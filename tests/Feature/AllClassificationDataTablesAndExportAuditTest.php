<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class AllClassificationDataTablesAndExportAuditTest extends TestCase
{
    use DatabaseTransactions;

    protected User $admin;

    protected function setUp(): void
    {
        parent::setUp();
        // Role 2 is admin with record_management and learning_management permissions
        $this->admin = User::where('role', '2')->first() ?? User::first();
    }

    /**
     * Test 1: Blade file configuration confirms DataTables uses POST and Export uses GET.
     */
    public function test_all_classification_blade_has_post_for_datatables_and_get_for_export(): void
    {
        $bladePath = resource_path('views/main/record/all/classification.blade.php');
        $this->assertFileExists($bladePath);

        $content = file_get_contents($bladePath);

        // DataTables must be configured with POST
        $this->assertMatchesRegularExpression("/type:\s*'POST'/i", $content, 'All KP OWL DataTables ajax method must be POST.');

        // DataTables must include CSRF token
        $this->assertStringContainsString('X-CSRF-TOKEN', $content, 'DataTables must pass X-CSRF-TOKEN header.');
        $this->assertStringContainsString('d._token', $content, 'DataTables data callback must include _token.');

        // Excel Export must use GET via window.location.href
        $this->assertStringContainsString('window.location.href = fullUrl', $content, 'Export download must trigger GET navigation.');
        $this->assertStringContainsString("route('manage.record.all.classification.download'", $content, 'Download route must be configured.');
    }

    /**
     * Test 2: DataTables endpoint accepts POST with CSRF and returns valid JSON.
     */
    public function test_all_classification_datatables_endpoint_accepts_post_request(): void
    {
        $slug = 'basic-common-duties';
        $url = route('manage.record.all.classification.datatable', $slug);

        $payload = [
            '_token' => csrf_token(),
            'draw' => 1,
            'start' => 0,
            'length' => 10,
            'search' => ['value' => '', 'regex' => 'false'],
            'employee_id' => '',
            'learning_title' => '',
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
        $this->assertGreaterThanOrEqual(1, $response->json('recordsTotal'));
    }

    /**
     * Test 3: DataTables endpoint supports custom filters via POST.
     */
    public function test_all_classification_datatables_endpoint_filters_via_post(): void
    {
        $slug = 'basic-common-duties';
        $url = route('manage.record.all.classification.datatable', $slug);

        // Request with non-existent employee to test filtering behavior
        $payload = [
            '_token' => csrf_token(),
            'draw' => 2,
            'start' => 0,
            'length' => 10,
            'search' => ['value' => '', 'regex' => 'false'],
            'employee_id' => 'NON_EXISTENT_EMPLOYEE_NIP_999999',
            'learning_title' => '',
            'source' => 'all',
            'status' => 'all',
            'year' => 'all',
        ];

        $response = $this->actingAs($this->admin)->postJson($url, $payload);

        $response->assertStatus(200);
        $this->assertEquals(0, $response->json('recordsFiltered'));
        $this->assertEmpty($response->json('data'));
    }

    /**
     * Test 4: DataTables route also preserves GET backward compatibility.
     */
    public function test_all_classification_datatables_endpoint_preserves_get_backward_compatibility(): void
    {
        $slug = 'basic-common-duties';
        $url = route('manage.record.all.classification.datatable', $slug);

        $response = $this->actingAs($this->admin)->getJson($url . '?draw=1&start=0&length=5');

        $response->assertStatus(200);
        $response->assertJsonStructure([
            'draw',
            'recordsTotal',
            'recordsFiltered',
            'data',
        ]);
    }

    /**
     * Test 5: Excel export endpoint remains accessible via GET.
     */
    public function test_all_classification_excel_export_endpoint_accessible_via_get(): void
    {
        $slug = 'basic-common-duties';
        $url = route('manage.record.all.classification.export', $slug);

        $response = $this->actingAs($this->admin)->get($url);

        $response->assertStatus(200);
        $response->assertHeader('content-type', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
    }

    /**
     * Test 6: Excel export endpoint respects active filters via GET query parameters.
     */
    public function test_all_classification_excel_export_endpoint_accepts_get_filters(): void
    {
        $slug = 'basic-common-duties';
        $url = route('manage.record.all.classification.download', [
            'classification' => $slug,
            'format' => 'xlsx',
            'employee_id' => '999999',
            'learning_title' => 'Non Existent Test Course',
            'source' => 'all',
            'status' => 'all',
            'year' => '2025',
        ]);

        $response = $this->actingAs($this->admin)->get($url);

        $response->assertStatus(200);
        $response->assertHeader('content-type', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
    }
}
