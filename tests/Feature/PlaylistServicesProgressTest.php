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

class PlaylistServicesProgressTest extends TestCase
{
    use DatabaseTransactions;

    protected User $userA;
    protected User $userB;
    protected TrainingPlan $playlistA;
    protected TrainingPlan $playlistB;
    protected Content $content1;
    protected Content $content2;
    protected OngoingPlaylistService $ongoingService;
    protected CompletedPlaylistService $completedService;

    protected function setUp(): void
    {
        parent::setUp();

        $this->ongoingService = app(OngoingPlaylistService::class);
        $this->completedService = app(CompletedPlaylistService::class);

        $this->userA = User::factory()->create([
            'team'       => 'Engineering',
            'department' => 'Technology',
            'division'   => 'LMS',
            'ssohash'    => 'test-hash-a-' . \Illuminate\Support\Str::uuid(),
            'sap_code'   => 'SAP-A-' . rand(1000, 9999),
            'role'       => 1,
            'active'     => 1,
        ]);

        $this->userB = User::factory()->create([
            'team'       => 'Engineering',
            'department' => 'Technology',
            'division'   => 'LMS',
            'ssohash'    => 'test-hash-b-' . \Illuminate\Support\Str::uuid(),
            'sap_code'   => 'SAP-B-' . rand(1000, 9999),
            'role'       => 1,
            'active'     => 1,
        ]);

        $this->playlistA = TrainingPlan::create([
            'title'                  => 'Playlist A Test',
            'slug'                   => 'playlist-a-test-' . rand(1000, 9999),
            'active'                 => 'Active',
            'total_duration_seconds' => 7200,
            'learning_hours'         => 2.0,
            'has_deadline'           => 0,
        ]);

        $this->playlistB = TrainingPlan::create([
            'title'                  => 'Playlist B Test',
            'slug'                   => 'playlist-b-test-' . rand(1000, 9999),
            'active'                 => 'Active',
            'total_duration_seconds' => 3600,
            'learning_hours'         => 1.0,
            'has_deadline'           => 0,
        ]);

        $this->content1 = Content::create([
            'title'        => 'Content 1 Test',
            'slug'         => 'content-1-test-' . rand(1000, 9999),
            'type'         => 'Video',
            'duration'     => '01:00:00',
            'active'       => 'Active',
            'playlistonly' => 'N',
            'has_deadline' => 0,
        ]);

        $this->content2 = Content::create([
            'title'        => 'Content 2 Test',
            'slug'         => 'content-2-test-' . rand(1000, 9999),
            'type'         => 'Video',
            'duration'     => '01:00:00',
            'active'       => 'Active',
            'playlistonly' => 'N',
            'has_deadline' => 0,
        ]);

        // Playlist A has content1 and content2 (2 items)
        TrainingPlanDetail::create([
            'trainingplan_id' => $this->playlistA->id,
            'content_id'      => $this->content1->id,
            'content_order'   => 0,
        ]);
        TrainingPlanDetail::create([
            'trainingplan_id' => $this->playlistA->id,
            'content_id'      => $this->content2->id,
            'content_order'   => 1,
        ]);

        // Playlist B has content1 (1 item)
        TrainingPlanDetail::create([
            'trainingplan_id' => $this->playlistB->id,
            'content_id'      => $this->content1->id,
            'content_order'   => 0,
        ]);
    }

    /* =========================================================================
       ONGOING PLAYLIST SERVICE TESTS
    ========================================================================= */

    /**
     * Test Ongoing 1: Playlist with no PlaylistContentUser progress is not returned
     */
    public function test_ongoing_service_ignores_untouched_playlists(): void
    {
        $ongoing = $this->ongoingService->userPlaylists($this->userA->id);
        $this->assertFalse($ongoing->contains('id', $this->playlistA->id));
        $this->assertFalse($ongoing->contains('id', $this->playlistB->id));
    }

