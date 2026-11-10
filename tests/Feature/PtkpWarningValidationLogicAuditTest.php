<?php

namespace Tests\Feature;

use App\Models\OfflineTraining;
use App\Models\OfflineTrainingUser;
use App\Models\User;
use App\Services\OfflineTraining\PtkpImportService;
use App\Services\OfflineTraining\PtkpValidationService;
use App\Services\Record\PtkpColumnDefinitions;
use App\Services\Record\PtkpExcelSchema;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Http\UploadedFile;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Tests\TestCase;

class PtkpWarningValidationLogicAuditTest extends TestCase
{
    use DatabaseTransactions;

    protected User $adminUser;
    protected User $targetUser;
    protected OfflineTraining $testCourse;
    protected PtkpValidationService $validationService;

    protected function setUp(): void
    {
        parent::setUp();

        $this->validationService = app(PtkpValidationService::class);

        $this->adminUser = User::whereIn('role', [User::ROLE_ADMIN, User::ROLE_SUPER_ADMIN])->first()
            ?? $this->createTestUser(['role' => User::ROLE_ADMIN]);

        $this->targetUser = $this->createTestUser([
            'id_emp'       => 'AUD_01',
            'name'         => 'Bambang Soedjoyono',
            'company_name' => 'PT Krakatau Posco',
            'jobgrade'     => '4A',
            'jobposition'  => 'Senior Operations Specialist',
            'team'         => 'Rolling Mill 1',
            'department'   => 'Plant Maintenance Dept',
        ]);

        $this->testCourse = OfflineTraining::create([
            'title'       => 'Audit Warning Course ' . uniqid(),
            'company'     => 'PT Krakatau Posco',
            'description' => 'Course for Warning Validation Logic Audit',
            'created_by'  => $this->adminUser->id,
        ]);
    }

    protected function createTestUser(array $attrs = []): User
    {
        static $seq = 7500;
        $seq++;
        return User::create(array_merge([
            'name'         => 'Test Audit User ' . $seq,
            'email'        => "audit_ptkp_{$seq}@example.com",
            'password'     => bcrypt('secret'),
            'id_emp'       => 'WP' . $seq,
            'id_user'      => 'UID_W' . $seq,
            'id_org_unit'  => 'OU_01',
            'team'         => 'Plant Maintenance',
            'department'   => 'Maintenance Department',
            'division'     => 'Operations Division',
            'sap_code'     => 'SAP_W' . $seq,
            'ssohash'      => 'HASH_W' . $seq,
            'role'         => User::ROLE_USER,
            'company_name' => 'PT Krakatau Posco',
            'jobgrade'     => '3B',
            'jobposition'  => 'Technician',
        ], $attrs));
    }

    protected function getCanonicalRowData(array $overrides = []): array
    {
        $base = [
            'employee_id'              => $this->targetUser->id_emp,
            'name'                     => $this->targetUser->name,
            'company'                  => 'PT Krakatau Posco',
            'course_name'              => $this->testCourse->title,
            'type'                     => 'In-House',
            'category'                 => '2',
            'level'                    => '2',
            'batch'                    => '2',
            'main_classification'      => 'Compliance',
            'sub_classification'       => 'Certification',
            'delivery_method'          => 'Offline',
            'sub_delivery_method'      => 'On-Site',
            'provider'                 => 'External',
            'sub_provider'             => 'Institution',
            'provider_name'            => 'Training Team',
            'venue'                    => 'HRDC',
            'instructor'               => 'Bambang Soedjoyono (TPM), Dewi (Disnaker)',
            'learning_hours'           => 32,
            'progress'                 => 1,
            'start_date'               => '24/07/2026',
            'end_date'                 => '24/07/2026',
            'start_time'               => '08:00:00',
            'end_time'                 => '17:00:00',
            'month'                    => '7',
            'requirement'              => 'mandatory',
            'attendance'               => 'Yes',
            'attendance_reason'        => 'Present at all sessions',
            'bond_status'              => 'No',
            'bond_duration'            => '12',
            'bond_end_date'            => '24/07/2028',
            'bond_amount'              => '1000000',
            'l1_program'               => '4.82',
            'assessment_score'         => '85',
            'l2_verbal'                => 'Passed',
            'l3_numerical'             => '90',
            'l3_verbal'                => 'Implemented successfully',
            'certificate_availability' => 'Yes',
            'certificate_institution'  => 'KEMENAKER',
            'certificate_issued_date'  => '24/07/2026',
            'certificate_expired_date' => '24/07/2029',
            'pic'                      => 'Nasywa',
        ];

        return array_merge($base, $overrides);
    }

