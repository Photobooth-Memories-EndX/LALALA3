<?php

namespace Tests\Feature;

use App\Models\OfflineTraining;
use App\Models\OfflineTrainingUser;
use App\Models\User;
use App\Services\Record\OfflineTrainingExcelSchema;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Tests\TestCase;

class OfflineTrainingImportDedicatedPageTest extends TestCase
{
    protected User $admin;
    protected ?User $regularUser;
    protected array $createdTempFiles = [];

    protected function setUp(): void
    {
        parent::setUp();
        $this->admin = User::where('role', '2')->first() ?? User::first();
        $this->regularUser = User::where('role', '1')->first();
    }

    protected function tearDown(): void
    {
        // Clean test DB records
        DB::table('offline_training_users')->where('company', 'PT DEDICATED TEST CORP')->delete();
        DB::table('offline_trainings')->where('category', 'TEST_DEDICATED_CAT')->delete();

        foreach ($this->createdTempFiles as $path) {
            if (File::exists($path)) {
                File::delete($path);
            }
        }

        $tempDir = storage_path('app/temp');
        if (File::isDirectory($tempDir)) {
            foreach (File::files($tempDir) as $file) {
                if (str_starts_with($file->getFilename(), 'owl_dedicated_test_')) {
                    File::delete($file->getPathname());
                }
            }
        }

        parent::tearDown();
    }

    protected function createSpreadsheet(array $rows): UploadedFile
    {
        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle(OfflineTrainingExcelSchema::DATA_SHEET_TITLE);

        $headers = OfflineTrainingExcelSchema::headers();
        foreach ($headers as $idx => $headerText) {
            $colLetter = Coordinate::stringFromColumnIndex($idx + 1);
            $sheet->setCellValue($colLetter . '1', $headerText);
        }

        $rowNum = 2;
        foreach ($rows as $row) {
            foreach (OfflineTrainingExcelSchema::columns() as $colIdx => $colDef) {
                $colLetter = Coordinate::stringFromColumnIndex($colIdx);
                $key = $colDef['key'];
                if ($key && isset($row[$key])) {
                    $sheet->setCellValue($colLetter . $rowNum, $row[$key]);
                }
            }
            $rowNum++;
        }

        $tempPath = tempnam(sys_get_temp_dir(), 'owl_dedicated_test_') . '.xlsx';
        $writer = new Xlsx($spreadsheet);
        $writer->save($tempPath);
        $this->createdTempFiles[] = $tempPath;

        return new UploadedFile(
            $tempPath,
            'test_import_non_ptkp.xlsx',
            'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            null,
            true
        );
    }

    /**
     * 1. Test offline index has button labeled 'Import Data Non PTKP' linking to dedicated URL.
     */
    public function test_offline_index_button_has_label_and_links_to_dedicated_page(): void
    {
        $response = $this->actingAs($this->admin)->get('/manage/record/offline');
        $response->assertStatus(200);

        // Required labels
        $response->assertSee('Import Data Non PTKP');
        $response->assertSee(route('manage.record.offline.import-non-ptkp'));
        $response->assertSee('Add Training');
        $response->assertSee('Manual');

        // Must not trigger modal
        $response->assertDontSee('data-bs-target="#offlineImportModal"', false);
    }

    /**
     * 2. Test dedicated import page renders with breadcrumbs, title, description, and stepper.
     */
    public function test_dedicated_import_page_renders_successfully(): void
    {
        $response = $this->actingAs($this->admin)->get('/manage/record/offline/import-non-ptkp');
        $response->assertStatus(200);

        // Header & Breadcrumb
        $response->assertSee('Import Data Non PTKP');
        $response->assertSee('Manage Record');
        $response->assertSee('Offline Record');
        $response->assertSee('Upload dan validasi data training offline Non PTKP sebelum diimpor ke sistem OWL.');
        $response->assertSee('Kembali ke Offline Record');
        $response->assertSee(route('manage.record.offline'));

        // Stepper: 4 stages
        $response->assertSee('Upload Spreadsheet');
        $response->assertSee('Validation Preview');
        $response->assertSee('Review Data');
        $response->assertSee('Import Records');
        $response->assertSee('id="stepper-step-1"', false);
        $response->assertSee('id="stepper-step-2"', false);
        $response->assertSee('id="stepper-step-3"', false);
        $response->assertSee('id="stepper-step-4"', false);

        // Stage 1 elements
        $response->assertSee('id="dropzone-container"', false);
        $response->assertSee('id="excel-file-input"', false);
        $response->assertSee('id="btn-validate-upload"', false);
        $response->assertSee('Download Template');
        $response->assertSee(route('manage.record.offline.import.template'));

        // Comparison table elements
        $response->assertSee('id="preview-main-table"', false);
        $response->assertSee('id="btn-action-confirm-import"', false);
        $response->assertSee('id="btn-reupload-file"', false);
        $response->assertSee('Perbandingan Data Sebelum Import');
    }

