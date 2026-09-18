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

class PlaylistAndContentStatusFilterTest extends TestCase
{
    use DatabaseTransactions;

    protected User $user;
    protected Classification $classification;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create([
            'team'       => 'Quality Assurance',
            'department' => 'Technology',
            'division'   => 'Engineering',
            'ssohash'    => 'test-hash-' . \Illuminate\Support\Str::uuid(),
            'sap_code'   => 'SAP-TEST',
            'role'       => 3,
        ]);
        $this->actingAs($this->user);

        $this->classification = Classification::create([
            'classification_name' => 'General Technical',
            'slug'                => 'general-technical',
        ]);
    }

    /**
     * POIN 1: PLAYLIST AKTIF, KONTEN INAKTIF/UNPUBLISH (VIEW USER)
     * - Playlist is Active.
     * - Content 1 is Active (00:30:00).
     * - Content 2 is Inactive (00:45:00).
     * - User view index & show must only show Content 1.
     */
    public function test_active_playlist_filters_out_inactive_contents_for_user(): void
    {
        $plan = TrainingPlan::create([
            'title'                  => 'Active Playlist Mixed Content',
            'active'                 => 'Active',
            'total_duration_seconds' => 4500,
            'learning_hours'         => 1.5,
        ]);

        $c1 = Content::create([
            'title'             => 'Active Chapter 1',
            'type'              => 'Video',
            'duration'          => '00:30:00',
            'active'            => 'Active',
            'classification_id' => $this->classification->id,
        ]);

        $c2 = Content::create([
            'title'             => 'Inactive Chapter 2',
            'type'              => 'Video',
            'duration'          => '00:45:00',
            'active'            => 'Inactive',
            'classification_id' => $this->classification->id,
        ]);

        TrainingPlanDetail::create(['trainingplan_id' => $plan->id, 'content_id' => $c1->id, 'content_order' => 0]);
        TrainingPlanDetail::create(['trainingplan_id' => $plan->id, 'content_id' => $c2->id, 'content_order' => 1]);

        // User Playlist Index: Cards only show active content
        $indexResponse = $this->get(route('playlist.user.index'));
        $indexResponse->assertOk();
        $indexResponse->assertSee('Active Playlist Mixed Content');
        $indexResponse->assertSee('Active Chapter 1');
        $indexResponse->assertDontSee('Inactive Chapter 2');

        // User Playlist Detail View: Shows Back button, #1 and #2 numbering, and Inactive badge on #2
        $viewResponse = $this->get(route('playlist.user.view', [$plan->slug, $c1->slug]));
        $viewResponse->assertOk();
        $viewResponse->assertSee(route('playlist.user.index'), false);
        $viewResponse->assertSee('#1');
        $viewResponse->assertSee('#2');
        $viewResponse->assertSee('Active Chapter 1');
        $viewResponse->assertSee('Inactive Chapter 2');
        $viewResponse->assertSee('Inactive');
        $viewResponse->assertSee('content-title-two-lines');
        $viewResponse->assertSee('title="Active Chapter 1"', false);
        $viewResponse->assertSee('title="Inactive Chapter 2"', false);

        // Accessing Inactive Content inside Playlist should 404
        $inactiveContentResponse = $this->get(route('playlist.user.view', [$plan->slug, $c2->slug]));
        $inactiveContentResponse->assertNotFound();
    }

    /**
     * POIN 2: PLAYLIST INAKTIF/UNPUBLISH HILANG DARI MENU PLAYLIST
     * - Playlist is Inactive.
     * - Playlist should not appear in /playlist index.
     * - Accessing inactive playlist directly returns 404.
     */
    public function test_inactive_playlist_does_not_appear_in_user_playlist_menu(): void
    {
        $inactivePlan = TrainingPlan::create([
            'title'                  => 'Hidden Inactive Playlist',
            'active'                 => 'Inactive',
            'total_duration_seconds' => 1800,
            'learning_hours'         => 0.5,
        ]);

        $c1 = Content::create([
            'title'             => 'Content for Inactive Playlist',
            'type'              => 'Video',
            'duration'          => '00:30:00',
            'active'            => 'Active',
            'classification_id' => $this->classification->id,
        ]);

        TrainingPlanDetail::create(['trainingplan_id' => $inactivePlan->id, 'content_id' => $c1->id, 'content_order' => 0]);

        $indexResponse = $this->get(route('playlist.user.index'));
        $indexResponse->assertOk();
        $indexResponse->assertDontSee('Hidden Inactive Playlist');

        // Direct access to inactive playlist returns 404
        $viewResponse = $this->get(route('playlist.user.view', [$inactivePlan->slug, $c1->slug]));
        $viewResponse->assertNotFound();
    }

    /**
     * POIN 3: KONTEN DI ALL CONTENT TETAP AKTIF SAAT PLAYLIST INAKTIF
     * - Content is Active (playlistonly = 'N').
     * - Linked Playlist is Inactive.
     * - Content must still be visible in /content (All Content).
     */
    public function test_content_in_all_content_remains_visible_when_playlist_is_inactive(): void
    {
        $inactivePlan = TrainingPlan::create([
            'title'  => 'Inactive Parent Playlist',
            'active' => 'Inactive',
        ]);

        $c1 = Content::create([
            'title'             => 'Stand-alone Active Content in Inactive Playlist',
            'type'              => 'Video',
            'duration'          => '00:20:00',
            'active'            => 'Active',
            'playlistonly'      => 'N',
            'classification_id' => $this->classification->id,
        ]);

        TrainingPlanDetail::create(['trainingplan_id' => $inactivePlan->id, 'content_id' => $c1->id, 'content_order' => 0]);

        $contentIndexResponse = $this->get(route('content.user.index'));
        $contentIndexResponse->assertOk();
        $contentIndexResponse->assertSee('Stand-alone Active Content in Inactive Playlist');
    }

    /**
     * POIN 4: TAMPILAN KONTEN INAKTIF DI SETTING MANAGE PLAYLIST (KHUSUS ADMIN)
     * - Admin Edit Playlist Manage page shows all contents including inactive ones.
     * - Inactive content has data-status="Inactive" in options.
     */
    public function test_admin_manage_playlist_edit_shows_inactive_contents_with_status(): void
    {
        $plan = TrainingPlan::create([
            'title'  => 'Admin Manage Playlist',
            'active' => 'Active',
        ]);

        $cActive = Content::create([
            'title'             => 'Active Module',
            'type'              => 'Video',
            'duration'          => '00:15:00',
            'active'            => 'Active',
            'classification_id' => $this->classification->id,
        ]);

        $cInactive = Content::create([
            'title'             => 'Inactive Module',
            'type'              => 'Video',
            'duration'          => '00:15:00',
            'active'            => 'Inactive',
            'classification_id' => $this->classification->id,
        ]);

        TrainingPlanDetail::create(['trainingplan_id' => $plan->id, 'content_id' => $cActive->id, 'content_order' => 0]);
        TrainingPlanDetail::create(['trainingplan_id' => $plan->id, 'content_id' => $cInactive->id, 'content_order' => 1]);

        $editResponse = $this->get(route('playlist.edit', $plan->slug));
        $editResponse->assertOk();
        $editResponse->assertSee('Active Module');
        $editResponse->assertSee('Inactive Module');
        $editResponse->assertSee('data-status="Inactive"', false);
    }

    /**
     * POIN 5: PERBAIKI BUG URUTAN SECTION PLAYLIST (FIX ORDERING)
     * - Model TrainingPlan relation contents() & trainingplandetail() are ordered by numeric content_order.
     * - TrainingPlanController update properly updates content_order for existing and reordered items.
     */
    public function test_playlist_section_ordering_and_update_reorder(): void
    {
        $plan = TrainingPlan::create([
            'title'  => 'Ordering Test Playlist',
            'active' => 'Inactive',
        ]);

        $c1 = Content::create(['title' => 'Chapter Alpha', 'duration' => '00:10:00', 'active' => 'Active']);
        $c2 = Content::create(['title' => 'Chapter Beta', 'duration' => '00:10:00', 'active' => 'Active']);
        $c3 = Content::create(['title' => 'Chapter Gamma', 'duration' => '00:10:00', 'active' => 'Active']);

        // Set initial order: Alpha (0), Beta (1), Gamma (2)
        TrainingPlanDetail::create(['trainingplan_id' => $plan->id, 'content_id' => $c1->id, 'content_order' => 0]);
        TrainingPlanDetail::create(['trainingplan_id' => $plan->id, 'content_id' => $c2->id, 'content_order' => 1]);
        TrainingPlanDetail::create(['trainingplan_id' => $plan->id, 'content_id' => $c3->id, 'content_order' => 2]);

        $this->assertEquals(['Chapter Alpha', 'Chapter Beta', 'Chapter Gamma'], $plan->fresh()->contents->pluck('title')->all());

        // Admin updates order: Gamma (0), Alpha (1), Beta (2)
        $updateResponse = $this->put(route('playlist.update', $plan->slug), [
            'title'   => 'Ordering Test Playlist',
            'content' => [$c3->id, $c1->id, $c2->id],
            'action'  => 'publish',
        ]);

        $updateResponse->assertSessionHasNoErrors();

        $freshDetails = TrainingPlanDetail::where('trainingplan_id', $plan->id)
            ->orderByRaw('CAST(content_order AS UNSIGNED) ASC')
            ->get();

        $this->assertSame($c3->id, (int) $freshDetails[0]->content_id);
        $this->assertSame(0, (int) $freshDetails[0]->content_order);

        $this->assertSame($c1->id, (int) $freshDetails[1]->content_id);
        $this->assertSame(1, (int) $freshDetails[1]->content_order);

        $this->assertSame($c2->id, (int) $freshDetails[2]->content_id);
        $this->assertSame(2, (int) $freshDetails[2]->content_order);

        $this->assertEquals(['Chapter Gamma', 'Chapter Alpha', 'Chapter Beta'], $plan->fresh()->contents->pluck('title')->all());
    }

    /**
     * POIN 6: SOFT-DELETED CONTENT DOES NOT APPEAR IN LEARNER SIDEBAR
     * - Playlist contains multiple contents.
     * - One referenced content is soft-deleted.
     * - Learner sidebar renders without crashing (200 OK).
     * - Soft-deleted content is not rendered as a blank or ghost item.
     * - Remaining active contents are rendered properly.
     * - Existing PlaylistContentUser progress records remain untouched.
     */
    public function test_soft_deleted_content_does_not_appear_in_learner_sidebar(): void
    {
        $plan = TrainingPlan::create([
            'title'                  => 'Sidebar Soft-Delete Safety Plan',
            'active'                 => 'Active',
            'total_duration_seconds' => 3600,
            'learning_hours'         => 1.0,
        ]);

        $c1 = Content::create([
            'title'             => 'Active Lesson Alpha',
            'type'              => 'Video',
            'duration'          => '00:20:00',
            'active'            => 'Active',
            'classification_id' => $this->classification->id,
        ]);

        $c2 = Content::create([
            'title'             => 'Doomed Soft-Deleted Lesson Beta',
            'type'              => 'Video',
            'duration'          => '00:20:00',
            'active'            => 'Active',
            'classification_id' => $this->classification->id,
        ]);

        $c3 = Content::create([
            'title'             => 'Active Lesson Gamma',
            'type'              => 'Video',
            'duration'          => '00:20:00',
            'active'            => 'Active',
            'classification_id' => $this->classification->id,
        ]);

        $d1 = TrainingPlanDetail::create(['trainingplan_id' => $plan->id, 'content_id' => $c1->id, 'content_order' => 0]);
        $d2 = TrainingPlanDetail::create(['trainingplan_id' => $plan->id, 'content_id' => $c2->id, 'content_order' => 1]);
        $d3 = TrainingPlanDetail::create(['trainingplan_id' => $plan->id, 'content_id' => $c3->id, 'content_order' => 2]);

        // Pre-create user progress records for lessons
        $pcu1 = PlaylistContentUser::create([
            'user_id'         => $this->user->id,
            'trainingplan_id' => $plan->id,
            'content_id'      => $c1->id,
            'progress'        => 100,
            'finish'          => '1',
            'learning_status' => 'completed',
        ]);

        $pcu2 = PlaylistContentUser::create([
            'user_id'         => $this->user->id,
            'trainingplan_id' => $plan->id,
            'content_id'      => $c2->id,
            'progress'        => 50,
            'finish'          => '0',
            'learning_status' => 'in_progress',
        ]);

        // Soft-delete Content 2
        $c2->delete();
        $this->assertSoftDeleted('content', ['id' => $c2->id]);

        // Access learner playlist view on Lesson 1
        $response = $this->get(route('playlist.user.view', [$plan->slug, $c1->slug]));

        $response->assertOk();

        // Active lessons must be visible in the sidebar
        $response->assertSee('Active Lesson Alpha');
        $response->assertSee('Active Lesson Gamma');

        // Soft-deleted lesson must NOT appear anywhere in the sidebar
        $response->assertDontSee('Doomed Soft-Deleted Lesson Beta');

        // Verify no empty/blank data-title or title attributes exist for missing content
        $response->assertDontSee('data-title=""', false);
        $response->assertDontSee('title=""', false);

        // Verify existing progress in database remains completely intact
        $freshPcu1 = $pcu1->fresh();
        $this->assertEquals(100, $freshPcu1->progress);
        $this->assertEquals('1', (string) $freshPcu1->finish);

        $freshPcu2 = $pcu2->fresh();
        $this->assertEquals(50, $freshPcu2->progress);
        $this->assertEquals('0', (string) $freshPcu2->finish);

        // Verify TrainingPlanDetail records remain in database (not deleted silently)
        $this->assertDatabaseHas('trainingplandetail', ['id' => $d1->id]);
        $this->assertDatabaseHas('trainingplandetail', ['id' => $d2->id]);
        $this->assertDatabaseHas('trainingplandetail', ['id' => $d3->id]);
    }
}
