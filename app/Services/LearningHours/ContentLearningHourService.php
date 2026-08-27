<?php

namespace App\Services\LearningHours;

use App\Contracts\LearningHourSyncInterface;
use App\DataTransferObjects\LearningHourSyncResult;
use App\Events\ContentLearningHourSynchronized;
use App\Models\Content;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class ContentLearningHourService implements LearningHourSyncInterface
{
    public function __construct(
        protected LearningHourConverterService $converter,
        protected ?DurationParserService $parser = null
    ) {
        $this->parser ??= new DurationParserService();
    }

    /**
     * Synchronize learning_hours for all content using chunking and dirty checking.
     *
     * @return array{updated: int, skipped: int, total: int}
     */
    public function syncAll(): array
    {
        $updated = 0;
        $skipped = 0;
        $total = 0;
        $chunkSize = function_exists('config') ? (int) config('learning_hours.chunk_size', 200) : 200;

        Content::query()->chunk($chunkSize, function ($contents) use (&$updated, &$skipped, &$total) {
            foreach ($contents as $content) {
                $total++;
                if ($this->syncContent($content)) {
                    $updated++;
                } else {
                    $skipped++;
                }
            }
        });

        return [
            'updated' => $updated,
            'skipped' => $skipped,
            'total'   => $total,
        ];
    }

    /**
     * Alias for batch synchronization.
     */
    public function syncBatch(): array
    {
        return $this->syncAll();
    }

    /**
     * Synchronize a single content record.
     * Updates duration if needed, calculates learning hours, and persists atomically.
     * Returns true if database was updated, false if skipped.
     */
    public function syncContent(Content $content, array $requestData = []): bool
    {
        return DB::transaction(function () use ($content, $requestData) {
            $oldDuration = $content->duration;
            $oldLearningHours = (float) ($content->learning_hours ?? 0);

            $inputDuration = $requestData['duration'] ?? null;
            $inputPageDuration = $requestData['page_duration'] ?? null;
            $inputTotalPages = $requestData['document_total_pages'] ?? $requestData['total_page'] ?? null;

            $newDuration = $oldDuration;

            if (!empty($inputDuration)) {
                $newDuration = $this->parser->normalize($inputDuration);
            }

            $contentType = $requestData['type'] ?? $content->type;

            if ($contentType === 'Document') {
                $totalPages = (int) ($inputTotalPages ?? $content->document_total_pages ?? $content->total_page ?? 0);
                if ($totalPages > 0) {
                    $pageDuration = isset($inputPageDuration) ? (int) $inputPageDuration : (int) ($content->page_duration ?? 30);
                    if ($pageDuration > 0) {
                        $newDuration = $this->parser->fromSeconds($totalPages * $pageDuration);
                    } elseif ($pageDuration === 0) {
                        $newDuration = '00:00:00';
                    }
                }
            } elseif (empty($inputDuration)) {
                $newDuration = $this->parser->normalize($oldDuration);
            }

            $newLearningHours = $this->converter->convert($newDuration);

            $durationChanged = $oldDuration !== $newDuration;
            $hoursChanged = abs($oldLearningHours - $newLearningHours) >= 0.0001;

            $wasUpdated = false;

            if ($durationChanged || $hoursChanged) {
                $content->forceFill([
                    'duration'       => $newDuration,
                    'learning_hours' => $newLearningHours,
                ])->saveQuietly();

                $wasUpdated = true;

                $this->logAudit('Content synchronized', [
                    'content_id'         => $content->id,
                    'old_duration'       => $oldDuration,
                    'new_duration'       => $newDuration,
                    'old_learning_hours' => $oldLearningHours,
                    'new_learning_hours' => $newLearningHours,
                    'user_id'            => Auth::id(),
                    'updated_at'         => now()->toDateTimeString(),
                ]);
            }

            ContentLearningHourSynchronized::dispatch(
                $content,
                $oldDuration,
                $newDuration,
                $oldLearningHours,
                $newLearningHours,
                $wasUpdated
            );

            return $wasUpdated;
        });
    }

    /**
     * Implementation of LearningHourSyncInterface::syncSingle.
     */
    public function syncSingle(mixed $model, array $requestData = []): LearningHourSyncResult
    {
        if (!$model instanceof Content) {
            $model = Content::findOrFail((int) $model);
        }

        return $this->syncSingleContent($model, $requestData);
    }

    /**
     * Synchronize a single content record and return detailed DTO result.
     */
    public function syncSingleContent(Content $content, array $requestData = []): LearningHourSyncResult
    {
        $wasUpdated = $this->syncContent($content, $requestData);
        $freshContent = $content->fresh();
        $finalHours = (float) ($freshContent?->learning_hours ?? $this->converter->convert($content->duration));

        return new LearningHourSyncResult(
            success: true,
            updated: $wasUpdated,
            learningHours: $finalHours,
            message: $wasUpdated
                ? 'Learning Hours synchronized successfully.'
                : 'Learning Hours already up to date.',
            meta: [
                'duration' => $freshContent?->duration ?? $content->duration,
            ]
        );
    }

    /**
     * Helper to log audit details using configured channel or default logger.
     */
    protected function logAudit(string $message, array $context): void
    {
        try {
            $channelName = function_exists('config') ? config('learning_hours.log_channel') : null;
            if ($channelName && function_exists('config') && config("logging.channels.{$channelName}")) {
                Log::channel($channelName)->info($message, $context);
                return;
            }
        } catch (\Throwable $e) {
            // Fallback
        }

        Log::info($message, $context);
    }
}