    protected function createPtkpExcelFile(array $rows): UploadedFile
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

        $tempPath = tempnam(sys_get_temp_dir(), 'ptkp_warning_audit_') . '.xlsx';
        $writer = new Xlsx($spreadsheet);
        $writer->save($tempPath);

        return new UploadedFile($tempPath, 'ptkp_warning_audit.xlsx', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet', null, true);
    }

    /**
     * Test direct helper isWarningFieldEmpty behavior against all canonical inputs.
     */
    public function test_is_warning_field_empty_unit_behavior(): void
    {
        // 1. Genuinely empty values must return TRUE
        $this->assertTrue($this->validationService->isWarningFieldEmpty(null), 'null must evaluate to empty');
        $this->assertTrue($this->validationService->isWarningFieldEmpty(''), 'empty string must evaluate to empty');
        $this->assertTrue($this->validationService->isWarningFieldEmpty('   '), 'whitespace string must evaluate to empty');
        $this->assertTrue($this->validationService->isWarningFieldEmpty("\t\n "), 'tabs and newlines must evaluate to empty');

        $this->assertTrue(PtkpColumnDefinitions::isWarningFieldEmpty(null));
        $this->assertTrue(PtkpColumnDefinitions::isWarningFieldEmpty(''));
        $this->assertTrue(PtkpColumnDefinitions::isWarningFieldEmpty('   '));

        // 2. Dash values must return FALSE (considered populated data, NOT empty)
        $this->assertFalse($this->validationService->isWarningFieldEmpty('-'), "'-' must evaluate to populated");
        $this->assertFalse($this->validationService->isWarningFieldEmpty(' - '), "' - ' must evaluate to populated");
        $this->assertFalse($this->validationService->isWarningFieldEmpty('--'), "'--' must evaluate to populated");
        $this->assertFalse($this->validationService->isWarningFieldEmpty('—'), "'—' (em-dash) must evaluate to populated");
        $this->assertFalse($this->validationService->isWarningFieldEmpty('–'), "'–' (en-dash) must evaluate to populated");

        $this->assertFalse(PtkpColumnDefinitions::isWarningFieldEmpty('-'));
        $this->assertFalse(PtkpColumnDefinitions::isWarningFieldEmpty(' - '));

        // 3. Numeric zeros must return FALSE (considered populated data, NOT empty)
        $this->assertFalse($this->validationService->isWarningFieldEmpty(0), 'integer 0 must evaluate to populated');
        $this->assertFalse($this->validationService->isWarningFieldEmpty('0'), "string '0' must evaluate to populated");
        $this->assertFalse($this->validationService->isWarningFieldEmpty(0.0), 'float 0.0 must evaluate to populated');
        $this->assertFalse($this->validationService->isWarningFieldEmpty('0.0'), "string '0.0' must evaluate to populated");

        $this->assertFalse(PtkpColumnDefinitions::isWarningFieldEmpty(0));
        $this->assertFalse(PtkpColumnDefinitions::isWarningFieldEmpty('0'));

        // 4. Other legitimate values must return FALSE
        $this->assertFalse($this->validationService->isWarningFieldEmpty('00:00:00'));
        $this->assertFalse($this->validationService->isWarningFieldEmpty('On-process'));
        $this->assertFalse($this->validationService->isWarningFieldEmpty('Yes'));
        $this->assertFalse($this->validationService->isWarningFieldEmpty('32'));
        $this->assertFalse($this->validationService->isWarningFieldEmpty(32));
    }

