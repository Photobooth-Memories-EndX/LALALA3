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
                            {--limit= : Maximum records to process}';

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
        $this->line('Mode       : ' . ($isDryRun ? '<comment>DRY RUN (Preview Only)</comment>' : '<fg=green;options=bold>LIVE EXECUTION</>'));
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

        $this->line('Starting batch execution...');

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
            function (int $batchNumber, int $processedInBatch, int $totalProcessed, array $stats) {
                $this->line(sprintf(
                    'Batch #%d processed: %d records (Total: %d | Updated: %d | Skipped: %d | Missing: %d)',
                    $batchNumber,
                    $processedInBatch,
                    $totalProcessed,
                    $stats['updated'],
                    $stats['skipped'],
                    $stats['missing_content']
                ));
            }
        );

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
                ['Missing Content Fallbacks', number_format($results['missing_content'])],
                ['Total Batches Executed', number_format($results['batch_count'])],
                ['Elapsed Duration', $results['duration_seconds'] . ' seconds'],
                ['Mode', $isDryRun ? 'DRY RUN' : 'COMMITTED TO DATABASE'],
            ]
        );

        return Command::SUCCESS;
    }
}
