<?php

namespace App\Queries\Manage\KpOwl;

use Carbon\Carbon;
use Illuminate\Database\Query\Builder;
use Illuminate\Support\Facades\DB;

class KpOwlRecordQuery
{
    /**
     * Build unified database query for KP OWL records (ContentUser + grouped PlaylistContentUser).
     */
    public function buildQuery(array $filters = []): Builder
    {
        $categoryParam = strtolower(trim((string) ($filters['category'] ?? ($filters['learning_source'] ?? 'all'))));
        $typeParam = strtolower(trim((string) ($filters['type'] ?? 'all')));

        $includeContent = !in_array($categoryParam, ['playlist'], true) && !in_array($typeParam, ['playlist'], true);
        $includePlaylist = !in_array($categoryParam, ['content', 'all content', 'all_content'], true) && !in_array($typeParam, ['video', 'document', 'youtube', 'mooc'], true);

        if (!$includeContent && !$includePlaylist) {
            $includeContent = true;
            $includePlaylist = true;
        }

        $cuSelect = $includeContent ? $this->buildContentSubquery($filters) : null;
        $pcuSelect = $includePlaylist ? $this->buildPlaylistSubquery($filters) : null;

        if ($cuSelect && $pcuSelect) {
            $union = $cuSelect->unionAll($pcuSelect);
        } elseif ($cuSelect) {
            $union = $cuSelect;
        } else {
            $union = $pcuSelect;
        }

        $query = DB::query()
            ->fromSub($union, 'unified')
            ->leftJoin('users as u', 'u.id', '=', 'unified.user_id')
            ->leftJoin('content as c', 'c.id', '=', 'unified.content_id')
            ->leftJoin('classification as cl', 'cl.id', '=', 'c.classification_id')
            ->leftJoin('trainingplan as tp', 'tp.id', '=', 'unified.trainingplan_id')
            ->select([
                'unified.source_id',
                'unified.source_type',
                'unified.user_id',
                'unified.content_id',
                'unified.trainingplan_id',
                'unified.requirement_status',
                'unified.finish',
                'unified.progress',
                'unified.learning_hours',
                'unified.created_at',
                'unified.updated_at',
                'unified.completed_at',
                'unified.duration_seconds',
                'unified.actual_watch_seconds',
                'unified.current_time',
                'unified.afk_duration_seconds',
                'u.id_emp',
                'u.name as employee_name',
                'u.jobgrade',
                'u.jobposition',
                'u.team',
                'u.department',
                'u.company_name',
                'c.title as content_title',
                'c.type as content_type',
                'c.author as content_author',
                'c.duration as content_duration',
                'tp.title as playlist_title',
                'tp.total_duration_seconds as playlist_duration_seconds',
                'cl.classification_name',
            ]);

        $this->applyOuterFilters($query, $filters);

        return $query;
    }

    /**
     * Standalone Content subquery (content_users).
     */
    protected function buildContentSubquery(array $filters): Builder
    {
        $q = DB::table('content_users as cu')
            ->select([
                'cu.id as source_id',
                DB::raw("'content' as source_type"),
                'cu.user_id',
                'cu.content_id',
                DB::raw('CAST(NULL AS UNSIGNED) as trainingplan_id'),
                'cu.status as requirement_status',
                'cu.finish',
                'cu.progress',
                DB::raw('COALESCE(cu.learning_hours, 0) as learning_hours'),
                'cu.created_at',
                'cu.updated_at',
                DB::raw("CASE WHEN cu.finish = '1' OR cu.progress >= 100 THEN cu.completed_at ELSE NULL END as completed_at"),
                DB::raw('COALESCE(cu.duration_seconds, 0) as duration_seconds'),
                DB::raw('COALESCE(cu.actual_watch_seconds, 0) as actual_watch_seconds'),
                DB::raw('COALESCE(cu.current_time, 0) as `current_time`'),
                DB::raw('COALESCE(cu.afk_duration_seconds, 0) as afk_duration_seconds'),
            ]);

        $this->applySubqueryFilters($q, $filters, 'cu');

        return $q;
    }

