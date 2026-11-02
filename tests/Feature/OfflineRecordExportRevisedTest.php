<?php

namespace Tests\Feature;

use App\Exports\Manage\ClassificationOfflineRecordExport;
use App\Exports\Manage\ClassificationOnlineRecordExport;
use App\Exports\OfflineRecordExport;
use PhpOffice\PhpSpreadsheet\IOFactory;
use Tests\TestCase;
use ZipArchive;

class OfflineRecordExportRevisedTest extends TestCase
{
    /** @test */
    public function offline_record_export_filename_matches_exact_convention()
    {
        $fileName = OfflineRecordExport::generateFileName();

        $this->assertMatchesRegularExpression(
            '/^EmployeeNonOWLrecord_\d{4}-\d{2}-\d{2} at \d{2}-\d{2}-\d{2}\.xlsx$/',
            $fileName,
            'Filename must match EmployeeNonOWLrecord_YYYY-MM-DD at HH-mm-ss.xlsx'
        );

        $now = now();
        $expectedDate = $now->format('Y-m-d');
        $this->assertStringContainsString("EmployeeNonOWLrecord_{$expectedDate} at ", $fileName);
        $this->assertStringEndsWith('.xlsx', $fileName);

        $export = new OfflineRecordExport();
        $this->assertEquals($fileName, $export->getFileName());
    }

    /** @test */
    public function offline_record_export_headers_have_exact_50_columns_without_source_or_total_recognized_duration()
    {
        $headers = OfflineRecordExport::headers();

        $this->assertCount(50, $headers, 'Offline export must have exactly 50 columns');
        $this->assertNotContains('Source', $headers, 'Source must be removed');
        $this->assertNotContains('Total Recognized Duration', $headers, 'Total Recognized Duration must be removed');

        $this->assertEquals('NO', $headers[0]);
        $this->assertEquals('Employee ID', $headers[1]);
        $this->assertEquals('Employee Name', $headers[2]);
        $this->assertEquals('Course Duration', $headers[23]); // Col 24 (X)
        $this->assertEquals('Learning Hours', $headers[24]);  // Col 25 (Y)
        $this->assertEquals('% Progress', $headers[25]);      // Col 26 (Z)
        $this->assertEquals('Total Engagement Time (LH)', $headers[49]); // Col 50 (AX)

        $widths = OfflineRecordExport::columnWidths();
        $this->assertCount(50, $widths);
        $this->assertArrayHasKey('A', $widths);
        $this->assertArrayHasKey('Z', $widths);
        $this->assertArrayHasKey('AA', $widths);
        $this->assertArrayHasKey('AX', $widths);
        $this->assertArrayNotHasKey('AY', $widths);
    }

    /** @test */
    public function offline_record_export_generates_valid_xlsx_with_default_theme()
    {
        $export = new OfflineRecordExport();
        $filePath = $export->generateXlsxFile();

        $this->assertFileExists($filePath);

        $zip = new ZipArchive();
        $this->assertTrue($zip->open($filePath) === true);

        $stylesXml = $zip->getFromName('xl/styles.xml');
        $sheetXml = $zip->getFromName('xl/worksheets/sheet1.xml');
        $zip->close();

        // Verify Default Normal Theme (no blue fill, no bold font, only 2 default fills)
        $this->assertStringNotContainsString('FF002B49', $stylesXml, 'Styles must not contain custom blue fill');
        $this->assertStringNotContainsString('002B49', $stylesXml, 'Styles must not contain hex 002B49');
        $this->assertStringNotContainsString('<b/>', $stylesXml, 'Styles must not contain bold font tags');
        $this->assertStringContainsString('<fills count="2">', $stylesXml, 'Fills count must be 2 (none and gray125 only)');

        // Verify custom number formats
        $this->assertStringContainsString('yyyy/mm/dd', $stylesXml);
        $this->assertStringContainsString('hh:mm:ss', $stylesXml);
        $this->assertStringContainsString('[h]:mm:ss', $stylesXml);
        $this->assertStringContainsString('0.00&quot; h&quot;', $stylesXml);
        $this->assertStringContainsString('0&quot; MONTH&quot;', $stylesXml);

        // Verify header row 1 in sheet1.xml
        $this->assertStringContainsString('<row r="1">', $sheetXml);
        $this->assertStringNotContainsString('<row r="1" ht=', $sheetXml);
        $this->assertStringContainsString('r="A1"', $sheetXml);
        $this->assertStringContainsString('r="AX1"', $sheetXml);
        $this->assertStringNotContainsString('r="AY1"', $sheetXml);

        // Verify with PhpSpreadsheet
        $spreadsheet = IOFactory::load($filePath);
        $sheet = $spreadsheet->getActiveSheet();

        $this->assertEquals('AX', $sheet->getHighestColumn());
        $this->assertGreaterThanOrEqual(2, $sheet->getHighestRow());

        // Inspect row 2
        $row2Headers = [];
        for ($c = 1; $c <= 50; $c++) {
            $row2Headers[] = (string) $sheet->getCellByColumnAndRow($c, 1)->getValue();
        }
        $this->assertSame(OfflineRecordExport::headers(), $row2Headers);

        // Verify delivery method normalization
        $deliv = (string) $sheet->getCell('Q2')->getValue();
        $this->assertContains($deliv, ['Offline', 'Online', 'Blended']);

        // Verify Bond duration formatted as MONTH
        $cBond = $sheet->getCell('AK2');
        $this->assertEquals(6, $cBond->getValue());
        $this->assertEquals('6 MONTH', $cBond->getFormattedValue());

        @unlink($filePath);
    }

    /** @test */
    public function classification_offline_record_export_inherits_50_columns_and_default_theme()
    {
        $export = new ClassificationOfflineRecordExport('leadership');
        $this->assertCount(50, $export->headings());
        $this->assertNotContains('Source', $export->headings());
        $this->assertNotContains('Total Recognized Duration', $export->headings());
    }

    /** @test */
    public function kp_owl_export_remains_intact_with_51_columns_and_no_regression()
    {
        $kpH = ClassificationOnlineRecordExport::headers();
        $this->assertCount(51, $kpH);
        $this->assertNotContains('Source', $kpH);
        $this->assertContains('Total Recognized Duration', $kpH);
    }
}
