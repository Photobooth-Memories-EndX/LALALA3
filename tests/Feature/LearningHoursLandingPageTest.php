<?php

namespace Tests\Feature;

use App\Models\RoleAccessPermission;
use App\Models\User;
use Tests\TestCase;

class LearningHoursLandingPageTest extends TestCase
{
    protected User $superAdmin;
    protected User $admin;
    protected User $regularUser;

    protected function setUp(): void
    {
        parent::setUp();

        $this->superAdmin = User::where('role', User::ROLE_SUPER_ADMIN)->first() ?? User::factory()->make(['role' => User::ROLE_SUPER_ADMIN]);
        $this->admin = User::where('role', User::ROLE_ADMIN)->first() ?? User::factory()->make(['role' => User::ROLE_ADMIN]);
        $this->regularUser = User::where('role', User::ROLE_USER)->first() ?? User::factory()->make(['role' => User::ROLE_USER]);
    }

    public function test_parent_page_authorized_returns_200(): void
    {
        RoleAccessPermission::updateOrCreate(
            ['role' => User::ROLE_ADMIN, 'module_key' => 'learning_hours_management'],
            ['can_access' => true]
        );

        $response = $this->actingAs($this->admin)->get('/manage/learning-hours');
        $response->assertStatus(200);
        $response->assertSee('Learning Hour Management');
        $response->assertSee('Manage learning hours for Content User and Playlist Content User records.');
    }

    public function test_parent_page_unauthorized_returns_403(): void
    {
        RoleAccessPermission::updateOrCreate(
            ['role' => User::ROLE_USER, 'module_key' => 'learning_hours_management'],
            ['can_access' => false]
        );

        $response = $this->actingAs($this->regularUser)->get('/manage/learning-hours');
        $response->assertStatus(403);
    }

    public function test_parent_page_guest_redirects_to_login(): void
    {
        $response = $this->get('/manage/learning-hours');
        $response->assertRedirect('/login');
    }

    public function test_parent_page_super_admin_access(): void
    {
        $response = $this->actingAs($this->superAdmin)->get('/manage/learning-hours');
        $response->assertStatus(200);
    }

    public function test_dashboard_card_links_to_parent_landing_page(): void
    {
        RoleAccessPermission::updateOrCreate(
            ['role' => User::ROLE_ADMIN, 'module_key' => 'learning_hours_management'],
            ['can_access' => true]
        );

        $response = $this->actingAs($this->admin)->get('/manage');
        $response->assertStatus(200);

        // CTA must point to /manage/learning-hours, NOT /manage/learning-hours/content-user
        $response->assertSee(route('manage.learning-hours.index'));
        $this->assertStringContainsString('href="' . route('manage.learning-hours.index') . '"', $response->getContent());
    }

    public function test_landing_page_contains_content_user_link(): void
    {
        RoleAccessPermission::updateOrCreate(
            ['role' => User::ROLE_ADMIN, 'module_key' => 'learning_hours_management'],
            ['can_access' => true]
        );

        $response = $this->actingAs($this->admin)->get('/manage/learning-hours');
        $response->assertStatus(200);
        $response->assertSee(route('manage.learning-hours.content-user.index'));
    }

    public function test_landing_page_contains_playlist_content_user_link(): void
    {
        RoleAccessPermission::updateOrCreate(
            ['role' => User::ROLE_ADMIN, 'module_key' => 'learning_hours_management'],
            ['can_access' => true]
        );

        $response = $this->actingAs($this->admin)->get('/manage/learning-hours');
        $response->assertStatus(200);
        $response->assertSee(route('manage.learning-hours.playlist-content-user.index'));
    }

    public function test_landing_page_displays_card_titles_and_cta_texts(): void
    {
        RoleAccessPermission::updateOrCreate(
            ['role' => User::ROLE_ADMIN, 'module_key' => 'learning_hours_management'],
            ['can_access' => true]
        );

        $response = $this->actingAs($this->admin)->get('/manage/learning-hours');
        $response->assertStatus(200);

        $html = $response->getContent();

        // Card 1
        $this->assertStringContainsString('Content User Learning Hours', $html);
        $this->assertStringContainsString('Manage and calculate learning hours for Content User records.', $html);
        $this->assertStringContainsString('Manage Content Users', $html);

        // Card 2
        $this->assertStringContainsString('Playlist Content User Learning Hours', $html);
        $this->assertStringContainsString('Manage and inspect actual learning hours for Playlist Content User records.', $html);
        $this->assertStringContainsString('Manage Playlist Records', $html);
    }

    public function test_dashboard_has_no_duplicate_learning_hour_cards(): void
    {
        RoleAccessPermission::updateOrCreate(
            ['role' => User::ROLE_ADMIN, 'module_key' => 'learning_hours_management'],
            ['can_access' => true]
        );

        $response = $this->actingAs($this->admin)->get('/manage');
        $response->assertStatus(200);

        $html = $response->getContent();

        // Must have exactly 1 'Manage Learning Hours' CTA button on dashboard
        $this->assertEquals(1, substr_count($html, 'Manage Learning Hours'));

        // Child card titles must NOT appear on the dashboard level
        $this->assertStringNotContainsString('Manage Content Users', $html);
        $this->assertStringNotContainsString('Manage Playlist Records', $html);
    }

    public function test_sidebar_active_state_on_landing_page(): void
    {
        RoleAccessPermission::updateOrCreate(
            ['role' => User::ROLE_ADMIN, 'module_key' => 'learning_hours_management'],
            ['can_access' => true]
        );

        $response = $this->actingAs($this->admin)->get('/manage/learning-hours');
        $response->assertStatus(200);

        $html = $response->getContent();

        // Parent dropdown active
        $this->assertStringContainsString('nav-item dropdown active', $html);

        // Neither child should be active on the landing page
        $this->assertDoesNotMatchRegularExpression('/<a class="dropdown-item active"[^>]*href="[^"]*learning-hours\/content-user"/', $html);
        $this->assertDoesNotMatchRegularExpression('/<a class="dropdown-item active"[^>]*href="[^"]*learning-hours\/playlist-content-user"/', $html);
    }
}
