<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Str;
use Tests\TestCase;

class ManageRoleTest extends TestCase
{
    use DatabaseTransactions;

    private User $superAdmin;
    private User $targetUser;

    protected function setUp(): void
    {
        parent::setUp();

        $this->superAdmin = $this->createTestUser(User::ROLE_SUPER_ADMIN);
        $this->targetUser = $this->createTestUser(User::ROLE_USER);
    }

    private function createTestUser(int $role): User
    {
        return User::factory()->create([
            'role' => $role,
            'name' => 'User ' . Str::random(5),
            'email' => 'test_' . Str::random(8) . '@example.com',
            'team' => 'Management',
            'department' => 'IT',
            'division' => 'Technology',
            'ssohash' => 'test-sso-' . Str::uuid(),
            'sap_code' => 'SAP-' . Str::upper(Str::random(8)),
            'id_org_unit' => 'ORG01',
        ]);
    }

    public function test_role_dropdown_contains_all_four_roles_in_exact_order(): void
    {
        $response = $this->actingAs($this->superAdmin)
            ->get(route('manage.role.index'));

        $response->assertOk();
        $response->assertSee('Edit User Organization');

        // Verify exact order in dropdown: User (1) -> Sub Admin (4) -> Administrator (2) -> Super Admin (3)
        $response->assertSeeInOrder([
            '<option value="1"',
            'User',
            '<option value="4"',
            'Sub Admin',
            '<option value="2"',
            'Administrator',
            '<option value="3"',
            'Super Admin',
        ], false);
    }

    public function test_authorized_super_admin_can_assign_super_admin_role(): void
    {
        $response = $this->actingAs($this->superAdmin)
            ->put(route('manage.role.update', $this->targetUser->id), [
                'role' => User::ROLE_SUPER_ADMIN,
                'division' => 'Technology',
                'department' => 'IT',
                'team' => 'Management',
                'id_org_unit' => 'ORG01',
            ]);

        $response->assertRedirect(route('manage.role.index'));
        $response->assertSessionHas('success');

        $this->targetUser->refresh();
        $this->assertEquals(User::ROLE_SUPER_ADMIN, (int) $this->targetUser->role);
        $this->assertTrue($this->targetUser->isSuperAdmin());
    }

    public function test_authorized_super_admin_can_assign_other_roles(): void
    {
        // Test assign Administrator (Role 2)
        $responseAdmin = $this->actingAs($this->superAdmin)
            ->put(route('manage.role.update', $this->targetUser->id), [
                'role' => User::ROLE_ADMIN,
                'division' => 'Technology',
                'department' => 'IT',
                'team' => 'Management',
            ]);
        $responseAdmin->assertRedirect(route('manage.role.index'));
        $this->assertEquals(User::ROLE_ADMIN, (int) $this->targetUser->fresh()->role);

        // Test assign Subadmin Division (Role 4)
        $responseSubadmin = $this->actingAs($this->superAdmin)
            ->put(route('manage.role.update', $this->targetUser->id), [
                'role' => User::ROLE_SUBADMIN_DIVISION,
                'division' => 'Technology',
                'department' => 'IT',
                'team' => 'Management',
            ]);
        $responseSubadmin->assertRedirect(route('manage.role.index'));
        $this->assertEquals(User::ROLE_SUBADMIN_DIVISION, (int) $this->targetUser->fresh()->role);

        // Test assign User (Role 1)
        $responseUser = $this->actingAs($this->superAdmin)
            ->put(route('manage.role.update', $this->targetUser->id), [
                'role' => User::ROLE_USER,
                'division' => 'Technology',
                'department' => 'IT',
                'team' => 'Management',
            ]);
        $responseUser->assertRedirect(route('manage.role.index'));
        $this->assertEquals(User::ROLE_USER, (int) $this->targetUser->fresh()->role);
    }

    public function test_unauthorized_users_cannot_access_or_update_roles(): void
    {
        $admin = $this->createTestUser(User::ROLE_ADMIN);
        $user = $this->createTestUser(User::ROLE_USER);
        $subadmin = $this->createTestUser(User::ROLE_SUBADMIN_DIVISION);

        // Guest redirect
        $this->get(route('manage.role.index'))
            ->assertRedirect(route('loginpage'));
        $this->put(route('manage.role.update', $this->targetUser->id), ['role' => User::ROLE_SUPER_ADMIN])
            ->assertRedirect(route('loginpage'));

        // Admin (2) forbidden
        $this->actingAs($admin)->get(route('manage.role.index'))->assertForbidden();
        $this->actingAs($admin)->put(route('manage.role.update', $this->targetUser->id), [
            'role' => User::ROLE_SUPER_ADMIN,
        ])->assertForbidden();

        // User (1) forbidden
        $this->actingAs($user)->get(route('manage.role.index'))->assertForbidden();
        $this->actingAs($user)->put(route('manage.role.update', $this->targetUser->id), [
            'role' => User::ROLE_SUPER_ADMIN,
        ])->assertForbidden();

        // Subadmin Division (4) forbidden
        $this->actingAs($subadmin)->get(route('manage.role.index'))->assertForbidden();
        $this->actingAs($subadmin)->put(route('manage.role.update', $this->targetUser->id), [
            'role' => User::ROLE_SUPER_ADMIN,
        ])->assertForbidden();
    }

    public function test_super_admin_cannot_edit_own_account(): void
    {
        $response = $this->actingAs($this->superAdmin)
            ->put(route('manage.role.update', $this->superAdmin->id), [
                'role' => User::ROLE_USER,
            ]);

        $response->assertRedirect(route('manage.role.index'));
        $response->assertSessionHas('warning', 'Anda tidak dapat mengubah data akun Anda sendiri.');
        $this->assertEquals(User::ROLE_SUPER_ADMIN, (int) $this->superAdmin->fresh()->role);
    }

    public function test_existing_super_admin_user_cannot_be_edited_via_manage_role(): void
    {
        $anotherSuperAdmin = $this->createTestUser(User::ROLE_SUPER_ADMIN);

        $response = $this->actingAs($this->superAdmin)
            ->put(route('manage.role.update', $anotherSuperAdmin->id), [
                'role' => User::ROLE_USER,
            ]);

        $response->assertRedirect(route('manage.role.index'));
        $response->assertSessionHas('warning', 'Data Super Admin tidak dapat diubah melalui halaman ini.');
        $this->assertEquals(User::ROLE_SUPER_ADMIN, (int) $anotherSuperAdmin->fresh()->role);
    }

    public function test_edit_user_modal_renders_correctly_with_preselected_role(): void
    {
        $response = $this->actingAs($this->superAdmin)
            ->get(route('manage.role.index', ['s' => $this->targetUser->email]));

        $response->assertOk();
        $response->assertSee('id="editUserModal' . $this->targetUser->id . '"', false);
        $this->assertMatchesRegularExpression('/<option value="1"[^>]*selected/i', $response->getContent());
    }

    public function test_validation_rejects_invalid_roles(): void
    {
        $response = $this->actingAs($this->superAdmin)
            ->put(route('manage.role.update', $this->targetUser->id), [
                'role' => 999,
            ]);

        $response->assertSessionHasErrors('role');
    }
}

