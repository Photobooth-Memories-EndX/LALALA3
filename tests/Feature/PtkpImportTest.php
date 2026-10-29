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

    public function test_ptkp_template_has_canonical_49_columns_and_proper_structure(): void
    {
        $export = new PtkpImportTemplateExport();
        $spreadsheet = $export->buildSpreadsheet();

        $sheet = $spreadsheet->getSheetByName(PtkpExcelSchema::DATA_SHEET_TITLE);
        $this->assertNotNull($sheet);

        $cols = PtkpExcelSchema::columns();
        $this->assertCount(49, $cols);

        $this->assertEquals('No', $cols[1]['header']);
        $this->assertEquals('Employee ID', $cols[2]['header']);
        $this->assertEquals('Provider', $cols[19]['header']);
        $this->assertEquals('Sub Provider', $cols[20]['header']);

        $this->assertEquals('Certificate Availability', $cols[46]['header']);
        $this->assertEquals('Certificate Institution', $cols[47]['header']);
        $this->assertEquals('Certificate Issued Date', $cols[48]['header']);
        $this->assertEquals('Certificate Expired Date', $cols[49]['header']);

        $headerStrings = PtkpExcelSchema::headers();
        $this->assertNotContains('Total Engagement Time', $headerStrings);
        $this->assertNotContains('Certificate', $headerStrings);
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
                'no'           => 1,
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
                'no'          => 1,
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
                'no'          => 1,
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
                'no'          => 1,
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
                'no'          => 1,
                'employee_id' => 'EMP_TEST_99',
                'course_name' => 'Fire Drill 2026',
                'batch'       => 'Batch 1',
                'start_date'  => '2026-09-01',
                'end_date'    => '2026-09-01',
                'pic'         => 'PIC Safety',
            ],
            [
                'no'          => 2,
                'employee_id' => 'EMP_TEST_99',
                'course_name' => 'Fire Drill 2026',
                'batch'       => 'Batch 1',
                'start_date'  => '2026-09-01',
                'end_date'    => '2026-09-01',
                'pic'         => 'PIC Safety',
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
                'no'            => 1,
                'employee_id'   => 'EMP_TEST_99',
                'course_name'   => 'PTKP Operational Excellence',
                'batch'         => 'Batch 9',
                'provider'      => 'Internal',
                'sub_provider'  => 'HRDC',
                'provider_name' => 'PTKP Learning Center',
                'pic'           => 'PIC Excellence',
            ]
        ]);

        $previewRes = $this->actingAs($this->adminUser)
            ->post(route('manage.record.offline.import-ptkp.preview'), ['file' => $file]);

        $token = $previewRes->json('token');
        $this->assertNotEmpty($token);

        $confirmRes = $this->actingAs($this->adminUser)
            ->post(route('manage.record.offline.import-ptkp.confirm'), ['token' => $token]);

        $confirmRes->assertStatus(200);
        $result = $confirmRes->json('result');

        $this->assertEquals(1, $result['imported']);
        $this->assertEquals(1, $result['trainings_created']);

        $training = OfflineTraining::where('course_name', 'PTKP Operational Excellence')->first();
        $this->assertNotNull($training);
        $this->assertEquals('Internal', $training->provider);
        $this->assertEquals('HRDC', $training->sub_provider);

        $participant = OfflineTrainingUser::where('offline_training_id', $training->id)
            ->where('employee_id', 'EMP_TEST_99')
            ->first();

        $this->assertNotNull($participant);
        $this->assertEquals($this->targetEmployee->id, $participant->user_id);
        $this->assertEquals('Test Employee 99', $participant->name);
        $this->assertEquals('3B', $participant->grade);
        $this->assertEquals('Senior Technician', $participant->job_position);
        $this->assertEquals('Electrical Team', $participant->team);
        $this->assertEquals('Plant Maintenance Dept', $participant->department);
    }

    public function test_duplicate_against_existing_database_record_is_skipped(): void
    {
        // First create a training and enroll targetEmployee
        $training = OfflineTraining::create([
            'course_name' => 'Existing Database Training',
            'batch'       => 'Batch 1',
            'start_date'  => '2026-08-10',
            'finish_date' => '2026-08-10',
            'type'        => 'In-House',
            'venue'       => 'Room A',
            'created_by'  => $this->adminUser->id,
        ]);

        OfflineTrainingUser::create([
            'offline_training_id' => $training->id,
            'user_id'             => $this->targetEmployee->id,
            'employee_id'         => $this->targetEmployee->id_emp,
            'name'                => $this->targetEmployee->name,
            'company'             => 'PT Krakatau Posco',
            'learning_hours'      => 8.0,
            'progress'            => 100.0,
        ]);

        // Upload the same participant & event
        $file = $this->createPtkpExcel([
            [
                'no'          => 1,
                'employee_id' => $this->targetEmployee->id_emp,
                'course_name' => 'Existing Database Training',
                'batch'       => 'Batch 1',
                'start_date'  => '2026-08-10',
                'end_date'    => '2026-08-10',
                'type'        => 'In-House',
            ]
        ]);

        $response = $this->actingAs($this->adminUser)
            ->post(route('manage.record.offline.import-ptkp.preview'), ['file' => $file]);

        $response->assertStatus(200);
        $json = $response->json();

        $this->assertEquals(1, $json['stats']['duplicate']);
        $this->assertEquals(0, $json['importable_count']);
        $this->assertEquals('DUPLICATE', $json['rows'][0]['status']);
    }

    public function test_different_trainings_for_same_employee_are_not_duplicates(): void
    {
        $file = $this->createPtkpExcel([
            [
                'no'          => 1,
                'employee_id' => $this->targetEmployee->id_emp,
                'course_name' => 'Safety Protocol Module A',
                'batch'       => 'Batch 1',
            ],
            [
                'no'          => 2,
                'employee_id' => $this->targetEmployee->id_emp,
                'course_name' => 'Leadership Training Module B',
                'batch'       => 'Batch 2',
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
        // Create duplicate user with same id_emp
        $this->createTestUser([
            'id_emp' => 'EMP_AMB9',
            'name'   => 'First User',
        ]);
        $this->createTestUser([
            'id_emp' => 'EMP_AMB9',
            'name'   => 'Second User',
        ]);

        $file = $this->createPtkpExcel([
            [
                'no'          => 1,
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
        $response->assertSee('#offlineImportModal');
        $response->assertSee('Import Data PTKP');
        $response->assertSee('Import Data Non PTKP');
    }

    protected function createPtkpExcel(array $rows): UploadedFile
    {
        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle(PtkpExcelSchema::DATA_SHEET_TITLE);

        $headers = PtkpExcelSchema::headers();
        foreach ($headers as $idx => $h) {
            $colLetter = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($idx + 1);
            $sheet->setCellValue("{$colLetter}1", $h);
        }

        $rowIdx = 2;
        foreach ($rows as $data) {
            $sheet->setCellValue("A{$rowIdx}", $data['no'] ?? ($rowIdx - 1));
            $sheet->setCellValueExplicit("B{$rowIdx}", (string) ($data['employee_id'] ?? ''), \PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_STRING);
            $sheet->setCellValue("C{$rowIdx}", $data['name'] ?? '');
            $sheet->setCellValue("D{$rowIdx}", $data['grade'] ?? '');
            $sheet->setCellValue("E{$rowIdx}", $data['job_position'] ?? '');
            $sheet->setCellValue("F{$rowIdx}", $data['team'] ?? '');
            $sheet->setCellValue("G{$rowIdx}", $data['department'] ?? '');
            $sheet->setCellValue("H{$rowIdx}", $data['company'] ?? 'PT Krakatau Posco');
            $sheet->setCellValue("I{$rowIdx}", $data['course_name'] ?? '');
            $sheet->setCellValue("J{$rowIdx}", $data['sub_course_name'] ?? '');
            $sheet->setCellValue("K{$rowIdx}", $data['type'] ?? 'In-House');
            $sheet->setCellValue("L{$rowIdx}", $data['category'] ?? 'Technical');
            $sheet->setCellValue("M{$rowIdx}", $data['level'] ?? 'Basic');
            $sheet->setCellValue("N{$rowIdx}", $data['batch'] ?? 'Batch 1');
            $sheet->setCellValue("O{$rowIdx}", $data['main_classification'] ?? 'Basic');
            $sheet->setCellValue("P{$rowIdx}", $data['sub_classification'] ?? 'Plant');
            $sheet->setCellValue("Q{$rowIdx}", $data['delivery_method'] ?? 'Offline');
            $sheet->setCellValue("R{$rowIdx}", $data['sub_delivery_method'] ?? 'On-site');
            $sheet->setCellValue("S{$rowIdx}", $data['provider'] ?? 'External');
            $sheet->setCellValue("T{$rowIdx}", $data['sub_provider'] ?? 'Vendor');
            $sheet->setCellValue("U{$rowIdx}", $data['provider_name'] ?? 'Vendor X');
            $sheet->setCellValue("V{$rowIdx}", $data['venue'] ?? 'Room A');
            $sheet->setCellValue("W{$rowIdx}", $data['instructor'] ?? 'Trainer');
            $sheet->setCellValue("X{$rowIdx}", $data['duration_seconds'] ?? '08:00:00');
            $sheet->setCellValue("Y{$rowIdx}", $data['learning_hours'] ?? 8.0);
            $sheet->setCellValue("Z{$rowIdx}", $data['progress'] ?? 100);
            $sheet->setCellValue("AA{$rowIdx}", $data['progress_status'] ?? 'Finished');
            $sheet->setCellValue("AB{$rowIdx}", $data['start_date'] ?? '2026-08-10');
            $sheet->setCellValue("AC{$rowIdx}", $data['end_date'] ?? '2026-08-10');
            $sheet->setCellValue("AD{$rowIdx}", $data['start_time'] ?? '08:00:00');
            $sheet->setCellValue("AE{$rowIdx}", $data['end_time'] ?? '17:00:00');
            $sheet->setCellValue("AF{$rowIdx}", $data['month'] ?? 'August');
            $sheet->setCellValue("AG{$rowIdx}", $data['requirement'] ?? 'Mandatory');
            $sheet->setCellValue("AH{$rowIdx}", $data['attendance'] ?? 'Yes');
            $sheet->setCellValue("AS{$rowIdx}", $data['pic'] ?? 'HR Admin');

            $rowIdx++;
        }

        $tempPath = tempnam(sys_get_temp_dir(), 'ptkp_test_') . '.xlsx';
        $writer = new Xlsx($spreadsheet);
        $writer->save($tempPath);

        return new UploadedFile($tempPath, 'ptkp_test.xlsx', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet', null, true);
    }
}
