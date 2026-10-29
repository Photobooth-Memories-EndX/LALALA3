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

    public function test_admin_task_trash_page_renders_three_dots_action_menu(): void
    {
        $task = Task::create([
            'title'       => 'Trash Task ' . uniqid(),
            'type'        => 'upload',
            'parent_type' => 'standalone',
            'parent_id'   => null,
            'status'      => 'published',
            'created_by'  => $this->admin->id,
        ]);
        $task->delete();

        $response = $this->actingAs($this->admin)->get(route('manage.task.trash'));

        $response->assertStatus(200);
        $response->assertSee('trash-menu-btn', false);
        $response->assertSee('ti ti-dots', false);
        $response->assertSee('trashActionsMenu', false);
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

    public function test_admin_submissions_page_renders_single_review_action_and_resubmit_in_modal(): void
    {
        $task = Task::create([
            'parent_type'      => 'playlist',
            'training_plan_id' => $this->plan->id,
            'title'            => 'Submission Task ' . uniqid(),
            'type'             => 'open_ended',
            'status'           => 'published',
            'created_by'       => $this->admin->id,
        ]);

        $submission = \App\Models\TaskSubmission::create([
            'task_id'      => $task->id,
            'user_id'      => $this->regularUser->id,
            'status'       => 'pending',
            'submitted_at' => now(),
        ]);

        $response = $this->actingAs($this->admin)->get(route('manage.task.submissions', $task));

        $response->assertStatus(200);
        $response->assertSee('submission-menu-btn', false);
        $response->assertSee('Review Submission', false);
        $response->assertSee('id="reviewSubmissionModal"', false);
        $response->assertSee('id="modalResubmitBtn"', false);
        $response->assertSee('id="filePreviewModal"', false);
        $response->assertDontSee('id="resubmitModal"', false);
    }

    public function test_admin_can_request_resubmit_submission(): void
    {
        $task = Task::create([
            'parent_type'      => 'playlist',
            'training_plan_id' => $this->plan->id,
            'title'            => 'Task For Resubmit ' . uniqid(),
            'type'             => 'open_ended',
            'status'           => 'published',
            'created_by'       => $this->admin->id,
        ]);

        $submission = \App\Models\TaskSubmission::create([
            'task_id'      => $task->id,
            'user_id'      => $this->regularUser->id,
            'status'       => 'pending',
            'submitted_at' => now(),
        ]);

        $response = $this->actingAs($this->admin)->post(
            route('manage.task.submissions.resubmit', [$task, $submission]),
            ['feedback' => 'Please revise your document formatting.']
        );

        $response->assertRedirect();
        $submission->refresh();
        $this->assertTrue((bool)$submission->resubmit_requested);
        $this->assertEquals('pending', $submission->status);
        $this->assertEquals('Please revise your document formatting.', $submission->feedback);
    }

    public function test_admin_can_complete_submission(): void
    {
        $task = Task::create([
            'parent_type'      => 'playlist',
            'training_plan_id' => $this->plan->id,
            'title'            => 'Task For Complete ' . uniqid(),
            'type'             => 'open_ended',
            'status'           => 'published',
            'created_by'       => $this->admin->id,
        ]);

        $submission = \App\Models\TaskSubmission::create([
            'task_id'            => $task->id,
            'user_id'            => $this->regularUser->id,
            'status'             => 'pending',
            'resubmit_requested' => true,
            'submitted_at'       => now(),
        ]);

        $response = $this->actingAs($this->admin)->post(
            route('manage.task.submissions.complete', [$task, $submission]),
            [
                'score'    => 95,
                'feedback' => 'Great revision! Well done.',
            ]
        );

        $response->assertRedirect();
        $submission->refresh();
        $this->assertEquals('completed', $submission->status);
        $this->assertEquals(95, $submission->score);
        $this->assertEquals('Great revision! Well done.', $submission->feedback);
        $this->assertFalse((bool)$submission->resubmit_requested);
    }

    public function test_task_list_has_type_column(): void
    {
        $task = Task::create([
            'parent_type'      => 'playlist',
            'training_plan_id' => $this->plan->id,
            'title'            => 'Task Type Column ' . uniqid(),
            'type'             => 'essay',
            'status'           => 'published',
            'created_by'       => $this->admin->id,
        ]);

        $response = $this->actingAs($this->admin)->get(route('manage.task.parent', [
            'parentType' => 'playlist',
            'parentId'   => $this->plan->id,
        ]));

        $response->assertStatus(200);
        $response->assertSee('<th>Type</th>', false);
        $response->assertSee($task->typeLabel(), false);
    }

    public function test_submissions_table_does_not_render_submission_column(): void
    {
        $task = Task::create([
            'parent_type'      => 'playlist',
            'training_plan_id' => $this->plan->id,
            'title'            => 'Task Submissions No Sub Column ' . uniqid(),
            'type'             => 'essay',
            'status'           => 'published',
            'created_by'       => $this->admin->id,
        ]);

        $field = \App\Models\TaskField::create([
            'task_id'  => $task->id,
            'label'    => 'Question 1',
            'type'     => 'textarea',
            'order'    => 1,
            'required' => true,
        ]);

        $submission = \App\Models\TaskSubmission::create([
            'task_id'      => $task->id,
            'user_id'      => $this->regularUser->id,
            'status'       => 'pending',
            'submitted_at' => now(),
            'answers'      => [
                $field->id => [
                    'task_field_id' => $field->id,
                    'answer_text'   => 'This is a sample test submission answer text',
                ],
            ],
        ]);

        $response = $this->actingAs($this->admin)->get(route('manage.task.submissions', $task));

        $response->assertStatus(200);
        // Table header must not contain SUBMISSION & ANSWERS
        $response->assertDontSee('SUBMISSION &amp; ANSWERS', false);
        $response->assertDontSee('SUBMISSION & ANSWERS', false);

        // Content should still exist in the review modal template
        $response->assertSee('id="submissionContent-' . $submission->id . '"', false);
        $response->assertSee('This is a sample test submission answer text', false);
    }

    public function test_task_preview_modals_have_zoom_controls_and_close_buttons(): void
    {
        $task = Task::create([
            'parent_type'      => 'playlist',
            'training_plan_id' => $this->plan->id,
            'title'            => 'Task Zoom Modal Test ' . uniqid(),
            'type'             => 'file_submission',
            'status'           => 'published',
            'created_by'       => $this->admin->id,
        ]);

        // 1. Submissions page review workstation has clean canvas (obsolete zoom toolbar removed)
        $subResponse = $this->actingAs($this->admin)->get(route('manage.task.submissions', $task));
        $subResponse->assertStatus(200);
        $subResponse->assertDontSee('id="previewZoomToolbar"', false);
        $subResponse->assertDontSee('id="previewZoomInBtn"', false);
        $subResponse->assertDontSee('id="previewZoomOutBtn"', false);
        $subResponse->assertDontSee('id="previewZoomResetBtn"', false);

        // 2. Task create/edit page preview modal
        $createResponse = $this->actingAs($this->admin)->get(route('manage.task.create'));
        $createResponse->assertStatus(200);
        $createResponse->assertSee('id="taskPreviewZoomToolbar"', false);
        $createResponse->assertSee('id="taskPreviewZoomInBtn"', false);
        $createResponse->assertSee('id="taskPreviewZoomOutBtn"', false);
        $createResponse->assertSee('id="taskPreviewZoomResetBtn"', false);
        $createResponse->assertSee('id="taskPreviewZoomWrapper"', false);
    }

    public function test_file_preview_modal_is_english_no_new_tab_and_download_only_in_popup(): void
    {
        $task = Task::create([
            'parent_type'      => 'playlist',
            'training_plan_id' => $this->plan->id,
            'title'            => 'Task File Preview Modal Test ' . uniqid(),
            'type'             => 'file_submission',
            'status'           => 'published',
            'created_by'       => $this->admin->id,
        ]);

        $field = \App\Models\TaskField::create([
            'task_id'    => $task->id,
            'type'       => 'file',
            'label'      => 'Upload Project File',
            'required'   => true,
            'sort_order' => 1,
        ]);

        $submission = \App\Models\TaskSubmission::create([
            'task_id'      => $task->id,
            'user_id'      => $this->regularUser->id,
            'status'       => 'pending',
            'submitted_at' => now(),
            'answers'      => [
                $field->id => [
                    'task_field_id' => $field->id,
                    'file_path'     => ['tasks/test_project.pdf', 'tasks/screenshot.png'],
                ],
            ],
        ]);

        $response = $this->actingAs($this->admin)->get(route('manage.task.submissions', $task));
        $response->assertStatus(200);

        // 1. File cards and fullscreen popup have download buttons
        $response->assertSee('id="filePreviewModal"', false);
        $response->assertSee('id="fileFullscreenModal"', false);
        $response->assertSee('id="fsModalDownloadBtn"', false);
        $response->assertSee('download="test_project.pdf"', false);

        // 2. No "New Tab" button in the popup
        $response->assertDontSee('id="previewOpenTabBtn"', false);
        $response->assertDontSee('New Tab', false);

        // 3. Workstation controls are in English
        $response->assertSee('Close &amp; Return to Review', false);
        $response->assertDontSee('Sedang melihat pratinjau file submission.', false);
        $response->assertDontSee('Tutup &amp; Kembali ke Review', false);

        // 4. File preview is directly embedded inline without needing to click View File first
        $responseContent = $response->getContent();
        // PDF iframe embedded
        $this->assertStringContainsString('tasks/test_project.pdf', $responseContent);
        $this->assertStringContainsString('<iframe src="', $responseContent);
        $this->assertStringContainsString('test_project.pdf', $responseContent);
        // Image img tag embedded directly and is clickable
        $this->assertStringContainsString('tasks/screenshot.png', $responseContent);
        $this->assertStringContainsString('<img src="', $responseContent);
        $this->assertStringContainsString('screenshot.png', $responseContent);
        $this->assertStringContainsString('preview-image-clickable', $responseContent);
        $this->assertStringContainsString('btn-open-fullscreen', $responseContent);
        $this->assertStringContainsString('Fullscreen', $responseContent);
    }
}



