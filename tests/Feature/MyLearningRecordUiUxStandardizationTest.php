<?php

namespace Tests\Feature;

use App\Models\Content;
use App\Models\ContentUser;
use App\Models\User;
use App\Models\OfflineTraining;
use App\Models\OfflineTrainingUser;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Str;
use Tests\TestCase;

class MyLearningRecordUiUxStandardizationTest extends TestCase
{
    use DatabaseTransactions;

    protected User $user;
    protected string $empId;

    protected function setUp(): void
    {
        parent::setUp();
        $this->empId = 'EMP' . rand(1000, 9999);
        $this->user = User::factory()->create([
            'id_emp'     => $this->empId,
            'team'       => 'Engineering',
            'department' => 'Technology',
            'division'   => 'LMS',
            'ssohash'    => 'hash-' . Str::uuid(),
            'sap_code'   => 'SAP' . rand(1000, 9999),
            'role'       => 1,
            'active'     => 1,
        ]);
    }

    /**
     * Test Non KP OWL (Offline) view contains standardized pagination footer layout.
     */
    public function test_offline_view_renders_standardized_pagination_footer(): void
    {
        $training = OfflineTraining::create([
            'course_name'         => 'Offline Training Alpha',
            'type'                => 'In-House',
            'category'            => 'Technical',
            'main_classification' => 'Engineering',
            'learning_hours'      => 8.0,
            'duration_seconds'    => 28800,
            'start_date'          => '2026-03-01',
            'finish_date'         => '2026-03-01',
        ]);

        OfflineTrainingUser::create([
            'offline_training_id' => $training->id,
            'user_id'             => $this->user->id,
            'employee_id'         => $this->empId,
            'learning_hours'      => 8.0,
            'progress'            => 100,
            'attendance'          => 1,
            'assessment_score'    => 85,
            'certificate'         => 'CERT-OFF-001',
        ]);

        $response = $this->actingAs($this->user)
            ->get(route('user.learning.dashboard.partial', ['tab' => 'record', 'record_mode' => 'offline']));

        $response->assertOk();
        $response->assertSee('id="learningRecordOfflineTable"', false);
        $response->assertSee('id="recordOfflinePageLength"', false);
        $response->assertSee('<option value="10">10</option>', false);
        $response->assertSee('<option value="25">25</option>', false);
        $response->assertSee('<option value="50">50</option>', false);
        $response->assertSee('id="recordOfflineTableInfo"', false);
        $response->assertSee('id="recordOfflinePagination"', false);
        $response->assertSee('record-row-index', false);
    }

    /**
     * Test All Record view contains standardized pagination footer layout.
     */
    public function test_all_record_view_renders_standardized_pagination_footer(): void
    {
        $training = OfflineTraining::create([
            'course_name'         => 'Offline Training Beta',
            'type'                => 'In-House',
            'category'            => 'Technical',
            'main_classification' => 'Engineering',
            'learning_hours'      => 8.0,
            'duration_seconds'    => 28800,
            'start_date'          => '2026-03-01',
            'finish_date'         => '2026-03-01',
        ]);

        OfflineTrainingUser::create([
            'offline_training_id' => $training->id,
            'user_id'             => $this->user->id,
            'employee_id'         => $this->empId,
            'learning_hours'      => 8.0,
            'progress'            => 100,
            'attendance'          => 1,
            'assessment_score'    => 90,
            'certificate'         => 'CERT-OFF-002',
        ]);

        $response = $this->actingAs($this->user)
            ->get(route('user.learning.dashboard.partial', ['tab' => 'record', 'record_mode' => 'all']));

        $response->assertOk();
        $response->assertSee('id="learningRecordAllTable"', false);
        $response->assertSee('id="recordAllPageLength"', false);
        $response->assertSee('<option value="10">10</option>', false);
        $response->assertSee('<option value="25">25</option>', false);
        $response->assertSee('<option value="50">50</option>', false);
        $response->assertSee('id="recordAllTableInfo"', false);
        $response->assertSee('id="recordAllPagination"', false);
        $response->assertSee('record-row-index', false);
    }

