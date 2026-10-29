<?php

namespace Tests\Feature;

use App\Exports\Manage\PtkpImportTemplateExport;
use App\Models\ImportBatch;
use App\Models\ImportBatchRow;
use App\Models\OfflineTraining;
use App\Models\OfflineTrainingUser;
use App\Models\User;
use App\Services\OfflineTraining\PtkpDatabaseWriter;
use App\Services\Record\OfflineTrainingExcelSchema;
use App\Services\Record\PtkpExcelSchema;
use Exception;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Tests\TestCase;

class ImportLogIntegrationTest extends TestCase
{
    use DatabaseTransactions;

    protected User $adminUser;
    protected User $testEmployee;

    protected function setUp(): void
    {
        parent::setUp();

        $this->adminUser = User::whereIn('role', [User::ROLE_ADMIN, User::ROLE_SUPER_ADMIN])->first()
            ?? $this->createTestUser(['role' => User::ROLE_ADMIN]);

        $this->testEmployee = $this->createTestUser([
            'id_emp'       => 'LOG_EMP_' . strtoupper(Str::random(4)),
            'name'         => 'Employee Log Test',
            'jobgrade'     => '3A',
            'jobposition'  => 'Log Inspector',
            'division'     => 'Plant Division',
            'team'         => 'Audit Team',
            'department'   => 'QA Dept',
            'company_name' => 'PT Krakatau Posco',
            'role'         => User::ROLE_USER,
        ]);
    }

    protected function createTestUser(array $attrs = []): User
    {
        static $seq = 8000;
        $seq++;
        return User::create(array_merge([
            'name'         => 'Test User ' . $seq,
            'email'        => "testuser_{$seq}@posco.net",
            'username'     => 'user_' . $seq,
            'password'     => bcrypt('secret'),
            'id_emp'       => 'EMP_' . $seq,
            'id_user'      => 'UID_' . $seq,
            'id_org_unit'  => 'OU_01',
            'team'         => 'Audit Team',
            'department'   => 'QA Dept',
            'division'     => 'Plant Division',
            'sap_code'     => 'SAP_' . $seq,
            'ssohash'      => 'HASH_' . $seq,
            'role'         => User::ROLE_USER,
            'company_name' => 'PT Krakatau Posco',
        ], $attrs));
    }

    /**
     * Test successful PTKP Import creates batch log, records rows, and saves source file to private disk.
     */
    public function test_ptkp_import_creates_batch_log_and_stores_source_file(): void
    {
        $rows = [[
            'row_no'         => 1,
            'employee_id'    => $this->testEmployee->id_emp,
            'company'        => 'PT Krakatau Posco',
            'course_name'    => 'Log Audit Training ISO 9001',
            'start_date'     => '2026-05-10',
            'finish_date'    => '2026-05-11',
            'learning_hours' => 8.0,
            'type'           => 'Public',
            'venue'          => 'Room Audit A',
        ]];

        $file = $this->createPtkpSpreadsheet($rows);

        $previewResp = $this->actingAs($this->adminUser)->post(route('manage.record.offline.import-ptkp.preview'), ['file' => $file]);
        $previewResp->assertStatus(200);
        $token = $previewResp->json('token');

        $this->assertEquals(0, ImportBatch::where('source_type', ImportBatch::SOURCE_PTKP)->where('original_filename', 'ptkp_log_test.xlsx')->count());

        $confirmResp = $this->actingAs($this->adminUser)->post(route('manage.record.offline.import-ptkp.confirm'), ['token' => $token]);
        $confirmResp->assertStatus(200);
        $json = $confirmResp->json();

        $this->assertTrue($json['success']);
        $batchUuid = $json['result']['batch_uuid'];
        $this->assertNotEmpty($batchUuid);

        // 3. Verify ImportBatch
        $batch = ImportBatch::where('batch_uuid', $batchUuid)->first();
        $this->assertNotNull($batch);
        $this->assertSame(ImportBatch::SOURCE_PTKP, $batch->source_type);
        $this->assertSame('ptkp_log_test.xlsx', $batch->original_filename);
        $this->assertSame(ImportBatch::STATUS_COMPLETED, $batch->status);
        $this->assertSame($this->adminUser->id, $batch->imported_by);
        $this->assertSame(1, $batch->total_rows);
        $this->assertSame(1, $batch->valid_rows);
        $this->assertSame(1, $batch->imported_rows);
        $this->assertSame(0, $batch->skipped_rows);
        $this->assertSame(1, $batch->trainings_created);

        // 4. Verify private storage & hash
        $this->assertTrue($batch->hasStoredFile());
        $this->assertStringStartsWith('imports/ptkp/' . $batchUuid, $batch->file_path);
        $this->assertFalse(Storage::disk('public')->exists($batch->file_path));
        $this->assertSame('local', $batch->file_disk);
        $this->assertNotEmpty($batch->file_hash);
        $this->assertGreaterThan(0, $batch->file_size);

        // 5. Verify ImportBatchRow
        $batchRows = $batch->rows;
        $this->assertCount(1, $batchRows);
        $row = $batchRows->first();
        $this->assertSame(ImportBatchRow::STATUS_VALID, $row->validation_status);
        $this->assertSame(ImportBatchRow::ACTION_IMPORTED, $row->action);
        $this->assertSame($this->testEmployee->id_emp, $row->employee_id);
        $this->assertNotNull($row->offline_training_id);
        $this->assertNotNull($row->offline_training_user_id);

        // Clean up stored file
        app(\App\Services\ImportLog\ImportFileStorageService::class)->deleteStoredFile($batch);
    }

