<?php

namespace Tests\Feature;

use App\Models\Content;
use App\Models\ContentUser;
use App\Models\RoleAccessPermission;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class ContentUserLearningHourManagementTest extends TestCase
{
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
    }

    public function test_guest_cannot_access_learning_hours_management(): void
    {
        $response = $this->get('/manage/learning-hours/content-user');
        $response->assertRedirect('/login');
    }

    public function test_regular_user_is_denied_access(): void
    {
        $user = User::where('role', User::ROLE_USER)->first() ?? User::factory()->make(['role' => User::ROLE_USER]);

        $response = $this->actingAs($user)->get('/manage/learning-hours/content-user');
        $response->assertStatus(403);
    }

    public function test_admin_can_view_learning_hours_management_page(): void
    {
        $admin = User::where('role', User::ROLE_ADMIN)->first() ?? User::first();

        $response = $this->actingAs($admin)->get('/manage/learning-hours/content-user');
        $response->assertStatus(200);
        $response->assertViewIs('manage.learning-hours.content-user.index');
        $response->assertSee('Content User Learning Hours');
        $response->assertViewHas('statistics');
        $response->assertViewHas('classifications');
    }

    public function test_datatable_endpoint_returns_valid_server_side_json(): void
    {
        $admin = User::where('role', User::ROLE_ADMIN)->first() ?? User::first();

        $response = $this->actingAs($admin)->getJson('/manage/learning-hours/content-user/datatable?draw=1&start=0&length=10');
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

    public function test_calculate_selected_and_recalculate_selected(): void
    {
        $admin = User::where('role', User::ROLE_ADMIN)->first() ?? User::first();
        $contentUsers = ContentUser::whereHas('content')->take(3)->pluck('id')->toArray();

        $this->assertNotEmpty($contentUsers);

        // Test Calculate Selected
        $response = $this->actingAs($admin)->postJson('/manage/learning-hours/content-user/calculate-selected', [
            'ids' => $contentUsers,
        ]);

        $response->assertStatus(200);
        $response->assertJsonPath('success', true);
        $response->assertJsonStructure(['result' => ['total', 'updated', 'skipped', 'failed']]);

        // Test Recalculate Selected
        $recalcResponse = $this->actingAs($admin)->postJson('/manage/learning-hours/content-user/recalculate-selected', [
            'ids' => $contentUsers,
        ]);

        $recalcResponse->assertStatus(200);
        $recalcResponse->assertJsonPath('success', true);
    }

    public function test_batch_start_and_process_workflow(): void
    {
        $admin = User::where('role', User::ROLE_ADMIN)->first() ?? User::first();

        $startResponse = $this->actingAs($admin)->postJson('/manage/learning-hours/content-user/batch/start', [
            'type' => 'all_remaining',
        ]);

        $startResponse->assertStatus(200);
        $startResponse->assertJsonPath('success', true);
        $batchId = $startResponse->json('batch_id');
        $this->assertNotNull($batchId);

        $processResponse = $this->actingAs($admin)->postJson('/manage/learning-hours/content-user/batch/process', [
            'batch_id' => $batchId,
        ]);

        $processResponse->assertStatus(200);
        $processResponse->assertJsonPath('success', true);
    }

    public function test_manage_dashboard_displays_learning_hour_management_card_for_admin(): void
    {
        $admin = User::where('role', User::ROLE_ADMIN)->first() ?? User::first();

        $response = $this->actingAs($admin)->get('/manage');
        $response->assertStatus(200);
        $response->assertSee('Learning Hour Management');
        $response->assertSee(route('manage.learning-hours.content-user.index'));
    }

    public function test_get_statistics_returns_accurate_metrics_without_sql_error(): void
    {
        $service = app(\App\Services\LearningHours\ContentUserLearningHourManagementService::class);
        $stats = $service->getStatistics();

        $this->assertIsArray($stats);
        $this->assertArrayHasKey('total', $stats);
        $this->assertArrayHasKey('calculated', $stats);
        $this->assertArrayHasKey('remaining', $stats);
        $this->assertArrayHasKey('failed', $stats);
        $this->assertArrayHasKey('percentage', $stats);

        $this->assertGreaterThanOrEqual(0, $stats['total']);
        $this->assertGreaterThanOrEqual(0, $stats['calculated']);
        $this->assertGreaterThanOrEqual(0, $stats['remaining']);
        $this->assertGreaterThanOrEqual(0, $stats['failed']);

        // Verify total equals calculated + remaining + failed
        $this->assertEquals($stats['total'], $stats['calculated'] + $stats['remaining'] + $stats['failed']);

        if ($stats['total'] > 0) {
            $expectedPercentage = round(($stats['calculated'] / $stats['total']) * 100, 1);
            $this->assertEquals($expectedPercentage, $stats['percentage']);
        }
    }

    public function test_migrations_added_learning_hours_and_completed_at_columns(): void
    {
        $this->assertTrue(\Illuminate\Support\Facades\Schema::hasColumn('content_users', 'learning_hours'));
        $this->assertTrue(\Illuminate\Support\Facades\Schema::hasColumn('content_users', 'completed_at'));
    }

    public function test_hybrid_learning_hours_calculation_rules(): void
    {
        $content = Content::whereNotNull('learning_hours')->where('learning_hours', '>', 0)->first();
        $this->assertNotNull($content);

        $user = User::first();
        $masterHours = (float) $content->learning_hours;

        // 1. Completed user gets full master hours and completed_at is set
        $completedCu = new ContentUser([
            'user_id' => $user->id,
            'content_id' => $content->id,
            'progress' => 100,
            'finish' => '1',
        ]);
        $completedCu->save();
        $this->assertEquals($masterHours, (float) $completedCu->learning_hours);
        $this->assertNotNull($completedCu->completed_at);

        // 2. completed_at is immutable once set
        $origCompletedAt = $completedCu->completed_at;
        $completedCu->progress = 100;
        $completedCu->save();
        $this->assertEquals($origCompletedAt->toDateTimeString(), $completedCu->completed_at->toDateTimeString());

        // 3. Partial progress gets proportional hours
        $partialCu = new ContentUser([
            'user_id' => $user->id,
            'content_id' => $content->id,
            'progress' => 50,
            'finish' => '0',
        ]);
        $partialCu->save();
        $expectedPartial = round($masterHours * 0.5, 1);
        $this->assertEquals($expectedPartial, (float) $partialCu->learning_hours);
        $this->assertNull($partialCu->completed_at);

        // 4. Zero progress gets 0.0
        $zeroCu = new ContentUser([
            'user_id' => $user->id,
            'content_id' => $content->id,
            'progress' => 0,
            'finish' => '0',
        ]);
        $zeroCu->save();
        $this->assertEquals(0.0, (float) $zeroCu->learning_hours);
        $this->assertNull($zeroCu->completed_at);

        // Clean up test rows
        $completedCu->delete();
        $partialCu->delete();
        $zeroCu->delete();
    }

    public function test_datatable_filters_by_year_2024_2025_2026(): void
    {
        $admin = User::where('role', User::ROLE_ADMIN)->first() ?? User::first();

        // 1. Filter year 2024
        $resp2024 = $this->actingAs($admin)->getJson('/manage/learning-hours/content-user/datatable?draw=1&start=0&length=10&year=2024');
        $resp2024->assertStatus(200);
        $data2024 = $resp2024->json('data');
        if (!empty($data2024)) {
            $this->assertEquals('2024', $data2024[0]['year']);
        }

        // 2. Filter year 2025
        $resp2025 = $this->actingAs($admin)->getJson('/manage/learning-hours/content-user/datatable?draw=1&start=0&length=10&year=2025');
        $resp2025->assertStatus(200);
        $data2025 = $resp2025->json('data');
        if (!empty($data2025)) {
            $this->assertEquals('2025', $data2025[0]['year']);
        }

        // 3. Filter year 2026
        $resp2026 = $this->actingAs($admin)->getJson('/manage/learning-hours/content-user/datatable?draw=1&start=0&length=10&year=2026');
        $resp2026->assertStatus(200);
        $data2026 = $resp2026->json('data');
        if (!empty($data2026)) {
            $this->assertEquals('2026', $data2026[0]['year']);
        }
    }

    public function test_datatable_sort_and_search_on_learning_hours(): void
    {
        $admin = User::where('role', User::ROLE_ADMIN)->first() ?? User::first();

        // Sorting by learning_hours column
        $sortResp = $this->actingAs($admin)->getJson('/manage/learning-hours/content-user/datatable?draw=1&start=0&length=10&order[0][column]=7&order[0][dir]=desc&columns[7][data]=learning_hours');
        $sortResp->assertStatus(200);
        $this->assertNotEmpty($sortResp->json('data'));

        // Search by learning_hours number
        $searchResp = $this->actingAs($admin)->getJson('/manage/learning-hours/content-user/datatable?draw=1&start=0&length=10&search[value]=0.5');
        $searchResp->assertStatus(200);
        $this->assertNotEmpty($searchResp->json('data'));
    }

    public function test_database_integrity_and_orphan_preservation(): void
    {
        $total = DB::table('content_users')->count();
        $this->assertGreaterThanOrEqual(165768, $total);

        // Orphan count matches expectations (records with soft-deleted content)
        $orphanCount = ContentUser::whereDoesntHave('content')->count();
        $this->assertEquals(36, $orphanCount);

        // All orphans have null learning_hours and null completed_at
        $orphansWithHours = ContentUser::whereDoesntHave('content')->whereNotNull('learning_hours')->count();
        $this->assertEquals(0, $orphansWithHours);
    }

    public function test_playlist_content_user_calculation_and_persistence(): void
    {
        $admin = User::where('role', User::ROLE_ADMIN)->first() ?? User::first();
        $pcu = \App\Models\PlaylistContentUser::where('progress', '>', 0)->first();
        $this->assertNotNull($pcu);

        // Clear learning_hour
        DB::table('playlist_content_users')->where('id', $pcu->id)->update(['learning_hour' => null, 'completed_at' => null]);

        $response = $this->actingAs($admin)->postJson('/manage/learning-hours/content-user/recalculate-selected', [
            'ids' => [$pcu->id],
            'context' => 'playlist',
        ]);

        $response->assertStatus(200);
        $response->assertJsonPath('success', true);

        $fromDb = DB::table('playlist_content_users')->where('id', $pcu->id)->first();
        $this->assertNotNull($fromDb->learning_hour);
        $this->assertGreaterThanOrEqual(0, (float) $fromDb->learning_hour);
        if ((int) $fromDb->progress >= 100 || (string) $fromDb->finish === '1') {
            $this->assertNotNull($fromDb->completed_at);
        }
    }

    public function test_mass_assignment_on_both_models(): void
    {
        // 1. ContentUser allows learning_hours and completed_at
        $cu = new ContentUser();
        $cu->fill([
            'learning_hours' => 2.5,
            'completed_at' => now(),
        ]);
        $this->assertEquals(2.5, (float) $cu->learning_hours);
        $this->assertNotNull($cu->completed_at);

        // 2. PlaylistContentUser allows learning_hour and completed_at
        $pcu = new \App\Models\PlaylistContentUser();
        $pcu->fill([
            'learning_hour' => 1.5,
            'completed_at' => now(),
        ]);
        $this->assertEquals(1.5, (float) $pcu->learning_hour);
        $this->assertNotNull($pcu->completed_at);
    }

    public function test_datatable_handles_playlist_source(): void
    {
        $admin = User::where('role', User::ROLE_ADMIN)->first() ?? User::first();

        $response = $this->actingAs($admin)->getJson('/manage/learning-hours/content-user/datatable?draw=1&start=0&length=5&source=playlist');
        $response->assertStatus(200);
        $this->assertArrayHasKey('recordsTotal', $response->json());
        $this->assertGreaterThanOrEqual(244520, $response->json('recordsTotal'));
    }
}
