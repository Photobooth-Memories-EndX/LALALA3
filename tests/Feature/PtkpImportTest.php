<?php

namespace Tests\Feature;

use App\Exports\Manage\PtkpImportTemplateExport;
use App\Models\OfflineTraining;
use App\Models\OfflineTrainingUser;
use App\Models\User;
use App\Services\Record\PtkpExcelSchema;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Http\UploadedFile;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Tests\TestCase;

class PtkpImportTest extends TestCase
{
    use DatabaseTransactions;

    protected User $adminUser;
    protected User $regularUser;
    protected User $targetEmployee;

    protected function setUp(): void
    {
        parent::setUp();

        $this->adminUser = User::whereIn('role', [User::ROLE_ADMIN, User::ROLE_SUPER_ADMIN])->first()
            ?? $this->createTestUser(['role' => User::ROLE_ADMIN]);

        $this->regularUser = User::where('role', User::ROLE_USER)->first()
            ?? $this->createTestUser(['role' => User::ROLE_USER]);

        $this->targetEmployee = $this->createTestUser([
            'id_emp'       => 'EMP_TEST_99',
            'name'         => 'Test Employee 99',
            'jobgrade'     => '3B',
            'jobposition'  => 'Senior Technician',
            'team'         => 'Electrical Team',
            'department'   => 'Plant Maintenance Dept',
            'company_name' => 'PT Krakatau Posco',
        ]);
    }

    protected function createTestUser(array $attrs = []): User
    {
        static $seq = 5000;
        $seq++;
        return User::create(array_merge([
            'name'         => 'Test User ' . $seq,
            'email'        => "testuser_{$seq}@example.com",
            'password'     => bcrypt('secret'),
            'id_emp'       => 'EMP_PTKP_' . $seq,
            'id_user'      => 'UID_' . $seq,
            'id_org_unit'  => 'OU_01',
            'team'         => 'Team Sample',
            'department'   => 'Dept Sample',
            'division'     => 'Division Sample',
            'sap_code'     => 'SAP_' . $seq,
            'ssohash'      => 'HASH_' . $seq,
            'role'         => User::ROLE_USER,
            'company_name' => 'PT Krakatau Posco',
        ], $attrs));
    }

    public function test_ptkp_import_page_requires_auth_and_permission(): void
    {
        $guestResponse = $this->get(route('manage.record.offline.import-ptkp'));
        $guestResponse->assertRedirect(route('loginpage'));

        $userResponse = $this->actingAs($this->regularUser)
            ->get(route('manage.record.offline.import-ptkp'));
        $this->assertTrue(in_array($userResponse->status(), [302, 403]));

        $adminResponse = $this->actingAs($this->adminUser)
            ->get(route('manage.record.offline.import-ptkp'));
        $adminResponse->assertStatus(200);
        $adminResponse->assertSee('Import Data PTKP');
    }

    public function test_ptkp_template_has_exact_48_columns_and_proper_structure(): void
    {
        $export = new PtkpImportTemplateExport();
        $spreadsheet = $export->buildSpreadsheet();

        $sheet = $spreadsheet->getSheetByName(PtkpExcelSchema::DATA_SHEET_TITLE);
        $this->assertNotNull($sheet);

        $cols = PtkpExcelSchema::columns();
        $this->assertCount(48, $cols);

        $expectedFinalHeaders = [
            1  => 'Status',
            2  => 'No',
            3  => 'Employee ID',
            4  => 'Employee Name',
            5  => 'Grade',
            6  => 'Job Position',
            7  => 'Team',
            8  => 'Department',
            9  => 'Company',
            10 => 'Course Title',
            11 => 'Sub Course Title',
            12 => 'Type',
            13 => 'Category',
            14 => 'Level',
            15 => 'Batch',
            16 => 'Main Classification',
            17 => 'Sub Classification',
            18 => 'Delivery Method',
            19 => 'Sub Delivery Method',
            20 => 'Provider',
            21 => 'Sub Provider',
            22 => 'Provider Name',
            23 => 'Venue',
            24 => 'Instructor',
            25 => 'Learning Hours',
            26 => '% Progress',
            27 => 'Start Date',
            28 => 'End Date',
            29 => 'Start Time',
            30 => 'End Time',
            31 => 'Record Month',
            32 => 'Requirement',
            33 => 'Attendance',
            34 => 'Attendance Reason',
            35 => 'Bond Status',
            36 => 'Bond Duration',
            37 => 'Bond End Date',
            38 => 'Bond Amount',
            39 => 'Score (L1) - Course',
            40 => 'Score (L2)',
            41 => 'L2 Status',
            42 => 'Score (L3)',
            43 => 'L3 Status',
            44 => 'Certificate Availability',
            45 => 'Certificate Institution',
            46 => 'Certificate Issued Date',
            47 => 'Certificate Expired Date',
            48 => 'Person in Charge (PIC)',
        ];

        foreach ($expectedFinalHeaders as $idx => $expectedHeader) {
            $this->assertEquals($expectedHeader, $cols[$idx]['header'], "Header mismatch at column {$idx}");
        }

        // PIC must appear only once at column 48
        $headerStrings = PtkpExcelSchema::headers();
        $picMatches = array_keys(array_filter($headerStrings, fn($h) => str_contains($h, 'PIC') || str_contains($h, 'Person in Charge')));
        $this->assertCount(1, $picMatches);
        $this->assertEquals(47, $picMatches[0]); // 0-indexed index 47 is column 48

        // Omitted columns must not exist
        $this->assertNotContains('Course Duration', $headerStrings);
        $this->assertNotContains('Progress Status', $headerStrings);
        $this->assertNotContains('Total Engagement Time', $headerStrings);
    }

