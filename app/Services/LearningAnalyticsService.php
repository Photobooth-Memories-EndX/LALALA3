<?php

namespace App\Services;

use App\Models\AssesmentResult;
use App\Models\Certificate;
use App\Models\ContentUser;
use App\Services\Learning\LearningHourService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

/**
 * Membangun seluruh dataset analytics untuk halaman My Learning Monitoring.
 * Semua perhitungan memakai aggregation query (bukan koleksi besar) dan
 * di-cache 5 menit agar tetap ringan untuk data ribuan user.
 */
class LearningAnalyticsService
{
     private const TTL = 60 * 60 * 24; // 1 hari

    /** Ekspresi MySQL untuk konversi content.duration -> jam. */
    private const HOURS_EXPR = "
        CASE
            WHEN LENGTH(content.duration) - LENGTH(REPLACE(content.duration, ':', '')) = 2
            THEN TIME_TO_SEC(content.duration) / 3600
            WHEN LENGTH(content.duration) - LENGTH(REPLACE(content.duration, ':', '')) = 1
            THEN TIME_TO_SEC(CONCAT('00:', content.duration)) / 3600
            ELSE 0
        END
    ";

    public function __construct(private readonly LearningHourService $learningHourService)
    {
    }

    /*
    |--------------------------------------------------------------------------
    | Personal analytics (role 1/2 dan halaman profile)
    |--------------------------------------------------------------------------
    */
    public function personal(int $userId): array
    {
        return Cache::remember("learning_analytics_personal_{$userId}", self::TTL, function () use ($userId) {
            return [
                'summary'             => $this->personalSummary($userId),
                'category'            => $this->categoryDistribution($userId),
                'monthly_activity'    => $this->monthlyActivity($userId),
                'completion_trend'    => $this->completionTrend($userId),
                'assessment_trend'    => $this->assessmentTrend($userId),
                'type_distribution'   => $this->typeDistribution($userId),
                'status_distribution' => $this->statusDistribution($userId),
                'top_categories'      => $this->topCategories($userId),
                'weekly_heatmap'      => $this->weeklyHeatmap($userId),
                'hours_trend'         => $this->hoursTrend($userId),
            ];
        });
    }

    /*
    |--------------------------------------------------------------------------
    | Team / organization analytics (role 3/4)
    |--------------------------------------------------------------------------
    | $cacheKey  : penanda unik scope (mis. "all" / "team:IT").
    | $idsFactory: closure mengembalikan Eloquent user-query fresh (untuk subquery id).
    */
    public function team(string $cacheKey, \Closure $idsFactory): array
    {
        return Cache::remember("learning_analytics_team_{$cacheKey}", self::TTL, function () use ($idsFactory) {
            return [
                'summary'               => $this->teamSummary($idsFactory),
                'progress_distribution' => $this->teamProgressDistribution($idsFactory),
                'top_learners'          => $this->teamTopLearners($idsFactory),
                'monthly_activity'      => $this->teamMonthlyActivity($idsFactory),
            ];
        });
    }

    /**
     * Full chart dataset for a scoped group of users (monitoring index / group pages).
     * Keys match the personal() output so the same Blade component can render both.
     *
     * @param string   $cacheKey   Unique identifier for this scope (e.g. "all-users", "group-team-IT").
     * @param \Closure $idsFactory Returns a fresh Eloquent User builder scoped to the desired users.
     */
    public function groupCharts(string $cacheKey, \Closure $idsFactory): array
    {
        return Cache::remember("learning_analytics_group_{$cacheKey}", self::TTL, function () use ($idsFactory) {
            return [
                'category'            => $this->categoryDistributionForIds($idsFactory),
                'type_distribution'   => $this->typeDistributionForIds($idsFactory),
                'status_distribution' => $this->statusDistributionForIds($idsFactory),
                'monthly_activity'    => $this->monthlyActivityForIds($idsFactory),
                'completion_trend'    => $this->completionTrendForIds($idsFactory),
                'assessment_trend'    => $this->assessmentTrendForIds($idsFactory),
                'top_categories'      => $this->topCategoriesForIds($idsFactory),
                'hours_trend'         => $this->hoursTrendForIds($idsFactory),
            ];
        });
    }

    public function forgetPersonal(int $userId): void
    {
        Cache::forget("learning_analytics_personal_{$userId}");
    }

