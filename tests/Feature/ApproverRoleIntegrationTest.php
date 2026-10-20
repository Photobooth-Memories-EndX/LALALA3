<?php

namespace Tests\Feature;

use App\Models\RoleAccessPermission;
use App\Models\User;
use Database\Seeders\ApproverRoleSeeder;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Tests\TestCase;

class ApproverRoleIntegrationTest extends TestCase
{
    use DatabaseTransactions;

    private User $superAdmin;
    private User $regularUser;
    private User $approverUser;

    protected function setUp(): void
    {
        parent::setUp();

        $this->superAdmin = $this->createTestUser(User::ROLE_SUPER_ADMIN, 'Super Admin');
        $this->regularUser = $this->createTestUser(User::ROLE_USER, 'Regular User');
        $this->approverUser = $this->createTestUser(User::ROLE_APPROVER, 'Approver User');
    }

    private function createTestUser(int $role, string $namePrefix): User
    {
        return User::factory()->create([
            'role' => $role,
            'name' => $namePrefix . ' ' . Str::random(5),
            'email' => 'test_' . Str::random(8) . '@example.com',
            'team' => 'Training Team',
            'department' => 'HR',
            'division' => 'Human Resources & General Affairs Division',
            'ssohash' => 'test-sso-' . Str::uuid(),
            'sap_code' => 'SAP-' . Str::upper(Str::random(8)),
            'id_org_unit' => 'ORG05',
        ]);
    }

    /**
     * A. SEEDER TESTS
     */
    public function test_approver_seeder_initializes_permissions_safely_and_idempotently(): void
    {
        // 1. Initial count per role for roles 1-4
        $initialRole1Count = RoleAccessPermission::where('role', '1')->count();
        $initialRole2Count = RoleAccessPermission::where('role', '2')->count();
        $initialRole3Count = RoleAccessPermission::where('role', '3')->count();
        $initialRole4Count = RoleAccessPermission::where('role', '4')->count();

        // 2. Run seeder
        $seeder = new ApproverRoleSeeder();
        $seeder->run();

        // Role 5 permissions must exist
        $role5Count = RoleAccessPermission::where('role', '5')->count();
        $this->assertGreaterThan(0, $role5Count);

        // Re-run seeder (idempotency check)
        $seeder->run();
        $role5CountAfter = RoleAccessPermission::where('role', '5')->count();
        $this->assertEquals($role5Count, $role5CountAfter);

        // Verify roles 1-4 remained identical and untouched
        $this->assertEquals($initialRole1Count, RoleAccessPermission::where('role', '1')->count());
        $this->assertEquals($initialRole2Count, RoleAccessPermission::where('role', '2')->count());
        $this->assertEquals($initialRole3Count, RoleAccessPermission::where('role', '3')->count());
        $this->assertEquals($initialRole4Count, RoleAccessPermission::where('role', '4')->count());
    }

    public function test_approver_seeder_does_not_reset_customized_admin_permissions(): void
    {
        RoleAccessPermission::updateOrCreate(
            ['role' => '5', 'module_key' => 'learning_management'],
            ['can_access' => true]
        );

        $seeder = new ApproverRoleSeeder();
        $seeder->run();

        $perm = RoleAccessPermission::where('role', '5')
            ->where('module_key', 'learning_management')
            ->first();

        $this->assertNotNull($perm);
        $this->assertTrue((bool) $perm->can_access);
    }

    /**
     * B. ROLE MANAGEMENT TESTS
     */
    public function test_manage_role_page_displays_approver_in_dropdown_and_table(): void
    {
        $response = $this->actingAs($this->superAdmin)
            ->get(route('manage.role.index'));

        $response->assertOk();

        // Dropdown displays all 5 roles in correct sequence
        $response->assertSeeInOrder([
            '<option value="1"',
            'User',
            '<option value="4"',
            'Sub Admin',
            '<option value="2"',
            'Administrator',
            '<option value="3"',
            'Super Admin',
            '<option value="5"',
            'Approver',
        ], false);

        // Table displays Approver badge for approver user
        $response->assertSee('Approver');
    }

    public function test_super_admin_can_assign_approver_role_without_altering_other_user_data(): void
    {
        $initialDivision = $this->regularUser->division;
        $initialDepartment = $this->regularUser->department;
        $initialTeam = $this->regularUser->team;
        $initialOrgUnit = $this->regularUser->id_org_unit;

        $response = $this->actingAs($this->superAdmin)
            ->put(route('manage.role.update', $this->regularUser->id), [
                'role' => User::ROLE_APPROVER,
                'division' => $initialDivision,
                'department' => $initialDepartment,
                'team' => $initialTeam,
                'id_org_unit' => $initialOrgUnit,
            ]);

        $response->assertRedirect(route('manage.role.index'));
        $response->assertSessionHas('success');

        $this->regularUser->refresh();
        $this->assertEquals(User::ROLE_APPROVER, (int) $this->regularUser->role);
        $this->assertEquals('Approver', $this->regularUser->role_name);
        $this->assertTrue($this->regularUser->isApprover());

        // Organization data preserved intact
        $this->assertEquals($initialDivision, $this->regularUser->division);
        $this->assertEquals($initialDepartment, $this->regularUser->department);
        $this->assertEquals($initialTeam, $this->regularUser->team);
        $this->assertEquals($initialOrgUnit, $this->regularUser->id_org_unit);
    }

