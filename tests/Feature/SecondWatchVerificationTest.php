<?php

namespace Tests\Feature;

use App\Models\Content;
use App\Models\ContentUser;
use App\Models\User;
use App\Services\Learning\LearningRecordRecognizedDurationService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Str;
use Tests\TestCase;

class SecondWatchVerificationTest extends TestCase
{
    use DatabaseTransactions;

    protected User $user;
    protected Content $content;
    protected ContentUser $contentUser;
    protected LearningRecordRecognizedDurationService $service;

    protected function setUp(): void
    {
        parent::setUp();

        $this->service = app(LearningRecordRecognizedDurationService::class);

        // Content 281: Duration 98 seconds
        $this->content = Content::find(281);
        $this->assertNotNull($this->content);
        $this->content->update([
            'duration' => '00:01:38',
            'duration_in_seconds' => 98,
            'active' => 'Active',
        ]);

        // Create a completely clean test user
        $this->user = User::factory()->create();

        // Fresh content_user with 0 progress, 0 actual watch
        $this->contentUser = ContentUser::create([
            'user_id' => $this->user->id,
            'content_id' => $this->content->id,
            'progress' => 0,
            'finish' => '0',
            'actual_watch_seconds' => 0,
            'current_time' => 0,
            'duration_seconds' => 98,
            'learning_status' => 'not_started',
        ]);
    }

    /**
     * Test Case: Fresh user watches video first time (0 -> 98s), then second time (0 -> 98s).
     */
    public function test_fresh_user_watches_first_time_then_second_time()
    {
        $this->actingAs($this->user);

        $testClock = now();
        Carbon::setTestNow($testClock);

        // ==========================================
        // SESSION 1: FIRST WATCH (0s -> 98s)
        // 9 heartbeats of 10s + 1 heartbeat of 8s = 98s total
        // ==========================================
        for ($i = 1; $i <= 9; $i++) {
            $testClock = $testClock->addSeconds(10);
            Carbon::setTestNow($testClock);

            $res = $this->postJson(route('content.user.progress.update'), [
                'content_id' => $this->content->id,
                'type' => 'video',
                'current_time' => $i * 10,
                'duration_seconds' => 98,
                'delta_seconds' => 10,
                'heartbeat_id' => (string) Str::uuid(),
            ]);

            $res->assertOk();
            $res->assertJson([
                'actual_watch_seconds' => $i * 10,
                'total_recognized_seconds' => 0,
                'total_recognized_duration' => '00:00:00',
            ]);
        }

        // Final 8s of session 1 (completes at 98s)
        $testClock = $testClock->addSeconds(8);
        Carbon::setTestNow($testClock);

        $resEnd1 = $this->postJson(route('content.user.progress.update'), [
            'content_id' => $this->content->id,
            'type' => 'video',
            'current_time' => 98,
            'duration_seconds' => 98,
            'delta_seconds' => 8,
            'status' => 'completed',
            'heartbeat_id' => (string) Str::uuid(),
        ]);

        $resEnd1->assertOk();
        $resEnd1->assertJson([
            'actual_watch_seconds' => 98,
            'progress' => 100,
            'finish' => 1,
            'total_recognized_seconds' => 0,
            'total_recognized_duration' => '00:00:00',
        ]);

        // ==========================================
        // SESSION 2: SECOND WATCH (0s -> 98s)
        // User starts watching again (rewatch).
        // First heartbeat of second watch (10s in):
        // Total actual watch = 98 + 10 = 108s.
        // Recognized Rewatch MUST BE 108 - 98 = 10s ("00:00:10")!
        // ==========================================
        $testClock = $testClock->addSeconds(10);
        Carbon::setTestNow($testClock);

        $resS2H1 = $this->postJson(route('content.user.progress.update'), [
            'content_id' => $this->content->id,
            'type' => 'video',
            'current_time' => 10,
            'duration_seconds' => 98,
            'delta_seconds' => 10,
            'heartbeat_id' => (string) Str::uuid(),
        ]);

        $resS2H1->assertOk();

        $this->assertSame(108, $resS2H1->json('actual_watch_seconds'));
        $this->assertSame(10, $resS2H1->json('total_recognized_seconds'));
        $this->assertSame('00:00:10', $resS2H1->json('total_recognized_duration'));

        Carbon::setTestNow();
    }

