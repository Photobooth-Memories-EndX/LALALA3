<?php

namespace Tests\Feature;

use App\Models\Classification;
use App\Models\Content;
use App\Models\ContentUser;
use App\Models\PlaylistContentUser;
use App\Models\TrainingPlan;
use App\Models\TrainingPlanDetail;
use App\Models\User;
use App\Services\Learning\LearningRecordLearningHourService;
use App\Services\Learning\LearningRecordRecognizedDurationService;
use App\Support\DurationFormatter;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Str;
use Tests\TestCase;

class LearningRecordGlobalTwoTimesDurationCapTest extends TestCase
{
    use DatabaseTransactions;

    protected User $user;
    protected Classification $classification;
    protected LearningRecordLearningHourService $hourService;
    protected LearningRecordRecognizedDurationService $recognizedService;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create([
            'name'       => 'Cap Tester ' . rand(1000, 9999),
            'id_emp'     => 'EMP-CAP-' . rand(1000, 9999),
            'team'       => 'Engineering',
            'department' => 'Technology',
            'division'   => 'LMS',
            'ssohash'    => 'hash-cap-' . Str::uuid(),
            'sap_code'   => 'SAP-CAP-' . rand(1000, 9999),
            'role'       => 1,
            'active'     => 1,
        ]);

        $this->classification = Classification::firstOrCreate(
            ['classification_name' => 'Cap Classification'],
            ['slug' => 'cap-classification-' . rand(1000, 9999)]
        );

