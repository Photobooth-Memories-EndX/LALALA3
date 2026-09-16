<?php

namespace Tests\Feature;

use App\Console\Kernel;
use App\Jobs\CalculatePlaylistContentUserLearningHoursJob;
use App\Models\PlaylistContentUser;
use App\Services\LearningHours\PlaylistContentUserBatchCalculatorService;
use Illuminate\Console\Scheduling\Event;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

class PlaylistLearningHourSchedulerTest extends TestCase
{
    /**
     * Helper to retrieve schedule events with current configuration.
     *
     * @return array<Event>
     */
    protected function getScheduleEvents(): array
    {
        $schedule = new Schedule();
        $kernel = app(Kernel::class);

        // Invoke protected schedule method
        $reflection = new \ReflectionClass($kernel);
        $method = $reflection->getMethod('schedule');
        $method->setAccessible(true);
        $method->invoke($kernel, $schedule);

        return $schedule->events();
    }

    /**
     * Find playlist learning hours event in schedule.
     */
    protected function findPlaylistEvent(array $events): ?Event
    {
        foreach ($events as $event) {
            if (str_contains($event->command ?? '', 'learning-hours:calculate-playlist-content-users')) {
                return $event;
            }
        }

        return null;
    }

    public function test_scheduler_registers_command_when_enabled(): void
    {
        Config::set('learning_hours.playlist_content_user.schedule.enabled', true);
        Config::set('learning_hours.playlist_content_user.schedule.time', '00:00');
        Config::set('learning_hours.playlist_content_user.schedule.timezone', 'Asia/Jakarta');

        $events = $this->getScheduleEvents();
        $event = $this->findPlaylistEvent($events);

        $this->assertNotNull($event, 'Command learning-hours:calculate-playlist-content-users should be registered in schedule.');
    }

    public function test_scheduler_does_not_register_command_when_disabled(): void
    {
        Config::set('learning_hours.playlist_content_user.schedule.enabled', false);

        $events = $this->getScheduleEvents();
        $event = $this->findPlaylistEvent($events);

        $this->assertNull($event, 'Command should NOT be registered in schedule when disabled.');
    }

    public function test_default_schedule_is_daily_00_00_asia_jakarta(): void
    {
        Config::set('learning_hours.playlist_content_user.schedule.enabled', true);
        Config::set('learning_hours.playlist_content_user.schedule.time', '00:00');
        Config::set('learning_hours.playlist_content_user.schedule.timezone', 'Asia/Jakarta');

        $events = $this->getScheduleEvents();
        $event = $this->findPlaylistEvent($events);

        $this->assertNotNull($event);
        $this->assertEquals('0 0 * * *', $event->expression);
        $this->assertEquals('Asia/Jakarta', (string) $event->timezone);
    }

    public function test_schedule_time_can_be_configured_to_01_00(): void
    {
        Config::set('learning_hours.playlist_content_user.schedule.enabled', true);
        Config::set('learning_hours.playlist_content_user.schedule.time', '01:00');
        Config::set('learning_hours.playlist_content_user.schedule.timezone', 'Asia/Jakarta');

        $events = $this->getScheduleEvents();
        $event = $this->findPlaylistEvent($events);

        $this->assertNotNull($event);
        $this->assertEquals('0 1 * * *', $event->expression);
    }

    public function test_schedule_time_can_be_configured_to_02_30(): void
    {
        Config::set('learning_hours.playlist_content_user.schedule.enabled', true);
        Config::set('learning_hours.playlist_content_user.schedule.time', '02:30');
        Config::set('learning_hours.playlist_content_user.schedule.timezone', 'Asia/Jakarta');

        $events = $this->getScheduleEvents();
        $event = $this->findPlaylistEvent($events);

        $this->assertNotNull($event);
        $this->assertEquals('30 2 * * *', $event->expression);
    }

    public function test_schedule_prevents_duplicate_execution_via_without_overlapping(): void
    {
        Config::set('learning_hours.playlist_content_user.schedule.enabled', true);

        $events = $this->getScheduleEvents();
        $event = $this->findPlaylistEvent($events);

        $this->assertNotNull($event);
        $this->assertTrue($event->withoutOverlapping, 'Event must have withoutOverlapping enabled.');
    }

    public function test_schedule_runs_in_background_without_blocking(): void
    {
        Config::set('learning_hours.playlist_content_user.schedule.enabled', true);

        $events = $this->getScheduleEvents();
        $event = $this->findPlaylistEvent($events);

        $this->assertNotNull($event);
        $this->assertTrue($event->runInBackground, 'Event must run in background to keep scheduler lightweight.');
    }

    public function test_existing_command_remains_callable_manually(): void
    {
        $this->artisan('learning-hours:calculate-playlist-content-users', [
            '--dry-run' => true,
            '--limit'   => 5,
        ])->assertExitCode(0);
    }

    public function test_command_with_queue_option_dispatches_job(): void
    {
        Queue::fake();

        $this->artisan('learning-hours:calculate-playlist-content-users', [
            '--queue' => true,
            '--limit' => 10,
        ])->assertExitCode(0);

        Queue::assertPushed(
            CalculatePlaylistContentUserLearningHoursJob::class,
            function (CalculatePlaylistContentUserLearningHoursJob $job) {
                return isset($job->options['max_records']) && $job->options['max_records'] === 10;
            }
        );
    }

    public function test_job_configuration_properties(): void
    {
        $job = new CalculatePlaylistContentUserLearningHoursJob();

        $this->assertEquals(3, $job->tries);
        $this->assertEquals(600, $job->timeout);
        $this->assertEquals(60, $job->backoff);
    }

    public function test_job_execution_reuses_existing_batch_service(): void
    {
        $batchService = $this->createMock(PlaylistContentUserBatchCalculatorService::class);
        $batchService->expects($this->once())
            ->method('process')
            ->willReturn([
                'total_scanned'    => 5,
                'total_updated'    => 5,
                'total_skipped'    => 0,
                'duration_seconds' => 0.05,
            ]);

        $job = new CalculatePlaylistContentUserLearningHoursJob(['limit' => 5]);
        $job->handle($batchService);
    }
}