    /**
     * Test Ongoing 2: Partial playlist progress is correctly classified as ongoing (50%)
     */
    public function test_ongoing_service_returns_partially_completed_playlist(): void
    {
        // User A completes 1 of 2 in Playlist A
        PlaylistContentUser::create([
            'user_id'         => $this->userA->id,
            'trainingplan_id' => $this->playlistA->id,
            'content_id'      => $this->content1->id,
            'progress'        => 100,
            'finish'          => '1',
            'learning_status' => 'completed',
        ]);

        $ongoing = $this->ongoingService->userPlaylists($this->userA->id);
        $plan = $ongoing->firstWhere('id', $this->playlistA->id);

        $this->assertNotNull($plan);
        $this->assertSame(2, (int) $plan->total_items);
        $this->assertSame(1, (int) $plan->finished_items);
        $this->assertSame(50, (int) $plan->progress_percent);
        $this->assertFalse($plan->is_completed);
    }

    /**
     * Test Ongoing 3: Direct Content progress does NOT make playlist ongoing or completed
     */
    public function test_ongoing_service_ignores_direct_content_progress(): void
    {
        // User A has 100% in Direct Content 1 and Content 2
        ContentUser::create([
            'user_id'         => $this->userA->id,
            'content_id'      => $this->content1->id,
            'progress'        => 100,
            'finish'          => '1',
            'learning_status' => 'completed',
        ]);
        ContentUser::create([
            'user_id'         => $this->userA->id,
            'content_id'      => $this->content2->id,
            'progress'        => 100,
            'finish'          => '1',
            'learning_status' => 'completed',
        ]);

        $ongoing = $this->ongoingService->userPlaylists($this->userA->id);
        $this->assertFalse($ongoing->contains('id', $this->playlistA->id));
        $this->assertFalse($ongoing->contains('id', $this->playlistB->id));
    }

    /**
     * Test Ongoing 4: Multi-Playlist isolation in Ongoing Service
     */
    public function test_ongoing_service_multi_playlist_isolation(): void
    {
        // User A completes Playlist B (1 of 1 -> 100%)
        PlaylistContentUser::create([
            'user_id'         => $this->userA->id,
            'trainingplan_id' => $this->playlistB->id,
            'content_id'      => $this->content1->id,
            'progress'        => 100,
            'finish'          => '1',
            'learning_status' => 'completed',
        ]);

        $ongoing = $this->ongoingService->userPlaylists($this->userA->id);

        // Playlist B is present and completed
        $planB = $ongoing->firstWhere('id', $this->playlistB->id);
        $this->assertNotNull($planB);
        $this->assertSame(100, (int) $planB->progress_percent);
        $this->assertTrue($planB->is_completed);

        // Playlist A is untouched
        $this->assertFalse($ongoing->contains('id', $this->playlistA->id));
    }

    /**
     * Test Ongoing 5: User isolation in Ongoing Service
     */
    public function test_ongoing_service_user_isolation(): void
    {
        // User A starts Playlist A
        PlaylistContentUser::create([
            'user_id'         => $this->userA->id,
            'trainingplan_id' => $this->playlistA->id,
            'content_id'      => $this->content1->id,
            'progress'        => 50,
            'finish'          => '0',
            'learning_status' => 'in_progress',
        ]);

        $ongoingA = $this->ongoingService->userPlaylists($this->userA->id);
        $ongoingB = $this->ongoingService->userPlaylists($this->userB->id);

        $this->assertTrue($ongoingA->contains('id', $this->playlistA->id));
        $this->assertFalse($ongoingB->contains('id', $this->playlistA->id));
    }

    /* =========================================================================
       COMPLETED PLAYLIST SERVICE TESTS
    ========================================================================= */