    /*
    |--------------------------------------------------------------------------
    | Personal builders
    |--------------------------------------------------------------------------
    */
    private function personalSummary(int $userId): array
    {
        $base = ContentUser::where('user_id', $userId);

        $total      = (clone $base)->count();
        $finished   = (clone $base)->where('finish', '1')->count();
        $ongoing    = (clone $base)->where('finish', '0')->count();
        $avgProgress = (int) round((clone $base)->avg('progress') ?? 0);

        $assessmentCompleted = AssesmentResult::where('user_id', $userId)->where('final', 1)->count();
        $avgScore            = (int) round(AssesmentResult::where('user_id', $userId)->where('final', 1)->avg('score') ?? 0);
        $certificate         = Certificate::where('user_id', $userId)->count();

        return [
            'total_learning'       => $total,
            'finished_learning'    => $finished,
            'ongoing_learning'     => $ongoing,
            'average_progress'     => $avgProgress,
            'assessment_completed' => $assessmentCompleted,
            'average_score'        => $avgScore,
            'certificate'          => $certificate,
            'learning_hours'       => $this->userLearningHours($userId),
        ];
    }

    private function categoryDistribution(int $userId): array
    {
        $rows = DB::table('content_users as cu')
            ->join('content as content', 'content.id', '=', 'cu.content_id')
            ->leftJoin('classification as cl', 'cl.id', '=', 'content.classification_id')
            ->where('cu.user_id', $userId)
            ->whereNull('content.deleted_at')
            ->groupBy('label')
            ->selectRaw("COALESCE(cl.classification_name, 'Lainnya') as label, COUNT(*) as total")
            ->orderByDesc('total')
            ->get();

        return [
            'labels' => $rows->pluck('label')->all(),
            'series' => $rows->pluck('total')->map(fn ($v) => (int) $v)->all(),
        ];
    }

    private function topCategories(int $userId): array
    {
        $rows = DB::table('content_users as cu')
            ->join('content as content', 'content.id', '=', 'cu.content_id')
            ->leftJoin('classification as cl', 'cl.id', '=', 'content.classification_id')
            ->where('cu.user_id', $userId)
            ->whereNull('content.deleted_at')
            ->groupBy('label')
            ->selectRaw("COALESCE(cl.classification_name, 'Lainnya') as label, COUNT(*) as total")
            ->orderByDesc('total')
            ->limit(5)
            ->get();

        return [
            'labels' => $rows->pluck('label')->all(),
            'series' => $rows->pluck('total')->map(fn ($v) => (int) $v)->all(),
        ];
    }

    private function typeDistribution(int $userId): array
    {
        $rows = DB::table('content_users as cu')
            ->join('content as content', 'content.id', '=', 'cu.content_id')
            ->where('cu.user_id', $userId)
            ->whereNull('content.deleted_at')
            ->groupBy('label')
            ->selectRaw("COALESCE(NULLIF(content.type, ''), 'Lainnya') as label, COUNT(*) as total")
            ->orderByDesc('total')
            ->get();

        return [
            'labels' => $rows->pluck('label')->all(),
            'series' => $rows->pluck('total')->map(fn ($v) => (int) $v)->all(),
        ];
    }

