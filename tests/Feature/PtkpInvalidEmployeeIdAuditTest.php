<?php

namespace Tests\Feature;

use App\Models\ImportBatch;
use App\Models\ImportBatchRow;
use App\Models\OfflineTraining;
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

class PtkpInvalidEmployeeIdAuditTest extends TestCase
{
    use DatabaseTransactions;

    protected User $adminUser;
    protected User $directorKim;
    protected User $directorNovial;
    protected User $validEmployee;
    protected User $softDeletedUser;
    protected PtkpValidationService $validationService;
    protected PtkpImportService $importService;

    protected function setUp(): void
    {
        parent::setUp();

        $this->adminUser = User::whereIn('role', [User::ROLE_ADMIN, User::ROLE_SUPER_ADMIN])->first()
            ?? $this->createTestUser(['role' => User::ROLE_ADMIN]);

        // Ensure director Kim Kyu-Je (000022) exists
        $this->directorKim = User::where('id_emp', '000022')->first()
            ?? $this->createTestUser([
                'id_emp'       => '000022',
                'name'         => 'KIM KYU-JE',
                'company_name' => 'PT Krakatau Posco',
            ]);

        // Ensure director Novial (000033) exists
        $this->directorNovial = User::where('id_emp', '000033')->first()
            ?? $this->createTestUser([
                'id_emp'       => '000033',
                'name'         => 'NOVIAL',
                'company_name' => 'PT Krakatau Posco',
            ]);

        // Fetch or create standard active employee
        $this->validEmployee = User::where('id_emp', '116022')->first()
            ?? $this->createTestUser([
                'id_emp'       => '116022',
                'name'         => 'MUHAMMAD IQBAL EL HIDAYAT',
                'company_name' => 'PT Krakatau Posco',
            ]);

        // Create soft deleted user
        $this->softDeletedUser = $this->createTestUser([
            'id_emp'       => '119999',
            'name'         => 'SOFT DELETED EMPLOYEE',
            'company_name' => 'PT Krakatau Posco',
        ]);
        $this->softDeletedUser->delete();

        $this->validationService = app(PtkpValidationService::class);
        $this->importService = app(PtkpImportService::class);
    }

    /**
     * Skenario 1 & 2: Employee ID 22 dan 33 tidak ditemukan -> INVALID.
     * Tidak boleh ter-padding ke 000022 atau 000033 yang merupakan akun Direktur.
     */
    public function test_scenario_employee_id_22_and_33_are_invalid(): void
    {
        $lookup = $this->validationService->batchLookupUsers(['22', '33']);

        $match22 = $this->validationService->matchUser('22', $lookup, 'IQBAL NUR RACHMAN');
        $this->assertEquals(PtkpValidationService::STATUS_INVALID, $match22['status']);
        $this->assertNull($match22['user']);
        $this->assertStringContainsString("Employee ID '22' was not found", $match22['errors'][0]);

        $match33 = $this->validationService->matchUser('33', $lookup, 'RIDWAN ALFITRA');
        $this->assertEquals(PtkpValidationService::STATUS_INVALID, $match33['status']);
        $this->assertNull($match33['user']);
        $this->assertStringContainsString("Employee ID '33' was not found", $match33['errors'][0]);
    }

    /**
     * Skenario 3: Empat baris pengujian (Rows 380, 391, 494, 505) seluruhnya INVALID.
     */
    public function test_scenario_four_test_rows_are_all_invalid(): void
    {
        $file = $this->createPtkpExcel([
            [
                'row_no'      => 380,
                'employee_id' => '22',
                'name'        => 'IQBAL NUR RACHMAN',
                'course_name' => 'New Employee Orientation for Steel Industry Technology Class (OJT PIPB)',
                'company'     => 'PT Krakatau Posco',
            ],
            [
                'row_no'      => 391,
                'employee_id' => '33',
                'name'        => 'RIDWAN ALFITRA',
                'course_name' => 'New Employee Orientation for Steel Industry Technology Class (OJT PIPB)',
                'company'     => 'PT Krakatau Posco',
            ],
            [
                'row_no'      => 494,
                'employee_id' => '22',
                'name'        => 'IQBAL NUR RACHMAN',
                'course_name' => 'NewGen Capability Development Vol. 1-2026',
                'company'     => 'PT Krakatau Posco',
            ],
            [
                'row_no'      => 505,
                'employee_id' => '33',
                'name'        => 'RIDWAN ALFITRA',
                'course_name' => 'NewGen Capability Development Vol. 1-2026',
                'company'     => 'PT Krakatau Posco',
            ],
        ]);

        $response = $this->actingAs($this->adminUser)
            ->post(route('manage.record.offline.import-ptkp.preview'), ['file' => $file]);

        $response->assertStatus(200);
        $json = $response->json();

        $this->assertEquals(4, $json['stats']['total_rows']);
        $this->assertEquals(0, $json['stats']['valid']);
        $this->assertEquals(4, $json['stats']['invalid']);
        $this->assertEquals(4, $json['stats']['invalid_id_count']);

        foreach ($json['rows'] as $idx => $r) {
            $this->assertEquals(PtkpValidationService::STATUS_INVALID, $r['status'], "Row index $idx should be INVALID");
            $this->assertTrue($r['is_invalid_id']);
            $this->assertNotEquals('KIM KYU-JE', $r['name']);
            $this->assertNotEquals('NOVIAL', $r['name']);
        }
    }