    /**
     * Test Case: Existing completed user (finish = 1, progress = 100, actual_watch_seconds = 0)
     * comes back to rewatch the video.
     */
    public function test_existing_completed_user_rewatches_video()
    {
        $this->actingAs($this->user);

        // Simulate an existing completed user record
        // (completed before watch time tracking, or completed earlier)
        $this->contentUser->update([
            'progress' => 100,
            'finish' => '1',
            'actual_watch_seconds' => 0,
            'current_time' => 20, // paused position or previous seek position
            'learning_status' => 'completed',
        ]);

        $testClock = now();
        Carbon::setTestNow($testClock);

        // User starts rewatching: plays 10 seconds
        $testClock = $testClock->addSeconds(10);
        Carbon::setTestNow($testClock);

        $res = $this->postJson(route('content.user.progress.update'), [
            'content_id' => $this->content->id,
            'type' => 'video',
            'current_time' => 10,
            'duration_seconds' => 98,
            'delta_seconds' => 10,
            'heartbeat_id' => (string) Str::uuid(),
        ]);

        $res->assertOk();

        // Expected: Since user was ALREADY COMPLETED, their first watch quota (98s) is already done!
        // Rewatching 10s MUST yield actual_watch_seconds >= 108 and recognized_duration = "00:00:10"!
        $this->assertSame(108, $res->json('actual_watch_seconds'));
        $this->assertSame(10, $res->json('total_recognized_seconds'));
        $this->assertSame('00:00:10', $res->json('total_recognized_duration'));

        Carbon::setTestNow();
    }

