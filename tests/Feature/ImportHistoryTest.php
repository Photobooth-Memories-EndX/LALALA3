<?php

namespace Tests\Feature;

use App\Models\ImportBatch;
use App\Models\ImportBatchRow;
use App\Models\OfflineTraining;
use App\Models\OfflineTrainingUser;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Tests\TestCase;

class ImportHistoryTest extends TestCase
{
    use DatabaseTransactions;

    protected User $admin;
    protected ?User $regularUser;
    protected array $createdStorageFiles = [];

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::whereIn('role', [User::ROLE_ADMIN, User::ROLE_SUPER_ADMIN])->first()
            ?? $this->createTestUser(['role' => User::ROLE_ADMIN]);

        $this->regularUser = User::where('role', User::ROLE_USER)->first()
            ?? $this->createTestUser(['role' => User::ROLE_USER]);
    }

    protected function tearDown(): void
    {
        foreach ($this->createdStorageFiles as $file) {
            if (Storage::disk('local')->exists($file)) {
                Storage::disk('local')->delete($file);
            }
        }

        parent::tearDown();
    }

    protected function createTestUser(array $attrs = []): User
    {
        static $seq = 9000;
        $seq++;
        return User::create(array_merge([
            'name'         => 'History Test User ' . $seq,
            'email'        => "history_user_{$seq}@posco.net",
            'password'     => bcrypt('secret'),
            'id_emp'       => 'H_EMP_' . $seq,
            'id_user'      => 'H_UID_' . $seq,
            'id_org_unit'  => 'OU_01',
            'team'         => 'History Team',
            'department'   => 'Audit Dept',
            'division'     => 'IT Division',
            'sap_code'     => 'SAP_' . $seq,
            'ssohash'      => 'HASH_' . $seq,
            'role'         => User::ROLE_USER,
            'company_name' => 'PT Krakatau Posco',
        ], $attrs));
    }

    protected function createTestBatch(array $attrs = []): ImportBatch
    {
        $uuid = $attrs['batch_uuid'] ?? (string) Str::uuid();

        return ImportBatch::create(array_merge([
            'batch_uuid'        => $uuid,
            'source_type'       => ImportBatch::SOURCE_PTKP,
            'original_filename' => 'test_audit.xlsx',
            'file_path'         => "imports/ptkp/{$uuid}/original.xlsx",
            'file_disk'         => 'local',
            'file_hash'         => hash('sha256', 'dummy'),
            'file_size'         => 1024,
            'imported_by'       => $this->admin->id,
            'status'            => ImportBatch::STATUS_COMPLETED,
            'total_rows'        => 10,
            'valid_rows'        => 8,
            'warning_rows'      => 0,
            'duplicate_rows'    => 2,
            'invalid_rows'      => 0,
            'imported_rows'     => 8,
            'skipped_rows'      => 2,
            'failed_rows'       => 0,
            'trainings_created' => 1,
            'trainings_reused'  => 0,
            'started_at'        => now()->subMinutes(5),
            'finished_at'       => now(),
        ], $attrs));
    }

    /** 1. Authorized user can view import history index */
    public function test_authorized_user_can_view_import_history_page(): void
    {
        $response = $this->actingAs($this->admin)
            ->get(route('manage.record.offline.import-history.index'));

        $response->assertStatus(200);
        $response->assertSee('Import History');
        $response->assertSee('id="import-batches-table"', false);
        $response->assertSee('id="importDetailModal"', false);
    }

    /** 2. Unauthorized user is forbidden */
    public function test_unauthorized_user_is_forbidden(): void
    {
        // Guest redirected to login
        $this->get(route('manage.record.offline.import-history.index'))
            ->assertRedirect(route('loginpage'));

        // Regular user denied with 403
        $this->actingAs($this->regularUser)
            ->get(route('manage.record.offline.import-history.index'))
            ->assertStatus(403);
    }

    /** 3. Batches list returns PTKP and Non PTKP records */
    public function test_batches_list_displays_ptkp_and_non_ptkp(): void
    {
        $batchPtkp = $this->createTestBatch(['source_type' => ImportBatch::SOURCE_PTKP]);
        $batchNonPtkp = $this->createTestBatch(['source_type' => ImportBatch::SOURCE_NON_PTKP]);

        $response = $this->actingAs($this->admin)
            ->getJson(route('manage.record.offline.import-history.datatable'));

        $response->assertStatus(200);
        $response->assertJsonFragment(['batch_uuid' => $batchPtkp->batch_uuid]);
        $response->assertJsonFragment(['batch_uuid' => $batchNonPtkp->batch_uuid]);
    }

    /** 4. Filter by source_type works */
    public function test_filter_source_type_works(): void
    {
        $batchPtkp = $this->createTestBatch(['source_type' => ImportBatch::SOURCE_PTKP]);
        $batchNonPtkp = $this->createTestBatch(['source_type' => ImportBatch::SOURCE_NON_PTKP]);

        $respPtkp = $this->actingAs($this->admin)
            ->getJson(route('manage.record.offline.import-history.datatable', ['source_type' => 'PTKP']));
        $respPtkp->assertJsonFragment(['batch_uuid' => $batchPtkp->batch_uuid]);
        $respPtkp->assertJsonMissing(['batch_uuid' => $batchNonPtkp->batch_uuid]);

        $respNonPtkp = $this->actingAs($this->admin)
            ->getJson(route('manage.record.offline.import-history.datatable', ['source_type' => 'NON_PTKP']));
        $respNonPtkp->assertJsonFragment(['batch_uuid' => $batchNonPtkp->batch_uuid]);
        $respNonPtkp->assertJsonMissing(['batch_uuid' => $batchPtkp->batch_uuid]);
    }

    /** 5. Filter by status works */
    public function test_filter_status_works(): void
    {
        $completed = $this->createTestBatch(['status' => ImportBatch::STATUS_COMPLETED]);
        $failed = $this->createTestBatch(['status' => ImportBatch::STATUS_FAILED]);

        $response = $this->actingAs($this->admin)
            ->getJson(route('manage.record.offline.import-history.datatable', ['status' => 'FAILED']));

        $response->assertJsonFragment(['batch_uuid' => $failed->batch_uuid]);
        $response->assertJsonMissing(['batch_uuid' => $completed->batch_uuid]);
    }

    /** 6. Filter by date range works */
    public function test_filter_date_range_works(): void
    {
        $past = $this->createTestBatch([
            'started_at' => now()->subDays(10),
            'finished_at' => now()->subDays(10)->addMinutes(2),
        ]);
        $today = $this->createTestBatch([
            'started_at' => now(),
            'finished_at' => now()->addMinutes(1),
        ]);

        $response = $this->actingAs($this->admin)
            ->getJson(route('manage.record.offline.import-history.datatable', [
                'date_start' => now()->subDay()->format('Y-m-d'),
                'date_end'   => now()->addDay()->format('Y-m-d'),
            ]));

        $response->assertJsonFragment(['batch_uuid' => $today->batch_uuid]);
        $response->assertJsonMissing(['batch_uuid' => $past->batch_uuid]);
    }

    /** 7. Pagination of batches list works */
    public function test_pagination_of_batches_list_works(): void
    {
        $this->createTestBatch();
        $this->createTestBatch();

        $response = $this->actingAs($this->admin)
            ->getJson(route('manage.record.offline.import-history.datatable', [
                'start'  => 0,
                'length' => 1,
            ]));

        $response->assertStatus(200);
        $data = $response->json('data');
        $this->assertCount(1, $data);
    }

    /** 8. Valid batch detail can be retrieved */
    public function test_valid_batch_detail_can_be_retrieved(): void
    {
        $batch = $this->createTestBatch([
            'total_rows'        => 25,
            'imported_rows'     => 20,
            'skipped_rows'      => 5,
            'trainings_created' => 2,
        ]);

        $response = $this->actingAs($this->admin)
            ->getJson(route('manage.record.offline.import-history.detail', $batch->batch_uuid));

        $response->assertStatus(200)
            ->assertJson([
                'batch_uuid' => $batch->batch_uuid,
                'counters'   => [
                    'total_rows'        => 25,
                    'imported_rows'     => 20,
                    'skipped_rows'      => 5,
                    'trainings_created' => 2,
                ],
            ]);
    }

    /** 9. Non-existent batch detail returns 404 */
    public function test_non_existent_batch_detail_returns_404(): void
    {
        $dummyUuid = (string) Str::uuid();

        $this->actingAs($this->admin)
            ->getJson(route('manage.record.offline.import-history.detail', $dummyUuid))
            ->assertStatus(404);
    }

    /** 10. Detail rows display correct action and validation status */
    public function test_rows_detail_displays_correct_action_and_validation_status(): void
    {
        $batch = $this->createTestBatch();

        ImportBatchRow::create([
            'import_batch_id'   => $batch->id,
            'row_number'        => 2,
            'employee_id'       => 'EMP_TEST_01',
            'employee_name'     => 'John Doe',
            'course_name'       => 'Safety Induction',
            'validation_status' => ImportBatchRow::STATUS_VALID,
            'action'            => ImportBatchRow::ACTION_IMPORTED,
        ]);

        ImportBatchRow::create([
            'import_batch_id'   => $batch->id,
            'row_number'        => 3,
            'employee_id'       => 'EMP_TEST_02',
            'employee_name'     => 'Jane Doe',
            'course_name'       => 'Safety Induction',
            'validation_status' => ImportBatchRow::STATUS_DUPLICATE,
            'action'            => ImportBatchRow::ACTION_SKIPPED,
        ]);

        $response = $this->actingAs($this->admin)
            ->getJson(route('manage.record.offline.import-history.rows', $batch->batch_uuid));

        $response->assertStatus(200);
        $data = $response->json('data');
        $this->assertCount(2, $data);

        $row1 = $data[0];
        $this->assertEquals(2, $row1['row_number']);
        $this->assertStringContainsString('VALID', $row1['validation_status_badge']);
        $this->assertStringContainsString('IMPORTED', $row1['action_badge']);

        $row2 = $data[1];
        $this->assertEquals(3, $row2['row_number']);
        $this->assertStringContainsString('DUPLICATE', $row2['validation_status_badge']);
        $this->assertStringContainsString('SKIPPED', $row2['action_badge']);
    }

    /** 11. Filtering of rows by action works */
    public function test_filtering_of_rows_by_action_works(): void
    {
        $batch = $this->createTestBatch();

        ImportBatchRow::create([
            'import_batch_id'   => $batch->id,
            'row_number'        => 1,
            'employee_id'       => 'EMP_1',
            'employee_name'     => 'User 1',
            'course_name'       => 'Course',
            'validation_status' => ImportBatchRow::STATUS_DUPLICATE,
            'action'            => ImportBatchRow::ACTION_SKIPPED,
        ]);
        ImportBatchRow::create([
            'import_batch_id'   => $batch->id,
            'row_number'        => 2,
            'employee_id'       => 'EMP_2',
            'employee_name'     => 'User 2',
            'course_name'       => 'Course',
            'validation_status' => ImportBatchRow::STATUS_VALID,
            'action'            => ImportBatchRow::ACTION_IMPORTED,
        ]);

        $response = $this->actingAs($this->admin)
            ->getJson(route('manage.record.offline.import-history.rows', [
                'batch_uuid' => $batch->batch_uuid,
                'action'     => ImportBatchRow::ACTION_SKIPPED,
            ]));

        $response->assertStatus(200);
        $this->assertCount(1, $response->json('data'));
    }

    /** 12. Pagination of rows works */
    public function test_pagination_of_rows_works(): void
    {
        $batch = $this->createTestBatch();

        for ($i = 1; $i <= 5; $i++) {
            ImportBatchRow::create([
                'import_batch_id'   => $batch->id,
                'row_number'        => $i,
                'employee_id'       => "EMP_{$i}",
                'employee_name'     => "User {$i}",
                'course_name'       => 'Course',
                'validation_status' => ImportBatchRow::STATUS_VALID,
                'action'            => ImportBatchRow::ACTION_IMPORTED,
            ]);
        }

        $response = $this->actingAs($this->admin)
            ->getJson(route('manage.record.offline.import-history.rows', [
                'batch_uuid' => $batch->batch_uuid,
                'start'      => 0,
                'length'     => 2,
            ]));

        $response->assertStatus(200);
        $this->assertCount(2, $response->json('data'));
    }

    /** 12. Download file succeeds for batch with available file */
    public function test_download_file_succeeds_for_batch_with_stored_file(): void
    {
        $uuid = (string) Str::uuid();
        $filePath = "imports/ptkp/{$uuid}/original.xlsx";

        Storage::disk('local')->put($filePath, 'PK_DUMMY_EXCEL_BYTES');
        $this->createdStorageFiles[] = $filePath;

        $batch = $this->createTestBatch([
            'batch_uuid'        => $uuid,
            'file_path'         => $filePath,
            'file_disk'         => 'local',
            'original_filename' => 'actual_audit.xlsx',
        ]);

        $response = $this->actingAs($this->admin)
            ->get(route('manage.record.offline.import-history.download', $batch->batch_uuid));

        $response->assertStatus(200);
        $response->assertHeader('Content-Disposition');
        $this->assertStringContainsString('actual_audit', $response->headers->get('Content-Disposition'));
    }

    /** 13. Download forbidden for unauthorized user */
    public function test_download_forbidden_for_unauthorized_user(): void
    {
        $batch = $this->createTestBatch();

        $this->actingAs($this->regularUser)
            ->get(route('manage.record.offline.import-history.download', $batch->batch_uuid))
            ->assertStatus(403);
    }

    /** 14. Invalid UUID or missing batch download rejected with 404 */
    public function test_invalid_uuid_or_missing_batch_download_rejected(): void
    {
        // Malformed UUID
        $this->actingAs($this->admin)
            ->get('/manage/record/offline/import-history/invalid-uuid/download')
            ->assertStatus(404);

        // Non-existent UUID
        $dummy = (string) Str::uuid();
        $this->actingAs($this->admin)
            ->get(route('manage.record.offline.import-history.download', $dummy))
            ->assertStatus(404);
    }

    /** 15. Missing file on disk returns 404 */
    public function test_missing_file_returns_appropriate_response(): void
    {
        $batch = $this->createTestBatch([
            'file_path' => 'imports/ptkp/non-existent-uuid/original.xlsx',
        ]);

        $this->actingAs($this->admin)
            ->get(route('manage.record.offline.import-history.download', $batch->batch_uuid))
            ->assertStatus(404);
    }

    /** 16. Download prevents path traversal */
    public function test_download_prevents_path_traversal(): void
    {
        $this->actingAs($this->admin)
            ->get('/manage/record/offline/import-history/../../etc/passwd/download')
            ->assertStatus(404);
    }

    /** 17. Malicious original filename is safely sanitized for download */
    public function test_malicious_filename_is_sanitized_for_download(): void
    {
        $uuid = (string) Str::uuid();
        $filePath = "imports/ptkp/{$uuid}/original.xlsx";
        Storage::disk('local')->put($filePath, 'PK_DUMMY_EXCEL_BYTES');
        $this->createdStorageFiles[] = $filePath;

        $batch = $this->createTestBatch([
            'batch_uuid'        => $uuid,
            'file_path'         => $filePath,
            'original_filename' => '../../../etc/passwd.xlsx',
        ]);

        $safeName = $batch->getSafeDownloadFilename();
        $this->assertStringNotContainsString('../', $safeName);
        $this->assertStringNotContainsString('/', $safeName);

        $response = $this->actingAs($this->admin)
            ->get(route('manage.record.offline.import-history.download', $batch->batch_uuid));

        $response->assertStatus(200);
        $disposition = $response->headers->get('Content-Disposition');
        $this->assertStringNotContainsString('../', $disposition);
    }

    /** 18. Page and detail access does not alter business tables */
    public function test_page_does_not_modify_business_tables(): void
    {
        $batch = $this->createTestBatch();

        $trainingsBefore = OfflineTraining::count();
        $participantsBefore = OfflineTrainingUser::count();

        $this->actingAs($this->admin)
            ->get(route('manage.record.offline.import-history.index'));
        $this->actingAs($this->admin)
            ->getJson(route('manage.record.offline.import-history.detail', $batch->batch_uuid));
        $this->actingAs($this->admin)
            ->getJson(route('manage.record.offline.import-history.rows', $batch->batch_uuid));

        $this->assertEquals($trainingsBefore, OfflineTraining::count());
        $this->assertEquals($participantsBefore, OfflineTrainingUser::count());
    }

    /** 19. Summary cards aggregate metrics properly without N+1 */
    public function test_summary_cards_aggregates_metrics(): void
    {
        $this->createTestBatch(['status' => ImportBatch::STATUS_COMPLETED]);
        $this->createTestBatch(['status' => ImportBatch::STATUS_COMPLETED_WITH_WARNINGS]);
        $this->createTestBatch(['status' => ImportBatch::STATUS_FAILED]);

        $response = $this->actingAs($this->admin)
            ->getJson(route('manage.record.offline.import-history.summary'));

        $response->assertStatus(200);
        $data = $response->json();
        $this->assertGreaterThanOrEqual(3, $data['total_batches']);
        $this->assertGreaterThanOrEqual(1, $data['completed_batches']);
        $this->assertGreaterThanOrEqual(1, $data['warning_batches']);
        $this->assertGreaterThanOrEqual(1, $data['failed_batches']);
    }
}
