<?php

namespace Tests\Feature;

use App\Models\Content;
use App\Models\ContentUser;
use App\Models\PlaylistContentUser;
use App\Models\TrainingPlan;
use App\Models\TrainingPlanDetail;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class PlaylistProgressControllerTest extends TestCase
{
    use DatabaseTransactions;

    protected User $user;
    protected TrainingPlan $playlistA;
    protected TrainingPlan $playlistB;
    protected Content $contentVideo;
    protected Content $contentDoc;
    protected Content $unrelatedContent;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create([
            'team'       => 'Engineering',
            'department' => 'Technology',
            'division'   => 'LMS',
            'ssohash'    => 'test-hash-' . \Illuminate\Support\Str::uuid(),
            'sap_code'   => 'SAP-TEST-' . rand(1000, 9999),
            'role'       => 1,
            'active'     => 1,
        ]);

        $this->playlistA = TrainingPlan::create([
            'title'                  => 'Playlist A Test',
            'active'                 => 'Active',
            'total_duration_seconds' => 3600,
            'learning_hours'         => 1.0,
            'has_deadline'           => 0,
        ]);

        $this->playlistB = TrainingPlan::create([
            'title'                  => 'Playlist B Test',
            'active'                 => 'Active',
            'total_duration_seconds' => 3600,
            'learning_hours'         => 1.0,
            'has_deadline'           => 0,
        ]);

        $this->contentVideo = Content::create([
            'title'        => 'Content Video Test',
            'type'         => 'Video',
            'duration'     => '00:10:00',
            'active'       => 'Active',
            'playlistonly' => 'N',
            'has_deadline' => 0,
        ]);

        $this->contentDoc = Content::create([
            'title'         => 'Content Document Test',
            'type'          => 'Document',
            'duration'      => '00:05:00',
            'page_duration' => 0, // Scroll mode
            'total_page'    => 10,
            'active'        => 'Active',
            'playlistonly'  => 'N',
            'has_deadline'  => 0,
        ]);

        $this->unrelatedContent = Content::create([
            'title'        => 'Unrelated Content Test',
            'type'         => 'Video',
            'duration'     => '00:10:00',
            'active'       => 'Active',
            'playlistonly' => 'N',
            'has_deadline' => 0,
        ]);

        // Attach contentVideo and contentDoc to Playlist A
        TrainingPlanDetail::create([
            'trainingplan_id' => $this->playlistA->id,
            'content_id'      => $this->contentVideo->id,
            'content_order'   => 0,
        ]);

        TrainingPlanDetail::create([
            'trainingplan_id' => $this->playlistA->id,
            'content_id'      => $this->contentDoc->id,
            'content_order'   => 1,
        ]);

        // Attach contentVideo to Playlist B as well (multi-playlist content)
        TrainingPlanDetail::create([
            'trainingplan_id' => $this->playlistB->id,
            'content_id'      => $this->contentVideo->id,
            'content_order'   => 0,
        ]);
    }

    /**
     * Test 1 — Authenticated Playlist Progress
     */
    public function test_authenticated_user_can_update_playlist_progress(): void
    {
        $response = $this->actingAs($this->user)->postJson(route('playlist.user.progress.update'), [
            'trainingplan_id'  => $this->playlistA->id,
            'content_id'       => $this->contentVideo->id,
            'type'             => 'video',
            'current_time'     => 120,
            'duration_seconds' => 600,
            'status'           => 'in_progress',
        ]);

        $response->assertStatus(200);
        $response->assertJson([
            'success'  => true,
            'progress' => 20,
            'status'   => 'in_progress',
            'finish'   => 0,
        ]);

        $this->assertDatabaseHas('playlist_content_users', [
            'user_id'         => $this->user->id,
            'trainingplan_id' => $this->playlistA->id,
            'content_id'      => $this->contentVideo->id,
            'progress'        => 20,
            'finish'          => '0',
            'learning_status' => 'in_progress',
        ]);
    }

    /**
     * Test 2 — Progress Update (20% -> 50%)
     */
    public function test_progress_advances_on_consecutive_updates(): void
    {
        $this->actingAs($this->user)->postJson(route('playlist.user.progress.update'), [
            'trainingplan_id'  => $this->playlistA->id,
            'content_id'       => $this->contentVideo->id,
            'type'             => 'video',
            'current_time'     => 120,
            'duration_seconds' => 600,
        ]);

        $response = $this->actingAs($this->user)->postJson(route('playlist.user.progress.update'), [
            'trainingplan_id'  => $this->playlistA->id,
            'content_id'       => $this->contentVideo->id,
            'type'             => 'video',
            'current_time'     => 300,
            'duration_seconds' => 600,
        ]);

        $response->assertStatus(200);
        $response->assertJson([
            'success'  => true,
            'progress' => 50,
        ]);

        $pcu = PlaylistContentUser::where('user_id', $this->user->id)
            ->where('trainingplan_id', $this->playlistA->id)
            ->where('content_id', $this->contentVideo->id)
            ->first();

        $this->assertNotNull($pcu);
        $this->assertSame(50, $pcu->progress);
    }

    /**
     * Test 3 — Monotonic Protection (70% does not decrease on rewind to 30%)
     */
    public function test_monotonic_protection_prevents_progress_decrease(): void
    {
        // First request: advance to 70% (420s / 600s)
        $this->actingAs($this->user)->postJson(route('playlist.user.progress.update'), [
            'trainingplan_id'  => $this->playlistA->id,
            'content_id'       => $this->contentVideo->id,
            'type'             => 'video',
            'current_time'     => 420,
            'duration_seconds' => 600,
        ]);

        // Second request: rewind to 180s (30%)
        $response = $this->actingAs($this->user)->postJson(route('playlist.user.progress.update'), [
            'trainingplan_id'  => $this->playlistA->id,
            'content_id'       => $this->contentVideo->id,
            'type'             => 'video',
            'current_time'     => 180,
            'duration_seconds' => 600,
        ]);

        $response->assertStatus(200);
        $response->assertJson([
            'success'      => true,
            'progress'     => 70, // Retained highest
            'current_time' => 180, // Saved latest playback position
        ]);

        $pcu = PlaylistContentUser::where('user_id', $this->user->id)
            ->where('trainingplan_id', $this->playlistA->id)
            ->where('content_id', $this->contentVideo->id)
            ->first();

        $this->assertSame(70, $pcu->progress);
        $this->assertSame(180, $pcu->current_time);
    }

    /**
     * Test 4 — Invalid Playlist Content (Content not in playlist rejected with 422)
     */
    public function test_content_not_in_playlist_is_rejected(): void
    {
        $response = $this->actingAs($this->user)->postJson(route('playlist.user.progress.update'), [
            'trainingplan_id'  => $this->playlistA->id,
            'content_id'       => $this->unrelatedContent->id,
            'type'             => 'video',
            'current_time'     => 100,
            'duration_seconds' => 600,
        ]);

        $response->assertStatus(422);

        $this->assertDatabaseMissing('playlist_content_users', [
            'user_id'         => $this->user->id,
            'trainingplan_id' => $this->playlistA->id,
            'content_id'      => $this->unrelatedContent->id,
        ]);
    }

    /**
     * Test 5 — Unauthenticated Request Rejected
     */
    public function test_unauthenticated_request_is_rejected(): void
    {
        $response = $this->postJson(route('playlist.user.progress.update'), [
            'trainingplan_id'  => $this->playlistA->id,
            'content_id'       => $this->contentVideo->id,
            'type'             => 'video',
            'current_time'     => 100,
            'duration_seconds' => 600,
        ]);

        $response->assertStatus(401);
    }

    /**
     * Test 6 — Direct Content Isolation (content_users is untouched)
     */
    public function test_direct_content_progress_is_isolated_and_untouched(): void
    {
        // Seed direct content progress at 80%
        $directRecord = ContentUser::create([
            'user_id'         => $this->user->id,
            'content_id'      => $this->contentVideo->id,
            'progress'        => 80,
            'finish'          => '0',
            'learning_status' => 'in_progress',
            'current_time'    => 480,
        ]);

        // Send playlist progress at 25% (150s / 600s)
        $response = $this->actingAs($this->user)->postJson(route('playlist.user.progress.update'), [
            'trainingplan_id'  => $this->playlistA->id,
            'content_id'       => $this->contentVideo->id,
            'type'             => 'video',
            'current_time'     => 150,
            'duration_seconds' => 600,
        ]);

        $response->assertStatus(200);

        // Verify content_users still has 80%
        $directRecord->refresh();
        $this->assertSame(80, $directRecord->progress);
        $this->assertSame(480, $directRecord->current_time);

        // Verify playlist_content_users has 25%
        $playlistRecord = PlaylistContentUser::where('user_id', $this->user->id)
            ->where('trainingplan_id', $this->playlistA->id)
            ->where('content_id', $this->contentVideo->id)
            ->first();

        $this->assertNotNull($playlistRecord);
        $this->assertSame(25, $playlistRecord->progress);
        $this->assertSame(150, $playlistRecord->current_time);
    }

    /**
     * Test 7 — Multiple Playlists (Independent Progress in Playlist A vs Playlist B)
     */
    public function test_same_content_has_independent_progress_across_multiple_playlists(): void
    {
        // Update Playlist A progress to 20%
        $this->actingAs($this->user)->postJson(route('playlist.user.progress.update'), [
            'trainingplan_id'  => $this->playlistA->id,
            'content_id'       => $this->contentVideo->id,
            'type'             => 'video',
            'current_time'     => 120,
            'duration_seconds' => 600,
        ]);

        // Update Playlist B progress to 60%
        $this->actingAs($this->user)->postJson(route('playlist.user.progress.update'), [
            'trainingplan_id'  => $this->playlistB->id,
            'content_id'       => $this->contentVideo->id,
            'type'             => 'video',
            'current_time'     => 360,
            'duration_seconds' => 600,
        ]);

        $recordA = PlaylistContentUser::where('user_id', $this->user->id)
            ->where('trainingplan_id', $this->playlistA->id)
            ->where('content_id', $this->contentVideo->id)
            ->first();

        $recordB = PlaylistContentUser::where('user_id', $this->user->id)
            ->where('trainingplan_id', $this->playlistB->id)
            ->where('content_id', $this->contentVideo->id)
            ->first();

        $this->assertNotNull($recordA);
        $this->assertNotNull($recordB);
        $this->assertSame(20, $recordA->progress);
        $this->assertSame(60, $recordB->progress);
    }

    /**
     * Test 8 — Completion marks finish = '1' and learning_status = 'completed'
     */
    public function test_completion_sets_finish_one_and_learning_status_completed(): void
    {
        $response = $this->actingAs($this->user)->postJson(route('playlist.user.progress.update'), [
            'trainingplan_id'  => $this->playlistA->id,
            'content_id'       => $this->contentVideo->id,
            'type'             => 'video',
            'current_time'     => 600,
            'duration_seconds' => 600,
            'status'           => 'completed',
        ]);

        $response->assertStatus(200);
        $response->assertJson([
            'success'  => true,
            'progress' => 100,
            'finish'   => 1,
            'status'   => 'completed',
        ]);

        $pcu = PlaylistContentUser::where('user_id', $this->user->id)
            ->where('trainingplan_id', $this->playlistA->id)
            ->where('content_id', $this->contentVideo->id)
            ->first();

        $this->assertNotNull($pcu);
        $this->assertSame(100, $pcu->progress);
        $this->assertSame('1', (string) $pcu->finish);
        $this->assertSame('completed', $pcu->learning_status);
    }

    /**
     * Test 9 — Playlist Document Timed Pagination: Page Duration 3s Lifecycle (22 -> 23)
     */
    public function test_playlist_document_timed_pagination_enforces_timer_and_advances_page(): void
    {
        $doc = Content::create([
            'title'         => 'Playlist Timed Doc 3s',
            'type'          => 'Document',
            'duration'      => '00:01:30',
            'page_duration' => 3,
            'total_page'    => 30,
            'active'        => 'Active',
            'playlistonly'  => 'Y',
            'has_deadline'  => 0,
        ]);

        TrainingPlanDetail::create([
            'trainingplan_id' => $this->playlistA->id,
            'content_id'      => $doc->id,
            'content_order'   => 2,
        ]);

        // Seed user at last_page = 21 (page 22 is allowed next)
        PlaylistContentUser::create([
            'user_id'         => $this->user->id,
            'trainingplan_id' => $this->playlistA->id,
            'content_id'      => $doc->id,
            'last_page'       => 21,
            'total_pages'     => 30,
            'progress'        => 70,
            'finish'          => '0',
            'learning_status' => 'in_progress',
        ]);

        // 1. Activate page 22 (starts timer)
        $this->actingAs($this->user)->postJson(route('playlist.user.progress.update'), [
            'trainingplan_id' => $this->playlistA->id,
            'content_id'      => $doc->id,
            'type'            => 'document',
            'active_page'     => 22,
            'total_pages'     => 30,
            'status'          => 'in_progress',
        ]);

        // 2. Insufficient time on page 22 (1s elapsed on 3s page) -> page_completed = false
        $failResponse = $this->actingAs($this->user)->postJson(route('playlist.user.progress.update'), [
            'trainingplan_id'      => $this->playlistA->id,
            'content_id'           => $doc->id,
            'type'                 => 'document',
            'active_page'          => 22,
            'completed_page'       => 22,
            'page_elapsed_seconds' => 1,
            'total_pages'          => 30,
            'status'               => 'in_progress',
        ]);

        $failResponse->assertOk();
        $failResponse->assertJson([
            'success'        => true,
            'page_completed' => false,
        ]);

        // 3. Complete page 22 with session timer (3s elapsed)
        $timerKey = 'learning.playlist_doc_page_timer.' . $this->playlistA->id . '.' . $doc->id;
        $successResponse = $this->actingAs($this->user)->withSession([
            $timerKey => [
                'page'       => 22,
                'started_at' => microtime(true) - 3.5,
            ],
        ])->postJson(route('playlist.user.progress.update'), [
            'trainingplan_id'      => $this->playlistA->id,
            'content_id'           => $doc->id,
            'type'                 => 'document',
            'active_page'          => 22,
            'completed_page'       => 22,
            'page_elapsed_seconds' => 3,
            'total_pages'          => 30,
            'status'               => 'in_progress',
        ]);

        $successResponse->assertOk();
        $successResponse->assertJson([
            'success'        => true,
            'page_completed' => true,
            'last_page'      => 22,
            'allowed_page'   => 23,
        ]);

        // 4. Move to page 23 and complete it
        $this->actingAs($this->user)->postJson(route('playlist.user.progress.update'), [
            'trainingplan_id' => $this->playlistA->id,
            'content_id'      => $doc->id,
            'type'            => 'document',
            'active_page'     => 23,
            'total_pages'     => 30,
            'status'          => 'in_progress',
        ]);

        $page23Response = $this->actingAs($this->user)->withSession([
            $timerKey => [
                'page'       => 23,
                'started_at' => microtime(true) - 3.5,
            ],
        ])->postJson(route('playlist.user.progress.update'), [
            'trainingplan_id'      => $this->playlistA->id,
            'content_id'           => $doc->id,
            'type'                 => 'document',
            'active_page'          => 23,
            'completed_page'       => 23,
            'page_elapsed_seconds' => 3,
            'total_pages'          => 30,
            'status'               => 'in_progress',
        ]);

        $page23Response->assertOk();
        $page23Response->assertJson([
            'success'        => true,
            'page_completed' => true,
            'last_page'      => 23,
            'allowed_page'   => 24,
        ]);
    }

    /**
     * Test 10 — Playlist Document Fallback: Derives completedPages from previous progress when last_page is null
     */
    public function test_playlist_document_derives_completed_pages_from_previous_progress(): void
    {
        $doc = Content::create([
            'title'         => 'Playlist Timed Doc Progress Fallback',
            'type'          => 'Document',
            'duration'      => '00:01:30',
            'page_duration' => 3,
            'total_page'    => 30,
            'active'        => 'Active',
            'playlistonly'  => 'Y',
            'has_deadline'  => 0,
        ]);

        TrainingPlanDetail::create([
            'trainingplan_id' => $this->playlistA->id,
            'content_id'      => $doc->id,
            'content_order'   => 3,
        ]);

        // User record has progress = 70% but last_page is null/0
        PlaylistContentUser::create([
            'user_id'         => $this->user->id,
            'trainingplan_id' => $this->playlistA->id,
            'content_id'      => $doc->id,
            'last_page'       => null,
            'total_pages'     => 30,
            'progress'        => 70, // (70 * 30)/100 = 21 completed pages
            'finish'          => '0',
            'learning_status' => 'in_progress',
        ]);

        $timerKey = 'learning.playlist_doc_page_timer.' . $this->playlistA->id . '.' . $doc->id;

        // User attempts to complete page 22 (21 + 1)
        $response = $this->actingAs($this->user)->withSession([
            $timerKey => [
                'page'       => 22,
                'started_at' => microtime(true) - 3.5,
            ],
        ])->postJson(route('playlist.user.progress.update'), [
            'trainingplan_id'      => $this->playlistA->id,
            'content_id'           => $doc->id,
            'type'                 => 'document',
            'active_page'          => 22,
            'completed_page'       => 22,
            'page_elapsed_seconds' => 3,
            'total_pages'          => 30,
            'status'               => 'in_progress',
        ]);

        $response->assertOk();
        $response->assertJson([
            'success'        => true,
            'page_completed' => true,
            'last_page'      => 22,
            'allowed_page'   => 23,
        ]);
    }

    /**
     * Test 11 — Playlist Document: Durations 1s and 2s
     */
    public function test_playlist_document_timed_pagination_durations_1s_and_2s(): void
    {
        $doc1s = Content::create([
            'title'         => 'Playlist Timed Doc 1s',
            'type'          => 'Document',
            'duration'      => '00:00:30',
            'page_duration' => 1,
            'total_page'    => 30,
            'active'        => 'Active',
            'playlistonly'  => 'Y',
            'has_deadline'  => 0,
        ]);

        TrainingPlanDetail::create([
            'trainingplan_id' => $this->playlistA->id,
            'content_id'      => $doc1s->id,
            'content_order'   => 4,
        ]);

        $timerKey = 'learning.playlist_doc_page_timer.' . $this->playlistA->id . '.' . $doc1s->id;

        $response = $this->actingAs($this->user)->withSession([
            $timerKey => [
                'page'       => 1,
                'started_at' => microtime(true) - 1.5,
            ],
        ])->postJson(route('playlist.user.progress.update'), [
            'trainingplan_id'      => $this->playlistA->id,
            'content_id'           => $doc1s->id,
            'type'                 => 'document',
            'active_page'          => 1,
            'completed_page'       => 1,
            'page_elapsed_seconds' => 1,
            'total_pages'          => 30,
            'status'               => 'in_progress',
        ]);

        $response->assertOk();
        $response->assertJson([
            'success'        => true,
            'page_completed' => true,
            'last_page'      => 1,
            'allowed_page'   => 2,
        ]);
    }

    /**
     * Test 12 — Playlist Document Last Page: Reaching page 30/30 marks completed
     */
    public function test_playlist_document_timed_pagination_last_page_completes_content(): void
    {
        $doc = Content::create([
            'title'         => 'Playlist Timed Doc Last Page',
            'type'          => 'Document',
            'duration'      => '00:01:30',
            'page_duration' => 3,
            'total_page'    => 30,
            'active'        => 'Active',
            'playlistonly'  => 'Y',
            'has_deadline'  => 0,
        ]);

        TrainingPlanDetail::create([
            'trainingplan_id' => $this->playlistA->id,
            'content_id'      => $doc->id,
            'content_order'   => 5,
        ]);

        PlaylistContentUser::create([
            'user_id'         => $this->user->id,
            'trainingplan_id' => $this->playlistA->id,
            'content_id'      => $doc->id,
            'last_page'       => 29,
            'total_pages'     => 30,
            'progress'        => 96,
            'finish'          => '0',
            'learning_status' => 'in_progress',
        ]);

        $timerKey = 'learning.playlist_doc_page_timer.' . $this->playlistA->id . '.' . $doc->id;

        $response = $this->actingAs($this->user)->withSession([
            $timerKey => [
                'page'       => 30,
                'started_at' => microtime(true) - 3.5,
            ],
        ])->postJson(route('playlist.user.progress.update'), [
            'trainingplan_id'      => $this->playlistA->id,
            'content_id'           => $doc->id,
            'type'                 => 'document',
            'active_page'          => 30,
            'completed_page'       => 30,
            'page_elapsed_seconds' => 3,
            'total_pages'          => 30,
            'status'               => 'in_progress',
        ]);

        $response->assertOk();
        $response->assertJson([
            'success'        => true,
            'page_completed' => true,
            'last_page'      => 30,
            'progress'       => 100,
            'finish'         => 1,
            'allowed_page'   => 30,
        ]);
    }
}
