<?php

namespace Tests\Feature;

use App\Models\Task;
use App\Models\TaskField;
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
}
