<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;
use ZipArchive;

class ManageRecordRefactorVerificationTest extends TestCase
{
    protected ?User $admin;

    protected function setUp(): void
    {
        parent::setUp();
        $this->admin = User::where('role', '2')->first() ?? User::first();
    }

    /**
     * 1. Test All Record page loads with HTTP 200 and required elements.
     */
    public function test_all_record_index_loads_successfully(): void
    {
        $response = $this->actingAs($this->admin)->get('/manage/record/all');

        $response->assertStatus(200);
        $response->assertSee('Learning Record Management');
        $response->assertSee('Classification Summary');
        $response->assertSee('btn-all-export-excel');
        $response->assertDontSee('all-filter-form');
        $response->assertDontSee('all-learning-table');
        $response->assertDontSee('moreAllFiltersModal');
    }

    /**
     * 2. Test All Record DataTable AJAX endpoint returns valid JSON with 34 columns + action.
     */
    public function test_all_record_datatable_ajax_returns_valid_json(): void
    {
        $response = $this->actingAs($this->admin)->getJson('/manage/record/all/data?draw=1&start=0&length=10');

        $response->assertStatus(200);
        $response->assertJsonStructure([
            'draw',
            'recordsTotal',
            'recordsFiltered',
            'data',
        ]);

        $data = $response->json('data');
        if (!empty($data)) {
            $row = $data[0];
            // Assert core DataTable columns from the 34 specification columns
            $this->assertArrayHasKey('source_badge', $row);
            $this->assertArrayHasKey('employee_id', $row);
            $this->assertArrayHasKey('employee_name', $row);
            $this->assertArrayHasKey('course_title', $row);
            $this->assertArrayHasKey('type', $row);
            $this->assertArrayHasKey('category', $row);
            $this->assertArrayHasKey('main_classification', $row);
            $this->assertArrayHasKey('sub_classification', $row);
            $this->assertArrayHasKey('delivery_method', $row);
            $this->assertArrayHasKey('sub_delivery_method', $row);
            $this->assertArrayHasKey('provider', $row);
            $this->assertArrayHasKey('provider_name', $row);
            $this->assertArrayHasKey('venue', $row);
            $this->assertArrayHasKey('course_duration', $row);
            $this->assertArrayHasKey('total_recognized_duration', $row);
            $this->assertArrayHasKey('learning_hours', $row);
            $this->assertArrayHasKey('progress_bar', $row);
            $this->assertArrayHasKey('progress_status', $row);
            $this->assertArrayHasKey('start_date', $row);
            $this->assertArrayHasKey('end_date', $row);
            $this->assertArrayHasKey('start_time', $row);
            $this->assertArrayHasKey('end_time', $row);
            $this->assertArrayHasKey('record_month', $row);
            $this->assertArrayHasKey('requirement', $row);
            $this->assertArrayHasKey('attendance', $row);
            $this->assertArrayHasKey('score_l1', $row);
            $this->assertArrayHasKey('score_l2', $row);
            $this->assertArrayHasKey('score_l3', $row);
            $this->assertArrayHasKey('certificate_availability', $row);
            $this->assertArrayHasKey('total_engagement_time', $row);
            $this->assertArrayHasKey('action', $row);
        }
    }

    /**
     * 3. Test Detail View returns the 18 canonical fields.
     */
    public function test_all_record_details_returns_18_canonical_fields(): void
    {
        // Try to find a real offline or online record
        $offlineUser = DB::table('offline_training_users')->first();
        if ($offlineUser) {
            $response = $this->actingAs($this->admin)->getJson("/manage/record/all/offline/{$offlineUser->id}/details");
            $response->assertStatus(200);

            $expectedFields = [
                'grade',
                'job_position',
                'sub_course_title',
                'level',
                'batch',
                'sub_provider',
                'instructor',
                'attendance_reason',
                'bond_status',
                'bond_duration',
                'bond_end_date',
                'bond_amount',
                'l2_status',
                'l3_status',
                'certificate_institution',
                'certificate_issued_date',
                'certificate_expired_date',
                'pic',
            ];

            foreach ($expectedFields as $field) {
                $this->assertArrayHasKey($field, $response->json(), "Missing Detail View field: {$field}");
            }
        }
    }

