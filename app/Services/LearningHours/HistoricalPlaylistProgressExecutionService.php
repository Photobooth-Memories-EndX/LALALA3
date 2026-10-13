<?php

namespace App\Services\LearningHours;

use App\Models\LearningHourCalculationBatch;
use Carbon\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

/**
 * Service for Executing Historical Playlist Progress Migration.
 *
 * Single Source of Truth for both Web (Chunked AJAX) and CLI Command.
 *
 * Core Responsibilities:
 * - Resolve migration scope (cutoff, playlist IDs)
 * - Concurrency control & lock management (Cache lock + DB status)
 * - Safe chunked execution (default 1000 records/step)
 * - Best progress source selection (Phase 3 priority: finish DESC, progress DESC, current_time DESC, id DESC)
 * - Idempotency & preservation of existing PCU (insertOrIgnore)
 * - Execution state persistence (survives browser refresh/restart)
 * - Safe failure handling & retry support
 */
class HistoricalPlaylistProgressExecutionService
{
    public const DEFAULT_CUTOFF = PlaylistProgressDryRunService::DEFAULT_CUTOFF;
    public const DEFAULT_CHUNK_SIZE = 1000;
    public const BATCH_TYPE = 'historical_playlist_progress';
    public const LOCK_KEY = 'historical_playlist_migration_lock';
    public const LOCK_TTL = 1800; // 30 minutes, renewed on every chunk step
    public const ACTIVE_EXECUTION_CACHE_KEY = 'active_historical_playlist_migration_id';

    public function __construct(
        protected PlaylistProgressDryRunService $dryRunService
    ) {}

    /**
     * Resolve playlist migration scope based on cutoff timestamp and optional playlist ID.
     *
     * @return array{
     *     cutoff: string,
     *     playlist_id: ?int,
     *     old_playlist_ids: array<int>,
     *     old_playlist_count: int,
     *     playlist_titles: array<int, string>,
     * }
     */
    public function resolveScope(?string $cutoff = null, ?int $playlistId = null): array
    {
        $cutoff = !empty($cutoff) ? $cutoff : self::DEFAULT_CUTOFF;

        $query = DB::table('trainingplan')
            ->where('created_at', '<=', $cutoff)
            ->orderBy('id', 'asc');

        if ($playlistId !== null) {
            $query->where('id', $playlistId);
        }

        $playlists = $query->select(['id', 'title'])->get();

        $oldPlaylistIds = [];
        $playlistTitles = [];

        foreach ($playlists as $pl) {
            $id = (int) $pl->id;
            $oldPlaylistIds[] = $id;
            $playlistTitles[$id] = (string) $pl->title;
        }

        return [
            'cutoff'             => $cutoff,
            'playlist_id'        => $playlistId,
            'old_playlist_ids'   => $oldPlaylistIds,
            'old_playlist_count' => count($oldPlaylistIds),
            'playlist_titles'    => $playlistTitles,
        ];
    }

