<?php

namespace Tests\Feature;

use App\Models\OfflineTraining;
use App\Models\OfflineTrainingUser;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class TaskManagementNonKpOwlTest extends TestCase
{
    use DatabaseTransactions;

    protected User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::where('role', User::ROLE_ADMIN)->first()
            ?? User::where('role', User::ROLE_SUPER_ADMIN)->first()
            ?? User::factory()->create(['role' => User::ROLE_ADMIN]);
    }

    public function test_task_plans_page_renders_create_button_with_offline_training_param_when_scope_is_non_kp(): void
    {
        $response = $this->actingAs($this->admin)->get(route('manage.task.index', ['scope' => 'non_kp']));

        $response->assertStatus(200);
        $response->assertSee(e(route('manage.task.create', ['scope' => 'non_kp', 'parent_type' => 'offline_training'])), false);
    }

    public function test_create_task_form_automatically_defaults_to_offline_training_when_scope_is_non_kp(): void
    {
        $response = $this->actingAs($this->admin)->get(route('manage.task.create', ['scope' => 'non_kp']));

        $response->assertStatus(200);
        $response->assertSee('<option value="offline_training" selected>', false);
        $response->assertSee('id="parentPickerLabel">Offline Training</label>', false);
        $response->assertSee(route('manage.task.index', ['scope' => 'non_kp']), false);
    }

    public function test_create_task_form_defaults_to_offline_training_when_parent_type_is_offline_training(): void
    {
        $response = $this->actingAs($this->admin)->get(route('manage.task.create', ['parent_type' => 'offline_training']));

        $response->assertStatus(200);
        $response->assertSee('<option value="offline_training" selected>', false);
        $response->assertSee('id="parentPickerLabel">Offline Training</label>', false);
    }

    public function test_create_task_form_defaults_to_playlist_when_scope_is_kp_or_no_param(): void
    {
        $response = $this->actingAs($this->admin)->get(route('manage.task.create'));

        $response->assertStatus(200);
        $response->assertSee('<option value="playlist" selected>', false);
        $response->assertSee('id="parentPickerLabel">Playlist</label>', false);
    }

    public function test_parent_options_endpoint_returns_offline_trainings(): void
    {
        $offline = OfflineTraining::create([
            'title'               => 'Test Safety Induction 2026',
            'course_name'         => 'Test Safety Induction 2026',
            'category'            => 'Safety',
            'instructor'          => 'John Doe',
            'location'            => 'Room 101',
            'quota'               => 20,
            'status'              => 'Open Registration',
            'start_date'          => '2026-11-01',
            'end_date'            => '2026-11-02',
            'start_time'          => '08:00',
            'end_time'            => '16:00',
        ]);

        $response = $this->actingAs($this->admin)->getJson(route('manage.task.parents', ['type' => 'offline_training', 'q' => 'Safety Induction']));

        $response->assertStatus(200);
        $response->assertJsonFragment([
            'id'    => $offline->id,
            'title' => 'Test Safety Induction 2026',
        ]);
    }

    public function test_can_store_task_with_offline_training_placement(): void
    {
        $offline = OfflineTraining::create([
            'title'       => 'Plant Tour Offline 2026',
            'course_name' => 'Plant Tour Offline 2026',
            'category'    => 'Technical',
            'instructor'  => 'Trainer A',
            'location'    => 'Plant Area',
            'quota'       => 10,
            'status'      => 'Open Registration',
            'start_date'  => '2026-12-01',
            'end_date'    => '2026-12-02',
            'start_time'  => '08:00',
            'end_time'    => '17:00',
        ]);

        $payload = [
            'parent_type' => 'offline_training',
            'parent_id'   => $offline->id,
            'type'        => 'file_submission',
            'title'       => 'Upload Attendance Sheet',
            'description' => 'Please upload signed attendance form',
            'action'      => 'publish',
            'fields'      => [
                [
                    'label'      => 'Attendance Document',
                    'type'       => 'file',
                    'required'   => '1',
                    'file_types' => 'document',
                    'max_files'  => 1,
                ],
            ],
        ];

        $response = $this->actingAs($this->admin)->post(route('manage.task.store'), $payload);

        $response->assertRedirect(route('manage.task.parent', ['parentType' => 'offline_training', 'parentId' => $offline->id]));

        $this->assertDatabaseHas('tasks', [
            'title'               => 'Upload Attendance Sheet',
            'parent_type'         => 'offline_training',
            'offline_training_id' => $offline->id,
            'status'              => 'published',
        ]);
    }

    public function test_enrolled_user_can_see_offline_training_task_in_my_task(): void
    {
        $user = User::where('role', '1')->first() ?? User::first();

        $offline = OfflineTraining::create([
            'title'       => 'Welding Certification 2026',
            'course_name' => 'Welding Certification 2026',
            'batch'       => 'WELD-BATCH-01',
            'category'    => 'Technical',
            'instructor'  => 'Trainer B',
            'location'    => 'Workshop A',
            'quota'       => 15,
            'status'      => 'Open Registration',
            'start_date'  => '2026-11-10',
            'end_date'    => '2026-11-12',
            'start_time'  => '08:00',
            'end_time'    => '17:00',
        ]);

        OfflineTrainingUser::create([
            'offline_training_id' => $offline->id,
            'user_id'             => $user->id,
            'name'                => $user->name,
            'employee_id'         => $user->employee_id ?? 'EMP-999',
            'attendance'          => 1,
            'progress'            => 100,
        ]);

        $task = \App\Models\Task::create([
            'parent_type'         => 'offline_training',
            'offline_training_id' => $offline->id,
            'title'               => 'Upload Welding Photo Evidence',
            'type'                => 'file_submission',
            'status'              => 'published',
            'created_by'          => $this->admin->id,
        ]);

        $response = $this->actingAs($user)->get(route('user.learning.dashboard.partial', ['tab' => 'my-task']));

        $response->assertStatus(200);
        $response->assertSee('Welding Certification 2026');
        $response->assertSee('Offline Training');

        $detailResponse = $this->actingAs($user)->get(route('user.learning.dashboard.partial', [
            'tab'            => 'my-task',
            'my_task_parent' => 'offline_training:' . $offline->id,
        ]));

        $detailResponse->assertStatus(200);
        $detailResponse->assertSee('Upload Welding Photo Evidence');
    }

    public function test_non_enrolled_user_does_not_see_offline_training_task_in_my_task(): void
    {
        $user = User::where('role', '1')->first() ?? User::first();

        $offline = OfflineTraining::create([
            'title'       => 'Secret Plant Training 2026',
            'course_name' => 'Secret Plant Training 2026',
            'category'    => 'Confidential',
            'instructor'  => 'Trainer C',
            'location'    => 'Secret Room',
            'quota'       => 5,
            'status'      => 'Open Registration',
            'start_date'  => '2026-11-10',
            'end_date'    => '2026-11-12',
            'start_time'  => '08:00',
            'end_time'    => '17:00',
        ]);

        \App\Models\Task::create([
            'parent_type'         => 'offline_training',
            'offline_training_id' => $offline->id,
            'title'               => 'Confidential Report Upload',
            'type'                => 'file_submission',
            'status'              => 'published',
            'created_by'          => $this->admin->id,
        ]);

        $response = $this->actingAs($user)->get(route('user.learning.dashboard.partial', ['tab' => 'my-task']));

        $response->assertStatus(200);
        $response->assertDontSee('Confidential Report Upload');
    }

    public function test_store_task_fails_validation_when_offline_training_parent_id_is_missing(): void
    {
        $payload = [
            'parent_type' => 'offline_training',
            'parent_id'   => '',
            'type'        => 'file_submission',
            'title'       => 'Incomplete Task',
            'action'      => 'publish',
        ];

        $response = $this->actingAs($this->admin)->post(route('manage.task.store'), $payload);

        $response->assertSessionHasErrors(['parent_id']);
    }

    public function test_offline_training_group_renders_link_to_training_schedule_history_tab(): void
    {
        $user = User::where('role', '1')->first() ?? User::first();

        $offline = OfflineTraining::create([
            'title'       => 'Hydraulic System Training 2026',
            'course_name' => 'Hydraulic System Training 2026',
            'category'    => 'Technical',
            'instructor'  => 'Trainer D',
            'location'    => 'Workshop B',
            'quota'       => 10,
            'status'      => 'Open Registration',
            'start_date'  => '2026-11-20',
            'end_date'    => '2026-11-22',
            'start_time'  => '08:00',
            'end_time'    => '17:00',
        ]);

        OfflineTrainingUser::create([
            'offline_training_id' => $offline->id,
            'user_id'             => $user->id,
            'name'                => $user->name,
            'employee_id'         => $user->employee_id ?? 'EMP-888',
            'attendance'          => 1,
            'progress'            => 100,
        ]);

        \App\Models\Task::create([
            'parent_type'         => 'offline_training',
            'offline_training_id' => $offline->id,
            'title'               => 'Hydraulic Checklist Upload',
            'type'                => 'file_submission',
            'status'              => 'published',
            'created_by'          => $this->admin->id,
        ]);

        $detailResponse = $this->actingAs($user)->get(route('user.learning.dashboard.partial', [
            'tab'            => 'my-task',
            'my_task_parent' => 'offline_training:' . $offline->id,
        ]));

        $detailResponse->assertStatus(200);
        $expectedUrl = route('trainingplan.user.index', ['tab' => 'history']);
        $detailResponse->assertSee(e($expectedUrl), false);
    }

    public function test_create_task_form_renders_survey_section_and_survey_id_field(): void
    {
        $response = $this->actingAs($this->admin)->get(route('manage.task.create'));

        $response->assertStatus(200);
        $response->assertSee('id="surveySection"', false);
        $response->assertSee('name="survey_id"', false);
        $response->assertSee('Target Survey Assignment', false);
    }

    public function test_can_store_survey_task_without_requiring_fields(): void
    {
        $offline = OfflineTraining::create([
            'title'       => 'Safety Awareness 2026',
            'course_name' => 'Safety Awareness 2026',
            'category'    => 'Safety',
            'instructor'  => 'Trainer E',
            'location'    => 'Hall A',
            'quota'       => 15,
            'status'      => 'Open Registration',
            'start_date'  => '2026-11-25',
            'end_date'    => '2026-11-26',
            'start_time'  => '08:00',
            'end_time'    => '17:00',
        ]);

        $payload = [
            'parent_type' => 'offline_training',
            'parent_id'   => $offline->id,
            'type'        => 'survey',
            'title'       => 'Post-Training Feedback Survey',
            'description' => 'Please fill out this survey',
            'action'      => 'draft',
            'survey_id'   => null,
        ];

        $response = $this->actingAs($this->admin)->post(route('manage.task.store'), $payload);

        $response->assertRedirect(route('manage.task.parent', ['parentType' => 'offline_training', 'parentId' => $offline->id]));

        $this->assertDatabaseHas('tasks', [
            'parent_type'         => 'offline_training',
            'offline_training_id' => $offline->id,
            'type'                => 'survey',
            'title'               => 'Post-Training Feedback Survey',
            'status'              => 'draft',
            'survey_id'           => null,
        ]);
    }
}


