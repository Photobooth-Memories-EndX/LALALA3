<?php

namespace App\Services\Record;

use App\Http\Controllers\LearningManageController;
use App\Models\OfflineTraining;
use App\Models\OfflineTrainingUser;
use Illuminate\Database\Query\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Yajra\DataTables\Facades\DataTables;

class RecordAllCombinedService
{
    protected LearningManageController $learningManageController;

    public function __construct(LearningManageController $learningManageController)
    {
        $this->learningManageController = $learningManageController;
    }

    /**
     * Calculate combined KPI summary across Online and Offline records.
     */
    public function getSummary(): array
    {
        // 1. Online KPI & Learning Hours (canonical methods)
        $onlineKpi = $this->learningManageController->getKpiSummary();
        $onlineHours = $this->learningManageController->getTotalLearningHours();

        $onlineTotal = (int) ($onlineKpi['total_learning'] ?? 0);
        $onlineOngoing = (int) ($onlineKpi['ongoing'] ?? 0);
        $onlineFinished = (int) ($onlineKpi['finished'] ?? 0);

        // 2. Offline KPI & Learning Hours (from offline_training_users)
        $hasOfflineTables = Schema::hasTable('offline_trainings') && Schema::hasTable('offline_training_users');

        if ($hasOfflineTables) {
            $offlineTotal = (int) DB::table('offline_training_users as otu')
                ->join('offline_trainings as ot', 'ot.id', '=', 'otu.offline_training_id')
                ->whereNull('otu.deleted_at')
                ->whereNull('ot.deleted_at')
                ->count();

            $offlineOngoing = (int) DB::table('offline_training_users as otu')
                ->join('offline_trainings as ot', 'ot.id', '=', 'otu.offline_training_id')
                ->whereNull('otu.deleted_at')
                ->whereNull('ot.deleted_at')
                ->where('otu.progress', '>', 0)
                ->where('otu.progress', '<', 100)
                ->count();

            $offlineFinished = (int) DB::table('offline_training_users as otu')
                ->join('offline_trainings as ot', 'ot.id', '=', 'otu.offline_training_id')
                ->whereNull('otu.deleted_at')
                ->whereNull('ot.deleted_at')
                ->where('otu.progress', '>=', 100)
                ->count();

            // Strictly credited participant learning hours from offline_training_users!
            $offlineHours = (float) DB::table('offline_training_users as otu')
                ->join('offline_trainings as ot', 'ot.id', '=', 'otu.offline_training_id')
                ->whereNull('otu.deleted_at')
                ->whereNull('ot.deleted_at')
                ->sum('otu.learning_hours');
        } else {
            $offlineTotal = 0;
            $offlineOngoing = 0;
            $offlineFinished = 0;
            $offlineHours = 0.0;
        }

        // 3. Combined Metrics
        $totalLearning = $onlineTotal + $offlineTotal;
        $totalOngoing = $onlineOngoing + $offlineOngoing;
        $totalFinished = $onlineFinished + $offlineFinished;
        $totalLearningHours = round($onlineHours + $offlineHours, 2);
        $completionRate = $totalLearning > 0 ? round(($totalFinished / $totalLearning) * 100, 1) : 0.0;

        return [
            'total_learning'       => $totalLearning,
            'ongoing'              => $totalOngoing,
            'finished'             => $totalFinished,
            'total_learning_hours' => $totalLearningHours,
            'completion_rate'      => $completionRate,
            'online' => [
                'total_learning'       => $onlineTotal,
                'ongoing'              => $onlineOngoing,
                'finished'             => $onlineFinished,
                'total_learning_hours' => round($onlineHours, 2),
            ],
            'offline' => [
                'total_learning'       => $offlineTotal,
                'ongoing'              => $offlineOngoing,
                'finished'             => $offlineFinished,
                'total_learning_hours' => round($offlineHours, 2),
            ],
        ];
    }