    /**
     * Start a new migration execution session.
     *
     * @throws \RuntimeException If another migration is already running
     */
    public function startExecution(
        string $cutoff = self::DEFAULT_CUTOFF,
        ?int $playlistId = null,
        int $chunkSize = self::DEFAULT_CHUNK_SIZE,
        ?int $userId = null
    ): array {
        // 1. Concurrency Check
        $running = $this->getRunningExecution();
        if ($running !== null) {
            throw new \RuntimeException('Migration is currently in progress.');
        }

        // 2. Acquire Atomic Lock
        if (!$this->acquireLock()) {
            throw new \RuntimeException('Migration is currently in progress.');
        }

        try {
            $scope = $this->resolveScope($cutoff, $playlistId);
            $oldPlaylistIds = $scope['old_playlist_ids'];
            $playlistTitles = $scope['playlist_titles'];

            // 3. Get candidate statistics matching dry run (fresh to guarantee accurate up-to-date counts)
            $summary = $this->dryRunService->getSummaryMetrics($cutoff, $playlistId, fresh: true);
            $totalCandidates = (int) ($summary['total_candidates'] ?? 0);
            $candidateAlreadyExists = (int) ($summary['skipped_existing_count'] ?? 0);
            $toInsertCount = (int) ($summary['to_insert_count'] ?? 0);

            $executionId = 'historical-playlist-progress-' . Str::uuid();

            $isAlreadyCompleted = ($totalCandidates > 0 && $toInsertCount === 0);
            $isImmediatelyCompleted = ($totalCandidates === 0 || empty($oldPlaylistIds) || $isAlreadyCompleted);

            $metadata = [
                'execution_id'             => $executionId,
                'cutoff'                   => $cutoff,
                'playlist_id'              => $playlistId,
                'playlist_ids'             => $oldPlaylistIds,
                'playlist_titles'          => $playlistTitles,
                'total_candidates'         => $totalCandidates,
                'candidate_already_exists' => $candidateAlreadyExists,
                'to_insert_count'          => $toInsertCount,
                'chunk_size'               => $chunkSize,
                'current_playlist_idx'     => $isAlreadyCompleted ? count($oldPlaylistIds) : 0,
                'current_offset'           => 0,
                'inserted_records'         => 0,
                'skipped_records'          => $isAlreadyCompleted ? $totalCandidates : 0,
                'already_completed'        => $isAlreadyCompleted,
                'started_at'               => now()->toIso8601String(),
                'finished_at'              => $isImmediatelyCompleted ? now()->toIso8601String() : null,
                'last_error'               => null,
                'message'                  => $isAlreadyCompleted
                    ? "All historical playlist progress has already been migrated. " . number_format($totalCandidates) . " of " . number_format($totalCandidates) . " records are already present."
                    : null,
            ];

            // 4. Create persistent execution batch
            $batch = LearningHourCalculationBatch::create([
                'type'              => self::BATCH_TYPE,
                'status'            => $isImmediatelyCompleted ? 'completed' : 'processing',
                'total_records'     => $totalCandidates,
                'processed_records' => $isAlreadyCompleted ? $totalCandidates : 0,
                'failed_records'    => 0,
                'last_processed_id' => null,
                'created_by'        => $userId,
                'started_at'        => now(),
                'finished_at'       => $isImmediatelyCompleted ? now() : null,
                'error_message'     => json_encode($metadata),
            ]);

            if ($isImmediatelyCompleted) {
                $this->releaseLock();
            } else {
                Cache::put(self::ACTIVE_EXECUTION_CACHE_KEY, $batch->id, 86400);
            }

            Log::info('Historical playlist progress migration execution started', [
                'execution_id'     => $executionId,
                'batch_id'         => $batch->id,
                'cutoff'           => $cutoff,
                'playlist_id'      => $playlistId,
                'total_candidates' => $totalCandidates,
                'chunk_size'       => $chunkSize,
                'created_by'       => $userId,
            ]);

            return $this->formatExecutionResponse($batch, $metadata, $isImmediatelyCompleted);
        } catch (\Throwable $e) {
            $this->releaseLock();
            throw $e;
        }
    }

