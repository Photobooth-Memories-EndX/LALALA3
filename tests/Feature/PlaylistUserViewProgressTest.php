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

class PlaylistUserViewProgressTest extends TestCase
{
    use DatabaseTransactions;

    protected User $user;
    protected TrainingPlan $playlistA;
    protected TrainingPlan $playlistB;
    protected Content $contentVideo;
    protected Content $contentDoc;

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
            'slug'                   => 'playlist-a-test-' . rand(1000, 9999),
            'active'                 => 'Active',
            'total_duration_seconds' => 3600,
            'learning_hours'         => 1.0,
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

        $this->contentVideo = Content::create([
            'title'        => 'Content Video Test',
            'slug'         => 'content-video-test-' . rand(1000, 9999),
            'type'         => 'Video',
            'duration'     => '00:10:00',
            'active'       => 'Active',
            'playlistonly' => 'N',
            'has_deadline' => 0,
        ]);

        $this->contentDoc = Content::create([
            'title'         => 'Content Document Test',
            'slug'          => 'content-doc-test-' . rand(1000, 9999),
            'type'          => 'Document',
            'duration'      => '00:05:00',
            'page_duration' => 0,
            'total_page'    => 10,
            'active'        => 'Active',
            'playlistonly'  => 'N',
            'has_deadline'  => 0,
        ]);

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

        TrainingPlanDetail::create([
            'trainingplan_id' => $this->playlistB->id,
            'content_id'      => $this->contentVideo->id,
            'content_order'   => 0,
        ]);
    }

    /**
     * Test 1: Opening playlist view lazily creates PlaylistContentUser (and NOT ContentUser)
     */
    public function test_view_lazily_creates_playlist_content_user_and_leaves_content_users_untouched(): void
    {
        $this->assertDatabaseMissing('playlist_content_users', [
            'user_id'         => $this->user->id,
            'trainingplan_id' => $this->playlistA->id,
            'content_id'      => $this->contentVideo->id,
        ]);

        $response = $this->actingAs($this->user)->get(route('playlist.user.view', [
            'slug'    => $this->playlistA->slug,
            'vidslug' => $this->contentVideo->slug,
        ]));

        $response->assertStatus(200);
        $response->assertViewHas('content_user', function ($viewContentUser) {
            return $viewContentUser instanceof PlaylistContentUser
                && (int) $viewContentUser->trainingplan_id === $this->playlistA->id
                && (int) $viewContentUser->content_id === $this->contentVideo->id;
        });

        $this->assertDatabaseHas('playlist_content_users', [
            'user_id'         => $this->user->id,
            'trainingplan_id' => $this->playlistA->id,
            'content_id'      => $this->contentVideo->id,
        ]);

        $this->assertDatabaseMissing('content_users', [
            'user_id'    => $this->user->id,
            'content_id' => $this->contentVideo->id,
        ]);
    }

    /**
     * Test 2: Playlist view reads progress from playlist_content_users
     */
    public function test_view_reads_existing_progress_from_playlist_content_users(): void
    {
        PlaylistContentUser::create([
            'user_id'         => $this->user->id,
            'trainingplan_id' => $this->playlistA->id,
            'content_id'      => $this->contentVideo->id,
            'progress'        => 45,
            'finish'          => '0',
            'learning_status' => 'in_progress',
            'current_time'    => 270,
        ]);

        $response = $this->actingAs($this->user)->get(route('playlist.user.view', [
            'slug'    => $this->playlistA->slug,
            'vidslug' => $this->contentVideo->slug,
        ]));

        $response->assertStatus(200);
        $response->assertViewHas('content_user', function ($viewContentUser) {
            return $viewContentUser instanceof PlaylistContentUser
                && (int) $viewContentUser->progress === 45
                && (int) $viewContentUser->current_time === 270;
        });

        $response->assertViewHas('progressByContentId', function ($progressByContentId) {
            return isset($progressByContentId[$this->contentVideo->id])
                && (int) $progressByContentId[$this->contentVideo->id] === 45;
        });
    }

    /**
     * Test 3: Multi-Context Isolation
     * Direct Content (80%) vs Playlist A (20%) vs Playlist B (60%)
     */
    public function test_multi_context_progress_isolation_in_views(): void
    {
        // 1. Direct Content = 80%
        ContentUser::create([
            'user_id'         => $this->user->id,
            'content_id'      => $this->contentVideo->id,
            'progress'        => 80,
            'finish'          => '0',
            'learning_status' => 'in_progress',
            'current_time'    => 480,
        ]);

        // 2. Playlist A = 20%
        PlaylistContentUser::create([
            'user_id'         => $this->user->id,
            'trainingplan_id' => $this->playlistA->id,
            'content_id'      => $this->contentVideo->id,
            'progress'        => 20,
            'finish'          => '0',
            'learning_status' => 'in_progress',
            'current_time'    => 120,
        ]);

        // 3. Playlist B = 60%
        PlaylistContentUser::create([
            'user_id'         => $this->user->id,
            'trainingplan_id' => $this->playlistB->id,
            'content_id'      => $this->contentVideo->id,
            'progress'        => 60,
            'finish'          => '0',
            'learning_status' => 'in_progress',
            'current_time'    => 360,
        ]);

        // Access Playlist A
        $responseA = $this->actingAs($this->user)->get(route('playlist.user.view', [
            'slug'    => $this->playlistA->slug,
            'vidslug' => $this->contentVideo->slug,
        ]));
        $responseA->assertStatus(200);
        $responseA->assertViewHas('content_user', fn ($cu) => (int) $cu->progress === 20 && (int) $cu->current_time === 120);
        $responseA->assertViewHas('progressByContentId', fn ($p) => (int) $p[$this->contentVideo->id] === 20);

        // Access Playlist B
        $responseB = $this->actingAs($this->user)->get(route('playlist.user.view', [
            'slug'    => $this->playlistB->slug,
            'vidslug' => $this->contentVideo->slug,
        ]));
        $responseB->assertStatus(200);
        $responseB->assertViewHas('content_user', fn ($cu) => (int) $cu->progress === 60 && (int) $cu->current_time === 360);
        $responseB->assertViewHas('progressByContentId', fn ($p) => (int) $p[$this->contentVideo->id] === 60);

        // Access Direct Content
        $responseDirect = $this->actingAs($this->user)->get(route('content.user.view', [
            'slug' => $this->contentVideo->slug,
        ]));
        $responseDirect->assertStatus(200);
        $responseDirect->assertViewHas('content_user', fn ($cu) => (int) $cu->progress === 80 && (int) $cu->current_time === 480);
    }
}
