<?php

namespace App\Services\Learning;

use App\Models\Content;
use App\Models\ContentUser;
use App\Models\PlaylistContentUser;
use App\Models\TrainingPlan;
use App\Services\LearningHours\ActualLearningHourCalculator;
use App\Support\DurationFormatter;
use Illuminate\Support\Collection;

class LearningRecordRecognizedDurationService
{
    public function __construct(
        protected ?ActualLearningHourCalculator $calculator = null
    ) {
        $this->calculator ??= new ActualLearningHourCalculator();
    }

    /**
     * Resolve recognized duration in integer seconds for a single ContentUser record.
     * Capped at maximum 2x original content duration.
     *
     * @param ContentUser $cu
     * @return int Recognized duration in seconds
     */
    public function resolveForContentUser(ContentUser $cu): int
    {
        return app(LearningRecordLearningHourService::class)
            ->calculateContentRecognizedSeconds($cu);
    }

    /**
     * Resolve recognized duration in integer seconds for a single PlaylistContentUser record.
     * Capped at maximum 2x original module content duration.
     *
     * @param PlaylistContentUser $pcu
     * @return int Recognized duration in seconds
     */
    public function resolveForPlaylistContentUser(PlaylistContentUser $pcu): int
    {
        $durationSeconds = (int) ($pcu->duration_seconds ?? 0);
        if ($durationSeconds <= 0 && $pcu->content && !empty($pcu->content->duration)) {
            $durationSeconds = DurationFormatter::toSeconds($pcu->content->duration) ?? 0;
        }

        if ($durationSeconds <= 0) {
            return 0;
        }

        $maxCapSeconds = $durationSeconds * 2;
        if ($pcu->content && $pcu->content->max_actual_watch_seconds !== null) {
            $maxCapSeconds = min($maxCapSeconds, (int) $pcu->content->max_actual_watch_seconds);
        }

        $actualWatchSeconds = (int) ($pcu->actual_watch_seconds ?? 0);
        $currentTime = (int) ($pcu->current_time ?? 0);
        $progress = (int) ($pcu->progress ?? 0);
        $finish = $pcu->finish ?? '0';

        $actualSeconds = $this->calculator->resolveActualSeconds(
            $currentTime,
            $finish,
            $progress,
            $durationSeconds,
            $actualWatchSeconds
        );

        if ($actualSeconds <= 0) {
            if ($progress > 0) {
                $actualSeconds = (int) round(($progress / 100) * $durationSeconds);
            } elseif ((float) ($pcu->learning_hour ?? 0) > 0) {
                $actualSeconds = (int) round((float) $pcu->learning_hour * 3600);
            }
        }

        return max(0, min($actualSeconds, $maxCapSeconds));
    }

    /**
     * Resolve recognized duration in integer seconds from a generic database row or array.
     * Pure, stateless, zero database queries. Suitable for DataTables and bulk export streaming.
     *
     * @param object|array $row
     * @return int Recognized duration in seconds
     */
    public function resolveFromRow(object|array $row): int
    {
        $rowObj = is_array($row) ? (object) $row : $row;

        // Offline training records do not have recognized duration
        if (($rowObj->source ?? '') === 'offline') {
            return 0;
        }

        $durationSeconds = 0;
        if (isset($rowObj->duration_seconds) && (int) $rowObj->duration_seconds > 0) {
            $durationSeconds = (int) $rowObj->duration_seconds;
        } elseif (!empty($rowObj->content_duration)) {
            $durationSeconds = DurationFormatter::toSeconds($rowObj->content_duration) ?? 0;
        } elseif (!empty($rowObj->duration)) {
            $durationSeconds = DurationFormatter::toSeconds($rowObj->duration) ?? 0;
        }

        if ($durationSeconds <= 0) {
            return 0;
        }

        $maxCapSeconds = $durationSeconds * 2;

        $actualWatchSeconds = (int) ($rowObj->actual_watch_seconds ?? 0);
        $currentTime = (int) ($rowObj->current_time ?? 0);
        $progress = (int) ($rowObj->progress ?? 0);
        $finish = $rowObj->finish ?? '0';
        $learningHours = (float) ($rowObj->learning_hours ?? ($rowObj->learning_hour ?? 0));

        $actualSeconds = $this->calculator->resolveActualSeconds(
            $currentTime,
            $finish,
            $progress,
            $durationSeconds,
            $actualWatchSeconds
        );

        if ($actualSeconds <= 0) {
            if ($progress > 0) {
                $actualSeconds = (int) round(($progress / 100) * $durationSeconds);
            } elseif ($learningHours > 0) {
                $actualSeconds = (int) round($learningHours * 3600);
            }
        }

        return max(0, min($actualSeconds, $maxCapSeconds));
    }

    /**
     * Calculate recognized duration for completed playlists in batch (Zero N+1).
     * Delegates directly to LearningRecordLearningHourService to ensure 100% synchronized capping & calculation.
     *
     * @param int $userId
     * @param Collection<int, TrainingPlan> $completedPlaylists
     * @return Collection<int, int> Map of [trainingplan_id => recognized_duration_seconds]
     */
    public function resolvePlaylistsBatch(int $userId, Collection $completedPlaylists): Collection
    {
        return app(LearningRecordLearningHourService::class)
            ->calculatePlaylistsRecognizedSecondsBatch($userId, $completedPlaylists);
    }

    /**
     * Resolve global recognized duration in integer seconds for a user on a given content.
     * Capped at maximum 2x original content duration across direct and all playlists.
     *
     * @param int $userId
     * @param int|Content $content
     * @return int
     */
    public function resolveGlobalContentRecognizedSeconds(int $userId, int|Content $content): int
    {
        return app(LearningRecordLearningHourService::class)
            ->resolveGlobalContentRecognizedSeconds($userId, $content);
    }

    /**
     * Resolve global recognized duration formatted as HH:MM:SS for a user on a given content.
     *
     * @param int $userId
     * @param int|Content $content
     * @return string
     */
    public function resolveGlobalContentRecognizedDuration(int $userId, int|Content $content): string
    {
        return $this->formatSeconds($this->resolveGlobalContentRecognizedSeconds($userId, $content));
    }

    /**
     * Format integer seconds to standard HH:MM:SS format without 24-hour wrap.
     * e.g., 0 -> "00:00:00", 98100 -> "27:15:00".
     *
     * @param int|float|null $seconds
     * @return string
     */
    public function formatSeconds(mixed $seconds): string
    {
        $sec = max(0, (int) round((float) ($seconds ?? 0)));

        return DurationFormatter::fromSeconds($sec);
    }
}