    public function test_template_download_stream_response(): void
    {
        $response = $this->actingAs($this->adminUser)
            ->get(route('manage.record.offline.import-ptkp.template'));

        $response->assertStatus(200);
        $this->assertStringContainsString('spreadsheetml.sheet', $response->headers->get('content-type'));
    }

    public function test_valid_ptkp_upload_preview_extracts_five_identities_from_users(): void
    {
        $file = $this->createPtkpExcel([
            [
                'row_no'       => 1,
                'employee_id'  => 'EMP_TEST_99',
                'name'         => 'Name in Excel Should Be Ignored',
                'grade'        => 'Wrong Grade in Excel',
                'job_position' => 'Wrong Position in Excel',
                'team'         => 'Wrong Team in Excel',
                'department'   => 'Wrong Dept in Excel',
                'company'      => 'PT Krakatau Posco',
                'course_name'  => 'Safety Induction Training',
                'pic'          => 'HR Admin',
            ]
        ]);

        $response = $this->actingAs($this->adminUser)
            ->post(route('manage.record.offline.import-ptkp.preview'), ['file' => $file]);

        $response->assertStatus(200);
        $json = $response->json();

        $this->assertTrue($json['success']);
        $this->assertEquals(1, $json['stats']['valid']);
        $this->assertEquals(0, $json['stats']['invalid']);
        $this->assertEquals(1, $json['importable_count']);

        $row = $json['rows'][0];
        $this->assertEquals('VALID', $row['status']);
        $this->assertEquals('EMP_TEST_99', $row['employee_id']);

        $this->assertEquals('Test Employee 99', $row['name']);
        $this->assertEquals('3B', $row['data']['grade']);
        $this->assertEquals('Senior Technician', $row['data']['job_position']);
        $this->assertEquals('Electrical Team', $row['data']['team']);
        $this->assertEquals('Plant Maintenance Dept', $row['data']['department']);
    }

    public function test_invalid_status_when_employee_id_not_found(): void
    {
        $file = $this->createPtkpExcel([
            [
                'row_no'      => 1,
                'employee_id' => 'NON_EXISTENT_ID_999',
                'course_name' => 'Safety Induction Training',
            ]
        ]);

        $response = $this->actingAs($this->adminUser)
            ->post(route('manage.record.offline.import-ptkp.preview'), ['file' => $file]);

        $response->assertStatus(200);
        $json = $response->json();

        $this->assertEquals(1, $json['stats']['invalid']);
        $this->assertEquals(0, $json['importable_count']);
        $this->assertEquals('INVALID', $json['rows'][0]['status']);
        $this->assertFalse($json['can_confirm']);
    }

    public function test_invalid_status_when_course_title_is_empty(): void
    {
        $file = $this->createPtkpExcel([
            [
                'row_no'      => 1,
                'employee_id' => 'EMP_TEST_99',
                'course_name' => '',
            ]
        ]);

        $response = $this->actingAs($this->adminUser)
            ->post(route('manage.record.offline.import-ptkp.preview'), ['file' => $file]);

        $response->assertStatus(200);
        $json = $response->json();

        $this->assertEquals(1, $json['stats']['invalid']);
        $this->assertEquals('INVALID', $json['rows'][0]['status']);
    }