    /**
     * Calculate combined Classification Summary table rows.
     */
    public function getClassificationSummary(): Collection
    {
        // 1. Online classifications
        $onlineClassifications = $this->learningManageController->getClassificationSummary();

        $onlineMap = [];
        foreach ($onlineClassifications as $oc) {
            $name = trim((string) ($oc->classification_name ?? ''));
            if ($name === '') {
                $name = 'Unclassified';
            }
            $key = strtolower($name);

            $onlineMap[$key] = [
                'name'           => $name,
                'total_learning' => (int) ($oc->total_learning ?? 0),
                'ongoing'        => (int) ($oc->ongoing_learning ?? 0),
                'finished'       => (int) ($oc->finished_learning ?? 0),
                'learning_hours' => (float) ($oc->learning_hours ?? 0),
            ];
        }

        // 2. Offline classifications
        $offlineMap = [];
        $hasOfflineTables = Schema::hasTable('offline_trainings') && Schema::hasTable('offline_training_users');

        if ($hasOfflineTables) {
            $offlineData = DB::table('offline_training_users as otu')
                ->join('offline_trainings as ot', 'ot.id', '=', 'otu.offline_training_id')
                ->whereNull('otu.deleted_at')
                ->whereNull('ot.deleted_at')
                ->select([
                    DB::raw("COALESCE(NULLIF(TRIM(ot.main_classification), ''), 'Unclassified') as class_name"),
                    DB::raw("COUNT(otu.id) as total_count"),
                    DB::raw("SUM(CASE WHEN otu.progress > 0 AND otu.progress < 100 THEN 1 ELSE 0 END) as ongoing_count"),
                    DB::raw("SUM(CASE WHEN otu.progress >= 100 THEN 1 ELSE 0 END) as finished_count"),
                    DB::raw("COALESCE(SUM(otu.learning_hours), 0) as total_hours"),
                ])
                ->groupBy(DB::raw("COALESCE(NULLIF(TRIM(ot.main_classification), ''), 'Unclassified')"))
                ->get();

            foreach ($offlineData as $od) {
                $name = trim((string) ($od->class_name ?? 'Unclassified'));
                if ($name === '') {
                    $name = 'Unclassified';
                }
                $key = strtolower($name);

                $offlineMap[$key] = [
                    'name'           => $name,
                    'total_learning' => (int) ($od->total_count ?? 0),
                    'ongoing'        => (int) ($od->ongoing_count ?? 0),
                    'finished'       => (int) ($od->finished_count ?? 0),
                    'learning_hours' => (float) ($od->total_hours ?? 0),
                ];
            }
        }

        // 3. Union & merge by classification key
        $allKeys = array_unique(array_merge(array_keys($onlineMap), array_keys($offlineMap)));
        sort($allKeys);

        $merged = [];
        $unclassifiedItem = null;

        foreach ($allKeys as $k) {
            $on = $onlineMap[$k] ?? ['name' => ucfirst($k), 'total_learning' => 0, 'ongoing' => 0, 'finished' => 0, 'learning_hours' => 0.0];
            $off = $offlineMap[$k] ?? ['name' => ucfirst($k), 'total_learning' => 0, 'ongoing' => 0, 'finished' => 0, 'learning_hours' => 0.0];

            $displayName = $on['name'] !== ucfirst($k) ? $on['name'] : $off['name'];
            $kpCount = $on['total_learning'];
            $nonKpCount = $off['total_learning'];
            $totalCount = $kpCount + $nonKpCount;
            $totHours = round($on['learning_hours'] + $off['learning_hours'], 2);
            $ongoingCount = $on['ongoing'] + $off['ongoing'];
            $finishedCount = $on['finished'] + $off['finished'];

            $row = [
                'classification_name' => $displayName,
                'kp_owl'              => $kpCount,
                'non_kp_owl'          => $nonKpCount,
                'total_learning'      => $totalCount,
                'learning_hours'      => $totHours,
                'ongoing'             => $ongoingCount,
                'finished'            => $finishedCount,
            ];

            if (strtolower($displayName) === 'unclassified') {
                $unclassifiedItem = $row;
            } else {
                $merged[] = $row;
            }
        }

        // Keep Unclassified at the end if present
        if ($unclassifiedItem !== null) {
            $merged[] = $unclassifiedItem;
        }

        // Add 1-based index 'no'
        $finalCollection = collect();
        foreach ($merged as $idx => $item) {
            $item['no'] = $idx + 1;
            $finalCollection->push((object) $item);
        }

        return $finalCollection;
    }

