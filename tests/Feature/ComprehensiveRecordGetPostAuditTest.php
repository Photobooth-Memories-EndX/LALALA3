<?php

namespace Tests\Feature;

use App\Models\OfflineTraining;
use App\Models\OfflineTrainingUser;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class ComprehensiveRecordGetPostAuditTest extends TestCase
{
    use DatabaseTransactions;

    protected User $admin;

    protected function setUp(): void
    {
        parent::setUp();
        $this->admin = User::where('role', '2')->first() ?? User::first();
    }

    /**
     * Test 1: Verify all modified Blade files configure DataTables as POST and Export as GET.
     */
    public function test_all_modified_blades_have_post_datatables_and_get_export(): void
    {
        $blades = [
            'resources/views/main/record/all/participant.blade.php',
            'resources/views/main/record/all/classification.blade.php',
            'resources/views/main/record/offline/classification.blade.php',
            'resources/views/main/record/offline/participant.blade.php',
            'resources/views/main/record/offline/course.blade.php',
            'resources/views/main/record/online/classification.blade.php',
            'resources/views/main/profile/components/profile-table-script.blade.php',
        ];

        foreach ($blades as $bladeRelPath) {
            $path = base_path($bladeRelPath);
            $this->assertFileExists($path, "File {$bladeRelPath} must exist.");
            $content = file_get_contents($path);

            $this->assertMatchesRegularExpression("/type:\s*'POST'/i", $content, "{$bladeRelPath} must use POST for DataTables.");
            $this->assertStringContainsString('X-CSRF-TOKEN', $content, "{$bladeRelPath} must pass X-CSRF-TOKEN header.");
            $this->assertStringContainsString('d._token', $content, "{$bladeRelPath} must pass _token in payload.");
        }
    }

    /**
     * Test 2: ALL Participant Detail DataTables accepts POST and works for participants 125728 & 129458 without HTTP 500.
     */
    public function test_all_participant_datatables_post_and_tpd_cache_fix(): void
    {
        $identifiers = ['125728', '129458'];

        foreach ($identifiers as $id) {
            $url = route('manage.record.all.participant.datatable', $id);

            $payload = [
                '_token' => csrf_token(),
                'draw' => 1,
                'start' => 0,
                'length' => 10,
                'search' => ['value' => '', 'regex' => 'false'],
                'learning_title' => '',
                'source' => 'all',
                'status' => 'all',
                'year' => 'all',
                'classification' => 'basic-common-duties',
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
            $this->assertGreaterThan(0, $response->json('recordsTotal'));
        }
    }

    /**
     * Test 3: ALL Participant Excel Export remains GET and returns XLSX attachment.
     */
    public function test_all_participant_export_remains_get(): void
    {
        $url = route('manage.record.all.participant.export.excel', [
            'identifier' => '125728',
            'classification' => 'basic-common-duties',
        ]);

        $response = $this->actingAs($this->admin)->get($url);

        $response->assertStatus(200);
        $response->assertHeader('content-type', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
    }

    /**
     * Test 4: Offline Classification DataTables accepts POST, and Export uses GET.
     */
    public function test_offline_classification_datatables_post_and_export_get(): void
    {
        $slug = 'basic-common-duties';
        $tableUrl = route('manage.record.offline.classification.datatable', $slug);

        $payload = [
            '_token' => csrf_token(),
            'draw' => 1,
            'start' => 0,
            'length' => 10,
            'search' => ['value' => '', 'regex' => 'false'],
            'classification' => $slug,
            'employee_id' => '',
            'course_name' => '',
            'type' => 'all',
            'status' => 'all',
            'year' => 'all',
        ];

        $tableResponse = $this->actingAs($this->admin)->postJson($tableUrl, $payload);
        $tableResponse->assertStatus(200);
        $tableResponse->assertJsonStructure(['draw', 'recordsTotal', 'recordsFiltered', 'data']);

        // Excel export GET
        $exportUrl = route('manage.record.offline.classification.export.excel', $slug);
        $exportResponse = $this->actingAs($this->admin)->get($exportUrl);
        $exportResponse->assertStatus(200);
        $exportResponse->assertHeader('content-type', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
    }

    /**
     * Test 5: Offline Participant Detail DataTables accepts POST, and Export uses GET.
     */
    public function test_offline_participant_datatables_post_and_export_get(): void
    {
        $id = '1004';
        $tableUrl = route('manage.record.offline.participant.datatable', $id);

        $payload = [
            '_token' => csrf_token(),
            'draw' => 1,
            'start' => 0,
            'length' => 10,
            'search' => ['value' => '', 'regex' => 'false'],
            'course_name' => '',
            'classification' => 'job-functional',
        ];

        $tableResponse = $this->actingAs($this->admin)->postJson($tableUrl, $payload);
        $tableResponse->assertStatus(200);
        $tableResponse->assertJsonStructure(['draw', 'recordsTotal', 'recordsFiltered', 'data']);

        // Excel export GET
        $exportUrl = route('manage.record.offline.participant.export.excel', $id);
        $exportResponse = $this->actingAs($this->admin)->get($exportUrl);
        $exportResponse->assertStatus(200);
        $exportResponse->assertHeader('content-type', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
    }

    /**
     * Test 6: Offline Course Detail DataTables accepts POST, and Export uses GET.
     */
    public function test_offline_course_datatables_post_and_export_get(): void
    {
        // Find existing training ID or fallback
        $training = OfflineTraining::first();
        if (!$training) {
            $this->markTestSkipped('No offline training found.');
        }

        $tableUrl = route('manage.record.offline.course.datatable', $training->id);

        $payload = [
            '_token' => csrf_token(),
            'draw' => 1,
            'start' => 0,
            'length' => 10,
            'search' => ['value' => '', 'regex' => 'false'],
            'classification' => 'job-functional',
        ];

        $tableResponse = $this->actingAs($this->admin)->postJson($tableUrl, $payload);
        $tableResponse->assertStatus(200);
        $tableResponse->assertJsonStructure(['draw', 'recordsTotal', 'recordsFiltered', 'data']);

        // Excel export GET
        $exportUrl = route('manage.record.offline.course.export.excel', $training->id);
        $exportResponse = $this->actingAs($this->admin)->get($exportUrl);
        $exportResponse->assertStatus(200);
        $exportResponse->assertHeader('content-type', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
    }

    /**
     * Test 7: User Profile DataTables accepts POST, and Export uses GET.
     */
    public function test_user_profile_datatables_post_and_export_get(): void
    {
        $user = $this->admin;
        $tableUrl = route('user.manage.profile.learning.datatable', $user->ssohash);

        $payload = [
            '_token' => csrf_token(),
            'draw' => 1,
            'start' => 0,
            'length' => 10,
            'search' => ['value' => '', 'regex' => 'false'],
            'learning_title' => '',
            'classification' => '',
            'status' => '',
        ];

        $tableResponse = $this->actingAs($this->admin)->postJson($tableUrl, $payload);
        $tableResponse->assertStatus(200);
        $tableResponse->assertJsonStructure(['draw', 'recordsTotal', 'recordsFiltered', 'data']);

        // Excel export GET
        $exportUrl = route('user.manage.profile.export.excel', $user->ssohash);
        $exportResponse = $this->actingAs($this->admin)->get($exportUrl);
        $exportResponse->assertStatus(200);
        $exportResponse->assertHeader('content-type', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
    }

    /**
     * Test 8: All routes preserve GET backward compatibility for DataTables.
     */
    public function test_routes_preserve_get_backward_compatibility(): void
    {
        $allPartUrl = route('manage.record.all.participant.datatable', '125728');
        $resp1 = $this->actingAs($this->admin)->getJson($allPartUrl . '?draw=1&start=0&length=5');
        $resp1->assertStatus(200)->assertJsonStructure(['draw', 'recordsTotal', 'recordsFiltered', 'data']);

        $offClassUrl = route('manage.record.offline.classification.datatable', 'basic-common-duties');
        $resp2 = $this->actingAs($this->admin)->getJson($offClassUrl . '?draw=1&start=0&length=5');
        $resp2->assertStatus(200)->assertJsonStructure(['draw', 'recordsTotal', 'recordsFiltered', 'data']);

        $profUrl = route('user.manage.profile.learning.datatable', $this->admin->ssohash);
        $resp3 = $this->actingAs($this->admin)->getJson($profUrl . '?draw=1&start=0&length=5');
        $resp3->assertStatus(200)->assertJsonStructure(['draw', 'recordsTotal', 'recordsFiltered', 'data']);
    }
}
