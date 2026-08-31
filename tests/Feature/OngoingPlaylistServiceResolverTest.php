<?php

namespace Tests\Feature;

use App\Models\Classification;
use App\Models\Content;
use App\Models\ContentUser;
use App\Models\PlaylistContentUser;
use App\Models\TrainingPlan;
use App\Models\TrainingPlanDetail;
use App\Models\User;
use App\Services\Learning\OngoingPlaylistService;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class OngoingPlaylistServiceResolverTest extends TestCase
{
    use DatabaseTransactions;

    protected User $userA;
    protected User $userB;
    protected Classification $classification;
    protected OngoingPlaylistService $service;

    protected function setUp(): void
    {
        parent::setUp();

        $this->service = app(OngoingPlaylistService::class);

        $this->userA = User::factory()->create([
            'team'       => 'Engineering',
            'department' => 'Technology',
            'division'   => 'LMS',
            'ssohash'    => 'test-hash-ong-a-' . \Illuminate\Support\Str::uuid(),
            'sap_code'   => 'SAP-ONG-A-' . rand(1000, 9999),
            'role'       => 1,
            'active'     => 1,
        ]);

        $this->userB = User::factory()->create([
            'team'       => 'Engineering',
            'department' => 'Technology',
            'division'   => 'LMS',
            'ssohash'    => 'test-hash-ong-b-' . \Illuminate\Support\Str::uuid(),
            'sap_code'   => 'SAP-ONG-B-' . rand(1000, 9999),
            'role'       => 1,
            'active'     => 1,
        ]);

        $this->classification = Classification::firstOrCreate(
            ['classification_name' => 'Ongoing Test Classification'],
            ['slug' => 'ongoing-test-classification']
        );
    }

    /**
     * TEST 1: Historical Category A progress is recognized in Ongoing calculation
     */
    public function test_1_historical_category_a_progress_is_recognized(): void
    {
        $plan = TrainingPlan::create(['title' => 'Ongoing Hist Plan', 'slug' => 'ong-hist-plan-' . rand(1000, 9999), 'active' => 'Active']);
        $c1 = Content::create(['title' => 'Ong Hist C1', 'slug' => 'ong-hist-c1-' . rand(1000, 9999), 'type' => 'Video', 'classification_id' => $this->classification->id, 'active' => 'Active', 'playlistonly' => 'Y']);
        $c2 = Content::create(['title' => 'Ong Hist C2', 'slug' => 'ong-hist-c2-' . rand(1000, 9999), 'type' => 'Video', 'classification_id' => $this->classification->id, 'active' => 'Active', 'playlistonly' => 'Y']);
        TrainingPlanDetail::create(['trainingplan_id' => $plan->id, 'content_id' => $c1->id, 'content_order' => 0]);
        TrainingPlanDetail::create(['trainingplan_id' => $plan->id, 'content_id' => $c2->id, 'content_order' => 1]);

        // Playlist progress in playlist_content_users
        PlaylistContentUser::create([
            'user_id'         => $this->userA->id,
            'trainingplan_id' => $plan->id,
            'content_id'      => $c1->id,
            'progress'        => 50,
            'finish'          => '0',
            'learning_status' => 'in_progress',
        ]);

        $ongoing = $this->service->userPlaylists($this->userA->id);
        $found = $ongoing->firstWhere('id', $plan->id);

        $this->assertNotNull($found);
        $this->assertEquals(2, (int) $found->total_items);
        $this->assertEquals(0, (int) $found->finished_items);
        $this->assertEquals(25, (int) $found->avg_progress); // avg(50, 0) = 25
        $this->assertEquals(0, (int) $found->progress_percent);
        $this->assertFalse($found->is_completed);
    }

    /**
     * TEST 2: PCU overrides historical content_users in Ongoing calculation
     */
    public function test_2_pcu_overrides_historical_content_users(): void
    {
        $plan = TrainingPlan::create(['title' => 'PCU Override Plan', 'slug' => 'pcu-over-plan-' . rand(1000, 9999), 'active' => 'Active']);
        $c1 = Content::create(['title' => 'PCU Over C1', 'slug' => 'pcu-over-c1-' . rand(1000, 9999), 'type' => 'Video', 'classification_id' => $this->classification->id, 'active' => 'Active', 'playlistonly' => 'Y']);
        TrainingPlanDetail::create(['trainingplan_id' => $plan->id, 'content_id' => $c1->id, 'content_order' => 0]);

        // Historical 100% in content_users
        ContentUser::create(['user_id' => $this->userA->id, 'content_id' => $c1->id, 'progress' => 100, 'finish' => '1']);

        // Authoritative 20% in PCU
        PlaylistContentUser::create(['user_id' => $this->userA->id, 'trainingplan_id' => $plan->id, 'content_id' => $c1->id, 'progress' => 20, 'finish' => '0']);

        $ongoing = $this->service->userPlaylists($this->userA->id);
        $found = $ongoing->firstWhere('id', $plan->id);

        $this->assertNotNull($found);
        $this->assertEquals(0, (int) $found->finished_items);
        $this->assertEquals(20, (int) $found->avg_progress);
        $this->assertEquals(0, (int) $found->progress_percent);
        $this->assertFalse($found->is_completed);
    }

    /**
     * TEST 3: Completed PCU is authoritative
     */
    public function test_3_completed_pcu_is_authoritative(): void
    {
        $plan = TrainingPlan::create(['title' => 'Completed PCU Plan', 'slug' => 'comp-pcu-plan-' . rand(1000, 9999), 'active' => 'Active']);
        $c1 = Content::create(['title' => 'Comp PCU C1', 'slug' => 'comp-pcu-c1-' . rand(1000, 9999), 'type' => 'Video', 'classification_id' => $this->classification->id, 'active' => 'Active', 'playlistonly' => 'Y']);
        TrainingPlanDetail::create(['trainingplan_id' => $plan->id, 'content_id' => $c1->id, 'content_order' => 0]);

        // Historical 20% in CU
        ContentUser::create(['user_id' => $this->userA->id, 'content_id' => $c1->id, 'progress' => 20, 'finish' => '0']);

        // Authoritative 100% in PCU
        PlaylistContentUser::create(['user_id' => $this->userA->id, 'trainingplan_id' => $plan->id, 'content_id' => $c1->id, 'progress' => 100, 'finish' => '1']);

        $ongoing = $this->service->userPlaylists($this->userA->id);
        $found = $ongoing->firstWhere('id', $plan->id);

        $this->assertNotNull($found);
        $this->assertEquals(1, (int) $found->finished_items);
        $this->assertEquals(100, (int) $found->progress_percent);
        $this->assertTrue($found->is_completed);
    }

    /**
     * TEST 4: Multi-playlist historical ambiguity is NOT blindly counted
     */
    public function test_4_multi_playlist_ambiguity_not_blindly_counted(): void
    {
        $planA = TrainingPlan::create(['title' => 'Amb Plan A', 'slug' => 'amb-plan-a-' . rand(1000, 9999), 'active' => 'Active']);
        $planB = TrainingPlan::create(['title' => 'Amb Plan B', 'slug' => 'amb-plan-b-' . rand(1000, 9999), 'active' => 'Active']);

        $c = Content::create(['title' => 'Amb Content', 'slug' => 'amb-content-' . rand(1000, 9999), 'type' => 'Video', 'classification_id' => $this->classification->id, 'active' => 'Active', 'playlistonly' => 'Y']);
        TrainingPlanDetail::create(['trainingplan_id' => $planA->id, 'content_id' => $c->id, 'content_order' => 0]);
        TrainingPlanDetail::create(['trainingplan_id' => $planB->id, 'content_id' => $c->id, 'content_order' => 0]);

        // Historical 100% in CU
        ContentUser::create(['user_id' => $this->userA->id, 'content_id' => $c->id, 'progress' => 100, 'finish' => '1']);

        $ongoing = $this->service->userPlaylists($this->userA->id);

        // Neither Plan A nor Plan B should be returned since progress is ambiguous (0%)
        $this->assertFalse($ongoing->contains('id', $planA->id));
        $this->assertFalse($ongoing->contains('id', $planB->id));
    }

    /**
     * TEST 5: Playlist A vs Playlist B isolation
     */
    public function test_5_playlist_isolation(): void
    {
        $planA = TrainingPlan::create(['title' => 'Iso Plan A', 'slug' => 'iso-plan-a-' . rand(1000, 9999), 'active' => 'Active']);
        $planB = TrainingPlan::create(['title' => 'Iso Plan B', 'slug' => 'iso-plan-b-' . rand(1000, 9999), 'active' => 'Active']);

        $c = Content::create(['title' => 'Iso Content', 'slug' => 'iso-content-' . rand(1000, 9999), 'type' => 'Video', 'classification_id' => $this->classification->id, 'active' => 'Active', 'playlistonly' => 'Y']);
        TrainingPlanDetail::create(['trainingplan_id' => $planA->id, 'content_id' => $c->id, 'content_order' => 0]);
        TrainingPlanDetail::create(['trainingplan_id' => $planB->id, 'content_id' => $c->id, 'content_order' => 0]);

        PlaylistContentUser::create(['user_id' => $this->userA->id, 'trainingplan_id' => $planA->id, 'content_id' => $c->id, 'progress' => 20, 'finish' => '0']);
        PlaylistContentUser::create(['user_id' => $this->userA->id, 'trainingplan_id' => $planB->id, 'content_id' => $c->id, 'progress' => 60, 'finish' => '0']);

        $ongoing = $this->service->userPlaylists($this->userA->id);

        $resA = $ongoing->firstWhere('id', $planA->id);
        $resB = $ongoing->firstWhere('id', $planB->id);

        $this->assertNotNull($resA);
        $this->assertNotNull($resB);
        $this->assertEquals(20, (int) $resA->avg_progress);
        $this->assertEquals(60, (int) $resB->avg_progress);
    }

    /**
     * TEST 6: User isolation
     */
    public function test_6_user_isolation(): void
    {
        $plan = TrainingPlan::create(['title' => 'User Iso Plan', 'slug' => 'u-iso-plan-' . rand(1000, 9999), 'active' => 'Active']);
        $c = Content::create(['title' => 'User Iso Content', 'slug' => 'u-iso-c-' . rand(1000, 9999), 'type' => 'Video', 'classification_id' => $this->classification->id, 'active' => 'Active', 'playlistonly' => 'Y']);
        TrainingPlanDetail::create(['trainingplan_id' => $plan->id, 'content_id' => $c->id, 'content_order' => 0]);

        // User A has 100% in PCU
        PlaylistContentUser::create(['user_id' => $this->userA->id, 'trainingplan_id' => $plan->id, 'content_id' => $c->id, 'progress' => 100, 'finish' => '1']);

        // User A sees completed
        $ongoingA = $this->service->userPlaylists($this->userA->id);
        $this->assertTrue($ongoingA->contains('id', $plan->id));

        // User B has zero progress -> should not see plan
        $ongoingB = $this->service->userPlaylists($this->userB->id);
        $this->assertFalse($ongoingB->contains('id', $plan->id));
    }

    /**
     * TEST 7: Zero-progress untouched content is not returned
     */
    public function test_7_zero_progress_content_is_not_returned(): void
    {
        $plan = TrainingPlan::create(['title' => 'Zero Plan', 'slug' => 'zero-plan-' . rand(1000, 9999), 'active' => 'Active']);
        $c = Content::create(['title' => 'Zero Content', 'slug' => 'zero-content-' . rand(1000, 9999), 'type' => 'Video', 'classification_id' => $this->classification->id, 'active' => 'Active', 'playlistonly' => 'Y']);
        TrainingPlanDetail::create(['trainingplan_id' => $plan->id, 'content_id' => $c->id, 'content_order' => 0]);

        $ongoing = $this->service->userPlaylists($this->userA->id);
        $this->assertFalse($ongoing->contains('id', $plan->id));
    }

    /**
     * TEST 8: Direct Content isolation (Category C playlistonly = N does not leak into playlist)
     */
    public function test_8_direct_content_isolation(): void
    {
        $plan = TrainingPlan::create(['title' => 'Direct Iso Plan', 'slug' => 'dir-iso-plan-' . rand(1000, 9999), 'active' => 'Active']);
        $c = Content::create(['title' => 'Direct Iso Content', 'slug' => 'dir-iso-c-' . rand(1000, 9999), 'type' => 'Video', 'classification_id' => $this->classification->id, 'active' => 'Active', 'playlistonly' => 'N']);
        TrainingPlanDetail::create(['trainingplan_id' => $plan->id, 'content_id' => $c->id, 'content_order' => 0]);

        // User has 100% in direct content
        ContentUser::create(['user_id' => $this->userA->id, 'content_id' => $c->id, 'progress' => 100, 'finish' => '1']);

        $ongoing = $this->service->userPlaylists($this->userA->id);
        $this->assertFalse($ongoing->contains('id', $plan->id));
    }

    /**
     * TEST 9: Historical Category A completion exposes completed status
     */
    public function test_9_historical_category_a_completion_exposes_completed_status(): void
    {
        $plan = TrainingPlan::create(['title' => 'Hist Comp Plan', 'slug' => 'hist-comp-plan-' . rand(1000, 9999), 'active' => 'Active']);
        $c = Content::create(['title' => 'Hist Comp Content', 'slug' => 'hist-comp-c-' . rand(1000, 9999), 'type' => 'Video', 'classification_id' => $this->classification->id, 'active' => 'Active', 'playlistonly' => 'Y']);
        TrainingPlanDetail::create(['trainingplan_id' => $plan->id, 'content_id' => $c->id, 'content_order' => 0]);

        // Playlist 100% in playlist_content_users
        PlaylistContentUser::create([
            'user_id'         => $this->userA->id,
            'trainingplan_id' => $plan->id,
            'content_id'      => $c->id,
            'progress'        => 100,
            'finish'          => '1',
            'learning_status' => 'completed',
        ]);

        $ongoing = $this->service->userPlaylists($this->userA->id);
        $found = $ongoing->firstWhere('id', $plan->id);

        $this->assertNotNull($found);
        $this->assertEquals(1, (int) $found->finished_items);
        $this->assertEquals(100, (int) $found->progress_percent);
        $this->assertTrue($found->is_completed);
    }

    /**
     * TEST 10: Zero database writes on service execution
     */
    public function test_10_zero_database_writes_on_service_execution(): void
    {
        $plan = TrainingPlan::create(['title' => 'Zero Write Plan', 'slug' => 'zw-plan-' . rand(1000, 9999), 'active' => 'Active']);
        $c = Content::create(['title' => 'Zero Write Content', 'slug' => 'zw-c-' . rand(1000, 9999), 'type' => 'Video', 'classification_id' => $this->classification->id, 'active' => 'Active', 'playlistonly' => 'Y']);
        TrainingPlanDetail::create(['trainingplan_id' => $plan->id, 'content_id' => $c->id, 'content_order' => 0]);

        ContentUser::create(['user_id' => $this->userA->id, 'content_id' => $c->id, 'progress' => 80, 'finish' => '0']);

        $initialPcuCount = PlaylistContentUser::count();
        $initialCuCount = ContentUser::count();

        $this->service->userPlaylists($this->userA->id);

        $this->assertEquals($initialPcuCount, PlaylistContentUser::count());
        $this->assertEquals($initialCuCount, ContentUser::count());
    }
}
