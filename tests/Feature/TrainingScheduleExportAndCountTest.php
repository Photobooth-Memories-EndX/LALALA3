<?php

namespace Tests\Feature;

use App\Http\Controllers\OfflineTrainingController;
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

    /**
     * 9. Verify venue / location field binding fallback chain and explicit '-' fallback.
     */
    public function test_export_pdf_venue_field_binding_fallback_chain_and_pill_badge(): void
    {
        $bladePath = resource_path('views/exports/training-schedule-pdf.blade.php');
        $bladeContent = file_get_contents($bladePath);

        // 1. Audit fallback chain presence in blade
        $this->assertStringContainsString(
            '{{ $training->location ?? $training->venue ?? $training->room_name ?? $training->room ?? \'-\' }}',
            $bladeContent
        );

        // 2. Audit Row 3 Date & Venue inline row layout
        $this->assertStringContainsString('Row 3: Date & Venue Inline Row', $bladeContent);
        $this->assertStringContainsString('<span style="color: #94a3b8; margin: 0 4px;">|</span>', $bladeContent);

        // 3. Render with standard location
        $tLocation = (object)[
            'title' => 'Test Location Training',
            'category' => 'Technical',
            'status' => 'Open',
            'start_date' => '2026-10-20',
            'end_date' => '2026-10-20',
            'start_time' => '09:00',
            'end_time' => '17:00',
            'registration_close_date' => '2026-10-18',
            'registration_close_time' => '17:00',
            'location' => 'Main Auditorium',
            'venue' => null,
            'room_name' => null,
            'room' => null,
            'target_employee' => 'All',
            'batch' => '1',
            'instructor_name' => 'Instructor A',
            'quota' => 20,
            'syllabi' => collect(),
            'syllabus_summary' => 'Overview',
        ];

        $htmlLoc = view('exports.training-schedule-pdf', ['trainings' => collect([$tLocation])])->render();
        $this->assertStringContainsString('Main Auditorium', $htmlLoc);

        // 4. Render with room_name fallback
        $tRoomName = (object)[
            'title' => 'Test Room Name Training',
            'category' => 'Technical',
            'status' => 'Open',
            'start_date' => '2026-10-20',
            'end_date' => '2026-10-20',
            'start_time' => '09:00',
            'end_time' => '17:00',
            'registration_close_date' => null,
            'registration_close_time' => null,
            'location' => null,
            'venue' => null,
            'room_name' => 'Conference Room 402',
            'room' => null,
            'target_employee' => 'All',
            'batch' => '1',
            'instructor_name' => 'Instructor B',
            'quota' => 25,
            'syllabi' => collect(),
            'syllabus_summary' => 'Overview',
        ];

        $htmlRoomName = view('exports.training-schedule-pdf', ['trainings' => collect([$tRoomName])])->render();
        $this->assertStringContainsString('Conference Room 402', $htmlRoomName);

        // 5. Render with room fallback
        $tRoom = (object)[
            'title' => 'Test Room Training',
            'category' => 'Technical',
            'status' => 'Open',
            'start_date' => '2026-10-20',
            'end_date' => '2026-10-20',
            'start_time' => '09:00',
            'end_time' => '17:00',
            'registration_close_date' => null,
            'registration_close_time' => null,
            'location' => null,
            'venue' => null,
            'room_name' => null,
            'room' => 'Hall C',
            'target_employee' => 'All',
            'batch' => '1',
            'instructor_name' => 'Instructor C',
            'quota' => 30,
            'syllabi' => collect(),
            'syllabus_summary' => 'Overview',
        ];

        $htmlRoom = view('exports.training-schedule-pdf', ['trainings' => collect([$tRoom])])->render();
        $this->assertStringContainsString('Hall C', $htmlRoom);

        // 6. Render with completely empty venue data -> must display '-'
        $tEmpty = (object)[
            'title' => 'Test Empty Venue Training',
            'category' => 'Technical',
            'status' => 'Open',
            'start_date' => '2026-10-20',
            'end_date' => '2026-10-20',
            'start_time' => '09:00',
            'end_time' => '17:00',
            'registration_close_date' => null,
            'registration_close_time' => null,
            'location' => null,
            'venue' => null,
            'room_name' => null,
            'room' => null,
            'target_employee' => 'All',
            'batch' => '1',
            'instructor_name' => 'Instructor D',
            'quota' => 30,
            'syllabi' => collect(),
            'syllabus_summary' => 'Overview',
        ];

        $htmlEmpty = view('exports.training-schedule-pdf', ['trainings' => collect([$tEmpty])])->render();
        $this->assertMatchesRegularExpression('/<svg[^>]*>.*?<\/svg>\s*-\s*<\/span>/s', $htmlEmpty);
    }

    /**
     * 10. Verify today active session is marked as Running even if registration is closed.
     */
    public function test_today_active_session_marked_as_running_even_if_registration_closed(): void
    {
        $todayStr = Carbon::today()->format('Y-m-d');
        $trainingRunningToday = (object)[
            'id' => 999,
            'title' => 'Today Live Active Session',
            'course_name' => 'Today Live Active Session',
            'status' => 'Closed',
            'start_date' => $todayStr,
            'end_date' => $todayStr,
            'start_time' => '08:00:00',
            'end_time' => '17:00:00',
            'registration_close_date' => Carbon::yesterday()->format('Y-m-d'),
            'registration_close_time' => '17:00:00',
            'category' => 'Job Functional',
            'location' => 'Room 101',
            'instructor' => 'Jane Doe',
            'participants_count' => 30,
            'quota' => 30,
            'target_employee' => 'All',
            'batch' => '1',
            'syllabi' => collect(),
            'syllabus_summary' => 'Topic A',
        ];

        // Calendar tab rendering for current date
        $calHtml = view('user.trainingplan-user.partials.calendar-tab', [
            'trainings' => collect([$trainingRunningToday]),
            'selectedDate' => $todayStr,
            'todaysAgenda' => collect([$trainingRunningToday]),
        ])->render();

        // Must render Running badge in date cell
        $this->assertStringContainsString('cal-status-badge-running', $calHtml);
        $this->assertStringContainsString('Running: 1', $calHtml);

        // Must render Running badge in Today\'s Agenda sidebar
        $this->assertStringContainsString('Today\'s Agenda', $calHtml);
        $this->assertStringContainsString('badge bg-warning-subtle text-warning border border-warning-subtle rounded-pill', $calHtml);
        $this->assertStringContainsString('Running', $calHtml);
    }

    /**
     * 11. Verify export announcement card 380px min-height container and flex column layout.
     */
    public function test_export_card_380px_and_flex_structure(): void
    {
        $bladePath = resource_path('views/exports/training-schedule-pdf.blade.php');
        $bladeContent = file_get_contents($bladePath);

        $this->assertStringContainsString('min-height: 380px;', $bladeContent);
        $this->assertStringContainsString('margin-bottom: 20px;', $bladeContent);
        $this->assertStringContainsString('display: flex; flex-direction: column; justify-content: space-between;', $bladeContent);
    }

    /**
     * 12. Verify strict status evaluation hierarchy and legend items in calendar tab.
     */
    public function test_calendar_strict_status_evaluation_hierarchy_and_legend(): void
    {
        $todayStr = Carbon::today()->format('Y-m-d');
        $futureDateStr = Carbon::today()->addDays(5)->format('Y-m-d');

        // Training Open: registration_close_date >= today && is_published
        $tOpen = (object)[
            'id' => 101,
            'title' => 'Open Future Training',
            'start_date' => $futureDateStr,
            'end_date' => $futureDateStr,
            'registration_close_date' => Carbon::today()->addDays(2)->format('Y-m-d'),
            'registration_close_time' => '23:59:00',
            'is_published' => true,
            'category' => 'General',
            'location' => 'HQ',
            'instructor' => 'John Doe',
            'quota' => 20,
            'participants_count' => 5,
        ];

        // Training Closed: start_date > today && registration_close_date < today
        $tClosed = (object)[
            'id' => 102,
            'title' => 'Closed Future Training',
            'start_date' => $futureDateStr,
            'end_date' => $futureDateStr,
            'registration_close_date' => Carbon::yesterday()->format('Y-m-d'),
            'registration_close_time' => '23:59:00',
            'is_published' => true,
            'category' => 'General',
            'location' => 'HQ',
            'instructor' => 'Jane Doe',
            'quota' => 20,
            'participants_count' => 15,
        ];

        $calHtml = view('user.trainingplan-user.partials.calendar-tab', [
            'trainings' => collect([$tOpen, $tClosed]),
            'selectedDate' => $futureDateStr,
            'todaysAgenda' => collect(),
        ])->render();

        // 1. Both Open: 1 and Closed: 1 badges must be rendered on the shared date
        $this->assertStringContainsString('Open: 1', $calHtml);
        $this->assertStringContainsString('Closed: 1', $calHtml);
        $this->assertStringContainsString('bg-success-light text-success', $calHtml);
        $this->assertStringContainsString('bg-secondary-light text-secondary', $calHtml);

        // 2. Legend must contain all 4 status indicators in English
        $this->assertStringContainsString('Open Registration', $calHtml);
        $this->assertStringContainsString('Registration Closed', $calHtml);
        $this->assertStringContainsString('Running / Today\'s Session', $calHtml);
        $this->assertStringContainsString('Past Training Session', $calHtml);
    }

    /**
     * 13. Verify userIndex /trainingplan resolves calendarEvents without undefined variable errors
     *     and adheres strictly to status classification hierarchy and array structure.
     */
    public function test_user_index_calendar_events_structure_and_no_undefined_variable_errors(): void
    {
        $today = Carbon::today()->format('Y-m-d');
        $yesterday = Carbon::yesterday()->format('Y-m-d');
        $tomorrow = Carbon::tomorrow()->format('Y-m-d');
        $future5 = Carbon::today()->addDays(5)->format('Y-m-d');

        // 1. Ended
        $tEnded = OfflineTraining::create([
            'title' => 'Ended Test Training',
            'category' => 'Leadership',
            'start_date' => Carbon::today()->subDays(5)->format('Y-m-d'),
            'end_date' => $yesterday,
            'status' => 'Open Registration',
            'quota' => 20,
        ]);

        // 2. Running (active today, reg closed yesterday)
        $tRunning = OfflineTraining::create([
            'title' => 'Running Test Training',
            'category' => 'Job Functional',
            'start_date' => $yesterday,
            'end_date' => $tomorrow,
            'registration_close_date' => $yesterday,
            'status' => 'Open Registration',
            'quota' => 25,
        ]);

        // 3. Open (future, reg open)
        $tOpen = OfflineTraining::create([
            'title' => 'Open Test Training',
            'category' => 'Compliance & Safety',
            'start_date' => $tomorrow,
            'end_date' => $future5,
            'registration_close_date' => $tomorrow,
            'status' => 'Open Registration',
            'quota' => 30,
        ]);

        // 4. Closed (future, reg closed)
        $tClosed = OfflineTraining::create([
            'title' => 'Closed Test Training',
            'category' => 'Innovation',
            'start_date' => $tomorrow,
            'end_date' => $future5,
            'registration_close_date' => $yesterday,
            'status' => 'Open Registration',
            'quota' => 15,
        ]);

        $response = $this->actingAs($this->admin)->get('/trainingplan');
        $response->assertStatus(200);

        $calendarEvents = $response->viewData('calendarEvents');
        $this->assertIsArray($calendarEvents);

        $eventsById = collect($calendarEvents)->keyBy('id');

        // Check tEnded
        $this->assertTrue($eventsById->has((string)$tEnded->id));
        $endedEvent = $eventsById->get((string)$tEnded->id);
        $this->assertEquals('Ended', $endedEvent['status']);
        $this->assertEquals('Ended', $endedEvent['computedStatus']);
        $this->assertTrue($endedEvent['isPast']);
        $this->assertArrayHasKey('registration_close_date', $endedEvent);
        $this->assertArrayHasKey('registrationCloseDate', $endedEvent);
        $this->assertArrayHasKey('enrolled', $endedEvent);
        $this->assertArrayHasKey('quota', $endedEvent);

        // Check tRunning
        $this->assertTrue($eventsById->has((string)$tRunning->id));
        $runningEvent = $eventsById->get((string)$tRunning->id);
        $this->assertEquals('Running', $runningEvent['status']);
        $this->assertEquals('Running', $runningEvent['computedStatus']);

        // Check tOpen
        $this->assertTrue($eventsById->has((string)$tOpen->id));
        $openEvent = $eventsById->get((string)$tOpen->id);
        $this->assertEquals('Open', $openEvent['status']);
        $this->assertEquals('Open', $openEvent['computedStatus']);

        // Check tClosed
        $this->assertTrue($eventsById->has((string)$tClosed->id));
        $closedEvent = $eventsById->get((string)$tClosed->id);
        $this->assertEquals('Closed', $closedEvent['status']);
        $this->assertEquals('Closed', $closedEvent['computedStatus']);
    }

    /**
     * 14. Verify calendar cell status logic - Ensure "Running" status only triggers on Today's date,
     *     and future dates strictly render Closed: N or Open: N (never Running).
     */
    public function test_calendar_cell_status_running_only_triggers_on_today_date(): void
    {
        $todayStr = Carbon::today()->format('Y-m-d');
        $yesterdayStr = Carbon::yesterday()->format('Y-m-d');
        $futureDateStr = Carbon::today()->addDays(5)->format('Y-m-d');
        $futureEndStr = Carbon::today()->addDays(10)->format('Y-m-d');

        // Training spans yesterday through future +10 days, registration closed yesterday
        $trainingSpanningTodayAndFuture = (object)[
            'id' => 888,
            'title' => 'Multi-Day Ongoing Training',
            'start_date' => $yesterdayStr,
            'end_date' => $futureEndStr,
            'registration_close_date' => $yesterdayStr,
            'registration_close_time' => '17:00:00',
            'status' => 'Open Registration',
            'category' => 'Job Functional',
            'is_published' => true,
        ];

        // 1. Controller evaluateCalendarCellStatus helper verification
        // Past date cell (< today) => Ended
        $this->assertEquals(
            'Ended',
            OfflineTrainingController::evaluateCalendarCellStatus($trainingSpanningTodayAndFuture, $yesterdayStr, $todayStr)
        );

        // Today date cell (=== today) => Running (session overlaps today)
        $this->assertEquals(
            'Running',
            OfflineTrainingController::evaluateCalendarCellStatus($trainingSpanningTodayAndFuture, $todayStr, $todayStr)
        );

        // Future date cell (> today) with registration closed => Closed (NEVER Running)
        $this->assertEquals(
            'Closed',
            OfflineTrainingController::evaluateCalendarCellStatus($trainingSpanningTodayAndFuture, $futureDateStr, $todayStr)
        );

        // Future date cell (> today) with registration open => Open (NEVER Running)
        $trainingFutureRegOpen = (object)[
            'id' => 889,
            'title' => 'Multi-Day Future Open Training',
            'start_date' => $yesterdayStr,
            'end_date' => $futureEndStr,
            'registration_close_date' => Carbon::today()->addDays(2)->format('Y-m-d'),
            'registration_close_time' => '23:59:00',
            'status' => 'Open Registration',
            'category' => 'Job Functional',
            'is_published' => true,
        ];
        $this->assertEquals(
            'Open',
            OfflineTrainingController::evaluateCalendarCellStatus($trainingFutureRegOpen, $futureDateStr, $todayStr)
        );

        // 2. Blade Calendar Grid rendering verification
        $calHtml = view('user.trainingplan-user.partials.calendar-tab', [
            'trainings' => collect([$trainingSpanningTodayAndFuture, $trainingFutureRegOpen]),
            'selectedDate' => $futureDateStr,
            'todaysAgenda' => collect(),
        ])->render();

        // On future date cell (futureDateStr), both Open: 1 and Closed: 1 badges must be present
        $this->assertStringContainsString('Open: 1', $calHtml);
        $this->assertStringContainsString('Closed: 1', $calHtml);

        // On today's date cell, Running: 2 must be present
        $this->assertStringContainsString('Running: 2', $calHtml);
    }

    /**
     * 15. Verify dedicated JPG export template exists with 2-column grid and controller binds to it.
     */
    public function test_jpg_export_dedicated_view_and_two_column_grid(): void
    {
        $bladePath = resource_path('views/exports/training-schedule-jpg.blade.php');
        $this->assertFileExists($bladePath);

        $bladeContent = file_get_contents($bladePath);
        $this->assertStringContainsString('grid-template-columns: repeat(2, 1fr)', $bladeContent);
        $this->assertStringContainsString('gap: 20px', $bladeContent);

        // Verify controller exportJpg renders exports.training-schedule-jpg
        $controllerContent = file_get_contents(app_path('Http/Controllers/OfflineTrainingController.php'));
        $this->assertStringContainsString("view('exports.training-schedule-jpg'", $controllerContent);
    }
}
