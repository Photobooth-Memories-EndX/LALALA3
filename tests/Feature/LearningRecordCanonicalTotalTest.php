<?php

namespace Tests\Feature;

use App\Models\Classification;
use App\Models\Content;
use App\Models\ContentUser;
use App\Models\PlaylistContentUser;
use App\Models\TrainingPlan;
use App\Models\TrainingPlanDetail;
use App\Models\User;
use App\Services\LearningAnalyticsService;
use App\Services\Learning\LearningRecordLearningHourService;
use App\Services\Learning\OngoingContentService;
use App\Services\Learning\OngoingPlaylistService;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Str;
use Tests\TestCase;

class LearningRecordCanonicalTotalTest extends TestCase
{
    use DatabaseTransactions;

    protected User $user;
    protected Classification $classification;
    protected LearningRecordLearningHourService $actualHourService;
    protected LearningAnalyticsService $analyticsService;
    protected OngoingContentService $ongoingContentService;
    protected OngoingPlaylistService $ongoingPlaylistService;

    protected function setUp(): void
    {
        parent::setUp();

        $this->actualHourService = app(LearningRecordLearningHourService::class);
        $this->analyticsService = app(LearningAnalyticsService::class);
        $this->ongoingContentService = app(OngoingContentService::class);
        $this->ongoingPlaylistService = app(OngoingPlaylistService::class);

        $this->user = User::factory()->create([
            'name'       => 'Test Canonical Learner ' . rand(1000, 9999),
            'team'       => 'Engineering',
            'department' => 'Technology',
            'division'   => 'LMS',
            'ssohash'    => 'test-canonical-' . Str::uuid(),
            'sap_code'   => 'SAP-CANONICAL-' . rand(1000, 9999),
            'role'       => 1,
            'active'     => 1,
        ]);

        $this->classification = Classification::firstOrCreate(
            ['classification_name' => 'General Engineering'],
            ['slug' => 'general-engineering']
        );
    }

    /**
     * 1. No completed records returns 0.0.
     */
    public function test_user_with_no_completed_records_returns_zero(): void
    {
        $hours = $this->actualHourService->getTotalLearningHours($this->user->id);
        $this->assertSame(0.0, $hours);

        $analyticsHours = $this->analyticsService->userLearningHours($this->user->id);
        $this->assertSame(0.0, $analyticsHours);
    }

    /**
     * 2. Standalone completed content uses actual hours, not catalog hours.
     */
    public function test_standalone_completed_content_uses_actual_hours(): void
    {
        $content = Content::create([
            'title'             => 'Standalone Content',
            'slug'              => 'standalone-' . rand(1000, 9999),
            'type'              => 'Video',
            'classification_id' => $this->classification->id,
            'duration'          => '00:30:00', // Catalog 30m -> old bucket would be 0.5h
            'learning_hours'    => 2.0, // Catalog LH: MUST NOT BE USED
            'active'            => 'Active',
            'playlistonly'      => 'N',
        ]);

        // User watched 720 seconds (0.2 hours)
        ContentUser::create([
            'user_id'              => $this->user->id,
            'content_id'           => $content->id,
            'actual_watch_seconds' => 720,
            'duration_seconds'     => 1800,
            'progress'             => 100,
            'finish'               => '1',
            'status'               => 'optional',
        ]);

        $hours = $this->actualHourService->getTotalLearningHours($this->user->id);
        $this->assertEquals(0.2, $hours);
    }

