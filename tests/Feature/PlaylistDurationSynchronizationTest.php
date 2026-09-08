<?php

namespace Tests\Feature;

use App\Models\Classification;
use App\Models\Content;
use App\Models\RoleAccessPermission;
use App\Models\TrainingPlan;
use App\Models\TrainingPlanDetail;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class PlaylistDurationSynchronizationTest extends TestCase
{
    use DatabaseTransactions;

    protected User $adminUser;
    protected User $unauthorizedUser;
    protected Classification $classification;

    protected function setUp(): void
    {
        parent::setUp();

        $this->classification = Classification::firstOrCreate(
            ['slug' => 'testing-classification-playlist-duration'],
            ['classification_name' => 'Testing Classification Playlist Duration']
        );

        $this->adminUser = User::factory()->create([
            'role'       => User::ROLE_SUPER_ADMIN,
            'team'       => 'Quality Assurance',
            'department' => 'Technology',
            'division'   => 'Engineering',
            'ssohash'    => 'test-superadmin-duration-' . \Illuminate\Support\Str::uuid(),
            'sap_code'   => 'SAP-SUPERADMIN-PD',
        ]);

        $this->unauthorizedUser = User::factory()->create([
            'role'       => User::ROLE_USER,
            'team'       => 'Operations',
            'department' => 'Manufacturing',
            'division'   => 'Production',
            'ssohash'    => 'test-user-noperm-' . \Illuminate\Support\Str::uuid(),
            'sap_code'   => 'SAP-NOPERM-PD',
        ]);

        RoleAccessPermission::updateOrCreate(
            ['role' => User::ROLE_SUPER_ADMIN, 'module_key' => 'playlist_learning_path'],
            ['can_access' => true]
        );

        RoleAccessPermission::updateOrCreate(
            ['role' => User::ROLE_USER, 'module_key' => 'playlist_learning_path'],
            ['can_access' => false]
        );
    }

    /**
     * Test 1 — Unauthenticated user cannot access duration synchronization endpoint.
     */
    public function test_unauthenticated_user_cannot_access_synchronization_endpoint(): void
    {
        $response = $this->postJson(route('manage.playlist.synchronize-durations'));

        $this->assertTrue(in_array($response->status(), [401, 302], true));
    }

    /**
     * Test 2 — User without playlist_learning_path permission is blocked with 403.
     */
    public function test_user_without_permission_cannot_access_synchronization_endpoint(): void
    {
        $response = $this->actingAs($this->unauthorizedUser)
            ->postJson(route('manage.playlist.synchronize-durations'));

        $this->assertTrue(in_array($response->status(), [302, 403], true));
    }

    /**
     * Test 3 — Authorized user can access synchronization endpoint.
     */
    public function test_authorized_user_can_access_synchronization_endpoint(): void
    {
        $response = $this->actingAs($this->adminUser)
            ->postJson(route('manage.playlist.synchronize-durations'));

        $response->assertStatus(200);
        $response->assertJsonStructure([
            'success',
            'message',
            'data' => [
                'total_playlists',
                'updated',
                'unchanged',
                'total_materials',
                'durations',
            ],
        ]);
    }

    /**
     * Test 4 — Single playlist calculation updates stored duration seconds accurately.
     */
    public function test_single_playlist_duration_calculation(): void
    {
        $c1 = Content::create([
            'title'             => 'Content Video Single',
            'type'              => 'Video',
            'duration'          => '00:15:30',
            'active'            => 'Active',
            'classification_id' => $this->classification->id,
        ]);

        $plan = TrainingPlan::create([
            'title'                  => 'Single Plan Duration Test',
            'total_duration_seconds' => 0,
            'active'                 => 'Active',
        ]);

        TrainingPlanDetail::create([
            'trainingplan_id' => $plan->id,
            'content_id'      => $c1->id,
            'content_order'   => 0,
        ]);

        // Reset total_duration_seconds via DB query to bypass model hook and simulate outdated state
        DB::table('trainingplan')->where('id', $plan->id)->update(['total_duration_seconds' => 0]);

        // 15 min 30 sec = 930 seconds
        $response = $this->actingAs($this->adminUser)
            ->postJson(route('manage.playlist.synchronize-durations'), [
                'id' => $plan->id,
            ]);

        $response->assertStatus(200);
        $response->assertJson([
            'success' => true,
            'data'    => [
                'total_playlists' => 1,
                'updated'         => 1,
                'unchanged'       => 0,
                'total_materials' => 1,
            ],
        ]);

        $plan->refresh();
        $this->assertSame(930, $plan->total_duration_seconds);
    }

    /**
     * Test 5 — Multi-content calculation:
     * 00:10:00 (600s) + 00:05:30 (330s) + 00:20:00 (1200s) = 2130 seconds ("00:35:30")
     */
    public function test_multi_content_calculation_formula(): void
    {
        $c1 = Content::create(['title' => 'Formula C1', 'type' => 'Video', 'duration' => '00:10:00', 'active' => 'Active', 'classification_id' => $this->classification->id]);
        $c2 = Content::create(['title' => 'Formula C2', 'type' => 'Document', 'duration' => '00:05:30', 'active' => 'Active', 'classification_id' => $this->classification->id]);
        $c3 = Content::create(['title' => 'Formula C3', 'type' => 'YouTube', 'duration' => '00:20:00', 'active' => 'Active', 'classification_id' => $this->classification->id]);

        $plan = TrainingPlan::create([
            'title'                  => 'Formula Verification Plan',
            'total_duration_seconds' => 0,
            'learning_hours'         => 4.5, // should remain untouched
            'active'                 => 'Active',
        ]);

        TrainingPlanDetail::create(['trainingplan_id' => $plan->id, 'content_id' => $c1->id, 'content_order' => 0]);
        TrainingPlanDetail::create(['trainingplan_id' => $plan->id, 'content_id' => $c2->id, 'content_order' => 1]);
        TrainingPlanDetail::create(['trainingplan_id' => $plan->id, 'content_id' => $c3->id, 'content_order' => 2]);

        // Reset via DB to simulate outdated duration before sync
        DB::table('trainingplan')->where('id', $plan->id)->update(['total_duration_seconds' => 0]);

        $response = $this->actingAs($this->adminUser)
            ->postJson(route('manage.playlist.synchronize-durations'), [
                'ids' => [$plan->id],
            ]);

        $response->assertStatus(200);
        $plan->refresh();

        $this->assertSame(2130, $plan->total_duration_seconds);
        $this->assertEquals(4.5, $plan->learning_hours, 'Learning Hours must remain completely unchanged.');

        $durations = $response->json('data.durations');
        $this->assertNotEmpty($durations);
        $this->assertSame('00:35:30', $durations[0]['new_duration']);
        $this->assertSame(2130, $durations[0]['new_seconds']);
        $this->assertSame(3, $durations[0]['materials_count']);
        $this->assertTrue($durations[0]['updated']);
    }

    /**
     * Test 6 — Empty playlist without materials evaluates to 0 seconds ("00:00:00").
     */
    public function test_empty_playlist_calculation_evaluates_to_zero(): void
    {
        $plan = TrainingPlan::create([
            'title'                  => 'Empty Plan Test',
            'total_duration_seconds' => 500, // old inaccurate value
            'active'                 => 'Active',
        ]);

        $response = $this->actingAs($this->adminUser)
            ->postJson(route('manage.playlist.synchronize-durations'), [
                'id' => $plan->id,
            ]);

        $response->assertStatus(200);
        $plan->refresh();

        $this->assertSame(0, $plan->total_duration_seconds);
        $durations = $response->json('data.durations');
        $this->assertSame('00:00:00', $durations[0]['new_duration']);
        $this->assertSame(0, $durations[0]['materials_count']);
    }

    /**
     * Test 7 — Soft-deleted content is excluded from playlist duration calculation.
     */
    public function test_soft_deleted_content_is_excluded_from_duration(): void
    {
        $c1 = Content::create(['title' => 'Active Content', 'duration' => '00:10:00', 'active' => 'Active', 'classification_id' => $this->classification->id]);
        $c2 = Content::create(['title' => 'Deleted Content', 'duration' => '00:30:00', 'active' => 'Active', 'classification_id' => $this->classification->id]);

        $plan = TrainingPlan::create([
            'title'                  => 'Soft Delete Test Plan',
            'total_duration_seconds' => 0,
            'active'                 => 'Active',
        ]);

        TrainingPlanDetail::create(['trainingplan_id' => $plan->id, 'content_id' => $c1->id, 'content_order' => 0]);
        TrainingPlanDetail::create(['trainingplan_id' => $plan->id, 'content_id' => $c2->id, 'content_order' => 1]);

        // Soft delete c2
        $c2->delete();

        $response = $this->actingAs($this->adminUser)
            ->postJson(route('manage.playlist.synchronize-durations'), [
                'id' => $plan->id,
            ]);

        $response->assertStatus(200);
        $plan->refresh();

        // Only c1 (600s) should be summed; deleted c2 (1800s) ignored
        $this->assertSame(600, $plan->total_duration_seconds);
    }

    /**
     * Test 8 — Content with null or empty duration is handled gracefully without exception.
     */
    public function test_null_or_empty_duration_handled_gracefully(): void
    {
        $c1 = Content::create(['title' => 'C Valid', 'duration' => '00:05:00', 'active' => 'Active', 'classification_id' => $this->classification->id]);
        $c2 = Content::create(['title' => 'C Null Duration', 'duration' => null, 'active' => 'Active', 'classification_id' => $this->classification->id]);
        $c3 = Content::create(['title' => 'C Empty Duration', 'duration' => '', 'active' => 'Active', 'classification_id' => $this->classification->id]);

        $plan = TrainingPlan::create([
            'title'                  => 'Null Duration Test Plan',
            'total_duration_seconds' => 0,
            'active'                 => 'Active',
        ]);

        TrainingPlanDetail::create(['trainingplan_id' => $plan->id, 'content_id' => $c1->id, 'content_order' => 0]);
        TrainingPlanDetail::create(['trainingplan_id' => $plan->id, 'content_id' => $c2->id, 'content_order' => 1]);
        TrainingPlanDetail::create(['trainingplan_id' => $plan->id, 'content_id' => $c3->id, 'content_order' => 2]);

        $response = $this->actingAs($this->adminUser)
            ->postJson(route('manage.playlist.synchronize-durations'), [
                'id' => $plan->id,
            ]);

        $response->assertStatus(200);
        $plan->refresh();

        // Only c1 (300s) counted
        $this->assertSame(300, $plan->total_duration_seconds);
    }

    /**
     * Test 9 — Inactive content is included in playlist duration per existing management behavior.
     */
    public function test_inactive_content_is_included_in_duration(): void
    {
        $cActive = Content::create(['title' => 'Active Content', 'duration' => '00:10:00', 'active' => 'Active', 'classification_id' => $this->classification->id]);
        $cInactive = Content::create(['title' => 'Inactive Content', 'duration' => '00:15:00', 'active' => 'Inactive', 'classification_id' => $this->classification->id]);

        $plan = TrainingPlan::create([
            'title'                  => 'Inactive Inclusion Test Plan',
            'total_duration_seconds' => 0,
            'active'                 => 'Active',
        ]);

        TrainingPlanDetail::create(['trainingplan_id' => $plan->id, 'content_id' => $cActive->id, 'content_order' => 0]);
        TrainingPlanDetail::create(['trainingplan_id' => $plan->id, 'content_id' => $cInactive->id, 'content_order' => 1]);

        $response = $this->actingAs($this->adminUser)
            ->postJson(route('manage.playlist.synchronize-durations'), [
                'id' => $plan->id,
            ]);

        $response->assertStatus(200);
        $plan->refresh();

        // 600s + 900s = 1500s
        $this->assertSame(1500, $plan->total_duration_seconds);
    }

    /**
     * Test 10, 11 & 12 — Idempotence: Second sync run marks all accurate playlists as unchanged.
     */
    public function test_second_synchronization_run_reports_unchanged(): void
    {
        $c1 = Content::create(['title' => 'Idempotence C1', 'duration' => '00:10:00', 'active' => 'Active', 'classification_id' => $this->classification->id]);

        $plan = TrainingPlan::create([
            'title'                  => 'Idempotence Test Plan',
            'total_duration_seconds' => 0,
            'active'                 => 'Active',
        ]);

        TrainingPlanDetail::create(['trainingplan_id' => $plan->id, 'content_id' => $c1->id, 'content_order' => 0]);

        // Reset via DB to simulate outdated duration before first sync
        DB::table('trainingplan')->where('id', $plan->id)->update(['total_duration_seconds' => 0]);

        // First run: updates from 0 -> 600
        $res1 = $this->actingAs($this->adminUser)
            ->postJson(route('manage.playlist.synchronize-durations'), ['id' => $plan->id]);
        $res1->assertStatus(200);
        $this->assertSame(1, $res1->json('data.updated'));
        $this->assertSame(0, $res1->json('data.unchanged'));

        // Second run: already 600, should be unchanged
        $res2 = $this->actingAs($this->adminUser)
            ->postJson(route('manage.playlist.synchronize-durations'), ['id' => $plan->id]);
        $res2->assertStatus(200);
        $this->assertSame(0, $res2->json('data.updated'));
        $this->assertSame(1, $res2->json('data.unchanged'));
    }

    /**
     * Test 13 — Bulk synchronization without parameters processes all playlists.
     */
    public function test_bulk_synchronization_processes_all_playlists(): void
    {
        $totalInDb = TrainingPlan::count();

        $response = $this->actingAs($this->adminUser)
            ->postJson(route('manage.playlist.synchronize-durations'));

        $response->assertStatus(200);
        $this->assertSame($totalInDb, $response->json('data.total_playlists'));
    }

    /**
     * Test 14 — Performance / No N+1: Query count is constant regardless of playlist count.
     */
    public function test_synchronization_does_not_cause_n_plus_one_queries(): void
    {
        DB::enableQueryLog();

        $service = app(\App\Services\PlaylistDurationService::class);
        $service->synchronizeAll();

        $queryCount = count(DB::getQueryLog());
        DB::disableQueryLog();

        // 1 query for TrainingPlan, 1 query for TrainingPlanDetail, 1 query for Content (eager loaded),
        // plus at most update queries for changed plans (which is 0 when all are already synchronized)
        // Total select queries must be <= 5
        $this->assertLessThanOrEqual(5, $queryCount, "Bulk duration synchronization must not execute N+1 select queries. Queries executed: {$queryCount}");
    }

    /**
     * Test 15 — Existing edit per-item sync endpoint continues to work properly.
     */
    public function test_existing_edit_synchronize_learning_hours_continues_to_work(): void
    {
        $c1 = Content::create(['title' => 'Legacy Sync Content', 'duration' => '00:30:00', 'active' => 'Active', 'classification_id' => $this->classification->id]);

        $plan = TrainingPlan::create([
            'title'                  => 'Legacy Sync Plan',
            'total_duration_seconds' => 0,
            'active'                 => 'Active',
        ]);

        TrainingPlanDetail::create(['trainingplan_id' => $plan->id, 'content_id' => $c1->id, 'content_order' => 0]);

        $response = $this->actingAs($this->adminUser)
            ->postJson(route('playlist.synchronize-learning-hours', $plan->slug));

        $response->assertStatus(200);
        $response->assertJson([
            'success'                => true,
            'total_duration_seconds' => 1800,
            'duration'               => '00:30:00',
        ]);

        $plan->refresh();
        $this->assertSame(1800, $plan->total_duration_seconds);
    }
}
