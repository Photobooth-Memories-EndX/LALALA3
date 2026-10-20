<?php

namespace Tests\Feature;

use App\Models\Content;
use App\Models\PlaylistContentUser;
use App\Models\Task;
use App\Models\TrainingPlan;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class TaskBackButtonConsistencyTest extends TestCase
{
    use DatabaseTransactions;

    protected User $admin;
    protected User $regularUser;
    protected TrainingPlan $plan;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::where('role', User::ROLE_ADMIN)->first()
            ?? User::where('role', User::ROLE_SUPER_ADMIN)->first()
            ?? User::factory()->create(['role' => User::ROLE_ADMIN]);

        $this->regularUser = User::where('role', User::ROLE_USER)->first()
            ?? User::factory()->create(['role' => User::ROLE_USER]);

        $this->plan = TrainingPlan::create([
            'title'       => 'Test Plan ' . uniqid(),
            'slug'        => 'test-plan-' . uniqid(),
            'description' => 'Test description',
            'is_active'   => 1,
        ]);
    }

    public function test_admin_task_plans_page_renders_link_style_back_button(): void
    {
        $response = $this->actingAs($this->admin)->get(route('manage.task.index'));

        $response->assertStatus(200);
        $response->assertSee('Back');
        $response->assertSee('ti ti-arrow-left');
    }

    public function test_admin_task_create_page_renders_link_style_back_button(): void
    {
        $response = $this->actingAs($this->admin)->get(route('manage.task.create'));

        $response->assertStatus(200);
        $response->assertSee('text-primary text-decoration-none fw-semibold', false);
        $response->assertSee('ti ti-arrow-left');
        $response->assertSee('Back');
    }

    public function test_admin_task_parent_index_renders_link_style_back_button(): void
    {
        $response = $this->actingAs($this->admin)->get(route('manage.task.parent', [
            'parentType' => 'playlist',
            'parentId'   => $this->plan->id,
        ]));

        $response->assertStatus(200);
        $response->assertSee('text-primary text-decoration-none fw-semibold', false);
        $response->assertSee('ti ti-arrow-left');
        $response->assertSee('Back');
    }

    public function test_admin_task_trash_page_renders_link_style_back_button(): void
    {
        $response = $this->actingAs($this->admin)->get(route('manage.task.trash'));

        $response->assertStatus(200);
        $response->assertSee('text-primary text-decoration-none fw-semibold', false);
        $response->assertSee('ti ti-arrow-left');
        $response->assertSee('Back');
    }

    public function test_user_my_task_detail_renders_link_style_back_button(): void
    {
        // Create content & task for this plan
        $content = Content::create([
            'title'     => 'Lesson ' . uniqid(),
            'slug'      => 'lesson-' . uniqid(),
            'type'      => 'Video',
            'is_active' => 1,
        ]);

        $task = Task::create([
            'parent_type'      => 'playlist',
            'training_plan_id' => $this->plan->id,
            'title'            => 'Task for Plan ' . uniqid(),
            'type'             => 'open_ended',
            'status'           => 'published',
            'created_by'       => $this->admin->id,
        ]);

        \App\Models\TaskPlaylistUnlock::create([
            'user_id'          => $this->regularUser->id,
            'training_plan_id' => $this->plan->id,
        ]);

        $response = $this->actingAs($this->regularUser)->get(route('user.learning.dashboard.partial', [
            'tab'            => 'my-task',
            'my_task_parent' => 'playlist:' . $this->plan->id,
        ]));

        $response->assertStatus(200);
        $response->assertSee('text-primary text-decoration-none fw-semibold', false);
        $response->assertSee('ti ti-arrow-left');
        $response->assertSee('Back');
    }
}
