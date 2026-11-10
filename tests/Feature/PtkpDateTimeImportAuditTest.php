<?php

namespace Tests\Feature;

use App\Models\OfflineTraining;
use App\Models\OfflineTrainingUser;
use App\Models\User;
use App\Services\OfflineTraining\ExcelDateTimeParser;
use App\Services\OfflineTraining\PtkpImportService;
use App\Services\Record\PtkpExcelSchema;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Http\UploadedFile;
use PhpOffice\PhpSpreadsheet\Cell\DataType;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Tests\TestCase;

class PtkpDateTimeImportAuditTest extends TestCase
{
    use DatabaseTransactions;

    protected User $adminUser;
    protected User $targetEmployee;
    protected PtkpImportService $importService;

    protected function setUp(): void
    {
        parent::setUp();

        $this->importService = app(PtkpImportService::class);

        $this->adminUser = User::whereIn('role', [User::ROLE_ADMIN, User::ROLE_SUPER_ADMIN])->first()
            ?? $this->createTestUser(['role' => User::ROLE_ADMIN]);

        $this->targetEmployee = $this->createTestUser([
            'id_emp'       => 'EMP_99',
            'name'         => 'Audit User 01',
            'jobgrade'     => '3B',
            'jobposition'  => 'Electrical Specialist',
            'team'         => 'Substation Maintenance',
            'department'   => 'Plant Maintenance Dept',
            'company_name' => 'PT Krakatau Posco',
        ]);
    }

    protected function createTestUser(array $attrs = []): User
    {
        static $seq = 800;
        $seq++;
        return User::create(array_merge([
            'name'         => 'Audit User ' . $seq,
            'email'        => "audit_{$seq}@example.com",
            'password'     => bcrypt('secret'),
            'id_emp'       => 'E' . $seq,
            'id_user'      => 'U' . $seq,
            'id_org_unit'  => 'OU_01',
            'team'         => 'Team Audit',
            'department'   => 'Dept Audit',
            'division'     => 'Division Audit',
            'sap_code'     => 'S' . $seq,
            'ssohash'      => 'H' . $seq,
            'role'         => User::ROLE_USER,
            'company_name' => 'PT Krakatau Posco',
        ], $attrs));
    }

