<?php

namespace Tests\Feature;

use App\Models\Task;
use App\Models\TaskField;
use App\Models\TaskSubmission;
use App\Models\TaskSubmissionAnswer;
use App\Models\TrainingPlan;
use App\Models\User;
use App\Services\DocumentPreviewService;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class TaskDocumentPreviewTest extends TestCase
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
            'title'       => 'Doc Preview Test Course ' . uniqid(),
            'slug'        => 'doc-preview-course-' . uniqid(),
            'description' => 'Test Course Plan',
            'is_active'   => 1,
        ]);
    }

    public function test_document_preview_service_renders_docx(): void
    {
        $service = new DocumentPreviewService();
        $sampleDocx = 'task-submissions/16/iq3cJY6jFnXMJDZYxe9FwsJzy1aIlcGX1eiWLJRQ.docx';

        $html = $service->render($sampleDocx);

        $this->assertStringContainsString('docx-paper', $html);
        $this->assertStringContainsString('html', strtolower($html));
    }

    public function test_preview_document_route_returns_200_for_valid_docx(): void
    {
        $task = Task::create([
            'parent_type'      => 'playlist',
            'training_plan_id' => $this->plan->id,
            'title'            => 'Task Preview Docx ' . uniqid(),
            'type'             => 'open_ended',
            'status'           => 'published',
            'created_by'       => $this->admin->id,
        ]);

        $sampleDocx = 'task-submissions/16/iq3cJY6jFnXMJDZYxe9FwsJzy1aIlcGX1eiWLJRQ.docx';

        $response = $this->actingAs($this->admin)->get(route('manage.task.submissions.preview-doc', [
            'task' => $task->id,
            'path' => $sampleDocx,
        ]));

        $response->assertStatus(200);
        $response->assertHeader('Content-Type', 'text/html; charset=UTF-8');
        $this->assertStringContainsString('docx-paper', $response->getContent());
    }

    public function test_preview_document_blocks_directory_traversal(): void
    {
        $task = Task::create([
            'parent_type'      => 'playlist',
            'training_plan_id' => $this->plan->id,
            'title'            => 'Task Security Test ' . uniqid(),
            'type'             => 'open_ended',
            'status'           => 'published',
            'created_by'       => $this->admin->id,
        ]);

        $response = $this->actingAs($this->admin)->get(route('manage.task.submissions.preview-doc', [
            'task' => $task->id,
            'path' => '../../../../windows/win.ini',
        ]));

        $response->assertStatus(403);
    }

    public function test_preview_document_requires_path_parameter(): void
    {
        $task = Task::create([
            'parent_type'      => 'playlist',
            'training_plan_id' => $this->plan->id,
            'title'            => 'Task Missing Path ' . uniqid(),
            'type'             => 'open_ended',
            'status'           => 'published',
            'created_by'       => $this->admin->id,
        ]);

        $response = $this->actingAs($this->admin)->get(route('manage.task.submissions.preview-doc', [
            'task' => $task->id,
        ]));

        $response->assertStatus(400);
    }

    public function test_submissions_view_renders_preview_iframe_for_docx_and_no_unsupported_warning(): void
    {
        $task = Task::create([
            'parent_type'      => 'playlist',
            'training_plan_id' => $this->plan->id,
            'title'            => 'Task Submissions Docx View ' . uniqid(),
            'type'             => 'file_submission',
            'status'           => 'published',
            'created_by'       => $this->admin->id,
        ]);

        $field = TaskField::create([
            'task_id'    => $task->id,
            'type'       => 'file',
            'label'      => 'Upload your report (DOCX)',
            'required'   => true,
            'sort_order' => 0,
        ]);

        $sampleDocx = 'task-submissions/16/iq3cJY6jFnXMJDZYxe9FwsJzy1aIlcGX1eiWLJRQ.docx';

        $submission = TaskSubmission::create([
            'task_id'            => $task->id,
            'user_id'            => $this->learner->id,
            'status'             => 'pending',
            'resubmit_requested' => false,
            'submitted_at'       => now(),
            'answers'            => [
                $field->id => [
                    'task_field_id' => $field->id,
                    'answer_text'   => null,
                    'file_path'     => [$sampleDocx],
                ],
            ],
        ]);

        $response = $this->actingAs($this->admin)->get(route('manage.task.submissions', ['task' => $task->id]));

        $response->assertStatus(200);

        // Must render preview-document route iframe
        $response->assertSee('submissions/preview-document');
        $response->assertSee('DOCX');

        // Must NOT render the old "Preview not directly supported" warning
        $response->assertDontSee('Preview not directly supported for DOCX files');
    }

    public function test_document_preview_service_renders_xlsx(): void
    {
        $service = new DocumentPreviewService();
        $sampleXlsx = 'Worksheet_Progress_Task_Management_OWL.xlsx';

        $html = $service->render($sampleXlsx);

        $this->assertStringContainsString('sheet-wrapper', $html);
        $this->assertStringContainsString('table', strtolower($html));
    }

    public function test_document_preview_handles_missing_file_gracefully(): void
    {
        $service = new DocumentPreviewService();
        $html = $service->render('non-existent-folder/missing-file.docx');

        $this->assertStringContainsString('File Not Found', $html);
    }

    public function test_submissions_view_renders_preview_iframe_for_action_plan_task(): void
    {
        $task = Task::create([
            'parent_type'      => 'playlist',
            'training_plan_id' => $this->plan->id,
            'title'            => 'Task Action Plan Docx View ' . uniqid(),
            'type'             => 'action_plan',
            'status'           => 'published',
            'created_by'       => $this->admin->id,
        ]);

        $field = TaskField::create([
            'task_id'    => $task->id,
            'type'       => 'file',
            'label'      => 'Upload your file',
            'required'   => true,
            'sort_order' => 0,
        ]);

        $sampleDocx = 'task-submissions/16/iq3cJY6jFnXMJDZYxe9FwsJzy1aIlcGX1eiWLJRQ.docx';

        TaskSubmission::create([
            'task_id'            => $task->id,
            'user_id'            => $this->learner->id,
            'status'             => 'pending',
            'resubmit_requested' => false,
            'submitted_at'       => now(),
            'answers'            => [
                $field->id => [
                    'task_field_id' => $field->id,
                    'answer_text'   => null,
                    'file_path'     => [$sampleDocx],
                ],
            ],
        ]);

        $response = $this->actingAs($this->admin)->get(route('manage.task.submissions', ['task' => $task->id]));

        $response->assertStatus(200);
        $response->assertSee('submissions/preview-document');
        $response->assertSee('DOCX');
    }

    public function test_document_preview_service_renders_pptx(): void
    {
        $service = new DocumentPreviewService();
        $samplePptx = 'task-submissions/2387/OKAAsBTOrrIXktq43tE2RUAJwvrVk3dvJS9RpxWY.pptx';

        $html = $service->render($samplePptx);

        $this->assertStringContainsString('pptx-slide-canvas', $html);
        $this->assertStringContainsString('Slide 1 of 15', $html);
        $this->assertStringContainsString('pptx-toolbar', $html);
        $this->assertStringNotContainsString('Direct HTML preview is not available', $html);
    }

    public function test_preview_document_route_returns_200_for_valid_pptx(): void
    {
        $task = Task::create([
            'parent_type'      => 'playlist',
            'training_plan_id' => $this->plan->id,
            'title'            => 'Task Preview PPTX ' . uniqid(),
            'type'             => 'open_ended',
            'status'           => 'published',
            'created_by'       => $this->admin->id,
        ]);

        $samplePptx = 'task-submissions/2387/OKAAsBTOrrIXktq43tE2RUAJwvrVk3dvJS9RpxWY.pptx';

        $response = $this->actingAs($this->admin)->get(route('manage.task.submissions.preview-doc', [
            'task' => $task->id,
            'path' => $samplePptx,
        ]));

        $response->assertStatus(200);
        $response->assertHeader('Content-Type', 'text/html; charset=UTF-8');
        $this->assertStringContainsString('pptx-slide-canvas', $response->getContent());
        $this->assertStringContainsString('Slide 1 of 15', $response->getContent());
    }
}
