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
use Tests\TestCase;

class LearningProgressResolverTest extends TestCase
{
    use DatabaseTransactions;

    protected User $user;
    protected Classification $classification;
    protected LearningProgressResolver $resolver;

    protected function setUp(): void
    {
        parent::setUp();

        $this->resolver = new LearningProgressResolver();

        $this->user = User::factory()->create([
            'team'       => 'Engineering',
            'department' => 'Technology',
            'division'   => 'LMS',
            'ssohash'    => 'test-hash-resolver-' . \Illuminate\Support\Str::uuid(),
            'sap_code'   => 'SAP-RES-' . rand(1000, 9999),
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
    public function test_direct_content_reads_content_users(): void
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
            'user_id'          => $this->user->id,
            'content_id'       => $content->id,
            'progress'         => 75,
            'finish'           => '0',
            'learning_status'  => 'in_progress',
            'current_time'     => 450,
            'duration_seconds' => 600,
        ]);

        $result = $this->resolver->resolveDirect($this->user->id, $content->id);

        $this->assertEquals(75, $result->progress);
        $this->assertEquals(0, $result->finish);
        $this->assertEquals('in_progress', $result->learningStatus);
        $this->assertEquals(450, $result->currentTime);
        $this->assertEquals(ResolvedProgress::SOURCE_CONTENT_USERS, $result->source);
        $this->assertTrue($result->isAuthoritative);
        $this->assertFalse($result->isHistoricalFallback);
    }

    /**
     * TEST 2: Playlist reads playlist_content_users when record exists
     */
    public function test_playlist_reads_playlist_content_users_when_record_exists(): void
    {
        $plan = TrainingPlan::create([
            'title'  => 'Test Plan 1',
            'slug'   => 'test-plan-1-' . rand(1000, 9999),
            'active' => 'Active',
        ]);

        $content = Content::create([
            'title'             => 'Test Content 1',
            'slug'              => 'test-content-1-' . rand(1000, 9999),
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
            'user_id'          => $this->user->id,
            'trainingplan_id'  => $plan->id,
            'content_id'       => $content->id,
            'progress'         => 90,
            'finish'           => '0',
            'learning_status'  => 'in_progress',
            'current_time'     => 540,
            'duration_seconds' => 600,
        ]);

        $result = $this->resolver->resolvePlaylist($this->user->id, $plan->id, $content->id);

        $this->assertEquals(90, $result->progress);
        $this->assertEquals(ResolvedProgress::SOURCE_PLAYLIST_CONTENT_USERS, $result->source);
        $this->assertTrue($result->isAuthoritative);
        $this->assertFalse($result->isHistoricalFallback);
    }

