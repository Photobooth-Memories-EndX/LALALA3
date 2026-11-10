<?php

namespace Tests\Feature;

use App\Models\OfflineTraining;
use App\Models\User;
use App\Services\Record\PtkpExcelSchema;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Http\UploadedFile;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Tests\TestCase;

class PtkpValidationClassificationStrictMatrixTest extends TestCase
{
    use DatabaseTransactions;

    protected User $adminUser;
    protected User $activeUser;
    protected User $softDeletedUser;
    protected OfflineTraining $activeTraining;
    protected OfflineTraining $softDeletedTraining;

    protected function setUp(): void
    {
        parent::setUp();

        // 1. Setup admin user
        $this->adminUser = User::whereIn('role', [User::ROLE_ADMIN, User::ROLE_SUPER_ADMIN])->first()
            ?? $this->createTestUser(['role' => User::ROLE_ADMIN]);

        // 2. Setup active target user
        $this->activeUser = $this->createTestUser([
            'id_emp'       => 'MTRX_01',
            'name'         => 'Budi Santoso',
            'company_name' => 'PT Krakatau Posco',
            'jobgrade'     => '4A',
            'jobposition'  => 'Senior Specialist',
            'team'         => 'Operation Team',
            'department'   => 'Steel Making Department',
        ]);

        // 3. Setup soft-deleted target user (Simulates Muhamad Reza Rachmat 620742)
        $this->softDeletedUser = $this->createTestUser([
            'id_emp'       => 'MTRX_02',
            'name'         => 'Reza Rachmat Resigned',
            'company_name' => 'PT Krakatau Posco',
            'jobgrade'     => '3B',
            'jobposition'  => 'Technician',
            'team'         => 'Rolling Team',
            'department'   => 'Hot Strip Mill',
        ]);
        $this->softDeletedUser->delete(); // Soft delete user

        // 4. Setup active offline training course
        $this->activeTraining = OfflineTraining::create([
            'title'       => 'Strict Matrix Safety Course ' . uniqid(),
            'company'     => 'PT Krakatau Posco',
            'description' => 'Mandatory Safety Training',
            'created_by'  => $this->adminUser->id,
        ]);

        // 5. Setup soft-deleted offline training course
        $this->softDeletedTraining = OfflineTraining::create([
            'title'       => 'Strict Matrix Archived Course ' . uniqid(),
            'company'     => 'PT Krakatau Posco',
            'description' => 'Archived Legacy Training',
            'created_by'  => $this->adminUser->id,
        ]);
        $this->softDeletedTraining->delete(); // Soft delete course
    }

    protected function createTestUser(array $attrs = []): User
    {
        static $seq = 6000;
        $seq++;
        return User::create(array_merge([
            'name'         => 'Test User ' . $seq,
            'email'        => "matrix_user_{$seq}@example.com",
            'password'     => bcrypt('secret'),
            'id_emp'       => 'P' . $seq,
            'id_user'      => 'UID_' . $seq,
            'id_org_unit'  => 'OU_01',
            'team'         => 'Team Sample',
            'department'   => 'Dept Sample',
            'division'     => 'Division Sample',
            'sap_code'     => 'SAP_' . $seq,
            'ssohash'      => 'HASH_' . $seq,
            'role'         => User::ROLE_USER,
            'company_name' => 'PT Krakatau Posco',
            'jobgrade'     => '3A',
            'jobposition'  => 'Technician',
        ], $attrs));
    }

