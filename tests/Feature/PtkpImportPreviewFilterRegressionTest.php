<?php

namespace Tests\Feature;

use App\Models\User;
use App\Services\OfflineTraining\PtkpImportService;
use App\Services\OfflineTraining\PtkpValidationService;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Http\UploadedFile;
use PhpOffice\PhpSpreadsheet\IOFactory;
use Tests\TestCase;

class PtkpImportPreviewFilterRegressionTest extends TestCase
{
    use DatabaseTransactions;

    protected User $adminUser;
    protected string $excelPath;
    protected PtkpImportService $importService;

    protected function setUp(): void
    {
        parent::setUp();

        $this->adminUser = User::whereIn('role', [User::ROLE_ADMIN, User::ROLE_SUPER_ADMIN])->first()
            ?? User::first();

        $this->excelPath = base_path('docs/PTKP_OWL_Import_Template fix data 11 11.xlsx');
        $this->importService = app(PtkpImportService::class);
    }

    /**
     * Test 1: Upload preview returns 3,405 valid and 215 invalid.
     */
    public function test_upload_preview_returns_exact_expected_statistics(): void
    {
        $this->assertFileExists($this->excelPath);

        $tempCopy = tempnam(sys_get_temp_dir(), 'ptkp_reg_') . '.xlsx';
        copy($this->excelPath, $tempCopy);

        $file = new UploadedFile(
            $tempCopy,
            'PTKP_OWL_Import_Template fix data 11 11.xlsx',
            'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            null,
            true
        );

        $response = $this->actingAs($this->adminUser)
            ->postJson(route('manage.record.offline.import-ptkp.preview'), [
                'file' => $file,
            ]);

        $response->assertStatus(200);
        $data = $response->json();

        $this->assertTrue($data['success']);
        $stats = $data['stats'];

        $this->assertSame(3679, $stats['total_rows'], 'Total rows must be 3679');
        $this->assertSame(3405, $stats['valid'], 'Valid rows must be exactly 3405');
        $this->assertSame(58, $stats['warning'], 'Warning rows must be 58');
        $this->assertSame(1, $stats['duplicate'], 'Duplicate rows must be 1');
        $this->assertSame(215, $stats['invalid'], 'Invalid rows must be exactly 215');
        $this->assertSame(215, $stats['invalid_id_count'], 'All 215 invalid rows must be invalid ID');
        $this->assertSame(0, $stats['invalid_company'], 'Invalid company must be 0');
        $this->assertSame(3463, $stats['importable_count'], 'Importable count must be 3463 (3405 valid + 58 warning)');
    }

    /**
     * Test 2: The four specific rows (380, 391, 494, 505) are strictly INVALID.
     */
    public function test_four_target_rows_are_strictly_invalid_with_raw_identity(): void
    {
        $reader = IOFactory::createReaderForFile($this->excelPath);
        $spreadsheet = $reader->load($this->excelPath);

        $reflection = new \ReflectionClass($this->importService);
        $method = $reflection->getMethod('parseAndEvaluateWorkbook');
        $method->setAccessible(true);
        $result = $method->invoke($this->importService, $spreadsheet->getSheet(0));

        $rowsByNumber = [];
        foreach ($result['all_rows'] as $r) {
            $rowsByNumber[$r['excel_row']] = $r;
        }

        // Row 380: 22 | IQBAL NUR RACHMAN | New Employee Orientation
        $row380 = $rowsByNumber[380];
        $this->assertSame(PtkpValidationService::STATUS_INVALID, $row380['status']);
        $this->assertSame('22', $row380['employee_id']);
        $this->assertSame('IQBAL NUR RACHMAN', $row380['name']);
        $this->assertTrue($row380['is_invalid_id']);
        $this->assertContains("ERROR: Employee ID '22' was not found in LMS users database.", $row380['messages']);

        // Row 391: 33 | RIDWAN ALFITRA | New Employee Orientation
        $row391 = $rowsByNumber[391];
        $this->assertSame(PtkpValidationService::STATUS_INVALID, $row391['status']);
        $this->assertSame('33', $row391['employee_id']);
        $this->assertSame('RIDWAN ALFITRA', $row391['name']);
        $this->assertTrue($row391['is_invalid_id']);
        $this->assertContains("ERROR: Employee ID '33' was not found in LMS users database.", $row391['messages']);

        // Row 494: 22 | IQBAL NUR RACHMAN | NewGen Capability Development Vol. 1-2026
        $row494 = $rowsByNumber[494];
        $this->assertSame(PtkpValidationService::STATUS_INVALID, $row494['status']);
        $this->assertSame('22', $row494['employee_id']);
        $this->assertSame('IQBAL NUR RACHMAN', $row494['name']);
        $this->assertTrue($row494['is_invalid_id']);
        $this->assertContains("ERROR: Employee ID '22' was not found in LMS users database.", $row494['messages']);

        // Row 505: 33 | RIDWAN ALFITRA | NewGen Capability Development Vol. 1-2026
        $row505 = $rowsByNumber[505];
        $this->assertSame(PtkpValidationService::STATUS_INVALID, $row505['status']);
        $this->assertSame('33', $row505['employee_id']);
        $this->assertSame('RIDWAN ALFITRA', $row505['name']);
        $this->assertTrue($row505['is_invalid_id']);
        $this->assertContains("ERROR: Employee ID '33' was not found in LMS users database.", $row505['messages']);
    }

