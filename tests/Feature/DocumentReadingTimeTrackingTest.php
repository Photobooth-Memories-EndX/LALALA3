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

class DocumentReadingTimeTrackingTest extends TestCase
{
    use DatabaseTransactions;

    protected User $user;
    protected Content $docContent;
    protected Content $timedDocContent;
    protected Content $videoContent;
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
            'sap_code'   => 'SAP-DOC-' . rand(1000, 9999),
            'role'       => 1,
            'active'     => 1,
        ]);

        // Scroll mode document (page_duration = 0)
        $this->docContent = Content::create([
            'title'          => 'Test Scroll Doc Reading Time',
            'type'           => 'Document',
            'duration'       => '00:10:00',
            'page_duration'  => 0,
            'total_page'     => 10,
            'active'         => 'Active',
            'playlistonly'   => 'N',
            'has_deadline'   => 0,
            'learning_hours' => 0.5,
        ]);

        // Timed document (page_duration = 30s)
        $this->timedDocContent = Content::create([
            'title'          => 'Test Timed Doc Reading Time',
            'type'           => 'Document',
            'duration'       => '00:05:00',
            'page_duration'  => 30,
            'total_page'     => 10,
            'active'         => 'Active',
            'playlistonly'   => 'N',
            'has_deadline'   => 0,
            'learning_hours' => 0.5,
        ]);

        $this->videoContent = Content::create([
            'title'          => 'Test Video Isolation',
            'type'           => 'Video',
            'duration'       => '00:02:00',
            'active'         => 'Active',
            'playlistonly'   => 'N',
            'has_deadline'   => 0,
            'learning_hours' => 1.0,
        ]);

        $this->playlist = TrainingPlan::create([
            'title'                  => 'Test Playlist Doc Reading Time',
            'active'                 => 'Active',
            'total_duration_seconds' => 3600,
            'learning_hours'         => 1.0,
            'has_deadline'           => 0,
        ]);

        TrainingPlanDetail::create([
            'trainingplan_id' => $this->playlist->id,
            'content_id'      => $this->docContent->id,
            'content_order'   => 0,
        ]);

        TrainingPlanDetail::create([
            'trainingplan_id' => $this->playlist->id,
            'content_id'      => $this->timedDocContent->id,
            'content_order'   => 1,
        ]);
    }

    /**
     * Case 1: PDF dibuka dan dibaca selama 60 detik -> actual_watch_seconds = 60.
     */
    public function test_pdf_reading_accumulates_actual_watch_seconds(): void
    {
        $response = $this->actingAs($this->user)->postJson(route('content.user.progress.update'), [
            'content_id'    => $this->docContent->id,
            'type'          => 'document',
            'last_page'     => 1,
            'total_pages'   => 10,
            'delta_seconds' => 30,
            'heartbeat_id'  => (string) Str::uuid(),
            'status'        => 'in_progress',
        ]);

        $response->assertOk();
        $response->assertJson([
            'actual_watch_seconds' => 30,
        ]);

        // Second heartbeat (30s later)
        $now = (float) (now()->getPreciseTimestamp(3) / 1000);
        Cache::put("document_last_hb_time:{$this->user->id}:{$this->docContent->id}", $now - 30, now()->addMinutes(30));

        $response2 = $this->actingAs($this->user)->postJson(route('content.user.progress.update'), [
            'content_id'    => $this->docContent->id,
            'type'          => 'document',
            'last_page'     => 2,
            'total_pages'   => 10,
            'delta_seconds' => 30,
            'heartbeat_id'  => (string) Str::uuid(),
            'status'        => 'in_progress',
        ]);

        $response2->assertOk();
        $response2->assertJson([
            'actual_watch_seconds' => 60,
        ]);

        $cu = ContentUser::where('user_id', $this->user->id)->where('content_id', $this->docContent->id)->first();
        $this->assertEquals(60, $cu->actual_watch_seconds);
        $this->assertEquals(round(60 / 3600, 4), (float) $cu->learning_hours);
    }

    /**
     * Case 2 & 3: PDF dibuka 60 detik lalu pindah tab 120 detik -> tab hidden tidak dihitung -> kembali membaca 30s -> total 90.
     */
    public function test_visibility_pause_and_resume(): void
    {
        // User reads for 60s
        $this->actingAs($this->user)->postJson(route('content.user.progress.update'), [
            'content_id'    => $this->docContent->id,
            'type'          => 'document',
            'delta_seconds' => 30,
            'heartbeat_id'  => (string) Str::uuid(),
        ]);

        $now = (float) (now()->getPreciseTimestamp(3) / 1000);
        Cache::put("document_last_hb_time:{$this->user->id}:{$this->docContent->id}", $now - 30, now()->addMinutes(30));

        $this->actingAs($this->user)->postJson(route('content.user.progress.update'), [
            'content_id'    => $this->docContent->id,
            'type'          => 'document',
            'delta_seconds' => 30,
            'heartbeat_id'  => (string) Str::uuid(),
        ]);

        $cu = ContentUser::where('user_id', $this->user->id)->where('content_id', $this->docContent->id)->first();
        $this->assertEquals(60, $cu->actual_watch_seconds);

        // Tab is hidden for 120 seconds: client DocumentReadingTracker freezes and does NOT accumulate time.
        // When returning to tab, it resumes ticking from 0. User then reads for 30s.
        $nowReturn = (float) (now()->getPreciseTimestamp(3) / 1000);
        Cache::put("document_last_hb_time:{$this->user->id}:{$this->docContent->id}", $nowReturn - 30, now()->addMinutes(30));

        $response = $this->actingAs($this->user)->postJson(route('content.user.progress.update'), [
            'content_id'    => $this->docContent->id,
            'type'          => 'document',
            'delta_seconds' => 30,
            'heartbeat_id'  => (string) Str::uuid(),
        ]);

        $response->assertOk();
        $response->assertJson([
            'actual_watch_seconds' => 90,
        ]);

        $cu->refresh();
        $this->assertEquals(90, $cu->actual_watch_seconds);
    }

    /**
     * Case 4 & 5: Pindah halaman (page 1: 30s, page 2: 60s -> 90s), kembali ke page 1 dan baca 20s -> 110s.
     */
    public function test_page_navigation_accumulates_across_pages(): void
    {
        // Page 1: 30s
        $this->actingAs($this->user)->postJson(route('content.user.progress.update'), [
            'content_id'    => $this->docContent->id,
            'type'          => 'document',
            'last_page'     => 1,
            'total_pages'   => 10,
            'delta_seconds' => 30,
            'heartbeat_id'  => (string) Str::uuid(),
        ]);

        // Navigate to Page 2: reads for 30s + 30s = 60s
        $now = (float) (now()->getPreciseTimestamp(3) / 1000);
        Cache::put("document_last_hb_time:{$this->user->id}:{$this->docContent->id}", $now - 30, now()->addMinutes(30));
        $this->actingAs($this->user)->postJson(route('content.user.progress.update'), [
            'content_id'    => $this->docContent->id,
            'type'          => 'document',
            'last_page'     => 2,
            'total_pages'   => 10,
            'delta_seconds' => 30,
            'heartbeat_id'  => (string) Str::uuid(),
        ]);

        $now = (float) (now()->getPreciseTimestamp(3) / 1000);
        Cache::put("document_last_hb_time:{$this->user->id}:{$this->docContent->id}", $now - 30, now()->addMinutes(30));
        $this->actingAs($this->user)->postJson(route('content.user.progress.update'), [
            'content_id'    => $this->docContent->id,
            'type'          => 'document',
            'last_page'     => 2,
            'total_pages'   => 10,
            'delta_seconds' => 30,
            'heartbeat_id'  => (string) Str::uuid(),
        ]);

        $cu = ContentUser::where('user_id', $this->user->id)->where('content_id', $this->docContent->id)->first();
        $this->assertEquals(90, $cu->actual_watch_seconds);

        // User flips back to Page 1 and reads for 20s
        $now = (float) (now()->getPreciseTimestamp(3) / 1000);
        Cache::put("document_last_hb_time:{$this->user->id}:{$this->docContent->id}", $now - 20, now()->addMinutes(30));
        $this->actingAs($this->user)->postJson(route('content.user.progress.update'), [
            'content_id'    => $this->docContent->id,
            'type'          => 'document',
            'last_page'     => 1,
            'total_pages'   => 10,
            'delta_seconds' => 20,
            'heartbeat_id'  => (string) Str::uuid(),
        ]);

        $cu->refresh();
        $this->assertEquals(110, $cu->actual_watch_seconds);
        // Monotonic check: last_page should remain at highest reached (page 2)
        $this->assertEquals(2, $cu->last_page);
    }

    /**
     * Case 6: Progress tidak berubah ketika hanya timer berjalan.
     */
    public function test_progress_does_not_change_when_only_timer_ticks(): void
    {
        // Initial state: page 3 out of 10 (30%)
        ContentUser::create([
            'user_id'              => $this->user->id,
            'content_id'           => $this->docContent->id,
            'progress'             => 30,
            'last_page'            => 3,
            'total_pages'          => 10,
            'actual_watch_seconds' => 50,
        ]);

        // Heartbeat sent with delta only (no page advancement)
        $response = $this->actingAs($this->user)->postJson(route('content.user.progress.update'), [
            'content_id'    => $this->docContent->id,
            'type'          => 'document',
            'delta_seconds' => 10,
            'heartbeat_id'  => (string) Str::uuid(),
            'status'        => 'in_progress',
        ]);

        $response->assertOk();
        $response->assertJson([
            'progress'             => 30,
            'last_page'            => 3,
            'actual_watch_seconds' => 60,
        ]);

        $cu = ContentUser::where('user_id', $this->user->id)->where('content_id', $this->docContent->id)->first();
        $this->assertEquals(30, $cu->progress);
        $this->assertEquals(3, $cu->last_page);
        $this->assertEquals(60, $cu->actual_watch_seconds);
    }

    /**
     * Case 7: Actual time tidak berubah hanya karena user seek/navigate halaman dengan cepat.
     */
    public function test_rapid_page_turn_does_not_inflate_actual_watch_seconds(): void
    {
        // User flips rapidly through pages 1 to 5 with delta = 0
        for ($page = 1; $page <= 5; $page++) {
            $this->actingAs($this->user)->postJson(route('content.user.progress.update'), [
                'content_id'  => $this->docContent->id,
                'type'        => 'document',
                'last_page'   => $page,
                'total_pages' => 10,
            ]);
        }

        $cu = ContentUser::where('user_id', $this->user->id)->where('content_id', $this->docContent->id)->first();
        $this->assertEquals(5, $cu->last_page);
        $this->assertEquals(50, $cu->progress);
        $this->assertEquals(0, (int) $cu->actual_watch_seconds);
    }

    /**
     * Case 8: Duplicate heartbeat tidak double count (idempotency).
     */
    public function test_duplicate_heartbeat_id_is_idempotent(): void
    {
        $heartbeatId = (string) Str::uuid();

        $response1 = $this->actingAs($this->user)->postJson(route('content.user.progress.update'), [
            'content_id'    => $this->docContent->id,
            'type'          => 'document',
            'delta_seconds' => 10,
            'heartbeat_id'  => $heartbeatId,
        ]);
        $response1->assertJson(['actual_watch_seconds' => 10]);

        // Send identical heartbeatId
        $response2 = $this->actingAs($this->user)->postJson(route('content.user.progress.update'), [
            'content_id'    => $this->docContent->id,
            'type'          => 'document',
            'delta_seconds' => 10,
            'heartbeat_id'  => $heartbeatId,
        ]);
        $response2->assertJson(['actual_watch_seconds' => 10]);

        $cu = ContentUser::where('user_id', $this->user->id)->where('content_id', $this->docContent->id)->first();
        $this->assertEquals(10, $cu->actual_watch_seconds);
    }

    /**
     * Case 9: Rapid spoofing tidak dapat menambah waktu secara bebas.
     */
    public function test_rapid_spoofing_clamped_by_server_elapsed_time(): void
    {
        // First legitimate heartbeat
        $this->actingAs($this->user)->postJson(route('content.user.progress.update'), [
            'content_id'    => $this->docContent->id,
            'type'          => 'document',
            'delta_seconds' => 10,
            'heartbeat_id'  => (string) Str::uuid(),
        ]);

        // Immediate spoof heartbeat 50ms later claiming 30 seconds
        $response = $this->actingAs($this->user)->postJson(route('content.user.progress.update'), [
            'content_id'    => $this->docContent->id,
            'type'          => 'document',
            'delta_seconds' => 30,
            'heartbeat_id'  => (string) Str::uuid(),
        ]);

        // Clamped by server elapsed (~0s + 2s tolerance = max 2s)
        $cu = ContentUser::where('user_id', $this->user->id)->where('content_id', $this->docContent->id)->first();
        $this->assertLessThanOrEqual(13, $cu->actual_watch_seconds);
        $this->assertGreaterThanOrEqual(10, $cu->actual_watch_seconds);
    }

    /**
     * Case 10: Excessive delta di-clamp ke max 30s.
     */
    public function test_excessive_delta_clamped(): void
    {
        $response = $this->actingAs($this->user)->postJson(route('content.user.progress.update'), [
            'content_id'    => $this->docContent->id,
            'type'          => 'document',
            'delta_seconds' => 100, // Excessive
            'heartbeat_id'  => (string) Str::uuid(),
        ]);

        $response->assertOk();
        // Clamped to max 30s
        $response->assertJson(['actual_watch_seconds' => 30]);
    }

    /**
     * Case 11 & 12: Concurrent requests dan multiple tabs tidak menyebabkan race condition / lost update.
     */
    public function test_multiple_tabs_accumulate_safely(): void
    {
        // Tab A sends 10s
        $this->actingAs($this->user)->postJson(route('content.user.progress.update'), [
            'content_id'    => $this->docContent->id,
            'type'          => 'document',
            'delta_seconds' => 10,
            'heartbeat_id'  => (string) Str::uuid(),
        ]);

        // Tab B sends 10s after 10s
        $now = (float) (now()->getPreciseTimestamp(3) / 1000);
        Cache::put("document_last_hb_time:{$this->user->id}:{$this->docContent->id}", $now - 10, now()->addMinutes(30));

        $this->actingAs($this->user)->postJson(route('content.user.progress.update'), [
            'content_id'    => $this->docContent->id,
            'type'          => 'document',
            'delta_seconds' => 10,
            'heartbeat_id'  => (string) Str::uuid(),
        ]);

        $cu = ContentUser::where('user_id', $this->user->id)->where('content_id', $this->docContent->id)->first();
        $this->assertEquals(20, $cu->actual_watch_seconds);
    }

    /**
     * Case 13: Learning Hours dihitung dari actual_watch_seconds dengan 4 desimal.
     */
    public function test_learning_hours_calculated_from_actual_watch_seconds(): void
    {
        // Set actual_watch_seconds = 1800 -> 0.5000 hours
        $cu = ContentUser::create([
            'user_id'              => $this->user->id,
            'content_id'           => $this->docContent->id,
            'actual_watch_seconds' => 1800,
            'progress'             => 50,
            'last_page'            => 5,
            'total_pages'          => 10,
        ]);

        $this->assertEquals(0.5000, (float) $cu->learning_hours);

        // Update with 423 total seconds -> 423 / 3600 = 0.1175
        $cu->actual_watch_seconds = 423;
        $cu->save();

        $this->assertEquals(0.1175, (float) $cu->learning_hours);

        // Verify LearningRecordLearningHourService uses actual_watch_seconds
        $service = app(LearningRecordLearningHourService::class);
        $this->assertEquals(0.1175, $service->calculateContent($cu));
    }

    /**
     * Case 14: Standalone PDF hanya update content_users, TIDAK menyentuh playlist_content_users.
     */
    public function test_standalone_pdf_only_updates_content_users(): void
    {
        PlaylistContentUser::create([
            'user_id'              => $this->user->id,
            'trainingplan_id'      => $this->playlist->id,
            'content_id'           => $this->docContent->id,
            'progress'             => 0,
            'actual_watch_seconds' => 0,
        ]);

        $this->actingAs($this->user)->postJson(route('content.user.progress.update'), [
            'content_id'    => $this->docContent->id,
            'type'          => 'document',
            'delta_seconds' => 15,
            'heartbeat_id'  => (string) Str::uuid(),
        ]);

        $cu = ContentUser::where('user_id', $this->user->id)->where('content_id', $this->docContent->id)->first();
        $this->assertEquals(15, $cu->actual_watch_seconds);

        $pcu = PlaylistContentUser::where('user_id', $this->user->id)
            ->where('trainingplan_id', $this->playlist->id)
            ->where('content_id', $this->docContent->id)
            ->first();
        $this->assertEquals(0, $pcu->actual_watch_seconds);
    }

    /**
     * Case 15: Playlist PDF hanya update playlist_content_users, TIDAK menyentuh content_users.
     */
    public function test_playlist_pdf_only_updates_playlist_content_users(): void
    {
        ContentUser::create([
            'user_id'              => $this->user->id,
            'content_id'           => $this->docContent->id,
            'progress'             => 0,
            'actual_watch_seconds' => 0,
        ]);

        $response = $this->actingAs($this->user)->postJson(route('playlist.user.progress.update'), [
            'trainingplan_id' => $this->playlist->id,
            'content_id'      => $this->docContent->id,
            'type'            => 'document',
            'delta_seconds'   => 25,
            'heartbeat_id'    => (string) Str::uuid(),
            'status'          => 'in_progress',
        ]);

        $response->assertOk();
        $response->assertJson([
            'actual_watch_seconds' => 25,
        ]);

        $pcu = PlaylistContentUser::where('user_id', $this->user->id)
            ->where('trainingplan_id', $this->playlist->id)
            ->where('content_id', $this->docContent->id)
            ->first();
        $this->assertEquals(25, $pcu->actual_watch_seconds);
        $this->assertEquals(round(25 / 3600, 4), (float) $pcu->learning_hour);

        // Standalone record remains 0
        $cu = ContentUser::where('user_id', $this->user->id)->where('content_id', $this->docContent->id)->first();
        $this->assertEquals(0, $cu->actual_watch_seconds);
    }

    /**
     * Case 16 & Anti Double-Counting (Phase 17):
     * Memverifikasi bahwa page_elapsed_seconds pada timed document TIDAK pernah double-count dengan delta_seconds.
     */
    public function test_anti_double_counting_timed_document(): void
    {
        // 1. User is on page 1 of timed document.
        // DocumentReadingTracker sends heartbeats totalling 30 seconds.
        $this->actingAs($this->user)->postJson(route('content.user.progress.update'), [
            'content_id'    => $this->timedDocContent->id,
            'type'          => 'document',
            'delta_seconds' => 15,
            'heartbeat_id'  => (string) Str::uuid(),
        ]);

        $now = (float) (now()->getPreciseTimestamp(3) / 1000);
        Cache::put("document_last_hb_time:{$this->user->id}:{$this->timedDocContent->id}", $now - 15, now()->addMinutes(30));

        $this->actingAs($this->user)->postJson(route('content.user.progress.update'), [
            'content_id'    => $this->timedDocContent->id,
            'type'          => 'document',
            'delta_seconds' => 15,
            'heartbeat_id'  => (string) Str::uuid(),
        ]);

        $cu = ContentUser::where('user_id', $this->user->id)->where('content_id', $this->timedDocContent->id)->first();
        $this->assertEquals(30, $cu->actual_watch_seconds);

        // 2. Now, OwlPdfReader completes page 1 and sends page_elapsed_seconds = 30 without delta_seconds.
        // Fake session page timer so server validates page duration.
        session(['learning.document_page_timer.' . $this->timedDocContent->id => [
            'page'       => 1,
            'started_at' => microtime(true) - 31,
        ]]);

        $response = $this->actingAs($this->user)->postJson(route('content.user.progress.update'), [
            'content_id'           => $this->timedDocContent->id,
            'type'                 => 'document',
            'active_page'          => 1,
            'completed_page'       => 1,
            'page_elapsed_seconds' => 30,
            'total_pages'          => 10,
        ]);

        $response->assertOk();
        $response->assertJson([
            'page_completed' => true,
            'last_page'      => 1,
        ]);

        // CRITICAL CHECK: actual_watch_seconds must remain 30, NOT 60 (no double counting)!
        $cu->refresh();
        $this->assertEquals(30, $cu->actual_watch_seconds);
        $this->assertEquals(round(30 / 3600, 4), (float) $cu->learning_hours);
    }

    /**
     * Case 17: Content View renders document-reading-tracker.js and initializes DocumentReadingTracker for PDF.
     */
    public function test_content_view_renders_document_reading_tracker_for_pdf(): void
    {
        $response = $this->actingAs($this->user)->get(route('content.user.view', $this->docContent->slug));

        $response->assertOk();
        $response->assertSee('document-reading-tracker.js');
        $response->assertSee('DocumentReadingTracker');
        $response->assertSee('window.contentDocumentReadingTracker');
        $response->assertDontSee('video-watch-time.js');
    }

    /**
     * Case 18: Content View does NOT render document-reading-tracker.js for Video content.
     */
    public function test_content_view_does_not_render_document_reading_tracker_for_video(): void
    {
        $response = $this->actingAs($this->user)->get(route('content.user.view', $this->videoContent->slug));

        $response->assertOk();
        $response->assertSee('video-watch-time.js');
        $response->assertSee('VideoWatchTimeTracker');
        $response->assertDontSee('document-reading-tracker.js');
    }
}