    /**
     * 3. Completed playlist contributes actual module hours.
     */
    public function test_completed_playlist_contributes_actual_hours(): void
    {
        $plan = TrainingPlan::create([
            'title'                  => 'Canonical Playlist',
            'slug'                   => 'canon-plan-' . rand(1000, 9999),
            'active'                 => 'Active',
            'total_duration_seconds' => 3600,
            'learning_hours'         => 5.0, // Catalog value: MUST NOT BE USED
        ]);

        $content = Content::create([
            'title'             => 'Playlist Module 1',
            'slug'              => 'mod-1-' . rand(1000, 9999),
            'type'              => 'Video',
            'classification_id' => $this->classification->id,
            'duration'          => '00:15:00', // 900s
            'learning_hours'    => 1.0,
            'active'            => 'Active',
            'playlistonly'      => 'Y',
        ]);

        TrainingPlanDetail::create([
            'trainingplan_id' => $plan->id,
            'content_id'      => $content->id,
            'content_order'   => 1,
        ]);

        // Mark playlist module finished in PCU with 900s watch time (0.25h)
        PlaylistContentUser::create([
            'user_id'              => $this->user->id,
            'trainingplan_id'      => $plan->id,
            'content_id'           => $content->id,
            'actual_watch_seconds' => 900,
            'duration_seconds'     => 900,
            'progress'             => 100,
            'finish'               => '1',
        ]);

        $hours = $this->actualHourService->getTotalLearningHours($this->user->id);
        $this->assertEquals(0.25, $hours);
    }

    /**
     * 4. Standalone + completed playlist sums both correctly.
     */
    public function test_standalone_and_completed_playlist_sums_both(): void
    {
        // 1. Standalone content: 360s = 0.1h
        $contentStandalone = Content::create([
            'title'             => 'Standalone Video',
            'slug'              => 'std-' . rand(1000, 9999),
            'type'              => 'Video',
            'classification_id' => $this->classification->id,
            'duration'          => '00:10:00',
            'learning_hours'    => 1.0,
            'active'            => 'Active',
            'playlistonly'      => 'N',
        ]);
        ContentUser::create([
            'user_id'              => $this->user->id,
            'content_id'           => $contentStandalone->id,
            'actual_watch_seconds' => 360,
            'duration_seconds'     => 600,
            'progress'             => 100,
            'finish'               => '1',
        ]);

        // 2. Playlist: 720s = 0.2h
        $plan = TrainingPlan::create([
            'title'                  => 'Plan Combo',
            'slug'                   => 'plan-combo-' . rand(1000, 9999),
            'active'                 => 'Active',
            'total_duration_seconds' => 720,
            'learning_hours'         => 3.0,
        ]);
        $contentPlaylist = Content::create([
            'title'             => 'Playlist Mod Combo',
            'slug'              => 'plc-' . rand(1000, 9999),
            'type'              => 'Video',
            'classification_id' => $this->classification->id,
            'duration'          => '00:12:00',
            'learning_hours'    => 1.0,
            'active'            => 'Active',
            'playlistonly'      => 'Y',
        ]);
        TrainingPlanDetail::create([
            'trainingplan_id' => $plan->id,
            'content_id'      => $contentPlaylist->id,
            'content_order'   => 1,
        ]);
        PlaylistContentUser::create([
            'user_id'              => $this->user->id,
            'trainingplan_id'      => $plan->id,
            'content_id'           => $contentPlaylist->id,
            'actual_watch_seconds' => 720,
            'duration_seconds'     => 720,
            'progress'             => 100,
            'finish'               => '1',
        ]);

        // Expected total = 0.1 + 0.2 = 0.3
        $hours = $this->actualHourService->getTotalLearningHours($this->user->id);
        $this->assertEquals(0.3, $hours);
    }

    /**
     * 5. Ongoing content is NOT counted in completed learning hours.
     */
    public function test_ongoing_content_is_not_counted(): void
    {
        $content = Content::create([
            'title'             => 'Ongoing Video',
            'slug'              => 'ongoing-' . rand(1000, 9999),
            'type'              => 'Video',
            'classification_id' => $this->classification->id,
            'duration'          => '00:20:00',
            'active'            => 'Active',
            'playlistonly'      => 'N',
        ]);

        // progress 50%, finish = '0'
        ContentUser::create([
            'user_id'              => $this->user->id,
            'content_id'           => $content->id,
            'actual_watch_seconds' => 600,
            'duration_seconds'     => 1200,
            'progress'             => 50,
            'finish'               => '0',
        ]);

        $hours = $this->actualHourService->getTotalLearningHours($this->user->id);
        $this->assertSame(0.0, $hours);
    }

