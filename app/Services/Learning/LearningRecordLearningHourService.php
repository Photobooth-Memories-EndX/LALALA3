<?php

namespace App\Services\Learning;

use App\Models\ContentUser;
use App\Models\PlaylistContentUser;
use App\Models\TrainingPlan;
use App\Support\DurationFormatter;
use Illuminate\Support\Collection;

class LearningRecordLearningHourService
{
    public function __construct(
        private readonly ?CompletedPlaylistService $completedPlaylistService = null,
        private readonly ?UserRecordOfflineService $offlineService = null
    ) {
    }

    private function completedPlaylistsService(): CompletedPlaylistService
    {
        return $this->completedPlaylistService ?? app(CompletedPlaylistService::class);
    }

    private function offlineService(): UserRecordOfflineService
    {
        return $this->offlineService ?? app(UserRecordOfflineService::class);
    }

    /**
     * Canonical total actual completed learning hours for online record.
     *
     * @param int $userId
     * @return float Rounded to 2 decimal places
     */
    public function getOnlineLearningHours(int $userId): float
    {
        return $this->getTotalLearningHours($userId);
    }

    /**
     * Canonical total completed learning hours for offline record.
     *
     * @param int $userId
     * @param string|null $userEmpId
     * @return float Rounded to 2 decimal places
     */
    public function getOfflineLearningHours(int $userId, ?string $userEmpId = null): float
    {
        return $this->offlineService()->getTotalLearningHours($userId, $userEmpId);
    }

    /**
     * Canonical total learning hours by active record mode.
     *
     * Mapping:
     * - 'online' (default / empty / invalid) => Online only
     * - 'offline'                            => Offline only
     * - 'all'                                => Online + Offline
     * - 'export'                             => Online + Offline
     *
     * @param string|null $mode
     * @param int $userId
     * @param string|null $userEmpId
     * @return float Rounded to 2 decimal places
     */
    public function getTotalLearningHoursByMode(?string $mode, int $userId, ?string $userEmpId = null): float
    {
        $normalizedMode = match (strtolower(trim((string) $mode))) {
            'offline' => 'offline',
            'all'     => 'all',
            'export'  => 'export',
            default   => 'online',
        };

        return match ($normalizedMode) {
            'offline' => $this->getOfflineLearningHours($userId, $userEmpId),
            'all', 'export' => round(
                $this->getOnlineLearningHours($userId) + $this->getOfflineLearningHours($userId, $userEmpId),
                2
            ),
            default   => $this->getOnlineLearningHours($userId),
        };
    }

    /**
     * Canonical total actual completed learning hours for a user.
     * Reused by Home, Learning Record, My Learning Dashboard, and Monitoring.
     *
     * @param int $userId
     * @return float Rounded to 2 decimal places
     */
    public function getTotalLearningHours(int $userId): float
    {
        $completedPlaylists = $this->completedPlaylistsService()->forLearningRecord($userId);

        $completedContentUsers = ContentUser::with('content')
            ->join('content', 'content.id', '=', 'content_users.content_id')
            ->where('content_users.user_id', $userId)
            ->where('content_users.finish', '1')
            ->whereNull('content.deleted_at')
            ->whereIn('content.type', ['Video', 'Document'])
            ->select('content_users.*')
            ->get();

        $actualContentHours = (float) $completedContentUsers->sum(
            fn (ContentUser $cu) => $this->calculateContent($cu)
        );

        $actualPlaylistHours = (float) $this->calculatePlaylistsBatch($userId, $completedPlaylists)->sum();

        return round($actualContentHours + $actualPlaylistHours, 2);
    }

    /**
     * Calculate actual learning hours for a single ContentUser record.
     *
     * Synchronized with calculateContentRecognizedSeconds: uses the exact same
     * capped seconds variable as Learning Hours.
     *
     * @param ContentUser $cu
     * @return float Hours rounded to 4 decimal places
     */
    public function calculateContent(ContentUser $cu): float
    {
        $recognizedSeconds = $this->calculateContentRecognizedSeconds($cu);

        $calculatedHours = round($recognizedSeconds / 3600, 4);
        if ($cu->content && $cu->content->max_learning_hours !== null) {
            $calculatedHours = min($calculatedHours, $cu->content->max_learning_hours);
        }

        return $calculatedHours;
    }

