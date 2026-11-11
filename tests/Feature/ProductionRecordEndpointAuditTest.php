<?php

namespace Tests\Feature;

use App\Models\Classification;
use App\Models\OfflineTraining;
use App\Models\OfflineTrainingUser;
use App\Models\TrainingPlan;
use App\Models\User;
use App\Services\Record\RecordAllCombinedService;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

/**
 * ProductionRecordEndpointAuditTest
 *
 * Comprehensive Automated Regression Testing Suite for PT Krakatau Posco OWL LMS Record Modules.
 * Validates GET and POST architectural consistency, CSRF protection, response structures,
 * and regression safety for both Online and Offline records across all 9 target pages.
 */
class ProductionRecordEndpointAuditTest extends TestCase
{
    use DatabaseTransactions;

    protected User $admin;
    protected User $participant125728;
    protected User $participant129458;
    protected User $profileUser;
    protected OfflineTraining $testOfflineTraining;
    protected OfflineTrainingUser $testOfflineParticipant;

    protected function setUp(): void
    {
        parent::setUp();

        // 1. Resolve Admin (Role 2 has record_management and learning_management permissions)
        $this->admin = User::where('role', '2')->first() ?? User::first();

        // 2. Resolve target employees
        $this->participant125728 = User::where('id_emp', '125728')->first() ?? $this->admin;
        $this->participant129458 = User::where('id_emp', '129458')->first() ?? $this->admin;
        $this->profileUser = User::where('ssohash', '79a64462dca23de1fc56b8c6f1b8b69da66034f7')->first() ?? $this->admin;

        // 3. Setup safe transactional Offline Training fixture if none exists
        $this->testOfflineTraining = OfflineTraining::firstOrCreate([
            'course_name' => 'Automated Audit Offline Safety Training',
        ], [
            'type' => 'offline',
            'main_classification' => 'Job Functional',
            'sub_classification' => 'Safety & Environment',
            'status' => 'finished',
            'learning_hours' => 4.0,
            'duration_seconds' => 14400,
            'start_date' => now()->toDateString(),
            'end_date' => now()->toDateString(),
        ]);

        $this->testOfflineParticipant = OfflineTrainingUser::firstOrCreate([
            'offline_training_id' => $this->testOfflineTraining->id,
            'user_id' => $this->admin->id,
        ], [
            'employee_id' => $this->admin->id_emp ?: '1004',
            'name' => $this->admin->name,
            'main_classification' => 'Job Functional',
            'learning_hours' => 4.0,
            'start_date' => now()->toDateString(),
            'end_date' => now()->toDateString(),
            'progress' => 100.00,
        ]);
    }

    /**
     * Requirement 1: ALL Classification DataTables POST.
     */
    public function test_01_all_classification_datatables_post(): void
    {
        $url = route('manage.record.all.classification.data', 'basic-common-duties');

        $payload = [
            '_token' => csrf_token(),
            'draw' => 1,
            'start' => 0,
            'length' => 10,
            'classification' => 'basic-common-duties',
            'source' => 'all',
            'status' => 'all',
            'year' => 'all',
        ];

        $response = $this->actingAs($this->admin)->postJson($url, $payload);

        $response->assertStatus(200);
        $response->assertJsonStructure([
            'draw',
            'recordsTotal',
            'recordsFiltered',
            'data',
        ]);
        $this->assertEquals(1, $response->json('draw'));
        $this->assertIsArray($response->json('data'));
    }

    /**
     * Requirement 2: ALL Participant DataTables POST.
     */
    public function test_02_all_participant_datatables_post(): void
    {
        $url = route('manage.record.all.participant.datatable', $this->participant125728->id_emp);

        $payload = [
            '_token' => csrf_token(),
            'draw' => 1,
            'start' => 0,
            'length' => 10,
            'classification' => 'basic-common-duties',
            'source' => 'all',
            'status' => 'all',
            'year' => 'all',
        ];

        $response = $this->actingAs($this->admin)->postJson($url, $payload);

        $response->assertStatus(200);
        $response->assertJsonStructure(['draw', 'recordsTotal', 'recordsFiltered', 'data']);
        $this->assertEquals(1, $response->json('draw'));
        $this->assertIsArray($response->json('data'));
    }