    /**
     * Test Unit Level: ExcelDateTimeParser specifications.
     */
    public function test_excel_date_time_parser_handles_all_prescribed_scenarios(): void
    {
        // 1. Excel Serial Date
        $res46023 = ExcelDateTimeParser::parseDateResult(46023);
        $this->assertTrue($res46023['valid']);
        $this->assertEquals('2026-01-01', $res46023['value']);

        $res46052 = ExcelDateTimeParser::parseDateResult(46052);
        $this->assertTrue($res46052['valid']);
        $this->assertEquals('2026-01-30', $res46052['value']);

        // Combined datetime serial
        $resCombDate = ExcelDateTimeParser::parseDateResult(46023.333333333);
        $this->assertTrue($resCombDate['valid']);
        $this->assertEquals('2026-01-01', $resCombDate['value']);

        // 2. Excel Fractional Time
        $res033 = ExcelDateTimeParser::parseTimeResult(0.333333333);
        $this->assertTrue($res033['valid']);
        $this->assertEquals('08:00:00', $res033['value']);

        $res070 = ExcelDateTimeParser::parseTimeResult(0.708333333);
        $this->assertTrue($res070['valid']);
        $this->assertEquals('17:00:00', $res070['value']);

        $resCombTime = ExcelDateTimeParser::parseTimeResult(46023.333333333);
        $this->assertTrue($resCombTime['valid']);
        $this->assertEquals('08:00:00', $resCombTime['value']);

        // 3. Formatted Date and Time Strings
        $this->assertEquals('2026-01-01', ExcelDateTimeParser::parseDate('2026-01-01'));
        $this->assertEquals('2026-01-30', ExcelDateTimeParser::parseDate('2026/01/30'));
        $this->assertEquals('2026-01-30', ExcelDateTimeParser::parseDate('30/01/2026'));
        $this->assertEquals('2026-08-10', ExcelDateTimeParser::parseDate('10 Aug 2026'));

        $this->assertEquals('08:00:00', ExcelDateTimeParser::parseTime('08:00:00'));
        $this->assertEquals('08:00:00', ExcelDateTimeParser::parseTime('08:00'));
        $this->assertEquals('08:00:00', ExcelDateTimeParser::parseTime('08.00'));
        $this->assertEquals('08:00:00', ExcelDateTimeParser::parseTime('8:00 AM'));
        $this->assertEquals('17:00:00', ExcelDateTimeParser::parseTime('5:00 PM'));

        // 4. Empty Date and Time
        $emptyDateRes = ExcelDateTimeParser::parseDateResult('');
        $this->assertTrue($emptyDateRes['valid']);
        $this->assertTrue($emptyDateRes['is_empty']);
        $this->assertNull($emptyDateRes['value']);

        $dashDateRes = ExcelDateTimeParser::parseDateResult('-');
        $this->assertTrue($dashDateRes['valid']);
        $this->assertTrue($dashDateRes['is_empty']);
        $this->assertNull($dashDateRes['value']);

        $emptyTimeRes = ExcelDateTimeParser::parseTimeResult(null);
        $this->assertTrue($emptyTimeRes['valid']);
        $this->assertTrue($emptyTimeRes['is_empty']);
        $this->assertNull($emptyTimeRes['value']);

        $naTimeRes = ExcelDateTimeParser::parseTimeResult('n/a');
        $this->assertTrue($naTimeRes['valid']);
        $this->assertTrue($naTimeRes['is_empty']);
        $this->assertNull($naTimeRes['value']);

        // 5. Invalid Date & Time rejection (no silent rollover)
        $invDateRes = ExcelDateTimeParser::parseDateResult('2026-02-31');
        $this->assertFalse($invDateRes['valid']);
        $this->assertNull($invDateRes['value']);
        $this->assertNotNull($invDateRes['error']);

        $invTimeRes = ExcelDateTimeParser::parseTimeResult('25:00:00');
        $this->assertFalse($invTimeRes['valid']);
        $this->assertNull($invTimeRes['value']);
        $this->assertNotNull($invTimeRes['error']);

        $invMinTimeRes = ExcelDateTimeParser::parseTimeResult('08:65:00');
        $this->assertFalse($invMinTimeRes['valid']);
        $this->assertNull($invMinTimeRes['value']);
    }