    /**
     * Get distinct filter options (classifications and years).
     */
    public function getFilterOptions(): array
    {
        $classifications = [];
        $years = [];

        // Online classifications
        $onlineClasses = DB::table('classification')
            ->whereNotNull('classification_name')
            ->where('classification_name', '!=', '')
            ->distinct()
            ->pluck('classification_name')
            ->toArray();
        $classifications = array_merge($classifications, $onlineClasses);

        // Offline classifications
        if (Schema::hasTable('offline_trainings')) {
            $offlineClasses = DB::table('offline_trainings')
                ->whereNotNull('main_classification')
                ->where('main_classification', '!=', '')
                ->whereNull('deleted_at')
                ->distinct()
                ->pluck('main_classification')
                ->toArray();
            $classifications = array_merge($classifications, $offlineClasses);
        }

        $classifications = array_values(array_unique(array_filter(array_map('trim', $classifications))));
        sort($classifications);

        // Online years
        $cuYears = DB::table('content_users')->selectRaw('DISTINCT YEAR(created_at) as yr')->pluck('yr')->toArray();
        $pcuYears = DB::table('playlist_content_users')->selectRaw('DISTINCT YEAR(created_at) as yr')->pluck('yr')->toArray();
        $years = array_merge($years, $cuYears, $pcuYears);

        // Offline years
        if (Schema::hasTable('offline_trainings')) {
            $offYears1 = DB::table('offline_trainings')->whereNull('deleted_at')->whereNotNull('start_date')->selectRaw('DISTINCT YEAR(start_date) as yr')->pluck('yr')->toArray();
            $offYears2 = DB::table('offline_trainings')->whereNull('deleted_at')->whereNotNull('finish_date')->selectRaw('DISTINCT YEAR(finish_date) as yr')->pluck('yr')->toArray();
            $years = array_merge($years, $offYears1, $offYears2);
        }

        $years = array_values(array_unique(array_filter($years)));
        rsort($years);

        return [
            'classifications' => $classifications,
            'years'           => $years,
        ];
    }

    /**
     * Check if request has active filters (other than defaults).
     */
    protected function hasActiveFilters(array $filters): bool
    {
        $source = strtolower(trim((string) ($filters['source'] ?? 'all')));
        if ($source !== '' && $source !== 'all') {
            return true;
        }

        $status = strtolower(trim((string) ($filters['status'] ?? 'all')));
        if ($status !== '' && $status !== 'all') {
            return true;
        }

        $employee = trim((string) ($filters['employee_id'] ?? ($filters['employee'] ?? '')));
        if ($employee !== '') {
            return true;
        }

        $title = trim((string) ($filters['learning_title'] ?? ($filters['title'] ?? '')));
        if ($title !== '') {
            return true;
        }

        $classification = trim((string) ($filters['classification'] ?? 'all'));
        if ($classification !== '' && $classification !== 'all') {
            return true;
        }

        $year = trim((string) ($filters['year'] ?? 'all'));
        if ($year !== '' && $year !== 'all') {
            return true;
        }

        $search = is_array($filters['search'] ?? null) ? ($filters['search']['value'] ?? '') : ($filters['search'] ?? '');
        if (trim((string) $search) !== '') {
            return true;
        }

        return false;
    }

