<?php

namespace App\Services\LearningHours;

use App\Models\ContentUser;
use App\Models\LearningHourCalculationBatch;
use App\Models\PlaylistContentUser;
use App\Services\Learning\LearningHourService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class ContentUserLearningHourManagementService
{
    public const BUCKET_SECONDS = 1800;
    public const BUCKET_HOURS = 0.5;

    public function __construct(
        protected LearningHourConverterService $converter,
        protected DurationParserService $parser,
        protected ?LearningHourService $learningHourService = null
    ) {
        $this->learningHourService ??= new LearningHourService();
    }

    /**
     * Calculate learning hours for a single ContentUser or PlaylistContentUser record.
     *
     * @param ContentUser|PlaylistContentUser|int $record
     * @param bool $force Recalculate even if already set
     * @param string $context 'auto', 'direct', or 'playlist'
     * @return array{success: bool, updated: bool, learning_hours?: ?float, learning_hour?: ?float, message: string}
     */
    public function calculateSingle(ContentUser|PlaylistContentUser|int $record, bool $force = false, string $context = 'auto'): array
    {
        if ($record instanceof PlaylistContentUser || ($context === 'playlist' && is_int($record))) {
            return $this->calculatePlaylistSingle($record, $force);
        }

        $contentUser = $record;
        if (is_int($contentUser)) {
            $contentUser = ContentUser::with('content')->find($contentUser);
            if (!$contentUser && $context === 'auto') {
                $pcu = PlaylistContentUser::with('content')->find($record);
                if ($pcu) {
                    return $this->calculatePlaylistSingle($pcu, $force);
                }
            }
        } elseif (!$contentUser->relationLoaded('content')) {
            $contentUser->load('content');
        }

        if (!$contentUser) {
            return [
                'success' => false,
                'updated' => false,
                'learning_hours' => null,
                'message' => 'ContentUser record not found.',
            ];
        }

        if (!$contentUser->content) {
            return [
                'success' => false,
                'updated' => false,
                'learning_hours' => null,
                'message' => 'Related Content record not found or soft-deleted.',
            ];
        }

        $content = $contentUser->content;
        $masterHours = $content->learning_hours !== null
            ? (float) $content->learning_hours
            : $this->calculateFromDuration($content->duration);

        if ($masterHours === null) {
            return [
                'success' => false,
                'updated' => false,
                'learning_hours' => null,
                'message' => 'Cannot determine master learning hours from content.',
            ];
        }

        $progress = (int) ($contentUser->progress ?? 0);
        $isFinished = $contentUser->isFinished();

        if ($isFinished) {
            $userHours = (float) $masterHours;
        } elseif ($progress > 0) {
            $userHours = round((float) $masterHours * ($progress / 100), 1);
        } else {
            $userHours = 0.0;
        }

        if (!$force && $contentUser->learning_hours !== null && abs((float) $contentUser->learning_hours - $userHours) < 0.0001) {
            return [
                'success' => true,
                'updated' => false,
                'learning_hours' => (float) $contentUser->learning_hours,
                'message' => 'Learning hours already up to date.',
            ];
        }

        $updateData = [
            'learning_hours' => $userHours,
        ];

        if ($isFinished && $contentUser->completed_at === null) {
            $updateData['completed_at'] = now();
        }

        $contentUser->forceFill($updateData)->saveQuietly();

        return [
            'success' => true,
            'updated' => true,
            'learning_hours' => $userHours,
            'message' => 'Learning hours calculated successfully.',
        ];
    }

    /**
     * Calculate learning hours for a single PlaylistContentUser record.
     * Writes strictly to playlist_content_users.learning_hour and playlist_content_users.completed_at.
     *
     * @param PlaylistContentUser|int $record
     * @param bool $force
     * @return array{success: bool, updated: bool, learning_hour: ?float, message: string}
     */
    public function calculatePlaylistSingle(PlaylistContentUser|int $record, bool $force = false): array
    {
        if (is_int($record)) {
            $record = PlaylistContentUser::with('content')->find($record);
        } elseif (!$record->relationLoaded('content')) {
            $record->load('content');
        }

        if (!$record) {
            return [
                'success' => false,
                'updated' => false,
                'learning_hour' => null,
                'message' => 'PlaylistContentUser record not found.',
            ];
        }

        if (!$record->content) {
            return [
                'success' => false,
                'updated' => false,
                'learning_hour' => null,
                'message' => 'Related Content record not found or soft-deleted.',
            ];
        }

        $content = $record->content;
        $masterHours = $content->learning_hours !== null
            ? (float) $content->learning_hours
            : $this->calculateFromDuration($content->duration);

        if ($masterHours === null) {
            return [
                'success' => false,
                'updated' => false,
                'learning_hour' => null,
                'message' => 'Cannot determine master learning hours from content.',
            ];
        }

        $calculator = app(ActualLearningHourCalculator::class);
        $userHours = $calculator->calculateFromTracking(
            $record->current_time,
            $record->finish,
            $record->progress,
            $content->duration ?? $record->duration_seconds
        );

        if (!$force && $record->learning_hour !== null && abs((float) $record->learning_hour - $userHours) < 0.0001) {
            return [
                'success' => true,
                'updated' => false,
                'learning_hour' => (float) $record->learning_hour,
                'message' => 'Learning hours already up to date.',
            ];
        }

        $updateData = [
            'learning_hour' => $userHours,
        ];

        $isFinished = ((string) $record->finish === '1' || (int) $record->progress >= 100);

        if ($isFinished && $record->completed_at === null) {
            $updateData['completed_at'] = now();
        }

        $record->forceFill($updateData)->saveQuietly();

        return [
            'success' => true,
            'updated' => true,
            'learning_hour' => $userHours,
            'message' => 'Playlist learning hour calculated successfully.',
        ];
    }

    /**
     * Calculate learning hours for selected IDs (supports Direct Content and Playlist Content).
     *
     * @param array<int> $ids
     * @param bool $force
     * @param string $context 'auto', 'direct', or 'playlist'
     * @return array{total: int, updated: int, skipped: int, failed: int}
     */
    public function calculateSelected(array $ids, bool $force = false, string $context = 'auto'): array
    {
        $updated = 0;
        $skipped = 0;
        $failed = 0;
        $total = count($ids);

        if ($total === 0) {
            return ['total' => 0, 'updated' => 0, 'skipped' => 0, 'failed' => 0];
        }

        if ($context === 'playlist') {
            $records = PlaylistContentUser::with('content')
                ->whereIn('id', $ids)
                ->get();

            foreach ($records as $item) {
                $result = $this->calculatePlaylistSingle($item, $force);
                if (!$result['success']) {
                    $failed++;
                } elseif ($result['updated']) {
                    $updated++;
                } else {
                    $skipped++;
                }
            }
        } elseif ($context === 'direct') {
            $records = ContentUser::with('content')
                ->whereIn('id', $ids)
                ->get();

            foreach ($records as $item) {
                $result = $this->calculateSingle($item, $force);
                if (!$result['success']) {
                    $failed++;
                } elseif ($result['updated']) {
                    $updated++;
                } else {
                    $skipped++;
                }
            }
        } else {
            // Auto detection
            $directRecords = ContentUser::with('content')->whereIn('id', $ids)->get();
            $directIds = $directRecords->pluck('id')->all();
            $remainingIds = array_diff($ids, $directIds);

            foreach ($directRecords as $item) {
                $result = $this->calculateSingle($item, $force);
                if (!$result['success']) {
                    $failed++;
                } elseif ($result['updated']) {
                    $updated++;
                } else {
                    $skipped++;
                }
            }

            if (!empty($remainingIds)) {
                $playlistRecords = PlaylistContentUser::with('content')->whereIn('id', $remainingIds)->get();
                foreach ($playlistRecords as $item) {
                    $result = $this->calculatePlaylistSingle($item, $force);
                    if (!$result['success']) {
                        $failed++;
                    } elseif ($result['updated']) {
                        $updated++;
                    } else {
                        $skipped++;
                    }
                }
            }
        }

        return [
            'total' => $total,
            'updated' => $updated,
            'skipped' => $skipped,
            'failed' => $failed,
        ];
    }

    /**
     * Recalculate learning hours for selected IDs (force = true).
     *
     * @param array<int> $ids
     * @param string $context
     * @return array{total: int, updated: int, skipped: int, failed: int}
     */
    public function recalculateSelected(array $ids, string $context = 'auto'): array
    {
        return $this->calculateSelected($ids, force: true, context: $context);
    }

    /**
     * Calculate canonical learning hours from duration string (HH:MM:SS or MM:SS) or seconds.
     * Formula: ceil(total_seconds / 1800) * 0.5
     */
    public function calculateFromDuration(string|int|null $duration): float
    {
        return $this->converter->convert($duration);
    }

    /**
     * Start a new tracking batch for asynchronous / chunked calculation.
     */
    public function startBatch(string $type = 'all_remaining', ?int $userId = null): LearningHourCalculationBatch
    {
        $query = ContentUser::query();

        if ($type === 'all_remaining') {
            $query->whereNull('learning_hours')->whereHas('content');
        }

        $totalRecords = $query->count();

        return LearningHourCalculationBatch::create([
            'type' => $type,
            'status' => $totalRecords > 0 ? 'processing' : 'completed',
            'total_records' => $totalRecords,
            'processed_records' => 0,
            'failed_records' => 0,
            'last_processed_id' => null,
            'created_by' => $userId,
            'started_at' => now(),
            'finished_at' => $totalRecords === 0 ? now() : null,
        ]);
    }

    /**
     * Process one chunk for a given calculation batch.
     */
    public function processBatchStep(int $batchId, int $chunkSize = 2500): array
    {
        $batch = LearningHourCalculationBatch::find($batchId);

        if (!$batch) {
            return [
                'success' => false,
                'message' => 'Batch not found.',
                'is_completed' => true,
            ];
        }

        if ($batch->status === 'completed' || $batch->status === 'cancelled') {
            return [
                'success' => true,
                'batch' => $batch,
                'is_completed' => true,
                'percentage' => 100.0,
                'processed' => $batch->processed_records,
                'total' => $batch->total_records,
            ];
        }

        $query = ContentUser::query()
            ->with('content')
            ->orderBy('id');

        if ($batch->last_processed_id !== null) {
            $query->where('id', '>', $batch->last_processed_id);
        }

        if ($batch->type === 'all_remaining') {
            $query->whereNull('learning_hours')->whereHas('content');
        }

        $chunk = $query->limit($chunkSize)->get();

        if ($chunk->isEmpty()) {
            $batch->update([
                'status' => 'completed',
                'finished_at' => now(),
            ]);

            return [
                'success' => true,
                'batch' => $batch->fresh(),
                'is_completed' => true,
                'percentage' => 100.0,
                'processed' => $batch->processed_records,
                'total' => $batch->total_records,
            ];
        }

        $chunkProcessed = 0;
        $chunkFailed = 0;
        $lastId = $batch->last_processed_id;
        $groups = [];
        $completedIds = [];

        foreach ($chunk as $item) {
            $lastId = $item->id;
            $chunkProcessed++;

            if (!$item->content || empty($item->content->duration)) {
                $chunkFailed++;
                continue;
            }

            $masterHours = $item->content->learning_hours !== null
                ? (float) $item->content->learning_hours
                : $this->calculateFromDuration($item->content->duration);

            if ($masterHours === null) {
                $chunkFailed++;
                continue;
            }

            $progress = (int) ($item->progress ?? 0);
            $isFinished = $item->isFinished();

            if ($isFinished) {
                $userHours = (float) $masterHours;
                if ($item->completed_at === null) {
                    $completedIds[] = $item->id;
                }
            } elseif ($progress > 0) {
                $userHours = round((float) $masterHours * ($progress / 100), 1);
            } else {
                $userHours = 0.0;
            }

            $groups[(string) $userHours][] = $item->id;
        }

        DB::beginTransaction();
        try {
            foreach ($groups as $hoursVal => $ids) {
                DB::table('content_users')->whereIn('id', $ids)->update(['learning_hours' => (float) $hoursVal]);
            }
            if (!empty($completedIds)) {
                DB::table('content_users')->whereIn('id', $completedIds)->whereNull('completed_at')->update(['completed_at' => now()]);
            }
            DB::commit();
        } catch (\Throwable $e) {
            DB::rollBack();
            Log::error('LearningHourCalculation batch step failed', [
                'batch_id' => $batchId,
                'error' => $e->getMessage(),
            ]);

            $batch->update([
                'status' => 'failed',
                'error_message' => $e->getMessage(),
            ]);

            return [
                'success' => false,
                'message' => 'Batch processing error: ' . $e->getMessage(),
                'is_completed' => true,
            ];
        }

        $newProcessed = $batch->processed_records + $chunkProcessed;
        $newFailed = $batch->failed_records + $chunkFailed;
        $isCompleted = ($newProcessed + $newFailed) >= $batch->total_records;

        $batch->update([
            'processed_records' => $newProcessed,
            'failed_records' => $newFailed,
            'last_processed_id' => $lastId,
            'status' => $isCompleted ? 'completed' : 'processing',
            'finished_at' => $isCompleted ? now() : null,
        ]);

        return [
            'success' => true,
            'batch' => $batch->fresh(),
            'is_completed' => $isCompleted,
            'percentage' => $batch->percentage,
            'processed' => $newProcessed,
            'total' => $batch->total_records,
        ];
    }

    /**
     * Cancel an ongoing batch.
     */
    public function cancelBatch(int $batchId): bool
    {
        $batch = LearningHourCalculationBatch::find($batchId);

        if (!$batch || $batch->status === 'completed' || $batch->status === 'cancelled') {
            return false;
        }

        return (bool) $batch->update([
            'status' => 'cancelled',
            'finished_at' => now(),
        ]);
    }

    /**
     * Get aggregate statistics for the dashboard/summary header.
     */
    public function getStatistics(): array
    {
        $total = ContentUser::count();
        $calculated = ContentUser::whereNotNull('learning_hours')->count();

        // Remaining records needing calculation (content exists with valid duration, but learning_hours is null)
        $remaining = ContentUser::whereNull('learning_hours')
            ->whereHas('content', function ($cq) {
                $cq->whereNotNull('duration')->where('duration', '!=', '');
            })->count();

        // Count failed (records where content is null or content duration is empty)
        $failed = ContentUser::whereNull('learning_hours')
            ->where(function ($q) {
                $q->whereDoesntHave('content')
                    ->orWhereHas('content', function ($cq) {
                        $cq->whereNull('duration')->orWhere('duration', '');
                    });
            })
            ->count();

        $percentage = $total > 0 ? round(($calculated / $total) * 100, 1) : 0.0;

        return [
            'total' => $total,
            'calculated' => $calculated,
            'remaining' => $remaining,
            'failed' => $failed,
            'percentage' => $percentage,
        ];
    }

    /**
     * Build the query for Yajra DataTables Server-Side Processing.
     */
    public function buildDatatableQuery(Request $request): Builder
    {
        $query = ContentUser::query()
            ->with([
                'user:id,name,id_emp,ssohash,role',
                'content' => function ($q) {
                    $q->select('id', 'title', 'type', 'duration', 'slug', 'classification_id', 'learning_hours')
                        ->with('classification:id,classification_name,slug');
                },
            ])
            ->select('content_users.*');

        // Apply filters
        $this->applyDatatableFilters($query, $request);

        return $query;
    }

    /**
     * Apply filter conditions to the DataTable query.
     */
    protected function applyDatatableFilters(Builder $query, Request $request): void
    {
        if ($request->filled('status') && $request->status !== 'all') {
            $query->where('content_users.status', $request->status);
        }

        if ($request->filled('learning_hours_status') && $request->learning_hours_status !== 'all') {
            if ($request->learning_hours_status === 'calculated') {
                $query->whereNotNull('content_users.learning_hours');
            } elseif ($request->learning_hours_status === 'uncalculated') {
                $query->whereNull('content_users.learning_hours');
            }
        }

        if ($request->filled('year') && $request->year !== 'all') {
            $year = (int) $request->year;
            if ($year > 2000 && $year < 2100) {
                $query->where('content_users.completed_at', '>=', "{$year}-01-01 00:00:00")
                      ->where('content_users.completed_at', '<=', "{$year}-12-31 23:59:59");
            }
        }

        if ($request->filled('content_type') && $request->content_type !== 'all') {
            $query->whereHas('content', function ($q) use ($request) {
                $q->where('type', $request->content_type);
            });
        }

        if ($request->filled('classification_slug') && $request->classification_slug !== 'all') {
            $query->whereHas('content.classification', function ($q) use ($request) {
                $q->where('slug', $request->classification_slug);
            });
        }
    }

    /**
     * Get aggregate statistics for PlaylistContentUser records.
     */
    public function getPlaylistStatistics(): array
    {
        $total = PlaylistContentUser::count();
        $calculated = PlaylistContentUser::whereNotNull('learning_hour')->count();

        $remaining = PlaylistContentUser::whereNull('learning_hour')
            ->whereHas('content', function ($cq) {
                $cq->whereNotNull('duration')->where('duration', '!=', '');
            })->count();

        $failed = PlaylistContentUser::whereNull('learning_hour')
            ->where(function ($q) {
                $q->whereDoesntHave('content')
                    ->orWhereHas('content', function ($cq) {
                        $cq->whereNull('duration')->orWhere('duration', '');
                    });
            })
            ->count();

        $percentage = $total > 0 ? round(($calculated / $total) * 100, 1) : 0.0;

        return [
            'total' => $total,
            'calculated' => $calculated,
            'remaining' => $remaining,
            'failed' => $failed,
            'percentage' => $percentage,
        ];
    }

    /**
     * Build the query for PlaylistContentUser DataTables processing.
     */
    public function buildPlaylistDatatableQuery(Request $request): Builder
    {
        $query = PlaylistContentUser::query()
            ->with([
                'user:id,name,id_emp,ssohash,role',
                'trainingPlan:id,title',
                'content' => function ($q) {
                    $q->select('id', 'title', 'type', 'duration', 'slug', 'classification_id', 'learning_hours')
                        ->with('classification:id,classification_name,slug');
                },
            ])
            ->select('playlist_content_users.*');

        $this->applyPlaylistDatatableFilters($query, $request);

        return $query;
    }

    /**
     * Apply filter conditions to the PlaylistContentUser DataTable query.
     */
    protected function applyPlaylistDatatableFilters(Builder $query, Request $request): void
    {
        if ($request->filled('learning_hours_status') && $request->learning_hours_status !== 'all') {
            if ($request->learning_hours_status === 'calculated') {
                $query->whereNotNull('playlist_content_users.learning_hour');
            } elseif ($request->learning_hours_status === 'uncalculated') {
                $query->whereNull('playlist_content_users.learning_hour');
            }
        }

        if ($request->filled('year') && $request->year !== 'all') {
            $year = (int) $request->year;
            if ($year > 2000 && $year < 2100) {
                $query->where('playlist_content_users.completed_at', '>=', "{$year}-01-01 00:00:00")
                      ->where('playlist_content_users.completed_at', '<=', "{$year}-12-31 23:59:59");
            }
        }

        if ($request->filled('content_type') && $request->content_type !== 'all') {
            $query->whereHas('content', function ($q) use ($request) {
                $q->where('type', $request->content_type);
            });
        }

        if ($request->filled('classification_slug') && $request->classification_slug !== 'all') {
            $query->whereHas('content.classification', function ($q) use ($request) {
                $q->where('slug', $request->classification_slug);
            });
        }
    }

    /**
     * Format learning hours float for human-readable display.
     * E.g. 0.5 -> "0.5 hour", 1.0 -> "1 hour", 1.5 -> "1.5 hours".
     */
    public function formatHours(?float $hours): string
    {
        if ($hours === null || $hours <= 0) {
            return '-';
        }

        $label = rtrim(rtrim(number_format($hours, 1, '.', ''), '0'), '.');

        return $label . ' ' . ($hours <= 1.0 ? 'hour' : 'hours');
    }
}