    /**
     * Test successful Non-PTKP Import creates batch log, records rows, and saves source file.
     */
    public function test_non_ptkp_import_creates_batch_log_and_stores_source_file(): void
    {
        $row = $this->buildNonPtkpRow([
            'employee_id' => $this->testEmployee->id_emp,
            'name'        => $this->testEmployee->name,
            'course_name' => 'Non-PTKP Fire Fighting ' . Str::random(4),
            'attendance'  => 'Yes',
        ]);

        $file = $this->createNonPtkpSpreadsheet([$row]);

        // 1. Preview
        $previewResp = $this->actingAs($this->adminUser)
            ->post(route('manage.record.offline.import-non-ptkp.preview'), ['file' => $file]);
        $previewResp->assertStatus(200);
        $token = $previewResp->json('token');

        // 2. Confirm
        $confirmResp = $this->actingAs($this->adminUser)
            ->post(route('manage.record.offline.import-non-ptkp.confirm'), ['token' => $token]);
        $confirmResp->assertStatus(200);
        $json = $confirmResp->json();

        $batchUuid = $json['result']['batch_uuid'];
        $batch = ImportBatch::where('batch_uuid', $batchUuid)->first();

        $this->assertNotNull($batch);
        $this->assertSame(ImportBatch::SOURCE_NON_PTKP, $batch->source_type);
        $this->assertSame(ImportBatch::STATUS_COMPLETED, $batch->status);
        $this->assertSame(1, $batch->total_rows);
        $this->assertSame(1, $batch->imported_rows);
        $this->assertTrue($batch->hasStoredFile());

        $row = $batch->rows()->first();
        $this->assertSame(ImportBatchRow::ACTION_IMPORTED, $row->action);
        $this->assertNotNull($row->offline_training_id);
        $this->assertNotNull($row->offline_training_user_id);

        app(\App\Services\ImportLog\ImportFileStorageService::class)->deleteStoredFile($batch);
    }

