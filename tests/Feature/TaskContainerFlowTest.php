<?php

namespace Tests\Feature;

use App\Models\Content;
use App\Models\Task;
use App\Models\TaskGroup;
use App\Models\TrainingPlan;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class TaskContainerFlowTest extends TestCase
{
    use DatabaseTransactions;

    protected User $admin;
    protected User $learner;
    protected TrainingPlan $plan;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::where('role', User::ROLE_ADMIN)->first()
            ?? User::where('role', User::ROLE_SUPER_ADMIN)->first()
            ?? User::factory()->create(['role' => User::ROLE_ADMIN]);

        $this->learner = User::where('role', User::ROLE_USER)->first()
            ?? User::factory()->create(['role' => User::ROLE_USER]);

        $this->plan = TrainingPlan::create([
            'title'       => 'Container Test Course ' . uniqid(),
            'slug'        => 'container-test-course-' . uniqid(),
            'description' => 'Test course description',
            'is_active'   => 1,
        ]);
    }

    public function test_admin_can_create_others_container_group(): void
    {
        $response = $this->actingAs($this->admin)->post(route('manage.task.group.store'), [
            'title'       => 'Onboarding MT Batch 2026',
            'description' => 'Tugas-tugas khusus masa onboarding MT',
        ]);

        $group = TaskGroup::where('title', 'Onboarding MT Batch 2026')->first();
        $this->assertNotNull($group);

        $response->assertRedirect(route('manage.task.parent', [
            'parentType' => 'standalone',
            'parentId'   => $group->id,
        ]));
    }

    public function test_container_page_renders_container_title_and_add_task_button(): void
    {
        $group = TaskGroup::create([
            'title'      => 'Workshop Leadership ' . uniqid(),
            'created_by' => $this->admin->id,
        ]);

        $response = $this->actingAs($this->admin)->get(route('manage.task.parent', [
            'parentType' => 'standalone',
            'parentId'   => $group->id,
        ]));

        $response->assertStatus(200);
        $response->assertSee($group->title);
        $response->assertSee('Add Task');
        $response->assertSee('No Tasks in This Container Yet');
    }

    public function test_task_create_page_locks_container_when_parent_params_provided(): void
    {
        $group = TaskGroup::create([
            'title'      => 'Event Inovasi ' . uniqid(),
            'created_by' => $this->admin->id,
        ]);

        $response = $this->actingAs($this->admin)->get(route('manage.task.create', [
            'parent_type' => 'standalone',
            'parent_id'   => $group->id,
        ]));

        $response->assertStatus(200);
        $response->assertSee($group->title);
        $response->assertSee('This task will be automatically added to');
    }

    public function test_storing_task_in_others_container_assigns_task_group_id(): void
    {
        $group = TaskGroup::create([
            'title'      => 'Group Assessment ' . uniqid(),
            'created_by' => $this->admin->id,
        ]);

        $response = $this->actingAs($this->admin)->post(route('manage.task.store'), [
            'parent_type' => 'standalone',
            'parent_id'   => $group->id,
            'title'       => 'Tugas Proposal Inovasi ' . uniqid(),
            'type'        => 'file_submission',
            'action'      => 'publish',
            'fields'      => [
                [
                    'label'      => 'Upload Berkas Proposal (PDF/Docx)',
                    'type'       => 'file',
                    'required'   => 1,
                    'file_types' => 'document',
                    'max_files'  => 1,
                    'max_size_mb'=> 5,
                ],
            ],
        ]);

        $response->assertRedirect(route('manage.task.parent', [
            'parentType' => 'standalone',
            'parentId'   => $group->id,
        ]));

        $task = Task::where('task_group_id', $group->id)->first();
        $this->assertNotNull($task);
        $this->assertEquals($group->id, $task->task_group_id);
        $this->assertEquals('standalone', $task->parent_type);
        $this->assertEquals($group->title, $task->parentName());
    }
}

