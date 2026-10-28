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

    public function test_role_dropdown_contains_all_five_roles_in_exact_order(): void
    {
        $response = $this->actingAs($this->superAdmin)
            ->get(route('manage.role.index'));

        $response->assertOk();
        $response->assertSee('Edit User Organization');

        // Verify exact order in dropdown: User (1) -> Sub Admin (4) -> Administrator (2) -> Super Admin (3) -> Approver (5)
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

        // Test assign Approver (Role 5)
        $responseApprover = $this->actingAs($this->superAdmin)
            ->put(route('manage.role.update', $this->targetUser->id), [
                'role' => User::ROLE_APPROVER,
                'division' => 'Technology',
                'department' => 'IT',
                'team' => 'Management',
            ]);
        $responseApprover->assertRedirect(route('manage.role.index'));
        $this->assertEquals(User::ROLE_APPROVER, (int) $this->targetUser->fresh()->role);
        $this->assertTrue($this->targetUser->fresh()->isApprover());
        $this->assertEquals('Approver', $this->targetUser->fresh()->role_name);

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
        $approver = $this->createTestUser(User::ROLE_APPROVER);

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

        // Approver (5) forbidden
        $this->actingAs($approver)->get(route('manage.role.index'))->assertForbidden();
        $this->actingAs($approver)->put(route('manage.role.update', $this->targetUser->id), [
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

    public function test_super_admin_can_view_user_detail_page_with_all_hr_fields(): void
    {
        $hrUser = User::factory()->create([
            'role' => User::ROLE_USER,
            'name' => 'John Doe HR',
            'email' => 'john.hr@krakatauposco.co.id',
            'sap_code' => 'SAP-998877',
            'id_emp' => '998877',
            'team' => 'DevOps Team',
            'department' => 'Digital Dept',
            'division' => 'Technology Div',
            'id_org_unit' => 'OU99',
            'orgunit' => 'Digital Org Unit',
            'id_division' => 'DV9',
            'id_department' => 'DP8',
            'id_team' => 'TM7',
            'cell_phone' => '081299998888',
            'sex' => 'M',
            'id_job_position' => 'JP44',
            'jobposition' => 'Senior DevOps Specialist',
            'positionID' => '40009999',
            'jobgrade' => 'JG06',
            'id_job_clan' => 'JC02',
            'job_clan' => 'Engineering Clan',
            'id_job_family' => 'JF05',
            'job_family' => 'Cloud Systems Family',
            'appr1' => '00010001',
            'appr2' => '00010002',
            'resign' => 'N',
            'flag' => 1,
            'ssohash' => 'secret_sha1_hash_must_be_hidden_12345',
        ]);

        $response = $this->actingAs($this->superAdmin)
            ->get(route('manage.role.show', $hrUser->id));

        $response->assertOk();
        $response->assertSee('John Doe HR');
        $response->assertSee('998877');
        $response->assertSee('081299998888');
        $response->assertSee('Senior DevOps Specialist');
        $response->assertSee('JG06');
        $response->assertSee('Engineering Clan');
        $response->assertSee('Cloud Systems Family');
        $response->assertSee('00010001');
        $response->assertSee('00010002');
        $response->assertSee('A. Informasi Akun OWL');
        $response->assertSee('B. Informasi Organisasi');
        $response->assertSee('C. Informasi HR');

        // Verify secret SSO token is strictly hidden from view
        $response->assertDontSee('secret_sha1_hash_must_be_hidden_12345');
        $response->assertSee('Terhubung (SSO Active)');
    }

    public function test_unauthorized_users_cannot_access_user_detail_page(): void
    {
        $admin = $this->createTestUser(User::ROLE_ADMIN);
        $user = $this->createTestUser(User::ROLE_USER);

        // Guest redirect
        $this->get(route('manage.role.show', $this->targetUser->id))
            ->assertRedirect(route('loginpage'));

        // Admin forbidden
        $this->actingAs($admin)
            ->get(route('manage.role.show', $this->targetUser->id))
            ->assertForbidden();

        // User forbidden
        $this->actingAs($user)
            ->get(route('manage.role.show', $this->targetUser->id))
            ->assertForbidden();
    }

    public function test_super_admin_can_soft_delete_user_without_historical_learning_data(): void
    {
        $userToDelete = $this->createTestUser(User::ROLE_USER);

        $response = $this->actingAs($this->superAdmin)
            ->delete(route('manage.role.destroy', $userToDelete->id));

        $response->assertRedirect(route('manage.role.index'));
        $response->assertSessionHas('success');

        $this->assertTrue($userToDelete->fresh()->trashed());
        $this->assertSoftDeleted('users', ['id' => $userToDelete->id]);
    }

    public function test_super_admin_cannot_delete_own_account(): void
    {
        $response = $this->actingAs($this->superAdmin)
            ->delete(route('manage.role.destroy', $this->superAdmin->id));

        $response->assertRedirect(route('manage.role.index'));
        $response->assertSessionHas('warning', 'Anda tidak dapat menghapus akun Anda sendiri.');
        $this->assertFalse($this->superAdmin->fresh()->trashed());
    }

    public function test_super_admin_cannot_delete_another_super_admin(): void
    {
        $otherSuperAdmin = $this->createTestUser(User::ROLE_SUPER_ADMIN);

        $response = $this->actingAs($this->superAdmin)
            ->delete(route('manage.role.destroy', $otherSuperAdmin->id));

        $response->assertRedirect(route('manage.role.index'));
        $response->assertSessionHas('warning', 'Akun Super Admin tidak dapat dihapus.');
        $this->assertFalse($otherSuperAdmin->fresh()->trashed());
    }

    public function test_super_admin_cannot_delete_user_with_active_learning_history(): void
    {
        $userWithLearning = $this->createTestUser(User::ROLE_USER);

        // Mock relation content user
        \App\Models\ContentUser::create([
            'user_id' => $userWithLearning->id,
            'content_id' => 1,
            'status' => 'mandatory',
        ]);

        $response = $this->actingAs($this->superAdmin)
            ->delete(route('manage.role.destroy', $userWithLearning->id));

        $response->assertRedirect(route('manage.role.index'));
        $response->assertSessionHas('warning');
        $this->assertFalse($userWithLearning->fresh()->trashed());
    }

    public function test_super_admin_can_trigger_synchronize_dry_run_simulation(): void
    {
        $response = $this->actingAs($this->superAdmin)
            ->postJson(route('manage.role.sync'));

        $response->assertOk();
        $response->assertJson([
            'success' => true,
            'mode' => 'DRY-RUN (Zero-Write Simulation)',
        ]);
        $response->assertJsonStructure([
            'success',
            'status',
            'mode',
            'duration',
            'total_api',
            'total_owl',
            'created_count',
            'updated_count',
            'updated_active_count',
            'updated_soft_deleted_count',
            'unchanged_count',
            'conflict_count',
            'message',
        ]);
    }

    public function test_unauthorized_users_cannot_trigger_synchronize_dry_run(): void
    {
        $admin = $this->createTestUser(User::ROLE_ADMIN);
        $user = $this->createTestUser(User::ROLE_USER);

        // Guest redirect
        $this->postJson(route('manage.role.sync'))
            ->assertUnauthorized();

        // Admin forbidden
        $this->actingAs($admin)
            ->postJson(route('manage.role.sync'))
            ->assertForbidden();

        // User forbidden
        $this->actingAs($user)
            ->postJson(route('manage.role.sync'))
            ->assertForbidden();
    }

    public function test_update_user_can_modify_active_status_and_protects_credentials(): void
    {
        $oldPassword = $this->targetUser->password;
        $oldSsoHash = $this->targetUser->ssohash;

        $response = $this->actingAs($this->superAdmin)
            ->put(route('manage.role.update', $this->targetUser->id), [
                'role' => User::ROLE_USER,
                'active' => 1,
                'division' => 'New Division',
                'department' => 'New Department',
                'team' => 'New Team',
            ]);

        $response->assertRedirect(route('manage.role.index'));
        $this->targetUser->refresh();

        $this->assertEquals(1, (int) $this->targetUser->active);
        $this->assertEquals('New Division', $this->targetUser->division);
        $this->assertEquals($oldPassword, $this->targetUser->password);
        $this->assertEquals($oldSsoHash, $this->targetUser->ssohash);
    }
}
