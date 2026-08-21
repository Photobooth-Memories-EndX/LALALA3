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
        }
    }

    public function test_authenticated_admin_can_fetch_datatable(): void
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
    }
}
