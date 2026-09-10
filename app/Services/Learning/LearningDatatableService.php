<?php

namespace App\Services\Learning;

use App\Http\Controllers\LearningManageController;
use App\Models\ContentUser;
use App\Models\PlaylistContentUser;
use App\Support\DurationFormatter;
use Carbon\Carbon;
use Illuminate\Database\Query\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Yajra\DataTables\Facades\DataTables;

class LearningDatatableService
{
    public const PLAYLIST_CUTOFF = LearningManageController::PLAYLIST_CUTOFF;

    /**
     * Build unified query builder for learning data (ContentUser + PlaylistContentUser).
     */
    public static function buildBaseQuery(array $filters = [], string $type = '', string $slug = ''): Builder
    {
        $categoryParam = strtolower(trim((string) ($filters['category'] ?? ($filters['learning_source'] ?? ($filters['source'] ?? 'all')))));

        $cuSelect = DB::table('content_users as cu')
            ->select([
                'cu.id as source_id',
                DB::raw("'content' as source_type"),
                DB::raw("'All Content' as category_source"),
                'cu.user_id',
                'cu.content_id',
                DB::raw('CAST(NULL AS UNSIGNED) as trainingplan_id'),
                'cu.status',
                'cu.finish',
                'cu.progress',
                'cu.created_at',
            ]);

        $pcuSelect = DB::table('playlist_content_users as pcu')
            ->select([
                'pcu.id as source_id',
                DB::raw("'playlist' as source_type"),
                DB::raw("'Playlist' as category_source"),
                'pcu.user_id',
                'pcu.content_id',
                'pcu.trainingplan_id',
                DB::raw("'Playlist' as status"),
                'pcu.finish',
                'pcu.progress',
                'pcu.created_at',
            ]);

        // 1. Status Filter Pushdown
        $status = $filters['status'] ?? null;
        if (filled($status) && $status !== 'all') {
            $statusStr = strtolower(trim((string) $status));
            if ($statusStr === 'finished' || $statusStr === 'finish' || $statusStr === '1') {
                $cuSelect->where('cu.finish', '1');
                $pcuSelect->where('pcu.finish', '1');
            } elseif ($statusStr === 'ongoing' || $statusStr === '0') {
                $cuSelect->where('cu.finish', '0')->where('cu.progress', '>', 0);
                $pcuSelect->where('pcu.finish', '0')->where('pcu.progress', '>', 0);
            } elseif ($statusStr === 'not_started') {
                $cuSelect->where('cu.finish', '0')->where(function ($q) {
                    $q->where('cu.progress', 0)->orWhereNull('cu.progress');
                });
                $pcuSelect->where('pcu.finish', '0')->where(function ($q) {
                    $q->where('pcu.progress', 0)->orWhereNull('pcu.progress');
                });
            } elseif (in_array($statusStr, ['mandatory', 'optional'], true)) {
                $cuSelect->where('cu.status', $statusStr);
                $pcuSelect->whereRaw('1 = 0');
            } elseif ($statusStr === 'playlist') {
                $cuSelect->whereRaw('1 = 0');
            }
        }

        // 2. Program Type Filter Pushdown
        $programType = $filters['program_type'] ?? ($filters['program'] ?? null);
        if (filled($programType) && $programType !== 'all') {
            if ($programType === 'mandatory' || $programType === 'optional') {
                $cuSelect->where('cu.status', $programType);
                $pcuSelect->whereRaw('1 = 0');
            } else {
                $cuSelect->where('cu.status', $programType);
            }
        }

        // 3. Year Filter Pushdown
        $year = $filters['year'] ?? null;
        if (filled($year) && $year !== 'all') {
            $cuSelect->whereYear('cu.created_at', $year);
            $pcuSelect->whereYear('pcu.created_at', $year);
        }

        // 4. Date Range Filter Pushdown
        $fromDate = $filters['from_date'] ?? ($filters['date_from'] ?? null);
        $toDate = $filters['to_date'] ?? ($filters['date_to'] ?? null);
        if (filled($fromDate) && filled($toDate)) {
            $cuSelect->whereBetween('cu.created_at', [$fromDate . ' 00:00:00', $toDate . ' 23:59:59']);
            $pcuSelect->whereBetween('pcu.created_at', [$fromDate . ' 00:00:00', $toDate . ' 23:59:59']);
        } elseif (filled($fromDate)) {
            $cuSelect->whereDate('cu.created_at', '>=', $fromDate);
            $pcuSelect->whereDate('pcu.created_at', '>=', $fromDate);
        } elseif (filled($toDate)) {
            $cuSelect->whereDate('cu.created_at', '<=', $toDate);
            $pcuSelect->whereDate('pcu.created_at', '<=', $toDate);
        }

        // 5. Progress Range Filter Pushdown
        $progressRange = $filters['progress_range'] ?? null;
        if (filled($progressRange) && $progressRange !== 'all') {
            $progressVal = str_replace([' ', '%'], '', (string) $progressRange);
            switch ($progressVal) {
                case '0':
                    $cuSelect->where(function ($q) {
                        $q->where('cu.progress', 0)->orWhereNull('cu.progress');
                    });
                    $pcuSelect->where(function ($q) {
                        $q->where('pcu.progress', 0)->orWhereNull('pcu.progress');
                    });
                    break;
                case '1-25':
                    $cuSelect->whereBetween('cu.progress', [1, 25]);
                    $pcuSelect->whereBetween('pcu.progress', [1, 25]);
                    break;
                case '26-50':
                    $cuSelect->whereBetween('cu.progress', [26, 50]);
                    $pcuSelect->whereBetween('pcu.progress', [26, 50]);
                    break;
                case '51-75':
                    $cuSelect->whereBetween('cu.progress', [51, 75]);
                    $pcuSelect->whereBetween('pcu.progress', [51, 75]);
                    break;
                case '76-99':
                    $cuSelect->whereBetween('cu.progress', [76, 99]);
                    $pcuSelect->whereBetween('pcu.progress', [76, 99]);
                    break;
                case '100':
                    $cuSelect->where('cu.progress', 100);
                    $pcuSelect->where('pcu.progress', 100);
                    break;
            }
        }

        // 6. Classification Filter Pushdown (via indexed content_id)
        $classSlug = $filters['classification_slug'] ?? ($type === 'category' ? $slug : null);
        if (filled($classSlug)) {
            $classContentIds = DB::table('content')
                ->join('classification', 'classification.id', '=', 'content.classification_id')
                ->where('classification.slug', $classSlug)
                ->pluck('content.id')
                ->toArray();
            $cuSelect->whereIn('cu.content_id', !empty($classContentIds) ? $classContentIds : [-1]);
            $pcuSelect->whereIn('pcu.content_id', !empty($classContentIds) ? $classContentIds : [-1]);
        }

        // 7. Content Filter Pushdown (via indexed content_id)
        $contentSlug = $filters['content_slug'] ?? ($type === 'content' ? $slug : null);
        if (filled($contentSlug)) {
            $contentId = DB::table('content')->where('slug', $contentSlug)->value('id');
            $cuSelect->where('cu.content_id', $contentId ?: -1);
            $pcuSelect->where('pcu.content_id', $contentId ?: -1);
        }

        // 8. Employee ID / NIP / Name Filter Pushdown (via indexed user_id)
        $employeeParam = $filters['employee_id'] ?? ($filters['user_id'] ?? ($filters['employee'] ?? null));
        if (filled($employeeParam) && $employeeParam !== 'all') {
            $userQuery = DB::table('users')
                ->where('id_emp', 'like', '%' . $employeeParam . '%')
                ->orWhere('name', 'like', '%' . $employeeParam . '%');
            if (is_numeric($employeeParam)) {
                $userQuery->orWhere('id', (int) $employeeParam);
            }
            $userIds = $userQuery->pluck('id')->toArray();
            $cuSelect->whereIn('cu.user_id', !empty($userIds) ? $userIds : [-1]);
            $pcuSelect->whereIn('pcu.user_id', !empty($userIds) ? $userIds : [-1]);
        }

        // 9. Learning Title Filter Pushdown (via indexed content_id and trainingplan_id)
        $learningTitleParam = $filters['learning_title'] ?? ($filters['title'] ?? ($filters['content_title'] ?? null));
        if (filled($learningTitleParam)) {
            $contentIds = DB::table('content')->where('title', 'like', '%' . $learningTitleParam . '%')->pluck('id')->toArray();
            $tpIds = DB::table('trainingplan')->where('title', 'like', '%' . $learningTitleParam . '%')->pluck('id')->toArray();

            $cuSelect->whereIn('cu.content_id', !empty($contentIds) ? $contentIds : [-1]);
            $pcuSelect->where(function ($q) use ($contentIds, $tpIds) {
                $matched = false;
                if (!empty($contentIds)) {
                    $q->whereIn('pcu.content_id', $contentIds);
                    $matched = true;
                }
                if (!empty($tpIds)) {
                    if ($matched) {
                        $q->orWhereIn('pcu.trainingplan_id', $tpIds);
                    } else {
                        $q->whereIn('pcu.trainingplan_id', $tpIds);
                        $matched = true;
                    }
                }
                if (!$matched) {
                    $q->whereRaw('1 = 0');
                }
            });
        }

        // 10. Search Param Pushdown
        $searchParam = $filters['search'] ?? null;
        $search = is_array($searchParam)
            ? trim((string) ($searchParam['value'] ?? ''))
            : trim((string) ($searchParam ?? ''));

        if ($search !== '' && !filled($employeeParam) && !filled($learningTitleParam)) {
            $searchLower = strtolower($search);

            $searchUserIds = DB::table('users')
                ->where('name', 'like', "%{$search}%")
                ->orWhere('id_emp', 'like', "%{$search}%")
                ->pluck('id')
                ->toArray();

            $searchContentIds = DB::table('content')
                ->leftJoin('classification', 'classification.id', '=', 'content.classification_id')
                ->where('content.title', 'like', "%{$search}%")
                ->orWhere('content.duration', 'like', "%{$search}%")
                ->orWhere('classification.classification_name', 'like', "%{$search}%")
                ->pluck('content.id')
                ->toArray();

            $searchTpIds = DB::table('trainingplan')
                ->where('title', 'like', "%{$search}%")
                ->pluck('id')
                ->toArray();

            $statusMatches = [];
            foreach (['mandatory', 'optional', 'playlist'] as $st) {
                if (str_contains($st, $searchLower)) {
                    $statusMatches[] = $st;
                }
            }
            $matchCatContent = str_contains('all content', $searchLower);
            $matchCatPlaylist = str_contains('playlist', $searchLower);

            $cuSelect->where(function ($q) use ($searchUserIds, $searchContentIds, $statusMatches, $matchCatContent) {
                $matched = false;
                if (!empty($searchUserIds)) {
                    $q->orWhereIn('cu.user_id', $searchUserIds);
                    $matched = true;
                }
                if (!empty($searchContentIds)) {
                    $q->orWhereIn('cu.content_id', $searchContentIds);
                    $matched = true;
                }
                if (!empty($statusMatches)) {
                    $q->orWhereIn('cu.status', $statusMatches);
                    $matched = true;
                }
                if ($matchCatContent) {
                    $q->orWhereRaw('1 = 1');
                    $matched = true;
                }
                if (!$matched) {
                    $q->whereRaw('1 = 0');
                }
            });

            $pcuSelect->where(function ($q) use ($searchUserIds, $searchContentIds, $searchTpIds, $statusMatches, $matchCatPlaylist) {
                $matched = false;
                if (!empty($searchUserIds)) {
                    $q->orWhereIn('pcu.user_id', $searchUserIds);
                    $matched = true;
                }
                if (!empty($searchContentIds)) {
                    $q->orWhereIn('pcu.content_id', $searchContentIds);
                    $matched = true;
                }
                if (!empty($searchTpIds)) {
                    $q->orWhereIn('pcu.trainingplan_id', $searchTpIds);
                    $matched = true;
                }
                if (in_array('playlist', $statusMatches) || $matchCatPlaylist) {
                    $q->orWhereRaw('1 = 1');
                    $matched = true;
                }
                if (!$matched) {
                    $q->whereRaw('1 = 0');
                }
            });
        }

        // Union Assembly
        if ($categoryParam === 'all_content') {
            $union = $cuSelect;
        } elseif ($categoryParam === 'playlist') {
            $union = $pcuSelect;
        } else {
            $union = $cuSelect->unionAll($pcuSelect);
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
                'unified.category_source',
                'unified.user_id',
                'unified.content_id',
                'unified.trainingplan_id',
                'unified.status',
                'unified.finish',
                'unified.progress',
                'unified.created_at',
                'u.id_emp',
                'u.id_emp as user_id_emp',
                'u.name as employee_name',
                'u.name as user_name',
                'u.department as user_department',
                'u.team as user_team',
                'u.ssohash as user_ssohash',
                'c.title as content_title',
                'c.slug as content_slug',
                'c.type as content_type',
                'c.duration as content_duration',
                'cl.id as classification_id',
                'cl.classification_name',
                'cl.slug as classification_slug',
                'tp.title as playlist_title',
            ]);

