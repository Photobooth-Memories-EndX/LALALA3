<?php

namespace Tests\Feature;

use App\Models\Content;
use App\Models\ContentUser;
use App\Models\PlaylistContentUser;
use App\Models\TrainingPlan;
use App\Models\TrainingPlanDetail;
use App\Models\User;
use App\Services\Learning\LearningRecordLearningHourService;
use App\Services\LearningHours\ActualLearningHourCalculator;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;
use Tests\TestCase;

class VideoWatchTimeTrackingTest extends TestCase
{
    use DatabaseTransactions;

    protected User $user;
    protected Content $videoContent;
    protected Content $docContent;
    protected TrainingPlan $playlist;

    protected function setUp(): void
    {
        parent::setUp();
        Cache::flush();

        $this->user = User::factory()->create([
            'team'       => 'Engineering',
            'department' => 'Technology',
            'division'   => 'LMS',
            'ssohash'    => 'test-hash-' . Str::uuid(),
            'sap_code'   => 'SAP-TEST-' . rand(1000, 9999),
            'role'       => 1,
            'active'     => 1,
        ]);

        $this->videoContent = Content::create([
            'title'          => 'Test Video Tracking',
            'type'           => 'Video',
            'duration'       => '00:01:00', // 60 seconds
            'active'         => 'Active',
            'playlistonly'   => 'N',
            'has_deadline'   => 0,
            'learning_hours' => 1.0,
        ]);

        $this->docContent = Content::create([
            'title'          => 'Test Doc Tracking',
            'type'           => 'Document',
            'duration'       => '00:05:00',
            'page_duration'  => 0, // Scroll mode
            'total_page'     => 10,
            'active'         => 'Active',
            'playlistonly'   => 'N',
            'has_deadline'   => 0,
            'learning_hours' => 0.5,
        ]);

        $this->playlist = TrainingPlan::create([
            'title'                  => 'Test Playlist Tracking',
            'active'                 => 'Active',
            'total_duration_seconds' => 3600,
            'learning_hours'         => 1.0,
            'has_deadline'           => 0,
        ]);

        TrainingPlanDetail::create([
            'trainingplan_id' => $this->playlist->id,
            'content_id'      => $this->videoContent->id,
            'content_order'   => 0,
        ]);

        TrainingPlanDetail::create([
            'trainingplan_id' => $this->playlist->id,
            'content_id'      => $this->docContent->id,
            'content_order'   => 1,
        ]);
    }

    /**
     * Case 1: Play 10s -> actual_watch_seconds = 10, learning_hours updated.
     */
    public function test_play_10_seconds_accumulates_actual_watch_seconds(): void
    {
        $response = $this->actingAs($this->user)->postJson(route('content.user.progress.update'), [
            'content_id'       => $this->videoContent->id,
            'type'             => 'video',
            'current_time'     => 10,
            'duration_seconds' => 60,
            'delta_seconds'    => 10,
            'heartbeat_id'     => (string) Str::uuid(),
            'status'           => 'in_progress',
        ]);

        $response->assertOk();
        $response->assertJson([
            'actual_watch_seconds' => 10,
            'current_time'         => 10,
        ]);

        $cu = ContentUser::where('user_id', $this->user->id)
            ->where('content_id', $this->videoContent->id)
            ->first();

        $this->assertNotNull($cu);
        $this->assertSame(10, (int) $cu->actual_watch_seconds);
        $this->assertSame(10, (int) $cu->current_time);
        $this->assertGreaterThan(0, (float) $cu->learning_hours);
        $this->assertEquals(round(10 / 3600, 4), round((float) $cu->learning_hours, 4));
    }

