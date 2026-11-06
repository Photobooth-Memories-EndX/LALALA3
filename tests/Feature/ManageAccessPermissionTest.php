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
            'learning_engagement_time' => 'Learning Engagement Time',
            'learning_management' => 'Learning Management',
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

        RoleAccessPermission::updateOrCreate(
            ['role' => User::ROLE_SUPER_ADMIN, 'module_key' => 'learning_hours_management'],
            ['can_access' => true]
        );

        $this->assertTrue($adminUser->canAccessManageModule('learning_hours_management'));
        $this->assertFalse($regularUser->canAccessManageModule('learning_hours_management'));
        $this->assertTrue($this->superAdmin->canAccessManageModule('learning_hours_management'));
    }

    public function test_learning_engagement_time_authorization_via_user_model(): void
    {
        $adminUser = $this->createTestUser(User::ROLE_ADMIN);
        $regularUser = $this->createTestUser(User::ROLE_USER);

        RoleAccessPermission::updateOrCreate(
            ['role' => User::ROLE_ADMIN, 'module_key' => 'learning_engagement_time'],
            ['can_access' => true]
        );

        RoleAccessPermission::updateOrCreate(
            ['role' => User::ROLE_USER, 'module_key' => 'learning_engagement_time'],
            ['can_access' => false]
        );

        RoleAccessPermission::updateOrCreate(
            ['role' => User::ROLE_SUPER_ADMIN, 'module_key' => 'learning_engagement_time'],
            ['can_access' => true]
        );

        $this->assertTrue($adminUser->canAccessManageModule('learning_engagement_time'));
        $this->assertFalse($regularUser->canAccessManageModule('learning_engagement_time'));
        $this->assertTrue($this->superAdmin->canAccessManageModule('learning_engagement_time'));
    }

    public function test_super_admin_can_access_index_and_sees_atur_permission_for_all_roles(): void
    {
        $response = $this->actingAs($this->superAdmin)
            ->get(route('manage.access-permission.index'));

        $response->assertOk();
        $response->assertSee('Manage Access Permission');
        $response->assertDontSee('Tidak perlu diatur');

        foreach (User::roles() as $roleId => $roleName) {
            $response->assertSee(route('manage.access-permission.edit', $roleId));
            $response->assertSee($roleName);
        }
    }

    public function test_access_permission_index_displays_roles_in_correct_order(): void
    {
        $response = $this->actingAs($this->superAdmin)
            ->get(route('manage.access-permission.index'));

        $response->assertOk();

        // Assert exact sequence: User (Role 1) -> Sub Admin (Role 4) -> Administrator (Role 2) -> Super Admin (Role 3) -> Approver (Role 5)
        $response->assertSeeInOrder([
            'User',
            'Learning User',
            route('manage.access-permission.edit', User::ROLE_USER),
            'Sub Admin',
            'Unit Monitoring',
            route('manage.access-permission.edit', User::ROLE_SUBADMIN_DIVISION),
            'Administrator',
            'Manage LMS Default',
            route('manage.access-permission.edit', User::ROLE_ADMIN),
            'Super Admin',
            'Full Access',
            route('manage.access-permission.edit', User::ROLE_SUPER_ADMIN),
            'Approver',
            'Training Approval',
            route('manage.access-permission.edit', User::ROLE_APPROVER),
        ]);
    }

    public function test_super_admin_can_access_edit_for_super_admin_role(): void
    {
        $response = $this->actingAs($this->superAdmin)
            ->get(route('manage.access-permission.edit', User::ROLE_SUPER_ADMIN));

        $response->assertOk();
        $response->assertSee('Edit Access Permission');
        $response->assertSee('Role 3');
        $response->assertSee('Super Admin');
        $response->assertSee('Configure Manage LMS modules for the <strong>Super Admin</strong> role.', false);
        $response->assertSee('Save Permissions');
    }

    public function test_super_admin_can_update_super_admin_role_permissions(): void
    {
        $response = $this->actingAs($this->superAdmin)
            ->put(route('manage.access-permission.update', User::ROLE_SUPER_ADMIN), [
                'permissions' => [
                    'admin_management',
                    'learning_hours_management',
                ],
            ]);

        $response->assertRedirect(route('manage.access-permission.index'));
        $response->assertSessionHas('success', 'Access permission role berhasil diperbarui.');

        $adminMgmt = RoleAccessPermission::where('role', User::ROLE_SUPER_ADMIN)
            ->where('module_key', 'admin_management')
            ->first();
        $contentRepo = RoleAccessPermission::where('role', User::ROLE_SUPER_ADMIN)
            ->where('module_key', 'content_repository')
            ->first();

        $this->assertNotNull($adminMgmt);
        $this->assertTrue((bool) $adminMgmt->can_access);
        $this->assertNotNull($contentRepo);
        $this->assertFalse((bool) $contentRepo->can_access);
    }

    public function test_super_admin_module_authorization_reflects_saved_role_access_permissions(): void
    {
        $superAdminUser = $this->createTestUser(User::ROLE_SUPER_ADMIN);

        RoleAccessPermission::updateOrCreate(
            ['role' => User::ROLE_SUPER_ADMIN, 'module_key' => 'content_repository'],
            ['can_access' => false]
        );

        RoleAccessPermission::updateOrCreate(
            ['role' => User::ROLE_SUPER_ADMIN, 'module_key' => 'admin_management'],
            ['can_access' => true]
        );

        // When explicitly false, canAccessManageModule must return false
        $this->assertFalse($superAdminUser->canAccessManageModule('content_repository'));

        // When explicitly true, canAccessManageModule must return true
        $this->assertTrue($superAdminUser->canAccessManageModule('admin_management'));

        // When not set in DB for unconfigured modules, fallback is true for Super Admin
        $this->assertTrue($superAdminUser->canAccessManageModule('non_configured_module_key'));
    }

    public function test_super_admin_is_not_locked_out_when_all_lms_modules_are_disabled(): void
    {
        $modules = [
            'admin_management', 'user_management', 'learning_management',
            'content_repository', 'category_classification', 'assessment_management',
            'question_bank', 'playlist_learning_path', 'learning_hours_management',
            'playlist_debug_tools', 'news_announcement', 'page_management',
            'event_documentation', 'certificate_setting', 'monitoring',
        ];

        foreach ($modules as $module) {
            RoleAccessPermission::updateOrCreate(
                ['role' => User::ROLE_SUPER_ADMIN, 'module_key' => $module],
                ['can_access' => false]
            );
        }

        // Super Admin can still access permission management index & edit
        $indexResponse = $this->actingAs($this->superAdmin)
            ->get(route('manage.access-permission.index'));
        $indexResponse->assertOk();

        $editResponse = $this->actingAs($this->superAdmin)
            ->get(route('manage.access-permission.edit', User::ROLE_SUPER_ADMIN));
        $editResponse->assertOk();

        // Super Admin can restore permissions
        $updateResponse = $this->actingAs($this->superAdmin)
            ->put(route('manage.access-permission.update', User::ROLE_SUPER_ADMIN), [
                'permissions' => ['content_repository'],
            ]);
        $updateResponse->assertRedirect(route('manage.access-permission.index'));

        $this->assertTrue($this->superAdmin->canAccessManageModule('content_repository'));
    }

    public function test_unauthorized_users_cannot_access_or_update_permissions(): void
    {
        $admin = $this->createTestUser(User::ROLE_ADMIN);
        $user = $this->createTestUser(User::ROLE_USER);
        $subadmin = $this->createTestUser(User::ROLE_SUBADMIN_DIVISION);
        $approver = $this->createTestUser(User::ROLE_APPROVER);

        // Guest redirect
        $this->get(route('manage.access-permission.index'))
            ->assertRedirect(route('loginpage'));

        // Admin (2) forbidden
        $this->actingAs($admin)->get(route('manage.access-permission.index'))->assertForbidden();
        $this->actingAs($admin)->get(route('manage.access-permission.edit', User::ROLE_ADMIN))->assertForbidden();
        $this->actingAs($admin)->put(route('manage.access-permission.update', User::ROLE_ADMIN), [])->assertForbidden();

        // User (1) forbidden
        $this->actingAs($user)->get(route('manage.access-permission.index'))->assertForbidden();
        $this->actingAs($user)->get(route('manage.access-permission.edit', User::ROLE_USER))->assertForbidden();
        $this->actingAs($user)->put(route('manage.access-permission.update', User::ROLE_USER), [])->assertForbidden();

        // Subadmin (4) forbidden
        $this->actingAs($subadmin)->get(route('manage.access-permission.index'))->assertForbidden();
        $this->actingAs($subadmin)->get(route('manage.access-permission.edit', User::ROLE_SUBADMIN_DIVISION))->assertForbidden();
        $this->actingAs($subadmin)->put(route('manage.access-permission.update', User::ROLE_SUBADMIN_DIVISION), [])->assertForbidden();

        // Approver (5) forbidden
        $this->actingAs($approver)->get(route('manage.access-permission.index'))->assertForbidden();
        $this->actingAs($approver)->get(route('manage.access-permission.edit', User::ROLE_APPROVER))->assertForbidden();
        $this->actingAs($approver)->put(route('manage.access-permission.update', User::ROLE_APPROVER), [])->assertForbidden();
    }

    public function test_saving_super_admin_permissions_does_not_affect_other_roles(): void
    {
        RoleAccessPermission::updateOrCreate(
            ['role' => User::ROLE_ADMIN, 'module_key' => 'content_repository'],
            ['can_access' => true]
        );

        // Update Super Admin to have content_repository = false
        $this->actingAs($this->superAdmin)
            ->put(route('manage.access-permission.update', User::ROLE_SUPER_ADMIN), [
                'permissions' => ['admin_management'],
            ]);

        $adminPermission = RoleAccessPermission::where('role', User::ROLE_ADMIN)
            ->where('module_key', 'content_repository')
            ->first();

        $superAdminPermission = RoleAccessPermission::where('role', User::ROLE_SUPER_ADMIN)
            ->where('module_key', 'content_repository')
            ->first();

        $this->assertTrue((bool) $adminPermission->can_access);
        $this->assertFalse((bool) $superAdminPermission->can_access);
    }

    public function test_super_admin_can_access_edit_for_approver_role(): void
    {
        $response = $this->actingAs($this->superAdmin)
            ->get(route('manage.access-permission.edit', User::ROLE_APPROVER));

        $response->assertOk();
        $response->assertSee('Edit Access Permission');
        $response->assertSee('Role 5');
        $response->assertSee('Approver');
        $response->assertSee('Configure Manage LMS modules for the <strong>Approver</strong> role.', false);
        $response->assertSee('Save Permissions');
    }

    public function test_super_admin_can_update_approver_role_permissions(): void
    {
        $response = $this->actingAs($this->superAdmin)
            ->put(route('manage.access-permission.update', User::ROLE_APPROVER), [
                'permissions' => [
                    'learning_management',
                ],
            ]);

        $response->assertRedirect(route('manage.access-permission.index'));
        $response->assertSessionHas('success', 'Access permission role berhasil diperbarui.');

        $learningMgmt = RoleAccessPermission::where('role', User::ROLE_APPROVER)
            ->where('module_key', 'learning_management')
            ->first();
        $adminMgmt = RoleAccessPermission::where('role', User::ROLE_APPROVER)
            ->where('module_key', 'admin_management')
            ->first();

        $this->assertNotNull($learningMgmt);
        $this->assertTrue((bool) $learningMgmt->can_access);
        $this->assertNotNull($adminMgmt);
        $this->assertFalse((bool) $adminMgmt->can_access);
    }

    public function test_approver_role_seeder_is_idempotent_and_does_not_reset_customized_permissions(): void
    {
        // Explicitly set a permission to true
        RoleAccessPermission::updateOrCreate(
            ['role' => User::ROLE_APPROVER, 'module_key' => 'monitoring'],
            ['can_access' => true]
        );

        // Run ApproverRoleSeeder
        $seeder = new \Database\Seeders\ApproverRoleSeeder();
        $seeder->run();

        // The customized permission MUST remain true (not overwritten)
        $monitoringPerm = RoleAccessPermission::where('role', User::ROLE_APPROVER)
            ->where('module_key', 'monitoring')
            ->first();

        $this->assertNotNull($monitoringPerm);
        $this->assertTrue((bool) $monitoringPerm->can_access);
    }
}
