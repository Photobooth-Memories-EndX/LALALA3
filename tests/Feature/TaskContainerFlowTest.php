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
        $response->assertSee('No Tasks in This Course Yet');
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
        $response->assertSee('name="parent_type" value="standalone"', false);
        $response->assertSee('name="parent_id" value="' . $group->id . '"', false);
        $response->assertSee('1. Basic Task Information');
        $response->assertDontSee('Placement Category');
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

    public function test_save_as_draft_preserves_task_content_and_redirects_to_edit(): void
    {
        $group = TaskGroup::create([
            'title'      => 'Activity Container ' . uniqid(),
            'created_by' => $this->admin->id,
        ]);

        $taskTitle = 'Draft Action Plan ' . uniqid();
        $response = $this->actingAs($this->admin)->post(route('manage.task.store'), [
            'parent_type' => 'standalone',
            'parent_id'   => $group->id,
            'title'       => $taskTitle,
            'description' => 'Detailed draft instructions here',
            'type'        => 'action_plan',
            'action'      => 'draft',
            'fields'      => [
                [
                    'label'      => 'Evidence attachment',
                    'type'       => 'file',
                    'required'   => 1,
                    'file_types' => 'document',
                    'max_files'  => 1,
                    'max_size_mb'=> 5,
                ],
            ],
        ]);

        $task = Task::where('title', $taskTitle)->first();
        $this->assertNotNull($task);
        $this->assertEquals('draft', $task->status);

        // Redirects to edit page with data preserved, NOT to a blank create page
        $response->assertRedirect(route('manage.task.edit', [
            'task'        => $task->id,
            'parent_type' => 'standalone',
            'parent_id'   => $group->id,
        ]));

        $editPage = $this->actingAs($this->admin)->get(route('manage.task.edit', [
            'task'        => $task->id,
            'parent_type' => 'standalone',
            'parent_id'   => $group->id,
        ]));

        $editPage->assertStatus(200);
        $editPage->assertSee($taskTitle);
        $editPage->assertSee('Evidence attachment');
        $editPage->assertSee('Save as Draft');
        $editPage->assertSee('Publish Task');
    }

    public function test_admin_course_task_list_renders_created_at_column(): void
    {
        $group = TaskGroup::create([
            'title'      => 'Test Container ' . uniqid(),
            'created_by' => $this->admin->id,
            'created_at' => now()->subDays(5),
        ]);

        Task::create([
            'title'         => 'Test Task Inside Container ' . uniqid(),
            'parent_type'   => 'standalone',
            'task_group_id' => $group->id,
            'type'          => 'assignment',
            'status'        => 'published',
            'created_by'    => $this->admin->id,
        ]);

        $response = $this->actingAs($this->admin)->get(route('manage.task.index'));

        $response->assertStatus(200);
        $response->assertSee('CREATED AT');
        $response->assertSee($group->created_at->format('d M Y'));
    }

    public function test_empty_container_without_tasks_is_rendered_and_newest_at_top(): void
    {
        $newTitle = 'Fresh Empty Container ' . uniqid();
        $group = TaskGroup::create([
            'title'      => $newTitle,
            'created_by' => $this->admin->id,
            'created_at' => now()->addMinutes(10),
        ]);

        $response = $this->actingAs($this->admin)->get(route('manage.task.index'));

        $response->assertStatus(200);
        $response->assertSee($newTitle);

        $parents = $response->viewData('parents');
        $this->assertNotEmpty($parents);
        $this->assertEquals($newTitle, $parents->first()->parent_name);
        $this->assertEquals(0, $parents->first()->total);
    }

    public function test_modal_renders_category_dropdown_with_content_playlist_and_options_endpoint(): void
    {
        $response = $this->actingAs($this->admin)->get(route('manage.task.index'));

        $response->assertStatus(200);
        $response->assertSee('id="modalCategorySelect"', false);
        $response->assertSee('value="content_playlist"', false);
        $response->assertSee('Content Playlist');
        $response->assertSee('id="modal_content_playlist_id"', false);

        // Create a playlist and content linked via trainingplandetail
        $plan = TrainingPlan::create([
            'title'  => 'Special Playlist Test ' . uniqid(),
            'slug'   => 'special-playlist-test-' . uniqid(),
            'active' => 'Active',
        ]);
        $content = Content::create([
            'title'        => 'Special Content Video ' . uniqid(),
            'type'         => 'video',
            'status'       => 'mandatory',
            'playlistonly' => 'N',
            'active'       => 'Active',
        ]);
        $plan->contents()->attach($content->id, [
            'content_order' => 1,
            'created_at'    => now(),
            'updated_at'    => now(),
        ]);

        // Test the content_playlist parentOptions endpoint
        $optionsResponse = $this->actingAs($this->admin)->getJson(route('manage.task.parents', [
            'type' => 'content_playlist',
            'q'    => $content->title,
        ]));
        $optionsResponse->assertStatus(200);
        $items = $optionsResponse->json();
        $this->assertIsArray($items);

        $matched = collect($items)->firstWhere('id', $content->id);
        $this->assertNotNull($matched);
        $this->assertStringContainsString($content->title, $matched['title']);
        $this->assertStringContainsString($plan->title, $matched['title']);

        // Search by playlist title
        $searchResponse = $this->actingAs($this->admin)->getJson(route('manage.task.parents', [
            'type' => 'content_playlist',
            'q'    => $plan->title,
        ]));
        $searchResponse->assertStatus(200);
        $searchItems = $searchResponse->json();
        $this->assertTrue(collect($searchItems)->contains('id', $content->id));
    }

    public function test_file_submission_form_hides_add_question_and_defaults_label(): void
    {
        $group = TaskGroup::create([
            'title'      => 'Group FS ' . uniqid(),
            'created_by' => $this->admin->id,
        ]);

        // 1. Check create view for file_submission (default) hides Add Question
        $createFs = $this->actingAs($this->admin)->get(route('manage.task.create'));
        $createFs->assertStatus(200);
        $createFs->assertSee('id="addQuestionWrap" class="mt-3" style="display:none;"', false);
        $createFs->assertSee('File Submission Requirements');

        // 2. Check create view for action_plan displays Add Question
        $createAp = $this->actingAs($this->admin)->get(route('manage.task.create', ['type' => 'action_plan']));
        $createAp->assertStatus(200);
        $createAp->assertSee('id="addQuestionWrap" class="mt-3" style=""', false);

        // 3. Storing file_submission without label defaults to 'File Submission'
        $taskTitle = 'FS Test ' . uniqid();
        $storeResp = $this->actingAs($this->admin)->post(route('manage.task.store'), [
            'parent_type' => 'standalone',
            'parent_id'   => $group->id,
            'title'       => $taskTitle,
            'description' => 'Test file submission description',
            'type'        => 'file_submission',
            'action'      => 'publish',
            'points'      => 100,
            'fields'      => [
                0 => [
                    'label'       => '',
                    'type'        => 'file',
                    'required'    => '1',
                    'file_types'  => 'document',
                    'max_files'   => 2,
                    'max_size_mb' => 10,
                ],
            ],
        ]);

        $task = Task::where('title', $taskTitle)->first();
        $this->assertNotNull($task);
        $this->assertEquals('file_submission', $task->type);

        $field = $task->fields()->first();
        $this->assertNotNull($field);
        $this->assertEquals('File Submission', $field->label);
        $this->assertEquals('file', $field->type);
    }
}

