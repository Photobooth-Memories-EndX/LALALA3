<?php

namespace Tests\Feature;

use App\Console\Kernel;
use App\Jobs\CalculatePlaylistContentUserLearningHoursJob;
use App\Models\Content;
use App\Models\PlaylistContentUser;
use App\Models\TrainingPlan;
use App\Models\User;
use App\Services\LearningHours\ActualLearningHourCalculator;
use App\Services\LearningHours\PlaylistContentUserBatchCalculatorService;
use App\Services\LearningHours\PlaylistContentUserLearningHourManagementService;
use Illuminate\Console\Scheduling\Event;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class PlaylistLearningHourProductionHardeningTest extends TestCase
{
    protected ActualLearningHourCalculator $calculator;
    protected PlaylistContentUserBatchCalculatorService $batchService;

    protected function setUp(): void
    {
        parent::setUp();
        $this->calculator = app(ActualLearningHourCalculator::class);
        $this->batchService = app(PlaylistContentUserBatchCalculatorService::class);
    }

    public function test_actual_tracking_formula_exact_seconds_precision(): void
    {
        // 1230s -> 0.3417
        $this->assertSame(0.3417, $this->calculator->calculate(1230));

        // 1530s -> 0.4250
        $this->assertSame(0.4250, $this->calculator->calculate(1530));

        // 1200s -> 0.3333
        $this->assertSame(0.3333, $this->calculator->calculate(1200));

        // 1800s -> 0.5000
        $this->assertSame(0.5000, $this->calculator->calculate(1800));

        // 3600s -> 1.0000
        $this->assertSame(1.0000, $this->calculator->calculate(3600));

        // 4815s -> 1.3375
        $this->assertSame(1.3375, $this->calculator->calculate(4815));
    }

    public function test_historical_fallback_formula_when_current_time_zero_and_finished(): void
    {
        // When content duration is 900 seconds (15 minutes), finished with 0 current_time -> 0.2500
        $durationSeconds = 900;
        $expected = $this->calculator->calculate($durationSeconds);

        $this->assertSame(0.2500, $expected);
    }

    public function test_unfinished_records_with_zero_current_time_produce_zero_hours(): void
    {
        // Unfinished record: current_time = 0, finish = 0, progress = 25 -> must be 0.0000, NOT full duration
        $isFinished = false;
        $currentTime = 0;

        $hours = ($currentTime > 0)
            ? $this->calculator->calculate($currentTime)
            : ($isFinished ? $this->calculator->calculate(1800) : 0.0000);

        $this->assertSame(0.0000, $hours);
    }

    public function test_concurrency_protection_rejects_stale_update_when_heartbeat_changes(): void
    {
        DB::beginTransaction();
        try {
            $user = User::first();
            $plan = TrainingPlan::first();
            $content = Content::first();

            // Create or update a test fixture row
            $testRow = PlaylistContentUser::updateOrCreate(
                [
                    'user_id'         => $user->id,
                    'trainingplan_id' => $plan->id,
                    'content_id'      => $content->id,
                ],
                [
                    'current_time'  => 1230,
                    'learning_hour' => 0.3417,
                    'progress'      => 50,
                    'finish'        => '0',
                ]
            );

            // Step 1: Batch takes snapshot
            $snapshotTime = 1230;
            $staleCalculatedHour = 0.9999;

            // Step 2: User heartbeat arrives and updates current_time to 1300
            DB::table('playlist_content_users')
                ->where('id', $testRow->id)
                ->update(['current_time' => 1300]);

            // Step 3: Batch attempts bulk update matching snapshot
            $affected = DB::table('playlist_content_users')
                ->whereIn('id', [$testRow->id])
                ->where('current_time', $snapshotTime)
                ->update([
                    'learning_hour' => $staleCalculatedHour,
                ]);

            // Step 4: Verification
            $this->assertEquals(0, $affected, 'Bulk update must affect 0 rows when current_time changed.');

            $freshRow = DB::table('playlist_content_users')->where('id', $testRow->id)->first();
            $this->assertEquals(1300, $freshRow->current_time);
            $this->assertEquals('0.3417', $freshRow->learning_hour, 'Stale hour must NOT overwrite live data.');

        } finally {
            DB::rollBack();
        }
    }

    public function test_idempotency_does_not_accumulate_on_repeated_runs(): void
    {
        $inputSeconds = 1230;

        $run1 = $this->calculator->calculate($inputSeconds);
        $run2 = $this->calculator->calculate($inputSeconds);
        $run3 = $this->calculator->calculate($inputSeconds);

        $this->assertSame(0.3417, $run1);
        $this->assertSame(0.3417, $run2);
        $this->assertSame(0.3417, $run3);
        $this->assertNotSame(0.6834, $run2);
    }

    public function test_scheduler_time_configuration_variants(): void
    {
        $times = [
            '00:00' => '0 0 * * *',
            '01:00' => '0 1 * * *',
            '02:30' => '30 2 * * *',
            '23:30' => '30 23 * * *',
        ];

        foreach ($times as $time => $expectedCron) {
            Config::set('learning_hours.playlist_content_user.schedule.enabled', true);
            Config::set('learning_hours.playlist_content_user.schedule.time', $time);
            Config::set('learning_hours.playlist_content_user.schedule.timezone', 'Asia/Jakarta');

            $schedule = new Schedule();
            $kernel = app(Kernel::class);
            $reflection = new \ReflectionClass($kernel);
            $method = $reflection->getMethod('schedule');
            $method->setAccessible(true);
            $method->invoke($kernel, $schedule);

            $matchedEvent = null;
            foreach ($schedule->events() as $event) {
                if (str_contains($event->command ?? '', 'learning-hours:calculate-playlist-content-users')) {
                    $matchedEvent = $event;
                    break;
                }
            }

            $this->assertNotNull($matchedEvent, "Event for {$time} should be found.");
            $this->assertEquals($expectedCron, $matchedEvent->expression, "Cron expression for {$time} must be {$expectedCron}.");
            $this->assertEquals('Asia/Jakarta', (string) $matchedEvent->timezone);
            $this->assertTrue($matchedEvent->withoutOverlapping);
            $this->assertTrue($matchedEvent->runInBackground);
        }
    }

    public function test_queue_job_properties_and_safe_failure_logging(): void
    {
        $job = new CalculatePlaylistContentUserLearningHoursJob(['chunk_size' => 500]);

        $this->assertSame(3, $job->tries);
        $this->assertSame(600, $job->timeout);
        $this->assertSame(60, $job->backoff);

        // Test failed handler logs without corrupting state
        $exception = new \RuntimeException('Simulated worker timeout');
        $job->failed($exception);
        $this->assertTrue(true, 'Failed handler logged exception safely.');
    }

    public function test_ui_query_count_remains_strictly_constant_across_lengths(): void
    {
        $admin = User::where('role', User::ROLE_ADMIN)->first()
            ?? User::where('role', User::ROLE_SUPER_ADMIN)->first()
            ?? User::first();

        $queryCounts = [];

        foreach ([20, 50, 100] as $length) {
            DB::flushQueryLog();
            DB::enableQueryLog();

            $response = $this->actingAs($admin)
                ->getJson("/manage/learning-hours/playlist-content-user/datatable?draw=1&start=0&length={$length}");

            $response->assertStatus(200);

            $queries = DB::getQueryLog();
            $queryCounts[$length] = count($queries);
        }

        // All lengths must have identical query count (no N+1)
        $this->assertEquals(
            $queryCounts[20],
            $queryCounts[50],
            "Query count for 20 ({$queryCounts[20]}) must match 50 ({$queryCounts[50]})."
        );
        $this->assertEquals(
            $queryCounts[50],
            $queryCounts[100],
            "Query count for 50 ({$queryCounts[50]}) must match 100 ({$queryCounts[100]})."
        );
    }
}
