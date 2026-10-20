<?php

namespace Tests\Feature;

use App\Models\Content;
use App\Models\PlaylistContentUser;
use App\Models\Task;
use App\Models\TaskPlaylistUnlock;
use App\Models\TrainingPlan;
use App\Models\TrainingPlanDetail;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class CourseAssignmentNoMarkAsCompleteTest extends TestCase
{
    use DatabaseTransactions;

    protected User $user;
    protected TrainingPlan $plan;
    protected Content $content;
    protected Task $task;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::where('role', User::ROLE_USER)->first()
            ?? User::factory()->create(['team' => 'IT', 'role' => User::ROLE_USER]);

        $this->plan = TrainingPlan::create([
            'title'       => 'Test Linked Course ' . uniqid(),
            'slug'        => 'test-linked-course-' . uniqid(),
            'description' => 'Course description',
            'is_active'   => 1,
        ]);

        $this->content = Content::create([
            'title'       => 'Lesson 1 ' . uniqid(),
            'slug'        => 'lesson-1-' . uniqid(),
            'type'        => 'Video',
            'is_active'   => 1,
            'duration'    => '00:10:00',
        ]);

        TrainingPlanDetail::create([
            'trainingplan_id' => $this->plan->id,
            'content_id'      => $this->content->id,
            'content_order'   => 1,
        ]);

        // Unlock the course for the user
        TaskPlaylistUnlock::create([
            'user_id'          => $this->user->id,
            'training_plan_id' => $this->plan->id,
            'unlocked_at'      => now(),
        ]);

        $this->task = Task::create([
            'title'                   => 'Course Assignment Task ' . uniqid(),
            'type'                    => 'course_assignment',
            'parent_type'             => 'playlist',
            'training_plan_id'        => $this->plan->id,
            'link_type'               => 'lms',
            'linked_training_plan_id' => $this->plan->id,
            'status'                  => 'published',
            'created_by'              => $this->user->id,
        ]);
    }

    public function test_course_assignment_card_does_not_render_mark_as_complete_button(): void
    {
        $response = $this->actingAs($this->user)->get('/my-learning/partial/my-task?my_task_parent=playlist:' . $this->plan->id);

        $response->assertStatus(200);
        $response->assertDontSee('Mark as Complete');
        $response->assertDontSee('Marked as done');
        $response->assertSee('Open Course: ' . $this->plan->title);
        $response->assertSee('Finish the course (100% progress) — this task will be marked complete automatically.');
    }

    public function test_external_course_assignment_card_does_not_render_mark_as_complete_button(): void
    {
        $this->task->update([
            'link_type'               => 'external',
            'linked_training_plan_id' => null,
            'external_url'            => 'https://example.com/course',
        ]);

        $response = $this->actingAs($this->user)->get('/my-learning/partial/my-task?my_task_parent=playlist:' . $this->plan->id);

        $response->assertStatus(200);
        $response->assertDontSee('Mark as Complete');
        $response->assertSee('Open External Course');
        $response->assertSee('Finish the course — this task will be marked complete automatically.');
    }

    public function test_manual_post_submit_on_course_assignment_is_blocked_with_auto_complete_info(): void
    {
        $response = $this->actingAs($this->user)->post(route('my-task.submit', $this->task));

        $response->assertRedirect(route('user.learning.dashboard', ['learning_tab' => 'my-task']));
        $response->assertSessionHas('info', 'This task completes automatically once you finish the course (100% progress).');

        $this->assertDatabaseMissing('task_submissions', [
            'task_id' => $this->task->id,
            'user_id' => $this->user->id,
            'status'  => 'pending',
        ]);
    }

    public function test_course_assignment_auto_completes_when_course_progress_is_100_percent(): void
    {
        // Mark the content in this course as finished (100% progress)
        PlaylistContentUser::create([
            'user_id'         => $this->user->id,
            'trainingplan_id' => $this->plan->id,
            'content_id'      => $this->content->id,
            'progress'        => 100,
            'finish'          => 1,
            'completed_at'    => now(),
        ]);

        $response = $this->actingAs($this->user)->get('/my-learning/partial/my-task?my_task_parent=playlist:' . $this->plan->id);

        $response->assertStatus(200);

        $this->assertDatabaseHas('task_submissions', [
            'task_id' => $this->task->id,
            'user_id' => $this->user->id,
            'status'  => 'completed',
        ]);

        $response->assertSee('Course completed.');
    }
}