    /**
     * 3. Test legacy URL /manage/record/offline/import redirects to dedicated URL.
     */
    public function test_legacy_import_url_redirects_to_dedicated_import_page(): void
    {
        $response = $this->actingAs($this->admin)->get('/manage/record/offline/import');
        $response->assertStatus(302);
        $response->assertRedirect(route('manage.record.offline.import-non-ptkp'));
    }

    /**
     * 4. Test download template functionality remains functional.
     */
    public function test_template_download_works_from_dedicated_route(): void
    {
        $response = $this->actingAs($this->admin)->get('/manage/record/offline/import/template');
        $response->assertStatus(200);
        $this->assertEquals('application/vnd.openxmlformats-officedocument.spreadsheetml.sheet', $response->headers->get('content-type'));
        $this->assertStringContainsString('Non_KP_OWL_Import_Template.xlsx', $response->headers->get('content-disposition'));
    }

    /**
     * 5. Test validation preview does NOT write data to the database.
     */
    public function test_preview_does_not_write_to_database(): void
    {
        $trainingsBefore = OfflineTraining::count();
        $participantsBefore = OfflineTrainingUser::count();

        $rows = [
            [
                'employee_id'           => '100999',
                'name'                  => 'Dedicated Tester',
                'grade'                 => '3B',
                'job_position'          => 'Officer',
                'team'                  => 'Quality',
                'department'            => 'QA',
                'company'               => 'PT DEDICATED TEST CORP',
                'course_name'           => 'Dedicated Preview Safety 2026',
                'type'                  => 'In-House',
                'category'              => 'TEST_DEDICATED_CAT',
                'batch'                 => 'Batch 1',
                'main_classification'   => 'Safety',
                'learning_hours'        => 4.0,
                'progress'              => 100,
                'start_date'            => '2026-10-01',
                'end_date'              => '2026-10-02',
                'venue'                 => 'Training Room 2',
            ]
        ];

        $file = $this->createSpreadsheet($rows);

        $response = $this->actingAs($this->admin)->postJson('/manage/record/offline/import/preview', [
            'file' => $file,
        ]);

        $response->assertStatus(200);
        $response->assertJson(['success' => true]);

        // CRITICAL: Database counts MUST remain strictly identical!
        $this->assertEquals($trainingsBefore, OfflineTraining::count(), 'Preview must NOT create trainings!');
        $this->assertEquals($participantsBefore, OfflineTrainingUser::count(), 'Preview must NOT insert participants!');
    }

    /**
     * 6. Test confirm execution performs actual import inside transaction.
     */
    public function test_confirm_imports_valid_records(): void
    {
        $rows = [
            [
                'employee_id'           => '100888',
                'name'                  => 'Import Tester',
                'grade'                 => '3B',
                'job_position'          => 'Officer',
                'team'                  => 'Quality',
                'department'            => 'QA',
                'company'               => 'PT DEDICATED TEST CORP',
                'course_name'           => 'Dedicated Import Course 2026',
                'type'                  => 'In-House',
                'category'              => 'TEST_DEDICATED_CAT',
                'batch'                 => 'Batch 1',
                'main_classification'   => 'Safety',
                'learning_hours'        => 4.0,
                'progress'              => 100,
                'start_date'            => '2026-10-01',
                'end_date'              => '2026-10-02',
                'venue'                 => 'Training Room 2',
            ]
        ];

        $file = $this->createSpreadsheet($rows);

        $previewResponse = $this->actingAs($this->admin)->postJson('/manage/record/offline/import/preview', [
            'file' => $file,
        ]);

        $token = $previewResponse->json('token');
        $this->assertNotEmpty($token);

        $confirmResponse = $this->actingAs($this->admin)->postJson('/manage/record/offline/import/confirm', [
            'token' => $token,
        ]);

        $confirmResponse->assertStatus(200);
        $confirmResponse->assertJson(['success' => true]);
        $this->assertEquals(1, $confirmResponse->json('result.imported'));

        // Check record exists in DB
        $this->assertTrue(OfflineTraining::where('course_name', 'Dedicated Import Course 2026')->exists());
        $this->assertTrue(OfflineTrainingUser::where('company', 'PT DEDICATED TEST CORP')->exists());
    }
}
