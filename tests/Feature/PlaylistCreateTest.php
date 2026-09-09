<?php

namespace Tests\Feature;

use App\Models\Classification;
use App\Models\Content;
use App\Models\RoleAccessPermission;
use App\Models\TrainingPlan;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class PlaylistCreateTest extends TestCase
{
    use DatabaseTransactions;

    protected User $adminUser;
    protected User $unauthorizedUser;
    protected Classification $classification;

    protected function setUp(): void
    {
        parent::setUp();

        $this->classification = Classification::firstOrCreate(
            ['slug' => 'test-classification-pl-create'],
            ['classification_name' => 'Testing Classification PL Create']
        );

        $this->adminUser = User::factory()->create([
            'role'       => User::ROLE_SUPER_ADMIN,
            'team'       => 'Quality Assurance',
            'department' => 'Technology',
            'division'   => 'Engineering',
            'ssohash'    => 'test-admin-create-' . \Illuminate\Support\Str::uuid(),
            'sap_code'   => 'SAP-ADMIN-CREATE',
        ]);

        $this->unauthorizedUser = User::factory()->create([
            'role'       => User::ROLE_USER,
            'team'       => 'Operations',
            'department' => 'Manufacturing',
            'division'   => 'Production',
            'ssohash'    => 'test-user-create-noperm-' . \Illuminate\Support\Str::uuid(),
            'sap_code'   => 'SAP-USER-CREATE-NOPERM',
        ]);

        RoleAccessPermission::updateOrCreate(
            ['role' => User::ROLE_SUPER_ADMIN, 'module_key' => 'playlist_learning_path'],
            ['can_access' => true]
        );
    }

    /**
     * Item 1 — Create page loads successfully (HTTP 200) for authorized admin.
     */
    public function test_create_page_loads_successfully_for_authorized_admin(): void
    {
        $response = $this->actingAs($this->adminUser)
            ->get(route('playlist.create'));

        $response->assertStatus(200);
    }

    /**
     * Item 2 — Unauthenticated user is redirected to login.
     */
    public function test_unauthenticated_user_redirected_to_login(): void
    {
        $response = $this->get(route('playlist.create'));

        $response->assertRedirect(route('loginpage'));
    }

    /**
     * Item 3 — User without permission receives 403 Forbidden.
     */
    public function test_user_without_permission_receives_403_forbidden(): void
    {
        $response = $this->actingAs($this->unauthorizedUser)
            ->get(route('playlist.create'));

        $response->assertStatus(403);
    }

    /**
     * Item 4 — Page title is "Add Playlist".
     */
    public function test_page_title_is_add_playlist(): void
    {
        $response = $this->actingAs($this->adminUser)
            ->get(route('playlist.create'));

        $response->assertStatus(200);
        $response->assertSee('Add Playlist');
    }

    /**
     * Item 5 — Breadcrumb trail contains "Manage", "Playlist", and "Add Playlist".
     */
    public function test_breadcrumb_trail_contains_manage_playlist_and_add_playlist(): void
    {
        $response = $this->actingAs($this->adminUser)
            ->get(route('playlist.create'));

        $response->assertStatus(200);
        $response->assertSee('Manage');
        $response->assertSee('Playlist');
        $response->assertSee('Add Playlist');
    }

    /**
     * Item 6 — Back button links to route('playlist.index').
     */
    public function test_back_button_links_to_playlist_index(): void
    {
        $response = $this->actingAs($this->adminUser)
            ->get(route('playlist.create'));

        $response->assertStatus(200);
        $response->assertSee(route('playlist.index'));
    }

    /**
     * Item 7 — Section 1 "Playlist Information" is rendered with Title and optional URL Link.
     */
    public function test_section_1_playlist_information_rendered(): void
    {
        $response = $this->actingAs($this->adminUser)
            ->get(route('playlist.create'));

        $response->assertStatus(200);
        $response->assertSee('Playlist Information');
        $response->assertSee('Title');
        $response->assertSee('name="title"', false);
        $response->assertSee('name="url_link"', false);
    }

    /**
     * Item 8 — Section 2 "Thumbnail & Live Preview" is rendered with file input and live preview.
     */
    public function test_section_2_thumbnail_and_live_preview_rendered(): void
    {
        $response = $this->actingAs($this->adminUser)
            ->get(route('playlist.create'));

        $response->assertStatus(200);
        $response->assertSee('Thumbnail &amp; Live Preview', false);
        $response->assertSee('playlist_thumbnail_input');
        $response->assertSee('playlistThumbnailLink');
        $response->assertSee('playlistThumbnailImg');
    }

    /**
     * Item 9 — Section 2 has NO "Current Thumbnail" label, NO download button, NO remove toggle for existing image.
     */
    public function test_section_2_has_no_current_thumbnail_or_download_button(): void
    {
        $response = $this->actingAs($this->adminUser)
            ->get(route('playlist.create'));

        $response->assertStatus(200);
        $response->assertDontSee('Current Thumbnail');
        $response->assertDontSee('Download Current Thumbnail');
        $response->assertDontSee('name="remove_thumbnail"', false);
    }

    /**
     * Item 10 — Section 3 "Duration & Learning Hours" is rendered.
     */
    public function test_section_3_duration_and_learning_hours_rendered(): void
    {
        $response = $this->actingAs($this->adminUser)
            ->get(route('playlist.create'));

        $response->assertStatus(200);
        $response->assertSee('Duration &amp; Learning Hours', false);
        $response->assertSee('Playlist Total Duration');
        $response->assertSee('Learning Hours');
        $response->assertSee('btn-sync-playlist');
    }

    /**
     * Item 11 — Duration field defaults to 00:00:00.
     */
    public function test_duration_field_defaults_to_zero_time(): void
    {
        $response = $this->actingAs($this->adminUser)
            ->get(route('playlist.create'));

        $response->assertStatus(200);
        $response->assertSee('value="00:00:00"', false);
    }

    /**
     * Item 12 — Learning Hours field defaults to 0.0000.
     */
    public function test_learning_hours_field_defaults_to_zero(): void
    {
        $response = $this->actingAs($this->adminUser)
            ->get(route('playlist.create'));

        $response->assertStatus(200);
        $response->assertSee('value="0.0000"', false);
    }

    /**
     * Item 13 — Duration field is EDITABLE (not locked/disabled/readonly).
     */
    public function test_duration_field_is_editable_on_create(): void
    {
        $response = $this->actingAs($this->adminUser)
            ->get(route('playlist.create'));

        $response->assertStatus(200);
        $html = $response->getContent();
        $this->assertMatchesRegularExpression('/<input[^>]+name="total_duration"[^>]*>/', $html);
        $this->assertDoesNotMatchRegularExpression('/<input[^>]+name="total_duration"[^>]*readonly/', $html);
        $this->assertDoesNotMatchRegularExpression('/<input[^>]+name="total_duration"[^>]*disabled/', $html);
    }

    /**
     * Item 14 — Learning Hours field is EDITABLE (not locked/disabled/readonly).
     */
    public function test_learning_hours_field_is_editable_on_create(): void
    {
        $response = $this->actingAs($this->adminUser)
            ->get(route('playlist.create'));

        $response->assertStatus(200);
        $html = $response->getContent();
        $this->assertMatchesRegularExpression('/<input[^>]+name="learning_hours"[^>]*>/', $html);
        $this->assertDoesNotMatchRegularExpression('/<input[^>]+name="learning_hours"[^>]*readonly/', $html);
        $this->assertDoesNotMatchRegularExpression('/<input[^>]+name="learning_hours"[^>]*disabled/', $html);
    }

    /**
     * Item 15 — Section 4 "Playlist Content" is rendered with materials multi-select.
     */
    public function test_section_4_playlist_content_rendered(): void
    {
        $response = $this->actingAs($this->adminUser)
            ->get(route('playlist.create'));

        $response->assertStatus(200);
        $response->assertSee('Playlist Content');
        $response->assertSee('js-content-select');
    }

    /**
     * Item 16 — Section 4 has NO duplicate Save Playlist button.
     */
    public function test_section_4_has_no_duplicate_save_playlist_button(): void
    {
        $response = $this->actingAs($this->adminUser)
            ->get(route('playlist.create'));

        $response->assertStatus(200);
        $response->assertDontSee('btn-save-playlist-content');
    }

    /**
     * Item 17 — Section 5 "Deadline & Actions" is rendered with deadline toggle.
     */
    public function test_section_5_deadline_and_actions_rendered(): void
    {
        $response = $this->actingAs($this->adminUser)
            ->get(route('playlist.create'));

        $response->assertStatus(200);
        $response->assertSee('Deadline &amp; Actions', false);
        $response->assertSee('has_deadline');
        $response->assertSee('Set Playlist Deadline?');
    }

    /**
     * Item 18 — Section 5 has Cancel button linking to route('playlist.index').
     */
    public function test_section_5_has_cancel_button_linking_to_playlist_index(): void
    {
        $response = $this->actingAs($this->adminUser)
            ->get(route('playlist.create'));

        $response->assertStatus(200);
        $response->assertSee(route('playlist.index'));
        $response->assertSee('Cancel');
    }

    /**
     * Item 19 — Section 5 has "Save as Draft" button.
     */
    public function test_section_5_has_save_as_draft_button(): void
    {
        $response = $this->actingAs($this->adminUser)
            ->get(route('playlist.create'));

        $response->assertStatus(200);
        $response->assertSee('Save as Draft');
        $response->assertSee('value="draft"', false);
    }

    /**
     * Item 20 — Section 5 has "Save & Publish" button.
     */
    public function test_section_5_has_save_and_publish_button(): void
    {
        $response = $this->actingAs($this->adminUser)
            ->get(route('playlist.create'));

        $response->assertStatus(200);
        $response->assertSee('Save &amp; Publish', false);
        $response->assertSee('value="publish"', false);
    }

    /**
     * Item 21 — Page has NO Publish Status / Unpublish section anywhere.
     */
    public function test_page_has_no_publish_status_or_unpublish_section(): void
    {
        $response = $this->actingAs($this->adminUser)
            ->get(route('playlist.create'));

        $response->assertStatus(200);
        $response->assertDontSee('Publish Status');
        $response->assertDontSee('Unpublish Playlist');
        $response->assertDontSee('value="unpublish"', false);
    }

    /**
     * Item 22 — Form submission with required title saves playlist to database.
     */
    public function test_form_submission_with_required_title_saves_playlist(): void
    {
        $title = 'Auto Created Playlist ' . \Illuminate\Support\Str::random(6);

        $response = $this->actingAs($this->adminUser)
            ->post(route('playlist.store'), [
                'title'          => $title,
                'total_duration' => '00:00:00',
                'learning_hours' => '0.0000',
                'action'         => 'draft',
            ]);

        $this->assertDatabaseHas('trainingplan', [
            'title'  => $title,
            'active' => 'Inactive',
        ]);
    }

    /**
     * Item 23 — Form submission without title fails validation.
     */
    public function test_form_submission_without_title_fails_validation(): void
    {
        $response = $this->actingAs($this->adminUser)
            ->post(route('playlist.store'), [
                'title'          => '',
                'total_duration' => '00:00:00',
                'learning_hours' => '0.0000',
                'action'         => 'draft',
            ]);

        $response->assertSessionHasErrors('title');
    }

    /**
     * Item 24 — Form submission with "Save as Draft" saves with active = "Inactive".
     */
    public function test_form_submission_with_save_as_draft_saves_as_inactive(): void
    {
        $title = 'Draft Save Test ' . \Illuminate\Support\Str::random(6);

        $response = $this->actingAs($this->adminUser)
            ->post(route('playlist.store'), [
                'title'          => $title,
                'total_duration' => '00:10:00',
                'learning_hours' => '0.1667',
                'action'         => 'draft',
            ]);

        $response->assertRedirect(route('playlist.index'));

        $this->assertDatabaseHas('trainingplan', [
            'title'  => $title,
            'active' => 'Inactive',
        ]);
    }

    /**
     * Item 25 — Form submission with "Save & Publish" saves with active = "Active".
     */
    public function test_form_submission_with_save_and_publish_saves_as_active(): void
    {
        $title = 'Publish Save Test ' . \Illuminate\Support\Str::random(6);

        $response = $this->actingAs($this->adminUser)
            ->post(route('playlist.store'), [
                'title'          => $title,
                'total_duration' => '00:20:00',
                'learning_hours' => '0.3333',
                'action'         => 'publish',
            ]);

        $plan = TrainingPlan::where('title', $title)->first();
        $this->assertNotNull($plan);
        $this->assertEquals('Active', $plan->active);
        $response->assertRedirect(route('playlist.edit', $plan->slug));
    }

    /**
     * Item 26 — Form submission with manual duration (e.g. 01:30:00) persists to total_duration_seconds = 5400.
     */
    public function test_form_submission_with_manual_duration_persists(): void
    {
        $title = 'Manual Duration Test ' . \Illuminate\Support\Str::random(6);

        $response = $this->actingAs($this->adminUser)
            ->post(route('playlist.store'), [
                'title'          => $title,
                'total_duration' => '01:30:00',
                'learning_hours' => '1.5000',
                'action'         => 'draft',
            ]);

        $this->assertDatabaseHas('trainingplan', [
            'title'                  => $title,
            'total_duration_seconds' => 5400,
        ]);
    }

    /**
     * Item 27 — Form submission with manual decimal learning hours (e.g. 0.2517) persists without 0.5 increment restriction.
     */
    public function test_form_submission_with_manual_decimal_learning_hours_persists(): void
    {
        $title = 'Manual LH Test ' . \Illuminate\Support\Str::random(6);

        $response = $this->actingAs($this->adminUser)
            ->post(route('playlist.store'), [
                'title'          => $title,
                'total_duration' => '00:15:06',
                'learning_hours' => '0.2517',
                'action'         => 'draft',
            ]);

        $plan = TrainingPlan::where('title', $title)->first();
        $this->assertNotNull($plan);
        $this->assertEquals(0.2517, (float) $plan->learning_hours);
    }

    /**
     * Item 28 — Form submission with invalid duration format fails validation.
     */
    public function test_form_submission_with_invalid_duration_format_fails_validation(): void
    {
        $response = $this->actingAs($this->adminUser)
            ->post(route('playlist.store'), [
                'title'          => 'Invalid Duration Plan',
                'total_duration' => '1:30', // invalid HH:MM:SS format
                'learning_hours' => '0.5000',
                'action'         => 'draft',
            ]);

        $response->assertSessionHasErrors('total_duration');
    }

    /**
     * Item 29 — Form submission with invalid URL fails validation.
     */
    public function test_form_submission_with_invalid_url_fails_validation(): void
    {
        $response = $this->actingAs($this->adminUser)
            ->post(route('playlist.store'), [
                'title'          => 'Invalid URL Plan',
                'url_link'       => 'not-a-valid-url',
                'total_duration' => '00:00:00',
                'learning_hours' => '0.0000',
                'action'         => 'draft',
            ]);

        $response->assertSessionHasErrors('url_link');
    }

    /**
     * Additional — Synchronize preview endpoint calculates values from selected contents without persisting.
     */
    public function test_synchronize_preview_endpoint_returns_calculated_values(): void
    {
        $c1 = Content::create([
            'title'                 => 'Preview Content 1',
            'duration'              => '00:10:00',
            'learning_hours'        => 0.1667,
            'id_classification'     => $this->classification->id,
            'type'                  => 'video',
            'status'                => 'Active',
        ]);

        $c2 = Content::create([
            'title'                 => 'Preview Content 2',
            'duration'              => '00:20:00',
            'learning_hours'        => 0.3333,
            'id_classification'     => $this->classification->id,
            'type'                  => 'video',
            'status'                => 'Active',
        ]);

        $response = $this->actingAs($this->adminUser)
            ->postJson(route('playlist.synchronize-learning-hours', 'preview'), [
                'content' => [$c1->id, $c2->id],
            ]);

        $response->assertStatus(200);
        $response->assertJson([
            'success' => true,
        ]);
        $json = $response->json();
        $this->assertEquals('00:30:00', $json['duration']);
        $this->assertEquals(0.5, round((float) $json['learning_hours'], 1));
    }
}