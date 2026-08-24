<?php

namespace Tests\Feature;

use App\Models\Content;
use App\Models\ContentUser;
use App\Models\RoleAccessPermission;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class ContentUserLearningHourManagementTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        // Ensure permission is seeded for tests
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
    }

    public function test_guest_cannot_access_learning_hours_management(): void
    {
        $response = $this->get('/manage/learning-hours/content-user');
        $response->assertRedirect('/login');
    }

    public function test_regular_user_is_denied_access(): void
    {
        $user = User::where('role', User::ROLE_USER)->first() ?? User::factory()->make(['role' => User::ROLE_USER]);

        $response = $this->actingAs($user)->get('/manage/learning-hours/content-user');
        $response->assertStatus(403);
    }

    public function test_admin_can_view_learning_hours_management_page(): void
    {
        $admin = User::where('role', User::ROLE_ADMIN)->first() ?? User::first();

        $response = $this->actingAs($admin)->get('/manage/learning-hours/content-user');
        $response->assertStatus(200);
        $response->assertViewIs('manage.learning-hours.content-user.index');
        $response->assertSee('Content User Learning Hours');
        $response->assertViewHas('statistics');
        $response->assertViewHas('classifications');
    }

    public function test_datatable_endpoint_returns_valid_server_side_json(): void
    {
        $admin = User::where('role', User::ROLE_ADMIN)->first() ?? User::first();

        $response = $this->actingAs($admin)->getJson('/manage/learning-hours/content-user/datatable?draw=1&start=0&length=10');
        $response->assertStatus(200);
        $response->assertJsonStructure([
            'draw',
            'recordsTotal',
            'recordsFiltered',
            'data',
        ]);

        $json = $response->json();
        $this->assertEquals(1, $json['draw']);
        $this->assertGreaterThan(0, $json['recordsTotal']);
        $this->assertCount(10, $json['data']);
    }

    public function test_calculate_selected_and_recalculate_selected(): void
    {
        $admin = User::where('role', User::ROLE_ADMIN)->first() ?? User::first();
        $contentUsers = ContentUser::whereHas('content')->take(3)->pluck('id')->toArray();

        $this->assertNotEmpty($contentUsers);

        // Test Calculate Selected
        $response = $this->actingAs($admin)->postJson('/manage/learning-hours/content-user/calculate-selected', [
            'ids' => $contentUsers,
        ]);

        $response->assertStatus(200);
        $response->assertJsonPath('success', true);
        $response->assertJsonStructure(['result' => ['total', 'updated', 'skipped', 'failed']]);

        // Test Recalculate Selected
        $recalcResponse = $this->actingAs($admin)->postJson('/manage/learning-hours/content-user/recalculate-selected', [
            'ids' => $contentUsers,
        ]);

        $recalcResponse->assertStatus(200);
        $recalcResponse->assertJsonPath('success', true);
    }

    public function test_batch_start_and_process_workflow(): void
    {
        $admin = User::where('role', User::ROLE_ADMIN)->first() ?? User::first();

        $startResponse = $this->actingAs($admin)->postJson('/manage/learning-hours/content-user/batch/start', [
            'type' => 'all_remaining',
        ]);

        $startResponse->assertStatus(200);
        $startResponse->assertJsonPath('success', true);
        $batchId = $startResponse->json('batch_id');
        $this->assertNotNull($batchId);

        $processResponse = $this->actingAs($admin)->postJson('/manage/learning-hours/content-user/batch/process', [
            'batch_id' => $batchId,
        ]);

        $processResponse->assertStatus(200);
        $processResponse->assertJsonPath('success', true);
    }

    public function test_manage_dashboard_displays_learning_hour_management_card_for_admin(): void
    {
        $admin = User::where('role', User::ROLE_ADMIN)->first() ?? User::first();

        $response = $this->actingAs($admin)->get('/manage');
        $response->assertStatus(200);
        $response->assertSee('Learning Hour Management');
        $response->assertSee(route('manage.learning-hours.content-user.index'));
    }
}
