<?php

namespace Tests\Feature;

use App\Models\RoleAccessPermission;
use App\Models\User;
use Tests\TestCase;

class PlaylistLearningHourLayoutTest extends TestCase
{
    protected User $admin;
    protected User $regularUser;

    protected function setUp(): void
    {
        parent::setUp();

        RoleAccessPermission::updateOrCreate(
            ['role' => User::ROLE_ADMIN, 'module_key' => 'learning_hours_management'],
            ['can_access' => true]
        );
        RoleAccessPermission::updateOrCreate(
            ['role' => User::ROLE_USER, 'module_key' => 'learning_hours_management'],
            ['can_access' => false]
        );

        $this->admin = User::where('role', User::ROLE_ADMIN)->first()
            ?? User::where('role', User::ROLE_SUPER_ADMIN)->first()
            ?? User::first();

        $this->regularUser = User::where('role', User::ROLE_USER)->first()
            ?? User::factory()->make(['role' => User::ROLE_USER]);
    }

    public function test_page_renders_with_authorized_permission(): void
    {
        $response = $this->actingAs($this->admin)->get('/manage/learning-hours/playlist-content-user');
        $response->assertStatus(200);
        $response->assertViewIs('manage.learning-hours.playlist-content-user.index');
    }

    public function test_page_header_and_action_buttons_render(): void
    {
        $response = $this->actingAs($this->admin)->get('/manage/learning-hours/playlist-content-user');
        $response->assertStatus(200);

        $html = $response->getContent();

        $this->assertStringContainsString('Manage &bull; Learning Hours', $html);
        $this->assertStringContainsString('Playlist Content User Learning Hours', $html);
        $this->assertStringContainsString('Compare Master Learning Hours with Actual Watched Duration for all Playlist Content records.', $html);

        // Action buttons
        $this->assertStringContainsString('Direct Content View', $html);
        $this->assertStringContainsString(route('manage.learning-hours.content-user.index'), $html);
        $this->assertStringContainsString('Back to Dashboard', $html);
        $this->assertStringContainsString(route('manage.index'), $html);
    }

    public function test_summary_cards_render_with_four_equal_cards(): void
    {
        $response = $this->actingAs($this->admin)->get('/manage/learning-hours/playlist-content-user');
        $response->assertStatus(200);

        $html = $response->getContent();

        // 4 card labels
        $this->assertStringContainsString('Total Playlist Records', $html);
        $this->assertStringContainsString('Total Actual Hours', $html);
        $this->assertStringContainsString('Actual Tracking', $html);
        $this->assertStringContainsString('Historical Fallback', $html);

        // Grid classes for equal height and layout
        $this->assertStringContainsString('stat-card-total', $html);
        $this->assertStringContainsString('stat-card-calculated', $html);
        $this->assertStringContainsString('stat-card-actual', $html);
        $this->assertStringContainsString('stat-card-fallback', $html);
        $this->assertEquals(4, substr_count($html, 'col-12 col-sm-6 col-xl-3'));
    }

    public function test_filter_panel_renders_all_fields_and_buttons(): void
    {
        $response = $this->actingAs($this->admin)->get('/manage/learning-hours/playlist-content-user');
        $response->assertStatus(200);

        $html = $response->getContent();

        $this->assertStringContainsString('Playlist / Training Plan', $html);
        $this->assertStringContainsString('Calculation Source', $html);
        $this->assertStringContainsString('Status', $html);
        $this->assertStringContainsString('Content Type', $html);
        $this->assertStringContainsString('Classification', $html);
        $this->assertStringContainsString('Completion Year', $html);
        $this->assertStringContainsString('Min Hours', $html);
        $this->assertStringContainsString('Max Hours', $html);

        // Buttons
        $this->assertStringContainsString('Apply Filter', $html);
        $this->assertStringContainsString('Reset', $html);
        $this->assertStringContainsString('id="btn-apply-filters"', $html);
        $this->assertStringContainsString('id="btn-reset-filters"', $html);
    }

    public function test_information_guide_and_reference_banner_render(): void
    {
        $response = $this->actingAs($this->admin)->get('/manage/learning-hours/playlist-content-user');
        $response->assertStatus(200);

        $html = $response->getContent();

        $this->assertStringContainsString('Learning Hour Information & Reference Guide', $html);
        $this->assertStringContainsString('Master Learning Hour is a reference value. Actual Learning Hour represents the calculated learning time for the learner.', $html);
        $this->assertStringContainsString('Current Time:', $html);
        $this->assertStringContainsString('Actual Learning Hour:', $html);
        $this->assertStringContainsString('Master Learning Hour:', $html);
        $this->assertStringContainsString('Duration:', $html);
        $this->assertStringContainsString('Progress:', $html);
        $this->assertStringContainsString('Calculation Source:', $html);
    }

    public function test_datatable_container_and_table_structure(): void
    {
        $response = $this->actingAs($this->admin)->get('/manage/learning-hours/playlist-content-user');
        $response->assertStatus(200);

        $html = $response->getContent();

        $this->assertStringContainsString('id="playlist-content-user-lh-table"', $html);
        $this->assertStringContainsString('class="table-responsive"', $html);
        $this->assertStringContainsString('id="btn-refresh-table"', $html);
        $this->assertStringContainsString('Refresh', $html);
    }

    public function test_regular_user_is_forbidden(): void
    {
        $response = $this->actingAs($this->regularUser)->get('/manage/learning-hours/playlist-content-user');
        $response->assertStatus(403);
    }
}
