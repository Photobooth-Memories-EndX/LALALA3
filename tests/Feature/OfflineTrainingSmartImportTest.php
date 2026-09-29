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
    }

    protected function tearDown(): void
    {
        // Clean up test DB records
        DB::table('offline_training_users')->where('company', 'TEST_CORP')->delete();
        DB::table('offline_training_users')->where('name', 'Round Trip User')->delete();
        DB::table('offline_training_users')->where('employee_id', 'EMP_ARI_99')->delete();
        DB::table('offline_trainings')->where('category', 'TEST_CAT')->delete();
        DB::table('offline_trainings')->where('course_name', 'Round Trip Training 2026')->delete();

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
     * Helper to construct a canonical 44-element data row matching OfflineTrainingExcelSchema.
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
            'l2_verbal'             => 'Passed with Distinction',
            'l3_numerical'          => 92.5,
            'l3_verbal'             => 'Exceeds Expectations',
            'pic'                   => 'Siti Rahma HRD',
        ];

        $merged = array_merge($defaults, $overrides);

        return [
            $merged['no'],
            $merged['employee_id'],
            $merged['name'],
            $merged['grade'],
            $merged['job_position'],
            $merged['team'],
            $merged['department'],
            $merged['company'],
            $merged['course_name'],
            $merged['sub_course_name'],
            $merged['type'],
            $merged['category'],
            $merged['level'],
            $merged['batch'],
            $merged['main_classification'],
            $merged['sub_classification'],
            $merged['counted_corporate_kpi'],
            $merged['sub_corporate_kpi'],
            $merged['delivery_method'],
            $merged['sub_delivery_method'],
            $merged['provider'],
            $merged['sub_provider'],
            $merged['duration_seconds'],
            $merged['learning_hours'],
            $merged['progress'],
            $merged['start_date'],
            $merged['finish_date'],
            $merged['month'],
            $merged['provider_name'],
            $merged['venue'],
            $merged['instructor'],
            $merged['assessment_score'],
            $merged['certificate'],
            $merged['attendance'],
            $merged['attendance_reason'],
            $merged['bond_status'],
            $merged['bond_end_date'],
            $merged['bond_amount'],
            $merged['l1_program'],
            $merged['l2_numerical'],
            $merged['l2_verbal'],
            $merged['l3_numerical'],
            $merged['l3_verbal'],
            $merged['pic'],
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
     * CASE 1: Perfect New Template (single header row 1, 44 canonical headers, data on row 2+) -> PASS
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
        $this->assertEquals(44, $headerCheck['stats']['valid']);
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
        $this->assertEquals(44, $headerCheck['stats']['valid']);
        $this->assertTrue($response->json('can_confirm'));
    }

    /**
     * CASE 3: Header Capitalization (`job position` instead of `Job Position`) -> AUTO MATCH
     */
    public function test_case_03_header_capitalization_auto_matches(): void
    {
        $headers = OfflineTrainingExcelSchema::headers();
        $headers[4] = 'job position'; // Col 5 in 0-indexed is 4

        $row = $this->buildCanonicalTestRow();
        $file = $this->createSpreadsheetFile([$row], false, $headers);

        $response = $this->actingAs($this->admin)->postJson('/manage/record/offline/import/preview', [
            'file' => $file,
        ]);

        $response->assertStatus(200);
        $headerCheck = $response->json('header_check');

        $this->assertTrue($headerCheck['can_proceed']);
        $this->assertEquals('AUTO_MATCHED', $headerCheck['columns'][5]['status']);
        $this->assertEquals('Job Position', $headerCheck['columns'][5]['mapped_to']);
        $this->assertTrue($response->json('can_confirm'));
    }

    /**
     * CASE 4: Header Underscore (`job_position` instead of `Job Position`) -> AUTO MATCH
     */
    public function test_case_04_header_underscore_auto_matches(): void
    {
        $headers = OfflineTrainingExcelSchema::headers();
        $headers[4] = 'job_position';

        $row = $this->buildCanonicalTestRow();
        $file = $this->createSpreadsheetFile([$row], false, $headers);

        $response = $this->actingAs($this->admin)->postJson('/manage/record/offline/import/preview', [
            'file' => $file,
        ]);

        $response->assertStatus(200);
        $headerCheck = $response->json('header_check');

        $this->assertTrue($headerCheck['can_proceed']);
        $this->assertEquals('AUTO_MATCHED', $headerCheck['columns'][5]['status']);
        $this->assertEquals('Job Position', $headerCheck['columns'][5]['mapped_to']);
        $this->assertTrue($response->json('can_confirm'));
    }

    /**
     * CASE 5: Safe Typo (`Job Positon` instead of `Job Position`) -> AUTO MATCH
     */
    public function test_case_05_safe_typo_job_position_auto_matches(): void
    {
        $headers = OfflineTrainingExcelSchema::headers();
        $headers[4] = 'Job Positon'; // Typo: Positon

        $row = $this->buildCanonicalTestRow();
        $file = $this->createSpreadsheetFile([$row], false, $headers);

        $response = $this->actingAs($this->admin)->postJson('/manage/record/offline/import/preview', [
            'file' => $file,
        ]);

        $response->assertStatus(200);
        $headerCheck = $response->json('header_check');

        $this->assertTrue($headerCheck['can_proceed']);
        $this->assertEquals('AUTO_MATCHED', $headerCheck['columns'][5]['status']);
        $this->assertEquals('Job Position', $headerCheck['columns'][5]['mapped_to']);
        $this->assertTrue($response->json('can_confirm'));
    }

    /**
     * CASE 6: Safe Typo (`Assesment Score` instead of `Assessment Score`) -> AUTO MATCH
     */
    public function test_case_06_safe_typo_assessment_score_auto_matches(): void
    {
        $headers = OfflineTrainingExcelSchema::headers();
        $headers[31] = 'Assesment Score'; // Col 32 in 0-indexed is 31

        $row = $this->buildCanonicalTestRow();
        $file = $this->createSpreadsheetFile([$row], false, $headers);

        $response = $this->actingAs($this->admin)->postJson('/manage/record/offline/import/preview', [
            'file' => $file,
        ]);

        $response->assertStatus(200);
        $headerCheck = $response->json('header_check');

        $this->assertTrue($headerCheck['can_proceed']);
        $this->assertEquals('AUTO_MATCHED', $headerCheck['columns'][32]['status']);
        $this->assertEquals('Assessment Score', $headerCheck['columns'][32]['mapped_to']);
        $this->assertTrue($response->json('can_confirm'));
    }

    /**
     * CASE 7: Wrong Semantic Header (`Provider` in Job Position column) -> INVALID -> blocked from confirm
     */
    public function test_case_07_wrong_semantic_header_blocks_confirm(): void
    {
        $headers = OfflineTrainingExcelSchema::headers();
        $headers[4] = 'Provider'; // Column 5 replaced with Provider (wrong semantic header!)

        $row = $this->buildCanonicalTestRow();
        $file = $this->createSpreadsheetFile([$row], false, $headers);

        $response = $this->actingAs($this->admin)->postJson('/manage/record/offline/import/preview', [
            'file' => $file,
        ]);

        $response->assertStatus(200);
        $headerCheck = $response->json('header_check');

        $this->assertFalse($headerCheck['can_proceed']);
        $this->assertEquals('INVALID', $headerCheck['columns'][5]['status']);
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
     * CASE 8: Missing Column (43 columns only) -> INVALID -> blocked from confirm
     */
    public function test_case_08_missing_column_blocks_confirm(): void
    {
        $headers = OfflineTrainingExcelSchema::headers();
        unset($headers[43]); // Remove column 44 (PIC)

        $row = $this->buildCanonicalTestRow();
        unset($row[43]);
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
     * CASE 9: Extra Column (45 columns) -> INVALID -> blocked from confirm
     */
    public function test_case_09_extra_column_blocks_confirm(): void
    {
        $headers = OfflineTrainingExcelSchema::headers();
        $headers[] = 'Extra Bonus Column'; // Column 45

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
        $headers[7] = 'Department'; // Col 8 (Company) replaced with Department!

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
            'assessment_score' => 'ABC', // Invalid numeric score!
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
        $this->assertStringContainsString('Column: Assessment Score', $errorMsg);
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

        // Verify single header row
        $this->assertEquals('No', $sheet->getCell('A1')->getValue());
        $this->assertEquals('ID', $sheet->getCell('B1')->getValue());
        $this->assertEquals('PIC', $sheet->getCell('AR1')->getValue());

        // Verify freeze pane is A2 (Row 1 frozen)
        $this->assertEquals('A2', $sheet->getFreezePane());

        // 3. Fill Row 2 with valid business data
        $courseName = 'Round Trip Training 2026';
        $batch = 'Batch RT1';
        $participantName = 'Round Trip User';

        $rowData = $this->buildCanonicalTestRow([
            'course_name' => $courseName,
            'batch'       => $batch,
            'name'        => $participantName,
            'employee_id' => 'EMP_RT_01',
        ]);

        $colIdx = 1;
        foreach ($rowData as $val) {
            $colLetter = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($colIdx);
            $sheet->setCellValue($colLetter . '2', $val);
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