    /**
     * Test Section 3 & 7 Canonical Scenario:
     * - Sesi 1: First watch selesai (98s) -> Total Actual: 98s -> Recognized: 00:00:00
     * - Sesi 2: Second watch incremental (108s, 128s, 196s) -> Recognized: 10s, 30s, 98s
     * - Sesi 3: Third watch selesai (294s) -> Recognized remains strictly 98s (00:01:38)
     */
    public function test_full_three_sessions_lifecycle_and_strict_cap()
    {
        $this->actingAs($this->user);

        $testClock = now();
        Carbon::setTestNow($testClock);

        // --- SESI 1: First Watch (0 -> 98s) ---
        for ($i = 1; $i <= 9; $i++) {
            $testClock = $testClock->addSeconds(10);
            Carbon::setTestNow($testClock);

            $this->postJson(route('content.user.progress.update'), [
                'content_id' => $this->content->id,
                'type' => 'video',
                'current_time' => $i * 10,
                'duration_seconds' => 98,
                'delta_seconds' => 10,
                'heartbeat_id' => (string) Str::uuid(),
            ]);
        }
        $testClock = $testClock->addSeconds(8);
        Carbon::setTestNow($testClock);
        $s1End = $this->postJson(route('content.user.progress.update'), [
            'content_id' => $this->content->id,
            'type' => 'video',
            'current_time' => 98,
            'duration_seconds' => 98,
            'delta_seconds' => 8,
            'status' => 'completed',
            'heartbeat_id' => (string) Str::uuid(),
        ]);
        $s1End->assertJson([
            'actual_watch_seconds' => 98,
            'total_recognized_seconds' => 0,
            'total_recognized_duration' => '00:00:00',
        ]);

        // --- SESI 2: Second Watch Incremental ---
        // 1. At 108s total (+10s rewatch) -> Recognized 10s ("00:00:10")
        $testClock = $testClock->addSeconds(10);
        Carbon::setTestNow($testClock);
        $s2Hb1 = $this->postJson(route('content.user.progress.update'), [
            'content_id' => $this->content->id,
            'type' => 'video',
            'current_time' => 10,
            'duration_seconds' => 98,
            'delta_seconds' => 10,
            'heartbeat_id' => (string) Str::uuid(),
        ]);
        $s2Hb1->assertJson([
            'actual_watch_seconds' => 108,
            'total_recognized_seconds' => 10,
            'total_recognized_duration' => '00:00:10',
        ]);

        // 2. At 128s total (+20s more rewatch) -> Recognized 30s ("00:00:30")
        $testClock = $testClock->addSeconds(20);
        Carbon::setTestNow($testClock);
        $s2Hb2 = $this->postJson(route('content.user.progress.update'), [
            'content_id' => $this->content->id,
            'type' => 'video',
            'current_time' => 30,
            'duration_seconds' => 98,
            'delta_seconds' => 20,
            'heartbeat_id' => (string) Str::uuid(),
        ]);
        $s2Hb2->assertJson([
            'actual_watch_seconds' => 128,
            'total_recognized_seconds' => 30,
            'total_recognized_duration' => '00:00:30',
        ]);

        // 3. Complete Sesi 2 (+68s to reach 196s total) -> Recognized 98s ("00:01:38")
        // Split in increments: 20s, 20s, 20s, 8s
        for ($k = 0; $k < 3; $k++) {
            $testClock = $testClock->addSeconds(20);
            Carbon::setTestNow($testClock);
            $this->postJson(route('content.user.progress.update'), [
                'content_id' => $this->content->id,
                'type' => 'video',
                'current_time' => 50 + ($k * 20),
                'duration_seconds' => 98,
                'delta_seconds' => 20,
                'heartbeat_id' => (string) Str::uuid(),
            ]);
        }
        $testClock = $testClock->addSeconds(8);
        Carbon::setTestNow($testClock);
        $s2End = $this->postJson(route('content.user.progress.update'), [
            'content_id' => $this->content->id,
            'type' => 'video',
            'current_time' => 98,
            'duration_seconds' => 98,
            'delta_seconds' => 8,
            'status' => 'completed',
            'heartbeat_id' => (string) Str::uuid(),
        ]);
        $s2End->assertJson([
            'actual_watch_seconds' => 196,
            'total_recognized_seconds' => 98,
            'total_recognized_duration' => '00:01:38',
            'is_capped' => true,
        ]);

        // --- SESI 3: Third Watch (Total 196 + 98 = 294s) ---
        // Recognized Duration must remain strictly 98s (00:01:38)
        for ($m = 1; $m <= 4; $m++) {
            $testClock = $testClock->addSeconds(25);
            Carbon::setTestNow($testClock);
            $s3Res = $this->postJson(route('content.user.progress.update'), [
                'content_id' => $this->content->id,
                'type' => 'video',
                'current_time' => $m * 24,
                'duration_seconds' => 98,
                'delta_seconds' => 24,
                'heartbeat_id' => (string) Str::uuid(),
            ]);
            $s3Res->assertJson([
                'total_recognized_seconds' => 98,
                'total_recognized_duration' => '00:01:38',
                'is_capped' => true,
            ]);
        }

        Carbon::setTestNow();
    }

