<?php

namespace Tests\Feature;

use App\Models\Classification;
use App\Models\Content;
use App\Models\PlaylistContentUser;
use App\Models\RoleAccessPermission;
use App\Models\TrainingPlan;
use App\Models\TrainingPlanDetail;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;
use Tests\TestCase;

class PlaylistDeleteTest extends TestCase
{
    use DatabaseTransactions;

    protected User $adminUser;
    protected User $unauthorizedUser;
    protected Classification $classification;

    protected function setUp(): void
    {
        parent::setUp();

        $this->classification = Classification::firstOrCreate(
            ['slug' => 'test-classification-pl-delete'],
            ['classification_name' => 'Testing Classification PL Delete']
        );

        $this->adminUser = User::factory()->create([
            'role'       => User::ROLE_SUPER_ADMIN,
            'team'       => 'Quality Assurance',
            'department' => 'Technology',
            'division'   => 'Engineering',
            'ssohash'    => 'test-admin-del-' . Str::uuid(),
            'sap_code'   => 'SAP-ADMIN-DEL',
        ]);

        $this->unauthorizedUser = User::factory()->create([
            'role'       => User::ROLE_USER,
            'team'       => 'Operations',
            'department' => 'Manufacturing',
            'division'   => 'Production',
            'ssohash'    => 'test-user-del-noperm-' . Str::uuid(),
            'sap_code'   => 'SAP-USER-DEL-NOPERM',
        ]);

        RoleAccessPermission::updateOrCreate(
            ['role' => User::ROLE_SUPER_ADMIN, 'module_key' => 'playlist_learning_path'],
            ['can_access' => true]
        );
    }

    /**
     * Item 1 — Authorized admin can delete an inactive playlist.
     */
    public function test_authorized_admin_can_delete_inactive_playlist(): void
    {
        $plan = TrainingPlan::create([
            'title'  => 'Deletable Inactive Playlist ' . Str::random(5),
            'active' => 'Inactive',
        ]);

        $response = $this->actingAs($this->adminUser)
            ->delete(route('playlist.destroy', $plan->slug));

        $response->assertRedirect(route('playlist.index'));
        $this->assertNull(TrainingPlan::find($plan->id));
        $this->assertTrue(TrainingPlan::withTrashed()->find($plan->id)->trashed());
    }

    /**
     * Item 2 — Unauthenticated user cannot delete playlist.
     */
    public function test_unauthenticated_user_cannot_delete(): void
    {
        $plan = TrainingPlan::create([
            'title'  => 'Guarded Playlist ' . Str::random(5),
            'active' => 'Inactive',
        ]);

        $response = $this->delete(route('playlist.destroy', $plan->slug));

        $response->assertRedirect(route('loginpage'));
        $this->assertFalse(TrainingPlan::withTrashed()->find($plan->id)->trashed());
    }

    /**
     * Item 3 — Unauthorized user receives 403.
     */
    public function test_unauthorized_user_receives_403(): void
    {
        $plan = TrainingPlan::create([
            'title'  => 'Protected Playlist ' . Str::random(5),
            'active' => 'Inactive',
        ]);

        $response = $this->actingAs($this->unauthorizedUser)
            ->delete(route('playlist.destroy', $plan->slug));

        $response->assertStatus(403);
        $this->assertFalse(TrainingPlan::withTrashed()->find($plan->id)->trashed());
    }

    /**
     * Item 4 — Nonexistent playlist is handled gracefully.
     */
    public function test_nonexistent_playlist_is_handled_gracefully(): void
    {
        // AJAX request
        $ajaxRes = $this->actingAs($this->adminUser)
            ->deleteJson('/manage/playlist/non-existent-playlist-slug-' . Str::random(8));

        $ajaxRes->assertStatus(404);
        $ajaxRes->assertJson([
            'success' => false,
            'message' => 'Playlist not found.',
        ]);

        // Standard request
        $stdRes = $this->actingAs($this->adminUser)
            ->delete('/manage/playlist/non-existent-playlist-slug-' . Str::random(8));

        $stdRes->assertRedirect(route('playlist.index'));
        $stdRes->assertSessionHas('warning', 'Playlist not found.');
    }

