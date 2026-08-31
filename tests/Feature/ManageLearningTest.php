<?php

namespace Tests\Feature;

use App\Models\Classification;
use App\Models\Content;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class ManageLearningTest extends TestCase
{
    public function test_authenticated_admin_can_access_manage_learning_page(): void
    {
        $user = User::where('role', '2')->first() ?? User::first();

        $queries = [];
        DB::listen(function ($query) use (&$queries) {
            $queries[] = $query->sql;
        });

        $start = microtime(true);
        $response = $this->actingAs($user)->get('/manage/learning');
        $duration = microtime(true) - $start;

        $response->assertStatus(200);
        $response->assertViewIs('main.learning.index');
        $response->assertSee('Manage Learning');
        $response->assertSee('Classification Summary');
        $response->assertViewHas('totalLearning');
        $response->assertViewHas('ongoing');
        $response->assertViewHas('finish');
        $response->assertViewHas('completionRate');
        $response->assertViewHas('learningHours', 35622.6);
        $response->assertViewHas('classification');

        echo PHP_EOL . "Feature Test - GET /manage/learning:" . PHP_EOL;
        echo "Status: " . $response->getStatusCode() . PHP_EOL;
        echo "Queries executed: " . count($queries) . PHP_EOL;
        echo "Request duration: " . round($duration, 4) . "s" . PHP_EOL;
        echo "Peak memory: " . round(memory_get_peak_usage(true) / 1024 / 1024, 2) . " MB" . PHP_EOL;
    }

    public function test_authenticated_admin_can_access_all_learning_page(): void
    {
        $user = User::where('role', '2')->first() ?? User::first();

        $response = $this->actingAs($user)->get('/manage/learning/all');
        $response->assertStatus(200);
        $response->assertViewIs('main.learning.all');
    }

    public function test_authenticated_admin_can_access_category_learning_page(): void
    {
        $user = User::where('role', '2')->first() ?? User::first();
        $classification = Classification::first();

        if ($classification) {
            $response = $this->actingAs($user)->get('/manage/learning/category/' . $classification->slug);
            $response->assertStatus(200);
            $response->assertViewIs('main.learning.all');
            $response->assertSee('Classification: ' . $classification->classification_name);
        }
    }

    public function test_authenticated_admin_can_access_content_learning_page(): void
    {
        $user = User::where('role', '2')->first() ?? User::first();
        $content = Content::first();

        if ($content) {
            $response = $this->actingAs($user)->get('/manage/learning/content/' . $content->slug);
            $response->assertStatus(200);
            $response->assertViewIs('main.learning.all');
            $response->assertSee('Content: ' . $content->title);
        }
    }

    public function test_authenticated_admin_can_fetch_datatable_with_server_side_pagination(): void
    {
        $user = User::where('role', '2')->first() ?? User::first();

        $response = $this->actingAs($user)->getJson('/manage/learning/datatable?draw=1&start=0&length=10');
        $response->assertStatus(200);
        $response->assertJsonStructure([
            'draw',
            'recordsTotal',
            'recordsFiltered',
            'data',
        ]);

        $json = $response->json();
        $this->assertEquals(1, $json['draw']);
        $this->assertGreaterThan(0, $json['recordsTotal']);
        $this->assertCount(10, $json['data']);
    }

    public function test_datatable_filtering_by_status_and_year(): void
    {
        $user = User::where('role', '2')->first() ?? User::first();

        $response = $this->actingAs($user)->getJson('/manage/learning/datatable?draw=1&start=0&length=10&status=optional&year=' . date('Y'));
        $response->assertStatus(200);

        $json = $response->json();
        $this->assertLessThanOrEqual(10, count($json['data']));
    }

    public function test_datatable_search(): void
    {
        $user = User::where('role', '2')->first() ?? User::first();

        $response = $this->actingAs($user)->getJson('/manage/learning/datatable?draw=1&start=0&length=10&search[value]=safety');
        $response->assertStatus(200);

        $json = $response->json();
        $this->assertLessThanOrEqual(10, count($json['data']));
    }

    public function test_authenticated_admin_can_search_employees_for_live_dropdown(): void
    {
        $user = User::where('role', '2')->first() ?? User::first();

        $response = $this->actingAs($user)->getJson('/manage/learning/search-employees?q=');
        $response->assertStatus(200);
        $response->assertJsonStructure(['results']);

        $json = $response->json();
        if (!empty($json['results'])) {
            $this->assertStringNotContainsString('👤', $json['results'][0]['text']);
            $this->assertStringNotContainsString('📚', $json['results'][0]['text']);
        }

        $responseWithTerm = $this->actingAs($user)->getJson('/manage/learning/search-employees?q=admin');
        $responseWithTerm->assertStatus(200);
        $responseWithTerm->assertJsonStructure(['results']);
    }

    public function test_authenticated_admin_can_search_titles_for_live_dropdown(): void
    {
        $user = User::where('role', '2')->first() ?? User::first();

        $response = $this->actingAs($user)->getJson('/manage/learning/search-titles?q=');
        $response->assertStatus(200);
        $response->assertJsonStructure(['results']);

        $responseWithTerm = $this->actingAs($user)->getJson('/manage/learning/search-titles?q=safety');
        $responseWithTerm->assertStatus(200);
        $responseWithTerm->assertJsonStructure(['results']);
    }

    public function test_authenticated_admin_can_download_filtered_learning_export(): void
    {
        $user = User::where('role', '2')->first() ?? User::first();

        $response = $this->actingAs($user)->get('/manage/learning/download?search=admin&status=finished&year=' . date('Y'));
        $response->assertStatus(200);
        $this->assertTrue($response->headers->get('content-type') === 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet' || $response->headers->has('content-disposition'));
    }

    public function test_datatable_filtering_by_more_filters_parameters(): void
    {
        $user = User::where('role', '2')->first() ?? User::first();

        $response = $this->actingAs($user)->getJson('/manage/learning/datatable?draw=1&start=0&length=10&from_date=2020-01-01&to_date=2030-12-31&program_type=mandatory&progress_range=1-25&duration=10-30');
        $response->assertStatus(200);

        $json = $response->json();
        $this->assertEquals(1, $json['draw']);
        $this->assertArrayHasKey('recordsTotal', $json);
        $this->assertArrayHasKey('data', $json);
    }

    public function test_datatable_filtering_by_progress_range_51_75_and_optional_program(): void
    {
        $user = User::where('role', '2')->first() ?? User::first();

        $response = $this->actingAs($user)->getJson('/manage/learning/datatable?draw=1&start=0&length=10&program_type=optional&progress_range=51-75%25&duration=lt10');
        $response->assertStatus(200);

        $json = $response->json();
        $this->assertEquals(1, $json['draw']);
        $this->assertArrayHasKey('recordsTotal', $json);
        $this->assertArrayHasKey('data', $json);
    }

    public function test_datatable_filtering_by_employee_id(): void
    {
        $user = User::where('role', '2')->first() ?? User::first();

        $response = $this->actingAs($user)->getJson('/manage/learning/datatable?draw=1&start=0&length=10&employee_id=' . $user->id);
        $response->assertStatus(200);

        $json = $response->json();
        $this->assertEquals(1, $json['draw']);
        $this->assertArrayHasKey('recordsTotal', $json);
        $this->assertArrayHasKey('data', $json);
    }

    public function test_datatable_filtering_by_learning_title(): void
    {
        $user = User::where('role', '2')->first() ?? User::first();

        $response = $this->actingAs($user)->getJson('/manage/learning/datatable?draw=1&start=0&length=10&learning_title=safety');
        $response->assertStatus(200);

        $json = $response->json();
        $this->assertEquals(1, $json['draw']);
        $this->assertArrayHasKey('recordsTotal', $json);
        $this->assertArrayHasKey('data', $json);
    }

    public function test_datatable_filtering_by_both_employee_and_learning_title(): void
    {
        $user = User::where('role', '2')->first() ?? User::first();

        $response = $this->actingAs($user)->getJson('/manage/learning/datatable?draw=1&start=0&length=10&employee_id=' . $user->id . '&learning_title=test');
        $response->assertStatus(200);

        $json = $response->json();
        $this->assertEquals(1, $json['draw']);
        $this->assertArrayHasKey('recordsTotal', $json);
        $this->assertArrayHasKey('data', $json);
    }

    public function test_download_export_with_employee_and_learning_title(): void
    {
        $user = User::where('role', '2')->first() ?? User::first();

        $response = $this->actingAs($user)->get('/manage/learning/download?employee_id=' . $user->id . '&learning_title=safety&status=all');
        $response->assertStatus(200);
        $this->assertTrue($response->headers->get('content-type') === 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet' || $response->headers->has('content-disposition'));
    }
}
