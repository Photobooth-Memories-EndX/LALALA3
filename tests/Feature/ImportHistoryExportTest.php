<?php

namespace Tests\Feature;

use App\Models\ImportBatch;
use App\Models\ImportBatchRow;
use App\Models\User;
use App\Services\Record\PtkpColumnDefinitions;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Str;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\IOFactory;
use Tests\TestCase;

class ImportHistoryExportTest extends TestCase
{
    use DatabaseTransactions;

    protected User $admin;
    protected ?User $regularUser;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::whereIn('role', [User::ROLE_ADMIN, User::ROLE_SUPER_ADMIN])->first()
            ?? $this->createTestUser(['role' => User::ROLE_ADMIN]);

        $this->regularUser = User::where('role', User::ROLE_USER)->first()
            ?? $this->createTestUser(['role' => User::ROLE_USER]);
    }

    protected function createTestUser(array $attrs = []): User
    {
        static $seq = 8000;
        $seq++;
        return User::create(array_merge([
            'name'         => 'Export Test User ' . $seq,
            'email'        => "export_user_{$seq}@posco.net",
            'password'     => bcrypt('secret'),
            'id_emp'       => 'EX_EMP_' . $seq,
            'id_user'      => 'EX_UID_' . $seq,
            'id_org_unit'  => 'OU_01',
            'team'         => 'Test Team',
            'department'   => 'Test Dept',
            'division'     => 'Test Division',
            'sap_code'     => 'SAP_' . $seq,
            'ssohash'      => 'HASH_' . $seq,
            'role'         => User::ROLE_USER,
            'company_name' => 'PT Krakatau Posco',
        ], $attrs));
    }

    protected function createBatchWithRows(): ImportBatch
    {
        $uuid = (string) Str::uuid();
        $batch = ImportBatch::create([
            'batch_uuid'        => $uuid,
            'source_type'       => ImportBatch::SOURCE_PTKP,
            'original_filename' => 'test_export_batch.xlsx',
            'file_path'         => "imports/ptkp/{$uuid}/original.xlsx",
            'file_disk'         => 'local',
            'file_hash'         => hash('sha256', 'dummy'),
            'file_size'         => 1024,
            'imported_by'       => $this->admin->id,
            'status'            => ImportBatch::STATUS_COMPLETED_WITH_WARNINGS,
            'total_rows'        => 5,
            'valid_rows'        => 1,
            'warning_rows'      => 1,
            'duplicate_rows'    => 1,
            'invalid_rows'      => 1,
            'imported_rows'     => 3,
            'skipped_rows'      => 2,
            'failed_rows'       => 0,
            'trainings_created' => 1,
            'trainings_reused'  => 0,
            'started_at'        => now()->subMinutes(10),
            'finished_at'       => now(),
        ]);

        // Row 1: VALID
        ImportBatchRow::create([
            'import_batch_id'   => $batch->id,
            'row_number'        => 2,
            'sheet_name'        => 'PTKP OWL Import',
            'employee_id'       => 'EMP_VALID_01',
            'employee_name'     => 'Budi Valid',
            'course_name'       => 'Laravel Advanced',
            'validation_status' => ImportBatchRow::STATUS_VALID,
            'action'            => ImportBatchRow::ACTION_IMPORTED,
            'messages'          => ['Valid row'],
            'row_data'          => [
                'employee_id' => 'EMP_VALID_01',
                'name'        => 'Budi Valid',
                'course_name' => 'Laravel Advanced',
                'grade'       => 'G5',
                'company'     => 'PT Krakatau Posco',
            ],
        ]);

        // Row 2: WARNING
        ImportBatchRow::create([
            'import_batch_id'   => $batch->id,
            'row_number'        => 3,
            'sheet_name'        => 'PTKP OWL Import',
            'employee_id'       => 'EMP_WARN_02',
            'employee_name'     => 'Siti Warning',
            'course_name'       => 'Safety Management',
            'validation_status' => ImportBatchRow::STATUS_WARNING,
            'action'            => ImportBatchRow::ACTION_IMPORTED,
            'messages'          => ['Non LMS User warning'],
            'row_data'          => [
                'employee_id' => 'EMP_WARN_02',
                'name'        => 'Siti Warning',
                'course_name' => 'Safety Management',
                'grade'       => 'G4',
                'company'     => 'PT Krakatau Posco',
            ],
        ]);

        // Row 3: DUPLICATE
        ImportBatchRow::create([
            'import_batch_id'   => $batch->id,
            'row_number'        => 4,
            'sheet_name'        => 'PTKP OWL Import',
            'employee_id'       => 'EMP_DUP_03',
            'employee_name'     => 'Rian Duplicate',
            'course_name'       => 'Safety Management',
            'validation_status' => ImportBatchRow::STATUS_DUPLICATE,
            'action'            => ImportBatchRow::ACTION_SKIPPED,
            'messages'          => ['Already enrolled'],
            'row_data'          => [
                'employee_id' => 'EMP_DUP_03',
                'name'        => 'Rian Duplicate',
                'course_name' => 'Safety Management',
                'grade'       => 'G5',
                'company'     => 'PT Krakatau Posco',
            ],
        ]);

        // Row 4: INVALID
        ImportBatchRow::create([
            'import_batch_id'   => $batch->id,
            'row_number'        => 5,
            'sheet_name'        => 'PTKP OWL Import',
            'employee_id'       => 'EMP_INV_04',
            'employee_name'     => 'Agus Salim Invalid',
            'course_name'       => 'Missing Title Course',
            'validation_status' => ImportBatchRow::STATUS_INVALID,
            'action'            => ImportBatchRow::ACTION_SKIPPED,
            'messages'          => ['Course is required'],
            'row_data'          => [
                'employee_id' => 'EMP_INV_04',
                'name'        => 'Agus Salim Invalid',
                'course_name' => 'Missing Title Course',
                'grade'       => 'G6',
                'company'     => 'PT Krakatau Posco',
            ],
        ]);

        // Row 5: INVALID COMPANY
        ImportBatchRow::create([
            'import_batch_id'   => $batch->id,
            'row_number'        => 6,
            'sheet_name'        => 'PTKP OWL Import',
            'employee_id'       => 'EMP_INVCOMP_05',
            'employee_name'     => 'Doni Invalid Company',
            'course_name'       => 'Plant Inspection',
            'validation_status' => ImportBatchRow::STATUS_INVALID_COMPANY,
            'action'            => ImportBatchRow::ACTION_IMPORTED,
            'messages'          => ['Company mismatch'],
            'row_data'          => [
                'employee_id' => 'EMP_INVCOMP_05',
                'name'        => 'Doni Invalid Company',
                'course_name' => 'Plant Inspection',
                'grade'       => 'G5',
                'company'     => 'Invalid Company PT',
            ],
        ]);

        return $batch;
    }

    /**
     * 1. Export ALL (no validation_status filter) generates 5 side-by-side groups in 1 sheet
     */
    public function test_export_all_generates_five_groups_side_by_side(): void
    {
        $batch = $this->createBatchWithRows();

        $response = $this->actingAs($this->admin)
            ->get(route('manage.record.offline.import-history.export', ['batch_uuid' => $batch->batch_uuid]));

        $response->assertStatus(200);
        $response->assertHeader('content-type', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');

        // Capture streamed content to temp file and parse with PhpSpreadsheet
        $tempFile = tempnam(sys_get_temp_dir(), 'export_test_') . '.xlsx';
        file_put_contents($tempFile, $response->streamedContent());

        $reader = IOFactory::createReaderForFile($tempFile);
        $reader->setReadDataOnly(true);
        $spreadsheet = $reader->load($tempFile);
        $sheet = $spreadsheet->getActiveSheet();

        // Row 1 must have the 5 groups
        $this->assertEquals('VALID', $sheet->getCell('A1')->getValue());
        $this->assertEquals('WARNING', $sheet->getCell(Coordinate::stringFromColumnIndex(47) . '1')->getValue());
        $this->assertEquals('DUPLICATE', $sheet->getCell(Coordinate::stringFromColumnIndex(93) . '1')->getValue());
        $this->assertEquals('INVALID', $sheet->getCell(Coordinate::stringFromColumnIndex(139) . '1')->getValue());
        $this->assertEquals('INVALID COMPANY', $sheet->getCell(Coordinate::stringFromColumnIndex(185) . '1')->getValue());

        // Far right Status column header
        $this->assertEquals('STATUS', $sheet->getCell(Coordinate::stringFromColumnIndex(231) . '1')->getValue());

        // Row 2 under VALID has canonical column headers
        $this->assertEquals('Employee ID', $sheet->getCell('A2')->getValue());
        $this->assertEquals('Employee Name', $sheet->getCell('B2')->getValue());
        $this->assertEquals('Person in Charge (PIC)', $sheet->getCell(Coordinate::stringFromColumnIndex(46) . '2')->getValue());

        // Data in Row 3 under VALID has Budi Valid
        $this->assertEquals('EMP_VALID_01', $sheet->getCell('A3')->getValue());
        $this->assertEquals('Budi Valid', $sheet->getCell('B3')->getValue());

        // Data in Row 3 under WARNING has Siti Warning (Cols 47, 48)
        $this->assertEquals('EMP_WARN_02', $sheet->getCell(Coordinate::stringFromColumnIndex(47) . '3')->getValue());
        $this->assertEquals('Siti Warning', $sheet->getCell(Coordinate::stringFromColumnIndex(48) . '3')->getValue());

        // Data in Row 3 under DUPLICATE has Rian Duplicate (Cols 93, 94)
        $this->assertEquals('EMP_DUP_03', $sheet->getCell(Coordinate::stringFromColumnIndex(93) . '3')->getValue());

        // Data in Row 3 under INVALID has Agus Salim Invalid (Cols 139, 140)
        $this->assertEquals('EMP_INV_04', $sheet->getCell(Coordinate::stringFromColumnIndex(139) . '3')->getValue());

        // Data in Row 3 under INVALID COMPANY has Doni (Cols 185, 186)
        $this->assertEquals('EMP_INVCOMP_05', $sheet->getCell(Coordinate::stringFromColumnIndex(185) . '3')->getValue());

        @unlink($tempFile);
    }

    /**
     * 2. Filter = INVALID only exports INVALID rows and shows single INVALID group header
     */
    public function test_export_filtered_by_invalid_only_exports_invalid_rows(): void
    {
        $batch = $this->createBatchWithRows();

        $response = $this->actingAs($this->admin)
            ->get(route('manage.record.offline.import-history.export', [
                'batch_uuid'        => $batch->batch_uuid,
                'validation_status' => 'INVALID',
            ]));

        $response->assertStatus(200);

        $tempFile = tempnam(sys_get_temp_dir(), 'export_test_') . '.xlsx';
        file_put_contents($tempFile, $response->streamedContent());

        $reader = IOFactory::createReaderForFile($tempFile);
        $reader->setReadDataOnly(true);
        $spreadsheet = $reader->load($tempFile);
        $sheet = $spreadsheet->getActiveSheet();

        // Row 1 header must be INVALID
        $this->assertEquals('INVALID', $sheet->getCell('A1')->getValue());

        // Row 2 headers
        $this->assertEquals('Employee ID', $sheet->getCell('A2')->getValue());
        $this->assertEquals('Person in Charge (PIC)', $sheet->getCell(Coordinate::stringFromColumnIndex(46) . '2')->getValue());
        $this->assertEquals('Status', $sheet->getCell(Coordinate::stringFromColumnIndex(47) . '2')->getValue());

        // Row 3 data: EMP_INV_04
        $this->assertEquals('EMP_INV_04', $sheet->getCell('A3')->getValue());
        $this->assertEquals('Agus Salim Invalid', $sheet->getCell('B3')->getValue());
        $this->assertEquals('INVALID', $sheet->getCell(Coordinate::stringFromColumnIndex(47) . '3')->getValue());

        // Row 4 must NOT contain any other rows (only 1 INVALID row was in batch)
        $this->assertEmpty($sheet->getCell('A4')->getValue());

        @unlink($tempFile);
    }

    /**
     * 3. Filter = VALID only exports VALID rows
     */
    public function test_export_filtered_by_valid_only_exports_valid_rows(): void
    {
        $batch = $this->createBatchWithRows();

        $response = $this->actingAs($this->admin)
            ->get(route('manage.record.offline.import-history.export', [
                'batch_uuid'        => $batch->batch_uuid,
                'validation_status' => 'VALID',
            ]));

        $response->assertStatus(200);

        $tempFile = tempnam(sys_get_temp_dir(), 'export_test_') . '.xlsx';
        file_put_contents($tempFile, $response->streamedContent());

        $reader = IOFactory::createReaderForFile($tempFile);
        $reader->setReadDataOnly(true);
        $spreadsheet = $reader->load($tempFile);
        $sheet = $spreadsheet->getActiveSheet();

        $this->assertEquals('VALID', $sheet->getCell('A1')->getValue());
        $this->assertEquals('EMP_VALID_01', $sheet->getCell('A3')->getValue());
        $this->assertEquals('VALID', $sheet->getCell(Coordinate::stringFromColumnIndex(47) . '3')->getValue());
        $this->assertEmpty($sheet->getCell('A4')->getValue());

        @unlink($tempFile);
    }

    /**
     * 4. Filter = WARNING only exports WARNING rows
     */
    public function test_export_filtered_by_warning_only_exports_warning_rows(): void
    {
        $batch = $this->createBatchWithRows();

        $response = $this->actingAs($this->admin)
            ->get(route('manage.record.offline.import-history.export', [
                'batch_uuid'        => $batch->batch_uuid,
                'validation_status' => 'WARNING',
            ]));

        $response->assertStatus(200);

        $tempFile = tempnam(sys_get_temp_dir(), 'export_test_') . '.xlsx';
        file_put_contents($tempFile, $response->streamedContent());

        $reader = IOFactory::createReaderForFile($tempFile);
        $reader->setReadDataOnly(true);
        $spreadsheet = $reader->load($tempFile);
        $sheet = $spreadsheet->getActiveSheet();

        $this->assertEquals('WARNING', $sheet->getCell('A1')->getValue());
        $this->assertEquals('EMP_WARN_02', $sheet->getCell('A3')->getValue());
        $this->assertEquals('WARNING', $sheet->getCell(Coordinate::stringFromColumnIndex(47) . '3')->getValue());

        @unlink($tempFile);
    }

    /**
     * 5. Filter = DUPLICATE only exports DUPLICATE rows
     */
    public function test_export_filtered_by_duplicate_only_exports_duplicate_rows(): void
    {
        $batch = $this->createBatchWithRows();

        $response = $this->actingAs($this->admin)
            ->get(route('manage.record.offline.import-history.export', [
                'batch_uuid'        => $batch->batch_uuid,
                'validation_status' => 'DUPLICATE',
            ]));

        $response->assertStatus(200);

        $tempFile = tempnam(sys_get_temp_dir(), 'export_test_') . '.xlsx';
        file_put_contents($tempFile, $response->streamedContent());

        $reader = IOFactory::createReaderForFile($tempFile);
        $reader->setReadDataOnly(true);
        $spreadsheet = $reader->load($tempFile);
        $sheet = $spreadsheet->getActiveSheet();

        $this->assertEquals('DUPLICATE', $sheet->getCell('A1')->getValue());
        $this->assertEquals('EMP_DUP_03', $sheet->getCell('A3')->getValue());
        $this->assertEquals('DUPLICATE', $sheet->getCell(Coordinate::stringFromColumnIndex(47) . '3')->getValue());

        @unlink($tempFile);
    }

    /**
     * 6. Filter = INVALID COMPANY only exports INVALID COMPANY rows
     */
    public function test_export_filtered_by_invalid_company_only_exports_invalid_company_rows(): void
    {
        $batch = $this->createBatchWithRows();

        $response = $this->actingAs($this->admin)
            ->get(route('manage.record.offline.import-history.export', [
                'batch_uuid'        => $batch->batch_uuid,
                'validation_status' => 'INVALID COMPANY',
            ]));

        $response->assertStatus(200);

        $tempFile = tempnam(sys_get_temp_dir(), 'export_test_') . '.xlsx';
        file_put_contents($tempFile, $response->streamedContent());

        $reader = IOFactory::createReaderForFile($tempFile);
        $reader->setReadDataOnly(true);
        $spreadsheet = $reader->load($tempFile);
        $sheet = $spreadsheet->getActiveSheet();

        $this->assertEquals('INVALID COMPANY', $sheet->getCell('A1')->getValue());
        $this->assertEquals('EMP_INVCOMP_05', $sheet->getCell('A3')->getValue());
        $this->assertEquals('INVALID COMPANY', $sheet->getCell(Coordinate::stringFromColumnIndex(47) . '3')->getValue());

        @unlink($tempFile);
    }

    /**
     * 7. Search filter applies correctly to export
     */
    public function test_export_with_search_query_applies_correctly(): void
    {
        $batch = $this->createBatchWithRows();

        // Search by employee ID
        $response = $this->actingAs($this->admin)
            ->get(route('manage.record.offline.import-history.export', [
                'batch_uuid'   => $batch->batch_uuid,
                'search_query' => 'EMP_INV_04',
            ]));

        $response->assertStatus(200);

        $tempFile = tempnam(sys_get_temp_dir(), 'export_test_') . '.xlsx';
        file_put_contents($tempFile, $response->streamedContent());

        $reader = IOFactory::createReaderForFile($tempFile);
        $reader->setReadDataOnly(true);
        $spreadsheet = $reader->load($tempFile);
        $sheet = $spreadsheet->getActiveSheet();

        // Group 4 (INVALID) should have EMP_INV_04 at col 139
        $this->assertEquals('EMP_INV_04', $sheet->getCell(Coordinate::stringFromColumnIndex(139) . '3')->getValue());
        // Group 1 (VALID) should be empty because EMP_VALID_01 does not match search
        $this->assertEmpty($sheet->getCell('A3')->getValue());

        @unlink($tempFile);
    }

    /**
     * 8. Action filter applies correctly to export
     */
    public function test_export_with_action_filter_applies_correctly(): void
    {
        $batch = $this->createBatchWithRows();

        // Filter: action=SKIPPED
        $response = $this->actingAs($this->admin)
            ->get(route('manage.record.offline.import-history.export', [
                'batch_uuid' => $batch->batch_uuid,
                'action'     => 'SKIPPED',
            ]));

        $response->assertStatus(200);

        $tempFile = tempnam(sys_get_temp_dir(), 'export_test_') . '.xlsx';
        file_put_contents($tempFile, $response->streamedContent());

        $reader = IOFactory::createReaderForFile($tempFile);
        $reader->setReadDataOnly(true);
        $spreadsheet = $reader->load($tempFile);
        $sheet = $spreadsheet->getActiveSheet();

        // VALID was IMPORTED, so it should not appear under VALID cols (A3 empty)
        $this->assertEmpty($sheet->getCell('A3')->getValue());

        // DUPLICATE was SKIPPED, so it should appear under DUPLICATE cols (93)
        $this->assertEquals('EMP_DUP_03', $sheet->getCell(Coordinate::stringFromColumnIndex(93) . '3')->getValue());

        @unlink($tempFile);
    }

    /**
     * 9. Import History Datatable renders "View Detail" button
     */
    public function test_import_history_datatable_renders_view_detail_button(): void
    {
        $batch = $this->createBatchWithRows();

        $response = $this->actingAs($this->admin)
            ->getJson(route('manage.record.offline.import-history.datatable'));

        $response->assertStatus(200);
        $json = $response->json();
        $this->assertNotEmpty($json['data']);

        $actionsHtml = $json['data'][0]['actions'];
        $this->assertStringContainsString('btn-batch-detail', $actionsHtml);
        $this->assertStringContainsString('View Detail', $actionsHtml);
        $this->assertStringContainsString('ti-eye', $actionsHtml);
    }

    /**
     * 10. Guests and unauthorized users cannot access export
     */
    public function test_guest_cannot_access_export(): void
    {
        $batch = $this->createBatchWithRows();

        $response = $this->get(route('manage.record.offline.import-history.export', [
            'batch_uuid' => $batch->batch_uuid,
        ]));

        // Should redirect to loginpage
        $response->assertRedirect(route('loginpage'));

        // Regular user should be forbidden (403)
        $this->actingAs($this->regularUser)
            ->get(route('manage.record.offline.import-history.export', ['batch_uuid' => $batch->batch_uuid]))
            ->assertStatus(403);
    }

    /**
     * 11. Test real database PTKP batch (batch 84) export with INVALID filter
     */
    public function test_real_ptkp_batch_export_with_filter_invalid(): void
    {
        $batch = ImportBatch::find(84);
        if (!$batch) {
            $this->markTestSkipped('Batch 84 not present in database.');
        }

        $response = $this->actingAs($this->admin)
            ->get(route('manage.record.offline.import-history.export', [
                'batch_uuid'        => $batch->batch_uuid,
                'validation_status' => 'INVALID',
            ]));

        $response->assertStatus(200);

        $tempFile = tempnam(sys_get_temp_dir(), 'real_ptkp_export_') . '.xlsx';
        file_put_contents($tempFile, $response->streamedContent());

        $reader = IOFactory::createReaderForFile($tempFile);
        $reader->setReadDataOnly(true);
        $spreadsheet = $reader->load($tempFile);
        $sheet = $spreadsheet->getActiveSheet();

        // Row 1 header must be INVALID
        $this->assertEquals('INVALID', $sheet->getCell('A1')->getValue());

        // Row 3 data is first INVALID row (101442 Agus Salim)
        $this->assertEquals('101442', $sheet->getCell('A3')->getValue());
        $this->assertEquals('AGUS SALIM', $sheet->getCell('B3')->getValue());
        $this->assertEquals('INVALID', $sheet->getCell(Coordinate::stringFromColumnIndex(47) . '3')->getValue());

        // Row 4 data is second INVALID row (102068 Athur Adi S)
        $this->assertEquals('102068', $sheet->getCell('A4')->getValue());
        $this->assertEquals('ATHUR ADI S', $sheet->getCell('B4')->getValue());
        $this->assertEquals('INVALID', $sheet->getCell(Coordinate::stringFromColumnIndex(47) . '4')->getValue());

        @unlink($tempFile);
    }

    /**
     * 12. Test real database Non-PTKP batch (batch 75) export
     */
    public function test_real_non_ptkp_batch_export(): void
    {
        $batch = ImportBatch::find(75);
        if (!$batch) {
            $this->markTestSkipped('Batch 75 not present in database.');
        }

        $response = $this->actingAs($this->admin)
            ->get(route('manage.record.offline.import-history.export', [
                'batch_uuid'        => $batch->batch_uuid,
                'validation_status' => 'VALID',
            ]));

        $response->assertStatus(200);

        $tempFile = tempnam(sys_get_temp_dir(), 'real_non_ptkp_export_') . '.xlsx';
        file_put_contents($tempFile, $response->streamedContent());

        $reader = IOFactory::createReaderForFile($tempFile);
        $reader->setReadDataOnly(true);
        $spreadsheet = $reader->load($tempFile);
        $sheet = $spreadsheet->getActiveSheet();

        // Row 1 header must be VALID
        $this->assertEquals('VALID', $sheet->getCell('A1')->getValue());

        // Row 3 data is EMP_RT_01
        $this->assertEquals('EMP_RT_01', $sheet->getCell('A3')->getValue());
        $this->assertEquals('Round Trip User', $sheet->getCell('B3')->getValue());
        $this->assertEquals('VALID', $sheet->getCell(Coordinate::stringFromColumnIndex(47) . '3')->getValue());

        @unlink($tempFile);
    }
}
