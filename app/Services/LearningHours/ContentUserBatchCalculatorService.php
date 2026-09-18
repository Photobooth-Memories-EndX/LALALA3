<?php

namespace App\Services\LearningHours;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class ContentUserBatchCalculatorService
{
    public function __construct(
        protected ActualLearningHourCalculator $calculator
    ) {}

    /**
     * Process calculating and persisting actual learning hours for content_users in batches.
     *
     * @param array{
     *     chunk_size?: int,
     *     dry_run?: bool,
     *     force?: bool,
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
     *     batch_count: int,
     *     duration_seconds: float,
     *     average_batch_runtime: float,
     *     peak_memory_mb: float,
     *     dry_run: bool,
     *     samples: array<int, array<string, mixed>>,
     * }
     */
    public function process(array $options = [], ?callable $onProgress = null): array
    {
        $startTime = microtime(true);
        $chunkSize = max(50, (int) ($options['chunk_size'] ?? 2500));
        $isDryRun = (bool) ($options['dry_run'] ?? false);
        $force = (bool) ($options['force'] ?? false);
        $userId = !empty($options['user_id']) ? (int) $options['user_id'] : null;
        $contentId = !empty($options['content_id']) ? (int) $options['content_id'] : null;
        $maxRecords = !empty($options['max_records']) ? (int) $options['max_records'] : null;

        // 1. Preload active master contents into memory (duration parsed to seconds)
        // 1 single query for the entire batch execution (0 N+1)
        $contentsMap = DB::table('content')
            ->whereNull('deleted_at')
            ->pluck('duration', 'id')
            ->mapWithKeys(fn ($duration, $id) => [(int) $id => $this->calculator->parseContentDurationToSeconds($duration)])
            ->all();

        // 2. Build base query
        $query = DB::table('content_users');

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
                    'content_id',
                    'progress',
                    'finish',
                    'current_time',
                    'duration_seconds',
                    'learning_hours',
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

            // Group record IDs by calculated learning_hours value AND current_time snapshot
            // for safe concurrency-protected bulk updates
            $updateGroups = [];
            $completedAtIds = [];
            $orphanIds = [];
            $batchTargetRecords = [];

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

                // Orphan record detection (content missing/deleted and no stored duration)
                if (!isset($contentsMap[$cid]) && empty($record->duration_seconds)) {
                    $totalMissingContent++;
                    $batchMissing++;
                    $orphanIds[] = (int) $record->id;
                    continue;
                }

                if ($currTime > 0) {
                    $ruleApplied = 'actual_tracked_time';
                } elseif ($isFinished) {
                    $totalHistoricalFallback++;
                    $batchHistorical++;
                    $ruleApplied = 'historical_content_duration';
                } else {
                    $totalZero++;
                    $batchZero++;
                    $ruleApplied = 'unfinished_zero';
                }

                // Resolve actual seconds and calculate using pure calculator
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
                        'existing_hour'   => $record->learning_hours !== null ? (float) $record->learning_hours : null,
                        'rule'            => $ruleApplied,
                    ];
                }

                // Check completed_at
                if ($isFinished && $record->completed_at === null) {
                    $completedAtIds[] = (int) $record->id;
                }

                // Only update when necessary (unless force is requested)
                if (!$force && $record->learning_hours !== null && abs((float) $record->learning_hours - $calculatedHour) < 0.0001) {
                    $totalSkipped++;
                    $batchSkipped++;
                    continue;
                }

                $batchTargetRecords[(int) $record->id] = [
                    'snapshot_time' => $currTime,
                    'hour'          => (float) $calculatedHour,
                ];

                // Concurrency Protection key: group by hour value + snapshot of current_time
                $key = number_format($calculatedHour, 4, '.', '') . '|' . $currTime;
                if (!isset($updateGroups[$key])) {
                    $updateGroups[$key] = [
                        'hour'          => (float) $calculatedHour,
                        'snapshot_time' => $currTime,
                        'ids'           => [],
                    ];
                }
                $updateGroups[$key]['ids'][] = (int) $record->id;
            }

            // Perform bulk update for this batch (if not dry run)
            if ($isDryRun) {
                $totalUpdated += count($batchTargetRecords);
                $batchUpdated += count($batchTargetRecords);
            } elseif (!empty($updateGroups) || !empty($completedAtIds) || !empty($orphanIds)) {
                try {
                    DB::transaction(function () use ($updateGroups, $completedAtIds, $orphanIds, $batchTargetRecords, &$totalConcurrentChanged, &$totalUpdated, &$batchUpdated) {
                        $actualAffectedTotal = 0;
                        $expectedTargetTotal = count($batchTargetRecords);

                        foreach ($updateGroups as $group) {
                            // Atomically update only where current_time has NOT changed during read
                            $affected = DB::table('content_users')
                                ->whereIn('id', $group['ids'])
                                ->where('current_time', $group['snapshot_time'])
                                ->update(['learning_hours' => $group['hour']]);

                            $actualAffectedTotal += $affected;
                        }

                        if ($actualAffectedTotal === $expectedTargetTotal) {
                            $totalUpdated += $expectedTargetTotal;
                            $batchUpdated += $expectedTargetTotal;
                        } else {
                            $targetIds = array_keys($batchTargetRecords);
                            $freshSnapshots = DB::table('content_users')
                                ->whereIn('id', $targetIds)
                                ->pluck('current_time', 'id');

                            $batchActualUpdated = 0;
                            $batchConcurrencySkipped = 0;

                            foreach ($batchTargetRecords as $targetId => $targetData) {
                                $freshTime = $freshSnapshots[$targetId] ?? null;

                                if ($freshTime === null || (int) $freshTime !== $targetData['snapshot_time']) {
                                    $batchConcurrencySkipped++;
                                } else {
                                    $batchActualUpdated++;
                                }
                            }

                            $totalUpdated += $batchActualUpdated;
                            $batchUpdated += $batchActualUpdated;
                            $totalConcurrentChanged += $batchConcurrencySkipped;
                        }

                        if (!empty($completedIds)) {
                            DB::table('content_users')
                                ->whereIn('id', $completedIds)
                                ->whereNull('completed_at')
                                ->update([
                                    'completed_at' => DB::raw('COALESCE(updated_at, created_at, NOW())'),
                                ]);
                        }

                        if (!empty($orphanIds)) {
                            DB::table('content_users')
                                ->whereIn('id', $orphanIds)
                                ->update([
                                    'learning_hours' => null,
                                    'completed_at'   => null,
                                ]);
                        }
                    });
                } catch (\Throwable $e) {
                    $totalFailed += count($chunk);
                    Log::error('ContentUserBatchCalculatorService: Batch update error', [
                        'batch'   => $batchCount,
                        'error'   => $e->getMessage(),
                        'last_id' => $lastId,
                    ]);
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

            unset($chunk, $updateGroups, $completedAtIds, $batchTargetRecords);
        }

        $duration = round(microtime(true) - $startTime, 3);
        $averageBatchRuntime = $batchCount > 0 ? round($duration / $batchCount, 4) : 0.0;
        $peakMemoryMb = round(memory_get_peak_usage(true) / 1024 / 1024, 2);

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
            'peak_memory_mb'            => $peakMemoryMb,
            'dry_run'                   => $isDryRun,
            'samples'                   => $samples,
        ];
    }
}