    /**
     * TC-01: Attendance Reason = NULL -> WARNING
     */
    public function test_tc01_attendance_reason_null_evaluates_to_warning(): void
    {
        $file = $this->createPtkpExcelFile([
            $this->getCanonicalRowData(['attendance_reason' => null])
        ]);

        $response = $this->actingAs($this->adminUser)
            ->post(route('manage.record.offline.import-ptkp.preview'), ['file' => $file]);

        $response->assertStatus(200);
        $json = $response->json();
        $row = $json['rows'][0];

        $this->assertEquals(1, $json['stats']['warning']);
        $this->assertEquals(0, $json['stats']['valid']);
        $this->assertEquals('WARNING', $row['status']);
        $this->assertContains('Attendance Reason', $row['empty_warning_fields']);
        $this->assertStringContainsString('Attendance Reason is empty', $row['warning_message']);
    }

    /**
     * TC-02: Attendance Reason = "" (empty string) -> WARNING
     */
    public function test_tc02_attendance_reason_empty_string_evaluates_to_warning(): void
    {
        $file = $this->createPtkpExcelFile([
            $this->getCanonicalRowData(['attendance_reason' => ''])
        ]);

        $response = $this->actingAs($this->adminUser)
            ->post(route('manage.record.offline.import-ptkp.preview'), ['file' => $file]);

        $response->assertStatus(200);
        $json = $response->json();
        $row = $json['rows'][0];

        $this->assertEquals(1, $json['stats']['warning']);
        $this->assertEquals('WARNING', $row['status']);
        $this->assertContains('Attendance Reason', $row['empty_warning_fields']);
        $this->assertStringContainsString('Attendance Reason is empty', $row['warning_message']);
    }

    /**
     * TC-03: Attendance Reason = whitespace -> WARNING
     */
    public function test_tc03_attendance_reason_whitespace_evaluates_to_warning(): void
    {
        $file = $this->createPtkpExcelFile([
            $this->getCanonicalRowData(['attendance_reason' => '     '])
        ]);

        $response = $this->actingAs($this->adminUser)
            ->post(route('manage.record.offline.import-ptkp.preview'), ['file' => $file]);

        $response->assertStatus(200);
        $json = $response->json();
        $row = $json['rows'][0];

        $this->assertEquals(1, $json['stats']['warning']);
        $this->assertEquals('WARNING', $row['status']);
        $this->assertContains('Attendance Reason', $row['empty_warning_fields']);
        $this->assertStringContainsString('Attendance Reason is empty', $row['warning_message']);
    }

    /**
     * TC-04: Type = '-' -> VALID (Tidak Warning)
     */
    public function test_tc04_type_dash_evaluates_to_valid(): void
    {
        $file = $this->createPtkpExcelFile([
            $this->getCanonicalRowData(['type' => '-'])
        ]);

        $response = $this->actingAs($this->adminUser)
            ->post(route('manage.record.offline.import-ptkp.preview'), ['file' => $file]);

        $response->assertStatus(200);
        $json = $response->json();
        $row = $json['rows'][0];

        $this->assertEquals(0, $json['stats']['warning']);
        $this->assertEquals(1, $json['stats']['valid']);
        $this->assertEquals('VALID', $row['status']);
        $this->assertNotContains('Type', $row['empty_warning_fields']);
        $this->assertEmpty($row['empty_warning_fields']);
    }

    /**
     * TC-05: L2 Status = '-' -> VALID (Tidak Warning)
     */
    public function test_tc05_l2_status_dash_evaluates_to_valid(): void
    {
        $file = $this->createPtkpExcelFile([
            $this->getCanonicalRowData(['l2_verbal' => '-'])
        ]);

        $response = $this->actingAs($this->adminUser)
            ->post(route('manage.record.offline.import-ptkp.preview'), ['file' => $file]);

        $response->assertStatus(200);
        $json = $response->json();
        $row = $json['rows'][0];

        $this->assertEquals(0, $json['stats']['warning']);
        $this->assertEquals(1, $json['stats']['valid']);
        $this->assertEquals('VALID', $row['status']);
        $this->assertNotContains('L2 Status', $row['empty_warning_fields']);
        $this->assertEmpty($row['empty_warning_fields']);
    }

