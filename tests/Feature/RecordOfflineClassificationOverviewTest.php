<?php

namespace Tests\Feature;

use App\Models\OfflineTraining;
use App\Models\OfflineTrainingUser;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class RecordOfflineClassificationOverviewTest extends TestCase
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

        parent::tearDown();
    }

    /**
     * Helper to create test offline trainings and participants.
     */
    protected function seedClassificationTestData(): void
    {
        // 1. Training A (Leadership) - 2 participants (1 ongoing, 1 finished)
        $t1Id = DB::table('offline_trainings')->insertGetId([
            'course_name'         => 'Executive Leadership 2026',
            'type'                => 'In-House',
            'category'            => 'Leadership Category',
            'level'               => 'Advanced',
            'batch'               => 'Batch 1',
            'main_classification' => 'Leadership',
            'learning_hours'      => 8.0,
            'start_date'          => '2026-02-01',
            'finish_date'         => '2026-02-05',
            'created_at'          => now(),
            'updated_at'          => now(),
        ]);
        $this->createdTrainingIds[] = $t1Id;

        // Participant 1: Ongoing (50%), 8 hours
        DB::table('offline_training_users')->insert([
            'offline_training_id' => $t1Id,
            'employee_id'         => 'TESTEMP01',
            'name'                => 'Alice Leadership',
            'learning_hours'      => 8.0,
            'progress'            => 50.0,
            'created_at'          => now(),
            'updated_at'          => now(),
        ]);

        // Participant 2: Finished (100%), 8 hours
        DB::table('offline_training_users')->insert([
            'offline_training_id' => $t1Id,
            'employee_id'         => 'TESTEMP02',
            'name'                => 'Bob Leadership',
            'learning_hours'      => 8.0,
            'progress'            => 100.0,
            'created_at'          => now(),
            'updated_at'          => now(),
        ]);

        // 2. Training B (Leadership) - 1 participant (finished), 4 hours
        $t2Id = DB::table('offline_trainings')->insertGetId([
            'course_name'         => 'Supervisory Leadership',
            'type'                => 'Public',
            'category'            => 'Leadership Category',
            'level'               => 'Intermediate',
            'batch'               => 'Batch 2',
            'main_classification' => 'Leadership',
            'learning_hours'      => 4.0,
            'start_date'          => '2025-06-10',
            'finish_date'         => '2025-06-12',
            'created_at'          => now(),
            'updated_at'          => now(),
        ]);
        $this->createdTrainingIds[] = $t2Id;

        DB::table('offline_training_users')->insert([
            'offline_training_id' => $t2Id,
            'employee_id'         => 'TESTEMP03',
            'name'                => 'Charlie Leadership',
            'learning_hours'      => 4.0,
            'progress'            => 100.0,
            'created_at'          => now(),
            'updated_at'          => now(),
        ]);

        // 3. Training C (Basic & Common Duties) - 1 participant (Not Started 0%), 6 hours
        $t3Id = DB::table('offline_trainings')->insertGetId([
            'course_name'         => 'Safety Induction Standard',
            'type'                => 'In-House',
            'category'            => 'Safety',
            'level'               => 'Basic',
            'batch'               => 'Batch 1',
            'main_classification' => 'Basic & Common Duties',
            'learning_hours'      => 6.0,
            'start_date'          => '2026-03-01',
            'finish_date'         => '2026-03-02',
            'created_at'          => now(),
            'updated_at'          => now(),
        ]);
        $this->createdTrainingIds[] = $t3Id;

        DB::table('offline_training_users')->insert([
            'offline_training_id' => $t3Id,
            'employee_id'         => 'TESTEMP04',
            'name'                => 'David Basic',
            'learning_hours'      => 6.0,
            'progress'            => 0.0,
            'created_at'          => now(),
            'updated_at'          => now(),
        ]);

        // 4. Training D (NULL classification -> Unclassified) - 1 participant (finished), 2.5 hours
        $t4Id = DB::table('offline_trainings')->insertGetId([
            'course_name'         => 'Special Workshop 2026',
            'type'                => 'Workshop',
            'category'            => 'Special',
            'level'               => 'All',
            'batch'               => 'Batch 1',
            'main_classification' => null,
            'learning_hours'      => 2.5,
            'start_date'          => '2026-04-01',
            'finish_date'         => '2026-04-02',
            'created_at'          => now(),
            'updated_at'          => now(),
        ]);
        $this->createdTrainingIds[] = $t4Id;

        DB::table('offline_training_users')->insert([
            'offline_training_id' => $t4Id,
            'employee_id'         => 'TESTEMP05',
            'name'                => 'Eve Unclassified',
            'learning_hours'      => 2.5,
            'progress'            => 100.0,
            'created_at'          => now(),
            'updated_at'          => now(),
        ]);
    }

    /**
     * 1. /manage/record/offline requires permission.
     */
    public function test_offline_page_requires_permission(): void
    {
        $guestResponse = $this->get('/manage/record/offline');
        $this->assertTrue($guestResponse->isRedirect() || $guestResponse->getStatusCode() === 401);

        if ($this->regularUser) {
            $unauthResponse = $this->actingAs($this->regularUser)->get('/manage/record/offline');
            $unauthResponse->assertStatus(403);
        }

        $adminResponse = $this->actingAs($this->admin)->get('/manage/record/offline');
        $adminResponse->assertStatus(200);
    }

    /**
     * 2. landing page renders Non KP OWL overview.
     */
    public function test_landing_page_renders_non_kp_owl_overview(): void
    {
        $response = $this->actingAs($this->admin)->get('/manage/record/offline');
        $response->assertStatus(200);
        $response->assertViewIs('main.record.offline');
        $response->assertSee('Non KP OWL');
        $response->assertSee('Offline Training Record');
        $response->assertSee('offline-kpi-summary-container', false);
        $response->assertSee('offline-classification-summary-container', false);
        $response->assertSee('Classification Summary');
    }

    /**
     * 3, 4, 5. landing page still renders Add Training, Import Excel, Download Template.
     */
    public function test_landing_page_still_renders_action_buttons(): void
    {
        $response = $this->actingAs($this->admin)->get('/manage/record/offline');
        $response->assertStatus(200);

        // 3. Add Training
        $response->assertSee('Add Training');

        // 4. Import Excel
        $response->assertSee('Import Excel');
        $response->assertSee('data-bs-target="#offlineImportModal"', false);

        // 5. Download Template
        $response->assertSee('Download Template');
        $response->assertSee(route('manage.record.offline.import.template'));
    }

    /**
     * 6, 7, 8. landing page does NOT render Quick Filter or full DataTable and does NOT initialize it.
     */
    public function test_landing_page_does_not_render_or_initialize_quick_filter_or_datatable(): void
    {
        $response = $this->actingAs($this->admin)->get('/manage/record/offline');
        $response->assertStatus(200);

        // 6. Does NOT render Quick Filter
        $response->assertDontSee('id="offline-filter-form"', false);
        $response->assertDontSee('id="offline-employee-input"', false);
        $response->assertDontSee('id="offline-course-input"', false);

        // 7. Does NOT render full offline DataTable
        $response->assertDontSee('id="offline-learning-table"', false);
        $response->assertDontSee('id="offline-classification-table"', false);

        // 8. Does NOT initialize offline DataTable in JS
        $response->assertDontSee('.DataTable(', false);
    }

    /**
     * 9. KPI endpoint remains protected.
     */
    public function test_kpi_endpoint_remains_protected(): void
    {
        $guestResponse = $this->get('/manage/record/offline/ajax/summary');
        $this->assertTrue($guestResponse->isRedirect() || $guestResponse->getStatusCode() === 401);

        if ($this->regularUser) {
            $unauthResponse = $this->actingAs($this->regularUser)->get('/manage/record/offline/ajax/summary');
            $unauthResponse->assertStatus(403);
        }

        $adminResponse = $this->actingAs($this->admin)->getJson('/manage/record/offline/ajax/summary');
        $adminResponse->assertStatus(200);
        $adminResponse->assertJsonStructure(['success', 'total_trainings', 'total_participants', 'total_learning_hours', 'completion_rate']);
    }

    /**
     * 10. classification endpoint requires permission.
     */
    public function test_classification_endpoint_requires_permission(): void
    {
        $guestResponse = $this->get('/manage/record/offline/ajax/classification');
        $this->assertTrue($guestResponse->isRedirect() || $guestResponse->getStatusCode() === 401);

        if ($this->regularUser) {
            $unauthResponse = $this->actingAs($this->regularUser)->get('/manage/record/offline/ajax/classification');
            $unauthResponse->assertStatus(403);
        }

        $adminResponse = $this->actingAs($this->admin)->getJson('/manage/record/offline/ajax/classification');
        $adminResponse->assertStatus(200);
        $adminResponse->assertJsonStructure(['success', 'data']);
    }

    /**
     * 11, 12, 13, 14, 15, 16, 17. classification summary metrics, grouping, and null normalization.
     */
    public function test_classification_summary_metrics_and_aggregation(): void
    {
        $this->seedClassificationTestData();

        $response = $this->actingAs($this->admin)->getJson('/manage/record/offline/ajax/classification');
        $response->assertStatus(200);

        $data = collect($response->json('data'));

        // 11. Groups by main_classification
        $leadership = $data->firstWhere('classification_name', 'Leadership');
        $basic = $data->firstWhere('classification_name', 'Basic & Common Duties');
        $unclassified = $data->firstWhere('classification_name', 'Unclassification') ?? $data->firstWhere('classification_name', 'Unclassified');

        $this->assertNotNull($leadership, 'Leadership classification must exist.');
        $this->assertNotNull($basic, 'Basic & Common Duties classification must exist.');
        $this->assertNotNull($unclassified, 'Null classification must be normalized to Unclassified.');

        // 12. Program count (Training count, not participant count!)
        $this->assertEquals(2, $leadership['programs'], 'Leadership has 2 programs (trainings).');
        $this->assertEquals(1, $basic['programs'], 'Basic & Common Duties has 1 program.');
        $this->assertEquals(1, $unclassified['programs'], 'Unclassified has 1 program.');

        // 13. Participant count
        $this->assertEquals(3, $leadership['total_participants'], 'Leadership has 3 participants total.');
        $this->assertEquals(1, $basic['total_participants'], 'Basic & Common Duties has 1 participant.');
        $this->assertEquals(1, $unclassified['total_participants'], 'Unclassified has 1 participant.');

        // 14. Learning hours aggregation (SUM of participant hours: 8 + 8 + 4 = 20)
        $this->assertEquals(20.0, (float) $leadership['learning_hours']);
        $this->assertEquals(6.0, (float) $basic['learning_hours']);
        $this->assertEquals(2.5, (float) $unclassified['learning_hours']);

        // 15. Ongoing count (progress > 0 and < 100) -> 1 participant (Alice)
        $this->assertEquals(1, $leadership['ongoing']);
        $this->assertEquals(0, $basic['ongoing']); // progress is 0 => not ongoing

        // 16. Finished count (progress >= 100) -> 2 participants (Bob, Charlie)
        $this->assertEquals(2, $leadership['finished']);
        $this->assertEquals(0, $basic['finished']);
        $this->assertEquals(1, $unclassified['finished']);

        // 17. Null classification appears as Unclassified with slug 'unclassified'
        $this->assertEquals('unclassified', $unclassified['slug']);
    }

    /**
     * 18, 19. classification names are clickable and open correct detail route.
     */
    public function test_classification_names_are_clickable_with_correct_routes(): void
    {
        $this->seedClassificationTestData();

        $response = $this->actingAs($this->admin)->get('/manage/record/offline/ajax/classification?format=html');
        $response->assertStatus(200);

        // 18 & 19. Clickable links with detail routes
        $response->assertSee(route('manage.record.offline.classification', 'leadership'));
        $response->assertSee(route('manage.record.offline.classification', 'basic-common-duties'));
        $response->assertSee(route('manage.record.offline.classification', $unclassified['slug'] ?? 'unclassification'));
    }

    /**
     * 20. classification detail requires permission.
     */
    public function test_classification_detail_requires_permission(): void
    {
        $guestResponse = $this->get('/manage/record/offline/classification/leadership');
        $this->assertTrue($guestResponse->isRedirect() || $guestResponse->getStatusCode() === 401);

        if ($this->regularUser) {
            $unauthResponse = $this->actingAs($this->regularUser)->get('/manage/record/offline/classification/leadership');
            $unauthResponse->assertStatus(403);
        }

        $adminResponse = $this->actingAs($this->admin)->get('/manage/record/offline/classification/leadership');
        $adminResponse->assertStatus(200);
    }

    /**
     * 21. detail page displays selected classification and back button.
     */
    public function test_detail_page_displays_selected_classification(): void
    {
        $response = $this->actingAs($this->admin)->get('/manage/record/offline/classification/leadership');
        $response->assertStatus(200);
        $response->assertViewIs('main.record.offline.classification');
        $response->assertSee('Classification: Leadership');
        $response->assertSee('Back to Non KP OWL');
        $response->assertSee(route('manage.record.offline'));
        $response->assertSee('id="offline-filter-form"', false);
        $response->assertSee('id="offline-classification-table"', false);

        // Classification dropdown is locked / not rendered in quick filter
        $response->assertDontSee('id="offline-classification-select"', false);
    }

    /**
     * 22. detail DataTable only returns records belonging to selected classification.
     */
    public function test_detail_datatable_only_returns_selected_classification_records(): void
    {
        $this->seedClassificationTestData();

        // Query Leadership datatable
        $response = $this->actingAs($this->admin)->getJson(
            route('manage.record.offline.classification.datatable', 'leadership') . '?draw=1'
        );
        $response->assertStatus(200);

        $json = $response->json();
        $this->assertEquals(3, $json['recordsTotal'], 'Leadership should strictly return 3 participants.');
        $this->assertEquals(3, $json['recordsFiltered']);

        $participantNames = collect($json['data'])->pluck('participant_name')->all();
        $this->assertContains('Alice Leadership', $participantNames);
        $this->assertContains('Bob Leadership', $participantNames);
        $this->assertContains('Charlie Leadership', $participantNames);
        $this->assertNotContains('David Basic', $participantNames);
        $this->assertNotContains('Eve Unclassified', $participantNames);

        // Query Unclassified datatable
        $unclassifiedRes = $this->actingAs($this->admin)->getJson(
            route('manage.record.offline.classification.datatable', 'unclassified') . '?draw=1'
        );
        $unclassifiedRes->assertStatus(200);
        $uJson = $unclassifiedRes->json();
        $this->assertEquals(1, $uJson['recordsTotal']);
        $this->assertContains('Eve Unclassified', collect($uJson['data'])->pluck('participant_name')->all());
    }

    /**
     * 23. search employee works inside classification.
     */
    public function test_search_employee_works_inside_classification(): void
    {
        $this->seedClassificationTestData();

        $response = $this->actingAs($this->admin)->getJson(
            route('manage.record.offline.classification.datatable', 'leadership') . '?draw=1&employee_id=TESTEMP01'
        );
        $response->assertStatus(200);
        $json = $response->json();
        $this->assertEquals(1, $json['recordsFiltered']);
        $this->assertEquals('Alice Leadership', $json['data'][0]['participant_name']);
    }

    /**
     * 24. search course works inside classification.
     */
    public function test_search_course_works_inside_classification(): void
    {
        $this->seedClassificationTestData();

        $response = $this->actingAs($this->admin)->getJson(
            route('manage.record.offline.classification.datatable', 'leadership') . '?draw=1&course_name=Supervisory'
        );
        $response->assertStatus(200);
        $json = $response->json();
        $this->assertEquals(1, $json['recordsFiltered']);
        $this->assertEquals('Charlie Leadership', $json['data'][0]['participant_name']);
    }

    /**
     * 25. type filter works inside classification.
     */
    public function test_type_filter_works_inside_classification(): void
    {
        $this->seedClassificationTestData();

        $response = $this->actingAs($this->admin)->getJson(
            route('manage.record.offline.classification.datatable', 'leadership') . '?draw=1&type=Public'
        );
        $response->assertStatus(200);
        $json = $response->json();
        $this->assertEquals(1, $json['recordsFiltered']);
        $this->assertEquals('Charlie Leadership', $json['data'][0]['participant_name']);
    }

    /**
     * 26. status filter works inside classification.
     */
    public function test_status_filter_works_inside_classification(): void
    {
        $this->seedClassificationTestData();

        // Ongoing (Alice)
        $ongoingRes = $this->actingAs($this->admin)->getJson(
            route('manage.record.offline.classification.datatable', 'leadership') . '?draw=1&status=ongoing'
        );
        $ongoingRes->assertStatus(200);
        $this->assertEquals(1, $ongoingRes->json('recordsFiltered'));
        $this->assertEquals('Alice Leadership', $ongoingRes->json('data')[0]['participant_name']);

        // Finished (Bob & Charlie)
        $finishedRes = $this->actingAs($this->admin)->getJson(
            route('manage.record.offline.classification.datatable', 'leadership') . '?draw=1&status=finished'
        );
        $finishedRes->assertStatus(200);
        $this->assertEquals(2, $finishedRes->json('recordsFiltered'));
    }

    /**
     * 27. year filter works inside classification.
     */
    public function test_year_filter_works_inside_classification(): void
    {
        $this->seedClassificationTestData();

        // 2026 -> Executive Leadership (Alice & Bob)
        $y2026Res = $this->actingAs($this->admin)->getJson(
            route('manage.record.offline.classification.datatable', 'leadership') . '?draw=1&year=2026'
        );
        $y2026Res->assertStatus(200);
        $this->assertEquals(2, $y2026Res->json('recordsFiltered'));

        // 2025 -> Supervisory Leadership (Charlie)
        $y2025Res = $this->actingAs($this->admin)->getJson(
            route('manage.record.offline.classification.datatable', 'leadership') . '?draw=1&year=2025'
        );
        $y2025Res->assertStatus(200);
        $this->assertEquals(1, $y2025Res->json('recordsFiltered'));
        $this->assertEquals('Charlie Leadership', $y2025Res->json('data')[0]['participant_name']);
    }

    /**
     * 28. pagination remains server-side.
     */
    public function test_pagination_remains_server_side(): void
    {
        $this->seedClassificationTestData();

        $response = $this->actingAs($this->admin)->getJson(
            route('manage.record.offline.classification.datatable', 'leadership') . '?draw=1&start=0&length=1'
        );
        $response->assertStatus(200);
        $json = $response->json();
        $this->assertEquals(3, $json['recordsTotal']);
        $this->assertEquals(3, $json['recordsFiltered']);
        $this->assertCount(1, $json['data'], 'Server-side pagination limits output rows to requested length.');
    }

    /**
     * 29, 30, 31. import modal, template download, and add training still work.
     */
    public function test_preserved_features_import_modal_and_template_download(): void
    {
        // 29. Import modal present on landing page
        $landing = $this->actingAs($this->admin)->get('/manage/record/offline');
        $landing->assertStatus(200);
        $landing->assertSee('id="offlineImportModal"', false);

        // 30. Template download still returns valid XLSX download
        $templateRes = $this->actingAs($this->admin)->get('/manage/record/offline/import/template');
        $templateRes->assertStatus(200);
        $this->assertStringContainsString('spreadsheetml.sheet', (string) $templateRes->headers->get('content-type'));

        // 31. Add training button preserved on landing page
        $landing->assertSee('Add Training');
    }
}
