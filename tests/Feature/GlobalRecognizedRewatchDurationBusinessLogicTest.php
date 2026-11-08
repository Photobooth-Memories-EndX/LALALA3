<?php

namespace Tests\Feature;

use App\Exports\ExportMyLearningRecord;
use App\Http\Controllers\LearningRecordController;
use App\Models\Classification;
use App\Models\Content;
use App\Models\ContentUser;
use App\Models\PlaylistContentUser;
use App\Models\TrainingPlan;
use App\Models\TrainingPlanDetail;
use App\Models\User;
use App\Services\CertificateEligibilityService;
use App\Services\Learning\LearningRecordLearningHourService;
use App\Services\Learning\LearningRecordRecognizedDurationService;
use App\Support\DurationFormatter;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Str;
use Tests\TestCase;

class GlobalRecognizedRewatchDurationBusinessLogicTest extends TestCase
{
    use DatabaseTransactions;

    protected User $user;
    protected Classification $classification;
    protected LearningRecordRecognizedDurationService $recService;
    protected LearningRecordLearningHourService $hourService;
    protected LearningRecordController $recordController;
    protected CertificateEligibilityService $eligibilityService;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create([
            'name'       => 'Rewatch Tester ' . rand(1000, 9999),
            'id_emp'     => 'EMP-RW-' . rand(1000, 9999),
            'team'       => 'Engineering',
            'department' => 'Technology',
            'division'   => 'LMS',
            'ssohash'    => 'hash-rw-' . Str::uuid(),
            'sap_code'   => 'SAP-RW-' . rand(1000, 9999),
            'role'       => 1,
            'active'     => 1,
        ]);

        $this->classification = Classification::firstOrCreate(
            ['classification_name' => 'Rewatch Classification'],
            ['slug' => 'rewatch-classification-' . rand(1000, 9999)]
        );

        $this->recService = app(LearningRecordRecognizedDurationService::class);
        $this->hourService = app(LearningRecordLearningHourService::class);
        $this->recordController = app(LearningRecordController::class);
        $this->eligibilityService = app(CertificateEligibilityService::class);

