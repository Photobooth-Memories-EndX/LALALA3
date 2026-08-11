<?php

namespace App\Services\Learning;

use App\Models\ContentUser;
use App\Models\TrainingPlan;
use App\Models\TrainingPlanDetail;
use Illuminate\Support\Collection;

/**
 * Shared source for playlists completed through a user's content progress.
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
                    ->join('content_users', 'content_users.content_id', '=', 'trainingplandetail.content_id')
                    ->whereColumn('trainingplandetail.trainingplan_id', 'trainingplan.id')
                    ->where('content_users.user_id', $userId)
                    ->where('content_users.finish', '1');
            })
            ->selectSub(
                TrainingPlanDetail::selectRaw('COUNT(*)')
                    ->whereColumn('trainingplandetail.trainingplan_id', 'trainingplan.id'),
                'total_items'
            )
            ->selectSub(
                ContentUser::selectRaw('COUNT(*)')
                    ->join('trainingplandetail', 'trainingplandetail.content_id', '=', 'content_users.content_id')
                    ->whereColumn('trainingplandetail.trainingplan_id', 'trainingplan.id')
                    ->where('content_users.user_id', $userId)
                    ->where('content_users.finish', '1'),
                'finished_items'
            )
            ->selectSub(
                ContentUser::selectRaw('MAX(content_users.updated_at)')
                    ->join('trainingplandetail', 'trainingplandetail.content_id', '=', 'content_users.content_id')
                    ->whereColumn('trainingplandetail.trainingplan_id', 'trainingplan.id')
                    ->where('content_users.user_id', $userId)
                    ->where('content_users.finish', '1'),
                'last_finished_at'
            )
            ->orderByDesc('last_finished_at')
            ->get()
            ->filter(fn (TrainingPlan $plan) => (int) $plan->total_items > 0
                && (int) $plan->finished_items >= (int) $plan->total_items)
            ->values();
    }
}
