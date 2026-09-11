<?php

namespace App\Console\Commands;

use App\Models\PlaylistContentUser;
use App\Models\TrainingPlan;
use App\Models\TrainingPlanDetail;
use App\Services\LearningHours\HistoricalPlaylistProgressExecutionService;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class MigrateHistoricalPlaylistProgressCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'learning:migrate-playlist-progress
                            {--dry-run : Preview migration without mutating database}
                            {--cutoff=2026-08-28 23:59:59 : Cutoff timestamp for old playlists (created_at <= cutoff)}
                            {--playlist= : Specific Playlist ID to migrate}
                            {--chunk=1000 : Chunk size for batch inserts}
                            {--force : Force migration execution without confirmation}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Migrate historical learning progress from content_users to playlist_content_users for old playlists';

    /**
     * Execute the console command.
     */
    public function handle(HistoricalPlaylistProgressExecutionService $executionService): int
    {
        $isDryRun = (bool) $this->option('dry-run');
        $cutoff = (string) $this->option('cutoff');
        $specificPlaylistId = $this->option('playlist') !== null ? (int) $this->option('playlist') : null;
        $chunkSize = max(100, min(5000, (int) $this->option('chunk')));

        $this->info('==================================================');
        $this->info(' HISTORICAL PLAYLIST PROGRESS MIGRATION');
        $this->info('==================================================');
        $this->line("Mode      : " . ($isDryRun ? '<comment>DRY RUN (Preview Only)</comment>' : '<fg=red;options=bold>LIVE EXECUTION</>'));
        $this->line("Cutoff    : {$cutoff}");
        if ($specificPlaylistId !== null) {
            $this->line("Playlist  : #{$specificPlaylistId}");
        }
        $this->line("Chunk Size: {$chunkSize}");
        $this->newLine();

        // 1. Identify Old and New Playlists based on Cutoff
        $oldPlaylistQuery = DB::table('trainingplan')->where('created_at', '<=', $cutoff);
        $newPlaylistQuery = DB::table('trainingplan')->where('created_at', '>', $cutoff);

        if ($specificPlaylistId !== null) {
            $oldPlaylistQuery->where('id', $specificPlaylistId);
            $newPlaylistQuery->where('id', $specificPlaylistId);
        }

        $oldPlaylistIds = $oldPlaylistQuery->pluck('id')->map(fn ($id) => (int) $id)->all();
        $newPlaylistIds = $newPlaylistQuery->pluck('id')->map(fn ($id) => (int) $id)->all();

        $oldPlaylistCount = count($oldPlaylistIds);
        $newPlaylistCount = count($newPlaylistIds);

        // 2. Distinct contents in old playlists
        $connectedContentsCount = DB::table('trainingplandetail')
            ->whereIn('trainingplan_id', $oldPlaylistIds)
            ->distinct('content_id')
            ->count('content_id');

        // 3. Aggregate statistics of unique candidates (deduplicating content_users per user & content)
        $dedupedCuQuery = DB::table('content_users')
            ->groupBy('user_id', 'content_id')
            ->selectRaw("
                user_id,
                content_id,
                MAX(CASE WHEN finish = '1' OR progress >= 100 THEN 1 ELSE 0 END) as is_finished,
                MAX(progress) as max_progress
            ");

        if ($specificPlaylistId !== null) {
            $specificContentIds = DB::table('trainingplandetail')
                ->where('trainingplan_id', $specificPlaylistId)
                ->pluck('content_id');
            $dedupedCuQuery->whereIn('content_id', $specificContentIds);
        }

        $candidateQuery = DB::table('trainingplandetail as tpd')
            ->join('trainingplan as tp', 'tp.id', '=', 'tpd.trainingplan_id')
            ->joinSub($dedupedCuQuery, 'cu', function ($join) {
                $join->on('cu.content_id', '=', 'tpd.content_id');
            })
            ->where('tp.created_at', '<=', $cutoff);

        if ($specificPlaylistId !== null) {
            $candidateQuery->where('tp.id', $specificPlaylistId);
        }

        $stats = $candidateQuery->selectRaw("
            COUNT(*) as total_candidates,
            COUNT(DISTINCT cu.user_id) as affected_users,
            SUM(CASE WHEN cu.is_finished = 1 OR cu.max_progress >= 100 THEN 1 ELSE 0 END) as finished_count,
            SUM(CASE WHEN cu.is_finished = 0 AND cu.max_progress > 0 AND cu.max_progress < 100 THEN 1 ELSE 0 END) as in_progress_count,
            SUM(CASE WHEN cu.is_finished = 0 AND cu.max_progress = 0 THEN 1 ELSE 0 END) as zero_count
        ")->first();

        $totalCandidates = (int) ($stats->total_candidates ?? 0);
        $affectedUsers = (int) ($stats->affected_users ?? 0);
        $finishedCount = (int) ($stats->finished_count ?? 0);
        $inProgressCount = (int) ($stats->in_progress_count ?? 0);
        $zeroCount = (int) ($stats->zero_count ?? 0);

        // 4. Existing PCU and records that already exist
        $existingPcuQuery = DB::table('playlist_content_users');
        if ($specificPlaylistId !== null) {
            $existingPcuQuery->where('trainingplan_id', $specificPlaylistId);
        }
        $existingPcuTotal = $existingPcuQuery->count();

        $existsQuery = DB::table('trainingplandetail as tpd')
            ->join('trainingplan as tp', 'tp.id', '=', 'tpd.trainingplan_id')
            ->joinSub($dedupedCuQuery, 'cu', function ($join) {
                $join->on('cu.content_id', '=', 'tpd.content_id');
            })
            ->join('playlist_content_users as pcu', function ($join) {
                $join->on('pcu.user_id', '=', 'cu.user_id')
                     ->on('pcu.trainingplan_id', '=', 'tpd.trainingplan_id')
                     ->on('pcu.content_id', '=', 'cu.content_id');
            })
            ->where('tp.created_at', '<=', $cutoff);

        if ($specificPlaylistId !== null) {
            $existsQuery->where('tp.id', $specificPlaylistId);
        }

        $candidateAlreadyExists = $existsQuery->count();
        $toInsert = max(0, $totalCandidates - $candidateAlreadyExists);

        // Display Summary Table
        $this->table(
            ['Metric', 'Count'],
            [
                ['Old Playlists (created_at <= cutoff)', $oldPlaylistCount],
                ['New Playlists skipped (created_at > cutoff)', $newPlaylistCount],
                ['Contents linked to Old Playlists', $connectedContentsCount],
                ['Affected Users', $affectedUsers],
                ['Total Candidate PCU records', $totalCandidates],
                ['Existing PCU in database', $existingPcuTotal],
                ['Skipped (PCU already exists & authoritative)', $candidateAlreadyExists],
                ['New PCU to create', $toInsert],
                ['  ├─ Finished (100% / finish=1)', $finishedCount],
                ['  ├─ In-Progress (0% < progress < 100%)', $inProgressCount],
                ['  └─ Zero Progress (progress=0 & finish=0)', $zeroCount],
            ]
        );

        // Display sample candidates
        $sampleQuery = DB::table('trainingplandetail as tpd')
            ->join('trainingplan as tp', 'tp.id', '=', 'tpd.trainingplan_id')
            ->join('content_users as cu', 'cu.content_id', '=', 'tpd.content_id')
            ->join('content as c', 'c.id', '=', 'cu.content_id')
            ->where('tp.created_at', '<=', $cutoff);

        if ($specificPlaylistId !== null) {
            $sampleQuery->where('tp.id', $specificPlaylistId);
        }

        $samples = $sampleQuery->select([
            'cu.user_id',
            'tpd.trainingplan_id',
            'tp.title as playlist_title',
            'cu.content_id',
            'c.title as content_title',
            'cu.progress',
            'cu.finish',
            'cu.learning_status',
        ])
        ->orderBy('cu.user_id')
        ->orderByDesc('cu.finish')
        ->orderByDesc('cu.progress')
        ->orderByDesc('cu.current_time')
        ->orderByDesc('cu.id')
        ->limit(5)->get();

        if ($samples->isNotEmpty()) {
            $this->newLine();
            $this->info('Sample Candidate Records:');
            $sampleTable = [];
            foreach ($samples as $s) {
                $sampleTable[] = [
                    $s->user_id,
                    $s->trainingplan_id . ' - ' . mb_substr($s->playlist_title, 0, 25),
                    $s->content_id . ' - ' . mb_substr($s->content_title, 0, 25),
                    $s->progress . '%',
                    $s->finish,
                    $s->learning_status,
                ];
            }

            $this->table(
                ['User ID', 'Playlist', 'Content', 'Progress', 'Finish', 'Status'],
                $sampleTable
            );
        }

        if ($isDryRun) {
            $this->newLine();
            $this->warn('DRY RUN ONLY.');
            $this->line('No database mutations performed.');
            return self::SUCCESS;
        }

        // Live Execution Confirmation
        if (!$this->option('force')) {
            if (!$this->confirm("Are you sure you want to insert {$toInsert} records into playlist_content_users?")) {
                $this->warn('Migration aborted by user.');
                return self::SUCCESS;
            }
        }

        if ($totalCandidates === 0) {
            $this->info('No candidates found to migrate.');
            return self::SUCCESS;
        }

        $this->newLine();
        $this->info('Starting batch migration into playlist_content_users...');

        $insertedCount = 0;
        $startTime = microtime(true);

        try {
            $execution = $executionService->startExecution($cutoff, $specificPlaylistId, $chunkSize);

            $progressBar = $this->output->createProgressBar($totalCandidates);
            $progressBar->start();

            while (!$execution['is_completed']) {
                $stepResult = $executionService->executeStep($execution['execution_id']);
                if (!$stepResult['success']) {
                    $this->newLine();
                    $this->error('Migration failed: ' . ($stepResult['message'] ?? 'Unknown error'));
                    return self::FAILURE;
                }
                $progressBar->setProgress($stepResult['processed_records']);
                $execution = $stepResult;
            }

            $progressBar->finish();
            $this->newLine(2);
        } finally {
            $executionService->releaseLock();
        }

        $duration = round(microtime(true) - $startTime, 2);
        $insertedCount = $execution['inserted_records'] ?? 0;

        $this->info("Migration completed in {$duration} seconds.");
        $this->line("Successfully inserted: <info>{$insertedCount}</info> records into playlist_content_users.");
        $this->line("Skipped existing      : <comment>" . ($totalCandidates - $insertedCount) . "</comment> records.");

        return self::SUCCESS;
    }
}
