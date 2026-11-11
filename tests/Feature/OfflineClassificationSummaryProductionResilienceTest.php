<?php

namespace Tests\Feature;

use App\Models\User;
use App\Http\Controllers\Manage\Record\RecordOfflineController;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class OfflineClassificationSummaryProductionResilienceTest extends TestCase
{
    use DatabaseTransactions;

    protected User $adminUser;

    protected function setUp(): void
    {
        parent::setUp();

        $this->adminUser = User::where('role', '2')->first() ?? User::first();
        $this->actingAs($this->adminUser);
    }

    public function test_offline_ajax_classification_returns_json_with_expected_structure(): void
    {
        $response = $this->getJson(route('manage.record.offline.ajax.classification'));

        $response->assertStatus(200);
        $response->assertJsonStructure([
            'success',
            'data',
            'grand_total' => [
                'programs',
                'content',
                'total_participants',
                'total_learning',
                'learning_hours',
                'ongoing',
                'finished',
            ],
            'html',
        ]);
        $this->assertTrue($response->json('success'));
    }

    public function test_offline_ajax_classification_via_post_succeeds(): void
    {
        $response = $this->postJson(route('manage.record.offline.ajax.classification'));

        $response->assertStatus(200);
        $this->assertTrue($response->json('success'));
    }

    public function test_offline_ajax_classification_html_format_returns_rendered_view(): void
    {
        $response = $this->get(route('manage.record.offline.ajax.classification', ['format' => 'html']));

        $response->assertStatus(200);
        $response->assertSee('offline-classification-table');
        $response->assertSee('GRAND TOTAL');
    }

    public function test_offline_classification_summary_data_matches_exact_production_baseline(): void
    {
        $controller = app(RecordOfflineController::class);
        $summary = $controller->getClassificationSummaryData();

        $this->assertArrayHasKey('grand_total', $summary);
        $this->assertArrayHasKey('classifications', $summary);

        $gt = $summary['grand_total'];
        $this->assertEquals(217, $gt['programs'], 'Total programs should match baseline 217');
        $this->assertEquals(3535, $gt['total_participants'], 'Total participants should match baseline 3535');
        $this->assertEquals(33648.5, $gt['learning_hours'], 'Learning hours should match baseline 33648.5');
        $this->assertEquals(3273, $gt['ongoing'], 'Ongoing count should match baseline 3273');
        $this->assertEquals(40, $gt['finished'], 'Finished count should match baseline 40');

        $classifications = $summary['classifications'];
        $this->assertCount(12, $classifications, 'Should have 12 classifications in total');

        $names = $classifications->pluck('classification_name')->all();
        $this->assertContains('Basic & Common Duties', $names);
        $this->assertContains('Compliance', $names);
        $this->assertContains('Innovation', $names);
        $this->assertContains('ISO Standard', $names);
        $this->assertContains('Job Functional', $names);
        $this->assertContains('Language', $names);
        $this->assertContains('Leadership', $names);
        $this->assertContains('Maintenance', $names);
        $this->assertContains('Other', $names);
        $this->assertContains('SHE', $names);
        $this->assertContains('Technology', $names);
        $this->assertContains('Unclassification', $names);
    }

    public function test_offline_ajax_summary_kpi_works_via_get_and_post(): void
    {
        $getResponse = $this->getJson(route('manage.record.offline.ajax.summary'));
        $getResponse->assertStatus(200);
        $getResponse->assertJsonStructure(['total_trainings', 'total_participants', 'total_learning_hours', 'finished', 'ongoing', 'completion_rate', 'html']);

        $postResponse = $this->postJson(route('manage.record.offline.ajax.summary'));
        $postResponse->assertStatus(200);
        $postResponse->assertJsonStructure(['total_trainings', 'total_participants', 'total_learning_hours', 'finished', 'ongoing', 'completion_rate', 'html']);
    }

    public function test_soft_deleted_classification_is_excluded_from_summary(): void
    {
        if (!\Illuminate\Support\Facades\Schema::hasColumn('classification', 'deleted_at')) {
            $this->markTestSkipped('Classification table does not have deleted_at column.');
        }

        // Insert a soft-deleted classification
        $testSlug = 'audit-test-soft-deleted-' . uniqid();
        $id = \Illuminate\Support\Facades\DB::table('classification')->insertGetId([
            'classification_name' => 'Audit Soft Deleted Test',
            'slug'                => $testSlug,
            'is_active'           => 1,
            'deleted_at'          => now(),
            'created_at'          => now(),
            'updated_at'          => now(),
        ]);

        $controller = app(RecordOfflineController::class);
        $summary = $controller->getClassificationSummaryData();
        $names = $summary['classifications']->pluck('classification_name')->all();

        $this->assertNotContains('Audit Soft Deleted Test', $names, 'Soft deleted classification must not appear in summary');
    }

    public function test_unauthenticated_requests_are_rejected(): void
    {
        \Illuminate\Support\Facades\Auth::logout();

        // AJAX request returns 401
        $ajaxResponse = $this->getJson(route('manage.record.offline.ajax.classification'));
        $ajaxResponse->assertStatus(401);

        // Non-AJAX browser request redirects to login
        $browserResponse = $this->get(route('manage.record.offline.ajax.classification'));
        $browserResponse->assertStatus(302);
        $browserResponse->assertRedirect('/login');
    }
}
