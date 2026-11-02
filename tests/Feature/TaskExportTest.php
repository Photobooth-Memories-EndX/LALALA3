<?php

namespace Tests\Feature;

use App\Models\Task;
use App\Models\TaskField;
use App\Models\TaskSubmission;
use App\Models\TrainingPlan;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class TaskExportTest extends TestCase
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
            'title'       => 'Export Test Course ' . uniqid(),
            'slug'        => 'export-course-' . uniqid(),
            'description' => 'Test Course Plan',
            'is_active'   => 1,
        ]);
    }

    public function test_admin_submissions_page_renders_google_forms_export_menu(): void
    {
        $task = Task::create([
            'parent_type'      => 'playlist',
            'training_plan_id' => $this->plan->id,
            'title'            => 'Task Export Menu View ' . uniqid(),
            'type'             => 'open_ended',
            'status'           => 'published',
            'created_by'       => $this->admin->id,
        ]);

        $response = $this->actingAs($this->admin)->get(route('manage.task.submissions', ['task' => $task->id]));

        $response->assertStatus(200);
        $response->assertSee('Export to Excel');
        $response->assertSee('btnExportExcel');
        $response->assertSee('btnExportOptions');
        $response->assertSee('Microsoft Excel (.xlsx)');
        $response->assertSee('Comma-Separated (.csv)');
        $response->assertSee('Download Attachments (.zip)');
    }

    public function test_export_submissions_route_downloads_excel_file(): void
    {
        $task = Task::create([
            'parent_type'      => 'playlist',
            'training_plan_id' => $this->plan->id,
            'title'            => 'Task Export Excel ' . uniqid(),
            'type'             => 'open_ended',
            'status'           => 'published',
            'created_by'       => $this->admin->id,
        ]);

        $field = TaskField::create([
            'task_id'    => $task->id,
            'type'       => 'short_answer',
            'label'      => 'Full Name',
            'required'   => true,
            'sort_order' => 0,
        ]);

        TaskSubmission::create([
            'task_id'            => $task->id,
            'user_id'            => $this->learner->id,
            'status'             => 'completed',
            'resubmit_requested' => false,
            'submitted_at'       => now(),
            'score'              => 95,
            'feedback'           => 'Great job!',
            'answers'            => [
                $field->id => [
                    'task_field_id' => $field->id,
                    'answer_text'   => 'John Doe',
                    'file_path'     => [],
                ],
            ],
        ]);

        $response = $this->actingAs($this->admin)->get(route('manage.task.submissions.export', [
            'task'   => $task->id,
            'format' => 'xlsx',
        ]));

        $response->assertStatus(200);
        $this->assertTrue(
            str_contains((string) $response->headers->get('content-disposition'), '.xlsx')
        );
    }

    public function test_export_submissions_route_downloads_csv_file(): void
    {
        $task = Task::create([
            'parent_type'      => 'playlist',
            'training_plan_id' => $this->plan->id,
            'title'            => 'Task Export CSV ' . uniqid(),
            'type'             => 'open_ended',
            'status'           => 'published',
            'created_by'       => $this->admin->id,
        ]);

        $response = $this->actingAs($this->admin)->get(route('manage.task.submissions.export', [
            'task'   => $task->id,
            'format' => 'csv',
        ]));

        $response->assertStatus(200);
        $this->assertTrue(
            str_contains((string) $response->headers->get('content-disposition'), '.csv')
        );
    }

    public function test_export_submissions_route_downloads_zip_archive_of_attachments(): void
    {
        Storage::fake('public');

        $task = Task::create([
            'parent_type'      => 'playlist',
            'training_plan_id' => $this->plan->id,
            'title'            => 'Task Export ZIP ' . uniqid(),
            'type'             => 'file_submission',
            'status'           => 'published',
            'created_by'       => $this->admin->id,
        ]);

        $field = TaskField::create([
            'task_id'    => $task->id,
            'type'       => 'file',
            'label'      => 'Upload Document',
            'required'   => true,
            'sort_order' => 0,
        ]);

        // Put a fake file on public disk
        $filePath = 'task-submissions/' . $task->id . '/test_document.txt';
        Storage::disk('public')->put($filePath, 'Hello World Submission Content');

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
                    'file_path'     => [$filePath],
                ],
            ],
        ]);

        $response = $this->actingAs($this->admin)->get(route('manage.task.submissions.export', [
            'task'   => $task->id,
            'format' => 'zip',
        ]));

        $response->assertStatus(200);
        $this->assertTrue(
            str_contains((string) $response->headers->get('content-disposition'), '.zip')
        );
    }
}