    private function statusDistribution(int $userId): array
    {
        $row = ContentUser::where('user_id', $userId)
            ->selectRaw("
                SUM(CASE WHEN finish = '1' OR progress >= 100 THEN 1 ELSE 0 END) as completed,
                SUM(CASE WHEN finish = '0' AND progress BETWEEN 1 AND 99 THEN 1 ELSE 0 END) as ongoing,
                SUM(CASE WHEN finish = '0' AND progress = 0 THEN 1 ELSE 0 END) as not_started
            ")
            ->first();

        return [
            'labels' => ['Completed', 'Ongoing', 'Not Started'],
            'series' => [
                (int) ($row->completed ?? 0),
                (int) ($row->ongoing ?? 0),
                (int) ($row->not_started ?? 0),
            ],
        ];
    }

    private function monthlyActivity(int $userId): array
    {
        $rows = ContentUser::where('user_id', $userId)
            ->where('updated_at', '>=', now()->subMonths(11)->startOfMonth())
            ->selectRaw("DATE_FORMAT(updated_at, '%Y-%m') as ym, COUNT(*) as total")
            ->groupBy('ym')
            ->pluck('total', 'ym');

        return $this->mapMonthly($rows);
    }

    private function completionTrend(int $userId): array
    {
        $rows = ContentUser::where('user_id', $userId)
            ->where('finish', '1')
            ->where('updated_at', '>=', now()->subMonths(11)->startOfMonth())
            ->selectRaw("DATE_FORMAT(updated_at, '%Y-%m') as ym, COUNT(*) as total")
            ->groupBy('ym')
            ->pluck('total', 'ym');

        return $this->mapMonthly($rows);
    }

    private function assessmentTrend(int $userId): array
    {
        $rows = AssesmentResult::where('user_id', $userId)
            ->where('created_at', '>=', now()->subMonths(11)->startOfMonth())
            ->selectRaw("DATE_FORMAT(created_at, '%Y-%m') as ym, ROUND(AVG(score)) as total")
            ->groupBy('ym')
            ->pluck('total', 'ym');

        return $this->mapMonthly($rows);
    }

    private function hoursTrend(int $userId): array
    {
        $rows = ContentUser::join('content', 'content.id', '=', 'content_users.content_id')
            ->where('content_users.user_id', $userId)
            ->whereNull('content.deleted_at')
            ->where('content_users.updated_at', '>=', now()->subMonths(11)->startOfMonth())
            ->selectRaw("DATE_FORMAT(content_users.updated_at, '%Y-%m') as ym, ROUND(SUM(" . self::HOURS_EXPR . "), 1) as total")
            ->groupBy('ym')
            ->pluck('total', 'ym');

        return $this->mapMonthly($rows, true);
    }

    private function weeklyHeatmap(int $userId): array
    {
        // 1 = Minggu ... 7 = Sabtu (DAYOFWEEK MySQL).
        $rows = ContentUser::where('user_id', $userId)
            ->selectRaw('DAYOFWEEK(updated_at) as dow, COUNT(*) as total')
            ->groupBy('dow')
            ->pluck('total', 'dow');

        $labels = ['Sen', 'Sel', 'Rab', 'Kam', 'Jum', 'Sab', 'Min'];
        $map    = [2, 3, 4, 5, 6, 7, 1]; // urutan Senin..Minggu -> DAYOFWEEK

        $series = [];
        foreach ($map as $dow) {
            $series[] = (int) ($rows[$dow] ?? 0);
        }

        return ['labels' => $labels, 'series' => $series];
    }

    /*
    |--------------------------------------------------------------------------
    | Team builders
    |--------------------------------------------------------------------------
    */
    private function teamSummary(\Closure $idsFactory): array
    {
        $progressContent = ContentUser::whereIn('content_users.user_id', $this->ids($idsFactory));

        $avgProgress = (int) round((clone $progressContent)->avg('content_users.progress') ?? 0);
        $total       = (clone $progressContent)->count();
        $finished    = (clone $progressContent)->where('content_users.finish', '1')->count();

        $assessmentAvg = (int) round(
            AssesmentResult::whereIn('assesment_results.user_id', $this->ids($idsFactory))
                ->where('final', 1)
                ->avg('score') ?? 0
        );

        $hours = (float) (ContentUser::join('content', 'content.id', '=', 'content_users.content_id')
            ->whereIn('content_users.user_id', $this->ids($idsFactory))
            ->whereNull('content.deleted_at')
            ->selectRaw('ROUND(SUM(' . self::HOURS_EXPR . '), 1) as total')
            ->value('total') ?? 0);

        return [
            'average_progress' => $avgProgress,
            'assessment_avg'   => $assessmentAvg,
            'learning_hours'   => $hours,
            'completion_rate'  => $total > 0 ? (int) round(($finished / $total) * 100) : 0,
        ];
    }

    private function teamProgressDistribution(\Closure $idsFactory): array
    {
        $perUser = ContentUser::whereIn('content_users.user_id', $this->ids($idsFactory))
            ->groupBy('content_users.user_id')
            ->selectRaw('content_users.user_id, AVG(content_users.progress) as ap')
            ->pluck('ap');

        $buckets = ['0-20' => 0, '21-40' => 0, '41-60' => 0, '61-80' => 0, '81-100' => 0];

        foreach ($perUser as $ap) {
            $ap = (int) round($ap);
            if ($ap <= 20)      $buckets['0-20']++;
            elseif ($ap <= 40)  $buckets['21-40']++;
            elseif ($ap <= 60)  $buckets['41-60']++;
            elseif ($ap <= 80)  $buckets['61-80']++;
            else                $buckets['81-100']++;
        }

        return [
            'labels' => array_keys($buckets),
            'series' => array_values($buckets),
        ];
    }

    private function teamTopLearners(\Closure $idsFactory): array
    {
        $rows = $idsFactory()
            ->select('users.id', 'users.name', 'users.id_emp')
            ->selectSub(
                ContentUser::selectRaw('COUNT(*)')
                    ->whereColumn('content_users.user_id', 'users.id')
                    ->where('finish', '1'),
                'finished'
            )
            ->selectSub(
                ContentUser::selectRaw('COALESCE(ROUND(AVG(progress)), 0)')
                    ->whereColumn('content_users.user_id', 'users.id'),
                'avg_progress'
            )
            ->selectSub(
                AssesmentResult::selectRaw('COALESCE(ROUND(AVG(score)), 0)')
                    ->whereColumn('assesment_results.user_id', 'users.id')
                    ->where('final', 1),
                'avg_score'
            )
            ->orderByDesc('finished')
            ->orderByDesc('avg_progress')
            ->limit(10)
            ->get();

        return $rows->map(fn ($u) => [
            'name'         => $u->name ?: '-',
            'id_emp'       => $u->id_emp ?: '-',
            'finished'     => (int) $u->finished,
            'avg_progress' => (int) $u->avg_progress,
            'avg_score'    => (int) $u->avg_score,
        ])->all();
    }

    private function teamMonthlyActivity(\Closure $idsFactory): array
    {
        $rows = ContentUser::whereIn('content_users.user_id', $this->ids($idsFactory))
            ->where('content_users.updated_at', '>=', now()->subMonths(11)->startOfMonth())
            ->selectRaw("DATE_FORMAT(content_users.updated_at, '%Y-%m') as ym, COUNT(*) as total")
            ->groupBy('ym')
            ->pluck('total', 'ym');

        return $this->mapMonthly($rows);
    }

    /*
    |--------------------------------------------------------------------------
    | Group builders (multi-user versions of the personal builders above)
    |--------------------------------------------------------------------------
    */
    private function categoryDistributionForIds(\Closure $idsFactory): array
    {
        $rows = DB::table('content_users as cu')
            ->join('content as content', 'content.id', '=', 'cu.content_id')
            ->leftJoin('classification as cl', 'cl.id', '=', 'content.classification_id')
            ->whereIn('cu.user_id', $this->ids($idsFactory)->toBase())
            ->whereNull('content.deleted_at')
            ->groupBy('label')
            ->selectRaw("COALESCE(cl.classification_name, 'Lainnya') as label, COUNT(*) as total")
            ->orderByDesc('total')
            ->get();

        return [
            'labels' => $rows->pluck('label')->all(),
            'series' => $rows->pluck('total')->map(fn ($v) => (int) $v)->all(),
        ];
    }

    private function topCategoriesForIds(\Closure $idsFactory): array
    {
        $rows = DB::table('content_users as cu')
            ->join('content as content', 'content.id', '=', 'cu.content_id')
            ->leftJoin('classification as cl', 'cl.id', '=', 'content.classification_id')
            ->whereIn('cu.user_id', $this->ids($idsFactory)->toBase())
            ->whereNull('content.deleted_at')
            ->groupBy('label')
            ->selectRaw("COALESCE(cl.classification_name, 'Lainnya') as label, COUNT(*) as total")
            ->orderByDesc('total')
            ->limit(5)
            ->get();

        return [
            'labels' => $rows->pluck('label')->all(),
            'series' => $rows->pluck('total')->map(fn ($v) => (int) $v)->all(),
        ];
    }

    private function typeDistributionForIds(\Closure $idsFactory): array
    {
        $rows = DB::table('content_users as cu')
            ->join('content as content', 'content.id', '=', 'cu.content_id')
            ->whereIn('cu.user_id', $this->ids($idsFactory)->toBase())
            ->whereNull('content.deleted_at')
            ->groupBy('label')
            ->selectRaw("COALESCE(NULLIF(content.type, ''), 'Lainnya') as label, COUNT(*) as total")
            ->orderByDesc('total')
            ->get();

        return [
            'labels' => $rows->pluck('label')->all(),
            'series' => $rows->pluck('total')->map(fn ($v) => (int) $v)->all(),
        ];
    }

    private function statusDistributionForIds(\Closure $idsFactory): array
    {
        $row = ContentUser::whereIn('user_id', $this->ids($idsFactory))
            ->selectRaw("
                SUM(CASE WHEN finish = '1' OR progress >= 100 THEN 1 ELSE 0 END) as completed,
                SUM(CASE WHEN finish = '0' AND progress BETWEEN 1 AND 99 THEN 1 ELSE 0 END) as ongoing,
                SUM(CASE WHEN finish = '0' AND progress = 0 THEN 1 ELSE 0 END) as not_started
            ")
            ->first();

        return [
            'labels' => ['Completed', 'Ongoing', 'Not Started'],
            'series' => [
                (int) ($row->completed ?? 0),
                (int) ($row->ongoing ?? 0),
                (int) ($row->not_started ?? 0),
            ],
        ];
    }

    private function monthlyActivityForIds(\Closure $idsFactory): array
    {
        $rows = ContentUser::whereIn('user_id', $this->ids($idsFactory))
            ->where('updated_at', '>=', now()->subMonths(11)->startOfMonth())
            ->selectRaw("DATE_FORMAT(updated_at, '%Y-%m') as ym, COUNT(*) as total")
            ->groupBy('ym')
            ->pluck('total', 'ym');

        return $this->mapMonthly($rows);
    }

    private function completionTrendForIds(\Closure $idsFactory): array
    {
        $rows = ContentUser::whereIn('user_id', $this->ids($idsFactory))
            ->where('finish', '1')
            ->where('updated_at', '>=', now()->subMonths(11)->startOfMonth())
            ->selectRaw("DATE_FORMAT(updated_at, '%Y-%m') as ym, COUNT(*) as total")
            ->groupBy('ym')
            ->pluck('total', 'ym');

        return $this->mapMonthly($rows);
    }

    private function assessmentTrendForIds(\Closure $idsFactory): array
    {
        $rows = AssesmentResult::whereIn('user_id', $this->ids($idsFactory))
            ->where('created_at', '>=', now()->subMonths(11)->startOfMonth())
            ->selectRaw("DATE_FORMAT(created_at, '%Y-%m') as ym, ROUND(AVG(score)) as total")
            ->groupBy('ym')
            ->pluck('total', 'ym');

        return $this->mapMonthly($rows);
    }

    private function hoursTrendForIds(\Closure $idsFactory): array
    {
        $rows = ContentUser::join('content', 'content.id', '=', 'content_users.content_id')
            ->whereIn('content_users.user_id', $this->ids($idsFactory))
            ->whereNull('content.deleted_at')
            ->where('content_users.updated_at', '>=', now()->subMonths(11)->startOfMonth())
            ->selectRaw("DATE_FORMAT(content_users.updated_at, '%Y-%m') as ym, ROUND(SUM(" . self::HOURS_EXPR . "), 1) as total")
            ->groupBy('ym')
            ->pluck('total', 'ym');

        return $this->mapMonthly($rows, true);
    }

    /*
    |--------------------------------------------------------------------------
    | Helpers
    |--------------------------------------------------------------------------
    */
    private function ids(\Closure $idsFactory): Builder
    {
        return $idsFactory()->select('users.id');
    }

    /**
     * Total jam belajar seorang user — dipakai juga oleh dashboard My Learning
     * dan monitoring (public agar tidak diduplikasi di controller).
     */
    public function userLearningHours(int $userId): float
    {
        return $this->learningHourService->getTotalLearningHours($userId);
    }

    /**
     * Dataset tunggal untuk Hero Home dan halaman lain yang memakai Hero Home.
     */
    public function heroSummary(int $userId): array
    {
        $contentUser = ContentUser::where('user_id', $userId)->get();

        return [
            'content_user' => $contentUser,
            'completedCourse' => $contentUser->where('finish', 1)->count(),
            'ongoingCourse' => $contentUser->where('finish', 0)->count(),
            'learningHours' => $this->userLearningHours($userId),
        ];
    }

    /**
     * Petakan hasil group-by-bulan ke deret 12 bulan kontinu.
     */
    private function mapMonthly($keyedRows, bool $float = false): array
    {
        $labels = [];
        $series = [];

        for ($i = 11; $i >= 0; $i--) {
            $month = Carbon::now()->subMonths($i);
            $key   = $month->format('Y-m');

            $labels[] = $month->format('M y');
            $value    = $keyedRows[$key] ?? 0;
            $series[] = $float ? (float) $value : (int) $value;
        }

        return ['labels' => $labels, 'series' => $series];
    }
}