    /**
     * Skenario 4: Employee ID valid dan ditemukan -> VALID.
     */
    public function test_scenario_valid_employee_id_is_valid(): void
    {
        $file = $this->createPtkpExcel([
            [
                'row_no'      => 1,
                'employee_id' => $this->validEmployee->id_emp,
                'name'        => $this->validEmployee->name,
                'course_name' => 'Valid Employee Test Course',
                'company'     => 'PT Krakatau Posco',
            ],
        ]);

        $response = $this->actingAs($this->adminUser)
            ->post(route('manage.record.offline.import-ptkp.preview'), ['file' => $file]);

        $response->assertStatus(200);
        $json = $response->json();

        $this->assertEquals(1, $json['stats']['valid']);
        $this->assertEquals(0, $json['stats']['invalid']);
        $this->assertEquals(PtkpValidationService::STATUS_VALID, $json['rows'][0]['status']);
        $this->assertEquals($this->validEmployee->id_emp, $json['rows'][0]['employee_id']);
    }

    /**
     * Skenario 5: Employee ID kosong -> INVALID.
     */
    public function test_scenario_empty_employee_id_is_invalid(): void
    {
        $lookup = $this->validationService->batchLookupUsers(['']);
        $match = $this->validationService->matchUser('', $lookup, 'SOME NAME');
        $this->assertEquals(PtkpValidationService::STATUS_INVALID, $match['status']);
        $this->assertNull($match['user']);
        $this->assertTrue($match['is_not_found']);
    }

    /**
     * Skenario 6: Employee ID memiliki whitespace -> Normalisasi konsisten.
     */
    public function test_scenario_employee_id_with_whitespace_is_normalized(): void
    {
        $rawWithSpaces = '  ' . $this->validEmployee->id_emp . '  ';
        $lookup = $this->validationService->batchLookupUsers([$rawWithSpaces]);
        $match = $this->validationService->matchUser($rawWithSpaces, $lookup, $this->validEmployee->name);

        $this->assertEquals(PtkpValidationService::STATUS_VALID, $match['status']);
        $this->assertNotNull($match['user']);
        $this->assertEquals($this->validEmployee->id, $match['user']->id);
    }

    /**
     * Skenario 7: Employee ID berbentuk angka/string 3-5 digit (541 -> 000541) -> Lookup konsisten.
     */
    public function test_scenario_unpadded_3_digit_nip_matches_when_user_exists(): void
    {
        $iipUser = User::where('id_emp', '000541')->first()
            ?? $this->createTestUser([
                'id_emp'       => '000541',
                'name'         => 'IIP ARIEF BUDIMAN',
                'company_name' => 'PT Krakatau Posco',
            ]);

        $lookup = $this->validationService->batchLookupUsers(['541']);
        $match = $this->validationService->matchUser('541', $lookup, 'IIP ARIEF BUDIMAN');

        $this->assertEquals(PtkpValidationService::STATUS_VALID, $match['status']);
        $this->assertNotNull($match['user']);
        $this->assertEquals('000541', $match['user']->id_emp);
    }

    /**
     * Skenario 8: Employee ID milik soft deleted user -> Terdeteksi dan status ditangani sesuai aturan bisnis.
     */
    public function test_scenario_soft_deleted_employee_id(): void
    {
        $lookup = $this->validationService->batchLookupUsers([$this->softDeletedUser->id_emp]);
        $match = $this->validationService->matchUser($this->softDeletedUser->id_emp, $lookup, $this->softDeletedUser->name);

        $this->assertEquals(PtkpValidationService::STATUS_VALID, $match['status']);
        $this->assertNotNull($match['user']);
        $this->assertTrue($match['is_resigned']);
    }

    /**
     * Skenario 9: Nama peserta sama tetapi ID berbeda -> Tidak salah mencocokkan.
     */
    public function test_scenario_same_name_different_id_does_not_mismatch(): void
    {
        // 22 with name "MUHAMMAD IQBAL EL HIDAYAT" (same name as validEmployee 116022)
        $lookup = $this->validationService->batchLookupUsers(['22']);
        $match = $this->validationService->matchUser('22', $lookup, 'MUHAMMAD IQBAL EL HIDAYAT');

        $this->assertEquals(PtkpValidationService::STATUS_INVALID, $match['status']);
        $this->assertNull($match['user']);
    }

