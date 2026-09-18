<?php

namespace App\Services\Learning;

use App\Models\ContentUser;
use App\Models\PlaylistContentUser;
use App\Models\TrainingPlan;
use App\Support\DurationFormatter;
use Illuminate\Support\Collection;

class LearningRecordLearningHourService
{
    /**
     * Calculate actual learning hours for a single ContentUser record.
     *
     * Rules:
     * 1. If content_users.current_time > 0: use current_time
     * 2. If current_time = 0 and is finished: use full duration_seconds
     * 3. If current_time = 0 and progress > 0: use (progress / 100) * duration_seconds
     * 4. Fallback: if content_users.learning_hours > 0, use learning_hours * 3600
     *
     * @param ContentUser $cu
     * @return float Hours rounded to 4 decimal places
     */
    public function calculateContent(ContentUser $cu): float
    {
        $durationSeconds = (int) ($cu->duration_seconds ?? 0);
        if ($durationSeconds <= 0 && $cu->content && !empty($cu->content->duration)) {
            $durationSeconds = DurationFormatter::toSeconds($cu->content->duration) ?? 0;
        }

        $actualWatchSeconds = (int) ($cu->actual_watch_seconds ?? 0);
        $currentTime = (int) ($cu->current_time ?? 0);
        $progress = (int) ($cu->progress ?? 0);
        $isFinished = ((string) $cu->finish === '1' || $progress >= 100);

        if ($actualWatchSeconds > 0) {
            $actualSeconds = $actualWatchSeconds;
        } elseif ($currentTime > 0) {
            $actualSeconds = $currentTime;
        } elseif ($isFinished && $durationSeconds > 0) {
            $actualSeconds = $durationSeconds;
        } elseif ($progress > 0 && $durationSeconds > 0) {
            $actualSeconds = (int) round(($progress / 100) * $durationSeconds);
        } elseif ((float) ($cu->learning_hours ?? 0) > 0) {
            $actualSeconds = (int) round((float) $cu->learning_hours * 3600);
        } else {
            $actualSeconds = 0;
        }

        $calculatedHours = round($actualSeconds / 3600, 4);
        if ($cu->content && $cu->content->max_learning_hours !== null) {
            $calculatedHours = min($calculatedHours, $cu->content->max_learning_hours);
        }

        return $calculatedHours;
    }

    /**
     * Calculate actual consumed learning hours for completed playlists in batch (Zero N+1).
     *
     * Rules:
     * Sum actual consumed seconds across all valid modules of the playlist for user.
     * Per module:
     * 1. actual_watch_seconds > 0: use actual_watch_seconds
     * 2. current_time > 0: use current_time
     * 3. finished = 1 and current_time = 0: use full module duration
     * 4. progress > 0 and current_time = 0: use (progress / 100) * module duration
     * 5. fallback: if pcu.learning_hour > 0, use learning_hour * 3600
     *
     * @param int $userId
     * @param Collection<int, TrainingPlan> $completedPlaylists
     * @return Collection<int, float> Map of [trainingplan_id => actual_learning_hours]
     */
    public function calculatePlaylistsBatch(int $userId, Collection $completedPlaylists): Collection
    {
        $planIds = $completedPlaylists->pluck('id')->filter()->all();
        if (empty($planIds)) {
            return collect();
        }

        // Single query eager-load PCUs for all completed playlists of this user
        $userPcuMap = PlaylistContentUser::where('user_id', $userId)
            ->whereIn('trainingplan_id', $planIds)
            ->get()
            ->groupBy('trainingplan_id');

        // Extract all module content IDs to optionally query historical content_users fallback
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
            $consumedPlaylistSeconds = 0;

            foreach ($validDetails as $d) {
                $cid = $d->content_id;
                $pcu = $planPcus->get($cid);
                $cu = $userCuMap->get($cid);
                $moduleDuration = DurationFormatter::toSeconds($d->content?->duration ?? '') ?? 0;

                if ($pcu) {
                    $pcuDuration = (int) ($pcu->duration_seconds ?? 0);
                    if ($pcuDuration <= 0) {
                        $pcuDuration = $moduleDuration;
                    }
                    $pcuActualWatch = (int) ($pcu->actual_watch_seconds ?? 0);
                    $pcuCurrentTime = (int) ($pcu->current_time ?? 0);
                    $pcuProgress = (int) ($pcu->progress ?? 0);
                    $pcuFinished = ((string) $pcu->finish === '1' || $pcuProgress >= 100);

                    $moduleSec = 0;
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

                    if ($d->content && $d->content->max_actual_watch_seconds !== null) {
                        $moduleSec = min($moduleSec, $d->content->max_actual_watch_seconds);
                    }
                    $consumedPlaylistSeconds += $moduleSec;
                } elseif ($cu) {
                    // Fallback to historical ContentUser record if PCU is absent
                    $cuDuration = (int) ($cu->duration_seconds ?? 0);
                    if ($cuDuration <= 0) {
                        $cuDuration = $moduleDuration;
                    }
                    $cuActualWatch = (int) ($cu->actual_watch_seconds ?? 0);
                    $cuCurrentTime = (int) ($cu->current_time ?? 0);
                    $cuProgress = (int) ($cu->progress ?? 0);
                    $cuFinished = ((string) $cu->finish === '1' || $cuProgress >= 100);

                    $moduleSec = 0;
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

                    if ($d->content && $d->content->max_actual_watch_seconds !== null) {
                        $moduleSec = min($moduleSec, $d->content->max_actual_watch_seconds);
                    }
                    $consumedPlaylistSeconds += $moduleSec;
                }
            }

            $result->put($plan->id, round($consumedPlaylistSeconds / 3600, 4));
        }

        return $result;
    }

    /**
     * Single playlist calculation fallback.
     */
    public function calculatePlaylistSingle(int $userId, TrainingPlan $plan): float
    {
        return $this->calculatePlaylistsBatch($userId, collect([$plan]))->get($plan->id, 0.0);
    }

    /**
     * Format actual learning hours for table display.
     * e.g. 0.08 hour / 0.73 hour / 2.50 hours.
     * When processed by record-row.blade.php:
     * str_replace([' hours', ' hour'], [' h', ' h'], ...) -> 0.08 h, 0.73 h, 2.50 h.
     *
     * @param float $hours
     * @return string
     */
    public function formatHours(float $hours): string
    {
        if ($hours <= 0) {
            return '-';
        }

        $formatted = number_format($hours, 2, '.', '');

        return $formatted . ' ' . ($hours <= 1.0 ? 'hour' : 'hours');
    }

    /**
     * Provide filter options for Learning Record dropdown.
     */
    public function filterOptions(Collection $buckets): Collection
    {
        return $buckets
            ->map(fn ($b) => round((float) $b, 2))
            ->filter(fn (float $b) => $b > 0)
            ->unique()
            ->sort()
            ->values()
            ->map(fn (float $hours) => [
                'value' => (string) $hours,
                'label' => $this->formatHours($hours),
            ]);
    }
}