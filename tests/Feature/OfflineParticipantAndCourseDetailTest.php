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
        $response->assertSee('Auditorium 1');
        $response->assertSee('Dr. Aris W.');
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
}