    /**
     * Execute one chunk step for a given migration execution.
     *
     * Processes up to $chunkSize records from the current playlist.
     * When a playlist finishes, automatically advances to the next playlist.
     *
     * @param string|int $executionIdOrBatchId
     * @return array<string, mixed>
     */
    public function executeStep(string|int $executionIdOrBatchId): array
    {
        $batch = $this->resolveBatch($executionIdOrBatchId);

        if (!$batch) {
            return [
                'success'      => false,
                'message'      => 'Execution batch not found.',
                'is_completed' => true,
            ];
        }

        $metadata = json_decode($batch->error_message ?? '{}', true) ?: [];

        if ($batch->status === 'completed') {
            $this->releaseLock();
            return $this->formatExecutionResponse($batch, $metadata, true);
        }

        // Renew concurrency lock heartbeat
        $this->renewLock();

        // If batch was marked as failed, transition back to processing on retry
        if ($batch->status === 'failed') {
            $batch->status = 'processing';
        }

        $playlistIds = $metadata['playlist_ids'] ?? [];
        $currentIdx = (int) ($metadata['current_playlist_idx'] ?? 0);
        $currentOffset = (int) ($metadata['current_offset'] ?? 0);
        $chunkSize = max(1, (int) ($metadata['chunk_size'] ?? self::DEFAULT_CHUNK_SIZE));

        try {
            $chunkProcessed = 0;
            $insertedCount = 0;
            $skippedCount = 0;
            $activePlaylistTitle = null;

            while ($currentIdx < count($playlistIds)) {
                $currentPlaylistId = (int) $playlistIds[$currentIdx];
                $activePlaylistTitle = $metadata['playlist_titles'][$currentPlaylistId] ?? "Playlist #{$currentPlaylistId}";

                $contentIds = DB::table('trainingplandetail')
                    ->where('trainingplan_id', $currentPlaylistId)
                    ->pluck('content_id')
                    ->map(fn ($id) => (int) $id)
                    ->all();

                if (empty($contentIds)) {
                    $currentIdx++;
                    $currentOffset = 0;
                    continue;
                }

                if (!isset($metadata['playlist_counts'][$currentPlaylistId])) {
                    $metadata['playlist_counts'][$currentPlaylistId] = DB::table('content_users')
                        ->whereIn('content_id', $contentIds)
                        ->count();
                }
                $totalInPlaylist = (int) $metadata['playlist_counts'][$currentPlaylistId];

                if ($totalInPlaylist === 0 || $currentOffset >= $totalInPlaylist) {
                    $currentIdx++;
                    $currentOffset = 0;
                    continue;
                }

                // Phase 3 Ordering: user_id ASC, finish DESC, progress DESC, current_time DESC, id DESC
                $chunk = DB::table('content_users')
                    ->whereIn('content_id', $contentIds)
                    ->orderBy('user_id')
                    ->orderByDesc('finish')
                    ->orderByDesc('progress')
                    ->orderByDesc('current_time')
                    ->orderByDesc('id')
                    ->offset($currentOffset)
                    ->limit($chunkSize)
                    ->get();

                if ($chunk->isEmpty()) {
                    // Current playlist records exhausted, proceed to next
                    $currentIdx++;
                    $currentOffset = 0;
                    continue;
                }

                // Build PCU payload
                $payload = [];
                foreach ($chunk as $cu) {
                    $payload[] = [
                        'user_id'          => (int) $cu->user_id,
                        'trainingplan_id'  => (int) $currentPlaylistId,
                        'content_id'       => (int) $cu->content_id,
                        'progress'         => (int) ($cu->progress ?? 0),
                        'last_page'        => $cu->last_page !== null ? (int) $cu->last_page : null,
                        'total_pages'      => $cu->total_pages !== null ? (int) $cu->total_pages : null,
                        'current_time'     => (int) ($cu->current_time ?? 0),
                        'duration_seconds' => $cu->duration_seconds !== null ? (int) $cu->duration_seconds : null,
                        'learning_status'  => (string) ($cu->learning_status ?? 'not_started'),
                        'status'           => (string) ($cu->status ?? 'optional'),
                        'finish'           => (string) ($cu->finish ?? '0'),
                        'created_at'       => $cu->created_at ?? Carbon::now(),
                        'updated_at'       => $cu->updated_at ?? Carbon::now(),
                    ];
                }

                // Insert into playlist_content_users preserving existing (insertOrIgnore)
                $affected = !empty($payload) ? DB::table('playlist_content_users')->insertOrIgnore($payload) : 0;

                $chunkProcessed = count($chunk);
                $insertedCount = $affected;
                $skippedCount = $chunkProcessed - $affected;

                if ($currentOffset + $chunkProcessed >= $totalInPlaylist || $chunkProcessed < $chunkSize) {
                    $currentIdx++;
                    $currentOffset = 0;
                } else {
                    $currentOffset += $chunkProcessed;
                }

                // Chunk processed successfully, break to return step response
                break;
            }

            // Update execution state counters
            $metadata['inserted_records'] = ($metadata['inserted_records'] ?? 0) + $insertedCount;
            $metadata['skipped_records'] = ($metadata['skipped_records'] ?? 0) + $skippedCount;
            $metadata['current_playlist_idx'] = $currentIdx;
            $metadata['current_offset'] = $currentOffset;
            $batch->processed_records += $chunkProcessed;

            $isCompleted = ($currentIdx >= count($playlistIds));
            if ($isCompleted) {
                $batch->status = 'completed';
                $batch->finished_at = now();
                $metadata['finished_at'] = now()->toIso8601String();
                $this->releaseLock();
                Cache::forget(self::ACTIVE_EXECUTION_CACHE_KEY);
                $this->dryRunService->clearSummaryCache($metadata['cutoff'] ?? self::DEFAULT_CUTOFF, $metadata['playlist_id'] ?? null);
                if (!empty($metadata['playlist_id'])) {
                    $this->dryRunService->clearSummaryCache($metadata['cutoff'] ?? self::DEFAULT_CUTOFF, null);
                }

                Log::info('Historical playlist progress migration execution completed', [
                    'execution_id'      => $metadata['execution_id'] ?? null,
                    'batch_id'          => $batch->id,
                    'total_candidates'  => $batch->total_records,
                    'processed_records' => $batch->processed_records,
                    'inserted_records'  => $metadata['inserted_records'],
                    'skipped_records'   => $metadata['skipped_records'],
                    'duration_seconds'  => $batch->started_at ? $batch->started_at->diffInSeconds(now()) : 0,
                ]);
            }

            $batch->error_message = json_encode($metadata);
            $batch->touch();
            $batch->save();

            return $this->formatExecutionResponse($batch, $metadata, $isCompleted, $activePlaylistTitle);
        } catch (\Throwable $e) {
            $batch->status = 'failed';
            $batch->failed_records += 1;
            $metadata['last_error'] = $e->getMessage();
            $batch->error_message = json_encode($metadata);
            $batch->save();
            $this->releaseLock();

            Log::error('Historical playlist progress migration step failed', [
                'execution_id' => $metadata['execution_id'] ?? null,
                'batch_id'     => $batch->id,
                'error'        => $e->getMessage(),
            ]);

            return [
                'success'           => false,
                'message'           => 'Execution step failed: ' . $e->getMessage(),
                'execution_id'      => $metadata['execution_id'] ?? null,
                'batch_id'          => $batch->id,
                'status'            => 'failed',
                'can_retry'         => true,
                'is_completed'      => false,
                'processed_records' => (int) $batch->processed_records,
                'inserted_records'  => (int) ($metadata['inserted_records'] ?? 0),
                'skipped_records'   => (int) ($metadata['skipped_records'] ?? 0),
                'total_candidates'  => (int) $batch->total_records,
            ];
        }
    }