    /**
     * Item 5 — Already deleted playlist is handled gracefully.
     */
    public function test_already_deleted_playlist_is_handled_gracefully(): void
    {
        $plan = TrainingPlan::create([
            'title'  => 'Already Trashed ' . Str::random(5),
            'active' => 'Inactive',
        ]);
        $plan->delete();

        // AJAX request
        $ajaxRes = $this->actingAs($this->adminUser)
            ->deleteJson(route('playlist.destroy', $plan->slug));

        $ajaxRes->assertStatus(404);
        $ajaxRes->assertJson([
            'success' => false,
            'message' => 'This playlist has already been deleted.',
        ]);

        // Standard request
        $stdRes = $this->actingAs($this->adminUser)
            ->delete(route('playlist.destroy', $plan->slug));

        $stdRes->assertRedirect(route('playlist.index'));
        $stdRes->assertSessionHas('warning', 'This playlist has already been deleted.');
    }

    /**
     * Item 6 — Soft-deleted playlist disappears from index.
     */
    public function test_soft_deleted_playlist_disappears_from_index(): void
    {
        $uniqueTitle = 'Disappearing Playlist ' . Str::random(8);
        $plan = TrainingPlan::create([
            'title'  => $uniqueTitle,
            'active' => 'Inactive',
        ]);

        $this->actingAs($this->adminUser)
            ->delete(route('playlist.destroy', $plan->slug));

        $indexRes = $this->actingAs($this->adminUser)
            ->get(route('playlist.index'));

        $indexRes->assertStatus(200);
        $indexRes->assertDontSee($uniqueTitle);
    }

    /**
     * Item 7 — Soft-deleted playlist cannot open Detail.
     */
    public function test_soft_deleted_playlist_cannot_open_detail(): void
    {
        $plan = TrainingPlan::create([
            'title'  => 'No Detail Playlist ' . Str::random(5),
            'active' => 'Inactive',
        ]);
        $plan->delete();

        $response = $this->actingAs($this->adminUser)
            ->getJson(route('manage.playlist.details', $plan->id));

        $response->assertStatus(404);
        $response->assertJson([
            'success' => false,
            'message' => 'Playlist not found.',
        ]);
    }

    /**
     * Item 8 — Soft-deleted playlist cannot open Edit.
     */
    public function test_soft_deleted_playlist_cannot_open_edit(): void
    {
        $plan = TrainingPlan::create([
            'title'  => 'No Edit Playlist ' . Str::random(5),
            'active' => 'Inactive',
        ]);
        $plan->delete();

        $response = $this->actingAs($this->adminUser)
            ->get(route('playlist.edit', $plan->slug));

        $response->assertStatus(404);
    }

    /**
     * Item 9 — Underlying Content remains intact after playlist deletion.
     */
    public function test_underlying_content_remains_intact(): void
    {
        $content = Content::create([
            'title'             => 'Master Content ' . Str::random(6),
            'duration'          => '00:15:00',
            'learning_hours'    => 0.25,
            'id_classification' => $this->classification->id,
            'type'              => 'video',
            'active'            => 'Active',
        ]);

        $plan = TrainingPlan::create([
            'title'  => 'Owner Playlist ' . Str::random(6),
            'active' => 'Inactive',
        ]);

        TrainingPlanDetail::create([
            'trainingplan_id' => $plan->id,
            'content_id'      => $content->id,
            'content_order'   => 0,
        ]);

        $this->actingAs($this->adminUser)
            ->delete(route('playlist.destroy', $plan->slug));

        $this->assertNotNull(Content::find($content->id));
        $this->assertEquals('Active', Content::find($content->id)->active);
    }

    /**
     * Item 10 — User progress in playlist_content_users is preserved after playlist deletion.
     */
    public function test_user_progress_remains_intact(): void
    {
        $content = Content::create([
            'title'             => 'Progress Content ' . Str::random(6),
            'duration'          => '00:10:00',
            'learning_hours'    => 0.1667,
            'id_classification' => $this->classification->id,
            'type'              => 'video',
            'status'            => 'Active',
        ]);

        $plan = TrainingPlan::create([
            'title'  => 'Progress Playlist ' . Str::random(6),
            'active' => 'Active',
        ]);

        $pcu = PlaylistContentUser::create([
            'user_id'          => $this->adminUser->id,
            'trainingplan_id'  => $plan->id,
            'content_id'       => $content->id,
            'progress'         => 100,
            'finish'           => '1',
            'learning_hour'    => 0.1667,
            'completed_at'     => now(),
            'learning_status'  => 'completed',
        ]);

        $this->actingAs($this->adminUser)
            ->delete(route('playlist.destroy', $plan->slug));

        $freshPcu = PlaylistContentUser::find($pcu->id);
        $this->assertNotNull($freshPcu);
        $this->assertEquals(100, $freshPcu->progress);
        $this->assertEquals('1', $freshPcu->finish);
        $this->assertNotNull($freshPcu->completed_at);
    }

