<?php

namespace App\Console\Commands;

use App\Services\LearningHours\PlaylistContentUserBatchCalculatorService;
use Illuminate\Console\Command;

class CalculatePlaylistContentUserLearningHoursCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'learning-hours:calculate-playlist-content-users
                            {--chunk= : Number of records per batch (default: config learning_hours.playlist_content_user.chunk_size)}
                            {--dry-run : Simulate calculation without writing to database}
                            {--force : Recalculate and update even if learning_hour already matches}
                            {--playlist= : Specific Training Plan (Playlist) ID}
                            {--user= : Specific User ID}
                            {--content= : Specific Content ID}
                            {--limit= : Maximum records to process}
                            {--queue : Dispatch calculation to background queue}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Batch calculate actual learning hours for playlist_content_users using exact actual seconds and precision 4 decimals';

    /**
     * Execute the console command.
     */
    public function handle(PlaylistContentUserBatchCalculatorService $batchService): int
    {
        $isDryRun = (bool) $this->option('dry-run');
        $force = (bool) $this->option('force');
        $chunkSize = $this->option('chunk') !== null
            ? (int) $this->option('chunk')
            : (int) config('learning_hours.playlist_content_user.chunk_size', 500);

        $playlistId = $this->option('playlist') !== null ? (int) $this->option('playlist') : null;
        $userId = $this->option('user') !== null ? (int) $this->option('user') : null;
        $contentId = $this->option('content') !== null ? (int) $this->option('content') : null;
        $limit = $this->option('limit') !== null ? (int) $this->option('limit') : null;

        $this->info('======================================================================');
        $this->info(' BATCH CALCULATE ACTUAL LEARNING HOURS: PLAYLIST CONTENT USERS');
        $this->info('======================================================================');
        $this->line('Mode       : ' . ($isDryRun ? '<comment>DRY RUN (Preview Only - No DB Writes)</comment>' : '<fg=green;options=bold>LIVE EXECUTION</>'));
        $this->line("Batch Size : {$chunkSize}");
        if ($force) {
            $this->line('Force Mode : <comment>Enabled (overwrites matching values)</comment>');
        }
        if ($playlistId !== null) {
            $this->line("Playlist ID: {$playlistId}");
        }
        if ($userId !== null) {
            $this->line("User ID    : {$userId}");
        }
        if ($contentId !== null) {
            $this->line("Content ID : {$contentId}");
        }
        if ($limit !== null) {
            $this->line("Limit      : {$limit} records");
        }
        $this->newLine();

        if ($this->option('queue')) {
            \App\Jobs\CalculatePlaylistContentUserLearningHoursJob::dispatch([
                'chunk_size'  => $chunkSize,
                'dry_run'     => $isDryRun,
                'force'       => $force,
                'playlist_id' => $playlistId,
                'user_id'     => $userId,
                'content_id'  => $contentId,
                'max_records' => $limit,
            ]);

            $this->info('Successfully dispatched Learning Hour Playlist calculation job to background queue.');
            return self::SUCCESS;
        }

        $this->line('Starting batch execution...');

        $lastLoggedBatch = 0;

        $results = $batchService->process(
            [
                'chunk_size'  => $chunkSize,
                'dry_run'     => $isDryRun,
                'force'       => $force,
                'playlist_id' => $playlistId,
                'user_id'     => $userId,
                'content_id'  => $contentId,
                'max_records' => $limit,
            ],
            function (int $batchNumber, int $processedInBatch, int $totalProcessed, array $stats) use (&$lastLoggedBatch) {
                // Log every 5 batches or first 5 batches to avoid console flood
                if ($batchNumber <= 5 || $batchNumber % 10 === 0) {
                    $this->line(sprintf(
                        'Batch #%d processed: %d records (Total: %d | Updated: %d | Skipped: %d | Fallback: %d | Zero: %d)',
                        $batchNumber,
                        $processedInBatch,
                        $totalProcessed,
                        $stats['updated'],
                        $stats['skipped'],
                        $stats['historical_fallback'],
                        $stats['zero']
                    ));
                    $lastLoggedBatch = $batchNumber;
                }
            }
        );

        if ($results['batch_count'] > $lastLoggedBatch) {
            $this->line(sprintf('Final Batch #%d completed.', $results['batch_count']));
        }

        // Show sample table (especially helpful in dry-run)
        if (!empty($results['samples'])) {
            $this->newLine();
            $this->info('SAMPLE CALCULATED RECORDS (Max 10):');
            $sampleRows = [];
            foreach ($results['samples'] as $s) {
                $sampleRows[] = [
                    $s['id'],
                    $s['current_time'] . 's',
                    $s['finish'],
                    $s['progress'] . '%',
                    $s['existing_hour'] ?? 'NULL',
                    $s['calculated_hour'],
                    $s['rule'],
                ];
            }
            $this->table(
                ['ID', 'Current Time', 'Finish', 'Progress', 'Existing LH', 'Calculated LH', 'Rule Applied'],
                $sampleRows
            );
        }

        $this->newLine();
        $this->info('======================================================================');
        $this->info(' BATCH EXECUTION SUMMARY');
        $this->info('======================================================================');
        $this->table(
            ['Metric', 'Value'],
            [
                ['Total Scanned', number_format($results['total_scanned'])],
                ['Total Updated', number_format($results['total_updated'])],
                ['Total Skipped (Already Accurate)', number_format($results['total_skipped'])],
                ['Historical Duration Fallbacks', number_format($results['total_historical_fallback'])],
                ['Unfinished / Zero Hours', number_format($results['total_zero'])],
                ['Missing Content Fallbacks', number_format($results['total_missing_content'])],
                ['Concurrent Changes Protected', number_format($results['total_concurrent_changed'])],
                ['Total Failed Records', number_format($results['total_failed'])],
                ['Total Batches Executed', number_format($results['batch_count'])],
                ['Total Runtime', $results['duration_seconds'] . ' seconds'],
                ['Average Batch Runtime', $results['average_batch_runtime'] . ' seconds/batch'],
                ['Execution Mode', $isDryRun ? 'DRY RUN (No DB mutations)' : 'COMMITTED TO DATABASE'],
            ]
        );

        return Command::SUCCESS;
    }
}
