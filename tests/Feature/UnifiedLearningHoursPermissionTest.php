<?php

namespace Tests\Feature;

use App\Http\Controllers\ManageAccessPermissionController;
use App\Models\RoleAccessPermission;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class UnifiedLearningHoursPermissionTest extends TestCase
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

    public function test_single_unified_permission_key_definition(): void
    {
        // 1. Controller modules must define exactly 'learning_hours_management'
        $response = $this->actingAs($this->superAdmin)->get('/manage/access-permission/2/edit');
        $response->assertStatus(200);
        $modules = $response->viewData('modules');

        $this->assertArrayHasKey('learning_hours_management', $modules);
        $this->assertEquals('Learning Hours Management', $modules['learning_hours_management']);

        // Must NOT have a separate playlist learning hours permission key
        $this->assertArrayNotHasKey('playlist_learning_hours_management', $modules);
        $this->assertArrayNotHasKey('playlist_learning_hours', $modules);

        // 2. Database distinct check: only one learning_hours module key
        $distinctKeys = DB::table('role_access_permissions')
            ->where('module_key', 'like', '%learning_hour%')
            ->distinct()
            ->pluck('module_key')
            ->toArray();

        $this->assertEquals(['learning_hours_management'], $distinctKeys);
    }

    public function test_user_with_permission_can_access_both_content_and_playlist_learning_hours(): void
    {
        // Grant permission to admin role
        RoleAccessPermission::updateOrCreate(
            ['role' => User::ROLE_ADMIN, 'module_key' => 'learning_hours_management'],
            ['can_access' => true]
        );

        // Content User -> 200
        $responseContent = $this->actingAs($this->admin)->get('/manage/learning-hours/content-user');
        $responseContent->assertStatus(200);

        // Playlist Content User -> 200
        $responsePlaylist = $this->actingAs($this->admin)->get('/manage/learning-hours/playlist-content-user');
        $responsePlaylist->assertStatus(200);
    }

    public function test_user_without_permission_is_forbidden_from_both_pages(): void
    {
        // Explicitly revoke permission from regular user role
        RoleAccessPermission::updateOrCreate(
            ['role' => User::ROLE_USER, 'module_key' => 'learning_hours_management'],
            ['can_access' => false]
        );

        // Content User -> 403
        $responseContent = $this->actingAs($this->regularUser)->get('/manage/learning-hours/content-user');
        $responseContent->assertStatus(403);

        // Playlist Content User -> 403
        $responsePlaylist = $this->actingAs($this->regularUser)->get('/manage/learning-hours/playlist-content-user');
        $responsePlaylist->assertStatus(403);
    }

    public function test_guest_is_redirected_to_login_for_both_pages(): void
    {
        // Content User -> redirect login
        $responseContent = $this->get('/manage/learning-hours/content-user');
        $responseContent->assertRedirect('/login');

        // Playlist Content User -> redirect login
        $responsePlaylist = $this->get('/manage/learning-hours/playlist-content-user');
        $responsePlaylist->assertRedirect('/login');
    }

    public function test_super_admin_can_access_both_pages(): void
    {
        // Content User -> 200
        $responseContent = $this->actingAs($this->superAdmin)->get('/manage/learning-hours/content-user');
        $responseContent->assertStatus(200);

        // Playlist Content User -> 200
        $responsePlaylist = $this->actingAs($this->superAdmin)->get('/manage/learning-hours/playlist-content-user');
        $responsePlaylist->assertStatus(200);
    }

    public function test_sidebar_visibility_shows_parent_and_both_submenus_only_when_permitted(): void
    {
        // 1. Authorized Admin
        RoleAccessPermission::updateOrCreate(
            ['role' => User::ROLE_ADMIN, 'module_key' => 'learning_hours_management'],
            ['can_access' => true]
        );

        $responseAuth = $this->actingAs($this->admin)->get('/manage/learning-hours/playlist-content-user');
        $responseAuth->assertStatus(200);

        $authContent = $responseAuth->getContent();
        $this->assertStringContainsString('Learning Hours', $authContent);
        $this->assertStringContainsString(route('manage.learning-hours.content-user.index'), $authContent);
        $this->assertStringContainsString(route('manage.learning-hours.playlist-content-user.index'), $authContent);

        // 2. Unauthorized Regular User
        RoleAccessPermission::updateOrCreate(
            ['role' => User::ROLE_USER, 'module_key' => 'learning_hours_management'],
            ['can_access' => false]
        );

        $responseUnauth = $this->actingAs($this->regularUser)->get('/');
        $responseUnauth->assertStatus(200);

        $unauthContent = $responseUnauth->getContent();
        $this->assertStringNotContainsString(route('manage.learning-hours.content-user.index'), $unauthContent);
        $this->assertStringNotContainsString(route('manage.learning-hours.playlist-content-user.index'), $unauthContent);
    }

    public function test_sidebar_active_state_behavior(): void
    {
        RoleAccessPermission::updateOrCreate(
            ['role' => $this->admin->role, 'module_key' => 'learning_hours_management'],
            ['can_access' => true]
        );

        // Case A: On Content User page
        $respA = $this->actingAs($this->admin)->get('/manage/learning-hours/content-user');
        $respA->assertStatus(200);
        $contentA = $respA->getContent();

        // Parent active
        $this->assertStringContainsString('nav-item dropdown active', $contentA);
        // Content User active
        $this->assertMatchesRegularExpression('/<a class="dropdown-item active"[^>]*href="[^"]*learning-hours\/content-user"/', $contentA);
        // Playlist NOT active
        $this->assertDoesNotMatchRegularExpression('/<a class="dropdown-item active"[^>]*href="[^"]*learning-hours\/playlist-content-user"/', $contentA);

        // Case B: On Playlist Content User page
        $respB = $this->actingAs($this->admin)->get('/manage/learning-hours/playlist-content-user');
        $respB->assertStatus(200);
        $contentB = $respB->getContent();

        // Parent active
        $this->assertStringContainsString('nav-item dropdown active', $contentB);
        // Playlist active
        $this->assertMatchesRegularExpression('/<a class="dropdown-item active"[^>]*href="[^"]*learning-hours\/playlist-content-user"/', $contentB);
        // Content User NOT active
        $this->assertDoesNotMatchRegularExpression('/<a class="dropdown-item active"[^>]*href="[^"]*learning-hours\/content-user"/', $contentB);
    }

    public function test_access_permission_edit_page_displays_single_checkbox(): void
    {
        $response = $this->actingAs($this->superAdmin)->get('/manage/access-permission/2/edit');
        $response->assertStatus(200);

        $content = $response->getContent();

        // Must display 'Learning Hours Management'
        $response->assertSee('Learning Hours Management');
        $response->assertSee('learning_hours_management');

        // Check occurrence count of the checkbox in HTML
        $count = substr_count($content, 'value="learning_hours_management"');
        $this->assertEquals(1, $count, 'Checkbox for learning_hours_management must appear exactly once.');

        // Must NOT display any separate playlist checkbox
        $this->assertStringNotContainsString('value="playlist_learning_hours"', $content);
        $this->assertStringNotContainsString('value="playlist_learning_hours_management"', $content);
    }

    public function test_dashboard_displays_single_merged_learning_hour_management_card(): void
    {
        // 1. Admin with permission
        RoleAccessPermission::updateOrCreate(
            ['role' => User::ROLE_ADMIN, 'module_key' => 'learning_hours_management'],
            ['can_access' => true]
        );

        $responseAdmin = $this->actingAs($this->admin)->get('/manage');
        $responseAdmin->assertStatus(200);

        $adminHtml = $responseAdmin->getContent();

        // Must see merged card title, description, and CTA
        $this->assertStringContainsString('Learning Hour Management', $adminHtml);
        $this->assertStringContainsString('Manage and monitor learning hours for Content User and Playlist Content User records.', $adminHtml);
        $this->assertStringContainsString('Manage Learning Hours', $adminHtml);

        // Must NOT see the old separate CTAs or titles
        $this->assertStringNotContainsString('Manage Content Users', $adminHtml);
        $this->assertStringNotContainsString('Manage Playlist Records', $adminHtml);
        $this->assertStringNotContainsString('Learning Hour Management (Content User)', $adminHtml);
        $this->assertStringNotContainsString('Playlist Content User Learning Hours', $adminHtml);

        // Ensure exactly ONE 'Manage Learning Hours' CTA button appears on dashboard
        $this->assertEquals(1, substr_count($adminHtml, 'Manage Learning Hours'));

        // 2. Super Admin also sees the merged card
        $responseSuper = $this->actingAs($this->superAdmin)->get('/manage');
        $responseSuper->assertStatus(200);
        $superHtml = $responseSuper->getContent();
        $this->assertStringContainsString('Learning Hour Management', $superHtml);
        $this->assertStringContainsString('Manage Learning Hours', $superHtml);

        // 3. User without permission does NOT see the card
        RoleAccessPermission::updateOrCreate(
            ['role' => User::ROLE_USER, 'module_key' => 'learning_hours_management'],
            ['can_access' => false]
        );

        // Even if user visits dashboard or home, card is not visible
        $responseUser = $this->actingAs($this->regularUser)->get('/manage');
        if ($responseUser->getStatusCode() === 200) {
            $userHtml = $responseUser->getContent();
            $this->assertStringNotContainsString('Learning Hour Management', $userHtml);
            $this->assertStringNotContainsString('Manage Learning Hours', $userHtml);
        }
    }
}
