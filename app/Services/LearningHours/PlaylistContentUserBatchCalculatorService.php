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
     *     missing_content: int,
     *     batch_count: int,
     *     duration_seconds: float,
     *     dry_run: bool,
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
        $missingContent = 0;
        $batchCount = 0;
        $lastId = 0;

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
            $batchMissing = 0;

            // Group record IDs by their target learning_hour value for bulk update
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

                // Check missing content on finished records with current_time = 0
                if ($isFinished && (int) ($record->current_time ?? 0) <= 0 && $contentDurationSeconds === null) {
                    $missingContent++;
                    $batchMissing++;
                }

                // Resolve actual seconds and calculate
                $actualSeconds = $this->calculator->resolveActualSeconds(
                    $record->current_time,
                    $record->finish,
                    $record->progress,
                    $contentDurationSeconds
                );

                $calculatedHour = $this->calculator->calculate($actualSeconds) ?? 0.0;

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

                $key = number_format($calculatedHour, 4, '.', '');
                $updateGroups[$key][] = $record->id;
                $totalUpdated++;
                $batchUpdated++;
            }

            // Perform bulk update for this batch (if not dry run)
            if (!$isDryRun && (!empty($updateGroups) || !empty($completedAtIds))) {
                DB::transaction(function () use ($updateGroups, $completedAtIds) {
                    foreach ($updateGroups as $hourValue => $ids) {
                        DB::table('playlist_content_users')
                            ->whereIn('id', $ids)
                            ->update(['learning_hour' => (float) $hourValue]);
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
            }

            if ($onProgress !== null) {
                $onProgress($batchCount, count($chunk), $totalScanned, [
                    'updated' => $batchUpdated,
                    'skipped' => $batchSkipped,
                    'missing_content' => $batchMissing,
                ]);
            }

            // Free batch chunk from memory
            unset($chunk, $updateGroups, $completedAtIds);
        }

        $duration = round(microtime(true) - $startTime, 3);

        return [
            'total_scanned'    => $totalScanned,
            'total_updated'    => $totalUpdated,
            'total_skipped'    => $totalSkipped,
            'missing_content'  => $missingContent,
            'batch_count'      => $batchCount,
            'duration_seconds' => $duration,
            'dry_run'          => $isDryRun,
        ];
    }
}
