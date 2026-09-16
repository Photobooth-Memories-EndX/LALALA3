<?php

namespace Tests\Feature;

use App\Models\Classification;
use App\Models\Content;
use App\Models\ContentUser;
use App\Models\PlaylistContentUser;
use App\Models\TrainingPlan;
use App\Models\TrainingPlanDetail;
use App\Models\User;
use App\Services\Learning\CompletedPlaylistService;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class CompletedPlaylistServiceResolverTest extends TestCase
{
    use DatabaseTransactions;

    protected User $userA;
    protected User $userB;
    protected Classification $classification;
    protected CompletedPlaylistService $service;

    protected function setUp(): void
    {
        parent::setUp();

        $this->service = app(CompletedPlaylistService::class);

        $this->userA = User::factory()->create([
            'team'       => 'Engineering',
            'department' => 'Technology',
            'division'   => 'LMS',
            'ssohash'    => 'test-hash-comp-a-' . \Illuminate\Support\Str::uuid(),
            'sap_code'   => 'SAP-COMP-A-' . rand(1000, 9999),
            'role'       => 1,
            'active'     => 1,
        ]);

        $this->userB = User::factory()->create([
            'team'       => 'Engineering',
            'department' => 'Technology',
            'division'   => 'LMS',
            'ssohash'    => 'test-hash-comp-b-' . \Illuminate\Support\Str::uuid(),
            'sap_code'   => 'SAP-COMP-B-' . rand(1000, 9999),
            'role'       => 1,
            'active'     => 1,
        ]);

        $this->classification = Classification::firstOrCreate(
            ['classification_name' => 'Completed Test Classification'],
            ['slug' => 'completed-test-classification']
        );
    }

    /**
     * TEST 1: Historical Category A completed content is recognized
     */
    public function test_1_historical_category_a_completed_content_is_recognized(): void
    {
        $plan = TrainingPlan::create(['title' => 'Comp Hist Plan', 'slug' => 'comp-hist-plan-' . rand(1000, 9999), 'active' => 'Active']);
        $c = Content::create(['title' => 'Comp Hist Content', 'slug' => 'comp-hist-c-' . rand(1000, 9999), 'type' => 'Video', 'classification_id' => $this->classification->id, 'active' => 'Active', 'playlistonly' => 'Y']);
        TrainingPlanDetail::create(['trainingplan_id' => $plan->id, 'content_id' => $c->id, 'content_order' => 0]);

        // Playlist completion in playlist_content_users
        PlaylistContentUser::create([
            'user_id'         => $this->userA->id,
            'trainingplan_id' => $plan->id,
            'content_id'      => $c->id,
            'progress'        => 100,
            'finish'          => '1',
            'learning_status' => 'completed',
        ]);

        $completed = $this->service->forUser($this->userA->id);
        $found = $completed->firstWhere('id', $plan->id);

        $this->assertNotNull($found);
        $this->assertEquals(1, (int) $found->total_items);
        $this->assertEquals(1, (int) $found->finished_items);
        $this->assertEquals(100, (int) $found->progress_percent);
        $this->assertTrue($found->is_completed);
    }

    /**
     * TEST 2: Existing PCU overrides historical content_users (CU = 100%, PCU = 50% -> incomplete)
     */
    public function test_2_existing_pcu_overrides_historical_content_users(): void
    {
        $plan = TrainingPlan::create(['title' => 'PCU Over Plan', 'slug' => 'pcu-over-comp-' . rand(1000, 9999), 'active' => 'Active']);
        $c = Content::create(['title' => 'PCU Over Content', 'slug' => 'pcu-over-comp-c-' . rand(1000, 9999), 'type' => 'Video', 'classification_id' => $this->classification->id, 'active' => 'Active', 'playlistonly' => 'Y']);
        TrainingPlanDetail::create(['trainingplan_id' => $plan->id, 'content_id' => $c->id, 'content_order' => 0]);

        // Historical 100% in content_users
        ContentUser::create(['user_id' => $this->userA->id, 'content_id' => $c->id, 'progress' => 100, 'finish' => '1']);

        // Authoritative 50% in PCU
        PlaylistContentUser::create(['user_id' => $this->userA->id, 'trainingplan_id' => $plan->id, 'content_id' => $c->id, 'progress' => 50, 'finish' => '0']);

        $completed = $this->service->forUser($this->userA->id);
        $this->assertFalse($completed->contains('id', $plan->id));
    }

    /**
     * TEST 3: Completed PCU overrides historical content_users (CU = 20%, PCU = 100% -> completed)
     */
    public function test_3_completed_pcu_overrides_historical_content_users(): void
    {
        $plan = TrainingPlan::create(['title' => 'Comp PCU Win Plan', 'slug' => 'comp-pcu-win-' . rand(1000, 9999), 'active' => 'Active']);
        $c = Content::create(['title' => 'Comp PCU Win Content', 'slug' => 'comp-pcu-win-c-' . rand(1000, 9999), 'type' => 'Video', 'classification_id' => $this->classification->id, 'active' => 'Active', 'playlistonly' => 'Y']);
        TrainingPlanDetail::create(['trainingplan_id' => $plan->id, 'content_id' => $c->id, 'content_order' => 0]);

        // Historical 20% in CU
        ContentUser::create(['user_id' => $this->userA->id, 'content_id' => $c->id, 'progress' => 20, 'finish' => '0']);

        // Authoritative 100% in PCU
        PlaylistContentUser::create(['user_id' => $this->userA->id, 'trainingplan_id' => $plan->id, 'content_id' => $c->id, 'progress' => 100, 'finish' => '1']);

        $completed = $this->service->forUser($this->userA->id);
        $this->assertTrue($completed->contains('id', $plan->id));
    }

    /**
     * TEST 4: Multi-playlist historical progress is not blindly attributed
     */
    public function test_4_multi_playlist_historical_progress_not_blindly_attributed(): void
    {
        $planA = TrainingPlan::create(['title' => 'Comp Amb Plan A', 'slug' => 'comp-amb-a-' . rand(1000, 9999), 'active' => 'Active']);
        $planB = TrainingPlan::create(['title' => 'Comp Amb Plan B', 'slug' => 'comp-amb-b-' . rand(1000, 9999), 'active' => 'Active']);

        $c = Content::create(['title' => 'Comp Amb Content', 'slug' => 'comp-amb-c-' . rand(1000, 9999), 'type' => 'Video', 'classification_id' => $this->classification->id, 'active' => 'Active', 'playlistonly' => 'Y']);
        TrainingPlanDetail::create(['trainingplan_id' => $planA->id, 'content_id' => $c->id, 'content_order' => 0]);
        TrainingPlanDetail::create(['trainingplan_id' => $planB->id, 'content_id' => $c->id, 'content_order' => 0]);

        // Historical 100% in CU
        ContentUser::create(['user_id' => $this->userA->id, 'content_id' => $c->id, 'progress' => 100, 'finish' => '1']);

        $completed = $this->service->forUser($this->userA->id);
        $this->assertFalse($completed->contains('id', $planA->id));
        $this->assertFalse($completed->contains('id', $planB->id));
    }

    /**
     * TEST 5: Playlist A and Playlist B are isolated
     */
    public function test_5_playlist_isolation(): void
    {
        $planA = TrainingPlan::create(['title' => 'Comp Iso Plan A', 'slug' => 'comp-iso-a-' . rand(1000, 9999), 'active' => 'Active']);
        $planB = TrainingPlan::create(['title' => 'Comp Iso Plan B', 'slug' => 'comp-iso-b-' . rand(1000, 9999), 'active' => 'Active']);

        $c = Content::create(['title' => 'Comp Iso Content', 'slug' => 'comp-iso-c-' . rand(1000, 9999), 'type' => 'Video', 'classification_id' => $this->classification->id, 'active' => 'Active', 'playlistonly' => 'Y']);
        TrainingPlanDetail::create(['trainingplan_id' => $planA->id, 'content_id' => $c->id, 'content_order' => 0]);
        TrainingPlanDetail::create(['trainingplan_id' => $planB->id, 'content_id' => $c->id, 'content_order' => 0]);

        // User A completes in Plan A (100%), but only 50% in Plan B
        PlaylistContentUser::create(['user_id' => $this->userA->id, 'trainingplan_id' => $planA->id, 'content_id' => $c->id, 'progress' => 100, 'finish' => '1']);
        PlaylistContentUser::create(['user_id' => $this->userA->id, 'trainingplan_id' => $planB->id, 'content_id' => $c->id, 'progress' => 50, 'finish' => '0']);

        $completed = $this->service->forUser($this->userA->id);
        $this->assertTrue($completed->contains('id', $planA->id));
        $this->assertFalse($completed->contains('id', $planB->id));
    }

    /**
     * TEST 6: Different users are isolated
     */
    public function test_6_user_isolation(): void
    {
        $plan = TrainingPlan::create(['title' => 'Comp User Iso Plan', 'slug' => 'comp-u-iso-' . rand(1000, 9999), 'active' => 'Active']);
        $c = Content::create(['title' => 'Comp User Iso Content', 'slug' => 'comp-u-iso-c-' . rand(1000, 9999), 'type' => 'Video', 'classification_id' => $this->classification->id, 'active' => 'Active', 'playlistonly' => 'Y']);
        TrainingPlanDetail::create(['trainingplan_id' => $plan->id, 'content_id' => $c->id, 'content_order' => 0]);

        // User A completes
        PlaylistContentUser::create(['user_id' => $this->userA->id, 'trainingplan_id' => $plan->id, 'content_id' => $c->id, 'progress' => 100, 'finish' => '1']);

        $compA = $this->service->forUser($this->userA->id);
        $compB = $this->service->forUser($this->userB->id);

        $this->assertTrue($compA->contains('id', $plan->id));
        $this->assertFalse($compB->contains('id', $plan->id));
    }

    /**
     * TEST 7: Partial playlist is not Completed (2 of 3 finished -> not completed)
     */
    public function test_7_partial_playlist_is_not_completed(): void
    {
        $plan = TrainingPlan::create(['title' => 'Partial Plan', 'slug' => 'partial-plan-' . rand(1000, 9999), 'active' => 'Active']);
        $c1 = Content::create(['title' => 'Part C1', 'slug' => 'part-c1-' . rand(1000, 9999), 'type' => 'Video', 'classification_id' => $this->classification->id, 'active' => 'Active', 'playlistonly' => 'Y']);
        $c2 = Content::create(['title' => 'Part C2', 'slug' => 'part-c2-' . rand(1000, 9999), 'type' => 'Video', 'classification_id' => $this->classification->id, 'active' => 'Active', 'playlistonly' => 'Y']);
        $c3 = Content::create(['title' => 'Part C3', 'slug' => 'part-c3-' . rand(1000, 9999), 'type' => 'Video', 'classification_id' => $this->classification->id, 'active' => 'Active', 'playlistonly' => 'Y']);
        TrainingPlanDetail::create(['trainingplan_id' => $plan->id, 'content_id' => $c1->id, 'content_order' => 0]);
        TrainingPlanDetail::create(['trainingplan_id' => $plan->id, 'content_id' => $c2->id, 'content_order' => 1]);
        TrainingPlanDetail::create(['trainingplan_id' => $plan->id, 'content_id' => $c3->id, 'content_order' => 2]);

        PlaylistContentUser::create(['user_id' => $this->userA->id, 'trainingplan_id' => $plan->id, 'content_id' => $c1->id, 'progress' => 100, 'finish' => '1']);
        PlaylistContentUser::create(['user_id' => $this->userA->id, 'trainingplan_id' => $plan->id, 'content_id' => $c2->id, 'progress' => 100, 'finish' => '1']);
        PlaylistContentUser::create(['user_id' => $this->userA->id, 'trainingplan_id' => $plan->id, 'content_id' => $c3->id, 'progress' => 50, 'finish' => '0']);

        $completed = $this->service->forUser($this->userA->id);
        $this->assertFalse($completed->contains('id', $plan->id));
    }

    /**
     * TEST 8: All required contents completed -> Completed (3 of 3 finished -> completed)
     */
    public function test_8_all_required_contents_completed(): void
    {
        $plan = TrainingPlan::create(['title' => 'All Comp Plan', 'slug' => 'all-comp-plan-' . rand(1000, 9999), 'active' => 'Active']);
        $c1 = Content::create(['title' => 'All C1', 'slug' => 'all-c1-' . rand(1000, 9999), 'type' => 'Video', 'classification_id' => $this->classification->id, 'active' => 'Active', 'playlistonly' => 'Y']);
        $c2 = Content::create(['title' => 'All C2', 'slug' => 'all-c2-' . rand(1000, 9999), 'type' => 'Video', 'classification_id' => $this->classification->id, 'active' => 'Active', 'playlistonly' => 'Y']);
        $c3 = Content::create(['title' => 'All C3', 'slug' => 'all-c3-' . rand(1000, 9999), 'type' => 'Video', 'classification_id' => $this->classification->id, 'active' => 'Active', 'playlistonly' => 'Y']);
        TrainingPlanDetail::create(['trainingplan_id' => $plan->id, 'content_id' => $c1->id, 'content_order' => 0]);
        TrainingPlanDetail::create(['trainingplan_id' => $plan->id, 'content_id' => $c2->id, 'content_order' => 1]);
        TrainingPlanDetail::create(['trainingplan_id' => $plan->id, 'content_id' => $c3->id, 'content_order' => 2]);

        PlaylistContentUser::create(['user_id' => $this->userA->id, 'trainingplan_id' => $plan->id, 'content_id' => $c1->id, 'progress' => 100, 'finish' => '1']);
        PlaylistContentUser::create(['user_id' => $this->userA->id, 'trainingplan_id' => $plan->id, 'content_id' => $c2->id, 'progress' => 100, 'finish' => '1']);
        PlaylistContentUser::create(['user_id' => $this->userA->id, 'trainingplan_id' => $plan->id, 'content_id' => $c3->id, 'progress' => 100, 'finish' => '1']);

        $completed = $this->service->forUser($this->userA->id);
        $found = $completed->firstWhere('id', $plan->id);

        $this->assertNotNull($found);
        $this->assertEquals(3, (int) $found->total_items);
        $this->assertEquals(3, (int) $found->finished_items);
        $this->assertEquals(100, (int) $found->progress_percent);
        $this->assertTrue($found->is_completed);
    }

    /**
     * TEST 9: Zero/no-progress content prevents completion
     */
    public function test_9_zero_progress_content_prevents_completion(): void
    {
        $plan = TrainingPlan::create(['title' => 'Zero Comp Plan', 'slug' => 'zero-comp-plan-' . rand(1000, 9999), 'active' => 'Active']);
        $c1 = Content::create(['title' => 'Zero C1', 'slug' => 'zero-c1-' . rand(1000, 9999), 'type' => 'Video', 'classification_id' => $this->classification->id, 'active' => 'Active', 'playlistonly' => 'Y']);
        $c2 = Content::create(['title' => 'Zero C2', 'slug' => 'zero-c2-' . rand(1000, 9999), 'type' => 'Video', 'classification_id' => $this->classification->id, 'active' => 'Active', 'playlistonly' => 'Y']);
        TrainingPlanDetail::create(['trainingplan_id' => $plan->id, 'content_id' => $c1->id, 'content_order' => 0]);
        TrainingPlanDetail::create(['trainingplan_id' => $plan->id, 'content_id' => $c2->id, 'content_order' => 1]);

        PlaylistContentUser::create(['user_id' => $this->userA->id, 'trainingplan_id' => $plan->id, 'content_id' => $c1->id, 'progress' => 100, 'finish' => '1']);
        // C2 has no record (0%)

        $completed = $this->service->forUser($this->userA->id);
        $this->assertFalse($completed->contains('id', $plan->id));
    }

    /**
     * TEST 10: Direct Content remains isolated (playlistonly = N in content_users does not complete playlist)
     */
    public function test_10_direct_content_remains_isolated(): void
    {
        $plan = TrainingPlan::create(['title' => 'Direct Iso Comp Plan', 'slug' => 'dir-iso-comp-' . rand(1000, 9999), 'active' => 'Active']);
        $c = Content::create(['title' => 'Direct Iso Comp Content', 'slug' => 'dir-iso-comp-c-' . rand(1000, 9999), 'type' => 'Video', 'classification_id' => $this->classification->id, 'active' => 'Active', 'playlistonly' => 'N']);
        TrainingPlanDetail::create(['trainingplan_id' => $plan->id, 'content_id' => $c->id, 'content_order' => 0]);

        // Historical 100% on direct content (playlistonly = N)
        ContentUser::create(['user_id' => $this->userA->id, 'content_id' => $c->id, 'progress' => 100, 'finish' => '1']);

        $completed = $this->service->forUser($this->userA->id);
        $this->assertFalse($completed->contains('id', $plan->id));
    }

    /**
     * TEST 11: Empty playlist (0 items) is not completed
     */
    public function test_11_empty_playlist_is_not_completed(): void
    {
        $emptyPlan = TrainingPlan::create(['title' => 'Empty Plan', 'slug' => 'empty-plan-' . rand(1000, 9999), 'active' => 'Active']);

        $completed = $this->service->forUser($this->userA->id);
        $this->assertFalse($completed->contains('id', $emptyPlan->id));
    }

    /**
     * TEST 12: Service performs zero database writes
     */
    public function test_12_service_performs_zero_database_writes(): void
    {
        $plan = TrainingPlan::create(['title' => 'ZW Comp Plan', 'slug' => 'zw-comp-plan-' . rand(1000, 9999), 'active' => 'Active']);
        $c = Content::create(['title' => 'ZW Comp Content', 'slug' => 'zw-comp-c-' . rand(1000, 9999), 'type' => 'Video', 'classification_id' => $this->classification->id, 'active' => 'Active', 'playlistonly' => 'Y']);
        TrainingPlanDetail::create(['trainingplan_id' => $plan->id, 'content_id' => $c->id, 'content_order' => 0]);

        ContentUser::create(['user_id' => $this->userA->id, 'content_id' => $c->id, 'progress' => 100, 'finish' => '1']);

        $initialPcuCount = PlaylistContentUser::count();
        $initialCuCount = ContentUser::count();

        $this->service->forUser($this->userA->id);

        $this->assertEquals($initialPcuCount, PlaylistContentUser::count());
        $this->assertEquals($initialCuCount, ContentUser::count());
    }
}
