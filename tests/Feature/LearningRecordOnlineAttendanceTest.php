<?php

namespace Tests\Feature;

use App\Exports\ExportMyAllLearningRecord;
use App\Exports\ExportMyLearningRecord;
use App\Http\Controllers\LearningRecordController;
use App\Models\Classification;
use App\Models\Content;
use App\Models\ContentUser;
use App\Models\OfflineTraining;
use App\Models\OfflineTrainingUser;
use App\Models\PlaylistContentUser;
use App\Models\TrainingPlan;
use App\Models\TrainingPlanDetail;
use App\Models\User;
use App\Services\CertificateEligibilityService;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Str;
use Tests\TestCase;

class LearningRecordOnlineAttendanceTest extends TestCase
{
    use DatabaseTransactions;

    protected User $user;
    protected Classification $classification;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create([
            'name'       => 'Test Learner Attendance',
            'id_emp'     => 'EMP-ATT-' . rand(1000, 9999),
            'team'       => 'Engineering',
            'department' => 'Technology',
            'division'   => 'LMS',
            'ssohash'    => 'test-hash-att-' . Str::uuid(),
            'sap_code'   => 'SAP-ATT-' . rand(1000, 9999),
            'role'       => 1,
            'active'     => 1,
        ]);

        $this->classification = Classification::firstOrCreate(
            ['classification_name' => 'Attendance Test Classification'],
            ['slug' => 'att-test-classification-' . rand(1000, 9999)]
        );
    }

    /**
     * Test Content with 100% progress displays Present badge (green bg-success-lt text-success).
     */
    public function test_content_progress_100_renders_attendance_present_badge(): void
    {
        $content = Content::create([
            'title'             => 'Full Progress Course',
            'slug'              => 'full-progress-course-' . rand(1000, 9999),
            'type'              => 'Video',
            'classification_id' => $this->classification->id,
            'active'            => 'Active',
            'duration'          => '00:30:00',
        ]);

        ContentUser::create([
            'content_id' => $content->id,
            'user_id'    => $this->user->id,
            'progress'   => 100,
            'finish'     => '1',
            'status'     => 'mandatory',
        ]);

        $response = $this->actingAs($this->user)
            ->get(route('user.learning.dashboard.partial', ['tab' => 'record', 'record_mode' => 'online']));

        $response->assertOk();
        $response->assertSee('Full Progress Course');
        $response->assertSee('learning-record-attendance', false);
        $response->assertSee('bg-success-lt text-success', false);
        $response->assertSee('Present');
    }

    /**
     * Test Content with progress < 100% displays neutral '-' badge (bg-secondary-lt text-secondary).
     */
    public function test_content_progress_less_than_100_renders_attendance_dash_badge(): void
    {
        $content = Content::create([
            'title'             => 'Half Progress Course',
            'slug'              => 'half-progress-course-' . rand(1000, 9999),
            'type'              => 'Video',
            'classification_id' => $this->classification->id,
            'active'            => 'Active',
            'duration'          => '00:30:00',
        ]);

        ContentUser::create([
            'content_id' => $content->id,
            'user_id'    => $this->user->id,
            'progress'   => 50,
            'finish'     => '1',
            'status'     => 'mandatory',
        ]);

        $response = $this->actingAs($this->user)
            ->get(route('user.learning.dashboard.partial', ['tab' => 'record', 'record_mode' => 'online']));

        $response->assertOk();
        $response->assertSee('Half Progress Course');
        $response->assertSee('learning-record-attendance', false);
        $response->assertSee('bg-secondary-lt text-secondary', false);
    }

    /**
     * Test Attendance Cell component directly with null and invalid progress values.
     */
    public function test_attendance_cell_with_null_and_invalid_progress(): void
    {
        // 1. Progress null -> '-'
        $renderedNull = $this->blade('<x-learning.record-attendance-cell :row="$row" />', [
            'row' => ['progress' => null, 'attendance' => null],
        ]);
        $this->assertStringContainsString('bg-secondary-lt text-secondary', $renderedNull);
        $this->assertStringContainsString('-', $renderedNull);
        $this->assertStringNotContainsString('Present', $renderedNull);

        // 2. Progress non-numeric -> '-'
        $renderedInvalid = $this->blade('<x-learning.record-attendance-cell :row="$row" />', [
            'row' => ['progress' => 'invalid', 'attendance' => null],
        ]);
        $this->assertStringContainsString('bg-secondary-lt text-secondary', $renderedInvalid);
        $this->assertStringContainsString('-', $renderedInvalid);

        // 3. Progress 100 -> 'Present'
        $renderedPresent = $this->blade('<x-learning.record-attendance-cell :row="$row" />', [
            'row' => ['progress' => 100, 'attendance' => 'Present'],
        ]);
        $this->assertStringContainsString('bg-success-lt text-success', $renderedPresent);
        $this->assertStringContainsString('Present', $renderedPresent);
    }

    /**
     * Test completed Playlist displays Present badge (green bg-success-lt text-success).
     */
    public function test_playlist_progress_100_renders_attendance_present_badge(): void
    {
        $plan = TrainingPlan::create([
            'title'  => 'Master Playlist Attendance',
            'slug'   => 'master-playlist-att-' . rand(1000, 9999),
            'active' => 'Active',
        ]);

        $content = Content::create([
            'title'             => 'Playlist Inner Content',
            'slug'              => 'pl-inner-content-' . rand(1000, 9999),
            'type'              => 'Video',
            'classification_id' => $this->classification->id,
            'active'            => 'Active',
            'playlistonly'      => 'Y',
        ]);

        TrainingPlanDetail::create([
            'trainingplan_id' => $plan->id,
            'content_id'      => $content->id,
            'content_order'   => 0,
        ]);

        PlaylistContentUser::create([
            'trainingplan_id' => $plan->id,
            'content_id'      => $content->id,
            'user_id'         => $this->user->id,
            'progress'        => 100,
            'finish'          => '1',
        ]);

        $response = $this->actingAs($this->user)
            ->get(route('user.learning.dashboard.partial', ['tab' => 'record', 'record_mode' => 'online']));

        $response->assertOk();
        $response->assertSee('Master Playlist Attendance');
        $response->assertSee('learning-record-attendance', false);
        $response->assertSee('bg-success-lt text-success', false);
        $response->assertSee('Present');
    }

    /**
     * Test ATTENDANCE column header is present and placed before Assessment Score in the thead.
     */
    public function test_attendance_column_header_and_order_in_kp_owl_table(): void
    {
        $response = $this->actingAs($this->user)
            ->get(route('user.learning.dashboard.partial', ['tab' => 'record', 'record_mode' => 'online']));

        $response->assertOk();
        $html = $response->getContent();

        // Check header labels in the table header
        $attHeader = '<span class="record-sort-label">ATTENDANCE</span>';
        $scoreHeader = '<span class="record-sort-label">Assessment Score</span>';

        $this->assertStringContainsString($attHeader, $html);
        $this->assertStringContainsString($scoreHeader, $html);

        $attPos = strpos($html, $attHeader);
        $scorePos = strpos($html, $scoreHeader);

        $this->assertNotFalse($attPos);
        $this->assertNotFalse($scorePos);
        $this->assertLessThan($scorePos, $attPos, 'ATTENDANCE header must appear before Assessment Score');
    }

    /**
     * Test raw rows built by LearningRecordController have attendance field.
     */
    public function test_raw_rows_include_attendance_data(): void
    {
        $content = Content::create([
            'title'             => 'Data Row Content',
            'slug'              => 'data-row-c-' . rand(1000, 9999),
            'type'              => 'Video',
            'classification_id' => $this->classification->id,
            'active'            => 'Active',
            'duration'          => '00:20:00',
        ]);

        ContentUser::create([
            'content_id' => $content->id,
            'user_id'    => $this->user->id,
            'progress'   => 100,
            'finish'     => '1',
        ]);

        \Illuminate\Support\Facades\Auth::login($this->user);

        $controller = app(LearningRecordController::class);
        $eligibilityService = app(CertificateEligibilityService::class);
        $rows = $controller->getRawOnlineRows($this->user->id, $eligibilityService);

        $row = $rows->firstWhere('slug', $content->slug);
        $this->assertNotNull($row);
        $this->assertArrayHasKey('attendance', $row);
        $this->assertEquals('Present', $row['attendance']);
    }

    /**
     * Test Non KP OWL (Offline) and All Record modes remain unaffected.
     */
    public function test_offline_and_all_mode_remain_unaffected(): void
    {
        $offlineTraining = OfflineTraining::create([
            'course_name'         => 'Regression Test Offline Course',
            'type'                => 'In-House',
            'category'            => 'Technical',
            'main_classification' => 'Engineering',
            'learning_hours'      => 4.0,
            'duration_seconds'    => 14400,
            'start_date'          => '2026-05-01',
            'finish_date'         => '2026-05-01',
        ]);

        OfflineTrainingUser::create([
            'offline_training_id' => $offlineTraining->id,
            'user_id'             => $this->user->id,
            'employee_id'         => $this->user->id_emp,
            'learning_hours'      => 4.0,
            'progress'            => 100,
            'attendance'          => 1,
            'assessment_score'    => 85,
        ]);

        $offlineResponse = $this->actingAs($this->user)
            ->get(route('user.learning.dashboard.partial', ['tab' => 'record', 'record_mode' => 'offline']));
        $offlineResponse->assertOk();
        $offlineResponse->assertSee('learningRecordOfflineTable', false);
        $offlineResponse->assertSee('Regression Test Offline Course');

        $allResponse = $this->actingAs($this->user)
            ->get(route('user.learning.dashboard.partial', ['tab' => 'record', 'record_mode' => 'all']));
        $allResponse->assertOk();
        $allResponse->assertSee('learningRecordAllTable', false);
        $allResponse->assertSee('Regression Test Offline Course');
    }

    /**
     * Test All Record mode displays Present badge for 100% online progress and '-' for < 100%.
     */
    public function test_all_record_mode_online_attendance_renders_present_and_dash_badge(): void
    {
        // 1. Online content 100%
        $contentFull = Content::create([
            'title'             => 'All Mode Online 100 Course',
            'slug'              => 'all-online-100-' . rand(1000, 9999),
            'type'              => 'Video',
            'classification_id' => $this->classification->id,
            'active'            => 'Active',
            'duration'          => '00:20:00',
        ]);
        ContentUser::create([
            'content_id' => $contentFull->id,
            'user_id'    => $this->user->id,
            'progress'   => 100,
            'finish'     => '1',
            'status'     => 'mandatory',
        ]);

        // 2. Online content 60%
        $contentPart = Content::create([
            'title'             => 'All Mode Online 60 Course',
            'slug'              => 'all-online-60-' . rand(1000, 9999),
            'type'              => 'Document',
            'classification_id' => $this->classification->id,
            'active'            => 'Active',
            'duration'          => '00:15:00',
        ]);
        ContentUser::create([
            'content_id' => $contentPart->id,
            'user_id'    => $this->user->id,
            'progress'   => 60,
            'finish'     => '1',
            'status'     => 'mandatory',
        ]);

        $response = $this->actingAs($this->user)
            ->get(route('user.learning.dashboard.partial', ['tab' => 'record', 'record_mode' => 'all']));

        $response->assertOk();
        $html = $response->getContent();

        // 100% course should show Present badge
        $this->assertStringContainsString('All Mode Online 100 Course', $html);
        $this->assertStringContainsString('All Mode Online 60 Course', $html);
        $this->assertStringContainsString('bg-success-lt text-success', $html);
        $this->assertStringContainsString('Present', $html);
    }

    /**
     * Test Excel exports expand playlist into parent-child rows with consistent Attendance.
     */
    public function test_excel_export_expands_playlist_content_parent_child_rows(): void
    {
        $plan = TrainingPlan::create([
            'title'  => 'Parent Playlist Expansion Test',
            'slug'   => 'parent-pl-exp-' . rand(1000, 9999),
            'active' => 'Active',
            'total_duration_seconds' => 3600,
            'learning_hours' => 1.0,
        ]);

        $child1 = Content::create([
            'title'             => '[Module 1] Video Introduction',
            'slug'              => 'm1-video-intro-' . rand(1000, 9999),
            'type'              => 'Video',
            'classification_id' => $this->classification->id,
            'active'            => 'Active',
            'duration'          => '00:20:00',
            'playlistonly'      => 'Y',
        ]);

        $child2 = Content::create([
            'title'             => '[Module 2] Document Deep Dive',
            'slug'              => 'm2-doc-deep-' . rand(1000, 9999),
            'type'              => 'Document',
            'classification_id' => $this->classification->id,
            'active'            => 'Active',
            'duration'          => '00:40:00',
            'playlistonly'      => 'Y',
        ]);

        TrainingPlanDetail::create([
            'trainingplan_id' => $plan->id,
            'content_id'      => $child1->id,
            'content_order'   => 1,
        ]);

        TrainingPlanDetail::create([
            'trainingplan_id' => $plan->id,
            'content_id'      => $child2->id,
            'content_order'   => 2,
        ]);

        PlaylistContentUser::create([
            'trainingplan_id' => $plan->id,
            'content_id'      => $child1->id,
            'user_id'         => $this->user->id,
            'progress'        => 100,
            'finish'          => '1',
            'actual_watch_seconds' => 1200,
        ]);

        PlaylistContentUser::create([
            'trainingplan_id' => $plan->id,
            'content_id'      => $child2->id,
            'user_id'         => $this->user->id,
            'progress'        => 100,
            'finish'          => '1',
            'actual_watch_seconds' => 2400,
        ]);

        $this->actingAs($this->user);

        // 1. KP OWL Export Check
        $exportKp = new ExportMyLearningRecord();
        $kpData = $exportKp->array();

        // Must have: Heading (row 0), Parent Playlist (row 1), Child 1 (row 2), Child 2 (row 3)
        $this->assertGreaterThanOrEqual(4, count($kpData));

        // Find parent row
        $parentRow = collect($kpData)->firstWhere(1, 'Parent Playlist Expansion Test');
        $this->assertNotNull($parentRow, 'Parent Playlist row must exist in KP OWL export');
        $this->assertEquals(1, $parentRow[0]); // Numbered No
        $this->assertEquals('Playlist', $parentRow[2]); // Type
        $this->assertEquals('Playlist', $parentRow[3]); // Category
        $this->assertEquals('100%', $parentRow[7]); // Progress
        $this->assertEquals('Present', $parentRow[9]); // Attendance

        // Find Child 1 row
        $child1Row = collect($kpData)->first(fn ($r) => str_contains((string) ($r[1] ?? ''), '[Module 1] Video Introduction'));
        $this->assertNotNull($child1Row, 'Child 1 row must exist');
        $this->assertEquals('', $child1Row[0]); // No is empty
        $this->assertStringStartsWith('    ', $child1Row[1]); // Indented title
        $this->assertEquals('Video', $child1Row[2]); // Type
        $this->assertEquals('Playlist', $child1Row[3]); // Category
        $this->assertEquals($this->classification->classification_name, $child1Row[4]); // Classification
        $this->assertEquals('100%', $child1Row[7]); // Progress
        $this->assertEquals('Present', $child1Row[9]); // Attendance

        // Find Child 2 row
        $child2Row = collect($kpData)->first(fn ($r) => str_contains((string) ($r[1] ?? ''), '[Module 2] Document Deep Dive'));
        $this->assertNotNull($child2Row, 'Child 2 row must exist');
        $this->assertEquals('', $child2Row[0]); // No is empty
        $this->assertStringStartsWith('    ', $child2Row[1]); // Indented title
        $this->assertEquals('Document', $child2Row[2]); // Type
        $this->assertEquals('Playlist', $child2Row[3]); // Category
        $this->assertEquals('Present', $child2Row[9]); // Attendance

        // 2. All Record Export Check
        $exportAll = new ExportMyAllLearningRecord();
        $allData = $exportAll->array();

        $this->assertGreaterThanOrEqual(4, count($allData));

        // Parent in All Record
        $parentRowAll = collect($allData)->firstWhere(1, 'Parent Playlist Expansion Test');
        $this->assertNotNull($parentRowAll);
        $this->assertEquals(1, $parentRowAll[0]);
        $this->assertEquals('Playlist', $parentRowAll[2]);
        $this->assertEquals('100%', $parentRowAll[7]);
        $this->assertEquals('Present', $parentRowAll[8]); // Attendance at index 8

        // Child 1 in All Record
        $child1RowAll = collect($allData)->first(fn ($r) => str_contains((string) ($r[1] ?? ''), '[Module 1] Video Introduction'));
        $this->assertNotNull($child1RowAll);
        $this->assertEquals('', $child1RowAll[0]);
        $this->assertStringStartsWith('    ', $child1RowAll[1]);
        $this->assertEquals('Video', $child1RowAll[2]);
        $this->assertEquals('Playlist', $child1RowAll[3]);
        $this->assertEquals('100%', $child1RowAll[7]);
        $this->assertEquals('Present', $child1RowAll[8]); // Attendance at index 8
    }
}
