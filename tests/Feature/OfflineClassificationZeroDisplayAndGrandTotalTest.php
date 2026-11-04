<?php

namespace Tests\Feature;

use App\Models\Classification;
use App\Models\OfflineTraining;
use App\Models\OfflineTrainingUser;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class OfflineClassificationZeroDisplayAndGrandTotalTest extends TestCase
{
    protected User $admin;
    protected ?User $regularUser;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::where('role', '2')->first() ?? User::first();
        $this->regularUser = User::where('role', '1')->first();
    }

    /**
     * Test 1: Database has classifications but no offline training records.
     * Expected: All classifications appear with zero values, including GRAND TOTAL.
     * No "Unable to load classification summary" error.
     */
    public function test_scenario_1_zero_offline_training_records_displays_all_classifications_and_zero_grand_total(): void
    {
        DB::beginTransaction();

        try {
            // Delete all offline records in transaction
            DB::table('offline_training_users')->delete();
            DB::table('offline_trainings')->delete();

            // 1. AJAX JSON request
            $response = $this->actingAs($this->admin)->getJson(route('manage.record.offline.ajax.classification'));
            $response->assertStatus(200);
            $response->assertJson(['success' => true]);

            $data = collect($response->json('data'));
            $grandTotal = $response->json('grand_total');

            // All master classifications from database must appear
            $masterClasses = DB::table('classification')
                ->where(function ($q) {
                    $q->whereNull('is_active')->orWhere('is_active', 1);
                })
                ->orderBy('classification_name', 'asc')
                ->get();

            $this->assertNotEmpty($masterClasses);

            foreach ($masterClasses as $mc) {
                $row = $data->firstWhere('classification_name', $mc->classification_name);
                $this->assertNotNull($row, "Classification '{$mc->classification_name}' must be displayed even with 0 records.");
                $this->assertEquals(0, $row['programs'], "Programs must be 0 for {$mc->classification_name}");
                $this->assertEquals(0, $row['total_participants'], "Total participants must be 0 for {$mc->classification_name}");
                $this->assertEquals(0.0, (float) $row['learning_hours'], "Learning hours must be 0.0 for {$mc->classification_name}");
                $this->assertEquals(0, $row['ongoing'], "Ongoing must be 0 for {$mc->classification_name}");
                $this->assertEquals(0, $row['finished'], "Finished must be 0 for {$mc->classification_name}");
            }

            // Unclassification row must be present at the bottom with 0
            $unclass = $data->firstWhere('slug', 'unclassification');
            $this->assertNotNull($unclass, "Unclassification must be present even with 0 records.");
            $this->assertEquals(0, $unclass['programs']);
            $this->assertEquals(0, $unclass['total_participants']);
            $this->assertEquals(0.0, (float) $unclass['learning_hours']);

            // GRAND TOTAL must be all 0s
            $this->assertNotNull($grandTotal, "grand_total must be present in response.");
            $this->assertEquals(0, $grandTotal['programs']);
            $this->assertEquals(0, $grandTotal['total_participants']);
            $this->assertEquals(0.0, (float) $grandTotal['learning_hours']);
            $this->assertEquals(0, $grandTotal['ongoing']);
            $this->assertEquals(0, $grandTotal['finished']);

            // 2. HTML format request
            $htmlResponse = $this->actingAs($this->admin)->get(route('manage.record.offline.ajax.classification', ['format' => 'html']));
            $htmlResponse->assertStatus(200);
            $htmlResponse->assertSee('GRAND TOTAL');
            $htmlResponse->assertSee('0.0 Hours');
            $htmlResponse->assertDontSee('No offline training classifications found.');
            $htmlResponse->assertDontSee('Unable to load classification summary.');
        } finally {
            DB::rollBack();
        }
    }

    /**
     * Test 2: Database has offline training records across multiple classifications.
     * Expected: Correct classification aggregation and correct GRAND TOTAL.
     */
    public function test_scenario_2_multiple_classifications_aggregated_correctly_with_grand_total(): void
    {
        DB::beginTransaction();

        try {
            DB::table('offline_training_users')->delete();
            DB::table('offline_trainings')->delete();

            // Training 1: Leadership (2 participants: 1 ongoing 5h, 1 finished 5h)
            $t1Id = DB::table('offline_trainings')->insertGetId([
                'course_name'         => 'Leadership Mastery',
                'type'                => 'In-House',
                'main_classification' => 'Leadership',
                'start_date'          => '2026-03-01',
                'finish_date'         => '2026-03-05',
                'created_at'          => now(),
                'updated_at'          => now(),
            ]);
            DB::table('offline_training_users')->insert([
                [
                    'offline_training_id' => $t1Id,
                    'employee_id'         => 'EMP001',
                    'name'                => 'John Leader',
                    'learning_hours'      => 5.0,
                    'progress'            => 50.0,
                    'created_at'          => now(),
                    'updated_at'          => now(),
                ],
                [
                    'offline_training_id' => $t1Id,
                    'employee_id'         => 'EMP002',
                    'name'                => 'Jane Leader',
                    'learning_hours'      => 5.0,
                    'progress'            => 100.0,
                    'created_at'          => now(),
                    'updated_at'          => now(),
                ],
            ]);

            // Training 2: Innovation (1 participant: finished 10h)
            $t2Id = DB::table('offline_trainings')->insertGetId([
                'course_name'         => 'Design Thinking Sprint',
                'type'                => 'Public',
                'main_classification' => 'Innovation',
                'start_date'          => '2026-04-01',
                'finish_date'         => '2026-04-02',
                'created_at'          => now(),
                'updated_at'          => now(),
            ]);
            DB::table('offline_training_users')->insert([
                'offline_training_id' => $t2Id,
                'employee_id'         => 'EMP003',
                'name'                => 'Alex Innovator',
                'learning_hours'      => 10.0,
                'progress'            => 100.0,
                'created_at'          => now(),
                'updated_at'          => now(),
            ]);

            $response = $this->actingAs($this->admin)->getJson(route('manage.record.offline.ajax.classification'));
            $response->assertStatus(200);

            $data = collect($response->json('data'));
            $grandTotal = $response->json('grand_total');

            // Verify Leadership
            $leadership = $data->firstWhere('classification_name', 'Leadership');
            $this->assertNotNull($leadership);
            $this->assertEquals(1, $leadership['programs']);
            $this->assertEquals(2, $leadership['total_participants']);
            $this->assertEquals(10.0, (float) $leadership['learning_hours']);
            $this->assertEquals(1, $leadership['ongoing']);
            $this->assertEquals(1, $leadership['finished']);

            // Verify Innovation
            $innovation = $data->firstWhere('classification_name', 'Innovation');
            $this->assertNotNull($innovation);
            $this->assertEquals(1, $innovation['programs']);
            $this->assertEquals(1, $innovation['total_participants']);
            $this->assertEquals(10.0, (float) $innovation['learning_hours']);
            $this->assertEquals(0, $innovation['ongoing']);
            $this->assertEquals(1, $innovation['finished']);

            // Verify GRAND TOTAL equals sum of all columns
            $sumPrograms = $data->sum('programs');
            $sumParticipants = $data->sum('total_participants');
            $sumHours = round($data->sum('learning_hours'), 2);
            $sumOngoing = $data->sum('ongoing');
            $sumFinished = $data->sum('finished');

            $this->assertEquals($sumPrograms, $grandTotal['programs']);
            $this->assertEquals($sumParticipants, $grandTotal['total_participants']);
            $this->assertEquals($sumHours, (float) $grandTotal['learning_hours']);
            $this->assertEquals($sumOngoing, $grandTotal['ongoing']);
            $this->assertEquals($sumFinished, $grandTotal['finished']);

            $this->assertEquals(2, $grandTotal['programs']);
            $this->assertEquals(3, $grandTotal['total_participants']);
            $this->assertEquals(20.0, (float) $grandTotal['learning_hours']);
            $this->assertEquals(1, $grandTotal['ongoing']);
            $this->assertEquals(2, $grandTotal['finished']);

            // Check HTML view
            $htmlRes = $this->actingAs($this->admin)->get(route('manage.record.offline.ajax.classification', ['format' => 'html']));
            $htmlRes->assertStatus(200);
            $htmlRes->assertSee('GRAND TOTAL');
            $htmlRes->assertSee('20 Hours');
        } finally {
            DB::rollBack();
        }
    }

    /**
     * Test 3: Some classifications have records while others have none.
     * Expected: All classifications remain visible, with zero values for empty categories.
     */
    public function test_scenario_3_empty_categories_remain_visible_with_zeros(): void
    {
        DB::beginTransaction();

        try {
            DB::table('offline_training_users')->delete();
            DB::table('offline_trainings')->delete();

            // Insert data only for 'Job Functional'
            $tId = DB::table('offline_trainings')->insertGetId([
                'course_name'         => 'Advanced CNC Operation',
                'type'                => 'Workshop',
                'main_classification' => 'Job Functional',
                'start_date'          => '2026-05-10',
                'finish_date'         => '2026-05-12',
                'created_at'          => now(),
                'updated_at'          => now(),
            ]);
            DB::table('offline_training_users')->insert([
                'offline_training_id' => $tId,
                'employee_id'         => 'EMP004',
                'name'                => 'Budi Operator',
                'learning_hours'      => 12.5,
                'progress'            => 100.0,
                'created_at'          => now(),
                'updated_at'          => now(),
            ]);

            $response = $this->actingAs($this->admin)->getJson(route('manage.record.offline.ajax.classification'));
            $response->assertStatus(200);

            $data = collect($response->json('data'));

            // Job Functional has records
            $jf = $data->firstWhere('classification_name', 'Job Functional');
            $this->assertNotNull($jf);
            $this->assertEquals(1, $jf['programs']);
            $this->assertEquals(1, $jf['total_participants']);
            $this->assertEquals(12.5, (float) $jf['learning_hours']);
            $this->assertEquals(1, $jf['finished']);

            // Other master categories MUST still exist and have 0
            $iso = $data->firstWhere('classification_name', 'ISO Standard');
            $this->assertNotNull($iso, 'ISO Standard must remain visible even with 0 records.');
            $this->assertEquals(0, $iso['programs']);
            $this->assertEquals(0, $iso['total_participants']);
            $this->assertEquals(0.0, (float) $iso['learning_hours']);

            $maintenance = $data->firstWhere('classification_name', 'Maintenance');
            $this->assertNotNull($maintenance, 'Maintenance must remain visible even with 0 records.');
            $this->assertEquals(0, $maintenance['programs']);

            $she = $data->firstWhere('classification_name', 'SHE');
            $this->assertNotNull($she, 'SHE must remain visible even with 0 records.');
            $this->assertEquals(0, $she['programs']);

            // Grand total matches sum
            $grandTotal = $response->json('grand_total');
            $this->assertEquals(1, $grandTotal['programs']);
            $this->assertEquals(1, $grandTotal['total_participants']);
            $this->assertEquals(12.5, (float) $grandTotal['learning_hours']);
            $this->assertEquals(0, $grandTotal['ongoing']);
            $this->assertEquals(1, $grandTotal['finished']);
        } finally {
            DB::rollBack();
        }
    }

    /**
     * Test 4: Error handling and retry capability.
     * Expected: Unauthorized requests are rejected; UI contains retry handler and error state.
     */
    public function test_scenario_4_error_handling_and_retry_preserved(): void
    {
        // 1. Unauthenticated request is redirected or rejected
        $guestResponse = $this->get(route('manage.record.offline.ajax.classification'));
        $this->assertTrue($guestResponse->isRedirect() || $guestResponse->getStatusCode() === 401);

        // 2. Landing page contains the retry button and error handling logic in JavaScript
        $landingResponse = $this->actingAs($this->admin)->get(route('manage.record.offline'));
        $landingResponse->assertStatus(200);
        $landingResponse->assertSee('offline-classification-summary-container');
        $landingResponse->assertSee('btn-retry-offline-classification');
        $landingResponse->assertSee('Unable to load classification summary.');
    }

    /**
     * Test 5: Verify no regressions on KP OWL (Online) and All Record pages.
     */
    public function test_no_regression_on_online_and_all_record_pages(): void
    {
        $onlineResponse = $this->actingAs($this->admin)->get('/manage/record/online');
        $onlineResponse->assertStatus(200);

        $allResponse = $this->actingAs($this->admin)->get('/manage/record/all');
        $allResponse->assertStatus(200);
    }
}