    /**
     * Case 2: Pause -> final delta saved and status marked paused.
     */
    public function test_pause_saves_final_delta(): void
    {
        // 1st tick: 10s
        $this->actingAs($this->user)->postJson(route('content.user.progress.update'), [
            'content_id'       => $this->videoContent->id,
            'type'             => 'video',
            'current_time'     => 10,
            'duration_seconds' => 60,
            'delta_seconds'    => 10,
            'heartbeat_id'     => (string) Str::uuid(),
            'status'           => 'in_progress',
        ]);

        // 5 seconds pass in real time before user pauses
        $this->travel(5)->seconds();

        // Pause after another 5s
        $response = $this->actingAs($this->user)->postJson(route('content.user.progress.update'), [
            'content_id'       => $this->videoContent->id,
            'type'             => 'video',
            'current_time'     => 15,
            'duration_seconds' => 60,
            'delta_seconds'    => 5,
            'heartbeat_id'     => (string) Str::uuid(),
            'status'           => 'paused',
        ]);

        $response->assertOk();
        $response->assertJson([
            'actual_watch_seconds' => 15,
            'status'               => 'paused',
        ]);

        $cu = ContentUser::where('user_id', $this->user->id)
            ->where('content_id', $this->videoContent->id)
            ->first();

        $this->assertSame(15, (int) $cu->actual_watch_seconds);
        $this->assertSame('paused', $cu->learning_status);
    }

    /**
     * Case 3: Seeking forward (e.g. 5s -> 50s) does NOT inflate actual_watch_seconds.
     */
    public function test_seek_forward_does_not_inflate_actual_watch_seconds(): void
    {
        // Watched 5 seconds
        $this->actingAs($this->user)->postJson(route('content.user.progress.update'), [
            'content_id'       => $this->videoContent->id,
            'type'             => 'video',
            'current_time'     => 5,
            'duration_seconds' => 60,
            'delta_seconds'    => 5,
            'heartbeat_id'     => (string) Str::uuid(),
            'status'           => 'in_progress',
        ]);

        // Seek forward to 50s (delta_seconds is 0)
        $seekResponse = $this->actingAs($this->user)->postJson(route('content.user.progress.update'), [
            'content_id'       => $this->videoContent->id,
            'type'             => 'video',
            'current_time'     => 50,
            'duration_seconds' => 60,
            'delta_seconds'    => 0,
            'heartbeat_id'     => (string) Str::uuid(),
            'status'           => 'in_progress',
        ]);

        $seekResponse->assertOk();
        $seekResponse->assertJson([
            'actual_watch_seconds' => 5, // Unchanged!
            'current_time'         => 50, // Playhead updated for resume!
        ]);

        // Then user plays 10 seconds starting from 50s (50s -> 60s)
        $this->travel(10)->seconds();

        $playAfterSeek = $this->actingAs($this->user)->postJson(route('content.user.progress.update'), [
            'content_id'       => $this->videoContent->id,
            'type'             => 'video',
            'current_time'     => 60,
            'duration_seconds' => 60,
            'delta_seconds'    => 10,
            'heartbeat_id'     => (string) Str::uuid(),
            'status'           => 'in_progress',
        ]);

        $playAfterSeek->assertOk();
        $playAfterSeek->assertJson([
            'actual_watch_seconds' => 15, // 5 + 10 = 15, NOT 60!
            'current_time'         => 60,
        ]);
    }

    /**
     * Case 4: Seeking backward (e.g. 50s -> 10s) does NOT decrease actual_watch_seconds.
     */
    public function test_seek_backward_does_not_decrease_actual_watch_seconds(): void
    {
        $cu = ContentUser::create([
            'user_id'              => $this->user->id,
            'content_id'           => $this->videoContent->id,
            'current_time'         => 50,
            'actual_watch_seconds' => 40,
            'progress'             => 83,
        ]);

        // Seek back to 10s
        $response = $this->actingAs($this->user)->postJson(route('content.user.progress.update'), [
            'content_id'       => $this->videoContent->id,
            'type'             => 'video',
            'current_time'     => 10,
            'duration_seconds' => 60,
            'delta_seconds'    => 0,
            'heartbeat_id'     => (string) Str::uuid(),
            'status'           => 'in_progress',
        ]);

        $response->assertOk();
        $response->assertJson([
            'actual_watch_seconds' => 40, // Does not decrease!
            'current_time'         => 10, // Position rewinds for resume
        ]);

        $cu->refresh();
        $this->assertSame(40, (int) $cu->actual_watch_seconds);
        $this->assertSame(10, (int) $cu->current_time);
        $this->assertSame(83, (int) $cu->progress); // Monotonic: progress does not drop
    }