        // 11. Duration Filter (calculated from content duration)
        $duration = $filters['duration'] ?? null;
        if (filled($duration) && $duration !== 'all') {
            $durationInput = strtolower(trim((string) $duration));
            $durationRange = match ($durationInput) {
                'lt10', 'under_10', '< 10 minutes', '<10min', '<10' => [null, 600, false, true],
                '10-30', '10_30', '10 - 30 minutes', '10-30min' => [600, 1800, true, false],
                '30-60', '30_60', '30 - 60 minutes', '30-60min' => [1800, 3600, false, false],
                'gt60', 'above_60', '> 1 hour', '>1hour', '>60', '>60min' => [3600, null, false, false],
                default => null,
            };

            if ($durationRange !== null) {
                [$minimum, $maximum, $includeMinimum, $excludeMaximum] = $durationRange;
                $durationInSeconds = "CASE
                    WHEN c.duration IS NULL OR TRIM(c.duration) = '' THEN NULL
                    WHEN LENGTH(c.duration) - LENGTH(REPLACE(c.duration, ':', '')) = 1 THEN
                        CAST(SUBSTRING_INDEX(c.duration, ':', 1) AS UNSIGNED) * 60
                        + CAST(SUBSTRING_INDEX(c.duration, ':', -1) AS UNSIGNED)
                    WHEN LENGTH(c.duration) - LENGTH(REPLACE(c.duration, ':', '')) = 2 THEN
                        CAST(SUBSTRING_INDEX(c.duration, ':', 1) AS UNSIGNED) * 3600
                        + CAST(SUBSTRING_INDEX(SUBSTRING_INDEX(c.duration, ':', 2), ':', -1) AS UNSIGNED) * 60
                        + CAST(SUBSTRING_INDEX(c.duration, ':', -1) AS UNSIGNED)
                    ELSE NULL
                END";

                if ($minimum !== null) {
                    $query->whereRaw($durationInSeconds . ($includeMinimum ? ' >= ?' : ' > ?'), [$minimum]);
                }
                if ($maximum !== null) {
                    $query->whereRaw($durationInSeconds . ($excludeMaximum ? ' < ?' : ' <= ?'), [$maximum]);
                }
            }
        }

