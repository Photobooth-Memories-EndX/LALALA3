<?php

namespace Tests\Feature;

use App\Models\Content;
use App\Models\OfflineTraining;
use App\Models\Task;
use App\Models\TaskGroup;
use App\Models\TrainingPlan;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class TaskUnifiedTrashTest extends TestCase
{
    use DatabaseTransactions;

    protected User $admin;
    protected TrainingPlan $plan;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::where('role', User::ROLE_ADMIN)->first()
            ?? User::where('role', User::ROLE_SUPER_ADMIN)->first()
            ?? User::factory()->create(['role' => User::ROLE_ADMIN]);

        $this->plan = TrainingPlan::create([
            'title'       => 'Trash Test Plan ' . uniqid(),
            'slug'        => 'trash-test-plan-' . uniqid(),
            'description' => 'Test course description',
            'is_active'   => 1,
        ]);
    }

    public function test_trash_button_only_on_front_page_and_not_on_course_tasks_page(): void
    {
        // 1. Front page has the trash button
        $frontResponse = $this->actingAs($this->admin)->get(route('manage.task.index'));
        $frontResponse->assertStatus(200);
        $frontResponse->assertSee(route('manage.task.trash'));
        $frontResponse->assertSee('Trash');

        // 2. Inner course page does NOT have the trash button
        $innerResponse = $this->actingAs($this->admin)->get(route('manage.task.parent', [
            'parentType' => 'playlist',
            'parentId'   => $this->plan->id,
        ]));
        $innerResponse->assertStatus(200);
        $innerResponse->assertDontSee(route('manage.task.trash'));
    }

    public function test_trash_page_renders_type_filter_pills_and_badges(): void
    {
        $response = $this->actingAs($this->admin)->get(route('manage.task.trash'));

        $response->assertStatus(200);
        $response->assertSee('my-task-category-pill', false);
        $response->assertSee('All');
        $response->assertSee('Course');
        $response->assertSee('Task');
        $response->assertSee('Search trash...');
    }

    public function test_trash_page_filters_by_course_and_task(): void
    {
        $uniqueSuffix = uniqid();

        // Create a trashed task
        $task = Task::create([
            'title'       => "Trashed Task {$uniqueSuffix}",
            'type'        => 'upload',
            'parent_type' => 'playlist',
            'training_plan_id' => $this->plan->id,
            'status'      => 'published',
            'created_by'  => $this->admin->id,
        ]);
        $task->delete();

        // Create a trashed container group
        $group = TaskGroup::create([
            'title'       => "Trashed Wadah {$uniqueSuffix}",
            'description' => 'Test wadah description',
            'created_by'  => $this->admin->id,
        ]);
        $group->delete();

        // 1. type=all shows both
        $allResponse = $this->actingAs($this->admin)->get(route('manage.task.trash', ['type' => 'all']));
        $allResponse->assertStatus(200);
        $allResponse->assertSee("Trashed Task {$uniqueSuffix}");
        $allResponse->assertSee("Trashed Wadah {$uniqueSuffix}");

        // 2. type=course shows only the course wadah
        $courseResponse = $this->actingAs($this->admin)->get(route('manage.task.trash', ['type' => 'course']));
        $courseResponse->assertStatus(200);
        $courseResponse->assertSee("Trashed Wadah {$uniqueSuffix}");
        $courseResponse->assertDontSee("Trashed Task {$uniqueSuffix}");

        // 3. type=task shows only the task
        $taskResponse = $this->actingAs($this->admin)->get(route('manage.task.trash', ['type' => 'task']));
        $taskResponse->assertStatus(200);
        $taskResponse->assertSee("Trashed Task {$uniqueSuffix}");
        $taskResponse->assertDontSee("Trashed Wadah {$uniqueSuffix}");
    }

    public function test_admin_can_restore_and_delete_course_from_trash_without_permanent_data_loss(): void
    {
        $groupTitle = 'Restore Test Wadah ' . uniqid();
        $group = TaskGroup::create([
            'title'       => $groupTitle,
            'created_by'  => $this->admin->id,
        ]);
        $group->delete();

        $this->assertTrue($group->fresh()->trashed());

        // Restore course
        $restoreResponse = $this->actingAs($this->admin)->post(route('manage.task.restore', [
            'id'          => $group->id,
            'item_type'   => 'course',
            'course_type' => 'standalone',
        ]));
        $restoreResponse->assertRedirect();
        $this->assertFalse($group->fresh()->trashed());

        // Move to trash again
        $group->delete();

        // Delete from trash (data preserved in DB, hidden from view)
        $forceResponse = $this->actingAs($this->admin)->delete(route('manage.task.force-destroy', [
            'id'          => $group->id,
            'item_type'   => 'course',
            'course_type' => 'standalone',
        ]));
        $forceResponse->assertRedirect();

        // Data stays in database
        $this->assertNotNull(TaskGroup::withTrashed()->find($group->id));

        // But is hidden from trash view
        $trashResp = $this->actingAs($this->admin)->get(route('manage.task.trash', ['type' => 'course']));
        $trashResp->assertDontSee($groupTitle);
    }

    public function test_deleting_content_with_users_from_trash_keeps_db_and_does_not_fail_foreign_key(): void
    {
        $contentTitle = 'Content With Users ' . uniqid();
        $content = Content::create([
            'title' => $contentTitle,
            'slug'  => 'c-user-' . uniqid(),
            'type'  => 'video',
        ]);

        \Illuminate\Support\Facades\DB::table('content_users')->insert([
            'content_id' => $content->id,
            'user_id'    => $this->admin->id,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $content->delete();

        // Delete from trash
        $response = $this->actingAs($this->admin)->delete(route('manage.task.force-destroy', [
            'id'          => $content->id,
            'item_type'   => 'course',
            'course_type' => 'content',
        ]));

        $response->assertRedirect();

        // Content row remains in database
        $this->assertNotNull(Content::withTrashed()->find($content->id));
        // Child content_users row remains in database
        $this->assertDatabaseHas('content_users', [
            'content_id' => $content->id,
            'user_id'    => $this->admin->id,
        ]);

        // Content is hidden from trash view
        $trashResp = $this->actingAs($this->admin)->get(route('manage.task.trash', ['type' => 'course']));
        $trashResp->assertDontSee($contentTitle);
    }

    public function test_admin_can_move_container_to_trash_via_destroy_endpoint(): void
    {
        $group = TaskGroup::create([
            'title'       => 'Move To Trash Wadah ' . uniqid(),
            'created_by'  => $this->admin->id,
        ]);

        $this->assertFalse($group->trashed());

        $response = $this->actingAs($this->admin)->delete(route('manage.task.container.destroy', [
            'parentType' => 'standalone',
            'parentId'   => $group->id,
        ]));

        $response->assertRedirect();
        $this->assertTrue($group->fresh()->trashed());
    }
    public function test_trash_view_renders_cleanly_when_only_tasks_passed(): void
    {
        $task = Task::create([
            'title'       => 'Legacy Trashed ' . uniqid(),
            'type'        => 'upload',
            'parent_type' => 'playlist',
            'training_plan_id' => $this->plan->id,
            'status'      => 'published',
            'created_by'  => $this->admin->id,
        ]);
        $task->delete();

        $view = $this->actingAs($this->admin)->view('manage.task.trash', [
            'tasks' => Task::onlyTrashed()->paginate(20),
        ]);

        $view->assertSee('Legacy Trashed');
    }

    public function test_moving_container_to_trash_removes_it_from_task_dashboard_list_and_restores(): void
    {
        $planTitle = 'Playlist Container ' . uniqid();
        $plan = TrainingPlan::create([
            'title' => $planTitle,
            'slug'  => 'tp-' . uniqid(),
        ]);

        $task = Task::create([
            'title'            => 'Task under plan ' . uniqid(),
            'type'             => 'assignment',
            'parent_type'      => 'playlist',
            'training_plan_id' => $plan->id,
            'status'           => 'published',
            'created_by'       => $this->admin->id,
        ]);

        // 1. Dashboard displays the container
        $beforeResp = $this->actingAs($this->admin)->get(route('manage.task.index', ['scope' => 'kp', 'parent_type' => 'all']));
        $beforeResp->assertStatus(200);
        $beforeResp->assertSee($planTitle);

        // 2. Move container to trash
        $destroyResp = $this->actingAs($this->admin)->delete(route('manage.task.container.destroy', [
            'parentType' => 'playlist',
            'parentId'   => $plan->id,
        ]));
        $destroyResp->assertRedirect();

        // 3. Container disappears from dashboard
        $afterResp = $this->actingAs($this->admin)->get(route('manage.task.index', ['scope' => 'kp', 'parent_type' => 'all']));
        $afterResp->assertStatus(200);
        $afterResp->assertDontSee($planTitle);
        $this->assertTrue($plan->fresh()->trashed());
        $this->assertTrue($task->fresh()->trashed());

        // 4. Container appears in trash page under course tab
        $trashResp = $this->actingAs($this->admin)->get(route('manage.task.trash', ['type' => 'course']));
        $trashResp->assertStatus(200);
        $trashResp->assertSee($planTitle);

        // 5. Restore container from trash
        $restoreResp = $this->actingAs($this->admin)->post(route('manage.task.restore', [
            'id'          => $plan->id,
            'item_type'   => 'course',
            'course_type' => 'playlist',
        ]));
        $restoreResp->assertRedirect();

        // 6. Container and tasks reappear on dashboard
        $reappearResp = $this->actingAs($this->admin)->get(route('manage.task.index', ['scope' => 'kp', 'parent_type' => 'all']));
        $reappearResp->assertStatus(200);
        $reappearResp->assertSee($planTitle);
        $this->assertFalse($plan->fresh()->trashed());
        $this->assertFalse($task->fresh()->trashed());
    }

    public function test_moving_content_and_offline_training_containers_to_trash_removes_them_from_dashboard(): void
    {
        // Content container
        $contentTitle = 'Content Container ' . uniqid();
        $content = Content::create([
            'title' => $contentTitle,
            'slug'  => 'tc-' . uniqid(),
            'type'  => 'Video',
        ]);
        $contentTask = Task::create([
            'title'       => 'Content Task ' . uniqid(),
            'type'        => 'assignment',
            'parent_type' => 'content',
            'content_id'  => $content->id,
            'status'      => 'published',
            'created_by'  => $this->admin->id,
        ]);

        $this->actingAs($this->admin)->delete(route('manage.task.container.destroy', [
            'parentType' => 'content',
            'parentId'   => $content->id,
        ]))->assertRedirect();

        $contentDashboard = $this->actingAs($this->admin)->get(route('manage.task.index', ['scope' => 'kp', 'parent_type' => 'all']));
        $contentDashboard->assertDontSee($contentTitle);
        $this->assertTrue($content->fresh()->trashed());
        $this->assertTrue($contentTask->fresh()->trashed());

        // Offline training container
        $otTitle = 'Offline Session ' . uniqid();
        $ot = OfflineTraining::create([
            'title' => $otTitle,
            'slug'  => 'ot-' . uniqid(),
        ]);
        $otTask = Task::create([
            'title'               => 'OT Task ' . uniqid(),
            'type'                => 'assignment',
            'parent_type'         => 'offline_training',
            'offline_training_id' => $ot->id,
            'status'              => 'published',
            'created_by'          => $this->admin->id,
        ]);

        $this->actingAs($this->admin)->delete(route('manage.task.container.destroy', [
            'parentType' => 'offline_training',
            'parentId'   => $ot->id,
        ]))->assertRedirect();

        $otDashboard = $this->actingAs($this->admin)->get(route('manage.task.index', ['scope' => 'non_kp']));
        $otDashboard->assertDontSee($otTitle);
        $this->assertTrue($ot->fresh()->trashed());
        $this->assertTrue($otTask->fresh()->trashed());
    }
}