    /**
     * Calculate Total Recognized Duration in seconds for a ContentUser record.
     * Formula: min(watched_seconds, course_duration_seconds * 2)
     */
    public function calculateContentRecognizedSeconds(ContentUser $cu): int
    {
        $durationSeconds = (int) ($cu->content?->duration_in_seconds ?? 0);
        if ($durationSeconds <= 0 && $cu->content && !empty($cu->content->duration)) {
            $durationSeconds = DurationFormatter::toSeconds($cu->content->duration) ?? 0;
        }
        if ($durationSeconds <= 0) {
            $durationSeconds = (int) ($cu->duration_seconds ?? 0);
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

        $maxCapSeconds = $durationSeconds > 0 ? ($durationSeconds * 2) : 0;
        if ($cu->content && $cu->content->max_actual_watch_seconds !== null) {
            $maxCapSeconds = $maxCapSeconds > 0
                ? min($maxCapSeconds, (int) $cu->content->max_actual_watch_seconds)
                : (int) $cu->content->max_actual_watch_seconds;
        }

        return ($maxCapSeconds > 0) ? min($actualSeconds, $maxCapSeconds) : $actualSeconds;
    }

    /**
     * Calculate actual consumed learning hours for completed playlists in batch (Zero N+1).
     *
     * Synchronized directly with calculatePlaylistsRecognizedSecondsBatch:
     * both use the exact same capped seconds variable.
     *
     * @param int $userId
     * @param Collection<int, TrainingPlan> $completedPlaylists
     * @return Collection<int, float> Map of [trainingplan_id => actual_learning_hours]
     */
    public function calculatePlaylistsBatch(int $userId, Collection $completedPlaylists): Collection
    {
        return $this->calculatePlaylistsRecognizedSecondsBatch($userId, $completedPlaylists)
            ->map(fn (int $seconds) => round($seconds / 3600, 4));
    }

    /**
     * Single playlist calculation fallback.
     */
    public function calculatePlaylistSingle(int $userId, TrainingPlan $plan): float
    {
        return $this->calculatePlaylistsBatch($userId, collect([$plan]))->get($plan->id, 0.0);
    }

    /**
     * Calculate Total Recognized Duration in seconds for completed playlists in batch (Zero N+1).
     *
     * @param int $userId
     * @param Collection<int, TrainingPlan> $completedPlaylists
     * @return Collection<int, int> Map of [trainingplan_id => recognized_seconds]
     */
    public function calculatePlaylistsRecognizedSecondsBatch(int $userId, Collection $completedPlaylists): Collection
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
            $consumedPlaylistRecognizedSeconds = 0;

            foreach ($validDetails as $d) {
                $cid = $d->content_id;
                $pcu = $planPcus->get($cid);
                $cu = $userCuMap->get($cid);
                $moduleDuration = (int) ($d->content?->duration_in_seconds ?? 0);
                if ($moduleDuration <= 0 && $d->content && !empty($d->content->duration)) {
                    $moduleDuration = DurationFormatter::toSeconds($d->content->duration) ?? 0;
                }

                if ($pcu) {
                    $pcuDuration = $moduleDuration > 0 ? $moduleDuration : (int) ($pcu->duration_seconds ?? 0);
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

                    $maxModuleCap = $pcuDuration > 0 ? ($pcuDuration * 2) : 0;
                    if ($d->content && $d->content->max_actual_watch_seconds !== null) {
                        $maxModuleCap = $maxModuleCap > 0
                            ? min($maxModuleCap, (int) $d->content->max_actual_watch_seconds)
                            : (int) $d->content->max_actual_watch_seconds;
                    }

                    $moduleRecognizedSec = ($maxModuleCap > 0) ? min($moduleSec, $maxModuleCap) : $moduleSec;
                    $consumedPlaylistRecognizedSeconds += $moduleRecognizedSec;
                } elseif ($cu) {
                    $cuDuration = $moduleDuration > 0 ? $moduleDuration : (int) ($cu->duration_seconds ?? 0);
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

                    $maxModuleCap = $cuDuration > 0 ? ($cuDuration * 2) : 0;
                    if ($d->content && $d->content->max_actual_watch_seconds !== null) {
                        $maxModuleCap = $maxModuleCap > 0
                            ? min($maxModuleCap, (int) $d->content->max_actual_watch_seconds)
                            : (int) $d->content->max_actual_watch_seconds;
                    }

                    $moduleRecognizedSec = ($maxModuleCap > 0) ? min($moduleSec, $maxModuleCap) : $moduleSec;
                    $consumedPlaylistRecognizedSeconds += $moduleRecognizedSec;
                }
            }

            $result->put($plan->id, $consumedPlaylistRecognizedSeconds);
        }

        return $result;
    }

    /**
     * Single playlist recognized duration fallback (in seconds).
     */
    public function calculatePlaylistRecognizedSecondsSingle(int $userId, TrainingPlan $plan): int
    {
        return (int) $this->calculatePlaylistsRecognizedSecondsBatch($userId, collect([$plan]))->get($plan->id, 0);
    }

    /**
     * Format recognized duration seconds to HH:MM:SS.
     */
    public function formatRecognizedDuration(int $seconds): string
    {
        return DurationFormatter::fromSeconds($seconds);
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