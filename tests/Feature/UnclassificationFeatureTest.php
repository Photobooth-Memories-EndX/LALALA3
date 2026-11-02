<?php

namespace Tests\Feature;

use App\Http\Controllers\Manage\Record\RecordOfflineController;
use App\Http\Controllers\Manage\Record\RecordOnlineController;
use App\Models\User;
use Illuminate\Http\Request;
use Tests\TestCase;

class UnclassificationFeatureTest extends TestCase
{
    protected User $admin;

    protected function setUp(): void
    {
        parent::setUp();
        $this->admin = User::where('role', '2')->first() ?? User::first();
    }

    /**
     * Test Online Classification Summary has Unclassification at the bottom
     */
    public function test_online_classification_summary_has_unclassification_as_last_row(): void
    {
        $response = $this->actingAs($this->admin)
            ->get(route('manage.record.online.ajax.classification'));

        $response->assertStatus(200);
        $content = $response->getContent();

        $this->assertStringContainsString('Unclassification', $content);
        $this->assertStringContainsString(route('manage.record.online.classification', 'unclassification'), $content);

        // Controller unit test
        $controller = app(RecordOnlineController::class);
        $learningManage = app(\App\Http\Controllers\LearningManageController::class);
        $summary = $learningManage->getClassificationSummary(true);

        $this->assertNotEmpty($summary);
        $lastRow = $summary->last();
        $this->assertSame('Unclassification', $lastRow->classification_name);
        $this->assertSame('unclassification', $lastRow->slug);
        $this->assertGreaterThan(0, $lastRow->total_learning);
    }

    /**
     * Test Online Unclassification Detail Page and DataTable
     */
    public function test_online_unclassification_detail_and_datatable(): void
    {
        $viewResponse = $this->actingAs($this->admin)
            ->get(route('manage.record.online.classification', 'unclassification'));

        $viewResponse->assertStatus(200);
        $viewResponse->assertViewIs('main.record.online.classification');
        $viewResponse->assertSee('Unclassification');
        $viewResponse->assertSee('Back to KP OWL');

        // DataTable endpoint
        $dtResponse = $this->actingAs($this->admin)
            ->get(route('manage.record.online.classification.datatable', 'unclassification'));

        $dtResponse->assertStatus(200);
        $data = $dtResponse->json();
        $this->assertArrayHasKey('recordsTotal', $data);
        $this->assertArrayHasKey('recordsFiltered', $data);
        $this->assertGreaterThan(0, $data['recordsTotal']);
    }

    /**
     * Test Offline Classification Summary has Unclassification as last row and no Audit Isolated Classification
     */
    public function test_offline_classification_summary_has_unclassification_and_no_audit_isolated(): void
    {
        $response = $this->actingAs($this->admin)
            ->get(route('manage.record.offline.ajax.classification'));

        $response->assertStatus(200);
        $content = $response->getContent();

        $this->assertStringContainsString('Unclassification', $content);
        $this->assertStringNotContainsString('Audit Isolated Classification', $content);
        $this->assertStringContainsString(route('manage.record.offline.classification', 'unclassification'), $content);

        // Check controller summary
        $controller = app(RecordOfflineController::class);
        $summary = $controller->getClassificationSummary();

        $this->assertNotEmpty($summary);
        $lastRow = $summary->last();
        $this->assertSame('Unclassification', $lastRow->classification_name);
        $this->assertSame('unclassification', $lastRow->slug);
        $this->assertGreaterThan(0, $lastRow->programs);
        $this->assertGreaterThan(0, $lastRow->total_participants);

        // Ensure no row has 'Audit Isolated Classification'
        foreach ($summary as $row) {
            $this->assertNotSame('Audit Isolated Classification', $row->classification_name);
            $this->assertNotSame('audit-isolated-classification', $row->slug);
        }
    }

    /**
     * Test Legacy Audit Isolated Classification redirects 301 to Unclassification
     */
    public function test_legacy_offline_audit_isolated_classification_redirects(): void
    {
        $response = $this->actingAs($this->admin)
            ->get('/manage/record/offline/classification/audit-isolated-classification');

        $response->assertStatus(301);
        $response->assertRedirect(route('manage.record.offline.classification', 'unclassification'));
    }

    /**
     * Test Offline Unclassification Detail Page and DataTable
     */
    public function test_offline_unclassification_detail_and_datatable(): void
    {
        $viewResponse = $this->actingAs($this->admin)
            ->get(route('manage.record.offline.classification', 'unclassification'));

        $viewResponse->assertStatus(200);
        $viewResponse->assertViewIs('main.record.offline.classification');
        $viewResponse->assertSee('Unclassification');
        $viewResponse->assertSee('Back to Non KP OWL');

        // DataTable endpoint
        $dtResponse = $this->actingAs($this->admin)
            ->get(route('manage.record.offline.classification.datatable', 'unclassification'));

        $dtResponse->assertStatus(200);
        $data = $dtResponse->json();
        $this->assertArrayHasKey('recordsTotal', $data);
        $this->assertArrayHasKey('recordsFiltered', $data);
        $this->assertGreaterThan(0, $data['recordsTotal']);
    }

    /**
     * Test Export Endpoints for both Online and Offline
     */
    public function test_export_endpoints_work(): void
    {
        // Online exports
        $resCsvOnline = $this->actingAs($this->admin)
            ->get(route('manage.record.online.classification.export.csv', 'unclassification'));
        $resCsvOnline->assertStatus(200);

        // Offline exports
        $resCsvOffline = $this->actingAs($this->admin)
            ->get(route('manage.record.offline.classification.export.csv', 'unclassification'));
        $resCsvOffline->assertStatus(200);
    }
}