    public function test_company_mismatch_is_non_blocking_warning_and_importable(): void
    {
        $file = $this->createPtkpExcel([
            [
                'row_no'      => 1,
                'employee_id' => 'EMP_TEST_99',
                'company'     => 'PT Krakatau Tirta Industri',
                'course_name' => 'Water Treatment Safety',
                'pic'         => 'Admin PIC',
            ]
        ]);

        $response = $this->actingAs($this->adminUser)
            ->post(route('manage.record.offline.import-ptkp.preview'), ['file' => $file]);

        $response->assertStatus(200);
        $json = $response->json();

        $this->assertEquals(1, $json['stats']['invalid_company']);
        $this->assertEquals(1, $json['importable_count']);
        $this->assertTrue($json['can_confirm']);
        $this->assertEquals('INVALID COMPANY', $json['rows'][0]['status']);
        $this->assertTrue($json['rows'][0]['company_mismatch']);
        $this->assertEquals('PT Krakatau Tirta Industri', $json['rows'][0]['company']);
    }

    public function test_duplicate_in_same_file_is_skipped(): void
    {
        $file = $this->createPtkpExcel([
            [
                'row_no'      => 1,
                'employee_id' => 'EMP_TEST_99',
                'course_name' => 'Emergency Response Protocol',
                'batch'       => 'Batch A',
            ],
            [
                'row_no'      => 2,
                'employee_id' => 'EMP_TEST_99',
                'course_name' => 'Emergency Response Protocol',
                'batch'       => 'Batch A',
            ],
        ]);

        $response = $this->actingAs($this->adminUser)
            ->post(route('manage.record.offline.import-ptkp.preview'), ['file' => $file]);

        $response->assertStatus(200);
        $json = $response->json();

        $this->assertEquals(1, $json['stats']['valid']);
        $this->assertEquals(1, $json['stats']['duplicate']);
        $this->assertEquals(1, $json['importable_count']);
        $this->assertEquals('DUPLICATE', $json['rows'][1]['status']);
    }

    public function test_confirm_persists_records_and_cleans_up(): void
    {
        $file = $this->createPtkpExcel([
            [
                'row_no'          => 1,
                'employee_id'     => 'EMP_TEST_99',
                'course_name'     => 'Quality Control ISO 9001',
                'batch'           => 'Batch 2026-X',
                'pic'             => 'ISO Auditor',
                'learning_hours'  => 16.0,
            ]
        ]);

        $previewResp = $this->actingAs($this->adminUser)
            ->post(route('manage.record.offline.import-ptkp.preview'), ['file' => $file]);

        $token = $previewResp->json('token');
        $this->assertNotEmpty($token);

        $confirmResp = $this->actingAs($this->adminUser)
            ->post(route('manage.record.offline.import-ptkp.confirm'), ['token' => $token]);

        $confirmResp->assertStatus(200);
        $confirmJson = $confirmResp->json();

        $this->assertTrue($confirmJson['success']);
        $this->assertEquals(1, $confirmJson['result']['imported']);
        $this->assertEquals(1, $confirmJson['result']['trainings_created']);

        $participant = OfflineTrainingUser::where('employee_id', 'EMP_TEST_99')
            ->whereHas('training', fn($q) => $q->where('course_name', 'Quality Control ISO 9001'))
            ->first();

        $this->assertNotNull($participant);
        $this->assertEquals('Test Employee 99', $participant->name);
        $this->assertEquals('3B', $participant->grade);
        $this->assertEquals('Senior Technician', $participant->job_position);
        $this->assertEquals('Electrical Team', $participant->team);
        $this->assertEquals('Plant Maintenance Dept', $participant->department);
    }

    public function test_duplicate_against_existing_database_record_is_skipped(): void
    {
        $training = OfflineTraining::create([
            'course_name'  => 'Pre-existing PTKP Course',
            'batch'        => 'Batch 1',
            'start_date'   => '2026-08-10',
            'finish_date'  => '2026-08-10',
            'type'         => 'In-House',
            'venue'        => 'Room A',
            'created_by'   => $this->adminUser->id,
        ]);

        OfflineTrainingUser::create([
            'offline_training_id' => $training->id,
            'user_id'             => $this->targetEmployee->id,
            'employee_id'         => 'EMP_TEST_99',
            'name'                => 'Test Employee 99',
        ]);

        $file = $this->createPtkpExcel([
            [
                'row_no'      => 1,
                'employee_id' => 'EMP_TEST_99',
                'course_name' => 'Pre-existing PTKP Course',
                'batch'       => 'Batch 1',
                'start_date'  => '2026-08-10',
                'end_date'    => '2026-08-10',
                'type'        => 'In-House',
                'venue'       => 'Room A',
            ]
        ]);

        $response = $this->actingAs($this->adminUser)
            ->post(route('manage.record.offline.import-ptkp.preview'), ['file' => $file]);

        $response->assertStatus(200);
        $json = $response->json();

        $this->assertEquals(1, $json['stats']['duplicate']);
        $this->assertEquals(0, $json['importable_count']);
        $this->assertFalse($json['can_confirm']);
        $this->assertEquals('DUPLICATE', $json['rows'][0]['status']);
    }

