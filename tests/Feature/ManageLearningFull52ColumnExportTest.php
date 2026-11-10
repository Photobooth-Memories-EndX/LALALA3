<?php

namespace Tests\Feature;

use App\Exports\LearningExport;
use App\Models\Classification;
use App\Models\Content;
use App\Models\TrainingPlan;
use App\Models\User;
use App\Services\Learning\LearningDatatableService;
use App\Services\Learning\LearningExportService;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Reader\IReadFilter;
use Tests\TestCase;
use ZipArchive;

class ManageLearningHeaderReadFilter implements IReadFilter
{
    private int $maxRow;

    public function __construct(int $maxRow = 5)
    {
        $this->maxRow = $maxRow;
    }

    public function readCell($columnAddress, $row, $worksheetName = ''): bool
    {
        return $row <= $this->maxRow;
    }
}

class ManageLearningFull52ColumnExportTest extends TestCase
{
    use DatabaseTransactions;

    protected User $adminUser;

    protected function setUp(): void
    {
        parent::setUp();
        $this->adminUser = User::where('role', '2')->first() ?? User::factory()->create(['role' => '2']);
    }

    protected function inspectXlsxResponse($response, int $readRows = 5): array
    {
        $response->assertStatus(200);
        $this->assertStringContainsString('.xlsx', $response->headers->get('content-disposition', ''));

        $tempPath = tempnam(sys_get_temp_dir(), 'test_ml_export_') . '.xlsx';
        file_put_contents($tempPath, $response->getFile()->getContent());

        // 1. Validate ZIP container integrity
        $zip = new ZipArchive();
        $zipOpen = $zip->open($tempPath);
        $this->assertTrue($zipOpen === true, 'Downloaded export must be a valid XLSX ZIP container');
        $this->assertNotFalse($zip->locateName('[Content_Types].xml'), '[Content_Types].xml must exist');
        $this->assertNotFalse($zip->locateName('xl/workbook.xml'), 'xl/workbook.xml must exist');
        $this->assertNotFalse($zip->locateName('xl/worksheets/sheet1.xml'), 'xl/worksheets/sheet1.xml must exist');
        $this->assertNotFalse($zip->locateName('xl/styles.xml'), 'xl/styles.xml must exist');
        $zip->close();

        // 2. Read headers & top data rows via lightweight filter
        $reader = IOFactory::createReader('Xlsx');
        $reader->setReadFilter(new ManageLearningHeaderReadFilter($readRows));
        $reader->setReadDataOnly(true);
        $spreadsheet = $reader->load($tempPath);
        $sheet = $spreadsheet->getActiveSheet();

        $headers = [];
        for ($col = 1; $col <= 52; $col++) {
            $colLetter = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($col);
            $headers[] = (string) $sheet->getCell("{$colLetter}1")->getValue();
        }

        $row2 = [];
        for ($col = 1; $col <= 52; $col++) {
            $colLetter = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($col);
            $row2[] = (string) $sheet->getCell("{$colLetter}2")->getValue();
        }

        $spreadsheet->disconnectWorksheets();
        unset($spreadsheet);
        @unlink($tempPath);

        return [
            'headers' => $headers,
            'row2'    => $row2,
        ];
    }

    /**
     * TEST 1: Page 1 - Content Overview Export (/manage/learning/content/overview-of-ptkp-document-standard)
     */
    public function test_page1_content_overview_export_has_canonical_52_columns(): void
    {
        $slug = 'overview-of-ptkp-document-standard';
        $content = Content::where('slug', $slug)->first();
        if (!$content) {
            $this->markTestSkipped("Content {$slug} not found.");
        }

        $response = $this->actingAs($this->adminUser)->get(
            "/manage/learning/content/{$slug}?action=download"
        );

        $data = $this->inspectXlsxResponse($response);
        $canonicalHeadings = LearningExportService::headings();

        $this->assertCount(52, $data['headers']);
        $this->assertEquals($canonicalHeadings, $data['headers']);

        // Check Row 2 data mapping for Content Overview
        $row = $data['row2'];
        $this->assertEquals('1', $row[0]);                                            // Col 1: NO
        $this->assertEquals('KP OWL', $row[1]);                                       // Col 2: Source
        $this->assertNotEmpty($row[2]);                                               // Col 3: Employee ID
        $this->assertNotEmpty($row[3]);                                               // Col 4: Employee Name
        $this->assertEquals('Overview of PTKP Document Standard', $row[9]);           // Col 10: Course Title
        $this->assertTrue($row[10] === '-' || $row[10] === 'PTKP Documentation Management'); // Col 11: Sub Course Title
        $this->assertEquals('Video', $row[11]);                                       // Col 12: Type
        $this->assertTrue($row[12] === 'Content' || $row[12] === 'All Content' || str_starts_with($row[12], 'Playlist')); // Col 13: Category
        $this->assertEquals('Online', $row[17]);                                      // Col 18: Delivery Method
        $this->assertEquals('Internal', $row[19]);                                    // Col 20: Provider
        $this->assertEquals('KP-OWL', $row[21]);                                      // Col 22: Provider Name
        $this->assertEquals('Online LMS', $row[22]);                                  // Col 23: Venue
        $this->assertEquals('00:09:12', $row[24]);                                    // Col 25: Course Duration
    }