    /**
     * Item 11 — PlaylistContentUser can resolve soft-deleted playlist safely via withTrashed().
     */
    public function test_playlist_content_user_can_resolve_soft_deleted_playlist_safely(): void
    {
        $content = Content::create([
            'title'             => 'Relation Content ' . Str::random(6),
            'duration'          => '00:10:00',
            'learning_hours'    => 0.1667,
            'id_classification' => $this->classification->id,
            'type'              => 'video',
            'status'            => 'Active',
        ]);

        $plan = TrainingPlan::create([
            'title'  => 'Relation Plan ' . Str::random(6),
            'active' => 'Inactive',
        ]);

        $pcu = PlaylistContentUser::create([
            'user_id'          => $this->adminUser->id,
            'trainingplan_id'  => $plan->id,
            'content_id'       => $content->id,
            'progress'         => 50,
            'finish'           => '0',
            'learning_hour'    => 0.0833,
        ]);

        $this->actingAs($this->adminUser)
            ->delete(route('playlist.destroy', $plan->slug));

        $freshPcu = PlaylistContentUser::find($pcu->id);
        $this->assertNotNull($freshPcu->trainingPlan);
        $this->assertEquals($plan->id, $freshPcu->trainingPlan->id);
        $this->assertTrue($freshPcu->trainingPlan->trashed());
    }

    /**
     * Item 12 — Active playlist can be deleted with confirmation.
     */
    public function test_active_playlist_can_be_deleted(): void
    {
        $plan = TrainingPlan::create([
            'title'  => 'Active To Delete ' . Str::random(6),
            'active' => 'Active',
        ]);

        $response = $this->actingAs($this->adminUser)
            ->deleteJson(route('playlist.destroy', $plan->slug));

        $response->assertStatus(200);
        $response->assertJson([
            'success' => true,
            'message' => 'Playlist deleted successfully.',
        ]);

        $this->assertTrue(TrainingPlan::withTrashed()->find($plan->id)->trashed());
    }

    /**
     * Item 13 — Thumbnail cleanup removes physical file if present.
     */
    public function test_thumbnail_cleanup_removes_physical_file(): void
    {
        $dir = public_path('storage/files/training_plan');
        if (!File::isDirectory($dir)) {
            File::makeDirectory($dir, 0777, true, true);
        }

        $filename = 'test_thumb_' . Str::uuid() . '.png';
        $fullPath = $dir . '/' . $filename;
        File::put($fullPath, 'fake_png_data');
        $this->assertTrue(File::exists($fullPath));

        $plan = TrainingPlan::create([
            'title'     => 'Thumb Plan ' . Str::random(6),
            'thumbnail' => 'files/training_plan/' . $filename,
            'active'    => 'Inactive',
        ]);

        $this->actingAs($this->adminUser)
            ->delete(route('playlist.destroy', $plan->slug));

        $this->assertFalse(File::exists($fullPath));
    }

    /**
     * Item 14 — TrainingPlanDetail cleanup removes orphaned rows.
     */
    public function test_training_plan_detail_cleanup_is_correct(): void
    {
        $content = Content::create([
            'title'             => 'Detail Clean Content ' . Str::random(6),
            'duration'          => '00:10:00',
            'learning_hours'    => 0.1667,
            'id_classification' => $this->classification->id,
            'type'              => 'video',
            'active'            => 'Active',
        ]);

        $plan = TrainingPlan::create([
            'title'  => 'Detail Clean Plan ' . Str::random(6),
            'active' => 'Inactive',
        ]);

        TrainingPlanDetail::create([
            'trainingplan_id' => $plan->id,
            'content_id'      => $content->id,
            'content_order'   => 0,
        ]);

        $this->assertEquals(1, TrainingPlanDetail::where('trainingplan_id', $plan->id)->count());

        $this->actingAs($this->adminUser)
            ->delete(route('playlist.destroy', $plan->slug));

        $this->assertEquals(0, TrainingPlanDetail::where('trainingplan_id', $plan->id)->count());
    }

