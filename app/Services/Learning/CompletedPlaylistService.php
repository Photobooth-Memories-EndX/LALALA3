<?php

namespace App\Services\Learning;

use App\Models\ContentUser;
use App\Models\PlaylistContentUser;
use App\Models\TrainingPlan;
use App\Models\TrainingPlanDetail;
use Illuminate\Support\Collection;

/**
 * Shared source for playlists completed through a user's playlist content progress.
 *
 * Utilizes LearningProgressResolver as the authoritative source for module completion,
 * supporting both new playlist_content_users records (authoritative) and historical
 * Category A fallback from content_users.
 */
class CompletedPlaylistService
{
    public function __construct(private LearningProgressResolver $resolver)
    {
    }

    /**
     * @return \Illuminate\Support\Collection<int, TrainingPlan>
     */
    public function forUser(int $userId): Collection
    {
        $plans = TrainingPlan::with([
                'trainingplandetail' => fn ($q) => $q->orderByRaw('CAST(content_order AS UNSIGNED) ASC'),
                'trainingplandetail.content' => fn ($q) => $q->where('active', 'Active'),
            ])
            ->select([
                'trainingplan.id',
                'trainingplan.title',
                'trainingplan.slug',
                'trainingplan.thumbnail',
                'trainingplan.total_duration_seconds',
                'trainingplan.learning_hours',
                'trainingplan.active',
                'trainingplan.created_at',
            ])
            ->where('active', 'Active')
            ->get();

        $completed = collect();

        foreach ($plans as $plan) {
            $validDetails = $plan->trainingplandetail->filter(
                fn ($d) => !empty($d->content) && $d->content->exists && $d->content->active === 'Active'
            );

            $total = $validDetails->count();
            if ($total === 0) {
                continue; // Empty playlist is not completed
            }

            $contentIds = $validDetails->pluck('content_id');

            // Batch resolve progress for this playlist via LearningProgressResolver
            $resolvedBatch = $this->resolver->resolvePlaylistBatch($userId, $plan->id, $contentIds);

            $finished = $resolvedBatch->filter(fn ($r) => $r->isFinished())->count();

            // Business rule: completed if total > 0 and all items are finished
            if ($finished < $total) {
                continue;
            }

            $plan->total_items      = $total;
            $plan->finished_items   = $finished;
            $plan->progress_percent = 100;
            $plan->is_completed     = true;

            $seconds = max(0, (int) ($plan->total_duration_seconds ?? 0));
            $plan->duration = \App\Support\DurationFormatter::fromSeconds($seconds);

            // Fetch timestamp of completion
            $pcuTimestamps = PlaylistContentUser::where('user_id', $userId)
                ->where('trainingplan_id', $plan->id)
                ->whereIn('content_id', $contentIds)
                ->where(fn ($q) => $q->where('finish', '1')->orWhere('progress', '>=', 100))
                ->selectRaw('MAX(updated_at) as last_fin')
                ->first();

            $cuTimestamps = ContentUser::where('user_id', $userId)
                ->whereIn('content_id', $contentIds)
                ->where(fn ($q) => $q->where('finish', '1')->orWhere('progress', '>=', 100))
                ->selectRaw('MAX(updated_at) as last_fin')
                ->first();

            $lastFinished = $pcuTimestamps?->last_fin ?? $cuTimestamps?->last_fin ?? $plan->created_at;
            if ($pcuTimestamps?->last_fin && $cuTimestamps?->last_fin) {
                $lastFinished = max($pcuTimestamps->last_fin, $cuTimestamps->last_fin);
            }
            $plan->last_finished_at = $lastFinished;

            $completed->push($plan);
        }

        return $completed->sortByDesc('last_finished_at')->values();
    }
}
