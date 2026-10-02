<?php

namespace Tests\Feature;

use App\Models\OfflineTraining;
use App\Models\OfflineTrainingUser;
use App\Models\User;
use App\Services\Record\RecordOfflineQueryService;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class OfflineParticipantAndCourseDetailTest extends TestCase
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
            DB::table('offline_training_users')->whereIn('user_id', $this->createdUserIds)->delete();
            DB::table('users')->whereIn('id', $this->createdUserIds)->delete();
        }

        parent::tearDown();
    }

    /**
     * Helper to create a test user.
     */
    protected function createParticipantUser(string $name, string $nip): User
    {
        $safeNip = substr(preg_replace('/[^0-9A-Za-z]/', '', $nip), 0, 8);
        if (strlen($safeNip) < 8) {
            $safeNip = str_pad($safeNip, 8, '0', STR_PAD_RIGHT);
        }

        $user = User::create([
            'name'       => $name,
            'email'      => 'test_' . uniqid() . '@example.com',
            'password'   => bcrypt('password'),
            'id_emp'     => $safeNip,
            'role'       => User::ROLE_USER,
            'division'   => 'Technology Division',
            'team'       => 'Technology Team',
            'department' => 'IT Department',
            'ssohash'    => md5(uniqid()),
            'sap_code'   => 'SAP' . substr(uniqid(), 0, 5),
        ]);

        $this->createdUserIds[] = $user->id;

        return $user;
    }

    /**
     * Helper to create an offline training session.
     */
    protected function createTraining(array $attributes = []): OfflineTraining
    {
        $training = OfflineTraining::create(array_merge([
            'course_name'           => 'Korean Business Etiquette & Cross-Culture',
            'sub_course_name'       => 'Global Communication in Joint Venture',
            'type'                  => 'In-House',
            'category'              => 'Cultural Track',
            'level'                 => 'Intermediate',
            'batch'                 => 'Batch 1',
            'main_classification'   => 'Basic & Common Duties',
            'sub_classification'    => 'Business Culture',
            'counted_corporate_kpi' => true,
            'corporate_kpi_year'    => 2026,
            'sub_corporate_kpi'     => 'KPI Culture',
            'delivery_method'       => 'Classroom',
            'sub_delivery_method'   => 'Offline Lecture',
            'provider'              => 'Internal',
            'sub_provider'          => 'HRD Academy',
            'provider_name'         => 'PTKP Learning Center',
            'venue'                 => 'Auditorium Lt. 2',
            'instructor'            => 'Kim Min-seok',
            'duration_seconds'      => 14400,
            'learning_hours'        => 4.0,
            'start_date'            => '2026-05-10',
            'finish_date'           => '2026-05-11',
        ], $attributes));

        $this->createdTrainingIds[] = $training->id;

        return $training;
    }

    /**
     * Helper to enroll participant.
     */
    protected function enrollParticipant(OfflineTraining $training, array $attrs = []): OfflineTrainingUser
    {
        return OfflineTrainingUser::create(array_merge([
            'offline_training_id' => $training->id,
            'user_id'             => null,
            'employee_id'         => 'TEST_EMP_9988',
            'name'                => 'Dafa Isolated Tester',
            'grade'               => 'G4',
            'job_position'        => 'Junior Engineer',
            'team'                => 'Technology Team',
            'department'          => 'IT Department',
            'company'             => 'PT Krakatau Posco',
            'learning_hours'      => 4.0,
            'progress'            => 100.0,
            'assessment_score'    => 88.5,
            'certificate'         => 'CERT-001',
            'attendance'          => true,
            'attendance_reason'   => null,
        ], $attrs));
    }

    /*
    |--------------------------------------------------------------------------
    | PARTICIPANT DETAIL TESTS (1 - 10)
    |--------------------------------------------------------------------------
    */

    /**
     * 1. Participant Detail requires authentication.
     */
    public function test_participant_detail_requires_authentication(): void
    {
        $response = $this->get(route('manage.record.offline.participant', 'TEST_EMP_9988'));
        $response->assertRedirect(route('loginpage'));
    }

    /**
     * 2. Participant Detail requires record_management permission.
     */
    public function test_participant_detail_requires_record_management_permission(): void
    {
        if (!$this->regularUser) {
            $this->markTestSkipped('No regular user found for permission test.');
        }

        $response = $this->actingAs($this->regularUser)->get(route('manage.record.offline.participant', 'TEST_EMP_9988'));
        $response->assertStatus(403);
    }

    /**
     * 3. Existing participant returns 200.
     */
    public function test_existing_participant_returns_200(): void
    {
        $training = $this->createTraining();
        $this->enrollParticipant($training, ['employee_id' => 'TEST_EMP_9988', 'name' => 'Dafa Isolated Tester']);

        $response = $this->actingAs($this->admin)->get(route('manage.record.offline.participant', 'TEST_EMP_9988'));
        $response->assertStatus(200);
        $response->assertViewIs('main.record.offline.participant');
    }

    /**
     * 4. Unknown participant returns 404.
     */
    public function test_unknown_participant_returns_404(): void
    {
        $response = $this->actingAs($this->admin)->get(route('manage.record.offline.participant', 'NONEXISTENT_99999'));
        $response->assertStatus(404);
    }

    /**
     * 5. Shows selected participant identity.
     */
    public function test_shows_selected_participant_identity(): void
    {
        $training = $this->createTraining();
        $this->enrollParticipant($training, [
            'employee_id'  => 'TEST_EMP_9988',
            'name'         => 'Dafa Isolated Tester',
            'job_position' => 'Junior Engineer',
            'team'         => 'Technology Team',
            'department'   => 'IT Department',
            'company'      => 'PT Krakatau Posco',
        ]);

        $response = $this->actingAs($this->admin)->get(route('manage.record.offline.participant', 'TEST_EMP_9988'));
        $response->assertStatus(200);
        $response->assertSee('Dafa Isolated Tester');
        $response->assertSee('TEST_EMP_9988');
        $response->assertSee('Junior Engineer');
        $response->assertSee('IT Department');
        $response->assertSee('PT Krakatau Posco');
    }

    /**
     * 6. Shows all training records belonging to that participant.
     */
    public function test_shows_all_training_records_belonging_to_that_participant(): void
    {
        $t1 = $this->createTraining(['course_name' => 'Training Alpha']);
        $t2 = $this->createTraining(['course_name' => 'Training Beta']);
        $t3 = $this->createTraining(['course_name' => 'Training Gamma']);

        $this->enrollParticipant($t1, ['employee_id' => 'TEST_EMP_9988', 'name' => 'Dafa Isolated Tester']);
        $this->enrollParticipant($t2, ['employee_id' => 'TEST_EMP_9988', 'name' => 'Dafa Isolated Tester']);
        $this->enrollParticipant($t3, ['employee_id' => 'TEST_EMP_9988', 'name' => 'Dafa Isolated Tester']);

        $response = $this->actingAs($this->admin)->getJson(
            route('manage.record.offline.participant.datatable', 'TEST_EMP_9988')
        );

        $response->assertStatus(200);
        $json = $response->json();
        $this->assertEquals(3, $json['recordsTotal']);
        $this->assertEquals(3, $json['recordsFiltered']);

        $renderedCourses = collect($json['data'])->pluck('course_name')->implode(' ');
        $this->assertStringContainsString('Training Alpha', $renderedCourses);
        $this->assertStringContainsString('Training Beta', $renderedCourses);
        $this->assertStringContainsString('Training Gamma', $renderedCourses);
    }

    /**
     * 7. Does not show another employee's records.
     */
    public function test_does_not_show_another_employees_records(): void
    {
        $t1 = $this->createTraining(['course_name' => 'Dafa Special Training']);
        $t2 = $this->createTraining(['course_name' => 'Secret Other Employee Course']);

        $this->enrollParticipant($t1, ['employee_id' => 'TEST_EMP_9988', 'name' => 'Dafa Isolated Tester']);
        $this->enrollParticipant($t2, ['employee_id' => 'OTHER_EMP_7777', 'name' => 'Other Employee']);

        $response = $this->actingAs($this->admin)->getJson(
            route('manage.record.offline.participant.datatable', 'TEST_EMP_9988')
        );

        $response->assertStatus(200);
        $json = $response->json();
        $this->assertEquals(1, $json['recordsTotal']);

        $renderedCourses = collect($json['data'])->pluck('course_name')->implode(' ');
        $this->assertStringContainsString('Dafa Special Training', $renderedCourses);
        $this->assertStringNotContainsString('Secret Other Employee Course', $renderedCourses);
    }

    /**
     * 8. Excludes soft-deleted participant records.
     */
    public function test_excludes_soft_deleted_participant_records(): void
    {
        $t1 = $this->createTraining(['course_name' => 'Active Training']);
        $t2 = $this->createTraining(['course_name' => 'Soft Deleted Participation']);

        $this->enrollParticipant($t1, ['employee_id' => 'TEST_EMP_9988', 'name' => 'Dafa Isolated Tester']);
        $deletedP = $this->enrollParticipant($t2, ['employee_id' => 'TEST_EMP_9988', 'name' => 'Dafa Isolated Tester']);
        $deletedP->delete(); // Soft delete

        $response = $this->actingAs($this->admin)->getJson(
            route('manage.record.offline.participant.datatable', 'TEST_EMP_9988')
        );

        $response->assertStatus(200);
        $json = $response->json();
        $this->assertEquals(1, $json['recordsTotal']);

        $renderedCourses = collect($json['data'])->pluck('course_name')->implode(' ');
        $this->assertStringContainsString('Active Training', $renderedCourses);
        $this->assertStringNotContainsString('Soft Deleted Participation', $renderedCourses);
    }

    /**
     * 9. Excludes soft-deleted training records.
     */
    public function test_excludes_soft_deleted_training_records(): void
    {
        $t1 = $this->createTraining(['course_name' => 'Non-Deleted Training']);
        $t2 = $this->createTraining(['course_name' => 'Deleted Training Event']);

        $this->enrollParticipant($t1, ['employee_id' => 'TEST_EMP_9988', 'name' => 'Dafa Isolated Tester']);
        $this->enrollParticipant($t2, ['employee_id' => 'TEST_EMP_9988', 'name' => 'Dafa Isolated Tester']);

        $t2->delete(); // Soft delete training

        $response = $this->actingAs($this->admin)->getJson(
            route('manage.record.offline.participant.datatable', 'TEST_EMP_9988')
        );

        $response->assertStatus(200);
        $json = $response->json();
        $this->assertEquals(1, $json['recordsTotal']);

        $renderedCourses = collect($json['data'])->pluck('course_name')->implode(' ');
        $this->assertStringContainsString('Non-Deleted Training', $renderedCourses);
        $this->assertStringNotContainsString('Deleted Training Event', $renderedCourses);
    }

    /**
     * 10. Summary counts are participant-scoped.
     */
    public function test_summary_counts_are_participant_scoped(): void
    {
        $t1 = $this->createTraining(['learning_hours' => 5.0]);
        $t2 = $this->createTraining(['learning_hours' => 3.0]);
        $t3 = $this->createTraining(['learning_hours' => 10.0]);

        // Dafa: 1 finished (5 hrs), 1 ongoing (3 hrs) = 2 total, 8 hrs
        $this->enrollParticipant($t1, ['employee_id' => 'TEST_EMP_9988', 'learning_hours' => 5.0, 'progress' => 100.0]);
        $this->enrollParticipant($t2, ['employee_id' => 'TEST_EMP_9988', 'learning_hours' => 3.0, 'progress' => 50.0]);

        // Another employee: 1 finished (10 hrs)
        $this->enrollParticipant($t3, ['employee_id' => 'OTHER_EMP_7777', 'learning_hours' => 10.0, 'progress' => 100.0]);

        $participantInfo = RecordOfflineQueryService::resolveParticipant('TEST_EMP_9988');
        $this->assertNotNull($participantInfo);

        $summary = RecordOfflineQueryService::getParticipantSummary($participantInfo);

        $this->assertEquals(2, $summary['total_training'], 'Total training must strictly count Dafa only (2).');
        $this->assertEquals(1, $summary['finished'], 'Finished must strictly be 1.');
        $this->assertEquals(1, $summary['ongoing'], 'Ongoing must strictly be 1.');
        $this->assertEquals(8.0, $summary['learning_hours'], 'Learning hours must strictly sum Dafa only (5 + 3 = 8).');
    }

    /*
    |--------------------------------------------------------------------------
    | COURSE DETAIL TESTS (11 - 17)
    |--------------------------------------------------------------------------
    */

    /**
     * 11. Existing training returns 200.
     */
    public function test_existing_training_returns_200(): void
    {
        $training = $this->createTraining();

        $response = $this->actingAs($this->admin)->get(route('manage.record.offline.course', $training->id));
        $response->assertStatus(200);
        $response->assertViewIs('main.record.offline.course');
    }

    /**
     * 12. Unknown training returns 404.
     */
    public function test_unknown_training_returns_404(): void
    {
        $response = $this->actingAs($this->admin)->get(route('manage.record.offline.course', 99999999));
        $response->assertStatus(404);

        // Also verify soft-deleted training returns 404
        $training = $this->createTraining();
        $training->delete();

        $responseDeleted = $this->actingAs($this->admin)->get(route('manage.record.offline.course', $training->id));
        $responseDeleted->assertStatus(404);
    }

    /**
     * 13. Course metadata renders correctly.
     */
    public function test_course_metadata_renders_correctly(): void
    {
        $training = $this->createTraining([
            'course_name'         => 'Safety Leadership Excellence',
            'sub_course_name'     => 'Incident Investigation Mastery',
            'type'                => 'Certification',
            'category'            => 'HSE',
            'batch'               => 'Batch 7',
            'main_classification' => 'Quality & Safety',
            'venue'               => 'Auditorium 1',
            'instructor'          => 'Dr. Aris W.',
        ]);

        $response = $this->actingAs($this->admin)->get(route('manage.record.offline.course', $training->id));
        $response->assertStatus(200);
        $response->assertSee('Safety Leadership Excellence');
        $response->assertSee('Incident Investigation Mastery');
        $response->assertSee('Certification');
        $response->assertSee('Batch: Batch 7');
        $response->assertSee('Quality & Safety');
        // Detailed metadata grid is removed from header
        $response->assertDontSee('TRAINING METADATA GRID');
        $response->assertDontSee('Auditorium 1');
        $response->assertDontSee('Dr. Aris W.');
    }

    /**
     * 14. Shows all participants for selected offline_training_id.
     */
    public function test_shows_all_participants_for_selected_offline_training_id(): void
    {
        $training = $this->createTraining();

        $this->enrollParticipant($training, ['employee_id' => '11111111', 'name' => 'Alice']);
        $this->enrollParticipant($training, ['employee_id' => '22222222', 'name' => 'Bob']);
        $this->enrollParticipant($training, ['employee_id' => '33333333', 'name' => 'Charlie']);

        $response = $this->actingAs($this->admin)->getJson(
            route('manage.record.offline.course.datatable', $training->id)
        );

        $response->assertStatus(200);
        $json = $response->json();
        $this->assertEquals(3, $json['recordsTotal']);
        $this->assertEquals(3, $json['recordsFiltered']);

        $renderedParticipants = collect($json['data'])->pluck('participant_name')->implode(' ');
        $this->assertStringContainsString('Alice', $renderedParticipants);
        $this->assertStringContainsString('Bob', $renderedParticipants);
        $this->assertStringContainsString('Charlie', $renderedParticipants);
    }

    /**
     * 15. Does not include participants from another training.
     */
    public function test_does_not_include_participants_from_another_training(): void
    {
        $t1 = $this->createTraining(['course_name' => 'Target Course']);
        $t2 = $this->createTraining(['course_name' => 'Other Course']);

        $this->enrollParticipant($t1, ['employee_id' => '11111111', 'name' => 'Target Participant']);
        $this->enrollParticipant($t2, ['employee_id' => '99999999', 'name' => 'Other Participant']);

        $response = $this->actingAs($this->admin)->getJson(
            route('manage.record.offline.course.datatable', $t1->id)
        );

        $response->assertStatus(200);
        $json = $response->json();
        $this->assertEquals(1, $json['recordsTotal']);

        $renderedParticipants = collect($json['data'])->pluck('participant_name')->implode(' ');
        $this->assertStringContainsString('Target Participant', $renderedParticipants);
        $this->assertStringNotContainsString('Other Participant', $renderedParticipants);
    }

    /**
     * 16. Same course_name with different training ID does NOT get mixed.
     */
    public function test_same_course_name_with_different_training_id_does_not_get_mixed(): void
    {
        $courseTitle = 'Identical Course Name Across Batches';

        $batch1 = $this->createTraining(['course_name' => $courseTitle, 'batch' => 'Batch 1']);
        $batch2 = $this->createTraining(['course_name' => $courseTitle, 'batch' => 'Batch 2']);

        $this->enrollParticipant($batch1, ['employee_id' => '1001', 'name' => 'Batch 1 Employee A']);
        $this->enrollParticipant($batch1, ['employee_id' => '1002', 'name' => 'Batch 1 Employee B']);

        $this->enrollParticipant($batch2, ['employee_id' => '2001', 'name' => 'Batch 2 Employee X']);

        // Check Batch 1 course datatable
        $res1 = $this->actingAs($this->admin)->getJson(
            route('manage.record.offline.course.datatable', $batch1->id)
        );
        $res1->assertStatus(200);
        $json1 = $res1->json();
        $this->assertEquals(2, $json1['recordsTotal'], 'Batch 1 must contain exactly 2 participants.');
        $names1 = collect($json1['data'])->pluck('participant_name')->implode(' ');
        $this->assertStringContainsString('Batch 1 Employee A', $names1);
        $this->assertStringContainsString('Batch 1 Employee B', $names1);
        $this->assertStringNotContainsString('Batch 2 Employee X', $names1);

        // Check Batch 2 course datatable
        $res2 = $this->actingAs($this->admin)->getJson(
            route('manage.record.offline.course.datatable', $batch2->id)
        );
        $res2->assertStatus(200);
        $json2 = $res2->json();
        $this->assertEquals(1, $json2['recordsTotal'], 'Batch 2 must contain exactly 1 participant.');
        $names2 = collect($json2['data'])->pluck('participant_name')->implode(' ');
        $this->assertStringContainsString('Batch 2 Employee X', $names2);
        $this->assertStringNotContainsString('Batch 1 Employee A', $names2);
    }

    /**
     * 17. Summary metrics are course/event scoped.
     */
    public function test_summary_metrics_are_course_event_scoped(): void
    {
        $training = $this->createTraining(['learning_hours' => 6.5]);

        // 2 finished (100%), 1 ongoing (40%) -> avg progress = (100+100+40)/3 = 80.0%
        $this->enrollParticipant($training, ['employee_id' => '101', 'progress' => 100.0]);
        $this->enrollParticipant($training, ['employee_id' => '102', 'progress' => 100.0]);
        $this->enrollParticipant($training, ['employee_id' => '103', 'progress' => 40.0]);

        $summary = RecordOfflineQueryService::getCourseSummary($training->id, $training);

        $this->assertEquals(3, $summary['total_participants']);
        $this->assertEquals(2, $summary['finished']);
        $this->assertEquals(1, $summary['ongoing']);
        $this->assertEquals(80.0, $summary['avg_progress']);
        $this->assertEquals(6.5, $summary['learning_hours']);
    }

    /*
    |--------------------------------------------------------------------------
    | CLASSIFICATION TABLE CLICKABLE LINKS & ACTIONS (18 - 23)
    |--------------------------------------------------------------------------
    */

    /**
     * 18. Participant name renders as link.
     */
    public function test_participant_name_renders_as_link(): void
    {
        $uniqueClass = 'Unique Class ' . uniqid();
        $training = $this->createTraining(['main_classification' => $uniqueClass]);
        $this->enrollParticipant($training, ['employee_id' => 'TEST_EMP_9988', 'name' => 'Dafa Unique Link Tester']);

        $response = $this->actingAs($this->admin)->getJson(
            route('manage.record.offline.classification.datatable', \Illuminate\Support\Str::slug($uniqueClass)) . '?draw=1'
        );

        $response->assertStatus(200);
        $json = $response->json();
        $this->assertNotEmpty($json['data']);

        $firstRow = $json['data'][0];
        $this->assertStringContainsString('<a ', $firstRow['participant_name']);
        $this->assertStringContainsString('participant-link', $firstRow['participant_name']);
        $this->assertStringContainsString('Dafa Unique Link Tester', $firstRow['participant_name']);
    }

    /**
     * 19. Course name renders as link.
     */
    public function test_course_name_renders_as_link(): void
    {
        $uniqueClass = 'Unique Class ' . uniqid();
        $training = $this->createTraining([
            'course_name'         => 'Unique Course Name ' . uniqid(),
            'main_classification' => $uniqueClass,
        ]);
        $this->enrollParticipant($training, ['employee_id' => 'TEST_EMP_9988', 'name' => 'Dafa Unique Link Tester']);

        $response = $this->actingAs($this->admin)->getJson(
            route('manage.record.offline.classification.datatable', \Illuminate\Support\Str::slug($uniqueClass)) . '?draw=1'
        );

        $response->assertStatus(200);
        $json = $response->json();
        $this->assertNotEmpty($json['data']);

        $firstRow = $json['data'][0];
        $this->assertStringContainsString('<a ', $firstRow['course_name']);
        $this->assertStringContainsString('course-link', $firstRow['course_name']);
        $this->assertStringContainsString($training->course_name, $firstRow['course_name']);
    }

    /**
     * 20. Participant link targets participant detail.
     */
    public function test_participant_link_targets_participant_detail(): void
    {
        $uniqueClass = 'Unique Class ' . uniqid();
        $training = $this->createTraining(['main_classification' => $uniqueClass]);
        $this->enrollParticipant($training, ['employee_id' => 'TEST_EMP_9988', 'name' => 'Dafa Unique Link Tester']);

        $response = $this->actingAs($this->admin)->getJson(
            route('manage.record.offline.classification.datatable', \Illuminate\Support\Str::slug($uniqueClass)) . '?draw=1'
        );

        $response->assertStatus(200);
        $firstRow = $response->json('data')[0];

        $expectedUrlSegment = url('/manage/record/offline/participant/TEST_EMP_9988');
        $this->assertStringContainsString($expectedUrlSegment, $firstRow['participant_name']);
    }

    /**
     * 21. Course link targets course detail.
     */
    public function test_course_link_targets_course_detail(): void
    {
        $uniqueClass = 'Unique Class ' . uniqid();
        $training = $this->createTraining(['main_classification' => $uniqueClass]);
        $this->enrollParticipant($training, ['employee_id' => 'TEST_EMP_9988', 'name' => 'Dafa Unique Link Tester']);

        $response = $this->actingAs($this->admin)->getJson(
            route('manage.record.offline.classification.datatable', \Illuminate\Support\Str::slug($uniqueClass)) . '?draw=1'
        );

        $response->assertStatus(200);
        $firstRow = $response->json('data')[0];

        $expectedUrlSegment = url('/manage/record/offline/course/' . $training->id);
        $this->assertStringContainsString($expectedUrlSegment, $firstRow['course_name']);
    }

    /**
     * 22. Other columns remain non-clickable.
     */
    public function test_other_columns_remain_non_clickable(): void
    {
        $uniqueClass = 'Unique Class ' . uniqid();
        $training = $this->createTraining([
            'course_name'         => 'Sample Test Course',
            'sub_course_name'     => 'Sample Sub Course',
            'type'                => 'In-House',
            'category'            => 'General Category',
            'main_classification' => $uniqueClass,
        ]);
        $this->enrollParticipant($training, [
            'employee_id'  => 'TEST_EMP_9988',
            'name'         => 'Dafa Unique Link Tester',
            'grade'        => 'G3',
            'job_position' => 'Staff Specialist',
            'team'         => 'Team A',
            'department'   => 'Dept B',
            'company'      => 'PT Krakatau Posco',
        ]);

        $response = $this->actingAs($this->admin)->getJson(
            route('manage.record.offline.classification.datatable', \Illuminate\Support\Str::slug($uniqueClass)) . '?draw=1'
        );

        $response->assertStatus(200);
        $firstRow = $response->json('data')[0];

        // Verify other columns DO NOT have <a> tags
        $this->assertStringNotContainsString('<a ', $firstRow['employee_id']);
        $this->assertStringNotContainsString('<a ', $firstRow['grade']);
        $this->assertStringNotContainsString('<a ', $firstRow['job_position']);
        $this->assertStringNotContainsString('<a ', $firstRow['team']);
        $this->assertStringNotContainsString('<a ', $firstRow['department']);
        $this->assertStringNotContainsString('<a ', $firstRow['company']);
        $this->assertStringNotContainsString('<a ', $firstRow['sub_course_name']);
        $this->assertStringNotContainsString('<a ', $firstRow['type_badge']);
        $this->assertStringNotContainsString('<a ', $firstRow['category']);
    }

    /**
     * 23. Existing View/Edit/Delete actions continue working.
     */
    public function test_existing_view_edit_delete_actions_continue_working(): void
    {
        $uniqueClass = 'Unique Class ' . uniqid();
        $training = $this->createTraining(['main_classification' => $uniqueClass]);
        $participant = $this->enrollParticipant($training, ['employee_id' => 'TEST_EMP_9988', 'name' => 'Dafa Unique Link Tester']);

        $response = $this->actingAs($this->admin)->getJson(
            route('manage.record.offline.classification.datatable', \Illuminate\Support\Str::slug($uniqueClass)) . '?draw=1'
        );

        $response->assertStatus(200);
        $firstRow = $response->json('data')[0];

        $actionHtml = $firstRow['action'];

        // View detail modal button
        $this->assertStringContainsString('btn-view-offline-detail', $actionHtml);
        $this->assertStringContainsString('data-id="' . $participant->id . '"', $actionHtml);

        // Edit button
        $this->assertStringContainsString('btn-edit-offline-record', $actionHtml);
        $this->assertStringContainsString(route('manage.record.offline.edit', $participant->id), $actionHtml);

        // Delete button
        $this->assertStringContainsString('btn-delete-offline-record', $actionHtml);
        $this->assertStringContainsString('data-id="' . $participant->id . '"', $actionHtml);
    }

    /**
     * 24. Course datatable handles standard jQuery DataTables search array payload without ErrorException.
     */
    public function test_course_datatable_handles_datatables_search_array_payload_and_returns_participants(): void
    {
        $training = $this->createTraining();
        $this->enrollParticipant($training, ['employee_id' => 'COURSE_EMP_1', 'name' => 'Alice']);
        $this->enrollParticipant($training, ['employee_id' => 'COURSE_EMP_2', 'name' => 'Bob']);

        $response = $this->actingAs($this->admin)->getJson(
            route('manage.record.offline.course.datatable', $training->id),
            [
                'draw' => 1,
                'search' => ['value' => '', 'regex' => 'false'],
                'participant' => '',
                'status' => 'all',
                'start' => 0,
                'length' => 25,
            ]
        );

        $response->assertStatus(200);
        $this->assertEquals(2, $response->json('recordsTotal'));
        $this->assertEquals(2, $response->json('recordsFiltered'));
        $this->assertCount(2, $response->json('data'));
    }

    /**
     * 25. Participant detail Blade renders full raw canonical table and More Filters modal.
     */
    public function test_participant_detail_renders_raw_record_table_and_more_filters_modal(): void
    {
        $training = $this->createTraining();
        $user = $this->createParticipantUser('Dafa Full View Tester', 'NIP_FULL_99');
        $this->enrollParticipant($training, [
            'user_id'     => $user->id,
            'employee_id' => $user->id_emp,
            'name'        => $user->name,
        ]);

        $response = $this->actingAs($this->admin)->get(
            route('manage.record.offline.participant', $user->id) . '?classification=basic-common-duties'
        );

        $response->assertStatus(200);
        $response->assertSee('Training Records');
        $response->assertSee('More Filters');
        $response->assertSee('Excel (.xlsx)');
        $response->assertSee('CSV (.csv)');
        $response->assertSee('PDF Landscape (.pdf)');
        $response->assertSee('MAIN CLASSIFICATION');
        $response->assertSee('COUNTED CORPORATE KPI');
        $response->assertSee('DELIVERY METHOD');
        $response->assertSee('BOND STATUS');
    }

    /**
     * 26. Participant datatable returns all canonical 44 columns + status + action.
     */
    public function test_participant_datatable_returns_all_canonical_44_columns(): void
    {
        $training = $this->createTraining([
            'course_name'           => 'Corporate Governance Mastery',
            'sub_course_name'       => 'Anti-Bribery Compliance',
            'main_classification'   => 'Management',
            'sub_classification'    => 'Compliance',
            'counted_corporate_kpi' => true,
            'sub_corporate_kpi'     => 'KPI Legal',
            'delivery_method'       => 'Classroom',
            'sub_delivery_method'   => 'In-Person Workshop',
            'provider'              => 'External',
            'sub_provider'          => 'Legal Firm',
            'provider_name'         => 'Law & Integrity Partners',
            'venue'                 => 'Training Room 1',
            'instructor'            => 'Advocate John',
            'duration_seconds'      => 7200,
            'learning_hours'        => 2.0,
            'start_date'            => '2026-06-01',
            'finish_date'           => '2026-06-02',
        ]);
        $user = $this->createParticipantUser('Schema Verification User', 'NIP_SCHEMA_01');
        $this->enrollParticipant($training, [
            'user_id'           => $user->id,
            'employee_id'       => $user->id_emp,
            'name'              => $user->name,
            'progress'          => 100,
            'learning_hours'    => 2.0,
            'assessment_score'  => 95.0,
            'certificate'       => 'Yes',
            'attendance'        => true,
            'attendance_reason' => null,
            'bond_status'       => 'Yes',
            'bond_end_date'     => '2027-06-01',
            'bond_amount'       => 5000000,
            'l1_program'        => 'L1 Completed',
            'l2_numerical'      => 95.0,
            'l2_verbal'         => 'High Competence',
            'l3_numerical'      => 90.0,
            'l3_verbal'         => 'Applied to Project',
            'pic'               => 'PIC HR',
        ]);

        $response = $this->actingAs($this->admin)->getJson(
            route('manage.record.offline.participant.datatable', $user->id) . '?draw=1'
        );

        $response->assertStatus(200);
        $this->assertEquals(1, $response->json('recordsTotal'));
        $row = $response->json('data')[0];

        // Verify keys exist in row
        $this->assertArrayHasKey('status_badge', $row);
        $this->assertArrayHasKey('employee_id', $row);
        $this->assertArrayHasKey('participant_name', $row);
        $this->assertArrayHasKey('grade', $row);
        $this->assertArrayHasKey('job_position', $row);
        $this->assertArrayHasKey('team', $row);
        $this->assertArrayHasKey('department', $row);
        $this->assertArrayHasKey('company', $row);
        $this->assertArrayHasKey('course_name', $row);
        $this->assertArrayHasKey('sub_course_name', $row);
        $this->assertArrayHasKey('type_badge', $row);
        $this->assertArrayHasKey('category', $row);
        $this->assertArrayHasKey('level', $row);
        $this->assertArrayHasKey('batch', $row);
        $this->assertArrayHasKey('main_classification', $row);
        $this->assertArrayHasKey('sub_classification', $row);
        $this->assertArrayHasKey('counted_corporate_kpi', $row);
        $this->assertArrayHasKey('sub_corporate_kpi', $row);
        $this->assertArrayHasKey('delivery_method', $row);
        $this->assertArrayHasKey('sub_delivery_method', $row);
        $this->assertArrayHasKey('provider', $row);
        $this->assertArrayHasKey('sub_provider', $row);
        $this->assertArrayHasKey('provider_name', $row);
        $this->assertArrayHasKey('venue', $row);
        $this->assertArrayHasKey('instructor', $row);
        $this->assertArrayHasKey('duration', $row);
        $this->assertArrayHasKey('course_learning_hours', $row);
        $this->assertArrayHasKey('start_date', $row);
        $this->assertArrayHasKey('finish_date', $row);
        $this->assertArrayHasKey('month', $row);
        $this->assertArrayHasKey('learning_hours', $row);
        $this->assertArrayHasKey('progress_badge', $row);
        $this->assertArrayHasKey('assessment_score', $row);
        $this->assertArrayHasKey('certificate_badge', $row);
        $this->assertArrayHasKey('attendance_badge', $row);
        $this->assertArrayHasKey('attendance_reason', $row);
        $this->assertArrayHasKey('bond_status', $row);
        $this->assertArrayHasKey('bond_end_date', $row);
        $this->assertArrayHasKey('bond_amount', $row);
        $this->assertArrayHasKey('l1_program', $row);
        $this->assertArrayHasKey('l2_numerical', $row);
        $this->assertArrayHasKey('l2_verbal', $row);
        $this->assertArrayHasKey('l3_numerical', $row);
        $this->assertArrayHasKey('l3_verbal', $row);
        $this->assertArrayHasKey('pic', $row);
        $this->assertArrayHasKey('action', $row);

        // Course name must be a link to course detail
        $this->assertStringContainsString(route('manage.record.offline.course', $training->id), $row['course_name']);
    }

    /**
     * 27. Crucial Rule: classification query param on participant detail does NOT restrict records.
     */
    public function test_participant_classification_query_param_does_not_filter_participant_records(): void
    {
        $training1 = $this->createTraining(['main_classification' => 'Leadership']);
        $training2 = $this->createTraining(['main_classification' => 'Technical']);
        $user = $this->createParticipantUser('Multi Class Employee', 'NIP_MULTI_01');

        $this->enrollParticipant($training1, ['user_id' => $user->id, 'employee_id' => $user->id_emp]);
        $this->enrollParticipant($training2, ['user_id' => $user->id, 'employee_id' => $user->id_emp]);

        // Request with ?classification=leadership (context from back navigation)
        $response = $this->actingAs($this->admin)->getJson(
            route('manage.record.offline.participant.datatable', $user->id) . '?classification=leadership&draw=1'
        );

        $response->assertStatus(200);
        // Must show BOTH records (2), not just the 1 matching the back navigation parameter
        $this->assertEquals(2, $response->json('recordsTotal'));
    }

    /**
     * 28. Explicit filter_classification from More Filters does filter records.
     */
    public function test_participant_explicit_more_filter_classification_filters_records(): void
    {
        $training1 = $this->createTraining(['main_classification' => 'Leadership']);
        $training2 = $this->createTraining(['main_classification' => 'Technical']);
        $user = $this->createParticipantUser('Explicit Filter Employee', 'NIP_EXPLICIT_01');

        $this->enrollParticipant($training1, ['user_id' => $user->id, 'employee_id' => $user->id_emp]);
        $this->enrollParticipant($training2, ['user_id' => $user->id, 'employee_id' => $user->id_emp]);

        // Explicit filter_classification=Technical from More Filters modal
        $response = $this->actingAs($this->admin)->getJson(
            route('manage.record.offline.participant.datatable', $user->id) . '?filter_classification=Technical&draw=1'
        );

        $response->assertStatus(200);
        $this->assertEquals(1, $response->json('recordsTotal'));
        $this->assertEquals('Technical', $response->json('data')[0]['main_classification']);
    }

    /**
     * 29. Participant More Filters (status, progress, attendance) work correctly.
     */
    public function test_participant_more_filters_work_correctly(): void
    {
        $training1 = $this->createTraining(['course_name' => 'Finished Course']);
        $training2 = $this->createTraining(['course_name' => 'Ongoing Course']);
        $user = $this->createParticipantUser('Filter Test Employee', 'NIP_FILTERS_01');

        $this->enrollParticipant($training1, ['user_id' => $user->id, 'employee_id' => $user->id_emp, 'progress' => 100, 'attendance' => true]);
        $this->enrollParticipant($training2, ['user_id' => $user->id, 'employee_id' => $user->id_emp, 'progress' => 50, 'attendance' => false]);

        // Status filter: finished
        $resFinished = $this->actingAs($this->admin)->getJson(
            route('manage.record.offline.participant.datatable', $user->id) . '?status=finished&draw=1'
        );
        $this->assertEquals(1, $resFinished->json('recordsTotal'));
        $this->assertStringContainsString('Finished Course', $resFinished->json('data')[0]['course_name']);

        // Attendance filter: absent
        $resAbsent = $this->actingAs($this->admin)->getJson(
            route('manage.record.offline.participant.datatable', $user->id) . '?attendance=absent&draw=1'
        );
        $this->assertEquals(1, $resAbsent->json('recordsTotal'));
        $this->assertStringContainsString('Ongoing Course', $resAbsent->json('data')[0]['course_name']);
    }

    /**
     * 30. Participant Excel export returns .xlsx with status 200.
     */
    public function test_participant_export_excel_returns_xlsx(): void
    {
        $training = $this->createTraining();
        $user = $this->createParticipantUser('Excel Export Employee', 'NIP_EXCEL_01');
        $this->enrollParticipant($training, ['user_id' => $user->id, 'employee_id' => $user->id_emp]);

        $response = $this->actingAs($this->admin)->get(
            route('manage.record.offline.participant.export.excel', $user->id)
        );

        $response->assertStatus(200);
        $this->assertStringContainsString('spreadsheetml.sheet', $response->headers->get('content-type'));
    }

    /**
     * 31. Participant CSV export returns .csv with status 200.
     */
    public function test_participant_export_csv_returns_csv(): void
    {
        $training = $this->createTraining();
        $user = $this->createParticipantUser('CSV Export Employee', 'NIP_CSV_01');
        $this->enrollParticipant($training, ['user_id' => $user->id, 'employee_id' => $user->id_emp]);

        $response = $this->actingAs($this->admin)->get(
            route('manage.record.offline.participant.export.csv', $user->id)
        );

        $response->assertStatus(200);
        $this->assertStringContainsString('text/csv', $response->headers->get('content-type'));
    }

    /**
     * 32. Participant PDF landscape export returns .pdf with status 200.
     */
    public function test_participant_export_pdf_returns_pdf(): void
    {
        $training = $this->createTraining();
        $user = $this->createParticipantUser('PDF Export Employee', 'NIP_PDF_01');
        $this->enrollParticipant($training, ['user_id' => $user->id, 'employee_id' => $user->id_emp]);

        $response = $this->actingAs($this->admin)->get(
            route('manage.record.offline.participant.export.pdf', $user->id)
        );

        $response->assertStatus(200);
        $this->assertStringContainsString('application/pdf', $response->headers->get('content-type'));
    }

    /**
     * 33. Participant export endpoints require authentication and record management permissions.
     */
    public function test_participant_export_endpoints_require_authentication_and_permission(): void
    {
        $training = $this->createTraining();
        $user = $this->createParticipantUser('Auth Test Employee', 'NIP_AUTH_01');
        $this->enrollParticipant($training, ['user_id' => $user->id, 'employee_id' => $user->id_emp]);

        // Unauthenticated -> redirect
        $this->get(route('manage.record.offline.participant.export.excel', $user->id))->assertRedirect();
        $this->get(route('manage.record.offline.participant.export.csv', $user->id))->assertRedirect();
        $this->get(route('manage.record.offline.participant.export.pdf', $user->id))->assertRedirect();

        // Regular user without record_management permission -> 403
        if ($this->regularUser) {
            $this->actingAs($this->regularUser)->get(route('manage.record.offline.participant.export.excel', $user->id))->assertStatus(403);
            $this->actingAs($this->regularUser)->get(route('manage.record.offline.participant.export.csv', $user->id))->assertStatus(403);
            $this->actingAs($this->regularUser)->get(route('manage.record.offline.participant.export.pdf', $user->id))->assertStatus(403);
        }
    }

    /*
    |--------------------------------------------------------------------------
    | NEW COURSE DETAIL CANONICAL VIEW & EXPORT TESTS (34 - 44)
    |--------------------------------------------------------------------------
    */

    /**
     * 34. Course Detail requires authentication and record_management permission.
     */
    public function test_course_detail_requires_authentication_and_permission(): void
    {
        $training = $this->createTraining();

        // Unauthenticated -> redirect
        $this->get(route('manage.record.offline.course', $training->id))->assertRedirect();

        // Regular user without permission -> 403
        if ($this->regularUser) {
            $this->actingAs($this->regularUser)->get(route('manage.record.offline.course', $training->id))->assertStatus(403);
        }
    }

    /**
     * 35. Course Detail Blade renders simplified header without metadata grid and displays KPI cards.
     */
    public function test_course_detail_renders_simplified_header_kpi_cards_and_export_dropdown(): void
    {
        $training = $this->createTraining([
            'course_name'         => 'Advanced Python Architecture',
            'sub_course_name'     => 'AsyncIO and Microservices',
            'type'                => 'In-House',
            'batch'               => 'Batch 3',
            'main_classification' => 'Technical',
        ]);
        $this->enrollParticipant($training, ['employee_id' => 'EMP_KPI_1', 'progress' => 100.0]);
        $this->enrollParticipant($training, ['employee_id' => 'EMP_KPI_2', 'progress' => 50.0]);

        $response = $this->actingAs($this->admin)->get(route('manage.record.offline.course', $training->id));
        $response->assertStatus(200);

        // Header shows Course & Sub Course Name prominently
        $response->assertSee('Advanced Python Architecture');
        $response->assertSee('AsyncIO and Microservices');
        $response->assertSee('In-House');
        $response->assertSee('Batch: Batch 3');
        $response->assertSee('Technical');

        // Detailed metadata grid is removed from header
        $response->assertDontSee('TRAINING METADATA GRID');
        $response->assertDontSee('Venue & Instructor');
        $response->assertDontSee('Dates & Duration');

        // KPI Summary cards are visible
        $response->assertSee('Total Participants');
        $response->assertSee('Finished');
        $response->assertSee('Ongoing');
        $response->assertSee('Avg. Progress');

        // Quick filter & Export dropdown are visible
        $response->assertSee('Quick Filter');
        $response->assertSee('Excel (.xlsx)');
        $response->assertSee('CSV (.csv)');
        $response->assertSee('PDF Landscape (.pdf)');
    }

    /**
     * 36. Course DataTable returns all canonical 44 columns and exposes course_name and sub_course_name on every row.
     */
    public function test_course_datatable_returns_all_canonical_44_columns(): void
    {
        $training = $this->createTraining([
            'course_name'     => 'Cyber Security Foundations',
            'sub_course_name' => 'Threat Modeling and Defense',
            'instructor'      => 'Dr. Satoshi Nakamoto',
            'venue'           => 'Server Lab 404',
        ]);
        $this->enrollParticipant($training, [
            'employee_id'      => 'CYBER_001',
            'name'             => 'Cyber Defender',
            'progress'         => 80.0,
            'assessment_score' => 92.5,
            'attendance'       => true,
            'l1_program'       => 'Excellent',
            'l2_numerical'     => 90.0,
            'l3_verbal'        => 'High Impact',
        ]);

        $response = $this->actingAs($this->admin)->getJson(
            route('manage.record.offline.course.datatable', $training->id) . '?draw=1'
        );

        $response->assertStatus(200);
        $data = $response->json('data');
        $this->assertCount(1, $data);
        $row = $data[0];

        // 1. Participant info
        $this->assertEquals('CYBER_001', $row['employee_id']);
        $this->assertStringContainsString('Cyber Defender', $row['participant_name']);

        // 2. Training info repeated on each row
        $this->assertStringContainsString('Cyber Security Foundations', $row['course_name']);
        $this->assertStringContainsString('Threat Modeling and Defense', $row['sub_course_name']);
        $this->assertEquals('Dr. Satoshi Nakamoto', $row['instructor']);
        $this->assertEquals('Server Lab 404', $row['venue']);

        // 3. Evaluations, scores & obligations
        $this->assertStringContainsString('92.5', $row['assessment_score']);
        $this->assertStringContainsString('Present', $row['attendance_badge']);
        $this->assertEquals('Excellent', $row['l1_program']);
        $this->assertStringContainsString('90.0', $row['l2_numerical']);
        $this->assertStringContainsString('High Impact', $row['l3_verbal']);

        // 4. Action button (view detail modal)
        $this->assertStringContainsString('btn-view-offline-detail', $row['action']);
    }

    /**
     * 37. Course DataTable filters by participant search (name or NIP).
     */
    public function test_course_datatable_filters_by_participant_search(): void
    {
        $training = $this->createTraining();
        $this->enrollParticipant($training, ['employee_id' => 'EMP_SEARCH_1', 'name' => 'Alice Wonder']);
        $this->enrollParticipant($training, ['employee_id' => 'EMP_SEARCH_2', 'name' => 'Bob Builder']);

        // Search by name
        $resName = $this->actingAs($this->admin)->getJson(
            route('manage.record.offline.course.datatable', $training->id) . '?participant=Alice'
        );
        $resName->assertStatus(200);
        $this->assertEquals(1, $resName->json('recordsFiltered'));
        $this->assertStringContainsString('Alice Wonder', $resName->json('data')[0]['participant_name']);

        // Search by NIP
        $resNip = $this->actingAs($this->admin)->getJson(
            route('manage.record.offline.course.datatable', $training->id) . '?participant=EMP_SEARCH_2'
        );
        $resNip->assertStatus(200);
        $this->assertEquals(1, $resNip->json('recordsFiltered'));
        $this->assertStringContainsString('Bob Builder', $resNip->json('data')[0]['participant_name']);
    }

    /**
     * 38. Course DataTable filters by status (finished, ongoing, not_started).
     */
    public function test_course_datatable_filters_by_status(): void
    {
        $training = $this->createTraining();
        $this->enrollParticipant($training, ['employee_id' => 'ST_FIN', 'progress' => 100.0]);
        $this->enrollParticipant($training, ['employee_id' => 'ST_ONG', 'progress' => 45.0]);
        $this->enrollParticipant($training, ['employee_id' => 'ST_NOT', 'progress' => 0.0]);

        // Filter finished
        $resFin = $this->actingAs($this->admin)->getJson(
            route('manage.record.offline.course.datatable', $training->id) . '?status=finished'
        );
        $resFin->assertStatus(200);
        $this->assertEquals(1, $resFin->json('recordsFiltered'));
        $this->assertEquals('ST_FIN', $resFin->json('data')[0]['employee_id']);

        // Filter ongoing
        $resOng = $this->actingAs($this->admin)->getJson(
            route('manage.record.offline.course.datatable', $training->id) . '?status=ongoing'
        );
        $resOng->assertStatus(200);
        $this->assertEquals(1, $resOng->json('recordsFiltered'));
        $this->assertEquals('ST_ONG', $resOng->json('data')[0]['employee_id']);

        // Filter not started
        $resNot = $this->actingAs($this->admin)->getJson(
            route('manage.record.offline.course.datatable', $training->id) . '?status=not_started'
        );
        $resNot->assertStatus(200);
        $this->assertEquals(1, $resNot->json('recordsFiltered'));
        $this->assertEquals('ST_NOT', $resNot->json('data')[0]['employee_id']);
    }

    /**
     * 39. Course DataTable combines search and status filters.
     */
    public function test_course_datatable_combines_search_and_status_filters(): void
    {
        $training = $this->createTraining();
        $this->enrollParticipant($training, ['employee_id' => 'COMB_1', 'name' => 'Charlie One', 'progress' => 100.0]);
        $this->enrollParticipant($training, ['employee_id' => 'COMB_2', 'name' => 'Charlie Two', 'progress' => 50.0]);

        // Match Charlie + finished -> 1
        $res1 = $this->actingAs($this->admin)->getJson(
            route('manage.record.offline.course.datatable', $training->id) . '?participant=Charlie&status=finished'
        );
        $res1->assertStatus(200);
        $this->assertEquals(1, $res1->json('recordsFiltered'));
        $this->assertEquals('COMB_1', $res1->json('data')[0]['employee_id']);

        // Match Charlie + not_started -> 0
        $res0 = $this->actingAs($this->admin)->getJson(
            route('manage.record.offline.course.datatable', $training->id) . '?participant=Charlie&status=not_started'
        );
        $res0->assertStatus(200);
        $this->assertEquals(0, $res0->json('recordsFiltered'));
    }

    /**
     * 40. Course Excel export returns .xlsx, requires permission, and respects active filters.
     */
    public function test_course_export_excel_returns_xlsx_and_respects_filters(): void
    {
        $training = $this->createTraining(['course_name' => 'Export Excel Test Course']);
        $this->enrollParticipant($training, ['employee_id' => 'EXCEL_1', 'progress' => 100.0]);
        $this->enrollParticipant($training, ['employee_id' => 'EXCEL_2', 'progress' => 20.0]);

        // Unfiltered download
        $response = $this->actingAs($this->admin)->get(
            route('manage.record.offline.course.export.excel', $training->id)
        );
        $response->assertStatus(200);
        $disposition = $response->headers->get('content-disposition') ?? '';
        $this->assertStringContainsString('.xlsx', $disposition);

        // Filtered download (status=finished)
        $responseFiltered = $this->actingAs($this->admin)->get(
            route('manage.record.offline.course.export.excel', $training->id) . '?status=finished'
        );
        $responseFiltered->assertStatus(200);
    }

    /**
     * 41. Course CSV export returns .csv and respects active filters.
     */
    public function test_course_export_csv_returns_csv_and_respects_filters(): void
    {
        $training = $this->createTraining(['course_name' => 'Export CSV Test Course']);
        $this->enrollParticipant($training, ['employee_id' => 'CSV_1', 'progress' => 100.0]);
        $this->enrollParticipant($training, ['employee_id' => 'CSV_2', 'progress' => 30.0]);

        $response = $this->actingAs($this->admin)->get(
            route('manage.record.offline.course.export.csv', $training->id) . '?status=finished'
        );
        $response->assertStatus(200);
        $this->assertStringContainsString('text/csv', $response->headers->get('content-type'));
    }

    /**
     * 42. Course PDF export returns .pdf and renders A3 landscape.
     */
    public function test_course_export_pdf_returns_pdf_and_respects_filters(): void
    {
        $training = $this->createTraining(['course_name' => 'Export PDF Test Course']);
        $this->enrollParticipant($training, ['employee_id' => 'PDF_1', 'progress' => 100.0]);
        $this->enrollParticipant($training, ['employee_id' => 'PDF_2', 'progress' => 40.0]);

        $response = $this->actingAs($this->admin)->get(
            route('manage.record.offline.course.export.pdf', $training->id) . '?status=finished'
        );
        $response->assertStatus(200);
        $this->assertStringContainsString('application/pdf', $response->headers->get('content-type'));
    }

    /**
     * 43. Course export endpoints require authentication and record_management permission.
     */
    public function test_course_export_endpoints_require_authentication_and_permission(): void
    {
        $training = $this->createTraining();

        // Unauthenticated -> redirect
        $this->get(route('manage.record.offline.course.export.excel', $training->id))->assertRedirect();
        $this->get(route('manage.record.offline.course.export.csv', $training->id))->assertRedirect();
        $this->get(route('manage.record.offline.course.export.pdf', $training->id))->assertRedirect();

        // Regular user without permission -> 403
        if ($this->regularUser) {
            $this->actingAs($this->regularUser)->get(route('manage.record.offline.course.export.excel', $training->id))->assertStatus(403);
            $this->actingAs($this->regularUser)->get(route('manage.record.offline.course.export.csv', $training->id))->assertStatus(403);
            $this->actingAs($this->regularUser)->get(route('manage.record.offline.course.export.pdf', $training->id))->assertStatus(403);
        }
    }

    /**
     * 44. Query service guarantees filter parity between DataTable, Excel, and CSV for Course Detail.
     */
    public function test_course_query_service_filter_parity_across_datatable_and_exports(): void
    {
        $training = $this->createTraining();
        $this->enrollParticipant($training, ['employee_id' => 'PAR_1', 'name' => 'Parity One', 'progress' => 100.0]);
        $this->enrollParticipant($training, ['employee_id' => 'PAR_2', 'name' => 'Parity Two', 'progress' => 100.0]);
        $this->enrollParticipant($training, ['employee_id' => 'PAR_3', 'name' => 'Parity Three', 'progress' => 50.0]);

        // Filter: status=finished -> exactly 2
        $filters = ['status' => 'finished'];
        $queryCount = RecordOfflineQueryService::buildCourseQuery((int) $training->id, $filters)->count();
        $this->assertEquals(2, $queryCount);

        // Datatable returns recordsFiltered = 2
        $dtRes = $this->actingAs($this->admin)->getJson(
            route('manage.record.offline.course.datatable', $training->id) . '?status=finished'
        );
        $dtRes->assertStatus(200);
        $this->assertEquals(2, $dtRes->json('recordsFiltered'));

        // Another training with same course name is NOT mixed into this course query
        $otherTraining = $this->createTraining(['course_name' => $training->course_name]);
        $this->enrollParticipant($otherTraining, ['employee_id' => 'PAR_OTHER', 'progress' => 100.0]);

        $queryCountAgain = RecordOfflineQueryService::buildCourseQuery((int) $training->id, $filters)->count();
        $this->assertEquals(2, $queryCountAgain, 'Participant from another training must not be counted.');
    }

    /**
     * 45. Course page renders full Quick Filter bar and More Filters modal.
     */
    public function test_course_detail_page_renders_quick_filters_and_more_filters_modal(): void
    {
        $training = $this->createTraining();
        $this->enrollParticipant($training, [
            'grade'       => 'G5',
            'team'        => 'Infra Team',
            'department'  => 'DevOps Dept',
            'company'     => 'PT Krakatau Steel',
        ]);

        $response = $this->actingAs($this->admin)->get(route('manage.record.offline.course', $training->id));
        $response->assertStatus(200);

        // Quick filter elements
        $response->assertSee('id="course-participant-input"', false);
        $response->assertSee('id="course-type-select"', false);
        $response->assertSee('id="course-status-select"', false);
        $response->assertSee('id="course-year-select"', false);
        $response->assertSee('id="btn-apply-course-filter"', false);
        $response->assertSee('id="btn-more-course-filters"', false);
        $response->assertSee('id="activeCourseFilterBadge"', false);
        $response->assertSee('id="btn-reset-course-filter"', false);
        $response->assertSee('id="courseExportDropdown"', false);

        // More filters modal elements
        $response->assertSee('id="moreCourseFiltersModal"', false);
        $response->assertSee('id="modal-course-grade"', false);
        $response->assertSee('id="modal-course-job-position"', false);
        $response->assertSee('id="modal-course-team"', false);
        $response->assertSee('id="modal-course-department"', false);
        $response->assertSee('id="modal-course-company"', false);
        $response->assertSee('name="modal_course_progress_range"', false);
        $response->assertSee('id="modal-course-hours-min"', false);
        $response->assertSee('id="modal-course-score-min"', false);
        $response->assertSee('name="modal_course_certificate"', false);
        $response->assertSee('name="modal_course_attendance"', false);
        $response->assertSee('id="modal-course-attendance-reason"', false);
        $response->assertSee('name="modal_course_bond_status"', false);
        $response->assertSee('id="modal-course-bond-from"', false);
        $response->assertSee('id="modal-course-bond-amount-min"', false);
        $response->assertSee('id="modal-course-l1"', false);
        $response->assertSee('id="modal-course-l2-num-min"', false);
        $response->assertSee('id="modal-course-l2-verbal"', false);
        $response->assertSee('id="modal-course-l3-num-min"', false);
        $response->assertSee('id="modal-course-l3-verbal"', false);
        $response->assertSee('id="modal-course-pic"', false);
        $response->assertSee('id="btn-reset-modal-course-filters"', false);
        $response->assertSee('id="btn-apply-modal-course-filters"', false);

        // Options populated from participants
        $response->assertSee('G5');
        $response->assertSee('Infra Team');
        $response->assertSee('DevOps Dept');
        $response->assertSee('PT Krakatau Steel');
    }

    /**
     * 46. Course DataTable filters by Type and Year.
     */
    public function test_course_datatable_filters_by_type_and_year(): void
    {
        $training = $this->createTraining([
            'type'        => 'In-House',
            'start_date'  => '2026-03-01',
            'finish_date' => '2026-03-02',
        ]);
        $this->enrollParticipant($training, ['employee_id' => 'TY_1', 'name' => 'Type Test User']);

        // Type matches
        $resTypeOk = $this->actingAs($this->admin)->getJson(
            route('manage.record.offline.course.datatable', $training->id) . '?type=In-House'
        );
        $resTypeOk->assertStatus(200);
        $this->assertEquals(1, $resTypeOk->json('recordsFiltered'));

        // Type mismatch
        $resTypeMismatch = $this->actingAs($this->admin)->getJson(
            route('manage.record.offline.course.datatable', $training->id) . '?type=Public'
        );
        $resTypeMismatch->assertStatus(200);
        $this->assertEquals(0, $resTypeMismatch->json('recordsFiltered'));

        // Year matches
        $resYearOk = $this->actingAs($this->admin)->getJson(
            route('manage.record.offline.course.datatable', $training->id) . '?year=2026'
        );
        $resYearOk->assertStatus(200);
        $this->assertEquals(1, $resYearOk->json('recordsFiltered'));

        // Year mismatch
        $resYearMismatch = $this->actingAs($this->admin)->getJson(
            route('manage.record.offline.course.datatable', $training->id) . '?year=2020'
        );
        $resYearMismatch->assertStatus(200);
        $this->assertEquals(0, $resYearMismatch->json('recordsFiltered'));
    }

    /**
     * 47. Course DataTable filters by Employee Information: Grade, Job Position, Team, Department, Company.
     */
    public function test_course_datatable_filters_by_employee_information(): void
    {
        $training = $this->createTraining();
        $this->enrollParticipant($training, [
            'employee_id'  => 'EMP_INFO_1',
            'name'         => 'Alpha Developer',
            'grade'        => 'G4',
            'job_position' => 'Senior Backend Engineer',
            'team'         => 'Core Backend',
            'department'   => 'Software Engineering',
            'company'      => 'PT Krakatau Posco',
        ]);
        $this->enrollParticipant($training, [
            'employee_id'  => 'EMP_INFO_2',
            'name'         => 'Beta Analyst',
            'grade'        => 'G3',
            'job_position' => 'Quality Assurance',
            'team'         => 'Testing Team',
            'department'   => 'Quality Dept',
            'company'      => 'PT Krakatau Steel',
        ]);

        // Filter Grade
        $resGrade = $this->actingAs($this->admin)->getJson(
            route('manage.record.offline.course.datatable', $training->id) . '?grade=G4'
        );
        $this->assertEquals(1, $resGrade->json('recordsFiltered'));
        $this->assertStringContainsString('Alpha Developer', $resGrade->json('data')[0]['participant_name']);

        // Filter Job Position (partial search)
        $resPos = $this->actingAs($this->admin)->getJson(
            route('manage.record.offline.course.datatable', $training->id) . '?job_position=Backend'
        );
        $this->assertEquals(1, $resPos->json('recordsFiltered'));
        $this->assertStringContainsString('Alpha Developer', $resPos->json('data')[0]['participant_name']);

        // Filter Team
        $resTeam = $this->actingAs($this->admin)->getJson(
            route('manage.record.offline.course.datatable', $training->id) . '?team=Testing Team'
        );
        $this->assertEquals(1, $resTeam->json('recordsFiltered'));
        $this->assertStringContainsString('Beta Analyst', $resTeam->json('data')[0]['participant_name']);

        // Filter Department
        $resDept = $this->actingAs($this->admin)->getJson(
            route('manage.record.offline.course.datatable', $training->id) . '?department=Quality Dept'
        );
        $this->assertEquals(1, $resDept->json('recordsFiltered'));
        $this->assertStringContainsString('Beta Analyst', $resDept->json('data')[0]['participant_name']);

        // Filter Company
        $resComp = $this->actingAs($this->admin)->getJson(
            route('manage.record.offline.course.datatable', $training->id) . '?company=PT Krakatau Posco'
        );
        $this->assertEquals(1, $resComp->json('recordsFiltered'));
        $this->assertStringContainsString('Alpha Developer', $resComp->json('data')[0]['participant_name']);
    }

    /**
     * 48. Course DataTable filters by Training Result: Progress Range, Hours, Score, Certificate.
     */
    public function test_course_datatable_filters_by_training_result(): void
    {
        $training = $this->createTraining();
        $this->enrollParticipant($training, [
            'employee_id'      => 'RES_1',
            'name'             => 'Winner One',
            'progress'         => 100.0,
            'learning_hours'   => 8.0,
            'assessment_score' => 95.0,
            'certificate'      => 'CERT-WIN',
        ]);
        $this->enrollParticipant($training, [
            'employee_id'      => 'RES_2',
            'name'             => 'Ongoing Two',
            'progress'         => 45.0,
            'learning_hours'   => 4.0,
            'assessment_score' => 65.0,
            'certificate'      => null,
        ]);

        // Progress Range: 26-50
        $resPr = $this->actingAs($this->admin)->getJson(
            route('manage.record.offline.course.datatable', $training->id) . '?progress_range=26-50'
        );
        $this->assertEquals(1, $resPr->json('recordsFiltered'));
        $this->assertStringContainsString('Ongoing Two', $resPr->json('data')[0]['participant_name']);

        // Learning Hours Min: 6
        $resHours = $this->actingAs($this->admin)->getJson(
            route('manage.record.offline.course.datatable', $training->id) . '?hours_min=6'
        );
        $this->assertEquals(1, $resHours->json('recordsFiltered'));
        $this->assertStringContainsString('Winner One', $resHours->json('data')[0]['participant_name']);

        // Assessment Score Min: 80
        $resScore = $this->actingAs($this->admin)->getJson(
            route('manage.record.offline.course.datatable', $training->id) . '?score_min=80'
        );
        $this->assertEquals(1, $resScore->json('recordsFiltered'));
        $this->assertStringContainsString('Winner One', $resScore->json('data')[0]['participant_name']);

        // Certificate: yes
        $resCert = $this->actingAs($this->admin)->getJson(
            route('manage.record.offline.course.datatable', $training->id) . '?certificate=yes'
        );
        $this->assertEquals(1, $resCert->json('recordsFiltered'));
        $this->assertStringContainsString('Winner One', $resCert->json('data')[0]['participant_name']);
    }

    /**
     * 49. Course DataTable filters by Attendance and Attendance Reason.
     */
    public function test_course_datatable_filters_by_attendance_and_reason(): void
    {
        $training = $this->createTraining();
        $this->enrollParticipant($training, [
            'employee_id'       => 'ATT_1',
            'name'              => 'Present Person',
            'attendance'        => true,
            'attendance_reason' => null,
        ]);
        $this->enrollParticipant($training, [
            'employee_id'       => 'ATT_2',
            'name'              => 'Sick Person',
            'attendance'        => false,
            'attendance_reason' => 'Medical Sick Leave',
        ]);

        // Attendance: present
        $resPres = $this->actingAs($this->admin)->getJson(
            route('manage.record.offline.course.datatable', $training->id) . '?attendance=present'
        );
        $this->assertEquals(1, $resPres->json('recordsFiltered'));
        $this->assertStringContainsString('Present Person', $resPres->json('data')[0]['participant_name']);

        // Attendance: absent
        $resAbs = $this->actingAs($this->admin)->getJson(
            route('manage.record.offline.course.datatable', $training->id) . '?attendance=absent'
        );
        $this->assertEquals(1, $resAbs->json('recordsFiltered'));
        $this->assertStringContainsString('Sick Person', $resAbs->json('data')[0]['participant_name']);

        // Attendance Reason
        $resReason = $this->actingAs($this->admin)->getJson(
            route('manage.record.offline.course.datatable', $training->id) . '?attendance_reason=Medical'
        );
        $this->assertEquals(1, $resReason->json('recordsFiltered'));
        $this->assertStringContainsString('Sick Person', $resReason->json('data')[0]['participant_name']);
    }

    /**
     * 50. Course DataTable filters by Obligation: Bond Status, End Date, Amount.
     */
    public function test_course_datatable_filters_by_obligation(): void
    {
        $training = $this->createTraining();
        $this->enrollParticipant($training, [
            'employee_id'   => 'BOND_1',
            'name'          => 'Bonded Employee',
            'bond_status'   => 'Yes',
            'bond_end_date' => '2027-12-31',
            'bond_amount'   => 15000000,
        ]);
        $this->enrollParticipant($training, [
            'employee_id'   => 'BOND_2',
            'name'          => 'Free Employee',
            'bond_status'   => 'No',
            'bond_end_date' => null,
            'bond_amount'   => null,
        ]);

        // Bond Status: yes
        $resBond = $this->actingAs($this->admin)->getJson(
            route('manage.record.offline.course.datatable', $training->id) . '?bond_status=yes'
        );
        $this->assertEquals(1, $resBond->json('recordsFiltered'));
        $this->assertStringContainsString('Bonded Employee', $resBond->json('data')[0]['participant_name']);

        // Bond Date Range
        $resDate = $this->actingAs($this->admin)->getJson(
            route('manage.record.offline.course.datatable', $training->id) . '?bond_end_from=2027-01-01&bond_end_to=2027-12-31'
        );
        $this->assertEquals(1, $resDate->json('recordsFiltered'));
        $this->assertStringContainsString('Bonded Employee', $resDate->json('data')[0]['participant_name']);

        // Bond Amount Min
        $resAmt = $this->actingAs($this->admin)->getJson(
            route('manage.record.offline.course.datatable', $training->id) . '?bond_amount_min=10000000'
        );
        $this->assertEquals(1, $resAmt->json('recordsFiltered'));
        $this->assertStringContainsString('Bonded Employee', $resAmt->json('data')[0]['participant_name']);
    }

    /**
     * 51. Course DataTable filters by Evaluation: L1, L2, L3, PIC.
     */
    public function test_course_datatable_filters_by_evaluation(): void
    {
        $training = $this->createTraining();
        $this->enrollParticipant($training, [
            'employee_id'  => 'EVAL_1',
            'name'         => 'Evaluated Star',
            'l1_program'   => 'Outstanding',
            'l2_numerical' => 92.0,
            'l2_verbal'    => 'Exceeded Expectations',
            'l3_numerical' => 88.0,
            'l3_verbal'    => 'Great Project',
            'pic'          => 'John HRD PIC',
        ]);
        $this->enrollParticipant($training, [
            'employee_id'  => 'EVAL_2',
            'name'         => 'Ordinary Participant',
            'l1_program'   => 'Good',
            'l2_numerical' => 60.0,
            'l2_verbal'    => 'Satisfactory',
            'l3_numerical' => 55.0,
            'l3_verbal'    => 'Average',
            'pic'          => 'Jane Other PIC',
        ]);

        // L1 Program
        $resL1 = $this->actingAs($this->admin)->getJson(
            route('manage.record.offline.course.datatable', $training->id) . '?l1_program=Outstanding'
        );
        $this->assertEquals(1, $resL1->json('recordsFiltered'));
        $this->assertStringContainsString('Evaluated Star', $resL1->json('data')[0]['participant_name']);

        // L2 Numerical Min
        $resL2 = $this->actingAs($this->admin)->getJson(
            route('manage.record.offline.course.datatable', $training->id) . '?l2_numerical_min=80'
        );
        $this->assertEquals(1, $resL2->json('recordsFiltered'));
        $this->assertStringContainsString('Evaluated Star', $resL2->json('data')[0]['participant_name']);

        // L3 Verbal
        $resL3v = $this->actingAs($this->admin)->getJson(
            route('manage.record.offline.course.datatable', $training->id) . '?l3_verbal=Great'
        );
        $this->assertEquals(1, $resL3v->json('recordsFiltered'));
        $this->assertStringContainsString('Evaluated Star', $resL3v->json('data')[0]['participant_name']);

        // PIC
        $resPic = $this->actingAs($this->admin)->getJson(
            route('manage.record.offline.course.datatable', $training->id) . '?pic=John'
        );
        $this->assertEquals(1, $resPic->json('recordsFiltered'));
        $this->assertStringContainsString('Evaluated Star', $resPic->json('data')[0]['participant_name']);
    }

    /**
     * 52. Course DataTable handles combined advanced filters correctly.
     */
    public function test_course_datatable_handles_combined_advanced_filters(): void
    {
        $training = $this->createTraining();
        $this->enrollParticipant($training, [
            'employee_id'      => 'COMB_1',
            'name'             => 'Perfect Match',
            'department'       => 'Technology',
            'progress'         => 100.0,
            'attendance'       => true,
            'assessment_score' => 88.0,
        ]);
        $this->enrollParticipant($training, [
            'employee_id'      => 'COMB_2',
            'name'             => 'Diff Dept',
            'department'       => 'Finance',
            'progress'         => 100.0,
            'attendance'       => true,
            'assessment_score' => 90.0,
        ]);
        $this->enrollParticipant($training, [
            'employee_id'      => 'COMB_3',
            'name'             => 'Low Score',
            'department'       => 'Technology',
            'progress'         => 100.0,
            'attendance'       => true,
            'assessment_score' => 70.0,
        ]);

        $query = http_build_query([
            'department' => 'Technology',
            'status'     => 'finished',
            'attendance' => 'present',
            'score_min'  => 80,
        ]);

        $res = $this->actingAs($this->admin)->getJson(
            route('manage.record.offline.course.datatable', $training->id) . '?' . $query
        );
        $this->assertEquals(1, $res->json('recordsFiltered'));
        $this->assertStringContainsString('Perfect Match', $res->json('data')[0]['participant_name']);
    }

    /**
     * 53. Course exports (Excel, CSV, PDF) respect More Filters identically.
     */
    public function test_course_exports_respect_more_filters(): void
    {
        $training = $this->createTraining();
        $this->enrollParticipant($training, [
            'employee_id'      => 'EXP_MORE_1',
            'department'       => 'Engineering',
            'assessment_score' => 90.0,
        ]);
        $this->enrollParticipant($training, [
            'employee_id'      => 'EXP_MORE_2',
            'department'       => 'HR',
            'assessment_score' => 70.0,
        ]);

        $query = http_build_query([
            'department' => 'Engineering',
            'score_min'  => 85,
        ]);

        // Excel
        $resExcel = $this->actingAs($this->admin)->get(
            route('manage.record.offline.course.export.excel', $training->id) . '?' . $query
        );
        $resExcel->assertStatus(200);

        // CSV
        $resCsv = $this->actingAs($this->admin)->get(
            route('manage.record.offline.course.export.csv', $training->id) . '?' . $query
        );
        $resCsv->assertStatus(200);

        // PDF
        $resPdf = $this->actingAs($this->admin)->get(
            route('manage.record.offline.course.export.pdf', $training->id) . '?' . $query
        );
        $resPdf->assertStatus(200);

        // Underlying query count is exactly 1
        $count = RecordOfflineQueryService::buildCourseQuery((int) $training->id, [
            'department' => 'Engineering',
            'score_min'  => 85,
        ])->count();
        $this->assertEquals(1, $count);
    }

    /**
     * 54. Course filters exclude soft-deleted participants and soft-deleted training events.
     */
    public function test_course_filters_exclude_soft_deleted_records(): void
    {
        $training = $this->createTraining();
        $pActive = $this->enrollParticipant($training, ['employee_id' => 'ACTIVE_P', 'name' => 'Active Participant']);
        $pDeleted = $this->enrollParticipant($training, ['employee_id' => 'DELETED_P', 'name' => 'Deleted Participant']);

        // Soft-delete participant
        DB::table('offline_training_users')->where('id', $pDeleted->id)->update(['deleted_at' => now()]);

        $res = $this->actingAs($this->admin)->getJson(
            route('manage.record.offline.course.datatable', $training->id)
        );
        $res->assertStatus(200);
        $this->assertEquals(1, $res->json('recordsFiltered'));
        $this->assertStringContainsString('Active Participant', $res->json('data')[0]['participant_name']);
    }
}