    /**
     * Build unified query for All KP OWL DataTable.
     * Uses lean pushdown union without heavy text joins to maintain minimal memory footprint.
     */
    public function buildCombinedQuery(array $filters = []): Builder
    {
        $sourceParam = strtolower(trim((string) ($filters['source'] ?? 'all')));
        $includeOnline = in_array($sourceParam, ['all', 'online'], true);
        $includeOffline = in_array($sourceParam, ['all', 'offline'], true) && Schema::hasTable('offline_trainings') && Schema::hasTable('offline_training_users');

        $unions = [];
        if ($includeOnline) {
            $unions[] = $this->buildContentQuery($filters);
            $unions[] = $this->buildPlaylistQuery($filters);
        }

        if ($includeOffline) {
            $unions[] = $this->buildOfflineQuery($filters);
        }

        if (empty($unions)) {
            return DB::table('offline_trainings')
                ->whereRaw('1 = 0')
                ->select([
                    DB::raw('1 as source_id'),
                    DB::raw("'online' as source"),
                    DB::raw("'content' as source_type"),
                    DB::raw("'' as row_id"),
                    DB::raw("'' as employee_id"),
                    DB::raw("'' as employee_name"),
                    DB::raw("'' as grade"),
                    DB::raw("'' as job_position"),
                    DB::raw("'' as team"),
                    DB::raw("'' as department"),
                    DB::raw("'' as company"),
                    DB::raw("'' as learning_title"),
                    DB::raw("'' as learning_slug"),
                    DB::raw("'' as type"),
                    DB::raw("'' as classification"),
                    DB::raw("'' as category"),
                    DB::raw("0 as duration_seconds"),
                    DB::raw("0.0 as learning_hours"),
                    DB::raw("0 as progress"),
                    DB::raw("'' as status"),
                    DB::raw("'' as start_date"),
                    DB::raw("'' as finish_date"),
                    DB::raw("NOW() as raw_finish_date"),
                    DB::raw("NOW() as record_date"),
                ]);
        }

        $first = array_shift($unions);
        foreach ($unions as $u) {
            $first->unionAll($u);
        }

        // Join relational tables outside the union to keep union table tiny and fast
        return DB::query()
            ->fromSub($first, 'unified')
            ->leftJoin('users as u', 'u.id', '=', 'unified.user_id')
            ->leftJoin('content as c', 'c.id', '=', 'unified.content_id')
            ->leftJoin('classification as cl', 'cl.id', '=', 'c.classification_id')
            ->leftJoin('trainingplan as tp', 'tp.id', '=', 'unified.trainingplan_id')
            ->leftJoin('offline_training_users as otu', 'otu.id', '=', 'unified.offline_training_user_id')
            ->leftJoin('offline_trainings as ot', 'ot.id', '=', 'otu.offline_training_id')
            ->select([
                'unified.source_id',
                'unified.source',
                'unified.source_type',
                DB::raw("CASE
                    WHEN unified.source = 'offline' THEN CONCAT('offline:', unified.source_id)
                    WHEN unified.source_type = 'playlist' THEN CONCAT('online:playlist:', unified.source_id)
                    ELSE CONCAT('online:content:', unified.source_id)
                END as row_id"),
                DB::raw("COALESCE(otu.employee_id, u.id_emp, '') as employee_id"),
                DB::raw("COALESCE(otu.name, u.name, '') as employee_name"),
                DB::raw("COALESCE(otu.grade, '') as grade"),
                DB::raw("COALESCE(otu.job_position, '') as job_position"),
                DB::raw("COALESCE(otu.team, u.team, '') as team"),
                DB::raw("COALESCE(otu.department, u.department, '') as department"),
                DB::raw("COALESCE(otu.company, u.company_name, '') as company"),
                DB::raw("CASE
                    WHEN unified.source = 'offline' THEN COALESCE(ot.course_name, 'Offline Training')
                    WHEN unified.source_type = 'playlist' THEN COALESCE(tp.title, c.title, 'Playlist')
                    ELSE COALESCE(c.title, 'Untitled Content')
                END as learning_title"),
                DB::raw("COALESCE(c.slug, '') as learning_slug"),
                DB::raw("CASE
                    WHEN unified.source = 'offline' THEN COALESCE(NULLIF(TRIM(ot.type), ''), 'Offline Training')
                    WHEN unified.source_type = 'playlist' THEN 'Playlist'
                    ELSE COALESCE(c.type, 'Document')
                END as type"),
                DB::raw("CASE
                    WHEN unified.source = 'offline' THEN COALESCE(NULLIF(TRIM(ot.main_classification), ''), 'Unclassified')
                    ELSE COALESCE(NULLIF(TRIM(cl.classification_name), ''), 'Unclassified')
                END as classification"),
                DB::raw("CASE
                    WHEN unified.source = 'offline' THEN COALESCE(NULLIF(TRIM(ot.category), ''), 'General')
                    WHEN unified.source_type = 'playlist' THEN 'Playlist'
                    ELSE 'All Content'
                END as category"),
                DB::raw("CASE
                    WHEN unified.source = 'offline' THEN COALESCE(ot.duration_seconds, 0)
                    WHEN c.duration IS NULL OR TRIM(c.duration) = '' THEN 0
                    WHEN LENGTH(c.duration) - LENGTH(REPLACE(c.duration, ':', '')) = 2 THEN TIME_TO_SEC(c.duration)
                    ELSE TIME_TO_SEC(CONCAT('00:', c.duration))
                END as duration_seconds"),
                DB::raw("CASE
                    WHEN unified.source = 'offline' THEN COALESCE(ROUND(otu.learning_hours, 2), 0)
                    WHEN c.duration IS NULL OR TRIM(c.duration) = '' THEN 0
                    WHEN LENGTH(c.duration) - LENGTH(REPLACE(c.duration, ':', '')) = 2 THEN ROUND(TIME_TO_SEC(c.duration) / 3600, 2)
                    ELSE ROUND(TIME_TO_SEC(CONCAT('00:', c.duration)) / 3600, 2)
                END as learning_hours"),
                DB::raw("COALESCE(unified.progress, 0) as progress"),
                DB::raw("CASE
                    WHEN unified.finish = '1' OR unified.progress >= 100 THEN 'Finished'
                    WHEN unified.progress > 0 THEN 'Ongoing'
                    ELSE 'Not Started'
                END as status"),
                DB::raw("DATE_FORMAT(unified.created_at, '%Y-%m-%d') as start_date"),
                DB::raw("DATE_FORMAT(unified.finish_date, '%Y-%m-%d') as finish_date"),
                'unified.finish_date as raw_finish_date',
                'unified.created_at as record_date',
            ]);
    }

    /**
     * Build Online Content subquery.
     */
    protected function buildContentQuery(array $filters): Builder
    {
        $q = DB::table('content_users as cu')
            ->select([
                'cu.id as source_id',
                DB::raw("'online' as source"),
                DB::raw("'content' as source_type"),
                'cu.user_id',
                'cu.content_id',
                DB::raw('CAST(NULL AS UNSIGNED) as trainingplan_id'),
                DB::raw('CAST(NULL AS UNSIGNED) as offline_training_user_id'),
                'cu.progress',
                'cu.finish',
                'cu.created_at',
                DB::raw("COALESCE(cu.completed_at, cu.updated_at, cu.created_at) as finish_date"),
            ]);

        $this->applyOnlineSubqueryFilters($q, $filters, 'cu');

        return $q;
    }

    /**
     * Build Online Playlist subquery.
     */
    protected function buildPlaylistQuery(array $filters): Builder
    {
        $q = DB::table('playlist_content_users as pcu')
            ->select([
                'pcu.id as source_id',
                DB::raw("'online' as source"),
                DB::raw("'playlist' as source_type"),
                'pcu.user_id',
                'pcu.content_id',
                'pcu.trainingplan_id',
                DB::raw('CAST(NULL AS UNSIGNED) as offline_training_user_id'),
                'pcu.progress',
                'pcu.finish',
                'pcu.created_at',
                DB::raw("COALESCE(pcu.completed_at, pcu.updated_at, pcu.created_at) as finish_date"),
            ]);

        $this->applyOnlineSubqueryFilters($q, $filters, 'pcu');

        return $q;
    }

    /**
     * Apply pushdown filters to Online subqueries using index lookups.
     */
    protected function applyOnlineSubqueryFilters(Builder $q, array $filters, string $recordAlias): void
    {
        // 1. Status Filter
        $status = strtolower(trim((string) ($filters['status'] ?? 'all')));
        if ($status !== '' && $status !== 'all') {
            if ($status === 'finished' || $status === 'finish') {
                $q->where("{$recordAlias}.finish", '1');
            } elseif ($status === 'ongoing') {
                $q->where("{$recordAlias}.finish", '0')->where("{$recordAlias}.progress", '>', 0);
            } elseif ($status === 'not_started') {
                $q->where("{$recordAlias}.finish", '0')
                    ->where(function ($sub) use ($recordAlias) {
                        $sub->where("{$recordAlias}.progress", '<=', 0)
                            ->orWhereNull("{$recordAlias}.progress");
                    });
            }
        }

        // 2. Employee Filter (ID or Name)
        $employee = trim((string) ($filters['employee_id'] ?? ($filters['employee'] ?? '')));
        if ($employee !== '') {
            $userIds = DB::table('users')
                ->where('id_emp', 'like', "%{$employee}%")
                ->orWhere('name', 'like', "%{$employee}%")
                ->pluck('id')
                ->toArray();
            $q->whereIn("{$recordAlias}.user_id", !empty($userIds) ? $userIds : [-1]);
        }

        // 3. Learning Title Filter
        $title = trim((string) ($filters['learning_title'] ?? ($filters['title'] ?? '')));
        if ($title !== '') {
            $contentIds = DB::table('content')->where('title', 'like', "%{$title}%")->pluck('id')->toArray();
            $tpIds = DB::table('trainingplan')->where('title', 'like', "%{$title}%")->pluck('id')->toArray();

            if ($recordAlias === 'cu') {
                $q->whereIn("{$recordAlias}.content_id", !empty($contentIds) ? $contentIds : [-1]);
            } else {
                $q->where(function ($sub) use ($contentIds, $tpIds, $recordAlias) {
                    $matched = false;
                    if (!empty($contentIds)) {
                        $sub->whereIn("{$recordAlias}.content_id", $contentIds);
                        $matched = true;
                    }
                    if (!empty($tpIds)) {
                        if ($matched) {
                            $sub->orWhereIn("{$recordAlias}.trainingplan_id", $tpIds);
                        } else {
                            $sub->whereIn("{$recordAlias}.trainingplan_id", $tpIds);
                            $matched = true;
                        }
                    }
                    if (!$matched) {
                        $sub->whereRaw('1 = 0');
                    }
                });
            }
        }

        // 4. Classification Filter
        $classification = trim((string) ($filters['classification'] ?? 'all'));
        if ($classification !== '' && $classification !== 'all') {
            if (strtolower($classification) === 'unclassified') {
                $classContentIds = DB::table('content')
                    ->leftJoin('classification', 'classification.id', '=', 'content.classification_id')
                    ->where(function ($sub) {
                        $sub->whereNull('classification.classification_name')
                            ->orWhere('classification.classification_name', '');
                    })
                    ->pluck('content.id')
                    ->toArray();
            } else {
                $classContentIds = DB::table('content')
                    ->join('classification', 'classification.id', '=', 'content.classification_id')
                    ->where('classification.classification_name', $classification)
                    ->pluck('content.id')
                    ->toArray();
            }
            $q->whereIn("{$recordAlias}.content_id", !empty($classContentIds) ? $classContentIds : [-1]);
        }

        // 5. Year Filter
        $year = trim((string) ($filters['year'] ?? 'all'));
        if ($year !== '' && $year !== 'all' && is_numeric($year)) {
            $q->where(function ($sub) use ($recordAlias, $year) {
                $sub->whereYear("{$recordAlias}.created_at", (int) $year)
                    ->orWhereYear("{$recordAlias}.updated_at", (int) $year);
            });
        }

        // 6. General DataTables Search (when employee & title are not specified)
        $search = trim((string) (is_array($filters['search'] ?? null) ? ($filters['search']['value'] ?? '') : ($filters['search'] ?? '')));
        if ($search !== '' && $employee === '' && $title === '') {
            $searchUserIds = DB::table('users')
                ->where('name', 'like', "%{$search}%")
                ->orWhere('id_emp', 'like', "%{$search}%")
                ->pluck('id')
                ->toArray();

            $searchContentIds = DB::table('content')
                ->leftJoin('classification', 'classification.id', '=', 'content.classification_id')
                ->where('content.title', 'like', "%{$search}%")
                ->orWhere('classification.classification_name', 'like', "%{$search}%")
                ->pluck('content.id')
                ->toArray();

            $searchTpIds = DB::table('trainingplan')
                ->where('title', 'like', "%{$search}%")
                ->pluck('id')
                ->toArray();

            $q->where(function ($sub) use ($searchUserIds, $searchContentIds, $searchTpIds, $recordAlias) {
                $matched = false;
                if (!empty($searchUserIds)) {
                    $sub->whereIn("{$recordAlias}.user_id", $searchUserIds);
                    $matched = true;
                }
                if (!empty($searchContentIds)) {
                    if ($matched) {
                        $sub->orWhereIn("{$recordAlias}.content_id", $searchContentIds);
                    } else {
                        $sub->whereIn("{$recordAlias}.content_id", $searchContentIds);
                        $matched = true;
                    }
                }
                if ($recordAlias === 'pcu' && !empty($searchTpIds)) {
                    if ($matched) {
                        $sub->orWhereIn("{$recordAlias}.trainingplan_id", $searchTpIds);
                    } else {
                        $sub->whereIn("{$recordAlias}.trainingplan_id", $searchTpIds);
                        $matched = true;
                    }
                }
                if (!$matched) {
                    $sub->whereRaw('1 = 0');
                }
            });
        }
    }

    /**
     * Build Offline subquery from offline_training_users JOIN offline_trainings.
     */
    protected function buildOfflineQuery(array $filters): Builder
    {
        $q = DB::table('offline_training_users as otu')
            ->join('offline_trainings as ot', 'ot.id', '=', 'otu.offline_training_id')
            ->whereNull('otu.deleted_at')
            ->whereNull('ot.deleted_at')
            ->select([
                'otu.id as source_id',
                DB::raw("'offline' as source"),
                DB::raw("'offline_training' as source_type"),
                'otu.user_id',
                DB::raw('CAST(NULL AS UNSIGNED) as content_id'),
                DB::raw('CAST(NULL AS UNSIGNED) as trainingplan_id'),
                'otu.id as offline_training_user_id',
                'otu.progress',
                DB::raw("CASE WHEN otu.progress >= 100 THEN '1' ELSE '0' END as finish"),
                'otu.created_at',
                DB::raw("COALESCE(ot.finish_date, ot.start_date, otu.created_at) as finish_date"),
            ]);

        // Status filter
        $status = strtolower(trim((string) ($filters['status'] ?? 'all')));
        if ($status !== '' && $status !== 'all') {
            if ($status === 'finished' || $status === 'finish') {
                $q->where('otu.progress', '>=', 100);
            } elseif ($status === 'ongoing') {
                $q->where('otu.progress', '>', 0)->where('otu.progress', '<', 100);
            } elseif ($status === 'not_started') {
                $q->where(function ($sub) {
                    $sub->where('otu.progress', '<=', 0)
                        ->orWhereNull('otu.progress');
                });
            }
        }

        // Employee filter
        $employee = trim((string) ($filters['employee_id'] ?? ($filters['employee'] ?? '')));
        if ($employee !== '') {
            $userMatchedIds = DB::table('users')
                ->where('name', 'like', "%{$employee}%")
                ->orWhere('id_emp', 'like', "%{$employee}%")
                ->pluck('id')
                ->toArray();

            $q->where(function ($sub) use ($employee, $userMatchedIds) {
                $sub->where('otu.employee_id', 'like', "%{$employee}%")
                    ->orWhere('otu.name', 'like', "%{$employee}%");
                if (!empty($userMatchedIds)) {
                    $sub->orWhereIn('otu.user_id', $userMatchedIds);
                }
            });
        }

        // Learning Title filter
        $title = trim((string) ($filters['learning_title'] ?? ($filters['title'] ?? '')));
        if ($title !== '') {
            $q->where('ot.course_name', 'like', "%{$title}%");
        }

        // Classification filter
        $classification = trim((string) ($filters['classification'] ?? 'all'));
        if ($classification !== '' && $classification !== 'all') {
            if (strtolower($classification) === 'unclassified') {
                $q->where(function ($sub) {
                    $sub->whereNull('ot.main_classification')
                        ->orWhere('ot.main_classification', '');
                });
            } else {
                $q->where('ot.main_classification', $classification);
            }
        }

        // Year filter
        $year = trim((string) ($filters['year'] ?? 'all'));
        if ($year !== '' && $year !== 'all' && is_numeric($year)) {
            $q->where(function ($sub) use ($year) {
                $sub->whereYear('ot.finish_date', (int) $year)
                    ->orWhereYear('ot.start_date', (int) $year)
                    ->orWhereYear('otu.created_at', (int) $year);
            });
        }

        // General Search
        $search = trim((string) (is_array($filters['search'] ?? null) ? ($filters['search']['value'] ?? '') : ($filters['search'] ?? '')));
        if ($search !== '' && $employee === '' && $title === '') {
            $q->where(function ($sub) use ($search) {
                $sub->where('otu.employee_id', 'like', "%{$search}%")
                    ->orWhere('otu.name', 'like', "%{$search}%")
                    ->orWhere('ot.course_name', 'like', "%{$search}%")
                    ->orWhere('ot.main_classification', 'like', "%{$search}%");
            });
        }

        return $q;
    }

    /**
     * Process and return DataTables server-side response.
     */
    public function datatable(Request $request): JsonResponse
    {
        $length = (int) ($request->input('length') ?: ($request->input('entries') ?: 25));
        if ($length <= 0) {
            $length = 25;
        }
        $request->merge(['length' => $length]);
        if (!$request->has('start')) {
            $request->merge(['start' => 0]);
        }
        app()->instance('request', $request);
        app()->forgetInstance('datatables.request');

        $filters = $request->all();
        $query = $this->buildCombinedQuery($filters);

        // Sorting: default is latest finish_date / record_date
        $hasCustomSort = $request->filled('order');
        if (!$hasCustomSort) {
            $query->orderBy('raw_finish_date', 'desc')
                ->orderBy('record_date', 'desc');
        }

        $hasFilters = $this->hasActiveFilters($filters);
        $hasOffline = Schema::hasTable('offline_trainings') && Schema::hasTable('offline_training_users');

        $onlineCount = (int) (DB::table('content_users')->count() + DB::table('playlist_content_users')->count());
        $offlineCount = $hasOffline ? (int) DB::table('offline_training_users')->whereNull('deleted_at')->count() : 0;
        $totalAll = $onlineCount + $offlineCount;

        $sourceParam = strtolower(trim((string) ($filters['source'] ?? 'all')));
        if ($sourceParam === 'online') {
            $sourceTotal = $onlineCount;
        } elseif ($sourceParam === 'offline') {
            $sourceTotal = $offlineCount;
        } else {
            $sourceTotal = $totalAll;
        }

        $dataTable = DataTables::of($query)
            ->setTotalRecords($sourceTotal);

        if ($hasFilters) {
            $dataTable->setFilteredRecords($query->count());
        } else {
            $dataTable->setFilteredRecords($sourceTotal);
        }

        return $dataTable
            ->addIndexColumn()
            ->setRowId('row_id')
            ->addColumn('source_badge', function ($row) {
                if ($row->source === 'online') {
                    return '<span class="badge" style="background-color: #e0f2fe; color: #0284c7; font-weight: 600; font-size: 0.725rem; border-radius: 6px; padding: 3px 8px;">KP OWL</span>';
                }
                return '<span class="badge" style="background-color: #fef3c7; color: #d97706; font-weight: 600; font-size: 0.725rem; border-radius: 6px; padding: 3px 8px;">Non KP OWL</span>';
            })
            ->addColumn('status_badge', function ($row) {
                $st = $row->status ?? 'Not Started';
                if ($st === 'Finished') {
                    return '<span class="badge bg-success-lt text-success fw-bold" style="font-size: 0.725rem;">Finished</span>';
                } elseif ($st === 'Ongoing') {
                    return '<span class="badge bg-warning-lt text-warning fw-bold" style="font-size: 0.725rem;">Ongoing</span>';
                }
                return '<span class="badge bg-secondary-lt text-secondary fw-bold" style="font-size: 0.725rem;">Not Started</span>';
            })
            ->addColumn('employee', function ($row) {
                $name = e($row->employee_name ?: '-');
                $sub = e($row->department ?: ($row->team ?: ''));
                return '<div class="fw-bold text-dark">' . $name . '</div>' . ($sub ? '<div class="text-muted extra-small" style="font-size:0.75rem;">' . $sub . '</div>' : '');
            })
            ->addColumn('learning_hours_formatted', function ($row) {
                return number_format((float) ($row->learning_hours ?? 0), 2);
            })
            ->addColumn('progress_bar', function ($row) {
                $progress = max(0, min(100, round((float) ($row->progress ?? 0))));
                return '<div class="d-flex align-items-center gap-2" style="min-width: 90px;">'
                    . '<div class="progress progress-sm flex-fill" style="height: 6px;">'
                    . '<div class="progress-bar bg-primary" style="width: ' . $progress . '%;"></div>'
                    . '</div>'
                    . '<span class="extra-small text-muted" style="font-size: 0.75rem;">' . $progress . '%</span>'
                    . '</div>';
            })
            ->addColumn('finish_date_formatted', function ($row) {
                $d = $row->finish_date ?? null;
                return $d ? date('d M Y', strtotime($d)) : '-';
            })
            ->addColumn('action', function ($row) {
                if ($row->source === 'offline') {
                    return '<button type="button" class="btn btn-sm btn-outline-primary rounded-pill btn-view-offline-detail px-3" data-id="' . $row->source_id . '">'
                        . '<i class="ti ti-eye me-1"></i> Detail'
                        . '</button>';
                }
                if (!empty($row->learning_slug)) {
                    return '<a href="' . route('learning.manage.content', $row->learning_slug) . '" class="btn btn-sm btn-outline-primary rounded-pill px-3">'
                        . '<i class="ti ti-eye me-1"></i> View'
                        . '</a>';
                }
                return '<span class="text-muted small">-</span>';
            })
            ->rawColumns([
                'source_badge',
                'status_badge',
                'employee',
                'progress_bar',
                'action',
            ])
            ->make(true);
    }
}
