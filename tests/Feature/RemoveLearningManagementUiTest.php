<?php

namespace Tests\Feature;

use App\Models\RoleAccessPermission;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Route;
use Tests\TestCase;

class RemoveLearningManagementUiTest extends TestCase
{
    use DatabaseTransactions;

    protected User $admin;
    protected User $superAdmin;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::where('role', '2')->first() ?? User::factory()->create(['role' => 2]);
        $this->superAdmin = User::where('role', '3')->first() ?? User::factory()->create(['role' => 3]);
    }

    /**
     * Test 1: Manage dashboard (/manage) does not display Learning Management card,
     * while other management cards remain visible.
     */
    public function test_manage_dashboard_does_not_display_learning_management_card(): void
    {
        $response = $this->actingAs($this->admin)->get('/manage');

        $response->assertOk();
        $content = $response->getContent();

        // Learning Management card description, card title, and link must NOT be present
        $response->assertDontSee('Monitor employee learning activities, track learning progress');
        $this->assertDoesNotMatchRegularExpression(
            '/<a[^>]*href="[^"]*\/manage\/learning"[^>]*>/i',
            $content,
            'Link to /manage/learning must not exist on the manage dashboard'
        );
        $this->assertDoesNotMatchRegularExpression(
            '/<h3[^>]*>\s*Learning Management\s*<\/h3>/i',
            $content,
            'Card for Learning Management must not exist on the manage dashboard'
        );

        // Other core cards must remain visible
        $response->assertSee('Record Management');
        $response->assertSee('Assessment Management');
        $response->assertSee('Content Repository');
        $response->assertSee('Playlist &amp; Learning Path', false);
    }

    /**
     * Test 2: Edit Access Permission page displays Learning Management checkbox in Debugger Developer Tools.
     */
    public function test_access_permission_edit_displays_learning_management_checkbox(): void
    {
        $response = $this->actingAs($this->superAdmin)
            ->get(route('manage.access-permission.edit', 3));

        $response->assertOk();
        $content = $response->getContent();

        // Checkbox for learning_management must exist in the DOM
        $this->assertMatchesRegularExpression(
            '/<input[^>]*type="checkbox"[^>]*value="learning_management"/i',
            $content,
            'Learning Management checkbox must be rendered in Edit Access Permission'
        );

        // Checkboxes for other modules must still be rendered
        $this->assertMatchesRegularExpression(
            '/<input[^>]*value="record_management"/i',
            $content
        );
        $this->assertMatchesRegularExpression(
            '/<input[^>]*value="admin_management"/i',
            $content
        );
        $this->assertMatchesRegularExpression(
            '/<input[^>]*value="content_repository"/i',
            $content
        );
    }

    /**
     * Test 3: Saving permissions correctly updates learning_management permission.
     */
    public function test_access_permission_save_updates_learning_management_permission(): void
    {
        // Ensure Role 3 currently has learning_management = true
        RoleAccessPermission::updateOrCreate(
            ['role' => 3, 'module_key' => 'learning_management'],
            ['can_access' => true]
        );

        // Administrator saves changes for Role 3 without learning_management in payload (disables it)
        $response = $this->actingAs($this->superAdmin)
            ->put(route('manage.access-permission.update', 3), [
                'permissions' => [
                    'admin_management',
                    'record_management',
                    'content_repository',
                ],
            ]);

        $response->assertRedirect(route('manage.access-permission.index'));

        // Verify learning_management is false in the database
        $permission = RoleAccessPermission::where('role', 3)
            ->where('module_key', 'learning_management')
            ->first();

        $this->assertNotNull($permission, 'RoleAccessPermission for learning_management must remain in database');
        $this->assertFalse((bool) $permission->can_access, 'learning_management permission must be false when unchecked');

        // Administrator saves changes for Role 3 with learning_management in payload (enables it)
        $response2 = $this->actingAs($this->superAdmin)
            ->put(route('manage.access-permission.update', 3), [
                'permissions' => [
                    'admin_management',
                    'record_management',
                    'content_repository',
                    'learning_management',
                ],
            ]);

        $response2->assertRedirect(route('manage.access-permission.index'));
        $permission->refresh();
        $this->assertTrue((bool) $permission->can_access, 'learning_management permission must be true when checked');
    }

    /**
     * Test 4: Direct URL /manage/learning remains accessible and functional.
     */
    public function test_direct_access_to_manage_learning_still_works(): void
    {
        $response = $this->actingAs($this->admin)->get('/manage/learning');

        $response->assertOk();
    }

    /**
     * Test 5: Routes, route names, and database permissions are preserved.
     */
    public function test_routes_and_database_remain_intact(): void
    {
        // Verify routes exist
        $this->assertTrue(Route::has('manage.index'));
        $this->assertTrue(Route::has('learning.manage.index'));
        $this->assertTrue(Route::has('manage.access-permission.index'));
        $this->assertTrue(Route::has('manage.access-permission.edit'));
        $this->assertTrue(Route::has('manage.access-permission.update'));

        // Verify database records for learning_management still exist
        $dbCount = RoleAccessPermission::where('module_key', 'learning_management')->count();
        $this->assertGreaterThan(0, $dbCount, 'RoleAccessPermission table must still contain learning_management records');
    }
}