    /**
     * Test Completed 1: All Playlist contents completed -> qualified as completed
     */
    public function test_completed_service_returns_fully_completed_playlist(): void
    {
        // User A completes both contents in Playlist A
        PlaylistContentUser::create([
            'user_id'         => $this->userA->id,
            'trainingplan_id' => $this->playlistA->id,
            'content_id'      => $this->content1->id,
            'progress'        => 100,
            'finish'          => '1',
            'learning_status' => 'completed',
        ]);
        PlaylistContentUser::create([
            'user_id'         => $this->userA->id,
            'trainingplan_id' => $this->playlistA->id,
            'content_id'      => $this->content2->id,
            'progress'        => 100,
            'finish'          => '1',
            'learning_status' => 'completed',
        ]);

        $completed = $this->completedService->forUser($this->userA->id);
        $this->assertTrue($completed->contains('id', $this->playlistA->id));
    }

    /**
     * Test Completed 2: One content incomplete -> NOT completed
     */
    public function test_completed_service_excludes_partially_completed_playlist(): void
    {
        // 1 of 2 completed in Playlist A
        PlaylistContentUser::create([
            'user_id'         => $this->userA->id,
            'trainingplan_id' => $this->playlistA->id,
            'content_id'      => $this->content1->id,
            'progress'        => 100,
            'finish'          => '1',
            'learning_status' => 'completed',
        ]);
        PlaylistContentUser::create([
            'user_id'         => $this->userA->id,
            'trainingplan_id' => $this->playlistA->id,
            'content_id'      => $this->content2->id,
            'progress'        => 50,
            'finish'          => '0',
            'learning_status' => 'in_progress',
        ]);

        $completed = $this->completedService->forUser($this->userA->id);
        $this->assertFalse($completed->contains('id', $this->playlistA->id));
    }

    /**
     * Test Completed 3: Direct Content completion does NOT make playlist completed
     */
    public function test_completed_service_ignores_direct_content_completion(): void
    {
        // Direct Content 1 and 2 are 100% complete
        ContentUser::create([
            'user_id'         => $this->userA->id,
            'content_id'      => $this->content1->id,
            'progress'        => 100,
            'finish'          => '1',
            'learning_status' => 'completed',
        ]);
        ContentUser::create([
            'user_id'         => $this->userA->id,
            'content_id'      => $this->content2->id,
            'progress'        => 100,
            'finish'          => '1',
            'learning_status' => 'completed',
        ]);

        $completed = $this->completedService->forUser($this->userA->id);
        $this->assertFalse($completed->contains('id', $this->playlistA->id));
        $this->assertFalse($completed->contains('id', $this->playlistB->id));
    }

    /**
     * Test Completed 4: Multi-Playlist isolation in Completed Service
     */
    public function test_completed_service_multi_playlist_isolation(): void
    {
        // User A completes Playlist B (1 of 1), but not Playlist A (0 of 2)
        PlaylistContentUser::create([
            'user_id'         => $this->userA->id,
            'trainingplan_id' => $this->playlistB->id,
            'content_id'      => $this->content1->id,
            'progress'        => 100,
            'finish'          => '1',
            'learning_status' => 'completed',
        ]);

        $completed = $this->completedService->forUser($this->userA->id);

        $this->assertTrue($completed->contains('id', $this->playlistB->id));
        $this->assertFalse($completed->contains('id', $this->playlistA->id));
    }

    /**
     * Test Completed 5: User isolation in Completed Service
     */
    public function test_completed_service_user_isolation(): void
    {
        // User A completes Playlist B
        PlaylistContentUser::create([
            'user_id'         => $this->userA->id,
            'trainingplan_id' => $this->playlistB->id,
            'content_id'      => $this->content1->id,
            'progress'        => 100,
            'finish'          => '1',
            'learning_status' => 'completed',
        ]);

        $completedA = $this->completedService->forUser($this->userA->id);
        $completedB = $this->completedService->forUser($this->userB->id);

        $this->assertTrue($completedA->contains('id', $this->playlistB->id));
        $this->assertFalse($completedB->contains('id', $this->playlistB->id));
    }
}
