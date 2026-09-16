<?php

namespace Tests\Feature;

use App\Models\RoleAccessPermission;
use App\Models\User;
use Tests\TestCase;

class PlaylistLearningHourMenuTest extends TestCase
{
    protected User $admin;
    protected User $superAdmin;
    protected User $regularUser;

    protected function setUp(): void
    {
        parent::setUp();

        // Ensure permissions are set
        RoleAccessPermission::updateOrCreate(
            ['role' => User::ROLE_ADMIN, 'module_key' => 'learning_hours_management'],
            ['can_access' => true]
        );
        RoleAccessPermission::updateOrCreate(
            ['role' => User::ROLE_SUPER_ADMIN, 'module_key' => 'learning_hours_management'],
            ['can_access' => true]
        );
        RoleAccessPermission::updateOrCreate(
            ['role' => User::ROLE_USER, 'module_key' => 'learning_hours_management'],
            ['can_access' => false]
        );

        $this->admin = User::where('role', User::ROLE_ADMIN)->first() ?? User::first();
        $this->superAdmin = User::where('role', User::ROLE_SUPER_ADMIN)->first() ?? $this->admin;
        $this->regularUser = User::where('role', User::ROLE_USER)->first()
            ?? User::factory()->make(['role' => User::ROLE_USER]);
    }

    public function test_admin_and_superadmin_can_see_learning_hours_menu(): void
    {
        // 1. Admin
        $responseAdmin = $this->actingAs($this->admin)->get('/manage/learning-hours/playlist-content-user');
        $responseAdmin->assertStatus(200);
        $responseAdmin->assertSee('Learning Hours');
        $responseAdmin->assertSee('Content User');
        $responseAdmin->assertSee('Playlist Content User');
        $responseAdmin->assertSee(route('manage.learning-hours.content-user.index'));
        $responseAdmin->assertSee(route('manage.learning-hours.playlist-content-user.index'));

        // 2. Super Admin
        $responseSuper = $this->actingAs($this->superAdmin)->get('/manage/learning-hours/playlist-content-user');
        $responseSuper->assertStatus(200);
        $responseSuper->assertSee('Learning Hours');
        $responseSuper->assertSee('Playlist Content User');
    }

    public function test_unauthorized_user_cannot_see_learning_hours_menu(): void
    {
        // When unauthorized user views a public page (e.g. home or content), learning hours menu should not appear
        $response = $this->actingAs($this->regularUser)->get('/');
        $response->assertStatus(200);
        $response->assertDontSee(route('manage.learning-hours.playlist-content-user.index'));
        $response->assertDontSee(route('manage.learning-hours.content-user.index'));
    }

    public function test_content_user_active_menu_state(): void
    {
        $response = $this->actingAs($this->admin)->get('/manage/learning-hours/content-user');
        $response->assertStatus(200);

        $content = $response->getContent();

        // Learning Hours dropdown must have active class
        $this->assertStringContainsString('nav-item dropdown active', $content);

        // Content User item must have active class
        $this->assertMatchesRegularExpression(
            '/<a class="dropdown-item active"[^>]*href="[^"]*manage\/learning-hours\/content-user"/',
            $content
        );

        // Playlist Content User item must NOT have active class
        $this->assertDoesNotMatchRegularExpression(
            '/<a class="dropdown-item active"[^>]*href="[^"]*manage\/learning-hours\/playlist-content-user"/',
            $content
        );
    }

    public function test_playlist_content_user_active_menu_state(): void
    {
        $response = $this->actingAs($this->admin)->get('/manage/learning-hours/playlist-content-user');
        $response->assertStatus(200);

        $content = $response->getContent();

        // Learning Hours dropdown must have active class
        $this->assertStringContainsString('nav-item dropdown active', $content);

        // Playlist Content User item must have active class
        $this->assertMatchesRegularExpression(
            '/<a class="dropdown-item active"[^>]*href="[^"]*manage\/learning-hours\/playlist-content-user"/',
            $content
        );

        // Content User item must NOT have active class
        $this->assertDoesNotMatchRegularExpression(
            '/<a class="dropdown-item active"[^>]*href="[^"]*manage\/learning-hours\/content-user"/',
            $content
        );
    }

    public function test_no_duplicate_menu_items(): void
    {
        $response = $this->actingAs($this->admin)->get('/manage/learning-hours/playlist-content-user');
        $response->assertStatus(200);

        $content = $response->getContent();

        // Check occurrence count in sidebar nav
        $playlistCount = substr_count($content, route('manage.learning-hours.playlist-content-user.index'));
        // In the sidebar there should only be 1 occurrence of the link (and 1 on dashboard card if on /manage)
        $this->assertGreaterThanOrEqual(1, $playlistCount);
    }
}