    /**
     * 6. Deleted content is excluded.
     */
    public function test_deleted_content_is_excluded(): void
    {
        $content = Content::create([
            'title'             => 'Deleted Video',
            'slug'              => 'deleted-' . rand(1000, 9999),
            'type'              => 'Video',
            'classification_id' => $this->classification->id,
            'duration'          => '00:10:00',
            'active'            => 'Active',
            'playlistonly'      => 'N',
            'deleted_at'        => now(),
        ]);

        ContentUser::create([
            'user_id'              => $this->user->id,
            'content_id'           => $content->id,
            'actual_watch_seconds' => 600,
            'duration_seconds'     => 600,
            'progress'             => 100,
            'finish'               => '1',
        ]);

        $hours = $this->actualHourService->getTotalLearningHours($this->user->id);
        $this->assertSame(0.0, $hours);
    }

    /**
     * 7. Decimal precision is rounded to 2 decimal places.
     */
    public function test_decimal_precision_is_rounded_to_two_decimals(): void
    {
        // 1357 seconds = 0.376944... hours -> rounds to 0.38
        $content = Content::create([
            'title'             => 'Odd Duration Video',
            'slug'              => 'odd-' . rand(1000, 9999),
            'type'              => 'Video',
            'classification_id' => $this->classification->id,
            'duration'          => '00:22:37',
            'active'            => 'Active',
            'playlistonly'      => 'N',
        ]);

        ContentUser::create([
            'user_id'              => $this->user->id,
            'content_id'           => $content->id,
            'actual_watch_seconds' => 1357,
            'duration_seconds'     => 1357,
            'progress'             => 100,
            'finish'               => '1',
        ]);

        $hours = $this->actualHourService->getTotalLearningHours($this->user->id);
        $this->assertSame(0.38, $hours);
    }

    /**
     * 8. LearningAnalyticsService heroSummary uses canonical actual LH.
     */
    public function test_home_analytics_and_hero_summary_use_canonical_actual_lh(): void
    {
        $content = Content::create([
            'title'             => 'Hero Analytics Video',
            'slug'              => 'hero-vid-' . rand(1000, 9999),
            'type'              => 'Video',
            'classification_id' => $this->classification->id,
            'duration'          => '00:30:00', // Catalog 30m -> old bucket was 0.5h
            'learning_hours'    => 2.5, // Catalog LH: MUST NOT BE USED
            'active'            => 'Active',
            'playlistonly'      => 'N',
        ]);

        // Actual watch: 360 seconds = 0.1 hours
        ContentUser::create([
            'user_id'              => $this->user->id,
            'content_id'           => $content->id,
            'actual_watch_seconds' => 360,
            'duration_seconds'     => 1800,
            'progress'             => 100,
            'finish'               => '1',
        ]);

        $summary = $this->analyticsService->heroSummary($this->user->id);

        $this->assertEquals(0.1, $summary['learningHours']);
        $this->assertEquals(1, $summary['completedCourse']);
        $this->assertEquals(0, $summary['ongoingCourse']);
    }

    /**
     * 9. Total course count matches active catalog contents and excludes inactive.
     */
    public function test_hero_summary_total_course_matches_active_catalog_count(): void
    {
        $initialActiveCount = Content::where('active', 'Active')->count();

        // Create 1 active content
        Content::create([
            'title'             => 'Active Test Content',
            'slug'              => 'active-test-' . rand(1000, 9999),
            'type'              => 'Video',
            'classification_id' => $this->classification->id,
            'duration'          => '00:10:00',
            'active'            => 'Active',
            'playlistonly'      => 'N',
        ]);

        // Create 1 inactive content
        Content::create([
            'title'             => 'Inactive Test Content',
            'slug'              => 'inactive-test-' . rand(1000, 9999),
            'type'              => 'Video',
            'classification_id' => $this->classification->id,
            'duration'          => '00:10:00',
            'active'            => 'Inactive',
            'playlistonly'      => 'N',
        ]);

        $summary = $this->analyticsService->heroSummary($this->user->id);

        $this->assertSame($initialActiveCount + 1, $summary['totalCourse']);
    }

