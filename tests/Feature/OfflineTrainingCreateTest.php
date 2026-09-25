<?php

namespace Tests\Feature;

use App\Models\OfflineTraining;
use App\Models\OfflineTrainingUser;
use App\Models\User;
use App\Services\Record\OfflineTrainingCreateService;
use App\Services\Record\OfflineTrainingImportService;
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
     * 1. Test unauthorized users cannot access store endpoint.
     */
    public function test_guest_cannot_store_or_search_offline_training(): void
    {
        $resStore = $this->postJson(route('manage.record.offline.store'), [
            'course_name' => 'Test Unauthorized Training',
            'participants' => [['employee_id' => '99999', 'name' => 'John Doe']]
        ]);
        $this->assertTrue(in_array($resStore->status(), [401, 302]));

        $resSearch = $this->getJson(route('manage.record.offline.search-employees', ['q' => 'John']));
        $this->assertTrue(in_array($resSearch->status(), [401, 302]));
    }

    /**
     * 2. Test regular non-admin user cannot store offline training.
     */
    public function test_regular_user_cannot_store_offline_training(): void
    {
        if (!$this->regularUser) {
            $this->markTestSkipped('No regular user found with role 1.');
        }

        $res = $this->actingAs($this->regularUser)
            ->postJson(route('manage.record.offline.store'), [
                'course_name' => 'Test Regular User Training',
                'participants' => [['employee_id' => '99999', 'name' => 'Jane Doe']]
            ]);

        $this->assertTrue(in_array($res->status(), [403, 302]));
    }

    /**
     * 3. Test modal elements and trigger button exist on offline landing page.
     */
    public function test_add_training_modal_rendered_on_offline_landing_page(): void
    {
        $response = $this->actingAs($this->admin)->get(route('manage.record.offline'));

        $response->assertStatus(200);
        $response->assertSee('id="btn-open-offline-add"', false);
        $response->assertSee('data-bs-target="#offlineAddTrainingModal"', false);
        $response->assertSee('id="offlineAddTrainingModal"', false);
        $response->assertSee('id="section-1-training-info"', false);
        $response->assertSee('id="section-2-participants"', false);
        $response->assertSee('id="section-3-results"', false);
        $response->assertSee('id="section-4-review"', false);
        $response->assertSee('id="modal-step-nav"', false);
    }

    /**
     * 4. Test search employees endpoint returns matching records.
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
     * 5. Test manual add training creates 1 training and N participants in DB.
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
     * 6. Test LMS user matching: participant matching existing users.id_emp gets user_id populated.
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
     * 7. Test duplicate participant within the same training event is rejected with 422.
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
     * 8. Test validation errors: empty course name, finish date < start date, no participants.
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
     * 9. Test transaction rollback on participant error: training is not orphaned.
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
     * 10. Parity Test: Manual Add vs. Excel Schema parity.
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
}