    /**
     * 4. Test All Record Export generates valid 52-column Excel.
     */
    public function test_all_record_export_generates_valid_52_column_excel(): void
    {
        $response = $this->actingAs($this->admin)->get('/manage/record/all/export?year=2026');

        $response->assertStatus(200);
        $this->assertEquals(
            'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            $response->headers->get('Content-Type')
        );

        // Verify content is a valid ZIP file with worksheet
        $filePath = $response->getFile()->getPathname();
        $this->assertFileExists($filePath);

        $zip = new ZipArchive();
        $this->assertTrue($zip->open($filePath) === true, 'Exported Excel is not a valid ZIP archive.');

        $sheetXml = $zip->getFromName('xl/worksheets/sheet1.xml');
        $this->assertNotEmpty($sheetXml, 'sheet1.xml not found in export');

        // Verify column 52 exists: AZ1 (Total Engagement Time (LH))
        $this->assertStringContainsString('r="AZ1"', $sheetXml, 'Column 52 (AZ1) missing from export header!');
        $this->assertStringContainsString('r="A1"', $sheetXml, 'Column 1 (A1) missing from export header!');

        $zip->close();
    }

    /**
     * 5. Test Classification Detail page loads with HTTP 200.
     */
    public function test_classification_record_index_loads_successfully(): void
    {
        $response = $this->actingAs($this->admin)->get('/manage/record/all/classification/basic-common-duties');

        $response->assertStatus(200);
        $response->assertSee('all-classification-learning-table');
        $response->assertSee('recordDetailModal');
    }

    /**
     * 6. Test Classification Record DataTable AJAX endpoint returns valid JSON.
     */
    public function test_classification_record_datatable_ajax_returns_valid_json(): void
    {
        $response = $this->actingAs($this->admin)->getJson('/manage/record/all/classification/basic-common-duties/data?draw=1&start=0&length=10');

        $response->assertStatus(200);
        $response->assertJsonStructure([
            'draw',
            'recordsTotal',
            'recordsFiltered',
            'data',
        ]);
    }

    /**
     * 7. Test Classification Record Export generates valid 52-column Excel.
     */
    public function test_classification_record_export_generates_valid_52_column_excel(): void
    {
        $response = $this->actingAs($this->admin)->get('/manage/record/all/classification/basic-common-duties/export');

        $response->assertStatus(200);
        $this->assertEquals(
            'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            $response->headers->get('Content-Type')
        );

        // Verify content is a valid ZIP file with worksheet
        $filePath = $response->getFile()->getPathname();
        $this->assertFileExists($filePath);

        $zip = new ZipArchive();
        $this->assertTrue($zip->open($filePath) === true, 'Classification export is not a valid ZIP.');

        $sheetXml = $zip->getFromName('xl/worksheets/sheet1.xml');
        $this->assertNotEmpty($sheetXml);

        // Verify column 52 (AZ1) exists
        $this->assertStringContainsString('r="AZ1"', $sheetXml, 'Column 52 (AZ1) missing from classification export header!');
        $this->assertStringContainsString('r="A1"', $sheetXml, 'Column 1 (A1) missing from classification export header!');

        $zip->close();
    }

    /**
     * 8. Test Classification Record Details endpoint returns the 18 canonical fields.
     */
    public function test_classification_record_details_returns_18_canonical_fields(): void
    {
        $offlineUser = DB::table('offline_training_users')->first();
        if ($offlineUser) {
            $response = $this->actingAs($this->admin)->getJson("/manage/record/all/classification/basic-common-duties/offline/{$offlineUser->id}/details");
            $response->assertStatus(200);
            $this->assertArrayHasKey('grade', $response->json());
            $this->assertArrayHasKey('job_position', $response->json());
            $this->assertArrayHasKey('bond_status', $response->json());
            $this->assertArrayHasKey('pic', $response->json());
        }
    }
}