        // 12. Sorting
        $sort = $filters['sort'] ?? null;
        if (filled($sort)) {
            switch ($sort) {
                case 'newest':
                    $query->orderBy('unified.created_at', 'desc');
                    break;
                case 'oldest':
                    $query->orderBy('unified.created_at', 'asc');
                    break;
                case 'highest_progress':
                    $query->orderBy('unified.progress', 'desc');
                    break;
                case 'lowest_progress':
                    $query->orderBy('unified.progress', 'asc');
                    break;
            }
        }

        return $query;
    }

    public function make(Request $request)
    {
        $categoryParam = strtolower(trim((string) ($request->input('category') ?? ($request->input('learning_source') ?? ($request->input('source') ?? 'all')))));

        $hasFilters = filled($request->input('status'))
            || filled($request->input('program_type'))
            || filled($request->input('program'))
            || filled($request->input('year'))
            || filled($request->input('from_date'))
            || filled($request->input('to_date'))
            || filled($request->input('progress_range'))
            || filled($request->input('duration'))
            || filled($request->input('classification_slug'))
            || filled($request->input('content_slug'))
            || filled($request->input('employee_id'))
            || filled($request->input('user_id'))
            || filled($request->input('employee'))
            || filled($request->input('learning_title'))
            || filled($request->input('title'))
            || filled($request->input('content_title'))
            || filled(trim((string) $request->input('search.value')))
            || (is_string($request->input('search')) && trim($request->input('search')) !== '');

        $totalAll = ContentUser::count() + PlaylistContentUser::count();

        $query = self::buildBaseQuery($request->all());

        $dataTable = DataTables::query($query)
            ->setTotalRecords($totalAll);

        if ($hasFilters) {
            $dataTable->setFilteredRecords($query->count());
        } elseif ($categoryParam === 'all_content') {
            $dataTable->setFilteredRecords(ContentUser::count());
        } elseif ($categoryParam === 'playlist') {
            $dataTable->setFilteredRecords(PlaylistContentUser::count());
        } else {
            $dataTable->setFilteredRecords($totalAll);
        }

        return $dataTable
            ->addIndexColumn()
            ->setRowId(fn ($row) => $row->source_type . ':' . $row->source_id)
            ->addColumn('status_badge', fn ($row) => $this->statusBadge($row))
            ->addColumn('id_emp', fn ($row) => $this->idEmpColumn($row))
            ->addColumn('employee', fn ($row) => $this->employeeColumn($row))
            ->addColumn('learning_title', fn ($row) => $this->learningTitleColumn($row))
            ->addColumn('classification_name', fn ($row) => $this->classificationColumn($row))
            ->addColumn('category_badge', fn ($row) => $this->categoryBadge($row))
            ->addColumn('duration', fn ($row) => e(DurationFormatter::format($row->content_duration ?? null)))
            ->addColumn('learning_date', fn ($row) => $row->created_at ? Carbon::parse($row->created_at)->format('d M Y') : '-')
            ->addColumn('progress_bar', fn ($row) => $this->progressBar($row))
            ->addColumn('action', fn ($row) => $this->actionColumn($row))
            ->orderColumn('status_badge', 'unified.status $1')
            ->orderColumn('id_emp', 'u.id_emp $1')
            ->orderColumn('employee', 'u.name $1')
            ->orderColumn('learning_title', 'c.title $1')
            ->orderColumn('classification_name', 'cl.classification_name $1')
            ->orderColumn('category_badge', 'unified.category_source $1')
            ->orderColumn('duration', 'c.duration $1')
            ->orderColumn('learning_date', 'unified.created_at $1')
            ->orderColumn('progress_bar', 'unified.progress $1')
            ->rawColumns([
                'status_badge',
                'id_emp',
                'employee',
                'learning_title',
                'classification_name',
                'category_badge',
                'progress_bar',
                'action',
            ])
            ->make(true);
    }

    private function statusBadge($row): string
    {
        $status = (string) ($row->status ?? '-');
        $lower = strtolower($status);

        if ($lower === 'mandatory') {
            return '<span class="badge bg-red-lt text-red">Mandatory</span>';
        }

        if ($lower === 'optional') {
            return '<span class="badge bg-blue-lt text-blue">Optional</span>';
        }

        if ($lower === 'playlist') {
            return '<span class="badge bg-purple-lt text-purple">Playlist</span>';
        }

        return '<span class="badge bg-secondary-lt text-secondary">' . e(ucfirst($status)) . '</span>';
    }

    private function categoryBadge($row): string
    {
        if ($row->category_source === 'Playlist') {
            return '<span class="badge bg-green-lt text-green">Playlist</span>';
        }

        return '<span class="badge bg-azure-lt text-azure">All Content</span>';
    }

    private function idEmpColumn($row): string
    {
        $idEmp = $row->id_emp ?? '-';

        return '<span class="fw-semibold text-secondary">' . e($idEmp) . '</span>';
    }

    private function employeeColumn($row): string
    {
        $name = $row->employee_name ?? '-';

        if (empty($row->user_ssohash)) {
            return e($name);
        }

        return '<a href="' . route('user.manage.profile', $row->user_ssohash) . '" class="fw-semibold">' . e($name) . '</a>';
    }

    private function learningTitleColumn($row): string
    {
        if (empty($row->content_title)) {
            return '-';
        }

        $icon = in_array($row->content_type, ['Video', 'YouTube'], true)
            ? '<span class="avatar avatar-sm bg-red-lt text-red me-2"><i class="ti ti-video"></i></span>'
            : '<span class="avatar avatar-sm bg-yellow-lt text-yellow me-2"><i class="ti ti-file-type-pdf"></i></span>';

        $titleLink = !empty($row->content_slug)
            ? '<a href="' . route('learning.manage.content', $row->content_slug) . '" class="text-reset fw-semibold">' . e($row->content_title) . '</a>'
            : '<span class="fw-semibold">' . e($row->content_title) . '</span>';

        $playlistSubtext = '';
        if ($row->category_source === 'Playlist' && !empty($row->playlist_title)) {
            $playlistSubtext = '<div class="text-muted small" style="font-size: 0.78rem;"><i class="ti ti-list-details me-1"></i>Playlist: ' . e($row->playlist_title) . '</div>';
        }

        return '<div class="d-flex align-items-center">'
            . $icon
            . '<div>'
            . $titleLink
            . $playlistSubtext
            . '</div></div>';
    }

    private function classificationColumn($row): string
    {
        if (empty($row->classification_name)) {
            return '-';
        }

        if (!empty($row->classification_slug)) {
            return '<a href="' . route('learning.manage.category', $row->classification_slug) . '" class="text-reset">'
                . e($row->classification_name)
                . '</a>';
        }

        return e($row->classification_name);
    }

    private function progressBar($row): string
    {
        $progress = max(0, min(100, (int) ($row->progress ?? 0)));

        return '<div class="d-flex align-items-center gap-2">'
            . '<div class="progress progress-sm flex-fill" style="min-width:80px;">'
            . '<div class="progress-bar bg-primary" style="width: ' . $progress . '%"></div>'
            . '</div>'
            . '<span class="small text-muted">' . $progress . '%</span>'
            . '</div>';
    }

    private function actionColumn($row): string
    {
        if (empty($row->content_slug)) {
            return '-';
        }

        return '<a href="' . route('learning.manage.content', $row->content_slug) . '" class="btn btn-sm btn-outline-primary rounded-pill">'
            . '<i class="ti ti-eye me-1"></i> View'
            . '</a>';
    }
}