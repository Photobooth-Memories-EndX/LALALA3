<?php

namespace Tests\Feature;

use App\Http\Controllers\LearningManageController;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class RecordOnlineAsyncTest extends TestCase
{
    protected User $admin;
    protected ?User $regularUser;

    protected function setUp(): void
    {
        parent::setUp();

        // In OWL LMS: role 2 is Admin (has record_management), role 1 is Regular User (no record_management)
        $this->admin = User::where('role', '2')->first() ?? User::first();
        $this->regularUser = User::where('role', '1')->first();
    }

    /**
     * 1. Test Record Online page loads lightweight simplified overview shell without filter & table.
     */
    public function test_record_online_page_loads_lightweight_shell(): void
    {
        $queries = [];
        DB::listen(function ($query) use (&$queries) {
            $queries[] = $query->sql;
        });

        $start = microtime(true);
        $response = $this->actingAs($this->admin)->get('/manage/record/online');
        $duration = microtime(true) - $start;

        $response->assertStatus(200);
        $response->assertViewIs('main.record.online');
        $response->assertSee('KP OWL');
        $response->assertSee('Online Learning Record');
        $response->assertSee('kpi-summary-container', false);
        $response->assertSee('classification-summary-container', false);
        $response->assertSee('Classification Summary');
        $response->assertSee('Aggregate overview per classification category in KP OWL.');

        // Verify Quick Filter and Learning Table are strictly absent from landing page
        $response->assertDontSee('Quick Filter');
        $response->assertDontSee('Search employee or NIP');
        $response->assertDontSee('learning-table', false);
        $response->assertDontSee('btn-more-filters');
        $response->assertDontSee('dataTables.bootstrap5.min.css');

        // Verify that the massive aggregation query is NOT executed during initial page load
        $hasHeavyUnion = false;
        foreach ($queries as $sql) {
            if (str_contains(strtolower($sql), 'union') && str_contains(strtolower($sql), 'playlist_content_users')) {
                $hasHeavyUnion = true;
                break;
            }
        }
        $this->assertFalse($hasHeavyUnion, 'Initial GET /manage/record/online must not execute heavy classification aggregation query.');

        echo PHP_EOL . "Test Shell Load: " . round($duration * 1000, 2) . " ms, Queries: " . count($queries) . PHP_EOL;
    }

    /**
     * 2. Test Summary endpoint requires permission.
     */
    public function test_summary_endpoint_requires_permission(): void
    {
        // Guest is redirected
        $guestResponse = $this->get('/manage/record/online/ajax/summary');
        $this->assertTrue($guestResponse->isRedirect() || $guestResponse->getStatusCode() === 401);

        // Regular user without permission gets 403
        if ($this->regularUser) {
            $unauthorizedResponse = $this->actingAs($this->regularUser)->get('/manage/record/online/ajax/summary');
            $unauthorizedResponse->assertStatus(403);
        }
    }

    /**
     * 3. Test Classification endpoint requires permission.
     */
    public function test_classification_endpoint_requires_permission(): void
    {
        // Guest is redirected
        $guestResponse = $this->get('/manage/record/online/ajax/classification');
        $this->assertTrue($guestResponse->isRedirect() || $guestResponse->getStatusCode() === 401);

        // Regular user without permission gets 403
        if ($this->regularUser) {
            $unauthorizedResponse = $this->actingAs($this->regularUser)->get('/manage/record/online/ajax/classification');
            $unauthorizedResponse->assertStatus(403);
        }
    }

    /**
     * 4. Test Summary contract and values.
     */
    public function test_summary_contract_and_values(): void
    {
        $response = $this->actingAs($this->admin)->getJson('/manage/record/online/ajax/summary');
        $response->assertStatus(200);

        $response->assertJsonStructure([
            'success',
            'total_learning',
            'ongoing',
            'finished',
            'completion_rate',
            'total_learning_hours',
            'data',
            'html',
        ]);

        $json = $response->json();
        $this->assertEquals(410301, $json['total_learning']);
        $this->assertEquals(40075, $json['ongoing']);
        $this->assertEquals(370226, $json['finished']);
        $this->assertEquals(90.2, $json['completion_rate']);
        $this->assertEquals(87539.2, $json['total_learning_hours']);

        // Check HTML partial rendered
        $this->assertStringContainsString('Total Learning', $json['html']);
        $this->assertStringContainsString('410,301', $json['html']);
        $this->assertStringContainsString('40,075', $json['html']);
        $this->assertStringContainsString('370,226', $json['html']);
        $this->assertStringContainsString('90.2%', $json['html']);
    }

    /**
     * 5. Test Classification contract and response.
     */
    public function test_classification_contract_and_response(): void
    {
        // HTML response test
        $responseHtml = $this->actingAs($this->admin)->get('/manage/record/online/ajax/classification');
        $responseHtml->assertStatus(200);
        $responseHtml->assertSee('Classification');
        $responseHtml->assertSee('Learning Hours');

        // JSON response test
        $responseJson = $this->actingAs($this->admin)->getJson('/manage/record/online/ajax/classification?format=json');
        $responseJson->assertStatus(200);
        $responseJson->assertJsonStructure([
            'success',
            'data',
        ]);

        $classifications = $responseJson->json('data');
        $this->assertIsArray($classifications);
        $this->assertCount(10, $classifications);
    }

    /**
     * 6. Test legacy route remains accessible.
     */
    public function test_legacy_route_remains_accessible(): void
    {
        $response = $this->actingAs($this->admin)->get('/manage/learning');
        $response->assertStatus(200);
        $response->assertViewIs('main.learning.index');
        $response->assertViewHas('totalLearning', 410301);
        $response->assertViewHas('ongoing', 40075);
        $response->assertViewHas('finish', 370226);
        $response->assertViewHas('completionRate', 90.2);
        $response->assertViewHas('learningHours', 87539.2);
        $response->assertViewHas('classification');
    }

    /**
     * 7. Test exact data parity between legacy and new calculation.
     */
    public function test_data_parity_between_legacy_and_new_endpoints(): void
    {
        $legacy = app(LearningManageController::class)->getSummaryData();
        $asyncResponse = $this->actingAs($this->admin)->getJson('/manage/record/online/ajax/summary');
        $async = $asyncResponse->json();

        $this->assertEquals($legacy['totalLearning'], $async['total_learning']);
        $this->assertEquals($legacy['ongoing'], $async['ongoing']);
        $this->assertEquals($legacy['finish'], $async['finished']);
        $this->assertEquals($legacy['completionRate'], $async['completion_rate']);
        $this->assertEquals($legacy['learningHours'], $async['total_learning_hours']);

        // Parity on classification
        $classResponse = $this->actingAs($this->admin)->getJson('/manage/record/online/ajax/classification?format=json');
        $classData = $classResponse->json('data');
        $this->assertCount(count($legacy['classification']), $classData);

        $legacySumHours = round((float) $legacy['classification']->sum('learning_hours'), 1);
        $asyncSumHours = round(collect($classData)->sum('learning_hours'), 1);
        $this->assertEquals($legacySumHours, $asyncSumHours);
    }

    /**
     * 8. Test legacy explorer (/manage/learning/all) strictly retains Quick Filter and Learning Table.
     */
    public function test_legacy_manage_learning_all_retains_quick_filter_and_table(): void
    {
        $response = $this->actingAs($this->admin)->get('/manage/learning/all');
        $response->assertStatus(200);
        $response->assertViewIs('main.learning.all');
        $response->assertSee('Quick Filter');
        $response->assertSee('Search employee or NIP');
        $response->assertSee('learning-table', false);
    }
}
