<?php

namespace Tests\Feature;

use App\Models\OfflineTraining;
use App\Models\OfflineTrainingUser;
use App\Models\User;
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
        $response->assertSee('All KP OWL');
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

        // Check HTML contains KPI and Source Summary cards
        $html = $response->json('html');
        $this->assertStringContainsString('Total Learning', $html);
        $this->assertStringContainsString('KP OWL — Online Learning Records', $html);
        $this->assertStringContainsString('Non KP OWL — Offline Training Records', $html);
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
        $this->assertEquals($uniqueName, $respByName->json('data.0.employee_name'));
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
        $this->assertEquals($uniqueTitle, $resp->json('data.0.learning_title'));
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
            $this->assertStringContainsString('btn btn-sm btn-outline-primary', $onlineAction);
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
}
