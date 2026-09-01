<?php

namespace App\Services\LearningHours;

use App\Models\ContentUser;
use App\Models\LearningHourCalculationBatch;
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
     * Calculate learning hours for a single ContentUser record.
     *
     * @param ContentUser|int $contentUser
     * @param bool $force Recalculate even if already set
     * @return array{success: bool, updated: bool, learning_hours: ?float, message: string}
     */
    public function calculateSingle(ContentUser|int $contentUser, bool $force = false): array
    {
        if (is_int($contentUser)) {
            $contentUser = ContentUser::with('content')->find($contentUser);
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
        $duration = $content->duration;
        $calculatedHours = $this->calculateFromDuration($duration);

        if (!$force && $content->learning_hours !== null && abs((float) $content->learning_hours - $calculatedHours) < 0.0001) {
            return [
                'success' => true,
                'updated' => false,
                'learning_hours' => (float) $content->learning_hours,
                'message' => 'Learning hours already up to date.',
            ];
        }

        $content->forceFill([
            'learning_hours' => $calculatedHours,
        ])->saveQuietly();

        return [
            'success' => true,
            'updated' => true,
            'learning_hours' => $calculatedHours,
            'message' => 'Learning hours calculated successfully.',
        ];
    }

    /**
     * Calculate learning hours for selected ContentUser IDs.
     *
     * @param array<int> $ids
     * @param bool $force
     * @return array{total: int, updated: int, skipped: int, failed: int}
     */
    public function calculateSelected(array $ids, bool $force = false): array
    {
        $updated = 0;
        $skipped = 0;
        $failed = 0;
        $total = count($ids);

        if ($total === 0) {
            return ['total' => 0, 'updated' => 0, 'skipped' => 0, 'failed' => 0];
        }

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

        return [
            'total' => $total,
            'updated' => $updated,
            'skipped' => $skipped,
            'failed' => $failed,
        ];
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
            $query->whereHas('content', function ($q) {
                $q->whereNull('learning_hours');
            });
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
            $query->whereHas('content', function ($q) {
                $q->whereNull('learning_hours');
            });
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

        foreach ($chunk as $item) {
            $lastId = $item->id;
            $chunkProcessed++;

            if (!$item->content || empty($item->content->duration)) {
                $chunkFailed++;
                continue;
            }

            $calculatedHours = $this->calculateFromDuration($item->content->duration);
            $key = (string) $calculatedHours;
            $groups[$key][] = $item->content->id;
        }

        DB::beginTransaction();
        try {
            foreach ($groups as $hoursVal => $contentIds) {
                \App\Models\Content::whereIn('id', array_unique($contentIds))->update(['learning_hours' => (float) $hoursVal]);
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
        $isFinished = $chunk->count() < $chunkSize || ($batch->total_records > 0 && $newProcessed >= $batch->total_records);

        $batch->update([
            'processed_records' => $newProcessed,
            'failed_records' => $newFailed,
            'last_processed_id' => $lastId,
            'status' => $isFinished ? 'completed' : 'processing',
            'finished_at' => $isFinished ? now() : null,
        ]);

        return [
            'success' => true,
            'batch' => $batch->fresh(),
            'is_completed' => $isFinished,
            'percentage' => $batch->percentage,
            'processed' => $newProcessed,
            'total' => $batch->total_records,
        ];
    }

    /**
     * Get aggregate statistics for the dashboard/summary header.
     */
    public function getStatistics(): array
    {
        $total = ContentUser::count();
        $calculated = ContentUser::whereHas('content', function ($q) {
            $q->whereNotNull('learning_hours');
        })->count();
        
        // Count failed (records where content is null or content duration is empty)
        $failed = ContentUser::where(function ($q) {
            $q->whereDoesntHave('content')
                ->orWhereHas('content', function ($cq) {
                    $cq->whereNull('duration')->orWhere('duration', '');
                });
        })->count();

        // Remaining records needing calculation (content exists with valid duration, but learning_hours is null)
        $remaining = ContentUser::whereHas('content', function ($q) {
            $q->whereNull('learning_hours')
                ->whereNotNull('duration')
                ->where('duration', '!=', '');
        })->count();

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
                $query->whereHas('content', function ($q) {
                    $q->whereNotNull('learning_hours');
                });
            } elseif ($request->learning_hours_status === 'uncalculated') {
                $query->where(function ($q) {
                    $q->whereDoesntHave('content')
                        ->orWhereHas('content', function ($cq) {
                            $cq->whereNull('learning_hours');
                        });
                });
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