    /**
     * TC-06: L3 Status = '-' -> VALID (Tidak Warning)
     */
    public function test_tc06_l3_status_dash_evaluates_to_valid(): void
    {
        $file = $this->createPtkpExcelFile([
            $this->getCanonicalRowData(['l3_verbal' => '-'])
        ]);

        $response = $this->actingAs($this->adminUser)
            ->post(route('manage.record.offline.import-ptkp.preview'), ['file' => $file]);

        $response->assertStatus(200);
        $json = $response->json();
        $row = $json['rows'][0];

        $this->assertEquals(0, $json['stats']['warning']);
        $this->assertEquals(1, $json['stats']['valid']);
        $this->assertEquals('VALID', $row['status']);
        $this->assertNotContains('L3 Status', $row['empty_warning_fields']);
        $this->assertEmpty($row['empty_warning_fields']);
    }

    /**
     * TC-07: Bond Amount = 0 -> VALID (Tidak Warning)
     */
    public function test_tc07_bond_amount_zero_evaluates_to_valid(): void
    {
        $file = $this->createPtkpExcelFile([
            $this->getCanonicalRowData(['bond_amount' => 0]),
            $this->getCanonicalRowData(['bond_amount' => '0', 'course_name' => $this->testCourse->title . ' B'])
        ]);

        $response = $this->actingAs($this->adminUser)
            ->post(route('manage.record.offline.import-ptkp.preview'), ['file' => $file]);

        $response->assertStatus(200);
        $json = $response->json();

        $this->assertEquals(0, $json['stats']['warning']);
        $this->assertEquals(2, $json['stats']['valid']);

        $row1 = $json['rows'][0];
        $row2 = $json['rows'][1];

        $this->assertEquals('VALID', $row1['status']);
        $this->assertNotContains('Bond Amount', $row1['empty_warning_fields']);

        $this->assertEquals('VALID', $row2['status']);
        $this->assertNotContains('Bond Amount', $row2['empty_warning_fields']);
    }

    /**
     * TC-08: Start Time = 00:00:00 -> VALID (Tidak Warning)
     */
    public function test_tc08_start_time_midnight_evaluates_to_valid(): void
    {
        $file = $this->createPtkpExcelFile([
            $this->getCanonicalRowData(['start_time' => '00:00:00', 'end_time' => '00:00:00'])
        ]);

        $response = $this->actingAs($this->adminUser)
            ->post(route('manage.record.offline.import-ptkp.preview'), ['file' => $file]);

        $response->assertStatus(200);
        $json = $response->json();
        $row = $json['rows'][0];

        $this->assertEquals(0, $json['stats']['warning']);
        $this->assertEquals(1, $json['stats']['valid']);
        $this->assertEquals('VALID', $row['status']);
        $this->assertNotContains('Start Time', $row['empty_warning_fields']);
        $this->assertEmpty($row['format_warnings']);
    }

    /**
     * TC-09: Certificate Issued Date = On-process -> VALID (Tidak Warning)
     */
    public function test_tc09_certificate_issued_date_on_process_evaluates_to_valid(): void
    {
        $file = $this->createPtkpExcelFile([
            $this->getCanonicalRowData([
                'certificate_issued_date'  => 'On-process',
                'certificate_expired_date' => 'On-process',
            ])
        ]);

        $response = $this->actingAs($this->adminUser)
            ->post(route('manage.record.offline.import-ptkp.preview'), ['file' => $file]);

        $response->assertStatus(200);
        $json = $response->json();
        $row = $json['rows'][0];

        $this->assertEquals(0, $json['stats']['warning']);
        $this->assertEquals(1, $json['stats']['valid']);
        $this->assertEquals('VALID', $row['status']);
        $this->assertEmpty($row['format_warnings']);
    }

