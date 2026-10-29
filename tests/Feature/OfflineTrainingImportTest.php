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
     * Helper to build a canonical 44-element data row matching OfflineTrainingExcelSchema.
     */
    protected function buildCanonicalTestRow(array $overrides = []): array
    {
        $defaults = [
            'no'                    => 1,
            'employee_id'           => '100234',
            'name'                  => 'Budi Santoso',
            'grade'                 => '4A',
            'job_position'          => 'Electrical Maintenance Engineer',
            'team'                  => 'Rolling Mill 1',
            'department'            => 'Maintenance Department',
            'company'               => 'TEST_CORP',
            'course_name'           => 'High Voltage Safety & Operation',
            'sub_course_name'       => 'Switchgear Module',
            'type'                  => 'In-House',
            'category'              => 'TEST_CAT',
            'level'                 => 'Intermediate',
            'batch'                 => 'Batch 1',
            'main_classification'   => 'Technical',
            'sub_classification'    => 'Plant Maintenance',
            'counted_corporate_kpi' => 'Yes',
            'sub_corporate_kpi'     => 'Safety Incident Reduction',
            'delivery_method'       => 'Classroom',
            'sub_delivery_method'   => 'Hands-on Lab',
            'provider'              => 'External',
            'sub_provider'          => 'Vendor',
            'duration_seconds'      => '08:00:00',
            'learning_hours'        => 8.0,
            'progress'              => 100,
            'start_date'            => '2026-08-10',
            'finish_date'           => '2026-08-10',
            'month'                 => 'August',
            'provider_name'         => 'PT Schneider Indonesia',
            'venue'                 => 'Auditorium Hall B',
            'instructor'            => 'Ir. Gunawan, M.T.',
            'assessment_score'      => 85.5,
            'certificate'           => 'Yes',
            'attendance'            => 'Yes',
            'attendance_reason'     => 'Present in all sessions',
            'bond_status'           => 'No',
            'bond_end_date'         => '',
            'bond_amount'           => '',
            'l1_program'            => 'Very Good',
            'l2_numerical'          => 88.0,
            'l2_verbal'             => 'Passed',
            'l3_numerical'          => 90.0,
            'l3_verbal'             => 'Implemented successfully',
            'pic'                   => 'HRDC Team',
        ];

        $m = array_merge($defaults, $overrides);

                return [
            $m['employee_id'] ?? '100234',                                   // 1. Employee ID
            $m['name'] ?? 'Budi Santoso',                                    // 2. Employee Name
            $m['grade'] ?? '4A',                                             // 3. Grade
            $m['job_position'] ?? 'Electrical Maintenance Engineer',         // 4. Job Position
            $m['team'] ?? 'Rolling Mill 1',                                  // 5. Team
            $m['department'] ?? 'Maintenance Department',                    // 6. Department
            $m['company'] ?? 'TEST_CORP',                                    // 7. Company
            $m['course_name'] ?? 'High Voltage Safety & Operation',          // 8. Course Title
            $m['sub_course_name'] ?? 'Switchgear Module',                    // 9. Sub Course Title
            $m['type'] ?? 'In-House',                                        // 10. Type
            $m['category'] ?? 'TEST_CAT',                                    // 11. Category
            $m['level'] ?? 'Intermediate',                                   // 12. Level
            $m['batch'] ?? 'Batch 1',                                        // 13. Batch
            $m['main_classification'] ?? 'Technical',                        // 14. Main Classification
            $m['sub_classification'] ?? 'Plant Maintenance',                 // 15. Sub Classification
            $m['delivery_method'] ?? 'Offline',                              // 16. Delivery Method
            $m['sub_delivery_method'] ?? 'On-site',                          // 17. Sub Delivery Method
            $m['provider_name'] ?? 'PT Schneider Indonesia',                 // 18. Provider Name
            $m['venue'] ?? 'Auditorium Hall B',                              // 19. Venue
            $m['instructor'] ?? 'Ir. Gunawan, M.T.',                         // 20. Instructor
            $m['duration_seconds'] ?? '08:00:00',                            // 21. Course Duration
            $m['learning_hours'] ?? 8.0,                                     // 22. Learning Hours
            $m['progress'] ?? 100,                                           // 23. % Progress
            $m['progress_status'] ?? 'Finished',                             // 24. Progress Status
            $m['start_date'] ?? '2026-08-10',                                // 25. Start Date
            $m['finish_date'] ?? ($m['end_date'] ?? '2026-08-10'),           // 26. End Date
            $m['start_time'] ?? '08:00:00',                                  // 27. Start Time
            $m['end_time'] ?? '17:00:00',                                    // 28. End Time
            $m['month'] ?? 'August',                                         // 29. Record Month
            $m['requirement'] ?? 'Mandatory',                                // 30. Requirement
            $m['attendance'] ?? 'Yes',                                       // 31. Attendance
            $m['attendance_reason'] ?? 'Present in all sessions',            // 32. Attendance Reason
            $m['bond_status'] ?? 'No',                                       // 33. Bond Status
            $m['bond_duration'] ?? '',                                       // 34. Bond Duration
            $m['bond_end_date'] ?? '',                                       // 35. Bond End Date
            $m['bond_amount'] ?? '',                                         // 36. Bond Amount
            $m['l1_program'] ?? 'Very Good',                                 // 37. Score (L1) - Course
            $m['l2_numerical'] ?? 88.0,                                      // 38. Score (L2)
            $m['l2_verbal'] ?? 'Passed',                                     // 39. L2 Status
            $m['l3_numerical'] ?? 90.0,                                      // 40. Score (L3)
            $m['l3_verbal'] ?? 'Implemented successfully',                   // 41. L3 Status
            $m['pic'] ?? 'HRDC Team',                                        // 42. Person in Charge (PIC)
            $m['certificate'] ?? 'Yes',                                      // 43. Certificate
            $m['total_engagement_time'] ?? 8.0,                              // 44. Total Engagement Time
        ];
    }

    /**
     * Helper to create a test spreadsheet file.
     */
    protected function createSpreadsheetFile(array $dataRows, bool $isGrouped = true): UploadedFile
    {
        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();

        if ($isGrouped) {
            // Row 1: Merged Parent Headers (Legacy 2-row format)
            $sheet->setCellValue('A1', 'No');
            $sheet->setCellValue('B1', 'JOB INFORMATION');
            $sheet->mergeCells('B1:H1');
            $sheet->setCellValue('I1', 'TRAINING INFORMATION');
            $sheet->mergeCells('I1:AG1');
            $sheet->setCellValue('AH1', 'ATTENDANCE');
            $sheet->mergeCells('AH1:AI1');
            $sheet->setCellValue('AJ1', 'OBLIGATION');
            $sheet->mergeCells('AJ1:AL1');
            $sheet->setCellValue('AM1', 'L1');
            $sheet->setCellValue('AN1', 'L2 (POST TEST)');
            $sheet->mergeCells('AN1:AO1');
            $sheet->setCellValue('AP1', 'L3 (PROJECT)');
            $sheet->mergeCells('AP1:AR1');

            // Row 2: Canonical child headers
            $headers = \App\Services\Record\OfflineTrainingExcelSchema::headers();
            foreach ($headers as $idx => $header) {
                $colLetter = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($idx + 1);
                $sheet->setCellValue($colLetter . '2', $header);
            }

            $startRow = 3;
        } else {
            // Single row headers on Row 1 (Target format)
            $headers = \App\Services\Record\OfflineTrainingExcelSchema::headers();
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
        $response->assertRedirect(route('manage.record.offline.import-non-ptkp'));

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
        $this->admin->update(['id_emp' => 'EMP901', 'name' => 'Budi Santoso']);
        $adminEmp = 'EMP901';
        $row = $this->buildCanonicalTestRow([
            'employee_id'           => $adminEmp,
            'name'                  => 'Budi Santoso',
            'grade'                 => 'G3',
            'job_position'          => 'Engineer',
            'team'                  => 'Plant Team',
            'department'            => 'Maintenance',
            'company'               => 'TEST_CORP',
            'course_name'           => 'Safety Leadership Training',
            'sub_course_name'       => 'Sub Safety',
            'type'                  => 'In-House',
            'category'              => 'TEST_CAT',
            'level'                 => 'Basic',
            'batch'                 => 'Batch 1',
            'main_classification'   => 'Technical',
            'sub_classification'    => 'Safety',
            'counted_corporate_kpi' => 'Yes',
            'sub_corporate_kpi'     => 'Sub KPI',
            'delivery_method'       => 'Classroom',
            'sub_delivery_method'   => 'Face to Face',
            'provider'              => 'External',
            'sub_provider'          => 'Vendor',
            'duration_seconds'      => '08:00:00',
            'learning_hours'        => 8.0,
            'progress'              => 100,
            'start_date'            => '2026-03-10',
            'finish_date'           => '2026-03-11',
            'month'                 => 'March',
            'provider_name'         => 'Provider Corp',
            'venue'                 => 'Hall A',
            'instructor'            => 'John Doe',
            'assessment_score'      => 88.5,
            'certificate'           => 'Yes',
            'attendance'            => 'Yes',
            'attendance_reason'     => 'Present',
            'bond_status'           => 'Yes',
            'bond_end_date'         => '2027-03-10',
            'bond_amount'           => '5000000',
            'l1_program'            => 'L1 Excellent',
            'l2_numerical'          => 85.0,
            'l2_verbal'             => 'Good',
            'l3_numerical'          => 90.0,
            'l3_verbal'             => 'Very Good',
            'pic'                   => 'Jane PIC',
        ]);

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
        $row = $this->buildCanonicalTestRow([
            'employee_id' => 'EMP902',
            'name'        => 'Siti Rahma',
            'course_name' => 'Welding Certification',
        ]);

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

        $row1 = $this->buildCanonicalTestRow([
            'no'          => 1,
            'employee_id' => $this->admin->id_emp,
            'name'        => 'Admin Preserved Name',
            'course_name' => 'Leadership Workshop',
            'batch'       => 'Batch 10',
        ]);

        // Non-existent LMS user
        $row2 = $this->buildCanonicalTestRow([
            'no'          => 2,
            'employee_id' => 'UNKNOWN_NIP_9999',
            'name'        => 'Unknown Participant',
            'course_name' => 'Leadership Workshop',
            'batch'       => 'Batch 10',
        ]);

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
            $this->buildCanonicalTestRow([
                'no'                  => 1,
                'employee_id'         => 'EMP_G1',
                'name'                => 'User One',
                'grade'               => 'G2',
                'job_position'        => 'Role 1',
                'team'                => 'T1',
                'department'          => 'D1',
                'company'             => 'TEST_CORP',
                'course_name'         => $trainingCourse,
                'type'                => 'Public',
                'category'            => 'TEST_CAT',
                'level'               => 'Basic',
                'batch'               => $batch,
                'main_classification' => 'General',
                'sub_classification'  => 'General',
                'kpi'                 => 'No',
                'delivery_method'     => 'Classroom',
                'provider'            => 'Vendor G',
                'venue'               => 'Hotel A',
                'duration'            => '04:00:00',
                'learning_hours'      => 4.0,
                'start_date'          => '2026-06-01',
                'finish_date'         => '2026-06-01',
                'progress'            => 100,
                'assessment_score'    => 80,
                'attendance'          => 'Yes',
                'reason'              => 'Present',
                'month'               => 'June',
                'bond_status'         => 'No',
            ]),
            $this->buildCanonicalTestRow([
                'no'                  => 2,
                'employee_id'         => 'EMP_G2',
                'name'                => 'User Two',
                'grade'               => 'G2',
                'job_position'        => 'Role 2',
                'team'                => 'T1',
                'department'          => 'D1',
                'company'             => 'TEST_CORP',
                'course_name'         => $trainingCourse,
                'type'                => 'Public',
                'category'            => 'TEST_CAT',
                'level'               => 'Basic',
                'batch'               => $batch,
                'main_classification' => 'General',
                'sub_classification'  => 'General',
                'kpi'                 => 'No',
                'delivery_method'     => 'Classroom',
                'provider'            => 'Vendor G',
                'venue'               => 'Hotel A',
                'duration'            => '04:00:00',
                'learning_hours'      => 4.0,
                'start_date'          => '2026-06-01',
                'finish_date'         => '2026-06-01',
                'progress'            => 100,
                'assessment_score'    => 85,
                'attendance'          => 'Yes',
                'reason'              => 'Present',
                'month'               => 'June',
                'bond_status'         => 'No',
            ]),
            $this->buildCanonicalTestRow([
                'no'                  => 3,
                'employee_id'         => 'EMP_G3',
                'name'                => 'User Three',
                'grade'               => 'G3',
                'job_position'        => 'Role 3',
                'team'                => 'T2',
                'department'          => 'D1',
                'company'             => 'TEST_CORP',
                'course_name'         => $trainingCourse,
                'type'                => 'Public',
                'category'            => 'TEST_CAT',
                'level'               => 'Basic',
                'batch'               => $batch,
                'main_classification' => 'General',
                'sub_classification'  => 'General',
                'kpi'                 => 'No',
                'delivery_method'     => 'Classroom',
                'provider'            => 'Vendor G',
                'venue'               => 'Hotel A',
                'duration'            => '04:00:00',
                'learning_hours'      => 4.0,
                'start_date'          => '2026-06-01',
                'finish_date'         => '2026-06-01',
                'progress'            => 100,
                'assessment_score'    => 90,
                'attendance'          => 'Yes',
                'reason'              => 'Present',
                'month'               => 'June',
                'bond_status'         => 'No',
            ]),
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
            $this->buildCanonicalTestRow([
                'no'          => 1,
                'employee_id' => 'EMP_DUP1',
                'name'        => 'Duplicate User',
                'course_name' => $trainingCourse,
                'batch'       => $batch,
            ]),
            $this->buildCanonicalTestRow([
                'no'          => 2,
                'employee_id' => 'EMP_DUP1',
                'name'        => 'Duplicate User',
                'course_name' => $trainingCourse,
                'batch'       => $batch,
            ]),
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
            $this->buildCanonicalTestRow([
                'no'               => 1,
                'employee_id'      => 'EMP_EX1',
                'name'             => 'Already Enrolled User',
                'course_name'      => 'Existing Database Training',
                'type'             => 'In-House',
                'category'         => 'TEST_CAT',
                'batch'            => 'Batch EX',
                'venue'            => 'Main Auditorium',
                'duration'         => '04:00:00',
                'learning_hours'   => 4.0,
                'start_date'       => '2026-08-01',
                'finish_date'      => '2026-08-02',
                'progress'         => 100,
                'assessment_score' => 90,
            ]),
            $this->buildCanonicalTestRow([
                'no'               => 2,
                'employee_id'      => 'EMP_EX2',
                'name'             => 'Brand New Participant',
                'course_name'      => 'Existing Database Training',
                'type'             => 'In-House',
                'category'         => 'TEST_CAT',
                'batch'            => 'Batch EX',
                'venue'            => 'Main Auditorium',
                'duration'         => '04:00:00',
                'learning_hours'   => 4.0,
                'start_date'       => '2026-08-01',
                'finish_date'      => '2026-08-02',
                'progress'         => 100,
                'assessment_score' => 92,
            ]),
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
        $row = $this->buildCanonicalTestRow([
            'no'          => 1,
            'employee_id' => 'EMP_CANCEL',
            'name'        => 'Cancel User',
            'course_name' => 'Cancel Training',
        ]);

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
        $row = $this->buildCanonicalTestRow([
            'no'          => 1,
            'employee_id' => 'EMP_CLEANUP',
            'name'        => 'Cleanup User',
            'course_name' => 'Cleanup Training',
        ]);

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
        $row = $this->buildCanonicalTestRow([
            'no'          => 1,
            'employee_id' => $adminEmp,
            'name'        => 'Budi Santoso',
            'course_name' => 'Safety Training',
            'start_date'  => '2026-05-15',
            'finish_date' => '2026-05-10', // Invalid: finish before start!
        ]);

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