    /**
     * Test duplicate and warning rows are correctly logged with status and actions.
     */
    public function test_ptkp_duplicate_and_warning_rows_logged_accurately(): void
    {
        $courseTitle = 'Safety Culture Wave ' . Str::random(4);

        // Create an existing training and participant in database to trigger DB duplicate
        $existingTraining = OfflineTraining::create([
            'course_name'    => $courseTitle,
            'batch'          => 'Batch 1',
            'start_date'     => '2026-07-01',
            'finish_date'    => '2026-07-01',
            'type'           => 'In-House',
            'venue'          => 'Room A',
            'learning_hours' => 2.0,
            'created_by'     => $this->adminUser->id,
        ]);

        OfflineTrainingUser::create([
            'offline_training_id' => $existingTraining->id,
            'user_id'             => $this->testEmployee->id,
            'employee_id'         => $this->testEmployee->id_emp,
            'name'                => $this->testEmployee->name,
            'company'             => 'PT Krakatau Posco',
        ]);

        // Upload file with:
        // Row 1: Duplicate against DB
        // Row 2: Warning (company mismatch, importable)
        $rows = [
            [
                'row_no' => 1, 'employee_id' => $this->testEmployee->id_emp, 'company' => 'PT Krakatau Posco',
                'course_name' => $courseTitle, 'batch' => 'Batch 1', 'start_date' => '2026-07-01', 'finish_date' => '2026-07-01',
                'learning_hours' => 2.0, 'type' => 'In-House', 'venue' => 'Room A',
            ],
            [
                'row_no' => 2, 'employee_id' => $this->testEmployee->id_emp, 'company' => 'PT Krakatau Tirta Industri',
                'course_name' => 'New Water Tech Class ' . Str::random(4), 'batch' => 'Batch 1', 'start_date' => '2026-07-05',
                'finish_date' => '2026-07-05', 'learning_hours' => 4.0, 'type' => 'In-House', 'venue' => 'Room A',
            ],
        ];

        $file = $this->createPtkpSpreadsheet($rows);

        $previewResp = $this->actingAs($this->adminUser)
            ->post(route('manage.record.offline.import-ptkp.preview'), ['file' => $file]);
        $token = $previewResp->json('token');

        $confirmResp = $this->actingAs($this->adminUser)
            ->post(route('manage.record.offline.import-ptkp.confirm'), ['token' => $token]);
        $confirmResp->assertStatus(200);

        $batchUuid = $confirmResp->json('result.batch_uuid');
        $batch = ImportBatch::where('batch_uuid', $batchUuid)->first();

        $this->assertNotNull($batch);
        $this->assertSame(ImportBatch::STATUS_COMPLETED_WITH_WARNINGS, $batch->status);
        $this->assertSame(2, $batch->total_rows);
        $this->assertSame(1, $batch->duplicate_rows);
        $this->assertSame(1, $batch->warning_rows);
        $this->assertSame(1, $batch->imported_rows);
        $this->assertSame(1, $batch->skipped_rows);

        // Check rows
        $duplicateRow = $batch->rows()->where('row_number', 2)->first();
        $this->assertSame(ImportBatchRow::STATUS_DUPLICATE, $duplicateRow->validation_status);
        $this->assertSame(ImportBatchRow::ACTION_SKIPPED, $duplicateRow->action);
        $this->assertNull($duplicateRow->offline_training_user_id);

        $warningRow = $batch->rows()->where('row_number', 3)->first();
        $this->assertSame(ImportBatchRow::STATUS_INVALID_COMPANY, $warningRow->validation_status);
        $this->assertSame(ImportBatchRow::ACTION_IMPORTED, $warningRow->action);
        $this->assertNotNull($warningRow->offline_training_user_id);

        app(\App\Services\ImportLog\ImportFileStorageService::class)->deleteStoredFile($batch);
    }