    /**
     * TC-10: Attendance Reason kosong dan Type = '-' -> WARNING hanya karena Attendance Reason
     */
    public function test_tc10_attendance_reason_empty_with_type_dash_triggers_warning_only_for_attendance_reason(): void
    {
        $file = $this->createPtkpExcelFile([
            $this->getCanonicalRowData([
                'attendance_reason' => '',
                'type'              => '-',
            ])
        ]);

        $response = $this->actingAs($this->adminUser)
            ->post(route('manage.record.offline.import-ptkp.preview'), ['file' => $file]);

        $response->assertStatus(200);
        $json = $response->json();
        $row = $json['rows'][0];

        $this->assertEquals(1, $json['stats']['warning']);
        $this->assertEquals(0, $json['stats']['valid']);
        $this->assertEquals('WARNING', $row['status']);

        // Must contain ONLY Attendance Reason
        $this->assertEquals(['Attendance Reason'], $row['empty_warning_fields']);
        $this->assertNotContains('Type', $row['empty_warning_fields']);
        $this->assertEquals('WARNING: Attendance Reason is empty', $row['warning_message']);
    }

    /**
     * TC-11: Semua designated warning fields terisi -> VALID (Tidak Warning)
     */
    public function test_tc11_all_designated_warning_fields_populated_evaluates_to_valid(): void
    {
        $file = $this->createPtkpExcelFile([
            $this->getCanonicalRowData()
        ]);

        $response = $this->actingAs($this->adminUser)
            ->post(route('manage.record.offline.import-ptkp.preview'), ['file' => $file]);

        $response->assertStatus(200);
        $json = $response->json();
        $row = $json['rows'][0];

        $this->assertEquals(0, $json['stats']['warning']);
        $this->assertEquals(1, $json['stats']['valid']);
        $this->assertEquals('VALID', $row['status']);
        $this->assertEmpty($row['empty_warning_fields']);
        $this->assertEmpty($row['warning_reasons']);
        $this->assertNull($row['warning_message']);
    }

    /**
     * TC-12: Beberapa designated warning fields kosong -> WARNING dengan seluruh nama field ditampilkan
     */
    public function test_tc12_multiple_designated_warning_fields_empty_lists_all_in_warning(): void
    {
        $file = $this->createPtkpExcelFile([
            $this->getCanonicalRowData([
                'attendance_reason' => '',
                'bond_duration'     => '',
                'l2_verbal'         => '',
            ])
        ]);

        $response = $this->actingAs($this->adminUser)
            ->post(route('manage.record.offline.import-ptkp.preview'), ['file' => $file]);

        $response->assertStatus(200);
        $json = $response->json();
        $row = $json['rows'][0];

        $this->assertEquals(1, $json['stats']['warning']);
        $this->assertEquals('WARNING', $row['status']);

        $expectedEmpty = ['Attendance Reason', 'Bond Duration', 'L2 Status'];
        $this->assertEquals($expectedEmpty, $row['empty_warning_fields']);

        // Warning message must list all three
        $this->assertStringContainsString('Attendance Reason is empty', $row['warning_message']);
        $this->assertStringContainsString('Bond Duration is empty', $row['warning_message']);
        $this->assertStringContainsString('L2 Status is empty', $row['warning_message']);

        // Structured inspection details must list each with 'Empty'
        $this->assertCount(3, $row['empty_warning_details']);
        foreach ($row['empty_warning_details'] as $detail) {
            $this->assertEquals('Empty', $detail['display_value']);
            $this->assertContains($detail['field'], $expectedEmpty);
        }
    }

