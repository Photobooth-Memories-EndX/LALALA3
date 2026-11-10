<?php

namespace Tests\Feature;

use App\Models\OfflineTraining;
use App\Models\OfflineTrainingUser;
use App\Models\User;
use App\Services\OfflineTraining\PtkpImportService;
use App\Services\OfflineTraining\PtkpValidationService;
use App\Services\Record\PtkpExcelSchema;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Http\UploadedFile;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Tests\TestCase;

class PtkpFullRowDuplicateDetectionTest extends TestCase
{
    use DatabaseTransactions;

    protected User $adminUser;
    protected User $testEmployee;
    protected User $testEmployee2;
    protected PtkpValidationService $validationService;

    protected function setUp(): void
    {
        parent::setUp();

        $this->validationService = app(PtkpValidationService::class);

        $this->adminUser = User::whereIn('role', [User::ROLE_ADMIN, User::ROLE_SUPER_ADMIN])->first()
            ?? $this->getOrCreateTestUser('ADM_DUP_01', 'Admin Duplicate Tester', ['role' => User::ROLE_ADMIN]);

        $this->testEmployee = $this->getOrCreateTestUser('131478', 'FERDIAN CAHYO DWIPUTRO', [
            'jobgrade'     => '4A',
            'jobposition'  => 'Software Engineer',
            'team'         => 'OWL Development Team',
            'department'   => 'IT Application Dept',
            'company_name' => 'PT Krakatau Posco',
        ]);
        $this->testEmployee2 = $this->getOrCreateTestUser('117834', 'RIJAL SETIAWAN', [
            'jobgrade'     => '3B',
            'jobposition'  => 'Senior Technician',
            'team'         => 'Electrical Maintenance Team',
            'department'   => 'Plant Maintenance Dept',
            'company_name' => 'PT Krakatau Tirta Industri',
        ]);
    }

    protected function getOrCreateTestUser(string $empId, string $name, array $attrs = []): User
    {
        $existing = User::where('id_emp', $empId)->first();
        if ($existing) {
            if (!empty($attrs)) {
                $existing->update($attrs);
            }
            return $existing;
        }

        static $seq = 9200;
        $seq++;

        return User::create(array_merge([
            'name'         => $name,
            'email'        => "dup_user_{$seq}@example.com",
            'password'     => bcrypt('secret'),
            'id_emp'       => $empId,
            'id_user'      => 'U' . substr(str_pad((string)$seq, 8, '0', STR_PAD_LEFT), -8),
            'id_org_unit'  => 'OU_01',
            'jobgrade'     => '4A',
            'jobposition'  => 'Software Engineer',
            'team'         => 'OWL Development Team',
            'department'   => 'IT Application Dept',
            'division'     => 'Test Div',
            'sap_code'     => 'SAP_' . $seq,
            'ssohash'      => 'HASH_' . $seq,
            'role'         => User::ROLE_USER,
            'company_name' => 'PT Krakatau Posco',
        ], $attrs));
    }