    /**
     * Test Full Import Workflow: Preview, Mapping, Validation, and Persistence to offline_training_users.
     */
    public function test_ptkp_import_with_excel_serials_persists_to_offline_training_users(): void
    {
        $file = $this->createCustomPtkpSpreadsheet([
            [
                'employee_id'    => $this->targetEmployee->id_emp,
                'course_name'    => 'Power Transformer Testing & Diagnostics',
                'category'       => 'Technical Competency',
                'batch'          => 'Batch 2026-A',
                'venue'          => 'Substation Workshop',
                'learning_hours' => 16.0,
                'progress'       => 100,
                'start_date'     => 46023, // Excel Serial 2026-01-01
                'end_date'       => 46052, // Excel Serial 2026-01-30
                'start_time'     => 0.3333333333333333, // Excel Serial 08:00:00
                'end_time'       => 0.7083333333333334, // Excel Serial 17:00:00
                'attendance'     => 'Yes',
                'pic'            => 'Audit Admin',
            ],
            [
                'employee_id'    => $this->targetEmployee->id_emp,
                'course_name'    => 'High Voltage Safety Fundamentals',
                'category'       => 'Safety Competency',
                'batch'          => 'Batch 2026-B',
                'venue'          => 'Main Auditorium',
                'learning_hours' => 8.0,
                'progress'       => 100,
                'start_date'     => '2026-02-15',
                'end_date'       => '2026-02-16',
                'start_time'     => '',   // Empty time -> should store NULL in offline_training_users
                'end_time'       => '-',  // Empty placeholder -> should store NULL in offline_training_users
                'attendance'     => 'Yes',
                'pic'            => 'Audit Admin',
            ],
            [
                'employee_id'    => $this->targetEmployee->id_emp,
                'course_name'    => 'Night Shift Emergency Response',
                'category'       => 'Safety Competency',
                'batch'          => 'Batch 2026-C',
                'venue'          => 'Plant Control Room',
                'learning_hours' => 4.0,
                'progress'       => 100,
                'start_date'     => '2026-03-01',
                'end_date'       => '2026-03-02',
                'start_time'     => '22:00:00', // Midnight crossing
                'end_time'       => '02:00:00',
                'attendance'     => 'Yes',
                'pic'            => 'Audit Admin',
            ],
        ]);

        // 1. Preview
        $previewResp = $this->actingAs($this->adminUser)
            ->post(route('manage.record.offline.import-ptkp.preview'), ['file' => $file]);

        $previewResp->assertStatus(200);
        $pJson = $previewResp->json();
        $this->assertTrue($pJson['success']);
        $this->assertTrue($pJson['can_confirm']);
        $this->assertEquals(3, $pJson['stats']['importable_count']);

        // Verify preview row 0 (Excel serial date & fractional time)
        $row0 = $pJson['rows'][0];
        $this->assertEquals('2026-01-01', $row0['data']['start_date']);
        $this->assertEquals('2026-01-30', $row0['data']['end_date']);
        $this->assertEquals('08:00:00', $row0['data']['start_time']);
        $this->assertEquals('17:00:00', $row0['data']['end_time']);

        // Verify preview row 1 (Empty times parsed as NULL)
        $row1 = $pJson['rows'][1];
        $this->assertTrue(in_array($row1['status'], ['VALID', 'WARNING']));
        $this->assertEquals('2026-02-15', $row1['data']['start_date']);
        $this->assertEquals('2026-02-16', $row1['data']['end_date']);
        $this->assertNull($row1['data']['start_time']);
        $this->assertNull($row1['data']['end_time']);

        // Verify preview row 2 (Midnight crossing)
        $row2 = $pJson['rows'][2];
        $this->assertTrue(in_array($row2['status'], ['VALID', 'WARNING']));
        $this->assertEquals('22:00:00', $row2['data']['start_time']);
        $this->assertEquals('02:00:00', $row2['data']['end_time']);
        $this->assertEquals(14400, $row2['data']['duration_seconds']); // 4 hours in seconds

        $token = $pJson['token'];

        // 2. Confirm Import
        $confirmResp = $this->actingAs($this->adminUser)
            ->post(route('manage.record.offline.import-ptkp.confirm'), ['token' => $token]);

        $confirmResp->assertStatus(200);
        $cJson = $confirmResp->json();
        $this->assertTrue($cJson['success']);
        $this->assertEquals(3, $cJson['result']['imported']);

        // 3. Direct Database Verification in offline_training_users table
        $otu0 = OfflineTrainingUser::where('employee_id', $this->targetEmployee->id_emp)
            ->whereHas('training', fn($q) => $q->where('course_name', 'Power Transformer Testing & Diagnostics'))
            ->first();

        $this->assertNotNull($otu0);
        $this->assertEquals('2026-01-01', $otu0->start_date->format('Y-m-d'));
        $this->assertEquals('2026-01-30', $otu0->end_date->format('Y-m-d'));
        $this->assertEquals('08:00:00', $otu0->start_time);
        $this->assertEquals('17:00:00', $otu0->end_time);

        // Verify row 1 empty times are stored as NULL in offline_training_users
        $otu1 = OfflineTrainingUser::where('employee_id', $this->targetEmployee->id_emp)
            ->whereHas('training', fn($q) => $q->where('course_name', 'High Voltage Safety Fundamentals'))
            ->first();

        $this->assertNotNull($otu1);
        $this->assertEquals('2026-02-15', $otu1->start_date->format('Y-m-d'));
        $this->assertEquals('2026-02-16', $otu1->end_date->format('Y-m-d'));
        $this->assertNull($otu1->start_time);
        $this->assertNull($otu1->end_time);

        // Verify row 2 midnight crossing in offline_training_users
        $otu2 = OfflineTrainingUser::where('employee_id', $this->targetEmployee->id_emp)
            ->whereHas('training', fn($q) => $q->where('course_name', 'Night Shift Emergency Response'))
            ->first();

        $this->assertNotNull($otu2);
        $this->assertEquals('2026-03-01', $otu2->start_date->format('Y-m-d'));
        $this->assertEquals('2026-03-02', $otu2->end_date->format('Y-m-d'));
        $this->assertEquals('22:00:00', $otu2->start_time);
        $this->assertEquals('02:00:00', $otu2->end_time);
    }

