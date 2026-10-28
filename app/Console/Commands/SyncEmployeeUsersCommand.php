<?php

namespace App\Console\Commands;

use App\Services\EmployeeSync\EmployeeUserSyncService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;

class SyncEmployeeUsersCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'owl:sync-employee-users
                            {--dry-run : Run synchronization in simulation mode without writing to database}
                            {--no-create : Do not create new users, only report or skip candidates}
                            {--limit= : Limit the number of employee records processed from API}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Synchronize OWL users with PT Krakatau Posco Employee API Gateway';

    /**
     * Execute the console command.
     */
    public function handle(EmployeeUserSyncService $syncService): int
    {
        $lockKey = 'owl:sync-employee-users:lock';
        $lockTtl = 300; // 5 minutes TTL
        $lock = Cache::lock($lockKey, $lockTtl);

        if (!$lock->get()) {
            $this->error('Synchronization aborted: Another sync process is currently running or holding the execution lock.');
            return Command::FAILURE;
        }

        try {
            $isDryRun = (bool) $this->option('dry-run');
            $noCreate = (bool) $this->option('no-create');
            $limit = $this->option('limit') !== null ? (int) $this->option('limit') : null;

            $this->info('Starting OWL Employee User Synchronization...');
            if ($isDryRun) {
                $this->warn('DRY RUN MODE ENABLED: Zero-write active. No database records will be modified or created.');
            }

            $result = $syncService->sync([
                'dry_run' => $isDryRun,
                'allow_create' => !$noCreate,
                'limit' => $limit,
            ]);

            $this->newLine();
            $this->table(
                ['Metric', 'Value'],
                [
                    ['Status', strtoupper($result->status)],
                    ['Mode', $result->isDryRun ? 'DRY-RUN (Zero-Write Simulation)' : 'PERSISTED (Live Sync)'],
                    ['Duration', $result->durationSeconds() . ' seconds'],
                    ['Total API Records', number_format($result->totalApi)],
                    ['Total OWL Users in DB', number_format($result->totalOwl)],
                    ['Created Users (New)', number_format($result->createdCount)],
                    ['Updated Users (Total)', number_format($result->updatedCount)],
                    ['  - Active Users Updated', number_format($result->updatedActiveCount)],
                    ['  - Soft-Deleted Users Updated', number_format($result->updatedSoftDeletedCount)],
                    ['Unchanged Users', number_format($result->unchangedCount)],
                    ['Skipped Candidates / Records', number_format($result->skippedCount)],
                    ['Conflicts Detected', number_format($result->conflictCount)],
                    ['Failed Count', number_format($result->failedCount)],
                ]
            );

            if ($result->updatedSoftDeletedCount > 0) {
                $this->newLine();
                $this->warn("[NOTICE] {$result->updatedSoftDeletedCount} soft-deleted user(s) in OWL were updated with fresh organizational profile data from API Gateway (accounts remain soft-deleted).");
            }

            if ($result->errorMessage) {
                $this->error('Error Message: ' . $result->errorMessage);
            }

            // Display sample created users
            if (!empty($result->createdDetails)) {
                $this->newLine();
                $this->info('Sample New Candidates / Created Users:');
                $sampleCreated = array_slice($result->createdDetails, 0, 10);
                $this->table(
                    ['Employee ID', 'Name', 'Email'],
                    array_map(fn ($c) => [$c['id_emp'], $c['name'], $c['email']], $sampleCreated)
                );
            }

            // Display sample updated changes
            if (!empty($result->updatedDetails)) {
                $this->newLine();
                $this->info('Sample Updated Users:');
                $sampleRows = [];
                foreach (array_slice($result->updatedDetails, 0, 10) as $up) {
                    $changeSummaries = [];
                    foreach ($up['changes'] as $f => $vals) {
                        $changeSummaries[] = "{$f}: '{$vals['from']}' -> '{$vals['to']}'";
                    }
                    $sampleRows[] = [
                        $up['id_emp'],
                        $up['name'],
                        $up['is_soft_deleted'] ? 'Soft-Deleted' : 'Active',
                        implode("\n", $changeSummaries),
                    ];
                }
                $this->table(['Employee ID', 'Name', 'User Status', 'Changes'], $sampleRows);
            }

            // Display conflicts if any
            if (!empty($result->conflicts)) {
                $this->newLine();
                $this->warn('Conflicts Detected:');
                $this->table(
                    ['Employee ID', 'Conflict Reason'],
                    array_map(fn ($cf) => [$cf['id_emp'] ?: '(Empty)', $cf['reason']], array_slice($result->conflicts, 0, 15))
                );
            }

            $this->newLine();
            if ($result->status === 'failed') {
                $this->error('Synchronization completed with FATAL error.');
                return Command::FAILURE;
            }

            if ($result->status === 'partial') {
                $this->warn('Synchronization completed with warnings/conflicts.');
                return Command::SUCCESS;
            }

            $this->info('Synchronization completed successfully!');
            return Command::SUCCESS;
        } finally {
            optional($lock)->release();
        }
    }
}