    /**
     * 10. Completed count combines standalone completed content and completed playlists.
     */
    public function test_hero_summary_completed_course_combines_standalone_and_playlist(): void
    {
        // 1 Standalone completed
        $standalone = Content::create([
            'title'             => 'Standalone Completed',
            'slug'              => 'std-comp-' . rand(1000, 9999),
            'type'              => 'Video',
            'classification_id' => $this->classification->id,
            'duration'          => '00:10:00',
            'active'            => 'Active',
            'playlistonly'      => 'N',
        ]);
        ContentUser::create([
            'user_id'              => $this->user->id,
            'content_id'           => $standalone->id,
            'actual_watch_seconds' => 600,
            'duration_seconds'     => 600,
            'progress'             => 100,
            'finish'               => '1',
        ]);

        // 1 Completed Playlist
        $plan = TrainingPlan::create([
            'title'                  => 'Completed Plan Canonical',
            'slug'                   => 'plan-comp-canon-' . rand(1000, 9999),
            'active'                 => 'Active',
            'total_duration_seconds' => 600,
            'learning_hours'         => 1.0,
        ]);
        $playlistContent = Content::create([
            'title'             => 'Playlist Mod 1',
            'slug'              => 'pl-mod-1-' . rand(1000, 9999),
            'type'              => 'Video',
            'classification_id' => $this->classification->id,
            'duration'          => '00:10:00',
            'active'            => 'Active',
            'playlistonly'      => 'Y',
        ]);
        TrainingPlanDetail::create([
            'trainingplan_id' => $plan->id,
            'content_id'      => $playlistContent->id,
            'content_order'   => 1,
        ]);
        PlaylistContentUser::create([
            'user_id'              => $this->user->id,
            'trainingplan_id'      => $plan->id,
            'content_id'           => $playlistContent->id,
            'actual_watch_seconds' => 600,
            'duration_seconds'     => 600,
            'progress'             => 100,
            'finish'               => '1',
        ]);

        $summary = $this->analyticsService->heroSummary($this->user->id);

        $this->assertEquals(2, $summary['completedCourse']);
    }

    /**
     * 11. Home dashboard passes totalCourse to view and renders metrics correctly.
     */
    public function test_home_dashboard_passes_canonical_metrics_to_view(): void
    {
        $response = $this->actingAs($this->user)->get('/');

        $response->assertStatus(200);
        $response->assertViewHas('totalCourse');
        $response->assertViewHas('completedCourse');
        $response->assertViewHas('ongoingCourse');
        $response->assertViewHas('learningHours');

        $activeCount = Content::where('active', 'Active')->count();
        $this->assertSame($activeCount, $response->viewData('totalCourse'));
    }