    /**
     * Test 3: Simulation of frontend search filtering matrix.
     */
    public function test_frontend_search_and_filter_matrix(): void
    {
        $reader = IOFactory::createReaderForFile($this->excelPath);
        $spreadsheet = $reader->load($this->excelPath);

        $reflection = new \ReflectionClass($this->importService);
        $method = $reflection->getMethod('parseAndEvaluateWorkbook');
        $method->setAccessible(true);
        $result = $method->invoke($this->importService, $spreadsheet->getSheet(0));
        $allRows = $result['parsed_rows'];

        // Helper replicating enhanced frontend filter
        $filterFn = function (array $rows, string $currentFilter = 'all', array $search = []) {
            $qEmpId = strtolower(trim((string) ($search['empId'] ?? '')));
            $exactEmpIdExists = false;
            if ($qEmpId !== '') {
                foreach ($rows as $r) {
                    $e = strtolower(trim((string) ($r['employee_id'] ?? '')));
                    if ($e === $qEmpId) {
                        $exactEmpIdExists = true;
                        break;
                    }
                }
            }

            return array_filter($rows, function ($row) use ($currentFilter, $search, $qEmpId, $exactEmpIdExists) {
                if ($currentFilter !== 'all') {
                    if ($currentFilter === 'valid' && $row['status'] !== 'VALID') return false;
                    if ($currentFilter === 'warning' && $row['status'] !== 'WARNING') return false;
                    if ($currentFilter === 'duplicate' && $row['status'] !== 'DUPLICATE') return false;
                    if ($currentFilter === 'invalid' && $row['status'] !== 'INVALID') return false;
                }
                if ($qEmpId !== '') {
                    $rowEmpId = strtolower(trim((string) ($row['employee_id'] ?? '')));
                    if ($exactEmpIdExists) {
                        if ($rowEmpId !== $qEmpId) return false;
                    } else {
                        if (strpos($rowEmpId, $qEmpId) === false) return false;
                    }
                }
                if (!empty($search['name'])) {
                    $val = strtolower((string) ($row['name'] ?? ''));
                    if (strpos($val, strtolower($search['name'])) === false) return false;
                }
                return true;
            });
        };

        // 1. Filter ALL
        $all = $filterFn($allRows, 'all');
        $this->assertCount(3679, $all);

        // 2. Filter VALID
        $valid = $filterFn($allRows, 'valid');
        $this->assertCount(3405, $valid);
        $validRowNumbers = array_column($valid, 'excel_row');
        $this->assertNotContains(380, $validRowNumbers);
        $this->assertNotContains(391, $validRowNumbers);
        $this->assertNotContains(494, $validRowNumbers);
        $this->assertNotContains(505, $validRowNumbers);

        // 3. Filter INVALID
        $invalid = $filterFn($allRows, 'invalid');
        $this->assertCount(215, $invalid);
        $invalidRowNumbers = array_column($invalid, 'excel_row');
        $this->assertContains(380, $invalidRowNumbers);
        $this->assertContains(391, $invalidRowNumbers);
        $this->assertContains(494, $invalidRowNumbers);
        $this->assertContains(505, $invalidRowNumbers);

        // 4. Search Employee ID = 22
        $emp22 = $filterFn($allRows, 'all', ['empId' => '22']);
        $this->assertCount(2, $emp22);
        $this->assertEquals([380, 494], array_values(array_column($emp22, 'excel_row')));

        // 5. Search Employee ID = 33
        $emp33 = $filterFn($allRows, 'all', ['empId' => '33']);
        $this->assertCount(2, $emp33);
        $this->assertEquals([391, 505], array_values(array_column($emp33, 'excel_row')));

        // 6. Search Name = IQBAL NUR RACHMAN
        $nameIqbal = $filterFn($allRows, 'all', ['name' => 'IQBAL NUR RACHMAN']);
        $this->assertCount(3, $nameIqbal);
        $this->assertContains(380, array_column($nameIqbal, 'excel_row'));
        $this->assertContains(494, array_column($nameIqbal, 'excel_row'));

        // 7. Search Name = RIDWAN ALFITRA
        $nameRidwan = $filterFn($allRows, 'all', ['name' => 'RIDWAN ALFITRA']);
        $this->assertCount(3, $nameRidwan);
        $this->assertContains(391, array_column($nameRidwan, 'excel_row'));
        $this->assertContains(505, array_column($nameRidwan, 'excel_row'));

        // 8. Combination: Search ID 22 + Filter INVALID
        $emp22Invalid = $filterFn($allRows, 'invalid', ['empId' => '22']);
        $this->assertCount(2, $emp22Invalid);

        // 9. Combination: Search ID 22 + Filter VALID
        $emp22Valid = $filterFn($allRows, 'valid', ['empId' => '22']);
        $this->assertCount(0, $emp22Valid);

        // 10. Combination: Search ID 33 + Filter VALID
        $emp33Valid = $filterFn($allRows, 'valid', ['empId' => '33']);
        $this->assertCount(0, $emp33Valid);
    }
}
