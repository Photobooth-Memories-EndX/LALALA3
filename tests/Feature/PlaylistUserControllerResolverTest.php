<?php

namespace Tests\Feature;

use App\Models\Classification;
use App\Models\Content;
use App\Models\ContentUser;
use App\Models\PlaylistContentUser;
use App\Models\TrainingPlan;
use App\Models\TrainingPlanDetail;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class PlaylistUserControllerResolverTest extends TestCase
{
    use DatabaseTransactions;

    protected User $user;
    protected Classification $classification;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create([
            'team'       => 'Engineering',
            'department' => 'Technology',
            'division'   => 'LMS',
            'ssohash'    => 'test-hash-ctrl-' . \Illuminate\Support\Str::uuid(),
            'sap_code'   => 'SAP-CTRL-' . rand(1000, 9999),
            'role'       => 1,
            'active'     => 1,
        ]);

        $this->classification = Classification::firstOrCreate(
            ['classification_name' => 'Playlist Controller Test Classification'],
            ['slug' => 'playlist-ctrl-test-classification']
        );
    }

    /**
     * TEST 1: Historical progress (Category A) appears in Playlist index and view without PCU record
     */
    public function test_historical_category_a_progress_appears_in_playlist_view_and_index(): void
    {
        $plan = TrainingPlan::create([
            'title'  => 'Category A Historical Plan',
            'slug'   => 'cat-a-hist-plan-' . rand(1000, 9999),
            'active' => 'Active',
        ]);

        $content = Content::create([
            'title'             => 'Cat A Hist Content',
            'slug'              => 'cat-a-hist-content-' . rand(1000, 9999),
            'type'              => 'Video',
            'classification_id' => $this->classification->id,
            'active'            => 'Active',
            'playlistonly'      => 'Y',
        ]);

        TrainingPlanDetail::create([
            'trainingplan_id' => $plan->id,
            'content_id'      => $content->id,
            'content_order'   => 0,
        ]);

        // Playlist completion in playlist_content_users
        PlaylistContentUser::create([
            'user_id'         => $this->user->id,
            'trainingplan_id' => $plan->id,
            'content_id'      => $content->id,
            'progress'        => 100,
            'finish'          => '1',
            'learning_status' => 'completed',
        ]);

        // Index page should show 100% progress for this playlist
        $responseIndex = $this->actingAs($this->user)->get(route('playlist.user.index'));
        $responseIndex->assertStatus(200);
        $responseIndex->assertViewHas('playlistStats', function ($stats) use ($plan) {
            return isset($stats[$plan->id]) && $stats[$plan->id]['completedContent'] === 1 && $stats[$plan->id]['progress'] == 100;
        });

        // View page should show 1 of 1 finished
        $responseView = $this->actingAs($this->user)->get(route('playlist.user.view', [
            'slug'    => $plan->slug,
            'vidslug' => $content->slug,
        ]));
        $responseView->assertStatus(200);
        $responseView->assertViewHas('completedContent', 1);
        $responseView->assertViewHas('percentageProgressPlaylist', 100);
        $responseView->assertViewHas('progressByContentId', function ($progressMap) use ($content) {
            return ($progressMap[$content->id] ?? null) === 100;
        });
    }

    /**
     * TEST 2: Existing playlist_content_users overrides historical content_users
     */
    public function test_existing_pcu_overrides_historical_content_users_in_controller(): void
    {
        $plan = TrainingPlan::create([
            'title'  => 'Override Test Plan',
            'slug'   => 'override-test-plan-' . rand(1000, 9999),
            'active' => 'Active',
        ]);

        $content = Content::create([
            'title'             => 'Override Test Content',
            'slug'              => 'override-test-content-' . rand(1000, 9999),
            'type'              => 'Video',
            'classification_id' => $this->classification->id,
            'active'            => 'Active',
            'playlistonly'      => 'Y',
        ]);

        TrainingPlanDetail::create([
            'trainingplan_id' => $plan->id,
            'content_id'      => $content->id,
            'content_order'   => 0,
        ]);

        // Historical 100% in content_users
        ContentUser::create([
            'user_id'         => $this->user->id,
            'content_id'      => $content->id,
            'progress'        => 100,
            'finish'          => '1',
            'learning_status' => 'completed',
        ]);

        // Authoritative new 25% in playlist_content_users
        PlaylistContentUser::create([
            'user_id'         => $this->user->id,
            'trainingplan_id' => $plan->id,
            'content_id'      => $content->id,
            'progress'        => 25,
            'finish'          => '0',
            'learning_status' => 'in_progress',
        ]);

        $responseView = $this->actingAs($this->user)->get(route('playlist.user.view', [
            'slug'    => $plan->slug,
            'vidslug' => $content->slug,
        ]));

        $responseView->assertStatus(200);
        $responseView->assertViewHas('completedContent', 0);
        $responseView->assertViewHas('progressByContentId', function ($progressMap) use ($content) {
            return ($progressMap[$content->id] ?? null) === 25;
        });
    }

    /**
     * TEST 3: Multi-playlist historical content (Category B) is NOT credited blindly
     */
    public function test_multi_playlist_historical_content_is_not_credited_blindly(): void
    {
        $planA = TrainingPlan::create(['title' => 'Multi Plan A', 'slug' => 'multi-plan-a-' . rand(1000, 9999), 'active' => 'Active']);
        $planB = TrainingPlan::create(['title' => 'Multi Plan B', 'slug' => 'multi-plan-b-' . rand(1000, 9999), 'active' => 'Active']);

        $content = Content::create([
            'title'             => 'Multi Plan Shared Content',
            'slug'              => 'multi-shared-content-' . rand(1000, 9999),
            'type'              => 'Video',
            'classification_id' => $this->classification->id,
            'active'            => 'Active',
            'playlistonly'      => 'Y',
        ]);

        TrainingPlanDetail::create(['trainingplan_id' => $planA->id, 'content_id' => $content->id, 'content_order' => 0]);
        TrainingPlanDetail::create(['trainingplan_id' => $planB->id, 'content_id' => $content->id, 'content_order' => 0]);

        // Historical 100% in content_users
        ContentUser::create([
            'user_id'    => $this->user->id,
            'content_id' => $content->id,
            'progress'   => 100,
            'finish'     => '1',
        ]);

        // View Plan A -> Should be 0 completed because attribution is ambiguous
        $responseViewA = $this->actingAs($this->user)->get(route('playlist.user.view', [
            'slug'    => $planA->slug,
            'vidslug' => $content->slug,
        ]));
        $responseViewA->assertStatus(200);
        $responseViewA->assertViewHas('completedContent', 0);
        $responseViewA->assertViewHas('progressByContentId', function ($progressMap) use ($content) {
            return ($progressMap[$content->id] ?? null) === 0;
        });
    }

    /**
     * TEST 4: Browsing playlist index has zero database writes to playlist_content_users
     */
    public function test_browsing_playlist_index_does_not_mutate_database(): void
    {
        $plan = TrainingPlan::create(['title' => 'Index Read Plan', 'slug' => 'index-read-plan-' . rand(1000, 9999), 'active' => 'Active']);
        $content = Content::create(['title' => 'Index Read Content', 'slug' => 'index-read-content-' . rand(1000, 9999), 'type' => 'Video', 'classification_id' => $this->classification->id, 'active' => 'Active', 'playlistonly' => 'Y']);
        TrainingPlanDetail::create(['trainingplan_id' => $plan->id, 'content_id' => $content->id, 'content_order' => 0]);

        $initialPcuCount = PlaylistContentUser::count();

        $response = $this->actingAs($this->user)->get(route('playlist.user.index'));
        $response->assertStatus(200);

        $this->assertEquals($initialPcuCount, PlaylistContentUser::count());
    }

    /**
     * TEST 5: Opening untouched playlist view initializes PCU record safely at 0% without leaking Direct Content
     */
    public function test_viewing_untouched_playlist_initializes_pcu_safely_at_zero(): void
    {
        $plan = TrainingPlan::create(['title' => 'Init PCU Plan', 'slug' => 'init-pcu-plan-' . rand(1000, 9999), 'active' => 'Active']);
        $content = Content::create(['title' => 'Init PCU Content', 'slug' => 'init-pcu-content-' . rand(1000, 9999), 'type' => 'Video', 'classification_id' => $this->classification->id, 'active' => 'Active', 'playlistonly' => 'Y']);
        TrainingPlanDetail::create(['trainingplan_id' => $plan->id, 'content_id' => $content->id, 'content_order' => 0]);

        // User has direct progress 80% in content_users
        ContentUser::create([
            'user_id'          => $this->user->id,
            'content_id'       => $content->id,
            'progress'         => 80,
            'finish'           => '0',
            'learning_status'  => 'in_progress',
            'current_time'     => 480,
            'duration_seconds' => 600,
        ]);

        $response = $this->actingAs($this->user)->get(route('playlist.user.view', [
            'slug'    => $plan->slug,
            'vidslug' => $content->slug,
        ]));
        $response->assertStatus(200);

        // PCU record is initialized at 0% for the playlist (clean isolation)
        $this->assertDatabaseHas('playlist_content_users', [
            'user_id'         => $this->user->id,
            'trainingplan_id' => $plan->id,
            'content_id'      => $content->id,
            'progress'        => 0,
            'finish'          => '0',
            'learning_status' => 'not_started',
        ]);
    }
}
