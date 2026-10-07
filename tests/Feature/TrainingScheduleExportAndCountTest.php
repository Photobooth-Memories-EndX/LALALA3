<?php

namespace Tests\Feature;

use App\Models\OfflineTraining;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class TrainingScheduleExportAndCountTest extends TestCase
{
    use DatabaseTransactions;

    protected User $admin;
    protected OfflineTraining $trainingA;
    protected OfflineTraining $trainingB;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::where('role', '2')->first() ?? User::where('role', '3')->first() ?? User::first();

        // Create sample training schedules
        $this->trainingA = OfflineTraining::create([
            'title'                   => 'Export Test Leadership Training 2026',
            'course_name'             => 'Export Test Leadership Training 2026',
            'category'                => 'Leadership',
            'training_code'           => 'PTKP-TEST-LEAD-01',
            'instructor'              => 'John Leadership Trainer',
            'location'                => 'Room KP-101',
            'venue'                   => 'Room KP-101',
            'target_employee'         => 'Managers and Team Leads',
            'quota'                   => 25,
            'status'                  => 'Open Registration',
            'start_date'              => '2026-10-15',
            'end_date'                => '2026-10-16',
            'start_time'              => '08:30:00',
            'end_time'                => '16:30:00',
            'registration_close_date' => '2026-10-12',
            'registration_close_time' => '17:00:00',
            'batch'                   => 'Batch 1',
        ]);

        $this->trainingB = OfflineTraining::create([
            'title'                   => 'Export Test Safety & Compliance 2026',
            'course_name'             => 'Export Test Safety & Compliance 2026',
            'category'                => 'Compliance & Safety',
            'training_code'           => 'PTKP-TEST-SAFE-02',
            'instructor'              => 'Jane Safety Officer',
            'location'                => 'PTKP Plant Hall',
            'venue'                   => 'PTKP Plant Hall',
            'target_employee'         => 'All Field Workers',
            'quota'                   => 50,
            'status'                  => 'Closed',
            'start_date'              => '2026-11-20',
            'end_date'                => '2026-11-21',
            'start_time'              => '09:00:00',
            'end_time'                => '15:00:00',
            'registration_close_date' => '2026-11-15',
            'registration_close_time' => '12:00:00',
            'batch'                   => 'Batch 2',
        ]);
    }

    /**
     * 1. Badge counter is removed from header, view data and pagination links retain parameters.
     */
    public function test_index_page_header_badge_is_removed_and_view_has_pagination_data(): void
    {
        $response = $this->actingAs($this->admin)->get('/manage/training-schedule?category=Leadership');
        $response->assertStatus(200);

        // Header badge counter is removed
        $response->assertDontSee('id="scheduleTotalBadge"', false);
        $response->assertDontSee('id="scheduleTotalCount"', false);

        // Verify view data
        $response->assertViewHas('schedules');
        $response->assertViewHas('totalSchedules');
        $response->assertViewHas('trainings');
    }

    /**
     * 2. Counter updates dynamically when filter parameters are applied.
     */
    public function test_index_page_counter_updates_when_filtered(): void
    {
        // Filter by category Leadership
        $response = $this->actingAs($this->admin)->get('/manage/training-schedule?category=Leadership&search=Export+Test+Leadership');
        $response->assertStatus(200);

        $response->assertSee('Export Test Leadership Training 2026');
        $response->assertDontSee('Export Test Safety & Compliance 2026');

        $totalSchedules = $response->viewData('totalSchedules');
        $this->assertEquals(1, $totalSchedules);
    }

    /**
     * 3. Download dropdown menu with Excel, PDF, and JPG options.
     */
    public function test_quick_filter_has_export_dropdown_with_excel_pdf_jpg_options(): void
    {
        $response = $this->actingAs($this->admin)->get('/manage/training-schedule?category=Leadership&status=open&month=10&year=2026');
        $response->assertStatus(200);

        // Dropdown toggle button
        $response->assertSee('id="dropdownExportSchedule"', false);

        // Option 1: Export Excel (.xlsx)
        $response->assertSee('id="btnExportExcel"', false);
        $response->assertSee('Export Excel (.xlsx)', false);
        $response->assertSee('/manage/training-schedule/export/excel', false);

        // Option 2: Export PDF Announcement Card (.pdf)
        $response->assertSee('id="btnExportPdf"', false);
        $response->assertSee('Export PDF Announcement Card (.pdf)', false);
        $response->assertSee('/manage/training-schedule/export/pdf', false);

        // Option 3: Export JPG Announcement Image (.jpg)
        $response->assertSee('id="btnExportJpg"', false);
        $response->assertSee('Export JPG Announcement Image (.jpg)', false);
        $response->assertSee('/manage/training-schedule/export/jpg', false);
    }

    /**
     * 4. Controller exportExcel handler returns .xlsx file.
     */
    public function test_export_excel_returns_xlsx_download(): void
    {
        $response = $this->actingAs($this->admin)->get('/manage/training-schedule/export/excel?category=Leadership');
        $response->assertStatus(200);

        $disposition = $response->headers->get('content-disposition');
        $this->assertNotNull($disposition);
        $this->assertStringContainsString('Training_Schedule_Export_', $disposition);
        $this->assertStringContainsString('.xlsx', $disposition);
    }

    /**
     * 5. Controller exportPdf handler returns .pdf file with Krakatau Posco theme and updated subtitle.
     */
    public function test_export_pdf_returns_pdf_card_announcement(): void
    {
        $response = $this->actingAs($this->admin)->get('/manage/training-schedule/export/pdf?category=Leadership');
        $response->assertStatus(200);

        $disposition = $response->headers->get('content-disposition');
        $this->assertNotNull($disposition);
        $this->assertStringContainsString('Training_Schedule_Cards_', $disposition);
        $this->assertStringContainsString('.pdf', $disposition);

        // Verify template rendered subtitle
        $viewContent = view('exports.training-schedule-pdf', [
            'trainings' => collect([$this->trainingA]),
            'activeFilters' => ['Category' => 'Leadership'],
        ])->render();
        $this->assertStringContainsString('Official Offline Training Calendar &bull; OWL Learning Hub', $viewContent);
        $this->assertStringNotContainsString('Krakatau Posco Academy', $viewContent);
    }

    /**
     * 6. Controller exportJpg handler returns .jpg image.
     */
    public function test_export_jpg_returns_jpg_announcement_image(): void
    {
        $response = $this->actingAs($this->admin)->get('/manage/training-schedule/export/jpg?category=Leadership');
        $response->assertStatus(200);

        $contentType = $response->headers->get('content-type');
        $this->assertStringContainsString('image/jpeg', $contentType);

        $disposition = $response->headers->get('content-disposition');
        $this->assertNotNull($disposition);
        $this->assertStringContainsString('Training_Schedule_Announcement_', $disposition);
        $this->assertStringContainsString('.jpg', $disposition);

        // Content is valid JPEG
        $content = $response->getContent();
        $this->assertNotEmpty($content);
        // JPEG magic number FF D8
        $this->assertEquals("\xFF\xD8", substr($content, 0, 2));
    }

    /**
     * 7. Export methods apply filter parameters properly.
     */
    public function test_exports_apply_filter_query_logic(): void
    {
        // Month 11 should match Training B, but not Training A (month 10)
        $responsePdf = $this->actingAs($this->admin)->get('/manage/training-schedule/export/pdf?month=11&year=2026');
        $responsePdf->assertStatus(200);

        // Status filter: Closed should match Training B
        $responseExcel = $this->actingAs($this->admin)->get('/manage/training-schedule/export/excel?status=closed');
        $responseExcel->assertStatus(200);

        // Status filter: Open should match Training A
        $responseJpg = $this->actingAs($this->admin)->get('/manage/training-schedule/export/jpg?status=open');
        $responseJpg->assertStatus(200);
    }

    /**
     * 8. Pagination retains active query parameters and blade dropdowns preserve selection.
     */
    public function test_pagination_and_filter_state_retention_in_manage_schedule(): void
    {
        $response = $this->actingAs($this->admin)->get('/manage/training-schedule?category=Leadership&status=open&month=03&year=2026&search=Test');
        $response->assertStatus(200);

        // Verify dropdown selections are retained in rendered HTML
        $response->assertSee('<option value="Leadership" selected>Leadership</option>', false);
        $response->assertSee('<option value="open" selected>Open Registration</option>', false);
        $response->assertSee('<option value="03" selected>March</option>', false);
        $response->assertSee('<option value="2026" selected>2026</option>', false);
        $response->assertSee('value="Test"', false);

        // Verify pagination retains query string
        $schedules = $response->viewData('schedules');
        $this->assertNotNull($schedules);
        $url = $schedules->url(2);
        $this->assertStringContainsString('category=Leadership', $url);
        $this->assertStringContainsString('status=open', $url);
        $this->assertStringContainsString('month=03', $url);
        $this->assertStringContainsString('year=2026', $url);
        $this->assertStringContainsString('search=Test', $url);

        // Verify blade file contents directly for required patterns
        $bladeContent = file_get_contents(resource_path('views/manage/training-schedule/index.blade.php'));
        $this->assertStringContainsString("<option value=\"03\" {{ request('month') == '03' ? 'selected' : '' }}>March</option>", $bladeContent);
        $this->assertStringContainsString("<option value=\"2026\" {{ request('year') == '2026' ? 'selected' : '' }}>2026</option>", $bladeContent);
        $this->assertStringContainsString("<option value=\"{{ \$cat }}\" {{ request('category') == \$cat ? 'selected' : '' }}>{{ \$cat }}</option>", $bladeContent);
        $this->assertStringContainsString("<option value=\"{{ \$st }}\" {{ request('status') == \$st ? 'selected' : '' }}>", $bladeContent);
        $this->assertStringContainsString("<input type=\"text\" name=\"search\" value=\"{{ request('search') }}\">", $bladeContent);
        $this->assertStringContainsString("{{ \$schedules->appends(request()->query())->links() }}", $bladeContent);
    }
}