    /**
     * C. ACCESS PERMISSION TESTS
     */
    public function test_access_permission_index_displays_approver_as_fifth_row_with_badge(): void
    {
        $response = $this->actingAs($this->superAdmin)
            ->get(route('manage.access-permission.index'));

        $response->assertOk();
        $response->assertSee('Training Approval');
        $response->assertSee('Approver');
        $response->assertSee(route('manage.access-permission.edit', User::ROLE_APPROVER));
    }

    public function test_access_permission_edit_page_for_role_five_can_be_viewed_and_updated(): void
    {
        $editResponse = $this->actingAs($this->superAdmin)
            ->get(route('manage.access-permission.edit', User::ROLE_APPROVER));

        $editResponse->assertOk();
        $editResponse->assertSee('Edit Access Permission');
        $editResponse->assertSee('Role 5');
        $editResponse->assertSee('Approver');

        // Update permissions for Approver
        $updateResponse = $this->actingAs($this->superAdmin)
            ->put(route('manage.access-permission.update', User::ROLE_APPROVER), [
                'permissions' => ['record_management'],
            ]);

        $updateResponse->assertRedirect(route('manage.access-permission.index'));

        // Verify changes are stored in database
        $recordPerm = RoleAccessPermission::where('role', User::ROLE_APPROVER)
            ->where('module_key', 'record_management')
            ->first();
        $userPerm = RoleAccessPermission::where('role', User::ROLE_APPROVER)
            ->where('module_key', 'user_management')
            ->first();

        $this->assertNotNull($recordPerm);
        $this->assertTrue((bool) $recordPerm->can_access);
        $this->assertNotNull($userPerm);
        $this->assertFalse((bool) $userPerm->can_access);
    }

    /**
     * D. AUTHORIZATION & SECURITY TESTS
     */
    public function test_approver_cannot_access_manage_role_or_access_permission(): void
    {
        $this->actingAs($this->approverUser)
            ->get(route('manage.role.index'))
            ->assertForbidden();

        $this->actingAs($this->approverUser)
            ->put(route('manage.role.update', $this->regularUser->id), [
                'role' => User::ROLE_SUPER_ADMIN,
            ])
            ->assertForbidden();

        $this->actingAs($this->approverUser)
            ->get(route('manage.access-permission.index'))
            ->assertForbidden();

        $this->actingAs($this->approverUser)
            ->get(route('manage.access-permission.edit', User::ROLE_APPROVER))
            ->assertForbidden();

        $this->actingAs($this->approverUser)
            ->put(route('manage.access-permission.update', User::ROLE_APPROVER), [])
            ->assertForbidden();
    }

    public function test_approver_cannot_access_manage_dashboard_or_user_management(): void
    {
        // Approver cannot access Manage LMS Dashboard (requires role 2, 3, or 4)
        $this->actingAs($this->approverUser)
            ->get(route('manage.dashboard'))
            ->assertForbidden();

        // Approver cannot access User Management module
        $this->actingAs($this->approverUser)
            ->get(route('user.index'))
            ->assertForbidden();
    }

    public function test_approver_can_access_my_learning_and_public_features(): void
    {
        // My Learning is accessible
        $response = $this->actingAs($this->approverUser)
            ->get(route('user.learning.dashboard'));

        $response->assertOk();

        // Home is accessible
        $this->actingAs($this->approverUser)
            ->get('/')
            ->assertOk();
    }

    public function test_user_model_role_helper_methods_for_approver(): void
    {
        $this->assertTrue($this->approverUser->isApprover());
        $this->assertFalse($this->approverUser->isAdmin());
        $this->assertFalse($this->approverUser->isSuperAdmin());
        $this->assertFalse($this->approverUser->isSubadminDivision());
        $this->assertFalse($this->approverUser->isUser());

        $this->assertFalse($this->approverUser->canManageLms());
        $this->assertFalse($this->approverUser->canManageRole());
        $this->assertFalse($this->approverUser->canMonitor());

        // Prepared authorization method
        $this->assertTrue($this->approverUser->canApproveTraining());
        $this->assertFalse($this->regularUser->canApproveTraining());
    }
}