    /**
     * Case 5: Replay (0 -> 60s twice) accumulates past duration_seconds without cap.
     */
    public function test_replay_accumulates_past_duration_seconds(): void
    {
        // 1st watch: 30s + 30s = 60s
        $this->actingAs($this->user)->postJson(route('content.user.progress.update'), [
            'content_id'       => $this->videoContent->id,
            'type'             => 'video',
            'current_time'     => 30,
            'duration_seconds' => 60,
            'delta_seconds'    => 30,
            'heartbeat_id'     => (string) Str::uuid(),
            'status'           => 'in_progress',
        ]);

        $this->travel(30)->seconds();

        $this->actingAs($this->user)->postJson(route('content.user.progress.update'), [
            'content_id'       => $this->videoContent->id,
            'type'             => 'video',
            'current_time'     => 60,
            'duration_seconds' => 60,
            'delta_seconds'    => 30,
            'heartbeat_id'     => (string) Str::uuid(),
            'status'           => 'completed',
        ]);

        $cu = ContentUser::where('user_id', $this->user->id)
            ->where('content_id', $this->videoContent->id)
            ->first();

        $this->assertSame(60, (int) $cu->actual_watch_seconds);
        $this->assertSame('1', (string) $cu->finish);

        // 2nd watch (replay): user watches another 30s + 30s
        $this->travel(30)->seconds();

        $this->actingAs($this->user)->postJson(route('content.user.progress.update'), [
            'content_id'       => $this->videoContent->id,
            'type'             => 'video',
            'current_time'     => 30,
            'duration_seconds' => 60,
            'delta_seconds'    => 30,
            'heartbeat_id'     => (string) Str::uuid(),
            'status'           => 'in_progress',
        ]);

        $this->travel(30)->seconds();

        $response = $this->actingAs($this->user)->postJson(route('content.user.progress.update'), [
            'content_id'       => $this->videoContent->id,
            'type'             => 'video',
            'current_time'     => 60,
            'duration_seconds' => 60,
            'delta_seconds'    => 30,
            'heartbeat_id'     => (string) Str::uuid(),
            'status'           => 'completed',
        ]);

        $response->assertOk();
        $response->assertJson([
            'actual_watch_seconds' => 120, // 60 + 60 = 120s!
        ]);

        $cu->refresh();
        $this->assertSame(120, (int) $cu->actual_watch_seconds);
        $this->assertGreaterThan(60, (int) $cu->actual_watch_seconds);
        $this->assertEquals(round(120 / 3600, 4), round((float) $cu->learning_hours, 4));
    }

    /**
     * Case 6: Duplicate heartbeat_id is ignored (idempotency check).
     */
    public function test_duplicate_heartbeat_id_is_idempotent(): void
    {
        $hbId = (string) Str::uuid();

        // Send first heartbeat
        $first = $this->actingAs($this->user)->postJson(route('content.user.progress.update'), [
            'content_id'       => $this->videoContent->id,
            'type'             => 'video',
            'current_time'     => 10,
            'duration_seconds' => 60,
            'delta_seconds'    => 10,
            'heartbeat_id'     => $hbId,
            'status'           => 'in_progress',
        ]);

        $first->assertJson(['actual_watch_seconds' => 10]);

        // Duplicate network resend with identical heartbeat_id
        $duplicate = $this->actingAs($this->user)->postJson(route('content.user.progress.update'), [
            'content_id'       => $this->videoContent->id,
            'type'             => 'video',
            'current_time'     => 10,
            'duration_seconds' => 60,
            'delta_seconds'    => 10,
            'heartbeat_id'     => $hbId,
            'status'           => 'in_progress',
        ]);

        $duplicate->assertJson(['actual_watch_seconds' => 10]);

        $cu = ContentUser::where('user_id', $this->user->id)
            ->where('content_id', $this->videoContent->id)
            ->first();

        $this->assertSame(10, (int) $cu->actual_watch_seconds);
    }

