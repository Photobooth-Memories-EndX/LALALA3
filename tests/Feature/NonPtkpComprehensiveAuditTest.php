<?php

namespace Tests\Feature;

use App\Models\ImportBatch;
use App\Models\ImportBatchRow;
use App\Models\OfflineTraining;
use App\Models\OfflineTrainingUser;
use App\Models\User;
use App\Services\Record\OfflineTrainingExcelSchema;
use App\Services\Record\OfflineTrainingImportService;
use App\Services\Record\PtkpExcelSchema;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Tests\TestCase;

class NonPtkpComprehensiveAuditTest extends TestCase
{
    protected ?User $admin = null;
    protected ?User $regularUser = null;
    protected array $createdTempFiles = [];

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::whereIn('role', [User::ROLE_ADMIN, User::ROLE_SUPER_ADMIN])->first()
            ?? $this->createTestUser(['role' => User::ROLE_ADMIN]);

        $this->regularUser = User::where('role', User::ROLE_USER)->first()
            ?? $this->createTestUser(['role' => User::ROLE_USER]);

        // Clean up test records
        DB::table('offline_training_users')->where('company', 'TEST_COMP_AUDIT_CORP')->delete();
        DB::table('offline_trainings')->where('category', 'TEST_COMP_AUDIT_CAT')->delete();
        DB::table('users')->where('email', 'like', 'compaudit_%@example.com')->delete();
    }

    protected function tearDown(): void
    {
        DB::table('offline_training_users')->where('company', 'TEST_COMP_AUDIT_CORP')->delete();
        DB::table('offline_trainings')->where('category', 'TEST_COMP_AUDIT_CAT')->delete();
        DB::table('users')->where('email', 'like', 'compaudit_%@example.com')->delete();

        foreach ($this->createdTempFiles as $path) {
            if (File::exists($path)) {
                @File::delete($path);
            }
        }

        parent::tearDown();
    }

    protected function createTestUser(array $attrs = []): User
    {
        static $seq = 7500;
        $seq++;
        return User::create(array_merge([
            'name'         => 'Comp Audit User ' . $seq,
            'email'        => "compaudit_{$seq}@example.com",
            'password'     => bcrypt('secret'),
            'id_emp'       => 'CA_' . $seq,
            'id_user'      => 'UCA_' . $seq,
            'id_org_unit'  => 'OU_CA',
            'team'         => 'Team Audit',
            'department'   => 'Dept Audit',
            'division'     => 'Division Quality',
            'sap_code'     => 'SCA_' . $seq,
            'ssohash'      => 'HCA_' . $seq,
            'role'         => User::ROLE_USER,
            'company_name' => 'PT Krakatau Posco',
        ], $attrs));
    }

    protected function buildNonPtkpRow(array $overrides = []): array
    {
        return array_merge([
            'employee_id'              => '100888',
            'name'                     => 'Audit Test Employee',
            'grade'                    => '4A',
            'job_position'             => 'Operations Engineer',
            'team'                     => 'Operations Team 1',
            'department'               => 'Steelmaking Dept',
            'company'                  => 'TEST_COMP_AUDIT_CORP',
            'course_name'              => 'Non-PTKP Standard Safety Operations',
            'sub_course_name'          => 'Emergency Protocols',
            'type'                     => 'In-House',
            'category'                 => 'TEST_COMP_AUDIT_CAT',
            'level'                    => 'Basic',
            'batch'                    => 'Batch 01',
            'main_classification'      => 'Basic & Common Duties',
            'sub_classification'       => 'Plant Safety',
            'delivery_method'          => 'Offline',
            'sub_delivery_method'      => 'Classroom',
            'provider'                 => 'Internal',
            'sub_provider'             => 'PTKP Trainer',
            'provider_name'            => 'Safety Dept Academy',
            'venue'                    => 'Training Center Room 201',
            'instructor'               => 'Senior Safety Officer',
            'duration_seconds'         => 28800,
            'learning_hours'           => 8.0,
            'progress'                 => 100,
            'start_date'               => '2026-05-10',
            'finish_date'              => '2026-05-10',
            'end_date'                 => '2026-05-10',
            'start_time'               => '08:00:00',
            'end_time'                 => '16:00:00',
            'record_month'             => 'May 2026',
            'month'                    => 'May 2026',
            'requirement'              => 'non mandatory',
            'attendance'               => 'Yes',
            'attendance_reason'        => 'Present all day',
            'bond_status'              => 'No',
            'bond_duration'            => null,
            'bond_end_date'            => null,
            'bond_amount'              => null,
            'l1_program'               => 'Satisfactory',
            'assessment_score'         => 88.5,
            'l2_verbal'                => 'Passed',
            'l3_numerical'             => 85.0,
            'l3_verbal'                => 'Effective',
            'certificate_availability' => 'Yes',
            'certificate_institution'  => 'Safety Academy',
            'certificate_issued_date'  => '2026-05-15',
            'certificate_expired_date' => '2028-05-15',
            'pic'                      => 'PIC Safety Training',
        ], $overrides);
    }

    protected function createSpreadsheet(array $rows, ?string $sheetTitle = null): UploadedFile
    {
        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle($sheetTitle ?? OfflineTrainingExcelSchema::DATA_SHEET_TITLE);

        // Canonical 46 columns
        $canonicalCols = PtkpExcelSchema::columns();

        // Row 1: Header (A=NO, B..AU=canonical columns)
        $sheet->setCellValue('A1', 'NO');
        foreach ($canonicalCols as $idx => $colDef) {
            $colLetter = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($idx + 1);
            $sheet->setCellValue($colLetter . '1', $colDef['header']);
        }

        // Data rows starting from Row 2
        $rowNum = 2;
        foreach ($rows as $data) {
            $sheet->setCellValue('A' . $rowNum, $rowNum - 1);
            foreach ($canonicalCols as $idx => $colDef) {
                $colLetter = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($idx + 1);
                $key = $colDef['key'];
                $val = $data[$key] ?? null;
                $sheet->setCellValueExplicit($colLetter . $rowNum, $val !== null ? (string) $val : '', \PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_STRING);
            }
            $rowNum++;
        }

        $tempPath = tempnam(sys_get_temp_dir(), 'audit_nonptkp_') . '.xlsx';
        $writer = new Xlsx($spreadsheet);
        $writer->save($tempPath);
        $this->createdTempFiles[] = $tempPath;

        return new UploadedFile($tempPath, 'audit_non_ptkp.xlsx', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet', null, true);
    }

    /**
     * 1. Test page access and permissions.
     */
    public function test_page_access_and_permissions(): void
    {
        // Guest is redirected to login
        $guestResp = $this->get('/manage/record/offline/import-non-ptkp');
        $this->assertTrue($guestResp->isRedirect() || $guestResp->getStatusCode() === 401);

        // Regular user without permission gets 403
        if ($this->regularUser) {
            $forbidResp = $this->actingAs($this->regularUser)->get('/manage/record/offline/import-non-ptkp');
            $forbidResp->assertStatus(403);
        }

        // Admin gets 200 and page rendered
        $adminResp = $this->actingAs($this->admin)->get('/manage/record/offline/import-non-ptkp');
        $adminResp->assertStatus(200);
        $adminResp->assertSee('Import Non-PTKP');
        $adminResp->assertSee('Upload File');
        $adminResp->assertSee('Validation & Review', false);
        $adminResp->assertSee('Import Records');
    }

    /**
     * 2. Test download template structure and sheets.
     */
    public function test_download_template(): void
    {
        $resp = $this->actingAs($this->admin)->get(route('manage.record.offline.import-non-ptkp.template'));
        $resp->assertStatus(200);
        $this->assertStringContainsString('application/vnd.openxmlformats-officedocument.spreadsheetml.sheet', $resp->headers->get('content-type'));
        $this->assertStringContainsString(OfflineTrainingExcelSchema::TEMPLATE_FILENAME, $resp->headers->get('content-disposition'));
    }

    /**
     * 3. Test uploading unsupported file format is rejected.
     */
    public function test_upload_unsupported_file_extension(): void
    {
        $fakePdf = UploadedFile::fake()->create('document.pdf', 100, 'application/pdf');

        $resp = $this->actingAs($this->admin)->postJson(route('manage.record.offline.import-non-ptkp.preview'), [
            'file' => $fakePdf,
        ]);

        $resp->assertStatus(422);
        $resp->assertJson(['success' => false]);
        $this->assertStringContainsString('Invalid file format', $resp->json('message'));
    }

    /**
     * 4. Test uploading empty spreadsheet (0 data rows).
     */
    public function test_upload_empty_spreadsheet(): void
    {
        $file = $this->createSpreadsheet([]); // 0 data rows

        $resp = $this->actingAs($this->admin)->post(route('manage.record.offline.import-non-ptkp.preview'), [
            'file' => $file,
        ]);

        $resp->assertStatus(200);
        $data = $resp->json('data');
        $this->assertEquals(0, $data['stats']['total_rows']);
        $this->assertEquals('EMPTY_FILE', $data['preview_state']);
        $this->assertFalse($data['can_confirm']);
    }

    /**
     * 5. Test missing mandatory headers throws validation error.
     */
    public function test_upload_mismatched_headers(): void
    {
        // Spreadsheet with completely wrong headers
        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setCellValue('A1', 'Random Col 1');
        $sheet->setCellValue('B1', 'Random Col 2');
        $sheet->setCellValue('A2', 'Data 1');
        $sheet->setCellValue('B2', 'Data 2');

        $tempPath = tempnam(sys_get_temp_dir(), 'bad_headers_') . '.xlsx';
        (new Xlsx($spreadsheet))->save($tempPath);
        $this->createdTempFiles[] = $tempPath;

        $badFile = new UploadedFile($tempPath, 'bad_headers.xlsx', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet', null, true);

        $resp = $this->actingAs($this->admin)->post(route('manage.record.offline.import-non-ptkp.preview'), [
            'file' => $badFile,
        ]);

        $resp->assertStatus(200);
        $resp->assertJson([
            'success'       => true,
            'can_confirm'   => false,
            'preview_state' => 'HEADER_ERROR',
        ]);
        $this->assertFalse($resp->json('data.header_check.can_proceed'));
        $this->assertNotEmpty($resp->json('data.header_check.issues'));
    }

    /**
     * 6. Test valid employee ID matching LMS user.
     */
    public function test_valid_employee_id_matching(): void
    {
        $testUser = $this->createTestUser();

        $row = $this->buildNonPtkpRow([
            'employee_id' => $testUser->id_emp,
            'name'        => $testUser->name,
            'course_name' => 'Safety Audit Course 101',
        ]);
        $file = $this->createSpreadsheet([$row]);

        $resp = $this->actingAs($this->admin)->post(route('manage.record.offline.import-non-ptkp.preview'), [
            'file' => $file,
        ]);

        $resp->assertStatus(200);
        $data = $resp->json('data');
        $this->assertEquals(1, $data['stats']['total_rows']);
        $this->assertEquals(1, $data['stats']['valid']);
        $this->assertEquals('valid', $data['preview_rows'][0]['status']);
        $this->assertTrue($data['preview_rows'][0]['user_matched']);
        $this->assertEquals($testUser->id, $data['preview_rows'][0]['data']['user_id']);
    }

    /**
     * 7. Test unmatched employee ID produces non-blocking warning and user_id is null.
     */
    public function test_unmatched_employee_id_handling(): void
    {
        $row = $this->buildNonPtkpRow([
            'employee_id' => 'NON_EXISTENT_NIP_99999',
            'name'        => 'Contractor Without LMS Account',
            'course_name' => 'Contractor Safety Induction',
        ]);
        $file = $this->createSpreadsheet([$row]);

        $resp = $this->actingAs($this->admin)->post(route('manage.record.offline.import-non-ptkp.preview'), [
            'file' => $file,
        ]);

        $resp->assertStatus(200);
        $data = $resp->json('data');
        $this->assertEquals(1, $data['stats']['warning']);
        $this->assertEquals(1, $data['importable_count']);
        $this->assertEquals('warning', $data['preview_rows'][0]['status']);
        $this->assertFalse($data['preview_rows'][0]['user_matched']);
        $this->assertNull($data['preview_rows'][0]['data']['user_id']);
        $this->assertTrue($data['can_confirm']);
    }

    /**
     * 8. Test missing course title is invalid (blocking).
     */
    public function test_missing_course_title_is_invalid(): void
    {
        $row = $this->buildNonPtkpRow([
            'course_name' => '', // Empty course
        ]);
        $file = $this->createSpreadsheet([$row]);

        $resp = $this->actingAs($this->admin)->post(route('manage.record.offline.import-non-ptkp.preview'), [
            'file' => $file,
        ]);

        $resp->assertStatus(200);
        $data = $resp->json('data');
        $this->assertEquals(1, $data['stats']['invalid']);
        $this->assertEquals('invalid', $data['preview_rows'][0]['status']);
        $this->assertFalse($data['can_confirm']);
    }

    /**
     * 9. Test missing both employee ID and name is invalid (blocking).
     */
    public function test_missing_both_employee_id_and_name_is_invalid(): void
    {
        $row = $this->buildNonPtkpRow([
            'employee_id' => '',
            'name'        => '',
        ]);
        $file = $this->createSpreadsheet([$row]);

        $resp = $this->actingAs($this->admin)->post(route('manage.record.offline.import-non-ptkp.preview'), [
            'file' => $file,
        ]);

        $resp->assertStatus(200);
        $data = $resp->json('data');
        $this->assertEquals(1, $data['stats']['invalid']);
        $this->assertEquals('invalid', $data['preview_rows'][0]['status']);
    }

    /**
     * 10. Test invalid dates (finish < start, or invalid date string).
     */
    public function test_invalid_dates_validation(): void
    {
        // finish date before start date
        $row1 = $this->buildNonPtkpRow([
            'start_date'  => '2026-06-15',
            'finish_date' => '2026-06-10', // earlier!
        ]);
        $file = $this->createSpreadsheet([$row1]);

        $resp = $this->actingAs($this->admin)->post(route('manage.record.offline.import-non-ptkp.preview'), [
            'file' => $file,
        ]);

        $resp->assertStatus(200);
        $data = $resp->json('data');
        $this->assertEquals(1, $data['stats']['invalid']);
        $this->assertEquals('invalid', $data['preview_rows'][0]['status']);
        $this->assertStringContainsString('Finish date cannot be earlier', json_encode($data['preview_rows'][0]['messages']));
    }

    /**
     * 11. Test invalid learning hours.
     */
    public function test_invalid_learning_hours_validation(): void
    {
        $row = $this->buildNonPtkpRow([
            'learning_hours' => 'not_a_number_hours',
        ]);
        $file = $this->createSpreadsheet([$row]);

        $resp = $this->actingAs($this->admin)->post(route('manage.record.offline.import-non-ptkp.preview'), [
            'file' => $file,
        ]);

        $resp->assertStatus(200);
        $data = $resp->json('data');
        $this->assertEquals(1, $data['stats']['invalid']);
        $this->assertEquals('invalid', $data['preview_rows'][0]['status']);
    }

    /**
     * 12. Test duplicate row in same file is skipped.
     */
    public function test_in_file_duplicate_detection(): void
    {
        $testUser = $this->createTestUser();

        $row1 = $this->buildNonPtkpRow(['employee_id' => $testUser->id_emp, 'name' => $testUser->name, 'course_name' => 'Same Course Title']);
        $row2 = $this->buildNonPtkpRow(['employee_id' => $testUser->id_emp, 'name' => $testUser->name, 'course_name' => 'Same Course Title']);
        $file = $this->createSpreadsheet([$row1, $row2]);

        $resp = $this->actingAs($this->admin)->post(route('manage.record.offline.import-non-ptkp.preview'), [
            'file' => $file,
        ]);

        $resp->assertStatus(200);
        $data = $resp->json('data');
        $this->assertEquals(2, $data['stats']['total_rows']);
        $this->assertEquals(1, $data['stats']['valid']);
        $this->assertEquals(1, $data['stats']['duplicate']);
        $this->assertEquals('valid', $data['preview_rows'][0]['status']);
        $this->assertEquals('duplicate', $data['preview_rows'][1]['status']);
    }

    /**
     * 13. Test partial import (valid + warning + invalid + duplicate) imports only eligible records.
     */
    public function test_partial_import_saves_valid_and_skips_invalid_and_duplicate(): void
    {
        $user1 = $this->createTestUser();
        $user2 = $this->createTestUser();

        $rowValid = $this->buildNonPtkpRow([
            'employee_id' => $user1->id_emp,
            'name'        => $user1->name,
            'course_name' => 'Partial Import Course Alpha',
        ]);

        $rowWarning = $this->buildNonPtkpRow([
            'employee_id' => 'UNMATCHED_USER_888',
            'name'        => 'Contractor Budi',
            'course_name' => 'Partial Import Course Alpha',
        ]);

        $rowInvalid = $this->buildNonPtkpRow([
            'employee_id' => $user2->id_emp,
            'name'        => $user2->name,
            'course_name' => '', // Invalid!
        ]);

        $rowDuplicate = $this->buildNonPtkpRow([
            'employee_id' => $user1->id_emp,
            'name'        => $user1->name,
            'course_name' => 'Partial Import Course Alpha', // Duplicate of rowValid!
        ]);

        $file = $this->createSpreadsheet([$rowValid, $rowWarning, $rowInvalid, $rowDuplicate]);

        $previewResp = $this->actingAs($this->admin)->post(route('manage.record.offline.import-non-ptkp.preview'), [
            'file' => $file,
        ]);

        $previewResp->assertStatus(200);
        $token = $previewResp->json('data.token');
        $stats = $previewResp->json('data.stats');

        $this->assertEquals(4, $stats['total_rows']);
        $this->assertEquals(1, $stats['valid']);
        $this->assertEquals(1, $stats['warning']);
        $this->assertEquals(1, $stats['invalid']);
        $this->assertEquals(1, $stats['duplicate']);
        $this->assertEquals(2, $stats['importable_count']);

        // Confirm should succeed for the 2 eligible records
        $confirmResp = $this->actingAs($this->admin)->post(route('manage.record.offline.import-non-ptkp.confirm'), [
            'token' => $token,
        ]);

        $confirmResp->assertStatus(200);
        $result = $confirmResp->json('result');
        $this->assertEquals(2, $result['imported']);
        $this->assertEquals(1, $result['skipped_duplicates']);
        $this->assertEquals(1, $result['invalid']);

        // Verify database: exactly 1 OfflineTraining created, 2 OfflineTrainingUsers created
        $training = OfflineTraining::where('course_name', 'Partial Import Course Alpha')->first();
        $this->assertNotNull($training);
        $this->assertEquals(2, OfflineTrainingUser::where('offline_training_id', $training->id)->count());

        // One linked to user1, one with user_id NULL for unmatched
        $this->assertTrue(OfflineTrainingUser::where('offline_training_id', $training->id)->where('user_id', $user1->id)->exists());
        $this->assertTrue(OfflineTrainingUser::where('offline_training_id', $training->id)->whereNull('user_id')->where('employee_id', 'UNMATCHED_USER_888')->exists());
    }

    /**
     * 14. Test re-importing identical file safely skips all records as duplicates without duplicate rows.
     */
    public function test_reimport_identical_file_safely_skips_all_duplicates(): void
    {
        $user = $this->createTestUser();
        $row = $this->buildNonPtkpRow([
            'employee_id' => $user->id_emp,
            'name'        => $user->name,
            'course_name' => 'Reimport Safety Drill',
        ]);
        $file1 = $this->createSpreadsheet([$row]);

        // First import
        $prev1 = $this->actingAs($this->admin)->post(route('manage.record.offline.import-non-ptkp.preview'), ['file' => $file1]);
        $token1 = $prev1->json('data.token');
        $this->actingAs($this->admin)->post(route('manage.record.offline.import-non-ptkp.confirm'), ['token' => $token1]);

        $countBefore = OfflineTrainingUser::where('employee_id', $user->id_emp)->count();
        $this->assertEquals(1, $countBefore);

        // Second import of same file
        $file2 = $this->createSpreadsheet([$row]);
        $prev2 = $this->actingAs($this->admin)->post(route('manage.record.offline.import-non-ptkp.preview'), ['file' => $file2]);

        $stats2 = $prev2->json('data.stats');
        $this->assertEquals(1, $stats2['duplicate']);
        $this->assertEquals(0, $stats2['importable_count']);
        $this->assertEquals('NO_NEW_RECORDS', $prev2->json('data.preview_state'));
        $this->assertFalse($prev2->json('data.can_confirm'));

        $countAfter = OfflineTrainingUser::where('employee_id', $user->id_emp)->count();
        $this->assertEquals(1, $countAfter); // Still exactly 1!
    }

    /**
     * 15. Test audit batch logging in import_batches and import_batch_rows.
     */
    public function test_audit_batch_logging(): void
    {
        $user = $this->createTestUser();
        $row = $this->buildNonPtkpRow([
            'employee_id' => $user->id_emp,
            'name'        => $user->name,
            'course_name' => 'Audit Batch Test Course',
        ]);
        $file = $this->createSpreadsheet([$row]);

        $prev = $this->actingAs($this->admin)->post(route('manage.record.offline.import-non-ptkp.preview'), ['file' => $file]);
        $token = $prev->json('data.token');
        $confirm = $this->actingAs($this->admin)->post(route('manage.record.offline.import-non-ptkp.confirm'), ['token' => $token]);

        $batchUuid = $confirm->json('result.batch_uuid');
        $this->assertNotEmpty($batchUuid);

        $batch = ImportBatch::where('batch_uuid', $batchUuid)->first();
        $this->assertNotNull($batch);
        $this->assertEquals(ImportBatch::SOURCE_NON_PTKP, $batch->source_type);
        $this->assertEquals(ImportBatch::STATUS_COMPLETED, $batch->status);
        $this->assertEquals(1, $batch->imported_rows);

        // Verify batch rows
        $batchRows = ImportBatchRow::where('import_batch_id', $batch->id)->get();
        $this->assertCount(1, $batchRows);
        $this->assertEquals(ImportBatchRow::ACTION_IMPORTED, $batchRows[0]->action);
        $this->assertEquals($user->id_emp, $batchRows[0]->employee_id);
    }

    /**
     * 16. Test cancel session cleans up temp file and cache.
     */
    public function test_cancel_session_cleans_up_temporary_file(): void
    {
        $file = $this->createSpreadsheet([$this->buildNonPtkpRow()]);
        $prev = $this->actingAs($this->admin)->post(route('manage.record.offline.import-non-ptkp.preview'), ['file' => $file]);
        $token = $prev->json('data.token');

        $this->assertNotNull(Cache::get(OfflineTrainingImportService::CACHE_PREFIX . $token));

        $cancelResp = $this->actingAs($this->admin)->post(route('manage.record.offline.import-non-ptkp.cancel'), ['token' => $token]);
        $cancelResp->assertStatus(200);
        $cancelResp->assertJson(['success' => true]);

        $this->assertNull(Cache::get(OfflineTrainingImportService::CACHE_PREFIX . $token));
    }

    /**
     * 17. Test same employee enrolled in different training courses are not considered duplicates.
     */
    public function test_different_trainings_for_same_employee_are_not_duplicates(): void
    {
        $user = $this->createTestUser();
        $row1 = $this->buildNonPtkpRow(['employee_id' => $user->id_emp, 'name' => $user->name, 'course_name' => 'Course Alpha']);
        $row2 = $this->buildNonPtkpRow(['employee_id' => $user->id_emp, 'name' => $user->name, 'course_name' => 'Course Beta']);
        $file = $this->createSpreadsheet([$row1, $row2]);

        $prev = $this->actingAs($this->admin)->post(route('manage.record.offline.import-non-ptkp.preview'), ['file' => $file]);
        $stats = $prev->json('data.stats');

        $this->assertEquals(2, $stats['total_rows']);
        $this->assertEquals(2, $stats['valid']);
        $this->assertEquals(0, $stats['duplicate']);
        $this->assertEquals('valid', $prev->json('data.preview_rows.0.status'));
        $this->assertEquals('valid', $prev->json('data.preview_rows.1.status'));
    }

    /**
     * 18. Test empty company is flagged as invalid_company and remains importable with review note.
     */
    public function test_empty_company_marked_invalid_company_and_importable(): void
    {
        $user = $this->createTestUser();
        $row = $this->buildNonPtkpRow([
            'employee_id' => $user->id_emp,
            'name'        => $user->name,
            'company'     => '', // Empty company
            'course_name' => 'Plant Safety Awareness',
        ]);
        $file = $this->createSpreadsheet([$row]);

        $prev = $this->actingAs($this->admin)->post(route('manage.record.offline.import-non-ptkp.preview'), ['file' => $file]);
        $stats = $prev->json('data.stats');

        $this->assertEquals(1, $stats['invalid_company']);
        $this->assertEquals(1, $stats['importable_count']);
        $this->assertEquals('invalid_company', $prev->json('data.preview_rows.0.status'));
        $this->assertTrue($prev->json('data.can_confirm'));
    }

    /**
     * 19. Test both route names (import-non-ptkp and import) work identically for backward compatibility.
     */
    public function test_both_route_names_work_identically(): void
    {
        // 1. GET /manage/record/offline/import redirects to /manage/record/offline/import-non-ptkp
        $resp = $this->actingAs($this->admin)->get(route('manage.record.offline.import'));
        $resp->assertRedirect(route('manage.record.offline.import-non-ptkp'));

        // 2. Both template routes return identical template
        $resp1 = $this->actingAs($this->admin)->get(route('manage.record.offline.import-non-ptkp.template'));
        $resp2 = $this->actingAs($this->admin)->get(route('manage.record.offline.import.template'));
        $resp1->assertOk();
        $resp2->assertOk();
    }
}
