<?php

namespace App\Console\Commands;

use App\Jobs\SyncContentLearningHourJob;
use App\Jobs\SyncPlaylistLearningHourJob;
use App\Models\Content;
use App\Models\TrainingPlan;
use App\Services\LearningHours\ContentLearningHourService;
use App\Services\LearningHours\LearningHourSynchronizeService;
use App\Services\LearningHours\PlaylistLearningHourService;
use Illuminate\Console\Command;

class SyncLearningHoursCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'learning-hours:sync
                            {--content= : Specific Content ID to synchronize}
                            {--playlist= : Specific Playlist ID to synchronize}
                            {--queue : Dispatch batch synchronization to background queue}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Synchronize Content and Playlist Learning Hours';

    /**
     * Execute the console command.
     */
    public function handle(
        ContentLearningHourService $contentService,
        PlaylistLearningHourService $playlistService,
        LearningHourSynchronizeService $syncService
    ): int {
        $contentId = $this->option('content');
        $playlistId = $this->option('playlist');
        $useQueue = $this->option('queue');

        if ($contentId !== null) {
            $content = Content::find((int) $contentId);
            if (!$content) {
                $this->error("Content ID {$contentId} not found.");
                return self::FAILURE;
            }

            $result = $contentService->syncSingleContent($content);
            $this->info("Content #{$content->id} synchronized. Learning Hours: {$result->learningHours}. Status: " . ($result->updated ? 'Updated' : 'Up to date'));
            return self::SUCCESS;
        }

        if ($playlistId !== null) {
            $playlist = TrainingPlan::find((int) $playlistId);
            if (!$playlist) {
                $this->error("Playlist ID {$playlistId} not found.");
                return self::FAILURE;
            }

            $result = $playlistService->syncSinglePlaylist($playlist);
            $this->info("Playlist #{$playlist->id} synchronized. Learning Hours: {$result->learningHours}. Status: " . ($result->updated ? 'Updated' : 'Up to date'));
            return self::SUCCESS;
        }

        if ($useQueue) {
            SyncContentLearningHourJob::dispatch();
            SyncPlaylistLearningHourJob::dispatch();

            $this->info('Batch synchronization jobs dispatched to background queue worker.');
            return self::SUCCESS;
        }

        $this->info('Starting full Learning Hours synchronization...');
        $stats = $syncService->synchronize();

        $this->table(
            ['Category', 'Updated', 'Skipped', 'Total'],
            [
                ['Content', $stats['content_updated'], $stats['content_skipped'], $stats['content_total']],
                ['Playlist', $stats['playlist_updated'], $stats['playlist_skipped'], $stats['playlist_total']],
                ['Total', $stats['total_updated'], $stats['total_skipped'], $stats['content_total'] + $stats['playlist_total']],
            ]
        );

        $this->info('Learning Hours synchronization completed successfully.');

        return self::SUCCESS;
    }
}