    /**
     * Get read-only status of an execution.
     */
    public function getStatus(string|int $executionIdOrBatchId): ?array
    {
        $batch = $this->resolveBatch($executionIdOrBatchId);
        if (!$batch) {
            return null;
        }

        $metadata = json_decode($batch->error_message ?? '{}', true) ?: [];
        $isCompleted = ($batch->status === 'completed');

        $currentIdx = (int) ($metadata['current_playlist_idx'] ?? 0);
        $playlistIds = $metadata['playlist_ids'] ?? [];
        $currentPlaylistId = $playlistIds[$currentIdx] ?? null;
        $currentPlaylistTitle = $currentPlaylistId ? ($metadata['playlist_titles'][$currentPlaylistId] ?? null) : null;

        return $this->formatExecutionResponse($batch, $metadata, $isCompleted, $currentPlaylistTitle);
    }

    /**
     * Get currently active running execution, or recently completed/failed execution within 15 minutes.
     */
    public function getActiveExecution(): ?array
    {
        $running = $this->getRunningExecution();
        if ($running !== null) {
            return $running;
        }

        $recentBatch = LearningHourCalculationBatch::where('type', self::BATCH_TYPE)
            ->whereIn('status', ['completed', 'failed'])
            ->where('updated_at', '>=', now()->subMinutes(15))
            ->latest('id')
            ->first();

        if (!$recentBatch) {
            return null;
        }

        $metadata = json_decode($recentBatch->error_message ?? '{}', true) ?: [];
        $isCompleted = ($recentBatch->status === 'completed');

        $currentIdx = (int) ($metadata['current_playlist_idx'] ?? 0);
        $playlistIds = $metadata['playlist_ids'] ?? [];
        $currentPlaylistId = $playlistIds[$currentIdx] ?? null;
        $currentPlaylistTitle = $currentPlaylistId ? ($metadata['playlist_titles'][$currentPlaylistId] ?? null) : null;

        return $this->formatExecutionResponse($recentBatch, $metadata, $isCompleted, $currentPlaylistTitle);
    }

    /**
     * Get currently processing execution batch, if any.
     */
    public function getRunningExecution(): ?array
    {
        $activeBatch = LearningHourCalculationBatch::where('type', self::BATCH_TYPE)
            ->where('status', 'processing')
            ->where('updated_at', '>=', now()->subMinutes(30))
            ->latest('id')
            ->first();

        if (!$activeBatch) {
            return null;
        }

        $metadata = json_decode($activeBatch->error_message ?? '{}', true) ?: [];

        $currentIdx = (int) ($metadata['current_playlist_idx'] ?? 0);
        $playlistIds = $metadata['playlist_ids'] ?? [];
        $currentPlaylistId = $playlistIds[$currentIdx] ?? null;
        $currentPlaylistTitle = $currentPlaylistId ? ($metadata['playlist_titles'][$currentPlaylistId] ?? null) : null;

        return $this->formatExecutionResponse($activeBatch, $metadata, false, $currentPlaylistTitle);
    }

    /**
     * Acquire atomic application lock.
     */
    public function acquireLock(): bool
    {
        return (bool) Cache::lock(self::LOCK_KEY, self::LOCK_TTL)->get();
    }

