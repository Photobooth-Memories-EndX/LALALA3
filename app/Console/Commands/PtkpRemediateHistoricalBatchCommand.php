<?php

namespace App\Console\Commands;

use App\Models\ImportBatch;
use App\Models\ImportBatchRow;
use App\Models\OfflineTraining;
use App\Models\OfflineTrainingUser;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;

class PtkpRemediateHistoricalBatchCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'ptkp:remediate-historical-batch
                            {batch_uuid=af57a684-d72b-47a4-bab9-ffa505844bbc : UUID of the historical import batch}
                            {--dry-run : Preview changes without modifying database (Default)}
                            {--execute : Perform the remediation in database}
                            {--rollback : Roll back changes using backup snapshot}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Safely remediate false-positive historical imported rows for Directors Kim Kyu-Je and Novial';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $batchUuid = $this->argument('batch_uuid');
        $isExecute = (bool) $this->option('execute');
        $isRollback = (bool) $this->option('rollback');
        $isDryRun = !$isExecute && !$isRollback;

        $this->newLine();
        $this->info('================================================================================');
        $this->info(' OWL LMS — PTKP HISTORICAL BATCH SAFETY REMEDIATION');
        $this->info('================================================================================');
        $this->line("Target Batch UUID : <comment>{$batchUuid}</comment>");

        $batch = ImportBatch::where('batch_uuid', $batchUuid)->first();
        if (!$batch) {
            $this->error("Batch not found for UUID: {$batchUuid}");
            return 1;
        }

        $backupFile = storage_path("app/ptkp_remediation_backup_{$batchUuid}.json");

        if ($isRollback) {
            return $this->handleRollback($batch, $backupFile);
        }

        $this->line("Mode              : " . ($isDryRun ? '<fg=yellow;options=bold>DRY-RUN (Safe Read-Only Preview — Zero DB Updates)</>' : '<fg=red;options=bold>EXECUTION MODE (Live Database Updates)</>'));
        $this->newLine();

        // 1. Identify affected import_batch_rows
        $targetRowNumbers = [380, 391, 494, 505];
        $targetRows = ImportBatchRow::where('import_batch_id', $batch->id)
            ->whereIn('row_number', $targetRowNumbers)
            ->get();

        if ($targetRows->isEmpty()) {
            $this->warn("No target rows found in batch {$batch->id} for rows: " . implode(', ', $targetRowNumbers));
            return 0;
        }

        // Check if already remediated (Idempotency Check)
        $alreadyRemediated = $targetRows->every(function ($r) {
            return $r->validation_status === ImportBatchRow::STATUS_INVALID
                && $r->action === ImportBatchRow::ACTION_SKIPPED;
        });

        if ($alreadyRemediated) {
            $this->info("STATE: Target rows are ALREADY remediated (status = INVALID, action = SKIPPED).");
            $this->info("IDEMPOTENCY: No database modifications needed. Historical batch is clean.");
            return 0;
        }

        // Expected remediation mapping
        $remediationMap = [
            380 => ['emp_id' => '22', 'name' => 'IQBAL NUR RACHMAN', 'error' => "ERROR: Employee ID '22' was not found in LMS users database."],
            391 => ['emp_id' => '33', 'name' => 'RIDWAN ALFITRA',    'error' => "ERROR: Employee ID '33' was not found in LMS users database."],
            494 => ['emp_id' => '22', 'name' => 'IQBAL NUR RACHMAN', 'error' => "ERROR: Employee ID '22' was not found in LMS users database."],
            505 => ['emp_id' => '33', 'name' => 'RIDWAN ALFITRA',    'error' => "ERROR: Employee ID '33' was not found in LMS users database."],
        ];

        $otuIds = $targetRows->pluck('offline_training_user_id')->filter()->unique()->values()->all();
        $otIds = $targetRows->pluck('offline_training_id')->filter()->unique()->values()->all();

        $this->info("1. AFFECTED SPREADSHEET ROWS (import_batch_rows table):");
        $rowsTable = [];
        foreach ($targetRows as $r) {
            $rem = $remediationMap[$r->row_number] ?? null;
            $rowsTable[] = [
                'PK ID'       => $r->id,
                'Row'         => $r->row_number,
                'Current ID'  => $r->employee_id,
                'Remediate ID'=> $rem['emp_id'] ?? $r->employee_id,
                'Current Name'=> $r->employee_name,
                'Remediate Name' => $rem['name'] ?? $r->employee_name,
                'Status Now'  => $r->validation_status,
                'Status Rem'  => ImportBatchRow::STATUS_INVALID,
                'Action Now'  => $r->action,
                'Action Rem'  => ImportBatchRow::ACTION_SKIPPED,
                'OTU ID'      => $r->offline_training_user_id ?: '-',
            ];
        }
        $this->table(['PK ID', 'Row', 'Current ID', 'Remediate ID', 'Current Name', 'Remediate Name', 'Status Now', 'Status Rem', 'Action Now', 'Action Rem', 'OTU ID'], $rowsTable);
        $this->newLine();

        $this->info("2. AFFECTED PARTICIPANT ENROLLMENTS (offline_training_users table):");
        $otuRecords = OfflineTrainingUser::withTrashed()->whereIn('id', $otuIds)->get();
        $otuTable = [];
        foreach ($otuRecords as $otu) {
            $user = DB::table('users')->where('id', $otu->user_id)->first();
            $training = DB::table('offline_trainings')->where('id', $otu->offline_training_id)->first();
            $otuTable[] = [
                'OTU PK'       => $otu->id,
                'User PK'      => $otu->user_id,
                'User Name'    => $user?->name ?? 'Unknown',
                'Director NIP' => $user?->id_emp ?? 'Unknown',
                'Training PK'  => $otu->offline_training_id,
                'Course Title' => $training?->course_name ?? 'Unknown',
                'Action'       => 'SOFT-DELETE (Remove enrollment from Director account)',
            ];
        }
        $this->table(['OTU PK', 'User PK', 'User Name', 'Director NIP', 'Training PK', 'Course Title', 'Action'], $otuTable);
        $this->newLine();

        $this->info("3. ORPHAN TRAINING ASSESSMENT (offline_trainings table):");
        $otTable = [];
        $emptyOtIds = [];
        foreach ($otIds as $otId) {
            $ot = OfflineTraining::find($otId);
            $totalParticipants = OfflineTrainingUser::where('offline_training_id', $otId)->count();
            $targetParticipantsInThis = OfflineTrainingUser::where('offline_training_id', $otId)->whereIn('id', $otuIds)->count();
            $remainingAfterRemediation = $totalParticipants - $targetParticipantsInThis;

            $action = 'KEEP ACTIVE (Has other valid participants)';
            if ($remainingAfterRemediation <= 0) {
                $action = 'SOFT-DELETE (0 remaining participants, was created only for invalid rows)';
                $emptyOtIds[] = $otId;
            }

            $otTable[] = [
                'OT PK'         => $otId,
                'Title'         => $ot?->title ?? 'Unknown',
                'Total Users'   => $totalParticipants,
                'Target Users'  => $targetParticipantsInThis,
                'Remaining Users' => $remainingAfterRemediation,
                'Recommended'   => $action,
            ];
        }
        $this->table(['OT PK', 'Title', 'Total Users', 'Target Users', 'Remaining Users', 'Recommended'], $otTable);
        $this->newLine();

        $this->info("4. BATCH STATISTICAL ADJUSTMENT (import_batches table PK {$batch->id}):");
        $statsTable = [
            ['Metric' => 'valid_rows',        'Before' => $batch->valid_rows,        'After' => $batch->valid_rows - count($targetRows),        'Delta' => '-' . count($targetRows)],
            ['Metric' => 'invalid_rows',      'Before' => $batch->invalid_rows,      'After' => $batch->invalid_rows + count($targetRows),      'Delta' => '+' . count($targetRows)],
            ['Metric' => 'imported_rows',     'Before' => $batch->imported_rows,     'After' => $batch->imported_rows - count($targetRows),     'Delta' => '-' . count($targetRows)],
            ['Metric' => 'skipped_rows',      'Before' => $batch->skipped_rows,      'After' => $batch->skipped_rows + count($targetRows),      'Delta' => '+' . count($targetRows)],
            ['Metric' => 'trainings_created', 'Before' => $batch->trainings_created, 'After' => $batch->trainings_created - count($emptyOtIds), 'Delta' => '-' . count($emptyOtIds)],
            ['Metric' => 'total_rows',        'Before' => $batch->total_rows,        'After' => $batch->total_rows,                             'Delta' => '0 (Unchanged)'],
        ];
        $this->table(['Metric', 'Before', 'After', 'Delta'], $statsTable);
        $this->newLine();

        if ($isDryRun) {
            $this->warn("********************************************************************************");
            $this->warn(" DRY-RUN COMPLETE: No database modifications were written.");
            $this->warn(" To execute this remediation, rerun with: php artisan ptkp:remediate-historical-batch --execute");
            $this->warn("********************************************************************************");
            return 0;
        }

        // =========================================================================
        // EXECUTION MODE
        // =========================================================================
        $this->warn("Writing backup snapshot to: {$backupFile}");

        $snapshot = [
            'timestamp'             => now()->toDateTimeString(),
            'batch_id'              => $batch->id,
            'batch_uuid'            => $batch->batch_uuid,
            'batch_counters'        => $batch->only(['valid_rows', 'invalid_rows', 'imported_rows', 'skipped_rows', 'trainings_created', 'total_rows']),
            'import_batch_rows'     => $targetRows->toArray(),
            'offline_training_users'=> $otuRecords->toArray(),
            'soft_deleted_ot_ids'   => $emptyOtIds,
            'offline_trainings'     => OfflineTraining::whereIn('id', $emptyOtIds)->get()->toArray(),
        ];
        File::put($backupFile, json_encode($snapshot, JSON_PRETTY_PRINT));

        DB::beginTransaction();
        try {
            // Re-lock batch and target rows inside transaction to guarantee batch ownership & concurrency safety
            $lockedBatch = ImportBatch::where('id', $batch->id)->lockForUpdate()->firstOrFail();
            $lockedRows = ImportBatchRow::where('import_batch_id', $lockedBatch->id)
                ->whereIn('row_number', $targetRowNumbers)
                ->lockForUpdate()
                ->get();

            if ($lockedRows->count() !== count($targetRowNumbers)) {
                throw new \RuntimeException("Mismatch in target rows count for batch ID {$lockedBatch->id}. Aborting.");
            }

            // Verify strict batch ownership for every target row
            foreach ($lockedRows as $lr) {
                if ((int)$lr->import_batch_id !== (int)$lockedBatch->id) {
                    throw new \RuntimeException("Security check failed: Row ID {$lr->id} does not belong to Batch ID {$lockedBatch->id}.");
                }
            }

            // 1. Soft delete erroneous offline_training_users
            if (!empty($otuIds)) {
                OfflineTrainingUser::whereIn('id', $otuIds)->delete();
            }

            // 2. Soft delete orphan offline_trainings (e.g. Training 27)
            if (!empty($emptyOtIds)) {
                OfflineTraining::whereIn('id', $emptyOtIds)->delete();
            }

            // 3. Update import_batch_rows with explicit batch constraint
            foreach ($lockedRows as $r) {
                $rem = $remediationMap[$r->row_number];
                ImportBatchRow::where('id', $r->id)
                    ->where('import_batch_id', $lockedBatch->id)
                    ->update([
                        'employee_id'              => $rem['emp_id'],
                        'employee_name'            => $rem['name'],
                        'validation_status'        => ImportBatchRow::STATUS_INVALID,
                        'action'                   => ImportBatchRow::ACTION_SKIPPED,
                        'offline_training_id'      => null,
                        'offline_training_user_id' => null,
                        'error_code'               => 'INVALID_ID',
                        'messages'                 => json_encode([$rem['error']]),
                    ]);
            }

            // 4. Update import_batches counters
            $lockedBatch->valid_rows = $lockedBatch->valid_rows - count($lockedRows);
            $lockedBatch->invalid_rows = $lockedBatch->invalid_rows + count($lockedRows);
            $lockedBatch->imported_rows = $lockedBatch->imported_rows - count($lockedRows);
            $lockedBatch->skipped_rows = $lockedBatch->skipped_rows + count($lockedRows);
            if (!empty($emptyOtIds)) {
                $lockedBatch->trainings_created = max(0, $lockedBatch->trainings_created - count($emptyOtIds));
            }
            $lockedBatch->save();

            DB::commit();

            $this->info("SUCCESS: Historical batch remediation completed successfully!");
            $this->info("Backup snapshot stored at: {$backupFile}");
            return 0;
        } catch (\Throwable $e) {
            DB::rollBack();
            $this->error("Remediation failed: " . $e->getMessage());
            return 1;
        }
    }

    protected function handleRollback(ImportBatch $batch, string $backupFile): int
    {
        $this->warn("Executing rollback from backup: {$backupFile}");

        if (!File::exists($backupFile)) {
            $this->error("Backup file not found: {$backupFile}");
            return 1;
        }

        $snapshot = json_decode(File::get($backupFile), true);
        if (!$snapshot || !is_array($snapshot)) {
            $this->error("Invalid backup snapshot content.");
            return 1;
        }

        DB::beginTransaction();
        try {
            // 1. Restore offline_training_users
            $otuIds = array_column($snapshot['offline_training_users'] ?? [], 'id');
            if (!empty($otuIds)) {
                OfflineTrainingUser::withTrashed()->whereIn('id', $otuIds)->restore();
            }

            // 2. Restore offline_trainings
            $otIds = $snapshot['soft_deleted_ot_ids'] ?? [];
            if (!empty($otIds)) {
                OfflineTraining::withTrashed()->whereIn('id', $otIds)->restore();
            }

            // 3. Restore import_batch_rows with explicit batch constraint
            foreach ($snapshot['import_batch_rows'] ?? [] as $rData) {
                ImportBatchRow::where('id', $rData['id'])
                    ->where('import_batch_id', $batch->id)
                    ->update([
                        'employee_id'              => $rData['employee_id'],
                        'employee_name'            => $rData['employee_name'],
                        'validation_status'        => $rData['validation_status'],
                        'action'                   => $rData['action'],
                        'offline_training_id'      => $rData['offline_training_id'],
                        'offline_training_user_id' => $rData['offline_training_user_id'],
                        'error_code'               => $rData['error_code'],
                        'messages'                 => is_array($rData['messages']) ? json_encode($rData['messages']) : $rData['messages'],
                    ]);
            }

            // 4. Restore batch counters
            $batch->fill($snapshot['batch_counters'] ?? []);
            $batch->save();

            DB::commit();

            $this->info("SUCCESS: Rollback executed cleanly. Database reverted to original state.");
            return 0;
        } catch (\Throwable $e) {
            DB::rollBack();
            $this->error("Rollback failed: " . $e->getMessage());
            return 1;
        }
    }
}
