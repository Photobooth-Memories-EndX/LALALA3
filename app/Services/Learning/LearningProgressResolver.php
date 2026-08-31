<?php

namespace App\Services\Learning;

use App\Models\ContentUser;
use App\Models\PlaylistContentUser;
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
}
