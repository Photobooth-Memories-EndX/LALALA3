<?php

namespace App\Services\LearningHours;

use App\Contracts\LearningHourSyncInterface;
use App\DataTransferObjects\LearningHourSyncResult;
use App\Events\PlaylistLearningHourSynchronized;
use App\Models\Content;
use App\Models\TrainingPlan;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class PlaylistLearningHourService implements LearningHourSyncInterface
{
    public function __construct(
        protected LearningHourConverterService $converter,
        protected ?DurationParserService $parser = null
    ) {
        $this->parser ??= new DurationParserService();
    }

    /**
     * Synchronize learning_hours for all playlists (trainingplan) using chunking and dirty checking.
     * Eager loads trainingplandetail.content to completely avoid N+1 queries.
     *
     * @param bool $syncDuration If false, total_duration_seconds remains unchanged.
     * @return array{updated: int, skipped: int, total: int}
     */
    public function syncAll(bool $syncDuration = false): array
    {
        $updated = 0;
        $skipped = 0;
        $total = 0;
        $chunkSize = function_exists('config') ? (int) config('learning_hours.chunk_size', 200) : 200;

        TrainingPlan::query()
            ->with(['trainingplandetail.content:id,duration'])
            ->chunk($chunkSize, function ($playlists) use (&$updated, &$skipped, &$total, $syncDuration) {
                foreach ($playlists as $plan) {
                    $total++;
                    if ($this->syncPlaylist($plan, $syncDuration)) {
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
    public function syncBatch(bool $syncDuration = false): array
    {
        return $this->syncAll($syncDuration);
    }

    /**
     * Synchronize a single training plan / playlist record.
     * Always calculates total duration strictly from trainingplandetail -> content.duration.
     * When $syncDuration is false, strictly updates learning_hours and leaves total_duration_seconds untouched.
     * Returns true if database was updated, false if skipped (dirty checking).
     */
    public function syncPlaylist(TrainingPlan $plan, bool $syncDuration = false): bool
    {
        return DB::transaction(function () use ($plan, $syncDuration) {
            if (!$plan->relationLoaded('trainingplandetail')) {
                $plan->load('trainingplandetail.content:id,duration');
            }

            $totalSeconds = 0;
            foreach ($plan->trainingplandetail as $detail) {
                if ($detail->content && $detail->content->duration) {
                    $totalSeconds += $this->parser->toSeconds($detail->content->duration);
                }
            }

            $newLearningHours = $this->converter->convertFromSeconds($totalSeconds);
            $currentLearningHours = (float) ($plan->learning_hours ?? 0);
            $currentTotalSeconds = (int) ($plan->total_duration_seconds ?? 0);

            $hoursChanged = abs($currentLearningHours - $newLearningHours) >= 0.0001;
            $secondsChanged = $syncDuration && ($currentTotalSeconds !== $totalSeconds);
            $wasUpdated = false;

            if ($hoursChanged || $secondsChanged) {
                $attributes = [
                    'learning_hours' => $newLearningHours,
                ];

                if ($syncDuration) {
                    $attributes['total_duration_seconds'] = $totalSeconds;
                }

                $plan->forceFill($attributes)->saveQuietly();

                $wasUpdated = true;

                $this->logAudit('Playlist synchronized', [
                    'playlist_id'          => $plan->id,
                    'old_duration_seconds' => $currentTotalSeconds,
                    'new_duration_seconds' => $syncDuration ? $totalSeconds : $currentTotalSeconds,
                    'old_learning_hours'   => $currentLearningHours,
                    'new_learning_hours'   => $newLearningHours,
                    'user_id'              => Auth::id(),
                    'updated_at'           => now()->toDateTimeString(),
                ]);
            }

            PlaylistLearningHourSynchronized::dispatch(
                $plan,
                $currentTotalSeconds,
                $syncDuration ? $totalSeconds : $currentTotalSeconds,
                $currentLearningHours,
                $newLearningHours,
                $wasUpdated
            );

            return $wasUpdated;
        });
    }

    /**
     * Implementation of LearningHourSyncInterface::syncSingle.
     */
    public function syncSingle(mixed $model): LearningHourSyncResult
    {
        if (!$model instanceof TrainingPlan) {
            $model = TrainingPlan::findOrFail((int) $model);
        }

        return $this->syncSinglePlaylist($model);
    }

    /**
     * Synchronize a single playlist record and return detailed DTO result.
     */
    public function syncSinglePlaylist(TrainingPlan $plan, bool $syncDuration = true): LearningHourSyncResult
    {
        $wasUpdated = $this->syncPlaylist($plan, $syncDuration);
        $freshPlan = $plan->fresh();

        if (!$freshPlan->relationLoaded('trainingplandetail')) {
            $freshPlan->load('trainingplandetail.content:id,duration');
        }

        $totalSeconds = 0;
        foreach ($freshPlan->trainingplandetail as $detail) {
            if ($detail->content && $detail->content->duration) {
                $totalSeconds += $this->parser->toSeconds($detail->content->duration);
            }
        }

        $finalHours = (float) ($freshPlan->learning_hours ?? $this->converter->convertFromSeconds($totalSeconds));

        return new LearningHourSyncResult(
            success: true,
            updated: $wasUpdated,
            learningHours: $finalHours,
            message: $wasUpdated
                ? 'Learning Hours synchronized successfully.'
                : 'Learning Hours already up to date.',
            meta: [
                'total_duration_seconds' => $totalSeconds,
                'duration'               => $this->parser->fromSeconds($totalSeconds),
            ]
        );
    }

    /**
     * Calculate learning hours for an arbitrary array of content IDs (e.g. unsaved playlist form).
     */
    public function calculateLearningHoursFromContentIds(array $contentIds): float
    {
        $ids = array_filter(array_map('intval', $contentIds));
        if (empty($ids)) {
            return 0.0;
        }

        $contents = Content::whereIn('id', $ids)->get(['id', 'duration']);
        $totalSeconds = 0;
        foreach ($contents as $content) {
            if ($content->duration) {
                $totalSeconds += $this->parser->toSeconds($content->duration);
            }
        }

        return $this->converter->convertFromSeconds($totalSeconds);
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