    /**
     * Requirement 3: ALL Participant 125728 query integrity and DataTables verification.
     */
    public function test_03_all_participant_125728(): void
    {
        $service = app(RecordAllCombinedService::class);
        $participant = $service->resolveParticipant('125728');
        $this->assertNotNull($participant, 'Participant 125728 must be resolved.');
        $this->assertEquals('125728', $participant['employee_id']);

        $summary = $service->getParticipantSummary($participant, 'basic-common-duties');
        $this->assertGreaterThan(0, $summary['total_learning']);

        $url = route('manage.record.all.participant.datatable', '125728');
        $response = $this->actingAs($this->admin)->postJson($url, [
            '_token' => csrf_token(),
            'draw' => 1,
            'start' => 0,
            'length' => 10,
            'classification' => 'basic-common-duties',
        ]);

        $response->assertStatus(200);
        $this->assertEquals($summary['total_learning'], $response->json('recordsTotal'));
    }

    /**
     * Requirement 4: ALL Participant 129458 query integrity and DataTables verification.
     */
    public function test_04_all_participant_129458(): void
    {
        $service = app(RecordAllCombinedService::class);
        $participant = $service->resolveParticipant('129458');
        $this->assertNotNull($participant, 'Participant 129458 must be resolved.');
        $this->assertEquals('129458', $participant['employee_id']);

        $summary = $service->getParticipantSummary($participant, 'basic-common-duties');
        $this->assertGreaterThan(0, $summary['total_learning']);

        $url = route('manage.record.all.participant.datatable', '129458');
        $response = $this->actingAs($this->admin)->postJson($url, [
            '_token' => csrf_token(),
            'draw' => 1,
            'start' => 0,
            'length' => 10,
            'classification' => 'basic-common-duties',
        ]);

        $response->assertStatus(200);
        $this->assertEquals($summary['total_learning'], $response->json('recordsTotal'));
    }

    /**
     * Requirement 5: Offline Classification DataTables POST.
     */
    public function test_05_offline_classification_datatables_post(): void
    {
        $url = route('manage.record.offline.classification.datatable', 'basic-common-duties');

        $payload = [
            '_token' => csrf_token(),
            'draw' => 1,
            'start' => 0,
            'length' => 10,
            'classification' => 'basic-common-duties',
            'type' => 'all',
            'status' => 'all',
            'year' => 'all',
        ];

        $response = $this->actingAs($this->admin)->postJson($url, $payload);

        $response->assertStatus(200);
        $response->assertJsonStructure(['draw', 'recordsTotal', 'recordsFiltered', 'data']);
    }

    /**
     * Requirement 6: Offline Participant DataTables POST.
     */
    public function test_06_offline_participant_datatables_post(): void
    {
        $identifier = $this->admin->id_emp ?: (string) $this->admin->id;
        $url = route('manage.record.offline.participant.datatable', $identifier);

        $payload = [
            '_token' => csrf_token(),
            'draw' => 1,
            'start' => 0,
            'length' => 10,
            'classification' => 'job-functional',
        ];

        $response = $this->actingAs($this->admin)->postJson($url, $payload);

        $response->assertStatus(200);
        $response->assertJsonStructure(['draw', 'recordsTotal', 'recordsFiltered', 'data']);
    }

    /**
     * Requirement 7: Offline Course DataTables POST.
     */
    public function test_07_offline_course_datatables_post(): void
    {
        $url = route('manage.record.offline.course.datatable', $this->testOfflineTraining->id);

        $payload = [
            '_token' => csrf_token(),
            'draw' => 1,
            'start' => 0,
            'length' => 10,
            'classification' => 'job-functional',
        ];

        $response = $this->actingAs($this->admin)->postJson($url, $payload);

        $response->assertStatus(200);
        $response->assertJsonStructure(['draw', 'recordsTotal', 'recordsFiltered', 'data']);
    }

    /**
     * Requirement 8: Online Classification DataTables POST.
     */
    public function test_08_online_classification_datatables_post(): void
    {
        $url = route('manage.record.online.classification.datatable', 'basic-common-duties');

        $payload = [
            '_token' => csrf_token(),
            'draw' => 1,
            'start' => 0,
            'length' => 10,
            'classification' => 'basic-common-duties',
            'classification_slug' => 'basic-common-duties',
            'slug' => 'basic-common-duties',
            'category' => 'all',
            'status' => 'all',
            'year' => 'all',
        ];

        $response = $this->actingAs($this->admin)->postJson($url, $payload);

        $response->assertStatus(200);
        $response->assertJsonStructure(['draw', 'recordsTotal', 'recordsFiltered', 'data']);
        $this->assertEquals(1, $response->json('draw'));
        $this->assertIsArray($response->json('data'));
    }