    /**
     * Case 7: Delta clamping: excessive delta on single heartbeat clamped to max 30s.
     */
    public function test_delta_seconds_clamped_to_max_30(): void
    {
        $response = $this->actingAs($this->user)->postJson(route('content.user.progress.update'), [
            'content_id'       => $this->videoContent->id,
            'type'             => 'video',
            'current_time'     => 50,
            'duration_seconds' => 60,
            'delta_seconds'    => 100, // Client tries to send 100s delta
            'heartbeat_id'     => (string) Str::uuid(),
            'status'           => 'in_progress',
        ]);

        $response->assertOk();
        $response->assertJson([
            'actual_watch_seconds' => 30, // Clamped to 30s
        ]);

        $cu = ContentUser::where('user_id', $this->user->id)
            ->where('content_id', $this->videoContent->id)
            ->first();

        $this->assertSame(30, (int) $cu->actual_watch_seconds);
    }

    /**
     * Case 8: Playlist video strictly updates playlist_content_users (actual_watch_seconds, learning_hour)
     * and does NOT touch content_users.
     */
    public function test_playlist_video_strictly_updates_playlist_content_users(): void
    {
        $response = $this->actingAs($this->user)->postJson(route('playlist.user.progress.update'), [
            'trainingplan_id'  => $this->playlist->id,
            'content_id'       => $this->videoContent->id,
            'type'             => 'video',
            'current_time'     => 20,
            'duration_seconds' => 60,
            'delta_seconds'    => 20,
            'heartbeat_id'     => (string) Str::uuid(),
            'status'           => 'in_progress',
        ]);

        $response->assertOk();
        $response->assertJson([
            'success'              => true,
            'actual_watch_seconds' => 20,
            'current_time'         => 20,
        ]);

        $pcu = PlaylistContentUser::where('user_id', $this->user->id)
            ->where('trainingplan_id', $this->playlist->id)
            ->where('content_id', $this->videoContent->id)
            ->first();

        $this->assertNotNull($pcu);
        $this->assertSame(20, (int) $pcu->actual_watch_seconds);
        $this->assertSame(20, (int) $pcu->current_time);
        $this->assertGreaterThan(0, (float) $pcu->learning_hour);

        // Verify content_users was NOT created / touched
        $cu = ContentUser::where('user_id', $this->user->id)
            ->where('content_id', $this->videoContent->id)
            ->first();

        $this->assertNull($cu);
    }

    /**
     * Case 9: Standalone video strictly updates content_users (actual_watch_seconds, learning_hours)
     * and does NOT touch playlist_content_users.
     */
    public function test_standalone_video_strictly_updates_content_users(): void
    {
        $response = $this->actingAs($this->user)->postJson(route('content.user.progress.update'), [
            'content_id'       => $this->videoContent->id,
            'type'             => 'video',
            'current_time'     => 25,
            'duration_seconds' => 60,
            'delta_seconds'    => 25,
            'heartbeat_id'     => (string) Str::uuid(),
            'status'           => 'in_progress',
        ]);

        $response->assertOk();
        $response->assertJson([
            'actual_watch_seconds' => 25,
            'current_time'         => 25,
        ]);

        $cu = ContentUser::where('user_id', $this->user->id)
            ->where('content_id', $this->videoContent->id)
            ->first();

        $this->assertNotNull($cu);
        $this->assertSame(25, (int) $cu->actual_watch_seconds);

        // Verify playlist_content_users was NOT created / touched
        $pcu = PlaylistContentUser::where('user_id', $this->user->id)
            ->where('content_id', $this->videoContent->id)
            ->first();

        $this->assertNull($pcu);
    }

    /**
     * Case 10: PDF Scroll regression test -> unaffected by video changes.
     */
    public function test_pdf_scroll_regression_unaffected(): void
    {
        $response = $this->actingAs($this->user)->postJson(route('content.user.progress.update'), [
            'content_id'  => $this->docContent->id,
            'type'        => 'document',
            'last_page'   => 5,
            'total_pages' => 10,
            'status'      => 'in_progress',
        ]);

        $response->assertOk();
        $response->assertJson([
            'progress'  => 50,
            'last_page' => 5,
        ]);

        $cu = ContentUser::where('user_id', $this->user->id)
            ->where('content_id', $this->docContent->id)
            ->first();

        $this->assertNotNull($cu);
        $this->assertSame(5, (int) $cu->last_page);
        $this->assertSame(50, (int) $cu->progress);
        $this->assertSame(0, (int) ($cu->actual_watch_seconds ?? 0));
    }

