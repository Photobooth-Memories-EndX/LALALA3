<?php

namespace Tests\Feature;

use App\Exports\OfflineClassificationExport;
use App\Exports\OfflineTrainingExport;
use App\Models\OfflineTraining;
use App\Models\OfflineTrainingUser;
use App\Models\User;
use App\Services\Record\OfflineTrainingExcelSchema;
use App\Services\Record\OfflineTrainingImportService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Facades\Excel;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use Tests\TestCase;

class OfflineExportImportRoundTripTest extends TestCase
{
    protected User $admin;
    protected array $createdTrainingIds = [];
    protected array $createdTempFiles = [];

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::where('role', '2')->first() ?? User::first();
    }

    protected function tearDown(): void
    {
        if (!empty($this->createdTrainingIds)) {
            DB::table('offline_training_users')->whereIn('offline_training_id', $this->createdTrainingIds)->delete();
            DB::table('offline_trainings')->whereIn('id', $this->createdTrainingIds)->delete();
        }

        foreach ($this->createdTempFiles as $file) {
            if (file_exists($file)) {
                @unlink($file);
            }
        }

        parent::tearDown();
    }

    /**
     * Helper to create a temp file path and track for cleanup.
     */
    protected function getTempFilePath(string $prefix = 'owl_roundtrip_'): string
    {
        $tempPath = tempnam(sys_get_temp_dir(), $prefix) . '.xlsx';
        $this->createdTempFiles[] = $tempPath;
        return $tempPath;
    }

    /**
     * Seed realistic data with rich variance across classifications, participants, and nullables.
     */
    protected function seedComprehensiveTestData(): array
    {
        // Training 1: Leadership with 2 participants
        $t1 = OfflineTraining::create([
            'course_name'           => 'Strategic Leadership Excellence',
            'sub_course_name'       => 'High-Stakes Decision Making',
            'type'                  => 'Public',
            'category'              => 'Executive Leadership',
            'level'                 => 'Advanced',
            'batch'                 => 'Batch 2026-A',
            'main_classification'   => 'RoundTrip Class',
            'sub_classification'    => 'Strategic Management',
            'counted_corporate_kpi' => true,
            'sub_corporate_kpi'     => 'Leadership Capability Index',
            'delivery_method'       => 'Classroom',
            'sub_delivery_method'   => 'Case Study & Simulation',
            'provider'              => 'External',
            'sub_provider'          => 'Consultancy',
            'provider_name'         => 'Harvard Business Review Indo',
            'venue'                 => 'Grand Ballroom Hotel Cilegon',
            'instructor'            => 'Prof. Dr. Suryanto, MBA',
            'duration_seconds'      => 28800, // 8 hours
            'learning_hours'        => 8.0,
            'start_date'            => '2026-04-10',
            'finish_date'           => '2026-04-11',
        ]);
        $this->createdTrainingIds[] = $t1->id;

        $p1 = OfflineTrainingUser::create([
            'offline_training_id' => $t1->id,
            'employee_id'         => 'RT_EMP001',
            'name'                => 'BUDI SANTOSO',
            'grade'               => '4A',
            'job_position'        => 'Operations Superintendent',
            'team'                => 'Blast Furnace 1',
            'department'          => 'Ironmaking',
            'company'             => 'PT Krakatau Posco',
            'learning_hours'      => 8.0,
            'progress'            => 100.0,
            'assessment_score'    => 92.5,
            'certificate'         => 'CERT-RT-001',
            'attendance'          => true,
            'attendance_reason'   => 'Fully attended all sessions',
            'bond_status'         => 'Yes',
            'bond_end_date'       => '2028-04-11',
            'bond_amount'         => 25000000.0,
            'l1_program'          => '5.0 Highly Effective',
            'l2_numerical'        => 92.5,
            'l2_verbal'           => 'Passed with Distinction',
            'l3_numerical'        => 90.0,
            'l3_verbal'           => 'Optimized blast furnace shift handovers',
            'pic'                 => 'HR Training Team',
        ]);

        $p2 = OfflineTrainingUser::create([
            'offline_training_id' => $t1->id,
            'employee_id'         => 'RT_EMP002',
            'name'                => 'SITI RAHMAWATI',
            'grade'               => '4B',
            'job_position'        => 'Quality Assurance Manager',
            'team'                => 'Inspection Team',
            'department'          => 'Quality Management',
            'company'             => 'PT Krakatau Posco',
            'learning_hours'      => 8.0,
            'progress'            => 100.0,
            'assessment_score'    => 88.0,
            'certificate'         => 'CERT-RT-002',
            'attendance'          => true,
            'attendance_reason'   => 'Attended all sessions',
            'bond_status'         => 'No',
            'bond_end_date'       => null,
            'bond_amount'         => null,
            'l1_program'          => '4.8 Very Good',
            'l2_numerical'        => 88.0,
            'l2_verbal'           => 'Passed',
            'l3_numerical'        => 85.0,
            'l3_verbal'           => 'Applied QA protocols',
            'pic'                 => 'HR Training Team',
        ]);

        // Training 2: Technical with nullable values and absent participant
        $t2 = OfflineTraining::create([
            'course_name'           => 'Hydraulic & Pneumatic Systems Maintenance',
            'sub_course_name'       => null,
            'type'                  => 'In-House',
            'category'              => 'Maintenance',
            'level'                 => 'Intermediate',
            'batch'                 => 'Batch 1',
            'main_classification'   => 'RoundTrip Class',
            'sub_classification'    => 'Mechanical Maintenance',
            'counted_corporate_kpi' => false,
            'sub_corporate_kpi'     => null,
            'delivery_method'       => 'Practical',
            'sub_delivery_method'   => 'Hands-on Workshop',
            'provider'              => 'Internal',
            'sub_provider'          => null,
            'provider_name'         => null,
            'venue'                 => 'Central Workshop Room B',
            'instructor'            => 'Supardi, S.T.',
            'duration_seconds'      => 14400, // 4 hours
            'learning_hours'        => 4.0,
            'start_date'            => '2026-05-20',
            'finish_date'           => '2026-05-20',
        ]);
        $this->createdTrainingIds[] = $t2->id;

        $p3 = OfflineTrainingUser::create([
            'offline_training_id' => $t2->id,
            'employee_id'         => 'RT_EMP003',
            'name'                => 'DEDI KURNIAWAN',
            'grade'               => '3A',
            'job_position'        => 'Mechanical Technician',
            'team'                => 'Continuous Casting Mill',
            'department'          => 'Steelmaking Maintenance',
            'company'             => 'PT Krakatau Posco',
            'learning_hours'      => 0.0,
            'progress'            => 0.0,
            'assessment_score'    => null,
            'certificate'         => null,
            'attendance'          => false,
            'attendance_reason'   => 'Emergency boiler repair duty',
            'bond_status'         => 'No',
            'bond_end_date'       => null,
            'bond_amount'         => null,
            'l1_program'          => null,
            'l2_numerical'        => null,
            'l2_verbal'           => null,
            'l3_numerical'        => null,
            'l3_verbal'           => null,
            'pic'                 => 'Plant Technical PIC',
        ]);

        return compact('t1', 'p1', 'p2', 't2', 'p3');
    }

    // =========================================================================
    // 1. WORKBOOK VISUAL & STRUCTURAL PARITY WITH IMPORT TEMPLATE
    // =========================================================================

    public function test_exported_workbook_has_exact_two_sheet_structure(): void
    {
        $this->seedComprehensiveTestData();

        $export = new OfflineTrainingExport(null, new Request());
        $tempPath = $this->getTempFilePath('global_export_');
        file_put_contents($tempPath, Excel::raw($export, \Maatwebsite\Excel\Excel::XLSX));

        $reader = IOFactory::createReaderForFile($tempPath);
        $spreadsheet = $reader->load($tempPath);

        // 1. Sheet count and names
        $this->assertEquals(2, $spreadsheet->getSheetCount(), 'Workbook must contain exactly 2 sheets');
        $this->assertEquals('Non KP OWL Import', $spreadsheet->getSheet(0)->getTitle());
        $this->assertEquals('Instructions', $spreadsheet->getSheet(1)->getTitle());

        // 2. Active sheet must be Sheet 0 (Non KP OWL Import)
        $this->assertEquals(0, $spreadsheet->getActiveSheetIndex(), 'Sheet 0 must be active by default');
    }

    public function test_exported_sheet1_matches_import_template_two_row_header_structure(): void
    {
        $this->seedComprehensiveTestData();

        $export = new OfflineTrainingExport(null, new Request());
        $tempPath = $this->getTempFilePath('header_structure_');
        file_put_contents($tempPath, Excel::raw($export, \Maatwebsite\Excel\Excel::XLSX));

        $reader = IOFactory::createReaderForFile($tempPath);
        $spreadsheet = $reader->load($tempPath);
        $sheet = $spreadsheet->getSheet(0);

        // Row 1 Grouped Headers
        $this->assertEquals('No', $sheet->getCell('A1')->getValue());
        $this->assertEquals('JOB INFORMATION', $sheet->getCell('B1')->getValue());
        $this->assertEquals('TRAINING INFORMATION', $sheet->getCell('I1')->getValue());
        $this->assertEquals('ATTENDANCE', $sheet->getCell('AH1')->getValue());
        $this->assertEquals('OBLIGATION', $sheet->getCell('AJ1')->getValue());
        $this->assertEquals('L1', $sheet->getCell('AM1')->getValue());
        $this->assertEquals('L2 (POST TEST)', $sheet->getCell('AN1')->getValue());
        $this->assertEquals('L3 (PROJECT)', $sheet->getCell('AP1')->getValue());

        // Merged ranges check
        $mergeRanges = $sheet->getMergeCells();
        $this->assertArrayHasKey('B1:H1', $mergeRanges, 'JOB INFORMATION must span B1:H1');
        $this->assertArrayHasKey('I1:AG1', $mergeRanges, 'TRAINING INFORMATION must span I1:AG1');
        $this->assertArrayHasKey('AH1:AI1', $mergeRanges, 'ATTENDANCE must span AH1:AI1');
        $this->assertArrayHasKey('AJ1:AL1', $mergeRanges, 'OBLIGATION must span AJ1:AL1');
        $this->assertArrayHasKey('AN1:AO1', $mergeRanges, 'L2 (POST TEST) must span AN1:AO1');
        $this->assertArrayHasKey('AP1:AR1', $mergeRanges, 'L3 (PROJECT) must span AP1:AR1');

        // Row 2 Child Column Headers check (all 44)
        $expectedHeaders = OfflineTrainingExcelSchema::headers();
        $this->assertCount(44, $expectedHeaders);

        for ($idx = 1; $idx <= 44; $idx++) {
            $colLetter = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($idx);
            $actualHeader = $sheet->getCell($colLetter . '2')->getValue();
            $this->assertEquals($expectedHeaders[$idx - 1], $actualHeader, "Header mismatch at column {$colLetter}2");
        }

        // Freeze pane
        $this->assertEquals('A3', $sheet->getFreezePane(), 'Freeze pane must freeze top 2 rows (A3)');

        // Row heights
        $this->assertEquals(28, (int) $sheet->getRowDimension(1)->getRowHeight());
        $this->assertEquals(36, (int) $sheet->getRowDimension(2)->getRowHeight());
    }

    public function test_exported_sheet2_contains_identical_import_guidelines(): void
    {
        $this->seedComprehensiveTestData();

        $export = new OfflineTrainingExport(null, new Request());
        $tempPath = $this->getTempFilePath('guidelines_');
        file_put_contents($tempPath, Excel::raw($export, \Maatwebsite\Excel\Excel::XLSX));

        $reader = IOFactory::createReaderForFile($tempPath);
        $spreadsheet = $reader->load($tempPath);
        $instructionSheet = $spreadsheet->getSheet(1);

        $this->assertStringContainsString('Guidelines', (string) $instructionSheet->getCell('A1')->getValue());
        $this->assertEquals('Section', $instructionSheet->getCell('A3')->getValue());
        $this->assertEquals('Guideline & Rule', $instructionSheet->getCell('B3')->getValue());
        $this->assertEquals('Details / Examples', $instructionSheet->getCell('C3')->getValue());

        // Check guideline items exist
        $this->assertEquals('1. Header Integrity', $instructionSheet->getCell('A4')->getValue());
        $this->assertEquals('2. Participant Records', $instructionSheet->getCell('A5')->getValue());
    }

    public function test_classification_export_shares_exact_same_workbook_structure(): void
    {
        $this->seedComprehensiveTestData();

        $export = new OfflineClassificationExport('roundtrip-class', new Request());
        $tempPath = $this->getTempFilePath('roundtrip_class_export_');
        file_put_contents($tempPath, Excel::raw($export, \Maatwebsite\Excel\Excel::XLSX));

        $reader = IOFactory::createReaderForFile($tempPath);
        $spreadsheet = $reader->load($tempPath);

        $this->assertEquals(2, $spreadsheet->getSheetCount());
        $this->assertEquals('Non KP OWL Import', $spreadsheet->getSheet(0)->getTitle());
        $this->assertEquals('Instructions', $spreadsheet->getSheet(1)->getTitle());

        $sheet = $spreadsheet->getSheet(0);
        $this->assertEquals('JOB INFORMATION', $sheet->getCell('B1')->getValue());
        $this->assertEquals('ID', $sheet->getCell('B2')->getValue());

        // Ensure data rows only contain RoundTrip Class
        $highestRow = $sheet->getHighestDataRow();
        $this->assertGreaterThanOrEqual(4, $highestRow); // Rows 1, 2 headers + participants

        for ($r = 3; $r <= $highestRow; $r++) {
            $classificationVal = $sheet->getCell('O' . $r)->getValue(); // Column O = Main Classification
            $this->assertEquals('RoundTrip Class', $classificationVal);
        }
    }

    // =========================================================================
    // 2. EXPORT -> IMPORT SEAMLESS ROUND-TRIP COMPATIBILITY
    // =========================================================================

    public function test_global_export_can_be_imported_back_with_zero_errors_and_zero_unknown_headers(): void
    {
        $data = $this->seedComprehensiveTestData();
        $expectedTotal = \App\Services\Record\RecordOfflineQueryService::buildGlobalQuery()->count();

        // 1. Export actual database records
        $export = new OfflineTrainingExport(null, new Request());
        $tempExportPath = $this->getTempFilePath('roundtrip_export_');
        file_put_contents($tempExportPath, Excel::raw($export, \Maatwebsite\Excel\Excel::XLSX));

        // 2. Create UploadedFile from the exported file
        $uploadedFile = new UploadedFile(
            $tempExportPath,
            'non-kp-owl-all-records-2026-09-28.xlsx',
            'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            null,
            true
        );

        // 3. Pass to OfflineTrainingImportService
        $service = app(OfflineTrainingImportService::class);
        $stored = $service->storeTemporaryFile($uploadedFile, $this->admin->id);
        $token = $stored['token'];

        // 4. Parse and preview
        $preview = $service->parseAndPreview($token, $this->admin->id);

        // Assert zero unknown headers
        $this->assertEmpty($preview['unknown_headers'], 'Importer must not report any unknown headers for exported file');

        // Assert zero invalid rows
        $this->assertEquals(0, $preview['stats']['invalid'], 'All exported rows must be valid with zero errors');

        // Assert total rows matches total active participants
        $this->assertEquals($expectedTotal, $preview['stats']['total_rows']);

        // Since records already exist in DB, they should be flagged as duplicates safely
        $this->assertEquals($expectedTotal, $preview['stats']['duplicate']);
    }

    public function test_export_round_trip_recreates_exact_training_and_participant_records(): void
    {
        $data = $this->seedComprehensiveTestData();

        // 1. Export database state to XLSX (scoped to RoundTrip Class for isolation)
        $export = new OfflineClassificationExport('roundtrip-class', new Request());
        $tempExportPath = $this->getTempFilePath('recreation_export_');
        file_put_contents($tempExportPath, Excel::raw($export, \Maatwebsite\Excel\Excel::XLSX));

        // 2. Clear database seeded records to test full recreation
        DB::table('offline_training_users')->whereIn('offline_training_id', $this->createdTrainingIds)->delete();
        DB::table('offline_trainings')->whereIn('id', $this->createdTrainingIds)->delete();
        $this->createdTrainingIds = [];

        // 3. Upload and import the exported file
        $uploadedFile = new UploadedFile(
            $tempExportPath,
            'exported_records.xlsx',
            'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            null,
            true
        );

        $service = app(OfflineTrainingImportService::class);
        $stored = $service->storeTemporaryFile($uploadedFile, $this->admin->id);
        $preview = $service->parseAndPreview($stored['token'], $this->admin->id);

        $this->assertEquals(3, $preview['stats']['total_rows']);
        $this->assertEquals(0, $preview['stats']['invalid']);
        $this->assertEquals(0, $preview['stats']['duplicate']);
        $this->assertEquals(2, $preview['stats']['new_trainings'], '2 distinct training events should be detected');

        // 4. Execute the import
        $result = $service->executeImport($stored['token'], $this->admin->id);

        $this->assertTrue($result['success']);
        $this->assertEquals(3, $result['imported']);
        $this->assertEquals(2, $result['trainings_created']);

        // Track new IDs for teardown cleanup
        $newTrainings = OfflineTraining::whereIn('course_name', [
            'Strategic Leadership Excellence',
            'Hydraulic & Pneumatic Systems Maintenance',
        ])->get();

        foreach ($newTrainings as $t) {
            $this->createdTrainingIds[] = $t->id;
        }

        // 5. Verify field fidelity of recreated records
        // Leadership Training verification
        $leadTraining = $newTrainings->firstWhere('course_name', 'Strategic Leadership Excellence');
        $this->assertNotNull($leadTraining);
        $this->assertEquals('RoundTrip Class', $leadTraining->main_classification);
        $this->assertEquals('Strategic Management', $leadTraining->sub_classification);
        $this->assertTrue((bool) $leadTraining->counted_corporate_kpi);
        $this->assertEquals(28800, $leadTraining->duration_seconds);
        $this->assertEquals(8.0, (float) $leadTraining->learning_hours);
        $this->assertEquals('2026-04-10', $leadTraining->start_date->format('Y-m-d'));
        $this->assertEquals('2026-04-11', $leadTraining->finish_date->format('Y-m-d'));
        $this->assertEquals('Harvard Business Review Indo', $leadTraining->provider_name);

        // Participants verification under Leadership
        $participants = OfflineTrainingUser::where('offline_training_id', $leadTraining->id)->get();
        $this->assertCount(2, $participants);

        // Participant 1: Budi Santoso
        $budi = $participants->firstWhere('employee_id', 'RT_EMP001');
        $this->assertNotNull($budi);
        $this->assertEquals('BUDI SANTOSO', $budi->name);
        $this->assertEquals('4A', $budi->grade);
        $this->assertEquals('Operations Superintendent', $budi->job_position);
        $this->assertEquals(100.0, (float) $budi->progress);
        $this->assertEquals(92.5, (float) $budi->assessment_score);
        $this->assertEquals('CERT-RT-001', $budi->certificate);
        $this->assertTrue((bool) $budi->attendance);
        $this->assertEquals('Yes', $budi->bond_status);
        $this->assertEquals('2028-04-11', $budi->bond_end_date->format('Y-m-d'));
        $this->assertEquals(25000000.0, (float) $budi->bond_amount);
        $this->assertEquals('5.0 Highly Effective', $budi->l1_program);
        $this->assertEquals(92.5, (float) $budi->l2_numerical);
        $this->assertEquals('Passed with Distinction', $budi->l2_verbal);
        $this->assertEquals(90.0, (float) $budi->l3_numerical);

        // Participant 2: Siti Rahmawati (No bond)
        $siti = $participants->firstWhere('employee_id', 'RT_EMP002');
        $this->assertNotNull($siti);
        $this->assertEquals('No', $siti->bond_status);
        $this->assertNull($siti->bond_end_date);
        $this->assertNull($siti->bond_amount);

        // Participant 3: Dedi Kurniawan (Absent with reason, null scores)
        $techTraining = $newTrainings->firstWhere('course_name', 'Hydraulic & Pneumatic Systems Maintenance');
        $this->assertNotNull($techTraining);
        $dedi = OfflineTrainingUser::where('offline_training_id', $techTraining->id)
            ->where('employee_id', 'RT_EMP003')
            ->first();
        $this->assertNotNull($dedi);
        $this->assertFalse((bool) $dedi->attendance);
        $this->assertEquals('Emergency boiler repair duty', $dedi->attendance_reason);
        $this->assertNull($dedi->assessment_score);
        $this->assertNull($dedi->l2_numerical);
    }

    public function test_edited_export_file_with_new_participant_imports_seamlessly(): void
    {
        $this->seedComprehensiveTestData();

        // 1. Export actual records (scoped to RoundTrip Class for isolation)
        $export = new OfflineClassificationExport('roundtrip-class', new Request());
        $tempExportPath = $this->getTempFilePath('edited_export_');
        file_put_contents($tempExportPath, Excel::raw($export, \Maatwebsite\Excel\Excel::XLSX));

        // 2. Open exported workbook, append a 4th participant to the Strategic Leadership training
        $reader = IOFactory::createReaderForFile($tempExportPath);
        $spreadsheet = $reader->load($tempExportPath);
        $sheet = $spreadsheet->getSheet(0);

        $newRow = 6; // Data rows are 3, 4, 5. New row is 6.
        $sheet->setCellValue('A' . $newRow, 4);
        $sheet->setCellValue('B' . $newRow, 'RT_EMP004');
        $sheet->setCellValue('C' . $newRow, 'AGUS SETIAWAN');
        $sheet->setCellValue('D' . $newRow, '3B');
        $sheet->setCellValue('E' . $newRow, 'Production Engineer');
        $sheet->setCellValue('F' . $newRow, 'Rolling Team');
        $sheet->setCellValue('G' . $newRow, 'Hot Strip Mill');
        $sheet->setCellValue('H' . $newRow, 'PT Krakatau Posco');
        // Match Strategic Leadership training grouping fields
        $sheet->setCellValue('I' . $newRow, 'Strategic Leadership Excellence');
        $sheet->setCellValue('J' . $newRow, 'High-Stakes Decision Making');
        $sheet->setCellValue('K' . $newRow, 'Public');
        $sheet->setCellValue('L' . $newRow, 'Executive Leadership');
        $sheet->setCellValue('M' . $newRow, 'Advanced');
        $sheet->setCellValue('N' . $newRow, 'Batch 2026-A');
        $sheet->setCellValue('O' . $newRow, 'RoundTrip Class');
        $sheet->setCellValue('P' . $newRow, 'Strategic Management');
        $sheet->setCellValue('Q' . $newRow, 'Yes');
        $sheet->setCellValue('S' . $newRow, 'Classroom');
        $sheet->setCellValue('U' . $newRow, 'External');
        $sheet->setCellValue('W' . $newRow, '08:00:00');
        $sheet->setCellValue('X' . $newRow, '8.0');
        $sheet->setCellValue('Y' . $newRow, '100');
        $sheet->setCellValue('Z' . $newRow, '2026-04-10');
        $sheet->setCellValue('AA' . $newRow, '2026-04-11');
        $sheet->setCellValue('AC' . $newRow, 'Harvard Business Review Indo');
        $sheet->setCellValue('AD' . $newRow, 'Grand Ballroom Hotel Cilegon');
        $sheet->setCellValue('AE' . $newRow, 'Prof. Dr. Suryanto, MBA');
        $sheet->setCellValue('AF' . $newRow, '95.0');
        $sheet->setCellValue('AG' . $newRow, 'CERT-RT-004');
        $sheet->setCellValue('AH' . $newRow, 'Yes');
        $sheet->setCellValue('AJ' . $newRow, 'No');
        $sheet->setCellValue('AR' . $newRow, 'HR Team');

        $editedTempPath = $this->getTempFilePath('edited_saved_');
        $writer = new \PhpOffice\PhpSpreadsheet\Writer\Xlsx($spreadsheet);
        $writer->save($editedTempPath);

        // 3. Import the edited file
        $uploadedFile = new UploadedFile(
            $editedTempPath,
            'edited_records.xlsx',
            'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            null,
            true
        );

        $service = app(OfflineTrainingImportService::class);
        $stored = $service->storeTemporaryFile($uploadedFile, $this->admin->id);
        $preview = $service->parseAndPreview($stored['token'], $this->admin->id);

        $this->assertEquals(4, $preview['stats']['total_rows']);
        $this->assertEquals(0, $preview['stats']['invalid']);
        $this->assertEquals(3, $preview['stats']['duplicate'], 'Existing 3 participants detected as duplicates');
        $this->assertEquals(1, $preview['stats']['valid'], '1 new participant valid for import');

        // 4. Execute import
        $result = $service->executeImport($stored['token'], $this->admin->id);
        $this->assertTrue($result['success']);
        $this->assertEquals(1, $result['imported']);
        $this->assertEquals(3, $result['skipped_duplicates']);
        $this->assertEquals(0, $result['trainings_created'], 'Should reuse existing training, not create duplicate');

        // 5. Verify Agus Setiawan was added to the existing training
        $agus = OfflineTrainingUser::where('employee_id', 'RT_EMP004')->first();
        $this->assertNotNull($agus);
        $this->assertEquals('AGUS SETIAWAN', $agus->name);
        $this->assertEquals(95.0, (float) $agus->assessment_score);
    }

    public function test_exported_and_imported_dash_values_convert_cleanly_to_null(): void
    {
        // Generate blank template via schema
        $spreadsheet = OfflineTrainingExcelSchema::generateTemplateSpreadsheet();
        $sheet = $spreadsheet->getSheet(0);

        // Append a row where optional fields are literal dashes '-' or '--' or 'N/A'
        $row = 3;
        $sheet->setCellValue('A' . $row, 1);
        $sheet->setCellValue('B' . $row, 'RT_DASH001');
        $sheet->setCellValue('C' . $row, 'DASH TEST PARTICIPANT');
        $sheet->setCellValue('D' . $row, '-');
        $sheet->setCellValue('E' . $row, '-');
        $sheet->setCellValue('F' . $row, '-');
        $sheet->setCellValue('G' . $row, '-');
        $sheet->setCellValue('H' . $row, '-');
        $sheet->setCellValue('I' . $row, 'Dash Handling Verification Course');
        $sheet->setCellValue('J' . $row, '-');
        $sheet->setCellValue('K' . $row, 'In-House');
        $sheet->setCellValue('L' . $row, '-');
        $sheet->setCellValue('M' . $row, '-');
        $sheet->setCellValue('N' . $row, 'Batch 1');
        $sheet->setCellValue('O' . $row, 'RoundTrip Class');
        $sheet->setCellValue('P' . $row, '-');
        $sheet->setCellValue('Q' . $row, '-');
        $sheet->setCellValue('R' . $row, '-');
        $sheet->setCellValue('S' . $row, '-');
        $sheet->setCellValue('T' . $row, '-');
        $sheet->setCellValue('U' . $row, '-');
        $sheet->setCellValue('V' . $row, '-');
        $sheet->setCellValue('W' . $row, '-'); // Duration '-'
        $sheet->setCellValue('X' . $row, '-'); // Learning Hours '-'
        $sheet->setCellValue('Y' . $row, '-'); // Progress '-'
        $sheet->setCellValue('Z' . $row, '2026-06-01');
        $sheet->setCellValue('AA' . $row, '2026-06-01');
        $sheet->setCellValue('AC' . $row, '-');
        $sheet->setCellValue('AD' . $row, '-');
        $sheet->setCellValue('AE' . $row, '-');
        $sheet->setCellValue('AF' . $row, '-'); // Assessment Score '-'
        $sheet->setCellValue('AG' . $row, '-');
        $sheet->setCellValue('AH' . $row, '-'); // Attendance '-'
        $sheet->setCellValue('AI' . $row, '-');
        $sheet->setCellValue('AJ' . $row, 'No');
        $sheet->setCellValue('AK' . $row, '-'); // Bond End Date '-'
        $sheet->setCellValue('AL' . $row, '-'); // Bond Amount '-'
        $sheet->setCellValue('AM' . $row, '-');
        $sheet->setCellValue('AN' . $row, '-'); // L2 Numerical '-'
        $sheet->setCellValue('AO' . $row, '-');
        $sheet->setCellValue('AP' . $row, '-'); // L3 Numerical '-'
        $sheet->setCellValue('AQ' . $row, '-');
        $sheet->setCellValue('AR' . $row, 'PIC Test');

        $tempPath = $this->getTempFilePath('dash_test_');
        $writer = new \PhpOffice\PhpSpreadsheet\Writer\Xlsx($spreadsheet);
        $writer->save($tempPath);

        $uploadedFile = new UploadedFile(
            $tempPath,
            'dash_test.xlsx',
            'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            null,
            true
        );

        $service = app(OfflineTrainingImportService::class);
        $stored = $service->storeTemporaryFile($uploadedFile, $this->admin->id);
        $preview = $service->parseAndPreview($stored['token'], $this->admin->id);

        $this->assertEquals(1, $preview['stats']['total_rows']);
        $this->assertEquals(0, $preview['stats']['invalid'], 'Dashes in optional fields must not cause validation errors');

        $result = $service->executeImport($stored['token'], $this->admin->id);
        $this->assertTrue($result['success']);
        $this->assertEquals(1, $result['imported']);

        // Verify clean nulls in database
        $createdTraining = OfflineTraining::where('course_name', 'Dash Handling Verification Course')->first();
        $this->assertNotNull($createdTraining);
        $this->createdTrainingIds[] = $createdTraining->id;

        $createdParticipant = OfflineTrainingUser::where('employee_id', 'RT_DASH001')->first();
        $this->assertNotNull($createdParticipant);

        // Assert all dashed fields were normalized to null (NOT string '-')
        $this->assertNull($createdParticipant->grade);
        $this->assertNull($createdParticipant->job_position);
        $this->assertNull($createdParticipant->assessment_score);
        $this->assertNull($createdParticipant->attendance);
        $this->assertNull($createdParticipant->bond_end_date);
        $this->assertNull($createdParticipant->bond_amount);
        $this->assertNull($createdParticipant->l2_numerical);
        $this->assertNull($createdParticipant->l3_numerical);
    }
}
