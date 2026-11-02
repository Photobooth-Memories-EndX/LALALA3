<?php

namespace Tests\Feature;

use App\Exports\AllRecordExport;
use App\Exports\Manage\AllRecordExport as ManageAllRecordExport;
use App\Exports\OfflineRecordExport;
use App\Exports\OnlineRecordExport;
use App\Models\User;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Reader\IReadFilter;
use Tests\TestCase;
use ZipArchive;

class ManageRecordHeaderReadFilter implements IReadFilter
{
    public function readCell($columnAddress, $row, $worksheetName = ''): bool
    {
        return $row <= 5;
    }
}

class ManageRecordFull52ColumnExportTest extends TestCase
{
    protected ?User $admin;

    public const CANONICAL_52_COLUMNS = [
        'NO',
        'Source',
        'Employee ID',
        'Employee Name',
        'Grade',
        'Job Position',
        'Team',
        'Department',
        'Company',
        'Course Title',
        'Sub Course Title',
        'Type',
        'Category',
        'Level',
        'Batch',
        'Main Classification',
        'Sub Classification',
        'Delivery Method',
        'Sub Delivery Method',
        'Provider',
        'Sub Provider',
        'Provider Name',
        'Venue',
        'Instructor',
        'Course Duration',
        'Total Recognized Duration',
        'Learning Hours',
        '% Progress',
        'Progress Status',
        'Start Date',
        'End Date',
        'Start Time',
        'End Time',
        'Record Month',
        'Requirement',
        'Attendance',
        'Attendance Reason',
        'Bond Status',
        'Bond Duration',
        'Bond End Date',
        'Bond Amount',
        'Score (L1) - Course',
        'Score (L2)',
        'L2 Status',
        'Score (L3)',
        'L3 Status',
        'Certificate Availability',
        'Certificate Institution',
        'Certificate Issued Date',
        'Certificate Expired Date',
        'Person in Charge (PIC)',
        'Total Engagement Time (LH)',
    ];

    protected function setUp(): void
    {
        parent::setUp();
        $this->admin = User::where('role', '2')->first() ?? User::first();
    }

    /**
     * Test 1: Verify all export classes implement exactly 52 canonical headings in identical order.
     */
    public function test_all_three_export_classes_have_identical_52_canonical_headings(): void
    {
        $onlineHeadings = OnlineRecordExport::headers();
        $offlineHeadings = OfflineRecordExport::headers();
        $allManageHeadings = ManageAllRecordExport::headings();
        $allHeadings = AllRecordExport::headings();

        $this->assertCount(52, $onlineHeadings, 'OnlineRecordExport headers must count exactly 52');
        $this->assertCount(52, $offlineHeadings, 'OfflineRecordExport headers must count exactly 52');
        $this->assertCount(52, $allManageHeadings, 'Manage\AllRecordExport headings must count exactly 52');
        $this->assertCount(52, $allHeadings, 'AllRecordExport headings must count exactly 52');

        $this->assertSame(self::CANONICAL_52_COLUMNS, $onlineHeadings);
        $this->assertSame(self::CANONICAL_52_COLUMNS, $offlineHeadings);
        $this->assertSame(self::CANONICAL_52_COLUMNS, $allManageHeadings);
        $this->assertSame(self::CANONICAL_52_COLUMNS, $allHeadings);
    }

    /**
     * Test 2: Verify all export classes implement 52 column widths spanning A through AZ.
     */
    public function test_all_export_classes_have_column_widths_from_a_to_az(): void
    {
        $classes = [
            OnlineRecordExport::class,
            OfflineRecordExport::class,
            ManageAllRecordExport::class,
            AllRecordExport::class,
        ];

        foreach ($classes as $class) {
            $widths = $class::columnWidths();
            $this->assertCount(52, $widths, "{$class} columnWidths must count 52");
            $this->assertArrayHasKey('A', $widths);
            $this->assertArrayHasKey('Z', $widths);
            $this->assertArrayHasKey('AA', $widths);
            $this->assertArrayHasKey('AZ', $widths);
        }
    }

    /**
     * Helper to load top rows of exported Excel with minimal memory.
     */
    protected function loadTopRows(string $filePath): array
    {
        $reader = IOFactory::createReaderForFile($filePath);
        $reader->setReadFilter(new ManageRecordHeaderReadFilter());
        $reader->setReadDataOnly(true);
        $spreadsheet = $reader->load($filePath);
        $sheet = $spreadsheet->getActiveSheet();

        $rows = [];
        for ($r = 1; $r <= min(5, $sheet->getHighestRow()); $r++) {
            $row = [];
            for ($c = 1; $c <= 52; $c++) {
                $row[] = (string) $sheet->getCellByColumnAndRow($c, $r)->getValue();
            }
            $rows[$r] = $row;
        }

        $spreadsheet->disconnectWorksheets();
        return $rows;
    }