    /**
     * 12. Home On Going strictly equals My Learning Ongoing Content count.
     * Ongoing Playlist and MOOC are excluded from Home On Going.
     */
    public function test_home_ongoing_strictly_equals_my_learning_ongoing_content_count(): void
    {
        // 1 Standalone Ongoing Content (Video, 50%)
        $content = Content::create([
            'title'             => 'Standalone Ongoing Video',
            'slug'              => 'std-ongoing-' . rand(1000, 9999),
            'type'              => 'Video',
            'classification_id' => $this->classification->id,
            'duration'          => '00:20:00',
            'active'            => 'Active',
            'playlistonly'      => 'N',
        ]);
        ContentUser::create([
            'user_id'              => $this->user->id,
            'content_id'           => $content->id,
            'actual_watch_seconds' => 600,
            'duration_seconds'     => 1200,
            'progress'             => 50,
            'finish'               => '0',
            'learning_status'      => 'in_progress',
        ]);

        // 1 Completed Content (Video, 100%, finish=1) -> MUST NOT be in ongoing
        $compContent = Content::create([
            'title'             => 'Completed Video',
            'slug'              => 'comp-vid-' . rand(1000, 9999),
            'type'              => 'Video',
            'classification_id' => $this->classification->id,
            'duration'          => '00:10:00',
            'active'            => 'Active',
            'playlistonly'      => 'N',
        ]);
        ContentUser::create([
            'user_id'              => $this->user->id,
            'content_id'           => $compContent->id,
            'actual_watch_seconds' => 600,
            'duration_seconds'     => 600,
            'progress'             => 100,
            'finish'               => '1',
        ]);

        // 1 Ongoing Playlist (2 items: 1 finished, 1 in progress -> 50% playlist progress)
        // MUST NOT be added to Home On Going
        $plan = TrainingPlan::create([
            'title'                  => 'Ongoing Playlist Package',
            'slug'                   => 'ongoing-plan-' . rand(1000, 9999),
            'active'                 => 'Active',
            'total_duration_seconds' => 1200,
            'learning_hours'         => 2.0,
        ]);
        $plChild1 = Content::create([
            'title'             => 'Playlist Inner Item 1',
            'slug'              => 'pl-inner-1-' . rand(1000, 9999),
            'type'              => 'Video',
            'classification_id' => $this->classification->id,
            'duration'          => '00:10:00',
            'active'            => 'Active',
            'playlistonly'      => 'Y',
        ]);
        $plChild2 = Content::create([
            'title'             => 'Playlist Inner Item 2',
            'slug'              => 'pl-inner-2-' . rand(1000, 9999),
            'type'              => 'Video',
            'classification_id' => $this->classification->id,
            'duration'          => '00:10:00',
            'active'            => 'Active',
            'playlistonly'      => 'Y',
        ]);
        TrainingPlanDetail::create([
            'trainingplan_id' => $plan->id,
            'content_id'      => $plChild1->id,
            'content_order'   => 1,
        ]);
        TrainingPlanDetail::create([
            'trainingplan_id' => $plan->id,
            'content_id'      => $plChild2->id,
            'content_order'   => 2,
        ]);
        PlaylistContentUser::create([
            'user_id'              => $this->user->id,
            'trainingplan_id'      => $plan->id,
            'content_id'           => $plChild1->id,
            'actual_watch_seconds' => 600,
            'duration_seconds'     => 600,
            'progress'             => 100,
            'finish'               => '1',
        ]);
        PlaylistContentUser::create([
            'user_id'              => $this->user->id,
            'trainingplan_id'      => $plan->id,
            'content_id'           => $plChild2->id,
            'actual_watch_seconds' => 300,
            'duration_seconds'     => 600,
            'progress'             => 50,
            'finish'               => '0',
        ]);

        // 1 Ongoing MOOC (progress 50%) -> MUST NOT be added to Home On Going
        $moocContent = Content::create([
            'title'             => 'Ongoing MOOC Course',
            'slug'              => 'mooc-course-' . rand(1000, 9999),
            'type'              => 'MOOC',
            'classification_id' => $this->classification->id,
            'duration'          => '00:30:00',
            'active'            => 'Active',
            'playlistonly'      => 'N',
        ]);
        ContentUser::create([
            'user_id'              => $this->user->id,
            'content_id'           => $moocContent->id,
            'actual_watch_seconds' => 900,
            'duration_seconds'     => 1800,
            'progress'             => 50,
            'finish'               => '0',
            'learning_status'      => 'in_progress',
        ]);

        // Canonical counts
        $ongoingContentCount = $this->ongoingContentService->ongoingContentCount($this->user->id);
        $summary = $this->analyticsService->heroSummary($this->user->id);

        // 1. My Learning Ongoing Content count is exactly 1
        $this->assertSame(1, $ongoingContentCount);

        // 2. Home On Going strictly matches My Learning Ongoing Content count
        $this->assertSame($ongoingContentCount, $summary['ongoingCourse']);
        $this->assertSame(1, $summary['ongoingCourse']);

        // 3. Ongoing playlist is NOT added to Home (it remains separate in OngoingPlaylistService)
        $userPlaylists = $this->ongoingPlaylistService->userPlaylists($this->user->id);
        $playlistFilters = [
            'search' => '', 'status' => 'all', 'year' => 'all', 'activity_sort' => 'newest_activity',
            'progress_sort' => 'all', 'quick_status' => 'all', 'months' => [], 'progress_buckets' => []
        ];
        $ongoingPlaylistCount = $this->ongoingPlaylistService->cards($userPlaylists, $playlistFilters)->count();
        $this->assertSame(1, $ongoingPlaylistCount);
        $this->assertNotSame($ongoingContentCount + $ongoingPlaylistCount, $summary['ongoingCourse']);

        // 4. Completed is canonical (1 completed content)
        $this->assertSame(1, $summary['completedCourse']);
    }

