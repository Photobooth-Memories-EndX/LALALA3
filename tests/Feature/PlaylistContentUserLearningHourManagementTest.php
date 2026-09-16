<?php

namespace Tests\Feature;

use App\Models\Content;
use App\Models\PlaylistContentUser;
use App\Models\RoleAccessPermission;
use App\Models\TrainingPlan;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class PlaylistContentUserLearningHourManagementTest extends TestCase
{
    protected User $admin;
    protected User $regularUser;

    protected function setUp(): void
    {
        parent::setUp();

        // Ensure permission is seeded for tests
        RoleAccessPermission::updateOrCreate(
            ['role' => User::ROLE_ADMIN, 'module_key' => 'learning_hours_management'],
            ['can_access' => true]
        );
        RoleAccessPermission::updateOrCreate(
            ['role' => User::ROLE_SUPER_ADMIN, 'module_key' => 'learning_hours_management'],
            ['can_access' => true]
        );
        RoleAccessPermission::updateOrCreate(
            ['role' => User::ROLE_USER, 'module_key' => 'learning_hours_management'],
            ['can_access' => false]
        );

        $this->admin = User::where('role', User::ROLE_ADMIN)->first()
            ?? User::where('role', User::ROLE_SUPER_ADMIN)->first()
            ?? User::first();

        $this->regularUser = User::where('role', User::ROLE_USER)->first()
            ?? User::factory()->make(['role' => User::ROLE_USER]);
    }

    public function test_guest_cannot_access_playlist_learning_hours_management(): void
    {
        $response = $this->get('/manage/learning-hours/playlist-content-user');
        $response->assertRedirect('/login');
    }

    public function test_regular_user_is_forbidden(): void
    {
        $response = $this->actingAs($this->regularUser)->get('/manage/learning-hours/playlist-content-user');
        $response->assertStatus(403);
    }

    public function test_admin_can_view_playlist_learning_hours_management_page(): void
    {
        $response = $this->actingAs($this->admin)->get('/manage/learning-hours/playlist-content-user');
        $response->assertStatus(200);
        $response->assertViewIs('manage.learning-hours.playlist-content-user.index');
        $response->assertSee('Playlist Content User Learning Hours');
        $response->assertViewHas('statistics');
        $response->assertViewHas('trainingPlans');
        $response->assertViewHas('classifications');
        $response->assertViewHas('years');
    }

    public function test_datatable_endpoint_returns_server_side_json_with_correct_structure(): void
    {
        $response = $this->actingAs($this->admin)
            ->getJson('/manage/learning-hours/playlist-content-user/datatable?draw=1&start=0&length=10');

        $response->assertStatus(200);
        $response->assertJsonStructure([
            'draw',
            'recordsTotal',
            'recordsFiltered',
            'data',
        ]);

        $json = $response->json();
        $this->assertEquals(1, $json['draw']);
        $this->assertGreaterThan(0, $json['recordsTotal']);
        $this->assertCount(10, $json['data']);

        // Inspect first row content
        $firstRow = $json['data'][0];
        $this->assertArrayHasKey('employee', $firstRow);
        $this->assertArrayHasKey('playlist_title', $firstRow);
        $this->assertArrayHasKey('content_title', $firstRow);
        $this->assertArrayHasKey('current_time_display', $firstRow);
        $this->assertArrayHasKey('actual_learning_hour', $firstRow);
        $this->assertArrayHasKey('master_learning_hour', $firstRow);
        $this->assertArrayHasKey('calculation_source', $firstRow);
    }

    public function test_actual_learning_hour_is_displayed_with_four_decimals(): void
    {
        // Find a record with learning_hour = 0.3417 (from 1230s)
        $sample = PlaylistContentUser::where('learning_hour', 0.3417)->first();
        $this->assertNotNull($sample, 'Record with 0.3417 must exist in database.');

        $response = $this->actingAs($this->admin)
            ->getJson("/manage/learning-hours/playlist-content-user/datatable?user_id={$sample->user_id}&trainingplan_id={$sample->trainingplan_id}&content_id={$sample->content_id}");

        $response->assertStatus(200);
        $data = $response->json('data');
        $this->assertNotEmpty($data);

        $row = $data[0];
        $this->assertStringContainsString('0.3417 h', $row['actual_learning_hour']);
        $this->assertStringNotContainsString('0.3 h', $row['actual_learning_hour']);
    }

    public function test_calculation_source_distinguishes_actual_tracking_and_historical_fallback(): void
    {
        // 1. Actual tracking record (current_time > 0)
        $actualRecord = PlaylistContentUser::where('current_time', '>', 0)->first();
        if ($actualRecord) {
            $resp = $this->actingAs($this->admin)
                ->getJson("/manage/learning-hours/playlist-content-user/datatable?user_id={$actualRecord->user_id}&trainingplan_id={$actualRecord->trainingplan_id}&content_id={$actualRecord->content_id}");

            $resp->assertStatus(200);
            $data = $resp->json('data');
            $this->assertNotEmpty($data);
            $this->assertStringContainsString('Actual Tracking', $data[0]['calculation_source']);
        }

        // 2. Historical fallback record (current_time = 0 AND finish = 1)
        $historicalRecord = PlaylistContentUser::where('current_time', 0)->where('finish', '1')->first();
        if ($historicalRecord) {
            $resp = $this->actingAs($this->admin)
                ->getJson("/manage/learning-hours/playlist-content-user/datatable?user_id={$historicalRecord->user_id}&trainingplan_id={$historicalRecord->trainingplan_id}&content_id={$historicalRecord->content_id}");

            $resp->assertStatus(200);
            $data = $resp->json('data');
            $this->assertNotEmpty($data);
            $this->assertStringContainsString('Historical Fallback', $data[0]['calculation_source']);
        }
    }

    public function test_filter_by_source_and_learning_hour_range(): void
    {
        // Filter actual tracking records
        $response = $this->actingAs($this->admin)
            ->getJson('/manage/learning-hours/playlist-content-user/datatable?calculation_source=actual&length=10');

        $response->assertStatus(200);
        $data = $response->json('data');
        foreach ($data as $row) {
            $this->assertStringContainsString('Actual Tracking', $row['calculation_source']);
        }

        // Filter by hour range: 0.3000 to 0.4000
        $rangeResponse = $this->actingAs($this->admin)
            ->getJson('/manage/learning-hours/playlist-content-user/datatable?min_hour=0.3000&max_hour=0.4000&length=10');

        $rangeResponse->assertStatus(200);
        $rangeData = $rangeResponse->json('data');
        $this->assertNotEmpty($rangeData);
    }

    public function test_numeric_sorting_on_actual_learning_hour_at_database_level(): void
    {
        $responseDesc = $this->actingAs($this->admin)
            ->getJson('/manage/learning-hours/playlist-content-user/datatable?draw=1&start=0&length=5&order[0][column]=8&order[0][dir]=desc&columns[8][data]=actual_learning_hour');

        $responseDesc->assertStatus(200);
        $dataDesc = $responseDesc->json('data');
        $this->assertCount(5, $dataDesc);

        // Extract raw numeric values from badge strings
        $hoursDesc = array_map(function ($row) {
            preg_match('/([0-9]+\.[0-9]+)/', strip_tags($row['actual_learning_hour']), $matches);
            return isset($matches[1]) ? (float) $matches[1] : 0.0;
        }, $dataDesc);

        // Verify descending order
        $sorted = $hoursDesc;
        rsort($sorted);
        $this->assertEquals($sorted, $hoursDesc, 'Learning hours were not sorted numerically descending.');
    }

    public function test_no_n_plus_one_queries_when_loading_records(): void
    {
        DB::flushQueryLog();
        DB::enableQueryLog();

        $response = $this->actingAs($this->admin)
            ->getJson('/manage/learning-hours/playlist-content-user/datatable?length=50');

        $response->assertStatus(200);

        $queries = DB::getQueryLog();

        // Count queries targeting users and contents
        $contentQueries = array_filter($queries, function ($q) {
            return str_contains(strtolower($q['query']), 'from `content`')
                && !str_contains(strtolower($q['query']), 'where in');
        });

        // Eager loading should load content in a single WHERE IN query, NOT 50 individual queries!
        $this->assertLessThanOrEqual(
            2,
            count($contentQueries),
            'N+1 query detected! Content was queried individually instead of eager loaded.'
        );
    }

    public function test_page_and_datatable_are_read_only_and_perform_zero_mutations(): void
    {
        DB::flushQueryLog();
        DB::enableQueryLog();

        // 1. Visit index page
        $this->actingAs($this->admin)->get('/manage/learning-hours/playlist-content-user');

        // 2. Fetch datatable records
        $this->actingAs($this->admin)->getJson('/manage/learning-hours/playlist-content-user/datatable?length=20');

        $queries = DB::getQueryLog();

        $mutationQueries = array_filter($queries, function ($q) {
            $sql = strtolower(trim($q['query']));
            return str_starts_with($sql, 'update')
                || str_starts_with($sql, 'insert')
                || str_starts_with($sql, 'delete');
        });

        $this->assertCount(0, $mutationQueries, 'Page load must be STRICTLY READ-ONLY with ZERO database mutations.');
    }
}
