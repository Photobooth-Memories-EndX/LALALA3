<?php

namespace Tests\Feature;

use App\Models\User;
use App\Services\Record\OfflineTrainingExcelSchema;
use App\Services\Record\OfflineTrainingImportService;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Schema;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Tests\TestCase;

class OfflineTrainingImportModalTest extends TestCase
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
        // Clean up any test database records
        DB::table('offline_training_users')->where('company', 'PT Krakatau Posco Test')->delete();
        DB::table('offline_trainings')->where('category', 'Technical Competency Test')->delete();

        // Clean up temp files
        foreach ($this->createdTempFiles as $path) {
            if (File::exists($path)) {
                File::delete($path);
            }
        }

        $tempDir = storage_path('app/temp');
        if (File::isDirectory($tempDir)) {
            foreach (File::files($tempDir) as $file) {
                if (str_starts_with($file->getFilename(), 'owl_modal_test_')) {
                    File::delete($file->getPathname());
                }
            }
        }

        parent::tearDown();
    }

    /**
     * 1. Test header action buttons on /manage/record/offline:
     *    - [+ Add Training] (Primary)
     *    - [Import Excel] (Secondary, triggers modal)
     *    - [Download Template] (Outline, downloads XLSX template)
     */
    public function test_offline_page_renders_three_aligned_action_buttons(): void
    {
        $response = $this->actingAs($this->admin)->get('/manage/record/offline');
        $response->assertStatus(200);

        // Verify "+ Add Training"
        $response->assertSee('Add Training');

        // Verify "Import Excel" button targeting #offlineImportModal
        $response->assertSee('Import Excel');
        $response->assertSee('data-bs-toggle="modal"', false);
        $response->assertSee('data-bs-target="#offlineImportModal"', false);

        // Verify "Download Template" button
        $response->assertSee('Download Template');
        $response->assertSee(route('manage.record.offline.import.template'));
    }

    /**
     * 2. Test modal DOM structure and 5 distinct state containers:
     *    - Upload, Validating, Preview, Importing, Result
     */
    public function test_offline_import_modal_component_structure_and_states(): void
    {
        $response = $this->actingAs($this->admin)->get('/manage/record/offline');
        $response->assertStatus(200);

        // Modal container attributes
        $response->assertSee('id="offlineImportModal"', false);
        $response->assertSee('data-bs-backdrop="static"', false);
        $response->assertSee('id="offline-import-modal-dialog"', false);
        $response->assertSee('modal-dialog-centered', false);

        // State containers
        $response->assertSee('id="modal-import-state-upload"', false);
        $response->assertSee('id="modal-import-state-loading"', false);
        $response->assertSee('id="modal-import-state-preview"', false);
        $response->assertSee('id="modal-import-state-importing"', false);
        $response->assertSee('id="modal-import-state-result"', false);

        // Upload state components
        $response->assertSee('id="modal-dropzone-container"', false);
        $response->assertSee('id="modal-excel-file-input"', false);
        $response->assertSee('id="modal-btn-validate"', false);
        $response->assertSee('id="modal-upload-error"', false);

        // Preview state KPI cards
        $response->assertSee('id="modal-kpi-total-rows"', false);
        $response->assertSee('id="modal-kpi-valid-rows"', false);
        $response->assertSee('id="modal-kpi-warning-rows"', false);
        $response->assertSee('id="modal-kpi-duplicate-rows"', false);
        $response->assertSee('id="modal-kpi-invalid-rows"', false);
        $response->assertSee('id="modal-kpi-training-events"', false);

        // Preview compact table and buttons
        $response->assertSee('id="modal-preview-table"', false);
        $response->assertSee('id="modal-preview-tbody"', false);
        $response->assertSee('id="modal-btn-confirm"', false);
        $response->assertSee('id="modal-btn-cancel-preview"', false);

        // Result state metrics
        $response->assertSee('id="modal-result-imported"', false);
        $response->assertSee('id="modal-result-created"', false);
        $response->assertSee('id="modal-result-reused"', false);
        $response->assertSee('id="modal-result-skipped"', false);
        $response->assertSee('id="modal-btn-result-close"', false);
    }

    /**
     * 3. Test template download endpoint requires authentication and permission.
     */
    public function test_template_download_endpoint_requires_auth_and_permission(): void
    {
        // Guest redirected
        $guestResponse = $this->get('/manage/record/offline/import/template');
        $this->assertTrue($guestResponse->isRedirect() || $guestResponse->getStatusCode() === 401);

        // Regular user forbidden
        if ($this->regularUser) {
            $unauthResponse = $this->actingAs($this->regularUser)->get('/manage/record/offline/import/template');
            $unauthResponse->assertStatus(403);
        }

        // Admin success
        $response = $this->actingAs($this->admin)->get('/manage/record/offline/import/template');
        $response->assertStatus(200);
        $this->assertTrue($response->headers->get('content-type') === 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        $this->assertStringContainsString('Non_KP_OWL_Import_Template.xlsx', $response->headers->get('content-disposition'));
    }

    /**
     * 4. Test alternative template route /manage/record/offline/template also streams valid template.
     */
    public function test_alternative_template_download_route(): void
    {
        $response = $this->actingAs($this->admin)->get('/manage/record/offline/template');
        $response->assertStatus(200);
        $this->assertTrue($response->headers->get('content-type') === 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        $this->assertStringContainsString('Non_KP_OWL_Import_Template.xlsx', $response->headers->get('content-disposition'));
    }

    /**
     * 5. Test generated template structure: exactly 2 worksheets, Sheet 1 active, Sheet 2 instructions.
     */
    public function test_template_structure_sheets_and_freeze_panes(): void
    {
        $spreadsheet = OfflineTrainingExcelSchema::generateTemplateSpreadsheet();

        $this->assertEquals(2, $spreadsheet->getSheetCount());
        $this->assertEquals('Non KP OWL Import', $spreadsheet->getSheet(0)->getTitle());
        $this->assertEquals('Instructions', $spreadsheet->getSheet(1)->getTitle());
        $this->assertEquals(0, $spreadsheet->getActiveSheetIndex());

        // Verify freeze pane at A2 (first header row frozen, data starts on Row 2)
        $sheet = $spreadsheet->getSheet(0);
        $this->assertEquals('A2', $sheet->getFreezePane());
    }

    /**
     * 6. Test template has single header row on Row 1 containing exactly 44 canonical columns.
     */
    public function test_template_seven_parent_groups_and_forty_four_columns(): void
    {
        $groups = OfflineTrainingExcelSchema::groups();
        $this->assertCount(7, $groups);

        $totalCols = OfflineTrainingExcelSchema::totalColumns();
        $this->assertEquals(44, $totalCols);

        $spreadsheet = OfflineTrainingExcelSchema::generateTemplateSpreadsheet();
        $sheet = $spreadsheet->getSheet(0);

        // Row 1 Canonical Headers Check (Single header row on Row 1)
        $this->assertEquals('No', $sheet->getCell('A1')->getValue());
        $this->assertEquals('ID', $sheet->getCell('B1')->getValue());
        $this->assertEquals('Name', $sheet->getCell('C1')->getValue());
        $this->assertEquals('Grade', $sheet->getCell('D1')->getValue());
        $this->assertEquals('Job Position', $sheet->getCell('E1')->getValue());
        $this->assertEquals('Course Name', $sheet->getCell('I1')->getValue());
        $this->assertEquals('Attendance Yes/No', $sheet->getCell('AH1')->getValue());
        $this->assertEquals('Bond Status', $sheet->getCell('AJ1')->getValue());
        $this->assertEquals('Program', $sheet->getCell('AM1')->getValue());
        $this->assertEquals('Numerical', $sheet->getCell('AN1')->getValue()); // L2 Numerical
        $this->assertEquals('Verbal', $sheet->getCell('AO1')->getValue());    // L2 Verbal
        $this->assertEquals('Numerical', $sheet->getCell('AP1')->getValue()); // L3 Numerical
        $this->assertEquals('Verbal', $sheet->getCell('AQ1')->getValue());    // L3 Verbal
        $this->assertEquals('PIC', $sheet->getCell('AR1')->getValue());       // L3 PIC
    }

    /**
     * 7. Test disambiguation of L2 vs L3 Numerical and Verbal headers.
     */
    public function test_disambiguation_of_l2_and_l3_numerical_and_verbal(): void
    {
        $columns = OfflineTrainingExcelSchema::columns();

        // Find L2 and L3 columns by index
        $l2Num = $columns[40];
        $l2Ver = $columns[41];
        $l3Num = $columns[42];
        $l3Ver = $columns[43];

        $this->assertEquals('l2_numerical', $l2Num['key']);
        $this->assertEquals('l2_verbal', $l2Ver['key']);
        $this->assertEquals('l3_numerical', $l3Num['key']);
        $this->assertEquals('l3_verbal', $l3Ver['key']);

        $this->assertEquals('L2 (POST TEST)', $l2Num['group']);
        $this->assertEquals('L2 (POST TEST)', $l2Ver['group']);
        $this->assertEquals('L3 (PROJECT)', $l3Num['group']);
        $this->assertEquals('L3 (PROJECT)', $l3Ver['group']);

        $this->assertEquals('AN', Coordinate::stringFromColumnIndex(40));
        $this->assertEquals('AO', Coordinate::stringFromColumnIndex(41));
        $this->assertEquals('AP', Coordinate::stringFromColumnIndex(42));
        $this->assertEquals('AQ', Coordinate::stringFromColumnIndex(43));
    }

    /**
     * 8. Test system fields are strictly excluded from the template spreadsheet.
     */
    public function test_system_fields_strictly_excluded_from_template(): void
    {
        $headers = OfflineTrainingExcelSchema::headers();
        $systemFields = OfflineTrainingExcelSchema::systemFields();

        foreach ($systemFields as $sysField) {
            $this->assertNotContains($sysField, $headers, "System field {$sysField} must not be present in template headers.");
        }

        $allKeys = array_column(OfflineTrainingExcelSchema::columns(), 'key');
        $this->assertNotContains('user_id', $allKeys);
        $this->assertNotContains('created_by', $allKeys);
        $this->assertNotContains('updated_by', $allKeys);
        $this->assertNotContains('created_at', $allKeys);
        $this->assertNotContains('updated_at', $allKeys);
    }

    /**
     * 9. Test Instructions sheet contains guidelines and documentation for all 7 groups.
     */
    public function test_instructions_sheet_content_and_guidelines(): void
    {
        $spreadsheet = OfflineTrainingExcelSchema::generateTemplateSpreadsheet();
        $sheet = $spreadsheet->getSheet(1);

        $this->assertEquals('Instructions', $sheet->getTitle());

        $content = '';
        for ($row = 1; $row <= 35; $row++) {
            for ($col = 1; $col <= 3; $col++) {
                $cellVal = $sheet->getCellByColumnAndRow($col, $row)->getValue();
                if ($cellVal) {
                    $content .= ' ' . $cellVal;
                }
            }
        }

        $this->assertStringContainsString('KP OWL LMS', $content);
        $this->assertStringContainsString('Non KP OWL Offline Training Import Guidelines', $content);
        $this->assertStringContainsString('Header Integrity', $content);
        $this->assertStringContainsString('Participant Records', $content);
        $this->assertStringContainsString('Event Grouping', $content);
    }

    /**
     * 10. Test canonical schema is single source of truth for both template and import service.
     */
    public function test_canonical_schema_is_single_source_of_truth(): void
    {
        $requiredKeys = OfflineTrainingExcelSchema::requiredDatabaseKeys();
        $this->assertContains('employee_id', $requiredKeys);
        $this->assertContains('name', $requiredKeys);
        $this->assertContains('course_name', $requiredKeys);

        // Assert import service relies on the same canonical keys
        $reflection = new \ReflectionClass(OfflineTrainingImportService::class);
        $method = $reflection->getMethod('validateRequiredHeaders');
        $method->setAccessible(true);

        $importService = app(OfflineTrainingImportService::class);

        // Passing all required keys passes without exception
        $method->invoke($importService, ['employee_id', 'name', 'course_name']);

        // Missing required key throws InvalidArgumentException
        $this->expectException(\InvalidArgumentException::class);
        $method->invoke($importService, ['name', 'course_name']); // missing employee_id
    }

    /**
     * 11. Critical Round-Trip Test:
     *     Generate template -> Fill sample row -> Upload to preview -> Assert valid parsing with 0 unknown headers.
     */
    public function test_critical_round_trip_template_generation_and_preview(): void
    {
        // 1. Generate template spreadsheet
        $spreadsheet = OfflineTrainingExcelSchema::generateTemplateSpreadsheet();
        $sheet = $spreadsheet->getSheet(0);

        // 2. Fill row 3 with canonical schema examples
        foreach (OfflineTrainingExcelSchema::columns() as $colIdx => $colDef) {
            $colLetter = Coordinate::stringFromColumnIndex($colIdx);
            $exampleVal = $colDef['example'] ?? null;
            if ($exampleVal !== null && $exampleVal !== '') {
                $sheet->setCellValue($colLetter . '3', $exampleVal);
            }
        }

        // 3. Save to temp file
        $tempPath = tempnam(sys_get_temp_dir(), 'owl_modal_test_') . '.xlsx';
        $writer = new Xlsx($spreadsheet);
        $writer->save($tempPath);
        $this->createdTempFiles[] = $tempPath;

        $uploadedFile = new UploadedFile(
            $tempPath,
            'Non_KP_OWL_Import_Template.xlsx',
            'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            null,
            true
        );

        // 4. Send to preview endpoint
        $response = $this->actingAs($this->admin)->postJson('/manage/record/offline/import/preview', [
            'file' => $uploadedFile,
        ]);

        $response->assertStatus(200);
        $response->assertJson(['success' => true]);

        $data = $response->json('data');
        $this->assertNotEmpty($data['token']);
        $this->assertEquals(1, $data['stats']['total_rows']);
        $this->assertEquals(1, $data['stats']['valid']);
        $this->assertEquals(0, $data['stats']['invalid']);
        $this->assertEmpty($data['unknown_headers'], 'Template child headers must produce 0 unknown headers.');

        // Verify mapped data row
        $previewRow = $data['preview_rows'][0];
        $this->assertContains($previewRow['status'], ['valid', 'warning']);
        $this->assertEquals('100234', $previewRow['data']['employee_id']);
        $this->assertEquals('Budi Santoso', $previewRow['data']['name']);
        $this->assertEquals('High Voltage Safety & Operation', $previewRow['data']['course_name']);
        $this->assertEquals(88.0, $previewRow['data']['l2_numerical']);
        $this->assertEquals('Passed', $previewRow['data']['l2_verbal']);
        $this->assertEquals(90.0, $previewRow['data']['l3_numerical']);
        $this->assertEquals('Implemented successfully in plant', $previewRow['data']['l3_verbal']);
        $this->assertEquals('HRDC Team', $previewRow['data']['pic']);
    }

    /**
     * 12. Test safe cleanup on import cancel.
     */
    public function test_safe_cleanup_on_cancel(): void
    {
        // Upload a valid test file first
        $spreadsheet = OfflineTrainingExcelSchema::generateTemplateSpreadsheet();
        $sheet = $spreadsheet->getSheet(0);
        $sheet->setCellValue('B3', '100088');
        $sheet->setCellValue('C3', 'Test User Cancel');
        $sheet->setCellValue('I3', 'Test Course Cancel');

        $tempPath = tempnam(sys_get_temp_dir(), 'owl_cancel_') . '.xlsx';
        $writer = new Xlsx($spreadsheet);
        $writer->save($tempPath);
        $this->createdTempFiles[] = $tempPath;

        $uploadedFile = new UploadedFile($tempPath, 'cancel_test.xlsx', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet', null, true);

        $previewResponse = $this->actingAs($this->admin)->postJson('/manage/record/offline/import/preview', [
            'file' => $uploadedFile,
        ]);
        $previewResponse->assertStatus(200);
        $token = $previewResponse->json('data.token');
        $this->assertNotEmpty($token);

        // Cancel the session
        $cancelResponse = $this->actingAs($this->admin)->postJson('/manage/record/offline/import/cancel', [
            'token' => $token,
        ]);
        $cancelResponse->assertStatus(200);
        $cancelResponse->assertJson(['success' => true]);
    }

    /**
     * 13. Test backward compatibility: GET /manage/record/offline/import redirects to /manage/record/offline?open_import=1
     */
    public function test_backward_compatibility_redirect(): void
    {
        $response = $this->actingAs($this->admin)->get('/manage/record/offline/import');
        $response->assertStatus(302);
        $response->assertRedirect(route('manage.record.offline', ['open_import' => 1]));
    }

    /**
     * 14. Test zero schema change verification.
     */
    public function test_zero_schema_change_verification(): void
    {
        // Tables must exist
        $this->assertTrue(Schema::hasTable('offline_trainings'));
        $this->assertTrue(Schema::hasTable('offline_training_users'));

        // Check key columns of offline_trainings
        $trainingColumns = Schema::getColumnListing('offline_trainings');
        $expectedTrainingCols = [
            'id', 'course_name', 'sub_course_name', 'type', 'category', 'level', 'batch',
            'main_classification', 'sub_classification', 'counted_corporate_kpi', 'sub_corporate_kpi',
            'delivery_method', 'sub_delivery_method', 'provider', 'sub_provider', 'provider_name',
            'venue', 'instructor', 'duration_seconds', 'learning_hours', 'start_date', 'finish_date',
            'created_by', 'created_at', 'updated_at', 'deleted_at'
        ];
        foreach ($expectedTrainingCols as $col) {
            $this->assertContains($col, $trainingColumns, "offline_trainings must have column {$col}");
        }

        // Check key columns of offline_training_users
        $userColumns = Schema::getColumnListing('offline_training_users');
        $expectedUserCols = [
            'id', 'offline_training_id', 'user_id', 'employee_id', 'name', 'grade', 'job_position',
            'team', 'department', 'company', 'learning_hours', 'progress', 'assessment_score',
            'certificate', 'attendance', 'attendance_reason', 'bond_status', 'bond_end_date',
            'bond_amount', 'l1_program', 'l2_numerical', 'l2_verbal', 'l3_numerical', 'l3_verbal',
            'pic', 'created_at', 'updated_at'
        ];
        foreach ($expectedUserCols as $col) {
            $this->assertContains($col, $userColumns, "offline_training_users must have column {$col}");
        }
    }
}