    /**
     * Test transaction rollback marks batch as FAILED, records no imported rows, and keeps audit file.
     */
    public function test_transaction_rollback_marks_batch_as_failed(): void
    {
        $rows = [[
            'row_no' => 1, 'employee_id' => $this->testEmployee->id_emp, 'company' => 'PT Krakatau Posco',
            'course_name' => 'Crash Course ' . Str::random(4), 'start_date' => '2026-08-01', 'finish_date' => '2026-08-01',
            'learning_hours' => 2.0, 'type' => 'Internal', 'venue' => 'Lab',
        ]];

        $file = $this->createPtkpSpreadsheet($rows);

        $previewResp = $this->actingAs($this->adminUser)
            ->post(route('manage.record.offline.import-ptkp.preview'), ['file' => $file]);
        $token = $previewResp->json('token');

        // Mock PtkpDatabaseWriter to throw an exception during persist
        $mockWriter = $this->createMock(PtkpDatabaseWriter::class);
        $mockWriter->method('persistTrainingsAndParticipants')
            ->willThrowException(new Exception('Simulated fatal database connection abort (SQL: SELECT secret_token FROM secrets)'));

        $this->app->instance(PtkpDatabaseWriter::class, $mockWriter);

        $confirmResp = $this->actingAs($this->adminUser)
            ->post(route('manage.record.offline.import-ptkp.confirm'), ['token' => $token]);

        $confirmResp->assertStatus(422);

        // Find the failed batch
        $batch = ImportBatch::where('source_type', ImportBatch::SOURCE_PTKP)
            ->where('imported_by', $this->adminUser->id)
            ->latest('id')
            ->first();

        $this->assertNotNull($batch);
        $this->assertSame(ImportBatch::STATUS_FAILED, $batch->status);
        $this->assertSame(0, $batch->imported_rows);
        $this->assertSame(1, $batch->failed_rows);

        // Verify error_summary is sanitized (no SQL or sensitive info)
        $this->assertStringNotContainsString('secret_token', $batch->error_summary);
        $this->assertStringContainsString('Simulated fatal database connection abort', $batch->error_summary);

        // Verify NO rows are marked as IMPORTED in database
        $this->assertSame(0, ImportBatchRow::where('import_batch_id', $batch->id)->where('action', ImportBatchRow::ACTION_IMPORTED)->count());

        // Verify source file is PRESERVED for audit
        $this->assertTrue($batch->hasStoredFile());

        app(\App\Services\ImportLog\ImportFileStorageService::class)->deleteStoredFile($batch);
    }

    /**
     * Test duplicate concurrent confirmation is rejected and does not create a second batch.
     */
    public function test_double_submission_is_prevented(): void
    {
        $rows = [
            [
                'row_no'         => 1,
                'employee_id'    => $this->testEmployee->id_emp,
                'company'        => 'PT Krakatau Posco',
                'course_name'    => 'Single Execution Course ' . Str::random(4),
                'start_date'     => '2026-09-01',
                'finish_date'    => '2026-09-01',
                'learning_hours' => 1.0,
                'type'           => 'Public',
                'venue'          => 'Hall A',
            ],
        ];

        $file = $this->createPtkpSpreadsheet($rows);

        $previewResp = $this->actingAs($this->adminUser)
            ->post(route('manage.record.offline.import-ptkp.preview'), ['file' => $file]);
        $token = $previewResp->json('token');

        // First confirm succeeds
        $firstResp = $this->actingAs($this->adminUser)
            ->post(route('manage.record.offline.import-ptkp.confirm'), ['token' => $token]);
        $firstResp->assertStatus(200);
        $firstBatchUuid = $firstResp->json('result.batch_uuid');

        // Second confirm with same token fails
        $secondResp = $this->actingAs($this->adminUser)
            ->post(route('manage.record.offline.import-ptkp.confirm'), ['token' => $token]);
        $secondResp->assertStatus(422);

        // Assert only 1 batch was created for this execution
        $this->assertSame(1, ImportBatch::where('batch_uuid', $firstBatchUuid)->count());

        $batch = ImportBatch::where('batch_uuid', $firstBatchUuid)->first();
        app(\App\Services\ImportLog\ImportFileStorageService::class)->deleteStoredFile($batch);
    }

    /**
     * Test invalid or expired token does not create batch or store file.
     */
    public function test_invalid_token_does_not_create_batch(): void
    {
        $initialBatchCount = ImportBatch::count();

        $resp = $this->actingAs($this->adminUser)
            ->post(route('manage.record.offline.import-ptkp.confirm'), ['token' => str_repeat('f', 32)]);

        $resp->assertStatus(422);
        $this->assertSame($initialBatchCount, ImportBatch::count());
    }

