<?php

namespace Tests\Feature;

use App\Models\Task;
use App\Models\TaskField;
use App\Models\TaskPlaylistUnlock;
use App\Models\TaskSubmission;
use App\Models\TrainingPlan;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class TaskReviewStatusFlowTest extends TestCase
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
            'title'       => 'Review Status Flow Course ' . uniqid(),
            'slug'        => 'review-status-flow-course-' . uniqid(),
            'description' => 'Test Course Plan',
            'is_active'   => 1,
        ]);

        view()->share('errors', new \Illuminate\Support\ViewErrorBag());
    }

    public function test_user_status_is_pending_review_when_submitted(): void
    {
        $task = Task::create([
            'parent_type'      => 'playlist',
            'training_plan_id' => $this->plan->id,
            'title'            => 'Task Pending Review ' . uniqid(),
            'type'             => 'open_ended',
            'status'           => 'published',
            'created_by'       => $this->admin->id,
        ]);

        $submission = TaskSubmission::create([
            'task_id'            => $task->id,
            'user_id'            => $this->learner->id,
            'status'             => 'pending',
            'resubmit_requested' => false,
            'submitted_at'       => now(),
        ]);

        $this->assertEquals('submitted', $task->userStatusFor($this->learner));
    }

    public function test_admin_can_approve_submission_and_user_status_is_completed(): void
    {
        $task = Task::create([
            'parent_type'      => 'playlist',
            'training_plan_id' => $this->plan->id,
            'title'            => 'Task Approve ' . uniqid(),
            'type'             => 'open_ended',
            'status'           => 'published',
            'created_by'       => $this->admin->id,
        ]);

        $submission = TaskSubmission::create([
            'task_id'            => $task->id,
            'user_id'            => $this->learner->id,
            'status'             => 'pending',
            'resubmit_requested' => false,
            'submitted_at'       => now(),
        ]);

        $response = $this->actingAs($this->admin)->post(
            route('manage.task.submissions.complete', [$task, $submission]),
            [
                'score'    => 90,
                'feedback' => 'Approved with excellence!',
            ]
        );

        $response->assertRedirect();
        $submission->refresh();

        $this->assertEquals('completed', $submission->status);
        $this->assertFalse((bool)$submission->resubmit_requested);
        $this->assertEquals(90, $submission->score);
        $this->assertEquals('Approved with excellence!', $submission->feedback);

        // User side status
        $this->assertEquals('completed', $task->userStatusFor($this->learner));
    }

    public function test_admin_can_reject_submission_with_request_resubmit(): void
    {
        $task = Task::create([
            'parent_type'      => 'playlist',
            'training_plan_id' => $this->plan->id,
            'title'            => 'Task Reject Resubmit ' . uniqid(),
            'type'             => 'open_ended',
            'status'           => 'published',
            'created_by'       => $this->admin->id,
        ]);

        $submission = TaskSubmission::create([
            'task_id'            => $task->id,
            'user_id'            => $this->learner->id,
            'status'             => 'pending',
            'resubmit_requested' => false,
            'submitted_at'       => now(),
        ]);

        $response = $this->actingAs($this->admin)->post(
            route('manage.task.submissions.reject', [$task, $submission]),
            [
                'reject_type' => 'resubmit',
                'feedback'    => 'Please provide more details on question 2.',
                'score'       => null,
            ]
        );

        $response->assertRedirect();
        $submission->refresh();

        $this->assertEquals('rejected', $submission->status);
        $this->assertTrue((bool)$submission->resubmit_requested);
        $this->assertEquals('Please provide more details on question 2.', $submission->feedback);

        // User side status is 'rejected'
        $this->assertEquals('rejected', $task->userStatusFor($this->learner));
    }

    public function test_admin_can_reject_submission_with_completed_as_rejected(): void
    {
        $task = Task::create([
            'parent_type'      => 'playlist',
            'training_plan_id' => $this->plan->id,
            'title'            => 'Task Reject Completed ' . uniqid(),
            'type'             => 'open_ended',
            'status'           => 'published',
            'created_by'       => $this->admin->id,
        ]);

        $submission = TaskSubmission::create([
            'task_id'            => $task->id,
            'user_id'            => $this->learner->id,
            'status'             => 'pending',
            'resubmit_requested' => false,
            'submitted_at'       => now(),
        ]);

        $response = $this->actingAs($this->admin)->post(
            route('manage.task.submissions.reject', [$task, $submission]),
            [
                'reject_type' => 'completed_rejected',
                'feedback'    => 'Criteria not met, assignment closed.',
                'score'       => 40,
            ]
        );

        $response->assertRedirect();
        $submission->refresh();

        $this->assertEquals('rejected', $submission->status);
        $this->assertFalse((bool)$submission->resubmit_requested);
        $this->assertEquals(40, $submission->score);
        $this->assertEquals('Criteria not met, assignment closed.', $submission->feedback);

        // User side status is 'rejected'
        $this->assertEquals('rejected', $task->userStatusFor($this->learner));
    }

    public function test_admin_submissions_view_contains_decision_options(): void
    {
        $task = Task::create([
            'parent_type'      => 'playlist',
            'training_plan_id' => $this->plan->id,
            'title'            => 'Task View Submissions ' . uniqid(),
            'type'             => 'open_ended',
            'status'           => 'published',
            'created_by'       => $this->admin->id,
        ]);

        TaskSubmission::create([
            'task_id'            => $task->id,
            'user_id'            => $this->learner->id,
            'status'             => 'pending',
            'resubmit_requested' => false,
            'submitted_at'       => now(),
        ]);

        $response = $this->actingAs($this->admin)->get(route('manage.task.submissions', $task));
        $response->assertStatus(200);

        // Approve and Rejected options exist at bottom
        $response->assertSee('id="btnActionApprove"', false);
        $response->assertSee('id="btnActionReject"', false);
        $response->assertSee('id="rejectedOptionsWrapper"', false);
        $response->assertSee('id="alreadyGradedBanner"', false);
        $response->assertSee('id="reviewConfirmModal"', false);
        $response->assertSee('id="reviewRejectModal"', false);
        $response->assertSee('id="rejectModalFeedbackInput"', false);
        $response->assertSee('Request Resubmit', false);
        $response->assertSee('Completed as Rejected', false);
    }

    public function test_user_task_card_renders_completed_status(): void
    {
        $task = Task::create([
            'parent_type'      => 'playlist',
            'training_plan_id' => $this->plan->id,
            'title'            => 'Task Card Completed ' . uniqid(),
            'type'             => 'open_ended',
            'status'           => 'published',
            'created_by'       => $this->admin->id,
        ]);

        TaskSubmission::create([
            'task_id'            => $task->id,
            'user_id'            => $this->learner->id,
            'status'             => 'completed',
            'score'              => 98,
            'feedback'           => 'Well done!',
            'resubmit_requested' => false,
            'submitted_at'       => now(),
        ]);

        $this->actingAs($this->learner);
        $html = view('user.learning.dashboard-partials.my-task-card', [
            'task'          => $task,
            'inModal'       => true,
            'statusLabels'  => ['completed' => 'Completed', 'submitted' => 'Pending review', 'rejected' => 'Rejected'],
            'statusBadges'  => ['completed' => 'bg-success-lt text-success', 'submitted' => 'bg-warning-lt text-warning', 'rejected' => 'bg-danger-lt text-danger'],
            'displayStatus' => fn ($s) => $s,
        ])->render();

        $this->assertStringContainsString('Completed', $html);
        $this->assertStringContainsString('Reviewed by admin', $html);
        $this->assertStringContainsString('Score: 98', $html);
        $this->assertStringContainsString('Well done!', $html);
    }

    public function test_user_task_card_renders_pending_review_status(): void
    {
        $task = Task::create([
            'parent_type'      => 'playlist',
            'training_plan_id' => $this->plan->id,
            'title'            => 'Task Card Pending ' . uniqid(),
            'type'             => 'open_ended',
            'status'           => 'published',
            'created_by'       => $this->admin->id,
        ]);

        TaskSubmission::create([
            'task_id'            => $task->id,
            'user_id'            => $this->learner->id,
            'status'             => 'pending',
            'resubmit_requested' => false,
            'submitted_at'       => now(),
        ]);

        $this->actingAs($this->learner);
        $html = view('user.learning.dashboard-partials.my-task-card', [
            'task'          => $task,
            'inModal'       => true,
            'statusLabels'  => ['completed' => 'Completed', 'submitted' => 'Pending review', 'rejected' => 'Rejected'],
            'statusBadges'  => ['completed' => 'bg-success-lt text-success', 'submitted' => 'bg-warning-lt text-warning', 'rejected' => 'bg-danger-lt text-danger'],
            'displayStatus' => fn ($s) => $s,
        ])->render();

        $this->assertStringContainsString('Pending review', $html);
        $this->assertStringContainsString('waiting for admin review', $html);
    }

    public function test_user_task_card_renders_rejected_with_resubmit(): void
    {
        $task = Task::create([
            'parent_type'      => 'playlist',
            'training_plan_id' => $this->plan->id,
            'title'            => 'Task Card Resubmit ' . uniqid(),
            'type'             => 'open_ended',
            'status'           => 'published',
            'created_by'       => $this->admin->id,
        ]);

        TaskSubmission::create([
            'task_id'            => $task->id,
            'user_id'            => $this->learner->id,
            'status'             => 'rejected',
            'resubmit_requested' => true,
            'feedback'           => 'Fix section 1 and resubmit',
            'submitted_at'       => now(),
        ]);

        $this->actingAs($this->learner);
        $html = view('user.learning.dashboard-partials.my-task-card', [
            'task'          => $task,
            'inModal'       => true,
            'statusLabels'  => ['completed' => 'Completed', 'submitted' => 'Pending review', 'rejected' => 'Rejected'],
            'statusBadges'  => ['completed' => 'bg-success-lt text-success', 'submitted' => 'bg-warning-lt text-warning', 'rejected' => 'bg-danger-lt text-danger'],
            'displayStatus' => fn ($s) => $s,
        ])->render();

        $this->assertStringContainsString('Rejected', $html);
        $this->assertStringContainsString('Task Revision Requested', $html);
        $this->assertStringContainsString('Fix section 1 and resubmit', $html);
        $this->assertStringContainsString('Resubmit Answers', $html);
    }

    public function test_user_task_card_renders_completed_as_rejected_without_resubmit_form(): void
    {
        $task = Task::create([
            'parent_type'      => 'playlist',
            'training_plan_id' => $this->plan->id,
            'title'            => 'Task Card Completed Rejected ' . uniqid(),
            'type'             => 'open_ended',
            'status'           => 'published',
            'created_by'       => $this->admin->id,
        ]);

        TaskSubmission::create([
            'task_id'            => $task->id,
            'user_id'            => $this->learner->id,
            'status'             => 'rejected',
            'resubmit_requested' => false,
            'score'              => 35,
            'feedback'           => 'Final rejection due to plagiarized answer',
            'submitted_at'       => now(),
        ]);

        $this->actingAs($this->learner);
        $html = view('user.learning.dashboard-partials.my-task-card', [
            'task'          => $task,
            'inModal'       => true,
            'statusLabels'  => ['completed' => 'Completed', 'submitted' => 'Pending review', 'rejected' => 'Rejected'],
            'statusBadges'  => ['completed' => 'bg-success-lt text-success', 'submitted' => 'bg-warning-lt text-warning', 'rejected' => 'bg-danger-lt text-danger'],
            'displayStatus' => fn ($s) => $s,
        ])->render();

        $this->assertStringContainsString('Rejected', $html);
        $this->assertStringContainsString('Submission Rejected', $html);
        $this->assertStringContainsString('Completed as Rejected', $html);
        $this->assertStringContainsString('Score: 35 / 100', $html);
        $this->assertStringContainsString('Final rejection due to plagiarized answer', $html);
        // Form should NOT be rendered
        $this->assertStringNotContainsString('Resubmit Answers', $html);
        $this->assertStringNotContainsString('User Submission Form', $html);
        $this->assertStringNotContainsString('Learner Submission Form', $html);
    }

    public function test_admin_cannot_regrade_already_completed_submission(): void
    {
        $task = Task::create([
            'parent_type'      => 'playlist',
            'training_plan_id' => $this->plan->id,
            'title'            => 'Task Cannot Regrade Completed ' . uniqid(),
            'type'             => 'open_ended',
            'status'           => 'published',
            'created_by'       => $this->admin->id,
        ]);

        $submission = TaskSubmission::create([
            'task_id'            => $task->id,
            'user_id'            => $this->learner->id,
            'status'             => 'completed',
            'resubmit_requested' => false,
            'score'              => 88,
            'feedback'           => 'Approved already.',
            'submitted_at'       => now(),
        ]);

        // Attempting to complete again
        $response = $this->actingAs($this->admin)->post(
            route('manage.task.submissions.complete', [$task, $submission]),
            ['score' => 90, 'feedback' => 'Trying to re-approve']
        );
        $response->assertRedirect();
        $response->assertSessionHas('error', 'This submission has already been graded and cannot be changed.');

        // Attempting to reject already completed
        $responseReject = $this->actingAs($this->admin)->post(
            route('manage.task.submissions.reject', [$task, $submission]),
            ['reject_type' => 'resubmit', 'feedback' => 'Trying to reject completed']
        );
        $responseReject->assertRedirect();
        $responseReject->assertSessionHas('error', 'This submission has already been graded and cannot be changed.');

        $submission->refresh();
        $this->assertEquals('completed', $submission->status);
        $this->assertEquals(88, $submission->score);
    }

    public function test_admin_cannot_regrade_already_completed_as_rejected_submission(): void
    {
        $task = Task::create([
            'parent_type'      => 'playlist',
            'training_plan_id' => $this->plan->id,
            'title'            => 'Task Cannot Regrade Rejected ' . uniqid(),
            'type'             => 'open_ended',
            'status'           => 'published',
            'created_by'       => $this->admin->id,
        ]);

        $submission = TaskSubmission::create([
            'task_id'            => $task->id,
            'user_id'            => $this->learner->id,
            'status'             => 'rejected',
            'resubmit_requested' => false,
            'score'              => 20,
            'feedback'           => 'Rejected permanently.',
            'submitted_at'       => now(),
        ]);

        // Attempting to complete already rejected
        $response = $this->actingAs($this->admin)->post(
            route('manage.task.submissions.complete', [$task, $submission]),
            ['score' => 70, 'feedback' => 'Trying to approve']
        );
        $response->assertRedirect();
        $response->assertSessionHas('error', 'This submission has already been graded and cannot be changed.');

        // Attempting to reject again
        $responseReject = $this->actingAs($this->admin)->post(
            route('manage.task.submissions.reject', [$task, $submission]),
            ['reject_type' => 'completed_rejected', 'feedback' => 'Trying to reject again']
        );
        $responseReject->assertRedirect();
        $responseReject->assertSessionHas('error', 'This submission has already been graded and cannot be changed.');

        $submission->refresh();
        $this->assertEquals('rejected', $submission->status);
        $this->assertFalse((bool)$submission->resubmit_requested);
        $this->assertEquals(20, $submission->score);
    }

    public function test_user_can_save_submission_as_draft(): void
    {
        TaskPlaylistUnlock::firstOrCreate([
            'user_id'          => $this->learner->id,
            'training_plan_id' => $this->plan->id,
        ]);

        $task = Task::create([
            'parent_type'      => 'playlist',
            'training_plan_id' => $this->plan->id,
            'title'            => 'Task Draft Flow ' . uniqid(),
            'type'             => 'open_ended',
            'status'           => 'published',
            'created_by'       => $this->admin->id,
        ]);

        $field1 = TaskField::create([
            'task_id'  => $task->id,
            'label'    => 'Required Question 1',
            'type'     => 'text',
            'required' => true,
            'order'    => 1,
        ]);

        $field2 = TaskField::create([
            'task_id'  => $task->id,
            'label'    => 'Optional Question 2',
            'type'     => 'paragraph',
            'required' => false,
            'order'    => 2,
        ]);

        // Saving as draft even though required field1 is empty
        $response = $this->actingAs($this->learner)->post(
            route('my-task.submit', $task),
            [
                'action' => 'draft',
                'fields' => [
                    $field1->id => '',
                    $field2->id => 'Draft notes for field 2',
                ],
            ]
        );

        $response->assertRedirect(route('user.learning.dashboard', [
            'learning_tab'   => 'my-task',
            'active_task_id' => $task->id,
        ]));
        $response->assertSessionHas('success', 'Task answers saved as draft. You can continue anytime before final submission.');

        $submission = TaskSubmission::where('task_id', $task->id)->where('user_id', $this->learner->id)->first();
        $this->assertNotNull($submission);
        $this->assertEquals('draft', $submission->status);
        $this->assertTrue($submission->isDraft());
        $this->assertEquals('in_progress', $task->userStatusFor($this->learner));

        $answers = $submission->answersByField();
        $this->assertEquals('Draft notes for field 2', $answers->get($field2->id)->answer_text);
    }

    public function test_user_can_submit_after_saving_draft(): void
    {
        TaskPlaylistUnlock::firstOrCreate([
            'user_id'          => $this->learner->id,
            'training_plan_id' => $this->plan->id,
        ]);

        $task = Task::create([
            'parent_type'      => 'playlist',
            'training_plan_id' => $this->plan->id,
            'title'            => 'Task Final Submit Flow ' . uniqid(),
            'type'             => 'open_ended',
            'status'           => 'published',
            'created_by'       => $this->admin->id,
        ]);

        $field = TaskField::create([
            'task_id'  => $task->id,
            'label'    => 'Final Exam Question',
            'type'     => 'paragraph',
            'required' => true,
            'order'    => 1,
        ]);

        // Create draft first
        TaskSubmission::create([
            'task_id'            => $task->id,
            'user_id'            => $this->learner->id,
            'status'             => 'draft',
            'resubmit_requested' => false,
            'answers'            => [
                $field->id => [
                    'task_field_id' => $field->id,
                    'answer_text'   => 'Initial draft answer',
                    'file_path'     => [],
                ],
            ],
        ]);

        // Now submit as final
        $response = $this->actingAs($this->learner)->post(
            route('my-task.submit', $task),
            [
                'action' => 'submit',
                'fields' => [
                    $field->id => 'Final polished answer',
                ],
            ]
        );

        $response->assertRedirect(route('user.learning.dashboard', [
            'learning_tab'   => 'my-task',
            'active_task_id' => $task->id,
        ]));
        $response->assertSessionHas('success', 'Answers submitted successfully. Waiting for admin review.');

        $submission = TaskSubmission::where('task_id', $task->id)->where('user_id', $this->learner->id)->first();
        $this->assertEquals('pending', $submission->status);
        $this->assertNotNull($submission->submitted_at);
        $this->assertEquals('submitted', $task->userStatusFor($this->learner));

        $answers = $submission->answersByField();
        $this->assertEquals('Final polished answer', $answers->get($field->id)->answer_text);
    }

    public function test_user_task_card_renders_draft_status_and_prefilled_answers(): void
    {
        $task = Task::create([
            'parent_type'      => 'playlist',
            'training_plan_id' => $this->plan->id,
            'title'            => 'Task Card Draft Render ' . uniqid(),
            'type'             => 'open_ended',
            'status'           => 'published',
            'created_by'       => $this->admin->id,
        ]);

        $field = TaskField::create([
            'task_id'  => $task->id,
            'label'    => 'Draft Question Label',
            'type'     => 'text',
            'required' => true,
            'order'    => 1,
        ]);

        TaskSubmission::create([
            'task_id'            => $task->id,
            'user_id'            => $this->learner->id,
            'status'             => 'draft',
            'resubmit_requested' => false,
            'answers'            => [
                $field->id => [
                    'task_field_id' => $field->id,
                    'answer_text'   => 'Saved Draft Answer 123',
                    'file_path'     => [],
                ],
            ],
        ]);

        $this->actingAs($this->learner);
        $html = view('user.learning.dashboard-partials.my-task-card', [
            'task'          => $task,
            'inModal'       => true,
            'statusLabels'  => ['in_progress' => 'In Progress', 'completed' => 'Completed', 'submitted' => 'Pending review', 'rejected' => 'Rejected'],
            'statusBadges'  => ['in_progress' => 'bg-primary-lt text-primary', 'completed' => 'bg-success-lt text-success', 'submitted' => 'bg-warning-lt text-warning', 'rejected' => 'bg-danger-lt text-danger'],
            'displayStatus' => fn ($s) => $s,
        ])->render();

        $this->assertStringContainsString('Draft', $html);
        $this->assertStringContainsString('Answers Saved as Draft', $html);
        $this->assertStringContainsString('Continue Draft Answers', $html);
        $this->assertStringContainsString('Save as Draft', $html);
        $this->assertStringContainsString('Submit Answers', $html);
        $this->assertStringContainsString('Saved Draft Answer 123', $html);
    }

    public function test_user_task_card_renders_full_submitted_answers_view(): void
    {
        $task = Task::create([
            'parent_type'      => 'playlist',
            'training_plan_id' => $this->plan->id,
            'title'            => 'Task Card View Submissions ' . uniqid(),
            'type'             => 'open_ended',
            'status'           => 'published',
            'created_by'       => $this->admin->id,
        ]);

        $field1 = TaskField::create([
            'task_id'  => $task->id,
            'label'    => 'Long Essay Prompt',
            'type'     => 'paragraph',
            'required' => true,
            'order'    => 1,
        ]);

        $field2 = TaskField::create([
            'task_id'  => $task->id,
            'label'    => 'Self Evaluation Rating',
            'type'     => 'rating',
            'required' => true,
            'order'    => 2,
        ]);

        TaskSubmission::create([
            'task_id'            => $task->id,
            'user_id'            => $this->learner->id,
            'status'             => 'pending',
            'resubmit_requested' => false,
            'submitted_at'       => now(),
            'answers'            => [
                $field1->id => [
                    'task_field_id' => $field1->id,
                    'answer_text'   => 'This is a complete and detailed untruncated essay answer that should be displayed in full inside the submission review box.',
                    'file_path'     => [],
                ],
                $field2->id => [
                    'task_field_id' => $field2->id,
                    'answer_text'   => '5',
                    'file_path'     => [],
                ],
            ],
        ]);

        $this->actingAs($this->learner);
        $html = view('user.learning.dashboard-partials.my-task-card', [
            'task'          => $task,
            'inModal'       => true,
            'statusLabels'  => ['completed' => 'Completed', 'submitted' => 'Pending review', 'rejected' => 'Rejected'],
            'statusBadges'  => ['completed' => 'bg-success-lt text-success', 'submitted' => 'bg-warning-lt text-warning', 'rejected' => 'bg-danger-lt text-danger'],
            'displayStatus' => fn ($s) => $s,
        ])->render();

        $this->assertStringContainsString('Pending review', $html);
        $this->assertStringContainsString('Your Submitted Answers', $html);
        $this->assertStringContainsString('Long Essay Prompt', $html);
        $this->assertStringContainsString('This is a complete and detailed untruncated essay answer that should be displayed in full inside the submission review box.', $html);
        $this->assertStringContainsString('Self Evaluation Rating', $html);
        $this->assertStringContainsString('5 / 5 Stars', $html);
        $this->assertStringNotContainsString('Submit Answers', $html);
    }

    public function test_quick_filter_shows_revision_notification_and_banner_is_removed(): void
    {
        TaskPlaylistUnlock::firstOrCreate([
            'user_id'          => $this->learner->id,
            'training_plan_id' => $this->plan->id,
        ]);

        $task = Task::create([
            'parent_type'      => 'playlist',
            'training_plan_id' => $this->plan->id,
            'title'            => 'Playlist Task Needing Revision ' . uniqid(),
            'type'             => 'open_ended',
            'status'           => 'published',
            'created_by'       => $this->admin->id,
        ]);

        TaskSubmission::create([
            'task_id'            => $task->id,
            'user_id'            => $this->learner->id,
            'status'             => 'rejected',
            'resubmit_requested' => true,
            'feedback'           => 'Need revision on question 1',
            'submitted_at'       => now(),
        ]);

        $response = $this->actingAs($this->learner)->get(route('user.learning.dashboard.partial', ['tab' => 'my-task']));
        $response->assertStatus(200);

        // Warning banner should NOT be present
        $response->assertDontSee('Require Revision', false);
        $response->assertDontSee('View Revision Tasks', false);

        // Quick filter pills should render notification badge for Playlist and All
        $html = $response->getContent();
        $this->assertStringContainsString('my-task-revision-badge', $html);
        $this->assertStringContainsString('require revision', $html);
    }

    public function test_course_and_task_display_resubmit_indicators_when_revision_requested(): void
    {
        TaskPlaylistUnlock::firstOrCreate([
            'user_id'          => $this->learner->id,
            'training_plan_id' => $this->plan->id,
        ]);

        $task = Task::create([
            'parent_type'      => 'playlist',
            'training_plan_id' => $this->plan->id,
            'title'            => 'Task Demanding Resubmission ' . uniqid(),
            'type'             => 'open_ended',
            'status'           => 'published',
            'created_by'       => $this->admin->id,
        ]);

        TaskSubmission::create([
            'task_id'            => $task->id,
            'user_id'            => $this->learner->id,
            'status'             => 'rejected',
            'resubmit_requested' => true,
            'feedback'           => 'Please revise question 2 with more details.',
            'submitted_at'       => now(),
        ]);

        // 1. Check parent table view
        $response = $this->actingAs($this->learner)->get(route('user.learning.dashboard.partial', ['tab' => 'my-task']));
        $response->assertStatus(200);

        $html = $response->getContent();

        // Course level indicators
        $this->assertStringContainsString($this->plan->title, $html);
        $this->assertStringContainsString('Need Resubmit', $html);
        $this->assertStringContainsString('<span class="badge bg-warning text-dark"><i class="ti ti-refresh me-1"></i>Resubmit</span>', $html);

        // Task level indicators
        $this->assertStringContainsString($task->title, $html);
        $this->assertStringContainsString('Need Resubmit', $html);

        // 2. Check drilled-down parent view
        $drillResponse = $this->actingAs($this->learner)->get(route('user.learning.dashboard.partial', [
            'tab'            => 'my-task',
            'my_task_parent' => 'playlist:' . $this->plan->id,
        ]));
        $drillResponse->assertStatus(200);
        $drillHtml = $drillResponse->getContent();

        $this->assertStringContainsString($this->plan->title, $drillHtml);
        $this->assertStringContainsString('Need Resubmit', $drillHtml);
        $this->assertStringContainsString('<span class="badge bg-warning text-dark">Rejected</span>', $drillHtml);

        // 3. Check task modal card
        $cardHtml = view('user.learning.dashboard-partials.my-task-card', [
            'task'          => $task,
            'inModal'       => true,
            'statusLabels'  => ['completed' => 'Completed', 'submitted' => 'Pending review', 'rejected' => 'Rejected'],
            'statusBadges'  => ['completed' => 'bg-success-lt text-success', 'submitted' => 'bg-warning-lt text-warning', 'rejected' => 'bg-danger-lt text-danger'],
            'displayStatus' => fn ($s) => $s,
        ])->render();

        $this->assertStringContainsString('Resubmit', $cardHtml);
        $this->assertStringContainsString('Task Revision Requested', $cardHtml);
        $this->assertStringContainsString('Admin Feedback', $cardHtml);
        $this->assertStringContainsString('alert-warning', $cardHtml);
    }

    public function test_user_task_card_renders_danger_alert_when_resubmit_is_overdue(): void
    {
        $task = Task::create([
            'parent_type'      => 'playlist',
            'training_plan_id' => $this->plan->id,
            'title'            => 'Task Resubmit Overdue ' . uniqid(),
            'type'             => 'open_ended',
            'status'           => 'published',
            'deadline'         => now()->subDay(),
            'created_by'       => $this->admin->id,
        ]);

        TaskSubmission::create([
            'task_id'            => $task->id,
            'user_id'            => $this->learner->id,
            'status'             => 'rejected',
            'resubmit_requested' => true,
            'feedback'           => 'Please revise overdue task',
            'submitted_at'       => now()->subDays(2),
        ]);

        $this->actingAs($this->learner);
        $cardHtml = view('user.learning.dashboard-partials.my-task-card', [
            'task'          => $task,
            'inModal'       => true,
            'statusLabels'  => ['completed' => 'Completed', 'submitted' => 'Pending review', 'rejected' => 'Rejected'],
            'statusBadges'  => ['completed' => 'bg-success-lt text-success', 'submitted' => 'bg-warning-lt text-warning', 'rejected' => 'bg-danger-lt text-danger'],
            'displayStatus' => fn ($s) => $s,
        ])->render();

        $this->assertStringContainsString('alert-danger', $cardHtml);
        $this->assertStringContainsString('bg-danger', $cardHtml);
        $this->assertStringContainsString('Overdue', $cardHtml);
        $this->assertStringContainsString('Task Revision Requested', $cardHtml);
    }

    public function test_admin_submissions_view_renders_fullscreen_and_open_in_new_tab_for_file_submissions(): void
    {
        $task = Task::create([
            'parent_type'      => 'playlist',
            'training_plan_id' => $this->plan->id,
            'title'            => 'Task File Review Workstation ' . uniqid(),
            'type'             => 'open_ended',
            'status'           => 'published',
            'created_by'       => $this->admin->id,
        ]);

        $field = TaskField::create([
            'task_id'  => $task->id,
            'label'    => 'Upload Final Report',
            'type'     => 'file_upload',
            'required' => true,
            'order'    => 1,
        ]);

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
                    'file_path'     => ['task-submissions/' . $task->id . '/final_report.docx'],
                ],
            ],
        ]);

        $response = $this->actingAs($this->admin)->get(route('manage.task.submissions', $task));
        $response->assertStatus(200);

        $html = $response->getContent();

        // 1. Open in new tab exists and replaces old "Full tab"
        $this->assertStringContainsString('Open in new tab', $html);
        $this->assertStringNotContainsString('> Full tab<', $html);

        // 2. Download button exists
        $this->assertStringContainsString('Download', $html);
    }

    public function test_submissions_responses_table_renders_quick_filters_all_need_review_approve_rejected(): void
    {
        $task = Task::create([
            'parent_type'      => 'playlist',
            'training_plan_id' => $this->plan->id,
            'title'            => 'Submissions Quick Filter Test ' . uniqid(),
            'type'             => 'open_ended',
            'status'           => 'published',
            'created_by'       => $this->admin->id,
        ]);

        // 1. Pending (Need Review)
        TaskSubmission::create([
            'task_id'            => $task->id,
            'user_id'            => $this->learner->id,
            'status'             => 'pending',
            'resubmit_requested' => false,
            'submitted_at'       => now(),
        ]);

        // 2. Completed (Approve)
        $existingUsers = User::where('role', User::ROLE_USER)->where('id', '!=', $this->learner->id)->take(2)->get();
        $user2 = $existingUsers[0] ?? User::create([
            'name'     => 'User Two ' . uniqid(),
            'email'    => 'user2_' . uniqid() . '@example.com',
            'password' => bcrypt('password'),
            'role'     => User::ROLE_USER,
            'team'     => 'Default Team',
        ]);
        TaskSubmission::create([
            'task_id'            => $task->id,
            'user_id'            => $user2->id,
            'status'             => 'completed',
            'score'              => 90,
            'resubmit_requested' => false,
            'submitted_at'       => now(),
        ]);

        // 3. Resubmit (Rejected)
        $user3 = $existingUsers[1] ?? User::create([
            'name'     => 'User Three ' . uniqid(),
            'email'    => 'user3_' . uniqid() . '@example.com',
            'password' => bcrypt('password'),
            'role'     => User::ROLE_USER,
            'team'     => 'Default Team',
        ]);
        TaskSubmission::create([
            'task_id'            => $task->id,
            'user_id'            => $user3->id,
            'status'             => 'pending',
            'resubmit_requested' => true,
            'submitted_at'       => now(),
        ]);

        $response = $this->actingAs($this->admin)->get(route('manage.task.submissions', $task));
        $response->assertStatus(200);

        $html = $response->getContent();

        // Check quick filter container & pills
        $this->assertStringContainsString('id="submissionQuickFilters"', $html);
        $this->assertStringContainsString('data-filter="all"', $html);
        $this->assertStringContainsString('All', $html);
        $this->assertStringContainsString('data-filter="need_review"', $html);
        $this->assertStringContainsString('Need Review', $html);
        $this->assertStringContainsString('data-filter="approve"', $html);
        $this->assertStringContainsString('Approve', $html);
        $this->assertStringContainsString('data-filter="rejected"', $html);
        $this->assertStringContainsString('Rejected', $html);

        // Check rows data-status
        $this->assertStringContainsString('data-status="need_review"', $html);
        $this->assertStringContainsString('data-status="approve"', $html);
        $this->assertStringContainsString('data-status="rejected"', $html);

        // Server-side filtering check
        $needReviewResponse = $this->actingAs($this->admin)->get(route('manage.task.submissions', ['task' => $task, 'status' => 'need_review']));
        $needReviewResponse->assertStatus(200);
        $needReviewHtml = $needReviewResponse->getContent();
        $this->assertStringContainsString('data-status="need_review"', $needReviewHtml);
        $this->assertStringNotContainsString('data-status="approve"', $needReviewHtml);
    }
}
