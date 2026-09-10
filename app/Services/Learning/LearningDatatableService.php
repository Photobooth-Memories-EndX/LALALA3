<?php

namespace App\Services\Learning;

use App\Http\Controllers\LearningManageController;
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
     * Build unified query builder for learning data (ContentUser + PlaylistContentUser post-cutoff).
     */
    public static function buildBaseQuery(array $filters = [], string $type = '', string $slug = ''): Builder
    {
        $cutoff = self::PLAYLIST_CUTOFF;

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
            ])
            ->where('pcu.created_at', '>', $cutoff);

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

        // 1. Status Filter
        $status = $filters['status'] ?? null;
        if (filled($status) && $status !== 'all') {
            $statusStr = (string) $status;
            if ($statusStr === 'finished' || $statusStr === 'finish' || $statusStr === '1') {
                $query->where('unified.finish', '1');
            } elseif ($statusStr === 'ongoing' || $statusStr === '0') {
                $query->where('unified.finish', '0')->where('unified.progress', '>', 0);
            } elseif ($statusStr === 'not_started') {
                $query->where('unified.finish', '0')->where(function ($q) {
                    $q->where('unified.progress', 0)->orWhereNull('unified.progress');
                });
            } elseif (in_array($statusStr, ['mandatory', 'optional'], true)) {
                $query->where('unified.status', $statusStr);
            }
        }

        // 2. Program Type Filter
        $programType = $filters['program_type'] ?? ($filters['program'] ?? null);
        if (filled($programType) && $programType !== 'all') {
            if ($programType === 'mandatory') {
                $query->where('unified.status', 'mandatory');
            } elseif ($programType === 'optional') {
                $query->where('unified.status', 'optional');
            } else {
                $query->where('unified.status', $programType);
            }
        }

        // 3. Year Filter
        $year = $filters['year'] ?? null;
        if (filled($year) && $year !== 'all') {
            $query->whereYear('unified.created_at', $year);
        }

        // 4. Date Range Filter
        $fromDate = $filters['from_date'] ?? ($filters['date_from'] ?? null);
        $toDate = $filters['to_date'] ?? ($filters['date_to'] ?? null);
        if (filled($fromDate) && filled($toDate)) {
            $query->whereBetween('unified.created_at', [$fromDate . ' 00:00:00', $toDate . ' 23:59:59']);
        } elseif (filled($fromDate)) {
            $query->whereDate('unified.created_at', '>=', $fromDate);
        } elseif (filled($toDate)) {
            $query->whereDate('unified.created_at', '<=', $toDate);
        }

        // 5. Progress Range Filter
        $progressRange = $filters['progress_range'] ?? null;
        if (filled($progressRange) && $progressRange !== 'all') {
            $progressVal = str_replace([' ', '%'], '', (string) $progressRange);
            switch ($progressVal) {
                case '0':
                    $query->where(function ($q) {
                        $q->where('unified.progress', 0)->orWhereNull('unified.progress');
                    });
                    break;
                case '1-25':
                    $query->whereBetween('unified.progress', [1, 25]);
                    break;
                case '26-50':
                    $query->whereBetween('unified.progress', [26, 50]);
                    break;
                case '51-75':
                    $query->whereBetween('unified.progress', [51, 75]);
                    break;
                case '76-99':
                    $query->whereBetween('unified.progress', [76, 99]);
                    break;
                case '100':
                    $query->where('unified.progress', 100);
                    break;
            }
        }

        // 6. Duration Filter
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

        // 7. Classification Filter
        $classSlug = $filters['classification_slug'] ?? ($type === 'category' ? $slug : null);
        if (filled($classSlug)) {
            $query->where('cl.slug', $classSlug);
        }

        // 8. Content Filter
        $contentSlug = $filters['content_slug'] ?? ($type === 'content' ? $slug : null);
        if (filled($contentSlug)) {
            $query->where('c.slug', $contentSlug);
        }

        // 9. Employee ID / NIP / Name Filter
        $employeeParam = $filters['employee_id'] ?? ($filters['user_id'] ?? ($filters['employee'] ?? null));
        if (filled($employeeParam) && $employeeParam !== 'all') {
            $query->where(function ($uq) use ($employeeParam) {
                $uq->where('u.id_emp', 'like', '%' . $employeeParam . '%')
                    ->orWhere('u.name', 'like', '%' . $employeeParam . '%');
                if (is_numeric($employeeParam)) {
                    $uq->orWhere('u.id', (int) $employeeParam);
                }
            });
        }

        // 10. Learning Title Filter
        $learningTitleParam = $filters['learning_title'] ?? ($filters['title'] ?? ($filters['content_title'] ?? null));
        if (filled($learningTitleParam)) {
            $query->where(function ($cq) use ($learningTitleParam) {
                $cq->where('c.title', 'like', '%' . $learningTitleParam . '%')
                    ->orWhere('tp.title', 'like', '%' . $learningTitleParam . '%');
            });
        }

        // 11. Search Param (Quick search or custom input search)
        $searchParam = $filters['search'] ?? null;
        $search = is_array($searchParam)
            ? trim((string) ($searchParam['value'] ?? ''))
            : trim((string) ($searchParam ?? ''));

        if ($search !== '' && !filled($employeeParam) && !filled($learningTitleParam)) {
            $query->where(function ($q) use ($search) {
                $q->where('unified.status', 'like', '%' . $search . '%')
                    ->orWhere('unified.category_source', 'like', '%' . $search . '%')
                    ->orWhere('u.name', 'like', '%' . $search . '%')
                    ->orWhere('u.id_emp', 'like', '%' . $search . '%')
                    ->orWhere('c.title', 'like', '%' . $search . '%')
                    ->orWhere('c.duration', 'like', '%' . $search . '%')
                    ->orWhere('cl.classification_name', 'like', '%' . $search . '%')
                    ->orWhere('tp.title', 'like', '%' . $search . '%');
            });
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
        $query = self::buildBaseQuery($request->all());

        return DataTables::query($query)
            ->filter(function ($query) use ($request) {
                $this->applyGlobalSearch($query, $request);
            })
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

    private function applyGlobalSearch($query, Request $request): void
    {
        $search = trim((string) $request->input('search.value'));

        if ($search === '') {
            return;
        }

        $query->where(function ($q) use ($search) {
            $q->where('unified.status', 'like', '%' . $search . '%')
                ->orWhere('unified.category_source', 'like', '%' . $search . '%')
                ->orWhere('u.name', 'like', '%' . $search . '%')
                ->orWhere('u.id_emp', 'like', '%' . $search . '%')
                ->orWhere('c.title', 'like', '%' . $search . '%')
                ->orWhere('c.duration', 'like', '%' . $search . '%')
                ->orWhere('cl.classification_name', 'like', '%' . $search . '%')
                ->orWhere('tp.title', 'like', '%' . $search . '%');
        });
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