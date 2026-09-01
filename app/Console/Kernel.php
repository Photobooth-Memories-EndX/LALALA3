<?php

namespace App\Console;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;

class Kernel extends ConsoleKernel
{
    /**
     * Define the application's command schedule.
     *
     * @param  \Illuminate\Console\Scheduling\Schedule  $schedule
     * @return void
     */
    protected function schedule(Schedule $schedule)
    {
        $playlistSchedule = config('learning_hours.playlist_content_user.schedule', []);
        $enabled = (bool) ($playlistSchedule['enabled'] ?? true);

        if ($enabled) {
            $time = (string) ($playlistSchedule['time'] ?? '00:00');
            $timezone = (string) ($playlistSchedule['timezone'] ?? 'Asia/Jakarta');

            $schedule->command('learning-hours:calculate-playlist-content-users')
                ->dailyAt($time)
                ->timezone($timezone)
                ->withoutOverlapping(60)
                ->runInBackground()
                ->before(function () {
                    \Illuminate\Support\Facades\Log::channel(config('learning_hours.log_channel', 'single'))
                        ->info('Learning Hour Playlist scheduler started.');
                })
                ->after(function () {
                    \Illuminate\Support\Facades\Log::channel(config('learning_hours.log_channel', 'single'))
                        ->info('Learning Hour Playlist calculation dispatched.');
                });
        }
    }

    /**
     * Register the commands for the application.
     *
     * @return void
     */
    protected function commands()
    {
        $this->load(__DIR__.'/Commands');

        require base_path('routes/console.php');
    }
}