    /**
     * Helper to create a PTKP spreadsheet with given rows.
     */
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
                    $val = $col['example'] ?? '';
                    if ($key === 'bond_amount' && ($val === '' || $val === null)) {
                        $val = 0;
                    }
                    $sheet->setCellValue("{$letter}{$rowIdx}", $val);
                }
            }
            $rowIdx++;
        }

        $tempPath = tempnam(sys_get_temp_dir(), 'ptkp_matrix_') . '.xlsx';
        $writer = new Xlsx($spreadsheet);
        $writer->save($tempPath);

        return new UploadedFile($tempPath, 'ptkp_matrix.xlsx', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet', null, true);
    }

    /**
     * Test Case 1: Valid Employee ID, Valid Course Title, No Warning -> VALID
     */
    public function test_case_01_valid_employee_and_valid_course_no_warning_evaluates_to_valid(): void
    {
        $file = $this->createPtkpExcel([
            [
                'row_no'      => 1,
                'employee_id' => $this->activeUser->id_emp,
                'company'     => 'PT Krakatau Posco',
                'course_name' => $this->activeTraining->title,
            ]
        ]);

        $response = $this->actingAs($this->adminUser)
            ->post(route('manage.record.offline.import-ptkp.preview'), ['file' => $file]);

        $response->assertStatus(200);
        $json = $response->json();

        $this->assertEquals(1, $json['stats']['valid']);
        $this->assertEquals(0, $json['stats']['warning']);
        $this->assertEquals(0, $json['stats']['invalid']);
        $this->assertEquals('VALID', $json['rows'][0]['status']);
        $this->assertFalse($json['rows'][0]['is_invalid_id']);
        $this->assertFalse($json['rows'][0]['is_invalid_course']);
    }

    /**
     * Test Case 2: Valid Employee ID, Valid Course Title, Attendance Reason Empty -> WARNING
     */
    public function test_case_02_valid_employee_and_valid_course_attendance_reason_empty_evaluates_to_warning(): void
    {
        $file = $this->createPtkpExcel([
            [
                'row_no'            => 1,
                'employee_id'       => $this->activeUser->id_emp,
                'company'           => 'PT Krakatau Posco',
                'course_name'       => $this->activeTraining->title,
                'attendance_reason' => '',
            ]
        ]);

        $response = $this->actingAs($this->adminUser)
            ->post(route('manage.record.offline.import-ptkp.preview'), ['file' => $file]);

        $response->assertStatus(200);
        $json = $response->json();

        $this->assertEquals(1, $json['stats']['warning']);
        $this->assertEquals(0, $json['stats']['invalid']);
        $this->assertEquals('WARNING', $json['rows'][0]['status']);
        $this->assertFalse($json['rows'][0]['is_invalid_id']);
        $this->assertFalse($json['rows'][0]['is_invalid_course']);
        $this->assertEquals('WARNING: Attendance Reason is empty', $json['rows'][0]['warning_message']);
    }

    /**
     * Test Case 3: Valid Employee ID, Valid Course Title, Assessment Score Empty -> WARNING
     */
    public function test_case_03_valid_employee_and_valid_course_assessment_score_empty_evaluates_to_warning(): void
    {
        $file = $this->createPtkpExcel([
            [
                'row_no'           => 1,
                'employee_id'      => $this->activeUser->id_emp,
                'company'          => 'PT Krakatau Posco',
                'course_name'      => $this->activeTraining->title,
                'assessment_score' => '',
            ]
        ]);

        $response = $this->actingAs($this->adminUser)
            ->post(route('manage.record.offline.import-ptkp.preview'), ['file' => $file]);

        $response->assertStatus(200);
        $json = $response->json();

        $this->assertEquals(1, $json['stats']['warning']);
        $this->assertEquals(0, $json['stats']['invalid']);
        $this->assertEquals('WARNING', $json['rows'][0]['status']);
        $this->assertFalse($json['rows'][0]['is_invalid_id']);
        $this->assertFalse($json['rows'][0]['is_invalid_course']);
        $this->assertStringContainsString('Score (L2) is empty', $json['rows'][0]['warning_message']);
    }

    /**
     * Test Case 4: Valid Employee ID, Valid Course Title, L2/L3 Status Empty -> WARNING
     */
    public function test_case_04_valid_employee_and_valid_course_l2_l3_status_empty_evaluates_to_warning(): void
    {
        $file = $this->createPtkpExcel([
            [
                'row_no'      => 1,
                'employee_id' => $this->activeUser->id_emp,
                'company'     => 'PT Krakatau Posco',
                'course_name' => $this->activeTraining->title,
                'l2_verbal'   => '',
                'l3_verbal'   => '',
            ]
        ]);

        $response = $this->actingAs($this->adminUser)
            ->post(route('manage.record.offline.import-ptkp.preview'), ['file' => $file]);

        $response->assertStatus(200);
        $json = $response->json();

        $this->assertEquals(1, $json['stats']['warning']);
        $this->assertEquals(0, $json['stats']['invalid']);
        $this->assertEquals('WARNING', $json['rows'][0]['status']);
        $this->assertFalse($json['rows'][0]['is_invalid_id']);
        $this->assertFalse($json['rows'][0]['is_invalid_course']);
        $this->assertStringContainsString('L2 Status is empty', $json['rows'][0]['warning_message']);
        $this->assertStringContainsString('L3 Status is empty', $json['rows'][0]['warning_message']);
    }

    /**
     * Test Case 5: Invalid Employee ID (not in LMS), Valid Course Title, No Warning -> INVALID ID
     */
    public function test_case_05_invalid_employee_not_found_evaluates_to_invalid_id(): void
    {
        $file = $this->createPtkpExcel([
            [
                'row_no'      => 1,
                'employee_id' => '999999_NON_EXISTENT',
                'company'     => 'PT Krakatau Posco',
                'course_name' => $this->activeTraining->title,
            ]
        ]);

        $response = $this->actingAs($this->adminUser)
            ->post(route('manage.record.offline.import-ptkp.preview'), ['file' => $file]);

        $response->assertStatus(200);
        $json = $response->json();

        $this->assertEquals(1, $json['stats']['invalid']);
        $this->assertEquals(1, $json['stats']['invalid_id_count']);
        $this->assertEquals(0, $json['stats']['invalid_course_count']);
        $this->assertEquals('INVALID', $json['rows'][0]['status']);
        $this->assertTrue($json['rows'][0]['is_invalid_id']);
        $this->assertFalse($json['rows'][0]['is_invalid_course']);
        $this->assertEquals('INVALID_ID', $json['rows'][0]['invalid_type']);
    }

    /**
     * Test Case 6: Valid Employee ID, Empty Course Title, No Warning -> INVALID COURSE TITLE
     */
    public function test_case_06_empty_course_title_evaluates_to_invalid_course_title(): void
    {
        $file = $this->createPtkpExcel([
            [
                'row_no'      => 1,
                'employee_id' => $this->activeUser->id_emp,
                'company'     => 'PT Krakatau Posco',
                'course_name' => '',
            ]
        ]);

        $response = $this->actingAs($this->adminUser)
            ->post(route('manage.record.offline.import-ptkp.preview'), ['file' => $file]);

        $response->assertStatus(200);
        $json = $response->json();

        $this->assertEquals(1, $json['stats']['invalid']);
        $this->assertEquals(0, $json['stats']['invalid_id_count']);
        $this->assertEquals(1, $json['stats']['invalid_course_count']);
        $this->assertEquals('INVALID', $json['rows'][0]['status']);
        $this->assertFalse($json['rows'][0]['is_invalid_id']);
        $this->assertTrue($json['rows'][0]['is_invalid_course']);
        $this->assertEquals('INVALID_COURSE', $json['rows'][0]['invalid_type']);
    }

    /**
     * Test Case 7: Empty Employee ID, Valid Course Title, Attendance Reason Empty -> INVALID ID with Warning preserved
     */
    public function test_case_07_empty_employee_id_with_attendance_reason_empty_evaluates_to_invalid_id_preserving_warning(): void
    {
        $file = $this->createPtkpExcel([
            [
                'row_no'            => 1,
                'employee_id'       => '',
                'company'           => 'PT Krakatau Posco',
                'course_name'       => $this->activeTraining->title,
                'attendance_reason' => '',
            ]
        ]);

        $response = $this->actingAs($this->adminUser)
            ->post(route('manage.record.offline.import-ptkp.preview'), ['file' => $file]);

        $response->assertStatus(200);
        $json = $response->json();

        $this->assertEquals(1, $json['stats']['invalid']);
        $this->assertEquals(1, $json['stats']['invalid_id_count']);
        $this->assertEquals('INVALID', $json['rows'][0]['status']);
        $this->assertTrue($json['rows'][0]['is_invalid_id']);
        $this->assertFalse($json['rows'][0]['is_invalid_course']);
        $this->assertEquals('INVALID_ID', $json['rows'][0]['invalid_type']);

        // Check messages preserve both error and warning
        $msgStr = implode(' ', $json['rows'][0]['messages']);
        $this->assertStringContainsString('Employee ID is required', $msgStr);
        $this->assertStringContainsString('Attendance Reason is empty', $msgStr);
    }

    /**
     * Test Case 8: Valid Employee ID, Empty Course Title, Attendance Reason Empty -> INVALID COURSE TITLE with Warning preserved
     */
    public function test_case_08_empty_course_title_with_attendance_reason_empty_evaluates_to_invalid_course_preserving_warning(): void
    {
        $file = $this->createPtkpExcel([
            [
                'row_no'            => 1,
                'employee_id'       => $this->activeUser->id_emp,
                'company'           => 'PT Krakatau Posco',
                'course_name'       => '',
                'attendance_reason' => '',
            ]
        ]);

        $response = $this->actingAs($this->adminUser)
            ->post(route('manage.record.offline.import-ptkp.preview'), ['file' => $file]);

        $response->assertStatus(200);
        $json = $response->json();

        $this->assertEquals(1, $json['stats']['invalid']);
        $this->assertEquals(1, $json['stats']['invalid_course_count']);
        $this->assertEquals('INVALID', $json['rows'][0]['status']);
        $this->assertFalse($json['rows'][0]['is_invalid_id']);
        $this->assertTrue($json['rows'][0]['is_invalid_course']);
        $this->assertEquals('INVALID_COURSE', $json['rows'][0]['invalid_type']);

        // Check messages preserve both error and warning
        $msgStr = implode(' ', $json['rows'][0]['messages']);
        $this->assertStringContainsString('Course Title is required', $msgStr);
        $this->assertStringContainsString('Attendance Reason is empty', $msgStr);
    }

    /**
     * Test Case 9: Duplicate in Same File -> DUPLICATE
     */
    public function test_case_09_duplicate_in_file_evaluates_to_duplicate(): void
    {
        $rowTemplate = [
            'employee_id' => $this->activeUser->id_emp,
            'company'     => 'PT Krakatau Posco',
            'course_name' => $this->activeTraining->title,
        ];

        $file = $this->createPtkpExcel([
            array_merge($rowTemplate, ['row_no' => 1]),
            array_merge($rowTemplate, ['row_no' => 2]),
        ]);

        $response = $this->actingAs($this->adminUser)
            ->post(route('manage.record.offline.import-ptkp.preview'), ['file' => $file]);

        $response->assertStatus(200);
        $json = $response->json();

        $this->assertEquals(1, $json['stats']['valid']);
        $this->assertEquals(1, $json['stats']['duplicate']);
        $this->assertEquals('VALID', $json['rows'][0]['status']);
        $this->assertEquals('DUPLICATE', $json['rows'][1]['status']);
    }

    /**
     * Test Case 10: Invalid Company Mismatch -> INVALID COMPANY
     */
    public function test_case_10_company_mismatch_evaluates_to_invalid_company(): void
    {
        $file = $this->createPtkpExcel([
            [
                'row_no'      => 1,
                'employee_id' => $this->activeUser->id_emp,
                'company'     => 'PT Krakatau Steel (Mismatched)',
                'course_name' => $this->activeTraining->title,
            ]
        ]);

        $response = $this->actingAs($this->adminUser)
            ->post(route('manage.record.offline.import-ptkp.preview'), ['file' => $file]);

        $response->assertStatus(200);
        $json = $response->json();

        $this->assertEquals(1, $json['stats']['invalid_company']);
        $this->assertEquals('INVALID COMPANY', $json['rows'][0]['status']);
        $this->assertTrue($json['rows'][0]['company_mismatch']);
        $this->assertFalse($json['rows'][0]['is_invalid_id']);
        $this->assertFalse($json['rows'][0]['is_invalid_course']);
    }

    /**
     * Test Case 11: Soft Deleted User -> Found via withTrashed(), Valid -> VALID
     */
    public function test_case_11_soft_deleted_user_evaluates_to_valid(): void
    {
        $file = $this->createPtkpExcel([
            [
                'row_no'      => 1,
                'employee_id' => $this->softDeletedUser->id_emp,
                'company'     => 'PT Krakatau Posco',
                'course_name' => $this->activeTraining->title,
            ]
        ]);

        $response = $this->actingAs($this->adminUser)
            ->post(route('manage.record.offline.import-ptkp.preview'), ['file' => $file]);

        $response->assertStatus(200);
        $json = $response->json();

        $this->assertEquals(1, $json['stats']['valid']);
        $this->assertEquals(0, $json['stats']['invalid']);
        $this->assertEquals('VALID', $json['rows'][0]['status']);
        $this->assertFalse($json['rows'][0]['is_invalid_id']);
        $this->assertFalse($json['rows'][0]['is_invalid_course']);
        $this->assertTrue($json['rows'][0]['data']['is_resigned']);
    }

    /**
     * Test Case 12: Soft Deleted Course -> Found via withTrashed(), Valid -> VALID
     */
    public function test_case_12_soft_deleted_course_evaluates_to_valid(): void
    {
        $file = $this->createPtkpExcel([
            [
                'row_no'      => 1,
                'employee_id' => $this->activeUser->id_emp,
                'company'     => 'PT Krakatau Posco',
                'course_name' => $this->softDeletedTraining->title,
            ]
        ]);

        $response = $this->actingAs($this->adminUser)
            ->post(route('manage.record.offline.import-ptkp.preview'), ['file' => $file]);

        $response->assertStatus(200);
        $json = $response->json();

        $this->assertEquals(1, $json['stats']['valid']);
        $this->assertEquals(0, $json['stats']['invalid']);
        $this->assertEquals('VALID', $json['rows'][0]['status']);
        $this->assertFalse($json['rows'][0]['is_invalid_id']);
        $this->assertFalse($json['rows'][0]['is_invalid_course']);
        $this->assertTrue($json['rows'][0]['data']['is_course_deleted']);
    }

    /**
     * Test Case 13: Combination - Both Employee ID and Course Title Invalid -> INVALID_ID_AND_COURSE
     */
    public function test_case_13_both_employee_id_and_course_title_invalid(): void
    {
        $file = $this->createPtkpExcel([
            [
                'row_no'      => 1,
                'employee_id' => 'NON_EXISTENT_ID',
                'company'     => 'PT Krakatau Posco',
                'course_name' => '',
            ]
        ]);

        $response = $this->actingAs($this->adminUser)
            ->post(route('manage.record.offline.import-ptkp.preview'), ['file' => $file]);

        $response->assertStatus(200);
        $json = $response->json();

        $this->assertEquals(1, $json['stats']['invalid']);
        $this->assertEquals(1, $json['stats']['invalid_id_count']);
        $this->assertEquals(1, $json['stats']['invalid_course_count']);
        $this->assertEquals('INVALID', $json['rows'][0]['status']);
        $this->assertTrue($json['rows'][0]['is_invalid_id']);
        $this->assertTrue($json['rows'][0]['is_invalid_course']);
        $this->assertEquals('INVALID_ID_AND_COURSE', $json['rows'][0]['invalid_type']);
    }

    /**
     * Test Case 14: Simulation of Excel Row 374 (Employee ID '16' not in DB + L2/L3 warnings)
     * Demonstrates that INVALID ID is solely due to Employee ID '16' being unmatched,
     * while L2 Status, Score (L3), and L3 Status remain strictly non-blocking warnings.
     */
    public function test_case_14_row_374_simulation_invalid_employee_id_16_with_l2_l3_warnings(): void
    {
        $file = $this->createPtkpExcel([
            [
                'row_no'           => 374,
                'employee_id'      => '16', // Not found in users.id_emp
                'name'             => 'ADRIYAN MAULANA RIZKY',
                'company'          => 'PT Krakatau Posco',
                'course_name'      => 'New Employee Orientation for Steel Industry Technology Class (OJT PIPB)',
                'l2_verbal'        => '', // L2 Status empty
                'l3_numerical'     => '', // Score (L3) empty
                'l3_verbal'        => '', // L3 Status empty
            ]
        ]);

        $response = $this->actingAs($this->adminUser)
            ->post(route('manage.record.offline.import-ptkp.preview'), ['file' => $file]);

        $response->assertStatus(200);
        $json = $response->json();
        $row = $json['rows'][0];

        // Primary status must be INVALID ID
        $this->assertEquals('INVALID', $row['status']);
        $this->assertTrue($row['is_invalid_id']);
        $this->assertFalse($row['is_invalid_course']);
        $this->assertEquals('INVALID_ID', $row['invalid_type']);

        // Blocking errors must contain Employee ID error and NEVER warnings
        $this->assertNotEmpty($row['id_errors']);
        $this->assertStringContainsString("Employee ID '16' was not found in LMS users database", $row['id_errors'][0]);
        $this->assertEmpty($row['course_errors']);

        // Blocking errors array must NOT contain L2/L3 warning texts
        foreach ($row['blocking_errors'] as $err) {
            $this->assertStringNotContainsString('L2 Status', $err);
            $this->assertStringNotContainsString('Score (L3)', $err);
            $this->assertStringNotContainsString('L3 Status', $err);
        }

        // Warnings must be completely preserved in empty_warning_fields and warning_message
        $this->assertContains('L2 Status', $row['empty_warning_fields']);
        $this->assertContains('Score (L3)', $row['empty_warning_fields']);
        $this->assertContains('L3 Status', $row['empty_warning_fields']);
        $this->assertStringContainsString('L2 Status is empty', $row['warning_message']);
        $this->assertStringContainsString('Score (L3) is empty', $row['warning_message']);
        $this->assertStringContainsString('L3 Status is empty', $row['warning_message']);
    }

    /**
     * Test Case 15: Valid Employee + Valid Course with L2 Status, Score (L3), L3 Status Empty -> WARNING (Never INVALID ID)
     */
    public function test_case_15_valid_user_with_l2_score_l3_l3_status_empty_evaluates_to_warning_never_invalid_id(): void
    {
        $file = $this->createPtkpExcel([
            [
                'row_no'           => 1,
                'employee_id'      => $this->activeUser->id_emp,
                'name'             => $this->activeUser->name,
                'company'          => 'PT Krakatau Posco',
                'course_name'      => $this->activeTraining->title,
                'l2_verbal'        => '', // L2 Status empty
                'l3_numerical'     => '', // Score (L3) empty
                'l3_verbal'        => '', // L3 Status empty
            ]
        ]);

        $response = $this->actingAs($this->adminUser)
            ->post(route('manage.record.offline.import-ptkp.preview'), ['file' => $file]);

        $response->assertStatus(200);
        $json = $response->json();
        $row = $json['rows'][0];

        // Status MUST be WARNING, never INVALID
        $this->assertEquals(0, $json['stats']['invalid']);
        $this->assertEquals(1, $json['stats']['warning']);
        $this->assertEquals('WARNING', $row['status']);
        $this->assertFalse($row['is_invalid_id']);
        $this->assertFalse($row['is_invalid_course']);
        $this->assertNull($row['invalid_type']);
        $this->assertEmpty($row['id_errors']);
        $this->assertEmpty($row['course_errors']);
        $this->assertEmpty($row['blocking_errors']);

        // Warnings must be present
        $this->assertContains('L2 Status', $row['empty_warning_fields']);
        $this->assertContains('Score (L3)', $row['empty_warning_fields']);
        $this->assertContains('L3 Status', $row['empty_warning_fields']);
    }

    /**
     * Test Case 16: Kosong Employee ID + Kosong Course Title -> INVALID ID & COURSE TITLE
     */
    public function test_case_16_empty_id_and_empty_course_evaluates_to_invalid_id_and_course(): void
    {
        $file = $this->createPtkpExcel([
            [
                'row_no'      => 1,
                'employee_id' => '',
                'course_name' => '',
            ]
        ]);

        $response = $this->actingAs($this->adminUser)
            ->post(route('manage.record.offline.import-ptkp.preview'), ['file' => $file]);

        $response->assertStatus(200);
        $json = $response->json();
        $row = $json['rows'][0];

        $this->assertEquals(1, $json['stats']['invalid']);
        $this->assertEquals(1, $json['stats']['invalid_id_count']);
        $this->assertEquals(1, $json['stats']['invalid_course_count']);
        $this->assertEquals('INVALID', $row['status']);
        $this->assertTrue($row['is_invalid_id']);
        $this->assertTrue($row['is_invalid_course']);
        $this->assertEquals('INVALID_ID_AND_COURSE', $row['invalid_type']);
    }

    /**
     * Test Case 17: Row Isolation — Warning in Row 1 must NEVER leak to Row 2 (which is strictly VALID)
     */
    public function test_case_17_row_isolation_warning_in_first_row_does_not_leak_to_second_valid_row(): void
    {
        $secondUser = $this->createTestUser([
            'id_emp'       => 'MTRX_03',
            'name'         => 'Clean Second User',
            'company_name' => 'PT Krakatau Posco',
        ]);

        $file = $this->createPtkpExcel([
            // Row 1: Has L2 Status, Score L3, L3 Status empty -> WARNING
            [
                'row_no'           => 1,
                'employee_id'      => $this->activeUser->id_emp,
                'name'             => $this->activeUser->name,
                'company'          => 'PT Krakatau Posco',
                'course_name'      => $this->activeTraining->title,
                'l2_verbal'        => '',
                'l3_numerical'     => '',
                'l3_verbal'        => '',
            ],
            // Row 2: Completely filled -> VALID (Must NOT inherit Row 1 warnings!)
            [
                'row_no'           => 2,
                'employee_id'      => $secondUser->id_emp,
                'name'             => $secondUser->name,
                'company'          => 'PT Krakatau Posco',
                'course_name'      => $this->activeTraining->title,
                'l2_verbal'        => 'Passed',
                'l3_numerical'     => 85,
                'l3_verbal'        => 'Implemented',
            ]
        ]);

        $response = $this->actingAs($this->adminUser)
            ->post(route('manage.record.offline.import-ptkp.preview'), ['file' => $file]);

        $response->assertStatus(200);
        $json = $response->json();

        $this->assertEquals(2, $json['total_rows']);
        $this->assertEquals(1, $json['stats']['warning']);
        $this->assertEquals(1, $json['stats']['valid']);

        $row1 = $json['rows'][0];
        $row2 = $json['rows'][1];

        // Row 1 asserts
        $this->assertEquals('WARNING', $row1['status']);
        $this->assertNotEmpty($row1['empty_warning_fields']);

        // Row 2 asserts (Strict Isolation: Must be clean VALID)
        $this->assertEquals('VALID', $row2['status']);
        $this->assertFalse($row2['is_blocking']);
        $this->assertFalse($row2['is_invalid_id']);
        $this->assertFalse($row2['is_invalid_course']);
        $this->assertEmpty($row2['warnings'], 'Row 2 must have no warnings');
        $this->assertEmpty($row2['warning_reasons'], 'Row 2 must have no warning reasons');
        $this->assertEmpty($row2['empty_warning_fields'], 'Row 2 must have no empty warning fields');
        $this->assertEmpty($row2['id_errors']);
        $this->assertEmpty($row2['course_errors']);
        $this->assertEmpty($row2['blocking_errors']);
        $this->assertEquals(['Row is valid and ready for import.'], $row2['messages']);
    }

    /**
     * Test Case 18: Re-upload — Consecutive uploads of the same file produce identical, clean results without stale metadata
     */
    public function test_case_18_reupload_same_file_maintains_consistent_clean_validation(): void
    {
        $rowsData = [
            [
                'row_no'      => 1,
                'employee_id' => $this->activeUser->id_emp,
                'company'     => 'PT Krakatau Posco',
                'course_name' => $this->activeTraining->title,
            ],
        ];

        // First upload
        $file1 = $this->createPtkpExcel($rowsData);
        $res1 = $this->actingAs($this->adminUser)
            ->post(route('manage.record.offline.import-ptkp.preview'), ['file' => $file1]);
        $res1->assertStatus(200);
        $json1 = $res1->json();

        // Second upload (Re-upload)
        $file2 = $this->createPtkpExcel($rowsData);
        $res2 = $this->actingAs($this->adminUser)
            ->post(route('manage.record.offline.import-ptkp.preview'), ['file' => $file2]);
        $res2->assertStatus(200);
        $json2 = $res2->json();

        $this->assertEquals($json1['stats'], $json2['stats']);
        $this->assertEquals($json1['rows'][0]['status'], $json2['rows'][0]['status']);
        $this->assertEquals($json1['rows'][0]['messages'], $json2['rows'][0]['messages']);
        $this->assertEquals($json1['rows'][0]['empty_warning_fields'], $json2['rows'][0]['empty_warning_fields']);
    }

    /**
     * Test Case 19: Row 3109 Simulation — Certificate Dates with 'On-process' are strictly NON-BLOCKING (Never INVALID)
     */
    public function test_case_19_row_3109_simulation_certificate_date_on_process_evaluates_to_valid_or_warning_never_invalid(): void
    {
        $faridUser = User::where('id_emp', '112041')->first() ?? $this->createTestUser([
            'id_emp'       => '112041',
            'name'         => 'FARID FADZLULLAH',
            'company_name' => 'PT Krakatau Posco',
        ]);

        $k3Course = OfflineTraining::create([
            'title'       => 'Teknisi K3 Ruang Terbatas ' . uniqid(),
            'company'     => 'PT Krakatau Posco',
            'created_by'  => $this->adminUser->id,
        ]);

        $file = $this->createPtkpExcel([
            [
                'row_no'                   => 3109,
                'employee_id'              => $faridUser->id_emp,
                'name'                     => $faridUser->name,
                'company'                  => 'PT Krakatau Posco',
                'course_name'              => $k3Course->title,
                'certificate_availability' => 'Yes',
                'certificate_institution'  => 'BNSP / Kemnaker',
                'certificate_issued_date'  => 'On-process',
                'certificate_expired_date' => 'On-process',
            ]
        ]);

        $response = $this->actingAs($this->adminUser)
            ->post(route('manage.record.offline.import-ptkp.preview'), ['file' => $file]);

        $response->assertStatus(200);
        $json = $response->json();
        $row = $json['rows'][0];

        // Must NEVER be INVALID!
        $this->assertEquals(0, $json['stats']['invalid']);
        $this->assertEquals(0, $json['stats']['invalid_id_count']);
        $this->assertEquals(0, $json['stats']['invalid_course_count']);
        $this->assertEquals(1, $json['stats']['importable_count']);

        $this->assertNotEquals('INVALID', $row['status']);
        $this->assertFalse($row['is_blocking']);
        $this->assertFalse($row['is_invalid_id']);
        $this->assertFalse($row['is_invalid_course']);
        $this->assertNull($row['invalid_type']);
        $this->assertEmpty($row['id_errors']);
        $this->assertEmpty($row['course_errors']);
        $this->assertEmpty($row['blocking_errors']);

        // Certificate displays should preserve 'On-process'
        $this->assertEquals('On-process', $row['data']['certificate_issued_date_display']);
        $this->assertEquals('On-process', $row['data']['certificate_expired_date_display']);
    }

    /**
     * Test Case 20: Invalid Certificate Date Format evaluates to WARNING, never INVALID
     */
    public function test_case_20_invalid_certificate_date_format_evaluates_to_warning_never_invalid(): void
    {
        $file = $this->createPtkpExcel([
            [
                'row_no'                   => 1,
                'employee_id'              => $this->activeUser->id_emp,
                'company'                  => 'PT Krakatau Posco',
                'course_name'              => $this->activeTraining->title,
                'certificate_issued_date'  => '99/99/9999', // Invalid date format
                'certificate_expired_date' => 'not-a-date',  // Invalid date format
            ]
        ]);

        $response = $this->actingAs($this->adminUser)
            ->post(route('manage.record.offline.import-ptkp.preview'), ['file' => $file]);

        $response->assertStatus(200);
        $json = $response->json();
        $row = $json['rows'][0];

        // Must be WARNING, never INVALID
        $this->assertEquals(0, $json['stats']['invalid']);
        $this->assertEquals(1, $json['stats']['warning']);
        $this->assertEquals(1, $json['stats']['importable_count']);
        $this->assertEquals('WARNING', $row['status']);
        $this->assertFalse($row['is_blocking']);
        $this->assertFalse($row['is_invalid_id']);
        $this->assertFalse($row['is_invalid_course']);
        $this->assertEmpty($row['blocking_errors']);
    }

    /**
     * Test Case 21: Invalid Start Time and Date Sequence evaluate to WARNING, never INVALID
     */
    public function test_case_21_invalid_start_time_and_date_sequence_evaluates_to_warning_never_invalid(): void
    {
        $file = $this->createPtkpExcel([
            [
                'row_no'       => 1,
                'employee_id'  => $this->activeUser->id_emp,
                'company'      => 'PT Krakatau Posco',
                'course_name'  => $this->activeTraining->title,
                'start_date'   => '20/08/2026', // Start date after end date
                'end_date'     => '10/08/2026',
                'start_time'   => '28:99:99',    // Invalid time
            ]
        ]);

        $response = $this->actingAs($this->adminUser)
            ->post(route('manage.record.offline.import-ptkp.preview'), ['file' => $file]);

        $response->assertStatus(200);
        $json = $response->json();
        $row = $json['rows'][0];

        $this->assertEquals(0, $json['stats']['invalid']);
        $this->assertEquals(1, $json['stats']['warning']);
        $this->assertEquals(1, $json['stats']['importable_count']);
        $this->assertEquals('WARNING', $row['status']);
        $this->assertFalse($row['is_blocking']);
        $this->assertFalse($row['is_invalid_id']);
        $this->assertFalse($row['is_invalid_course']);
        $this->assertEmpty($row['blocking_errors']);
    }

    /**
     * Test Case 22: All supporting optional fields empty while Employee ID & Course Title are valid -> WARNING, never INVALID
     */
    public function test_case_22_all_supporting_fields_empty_evaluates_to_warning_never_invalid(): void
    {
        $file = $this->createPtkpExcel([
            [
                'row_no'              => 1,
                'employee_id'         => $this->activeUser->id_emp,
                'company'             => 'PT Krakatau Posco',
                'course_name'         => $this->activeTraining->title,
                'type'                => '',
                'category'            => '',
                'level'               => '',
                'batch'               => '',
                'main_classification' => '',
                'sub_classification'  => '',
                'delivery_method'     => '',
                'sub_delivery_method' => '',
                'provider_name'       => '',
                'venue'               => '',
                'instructor'          => '',
                'learning_hours'      => '',
                'progress'            => '',
                'start_date'          => '',
                'end_date'            => '',
                'start_time'          => '',
                'end_time'            => '',
                'month'               => '',
                'requirement'         => '',
                'attendance'          => '',
                'attendance_reason'   => '',
                'bond_status'         => '',
                'bond_duration'       => '',
                'bond_end_date'       => '',
                'bond_amount'         => '',
                'l1_program'          => '',
                'assessment_score'    => '',
                'l2_verbal'           => '',
                'l3_numerical'        => '',
                'l3_verbal'           => '',
                'pic'                 => '',
            ]
        ]);

        $response = $this->actingAs($this->adminUser)
            ->post(route('manage.record.offline.import-ptkp.preview'), ['file' => $file]);

        $response->assertStatus(200);
        $json = $response->json();
        $row = $json['rows'][0];

        $this->assertEquals(0, $json['stats']['invalid']);
        $this->assertEquals(1, $json['stats']['warning']);
        $this->assertEquals(1, $json['stats']['importable_count']);
        $this->assertEquals('WARNING', $row['status']);
        $this->assertFalse($row['is_blocking']);
        $this->assertFalse($row['is_invalid_id']);
        $this->assertFalse($row['is_invalid_course']);
        $this->assertEmpty($row['blocking_errors']);
    }

    /**
     * Test Case 23: Employee valid + Course valid + Company Excel Non PTKP -> INVALID COMPANY
     */
    public function test_case_23_employee_valid_course_valid_company_non_ptkp_evaluates_to_invalid_company(): void
    {
        $file = $this->createPtkpExcel([
            [
                'row_no'              => 1,
                'employee_id'         => $this->activeUser->id_emp,
                'company'             => 'Non PTKP',
                'course_name'         => $this->activeTraining->title,
                'level'               => '', // Has empty warning field to ensure INVALID COMPANY takes precedence!
            ]
        ]);

        $response = $this->actingAs($this->adminUser)
            ->post(route('manage.record.offline.import-ptkp.preview'), ['file' => $file]);

        $response->assertStatus(200);
        $json = $response->json();
        $row = $json['rows'][0];

        $this->assertEquals('INVALID COMPANY', $row['status']);
        $this->assertFalse($row['is_blocking']);
        $this->assertFalse($row['is_invalid_id']);
        $this->assertFalse($row['is_invalid_course']);
        $this->assertTrue($row['is_invalid_company']);
        $this->assertTrue($row['company_mismatch']);
        $this->assertEquals('INVALID_COMPANY', $row['invalid_type']);
        $this->assertEquals(1, $json['stats']['invalid_company']);
        $this->assertEquals(1, $json['stats']['invalid_company_count']);
        $this->assertEquals(0, $json['stats']['invalid']);
        $this->assertEquals(0, $json['stats']['warning']);
        $this->assertEquals(1, $json['stats']['importable_count']);
        $this->assertTrue($json['can_confirm']);
    }

    /**
     * Test Case 24: Employee valid + Course valid + Company matches DB -> VALID
     */
    public function test_case_24_employee_valid_course_valid_company_matches_db_evaluates_to_valid(): void
    {
        $file = $this->createPtkpExcel([
            [
                'row_no'      => 1,
                'employee_id' => $this->activeUser->id_emp,
                'company'     => 'PT Krakatau Posco',
                'course_name' => $this->activeTraining->title,
            ]
        ]);

        $response = $this->actingAs($this->adminUser)
            ->post(route('manage.record.offline.import-ptkp.preview'), ['file' => $file]);

        $response->assertStatus(200);
        $json = $response->json();
        $row = $json['rows'][0];

        $this->assertEquals('VALID', $row['status']);
        $this->assertFalse($row['is_blocking']);
        $this->assertFalse($row['is_invalid_company']);
        $this->assertFalse($row['company_mismatch']);
        $this->assertEquals(1, $json['stats']['valid']);
        $this->assertEquals(0, $json['stats']['invalid_company']);
        $this->assertEquals(1, $json['stats']['importable_count']);
    }

    /**
     * Test Case 25: Employee valid + Course valid + Company differs -> INVALID COMPANY
     */
    public function test_case_25_employee_valid_course_valid_company_differs_evaluates_to_invalid_company(): void
    {
        $file = $this->createPtkpExcel([
            [
                'row_no'      => 1,
                'employee_id' => $this->activeUser->id_emp,
                'company'     => 'PT External Corp Alpha',
                'course_name' => $this->activeTraining->title,
            ]
        ]);

        $response = $this->actingAs($this->adminUser)
            ->post(route('manage.record.offline.import-ptkp.preview'), ['file' => $file]);

        $response->assertStatus(200);
        $json = $response->json();
        $row = $json['rows'][0];

        $this->assertEquals('INVALID COMPANY', $row['status']);
        $this->assertFalse($row['is_blocking']);
        $this->assertTrue($row['is_invalid_company']);
        $this->assertTrue($row['company_mismatch']);
        $this->assertEquals(1, $json['stats']['invalid_company']);
        $this->assertEquals(1, $json['stats']['importable_count']);
    }

    /**
     * Test Case 26: Employee invalid + Company Non PTKP -> INVALID ID and INVALID COMPANY
     */
    public function test_case_26_employee_invalid_company_non_ptkp_evaluates_to_invalid_id_and_invalid_company(): void
    {
        $file = $this->createPtkpExcel([
            [
                'row_no'      => 1,
                'employee_id' => 'NON_EXISTENT_EMP_99999',
                'company'     => 'Non PTKP',
                'course_name' => $this->activeTraining->title,
            ]
        ]);

        $response = $this->actingAs($this->adminUser)
            ->post(route('manage.record.offline.import-ptkp.preview'), ['file' => $file]);

        $response->assertStatus(200);
        $json = $response->json();
        $row = $json['rows'][0];

        $this->assertEquals('INVALID', $row['status']);
        $this->assertTrue($row['is_blocking']);
        $this->assertTrue($row['is_invalid_id']);
        $this->assertFalse($row['is_invalid_course']);
        $this->assertTrue($row['is_invalid_company']);
        $this->assertTrue($row['company_mismatch']);
        $this->assertEquals('INVALID_ID_AND_COMPANY', $row['invalid_type']);
        $this->assertEquals(1, $json['stats']['invalid']);
        $this->assertEquals(1, $json['stats']['invalid_id_count']);
        $this->assertEquals(0, $json['stats']['invalid_company']); // No double counting in KPI
    }

    /**
     * Test Case 27: Course invalid + Company Non PTKP -> INVALID COURSE TITLE and INVALID COMPANY
     */
    public function test_case_27_course_invalid_company_non_ptkp_evaluates_to_invalid_course_title_and_invalid_company(): void
    {
        $file = $this->createPtkpExcel([
            [
                'row_no'      => 1,
                'employee_id' => $this->activeUser->id_emp,
                'company'     => 'Non PTKP',
                'course_name' => '', // Empty Course Title
            ]
        ]);

        $response = $this->actingAs($this->adminUser)
            ->post(route('manage.record.offline.import-ptkp.preview'), ['file' => $file]);

        $response->assertStatus(200);
        $json = $response->json();
        $row = $json['rows'][0];

        $this->assertEquals('INVALID', $row['status']);
        $this->assertTrue($row['is_blocking']);
        $this->assertFalse($row['is_invalid_id']);
        $this->assertTrue($row['is_invalid_course']);
        $this->assertTrue($row['is_invalid_company']);
        $this->assertTrue($row['company_mismatch']);
        $this->assertEquals('INVALID_COURSE_AND_COMPANY', $row['invalid_type']);
        $this->assertEquals(1, $json['stats']['invalid']);
        $this->assertEquals(1, $json['stats']['invalid_course_count']);
        $this->assertEquals(0, $json['stats']['invalid_company']); // No double counting in KPI
    }

    /**
     * Test Case 28: Employee and Course valid + Company matches + warning fields empty -> WARNING
     */
    public function test_case_28_employee_and_course_valid_company_matches_warning_empty_evaluates_to_warning(): void
    {
        $file = $this->createPtkpExcel([
            [
                'row_no'              => 1,
                'employee_id'         => $this->activeUser->id_emp,
                'company'             => 'PT Krakatau Posco',
                'course_name'         => $this->activeTraining->title,
                'attendance_reason'   => '',
                'l2_verbal'           => '',
            ]
        ]);

        $response = $this->actingAs($this->adminUser)
            ->post(route('manage.record.offline.import-ptkp.preview'), ['file' => $file]);

        $response->assertStatus(200);
        $json = $response->json();
        $row = $json['rows'][0];

        $this->assertEquals('WARNING', $row['status']);
        $this->assertFalse($row['is_blocking']);
        $this->assertFalse($row['is_invalid_id']);
        $this->assertFalse($row['is_invalid_course']);
        $this->assertFalse($row['is_invalid_company']);
        $this->assertFalse($row['company_mismatch']);
        $this->assertEquals(1, $json['stats']['warning']);
        $this->assertEquals(0, $json['stats']['invalid_company']);
        $this->assertEquals(1, $json['stats']['importable_count']);
    }

    /**
     * Test Case 29: Duplicate record with Company Non PTKP -> Preserves duplicate priority with invalid company metadata
     */
    public function test_case_29_duplicate_record_with_company_non_ptkp_preserves_duplicate_priority_with_invalid_company_metadata(): void
    {
        $file = $this->createPtkpExcel([
            [
                'row_no'      => 1,
                'employee_id' => $this->activeUser->id_emp,
                'company'     => 'Non PTKP',
                'course_name' => $this->activeTraining->title,
            ],
            [
                'row_no'      => 2,
                'employee_id' => $this->activeUser->id_emp,
                'company'     => 'Non PTKP',
                'course_name' => $this->activeTraining->title,
            ]
        ]);

        $response = $this->actingAs($this->adminUser)
            ->post(route('manage.record.offline.import-ptkp.preview'), ['file' => $file]);

        $response->assertStatus(200);
        $json = $response->json();

        // Row 1 is first encountered, evaluated to INVALID COMPANY
        $row1 = $json['rows'][0];
        $this->assertEquals('INVALID COMPANY', $row1['status']);
        $this->assertTrue($row1['is_invalid_company']);

        // Row 2 is exact duplicate of row 1, evaluated to DUPLICATE with preserved invalid company metadata
        $row2 = $json['rows'][1];
        $this->assertEquals('DUPLICATE', $row2['status']);
        $this->assertTrue($row2['is_blocking']);
        $this->assertTrue($row2['is_invalid_company']);
        $this->assertTrue($row2['company_mismatch']);
        $this->assertEquals(1, $json['stats']['duplicate']);
        $this->assertEquals(1, $json['stats']['invalid_company']);
    }

    /**
     * Test Case 30: PT ABC Indonesia & PT XYZ evaluate to INVALID COMPANY
     */
    public function test_case_30_pt_abc_indonesia_and_pt_xyz_evaluate_to_invalid_company(): void
    {
        $file = $this->createPtkpExcel([
            [
                'row_no'      => 1,
                'employee_id' => $this->activeUser->id_emp,
                'company'     => 'PT ABC Indonesia',
                'course_name' => $this->activeTraining->title,
            ],
            [
                'row_no'      => 2,
                'employee_id' => $this->activeUser->id_emp,
                'company'     => 'PT XYZ',
                'course_name' => 'Secondary Course ' . uniqid(),
            ]
        ]);

        $response = $this->actingAs($this->adminUser)
            ->post(route('manage.record.offline.import-ptkp.preview'), ['file' => $file]);

        $response->assertStatus(200);
        $json = $response->json();

        $this->assertEquals(2, $json['stats']['invalid_company']);
        $this->assertEquals('INVALID COMPANY', $json['rows'][0]['status']);
        $this->assertTrue($json['rows'][0]['is_invalid_company']);
        $this->assertEquals('INVALID COMPANY', $json['rows'][1]['status']);
        $this->assertTrue($json['rows'][1]['is_invalid_company']);
    }

    /**
     * Test Case 31: PT Krakatau Steel evaluates to INVALID COMPANY
     */
    public function test_case_31_pt_krakatau_steel_evaluates_to_invalid_company(): void
    {
        $file = $this->createPtkpExcel([
            [
                'row_no'      => 1,
                'employee_id' => $this->activeUser->id_emp,
                'company'     => 'PT Krakatau Steel',
                'course_name' => $this->activeTraining->title,
            ]
        ]);

        $response = $this->actingAs($this->adminUser)
            ->post(route('manage.record.offline.import-ptkp.preview'), ['file' => $file]);

        $response->assertStatus(200);
        $json = $response->json();
        $row = $json['rows'][0];

        $this->assertEquals('INVALID COMPANY', $row['status']);
        $this->assertTrue($row['is_invalid_company']);
        $this->assertTrue($row['company_mismatch']);
        $this->assertEquals(1, $json['stats']['invalid_company']);
    }

    /**
     * Test Case 32: Company empty or NULL evaluates to INVALID COMPANY
     */
    public function test_case_32_company_empty_or_null_evaluates_to_invalid_company(): void
    {
        $file = $this->createPtkpExcel([
            [
                'row_no'      => 1,
                'employee_id' => $this->activeUser->id_emp,
                'company'     => '', // Empty string
                'course_name' => $this->activeTraining->title,
            ],
            [
                'row_no'      => 2,
                'employee_id' => $this->activeUser->id_emp,
                'company'     => null, // NULL
                'course_name' => 'Secondary Course ' . uniqid(),
            ]
        ]);

        $response = $this->actingAs($this->adminUser)
            ->post(route('manage.record.offline.import-ptkp.preview'), ['file' => $file]);

        $response->assertStatus(200);
        $json = $response->json();

        $this->assertEquals(2, $json['stats']['invalid_company']);
        $this->assertEquals('INVALID COMPANY', $json['rows'][0]['status']);
        $this->assertTrue($json['rows'][0]['is_invalid_company']);
        $this->assertEquals('INVALID COMPANY', $json['rows'][1]['status']);
        $this->assertTrue($json['rows'][1]['is_invalid_company']);
    }

    /**
     * Test Case 33: Company normalization for case, whitespace, and punctuation evaluates to VALID
     */
    public function test_case_33_company_case_whitespace_and_punctuation_normalization_evaluates_to_valid(): void
    {
        $file = $this->createPtkpExcel([
            [
                'row_no'      => 1,
                'employee_id' => $this->activeUser->id_emp,
                'company'     => 'pt krakatau posco', // Lowercase
                'course_name' => $this->activeTraining->title,
            ],
            [
                'row_no'      => 2,
                'employee_id' => $this->activeUser->id_emp,
                'company'     => '  PT   Krakatau   Posco  ', // Extra whitespace & padded
                'course_name' => 'Course Variant 2 ' . uniqid(),
            ],
            [
                'row_no'      => 3,
                'employee_id' => $this->activeUser->id_emp,
                'company'     => 'PT. Krakatau Posco', // Punctuation dot
                'course_name' => 'Course Variant 3 ' . uniqid(),
            ]
        ]);

        $response = $this->actingAs($this->adminUser)
            ->post(route('manage.record.offline.import-ptkp.preview'), ['file' => $file]);

        $response->assertStatus(200);
        $json = $response->json();

        $this->assertEquals(0, $json['stats']['invalid_company']);
        $this->assertEquals(3, $json['stats']['valid']);
        $this->assertEquals('VALID', $json['rows'][0]['status']);
        $this->assertFalse($json['rows'][0]['is_invalid_company']);
        $this->assertEquals('VALID', $json['rows'][1]['status']);
        $this->assertFalse($json['rows'][1]['is_invalid_company']);
        $this->assertEquals('VALID', $json['rows'][2]['status']);
        $this->assertFalse($json['rows'][2]['is_invalid_company']);
    }

    /**
     * Test Case 34: Company invalid + empty warning fields -> INVALID COMPANY, warning fields preserved
     */
    public function test_case_34_company_invalid_with_empty_warning_fields_evaluates_to_invalid_company_preserving_warnings(): void
    {
        $file = $this->createPtkpExcel([
            [
                'row_no'              => 1,
                'employee_id'         => $this->activeUser->id_emp,
                'company'             => 'PT Perusahaan Lainnya',
                'course_name'         => $this->activeTraining->title,
                'level'               => '',
                'attendance_reason'   => '',
                'bond_status'         => '',
            ]
        ]);

        $response = $this->actingAs($this->adminUser)
            ->post(route('manage.record.offline.import-ptkp.preview'), ['file' => $file]);

        $response->assertStatus(200);
        $json = $response->json();
        $row = $json['rows'][0];

        $this->assertEquals('INVALID COMPANY', $row['status']);
        $this->assertTrue($row['is_invalid_company']);
        $this->assertEquals(1, $json['stats']['invalid_company']);
        $this->assertEquals(0, $json['stats']['warning']);
        $this->assertNotEmpty($row['empty_warning_fields']);
        $this->assertContains('Level', $row['empty_warning_fields']);
        $this->assertContains('Attendance Reason', $row['empty_warning_fields']);
    }

    /**
     * Test Case 35: Direct unit matrix test for isWhitelistedCompany helper
     */
    public function test_case_35_is_whitelisted_company_helper_unit_matrix(): void
    {
        // Valid Whitelist variations
        $this->assertTrue(\App\Services\OfflineTraining\PtkpValidationService::isWhitelistedCompany('PT Krakatau Posco'));
        $this->assertTrue(\App\Services\OfflineTraining\PtkpValidationService::isWhitelistedCompany('pt krakatau posco'));
        $this->assertTrue(\App\Services\OfflineTraining\PtkpValidationService::isWhitelistedCompany('PT. Krakatau Posco'));
        $this->assertTrue(\App\Services\OfflineTraining\PtkpValidationService::isWhitelistedCompany('  PT   Krakatau   Posco  '));
        $this->assertTrue(\App\Services\OfflineTraining\PtkpValidationService::isWhitelistedCompany('PT. KRAKATAU POSCO'));

        // Invalid Company names
        $this->assertFalse(\App\Services\OfflineTraining\PtkpValidationService::isWhitelistedCompany('Non PTKP'));
        $this->assertFalse(\App\Services\OfflineTraining\PtkpValidationService::isWhitelistedCompany('PT ABC Indonesia'));
        $this->assertFalse(\App\Services\OfflineTraining\PtkpValidationService::isWhitelistedCompany('PT XYZ'));
        $this->assertFalse(\App\Services\OfflineTraining\PtkpValidationService::isWhitelistedCompany('PT Krakatau Steel'));
        $this->assertFalse(\App\Services\OfflineTraining\PtkpValidationService::isWhitelistedCompany(''));
        $this->assertFalse(\App\Services\OfflineTraining\PtkpValidationService::isWhitelistedCompany(null));
        $this->assertFalse(\App\Services\OfflineTraining\PtkpValidationService::isWhitelistedCompany('   '));
        $this->assertFalse(\App\Services\OfflineTraining\PtkpValidationService::isWhitelistedCompany('-'));
        $this->assertFalse(\App\Services\OfflineTraining\PtkpValidationService::isWhitelistedCompany('Perusahaan Lainnya'));
    }
}
