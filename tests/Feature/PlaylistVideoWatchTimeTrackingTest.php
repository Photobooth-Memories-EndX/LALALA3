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

class PlaylistVideoWatchTimeTrackingTest extends TestCase
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
            'title'          => 'Playlist Video Test',
            'type'           => 'Video',
            'duration'       => '00:01:00', // 60 seconds
            'active'         => 'Active',
            'playlistonly'   => 'Y',
            'has_deadline'   => 0,
            'learning_hours' => 1.0,
        ]);

        $this->docContent = Content::create([
            'title'          => 'Playlist Doc Scroll Test',
            'type'           => 'Document',
            'duration'       => '00:05:00',
            'page_duration'  => 0, // Scroll mode
            'total_page'     => 10,
            'active'         => 'Active',
            'playlistonly'   => 'Y',
            'has_deadline'   => 0,
            'learning_hours' => 0.5,
        ]);

        $this->playlist = TrainingPlan::create([
            'title'                  => 'Playlist Actual Watch Time Test',
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
     * Scenario 1: First heartbeat menambah actual_watch_seconds.
     */
    public function test_1_first_heartbeat_accumulates_actual_watch_seconds(): void
    {
        $response = $this->actingAs($this->user)->postJson(route('playlist.user.progress.update'), [
            'trainingplan_id'  => $this->playlist->id,
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
            'success'              => true,
            'actual_watch_seconds' => 10,
            'current_time'         => 10,
        ]);

        $pcu = PlaylistContentUser::where('user_id', $this->user->id)
            ->where('trainingplan_id', $this->playlist->id)
            ->where('content_id', $this->videoContent->id)
            ->first();

        $this->assertNotNull($pcu);
        $this->assertSame(10, (int) $pcu->actual_watch_seconds);
        $this->assertSame(10, (int) $pcu->current_time);
        $this->assertEquals(round(10 / 3600, 4), round((float) $pcu->learning_hour, 4));
    }

    /**
     * Scenario 2: Normal consecutive heartbeats menambah actual_watch_seconds.
     */
    public function test_2_normal_consecutive_heartbeats_accumulate_actual_watch_seconds(): void
    {
        // First heartbeat: 10s
        $this->actingAs($this->user)->postJson(route('playlist.user.progress.update'), [
            'trainingplan_id'  => $this->playlist->id,
            'content_id'       => $this->videoContent->id,
            'type'             => 'video',
            'current_time'     => 10,
            'duration_seconds' => 60,
            'delta_seconds'    => 10,
            'heartbeat_id'     => (string) Str::uuid(),
            'status'           => 'in_progress',
        ]);

        $this->travel(10)->seconds();

        // Second heartbeat: +10s (position 20s)
        $response = $this->actingAs($this->user)->postJson(route('playlist.user.progress.update'), [
            'trainingplan_id'  => $this->playlist->id,
            'content_id'       => $this->videoContent->id,
            'type'             => 'video',
            'current_time'     => 20,
            'duration_seconds' => 60,
            'delta_seconds'    => 10,
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

        $this->assertSame(20, (int) $pcu->actual_watch_seconds);
        $this->assertEquals(round(20 / 3600, 4), round((float) $pcu->learning_hour, 4));
    }

    /**
     * Scenario 3: Pause flush.
     */
    public function test_3_pause_flushes_remaining_delta_and_marks_paused(): void
    {
        // First heartbeat (10s)
        $this->actingAs($this->user)->postJson(route('playlist.user.progress.update'), [
            'trainingplan_id'  => $this->playlist->id,
            'content_id'       => $this->videoContent->id,
            'type'             => 'video',
            'current_time'     => 10,
            'duration_seconds' => 60,
            'delta_seconds'    => 10,
            'heartbeat_id'     => (string) Str::uuid(),
            'status'           => 'in_progress',
        ]);

        $this->travel(5)->seconds();

        // Pause event flushes final 5 seconds
        $response = $this->actingAs($this->user)->postJson(route('playlist.user.progress.update'), [
            'trainingplan_id'  => $this->playlist->id,
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
            'success'              => true,
            'actual_watch_seconds' => 15,
            'status'               => 'paused',
        ]);

        $pcu = PlaylistContentUser::where('user_id', $this->user->id)
            ->where('trainingplan_id', $this->playlist->id)
            ->where('content_id', $this->videoContent->id)
            ->first();

        $this->assertSame(15, (int) $pcu->actual_watch_seconds);
        $this->assertSame('paused', $pcu->learning_status);
    }

    /**
     * Scenario 4: Seek forward tidak dihitung sebagai watch time.
     */
    public function test_4_seek_forward_does_not_inflate_actual_watch_seconds(): void
    {
        // Watched 5 seconds
        $this->actingAs($this->user)->postJson(route('playlist.user.progress.update'), [
            'trainingplan_id'  => $this->playlist->id,
            'content_id'       => $this->videoContent->id,
            'type'             => 'video',
            'current_time'     => 5,
            'duration_seconds' => 60,
            'delta_seconds'    => 5,
            'heartbeat_id'     => (string) Str::uuid(),
            'status'           => 'in_progress',
        ]);

        // Seek forward to 50s (client tracker sends 0 delta during seek)
        $seekResponse = $this->actingAs($this->user)->postJson(route('playlist.user.progress.update'), [
            'trainingplan_id'  => $this->playlist->id,
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
            'current_time'         => 50, // Position updated for resume
        ]);

        // Play 10 seconds starting from 50s (50s -> 60s)
        $this->travel(10)->seconds();

        $playAfterSeek = $this->actingAs($this->user)->postJson(route('playlist.user.progress.update'), [
            'trainingplan_id'  => $this->playlist->id,
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
     * Scenario 5: Rewind tidak mengurangi accumulator.
     */
    public function test_5_rewind_does_not_decrease_accumulator(): void
    {
        $pcu = PlaylistContentUser::create([
            'user_id'              => $this->user->id,
            'trainingplan_id'      => $this->playlist->id,
            'content_id'           => $this->videoContent->id,
            'current_time'         => 50,
            'actual_watch_seconds' => 40,
            'progress'             => 83,
        ]);

        // User rewinds back to 10s
        $response = $this->actingAs($this->user)->postJson(route('playlist.user.progress.update'), [
            'trainingplan_id'  => $this->playlist->id,
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
            'actual_watch_seconds' => 40, // Does NOT decrease!
            'current_time'         => 10, // Position rewinds for resume
            'progress'             => 83, // Monotonic progress preserved
        ]);

        $pcu->refresh();
        $this->assertSame(40, (int) $pcu->actual_watch_seconds);
        $this->assertSame(10, (int) $pcu->current_time);
        $this->assertSame(83, (int) $pcu->progress);
    }

    /**
     * Scenario 6: Replay menambah accumulator lagi (tanpa cap).
     */
    public function test_6_replay_accumulates_past_duration_without_cap(): void
    {
        // 1st watch: 30s + 30s = 60s
        $this->actingAs($this->user)->postJson(route('playlist.user.progress.update'), [
            'trainingplan_id'  => $this->playlist->id,
            'content_id'       => $this->videoContent->id,
            'type'             => 'video',
            'current_time'     => 30,
            'duration_seconds' => 60,
            'delta_seconds'    => 30,
            'heartbeat_id'     => (string) Str::uuid(),
            'status'           => 'in_progress',
        ]);

        $this->travel(30)->seconds();

        $this->actingAs($this->user)->postJson(route('playlist.user.progress.update'), [
            'trainingplan_id'  => $this->playlist->id,
            'content_id'       => $this->videoContent->id,
            'type'             => 'video',
            'current_time'     => 60,
            'duration_seconds' => 60,
            'delta_seconds'    => 30,
            'heartbeat_id'     => (string) Str::uuid(),
            'status'           => 'completed',
        ]);

        $pcu = PlaylistContentUser::where('user_id', $this->user->id)
            ->where('trainingplan_id', $this->playlist->id)
            ->where('content_id', $this->videoContent->id)
            ->first();

        $this->assertSame(60, (int) $pcu->actual_watch_seconds);
        $this->assertSame('1', (string) $pcu->finish);

        // 2nd watch (replay): user watches another 30s + 30s
        $this->travel(30)->seconds();

        $this->actingAs($this->user)->postJson(route('playlist.user.progress.update'), [
            'trainingplan_id'  => $this->playlist->id,
            'content_id'       => $this->videoContent->id,
            'type'             => 'video',
            'current_time'     => 30,
            'duration_seconds' => 60,
            'delta_seconds'    => 30,
            'heartbeat_id'     => (string) Str::uuid(),
            'status'           => 'in_progress',
        ]);

        $this->travel(30)->seconds();

        $response = $this->actingAs($this->user)->postJson(route('playlist.user.progress.update'), [
            'trainingplan_id'  => $this->playlist->id,
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

        $pcu->refresh();
        $this->assertSame(120, (int) $pcu->actual_watch_seconds);
        $this->assertGreaterThan(60, (int) $pcu->actual_watch_seconds);
        $this->assertEquals(round(120 / 3600, 4), round((float) $pcu->learning_hour, 4));
    }

    /**
     * Scenario 7: Duplicate heartbeat_id tidak menambah dua kali.
     */
    public function test_7_duplicate_heartbeat_id_does_not_increment_twice(): void
    {
        $hbId = (string) Str::uuid();

        // Send first heartbeat
        $first = $this->actingAs($this->user)->postJson(route('playlist.user.progress.update'), [
            'trainingplan_id'  => $this->playlist->id,
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
        $duplicate = $this->actingAs($this->user)->postJson(route('playlist.user.progress.update'), [
            'trainingplan_id'  => $this->playlist->id,
            'content_id'       => $this->videoContent->id,
            'type'             => 'video',
            'current_time'     => 10,
            'duration_seconds' => 60,
            'delta_seconds'    => 10,
            'heartbeat_id'     => $hbId,
            'status'           => 'in_progress',
        ]);

        $duplicate->assertJson(['actual_watch_seconds' => 10]);

        $pcu = PlaylistContentUser::where('user_id', $this->user->id)
            ->where('trainingplan_id', $this->playlist->id)
            ->where('content_id', $this->videoContent->id)
            ->first();

        $this->assertSame(10, (int) $pcu->actual_watch_seconds);
    }

    /**
     * Scenario 8: Rapid spoof heartbeat ditolak/dibatasi berdasarkan elapsed server time.
     */
    public function test_8_rapid_spoof_heartbeat_clamped_by_server_elapsed_time(): void
    {
        // Attacker sends 5 requests claiming delta=30 within ~1 second
        for ($i = 0; $i < 5; $i++) {
            $resp = $this->actingAs($this->user)->postJson(route('playlist.user.progress.update'), [
                'trainingplan_id'  => $this->playlist->id,
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

        $pcu = PlaylistContentUser::where('user_id', $this->user->id)
            ->where('trainingplan_id', $this->playlist->id)
            ->where('content_id', $this->videoContent->id)
            ->first();

        // Server clamped the rapid requests: actual_watch_seconds is capped way below 150s (at most 42s).
        $this->assertLessThan(50, (int) $pcu->actual_watch_seconds);
        $this->assertGreaterThan(0, (int) $pcu->actual_watch_seconds);
    }

    /**
     * Scenario 9: Excessive delta_seconds tidak dapat memberikan watch time berlebihan.
     */
    public function test_9_excessive_delta_seconds_clamped(): void
    {
        // First legitimate heartbeat (10s)
        $this->actingAs($this->user)->postJson(route('playlist.user.progress.update'), [
            'trainingplan_id'  => $this->playlist->id,
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
        $resp = $this->actingAs($this->user)->postJson(route('playlist.user.progress.update'), [
            'trainingplan_id'  => $this->playlist->id,
            'content_id'       => $this->videoContent->id,
            'type'             => 'video',
            'current_time'     => 40,
            'duration_seconds' => 600,
            'delta_seconds'    => 30,
            'heartbeat_id'     => (string) Str::uuid(),
            'status'           => 'in_progress',
        ]);

        $resp->assertOk();
        // Server elapsed was 2s + 2s tolerance = 4s max delta.
        // Total actual watch seconds is clamped to ~14s (clamped from 30s!).
        $actual = $resp->json('actual_watch_seconds');
        $this->assertLessThanOrEqual(15, $actual);
        $this->assertGreaterThanOrEqual(13, $actual);
    }

    /**
     * Scenario 10: Concurrent heartbeat tidak kehilangan increment.
     */
    public function test_10_concurrent_heartbeats_accumulate_without_lost_updates(): void
    {
        $pcu = PlaylistContentUser::create([
            'user_id'              => $this->user->id,
            'trainingplan_id'      => $this->playlist->id,
            'content_id'           => $this->videoContent->id,
            'current_time'         => 100,
            'actual_watch_seconds' => 100,
            'progress'             => 50,
            'duration_seconds'     => 600,
        ]);

        $this->travel(10)->seconds();

        $respA = $this->actingAs($this->user)->postJson(route('playlist.user.progress.update'), [
            'trainingplan_id'  => $this->playlist->id,
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

        $respB = $this->actingAs($this->user)->postJson(route('playlist.user.progress.update'), [
            'trainingplan_id'  => $this->playlist->id,
            'content_id'       => $this->videoContent->id,
            'type'             => 'video',
            'current_time'     => 120,
            'duration_seconds' => 600,
            'delta_seconds'    => 10,
            'heartbeat_id'     => (string) Str::uuid(),
            'status'           => 'in_progress',
        ]);
        $respB->assertOk();

        $pcu->refresh();
        $this->assertSame(120, (int) $pcu->actual_watch_seconds);
        $this->assertEquals(round(120 / 3600, 4), round((float) $pcu->learning_hour, 4));
    }

    /**
     * Scenario 11: Multiple tab tidak menyebabkan duplicate processing.
     */
    public function test_11_multiple_tabs_accumulate_safely(): void
    {
        // Tab A watches 10s at position 100
        $this->actingAs($this->user)->postJson(route('playlist.user.progress.update'), [
            'trainingplan_id'  => $this->playlist->id,
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

        $respTabB = $this->actingAs($this->user)->postJson(route('playlist.user.progress.update'), [
            'trainingplan_id'  => $this->playlist->id,
            'content_id'       => $this->videoContent->id,
            'type'             => 'video',
            'current_time'     => 20,
            'duration_seconds' => 600,
            'delta_seconds'    => 10,
            'heartbeat_id'     => 'tab-b-' . Str::uuid(),
            'status'           => 'in_progress',
        ]);

        $respTabB->assertOk();
        $pcu = PlaylistContentUser::where('user_id', $this->user->id)
            ->where('trainingplan_id', $this->playlist->id)
            ->where('content_id', $this->videoContent->id)
            ->first();

        // Both heartbeats accumulated safely: 10 + 10 = 20
        $this->assertSame(20, (int) $pcu->actual_watch_seconds);
    }

    /**
     * Scenario 12: Learning Hours dihitung dari actual_watch_seconds.
     */
    public function test_12_learning_hours_calculated_from_actual_watch_seconds(): void
    {
        $this->videoContent->update(['duration' => '00:15:00', 'duration_in_seconds' => 900]);

        // Replayed 15m video multiple times: actual_watch_seconds = 1800 (30 mins = 0.5 hour, within 2x cap)
        $pcu = PlaylistContentUser::create([
            'user_id'              => $this->user->id,
            'trainingplan_id'      => $this->playlist->id,
            'content_id'           => $this->videoContent->id,
            'current_time'         => 900,
            'actual_watch_seconds' => 1800,
            'duration_seconds'     => 900,
            'progress'             => 100,
            'finish'               => '1',
        ]);

        $pcu->refresh();
        // Model boot hook calculated learning_hour strictly from actual_watch_seconds
        $this->assertEquals(0.5, (float) $pcu->learning_hour);

        // LearningRecordLearningHourService batch calculation for playlist
        $service = new LearningRecordLearningHourService();
        $hoursMap = $service->calculatePlaylistsBatch($this->user->id, collect([$this->playlist]));

        $this->assertTrue($hoursMap->has($this->playlist->id));
        $this->assertEquals(0.5, $hoursMap->get($this->playlist->id));

        $calc = new ActualLearningHourCalculator();
        $calculated = $calc->calculateFromTracking(
            $pcu->current_time,
            $pcu->finish,
            $pcu->progress,
            $this->videoContent->duration,
            $pcu->actual_watch_seconds
        );
        $this->assertEquals(0.5, $calculated);
    }

    /**
     * Scenario 13: Playlist hanya update playlist_content_users.
     */
    public function test_13_playlist_only_updates_playlist_content_users(): void
    {
        $response = $this->actingAs($this->user)->postJson(route('playlist.user.progress.update'), [
            'trainingplan_id'  => $this->playlist->id,
            'content_id'       => $this->videoContent->id,
            'type'             => 'video',
            'current_time'     => 25,
            'duration_seconds' => 60,
            'delta_seconds'    => 25,
            'heartbeat_id'     => (string) Str::uuid(),
            'status'           => 'in_progress',
        ]);

        $response->assertOk();

        // playlist_content_users updated
        $pcu = PlaylistContentUser::where('user_id', $this->user->id)
            ->where('trainingplan_id', $this->playlist->id)
            ->where('content_id', $this->videoContent->id)
            ->first();

        $this->assertNotNull($pcu);
        $this->assertSame(25, (int) $pcu->actual_watch_seconds);

        // content_users is NOT created or modified
        $cu = ContentUser::where('user_id', $this->user->id)
            ->where('content_id', $this->videoContent->id)
            ->first();

        $this->assertNull($cu);
    }

    /**
     * Scenario 14: Content standalone tidak ikut berubah.
     */
    public function test_14_standalone_content_does_not_affect_playlist_content_users(): void
    {
        // Seed existing playlist progress
        $pcu = PlaylistContentUser::create([
            'user_id'              => $this->user->id,
            'trainingplan_id'      => $this->playlist->id,
            'content_id'           => $this->videoContent->id,
            'current_time'         => 10,
            'actual_watch_seconds' => 10,
            'progress'             => 16,
        ]);

        // Watch video as standalone content
        $response = $this->actingAs($this->user)->postJson(route('content.user.progress.update'), [
            'content_id'       => $this->videoContent->id,
            'type'             => 'video',
            'current_time'     => 30,
            'duration_seconds' => 60,
            'delta_seconds'    => 30,
            'heartbeat_id'     => (string) Str::uuid(),
            'status'           => 'in_progress',
        ]);

        $response->assertOk();

        // content_users is updated
        $cu = ContentUser::where('user_id', $this->user->id)
            ->where('content_id', $this->videoContent->id)
            ->first();

        $this->assertNotNull($cu);
        $this->assertSame(30, (int) $cu->actual_watch_seconds);

        // playlist_content_users remained isolated and untouched
        $pcu->refresh();
        $this->assertSame(10, (int) $pcu->actual_watch_seconds);
        $this->assertSame(10, (int) $pcu->current_time);
        $this->assertSame(16, (int) $pcu->progress);
    }

    /**
     * Scenario 15: Existing playlist PDF/document regression tetap pass.
     */
    public function test_15_existing_playlist_pdf_and_document_regression_pass(): void
    {
        $response = $this->actingAs($this->user)->postJson(route('playlist.user.progress.update'), [
            'trainingplan_id' => $this->playlist->id,
            'content_id'      => $this->docContent->id,
            'type'            => 'document',
            'last_page'       => 5,
            'total_pages'     => 10,
            'status'          => 'in_progress',
        ]);

        $response->assertOk();
        $response->assertJson([
            'success'   => true,
            'progress'  => 50,
            'last_page' => 5,
        ]);

        $pcu = PlaylistContentUser::where('user_id', $this->user->id)
            ->where('trainingplan_id', $this->playlist->id)
            ->where('content_id', $this->docContent->id)
            ->first();

        $this->assertNotNull($pcu);
        $this->assertSame(5, (int) $pcu->last_page);
        $this->assertSame(50, (int) $pcu->progress);
        $this->assertSame(0, (int) ($pcu->actual_watch_seconds ?? 0));
    }

    /**
     * Scenario 16: Playlist actual learning hours capped at double content learning hour (Video & PDF).
     */
    public function test_playlist_actual_learning_hours_capped_properly(): void
    {
        // $this->docContent has learning_hours = 0.5 -> max LH = 1.0 -> max seconds = 3600
        $this->assertEquals(0.5, (float) $this->docContent->learning_hours);
        $this->assertEquals(1.0, (float) $this->docContent->max_learning_hours);
        $this->assertEquals(3600, $this->docContent->max_actual_watch_seconds);

        $pcu = PlaylistContentUser::create([
            'user_id'              => $this->user->id,
            'trainingplan_id'      => $this->playlist->id,
            'content_id'           => $this->docContent->id,
            'actual_watch_seconds' => 3590,
            'progress'             => 50,
            'last_page'            => 5,
            'total_pages'          => 10,
        ]);

        $response = $this->actingAs($this->user)->postJson(route('playlist.user.progress.update'), [
            'trainingplan_id' => $this->playlist->id,
            'content_id'      => $this->docContent->id,
            'type'            => 'document',
            'delta_seconds'   => 20, // 3590 + 20 = 3610 -> capped to 3600
            'heartbeat_id'    => (string) Str::uuid(),
            'status'          => 'in_progress',
        ]);

        $response->assertOk();
        $response->assertJson([
            'success'                  => true,
            'actual_watch_seconds'     => 3600,
            'learning_hour'            => 1.0000,
            'actual_learning_hours'    => 1.0000,
            'is_capped'                => true,
            'max_learning_hours'       => 1.0,
            'max_actual_watch_seconds' => 3600,
        ]);

        $pcu->refresh();
        $this->assertEquals(3600, $pcu->actual_watch_seconds);
        $this->assertEquals(1.0000, (float) $pcu->learning_hour);
    }

    /**
     * Scenario 17: Playlist progress and completion still work even when learning hours capped.
     */
    public function test_playlist_progress_and_completion_still_work_even_when_learning_hours_capped(): void
    {
        $pcu = PlaylistContentUser::create([
            'user_id'              => $this->user->id,
            'trainingplan_id'      => $this->playlist->id,
            'content_id'           => $this->videoContent->id, // 60s video, LH = 1.0 -> cap = 7200s
            'current_time'         => 30,
            'duration_seconds'     => 60,
            'actual_watch_seconds' => 7200, // Capped
            'progress'             => 50,
        ]);

        $response = $this->actingAs($this->user)->postJson(route('playlist.user.progress.update'), [
            'trainingplan_id'  => $this->playlist->id,
            'content_id'       => $this->videoContent->id,
            'type'             => 'video',
            'current_time'     => 60,
            'duration_seconds' => 60,
            'delta_seconds'    => 0,
            'heartbeat_id'     => (string) Str::uuid(),
            'status'           => 'completed',
        ]);

        $response->assertOk();
        $response->assertJson([
            'success'      => true,
            'progress'     => 100,
            'finish'       => 1,
            'status'       => 'completed',
            'current_time' => 60,
            'is_capped'    => true,
        ]);

        $pcu->refresh();
        $this->assertEquals(100, $pcu->progress);
        $this->assertEquals('1', (string) $pcu->finish);
        $this->assertEquals(7200, $pcu->actual_watch_seconds);
        $this->assertNotNull($pcu->completed_at);
    }
}