    /**
     * Case 11: PDF Timed regression test -> page timer and validation remain intact.
     */
    public function test_pdf_timed_regression_unaffected(): void
    {
        $timedDoc = Content::create([
            'title'          => 'Timed Doc',
            'type'           => 'Document',
            'duration'       => '00:05:00',
            'page_duration'  => 10, // 10s per page
            'total_page'     => 5,
            'active'         => 'Active',
            'playlistonly'   => 'N',
            'has_deadline'   => 0,
            'learning_hours' => 0.5,
        ]);

        // Start reading page 1
        $startResponse = $this->actingAs($this->user)->postJson(route('content.user.progress.update'), [
            'content_id'  => $timedDoc->id,
            'type'        => 'document',
            'active_page' => 1,
            'total_pages' => 5,
            'status'      => 'in_progress',
        ]);

        $startResponse->assertOk();
        $startResponse->assertJson([
            'allowed_page'   => 1,
            'page_completed' => false,
        ]);

        // Attempting to advance before page timer finishes should not complete page
        $prematureResponse = $this->actingAs($this->user)->postJson(route('content.user.progress.update'), [
            'content_id'           => $timedDoc->id,
            'type'                 => 'document',
            'active_page'          => 1,
            'completed_page'       => 1,
            'page_elapsed_seconds' => 2,
            'total_pages'          => 5,
            'status'               => 'in_progress',
        ]);

        $prematureResponse->assertOk();
        $prematureResponse->assertJson([
            'page_completed' => false,
            'allowed_page'   => 1,
        ]);
    }

    /**
     * Case 12: Learning Record reflects hours calculated from actual_watch_seconds.
     */
    public function test_learning_record_reflects_actual_watch_seconds(): void
    {
        $cu = ContentUser::create([
            'user_id'              => $this->user->id,
            'content_id'           => $this->videoContent->id,
            'current_time'         => 60,
            'actual_watch_seconds' => 1800, // 30 minutes = 0.5 hour
            'duration_seconds'     => 60,
            'progress'             => 100,
            'finish'               => '1',
            'learning_hours'       => 0.5,
        ]);

        $service = new LearningRecordLearningHourService();
        $hours = $service->calculateContent($cu);

        $this->assertEquals(0.5, $hours);

        $calc = new ActualLearningHourCalculator();
        $calculatedHours = $calc->calculateFromTracking(
            $cu->current_time,
            $cu->finish,
            $cu->progress,
            $this->videoContent->duration,
            $cu->actual_watch_seconds
        );
        $this->assertEquals(0.5, $calculatedHours);
    }

    /**
     * Hardening Test 13: Rapid spoofing attack (5 requests in 1 second) is clamped by server elapsed time.
     */
    public function test_rapid_spoofing_clamped_by_server_elapsed_time(): void
    {
        // Attacker sends 5 requests with delta=30 within ~1 second
        for ($i = 0; $i < 5; $i++) {
            $resp = $this->actingAs($this->user)->postJson(route('content.user.progress.update'), [
                'content_id'       => $this->videoContent->id,
                'type'             => 'video',
                'current_time'     => 30 * ($i + 1),
                'duration_seconds' => 600,
                'delta_seconds'    => 30,
                'heartbeat_id'     => (string) Str::uuid(),
                'status'           => 'in_progress',
            ]);
            $resp->assertOk();
            $this->travel(200)->milliseconds();
        }

        $cu = ContentUser::where('user_id', $this->user->id)
            ->where('content_id', $this->videoContent->id)
            ->first();

        // Attacker claimed 5 x 30 = 150 seconds, but server elapsed was ~1 second total!
        // Server clamped the rapid requests, so actual_watch_seconds is capped way below 150s (at most 42s).
        $this->assertLessThan(50, (int) $cu->actual_watch_seconds);
        $this->assertGreaterThan(0, (int) $cu->actual_watch_seconds);
    }

