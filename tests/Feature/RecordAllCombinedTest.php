<?php

namespace Tests\Feature;

use App\Models\OfflineTraining;
use App\Models\OfflineTrainingUser;
use App\Models\User;
use App\Services\Record\RecordAllCombinedService;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class RecordAllCombinedTest extends TestCase
{
    protected User $admin;
    protected ?User $regularUser;
    protected array $cleanupTrainingIds = [];

    protected function setUp(): void
    {
        parent::setUp();

        // In OWL LMS: role 2 is Admin (has record_management), role 1 is Regular User
        $this->admin = User::where('role', '2')->first() ?? User::first();
        $this->regularUser = User::where('role', '1')->first();
    }

    protected function tearDown(): void
    {
        // Clean up any test records created in database
        if (!empty($this->cleanupTrainingIds)) {
            OfflineTrainingUser::whereIn('offline_training_id', $this->cleanupTrainingIds)->forceDelete();
            OfflineTraining::whereIn('id', $this->cleanupTrainingIds)->forceDelete();
            $this->cleanupTrainingIds = [];
        }

        DB::table('offline_training_users')->where('company', 'TEST_COMBINED_CORP')->delete();
        DB::table('offline_trainings')->where('category', 'TEST_COMBINED_CAT')->delete();

        parent::tearDown();
    }

    /**
     * Helper to create test offline training & participants.
     */
    protected function createTestOfflineTraining(array $trainingAttrs = [], array $participants = []): OfflineTraining
    {
        $training = OfflineTraining::create(array_merge([
            'course_name'           => 'Test Combined Course ' . uniqid(),
            'type'                  => 'In-House',
            'category'              => 'TEST_COMBINED_CAT',
            'main_classification'   => 'General Competency',
            'counted_corporate_kpi' => 1,
            'duration_seconds'      => 28800, // 8 hours planned
            'learning_hours'        => 8.0,
            'start_date'            => '2026-08-01',
            'finish_date'           => '2026-08-01',
            'created_by'            => $this->admin->id,
        ], $trainingAttrs));

        $this->cleanupTrainingIds[] = $training->id;

        foreach ($participants as $p) {
            OfflineTrainingUser::create(array_merge([
                'offline_training_id' => $training->id,
                'user_id'             => $this->admin->id,
                'employee_id'         => 'EMP_TEST_' . uniqid(),
                'name'                => 'Combined Test Participant',
                'company'             => 'TEST_COMBINED_CORP',
                'learning_hours'      => 8.0,
                'progress'            => 100,
            ], $p));
        }

        return $training;
    }

    /**
     * 1. Test All Record landing page requires permission and renders lightweight shell.
     */
    public function test_all_record_page_requires_permission_and_renders_shell(): void
    {
        // Guest redirected or 401
        $guestResponse = $this->get('/manage/record/all');
        $this->assertTrue($guestResponse->isRedirect() || $guestResponse->getStatusCode() === 401);

        // Regular user forbidden
        if ($this->regularUser) {
            $unauthResponse = $this->actingAs($this->regularUser)->get('/manage/record/all');
            $unauthResponse->assertStatus(403);
        }

        // Admin success
        $queries = [];
        DB::listen(function ($query) use (&$queries) {
            $queries[] = $query->sql;
        });

        $start = microtime(true);
        $response = $this->actingAs($this->admin)->get('/manage/record/all');
        $duration = microtime(true) - $start;

        $response->assertStatus(200);
        $response->assertViewIs('main.record.all');

        // Verify Page title and Navigation
        $response->assertSee('All Record');
        $response->assertSee('Combined Online');
        $response->assertSee('ACTIVE'); // Active badge on navigation Card 3

        // Verify Shell containers and components
        $response->assertSee('all-kpi-summary-container', false);
        $response->assertSee('all-classification-container', false);
        $response->assertDontSee('all-learning-table', false);

        // Verify Quick Filter inputs are NOT on landing page
        $response->assertDontSee('all-filter-employee', false);
        $response->assertDontSee('all-filter-title', false);
        $response->assertDontSee('all-filter-source', false);
        $response->assertDontSee('all-filter-classification', false);
        $response->assertDontSee('all-filter-status', false);
        $response->assertDontSee('all-filter-year', false);

        // Verify Custom entries select and Offline detail modal are NOT on landing page
        $response->assertDontSee('all-table-entries-select', false);
        $response->assertDontSee('offlineTrainingDetailModal', false);

        // Verify that initial shell load is lightweight and does not run UNION ALL
        $hasHeavyUnion = false;
        foreach ($queries as $sql) {
            if (str_contains(strtolower($sql), 'union') && str_contains(strtolower($sql), 'playlist_content_users')) {
                $hasHeavyUnion = true;
                break;
            }
        }
        $this->assertFalse($hasHeavyUnion, 'Initial GET /manage/record/all must not execute heavy aggregation queries.');
        $this->assertLessThan(2.0, $duration, 'Initial shell load should complete within 2 seconds.');
    }

    /**
     * Test All Record landing page DIV reordering:
     * 1. Banner (manage-record-hero)
     * 2. Statistic Cards (all-kpi-summary-container)
     * 3. Toolbar (record-section-header with All Record title and description)
     * 4. Navigation Cards (record-navigation-row)
     * 5. Main Content (all-classification-container)
     */
    public function test_all_record_landing_page_reordered_div_layout(): void
    {
        $response = $this->actingAs($this->admin)->get('/manage/record/all');
        $response->assertStatus(200);

        $content = $response->getContent();
        $body = substr($content, strpos($content, '<body'));

        $posHero = strpos($body, 'manage-record-hero');
        $posKpi = strpos($body, 'id="all-kpi-summary-container"');
        $posToolbar = strpos($body, 'record-section-header');
        $posNav = strpos($body, 'record-navigation-row');
        $posActionBar = strpos($body, 'id="btn-export-all-combined"');
        $posClassification = strpos($body, 'id="all-classification-container"');

        $this->assertNotFalse($posHero, 'Banner (manage-record-hero) must exist');
        $this->assertNotFalse($posKpi, 'Statistic cards (all-kpi-summary-container) must exist');
        $this->assertFalse($posToolbar, 'Header section (record-section-header) must be removed');
        $this->assertNotFalse($posNav, 'Navigation cards (record-navigation-row) must exist');
        $this->assertNotFalse($posActionBar, 'Action bar (btn-export-all-combined) must exist below navigation cards');
        $this->assertNotFalse($posClassification, 'Classification summary (all-classification-container) must exist');

        $this->assertLessThan($posKpi, $posHero, 'Banner must precede Statistic Cards');
        $this->assertLessThan($posNav, $posKpi, 'Statistic Cards must precede Navigation Cards');
        $this->assertLessThan($posActionBar, $posNav, 'Navigation Cards must precede Action Bar');
        $this->assertLessThan($posClassification, $posActionBar, 'Action Bar must precede Classification Summary');

        // Check active state for All Record
        $response->assertSee('All Record');
        $response->assertSee('record-nav-card--active');
    }

    /**
     * 2. Test Summary endpoint requires permission and returns valid contract & breakdown.
     */
    public function test_summary_endpoint_requires_permission_and_returns_valid_contract(): void
    {
        // Guest redirected
        $guestResponse = $this->get('/manage/record/all/ajax/summary');
        $this->assertTrue($guestResponse->isRedirect() || $guestResponse->getStatusCode() === 401);

        // Regular user forbidden
        if ($this->regularUser) {
            $unauthResponse = $this->actingAs($this->regularUser)->get('/manage/record/all/ajax/summary');
            $unauthResponse->assertStatus(403);
        }

        // Admin success
        $response = $this->actingAs($this->admin)->getJson('/manage/record/all/ajax/summary');
        $response->assertStatus(200);
        $response->assertJsonStructure([
            'success',
            'total_learning',
            'ongoing',
            'finished',
            'total_learning_hours',
            'completion_rate',
            'data' => [
                'total_learning',
                'ongoing',
                'finished',
                'total_learning_hours',
                'completion_rate',
                'online' => [
                    'total_learning',
                    'ongoing',
                    'finished',
                    'total_learning_hours',
                ],
                'offline' => [
                    'total_learning',
                    'ongoing',
                    'finished',
                    'total_learning_hours',
                ],
            ],
            'html',
        ]);

        $this->assertTrue($response->json('success'));

        $data = $response->json('data');
        // Total must equal online + offline
        $this->assertEquals(
            $data['online']['total_learning'] + $data['offline']['total_learning'],
            $data['total_learning']
        );
        $this->assertEquals(
            $data['online']['ongoing'] + $data['offline']['ongoing'],
            $data['ongoing']
        );
        $this->assertEquals(
            $data['online']['finished'] + $data['offline']['finished'],
            $data['finished']
        );
        $this->assertEquals(
            round($data['online']['total_learning_hours'] + $data['offline']['total_learning_hours'], 2),
            $data['total_learning_hours']
        );

        // Check HTML contains 4 standardized summary cards
        $html = $response->json('html');
        $this->assertStringContainsString('Total Learning Data', $html);
        $this->assertStringContainsString('Ongoing Course', $html);
        $this->assertStringContainsString('Finished Course', $html);
        $this->assertStringContainsString('Total Learning Hours', $html);
    }

    /**
     * 3. Test Classification endpoint requires permission and returns combined classification table.
     */
    public function test_classification_endpoint_requires_permission_and_returns_combined_rows(): void
    {
        // Guest redirected
        $guestResponse = $this->get('/manage/record/all/ajax/classification');
        $this->assertTrue($guestResponse->isRedirect() || $guestResponse->getStatusCode() === 401);

        // Regular user forbidden
        if ($this->regularUser) {
            $unauthResponse = $this->actingAs($this->regularUser)->get('/manage/record/all/ajax/classification');
            $unauthResponse->assertStatus(403);
        }

        // Admin success
        $response = $this->actingAs($this->admin)->getJson('/manage/record/all/ajax/classification');
        $response->assertStatus(200);
        $response->assertJsonStructure([
            'success',
            'data' => [
                '*' => [
                    'no',
                    'classification_name',
                    'slug',
                    'kp_owl',
                    'non_kp_owl',
                    'total_learning',
                    'learning_hours',
                    'ongoing',
                    'finished',
                ],
            ],
            'html',
        ]);

        $this->assertTrue($response->json('success'));
        $data = $response->json('data');
        $this->assertIsArray($data);
        $this->assertNotEmpty($data);

        // Verify arithmetic integrity on each row: total_learning = kp_owl + non_kp_owl
        foreach ($data as $row) {
            $this->assertEquals(
                $row['kp_owl'] + $row['non_kp_owl'],
                $row['total_learning'],
                "Row {$row['classification_name']} total_learning must equal kp_owl + non_kp_owl."
            );
        }

        // Test format=json query parameter returns data array without HTML
        $jsonResp = $this->actingAs($this->admin)->getJson('/manage/record/all/ajax/classification?format=json');
        $jsonResp->assertStatus(200);
        $jsonResp->assertJsonStructure(['success', 'data']);
        $this->assertArrayNotHasKey('html', $jsonResp->json());
    }

    /**
     * 4. Test DataTable endpoint requires permission and returns valid 13-column schema.
     */
    public function test_datatable_endpoint_requires_permission_and_returns_valid_structure(): void
    {
        // Regular user forbidden
        if ($this->regularUser) {
            $unauthResponse = $this->actingAs($this->regularUser)->getJson('/manage/record/all/datatable');
            $unauthResponse->assertStatus(403);
        }

        // Admin success
        $response = $this->actingAs($this->admin)->getJson('/manage/record/all/datatable?draw=1&length=10');
        $response->assertStatus(200);
        $response->assertJsonStructure([
            'draw',
            'recordsTotal',
            'recordsFiltered',
            'data' => [
                '*' => [
                    'DT_RowIndex',
                    'row_id',
                    'source',
                    'source_badge',
                    'employee_id',
                    'employee_name',
                    'employee',
                    'learning_title',
                    'type',
                    'classification',
                    'category',
                    'learning_hours',
                    'learning_hours_formatted',
                    'progress',
                    'progress_bar',
                    'status',
                    'status_badge',
                    'start_date',
                    'finish_date',
                    'finish_date_formatted',
                    'action',
                ],
            ],
        ]);

        $this->assertEquals(1, $response->json('draw'));
        $this->assertGreaterThan(0, $response->json('recordsTotal'));
        $this->assertLessThanOrEqual(10, count($response->json('data')));
    }

    /**
     * 5. CRITICAL TEST: Combined Learning Hours must strictly use participant credited hours (offline_training_users.learning_hours).
     * NEVER offline_trainings.learning_hours.
     */
    public function test_critical_learning_hours_uses_participant_credited_hours(): void
    {
        // Step 1: Record baseline summary
        $baselineResponse = $this->actingAs($this->admin)->getJson('/manage/record/all/ajax/summary');
        $baseline = $baselineResponse->json('data');
        $baseOnlineHours = (float) $baseline['online']['total_learning_hours'];
        $baseOfflineHours = (float) $baseline['offline']['total_learning_hours'];

        // Step 2: Create Offline training with planned hours = 10.0
        // Participant 1 has credited hours = 6.0 (e.g. partial/custom completion)
        // Participant 2 has credited hours = 2.5
        $this->createTestOfflineTraining(
            ['learning_hours' => 10.0],
            [
                ['learning_hours' => 6.0, 'progress' => 100],
                ['learning_hours' => 2.5, 'progress' => 50],
            ]
        );

        // Step 3: Fetch updated summary
        $updatedResponse = $this->actingAs($this->admin)->getJson('/manage/record/all/ajax/summary');
        $updated = $updatedResponse->json('data');

        $expectedNewOfflineHours = round($baseOfflineHours + 6.0 + 2.5, 2); // 8.5 added, NOT 20.0 (10.0 * 2)!
        $this->assertEquals($expectedNewOfflineHours, (float) $updated['offline']['total_learning_hours']);

        $expectedTotalHours = round($baseOnlineHours + $expectedNewOfflineHours, 2);
        $this->assertEquals($expectedTotalHours, (float) $updated['total_learning_hours']);
        $this->assertNotEquals(round($baseOfflineHours + 20.0, 2), (float) $updated['offline']['total_learning_hours']);
    }

    /**
     * 6. Test source filter: source=all, source=online, source=offline.
     */
    public function test_source_filter_filters_records_accurately(): void
    {
        $this->createTestOfflineTraining(
            ['course_name' => 'Offline Source Filter Test Training'],
            [['name' => 'Offline Source Filter User', 'progress' => 100]]
        );

        // 1. source=all: returns records from both
        $allResp = $this->actingAs($this->admin)->getJson('/manage/record/all/datatable?source=all&length=25');
        $allResp->assertStatus(200);
        $this->assertGreaterThan(0, $allResp->json('recordsTotal'));

        // 2. source=online: strictly online records
        $onlineResp = $this->actingAs($this->admin)->getJson('/manage/record/all/datatable?source=online&length=25');
        $onlineResp->assertStatus(200);
        $onlineData = $onlineResp->json('data');
        $this->assertNotEmpty($onlineData);
        foreach ($onlineData as $row) {
            $this->assertEquals('online', $row['source']);
            $this->assertStringStartsWith('online:', $row['row_id']);
            $this->assertStringContainsString('KP OWL', $row['source_badge']);
            $this->assertStringNotContainsString('Non KP OWL', $row['source_badge']);
        }

        // 3. source=offline: strictly offline records
        $offlineResp = $this->actingAs($this->admin)->getJson('/manage/record/all/datatable?source=offline&length=25');
        $offlineResp->assertStatus(200);
        $offlineData = $offlineResp->json('data');
        $this->assertNotEmpty($offlineData);
        foreach ($offlineData as $row) {
            $this->assertEquals('offline', $row['source']);
            $this->assertStringStartsWith('offline:', $row['row_id']);
            $this->assertStringContainsString('Non KP OWL', $row['source_badge']);
            $this->assertStringNotContainsString('class="badge" style="background-color: #e0f2fe;', $row['source_badge']);
        }
    }

    /**
     * 7. Test employee search filter across Online and Offline records.
     */
    public function test_employee_filter_across_sources(): void
    {
        $uniqueNip = 'NIP_TEST_' . rand(100000, 999999);
        $uniqueName = 'Unique Tester Name ' . rand(100, 999);

        $this->createTestOfflineTraining(
            ['course_name' => 'Employee Filter Offline Course'],
            [['employee_id' => $uniqueNip, 'name' => $uniqueName]]
        );

        // Search by NIP
        $respByNip = $this->actingAs($this->admin)->getJson("/manage/record/all/datatable?employee_id={$uniqueNip}");
        $respByNip->assertStatus(200);
        $this->assertEquals(1, $respByNip->json('recordsFiltered'));
        $this->assertEquals($uniqueNip, $respByNip->json('data.0.employee_id'));

        // Search by Name
        $respByName = $this->actingAs($this->admin)->getJson("/manage/record/all/datatable?employee={$uniqueName}");
        $respByName->assertStatus(200);
        $this->assertEquals(1, $respByName->json('recordsFiltered'));
        $this->assertStringContainsString($uniqueName, $respByName->json('data.0.employee_name'));
    }

    /**
     * 8. Test learning title filter across Online and Offline records.
     */
    public function test_learning_title_filter_across_sources(): void
    {
        $uniqueTitle = 'Unique Title ' . uniqid();

        $this->createTestOfflineTraining(
            ['course_name' => $uniqueTitle],
            [['progress' => 100]]
        );

        $resp = $this->actingAs($this->admin)->getJson("/manage/record/all/datatable?learning_title=" . urlencode($uniqueTitle));
        $resp->assertStatus(200);
        $this->assertEquals(1, $resp->json('recordsFiltered'));
        $this->assertStringContainsString($uniqueTitle, $resp->json('data.0.learning_title'));
    }

    /**
     * 9. Test classification filter across Online and Offline records.
     */
    public function test_classification_filter_across_sources(): void
    {
        $uniqueClass = 'Special Unique Class ' . uniqid();

        $this->createTestOfflineTraining(
            ['main_classification' => $uniqueClass],
            [['progress' => 100]]
        );

        $resp = $this->actingAs($this->admin)->getJson("/manage/record/all/datatable?classification=" . urlencode($uniqueClass));
        $resp->assertStatus(200);
        $this->assertEquals(1, $resp->json('recordsFiltered'));
        $this->assertEquals($uniqueClass, $resp->json('data.0.classification'));
    }

    /**
     * 10. Test status filter: finished, ongoing, not_started.
     */
    public function test_status_filter_works_properly(): void
    {
        $this->createTestOfflineTraining(
            ['course_name' => 'Status Filter Test Course'],
            [
                ['progress' => 100, 'employee_id' => 'EMP_STAT_FIN'],
                ['progress' => 45,  'employee_id' => 'EMP_STAT_ONG'],
                ['progress' => 0,   'employee_id' => 'EMP_STAT_NOT'],
            ]
        );

        // Filter finished
        $finResp = $this->actingAs($this->admin)->getJson('/manage/record/all/datatable?source=offline&status=finished');
        $finResp->assertStatus(200);
        foreach ($finResp->json('data') as $row) {
            $this->assertEquals('Finished', $row['status']);
        }

        // Filter ongoing
        $ongResp = $this->actingAs($this->admin)->getJson('/manage/record/all/datatable?source=offline&status=ongoing');
        $ongResp->assertStatus(200);
        foreach ($ongResp->json('data') as $row) {
            $this->assertEquals('Ongoing', $row['status']);
        }

        // Filter not_started
        $notResp = $this->actingAs($this->admin)->getJson('/manage/record/all/datatable?source=offline&status=not_started');
        $notResp->assertStatus(200);
        foreach ($notResp->json('data') as $row) {
            $this->assertEquals('Not Started', $row['status']);
        }
    }

    /**
     * 11. Test year filter matches created dates.
     */
    public function test_year_filter_matches_offline_and_online(): void
    {
        $this->createTestOfflineTraining(
            ['start_date' => '2025-06-15', 'finish_date' => '2025-06-16'],
            [['progress' => 100]]
        );

        // Year 2025 offline filter
        $resp2025 = $this->actingAs($this->admin)->getJson('/manage/record/all/datatable?source=offline&year=2025');
        $resp2025->assertStatus(200);
        $this->assertGreaterThanOrEqual(1, $resp2025->json('recordsFiltered'));

        // Non-existent year 1995 filter
        $resp1995 = $this->actingAs($this->admin)->getJson('/manage/record/all/datatable?source=offline&year=1995');
        $resp1995->assertStatus(200);
        $this->assertEquals(0, $resp1995->json('recordsFiltered'));
    }

    /**
     * 12. Test source-aware action column: detail button for offline, view link for online.
     */
    public function test_source_aware_action_buttons(): void
    {
        $training = $this->createTestOfflineTraining(
            ['course_name' => 'Action Button Test Course'],
            [['name' => 'Action Button Participant', 'progress' => 100]]
        );
        $participant = $training->participants()->first();

        // 1. Offline row action
        $offlineResp = $this->actingAs($this->admin)->getJson("/manage/record/all/datatable?source=offline&employee=" . urlencode('Action Button Participant'));
        $offlineResp->assertStatus(200);
        $offlineAction = $offlineResp->json('data.0.action');
        $this->assertStringContainsString('btn-view-offline-detail', $offlineAction);
        $this->assertStringContainsString('data-id="' . $participant->id . '"', $offlineAction);
        $this->assertStringContainsString('Detail', $offlineAction);

        // 2. Online row action
        $onlineResp = $this->actingAs($this->admin)->getJson('/manage/record/all/datatable?source=online&length=1');
        $onlineResp->assertStatus(200);
        $onlineAction = $onlineResp->json('data.0.action');
        if (str_contains($onlineAction, 'View')) {
            $this->assertStringContainsString('btn-view-online-detail', $onlineAction);
            $this->assertStringNotContainsString('btn-view-offline-detail', $onlineAction);
        }
    }

    /**
     * 13. Test soft-deleted offline records are strictly excluded from summary and DataTable.
     */
    public function test_soft_deleted_offline_records_are_excluded(): void
    {
        $training = $this->createTestOfflineTraining(
            ['course_name' => 'Soft Delete Test Course'],
            [['name' => 'Soft Delete Participant', 'progress' => 100]]
        );

        // Verify participant is in datatable
        $beforeResp = $this->actingAs($this->admin)->getJson('/manage/record/all/datatable?source=offline&employee=Soft+Delete+Participant');
        $this->assertEquals(1, $beforeResp->json('recordsFiltered'));

        // Soft delete training and participant
        $training->participants()->delete();
        $training->delete();

        // Verify participant is excluded
        $afterResp = $this->actingAs($this->admin)->getJson('/manage/record/all/datatable?source=offline&employee=Soft+Delete+Participant');
        $this->assertEquals(0, $afterResp->json('recordsFiltered'));
    }

    /**
     * 14. Test read-only guarantee: zero DB insertions/updates when querying All KP OWL endpoints.
     */
    public function test_read_only_guarantee_no_db_modifications(): void
    {
        $countsBefore = [
            'content_users'          => DB::table('content_users')->count(),
            'playlist_content_users' => DB::table('playlist_content_users')->count(),
            'offline_trainings'      => DB::table('offline_trainings')->count(),
            'offline_training_users' => DB::table('offline_training_users')->count(),
            'users'                  => DB::table('users')->count(),
        ];

        // Call shell
        $this->actingAs($this->admin)->get('/manage/record/all');

        // Call summary
        $this->actingAs($this->admin)->getJson('/manage/record/all/ajax/summary');

        // Call classification
        $this->actingAs($this->admin)->getJson('/manage/record/all/ajax/classification');

        // Call datatable
        $this->actingAs($this->admin)->getJson('/manage/record/all/datatable?source=all&length=10');

        $countsAfter = [
            'content_users'          => DB::table('content_users')->count(),
            'playlist_content_users' => DB::table('playlist_content_users')->count(),
            'offline_trainings'      => DB::table('offline_trainings')->count(),
            'offline_training_users' => DB::table('offline_training_users')->count(),
            'users'                  => DB::table('users')->count(),
        ];

        $this->assertEquals($countsBefore, $countsAfter, 'All KP OWL endpoints must be strictly read-only.');
    }

    /**
     * 15. Test unclassified mapping: trainings with null classification map to Unclassified at the end.
     */
    public function test_unclassified_offline_training_maps_properly(): void
    {
        $this->createTestOfflineTraining(
            ['main_classification' => null, 'course_name' => 'Unclassified Offline Course'],
            [['progress' => 100, 'name' => 'Unclass Participant']]
        );

        // Classification summary
        $resp = $this->actingAs($this->admin)->getJson('/manage/record/all/ajax/classification');
        $resp->assertStatus(200);
        $data = $resp->json('data');

        $lastItem = end($data);
        $this->assertEquals('Unclassified', $lastItem['classification_name'], 'Unclassified must appear at the end.');
        $this->assertGreaterThanOrEqual(1, $lastItem['non_kp_owl']);

        // Datatable
        $dtResp = $this->actingAs($this->admin)->getJson('/manage/record/all/datatable?source=offline&classification=Unclassified');
        $dtResp->assertStatus(200);
        $this->assertGreaterThanOrEqual(1, $dtResp->json('recordsFiltered'));
    }

    /**
     * 16. Test null or zero learning hours in participant record handled safely without error.
     */
    public function test_null_participant_learning_hours_handled_safely(): void
    {
        $this->createTestOfflineTraining(
            ['course_name' => 'Null Hours Offline Course'],
            [['learning_hours' => null, 'progress' => 50, 'name' => 'Null Hours Participant']]
        );

        $resp = $this->actingAs($this->admin)->getJson('/manage/record/all/datatable?source=offline&employee=Null+Hours+Participant');
        $resp->assertStatus(200);
        $this->assertEquals(1, $resp->json('recordsFiltered'));
        $this->assertEquals('0.00', $resp->json('data.0.learning_hours_formatted'));
    }

    /**
     * 17. Test regression: verify KP OWL and Non KP OWL pages remain intact.
     */
    public function test_regression_online_and_offline_pages_remain_intact(): void
    {
        // Online landing
        $onlineResp = $this->actingAs($this->admin)->get('/manage/record/online');
        $onlineResp->assertStatus(200);

        // Offline landing
        $offlineResp = $this->actingAs($this->admin)->get('/manage/record/offline');
        $offlineResp->assertStatus(200);

        // All KP OWL navigation link active
        $allResp = $this->actingAs($this->admin)->get('/manage/record/all');
        $allResp->assertStatus(200);
        $allResp->assertSee(route('manage.record.all'));
    }

    /**
     * 18. Test Classification detail page requires permission.
     */
    public function test_all_record_classification_detail_requires_permission(): void
    {
        // Guest redirected
        $guestResponse = $this->get('/manage/record/all/classification/leadership');
        $this->assertTrue($guestResponse->isRedirect() || $guestResponse->getStatusCode() === 401);

        // Regular user forbidden
        if ($this->regularUser) {
            $unauthResponse = $this->actingAs($this->regularUser)->get('/manage/record/all/classification/leadership');
            $unauthResponse->assertStatus(403);
        }

        // Admin success
        $response = $this->actingAs($this->admin)->get('/manage/record/all/classification/leadership');
        $response->assertStatus(200);
    }

    /**
     * 19. Test Classification detail page renders proper header, back button, and quick filter without classification select.
     */
    public function test_all_record_classification_detail_renders_proper_header_and_elements_for_valid_slug(): void
    {
        $response = $this->actingAs($this->admin)->get('/manage/record/all/classification/leadership');
        $response->assertStatus(200);

        // Verify Header and Back Link
        $response->assertSee('Classification: Leadership');
        $response->assertSee('Back to All KP OWL');
        $response->assertSee(route('manage.record.all'));

        // Verify Combined Classification DataTable
        $response->assertSee('all-classification-learning-table', false);

        // Verify Quick Filter inputs (must NOT have classification dropdown)
        $response->assertSee('all-filter-employee', false);
        $response->assertSee('all-filter-title', false);
        $response->assertSee('all-filter-source', false);
        $response->assertSee('all-filter-status', false);
        $response->assertSee('all-filter-year', false);
        $response->assertDontSee('all-filter-classification', false);

        // Verify custom entries selector and offline detail modal
        $response->assertSee('all-table-entries-select', false);
        $response->assertSee('offlineTrainingDetailModal', false);
    }

    /**
     * 20. Test invalid classification slug returns 404.
     */
    public function test_all_record_classification_detail_returns_404_for_invalid_slug(): void
    {
        $resp = $this->actingAs($this->admin)->get('/manage/record/all/classification/completely-invalid-slug-xyz-999');
        $resp->assertStatus(404);

        $dtResp = $this->actingAs($this->admin)->getJson('/manage/record/all/classification/completely-invalid-slug-xyz-999/datatable');
        $dtResp->assertStatus(404);
    }

    /**
     * 21. Test Unclassified slug renders 200 and loads unclassified view.
     */
    public function test_all_record_classification_detail_handles_unclassified_slug(): void
    {
        $response = $this->actingAs($this->admin)->get('/manage/record/all/classification/unclassified');
        $response->assertStatus(200);
        $response->assertSee('Classification: Unclassified');
        $response->assertSee('all-classification-learning-table', false);
    }

    /**
     * 22. Test classification detail datatable restricts rows to selected classification across online and offline.
     */
    public function test_classification_datatable_restricts_records_to_selected_classification(): void
    {
        // Create 1 training in Leadership and 1 training in Technical
        $this->createTestOfflineTraining(
            ['course_name' => 'Leadership Exclusive Course', 'main_classification' => 'Leadership'],
            [['learning_hours' => 5.0, 'progress' => 100, 'name' => 'Participant Leadership Alpha']]
        );

        $this->createTestOfflineTraining(
            ['course_name' => 'Technical Exclusive Course', 'main_classification' => 'Technical'],
            [['learning_hours' => 6.0, 'progress' => 100, 'name' => 'Participant Technical Beta']]
        );

        // Query Leadership datatable
        $leadResp = $this->actingAs($this->admin)->getJson('/manage/record/all/classification/leadership/datatable?source=offline');
        $leadResp->assertStatus(200);

        $leadData = $leadResp->json('data');
        $this->assertNotEmpty($leadData);
        foreach ($leadData as $row) {
            $this->assertEquals('Leadership', $row['classification']);
            $this->assertStringNotContainsString('Participant Technical Beta', $row['employee']);
        }

        // Query Technical datatable
        $techResp = $this->actingAs($this->admin)->getJson('/manage/record/all/classification/technical/datatable?source=offline');
        $techResp->assertStatus(200);

        $techData = $techResp->json('data');
        $this->assertNotEmpty($techData);
        foreach ($techData as $row) {
            $this->assertEquals('Technical', $row['classification']);
            $this->assertStringNotContainsString('Participant Leadership Alpha', $row['employee']);
        }
    }

    /**
     * 23. Test classification datatable correctly filters unclassified records.
     */
    public function test_classification_datatable_handles_unclassified_records(): void
    {
        $this->createTestOfflineTraining(
            ['course_name' => 'Unclassified Exclusive Course', 'main_classification' => null],
            [['learning_hours' => 4.0, 'progress' => 100, 'name' => 'Participant Unclassified Delta']]
        );

        $resp = $this->actingAs($this->admin)->getJson('/manage/record/all/classification/unclassified/datatable?source=offline');
        $resp->assertStatus(200);

        $data = $resp->json('data');
        $this->assertNotEmpty($data);
        foreach ($data as $row) {
            $this->assertEquals('Unclassified', $row['classification']);
        }
    }

    /**
     * 24. Test classification summary contains clickable links targeting the dedicated classification page.
     */
    public function test_classification_summary_contains_clickable_links_to_detail_routes(): void
    {
        $response = $this->actingAs($this->admin)->get('/manage/record/all/ajax/classification');
        $response->assertStatus(200);

        $html = $response->json('html');
        $this->assertStringContainsString('all-classification-link', $html);
        $this->assertStringContainsString('/manage/record/all/classification/', $html);
    }

    /**
     * 25. Test classification table has exactly 47 superset columns combining Online and Offline fields.
     */
    public function test_classification_table_has_exact_47_superset_columns(): void
    {
        $response = $this->actingAs($this->admin)->get('/manage/record/all/classification/leadership');
        $response->assertStatus(200);

        $content = $response->getContent();

        // Extract <thead> content from #all-classification-learning-table
        preg_match('/<table[^>]*id="all-classification-learning-table"[^>]*>.*?<thead[^>]*>(.*?)<\/thead>/s', $content, $theadMatch);
        $this->assertNotEmpty($theadMatch, 'Table header must exist');

        $thead = $theadMatch[1];

        // Count <th> elements in main table header
        preg_match_all('/<th\b[^>]*>(.*?)<\/th>/si', $thead, $thMatches);
        $headers = array_map(fn ($h) => trim(strip_tags($h)), $thMatches[1]);

        $this->assertCount(47, $headers, 'Main table must have exactly 47 superset columns');

        $expectedHeaders = [
            'No',
            'Source',
            'ID',
            'Employee',
            'Status',
            'Grade',
            'Job Position',
            'Team',
            'Department',
            'Company',
            'Learning Title',
            'Sub Title',
            'Type',
            'Category',
            'Level',
            'Batch',
            'Main Classification',
            'Sub Classification',
            'Corporate KPI',
            'Sub Corporate KPI',
            'Delivery Method',
            'Sub Delivery Method',
            'Provider',
            'Sub Provider',
            'Provider Name',
            'Venue',
            'Instructor',
            'Duration',
            'Training Hours',
            'Start Date',
            'Finish Date',
            'Participant Hours',
            'Progress',
            'Assessment Score',
            'Certificate',
            'Attendance',
            'Attendance Reason',
            'Bond Status',
            'Bond End Date',
            'Bond Amount',
            'L1 Program',
            'L2 Numerical',
            'L2 Verbal',
            'L3 Numerical',
            'L3 Verbal',
            'PIC',
            'Action',
        ];

        $this->assertEquals($expectedHeaders, $headers, 'Main table headers must match the 47 superset columns exactly');
        $this->assertContains('Type', $headers, 'TYPE column must now be present in superset table');
        $this->assertContains('Category', $headers, 'CATEGORY column must now be present in superset table');
    }

    /**
     * 26. Test learning hours uses canonical participant credited hours, never catalog duration.
     */
    public function test_learning_hours_uses_canonical_credited_hours_never_duration(): void
    {
        // 1. Offline participant: 6 credited hours despite training having 8 hours (28800s) duration
        $uniqueName = 'LH Credited User ' . uniqid();
        $this->createTestOfflineTraining(
            ['duration_seconds' => 28800, 'learning_hours' => 8.0],
            [['name' => $uniqueName, 'learning_hours' => 6.0, 'progress' => 75]]
        );

        $offResp = $this->actingAs($this->admin)->getJson('/manage/record/all/datatable?source=offline&employee=' . urlencode($uniqueName));
        $offResp->assertStatus(200);
        $this->assertEquals(1, $offResp->json('recordsFiltered'));

        $row = $offResp->json('data.0');
        $this->assertEquals(6.0, (float) $row['learning_hours'], 'Offline learning_hours must come from participant learning_hours');
        $this->assertEquals('6.00', $row['learning_hours_formatted']);

        // 2. Online Content participant: must strictly use content_users.learning_hours
        $sampleCu = DB::table('content_users as cu')
            ->join('content as c', 'c.id', '=', 'cu.content_id')
            ->join('users as u', 'u.id', '=', 'cu.user_id')
            ->whereNotNull('c.duration')
            ->where('cu.learning_hours', '>', 0)
            ->whereRaw('ABS(cu.learning_hours - (TIME_TO_SEC(c.duration) / 3600)) > 0.01')
            ->select('cu.id', 'u.id_emp', 'cu.learning_hours', 'c.duration')
            ->first();

        if ($sampleCu) {
            $onResp = $this->actingAs($this->admin)->getJson('/manage/record/all/datatable?source=online&employee_id=' . urlencode($sampleCu->id_emp));
            $onResp->assertStatus(200);

            $matchedOnline = collect($onResp->json('data'))->firstWhere('row_id', 'online:content:' . $sampleCu->id);
            if ($matchedOnline) {
                $expectedHours = round((float) $sampleCu->learning_hours, 2);
                $this->assertEquals($expectedHours, (float) $matchedOnline['learning_hours'], 'Online learning_hours must strictly match content_users.learning_hours');
            }
        }
    }

    /**
     * 27. Test finish date is NULL for ongoing and not started records, and completed_at/finish_date for finished records.
     */
    public function test_finish_date_null_for_ongoing_and_not_started_and_populated_when_finished(): void
    {
        $uniqueCourse = 'Finish Date Course ' . uniqid();
        $this->createTestOfflineTraining(
            ['course_name' => $uniqueCourse, 'finish_date' => '2026-08-15'],
            [
                ['name' => 'FD Finished Participant', 'progress' => 100],
                ['name' => 'FD Ongoing Participant',  'progress' => 50],
                ['name' => 'FD Not Started Participant', 'progress' => 0],
            ]
        );

        $resp = $this->actingAs($this->admin)->getJson('/manage/record/all/datatable?source=offline&learning_title=' . urlencode($uniqueCourse));
        $resp->assertStatus(200);

        $data = collect($resp->json('data'));

        $finRow = $data->first(fn($r) => str_contains($r['employee_name'] ?? '', 'FD Finished Participant'));
        $this->assertNotNull($finRow);
        $this->assertEquals('Finished', $finRow['status']);
        $this->assertEquals('2026-08-15', $finRow['finish_date']);
        $this->assertEquals('15 Aug 2026', $finRow['finish_date_formatted']);

        $ongRow = $data->first(fn($r) => str_contains($r['employee_name'] ?? '', 'FD Ongoing Participant'));
        $this->assertNotNull($ongRow);
        $this->assertEquals('Ongoing', $ongRow['status']);
        $this->assertNull($ongRow['finish_date'], 'Ongoing record must have NULL finish_date');
        $this->assertEquals('-', $ongRow['finish_date_formatted'], 'Ongoing record must display "-" for finish_date');

        $notRow = $data->first(fn($r) => str_contains($r['employee_name'] ?? '', 'FD Not Started Participant'));
        $this->assertNotNull($notRow);
        $this->assertEquals('Not Started', $notRow['status']);
        $this->assertNull($notRow['finish_date'], 'Not Started record must have NULL finish_date');
        $this->assertEquals('-', $notRow['finish_date_formatted'], 'Not Started record must display "-" for finish_date');
    }

    /**
     * 28. Test superset columns return NULL for Online records and populate actual values for Offline records.
     */
    public function test_superset_columns_online_null_contract_and_offline_fields(): void
    {
        // 1. Create Offline Training with rich superset values
        $uniqueCourse = 'Superset Full Test Course ' . uniqid();
        $uniqueParticipant = 'Superset Offline User ' . uniqid();

        $this->createTestOfflineTraining([
            'course_name'           => $uniqueCourse,
            'sub_course_name'       => 'Module Superset',
            'type'                  => 'In-House',
            'category'              => 'TEST_COMBINED_CAT',
            'level'                 => 'Intermediate',
            'batch'                 => 'Batch 5',
            'main_classification'   => 'General Competency',
            'sub_classification'    => 'Electrical Safety',
            'counted_corporate_kpi' => 1,
            'sub_corporate_kpi'     => 'Safety Excellence',
            'delivery_method'       => 'Classroom',
            'sub_delivery_method'   => 'Workshop',
            'provider'              => 'External',
            'sub_provider'          => 'Vendor',
            'provider_name'         => 'PT Provider Indonesia',
            'venue'                 => 'Auditorium 1',
            'instructor'            => 'Ir. Superset',
            'duration_seconds'      => 14400,
            'learning_hours'        => 4.0,
            'start_date'            => '2026-08-10',
            'finish_date'           => '2026-08-10',
        ], [
            [
                'name'              => $uniqueParticipant,
                'grade'             => '4A',
                'job_position'      => 'Senior Technician',
                'team'              => 'Alpha Team',
                'department'        => 'Maintenance',
                'company'           => 'TEST_COMBINED_CORP',
                'learning_hours'    => 4.0,
                'progress'          => 100,
                'assessment_score'  => 87.5,
                'certificate'       => 'Yes',
                'attendance'        => 1,
                'attendance_reason' => 'Present full session',
                'bond_status'       => 'Yes',
                'bond_end_date'     => '2027-08-10',
                'bond_amount'       => 5000000,
                'l1_program'        => 'Very Good',
                'l2_numerical'      => 87.5,
                'l2_verbal'         => 'Passed',
                'l3_numerical'      => 90.0,
                'l3_verbal'         => 'Applied well',
                'pic'               => 'Training PIC',
            ]
        ]);

        $offResp = $this->actingAs($this->admin)->getJson('/manage/record/all/datatable?source=offline&learning_title=' . urlencode($uniqueCourse));
        $offResp->assertStatus(200);

        $offRow = collect($offResp->json('data'))->first(fn($r) => str_contains($r['employee_name'] ?? '', $uniqueParticipant));
        $this->assertNotNull($offRow);

        // Verify Offline fields populated
        $this->assertEquals('Non KP OWL', strip_tags($offRow['source_badge']));
        $this->assertEquals('Finished', $offRow['status']);
        $this->assertEquals('4A', $offRow['grade']);
        $this->assertEquals('Senior Technician', $offRow['job_position']);
        $this->assertEquals('Alpha Team', $offRow['team']);
        $this->assertEquals('Maintenance', $offRow['department']);
        $this->assertEquals('TEST_COMBINED_CORP', $offRow['company']);
        $this->assertStringContainsString($uniqueCourse, $offRow['learning_title']);
        $this->assertEquals('Module Superset', $offRow['sub_course_name']);
        $this->assertEquals('In-House', $offRow['type']);
        $this->assertEquals('TEST_COMBINED_CAT', $offRow['category']);
        $this->assertEquals('Intermediate', $offRow['level']);
        $this->assertEquals('Batch 5', $offRow['batch']);
        $this->assertEquals('Electrical Safety', $offRow['sub_classification']);
        $this->assertEquals('Yes', strip_tags($offRow['kpi_badge']));
        $this->assertEquals('Safety Excellence', $offRow['sub_corporate_kpi']);
        $this->assertEquals('Classroom', $offRow['delivery_method']);
        $this->assertEquals('Workshop', $offRow['sub_delivery_method']);
        $this->assertEquals('External', $offRow['provider']);
        $this->assertEquals('Vendor', $offRow['sub_provider']);
        $this->assertEquals('PT Provider Indonesia', $offRow['provider_name']);
        $this->assertEquals('Auditorium 1', $offRow['venue']);
        $this->assertEquals('Ir. Superset', $offRow['instructor']);
        $this->assertEquals('04:00:00', $offRow['duration_formatted']);
        $this->assertEquals('4.00', $offRow['training_hours_formatted']);
        $this->assertEquals('10 Aug 2026', $offRow['start_date_formatted']);
        $this->assertEquals('10 Aug 2026', $offRow['finish_date_formatted']);
        $this->assertEquals('4.00', $offRow['learning_hours_formatted']);
        $this->assertEquals('87.5', $offRow['assessment_score_formatted']);
        $this->assertStringContainsString('Available', $offRow['certificate_badge']);
        $this->assertEquals('Present', strip_tags($offRow['attendance_badge']));
        $this->assertEquals('Present full session', $offRow['attendance_reason']);
        $this->assertEquals('Yes', $offRow['bond_status']);
        $this->assertEquals('10 Aug 2027', $offRow['bond_end_date_formatted']);
        $this->assertEquals('IDR 5.000.000', $offRow['bond_amount_formatted']);
        $this->assertEquals('Very Good', $offRow['l1_program']);
        $this->assertEquals('87.5', $offRow['l2_numerical_formatted']);
        $this->assertEquals('Passed', $offRow['l2_verbal']);
        $this->assertEquals('90.0', $offRow['l3_numerical_formatted']);
        $this->assertEquals('Applied well', $offRow['l3_verbal']);
        $this->assertEquals('Training PIC', $offRow['pic']);

        // 2. Verify Online Contract: NULL values must not invent fake defaults
        $onResp = $this->actingAs($this->admin)->getJson('/manage/record/all/datatable?source=online&length=1');
        $onResp->assertStatus(200);

        if (!empty($onResp->json('data'))) {
            $onRow = $onResp->json('data.0');
            $this->assertEquals('KP OWL', strip_tags($onRow['source_badge']));

            // Critical corrections verification: NO FAKE VALUES
            $this->assertNull($onRow['delivery_method'], 'Online delivery_method must be NULL (no fake E-Learning value)');
            $this->assertNull($onRow['provider'], 'Online provider must be NULL (no fake Internal value)');
            $this->assertNull($onRow['provider_name'], 'Online provider_name must be NULL (no fake PT Krakatau Posco value)');
            $this->assertNull($onRow['start_date'], 'Online start_date must be NULL (no mapping from created_at)');
            $this->assertEquals('-', $onRow['start_date_formatted'], 'Online start_date_formatted must render as -');

            // Source-specific Offline fields must be NULL for Online
            $this->assertNull($onRow['grade'], 'Online grade must be NULL');
            $this->assertNull($onRow['job_position'], 'Online job_position must be NULL');
            $this->assertNull($onRow['sub_course_name'], 'Online sub_course_name must be NULL');
            $this->assertNull($onRow['level'], 'Online level must be NULL');
            $this->assertNull($onRow['batch'], 'Online batch must be NULL');
            $this->assertNull($onRow['sub_classification'], 'Online sub_classification must be NULL');
            $this->assertNull($onRow['counted_corporate_kpi'], 'Online counted_corporate_kpi must be NULL');
            $this->assertEquals('<span class="text-muted">-</span>', $onRow['kpi_badge']);
            $this->assertNull($onRow['assessment_score'], 'Online assessment_score must be NULL');
            $this->assertNull($onRow['certificate'], 'Online certificate must be NULL');
            $this->assertNull($onRow['attendance'], 'Online attendance must be NULL');
            $this->assertEquals('<span class="text-muted">-</span>', $onRow['attendance_badge']);
            $this->assertNull($onRow['bond_status'], 'Online bond_status must be NULL');
            $this->assertNull($onRow['l1_program'], 'Online l1_program must be NULL');
            $this->assertNull($onRow['l2_numerical'], 'Online l2_numerical must be NULL');
            $this->assertNull($onRow['l3_numerical'], 'Online l3_numerical must be NULL');
            $this->assertNull($onRow['pic'], 'Online pic must be NULL');
        }
    }

    /**
     * 29. Test Classification page renders More Filters modal, inputs, and download button.
     */
    public function test_classification_page_renders_more_filters_modal_and_download_button(): void
    {
        $response = $this->actingAs($this->admin)->get('/manage/record/all/classification/leadership');
        $response->assertStatus(200);
        $response->assertViewIs('main.record.all.classification');

        // Check More Filters button & badge
        $response->assertSee('id="btn-all-more-filters"', false);
        $response->assertSee('id="activeFilterBadge"', false);

        // Check Download dropdown button & format options
        $response->assertSee('id="btn-all-download"', false);
        $response->assertSee('data-format="xlsx"', false);
        $response->assertSee('data-format="csv"', false);
        $response->assertSee('data-format="pdf"', false);
        $response->assertSee('id="pdfLimitWarningModal"', false);

        // Check Modal container & inputs
        $response->assertSee('id="moreAllFiltersModal"', false);
        $response->assertSee('id="modal-from-date"', false);
        $response->assertSee('id="modal-to-date"', false);
        $response->assertSee('name="modal_progress_range"', false);
        $response->assertSee('name="modal_learning_hours"', false);
        $response->assertSee('id="modal-department"', false);
        $response->assertSee('id="modal-company"', false);
        $response->assertSee('id="btn-modal-apply"', false);
        $response->assertSee('id="btn-modal-reset"', false);
    }

    /**
     * 30. Test Classification Datatable supports advanced modal filters.
     */
    public function test_classification_datatable_supports_advanced_modal_filters(): void
    {
        // Create 3 distinct records
        $uniqueA = 'Participant A ' . uniqid();
        $uniqueB = 'Participant B ' . uniqid();
        $uniqueC = 'Participant C ' . uniqid();

        $training1 = $this->createTestOfflineTraining([
            'course_name'         => 'Test Advanced Filters Course 1',
            'main_classification' => 'Leadership',
            'finish_date'         => '2026-05-15',
        ], [
            [
                'name'           => $uniqueA,
                'department'     => 'FILTER_TEST_DEPT_A',
                'company'        => 'FILTER_TEST_COMP_A',
                'progress'       => 100,
                'learning_hours' => 1.5,
            ]
        ]);

        $training2 = $this->createTestOfflineTraining([
            'course_name'         => 'Test Advanced Filters Course 2',
            'main_classification' => 'Leadership',
            'finish_date'         => '2026-06-20',
        ], [
            [
                'name'           => $uniqueB,
                'department'     => 'FILTER_TEST_DEPT_B',
                'company'        => 'FILTER_TEST_COMP_B',
                'progress'       => 50,
                'learning_hours' => 3.0,
            ]
        ]);

        $training3 = $this->createTestOfflineTraining([
            'course_name'         => 'Test Advanced Filters Course 3',
            'main_classification' => 'Leadership',
            'finish_date'         => '2026-07-25',
        ], [
            [
                'name'           => $uniqueC,
                'department'     => 'FILTER_TEST_DEPT_A',
                'company'        => 'FILTER_TEST_COMP_B',
                'progress'       => 0,
                'learning_hours' => 0.0,
            ]
        ]);

        // Filter 1: Date Range matching only training 2
        $respDate = $this->actingAs($this->admin)->getJson('/manage/record/all/classification/leadership/datatable?from_date=2026-06-01&to_date=2026-06-30&source=offline');
        $respDate->assertStatus(200);
        $namesDate = collect($respDate->json('data'))->pluck('employee_name')->map(fn($n) => strip_tags($n))->all();
        $this->assertContains($uniqueB, $namesDate);
        $this->assertNotContains($uniqueA, $namesDate);
        $this->assertNotContains($uniqueC, $namesDate);

        // Filter 2: Progress Range 100 matching only training 1
        $respProg = $this->actingAs($this->admin)->getJson('/manage/record/all/classification/leadership/datatable?progress_range=100&source=offline');
        $respProg->assertStatus(200);
        $namesProg = collect($respProg->json('data'))->pluck('employee_name')->map(fn($n) => strip_tags($n))->all();
        $this->assertContains($uniqueA, $namesProg);
        $this->assertNotContains($uniqueB, $namesProg);
        $this->assertNotContains($uniqueC, $namesProg);

        // Filter 3: Progress Range 26-50 matching training 2
        $respProg50 = $this->actingAs($this->admin)->getJson('/manage/record/all/classification/leadership/datatable?progress_range=26-50&source=offline');
        $respProg50->assertStatus(200);
        $namesProg50 = collect($respProg50->json('data'))->pluck('employee_name')->map(fn($n) => strip_tags($n))->all();
        $this->assertContains($uniqueB, $namesProg50);
        $this->assertNotContains($uniqueA, $namesProg50);

        // Filter 4: Learning Hours Range 1-2 matching training 1 (1.5 hrs)
        $respHours = $this->actingAs($this->admin)->getJson('/manage/record/all/classification/leadership/datatable?learning_hours_range=1-2&source=offline');
        $respHours->assertStatus(200);
        $namesHours = collect($respHours->json('data'))->pluck('employee_name')->map(fn($n) => strip_tags($n))->all();
        $this->assertContains($uniqueA, $namesHours);
        $this->assertNotContains($uniqueB, $namesHours);

        // Filter 5: Department FILTER_TEST_DEPT_A matching training 1 and 3
        $respDept = $this->actingAs($this->admin)->getJson('/manage/record/all/classification/leadership/datatable?department=' . urlencode('FILTER_TEST_DEPT_A') . '&source=offline');
        $respDept->assertStatus(200);
        $namesDept = collect($respDept->json('data'))->pluck('employee_name')->map(fn($n) => strip_tags($n))->all();
        $this->assertContains($uniqueA, $namesDept);
        $this->assertContains($uniqueC, $namesDept);
        $this->assertNotContains($uniqueB, $namesDept);

        // Filter 6: Company FILTER_TEST_COMP_B matching training 2 and 3
        $respComp = $this->actingAs($this->admin)->getJson('/manage/record/all/classification/leadership/datatable?company=' . urlencode('FILTER_TEST_COMP_B') . '&source=offline');
        $respComp->assertStatus(200);
        $namesComp = collect($respComp->json('data'))->pluck('employee_name')->map(fn($n) => strip_tags($n))->all();
        $this->assertContains($uniqueB, $namesComp);
        $this->assertContains($uniqueC, $namesComp);
        $this->assertNotContains($uniqueA, $namesComp);
    }

    /**
     * 31. Test Classification download endpoint requires auth and exports Excel with filters.
     */
    public function test_classification_download_endpoint_requires_auth_and_exports_excel(): void
    {
        // Guest redirected or 401
        $guestResp = $this->get('/manage/record/all/classification/leadership/download');
        $this->assertTrue($guestResp->isRedirect() || $guestResp->getStatusCode() === 401);

        // Regular user 403
        if ($this->regularUser) {
            $unauthResp = $this->actingAs($this->regularUser)->get('/manage/record/all/classification/leadership/download');
            $unauthResp->assertStatus(403);
        }

        // Admin download via dedicated route
        $adminResp = $this->actingAs($this->admin)->get('/manage/record/all/classification/leadership/download?source=offline');
        $adminResp->assertStatus(200);
        $this->assertStringContainsString('spreadsheetml.sheet', (string) $adminResp->headers->get('content-type'));
        $this->assertStringContainsString('leadership', (string) $adminResp->headers->get('content-disposition'));

        // Admin download via ?action=download query parameter
        $actionResp = $this->actingAs($this->admin)->get('/manage/record/all/classification/leadership?action=download&source=offline');
        $actionResp->assertStatus(200);
        $this->assertStringContainsString('spreadsheetml.sheet', (string) $actionResp->headers->get('content-type'));
    }

    /**
     * 32. Test strict Filter Parity between DataTable count and Export Query count.
     */
    public function test_filter_parity_between_datatable_and_export_query(): void
    {
        $uniqueDept = 'PARITY_DEPT_' . uniqid();
        $uniqueCourse = 'Parity Course ' . uniqid();

        $this->createTestOfflineTraining([
            'course_name'         => $uniqueCourse,
            'main_classification' => 'Leadership',
            'finish_date'         => '2026-08-15',
        ], [
            [
                'name'           => 'Parity User 1',
                'department'     => $uniqueDept,
                'company'        => 'PARITY_CORP',
                'progress'       => 100,
                'learning_hours' => 2.5,
            ],
            [
                'name'           => 'Parity User 2',
                'department'     => $uniqueDept,
                'company'        => 'PARITY_CORP',
                'progress'       => 100,
                'learning_hours' => 2.5,
            ],
            [
                'name'           => 'Parity User 3',
                'department'     => $uniqueDept,
                'company'        => 'PARITY_CORP',
                'progress'       => 30, // will be filtered out by progress=100
                'learning_hours' => 1.0,
            ]
        ]);

        $filters = [
            'classification' => 'Leadership',
            'department'     => $uniqueDept,
            'progress_range' => '100',
            'source'         => 'offline',
        ];

        // 1. Query count via RecordAllCombinedService (same query used by Excel Export)
        $service = app(RecordAllCombinedService::class);
        $exportCount = $service->buildCombinedQuery($filters)->count();

        // 2. Query count via DataTable endpoint
        $dtParams = http_build_query([
            'department'     => $uniqueDept,
            'progress_range' => '100',
            'source'         => 'offline',
        ]);
        $dtResp = $this->actingAs($this->admin)->getJson('/manage/record/all/classification/leadership/datatable?' . $dtParams);
        $dtResp->assertStatus(200);
        $datatableCount = $dtResp->json('recordsFiltered');

        // Both must be exactly 2
        $this->assertEquals(2, $exportCount, 'Export query must find exactly 2 matching records.');
        $this->assertEquals(2, $datatableCount, 'DataTable query must find exactly 2 matching records.');
        $this->assertEquals($exportCount, $datatableCount, 'Export and DataTable must have 100% filter count parity.');
    }

    /**
     * 33. Test comprehensive Export filtering, streaming generation, and query parity across all filter combinations.
     */
    public function test_export_correctness_and_parity_across_all_filter_combinations(): void
    {
        $uniqueDept = 'EXPORT_TEST_DEPT_' . uniqid();
        $uniqueComp = 'EXPORT_TEST_COMP_' . uniqid();
        $uniqueEmp = 'EMP_EXP_' . uniqid();
        $uniqueName = 'Export Tester ' . uniqid();
        $uniqueTitle = 'Export Specific Training ' . uniqid();

        // Create test record
        $this->createTestOfflineTraining([
            'course_name'         => $uniqueTitle,
            'main_classification' => 'Leadership',
            'start_date'          => '2026-06-01',
            'finish_date'         => '2026-06-15',
        ], [
            [
                'employee_id'    => $uniqueEmp,
                'name'           => $uniqueName,
                'department'     => $uniqueDept,
                'company'        => $uniqueComp,
                'progress'       => 100,
                'learning_hours' => 3.5,
            ]
        ]);

        $filterScenarios = [
            'No filter'            => [],
            'Employee filter'      => ['employee_id' => $uniqueEmp],
            'Title filter'         => ['learning_title' => $uniqueTitle],
            'Online source'        => ['source' => 'online'],
            'Offline source'       => ['source' => 'offline'],
            'Finished'             => ['status' => 'finished'],
            'Ongoing'              => ['status' => 'ongoing'],
            'Not Started'          => ['status' => 'not_started'],
            'Year'                 => ['year' => '2026'],
            'Date range'           => ['from_date' => '2026-06-01', 'to_date' => '2026-06-30'],
            'Progress range'       => ['progress_range' => '100'],
            'Learning Hours range' => ['learning_hours_range' => '2-4'],
            'Combined filters'     => [
                'employee_id'          => $uniqueEmp,
                'learning_title'       => $uniqueTitle,
                'source'               => 'offline',
                'status'               => 'finished',
                'year'                 => '2026',
                'progress_range'       => '100',
                'learning_hours_range' => '2-4',
            ],
        ];

        $service = app(RecordAllCombinedService::class);

        foreach ($filterScenarios as $label => $filters) {
            $filters['classification'] = 'Leadership';

            // Parity check: Export query count vs DataTable recordsFiltered
            $exportCount = $service->buildCombinedQuery($filters)->count();

            $dtFilters = $filters;
            unset($dtFilters['classification']);
            $dtResp = $this->actingAs($this->admin)->getJson('/manage/record/all/classification/leadership/datatable?' . http_build_query($dtFilters));
            $dtResp->assertStatus(200);
            $dtCount = $dtResp->json('recordsFiltered');

            $this->assertEquals($exportCount, $dtCount, "Filter scenario '{$label}' failed parity test between Export and DataTable.");
        }

        // Test streaming file generation
        $export = new \App\Exports\AllClassificationExport('Leadership', [
            'employee_id' => $uniqueEmp,
            'source'      => 'offline',
        ]);
        $filePath = $export->generateXlsxFile();
        $this->assertFileExists($filePath);
        $this->assertGreaterThan(1000, filesize($filePath), 'Generated XLSX file must be valid and non-empty.');
        @unlink($filePath);
    }

    /**
     * Test classification download defaults to XLSX and returns streaming Excel file.
     */
    public function test_classification_download_xlsx_route(): void
    {
        $uniqueEmp = 'EXCEL_TEST_' . uniqid();
        $this->createTestOfflineTraining([
            'main_classification' => 'Leadership',
        ], [
            [
                'employee_id'    => $uniqueEmp,
                'name'           => 'Excel Export User',
                'progress'       => 100,
                'learning_hours' => 4.0,
            ]
        ]);

        $response = $this->actingAs($this->admin)->get('/manage/record/all/classification/leadership/download?employee_id=' . $uniqueEmp);

        $response->assertStatus(200);
        $this->assertTrue(
            str_contains($response->headers->get('content-type', ''), 'spreadsheetml') ||
            str_contains($response->headers->get('content-type', ''), 'octet-stream')
        );
        $this->assertStringContainsString('.xlsx', $response->headers->get('content-disposition', ''));
    }

    /**
     * Test classification download CSV stream with UTF-8 BOM, 46 column headers, and filtered rows.
     */
    public function test_classification_download_csv_route_and_stream(): void
    {
        $uniqueEmp = 'CSV_TEST_' . uniqid();
        $uniqueTitle = 'CSV Training Course ' . uniqid();
        $this->createTestOfflineTraining([
            'course_name'         => $uniqueTitle,
            'main_classification' => 'Leadership',
        ], [
            [
                'employee_id'    => $uniqueEmp,
                'name'           => 'CSV Export User',
                'department'     => 'IT Innovation',
                'company'        => 'TEST_COMBINED_CORP',
                'progress'       => 100,
                'learning_hours' => 5.5,
            ]
        ]);

        $response = $this->actingAs($this->admin)->get('/manage/record/all/classification/leadership/download?format=csv&employee_id=' . $uniqueEmp);

        $response->assertStatus(200);
        $this->assertStringContainsString('text/csv', $response->headers->get('content-type', ''));
        $this->assertStringContainsString('.csv', $response->headers->get('content-disposition', ''));

        // Capture streamed content
        $streamedContent = $response->streamedContent();

        // 1. Verify UTF-8 BOM is prepended
        $bom = "\xEF\xBB\xBF";
        $this->assertTrue(str_starts_with($streamedContent, $bom), 'CSV output must start with UTF-8 BOM.');

        // 2. Parse CSV lines
        $cleanContent = substr($streamedContent, 3);
        $lines = explode("\n", trim($cleanContent));
        $this->assertGreaterThanOrEqual(2, count($lines), 'CSV must contain at least headers and one data row.');

        // 3. Verify exactly 46 column headings
        $headerFields = str_getcsv($lines[0]);
        $this->assertCount(46, $headerFields, 'CSV header must contain exactly 46 columns.');
        $this->assertEquals('No', $headerFields[0]);
        $this->assertEquals('Source', $headerFields[1]);
        $this->assertEquals('Employee ID', $headerFields[2]);
        $this->assertEquals('Employee Name', $headerFields[3]);
        $this->assertEquals('Main Classification', $headerFields[16]);
        $this->assertEquals('PIC', $headerFields[45]);

        // 4. Verify data row content
        $dataFields = str_getcsv($lines[1]);
        $this->assertEquals('1', $dataFields[0]);
        $this->assertEquals('Non KP OWL', $dataFields[1]);
        $this->assertEquals($uniqueEmp, $dataFields[2]);
        $this->assertEquals('CSV Export User', $dataFields[3]);
        $this->assertEquals('IT Innovation', $dataFields[8]);
        $this->assertEquals('TEST_COMBINED_CORP', $dataFields[9]);
        $this->assertEquals('5.50', $dataFields[31]); // learning_hours
    }

    /**
     * Test classification download PDF when records <= 200 returns valid PDF binary.
     */
    public function test_classification_download_pdf_under_limit(): void
    {
        $uniqueEmp = 'PDF_TEST_' . uniqid();
        $this->createTestOfflineTraining([
            'main_classification' => 'Leadership',
        ], [
            [
                'employee_id'    => $uniqueEmp,
                'name'           => 'PDF Export User',
                'progress'       => 100,
                'learning_hours' => 3.0,
            ]
        ]);

        $response = $this->actingAs($this->admin)->get('/manage/record/all/classification/leadership/download?format=pdf&employee_id=' . $uniqueEmp);

        $response->assertStatus(200);
        $this->assertStringContainsString('application/pdf', $response->headers->get('content-type', ''));
        $this->assertStringContainsString('.pdf', $response->headers->get('content-disposition', ''));

        $content = $response->getContent();
        $this->assertStringStartsWith('%PDF-', $content, 'PDF output must begin with %PDF- signature.');
    }

    /**
     * Test classification download PDF guard when records exceed 200 limit.
     */
    public function test_classification_download_pdf_over_limit_guard(): void
    {
        // Unfiltered query on Leadership has > 200 records in the test DB
        // 1. AJAX request expects JSON with 422 status
        $ajaxResp = $this->actingAs($this->admin)->getJson('/manage/record/all/classification/leadership/download?format=pdf');

        $ajaxResp->assertStatus(422);
        $ajaxResp->assertJson([
            'success' => false,
            'limit'   => 200,
        ]);
        $this->assertGreaterThan(200, $ajaxResp->json('total'));

        // 2. Direct browser navigation returns 422 with the friendly warning Blade view
        $htmlResp = $this->actingAs($this->admin)->get('/manage/record/all/classification/leadership/download?format=pdf');
        $htmlResp->assertStatus(422);
        $htmlResp->assertSee('PDF Export Limit Exceeded');
        $htmlResp->assertSee('Download as Excel (.xlsx)');
        $htmlResp->assertSee('Download as CSV (.csv)');
    }

    /**
     * Test classification download format route parameter supports /download/csv and /download/xlsx.
     */
    public function test_classification_download_format_route_param(): void
    {
        $uniqueEmp = 'ROUTE_PARAM_' . uniqid();
        $this->createTestOfflineTraining([
            'main_classification' => 'Leadership',
        ], [
            [
                'employee_id' => $uniqueEmp,
                'name'        => 'Route Param User',
            ]
        ]);

        $respCsv = $this->actingAs($this->admin)->get('/manage/record/all/classification/leadership/download/csv?employee_id=' . $uniqueEmp);
        $respCsv->assertStatus(200);
        $this->assertStringContainsString('text/csv', $respCsv->headers->get('content-type', ''));

        $respXlsx = $this->actingAs($this->admin)->get('/manage/record/all/classification/leadership/download/xlsx?employee_id=' . $uniqueEmp);
        $respXlsx->assertStatus(200);
        $this->assertStringContainsString('.xlsx', $respXlsx->headers->get('content-disposition', ''));
    }

    /**
     * Test filter parity across Excel, CSV, and PDF downloads.
     */
    public function test_classification_download_multi_format_filter_parity(): void
    {
        $uniqueEmp = 'PARITY_EMP_' . uniqid();
        $uniqueDept = 'PARITY_DEPT_' . uniqid();
        $this->createTestOfflineTraining([
            'main_classification' => 'Leadership',
            'start_date'          => '2026-07-10',
            'finish_date'         => '2026-07-10',
        ], [
            [
                'employee_id'    => $uniqueEmp,
                'name'           => 'Parity Test User',
                'department'     => $uniqueDept,
                'company'        => 'TEST_COMBINED_CORP',
                'progress'       => 100,
                'learning_hours' => 2.0,
            ]
        ]);

        $filterParams = [
            'employee_id' => $uniqueEmp,
            'department'  => $uniqueDept,
            'source'      => 'offline',
            'status'      => 'finished',
        ];

        // 1. CSV download test
        $csvResp = $this->actingAs($this->admin)->get('/manage/record/all/classification/leadership/download/csv?' . http_build_query($filterParams));
        $csvResp->assertStatus(200);
        $csvContent = substr($csvResp->streamedContent(), 3); // strip BOM
        $csvLines = array_filter(explode("\n", trim($csvContent)));
        $this->assertCount(2, $csvLines, 'CSV must have 1 header line and exactly 1 data row matching filters.');

        // 2. PDF download test (under limit)
        $pdfResp = $this->actingAs($this->admin)->get('/manage/record/all/classification/leadership/download/pdf?' . http_build_query($filterParams));
        $pdfResp->assertStatus(200);
        $this->assertStringContainsString('application/pdf', $pdfResp->headers->get('content-type', ''));

        // 3. DataTable parity
        $dtResp = $this->actingAs($this->admin)->getJson('/manage/record/all/classification/leadership/datatable?' . http_build_query($filterParams));
        $dtResp->assertStatus(200);
        $this->assertEquals(1, $dtResp->json('recordsFiltered'));
    }
}
