<?php

namespace App\Services;

use App\Models\TrainingPlan;
use App\Models\TrainingPlanDetail;
use App\Support\DurationFormatter;

class PlaylistDurationService
{
    public function refreshForContent(int $contentId): void
    {
        $planIds = TrainingPlanDetail::where('content_id', $contentId)->pluck('trainingplan_id');
        $this->refreshMany($planIds);
    }

    public function refresh(int $trainingPlanId): void
    {
        $this->refreshMany([$trainingPlanId]);
    }

    /**
     * Rebuild cached playlist durations after content/detail changes.
     * The detail/content data is loaded in one eager-loaded query.
     *
     * @param  iterable<int>  $trainingPlanIds
     */
    public function refreshMany(iterable $trainingPlanIds): void
    {
        $ids = collect($trainingPlanIds)->filter()->map(fn ($id) => (int) $id)->unique()->values();

        if ($ids->isEmpty()) {
            return;
        }

        $details = TrainingPlanDetail::with('content:id,duration')
            ->whereIn('trainingplan_id', $ids)
            ->get()
            ->groupBy('trainingplan_id');

        TrainingPlan::whereIn('id', $ids)->get()->each(function (TrainingPlan $plan) use ($details) {
            $seconds = $details->get($plan->id, collect())->sum(function (TrainingPlanDetail $detail) {
                $duration = $detail->content?->duration;

                return DurationFormatter::toSeconds($duration) ?? 0;
            });

            $plan->forceFill(['total_duration_seconds' => (int) $seconds])->saveQuietly();
        });
    }
}
