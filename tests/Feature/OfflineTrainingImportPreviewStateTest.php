<?php

namespace Tests\Feature;

use App\Models\OfflineTraining;
use App\Models\OfflineTrainingUser;
use App\Models\User;
use App\Services\Record\OfflineTrainingExcelSchema;
use App\Services\Record\OfflineTrainingImportService;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Tests\TestCase;

class OfflineTrainingImportPreviewStateTest extends TestCase
{
    protected User $admin;
    protected array $createdTempFiles = [];

    protected function setUp(): void
    {
        parent::setUp();
        $this->admin = User::where('role', '2')->first() ?? User::first();
        $this->createTestUser(['id_emp' => '100001', 'name' => 'User One']);
        $this->createTestUser(['id_emp' => '100002', 'name' => 'User Two']);
    }

    protected function createTestUser(array $attributes = []): User
    {
        $existing = User::where('id_emp', $attributes['id_emp'] ?? '')->first();
        if ($existing) {
            return $existing;
        }

        $unique = uniqid();
        $defaults = [
            'name'         => 'Test Participant ' . $unique,
            'email'        => 'test_' . $unique . '@example.com',
            'id_user'      => (string) rand(10000, 99999),
            'id_emp'       => 'EMP_' . $unique,
            'id_org_unit'  => 'OU01',
            'company_name' => 'PT Krakatau Posco',
            'team'         => 'Test Team',
            'department'   => 'Test Dept',
            'division'     => 'Test Division',
            'ssohash'      => md5($unique),
            'sap_code'     => (string) rand(10000000, 99999999),
            'role'         => '1',
            'password'     => bcrypt('secret'),
        ];

        return User::create(array_merge($defaults, $attributes));
    }

    protected function tearDown(): void
    {
        // Clean up test DB records
        DB::table('offline_training_users')->where('company', 'TEST_PREVIEW_CORP')->delete();
        DB::table('offline_trainings')->where('category', 'TEST_PREVIEW_CAT')->delete();
        User::whereIn('id_emp', ['100001', '100002'])->delete();

        // Clean up temporary files
        foreach ($this->createdTempFiles as $path) {
            if (File::exists($path)) {
                File::delete($path);
            }
        }

        $tempDir = storage_path('app/temp');
        if (File::isDirectory($tempDir)) {
            foreach (File::files($tempDir) as $file) {
                if (str_starts_with($file->getFilename(), 'preview_state_test_')) {
                    File::delete($file->getPathname());
                }
            }
        }

        parent::tearDown();
    }

    protected function createSpreadsheetFile(array $dataRows, array $customHeaders = null): UploadedFile
    {
        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle(OfflineTrainingExcelSchema::DATA_SHEET_TITLE);

        $headers = $customHeaders ?? OfflineTrainingExcelSchema::headers();
        foreach ($headers as $idx => $headerText) {
            $colLetter = Coordinate::stringFromColumnIndex($idx + 1);
            $sheet->setCellValue($colLetter . '1', $headerText);
        }

        $rowNum = 2;
        foreach ($dataRows as $row) {
            foreach (OfflineTrainingExcelSchema::columns() as $colIdx => $colDef) {
                $colLetter = Coordinate::stringFromColumnIndex($colIdx);
                $key = $colDef['key'];
                if ($key && isset($row[$key])) {
                    $sheet->setCellValue($colLetter . $rowNum, $row[$key]);
                }
            }
            $rowNum++;
        }

        $tempPath = tempnam(sys_get_temp_dir(), 'preview_state_test_') . '.xlsx';
        $writer = new Xlsx($spreadsheet);
        $writer->save($tempPath);
        $this->createdTempFiles[] = $tempPath;

        return new UploadedFile(
            $tempPath,
            'preview_test.xlsx',
            'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            null,
            true
        );
    }

    protected function buildSampleRow(array $overrides = []): array
    {
        return array_merge([
            'employee_id'           => '100234',
            'name'                  => 'Budi Santoso',
            'grade'                 => '4A',
            'job_position'          => 'Staff',
            'team'                  => 'Operations',
            'department'            => 'Plant 1',
            'company'               => 'TEST_PREVIEW_CORP',
            'course_name'           => 'Preview Safety Course 2026',
            'sub_course_name'       => 'Module 1',
            'type'                  => 'In-House',
            'category'              => 'TEST_PREVIEW_CAT',
            'batch'                 => 'Batch A',
            'main_classification'   => 'Quality & Safety',
            'learning_hours'        => 8.0,
            'progress'              => 100,
            'start_date'            => '2026-09-01',
            'end_date'              => '2026-09-02',
            'finish_date'           => '2026-09-02',
            'venue'                 => 'Room 101',
            'attendance'            => 'Yes',
        ], $overrides);
    }

