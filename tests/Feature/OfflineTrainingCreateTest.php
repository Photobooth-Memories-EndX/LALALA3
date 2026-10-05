<?php

namespace Tests\Feature;

use App\Models\OfflineTraining;
use App\Models\OfflineTrainingUser;
use App\Models\User;
use App\Services\Record\OfflineTrainingCreateService;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class OfflineTrainingCreateTest extends TestCase
{
    protected User $admin;
    protected ?User $regularUser;
    protected array $createdTrainingIds = [];
    protected array $createdUserIds = [];

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

        if (!empty($this->createdUserIds)) {
            DB::table('users')->whereIn('id', $this->createdUserIds)->delete();
        }

        // Catch-all cleanup by test prefix
        $leftoverIds = DB::table('offline_trainings')
            ->where('course_name', 'LIKE', 'TEST_MANUAL_%')
            ->orWhere('category', 'TEST_MANUAL_CAT')
            ->pluck('id')
            ->toArray();

        if (!empty($leftoverIds)) {
            DB::table('offline_training_users')->whereIn('offline_training_id', $leftoverIds)->delete();
            DB::table('offline_trainings')->whereIn('id', $leftoverIds)->delete();
        }

        parent::tearDown();
    }

    /**
     * Helper to track training id for cleanup.
     */
    protected function trackTraining(int $id): int
    {
        $this->createdTrainingIds[] = $id;
        return $id;
    }

    /**
     * 1. Test unauthorized guest cannot access create page, store, or search endpoints.
     */
    public function test_guest_cannot_access_create_page_or_store(): void
    {
        $resCreate = $this->get(route('manage.record.offline.create'));
        $this->assertTrue(in_array($resCreate->status(), [401, 302]));

        $resStore = $this->postJson(route('manage.record.offline.store'), [
            'course_name' => 'Test Unauthorized Training',
            'participants' => [['employee_id' => '99999', 'name' => 'John Doe']]
        ]);
        $this->assertTrue(in_array($resStore->status(), [401, 302]));

        $resSearch = $this->getJson(route('manage.record.offline.search-employees', ['q' => 'John']));
        $this->assertTrue(in_array($resSearch->status(), [401, 302]));
    }

    /**
     * 2. Test regular non-admin user cannot access create page or store offline training.
     */
    public function test_regular_user_cannot_access_create_page_or_store(): void
    {
        if (!$this->regularUser) {
            $this->markTestSkipped('No regular user found with role 1.');
        }

        $resCreate = $this->actingAs($this->regularUser)->get(route('manage.record.offline.create'));
        $this->assertTrue(in_array($resCreate->status(), [403, 302]));

        $resStore = $this->actingAs($this->regularUser)
            ->postJson(route('manage.record.offline.store'), [
                'course_name' => 'Test Regular User Training',
                'participants' => [['employee_id' => '99999', 'name' => 'Jane Doe']]
            ]);
        $this->assertTrue(in_array($resStore->status(), [403, 302]));
    }

    /**
     * 3. Test landing page renders link to create page, and NOT a modal or inline form.
     */
    public function test_landing_page_renders_add_training_link_pointing_to_create_page(): void
    {
        $response = $this->actingAs($this->admin)->get(route('manage.record.offline'));

        $response->assertStatus(200);

        // Assert + Add Training is an anchor pointing to offline.create
        $response->assertSee('id="btn-open-offline-add"', false);
        $response->assertSee(route('manage.record.offline.create'), false);

        // Assert NO modal trigger or modal markup on the landing page
        $response->assertDontSee('data-bs-target="#offlineAddTrainingModal"', false);
        $response->assertDontSee('id="offlineAddTrainingModal"', false);
        $response->assertDontSee('id="section-1-training-info"', false);
        $response->assertDontSee('id="section-2-participants"', false);
    }

    /**
     * 4. Test dedicated create page renders shell, back button, and all 4 sections in one page.
     */
    public function test_create_page_renders_shell_and_all_four_sections(): void
    {
        $response = $this->actingAs($this->admin)->get(route('manage.record.offline.create'));

        $response->assertStatus(200);

        // Header and navigation
        $response->assertSee('Back to Non KP OWL');
        $response->assertSee(route('manage.record.offline'), false);
        $response->assertSee('Add Non KP OWL Training');
        $response->assertSee('Create an offline employee training record.');

        // Form Navigation sidebar is removed (single-scroll full-width layout)
        $response->assertDontSee('Form Navigation');
        $response->assertDontSee('id="page-step-nav"', false);
        $response->assertDontSee('id="step-nav-1"', false);
        $response->assertDontSee('id="step-nav-2"', false);
        $response->assertDontSee('id="step-nav-3"', false);
        $response->assertDontSee('id="step-nav-4"', false);

        // Continuous 4 sections on the same page
        $response->assertSee('id="section-1-training-info"', false);
        $response->assertSee('id="section-2-participants"', false);
        $response->assertSee('id="section-3-results"', false);
        $response->assertSee('id="section-4-review"', false);

        // Action buttons
        $response->assertSee('id="btn-cancel-add-training"', false);
        $response->assertSee('id="btn-submit-add-training"', false);
        $response->assertSee('Save Training');
    }

    /**
     * 5. Test create page contains all expected Training, Participant, and Result fields.
     */
    public function test_create_page_contains_all_fields_and_components(): void
    {
        $response = $this->actingAs($this->admin)->get(route('manage.record.offline.create'));

        $response->assertStatus(200);

        // Section 1: Training Info fields
        $response->assertSee('id="add-course-name"', false);
        $response->assertSee('id="add-batch"', false);
        $response->assertSee('id="add-sub-course-name"', false);
        $response->assertSee('id="add-type"', false);
        $response->assertSee('id="add-category"', false);
        $response->assertSee('id="add-level"', false);
        $response->assertSee('id="add-main-classification"', false);
        $response->assertSee('id="add-sub-classification"', false);
        $response->assertSee('name="counted_corporate_kpi"', false);
        $response->assertSee('id="add-sub-corporate-kpi"', false);
        $response->assertSee('id="add-delivery-method"', false);
        $response->assertSee('id="add-sub-delivery-method"', false);
        $response->assertSee('id="add-provider"', false);
        $response->assertSee('id="add-sub-provider"', false);
        $response->assertSee('id="add-provider-name"', false);
        $response->assertSee('id="add-duration"', false);
        $response->assertSee('id="add-learning-hours"', false);
        $response->assertSee('id="add-start-date"', false);
        $response->assertSee('id="add-finish-date"', false);
        $response->assertSee('id="add-month"', false);
        $response->assertSee('id="add-venue"', false);
        $response->assertSee('id="add-instructor"', false);

        // Section 2: Participants Search & Manual entry
        $response->assertSee('id="participant-search-input"', false);
        $response->assertSee('id="btn-search-participant"', false);
        $response->assertSee('id="btn-toggle-manual-participant"', false);
        $response->assertSee('id="manual-emp-id"', false);
        $response->assertSee('id="manual-emp-name"', false);
        $response->assertSee('id="selected-participants-table"', false);

        // Section 3: Learning Results fields
        $response->assertSee('id="select-active-participant"', false);
        $response->assertSee('id="btn-prev-participant"', false);
        $response->assertSee('id="btn-next-participant"', false);
        $response->assertSee('id="res-progress"', false);
        $response->assertSee('id="res-status-badge"', false);
        $response->assertSee('id="res-assessment-score"', false);
        $response->assertSee('id="res-certificate"', false);
        $response->assertSee('name="res_attendance"', false);
        $response->assertSee('id="res-attendance-reason"', false);
        $response->assertSee('id="res-bond-status"', false);
        $response->assertSee('id="res-bond-end-date"', false);
        $response->assertSee('id="res-bond-amount"', false);
        $response->assertSee('id="res-l1-program"', false);
        $response->assertSee('id="res-l2-numerical"', false);
        $response->assertSee('id="res-l2-verbal"', false);
        $response->assertSee('id="res-l3-numerical"', false);
        $response->assertSee('id="res-l3-verbal"', false);
        $response->assertSee('id="res-pic"', false);

        // Section 4: Full Record Preview Table (replaces old summary cards)
        $response->assertSee('id="offline-review-record-table"', false);
        $response->assertSee('id="offline-review-tbody"', false);
        $response->assertSee('id="rev-part-count"', false);
        $response->assertSee('id="rev-footer-summary"', false);
        $response->assertSee('id="btn-review-edit-1"', false);
        $response->assertSee('id="btn-review-edit-2"', false);
        $response->assertSee('id="btn-review-edit-3"', false);
        $response->assertSee('Full Record Preview');
    }

    /**
     * 6. Test search employees endpoint returns matching records.
     */
    public function test_search_employees_endpoint_returns_json_results(): void
    {
        $user = User::first();
        $this->assertNotNull($user);

        $query = substr($user->name, 0, 4);
        $response = $this->actingAs($this->admin)
            ->getJson(route('manage.record.offline.search-employees', ['q' => $query]));

        $response->assertStatus(200);
        $response->assertJsonStructure(['results' => []]);
        $results = $response->json('results');
        $this->assertNotEmpty($results);

        $first = $results[0];
        $this->assertArrayHasKey('id', $first);
        $this->assertArrayHasKey('user_id', $first);
        $this->assertArrayHasKey('employee_id', $first);
        $this->assertArrayHasKey('name', $first);
        $this->assertArrayHasKey('department', $first);
    }

    /**
     * 7. Test manual add training creates 1 training and N participants in DB.
     */
    public function test_store_creates_training_and_participants_atomically(): void
    {
        $payload = [
            'course_name'           => 'TEST_MANUAL_Advanced Safety Induction',
            'sub_course_name'       => 'High Voltage Plant Protocol',
            'type'                  => 'In-House',
            'category'              => 'TEST_MANUAL_CAT',
            'level'                 => 'Intermediate',
            'batch'                 => 'Batch 2026-A',
            'main_classification'   => 'Technical',
            'sub_classification'    => 'Electrical Safety',
            'counted_corporate_kpi' => 1,
            'sub_corporate_kpi'     => 'Zero LTI Initiative',
            'delivery_method'       => 'Classroom',
            'sub_delivery_method'   => 'Hands-on Simulation',
            'provider'              => 'Internal',
            'sub_provider'          => 'Internal Trainer',
            'provider_name'         => 'PTKP Learning Center',
            'duration'              => '08:30:00',
            'learning_hours'        => 8.5,
            'start_date'            => '2026-03-01',
            'finish_date'           => '2026-03-02',
            'venue'                 => 'Safety Dojo Room 101',
            'instructor'            => 'Ir. Budi Santoso',
            'participants'          => [
                [
                    'employee_id'       => 'EMP_TEST_001',
                    'name'              => 'Alice Participant',
                    'grade'             => '4A',
                    'job_position'      => 'Junior Electrical Engineer',
                    'team'              => 'Maintenance Team 1',
                    'department'        => 'Electrical Maintenance',
                    'company'           => 'PT Krakatau Posco',
                    'progress'          => 100,
                    'assessment_score'  => 88.5,
                    'certificate'       => 'Yes',
                    'attendance'        => 1,
                    'attendance_reason' => null,
                    'bond_status'       => 'Yes',
                    'bond_end_date'     => '2027-03-01',
                    'bond_amount'       => 5000000,
                    'l1_program'        => '4.8 - Very Good',
                    'l2_numerical'      => 88.5,
                    'l2_verbal'         => 'Passed',
                    'l3_numerical'      => 90.0,
                    'l3_verbal'         => 'Fully Applied in Plant',
                    'pic'               => 'HRDC Safety Team',
                ],
                [
                    'employee_id'       => 'EMP_TEST_002',
                    'name'              => 'Bob Participant',
                    'grade'             => '3B',
                    'job_position'      => 'Technician',
                    'team'              => 'Maintenance Team 2',
                    'department'        => 'Mechanical Maintenance',
                    'company'           => 'PT Krakatau Posco',
                    'progress'          => 50,
                    'assessment_score'  => 72.0,
                    'certificate'       => 'No',
                    'attendance'        => 0,
                    'attendance_reason' => 'On Duty Emergency Callout',
                    'bond_status'       => 'No',
                    'bond_end_date'     => null,
                    'bond_amount'       => null,
                    'l1_program'        => '4.0 - Good',
                    'l2_numerical'      => 72.0,
                    'l2_verbal'         => 'Remedial',
                    'l3_numerical'      => null,
                    'l3_verbal'         => null,
                    'pic'               => 'HRDC Safety Team',
                ],
            ]
        ];

        $response = $this->actingAs($this->admin)
            ->postJson(route('manage.record.offline.store'), $payload);

        $response->assertStatus(201);
        $response->assertJson([
            'success' => true,
            'message' => 'Offline training successfully saved.',
        ]);

        $trainingId = $response->json('data.id');
        $this->assertNotNull($trainingId);
        $this->trackTraining($trainingId);

        // Verify OfflineTraining row in database
        $this->assertDatabaseHas('offline_trainings', [
            'id'                    => $trainingId,
            'course_name'           => 'TEST_MANUAL_Advanced Safety Induction',
            'sub_course_name'       => 'High Voltage Plant Protocol',
            'type'                  => 'In-House',
            'category'              => 'TEST_MANUAL_CAT',
            'level'                 => 'Intermediate',
            'batch'                 => 'Batch 2026-A',
            'main_classification'   => 'Technical',
            'sub_classification'    => 'Electrical Safety',
            'counted_corporate_kpi' => 1,
            'sub_corporate_kpi'     => 'Zero LTI Initiative',
            'delivery_method'       => 'Classroom',
            'sub_delivery_method'   => 'Hands-on Simulation',
            'provider'              => 'Internal',
            'sub_provider'          => 'Internal Trainer',
            'provider_name'         => 'PTKP Learning Center',
            'duration_seconds'      => 30600, // 8h 30m = 30600s
            'learning_hours'        => 8.5,
            'start_date'            => '2026-03-01',
            'finish_date'           => '2026-03-02',
            'venue'                 => 'Safety Dojo Room 101',
            'instructor'            => 'Ir. Budi Santoso',
        ]);

        // Verify Participant 1 in database
        $this->assertDatabaseHas('offline_training_users', [
            'offline_training_id' => $trainingId,
            'employee_id'         => 'EMP_TEST_001',
            'name'                => 'Alice Participant',
            'grade'               => '4A',
            'job_position'        => 'Junior Electrical Engineer',
            'team'                => 'Maintenance Team 1',
            'department'          => 'Electrical Maintenance',
            'company'             => 'PT Krakatau Posco',
            'learning_hours'      => 8.5,
            'progress'            => 100,
            'assessment_score'    => 88.5,
            'certificate'         => 'Yes',
            'attendance'          => 1,
            'attendance_reason'   => null,
            'bond_status'         => 'Yes',
            'bond_end_date'       => '2027-03-01',
            'bond_amount'         => 5000000,
            'l1_program'          => '4.8 - Very Good',
            'l2_numerical'        => 88.5,
            'l2_verbal'           => 'Passed',
            'l3_numerical'        => 90.0,
            'l3_verbal'           => 'Fully Applied in Plant',
            'pic'                 => 'HRDC Safety Team',
        ]);

        // Verify Participant 2 in database
        $this->assertDatabaseHas('offline_training_users', [
            'offline_training_id' => $trainingId,
            'employee_id'         => 'EMP_TEST_002',
            'name'                => 'Bob Participant',
            'grade'               => '3B',
            'job_position'        => 'Technician',
            'attendance'          => 0,
            'attendance_reason'   => 'On Duty Emergency Callout',
            'bond_status'         => 'No',
            'bond_end_date'       => null,
            'bond_amount'         => null,
            'progress'            => 50,
        ]);
    }

    /**
     * 8. Test LMS user matching: participant matching existing users.id_emp gets user_id populated.
     */
    public function test_participant_matching_lms_user_gets_user_id_linked(): void
    {
        $existingUser = User::whereNotNull('id_emp')->first();
        $this->assertNotNull($existingUser, 'Existing user with id_emp required.');

        $payload = [
            'course_name'  => 'TEST_MANUAL_Induction LMS Match',
            'participants' => [
                [
                    'employee_id' => $existingUser->id_emp,
                    'name'        => $existingUser->name,
                ]
            ]
        ];

        $response = $this->actingAs($this->admin)
            ->postJson(route('manage.record.offline.store'), $payload);

        $response->assertStatus(201);
        $trainingId = $response->json('data.id');
        $this->trackTraining($trainingId);

        $this->assertDatabaseHas('offline_training_users', [
            'offline_training_id' => $trainingId,
            'user_id'             => $existingUser->id,
            'employee_id'         => $existingUser->id_emp,
        ]);
    }

    /**
     * 9. Test duplicate participant within the same training event is rejected with 422.
     */
    public function test_duplicate_participant_within_same_training_is_rejected(): void
    {
        $payload = [
            'course_name'  => 'TEST_MANUAL_Duplicate Check',
            'participants' => [
                [
                    'employee_id' => 'EMP_DUP_001',
                    'name'        => 'Charlie Duplicate',
                ],
                [
                    'employee_id' => 'emp_dup_001', // same employee_id different case
                    'name'        => 'Charlie Duplicate Clone',
                ]
            ]
        ];

        $response = $this->actingAs($this->admin)
            ->postJson(route('manage.record.offline.store'), $payload);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['participants']);
    }

    /**
     * 10. Test validation errors: empty course name, finish date < start date, no participants.
     */
    public function test_validation_errors_handled_properly(): void
    {
        // Missing course name
        $res1 = $this->actingAs($this->admin)
            ->postJson(route('manage.record.offline.store'), [
                'course_name'  => '',
                'participants' => [['employee_id' => 'EMP_001', 'name' => 'Name 1']]
            ]);
        $res1->assertStatus(422);
        $res1->assertJsonValidationErrors(['course_name']);

        // Finish date earlier than start date
        $res2 = $this->actingAs($this->admin)
            ->postJson(route('manage.record.offline.store'), [
                'course_name'  => 'TEST_MANUAL_Date Error',
                'start_date'   => '2026-05-10',
                'finish_date'  => '2026-05-01',
                'participants' => [['employee_id' => 'EMP_001', 'name' => 'Name 1']]
            ]);
        $res2->assertStatus(422);
        $res2->assertJsonValidationErrors(['finish_date']);

        // No participants
        $res3 = $this->actingAs($this->admin)
            ->postJson(route('manage.record.offline.store'), [
                'course_name'  => 'TEST_MANUAL_No Participants',
                'participants' => []
            ]);
        $res3->assertStatus(422);
        $res3->assertJsonValidationErrors(['participants']);
    }

    /**
     * 11. Test transaction rollback on participant error: training is not orphaned.
     */
    public function test_database_transaction_rolls_back_on_error(): void
    {
        $initialTrainingCount = DB::table('offline_trainings')->count();

        $payload = [
            'course_name'  => 'TEST_MANUAL_Rollback Test',
            'participants' => [
                [
                    'employee_id' => 'EMP_VALID',
                    'name'        => 'Valid Name',
                ],
                [
                    'employee_id' => '', // Invalid missing ID triggers validation error
                    'name'        => 'Invalid Participant',
                ],
            ]
        ];

        $res = $this->actingAs($this->admin)
            ->postJson(route('manage.record.offline.store'), $payload);

        $res->assertStatus(422);

        // Ensure no new training was created in database
        $this->assertEquals($initialTrainingCount, DB::table('offline_trainings')->count());
        $this->assertDatabaseMissing('offline_trainings', [
            'course_name' => 'TEST_MANUAL_Rollback Test'
        ]);
    }

    /**
     * 12. Parity Test: Manual Add vs. Excel Schema parity.
     * Verifies that data submitted via Manual Add aligns with identical fields from Excel Schema.
     */
    public function test_manual_add_and_schema_field_parity(): void
    {
        $payload = [
            'course_name'           => 'TEST_MANUAL_Parity Training',
            'sub_course_name'       => 'Parity Sub Course',
            'type'                  => 'Certification',
            'category'              => 'TEST_MANUAL_CAT',
            'level'                 => 'Advanced',
            'batch'                 => 'Batch 1',
            'main_classification'   => 'Competency',
            'sub_classification'    => 'Quality Audit',
            'counted_corporate_kpi' => 1,
            'sub_corporate_kpi'     => 'Corporate Audit KPI',
            'delivery_method'       => 'Practical',
            'sub_delivery_method'   => 'Hands-on',
            'provider'              => 'External',
            'sub_provider'          => 'Vendor',
            'provider_name'         => 'PT Global Quality',
            'duration'              => '16:00:00',
            'learning_hours'        => 16.0,
            'start_date'            => '2026-04-10',
            'finish_date'           => '2026-04-12',
            'venue'                 => 'Auditorium Hall',
            'instructor'            => 'Dr. Haryanto',
            'participants'          => [
                [
                    'employee_id'       => 'EMP_PARITY_001',
                    'name'              => 'Parity User One',
                    'grade'             => '5A',
                    'job_position'      => 'Senior QA Engineer',
                    'team'              => 'QA Team',
                    'department'        => 'Quality Assurance',
                    'company'           => 'PT Krakatau Posco',
                    'learning_hours'    => 16.0,
                    'progress'          => 100,
                    'assessment_score'  => 95.0,
                    'certificate'       => 'Yes',
                    'attendance'        => 1,
                    'attendance_reason' => null,
                    'bond_status'       => 'Yes',
                    'bond_end_date'     => '2028-04-10',
                    'bond_amount'       => 10000000,
                    'l1_program'        => '5.0 - Excellent',
                    'l2_numerical'      => 95.0,
                    'l2_verbal'         => 'Distinction',
                    'l3_numerical'      => 96.0,
                    'l3_verbal'         => 'Audit Process Optimized',
                    'pic'               => 'QA Manager',
                ]
            ]
        ];

        $service = app(OfflineTrainingCreateService::class);
        $training = $service->create($payload, $this->admin->id);
        $this->trackTraining($training->id);

        $participant = $training->participants->first();
        $this->assertNotNull($participant);

        // Verify Training Event Schema Alignment
        $this->assertEquals('TEST_MANUAL_Parity Training', $training->course_name);
        $this->assertEquals('Certification', $training->type);
        $this->assertEquals('Competency', $training->main_classification);
        $this->assertEquals(57600, $training->duration_seconds); // 16h = 57600s
        $this->assertEquals(16.0, (float) $training->learning_hours);
        $this->assertEquals('2026-04-10', Carbon::parse($training->start_date)->format('Y-m-d'));
        $this->assertEquals('2026-04-12', Carbon::parse($training->finish_date)->format('Y-m-d'));

        // Verify Participant Level Schema Alignment
        $this->assertEquals('EMP_PARITY_001', $participant->employee_id);
        $this->assertEquals('Parity User One', $participant->name);
        $this->assertEquals(100.0, (float) $participant->progress);
        $this->assertEquals(95.0, (float) $participant->assessment_score);
        $this->assertEquals('Yes', $participant->certificate);
        $this->assertTrue((bool) $participant->attendance);
        $this->assertEquals('Yes', $participant->bond_status);
        $this->assertEquals('2028-04-10', Carbon::parse($participant->bond_end_date)->format('Y-m-d'));
        $this->assertEquals(10000000.0, (float) $participant->bond_amount);
        $this->assertEquals('5.0 - Excellent', $participant->l1_program);
        $this->assertEquals(95.0, (float) $participant->l2_numerical);
        $this->assertEquals('Distinction', $participant->l2_verbal);
        $this->assertEquals(96.0, (float) $participant->l3_numerical);
        $this->assertEquals('Audit Process Optimized', $participant->l3_verbal);
        $this->assertEquals('QA Manager', $participant->pic);
    }

    /**
     * 13. Test landing page still preserves Import Excel modal and Download Template.
     */
    public function test_landing_page_preserves_import_modal_and_template_download(): void
    {
        $response = $this->actingAs($this->admin)->get(route('manage.record.offline'));

        $response->assertStatus(200);

        // Import Excel modal button and modal markup
        $response->assertSee('id="btn-open-offline-import"', false);
        $response->assertSee('data-bs-target="#offlineImportModal"', false);
        $response->assertSee('id="offlineImportModal"', false);

        // Download template link
        $response->assertSee('id="btn-download-offline-template"', false);
        $response->assertSee(route('manage.record.offline.import.template'), false);
    }

    /**
     * 14. Test Step 4 Review & Save renders full 44-column canonical record preview table with zero database writes.
     */
    public function test_step_4_review_renders_full_44_column_canonical_record_preview_table_with_zero_database_writes(): void
    {
        $trainingsCountBefore = \Illuminate\Support\Facades\DB::table('offline_trainings')->count();
        $participantsCountBefore = \Illuminate\Support\Facades\DB::table('offline_training_users')->count();

        $response = $this->actingAs($this->admin)->get(route('manage.record.offline.create'));

        $response->assertStatus(200);

        // Assert Step 4 table structure
        $response->assertSee('id="offline-review-record-table"', false);
        $response->assertSee('id="offline-review-tbody"', false);
        $response->assertSee('Full Record Preview');
        $response->assertSee('44 Columns');
        $response->assertSee('id="rev-part-count-badge"', false);
        $response->assertSee('id="rev-empty-state"', false);

        // Assert all 44 canonical data-col-index attributes are rendered in header
        for ($col = 1; $col <= 44; $col++) {
            $response->assertSee('data-col-index="' . $col . '"', false);
        }

        // Assert Sticky column classes exist for Col 1 (No), Col 2 (ID), Col 3 (Participant Name)
        $response->assertSee('sticky-col-1', false);
        $response->assertSee('sticky-col-2', false);
        $response->assertSee('sticky-col-3', false);

        // Assert navigation edit buttons
        $response->assertSee('id="btn-review-edit-1"', false);
        $response->assertSee('id="btn-review-edit-2"', false);
        $response->assertSee('id="btn-review-edit-3"', false);

        // Assert ZERO database writes occurred during preview
        $trainingsCountAfter = \Illuminate\Support\Facades\DB::table('offline_trainings')->count();
        $participantsCountAfter = \Illuminate\Support\Facades\DB::table('offline_training_users')->count();

        $this->assertEquals($trainingsCountBefore, $trainingsCountAfter, 'Zero offline_trainings records must be written during preview.');
        $this->assertEquals($participantsCountBefore, $participantsCountAfter, 'Zero offline_training_users records must be written during preview.');
    }
}