    /**
     * Test Validation Error Feedback: Row Number, Employee ID, Column Name, and Original Value.
     */
    public function test_validation_errors_provide_detailed_context_and_original_value(): void
    {
        $file = $this->createCustomPtkpSpreadsheet([
            [
                'employee_id' => $this->targetEmployee->id_emp,
                'course_name' => 'Invalid Date Course',
                'start_date'  => '2026-02-31', // Invalid day
                'end_date'    => '2026-02-30',
                'start_time'  => '25:00:00',   // Invalid hour
                'end_time'    => '17:00:00',
            ],
            [
                'employee_id' => $this->targetEmployee->id_emp,
                'course_name' => 'Inverted Dates Course',
                'start_date'  => '2026-05-20', // Start after End
                'end_date'    => '2026-05-10',
                'start_time'  => '08:00:00',
                'end_time'    => '17:00:00',
            ],
        ]);

        $response = $this->actingAs($this->adminUser)
            ->post(route('manage.record.offline.import-ptkp.preview'), ['file' => $file]);

        $response->assertStatus(200);
        $json = $response->json();

        // Row 0 has invalid date and invalid time -> WARNING (never INVALID per strict 2 mandatory criteria)
        $r0 = $json['rows'][0];
        $this->assertEquals('WARNING', $r0['status']);
        $this->assertFalse($r0['is_blocking']);
        $msgStr0 = implode(' ', $r0['messages']);
        $this->assertStringContainsString('Start Date', $msgStr0);
        $this->assertStringContainsString('2026-02-31', $msgStr0);
        $this->assertStringContainsString('Start Time', $msgStr0);
        $this->assertStringContainsString('25:00:00', $msgStr0);
        $this->assertStringContainsString($this->targetEmployee->id_emp, $msgStr0);

        // Row 1 has Start Date after End Date -> WARNING (never INVALID per strict 2 mandatory criteria)
        $r1 = $json['rows'][1];
        $this->assertEquals('WARNING', $r1['status']);
        $this->assertFalse($r1['is_blocking']);
        $msgStr1 = implode(' ', $r1['messages']);
        $this->assertStringContainsString('cannot be after End Date', $msgStr1);
    }

    /**
     * Helper to create a test PTKP spreadsheet in memory and return as UploadedFile.
     */
    protected function createCustomPtkpSpreadsheet(array $rows): UploadedFile
    {
        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle(PtkpExcelSchema::DATA_SHEET_TITLE);

        $cols = PtkpExcelSchema::columns(); // 46 columns

        // Write row 1 headers: Col A = NO, Cols B..AU = canonical headers
        $sheet->setCellValue('A1', 'NO');
        foreach ($cols as $idx => $col) {
            $letter = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($idx + 1);
            $sheet->setCellValue("{$letter}1", $col['header']);
        }

        // Write data rows
        foreach ($rows as $rIdx => $data) {
            $rowNum = $rIdx + 2;
            $sheet->setCellValue("A{$rowNum}", $rIdx + 1);

            foreach ($cols as $idx => $col) {
                $letter = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($idx + 1);
                $key = $col['key'];
                if (array_key_exists($key, $data)) {
                    $val = $data[$key];
                    if (is_numeric($val)) {
                        $sheet->setCellValueExplicit("{$letter}{$rowNum}", $val, DataType::TYPE_NUMERIC);
                    } elseif ($val === null || $val === '') {
                        $sheet->setCellValueExplicit("{$letter}{$rowNum}", '', DataType::TYPE_STRING);
                    } else {
                        $sheet->setCellValueExplicit("{$letter}{$rowNum}", (string) $val, DataType::TYPE_STRING);
                    }
                } else {
                    $sheet->setCellValueExplicit("{$letter}{$rowNum}", (string) ($col['example'] ?? ''), DataType::TYPE_STRING);
                }
            }
        }

        $tempPath = tempnam(sys_get_temp_dir(), 'ptkp_audit_test_') . '.xlsx';
        $writer = new Xlsx($spreadsheet);
        $writer->save($tempPath);
        $spreadsheet->disconnectWorksheets();

        return new UploadedFile(
            $tempPath,
            'audit_test.xlsx',
            'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            null,
            true
        );
    }