    /**
     * Test 12-column table headers on Offline mode.
     */
    public function test_offline_mode_has_twelve_standardized_headers(): void
    {
        $training = OfflineTraining::create([
            'course_name'         => 'Offline Training Gamma',
            'type'                => 'In-House',
            'category'            => 'Soft Skill',
            'main_classification' => 'Leadership',
            'learning_hours'      => 4.0,
            'duration_seconds'    => 14400,
            'start_date'          => '2026-04-01',
            'finish_date'         => '2026-04-01',
        ]);

        OfflineTrainingUser::create([
            'offline_training_id' => $training->id,
            'user_id'             => $this->user->id,
            'employee_id'         => $this->empId,
            'learning_hours'      => 4.0,
            'progress'            => 100,
            'attendance'          => 1,
            'assessment_score'    => 80,
        ]);

        $response = $this->actingAs($this->user)
            ->get(route('user.learning.dashboard.partial', ['tab' => 'record', 'record_mode' => 'offline']));

        $response->assertOk();
        $expectedHeaders = [
            'No',
            'Training Title',
            'Type',
            'Category',
            'Classification',
            'Duration',
            'Learning Hour',
            'Progress',
            'Attendance',
            'Date',
            'Score',
            'Certificate',
        ];

        foreach ($expectedHeaders as $header) {
            $response->assertSee($header);
        }
    }

    /**
     * Test 12-column table headers on All Record mode.
     */
    public function test_all_record_mode_has_twelve_standardized_headers(): void
    {
        $training = OfflineTraining::create([
            'course_name'         => 'All Mode Training Delta',
            'type'                => 'In-House',
            'category'            => 'Soft Skill',
            'main_classification' => 'Leadership',
            'learning_hours'      => 4.0,
            'duration_seconds'    => 14400,
            'start_date'          => '2026-04-01',
            'finish_date'         => '2026-04-01',
        ]);

        OfflineTrainingUser::create([
            'offline_training_id' => $training->id,
            'user_id'             => $this->user->id,
            'employee_id'         => $this->empId,
            'learning_hours'      => 4.0,
            'progress'            => 100,
            'attendance'          => 1,
            'assessment_score'    => 80,
        ]);

        $response = $this->actingAs($this->user)
            ->get(route('user.learning.dashboard.partial', ['tab' => 'record', 'record_mode' => 'all']));

        $response->assertOk();
        $expectedHeaders = [
            'No',
            'Training Title',
            'Type',
            'Category',
            'Classification',
            'Duration',
            'Learning Hour',
            'Progress',
            'Attendance',
            'Date',
            'Score',
            'Certificate',
        ];

        foreach ($expectedHeaders as $header) {
            $response->assertSee($header);
        }
    }

    /**
     * Test empty state hides the pagination footer.
     */
    public function test_empty_state_hides_pagination_footer(): void
    {
        $response = $this->actingAs($this->user)
            ->get(route('user.learning.dashboard.partial', ['tab' => 'record', 'record_mode' => 'offline']));

        $response->assertOk();
        $response->assertDontSee('id="recordOfflinePagination"', false);
        $response->assertSee('No offline learning records found');

        $responseAll = $this->actingAs($this->user)
            ->get(route('user.learning.dashboard.partial', ['tab' => 'record', 'record_mode' => 'all']));

        $responseAll->assertOk();
        $responseAll->assertDontSee('id="recordAllPagination"', false);
        $responseAll->assertSee('No learning records available');
    }

    /**
     * Test JS asset includes standardized pagination handlers.
     */
    public function test_js_asset_defines_standardized_pagination_handlers(): void
    {
        $jsContent = file_get_contents(public_path('js/learning-record.js'));

        $this->assertStringContainsString('function initStandardRecordPagination', $jsContent);
        $this->assertStringContainsString('function initLearningRecordOffline', $jsContent);
        $this->assertStringContainsString('function initLearningRecordAll', $jsContent);
        $this->assertStringContainsString('function initLearningRecord', $jsContent);
    }

    /**
     * Test CSS asset contains dedicated 12-column styling and removes internal vertical scroll.
     */
    public function test_css_asset_standardizes_twelve_column_tables_and_removes_internal_vertical_scroll(): void
    {
        $cssContent = file_get_contents(public_path('css/learning-record.css'));

        $this->assertStringContainsString('#learningRecordOfflineTable', $cssContent);
        $this->assertStringContainsString('#learningRecordAllTable', $cssContent);
        $this->assertStringContainsString('overflow-y: visible !important', $cssContent);
        $this->assertStringContainsString('max-height: none !important', $cssContent);
    }
}
