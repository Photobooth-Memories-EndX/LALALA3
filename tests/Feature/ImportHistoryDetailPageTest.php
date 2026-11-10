<?php

namespace Tests\Feature;

use App\Models\ImportBatch;
use App\Models\ImportBatchRow;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Str;
use Tests\TestCase;

class ImportHistoryDetailPageTest extends TestCase
{
    use DatabaseTransactions;

    protected User $admin;
    protected User $regularUser;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::whereIn('role', [User::ROLE_ADMIN, User::ROLE_SUPER_ADMIN])->first()
            ?? $this->createTestUser(['role' => User::ROLE_ADMIN]);

        $this->regularUser = User::where('role', User::ROLE_USER)->first()
            ?? $this->createTestUser(['role' => User::ROLE_USER]);
    }

    protected function createTestUser(array $attrs = []): User
    {
        static $seq = 9000;
        $seq++;
        return User::create(array_merge([
            'name'         => 'Detail Test User ' . $seq,
            'email'        => "detail_user_{$seq}@posco.net",
            'password'     => bcrypt('secret'),
            'id_emp'       => 'DET_EMP_' . $seq,
            'id_user'      => 'DET_UID_' . $seq,
            'id_org_unit'  => 'OU_DET_01',
            'team'         => 'Detail Team',
            'department'   => 'Detail Dept',
            'division'     => 'Detail Div',
            'sap_code'     => 'SAP_DET_' . $seq,
            'ssohash'      => 'HASH_DET_' . $seq,
            'role'         => User::ROLE_USER,
            'company_name' => 'PT Krakatau Posco',
        ], $attrs));
    }

    protected function createBatch(string $sourceType = ImportBatch::SOURCE_PTKP): ImportBatch
    {
        $uuid = (string) Str::uuid();
        $batch = ImportBatch::create([
            'batch_uuid'        => $uuid,
            'source_type'       => $sourceType,
            'original_filename' => 'detail_test_file.xlsx',
            'file_path'         => "imports/ptkp/{$uuid}/detail_test.xlsx",
            'file_disk'         => 'local',
            'file_hash'         => hash('sha256', 'dummy_content'),
            'file_size'         => 2048,
            'imported_by'       => $this->admin->id,
            'status'            => ImportBatch::STATUS_COMPLETED_WITH_WARNINGS,
            'total_rows'        => 5,
            'valid_rows'        => 2,
            'warning_rows'      => 1,
            'duplicate_rows'    => 1,
            'invalid_rows'      => 1,
            'imported_rows'     => 3,
            'skipped_rows'      => 2,
            'failed_rows'       => 0,
            'trainings_created' => 1,
            'trainings_reused'  => 1,
            'started_at'        => now()->subMinutes(5),
            'finished_at'       => now(),
        ]);

        // Row 1: Valid
        ImportBatchRow::create([
            'import_batch_id'   => $batch->id,
            'row_number'        => 2,
            'sheet_name'        => 'Sheet1',
            'employee_id'       => 'EMP_DETAIL_01',
            'employee_name'     => 'Ahmad Detail',
            'course_name'       => 'Safety Operations',
            'validation_status' => ImportBatchRow::STATUS_VALID,
            'action'            => ImportBatchRow::ACTION_IMPORTED,
            'messages'          => ['Row is valid'],
            'row_data'          => ['employee_id' => 'EMP_DETAIL_01'],
        ]);

        // Row 2: Warning
        ImportBatchRow::create([
            'import_batch_id'   => $batch->id,
            'row_number'        => 3,
            'sheet_name'        => 'Sheet1',
            'employee_id'       => 'EMP_DETAIL_02',
            'employee_name'     => 'Bambang Warning',
            'course_name'       => 'Safety Operations',
            'validation_status' => ImportBatchRow::STATUS_WARNING,
            'action'            => ImportBatchRow::ACTION_IMPORTED,
            'messages'          => ['Non LMS User warning'],
            'row_data'          => ['employee_id' => 'EMP_DETAIL_02'],
        ]);

        return $batch;
    }

    /** 1. Authorized admin can view dedicated batch detail page */
    public function test_authorized_user_can_view_batch_detail_page(): void
    {
        $batch = $this->createBatch();

        $response = $this->actingAs($this->admin)
            ->get(route('manage.record.offline.import-history.detail', $batch->batch_uuid));

        $response->assertStatus(200);
        $response->assertViewIs('manage.record.offline.import-history.detail');
        $response->assertSee('Import Batch Detail');
        $response->assertSee($batch->batch_uuid);
        $response->assertSee('Back to Import History');
        $response->assertSee('Spreadsheet Rows Audit Log');
        $response->assertSee('Download Excel');
    }

    /** 2. Unauthorized user cannot access batch detail page */
    public function test_unauthorized_user_is_forbidden(): void
    {
        $batch = $this->createBatch();

        $this->actingAs($this->regularUser)
            ->get(route('manage.record.offline.import-history.detail', $batch->batch_uuid))
            ->assertStatus(403);
    }

    /** 3. Guest is redirected to login */
    public function test_guest_is_redirected_to_login(): void
    {
        $batch = $this->createBatch();

        $this->get(route('manage.record.offline.import-history.detail', $batch->batch_uuid))
            ->assertRedirect();
    }

    /** 4. Non-existent UUID returns 404 */
    public function test_non_existent_uuid_returns_404(): void
    {
        $dummyUuid = (string) Str::uuid();

        $this->actingAs($this->admin)
            ->get(route('manage.record.offline.import-history.detail', $dummyUuid))
            ->assertStatus(404);
    }

    /** 5. Invalid UUID format returns 404 */
    public function test_invalid_uuid_format_returns_404(): void
    {
        $this->actingAs($this->admin)
            ->get(route('manage.record.offline.import-history.detail', 'invalid-uuid-123'))
            ->assertStatus(404);
    }

    /** 6. Detail page renders all required batch info and metrics */
    public function test_detail_page_renders_all_required_info_and_metrics(): void
    {
        $batch = $this->createBatch();

        $response = $this->actingAs($this->admin)
            ->get(route('manage.record.offline.import-history.detail', $batch->batch_uuid));

        $response->assertStatus(200);
        // Page Header & Breadcrumbs
        $response->assertSee('Manage Record');
        $response->assertSee('Import History');
        $response->assertSee('Batch Detail');

        // Batch Info
        $response->assertSee('Source Type');
        $response->assertSee('PTKP');
        $response->assertSee('Batch Status');
        $response->assertSee('Imported By');
        $response->assertSee('Duration');
        $response->assertSee('Original Filename');
        $response->assertSee('detail_test_file.xlsx');
        $response->assertSee('Import Date');
        $response->assertSee('Started At');
        $response->assertSee('Finished At');

        // Evaluation & Persistence Metrics (Exactly 6 Cards)
        $response->assertSee('Evaluation & Persistence Metrics', false);
        $response->assertSee('VALID');
        $response->assertSee('WARNING');
        $response->assertSee('DUPLICATE');
        $response->assertSee('INVALID');
        $response->assertSee('INVALID COMPANY');
        $response->assertSee('ALL TOTAL');

        // Excluded metrics MUST NOT be rendered as separate cards
        $response->assertDontSee('Total Evaluated');
        $response->assertDontSee('Training Created');
        $response->assertDontSee('Training Reused');
        $response->assertDontSee('metrics-grid-10');

        // Rows Audit Log
        $response->assertSee('Action Filter');
        $response->assertSee('Validation Status');
        $response->assertSee('Search Employee ID, Name, or Course...');
        $response->assertSee('detail-rows-table');
    }

    /** 7. Detail page renders Non-PTKP batch properly */
    public function test_detail_page_renders_non_ptkp_batch(): void
    {
        $batch = $this->createBatch(ImportBatch::SOURCE_NON_PTKP);

        $response = $this->actingAs($this->admin)
            ->get(route('manage.record.offline.import-history.detail', $batch->batch_uuid));

        $response->assertStatus(200);
        $response->assertSee('Non-PTKP');
        $response->assertSee($batch->batch_uuid);
    }

    /** 8. Rows datatable provider returns JSON for detail page */
    public function test_rows_datatable_returns_rows_for_batch(): void
    {
        $batch = $this->createBatch();

        $response = $this->actingAs($this->admin)
            ->getJson(route('manage.record.offline.import-history.rows', $batch->batch_uuid));

        $response->assertStatus(200);
        $json = $response->json();
        $this->assertNotEmpty($json['data']);
        $this->assertEquals(2, count($json['data']));
        $this->assertEquals('EMP_DETAIL_01', $json['data'][0]['employee_id']);
        $this->assertEquals('Ahmad Detail', $json['data'][0]['employee_name']);
        $this->assertStringContainsString('VALID', $json['data'][0]['validation_status_badge']);
    }

    /** 9. Verify exactly 6 metric cards in mandatory order: VALID, WARNING, DUPLICATE, INVALID, INVALID COMPANY, ALL TOTAL */
    public function test_detail_page_renders_exactly_six_metric_cards_in_order(): void
    {
        $batch = $this->createBatch();

        $response = $this->actingAs($this->admin)
            ->get(route('manage.record.offline.import-history.detail', $batch->batch_uuid));

        $response->assertStatus(200);
        $content = $response->getContent();

        // Check cards order in HTML
        $posValid = strpos($content, '<div class="metric-lbl">VALID</div>');
        $posWarning = strpos($content, '<div class="metric-lbl">WARNING</div>');
        $posDuplicate = strpos($content, '<div class="metric-lbl">DUPLICATE</div>');
        $posInvalid = strpos($content, '<div class="metric-lbl">INVALID</div>');
        $posInvalidCompany = strpos($content, '<div class="metric-lbl">INVALID COMPANY</div>');
        $posAllTotal = strpos($content, '<div class="metric-lbl fw-bold text-primary">ALL TOTAL</div>');

        $this->assertNotFalse($posValid, 'VALID card not found');
        $this->assertNotFalse($posWarning, 'WARNING card not found');
        $this->assertNotFalse($posDuplicate, 'DUPLICATE card not found');
        $this->assertNotFalse($posInvalid, 'INVALID card not found');
        $this->assertNotFalse($posInvalidCompany, 'INVALID COMPANY card not found');
        $this->assertNotFalse($posAllTotal, 'ALL TOTAL card not found');

        $this->assertTrue($posValid < $posWarning, 'VALID must be before WARNING');
        $this->assertTrue($posWarning < $posDuplicate, 'WARNING must be before DUPLICATE');
        $this->assertTrue($posDuplicate < $posInvalid, 'DUPLICATE must be before INVALID');
        $this->assertTrue($posInvalid < $posInvalidCompany, 'INVALID must be before INVALID COMPANY');
        $this->assertTrue($posInvalidCompany < $posAllTotal, 'INVALID COMPANY must be before ALL TOTAL');

        // Check count of metric cards: exactly 6
        $cardCount = substr_count($content, 'metric-status-card');
        $this->assertEquals(6, $cardCount, 'There must be exactly 6 metric status cards');
    }
}
