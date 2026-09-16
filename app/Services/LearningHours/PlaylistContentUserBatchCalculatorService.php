<?php

namespace App\Services\LearningHours;

use Illuminate\Support\Facades\DB;

class PlaylistContentUserBatchCalculatorService
{
    public function __construct(
        protected ActualLearningHourCalculator $calculator
    ) {}

    /**
     * Process calculating and persisting actual learning hours in batches.
     *
     * @param array{
     *     chunk_size?: int,
     *     dry_run?: bool,
     *     force?: bool,
     *     playlist_id?: ?int,
     *     user_id?: ?int,
     *     content_id?: ?int,
     *     max_records?: ?int,
     * } $options
     * @param callable|null $onProgress Callback fn(int $batchNumber, int $processedInBatch, int $totalProcessed, array $batchStats)
     * @return array{
     *     total_scanned: int,
     *     total_updated: int,
     *     total_skipped: int,
     *     total_historical_fallback: int,
     *     total_zero: int,
     *     total_missing_content: int,
     *     total_concurrent_changed: int,
     *     total_failed: int,
     *     total_batches: int,
     *     duration_seconds: float,
     *     average_batch_runtime: float,
     *     dry_run: bool,
     *     samples: array<int, array<string, mixed>>,
     * }
     */
    public function process(array $options = [], ?callable $onProgress = null): array
    {
        $startTime = microtime(true);
        $chunkSize = max(50, (int) ($options['chunk_size'] ?? config('learning_hours.playlist_content_user.chunk_size', 500)));
        $isDryRun = (bool) ($options['dry_run'] ?? false);
        $force = (bool) ($options['force'] ?? false);
        $playlistId = !empty($options['playlist_id']) ? (int) $options['playlist_id'] : null;
        $userId = !empty($options['user_id']) ? (int) $options['user_id'] : null;
        $contentId = !empty($options['content_id']) ? (int) $options['content_id'] : null;
        $maxRecords = !empty($options['max_records']) ? (int) $options['max_records'] : null;

        // 1. Preload active master contents into memory (duration parsed to seconds)
        // 1 single query for the entire batch execution (only 182 contents in DB)
        $contentsMap = DB::table('content')
            ->whereNull('deleted_at')
            ->pluck('duration', 'id')
            ->mapWithKeys(fn ($duration, $id) => [(int) $id => $this->calculator->parseContentDurationToSeconds($duration)])
            ->all();

        // 2. Build base query
        $query = DB::table('playlist_content_users');

        if ($playlistId !== null) {
            $query->where('trainingplan_id', $playlistId);
        }
        if ($userId !== null) {
            $query->where('user_id', $userId);
        }
        if ($contentId !== null) {
            $query->where('content_id', $contentId);
        }

        $totalScanned = 0;
        $totalUpdated = 0;
        $totalSkipped = 0;
        $totalHistoricalFallback = 0;
        $totalZero = 0;
        $totalMissingContent = 0;
        $totalConcurrentChanged = 0;
        $totalFailed = 0;
        $batchCount = 0;
        $lastId = 0;
        $samples = [];

        while (true) {
            if ($maxRecords !== null && $totalScanned >= $maxRecords) {
                break;
            }

            $currentLimit = $chunkSize;
            if ($maxRecords !== null) {
                $remaining = $maxRecords - $totalScanned;
                if ($remaining <= 0) {
                    break;
                }
                $currentLimit = min($chunkSize, $remaining);
            }

            // Keyset pagination using primary key index
            $chunk = (clone $query)
                ->where('id', '>', $lastId)
                ->orderBy('id', 'asc')
                ->limit($currentLimit)
                ->get([
                    'id',
                    'user_id',
                    'trainingplan_id',
                    'content_id',
                    'progress',
                    'finish',
                    'current_time',
                    'duration_seconds',
                    'learning_hour',
                    'completed_at',
                    'created_at',
                    'updated_at',
                ]);

            if ($chunk->isEmpty()) {
                break;
            }

            $batchCount++;
            $batchUpdated = 0;
            $batchSkipped = 0;
            $batchHistorical = 0;
            $batchZero = 0;
            $batchMissing = 0;

            // Group record IDs by their target learning_hour value AND current_time snapshot
            // for safe concurrency-protected bulk updates
            $updateGroups = [];
            $completedAtIds = [];

            foreach ($chunk as $record) {
                $lastId = (int) $record->id;
                $totalScanned++;

                $cid = (int) $record->content_id;
                $contentDurationSeconds = $contentsMap[$cid] ?? null;

                if ($contentDurationSeconds === null && !empty($record->duration_seconds)) {
                    $contentDurationSeconds = (int) $record->duration_seconds;
                }

                $isFinished = ((string) $record->finish === '1' || (int) $record->progress >= 100);
                $currTime = (int) ($record->current_time ?? 0);

                // Rule and fallback tracking
                $ruleApplied = 'current_time';
                if ($currTime > 0) {
                    $ruleApplied = 'actual_tracked_time';
                } elseif ($isFinished) {
                    if ($contentDurationSeconds !== null) {
                        $totalHistoricalFallback++;
                        $batchHistorical++;
                        $ruleApplied = 'historical_content_duration';
                    } else {
                        $totalMissingContent++;
                        $batchMissing++;
                        $ruleApplied = 'missing_content_fallback';
                    }
                } else {
                    $totalZero++;
                    $batchZero++;
                    $ruleApplied = 'unfinished_zero';
                }

                // Resolve actual seconds and calculate
                $actualSeconds = $this->calculator->resolveActualSeconds(
                    $record->current_time,
                    $record->finish,
                    $record->progress,
                    $contentDurationSeconds
                );

                $calculatedHour = $this->calculator->calculate($actualSeconds) ?? 0.0;

                if ($calculatedHour == 0.0 && $ruleApplied !== 'unfinished_zero') {
                    $totalZero++;
                    $batchZero++;
                }

                // Collect samples (up to 10 distinct records)
                if (count($samples) < 10) {
                    $samples[] = [
                        'id'              => $record->id,
                        'current_time'    => $currTime,
                        'finish'          => (string) $record->finish,
                        'progress'        => (int) $record->progress,
                        'calculated_hour' => $calculatedHour,
                        'existing_hour'   => $record->learning_hour,
                        'rule'            => $ruleApplied,
                    ];
                }

                // Check completed_at
                if ($isFinished && $record->completed_at === null) {
                    $completedAtIds[] = $record->id;
                }

                // Only update when necessary (unless force is requested)
                if (!$force && $record->learning_hour !== null && abs((float) $record->learning_hour - $calculatedHour) < 0.0001) {
                    $totalSkipped++;
                    $batchSkipped++;
                    continue;
                }

                // Concurrency Protection key: group by hour value + snapshot of current_time
                $key = number_format($calculatedHour, 4, '.', '') . '|' . $currTime;
                if (!isset($updateGroups[$key])) {
                    $updateGroups[$key] = [
                        'hour'          => (float) $calculatedHour,
                        'snapshot_time' => $currTime,
                        'ids'           => [],
                    ];
                }
                $updateGroups[$key]['ids'][] = $record->id;

                $totalUpdated++;
                $batchUpdated++;
            }

            // Perform bulk update for this batch (if not dry run)
            if (!$isDryRun && (!empty($updateGroups) || !empty($completedAtIds))) {
                try {
                    DB::transaction(function () use ($updateGroups, $completedAtIds, &$totalConcurrentChanged, &$totalUpdated) {
                        $actualAffectedTotal = 0;
                        $expectedTargetTotal = 0;

                        foreach ($updateGroups as $group) {
                            $expectedTargetTotal += count($group['ids']);

                            // Atomically update only where current_time has NOT changed during read
                            $affected = DB::table('playlist_content_users')
                                ->whereIn('id', $group['ids'])
                                ->where('current_time', $group['snapshot_time'])
                                ->update(['learning_hour' => $group['hour']]);

                            $actualAffectedTotal += $affected;
                        }

                        // Concurrency difference detection: rows modified by live learners during batch
                        $concurrentDifference = max(0, $expectedTargetTotal - $actualAffectedTotal);
                        if ($concurrentDifference > 0) {
                            $totalConcurrentChanged += $concurrentDifference;
                            $totalUpdated -= $concurrentDifference;
                        }

                        if (!empty($completedAtIds)) {
                            DB::table('playlist_content_users')
                                ->whereIn('id', $completedAtIds)
                                ->whereNull('completed_at')
                                ->update([
                                    'completed_at' => DB::raw('COALESCE(updated_at, created_at, NOW())'),
                                ]);
                        }
                    });
                } catch (\Throwable $e) {
                    $totalFailed += count($chunk);
                    report($e);
                }
            }

            if ($onProgress !== null) {
                $onProgress($batchCount, count($chunk), $totalScanned, [
                    'updated'             => $batchUpdated,
                    'skipped'             => $batchSkipped,
                    'historical_fallback' => $batchHistorical,
                    'zero'                => $batchZero,
                    'missing_content'     => $batchMissing,
                ]);
            }

            // Free batch chunk from memory
            unset($chunk, $updateGroups, $completedAtIds);
        }

        $duration = round(microtime(true) - $startTime, 3);
        $averageBatchRuntime = $batchCount > 0 ? round($duration / $batchCount, 4) : 0.0;

        return [
            'total_scanned'             => $totalScanned,
            'total_updated'             => $totalUpdated,
            'total_skipped'             => $totalSkipped,
            'total_historical_fallback' => $totalHistoricalFallback,
            'total_zero'                => $totalZero,
            'total_missing_content'     => $totalMissingContent,
            'total_concurrent_changed'  => $totalConcurrentChanged,
            'total_failed'              => $totalFailed,
            'total_batches'             => $batchCount,
            'batch_count'               => $batchCount,
            'duration_seconds'          => $duration,
            'average_batch_runtime'     => $averageBatchRuntime,
            'dry_run'                   => $isDryRun,
            'samples'                   => $samples,
        ];
    }
}