    /**
     * Item 15 — AJAX delete returns JSON.
     */
    public function test_ajax_delete_returns_json(): void
    {
        $plan = TrainingPlan::create([
            'title'  => 'Ajax Plan ' . Str::random(6),
            'active' => 'Inactive',
        ]);

        $response = $this->actingAs($this->adminUser)
            ->deleteJson(route('playlist.destroy', $plan->slug));

        $response->assertStatus(200);
        $response->assertJson([
            'success' => true,
            'message' => 'Playlist deleted successfully.',
        ]);
    }

    /**
     * Item 16 — Standard delete returns redirect with flash message.
     */
    public function test_standard_delete_returns_redirect(): void
    {
        $plan = TrainingPlan::create([
            'title'  => 'Standard Redirect Plan ' . Str::random(6),
            'active' => 'Inactive',
        ]);

        $response = $this->actingAs($this->adminUser)
            ->delete(route('playlist.destroy', $plan->slug));

        $response->assertRedirect(route('playlist.index'));
        $response->assertSessionHas('success', 'Playlist deleted successfully.');
    }

    /**
     * Item 17 & 18 — Double delete does not produce unhandled 500 or raw 404 crash.
     */
    public function test_double_delete_does_not_produce_unhandled_error(): void
    {
        $plan = TrainingPlan::create([
            'title'  => 'Double Click Plan ' . Str::random(6),
            'active' => 'Inactive',
        ]);

        // First click
        $first = $this->actingAs($this->adminUser)
            ->deleteJson(route('playlist.destroy', $plan->slug));
        $first->assertStatus(200);

        // Second click
        $second = $this->actingAs($this->adminUser)
            ->deleteJson(route('playlist.destroy', $plan->slug));
        $second->assertStatus(404);
        $second->assertJson([
            'success' => false,
            'message' => 'This playlist has already been deleted.',
        ]);
    }

    /**
     * Item 19 — Delete button uses modern modal trigger on index page.
     */
    public function test_delete_button_uses_modern_modal(): void
    {
        $plan = TrainingPlan::create([
            'title'  => 'Index Modal Button Plan ' . Str::random(6),
            'active' => 'Inactive',
        ]);

        $response = $this->actingAs($this->adminUser)
            ->get(route('playlist.index'));

        $response->assertStatus(200);
        $response->assertSee('btn-delete-playlist');
        $response->assertSee('data-bs-target="#modalDeletePlaylist"', false);
        $response->assertSee('data-delete-url', false);
        $response->assertSee('modalDeletePlaylist');
    }

    /**
     * Item 19 — Native confirm() is no longer used.
     */
    public function test_native_confirm_is_no_longer_used(): void
    {
        $response = $this->actingAs($this->adminUser)
            ->get(route('playlist.index'));

        $response->assertStatus(200);
        $response->assertDontSee('Are you sure want to delete this data?');
        $response->assertDontSee('onclick="return confirm(');
    }

    /**
     * Item 20 — Total materials count in system is accurately updated without orphan rows.
     */
    public function test_total_materials_count_in_system_is_accurately_updated_after_cleanup(): void
    {
        $initialTotal = TrainingPlanDetail::count();

        $content1 = Content::create([
            'title'             => 'Count Content 1 ' . Str::random(6),
            'duration'          => '00:10:00',
            'learning_hours'    => 0.1667,
            'id_classification' => $this->classification->id,
            'type'              => 'video',
            'active'            => 'Active',
        ]);

        $content2 = Content::create([
            'title'             => 'Count Content 2 ' . Str::random(6),
            'duration'          => '00:20:00',
            'learning_hours'    => 0.3333,
            'id_classification' => $this->classification->id,
            'type'              => 'video',
            'active'            => 'Active',
        ]);

        $plan = TrainingPlan::create([
            'title'  => 'Count Plan ' . Str::random(6),
            'active' => 'Inactive',
        ]);

        TrainingPlanDetail::create([
            'trainingplan_id' => $plan->id,
            'content_id'      => $content1->id,
            'content_order'   => 0,
        ]);
        TrainingPlanDetail::create([
            'trainingplan_id' => $plan->id,
            'content_id'      => $content2->id,
            'content_order'   => 1,
        ]);

        $this->assertEquals($initialTotal + 2, TrainingPlanDetail::count());

        $this->actingAs($this->adminUser)
            ->delete(route('playlist.destroy', $plan->slug));

        $this->assertEquals($initialTotal, TrainingPlanDetail::count());
    }
}