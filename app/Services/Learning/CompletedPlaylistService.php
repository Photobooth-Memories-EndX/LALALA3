<?php

namespace App\Services\Learning;

use App\Models\PlaylistContentUser;
use App\Models\TrainingPlan;
use App\Models\TrainingPlanDetail;
use Illuminate\Support\Collection;

/**
 * Shared source for playlists completed through a user's playlist content progress.
 */
class CompletedPlaylistService
{
    /**
     * @return \Illuminate\Support\Collection<int, TrainingPlan>
     */
    public function forUser(int $userId): Collection
    {
        return TrainingPlan::query()
            ->select('trainingplan.id', 'trainingplan.title', 'trainingplan.slug', 'trainingplan.thumbnail', 'trainingplan.total_duration_seconds', 'trainingplan.learning_hours')
            ->whereExists(function ($q) use ($userId) {
                $q->from('trainingplandetail')
                    ->join('playlist_content_users', function ($j) {
                        $j->on('playlist_content_users.trainingplan_id', '=', 'trainingplandetail.trainingplan_id')
                          ->on('playlist_content_users.content_id', '=', 'trainingplandetail.content_id');
                    })
                    ->whereColumn('trainingplandetail.trainingplan_id', 'trainingplan.id')
                    ->where('playlist_content_users.user_id', $userId)
                    ->where(function ($w) {
                        $w->where('playlist_content_users.finish', '1')
                            ->orWhere('playlist_content_users.progress', '>=', 100);
                    });
            })
            ->selectSub(
                TrainingPlanDetail::selectRaw('COUNT(*)')
                    ->whereColumn('trainingplandetail.trainingplan_id', 'trainingplan.id'),
                'total_items'
            )
            ->selectSub(
                PlaylistContentUser::selectRaw('COUNT(*)')
                    ->join('trainingplandetail', function ($j) {
                        $j->on('trainingplandetail.trainingplan_id', '=', 'playlist_content_users.trainingplan_id')
                          ->on('trainingplandetail.content_id', '=', 'playlist_content_users.content_id');
                    })
                    ->whereColumn('trainingplandetail.trainingplan_id', 'trainingplan.id')
                    ->where('playlist_content_users.user_id', $userId)
                    ->where(function ($w) {
                        $w->where('playlist_content_users.finish', '1')
                            ->orWhere('playlist_content_users.progress', '>=', 100);
                    }),
                'finished_items'
            )
            ->selectSub(
                PlaylistContentUser::selectRaw('MAX(playlist_content_users.updated_at)')
                    ->join('trainingplandetail', function ($j) {
                        $j->on('trainingplandetail.trainingplan_id', '=', 'playlist_content_users.trainingplan_id')
                          ->on('trainingplandetail.content_id', '=', 'playlist_content_users.content_id');
                    })
                    ->whereColumn('trainingplandetail.trainingplan_id', 'trainingplan.id')
                    ->where('playlist_content_users.user_id', $userId)
                    ->where(function ($w) {
                        $w->where('playlist_content_users.finish', '1')
                            ->orWhere('playlist_content_users.progress', '>=', 100);
                    }),
                'last_finished_at'
            )
            ->orderByDesc('last_finished_at')
            ->get()
            ->filter(fn (TrainingPlan $plan) => (int) $plan->total_items > 0
                && (int) $plan->finished_items >= (int) $plan->total_items)
            ->values();
    }
}
