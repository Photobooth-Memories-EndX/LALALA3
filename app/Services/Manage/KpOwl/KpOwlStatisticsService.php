<?php

namespace App\Services\Manage\KpOwl;

use App\Queries\Manage\KpOwl\KpOwlRecordQuery;
use Illuminate\Support\Facades\DB;

class KpOwlStatisticsService
{
    protected KpOwlRecordQuery $recordQuery;

    public function __construct(KpOwlRecordQuery $recordQuery)
    {
        $this->recordQuery = $recordQuery;
    }

    /**
     * Compute KPI metrics for KP OWL dashboard.
     */
    public function getStatistics(array $filters = []): array
    {
        // 1. Standalone Content Metrics
        $cuBase = DB::table('content_users as cu');
        $cuTotal = (int) (clone $cuBase)->count();
        $cuFinished = (int) (clone $cuBase)->where(function ($q) {
            $q->where('cu.finish', '1')->orWhere('cu.progress', '>=', 100);
        })->count();
        $cuOngoing = (int) (clone $cuBase)->where('cu.finish', '0')->where('cu.progress', '>', 0)->where('cu.progress', '<', 100)->count();
        $cuHours = (float) (clone $cuBase)->sum('cu.learning_hours');

        // 2. Playlist Metrics (grouped per user and trainingplan_id)
        $pcuGrouped = DB::table('playlist_content_users as pcu')
            ->select([
                'pcu.user_id',
                'pcu.trainingplan_id',
                DB::raw("CASE WHEN SUM(CASE WHEN pcu.finish = '1' OR pcu.progress >= 100 THEN 1 ELSE 0 END) = COUNT(pcu.id) THEN 1 ELSE 0 END as is_finished"),
                DB::raw("AVG(COALESCE(pcu.progress, 0)) as avg_progress"),
                DB::raw("SUM(COALESCE(pcu.learning_hour, 0)) as total_lh")
            ])
            ->groupBy(['pcu.user_id', 'pcu.trainingplan_id']);

        $pcuStats = DB::query()
            ->fromSub($pcuGrouped, 'grp')
            ->selectRaw('
                COUNT(*) as total_count,
                SUM(CASE WHEN is_finished = 1 OR avg_progress >= 100 THEN 1 ELSE 0 END) as finished_count,
                SUM(CASE WHEN is_finished = 0 AND avg_progress > 0 AND avg_progress < 100 THEN 1 ELSE 0 END) as ongoing_count,
                SUM(total_lh) as total_hours
            ')
            ->first();

        $pcuTotal = (int) ($pcuStats->total_count ?? 0);
        $pcuFinished = (int) ($pcuStats->finished_count ?? 0);
        $pcuOngoing = (int) ($pcuStats->ongoing_count ?? 0);
        $pcuHours = (float) ($pcuStats->total_hours ?? 0);

        // 3. Combined Total Metrics
        $totalLearning = $cuTotal + $pcuTotal;
        $finished = $cuFinished + $pcuFinished;
        $ongoing = $cuOngoing + $pcuOngoing;
        $notStarted = max(0, $totalLearning - ($finished + $ongoing));
        $totalHours = round($cuHours + $pcuHours, 2);
        $completionRate = $totalLearning > 0 ? round(($finished / $totalLearning) * 100, 1) : 0.0;

        return [
            'total_learning'       => $totalLearning,
            'ongoing'              => $ongoing,
            'finished'             => $finished,
            'not_started'          => $notStarted,
            'completion_rate'      => $completionRate,
            'total_learning_hours' => $totalHours,
        ];
    }
}