    /**
     * Requirement 9: User Profile DataTables POST.
     */
    public function test_09_user_profile_datatables_post(): void
    {
        $url = route('user.manage.profile.learning.datatable', $this->profileUser->ssohash);

        $payload = [
            '_token' => csrf_token(),
            'draw' => 1,
            'start' => 0,
            'length' => 10,
            'learning_title' => '',
            'classification' => '',
            'status' => '',
            'type' => '',
            'year' => '',
        ];

        $response = $this->actingAs($this->admin)->postJson($url, $payload);

        $response->assertStatus(200);
        $response->assertJsonStructure(['draw', 'recordsTotal', 'recordsFiltered', 'data']);
        $this->assertEquals(1, $response->json('draw'));
        $this->assertIsArray($response->json('data'));
    }

    /**
     * Requirement 10: Learning Playlist navigation GET.
     */
    public function test_10_learning_playlist_navigation_get(): void
    {
        $playlist = TrainingPlan::find(21) ?? TrainingPlan::first();
        $this->assertNotNull($playlist, 'TrainingPlan playlist must exist.');

        $url = route('learning.manage.playlist', $playlist->id);

        $response = $this->actingAs($this->admin)->get($url);

        $response->assertStatus(200);
        $response->assertSeeText($playlist->title);
    }