    /**
     * Test 3: KP OWL Export endpoint generates valid XLSX with exact filename and 52 columns.
     */
    public function test_kp_owl_export_generates_valid_xlsx(): void
    {
        $response = $this->actingAs($this->admin)->get('/manage/record/online/export?search=Andi');
        $response->assertStatus(200);

        $disposition = (string) $response->headers->get('content-disposition');
        $this->assertMatchesRegularExpression('/filename="EmployeeOWLrecord_\d{2}[A-Za-z]{3}\d{4} at \d{2}\.\d{2}\.\d{2}\.xlsx"/', $disposition);

        $filePath = $response->getFile()->getPathname();
        $this->assertFileExists($filePath);

        // Verify ZIP integrity
        $zip = new ZipArchive();
        $this->assertTrue($zip->open($filePath, ZipArchive::CHECKCONS) === true);
        $sheetXml = $zip->getFromName('xl/worksheets/sheet1.xml');
        $this->assertNotEmpty($sheetXml);
        $this->assertStringContainsString('r="A1"', $sheetXml);
        $this->assertStringContainsString('r="AZ1"', $sheetXml);
        $zip->close();

        // Verify PhpSpreadsheet load & headers
        $rows = $this->loadTopRows($filePath);
        $this->assertSame(self::CANONICAL_52_COLUMNS, $rows[1]);

        if (isset($rows[2])) {
            $this->assertSame('KP OWL', $rows[2][1], 'Source for KP OWL record must be "KP OWL"');
        }
    }

    /**
     * Test 4: Non KP OWL Export endpoint generates valid XLSX with exact filename and 52 columns.
     */
    public function test_non_kp_owl_export_generates_valid_xlsx(): void
    {
        $response = $this->actingAs($this->admin)->get('/manage/record/offline/export?search=Andi');
        $response->assertStatus(200);

        $disposition = (string) $response->headers->get('content-disposition');
        $this->assertMatchesRegularExpression('/filename="EmployeeNonOWLrecord_\d{4}-\d{2}-\d{2} at \d{2}-\d{2}-\d{2}\.xlsx"/', $disposition);

        $filePath = $response->getFile()->getPathname();
        $this->assertFileExists($filePath);

        // Verify ZIP integrity
        $zip = new ZipArchive();
        $this->assertTrue($zip->open($filePath, ZipArchive::CHECKCONS) === true);
        $sheetXml = $zip->getFromName('xl/worksheets/sheet1.xml');
        $this->assertNotEmpty($sheetXml);
        $this->assertStringContainsString('r="A1"', $sheetXml);
        $this->assertStringContainsString('r="AZ1"', $sheetXml);
        $zip->close();

        // Verify PhpSpreadsheet load & headers
        $rows = $this->loadTopRows($filePath);
        $this->assertSame(self::CANONICAL_52_COLUMNS, $rows[1]);

        if (isset($rows[2])) {
            $this->assertSame('Non KP OWL', $rows[2][1], 'Source for Non KP OWL record must be "Non KP OWL"');
            $this->assertSame('-', $rows[2][24], 'Course Duration for Non KP OWL must be strictly "-"');
            $this->assertSame('-', $rows[2][25], 'Total Recognized Duration for Non KP OWL must be strictly "-"');
        }
    }

    /**
     * Test 5: All Record Export endpoint generates valid XLSX with exact filename and 52 columns.
     */
    public function test_all_record_export_generates_valid_xlsx(): void
    {
        $response = $this->actingAs($this->admin)->get('/manage/record/all/export?search=Andi');
        $response->assertStatus(200);

        $disposition = (string) $response->headers->get('content-disposition');
        $this->assertMatchesRegularExpression('/filename="EmployeeAllrecord_\d{2}[A-Za-z]{3}\d{4} at \d{2}\.\d{2}\.\d{2}\.xlsx"/', $disposition);

        $filePath = $response->getFile()->getPathname();
        $this->assertFileExists($filePath);

        // Verify ZIP integrity
        $zip = new ZipArchive();
        $this->assertTrue($zip->open($filePath, ZipArchive::CHECKCONS) === true);
        $sheetXml = $zip->getFromName('xl/worksheets/sheet1.xml');
        $this->assertNotEmpty($sheetXml);
        $this->assertStringContainsString('r="A1"', $sheetXml);
        $this->assertStringContainsString('r="AZ1"', $sheetXml);
        $zip->close();

        // Verify PhpSpreadsheet load & headers
        $rows = $this->loadTopRows($filePath);
        $this->assertSame(self::CANONICAL_52_COLUMNS, $rows[1]);

        if (isset($rows[2])) {
            $this->assertContains($rows[2][1], ['KP OWL', 'Non KP OWL']);
        }
    }
}
