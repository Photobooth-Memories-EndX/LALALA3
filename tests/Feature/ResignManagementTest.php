<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class ResignManagementTest extends TestCase
{
    use DatabaseTransactions;

    protected User $superAdmin;
    protected User $regularUser;

    protected function setUp(): void
    {
        parent::setUp();

        $this->superAdmin = User::where('role', User::ROLE_SUPER_ADMIN)->first()
            ?: User::factory()->create(['role' => User::ROLE_SUPER_ADMIN]);

        $this->regularUser = User::where('role', User::ROLE_USER)->whereNull('deleted_at')->first()
            ?: User::factory()->create(['role' => User::ROLE_USER]);
    }

    /**
     * Test 1: Unauthenticated user is redirected to login.
     */
    public function test_unauthenticated_user_cannot_access_resign_management(): void
    {
        $response = $this->get('/manage/role/resign');
        $response->assertRedirect(route('loginpage'));
    }

    /**
     * Test 2: Non-superadmin user receives 403 Forbidden.
     */
    public function test_non_superadmin_user_receives_forbidden(): void
    {
        $response = $this->actingAs($this->regularUser)->get('/manage/role/resign');
        $response->assertStatus(403);
    }

    /**
     * Test 3: Super admin can access resign management page.
     */
    public function test_superadmin_can_access_resign_management(): void
    {
        $response = $this->actingAs($this->superAdmin)->get('/manage/role/resign');
        $response->assertStatus(200);
        $response->assertSee('Resign Management');
        $response->assertSee('List Resigned Employees');
    }

    /**
     * Test 4: User with deleted_at = NULL does NOT appear in resign datatable.
     */
    public function test_active_user_does_not_appear_in_resign_datatable(): void
    {
        $activeUser = User::whereNull('deleted_at')->whereNotNull('id_emp')->first();
        $this->assertNotNull($activeUser);

        $response = $this->actingAs($this->superAdmin)
            ->getJson(route('manage.role.resign.datatable', ['search_keyword' => $activeUser->id_emp]));

        $response->assertStatus(200);
        $json = $response->json();
        $this->assertEquals(0, $json['recordsFiltered']);
    }

    /**
     * Test 5: Soft-deleted user automatically appears in resign datatable.
     */
    public function test_soft_deleted_user_automatically_appears(): void
    {
        $trashedUser = User::onlyTrashed()->whereNotNull('id_emp')->first();
        $this->assertNotNull($trashedUser);

        $response = $this->actingAs($this->superAdmin)
            ->getJson(route('manage.role.resign.datatable', ['search_keyword' => $trashedUser->id_emp]));

        $response->assertStatus(200);
        $json = $response->json();
        $this->assertGreaterThanOrEqual(1, $json['recordsFiltered']);
        $this->assertEquals($trashedUser->id_emp, $json['data'][0]['id_emp']);
    }

    /**
     * Test 6: Dynamic soft-delete and restore lifecycle.
     * When user is soft-deleted, they appear. When restored, they disappear.
     */
    public function test_dynamic_soft_delete_and_restore_lifecycle(): void
    {
        $testUser = User::whereNull('deleted_at')->whereNotNull('id_emp')->first();
        $this->assertNotNull($testUser);
        $nip = $testUser->id_emp;

        // Step 1: Active user does not appear
        $resBefore = $this->actingAs($this->superAdmin)
            ->getJson(route('manage.role.resign.datatable', ['search_keyword' => $nip]));
        $this->assertEquals(0, $resBefore->json('recordsFiltered'));

        // Step 2: Soft delete the user -> user automatically appears
        $testUser->delete();
        $this->assertNotNull($testUser->fresh()->deleted_at);

        $resAfterDelete = $this->actingAs($this->superAdmin)
            ->getJson(route('manage.role.resign.datatable', ['search_keyword' => $nip]));
        $this->assertEquals(1, $resAfterDelete->json('recordsFiltered'));

        // Step 3: Restore the user -> user automatically disappears
        $testUser->restore();
        $this->assertNull($testUser->fresh()->deleted_at);

        $resAfterRestore = $this->actingAs($this->superAdmin)
            ->getJson(route('manage.role.resign.datatable', ['search_keyword' => $nip]));
        $this->assertEquals(0, $resAfterRestore->json('recordsFiltered'));
    }

    /**
     * Test 7: Pagination works properly on resign datatable.
     */
    public function test_pagination_on_resign_datatable(): void
    {
        $totalTrashed = User::onlyTrashed()->count();
        $this->assertGreaterThan(10, $totalTrashed);

        $response = $this->actingAs($this->superAdmin)
            ->getJson(route('manage.role.resign.datatable', ['start' => 0, 'length' => 10]));

        $response->assertStatus(200);
        $json = $response->json();
        $this->assertEquals($totalTrashed, $json['recordsTotal']);
        $this->assertCount(10, $json['data']);
    }

    /**
     * Test 8: Filter by Company.
     */
    public function test_filter_by_company(): void
    {
        $companyName = User::onlyTrashed()->whereNotNull('company_name')->value('company_name');
        $this->assertNotEmpty($companyName);

        $expectedCount = User::onlyTrashed()->where('company_name', $companyName)->count();

        $response = $this->actingAs($this->superAdmin)
            ->getJson(route('manage.role.resign.datatable', ['company' => $companyName]));

        $response->assertStatus(200);
        $json = $response->json();
        $this->assertEquals($expectedCount, $json['recordsFiltered']);
    }

    /**
     * Test 9: Filter with no matching data returns empty dataset.
     */
    public function test_filter_with_no_matching_data_returns_empty(): void
    {
        $response = $this->actingAs($this->superAdmin)
            ->getJson(route('manage.role.resign.datatable', ['company' => 'NonExistentCompanyXYZ']));

        $response->assertStatus(200);
        $json = $response->json();
        $this->assertEquals(0, $json['recordsFiltered']);
        $this->assertEmpty($json['data']);
    }
}