    /**
     * Test rapid pause and ended events do not cause duplicate delta or lost time.
     */
    public function test_rapid_pause_and_ended_events_safety()
    {
        $this->actingAs($this->user);

        $testClock = now();
        Carbon::setTestNow($testClock);

        // 1. Send pause event with delta 10s
        $testClock = $testClock->addSeconds(10);
        Carbon::setTestNow($testClock);
        $hbId = (string) Str::uuid();

        $pauseRes = $this->postJson(route('content.user.progress.update'), [
            'content_id' => $this->content->id,
            'type' => 'video',
            'current_time' => 10,
            'duration_seconds' => 98,
            'delta_seconds' => 10,
            'status' => 'paused',
            'heartbeat_id' => $hbId,
        ]);
        $pauseRes->assertOk();
        $this->assertSame(10, $pauseRes->json('actual_watch_seconds'));

        // 2. Immediately (0.05s later) ended event fires with delta 0
        $endedRes = $this->postJson(route('content.user.progress.update'), [
            'content_id' => $this->content->id,
            'type' => 'video',
            'current_time' => 10,
            'duration_seconds' => 98,
            'delta_seconds' => 0,
            'status' => 'completed',
            'heartbeat_id' => (string) Str::uuid(),
        ]);
        $endedRes->assertOk();
        // actual_watch_seconds remains 10 (no double count or zeroing)
        $this->assertSame(10, $endedRes->json('actual_watch_seconds'));

        // 3. Resend the EXACT same heartbeat_id (duplicate payload retransmit)
        $duplicateRes = $this->postJson(route('content.user.progress.update'), [
            'content_id' => $this->content->id,
            'type' => 'video',
            'current_time' => 10,
            'duration_seconds' => 98,
            'delta_seconds' => 10,
            'status' => 'paused',
            'heartbeat_id' => $hbId,
        ]);
        $duplicateRes->assertOk();
        // Server ignores duplicate delta: remains 10s
        $this->assertSame(10, $duplicateRes->json('actual_watch_seconds'));

        Carbon::setTestNow();
    }

    /**
     * Test Direct and Playlist share global quota and do not allow rewatch beyond 1x D.
     */
    public function test_direct_and_playlist_share_global_first_watch_and_rewatch_quota()
    {
        $this->actingAs($this->user);

        // Direct has completed first watch (98s)
        $this->contentUser->update([
            'actual_watch_seconds' => 98,
            'progress' => 100,
            'finish' => '1',
            'learning_status' => 'completed',
        ]);

        $playlist = \App\Models\TrainingPlan::create([
            'title' => 'Global Quota TP ' . Str::random(5),
            'slug' => 'global-quota-tp-' . Str::random(5),
            'active' => 'Active',
        ]);

        \App\Models\TrainingPlanDetail::create([
            'trainingplan_id' => $playlist->id,
            'content_id' => $this->content->id,
            'content_order' => 1,
        ]);

        $pcu = \App\Models\PlaylistContentUser::create([
            'user_id' => $this->user->id,
            'trainingplan_id' => $playlist->id,
            'content_id' => $this->content->id,
            'duration_seconds' => 98,
            'actual_watch_seconds' => 0,
            'progress' => 0,
            'finish' => '0',
            'learning_status' => 'not_started',
        ]);

        $testClock = now();
        Carbon::setTestNow($testClock);

        // User now watches via Playlist (40s split in two 20s heartbeats).
        // Since Direct already completed first watch (98s), playlist watch is IMMEDIATELY Recognized Rewatch!
        for ($s = 1; $s <= 2; $s++) {
            $testClock = $testClock->addSeconds(20);
            Carbon::setTestNow($testClock);

            $pcuRes = $this->postJson(route('playlist.user.progress.update'), [
                'trainingplan_id' => $playlist->id,
                'content_id' => $this->content->id,
                'type' => 'video',
                'current_time' => $s * 20,
                'duration_seconds' => 98,
                'delta_seconds' => 20,
                'heartbeat_id' => (string) Str::uuid(),
            ]);
            $pcuRes->assertOk();
        }

        $this->assertSame(40, $pcuRes->json('actual_watch_seconds'));
        // Global recognized rewatch = 40s ("00:00:40")
        $this->assertSame(40, $pcuRes->json('total_recognized_seconds'));
        $this->assertSame('00:00:40', $pcuRes->json('total_recognized_duration'));

        Carbon::setTestNow();
    }
}