    /**
     * TEST 3: Existing playlist_content_users is authoritative over historical content_users
     */
    public function test_existing_playlist_content_users_is_authoritative_over_historical_content_users(): void
    {
        $plan = TrainingPlan::create([
            'title'  => 'Test Plan 2',
            'slug'   => 'test-plan-2-' . rand(1000, 9999),
            'active' => 'Active',
        ]);

        $content = Content::create([
            'title'             => 'Test Content 2',
            'slug'              => 'test-content-2-' . rand(1000, 9999),
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

        // Historical record with 80%
        ContentUser::create([
            'user_id'    => $this->user->id,
            'content_id' => $content->id,
            'progress'   => 80,
            'finish'     => '0',
        ]);

        // Authoritative new playlist record with 20%
        PlaylistContentUser::create([
            'user_id'         => $this->user->id,
            'trainingplan_id' => $plan->id,
            'content_id'      => $content->id,
            'progress'        => 20,
            'finish'          => '0',
        ]);

        $result = $this->resolver->resolvePlaylist($this->user->id, $plan->id, $content->id);

        $this->assertEquals(20, $result->progress);
        $this->assertEquals(ResolvedProgress::SOURCE_PLAYLIST_CONTENT_USERS, $result->source);
        $this->assertTrue($result->isAuthoritative);
    }

    /**
     * TEST 4: Single-Playlist historical record (Category A) falls back to content_users
     */
    public function test_single_playlist_category_a_falls_back_to_historical_content_users(): void
    {
        $plan = TrainingPlan::create([
            'title'  => 'Single Category A Plan',
            'slug'   => 'single-category-a-plan-' . rand(1000, 9999),
            'active' => 'Active',
        ]);

        $content = Content::create([
            'title'             => 'Category A Content',
            'slug'              => 'category-a-content-' . rand(1000, 9999),
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

        // Historical record exists
        ContentUser::create([
            'user_id'          => $this->user->id,
            'content_id'       => $content->id,
            'progress'         => 100,
            'finish'           => '1',
            'learning_status'  => 'completed',
            'current_time'     => 600,
            'duration_seconds' => 600,
        ]);

        // No playlist_content_users record exists yet
        $result = $this->resolver->resolvePlaylist($this->user->id, $plan->id, $content->id);

        $this->assertEquals(100, $result->progress);
        $this->assertEquals(1, $result->finish);
        $this->assertEquals('completed', $result->learningStatus);
        $this->assertEquals(ResolvedProgress::SOURCE_HISTORICAL_CONTENT_USERS, $result->source);
        $this->assertTrue($result->isHistoricalFallback);
        $this->assertTrue($result->isFinished());
    }

    /**
     * TEST 5: Multi-Playlist historical record (Category B) is NOT blindly attributed
     */
    public function test_multi_playlist_category_b_is_not_blindly_attributed(): void
    {
        $planA = TrainingPlan::create(['title' => 'Multi Plan A', 'slug' => 'multi-plan-a-' . rand(1000, 9999), 'active' => 'Active']);
        $planB = TrainingPlan::create(['title' => 'Multi Plan B', 'slug' => 'multi-plan-b-' . rand(1000, 9999), 'active' => 'Active']);

        $content = Content::create([
            'title'             => 'Shared Content Across Two Plans',
            'slug'              => 'shared-content-two-plans-' . rand(1000, 9999),
            'type'              => 'Video',
            'classification_id' => $this->classification->id,
            'active'            => 'Active',
            'playlistonly'      => 'Y',
        ]);

        TrainingPlanDetail::create(['trainingplan_id' => $planA->id, 'content_id' => $content->id, 'content_order' => 0]);
        TrainingPlanDetail::create(['trainingplan_id' => $planB->id, 'content_id' => $content->id, 'content_order' => 0]);

        // Historical progress in content_users
        ContentUser::create([
            'user_id'    => $this->user->id,
            'content_id' => $content->id,
            'progress'   => 100,
            'finish'     => '1',
        ]);

        // Resolver for Plan A should NOT assume 100%
        $resultA = $this->resolver->resolvePlaylist($this->user->id, $planA->id, $content->id);
        $this->assertEquals(0, $resultA->progress);
        $this->assertEquals(ResolvedProgress::SOURCE_AMBIGUOUS, $resultA->source);
        $this->assertFalse($resultA->isFinished());

        // Resolver for Plan B should also NOT assume 100%
        $resultB = $this->resolver->resolvePlaylist($this->user->id, $planB->id, $content->id);
        $this->assertEquals(0, $resultB->progress);
        $this->assertEquals(ResolvedProgress::SOURCE_AMBIGUOUS, $resultB->source);
    }

    /**
     * TEST 6: playlistonly = 'N' (Category C) is never treated as Playlist progress
     */
    public function test_direct_content_category_c_is_never_treated_as_playlist_progress(): void
    {
        $plan = TrainingPlan::create(['title' => 'Plan with Direct Content', 'slug' => 'plan-with-direct-' . rand(1000, 9999), 'active' => 'Active']);

        $content = Content::create([
            'title'             => 'Direct Content in Plan',
            'slug'              => 'direct-content-in-plan-' . rand(1000, 9999),
            'type'              => 'Video',
            'classification_id' => $this->classification->id,
            'active'            => 'Active',
            'playlistonly'      => 'N', // <-- Category C Direct Content
        ]);

        TrainingPlanDetail::create(['trainingplan_id' => $plan->id, 'content_id' => $content->id, 'content_order' => 0]);

        ContentUser::create([
            'user_id'    => $this->user->id,
            'content_id' => $content->id,
            'progress'   => 100,
            'finish'     => '1',
        ]);

        // No playlist_content_users record exists
        $result = $this->resolver->resolvePlaylist($this->user->id, $plan->id, $content->id);

        $this->assertEquals(0, $result->progress);
        $this->assertEquals(ResolvedProgress::SOURCE_NONE, $result->source);
        $this->assertFalse($result->isFinished());
    }

    /**
     * TEST 7: Missing progress returns defined empty state
     */
    public function test_missing_progress_returns_empty_state(): void
    {
        $result = $this->resolver->resolvePlaylist($this->user->id, 999999, 888888);

        $this->assertEquals(0, $result->progress);
        $this->assertEquals(0, $result->finish);
        $this->assertEquals('not_started', $result->learningStatus);
        $this->assertEquals(ResolvedProgress::SOURCE_NONE, $result->source);
        $this->assertFalse($result->isAuthoritative);
        $this->assertFalse($result->isHistoricalFallback);
        $this->assertFalse($result->hasStarted());
    }

    /**
     * TEST 8: Direct Content progress does not affect Playlist progress
     */
    public function test_direct_content_progress_does_not_affect_playlist_progress(): void
    {
        $plan = TrainingPlan::create(['title' => 'Isolated Plan', 'slug' => 'isolated-plan-' . rand(1000, 9999), 'active' => 'Active']);

        $content = Content::create([
            'title'             => 'Isolated Content',
            'slug'              => 'isolated-content-' . rand(1000, 9999),
            'type'              => 'Video',
            'classification_id' => $this->classification->id,
            'active'            => 'Active',
            'playlistonly'      => 'Y',
        ]);

        TrainingPlanDetail::create(['trainingplan_id' => $plan->id, 'content_id' => $content->id, 'content_order' => 0]);

        // User has direct progress 50%
        ContentUser::create([
            'user_id'    => $this->user->id,
            'content_id' => $content->id,
            'progress'   => 50,
            'finish'     => '0',
        ]);

        // User creates playlist progress 10%
        PlaylistContentUser::create([
            'user_id'         => $this->user->id,
            'trainingplan_id' => $plan->id,
            'content_id'      => $content->id,
            'progress'        => 10,
            'finish'          => '0',
        ]);

        $directResult = $this->resolver->resolveDirect($this->user->id, $content->id);
        $playlistResult = $this->resolver->resolvePlaylist($this->user->id, $plan->id, $content->id);

        $this->assertEquals(50, $directResult->progress);
        $this->assertEquals(10, $playlistResult->progress);
    }

    /**
     * TEST 9: Playlist A progress does not affect Playlist B
     */
    public function test_playlist_a_progress_does_not_affect_playlist_b(): void
    {
        $planA = TrainingPlan::create(['title' => 'Isolation Plan A', 'slug' => 'iso-plan-a-' . rand(1000, 9999), 'active' => 'Active']);
        $planB = TrainingPlan::create(['title' => 'Isolation Plan B', 'slug' => 'iso-plan-b-' . rand(1000, 9999), 'active' => 'Active']);

        $content = Content::create([
            'title'             => 'Shared Isolation Content',
            'slug'              => 'shared-iso-content-' . rand(1000, 9999),
            'type'              => 'Video',
            'classification_id' => $this->classification->id,
            'active'            => 'Active',
            'playlistonly'      => 'Y',
        ]);

        TrainingPlanDetail::create(['trainingplan_id' => $planA->id, 'content_id' => $content->id, 'content_order' => 0]);
        TrainingPlanDetail::create(['trainingplan_id' => $planB->id, 'content_id' => $content->id, 'content_order' => 0]);

        // Record progress in Plan A
        PlaylistContentUser::create([
            'user_id'         => $this->user->id,
            'trainingplan_id' => $planA->id,
            'content_id'      => $content->id,
            'progress'        => 95,
            'finish'          => '0',
        ]);

        $resultA = $this->resolver->resolvePlaylist($this->user->id, $planA->id, $content->id);
        $resultB = $this->resolver->resolvePlaylist($this->user->id, $planB->id, $content->id);

        $this->assertEquals(95, $resultA->progress);
        $this->assertEquals(0, $resultB->progress);
    }

    /**
     * TEST 10: Resolver performs NO database writes
     */
    public function test_resolver_performs_no_database_writes(): void
    {
        $plan = TrainingPlan::create(['title' => 'No Write Plan', 'slug' => 'no-write-plan-' . rand(1000, 9999), 'active' => 'Active']);
        $content = Content::create(['title' => 'No Write Content', 'slug' => 'no-write-content-' . rand(1000, 9999), 'type' => 'Video', 'classification_id' => $this->classification->id, 'active' => 'Active', 'playlistonly' => 'Y']);
        TrainingPlanDetail::create(['trainingplan_id' => $plan->id, 'content_id' => $content->id, 'content_order' => 0]);

        $initialPcuCount = PlaylistContentUser::count();
        $initialCuCount = ContentUser::count();

        $this->resolver->resolveDirect($this->user->id, $content->id);
        $this->resolver->resolvePlaylist($this->user->id, $plan->id, $content->id);
        $this->resolver->resolvePlaylistBatch($this->user->id, $plan->id, [$content->id]);

        $this->assertEquals($initialPcuCount, PlaylistContentUser::count());
        $this->assertEquals($initialCuCount, ContentUser::count());
    }

    /**
     * TEST 11: Batch resolution resolvePlaylistBatch works accurately without N+1
     */
    public function test_batch_resolution_works_accurately(): void
    {
        $plan = TrainingPlan::create(['title' => 'Batch Plan', 'slug' => 'batch-plan-' . rand(1000, 9999), 'active' => 'Active']);

        // Content 1: Has authoritative PCU record (40%)
        $c1 = Content::create(['title' => 'Batch C1', 'slug' => 'batch-c1-' . rand(1000, 9999), 'type' => 'Video', 'classification_id' => $this->classification->id, 'active' => 'Active', 'playlistonly' => 'Y']);
        TrainingPlanDetail::create(['trainingplan_id' => $plan->id, 'content_id' => $c1->id, 'content_order' => 0]);
        PlaylistContentUser::create(['user_id' => $this->user->id, 'trainingplan_id' => $plan->id, 'content_id' => $c1->id, 'progress' => 40, 'finish' => '0']);

        // Content 2: Category A single playlist historical fallback (85%)
        $c2 = Content::create(['title' => 'Batch C2', 'slug' => 'batch-c2-' . rand(1000, 9999), 'type' => 'Document', 'classification_id' => $this->classification->id, 'active' => 'Active', 'playlistonly' => 'Y']);
        TrainingPlanDetail::create(['trainingplan_id' => $plan->id, 'content_id' => $c2->id, 'content_order' => 1]);
        ContentUser::create(['user_id' => $this->user->id, 'content_id' => $c2->id, 'progress' => 85, 'finish' => '0']);

        // Content 3: No progress (0%)
        $c3 = Content::create(['title' => 'Batch C3', 'slug' => 'batch-c3-' . rand(1000, 9999), 'type' => 'Video', 'classification_id' => $this->classification->id, 'active' => 'Active', 'playlistonly' => 'Y']);
        TrainingPlanDetail::create(['trainingplan_id' => $plan->id, 'content_id' => $c3->id, 'content_order' => 2]);

        $batch = $this->resolver->resolvePlaylistBatch($this->user->id, $plan->id, [$c1->id, $c2->id, $c3->id]);

        $this->assertCount(3, $batch);
        $this->assertEquals(40, $batch->get($c1->id)->progress);
        $this->assertEquals(ResolvedProgress::SOURCE_PLAYLIST_CONTENT_USERS, $batch->get($c1->id)->source);

        $this->assertEquals(85, $batch->get($c2->id)->progress);
        $this->assertEquals(ResolvedProgress::SOURCE_HISTORICAL_CONTENT_USERS, $batch->get($c2->id)->source);

        $this->assertEquals(0, $batch->get($c3->id)->progress);
        $this->assertEquals(ResolvedProgress::SOURCE_NONE, $batch->get($c3->id)->source);
    }

    /**
     * TEST 12: Historical fallback preserves progress, finish, and position consistently
     */
    public function test_historical_fallback_preserves_position_and_pages(): void
    {
        $plan = TrainingPlan::create(['title' => 'Position Plan', 'slug' => 'pos-plan-' . rand(1000, 9999), 'active' => 'Active']);
        $doc = Content::create(['title' => 'Doc Pos', 'slug' => 'doc-pos-' . rand(1000, 9999), 'type' => 'Document', 'classification_id' => $this->classification->id, 'active' => 'Active', 'playlistonly' => 'Y']);
        TrainingPlanDetail::create(['trainingplan_id' => $plan->id, 'content_id' => $doc->id, 'content_order' => 0]);

        ContentUser::create([
            'user_id'         => $this->user->id,
            'content_id'      => $doc->id,
            'progress'        => 60,
            'finish'          => '0',
            'learning_status' => 'in_progress',
            'last_page'       => 3,
            'total_pages'     => 5,
        ]);

        $result = $this->resolver->resolvePlaylist($this->user->id, $plan->id, $doc->id);

        $this->assertEquals(60, $result->progress);
        $this->assertEquals(0, $result->finish);
        $this->assertEquals('in_progress', $result->learningStatus);
        $this->assertEquals(3, $result->lastPage);
        $this->assertEquals(5, $result->totalPages);
        $this->assertTrue($result->isHistoricalFallback);
        $this->assertTrue($result->hasStarted());
        $this->assertFalse($result->isFinished());
    }
}
