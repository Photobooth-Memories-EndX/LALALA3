<?php

namespace App\Services\Learning;

use App\Models\Content;
use App\Models\ContentUser;
use App\Models\PlaylistContentUser;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

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
            return ResolvedProgress::fromContentUser($cu, asHistoricalPlaylistFallback: false);
        }

        return ResolvedProgress::empty(ResolvedProgress::SOURCE_NONE);
    }

    /**
     * Resolve Playlist Content progress.
     *
     * Priority:
     * 1. playlist_content_users (Authoritative)
     * 2. Historical content_users fallback ONLY for Category A (unambiguous, single active playlist & playlistonly = 'Y')
     * 3. Empty / Unresolved (zero progress)
     */
    public function resolvePlaylist(int $userId, int $trainingPlanId, int $contentId): ResolvedProgress
    {
        // 1. Authoritative: Check new Playlist progress table
        $pcu = PlaylistContentUser::where('user_id', $userId)
            ->where('trainingplan_id', $trainingPlanId)
            ->where('content_id', $contentId)
            ->first();

        if ($pcu) {
            return ResolvedProgress::fromPlaylistContentUser($pcu);
        }

        // 2. Safe Historical Fallback:
        // Must satisfy Category A rules:
        // - Content has playlistonly = 'Y' (not Direct Content)
        // - Content belongs to EXACTLY ONE active Playlist
        // - That active Playlist is $trainingPlanId
        $content = Content::select(['id', 'playlistonly', 'active'])->find($contentId);

        if (!$content || ($content->playlistonly ?? 'N') !== 'Y') {
            // Category C: Direct Content (playlistonly = 'N') must NEVER be attributed to Playlist
            return ResolvedProgress::empty(ResolvedProgress::SOURCE_NONE);
        }

        $activePlanIds = DB::table('trainingplandetail as tpd')
            ->join('trainingplan as tp', 'tp.id', '=', 'tpd.trainingplan_id')
            ->where('tpd.content_id', $contentId)
            ->where('tp.active', 'Active')
            ->pluck('tp.id')
            ->map(fn ($id) => (int) $id)
            ->all();

        $activePlanCount = count($activePlanIds);

        if ($activePlanCount === 1 && $activePlanIds[0] === (int) $trainingPlanId) {
            // Category A (Single Active Playlist) -> Safe to fallback to historical content_users
            $cu = ContentUser::where('user_id', $userId)
                ->where('content_id', $contentId)
                ->first();

            if ($cu) {
                return ResolvedProgress::fromContentUser($cu, asHistoricalPlaylistFallback: true);
            }

            return ResolvedProgress::empty(ResolvedProgress::SOURCE_NONE);
        }

        if ($activePlanCount > 1) {
            // Category B (Multiple Active Playlists) -> Ambiguous: DO NOT guess attribution
            return ResolvedProgress::empty(ResolvedProgress::SOURCE_AMBIGUOUS);
        }

        // Category D (0 Active Playlists / Orphan / Inactive) -> No attribution
        return ResolvedProgress::empty(ResolvedProgress::SOURCE_NONE);
    }

    /**
     * Batch resolve progress for an entire Playlist (Zero N+1).
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

        // 1. Fetch all authoritative playlist_content_users in one query
        $pcus = PlaylistContentUser::where('user_id', $userId)
            ->where('trainingplan_id', $trainingPlanId)
            ->whereIn('content_id', $contentIdArray)
            ->get()
            ->keyBy(fn ($p) => (int) $p->content_id);

        // Identify content IDs that need fallback check
        $missingContentIds = array_diff($contentIdArray, $pcus->keys()->all());

        $historicalFallbackMap = collect();

        if (!empty($missingContentIds)) {
            // 2. Fetch content metadata for missing contents
            $contents = Content::whereIn('id', $missingContentIds)
                ->get(['id', 'playlistonly'])
                ->keyBy(fn ($c) => (int) $c->id);

            // Fetch active playlist counts for missing contents in one query
            $activePlanCountsByContent = DB::table('trainingplandetail as tpd')
                ->join('trainingplan as tp', 'tp.id', '=', 'tpd.trainingplan_id')
                ->whereIn('tpd.content_id', $missingContentIds)
                ->where('tp.active', 'Active')
                ->selectRaw('tpd.content_id, tp.id as tp_id')
                ->get()
                ->groupBy('content_id');

            $categoryAContentIds = [];
            $ambiguousContentIds = [];

            foreach ($missingContentIds as $cid) {
                $c = $contents->get($cid);
                if (!$c || ($c->playlistonly ?? 'N') !== 'Y') {
                    continue; // Category C: Direct Content -> no fallback
                }

                $plans = $activePlanCountsByContent->get($cid, collect());
                $planIds = $plans->pluck('tp_id')->map(fn ($id) => (int) $id)->all();

                if (count($planIds) === 1 && $planIds[0] === (int) $trainingPlanId) {
                    $categoryAContentIds[] = $cid;
                } elseif (count($planIds) > 1) {
                    $ambiguousContentIds[] = $cid;
                }
            }

            // 3. Fetch historical content_users for Category A contents in one query
            if (!empty($categoryAContentIds)) {
                $cus = ContentUser::where('user_id', $userId)
                    ->whereIn('content_id', $categoryAContentIds)
                    ->get()
                    ->keyBy(fn ($cu) => (int) $cu->content_id);

                foreach ($categoryAContentIds as $cid) {
                    if ($cus->has($cid)) {
                        $historicalFallbackMap->put($cid, ResolvedProgress::fromContentUser($cus->get($cid), asHistoricalPlaylistFallback: true));
                    }
                }
            }

            foreach ($ambiguousContentIds as $cid) {
                $historicalFallbackMap->put($cid, ResolvedProgress::empty(ResolvedProgress::SOURCE_AMBIGUOUS));
            }
        }

        // Build complete resolved collection
        $result = collect();

        foreach ($contentIdArray as $cid) {
            if ($pcus->has($cid)) {
                $result->put($cid, ResolvedProgress::fromPlaylistContentUser($pcus->get($cid)));
            } elseif ($historicalFallbackMap->has($cid)) {
                $result->put($cid, $historicalFallbackMap->get($cid));
            } else {
                $result->put($cid, ResolvedProgress::empty(ResolvedProgress::SOURCE_NONE));
            }
        }

        return $result;
    }
}