        $this->actingAs($this->user);
    }

    private function createContent(int $durationSeconds, string $type = 'Video'): Content
    {
        return Content::create([
            'title'             => 'Content ' . rand(1000, 9999),
            'slug'              => 'content-' . Str::uuid(),
            'type'              => $type,
            'classification_id' => $this->classification->id,
            'duration'          => DurationFormatter::fromSeconds($durationSeconds),
            'active'            => 'Active',
            'playlistonly'      => 'N',
        ]);
    }

    /**
     * TC-01: Actual Watch 30s, Original 60s -> Recognized 0s (First Watch incomplete).
     */
    public function test_tc01_actual_watch_30s_original_60s_recognized_0s(): void
    {
        $content = $this->createContent(60);

        ContentUser::create([
            'user_id'              => $this->user->id,
            'content_id'           => $content->id,
            'actual_watch_seconds' => 30,
            'duration_seconds'     => 60,
            'progress'             => 50,
            'finish'               => '0',
            'status'               => 'non mandatory',
        ]);

        $recSeconds = $this->recService->resolveGlobalContentRecognizedSeconds($this->user->id, $content);
        $recDuration = $this->recService->resolveGlobalContentRecognizedDuration($this->user->id, $content);

        $this->assertSame(0, $recSeconds);
        $this->assertSame('00:00:00', $recDuration);
    }

    /**
     * TC-02: Actual Watch 60s, Original 60s -> Recognized 0s (First Watch completed).
     */
    public function test_tc02_actual_watch_60s_original_60s_recognized_0s(): void
    {
        $content = $this->createContent(60);

        ContentUser::create([
            'user_id'              => $this->user->id,
            'content_id'           => $content->id,
            'actual_watch_seconds' => 60,
            'duration_seconds'     => 60,
            'progress'             => 100,
            'finish'               => '1',
            'status'               => 'non mandatory',
        ]);

        $recSeconds = $this->recService->resolveGlobalContentRecognizedSeconds($this->user->id, $content);
        $recDuration = $this->recService->resolveGlobalContentRecognizedDuration($this->user->id, $content);

        $this->assertSame(0, $recSeconds);
        $this->assertSame('00:00:00', $recDuration);
    }

    /**
     * TC-03: Actual Watch 90s, Original 60s -> Recognized 30s (Rewatch begins).
     */
    public function test_tc03_actual_watch_90s_original_60s_recognized_30s(): void
    {
        $content = $this->createContent(60);

        ContentUser::create([
            'user_id'              => $this->user->id,
            'content_id'           => $content->id,
            'actual_watch_seconds' => 90,
            'duration_seconds'     => 60,
            'progress'             => 100,
            'finish'               => '1',
            'status'               => 'non mandatory',
        ]);

        $recSeconds = $this->recService->resolveGlobalContentRecognizedSeconds($this->user->id, $content);
        $recDuration = $this->recService->resolveGlobalContentRecognizedDuration($this->user->id, $content);

        $this->assertSame(30, $recSeconds);
        $this->assertSame('00:00:30', $recDuration);
    }

    /**
     * TC-04: Actual Watch 120s, Original 60s -> Recognized 60s (Maximum Rewatch reached).
     */
    public function test_tc04_actual_watch_120s_original_60s_recognized_60s(): void
    {
        $content = $this->createContent(60);

        ContentUser::create([
            'user_id'              => $this->user->id,
            'content_id'           => $content->id,
            'actual_watch_seconds' => 120,
            'duration_seconds'     => 60,
            'progress'             => 100,
            'finish'               => '1',
            'status'               => 'non mandatory',
        ]);

        $recSeconds = $this->recService->resolveGlobalContentRecognizedSeconds($this->user->id, $content);
        $recDuration = $this->recService->resolveGlobalContentRecognizedDuration($this->user->id, $content);

        $this->assertSame(60, $recSeconds);
        $this->assertSame('00:01:00', $recDuration);
    }

    /**
     * TC-05: Actual Watch 180s, Original 60s -> Recognized 60s (Capped at 1x duration).
     */
    public function test_tc05_actual_watch_180s_original_60s_recognized_60s(): void
    {
        $content = $this->createContent(60);

        ContentUser::create([
            'user_id'              => $this->user->id,
            'content_id'           => $content->id,
            'actual_watch_seconds' => 180,
            'duration_seconds'     => 60,
            'progress'             => 100,
            'finish'               => '1',
            'status'               => 'non mandatory',
        ]);

        $recSeconds = $this->recService->resolveGlobalContentRecognizedSeconds($this->user->id, $content);
        $recDuration = $this->recService->resolveGlobalContentRecognizedDuration($this->user->id, $content);

        $this->assertSame(60, $recSeconds);
        $this->assertSame('00:01:00', $recDuration);
    }

    /**
     * TC-06: Direct 40s + Playlist 20s, Original 60s -> Total Actual 60s -> Recognized 0s.
     */
    public function test_tc06_direct_40s_playlist_20s_original_60s_recognized_0s(): void
    {
        $content = $this->createContent(60);

        $plan = TrainingPlan::create([
            'title'                  => 'Plan TC06',
            'slug'                   => 'plan-tc06-' . Str::uuid(),
            'total_duration_seconds' => 60,
            'active'                 => 'Active',
        ]);
        TrainingPlanDetail::create(['trainingplan_id' => $plan->id, 'content_id' => $content->id, 'content_order' => 1]);

        PlaylistContentUser::create([
            'user_id'              => $this->user->id,
            'trainingplan_id'      => $plan->id,
            'content_id'           => $content->id,
            'actual_watch_seconds' => 20,
            'duration_seconds'     => 60,
            'progress'             => 33,
            'finish'               => '0',
        ]);

        ContentUser::create([
            'user_id'              => $this->user->id,
            'content_id'           => $content->id,
            'actual_watch_seconds' => 40,
            'duration_seconds'     => 60,
            'progress'             => 67,
            'finish'               => '0',
            'status'               => 'non mandatory',
        ]);

        $recSeconds = $this->recService->resolveGlobalContentRecognizedSeconds($this->user->id, $content);
        $recDuration = $this->recService->resolveGlobalContentRecognizedDuration($this->user->id, $content);

        $this->assertSame(0, $recSeconds);
        $this->assertSame('00:00:00', $recDuration);
    }

    /**
     * TC-07: Direct 40s + Playlist 50s, Original 60s -> Total Actual 90s -> Recognized 30s.
     */
    public function test_tc07_direct_40s_playlist_50s_original_60s_recognized_30s(): void
    {
        $content = $this->createContent(60);

        $plan = TrainingPlan::create([
            'title'                  => 'Plan TC07',
            'slug'                   => 'plan-tc07-' . Str::uuid(),
            'total_duration_seconds' => 60,
            'active'                 => 'Active',
        ]);
        TrainingPlanDetail::create(['trainingplan_id' => $plan->id, 'content_id' => $content->id, 'content_order' => 1]);

        PlaylistContentUser::create([
            'user_id'              => $this->user->id,
            'trainingplan_id'      => $plan->id,
            'content_id'           => $content->id,
            'actual_watch_seconds' => 50,
            'duration_seconds'     => 60,
            'progress'             => 83,
            'finish'               => '0',
        ]);

        ContentUser::create([
            'user_id'              => $this->user->id,
            'content_id'           => $content->id,
            'actual_watch_seconds' => 40,
            'duration_seconds'     => 60,
            'progress'             => 67,
            'finish'               => '0',
            'status'               => 'non mandatory',
        ]);

        $recSeconds = $this->recService->resolveGlobalContentRecognizedSeconds($this->user->id, $content);
        $recDuration = $this->recService->resolveGlobalContentRecognizedDuration($this->user->id, $content);

        $this->assertSame(30, $recSeconds);
        $this->assertSame('00:00:30', $recDuration);
    }

    /**
     * TC-08: Direct 60s + Playlist A 30s + Playlist B 30s, Original 60s -> Total Actual 120s -> Recognized 60s.
     */
    public function test_tc08_direct_60s_playlist_a_30s_playlist_b_30s_original_60s_recognized_60s(): void
    {
        $content = $this->createContent(60);

        $planA = TrainingPlan::create(['title' => 'Plan A', 'slug' => 'plan-a-' . Str::uuid(), 'total_duration_seconds' => 60, 'active' => 'Active']);
        $planB = TrainingPlan::create(['title' => 'Plan B', 'slug' => 'plan-b-' . Str::uuid(), 'total_duration_seconds' => 60, 'active' => 'Active']);
        TrainingPlanDetail::create(['trainingplan_id' => $planA->id, 'content_id' => $content->id, 'content_order' => 1]);
        TrainingPlanDetail::create(['trainingplan_id' => $planB->id, 'content_id' => $content->id, 'content_order' => 1]);

        PlaylistContentUser::create(['user_id' => $this->user->id, 'trainingplan_id' => $planA->id, 'content_id' => $content->id, 'actual_watch_seconds' => 30, 'duration_seconds' => 60, 'progress' => 50, 'finish' => '0']);
        PlaylistContentUser::create(['user_id' => $this->user->id, 'trainingplan_id' => $planB->id, 'content_id' => $content->id, 'actual_watch_seconds' => 30, 'duration_seconds' => 60, 'progress' => 50, 'finish' => '0']);
        ContentUser::create(['user_id' => $this->user->id, 'content_id' => $content->id, 'actual_watch_seconds' => 60, 'duration_seconds' => 60, 'progress' => 100, 'finish' => '1', 'status' => 'non mandatory']);

        $recSeconds = $this->recService->resolveGlobalContentRecognizedSeconds($this->user->id, $content);
        $recDuration = $this->recService->resolveGlobalContentRecognizedDuration($this->user->id, $content);

        $this->assertSame(60, $recSeconds);
        $this->assertSame('00:01:00', $recDuration);
    }

    /**
     * TC-09: Multi-Playlist rewatch must not exceed global cap (1x duration rewatch max).
     */
    public function test_tc09_multi_playlist_rewatch_strictly_capped(): void
    {
        $content = $this->createContent(60);

        $p1 = TrainingPlan::create(['title' => 'P1', 'slug' => 'p1-' . Str::uuid(), 'total_duration_seconds' => 60, 'active' => 'Active']);
        $p2 = TrainingPlan::create(['title' => 'P2', 'slug' => 'p2-' . Str::uuid(), 'total_duration_seconds' => 60, 'active' => 'Active']);
        $p3 = TrainingPlan::create(['title' => 'P3', 'slug' => 'p3-' . Str::uuid(), 'total_duration_seconds' => 60, 'active' => 'Active']);

        TrainingPlanDetail::create(['trainingplan_id' => $p1->id, 'content_id' => $content->id, 'content_order' => 1]);
        TrainingPlanDetail::create(['trainingplan_id' => $p2->id, 'content_id' => $content->id, 'content_order' => 1]);
        TrainingPlanDetail::create(['trainingplan_id' => $p3->id, 'content_id' => $content->id, 'content_order' => 1]);

        PlaylistContentUser::create(['user_id' => $this->user->id, 'trainingplan_id' => $p1->id, 'content_id' => $content->id, 'actual_watch_seconds' => 60, 'duration_seconds' => 60, 'progress' => 100, 'finish' => '1']);
        PlaylistContentUser::create(['user_id' => $this->user->id, 'trainingplan_id' => $p2->id, 'content_id' => $content->id, 'actual_watch_seconds' => 60, 'duration_seconds' => 60, 'progress' => 100, 'finish' => '1']);
        PlaylistContentUser::create(['user_id' => $this->user->id, 'trainingplan_id' => $p3->id, 'content_id' => $content->id, 'actual_watch_seconds' => 60, 'duration_seconds' => 60, 'progress' => 100, 'finish' => '1']);

        $recSeconds = $this->recService->resolveGlobalContentRecognizedSeconds($this->user->id, $content);
        $this->assertSame(60, $recSeconds);

        $contentAllocations = [];
        $batch = $this->hourService->calculatePlaylistsRecognizedSecondsBatch($this->user->id, collect([$p1, $p2, $p3]), $contentAllocations);

        $this->assertSame(0, $batch->get($p1->id));   // First watch (60s) -> 0s rewatch
        $this->assertSame(60, $batch->get($p2->id));  // Rewatch (60s) -> 60s rewatch
        $this->assertSame(0, $batch->get($p3->id));   // Quota capped -> 0s rewatch
        $this->assertSame(60, $batch->sum());
    }

    /**
     * TC-10: Progress and finish remain strictly independent between direct content and playlist.
     */
    public function test_tc10_progress_and_finish_remain_strictly_independent(): void
    {
        $content = $this->createContent(100);

        $plan = TrainingPlan::create(['title' => 'Indep Plan', 'slug' => 'indep-plan-' . Str::uuid(), 'total_duration_seconds' => 100, 'active' => 'Active']);
        TrainingPlanDetail::create(['trainingplan_id' => $plan->id, 'content_id' => $content->id, 'content_order' => 1]);

        $cu = ContentUser::create([
            'user_id'              => $this->user->id,
            'content_id'           => $content->id,
            'actual_watch_seconds' => 100,
            'progress'             => 100,
            'finish'               => '1',
            'status'               => 'non mandatory',
        ]);

        $pcu = PlaylistContentUser::create([
            'user_id'              => $this->user->id,
            'trainingplan_id'      => $plan->id,
            'content_id'           => $content->id,
            'actual_watch_seconds' => 25,
            'progress'             => 25,
            'finish'               => '0',
        ]);

        $this->assertSame(100, (int) $cu->fresh()->progress);
        $this->assertSame('1', (string) $cu->fresh()->finish);
        $this->assertSame(25, (int) $pcu->fresh()->progress);
        $this->assertSame('0', (string) $pcu->fresh()->finish);

        // Global recognized rewatch: min(max(100 + 25 - 100, 0), 100) = 25s
        $this->assertSame(25, $this->recService->resolveGlobalContentRecognizedSeconds($this->user->id, $content));
        $this->assertSame('00:00:25', $this->recService->resolveGlobalContentRecognizedDuration($this->user->id, $content));
    }

    /**
     * TC-11: Excel export and UI render identical Recognized Rewatch Duration.
     */
    public function test_tc11_excel_and_ui_display_identical_recognized_rewatch(): void
    {
        $content = $this->createContent(200);

        ContentUser::create([
            'user_id'              => $this->user->id,
            'content_id'           => $content->id,
            'actual_watch_seconds' => 250, // 200s first watch + 50s rewatch
            'current_time'         => 200,
            'duration_seconds'     => 200,
            'progress'             => 100,
            'finish'               => '1',
            'status'               => 'non mandatory',
        ]);

        // UI row
        $rows = $this->recordController->getRawOnlineRows($this->user->id, $this->eligibilityService);
        $directRow = $rows->firstWhere('title', $content->title);
        $this->assertNotNull($directRow);

        $this->assertSame('00:00:50', $directRow['total_recognized_duration']);
        $this->assertSame(50, $directRow['recognized_duration_seconds']);

        // Excel export
        $export = new ExportMyLearningRecord([]);
        $data = $export->array();
        $exportedRow = null;
        for ($i = 1; $i < count($data); $i++) {
            if (trim($data[$i][4]) === $content->title) {
                $exportedRow = $data[$i];
                break;
            }
        }

        $this->assertNotNull($exportedRow);
        // Column I: Course Duration = 200 / 86400
        $this->assertEqualsWithDelta(200 / 86400, $exportedRow[8], 0.0001);
        // Column J: Recognized Rewatch Duration = 50 / 86400
        $this->assertEqualsWithDelta(50 / 86400, $exportedRow[9], 0.0001);
    }

    /**
     * TC-12: Partial heartbeat sessions accumulate accurately without losing rewatch.
     */
    public function test_tc12_partial_heartbeat_sessions_accumulate_accurately(): void
    {
        $content = $this->createContent(60);

        $cu = ContentUser::create([
            'user_id'              => $this->user->id,
            'content_id'           => $content->id,
            'actual_watch_seconds' => 50,
            'current_time'         => 50,
            'duration_seconds'     => 60,
            'progress'             => 83,
            'finish'               => '0',
            'status'               => 'non mandatory',
        ]);

        // Heartbeat 1: +10s (reaches 60s, completes first watch quota -> rewatch = 0s)
        $res1 = $this->postJson(route('content.user.progress.update'), [
            'content_id'       => $content->id,
            'type'             => 'video',
            'delta_seconds'    => 10,
            'current_time'     => 60,
            'duration_seconds' => 60,
            'heartbeat_id'     => 'hb-tc12-1',
            'status'           => 'completed',
        ]);
        $res1->assertOk();
        $res1->assertJson([
            'total_recognized_seconds'  => 0,
            'total_recognized_duration' => '00:00:00',
        ]);

        // Advance time by 15 seconds to allow legitimate delta in anti-spoofing
        \Illuminate\Support\Carbon::setTestNow(now()->addSeconds(15));

        // Heartbeat 2: +15s (reaches 75s -> rewatch = 15s)
        $res2 = $this->postJson(route('content.user.progress.update'), [
            'content_id'       => $content->id,
            'type'             => 'video',
            'delta_seconds'    => 15,
            'current_time'     => 15,
            'duration_seconds' => 60,
            'heartbeat_id'     => 'hb-tc12-2',
            'status'           => 'completed',
        ]);
        $res2->assertOk();
        $res2->assertJson([
            'total_recognized_seconds'  => 15,
            'total_recognized_duration' => '00:00:15',
        ]);

        \Illuminate\Support\Carbon::setTestNow();
    }

    /**
     * TC-13: Concurrent / alternating Direct and Playlist heartbeats do not exceed global cap.
     */
    public function test_tc13_concurrent_direct_and_playlist_heartbeats_respect_global_cap(): void
    {
        $content = $this->createContent(60);

        $plan = TrainingPlan::create([
            'title'                  => 'Plan TC13',
            'slug'                   => 'plan-tc13-' . Str::uuid(),
            'total_duration_seconds' => 60,
            'active'                 => 'Active',
        ]);
        TrainingPlanDetail::create(['trainingplan_id' => $plan->id, 'content_id' => $content->id, 'content_order' => 1]);

        ContentUser::create([
            'user_id'              => $this->user->id,
            'content_id'           => $content->id,
            'actual_watch_seconds' => 50,
            'duration_seconds'     => 60,
            'progress'             => 83,
            'finish'               => '0',
            'status'               => 'non mandatory',
        ]);

        PlaylistContentUser::create([
            'user_id'              => $this->user->id,
            'trainingplan_id'      => $plan->id,
            'content_id'           => $content->id,
            'actual_watch_seconds' => 60,
            'duration_seconds'     => 60,
            'progress'             => 100,
            'finish'               => '1',
        ]);

        // Direct is currently 50, playlist is 60 -> total actual = 110s -> rewatch = 50s
        $this->assertSame(50, $this->recService->resolveGlobalContentRecognizedSeconds($this->user->id, $content));

        // Direct sends 20s heartbeat (50 + 20 = 70s in CU; total actual = 70 + 60 = 130s > 120s max)
        $res = $this->postJson(route('content.user.progress.update'), [
            'content_id'       => $content->id,
            'type'             => 'video',
            'delta_seconds'    => 20,
            'current_time'     => 60,
            'duration_seconds' => 60,
            'heartbeat_id'     => 'hb-tc13-dir',
            'status'           => 'completed',
        ]);
        $res->assertOk();

        // Must be capped at 60s max rewatch!
        $this->assertSame(60, $this->recService->resolveGlobalContentRecognizedSeconds($this->user->id, $content));
        $this->assertSame('00:01:00', $this->recService->resolveGlobalContentRecognizedDuration($this->user->id, $content));
    }
}
