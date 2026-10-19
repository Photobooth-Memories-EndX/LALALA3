<?php

namespace App\Services\Record;

use App\Http\Controllers\LearningManageController;
use App\Http\Controllers\Manage\Record\RecordAllController;
use App\Models\OfflineTraining;
use App\Models\OfflineTrainingUser;
use App\Models\User;
use Illuminate\Database\Query\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
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
            $baseOfflineUserQuery = DB::table('offline_training_users as otu')
                ->join('offline_trainings as ot', 'ot.id', '=', 'otu.offline_training_id')
                ->whereNull('otu.deleted_at')
                ->whereNull('ot.deleted_at');
            OfflineTraining::applyRecordScope($baseOfflineUserQuery, 'ot');

            $offlineTotal = (int) (clone $baseOfflineUserQuery)->count();

            $offlineOngoing = (int) (clone $baseOfflineUserQuery)
                ->where('otu.progress', '>', 0)
                ->where('otu.progress', '<', 100)
                ->count();

            $offlineFinished = (int) (clone $baseOfflineUserQuery)
                ->where('otu.progress', '>=', 100)
                ->count();

            // Strictly credited participant learning hours from offline_training_users!
            $offlineHours = (float) (clone $baseOfflineUserQuery)
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
            $offlineQuery = DB::table('offline_training_users as otu')
                ->join('offline_trainings as ot', 'ot.id', '=', 'otu.offline_training_id')
                ->whereNull('otu.deleted_at')
                ->whereNull('ot.deleted_at');
            OfflineTraining::applyRecordScope($offlineQuery, 'ot');

            $offlineData = $offlineQuery
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
                'slug'                => Str::slug($displayName) ?: 'unclassified',
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
            $offlineClassesQuery = DB::table('offline_trainings')
                ->whereNotNull('main_classification')
                ->where('main_classification', '!=', '')
                ->whereNull('deleted_at');
            OfflineTraining::applyRecordScope($offlineClassesQuery);
            $offlineClasses = $offlineClassesQuery
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
            $offYears1Query = DB::table('offline_trainings')->whereNull('deleted_at')->whereNotNull('start_date');
            OfflineTraining::applyRecordScope($offYears1Query);
            $offYears1 = $offYears1Query->selectRaw('DISTINCT YEAR(start_date) as yr')->pluck('yr')->toArray();

            $offYears2Query = DB::table('offline_trainings')->whereNull('deleted_at')->whereNotNull('finish_date');
            OfflineTraining::applyRecordScope($offYears2Query);
            $offYears2 = $offYears2Query->selectRaw('DISTINCT YEAR(finish_date) as yr')->pluck('yr')->toArray();
            $years = array_merge($years, $offYears1, $offYears2);
        }

        $years = array_values(array_unique(array_filter($years)));
        rsort($years);

        // Departments & Companies
        $userDeps = DB::table('users')->whereNotNull('department')->where('department', '!=', '')->distinct()->pluck('department')->toArray();
        $offlineDeps = Schema::hasTable('offline_training_users')
            ? DB::table('offline_training_users')->whereNotNull('department')->where('department', '!=', '')->distinct()->pluck('department')->toArray()
            : [];
        $departments = array_values(array_unique(array_filter(array_merge($userDeps, $offlineDeps))));
        sort($departments);

        $userComps = DB::table('users')->whereNotNull('company_name')->where('company_name', '!=', '')->distinct()->pluck('company_name')->toArray();
        $offlineComps = Schema::hasTable('offline_training_users')
            ? DB::table('offline_training_users')->whereNotNull('company')->where('company', '!=', '')->distinct()->pluck('company')->toArray()
            : [];
        $companies = array_values(array_unique(array_filter(array_merge($userComps, $offlineComps))));
        sort($companies);

        return [
            'classifications' => $classifications,
            'years'           => $years,
            'departments'     => $departments,
            'companies'       => $companies,
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

        $fromDate = trim((string) ($filters['from_date'] ?? ($filters['date_from'] ?? '')));
        if ($fromDate !== '') {
            return true;
        }

        $toDate = trim((string) ($filters['to_date'] ?? ($filters['date_to'] ?? '')));
        if ($toDate !== '') {
            return true;
        }

        $progressRange = trim((string) ($filters['progress_range'] ?? 'all'));
        if ($progressRange !== '' && $progressRange !== 'all') {
            return true;
        }

        $hoursRange = trim((string) ($filters['learning_hours_range'] ?? ($filters['learning_hours'] ?? ($filters['hours_range'] ?? 'all'))));
        if ($hoursRange !== '' && $hoursRange !== 'all') {
            return true;
        }

        $department = trim((string) ($filters['department'] ?? 'all'));
        if ($department !== '' && $department !== 'all') {
            return true;
        }

        $company = trim((string) ($filters['company'] ?? 'all'));
        if ($company !== '' && $company !== 'all') {
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
                    DB::raw('CAST(NULL AS UNSIGNED) as content_id'),
                    DB::raw("'' as content_title"),
                    DB::raw('CAST(NULL AS UNSIGNED) as playlist_id'),
                    DB::raw("'' as playlist_slug"),
                    DB::raw("'' as playlist_title"),
                    DB::raw('CAST(NULL AS UNSIGNED) as offline_training_id'),
                    DB::raw("'' as row_id"),
                    DB::raw("'' as employee_id"),
                    DB::raw("'' as employee_name"),
                    DB::raw("CAST(NULL AS CHAR) as grade"),
                    DB::raw("CAST(NULL AS CHAR) as job_position"),
                    DB::raw("'' as team"),
                    DB::raw("'' as department"),
                    DB::raw("'' as company"),
                    DB::raw("'' as learning_title"),
                    DB::raw("CAST(NULL AS CHAR) as sub_course_name"),
                    DB::raw("'' as learning_slug"),
                    DB::raw("'' as type"),
                    DB::raw("'' as category"),
                    DB::raw("CAST(NULL AS CHAR) as level"),
                    DB::raw("CAST(NULL AS CHAR) as batch"),
                    DB::raw("'' as classification"),
                    DB::raw("CAST(NULL AS CHAR) as sub_classification"),
                    DB::raw("CAST(NULL AS UNSIGNED) as counted_corporate_kpi"),
                    DB::raw("CAST(NULL AS CHAR) as sub_corporate_kpi"),
                    DB::raw("CAST(NULL AS CHAR) as delivery_method"),
                    DB::raw("CAST(NULL AS CHAR) as sub_delivery_method"),
                    DB::raw("CAST(NULL AS CHAR) as provider"),
                    DB::raw("CAST(NULL AS CHAR) as sub_provider"),
                    DB::raw("CAST(NULL AS CHAR) as provider_name"),
                    DB::raw("CAST(NULL AS CHAR) as venue"),
                    DB::raw("CAST(NULL AS CHAR) as instructor"),
                    DB::raw("0 as duration_seconds"),
                    DB::raw("CAST(NULL AS DECIMAL(10,2)) as training_hours"),
                    DB::raw("CAST(NULL AS CHAR) as start_date"),
                    DB::raw("CAST(NULL AS CHAR) as finish_date"),
                    DB::raw("0.0 as learning_hours"),
                    DB::raw("0 as progress"),
                    DB::raw("'' as status"),
                    DB::raw("CAST(NULL AS DECIMAL(10,2)) as assessment_score"),
                    DB::raw("CAST(NULL AS CHAR) as certificate"),
                    DB::raw("CAST(NULL AS UNSIGNED) as attendance"),
                    DB::raw("CAST(NULL AS CHAR) as attendance_reason"),
                    DB::raw("CAST(NULL AS CHAR) as bond_status"),
                    DB::raw("CAST(NULL AS CHAR) as bond_end_date"),
                    DB::raw("CAST(NULL AS DECIMAL(15,2)) as bond_amount"),
                    DB::raw("CAST(NULL AS CHAR) as l1_program"),
                    DB::raw("CAST(NULL AS DECIMAL(10,2)) as l2_numerical"),
                    DB::raw("CAST(NULL AS CHAR) as l2_verbal"),
                    DB::raw("CAST(NULL AS DECIMAL(10,2)) as l3_numerical"),
                    DB::raw("CAST(NULL AS CHAR) as l3_verbal"),
                    DB::raw("CAST(NULL AS CHAR) as pic"),
                    DB::raw("CAST(NULL AS DATETIME) as raw_finish_date"),
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
                'unified.user_id as user_id',
                'c.id as content_id',
                'c.title as content_title',
                'c.slug as content_slug',
                'c.status as content_status',
                'tp.id as playlist_id',
                'tp.slug as playlist_slug',
                'tp.title as playlist_title',
                'ot.id as offline_training_id',
                DB::raw("CASE
                    WHEN unified.source = 'offline' THEN CONCAT('offline:', unified.source_id)
                    WHEN unified.source_type = 'playlist' THEN CONCAT('online:playlist:', unified.source_id)
                    ELSE CONCAT('online:content:', unified.source_id)
                END as row_id"),
                DB::raw("COALESCE(otu.employee_id, u.id_emp, '') as employee_id"),
                DB::raw("COALESCE(otu.name, u.name, '') as employee_name"),
                DB::raw("CASE WHEN unified.source = 'offline' THEN otu.grade ELSE NULL END as grade"),
                DB::raw("CASE WHEN unified.source = 'offline' THEN otu.job_position ELSE NULL END as job_position"),
                DB::raw("COALESCE(otu.team, u.team, '') as team"),
                DB::raw("COALESCE(otu.department, u.department, '') as department"),
                DB::raw("COALESCE(otu.company, u.company_name, '') as company"),
                DB::raw("CASE
                    WHEN unified.source = 'offline' THEN COALESCE(ot.course_name, 'Offline Training')
                    WHEN unified.source_type = 'playlist' THEN COALESCE(tp.title, c.title, 'Playlist')
                    ELSE COALESCE(c.title, 'Untitled Content')
                END as learning_title"),
                DB::raw("CASE
                    WHEN unified.source = 'offline' THEN ot.sub_course_name
                    WHEN LOWER(TRIM(COALESCE(c.status, 'non mandatory'))) = 'mandatory' THEN 'Mandatory'
                    ELSE 'Non-Mandatory'
                END as sub_course_name"),
                DB::raw("COALESCE(c.slug, '') as learning_slug"),
                DB::raw("CASE
                    WHEN unified.source = 'offline' THEN COALESCE(NULLIF(TRIM(ot.type), ''), 'Offline Training')
                    WHEN unified.source_type = 'playlist' THEN 'Playlist'
                    ELSE COALESCE(c.type, 'Document')
                END as type"),
                DB::raw("CASE
                    WHEN unified.source = 'offline' THEN COALESCE(NULLIF(TRIM(ot.category), ''), 'General')
                    WHEN unified.source_type = 'playlist' THEN 'Playlist'
                    ELSE 'All Content'
                END as category"),
                DB::raw("CASE WHEN unified.source = 'offline' THEN ot.level ELSE NULL END as level"),
                DB::raw("CASE WHEN unified.source = 'offline' THEN ot.batch ELSE NULL END as batch"),
                DB::raw("CASE
                    WHEN unified.source = 'offline' THEN COALESCE(NULLIF(TRIM(ot.main_classification), ''), 'Unclassified')
                    ELSE COALESCE(NULLIF(TRIM(cl.classification_name), ''), 'Unclassified')
                END as classification"),
                DB::raw("CASE WHEN unified.source = 'offline' THEN ot.sub_classification ELSE NULL END as sub_classification"),
                DB::raw("CASE WHEN unified.source = 'offline' THEN ot.counted_corporate_kpi ELSE NULL END as counted_corporate_kpi"),
                DB::raw("CASE WHEN unified.source = 'offline' THEN ot.sub_corporate_kpi ELSE NULL END as sub_corporate_kpi"),
                DB::raw("CASE WHEN unified.source = 'offline' THEN ot.delivery_method ELSE NULL END as delivery_method"),
                DB::raw("CASE WHEN unified.source = 'offline' THEN ot.sub_delivery_method ELSE NULL END as sub_delivery_method"),
                DB::raw("CASE WHEN unified.source = 'offline' THEN ot.provider ELSE NULL END as provider"),
                DB::raw("CASE WHEN unified.source = 'offline' THEN ot.sub_provider ELSE NULL END as sub_provider"),
                DB::raw("CASE WHEN unified.source = 'offline' THEN ot.provider_name ELSE NULL END as provider_name"),
                DB::raw("CASE WHEN unified.source = 'offline' THEN ot.venue ELSE NULL END as venue"),
                DB::raw("CASE
                    WHEN unified.source = 'offline' THEN ot.instructor
                    ELSE c.author
                END as instructor"),
                DB::raw("CASE
                    WHEN unified.source = 'offline' THEN COALESCE(ot.duration_seconds, 0)
                    WHEN c.duration IS NULL OR TRIM(c.duration) = '' THEN 0
                    WHEN LENGTH(c.duration) - LENGTH(REPLACE(c.duration, ':', '')) = 2 THEN TIME_TO_SEC(c.duration)
                    ELSE TIME_TO_SEC(CONCAT('00:', c.duration))
                END as duration_seconds"),
                DB::raw("CASE
                    WHEN unified.source = 'offline' THEN ot.learning_hours
                    WHEN unified.source_type = 'playlist' THEN tp.learning_hours
                    ELSE c.learning_hours
                END as training_hours"),
                DB::raw("CASE
                    WHEN unified.source = 'offline' THEN DATE_FORMAT(ot.start_date, '%Y-%m-%d')
                    ELSE NULL
                END as start_date"),
                DB::raw("DATE_FORMAT(unified.finish_date, '%Y-%m-%d') as finish_date"),
                DB::raw("COALESCE(ROUND(unified.learning_hours, 2), 0) as learning_hours"),
                DB::raw("COALESCE(unified.progress, 0) as progress"),
                DB::raw("CASE
                    WHEN unified.finish = '1' OR unified.progress >= 100 THEN 'Finished'
                    WHEN unified.progress > 0 THEN 'Ongoing'
                    ELSE 'Not Started'
                END as status"),
                DB::raw("CASE WHEN unified.source = 'offline' THEN otu.assessment_score ELSE NULL END as assessment_score"),
                DB::raw("CASE WHEN unified.source = 'offline' THEN otu.certificate ELSE NULL END as certificate"),
                DB::raw("CASE WHEN unified.source = 'offline' THEN otu.attendance ELSE NULL END as attendance"),
                DB::raw("CASE WHEN unified.source = 'offline' THEN otu.attendance_reason ELSE NULL END as attendance_reason"),
                DB::raw("CASE WHEN unified.source = 'offline' THEN otu.bond_status ELSE NULL END as bond_status"),
                DB::raw("CASE WHEN unified.source = 'offline' THEN DATE_FORMAT(otu.bond_end_date, '%Y-%m-%d') ELSE NULL END as bond_end_date"),
                DB::raw("CASE WHEN unified.source = 'offline' THEN otu.bond_amount ELSE NULL END as bond_amount"),
                DB::raw("CASE WHEN unified.source = 'offline' THEN otu.l1_program ELSE NULL END as l1_program"),
                DB::raw("CASE WHEN unified.source = 'offline' THEN otu.l2_numerical ELSE NULL END as l2_numerical"),
                DB::raw("CASE WHEN unified.source = 'offline' THEN otu.l2_verbal ELSE NULL END as l2_verbal"),
                DB::raw("CASE WHEN unified.source = 'offline' THEN otu.l3_numerical ELSE NULL END as l3_numerical"),
                DB::raw("CASE WHEN unified.source = 'offline' THEN otu.l3_verbal ELSE NULL END as l3_verbal"),
                DB::raw("CASE WHEN unified.source = 'offline' THEN otu.pic ELSE NULL END as pic"),
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
                DB::raw("COALESCE(cu.learning_hours, 0) as learning_hours"),
                'cu.created_at',
                DB::raw("CASE WHEN cu.finish = '1' OR cu.progress >= 100 THEN cu.completed_at ELSE NULL END as finish_date"),
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
                DB::raw("COALESCE(pcu.learning_hour, 0) as learning_hours"),
                'pcu.created_at',
                DB::raw("CASE WHEN pcu.finish = '1' OR pcu.progress >= 100 THEN pcu.completed_at ELSE NULL END as finish_date"),
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
                $q->where(function ($sub) use ($recordAlias) {
                    $sub->where("{$recordAlias}.finish", '1')
                        ->orWhere("{$recordAlias}.progress", '>=', 100);
                });
            } elseif ($status === 'ongoing') {
                $q->where("{$recordAlias}.finish", '0')
                    ->where("{$recordAlias}.progress", '>', 0)
                    ->where("{$recordAlias}.progress", '<', 100);
            } elseif ($status === 'not_started') {
                $q->where("{$recordAlias}.finish", '0')
                    ->where(function ($sub) use ($recordAlias) {
                        $sub->where("{$recordAlias}.progress", '<=', 0)
                            ->orWhereNull("{$recordAlias}.progress");
                    });
            }
        }

        // 2. Employee Filter (ID or Name or Exact Participant)
        if (!empty($filters['exact_participant'])) {
            $pUserId = $filters['exact_participant']['user_id'] ?? null;
            $pEmpId = $filters['exact_participant']['employee_id'] ?? null;
            $matchedUserIds = [];
            if ($pUserId) {
                $matchedUserIds[] = (int) $pUserId;
            }
            if (!empty($pEmpId)) {
                $uIds = DB::table('users')->where('id_emp', $pEmpId)->pluck('id')->toArray();
                $matchedUserIds = array_merge($matchedUserIds, $uIds);
            }
            $matchedUserIds = array_unique(array_filter($matchedUserIds));
            $q->whereIn("{$recordAlias}.user_id", !empty($matchedUserIds) ? $matchedUserIds : [-1]);
        } else {
            $employeeParam = $filters['employee_id'] ?? ($filters['employee'] ?? ($filters['employees'] ?? null));
            if (is_array($employeeParam) && !empty($employeeParam)) {
                $userIds = DB::table('users')
                    ->where(function ($sub) use ($employeeParam) {
                        $sub->whereIn('id_emp', $employeeParam)
                            ->orWhereIn('name', $employeeParam);
                    })
                    ->pluck('id')
                    ->toArray();
                $q->whereIn("{$recordAlias}.user_id", !empty($userIds) ? $userIds : [-1]);
            } elseif (is_string($employeeParam) && trim($employeeParam) !== '' && trim($employeeParam) !== 'all') {
                $employee = trim($employeeParam);
                $userIds = DB::table('users')
                    ->where('id_emp', 'like', "%{$employee}%")
                    ->orWhere('name', 'like', "%{$employee}%")
                    ->pluck('id')
                    ->toArray();
                $q->whereIn("{$recordAlias}.user_id", !empty($userIds) ? $userIds : [-1]);
            }
        }

        // 3. Learning Title Filter
        $rawTitle = $filters['learning_title'] ?? ($filters['title'] ?? '');
        $title = is_string($rawTitle) ? trim($rawTitle) : '';
        if (is_array($rawTitle)) {
            $titleList = array_values(array_filter(array_map('trim', $rawTitle), fn($t) => $t !== '' && strtolower($t) !== 'all'));
            if (!empty($titleList)) {
                $contentIds = DB::table('content')->whereIn('title', $titleList)->pluck('id')->toArray();
                $tpIds = DB::table('trainingplan')->whereIn('title', $titleList)->pluck('id')->toArray();

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
        } elseif ($title !== '' && strtolower($title) !== 'all') {
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
        $year = $filters['year'] ?? ($filters['years'] ?? 'all');
        if (is_array($year) && !empty($year)) {
            $yearInts = array_map('intval', array_filter($year, 'is_numeric'));
            if (!empty($yearInts)) {
                $q->where(function ($sub) use ($recordAlias, $yearInts) {
                    $sub->whereIn(DB::raw("YEAR({$recordAlias}.created_at)"), $yearInts)
                        ->orWhereIn(DB::raw("YEAR({$recordAlias}.completed_at)"), $yearInts)
                        ->orWhereIn(DB::raw("YEAR({$recordAlias}.updated_at)"), $yearInts);
                });
            }
        } elseif (is_string($year) && $year !== '' && $year !== 'all' && is_numeric($year)) {
            $q->where(function ($sub) use ($recordAlias, $year) {
                $sub->whereYear("{$recordAlias}.created_at", (int) $year)
                    ->orWhereYear("{$recordAlias}.completed_at", (int) $year)
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

        // 7. Finish Date Range Filter
        $fromDate = trim((string) ($filters['from_date'] ?? ($filters['date_from'] ?? '')));
        if ($fromDate !== '') {
            $q->where("{$recordAlias}.completed_at", '>=', "{$fromDate} 00:00:00");
        }
        $toDate = trim((string) ($filters['to_date'] ?? ($filters['date_to'] ?? '')));
        if ($toDate !== '') {
            $q->where("{$recordAlias}.completed_at", '<=', "{$toDate} 23:59:59");
        }

        // 8. Progress Range Filter
        $progressRange = trim((string) ($filters['progress_range'] ?? 'all'));
        if ($progressRange !== '' && $progressRange !== 'all') {
            if ($progressRange === '0') {
                $q->where(function ($sub) use ($recordAlias) {
                    $sub->where("{$recordAlias}.progress", '<=', 0)
                        ->orWhereNull("{$recordAlias}.progress");
                });
            } elseif ($progressRange === '1-25') {
                $q->where("{$recordAlias}.progress", '>=', 1)->where("{$recordAlias}.progress", '<=', 25);
            } elseif ($progressRange === '26-50') {
                $q->where("{$recordAlias}.progress", '>=', 26)->where("{$recordAlias}.progress", '<=', 50);
            } elseif ($progressRange === '51-75') {
                $q->where("{$recordAlias}.progress", '>=', 51)->where("{$recordAlias}.progress", '<=', 75);
            } elseif ($progressRange === '76-99') {
                $q->where("{$recordAlias}.progress", '>=', 76)->where("{$recordAlias}.progress", '<=', 99);
            } elseif ($progressRange === '100') {
                $q->where(function ($sub) use ($recordAlias) {
                    $sub->where("{$recordAlias}.progress", '>=', 100)
                        ->orWhere("{$recordAlias}.finish", '1');
                });
            }
        }

        // 9. Participant Learning Hours Filter
        $hoursRange = trim((string) ($filters['learning_hours_range'] ?? ($filters['learning_hours'] ?? ($filters['hours_range'] ?? 'all'))));
        if ($hoursRange !== '' && $hoursRange !== 'all') {
            $hCol = $recordAlias === 'pcu' ? 'pcu.learning_hour' : 'cu.learning_hours';
            if ($hoursRange === 'lt05') {
                $q->where($hCol, '<', 0.5);
            } elseif ($hoursRange === '05-1') {
                $q->where($hCol, '>=', 0.5)->where($hCol, '<', 1.0);
            } elseif ($hoursRange === '1-2') {
                $q->where($hCol, '>=', 1.0)->where($hCol, '<', 2.0);
            } elseif ($hoursRange === '2-4') {
                $q->where($hCol, '>=', 2.0)->where($hCol, '<', 4.0);
            } elseif ($hoursRange === 'gte4') {
                $q->where($hCol, '>=', 4.0);
            }
        }

        // 10. Department Filter
        $department = $filters['department'] ?? ($filters['departments'] ?? 'all');
        if (is_array($department) && !empty($department)) {
            $deptUserIds = DB::table('users')->whereIn('department', $department)->pluck('id')->toArray();
            $q->whereIn("{$recordAlias}.user_id", !empty($deptUserIds) ? $deptUserIds : [-1]);
        } elseif (is_string($department) && filled($department) && $department !== 'all') {
            $deptUserIds = DB::table('users')->where('department', trim($department))->pluck('id')->toArray();
            $q->whereIn("{$recordAlias}.user_id", !empty($deptUserIds) ? $deptUserIds : [-1]);
        }

        // 11. Company Filter
        $company = $filters['company'] ?? ($filters['companies'] ?? 'all');
        if (is_array($company) && !empty($company)) {
            $compUserIds = DB::table('users')->whereIn('company_name', $company)->pluck('id')->toArray();
            $q->whereIn("{$recordAlias}.user_id", !empty($compUserIds) ? $compUserIds : [-1]);
        } elseif (is_string($company) && filled($company) && $company !== 'all') {
            $compUserIds = DB::table('users')->where('company_name', trim($company))->pluck('id')->toArray();
            $q->whereIn("{$recordAlias}.user_id", !empty($compUserIds) ? $compUserIds : [-1]);
        }

        // 11c. Division Filter
        $division = $filters['division'] ?? ($filters['divisions'] ?? 'all');
        if (is_array($division) && !empty($division)) {
            $divUserIds = DB::table('users')->whereIn('division', $division)->pluck('id')->toArray();
            $q->whereIn("{$recordAlias}.user_id", !empty($divUserIds) ? $divUserIds : [-1]);
        } elseif (is_string($division) && filled($division) && $division !== 'all') {
            $divUserIds = DB::table('users')->where('division', trim($division))->pluck('id')->toArray();
            $q->whereIn("{$recordAlias}.user_id", !empty($divUserIds) ? $divUserIds : [-1]);
        }

        // 11d. Team Filter
        $team = $filters['team'] ?? ($filters['teams'] ?? 'all');
        if (is_array($team) && !empty($team)) {
            $teamUserIds = DB::table('users')->whereIn('team', $team)->pluck('id')->toArray();
            $q->whereIn("{$recordAlias}.user_id", !empty($teamUserIds) ? $teamUserIds : [-1]);
        } elseif (is_string($team) && filled($team) && $team !== 'all') {
            $teamUserIds = DB::table('users')->where('team', 'like', '%' . trim($team) . '%')->pluck('id')->toArray();
            $q->whereIn("{$recordAlias}.user_id", !empty($teamUserIds) ? $teamUserIds : [-1]);
        }

        // 12. Content Type Filter (Document / Video)
        $type = trim((string) ($filters['type'] ?? ($filters['content_type'] ?? 'all')));
        if ($type !== '' && $type !== 'all') {
            $typeStr = strtolower($type);
            $typeContentIds = DB::table('content')
                ->where(function ($sub) use ($typeStr) {
                    if ($typeStr === 'document') {
                        $sub->where('type', 'Document');
                    } elseif ($typeStr === 'video') {
                        $sub->whereIn('type', ['Video', 'YouTube']);
                    } else {
                        $sub->where('type', $typeStr);
                    }
                })
                ->pluck('id')
                ->toArray();
            $q->whereIn("{$recordAlias}.content_id", !empty($typeContentIds) ? $typeContentIds : [-1]);
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
            ->whereNull('ot.deleted_at');

        OfflineTraining::applyRecordScope($q, 'ot');

        $q->select([
                'otu.id as source_id',
                DB::raw("'offline' as source"),
                DB::raw("'offline_training' as source_type"),
                'otu.user_id',
                DB::raw('CAST(NULL AS UNSIGNED) as content_id'),
                DB::raw('CAST(NULL AS UNSIGNED) as trainingplan_id'),
                'otu.id as offline_training_user_id',
                'otu.progress',
                DB::raw("CASE WHEN otu.progress >= 100 THEN '1' ELSE '0' END as finish"),
                DB::raw("COALESCE(otu.learning_hours, 0) as learning_hours"),
                'otu.created_at',
                DB::raw("CASE WHEN otu.progress >= 100 THEN ot.finish_date ELSE NULL END as finish_date"),
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
        if (!empty($filters['exact_participant'])) {
            $pUserId = $filters['exact_participant']['user_id'] ?? null;
            $pEmpId = $filters['exact_participant']['employee_id'] ?? null;
            $pName = $filters['exact_participant']['name'] ?? null;
            $q->where(function ($sub) use ($pUserId, $pEmpId, $pName) {
                $matched = false;
                if (!empty($pEmpId)) {
                    $sub->where('otu.employee_id', $pEmpId);
                    $matched = true;
                }
                if (!empty($pUserId)) {
                    if ($matched) {
                        $sub->orWhere('otu.user_id', (int) $pUserId);
                    } else {
                        $sub->where('otu.user_id', (int) $pUserId);
                        $matched = true;
                    }
                }
                if (!$matched && !empty($pName)) {
                    $sub->where('otu.name', $pName);
                }
            });
        } else {
            $employeeParam = $filters['employee_id'] ?? ($filters['employee'] ?? ($filters['employees'] ?? null));
            $employee = is_string($employeeParam) ? trim($employeeParam) : '';
            if (is_array($employeeParam) && !empty($employeeParam)) {
                $q->where(function ($sub) use ($employeeParam) {
                    $sub->whereIn('otu.employee_id', $employeeParam)
                        ->orWhereIn('otu.name', $employeeParam);
                });
            } elseif ($employee !== '' && $employee !== 'all') {
                $q->where(function ($sub) use ($employee) {
                    $sub->where('otu.employee_id', 'like', "%{$employee}%")
                        ->orWhere('otu.name', 'like', "%{$employee}%");
                });
            }
        }

        // Learning Title filter
        $rawTitle = $filters['learning_title'] ?? ($filters['title'] ?? '');
        $title = is_string($rawTitle) ? trim($rawTitle) : '';
        if (is_array($rawTitle)) {
            $titleList = array_values(array_filter(array_map('trim', $rawTitle), fn($t) => $t !== '' && strtolower($t) !== 'all'));
            if (!empty($titleList)) {
                $q->whereIn('ot.course_name', $titleList);
            }
        } elseif ($title !== '' && strtolower($title) !== 'all') {
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
        $year = $filters['year'] ?? ($filters['years'] ?? 'all');
        if (is_array($year) && !empty($year)) {
            $yearInts = array_map('intval', array_filter($year, 'is_numeric'));
            if (!empty($yearInts)) {
                $q->where(function ($sub) use ($yearInts) {
                    $sub->whereIn(DB::raw('YEAR(ot.finish_date)'), $yearInts)
                        ->orWhereIn(DB::raw('YEAR(ot.start_date)'), $yearInts)
                        ->orWhereIn(DB::raw('YEAR(otu.created_at)'), $yearInts);
                });
            }
        } elseif (is_string($year) && $year !== '' && $year !== 'all' && is_numeric($year)) {
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

        // 5. Finish Date Range Filter
        $fromDate = trim((string) ($filters['from_date'] ?? ($filters['date_from'] ?? '')));
        if ($fromDate !== '') {
            $q->whereDate('ot.finish_date', '>=', $fromDate);
        }
        $toDate = trim((string) ($filters['to_date'] ?? ($filters['date_to'] ?? '')));
        if ($toDate !== '') {
            $q->whereDate('ot.finish_date', '<=', $toDate);
        }

        // 6. Progress Range Filter
        $progressRange = trim((string) ($filters['progress_range'] ?? 'all'));
        if ($progressRange !== '' && $progressRange !== 'all') {
            if ($progressRange === '0') {
                $q->where(function ($sub) {
                    $sub->where('otu.progress', '<=', 0)
                        ->orWhereNull('otu.progress');
                });
            } elseif ($progressRange === '1-25') {
                $q->where('otu.progress', '>=', 1)->where('otu.progress', '<=', 25);
            } elseif ($progressRange === '26-50') {
                $q->where('otu.progress', '>=', 26)->where('otu.progress', '<=', 50);
            } elseif ($progressRange === '51-75') {
                $q->where('otu.progress', '>=', 51)->where('otu.progress', '<=', 75);
            } elseif ($progressRange === '76-99') {
                $q->where('otu.progress', '>=', 76)->where('otu.progress', '<=', 99);
            } elseif ($progressRange === '100') {
                $q->where('otu.progress', '>=', 100);
            }
        }

        // 7. Participant Learning Hours Filter
        $hoursRange = trim((string) ($filters['learning_hours_range'] ?? ($filters['learning_hours'] ?? ($filters['hours_range'] ?? 'all'))));
        if ($hoursRange !== '' && $hoursRange !== 'all') {
            if ($hoursRange === 'lt05') {
                $q->where('otu.learning_hours', '<', 0.5);
            } elseif ($hoursRange === '05-1') {
                $q->where('otu.learning_hours', '>=', 0.5)->where('otu.learning_hours', '<', 1.0);
            } elseif ($hoursRange === '1-2') {
                $q->where('otu.learning_hours', '>=', 1.0)->where('otu.learning_hours', '<', 2.0);
            } elseif ($hoursRange === '2-4') {
                $q->where('otu.learning_hours', '>=', 2.0)->where('otu.learning_hours', '<', 4.0);
            } elseif ($hoursRange === 'gte4') {
                $q->where('otu.learning_hours', '>=', 4.0);
            }
        }

        // 8. Department Filter
        $department = $filters['department'] ?? ($filters['departments'] ?? 'all');
        if (is_array($department) && !empty($department)) {
            $deptUserIds = DB::table('users')->whereIn('department', $department)->pluck('id')->toArray();
            $q->where(function ($sub) use ($department, $deptUserIds) {
                $sub->whereIn('otu.department', $department);
                if (!empty($deptUserIds)) {
                    $sub->orWhereIn('otu.user_id', $deptUserIds);
                }
            });
        } elseif (is_string($department) && filled($department) && $department !== 'all') {
            $deptTrim = trim($department);
            $deptUserIds = DB::table('users')->where('department', $deptTrim)->pluck('id')->toArray();
            $q->where(function ($sub) use ($deptTrim, $deptUserIds) {
                $sub->where('otu.department', $deptTrim);
                if (!empty($deptUserIds)) {
                    $sub->orWhereIn('otu.user_id', $deptUserIds);
                }
            });
        }

        // 9. Company Filter
        $company = $filters['company'] ?? ($filters['companies'] ?? 'all');
        if (is_array($company) && !empty($company)) {
            $compUserIds = DB::table('users')->whereIn('company_name', $company)->pluck('id')->toArray();
            $q->where(function ($sub) use ($company, $compUserIds) {
                $sub->whereIn('otu.company', $company);
                if (!empty($compUserIds)) {
                    $sub->orWhereIn('otu.user_id', $compUserIds);
                }
            });
        } elseif (is_string($company) && filled($company) && $company !== 'all') {
            $compTrim = trim($company);
            $compUserIds = DB::table('users')->where('company_name', $compTrim)->pluck('id')->toArray();
            $q->where(function ($sub) use ($compTrim, $compUserIds) {
                $sub->where('otu.company', $compTrim);
                if (!empty($compUserIds)) {
                    $sub->orWhereIn('otu.user_id', $compUserIds);
                }
            });
        }

        // 9b. Division Filter
        $division = $filters['division'] ?? ($filters['divisions'] ?? 'all');
        if (is_array($division) && !empty($division)) {
            $divUserIds = DB::table('users')->whereIn('division', $division)->pluck('id')->toArray();
            $q->whereIn('otu.user_id', !empty($divUserIds) ? $divUserIds : [-1]);
        } elseif (is_string($division) && filled($division) && $division !== 'all') {
            $divTrim = trim($division);
            $divUserIds = DB::table('users')->where('division', $divTrim)->pluck('id')->toArray();
            $q->whereIn('otu.user_id', !empty($divUserIds) ? $divUserIds : [-1]);
        }

        // 9c. Team Filter
        $team = $filters['team'] ?? ($filters['teams'] ?? '');
        if (is_array($team) && !empty($team)) {
            $teamUserIds = DB::table('users')->whereIn('team', $team)->pluck('id')->toArray();
            $q->where(function ($sub) use ($team, $teamUserIds) {
                $sub->whereIn('otu.team', $team);
                if (!empty($teamUserIds)) {
                    $sub->orWhereIn('otu.user_id', $teamUserIds);
                }
            });
        } elseif (is_string($team) && filled($team) && $team !== 'all') {
            $teamTrim = trim($team);
            $teamUserIds = DB::table('users')->where('team', 'like', "%{$teamTrim}%")->pluck('id')->toArray();
            $q->where(function ($sub) use ($teamTrim, $teamUserIds) {
                $sub->where('otu.team', 'like', "%{$teamTrim}%");
                if (!empty($teamUserIds)) {
                    $sub->orWhereIn('otu.user_id', $teamUserIds);
                }
            });
        }

        // 10. Sub Classification
        $subClass = trim((string) ($filters['sub_classification'] ?? 'all'));
        if ($subClass !== '' && $subClass !== 'all') {
            $q->where('ot.sub_classification', $subClass);
        }

        // 11. Type
        $typeParam = trim((string) ($filters['type'] ?? ($filters['training_type'] ?? 'all')));
        if ($typeParam !== '' && $typeParam !== 'all') {
            $q->where('ot.type', $typeParam);
        }

        // 12. Batch
        $batch = trim((string) ($filters['batch'] ?? 'all'));
        if ($batch !== '' && $batch !== 'all') {
            $q->where('ot.batch', $batch);
        }

        // 13. Provider
        $provider = trim((string) ($filters['provider'] ?? 'all'));
        if ($provider !== '' && $provider !== 'all') {
            $q->where('ot.provider', $provider);
        }

        // 14. Delivery Method
        $delivery = trim((string) ($filters['delivery_method'] ?? 'all'));
        if ($delivery !== '' && $delivery !== 'all') {
            $q->where('ot.delivery_method', $delivery);
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

        $classificationParam = trim((string) ($request->input('classification', '')));
        $classificationSlug = '';
        if ($classificationParam !== '' && strtolower($classificationParam) !== 'all') {
            $resolvedClass = RecordAllController::resolveClassification($classificationParam, false);
            $classificationSlug = $resolvedClass['slug'];
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
            ->addColumn('employee_id', function ($row) {
                return $row->employee_id ?: '-';
            })
            ->addColumn('employee_name', function ($row) use ($classificationSlug) {
                $name = $row->employee_name ?: '-';
                if ($name === '-' || $name === '') {
                    return '<span class="text-muted">-</span>';
                }

                $identifier = !empty($row->employee_id) ? $row->employee_id : (!empty($row->user_id) ? $row->user_id : null);
                $escaped = htmlspecialchars($name, ENT_QUOTES, 'UTF-8');
                if ($identifier) {
                    $params = ['identifier' => $identifier];
                    if ($classificationSlug) {
                        $params['classification'] = $classificationSlug;
                    }
                    $url = route('manage.record.all.participant', $params);
                    return '<div class="fw-semibold employee-name-cell"><a href="' . htmlspecialchars($url, ENT_QUOTES, 'UTF-8') . '" class="text-primary text-decoration-none hover-underline participant-link" title="View participant details">'
                        . $escaped . '</a></div>';
                }

                return '<div class="fw-semibold text-dark employee-name-cell" title="' . $escaped . '">' . $escaped . '</div>';
            })
            ->addColumn('employee', function ($row) use ($classificationSlug) {
                $name = $row->employee_name ?: '-';
                if ($name === '-' || $name === '') {
                    return '<span class="text-muted">-</span>';
                }

                $identifier = !empty($row->employee_id) ? $row->employee_id : (!empty($row->user_id) ? $row->user_id : null);
                $escaped = htmlspecialchars($name, ENT_QUOTES, 'UTF-8');
                if ($identifier) {
                    $params = ['identifier' => $identifier];
                    if ($classificationSlug) {
                        $params['classification'] = $classificationSlug;
                    }
                    $url = route('manage.record.all.participant', $params);
                    return '<div class="fw-semibold employee-name-cell"><a href="' . htmlspecialchars($url, ENT_QUOTES, 'UTF-8') . '" class="text-primary text-decoration-none hover-underline participant-link" title="View participant details">'
                        . $escaped . '</a></div>';
                }

                return '<div class="fw-semibold text-dark employee-name-cell" title="' . $escaped . '">' . $escaped . '</div>';
            })
            ->addColumn('learning_title', function ($row) use ($classificationSlug) {
                $title = $row->learning_title ?: '-';
                if ($title === '-' || $title === '') {
                    return '<span class="text-muted">-</span>';
                }
                $escaped = htmlspecialchars($title, ENT_QUOTES, 'UTF-8');

                $url = null;
                if ($row->source === 'offline' && !empty($row->offline_training_id)) {
                    $params = ['training' => $row->offline_training_id, 'from' => 'all'];
                    if ($classificationSlug) {
                        $params['classification'] = $classificationSlug;
                    }
                    $url = route('manage.record.offline.course', $params);
                } elseif ($row->source_type === 'content' && !empty($row->content_slug)) {
                    $params = ['slug' => $row->content_slug, 'from' => 'all'];
                    if ($classificationSlug) {
                        $params['classification'] = $classificationSlug;
                    }
                    $url = route('learning.manage.content', $params);
                } elseif ($row->source_type === 'playlist') {
                    $pSlug = !empty($row->playlist_slug) ? $row->playlist_slug : $row->learning_slug;
                    if (!empty($pSlug)) {
                        $url = url('/playlist/' . $pSlug);
                    }
                }

                if ($url) {
                    return '<div class="fw-semibold text-truncate" style="max-width: 250px;" title="' . $escaped . '">'
                        . '<a href="' . htmlspecialchars($url, ENT_QUOTES, 'UTF-8') . '" class="course-link" title="View details">'
                        . $escaped . '</a></div>';
                }

                return '<div class="fw-semibold text-dark text-truncate" style="max-width: 250px;" title="' . $escaped . '">' . $escaped . '</div>';
            })
            ->addColumn('kpi_badge', function ($row) {
                if ($row->counted_corporate_kpi === null || $row->counted_corporate_kpi === '') {
                    return '<span class="text-muted">-</span>';
                }
                $val = (string) $row->counted_corporate_kpi;
                if ($val === '1' || strtolower($val) === 'yes' || strtolower($val) === 'true') {
                    return '<span class="badge bg-success-lt text-success fw-bold" style="font-size: 0.725rem;">Yes</span>';
                }
                return '<span class="badge bg-secondary-lt text-secondary fw-bold" style="font-size: 0.725rem;">No</span>';
            })
            ->addColumn('duration_formatted', function ($row) {
                $sec = (int) ($row->duration_seconds ?? 0);
                if ($sec <= 0) {
                    return '-';
                }
                $h = floor($sec / 3600);
                $m = floor(($sec % 3600) / 60);
                $s = $sec % 60;
                return sprintf('%02d:%02d:%02d', $h, $m, $s);
            })
            ->addColumn('training_hours_formatted', function ($row) {
                if ($row->training_hours === null || $row->training_hours === '') {
                    return '-';
                }
                return number_format((float) $row->training_hours, 2);
            })
            ->addColumn('start_date_formatted', function ($row) {
                $d = $row->start_date ?? null;
                return ($d && $d !== '-') ? date('d M Y', strtotime($d)) : '-';
            })
            ->addColumn('finish_date_formatted', function ($row) {
                $d = $row->finish_date ?? null;
                return ($d && $d !== '-') ? date('d M Y', strtotime($d)) : '-';
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
            ->addColumn('assessment_score_formatted', function ($row) {
                return ($row->assessment_score !== null && $row->assessment_score !== '') ? number_format((float) $row->assessment_score, 1) : '-';
            })
            ->addColumn('certificate_badge', function ($row) {
                if (empty($row->certificate) || $row->certificate === '-') {
                    return '<span class="text-muted">-</span>';
                }
                $cert = e($row->certificate);
                return '<span class="badge bg-blue-lt text-primary fw-bold" style="font-size: 0.725rem;" title="' . $cert . '"><i class="ti ti-file-certificate me-1"></i> Available</span>';
            })
            ->addColumn('attendance_badge', function ($row) {
                $val = (string) ($row->attendance ?? '');
                $isYes = in_array(strtolower(trim($val)), ['1', 'yes', 'true', 'present'], true)
                    || (($row->source ?? '') === 'online' && ((float) ($row->progress ?? 0) >= 100));
                if ($isYes) {
                    return '<span class="badge bg-success-lt text-success fw-bold" style="font-size: 0.725rem;">Yes</span>';
                }
                return '<span class="badge bg-danger-lt text-danger fw-bold" style="font-size: 0.725rem;">No</span>';
            })
            ->addColumn('bond_end_date_formatted', function ($row) {
                $d = $row->bond_end_date ?? null;
                return ($d && $d !== '-') ? date('d M Y', strtotime($d)) : '-';
            })
            ->addColumn('bond_amount_formatted', function ($row) {
                if ($row->bond_amount !== null && (float) $row->bond_amount > 0) {
                    return 'IDR ' . number_format((float) $row->bond_amount, 0, ',', '.');
                }
                return '-';
            })
            ->addColumn('l2_numerical_formatted', function ($row) {
                return ($row->l2_numerical !== null && $row->l2_numerical !== '') ? number_format((float) $row->l2_numerical, 1) : '-';
            })
            ->addColumn('l3_numerical_formatted', function ($row) {
                return ($row->l3_numerical !== null && $row->l3_numerical !== '') ? number_format((float) $row->l3_numerical, 1) : '-';
            })
            ->addColumn('action', function ($row) {
                $user = auth()->user();
                $canRecord = $user && $user->canAccessManageModule('record_management');
                $canLearning = $user && $user->canAccessManageModule('learning_management');
                $canContent = $user && $user->canAccessManageModule('content_repository');
                $canPlaylist = $user && $user->canAccessManageModule('playlist_learning_path');

                if ($row->source === 'offline') {
                    $sourceId = (int) $row->source_id;
                    $empName = htmlspecialchars($row->employee_name ?: ($row->employee ?: 'Participant'), ENT_QUOTES);
                    $empNip = htmlspecialchars($row->employee_id ?: '', ENT_QUOTES);
                    $courseName = htmlspecialchars($row->learning_title ?: 'Training', ENT_QUOTES);
                    $classSlug = Str::slug($row->classification ?? 'unclassified');
                    $editUrl = route('manage.record.offline.edit', [
                        'id'             => $sourceId,
                        'return'         => 'all_classification',
                        'classification' => $classSlug,
                    ]);

                    $items = [];
                    $items[] = '<li>
                        <button type="button" class="dropdown-item d-flex align-items-center gap-2 btn-view-offline-detail" 
                                data-id="' . $sourceId . '">
                            <i class="ti ti-eye text-primary fs-3"></i>
                            <span>View Detail</span>
                        </button>
                    </li>';

                    if ($canRecord) {
                        $items[] = '<li>
                            <a href="' . $editUrl . '" class="dropdown-item d-flex align-items-center gap-2 btn-edit-offline-record">
                                <i class="ti ti-edit text-warning fs-3"></i>
                                <span>Edit Record</span>
                            </a>
                        </li>';
                        $items[] = '<li><hr class="dropdown-divider my-1"></li>';
                        $items[] = '<li>
                            <button type="button" class="dropdown-item d-flex align-items-center gap-2 text-danger btn-delete-offline-record" 
                                    data-id="' . $sourceId . '" 
                                    data-employee="' . $empName . '" 
                                    data-nip="' . $empNip . '" 
                                    data-course="' . $courseName . '">
                                <i class="ti ti-trash text-danger fs-3"></i>
                                <span>Delete Record</span>
                            </button>
                        </li>';
                    }

                    return '<div class="dropdown text-end dropdown-action-menu">
                        <button type="button" class="btn btn-sm btn-icon btn-ghost-secondary rounded-2 btn-action-trigger" 
                                data-bs-toggle="dropdown" 
                                data-bs-boundary="body" 
                                data-bs-popper-config=\'{"strategy":"fixed"}\'
                                aria-expanded="false" 
                                title="Action">
                            <i class="ti ti-dots-vertical fs-3"></i>
                        </button>
                        <ul class="dropdown-menu dropdown-menu-end shadow-sm border-0 py-1 rounded-3" style="min-width: 180px;">
                            ' . implode("\n", $items) . '
                        </ul>
                    </div>';
                }

                // source === 'online'
                $sourceId = (int) $row->source_id;
                $sourceType = htmlspecialchars($row->source_type ?? 'content', ENT_QUOTES);
                $empName = htmlspecialchars($row->employee_name ?: '', ENT_QUOTES);
                $empNip = htmlspecialchars($row->employee_id ?: '', ENT_QUOTES);
                $learningTitle = htmlspecialchars($row->learning_title ?: 'Content', ENT_QUOTES);
                $contentSlug = htmlspecialchars($row->learning_slug ?: '', ENT_QUOTES);
                $playlistSlug = htmlspecialchars($row->playlist_slug ?: '', ENT_QUOTES);

                $items = [];

                // 1. Learning Record Section (Peserta)
                $items[] = '<h6 class="dropdown-header text-uppercase text-muted fw-bold extra-small px-3 py-1 mb-0" style="font-size: 0.68rem; letter-spacing: 0.05em;">Learning Record</h6>';
                $items[] = '<li>
                    <button type="button" class="dropdown-item d-flex align-items-center gap-2 btn-view-online-detail" 
                            data-id="' . $sourceId . '" 
                            data-type="' . $sourceType . '">
                        <i class="ti ti-eye text-primary fs-3"></i>
                        <span>View Detail</span>
                    </button>
                </li>';

                if ($canRecord || $canLearning) {
                    $items[] = '<li>
                        <button type="button" class="dropdown-item d-flex align-items-center gap-2 btn-edit-online-record" 
                                data-id="' . $sourceId . '" 
                                data-type="' . $sourceType . '">
                            <i class="ti ti-adjustments text-warning fs-3"></i>
                            <span>Edit Progress</span>
                        </button>
                    </li>';

                    $items[] = '<li>
                        <button type="button" class="dropdown-item d-flex align-items-center gap-2 text-warning btn-reset-online-record" 
                                data-id="' . $sourceId . '" 
                                data-type="' . $sourceType . '" 
                                data-employee="' . $empName . '" 
                                data-title="' . $learningTitle . '">
                            <i class="ti ti-rotate-clockwise-2 text-warning fs-3"></i>
                            <span>Reset Progress</span>
                        </button>
                    </li>';

                    $items[] = '<li>
                        <button type="button" class="dropdown-item d-flex align-items-center gap-2 text-danger btn-delete-online-record" 
                                data-id="' . $sourceId . '" 
                                data-type="' . $sourceType . '" 
                                data-employee="' . $empName . '" 
                                data-nip="' . $empNip . '" 
                                data-title="' . $learningTitle . '">
                            <i class="ti ti-trash text-danger fs-3"></i>
                            <span>Delete Record</span>
                        </button>
                    </li>';
                }

                // 2. Master Entity Section (Content or Playlist)
                if ($sourceType === 'content') {
                    $masterItems = [];
                    if (!empty($contentSlug)) {
                        $masterItems[] = '<li>
                            <a href="' . route('learning.manage.content', $contentSlug) . '" class="dropdown-item d-flex align-items-center gap-2" target="_blank">
                                <i class="ti ti-external-link text-info fs-3"></i>
                                <span>View Content</span>
                            </a>
                        </li>';

                        if ($canContent) {
                            $masterItems[] = '<li>
                                <a href="' . route('content.edit', $contentSlug) . '" class="dropdown-item d-flex align-items-center gap-2 btn-edit-content-master" target="_blank">
                                    <i class="ti ti-edit text-success fs-3"></i>
                                    <span>Edit Content (Master)</span>
                                </a>
                            </li>';

                            $masterItems[] = '<li>
                                <button type="button" class="dropdown-item d-flex align-items-center gap-2 text-danger btn-delete-content-master" 
                                        data-slug="' . $contentSlug . '" 
                                        data-title="' . $learningTitle . '" 
                                        data-type="content">
                                    <i class="ti ti-trash-x text-danger fs-3"></i>
                                    <span>Delete Content (Master)</span>
                                </button>
                            </li>';
                        }
                    }

                    if (!empty($masterItems)) {
                        $items[] = '<li><hr class="dropdown-divider my-1"></li>';
                        $items[] = '<h6 class="dropdown-header text-uppercase text-muted fw-bold extra-small px-3 py-1 mb-0" style="font-size: 0.68rem; letter-spacing: 0.05em;">Master Content</h6>';
                        $items = array_merge($items, $masterItems);
                    }
                } elseif ($sourceType === 'playlist') {
                    $masterItems = [];
                    $targetSlug = !empty($playlistSlug) ? $playlistSlug : (!empty($row->learning_slug) ? $row->learning_slug : '');
                    if (!empty($targetSlug)) {
                        $masterItems[] = '<li>
                            <a href="' . url('/playlist/' . $targetSlug) . '" class="dropdown-item d-flex align-items-center gap-2" target="_blank">
                                <i class="ti ti-external-link text-info fs-3"></i>
                                <span>View Playlist</span>
                            </a>
                        </li>';

                        if ($canPlaylist) {
                            $masterItems[] = '<li>
                                <a href="' . route('playlist.edit', $targetSlug) . '" class="dropdown-item d-flex align-items-center gap-2 btn-edit-playlist-master" target="_blank">
                                    <i class="ti ti-edit text-success fs-3"></i>
                                    <span>Edit Playlist (Master)</span>
                                </a>
                            </li>';

                            $masterItems[] = '<li>
                                <button type="button" class="dropdown-item d-flex align-items-center gap-2 text-danger btn-delete-playlist-master" 
                                        data-slug="' . $targetSlug . '" 
                                        data-title="' . $learningTitle . '" 
                                        data-type="playlist">
                                    <i class="ti ti-trash-x text-danger fs-3"></i>
                                    <span>Delete Playlist (Master)</span>
                                </button>
                            </li>';
                        }
                    }

                    if (!empty($masterItems)) {
                        $items[] = '<li><hr class="dropdown-divider my-1"></li>';
                        $items[] = '<h6 class="dropdown-header text-uppercase text-muted fw-bold extra-small px-3 py-1 mb-0" style="font-size: 0.68rem; letter-spacing: 0.05em;">Master Playlist</h6>';
                        $items = array_merge($items, $masterItems);
                    }
                }

                return '<div class="dropdown text-end dropdown-action-menu">
                    <button type="button" class="btn btn-sm btn-icon btn-ghost-secondary rounded-2 btn-action-trigger" 
                            data-bs-toggle="dropdown" 
                            data-bs-boundary="body" 
                            data-bs-popper-config=\'{"strategy":"fixed"}\'
                            aria-expanded="false" 
                            title="Action">
                        <i class="ti ti-dots-vertical fs-3"></i>
                    </button>
                    <ul class="dropdown-menu dropdown-menu-end shadow-sm border-0 py-1 rounded-3" style="min-width: 200px;">
                        ' . implode("\n", $items) . '
                    </ul>
                </div>';
            })
            ->rawColumns([
                'source_badge',
                'status_badge',
                'employee_name',
                'employee',
                'learning_title',
                'kpi_badge',
                'progress_bar',
                'certificate_badge',
                'attendance_badge',
                'action',
            ])
            ->make(true);
    }

    /**
     * Resolve a participant identity across Online users and Offline training participants.
     */
    public function resolveParticipant(string $identifier): ?array
    {
        $identifier = trim($identifier);
        if ($identifier === '') {
            return null;
        }

        $user = null;
        $offlineUser = null;

        if (is_numeric($identifier) && (int) $identifier > 0) {
            $user = User::whereNull('deleted_at')->find((int) $identifier);
            if (!$user) {
                $user = User::whereNull('deleted_at')->where('id_emp', $identifier)->first();
            }
            if (!$user && Schema::hasTable('offline_training_users')) {
                $offlineUser = DB::table('offline_training_users')
                    ->whereNull('deleted_at')
                    ->where(function ($q) use ($identifier) {
                        $q->where('user_id', (int) $identifier)
                          ->orWhere('employee_id', $identifier);
                    })
                    ->orderByDesc('id')
                    ->first();
            }
        } else {
            $user = User::whereNull('deleted_at')->where('id_emp', $identifier)->first();
            if (!$user && Schema::hasTable('offline_training_users')) {
                $offlineUser = DB::table('offline_training_users')
                    ->whereNull('deleted_at')
                    ->where('employee_id', $identifier)
                    ->orderByDesc('id')
                    ->first();
            }
            if (!$user && !$offlineUser) {
                $user = User::whereNull('deleted_at')->where('name', $identifier)->first();
            }
            if (!$user && !$offlineUser && Schema::hasTable('offline_training_users')) {
                $offlineUser = DB::table('offline_training_users')
                    ->whereNull('deleted_at')
                    ->where('name', $identifier)
                    ->orderByDesc('id')
                    ->first();
            }
        }

        // If user found, look for matching offline training user to enrich grade/job_position/team
        if ($user && !$offlineUser && Schema::hasTable('offline_training_users')) {
            $offlineUser = DB::table('offline_training_users')
                ->whereNull('deleted_at')
                ->where(function ($q) use ($user) {
                    $q->where('user_id', $user->id);
                    if (!empty($user->id_emp)) {
                        $q->orWhere('employee_id', $user->id_emp);
                    }
                })
                ->orderByDesc('id')
                ->first();
        }

        // If offline record found without user, backfill user
        if ($offlineUser && !$user) {
            if (!empty($offlineUser->user_id)) {
                $user = User::whereNull('deleted_at')->find($offlineUser->user_id);
            }
            if (!$user && !empty($offlineUser->employee_id)) {
                $user = User::whereNull('deleted_at')->where('id_emp', $offlineUser->employee_id)->first();
            }
        }

        if (!$user && !$offlineUser) {
            return null;
        }

        $userId = $user?->id ?? ($offlineUser?->user_id ? (int) $offlineUser->user_id : null);
        $employeeId = $offlineUser?->employee_id ?: ($user?->id_emp ?: '');
        $name = ($offlineUser && !empty($offlineUser->name)) ? $offlineUser->name : ($user?->name ?: 'Participant');
        $grade = $offlineUser?->grade ?: ($user?->grade ?? '-');
        $jobPosition = $offlineUser?->job_position ?: ($user?->job_position ?? '-');
        $team = $offlineUser?->team ?: ($user?->team ?? '-');
        $department = $offlineUser?->department ?: ($user?->department ?? '-');
        $company = $offlineUser?->company ?: ($user?->company_name ?? 'PT Krakatau Posco');

        $rawIdentifier = !empty($employeeId) ? $employeeId : ($userId ? (string) $userId : $identifier);

        return [
            'user'           => $user,
            'user_id'        => $userId,
            'employee_id'    => $employeeId,
            'name'           => $name,
            'grade'          => $grade,
            'job_position'   => $jobPosition,
            'team'           => $team,
            'department'     => $department,
            'company'        => $company,
            'raw_identifier' => $rawIdentifier,
        ];
    }

    /**
     * Compute combined KPI summary for a specific participant.
     */
    public function getParticipantSummary(array $participant, ?string $classification = null): array
    {
        $filters = ['exact_participant' => $participant];
        if (filled($classification) && strtolower($classification) !== 'all') {
            $resolvedClass = RecordAllController::resolveClassification($classification, false);
            $filters['classification'] = $resolvedClass['is_unclassified'] ? 'Unclassified' : $resolvedClass['name'];
        }

        $query = $this->buildCombinedQuery($filters);
        $records = $query->get();

        $totalLearning = $records->count();
        $finished = 0;
        $ongoing = 0;
        $totalHours = 0.0;

        foreach ($records as $r) {
            $prog = (float) ($r->progress ?? 0);
            $st = (string) ($r->status ?? '');
            if ($st === 'Finished' || $prog >= 100) {
                $finished++;
            } elseif ($prog > 0) {
                $ongoing++;
            }

            $totalHours += (float) ($r->learning_hours ?? 0);
        }

        return [
            'total_learning' => $totalLearning,
            'finished'       => $finished,
            'ongoing'        => $ongoing,
            'learning_hours' => round($totalHours, 2),
        ];
    }

    /**
     * Build combined query locked strictly to a participant.
     */
    public function buildParticipantCombinedQuery(array $participant, array $filters = []): Builder
    {
        $filters['exact_participant'] = $participant;
        return $this->buildCombinedQuery($filters);
    }

    /**
     * Server-side DataTable for combined learning records of a single participant.
     */
    public function participantDatatable(array $participant, Request $request): JsonResponse
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
        $filters['exact_participant'] = $participant;

        $classificationParam = trim((string) ($request->input('classification', '')));
        $classificationSlug = '';
        if ($classificationParam !== '' && strtolower($classificationParam) !== 'all') {
            $resolvedClass = RecordAllController::resolveClassification($classificationParam, false);
            $classificationSlug = $resolvedClass['slug'];
            $filters['classification'] = $resolvedClass['is_unclassified'] ? 'Unclassified' : $resolvedClass['name'];
        }

        $query = $this->buildCombinedQuery($filters);

        // Sorting: default is latest finish_date / record_date
        $hasCustomSort = $request->filled('order');
        if (!$hasCustomSort) {
            $query->orderBy('raw_finish_date', 'desc')
                ->orderBy('record_date', 'desc');
        }

        $totalRecords = (clone $query)->count();

        $dataTable = DataTables::of($query)
            ->setTotalRecords($totalRecords)
            ->setFilteredRecords($totalRecords);

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
            ->addColumn('learning_title', function ($row) use ($classificationSlug) {
                $title = $row->learning_title ?: '-';
                if ($title === '-' || $title === '') {
                    return '<span class="text-muted">-</span>';
                }
                $escaped = htmlspecialchars($title, ENT_QUOTES, 'UTF-8');

                $url = null;
                if ($row->source === 'offline' && !empty($row->offline_training_id)) {
                    $params = ['training' => $row->offline_training_id, 'from' => 'all'];
                    if ($classificationSlug) {
                        $params['classification'] = $classificationSlug;
                    }
                    $url = route('manage.record.offline.course', $params);
                } elseif ($row->source_type === 'content' && !empty($row->content_slug)) {
                    $params = ['slug' => $row->content_slug, 'from' => 'all'];
                    if ($classificationSlug) {
                        $params['classification'] = $classificationSlug;
                    }
                    $url = route('learning.manage.content', $params);
                } elseif ($row->source_type === 'playlist') {
                    $pSlug = !empty($row->playlist_slug) ? $row->playlist_slug : $row->learning_slug;
                    if (!empty($pSlug)) {
                        $url = url('/playlist/' . $pSlug);
                    }
                }

                if ($url) {
                    return '<div class="fw-semibold text-truncate" style="max-width: 250px;" title="' . $escaped . '">'
                        . '<a href="' . htmlspecialchars($url, ENT_QUOTES, 'UTF-8') . '" class="course-link" title="View details">'
                        . $escaped . '</a></div>';
                }

                return '<div class="fw-semibold text-dark text-truncate" style="max-width: 250px;" title="' . $escaped . '">' . $escaped . '</div>';
            })
            ->addColumn('type', function ($row) {
                return htmlspecialchars($row->type ?: '-');
            })
            ->addColumn('category', function ($row) {
                return htmlspecialchars($row->category ?: '-');
            })
            ->addColumn('classification', function ($row) {
                return htmlspecialchars($row->classification ?: '-');
            })
            ->addColumn('duration_formatted', function ($row) {
                $sec = (int) ($row->duration_seconds ?? 0);
                if ($sec <= 0) {
                    return '-';
                }
                $h = floor($sec / 3600);
                $m = floor(($sec % 3600) / 60);
                $s = $sec % 60;
                return sprintf('%02d:%02d:%02d', $h, $m, $s);
            })
            ->addColumn('learning_hours_formatted', function ($row) {
                $lh = (float) ($row->learning_hours ?? 0);
                return '<span class="fw-semibold">' . number_format($lh, 2) . 'h</span>';
            })
            ->addColumn('progress_badge', function ($row) {
                $p = min(100, max(0, (float) ($row->progress ?? 0)));
                $barClass = $p >= 100 ? 'bg-success' : ($p > 0 ? 'bg-warning' : 'bg-secondary');
                return '<div class="d-flex align-items-center gap-2" style="min-width: 90px;">
                    <div class="progress flex-grow-1" style="height: 6px;">
                        <div class="progress-bar ' . $barClass . '" style="width: ' . $p . '%;"></div>
                    </div>
                    <span class="small fw-semibold text-muted" style="font-size: 0.72rem; min-width: 32px;">' . round($p) . '%</span>
                </div>';
            })
            ->addColumn('record_date_formatted', function ($row) {
                $d = $row->finish_date ?? ($row->start_date ?? ($row->record_date ?? null));
                return ($d && $d !== '-') ? date('d M Y', strtotime($d)) : '-';
            })
            ->addColumn('action', function ($row) use ($participant, $classificationSlug) {
                $user = auth()->user();
                $canRecord = $user && $user->canAccessManageModule('record_management');
                $canLearning = $user && $user->canAccessManageModule('learning_management');
                $canContent = $user && $user->canAccessManageModule('content_repository');
                $canPlaylist = $user && $user->canAccessManageModule('playlist_learning_path');

                if ($row->source === 'offline') {
                    $sourceId = (int) $row->source_id;
                    $empName = htmlspecialchars($row->employee_name ?: ($participant['name'] ?: 'Participant'), ENT_QUOTES);
                    $empNip = htmlspecialchars($row->employee_id ?: ($participant['employee_id'] ?: ''), ENT_QUOTES);
                    $courseName = htmlspecialchars($row->learning_title ?: 'Training', ENT_QUOTES);
                    $classSlug = !empty($classificationSlug) ? $classificationSlug : Str::slug($row->classification ?? 'unclassified');
                    $rawIdentifier = $participant['raw_identifier'] ?? ($participant['user_id'] ?? $participant['employee_id']);
                    $editUrl = route('manage.record.offline.edit', [
                        'id'             => $sourceId,
                        'return'         => 'all_participant',
                        'identifier'     => $rawIdentifier,
                        'classification' => $classSlug,
                    ]);

                    $items = [];
                    $items[] = '<li>
                        <button type="button" class="dropdown-item d-flex align-items-center gap-2 btn-view-offline-detail" 
                                data-id="' . $sourceId . '">
                            <i class="ti ti-eye text-primary fs-3"></i>
                            <span>View Detail</span>
                        </button>
                    </li>';

                    if ($canRecord) {
                        $items[] = '<li>
                            <a href="' . $editUrl . '" class="dropdown-item d-flex align-items-center gap-2 btn-edit-offline-record">
                                <i class="ti ti-edit text-warning fs-3"></i>
                                <span>Edit Record</span>
                            </a>
                        </li>';
                        $items[] = '<li><hr class="dropdown-divider my-1"></li>';
                        $items[] = '<li>
                            <button type="button" class="dropdown-item d-flex align-items-center gap-2 text-danger btn-delete-offline-record" 
                                    data-id="' . $sourceId . '" 
                                    data-employee="' . $empName . '" 
                                    data-nip="' . $empNip . '" 
                                    data-course="' . $courseName . '">
                                <i class="ti ti-trash text-danger fs-3"></i>
                                <span>Delete Record</span>
                            </button>
                        </li>';
                    }

                    return '<div class="dropdown text-end dropdown-action-menu">
                        <button type="button" class="btn btn-sm btn-icon btn-ghost-secondary rounded-2 btn-action-trigger" 
                                data-bs-toggle="dropdown" 
                                data-bs-boundary="body" 
                                data-bs-popper-config=\'{"strategy":"fixed"}\'
                                aria-expanded="false" 
                                title="Action">
                            <i class="ti ti-dots-vertical fs-3"></i>
                        </button>
                        <ul class="dropdown-menu dropdown-menu-end shadow-sm border-0 py-1 rounded-3" style="min-width: 180px;">
                            ' . implode("\n", $items) . '
                        </ul>
                    </div>';
                }

                // source === 'online'
                $sourceId = (int) $row->source_id;
                $sourceType = htmlspecialchars($row->source_type ?? 'content', ENT_QUOTES);
                $empName = htmlspecialchars($row->employee_name ?: ($participant['name'] ?: ''), ENT_QUOTES);
                $empNip = htmlspecialchars($row->employee_id ?: ($participant['employee_id'] ?: ''), ENT_QUOTES);
                $learningTitle = htmlspecialchars($row->learning_title ?: 'Content', ENT_QUOTES);
                $contentSlug = htmlspecialchars($row->learning_slug ?: ($row->content_slug ?? ''), ENT_QUOTES);
                $playlistSlug = htmlspecialchars($row->playlist_slug ?: ($row->learning_slug ?? ''), ENT_QUOTES);

                $items = [];

                // 1. Learning Record Section (Peserta)
                $items[] = '<h6 class="dropdown-header text-uppercase text-muted fw-bold extra-small px-3 py-1 mb-0" style="font-size: 0.68rem; letter-spacing: 0.05em;">Learning Record</h6>';
                $items[] = '<li>
                    <button type="button" class="dropdown-item d-flex align-items-center gap-2 btn-view-online-detail" 
                            data-id="' . $sourceId . '" 
                            data-type="' . $sourceType . '">
                        <i class="ti ti-eye text-primary fs-3"></i>
                        <span>View Detail</span>
                    </button>
                </li>';

                if ($canRecord || $canLearning) {
                    $items[] = '<li>
                        <button type="button" class="dropdown-item d-flex align-items-center gap-2 btn-edit-online-record" 
                                data-id="' . $sourceId . '" 
                                data-type="' . $sourceType . '">
                            <i class="ti ti-adjustments text-warning fs-3"></i>
                            <span>Edit Progress</span>
                        </button>
                    </li>';

                    $items[] = '<li>
                        <button type="button" class="dropdown-item d-flex align-items-center gap-2 text-warning btn-reset-online-record" 
                                data-id="' . $sourceId . '" 
                                data-type="' . $sourceType . '" 
                                data-employee="' . $empName . '" 
                                data-title="' . $learningTitle . '">
                            <i class="ti ti-rotate-clockwise-2 text-warning fs-3"></i>
                            <span>Reset Progress</span>
                        </button>
                    </li>';

                    $items[] = '<li>
                        <button type="button" class="dropdown-item d-flex align-items-center gap-2 text-danger btn-delete-online-record" 
                                data-id="' . $sourceId . '" 
                                data-type="' . $sourceType . '" 
                                data-employee="' . $empName . '" 
                                data-nip="' . $empNip . '" 
                                data-title="' . $learningTitle . '">
                            <i class="ti ti-trash text-danger fs-3"></i>
                            <span>Delete Record</span>
                        </button>
                    </li>';
                }

                // 2. Master Entity Section (Content or Playlist)
                if ($sourceType === 'content') {
                    $masterItems = [];
                    if (!empty($contentSlug)) {
                        $masterItems[] = '<li>
                            <a href="' . route('learning.manage.content', $contentSlug) . '" class="dropdown-item d-flex align-items-center gap-2" target="_blank">
                                <i class="ti ti-external-link text-info fs-3"></i>
                                <span>View Content</span>
                            </a>
                        </li>';

                        if ($canContent) {
                            $masterItems[] = '<li>
                                <a href="' . route('content.edit', $contentSlug) . '" class="dropdown-item d-flex align-items-center gap-2 btn-edit-content-master" target="_blank">
                                    <i class="ti ti-edit text-success fs-3"></i>
                                    <span>Edit Content (Master)</span>
                                </a>
                            </li>';

                            $masterItems[] = '<li>
                                <button type="button" class="dropdown-item d-flex align-items-center gap-2 text-danger btn-delete-content-master" 
                                        data-slug="' . $contentSlug . '" 
                                        data-title="' . $learningTitle . '" 
                                        data-type="content">
                                    <i class="ti ti-trash-x text-danger fs-3"></i>
                                    <span>Delete Content (Master)</span>
                                </button>
                            </li>';
                        }
                    }

                    if (!empty($masterItems)) {
                        $items[] = '<li><hr class="dropdown-divider my-1"></li>';
                        $items[] = '<h6 class="dropdown-header text-uppercase text-muted fw-bold extra-small px-3 py-1 mb-0" style="font-size: 0.68rem; letter-spacing: 0.05em;">Master Content</h6>';
                        $items = array_merge($items, $masterItems);
                    }
                } elseif ($sourceType === 'playlist') {
                    $masterItems = [];
                    $targetSlug = !empty($playlistSlug) ? $playlistSlug : (!empty($row->learning_slug) ? $row->learning_slug : '');
                    if (!empty($targetSlug)) {
                        $masterItems[] = '<li>
                            <a href="' . url('/playlist/' . $targetSlug) . '" class="dropdown-item d-flex align-items-center gap-2" target="_blank">
                                <i class="ti ti-external-link text-info fs-3"></i>
                                <span>View Playlist</span>
                            </a>
                        </li>';

                        if ($canPlaylist) {
                            $masterItems[] = '<li>
                                <a href="' . route('playlist.edit', $targetSlug) . '" class="dropdown-item d-flex align-items-center gap-2 btn-edit-playlist-master" target="_blank">
                                    <i class="ti ti-edit text-success fs-3"></i>
                                    <span>Edit Playlist (Master)</span>
                                </a>
                            </li>';

                            $masterItems[] = '<li>
                                <button type="button" class="dropdown-item d-flex align-items-center gap-2 text-danger btn-delete-playlist-master" 
                                        data-slug="' . $targetSlug . '" 
                                        data-title="' . $learningTitle . '" 
                                        data-type="playlist">
                                    <i class="ti ti-trash-x text-danger fs-3"></i>
                                    <span>Delete Playlist (Master)</span>
                                </button>
                            </li>';
                        }
                    }

                    if (!empty($masterItems)) {
                        $items[] = '<li><hr class="dropdown-divider my-1"></li>';
                        $items[] = '<h6 class="dropdown-header text-uppercase text-muted fw-bold extra-small px-3 py-1 mb-0" style="font-size: 0.68rem; letter-spacing: 0.05em;">Master Playlist</h6>';
                        $items = array_merge($items, $masterItems);
                    }
                }

                return '<div class="dropdown text-end dropdown-action-menu">
                    <button type="button" class="btn btn-sm btn-icon btn-ghost-secondary rounded-2 btn-action-trigger" 
                            data-bs-toggle="dropdown" 
                            data-bs-boundary="body" 
                            data-bs-popper-config=\'{"strategy":"fixed"}\'
                            aria-expanded="false" 
                            title="Action">
                        <i class="ti ti-dots-vertical fs-3"></i>
                    </button>
                    <ul class="dropdown-menu dropdown-menu-end shadow-sm border-0 py-1 rounded-3" style="min-width: 200px;">
                        ' . implode("\n", $items) . '
                    </ul>
                </div>';
            })
            ->rawColumns([
                'source_badge',
                'status_badge',
                'learning_title',
                'learning_hours_formatted',
                'progress_badge',
                'action',
            ])
            ->make(true);
    }
}