    /**
     * Hardening Test 14: Server elapsed time allows legitimate 10-second heartbeat.
     */
    public function test_server_elapsed_time_allows_legitimate_heartbeat(): void
    {
        // Heartbeat A (10s)
        $this->actingAs($this->user)->postJson(route('content.user.progress.update'), [
            'content_id'       => $this->videoContent->id,
            'type'             => 'video',
            'current_time'     => 10,
            'duration_seconds' => 600,
            'delta_seconds'    => 10,
            'heartbeat_id'     => (string) Str::uuid(),
            'status'           => 'in_progress',
        ]);

        // Legitimate user watches 10 seconds
        $this->travel(10)->seconds();

        // Heartbeat B (delta = 10s)
        $respB = $this->actingAs($this->user)->postJson(route('content.user.progress.update'), [
            'content_id'       => $this->videoContent->id,
            'type'             => 'video',
            'current_time'     => 20,
            'duration_seconds' => 600,
            'delta_seconds'    => 10,
            'heartbeat_id'     => (string) Str::uuid(),
            'status'           => 'in_progress',
        ]);

        $respB->assertOk();
        $respB->assertJson(['actual_watch_seconds' => 20]);
    }

    /**
     * Hardening Test 15: Excessive delta clamped when server elapsed is only 2 seconds.
     */
    public function test_excessive_delta_clamped_to_server_elapsed_plus_tolerance(): void
    {
        // Heartbeat A (10s)
        $this->actingAs($this->user)->postJson(route('content.user.progress.update'), [
            'content_id'       => $this->videoContent->id,
            'type'             => 'video',
            'current_time'     => 10,
            'duration_seconds' => 600,
            'delta_seconds'    => 10,
            'heartbeat_id'     => (string) Str::uuid(),
            'status'           => 'in_progress',
        ]);

        // Only 2 seconds elapsed on server
        $this->travel(2)->seconds();

        // Client maliciously claims delta = 30s
        $resp = $this->actingAs($this->user)->postJson(route('content.user.progress.update'), [
            'content_id'       => $this->videoContent->id,
            'type'             => 'video',
            'current_time'     => 40,
            'duration_seconds' => 600,
            'delta_seconds'    => 30,
            'heartbeat_id'     => (string) Str::uuid(),
            'status'           => 'in_progress',
        ]);

        $resp->assertOk();
        // Server elapsed is 2s + 2s tolerance = 4s max delta.
        // Total actual watch seconds is 10 + 4 = 14s (clamped from 30s!).
        $actual = $resp->json('actual_watch_seconds');
        $this->assertLessThanOrEqual(15, $actual);
        $this->assertGreaterThanOrEqual(13, $actual);
    }

    /**
     * Hardening Test 16: Concurrency / Atomic accumulation (100 + 10 + 10 = 120).
     */
    public function test_concurrent_requests_accumulate_without_lost_updates(): void
    {
        $cu = ContentUser::create([
            'user_id'              => $this->user->id,
            'content_id'           => $this->videoContent->id,
            'current_time'         => 100,
            'actual_watch_seconds' => 100,
            'progress'             => 50,
            'duration_seconds'     => 600,
        ]);

        $this->travel(10)->seconds();

        $respA = $this->actingAs($this->user)->postJson(route('content.user.progress.update'), [
            'content_id'       => $this->videoContent->id,
            'type'             => 'video',
            'current_time'     => 110,
            'duration_seconds' => 600,
            'delta_seconds'    => 10,
            'heartbeat_id'     => (string) Str::uuid(),
            'status'           => 'in_progress',
        ]);
        $respA->assertOk();

        $this->travel(10)->seconds();

        $respB = $this->actingAs($this->user)->postJson(route('content.user.progress.update'), [
            'content_id'       => $this->videoContent->id,
            'type'             => 'video',
            'current_time'     => 120,
            'duration_seconds' => 600,
            'delta_seconds'    => 10,
            'heartbeat_id'     => (string) Str::uuid(),
            'status'           => 'in_progress',
        ]);
        $respB->assertOk();

        $cu->refresh();
        $this->assertSame(120, (int) $cu->actual_watch_seconds);
        $this->assertEquals(round(120 / 3600, 4), round((float) $cu->learning_hours, 4));
    }