    protected function createPtkpExcel(array $rows): UploadedFile
    {
        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle(PtkpExcelSchema::DATA_SHEET_TITLE);

        $columns = PtkpExcelSchema::columns();
        foreach ($columns as $col) {
            $letter = $col['col_letter'];
            $sheet->setCellValue("{$letter}1", $col['header']);
        }

        $rowIdx = 2;
        foreach ($rows as $data) {
            foreach ($columns as $col) {
                $letter = $col['col_letter'];
                $key = $col['key'];

                if ($key === 'employee_id') {
                    $sheet->setCellValueExplicit("{$letter}{$rowIdx}", (string) ($data['employee_id'] ?? ''), \PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_STRING);
                } elseif (array_key_exists($key, $data)) {
                    $sheet->setCellValue("{$letter}{$rowIdx}", $data[$key]);
                } else {
                    $sheet->setCellValue("{$letter}{$rowIdx}", $col['example'] ?? '');
                }
            }
            $rowIdx++;
        }

        $tempPath = tempnam(sys_get_temp_dir(), 'ptkp_dup_test_') . '.xlsx';
        $writer = new Xlsx($spreadsheet);
        $writer->save($tempPath);

        return new UploadedFile($tempPath, 'ptkp_dup_test.xlsx', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet', null, true);
    }

    /**
     * Canonical 42-column baseline record representing standard PTKP business data.
     */
    protected function getCanonicalBaseline(): array
    {
        return [
            // Employee Information (7)
            'employee_id'         => '131478',
            'name'                => 'FERDIAN CAHYO DWIPUTRO',
            'grade'               => '4A',
            'job_position'        => 'Software Engineer',
            'team'                => 'OWL Development Team',
            'department'          => 'IT Application Dept',
            'company'             => 'PT Krakatau Posco',

            // Training Information (6)
            'course_name'         => 'Personalized Skill Development',
            'sub_course_name'     => 'Advanced Laravel Architecture',
            'type'                => 'Online',
            'category'            => 'Technical Competency',
            'level'               => 'Intermediate',
            'batch'               => 'Batch 1',

            // Classification (2)
            'main_classification' => 'Job Functional',
            'sub_classification'  => 'General Skill',

            // Delivery & Provider (5)
            'delivery_method'     => 'Online',
            'sub_delivery_method' => 'Self-Paced Learning',
            'provider_name'       => 'LinkedIn Learning',
            'venue'               => 'Zoom & Portal',
            'instructor'          => 'Taylor Otwell',

            // Learning Details (9)
            'course_duration'     => '32400',
            'learning_hours'      => '8.0',
            'progress'            => '100',
            'progress_status'     => 'Completed',
            'start_date'          => '2026-08-10',
            'end_date'            => '2026-08-10',
            'start_time'          => '08:00:00',
            'end_time'            => '17:00:00',
            'month'               => '8',

            // Assessment, Attendance & Bond (12)
            'requirement'         => 'Mandatory',
            'attendance'          => '1',
            'attendance_reason'   => 'Present in all sessions',
            'bond_status'         => 'No',
            'bond_duration'       => '0',
            'bond_end_date'       => '2026-08-10',
            'bond_amount'         => '0',
            'l1_program'          => 'Very Good',
            'assessment_score'    => '85',
            'l2_verbal'           => 'Passed',
            'l3_numerical'        => '90',
            'l3_verbal'           => 'Competent',

            // Person in Charge (1)
            'pic'                 => 'HRDC Team',
        ];
    }

    /**
     * Map of 42 canonical columns to an alternative distinct value for single-difference tests.
     */
    protected function getAlternativeColumnValues(): array
    {
        return [
            // Employee Information (7)
            'employee_id'         => '117834',
            'name'                => 'RIJAL SETIAWAN',
            'grade'               => '3B',
            'job_position'        => 'Senior Technician',
            'team'                => 'Electrical Maintenance Team',
            'department'          => 'Plant Maintenance Dept',
            'company'             => 'PT Krakatau Tirta Industri',

            // Training Information (6)
            'course_name'         => 'Industrial Safety Management',
            'sub_course_name'     => 'Hazard Identification Module',
            'type'                => 'In-House',
            'category'            => 'Safety & Health',
            'level'               => 'Advanced',
            'batch'               => 'Batch 2',

            // Classification (2)
            'main_classification' => 'Leadership',
            'sub_classification'  => 'Special Skill',

            // Delivery & Provider (5)
            'delivery_method'     => 'Offline',
            'sub_delivery_method' => 'Classroom Workshop',
            'provider_name'       => 'Internal Training Center',
            'venue'               => 'Auditorium Hall B',
            'instructor'          => 'Ir. Gunawan, M.T.',

            // Learning Details (9)
            'course_duration'     => '14400',
            'learning_hours'      => '4.0',
            'progress'            => '50',
            'progress_status'     => 'In Progress',
            'start_date'          => '2026-09-01',
            'end_date'            => '2026-09-02',
            'start_time'          => '09:00:00',
            'end_time'            => '18:00:00',
            'month'               => '9',

            // Assessment, Attendance & Bond (12)
            'requirement'         => 'Non Mandatory',
            'attendance'          => '0',
            'attendance_reason'   => 'Medical Sick Leave',
            'bond_status'         => 'Yes',
            'bond_duration'       => '12',
            'bond_end_date'       => '2028-08-10',
            'bond_amount'         => '5000000',
            'l1_program'          => 'Excellent',
            'assessment_score'    => '95',
            'l2_verbal'           => 'Distinction',
            'l3_numerical'        => '75',
            'l3_verbal'           => 'Needs Improvement',

            // Person in Charge (1)
            'pic'                 => 'HR Training Specialist',
        ];
    }

    // =========================================================================
    // SECTION 8 — OFFICIAL 48 SCENARIOS
    // =========================================================================

    /**
     * Scenario 1: Exact match on all 42 canonical columns -> DUPLICATE.
     */
    public function test_01_all_42_columns_match_is_duplicate(): void
    {
        $baseline = $this->getCanonicalBaseline();
        $identical = $this->getCanonicalBaseline();

        $cmp = $this->validationService->compareFullRowValues($baseline, $identical, false);

        $this->assertTrue($cmp['is_match'], 'Exact match on all 42 columns must evaluate as duplicate');
        $this->assertEmpty($cmp['diff_fields']);
        $this->assertCount(42, $cmp['matched_fields']);
    }

    /**
     * Scenario 2: All columns match except 'No' (preview row index) -> STILL DUPLICATE (No is strictly excluded).
     */
    public function test_02_only_no_differs_is_duplicate(): void
    {
        $rowA = array_merge($this->getCanonicalBaseline(), ['row_no' => 1, 'no' => 1]);
        $rowB = array_merge($this->getCanonicalBaseline(), ['row_no' => 205, 'no' => 205]);

        $cmp = $this->validationService->compareFullRowValues($rowA, $rowB, false);

        $this->assertTrue($cmp['is_match'], 'Difference only in No/row_no must still be recognized as duplicate');
        $this->assertEmpty($cmp['diff_fields']);
    }

    // -------------------------------------------------------------------------
    // Scenarios 3 - 44: Exactly 1 column differs out of the 42 official canonical columns
    // Each of the 42 columns tested individually -> MUST NOT BE DUPLICATE (remains VALID)
    // -------------------------------------------------------------------------

    public function test_03_diff_employee_id_not_duplicate(): void
    {
        $this->assertSingleColumnDifferenceNotDuplicate('employee_id');
    }

    public function test_04_diff_name_not_duplicate(): void
    {
        $this->assertSingleColumnDifferenceNotDuplicate('name');
    }

    public function test_05_diff_grade_not_duplicate(): void
    {
        $this->assertSingleColumnDifferenceNotDuplicate('grade');
    }

    public function test_06_diff_job_position_not_duplicate(): void
    {
        $this->assertSingleColumnDifferenceNotDuplicate('job_position');
    }

    public function test_07_diff_team_not_duplicate(): void
    {
        $this->assertSingleColumnDifferenceNotDuplicate('team');
    }

    public function test_08_diff_department_not_duplicate(): void
    {
        $this->assertSingleColumnDifferenceNotDuplicate('department');
    }

    public function test_09_diff_company_not_duplicate(): void
    {
        $this->assertSingleColumnDifferenceNotDuplicate('company');
    }

    public function test_10_diff_course_name_not_duplicate(): void
    {
        $this->assertSingleColumnDifferenceNotDuplicate('course_name');
    }

    public function test_11_diff_sub_course_name_not_duplicate(): void
    {
        $this->assertSingleColumnDifferenceNotDuplicate('sub_course_name');
    }

    public function test_12_diff_type_not_duplicate(): void
    {
        $this->assertSingleColumnDifferenceNotDuplicate('type');
    }

    public function test_13_diff_category_not_duplicate(): void
    {
        $this->assertSingleColumnDifferenceNotDuplicate('category');
    }

    public function test_14_diff_level_not_duplicate(): void
    {
        $this->assertSingleColumnDifferenceNotDuplicate('level');
    }

    public function test_15_diff_batch_not_duplicate(): void
    {
        $this->assertSingleColumnDifferenceNotDuplicate('batch');
    }

    public function test_16_diff_main_classification_not_duplicate(): void
    {
        $this->assertSingleColumnDifferenceNotDuplicate('main_classification');
    }

    public function test_17_diff_sub_classification_not_duplicate(): void
    {
        $this->assertSingleColumnDifferenceNotDuplicate('sub_classification');
    }

    public function test_18_diff_delivery_method_not_duplicate(): void
    {
        $this->assertSingleColumnDifferenceNotDuplicate('delivery_method');
    }

    public function test_19_diff_sub_delivery_method_not_duplicate(): void
    {
        $this->assertSingleColumnDifferenceNotDuplicate('sub_delivery_method');
    }

    public function test_20_diff_provider_name_not_duplicate(): void
    {
        $this->assertSingleColumnDifferenceNotDuplicate('provider_name');
    }

    public function test_21_diff_venue_not_duplicate(): void
    {
        $this->assertSingleColumnDifferenceNotDuplicate('venue');
    }

    public function test_22_diff_instructor_not_duplicate(): void
    {
        $this->assertSingleColumnDifferenceNotDuplicate('instructor');
    }

    public function test_23_diff_course_duration_not_duplicate(): void
    {
        $this->assertSingleColumnDifferenceNotDuplicate('course_duration');
    }

    public function test_24_diff_learning_hours_not_duplicate(): void
    {
        $this->assertSingleColumnDifferenceNotDuplicate('learning_hours');
    }

    public function test_25_diff_progress_not_duplicate(): void
    {
        $this->assertSingleColumnDifferenceNotDuplicate('progress');
    }

    public function test_26_diff_progress_status_not_duplicate(): void
    {
        $this->assertSingleColumnDifferenceNotDuplicate('progress_status');
    }

    public function test_27_diff_start_date_not_duplicate(): void
    {
        $this->assertSingleColumnDifferenceNotDuplicate('start_date');
    }

    public function test_28_diff_end_date_not_duplicate(): void
    {
        $this->assertSingleColumnDifferenceNotDuplicate('end_date');
    }

    public function test_29_diff_start_time_not_duplicate(): void
    {
        $this->assertSingleColumnDifferenceNotDuplicate('start_time');
    }

    public function test_30_diff_end_time_not_duplicate(): void
    {
        $this->assertSingleColumnDifferenceNotDuplicate('end_time');
    }

    public function test_31_diff_month_not_duplicate(): void
    {
        $this->assertSingleColumnDifferenceNotDuplicate('month');
    }

    public function test_32_diff_requirement_not_duplicate(): void
    {
        $this->assertSingleColumnDifferenceNotDuplicate('requirement');
    }

    public function test_33_diff_attendance_not_duplicate(): void
    {
        $this->assertSingleColumnDifferenceNotDuplicate('attendance');
    }

    public function test_34_diff_attendance_reason_not_duplicate(): void
    {
        $this->assertSingleColumnDifferenceNotDuplicate('attendance_reason');
    }

    public function test_35_diff_bond_status_not_duplicate(): void
    {
        $this->assertSingleColumnDifferenceNotDuplicate('bond_status');
    }

    public function test_36_diff_bond_duration_not_duplicate(): void
    {
        $this->assertSingleColumnDifferenceNotDuplicate('bond_duration');
    }

    public function test_37_diff_bond_end_date_not_duplicate(): void
    {
        $this->assertSingleColumnDifferenceNotDuplicate('bond_end_date');
    }

    public function test_38_diff_bond_amount_not_duplicate(): void
    {
        $this->assertSingleColumnDifferenceNotDuplicate('bond_amount');
    }

    public function test_39_diff_l1_program_not_duplicate(): void
    {
        $this->assertSingleColumnDifferenceNotDuplicate('l1_program');
    }

    public function test_40_diff_assessment_score_not_duplicate(): void
    {
        $this->assertSingleColumnDifferenceNotDuplicate('assessment_score');
    }

    public function test_41_diff_l2_verbal_not_duplicate(): void
    {
        $this->assertSingleColumnDifferenceNotDuplicate('l2_verbal');
    }

    public function test_42_diff_l3_numerical_not_duplicate(): void
    {
        $this->assertSingleColumnDifferenceNotDuplicate('l3_numerical');
    }

    public function test_43_diff_l3_verbal_not_duplicate(): void
    {
        $this->assertSingleColumnDifferenceNotDuplicate('l3_verbal');
    }

    public function test_44_diff_pic_not_duplicate(): void
    {
        $this->assertSingleColumnDifferenceNotDuplicate('pic');
    }

    /**
     * Scenario 45: Format difference in dates or numbers with identical canonical value -> DUPLICATE.
     */
    public function test_45_date_number_format_diff_same_canonical_is_duplicate(): void
    {
        $rowA = array_merge($this->getCanonicalBaseline(), [
            'start_date'       => '10/08/2026',
            'end_date'         => '10/08/2026',
            'course_duration'  => '09:00:00',
            'learning_hours'   => '8.00',
            'progress'         => '100.0',
            'assessment_score' => '85.0',
            'attendance'       => 'Yes',
            'month'            => 'August',
            'requirement'      => 'mandatory',
        ]);

        $rowB = array_merge($this->getCanonicalBaseline(), [
            'start_date'       => '2026-08-10',
            'end_date'         => '2026-08-10',
            'course_duration'  => '32400',
            'learning_hours'   => '8',
            'progress'         => '100',
            'assessment_score' => '85',
            'attendance'       => '1',
            'month'            => '8',
            'requirement'      => 'Mandatory',
        ]);

        $cmp = $this->validationService->compareFullRowValues($rowA, $rowB, false);

        $this->assertTrue($cmp['is_match'], 'Format variants with identical canonical normalization must match as duplicate');
        $this->assertEmpty($cmp['diff_fields']);
    }

    /**
     * Scenario 46: Exact duplicate against existing database record across all stored columns -> DUPLICATE.
     */
    public function test_46_exact_duplicate_in_database_is_duplicate(): void
    {
        $baseline = $this->getCanonicalBaseline();

        // Create database training and participant matching baseline
        $training = OfflineTraining::create([
            'course_name'         => $baseline['course_name'],
            'sub_course_name'     => $baseline['sub_course_name'],
            'type'                => $baseline['type'],
            'category'            => $baseline['category'],
            'level'               => $baseline['level'],
            'batch'               => $baseline['batch'],
            'main_classification' => $baseline['main_classification'],
            'sub_classification'  => $baseline['sub_classification'],
            'delivery_method'     => $baseline['delivery_method'],
            'sub_delivery_method' => $baseline['sub_delivery_method'],
            'provider_name'       => $baseline['provider_name'],
            'venue'               => $baseline['venue'],
            'instructor'          => $baseline['instructor'],
            'duration_seconds'    => (int) $baseline['course_duration'],
            'learning_hours'      => (float) $baseline['learning_hours'],
            'start_date'          => $baseline['start_date'],
            'finish_date'         => $baseline['end_date'],
            'start_time'          => $baseline['start_time'],
            'end_time'            => $baseline['end_time'],
            'requirement'         => $baseline['requirement'],
            'created_by'          => $this->adminUser->id,
        ]);

        $otu = OfflineTrainingUser::create([
            'offline_training_id' => $training->id,
            'user_id'             => $this->testEmployee->id,
            'employee_id'         => $baseline['employee_id'],
            'name'                => $this->testEmployee->name,
            'grade'               => $this->testEmployee->jobgrade,
            'job_position'        => $this->testEmployee->jobposition,
            'team'                => $this->testEmployee->team,
            'department'          => $this->testEmployee->department,
            'company'             => $this->testEmployee->company_name,
            'main_classification' => $baseline['main_classification'],
            'sub_classification'  => $baseline['sub_classification'],
            'learning_hours'      => (float) $baseline['learning_hours'],
            'progress'            => (float) $baseline['progress'],
            'start_date'          => $baseline['start_date'],
            'end_date'            => $baseline['end_date'],
            'start_time'          => $baseline['start_time'],
            'end_time'            => $baseline['end_time'],
            'attendance'          => 1,
            'attendance_reason'   => $baseline['attendance_reason'],
            'bond_status'         => $baseline['bond_status'],
            'bond_duration'       => 0,
            'bond_end_date'       => $baseline['bond_end_date'],
            'bond_amount_idr'     => 0,
            'l1_program'          => $baseline['l1_program'],
            'assessment_score'    => (float) $baseline['assessment_score'],
            'l2_numerical'        => (float) $baseline['assessment_score'],
            'l2_verbal'           => $baseline['l2_verbal'],
            'l3_numerical'        => (float) $baseline['l3_numerical'],
            'l3_verbal'           => $baseline['l3_verbal'],
            'pic'                 => $baseline['pic'],
        ]);

        // Compare baseline Excel data against stored database record
        $dbRecord = array_merge($baseline, [
            '_otu_id'              => $otu->id,
            '_offline_training_id' => $training->id,
            'progress_status'      => null, // Not stored in DB
            'month'                => null, // Not stored in DB
        ]);

        $cmp = $this->validationService->compareFullRowValues($baseline, $dbRecord, true);

        $this->assertTrue($cmp['is_match'], 'Matching database stored columns must evaluate as duplicate');
        $this->assertEmpty($cmp['diff_fields']);
        $this->assertArrayHasKey('progress_status', $cmp['unverified_fields']);
        $this->assertArrayHasKey('month', $cmp['unverified_fields']);

        // Verify preview endpoint correctly classifies as DATABASE_40_COLUMN_MATCH
        $file = $this->createPtkpExcel([
            array_merge(['row_no' => 1], $baseline),
        ]);
        $response = $this->actingAs($this->adminUser)
            ->post(route('manage.record.offline.import-ptkp.preview'), ['file' => $file]);
        $response->assertStatus(200);
        $json = $response->json();

        $this->assertEquals(1, $json['stats']['duplicate']);
        $this->assertEquals('DUPLICATE', $json['rows'][0]['status']);
        $this->assertEquals('database', $json['rows'][0]['duplicate_source']);
        $this->assertEquals('DATABASE_40_COLUMN_MATCH', $json['rows'][0]['duplicate_type']);
        $this->assertEquals($otu->id, $json['rows'][0]['matched_record_id']);
    }

    /**
     * Scenario 47: Exact duplicate in uploaded Excel file -> Row 1 VALID, Row 2 DUPLICATE.
     */
    public function test_47_exact_duplicate_in_uploaded_excel_is_duplicate(): void
    {
        $baseline = $this->getCanonicalBaseline();

        $file = $this->createPtkpExcel([
            array_merge(['row_no' => 1], $baseline),
            array_merge(['row_no' => 2], $baseline),
        ]);

        $response = $this->actingAs($this->adminUser)
            ->post(route('manage.record.offline.import-ptkp.preview'), ['file' => $file]);

        $response->assertStatus(200);
        $json = $response->json();

        $this->assertEquals(1, $json['stats']['valid']);
        $this->assertEquals(1, $json['stats']['duplicate']);
        $this->assertEquals(1, $json['importable_count']);

        $row1 = $json['rows'][0];
        $row2 = $json['rows'][1];

        $this->assertEquals('VALID', $row1['status']);
        $this->assertEquals('DUPLICATE', $row2['status']);
        $this->assertEquals('file', $row2['duplicate_source']);
        $this->assertEquals('FULL_42_COLUMN_MATCH', $row2['duplicate_type']);
        $this->assertEquals(2, $row2['original_excel_row']);
    }

    /**
     * Scenario 48: Legacy database record with null/empty value on a stored column while import file has value
     * -> MUST NOT BE CLAIMED AS A FULL ROW MATCH (remains VALID, not duplicate).
     */
    public function test_48_legacy_database_incomplete_fields_not_claimed_full_row_match(): void
    {
        $baseline = $this->getCanonicalBaseline();

        // Simulate legacy database record with null grade and bond_duration
        $legacyDbRecord = $baseline;
        $legacyDbRecord['grade'] = null; // Missing in legacy DB
        $legacyDbRecord['bond_duration'] = null; // Missing in legacy DB
        $legacyDbRecord['progress_status'] = null; // Not stored in DB
        $legacyDbRecord['month'] = null; // Not stored in DB

        $cmp = $this->validationService->compareFullRowValues($baseline, $legacyDbRecord, true);

        $this->assertFalse($cmp['is_match'], 'Legacy database record with incomplete/null fields must NOT match a filled Excel row');
        $this->assertArrayHasKey('grade', $cmp['diff_fields']);
        $this->assertEquals('database_null_unverified', $cmp['diff_fields']['grade']['reason']);
        $this->assertArrayHasKey('bond_duration', $cmp['diff_fields']);
        $this->assertEquals('database_null_unverified', $cmp['diff_fields']['bond_duration']['reason']);
    }

    /**
     * Helper to test that modifying exactly one column results in non-duplicate (is_match = false).
     */
    protected function assertSingleColumnDifferenceNotDuplicate(string $col): void
    {
        $baseline = $this->getCanonicalBaseline();
        $altValues = $this->getAlternativeColumnValues();

        $this->assertArrayHasKey($col, $altValues, "Missing alternative test value for column '{$col}'");

        $modified = $baseline;
        $modified[$col] = $altValues[$col];

        $cmp = $this->validationService->compareFullRowValues($baseline, $modified, false);

        $this->assertFalse(
            $cmp['is_match'],
            "Column '{$col}' difference ('{$baseline[$col]}' vs '{$modified[$col]}') MUST NOT evaluate as duplicate"
        );
        $this->assertArrayHasKey(
            $col,
            $cmp['diff_fields'],
            "Column '{$col}' must be present in diff_fields"
        );
    }

    /**
     * Verify excluded columns do NOT prevent duplicate detection when modified.
     * Columns: Provider, Sub Provider, Certificate fields.
     */
    public function test_excluded_columns_do_not_prevent_duplicate_match(): void
    {
        $rowA = array_merge($this->getCanonicalBaseline(), [
            'provider'                 => 'Internal',
            'sub_provider'             => 'Internal-Training Team',
            'certificate_availability' => 'Yes',
            'certificate_institution'  => 'BNSP',
            'certificate_issued_date'  => '2026-08-10',
            'certificate_expired_date' => '2029-08-10',
        ]);

        $rowB = array_merge($this->getCanonicalBaseline(), [
            'provider'                 => 'External',
            'sub_provider'             => 'External Vendor',
            'certificate_availability' => 'No',
            'certificate_institution'  => 'Kemnaker',
            'certificate_issued_date'  => '2025-01-01',
            'certificate_expired_date' => '2028-01-01',
        ]);

        $cmp = $this->validationService->compareFullRowValues($rowA, $rowB, false);

        $this->assertTrue(
            $cmp['is_match'],
            'Excluded columns (provider, sub_provider, certificate fields) must not prevent duplicate match'
        );
        $this->assertEmpty($cmp['diff_fields']);
    }
}