    /**
     * Playlist subquery (playlist_content_users grouped by user_id and trainingplan_id).
     * Prevents duplicate learning records and double-counting engagement time / learning hours.
     */
    protected function buildPlaylistSubquery(array $filters): Builder
    {
        $q = DB::table('playlist_content_users as pcu')
            ->select([
                DB::raw('MIN(pcu.id) as source_id'),
                DB::raw("'playlist' as source_type"),
                'pcu.user_id',
                DB::raw('MIN(pcu.content_id) as content_id'),
                'pcu.trainingplan_id',
                DB::raw("COALESCE(MIN(pcu.status), 'non mandatory') as requirement_status"),
                DB::raw("CASE WHEN COUNT(pcu.id) > 0 AND SUM(CASE WHEN pcu.finish = '1' OR pcu.progress >= 100 THEN 1 ELSE 0 END) = COUNT(pcu.id) THEN '1' ELSE '0' END as finish"),
                DB::raw('ROUND(AVG(COALESCE(pcu.progress, 0))) as progress'),
                DB::raw('COALESCE(SUM(pcu.learning_hour), 0) as learning_hours'),
                DB::raw('MIN(pcu.created_at) as created_at'),
                DB::raw('MAX(pcu.updated_at) as updated_at'),
                DB::raw("MAX(CASE WHEN pcu.finish = '1' OR pcu.progress >= 100 THEN pcu.completed_at ELSE NULL END) as completed_at"),
                DB::raw('SUM(COALESCE(pcu.duration_seconds, 0)) as duration_seconds'),
                DB::raw('SUM(COALESCE(pcu.actual_watch_seconds, 0)) as actual_watch_seconds'),
                DB::raw('SUM(COALESCE(pcu.current_time, 0)) as `current_time`'),
                DB::raw('SUM(COALESCE(pcu.afk_duration_seconds, 0)) as afk_duration_seconds'),
            ])
            ->groupBy(['pcu.user_id', 'pcu.trainingplan_id']);

        $this->applySubqueryFilters($q, $filters, 'pcu');

        return $q;
    }

    /**
     * Apply indexed pushdown filters to subqueries.
     */
    protected function applySubqueryFilters(Builder $q, array $filters, string $alias): void
    {
        // 1. Progress Status Filter
        $status = strtolower(trim((string) ($filters['progress_status'] ?? ($filters['status'] ?? 'all'))));
        if ($status !== '' && $status !== 'all') {
            if ($status === 'finished' || $status === 'finish' || $status === '1') {
                $q->where(function ($sub) use ($alias) {
                    $sub->where("{$alias}.finish", '1')
                        ->orWhere("{$alias}.progress", '>=', 100);
                });
            } elseif ($status === 'ongoing' || $status === '0') {
                $q->where("{$alias}.finish", '0')
                  ->where("{$alias}.progress", '>', 0)
                  ->where("{$alias}.progress", '<', 100);
            } elseif ($status === 'not_started') {
                $q->where("{$alias}.finish", '0')
                  ->where(function ($sub) use ($alias) {
                      $sub->where("{$alias}.progress", '<=', 0)
                          ->orWhereNull("{$alias}.progress");
                  });
            }
        }

        // 2. Requirement Status (Mandatory / Non-Mandatory)
        $req = strtolower(trim((string) ($filters['requirement'] ?? ($filters['program_type'] ?? 'all'))));
        if ($req !== '' && $req !== 'all') {
            $isMandatory = in_array($req, ['mandatory', '1'], true);
            $q->where("{$alias}.status", $isMandatory ? 'mandatory' : 'non mandatory');
        }

        // 3. Exact user ID filter
        if (isset($filters['user_id']) && is_numeric($filters['user_id'])) {
            $q->where("{$alias}.user_id", (int) $filters['user_id']);
        }

        // 4. Exact content ID or playlist ID
        if (isset($filters['content_id']) && is_numeric($filters['content_id']) && $alias === 'cu') {
            $q->where('cu.content_id', (int) $filters['content_id']);
        }
        if (isset($filters['trainingplan_id']) && is_numeric($filters['trainingplan_id']) && $alias === 'pcu') {
            $q->where('pcu.trainingplan_id', (int) $filters['trainingplan_id']);
        }

        // 5. Date Range (created_at)
        $fromDate = $filters['from_date'] ?? ($filters['date_from'] ?? null);
        $toDate = $filters['to_date'] ?? ($filters['date_to'] ?? null);
        if (filled($fromDate)) {
            try {
                $parsed = Carbon::parse(str_replace('/', '-', $fromDate))->format('Y-m-d');
                $q->where("{$alias}.created_at", '>=', $parsed . ' 00:00:00');
            } catch (\Throwable $e) {}
        }
        if (filled($toDate)) {
            try {
                $parsed = Carbon::parse(str_replace('/', '-', $toDate))->format('Y-m-d');
                $q->where("{$alias}.created_at", '<=', $parsed . ' 23:59:59');
            } catch (\Throwable $e) {}
        }

        // 6. Year Filter
        $year = $filters['year'] ?? null;
        if (filled($year) && $year !== 'all' && is_numeric($year)) {
            $q->whereYear("{$alias}.created_at", (int) $year);
        }
    }

