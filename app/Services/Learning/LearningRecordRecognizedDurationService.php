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
        $durationSeconds = (int) ($cu->duration_seconds ?? 0);
        if ($durationSeconds <= 0 && $cu->content && !empty($cu->content->duration)) {
            $durationSeconds = DurationFormatter::toSeconds($cu->content->duration) ?? 0;
        }

        if ($durationSeconds <= 0) {
            return 0;
        }

        $maxCapSeconds = $cu->content && $cu->content->max_actual_watch_seconds !== null
            ? (int) $cu->content->max_actual_watch_seconds
            : ($durationSeconds * 2);

        $actualWatchSeconds = (int) ($cu->actual_watch_seconds ?? 0);
        $currentTime = (int) ($cu->current_time ?? 0);
        $progress = (int) ($cu->progress ?? 0);
        $finish = $cu->finish ?? '0';

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
            } elseif ((float) ($cu->learning_hours ?? 0) > 0) {
                $actualSeconds = (int) round((float) $cu->learning_hours * 3600);
            }
        }

        return max(0, min($actualSeconds, $maxCapSeconds));
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

        $maxCapSeconds = $pcu->content && $pcu->content->max_actual_watch_seconds !== null
            ? (int) $pcu->content->max_actual_watch_seconds
            : ($durationSeconds * 2);

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
     * Sums recognized seconds across all modules, respecting per-content 2x duration caps.
     *
     * @param int $userId
     * @param Collection<int, TrainingPlan> $completedPlaylists
     * @return Collection<int, int> Map of [trainingplan_id => recognized_duration_seconds]
     */
    public function resolvePlaylistsBatch(int $userId, Collection $completedPlaylists): Collection
    {
        $planIds = $completedPlaylists->pluck('id')->filter()->all();
        if (empty($planIds)) {
            return collect();
        }

        $userPcuMap = PlaylistContentUser::where('user_id', $userId)
            ->whereIn('trainingplan_id', $planIds)
            ->get()
            ->groupBy('trainingplan_id');

        $allContentIds = $completedPlaylists->flatMap(function (TrainingPlan $plan) {
            if (!$plan->relationLoaded('trainingplandetail')) {
                $plan->load(['trainingplandetail.content']);
            }
            return $plan->trainingplandetail->pluck('content_id');
        })->unique()->filter()->values()->all();

        $userCuMap = !empty($allContentIds)
            ? ContentUser::where('user_id', $userId)
                ->whereIn('content_id', $allContentIds)
                ->get()
                ->keyBy('content_id')
            : collect();

        $result = collect();

        foreach ($completedPlaylists as $plan) {
            if (!$plan->relationLoaded('trainingplandetail')) {
                $plan->load(['trainingplandetail.content']);
            }

            $validDetails = $plan->trainingplandetail->filter(
                fn ($d) => !empty($d->content) && $d->content->exists
            );

            $planPcus = $userPcuMap->get($plan->id, collect())->keyBy('content_id');
            $totalPlanSeconds = 0;

            foreach ($validDetails as $d) {
                $cid = $d->content_id;
                $pcu = $planPcus->get($cid);
                $cu = $userCuMap->get($cid);
                $content = $d->content;
                $moduleDuration = DurationFormatter::toSeconds($content?->duration ?? '') ?? 0;

                if ($moduleDuration <= 0) {
                    continue;
                }

                $maxCap = $content->max_actual_watch_seconds !== null
                    ? (int) $content->max_actual_watch_seconds
                    : ($moduleDuration * 2);

                $moduleSec = 0;

                if ($pcu) {
                    $pcuDuration = (int) ($pcu->duration_seconds ?? 0);
                    if ($pcuDuration <= 0) {
                        $pcuDuration = $moduleDuration;
                    }
                    $pcuActualWatch = (int) ($pcu->actual_watch_seconds ?? 0);
                    $pcuCurrentTime = (int) ($pcu->current_time ?? 0);
                    $pcuProgress = (int) ($pcu->progress ?? 0);
                    $pcuFinished = ((string) $pcu->finish === '1' || $pcuProgress >= 100);

                    if ($pcuActualWatch > 0) {
                        $moduleSec = $pcuActualWatch;
                    } elseif ($pcuCurrentTime > 0) {
                        $moduleSec = $pcuCurrentTime;
                    } elseif ($pcuFinished && $pcuDuration > 0) {
                        $moduleSec = $pcuDuration;
                    } elseif ($pcuProgress > 0 && $pcuDuration > 0) {
                        $moduleSec = (int) round(($pcuProgress / 100) * $pcuDuration);
                    } elseif ((float) ($pcu->learning_hour ?? 0) > 0) {
                        $moduleSec = (int) round((float) $pcu->learning_hour * 3600);
                    }
                } elseif ($cu) {
                    $cuDuration = (int) ($cu->duration_seconds ?? 0);
                    if ($cuDuration <= 0) {
                        $cuDuration = $moduleDuration;
                    }
                    $cuActualWatch = (int) ($cu->actual_watch_seconds ?? 0);
                    $cuCurrentTime = (int) ($cu->current_time ?? 0);
                    $cuProgress = (int) ($cu->progress ?? 0);
                    $cuFinished = ((string) $cu->finish === '1' || $cuProgress >= 100);

                    if ($cuActualWatch > 0) {
                        $moduleSec = $cuActualWatch;
                    } elseif ($cuCurrentTime > 0) {
                        $moduleSec = $cuCurrentTime;
                    } elseif ($cuFinished && $cuDuration > 0) {
                        $moduleSec = $cuDuration;
                    } elseif ($cuProgress > 0 && $cuDuration > 0) {
                        $moduleSec = (int) round(($cuProgress / 100) * $cuDuration);
                    } elseif ((float) ($cu->learning_hours ?? 0) > 0) {
                        $moduleSec = (int) round((float) $cu->learning_hours * 3600);
                    }
                } else {
                    // Fallback: playlist completed in Learning Record
                    $moduleSec = $moduleDuration;
                }

                $moduleSec = max(0, min($moduleSec, $maxCap));
                $totalPlanSeconds += $moduleSec;
            }

            $result->put($plan->id, $totalPlanSeconds);
        }

        return $result;
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