    /**
     * CASE 1: 44 valid headers, all rows valid, 0 duplicate, 0 invalid.
     * Expect: preview_state = READY, can_confirm = true, importable_count = count.
     */
    public function test_case_1_ready_state_all_valid(): void
    {
        $rows = [
            $this->buildSampleRow(['employee_id' => '100001', 'name' => 'User One']),
            $this->buildSampleRow(['employee_id' => '100002', 'name' => 'User Two']),
        ];

        $file = $this->createSpreadsheetFile($rows);

        $response = $this->actingAs($this->admin)->postJson('/manage/record/offline/import/preview', [
            'file' => $file,
        ]);

        $response->assertStatus(200);
        $this->assertEquals('READY', $response->json('preview_state'));
        $this->assertEquals('READY', $response->json('data.preview_state'));
        $this->assertTrue($response->json('can_confirm'));
        $this->assertEquals(2, $response->json('importable_count'));
        $this->assertEquals(2, $response->json('stats.valid'));
        $this->assertEquals(0, $response->json('stats.duplicate'));
        $this->assertEquals(0, $response->json('stats.invalid'));
    }

    /**
     * CASE 2: 44 valid headers, valid rows + duplicate rows, 0 invalid.
     * Expect: preview_state = READY_WITH_DUPLICATES, can_confirm = true, duplicates skipped.
     */
    public function test_case_2_ready_with_duplicates_state(): void
    {
        $rows = [
            $this->buildSampleRow(['employee_id' => '100001', 'name' => 'User One']),
            $this->buildSampleRow(['employee_id' => '100001', 'name' => 'User One']), // Duplicate in same file!
            $this->buildSampleRow(['employee_id' => '100002', 'name' => 'User Two']),
        ];

        $file = $this->createSpreadsheetFile($rows);

        $response = $this->actingAs($this->admin)->postJson('/manage/record/offline/import/preview', [
            'file' => $file,
        ]);

        $response->assertStatus(200);
        $this->assertEquals('READY_WITH_DUPLICATES', $response->json('preview_state'));
        $this->assertTrue($response->json('can_confirm'));
        $this->assertEquals(2, $response->json('importable_count'));
        $this->assertEquals(2, $response->json('stats.valid'));
        $this->assertEquals(1, $response->json('stats.duplicate'));
        $this->assertEquals(0, $response->json('stats.invalid'));
    }

    /**
     * CASE 3 (SCREENSHOT CASE):
     * 44 valid headers, 0 valid, all rows are duplicates against database, 0 invalid.
     * Expect: preview_state = NO_NEW_RECORDS, can_confirm = false, stats.duplicate > 0, stats.invalid = 0.
     * MUST NOT be HEADER_ERROR.
     */
    public function test_case_3_screenshot_case_no_new_records_state(): void
    {
        // 1. Create existing training and participant in DB
        $training = OfflineTraining::create([
            'course_name'         => 'Preview Safety Course 2026',
            'batch'               => 'Batch A',
            'start_date'          => '2026-09-01',
            'finish_date'         => '2026-09-02',
            'type'                => 'In-House',
            'venue'               => 'Room 101',
            'category'            => 'TEST_PREVIEW_CAT',
            'main_classification' => 'Quality & Safety',
            'learning_hours'      => 8.0,
        ]);

        OfflineTrainingUser::create([
            'offline_training_id' => $training->id,
            'employee_id'         => '100001',
            'name'                => 'User One',
            'company'             => 'TEST_PREVIEW_CORP',
            'learning_hours'      => 8.0,
        ]);

        OfflineTrainingUser::create([
            'offline_training_id' => $training->id,
            'employee_id'         => '100002',
            'name'                => 'User Two',
            'company'             => 'TEST_PREVIEW_CORP',
            'learning_hours'      => 8.0,
        ]);

        // 2. Upload file containing exactly the same existing participants
        $rows = [
            $this->buildSampleRow(['employee_id' => '100001', 'name' => 'User One']),
            $this->buildSampleRow(['employee_id' => '100002', 'name' => 'User Two']),
        ];

        $file = $this->createSpreadsheetFile($rows);

        $response = $this->actingAs($this->admin)->postJson('/manage/record/offline/import/preview', [
            'file' => $file,
        ]);

        $response->assertStatus(200);
        $this->assertEquals('NO_NEW_RECORDS', $response->json('preview_state'));
        $this->assertFalse($response->json('can_confirm'));
        $this->assertEquals(0, $response->json('importable_count'));
        $this->assertEquals(0, $response->json('stats.valid'));
        $this->assertEquals(2, $response->json('stats.duplicate'));
        $this->assertEquals(0, $response->json('stats.invalid'));
        $this->assertTrue($response->json('header_check.can_proceed'), 'Header must be 100% valid!');
        $this->assertEquals(46, $response->json('header_check.detected_columns'));
    }

