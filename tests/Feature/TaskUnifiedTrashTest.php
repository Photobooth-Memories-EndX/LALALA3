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

    public function test_admin_can_restore_and_force_delete_course_from_trash(): void
    {
        $group = TaskGroup::create([
            'title'       => 'Restore Test Wadah ' . uniqid(),
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

        // Delete again and force delete
        $group->delete();
        $forceResponse = $this->actingAs($this->admin)->delete(route('manage.task.force-destroy', [
            'id'          => $group->id,
            'item_type'   => 'course',
            'course_type' => 'standalone',
        ]));
        $forceResponse->assertRedirect();
        $this->assertNull(TaskGroup::withTrashed()->find($group->id));
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
}
