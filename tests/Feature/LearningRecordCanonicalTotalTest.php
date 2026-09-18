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

    protected function setUp(): void
    {
        parent::setUp();

        $this->actualHourService = app(LearningRecordLearningHourService::class);
        $this->analyticsService = app(LearningAnalyticsService::class);

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
}