    /**
     * Hardening Test 17: Multiple tabs monotonic and isolated accumulation.
     */
    public function test_multiple_tabs_monotonic_and_isolated(): void
    {
        // Tab A watches 10s at position 100
        $this->actingAs($this->user)->postJson(route('content.user.progress.update'), [
            'content_id'       => $this->videoContent->id,
            'type'             => 'video',
            'current_time'     => 100,
            'duration_seconds' => 600,
            'delta_seconds'    => 10,
            'heartbeat_id'     => 'tab-a-' . Str::uuid(),
            'status'           => 'in_progress',
        ]);

        // Tab B is at position 20 and sends heartbeat 10s later
        $this->travel(10)->seconds();

        $respTabB = $this->actingAs($this->user)->postJson(route('content.user.progress.update'), [
            'content_id'       => $this->videoContent->id,
            'type'             => 'video',
            'current_time'     => 20,
            'duration_seconds' => 600,
            'delta_seconds'    => 10,
            'heartbeat_id'     => 'tab-b-' . Str::uuid(),
            'status'           => 'in_progress',
        ]);

        $respTabB->assertOk();
        $cu = ContentUser::where('user_id', $this->user->id)
            ->where('content_id', $this->videoContent->id)
            ->first();

        // actual_watch_seconds accumulated both tabs: 10 + 10 = 20
        $this->assertSame(20, (int) $cu->actual_watch_seconds);
        // actual_watch_seconds never decreased even though Tab B was at position 20
        $this->assertGreaterThanOrEqual(10, (int) $cu->actual_watch_seconds);
    }

    /**
     * Hardening Test 18: Legacy record baselines once and NEVER re-baselines.
     */
    public function test_legacy_record_baselines_once_and_never_repeats(): void
    {
        $legacyCu = ContentUser::create([
            'user_id'              => $this->user->id,
            'content_id'           => $this->videoContent->id,
            'current_time'         => 600, // legacy playhead
            'actual_watch_seconds' => 0,   // no actual watch time yet
            'duration_seconds'     => 1800,
            'progress'             => 33,
        ]);

        // First heartbeat in new system: delta = 10
        $resp1 = $this->actingAs($this->user)->postJson(route('content.user.progress.update'), [
            'content_id'       => $this->videoContent->id,
            'type'             => 'video',
            'current_time'     => 610,
            'duration_seconds' => 1800,
            'delta_seconds'    => 10,
            'heartbeat_id'     => (string) Str::uuid(),
            'status'           => 'in_progress',
        ]);

        $resp1->assertOk();
        // First heartbeat baselined legacy 600 + 10 = 610
        $resp1->assertJson(['actual_watch_seconds' => 610]);

        // Second heartbeat: delta = 10 after 10s of playback
        $this->travel(10)->seconds();

        $resp2 = $this->actingAs($this->user)->postJson(route('content.user.progress.update'), [
            'content_id'       => $this->videoContent->id,
            'type'             => 'video',
            'current_time'     => 620,
            'duration_seconds' => 1800,
            'delta_seconds'    => 10,
            'heartbeat_id'     => (string) Str::uuid(),
            'status'           => 'in_progress',
        ]);

        $resp2->assertOk();
        // Second heartbeat adds 10 to 610 = 620 (DOES NOT re-baseline from 600!)
        $resp2->assertJson(['actual_watch_seconds' => 620]);

        $legacyCu->refresh();
        $this->assertSame(620, (int) $legacyCu->actual_watch_seconds);
    }

    /**
     * Hardening Test 18: Actual learning hours capped at double content learning hour for Standalone Video.
     */
    public function test_actual_learning_hours_capped_at_double_content_learning_hour(): void
    {
        $video = Content::create([
            'title'          => 'Capped Video Test',
            'type'           => 'Video',
            'duration'       => '00:20:00',
            'active'         => 'Active',
            'playlistonly'   => 'N',
            'has_deadline'   => 0,
            'learning_hours' => 0.33, // Max LH = 0.66, Max seconds = round(0.66 * 3600) = 2376
        ]);

        $this->assertEquals(0.66, (float) $video->max_learning_hours);
        $this->assertEquals(2376, $video->max_actual_watch_seconds);

        $cu = ContentUser::create([
            'user_id'              => $this->user->id,
            'content_id'           => $video->id,
            'current_time'         => 600,
            'duration_seconds'     => 1200,
            'actual_watch_seconds' => 2370,
            'progress'             => 50,
        ]);

        $response = $this->actingAs($this->user)->postJson(route('content.user.progress.update'), [
            'content_id'       => $video->id,
            'type'             => 'video',
            'current_time'     => 620,
            'duration_seconds' => 1200,
            'delta_seconds'    => 20, // 2370 + 20 = 2390, exceeds 2376
            'heartbeat_id'     => (string) Str::uuid(),
            'status'           => 'in_progress',
        ]);

        $response->assertOk();
        $response->assertJson([
            'success'                  => true,
            'actual_watch_seconds'     => 2376,
            'learning_hours'           => 0.6600,
            'actual_learning_hours'    => 0.6600,
            'is_capped'                => true,
            'max_learning_hours'       => 0.66,
            'max_actual_watch_seconds' => 2376,
        ]);

        $cu->refresh();
        $this->assertEquals(2376, $cu->actual_watch_seconds);
        $this->assertEquals(0.6600, (float) $cu->learning_hours);
    }