    /**
     * CASE 4: 44 valid headers, valid rows + invalid rows.
     * Expect: preview_state = DATA_ERROR, can_confirm = false, invalid > 0.
     */
    public function test_case_4_data_error_state_invalid_rows(): void
    {
        $rows = [
            $this->buildSampleRow(['employee_id' => '100001', 'name' => 'User One']),
            $this->buildSampleRow(['employee_id' => '100002', 'assessment_score' => 'INVALID_NUMERIC']), // invalid score!
        ];

        $file = $this->createSpreadsheetFile($rows);

        $response = $this->actingAs($this->admin)->postJson('/manage/record/offline/import/preview', [
            'file' => $file,
        ]);

        $response->assertStatus(200);
        $this->assertEquals('DATA_ERROR', $response->json('preview_state'));
        $this->assertFalse($response->json('can_confirm'));
        $this->assertEquals(1, $response->json('stats.invalid'));
        $this->assertTrue($response->json('header_check.can_proceed'), 'Header is valid, data is invalid.');
    }

    /**
     * CASE 5: Header error (e.g. duplicate canonical header mapping).
     * Expect: preview_state = HEADER_ERROR, can_confirm = false, header_check.can_proceed = false.
     */
    public function test_case_5_header_error_state(): void
    {
        $headers = OfflineTrainingExcelSchema::headers();
        $headers[7] = 'Department'; // Duplicate canonical column!

        $rows = [
            $this->buildSampleRow(),
        ];

        $file = $this->createSpreadsheetFile($rows, $headers);

        $response = $this->actingAs($this->admin)->postJson('/manage/record/offline/import/preview', [
            'file' => $file,
        ]);

        $response->assertStatus(200);
        $this->assertEquals('HEADER_ERROR', $response->json('preview_state'));
        $this->assertFalse($response->json('can_confirm'));
        $this->assertFalse($response->json('header_check.can_proceed'));
    }

    /**
     * CASE 6: Missing required header (e.g. Course Name column deleted).
     * Expect: preview_state = HEADER_ERROR, can_confirm = false.
     */
    public function test_case_6_missing_required_header(): void
    {
        $headers = OfflineTrainingExcelSchema::headers();
        $headers[8] = 'Some Random Column'; // Replaced Course Name!

        $rows = [
            $this->buildSampleRow(),
        ];

        $file = $this->createSpreadsheetFile($rows, $headers);

        $response = $this->actingAs($this->admin)->postJson('/manage/record/offline/import/preview', [
            'file' => $file,
        ]);

        $response->assertStatus(200);
        $this->assertEquals('HEADER_ERROR', $response->json('preview_state'));
        $this->assertFalse($response->json('can_confirm'));
        $this->assertFalse($response->json('header_check.can_proceed'));
    }

    /**
     * CASE 7: Header valid, rows with warnings (e.g. blank dates), 0 invalid.
     * Expect: preview_state = READY, can_confirm = true, warnings are counted as importable.
     */
    public function test_case_7_warnings_state_is_importable(): void
    {
        $rows = [
            $this->buildSampleRow([
                'employee_id' => '100001',
                'name'        => 'User One',
                'start_date'  => '', // Non-fatal warning: blank dates
                'end_date'    => '',
                'finish_date' => '',
            ]),
        ];

        $file = $this->createSpreadsheetFile($rows);

        $response = $this->actingAs($this->admin)->postJson('/manage/record/offline/import/preview', [
            'file' => $file,
        ]);

        $response->assertStatus(200);
        $this->assertEquals('READY', $response->json('preview_state'));
        $this->assertTrue($response->json('can_confirm'));
        $this->assertEquals(1, $response->json('stats.warning'));
        $this->assertEquals(1, $response->json('importable_count'));
    }

    /**
     * Test modal blade template renders new stat card tooltips and preview alert container.
     */
    public function test_modal_blade_renders_new_ux_elements(): void
    {
        $response = $this->actingAs($this->admin)->get(route('manage.record.offline.import-non-ptkp'));
        $response->assertStatus(200);

        // Check stat card tooltips
        $response->assertSee('title="Total spreadsheet rows read"', false);
        $response->assertSee('title="Valid records ready to import"', false);
        $response->assertSee('title="Existing records or duplicates that will be skipped"', false);
        $response->assertSee('title="Total unique training sessions identified"', false);

        // Check readiness banner element
        $response->assertSee('id="preview-state-banner"', false);

        // Check confirm button
        $response->assertSee('id="btn-action-confirm-import"', false);

        // Check inline JavaScript state machine exists in rendered HTML
        $response->assertSee("previewState === 'NO_NEW_RECORDS'", false);
        $response->assertSee('No New Records to Import', false);

        // Check explicit count and duplicate breakdown messaging in JavaScript
        $response->assertSee('records were read successfully', false);
        $response->assertSee('0 records will be imported and', false);
        $response->assertSee('duplicate records will be skipped', false);
        $response->assertSee('Existing database records will not be modified or overwritten.', false);
        $response->assertSee('records to import', false);
    }
}
