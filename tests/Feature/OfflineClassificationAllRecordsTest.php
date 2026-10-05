<?php

namespace Tests\Feature;

use App\Exports\OfflineClassificationExport;
use App\Http\Controllers\Manage\Record\RecordOfflineController;
use App\Models\User;
use App\Services\Record\RecordOfflineQueryService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class OfflineClassificationAllRecordsTest extends TestCase
{
    protected User $admin;
    protected array $createdTrainingIds = [];

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::where('role', '2')->first() ?? User::first();
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
     * Seed comprehensive multi-training, multi-participant, mixed-status, mixed-year test data.
     */
    protected function seedAllRecordsTestData(): array
    {
        // Training 1: Canonical "Basic & Common Duties", 2026, Month 1 (Jan)
        $t1Id = DB::table('offline_trainings')->insertGetId([
            'course_name'            => 'Basic Safety Culture',
            'sub_course_name'        => '5S Foundation',
            'type'                   => 'In-House',
            'category'               => 'Compliance',
            'level'                  => 'Basic',
            'batch'                  => 'Batch 1',
            'main_classification'    => 'Basic & Common Duties',
            'sub_classification'     => 'Workplace Safety',
            'counted_corporate_kpi'  => true,
            'duration_seconds'       => 3600,
            'learning_hours'         => 4.0,
            'start_date'             => '2026-01-15',
            'finish_date'            => '2026-01-16',
            'created_at'             => now(),
            'updated_at'             => now(),
        ]);
        $this->createdTrainingIds[] = $t1Id;

        // Participant 1A: Existing LMS User, Finished (100%), Present
        $p1AId = DB::table('offline_training_users')->insertGetId([
            'offline_training_id' => $t1Id,
            'user_id'             => $this->admin->id,
            'employee_id'         => 'TESTEMP_A1',
            'name'                => 'LMS Registered Worker',
            'learning_hours'      => 4.0,
            'progress'            => 100.0,
            'attendance'          => true,
            'assessment_score'    => 90.0,
            'created_at'          => now(),
            'updated_at'          => now(),
        ]);

        // Participant 1B: Manual Participant (user_id IS NULL), Ongoing (60%), Present
        $p1BId = DB::table('offline_training_users')->insertGetId([
            'offline_training_id' => $t1Id,
            'user_id'             => null,
            'employee_id'         => 'TESTEMP_A2_MANUAL',
            'name'                => 'Manual Entry Contractor',
            'learning_hours'      => 2.4,
            'progress'            => 60.0,
            'attendance'          => true,
            'assessment_score'    => 75.0,
            'created_at'          => now(),
            'updated_at'          => now(),
        ]);

        // Training 2: Variant "Basic Common Duties" (without ampersand), 2022 (Old year), Month 7 (July)
        $t2Id = DB::table('offline_trainings')->insertGetId([
            'course_name'            => 'Historical Anti-Corruption Induction',
            'sub_course_name'        => 'Integrity Standard',
            'type'                   => 'Public',
            'category'               => 'Ethics',
            'level'                  => 'General',
            'batch'                  => 'Batch 2022',
            'main_classification'    => 'Basic Common Duties',
            'sub_classification'     => 'Corporate Governance',
            'counted_corporate_kpi'  => false,
            'duration_seconds'       => 1800,
            'learning_hours'         => 2.0,
            'start_date'             => '2022-07-10',
            'finish_date'            => '2022-07-10',
            'created_at'             => now(),
            'updated_at'             => now(),
        ]);
        $this->createdTrainingIds[] = $t2Id;

        // Participant 2A: Manual Participant, Not Started (0%), Absent
        $p2AId = DB::table('offline_training_users')->insertGetId([
            'offline_training_id' => $t2Id,
            'user_id'             => null,
            'employee_id'         => 'TESTEMP_A3_MANUAL',
            'name'                => 'Dormant Participant',
            'learning_hours'      => 0.0,
            'progress'            => 0.0,
            'attendance'          => false,
            'attendance_reason'   => 'Sick Leave',
            'assessment_score'    => null,
            'created_at'          => now(),
            'updated_at'          => now(),
        ]);

        // Training 3: Another Classification (e.g. Audit Isolated Classification) to test strict isolation
        $t3Id = DB::table('offline_trainings')->insertGetId([
            'course_name'            => 'Advanced Plant Metallurgy',
            'sub_course_name'        => 'Steel Production',
            'type'                   => 'Classroom',
            'category'               => 'Engineering',
            'level'                  => 'Expert',
            'batch'                  => 'Batch 9',
            'main_classification'    => 'Audit Isolated Classification',
            'sub_classification'     => 'Metallurgy',
            'counted_corporate_kpi'  => true,
            'duration_seconds'       => 7200,
            'learning_hours'         => 8.0,
            'start_date'             => '2026-02-01',
            'finish_date'            => '2026-02-05',
            'created_at'             => now(),
            'updated_at'             => now(),
        ]);
        $this->createdTrainingIds[] = $t3Id;

        // Participant 3A: Under Isolated Classification
        $p3AId = DB::table('offline_training_users')->insertGetId([
            'offline_training_id' => $t3Id,
            'user_id'             => null,
            'employee_id'         => 'TESTEMP_B1_ISOLATED',
            'name'                => 'Metallurgist Engineer',
            'learning_hours'      => 8.0,
            'progress'            => 100.0,
            'attendance'          => true,
            'assessment_score'    => 98.0,
            'created_at'          => now(),
            'updated_at'          => now(),
        ]);

        return [
            'training_1' => $t1Id,
            'training_2' => $t2Id,
            'training_3' => $t3Id,
            'participant_1a' => $p1AId,
            'participant_1b' => $p1BId,
            'participant_2a' => $p2AId,
            'participant_3a' => $p3AId,
        ];
    }

    /**
     * Test 1: All participants across different trainings of the classification appear (grain = participant-level).
     */
    public function test_all_participants_belonging_to_classification_appear_regardless_of_name_variant()
    {
        $seeded = $this->seedAllRecordsTestData();

        $matchingNames = RecordOfflineQueryService::getMatchingClassifications('basic-common-duties');
        $this->assertContains('Basic & Common Duties', $matchingNames);
        $this->assertContains('Basic Common Duties', $matchingNames);

        $query = RecordOfflineQueryService::buildClassificationQuery('basic-common-duties');
        $results = $query->get();

        $this->assertTrue($results->contains('id', $seeded['participant_1a']));
        $this->assertTrue($results->contains('id', $seeded['participant_1b']));
        $this->assertTrue($results->contains('id', $seeded['participant_2a']));

        // Isolated classification must NOT be included
        $this->assertFalse($results->contains('id', $seeded['participant_3a']));
    }

    /**
     * Test 2: Manual participants with user_id NULL are fully preserved and displayed.
     */
    public function test_manual_participants_with_null_user_id_appear_with_full_details()
    {
        $seeded = $this->seedAllRecordsTestData();

        $response = $this->actingAs($this->admin)->getJson(
            route('manage.record.offline.classification.datatable', 'basic-common-duties') . '?draw=1'
        );
        $response->assertStatus(200);

        $data = collect($response->json('data'));
        $manualRow = $data->firstWhere('employee_id', 'TESTEMP_A2_MANUAL');

        $this->assertNotNull($manualRow, 'Manual participant with user_id = NULL must appear in DataTable.');
        $this->assertStringContainsString('Manual Entry Contractor', $manualRow['employee']);
    }

    /**
     * Test 3: FINISHED, ONGOING, and NOT STARTED records all appear by default without status filter.
     */
    public function test_all_progress_statuses_appear_by_default_without_status_filter()
    {
        $seeded = $this->seedAllRecordsTestData();

        $query = RecordOfflineQueryService::buildClassificationQuery('basic-common-duties');
        $records = $query->get();

        $finished = $records->firstWhere('id', $seeded['participant_1a']);
        $ongoing  = $records->firstWhere('id', $seeded['participant_1b']);
        $notStarted = $records->firstWhere('id', $seeded['participant_2a']);

        $this->assertNotNull($finished, 'Finished participant must appear.');
        $this->assertNotNull($ongoing, 'Ongoing participant must appear.');
        $this->assertNotNull($notStarted, 'Not Started participant must appear.');
    }

    /**
     * Test 4: Historical year records appear by default without year filter, and year filter works when selected.
     */
    public function test_all_years_appear_by_default_and_year_filter_restricts_correctly()
    {
        $seeded = $this->seedAllRecordsTestData();

        // Without year filter: 2026 and 2022 both appear
        $queryNoFilter = RecordOfflineQueryService::buildClassificationQuery('basic-common-duties');
        $allRecords = $queryNoFilter->get();
        $this->assertTrue($allRecords->contains('id', $seeded['participant_1a'])); // 2026
        $this->assertTrue($allRecords->contains('id', $seeded['participant_2a'])); // 2022

        // With year=2022 filter: only participant_2a appears
        $query2022 = RecordOfflineQueryService::buildClassificationQuery('basic-common-duties', ['year' => '2022']);
        $records2022 = $query2022->get();
        $this->assertTrue($records2022->contains('id', $seeded['participant_2a']));
        $this->assertFalse($records2022->contains('id', $seeded['participant_1a']));

        // With year=2026 filter: only 2026 participants appear
        $query2026 = RecordOfflineQueryService::buildClassificationQuery('basic-common-duties', ['year' => '2026']);
        $records2026 = $query2026->get();
        $this->assertTrue($records2026->contains('id', $seeded['participant_1a']));
        $this->assertFalse($records2026->contains('id', $seeded['participant_2a']));
    }

    /**
     * Test 5: Month filter works when provided and does not restrict when omitted.
     */
    public function test_month_filter_works_when_provided_and_omits_none_when_all()
    {
        $seeded = $this->seedAllRecordsTestData();

        // Month 7 (July) -> participant_2a
        $queryJuly = RecordOfflineQueryService::buildClassificationQuery('basic-common-duties', ['month' => '7']);
        $recordsJuly = $queryJuly->get();
        $this->assertTrue($recordsJuly->contains('id', $seeded['participant_2a']));
        $this->assertFalse($recordsJuly->contains('id', $seeded['participant_1a']));

        // Month 1 (January) -> participant_1a & 1b
        $queryJan = RecordOfflineQueryService::buildClassificationQuery('basic-common-duties', ['month' => '1']);
        $recordsJan = $queryJan->get();
        $this->assertTrue($recordsJan->contains('id', $seeded['participant_1a']));
        $this->assertFalse($recordsJan->contains('id', $seeded['participant_2a']));
    }

    /**
     * Test 6: Search works inside classification for both NIP and course title.
     */
    public function test_search_filters_properly_inside_classification()
    {
        $seeded = $this->seedAllRecordsTestData();

        // Search by manual employee ID
        $querySearch = RecordOfflineQueryService::buildClassificationQuery('basic-common-duties', ['employee_id' => 'TESTEMP_A2_MANUAL']);
        $results = $querySearch->get();
        $this->assertCount(1, $results);
        $this->assertEquals($seeded['participant_1b'], $results->first()->id);

        // Search by course name
        $queryCourse = RecordOfflineQueryService::buildClassificationQuery('basic-common-duties', ['course_name' => 'Historical Anti-Corruption']);
        $resultsCourse = $queryCourse->get();
        $this->assertCount(1, $resultsCourse);
        $this->assertEquals($seeded['participant_2a'], $resultsCourse->first()->id);
    }

    /**
     * Test 7: Classification isolation strictly excludes other classifications.
     */
    public function test_classification_isolation_strictly_excludes_unrelated_data()
    {
        $seeded = $this->seedAllRecordsTestData();

        $query = RecordOfflineQueryService::buildClassificationQuery('audit-isolated-classification');
        $results = $query->get();

        $this->assertTrue($results->contains('id', $seeded['participant_3a']));
        $this->assertFalse($results->contains('id', $seeded['participant_1a']));
        $this->assertFalse($results->contains('id', $seeded['participant_1b']));
        $this->assertFalse($results->contains('id', $seeded['participant_2a']));
    }

    /**
     * Test 8: Empty classification returns valid empty state without errors.
     */
    public function test_empty_classification_returns_empty_state_without_errors()
    {
        $response = $this->actingAs($this->admin)->getJson(
            route('manage.record.offline.classification.datatable', 'non-existent-classification-xyz') . '?draw=1'
        );

        $response->assertStatus(200);
        $json = $response->json();
        $this->assertEquals(0, $json['recordsTotal']);
        $this->assertEquals(0, $json['recordsFiltered']);
        $this->assertEmpty($json['data']);
    }

    /**
     * Test 9: Classification Summary consolidation aggregates variant names into single canonical row.
     */
    public function test_classification_summary_consolidates_variant_names_into_canonical_entry()
    {
        $seeded = $this->seedAllRecordsTestData();

        $summary = app(RecordOfflineController::class)->getClassificationSummary();
        $basicSummary = $summary->firstWhere('slug', 'basic-common-duties');

        $this->assertNotNull($basicSummary, 'Basic & Common Duties summary must exist.');
        $this->assertEquals('Basic & Common Duties', $basicSummary->classification_name);
        // There should be only ONE row with slug 'basic-common-duties'
        $slugMatches = $summary->where('slug', 'basic-common-duties')->count();
        $this->assertEquals(1, $slugMatches, 'Classification summary must have exactly one consolidated row per slug.');
    }

    /**
     * Test 10: Export shares identical dataset with DataTable for the classification.
     */
    public function test_export_has_data_parity_with_datatable_for_classification()
    {
        $seeded = $this->seedAllRecordsTestData();

        $dtQuery = RecordOfflineQueryService::buildClassificationQuery('basic-common-duties');
        $dtIds = $dtQuery->pluck('id')->sort()->values()->all();

        $export = new OfflineClassificationExport('basic-common-duties');
        $exportQuery = $export->query();
        $exportIds = $exportQuery->pluck('otu.id')->sort()->values()->all();

        $this->assertEquals($dtIds, $exportIds, 'Export query and DataTable query must return identical record IDs.');
    }

    /**
     * Test 11: Training without participants is recognized in KPI total_trainings and summary programs.
     */
    public function test_trainings_without_participants_are_recognized_in_summary_and_kpi()
    {
        $emptyTrainingId = DB::table('offline_trainings')->insertGetId([
            'course_name'            => 'Scheduled Empty Training 2026',
            'type'                   => 'In-House',
            'main_classification'    => 'Empty Class Track',
            'created_at'             => now(),
            'updated_at'             => now(),
        ]);
        $this->createdTrainingIds[] = $emptyTrainingId;

        // KPI Summary must include this training in total_trainings
        $kpiRes = $this->actingAs($this->admin)->getJson(route('manage.record.offline.ajax.summary'));
        $kpiRes->assertStatus(200);

        // Classification Summary must recognize it with programs = 1 and participants = 0
        $summary = app(RecordOfflineController::class)->getClassificationSummary();
        $row = $summary->firstWhere('slug', 'empty-class-track');
        $this->assertNotNull($row, 'Empty class track training must be recognized in summary.');
        $this->assertEquals(1, $row->programs, 'Training without participants must be counted as 1 program.');
        $this->assertEquals(0, $row->total_participants, 'Total participants for empty training must be 0.');
    }

    /**
     * Test 12: Real database full 1:1 reconciliation (Missing Programs = 0, Missing Participants = 0).
     */
    public function test_real_database_full_reconciliation_zero_difference()
    {
        $dbTrainingsCount = DB::table('offline_trainings')->whereNull('deleted_at')->count();
        $dbParticipantsCount = DB::table('offline_training_users')->whereNull('deleted_at')->count();

        $summary = app(RecordOfflineController::class)->getClassificationSummary();
        $sumPrograms = 0;
        $sumParticipants = 0;
        $seenParticipantIds = [];
        $seenTrainingIds = [];

        foreach ($summary as $row) {
            $sumPrograms += $row->programs;
            $sumParticipants += $row->total_participants;

            $query = RecordOfflineQueryService::buildClassificationQuery($row->slug);
            $partIds = $query->pluck('id')->all();
            $trnIds = $query->distinct()->pluck('offline_training_id')->all();

            foreach ($partIds as $pid) {
                $seenParticipantIds[$pid] = ($seenParticipantIds[$pid] ?? 0) + 1;
            }
            foreach ($trnIds as $tid) {
                $seenTrainingIds[$tid] = ($seenTrainingIds[$tid] ?? 0) + 1;
            }
        }

        $allDbPartIds = DB::table('offline_training_users')->whereNull('deleted_at')->pluck('id')->all();
        $missingPartIds = array_diff($allDbPartIds, array_keys($seenParticipantIds));

        $allDbTrnIds = DB::table('offline_trainings')->whereNull('deleted_at')->pluck('id')->all();
        $missingTrnIds = array_diff($allDbTrnIds, array_keys($seenTrainingIds));

        $this->assertEquals($dbTrainingsCount, $sumPrograms, 'Sum of programs across classifications must equal DB trainings count.');
        $this->assertEquals($dbParticipantsCount, $sumParticipants, 'Sum of participants across classifications must equal DB participants count.');
        $this->assertEmpty($missingPartIds, 'There must be zero missing participant IDs.');
        $this->assertEmpty($missingTrnIds, 'There must be zero missing training IDs.');
    }

    /**
     * Test 13: Detail endpoint returns 100% of business and technical fields from both tables without collision.
     */
    public function test_detail_endpoint_returns_all_fields_from_both_tables()
    {
        $firstParticipant = DB::table('offline_training_users')->whereNull('deleted_at')->first();
        $this->assertNotNull($firstParticipant, 'At least one participant must exist for detail test.');

        $response = $this->actingAs($this->admin)->getJson("/manage/record/offline/{$firstParticipant->id}/detail");
        $response->assertStatus(200);
        $response->assertJson(['success' => true]);

        $data = $response->json('data');

        // 1. All 21 business fields from offline_trainings
        $trainingFields = [
            'course_name', 'sub_course_name', 'type', 'category', 'level', 'batch',
            'main_classification', 'sub_classification', 'counted_corporate_kpi', 'sub_corporate_kpi',
            'delivery_method', 'sub_delivery_method', 'provider', 'sub_provider', 'provider_name',
            'venue', 'instructor', 'duration_seconds', 'course_learning_hours', 'start_date', 'finish_date'
        ];
        foreach ($trainingFields as $field) {
            $this->assertArrayHasKey($field, $data, "Field '{$field}' from offline_trainings must be present in detail response.");
        }

        // 2. All 22 business fields from offline_training_users
        $participantFields = [
            'employee_id', 'participant_name', 'grade', 'job_position', 'team', 'department',
            'company', 'participant_learning_hours', 'progress', 'assessment_score', 'certificate',
            'attendance', 'attendance_reason', 'bond_status', 'bond_end_date', 'bond_amount',
            'l1_program', 'l2_numerical', 'l2_verbal', 'l3_numerical', 'l3_verbal', 'pic'
        ];
        foreach ($participantFields as $field) {
            $this->assertArrayHasKey($field, $data, "Field '{$field}' from offline_training_users must be present in detail response.");
        }

        // 3. Technical, Aliased & Relational Fields
        $metaFields = [
            'id', 'participant_id', 'offline_training_id', 'training_id', 'user_id',
            'user_name', 'user_email', 'training_created_at', 'participant_created_at'
        ];
        foreach ($metaFields as $field) {
            $this->assertArrayHasKey($field, $data, "Metadata / relational field '{$field}' must be present in detail response.");
        }
    }

    /**
     * Test 14: Detail endpoint handles manual unlinked participant (user_id IS NULL) safely.
     */
    public function test_detail_endpoint_unlinked_participant_null_safety()
    {
        $trainingId = DB::table('offline_trainings')->insertGetId([
            'course_name'            => 'Special Machine Safety Inspection',
            'main_classification'    => 'Maintenance',
            'type'                   => 'In-House',
            'created_at'             => now(),
            'updated_at'             => now(),
        ]);
        $this->createdTrainingIds[] = $trainingId;

        $unlinkedPartId = DB::table('offline_training_users')->insertGetId([
            'offline_training_id' => $trainingId,
            'user_id'             => null,
            'employee_id'         => 'TESTEMP_UNLINKED',
            'name'                => 'External Subcontractor Worker',
            'attendance'          => null,
            'assessment_score'    => null,
            'bond_amount'         => null,
            'created_at'          => now(),
            'updated_at'          => now(),
        ]);

        $response = $this->actingAs($this->admin)->getJson("/manage/record/offline/{$unlinkedPartId}/detail");
        $response->assertStatus(200);
        $response->assertJson(['success' => true]);

        $data = $response->json('data');
        $this->assertEquals('TESTEMP_UNLINKED', $data['employee_id']);
        $this->assertEquals('External Subcontractor Worker', $data['participant_name']);
        $this->assertNull($data['user_id']);
        $this->assertNull($data['user_name']);
        $this->assertNull($data['user_email']);
        $this->assertEquals($trainingId, $data['training_id']);
    }
}
