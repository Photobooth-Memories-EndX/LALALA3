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

class PlaylistUserIndexProgressTest extends TestCase
{
    use DatabaseTransactions;

    protected User $userA;
    protected User $userB;
    protected TrainingPlan $playlistA;
    protected TrainingPlan $playlistB;
    protected Content $content1;
    protected Content $content2;

    protected function setUp(): void
    {
        parent::setUp();

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

        // Playlist A has 2 contents
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

        // Playlist B has 1 content (content1)
        TrainingPlanDetail::create([
            'trainingplan_id' => $this->playlistB->id,
            'content_id'      => $this->content1->id,
            'content_order'   => 0,
        ]);
    }

    /**
     * Test 1 — Playlist card without progress renders zero progress correctly
     */
    public function test_playlist_card_without_progress_renders_zero_percent(): void
    {
        $response = $this->actingAs($this->userA)->get(route('playlist.user.index'));

        $response->assertStatus(200);
        $response->assertViewHas('playlistStats', function ($stats) {
            $statA = $stats[$this->playlistA->id] ?? null;
            return $statA !== null
                && $statA['totalContent'] === 2
                && $statA['completedContent'] === 0
                && $statA['progress'] === 0;
        });
    }

    /**
     * Test 2 — Playlist card reads progress from playlist_content_users
     */
    public function test_playlist_card_calculates_progress_from_playlist_content_users(): void
    {
        // 1 completed of 2 in Playlist A -> 50%
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
            'progress'        => 40,
            'finish'          => '0',
            'learning_status' => 'in_progress',
        ]);

        $response = $this->actingAs($this->userA)->get(route('playlist.user.index'));

        $response->assertStatus(200);
        $response->assertViewHas('playlistStats', function ($stats) {
            $statA = $stats[$this->playlistA->id] ?? null;
            return $statA !== null
                && $statA['totalContent'] === 2
                && $statA['completedContent'] === 1
                && $statA['progress'] === 50;
        });
    }

    /**
     * Test 3 — Direct Content progress is ignored on playlist catalog card
     */
    public function test_direct_content_progress_is_ignored_on_playlist_card(): void
    {
        // Direct content is 100% completed
        ContentUser::create([
            'user_id'         => $this->userA->id,
            'content_id'      => $this->content1->id,
            'progress'        => 100,
            'finish'          => '1',
            'learning_status' => 'completed',
        ]);

        // Playlist progress for content1 is only 30% (not finished)
        PlaylistContentUser::create([
            'user_id'         => $this->userA->id,
            'trainingplan_id' => $this->playlistA->id,
            'content_id'      => $this->content1->id,
            'progress'        => 30,
            'finish'          => '0',
            'learning_status' => 'in_progress',
        ]);

        $response = $this->actingAs($this->userA)->get(route('playlist.user.index'));

        $response->assertStatus(200);
        $response->assertViewHas('playlistStats', function ($stats) {
            $statA = $stats[$this->playlistA->id] ?? null;
            // 0 of 2 completed in Playlist A (direct 100% must be ignored)
            return $statA !== null
                && $statA['completedContent'] === 0
                && $statA['progress'] === 0;
        });
    }

    /**
     * Test 4 — Multiple Playlist isolation on catalog cards
     */
    public function test_multiple_playlist_cards_have_isolated_progress(): void
    {
        // Playlist A: content1 finished (1 of 2 -> 50%)
        PlaylistContentUser::create([
            'user_id'         => $this->userA->id,
            'trainingplan_id' => $this->playlistA->id,
            'content_id'      => $this->content1->id,
            'progress'        => 100,
            'finish'          => '1',
            'learning_status' => 'completed',
        ]);

        // Playlist B: content1 NOT finished (0 of 1 -> 0%)
        PlaylistContentUser::create([
            'user_id'         => $this->userA->id,
            'trainingplan_id' => $this->playlistB->id,
            'content_id'      => $this->content1->id,
            'progress'        => 20,
            'finish'          => '0',
            'learning_status' => 'in_progress',
        ]);

        $response = $this->actingAs($this->userA)->get(route('playlist.user.index'));

        $response->assertStatus(200);
        $response->assertViewHas('playlistStats', function ($stats) {
            $statA = $stats[$this->playlistA->id] ?? null;
            $statB = $stats[$this->playlistB->id] ?? null;
            return $statA['progress'] === 50 && $statB['progress'] === 0;
        });
    }

    /**
     * Test 5 — Different user isolation
     */
    public function test_different_users_see_their_own_playlist_progress(): void
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

        // User B has 0% in Playlist B
        PlaylistContentUser::create([
            'user_id'         => $this->userB->id,
            'trainingplan_id' => $this->playlistB->id,
            'content_id'      => $this->content1->id,
            'progress'        => 0,
            'finish'          => '0',
            'learning_status' => 'not_started',
        ]);

        $responseA = $this->actingAs($this->userA)->get(route('playlist.user.index'));
        $responseA->assertStatus(200);
        $responseA->assertViewHas('playlistStats', fn ($s) => $s[$this->playlistB->id]['progress'] === 100);

        $responseB = $this->actingAs($this->userB)->get(route('playlist.user.index'));
        $responseB->assertStatus(200);
        $responseB->assertViewHas('playlistStats', fn ($s) => $s[$this->playlistB->id]['progress'] === 0);
    }
}
