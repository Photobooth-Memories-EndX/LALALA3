<?php

namespace App\Console\Commands;

use App\Services\LearningHours\ContentUserBatchCalculatorService;
use Illuminate\Console\Command;

class BackfillContentUserLearningHoursCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'learning-hours:backfill-content-users
                            {--chunk=2500 : Number of records per chunk}
                            {--dry-run : Simulate calculation without writing to database}
                            {--force : Recalculate and update even if learning_hours already matches}
                            {--limit= : Maximum records to process}
                            {--user= : Specific User ID}
                            {--content= : Specific Content ID}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Batch calculate actual learning hours for content_users using exact actual seconds and precision 4 decimals';

    /**
     * Execute the console command.
     */
    public function handle(ContentUserBatchCalculatorService $batchService): int
    {
        $isDryRun = (bool) $this->option('dry-run');
        $force = (bool) $this->option('force');
        $chunkSize = max(50, (int) ($this->option('chunk') ?? 2500));
        $limit = $this->option('limit') !== null ? (int) $this->option('limit') : null;
        $userId = $this->option('user') !== null ? (int) $this->option('user') : null;
        $contentId = $this->option('content') !== null ? (int) $this->option('content') : null;

        $this->info('======================================================================');
        $this->info(' BATCH CALCULATE ACTUAL LEARNING HOURS: CONTENT USERS');
        $this->info('======================================================================');
        $this->line('Mode       : ' . ($isDryRun ? '<comment>DRY RUN (Preview Only - No DB Writes)</comment>' : '<fg=green;options=bold>LIVE EXECUTION</>'));
        $this->line("Chunk Size : {$chunkSize}");
        if ($force) {
            $this->line('Force Mode : <comment>Enabled (overwrites matching values)</comment>');
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

        $lastLoggedBatch = 0;

        $results = $batchService->process(
            [
                'chunk_size'  => $chunkSize,
                'dry_run'     => $isDryRun,
                'force'       => $force,
                'user_id'     => $userId,
                'content_id'  => $contentId,
                'max_records' => $limit,
            ],
            function (int $batchNumber, int $processedInBatch, int $totalProcessed, array $stats) use (&$lastLoggedBatch) {
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
                    $s['existing_hour'] !== null ? number_format((float) $s['existing_hour'], 4, '.', '') . ' h' : 'NULL',
                    number_format((float) $s['calculated_hour'], 4, '.', '') . ' h',
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
                ['Total Skipped (Already Matching)', number_format($results['total_skipped'])],
                ['Historical Fallback Records', number_format($results['total_historical_fallback'])],
                ['Zero / Unfinished Records', number_format($results['total_zero'])],
                ['Missing Content Records', number_format($results['total_missing_content'])],
                ['Concurrency Skipped', number_format($results['total_concurrent_changed'])],
                ['Failed Records', number_format($results['total_failed'])],
                ['Total Batches', number_format($results['total_batches'])],
                ['Execution Time', $results['duration_seconds'] . 's'],
                ['Peak Memory', $results['peak_memory_mb'] . ' MB'],
                ['Mode', $isDryRun ? 'DRY RUN (No DB changes)' : 'LIVE (Changes applied)'],
            ]
        );

        return Command::SUCCESS;
    }
}