    /**
     * Hardening Test 19: Heartbeat payload clamped when exceeding cap for Video.
     */
    public function test_heartbeat_payload_clamped_when_exceeding_cap(): void
    {
        $video = Content::create([
            'title'          => 'Capped Video Spoof Test',
            'type'           => 'Video',
            'duration'       => '00:20:00',
            'active'         => 'Active',
            'playlistonly'   => 'N',
            'has_deadline'   => 0,
            'learning_hours' => 0.33, // Cap = 2376
        ]);

        $cu = ContentUser::create([
            'user_id'              => $this->user->id,
            'content_id'           => $video->id,
            'current_time'         => 800,
            'duration_seconds'     => 1200,
            'actual_watch_seconds' => 2376, // Already at cap
            'progress'             => 66,
        ]);

        $response = $this->actingAs($this->user)->postJson(route('content.user.progress.update'), [
            'content_id'       => $video->id,
            'type'             => 'video',
            'current_time'     => 810,
            'duration_seconds' => 1200,
            'delta_seconds'    => 10,
            'heartbeat_id'     => (string) Str::uuid(),
            'status'           => 'in_progress',
        ]);

        $response->assertOk();
        $response->assertJson([
            'success'              => true,
            'actual_watch_seconds' => 2376,
            'learning_hours'       => 0.6600,
            'is_capped'            => true,
        ]);

        $cu->refresh();
        $this->assertEquals(2376, $cu->actual_watch_seconds);
    }

    /**
     * Hardening Test 20: Video progress and 100% completion still work even when learning hours capped.
     */
    public function test_progress_and_completion_still_work_even_when_learning_hours_capped(): void
    {
        $video = Content::create([
            'title'          => 'Capped Video Completion Test',
            'type'           => 'Video',
            'duration'       => '00:10:00', // 600s
            'active'         => 'Active',
            'playlistonly'   => 'N',
            'has_deadline'   => 0,
            'learning_hours' => 0.1, // Max = 0.2 LH = 720 seconds
        ]);

        $cu = ContentUser::create([
            'user_id'              => $this->user->id,
            'content_id'           => $video->id,
            'current_time'         => 300,
            'duration_seconds'     => 600,
            'actual_watch_seconds' => 720, // Already capped
            'progress'             => 50,
        ]);

        // User finishes watching the video (current_time = 600, status = 'completed')
        $response = $this->actingAs($this->user)->postJson(route('content.user.progress.update'), [
            'content_id'       => $video->id,
            'type'             => 'video',
            'current_time'     => 600,
            'duration_seconds' => 600,
            'delta_seconds'    => 0, // No watch time delta because capped
            'heartbeat_id'     => (string) Str::uuid(),
            'status'           => 'completed',
        ]);

        $response->assertOk();
        $response->assertJson([
            'success'      => true,
            'progress'     => 100,
            'finish'       => 1,
            'status'       => 'completed',
            'current_time' => 600,
            'is_capped'    => true,
        ]);

        $cu->refresh();
        $this->assertEquals(100, $cu->progress);
        $this->assertEquals('1', (string) $cu->finish);
        $this->assertEquals(720, $cu->actual_watch_seconds);
        $this->assertEquals(0.2000, (float) $cu->learning_hours);
        $this->assertNotNull($cu->completed_at);
    }
}
