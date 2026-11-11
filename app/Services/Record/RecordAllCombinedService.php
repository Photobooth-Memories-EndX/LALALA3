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
     * In-memory cache for canonical aggregated summary and classifications within request lifecycle.
     */
    protected ?array $canonicalAggregatedDataCache = null;

    /**
     * Calculate combined KPI summary across Online and Offline records.
     * Derived from canonical unified aggregation to guarantee exact synchronization
     * with the Classification Summary GRAND TOTAL row.
     */
    public function getSummary(): array
    {
        return $this->getCanonicalAggregatedData()['summary'];
    }

    /**
     * Cached master classifications list from table `classification`.
     */
    protected static ?array $masterClassificationsCache = null;
    protected static ?bool $hasParticipantClassification = null;

    public static function hasParticipantClassification(): bool
    {
        if (self::$hasParticipantClassification === null) {
            self::$hasParticipantClassification = Schema::hasTable('offline_training_users')
                && Schema::hasColumn('offline_training_users', 'main_classification');
        }
        return self::$hasParticipantClassification;
    }

    /**
     * Get master classifications from table `classification` + Compliance.
     */
    public static function getMasterClassifications(): array
    {
        if (self::$masterClassificationsCache !== null) {
            return self::$masterClassificationsCache;
        }

        $masters = [];
        $dbMasters = DB::table('classification')
            ->orderBy('id')
            ->get();

        foreach ($dbMasters as $m) {
            $slug = !empty($m->slug) ? $m->slug : Str::slug($m->classification_name);
            $masters[$slug] = [
                'id'                  => (string) $m->id,
                'classification_name' => trim($m->classification_name),
                'slug'                => $slug,
                'is_active'           => (int) ($m->is_active ?? 1),
            ];
        }

        if (!isset($masters['compliance'])) {
            $masters['compliance'] = [
                'id'                  => 'compliance',
                'classification_name' => 'Compliance',
                'slug'                => 'compliance',
                'is_active'           => 1,
            ];
        }

        self::$masterClassificationsCache = $masters;
        return self::$masterClassificationsCache;
    }

    /**
     * Resolve any raw classification name or ID into canonical master entry or Unclassification bucket.
     */
    public static function resolveCanonical(?string $rawName, $id = null): array
    {
        $masters = self::getMasterClassifications();

        // 1. Try by master ID
        if ($id !== null && $id !== '') {
            foreach ($masters as $m) {
                if ((string) $m['id'] === (string) $id) {
                    return array_merge($m, ['is_unclassified' => false]);
                }
            }
        }

        $cleaned = trim(preg_replace('/\s+/', ' ', (string) ($rawName ?? '')));
        $lower = strtolower($cleaned);
        $slug = Str::slug($cleaned);

        // 2. Unclassified triggers
        if ($cleaned === '' || in_array($lower, ['unclassified', 'unclassification', 'audit isolated classification'], true) || in_array($slug, ['unclassified', 'unclassification', 'audit-isolated-classification'], true)) {
            return [
                'id'                  => 'unclassification',
                'classification_name' => 'Unclassification',
                'slug'                => 'unclassification',
                'is_unclassified'     => true,
            ];
        }

        // 3. Explicit alias mapping (Basic Common Duties variations)
        $aliases = [
            'basic common duties'     => 'basic-common-duties',
            'basic & common duties'   => 'basic-common-duties',
            'basic and common duties' => 'basic-common-duties',
            'basic-common-duties'     => 'basic-common-duties',
        ];

        if (isset($aliases[$lower])) {
            $targetSlug = $aliases[$lower];
            if (isset($masters[$targetSlug])) {
                return array_merge($masters[$targetSlug], ['is_unclassified' => false]);
            }
        }

        // 4. Match by slug
        if (isset($masters[$slug])) {
            return array_merge($masters[$slug], ['is_unclassified' => false]);
        }

        // 5. Match by case-insensitive name
        foreach ($masters as $m) {
            if (strcasecmp($m['classification_name'], $cleaned) === 0) {
                return array_merge($m, ['is_unclassified' => false]);
            }
        }

        // 6. Ampersand / 'and' normalized slug match
        $normalizedAmpersandSlug = Str::slug(str_replace('&', 'and', $cleaned));
        foreach ($masters as $m) {
            $mAmpSlug = Str::slug(str_replace('&', 'and', $m['classification_name']));
            if ($normalizedAmpersandSlug === $mAmpSlug) {
                return array_merge($m, ['is_unclassified' => false]);
            }
        }

        // 7. Non-master fallback -> Unclassification
        return [
            'id'                  => 'unclassification',
            'classification_name' => 'Unclassification',
            'slug'                => 'unclassification',
            'is_unclassified'     => true,
        ];
    }

    /**
     * Calculate combined Classification Summary table rows with canonical mapping.
     */
    public function getClassificationSummary(bool $fresh = false): Collection
    {
        return $this->getCanonicalAggregatedData($fresh)['classification'];
    }

    /**
     * Compute and return canonical unified aggregation across Online (KP OWL) and Offline (Non KP OWL).
     * Single source of truth for both KPI Header Cards and Classification Summary Grand Total.
     */
    public function getCanonicalAggregatedData(bool $fresh = false): array
    {
        if (!$fresh && $this->canonicalAggregatedDataCache !== null) {
            return $this->canonicalAggregatedDataCache;
        }

        // 1. Initialize master summary map
        $masters = self::getMasterClassifications();
        $canonicalSummary = [];

        foreach ($masters as $slug => $master) {
            $canonicalSummary[$slug] = [
                'id'                  => $master['id'],
                'classification_name' => $master['classification_name'],
                'slug'                => $master['slug'],
                'kp_owl'              => 0,
                'non_kp_owl'          => 0,
                'total_learning'      => 0,
                'learning_hours'      => 0.0,
                'ongoing'             => 0,
                'finished'            => 0,
                'is_unclassified'     => false,
            ];
        }

        // Synthetic catch-all Unclassification group
        $canonicalSummary['unclassification'] = [
            'id'                  => 'unclassification',
            'classification_name' => 'Unclassification',
            'slug'                => 'unclassification',
            'kp_owl'              => 0,
            'non_kp_owl'          => 0,
            'total_learning'      => 0,
            'learning_hours'      => 0.0,
            'ongoing'             => 0,
            'finished'            => 0,
            'is_unclassified'     => true,
        ];

        // 2. Online classifications aggregation
        $onlineClassifications = $this->learningManageController->getClassificationSummary();

        $onlineTotal = 0;
        $onlineOngoing = 0;
        $onlineFinished = 0;
        $onlineHours = 0.0;

        foreach ($onlineClassifications as $oc) {
            $resolved = self::resolveCanonical($oc->classification_name ?? null, $oc->id ?? null);
            $targetSlug = $resolved['slug'];

            if (!isset($canonicalSummary[$targetSlug])) {
                $canonicalSummary[$targetSlug] = [
                    'id'                  => $resolved['id'],
                    'classification_name' => $resolved['classification_name'],
                    'slug'                => $resolved['slug'],
                    'kp_owl'              => 0,
                    'non_kp_owl'          => 0,
                    'total_learning'      => 0,
                    'learning_hours'      => 0.0,
                    'ongoing'             => 0,
                    'finished'            => 0,
                    'is_unclassified'     => $resolved['is_unclassified'],
                ];
            }

            $count = (int) ($oc->total_learning ?? 0);
            $ongoingCount = (int) ($oc->ongoing_learning ?? 0);
            $finishedCount = (int) ($oc->finished_learning ?? 0);
            $hours = (float) ($oc->learning_hours ?? 0);

            $canonicalSummary[$targetSlug]['kp_owl'] += $count;
            $canonicalSummary[$targetSlug]['total_learning'] += $count;
            $canonicalSummary[$targetSlug]['ongoing'] += $ongoingCount;
            $canonicalSummary[$targetSlug]['finished'] += $finishedCount;
            $canonicalSummary[$targetSlug]['learning_hours'] += $hours;

            $onlineTotal += $count;
            $onlineOngoing += $ongoingCount;
            $onlineFinished += $finishedCount;
            $onlineHours += $hours;
        }

        // Detailed Content and Playlist sub-counts for online breakdown
        $cuStats = Schema::hasTable('content_users')
            ? DB::selectOne("
                SELECT 
                    COUNT(*) as total,
                    SUM(CASE WHEN finish = '1' THEN 1 ELSE 0 END) as finished,
                    SUM(CASE WHEN finish = '0' THEN 1 ELSE 0 END) as ongoing,
                    COALESCE(SUM(learning_hours), 0) as hours
                FROM content_users
            ")
            : null;

        $pcuStats = Schema::hasTable('playlist_content_users')
            ? DB::selectOne("
                SELECT 
                    COUNT(*) as total,
                    SUM(CASE WHEN finish = '1' THEN 1 ELSE 0 END) as finished,
                    SUM(CASE WHEN finish = '0' THEN 1 ELSE 0 END) as ongoing,
                    COALESCE(SUM(learning_hour), 0) as hours
                FROM playlist_content_users
            ")
            : null;

        $cuTotal = (int) ($cuStats->total ?? 0);
        $cuOngoing = (int) ($cuStats->ongoing ?? 0);
        $cuFinished = (int) ($cuStats->finished ?? 0);
        $cuHours = (float) ($cuStats->hours ?? 0);

        $pcuTotal = (int) ($pcuStats->total ?? 0);
        $pcuOngoing = (int) ($pcuStats->ongoing ?? 0);
        $pcuFinished = (int) ($pcuStats->finished ?? 0);
        $pcuHours = (float) ($pcuStats->hours ?? 0);

        // 3. Offline classifications aggregation
        $hasOfflineTables = Schema::hasTable('offline_trainings') && Schema::hasTable('offline_training_users');

        $offlineTotal = 0;
        $offlineOngoing = 0;
        $offlineFinished = 0;
        $offlineHours = 0.0;

        if ($hasOfflineTables) {
            $offlineQuery = DB::table('offline_training_users as otu')
                ->join('offline_trainings as ot', 'ot.id', '=', 'otu.offline_training_id')
                ->whereNull('otu.deleted_at')
                ->whereNull('ot.deleted_at');
            OfflineTraining::applyRecordScope($offlineQuery, 'ot');

            $offlineData = $offlineQuery
                ->select([
                    'ot.main_classification as raw_classification',
                    DB::raw("COUNT(otu.id) as total_count"),
                    DB::raw("SUM(CASE WHEN otu.progress > 0 AND otu.progress < 100 THEN 1 ELSE 0 END) as ongoing_count"),
                    DB::raw("SUM(CASE WHEN otu.progress >= 100 THEN 1 ELSE 0 END) as finished_count"),
                    DB::raw("COALESCE(SUM(otu.learning_hours), 0) as total_hours"),
                ])
                ->groupBy('ot.main_classification')
                ->get();

            foreach ($offlineData as $od) {
                $resolved = self::resolveCanonical($od->raw_classification ?? null);
                $targetSlug = $resolved['slug'];

                if (!isset($canonicalSummary[$targetSlug])) {
                    $canonicalSummary[$targetSlug] = [
                        'id'                  => $resolved['id'],
                        'classification_name' => $resolved['classification_name'],
                        'slug'                => $resolved['slug'],
                        'kp_owl'              => 0,
                        'non_kp_owl'          => 0,
                        'total_learning'      => 0,
                        'learning_hours'      => 0.0,
                        'ongoing'             => 0,
                        'finished'            => 0,
                        'is_unclassified'     => $resolved['is_unclassified'],
                    ];
                }

                $count = (int) ($od->total_count ?? 0);
                $ongoingCount = (int) ($od->ongoing_count ?? 0);
                $finishedCount = (int) ($od->finished_count ?? 0);
                $hours = (float) ($od->total_hours ?? 0);

                $canonicalSummary[$targetSlug]['non_kp_owl'] += $count;
                $canonicalSummary[$targetSlug]['total_learning'] += $count;
                $canonicalSummary[$targetSlug]['ongoing'] += $ongoingCount;
                $canonicalSummary[$targetSlug]['finished'] += $finishedCount;
                $canonicalSummary[$targetSlug]['learning_hours'] += $hours;

                $offlineTotal += $count;
                $offlineOngoing += $ongoingCount;
                $offlineFinished += $finishedCount;
                $offlineHours += $hours;
            }
        }

        // 4. Split normal classifications and synthetic Unclassification
        $normal = [];
        $unclassRow = null;

        foreach ($canonicalSummary as $item) {
            $item['learning_hours'] = round($item['learning_hours'], 2);

            if ($item['is_unclassified'] || $item['slug'] === 'unclassification') {
                $unclassRow = $item;
            } else {
                $normal[] = $item;
            }
        }

        // 5. Custom sorting: normal classifications sorted alphabetically
        usort($normal, function ($a, $b) {
            return strcasecmp($a['classification_name'], $b['classification_name']);
        });

        // 6. Append Unclassification strictly at the bottom (only if total_learning > 0)
        $merged = $normal;
        if ($unclassRow !== null && $unclassRow['total_learning'] > 0) {
            $merged[] = $unclassRow;
        }

        // 7. Generate 1-based sequential 'no'
        $finalCollection = collect();
        foreach ($merged as $idx => $item) {
            $item['no'] = $idx + 1;
            $finalCollection->push((object) $item);
        }

        // 8. Overall unified KPI metrics derived directly from classification collection
        $totalLearning = (int) $finalCollection->sum('total_learning');
        $totalOngoing = (int) $finalCollection->sum('ongoing');
        $totalFinished = (int) $finalCollection->sum('finished');
        $totalLearningHours = round((float) $finalCollection->sum('learning_hours'), 2);
        $completionRate = $totalLearning > 0 ? round(($totalFinished / $totalLearning) * 100, 1) : 0.0;

        $summary = [
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
                'content' => [
                    'total_learning'       => $cuTotal,
                    'ongoing'              => $cuOngoing,
                    'finished'             => $cuFinished,
                    'total_learning_hours' => round($cuHours, 2),
                ],
                'playlist' => [
                    'total_learning'       => $pcuTotal,
                    'ongoing'              => $pcuOngoing,
                    'finished'             => $pcuFinished,
                    'total_learning_hours' => round($pcuHours, 2),
                ],
            ],
            'offline' => [
                'total_learning'       => $offlineTotal,
                'ongoing'              => $offlineOngoing,
                'finished'             => $offlineFinished,
                'total_learning_hours' => round($offlineHours, 2),
            ],
        ];

        $this->canonicalAggregatedDataCache = [
            'summary'        => $summary,
            'classification' => $finalCollection,
        ];

        return $this->canonicalAggregatedDataCache;
    }

    /**
     * Get distinct filter options (classifications and years).
     */
    public function getFilterOptions(): array
    {
        $classifications = [];
        $years = [];

        // Canonical classifications dropdown from master table + Compliance + Unclassification
        $masterList = self::getMasterClassifications();
        $classNames = [];
        foreach ($masterList as $m) {
            $classNames[] = $m['classification_name'];
        }
        sort($classNames);
        $classNames[] = 'Unclassification';
        $classifications = array_values(array_unique($classNames));

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
                    DB::raw("0 as recognized_duration_seconds"),
                    DB::raw("0 as afk_duration_seconds"),
                    DB::raw("CAST(NULL AS DECIMAL(10,2)) as training_hours"),
                    DB::raw("CAST(NULL AS CHAR) as start_date"),
                    DB::raw("CAST(NULL AS CHAR) as finish_date"),
                    DB::raw("CAST(NULL AS CHAR) as end_date"),
                    DB::raw("CAST(NULL AS CHAR) as start_time"),
                    DB::raw("CAST(NULL AS CHAR) as end_time"),
                    DB::raw("CAST(NULL AS CHAR) as requirement"),
                    DB::raw("0.0 as learning_hours"),
                    DB::raw("0 as progress"),
                    DB::raw("'' as status"),
                    DB::raw("CAST(NULL AS DECIMAL(10,2)) as assessment_score"),
                    DB::raw("CAST(NULL AS CHAR) as certificate"),
                    DB::raw("CAST(NULL AS UNSIGNED) as attendance"),
                    DB::raw("CAST(NULL AS CHAR) as attendance_reason"),
                    DB::raw("CAST(NULL AS CHAR) as bond_status"),
                    DB::raw("CAST(NULL AS UNSIGNED) as bond_duration"),
                    DB::raw("CAST(NULL AS CHAR) as bond_end_date"),
                    DB::raw("CAST(NULL AS DECIMAL(15,2)) as bond_amount_idr"),
                    DB::raw("CAST(NULL AS DECIMAL(15,2)) as bond_amount"),
                    DB::raw("CAST(NULL AS CHAR) as l1_program"),
                    DB::raw("CAST(NULL AS DECIMAL(10,2)) as l2_numerical"),
                    DB::raw("CAST(NULL AS CHAR) as l2_verbal"),
                    DB::raw("CAST(NULL AS DECIMAL(10,2)) as l3_numerical"),
                    DB::raw("CAST(NULL AS CHAR) as l3_verbal"),
                    DB::raw("CAST(NULL AS CHAR) as pic"),
                    DB::raw("CAST(NULL AS DECIMAL(10,2)) as `current_time`"),
                    DB::raw("CAST(NULL AS UNSIGNED) as actual_watch_seconds"),
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
                DB::raw("CASE WHEN unified.source = 'offline' THEN otu.grade ELSE u.jobgrade END as grade"),
                DB::raw("CASE WHEN unified.source = 'offline' THEN otu.job_position ELSE u.jobposition END as job_position"),
                DB::raw("COALESCE(otu.team, u.team, '') as team"),
                DB::raw("COALESCE(otu.department, u.department, '') as department"),
                DB::raw("COALESCE(otu.company, u.company_name, '') as company"),
                DB::raw("CASE
                    WHEN unified.source = 'offline' THEN COALESCE(ot.title, ot.course_name, 'Offline Training')
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
                    WHEN unified.source = 'offline' THEN ot.type
                    WHEN unified.source_type = 'playlist' THEN 'Playlist'
                    ELSE COALESCE(c.type, 'Document')
                END as type"),
                DB::raw("CASE
                    WHEN unified.source = 'offline' THEN 'Non KP-OWL'
                    WHEN unified.source_type = 'playlist' THEN 'Playlist'
                    ELSE 'All Content'
                END as category"),
                DB::raw("CASE WHEN unified.source = 'offline' THEN ot.level ELSE NULL END as level"),
                DB::raw("CASE WHEN unified.source = 'offline' THEN ot.batch ELSE NULL END as batch"),
                DB::raw("CASE
                    WHEN unified.source = 'offline' THEN " . (self::hasParticipantClassification() ? "COALESCE(NULLIF(TRIM(otu.main_classification), ''), NULLIF(TRIM(ot.main_classification), ''), 'Unclassified')" : "COALESCE(NULLIF(TRIM(ot.main_classification), ''), 'Unclassified')") . "
                    ELSE COALESCE(NULLIF(TRIM(cl.classification_name), ''), 'Unclassified')
                END as classification"),
                DB::raw("CASE
                    WHEN unified.source = 'offline' THEN " . (self::hasParticipantClassification() ? "COALESCE(NULLIF(TRIM(otu.sub_classification), ''), ot.sub_classification)" : "ot.sub_classification") . "
                    ELSE NULL
                END as sub_classification"),
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
                DB::raw("COALESCE(unified.duration_seconds, 0) as duration_seconds"),
                DB::raw("CASE WHEN unified.source = 'offline' THEN 0 ELSE COALESCE(unified.recognized_duration_seconds, 0) END as recognized_duration_seconds"),
                DB::raw("COALESCE(unified.afk_duration_seconds, 0) as afk_duration_seconds"),
                DB::raw("CASE
                    WHEN unified.source = 'offline' THEN ot.learning_hours
                    WHEN unified.source_type = 'playlist' THEN tp.learning_hours
                    ELSE c.learning_hours
                END as training_hours"),
                DB::raw("CASE
                    WHEN unified.source = 'offline' THEN DATE_FORMAT(ot.start_date, '%Y-%m-%d')
                    ELSE DATE_FORMAT(unified.created_at, '%Y-%m-%d')
                END as start_date"),
                DB::raw("DATE_FORMAT(unified.finish_date, '%Y-%m-%d') as finish_date"),
                DB::raw("CASE
                    WHEN unified.source = 'offline' THEN DATE_FORMAT(ot.end_date, '%Y-%m-%d')
                    ELSE DATE_FORMAT(unified.finish_date, '%Y-%m-%d')
                END as end_date"),
                DB::raw("CASE WHEN unified.source = 'offline' THEN ot.start_time ELSE NULL END as start_time"),
                DB::raw("CASE WHEN unified.source = 'offline' THEN ot.end_time ELSE NULL END as end_time"),
                DB::raw("CASE
                    WHEN unified.source = 'offline' THEN COALESCE(ot.requirement, 'Mandatory')
                    WHEN LOWER(TRIM(COALESCE(c.status, 'non mandatory'))) = 'mandatory' THEN 'Mandatory'
                    ELSE 'Non-Mandatory'
                END as requirement"),
                DB::raw("COALESCE(ROUND(unified.learning_hours, 2), 0) as learning_hours"),
                DB::raw("COALESCE(unified.progress, 0) as progress"),
                DB::raw("CASE
                    WHEN unified.finish = '1' OR unified.progress >= 100 THEN 'Finished'
                    WHEN unified.progress > 0 THEN 'Ongoing'
                    ELSE 'Not Started'
                END as status"),
                DB::raw("CASE WHEN unified.source = 'offline' THEN otu.assessment_score ELSE NULL END as assessment_score"),
                DB::raw("CASE WHEN unified.source = 'offline' THEN otu.certificate ELSE NULL END as certificate"),
                DB::raw("CASE WHEN unified.source = 'offline' THEN DATE_FORMAT(otu.certificate_issued_date, '%Y-%m-%d') ELSE NULL END as certificate_issued_date"),
                DB::raw("CASE WHEN unified.source = 'offline' THEN DATE_FORMAT(otu.certificate_expired_date, '%Y-%m-%d') ELSE NULL END as certificate_expired_date"),
                DB::raw("CASE WHEN unified.source = 'offline' THEN otu.attendance ELSE NULL END as attendance"),
                DB::raw("CASE WHEN unified.source = 'offline' THEN otu.attendance_reason ELSE NULL END as attendance_reason"),
                DB::raw("CASE WHEN unified.source = 'offline' THEN otu.bond_status ELSE NULL END as bond_status"),
                DB::raw("CASE WHEN unified.source = 'offline' THEN otu.bond_duration ELSE NULL END as bond_duration"),
                DB::raw("CASE WHEN unified.source = 'offline' THEN DATE_FORMAT(otu.bond_end_date, '%Y-%m-%d') ELSE NULL END as bond_end_date"),
                DB::raw("CASE WHEN unified.source = 'offline' THEN otu.bond_amount_idr ELSE NULL END as bond_amount_idr"),
                DB::raw("CASE WHEN unified.source = 'offline' THEN otu.bond_amount_idr ELSE NULL END as bond_amount"),
                DB::raw("CASE WHEN unified.source = 'offline' THEN otu.l1_program ELSE NULL END as l1_program"),
                DB::raw("CASE WHEN unified.source = 'offline' THEN otu.l2_numerical ELSE NULL END as l2_numerical"),
                DB::raw("CASE WHEN unified.source = 'offline' THEN otu.l2_verbal ELSE NULL END as l2_verbal"),
                DB::raw("CASE WHEN unified.source = 'offline' THEN otu.l3_numerical ELSE NULL END as l3_numerical"),
                DB::raw("CASE WHEN unified.source = 'offline' THEN otu.l3_verbal ELSE NULL END as l3_verbal"),
                DB::raw("CASE WHEN unified.source = 'offline' THEN otu.pic ELSE NULL END as pic"),
                'unified.current_time',
                'unified.actual_watch_seconds',
                'unified.afk_duration_seconds',
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
            ->leftJoin('content as c', 'c.id', '=', 'cu.content_id')
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
                DB::raw("CASE
                    WHEN c.duration IS NULL OR TRIM(c.duration) = '' THEN 0
                    WHEN LENGTH(c.duration) - LENGTH(REPLACE(c.duration, ':', '')) = 2 THEN TIME_TO_SEC(c.duration)
                    ELSE TIME_TO_SEC(CONCAT('00:', c.duration))
                END as duration_seconds"),
                DB::raw("CASE
                    WHEN (CASE
                        WHEN c.duration IS NULL OR TRIM(c.duration) = '' THEN 0
                        WHEN LENGTH(c.duration) - LENGTH(REPLACE(c.duration, ':', '')) = 2 THEN TIME_TO_SEC(c.duration)
                        ELSE TIME_TO_SEC(CONCAT('00:', c.duration))
                    END) > 0 THEN
                        LEAST(
                            CASE
                                WHEN cu.actual_watch_seconds > 0 THEN cu.actual_watch_seconds
                                WHEN cu.current_time > 0 THEN cu.current_time
                                WHEN cu.finish = '1' OR cu.progress >= 100 THEN (CASE
                                    WHEN c.duration IS NULL OR TRIM(c.duration) = '' THEN 0
                                    WHEN LENGTH(c.duration) - LENGTH(REPLACE(c.duration, ':', '')) = 2 THEN TIME_TO_SEC(c.duration)
                                    ELSE TIME_TO_SEC(CONCAT('00:', c.duration))
                                END)
                                WHEN cu.progress > 0 THEN ROUND((cu.progress / 100) * (CASE
                                    WHEN c.duration IS NULL OR TRIM(c.duration) = '' THEN 0
                                    WHEN LENGTH(c.duration) - LENGTH(REPLACE(c.duration, ':', '')) = 2 THEN TIME_TO_SEC(c.duration)
                                    ELSE TIME_TO_SEC(CONCAT('00:', c.duration))
                                END))
                                WHEN cu.learning_hours > 0 THEN ROUND(cu.learning_hours * 3600)
                                ELSE 0
                            END,
                            (CASE
                                WHEN c.duration IS NULL OR TRIM(c.duration) = '' THEN 0
                                WHEN LENGTH(c.duration) - LENGTH(REPLACE(c.duration, ':', '')) = 2 THEN TIME_TO_SEC(c.duration)
                                ELSE TIME_TO_SEC(CONCAT('00:', c.duration))
                            END) * 2
                        )
                    ELSE
                        CASE
                            WHEN cu.actual_watch_seconds > 0 THEN cu.actual_watch_seconds
                            WHEN cu.current_time > 0 THEN cu.current_time
                            WHEN cu.learning_hours > 0 THEN ROUND(cu.learning_hours * 3600)
                            ELSE 0
                        END
                END as recognized_duration_seconds"),
                'cu.current_time',
                'cu.actual_watch_seconds',
                DB::raw("COALESCE(cu.afk_duration_seconds, 0) as afk_duration_seconds"),
            ]);

        $this->applyOnlineSubqueryFilters($q, $filters, 'cu');

        return $q;
    }

    /**
     * Build Online Playlist subquery (grouped 1 row per user per playlist).
     */
    protected function buildPlaylistQuery(array $filters): Builder
    {
        $q = DB::table('playlist_content_users as pcu')
            ->leftJoin('content as c', 'c.id', '=', 'pcu.content_id')
            ->select([
                DB::raw('MIN(pcu.id) as source_id'),
                DB::raw("'online' as source"),
                DB::raw("'playlist' as source_type"),
                'pcu.user_id',
                DB::raw('MIN(pcu.content_id) as content_id'),
                'pcu.trainingplan_id',
                DB::raw('CAST(NULL AS UNSIGNED) as offline_training_user_id'),
DB::raw("ROUND(AVG(COALESCE(pcu.progress, 0))) as progress"),
                DB::raw("CASE WHEN COUNT(pcu.id) > 0 AND SUM(CASE WHEN pcu.finish = '1' OR pcu.progress >= 100 THEN 1 ELSE 0 END) = COUNT(pcu.id) THEN '1' ELSE '0' END as finish"),
                DB::raw("COALESCE(SUM(pcu.learning_hour), 0) as learning_hours"),
                DB::raw("MIN(pcu.created_at) as created_at"),
                DB::raw("MAX(CASE WHEN pcu.finish = '1' OR pcu.progress >= 100 THEN pcu.completed_at ELSE NULL END) as finish_date"),
                DB::raw("SUM(
                    CASE
                        WHEN (pcu.progress > 0 OR pcu.finish = '1' OR pcu.learning_hour > 0 OR pcu.actual_watch_seconds > 0) THEN
                            CASE
                                WHEN pcu.duration_seconds > 0 THEN pcu.duration_seconds
                                WHEN c.duration IS NULL OR TRIM(c.duration) = '' THEN 0
                                WHEN LENGTH(c.duration) - LENGTH(REPLACE(c.duration, ':', '')) = 2 THEN TIME_TO_SEC(c.duration)
                                ELSE TIME_TO_SEC(CONCAT('00:', c.duration))
                            END
                        ELSE 0
                    END
                ) as duration_seconds"),
                DB::raw("SUM(
                    CASE
                        WHEN (pcu.progress > 0 OR pcu.finish = '1' OR pcu.learning_hour > 0 OR pcu.actual_watch_seconds > 0) THEN
                            CASE
                                WHEN (CASE
                                    WHEN pcu.duration_seconds > 0 THEN pcu.duration_seconds
                                    WHEN c.duration IS NULL OR TRIM(c.duration) = '' THEN 0
                                    WHEN LENGTH(c.duration) - LENGTH(REPLACE(c.duration, ':', '')) = 2 THEN TIME_TO_SEC(c.duration)
                                    ELSE TIME_TO_SEC(CONCAT('00:', c.duration))
                                END) > 0 THEN
                                    LEAST(
                                        CASE
                                            WHEN pcu.actual_watch_seconds > 0 THEN pcu.actual_watch_seconds
                                            WHEN pcu.current_time > 0 THEN pcu.current_time
                                            WHEN pcu.finish = '1' OR pcu.progress >= 100 THEN (CASE
                                                WHEN pcu.duration_seconds > 0 THEN pcu.duration_seconds
                                                WHEN c.duration IS NULL OR TRIM(c.duration) = '' THEN 0
                                                WHEN LENGTH(c.duration) - LENGTH(REPLACE(c.duration, ':', '')) = 2 THEN TIME_TO_SEC(c.duration)
                                                ELSE TIME_TO_SEC(CONCAT('00:', c.duration))
                                            END)
                                            WHEN pcu.progress > 0 THEN ROUND((pcu.progress / 100) * (CASE
                                                WHEN pcu.duration_seconds > 0 THEN pcu.duration_seconds
                                                WHEN c.duration IS NULL OR TRIM(c.duration) = '' THEN 0
                                                WHEN LENGTH(c.duration) - LENGTH(REPLACE(c.duration, ':', '')) = 2 THEN TIME_TO_SEC(c.duration)
                                                ELSE TIME_TO_SEC(CONCAT('00:', c.duration))
                                            END))
                                            WHEN pcu.learning_hour > 0 THEN ROUND(pcu.learning_hour * 3600)
                                            ELSE 0
                                        END,
                                        (CASE
                                            WHEN pcu.duration_seconds > 0 THEN pcu.duration_seconds
                                            WHEN c.duration IS NULL OR TRIM(c.duration) = '' THEN 0
                                            WHEN LENGTH(c.duration) - LENGTH(REPLACE(c.duration, ':', '')) = 2 THEN TIME_TO_SEC(c.duration)
                                            ELSE TIME_TO_SEC(CONCAT('00:', c.duration))
                                        END) * 2
                                    )
                                ELSE
                                    CASE
                                        WHEN pcu.actual_watch_seconds > 0 THEN pcu.actual_watch_seconds
                                        WHEN pcu.current_time > 0 THEN pcu.current_time
                                        WHEN pcu.learning_hour > 0 THEN ROUND(pcu.learning_hour * 3600)
                                        ELSE 0
                                    END
                            END
                        ELSE 0
                    END
                ) as recognized_duration_seconds"),
                DB::raw("SUM(COALESCE(pcu.current_time, 0)) as `current_time`"),
                DB::raw("SUM(COALESCE(pcu.actual_watch_seconds, 0)) as actual_watch_seconds"),
                DB::raw("SUM(COALESCE(pcu.afk_duration_seconds, 0)) as afk_duration_seconds"),
            ])
            ->groupBy(['pcu.user_id', 'pcu.trainingplan_id']);

        $this->applyPlaylistSubqueryFilters($q, $filters);

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
            $categorySlug = $classification;
            $classificationSlug = str_replace('-', ' ', strtolower($categorySlug));
            $resolvedClass = RecordAllController::resolveClassification($categorySlug, false);
            $resolvedName = $resolvedClass['name'] ?? $categorySlug;

            if (strtolower($classification) === 'unclassified' || strtolower($classification) === 'unclassification' || ($resolvedClass['is_unclassified'] ?? false)) {
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
                    ->where(function ($q) use ($categorySlug, $resolvedClass, $resolvedName, $classificationSlug) {
                        $q->where('classification.slug', $categorySlug)
                          ->orWhere('classification.slug', $resolvedClass['slug'])
                          ->orWhere('classification.slug', Str::slug($categorySlug))
                          ->orWhere('classification.classification_name', $resolvedName)
                          ->orWhere('classification.classification_name', $categorySlug)
                          ->orWhereRaw('LOWER(classification.classification_name) = ?', [strtolower($resolvedName)])
                          ->orWhereRaw('LOWER(classification.classification_name) = ?', [$classificationSlug])
                          ->orWhereRaw('LOWER(classification.classification_name) = ?', [strtolower($categorySlug)]);
                    })
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
        $employeeParam = $filters['employee_id'] ?? ($filters['employee'] ?? ($filters['employees'] ?? null));
        $search = trim((string) (is_array($filters['search'] ?? null) ? ($filters['search']['value'] ?? '') : ($filters['search'] ?? '')));
        if ($search !== '' && empty($employeeParam) && $title === '') {
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
     * Apply pushdown filters to Playlist subquery (grouped by user & playlist).
     */
    protected function applyPlaylistSubqueryFilters(Builder $q, array $filters): void
    {
        // 1. Exact participant or employee filter
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
            $q->whereIn('pcu.user_id', !empty($matchedUserIds) ? $matchedUserIds : [-1]);
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
                $q->whereIn('pcu.user_id', !empty($userIds) ? $userIds : [-1]);
            } elseif (is_string($employeeParam) && trim($employeeParam) !== '' && trim($employeeParam) !== 'all') {
                $employee = trim($employeeParam);
                $userIds = DB::table('users')
                    ->where('id_emp', 'like', "%{$employee}%")
                    ->orWhere('name', 'like', "%{$employee}%")
                    ->pluck('id')
                    ->toArray();
                $q->whereIn('pcu.user_id', !empty($userIds) ? $userIds : [-1]);
            }
        }

        // 2. Playlist Title Filter
        $rawTitle = $filters['learning_title'] ?? ($filters['title'] ?? '');
        $title = is_string($rawTitle) ? trim($rawTitle) : '';
        if (is_array($rawTitle)) {
            $titleList = array_values(array_filter(array_map('trim', $rawTitle), fn($t) => $t !== '' && strtolower($t) !== 'all'));
            if (!empty($titleList)) {
                $tpIds = DB::table('trainingplan')->whereIn('title', $titleList)->pluck('id')->toArray();
                $q->whereIn('pcu.trainingplan_id', !empty($tpIds) ? $tpIds : [-1]);
            }
        } elseif ($title !== '' && strtolower($title) !== 'all') {
            $tpIds = DB::table('trainingplan')->where('title', 'like', "%{$title}%")->pluck('id')->toArray();
            $q->whereIn('pcu.trainingplan_id', !empty($tpIds) ? $tpIds : [-1]);
        }

        // 3. Classification Filter
        $classification = trim((string) ($filters['classification'] ?? 'all'));
        if ($classification !== '' && $classification !== 'all') {
            $categorySlug = $classification;
            $classificationSlug = str_replace('-', ' ', strtolower($categorySlug));
            $resolvedClass = RecordAllController::resolveClassification($categorySlug, false);
            $resolvedName = $resolvedClass['name'] ?? $categorySlug;

            if (strtolower($classification) === 'unclassified' || strtolower($classification) === 'unclassification' || ($resolvedClass['is_unclassified'] ?? false)) {
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
                    ->where(function ($qSub) use ($categorySlug, $resolvedClass, $resolvedName, $classificationSlug) {
                        $qSub->where('classification.slug', $categorySlug)
                          ->orWhere('classification.slug', $resolvedClass['slug'])
                          ->orWhere('classification.slug', Str::slug($categorySlug))
                          ->orWhere('classification.classification_name', $resolvedName)
                          ->orWhere('classification.classification_name', $categorySlug)
                          ->orWhereRaw('LOWER(classification.classification_name) = ?', [strtolower($resolvedName)])
                          ->orWhereRaw('LOWER(classification.classification_name) = ?', [$classificationSlug])
                          ->orWhereRaw('LOWER(classification.classification_name) = ?', [strtolower($categorySlug)]);
                    })
                    ->pluck('content.id')
                    ->toArray();
            }
            $q->whereIn('pcu.content_id', !empty($classContentIds) ? $classContentIds : [-1]);
        }

        // 4. Year Filter
        $year = $filters['year'] ?? ($filters['years'] ?? 'all');
        if (is_array($year) && !empty($year)) {
            $yearInts = array_map('intval', array_filter($year, 'is_numeric'));
            if (!empty($yearInts)) {
                $q->where(function ($sub) use ($yearInts) {
                    $sub->whereIn(DB::raw('YEAR(pcu.created_at)'), $yearInts)
                        ->orWhereIn(DB::raw('YEAR(pcu.completed_at)'), $yearInts)
                        ->orWhereIn(DB::raw('YEAR(pcu.updated_at)'), $yearInts);
                });
            }
        } elseif (is_string($year) && $year !== '' && $year !== 'all' && is_numeric($year)) {
            $q->where(function ($sub) use ($year) {
                $sub->whereYear('pcu.created_at', (int) $year)
                    ->orWhereYear('pcu.completed_at', (int) $year)
                    ->orWhereYear('pcu.updated_at', (int) $year);
            });
        }

        // 5. Department, Company, Division, Team Filters
        $department = $filters['department'] ?? ($filters['departments'] ?? 'all');
        if (is_array($department) && !empty($department)) {
            $deptUserIds = DB::table('users')->whereIn('department', $department)->pluck('id')->toArray();
            $q->whereIn('pcu.user_id', !empty($deptUserIds) ? $deptUserIds : [-1]);
        } elseif (is_string($department) && filled($department) && $department !== 'all') {
            $deptUserIds = DB::table('users')->where('department', trim($department))->pluck('id')->toArray();
            $q->whereIn('pcu.user_id', !empty($deptUserIds) ? $deptUserIds : [-1]);
        }

        $company = $filters['company'] ?? ($filters['companies'] ?? 'all');
        if (is_array($company) && !empty($company)) {
            $compUserIds = DB::table('users')->whereIn('company_name', $company)->pluck('id')->toArray();
            $q->whereIn('pcu.user_id', !empty($compUserIds) ? $compUserIds : [-1]);
        } elseif (is_string($company) && filled($company) && $company !== 'all') {
            $compUserIds = DB::table('users')->where('company_name', trim($company))->pluck('id')->toArray();
            $q->whereIn('pcu.user_id', !empty($compUserIds) ? $compUserIds : [-1]);
        }

        $division = $filters['division'] ?? ($filters['divisions'] ?? 'all');
        if (is_array($division) && !empty($division)) {
            $divUserIds = DB::table('users')->whereIn('division', $division)->pluck('id')->toArray();
            $q->whereIn('pcu.user_id', !empty($divUserIds) ? $divUserIds : [-1]);
        } elseif (is_string($division) && filled($division) && $division !== 'all') {
            $divUserIds = DB::table('users')->where('division', trim($division))->pluck('id')->toArray();
            $q->whereIn('pcu.user_id', !empty($divUserIds) ? $divUserIds : [-1]);
        }

        $team = $filters['team'] ?? ($filters['teams'] ?? 'all');
        if (is_array($team) && !empty($team)) {
            $teamUserIds = DB::table('users')->whereIn('team', $team)->pluck('id')->toArray();
            $q->whereIn('pcu.user_id', !empty($teamUserIds) ? $teamUserIds : [-1]);
        } elseif (is_string($team) && filled($team) && $team !== 'all') {
            $teamUserIds = DB::table('users')->where('team', 'like', '%' . trim($team) . '%')->pluck('id')->toArray();
            $q->whereIn('pcu.user_id', !empty($teamUserIds) ? $teamUserIds : [-1]);
        }

        // 6. General Search
        $employeeParam = $filters['employee_id'] ?? ($filters['employee'] ?? ($filters['employees'] ?? null));
        $search = trim((string) (is_array($filters['search'] ?? null) ? ($filters['search']['value'] ?? '') : ($filters['search'] ?? '')));
        if ($search !== '' && empty($employeeParam) && $title === '') {
            $searchUserIds = DB::table('users')
                ->where('name', 'like', "%{$search}%")
                ->orWhere('id_emp', 'like', "%{$search}%")
                ->pluck('id')
                ->toArray();

            $searchTpIds = DB::table('trainingplan')
                ->where('title', 'like', "%{$search}%")
                ->pluck('id')
                ->toArray();

            $q->where(function ($sub) use ($searchUserIds, $searchTpIds) {
                $matched = false;
                if (!empty($searchUserIds)) {
                    $sub->whereIn('pcu.user_id', $searchUserIds);
                    $matched = true;
                }
                if (!empty($searchTpIds)) {
                    if ($matched) {
                        $sub->orWhereIn('pcu.trainingplan_id', $searchTpIds);
                    } else {
                        $sub->whereIn('pcu.trainingplan_id', $searchTpIds);
                        $matched = true;
                    }
                }
                if (!$matched) {
                    $sub->whereRaw('1 = 0');
                }
            });
        }

        // 7. Status Filter (Applied via HAVING on the aggregated playlist status)
        $status = strtolower(trim((string) ($filters['status'] ?? 'all')));
        if ($status !== '' && $status !== 'all') {
            if ($status === 'finished' || $status === 'finish') {
                $q->havingRaw("COUNT(pcu.id) > 0 AND SUM(CASE WHEN pcu.finish = '1' OR pcu.progress >= 100 THEN 1 ELSE 0 END) = COUNT(pcu.id)");
            } elseif ($status === 'ongoing') {
                $q->havingRaw("NOT (COUNT(pcu.id) > 0 AND SUM(CASE WHEN pcu.finish = '1' OR pcu.progress >= 100 THEN 1 ELSE 0 END) = COUNT(pcu.id)) AND (AVG(COALESCE(pcu.progress, 0)) > 0 OR SUM(CASE WHEN pcu.progress > 0 THEN 1 ELSE 0 END) > 0)");
            } elseif ($status === 'not_started') {
                $q->havingRaw("NOT (COUNT(pcu.id) > 0 AND SUM(CASE WHEN pcu.finish = '1' OR pcu.progress >= 100 THEN 1 ELSE 0 END) = COUNT(pcu.id)) AND AVG(COALESCE(pcu.progress, 0)) <= 0");
            }
        }

        // 8. Progress Range Filter (Applied via HAVING on the aggregated playlist progress)
        $progressRange = trim((string) ($filters['progress_range'] ?? 'all'));
        if ($progressRange !== '' && $progressRange !== 'all') {
            if ($progressRange === '0') {
                $q->havingRaw("AVG(COALESCE(pcu.progress, 0)) <= 0");
            } elseif ($progressRange === '1-25') {
                $q->havingRaw("AVG(COALESCE(pcu.progress, 0)) >= 1 AND AVG(COALESCE(pcu.progress, 0)) <= 25");
            } elseif ($progressRange === '26-50') {
                $q->havingRaw("AVG(COALESCE(pcu.progress, 0)) >= 26 AND AVG(COALESCE(pcu.progress, 0)) <= 50");
            } elseif ($progressRange === '51-75') {
                $q->havingRaw("AVG(COALESCE(pcu.progress, 0)) >= 51 AND AVG(COALESCE(pcu.progress, 0)) <= 75");
            } elseif ($progressRange === '76-99') {
                $q->havingRaw("AVG(COALESCE(pcu.progress, 0)) >= 76 AND AVG(COALESCE(pcu.progress, 0)) <= 99");
            } elseif ($progressRange === '100') {
                $q->havingRaw("COUNT(pcu.id) > 0 AND SUM(CASE WHEN pcu.finish = '1' OR pcu.progress >= 100 THEN 1 ELSE 0 END) = COUNT(pcu.id)");
            }
        }

        // 9. Learning Hours Range Filter (Applied via HAVING on SUM(pcu.learning_hour))
        $hoursRange = trim((string) ($filters['learning_hours_range'] ?? ($filters['learning_hours'] ?? ($filters['hours_range'] ?? 'all'))));
        if ($hoursRange !== '' && $hoursRange !== 'all') {
            if ($hoursRange === 'lt05') {
                $q->havingRaw("COALESCE(SUM(pcu.learning_hour), 0) < 0.5");
            } elseif ($hoursRange === '05-1') {
                $q->havingRaw("COALESCE(SUM(pcu.learning_hour), 0) >= 0.5 AND COALESCE(SUM(pcu.learning_hour), 0) < 1.0");
            } elseif ($hoursRange === '1-2') {
                $q->havingRaw("COALESCE(SUM(pcu.learning_hour), 0) >= 1.0 AND COALESCE(SUM(pcu.learning_hour), 0) < 2.0");
            } elseif ($hoursRange === '2-4') {
                $q->havingRaw("COALESCE(SUM(pcu.learning_hour), 0) >= 2.0 AND COALESCE(SUM(pcu.learning_hour), 0) < 4.0");
            } elseif ($hoursRange === 'gte4') {
                $q->havingRaw("COALESCE(SUM(pcu.learning_hour), 0) >= 4.0");
            }
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
                DB::raw("COALESCE(ot.duration_seconds, 0) as duration_seconds"),
                DB::raw("0 as recognized_duration_seconds"),
                DB::raw("CAST(NULL AS DECIMAL(10,2)) as `current_time`"),
                DB::raw('CAST(NULL AS UNSIGNED) as actual_watch_seconds'),
                DB::raw("ROUND(COALESCE(otu.learning_hours, ot.learning_hours, 0) * 3600) as afk_duration_seconds"),
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

            if ($pUserId && empty($pEmpId)) {
                $pEmpId = DB::table('users')->where('id', $pUserId)->value('id_emp');
            } elseif ($pEmpId && empty($pUserId)) {
                $pUserId = DB::table('users')->where('id_emp', $pEmpId)->value('id');
            }

            $q->where(function ($sub) use ($pUserId, $pEmpId, $pName) {
                if (!empty($pUserId) && !empty($pEmpId)) {
                    $sub->where('otu.user_id', (int) $pUserId)
                        ->orWhere('otu.employee_id', $pEmpId);
                } elseif (!empty($pEmpId)) {
                    $sub->where('otu.employee_id', $pEmpId);
                } elseif (!empty($pUserId)) {
                    $sub->where('otu.user_id', (int) $pUserId);
                } elseif (!empty($pName)) {
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
            $categorySlug = $classification;
            $classificationSlug = str_replace('-', ' ', strtolower($categorySlug));
            $resolvedClass = RecordAllController::resolveClassification($categorySlug, false);
            $resolvedName = $resolvedClass['name'] ?? $categorySlug;

            $classCol = self::hasParticipantClassification()
                ? "COALESCE(NULLIF(TRIM(otu.main_classification), ''), ot.main_classification)"
                : "ot.main_classification";
            if (strtolower($classification) === 'unclassified' || strtolower($classification) === 'unclassification' || ($resolvedClass['is_unclassified'] ?? false)) {
                $knownMasters = [
                    'Leadership', 'Basic & Common Duties', 'Basic Common Duties',
                    'Job Functional', 'Maintenance', 'Language', 'Innovation',
                    'SHE', 'ISO Standard', 'Technology', 'Other', 'Basic mindset', 'Compliance'
                ];
                $q->where(function ($sub) use ($knownMasters, $classCol) {
                    $sub->whereNull(DB::raw($classCol))
                        ->orWhereRaw("TRIM({$classCol}) = ''")
                        ->orWhereRaw("{$classCol} IN ('Audit Isolated Classification', 'Unclassified', 'Unclassification')")
                        ->orWhereRaw("{$classCol} NOT IN ('" . implode("', '", $knownMasters) . "')");
                });
            } else {
                $q->where(function ($q) use ($categorySlug, $resolvedClass, $resolvedName, $classificationSlug, $classCol) {
                    if (($resolvedClass['slug'] ?? '') === 'basic-common-duties' || $resolvedName === 'Basic & Common Duties' || $categorySlug === 'basic-common-duties') {
                        $q->whereRaw("{$classCol} IN ('Basic & Common Duties', 'Basic Common Duties')");
                    } else {
                        $q->where(DB::raw($classCol), $resolvedName)
                          ->orWhere(DB::raw($classCol), $categorySlug)
                          ->orWhereRaw("LOWER({$classCol}) = ?", [strtolower($resolvedName)])
                          ->orWhereRaw("LOWER({$classCol}) = ?", [$classificationSlug])
                          ->orWhereRaw("LOWER({$classCol}) = ?", [strtolower($categorySlug)])
                          ->orWhereRaw("LOWER(REPLACE(REPLACE({$classCol}, ' ', '-'), '&', 'and')) = ?", [strtolower(Str::slug($resolvedClass['slug'] ?? $categorySlug))])
                          ->orWhereRaw("LOWER(REPLACE({$classCol}, ' ', '-')) = ?", [strtolower(Str::slug($resolvedClass['slug'] ?? $categorySlug))]);
                    }
                });
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
                    ->orWhere('ot.course_name', 'like', "%{$search}%");
                if (self::hasParticipantClassification()) {
                    $sub->orWhereRaw("COALESCE(NULLIF(TRIM(otu.main_classification), ''), ot.main_classification) LIKE ?", ["%{$search}%"]);
                } else {
                    $sub->orWhere('ot.main_classification', 'like', "%{$search}%");
                }
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

        $tpdRows = DB::table('trainingplandetail as tpd')
            ->join('trainingplan as tp', 'tp.id', '=', 'tpd.trainingplan_id')
            ->whereNull('tp.deleted_at')
            ->orderBy('tpd.id', 'asc')
            ->select('tpd.content_id', 'tp.id as tp_id', 'tp.title as tp_title')
            ->get();
        $tpdCache = [];
        foreach ($tpdRows as $tpd) {
            if (!isset($tpdCache[$tpd->content_id])) {
                $tpdCache[$tpd->content_id] = [
                    'id'    => (int) $tpd->tp_id,
                    'title' => (string) $tpd->tp_title,
                ];
            }
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
                    return '<span class="badge bg-blue-100 text-blue-700 rounded-pill px-2.5 py-1" style="background-color: #dbeafe !important; color: #1d4ed8 !important; font-weight: 600; font-size: 0.75rem;">KP OWL</span>';
                }
                return '<span class="badge bg-amber-100 text-amber-700 rounded-pill px-2.5 py-1" style="background-color: #fef3c7 !important; color: #b45309 !important; font-weight: 600; font-size: 0.75rem;">Non KP OWL</span>';
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
                return number_format((float) ($row->learning_hours ?? 0), 2) . ' h';
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
                return is_numeric($row->assessment_score) ? (string) (int) round((float) $row->assessment_score) : '-';
            })
            ->addColumn('certificate_badge', function ($row) {
                if (empty($row->certificate) || $row->certificate === '-') {
                    return '<span class="text-muted">-</span>';
                }
                $cert = e($row->certificate);
                return '<span class="badge bg-blue-lt text-primary fw-bold" style="font-size: 0.725rem;" title="' . $cert . '"><i class="ti ti-file-certificate me-1"></i> Available</span>';
            })
            ->addColumn('certificate_availability', function ($row) {
                $isAvailable = (!empty($row->certificate) && $row->certificate !== '-');
                if (!$isAvailable && ($row->source ?? '') === 'online') {
                    $isAvailable = (($row->status ?? '') === 'Finished' || (float)($row->progress ?? 0) >= 100);
                }
                if ($isAvailable) {
                    return '<span class="badge bg-blue-lt text-primary fw-bold px-2 py-1" style="font-size: 0.725rem;"><i class="ti ti-file-certificate me-1"></i> Available</span>';
                }
                return '<span class="text-muted small">-</span>';
            })
            ->addColumn('attendance_badge', function ($row) {
                $val = (string) ($row->attendance ?? '');
                $attendance = in_array(strtolower(trim($val)), ['present', 'yes', '1', 'true', 'finished', 'completed', 'hadir'], true)
                    || (($row->source ?? '') === 'online' && ((float) ($row->progress ?? 0) >= 100))
                    ? 'Yes' : 'No';
                if ($attendance === 'Yes') {
                    return '<span class="badge bg-success-lt text-success fw-bold px-2 py-1">Yes</span>';
                }
                return '<span class="badge bg-danger-lt text-danger fw-bold px-2 py-1">No</span>';
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
            ->addColumn('playlist', function ($row) use ($tpdCache) {
                if ($row->source === 'offline') {
                    return '<span class="text-muted small">-</span>';
                }
                $playlistTitle = !empty($row->playlist_title) ? $row->playlist_title : null;
                $tpId = !empty($row->trainingplan_id) ? $row->trainingplan_id : null;
                if (!$playlistTitle && !empty($row->content_id) && isset($tpdCache[$row->content_id])) {
                    $playlistTitle = $tpdCache[$row->content_id]['title'];
                    $tpId = $tpdCache[$row->content_id]['id'];
                }
                if (!empty($playlistTitle)) {
                    if (!empty($tpId)) {
                        $url = route('learning.manage.playlist', ['id' => $tpId]);
                        return '<a href="' . e($url) . '" class="text-primary fw-semibold" title="' . e($playlistTitle) . '"><i class="bi bi-collection-play me-1"></i>' . e($playlistTitle) . '</a>';
                    }
                    return e($playlistTitle);
                }
                return '<span class="text-muted small">-</span>';
            })
            ->addColumn('type', function ($row) {
                if ($row->source === 'offline') {
                    return '<span class="text-muted small">-</span>';
                }
                $t = $row->type ?? 'Document';
                return htmlspecialchars($t ?: 'Document');
            })
            ->addColumn('category', function ($row) use ($tpdCache) {
                return $this->categoryBadge($row, $tpdCache);
            })
            ->addColumn('course_title', function ($row) {
                $title = htmlspecialchars($row->learning_title ?: '-');
                $rawReq = $row->requirement ?? ($row->status ?? 'Non-Mandatory');
                $req = !empty($rawReq) ? htmlspecialchars($rawReq) : 'Non-Mandatory';
                return '<div class="d-flex flex-column align-items-center justify-content-center text-center w-100">'
                    . '<span class="fw-bold text-dark text-center fs-6 mb-1">' . $title . '</span>'
                    . '<small class="text-muted text-center fs-7">Requirement: <span class="fw-semibold">' . $req . '</span></small>'
                    . '</div>';
            })
            ->addColumn('employee_id', function ($row) {
                return htmlspecialchars($row->employee_id ?: '-');
            })
            ->addColumn('employee_name', function ($row) {
                return htmlspecialchars($row->employee_name ?: '-');
            })
            ->addColumn('team', function ($row) {
                return htmlspecialchars($row->team ?: '-');
            })
            ->addColumn('department', function ($row) {
                return htmlspecialchars($row->department ?: '-');
            })
            ->addColumn('company', function ($row) {
                return htmlspecialchars($row->company ?: '-');
            })
            ->addColumn('main_classification', function ($row) {
                return htmlspecialchars($row->classification ?: '-');
            })
            ->addColumn('sub_classification', function ($row) {
                return htmlspecialchars($row->sub_classification ?: '-');
            })
            ->addColumn('delivery_method', function ($row) {
                if ($row->source === 'online') {
                    return 'Online';
                }
                return 'Offline';
            })
            ->addColumn('sub_delivery_method', function ($row) {
                if ($row->source === 'online') {
                    return 'KP OWL';
                }
                $sub = $row->sub_delivery_method ?? $row->training_type ?? $row->type ?? null;
                if (!$sub || $sub === '-' || strtolower(trim((string) $sub)) === 'offline') {
                    $rawDeliv = trim((string) ($row->delivery_method ?? ''));
                    if ($rawDeliv !== '' && strtolower($rawDeliv) !== 'offline') {
                        $sub = $rawDeliv;
                    }
                }
                return htmlspecialchars($sub ?: '-');
            })
            ->addColumn('provider', function ($row) {
                if ($row->source === 'online') {
                    return '<span class="badge bg-blue-lt text-blue">Internal</span>';
                }
                return htmlspecialchars($row->provider ?: '-');
            })
            ->addColumn('sub_provider', function ($row) {
                if ($row->source === 'online') {
                    return '<span class="text-muted small">-</span>';
                }
                return htmlspecialchars($row->sub_provider ?: '-');
            })
            ->addColumn('provider_name', function ($row) {
                if ($row->source === 'online') {
                    return 'KP OWL';
                }
                return htmlspecialchars($row->provider_name ?: '-');
            })
            ->addColumn('venue', function ($row) {
                if ($row->source === 'online') {
                    return 'Online LMS';
                }
                return htmlspecialchars($row->venue ?: '-');
            })
            ->addColumn('learning_hours', function ($row) {
                $hours = (float) ($row->training_hours ?? ($row->learning_hours ?? 0));
                return '<span class="fw-semibold">' . number_format($hours, 1) . 'h</span>';
            })
            ->addColumn('course_duration', function ($row) {
                $sec = (int) ($row->duration_seconds ?? 0);
                if ($sec <= 0) {
                    return '-';
                }
                $h = floor($sec / 3600);
                $m = floor(($sec % 3600) / 60);
                $s = $sec % 60;
                return sprintf('%02d:%02d:%02d', $h, $m, $s);
            })
            ->addColumn('recognized_duration', function ($row) {
                if (($row->source ?? '') === 'offline') {
                    return '-';
                }
                $sec = (int) ($row->recognized_duration_seconds ?? 0);
                if ($sec <= 0) {
                    $sec = app(\App\Services\Learning\LearningRecordRecognizedDurationService::class)->resolveFromRow($row);
                }
                $h = floor($sec / 3600);
                $m = floor(($sec % 3600) / 60);
                $s = $sec % 60;
                return sprintf('%02d:%02d:%02d', $h, $m, $s);
            })
            ->addColumn('total_recognized_duration', function ($row) {
                if (($row->source ?? '') === 'offline') {
                    return '-';
                }
                $sec = (int) ($row->recognized_duration_seconds ?? 0);
                if ($sec <= 0) {
                    $sec = app(\App\Services\Learning\LearningRecordRecognizedDurationService::class)->resolveFromRow($row);
                }
                $h = floor($sec / 3600);
                $m = floor(($sec % 3600) / 60);
                $s = $sec % 60;
                return sprintf('%02d:%02d:%02d', $h, $m, $s);
            })
            ->addColumn('grade', function ($row) {
                return htmlspecialchars($row->grade ?: '-');
            })
            ->addColumn('job_position', function ($row) {
                return htmlspecialchars($row->job_position ?: '-');
            })
            ->addColumn('sub_course_title', function ($row) {
                return htmlspecialchars($row->sub_course_name ?: '-');
            })
            ->addColumn('level', function ($row) {
                return htmlspecialchars($row->level ?: '-');
            })
            ->addColumn('batch', function ($row) {
                return htmlspecialchars($row->batch ?: '-');
            })
            ->addColumn('sub_classification', function ($row) {
                return htmlspecialchars($row->sub_classification ?: '-');
            })
            ->addColumn('delivery_method', function ($row) {
                $sourceRaw = strtolower(trim((string) ($row->source ?? '')));
                $isKpOwl = in_array($sourceRaw, ['online', 'kp owl', 'kp-owl', 'owl'], true);
                return $isKpOwl ? 'Online' : 'Offline';
            })
            ->addColumn('sub_delivery_method', function ($row) {
                $sourceRaw = strtolower(trim((string) ($row->source ?? '')));
                $isKpOwl = in_array($sourceRaw, ['online', 'kp owl', 'kp-owl', 'owl'], true);
                if ($isKpOwl) {
                    return 'KP OWL';
                }
                $sub = $row->sub_delivery_method ?? $row->training_type ?? $row->type ?? null;
                if (!$sub || $sub === '-' || strtolower(trim((string) $sub)) === 'offline') {
                    $rawDm = trim((string) ($row->delivery_method ?? ''));
                    if ($rawDm !== '' && strtolower($rawDm) !== 'offline') {
                        $sub = $rawDm;
                    }
                }
                return htmlspecialchars($sub ?: '-');
            })
            ->addColumn('provider', function ($row) {
                $sourceRaw = strtolower(trim((string) ($row->source ?? '')));
                $isKpOwl = in_array($sourceRaw, ['online', 'kp owl', 'kp-owl', 'owl'], true);
                return $isKpOwl ? 'Internal' : htmlspecialchars($row->provider ?: '-');
            })
            ->addColumn('sub_provider', function ($row) {
                $sourceRaw = strtolower(trim((string) ($row->source ?? '')));
                $isKpOwl = in_array($sourceRaw, ['online', 'kp owl', 'kp-owl', 'owl'], true);
                return $isKpOwl ? '-' : htmlspecialchars($row->sub_provider ?: '-');
            })
            ->addColumn('provider_name', function ($row) {
                $sourceRaw = strtolower(trim((string) ($row->source ?? '')));
                $isKpOwl = in_array($sourceRaw, ['online', 'kp owl', 'kp-owl', 'owl'], true);
                return $isKpOwl ? 'KP-OWL' : htmlspecialchars($row->provider_name ?: '-');
            })
            ->addColumn('venue', function ($row) {
                $sourceRaw = strtolower(trim((string) ($row->source ?? '')));
                $isKpOwl = in_array($sourceRaw, ['online', 'kp owl', 'kp-owl', 'owl'], true);
                return $isKpOwl ? 'Online LMS' : htmlspecialchars($row->venue ?: '-');
            })
            ->addColumn('instructor', function ($row) {
                return htmlspecialchars($row->instructor ?: '-');
            })
            ->addColumn('start_date', function ($row) {
                return !empty($row->start_date) ? date('Y-m-d', strtotime($row->start_date)) : '-';
            })
            ->addColumn('end_date', function ($row) {
                $d = $row->end_date ?? ($row->finish_date ?? null);
                return !empty($d) ? date('Y-m-d', strtotime($d)) : '-';
            })
            ->addColumn('start_time', function ($row) {
                return !empty($row->start_time) ? date('H:i:s', strtotime($row->start_time)) : '-';
            })
            ->addColumn('end_time', function ($row) {
                return !empty($row->end_time) ? date('H:i:s', strtotime($row->end_time)) : '-';
            })
            ->addColumn('attendance_reason', function ($row) {
                return htmlspecialchars($row->attendance_reason ?: '-');
            })
            ->addColumn('bond_status', function ($row) {
                return htmlspecialchars($row->bond_status ?: 'No');
            })
            ->addColumn('bond_duration', function ($row) {
                return !empty($row->bond_duration) ? htmlspecialchars($row->bond_duration . ' Months') : '-';
            })
            ->addColumn('bond_end_date', function ($row) {
                return !empty($row->bond_end_date) ? date('Y-m-d', strtotime($row->bond_end_date)) : '-';
            })
            ->addColumn('bond_amount', function ($row) {
                $idr = $row->bond_amount_idr ?? $row->bond_amount ?? null;
                return ($idr !== null && (float) $idr > 0) ? 'Rp ' . number_format((float) $idr, 0, ',', '.') : '-';
            })
            ->addColumn('l2_status', function ($row) {
                if (!empty($row->l2_verbal) && $row->l2_verbal !== '-') {
                    return htmlspecialchars($row->l2_verbal);
                }
                $s2 = is_numeric($row->assessment_score) ? (float) $row->assessment_score : (is_numeric($row->l2_numerical) ? (float) $row->l2_numerical : null);
                if ($s2 !== null) {
                    return $s2 >= 70 ? 'Passed' : 'Failed';
                }
                return '-';
            })
            ->addColumn('l3_status', function ($row) {
                return htmlspecialchars($row->l3_verbal ?: '-');
            })
            ->addColumn('certificate_availability', function ($row) {
                $isFinished = ((float) ($row->progress ?? 0) >= 100 || ($row->status ?? '') === 'Finished' || ($row->finish ?? '') === '1');
                $hasCert = (!empty($row->certificate) && $row->certificate !== '-') || $isFinished;
                return $hasCert ? 'Available' : '-';
            })
            ->addColumn('certificate_institution', function ($row) {
                if ($row->source === 'offline') {
                    return htmlspecialchars($row->certificate ?: '-');
                }
                $isFinished = ((float) ($row->progress ?? 0) >= 100 || ($row->status ?? '') === 'Finished' || ($row->finish ?? '') === '1');
                return $isFinished ? 'PT Krakatau Posco' : '-';
            })
            ->addColumn('certificate_issued_date', function ($row) {
                if ($row->source === 'offline') {
                    return !empty($row->certificate_issued_date) ? date('Y-m-d', strtotime($row->certificate_issued_date)) : '-';
                }
                $d = $row->finish_date ?? ($row->start_date ?? null);
                $isFinished = ((float) ($row->progress ?? 0) >= 100 || ($row->status ?? '') === 'Finished' || ($row->finish ?? '') === '1');
                return ($isFinished && !empty($d) && $d !== '-') ? date('Y-m-d', strtotime($d)) : '-';
            })
            ->addColumn('certificate_expired_date', function ($row) {
                if ($row->source === 'offline') {
                    return !empty($row->certificate_expired_date) ? date('Y-m-d', strtotime($row->certificate_expired_date)) : '-';
                }
                return '-';
            })
            ->addColumn('progress_percent', function ($row) {
                $progress = max(0, min(100, round((float) ($row->progress ?? 0))));
                $color = $progress >= 100 ? 'bg-success' : ($progress > 0 ? 'bg-warning' : 'bg-secondary');
                return '<div class="d-flex align-items-center gap-2" style="min-width: 85px;">
                    <div class="progress progress-xs flex-grow-1" style="width: 50px; height: 6px;">
                        <div class="progress-bar ' . $color . '" style="width: ' . $progress . '%"></div>
                    </div>
                    <span class="small fw-semibold text-muted">' . $progress . '%</span>
                </div>';
            })
            ->addColumn('progress_status', function ($row) {
                $progress = (float) ($row->progress ?? 0);
                if ($progress >= 100) {
                    return '<span class="badge bg-success-lt text-success fw-bold px-2 py-1">Finished</span>';
                } elseif ($progress > 0) {
                    return '<span class="badge bg-warning-lt text-warning fw-bold px-2 py-1">Ongoing</span>';
                }
                return '<span class="badge bg-secondary-lt fw-bold px-2 py-1">Not Started</span>';
            })
            ->addColumn('date_time_merged', function ($row) {
                return \App\Http\Controllers\Manage\Record\RecordOfflineController::renderMergedDateTime($row->start_date ?? null, $row->finish_date ?? ($row->end_date ?? null), $row->start_time ?? null, $row->end_time ?? null);
            })
            ->addColumn('date_merged', function ($row) {
                return \App\Http\Controllers\Manage\Record\RecordOfflineController::renderMergedDate($row->start_date ?? null, $row->finish_date ?? ($row->end_date ?? null));
            })
            ->addColumn('time_merged', function ($row) {
                return \App\Http\Controllers\Manage\Record\RecordOfflineController::renderMergedTime($row->start_time ?? null, $row->end_time ?? null);
            })
            ->addColumn('record_month', function ($row) {
                if ($row->source === 'online') {
                    $d = $row->finish_date ?? null;
                } else {
                    $d = $row->start_date ?: ($row->finish_date ?: ($row->record_date ?? null));
                }
                if (!empty($d) && $d !== '-') {
                    try {
                        return date('F', strtotime($d));
                    } catch (\Throwable $e) {
                        return '-';
                    }
                }
                return '-';
            })
            ->addColumn('requirement', function ($row) {
                $req = $row->requirement ?? ($row->status ?? '-');
                if (strtolower($req) === 'mandatory') {
                    return '<span class="badge bg-red-lt text-red">Mandatory</span>';
                }
                return htmlspecialchars($req);
            })
            ->addColumn('attendance', function ($row) {
                $val = (string) ($row->attendance ?? '');
                $attendance = in_array(strtolower(trim($val)), ['present', 'yes', '1', 'true', 'finished', 'completed', 'hadir'], true)
                    || (($row->source ?? '') === 'online' && ((float) ($row->progress ?? 0) >= 100))
                    ? 'Yes' : 'No';
                if ($attendance === 'Yes') {
                    return '<span class="badge bg-success-lt text-success fw-bold px-2 py-1">Yes</span>';
                }
                return '<span class="badge bg-danger-lt text-danger fw-bold px-2 py-1">No</span>';
            })
            ->addColumn('score_l1', function ($row) {
                if (isset($row->assessment_score) && is_numeric($row->assessment_score)) {
                    return '<span class="fw-semibold">' . number_format((float) $row->assessment_score, 1) . '</span>';
                }
                if (!empty($row->l1_program) && $row->l1_program !== '-') {
                    return htmlspecialchars($row->l1_program);
                }
                return '<span class="text-muted small">-</span>';
            })
            ->addColumn('score_l2', function ($row) {
                if (isset($row->l2_numerical) && is_numeric($row->l2_numerical)) {
                    return '<span class="fw-semibold">' . number_format((float) $row->l2_numerical, 1) . '</span>';
                }
                return '<span class="text-muted small">-</span>';
            })
            ->addColumn('score_l3', function ($row) {
                if (isset($row->l3_numerical) && is_numeric($row->l3_numerical)) {
                    return '<span class="fw-semibold">' . number_format((float) $row->l3_numerical, 1) . '</span>';
                }
                return '<span class="text-muted small">-</span>';
            })
            ->addColumn('pic', function ($row) {
                return !empty($row->pic) ? htmlspecialchars($row->pic) : '<span class="text-muted small">-</span>';
            })
            ->addColumn('total_engagement_duration', function ($row) {
                $afkSeconds = (int) ($row->afk_duration_seconds ?? 0);
                if ($afkSeconds <= 0 && ($row->source ?? '') === 'offline') {
                    $hours = (float) ($row->training_hours ?? ($row->learning_hours ?? 0));
                    $afkSeconds = (int) round($hours * 3600);
                }
                $h = floor($afkSeconds / 3600);
                $m = floor(($afkSeconds % 3600) / 60);
                $s = $afkSeconds % 60;
                return sprintf('%02d:%02d:%02d', $h, $m, $s);
            })
            ->addColumn('total_engagement_time', function ($row) {
                $afkSeconds = (int) ($row->afk_duration_seconds ?? 0);
                if ($afkSeconds <= 0 && ($row->source ?? '') === 'offline') {
                    $hours = (float) ($row->training_hours ?? ($row->learning_hours ?? 0));
                    $afkSeconds = (int) round($hours * 3600);
                }
                $h = floor($afkSeconds / 3600);
                $m = floor(($afkSeconds % 3600) / 60);
                $s = $afkSeconds % 60;
                return sprintf('%02d:%02d:%02d', $h, $m, $s);
            })
            ->addColumn('action', function ($row) {
                $source = $row->source ?? 'online';
                $id = $row->source_id ?? '';
                $editUrl = route('manage.record.edit', $id) . '?source=' . e($source);

                return '<div class="dropdown">
                    <button class="btn btn-sm btn-icon btn-ghost-secondary" type="button" data-bs-toggle="dropdown" aria-expanded="false">
                        <i class="ti ti-dots-vertical"></i>
                    </button>
                    <ul class="dropdown-menu dropdown-menu-end shadow-sm">
                        <li>
                            <a class="dropdown-item d-flex align-items-center gap-2 btn-more-info" href="javascript:void(0);" data-bs-toggle="modal" data-bs-target="#moreInformationModal" data-id="' . e($id) . '" data-record-id="' . e($id) . '" data-source="' . e($source) . '" data-record-source="' . e($source) . '">
                                <i class="bi bi-eye text-primary me-2"></i> More Information
                            </a>
                        </li>
                        <li>
                            <a class="dropdown-item d-flex align-items-center gap-2 btn-edit-record" href="' . e($editUrl) . '" data-id="' . e($id) . '">
                                <i class="bi bi-pencil-square text-warning me-2"></i> Edit Record
                            </a>
                        </li>
                        <li>
                            <a class="dropdown-item d-flex align-items-center gap-2 btn-delete-record text-danger" href="javascript:void(0);" data-bs-toggle="modal" data-bs-target="#deleteRecordModal" data-id="' . e($id) . '" data-record-id="' . e($id) . '" data-source="' . e($source) . '" data-record-source="' . e($source) . '">
                                <i class="bi bi-trash text-danger me-2"></i> Delete Record
                            </a>
                        </li>
                        <li>
                            <a class="dropdown-item d-flex align-items-center gap-2 btn-generate-cert disabled text-muted" href="javascript:void(0);" data-id="' . e($id) . '" onclick="return false;" style="cursor: not-allowed;">
                                <i class="bi bi-award text-info me-2"></i> Generate Certificate
                            </a>
                        </li>
                    </ul>
                </div>';
            })
            ->rawColumns([
                'source_badge',
                'status_badge',
                'employee_name',
                'employee',
                'learning_title',
                'course_title',
                'playlist',
                'type',
                'category',
                'category_badge',
                'main_classification',
                'provider',
                'sub_provider',
                'certificate_availability',
                'kpi_badge',
                'progress_bar',
                'progress_badge',
                'progress_percent',
                'progress_status',
                'date_time_merged',
                'date_merged',
                'time_merged',
                'requirement',
                'attendance',
                'certificate_badge',
                'attendance_badge',
                'score_l1',
                'score_l2',
                'score_l3',
                'learning_hours',
                'learning_hours_formatted',
                'pic',
                'total_engagement_duration',
                'total_engagement_time',
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

        $tpdRows = DB::table('trainingplandetail as tpd')
            ->join('trainingplan as tp', 'tp.id', '=', 'tpd.trainingplan_id')
            ->whereNull('tp.deleted_at')
            ->orderBy('tpd.id', 'asc')
            ->select('tpd.content_id', 'tp.id as tp_id', 'tp.title as tp_title')
            ->get();
        $tpdCache = [];
        foreach ($tpdRows as $tpd) {
            if (!isset($tpdCache[$tpd->content_id])) {
                $tpdCache[$tpd->content_id] = [
                    'id'    => (int) $tpd->tp_id,
                    'title' => (string) $tpd->tp_title,
                ];
            }
        }

        $dataTable = DataTables::of($query)
            ->setTotalRecords($totalRecords)
            ->setFilteredRecords($totalRecords);

        return $dataTable
            ->addIndexColumn()
            ->setRowId('row_id')
            ->addColumn('source_badge', function ($row) {
                if ($row->source === 'online') {
                    return '<span class="badge bg-blue-100 text-blue-700 rounded-pill px-2.5 py-1" style="background-color: #dbeafe !important; color: #1d4ed8 !important; font-weight: 600; font-size: 0.75rem;">KP OWL</span>';
                }
                return '<span class="badge bg-amber-100 text-amber-700 rounded-pill px-2.5 py-1" style="background-color: #fef3c7 !important; color: #b45309 !important; font-weight: 600; font-size: 0.75rem;">Non KP OWL</span>';
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
                return '<span class="fw-semibold">' . number_format($lh, 2) . ' h</span>';
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
            ->addColumn('playlist', function ($row) use ($tpdCache) {
                if ($row->source === 'offline') {
                    return '<span class="text-muted small">-</span>';
                }
                $playlistTitle = !empty($row->playlist_title) ? $row->playlist_title : null;
                $tpId = !empty($row->trainingplan_id) ? $row->trainingplan_id : null;
                if (!$playlistTitle && !empty($row->content_id) && isset($tpdCache[$row->content_id])) {
                    $playlistTitle = $tpdCache[$row->content_id]['title'];
                    $tpId = $tpdCache[$row->content_id]['id'];
                }
                if (!empty($playlistTitle)) {
                    if (!empty($tpId)) {
                        $url = route('learning.manage.playlist', ['id' => $tpId]);
                        return '<a href="' . e($url) . '" class="text-primary fw-semibold" title="' . e($playlistTitle) . '"><i class="bi bi-collection-play me-1"></i>' . e($playlistTitle) . '</a>';
                    }
                    return e($playlistTitle);
                }
                return '<span class="text-muted small">-</span>';
            })
            ->addColumn('type', function ($row) {
                if ($row->source === 'offline') {
                    return '<span class="text-muted small">-</span>';
                }
                $t = $row->type ?? 'Document';
                return htmlspecialchars($t ?: 'Document');
            })
            ->addColumn('category', function ($row) use ($tpdCache) {
                return $this->categoryBadge($row, $tpdCache);
            })
            ->addColumn('course_title', function ($row) {
                $title = htmlspecialchars($row->learning_title ?: '-');
                $rawReq = $row->requirement ?? ($row->status ?? 'Non-Mandatory');
                $req = !empty($rawReq) ? htmlspecialchars($rawReq) : 'Non-Mandatory';
                return '<div class="d-flex flex-column align-items-center justify-content-center text-center w-100">'
                    . '<span class="fw-bold text-dark text-center fs-6 mb-1">' . $title . '</span>'
                    . '<small class="text-muted text-center fs-7">Requirement: <span class="fw-semibold">' . $req . '</span></small>'
                    . '</div>';
            })
            ->addColumn('main_classification', function ($row) {
                return htmlspecialchars($row->classification ?: '-');
            })
            ->addColumn('course_duration', function ($row) {
                $sec = (int) ($row->duration_seconds ?? 0);
                if ($sec <= 0) {
                    return '-';
                }
                $h = floor($sec / 3600);
                $m = floor(($sec % 3600) / 60);
                $s = $sec % 60;
                return sprintf('%02d:%02d:%02d', $h, $m, $s);
            })
            ->addColumn('recognized_duration', function ($row) {
                if (($row->source ?? '') === 'offline') {
                    return '-';
                }
                $sec = (int) ($row->recognized_duration_seconds ?? 0);
                if ($sec <= 0) {
                    $sec = app(\App\Services\Learning\LearningRecordRecognizedDurationService::class)->resolveFromRow($row);
                }
                $h = floor($sec / 3600);
                $m = floor(($sec % 3600) / 60);
                $s = $sec % 60;
                return sprintf('%02d:%02d:%02d', $h, $m, $s);
            })
            ->addColumn('total_recognized_duration', function ($row) {
                if (($row->source ?? '') === 'offline') {
                    return '-';
                }
                $sec = (int) ($row->recognized_duration_seconds ?? 0);
                if ($sec <= 0) {
                    $sec = app(\App\Services\Learning\LearningRecordRecognizedDurationService::class)->resolveFromRow($row);
                }
                $h = floor($sec / 3600);
                $m = floor(($sec % 3600) / 60);
                $s = $sec % 60;
                return sprintf('%02d:%02d:%02d', $h, $m, $s);
            })
            ->addColumn('grade', function ($row) {
                return htmlspecialchars($row->grade ?: '-');
            })
            ->addColumn('job_position', function ($row) {
                return htmlspecialchars($row->job_position ?: '-');
            })
            ->addColumn('sub_course_title', function ($row) {
                return htmlspecialchars($row->sub_course_name ?: '-');
            })
            ->addColumn('level', function ($row) {
                return htmlspecialchars($row->level ?: '-');
            })
            ->addColumn('batch', function ($row) {
                return htmlspecialchars($row->batch ?: '-');
            })
            ->addColumn('sub_classification', function ($row) {
                return htmlspecialchars($row->sub_classification ?: '-');
            })
            ->addColumn('delivery_method', function ($row) {
                $sourceRaw = strtolower(trim((string) ($row->source ?? '')));
                $isKpOwl = in_array($sourceRaw, ['online', 'kp owl', 'kp-owl', 'owl'], true);
                return $isKpOwl ? 'Online' : 'Offline';
            })
            ->addColumn('sub_delivery_method', function ($row) {
                $sourceRaw = strtolower(trim((string) ($row->source ?? '')));
                $isKpOwl = in_array($sourceRaw, ['online', 'kp owl', 'kp-owl', 'owl'], true);
                if ($isKpOwl) {
                    return 'KP OWL';
                }
                $sub = $row->sub_delivery_method ?? $row->training_type ?? $row->type ?? null;
                if (!$sub || $sub === '-' || strtolower(trim((string) $sub)) === 'offline') {
                    $rawDm = trim((string) ($row->delivery_method ?? ''));
                    if ($rawDm !== '' && strtolower($rawDm) !== 'offline') {
                        $sub = $rawDm;
                    }
                }
                return htmlspecialchars($sub ?: '-');
            })
            ->addColumn('provider', function ($row) {
                $sourceRaw = strtolower(trim((string) ($row->source ?? '')));
                $isKpOwl = in_array($sourceRaw, ['online', 'kp owl', 'kp-owl', 'owl'], true);
                return $isKpOwl ? 'Internal' : htmlspecialchars($row->provider ?: '-');
            })
            ->addColumn('sub_provider', function ($row) {
                $sourceRaw = strtolower(trim((string) ($row->source ?? '')));
                $isKpOwl = in_array($sourceRaw, ['online', 'kp owl', 'kp-owl', 'owl'], true);
                return $isKpOwl ? '-' : htmlspecialchars($row->sub_provider ?: '-');
            })
            ->addColumn('provider_name', function ($row) {
                $sourceRaw = strtolower(trim((string) ($row->source ?? '')));
                $isKpOwl = in_array($sourceRaw, ['online', 'kp owl', 'kp-owl', 'owl'], true);
                return $isKpOwl ? 'KP-OWL' : htmlspecialchars($row->provider_name ?: '-');
            })
            ->addColumn('venue', function ($row) {
                $sourceRaw = strtolower(trim((string) ($row->source ?? '')));
                $isKpOwl = in_array($sourceRaw, ['online', 'kp owl', 'kp-owl', 'owl'], true);
                return $isKpOwl ? 'Online LMS' : htmlspecialchars($row->venue ?: '-');
            })
            ->addColumn('instructor', function ($row) {
                return htmlspecialchars($row->instructor ?: '-');
            })
            ->addColumn('start_date', function ($row) {
                return !empty($row->start_date) ? date('Y-m-d', strtotime($row->start_date)) : '-';
            })
            ->addColumn('end_date', function ($row) {
                $d = $row->end_date ?? ($row->finish_date ?? null);
                return !empty($d) ? date('Y-m-d', strtotime($d)) : '-';
            })
            ->addColumn('start_time', function ($row) {
                return !empty($row->start_time) ? date('H:i:s', strtotime($row->start_time)) : '-';
            })
            ->addColumn('end_time', function ($row) {
                return !empty($row->end_time) ? date('H:i:s', strtotime($row->end_time)) : '-';
            })
            ->addColumn('attendance_reason', function ($row) {
                return htmlspecialchars($row->attendance_reason ?: '-');
            })
            ->addColumn('bond_status', function ($row) {
                return htmlspecialchars($row->bond_status ?: 'No');
            })
            ->addColumn('bond_duration', function ($row) {
                return !empty($row->bond_duration) ? htmlspecialchars($row->bond_duration . ' Months') : '-';
            })
            ->addColumn('bond_end_date', function ($row) {
                return !empty($row->bond_end_date) ? date('Y-m-d', strtotime($row->bond_end_date)) : '-';
            })
            ->addColumn('bond_amount', function ($row) {
                $idr = $row->bond_amount_idr ?? $row->bond_amount ?? null;
                return ($idr !== null && (float) $idr > 0) ? 'Rp ' . number_format((float) $idr, 0, ',', '.') : '-';
            })
            ->addColumn('l2_status', function ($row) {
                if (!empty($row->l2_verbal) && $row->l2_verbal !== '-') {
                    return htmlspecialchars($row->l2_verbal);
                }
                $s2 = is_numeric($row->assessment_score) ? (float) $row->assessment_score : (is_numeric($row->l2_numerical) ? (float) $row->l2_numerical : null);
                if ($s2 !== null) {
                    return $s2 >= 70 ? 'Passed' : 'Failed';
                }
                return '-';
            })
            ->addColumn('l3_status', function ($row) {
                return htmlspecialchars($row->l3_verbal ?: '-');
            })
            ->addColumn('certificate_availability', function ($row) {
                $isFinished = ((float) ($row->progress ?? 0) >= 100 || ($row->status ?? '') === 'Finished' || ($row->finish ?? '') === '1');
                $hasCert = (!empty($row->certificate) && $row->certificate !== '-') || $isFinished;
                return $hasCert ? 'Available' : '-';
            })
            ->addColumn('certificate_institution', function ($row) {
                if ($row->source === 'offline') {
                    return htmlspecialchars($row->certificate ?: '-');
                }
                $isFinished = ((float) ($row->progress ?? 0) >= 100 || ($row->status ?? '') === 'Finished' || ($row->finish ?? '') === '1');
                return $isFinished ? 'PT Krakatau Posco' : '-';
            })
            ->addColumn('certificate_issued_date', function ($row) {
                if ($row->source === 'offline') {
                    return !empty($row->certificate_issued_date) ? date('Y-m-d', strtotime($row->certificate_issued_date)) : '-';
                }
                $d = $row->finish_date ?? ($row->start_date ?? null);
                $isFinished = ((float) ($row->progress ?? 0) >= 100 || ($row->status ?? '') === 'Finished' || ($row->finish ?? '') === '1');
                return ($isFinished && !empty($d) && $d !== '-') ? date('Y-m-d', strtotime($d)) : '-';
            })
            ->addColumn('certificate_expired_date', function ($row) {
                if ($row->source === 'offline') {
                    return !empty($row->certificate_expired_date) ? date('Y-m-d', strtotime($row->certificate_expired_date)) : '-';
                }
                return '-';
            })
            ->addColumn('progress_percent', function ($row) {
                $progress = max(0, min(100, round((float) ($row->progress ?? 0))));
                $color = $progress >= 100 ? 'bg-success' : ($progress > 0 ? 'bg-warning' : 'bg-secondary');
                return '<div class="d-flex align-items-center gap-2" style="min-width: 85px;">
                    <div class="progress progress-xs flex-grow-1" style="width: 50px; height: 6px;">
                        <div class="progress-bar ' . $color . '" style="width: ' . $progress . '%"></div>
                    </div>
                    <span class="small fw-semibold text-muted">' . $progress . '%</span>
                </div>';
            })
            ->addColumn('progress_status', function ($row) {
                $progress = (float) ($row->progress ?? 0);
                if ($progress >= 100) {
                    return '<span class="badge bg-success-lt text-success fw-bold px-2 py-1">Finished</span>';
                } elseif ($progress > 0) {
                    return '<span class="badge bg-warning-lt text-warning fw-bold px-2 py-1">Ongoing</span>';
                }
                return '<span class="badge bg-secondary-lt fw-bold px-2 py-1">Not Started</span>';
            })
            ->addColumn('date_time_merged', function ($row) {
                return \App\Http\Controllers\Manage\Record\RecordOfflineController::renderMergedDateTime($row->start_date ?? null, $row->finish_date ?? ($row->end_date ?? null), $row->start_time ?? null, $row->end_time ?? null);
            })
            ->addColumn('date_merged', function ($row) {
                return \App\Http\Controllers\Manage\Record\RecordOfflineController::renderMergedDate($row->start_date ?? null, $row->finish_date ?? ($row->end_date ?? null));
            })
            ->addColumn('time_merged', function ($row) {
                return \App\Http\Controllers\Manage\Record\RecordOfflineController::renderMergedTime($row->start_time ?? null, $row->end_time ?? null);
            })
            ->addColumn('record_month', function ($row) {
                if ($row->source === 'online') {
                    $d = $row->finish_date ?? null;
                } else {
                    $d = $row->start_date ?: ($row->finish_date ?: ($row->record_date ?? null));
                }
                if (!empty($d) && $d !== '-') {
                    try {
                        return date('F', strtotime($d));
                    } catch (\Throwable $e) {
                        return '-';
                    }
                }
                return '-';
            })
            ->addColumn('requirement', function ($row) {
                $req = $row->requirement ?? ($row->status ?? '-');
                if (strtolower($req) === 'mandatory') {
                    return '<span class="badge bg-red-lt text-red">Mandatory</span>';
                }
                return htmlspecialchars($req);
            })
            ->addColumn('attendance', function ($row) {
                $val = (string) ($row->attendance ?? '');
                $attendance = in_array(strtolower(trim($val)), ['present', 'yes', '1', 'true', 'finished', 'completed', 'hadir'], true)
                    || (($row->source ?? '') === 'online' && ((float) ($row->progress ?? 0) >= 100))
                    ? 'Yes' : 'No';
                if ($attendance === 'Yes') {
                    return '<span class="badge bg-success-lt text-success fw-bold px-2 py-1">Yes</span>';
                }
                return '<span class="badge bg-danger-lt text-danger fw-bold px-2 py-1">No</span>';
            })
            ->addColumn('score_l1', function ($row) {
                if (isset($row->assessment_score) && is_numeric($row->assessment_score)) {
                    return '<span class="fw-semibold">' . number_format((float) $row->assessment_score, 1) . '</span>';
                }
                if (!empty($row->l1_program) && $row->l1_program !== '-') {
                    return htmlspecialchars($row->l1_program);
                }
                return '<span class="text-muted small">-</span>';
            })
            ->addColumn('score_l2', function ($row) {
                if (isset($row->l2_numerical) && is_numeric($row->l2_numerical)) {
                    return '<span class="fw-semibold">' . number_format((float) $row->l2_numerical, 1) . '</span>';
                }
                return '<span class="text-muted small">-</span>';
            })
            ->addColumn('score_l3', function ($row) {
                if (isset($row->l3_numerical) && is_numeric($row->l3_numerical)) {
                    return '<span class="fw-semibold">' . number_format((float) $row->l3_numerical, 1) . '</span>';
                }
                return '<span class="text-muted small">-</span>';
            })
            ->addColumn('pic', function ($row) {
                return !empty($row->pic) ? htmlspecialchars($row->pic) : '<span class="text-muted small">-</span>';
            })
            ->addColumn('total_engagement_duration', function ($row) {
                $afkSeconds = (int) ($row->afk_duration_seconds ?? 0);
                if ($afkSeconds <= 0 && ($row->source ?? '') === 'offline') {
                    $hours = (float) ($row->training_hours ?? ($row->learning_hours ?? 0));
                    $afkSeconds = (int) round($hours * 3600);
                }
                $h = floor($afkSeconds / 3600);
                $m = floor(($afkSeconds % 3600) / 60);
                $s = $afkSeconds % 60;
                return sprintf('%02d:%02d:%02d', $h, $m, $s);
            })
            ->addColumn('total_engagement_time', function ($row) {
                $afkSeconds = (int) ($row->afk_duration_seconds ?? 0);
                if ($afkSeconds <= 0 && ($row->source ?? '') === 'offline') {
                    $hours = (float) ($row->training_hours ?? ($row->learning_hours ?? 0));
                    $afkSeconds = (int) round($hours * 3600);
                }
                $h = floor($afkSeconds / 3600);
                $m = floor(($afkSeconds % 3600) / 60);
                $s = $afkSeconds % 60;
                return sprintf('%02d:%02d:%02d', $h, $m, $s);
            })
            ->addColumn('action', function ($row) {
                $source = $row->source ?? 'online';
                $id = $row->source_id ?? '';
                $editUrl = route('manage.record.edit', $id) . '?source=' . e($source);

                return '<div class="dropdown">
                    <button class="btn btn-sm btn-icon btn-ghost-secondary" type="button" data-bs-toggle="dropdown" aria-expanded="false">
                        <i class="ti ti-dots-vertical"></i>
                    </button>
                    <ul class="dropdown-menu dropdown-menu-end shadow-sm">
                        <li>
                            <a class="dropdown-item d-flex align-items-center gap-2 btn-more-info" href="javascript:void(0);" data-bs-toggle="modal" data-bs-target="#moreInformationModal" data-id="' . e($id) . '" data-record-id="' . e($id) . '" data-source="' . e($source) . '" data-record-source="' . e($source) . '">
                                <i class="bi bi-eye text-primary me-2"></i> More Information
                            </a>
                        </li>
                        <li>
                            <a class="dropdown-item d-flex align-items-center gap-2 btn-edit-record" href="' . e($editUrl) . '" data-id="' . e($id) . '">
                                <i class="bi bi-pencil-square text-warning me-2"></i> Edit Record
                            </a>
                        </li>
                        <li>
                            <a class="dropdown-item d-flex align-items-center gap-2 btn-delete-record text-danger" href="javascript:void(0);" data-bs-toggle="modal" data-bs-target="#deleteRecordModal" data-id="' . e($id) . '" data-record-id="' . e($id) . '" data-source="' . e($source) . '" data-record-source="' . e($source) . '">
                                <i class="bi bi-trash text-danger me-2"></i> Delete Record
                            </a>
                        </li>
                        <li>
                            <a class="dropdown-item d-flex align-items-center gap-2 btn-generate-cert disabled text-muted" href="javascript:void(0);" data-id="' . e($id) . '" onclick="return false;" style="cursor: not-allowed;">
                                <i class="bi bi-award text-info me-2"></i> Generate Certificate
                            </a>
                        </li>
                    </ul>
                </div>';
            })
            ->rawColumns([
                'source_badge',
                'status_badge',
                'employee_name',
                'employee',
                'learning_title',
                'course_title',
                'playlist',
                'type',
                'category',
                'category_badge',
                'main_classification',
                'provider',
                'sub_provider',
                'certificate_availability',
                'kpi_badge',
                'progress_bar',
                'progress_badge',
                'progress_percent',
                'progress_status',
                'date_time_merged',
                'date_merged',
                'time_merged',
                'requirement',
                'attendance',
                'certificate_badge',
                'attendance_badge',
                'score_l1',
                'score_l2',
                'score_l3',
                'learning_hours',
                'learning_hours_formatted',
                'pic',
                'total_engagement_duration',
                'total_engagement_time',
                'action',
            ])
            ->make(true);
    }

    /**
     * Render category HTML badge:
     * - Non KP OWL (Offline): badge bg-secondary-lt text-secondary 'Non KP-OWL'
     * - Content Playlist: badge bg-purple-lt text-purple 'Content Playlist'
     * - All Content: badge bg-azure-lt text-azure 'All Content'
     */
    public function categoryBadge($row, array $tpdCache = []): string
    {
        $sourceRaw = strtolower(trim((string) ($row->source ?? '')));
        $isOffline = in_array($sourceRaw, ['offline', 'non kp owl', 'non kp-owl'], true);
        if ($isOffline) {
            return '<span class="badge bg-secondary-lt text-secondary">Non KP-OWL</span>';
        }

        $categorySource = $row->category_source ?? ($row->category ?? '');
        $hasPlaylist = !empty($row->playlist_title)
            || (!empty($row->content_id) && isset($tpdCache[$row->content_id]))
            || in_array($categorySource, ['Content Playlist', 'Playlist'], true)
            || in_array($row->category ?? '', ['Content Playlist', 'Playlist'], true)
            || ($row->source_type ?? '') === 'playlist';

        if ($hasPlaylist) {
            return '<span class="badge bg-purple-lt text-purple">Content Playlist</span>';
        }

        return '<span class="badge bg-azure-lt text-azure">All Content</span>';
    }

    /**
     * Normalize attendance attribute strictly to 'Yes' / 'No'.
     */
    public static function formatAttendance($attendance, $progress = 0, $source = 'offline'): string
    {
        $raw = strtolower(trim((string) ($attendance ?? '')));
        $isYes = in_array($raw, ['present', 'yes', '1', 'true', 'finished', 'completed', 'hadir'], true)
            || ($source === 'online' && ((float) ($progress ?? 0) >= 100));

        return $isYes ? 'Yes' : 'No';
    }

    /**
     * Format timestamp as dM Y \a\t H.i.s (e.g., 21Okt2026 at 09.35.00)
     */
    public static function formatExportTimestamp(?\DateTimeInterface $date = null): string
    {
        $dt = $date ? \Illuminate\Support\Carbon::parse($date) : \Illuminate\Support\Carbon::now();
        $monthMap = [
            1 => 'Jan', 2 => 'Feb', 3 => 'Mar', 4 => 'Apr',
            5 => 'Mei', 6 => 'Jun', 7 => 'Jul', 8 => 'Agu',
            9 => 'Sep', 10 => 'Okt', 11 => 'Nov', 12 => 'Des',
        ];
        $day = $dt->format('d');
        $month = $monthMap[(int) $dt->format('n')] ?? $dt->format('M');
        $year = $dt->format('Y');
        $time = $dt->format('H.i.s');

        return "{$day}{$month}{$year} at {$time}";
    }

    public static function generateUserFileName(string $employeeId, ?\DateTimeInterface $date = null): string
    {
        $timestamp = self::formatExportTimestamp($date);
        return "MyAllrecord_{$employeeId}_{$timestamp}.xlsx";
    }

    public static function generateManageFileName(?\DateTimeInterface $date = null): string
    {
        $timestamp = self::formatExportTimestamp($date);
        return "EmployeeAllrecord_{$timestamp}.xlsx";
    }
}
