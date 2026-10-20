<?php

namespace Tests\Feature;

use App\Models\AssesmentResult;
use App\Models\Certificate;
use App\Models\Classification;
use App\Models\Content;
use App\Models\ContentUser;
use App\Models\PlaylistContentUser;
use App\Models\TrainingPlan;
use App\Models\TrainingPlanDetail;
use App\Models\User;
use App\Services\Learning\CompletedPlaylistService;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class ManageLearningTest extends TestCase
{
    public function test_authenticated_admin_can_access_manage_learning_page(): void
    {
        $user = User::where('role', '2')->first() ?? User::first();

        $queries = [];
        DB::listen(function ($query) use (&$queries) {
            $queries[] = $query->sql;
        });

        $start = microtime(true);
        $response = $this->actingAs($user)->get('/manage/learning');
        $duration = microtime(true) - $start;

        $response->assertStatus(200);
        $response->assertViewIs('main.learning.index');
        $response->assertSee('Manage Learning');
        $response->assertSee('Classification Summary');
        $response->assertViewHas('totalLearning');
        $response->assertViewHas('ongoing');
        $response->assertViewHas('finish');
        $response->assertViewHas('completionRate');
        $this->assertGreaterThan(0, $response->viewData('learningHours'));
        $response->assertViewHas('classification');

        echo PHP_EOL . "Feature Test - GET /manage/learning:" . PHP_EOL;
        echo "Status: " . $response->getStatusCode() . PHP_EOL;
        echo "Queries executed: " . count($queries) . PHP_EOL;
        echo "Request duration: " . round($duration, 4) . "s" . PHP_EOL;
        echo "Peak memory: " . round(memory_get_peak_usage(true) / 1024 / 1024, 2) . " MB" . PHP_EOL;
    }

    public function test_authenticated_admin_can_access_all_learning_page(): void
    {
        $user = User::where('role', '2')->first() ?? User::first();

        $response = $this->actingAs($user)->get('/manage/learning/all');
        $response->assertStatus(200);
        $response->assertViewIs('main.learning.all');
    }

    public function test_authenticated_admin_can_access_category_learning_page(): void
    {
        $user = User::where('role', '2')->first() ?? User::first();
        $classification = Classification::first();

        if ($classification) {
            $response = $this->actingAs($user)->get('/manage/learning/category/' . $classification->slug);
            $response->assertStatus(200);
            $response->assertViewIs('main.learning.all');
            $response->assertSee('Classification: ' . $classification->classification_name);
        }
    }

    public function test_authenticated_admin_can_access_content_learning_page(): void
    {
        $user = User::where('role', '2')->first() ?? User::first();
        $content = Content::first();

        if ($content) {
            $response = $this->actingAs($user)->get('/manage/learning/content/' . $content->slug);
            $response->assertStatus(200);
            $response->assertViewIs('main.learning.all');
            $response->assertSee('Content: ' . $content->title);
        }
    }

    public function test_authenticated_admin_can_fetch_datatable_with_server_side_pagination(): void
    {
        $user = User::where('role', '2')->first() ?? User::first();

        $response = $this->actingAs($user)->getJson('/manage/learning/datatable?draw=1&start=0&length=10');
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
    }

    public function test_datatable_filtering_by_status_and_year(): void
    {
        $user = User::where('role', '2')->first() ?? User::first();

        $response = $this->actingAs($user)->getJson('/manage/learning/datatable?draw=1&start=0&length=10&status=optional&year=' . date('Y'));
        $response->assertStatus(200);

        $json = $response->json();
        $this->assertLessThanOrEqual(10, count($json['data']));
    }

    public function test_datatable_search(): void
    {
        $user = User::where('role', '2')->first() ?? User::first();

        $response = $this->actingAs($user)->getJson('/manage/learning/datatable?draw=1&start=0&length=10&search[value]=safety');
        $response->assertStatus(200);

        $json = $response->json();
        $this->assertLessThanOrEqual(10, count($json['data']));
    }

    public function test_authenticated_admin_can_search_employees_for_live_dropdown(): void
    {
        $user = User::where('role', '2')->first() ?? User::first();

        $response = $this->actingAs($user)->getJson('/manage/learning/search-employees?q=');
        $response->assertStatus(200);
        $response->assertJsonStructure(['results']);

        $json = $response->json();
        if (!empty($json['results'])) {
            $this->assertStringNotContainsString('👤', $json['results'][0]['text']);
            $this->assertStringNotContainsString('📚', $json['results'][0]['text']);
        }

        $responseWithTerm = $this->actingAs($user)->getJson('/manage/learning/search-employees?q=admin');
        $responseWithTerm->assertStatus(200);
        $responseWithTerm->assertJsonStructure(['results']);
    }

    public function test_authenticated_admin_can_search_titles_for_live_dropdown(): void
    {
        $user = User::where('role', '2')->first() ?? User::first();

        $response = $this->actingAs($user)->getJson('/manage/learning/search-titles?q=');
        $response->assertStatus(200);
        $response->assertJsonStructure(['results']);

        $responseWithTerm = $this->actingAs($user)->getJson('/manage/learning/search-titles?q=safety');
        $responseWithTerm->assertStatus(200);
        $responseWithTerm->assertJsonStructure(['results']);
    }

    public function test_authenticated_admin_can_download_filtered_learning_export(): void
    {
        $user = User::where('role', '2')->first() ?? User::first();

        $response = $this->actingAs($user)->get('/manage/learning/download?search=admin&status=finished&year=' . date('Y'));
        $response->assertStatus(200);
        $this->assertTrue($response->headers->get('content-type') === 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet' || $response->headers->has('content-disposition'));
    }

    public function test_datatable_filtering_by_more_filters_parameters(): void
    {
        $user = User::where('role', '2')->first() ?? User::first();

        $response = $this->actingAs($user)->getJson('/manage/learning/datatable?draw=1&start=0&length=10&from_date=2020-01-01&to_date=2030-12-31&program_type=mandatory&progress_range=1-25&duration=10-30');
        $response->assertStatus(200);

        $json = $response->json();
        $this->assertEquals(1, $json['draw']);
        $this->assertArrayHasKey('recordsTotal', $json);
        $this->assertArrayHasKey('data', $json);
    }

    public function test_datatable_filtering_by_progress_range_51_75_and_optional_program(): void
    {
        $user = User::where('role', '2')->first() ?? User::first();

        $response = $this->actingAs($user)->getJson('/manage/learning/datatable?draw=1&start=0&length=10&program_type=optional&progress_range=51-75%25&duration=lt10');
        $response->assertStatus(200);

        $json = $response->json();
        $this->assertEquals(1, $json['draw']);
        $this->assertArrayHasKey('recordsTotal', $json);
        $this->assertArrayHasKey('data', $json);
    }

    public function test_datatable_filtering_by_employee_id(): void
    {
        $user = User::where('role', '2')->first() ?? User::first();

        $response = $this->actingAs($user)->getJson('/manage/learning/datatable?draw=1&start=0&length=10&employee_id=' . $user->id);
        $response->assertStatus(200);

        $json = $response->json();
        $this->assertEquals(1, $json['draw']);
        $this->assertArrayHasKey('recordsTotal', $json);
        $this->assertArrayHasKey('data', $json);
    }

    public function test_datatable_filtering_by_learning_title(): void
    {
        $user = User::where('role', '2')->first() ?? User::first();

        $response = $this->actingAs($user)->getJson('/manage/learning/datatable?draw=1&start=0&length=10&learning_title=safety');
        $response->assertStatus(200);

        $json = $response->json();
        $this->assertEquals(1, $json['draw']);
        $this->assertArrayHasKey('recordsTotal', $json);
        $this->assertArrayHasKey('data', $json);
    }

    public function test_datatable_filtering_by_both_employee_and_learning_title(): void
    {
        $user = User::where('role', '2')->first() ?? User::first();

        $response = $this->actingAs($user)->getJson('/manage/learning/datatable?draw=1&start=0&length=10&employee_id=' . $user->id . '&learning_title=test');
        $response->assertStatus(200);

        $json = $response->json();
        $this->assertEquals(1, $json['draw']);
        $this->assertArrayHasKey('recordsTotal', $json);
        $this->assertArrayHasKey('data', $json);
    }

    public function test_download_export_with_employee_and_learning_title(): void
    {
        $user = User::where('role', '2')->first() ?? User::first();

        $response = $this->actingAs($user)->get('/manage/learning/download?employee_id=' . $user->id . '&learning_title=safety&status=all');
        $response->assertStatus(200);
        $this->assertTrue($response->headers->get('content-type') === 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet' || $response->headers->has('content-disposition'));
    }

    public function test_datatable_page_size_entries_per_page(): void
    {
        $user = User::where('role', '2')->first() ?? User::first();

        $response25 = $this->actingAs($user)->getJson('/manage/learning/datatable?draw=1&start=0&entries=25');
        $response25->assertStatus(200);
        $json25 = $response25->json();
        $this->assertCount(25, $json25['data']);

        $response50 = $this->actingAs($user)->getJson('/manage/learning/datatable?draw=1&start=0&entries=50');
        $response50->assertStatus(200);
        $json50 = $response50->json();
        $this->assertCount(50, $json50['data']);
    }

    public function test_datatable_filtering_by_status_playlist(): void
    {
        $user = User::where('role', '2')->first() ?? User::first();

        $response = $this->actingAs($user)->getJson('/manage/learning/datatable?draw=1&start=0&length=10&program_type=playlist');
        $response->assertStatus(200);
        $json = $response->json();
        $this->assertGreaterThan(0, $json['recordsFiltered']);
        foreach ($json['data'] as $item) {
            $this->assertStringContainsString('Playlist', $item['category_badge']);
        }
    }

    public function test_datatable_filtering_by_status_mandatory(): void
    {
        $user = User::where('role', '2')->first() ?? User::first();

        $response = $this->actingAs($user)->getJson('/manage/learning/datatable?draw=1&start=0&length=10&program_type=mandatory');
        $response->assertStatus(200);
        $json = $response->json();
        $this->assertGreaterThan(0, $json['recordsFiltered']);
        foreach ($json['data'] as $item) {
            $this->assertStringContainsString('Mandatory', $item['status_badge']);
        }
    }

    public function test_datatable_filtering_by_status_optional(): void
    {
        $user = User::where('role', '2')->first() ?? User::first();

        $response = $this->actingAs($user)->getJson('/manage/learning/datatable?draw=1&start=0&length=10&program_type=optional');
        $response->assertStatus(200);
        $json = $response->json();
        $this->assertGreaterThan(0, $json['recordsFiltered']);
        foreach ($json['data'] as $item) {
            $this->assertStringContainsString('Optional', $item['status_badge']);
        }
    }

    public function test_datatable_includes_action_column_with_three_buttons(): void
    {
        $user = User::where('role', '2')->first() ?? User::first();

        $response = $this->actingAs($user)->getJson('/manage/learning/datatable?draw=1&start=0&length=5');
        $response->assertStatus(200);
        $json = $response->json();
        $this->assertNotEmpty($json['data']);

        $firstRow = $json['data'][0];
        $this->assertArrayHasKey('action', $firstRow);
        $actionHtml = $firstRow['action'];

        $this->assertStringContainsString('btn-view-learning-detail', $actionHtml);
        $this->assertStringContainsString('btn-edit-learning-record', $actionHtml);
        $this->assertStringContainsString('btn-delete-learning-record', $actionHtml);
        $this->assertStringContainsString('data-source-type=', $actionHtml);
        $this->assertStringContainsString('data-source-id=', $actionHtml);
        $this->assertStringContainsString('ti-eye', $actionHtml);
        $this->assertStringContainsString('ti-pencil', $actionHtml);
        $this->assertStringContainsString('ti-trash', $actionHtml);
    }

    public function test_record_detail_for_content_user(): void
    {
        $user = User::where('role', '2')->first() ?? User::first();
        $cu = \App\Models\ContentUser::first();
        $this->assertNotNull($cu, 'ContentUser record must exist for testing');

        $response = $this->actingAs($user)->getJson("/manage/learning/record-detail/content/{$cu->id}");
        $response->assertStatus(200);

        $json = $response->json();
        $this->assertEquals('success', $json['status']);
        $this->assertEquals('content', $json['data']['source_type']);
        $this->assertEquals($cu->id, $json['data']['source_id']);
        $this->assertArrayHasKey('employee', $json['data']);
        $this->assertArrayHasKey('learning', $json['data']);
        $this->assertArrayHasKey('progress', $json['data']);
        $this->assertArrayHasKey('timestamps', $json['data']);
    }

    public function test_record_detail_for_playlist_content_user(): void
    {
        $user = User::where('role', '2')->first() ?? User::first();
        $pcu = \App\Models\PlaylistContentUser::first();
        $this->assertNotNull($pcu, 'PlaylistContentUser record must exist for testing');

        $response = $this->actingAs($user)->getJson("/manage/learning/record-detail/playlist/{$pcu->id}");
        $response->assertStatus(200);

        $json = $response->json();
        $this->assertEquals('success', $json['status']);
        $this->assertEquals('playlist', $json['data']['source_type']);
        $this->assertEquals($pcu->id, $json['data']['source_id']);
        $this->assertArrayHasKey('employee', $json['data']);
        $this->assertArrayHasKey('learning', $json['data']);
        $this->assertArrayHasKey('progress', $json['data']);
        $this->assertArrayHasKey('timestamps', $json['data']);
    }

    public function test_record_detail_with_invalid_source_type(): void
    {
        $user = User::where('role', '2')->first() ?? User::first();

        $response = $this->actingAs($user)->getJson('/manage/learning/record-detail/invalid_type/1');
        $response->assertStatus(404);
        $this->assertEquals('Invalid learning source type.', $response->json('message'));
    }

    public function test_authorized_admin_can_update_content_learning_record(): void
    {
        $user = User::where('role', '2')->first() ?? User::first();
        $cu = \App\Models\ContentUser::with('content')->first();
        $this->assertNotNull($cu, 'ContentUser record must exist');

        $original = $cu->only(['progress', 'current_time', 'actual_watch_seconds', 'afk_duration_seconds', 'finish', 'completed_at', 'learning_status', 'learning_hours']);

        try {
            $response = $this->actingAs($user)->putJson("/manage/learning/record/content/{$cu->id}", [
                'progress' => 85,
                'current_time' => 10,
                'actual_watch_seconds' => 120,
                'afk_duration_seconds' => 5,
            ]);

            $response->assertStatus(200);
            $response->assertJson([
                'status' => 'success',
                'data' => [
                    'source_type' => 'content',
                    'source_id' => $cu->id,
                    'progress' => 85,
                    'finish' => 0,
                    'learning_status' => 'in_progress',
                    'current_time' => 10,
                    'actual_watch_seconds' => 120,
                    'afk_duration_seconds' => 5,
                ],
            ]);

            $cu->refresh();
            $this->assertEquals(85, $cu->progress);
            $this->assertEquals(10, $cu->current_time);
            $this->assertEquals(120, $cu->actual_watch_seconds);
            $this->assertEquals(5, $cu->afk_duration_seconds);
            $this->assertEquals('0', (string) $cu->finish);
            $this->assertEquals('in_progress', $cu->learning_status);
            $this->assertNull($cu->completed_at);
        } finally {
            $cu->forceFill($original)->saveQuietly();
        }
    }

    public function test_authorized_admin_can_update_playlist_learning_record(): void
    {
        $user = User::where('role', '2')->first() ?? User::first();
        $pcu = \App\Models\PlaylistContentUser::with('content')->first();
        $this->assertNotNull($pcu, 'PlaylistContentUser record must exist');

        $original = $pcu->only(['progress', 'current_time', 'actual_watch_seconds', 'afk_duration_seconds', 'finish', 'completed_at', 'learning_status', 'learning_hour']);

        try {
            $response = $this->actingAs($user)->putJson("/manage/learning/record/playlist/{$pcu->id}", [
                'progress' => 75,
                'current_time' => 15,
                'actual_watch_seconds' => 150,
                'afk_duration_seconds' => 10,
            ]);

            $response->assertStatus(200);
            $response->assertJson([
                'status' => 'success',
                'data' => [
                    'source_type' => 'playlist',
                    'source_id' => $pcu->id,
                    'progress' => 75,
                    'finish' => 0,
                    'learning_status' => 'in_progress',
                    'current_time' => 15,
                    'actual_watch_seconds' => 150,
                    'afk_duration_seconds' => 10,
                ],
            ]);

            $pcu->refresh();
            $this->assertEquals(75, $pcu->progress);
            $this->assertEquals(15, $pcu->current_time);
            $this->assertEquals(150, $pcu->actual_watch_seconds);
            $this->assertEquals(10, $pcu->afk_duration_seconds);
            $this->assertEquals('0', (string) $pcu->finish);
            $this->assertEquals('in_progress', $pcu->learning_status);
            $this->assertNull($pcu->completed_at);
        } finally {
            $pcu->forceFill($original)->saveQuietly();
        }
    }

    public function test_invalid_source_type_rejected(): void
    {
        $user = User::where('role', '2')->first() ?? User::first();

        $response = $this->actingAs($user)->putJson('/manage/learning/record/invalid_source_type/1', [
            'progress' => 50,
            'current_time' => 10,
            'actual_watch_seconds' => 10,
            'afk_duration_seconds' => 0,
        ]);

        $response->assertStatus(404);
        $this->assertEquals('Invalid learning source type.', $response->json('message'));
    }

    public function test_progress_greater_than_100_rejected(): void
    {
        $user = User::where('role', '2')->first() ?? User::first();
        $cu = \App\Models\ContentUser::first();

        $response = $this->actingAs($user)->putJson("/manage/learning/record/content/{$cu->id}", [
            'progress' => 101,
            'current_time' => 0,
            'actual_watch_seconds' => 0,
            'afk_duration_seconds' => 0,
        ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['progress']);
    }

    public function test_progress_less_than_0_rejected(): void
    {
        $user = User::where('role', '2')->first() ?? User::first();
        $cu = \App\Models\ContentUser::first();

        $response = $this->actingAs($user)->putJson("/manage/learning/record/content/{$cu->id}", [
            'progress' => -1,
            'current_time' => 0,
            'actual_watch_seconds' => 0,
            'afk_duration_seconds' => 0,
        ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['progress']);
    }

    public function test_negative_tracking_seconds_rejected(): void
    {
        $user = User::where('role', '2')->first() ?? User::first();
        $cu = \App\Models\ContentUser::first();

        $response = $this->actingAs($user)->putJson("/manage/learning/record/content/{$cu->id}", [
            'progress' => 50,
            'current_time' => -10,
            'actual_watch_seconds' => -20,
            'afk_duration_seconds' => -5,
        ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['current_time', 'actual_watch_seconds', 'afk_duration_seconds']);
    }

    public function test_progress_100_sets_finish_correctly(): void
    {
        $user = User::where('role', '2')->first() ?? User::first();
        $cu = \App\Models\ContentUser::with('content')->first();
        $original = $cu->only(['progress', 'current_time', 'actual_watch_seconds', 'afk_duration_seconds', 'finish', 'completed_at', 'learning_status', 'learning_hours']);

        try {
            $response = $this->actingAs($user)->putJson("/manage/learning/record/content/{$cu->id}", [
                'progress' => 100,
                'current_time' => 50,
                'actual_watch_seconds' => 100,
                'afk_duration_seconds' => 0,
            ]);

            $response->assertStatus(200);
            $cu->refresh();
            $this->assertEquals(100, $cu->progress);
            $this->assertEquals('1', (string) $cu->finish);
            $this->assertEquals('completed', $cu->learning_status);
            $this->assertNotNull($cu->completed_at);
        } finally {
            $cu->forceFill($original)->saveQuietly();
        }
    }

    public function test_progress_below_100_sets_unfinished_correctly(): void
    {
        $user = User::where('role', '2')->first() ?? User::first();
        $cu = \App\Models\ContentUser::with('content')->first();
        $original = $cu->only(['progress', 'current_time', 'actual_watch_seconds', 'afk_duration_seconds', 'finish', 'completed_at', 'learning_status', 'learning_hours']);

        try {
            // First test with progress between 1 and 99
            $response = $this->actingAs($user)->putJson("/manage/learning/record/content/{$cu->id}", [
                'progress' => 45,
                'current_time' => 20,
                'actual_watch_seconds' => 50,
                'afk_duration_seconds' => 0,
            ]);

            $response->assertStatus(200);
            $cu->refresh();
            $this->assertEquals(45, $cu->progress);
            $this->assertEquals('0', (string) $cu->finish);
            $this->assertEquals('in_progress', $cu->learning_status);
            $this->assertNull($cu->completed_at);

            // Next test with progress 0
            $response0 = $this->actingAs($user)->putJson("/manage/learning/record/content/{$cu->id}", [
                'progress' => 0,
                'current_time' => 0,
                'actual_watch_seconds' => 0,
                'afk_duration_seconds' => 0,
            ]);

            $response0->assertStatus(200);
            $cu->refresh();
            $this->assertEquals(0, $cu->progress);
            $this->assertEquals('0', (string) $cu->finish);
            $this->assertEquals('not_started', $cu->learning_status);
            $this->assertNull($cu->completed_at);
        } finally {
            $cu->forceFill($original)->saveQuietly();
        }
    }

    public function test_completed_at_created_when_becoming_completed(): void
    {
        $user = User::where('role', '2')->first() ?? User::first();
        $cu = \App\Models\ContentUser::with('content')->first();
        $original = $cu->only(['progress', 'current_time', 'actual_watch_seconds', 'afk_duration_seconds', 'finish', 'completed_at', 'learning_status', 'learning_hours']);

        try {
            // Make sure record is unfinished first
            $cu->forceFill([
                'progress' => 50,
                'finish' => '0',
                'completed_at' => null,
                'learning_status' => 'in_progress',
            ])->saveQuietly();

            $response = $this->actingAs($user)->putJson("/manage/learning/record/content/{$cu->id}", [
                'progress' => 100,
                'current_time' => 50,
                'actual_watch_seconds' => 100,
                'afk_duration_seconds' => 0,
            ]);

            $response->assertStatus(200);
            $cu->refresh();
            $this->assertEquals('1', (string) $cu->finish);
            $this->assertNotNull($cu->completed_at);
        } finally {
            $cu->forceFill($original)->saveQuietly();
        }
    }

    public function test_existing_completed_at_preserved_when_already_completed(): void
    {
        $user = User::where('role', '2')->first() ?? User::first();
        $cu = \App\Models\ContentUser::with('content')->first();
        $original = $cu->only(['progress', 'current_time', 'actual_watch_seconds', 'afk_duration_seconds', 'finish', 'completed_at', 'learning_status', 'learning_hours']);

        try {
            $historicalDate = '2023-05-15 08:30:00';
            $cu->forceFill([
                'progress' => 100,
                'finish' => '1',
                'completed_at' => $historicalDate,
                'learning_status' => 'completed',
            ])->saveQuietly();

            $response = $this->actingAs($user)->putJson("/manage/learning/record/content/{$cu->id}", [
                'progress' => 100,
                'current_time' => 60,
                'actual_watch_seconds' => 120,
                'afk_duration_seconds' => 5,
            ]);

            $response->assertStatus(200);
            $cu->refresh();
            $this->assertEquals('1', (string) $cu->finish);
            $this->assertEquals($historicalDate, $cu->completed_at->format('Y-m-d H:i:s'));
        } finally {
            $cu->forceFill($original)->saveQuietly();
        }
    }

    public function test_learning_hours_recalculated(): void
    {
        $user = User::where('role', '2')->first() ?? User::first();
        $cu = \App\Models\ContentUser::with('content')->first();
        $original = $cu->only(['progress', 'current_time', 'actual_watch_seconds', 'afk_duration_seconds', 'finish', 'completed_at', 'learning_status', 'learning_hours']);

        try {
            $response = $this->actingAs($user)->putJson("/manage/learning/record/content/{$cu->id}", [
                'progress' => 100,
                'current_time' => 30,
                'actual_watch_seconds' => 3600,
                'afk_duration_seconds' => 0,
            ]);

            $response->assertStatus(200);
            $cu->refresh();
            $this->assertGreaterThan(0, (float) $cu->learning_hours);
            $calculator = app(\App\Services\LearningHours\ActualLearningHourCalculator::class);
            $expectedHours = $calculator->calculateFromTracking(
                $cu->current_time,
                $cu->finish,
                $cu->progress,
                $cu->duration_seconds ?? $cu->content?->duration,
                3600,
                $cu->content?->max_learning_hours
            );
            $this->assertEquals($expectedHours, (float) $cu->learning_hours);
        } finally {
            $cu->forceFill($original)->saveQuietly();
        }
    }

    public function test_unauthorized_user_cannot_update(): void
    {
        $nonAdmin = User::where('role', '!=', '2')->first();
        $cu = \App\Models\ContentUser::first();

        if ($nonAdmin) {
            $response = $this->actingAs($nonAdmin)->putJson("/manage/learning/record/content/{$cu->id}", [
                'progress' => 100,
                'current_time' => 10,
                'actual_watch_seconds' => 20,
                'afk_duration_seconds' => 0,
            ]);
            $this->assertContains($response->getStatusCode(), [302, 403]);
        }

        // Also test unauthenticated
        $unauthResponse = $this->putJson("/manage/learning/record/content/{$cu->id}", [
            'progress' => 100,
            'current_time' => 10,
            'actual_watch_seconds' => 20,
            'afk_duration_seconds' => 0,
        ]);
        $this->assertContains($unauthResponse->getStatusCode(), [302, 401, 403]);
    }

    public function test_playlist_record_uses_learning_hour_column(): void
    {
        $user = User::where('role', '2')->first() ?? User::first();
        $pcu = \App\Models\PlaylistContentUser::with('content')->first();
        $this->assertNotNull($pcu, 'PlaylistContentUser record must exist');
        $original = $pcu->only(['progress', 'current_time', 'actual_watch_seconds', 'afk_duration_seconds', 'finish', 'completed_at', 'learning_status', 'learning_hour']);

        try {
            $response = $this->actingAs($user)->putJson("/manage/learning/record/playlist/{$pcu->id}", [
                'progress' => 90,
                'current_time' => 20,
                'actual_watch_seconds' => 1800,
                'afk_duration_seconds' => 5,
            ]);

            $response->assertStatus(200);
            $json = $response->json();
            $this->assertArrayHasKey('learning_hour', $json['data']);
            $this->assertArrayNotHasKey('learning_hours', $json['data']);

            $pcu->refresh();
            $this->assertNotNull($pcu->learning_hour);
            $this->assertTrue(\Illuminate\Support\Facades\Schema::hasColumn('playlist_content_users', 'learning_hour'));
        } finally {
            $pcu->forceFill($original)->saveQuietly();
        }
    }

    public function test_content_record_uses_learning_hours_column(): void
    {
        $user = User::where('role', '2')->first() ?? User::first();
        $cu = \App\Models\ContentUser::with('content')->first();
        $this->assertNotNull($cu, 'ContentUser record must exist');
        $original = $cu->only(['progress', 'current_time', 'actual_watch_seconds', 'afk_duration_seconds', 'finish', 'completed_at', 'learning_status', 'learning_hours']);

        try {
            $response = $this->actingAs($user)->putJson("/manage/learning/record/content/{$cu->id}", [
                'progress' => 90,
                'current_time' => 20,
                'actual_watch_seconds' => 1800,
                'afk_duration_seconds' => 5,
            ]);

            $response->assertStatus(200);
            $json = $response->json();
            $this->assertArrayHasKey('learning_hours', $json['data']);
            $this->assertArrayNotHasKey('learning_hour', $json['data']);

            $cu->refresh();
            $this->assertNotNull($cu->learning_hours);
            $this->assertTrue(\Illuminate\Support\Facades\Schema::hasColumn('content_users', 'learning_hours'));
        } finally {
            $cu->forceFill($original)->saveQuietly();
        }
    }

    public function test_current_time_exceeding_duration_rejected(): void
    {
        $user = User::where('role', '2')->first() ?? User::first();
        $cu = \App\Models\ContentUser::with('content')
            ->whereHas('content', function ($q) {
                $q->whereNotNull('duration')->where('duration', '!=', '')->where('duration', '!=', '00:00:00');
            })
            ->first();

        if ($cu) {
            $calculator = app(\App\Services\LearningHours\ActualLearningHourCalculator::class);
            $durationSec = $calculator->parseContentDurationToSeconds($cu->content->duration);

            if ($durationSec > 0) {
                $response = $this->actingAs($user)->putJson("/manage/learning/record/content/{$cu->id}", [
                    'progress' => 50,
                    'current_time' => $durationSec + 1000,
                    'actual_watch_seconds' => 50,
                    'afk_duration_seconds' => 0,
                ]);

                $response->assertStatus(422);
                $response->assertJsonValidationErrors(['current_time']);
            }
        }
    }

    public function test_authorized_admin_can_reset_content_learning_record(): void
    {
        $user = User::where('role', '2')->first() ?? User::first();
        $cu = ContentUser::with('content')->first();
        $this->assertNotNull($cu, 'ContentUser record must exist');
        $original = $cu->only(['progress', 'current_time', 'actual_watch_seconds', 'afk_duration_seconds', 'finish', 'completed_at', 'learning_status', 'learning_hours', 'last_page']);

        try {
            // Put it in an active in-progress state first
            $cu->forceFill([
                'progress' => 60,
                'current_time' => 30,
                'actual_watch_seconds' => 120,
                'afk_duration_seconds' => 10,
                'finish' => '0',
                'learning_status' => 'in_progress',
                'completed_at' => null,
            ])->saveQuietly();

            $response = $this->actingAs($user)->postJson("/manage/learning/record/content/{$cu->id}/reset");

            $response->assertStatus(200);
            $json = $response->json();
            $this->assertEquals('success', $json['status']);
            $this->assertEquals('Learning progress has been reset successfully.', $json['message']);
            $this->assertEquals('content', $json['data']['source_type']);
            $this->assertEquals($cu->id, $json['data']['source_id']);
            $this->assertEquals(0, $json['data']['progress']);
            $this->assertEquals(0, $json['data']['finish']);
            $this->assertEquals('not_started', $json['data']['learning_status']);
            $this->assertEquals(0, $json['data']['current_time']);
            $this->assertEquals(0, $json['data']['actual_watch_seconds']);
            $this->assertEquals(0, $json['data']['afk_duration_seconds']);
            $this->assertEquals(0.0, (float) $json['data']['learning_hours']);
            $this->assertNull($json['data']['completed_at']);

            // Verify database record still exists (NEVER deleted)
            $cu->refresh();
            $this->assertEquals(0, (int) $cu->progress);
            $this->assertEquals('0', (string) $cu->finish);
            $this->assertEquals('not_started', $cu->learning_status);
            $this->assertEquals(0, (int) $cu->current_time);
            $this->assertEquals(0, (int) $cu->actual_watch_seconds);
            $this->assertEquals(0, (int) $cu->afk_duration_seconds);
            $this->assertEquals(0.0, (float) $cu->learning_hours);
            $this->assertNull($cu->completed_at);
            if ($original['last_page'] !== null) {
                $this->assertEquals(0, (int) $cu->last_page);
            }
        } finally {
            $cu->forceFill($original)->saveQuietly();
        }
    }

    public function test_authorized_admin_can_reset_completed_content_learning_record(): void
    {
        $user = User::where('role', '2')->first() ?? User::first();
        $cu = ContentUser::with('content')->first();
        $this->assertNotNull($cu, 'ContentUser record must exist');
        $original = $cu->only(['progress', 'current_time', 'actual_watch_seconds', 'afk_duration_seconds', 'finish', 'completed_at', 'learning_status', 'learning_hours', 'last_page']);

        try {
            $completedTime = now()->subDays(2);
            $cu->forceFill([
                'progress' => 100,
                'current_time' => 300,
                'actual_watch_seconds' => 1800,
                'afk_duration_seconds' => 20,
                'finish' => '1',
                'learning_status' => 'completed',
                'completed_at' => $completedTime,
                'learning_hours' => 0.5,
            ])->saveQuietly();

            $response = $this->actingAs($user)->postJson("/manage/learning/record/content/{$cu->id}/reset");

            $response->assertStatus(200);
            $json = $response->json();
            $this->assertEquals(0, $json['data']['progress']);
            $this->assertEquals(0, $json['data']['finish']);
            $this->assertNull($json['data']['completed_at']);
            $this->assertEquals('not_started', $json['data']['learning_status']);
            $this->assertEquals(0.0, (float) $json['data']['learning_hours']);

            $cu->refresh();
            $this->assertEquals(0, (int) $cu->progress);
            $this->assertEquals('0', (string) $cu->finish);
            $this->assertNull($cu->completed_at);
            $this->assertEquals('not_started', $cu->learning_status);
            $this->assertEquals(0.0, (float) $cu->learning_hours);
        } finally {
            $cu->forceFill($original)->saveQuietly();
        }
    }

    public function test_authorized_admin_can_reset_playlist_learning_record(): void
    {
        $user = User::where('role', '2')->first() ?? User::first();
        $pcu = PlaylistContentUser::with('content')->first();
        $this->assertNotNull($pcu, 'PlaylistContentUser record must exist');
        $original = $pcu->only(['progress', 'current_time', 'actual_watch_seconds', 'afk_duration_seconds', 'finish', 'completed_at', 'learning_status', 'learning_hour', 'last_page']);

        try {
            $pcu->forceFill([
                'progress' => 75,
                'current_time' => 150,
                'actual_watch_seconds' => 600,
                'afk_duration_seconds' => 15,
                'finish' => '0',
                'learning_status' => 'in_progress',
                'completed_at' => null,
            ])->saveQuietly();

            $response = $this->actingAs($user)->postJson("/manage/learning/record/playlist/{$pcu->id}/reset");

            $response->assertStatus(200);
            $json = $response->json();
            $this->assertEquals('success', $json['status']);
            $this->assertEquals('playlist', $json['data']['source_type']);
            $this->assertEquals($pcu->id, $json['data']['source_id']);
            $this->assertEquals(0, $json['data']['progress']);
            $this->assertEquals(0, $json['data']['finish']);
            $this->assertEquals('not_started', $json['data']['learning_status']);
            $this->assertEquals(0, $json['data']['current_time']);
            $this->assertEquals(0, $json['data']['actual_watch_seconds']);
            $this->assertEquals(0, $json['data']['afk_duration_seconds']);
            $this->assertEquals(0.0, (float) $json['data']['learning_hour']);
            $this->assertArrayHasKey('learning_hour', $json['data']);
            $this->assertArrayNotHasKey('learning_hours', $json['data']);
            $this->assertNull($json['data']['completed_at']);

            $pcu->refresh();
            $this->assertEquals(0, (int) $pcu->progress);
            $this->assertEquals('0', (string) $pcu->finish);
            $this->assertEquals('not_started', $pcu->learning_status);
            $this->assertEquals(0, (int) $pcu->current_time);
            $this->assertEquals(0, (int) $pcu->actual_watch_seconds);
            $this->assertEquals(0.0, (float) $pcu->learning_hour);
            $this->assertNull($pcu->completed_at);
        } finally {
            $pcu->forceFill($original)->saveQuietly();
        }
    }

    public function test_resetting_playlist_module_updates_canonical_playlist_completion(): void
    {
        $admin = User::where('role', '2')->first() ?? User::first();
        $targetUser = User::where('role', '!=', '2')->first() ?? $admin;
        $classification = Classification::first();

        $plan = TrainingPlan::create([
            'title' => 'Test Reset Playlist Plan',
            'slug' => 'test-reset-playlist-plan-' . rand(1000, 9999),
            'active' => 'Active',
        ]);
        $c = Content::create([
            'title' => 'Test Reset Content Item',
            'slug' => 'test-reset-content-item-' . rand(1000, 9999),
            'type' => 'Video',
            'classification_id' => $classification ? $classification->id : 1,
            'active' => 'Active',
            'playlistonly' => 'Y',
        ]);
        $detail = TrainingPlanDetail::create([
            'trainingplan_id' => $plan->id,
            'content_id' => $c->id,
            'content_order' => 0,
        ]);

        $pcu = PlaylistContentUser::create([
            'user_id' => $targetUser->id,
            'trainingplan_id' => $plan->id,
            'content_id' => $c->id,
            'progress' => 100,
            'finish' => '1',
            'learning_status' => 'completed',
            'completed_at' => now(),
            'actual_watch_seconds' => 120,
            'current_time' => 120,
        ]);

        try {
            $completedService = app(CompletedPlaylistService::class);

            // 1. Initial State: The playlist is completed
            $completedBefore = $completedService->forUser($targetUser->id);
            $this->assertTrue($completedBefore->contains('id', $plan->id), 'Playlist should be recognized as completed prior to reset');

            // 2. Perform Reset on the module
            $response = $this->actingAs($admin)->postJson("/manage/learning/record/playlist/{$pcu->id}/reset");
            $response->assertStatus(200);

            // 3. Post-Reset State: The module is reset to progress 0 and finish 0
            $pcu->refresh();
            $this->assertEquals(0, (int) $pcu->progress);
            $this->assertEquals('0', (string) $pcu->finish);

            // 4. CompletedPlaylistService dynamically reflects that the playlist is no longer complete!
            $completedAfter = $completedService->forUser($targetUser->id);
            $this->assertFalse($completedAfter->contains('id', $plan->id), 'Playlist must no longer be completed after resetting module progress');
        } finally {
            $pcu->delete();
            $detail->delete();
            $c->delete();
            $plan->delete();
        }
    }

    public function test_invalid_source_type_rejected_on_reset(): void
    {
        $user = User::where('role', '2')->first() ?? User::first();

        $response = $this->actingAs($user)->postJson('/manage/learning/record/unknown_source/1/reset');
        $response->assertStatus(404);
        $response->assertJson([
            'status' => 'error',
            'message' => 'Invalid learning source type.',
        ]);
    }

    public function test_unauthorized_user_cannot_reset(): void
    {
        $nonAdmin = User::where('role', '!=', '2')->first();
        $cu = ContentUser::first();
        $this->assertNotNull($cu, 'ContentUser record must exist');

        // Unauthenticated request
        $unauthResponse = $this->postJson("/manage/learning/record/content/{$cu->id}/reset");
        $this->assertContains($unauthResponse->getStatusCode(), [302, 401, 403]);

        // Non-admin request
        if ($nonAdmin) {
            $nonAdminResponse = $this->actingAs($nonAdmin)->postJson("/manage/learning/record/content/{$cu->id}/reset");
            $this->assertContains($nonAdminResponse->getStatusCode(), [302, 403]);
        }
    }

    public function test_nonexistent_record_returns_404_on_reset(): void
    {
        $user = User::where('role', '2')->first() ?? User::first();

        $responseContent = $this->actingAs($user)->postJson('/manage/learning/record/content/999999999/reset');
        $responseContent->assertStatus(404);
        $responseContent->assertJson([
            'status' => 'error',
            'message' => 'Learning record not found.',
        ]);

        $responsePlaylist = $this->actingAs($user)->postJson('/manage/learning/record/playlist/999999999/reset');
        $responsePlaylist->assertStatus(404);
        $responsePlaylist->assertJson([
            'status' => 'error',
            'message' => 'Playlist learning record not found.',
        ]);
    }

    public function test_resetting_already_reset_record_remains_valid(): void
    {
        $user = User::where('role', '2')->first() ?? User::first();
        $cu = ContentUser::first();
        $this->assertNotNull($cu, 'ContentUser record must exist');
        $original = $cu->only(['progress', 'current_time', 'actual_watch_seconds', 'afk_duration_seconds', 'finish', 'completed_at', 'learning_status', 'learning_hours', 'last_page']);

        try {
            // First reset
            $firstResponse = $this->actingAs($user)->postJson("/manage/learning/record/content/{$cu->id}/reset");
            $firstResponse->assertStatus(200);

            // Second reset (idempotency check)
            $secondResponse = $this->actingAs($user)->postJson("/manage/learning/record/content/{$cu->id}/reset");
            $secondResponse->assertStatus(200);

            $json = $secondResponse->json();
            $this->assertEquals('success', $json['status']);
            $this->assertEquals(0, $json['data']['progress']);
            $this->assertEquals(0, $json['data']['finish']);
            $this->assertEquals(0.0, (float) $json['data']['learning_hours']);

            $cu->refresh();
            $this->assertEquals(0, (int) $cu->progress);
            $this->assertEquals('0', (string) $cu->finish);
        } finally {
            $cu->forceFill($original)->saveQuietly();
        }
    }

    public function test_reset_does_not_delete_assessment_results_or_certificates(): void
    {
        $user = User::where('role', '2')->first() ?? User::first();
        $cu = ContentUser::first();
        $this->assertNotNull($cu, 'ContentUser record must exist');
        $original = $cu->only(['progress', 'current_time', 'actual_watch_seconds', 'afk_duration_seconds', 'finish', 'completed_at', 'learning_status', 'learning_hours', 'last_page']);

        // Check or create assessment result
        $ar = AssesmentResult::first() ?? AssesmentResult::create([
            'user_id' => $cu->user_id,
            'assesment_id' => 1,
            'score' => 85,
            'pass' => 1,
            'final' => 1,
            'batch' => 1,
            'user_answer' => ['1' => 'A'],
        ]);

        // Check or create certificate
        $cert = Certificate::first() ?? Certificate::create([
            'user_id' => $cu->user_id,
            'credential_id' => 'TEST-CRED-' . $cu->user_id,
            'title' => 'Test Safety Certificate',
            'participant_name' => 'Test Participant',
            'content_user_id' => $cu->id,
            'score' => 85,
        ]);

        try {
            $arCountBefore = AssesmentResult::where('id', $ar->id)->count();
            $certCountBefore = Certificate::where('id', $cert->id)->count();
            $cuCountBefore = ContentUser::where('id', $cu->id)->count();

            $this->assertEquals(1, $arCountBefore);
            $this->assertEquals(1, $certCountBefore);
            $this->assertEquals(1, $cuCountBefore);

            // Execute reset
            $response = $this->actingAs($user)->postJson("/manage/learning/record/content/{$cu->id}/reset");
            $response->assertStatus(200);

            // Assert that NO rows were deleted!
            $this->assertEquals(1, ContentUser::where('id', $cu->id)->count(), 'ContentUser enrollment record must NOT be deleted');
            $this->assertEquals(1, AssesmentResult::where('id', $ar->id)->count(), 'AssesmentResult must NOT be deleted');
            $this->assertEquals(1, Certificate::where('id', $cert->id)->count(), 'Certificate must NOT be deleted');
        } finally {
            $cu->forceFill($original)->saveQuietly();
        }
    }

    public function test_basic_common_duties_category_page_loads_successfully(): void
    {
        $user = User::where('role', '2')->first() ?? User::first();
        $classification = Classification::where('slug', 'basic-common-duties')->first();

        if ($classification) {
            $response = $this->actingAs($user)->get('/manage/learning/category/basic-common-duties');
            $response->assertStatus(200);
            $response->assertViewIs('main.learning.all');
            $response->assertSee('Classification: ' . $classification->classification_name);
            $response->assertSee('id="classification-slug"', false);
            $response->assertSee('value="basic-common-duties"', false);
        }
    }

    public function test_basic_common_duties_datatable_ajax_returns_valid_json(): void
    {
        $user = User::where('role', '2')->first() ?? User::first();

        $response = $this->actingAs($user)->getJson('/manage/learning/datatable?classification_slug=basic-common-duties&draw=1&start=0&length=10');
        $response->assertStatus(200);
        $response->assertJsonStructure([
            'draw',
            'recordsTotal',
            'recordsFiltered',
            'data',
        ]);

        $json = $response->json();
        $this->assertEquals(1, $json['draw']);
        $this->assertIsArray($json['data']);

        foreach ($json['data'] as $item) {
            $this->assertStringContainsString('Basic &amp; Common Duties', $item['classification_name']);
        }
    }

    public function test_basic_common_duties_datatable_search_and_sorting(): void
    {
        $user = User::where('role', '2')->first() ?? User::first();

        // Search test
        $response = $this->actingAs($user)->getJson('/manage/learning/datatable?classification_slug=basic-common-duties&draw=2&start=0&length=10&search[value]=nonexistent_keyword_12345');
        $response->assertStatus(200);
        $json = $response->json();
        $this->assertEquals(0, $json['recordsFiltered']);
        $this->assertEmpty($json['data']);

        // Sorting test (asc by date)
        $sortAsc = $this->actingAs($user)->getJson('/manage/learning/datatable?classification_slug=basic-common-duties&draw=3&start=0&length=5&sort=oldest');
        $sortAsc->assertStatus(200);
        $jsonAsc = $sortAsc->json();
        $this->assertIsArray($jsonAsc['data']);

        // Sorting test (desc by date)
        $sortDesc = $this->actingAs($user)->getJson('/manage/learning/datatable?classification_slug=basic-common-duties&draw=4&start=0&length=5&sort=newest');
        $sortDesc->assertStatus(200);
        $jsonDesc = $sortDesc->json();
        $this->assertIsArray($jsonDesc['data']);
    }

    public function test_basic_common_duties_datatable_empty_dataset_returns_no_error(): void
    {
        $user = User::where('role', '2')->first() ?? User::first();

        // Query with impossible progress filter + search to guarantee empty dataset
        $response = $this->actingAs($user)->getJson('/manage/learning/datatable?classification_slug=basic-common-duties&draw=5&start=0&length=10&progress_range=100&status=not_started&search[value]=impossible_query_xyz');
        $response->assertStatus(200);
        $json = $response->json();
        $this->assertEquals(5, $json['draw']);
        $this->assertEquals(0, $json['recordsFiltered']);
        $this->assertIsArray($json['data']);
        $this->assertCount(0, $json['data']);
    }

    public function test_basic_common_duties_export_excel_pdf_csv(): void
    {
        $user = User::where('role', '2')->first() ?? User::first();

        // Excel download
        $excelResponse = $this->actingAs($user)->get('/manage/learning/download?classification_slug=basic-common-duties&type=category&slug=basic-common-duties');
        $this->assertContains($excelResponse->getStatusCode(), [200, 302]);

        // PDF download
        $pdfResponse = $this->actingAs($user)->get('/manage/learning/download/pdf?classification_slug=basic-common-duties&type=category&slug=basic-common-duties');
        $pdfResponse->assertStatus(200);

        // CSV download
        $csvResponse = $this->actingAs($user)->get('/manage/learning/download/csv?classification_slug=basic-common-duties&type=category&slug=basic-common-duties');
        $csvResponse->assertStatus(200);
    }

    public function test_playlist_display_refactor_in_datatable_and_category_column(): void
    {
        $user = User::where('role', '2')->first() ?? User::first();

        // 1. Fetch playlist items from datatable
        $response = $this->actingAs($user)->getJson('/manage/learning/datatable?draw=1&start=0&length=20&category=playlist');
        $response->assertStatus(200);

        $json = $response->json();
        $this->assertIsArray($json['data']);

        if (!empty($json['data'])) {
            $firstRow = $json['data'][0];

            // Category badge should contain inline "Playlist:" label and clickable link without green badge
            $this->assertStringContainsString('Playlist:', $firstRow['category_badge']);
            $this->assertStringContainsString('playlist-category', $firstRow['category_badge']);
            $this->assertStringContainsString('playlist-cell-link', $firstRow['category_badge']);
            $this->assertStringContainsString('/manage/learning/playlist/', $firstRow['category_badge']);
            $this->assertStringNotContainsString('bg-green-lt', $firstRow['category_badge']);

            // Learning title MUST NOT contain the playlist subtext
            $this->assertStringNotContainsString('Playlist:', $firstRow['learning_title']);
            $this->assertStringNotContainsString('ti-list-details', $firstRow['learning_title']);
        }

        // 2. Fetch non-playlist item (All Content) and verify it preserves existing badge
        $allContentResponse = $this->actingAs($user)->getJson('/manage/learning/datatable?draw=1&start=0&length=10&category=all_content');
        $allContentResponse->assertStatus(200);
        $allContentJson = $allContentResponse->json();
        if (!empty($allContentJson['data'])) {
            $acRow = $allContentJson['data'][0];
            $this->assertStringContainsString('All Content', $acRow['category_badge']);
            $this->assertStringContainsString('bg-azure-lt', $acRow['category_badge']);
            $this->assertStringNotContainsString('playlist-category', $acRow['category_badge']);
        }
    }

    public function test_playlist_detail_page_global_learning_records(): void
    {
        $user = User::where('role', '2')->first() ?? User::first();
        $playlist = \App\Models\TrainingPlan::first();

        if ($playlist) {
            // A. Global Playlist Learning Records View
            $globalResponse = $this->actingAs($user)->get('/manage/learning/playlist/' . $playlist->id);
            $globalResponse->assertStatus(200);
            $globalResponse->assertViewIs('main.learning.all');
            $globalResponse->assertSee('Playlist: ' . $playlist->title);
            $globalResponse->assertSee('learning-table');
            $globalResponse->assertSee('playlist_id');

            // B. DataTables AJAX query scoped by playlist_id
            $dtResponse = $this->actingAs($user)->getJson('/manage/learning/datatable?draw=1&start=0&length=10&playlist_id=' . $playlist->id);
            $dtResponse->assertStatus(200);
            $dtJson = $dtResponse->json();
            $this->assertArrayHasKey('draw', $dtJson);
            $this->assertArrayHasKey('recordsTotal', $dtJson);
            $this->assertArrayHasKey('data', $dtJson);

            if (!empty($dtJson['data'])) {
                $row = $dtJson['data'][0];
                $this->assertStringContainsString('Playlist:', $row['category_badge']);
                $this->assertStringContainsString('/manage/learning/playlist/' . $playlist->id, $row['category_badge']);
                $this->assertStringNotContainsString('user_id=', $row['category_badge']);
                $this->assertStringNotContainsString('category_slug=', $row['category_badge']);
            }

            // C. Title autocomplete scoped by playlist_id
            $titleResponse = $this->actingAs($user)->getJson('/manage/learning/search-titles?playlist_id=' . $playlist->id);
            $titleResponse->assertStatus(200);

            // D. Exports for playlist
            $pdfResponse = $this->actingAs($user)->get('/manage/learning/download/pdf?playlist_id=' . $playlist->id);
            $pdfResponse->assertStatus(200);

            $csvResponse = $this->actingAs($user)->get('/manage/learning/download/csv?playlist_id=' . $playlist->id);
            $csvResponse->assertStatus(200);

            // E. 404 for invalid playlist id
            $notFoundResponse = $this->actingAs($user)->get('/manage/learning/playlist/99999999');
            $notFoundResponse->assertStatus(404);
        }
    }
}

