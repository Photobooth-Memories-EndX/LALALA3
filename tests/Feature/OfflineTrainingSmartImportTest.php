<?php

namespace Tests\Feature;

use App\Models\OfflineTraining;
use App\Models\OfflineTrainingUser;
use App\Models\User;
use App\Services\Record\OfflineTrainingExcelSchema;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Tests\TestCase;

class OfflineTrainingSmartImportTest extends TestCase
{
    protected User $admin;
    protected ?User $regularUser;
    protected array $createdTempFiles = [];

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::where('role', '2')->first() ?? User::first();
        $this->regularUser = User::where('role', '1')->first();

        // Ensure clean DB state before tests
        DB::table('offline_training_users')->where('company', 'TEST_CORP')->delete();
        DB::table('offline_training_users')->where('name', 'Round Trip User')->delete();
        DB::table('offline_training_users')->where('employee_id', 'EMP_ARI_99')->delete();
        DB::table('offline_training_users')->where('employee_id', 'EMP_RT_01')->delete();
        DB::table('offline_trainings')->where('category', 'TEST_CAT')->delete();
        DB::table('offline_trainings')->where('course_name', 'Round Trip Training 2026')->delete();
        User::where('id_emp', 'EMP_RT_01')->delete();
    }

    protected function tearDown(): void
    {
        // Clean up test DB records
        DB::table('offline_training_users')->where('company', 'TEST_CORP')->delete();
        DB::table('offline_training_users')->where('name', 'Round Trip User')->delete();
        DB::table('offline_training_users')->where('employee_id', 'EMP_ARI_99')->delete();
        DB::table('offline_training_users')->where('employee_id', 'EMP_RT_01')->delete();
        DB::table('offline_trainings')->where('category', 'TEST_CAT')->delete();
        DB::table('offline_trainings')->where('course_name', 'Round Trip Training 2026')->delete();
        User::where('id_emp', 'EMP_RT_01')->delete();

        // Clean up temporary files
        foreach ($this->createdTempFiles as $path) {
            if (File::exists($path)) {
                File::delete($path);
            }
        }

        $tempDir = storage_path('app/temp');
        if (File::isDirectory($tempDir)) {
            foreach (File::files($tempDir) as $file) {
                if (str_starts_with($file->getFilename(), 'smart_import_test_')) {
                    File::delete($file->getPathname());
                }
            }
        }

        parent::tearDown();
    }

    /**
     * Helper to construct a canonical 48-element data row matching OfflineTrainingExcelSchema.
     */
    protected function buildCanonicalTestRow(array $overrides = []): array
    {
        $defaults = [
            'status'                   => 'Valid',
            'no'                       => 1,
            'employee_id'              => '100234',
            'name'                     => 'Budi Santoso',
            'grade'                    => '4A',
            'job_position'             => 'Electrical Maintenance Engineer',
            'team'                     => 'Rolling Mill 1',
            'department'               => 'Maintenance Department',
            'company'                  => 'TEST_CORP',
            'course_name'              => 'High Voltage Safety & Operation',
            'sub_course_name'          => 'Switchgear Module',
            'type'                     => 'In-House',
            'category'                 => 'TEST_CAT',
            'level'                    => 'Intermediate',
            'batch'                    => 'Batch 1',
            'main_classification'      => 'Technical',
            'sub_classification'       => 'Plant Maintenance',
            'delivery_method'          => 'Offline',
            'sub_delivery_method'      => 'On-site',
            'provider'                 => 'External',
            'sub_provider'             => 'Vendor',
            'provider_name'            => 'PT Schneider Indonesia',
            'venue'                    => 'Auditorium Hall B',
            'instructor'               => 'Ir. Gunawan, M.T.',
            'learning_hours'           => 8.0,
            'progress'                 => 100,
            'start_date'               => '2026-08-10',
            'finish_date'              => '2026-08-10',
            'start_time'               => '08:00:00',
            'end_time'                 => '17:00:00',
            'month'                    => 'August',
            'requirement'              => 'Mandatory',
            'attendance'               => 'Yes',
            'attendance_reason'        => 'Present in all sessions',
            'bond_status'              => 'No',
            'bond_duration'            => '',
            'bond_end_date'            => '',
            'bond_amount'              => '',
            'l1_program'               => 'Very Good',
            'assessment_score'         => 88.0,
            'l2_verbal'                => 'Passed with Distinction',
            'l3_numerical'             => 92.5,
            'l3_verbal'                => 'Exceeds Expectations',
            'certificate_availability' => 'Yes',
            'certificate_institution'  => 'PT Schneider Indonesia',
            'certificate_issued_date'  => '2026-08-11',
            'certificate_expired_date' => '2028-08-11',
            'pic'                      => 'Siti Rahma HRD',
        ];

        $merged = array_merge($defaults, $overrides);

        return [
            $merged['status'] ?? 'Valid',                                         // 1. Status
            $merged['no'] ?? 1,                                                   // 2. No
            $merged['employee_id'] ?? '100234',                                   // 3. Employee ID
            $merged['name'] ?? 'Budi Santoso',                                    // 4. Employee Name
            $merged['grade'] ?? '4A',                                             // 5. Grade
            $merged['job_position'] ?? 'Electrical Maintenance Engineer',         // 6. Job Position
            $merged['team'] ?? 'Rolling Mill 1',                                  // 7. Team
            $merged['department'] ?? 'Maintenance Department',                    // 8. Department
            $merged['company'] ?? 'TEST_CORP',                                    // 9. Company
            $merged['course_name'] ?? 'High Voltage Safety & Operation',          // 10. Course Title
            $merged['sub_course_name'] ?? 'Switchgear Module',                    // 11. Sub Course Title
            $merged['type'] ?? 'In-House',                                        // 12. Type
            $merged['category'] ?? 'TEST_CAT',                                    // 13. Category
            $merged['level'] ?? 'Intermediate',                                   // 14. Level
            $merged['batch'] ?? 'Batch 1',                                        // 15. Batch
            $merged['main_classification'] ?? 'Technical',                        // 16. Main Classification
            $merged['sub_classification'] ?? 'Plant Maintenance',                 // 17. Sub Classification
            $merged['delivery_method'] ?? 'Offline',                              // 18. Delivery Method
            $merged['sub_delivery_method'] ?? 'On-site',                          // 19. Sub Delivery Method
            $merged['provider'] ?? 'External',                                    // 20. Provider
            $merged['sub_provider'] ?? 'Vendor',                                  // 21. Sub Provider
            $merged['provider_name'] ?? 'PT Schneider Indonesia',                 // 22. Provider Name
            $merged['venue'] ?? 'Auditorium Hall B',                              // 23. Venue
            $merged['instructor'] ?? 'Ir. Gunawan, M.T.',                         // 24. Instructor
            $merged['learning_hours'] ?? 8.0,                                     // 25. Learning Hours
            $merged['progress'] ?? 100,                                           // 26. % Progress
            $merged['start_date'] ?? '2026-08-10',                                // 27. Start Date
            $merged['finish_date'] ?? ($merged['end_date'] ?? '2026-08-10'),     // 28. End Date
            $merged['start_time'] ?? '08:00:00',                                  // 29. Start Time
            $merged['end_time'] ?? '17:00:00',                                    // 30. End Time
            $merged['month'] ?? 'August',                                         // 31. Record Month
            $merged['requirement'] ?? 'Mandatory',                                // 32. Requirement
            $merged['attendance'] ?? 'Yes',                                       // 33. Attendance
            $merged['attendance_reason'] ?? 'Present in all sessions',            // 34. Attendance Reason
            $merged['bond_status'] ?? 'No',                                       // 35. Bond Status
            $merged['bond_duration'] ?? '',                                       // 36. Bond Duration
            $merged['bond_end_date'] ?? '',                                       // 37. Bond End Date
            $merged['bond_amount'] ?? '',                                         // 38. Bond Amount
            $merged['l1_program'] ?? 'Very Good',                                 // 39. Score (L1) - Course
            $merged['l2_numerical'] ?? ($merged['assessment_score'] ?? 88.0),     // 40. Score (L2)
            $merged['l2_verbal'] ?? 'Passed with Distinction',                    // 41. L2 Status
            $merged['l3_numerical'] ?? 92.5,                                      // 42. Score (L3)
            $merged['l3_verbal'] ?? 'Exceeds Expectations',                       // 43. L3 Status
            $merged['certificate_availability'] ?? 'Yes',                         // 44. Certificate Availability
            $merged['certificate_institution'] ?? 'PT Schneider Indonesia',       // 45. Certificate Institution
            $merged['certificate_issued_date'] ?? '2026-08-11',                   // 46. Certificate Issued Date
            $merged['certificate_expired_date'] ?? '2028-08-11',                  // 47. Certificate Expired Date
            $merged['pic'] ?? 'Siti Rahma HRD',                                   // 48. Person in Charge (PIC)
        ];
    }

    /**
     * Helper to create a test spreadsheet file with arbitrary headers and data rows.
     */
    protected function createSpreadsheetFile(array $rows, bool $twoRow = false, ?array $customHeaders = null): UploadedFile
    {
        $headers = $customHeaders ?? OfflineTrainingExcelSchema::headers();
        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Non KP OWL Import');

        $startRow = 1;

        if ($twoRow) {
            $groupHeaders = OfflineTrainingExcelSchema::groupedRow1Headers();
            $colIdx = 1;
            foreach ($groupHeaders as $val) {
                $colLetter = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($colIdx);
                $sheet->setCellValue($colLetter . '1', $val);
                $colIdx++;
            }

            $colIdx = 1;
            foreach ($headers as $val) {
                $colLetter = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($colIdx);
                $sheet->setCellValue($colLetter . '2', $val);
                $colIdx++;
            }
            $startRow = 3;
        } else {
            $colIdx = 1;
            foreach ($headers as $val) {
                $colLetter = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($colIdx);
                $sheet->setCellValue($colLetter . '1', $val);
                $colIdx++;
            }
            $startRow = 2;
        }

        $rowIdx = $startRow;
        foreach ($rows as $rowData) {
            $cIdx = 1;
            foreach ($rowData as $val) {
                $colLetter = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($cIdx);
                $sheet->setCellValue($colLetter . $rowIdx, $val);
                $cIdx++;
            }
            $rowIdx++;
        }

        $tempPath = storage_path('app/temp/smart_import_test_' . uniqid() . '.xlsx');
        if (!File::isDirectory(storage_path('app/temp'))) {
            File::makeDirectory(storage_path('app/temp'), 0755, true);
        }

        $writer = new Xlsx($spreadsheet);
        $writer->save($tempPath);

        $this->createdTempFiles[] = $tempPath;

        return new UploadedFile($tempPath, 'smart_import_test.xlsx', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet', null, true);
    }

    /**
     * CASE 1: Perfect New Template (single header row 1, 48 canonical headers, data on row 2+) -> PASS
     */
    public function test_case_01_perfect_new_template_passes_validation(): void
    {
        $row = $this->buildCanonicalTestRow();
        $file = $this->createSpreadsheetFile([$row], false);

        $response = $this->actingAs($this->admin)->postJson('/manage/record/offline/import/preview', [
            'file' => $file,
        ]);

        $response->assertStatus(200);
        $headerCheck = $response->json('header_check');

        $this->assertTrue($headerCheck['can_proceed']);
        $this->assertEquals('single_row', $headerCheck['format']);
        $this->assertEquals(48, $headerCheck['stats']['valid']);
        $this->assertEquals(0, $headerCheck['stats']['invalid']);
        $this->assertEquals(0, $headerCheck['stats']['auto_matched']);
        $this->assertEmpty($headerCheck['issues']);
        $this->assertTrue($response->json('can_confirm'));
    }

    /**
     * CASE 2: Legacy Template (row 1 grouping, row 2 canonical headers, data on row 3+) -> PASS
     */
    public function test_case_02_legacy_template_passes_validation(): void
    {
        $row = $this->buildCanonicalTestRow();
        $file = $this->createSpreadsheetFile([$row], true);

        $response = $this->actingAs($this->admin)->postJson('/manage/record/offline/import/preview', [
            'file' => $file,
        ]);

        $response->assertStatus(200);
        $headerCheck = $response->json('header_check');

        $this->assertTrue($headerCheck['can_proceed']);
        $this->assertEquals('legacy_two_row', $headerCheck['format']);
        $this->assertEquals(48, $headerCheck['stats']['valid']);
        $this->assertTrue($response->json('can_confirm'));
    }

    /**
     * CASE 3: Header Capitalization (`job position` instead of `Job Position`) -> AUTO MATCH
     */
    public function test_case_03_header_capitalization_auto_matches(): void
    {
        $headers = OfflineTrainingExcelSchema::headers();
        $headers[5] = 'job position'; // Col 6 in 0-indexed is 5

        $row = $this->buildCanonicalTestRow();
        $file = $this->createSpreadsheetFile([$row], false, $headers);

        $response = $this->actingAs($this->admin)->postJson('/manage/record/offline/import/preview', [
            'file' => $file,
        ]);

        $response->assertStatus(200);
        $headerCheck = $response->json('header_check');

        $this->assertTrue($headerCheck['can_proceed']);
        $this->assertEquals('AUTO_MATCHED', $headerCheck['columns'][6]['status']);
        $this->assertEquals('Job Position', $headerCheck['columns'][6]['mapped_to']);
        $this->assertTrue($response->json('can_confirm'));
    }

    /**
     * CASE 4: Header Underscore (`job_position` instead of `Job Position`) -> AUTO MATCH
     */
    public function test_case_04_header_underscore_auto_matches(): void
    {
        $headers = OfflineTrainingExcelSchema::headers();
        $headers[5] = 'job_position';

        $row = $this->buildCanonicalTestRow();
        $file = $this->createSpreadsheetFile([$row], false, $headers);

        $response = $this->actingAs($this->admin)->postJson('/manage/record/offline/import/preview', [
            'file' => $file,
        ]);

        $response->assertStatus(200);
        $headerCheck = $response->json('header_check');

        $this->assertTrue($headerCheck['can_proceed']);
        $this->assertEquals('AUTO_MATCHED', $headerCheck['columns'][6]['status']);
        $this->assertEquals('Job Position', $headerCheck['columns'][6]['mapped_to']);
        $this->assertTrue($response->json('can_confirm'));
    }

    /**
     * CASE 5: Safe Typo (`Job Positon` instead of `Job Position`) -> AUTO MATCH
     */
    public function test_case_05_safe_typo_job_position_auto_matches(): void
    {
        $headers = OfflineTrainingExcelSchema::headers();
        $headers[5] = 'Job Positon'; // Typo: Positon

        $row = $this->buildCanonicalTestRow();
        $file = $this->createSpreadsheetFile([$row], false, $headers);

        $response = $this->actingAs($this->admin)->postJson('/manage/record/offline/import/preview', [
            'file' => $file,
        ]);

        $response->assertStatus(200);
        $headerCheck = $response->json('header_check');

        $this->assertTrue($headerCheck['can_proceed']);
        $this->assertEquals('AUTO_MATCHED', $headerCheck['columns'][6]['status']);
        $this->assertEquals('Job Position', $headerCheck['columns'][6]['mapped_to']);
        $this->assertTrue($response->json('can_confirm'));
    }

    /**
     * CASE 6: Safe Typo (`Score L2` instead of `Score (L2)`) -> AUTO MATCH
     */
    public function test_case_06_safe_typo_assessment_score_auto_matches(): void
    {
        $headers = OfflineTrainingExcelSchema::headers();
        $headers[39] = 'Score L2'; // Col 40 in 0-indexed is 39

        $row = $this->buildCanonicalTestRow();
        $file = $this->createSpreadsheetFile([$row], false, $headers);

        $response = $this->actingAs($this->admin)->postJson('/manage/record/offline/import/preview', [
            'file' => $file,
        ]);

        $response->assertStatus(200);
        $headerCheck = $response->json('header_check');

        $this->assertTrue($headerCheck['can_proceed']);
        $this->assertEquals('AUTO_MATCHED', $headerCheck['columns'][40]['status']);
        $this->assertEquals('Score (L2)', $headerCheck['columns'][40]['mapped_to']);
        $this->assertTrue($response->json('can_confirm'));
    }

    /**
     * CASE 7: Wrong Semantic Header (`Provider` in Job Position column) -> INVALID -> blocked from confirm
     */
    public function test_case_07_wrong_semantic_header_blocks_confirm(): void
    {
        $headers = OfflineTrainingExcelSchema::headers();
        $headers[5] = 'Provider'; // Column 6 replaced with Provider (wrong semantic header!)

        $row = $this->buildCanonicalTestRow();
        $file = $this->createSpreadsheetFile([$row], false, $headers);

        $response = $this->actingAs($this->admin)->postJson('/manage/record/offline/import/preview', [
            'file' => $file,
        ]);

        $response->assertStatus(200);
        $headerCheck = $response->json('header_check');

        $this->assertFalse($headerCheck['can_proceed']);
        $this->assertEquals('INVALID', $headerCheck['columns'][6]['status']);
        $this->assertFalse($response->json('can_confirm'));

        $token = $response->json('data.token');

        // Confirm endpoint must block execution
        $confResponse = $this->actingAs($this->admin)->postJson('/manage/record/offline/import/confirm', [
            'token' => $token,
        ]);
        $confResponse->assertStatus(422);
        $confResponse->assertJson(['success' => false]);
    }

    /**
     * CASE 8: Missing Column (47 columns only) -> INVALID -> blocked from confirm
     */
    public function test_case_08_missing_column_blocks_confirm(): void
    {
        $headers = OfflineTrainingExcelSchema::headers();
        unset($headers[47]); // Remove column 48 (PIC)

        $row = $this->buildCanonicalTestRow();
        unset($row[47]);
        $file = $this->createSpreadsheetFile([$row], false, array_values($headers));

        $response = $this->actingAs($this->admin)->postJson('/manage/record/offline/import/preview', [
            'file' => $file,
        ]);

        $response->assertStatus(200);
        $headerCheck = $response->json('header_check');

        $this->assertFalse($headerCheck['can_proceed']);
        $this->assertFalse($response->json('can_confirm'));

        $token = $response->json('data.token');

        $confResponse = $this->actingAs($this->admin)->postJson('/manage/record/offline/import/confirm', [
            'token' => $token,
        ]);
        $confResponse->assertStatus(422);
    }

    /**
     * CASE 9: Extra Column (49 columns) -> INVALID -> blocked from confirm
     */
    public function test_case_09_extra_column_blocks_confirm(): void
    {
        $headers = OfflineTrainingExcelSchema::headers();
        $headers[] = 'Extra Bonus Column'; // Column 49

        $row = $this->buildCanonicalTestRow();
        $row[] = 'Extra Value';
        $file = $this->createSpreadsheetFile([$row], false, $headers);

        $response = $this->actingAs($this->admin)->postJson('/manage/record/offline/import/preview', [
            'file' => $file,
        ]);

        $response->assertStatus(200);
        $headerCheck = $response->json('header_check');

        $this->assertFalse($headerCheck['can_proceed']);
        $this->assertGreaterThanOrEqual(1, $headerCheck['stats']['invalid']);
        $this->assertFalse($response->json('can_confirm'));

        $token = $response->json('data.token');

        $confResponse = $this->actingAs($this->admin)->postJson('/manage/record/offline/import/confirm', [
            'token' => $token,
        ]);
        $confResponse->assertStatus(422);
    }

    /**
     * CASE 10: Duplicate Canonical Mapping (two columns map to department) -> DUPLICATE -> blocked from confirm
     */
    public function test_case_10_duplicate_canonical_mapping_blocks_confirm(): void
    {
        $headers = OfflineTrainingExcelSchema::headers();
        $headers[8] = 'Department'; // Col 9 (Company) replaced with Department!

        $row = $this->buildCanonicalTestRow();
        $file = $this->createSpreadsheetFile([$row], false, $headers);

        $response = $this->actingAs($this->admin)->postJson('/manage/record/offline/import/preview', [
            'file' => $file,
        ]);

        $response->assertStatus(200);
        $headerCheck = $response->json('header_check');

        $this->assertFalse($headerCheck['can_proceed']);
        $this->assertGreaterThanOrEqual(2, $headerCheck['stats']['duplicate']);
        $this->assertFalse($response->json('can_confirm'));

        $token = $response->json('data.token');

        $confResponse = $this->actingAs($this->admin)->postJson('/manage/record/offline/import/confirm', [
            'token' => $token,
        ]);
        $confResponse->assertStatus(422);
    }

    /**
     * CASE 11: Duplicate Display Labels L2/L3 Disambiguated Correctly -> PASS
     */
    public function test_case_11_duplicate_labels_l2_l3_disambiguated_correctly(): void
    {
        // Standard canonical headers have duplicate display names:
        // Col 40: Numerical, Col 41: Verbal, Col 42: Numerical, Col 43: Verbal
        $row = $this->buildCanonicalTestRow([
            'l2_numerical' => 82.5,
            'l2_verbal'    => 'Satisfactory',
            'l3_numerical' => 95.0,
            'l3_verbal'    => 'Excellent',
        ]);
        $file = $this->createSpreadsheetFile([$row], false);

        $response = $this->actingAs($this->admin)->postJson('/manage/record/offline/import/preview', [
            'file' => $file,
        ]);

        $response->assertStatus(200);
        $headerCheck = $response->json('header_check');
        $this->assertTrue($headerCheck['can_proceed']);

        $previewRow = $response->json('data.preview_rows.0.data');
        $this->assertEquals(82.5, $previewRow['l2_numerical']);
        $this->assertEquals('Satisfactory', $previewRow['l2_verbal']);
        $this->assertEquals(95.0, $previewRow['l3_numerical']);
        $this->assertEquals('Excellent', $previewRow['l3_verbal']);
    }

    /**
     * CASE 12: Participant Name Case and Space Normalization -> Matched to DB User
     */
    /**
     * Helper to create a user with all required DB fields.
     */
    protected function createTestUser(array $attributes = []): User
    {
        $unique = uniqid();
        $defaults = [
            'name'         => 'Test Participant ' . $unique,
            'email'        => 'test_' . $unique . '@example.com',
            'id_user'      => (string) rand(10000, 99999),
            'id_emp'       => 'EMP_' . $unique,
            'id_org_unit'  => 'OU01',
            'company_name' => 'PT Krakatau Posco',
            'team'         => 'Test Team',
            'department'   => 'Test Dept',
            'division'     => 'Test Division',
            'ssohash'      => md5($unique),
            'sap_code'     => (string) rand(10000000, 99999999),
            'role'         => '1',
            'active'       => 1,
            'password'     => bcrypt('secret'),
        ];

        return User::create(array_merge($defaults, $attributes));
    }

    /**
     * CASE 12: Participant Name Case and Space Normalization -> Matched to DB User
     */
    public function test_case_12_participant_name_case_and_space_normalization(): void
    {
        // Create user in DB
        $user = $this->createTestUser([
            'name'   => 'Ari Prasetyo Utomo',
            'id_emp' => 'EMP_ARI_99',
        ]);

        $row = $this->buildCanonicalTestRow([
            'employee_id' => 'EMP_ARI_99',
            'name'        => '  ARI   PRASETYO  UTOMO  ',
        ]);
        $file = $this->createSpreadsheetFile([$row], false);

        $response = $this->actingAs($this->admin)->postJson('/manage/record/offline/import/preview', [
            'file' => $file,
        ]);

        $response->assertStatus(200);
        $previewRow = $response->json('data.preview_rows.0');

        $this->assertTrue($previewRow['user_matched']);
        $this->assertEquals($user->id, $previewRow['data']['user_id']);
    }

    /**
     * CASE 13: Ambiguous Name Matching Without NIP Is Not Guessed
     */
    public function test_case_13_ambiguous_name_without_nip_is_not_guessed(): void
    {
        $uniqueName = 'Bambang Susanto ' . uniqid();
        // Create 2 LMS users with exact same name
        $user1 = $this->createTestUser(['name' => $uniqueName, 'id_emp' => 'EMP_BS_101']);
        $user2 = $this->createTestUser(['name' => $uniqueName, 'id_emp' => 'EMP_BS_102']);

        // Upload row with NO NIP and this duplicate name
        $row = $this->buildCanonicalTestRow([
            'employee_id' => '',
            'name'        => $uniqueName,
        ]);
        $file = $this->createSpreadsheetFile([$row], false);

        $response = $this->actingAs($this->admin)->postJson('/manage/record/offline/import/preview', [
            'file' => $file,
        ]);

        $response->assertStatus(200);
        $previewRow = $response->json('data.preview_rows.0');

        // Must NOT guess which user! user_id must be null
        $this->assertFalse($previewRow['user_matched']);
        $this->assertNull($previewRow['data']['user_id']);
        $this->assertStringContainsString('AMBIGUOUS PARTICIPANT MATCH', implode(' | ', $previewRow['messages']));
    }

    /**
     * CASE 14: Correct Header, Wrong Row Data Type (Assessment Score = 'ABC')
     */
    public function test_case_14_correct_headers_with_invalid_row_data_type(): void
    {
        $row = $this->buildCanonicalTestRow([
            'assessment_score' => 'ABC',
            'l2_numerical'     => 'ABC', // Invalid numeric score!
        ]);
        $file = $this->createSpreadsheetFile([$row], false);

        $response = $this->actingAs($this->admin)->postJson('/manage/record/offline/import/preview', [
            'file' => $file,
        ]);

        $response->assertStatus(200);
        $headerCheck = $response->json('header_check');

        // Phase A: Header validation passes!
        $this->assertTrue($headerCheck['can_proceed']);

        // Phase B: Row data validation fails with cell coordinate and expected type!
        $this->assertEquals(1, $response->json('data.stats.invalid'));
        $previewRow = $response->json('data.preview_rows.0');
        $this->assertEquals('invalid', $previewRow['status']);

        $errorMsg = $previewRow['messages'][0];
        $this->assertTrue(str_contains($errorMsg, 'Assessment Score') || str_contains($errorMsg, 'Score (L2)'));
        $this->assertStringContainsString("Value: 'ABC'", $errorMsg);
        $this->assertStringContainsString('Expected: Numeric value', $errorMsg);
        $this->assertStringContainsString('Status: INVALID VALUE', $errorMsg);
    }

    /**
     * CASE 15: Import Round Trip (Download NEW template -> fill row -> upload -> preview -> confirm -> DB verify)
     */
    public function test_case_15_round_trip_template_download_and_database_import(): void
    {
        // 1. Download template
        $dlResponse = $this->actingAs($this->admin)->get('/manage/record/offline/template');
        $dlResponse->assertStatus(200);
        $dlResponse->assertHeader('content-type', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');

        $tempTemplate = storage_path('app/temp/smart_import_test_dl_' . uniqid() . '.xlsx');
        File::put($tempTemplate, $dlResponse->streamedContent());
        $this->createdTempFiles[] = $tempTemplate;

        // 2. Inspect downloaded template
        $reader = IOFactory::createReader('Xlsx');
        $spreadsheet = $reader->load($tempTemplate);
        $sheet = $spreadsheet->getSheetByName('Non KP OWL Import');
        $this->assertNotNull($sheet);

        // Verify headers & freeze pane
        $this->assertEquals('Status', $sheet->getCell('A2')->getValue());
        $this->assertEquals('No', $sheet->getCell('B2')->getValue());
        $this->assertEquals('Employee ID', $sheet->getCell('C2')->getValue());
        $this->assertEquals('Employee Name', $sheet->getCell('D2')->getValue());
        $this->assertEquals('Person in Charge (PIC)', $sheet->getCell('AV2')->getValue());

        // Verify freeze pane is D3 (Rows 1-2 and columns A-C frozen)
        $this->assertEquals('D3', $sheet->getFreezePane());

        // 3. Fill Row 3 with valid business data
        $courseName = 'Round Trip Training 2026';
        $batch = 'Batch RT1';
        $participantName = 'Round Trip User';

        $this->createTestUser([
            'id_emp' => 'EMP_RT_01',
            'name'   => $participantName,
        ]);

        $rowData = $this->buildCanonicalTestRow([
            'course_name' => $courseName,
            'batch'       => $batch,
            'name'        => $participantName,
            'employee_id' => 'EMP_RT_01',
        ]);

        $colIdx = 1;
        foreach ($rowData as $val) {
            $colLetter = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($colIdx);
            $sheet->setCellValue($colLetter . '3', $val);
            $colIdx++;
        }

        $writer = new Xlsx($spreadsheet);
        $writer->save($tempTemplate);

        $uploadFile = new UploadedFile($tempTemplate, 'round_trip.xlsx', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet', null, true);

        // 4. Preview
        $previewResponse = $this->actingAs($this->admin)->postJson('/manage/record/offline/import/preview', [
            'file' => $uploadFile,
        ]);
        $previewResponse->assertStatus(200);
        $this->assertTrue($previewResponse->json('can_confirm'));
        $this->assertEquals(1, $previewResponse->json('data.stats.valid'));
        $token = $previewResponse->json('data.token');

        // 5. Confirm
        $confirmResponse = $this->actingAs($this->admin)->postJson('/manage/record/offline/import/confirm', [
            'token' => $token,
        ]);
        $confirmResponse->assertStatus(200);
        $this->assertEquals(1, $confirmResponse->json('result.imported'));

        // 6. DB Verification
        $dbTraining = OfflineTraining::where('course_name', $courseName)->where('batch', $batch)->first();
        $this->assertNotNull($dbTraining);

        $dbParticipant = OfflineTrainingUser::where('offline_training_id', $dbTraining->id)->where('name', $participantName)->first();
        $this->assertNotNull($dbParticipant);
        $this->assertEquals('EMP_RT_01', $dbParticipant->employee_id);
    }
}
