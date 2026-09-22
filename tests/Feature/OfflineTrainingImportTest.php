<?php

namespace Tests\Feature;

use App\Models\OfflineTraining;
use App\Models\OfflineTrainingUser;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Tests\TestCase;

class OfflineTrainingImportTest extends TestCase
{
    protected User $admin;
    protected ?User $regularUser;
    protected array $createdTempFiles = [];

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::where('role', '2')->first() ?? User::first();
        $this->regularUser = User::where('role', '1')->first();
    }

    protected function tearDown(): void
    {
        // Clean up any test records created in database
        DB::table('offline_training_users')->where('company', 'TEST_CORP')->delete();
        DB::table('offline_trainings')->where('category', 'TEST_CAT')->delete();

        // Clean up any temporary files generated during tests
        foreach ($this->createdTempFiles as $path) {
            if (File::exists($path)) {
                File::delete($path);
            }
        }

        // Clean up storage temp dir files older than test
        $tempDir = storage_path('app/temp');
        if (File::isDirectory($tempDir)) {
            foreach (File::files($tempDir) as $file) {
                if (str_starts_with($file->getFilename(), 'offline_import_test_')) {
                    File::delete($file->getPathname());
                }
            }
        }

        parent::tearDown();
    }

    /**
     * Helper to create a test spreadsheet file.
     */
    protected function createSpreadsheetFile(array $dataRows, bool $isGrouped = true): UploadedFile
    {
        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();

        if ($isGrouped) {
            // Row 1: Merged Parent Headers
            $sheet->setCellValue('A1', 'No');
            $sheet->setCellValue('B1', 'JOB INFORMATION');
            $sheet->mergeCells('B1:H1');
            $sheet->setCellValue('I1', 'TRAINING INFORMATION');
            $sheet->mergeCells('I1:AC1');
            $sheet->setCellValue('AD1', 'ATTENDANCE');
            $sheet->mergeCells('AD1:AI1');
            $sheet->setCellValue('AJ1', 'OBLIGATION');
            $sheet->mergeCells('AJ1:AL1');
            $sheet->setCellValue('AM1', 'L1');
            $sheet->setCellValue('AN1', 'L2 (POST TEST)');
            $sheet->mergeCells('AN1:AO1');
            $sheet->setCellValue('AP1', 'L3 (PROJECT)');
            $sheet->mergeCells('AP1:AR1');

            // Row 2: Child Headers
            $headers = [
                'No', // A
                'NIP', 'Nama', 'Grade', 'Job Position', 'Team', 'Department', 'Company', // B-H
                'Course Name', 'Sub Course Name', 'Type', 'Category', 'Level', 'Batch', // I-N
                'Main Classification', 'Sub Classification', 'Counted to Corporate KPI', 'Sub Corporate KPI', // O-R
                'Delivery Method', 'Sub Delivery Method', 'Provider', 'Sub Provider', 'Provider Name', 'Venue', 'Instructor', 'Duration', 'Learning Hours', 'Start Date', 'Finish Date', // S-AB
                'Progress', 'Assessment Score', 'Certificate', 'Attendance', 'Attendance Reason', 'Month', // AC-AH
                'Bond Status', 'Bond End Date', 'Bond Amount', // AI-AK
                'Program', // AL (L1)
                'Numerical', 'Verbal', // AM-AN (L2)
                'Numerical', 'Verbal', 'PIC' // AO-AQ (L3)
            ];

            foreach ($headers as $idx => $header) {
                $colLetter = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($idx + 1);
                $sheet->setCellValue($colLetter . '2', $header);
            }

            $startRow = 3;
        } else {
            // Single row headers
            $headers = [
                'nip', 'nama', 'grade', 'job_position', 'team', 'department', 'company',
                'course_name', 'sub_course_name', 'type', 'category', 'level', 'batch',
                'main_classification', 'sub_classification', 'counted_to_corporate_kpi', 'sub_corporate_kpi',
                'delivery_method', 'sub_delivery_method', 'provider', 'sub_provider', 'provider_name',
                'venue', 'instructor', 'duration', 'learning_hours', 'start_date', 'finish_date',
                'progress', 'assessment_score', 'certificate', 'attendance', 'attendance_reason',
                'bond_status', 'bond_end_date', 'bond_amount', 'program',
                'l2_numerical', 'l2_verbal', 'l3_numerical', 'l3_verbal', 'pic'
            ];

            foreach ($headers as $idx => $header) {
                $colLetter = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($idx + 1);
                $sheet->setCellValue($colLetter . '1', $header);
            }

            $startRow = 2;
        }

        // Write data rows
        foreach ($dataRows as $rIdx => $rowValues) {
            $rowNum = $startRow + $rIdx;
            foreach ($rowValues as $cIdx => $val) {
                $colLetter = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($cIdx + 1);
                $sheet->setCellValue($colLetter . $rowNum, $val);
            }
        }

        $tempPath = tempnam(sys_get_temp_dir(), 'owl_test_') . '.xlsx';
        $writer = new Xlsx($spreadsheet);
        $writer->save($tempPath);

        $this->createdTempFiles[] = $tempPath;

        return new UploadedFile($tempPath, 'test_offline_import.xlsx', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet', null, true);
    }

    /**
     * 1. Test import page requires auth and permission.
     */
    public function test_import_page_requires_auth_and_permission(): void
    {
        // Guest redirected
        $guestResponse = $this->get('/manage/record/offline/import');
        $this->assertTrue($guestResponse->isRedirect() || $guestResponse->getStatusCode() === 401);

        // Regular user without record_management is forbidden
        if ($this->regularUser) {
            $unauthResponse = $this->actingAs($this->regularUser)->get('/manage/record/offline/import');
            $unauthResponse->assertStatus(403);
        }

        // Admin redirect to offline page with modal trigger
        $response = $this->actingAs($this->admin)->get('/manage/record/offline/import');
        $response->assertStatus(302);
        $response->assertRedirect(route('manage.record.offline', ['open_import' => 1]));

        // Legacy fallback view support
        $legacyResponse = $this->actingAs($this->admin)->get('/manage/record/offline/import?legacy=1');
        $legacyResponse->assertStatus(200);
        $legacyResponse->assertViewIs('main.record.offline-import');
        $legacyResponse->assertSee('Non KP OWL — Excel Import');
        $legacyResponse->assertSee('upload-dropzone', false);
        $legacyResponse->assertSee('preview-card', false);
    }

    /**
     * 2. Test preview endpoint rejects empty and invalid files.
     */
    public function test_preview_endpoint_rejects_empty_and_invalid_files(): void
    {
        // Empty upload
        $response = $this->actingAs($this->admin)->postJson('/manage/record/offline/import/preview', []);
        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['file']);

        // Invalid file format (text file)
        $fakeTxt = UploadedFile::fake()->create('document.txt', 10, 'text/plain');
        $response = $this->actingAs($this->admin)->postJson('/manage/record/offline/import/preview', [
            'file' => $fakeTxt,
        ]);
        $response->assertStatus(422);
        $response->assertJson([
            'success' => false,
        ]);
    }

    /**
     * 3. Test two-row grouped header parsing, L2/L3 disambiguation, and preview metrics.
     */
    public function test_two_row_grouped_header_parsing_and_preview(): void
    {
        $adminEmp = $this->admin->id_emp ?? 'EMP901';
        $row = [
            1, // No
            $adminEmp, 'Budi Santoso', 'G3', 'Engineer', 'Plant Team', 'Maintenance', 'TEST_CORP', // B-H
            'Safety Leadership Training', 'Sub Safety', 'In-House', 'TEST_CAT', 'Basic', 'Batch 1', // I-N
            'Technical', 'Safety', 'Yes', 'Sub KPI', // O-R
            'Classroom', 'Face to Face', 'Vendor A', 'Sub Vendor', 'Provider Corp', 'Hall A', 'John Doe', // S-Y
            '08:00:00', 8.0, '2026-03-10', '2026-03-11', // Z-AB
            100, 88.5, 'Yes', 'Present', '', 'March', // AC-AH
            'Yes', '2027-03-10', '5000000', // AI-AK (Bond)
            'L1 Excellent', // AL
            85.0, 'Good', // AM-AN (L2)
            90.0, 'Very Good', 'Jane PIC' // AO-AQ (L3)
        ];

        $file = $this->createSpreadsheetFile([$row], true);

        $response = $this->actingAs($this->admin)->postJson('/manage/record/offline/import/preview', [
            'file' => $file,
        ]);

        $response->assertStatus(200);
        $response->assertJson([
            'success' => true,
        ]);

        $data = $response->json('data');
        $this->assertNotEmpty($data['token']);
        $this->assertEquals(1, $data['stats']['total_rows']);
        $this->assertEquals(1, $data['stats']['valid']);
        $this->assertEquals(0, $data['stats']['invalid']);
        $this->assertEquals(1, $data['stats']['new_trainings']);

        $previewRow = $data['preview_rows'][0];
        $this->assertEquals('valid', $previewRow['status']);
        $this->assertEquals($adminEmp, $previewRow['data']['employee_id']);
        $this->assertEquals('Safety Leadership Training', $previewRow['data']['course_name']);
        $this->assertEquals('Batch 1', $previewRow['data']['batch']);
        $this->assertEquals(28800, $previewRow['data']['duration_seconds']);
        $this->assertEquals(8.0, $previewRow['data']['learning_hours']);
        $this->assertEquals('2026-03-10', $previewRow['data']['start_date']);
        $this->assertEquals('2026-03-11', $previewRow['data']['finish_date']);

        // Verify L2 vs L3 disambiguation
        $this->assertEquals(85.0, $previewRow['data']['l2_numerical']);
        $this->assertEquals('Good', $previewRow['data']['l2_verbal']);
        $this->assertEquals(90.0, $previewRow['data']['l3_numerical']);
        $this->assertEquals('Very Good', $previewRow['data']['l3_verbal']);
        $this->assertEquals('Jane PIC', $previewRow['data']['pic']);
    }

    /**
     * 4. Test single-row header fallback parsing.
     */
    public function test_single_row_header_fallback_parsing(): void
    {
        $row = [
            'EMP902', 'Siti Rahma', 'G2', 'Technician', 'Team B', 'Production', 'TEST_CORP',
            'Welding Certification', 'Sub Welding', 'Certification', 'TEST_CAT', 'Intermediate', 'Batch 2',
            'Technical', 'Welding', 'Yes', 'Sub KPI',
            'Classroom', 'Face to Face', 'Vendor B', 'Sub Vendor', 'Provider Corp',
            'Workshop 1', 'Master Welder', '04:00:00', 4.0, '2026-04-01', '2026-04-01',
            100, 95.0, 'Yes', 'Present', '',
            'No', '', '0', 'Standard L1',
            80.0, 'Passed', 85.0, 'Completed', 'PIC Officer'
        ];

        $file = $this->createSpreadsheetFile([$row], false);

        $response = $this->actingAs($this->admin)->postJson('/manage/record/offline/import/preview', [
            'file' => $file,
        ]);

        $response->assertStatus(200);
        $this->assertEquals(1, $response->json('data.stats.total_rows'));
        $this->assertEquals(1, $response->json('data.stats.valid'));
        $this->assertEquals('Welding Certification', $response->json('data.preview_rows.0.data.course_name'));
    }

    /**
     * 5. Test employee matching with LMS user and historical preservation.
     */
    public function test_employee_matching_and_historical_preservation(): void
    {
        // Admin user has an id_emp in LMS
        $adminEmpId = $this->admin->id_emp ?? 'EMP_ADMIN';
        if (!$this->admin->id_emp) {
            $this->admin->update(['id_emp' => 'EMP_ADMIN']);
        }

        $row1 = [
            1, $this->admin->id_emp, 'Admin Preserved Name', 'G5', 'Manager', 'Team Admin', 'IT Dept', 'TEST_CORP',
            'Leadership Workshop', '', 'In-House', 'TEST_CAT', 'Advanced', 'Batch 10',
            'Management', 'Leadership', 'Yes', '',
            'Classroom', '', 'Internal', '', '', 'Room 101', '', '02:00:00', 2.0, '2026-05-01', '2026-05-01',
            100, 100, 'Yes', 'Present', '', 'May',
            'No', '', '', '', '', '', '', '', ''
        ];

        // Non-existent LMS user
        $row2 = [
            2, 'UNKNOWN_NIP_9999', 'Unknown Participant', 'G1', 'Operator', 'Team X', 'Dept Y', 'TEST_CORP',
            'Leadership Workshop', '', 'In-House', 'TEST_CAT', 'Advanced', 'Batch 10',
            'Management', 'Leadership', 'Yes', '',
            'Classroom', '', 'Internal', '', '', 'Room 101', '', '02:00:00', 2.0, '2026-05-01', '2026-05-01',
            100, 100, 'Yes', 'Present', '', 'May',
            'No', '', '', '', '', '', '', '', ''
        ];

        $file = $this->createSpreadsheetFile([$row1, $row2], true);

        $response = $this->actingAs($this->admin)->postJson('/manage/record/offline/import/preview', [
            'file' => $file,
        ]);

        $response->assertStatus(200);
        $previewRows = $response->json('data.preview_rows');

        // Row 1: matched with admin user
        $this->assertTrue($previewRows[0]['user_matched']);
        $this->assertEquals($this->admin->id, $previewRows[0]['data']['user_id']);
        $this->assertEquals('Admin Preserved Name', $previewRows[0]['data']['name']);

        // Row 2: not matched, user_id is null, warning status, historical data preserved
        $this->assertFalse($previewRows[1]['user_matched']);
        $this->assertNull($previewRows[1]['data']['user_id']);
        $this->assertEquals('Unknown Participant', $previewRows[1]['data']['name']);
        $this->assertEquals('warning', $previewRows[1]['status']);
    }

    /**
     * 6. Test training event fingerprint grouping: 3 participants create 1 offline_training record.
     */
    public function test_training_event_grouping_creates_one_training_and_multiple_participants(): void
    {
        $trainingCourse = 'Grouped Training Event';
        $batch = 'Batch G1';

        $rows = [
            [
                1, 'EMP_G1', 'User One', 'G2', 'Role 1', 'T1', 'D1', 'TEST_CORP',
                $trainingCourse, '', 'Public', 'TEST_CAT', 'Basic', $batch,
                'General', 'General', 'No', '',
                'Classroom', '', 'Vendor G', '', '', 'Hotel A', '', '04:00:00', 4.0, '2026-06-01', '2026-06-01',
                100, 80, 'Yes', 'Present', '', 'June',
                'No', '', '', '', '', '', '', '', ''
            ],
            [
                2, 'EMP_G2', 'User Two', 'G2', 'Role 2', 'T1', 'D1', 'TEST_CORP',
                $trainingCourse, '', 'Public', 'TEST_CAT', 'Basic', $batch,
                'General', 'General', 'No', '',
                'Classroom', '', 'Vendor G', '', '', 'Hotel A', '', '04:00:00', 4.0, '2026-06-01', '2026-06-01',
                100, 85, 'Yes', 'Present', '', 'June',
                'No', '', '', '', '', '', '', '', ''
            ],
            [
                3, 'EMP_G3', 'User Three', 'G3', 'Role 3', 'T2', 'D1', 'TEST_CORP',
                $trainingCourse, '', 'Public', 'TEST_CAT', 'Basic', $batch,
                'General', 'General', 'No', '',
                'Classroom', '', 'Vendor G', '', '', 'Hotel A', '', '04:00:00', 4.0, '2026-06-01', '2026-06-01',
                100, 90, 'Yes', 'Present', '', 'June',
                'No', '', '', '', '', '', '', '', ''
            ],
        ];

        $file = $this->createSpreadsheetFile($rows, true);

        // Preview
        $prevResponse = $this->actingAs($this->admin)->postJson('/manage/record/offline/import/preview', [
            'file' => $file,
        ]);
        $prevResponse->assertStatus(200);
        $token = $prevResponse->json('data.token');
        $this->assertEquals(1, $prevResponse->json('data.stats.new_trainings'));

        // Confirm
        $confResponse = $this->actingAs($this->admin)->postJson('/manage/record/offline/import/confirm', [
            'token' => $token,
        ]);
        $confResponse->assertStatus(200);
        $result = $confResponse->json('result');

        $this->assertEquals(3, $result['rows_processed']);
        $this->assertEquals(3, $result['imported']);
        $this->assertEquals(0, $result['skipped_duplicates']);
        $this->assertEquals(1, $result['trainings_created']);

        // Check database
        $trainingsInDb = OfflineTraining::where('course_name', $trainingCourse)->where('batch', $batch)->get();
        $this->assertCount(1, $trainingsInDb);

        $trainingId = $trainingsInDb->first()->id;
        $participantsInDb = OfflineTrainingUser::where('offline_training_id', $trainingId)->get();
        $this->assertCount(3, $participantsInDb);
    }

    /**
     * 7. Test in-file duplicate participant skipping.
     */
    public function test_in_file_duplicate_participant_is_skipped(): void
    {
        $trainingCourse = 'Duplicate In File Test';
        $batch = 'Batch D1';

        $rows = [
            [
                1, 'EMP_DUP1', 'Duplicate User', 'G2', 'Role 1', 'T1', 'D1', 'TEST_CORP',
                $trainingCourse, '', 'Public', 'TEST_CAT', 'Basic', $batch,
                'General', 'General', 'No', '',
                'Classroom', '', 'Vendor D', '', '', 'Hotel D', '', '04:00:00', 4.0, '2026-07-01', '2026-07-01',
                100, 80, 'Yes', 'Present', '', 'July',
                'No', '', '', '', '', '', '', '', ''
            ],
            // Exact same employee for same event
            [
                2, 'EMP_DUP1', 'Duplicate User', 'G2', 'Role 1', 'T1', 'D1', 'TEST_CORP',
                $trainingCourse, '', 'Public', 'TEST_CAT', 'Basic', $batch,
                'General', 'General', 'No', '',
                'Classroom', '', 'Vendor D', '', '', 'Hotel D', '', '04:00:00', 4.0, '2026-07-01', '2026-07-01',
                100, 80, 'Yes', 'Present', '', 'July',
                'No', '', '', '', '', '', '', '', ''
            ],
        ];

        $file = $this->createSpreadsheetFile($rows, true);

        // Preview
        $prevResponse = $this->actingAs($this->admin)->postJson('/manage/record/offline/import/preview', [
            'file' => $file,
        ]);
        $prevResponse->assertStatus(200);
        $token = $prevResponse->json('data.token');
        $this->assertEquals(2, $prevResponse->json('data.stats.total_rows'));
        $this->assertEquals(1, $prevResponse->json('data.stats.valid'));
        $this->assertEquals(1, $prevResponse->json('data.stats.duplicate'));

        // Confirm
        $confResponse = $this->actingAs($this->admin)->postJson('/manage/record/offline/import/confirm', [
            'token' => $token,
        ]);
        $confResponse->assertStatus(200);
        $result = $confResponse->json('result');

        $this->assertEquals(1, $result['imported']);
        $this->assertEquals(1, $result['skipped_duplicates']);
    }

    /**
     * 8. Test in-database duplicate participant skipping and reusing existing training event.
     */
    public function test_in_database_duplicate_participant_is_skipped_and_training_reused(): void
    {
        // 1. Create an existing training event in DB
        $existingTraining = OfflineTraining::create([
            'course_name'     => 'Existing Database Training',
            'batch'           => 'Batch EX',
            'type'            => 'In-House',
            'category'        => 'TEST_CAT',
            'venue'           => 'Main Auditorium',
            'start_date'      => '2026-08-01',
            'finish_date'     => '2026-08-02',
            'duration_seconds' => 14400,
            'learning_hours'  => 4.0,
        ]);

        // Pre-enroll EMP_EX1
        OfflineTrainingUser::create([
            'offline_training_id' => $existingTraining->id,
            'employee_id'         => 'EMP_EX1',
            'name'                => 'Already Enrolled User',
            'company'             => 'TEST_CORP',
            'learning_hours'      => 4.0,
            'progress'            => 100,
        ]);

        // 2. Upload spreadsheet with EMP_EX1 (existing duplicate) and EMP_EX2 (new participant)
        $rows = [
            [
                1, 'EMP_EX1', 'Already Enrolled User', 'G3', 'Role', 'Team', 'Dept', 'TEST_CORP',
                'Existing Database Training', '', 'In-House', 'TEST_CAT', '', 'Batch EX',
                '', '', '', '',
                '', '', '', '', '', 'Main Auditorium', '', '04:00:00', 4.0, '2026-08-01', '2026-08-02',
                100, 90, '', '', '', '',
                '', '', '', '', '', '', '', '', ''
            ],
            [
                2, 'EMP_EX2', 'Brand New Participant', 'G3', 'Role', 'Team', 'Dept', 'TEST_CORP',
                'Existing Database Training', '', 'In-House', 'TEST_CAT', '', 'Batch EX',
                '', '', '', '',
                '', '', '', '', '', 'Main Auditorium', '', '04:00:00', 4.0, '2026-08-01', '2026-08-02',
                100, 92, '', '', '', '',
                '', '', '', '', '', '', '', '', ''
            ],
        ];

        $file = $this->createSpreadsheetFile($rows, true);

        // Preview
        $prevResponse = $this->actingAs($this->admin)->postJson('/manage/record/offline/import/preview', [
            'file' => $file,
        ]);
        $prevResponse->assertStatus(200);
        $token = $prevResponse->json('data.token');

        $this->assertEquals(2, $prevResponse->json('data.stats.total_rows'));
        $this->assertEquals(1, $prevResponse->json('data.stats.duplicate'));
        $this->assertEquals(1, $prevResponse->json('data.stats.valid'));
        $this->assertEquals(1, $prevResponse->json('data.stats.reused_trainings'));
        $this->assertEquals(0, $prevResponse->json('data.stats.new_trainings'));

        // Confirm
        $confResponse = $this->actingAs($this->admin)->postJson('/manage/record/offline/import/confirm', [
            'token' => $token,
        ]);
        $confResponse->assertStatus(200);
        $result = $confResponse->json('result');

        $this->assertEquals(1, $result['imported']);
        $this->assertEquals(1, $result['skipped_duplicates']);
        $this->assertEquals(0, $result['trainings_created']);
        $this->assertEquals(1, $result['trainings_reused']);

        // Check DB: exactly 2 participants attached to existing training
        $participants = OfflineTrainingUser::where('offline_training_id', $existingTraining->id)->get();
        $this->assertCount(2, $participants);
    }

    /**
     * 9. Test cancel cleans up temporary file and cache.
     */
    public function test_cancel_cleans_up_temporary_file_and_cache(): void
    {
        $row = [
            1, 'EMP_CANCEL', 'Cancel User', 'G1', 'Role', 'T', 'D', 'TEST_CORP',
            'Cancel Training', '', 'In-House', 'TEST_CAT', '', '',
            '', '', '', '', '', '', '', '', '', '', '', '01:00:00', 1.0, '2026-09-01', '2026-09-01',
            100, 80, '', '', '', '', '', '', '', '', '', '', '', '', ''
        ];

        $file = $this->createSpreadsheetFile([$row], true);

        $prevResponse = $this->actingAs($this->admin)->postJson('/manage/record/offline/import/preview', [
            'file' => $file,
        ]);
        $token = $prevResponse->json('data.token');

        $meta = Cache::get('offline_import_' . $token);
        $this->assertNotNull($meta);
        $this->assertFileExists($meta['temp_path']);

        // Call cancel
        $cancelResponse = $this->actingAs($this->admin)->postJson('/manage/record/offline/import/cancel', [
            'token' => $token,
        ]);
        $cancelResponse->assertStatus(200);

        // Assert file deleted and cache cleared
        $this->assertNull(Cache::get('offline_import_' . $token));
        $this->assertFileDoesNotExist($meta['temp_path']);
    }

    /**
     * 10. Test confirm cleans up temporary file upon completion.
     */
    public function test_confirm_cleans_up_temporary_file_upon_completion(): void
    {
        $row = [
            1, 'EMP_CLEANUP', 'Cleanup User', 'G1', 'Role', 'T', 'D', 'TEST_CORP',
            'Cleanup Training', '', 'In-House', 'TEST_CAT', '', '',
            '', '', '', '', '', '', '', '', '', '', '', '01:00:00', 1.0, '2026-09-02', '2026-09-02',
            100, 80, '', '', '', '', '', '', '', '', '', '', '', '', ''
        ];

        $file = $this->createSpreadsheetFile([$row], true);

        $prevResponse = $this->actingAs($this->admin)->postJson('/manage/record/offline/import/preview', [
            'file' => $file,
        ]);
        $token = $prevResponse->json('data.token');

        $meta = Cache::get('offline_import_' . $token);
        $tempPath = $meta['temp_path'];
        $this->assertFileExists($tempPath);

        // Confirm
        $this->actingAs($this->admin)->postJson('/manage/record/offline/import/confirm', [
            'token' => $token,
        ]);

        // File must be deleted after successful confirm
        $this->assertFileDoesNotExist($tempPath);
    }

    /**
     * 11. Test invalid token error handling.
     */
    public function test_invalid_token_error_handling(): void
    {
        $response = $this->actingAs($this->admin)->postJson('/manage/record/offline/import/confirm', [
            'token' => 'invalid_token_which_does_not_exist_32chars!',
        ]);

        $response->assertStatus(422);
        $response->assertJson([
            'success' => false,
        ]);
    }

    /**
     * 12. Test regression: verify KP OWL and Non KP OWL pages remain intact.
     */
    public function test_regression_record_pages_remain_intact(): void
    {
        // Online landing
        $onlineResponse = $this->actingAs($this->admin)->get('/manage/record/online');
        $onlineResponse->assertStatus(200);

        // Offline landing has Import Excel button linking to import route
        $offlineResponse = $this->actingAs($this->admin)->get('/manage/record/offline');
        $offlineResponse->assertStatus(200);
        $offlineResponse->assertSee(route('manage.record.offline.import'));

        // Offline summary AJAX
        $summaryResponse = $this->actingAs($this->admin)->getJson('/manage/record/offline/ajax/summary');
        $summaryResponse->assertStatus(200);
        $summaryResponse->assertJson(['success' => true]);
    }

    /**
     * 13. Test unauthorized user is forbidden on preview, confirm, and cancel endpoints.
     */
    public function test_unauthorized_user_forbidden_on_preview_confirm_cancel(): void
    {
        if (!$this->regularUser) {
            $this->markTestSkipped('No regular user found for testing.');
        }

        $fakeFile = UploadedFile::fake()->create('test.xlsx', 10);

        // Preview
        $prev = $this->actingAs($this->regularUser)->postJson('/manage/record/offline/import/preview', [
            'file' => $fakeFile,
        ]);
        $prev->assertStatus(403);

        // Confirm
        $conf = $this->actingAs($this->regularUser)->postJson('/manage/record/offline/import/confirm', [
            'token' => '12345678901234567890123456789012',
        ]);
        $conf->assertStatus(403);

        // Cancel
        $canc = $this->actingAs($this->regularUser)->postJson('/manage/record/offline/import/cancel', [
            'token' => '12345678901234567890123456789012',
        ]);
        $canc->assertStatus(403);
    }

    /**
     * 14. Test data normalizer methods directly via Service.
     */
    public function test_service_normalizers_handle_various_formats(): void
    {
        $service = app(\App\Services\Record\OfflineTrainingImportService::class);

        // Durations: "08:30:00" -> 30600 seconds, "02:00:00" -> 7200, 3600 -> 3600
        $this->assertEquals(30600, $service->normalizeDuration('08:30:00'));
        $this->assertEquals(7200, $service->normalizeDuration('02:00:00'));
        $this->assertEquals(3600, $service->normalizeDuration(3600));
        $this->assertNull($service->normalizeDuration(null));

        // Money: "Rp 5.000.000,00" -> 5000000.00, "$1,500.50" -> 1500.50
        $this->assertEquals(5000000.00, $service->normalizeMoney('Rp 5.000.000,00'));
        $this->assertEquals(1500.50, $service->normalizeMoney('$1,500.50'));
        $this->assertNull($service->normalizeMoney(''));

        // Booleans: "Y", "Yes", "Ya", 1 -> true; "N", "No", 0 -> false
        $this->assertTrue($service->normalizeBoolean('Y'));
        $this->assertTrue($service->normalizeBoolean('Yes'));
        $this->assertTrue($service->normalizeBoolean('Ya'));
        $this->assertTrue($service->normalizeBoolean(1));
        $this->assertFalse($service->normalizeBoolean('N'));
        $this->assertFalse($service->normalizeBoolean('No'));
        $this->assertFalse($service->normalizeBoolean(0));
        $this->assertNull($service->normalizeBoolean(''));

        // Learning Hours
        $this->assertEquals(8.5, $service->normalizeLearningHours('8.5'));
        $this->assertEquals(16.0, $service->normalizeLearningHours('16'));
        $this->assertNull($service->normalizeLearningHours(''));

        // Progress
        $this->assertEquals(100.0, $service->normalizeProgress('100%'));
        $this->assertEquals(75.5, $service->normalizeProgress('75.5'));
        $this->assertEquals(50.0, $service->normalizeProgress(0.5)); // fraction from excel
    }

    /**
     * 15. Test validation detects invalid date range (finish before start).
     */
    public function test_invalid_date_range_detected_as_error(): void
    {
        $adminEmp = $this->admin->id_emp ?? 'EMP901';
        $row = [
            1, $adminEmp, 'Budi Santoso', 'G3', 'Engineer', 'Plant Team', 'Maintenance', 'TEST_CORP',
            'Safety Training', '', 'In-House', 'TEST_CAT', 'Basic', 'Batch 1',
            'Technical', '', 'Yes', '',
            'Classroom', '', 'Vendor A', '', '', 'Hall A', '',
            '08:00:00', 8.0,
            '2026-05-15', '2026-05-10', // Invalid: finish before start!
            100, 88.5, 'Yes', 'Present', '', 'May',
            'No', '', '', '', '', '', '', '', ''
        ];

        $file = $this->createSpreadsheetFile([$row], true);

        $response = $this->actingAs($this->admin)->postJson('/manage/record/offline/import/preview', [
            'file' => $file,
        ]);

        $response->assertStatus(200);
        $data = $response->json('data');
        $this->assertEquals(1, $data['stats']['invalid']);
        $this->assertEquals(0, $data['stats']['valid']);
        $this->assertEquals('invalid', $data['preview_rows'][0]['status']);
        $this->assertStringContainsString('cannot be earlier than start date', $data['preview_rows'][0]['messages'][0]);
    }
}