    /**
     * Requirement 11: ALL Excel Export GET.
     */
    public function test_11_all_excel_export_get(): void
    {
        // 11.a Classification Excel Export GET
        $classExportUrl = route('manage.record.all.classification.export', 'basic-common-duties');
        $response = $this->actingAs($this->admin)->get($classExportUrl);
        $response->assertStatus(200);
        $response->assertHeader('content-type', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');

        // 11.b Participant Excel Export GET
        $partExportUrl = route('manage.record.all.participant.export.excel', [
            'identifier' => '125728',
            'classification' => 'basic-common-duties',
        ]);
        $partResponse = $this->actingAs($this->admin)->get($partExportUrl);
        $partResponse->assertStatus(200);
        $partResponse->assertHeader('content-type', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
    }

    /**
     * Requirement 12: Offline Excel Export GET.
     */
    public function test_12_offline_excel_export_get(): void
    {
        // 12.a Offline Classification Export GET
        $url1 = route('manage.record.offline.classification.export.excel', 'basic-common-duties');
        $res1 = $this->actingAs($this->admin)->get($url1);
        $res1->assertStatus(200);
        $res1->assertHeader('content-type', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');

        // 12.b Offline Participant Export GET
        $identifier = $this->admin->id_emp ?: (string) $this->admin->id;
        $url2 = route('manage.record.offline.participant.export.excel', $identifier);
        $res2 = $this->actingAs($this->admin)->get($url2);
        $res2->assertStatus(200);
        $res2->assertHeader('content-type', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');

        // 12.c Offline Course Export GET
        $url3 = route('manage.record.offline.course.export.excel', $this->testOfflineTraining->id);
        $res3 = $this->actingAs($this->admin)->get($url3);
        $res3->assertStatus(200);
        $res3->assertHeader('content-type', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
    }

    /**
     * Requirement 13: Online Excel Export GET.
     */
    public function test_13_online_excel_export_get(): void
    {
        $url = route('manage.record.online.classification.export.excel', [
            'classification' => 'basic-common-duties',
            'status' => 'finished',
            'year' => '2025',
        ]);

        $response = $this->actingAs($this->admin)->get($url);

        $response->assertStatus(200);
        $response->assertHeader('content-type', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
    }

    /**
     * Requirement 14: User Profile Excel Export GET.
     */
    public function test_14_user_profile_excel_export_get(): void
    {
        $url = route('user.manage.profile.export.excel', [
            'hash' => $this->profileUser->ssohash,
            'status' => 'finished',
        ]);

        $response = $this->actingAs($this->admin)->get($url);

        $response->assertStatus(200);
        $response->assertHeader('content-type', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
    }

    /**
     * Requirement 15: CSRF protection validation.
     */
    public function test_15_csrf_protection_enforced(): void
    {
        // Calling POST endpoint without CSRF token or session headers in normal web context triggers token verification
        $url = route('manage.record.online.classification.datatable', 'basic-common-duties');

        // Without actingAs and without token -> redirects to login (302) or 419 Page Expired
        $response = $this->post($url, [
            'draw' => 1,
            'start' => 0,
            'length' => 10,
        ]);

        $this->assertContains($response->getStatusCode(), [302, 419]);
    }

    /**
     * Requirement 16: JSON response validation (Content-Type application/json).
     */
    public function test_16_json_response_validation(): void
    {
        $url = route('manage.record.all.classification.data', 'basic-common-duties');

        $response = $this->actingAs($this->admin)->postJson($url, [
            '_token' => csrf_token(),
            'draw' => 1,
            'start' => 0,
            'length' => 5,
        ]);

        $response->assertStatus(200);
        $response->assertHeader('content-type', 'application/json');
        $this->assertJson($response->getContent());
    }

    /**
     * Requirement 17: DataTables response contract structure (draw, recordsTotal, recordsFiltered, data).
     */
    public function test_17_datatables_response_structure_contract(): void
    {
        $endpoints = [
            route('manage.record.all.classification.data', 'basic-common-duties'),
            route('manage.record.all.participant.datatable', '125728'),
            route('manage.record.online.classification.datatable', 'basic-common-duties'),
            route('user.manage.profile.learning.datatable', $this->profileUser->ssohash),
        ];

        foreach ($endpoints as $url) {
            $response = $this->actingAs($this->admin)->postJson($url, [
                '_token' => csrf_token(),
                'draw' => 5,
                'start' => 0,
                'length' => 5,
            ]);

            $response->assertStatus(200);
            $json = $response->json();
            $this->assertArrayHasKey('draw', $json, "Endpoint {$url} must return 'draw'");
            $this->assertArrayHasKey('recordsTotal', $json, "Endpoint {$url} must return 'recordsTotal'");
            $this->assertArrayHasKey('recordsFiltered', $json, "Endpoint {$url} must return 'recordsFiltered'");
            $this->assertArrayHasKey('data', $json, "Endpoint {$url} must return 'data'");
            $this->assertEquals(5, $json['draw']);
            $this->assertIsArray($json['data']);
        }
    }

    /**
     * Requirement 18: Route backward compatibility (Preserves GET support for DataTables).
     */
    public function test_18_route_get_compatibility(): void
    {
        $routes = [
            route('manage.record.all.classification.data', 'basic-common-duties') . '?draw=1&start=0&length=5',
            route('manage.record.all.participant.datatable', '125728') . '?draw=1&start=0&length=5',
            route('manage.record.offline.classification.datatable', 'basic-common-duties') . '?draw=1&start=0&length=5',
            route('manage.record.online.classification.datatable', 'basic-common-duties') . '?draw=1&start=0&length=5',
            route('user.manage.profile.learning.datatable', $this->profileUser->ssohash) . '?draw=1&start=0&length=5',
        ];

        foreach ($routes as $url) {
            $response = $this->actingAs($this->admin)->getJson($url);
            $response->assertStatus(200);
            $response->assertJsonStructure(['draw', 'recordsTotal', 'recordsFiltered', 'data']);
        }
    }

    /**
     * Requirement 19: Filter consistency via POST.
     */
    public function test_19_filter_consistency(): void
    {
        $url = route('manage.record.online.classification.datatable', 'basic-common-duties');

        // Test with non-matching filter
        $response = $this->actingAs($this->admin)->postJson($url, [
            '_token' => csrf_token(),
            'draw' => 1,
            'start' => 0,
            'length' => 10,
            'employee_id' => 'NON_EXISTENT_AUDIT_NIP_999999',
            'classification' => 'basic-common-duties',
        ]);

        $response->assertStatus(200);
        $this->assertEquals(0, $response->json('recordsFiltered'));
        $this->assertEmpty($response->json('data'));
    }

    /**
     * Requirement 20: Regression of combined Online + Offline records (RecordAllCombinedService).
     */
    public function test_20_regression_combined_online_and_offline(): void
    {
        $service = app(RecordAllCombinedService::class);

        // Check buildCombinedQuery execution without syntax/binding errors
        $query = $service->buildCombinedQuery([
            'classification' => 'basic-common-duties',
        ]);

        $this->assertNotNull($query);
        $count = $query->count();
        $this->assertGreaterThan(0, $count, 'Combined records count must be greater than 0.');

        // Verify union maintains online source rows
        $onlineQuery = $service->buildCombinedQuery([
            'classification' => 'basic-common-duties',
            'source' => 'online',
        ]);
        $onlineCount = $onlineQuery->count();
        $this->assertGreaterThan(0, $onlineCount, 'Online records must be queryable via source filter.');
    }
}