    /**
     * 13. Progress < 5% is excluded, while Progress >= 5% is included in Home On Going.
     */
    public function test_home_ongoing_threshold_requires_at_least_5_percent(): void
    {
        $content = Content::create([
            'title'             => 'Threshold Test Video',
            'slug'              => 'thresh-vid-' . rand(1000, 9999),
            'type'              => 'Video',
            'classification_id' => $this->classification->id,
            'duration'          => '00:20:00',
            'active'            => 'Active',
            'playlistonly'      => 'N',
        ]);

        $cu = ContentUser::create([
            'user_id'              => $this->user->id,
            'content_id'           => $content->id,
            'actual_watch_seconds' => 48,
            'duration_seconds'     => 1200,
            'progress'             => 4,
            'finish'               => '0',
            'learning_status'      => 'in_progress',
        ]);

        // Progress 4% -> MUST NOT be included
        $summary = $this->analyticsService->heroSummary($this->user->id);
        $this->assertSame(0, $summary['ongoingCourse']);

        // Update progress to 5% -> MUST be included
        $cu->update([
            'progress'             => 5,
            'actual_watch_seconds' => 60,
        ]);

        $summaryAfter = $this->analyticsService->heroSummary($this->user->id);
        $this->assertSame(1, $summaryAfter['ongoingCourse']);
    }

    /**
     * 14. Playlist child content is not counted as standalone ongoing Content in Home On Going.
     */
    public function test_home_ongoing_excludes_playlist_child_content(): void
    {
        $plan = TrainingPlan::create([
            'title'                  => 'Parent Training Plan',
            'slug'                   => 'parent-plan-' . rand(1000, 9999),
            'active'                 => 'Active',
            'total_duration_seconds' => 600,
        ]);

        $childContent = Content::create([
            'title'             => 'Child Content in Plan',
            'slug'              => 'child-in-plan-' . rand(1000, 9999),
            'type'              => 'Video',
            'classification_id' => $this->classification->id,
            'duration'          => '00:10:00',
            'active'            => 'Active',
            'playlistonly'      => 'Y',
        ]);

        TrainingPlanDetail::create([
            'trainingplan_id' => $plan->id,
            'content_id'      => $childContent->id,
            'content_order'   => 1,
        ]);

        ContentUser::create([
            'user_id'              => $this->user->id,
            'content_id'           => $childContent->id,
            'actual_watch_seconds' => 300,
            'duration_seconds'     => 600,
            'progress'             => 50,
            'finish'               => '0',
            'learning_status'      => 'in_progress',
        ]);

        $summary = $this->analyticsService->heroSummary($this->user->id);
        $this->assertSame(0, $summary['ongoingCourse']);
    }

    /**
     * 15. Cross-check with user 936 dataset:
     * Home On Going = 15, Playlist = 16 (untouched and not added to Home),
     * Completed = 71, Total Course = 274, LH = 17.13.
     */
    public function test_user_936_dataset_canonical_cross_check(): void
    {
        $user936 = User::find(936);
        if (!$user936) {
            $this->markTestSkipped('User 936 not present in this database environment.');
        }

        $summary = $this->analyticsService->heroSummary(936);
        $contentCount = $this->ongoingContentService->ongoingContentCount(936);

        // Home On Going strictly matches My Learning Ongoing Content
        $this->assertSame(15, $contentCount);
        $this->assertSame(15, $summary['ongoingCourse']);

        // Other canonical metrics for user 936
        $this->assertSame(71, $summary['completedCourse']);
        $this->assertSame(274, $summary['totalCourse']);
        $this->assertEquals(17.13, $summary['learningHours']);

        // Ongoing Playlist count remains 16 and is NOT included in Home On Going
        $playlistFilters = [
            'search' => '', 'status' => 'all', 'year' => 'all', 'activity_sort' => 'newest_activity',
            'progress_sort' => 'all', 'quick_status' => 'all', 'months' => [], 'progress_buckets' => []
        ];
        $userPlaylists = $this->ongoingPlaylistService->userPlaylists(936);
        $playlistCount = $this->ongoingPlaylistService->cards($userPlaylists, $playlistFilters)->count();
        $this->assertSame(16, $playlistCount);
    }
}

