<?php

namespace Tests\Feature;

use App\Models\Content;
use App\Models\ContentUser;
use App\Models\PlaylistContentUser;
use App\Models\TrainingPlan;
use App\Models\TrainingPlanDetail;
use App\Models\User;
use App\Services\Learning\CompletedPlaylistService;
use App\Services\Learning\OngoingPlaylistService;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class PlaylistEndToEndLifecycleTest extends TestCase
{
    use DatabaseTransactions;

    protected User $user;
    protected TrainingPlan $playlistA;
    protected TrainingPlan $playlistB;
    protected Content $sharedContent;
    protected Content $docContent;
    protected OngoingPlaylistService $ongoingService;
    protected CompletedPlaylistService $completedService;

    protected function setUp(): void
    {
        parent::setUp();

        $this->ongoingService = app(OngoingPlaylistService::class);
        $this->completedService = app(CompletedPlaylistService::class);

        $this->user = User::factory()->create([
            'team'       => 'Engineering',
            'department' => 'Technology',
            'division'   => 'LMS',
            'ssohash'    => 'test-hash-e2e-' . \Illuminate\Support\Str::uuid(),
            'sap_code'   => 'SAP-E2E-' . rand(1000, 9999),
            'role'       => 1,
            'active'     => 1,
        ]);

        $this->playlistA = TrainingPlan::create([
            'title'                  => 'E2E Playlist A',
            'slug'                   => 'e2e-playlist-a-' . rand(1000, 9999),
            'active'                 => 'Active',
            'total_duration_seconds' => 7200,
            'learning_hours'         => 2.0,
            'has_deadline'           => 0,
        ]);

        $this->playlistB = TrainingPlan::create([
            'title'                  => 'E2E Playlist B',
            'slug'                   => 'e2e-playlist-b-' . rand(1000, 9999),
            'active'                 => 'Active',
            'total_duration_seconds' => 3600,
            'learning_hours'         => 1.0,
            'has_deadline'           => 0,
        ]);

        $this->sharedContent = Content::create([
            'title'        => 'Shared Video Content',
            'slug'         => 'shared-video-' . rand(1000, 9999),
            'type'         => 'Video',
            'duration'     => '01:00:00',
            'active'       => 'Active',
            'playlistonly' => 'N',
            'has_deadline' => 0,
        ]);

        $this->docContent = Content::create([
            'title'         => 'PDF Document Content',
            'slug'          => 'pdf-doc-' . rand(1000, 9999),
            'type'          => 'Document',
            'duration'      => '00:10:00',
            'page_duration' => 0,
            'total_page'    => 10,
            'active'        => 'Active',
            'playlistonly'  => 'N',
            'has_deadline'  => 0,
        ]);

        // Playlist A: 2 contents (sharedContent + docContent)
        TrainingPlanDetail::create([
            'trainingplan_id' => $this->playlistA->id,
            'content_id'      => $this->sharedContent->id,
            'content_order'   => 0,
        ]);
        TrainingPlanDetail::create([
            'trainingplan_id' => $this->playlistA->id,
            'content_id'      => $this->docContent->id,
            'content_order'   => 1,
        ]);

        // Playlist B: 1 content (sharedContent)
        TrainingPlanDetail::create([
            'trainingplan_id' => $this->playlistB->id,
            'content_id'      => $this->sharedContent->id,
            'content_order'   => 0,
        ]);
    }

    /**
     * E2E Scenario 1 — Direct Content Lifecycle vs Playlist Isolation
     */
    public function test_direct_content_lifecycle_maintains_total_isolation(): void
    {
        // 1. User views Direct Content
        $viewResp = $this->actingAs($this->user)->get(route('content.user.view', ['slug' => $this->sharedContent->slug]));
        $viewResp->assertStatus(200);

        // 2. User plays Direct Content to 50%
        $progResp1 = $this->actingAs($this->user)->postJson(route('content.user.progress.update'), [
            'content_id'       => $this->sharedContent->id,
            'type'             => 'video',
            'current_time'     => 1800,
            'duration_seconds' => 3600,
            'status'           => 'in_progress',
        ]);
        $progResp1->assertStatus(200);
        $progResp1->assertJson(['success' => true, 'progress' => 50]);

        // 3. User finishes Direct Content to 100%
        $progResp2 = $this->actingAs($this->user)->postJson(route('content.user.progress.update'), [
            'content_id'       => $this->sharedContent->id,
            'type'             => 'video',
            'current_time'     => 3600,
            'duration_seconds' => 3600,
            'status'           => 'completed',
        ]);
        $progResp2->assertStatus(200);
        $progResp2->assertJson(['success' => true, 'progress' => 100, 'finish' => 1]);

        // Verify Direct Content database state
        $this->assertDatabaseHas('content_users', [
            'user_id'    => $this->user->id,
            'content_id' => $this->sharedContent->id,
            'progress'   => 100,
            'finish'     => '1',
        ]);

        // Verify Playlist database state is completely untouched
        $this->assertDatabaseMissing('playlist_content_users', [
            'user_id' => $this->user->id,
        ]);

        // Verify Playlist A & B are NOT marked ongoing or completed
        $ongoing = $this->ongoingService->userPlaylists($this->user->id);
        $this->assertFalse($ongoing->contains('id', $this->playlistA->id));
        $this->assertFalse($ongoing->contains('id', $this->playlistB->id));

        $completed = $this->completedService->forUser($this->user->id);
        $this->assertFalse($completed->contains('id', $this->playlistA->id));
        $this->assertFalse($completed->contains('id', $this->playlistB->id));
    }

    /**
     * E2E Scenario 2 — Full Playlist Learning Lifecycle (Video + Document to Completion)
     */
    public function test_playlist_learning_lifecycle_through_all_stages(): void
    {
        // Stage 1: User opens Playlist A Video Lesson
        $viewResp = $this->actingAs($this->user)->get(route('playlist.user.view', [
            'slug'    => $this->playlistA->slug,
            'vidslug' => $this->sharedContent->slug,
        ]));
        $viewResp->assertStatus(200);
        $this->assertDatabaseHas('playlist_content_users', [
            'user_id'         => $this->user->id,
            'trainingplan_id' => $this->playlistA->id,
            'content_id'      => $this->sharedContent->id,
            'progress'        => 0,
            'finish'          => '0',
        ]);

        // Stage 2: User watches 30 minutes (50%) of Video Lesson
        $p1 = $this->actingAs($this->user)->postJson(route('playlist.user.progress.update'), [
            'trainingplan_id'  => $this->playlistA->id,
            'content_id'       => $this->sharedContent->id,
            'type'             => 'video',
            'current_time'     => 1800,
            'duration_seconds' => 3600,
            'status'           => 'in_progress',
        ]);
        $p1->assertStatus(200);
        $p1->assertJson(['success' => true, 'progress' => 50]);

        // Check catalog card stats: 0 of 2 completed -> 0%
        $catalogResp1 = $this->actingAs($this->user)->get(route('playlist.user.index'));
        $catalogResp1->assertStatus(200);
        $catalogResp1->assertViewHas('playlistStats', fn ($s) => $s[$this->playlistA->id]['progress'] === 0);

        // Stage 3: User finishes Video Lesson (100%)
        $p2 = $this->actingAs($this->user)->postJson(route('playlist.user.progress.update'), [
            'trainingplan_id'  => $this->playlistA->id,
            'content_id'       => $this->sharedContent->id,
            'type'             => 'video',
            'current_time'     => 3600,
            'duration_seconds' => 3600,
            'status'           => 'completed',
        ]);
        $p2->assertStatus(200);
        $p2->assertJson(['success' => true, 'progress' => 100, 'finish' => 1]);

        // Check catalog card stats: 1 of 2 completed -> 50%
        $catalogResp2 = $this->actingAs($this->user)->get(route('playlist.user.index'));
        $catalogResp2->assertStatus(200);
        $catalogResp2->assertViewHas('playlistStats', fn ($s) => $s[$this->playlistA->id]['progress'] === 50);

        // Check ongoing service: 1 of 2 completed, not completed
        $ongoing = $this->ongoingService->userPlaylists($this->user->id);
        $planOngoing = $ongoing->firstWhere('id', $this->playlistA->id);
        $this->assertNotNull($planOngoing);
        $this->assertSame(50, (int) $planOngoing->progress_percent);
        $this->assertFalse($planOngoing->is_completed);

        // Stage 4: User opens Document Lesson and reads all 10 pages
        $p3 = $this->actingAs($this->user)->postJson(route('playlist.user.progress.update'), [
            'trainingplan_id' => $this->playlistA->id,
            'content_id'      => $this->docContent->id,
            'type'            => 'document',
            'last_page'       => 10,
            'total_pages'     => 10,
            'status'          => 'in_progress',
        ]);
        $p3->assertStatus(200);
        $p3->assertJson(['success' => true, 'progress' => 100, 'finish' => 1]);

        // Check catalog card stats: 2 of 2 completed -> 100%
        $catalogResp3 = $this->actingAs($this->user)->get(route('playlist.user.index'));
        $catalogResp3->assertStatus(200);
        $catalogResp3->assertViewHas('playlistStats', fn ($s) => $s[$this->playlistA->id]['progress'] === 100);

        // Check completed service: Playlist A is in completed list
        $completed = $this->completedService->forUser($this->user->id);
        $this->assertTrue($completed->contains('id', $this->playlistA->id));
    }

    /**
     * E2E Scenario 3 — Multi-Playlist Isolation on Shared Content
     */
    public function test_shared_content_progress_isolated_between_playlists(): void
    {
        // User completes Shared Content inside Playlist B (1 of 1 -> 100%)
        $respB = $this->actingAs($this->user)->postJson(route('playlist.user.progress.update'), [
            'trainingplan_id'  => $this->playlistB->id,
            'content_id'       => $this->sharedContent->id,
            'type'             => 'video',
            'current_time'     => 3600,
            'duration_seconds' => 3600,
            'status'           => 'completed',
        ]);
        $respB->assertStatus(200);

        // Playlist B must be completed
        $completed = $this->completedService->forUser($this->user->id);
        $this->assertTrue($completed->contains('id', $this->playlistB->id));

        // Playlist A must NOT be completed (0 of 2)
        $this->assertFalse($completed->contains('id', $this->playlistA->id));

        // Direct Content must NOT have records
        $this->assertDatabaseMissing('content_users', [
            'user_id'    => $this->user->id,
            'content_id' => $this->sharedContent->id,
        ]);
    }
}
