<?php

namespace Tests\Feature;

use App\Models\OfflineTraining;
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
}