    protected function createPtkpSpreadsheet(array $rows): UploadedFile
    {
        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle(PtkpExcelSchema::DATA_SHEET_TITLE);

        $columns = PtkpExcelSchema::columns();
        foreach ($columns as $idx => $col) {
            $letter = $col['col_letter'];
            $sheet->setCellValue("{$letter}1", $col['header']);
        }

        $rowIdx = 2;
        foreach ($rows as $data) {
            foreach ($columns as $idx => $col) {
                $letter = $col['col_letter'];
                $key = $col['key'];

                if ($key === 'employee_id') {
                    $sheet->setCellValueExplicit("{$letter}{$rowIdx}", (string) ($data['employee_id'] ?? ''), \PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_STRING);
                } elseif (array_key_exists($key, $data)) {
                    $sheet->setCellValue("{$letter}{$rowIdx}", $data[$key]);
                } elseif ($key === 'end_date' && isset($data['finish_date'])) {
                    $sheet->setCellValue("{$letter}{$rowIdx}", $data['finish_date']);
                } else {
                    $sheet->setCellValue("{$letter}{$rowIdx}", $col['example'] ?? '');
                }
            }
            $rowIdx++;
        }

        $tempPath = tempnam(sys_get_temp_dir(), 'ptkp_log_') . '.xlsx';
        $writer = new Xlsx($spreadsheet);
        $writer->save($tempPath);

        return new UploadedFile($tempPath, 'ptkp_log_test.xlsx', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet', null, true);
    }

    protected function buildNonPtkpRow(array $overrides = []): array
    {
        $m = array_merge([
            'employee_id'            => '100999',
            'name'                   => 'John Doe Non PTKP',
            'grade'                  => '4A',
            'job_position'           => 'Plant Engineer',
            'team'                   => 'Mill Team',
            'department'             => 'Maintenance Dept',
            'company'                => 'PT Krakatau Posco',
            'course_name'            => 'Non PTKP Safety Standard',
            'sub_course_name'        => 'Module 1 - Risk Assessment',
            'type'                   => 'In-House',
            'category'               => 'TEST_NON_PTKP_CAT',
            'level'                  => 'Intermediate',
            'batch'                  => 'Batch 01',
            'main_classification'    => 'Basic & Common Duties',
            'sub_classification'     => 'Safety Basics',
            'delivery_method'        => 'Offline Class',
            'sub_delivery_method'    => 'Lecture',
            'provider_name'          => 'Internal HR',
            'venue'                  => 'Hall B',
            'instructor'             => 'Instructor Test',
            'duration_seconds'       => 14400,
            'learning_hours'         => 4.0,
            'progress'               => 100.0,
            'progress_status'        => 'Completed',
            'start_date'             => '2026-06-01',
            'end_date'               => '2026-06-02',
            'start_time'             => '08:00',
            'end_time'               => '12:00',
            'month'                  => 'June',
            'requirement'            => 'Mandatory',
            'attendance'             => 'Yes',
            'attendance_reason'      => '',
            'bond_status'            => 'No',
            'bond_duration'          => null,
            'bond_end_date'          => null,
            'bond_amount'            => null,
            'l1_program'             => 'Good',
            'assessment_score'       => 85.0,
            'l2_verbal'              => 'Passed',
            'l3_numerical'           => null,
            'l3_verbal'              => null,
            'pic'                    => 'Admin PIC',
            'certificate'            => 'Available',
            'total_engagement_time'  => 4.0,
        ], $overrides);

        return array_values($m);
    }

    protected function createNonPtkpSpreadsheet(array $rows): UploadedFile
    {
        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle(OfflineTrainingExcelSchema::DATA_SHEET_TITLE);

        $headers = OfflineTrainingExcelSchema::headers();
        foreach ($headers as $idx => $hdr) {
            $colLetter = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($idx + 1);
            $sheet->setCellValue($colLetter . '1', $hdr);
        }

        foreach ($rows as $rIdx => $vals) {
            $rowNum = $rIdx + 2;
            foreach ($vals as $cIdx => $v) {
                $colLetter = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($cIdx + 1);
                $sheet->setCellValue($colLetter . $rowNum, $v);
            }
        }

        $tmpPath = tempnam(sys_get_temp_dir(), 'nptkp_test_') . '.xlsx';
        $writer = new Xlsx($spreadsheet);
        $writer->save($tmpPath);

        return new UploadedFile($tmpPath, 'test_non_ptkp.xlsx', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet', null, true);
    }
}
