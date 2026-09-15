<?php

namespace Tests\Unit;

use App\Models\Content;
use App\Models\PlaylistContentUser;
use App\Models\TrainingPlan;
use App\Models\User;
use App\Services\LearningHours\ActualLearningHourCalculator;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class ActualLearningHourCalculatorTest extends TestCase
{
    protected ActualLearningHourCalculator $calculator;

    protected function setUp(): void
    {
        parent::setUp();
        $this->calculator = new ActualLearningHourCalculator();
    }

    /**
     * @dataProvider requiredSecondsProvider
     */
    public function test_it_calculates_actual_learning_hours_with_four_decimal_precision(int $seconds, float $expectedHours): void
    {
        $calculated = $this->calculator->calculate($seconds);
        $this->assertSame($expectedHours, $calculated, "Failed calculating hours for {$seconds} seconds");
    }

    public function requiredSecondsProvider(): array
    {
        return [
            '1200 seconds -> 0.3333' => [1200, 0.3333],
            '1230 seconds -> 0.3417' => [1230, 0.3417],
            '1800 seconds -> 0.5000' => [1800, 0.5000],
            '2700 seconds -> 0.7500' => [2700, 0.7500],
            '3600 seconds -> 1.0000' => [3600, 1.0000],
            '4815 seconds -> 1.3375' => [4815, 1.3375],
        ];
    }

    public function test_it_handles_null_zero_negative_and_large_seconds(): void
    {
        $this->assertNull($this->calculator->calculate(null));
        $this->assertSame(0.0, $this->calculator->calculate(0));
        $this->assertSame(0.0, $this->calculator->calculate(-150));
        $this->assertSame(0.0, $this->calculator->calculate(-0.01));

        // Large seconds (100,000s / 3600 = 27.777777... -> 27.7778)
        $this->assertSame(27.7778, $this->calculator->calculate(100000));

        // String numeric
        $this->assertSame(0.3417, $this->calculator->calculate('1230'));
        $this->assertNull($this->calculator->calculate('invalid-string'));
    }

    public function test_calculator_does_not_execute_database_queries(): void
    {
        DB::flushQueryLog();
        DB::enableQueryLog();

        $this->calculator->calculate(1230);
        $this->calculator->calculate(3600);
        $this->calculator->resolveActualSeconds(1230, '1', 100, '00:30:00');
        $this->calculator->calculateFromTracking(1230, '1', 100, '00:30:00');
        $this->calculator->parseContentDurationToSeconds('01:30:00');

        $this->assertCount(0, DB::getQueryLog(), 'Calculator must be pure and perform 0 database queries.');
    }

    public function test_calculator_calculates_max_learning_hours_and_cap_seconds(): void
    {
        $this->assertNull($this->calculator->calculateMaxLearningHours(null));
        $this->assertNull($this->calculator->calculateMaxLearningHours(0));
        $this->assertNull($this->calculator->calculateMaxLearningHours(-0.5));

        // 0.33 LH -> 0.66 LH -> 2376 seconds
        $this->assertSame(0.66, $this->calculator->calculateMaxLearningHours(0.33));
        $this->assertSame(2376, $this->calculator->calculateMaxCapSeconds(0.33));

        // 0.5 LH -> 1.0 LH -> 3600 seconds
        $this->assertSame(1.0, $this->calculator->calculateMaxLearningHours(0.5));
        $this->assertSame(3600, $this->calculator->calculateMaxCapSeconds(0.5));

        // 1.0 LH -> 2.0 LH -> 7200 seconds
        $this->assertSame(2.0, $this->calculator->calculateMaxLearningHours(1.0));
        $this->assertSame(7200, $this->calculator->calculateMaxCapSeconds(1.0));

        // calculateFromTracking with maxLearningHours clamps result
        // 3600 seconds = 1.0 LH. If max is 0.66, must return 0.66
        $clampedHours = $this->calculator->calculateFromTracking(
            currentTime: 0,
            finish: '0',
            progress: 50,
            contentDuration: null,
            actualWatchSeconds: 3600,
            maxLearningHours: 0.66
        );
        $this->assertSame(0.66, $clampedHours);
    }

    public function test_historical_fallback_resolves_actual_seconds_accurately(): void
    {
        // 1. If current_time > 0: use current_time regardless of finish
        $seconds = $this->calculator->resolveActualSeconds(
            currentTime: 1230,
            finish: '0',
            progress: 34,
            contentDuration: '01:00:00'
        );
        $this->assertSame(1230, $seconds);

        // 2. If current_time = 0 and finished (finish = 1 or progress = 100): fallback to content duration
        $secondsFinished = $this->calculator->resolveActualSeconds(
            currentTime: 0,
            finish: '1',
            progress: 100,
            contentDuration: '00:04:30' // 270s
        );
        $this->assertSame(270, $secondsFinished);

        $secondsProgress100 = $this->calculator->resolveActualSeconds(
            currentTime: 0,
            finish: '0',
            progress: 100,
            contentDuration: '00:15:00' // 900s
        );
        $this->assertSame(900, $secondsProgress100);

        // 3. If current_time = 0 and NOT finished: return 0, do not assume full duration
        $secondsUnfinished = $this->calculator->resolveActualSeconds(
            currentTime: 0,
            finish: '0',
            progress: 50,
            contentDuration: '01:00:00'
        );
        $this->assertSame(0, $secondsUnfinished);
    }

    public function test_batch_calculation_processes_multiple_records_in_memory_without_queries(): void
    {
        DB::flushQueryLog();
        DB::enableQueryLog();

        $records = [
            ['id' => 1, 'content_id' => 10, 'current_time' => 1230, 'finish' => '0', 'progress' => 34],
            ['id' => 2, 'content_id' => 20, 'current_time' => 0, 'finish' => '1', 'progress' => 100],
            ['id' => 3, 'content_id' => 30, 'current_time' => 0, 'finish' => '0', 'progress' => 10],
            ['id' => 4, 'content_id' => 20, 'current_time' => 3600, 'finish' => '1', 'progress' => 100],
        ];

        $preloadedMap = [
            10 => '01:00:00', // 3600s
            20 => '00:30:00', // 1800s
            30 => '00:45:00', // 2700s
        ];

        $batchResults = $this->calculator->calculateBatch($records, $preloadedMap);

        $this->assertCount(0, DB::getQueryLog(), 'Batch calculation must execute 0 DB queries.');
        $this->assertEquals(0.3417, $batchResults[1]);
        $this->assertEquals(0.5000, $batchResults[2]);
        $this->assertEquals(0.0000, $batchResults[3]);
        $this->assertEquals(1.0000, $batchResults[4]);
    }

    public function test_playlist_content_user_save_does_not_cause_n_plus_one_queries(): void
    {
        // Fetch 50 records WITHOUT eager loading 'content'
        $records = PlaylistContentUser::take(50)->get();
        $this->assertNotEmpty($records);

        DB::flushQueryLog();
        DB::enableQueryLog();

        foreach ($records as $record) {
            $record->save();
        }

        $queries = DB::getQueryLog();
        $contentQueries = array_filter($queries, function ($q) {
            return str_contains(strtolower($q['query']), 'from `content`');
        });

        $this->assertCount(0, $contentQueries, 'PlaylistContentUser::save() must NEVER query content table in loop.');
    }

    public function test_performance_simulation_with_one_hundred_records_avoids_n_plus_one(): void
    {
        $records = PlaylistContentUser::take(100)->get();
        $this->assertGreaterThanOrEqual(1, $records->count());

        DB::flushQueryLog();
        DB::enableQueryLog();

        // 1 single query to preload all unique content durations
        $contentIds = $records->pluck('content_id')->unique()->filter()->all();
        $contentMap = DB::table('content')
            ->whereIn('id', $contentIds)
            ->pluck('duration', 'id')
            ->all();

        // Calculate 100 records in-memory
        $calculated = $this->calculator->calculateBatch($records, $contentMap);

        // Group updates to minimize queries
        $grouped = [];
        foreach ($calculated as $id => $hours) {
            $key = (string) $hours;
            $grouped[$key][] = $id;
        }

        foreach ($grouped as $hoursKey => $ids) {
            DB::table('playlist_content_users')
                ->whereIn('id', $ids)
                ->update(['learning_hour' => (float) $hoursKey]);
        }

        $totalQueries = count(DB::getQueryLog());

        // For 100 records, queries should be 1 (preload) + ~5-15 (grouped bulk updates), NEVER 100+
        $this->assertLessThanOrEqual(
            20,
            $totalQueries,
            "Total queries ({$totalQueries}) for 100 records exceeded threshold! Expected < 20."
        );
    }

    public function test_database_stores_and_retrieves_exact_four_decimal_precision(): void
    {
        // Check schema column definition
        $column = DB::select("SHOW COLUMNS FROM playlist_content_users WHERE Field = 'learning_hour'");
        $this->assertNotEmpty($column);
        $this->assertSame('decimal(10,4)', strtolower($column[0]->Type));

        // Find or create test record
        $user = User::first();
        $content = Content::first();
        $plan = TrainingPlan::first();

        $this->assertNotNull($user);
        $this->assertNotNull($content);
        $this->assertNotNull($plan);

        $testRecord = PlaylistContentUser::updateOrCreate(
            [
                'user_id' => $user->id,
                'trainingplan_id' => $plan->id,
                'content_id' => $content->id,
            ],
            [
                'progress' => 34,
                'current_time' => 1230,
                'duration_seconds' => 3600,
                'learning_status' => 'in_progress',
                'finish' => '0',
            ]
        );

        // Explicitly set 0.3417 and save without triggering hook to test raw DB precision
        DB::table('playlist_content_users')
            ->where('id', $testRecord->id)
            ->update(['learning_hour' => 0.3417]);

        // Verify direct from MySQL raw query
        $raw = DB::table('playlist_content_users')
            ->where('id', $testRecord->id)
            ->value('learning_hour');

        // MUST be '0.3417' and NOT '0.3' or '0.3000'
        $this->assertSame('0.3417', (string) $raw, "Database truncated decimal value! Got: {$raw}");

        // Test Eloquent model cast
        $fresh = PlaylistContentUser::find($testRecord->id);
        $this->assertEquals(0.3417, (float) $fresh->learning_hour);

        // Verify other tracking fields were not corrupted
        $this->assertEquals(1230, $fresh->current_time);
        $this->assertEquals(3600, $fresh->duration_seconds);
        $this->assertEquals(34, $fresh->progress);
        $this->assertSame('0', (string) $fresh->finish);
        $this->assertSame('in_progress', $fresh->learning_status);
    }
}