    /**
     * Test actual FIXS TEMPLATE PTKP.xlsx file from the project.
     */
    public function test_actual_fixs_template_excel_file_date_and_time_preview(): void
    {
        ini_set('memory_limit', '1536M');
        $realPath = base_path('tests/file/FIXS TEMPLATE PTKP.xlsx');
        if (!file_exists($realPath)) {
            $this->markTestSkipped('FIXS TEMPLATE PTKP.xlsx not found.');
        }

        // Copy to temporary file to avoid moving/deleting original
        $tempPath = tempnam(sys_get_temp_dir(), 'real_fixs_') . '.xlsx';
        copy($realPath, $tempPath);

        $uploadedFile = new UploadedFile(
            $tempPath,
            'FIXS TEMPLATE PTKP.xlsx',
            'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            null,
            true
        );

        $service = app(PtkpImportService::class);
        $meta = $service->storeTemporaryFile($uploadedFile, $this->adminUser->id);
        $preview = $service->parseAndPreview($meta['token'], $this->adminUser->id);

        $this->assertTrue($preview['header_check']['can_proceed']);
        $this->assertGreaterThan(0, count($preview['rows']));

        // Verify Row 1: Start Date 46023 -> 2026-01-01, End Date 46052 -> 2026-01-30, Start Time 0.333333333 -> 08:00:00, End Time 0.708333333 -> 17:00:00
        $r1 = $preview['rows'][0];
        $this->assertEquals('2026-01-01', $r1['data']['start_date']);
        $this->assertEquals('2026-01-30', $r1['data']['end_date']);
        $this->assertEquals('08:00:00', $r1['data']['start_time']);
        $this->assertEquals('17:00:00', $r1['data']['end_time']);
        $this->assertFalse($r1['data']['start_date_invalid']);
        $this->assertFalse($r1['data']['start_time_invalid']);

        // Verify Row 3 (Excel row 4): Empty time columns parsed as NULL
        $r3 = $preview['rows'][2];
        $this->assertEquals('2026-01-01', $r3['data']['start_date']);
        $this->assertEquals('2026-01-30', $r3['data']['end_date']);
        $this->assertNull($r3['data']['start_time']);
        $this->assertNull($r3['data']['end_time']);

        $service->cleanupTemporaryFile($meta['token'], $this->adminUser->id);
    }

    /**
     * Test actual PTKP_OWL_Import_Template (1).xlsx file from the project.
     */
    public function test_actual_ptkp_owl_import_template_file_preview(): void
    {
        $realPath = base_path('tests/file/PTKP_OWL_Import_Template (1).xlsx');
        if (!file_exists($realPath)) {
            $this->markTestSkipped('PTKP_OWL_Import_Template (1).xlsx not found.');
        }

        $tempPath = tempnam(sys_get_temp_dir(), 'real_ptkp_tmpl_') . '.xlsx';
        copy($realPath, $tempPath);

        $uploadedFile = new UploadedFile(
            $tempPath,
            'PTKP_OWL_Import_Template (1).xlsx',
            'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            null,
            true
        );

        $service = app(PtkpImportService::class);
        $meta = $service->storeTemporaryFile($uploadedFile, $this->adminUser->id);
        $preview = $service->parseAndPreview($meta['token'], $this->adminUser->id);

        $this->assertTrue($preview['header_check']['can_proceed']);
        $this->assertGreaterThan(0, count($preview['rows']));

        $r1 = $preview['rows'][0];
        $this->assertEquals('2026-08-10', $r1['data']['start_date']);
        $this->assertEquals('2026-08-10', $r1['data']['end_date']);
        $this->assertEquals('08:00:00', $r1['data']['start_time']);
        $this->assertEquals('17:00:00', $r1['data']['end_time']);

        $service->cleanupTemporaryFile($meta['token'], $this->adminUser->id);
    }

    /**
     * Test timezone invariance: Excel fractional time must not be shifted by application or server timezone.
     */
    public function test_timezone_invariance_for_training_time(): void
    {
        // 0.333333333 must be exactly 08:00:00 local time
        $time8 = ExcelDateTimeParser::parseTime(0.333333333);
        $this->assertEquals('08:00:00', $time8);

        // 0.708333333 must be exactly 17:00:00 local time
        $time17 = ExcelDateTimeParser::parseTime(0.708333333);
        $this->assertEquals('17:00:00', $time17);

        // String time must not change
        $this->assertEquals('08:00:00', ExcelDateTimeParser::parseTime('08:00:00'));
        $this->assertEquals('17:00:00', ExcelDateTimeParser::parseTime('17:00:00'));
    }