    /**
     * Renew the active lock heartbeat.
     */
    public function renewLock(): bool
    {
        Cache::put('historical_playlist_migration_lock_heartbeat', now()->timestamp, self::LOCK_TTL);
        Cache::put(self::ACTIVE_EXECUTION_CACHE_KEY, true, self::LOCK_TTL);
        return true;
    }

    /**
     * Release application lock.
     */
    public function releaseLock(): void
    {
        Cache::lock(self::LOCK_KEY, self::LOCK_TTL)->forceRelease();
        Cache::forget('historical_playlist_migration_lock_heartbeat');
        Cache::forget(self::ACTIVE_EXECUTION_CACHE_KEY);
    }

    /**
     * Check if an active execution lock is held.
     */
    public function isLocked(): bool
    {
        return $this->getRunningExecution() !== null;
    }

    /**
     * Resolve batch by ID or by metadata execution_id.
     */
    protected function resolveBatch(string|int $executionIdOrBatchId): ?LearningHourCalculationBatch
    {
        if (is_numeric($executionIdOrBatchId)) {
            return LearningHourCalculationBatch::find((int) $executionIdOrBatchId);
        }

        $idStr = (string) $executionIdOrBatchId;

        if (preg_match('/^historical-playlist-progress-(\d+)$/', $idStr, $m)) {
            return LearningHourCalculationBatch::find((int) $m[1]);
        }

        return LearningHourCalculationBatch::where('type', self::BATCH_TYPE)
            ->where('error_message', 'like', '%' . $idStr . '%')
            ->latest('id')
            ->first();
    }

    /**
     * Format standard execution status response.
     */
    protected function formatExecutionResponse(
        LearningHourCalculationBatch $batch,
        array $metadata,
        bool $isCompleted = false,
        ?string $currentPlaylistTitle = null
    ): array {
        $totalCandidates = (int) $batch->total_records;
        $processedRecords = (int) $batch->processed_records;
        $chunkSize = max(1, (int) ($metadata['chunk_size'] ?? self::DEFAULT_CHUNK_SIZE));

        $totalBatches = $totalCandidates > 0 ? (int) ceil($totalCandidates / $chunkSize) : 0;
        $currentBatch = $chunkSize > 0 ? min($totalBatches, (int) ceil($processedRecords / $chunkSize)) : 0;

        $percentage = $totalCandidates > 0
            ? round(min(100.0, ($processedRecords / $totalCandidates) * 100), 1)
            : 100.0;

        if ($isCompleted) {
            $percentage = 100.0;
            $currentBatch = $totalBatches;
        }

        $playlistIds = $metadata['playlist_ids'] ?? [];
        $currentIdx = (int) ($metadata['current_playlist_idx'] ?? 0);

        $durationSeconds = null;
        if ($batch->started_at) {
            $endTime = $batch->finished_at ?? now();
            $durationSeconds = $batch->started_at->diffInSeconds($endTime);
        }

        $alreadyCompleted = (bool) ($metadata['already_completed'] ?? false);

        return [
            'success'              => true,
            'execution_id'         => $metadata['execution_id'] ?? ('historical-playlist-progress-' . $batch->id),
            'batch_id'             => $batch->id,
            'status'               => $batch->status,
            'is_completed'         => $isCompleted,
            'already_completed'    => $alreadyCompleted,
            'percentage'           => $percentage,
            'total_candidates'     => $totalCandidates,
            'processed_records'    => $processedRecords,
            'inserted_records'     => (int) ($metadata['inserted_records'] ?? 0),
            'skipped_records'      => (int) ($metadata['skipped_records'] ?? 0),
            'batch_number'         => $currentBatch,
            'total_batches'        => $totalBatches,
            'chunk_size'           => $chunkSize,
            'cutoff'               => $metadata['cutoff'] ?? self::DEFAULT_CUTOFF,
            'playlist_id'          => $metadata['playlist_id'] ?? null,
            'current_playlist'     => $currentPlaylistTitle ?? ($isCompleted ? 'All Completed' : 'Initializing...'),
            'current_playlist_idx' => $currentIdx,
            'total_playlists'      => count($playlistIds),
            'started_at'           => $batch->started_at?->toIso8601String(),
            'finished_at'          => $batch->finished_at?->toIso8601String(),
            'duration_seconds'     => $durationSeconds,
            'message'              => $metadata['message'] ?? ($alreadyCompleted ? 'All historical playlist progress has already been migrated.' : null),
            'last_error'           => $metadata['last_error'] ?? null,
        ];
    }
}
