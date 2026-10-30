<?php

namespace App\Services\Learning;

use App\Models\ContentUser;
use App\Models\OfflineTrainingUser;
use App\Models\PlaylistContentUser;
use App\Services\LearningAnalyticsService;
use Illuminate\Support\Collection;

class LearningProgressResolver
{
    /**
     * Resolve Direct Content progress (source: content_users).
     */
    public function resolveDirect(int $userId, int $contentId): ResolvedProgress
    {
        $cu = ContentUser::where('user_id', $userId)
            ->where('content_id', $contentId)
            ->first();

        if ($cu) {
            return ResolvedProgress::fromContentUser($cu);
        }

        return ResolvedProgress::empty(ResolvedProgress::SOURCE_NONE);
    }

    /**
     * Resolve Playlist Content progress (source: playlist_content_users).
     *
     * Authoritative rule:
     * - If PCU exists for (user_id, trainingplan_id, content_id), return PCU progress.
     *   (Whether progress is 0%, 40%, or 100%, PCU is always authoritative).
     * - If PCU does not exist, return empty progress (0%, SOURCE_NONE).
     * - No runtime fallback to content_users.
     */
    public function resolvePlaylist(int $userId, int $trainingPlanId, int $contentId): ResolvedProgress
    {
        $pcu = PlaylistContentUser::where('user_id', $userId)
            ->where('trainingplan_id', $trainingPlanId)
            ->where('content_id', $contentId)
            ->first();

        if ($pcu) {
            return ResolvedProgress::fromPlaylistContentUser($pcu);
        }

        return ResolvedProgress::empty(ResolvedProgress::SOURCE_NONE);
    }

    /**
     * Batch resolve progress for an entire Playlist (Zero N+1, single query).
     *
     * @param  int  $userId
     * @param  int  $trainingPlanId
     * @param  array<int>|Collection<int, int>  $contentIds
     * @return Collection<int, ResolvedProgress> Keyed by content_id
     */
    public function resolvePlaylistBatch(int $userId, int $trainingPlanId, array|Collection $contentIds): Collection
    {
        $contentIdArray = collect($contentIds)->map(fn ($id) => (int) $id)->filter()->values()->all();

        if (empty($contentIdArray)) {
            return collect();
        }

        // 1. Single query: Fetch all authoritative playlist_content_users for this playlist
        $pcus = PlaylistContentUser::where('user_id', $userId)
            ->where('trainingplan_id', $trainingPlanId)
            ->whereIn('content_id', $contentIdArray)
            ->get()
            ->keyBy(fn ($p) => (int) $p->content_id);

        // 2. Map every requested content_id: PCU if present, empty 0% otherwise
        $result = collect();

        foreach ($contentIdArray as $cid) {
            if ($pcus->has($cid)) {
                $result->put($cid, ResolvedProgress::fromPlaylistContentUser($pcus->get($cid)));
            } else {
                $result->put($cid, ResolvedProgress::empty(ResolvedProgress::SOURCE_NONE));
            }
        }

        return $result;
    }

    /**
     * Count Non-KP OWL ongoing training records for a user.
     * Criteria: Dual-key match, training started (start_date <= now) and progress < 100.0 (or null), OR progress > 5.0 and progress < 100.0.
     */
    public function countNonKpOwlOngoing(int $userId, ?string $userEmpId = null): int
    {
        if (empty($userEmpId)) {
            $userEmpId = \App\Models\User::where('id', $userId)->value('id_emp');
        }

        return OfflineTrainingUser::query()
            ->where(function ($q) use ($userId, $userEmpId) {
                $q->where('user_id', $userId);
                if (!empty($userEmpId)) {
                    $q->orWhere('employee_id', $userEmpId);
                }
            })
            ->whereNull('deleted_at')
            ->whereHas('training', function ($q) {
                $q->whereNull('deleted_at');
            })
            ->where(function ($q) {
                $q->where(function ($sub) {
                    $sub->whereHas('training', function ($tq) {
                        $tq->where('start_date', '<=', now());
                    })->where(function ($pq) {
                        $pq->where('progress', '<', 100.0)
                           ->orWhereNull('progress');
                    });
                })->orWhere(function ($sub) {
                    $sub->where('progress', '>', 5.0)
                        ->where('progress', '<', 100.0);
                });
            })
            ->count();
    }

    /**
     * Get Non-KP OWL ongoing training records for a user.
     *
     * Dual-key user match (user_id or employee_id).
     * Timing & progress: training started (start_date <= now) and progress < 100 (or null), OR progress > 5.0 and progress < 100.0.
     *
     * @return Collection<int, OfflineTrainingUser>
     */
    public function getOngoingNonKpOwl(int $userId, ?string $userEmpId = null): Collection
    {
        if (empty($userEmpId)) {
            $userEmpId = \App\Models\User::where('id', $userId)->value('id_emp');
        }

        return OfflineTrainingUser::query()
            ->with(['training' => fn ($q) => $q->withTrashed()])
            ->where(function ($q) use ($userId, $userEmpId) {
                $q->where('user_id', $userId);
                if (!empty($userEmpId)) {
                    $q->orWhere('employee_id', $userEmpId);
                }
            })
            ->whereNull('deleted_at')
            ->whereHas('training', function ($q) {
                $q->whereNull('deleted_at');
            })
            ->where(function ($q) {
                $q->where(function ($sub) {
                    $sub->whereHas('training', function ($tq) {
                        $tq->where('start_date', '<=', now());
                    })->where(function ($pq) {
                        $pq->where('progress', '<', 100.0)
                           ->orWhereNull('progress');
                    });
                })->orWhere(function ($sub) {
                    $sub->where('progress', '>', 5.0)
                        ->where('progress', '<', 100.0);
                });
            })
            ->orderBy('id', 'desc')
            ->get()
            ->filter(fn (OfflineTrainingUser $otu) => $otu->training !== null)
            ->values();
    }

    /**
     * Count KP OWL ongoing learning records for a user.
     */
    public function countKpOwlOngoing(int $userId): int
    {
        return (int) app(LearningAnalyticsService::class)->ongoingLearningCount($userId);
    }

    /**
     * Count combined ongoing learning records across KP OWL and Non-KP OWL (> 5% progress).
     */
    public function countCombinedOngoing(int $userId, ?string $userEmpId = null): int
    {
        return $this->countKpOwlOngoing($userId) + $this->countNonKpOwlOngoing($userId, $userEmpId);
    }
}