    /**
     * Apply outer filters on joined fields (employees, classifications, titles, search).
     */
    protected function applyOuterFilters(Builder $query, array $filters): void
    {
        // 1. Employee Search (ID or Name)
        $emp = trim((string) ($filters['employee_id'] ?? ($filters['employee'] ?? '')));
        if ($emp !== '' && $emp !== 'all') {
            $query->where(function ($q) use ($emp) {
                $q->where('u.id_emp', 'like', "%{$emp}%")
                  ->orWhere('u.name', 'like', "%{$emp}%");
            });
        }

        // 2. Course Title Filter
        $courseTitle = trim((string) ($filters['course_title'] ?? ($filters['learning_title'] ?? ($filters['title'] ?? ''))));
        if ($courseTitle !== '' && $courseTitle !== 'all') {
            $query->where(function ($q) use ($courseTitle) {
                $q->where('c.title', 'like', "%{$courseTitle}%")
                  ->orWhere('tp.title', 'like', "%{$courseTitle}%");
            });
        }

        // 3. Classification Filter
        $class = trim((string) ($filters['main_classification'] ?? ($filters['classification'] ?? ($filters['classification_id'] ?? ''))));
        if ($class !== '' && $class !== 'all') {
            if (is_numeric($class)) {
                $query->where('cl.id', (int) $class);
            } else {
                $query->where(function ($q) use ($class) {
                    $q->where('cl.slug', $class)
                      ->orWhere('cl.classification_name', $class);
                });
            }
        }

        // 4. Content Type (Video, Document) - only for content rows
        $type = strtolower(trim((string) ($filters['type'] ?? 'all')));
        if ($type !== '' && $type !== 'all' && $type !== 'playlist') {
            if ($type === 'video') {
                $query->whereIn('c.type', ['Video', 'YouTube']);
            } elseif ($type === 'document') {
                $query->where('c.type', 'Document');
            } else {
                $query->where('c.type', $type);
            }
        }

        // 5. Department Filter
        $dept = trim((string) ($filters['department'] ?? 'all'));
        if ($dept !== '' && $dept !== 'all') {
            $query->where('u.department', $dept);
        }

        // 6. Team Filter
        $team = trim((string) ($filters['team'] ?? 'all'));
        if ($team !== '' && $team !== 'all') {
            $query->where('u.team', 'like', "%{$team}%");
        }

        // 7. Company Filter
        $company = trim((string) ($filters['company'] ?? 'all'));
        if ($company !== '' && $company !== 'all') {
            $query->where('u.company_name', $company);
        }

        // 8. Record Month (Must be based on completed_at / End Date per business logic)
        $month = trim((string) ($filters['record_month'] ?? ($filters['month'] ?? 'all')));
        if ($month !== '' && $month !== 'all') {
            if (is_numeric($month)) {
                $query->whereMonth('unified.completed_at', (int) $month);
            } else {
                $monthNum = date('n', strtotime("1 {$month} 2026"));
                if ($monthNum) {
                    $query->whereMonth('unified.completed_at', $monthNum);
                }
            }
        }

        // 9. Global Search (DataTable search)
        $searchParam = $filters['search'] ?? null;
        $search = is_array($searchParam) ? trim((string) ($searchParam['value'] ?? '')) : trim((string) ($searchParam ?? ''));
        if ($search !== '' && $emp === '' && $courseTitle === '') {
            $query->where(function ($q) use ($search) {
                $q->where('u.id_emp', 'like', "%{$search}%")
                  ->orWhere('u.name', 'like', "%{$search}%")
                  ->orWhere('c.title', 'like', "%{$search}%")
                  ->orWhere('tp.title', 'like', "%{$search}%")
                  ->orWhere('cl.classification_name', 'like', "%{$search}%")
                  ->orWhere('u.department', 'like', "%{$search}%")
                  ->orWhere('u.team', 'like', "%{$search}%");
            });
        }
    }
}
