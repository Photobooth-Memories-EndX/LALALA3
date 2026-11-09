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
        $adminResponse->assertSee('Import PTKP Data');
        $adminResponse->assertSee('Back to Offline Records');
        $adminResponse->assertDontSee('Import Status Guide');
        $adminResponse->assertDontSee('Import Status Legend');
        $adminResponse->assertDontSee('The record will be imported into the system.');
        $adminResponse->assertSee('INVALID COMPANY');
    }

    public function test_ptkp_template_has_exact_46_columns_and_proper_structure(): void
    {
        $export = new PtkpImportTemplateExport();
        $spreadsheet = $export->buildSpreadsheet();

        $sheet = $spreadsheet->getSheetByName(PtkpExcelSchema::DATA_SHEET_TITLE);
        $this->assertNotNull($sheet);

        $cols = PtkpExcelSchema::columns();
        $this->assertCount(46, $cols);

        $expectedFinalHeaders = [
            1  => 'Employee ID',
            2  => 'Employee Name',
            3  => 'Grade',
            4  => 'Job Position',
            5  => 'Team',
            6  => 'Department',
            7  => 'Company',
            8  => 'Course Title',
            9  => 'Sub Course Title',
            10 => 'Type',
            11 => 'Category',
            12 => 'Level',
            13 => 'Batch',
            14 => 'Main Classification',
            15 => 'Sub Classification',
            16 => 'Delivery Method',
            17 => 'Sub Delivery Method',
            18 => 'Provider',
            19 => 'Sub Provider',
            20 => 'Provider Name',
            21 => 'Venue',
            22 => 'Instructor',
            23 => 'Learning Hours',
            24 => '% Progress',
            25 => 'Start Date',
            26 => 'End Date',
            27 => 'Start Time',
            28 => 'End Time',
            29 => 'Record Month',
            30 => 'Requirement',
            31 => 'Attendance',
            32 => 'Attendance Reason',
            33 => 'Bond Status',
            34 => 'Bond Duration',
            35 => 'Bond End Date',
            36 => 'Bond Amount',
            37 => 'Score (L1) - Course',
            38 => 'Score (L2)',
            39 => 'L2 Status',
            40 => 'Score (L3)',
            41 => 'L3 Status',
            42 => 'Certificate Availability',
            43 => 'Certificate Institution',
            44 => 'Certificate Issued Date',
            45 => 'Certificate Expired Date',
            46 => 'Person in Charge (PIC)',
        ];

        foreach ($expectedFinalHeaders as $idx => $expectedHeader) {
            $this->assertEquals($expectedHeader, $cols[$idx]['header'], "Header mismatch at column {$idx}");
        }

        // PIC must appear only once at column 46
        $headerStrings = PtkpExcelSchema::headers();
        $picMatches = array_keys(array_filter($headerStrings, fn($h) => str_contains($h, 'PIC') || str_contains($h, 'Person in Charge')));
        $this->assertCount(1, $picMatches);
        $this->assertEquals(45, $picMatches[0]); // 0-indexed index 45 is column 46

        // Omitted columns must not exist
        $this->assertNotContains('Course Duration', $headerStrings);
        $this->assertNotContains('Progress Status', $headerStrings);
        $this->assertNotContains('Total Engagement Time', $headerStrings);
        $this->assertNotContains('Status', $headerStrings);
        $this->assertNotContains('No', $headerStrings);

        // Verify single-row header structure with NO column
        $this->assertSame('NO', $sheet->getCell('A1')->getValue());
        $this->assertSame('Employee ID', $sheet->getCell('B1')->getValue());
        $this->assertSame('Course Title', $sheet->getCell('I1')->getValue());
        $this->assertSame('Person in Charge (PIC)', $sheet->getCell('AU1')->getValue());

        // Verify example and sequence numbers
        $this->assertEquals(1, $sheet->getCell('A2')->getValue());
        $this->assertEquals(2, $sheet->getCell('A3')->getValue());
        $this->assertEquals(6, $sheet->getCell('A7')->getValue());

        // Freeze pane must be B2
        $this->assertSame('B2', $sheet->getFreezePane());

        // Auto filter must cover A1:AU1
        $this->assertSame('A1:AU1', $sheet->getAutoFilter()->getRange());

        // 0 merged cell ranges (group headers completely removed)
        $this->assertCount(0, $sheet->getMergeCells());
    }

    public function test_template_download_stream_response(): void
    {
        $response = $this->actingAs($this->adminUser)
            ->get(route('manage.record.offline.import-ptkp.template'));

        $response->assertStatus(200);
        $this->assertStringContainsString('application/vnd.openxmlformats-officedocument.spreadsheetml.sheet', $response->headers->get('content-type'));
        $this->assertStringContainsString('PTKP_OWL_Import_Template.xlsx', $response->headers->get('content-disposition'));
        $this->assertStringContainsString('attachment', $response->headers->get('content-disposition'));

        $content = $response->streamedContent();
        $this->assertNotEmpty($content);

        // Binary signature must begin exactly with PK (0x50, 0x4B, 0x03, 0x04) at offset 0 (no BOM, whitespace, or notices)
        $this->assertSame("PK\x03\x04", substr($content, 0, 4), 'Downloaded XLSX must start with valid ZIP magic bytes (PK\x03\x04) at offset 0.');

        // Test 2 — ZIP Integrity
        $tempPath = tempnam(sys_get_temp_dir(), 'ptkp_dl_test_') . '.xlsx';
        file_put_contents($tempPath, $content);

        try {
            $zip = new \ZipArchive();
            $zipRes = $zip->open($tempPath, \ZipArchive::CHECKCONS);
            $this->assertTrue($zipRes === true, "ZipArchive::open failed with error code: {$zipRes}");

            // Test 3 — XLSX Structure
            $requiredEntries = ['[Content_Types].xml', 'xl/workbook.xml', 'xl/worksheets/sheet1.xml'];
            foreach ($requiredEntries as $entry) {
                $entryXml = $zip->getFromName($entry);
                $this->assertNotFalse($entryXml, "Missing required XLSX entry: {$entry}");
                $xmlObj = @simplexml_load_string($entryXml);
                $this->assertNotFalse($xmlObj, "Invalid XML structure in entry: {$entry}");
            }
            $zip->close();

            // Test 4 — PhpSpreadsheet Load
            $spreadsheet = \PhpOffice\PhpSpreadsheet\IOFactory::load($tempPath);
            $this->assertNotNull($spreadsheet);
            $sheet = $spreadsheet->getSheetByName(PtkpExcelSchema::DATA_SHEET_TITLE);
            $this->assertNotNull($sheet);
            $this->assertSame('NO', $sheet->getCell('A1')->getValue());
            $this->assertSame('Employee ID', $sheet->getCell('B1')->getValue());
            $this->assertSame('Person in Charge (PIC)', $sheet->getCell('AU1')->getValue());

            // Test 5 — Import Compatibility
            $uploaded = new UploadedFile($tempPath, 'PTKP_OWL_Import_Template.xlsx', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet', null, true);
            $importService = app(\App\Services\OfflineTraining\PtkpImportService::class);
            $meta = $importService->storeTemporaryFile($uploaded, $this->adminUser->id);
            $preview = $importService->parseAndPreview($meta['token'], $this->adminUser->id);

            $this->assertSame('single_row', $preview['format']);
            $this->assertTrue($preview['header_check']['can_proceed']);
            $this->assertSame(47, $preview['header_check']['valid_count']);
            $this->assertSame(0, $preview['header_check']['invalid_count']);
        } finally {
            if (file_exists($tempPath)) {
                @unlink($tempPath);
            }
        }
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
            'course_name'         => 'Pre-existing PTKP Course',
            'sub_course_name'     => 'Switchgear Troubleshooting Module',
            'type'                => 'In-House',
            'category'            => 'Technical Competency',
            'level'               => 'Intermediate',
            'batch'               => 'Batch 1',
            'main_classification' => 'Basic & Common Duties',
            'sub_classification'  => 'Plant Maintenance',
            'delivery_method'     => 'Offline',
            'sub_delivery_method' => 'On-site',
            'provider_name'       => 'PT Schneider Indonesia',
            'venue'               => 'Auditorium Hall B',
            'instructor'          => 'Ir. Gunawan, M.T.',
            'duration_seconds'    => 32400,
            'learning_hours'      => 8.0,
            'start_date'          => '2026-08-10',
            'finish_date'         => '2026-08-10',
            'start_time'          => '08:00:00',
            'end_time'            => '17:00:00',
            'requirement'         => 'Mandatory',
            'created_by'          => $this->adminUser->id,
        ]);

        OfflineTrainingUser::create([
            'offline_training_id' => $training->id,
            'user_id'             => $this->targetEmployee->id,
            'employee_id'         => 'EMP_TEST_99',
            'name'                => 'Test Employee 99',
            'grade'               => '3B',
            'job_position'        => 'Senior Technician',
            'team'                => 'Electrical Team',
            'department'          => 'Plant Maintenance Dept',
            'company'             => 'PT Krakatau Posco',
            'main_classification' => 'Basic & Common Duties',
            'sub_classification'  => 'Plant Maintenance',
            'learning_hours'      => 8.0,
            'progress'            => 100,
            'start_date'          => '2026-08-10',
            'end_date'            => '2026-08-10',
            'start_time'          => '08:00:00',
            'end_time'            => '17:00:00',
            'attendance'          => 1,
            'attendance_reason'   => 'Present in all sessions',
            'bond_status'         => 'No',
            'bond_duration'       => 12,
            'bond_end_date'       => '2028-08-10',
            'bond_amount_idr'     => 0,
            'l1_program'          => 'Very Good',
            'assessment_score'    => 85,
            'l2_numerical'        => 85,
            'l2_verbal'           => 'Passed',
            'l3_numerical'        => 90.0,
            'l3_verbal'           => 'Implemented successfully',
            'pic'                 => 'HRDC Team',
        ]);

        $file = $this->createPtkpExcel([
            [
                'row_no'      => 1,
                'employee_id' => 'EMP_TEST_99',
                'course_name' => 'Pre-existing PTKP Course',
                'batch'       => 'Batch 1',
                'start_date'  => '10/08/2026',
                'end_date'    => '10/08/2026',
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

    public function test_extra_column_beyond_template_blocks_preview_confirmation(): void
    {
        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle(PtkpExcelSchema::DATA_SHEET_TITLE);

        $headers = PtkpExcelSchema::headers();
        foreach ($headers as $idx => $h) {
            $letter = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($idx + 1);
            $sheet->setCellValue("{$letter}1", $h);
        }
        $sheet->setCellValue('AU1', 'Extra Unexpected Column');
        $sheet->setCellValue('A2', 'EMP_TEST_99');
        $sheet->setCellValue('H2', 'Course Title');

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

    public function test_start_time_normalization_and_validation_scenarios(): void
    {
        $file = $this->createPtkpExcel([
            [
                'row_no'      => 1,
                'employee_id' => 'EMP_TEST_99',
                'course_name' => 'Time Format Test 1',
                'start_time'  => '08:00',
                'end_time'    => '17:00',
            ],
            [
                'row_no'      => 2,
                'employee_id' => 'EMP_TEST_99',
                'course_name' => 'Time Format Test 2',
                'start_time'  => ' 08:30 ',
                'end_time'    => ' 16:45 ',
            ],
            [
                'row_no'      => 3,
                'employee_id' => 'EMP_TEST_99',
                'course_name' => 'Time Format Test 3',
                'start_time'  => 0.3333333333333333, // Excel time serial 08:00
                'end_time'    => 0.7083333333333334, // Excel time serial 17:00
            ],
            [
                'row_no'      => 4,
                'employee_id' => 'EMP_TEST_99',
                'course_name' => 'Time Format Test 4',
                'start_time'  => 'invalid_time_string',
                'end_time'    => '17:00:00',
            ],
        ]);

        $response = $this->actingAs($this->adminUser)
            ->post(route('manage.record.offline.import-ptkp.preview'), ['file' => $file]);

        $response->assertStatus(200);
        $json = $response->json();

        // Rows 0, 1, 2 should be VALID / importable
        $this->assertEquals('VALID', $json['rows'][0]['status']);
        $this->assertEquals('08:00:00', $json['rows'][0]['data']['start_time']);
        $this->assertEquals('17:00:00', $json['rows'][0]['data']['end_time']);

        $this->assertEquals('VALID', $json['rows'][1]['status']);
        $this->assertEquals('08:30:00', $json['rows'][1]['data']['start_time']);
        $this->assertEquals('16:45:00', $json['rows'][1]['data']['end_time']);

        $this->assertEquals('VALID', $json['rows'][2]['status']);
        $this->assertEquals('08:00:00', $json['rows'][2]['data']['start_time']);
        $this->assertEquals('17:00:00', $json['rows'][2]['data']['end_time']);

        // Row 3 has invalid start_time -> non-blocking WARNING (never INVALID)
        $this->assertEquals('WARNING', $json['rows'][3]['status']);
        $this->assertFalse($json['rows'][3]['is_blocking']);
        $this->assertStringContainsString('Invalid Start Time format', implode(' ', $json['rows'][3]['messages']));
    }

    public function test_start_time_empty_falls_back_to_schema_default_without_null_insert_error(): void
    {
        $file = $this->createPtkpExcel([
            [
                'row_no'         => 1,
                'employee_id'    => 'EMP_TEST_99',
                'course_name'    => 'Course With Empty Time Fields',
                'start_time'     => '',
                'end_time'       => '',
                'learning_hours' => 8.0,
            ]
        ]);

        $previewResp = $this->actingAs($this->adminUser)
            ->post(route('manage.record.offline.import-ptkp.preview'), ['file' => $file]);

        $previewResp->assertStatus(200);
        $token = $previewResp->json('token');
        $this->assertNotEmpty($token);

        // Confirm import: should succeed without SQLSTATE 23000
        $confirmResp = $this->actingAs($this->adminUser)
            ->post(route('manage.record.offline.import-ptkp.confirm'), ['token' => $token]);

        $confirmResp->assertStatus(200);
        $this->assertTrue($confirmResp->json('success'));
        $this->assertEquals(1, $confirmResp->json('result.imported'));

        // Check persisted training record in database
        $training = OfflineTraining::where('course_name', 'Course With Empty Time Fields')->first();
        $this->assertNotNull($training);
        $this->assertNotNull($training->start_time);
        $this->assertNotNull($training->end_time);
        $this->assertEquals('08:00:00', $training->start_time);
        $this->assertEquals('16:00:00', $training->end_time);
        $this->assertEquals(28800, $training->duration_seconds); // Derived from learning_hours 8.0 * 3600
    }

    public function test_header_reordered_is_mapped_correctly_by_header_name(): void
    {
        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle(PtkpExcelSchema::DATA_SHEET_TITLE);

        $columns = PtkpExcelSchema::columns();

        // Swap Column 27 (Start Time) and Column 28 (End Time) in the header
        foreach ($columns as $idx => $col) {
            $letter = $col['col_letter'];
            if ($idx === 27) {
                $sheet->setCellValue("{$letter}1", 'End Time');
            } elseif ($idx === 28) {
                $sheet->setCellValue("{$letter}1", 'Start Time');
            } else {
                $sheet->setCellValue("{$letter}1", $col['header']);
            }
        }

        // Set row values: in swapped column AA (labeled End Time), put 17:00. In AB (labeled Start Time), put 08:00.
        foreach ($columns as $idx => $col) {
            $letter = $col['col_letter'];
            if ($idx === 27) {
                $sheet->setCellValue("{$letter}2", '17:00:00');
            } elseif ($idx === 28) {
                $sheet->setCellValue("{$letter}2", '08:00:00');
            } elseif ($col['key'] === 'employee_id') {
                $sheet->setCellValueExplicit("{$letter}2", 'EMP_TEST_99', \PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_STRING);
            } elseif ($col['key'] === 'course_name') {
                $sheet->setCellValue("{$letter}2", 'Swapped Column Test Course');
            } else {
                $val = $col['example'] ?? '';
                if ($col['key'] === 'bond_amount' && ($val === '' || $val === null)) {
                    $val = 0;
                }
                $sheet->setCellValue("{$letter}2", $val);
            }
        }

        $tempPath = tempnam(sys_get_temp_dir(), 'ptkp_swapped_') . '.xlsx';
        (new Xlsx($spreadsheet))->save($tempPath);
        $file = new UploadedFile($tempPath, 'ptkp_swapped.xlsx', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet', null, true);

        $response = $this->actingAs($this->adminUser)
            ->post(route('manage.record.offline.import-ptkp.preview'), ['file' => $file]);

        $response->assertStatus(200);
        $json = $response->json();
        $this->assertTrue($json['header_check']['can_proceed']);
        $this->assertEquals('VALID', $json['rows'][0]['status']);

        // Verify that start_time got '08:00:00' and end_time got '17:00:00' despite column swapping!
        $this->assertEquals('08:00:00', $json['rows'][0]['data']['start_time']);
        $this->assertEquals('17:00:00', $json['rows'][0]['data']['end_time']);
    }

    public function test_partial_import_saves_valid_rows_and_skips_invalid(): void
    {
        $file = $this->createPtkpExcel([
            [
                'row_no'      => 1,
                'employee_id' => 'EMP_TEST_99',
                'course_name' => 'Partial Import Course Valid',
                'start_time'  => '09:00:00',
                'end_time'    => '15:00:00',
            ],
            [
                'row_no'      => 2,
                'employee_id' => 'EMP_NONEXISTENT_99999',
                'course_name' => 'Partial Import Course Valid',
            ],
            [
                'row_no'      => 3,
                'employee_id' => 'EMP_TEST_99',
                'course_name' => '', // Empty Course Title -> INVALID
            ],
        ]);

        $previewResp = $this->actingAs($this->adminUser)
            ->post(route('manage.record.offline.import-ptkp.preview'), ['file' => $file]);

        $previewResp->assertStatus(200);
        $json = $previewResp->json();

        $this->assertEquals(1, $json['stats']['valid']);
        $this->assertEquals(2, $json['stats']['invalid']);
        $this->assertEquals(1, $json['importable_count']);
        $this->assertTrue($json['can_confirm']);

        $token = $json['token'];
        $confirmResp = $this->actingAs($this->adminUser)
            ->post(route('manage.record.offline.import-ptkp.confirm'), ['token' => $token]);

        $confirmResp->assertStatus(200);
        $confirmJson = $confirmResp->json();
        $this->assertTrue($confirmJson['success']);
        $this->assertEquals(1, $confirmJson['result']['imported']);
        $this->assertEquals(2, $confirmJson['result']['skipped_invalid']);

        // Verify valid record is in DB
        $validUser = OfflineTrainingUser::where('employee_id', 'EMP_TEST_99')
            ->whereHas('training', fn($q) => $q->where('course_name', 'Partial Import Course Valid'))
            ->first();
        $this->assertNotNull($validUser);

        // Verify nonexistent employee was NOT saved
        $invalidUser = OfflineTrainingUser::where('employee_id', 'EMP_NONEXISTENT_99999')->first();
        $this->assertNull($invalidUser);
    }

    public function test_duplicate_header_blocks_import_and_reports_issue(): void
    {
        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle(PtkpExcelSchema::DATA_SHEET_TITLE);

        $columns = PtkpExcelSchema::columns();
        foreach ($columns as $idx => $col) {
            $letter = $col['col_letter'];
            // Duplicate 'Employee ID' in column D (which should be Employee Name)
            if ($col['key'] === 'name') {
                $sheet->setCellValue("{$letter}1", 'Employee ID');
            } else {
                $sheet->setCellValue("{$letter}1", $col['header']);
            }
            $sheet->setCellValue("{$letter}2", $col['example'] ?? '');
        }

        $tempPath = tempnam(sys_get_temp_dir(), 'ptkp_dup_header_') . '.xlsx';
        (new Xlsx($spreadsheet))->save($tempPath);
        $file = new UploadedFile($tempPath, 'dup_header.xlsx', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet', null, true);

        $response = $this->actingAs($this->adminUser)
            ->post(route('manage.record.offline.import-ptkp.preview'), ['file' => $file]);

        $response->assertStatus(200);
        $json = $response->json();
        $this->assertFalse($json['header_check']['can_proceed']);
        $this->assertGreaterThan(0, $json['header_check']['duplicate_count']);
    }

    public function test_blank_rows_in_middle_are_skipped_and_data_afterwards_is_preserved(): void
    {
        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle(PtkpExcelSchema::DATA_SHEET_TITLE);

        $columns = PtkpExcelSchema::columns();
        foreach ($columns as $idx => $col) {
            $sheet->setCellValue("{$col['col_letter']}1", $col['header']);
        }

        // Row 2: First valid participant
        foreach ($columns as $col) {
            $val = $col['example'] ?? '';
            if ($col['key'] === 'employee_id') $val = 'EMP_TEST_99';
            if ($col['key'] === 'course_name') $val = 'Section One Course';
            $sheet->setCellValue("{$col['col_letter']}2", $val);
        }

        // Rows 3 to 202: 200 blank rows in the middle!

        // Row 203: Second valid participant after 200 blank rows
        foreach ($columns as $col) {
            $val = $col['example'] ?? '';
            if ($col['key'] === 'employee_id') $val = 'EMP_TEST_99';
            if ($col['key'] === 'course_name') $val = 'Section Two Course';
            $sheet->setCellValue("{$col['col_letter']}203", $val);
        }

        $tempPath = tempnam(sys_get_temp_dir(), 'ptkp_middle_blank_') . '.xlsx';
        (new Xlsx($spreadsheet))->save($tempPath);
        $file = new UploadedFile($tempPath, 'middle_blank.xlsx', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet', null, true);

        $response = $this->actingAs($this->adminUser)
            ->post(route('manage.record.offline.import-ptkp.preview'), ['file' => $file]);

        $response->assertStatus(200);
        $json = $response->json();

        // Both rows (row 2 and row 203) must be parsed without losing data!
        $this->assertEquals(2, $json['stats']['total_rows']);
        $this->assertEquals(2, $json['importable_count']);
        $this->assertEquals('Section One Course', $json['rows'][0]['course_name']);
        $this->assertEquals('Section Two Course', $json['rows'][1]['course_name']);
    }

    public function test_extreme_gap_of_500_blank_rows_with_max_row_formatting_preserves_all_data(): void
    {
        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle(PtkpExcelSchema::DATA_SHEET_TITLE);

        $columns = PtkpExcelSchema::columns();
        foreach ($columns as $idx => $col) {
            $sheet->setCellValue("{$col['col_letter']}1", $col['header']);
        }

        // Row 2: First valid participant
        foreach ($columns as $col) {
            $val = $col['example'] ?? '';
            if ($col['key'] === 'employee_id') $val = 'EMP_TEST_99';
            if ($col['key'] === 'course_name') $val = 'Course Before 500 Blank Rows';
            $sheet->setCellValue("{$col['col_letter']}2", $val);
        }

        // Rows 3 to 502: 500 blank rows in the middle

        // Row 503: Second valid participant after 500 blank rows
        foreach ($columns as $col) {
            $val = $col['example'] ?? '';
            if ($col['key'] === 'employee_id') $val = 'EMP_TEST_99';
            if ($col['key'] === 'course_name') $val = 'Course After 500 Blank Rows';
            $sheet->setCellValue("{$col['col_letter']}503", $val);
        }

        // Simulate empty cell with formatting at row 100,000
        $sheet->setCellValue('R100000', '');

        $tempPath = tempnam(sys_get_temp_dir(), 'ptkp_extreme_gap_') . '.xlsx';
        (new Xlsx($spreadsheet))->save($tempPath);
        $file = new UploadedFile($tempPath, 'extreme_gap.xlsx', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet', null, true);

        $response = $this->actingAs($this->adminUser)
            ->post(route('manage.record.offline.import-ptkp.preview'), ['file' => $file]);

        $response->assertStatus(200);
        $json = $response->json();

        $this->assertEquals(2, $json['stats']['total_rows']);
        $this->assertEquals(2, $json['importable_count']);
        $this->assertEquals('Course Before 500 Blank Rows', $json['rows'][0]['course_name']);
        $this->assertEquals('Course After 500 Blank Rows', $json['rows'][1]['course_name']);
    }

    public function test_numeric_nip_without_leading_zeros_is_normalized_to_canonical_6_digit_id(): void
    {
        // Create user with unique 6-digit NIP starting with zeros using test helper
        $user = $this->createTestUser([
            'id_emp'       => '000888',
            'name'         => 'Test Unpadded Employee',
            'company_name' => 'PT Krakatau Posco',
        ]);

        $file = $this->createPtkpExcel([
            [
                'row_no'      => 1,
                'employee_id' => '888', // Unpadded 3-digit numeric NIP in Excel
                'course_name' => 'Zero Padding Normalization Test Course',
            ],
            [
                'row_no'      => 2,
                'employee_id' => 'KP 061', // Non-numeric intern code -> must remain INVALID
                'course_name' => 'Zero Padding Normalization Test Course',
            ],
        ]);

        $response = $this->actingAs($this->adminUser)
            ->post(route('manage.record.offline.import-ptkp.preview'), ['file' => $file]);

        $response->assertStatus(200);
        $json = $response->json();

        // Row 1 (888) should be matched to user 000888
        $this->assertNotEquals('INVALID', $json['rows'][0]['status']);
        $this->assertEquals('000888', $json['rows'][0]['data']['employee_id']);
        $this->assertEquals('Test Unpadded Employee', $json['rows'][0]['name']);

        // Row 2 (KP 061) should remain INVALID
        $this->assertEquals('INVALID', $json['rows'][1]['status']);
        $this->assertEquals('KP 061', $json['rows'][1]['data']['employee_id']);
    }

    public function test_scenario_a_empty_red_header_fields_triggers_warning_and_persists(): void
    {
        // Scenario A: Type filled, Category filled, Level empty (RED), Batch filled, Main Classification empty (RED)
        $file = $this->createPtkpExcel([
            [
                'row_no'              => 1,
                'employee_id'         => 'EMP_TEST_99',
                'course_name'         => 'Scenario A Training Course',
                'type'                => 'In-House',
                'category'            => 'Technical Competency',
                'level'               => '', // Empty RED header
                'batch'               => 'Batch 1',
                'main_classification' => '', // Empty RED header
            ]
        ]);

        $response = $this->actingAs($this->adminUser)
            ->post(route('manage.record.offline.import-ptkp.preview'), ['file' => $file]);

        $response->assertStatus(200);
        $json = $response->json();

        $this->assertTrue($json['success']);
        $this->assertEquals(1, $json['stats']['warning']);
        $this->assertEquals(0, $json['stats']['valid']);
        $this->assertEquals(1, $json['importable_count']);

        $row = $json['rows'][0];
        $this->assertEquals('WARNING', $row['status']);
        $this->assertFalse($row['is_blocking']);
        $this->assertEquals(['Level', 'Main Classification'], $row['empty_warning_fields']);

        // Confirm import persists the warning record to DB
        $token = $json['token'];
        $this->assertNotEmpty($token);

        $confirmResponse = $this->actingAs($this->adminUser)
            ->post(route('manage.record.offline.import-ptkp.confirm'), [
                'token' => $token,
            ]);

        $confirmResponse->assertStatus(200);
        $confirmJson = $confirmResponse->json();
        $this->assertTrue($confirmJson['success']);
        $this->assertEquals(1, $confirmJson['result']['imported']);
        $this->assertEquals(1, $confirmJson['result']['warnings_imported']);

        // Assert record exists in database with null / empty fields
        $this->assertDatabaseHas('offline_trainings', [
            'course_name' => 'Scenario A Training Course',
            'type'        => 'In-House',
            'category'    => 'Technical Competency',
        ]);
    }

    public function test_scenario_b_empty_designated_fields_triggers_warning_and_persists(): void
    {
        // Scenario B: Category and Batch are designated warning fields. When empty, they trigger WARNING.
        $file = $this->createPtkpExcel([
            [
                'row_no'              => 1,
                'employee_id'         => 'EMP_TEST_99',
                'course_name'         => 'Scenario B Training Course',
                'type'                => 'In-House',
                'category'            => '', // Empty designated field
                'level'               => 'Intermediate',
                'batch'               => '', // Empty designated field
                'main_classification' => 'Basic & Common Duties',
            ]
        ]);

        $response = $this->actingAs($this->adminUser)
            ->post(route('manage.record.offline.import-ptkp.preview'), ['file' => $file]);

        $response->assertStatus(200);
        $json = $response->json();

        $this->assertTrue($json['success']);
        $this->assertEquals(0, $json['stats']['valid']);
        $this->assertEquals(1, $json['stats']['warning']);
        $this->assertEquals(1, $json['importable_count']);

        $row = $json['rows'][0];
        $this->assertEquals('WARNING', $row['status']);
        $this->assertEquals(['Category', 'Batch'], $row['empty_warning_fields']);
        $this->assertEquals('WARNING: Category is empty; Batch is empty', $row['warning_message']);
    }

    public function test_scenario_c_multiple_empty_designated_fields_reports_all_designated_fields(): void
    {
        // Scenario C: Category, Level, Venue, and Instructor are all designated warning fields.
        // Non-designated field (e.g. sub_course_name) must NOT trigger warning.
        $file = $this->createPtkpExcel([
            [
                'row_no'              => 1,
                'employee_id'         => 'EMP_TEST_99',
                'course_name'         => 'Scenario C Training Course',
                'sub_course_name'     => '', // Empty non-designated field (NOT in warning list)
                'category'            => '', // Empty designated field
                'level'               => '', // Empty designated field
                'venue'               => '', // Empty designated field
                'instructor'          => '', // Empty designated field
                'main_classification' => 'Basic & Common Duties',
                'bond_status'         => 'No',
                'l1_program'          => 'Very Good',
            ]
        ]);

        $response = $this->actingAs($this->adminUser)
            ->post(route('manage.record.offline.import-ptkp.preview'), ['file' => $file]);

        $response->assertStatus(200);
        $json = $response->json();

        $row = $json['rows'][0];
        $this->assertEquals('WARNING', $row['status']);
        $this->assertEquals(['Category', 'Level', 'Venue', 'Instructor'], $row['empty_warning_fields']);

        // Verify non-designated field is NOT in warnings
        foreach ($row['messages'] as $msg) {
            $this->assertStringNotContainsString('Sub Course Title', $msg);
        }
    }

    public function test_scenario_d_all_designated_fields_filled_with_empty_non_designated_remains_valid(): void
    {
        // Scenario D: All 33 designated warning fields filled, but non-designated fields empty
        // Non-designated: sub_course_name, provider, sub_provider, certificate_*
        $file = $this->createPtkpExcel([
            [
                'row_no'                    => 1,
                'employee_id'               => 'EMP_TEST_99',
                'course_name'               => 'Scenario D Training Course',
                // Non-designated fields empty
                'sub_course_name'           => '',
                'provider'                  => '',
                'sub_provider'              => '',
                'certificate_availability'  => '',
                'certificate_institution'   => '',
                'certificate_issued_date'   => '',
                'certificate_expired_date'  => '',
            ]
        ]);

        $response = $this->actingAs($this->adminUser)
            ->post(route('manage.record.offline.import-ptkp.preview'), ['file' => $file]);

        $response->assertStatus(200);
        $json = $response->json();

        $this->assertEquals(1, $json['stats']['valid']);
        $this->assertEquals(0, $json['stats']['warning']);
        $row = $json['rows'][0];
        $this->assertEquals('VALID', $row['status']);
        $this->assertEmpty($row['empty_warning_fields']);
    }

    public function test_duplicate_takes_priority_over_warning_when_red_fields_are_empty(): void
    {
        // Create duplicate rows that also have empty red fields
        $file = $this->createPtkpExcel([
            [
                'row_no'              => 1,
                'employee_id'         => 'EMP_TEST_99',
                'course_name'         => 'Duplicate Priority Course',
                'level'               => '', // Empty RED
            ],
            [
                'row_no'              => 2,
                'employee_id'         => 'EMP_TEST_99',
                'course_name'         => 'Duplicate Priority Course',
                'level'               => '', // Empty RED (Identical 42 columns)
            ],
        ]);

        $response = $this->actingAs($this->adminUser)
            ->post(route('manage.record.offline.import-ptkp.preview'), ['file' => $file]);

        $response->assertStatus(200);
        $json = $response->json();

        // Row 1 is WARNING (non-blocking)
        $this->assertEquals('WARNING', $json['rows'][0]['status']);
        // Row 2 is DUPLICATE (blocking)
        $this->assertEquals('DUPLICATE', $json['rows'][1]['status']);
        $this->assertTrue($json['rows'][1]['is_blocking']);
        $this->assertEquals(1, $json['stats']['duplicate']);
        $this->assertEquals(1, $json['stats']['warning']);
    }

    public function test_import_summary_and_database_persistence_for_mixed_valid_warning_duplicate(): void
    {
        $u1 = $this->createTestUser();
        $u2 = $this->createTestUser();

        $file = $this->createPtkpExcel([
            [
                'row_no'              => 1,
                'employee_id'         => $u1->id_emp,
                'course_name'         => 'Mixed Status Course 1',
                // VALID row
            ],
            [
                'row_no'              => 2,
                'employee_id'         => $u2->id_emp,
                'course_name'         => 'Mixed Status Course 2',
                'level'               => '', // WARNING row (Level empty)
                'instructor'          => '', // WARNING row (Instructor empty)
            ],
            [
                'row_no'              => 3,
                'employee_id'         => $u1->id_emp,
                'course_name'         => 'Mixed Status Course 1', // DUPLICATE of row 1
            ],
        ]);

        $previewResponse = $this->actingAs($this->adminUser)
            ->post(route('manage.record.offline.import-ptkp.preview'), ['file' => $file]);

        $previewResponse->assertStatus(200);
        $previewJson = $previewResponse->json();

        $this->assertEquals(1, $previewJson['stats']['valid']);
        $this->assertEquals(1, $previewJson['stats']['warning']);
        $this->assertEquals(1, $previewJson['stats']['duplicate']);
        $this->assertEquals(2, $previewJson['importable_count']); // valid + warning

        // Execute import
        $token = $previewJson['token'];
        $this->assertNotEmpty($token);

        $confirmResponse = $this->actingAs($this->adminUser)
            ->post(route('manage.record.offline.import-ptkp.confirm'), [
                'token' => $token,
            ]);

        $confirmResponse->assertStatus(200);
        $confirmJson = $confirmResponse->json();

        $this->assertTrue($confirmJson['success']);
        $this->assertEquals(2, $confirmJson['result']['imported']);
        $this->assertEquals(1, $confirmJson['result']['warnings_imported']);
        $this->assertEquals(1, $confirmJson['result']['skipped_duplicates']);

        // Both VALID and WARNING courses exist in DB
        $this->assertDatabaseHas('offline_trainings', ['course_name' => 'Mixed Status Course 1']);
        $this->assertDatabaseHas('offline_trainings', ['course_name' => 'Mixed Status Course 2']);
    }

    public function test_warning_aggregation_detects_empty_red_fields_and_collects_all_reasons(): void
    {
        // Real-world case: Level and Score L1 are genuinely empty/null -> both detected
        $file = $this->createPtkpExcel([
            [
                'row_no'              => 1,
                'employee_id'         => 'EMP_TEST_99',
                'course_name'         => 'Empty Red Fields Warning Test',
                'level'               => '', // genuinely empty RED field
                'main_classification' => 'Basic & Common Duties',
                'instructor'          => 'I Nyoman Sutedja A, Christofel',
                'bond_status'         => 'No',
                'l1_program'          => null, // genuinely null RED field
            ]
        ]);

        $response = $this->actingAs($this->adminUser)
            ->post(route('manage.record.offline.import-ptkp.preview'), ['file' => $file]);

        $response->assertStatus(200);
        $json = $response->json();

        $this->assertTrue($json['success']);
        $this->assertEquals(1, $json['stats']['warning']);

        $row = $json['rows'][0];
        $this->assertEquals('WARNING', $row['status']);
        $this->assertFalse($row['is_blocking']);
        $this->assertEquals(['Level', 'Score (L1) - Course'], $row['empty_warning_fields']);
        $this->assertEquals(['Level is empty', 'Score (L1) - Course is empty'], $row['warning_reasons']);
        $this->assertEquals('WARNING: Level is empty; Score (L1) - Course is empty', $row['warning_message']);

        // Check structured warning details for inspection modal
        $this->assertCount(2, $row['empty_warning_details']);
        $this->assertEquals('Level', $row['empty_warning_details'][0]['field']);
        $this->assertEquals('Empty', $row['empty_warning_details'][0]['display_value']);
        $this->assertEquals('Score (L1) - Course', $row['empty_warning_details'][1]['field']);
        $this->assertEquals('Empty', $row['empty_warning_details'][1]['display_value']);
    }

    public function test_dash_variations_are_considered_populated_and_do_not_trigger_warning(): void
    {
        // Check hyphen "-", en-dash (\u{2013}), em-dash (\u{2014}), double hyphen "--", and padded dash
        // Under the new rule, dashes are populated data and MUST NOT trigger WARNING.
        $file = $this->createPtkpExcel([
            [
                'row_no'              => 1,
                'employee_id'         => 'EMP_TEST_99',
                'course_name'         => 'Dash Populated Test',
                'level'               => '-',                // literal hyphen
                'main_classification' => '--',               // double hyphen
                'instructor'          => " \xe2\x80\x93 ",   // en-dash with spaces
                'bond_status'         => "\xe2\x80\x94",     // em-dash
                'l1_program'          => ' - ',              // hyphen with spaces
            ]
        ]);

        $response = $this->actingAs($this->adminUser)
            ->post(route('manage.record.offline.import-ptkp.preview'), ['file' => $file]);

        $response->assertStatus(200);
        $json = $response->json();

        $row = $json['rows'][0];
        $this->assertEquals('VALID', $row['status'], 'Dashes in red fields must be treated as populated and result in VALID status');
        $this->assertEmpty($row['empty_warning_fields']);
        $this->assertEmpty($row['warning_reasons']);
        $this->assertNull($row['warning_message']);
    }

    public function test_whitespace_and_null_trigger_warning_for_red_fields(): void
    {
        // Check that null, empty string, and whitespace-only strings DO trigger warning
        $file = $this->createPtkpExcel([
            [
                'row_no'              => 1,
                'employee_id'         => 'EMP_TEST_99',
                'course_name'         => 'Whitespace Null Warning Test',
                'level'               => "   ",              // whitespace-only
                'main_classification' => 'Basic & Common Duties',
                'instructor'          => "\t\n  ",           // tabs and newlines
                'bond_status'         => null,               // null
                'l1_program'          => 'Very Good',
            ]
        ]);

        $response = $this->actingAs($this->adminUser)
            ->post(route('manage.record.offline.import-ptkp.preview'), ['file' => $file]);

        $response->assertStatus(200);
        $json = $response->json();

        $row = $json['rows'][0];
        $this->assertEquals('WARNING', $row['status']);
        $this->assertEquals(['Level', 'Instructor', 'Bond Status'], $row['empty_warning_fields']);
        $this->assertEquals(['Level is empty', 'Instructor is empty', 'Bond Status is empty'], $row['warning_reasons']);
        $this->assertEquals('WARNING: Level is empty; Instructor is empty; Bond Status is empty', $row['warning_message']);
    }

    public function test_employee_133670_row_7_with_level_dash_is_valid(): void
    {
        // Verified from template: Employee 133670 Row 7 has Level='-' and Score L1='4.5'.
        // Under the new rule where '-' is populated data, Row 7 must evaluate to VALID with no warnings.
        $user = User::where('id_emp', '133670')->first()
            ?? $this->createTestUser([
                'id_emp'       => '133670',
                'name'         => 'AIRLANGGA ARIO PAMUNGKAS',
                'company_name' => 'PT Krakatau Posco',
            ]);

        $file = $this->createPtkpExcel([
            [
                'row_no'              => 7,
                'employee_id'         => '133670',
                'course_name'         => 'Personalized Skill Development',
                'level'               => '-', // Populated data, not empty
                'main_classification' => 'Basic & Common Duties',
                'instructor'          => 'Linkedin Learning',
                'bond_status'         => 'No',
                'l1_program'          => '4.5',
            ]
        ]);

        $response = $this->actingAs($this->adminUser)
            ->post(route('manage.record.offline.import-ptkp.preview'), ['file' => $file]);

        $response->assertStatus(200);
        $json = $response->json();

        $row = $json['rows'][0];
        $this->assertEquals('VALID', $row['status']);
        $this->assertEmpty($row['empty_warning_fields']);
        $this->assertNull($row['warning_message']);
    }

    public function test_employee_133670_row_872_with_null_score_l1_triggers_warning_only_for_score_l1(): void
    {
        // Template Row 872: Level='-', Instructor='-', Score L1 is empty/null.
        // Level and Instructor have '-' (populated -> no warning).
        // Only Score (L1) - Course triggers WARNING.
        $user = User::where('id_emp', '133670')->first()
            ?? $this->createTestUser([
                'id_emp'       => '133670',
                'name'         => 'AIRLANGGA ARIO PAMUNGKAS',
                'company_name' => 'PT Krakatau Posco',
            ]);

        $file = $this->createPtkpExcel([
            [
                'row_no'              => 872,
                'employee_id'         => '133670',
                'course_name'         => 'Maintenance Intermediate Skills',
                'level'               => '-', // Populated -> no warning
                'main_classification' => 'Plant Maintenance',
                'instructor'          => '-', // Populated -> no warning
                'bond_status'         => 'No',
                'l1_program'          => '',  // Genuinely empty -> WARNING
            ]
        ]);

        $response = $this->actingAs($this->adminUser)
            ->post(route('manage.record.offline.import-ptkp.preview'), ['file' => $file]);

        $response->assertStatus(200);
        $json = $response->json();

        $row = $json['rows'][0];
        $this->assertEquals('WARNING', $row['status']);
        $this->assertEquals(['Score (L1) - Course'], $row['empty_warning_fields']);
        $this->assertEquals('WARNING: Score (L1) - Course is empty', $row['warning_message']);
    }

    public function test_legitimate_values_like_zero_no_na_do_not_trigger_warning(): void
    {
        // Values like '0', 'No', 'N/A' in warning fields must NOT be treated as empty
        $file = $this->createPtkpExcel([
            [
                'row_no'              => 1,
                'employee_id'         => 'EMP_TEST_99',
                'course_name'         => 'Legitimate Values Test',
                'level'               => 'Basic',
                'main_classification' => 'N/A',
                'instructor'          => 'Ir. Gunawan, M.T.',
                'bond_status'         => 'No',
                'l1_program'          => '0', // Numeric zero as rating
            ]
        ]);

        $response = $this->actingAs($this->adminUser)
            ->post(route('manage.record.offline.import-ptkp.preview'), ['file' => $file]);

        $response->assertStatus(200);
        $json = $response->json();

        $row = $json['rows'][0];
        $this->assertEquals('VALID', $row['status']);
        $this->assertEmpty($row['empty_warning_fields']);
        $this->assertEmpty($row['warning_reasons']);
        $this->assertNull($row['warning_message']);
    }

    public function test_warning_all_five_red_fields_empty(): void
    {
        $file = $this->createPtkpExcel([
            [
                'row_no'              => 1,
                'employee_id'         => 'EMP_TEST_99',
                'course_name'         => 'All Five Empty Red Fields Test',
                'level'               => '',
                'main_classification' => null,
                'instructor'          => '   ',
                'bond_status'         => '',
                'l1_program'          => '',
            ]
        ]);

        $response = $this->actingAs($this->adminUser)
            ->post(route('manage.record.offline.import-ptkp.preview'), ['file' => $file]);

        $response->assertStatus(200);
        $json = $response->json();

        $row = $json['rows'][0];
        $this->assertEquals('WARNING', $row['status']);
        $this->assertCount(5, $row['empty_warning_fields']);
        $this->assertEquals(
            ['Level', 'Main Classification', 'Instructor', 'Bond Status', 'Score (L1) - Course'],
            $row['empty_warning_fields']
        );
        $this->assertEquals(
            'WARNING: Level is empty; Main Classification is empty; Instructor is empty; Bond Status is empty; Score (L1) - Course is empty',
            $row['warning_message']
        );
    }

    public function test_warning_with_company_mismatch_preserves_both(): void
    {
        $file = $this->createPtkpExcel([
            [
                'row_no'              => 1,
                'employee_id'         => 'EMP_TEST_99',
                'company'             => 'PT External Other Corp', // User has PT Krakatau Posco
                'course_name'         => 'Company Mismatch With Warning',
                'level'               => '',                      // Genuinely empty red field
                'main_classification' => 'Basic & Common Duties',
                'instructor'          => 'Ir. Gunawan',
                'bond_status'         => 'No',
                'l1_program'          => 'Very Good',
            ]
        ]);

        $response = $this->actingAs($this->adminUser)
            ->post(route('manage.record.offline.import-ptkp.preview'), ['file' => $file]);

        $response->assertStatus(200);
        $json = $response->json();

        $row = $json['rows'][0];
        $this->assertEquals('WARNING', $row['status']);
        $this->assertTrue($row['company_mismatch']);
        $this->assertEquals(['Level'], $row['empty_warning_fields']);
        $this->assertEquals(['Level is empty'], $row['warning_reasons']);
        $this->assertEquals('WARNING: Level is empty', $row['warning_message']);

        // Check messages contain both
        $hasCompanyMismatchMsg = false;
        $hasLevelMsg = false;
        foreach ($row['messages'] as $m) {
            if (stripos($m, 'Company mismatch') !== false) {
                $hasCompanyMismatchMsg = true;
            }
            if (stripos($m, 'Level is empty') !== false) {
                $hasLevelMsg = true;
            }
        }
        $this->assertTrue($hasCompanyMismatchMsg, 'Messages must include company mismatch');
        $this->assertTrue($hasLevelMsg, 'Messages must include Level warning');

        // Can still be confirmed
        $token = $json['token'];
        $confirmResponse = $this->actingAs($this->adminUser)
            ->post(route('manage.record.offline.import-ptkp.confirm'), ['token' => $token]);
        $confirmResponse->assertStatus(200);
        $this->assertTrue($confirmResponse->json('success'));
        $this->assertEquals(1, $confirmResponse->json('result.imported'));
    }

    public function test_only_level_empty_displays_single_warning_and_clears_when_filled(): void
    {
        // Demonstrates requirement:
        // Record with Level='' triggers ONLY Level warning.
        // Record with Level='Intermediate' has NO warnings (is VALID).
        $file = $this->createPtkpExcel([
            [
                'row_no'              => 1,
                'employee_id'         => 'EMP_TEST_99',
                'course_name'         => 'Level Empty Course',
                'level'               => '', // Empty red field
                'main_classification' => 'Basic & Common Duties',
                'instructor'          => 'I Nyoman Sutedja A, Christofel',
                'bond_status'         => 'No',
                'l1_program'          => 'Very Good',
            ],
            [
                'row_no'              => 2,
                'employee_id'         => 'EMP_TEST_99',
                'course_name'         => 'Level Populated Course',
                'level'               => 'Intermediate', // Populated red field
                'main_classification' => 'Basic & Common Duties',
                'instructor'          => 'I Nyoman Sutedja A, Christofel',
                'bond_status'         => 'No',
                'l1_program'          => 'Very Good',
            ],
        ]);

        $response = $this->actingAs($this->adminUser)
            ->post(route('manage.record.offline.import-ptkp.preview'), ['file' => $file]);

        $response->assertStatus(200);
        $json = $response->json();

        $this->assertTrue($json['success']);
        $this->assertEquals(1, $json['stats']['warning']);
        $this->assertEquals(1, $json['stats']['valid']);

        // Row 1: Level empty
        $row1 = $json['rows'][0];
        $this->assertEquals('WARNING', $row1['status']);
        $this->assertEquals(['Level'], $row1['empty_warning_fields']);
        $this->assertEquals(['Level is empty'], $row1['warning_reasons']);
        $this->assertEquals('WARNING: Level is empty', $row1['warning_message']);
        $this->assertCount(1, $row1['empty_warning_details']);
        $this->assertEquals('Level', $row1['empty_warning_details'][0]['field']);
        $this->assertEquals('Empty', $row1['empty_warning_details'][0]['display_value']);

        // Row 2: Level populated -> VALID, no warning reasons
        $row2 = $json['rows'][1];
        $this->assertEquals('VALID', $row2['status']);
        $this->assertEmpty($row2['empty_warning_fields']);
        $this->assertEmpty($row2['warning_reasons']);
        $this->assertNull($row2['warning_message']);
        $this->assertEmpty($row2['empty_warning_details']);
    }

    public function test_individual_single_red_field_warnings_and_record_based_kpi(): void
    {
        // 4 records:
        // Record 1: only Instructor empty
        // Record 2: only Bond Status empty
        // Record 3: only Score L1 empty
        // Record 4: all populated
        $file = $this->createPtkpExcel([
            [
                'row_no'              => 1,
                'employee_id'         => 'EMP_TEST_99',
                'course_name'         => 'Single Instructor Empty Course',
                'level'               => 'Basic',
                'main_classification' => 'Basic & Common Duties',
                'instructor'          => '', // Only this field empty
                'bond_status'         => 'No',
                'l1_program'          => 'Very Good',
            ],
            [
                'row_no'              => 2,
                'employee_id'         => 'EMP_TEST_99',
                'course_name'         => 'Single Bond Status Empty Course',
                'level'               => 'Basic',
                'main_classification' => 'Basic & Common Duties',
                'instructor'          => 'Ir. Gunawan, M.T.',
                'bond_status'         => '', // Only this field empty
                'l1_program'          => 'Very Good',
            ],
            [
                'row_no'              => 3,
                'employee_id'         => 'EMP_TEST_99',
                'course_name'         => 'Single Score L1 Empty Course',
                'level'               => 'Basic',
                'main_classification' => 'Basic & Common Duties',
                'instructor'          => 'Ir. Gunawan, M.T.',
                'bond_status'         => 'No',
                'l1_program'          => '', // Only this field empty
            ],
            [
                'row_no'              => 4,
                'employee_id'         => 'EMP_TEST_99',
                'course_name'         => 'All Populated Course',
                'level'               => 'Basic',
                'main_classification' => 'Basic & Common Duties',
                'instructor'          => 'Ir. Gunawan, M.T.',
                'bond_status'         => 'No',
                'l1_program'          => 'Very Good',
            ],
        ]);

        $response = $this->actingAs($this->adminUser)
            ->post(route('manage.record.offline.import-ptkp.preview'), ['file' => $file]);

        $response->assertStatus(200);
        $json = $response->json();

        // Warning count by record: 3 warning records, 1 valid record
        $this->assertEquals(3, $json['stats']['warning'], 'Warning KPI must count by records (3), not by empty fields');
        $this->assertEquals(1, $json['stats']['valid']);
        $this->assertEquals(4, $json['stats']['total_rows']);

        // Check Row 1 (Instructor only)
        $r1 = $json['rows'][0];
        $this->assertEquals('WARNING', $r1['status']);
        $this->assertEquals(['Instructor'], $r1['empty_warning_fields']);
        $this->assertEquals('WARNING: Instructor is empty', $r1['warning_message']);

        // Check Row 2 (Bond Status only)
        $r2 = $json['rows'][1];
        $this->assertEquals('WARNING', $r2['status']);
        $this->assertEquals(['Bond Status'], $r2['empty_warning_fields']);
        $this->assertEquals('WARNING: Bond Status is empty', $r2['warning_message']);

        // Check Row 3 (Score L1 only)
        $r3 = $json['rows'][2];
        $this->assertEquals('WARNING', $r3['status']);
        $this->assertEquals(['Score (L1) - Course'], $r3['empty_warning_fields']);
        $this->assertEquals('WARNING: Score (L1) - Course is empty', $r3['warning_message']);

        // Check Row 4 (All populated -> VALID)
        $r4 = $json['rows'][3];
        $this->assertEquals('VALID', $r4['status']);
        $this->assertEmpty($r4['empty_warning_fields']);
        $this->assertNull($r4['warning_message']);
    }

    public function test_section_8_strict_empty_and_populated_matrix(): void
    {
        // Matrix test corresponding to section 8 of prompt:
        // Tests Level values: NULL, empty string, whitespace, '-', ' - ', '--', '–', '—', '0', 0, 'No', 'N/A'
        $scenarios = [
            ['val' => null,             'expect_warn' => true,  'desc' => 'NULL'],
            ['val' => '',               'expect_warn' => true,  'desc' => 'empty string'],
            ['val' => '   ',           'expect_warn' => true,  'desc' => 'whitespace'],
            ['val' => '-',              'expect_warn' => false, 'desc' => 'hyphen'],
            ['val' => ' - ',            'expect_warn' => false, 'desc' => 'padded hyphen'],
            ['val' => '--',             'expect_warn' => false, 'desc' => 'double hyphen'],
            ['val' => "\xe2\x80\x93",   'expect_warn' => false, 'desc' => 'en-dash'],
            ['val' => "\xe2\x80\x94",   'expect_warn' => false, 'desc' => 'em-dash'],
            ['val' => 0,                'expect_warn' => false, 'desc' => 'int zero'],
            ['val' => '0',              'expect_warn' => false, 'desc' => 'string zero'],
            ['val' => 'No',             'expect_warn' => false, 'desc' => 'No'],
            ['val' => 'N/A',            'expect_warn' => false, 'desc' => 'N/A'],
        ];

        $rows = [];
        foreach ($scenarios as $idx => $sc) {
            $rows[] = [
                'row_no'              => $idx + 1,
                'employee_id'         => 'EMP_TEST_99',
                'course_name'         => "Course {$idx}",
                'level'               => $sc['val'],
                'main_classification' => 'Basic & Common Duties',
                'instructor'          => 'Ir. Gunawan',
                'bond_status'         => 'No',
                'l1_program'          => 'Very Good',
            ];
        }

        $file = $this->createPtkpExcel($rows);

        $response = $this->actingAs($this->adminUser)
            ->post(route('manage.record.offline.import-ptkp.preview'), ['file' => $file]);

        $response->assertStatus(200);
        $json = $response->json();

        foreach ($scenarios as $idx => $sc) {
            $row = $json['rows'][$idx];
            if ($sc['expect_warn']) {
                $this->assertEquals('WARNING', $row['status'], "Scenario {$sc['desc']} must trigger WARNING");
                $this->assertEquals(['Level'], $row['empty_warning_fields']);
            } else {
                $this->assertEquals('VALID', $row['status'], "Scenario {$sc['desc']} must NOT trigger WARNING (must be VALID)");
                $this->assertEmpty($row['empty_warning_fields']);
            }
        }
    }

    public function test_mixed_null_and_dash_values_only_trigger_warning_for_genuinely_null_fields(): void
    {
        $file = $this->createPtkpExcel([
            // Row 1: Level = NULL, Score L1 = '-' -> Only Level warning
            [
                'row_no'              => 1,
                'employee_id'         => 'EMP_TEST_99',
                'course_name'         => 'Row 1 Level Null Score Dash',
                'level'               => null,
                'main_classification' => 'Basic & Common Duties',
                'instructor'          => 'Ir. Gunawan',
                'bond_status'         => 'No',
                'l1_program'          => '-',
            ],
            // Row 2: Level = '-', Score L1 = NULL -> Only Score warning
            [
                'row_no'              => 2,
                'employee_id'         => 'EMP_TEST_99',
                'course_name'         => 'Row 2 Level Dash Score Null',
                'level'               => '-',
                'main_classification' => 'Basic & Common Duties',
                'instructor'          => 'Ir. Gunawan',
                'bond_status'         => 'No',
                'l1_program'          => null,
            ],
            // Row 3: All five fields = '-' -> VALID (No warning)
            [
                'row_no'              => 3,
                'employee_id'         => 'EMP_TEST_99',
                'course_name'         => 'Row 3 All Five Dashes',
                'level'               => '-',
                'main_classification' => '-',
                'instructor'          => '-',
                'bond_status'         => '-',
                'l1_program'          => '-',
            ],
            // Row 4: Mixed NULL and dash: Level NULL, Instructor NULL, Score NULL; Classification '-', Bond '-'
            [
                'row_no'              => 4,
                'employee_id'         => 'EMP_TEST_99',
                'course_name'         => 'Row 4 Mixed Null and Dash',
                'level'               => null,
                'main_classification' => '-',
                'instructor'          => null,
                'bond_status'         => '-',
                'l1_program'          => null,
            ],
        ]);

        $response = $this->actingAs($this->adminUser)
            ->post(route('manage.record.offline.import-ptkp.preview'), ['file' => $file]);

        $response->assertStatus(200);
        $json = $response->json();

        // Row 1: Only Level is empty
        $row1 = $json['rows'][0];
        $this->assertEquals('WARNING', $row1['status']);
        $this->assertEquals(['Level'], $row1['empty_warning_fields']);
        $this->assertEquals(['Level is empty'], $row1['warning_reasons']);
        $this->assertEquals('WARNING: Level is empty', $row1['warning_message']);

        // Row 2: Only Score L1 is empty
        $row2 = $json['rows'][1];
        $this->assertEquals('WARNING', $row2['status']);
        $this->assertEquals(['Score (L1) - Course'], $row2['empty_warning_fields']);
        $this->assertEquals(['Score (L1) - Course is empty'], $row2['warning_reasons']);
        $this->assertEquals('WARNING: Score (L1) - Course is empty', $row2['warning_message']);

        // Row 3: All dashes -> VALID, no warning reasons
        $row3 = $json['rows'][2];
        $this->assertEquals('VALID', $row3['status']);
        $this->assertEmpty($row3['empty_warning_fields']);
        $this->assertEmpty($row3['warning_reasons']);
        $this->assertNull($row3['warning_message']);

        // Row 4: Exactly 3 fields are NULL (Level, Instructor, Score L1)
        $row4 = $json['rows'][3];
        $this->assertEquals('WARNING', $row4['status']);
        $this->assertEquals(['Level', 'Instructor', 'Score (L1) - Course'], $row4['empty_warning_fields']);
        $this->assertEquals(
            ['Level is empty', 'Instructor is empty', 'Score (L1) - Course is empty'],
            $row4['warning_reasons']
        );
        $this->assertEquals(
            'WARNING: Level is empty; Instructor is empty; Score (L1) - Course is empty',
            $row4['warning_message']
        );
    }

    public function test_all_33_designated_fields_empty_detection_and_dash_behavior(): void
    {
        $all33Empty = [
            'row_no'              => 1,
            'employee_id'         => 'EMP_TEST_99',
            'course_name'         => 'All 33 Empty Test Course',
            // All 33 designated warning fields explicitly set to empty string
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
            'course_duration'     => '',
            'learning_hours'      => '',
            'progress'            => '',
            'progress_status'     => '',
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
        ];

        $all33Dashes = [
            'row_no'              => 2,
            'employee_id'         => 'EMP_TEST_99',
            'course_name'         => 'All 33 Dashes Test Course',
            // All 33 designated warning fields explicitly set to '-'
            'type'                => '-',
            'category'            => '-',
            'level'               => '-',
            'batch'               => '-',
            'main_classification' => '-',
            'sub_classification'  => '-',
            'delivery_method'     => '-',
            'sub_delivery_method' => '-',
            'provider_name'       => '-',
            'venue'               => '-',
            'instructor'          => '-',
            'course_duration'     => '-',
            'learning_hours'      => '-',
            'progress'            => '-',
            'progress_status'     => '-',
            'start_date'          => '-',
            'end_date'            => '-',
            'start_time'          => '-',
            'end_time'            => '-',
            'month'               => '-',
            'requirement'         => '-',
            'attendance'          => '-',
            'attendance_reason'   => '-',
            'bond_status'         => '-',
            'bond_duration'       => '-',
            'bond_end_date'       => '-',
            'bond_amount'         => '-',
            'l1_program'          => '-',
            'assessment_score'    => '-',
            'l2_verbal'           => '-',
            'l3_numerical'        => '-',
            'l3_verbal'           => '-',
            'pic'                 => '-',
        ];

        $singleEmptyField = [
            'row_no'              => 3,
            'employee_id'         => 'EMP_TEST_99',
            'course_name'         => 'Single Empty Field Test Course',
            // Exactly 1 empty field: Attendance Reason
            'attendance_reason'   => '',
        ];

        $file = $this->createPtkpExcel([$all33Empty, $all33Dashes, $singleEmptyField]);

        $response = $this->actingAs($this->adminUser)
            ->post(route('manage.record.offline.import-ptkp.preview'), ['file' => $file]);

        $response->assertStatus(200);
        $json = $response->json();

        // Row 1: All 33 fields empty -> WARNING with exactly 33 empty warning fields
        $row1 = $json['rows'][0];
        $this->assertEquals('WARNING', $row1['status']);
        $this->assertCount(33, $row1['empty_warning_fields']);
        $this->assertCount(33, $row1['empty_warning_details']);
        $this->assertCount(33, $row1['warning_reasons']);

        // Check each of the 33 fields is included
        $expected33 = [
            'Type', 'Category', 'Level', 'Batch', 'Main Classification', 'Sub Classification',
            'Delivery Method', 'Sub Delivery Method', 'Provider Name', 'Venue', 'Instructor',
            'Course Duration', 'Learning Hours', '% Progress', 'Progress Status', 'Start Date',
            'End Date', 'Start Time', 'End Time', 'Record Month', 'Requirement', 'Attendance',
            'Attendance Reason', 'Bond Status', 'Bond Duration', 'Bond End Date', 'Bond Amount',
            'Score (L1) - Course', 'Score (L2)', 'L2 Status', 'Score (L3)', 'L3 Status',
            'Person in Charge (PIC)'
        ];
        $this->assertEquals($expected33, $row1['empty_warning_fields']);

        // Row 2: All 33 dashes -> VALID (No warnings)
        $row2 = $json['rows'][1];
        $this->assertEquals('VALID', $row2['status'], 'All 33 dashes must be recognized as populated data and evaluate to VALID');
        $this->assertEmpty($row2['empty_warning_fields']);
        $this->assertNull($row2['warning_message']);

        // Row 3: Single empty field (Attendance Reason)
        $row3 = $json['rows'][2];
        $this->assertEquals('WARNING', $row3['status']);
        $this->assertEquals(['Attendance Reason'], $row3['empty_warning_fields']);
        $this->assertEquals('WARNING: Attendance Reason is empty', $row3['warning_message']);
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
                    $val = $col['example'] ?? '';
                    if ($key === 'bond_amount' && ($val === '' || $val === null)) {
                        $val = 0;
                    }
                    $sheet->setCellValue("{$letter}{$rowIdx}", $val);
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
