<?php

namespace Tests\Feature;

use App\Models\RoleAccessPermission;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Str;
use Tests\TestCase;

class ManageAccessPermissionTest extends TestCase
{
    use DatabaseTransactions;

    private User $superAdmin;

    protected function setUp(): void
    {
        parent::setUp();

        $this->superAdmin = $this->createTestUser(User::ROLE_SUPER_ADMIN);
    }

    private function createTestUser(int $role): User
    {
        return User::factory()->create([
            'role' => $role,
            'email' => 'test_' . Str::random(8) . '@example.com',
            'team' => 'Management',
            'department' => 'IT',
            'division' => 'Technology',
            'ssohash' => 'test-sso-' . Str::uuid(),
            'sap_code' => 'SAP-' . Str::upper(Str::random(8)),
        ]);
    }

    public function test_debugger_tools_section_displays_learning_hours_management(): void
    {
        $response = $this->actingAs($this->superAdmin)
            ->get(route('manage.access-permission.edit', User::ROLE_ADMIN));

        $response->assertOk();
        $response->assertSee('Debugger Tools');
        $response->assertSee('Learning Hours Management');
        $response->assertSee('learning_hours_management');
        $response->assertSee('Playlist Debug Tools');
        $response->assertSee('playlist_debug_tools');
    }

    public function test_role_admin_checkbox_is_checked_when_permission_is_true(): void
    {
        RoleAccessPermission::updateOrCreate(
            ['role' => User::ROLE_ADMIN, 'module_key' => 'learning_hours_management'],
            ['can_access' => true]
        );

        $response = $this->actingAs($this->superAdmin)
            ->get(route('manage.access-permission.edit', User::ROLE_ADMIN));

        $response->assertOk();
        $content = $response->getContent();
        $this->assertMatchesRegularExpression(
            '/<input[^>]*value="learning_hours_management"[^>]*checked/i',
            $content
        );
    }

    public function test_save_permission_uncheck_learning_hours_management(): void
    {
        RoleAccessPermission::updateOrCreate(
            ['role' => User::ROLE_ADMIN, 'module_key' => 'learning_hours_management'],
            ['can_access' => true]
        );

        // Submit form without learning_hours_management
        $response = $this->actingAs($this->superAdmin)
            ->put(route('manage.access-permission.update', User::ROLE_ADMIN), [
                'permissions' => [
                    'admin_management',
                    'user_management',
                    'playlist_debug_tools',
                ],
            ]);

        $response->assertRedirect(route('manage.access-permission.index'));

        $permission = RoleAccessPermission::where('role', User::ROLE_ADMIN)
            ->where('module_key', 'learning_hours_management')
            ->first();

        $this->assertNotNull($permission);
        $this->assertFalse((bool) $permission->can_access);

        // Verify edit view shows unchecked
        $editResponse = $this->actingAs($this->superAdmin)
            ->get(route('manage.access-permission.edit', User::ROLE_ADMIN));

        $editContent = $editResponse->getContent();
        $this->assertDoesNotMatchRegularExpression(
            '/<input[^>]*value="learning_hours_management"[^>]*checked/i',
            $editContent
        );
    }

    public function test_save_permission_check_learning_hours_management(): void
    {
        RoleAccessPermission::updateOrCreate(
            ['role' => User::ROLE_ADMIN, 'module_key' => 'learning_hours_management'],
            ['can_access' => false]
        );

        // Submit form with learning_hours_management
        $response = $this->actingAs($this->superAdmin)
            ->put(route('manage.access-permission.update', User::ROLE_ADMIN), [
                'permissions' => [
                    'admin_management',
                    'learning_hours_management',
                ],
            ]);

        $response->assertRedirect(route('manage.access-permission.index'));

        $permission = RoleAccessPermission::where('role', User::ROLE_ADMIN)
            ->where('module_key', 'learning_hours_management')
            ->first();

        $this->assertNotNull($permission);
        $this->assertTrue((bool) $permission->can_access);

        // Verify edit view shows checked
        $editResponse = $this->actingAs($this->superAdmin)
            ->get(route('manage.access-permission.edit', User::ROLE_ADMIN));

        $editContent = $editResponse->getContent();
        $this->assertMatchesRegularExpression(
            '/<input[^>]*value="learning_hours_management"[^>]*checked/i',
            $editContent
        );
    }

    public function test_role_user_permission_view_matches_database(): void
    {
        RoleAccessPermission::updateOrCreate(
            ['role' => User::ROLE_USER, 'module_key' => 'learning_hours_management'],
            ['can_access' => false]
        );

        $response = $this->actingAs($this->superAdmin)
            ->get(route('manage.access-permission.edit', User::ROLE_USER));

        $response->assertOk();
        $content = $response->getContent();
        $this->assertDoesNotMatchRegularExpression(
            '/<input[^>]*value="learning_hours_management"[^>]*checked/i',
            $content
        );
    }

    public function test_role_subadmin_division_permission_view_matches_database(): void
    {
        RoleAccessPermission::updateOrCreate(
            ['role' => User::ROLE_SUBADMIN_DIVISION, 'module_key' => 'learning_hours_management'],
            ['can_access' => false]
        );

        $response = $this->actingAs($this->superAdmin)
            ->get(route('manage.access-permission.edit', User::ROLE_SUBADMIN_DIVISION));

        $response->assertOk();
        $content = $response->getContent();
        $this->assertDoesNotMatchRegularExpression(
            '/<input[^>]*value="learning_hours_management"[^>]*checked/i',
            $content
        );
    }

    public function test_all_expected_permission_modules_are_present_without_regression(): void
    {
        $expectedStandardModules = [
            'admin_management' => 'Admin Management',
            'user_management' => 'User Management',
            'learning_management' => 'Learning Management',
            'content_repository' => 'Content Repository',
            'category_classification' => 'Category & Classification',
            'assessment_management' => 'Assessment Management',
            'question_bank' => 'Question Bank',
            'playlist_learning_path' => 'Playlist & Learning Path',
            'news_announcement' => 'News & Announcement',
            'page_management' => 'Page Management',
            'event_documentation' => 'Event Documentation',
            'certificate_setting' => 'Certificate Setting',
            'monitoring' => 'Monitoring',
        ];

        $expectedDebuggerModules = [
            'learning_hours_management' => 'Learning Hours Management',
            'playlist_debug_tools' => 'Playlist Debug Tools',
        ];

        $response = $this->actingAs($this->superAdmin)
            ->get(route('manage.access-permission.edit', User::ROLE_ADMIN));

        $response->assertOk();

        foreach ($expectedStandardModules as $key => $label) {
            $response->assertSee($label);
            $response->assertSee($key);
        }

        foreach ($expectedDebuggerModules as $key => $label) {
            $response->assertSee($label);
            $response->assertSee($key);
        }
    }

    public function test_learning_hours_management_authorization_via_user_model(): void
    {
        $adminUser = $this->createTestUser(User::ROLE_ADMIN);
        $regularUser = $this->createTestUser(User::ROLE_USER);

        RoleAccessPermission::updateOrCreate(
            ['role' => User::ROLE_ADMIN, 'module_key' => 'learning_hours_management'],
            ['can_access' => true]
        );

        RoleAccessPermission::updateOrCreate(
            ['role' => User::ROLE_USER, 'module_key' => 'learning_hours_management'],
            ['can_access' => false]
        );

        $this->assertTrue($adminUser->canAccessManageModule('learning_hours_management'));
        $this->assertFalse($regularUser->canAccessManageModule('learning_hours_management'));
        $this->assertTrue($this->superAdmin->canAccessManageModule('learning_hours_management'));
    }
}
