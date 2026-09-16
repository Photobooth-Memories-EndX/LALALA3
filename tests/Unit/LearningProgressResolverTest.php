<?php

namespace Tests\Unit;

use App\Models\Classification;
use App\Models\Content;
use App\Models\ContentUser;
use App\Models\PlaylistContentUser;
use App\Models\TrainingPlan;
use App\Models\TrainingPlanDetail;
use App\Models\User;
use App\Services\Learning\LearningProgressResolver;
use App\Services\Learning\ResolvedProgress;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class LearningProgressResolverTest extends TestCase
{
    use DatabaseTransactions;

    protected User $userA;
    protected User $userB;
    protected Classification $classification;
    protected LearningProgressResolver $resolver;

    protected function setUp(): void
    {
        parent::setUp();

        $this->resolver = new LearningProgressResolver();

        $this->userA = User::factory()->create([
            'team'       => 'Engineering',
            'department' => 'Technology',
            'division'   => 'LMS',
            'ssohash'    => 'test-hash-resolver-a-' . \Illuminate\Support\Str::uuid(),
            'sap_code'   => 'SAP-RES-A-' . rand(1000, 9999),
            'role'       => 1,
            'active'     => 1,
        ]);

        $this->userB = User::factory()->create([
            'team'       => 'Engineering',
            'department' => 'Technology',
            'division'   => 'LMS',
            'ssohash'    => 'test-hash-resolver-b-' . \Illuminate\Support\Str::uuid(),
            'sap_code'   => 'SAP-RES-B-' . rand(1000, 9999),
            'role'       => 1,
            'active'     => 1,
        ]);

        $this->classification = Classification::firstOrCreate(
            ['classification_name' => 'Resolver Test Classification'],
            ['slug' => 'resolver-test-classification']
        );
    }

    /**
     * TEST 1: Direct Content reads content_users
     */
    public function test_1_direct_content_reads_content_users(): void
    {
        $content = Content::create([
            'title'             => 'Direct Video Test',
            'slug'              => 'direct-video-test-' . rand(1000, 9999),
            'type'              => 'Video',
            'classification_id' => $this->classification->id,
            'duration'          => '00:10:00',
            'active'            => 'Active',
            'playlistonly'      => 'N',
            'has_deadline'      => 0,
        ]);

        ContentUser::create([
            'user_id'          => $this->userA->id,
            'content_id'       => $content->id,
            'progress'         => 75,
            'finish'           => '0',
            'learning_status'  => 'in_progress',
            'current_time'     => 450,
            'duration_seconds' => 600,
        ]);

        $result = $this->resolver->resolveDirect($this->userA->id, $content->id);

        $this->assertEquals(75, $result->progress);
        $this->assertEquals(0, $result->finish);
        $this->assertEquals('in_progress', $result->learningStatus);
        $this->assertEquals(450, $result->currentTime);
        $this->assertEquals(ResolvedProgress::SOURCE_CONTENT_USERS, $result->source);
        $this->assertTrue($result->isAuthoritative);
        $this->assertFalse($result->isHistoricalFallback);
    }

    /**
     * TEST 2: PCU exists -> PCU wins
     */
    public function test_2_pcu_exists_pcu_wins(): void
    {
        $plan = TrainingPlan::create(['title' => 'Test Plan 1', 'slug' => 'test-plan-1-' . rand(1000, 9999), 'active' => 'Active']);
        $content = Content::create(['title' => 'Test Content 1', 'slug' => 'test-content-1-' . rand(1000, 9999), 'type' => 'Video', 'classification_id' => $this->classification->id, 'active' => 'Active', 'playlistonly' => 'Y']);
        TrainingPlanDetail::create(['trainingplan_id' => $plan->id, 'content_id' => $content->id, 'content_order' => 0]);

        PlaylistContentUser::create([
            'user_id'          => $this->userA->id,
            'trainingplan_id'  => $plan->id,
            'content_id'       => $content->id,
            'progress'         => 90,
            'finish'           => '0',
            'learning_status'  => 'in_progress',
            'current_time'     => 540,
            'duration_seconds' => 600,
        ]);

        $result = $this->resolver->resolvePlaylist($this->userA->id, $plan->id, $content->id);

        $this->assertEquals(90, $result->progress);
        $this->assertEquals(ResolvedProgress::SOURCE_PLAYLIST_CONTENT_USERS, $result->source);
        $this->assertTrue($result->isAuthoritative);
        $this->assertFalse($result->isHistoricalFallback);
    }

    /**
     * TEST 3: PCU = 0% is AUTHORITATIVE and NEVER falls back to content_users = 100%
     */
    public function test_3_pcu_zero_is_authoritative_and_does_not_fallback(): void
    {
        $plan = TrainingPlan::create(['title' => 'Zero PCU Plan', 'slug' => 'zero-pcu-plan-' . rand(1000, 9999), 'active' => 'Active']);
        $content = Content::create(['title' => 'Zero Content', 'slug' => 'zero-content-' . rand(1000, 9999), 'type' => 'Video', 'classification_id' => $this->classification->id, 'active' => 'Active', 'playlistonly' => 'Y']);
        TrainingPlanDetail::create(['trainingplan_id' => $plan->id, 'content_id' => $content->id, 'content_order' => 0]);

        // Historical content_users is 100%
        ContentUser::create([
            'user_id'    => $this->userA->id,
            'content_id' => $content->id,
            'progress'   => 100,
            'finish'     => '1',
        ]);

        // PCU is explicitly 0%
        PlaylistContentUser::create([
            'user_id'         => $this->userA->id,
            'trainingplan_id' => $plan->id,
            'content_id'      => $content->id,
            'progress'        => 0,
            'finish'          => '0',
            'learning_status' => 'not_started',
        ]);

        $result = $this->resolver->resolvePlaylist($this->userA->id, $plan->id, $content->id);

        $this->assertEquals(0, $result->progress);
        $this->assertEquals(0, $result->finish);
        $this->assertEquals(ResolvedProgress::SOURCE_PLAYLIST_CONTENT_USERS, $result->source);
        $this->assertTrue($result->isAuthoritative);
        $this->assertFalse($result->isFinished());
    }

    /**
     * TEST 4: PCU = 40% vs CU = 100% -> PCU wins (returns 40%)
     */
    public function test_4_pcu_40_vs_cu_100_returns_40(): void
    {
        $plan = TrainingPlan::create(['title' => 'Plan 40', 'slug' => 'plan-40-' . rand(1000, 9999), 'active' => 'Active']);
        $content = Content::create(['title' => 'Content 40', 'slug' => 'content-40-' . rand(1000, 9999), 'type' => 'Video', 'classification_id' => $this->classification->id, 'active' => 'Active', 'playlistonly' => 'Y']);
        TrainingPlanDetail::create(['trainingplan_id' => $plan->id, 'content_id' => $content->id, 'content_order' => 0]);

        ContentUser::create(['user_id' => $this->userA->id, 'content_id' => $content->id, 'progress' => 100, 'finish' => '1']);
        PlaylistContentUser::create(['user_id' => $this->userA->id, 'trainingplan_id' => $plan->id, 'content_id' => $content->id, 'progress' => 40, 'finish' => '0']);

        $result = $this->resolver->resolvePlaylist($this->userA->id, $plan->id, $content->id);

        $this->assertEquals(40, $result->progress);
        $this->assertEquals(ResolvedProgress::SOURCE_PLAYLIST_CONTENT_USERS, $result->source);
        $this->assertTrue($result->isAuthoritative);
    }

    /**
     * TEST 5: PCU = 100% vs CU = 20% -> PCU wins (returns 100%)
     */
    public function test_5_pcu_100_vs_cu_20_returns_100(): void
    {
        $plan = TrainingPlan::create(['title' => 'Plan 100', 'slug' => 'plan-100-' . rand(1000, 9999), 'active' => 'Active']);
        $content = Content::create(['title' => 'Content 100', 'slug' => 'content-100-' . rand(1000, 9999), 'type' => 'Video', 'classification_id' => $this->classification->id, 'active' => 'Active', 'playlistonly' => 'Y']);
        TrainingPlanDetail::create(['trainingplan_id' => $plan->id, 'content_id' => $content->id, 'content_order' => 0]);

        ContentUser::create(['user_id' => $this->userA->id, 'content_id' => $content->id, 'progress' => 20, 'finish' => '0']);
        PlaylistContentUser::create(['user_id' => $this->userA->id, 'trainingplan_id' => $plan->id, 'content_id' => $content->id, 'progress' => 100, 'finish' => '1']);

        $result = $this->resolver->resolvePlaylist($this->userA->id, $plan->id, $content->id);

        $this->assertEquals(100, $result->progress);
        $this->assertEquals(1, $result->finish);
        $this->assertTrue($result->isFinished());
        $this->assertEquals(ResolvedProgress::SOURCE_PLAYLIST_CONTENT_USERS, $result->source);
    }

    /**
     * TEST 6: Missing PCU returns 0% and does NOT fallback to content_users
     */
    public function test_6_missing_pcu_returns_zero_without_fallback(): void
    {
        $plan = TrainingPlan::create(['title' => 'Missing PCU Plan', 'slug' => 'missing-pcu-plan-' . rand(1000, 9999), 'active' => 'Active']);
        $content = Content::create(['title' => 'Missing PCU Content', 'slug' => 'missing-pcu-content-' . rand(1000, 9999), 'type' => 'Video', 'classification_id' => $this->classification->id, 'active' => 'Active', 'playlistonly' => 'Y']);
        TrainingPlanDetail::create(['trainingplan_id' => $plan->id, 'content_id' => $content->id, 'content_order' => 0]);

        // CU has 100% but NO PCU exists
        ContentUser::create(['user_id' => $this->userA->id, 'content_id' => $content->id, 'progress' => 100, 'finish' => '1']);

        $result = $this->resolver->resolvePlaylist($this->userA->id, $plan->id, $content->id);

        $this->assertEquals(0, $result->progress);
        $this->assertEquals(0, $result->finish);
        $this->assertEquals(ResolvedProgress::SOURCE_NONE, $result->source);
        $this->assertFalse($result->isFinished());
    }

    /**
     * TEST 7: New Playlist returns 0%
     */
    public function test_7_new_playlist_returns_zero(): void
    {
        $newPlan = TrainingPlan::create([
            'title'      => 'Brand New Plan',
            'slug'       => 'brand-new-plan-' . rand(1000, 9999),
            'active'     => 'Active',
            'created_at' => now()->addDay(),
        ]);

        $content = Content::create(['title' => 'New Plan Content', 'slug' => 'new-plan-content-' . rand(1000, 9999), 'type' => 'Video', 'classification_id' => $this->classification->id, 'active' => 'Active', 'playlistonly' => 'Y']);
        TrainingPlanDetail::create(['trainingplan_id' => $newPlan->id, 'content_id' => $content->id, 'content_order' => 0]);

        ContentUser::create(['user_id' => $this->userA->id, 'content_id' => $content->id, 'progress' => 100, 'finish' => '1']);

        $result = $this->resolver->resolvePlaylist($this->userA->id, $newPlan->id, $content->id);

        $this->assertEquals(0, $result->progress);
        $this->assertEquals(ResolvedProgress::SOURCE_NONE, $result->source);
    }

    /**
     * TEST 8: Multi-Playlist isolation (Playlist A = 100%, Playlist B = 40%, Playlist C = 0%)
     */
    public function test_8_multi_playlist_isolation(): void
    {
        $planA = TrainingPlan::create(['title' => 'Plan A', 'slug' => 'plan-a-' . rand(1000, 9999), 'active' => 'Active']);
        $planB = TrainingPlan::create(['title' => 'Plan B', 'slug' => 'plan-b-' . rand(1000, 9999), 'active' => 'Active']);
        $planC = TrainingPlan::create(['title' => 'Plan C', 'slug' => 'plan-c-' . rand(1000, 9999), 'active' => 'Active']);

        $content = Content::create(['title' => 'Shared Content', 'slug' => 'shared-content-' . rand(1000, 9999), 'type' => 'Video', 'classification_id' => $this->classification->id, 'active' => 'Active', 'playlistonly' => 'Y']);
        TrainingPlanDetail::create(['trainingplan_id' => $planA->id, 'content_id' => $content->id, 'content_order' => 0]);
        TrainingPlanDetail::create(['trainingplan_id' => $planB->id, 'content_id' => $content->id, 'content_order' => 0]);
        TrainingPlanDetail::create(['trainingplan_id' => $planC->id, 'content_id' => $content->id, 'content_order' => 0]);

        PlaylistContentUser::create(['user_id' => $this->userA->id, 'trainingplan_id' => $planA->id, 'content_id' => $content->id, 'progress' => 100, 'finish' => '1']);
        PlaylistContentUser::create(['user_id' => $this->userA->id, 'trainingplan_id' => $planB->id, 'content_id' => $content->id, 'progress' => 40, 'finish' => '0']);
        // Plan C has NO PCU

        $resA = $this->resolver->resolvePlaylist($this->userA->id, $planA->id, $content->id);
        $resB = $this->resolver->resolvePlaylist($this->userA->id, $planB->id, $content->id);
        $resC = $this->resolver->resolvePlaylist($this->userA->id, $planC->id, $content->id);

        $this->assertEquals(100, $resA->progress);
        $this->assertEquals(40, $resB->progress);
        $this->assertEquals(0, $resC->progress);
    }

    /**
     * TEST 9: User isolation (User A = 100%, User B = 0%)
     */
    public function test_9_user_isolation(): void
    {
        $plan = TrainingPlan::create(['title' => 'User Iso Plan', 'slug' => 'user-iso-plan-' . rand(1000, 9999), 'active' => 'Active']);
        $content = Content::create(['title' => 'User Iso Content', 'slug' => 'user-iso-content-' . rand(1000, 9999), 'type' => 'Video', 'classification_id' => $this->classification->id, 'active' => 'Active', 'playlistonly' => 'Y']);
        TrainingPlanDetail::create(['trainingplan_id' => $plan->id, 'content_id' => $content->id, 'content_order' => 0]);

        PlaylistContentUser::create(['user_id' => $this->userA->id, 'trainingplan_id' => $plan->id, 'content_id' => $content->id, 'progress' => 100, 'finish' => '1']);

        $resA = $this->resolver->resolvePlaylist($this->userA->id, $plan->id, $content->id);
        $resB = $this->resolver->resolvePlaylist($this->userB->id, $plan->id, $content->id);

        $this->assertEquals(100, $resA->progress);
        $this->assertEquals(0, $resB->progress);
    }

    /**
     * TEST 10: Direct Content isolation (Direct = 100%, Playlist = 40%)
     */
    public function test_10_direct_content_isolation(): void
    {
        $plan = TrainingPlan::create(['title' => 'Direct Iso Plan', 'slug' => 'dir-iso-plan-' . rand(1000, 9999), 'active' => 'Active']);
        $content = Content::create(['title' => 'Direct Iso Content', 'slug' => 'dir-iso-content-' . rand(1000, 9999), 'type' => 'Video', 'classification_id' => $this->classification->id, 'active' => 'Active', 'playlistonly' => 'Y']);
        TrainingPlanDetail::create(['trainingplan_id' => $plan->id, 'content_id' => $content->id, 'content_order' => 0]);

        ContentUser::create(['user_id' => $this->userA->id, 'content_id' => $content->id, 'progress' => 100, 'finish' => '1']);
        PlaylistContentUser::create(['user_id' => $this->userA->id, 'trainingplan_id' => $plan->id, 'content_id' => $content->id, 'progress' => 40, 'finish' => '0']);

        $directRes = $this->resolver->resolveDirect($this->userA->id, $content->id);
        $playlistRes = $this->resolver->resolvePlaylist($this->userA->id, $plan->id, $content->id);

        $this->assertEquals(100, $directRes->progress);
        $this->assertEquals(40, $playlistRes->progress);
    }

    /**
     * TEST 11: Batch resolution works accurately
     */
    public function test_11_batch_resolution(): void
    {
        $plan = TrainingPlan::create(['title' => 'Batch Plan', 'slug' => 'batch-plan-' . rand(1000, 9999), 'active' => 'Active']);

        $c1 = Content::create(['title' => 'C1', 'slug' => 'c1-' . rand(1000, 9999), 'type' => 'Video', 'classification_id' => $this->classification->id, 'active' => 'Active', 'playlistonly' => 'Y']);
        $c2 = Content::create(['title' => 'C2', 'slug' => 'c2-' . rand(1000, 9999), 'type' => 'Video', 'classification_id' => $this->classification->id, 'active' => 'Active', 'playlistonly' => 'Y']);
        $c3 = Content::create(['title' => 'C3', 'slug' => 'c3-' . rand(1000, 9999), 'type' => 'Video', 'classification_id' => $this->classification->id, 'active' => 'Active', 'playlistonly' => 'Y']);

        TrainingPlanDetail::create(['trainingplan_id' => $plan->id, 'content_id' => $c1->id, 'content_order' => 0]);
        TrainingPlanDetail::create(['trainingplan_id' => $plan->id, 'content_id' => $c2->id, 'content_order' => 1]);
        TrainingPlanDetail::create(['trainingplan_id' => $plan->id, 'content_id' => $c3->id, 'content_order' => 2]);

        PlaylistContentUser::create(['user_id' => $this->userA->id, 'trainingplan_id' => $plan->id, 'content_id' => $c1->id, 'progress' => 100, 'finish' => '1']);
        PlaylistContentUser::create(['user_id' => $this->userA->id, 'trainingplan_id' => $plan->id, 'content_id' => $c2->id, 'progress' => 50, 'finish' => '0']);
        // C3 has no PCU

        $batch = $this->resolver->resolvePlaylistBatch($this->userA->id, $plan->id, [$c1->id, $c2->id, $c3->id]);

        $this->assertCount(3, $batch);
        $this->assertEquals(100, $batch->get($c1->id)->progress);
        $this->assertEquals(50, $batch->get($c2->id)->progress);
        $this->assertEquals(0, $batch->get($c3->id)->progress);
    }

    /**
     * TEST 12: Batch resolution does NOT query content_users and executes minimal queries (Zero N+1)
     */
    public function test_12_batch_does_not_query_content_users_and_has_zero_n_plus_1(): void
    {
        $plan = TrainingPlan::create(['title' => 'Perf Plan', 'slug' => 'perf-plan-' . rand(1000, 9999), 'active' => 'Active']);
        $c1 = Content::create(['title' => 'Perf C1', 'slug' => 'perf-c1-' . rand(1000, 9999), 'type' => 'Video', 'classification_id' => $this->classification->id, 'active' => 'Active', 'playlistonly' => 'Y']);
        $c2 = Content::create(['title' => 'Perf C2', 'slug' => 'perf-c2-' . rand(1000, 9999), 'type' => 'Video', 'classification_id' => $this->classification->id, 'active' => 'Active', 'playlistonly' => 'Y']);

        TrainingPlanDetail::create(['trainingplan_id' => $plan->id, 'content_id' => $c1->id, 'content_order' => 0]);
        TrainingPlanDetail::create(['trainingplan_id' => $plan->id, 'content_id' => $c2->id, 'content_order' => 1]);

        PlaylistContentUser::create(['user_id' => $this->userA->id, 'trainingplan_id' => $plan->id, 'content_id' => $c1->id, 'progress' => 80, 'finish' => '0']);

        DB::enableQueryLog();

        $batch = $this->resolver->resolvePlaylistBatch($this->userA->id, $plan->id, [$c1->id, $c2->id]);

        $queries = DB::getQueryLog();
        DB::disableQueryLog();

        // Exactly 1 single query to playlist_content_users
        $this->assertCount(1, $queries);
        $this->assertStringContainsString('playlist_content_users', strtolower($queries[0]['query']));
        $this->assertStringNotContainsString('from `content_users`', strtolower($queries[0]['query']));
        $this->assertStringNotContainsString('from content_users', strtolower($queries[0]['query']));
        $this->assertStringNotContainsString('trainingplandetail', strtolower($queries[0]['query']));
    }
}