        $this->hourService = app(LearningRecordLearningHourService::class);
        $this->recognizedService = app(LearningRecordRecognizedDurationService::class);
    }

    /**
     * TC-01: Standalone Single Watch (1x)
     * Video 4m (240s). User watches 4m.
     * Recognized: 4m (240s).
     */
    public function test_tc01_standalone_single_watch(): void
    {
        $content = Content::create([
            'title'             => 'TC01 Video',
            'slug'              => 'tc01-' . rand(1000, 9999),
            'type'              => 'Video',
            'classification_id' => $this->classification->id,
            'duration'          => '00:04:00', // 240s
            'active'            => 'Active',
            'playlistonly'      => 'N',
        ]);

        $cu = ContentUser::create([
            'user_id'              => $this->user->id,
            'content_id'           => $content->id,
            'actual_watch_seconds' => 240,
            'duration_seconds'     => 240,
            'progress'             => 100,
            'finish'               => '1',
            'status'               => 'non mandatory',
        ]);

        $sec = $this->hourService->calculateContentRecognizedSeconds($cu);
        $this->assertEquals(240, $sec);
        $this->assertEquals('00:04:00', DurationFormatter::fromSeconds($sec));

        $totalHours = $this->hourService->getTotalLearningHours($this->user->id);
        $this->assertEquals(0.07, $totalHours); // round(240/3600, 2) = 0.07
    }

    /**
     * TC-02: Standalone Rewatch (2x Cap Reach)
     * Video 4m (240s). User watches 8m (480s).
     * Recognized: 8m (480s).
     */
    public function test_tc02_standalone_rewatch_reaches_two_times_cap(): void
    {
        $content = Content::create([
            'title'             => 'TC02 Video',
            'slug'              => 'tc02-' . rand(1000, 9999),
            'type'              => 'Video',
            'classification_id' => $this->classification->id,
            'duration'          => '00:04:00', // 240s
            'active'            => 'Active',
            'playlistonly'      => 'N',
        ]);

        $cu = ContentUser::create([
            'user_id'              => $this->user->id,
            'content_id'           => $content->id,
            'actual_watch_seconds' => 480, // 2x
            'duration_seconds'     => 240,
            'progress'             => 100,
            'finish'               => '1',
            'status'               => 'non mandatory',
        ]);

        $sec = $this->hourService->calculateContentRecognizedSeconds($cu);
        $this->assertEquals(480, $sec);
        $this->assertEquals('00:08:00', DurationFormatter::fromSeconds($sec));
    }

    /**
     * TC-03: Standalone Over-Watch (Hard Cap at 2x)
     * Video 4m (240s). User watches 12m (720s, 3x).
     * Recognized: Hard-capped at 8m (480s).
     */
    public function test_tc03_standalone_overwatch_hard_capped_at_two_times(): void
    {
        $content = Content::create([
            'title'             => 'TC03 Video',
            'slug'              => 'tc03-' . rand(1000, 9999),
            'type'              => 'Video',
            'classification_id' => $this->classification->id,
            'duration'          => '00:04:00', // 240s
            'active'            => 'Active',
            'playlistonly'      => 'N',
        ]);

        $cu = ContentUser::create([
            'user_id'              => $this->user->id,
            'content_id'           => $content->id,
            'actual_watch_seconds' => 720, // 3x
            'duration_seconds'     => 240,
            'progress'             => 100,
            'finish'               => '1',
            'status'               => 'non mandatory',
        ]);

        $sec = $this->hourService->calculateContentRecognizedSeconds($cu);
        $this->assertEquals(480, $sec);
        $this->assertEquals('00:08:00', DurationFormatter::fromSeconds($sec));
    }

    /**
     * TC-04: Standalone + Playlist Module (No Overlap, Normal)
     * Content A (240s) watched direct 240s.
     * Content B (360s) in Playlist watched 360s.
     * Total = 600s (10m).
     */
    public function test_tc04_standalone_and_playlist_distinct_content(): void
    {
        $cA = Content::create([
            'title'             => 'TC04 Content A',
            'slug'              => 'tc04-a-' . rand(1000, 9999),
            'type'              => 'Video',
            'classification_id' => $this->classification->id,
            'duration'          => '00:04:00', // 240s
            'active'            => 'Active',
            'playlistonly'      => 'N',
        ]);
        ContentUser::create([
            'user_id'              => $this->user->id,
            'content_id'           => $cA->id,
            'actual_watch_seconds' => 240,
            'duration_seconds'     => 240,
            'progress'             => 100,
            'finish'               => '1',
            'status'               => 'non mandatory',
        ]);

        $cB = Content::create([
            'title'             => 'TC04 Content B',
            'slug'              => 'tc04-b-' . rand(1000, 9999),
            'type'              => 'Video',
            'classification_id' => $this->classification->id,
            'duration'          => '00:06:00', // 360s
            'active'            => 'Active',
            'playlistonly'      => 'Y',
        ]);
        $plan = TrainingPlan::create([
            'title'                  => 'TC04 Plan',
            'slug'                   => 'tc04-plan-' . rand(1000, 9999),
            'active'                 => 'Active',
            'total_duration_seconds' => 360,
        ]);
        TrainingPlanDetail::create(['trainingplan_id' => $plan->id, 'content_id' => $cB->id, 'content_order' => 1]);
        PlaylistContentUser::create([
            'user_id'              => $this->user->id,
            'trainingplan_id'      => $plan->id,
            'content_id'           => $cB->id,
            'actual_watch_seconds' => 360,
            'duration_seconds'     => 360,
            'progress'             => 100,
            'finish'               => '1',
        ]);

        $totalHours = $this->hourService->getTotalLearningHours($this->user->id);
        // (240s + 360s) = 600s = 0.1667h -> round(0.0667 + 0.1000, 2) = 0.17h
        $this->assertEquals(0.17, $totalHours);
    }

    /**
     * TC-05: Cross-Table Overlap (Direct 1x + Playlist 1x = 2x Global Cap)
     * Content A (240s). User watched direct 240s AND in Playlist 240s.
     * Playlist gets 240s (00:04:00).
     * Direct gets 240s (00:04:00).
     * Total Recognized Duration for Content A across both = 480s (00:08:00 = 2x).
     */
    public function test_tc05_cross_table_overlap_direct_and_playlist_sums_to_two_times(): void
    {
        $content = Content::create([
            'title'             => 'TC05 Overlap Content',
            'slug'              => 'tc05-ov-' . rand(1000, 9999),
            'type'              => 'Video',
            'classification_id' => $this->classification->id,
            'duration'          => '00:04:00', // 240s
            'active'            => 'Active',
            'playlistonly'      => 'N',
        ]);

        $plan = TrainingPlan::create([
            'title'                  => 'TC05 Plan',
            'slug'                   => 'tc05-plan-' . rand(1000, 9999),
            'active'                 => 'Active',
            'total_duration_seconds' => 240,
        ]);
        TrainingPlanDetail::create(['trainingplan_id' => $plan->id, 'content_id' => $content->id, 'content_order' => 1]);

        // Playlist PCU = 240s
        PlaylistContentUser::create([
            'user_id'              => $this->user->id,
            'trainingplan_id'      => $plan->id,
            'content_id'           => $content->id,
            'actual_watch_seconds' => 240,
            'duration_seconds'     => 240,
            'progress'             => 100,
            'finish'               => '1',
        ]);

        // Direct CU = 240s
        $cu = ContentUser::create([
            'user_id'              => $this->user->id,
            'content_id'           => $content->id,
            'actual_watch_seconds' => 240,
            'duration_seconds'     => 240,
            'progress'             => 100,
            'finish'               => '1',
            'status'               => 'non mandatory',
        ]);

        $contentAllocations = [];
        $plSec = $this->hourService->calculatePlaylistsRecognizedSecondsBatch($this->user->id, collect([$plan]), $contentAllocations)->get($plan->id);
        $this->assertEquals(240, $plSec);

        $cuSec = $this->hourService->calculateContentRecognizedSeconds($cu, $contentAllocations);
        $this->assertEquals(240, $cuSec);

        // Sum = 480s (8m, exactly 2x)
        $this->assertEquals(480, $plSec + $cuSec);

        $totalHours = $this->hourService->getTotalLearningHours($this->user->id);
        // round(240/3600, 4) + round(240/3600, 4) = 0.0667 + 0.0667 = 0.1334 -> 0.13h
        $this->assertEquals(0.13, $totalHours);
    }

    /**
     * TC-06: Cross-Table Overlap (Direct 2x + Playlist 1x = Over-Watch Clamped to 2x)
     * Direct: 480s (2x).
     * Playlist: 240s (1x). Total actual watch = 720s (3x).
     * Playlist allocates 240s. Direct gets remaining 240s (480s - 240s).
     * Total = 480s (2x, never exceeds 8m).
     */
    public function test_tc06_cross_table_overlap_direct_two_times_playlist_one_time_clamped(): void
    {
        $content = Content::create([
            'title'             => 'TC06 Over-Watch Content',
            'slug'              => 'tc06-ov-' . rand(1000, 9999),
            'type'              => 'Video',
            'classification_id' => $this->classification->id,
            'duration'          => '00:04:00', // 240s
            'active'            => 'Active',
            'playlistonly'      => 'N',
        ]);

        $plan = TrainingPlan::create([
            'title'                  => 'TC06 Plan',
            'slug'                   => 'tc06-plan-' . rand(1000, 9999),
            'active'                 => 'Active',
            'total_duration_seconds' => 240,
        ]);
        TrainingPlanDetail::create(['trainingplan_id' => $plan->id, 'content_id' => $content->id, 'content_order' => 1]);

        PlaylistContentUser::create([
            'user_id'              => $this->user->id,
            'trainingplan_id'      => $plan->id,
            'content_id'           => $content->id,
            'actual_watch_seconds' => 240,
            'duration_seconds'     => 240,
            'progress'             => 100,
            'finish'               => '1',
        ]);

        $cu = ContentUser::create([
            'user_id'              => $this->user->id,
            'content_id'           => $content->id,
            'actual_watch_seconds' => 480, // watched twice directly
            'duration_seconds'     => 240,
            'progress'             => 100,
            'finish'               => '1',
            'status'               => 'non mandatory',
        ]);

        $contentAllocations = [];
        $plSec = $this->hourService->calculatePlaylistsRecognizedSecondsBatch($this->user->id, collect([$plan]), $contentAllocations)->get($plan->id);
        $this->assertEquals(240, $plSec);

        $cuSec = $this->hourService->calculateContentRecognizedSeconds($cu, $contentAllocations);
        $this->assertEquals(240, $cuSec);

        // Sum = exactly 480s (2x duration, not 720s)
        $this->assertEquals(480, $plSec + $cuSec);

        $totalHours = $this->hourService->getTotalLearningHours($this->user->id);
        $this->assertEquals(0.13, $totalHours);
    }

    /**
     * TC-07: Multi-Playlist Overlap (Content in 3 Playlists)
     * Content A (240s, 2x cap = 480s) in 3 completed playlists.
     * Playlist 1 gets 240s. Playlist 2 gets 240s. Playlist 3 gets 0s.
     * Total across all 3 playlists = exactly 480s (2x cap).
     */
    public function test_tc07_multi_playlist_overlap_capped_at_two_times(): void
    {
        $content = Content::create([
            'title'             => 'TC07 Content in 3 Playlists',
            'slug'              => 'tc07-c3-' . rand(1000, 9999),
            'type'              => 'Video',
            'classification_id' => $this->classification->id,
            'duration'          => '00:04:00', // 240s
            'active'            => 'Active',
            'playlistonly'      => 'Y',
        ]);

        $p1 = TrainingPlan::create(['title' => 'P1', 'slug' => 'p1-' . rand(1000, 9999), 'active' => 'Active', 'total_duration_seconds' => 240]);
        $p2 = TrainingPlan::create(['title' => 'P2', 'slug' => 'p2-' . rand(1000, 9999), 'active' => 'Active', 'total_duration_seconds' => 240]);
        $p3 = TrainingPlan::create(['title' => 'P3', 'slug' => 'p3-' . rand(1000, 9999), 'active' => 'Active', 'total_duration_seconds' => 240]);

        TrainingPlanDetail::create(['trainingplan_id' => $p1->id, 'content_id' => $content->id, 'content_order' => 1]);
        TrainingPlanDetail::create(['trainingplan_id' => $p2->id, 'content_id' => $content->id, 'content_order' => 1]);
        TrainingPlanDetail::create(['trainingplan_id' => $p3->id, 'content_id' => $content->id, 'content_order' => 1]);

        PlaylistContentUser::create(['user_id' => $this->user->id, 'trainingplan_id' => $p1->id, 'content_id' => $content->id, 'actual_watch_seconds' => 240, 'duration_seconds' => 240, 'progress' => 100, 'finish' => '1']);
        PlaylistContentUser::create(['user_id' => $this->user->id, 'trainingplan_id' => $p2->id, 'content_id' => $content->id, 'actual_watch_seconds' => 240, 'duration_seconds' => 240, 'progress' => 100, 'finish' => '1']);
        PlaylistContentUser::create(['user_id' => $this->user->id, 'trainingplan_id' => $p3->id, 'content_id' => $content->id, 'actual_watch_seconds' => 240, 'duration_seconds' => 240, 'progress' => 100, 'finish' => '1']);

        $contentAllocations = [];
        $batch = $this->hourService->calculatePlaylistsRecognizedSecondsBatch($this->user->id, collect([$p1, $p2, $p3]), $contentAllocations);

        $this->assertEquals(240, $batch->get($p1->id));
        $this->assertEquals(240, $batch->get($p2->id));
        $this->assertEquals(0, $batch->get($p3->id));

        $this->assertEquals(480, $batch->sum());
    }

    /**
     * TC-08: Duplicate ContentUser Records Protection
     * User has 2 duplicate completed rows for Content A (240s).
     * Row 1: 240s. Row 2: 240s.
     * Row 3: 240s -> gets 0s.
     * Sum across all rows = 480s (2x cap).
     */
    public function test_tc08_duplicate_content_users_protection(): void
    {
        $content = Content::create([
            'title'             => 'TC08 Dup Content',
            'slug'              => 'tc08-dup-' . rand(1000, 9999),
            'type'              => 'Video',
            'classification_id' => $this->classification->id,
            'duration'          => '00:04:00', // 240s
            'active'            => 'Active',
            'playlistonly'      => 'N',
        ]);

        $cu1 = ContentUser::create(['user_id' => $this->user->id, 'content_id' => $content->id, 'actual_watch_seconds' => 240, 'duration_seconds' => 240, 'progress' => 100, 'finish' => '1', 'status' => 'non mandatory']);
        $cu2 = ContentUser::create(['user_id' => $this->user->id, 'content_id' => $content->id, 'actual_watch_seconds' => 240, 'duration_seconds' => 240, 'progress' => 100, 'finish' => '1', 'status' => 'non mandatory']);
        $cu3 = ContentUser::create(['user_id' => $this->user->id, 'content_id' => $content->id, 'actual_watch_seconds' => 240, 'duration_seconds' => 240, 'progress' => 100, 'finish' => '1', 'status' => 'non mandatory']);

        $contentAllocations = [];
        $sec1 = $this->hourService->calculateContentRecognizedSeconds($cu1, $contentAllocations);
        $sec2 = $this->hourService->calculateContentRecognizedSeconds($cu2, $contentAllocations);
        $sec3 = $this->hourService->calculateContentRecognizedSeconds($cu3, $contentAllocations);

        $this->assertEquals(240, $sec1);
        $this->assertEquals(240, $sec2);
        $this->assertEquals(0, $sec3);
        $this->assertEquals(480, $sec1 + $sec2 + $sec3);
    }

    /**
     * TC-09: Document Re-reading Baseline Seeding (No Degradation)
     * Legacy completed document (15m = 900s, actual_watch_seconds = 0, finish = '1').
     * User reopens and sends 15s heartbeat.
     * actual_watch_seconds becomes 915s (not degraded to 15s).
     */
    public function test_tc09_document_rereading_baseline_seeding(): void
    {
        $doc = Content::create([
            'title'             => 'TC09 Document',
            'slug'              => 'tc09-doc-' . rand(1000, 9999),
            'type'              => 'Document',
            'classification_id' => $this->classification->id,
            'duration'          => '00:15:00', // 900s
            'page_duration'     => 0,
            'total_page'        => 10,
            'active'            => 'Active',
            'playlistonly'      => 'N',
        ]);

        $cu = ContentUser::create([
            'user_id'              => $this->user->id,
            'content_id'           => $doc->id,
            'current_time'         => 0,
            'actual_watch_seconds' => 0, // Legacy row
            'duration_seconds'     => 900,
            'last_page'            => 10,
            'progress'             => 100,
            'finish'               => '1',
            'status'               => 'non mandatory',
        ]);

        $response = $this->actingAs($this->user)->postJson(route('content.user.progress.update'), [
            'content_id'    => $doc->id,
            'type'          => 'document',
            'delta_seconds' => 15,
            'heartbeat_id'  => (string) Str::uuid(),
            'last_page'     => 10,
            'total_pages'   => 10,
        ]);

        $response->assertOk();
        $cu->refresh();

        // Must baseline to 900 + 15 = 915s, NOT 15s!
        $this->assertEquals(915, $cu->actual_watch_seconds);
        $this->assertGreaterThanOrEqual(900, $cu->actual_watch_seconds);
    }

    /**
     * TC-10: Write-Path Heartbeat Cross-Table Capping
     * Video 4m (240s, 2x cap = 480s).
     * User already watched 240s in Playlist.
     * Direct heartbeat arrives when user has 230s in CU, delta is 30s (230 + 30 = 260s > 240s remaining quota).
     * CU actual_watch_seconds must be clamped at 240s, and is_capped is true.
     */
    public function test_tc10_write_path_cross_table_capping(): void
    {
        $content = Content::create([
            'title'             => 'TC10 Video',
            'slug'              => 'tc10-vid-' . rand(1000, 9999),
            'type'              => 'Video',
            'classification_id' => $this->classification->id,
            'duration'          => '00:04:00', // 240s
            'active'            => 'Active',
            'playlistonly'      => 'N',
        ]);

        $plan = TrainingPlan::create([
            'title'                  => 'TC10 Plan',
            'slug'                   => 'tc10-plan-' . rand(1000, 9999),
            'active'                 => 'Active',
            'total_duration_seconds' => 240,
        ]);
        TrainingPlanDetail::create(['trainingplan_id' => $plan->id, 'content_id' => $content->id, 'content_order' => 1]);

        PlaylistContentUser::create([
            'user_id'              => $this->user->id,
            'trainingplan_id'      => $plan->id,
            'content_id'           => $content->id,
            'actual_watch_seconds' => 240,
            'duration_seconds'     => 240,
            'progress'             => 100,
            'finish'               => '1',
        ]);

        $cu = ContentUser::create([
            'user_id'              => $this->user->id,
            'content_id'           => $content->id,
            'actual_watch_seconds' => 230,
            'current_time'         => 230,
            'duration_seconds'     => 240,
            'progress'             => 95,
            'finish'               => '0',
            'status'               => 'non mandatory',
        ]);

        $response = $this->actingAs($this->user)->postJson(route('content.user.progress.update'), [
            'content_id'       => $content->id,
            'type'             => 'video',
            'delta_seconds'    => 30, // 230 + 30 = 260, but max cap is 480 - 240 = 240
            'heartbeat_id'     => (string) Str::uuid(),
            'current_time'     => 240,
            'duration_seconds' => 240,
            'status'           => 'completed',
        ]);

        $response->assertOk();
        $response->assertJson([
            'success'              => true,
            'actual_watch_seconds' => 240, // Clamped!
            'is_capped'            => true,
        ]);

        $cu->refresh();
        $this->assertEquals(240, $cu->actual_watch_seconds);
    }

    /**
     * TC-11: User 5 TP 32 Parity Verification
     * Both services must return the exact same duration for Training Plan 32.
     */
    public function test_tc11_user_5_tp_32_services_parity(): void
    {
        $plan = TrainingPlan::find(32);
        if (!$plan) {
            $this->markTestSkipped('Training Plan 32 not found in current database.');
        }

        $lh = $this->hourService->calculatePlaylistsRecognizedSecondsBatch(5, collect([$plan]))->get(32);
        $rd = $this->recognizedService->resolvePlaylistsBatch(5, collect([$plan]))->get(32);

        $this->assertEquals($lh, $rd);
        $this->assertEquals(1484, $lh);
        $this->assertEquals('00:24:44', DurationFormatter::fromSeconds($lh));
        $this->assertEquals('00:24:44', DurationFormatter::fromSeconds($rd));
    }
}