    /**
     * TEST 2: Page 2 - Playlist Export (/manage/learning/playlist/11)
     */
    public function test_page2_playlist_export_has_canonical_52_columns(): void
    {
        $playlistId = 11;
        $playlist = TrainingPlan::find($playlistId);
        if (!$playlist) {
            $this->markTestSkipped("Playlist {$playlistId} not found.");
        }

        $response = $this->actingAs($this->adminUser)->get(
            "/manage/learning/playlist/{$playlistId}?action=download"
        );

        $data = $this->inspectXlsxResponse($response);
        $canonicalHeadings = LearningExportService::headings();

        $this->assertCount(52, $data['headers']);
        $this->assertEquals($canonicalHeadings, $data['headers']);

        // Check Row 2 data mapping for Playlist
        $row = $data['row2'];
        $this->assertEquals('1', $row[0]);                                            // Col 1: NO
        $this->assertEquals('KP OWL', $row[1]);                                       // Col 2: Source
        $this->assertNotEmpty($row[2]);                                               // Col 3: Employee ID
        $this->assertNotEmpty($row[3]);                                               // Col 4: Employee Name
        // Playlist title in Sub Course Title or Category
        $this->assertEquals('PTKP Documentation Management', $row[10]);               // Col 11: Sub Course Title
        $this->assertTrue($row[12] === 'Playlist' || str_starts_with($row[12], 'Playlist: ')); // Col 13: Category
        $this->assertEquals('Online', $row[17]);                                      // Col 18: Delivery Method
        $this->assertEquals('Internal', $row[19]);                                    // Col 20: Provider
        $this->assertEquals('KP-OWL', $row[21]);                                      // Col 22: Provider Name
        $this->assertEquals('Online LMS', $row[22]);                                  // Col 23: Venue
    }

    /**
     * TEST 3: Page 3 - Category Export (/manage/learning/category/iso-standard)
     */
    public function test_page3_category_export_has_canonical_52_columns(): void
    {
        $slug = 'iso-standard';
        $category = Classification::where('slug', $slug)->first();
        if (!$category) {
            $this->markTestSkipped("Category {$slug} not found.");
        }

        $response = $this->actingAs($this->adminUser)->get(
            "/manage/learning/category/{$slug}?action=download"
        );

        $data = $this->inspectXlsxResponse($response);
        $canonicalHeadings = LearningExportService::headings();

        $this->assertCount(52, $data['headers']);
        $this->assertEquals($canonicalHeadings, $data['headers']);

        // Check Row 2 data mapping for Category
        $row = $data['row2'];
        $this->assertEquals('1', $row[0]);                                            // Col 1: NO
        $this->assertEquals('KP OWL', $row[1]);                                       // Col 2: Source
        $this->assertNotEmpty($row[2]);                                               // Col 3: Employee ID
        $this->assertNotEmpty($row[3]);                                               // Col 4: Employee Name
        $this->assertEquals('ISO Standard', $row[15]);                                // Col 16: Main Classification
        $this->assertEquals('Online', $row[17]);                                      // Col 18: Delivery Method
        $this->assertEquals('Internal', $row[19]);                                    // Col 20: Provider
        $this->assertEquals('KP-OWL', $row[21]);                                      // Col 22: Provider Name
        $this->assertEquals('Online LMS', $row[22]);                                  // Col 23: Venue
    }

    /**
     * TEST 4: Central Download Endpoint (/manage/learning/download) with filters
     */
    public function test_central_download_endpoint_with_filters(): void
    {
        // 1. Content filter via central download endpoint
        $responseContent = $this->actingAs($this->adminUser)->get(
            '/manage/learning/download?content_slug=overview-of-ptkp-document-standard&status=finished'
        );
        $dataContent = $this->inspectXlsxResponse($responseContent);
        $this->assertCount(52, $dataContent['headers']);
        $this->assertEquals('Finished', $dataContent['row2'][28]);                    // Col 29: Progress Status

        // 2. Playlist filter via central download endpoint
        $responsePlaylist = $this->actingAs($this->adminUser)->get(
            '/manage/learning/download?playlist_id=11&status=finished'
        );
        $dataPlaylist = $this->inspectXlsxResponse($responsePlaylist);
        $this->assertCount(52, $dataPlaylist['headers']);
        $this->assertEquals('Finished', $dataPlaylist['row2'][28]);                   // Col 29: Progress Status

        // 3. Category filter via central download endpoint
        $responseCategory = $this->actingAs($this->adminUser)->get(
            '/manage/learning/download?classification_slug=iso-standard&status=finished'
        );
        $dataCategory = $this->inspectXlsxResponse($responseCategory);
        $this->assertCount(52, $dataCategory['headers']);
        $this->assertEquals('Finished', $dataCategory['row2'][28]);                   // Col 29: Progress Status
    }

    /**
     * TEST 5: Active search filters restrict exported records
     */
    public function test_search_and_employee_filters_restrict_exported_records(): void
    {
        $exportAll = new LearningExport([], 'content', 'overview-of-ptkp-document-standard');
        $allCount = $exportAll->query()->count();
        $this->assertGreaterThan(0, $allCount);

        // Filter by employee ID 129458
        $exportEmp = new LearningExport(['employee_id' => '129458'], 'content', 'overview-of-ptkp-document-standard');
        $empCount = $exportEmp->query()->count();
        $this->assertGreaterThan(0, $empCount);
        $this->assertLessThanOrEqual($allCount, $empCount);

        // Verify each row matches the employee filter
        foreach ($exportEmp->query()->limit(5)->get() as $r) {
            $this->assertStringContainsString('129458', (string) $r->user_id_emp);
        }
    }
}
