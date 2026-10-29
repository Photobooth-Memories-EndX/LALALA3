<?php

namespace Tests\Feature;

use App\Exports\OfflineClassificationExport;
use App\Models\OfflineTraining;
use App\Models\OfflineTrainingUser;
use App\Models\User;
use App\Services\Record\OfflineTrainingExcelSchema;
use App\Services\Record\RecordOfflineQueryService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class OfflineClassificationFilterAndExportTest extends TestCase
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
     * Helper to seed controlled test data under a specific classification.
     */
    protected function seedFilterTestData(string $classification = 'Testing Classification'): array
    {
        // Training 1: Testing Classification, In-House, 2026, Duration: 7200s (2 hrs = >60min)
        $t1Id = DB::table('offline_trainings')->insertGetId([
            'course_name'            => 'Executive Leadership Mastery',
            'sub_course_name'        => 'Strategic Decision Making',
            'type'                   => 'In-House',
            'category'               => 'Leadership Track',
            'level'                  => 'Senior',
            'batch'                  => 'Batch 1',
            'main_classification'    => $classification,
            'sub_classification'     => 'Strategic Leadership',
            'counted_corporate_kpi'  => true,
            'duration_seconds'       => 7200, // > 1 hr (gt60)
            'learning_hours'         => 8.0,
            'start_date'             => '2026-03-01',
            'finish_date'            => '2026-03-05',
            'created_at'             => now(),
            'updated_at'             => now(),
        ]);
        $this->createdTrainingIds[] = $t1Id;

        // Participant 1: Finished (100%), Present, Score 95.0, Hours 8.0
        $p1Id = DB::table('offline_training_users')->insertGetId([
            'offline_training_id' => $t1Id,
            'employee_id'         => 'TESTEMP901',
            'name'                => 'Alpha Commander',
            'learning_hours'      => 8.0,
            'progress'            => 100.0,
            'attendance'          => true,
            'assessment_score'    => 95.0,
            'created_at'          => now(),
            'updated_at'          => now(),
        ]);

        // Participant 2: Ongoing (45%), Present, Score 75.0, Hours 4.0
        $p2Id = DB::table('offline_training_users')->insertGetId([
            'offline_training_id' => $t1Id,
            'employee_id'         => 'TESTEMP902',
            'name'                => 'Bravo Director',
            'learning_hours'      => 4.0,
            'progress'            => 45.0,
            'attendance'          => true,
            'assessment_score'    => 75.0,
            'created_at'          => now(),
            'updated_at'          => now(),
        ]);

        // Training 2: Testing Classification, Public, 2025, Duration: 900s (15 min = 10-30)
        $t2Id = DB::table('offline_trainings')->insertGetId([
            'course_name'            => 'Agile Team Coaching',
            'sub_course_name'        => 'Sprint Leadership',
            'type'                   => 'Public',
            'category'               => 'Agile Track',
            'level'                  => 'Mid',
            'batch'                  => 'Batch 2',
            'main_classification'    => $classification,
            'sub_classification'     => 'Operational Leadership',
            'counted_corporate_kpi'  => false,
            'duration_seconds'       => 900, // 15 mins (10-30)
            'learning_hours'         => 3.0,
            'start_date'             => '2025-08-10',
            'finish_date'            => '2025-08-12',
            'created_at'             => now(),
            'updated_at'             => now(),
        ]);
        $this->createdTrainingIds[] = $t2Id;

        // Participant 3: Not Started (0%), Absent, Score null, Hours 0.0
        $p3Id = DB::table('offline_training_users')->insertGetId([
            'offline_training_id' => $t2Id,
            'employee_id'         => 'TESTEMP903',
            'name'                => 'Charlie Manager',
            'learning_hours'      => 0.0,
            'progress'            => 0.0,
            'attendance'          => false,
            'assessment_score'    => null,
            'created_at'          => now(),
            'updated_at'          => now(),
        ]);

        // Training 3: Other Classification (Technical) to test classification isolation
        $t3Id = DB::table('offline_trainings')->insertGetId([
            'course_name'            => 'Database Performance Tuning',
            'sub_course_name'        => 'MySQL Optimization',
            'type'                   => 'In-House',
            'category'               => 'IT Track',
            'level'                  => 'Specialist',
            'batch'                  => 'Batch 1',
            'main_classification'    => 'Technical',
            'sub_classification'     => 'Database Administration',
            'counted_corporate_kpi'  => true,
            'duration_seconds'       => 1800,
            'learning_hours'         => 6.0,
            'start_date'             => '2026-03-01',
            'finish_date'            => '2026-03-05',
            'created_at'             => now(),
            'updated_at'             => now(),
        ]);
        $this->createdTrainingIds[] = $t3Id;

        // Participant 4: Technical Participant
        $p4Id = DB::table('offline_training_users')->insertGetId([
            'offline_training_id' => $t3Id,
            'employee_id'         => 'TESTEMP904',
            'name'                => 'Delta Engineer',
            'learning_hours'      => 6.0,
            'progress'            => 100.0,
            'attendance'          => true,
            'assessment_score'    => 88.0,
            'created_at'          => now(),
            'updated_at'          => now(),
        ]);

        return compact('t1Id', 't2Id', 't3Id', 'p1Id', 'p2Id', 'p3Id', 'p4Id');
    }

    public function test_classification_page_requires_permission_and_renders(): void
    {
        $guestResponse = $this->get('/manage/record/offline/classification/leadership');
        $this->assertTrue(in_array($guestResponse->status(), [302, 401, 403]));

        $response = $this->actingAs($this->admin)->get('/manage/record/offline/classification/leadership');
        $response->assertStatus(200);
        $response->assertViewIs('main.record.offline.classification');
        $response->assertSee('Classification: Leadership');
        $response->assertViewHas('classificationName', 'Leadership');
        $response->assertViewHas('slug', 'leadership');
        $response->assertViewHas('downloadUrl');
    }

    public function test_classification_page_renders_quick_filter_elements_with_kp_owl_parity(): void
    {
        $response = $this->actingAs($this->admin)->get('/manage/record/offline/classification/leadership');
        $response->assertStatus(200);

        // Quick filter header & inputs
        $response->assertSee('Quick Filter');
        $response->assertSee('id="offline-employee-input"', false);
        $response->assertSee('placeholder="Search employee or NIP..."', false);
        $response->assertSee('id="offline-course-input"', false);
        $response->assertSee('placeholder="Search learning title..."', false);

        // Dropdowns
        $response->assertSee('id="offline-type-select"', false);
        $response->assertSee('Type: All');
        $response->assertSee('id="offline-status-select"', false);
        $response->assertSee('All Status');
        $response->assertSee('Finished');
        $response->assertSee('Ongoing');
        $response->assertSee('Not Started');
        $response->assertSee('id="offline-year-select"', false);
        $response->assertSee('All Year');

        // Buttons
        $response->assertSee('id="btn-apply-offline-filter"', false);
        $response->assertSee('id="btn-more-offline-filters"', false);
        $response->assertSee('id="activeFilterBadge"', false);
        $response->assertSee('id="btn-reset-offline-filter"', false);
        $response->assertSee('id="btn-download-export"', false);
        $response->assertSee('Download');
    }

    public function test_classification_page_renders_more_filters_modal_with_offline_semantics(): void
    {
        $response = $this->actingAs($this->admin)->get('/manage/record/offline/classification/leadership');
        $response->assertStatus(200);

        // Modal container
        $response->assertSee('id="moreOfflineFiltersModal"', false);
        $response->assertSee('More Filters');

        // Date Range
        $response->assertSee('Date Range (Training Date)');
        $response->assertSee('id="modal-from-date"', false);
        $response->assertSee('id="modal-to-date"', false);

        // Progress Range
        $response->assertSee('Progress Range');
        $response->assertSee('name="modal_progress_range"', false);
        $response->assertSee('id="prAll"', false);
        $response->assertSee('id="pr0"', false);
        $response->assertSee('id="pr25"', false);
        $response->assertSee('id="pr50"', false);
        $response->assertSee('id="pr75"', false);
        $response->assertSee('id="pr99"', false);
        $response->assertSee('id="pr100"', false);

        // Duration
        $response->assertSee('Duration');
        $response->assertSee('name="modal_duration"', false);
        $response->assertSee('id="durAll"', false);
        $response->assertSee('id="dur10"', false);
        $response->assertSee('id="dur30"', false);
        $response->assertSee('id="dur60"', false);
        $response->assertSee('id="durAbove60"', false);

        // Attendance
        $response->assertSee('Attendance');
        $response->assertSee('name="modal_attendance"', false);
        $response->assertSee('id="attAll"', false);
        $response->assertSee('id="attPresent"', false);
        $response->assertSee('id="attAbsent"', false);

        // Assessment Score & Learning Hours
        $response->assertSee('Assessment Score');
        $response->assertSee('id="modal-score-min"', false);
        $response->assertSee('id="modal-score-max"', false);
        $response->assertSee('Learning Hours');
        $response->assertSee('id="modal-hours-min"', false);
        $response->assertSee('id="modal-hours-max"', false);

        // Modal Action Buttons
        $response->assertSee('id="btn-modal-reset"', false);
        $response->assertSee('id="btn-modal-apply"', false);
    }

    public function test_datatable_strictly_locks_route_classification_ignoring_override(): void
    {
        $this->seedFilterTestData('Testing Classification');

        // Request Testing Classification datatable but pass classification=technical in query params
        $response = $this->actingAs($this->admin)->getJson('/manage/record/offline/classification/testing-classification/datatable?classification=technical');
        $response->assertStatus(200);

        $data = $response->json('data');
        $this->assertNotEmpty($data);

        // Ensure Delta Engineer (from Technical) is NOT returned
        $empIds = array_column($data, 'employee_id');
        $this->assertContains('TESTEMP901', $empIds);
        $this->assertNotContains('TESTEMP904', $empIds);
    }

    public function test_datatable_filters_by_employee_search(): void
    {
        $this->seedFilterTestData('Testing Classification');

        // Search by NIP
        $responseNip = $this->actingAs($this->admin)->getJson('/manage/record/offline/classification/testing-classification/datatable?employee_id=TESTEMP901');
        $responseNip->assertStatus(200);
        $dataNip = $responseNip->json('data');
        $this->assertCount(1, $dataNip);
        $this->assertEquals('TESTEMP901', $dataNip[0]['employee_id']);
        $this->assertStringContainsString('Alpha Commander', $dataNip[0]['employee']);

        // Search by participant name
        $responseName = $this->actingAs($this->admin)->getJson('/manage/record/offline/classification/testing-classification/datatable?employee_id=Bravo');
        $responseName->assertStatus(200);
        $dataName = $responseName->json('data');
        $this->assertCount(1, $dataName);
        $this->assertEquals('TESTEMP902', $dataName[0]['employee_id']);
        $this->assertStringContainsString('Bravo Director', $dataName[0]['employee']);
    }

    public function test_datatable_filters_by_course_name(): void
    {
        $this->seedFilterTestData('Testing Classification');

        $response = $this->actingAs($this->admin)->getJson('/manage/record/offline/classification/testing-classification/datatable?course_name=Agile+Team');
        $response->assertStatus(200);
        $data = $response->json('data');
        $this->assertCount(1, $data);
        $this->assertEquals('TESTEMP903', $data[0]['employee_id']);
        $this->assertStringContainsString('Charlie Manager', $data[0]['employee']);
        $this->assertStringContainsString('Agile Team Coaching', $data[0]['course_name']);
    }

    public function test_datatable_filters_by_type(): void
    {
        $this->seedFilterTestData('Testing Classification');

        // Filter Public
        $response = $this->actingAs($this->admin)->getJson('/manage/record/offline/classification/testing-classification/datatable?type=Public');
        $response->assertStatus(200);
        $data = $response->json('data');
        $this->assertCount(1, $data);
        $this->assertEquals('TESTEMP903', $data[0]['employee_id']);
        $this->assertStringContainsString('Charlie Manager', $data[0]['employee']);

        // Filter In-House
        $responseInHouse = $this->actingAs($this->admin)->getJson('/manage/record/offline/classification/testing-classification/datatable?type=In-House');
        $responseInHouse->assertStatus(200);
        $dataInHouse = $responseInHouse->json('data');
        $this->assertCount(2, $dataInHouse);
    }

    public function test_datatable_filters_by_status(): void
    {
        $this->seedFilterTestData('Testing Classification');

        // Finished: progress >= 100
        $resFinished = $this->actingAs($this->admin)->getJson('/manage/record/offline/classification/testing-classification/datatable?status=finished');
        $resFinished->assertStatus(200);
        $dataFinished = $resFinished->json('data');
        $this->assertCount(1, $dataFinished);
        $this->assertEquals('TESTEMP901', $dataFinished[0]['employee_id']);
        $this->assertStringContainsString('Alpha Commander', $dataFinished[0]['employee']);

        // Ongoing: 0 < progress < 100
        $resOngoing = $this->actingAs($this->admin)->getJson('/manage/record/offline/classification/testing-classification/datatable?status=ongoing');
        $resOngoing->assertStatus(200);
        $dataOngoing = $resOngoing->json('data');
        $this->assertCount(1, $dataOngoing);
        $this->assertEquals('TESTEMP902', $dataOngoing[0]['employee_id']);
        $this->assertStringContainsString('Bravo Director', $dataOngoing[0]['employee']);

        // Not Started: progress <= 0
        $resNotStarted = $this->actingAs($this->admin)->getJson('/manage/record/offline/classification/testing-classification/datatable?status=not_started');
        $resNotStarted->assertStatus(200);
        $dataNotStarted = $resNotStarted->json('data');
        $this->assertCount(1, $dataNotStarted);
        $this->assertEquals('TESTEMP903', $dataNotStarted[0]['employee_id']);
        $this->assertStringContainsString('Charlie Manager', $dataNotStarted[0]['employee']);
    }

    public function test_datatable_filters_by_year(): void
    {
        $this->seedFilterTestData('Testing Classification');

        // Year 2026
        $res2026 = $this->actingAs($this->admin)->getJson('/manage/record/offline/classification/testing-classification/datatable?year=2026');
        $res2026->assertStatus(200);
        $data2026 = $res2026->json('data');
        $this->assertCount(2, $data2026);

        // Year 2025
        $res2025 = $this->actingAs($this->admin)->getJson('/manage/record/offline/classification/testing-classification/datatable?year=2025');
        $res2025->assertStatus(200);
        $data2025 = $res2025->json('data');
        $this->assertCount(1, $data2025);
        $this->assertEquals('TESTEMP903', $data2025[0]['employee_id']);
        $this->assertStringContainsString('Charlie Manager', $data2025[0]['employee']);
    }

    public function test_datatable_more_filter_by_date_range(): void
    {
        $this->seedFilterTestData('Testing Classification');

        // Date range covering March 2026
        $res = $this->actingAs($this->admin)->getJson('/manage/record/offline/classification/testing-classification/datatable?from_date=2026-03-01&to_date=2026-03-31');
        $res->assertStatus(200);
        $data = $res->json('data');
        $this->assertCount(2, $data);

        // Date range in dd/mm/yyyy format
        $resFormat = $this->actingAs($this->admin)->getJson('/manage/record/offline/classification/testing-classification/datatable?from_date=01/08/2025&to_date=31/08/2025');
        $resFormat->assertStatus(200);
        $dataFormat = $resFormat->json('data');
        $this->assertCount(1, $dataFormat);
        $this->assertEquals('TESTEMP903', $dataFormat[0]['employee_id']);
        $this->assertStringContainsString('Charlie Manager', $dataFormat[0]['employee']);
    }

    public function test_datatable_more_filter_by_progress_range(): void
    {
        $this->seedFilterTestData('Testing Classification');

        // Progress 26-50% (Bravo Director is 45%)
        $res = $this->actingAs($this->admin)->getJson('/manage/record/offline/classification/testing-classification/datatable?progress_range=26-50');
        $res->assertStatus(200);
        $data = $res->json('data');
        $this->assertCount(1, $data);
        $this->assertEquals('TESTEMP902', $data[0]['employee_id']);
        $this->assertStringContainsString('Bravo Director', $data[0]['employee']);

        // Progress 100%
        $res100 = $this->actingAs($this->admin)->getJson('/manage/record/offline/classification/testing-classification/datatable?progress_range=100');
        $res100->assertStatus(200);
        $data100 = $res100->json('data');
        $this->assertCount(1, $data100);
        $this->assertEquals('TESTEMP901', $data100[0]['employee_id']);
        $this->assertStringContainsString('Alpha Commander', $data100[0]['employee']);
    }

    public function test_datatable_more_filter_by_duration(): void
    {
        $this->seedFilterTestData('Testing Classification');

        // > 1 hour (7200s) -> Alpha and Bravo
        $resGt60 = $this->actingAs($this->admin)->getJson('/manage/record/offline/classification/testing-classification/datatable?duration=gt60');
        $resGt60->assertStatus(200);
        $dataGt60 = $resGt60->json('data');
        $this->assertCount(2, $dataGt60);

        // 10-30 minutes (900s) -> Charlie
        $res1030 = $this->actingAs($this->admin)->getJson('/manage/record/offline/classification/testing-classification/datatable?duration=10-30');
        $res1030->assertStatus(200);
        $data1030 = $res1030->json('data');
        $this->assertCount(1, $data1030);
        $this->assertEquals('TESTEMP903', $data1030[0]['employee_id']);
        $this->assertStringContainsString('Charlie Manager', $data1030[0]['employee']);
    }

    public function test_datatable_more_filter_by_attendance(): void
    {
        $this->seedFilterTestData('Testing Classification');

        // Attendance: Present (true)
        $resPresent = $this->actingAs($this->admin)->getJson('/manage/record/offline/classification/testing-classification/datatable?attendance=present');
        $resPresent->assertStatus(200);
        $dataPresent = $resPresent->json('data');
        $this->assertCount(2, $dataPresent);

        // Attendance: Absent (false)
        $resAbsent = $this->actingAs($this->admin)->getJson('/manage/record/offline/classification/testing-classification/datatable?attendance=absent');
        $resAbsent->assertStatus(200);
        $dataAbsent = $resAbsent->json('data');
        $this->assertCount(1, $dataAbsent);
        $this->assertEquals('TESTEMP903', $dataAbsent[0]['employee_id']);
        $this->assertStringContainsString('Charlie Manager', $dataAbsent[0]['employee']);
    }

    public function test_datatable_more_filter_by_assessment_score(): void
    {
        $this->seedFilterTestData('Testing Classification');

        // Score >= 90.0
        $res = $this->actingAs($this->admin)->getJson('/manage/record/offline/classification/testing-classification/datatable?score_min=90');
        $res->assertStatus(200);
        $data = $res->json('data');
        $this->assertCount(1, $data);
        $this->assertEquals('TESTEMP901', $data[0]['employee_id']);
        $this->assertStringContainsString('Alpha Commander', $data[0]['employee']);

        // Score between 70 and 80
        $resRange = $this->actingAs($this->admin)->getJson('/manage/record/offline/classification/testing-classification/datatable?score_min=70&score_max=80');
        $resRange->assertStatus(200);
        $dataRange = $resRange->json('data');
        $this->assertCount(1, $dataRange);
        $this->assertEquals('TESTEMP902', $dataRange[0]['employee_id']);
        $this->assertStringContainsString('Bravo Director', $dataRange[0]['employee']);
    }

    public function test_datatable_more_filter_by_learning_hours(): void
    {
        $this->seedFilterTestData('Testing Classification');

        // Hours >= 7.0
        $res = $this->actingAs($this->admin)->getJson('/manage/record/offline/classification/testing-classification/datatable?hours_min=7');
        $res->assertStatus(200);
        $data = $res->json('data');
        $this->assertCount(1, $data);
        $this->assertEquals('TESTEMP901', $data[0]['employee_id']);
        $this->assertStringContainsString('Alpha Commander', $data[0]['employee']);
    }

    public function test_datatable_combined_quick_and_more_filters(): void
    {
        $this->seedFilterTestData('Testing Classification');

        // In-House + Present + Ongoing (45%)
        $res = $this->actingAs($this->admin)->getJson('/manage/record/offline/classification/testing-classification/datatable?type=In-House&attendance=present&status=ongoing');
        $res->assertStatus(200);
        $data = $res->json('data');
        $this->assertCount(1, $data);
        $this->assertEquals('TESTEMP902', $data[0]['employee_id']);
        $this->assertStringContainsString('Bravo Director', $data[0]['employee']);
    }

    public function test_export_endpoint_requires_permission(): void
    {
        $guestRes = $this->get('/manage/record/offline/classification/testing-classification/download');
        $this->assertTrue(in_array($guestRes->status(), [302, 401, 403]));
    }

    public function test_export_endpoint_downloads_excel_file_with_valid_headers_and_filename(): void
    {
        $this->seedFilterTestData('Testing Classification');

        $response = $this->actingAs($this->admin)->get('/manage/record/offline/classification/testing-classification/download');
        $response->assertStatus(200);

        // Check content-type or disposition
        $disposition = $response->headers->get('content-disposition');
        $this->assertNotNull($disposition);
        $this->assertStringContainsString('attachment', $disposition);
        $this->assertStringContainsString('non-kp-owl-testing-classification-', $disposition);
        $this->assertStringContainsString('.xlsx', $disposition);
    }

    public function test_export_respects_route_classification_and_active_filters(): void
    {
        $this->seedFilterTestData('Testing Classification');

        // Export with filter status=finished (should only include Alpha Commander)
        $request = new Request([
            'status' => 'finished',
        ]);
        $export = new OfflineClassificationExport('testing-classification', $request);

        $query = $export->query();
        $results = $query->get();

        $this->assertCount(1, $results);
        $this->assertEquals('TESTEMP901', $results[0]->employee_id);
        $this->assertEquals('Alpha Commander', $results[0]->participant_name);
        $this->assertEquals('Testing Classification', $results[0]->main_classification);
    }

    public function test_export_columns_match_offline_training_excel_schema_44_columns(): void
    {
        $this->seedFilterTestData('Testing Classification');

        $request = new Request();
        $export = new OfflineClassificationExport('testing-classification', $request);

        // 1. Verify Headings
        $headings = $export->headings();
        $expectedHeadings = OfflineTrainingExcelSchema::headers();
        $this->assertCount(44, $headings);
        $this->assertEquals($expectedHeadings, $headings);

        // 2. Verify Mapping
        $query = $export->query();
        $firstRow = $query->first();
        $this->assertNotNull($firstRow);

        $mapped = $export->map($firstRow);
        $this->assertCount(44, $mapped);

        // Verify key mapped values (44 canonical columns starting with Employee ID at index 0)
        $this->assertEquals('TESTEMP901', $mapped[0]); // 1. Employee ID
        $this->assertNotEmpty($mapped[1]); // 2. Employee Name
        $this->assertContains($mapped[9], ['In-House', 'Public']); // 10. Type
        $this->assertEquals('Testing Classification', $mapped[13]); // 14. Main Classification
        $this->assertStringContainsString(':', $mapped[20]); // 21. Course Duration (HH:MM:SS)
        $this->assertIsNumeric($mapped[21]); // 22. Learning Hours
        $this->assertIsNumeric($mapped[22]); // 23. % Progress
    }

    public function test_shared_query_service_guarantees_datatable_and_export_parity(): void
    {
        $this->seedFilterTestData('Testing Classification');

        $filters = [
            'type'       => 'In-House',
            'attendance' => 'present',
        ];

        // Service query count
        $serviceQuery = RecordOfflineQueryService::buildClassificationQuery('testing-classification', $filters);
        $count = $serviceQuery->count();
        $this->assertEquals(2, $count);

        // Datatable response count
        $dtRes = $this->actingAs($this->admin)->getJson('/manage/record/offline/classification/testing-classification/datatable?type=In-House&attendance=present');
        $dtRes->assertStatus(200);
        $this->assertEquals($count, $dtRes->json('recordsFiltered'));

        // Export query count
        $exportReq = new Request($filters);
        $export = new OfflineClassificationExport('testing-classification', $exportReq);
        $this->assertEquals($count, $export->query()->count());
    }

    public function test_soft_deleted_records_are_excluded_from_datatable_and_export(): void
    {
        $data = $this->seedFilterTestData('Testing Classification');

        // Soft delete training 1
        DB::table('offline_trainings')->where('id', $data['t1Id'])->update(['deleted_at' => now()]);

        $res = $this->actingAs($this->admin)->getJson('/manage/record/offline/classification/testing-classification/datatable');
        $res->assertStatus(200);
        $dtData = $res->json('data');

        // Should only contain Charlie Manager (from Training 2)
        $this->assertCount(1, $dtData);
        $this->assertEquals('TESTEMP903', $dtData[0]['employee_id']);
        $this->assertStringContainsString('Charlie Manager', $dtData[0]['employee']);

        // Export should also only contain Charlie Manager
        $export = new OfflineClassificationExport('testing-classification', new Request());
        $this->assertEquals(1, $export->query()->count());
    }

    public function test_regression_offline_landing_and_create_pages_remain_intact(): void
    {
        $landingRes = $this->actingAs($this->admin)->get('/manage/record/offline');
        $landingRes->assertStatus(200);
        $landingRes->assertSee('Non KP OWL');

        $createRes = $this->actingAs($this->admin)->get('/manage/record/offline/create');
        $createRes->assertStatus(200);
        $createRes->assertSee('Add Non KP OWL Training');
    }
}