    /**
     * Test SQL direct query on offline_training_users as required by Phase 7 verification.
     */
    public function test_sql_direct_query_on_offline_training_users(): void
    {
        if (empty(\Illuminate\Support\Facades\DB::select('SELECT id FROM offline_training_users LIMIT 1'))) {
            $tr = \App\Models\OfflineTraining::create([
                'title' => 'Test SQL Direct ' . uniqid(),
                'company' => 'PT Krakatau Posco',
                'created_by' => $this->adminUser->id,
            ]);
            \App\Models\OfflineTrainingUser::create([
                'offline_training_id' => $tr->id,
                'user_id' => $this->targetEmployee->id,
                'employee_id' => $this->targetEmployee->id_emp,
                'start_date' => '2026-08-10',
                'end_date' => '2026-08-10',
                'start_time' => '08:00:00',
                'end_time' => '17:00:00',
            ]);
        }

        $rows = \Illuminate\Support\Facades\DB::select('
            SELECT
                id,
                employee_id,
                start_date,
                end_date,
                start_time,
                end_time
            FROM offline_training_users
            LIMIT 20
        ');

        $this->assertNotEmpty($rows);

        foreach ($rows as $row) {
            if ($row->start_date !== null) {
                $this->assertMatchesRegularExpression('/^\d{4}-\d{2}-\d{2}$/', $row->start_date);
            }
            if ($row->end_date !== null) {
                $this->assertMatchesRegularExpression('/^\d{4}-\d{2}-\d{2}$/', $row->end_date);
            }
            if ($row->start_time !== null) {
                $this->assertMatchesRegularExpression('/^\d{2}:\d{2}:\d{2}$/', $row->start_time);
            }
            if ($row->end_time !== null) {
                $this->assertMatchesRegularExpression('/^\d{2}:\d{2}:\d{2}$/', $row->end_time);
            }
        }
    }
    /**
     * Test Standardization of all 5 date columns: Parsing, Strict Day-First, Ambiguity, and Placeholders.
     */
    public function test_standardized_five_date_columns_parsing_and_ambiguity(): void
    {
        // 1. Excel Serial Date 46244 -> 2026-08-10
        $resSerial = ExcelDateTimeParser::parseDateResult(46244);
        $this->assertTrue($resSerial['valid']);
        $this->assertEquals('2026-08-10', $resSerial['value']);

        // 2. DD/MM/YYYY string -> 2026-08-10
        $resDmy = ExcelDateTimeParser::parseDateResult('10/08/2026');
        $this->assertTrue($resDmy['valid']);
        $this->assertEquals('2026-08-10', $resDmy['value']);

        // 3. Strict Day-First Ambiguous Date: 05/06/2026 MUST be 2026-06-05 (5 June, NOT 6 May)
        $resAmb = ExcelDateTimeParser::parseDateResult('05/06/2026');
        $this->assertTrue($resAmb['valid']);
        $this->assertEquals('2026-06-05', $resAmb['value']);

        // 4. DD/MM/YY string -> 2026-08-10
        $res2Digit = ExcelDateTimeParser::parseDateResult('10/08/26');
        $this->assertTrue($res2Digit['valid']);
        $this->assertEquals('2026-08-10', $res2Digit['value']);

        // 5. Strict Day-First Ambiguous 2-digit Year: 05/06/26 MUST be 2026-06-05
        $resAmb2Digit = ExcelDateTimeParser::parseDateResult('05/06/26');
        $this->assertTrue($resAmb2Digit['valid']);
        $this->assertEquals('2026-06-05', $resAmb2Digit['value']);

        // 6. DateTimeInterface & Carbon instance
        $carbon = \Carbon\Carbon::create(2026, 8, 10);
        $resCarbon = ExcelDateTimeParser::parseDateResult($carbon);
        $this->assertTrue($resCarbon['valid']);
        $this->assertEquals('2026-08-10', $resCarbon['value']);

        // 7. Empty Cell & Placeholder
        $resEmpty = ExcelDateTimeParser::parseDateResult('');
        $this->assertTrue($resEmpty['valid']);
        $this->assertTrue($resEmpty['is_empty']);
        $this->assertNull($resEmpty['value']);

        $resDash = ExcelDateTimeParser::parseDateResult('-');
        $this->assertTrue($resDash['valid']);
        $this->assertTrue($resDash['is_empty']);
        $this->assertNull($resDash['value']);

        // 8. Invalid calendar date: 31/02/2026
        $resFeb31 = ExcelDateTimeParser::parseDateResult('31/02/2026');
        $this->assertFalse($resFeb31['valid']);
        $this->assertNull($resFeb31['value']);
        $this->assertStringContainsString('Invalid calendar date', $resFeb31['error']);

        // 9. Invalid calendar date: 35/08/2026
        $resDay35 = ExcelDateTimeParser::parseDateResult('35/08/2026');
        $this->assertFalse($resDay35['valid']);
        $this->assertNull($resDay35['value']);

        // 10. Invalid calendar date: 15/15/2026
        $resMon15 = ExcelDateTimeParser::parseDateResult('15/15/2026');
        $this->assertFalse($resMon15['valid']);
        $this->assertNull($resMon15['value']);

        // 11. formatDisplayDate helper
        $this->assertEquals('10/08/2026', ExcelDateTimeParser::formatDisplayDate('2026-08-10'));
        $this->assertEquals('05/06/2026', ExcelDateTimeParser::formatDisplayDate('2026-06-05'));
        $this->assertNull(ExcelDateTimeParser::formatDisplayDate(null));
        $this->assertNull(ExcelDateTimeParser::formatDisplayDate('-'));
    }

    /**
     * Test Excel Template generation has dd/mm/yyyy number format on Z, AA, AJ, AS, AT.
     */
    public function test_excel_template_has_ddmmyyyy_number_formatting_on_all_five_date_columns(): void
    {
        $exporter = new \App\Exports\Manage\PtkpImportTemplateExport();
        $spreadsheet = $exporter->buildSpreadsheet();
        $sheet = $spreadsheet->getActiveSheet();

        $dateCols = [
            'Z'  => '10/08/2026',
            'AA' => '10/08/2026',
            'AJ' => '10/08/2028',
            'AS' => '10/08/2026',
            'AT' => '10/08/2029',
        ];

        foreach ($dateCols as $col => $expectedFormatted) {
            $cell = $sheet->getCell("{$col}2");
            $this->assertEquals('dd/mm/yyyy', $cell->getStyle()->getNumberFormat()->getFormatCode(), "Col {$col} must have dd/mm/yyyy format code.");
            $this->assertEquals($expectedFormatted, $cell->getFormattedValue(), "Col {$col} Row 2 must display {$expectedFormatted}.");
            $this->assertTrue(\PhpOffice\PhpSpreadsheet\Shared\Date::isDateTime($cell), "Col {$col} Row 2 must contain a valid Excel date.");

            // Empty row 3
            $emptyCell = $sheet->getCell("{$col}3");
            $this->assertEquals('dd/mm/yyyy', $emptyCell->getStyle()->getNumberFormat()->getFormatCode());
            $this->assertNull($emptyCell->getValue());
        }

        $spreadsheet->disconnectWorksheets();
    }

    /**
     * Test persistence of all five date columns to offline_training_users.
     */
    public function test_all_five_date_columns_persisted_to_offline_training_users(): void
    {
        $uniqueCourse = 'Date Standardization Test Course ' . uniqid();
        $file = $this->createCustomPtkpSpreadsheet([
            [
                'employee_id'              => $this->targetEmployee->id_emp,
                'course_name'              => $uniqueCourse,
                'category'                 => 'Technical Competency',
                'batch'                    => 'Batch Date-1',
                'venue'                    => 'Substation Workshop',
                'learning_hours'           => 8.0,
                'progress'                 => 100,
                'start_date'               => '10/08/2026',
                'end_date'                 => '10/08/2026',
                'start_time'               => '08:00:00',
                'end_time'                 => '16:00:00',
                'attendance'               => 'Yes',
                'bond_status'              => 'Yes',
                'bond_duration'            => 24,
                'bond_end_date'            => '10/08/2028',
                'bond_amount'              => 15000000,
                'certificate_availability' => 'Yes',
                'certificate_institution'  => 'BNSP',
                'certificate_issued_date'  => '10/08/2026',
                'certificate_expired_date' => '10/08/2029',
                'pic'                      => 'PIC QA',
            ],
        ]);

        $uploadedFile = new \Illuminate\Http\UploadedFile(
            $file,
            'test_5_dates.xlsx',
            'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            null,
            true
        );

        $meta = $this->importService->storeTemporaryFile($uploadedFile, $this->adminUser->id);
        $preview = $this->importService->parseAndPreview($meta['token'], $this->adminUser->id);

        $this->assertEquals(1, $preview['stats']['importable_count']);
        $pRow = $preview['rows'][0];
        $this->assertEquals('10/08/2026', $pRow['data']['start_date_display']);
        $this->assertEquals('10/08/2026', $pRow['data']['end_date_display']);
        $this->assertEquals('10/08/2028', $pRow['data']['bond_end_date_display']);
        $this->assertEquals('10/08/2026', $pRow['data']['certificate_issued_date_display']);
        $this->assertEquals('10/08/2029', $pRow['data']['certificate_expired_date_display']);

        $result = $this->importService->executeImport($meta['token'], $this->adminUser->id);
        $this->assertEquals(1, $result['imported']);

        // Verify database persistence in offline_training_users
        $participant = \App\Models\OfflineTrainingUser::where('employee_id', $this->targetEmployee->id_emp)
            ->whereHas('training', fn ($q) => $q->where('course_name', $uniqueCourse))
            ->first();

        $this->assertNotNull($participant);
        $this->assertEquals('2026-08-10', $participant->start_date->format('Y-m-d'));
        $this->assertEquals('2026-08-10', $participant->end_date->format('Y-m-d'));
        $this->assertEquals('2028-08-10', $participant->bond_end_date->format('Y-m-d'));
        $this->assertEquals('2026-08-10', $participant->certificate_issued_date->format('Y-m-d'));
        $this->assertEquals('2029-08-10', $participant->certificate_expired_date->format('Y-m-d'));

        // Direct SQL check
        $dbRow = \Illuminate\Support\Facades\DB::selectOne('
            SELECT
                id,
                employee_id,
                start_date,
                end_date,
                bond_end_date,
                certificate_issued_date,
                certificate_expired_date
            FROM offline_training_users
            WHERE id = ?
        ', [$participant->id]);

        $this->assertEquals('2026-08-10', $dbRow->start_date);
        $this->assertEquals('2026-08-10', $dbRow->end_date);
        $this->assertEquals('2028-08-10', $dbRow->bond_end_date);
        $this->assertEquals('2026-08-10', $dbRow->certificate_issued_date);
        $this->assertEquals('2029-08-10', $dbRow->certificate_expired_date);

        // Clean up
        $participant->forceDelete();
        \App\Models\OfflineTraining::where('course_name', $uniqueCourse)->forceDelete();
    }

    /**
     * Test certificate date sequence validation: Issued Date cannot be after Expired Date.
     */
    public function test_certificate_issued_date_cannot_be_after_expired_date(): void
    {
        $uniqueCourse = 'Cert Date Sequence Test Course ' . uniqid();
        $file = $this->createCustomPtkpSpreadsheet([
            [
                'employee_id'              => $this->targetEmployee->id_emp,
                'course_name'              => $uniqueCourse,
                'start_date'               => '10/08/2026',
                'end_date'                 => '10/08/2026',
                'certificate_issued_date'  => '10/08/2029', // 2029
                'certificate_expired_date' => '10/08/2026', // 2026 (issued after expired!)
            ],
        ]);

        $uploadedFile = new \Illuminate\Http\UploadedFile(
            $file,
            'test_cert_seq.xlsx',
            'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            null,
            true
        );

        $meta = $this->importService->storeTemporaryFile($uploadedFile, $this->adminUser->id);
        $preview = $this->importService->parseAndPreview($meta['token'], $this->adminUser->id);

        $this->assertEquals(1, $preview['stats']['importable_count']);
        $this->assertEquals(1, $preview['stats']['warning']);
        $pRow = $preview['rows'][0];
        $this->assertEquals('WARNING', $pRow['status']);
        $this->assertFalse($pRow['is_blocking']);

        $foundCertMsg = false;
        foreach ($pRow['messages'] as $msg) {
            if (str_contains($msg, 'Certificate Issued Date') && str_contains($msg, 'cannot be after Certificate Expired Date')) {
                $foundCertMsg = true;
                break;
            }
        }
        $this->assertTrue($foundCertMsg, 'Expected validation error message for Certificate Issued Date after Expired Date.');
    }
}
