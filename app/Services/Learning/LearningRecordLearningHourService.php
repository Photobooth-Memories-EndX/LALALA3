<?php

namespace App\Services\Learning;

use App\Models\Content;
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

        $contentAllocations = [];
        $playlistData = $this->calculatePlaylistsDataBatch($userId, $completedPlaylists, $contentAllocations);
        $actualPlaylistHours = (float) $playlistData['hours']->sum();

        $actualContentHours = (float) $completedContentUsers->sum(
            fn (ContentUser $cu) => $this->calculateContentData($cu, $contentAllocations)['learning_hours']
        );

        return round($actualContentHours + $actualPlaylistHours, 2);
    }

    /**
     * Allocate first watch quota (1x duration) and rewatch quota (up to 1x duration)
     * across multiple accesses (Playlist Priority Allocation).
     *
     * @param int $cid Content ID
     * @param int $durationSeconds Original content duration
     * @param int $actualSeconds Actual watch seconds for this access
     * @param Content|null $contentModel
     * @param array $contentAllocations
     * @return array [capped_seconds, recognized_seconds, first_watch_seconds]
     */
    public function allocateContentQuota(
        int $cid,
        int $durationSeconds,
        int $actualSeconds,
        ?Content $contentModel,
        array &$contentAllocations
    ): array {
        if ($durationSeconds <= 0) {
            return [
                'capped_seconds'      => $actualSeconds,
                'recognized_seconds'  => 0,
                'first_watch_seconds' => 0,
            ];
        }

        $alloc = $contentAllocations[$cid] ?? null;
        if (is_array($alloc)) {
            $firstWatchAllocated = (int) ($alloc['first_watch'] ?? 0);
            $rewatchAllocated    = (int) ($alloc['rewatch'] ?? 0);
        } elseif (is_numeric($alloc)) {
            $intAlloc = (int) $alloc;
            $firstWatchAllocated = min($intAlloc, $durationSeconds);
            $rewatchAllocated    = max(0, $intAlloc - $durationSeconds);
        } else {
            $firstWatchAllocated = 0;
            $rewatchAllocated    = 0;
        }

        // 1. First watch quota (up to 1x original duration)
        $firstWatchRemaining = max(0, $durationSeconds - $firstWatchAllocated);
        $consumedFirstWatch  = min($actualSeconds, $firstWatchRemaining);

        // 2. Excess actual watch after first watch goes to recognized rewatch (up to 1x duration)
        $excessActualWatch   = max(0, $actualSeconds - $consumedFirstWatch);
        $rewatchCap          = $durationSeconds;
        if ($contentModel && $contentModel->max_actual_watch_seconds !== null) {
            $rewatchCap = min($rewatchCap, max(0, (int) $contentModel->max_actual_watch_seconds - $durationSeconds));
        }
        $rewatchRemaining    = max(0, $rewatchCap - $rewatchAllocated);
        $consumedRewatch     = min($excessActualWatch, $rewatchRemaining);

        // 3. Total capped watch for this access (used for Learning Hours, max 2x duration)
        $consumedCappedSeconds = $consumedFirstWatch + $consumedRewatch;

        // 4. Update allocations for this content
        $contentAllocations[$cid] = [
            'first_watch' => $firstWatchAllocated + $consumedFirstWatch,
            'rewatch'     => $rewatchAllocated + $consumedRewatch,
            'total'       => $firstWatchAllocated + $consumedFirstWatch + $rewatchAllocated + $consumedRewatch,
        ];

        return [
            'capped_seconds'      => $consumedCappedSeconds,
            'recognized_seconds'  => $consumedRewatch,
            'first_watch_seconds' => $consumedFirstWatch,
        ];
    }

    /**
     * Calculate both Learning Hours and Recognized Rewatch Duration for a ContentUser record.
     *
     * @param ContentUser $cu
     * @param array|null $contentAllocations
     * @return array [learning_hours => float, recognized_seconds => int, capped_seconds => int]
     */
    public function calculateContentData(ContentUser $cu, ?array &$contentAllocations = null): array
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

        if ($isFinished && $durationSeconds > 0) {
            $actualSeconds = max($durationSeconds, $actualWatchSeconds);
        } elseif ($actualWatchSeconds > 0) {
            $actualSeconds = $actualWatchSeconds;
        } elseif ($currentTime > 0) {
            $actualSeconds = $currentTime;
        } elseif ($progress > 0 && $durationSeconds > 0) {
            $actualSeconds = (int) round(($progress / 100) * $durationSeconds);
        } elseif ((float) ($cu->learning_hours ?? 0) > 0) {
            $actualSeconds = (int) round((float) $cu->learning_hours * 3600);
        } else {
            $actualSeconds = 0;
        }

        $cid = (int) ($cu->content_id ?? 0);
        $allocationsRef = $contentAllocations;
        if ($allocationsRef === null) {
            $allocationsRef = [];
            if ($cu->exists && !empty($cu->user_id) && $cid > 0) {
                $completedPlaylists = $this->completedPlaylistsService()->forLearningRecord((int) $cu->user_id);
                if ($completedPlaylists->isNotEmpty()) {
                    $this->calculatePlaylistsDataBatch((int) $cu->user_id, $completedPlaylists, $allocationsRef);
                }
            }
        }

        $allocRes = $this->allocateContentQuota($cid, $durationSeconds, $actualSeconds, $cu->content, $allocationsRef);

        if ($contentAllocations !== null) {
            $contentAllocations = $allocationsRef;
        }

        $calculatedHours = round($allocRes['capped_seconds'] / 3600, 4);
        if ($cu->content && $cu->content->max_learning_hours !== null) {
            $calculatedHours = min($calculatedHours, (float) $cu->content->max_learning_hours);
        }

        return [
            'learning_hours'     => $calculatedHours,
            'recognized_seconds' => $allocRes['recognized_seconds'],
            'capped_seconds'     => $allocRes['capped_seconds'],
        ];
    }

    /**
     * Calculate actual learning hours for a single ContentUser record.
     * Capped at maximum 2x original content duration.
     *
     * @param ContentUser $cu
     * @param array|null $contentAllocations
     * @return float Hours rounded to 4 decimal places
     */
    public function calculateContent(ContentUser $cu, ?array &$contentAllocations = null): float
    {
        return $this->calculateContentData($cu, $contentAllocations)['learning_hours'];
    }

    /**
     * Calculate Total Recognized Duration in seconds for a ContentUser record.
     * Formula: Recognized Rewatch Duration = MIN(MAX(Actual - Duration, 0), Duration).
     *
     * @param ContentUser $cu
     * @param array|null $contentAllocations
     * @return int
     */
    public function calculateContentRecognizedSeconds(ContentUser $cu, ?array &$contentAllocations = null): int
    {
        return $this->calculateContentData($cu, $contentAllocations)['recognized_seconds'];
    }

    /**
     * Calculate both Learning Hours and Recognized Rewatch Duration for completed playlists in batch (Zero N+1).
     *
     * @param int $userId
     * @param Collection<int, TrainingPlan> $completedPlaylists
     * @param array|null $contentAllocations
     * @return array [hours => Collection, recognized_seconds => Collection]
     */
    public function calculatePlaylistsDataBatch(int $userId, Collection $completedPlaylists, ?array &$contentAllocations = null): array
    {
        $planIds = $completedPlaylists->pluck('id')->filter()->all();
        if (empty($planIds)) {
            return [
                'hours'              => collect(),
                'recognized_seconds' => collect(),
            ];
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

        $contentAllocations ??= [];
        $hoursResult = collect();
        $recognizedSecondsResult = collect();

        foreach ($completedPlaylists as $plan) {
            if (!$plan->relationLoaded('trainingplandetail')) {
                $plan->load(['trainingplandetail.content']);
            }

            $validDetails = $plan->trainingplandetail->filter(
                fn ($d) => !empty($d->content) && $d->content->exists
            );

            $planPcus = $userPcuMap->get($plan->id, collect())->keyBy('content_id');
            $consumedPlaylistCappedSeconds = 0;
            $consumedPlaylistRecognizedSeconds = 0;

            foreach ($validDetails as $d) {
                $cid = (int) $d->content_id;
                $pcu = $planPcus->get($cid);
                $cu = $userCuMap->get($cid);
                $moduleDuration = (int) ($d->content?->duration_in_seconds ?? 0);
                if ($moduleDuration <= 0 && $d->content && !empty($d->content->duration)) {
                    $moduleDuration = DurationFormatter::toSeconds($d->content->duration) ?? 0;
                }

                $moduleSec = 0;
                if ($pcu) {
                    $pcuDuration = $moduleDuration > 0 ? $moduleDuration : (int) ($pcu->duration_seconds ?? 0);
                    $pcuActualWatch = (int) ($pcu->actual_watch_seconds ?? 0);
                    $pcuCurrentTime = (int) ($pcu->current_time ?? 0);
                    $pcuProgress = (int) ($pcu->progress ?? 0);
                    $pcuFinished = ((string) $pcu->finish === '1' || $pcuProgress >= 100);

                    if ($pcuFinished && $pcuDuration > 0) {
                        $moduleSec = max($pcuDuration, $pcuActualWatch);
                    } elseif ($pcuActualWatch > 0) {
                        $moduleSec = $pcuActualWatch;
                    } elseif ($pcuCurrentTime > 0) {
                        $moduleSec = $pcuCurrentTime;
                    } elseif ($pcuProgress > 0 && $pcuDuration > 0) {
                        $moduleSec = (int) round(($pcuProgress / 100) * $pcuDuration);
                    } elseif ((float) ($pcu->learning_hour ?? 0) > 0) {
                        $moduleSec = (int) round((float) $pcu->learning_hour * 3600);
                    }
                } elseif ($cu) {
                    $cuDuration = $moduleDuration > 0 ? $moduleDuration : (int) ($cu->duration_seconds ?? 0);
                    $cuActualWatch = (int) ($cu->actual_watch_seconds ?? 0);
                    $cuCurrentTime = (int) ($cu->current_time ?? 0);
                    $cuProgress = (int) ($cu->progress ?? 0);
                    $cuFinished = ((string) $cu->finish === '1' || $cuProgress >= 100);

                    if ($cuFinished && $cuDuration > 0) {
                        $moduleSec = max($cuDuration, $cuActualWatch);
                    } elseif ($cuActualWatch > 0) {
                        $moduleSec = $cuActualWatch;
                    } elseif ($cuCurrentTime > 0) {
                        $moduleSec = $cuCurrentTime;
                    } elseif ($cuProgress > 0 && $cuDuration > 0) {
                        $moduleSec = (int) round(($cuProgress / 100) * $cuDuration);
                    } elseif ((float) ($cu->learning_hours ?? 0) > 0) {
                        $moduleSec = (int) round((float) $cu->learning_hours * 3600);
                    }
                }

                $allocRes = $this->allocateContentQuota($cid, $moduleDuration, $moduleSec, $d->content, $contentAllocations);
                $consumedPlaylistCappedSeconds += $allocRes['capped_seconds'];
                $consumedPlaylistRecognizedSeconds += $allocRes['recognized_seconds'];
            }

            $hoursResult->put($plan->id, round($consumedPlaylistCappedSeconds / 3600, 4));
            $recognizedSecondsResult->put($plan->id, $consumedPlaylistRecognizedSeconds);
        }

        return [
            'hours'              => $hoursResult,
            'recognized_seconds' => $recognizedSecondsResult,
        ];
    }

    /**
     * Calculate actual consumed learning hours for completed playlists in batch (Zero N+1).
     *
     * @param int $userId
     * @param Collection<int, TrainingPlan> $completedPlaylists
     * @param array|null $contentAllocations
     * @return Collection<int, float> Map of [trainingplan_id => actual_learning_hours]
     */
    public function calculatePlaylistsBatch(int $userId, Collection $completedPlaylists, ?array &$contentAllocations = null): Collection
    {
        return $this->calculatePlaylistsDataBatch($userId, $completedPlaylists, $contentAllocations)['hours'];
    }

    /**
     * Single playlist calculation fallback.
     */
    public function calculatePlaylistSingle(int $userId, TrainingPlan $plan, ?array &$contentAllocations = null): float
    {
        return $this->calculatePlaylistsBatch($userId, collect([$plan]), $contentAllocations)->get($plan->id, 0.0);
    }

    /**
     * Calculate Total Recognized Duration in seconds for completed playlists in batch (Zero N+1).
     *
     * @param int $userId
     * @param Collection<int, TrainingPlan> $completedPlaylists
     * @param array|null $contentAllocations
     * @return Collection<int, int> Map of [trainingplan_id => recognized_seconds]
     */
    public function calculatePlaylistsRecognizedSecondsBatch(int $userId, Collection $completedPlaylists, ?array &$contentAllocations = null): Collection
    {
        return $this->calculatePlaylistsDataBatch($userId, $completedPlaylists, $contentAllocations)['recognized_seconds'];
    }

    /**
     * Single playlist recognized duration fallback (in seconds).
     */
    public function calculatePlaylistRecognizedSecondsSingle(int $userId, TrainingPlan $plan): int
    {
        return (int) $this->calculatePlaylistsRecognizedSecondsBatch($userId, collect([$plan]))->get($plan->id, 0);
    }

    /**
     * Resolve the global recognized duration (in seconds) for a user on a given content.
     * Combines valid actual watch across Direct (content_users) and all Playlists (playlist_content_users).
     * Canonical formula: Recognized Rewatch Duration = MIN(MAX(Total Actual - Duration, 0), Duration).
     *
     * @param int $userId
     * @param int|Content $content
     * @return int
     */
    public function resolveGlobalContentRecognizedSeconds(int $userId, int|Content $content): int
    {
        $contentModel = $content instanceof Content ? $content : Content::find((int) $content);
        if (!$contentModel) {
            return 0;
        }

        $durationSeconds = (int) ($contentModel->duration_in_seconds ?? 0);
        if ($durationSeconds <= 0 && !empty($contentModel->duration)) {
            $durationSeconds = DurationFormatter::toSeconds($contentModel->duration) ?? 0;
        }

        if ($durationSeconds <= 0) {
            $cuDuration = (int) (ContentUser::where('user_id', $userId)->where('content_id', $contentModel->id)->value('duration_seconds') ?? 0);
            $pcuDuration = (int) (PlaylistContentUser::where('user_id', $userId)->where('content_id', $contentModel->id)->value('duration_seconds') ?? 0);
            $durationSeconds = max($cuDuration, $pcuDuration);
        }

        if ($durationSeconds <= 0) {
            return 0;
        }

        // Direct content actual watch
        $cu = ContentUser::where('user_id', $userId)->where('content_id', $contentModel->id)->first();
        $cuActualSeconds = 0;
        if ($cu) {
            $cuActualWatch = (int) ($cu->actual_watch_seconds ?? 0);
            $cuCurrentTime = (int) ($cu->current_time ?? 0);
            $cuProgress = (int) ($cu->progress ?? 0);
            $cuFinished = ((string) $cu->finish === '1' || $cuProgress >= 100);

            if ($cuFinished && $durationSeconds > 0) {
                $cuActualSeconds = max($durationSeconds, $cuActualWatch);
            } elseif ($cuActualWatch > 0) {
                $cuActualSeconds = $cuActualWatch;
            } elseif ($cuCurrentTime > 0) {
                $cuActualSeconds = $cuCurrentTime;
            } elseif ($cuProgress > 0 && $durationSeconds > 0) {
                $cuActualSeconds = (int) round(($cuProgress / 100) * $durationSeconds);
            } elseif ((float) ($cu->learning_hours ?? 0) > 0) {
                $cuActualSeconds = (int) round((float) $cu->learning_hours * 3600);
            }
        }

        // Playlist content actual watch (across all playlists)
        $pcus = PlaylistContentUser::where('user_id', $userId)->where('content_id', $contentModel->id)->get();
        $pcuTotalActualSeconds = 0;
        foreach ($pcus as $pcu) {
            $pcuDuration = $durationSeconds > 0 ? $durationSeconds : (int) ($pcu->duration_seconds ?? 0);
            $pcuActualWatch = (int) ($pcu->actual_watch_seconds ?? 0);
            $pcuCurrentTime = (int) ($pcu->current_time ?? 0);
            $pcuProgress = (int) ($pcu->progress ?? 0);
            $pcuFinished = ((string) $pcu->finish === '1' || $pcuProgress >= 100);

            $pcuSec = 0;
            if ($pcuFinished && $pcuDuration > 0) {
                $pcuSec = max($pcuDuration, $pcuActualWatch);
            } elseif ($pcuActualWatch > 0) {
                $pcuSec = $pcuActualWatch;
            } elseif ($pcuCurrentTime > 0) {
                $pcuSec = $pcuCurrentTime;
            } elseif ($pcuProgress > 0 && $pcuDuration > 0) {
                $pcuSec = (int) round(($pcuProgress / 100) * $pcuDuration);
            } elseif ((float) ($pcu->learning_hour ?? 0) > 0) {
                $pcuSec = (int) round((float) $pcu->learning_hour * 3600);
            }

            $pcuTotalActualSeconds += $pcuSec;
        }

        $totalActualSeconds = $cuActualSeconds + $pcuTotalActualSeconds;

        $rewatchCap = $durationSeconds;
        if ($contentModel->max_actual_watch_seconds !== null) {
            $rewatchCap = min($rewatchCap, max(0, (int) $contentModel->max_actual_watch_seconds - $durationSeconds));
        }

        $recognizedSeconds = min(max($totalActualSeconds - $durationSeconds, 0), $rewatchCap);

        return max(0, $recognizedSeconds);
    }

    /**
     * Resolve global recognized duration in integer seconds for multiple contents in batch (Zero N+1).
     * Combines valid actual watch across Direct (content_users) and all Playlists (playlist_content_users).
     *
     * @param int $userId
     * @param Collection|array $contents Collection of Content models, or content IDs
     * @param Collection|null $contentUsers Pre-loaded ContentUser models keyed by content_id or indexed
     * @return Collection<int, int> Map of [content_id => recognized_seconds]
     */
    public function resolveGlobalContentRecognizedSecondsBatch(int $userId, Collection|array $contents, ?Collection $contentUsers = null): Collection
    {
        $contentCollection = $contents instanceof Collection ? $contents : collect($contents);
        if ($contentCollection->isEmpty() && ($contentUsers === null || $contentUsers->isEmpty())) {
            return collect();
        }

        $contentModels = collect();
        foreach ($contentCollection as $item) {
            if ($item instanceof Content) {
                $contentModels->put($item->id, $item);
            } elseif (is_numeric($item)) {
                $contentModels->put((int) $item, null);
            }
        }

        if ($contentUsers !== null) {
            foreach ($contentUsers as $cu) {
                if ($cu instanceof ContentUser && $cu->content_id) {
                    if (!$contentModels->has($cu->content_id) || $contentModels->get($cu->content_id) === null) {
                        $contentModels->put($cu->content_id, $cu->content);
                    }
                }
            }
        }

        $missingContentIds = $contentModels->filter(fn ($m) => $m === null)->keys()->all();
        if (!empty($missingContentIds)) {
            $loadedContents = Content::withTrashed()->whereIn('id', $missingContentIds)->get()->keyBy('id');
            foreach ($loadedContents as $id => $loaded) {
                $contentModels->put($id, $loaded);
            }
        }

        $contentIds = $contentModels->keys()->filter()->values()->all();
        if (empty($contentIds)) {
            return collect();
        }

        $cuMap = $contentUsers !== null
            ? $contentUsers->keyBy('content_id')
            : ContentUser::where('user_id', $userId)->whereIn('content_id', $contentIds)->get()->keyBy('content_id');

        $pcuGroup = PlaylistContentUser::where('user_id', $userId)
            ->whereIn('content_id', $contentIds)
            ->get()
            ->groupBy('content_id');

        $result = collect();

        foreach ($contentIds as $cid) {
            $contentModel = $contentModels->get($cid);
            $durationSeconds = (int) ($contentModel?->duration_in_seconds ?? 0);
            if ($durationSeconds <= 0 && $contentModel && !empty($contentModel->duration)) {
                $durationSeconds = DurationFormatter::toSeconds($contentModel->duration) ?? 0;
            }

            $cu = $cuMap->get($cid);
            $pcus = $pcuGroup->get($cid, collect());

            if ($durationSeconds <= 0) {
                $cuDuration = (int) ($cu?->duration_seconds ?? 0);
                $pcuDuration = (int) ($pcus->max('duration_seconds') ?? 0);
                $durationSeconds = max($cuDuration, $pcuDuration);
            }

            if ($durationSeconds <= 0) {
                $result->put($cid, 0);
                continue;
            }

            // Direct content actual watch
            $cuActualSeconds = 0;
            if ($cu) {
                $cuActualWatch = (int) ($cu->actual_watch_seconds ?? 0);
                $cuCurrentTime = (int) ($cu->current_time ?? 0);
                $cuProgress = (int) ($cu->progress ?? 0);
                $cuFinished = ((string) $cu->finish === '1' || $cuProgress >= 100);

                if ($cuFinished && $durationSeconds > 0) {
                    $cuActualSeconds = max($durationSeconds, $cuActualWatch);
                } elseif ($cuActualWatch > 0) {
                    $cuActualSeconds = $cuActualWatch;
                } elseif ($cuCurrentTime > 0) {
                    $cuActualSeconds = $cuCurrentTime;
                } elseif ($cuProgress > 0 && $durationSeconds > 0) {
                    $cuActualSeconds = (int) round(($cuProgress / 100) * $durationSeconds);
                } elseif ((float) ($cu->learning_hours ?? 0) > 0) {
                    $cuActualSeconds = (int) round((float) $cu->learning_hours * 3600);
                }
            }

            // Playlist content actual watch (across all playlists)
            $pcuTotalActualSeconds = 0;
            foreach ($pcus as $pcu) {
                $pcuDuration = $durationSeconds > 0 ? $durationSeconds : (int) ($pcu->duration_seconds ?? 0);
                $pcuActualWatch = (int) ($pcu->actual_watch_seconds ?? 0);
                $pcuCurrentTime = (int) ($pcu->current_time ?? 0);
                $pcuProgress = (int) ($pcu->progress ?? 0);
                $pcuFinished = ((string) $pcu->finish === '1' || $pcuProgress >= 100);

                $pcuSec = 0;
                if ($pcuFinished && $pcuDuration > 0) {
                    $pcuSec = max($pcuDuration, $pcuActualWatch);
                } elseif ($pcuActualWatch > 0) {
                    $pcuSec = $pcuActualWatch;
                } elseif ($pcuCurrentTime > 0) {
                    $pcuSec = $pcuCurrentTime;
                } elseif ($pcuProgress > 0 && $pcuDuration > 0) {
                    $pcuSec = (int) round(($pcuProgress / 100) * $pcuDuration);
                } elseif ((float) ($pcu->learning_hour ?? 0) > 0) {
                    $pcuSec = (int) round((float) $pcu->learning_hour * 3600);
                }

                $pcuTotalActualSeconds += $pcuSec;
            }

            $totalActualSeconds = $cuActualSeconds + $pcuTotalActualSeconds;

            $rewatchCap = $durationSeconds;
            if ($contentModel && $contentModel->max_actual_watch_seconds !== null) {
                $rewatchCap = min($rewatchCap, max(0, (int) $contentModel->max_actual_watch_seconds - $durationSeconds));
            }

            $recognizedSeconds = min(max($totalActualSeconds - $durationSeconds, 0), $rewatchCap);

            $result->put($cid, max(0, $recognizedSeconds));
        }

        return $result;
    }

    /**
     * Resolve the global recognized duration as HH:MM:SS string.
     *
     * @param int $userId
     * @param int|Content $content
     * @return string
     */
    public function resolveGlobalContentRecognizedDuration(int $userId, int|Content $content): string
    {
        return $this->formatRecognizedDuration($this->resolveGlobalContentRecognizedSeconds($userId, $content));
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