    /**
     * Comprehensive Excel Table 3 scenario test:
     * Validates the exact 46-row scenario from Section 3 of the prompt specification.
     */
    public function test_prompt_section_3_comprehensive_matrix_evaluation(): void
    {
        $excelScenario = [
            'type'                     => '-',                                        // col 10: Tidak Warning
            'category'                 => '2',                                        // col 11: Tidak Warning
            'level'                    => '2',                                        // col 12: Tidak Warning
            'batch'                    => '2',                                        // col 13: Tidak Warning
            'main_classification'      => 'Compliance',                               // col 14: Tidak Warning
            'sub_classification'       => 'Certification',                            // col 15: Tidak Warning
            'delivery_method'          => 'Offline',                                  // col 16: Tidak Warning
            'sub_delivery_method'      => 'On-Site',                                  // col 17: Tidak Warning
            'provider'                 => 'External',                                 // col 18: Tidak Warning
            'sub_provider'             => 'Institution',                              // col 19: Tidak Warning
            'provider_name'            => 'Training Team',                            // col 20: Tidak Warning
            'venue'                    => 'HRDC',                                     // col 21: Tidak Warning
            'instructor'               => 'Bambang Soedjoyono (TPM), Dewi (Disnaker)',// col 22: Tidak Warning
            'learning_hours'           => '32',                                       // col 23: Tidak Warning
            'progress'                 => '1',                                        // col 24: Tidak Warning
            'start_date'               => '24/07/2026',                               // col 25: Tidak Warning
            'end_date'                 => '24/07/2026',                               // col 26: Tidak Warning
            'start_time'               => '00:00:00',                                 // col 27: Tidak Warning
            'end_time'                 => '00:00:00',                                 // col 28: Tidak Warning
            'month'                    => '7',                                        // col 29: Tidak Warning
            'requirement'              => 'mandatory',                                // col 30: Tidak Warning
            'attendance'               => 'Yes',                                      // col 31: Tidak Warning
            'attendance_reason'        => '',                                         // col 32: KOSONG -> WARNING
            'bond_status'              => 'No',                                       // col 33: Tidak Warning
            'bond_duration'            => '',                                         // col 34: KOSONG -> WARNING
            'bond_end_date'            => '',                                         // col 35: KOSONG -> WARNING
            'bond_amount'              => '0',                                        // col 36: '0' -> Tidak Warning
            'l1_program'               => '4.82',                                     // col 37: Tidak Warning
            'assessment_score'         => '',                                         // col 38: KOSONG -> WARNING
            'l2_verbal'                => '-',                                        // col 39: '-' -> Tidak Warning
            'l3_numerical'             => '',                                         // col 40: KOSONG -> WARNING
            'l3_verbal'                => '-',                                        // col 41: '-' -> Tidak Warning
            'certificate_availability' => '',                                         // col 42: KOSONG
            'certificate_institution'  => 'KEMENAKER',                                // col 43: Tidak Warning
            'certificate_issued_date'  => 'On-process',                               // col 44: Tidak Warning
            'certificate_expired_date' => 'On-process',                               // col 45: Tidak Warning
            'pic'                      => 'Nasywa',                                   // col 46: Tidak Warning
        ];

        $file = $this->createPtkpExcelFile([
            $this->getCanonicalRowData($excelScenario)
        ]);

        $response = $this->actingAs($this->adminUser)
            ->post(route('manage.record.offline.import-ptkp.preview'), ['file' => $file]);

        $response->assertStatus(200);
        $json = $response->json();
        $row = $json['rows'][0];

        // Overall row status is WARNING
        $this->assertEquals('WARNING', $row['status']);
        $this->assertEquals(1, $json['stats']['warning']);

        // Dash fields MUST NOT trigger warning
        $this->assertNotContains('Type', $row['empty_warning_fields']);
        $this->assertNotContains('L2 Status', $row['empty_warning_fields']);
        $this->assertNotContains('L3 Status', $row['empty_warning_fields']);
        $this->assertNotContains('Bond Amount', $row['empty_warning_fields']);
        $this->assertNotContains('Start Time', $row['empty_warning_fields']);
        $this->assertNotContains('End Time', $row['empty_warning_fields']);
        $this->assertNotContains('Person in Charge (PIC)', $row['empty_warning_fields']);

        // Only genuinely empty designated fields trigger warning
        $this->assertContains('Attendance Reason', $row['empty_warning_fields']);
        $this->assertContains('Bond Duration', $row['empty_warning_fields']);
        $this->assertContains('Bond End Date', $row['empty_warning_fields']);
        $this->assertContains('Score (L2)', $row['empty_warning_fields']);
        $this->assertContains('Score (L3)', $row['empty_warning_fields']);

        // Warning message does NOT contain Type, L2 Status, L3 Status, or Bond Amount
        $this->assertStringNotContainsString('Type is empty', $row['warning_message']);
        $this->assertStringNotContainsString('L2 Status is empty', $row['warning_message']);
        $this->assertStringNotContainsString('L3 Status is empty', $row['warning_message']);
        $this->assertStringNotContainsString('Bond Amount is empty', $row['warning_message']);

        // Verify row is importable
        $this->assertEquals(1, $json['importable_count']);
    }
}
