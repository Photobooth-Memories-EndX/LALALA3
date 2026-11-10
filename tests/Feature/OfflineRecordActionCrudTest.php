<?php

namespace Tests\Feature;

use App\Models\OfflineTraining;
use App\Models\OfflineTrainingUser;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class OfflineRecordActionCrudTest extends TestCase
{
    protected User $admin;
    protected ?User $regularUser;
    protected array $createdTrainingIds = [];

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::where('role', '2')->first() ?? User::first();
        $this->regularUser = User::where('role', '1')->first();
    }

    protected function tearDown(): void
    {
        if (!empty($this->createdTrainingIds)) {
            DB::table('offline_training_users')->whereIn('offline_training_id', $this->createdTrainingIds)->delete();
            DB::table('offline_trainings')->whereIn('id', $this->createdTrainingIds)->delete();
        }

        parent::tearDown();
    }

    /**
     * Helper to create a test training with one or more participants.
     */
    protected function createTestTraining(array $trainingAttrs = [], array $participantsList = []): OfflineTraining
    {
        $training = OfflineTraining::create(array_merge([
            'course_name'           => 'Safety Standard Operational',
            'sub_course_name'       => 'Module 1: General Workshop',
            'type'                  => 'In-House',
            'category'              => 'Safety Track',
            'level'                 => 'Basic',
            'batch'                 => 'Batch 1',
            'main_classification'   => 'Competency',
            'sub_classification'    => 'Plant Safety',
            'counted_corporate_kpi' => true,
            'delivery_method'       => 'Classroom',
            'provider'              => 'Internal',
            'duration_seconds'      => 14400,
            'learning_hours'        => 4.0,
            'start_date'            => '2026-04-10',
            'finish_date'           => '2026-04-11',
            'venue'                 => 'Training Room A',
            'instructor'            => 'Bambang S.',
        ], $trainingAttrs));

        $this->createdTrainingIds[] = $training->id;

        if (empty($participantsList)) {
            $participantsList = [
                [
                    'employee_id'      => 'EMPTEST001',
                    'name'             => 'BUDI SANTOSO',
                    'grade'            => '3A',
                    'job_position'     => 'Technician',
                    'team'             => 'Rolling Mill',
                    'department'       => 'Production',
                    'company'          => 'PT Krakatau Posco',
                    'learning_hours'   => 4.0,
                    'progress'         => 100.0,
                    'assessment_score' => 90.0,
                    'certificate'      => 'Yes',
                    'attendance'       => true,
                    'bond_status'      => 'No',
                ]
            ];
        }

        foreach ($participantsList as $pData) {
            $pData['offline_training_id'] = $training->id;
            OfflineTrainingUser::create($pData);
        }

        return $training->fresh(['participants']);
    }

    // =========================================================================
    // 1. ACTION COLUMN IN CLASSIFICATION DATATABLE
    // =========================================================================

    public function test_action_column_renders_three_icon_buttons_without_text(): void
    {
        $training = $this->createTestTraining();
        $participant = $training->participants->first();

        $response = $this->actingAs($this->admin)
            ->getJson(route('manage.record.offline.classification.datatable', 'competency'));

        $response->assertStatus(200);
        $data = $response->json('data');

        $this->assertNotEmpty($data);
        $targetRow = collect($data)->firstWhere('DT_RowIndex');
        $this->assertNotNull($targetRow);

        $actionHtml = $targetRow['action'];

        // Assert all 3 icon buttons are present
        $this->assertStringContainsString('ti ti-eye', $actionHtml);
        $this->assertStringContainsString('ti ti-edit', $actionHtml);
        $this->assertStringContainsString('ti ti-trash', $actionHtml);

        // Assert tooltips, aria-labels, and titles are present
        $this->assertStringContainsString('title="View Detail"', $actionHtml);
        $this->assertStringContainsString('aria-label="View Detail"', $actionHtml);
        $this->assertStringContainsString('title="Edit Record"', $actionHtml);
        $this->assertStringContainsString('aria-label="Edit Record"', $actionHtml);
        $this->assertStringContainsString('title="Delete Record"', $actionHtml);
        $this->assertStringContainsString('aria-label="Delete Record"', $actionHtml);

        // Assert button classes
        $this->assertStringContainsString('btn-view-offline-detail', $actionHtml);
        $this->assertStringContainsString('btn-edit-offline-record', $actionHtml);
        $this->assertStringContainsString('btn-delete-offline-record', $actionHtml);

        // Assert NO text button labels remain (pure icon buttons)
        $this->assertStringNotContainsString('>Detail<', $actionHtml);
        $this->assertStringNotContainsString('> Detail<', $actionHtml);
        $this->assertStringNotContainsString('>Edit<', $actionHtml);
        $this->assertStringNotContainsString('>Delete<', $actionHtml);
    }

    public function test_action_view_button_has_data_id_for_modal(): void
    {
        $training = $this->createTestTraining();
        $participant = $training->participants->first();

        $response = $this->actingAs($this->admin)
            ->getJson(route('manage.record.offline.classification.datatable', 'competency'));

        $actionHtml = collect($response->json('data'))->firstWhere('id', $participant->id)['action'] ?? '';

        $this->assertStringContainsString('data-id="' . $participant->id . '"', $actionHtml);
    }

    public function test_action_edit_button_links_to_edit_page(): void
    {
        $training = $this->createTestTraining();
        $participant = $training->participants->first();

        $response = $this->actingAs($this->admin)
            ->getJson(route('manage.record.offline.classification.datatable', 'competency'));

        $actionHtml = collect($response->json('data'))->firstWhere('id', $participant->id)['action'] ?? '';
        $expectedUrl = route('manage.record.offline.edit', $participant->id);

        $this->assertStringContainsString('href="' . $expectedUrl . '"', $actionHtml);
    }

    public function test_action_delete_button_contains_employee_and_course_attributes(): void
    {
        $training = $this->createTestTraining();
        $participant = $training->participants->first();

        $response = $this->actingAs($this->admin)
            ->getJson(route('manage.record.offline.classification.datatable', 'competency'));

        $actionHtml = collect($response->json('data'))->firstWhere('id', $participant->id)['action'] ?? '';

        $this->assertStringContainsString('data-id="' . $participant->id . '"', $actionHtml);
        $this->assertStringContainsString('data-employee="' . htmlspecialchars($participant->name, ENT_QUOTES) . '"', $actionHtml);
        $this->assertStringContainsString('data-course="' . htmlspecialchars($training->course_name, ENT_QUOTES) . '"', $actionHtml);
    }

    // =========================================================================
    // 2. EDIT PAGE ACCESS & PERMISSIONS
    // =========================================================================

    public function test_guest_cannot_access_edit_page(): void
    {
        $training = $this->createTestTraining();
        $participant = $training->participants->first();

        $response = $this->get(route('manage.record.offline.edit', $participant->id));
        $response->assertRedirect('/login');
    }

    public function test_user_without_permission_cannot_access_edit_page(): void
    {
        if (!$this->regularUser) {
            $this->markTestSkipped('No regular user found.');
        }

        $training = $this->createTestTraining();
        $participant = $training->participants->first();

        $response = $this->actingAs($this->regularUser)
            ->get(route('manage.record.offline.edit', $participant->id));

        $response->assertStatus(403);
    }

    public function test_authorized_user_can_access_edit_page(): void
    {
        $training = $this->createTestTraining();
        $participant = $training->participants->first();

        $response = $this->actingAs($this->admin)
            ->get(route('manage.record.offline.edit', $participant->id));

        $response->assertStatus(200);
        $response->assertViewIs('main.record.offline-edit');
    }

    public function test_edit_page_returns_404_for_invalid_id(): void
    {
        $response = $this->actingAs($this->admin)
            ->get(route('manage.record.offline.edit', 99999999));

        $response->assertStatus(404);
    }

    // =========================================================================
    // 3. EDIT PAGE FORM PRE-POPULATION
    // =========================================================================

    public function test_edit_page_prepopulates_training_information(): void
    {
        $training = $this->createTestTraining([
            'course_name'     => 'Unique Advanced Robotics Course',
            'batch'           => 'Batch 77',
            'sub_course_name' => 'Sub Robotics Module',
            'category'        => 'Automation & AI',
            'venue'           => 'Hall Beta',
            'instructor'      => 'Dr. Hendra',
        ]);
        $participant = $training->participants->first();

        $response = $this->actingAs($this->admin)
            ->get(route('manage.record.offline.edit', $participant->id));

        $response->assertStatus(200);
        $response->assertSee('Unique Advanced Robotics Course');
        $response->assertSee('Batch 77');
        $response->assertSee('Sub Robotics Module');
        $response->assertSee('Automation &amp; AI', false);
        $response->assertSee('Hall Beta');
        $response->assertSee('Dr. Hendra');
    }

    public function test_edit_page_prepopulates_participant_information(): void
    {
        $training = $this->createTestTraining([], [
            [
                'employee_id'  => 'EMPPREPOP123',
                'name'         => 'SITI RAHMAWATI',
                'grade'        => '4B',
                'job_position' => 'Senior Process Engineer',
                'team'         => 'Coke Plant Team',
                'department'   => 'Ironmaking',
                'company'      => 'PT Krakatau Posco',
            ]
        ]);
        $participant = $training->participants->first();

        $response = $this->actingAs($this->admin)
            ->get(route('manage.record.offline.edit', $participant->id));

        $response->assertStatus(200);
        $response->assertSee('EMPPREPOP123');
        $response->assertSee('SITI RAHMAWATI');
        $response->assertSee('4B');
        $response->assertSee('Senior Process Engineer');
        $response->assertSee('Coke Plant Team');
        $response->assertSee('Ironmaking');
    }

    public function test_edit_page_prepopulates_learning_results(): void
    {
        $training = $this->createTestTraining([], [
            [
                'employee_id'      => 'EMPRESULTS456',
                'name'             => 'AGUS WIJAYA',
                'learning_hours'   => 12.5,
                'progress'         => 85.0,
                'assessment_score' => 92.5,
                'certificate'      => 'CERT-2026-XYZ',
                'attendance'       => true,
                'bond_status'      => 'Yes',
                'bond_end_date'    => '2028-06-30',
                'bond_amount'      => 7500000.0,
                'l1_program'       => '4.9 Exceeds',
                'l2_numerical'     => 95.0,
                'l2_verbal'        => 'High Distinction',
                'l3_numerical'     => 90.0,
                'l3_verbal'        => 'Fully Applied',
                'pic'              => 'HR Learning Division',
            ]
        ]);
        $participant = $training->participants->first();

        $response = $this->actingAs($this->admin)
            ->get(route('manage.record.offline.edit', $participant->id));

        $response->assertStatus(200);
        $response->assertSee('85');
        $response->assertSee('92.5');
        $response->assertSee('CERT-2026-XYZ');
        $response->assertSee('2028-06-30');
        $response->assertSee('7500000');
        $response->assertSee('4.9 Exceeds');
        $response->assertSee('High Distinction');
        $response->assertSee('HR Learning Division');
    }

    // =========================================================================
    // 4. UPDATE ACTION & VALIDATION
    // =========================================================================

    public function test_update_requires_permission(): void
    {
        if (!$this->regularUser) {
            $this->markTestSkipped('No regular user found.');
        }

        $training = $this->createTestTraining();
        $participant = $training->participants->first();

        $response = $this->actingAs($this->regularUser)
            ->put(route('manage.record.offline.update', $participant->id), [
                'course_name' => 'Updated Course Name',
                'employee_id' => 'EMPTEST001',
                'name'        => 'BUDI SANTOSO',
            ]);

        $response->assertStatus(403);
    }

    public function test_update_action_updates_training_and_participant_records(): void
    {
        $training = $this->createTestTraining();
        $participant = $training->participants->first();

        $updateData = [
            'course_name'               => 'Updated Safety Mastery 2026',
            'batch'                     => 'Batch 99',
            'sub_course_name'           => 'Updated Sub Course',
            'type'                      => 'Public',
            'category'                  => 'Advanced Safety',
            'level'                     => 'Advanced',
            'main_classification'       => 'Competency',
            'sub_classification'        => 'Safety Leadership',
            'counted_corporate_kpi'     => 0,
            'delivery_method'           => 'Workshop',
            'sub_delivery_method'       => 'Hands-on Lab',
            'provider'                  => 'External',
            'sub_provider'              => 'Vendor',
            'provider_name'             => 'PT Safety First Indo',
            'duration'                  => '06:00:00',
            'learning_hours'            => 6.0,
            'start_date'                => '2026-05-01',
            'finish_date'               => '2026-05-02',
            'venue'                     => 'Grand Ballroom',
            'instructor'                => 'Prof. Suryono',
            // Participant
            'employee_id'               => 'EMPATTR999',
            'name'                      => 'BUDI SANTOSO UPDATED',
            'grade'                     => '4A',
            'job_position'              => 'Lead Engineer',
            'team'                      => 'Plant Operations',
            'department'                => 'Operations',
            'company'                   => 'PT Krakatau Posco',
            'participant_learning_hours'=> 6.0,
            'progress'                  => 100,
            'assessment_score'          => 98.0,
            'certificate'               => 'Yes',
            'attendance'                => 1,
            'bond_status'               => 'Yes',
            'bond_end_date'             => '2027-12-31',
            'bond_amount'               => 10000000,
            'l1_program'                => '5.0 Perfect',
            'l2_numerical'              => 98.0,
            'l2_verbal'                 => 'Excellent',
            'l3_numerical'              => 95.0,
            'l3_verbal'                 => 'Executed',
            'pic'                       => 'HR Team Lead',
        ];

        $response = $this->actingAs($this->admin)
            ->put(route('manage.record.offline.update', $participant->id), $updateData);

        $response->assertRedirect(route('manage.record.offline.classification', 'competency'));

        // Verify training was updated in database
        $this->assertDatabaseHas('offline_trainings', [
            'id'          => $training->id,
            'course_name' => 'Updated Safety Mastery 2026',
            'batch'       => 'Batch 99',
            'type'        => 'Public',
            'venue'       => 'Grand Ballroom',
        ]);

        // Verify participant was updated in database
        $this->assertDatabaseHas('offline_training_users', [
            'id'               => $participant->id,
            'employee_id'      => 'EMPATTR999',
            'name'             => 'BUDI SANTOSO UPDATED',
            'job_position'     => 'Lead Engineer',
            'assessment_score' => 98.0,
            'bond_status'      => 'Yes',
        ]);
    }

    public function test_update_action_validates_required_fields(): void
    {
        $training = $this->createTestTraining();
        $participant = $training->participants->first();

        // Empty course_name
        $response = $this->actingAs($this->admin)
            ->put(route('manage.record.offline.update', $participant->id), [
                'course_name' => '',
                'employee_id' => 'EMPTEST001',
                'name'        => 'BUDI SANTOSO',
            ]);

        $response->assertSessionHasErrors(['course_name']);

        // Empty employee_id
        $response = $this->actingAs($this->admin)
            ->put(route('manage.record.offline.update', $participant->id), [
                'course_name' => 'Valid Course',
                'employee_id' => '',
                'name'        => 'BUDI SANTOSO',
            ]);

        $response->assertSessionHasErrors(['employee_id']);

        // Empty name
        $response = $this->actingAs($this->admin)
            ->put(route('manage.record.offline.update', $participant->id), [
                'course_name' => 'Valid Course',
                'employee_id' => 'EMPTEST001',
                'name'        => '',
            ]);

        $response->assertSessionHasErrors(['name']);
    }

    public function test_update_action_validates_progress_bounds(): void
    {
        $training = $this->createTestTraining();
        $participant = $training->participants->first();

        // Progress > 100
        $response = $this->actingAs($this->admin)
            ->put(route('manage.record.offline.update', $participant->id), [
                'course_name' => 'Valid Course',
                'employee_id' => 'EMPTEST001',
                'name'        => 'BUDI SANTOSO',
                'progress'    => 150,
            ]);

        $response->assertSessionHasErrors(['progress']);

        // Progress < 0
        $response = $this->actingAs($this->admin)
            ->put(route('manage.record.offline.update', $participant->id), [
                'course_name' => 'Valid Course',
                'employee_id' => 'EMPTEST001',
                'name'        => 'BUDI SANTOSO',
                'progress'    => -10,
            ]);

        $response->assertSessionHasErrors(['progress']);
    }

    public function test_update_action_validates_finish_date_after_start_date(): void
    {
        $training = $this->createTestTraining();
        $participant = $training->participants->first();

        $response = $this->actingAs($this->admin)
            ->put(route('manage.record.offline.update', $participant->id), [
                'course_name' => 'Valid Course',
                'employee_id' => 'EMPTEST001',
                'name'        => 'BUDI SANTOSO',
                'start_date'  => '2026-05-10',
                'finish_date' => '2026-05-01', // Earlier than start_date
            ]);

        $response->assertSessionHasErrors(['finish_date']);
    }

    public function test_update_action_prevents_duplicate_participant_in_same_training(): void
    {
        // Training with 2 participants
        $training = $this->createTestTraining([], [
            ['employee_id' => 'DUPE001', 'name' => 'First Person'],
            ['employee_id' => 'DUPE002', 'name' => 'Second Person'],
        ]);

        $part2 = $training->participants->where('employee_id', 'DUPE002')->first();

        // Attempt to update part2 to have employee_id DUPE001
        $response = $this->actingAs($this->admin)
            ->put(route('manage.record.offline.update', $part2->id), [
                'course_name' => $training->course_name,
                'employee_id' => 'DUPE001',
                'name'        => 'Second Person',
            ]);

        $response->assertSessionHasErrors(['employee_id']);
    }

    public function test_update_action_redirects_to_new_classification_when_changed(): void
    {
        $training = $this->createTestTraining(['main_classification' => 'Competency']);
        $participant = $training->participants->first();

        $response = $this->actingAs($this->admin)
            ->put(route('manage.record.offline.update', $participant->id), [
                'course_name'         => $training->course_name,
                'main_classification' => 'Leadership',
                'employee_id'         => $participant->employee_id,
                'name'                => $participant->name,
            ]);

        $response->assertRedirect(route('manage.record.offline.classification', 'leadership'));

        $this->assertDatabaseHas('offline_trainings', [
            'id'                  => $training->id,
            'main_classification' => 'Leadership',
        ]);
    }

    public function test_update_action_returns_json_response_for_ajax_request(): void
    {
        $training = $this->createTestTraining();
        $participant = $training->participants->first();

        $response = $this->actingAs($this->admin)
            ->putJson(route('manage.record.offline.update', $participant->id), [
                'course_name' => 'Ajax Updated Course',
                'employee_id' => $participant->employee_id,
                'name'        => $participant->name,
                'progress'    => 100,
            ]);

        $response->assertStatus(200);
        $response->assertJson([
            'success' => true,
        ]);
        $this->assertNotEmpty($response->json('redirect_url'));
    }

    // =========================================================================
    // 5. DELETE ACTION & SOFT DELETE BEHAVIOR
    // =========================================================================

    public function test_delete_endpoint_requires_permission(): void
    {
        if (!$this->regularUser) {
            $this->markTestSkipped('No regular user found.');
        }

        $training = $this->createTestTraining();
        $participant = $training->participants->first();

        $response = $this->actingAs($this->regularUser)
            ->deleteJson(route('manage.record.offline.destroy', $participant->id));

        $response->assertStatus(403);
    }

    public function test_delete_soft_deletes_participant(): void
    {
        $training = $this->createTestTraining();
        $participant = $training->participants->first();

        $response = $this->actingAs($this->admin)
            ->deleteJson(route('manage.record.offline.destroy', $participant->id));

        $response->assertStatus(200);
        $response->assertJson(['success' => true]);

        // Participant record is soft-deleted (deleted_at is NOT null)
        $this->assertSoftDeleted('offline_training_users', [
            'id' => $participant->id,
        ]);
    }

    public function test_delete_preserves_sibling_participants_in_same_training(): void
    {
        $training = $this->createTestTraining([], [
            ['employee_id' => 'SIBLING001', 'name' => 'Alice'],
            ['employee_id' => 'SIBLING002', 'name' => 'Bob'],
        ]);

        $alice = $training->participants->where('employee_id', 'SIBLING001')->first();
        $bob = $training->participants->where('employee_id', 'SIBLING002')->first();

        // Delete Alice
        $response = $this->actingAs($this->admin)
            ->deleteJson(route('manage.record.offline.destroy', $alice->id));

        $response->assertStatus(200);

        // Alice is soft-deleted
        $this->assertSoftDeleted('offline_training_users', ['id' => $alice->id]);

        // Bob is NOT deleted
        $this->assertDatabaseHas('offline_training_users', [
            'id'         => $bob->id,
            'deleted_at' => null,
        ]);

        // Parent training is NOT deleted because Bob still remains
        $this->assertDatabaseHas('offline_trainings', [
            'id'         => $training->id,
            'deleted_at' => null,
        ]);
    }

    public function test_deleting_last_participant_also_soft_deletes_parent_training(): void
    {
        // Training with only 1 participant
        $training = $this->createTestTraining([], [
            ['employee_id' => 'SOLO001', 'name' => 'Solo Participant'],
        ]);
        $solo = $training->participants->first();

        // Delete Solo
        $response = $this->actingAs($this->admin)
            ->deleteJson(route('manage.record.offline.destroy', $solo->id));

        $response->assertStatus(200);

        // Solo is soft deleted
        $this->assertSoftDeleted('offline_training_users', ['id' => $solo->id]);

        // Parent training is also soft deleted to avoid orphaned training
        $this->assertSoftDeleted('offline_trainings', ['id' => $training->id]);
    }

    public function test_delete_returns_404_for_invalid_id(): void
    {
        $response = $this->actingAs($this->admin)
            ->deleteJson(route('manage.record.offline.destroy', 99999999));

        $response->assertStatus(404);
    }

    public function test_soft_deleted_participant_is_excluded_from_datatable(): void
    {
        $training = $this->createTestTraining([], [
            ['employee_id' => 'ACTIVE001', 'name' => 'Active Participant'],
            ['employee_id' => 'DELETED001', 'name' => 'To Be Deleted'],
        ]);

        $toBeDeleted = $training->participants->where('employee_id', 'DELETED001')->first();

        // Soft delete the participant
        $this->actingAs($this->admin)
            ->deleteJson(route('manage.record.offline.destroy', $toBeDeleted->id));

        // Query datatable
        $response = $this->actingAs($this->admin)
            ->getJson(route('manage.record.offline.classification.datatable', 'competency'));

        $data = $response->json('data');
        $found = collect($data)->firstWhere('employee_id', 'DELETED001');

        $this->assertNull($found);
    }

    // =========================================================================
    // 6. VIEW INTEGRITY: DELETE CONFIRMATION MODAL
    // =========================================================================

    public function test_classification_view_renders_delete_confirmation_modal(): void
    {
        $response = $this->actingAs($this->admin)
            ->get(route('manage.record.offline.classification', 'competency'));

        $response->assertStatus(200);
        $response->assertSee('id="deleteOfflineRecordModal"', false);
        $response->assertSee('id="delete-modal-employee-name"', false);
        $response->assertSee('id="delete-modal-course-name"', false);
        $response->assertSee('id="btn-confirm-delete-offline-record"', false);
    }
}
