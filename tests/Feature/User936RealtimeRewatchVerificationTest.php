<?php

namespace Tests\Feature;

use App\Models\Content;
use App\Models\ContentUser;
use App\Models\PlaylistContentUser;
use App\Models\User;
use App\Services\Learning\LearningRecordRecognizedDurationService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Str;
use Tests\TestCase;

class User936RealtimeRewatchVerificationTest extends TestCase
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

        // Target content 1244 and user 936 from the user's screenshot
        $this->content = Content::find(1244) ?? Content::create([
            'id' => 1244,
            'title' => 'Dafa rizy',
            'slug' => 'dafa-rizy',
            'type' => 'Video',
            'duration' => '00:00:22',
            'duration_in_seconds' => 22,
            'active' => 'Active',
        ]);
        $this->content->update(['active' => 'Active', 'duration' => '00:00:22', 'duration_in_seconds' => 22]);

        $this->user = User::find(936) ?? User::factory()->create(['id' => 936]);

        $this->contentUser = ContentUser::firstOrCreate(
            ['user_id' => $this->user->id, 'content_id' => $this->content->id],
            [
                'actual_watch_seconds' => 22,
                'duration_seconds' => 22,
                'current_time' => 22,
                'progress' => 100,
                'finish' => '1',
                'learning_status' => 'completed',
                'learning_hours' => 0.0061,
            ]
        );

        // Reset to screenshot state: 22s actual watch (100% completed first watch), 22s duration
        $this->contentUser->update([
            'actual_watch_seconds' => 22,
            'duration_seconds' => 22,
            'current_time' => 22,
            'progress' => 100,
            'finish' => '1',
            'learning_status' => 'completed',
        ]);

        // Ensure no playlist actual watch initially for this test
        PlaylistContentUser::where('user_id', $this->user->id)
            ->where('content_id', $this->content->id)
            ->delete();
    }

    /**
     * Test the exact scenario from the screenshot and step-by-step rewatch progression.
     */
    public function test_step_by_step_rewatch_heartbeats_on_content_1244()
    {
        $this->actingAs($this->user);

        // 1. INITIAL STATE (Screenshot state)
        // Actual watch = 22s = 22s duration => Recognized Rewatch is 00:00:00
        $initialRecognized = $this->service->resolveGlobalContentRecognizedDuration($this->user->id, $this->content);
        $this->assertSame('00:00:00', $initialRecognized);

        $testClock = now();
        Carbon::setTestNow($testClock);

        // 2. REWATCH +8s (Total actual watch = 30s)
        // Rewatch quota consumed immediately = 30 - 22 = 8s
        $testClock = $testClock->addSeconds(10);
        Carbon::setTestNow($testClock);

        $response2 = $this->postJson(route('content.user.progress.update'), [
            'content_id' => $this->content->id,
            'type' => 'video',
            'current_time' => 8,
            'duration_seconds' => 22,
            'delta_seconds' => 8,
            'heartbeat_id' => (string) Str::uuid(),
        ]);

        $response2->assertOk();
        $response2->assertJson([
            'success' => true,
            'actual_watch_seconds' => 30,
            'total_recognized_seconds' => 8,
            'total_recognized_duration' => '00:00:08',
        ]);

        // 4. REWATCH +10s (Total actual watch = 40s)
        // Rewatch quota consumed = 40 - 22 = 18s
        $testClock = $testClock->addSeconds(15);
        Carbon::setTestNow($testClock);

        $response3 = $this->postJson(route('content.user.progress.update'), [
            'content_id' => $this->content->id,
            'type' => 'video',
            'current_time' => 18,
            'duration_seconds' => 22,
            'delta_seconds' => 10,
            'heartbeat_id' => (string) Str::uuid(),
        ]);

        $response3->assertOk();
        $response3->assertJson([
            'success' => true,
            'actual_watch_seconds' => 40,
            'total_recognized_seconds' => 18,
            'total_recognized_duration' => '00:00:18',
        ]);

        // 5. REWATCH +4s (Total actual watch = 44s, reaches 2x original duration cap)
        // Rewatch quota consumed = 44 - 22 = 22s (Full 1x duration)
        $testClock = $testClock->addSeconds(10);
        Carbon::setTestNow($testClock);

        $response4 = $this->postJson(route('content.user.progress.update'), [
            'content_id' => $this->content->id,
            'type' => 'video',
            'current_time' => 22,
            'duration_seconds' => 22,
            'delta_seconds' => 4,
            'heartbeat_id' => (string) Str::uuid(),
        ]);

        $response4->assertOk();
        $response4->assertJson([
            'success' => true,
            'actual_watch_seconds' => 44,
            'total_recognized_seconds' => 22,
            'total_recognized_duration' => '00:00:22',
            'is_capped' => true,
        ]);

        // 6. FURTHER WATCH +16s (Total actual watch = 60s)
        // Must stay strictly capped at 22s recognized duration
        $testClock = $testClock->addSeconds(20);
        Carbon::setTestNow($testClock);

        $response5 = $this->postJson(route('content.user.progress.update'), [
            'content_id' => $this->content->id,
            'type' => 'video',
            'current_time' => 16,
            'duration_seconds' => 22,
            'delta_seconds' => 16,
            'heartbeat_id' => (string) Str::uuid(),
        ]);

        $response5->assertOk();
        $response5->assertJson([
            'success' => true,
            'total_recognized_seconds' => 22,
            'total_recognized_duration' => '00:00:22',
            'is_capped' => true,
        ]);

        // 7. VERIFY UI BLADE RENDER AFTER REFRESH
        $viewResponse = $this->get(route('content.user.view', $this->content->slug));
        $viewResponse->assertOk();
        // Element #contentTotalRecognizedDuration contains 00:00:22
        $viewResponse->assertSee('<span id="contentTotalRecognizedDuration">00:00:22</span>', false);

        // 8. VERIFY MY LEARNING RECORD ONLINE ROWS & DETAIL
        $eligibilityService = app(\App\Services\CertificateEligibilityService::class);
        $recordController = app(\App\Http\Controllers\LearningRecordController::class);
        $rawRows = $recordController->getRawOnlineRows($this->user->id, $eligibilityService);
        $contentRow = $rawRows->first(fn ($r) => ($r['id'] ?? null) == $this->content->id || ($r['title'] ?? '') === $this->content->title);
        $this->assertNotNull($contentRow);
        $this->assertSame('00:00:22', $contentRow['total_recognized_duration']);
        $this->assertSame('00:00:22', $contentRow['recognized_duration']);
        $this->assertSame(22, $contentRow['recognized_duration_seconds']);

        // 9. VERIFY DETAIL MODAL AJAX RESPONSE
        $detailResponse = $this->get(route('user.learning.record.detail', ['source' => 'online', 'id' => $this->content->id]));
        $detailResponse->assertOk();
        $this->assertSame('00:00:22', $detailResponse->json('data.total_recognized_duration'));

        // 10. VERIFY EXCEL EXPORT
        $export = new \App\Exports\ExportMyLearningRecord([]);
        $export->array();
        $exportRows = $export->getRows();
        $exportRow = $exportRows->first(fn ($r) => ($r['id'] ?? null) == $this->content->id || ($r['title'] ?? '') === $this->content->title);
        $this->assertNotNull($exportRow);
        $this->assertSame('00:00:22', $exportRow['total_recognized_duration']);
        $this->assertSame(22, $exportRow['recognized_duration_seconds']);

        Carbon::setTestNow();
    }

    /**
     * Test playlist view renders recognized duration and playlist heartbeat updates dynamically.
     */
    public function test_playlist_view_renders_recognized_duration_and_heartbeat_updates()
    {
        $this->actingAs($this->user);

        // Create a test playlist and attach content 1244
        $playlist = \App\Models\TrainingPlan::create([
            'title' => 'Test Rewatch Playlist',
            'slug' => 'test-rewatch-playlist-' . Str::random(5),
            'active' => 'Active',
        ]);

        \App\Models\TrainingPlanDetail::create([
            'trainingplan_id' => $playlist->id,
            'content_id' => $this->content->id,
            'content_order' => 1,
        ]);

        // 1. Initial Playlist Content User
        $pcu = PlaylistContentUser::create([
            'user_id' => $this->user->id,
            'trainingplan_id' => $playlist->id,
            'content_id' => $this->content->id,
            'duration_seconds' => 22,
            'actual_watch_seconds' => 22,
            'progress' => 100,
            'finish' => '1',
            'learning_status' => 'completed',
        ]);

        // Direct content user is at 0 actual watch
        $this->contentUser->update([
            'actual_watch_seconds' => 0,
            'current_time' => 0,
            'progress' => 0,
            'finish' => '0',
            'learning_status' => 'not_started',
        ]);

        // Initial playlist view render
        $playlistViewResponse = $this->get(route('playlist.user.view', [$playlist->slug, $this->content->slug]));
        $playlistViewResponse->assertOk();
        $playlistViewResponse->assertSee('id="contentTotalRecognizedDuration"', false);
        $playlistViewResponse->assertSee('<span id="contentTotalRecognizedDuration">00:00:00</span>', false);

        // 2. Send Rewatch heartbeat via playlist progress endpoint
        $testClock = now();
        Carbon::setTestNow($testClock);

        $testClock = $testClock->addSeconds(12);
        Carbon::setTestNow($testClock);

        $response = $this->postJson(route('playlist.user.progress.update'), [
            'trainingplan_id' => $playlist->id,
            'content_id' => $this->content->id,
            'type' => 'video',
            'current_time' => 10,
            'duration_seconds' => 22,
            'delta_seconds' => 10,
            'heartbeat_id' => (string) Str::uuid(),
        ]);

        $response->assertOk();
        $response->assertJson([
            'success' => true,
            'actual_watch_seconds' => 32,
            'total_recognized_seconds' => 10,
            'total_recognized_duration' => '00:00:10',
        ]);

        // 3. Refresh playlist view
        $refreshedPlaylistView = $this->get(route('playlist.user.view', [$playlist->slug, $this->content->slug]));
        $refreshedPlaylistView->assertOk();
        $refreshedPlaylistView->assertSee('<span id="contentTotalRecognizedDuration">00:00:10</span>', false);

        Carbon::setTestNow();
    }
}