    public function test_different_trainings_for_same_employee_are_not_duplicates(): void
    {
        $file = $this->createPtkpExcel([
            [
                'row_no'      => 1,
                'employee_id' => 'EMP_TEST_99',
                'course_name' => 'Course Alpha',
            ],
            [
                'row_no'      => 2,
                'employee_id' => 'EMP_TEST_99',
                'course_name' => 'Course Beta',
            ],
        ]);

        $response = $this->actingAs($this->adminUser)
            ->post(route('manage.record.offline.import-ptkp.preview'), ['file' => $file]);

        $response->assertStatus(200);
        $json = $response->json();

        $this->assertEquals(2, $json['stats']['valid']);
        $this->assertEquals(0, $json['stats']['duplicate']);
        $this->assertEquals(2, $json['importable_count']);
    }

    public function test_ambiguous_employee_id_is_marked_invalid(): void
    {
        $this->createTestUser(['id_emp' => 'EMP_AMB9', 'name' => 'First User']);
        $this->createTestUser(['id_emp' => 'EMP_AMB9', 'name' => 'Second User']);

        $file = $this->createPtkpExcel([
            [
                'row_no'      => 1,
                'employee_id' => 'EMP_AMB9',
                'course_name' => 'Test Course',
            ]
        ]);

        $response = $this->actingAs($this->adminUser)
            ->post(route('manage.record.offline.import-ptkp.preview'), ['file' => $file]);

        $response->assertStatus(200);
        $json = $response->json();

        $this->assertEquals(1, $json['stats']['invalid']);
        $this->assertEquals('INVALID', $json['rows'][0]['status']);
        $this->assertStringContainsString('multiple users', implode(' ', $json['rows'][0]['messages']));
    }

    public function test_dropdown_add_training_contains_three_choices(): void
    {
        $response = $this->actingAs($this->adminUser)
            ->get(route('manage.record.offline'));

        $response->assertStatus(200);
        $response->assertSee(route('manage.record.offline.create'));
        $response->assertSee(route('manage.record.offline.import-ptkp'));
        $response->assertSee(route('manage.record.offline.import-non-ptkp'));
        $response->assertSee('Import Data PTKP');
        $response->assertSee('Import Data Non PTKP');
    }

    public function test_extra_column_beyond_48_blocks_preview_confirmation(): void
    {
        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle(PtkpExcelSchema::DATA_SHEET_TITLE);

        $headers = PtkpExcelSchema::headers();
        foreach ($headers as $idx => $h) {
            $letter = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($idx + 1);
            $sheet->setCellValue("{$letter}1", $h);
        }
        $sheet->setCellValue('AW1', 'Extra Unexpected Column');
        $sheet->setCellValue('A2', 'Valid');
        $sheet->setCellValue('B2', 1);
        $sheet->setCellValue('C2', 'EMP_TEST_99');
        $sheet->setCellValue('J2', 'Course Title');

        $tempPath = tempnam(sys_get_temp_dir(), 'ptkp_extra_') . '.xlsx';
        (new Xlsx($spreadsheet))->save($tempPath);
        $file = new UploadedFile($tempPath, 'ptkp_extra.xlsx', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet', null, true);

        $response = $this->actingAs($this->adminUser)
            ->post(route('manage.record.offline.import-ptkp.preview'), ['file' => $file]);

        $response->assertStatus(200);
        $json = $response->json();
        $this->assertFalse($json['header_check']['can_proceed']);
        $this->assertFalse($json['can_confirm']);
        $this->assertGreaterThan(0, $json['header_check']['invalid_count']);
    }

    protected function createPtkpExcel(array $rows): UploadedFile
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
                } else {
                    $sheet->setCellValue("{$letter}{$rowIdx}", $col['example'] ?? '');
                }
            }
            $rowIdx++;
        }

        $tempPath = tempnam(sys_get_temp_dir(), 'ptkp_test_') . '.xlsx';
        $writer = new Xlsx($spreadsheet);
        $writer->save($tempPath);

        return new UploadedFile($tempPath, 'ptkp_test.xlsx', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet', null, true);
    }
}
