<?php

namespace Tests\Feature;

use App\Models\Task;
use App\Models\TaskField;
use App\Models\TaskPlaylistUnlock;
use App\Models\TrainingPlan;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class TaskCheckboxLimitAndRatingTest extends TestCase
{
    use DatabaseTransactions;

    protected User $user;
    protected TrainingPlan $plan;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::where('role', User::ROLE_USER)->first()
            ?? User::first();

        $this->plan = TrainingPlan::firstOrCreate(
            ['slug' => 'test-checkbox-plan'],
            ['title' => 'Test Checkbox Plan', 'description' => 'Test', 'is_active' => 1]
        );

        TaskPlaylistUnlock::firstOrCreate([
            'user_id'          => $this->user->id,
            'training_plan_id' => $this->plan->id,
        ]);

        view()->share('errors', new \Illuminate\Support\ViewErrorBag());
    }

    public function test_task_field_detects_min_and_max_select_from_label(): void
    {
        $field1 = new TaskField(['label' => 'pilih 2-3 opsi dibawah']);
        $this->assertSame(2, $field1->minSelect());
        $this->assertSame(3, $field1->maxSelect());

        $field2 = new TaskField(['label' => 'Pilih 2 sampai 3 kegiatan']);
        $this->assertSame(2, $field2->minSelect());
        $this->assertSame(3, $field2->maxSelect());

        $field3 = new TaskField(['label' => 'Pilih maksimal 3 jawaban']);
        $this->assertNull($field3->minSelect());
        $this->assertSame(3, $field3->maxSelect());

        $field4 = new TaskField(['label' => 'Pertanyaan biasa']);
        $this->assertNull($field4->minSelect());
        $this->assertNull($field4->maxSelect());

        // Explicit attributes take priority
        $field5 = new TaskField([
            'label' => 'pilih 2-3 opsi dibawah',
            'min_select' => 1,
            'max_select' => 4,
        ]);
        $this->assertSame(1, $field5->minSelect());
        $this->assertSame(4, $field5->maxSelect());
    }

    public function test_checkbox_submission_fails_when_exceeding_max_select(): void
    {
        $task = Task::create([
            'title'            => 'Test Checkbox Limit Task ' . uniqid(),
            'type'             => 'action_plan',
            'parent_type'      => 'playlist',
            'training_plan_id' => $this->plan->id,
            'status'           => 'published',
            'created_by'       => $this->user->id,
        ]);

        $field = TaskField::create([
            'task_id'    => $task->id,
            'label'      => 'pilih 2-3 opsi dibawah',
            'type'       => 'checkbox',
            'options'    => ['kamis', 'jumat', 'sabtu', 'minggu'],
            'required'   => true,
            'sort_order' => 0,
        ]);

        // Attempt to submit 4 options when max is 3
        $response = $this->actingAs($this->user)->post(route('my-task.submit', $task), [
            'action' => 'submit',
            'fields' => [
                $field->id => ['kamis', 'jumat', 'sabtu', 'minggu'],
            ],
        ]);

        $response->assertSessionHasErrors("fields.{$field->id}");
    }

    public function test_checkbox_submission_succeeds_when_within_max_select(): void
    {
        $task = Task::create([
            'title'            => 'Test Checkbox Limit Task 2 ' . uniqid(),
            'type'             => 'action_plan',
            'parent_type'      => 'playlist',
            'training_plan_id' => $this->plan->id,
            'status'           => 'published',
            'created_by'       => $this->user->id,
        ]);

        $field = TaskField::create([
            'task_id'    => $task->id,
            'label'      => 'pilih 2-3 opsi dibawah',
            'type'       => 'checkbox',
            'options'    => ['kamis', 'jumat', 'sabtu', 'minggu'],
            'required'   => true,
            'sort_order' => 0,
        ]);

        // Submit exactly 3 options
        $response = $this->actingAs($this->user)->post(route('my-task.submit', $task), [
            'action' => 'submit',
            'fields' => [
                $field->id => ['kamis', 'jumat', 'sabtu'],
            ],
        ]);

        $response->assertSessionHasNoErrors();
    }

    public function test_view_renders_checkbox_group_attributes_and_star_rating_without_numbers(): void
    {
        $this->actingAs($this->user);

        $task = Task::create([
            'title'            => 'View Checkbox and Rating Task ' . uniqid(),
            'type'             => 'action_plan',
            'parent_type'      => 'playlist',
            'training_plan_id' => $this->plan->id,
            'status'           => 'published',
            'created_by'       => $this->user->id,
        ]);

        $cbField = TaskField::create([
            'task_id'    => $task->id,
            'label'      => 'pilih 2-3 opsi dibawah',
            'type'       => 'checkbox',
            'options'    => ['kamis', 'jumat', 'sabtu', 'minggu'],
            'required'   => true,
            'sort_order' => 0,
        ]);

        $ratingField = TaskField::create([
            'task_id'    => $task->id,
            'label'      => 'kasih rating course ini',
            'type'       => 'rating',
            'required'   => true,
            'sort_order' => 1,
        ]);

        $task->load('fields');

        $html = view('user.learning.dashboard-partials.my-task-card', [
            'task'             => $task,
            'submission'       => null,
            'answersByField'   => collect(),
            'existingAnswers'  => collect(),
            'reviewAction'     => null,
            'isApproved'       => false,
            'isPendingReview'  => false,
            'isDraft'          => false,
            'isFinalSubmitted' => false,
            'isOverdue'        => false,
            'statusLabels'     => ['completed' => 'Completed', 'submitted' => 'Pending review', 'rejected' => 'Rejected', 'in_progress' => 'In Progress', 'not_started' => 'Not Started', 'overdue' => 'Overdue'],
            'statusBadges'     => ['completed' => 'bg-success-lt text-success', 'submitted' => 'bg-warning-lt text-warning', 'rejected' => 'bg-danger-lt text-danger', 'in_progress' => 'bg-primary-lt text-primary', 'not_started' => 'bg-secondary-lt text-secondary', 'overdue' => 'bg-danger text-white'],
            'displayStatus'    => fn ($s) => $s,
        ])->render();

        // Checkbox has data attributes for limits
        $this->assertStringContainsString('data-max-select="3"', $html);
        $this->assertStringContainsString('data-min-select="2"', $html);
        $this->assertStringContainsString('Maksimal 3', $html);

        // Rating renders star widget without number labels
        $this->assertStringContainsString('star-rating-widget', $html);
        $this->assertStringContainsString('star-icon', $html);
        $this->assertStringNotContainsString('<span class="form-check-label"><i class="ti ti-star-filled text-warning"></i> 1</span>', $html);
        $this->assertStringNotContainsString('<span class="form-check-label"><i class="ti ti-star-filled text-warning"></i> 5</span>', $html);
    }

    public function test_short_answer_accepts_any_character_count(): void
    {
        $task = Task::create([
            'title'            => 'Short Answer Task ' . uniqid(),
            'type'             => 'action_plan',
            'parent_type'      => 'playlist',
            'training_plan_id' => $this->plan->id,
            'status'           => 'published',
            'created_by'       => $this->user->id,
        ]);

        $field = TaskField::create([
            'task_id'    => $task->id,
            'label'      => 'buat short answer',
            'type'       => 'short_answer',
            'required'   => true,
            'sort_order' => 0,
        ]);

        // Submit short answer with only 2 characters
        $response = $this->actingAs($this->user)->post(route('my-task.submit', $task), [
            'action' => 'submit',
            'fields' => [
                $field->id => 'ok',
            ],
        ]);

        $response->assertSessionHasNoErrors();
    }

    public function test_paragraph_requires_at_least_100_characters_on_submit(): void
    {
        $task = Task::create([
            'title'            => 'Paragraph Task ' . uniqid(),
            'type'             => 'action_plan',
            'parent_type'      => 'playlist',
            'training_plan_id' => $this->plan->id,
            'status'           => 'published',
            'created_by'       => $this->user->id,
        ]);

        $field = TaskField::create([
            'task_id'    => $task->id,
            'label'      => 'buat paragraph',
            'type'       => 'textarea',
            'required'   => true,
            'sort_order' => 0,
        ]);

        // 1. Submit paragraph with less than 100 characters -> must fail
        $responseFail = $this->actingAs($this->user)->post(route('my-task.submit', $task), [
            'action' => 'submit',
            'fields' => [
                $field->id => 'Teks ini terlalu pendek dan belum mencapai 100 karakter.',
            ],
        ]);

        $responseFail->assertSessionHasErrors("fields.{$field->id}");

        // 2. Submit paragraph with at least 100 characters -> must succeed
        $longText = str_repeat('Ini adalah contoh jawaban paragraph yang cukup panjang dan informatif. ', 3);
        $this->assertGreaterThanOrEqual(100, mb_strlen($longText));

        $responseOk = $this->actingAs($this->user)->post(route('my-task.submit', $task), [
            'action' => 'submit',
            'fields' => [
                $field->id => $longText,
            ],
        ]);

        $responseOk->assertSessionHasNoErrors();
    }

    public function test_paragraph_draft_allows_less_than_100_characters(): void
    {
        $task = Task::create([
            'title'            => 'Paragraph Draft Task ' . uniqid(),
            'type'             => 'action_plan',
            'parent_type'      => 'playlist',
            'training_plan_id' => $this->plan->id,
            'status'           => 'published',
            'created_by'       => $this->user->id,
        ]);

        $field = TaskField::create([
            'task_id'    => $task->id,
            'label'      => 'buat paragraph draft',
            'type'       => 'textarea',
            'required'   => true,
            'sort_order' => 0,
        ]);

        // Saving as draft with only 15 characters
        $responseDraft = $this->actingAs($this->user)->post(route('my-task.submit', $task), [
            'action' => 'draft',
            'fields' => [
                $field->id => 'Draft sementara',
            ],
        ]);

        $responseDraft->assertSessionHasNoErrors();
    }

    public function test_view_renders_paragraph_counter_and_short_answer_without_minimum(): void
    {
        $this->actingAs($this->user);

        $task = Task::create([
            'title'            => 'View Short Text and Paragraph Task ' . uniqid(),
            'type'             => 'action_plan',
            'parent_type'      => 'playlist',
            'training_plan_id' => $this->plan->id,
            'status'           => 'published',
            'created_by'       => $this->user->id,
        ]);

        $shortField = TaskField::create([
            'task_id'    => $task->id,
            'label'      => 'buat short answer',
            'type'       => 'short_answer',
            'required'   => true,
            'sort_order' => 0,
        ]);

        $paraField = TaskField::create([
            'task_id'    => $task->id,
            'label'      => 'buat paragraph',
            'type'       => 'textarea',
            'required'   => true,
            'sort_order' => 1,
        ]);

        $task->load('fields');

        $html = view('user.learning.dashboard-partials.my-task-card', [
            'task'             => $task,
            'submission'       => null,
            'answersByField'   => collect(),
            'existingAnswers'  => collect(),
            'reviewAction'     => null,
            'isApproved'       => false,
            'isPendingReview'  => false,
            'isDraft'          => false,
            'isFinalSubmitted' => false,
            'isOverdue'        => false,
            'statusLabels'     => ['completed' => 'Completed', 'submitted' => 'Pending review', 'rejected' => 'Rejected', 'in_progress' => 'In Progress', 'not_started' => 'Not Started', 'overdue' => 'Overdue'],
            'statusBadges'     => ['completed' => 'bg-success-lt text-success', 'submitted' => 'bg-warning-lt text-warning', 'rejected' => 'bg-danger-lt text-danger', 'in_progress' => 'bg-primary-lt text-primary', 'not_started' => 'bg-secondary-lt text-secondary', 'overdue' => 'bg-danger text-white'],
            'displayStatus'    => fn ($s) => $s,
        ])->render();

        // Paragraph renders character counter and minlength constraint
        $this->assertStringContainsString('data-min-chars="100"', $html);
        $this->assertStringContainsString('minlength="100"', $html);
        $this->assertStringContainsString('paragraph-char-counter', $html);
        $this->assertStringContainsString('Minimum <strong>100</strong> karakter', $html);

        // Short answer renders simple input without minlength="100"
        $this->assertStringContainsString('name="fields[' . $shortField->id . ']"', $html);
        $this->assertStringContainsString('placeholder="Your answer"', $html);
    }
}
