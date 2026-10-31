<?php

namespace Tests\Feature;

use App\Models\ImportBatch;
use App\Models\ImportBatchRow;
use App\Models\OfflineTraining;
use App\Models\OfflineTrainingUser;
use App\Models\User;
use App\Services\Record\OfflineTrainingExcelSchema;
use App\Services\Record\OfflineTrainingImportService;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Tests\TestCase;

class NonPtkpImportTest extends TestCase
{
    protected ?User $admin = null;
    protected ?User $regularUser = null;
    protected array $createdTempFiles = [];

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::whereIn('role', [User::ROLE_ADMIN, User::ROLE_SUPER_ADMIN])->first()
            ?? $this->createTestUser(['role' => User::ROLE_ADMIN]);

        $this->regularUser = User::where('role', User::ROLE_USER)->first()
            ?? $this->createTestUser(['role' => User::ROLE_USER]);

        // Clean up any test records
        DB::table('offline_training_users')->where('company', 'TEST_NON_PTKP_CORP')->delete();
        DB::table('offline_trainings')->where('category', 'TEST_NON_PTKP_CAT')->delete();
        DB::table('users')->where('email', 'like', 'nonptkp_%@example.com')->delete();
        DB::table('users')->where('email', 'like', 'nonptkp_%@example.com')->delete();
    }

    protected function tearDown(): void
    {
        DB::table('offline_training_users')->where('company', 'TEST_NON_PTKP_CORP')->delete();
        DB::table('offline_trainings')->where('category', 'TEST_NON_PTKP_CAT')->delete();
        DB::table('users')->where('email', 'like', 'nonptkp_%@example.com')->delete();

        foreach ($this->createdTempFiles as $path) {
            if (File::exists($path)) {
                File::delete($path);
            }
        }

        parent::tearDown();
    }

    protected function createTestUser(array $attrs = []): User
    {
        static $seq = 6000;
        $seq++;
        return User::create(array_merge([
            'name'         => 'Non PTKP User ' . $seq,
            'email'        => "nonptkp_{$seq}@example.com",
            'password'     => bcrypt('secret'),
            'id_emp'       => 'NP_' . $seq,
            'id_user'      => 'U_' . $seq,
            'id_org_unit'  => 'OU_01',
            'team'         => 'Team Rolling',
            'department'   => 'Dept Rolling',
            'division'     => 'Division Production',
            'sap_code'     => 'S_' . $seq,
            'ssohash'      => 'H_' . $seq,
            'role'         => User::ROLE_USER,
            'company_name' => 'PT Krakatau Posco',
        ], $attrs));
    }

    protected function buildNonPtkpRow(array $overrides = []): array
    {
        $m = array_merge([
            'employee_id'              => '100999',
            'name'                     => 'John Doe Non PTKP',
            'grade'                    => '4A',
            'job_position'             => 'Plant Engineer',
            'team'                     => 'Mill Team',
            'department'               => 'Maintenance Dept',
            'company'                  => 'TEST_NON_PTKP_CORP',
            'course_name'              => 'Non PTKP Safety Standard',
            'sub_course_name'          => 'Module 1 - Risk Assessment',
            'type'                     => 'In-House',
            'category'                 => 'TEST_NON_PTKP_CAT',
            'level'                    => 'Intermediate',
            'batch'                    => 'Batch 01',
            'main_classification'      => 'Basic & Common Duties',
            'sub_classification'       => 'Plant Safety',
            'delivery_method'          => 'Offline',
            'sub_delivery_method'      => 'On-site',
            'provider'                 => 'External',
            'sub_provider'             => 'Vendor',
            'provider_name'            => 'PT Safety First',
            'venue'                    => 'Room A-101',
            'instructor'               => 'Ir. Instructor',
            'learning_hours'           => 8.0,
            'progress'                 => 100,
            'start_date'               => '2026-09-10',
            'end_date'                 => '2026-09-10',
            'start_time'               => '08:00:00',
            'end_time'                 => '17:00:00',
            'month'                    => 'September',
            'requirement'              => 'Mandatory',
            'attendance'               => 'Yes',
            'attendance_reason'        => '',
            'bond_status'              => 'No',
            'bond_duration'            => '',
            'bond_end_date'            => '',
            'bond_amount'              => '',
            'l1_program'               => 'Very Good',
            'assessment_score'         => 90,
            'l2_verbal'                => 'Passed',
            'l3_numerical'             => 85,
            'l3_verbal'                => 'Good Implementation',
            'certificate_availability' => 'Yes',
            'certificate_institution'  => 'Safety Body',
            'certificate_issued_date'  => '2026-09-11',
            'certificate_expired_date' => '2028-09-11',
            'pic'                      => 'HR Training Team',
        ], $overrides);

        return [
            $m['employee_id'],
            $m['name'],
            $m['grade'],
            $m['job_position'],
            $m['team'],
            $m['department'],
            $m['company'],
            $m['course_name'],
            $m['sub_course_name'],
            $m['type'],
            $m['category'],
            $m['level'],
            $m['batch'],
            $m['main_classification'],
            $m['sub_classification'],
            $m['delivery_method'],
            $m['sub_delivery_method'],
            $m['provider'],
            $m['sub_provider'],
            $m['provider_name'],
            $m['venue'],
            $m['instructor'],
            $m['learning_hours'],
            $m['progress'],
            $m['start_date'],
            $m['end_date'],
            $m['start_time'],
            $m['end_time'],
            $m['month'],
            $m['requirement'],
            $m['attendance'],
            $m['attendance_reason'],
            $m['bond_status'],
            $m['bond_duration'],
            $m['bond_end_date'],
            $m['bond_amount'],
            $m['l1_program'],
            $m['assessment_score'],
            $m['l2_verbal'],
            $m['l3_numerical'],
            $m['l3_verbal'],
            $m['certificate_availability'],
            $m['certificate_institution'],
            $m['certificate_issued_date'],
            $m['certificate_expired_date'],
            $m['pic'],
        ];
    }

    protected function buildLegacyNonPtkpRow(array $overrides = []): array
    {
        $m = array_merge([
            'employee_id'            => '100999',
            'name'                   => 'John Doe Non PTKP',
            'grade'                  => '4A',
            'job_position'           => 'Plant Engineer',
            'team'                   => 'Mill Team',
            'department'             => 'Maintenance Dept',
            'company'                => 'TEST_NON_PTKP_CORP',
            'course_name'            => 'Non PTKP Safety Standard',
            'sub_course_name'        => 'Module 1 - Risk Assessment',
            'type'                   => 'In-House',
            'category'               => 'TEST_NON_PTKP_CAT',
            'level'                  => 'Intermediate',
            'batch'                  => 'Batch 01',
            'main_classification'    => 'Basic & Common Duties',
            'sub_classification'     => 'Plant Safety',
            'delivery_method'        => 'Offline',
            'sub_delivery_method'    => 'On-site',
            'provider_name'          => 'PT Safety First',
            'venue'                  => 'Room A-101',
            'instructor'             => 'Ir. Instructor',
            'duration_seconds'       => '08:00:00',
            'learning_hours'         => 8.0,
            'progress'               => 100,
            'progress_status'        => 'Finished',
            'start_date'             => '2026-09-10',
            'end_date'               => '2026-09-10',
            'start_time'             => '08:00:00',
            'end_time'               => '17:00:00',
            'month'                  => 'September',
            'requirement'            => 'Mandatory',
            'attendance'             => 'Yes',
            'attendance_reason'      => '',
            'bond_status'            => 'No',
            'bond_duration'          => '',
            'bond_end_date'          => '',
            'bond_amount'            => '',
            'l1_program'             => 'Very Good',
            'assessment_score'       => 90,
            'l2_verbal'              => 'Passed',
            'l3_numerical'           => 85,
            'l3_verbal'              => 'Good Implementation',
            'pic'                    => 'HR Training Team',
            'certificate'            => 'Yes',
            'total_engagement_time'  => 8.0,
        ], $overrides);

        return [
            $m['employee_id'], $m['name'], $m['grade'], $m['job_position'], $m['team'], $m['department'], $m['company'],
            $m['course_name'], $m['sub_course_name'], $m['type'], $m['category'], $m['level'], $m['batch'],
            $m['main_classification'], $m['sub_classification'], $m['delivery_method'], $m['sub_delivery_method'],
            $m['provider_name'], $m['venue'], $m['instructor'], $m['duration_seconds'], $m['learning_hours'],
            $m['progress'], $m['progress_status'], $m['start_date'], $m['end_date'], $m['start_time'], $m['end_time'],
            $m['month'], $m['requirement'], $m['attendance'], $m['attendance_reason'], $m['bond_status'],
            $m['bond_duration'], $m['bond_end_date'], $m['bond_amount'], $m['l1_program'], $m['assessment_score'],
            $m['l2_verbal'], $m['l3_numerical'], $m['l3_verbal'], $m['pic'], $m['certificate'], $m['total_engagement_time'],
        ];
    }

    protected function createSpreadsheet(array $rows): UploadedFile
    {
        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle(OfflineTrainingExcelSchema::DATA_SHEET_TITLE);

        // Canonical 48 headers on Row 1
        $headers = OfflineTrainingExcelSchema::canonicalHeaders();
        foreach ($headers as $idx => $hdr) {
            $colLetter = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($idx + 1);
            $sheet->setCellValue($colLetter . '1', $hdr);
        }

        foreach ($rows as $rIdx => $vals) {
            $rowNum = $rIdx + 2;
            foreach ($vals as $cIdx => $v) {
                $colLetter = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($cIdx + 1);
                $sheet->setCellValueExplicit($colLetter . $rowNum, $v, \PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_STRING);
            }
        }

        $tmpPath = tempnam(sys_get_temp_dir(), 'nptkp_test_') . '.xlsx';
        $writer = new Xlsx($spreadsheet);
        $writer->save($tmpPath);
        $this->createdTempFiles[] = $tmpPath;

        return new UploadedFile($tmpPath, 'test_non_ptkp.xlsx', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet', null, true);
    }

    protected function createLegacySpreadsheet(array $rows): UploadedFile
    {
        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle(OfflineTrainingExcelSchema::DATA_SHEET_TITLE);

        $headers = OfflineTrainingExcelSchema::legacyHeaders();
        foreach ($headers as $idx => $hdr) {
            $colLetter = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($idx + 1);
            $sheet->setCellValue($colLetter . '1', $hdr);
        }

        foreach ($rows as $rIdx => $vals) {
            $rowNum = $rIdx + 2;
            foreach ($vals as $cIdx => $v) {
                $colLetter = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($cIdx + 1);
                $sheet->setCellValueExplicit($colLetter . $rowNum, $v, \PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_STRING);
            }
        }

        $tmpPath = tempnam(sys_get_temp_dir(), 'nptkp_legacy_') . '.xlsx';
        $writer = new Xlsx($spreadsheet);
        $writer->save($tmpPath);
        $this->createdTempFiles[] = $tmpPath;

        return new UploadedFile($tmpPath, 'test_legacy_non_ptkp.xlsx', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet', null, true);
    }

    /**
     * 1. Test dedicated Non PTKP page requires authentication and permission.
     */
    public function test_non_ptkp_import_page_requires_auth_and_permission(): void
    {
        $guestResp = $this->get('/manage/record/offline/import-non-ptkp');
        $this->assertTrue($guestResp->isRedirect() || $guestResp->getStatusCode() === 401);

        if ($this->regularUser) {
            $forbidResp = $this->actingAs($this->regularUser)->get('/manage/record/offline/import-non-ptkp');
            $forbidResp->assertStatus(403);
        }

        $adminResp = $this->actingAs($this->admin)->get('/manage/record/offline/import-non-ptkp');
        $adminResp->assertStatus(200);
        $adminResp->assertSee('Import Non-PTKP Data');
        $adminResp->assertSee('Upload Spreadsheet');
        $adminResp->assertSee('Validation Preview');
        $adminResp->assertDontSee('Import Status Guide');
        $adminResp->assertDontSee('Import Status Legend');
    }

    /**
     * 2. Test Non PTKP template download.
     */
    public function test_non_ptkp_template_download(): void
    {
        $resp = $this->actingAs($this->admin)->get(route('manage.record.offline.import-non-ptkp.template'));
        $resp->assertStatus(200);
        $this->assertStringContainsString('application/vnd.openxmlformats-officedocument.spreadsheetml.sheet', $resp->headers->get('content-type'));
    }

    /**
     * 3. Test validation preview returns stats and 44-column mapped data.
     */
    public function test_non_ptkp_preview_returns_validation_and_canonical_columns(): void
    {
        $adminEmp = $this->admin->id_emp;
        $adminName = $this->admin->name;

        $row = $this->buildNonPtkpRow([
            'employee_id' => $adminEmp,
            'name'        => $adminName,
            'course_name' => 'Non PTKP Safety Standard',
        ]);
        $file = $this->createSpreadsheet([$row]);

        $resp = $this->actingAs($this->admin)->post(route('manage.record.offline.import-non-ptkp.preview'), [
            'file' => $file,
        ]);

        $resp->assertStatus(200);
        $resp->assertJson(['success' => true]);

        $data = $resp->json('data');
        $this->assertNotEmpty($data['token']);
        $this->assertEquals(1, $data['stats']['total_rows']);
        $this->assertEquals(1, $data['stats']['valid']);
        $this->assertEquals(0, $data['stats']['invalid']);
        $this->assertEquals('valid', $data['preview_rows'][0]['status']);
        $this->assertEquals('Non PTKP Safety Standard', $data['preview_rows'][0]['data']['course_name']);
        $this->assertEquals($adminEmp, $data['preview_rows'][0]['data']['employee_id']);
    }

    /**
     * 4. Test duplicate row inside same file is flagged as DUPLICATE.
     */
    public function test_non_ptkp_duplicate_in_same_file_is_skipped(): void
    {
        $adminEmp = $this->admin->id_emp;
        $adminName = $this->admin->name;

        $row1 = $this->buildNonPtkpRow(['employee_id' => $adminEmp, 'name' => $adminName, 'course_name' => 'Same Course Non PTKP']);
        $row2 = $this->buildNonPtkpRow(['employee_id' => $adminEmp, 'name' => $adminName, 'course_name' => 'Same Course Non PTKP']);
        $file = $this->createSpreadsheet([$row1, $row2]);

        $resp = $this->actingAs($this->admin)->post(route('manage.record.offline.import-non-ptkp.preview'), [
            'file' => $file,
        ]);

        $resp->assertStatus(200);
        $data = $resp->json('data');
        $this->assertEquals(2, $data['stats']['total_rows']);
        $this->assertEquals(1, $data['stats']['valid']);
        $this->assertEquals(1, $data['stats']['duplicate']);
        $this->assertEquals('valid', $data['preview_rows'][0]['status']);
        $this->assertEquals('duplicate', $data['preview_rows'][1]['status']);
    }

    /**
     * 4b. Test non-LMS employee produces importable warning without blocking.
     */
    public function test_non_ptkp_unmatched_employee_produces_warning_and_is_importable(): void
    {
        $row = $this->buildNonPtkpRow([
            'employee_id' => '999999_NOT_IN_LMS',
            'name'        => 'External Participant',
            'course_name' => 'Public Safety Seminar',
        ]);
        $file = $this->createSpreadsheet([$row]);

        $resp = $this->actingAs($this->admin)->post(route('manage.record.offline.import-non-ptkp.preview'), [
            'file' => $file,
        ]);

        $resp->assertStatus(200);
        $data = $resp->json('data');
        $this->assertEquals(1, $data['stats']['warning']);
        $this->assertEquals(1, $data['importable_count']);
        $this->assertEquals('warning', $data['preview_rows'][0]['status']);
        $this->assertFalse($data['preview_rows'][0]['user_matched']);
    }

    /**
     * 5. Test when all records are duplicates, state becomes NO_NEW_RECORDS.
     */
    public function test_non_ptkp_all_duplicates_returns_no_new_records_state(): void
    {
        // Pre-create training and participant in DB
        $t = OfflineTraining::create([
            'course_name' => 'Existing Safety Session',
            'batch'       => 'Batch EX1',
            'type'        => 'In-House',
            'category'    => 'TEST_NON_PTKP_CAT',
            'venue'       => 'Room A-101',
            'start_date'  => '2026-09-10',
            'finish_date' => '2026-09-10',
        ]);

        OfflineTrainingUser::create([
            'offline_training_id' => $t->id,
            'employee_id'         => '100888',
            'name'                => 'Existing Participant',
            'company'             => 'TEST_NON_PTKP_CORP',
        ]);

        $row = $this->buildNonPtkpRow([
            'employee_id' => '100888',
            'course_name' => 'Existing Safety Session',
            'batch'       => 'Batch EX1',
            'type'        => 'In-House',
            'venue'       => 'Room A-101',
            'start_date'  => '2026-09-10',
            'end_date'    => '2026-09-10',
        ]);
        $file = $this->createSpreadsheet([$row]);

        $resp = $this->actingAs($this->admin)->post(route('manage.record.offline.import-non-ptkp.preview'), [
            'file' => $file,
        ]);

        $resp->assertStatus(200);
        $data = $resp->json('data');
        $this->assertEquals('NO_NEW_RECORDS', $data['preview_state']);
        $this->assertEquals(0, $data['importable_count']);
        $this->assertEquals(1, $data['stats']['duplicate']);
        $this->assertFalse($data['can_confirm']);
    }

    /**
     * 6. Test confirm import persists valid records into database and cleans up.
     */
    public function test_non_ptkp_confirm_persists_valid_records_and_cleans_up(): void
    {
        $row = $this->buildNonPtkpRow([
            'employee_id' => '100777',
            'name'        => 'Jane Doe Non PTKP',
            'course_name' => 'Confirmed Course Non PTKP',
        ]);
        $file = $this->createSpreadsheet([$row]);

        $previewResp = $this->actingAs($this->admin)->post(route('manage.record.offline.import-non-ptkp.preview'), [
            'file' => $file,
        ]);
        $token = $previewResp->json('token');
        $this->assertNotEmpty($token);

        $confirmResp = $this->actingAs($this->admin)->post(route('manage.record.offline.import-non-ptkp.confirm'), [
            'token' => $token,
        ]);

        $confirmResp->assertStatus(200);
        $confirmResp->assertJson(['success' => true]);

        // Verify training and participant exist in database
        $this->assertDatabaseHas('offline_trainings', [
            'course_name' => 'Confirmed Course Non PTKP',
            'category'    => 'TEST_NON_PTKP_CAT',
        ]);

        $this->assertDatabaseHas('offline_training_users', [
            'employee_id' => '100777',
            'name'        => 'Jane Doe Non PTKP',
            'company'     => 'TEST_NON_PTKP_CORP',
        ]);

        // Verify cache and temp file cleaned up
        $this->assertNull(Cache::get(OfflineTrainingImportService::CACHE_PREFIX . $token));
    }

    /**
     * 7. Test cancel cleans up temporary file and session cache.
     */
    public function test_non_ptkp_cancel_cleans_up_temporary_file(): void
    {
        $row = $this->buildNonPtkpRow();
        $file = $this->createSpreadsheet([$row]);

        $previewResp = $this->actingAs($this->admin)->post(route('manage.record.offline.import-non-ptkp.preview'), [
            'file' => $file,
        ]);
        $token = $previewResp->json('token');

        $cancelResp = $this->actingAs($this->admin)->post(route('manage.record.offline.import-non-ptkp.cancel'), [
            'token' => $token,
        ]);

        $cancelResp->assertStatus(200);
        $cancelResp->assertJson(['success' => true]);
        $this->assertNull(Cache::get(OfflineTrainingImportService::CACHE_PREFIX . $token));
    }

    /**
     * 8. Test Offline Record landing dropdown has link to import-non-ptkp.
     */
    public function test_offline_page_dropdown_has_link_to_import_non_ptkp(): void
    {
        $resp = $this->actingAs($this->admin)->get(route('manage.record.offline'));
        $resp->assertStatus(200);
        $resp->assertSee(route('manage.record.offline.import-non-ptkp'));
        $resp->assertSee('Import Data Non PTKP');
        $resp->assertSee('Import Data PTKP');
        $resp->assertSee(route('manage.record.offline.create'));
    }

    /**
     * 9. Test Manual and PTKP routes remain unaffected.
     */
    public function test_manual_and_ptkp_routes_unaffected(): void
    {
        $ptkpResp = $this->actingAs($this->admin)->get(route('manage.record.offline.import-ptkp'));
        $ptkpResp->assertStatus(200);

        $manualResp = $this->actingAs($this->admin)->get(route('manage.record.offline.create'));
        $manualResp->assertStatus(200);
    }

    protected function createSpreadsheetWithHeaders(array $headers, array $rows): UploadedFile
    {
        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle(OfflineTrainingExcelSchema::DATA_SHEET_TITLE);

        foreach ($headers as $idx => $hdr) {
            $colLetter = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($idx + 1);
            $sheet->setCellValue($colLetter . '1', $hdr);
        }

        foreach ($rows as $rIdx => $vals) {
            $rowNum = $rIdx + 2;
            foreach ($vals as $cIdx => $v) {
                $colLetter = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($cIdx + 1);
                $sheet->setCellValueExplicit($colLetter . $rowNum, $v, \PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_STRING);
            }
        }

        $tmpPath = tempnam(sys_get_temp_dir(), 'nptkp_alias_') . '.xlsx';
        $writer = new Xlsx($spreadsheet);
        $writer->save($tmpPath);
        $this->createdTempFiles[] = $tmpPath;

        return new UploadedFile($tmpPath, 'alias_test.xlsx', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet', null, true);
    }

    /**
     * 10. Test header aliases are recognized and mapped correctly.
     */
    public function test_non_ptkp_header_aliases_recognized(): void
    {
        $this->createTestUser(['id_emp' => '100888', 'name' => 'Alias Test User']);

        $headers = OfflineTrainingExcelSchema::headers();
        $aliasMap = [
            'Employee ID'   => 'Employee No',
            'Employee Name' => 'Full Name',
            'Course Title'  => 'Training Title',
            'Company'       => 'Company Name',
            'Provider'      => 'Training Provider',
        ];

        $aliasedHeaders = array_map(function ($h) use ($aliasMap) {
            return $aliasMap[$h] ?? $h;
        }, $headers);

        $row = $this->buildNonPtkpRow([
            'employee_id' => '100888',
            'name'        => 'Alias Test User',
            'course_name' => 'Alias Course Test',
        ]);

        $file = $this->createSpreadsheetWithHeaders($aliasedHeaders, [$row]);

        $resp = $this->actingAs($this->admin)->post(route('manage.record.offline.import-non-ptkp.preview'), [
            'file' => $file,
        ]);

        $resp->assertStatus(200);
        $resp->assertJson(['success' => true]);
        $this->assertTrue($resp->json('header_check.can_proceed'));
        $this->assertSame(1, $resp->json('stats.valid'));
        $this->assertSame('100888', $resp->json('preview_rows.0.data.employee_id'));
        $this->assertSame('Alias Course Test', $resp->json('preview_rows.0.data.course_name'));
    }

    /**
     * 11. Test leading zero on employee_id is preserved through preview and database.
     */
    public function test_non_ptkp_leading_zero_employee_id_preserved(): void
    {
        $row = $this->buildNonPtkpRow([
            'employee_id' => '001234',
            'name'        => 'Leading Zero Employee',
            'course_name' => 'Leading Zero Course',
        ]);

        $file = $this->createSpreadsheet([$row]);

        $previewResp = $this->actingAs($this->admin)->post(route('manage.record.offline.import-non-ptkp.preview'), [
            'file' => $file,
        ]);

        $previewResp->assertStatus(200);
        $token = $previewResp->json('token');
        $this->assertSame('001234', $previewResp->json('preview_rows.0.data.employee_id'));

        $confirmResp = $this->actingAs($this->admin)->post(route('manage.record.offline.import-non-ptkp.confirm'), [
            'token' => $token,
        ]);

        $confirmResp->assertStatus(200);

        $this->assertDatabaseHas('offline_training_users', [
            'employee_id' => '001234',
            'name'        => 'Leading Zero Employee',
        ]);
    }

    /**
     * 12. Test optional empty cells do not fail validation.
     */
    public function test_non_ptkp_optional_empty_cells_allowed(): void
    {
        $sparseRow = $this->buildNonPtkpRow([
            'employee_id'            => '100444',
            'name'                   => 'Sparse Participant',
            'course_name'            => 'Sparse Course',
            'company'                => '', // empty company triggers INVALID COMPANY (non-blocking)
            'grade'                  => '',
            'job_position'           => '',
            'team'                   => '',
            'department'             => '',
            'sub_course_name'        => '',
            'type'                   => '',
            'category'               => '',
            'level'                  => '',
            'batch'                  => '',
            'main_classification'    => '',
            'sub_classification'     => '',
            'delivery_method'        => '',
            'sub_delivery_method'    => '',
            'provider'               => '',
            'sub_provider'           => '',
            'provider_name'          => '',
            'venue'                  => '',
            'instructor'             => '',
            'learning_hours'         => '',
            'progress'               => '',
            'start_date'             => '',
            'end_date'               => '',
            'start_time'             => '',
            'end_time'               => '',
            'month'                  => '',
            'requirement'            => '',
            'attendance'             => '',
            'attendance_reason'      => '',
            'bond_status'            => '',
            'bond_duration'          => '',
            'bond_end_date'          => '',
            'bond_amount'            => '',
            'l1_program'             => '',
            'assessment_score'       => '',
            'l2_verbal'              => '',
            'l3_numerical'           => '',
            'l3_verbal'              => '',
            'certificate_availability' => '',
            'certificate_institution'  => '',
            'certificate_issued_date'  => '',
            'certificate_expired_date' => '',
            'pic'                    => '',
        ]);

        $file = $this->createSpreadsheet([$sparseRow]);

        $previewResp = $this->actingAs($this->admin)->post(route('manage.record.offline.import-non-ptkp.preview'), [
            'file' => $file,
        ]);

        $previewResp->assertStatus(200);
        $previewResp->assertJson(['success' => true]);
        $this->assertSame(0, $previewResp->json('stats.invalid'));
        $this->assertSame(1, $previewResp->json('stats.invalid_company'));
        $this->assertSame(1, $previewResp->json('importable_count'));
        $this->assertSame('invalid_company', $previewResp->json('preview_rows.0.status'));
    }

    /**
     * 13. Test duplicate detection skips safely without blocking valid records.
     */
    public function test_non_ptkp_duplicate_detection_skips_safely_without_blocking_valid(): void
    {
        $this->createTestUser(['id_emp' => '100551', 'name' => 'Participant One']);
        $this->createTestUser(['id_emp' => '100552', 'name' => 'Participant Two']);

        $row1 = $this->buildNonPtkpRow([
            'employee_id' => '100551',
            'name'        => 'Participant One',
            'course_name' => 'Deduplication Course',
        ]);
        $row2 = $this->buildNonPtkpRow([
            'employee_id' => '100551',
            'name'        => 'Participant One Duplicate',
            'course_name' => 'Deduplication Course',
        ]);
        $row3 = $this->buildNonPtkpRow([
            'employee_id' => '100552',
            'name'        => 'Participant Two',
            'course_name' => 'Deduplication Course',
        ]);

        $file = $this->createSpreadsheet([$row1, $row2, $row3]);

        $previewResp = $this->actingAs($this->admin)->post(route('manage.record.offline.import-non-ptkp.preview'), [
            'file' => $file,
        ]);

        $previewResp->assertStatus(200);
        $this->assertSame(2, $previewResp->json('stats.valid'));
        $this->assertSame(1, $previewResp->json('stats.duplicate'));
        $this->assertSame(0, $previewResp->json('stats.invalid'));

        $token = $previewResp->json('token');
        $confirmResp = $this->actingAs($this->admin)->post(route('manage.record.offline.import-non-ptkp.confirm'), [
            'token' => $token,
        ]);

        $confirmResp->assertStatus(200);
        $this->assertSame(2, $confirmResp->json('result.imported'));
        $this->assertSame(1, $confirmResp->json('result.skipped_duplicates'));
    }

    /**
     * 14. Test non-LMS user produces WARNING status (not INVALID) and imports with null user_id.
     */
    public function test_non_ptkp_non_lms_user_imports_with_warning_and_null_user_id(): void
    {
        $nonLmsEmpId = 'EXT_USER_999999';
        $this->assertDatabaseMissing('users', ['id_emp' => $nonLmsEmpId]);

        $row = $this->buildNonPtkpRow([
            'employee_id' => $nonLmsEmpId,
            'name'        => 'External Non-LMS Participant',
            'course_name' => 'External Contractor Safety',
        ]);

        $file = $this->createSpreadsheet([$row]);

        $previewResp = $this->actingAs($this->admin)->post(route('manage.record.offline.import-non-ptkp.preview'), [
            'file' => $file,
        ]);

        $previewResp->assertStatus(200);
        $this->assertSame('warning', $previewResp->json('preview_rows.0.status'));
        $this->assertSame(1, $previewResp->json('stats.warning'));
        $this->assertSame(1, $previewResp->json('importable_count'));
        $this->assertSame(0, $previewResp->json('stats.invalid'));

        $token = $previewResp->json('token');
        $confirmResp = $this->actingAs($this->admin)->post(route('manage.record.offline.import-non-ptkp.confirm'), [
            'token' => $token,
        ]);

        $confirmResp->assertStatus(200);
        $this->assertSame(1, $confirmResp->json('result.imported'));

        $this->assertDatabaseHas('offline_training_users', [
            'employee_id' => $nonLmsEmpId,
            'user_id'     => null,
            'name'        => 'External Non-LMS Participant',
        ]);
    }

    /**
     * 15. Test audit log records correct batch status and imported/skipped rows.
     */
    public function test_non_ptkp_audit_log_records_correct_actions(): void
    {
        $this->createTestUser([
            'id_emp' => '100661',
            'name'   => 'Audit Log User 1',
        ]);

        $row1 = $this->buildNonPtkpRow([
            'employee_id' => '100661',
            'name'        => 'Audit Log User 1',
            'course_name' => 'Audit Log Course',
        ]);
        $row2 = $this->buildNonPtkpRow([
            'employee_id' => '100661',
            'name'        => 'Audit Log User 1 Duplicate',
            'course_name' => 'Audit Log Course',
        ]);

        $file = $this->createSpreadsheet([$row1, $row2]);

        $previewResp = $this->actingAs($this->admin)->post(route('manage.record.offline.import-non-ptkp.preview'), [
            'file' => $file,
        ]);
        $token = $previewResp->json('token');

        $confirmResp = $this->actingAs($this->admin)->post(route('manage.record.offline.import-non-ptkp.confirm'), [
            'token' => $token,
        ]);
        $confirmResp->assertStatus(200);

        $batchUuid = $confirmResp->json('result.batch_uuid');
        $this->assertNotEmpty($batchUuid);

        $batch = ImportBatch::where('batch_uuid', $batchUuid)->first();
        $this->assertNotNull($batch);
        $this->assertSame(ImportBatch::SOURCE_NON_PTKP, $batch->source_type);
        $this->assertSame(ImportBatch::STATUS_COMPLETED, $batch->status);
        $this->assertSame(2, $batch->total_rows);
        $this->assertSame(1, $batch->imported_rows);
        $this->assertSame(1, $batch->skipped_rows);

        $importedRow = $batch->rows()->where('action', ImportBatchRow::ACTION_IMPORTED)->first();
        $this->assertNotNull($importedRow);
        $this->assertSame('100661', $importedRow->employee_id);

        $skippedRow = $batch->rows()->where('action', ImportBatchRow::ACTION_SKIPPED)->first();
        $this->assertNotNull($skippedRow);
        $this->assertSame('100661', $skippedRow->employee_id);
    }

    /**
     * 16. Test Provider and Sub Provider are mapped and persisted independently.
     */
    public function test_non_ptkp_provider_and_sub_provider_imported_independently(): void
    {
        $this->createTestUser([
            'id_emp' => '100771',
            'name'   => 'Independent Provider User',
        ]);

        $row = $this->buildNonPtkpRow([
            'employee_id'   => '100771',
            'name'          => 'Independent Provider User',
            'course_name'   => 'Independent Provider Course',
            'provider'      => 'External',
            'sub_provider'  => 'Vendor',
            'provider_name' => 'PT Schneider Indonesia',
        ]);

        $file = $this->createSpreadsheet([$row]);

        $previewResp = $this->actingAs($this->admin)->post(route('manage.record.offline.import-non-ptkp.preview'), [
            'file' => $file,
        ]);

        $previewResp->assertStatus(200);
        $token = $previewResp->json('token');
        $this->assertTrue($previewResp->json('header_check.can_proceed'));
        $this->assertSame('External', $previewResp->json('preview_rows.0.data.provider'));
        $this->assertSame('Vendor', $previewResp->json('preview_rows.0.data.sub_provider'));
        $this->assertSame('PT Schneider Indonesia', $previewResp->json('preview_rows.0.data.provider_name'));

        $confirmResp = $this->actingAs($this->admin)->post(route('manage.record.offline.import-non-ptkp.confirm'), [
            'token' => $token,
        ]);
        $confirmResp->assertStatus(200);

        $training = OfflineTraining::where('course_name', 'Independent Provider Course')->first();
        $this->assertNotNull($training);
        $this->assertSame('External', $training->provider);
        $this->assertSame('Vendor', $training->sub_provider);
        $this->assertSame('PT Schneider Indonesia', $training->provider_name);
    }

    /**
     * 17. Test non-LMS participant is safely queryable in record queries with null user_id.
     */
    public function test_non_ptkp_non_lms_participant_safe_with_null_user_id(): void
    {
        $row = $this->buildNonPtkpRow([
            'employee_id' => '100772',
            'name'        => 'Contractor Without LMS',
            'company'     => 'TEST_NON_PTKP_CORP',
            'course_name' => 'Contractor Safety Non LMS',
        ]);

        $file = $this->createSpreadsheet([$row]);

        $previewResp = $this->actingAs($this->admin)->post(route('manage.record.offline.import-non-ptkp.preview'), [
            'file' => $file,
        ]);
        $token = $previewResp->json('token');

        $confirmResp = $this->actingAs($this->admin)->post(route('manage.record.offline.import-non-ptkp.confirm'), [
            'token' => $token,
        ]);
        $confirmResp->assertStatus(200);

        $participant = OfflineTrainingUser::where('employee_id', '100772')->first();
        $this->assertNotNull($participant);
        $this->assertNull($participant->user_id);
        $this->assertSame('Contractor Without LMS', $participant->name);
        $this->assertSame('TEST_NON_PTKP_CORP', $participant->company);

        // Verify RecordOfflineQueryService left-joins safely without dropping the record
        $result = \App\Services\Record\RecordOfflineQueryService::buildQuery()->where('otu.employee_id', '100772')->first();
        $this->assertNotNull($result);
        $this->assertSame('100772', $result->employee_id);
        $this->assertSame('Contractor Without LMS', $result->participant_name);
        $this->assertSame('TEST_NON_PTKP_CORP', $result->company);
    }

    /**
     * 18. Test all 5 validation statuses and their strict priority:
     *     INVALID > DUPLICATE > INVALID COMPANY > WARNING > VALID
     */
    public function test_non_ptkp_five_validation_statuses_and_priority(): void
    {
        $this->createTestUser(['id_emp' => 'NP_VALID_1', 'name' => 'Valid User 1']);

        // Pre-create DB participant for duplicate test
        $existingTraining = OfflineTraining::create([
            'course_name' => 'Priority Test Course',
            'batch'       => 'Batch 01',
            'type'        => 'In-House',
            'category'    => 'TEST_NON_PTKP_CAT',
            'venue'       => 'Room A-101',
            'start_date'  => '2026-09-10',
            'finish_date' => '2026-09-10',
        ]);
        OfflineTrainingUser::create([
            'offline_training_id' => $existingTraining->id,
            'employee_id'         => 'NP_DUP_DB',
            'name'                => 'Duplicate User',
            'company'             => 'TEST_NON_PTKP_CORP',
        ]);

        $rows = [
            // Row 1: Clean VALID
            $this->buildNonPtkpRow([
                'employee_id' => 'NP_VALID_1',
                'name'        => 'Valid User 1',
                'company'     => 'PT Krakatau Posco',
                'course_name' => 'Priority Test Course 2',
            ]),
            // Row 2: Empty company -> INVALID COMPANY (imported, non-blocking)
            $this->buildNonPtkpRow([
                'employee_id' => 'NP_VALID_1',
                'name'        => 'Valid User 1',
                'company'     => '',
                'course_name' => 'Priority Test Course 3',
            ]),
            // Row 3: Non-LMS employee -> WARNING (imported, non-blocking)
            $this->buildNonPtkpRow([
                'employee_id' => 'NON_LMS_EMP_99',
                'name'        => 'Non LMS Employee',
                'company'     => 'PT External Vendor',
                'course_name' => 'Priority Test Course 4',
            ]),
            // Row 4: Duplicate in DB -> DUPLICATE (skipped)
            $this->buildNonPtkpRow([
                'employee_id' => 'NP_DUP_DB',
                'name'        => 'Duplicate User',
                'company'     => 'TEST_NON_PTKP_CORP',
                'course_name' => 'Priority Test Course',
                'batch'       => 'Batch 01',
                'start_date'  => '2026-09-10',
                'end_date'    => '2026-09-10',
            ]),
            // Row 5: Missing course title -> INVALID (skipped)
            $this->buildNonPtkpRow([
                'employee_id' => 'NP_VALID_1',
                'name'        => 'Valid User 1',
                'company'     => 'PT Krakatau Posco',
                'course_name' => '',
            ]),
        ];

        $file = $this->createSpreadsheet($rows);

        $previewResp = $this->actingAs($this->admin)->post(route('manage.record.offline.import-non-ptkp.preview'), [
            'file' => $file,
        ]);

        $previewResp->assertStatus(200);
        $previewResp->assertJson(['success' => true]);

        $stats = $previewResp->json('data.stats');
        $this->assertSame(5, $stats['total_rows']);
        $this->assertSame(1, $stats['valid']);
        $this->assertSame(1, $stats['invalid_company']);
        $this->assertSame(1, $stats['warning']);
        $this->assertSame(1, $stats['duplicate']);
        $this->assertSame(1, $stats['invalid']);
        $this->assertSame(3, $previewResp->json('data.importable_count'));

        $previewRows = $previewResp->json('data.preview_rows');
        $this->assertSame('valid', $previewRows[0]['status']);
        $this->assertSame('invalid_company', $previewRows[1]['status']);
        $this->assertSame('warning', $previewRows[2]['status']);
        $this->assertSame('duplicate', $previewRows[3]['status']);
        $this->assertSame('invalid', $previewRows[4]['status']);
    }

    /**
     * 19. Test backward compatibility: Legacy 44-column files parse and import cleanly.
     */
    public function test_non_ptkp_backward_compatibility_with_legacy_44_column_file(): void
    {
        $this->createTestUser(['id_emp' => 'NP_LEGACY_1', 'name' => 'Legacy 44 Col User']);

        $row = $this->buildLegacyNonPtkpRow([
            'employee_id' => 'NP_LEGACY_1',
            'name'        => 'Legacy 44 Col User',
            'course_name' => 'Legacy 44 Column Offline Course',
            'company'     => 'TEST_NON_PTKP_CORP',
        ]);

        $file = $this->createLegacySpreadsheet([$row]);

        $previewResp = $this->actingAs($this->admin)->post(route('manage.record.offline.import-non-ptkp.preview'), [
            'file' => $file,
        ]);

        $previewResp->assertStatus(200);
        $previewResp->assertJson(['success' => true]);

        $this->assertTrue($previewResp->json('data.header_check.can_proceed'));
        $this->assertSame(1, $previewResp->json('data.stats.total_rows'));
        $this->assertSame(1, $previewResp->json('data.stats.valid'));
        $this->assertSame(0, $previewResp->json('data.stats.invalid'));
        $this->assertSame('valid', $previewResp->json('data.preview_rows.0.status'));
        $this->assertSame('NP_LEGACY_1', $previewResp->json('data.preview_rows.0.data.employee_id'));
        $this->assertSame('Legacy 44 Column Offline Course', $previewResp->json('data.preview_rows.0.data.course_name'));

        // Confirm import
        $token = $previewResp->json('data.token');
        $confirmResp = $this->actingAs($this->admin)->post(route('manage.record.offline.import-non-ptkp.confirm'), [
            'token' => $token,
        ]);

        $confirmResp->assertStatus(200);
        $this->assertSame(1, $confirmResp->json('result.imported'));

        $this->assertDatabaseHas('offline_trainings', [
            'course_name' => 'Legacy 44 Column Offline Course',
        ]);
        $this->assertDatabaseHas('offline_training_users', [
            'employee_id' => 'NP_LEGACY_1',
            'name'        => 'Legacy 44 Col User',
        ]);
    }

    /**
     * 20. Test Non-PTKP with empty Employee ID and filled Course Title is not automatically INVALID.
     *     Participant identity is safely preserved via name, marked as WARNING, and imported into DB.
     */
    public function test_non_ptkp_empty_employee_id_with_filled_course_title_is_not_automatically_invalid(): void
    {
        $courseTitle = 'Non-PTKP Empty NIP Safety ' . Str::random(4);
        $participantName = 'Participant Without NIP ' . Str::random(4);

        $row = $this->buildNonPtkpRow([
            'employee_id' => '', // Empty Employee ID
            'name'        => $participantName,
            'course_name' => $courseTitle,
            'company'     => 'PT Partner Global',
        ]);

        $file = $this->createSpreadsheet([$row]);

        // Preview
        $previewResp = $this->actingAs($this->admin)->post(route('manage.record.offline.import-non-ptkp.preview'), [
            'file' => $file,
        ]);

        $previewResp->assertStatus(200);
        $previewResp->assertJson(['success' => true]);

        // Must NOT be invalid! Should be WARNING and importable
        $this->assertSame(0, $previewResp->json('data.stats.invalid'));
        $this->assertSame(1, $previewResp->json('data.stats.warning'));
        $this->assertSame(1, $previewResp->json('data.importable_count'));

        $previewRow = $previewResp->json('data.preview_rows.0');
        $this->assertSame('warning', $previewRow['status']);
        $this->assertNull($previewRow['data']['employee_id']);
        $this->assertSame($participantName, $previewRow['data']['name']);
        $this->assertSame($courseTitle, $previewRow['data']['course_name']);

        // Confirm
        $token = $previewResp->json('data.token');
        $confirmResp = $this->actingAs($this->admin)->post(route('manage.record.offline.import-non-ptkp.confirm'), [
            'token' => $token,
        ]);

        $confirmResp->assertStatus(200);
        $this->assertSame(1, $confirmResp->json('result.imported'));

        // DB Verification
        $dbTraining = OfflineTraining::where('course_name', $courseTitle)->first();
        $this->assertNotNull($dbTraining);

        $dbUser = OfflineTrainingUser::where('offline_training_id', $dbTraining->id)->where('name', $participantName)->first();
        $this->assertNotNull($dbUser);
        $this->assertNull($dbUser->employee_id);
        $this->assertNull($dbUser->user_id);
    }

    /**
     * 21. Test Non-PTKP INVALID rules:
     *     - Filled Employee ID + Empty Course Title -> INVALID
     *     - Empty Employee ID + Empty Course Title -> INVALID
     *     - Empty Employee ID + Filled Course Title + Empty Name -> INVALID (cannot identify participant)
     */
    public function test_non_ptkp_invalid_when_course_title_empty_or_both_empty_or_unidentifiable(): void
    {
        $rows = [
            // Row 1: Filled Employee ID + Empty Course Title -> INVALID
            $this->buildNonPtkpRow([
                'employee_id' => '100111',
                'name'        => 'User With Missing Course',
                'course_name' => '',
            ]),
            // Row 2: Empty Employee ID + Empty Course Title -> INVALID
            $this->buildNonPtkpRow([
                'employee_id' => '',
                'name'        => 'User With Both Empty',
                'course_name' => '',
            ]),
            // Row 3: Empty Employee ID + Filled Course Title + Empty Name -> INVALID
            $this->buildNonPtkpRow([
                'employee_id' => '',
                'name'        => '',
                'course_name' => 'Valid Course Without Any Participant Identity',
            ]),
        ];

        $file = $this->createSpreadsheet($rows);

        $previewResp = $this->actingAs($this->admin)->post(route('manage.record.offline.import-non-ptkp.preview'), [
            'file' => $file,
        ]);

        $previewResp->assertStatus(200);
        $previewResp->assertJson(['success' => true]);

        $stats = $previewResp->json('data.stats');
        $this->assertSame(3, $stats['total_rows']);
        $this->assertSame(3, $stats['invalid']);
        $this->assertSame(0, $previewResp->json('data.importable_count'));

        $previewRows = $previewResp->json('data.preview_rows');
        $this->assertSame('invalid', $previewRows[0]['status']);
        $this->assertSame('invalid', $previewRows[1]['status']);
        $this->assertSame('invalid', $previewRows[2]['status']);
    }

    /**
     * 22. Test simplified single-row header template structure and freeze pane at A2.
     */
    public function test_non_ptkp_group_header_ranges_and_service_bond_evaluation_boundary(): void
    {
        // Canonical template has no category group headers
        $groups = OfflineTrainingExcelSchema::groups();
        $this->assertEmpty($groups);

        // Verify generated spreadsheet styling
        $spreadsheet = OfflineTrainingExcelSchema::generateTemplateSpreadsheet();
        $sheet = $spreadsheet->getSheet(0);

        // Single-row canonical headers on Row 1 (46 columns: Employee ID through PIC)
        $this->assertSame('Employee ID', $sheet->getCell('A1')->getValue());
        $this->assertSame('Course Title', $sheet->getCell('H1')->getValue());
        $this->assertSame('Bond Amount', $sheet->getCell('AJ1')->getValue());
        $this->assertSame('Score (L1) - Course', $sheet->getCell('AK1')->getValue());
        $this->assertSame('Person in Charge (PIC)', $sheet->getCell('AT1')->getValue());

        // Freeze pane is A2
        $this->assertSame('A2', $sheet->getFreezePane());

        // Auto filter covers A1:AT1
        $this->assertSame('A1:AT1', $sheet->getAutoFilter()->getRange());

        // No merged cells on Row 1
        $this->assertEmpty($sheet->getMergeCells());
    }
}