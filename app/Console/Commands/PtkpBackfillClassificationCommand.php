<?php

namespace App\Console\Commands;

use App\Services\OfflineTraining\PtkpClassificationBackfillService;
use Carbon\Carbon;
use Illuminate\Console\Command;

class PtkpBackfillClassificationCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'ptkp:backfill-classification
                            {--source= : Custom path to PTKP Excel source file}
                            {--dry-run : Run comparison without updating database (Default)}
                            {--execute : Execute backfill updates (Locked in Phase 5 until review)}
                            {--export= : Destination CSV report path}
                            {--limit= : Limit number of participant records to audit}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Audit historical participant records against PTKP Excel source and preview classification recovery (Dry-Run)';

    /**
     * Execute the console command.
     */
    public function handle(PtkpClassificationBackfillService $backfillService): int
    {
        $this->newLine();
        $this->info('================================================================================');
        $this->info(' OWL LMS — PTKP LEGACY CLASSIFICATION RECOVERY & INTEGRITY AUDIT (PHASE 5)');
        $this->info('================================================================================');

        $isExecuteRequested = (bool) $this->option('execute');
        $sourcePath = $this->option('source') ?: base_path('docs/PTKP_OWL_Import_Preview_5000 3.xlsx');
        $limit = $this->option('limit') !== null ? (int) $this->option('limit') : null;

        // Strict Safety Control: Execution Mode is locked in Phase 5 until explicit user approval
        if ($isExecuteRequested) {
            $this->warn(' [SAFETY NOTICE] Execution mode is currently LOCKED in Phase 5.');
            $this->warn(' Acceptance criteria require dry-run report review and explicit user approval before execution.');
            $this->warn(' Proceeding in SAFE DRY-RUN (READ-ONLY) mode. No database records will be modified.');
            $this->newLine();
        }

        $this->line(' Mode       : <comment>DRY-RUN ONLY (Read-Only Audit — Zero Database Modifications)</comment>');
        $this->line(" Source File: {$sourcePath}");
        if ($limit !== null) {
            $this->line(" Limit      : {$limit} participants");
        }
        $this->newLine();

        if (!file_exists($sourcePath)) {
            $this->error(" Source file not found: {$sourcePath}");
            return 1;
        }

        $this->info(' Loading and indexing PTKP Excel source...');
        $startTime = microtime(true);

        try {
            $progressBar = null;

            $result = $backfillService->runAudit(
                excelPath: $sourcePath,
                limit: $limit,
                progressCallback: function ($current, $total) use (&$progressBar) {
                    if ($progressBar === null) {
                        $progressBar = $this->output->createProgressBar($total);
                        $progressBar->setFormat(' %current%/%max% [%bar%] %percent:3s%% (%elapsed:6s%)');
                        $progressBar->start();
                    }
                    $progressBar->setProgress($current);
                }
            );

            if ($progressBar) {
                $progressBar->finish();
                $this->newLine(2);
            }

            $duration = round(microtime(true) - $startTime, 2);
            $stats = $result['stats'];

            // 1. Output Summary Table
            $this->info(' AUDIT METRICS & SUMMARY:');
            $this->table(
                ['Metric Description', 'Count', 'Percentage of Database'],
                [
                    ['Total Database Participants Scanned', number_format($stats['total_db_scanned']), '100.0%'],
                    ['Total Valid Excel Records Indexed', number_format($stats['total_excel_scanned']), '-'],
                    ['MATCHED (Already correct or matching)', number_format($stats['matched']), self::calcPct($stats['matched'], $stats['total_db_scanned'])],
                    ['NEEDS_BACKFILL (Eligible for recovery)', number_format($stats['needs_backfill']), self::calcPct($stats['needs_backfill'], $stats['total_db_scanned'])],
                    ['NOT_FOUND (Unmatched in Excel source)', number_format($stats['not_found']), self::calcPct($stats['not_found'], $stats['total_db_scanned'])],
                    ['DUPLICATE (Multiple identical Excel rows)', number_format($stats['duplicate']), self::calcPct($stats['duplicate'], $stats['total_db_scanned'])],
                    ['CONFLICT (Multiple contradictory classifications)', number_format($stats['conflict']), self::calcPct($stats['conflict'], $stats['total_db_scanned'])],
                    ['INVALID (Empty or corrupt classification in Excel)', number_format($stats['invalid']), self::calcPct($stats['invalid'], $stats['total_db_scanned'])],
                ]
            );

            // 2. Sample 15 Proposed Backfill Records
            if (!empty($result['sample_backfill'])) {
                $this->newLine();
                $this->info(' SAMPLE PROPOSED CLASSIFICATION RECOVERIES (First 15 candidates):');
                $sampleRows = [];
                foreach (array_slice($result['sample_backfill'], 0, 15) as $sample) {
                    $sampleRows[] = [
                        $sample['employee_id'],
                        \Illuminate\Support\Str::limit($sample['employee_name'], 20),
                        \Illuminate\Support\Str::limit($sample['course_title'], 25),
                        $sample['batch'] ?: '-',
                        $sample['existing_db_main'] ?: '-',
                        $sample['proposed_main'],
                        \Illuminate\Support\Str::limit($sample['proposed_sub'] ?: '-', 20),
                        'Row ' . implode(',', $sample['excel_source_rows']),
                    ];
                }

                $this->table(
                    ['Emp ID', 'Employee Name', 'Course Title', 'Batch', 'Existing (Flattened)', 'Proposed Main', 'Proposed Sub', 'Excel Row'],
                    $sampleRows
                );
            }

            // 3. Highlight Conflicts
            if (!empty($result['conflicts'])) {
                $this->newLine();
                $this->warn(' CONFLICTING SOURCE RECORDS DETECTED (Excluded from auto-recovery):');
                $conflictRows = [];
                foreach ($result['conflicts'] as $c) {
                    $conflictRows[] = [
                        $c['employee_id'],
                        $c['employee_name'],
                        $c['course_title'],
                        $c['reason'],
                    ];
                }
                $this->table(['Emp ID', 'Name', 'Course Title', 'Reason'], $conflictRows);
            }

            // 4. Export CSV Report
            $timestamp = Carbon::now()->format('Ymd_His');
            $exportPath = $this->option('export') ?: storage_path("app/ptkp_classification_backfill_audit_{$timestamp}.csv");
            $backfillService->exportAuditToCsv($result['audit_records'], $exportPath);

            $this->newLine();
            $this->info(" Full participant-level audit report exported successfully:");
            $this->line(" <comment>{$exportPath}</comment>");
            $this->line(" Execution time: {$duration}s | Database records updated: <fg=green;options=bold>0 (DRY-RUN PRESERVED)</>");
            $this->newLine();

            return 0;
        } catch (\Throwable $e) {
            $this->error(" Audit failed: {$e->getMessage()}");
            $this->line($e->getTraceAsString());
            return 1;
        }
    }

    private static function calcPct(int $part, int $total): string
    {
        if ($total <= 0) {
            return '0.0%';
        }
        return number_format(($part / $total) * 100, 1) . '%';
    }
}
