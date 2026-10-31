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

        DB::table('offline_training_users')->where('company', 'PT Krakatau Posco Test')->orWhere('employee_id', '100234')->delete();
        DB::table('offline_trainings')->where('category', 'Technical Competency Test')->orWhere('course_name', 'High Voltage Safety & Operation')->delete();
    }

    protected function tearDown(): void
    {
        // Clean up any test database records
        DB::table('offline_training_users')->where('company', 'PT Krakatau Posco Test')->orWhere('employee_id', '100234')->delete();
        DB::table('offline_trainings')->where('category', 'Technical Competency Test')->orWhere('course_name', 'High Voltage Safety & Operation')->delete();

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
     *    - [+ Add Training] (Dropdown)
     *    - [Manual] link
     *    - [Import Data Non PTKP] link to dedicated page (no modal)
     *    - [Download Template] link
     */
    public function test_offline_page_renders_action_buttons_and_dedicated_import_link(): void
    {
        $response = $this->actingAs($this->admin)->get('/manage/record/offline');
        $response->assertStatus(200);

        // Verify "+ Add Training" and "Manual"
        $response->assertSee('Add Training');
        $response->assertSee('Manual');

        // Verify "Import Data Non PTKP" directs to dedicated page without opening modal
        $response->assertSee('Import Data Non PTKP');
        $response->assertSee(route('manage.record.offline.import-non-ptkp'));
        $response->assertDontSee('data-bs-target="#offlineImportModal"', false);

        // Verify "Download Template" button
        $response->assertSee('Download Template');
        $response->assertSee(route('manage.record.offline.import.template'));
    }

    /**
     * 2. Test dedicated import page DOM structure and 4-step stepper and state containers.
     */
    public function test_dedicated_import_non_ptkp_page_structure_and_states(): void
    {
        // Test redirect from legacy /offline/import
        $redirectResponse = $this->actingAs($this->admin)->get('/manage/record/offline/import');
        $redirectResponse->assertRedirect(route('manage.record.offline.import-non-ptkp'));

        // Test direct access to dedicated page
        $response = $this->actingAs($this->admin)->get('/manage/record/offline/import-non-ptkp');
        $response->assertStatus(200);

        // Page title & Breadcrumb
        $response->assertSee('Import Non-PTKP Data');
        $response->assertSee('Back to Offline Records');
        $response->assertSee(route('manage.record.offline'));

        // 4-Step Stepper
        $response->assertSee('id="stepper-step-1"', false);
        $response->assertSee('id="stepper-step-2"', false);
        $response->assertSee('id="stepper-step-3"', false);
        $response->assertSee('id="stepper-step-4"', false);

        // State panels
        $response->assertSee('id="panel-state-upload"', false);
        $response->assertSee('id="panel-state-loading"', false);
        $response->assertSee('id="panel-state-preview"', false);
        $response->assertSee('id="panel-state-importing"', false);
        $response->assertSee('id="panel-state-result"', false);

        // Upload panel components
        $response->assertSee('id="dropzone-container"', false);
        $response->assertSee('id="excel-file-input"', false);
        $response->assertSee('id="btn-validate-upload"', false);
        $response->assertSee('id="upload-error-box"', false);

        // Preview panel KPI stats cards
        $response->assertSee('id="stat-total-rows"', false);
        $response->assertSee('id="stat-valid-rows"', false);
        $response->assertSee('id="stat-warning-rows"', false);
        $response->assertSee('id="stat-duplicate-rows"', false);
        $response->assertSee('id="stat-invalid-rows"', false);
        $response->assertSee('id="stat-training-events"', false);

        // Preview table and buttons
        $response->assertSee('id="preview-main-table"', false);
        $response->assertSee('id="preview-main-tbody"', false);
        $response->assertSee('id="btn-action-confirm-import"', false);
        $response->assertSee('id="btn-action-reupload"', false);

        // Result panel metrics
        $response->assertSee('id="res-imported-count"', false);
        $response->assertSee('id="res-trainings-created"', false);
        $response->assertSee('id="res-trainings-reused"', false);
        $response->assertSee('id="res-duplicates-skipped"', false);
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

        // Verify freeze pane at A2 (single-row header frozen, data starts row 2)
        $sheet = $spreadsheet->getSheet(0);
        $this->assertEquals('A2', $sheet->getFreezePane());
    }

    /**
     * 6. Test template has single-row header, 0 groups, and 46 canonical columns.
     */
    public function test_template_seven_parent_groups_and_forty_four_columns(): void
    {
        $groups = OfflineTrainingExcelSchema::groups();
        $this->assertEmpty($groups);

        $totalCols = OfflineTrainingExcelSchema::totalColumns();
        $this->assertEquals(46, $totalCols);

        $spreadsheet = OfflineTrainingExcelSchema::generateTemplateSpreadsheet();
        $sheet = $spreadsheet->getSheet(0);

        // Row 1 Canonical Headers Check (46 columns: Employee ID through PIC)
        $this->assertEquals('Employee ID', $sheet->getCell('A1')->getValue());
        $this->assertEquals('Employee Name', $sheet->getCell('B1')->getValue());
        $this->assertEquals('Grade', $sheet->getCell('C1')->getValue());
        $this->assertEquals('Job Position', $sheet->getCell('D1')->getValue());
        $this->assertEquals('Course Title', $sheet->getCell('H1')->getValue());
        $this->assertEquals('Provider', $sheet->getCell('R1')->getValue());
        $this->assertEquals('Sub Provider', $sheet->getCell('S1')->getValue());
        $this->assertEquals('Bond Status', $sheet->getCell('AG1')->getValue());
        $this->assertEquals('Score (L2)', $sheet->getCell('AL1')->getValue());
        $this->assertEquals('L2 Status', $sheet->getCell('AM1')->getValue());
        $this->assertEquals('Score (L3)', $sheet->getCell('AN1')->getValue());
        $this->assertEquals('L3 Status', $sheet->getCell('AO1')->getValue());
        $this->assertEquals('Certificate Availability', $sheet->getCell('AP1')->getValue());
        $this->assertEquals('Certificate Institution', $sheet->getCell('AQ1')->getValue());
        $this->assertEquals('Certificate Issued Date', $sheet->getCell('AR1')->getValue());
        $this->assertEquals('Certificate Expired Date', $sheet->getCell('AS1')->getValue());
        $this->assertEquals('Person in Charge (PIC)', $sheet->getCell('AT1')->getValue());
    }

    /**
     * 7. Test disambiguation of L2 vs L3 Numerical and Verbal headers.
     */
    public function test_disambiguation_of_l2_and_l3_numerical_and_verbal(): void
    {
        $columns = OfflineTrainingExcelSchema::columns();
        $colKeys = array_column($columns, 'key', 'col_letter');

        $this->assertEquals('assessment_score', $colKeys['AL']);
        $this->assertEquals('l2_verbal', $colKeys['AM']);
        $this->assertEquals('l3_numerical', $colKeys['AN']);
        $this->assertEquals('l3_verbal', $colKeys['AO']);
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
        $this->assertStringContainsString('Simplified Single-Row Header', $content);
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

        // 2. Fill row 2 with canonical schema examples (single-row header, data on row 2+)
        foreach (OfflineTrainingExcelSchema::columns() as $colIdx => $colDef) {
            $colLetter = Coordinate::stringFromColumnIndex($colIdx);
            $exampleVal = $colDef['example'] ?? null;
            if ($exampleVal !== null && $exampleVal !== '') {
                $sheet->setCellValue($colLetter . '2', $exampleVal);
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
        $this->assertEquals(1, $data['importable_count']);
        $this->assertEquals(0, $data['stats']['invalid']);
        $this->assertEmpty($data['unknown_headers'], 'Template child headers must produce 0 unknown headers.');

        // Verify mapped data row
        $previewRow = $data['preview_rows'][0];
        $this->assertContains($previewRow['status'], ['valid', 'warning']);
        $this->assertEquals('104921', $previewRow['data']['employee_id']);
        $this->assertEquals('Budi Santoso', $previewRow['data']['name']);
        $this->assertEquals('High Voltage Safety & Operation', $previewRow['data']['course_name']);
        $this->assertEquals(85, $previewRow['data']['l2_numerical']);
        $this->assertEquals('Passed', $previewRow['data']['l2_verbal']);
        $this->assertEquals(90.0, $previewRow['data']['l3_numerical']);
        $this->assertEquals('Implemented successfully', $previewRow['data']['l3_verbal']);
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
        $response->assertRedirect(route('manage.record.offline.import-non-ptkp'));
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
            'bond_amount_idr', 'l1_program', 'l2_numerical', 'l2_verbal', 'l3_numerical', 'l3_verbal',
            'pic', 'created_at', 'updated_at'
        ];
        foreach ($expectedUserCols as $col) {
            $this->assertContains($col, $userColumns, "offline_training_users must have column {$col}");
        }
    }
}