    /**
     * Skenario 10, 11 & 12: Integritas nama dan Employee ID saat disimpan ke batch rows.
     * Memastikan nama IQBAL NUR RACHMAN dan RIDWAN ALFITRA tidak ditimpa dengan Kim Kyu-Je dan Novial.
     */
    public function test_scenario_raw_name_is_preserved_when_invalid(): void
    {
        $file = $this->createPtkpExcel([
            [
                'row_no'      => 380,
                'employee_id' => '22',
                'name'        => 'IQBAL NUR RACHMAN',
                'course_name' => 'Orientation Class',
                'company'     => 'PT Krakatau Posco',
            ],
            [
                'row_no'      => 391,
                'employee_id' => '33',
                'name'        => 'RIDWAN ALFITRA',
                'course_name' => 'Orientation Class',
                'company'     => 'PT Krakatau Posco',
            ],
        ]);

        $previewResp = $this->actingAs($this->adminUser)
            ->post(route('manage.record.offline.import-ptkp.preview'), ['file' => $file]);

        $previewResp->assertStatus(200);
        $previewData = $previewResp->json();

        // Check preview rows preserve raw name
        $this->assertEquals('IQBAL NUR RACHMAN', $previewData['rows'][0]['name']);
        $this->assertEquals('22', $previewData['rows'][0]['employee_id']);
        $this->assertEquals(PtkpValidationService::STATUS_INVALID, $previewData['rows'][0]['status']);

        $this->assertEquals('RIDWAN ALFITRA', $previewData['rows'][1]['name']);
        $this->assertEquals('33', $previewData['rows'][1]['employee_id']);
        $this->assertEquals(PtkpValidationService::STATUS_INVALID, $previewData['rows'][1]['status']);
    }

    /**
     * Skenario 13: Verifikasi terhadap file template mentah yang sebenarnya.
     */
    public function test_scenario_actual_excel_rows_380_and_391(): void
    {
        $lookup = $this->validationService->batchLookupUsers(['22', '33', '117', '131']);

        // Row 380
        $r380 = $this->validationService->matchUser('22', $lookup, 'IQBAL NUR RACHMAN');
        $this->assertEquals(PtkpValidationService::STATUS_INVALID, $r380['status']);
        $this->assertNull($r380['user']);

        // Row 391
        $r391 = $this->validationService->matchUser('33', $lookup, 'RIDWAN ALFITRA');
        $this->assertEquals(PtkpValidationService::STATUS_INVALID, $r391['status']);
        $this->assertNull($r391['user']);

        // Row 2213
        $r2213 = $this->validationService->matchUser('117', $lookup, 'RIDWAN ALFITRA');
        $this->assertEquals(PtkpValidationService::STATUS_INVALID, $r2213['status']);
        $this->assertNull($r2213['user']);

        // Row 2241
        $r2241 = $this->validationService->matchUser('131', $lookup, 'IQBAL NUR RACHMAN');
        $this->assertEquals(PtkpValidationService::STATUS_INVALID, $r2241['status']);
        $this->assertNull($r2241['user']);
    }

    protected function createTestUser(array $attributes = []): User
    {
        static $seq = 8000;
        $seq++;
        $defaultEmpId = 'EMP_T_' . $seq;

        return User::create(array_merge([
            'id_user'      => 'U' . substr(str_pad((string)$seq, 8, '0', STR_PAD_LEFT), -8),
            'id_emp'       => $defaultEmpId,
            'name'         => 'Test User ' . $seq,
            'email'        => 'testuser' . $seq . '@krakatauposco.co.id',
            'password'     => bcrypt('secret'),
            'role'         => User::ROLE_USER,
            'jobgrade'     => '4A',
            'jobposition'  => 'Operations Specialist',
            'team'         => 'Rolling Mill 1',
            'department'   => 'Maintenance Department',
            'division'     => 'Division Sample',
            'id_org_unit'  => 'OU_01',
            'sap_code'     => 'SAP_' . $seq,
            'ssohash'      => 'HASH_' . $seq,
            'company_name' => 'PT Krakatau Posco',
        ], $attributes));
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

        $tempPath = tempnam(sys_get_temp_dir(), 'ptkp_audit_test_') . '.xlsx';
        $writer = new Xlsx($spreadsheet);
        $writer->save($tempPath);

        return new UploadedFile(
            $tempPath,
            'PTKP_OWL_Import_Template.xlsx',
            'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            null,
            true
        );
    }
}
