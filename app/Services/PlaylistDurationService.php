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
        $this->synchronizeAll($trainingPlanIds);
    }

    /**
     * Bulk calculate and synchronize total_duration_seconds for all (or specified) playlists.
     *
     * @param  iterable<int>|null  $trainingPlanIds
     * @return array{total_playlists: int, updated: int, unchanged: int, total_materials: int, durations: array}
     */
    public function synchronizeAll(?iterable $trainingPlanIds = null): array
    {
        $planQuery = TrainingPlan::query();

        if ($trainingPlanIds !== null) {
            $ids = collect($trainingPlanIds)
                ->filter(fn ($id) => $id !== null && $id !== '')
                ->map(fn ($id) => (int) $id)
                ->unique()
                ->values();

            if ($ids->isEmpty()) {
                return [
                    'total_playlists' => 0,
                    'updated'         => 0,
                    'unchanged'       => 0,
                    'total_materials' => 0,
                    'durations'       => [],
                ];
            }

            $planQuery->whereIn('id', $ids);
        }

        $plans = $planQuery->get();
        $targetIds = $plans->pluck('id')->all();

        if (empty($targetIds)) {
            return [
                'total_playlists' => 0,
                'updated'         => 0,
                'unchanged'       => 0,
                'total_materials' => 0,
                'durations'       => [],
            ];
        }

        // Eager load details with content duration (only 2 select queries for entire dataset)
        $details = TrainingPlanDetail::with(['content' => fn ($q) => $q->select('id', 'duration')])
            ->whereIn('trainingplan_id', $targetIds)
            ->get()
            ->groupBy('trainingplan_id');

        $updatedCount = 0;
        $unchangedCount = 0;
        $totalMaterialsCount = 0;
        $durations = [];

        foreach ($plans as $plan) {
            $planDetails = $details->get($plan->id, collect());
            $materialsCount = $planDetails->count();
            $totalMaterialsCount += $materialsCount;

            $calculatedSeconds = (int) $planDetails->sum(function (TrainingPlanDetail $detail) {
                $duration = $detail->content?->duration;

                return DurationFormatter::toSeconds($duration) ?? 0;
            });

            $oldSeconds = (int) ($plan->total_duration_seconds ?? 0);
            $isChanged = ($oldSeconds !== $calculatedSeconds);

            if ($isChanged) {
                $plan->forceFill(['total_duration_seconds' => $calculatedSeconds])->saveQuietly();
                $updatedCount++;
            } else {
                $unchangedCount++;
            }

            $durations[] = [
                'id'              => $plan->id,
                'title'           => $plan->title ?? ('Playlist #' . $plan->id),
                'old_duration'    => DurationFormatter::fromSeconds($oldSeconds),
                'new_duration'    => DurationFormatter::fromSeconds($calculatedSeconds),
                'old_seconds'     => $oldSeconds,
                'new_seconds'     => $calculatedSeconds,
                'materials_count' => $materialsCount,
                'updated'         => $isChanged,
            ];
        }

        return [
            'total_playlists' => $plans->count(),
            'updated'         => $updatedCount,
            'unchanged'       => $unchangedCount,
            'total_materials' => $totalMaterialsCount,
            'durations'       => $durations,
        ];
    }
}

