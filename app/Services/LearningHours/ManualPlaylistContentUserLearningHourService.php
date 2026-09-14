<?php

namespace App\Services\LearningHours;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class ManualPlaylistContentUserLearningHourService
{
    public const LOCK_KEY = 'manual_playlist_content_user_learning_hour_calc';
    public const LOCK_TTL = 300; // 5 minutes

    public function __construct(
        protected ActualLearningHourCalculator $calculator
    ) {}

    /**
     * Execute manual bulk calculation of learning hours for playlist content users.
     *
     * @param array<string, mixed> $criteria Filter criteria (mode, trainingplan_id, finish_status, calculation_source, etc.)
     * @param array<string, mixed> $options Execution options (dry_run, force, chunk_size, admin_id, admin_name)
     * @return array<string, mixed>
     * @throws \RuntimeException
     */
    public function calculate(array $criteria = [], array $options = []): array
    {
        // 1. Concurrency Protection across Admins (Cache Lock)
        $lock = Cache::lock(self::LOCK_KEY, self::LOCK_TTL);
        if (!$lock->get()) {
            throw new \RuntimeException('Another bulk calculation is currently in progress. Please wait until it completes.');
        }

        $startTime = microtime(true);
        $startedAt = now()->toDateTimeString();

        @set_time_limit(600);
        @ini_set('memory_limit', '512M');

        try {
            $mode = ($criteria['mode'] ?? 'all') === 'filtered' ? 'filtered' : 'all';
            $isDryRun = (bool) ($options['dry_run'] ?? false);
            $force = (bool) ($options['force'] ?? false);
            $chunkSize = max(50, (int) ($options['chunk_size'] ?? config('learning_hours.playlist_content_user.chunk_size', 500)));
            $maxRecords = !empty($options['max_records']) ? (int) $options['max_records'] : null;

            // 2. Preload active master contents into memory (duration parsed to seconds)
            // 1 single query for the entire batch execution (0 N+1)
            $contentsMap = DB::table('content')
                ->whereNull('deleted_at')
                ->pluck('duration', 'id')
                ->mapWithKeys(fn ($duration, $id) => [(int) $id => $this->calculator->parseContentDurationToSeconds($duration)])
                ->all();

            // 3. Build base query with criteria
            $baseQuery = DB::table('playlist_content_users');

            if ($mode === 'filtered') {
                $this->applyCriteriaFilters($baseQuery, $criteria);
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

            // 4. Keyset pagination execution
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

                $chunk = (clone $baseQuery)
                    ->where('playlist_content_users.id', '>', $lastId)
                    ->orderBy('playlist_content_users.id', 'asc')
                    ->limit($currentLimit)
                    ->get([
                        'playlist_content_users.id',
                        'playlist_content_users.user_id',
                        'playlist_content_users.trainingplan_id',
                        'playlist_content_users.content_id',
                        'playlist_content_users.progress',
                        'playlist_content_users.finish',
                        'playlist_content_users.current_time',
                        'playlist_content_users.duration_seconds',
                        'playlist_content_users.actual_watch_seconds',
                        'playlist_content_users.learning_hour',
                        'playlist_content_users.completed_at',
                        'playlist_content_users.created_at',
                        'playlist_content_users.updated_at',
                    ]);

                if ($chunk->isEmpty()) {
                    break;
                }

                $batchCount++;
                $updateGroups = [];
                $completedAtIds = [];
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
                    $actualWatch = (int) ($record->actual_watch_seconds ?? 0);

                    // Track rule applied
                    $ruleApplied = 'current_time';
                    if ($actualWatch > 0) {
                        $ruleApplied = 'actual_watch_seconds';
                    } elseif ($currTime > 0) {
                        $ruleApplied = 'actual_tracked_time';
                    } elseif ($isFinished) {
                        if ($contentDurationSeconds !== null) {
                            $totalHistoricalFallback++;
                            $ruleApplied = 'historical_content_duration';
                        } else {
                            $totalMissingContent++;
                            $ruleApplied = 'missing_content_fallback';
                        }
                    } else {
                        $totalZero++;
                        $ruleApplied = 'unfinished_zero';
                    }

                    // Calculate actual learning hour using shared pure calculator
                    $actualSeconds = $this->calculator->resolveActualSeconds(
                        $record->current_time,
                        $record->finish,
                        $record->progress,
                        $contentDurationSeconds,
                        $record->actual_watch_seconds ?? 0
                    );

                    $calculatedHour = $this->calculator->calculate($actualSeconds) ?? 0.0;

                    if ($calculatedHour == 0.0 && $ruleApplied !== 'unfinished_zero') {
                        $totalZero++;
                    }

                    if (count($samples) < 5) {
                        $samples[] = [
                            'id'              => $record->id,
                            'current_time'    => $currTime,
                            'finish'          => (string) $record->finish,
                            'progress'        => (int) $record->progress,
                            'calculated_hour' => $calculatedHour,
                            'existing_hour'   => $record->learning_hour !== null ? (float) $record->learning_hour : null,
                            'rule'            => $ruleApplied,
                        ];
                    }

                    // Check missing completed_at on completed records
                    if ($isFinished && $record->completed_at === null) {
                        $completedAtIds[] = (int) $record->id;
                    }

                    // Skipped check: if existing value is already identical and not forced
                    if (!$force && $record->learning_hour !== null && abs((float) $record->learning_hour - $calculatedHour) < 0.0001) {
                        $totalSkipped++;
                        continue;
                    }

                    // Record is targeted for calculation
                    $batchTargetRecords[(int) $record->id] = [
                        'snapshot_time' => $currTime,
                        'hour'          => (float) $calculatedHour,
                    ];

                    // Group by target calculated hour + snapshot of current_time for concurrency safety
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

                if ($isDryRun) {
                    $totalUpdated += count($batchTargetRecords);
                } elseif (!empty($updateGroups) || !empty($completedAtIds)) {
                    try {
                        DB::transaction(function () use ($updateGroups, $completedAtIds, $batchTargetRecords, &$totalConcurrentChanged, &$totalUpdated) {
                            $actualAffectedTotal = 0;
                            $expectedTargetTotal = count($batchTargetRecords);

                            foreach ($updateGroups as $group) {
                                // Safe atomic bulk update: Only updates if current_time hasn't changed
                                $affected = DB::table('playlist_content_users')
                                    ->whereIn('id', $group['ids'])
                                    ->where('current_time', $group['snapshot_time'])
                                    ->update(['learning_hour' => $group['hour']]);

                                $actualAffectedTotal += $affected;
                            }

                            if ($actualAffectedTotal === $expectedTargetTotal) {
                                // All records were physically updated by MySQL
                                $totalUpdated += $expectedTargetTotal;
                            } else {
                                // Differentiate between true concurrency (current_time changed by learner)
                                // and MySQL 0 affected rows (value in DB was already identical to calculated value)
                                $targetIds = array_keys($batchTargetRecords);
                                $freshSnapshots = DB::table('playlist_content_users')
                                    ->whereIn('id', $targetIds)
                                    ->pluck('current_time', 'id');

                                $batchUpdated = 0;
                                $batchConcurrencySkipped = 0;

                                foreach ($batchTargetRecords as $targetId => $targetData) {
                                    $freshTime = $freshSnapshots[$targetId] ?? null;

                                    if ($freshTime === null || (int) $freshTime !== $targetData['snapshot_time']) {
                                        // Learner heartbeat changed current_time during batch
                                        $batchConcurrencySkipped++;
                                    } else {
                                        // Record matched snapshot and is verified at target state
                                        $batchUpdated++;
                                    }
                                }

                                $totalUpdated += $batchUpdated;
                                $totalConcurrentChanged += $batchConcurrencySkipped;
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
                        Log::error('ManualPlaylistContentUserLearningHourService: Batch update error', [
                            'batch'   => $batchCount,
                            'error'   => $e->getMessage(),
                            'last_id' => $lastId,
                        ]);
                    }
                }
            }

            $duration = round(microtime(true) - $startTime, 2);
            $finishedAt = now()->toDateTimeString();

            // 5. Audit Logging
            Log::info('manual_playlist_content_user_learning_hour_calculation', [
                'admin_id'                 => $options['admin_id'] ?? null,
                'admin_name'               => $options['admin_name'] ?? null,
                'mode'                     => $mode,
                'dry_run'                  => $isDryRun,
                'started_at'               => $startedAt,
                'finished_at'              => $finishedAt,
                'duration_seconds'         => $duration,
                'total_scanned'            => $totalScanned,
                'total_updated'            => $totalUpdated,
                'total_skipped'            => $totalSkipped,
                'total_historical_fallback'=> $totalHistoricalFallback,
                'total_zero'               => $totalZero,
                'total_concurrent_changed' => $totalConcurrentChanged,
                'total_concurrency_skipped' => $totalConcurrentChanged,
                'total_failed'             => $totalFailed,
                'total_batches'            => $batchCount,
            ]);

            return [
                'success'                  => true,
                'mode'                     => $mode,
                'dry_run'                  => $isDryRun,
                'started_at'               => $startedAt,
                'finished_at'              => $finishedAt,
                'duration_seconds'         => $duration,
                'total_scanned'            => $totalScanned,
                'total_updated'            => $totalUpdated,
                'total_skipped'            => $totalSkipped,
                'total_concurrency_skipped'=> $totalConcurrentChanged,
                'total_concurrent_changed' => $totalConcurrentChanged,
                'total_historical_fallback'=> $totalHistoricalFallback,
                'total_zero'               => $totalZero,
                'total_missing_content'    => $totalMissingContent,
                'total_failed'             => $totalFailed,
                'total_batches'            => $batchCount,
                'samples'                  => $samples,
            ];
        } finally {
            $lock->release();
        }
    }

    /**
     * Apply filter criteria onto query builder safely using whitelists.
     */
    protected function applyCriteriaFilters(\Illuminate\Database\Query\Builder $query, array $criteria): void
    {
        // 1. Training Plan (Playlist)
        $tpId = $criteria['trainingplan_id'] ?? $criteria['playlist_id'] ?? null;
        if (!empty($tpId) && $tpId !== 'all') {
            $query->where('playlist_content_users.trainingplan_id', (int) $tpId);
        }

        // 2. Content ID
        if (!empty($criteria['content_id']) && $criteria['content_id'] !== 'all') {
            $query->where('playlist_content_users.content_id', (int) $criteria['content_id']);
        }

        // 3. User ID
        if (!empty($criteria['user_id']) && $criteria['user_id'] !== 'all') {
            $query->where('playlist_content_users.user_id', (int) $criteria['user_id']);
        }

        // 4. Finish / Learning Status
        $finishStatus = $criteria['finish_status'] ?? $criteria['status'] ?? null;
        if (!empty($finishStatus) && $finishStatus !== 'all') {
            if ($finishStatus === 'completed') {
                $query->where(function ($q) {
                    $q->where('playlist_content_users.finish', '1')
                      ->orWhere('playlist_content_users.progress', '>=', 100);
                });
            } elseif ($finishStatus === 'in_progress') {
                $query->where('playlist_content_users.finish', '0')
                      ->where('playlist_content_users.progress', '<', 100);
            }
        }

        // 5. Calculation Source
        $source = $criteria['calculation_source'] ?? $criteria['source'] ?? null;
        if (!empty($source) && $source !== 'all') {
            if ($source === 'actual') {
                $query->where(function ($q) {
                    $q->where('playlist_content_users.actual_watch_seconds', '>', 0)
                      ->orWhere('playlist_content_users.current_time', '>', 0);
                });
            } elseif ($source === 'historical') {
                $query->where(function ($q) {
                    $q->where('playlist_content_users.actual_watch_seconds', '<=', 0)
                      ->orWhereNull('playlist_content_users.actual_watch_seconds');
                })->where('playlist_content_users.current_time', '<=', 0)
                  ->where(function ($q) {
                      $q->where('playlist_content_users.finish', '1')
                        ->orWhere('playlist_content_users.progress', '>=', 100);
                  });
            } elseif ($source === 'zero') {
                $query->where(function ($q) {
                    $q->where('playlist_content_users.actual_watch_seconds', '<=', 0)
                      ->orWhereNull('playlist_content_users.actual_watch_seconds');
                })->where('playlist_content_users.current_time', '<=', 0)
                  ->where('playlist_content_users.finish', '0')
                  ->where('playlist_content_users.progress', '<', 100);
            }
        }

        // 6. Actual Learning Hour Range
        $minHour = $criteria['min_hour'] ?? $criteria['min_hours'] ?? null;
        if ($minHour !== null && $minHour !== '' && is_numeric($minHour)) {
            $query->where('playlist_content_users.learning_hour', '>=', (float) $minHour);
        }

        $maxHour = $criteria['max_hour'] ?? $criteria['max_hours'] ?? null;
        if ($maxHour !== null && $maxHour !== '' && is_numeric($maxHour)) {
            $query->where('playlist_content_users.learning_hour', '<=', (float) $maxHour);
        }

        // 7. Content Type (Video, Document, YouTube)
        $contentType = $criteria['content_type'] ?? null;
        if (!empty($contentType) && $contentType !== 'all' && in_array($contentType, ['Video', 'Document', 'YouTube'], true)) {
            $query->whereExists(function ($q) use ($contentType) {
                $q->select(DB::raw(1))
                  ->from('content')
                  ->whereColumn('content.id', 'playlist_content_users.content_id')
                  ->where('content.type', $contentType);
            });
        }

        // 8. Classification Slug
        $classificationSlug = $criteria['classification_slug'] ?? $criteria['classification'] ?? null;
        if (!empty($classificationSlug) && $classificationSlug !== 'all') {
            $query->whereExists(function ($q) use ($classificationSlug) {
                $q->select(DB::raw(1))
                  ->from('content')
                  ->join('classification', 'classification.id', '=', 'content.classification_id')
                  ->whereColumn('content.id', 'playlist_content_users.content_id')
                  ->where('classification.slug', $classificationSlug);
            });
        }

        // 9. Completion Year
        $year = $criteria['year'] ?? $criteria['completion_year'] ?? null;
        if (!empty($year) && $year !== 'all') {
            $yearInt = (int) $year;
            if ($yearInt > 2000 && $yearInt < 2100) {
                $query->whereYear('playlist_content_users.completed_at', $yearInt);
            }
        }
    }
}
