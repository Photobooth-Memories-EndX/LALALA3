<?php

namespace App\Services\Learning;

use App\Models\ContentUser;
use App\Support\DurationFormatter;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Yajra\DataTables\Facades\DataTables;

class LearningDatatableService
{
    public function make(Request $request)
    {
        $query = ContentUser::with([
                'user',
                'content.classification',
            ])
            ->select('content_users.*');

        $this->applyFilters($query, $request);

        return DataTables::eloquent($query)
            ->filter(function ($query) use ($request) {
                $this->applyGlobalSearch($query, $request);
            })
            ->addIndexColumn()
            ->addColumn('status_badge', fn ($row) => $this->statusBadge($row))
            ->addColumn('id_emp', fn ($row) => $this->idEmpColumn($row))
            ->addColumn('employee', fn ($row) => $this->employeeColumn($row))
            ->addColumn('learning_title', fn ($row) => $this->learningTitleColumn($row))
            ->addColumn('classification_name', fn ($row) => $this->classificationColumn($row))
            ->addColumn('duration', fn ($row) => e(DurationFormatter::format($row->content->duration ?? null)))
            ->addColumn('learning_date', fn ($row) => optional($row->created_at)->format('d M Y'))
            ->addColumn('progress_bar', fn ($row) => $this->progressBar($row))
            ->addColumn('action', fn ($row) => $this->actionColumn($row))
            ->rawColumns([
                'status_badge',
                'id_emp',
                'employee',
                'learning_title',
                'classification_name',
                'progress_bar',
                'action',
            ])
            ->make(true);
    }

    private function applyFilters($query, Request $request): void
    {
        if ($request->filled('status') && $request->status !== 'all') {
            $status = (string) $request->status;
            if ($status === 'finished' || $status === 'finish' || $status === '1') {
                $query->where('content_users.finish', '1');
            } elseif ($status === 'ongoing' || $status === '0') {
                $query->where('content_users.finish', '0')->where('content_users.progress', '>', 0);
            } elseif ($status === 'not_started') {
                $query->where('content_users.finish', '0')->where(function ($q) {
                    $q->where('content_users.progress', 0)->orWhereNull('content_users.progress');
                });
            } elseif (in_array($status, ['mandatory', 'optional'], true)) {
                $query->where('content_users.status', $status);
            }
        }

        $programType = $request->input('program_type') ?? $request->input('program');
        if (filled($programType) && $programType !== 'all') {
            if ($programType === 'mandatory') {
                $query->where(function ($q) {
                    $q->where('content_users.status', 'mandatory');
                    if (\Illuminate\Support\Facades\Schema::hasColumn('content_users', 'is_mandatory')) {
                        $q->orWhere('content_users.is_mandatory', 1);
                    }
                });
            } elseif ($programType === 'optional') {
                $query->where(function ($q) {
                    $q->where('content_users.status', 'optional');
                    if (\Illuminate\Support\Facades\Schema::hasColumn('content_users', 'is_mandatory')) {
                        $q->orWhere('content_users.is_mandatory', 0);
                    }
                });
            } else {
                $query->where('content_users.status', $programType);
            }
        }

        if ($request->filled('year') && $request->year !== 'all') {
            $query->whereYear('content_users.created_at', $request->year);
        }

        $fromDate = $request->input('from_date') ?? $request->input('date_from');
        $toDate = $request->input('to_date') ?? $request->input('date_to');

        if (filled($fromDate) && filled($toDate)) {
            $query->whereBetween('content_users.created_at', [$fromDate . ' 00:00:00', $toDate . ' 23:59:59']);
        } elseif (filled($fromDate)) {
            $query->whereDate('content_users.created_at', '>=', $fromDate);
        } elseif (filled($toDate)) {
            $query->whereDate('content_users.created_at', '<=', $toDate);
        }

        if ($request->filled('progress_range') && $request->progress_range !== 'all') {
            $progressVal = str_replace([' ', '%'], '', (string) $request->progress_range);
            switch ($progressVal) {
                case '0':
                    $query->where(function ($q) {
                        $q->where('content_users.progress', 0)->orWhereNull('content_users.progress');
                    });
                    break;
                case '1-25':
                    $query->whereBetween('content_users.progress', [1, 25]);
                    break;
                case '26-50':
                    $query->whereBetween('content_users.progress', [26, 50]);
                    break;
                case '51-75':
                    $query->whereBetween('content_users.progress', [51, 75]);
                    break;
                case '76-99':
                    $query->whereBetween('content_users.progress', [76, 99]);
                    break;
                case '100':
                    $query->where('content_users.progress', 100);
                    break;
            }
        }

        if ($request->filled('duration') && $request->duration !== 'all') {
            $durationInput = strtolower(trim((string) $request->duration));
            $durationRange = match ($durationInput) {
                'lt10', 'under_10', '< 10 minutes', '<10min', '<10' => [null, 600, false, true],
                '10-30', '10_30', '10 - 30 minutes', '10-30min' => [600, 1800, true, false],
                '30-60', '30_60', '30 - 60 minutes', '30-60min' => [1800, 3600, false, false],
                'gt60', 'above_60', '> 1 hour', '>1hour', '>60', '>60min' => [3600, null, false, false],
                default => null,
            };

            if ($durationRange !== null) {
                [$minimum, $maximum, $includeMinimum, $excludeMaximum] = $durationRange;
                $isSqlite = DB::connection()->getDriverName() === 'sqlite';
                if (!$isSqlite) {
                    $durationInSeconds = "CASE
                        WHEN content.duration IS NULL OR TRIM(content.duration) = '' THEN NULL
                        WHEN LENGTH(content.duration) - LENGTH(REPLACE(content.duration, ':', '')) = 1 THEN
                            CAST(SUBSTRING_INDEX(content.duration, ':', 1) AS UNSIGNED) * 60
                            + CAST(SUBSTRING_INDEX(content.duration, ':', -1) AS UNSIGNED)
                        WHEN LENGTH(content.duration) - LENGTH(REPLACE(content.duration, ':', '')) = 2 THEN
                            CAST(SUBSTRING_INDEX(content.duration, ':', 1) AS UNSIGNED) * 3600
                            + CAST(SUBSTRING_INDEX(SUBSTRING_INDEX(content.duration, ':', 2), ':', -1) AS UNSIGNED) * 60
                            + CAST(SUBSTRING_INDEX(content.duration, ':', -1) AS UNSIGNED)
                        ELSE NULL
                    END";

                    $query->whereHas('content', function ($contentQ) use ($durationInSeconds, $minimum, $maximum, $includeMinimum, $excludeMaximum) {
                        if ($minimum !== null) {
                            $contentQ->whereRaw($durationInSeconds . ($includeMinimum ? ' >= ?' : ' > ?'), [$minimum]);
                        }
                        if ($maximum !== null) {
                            $contentQ->whereRaw($durationInSeconds . ($excludeMaximum ? ' < ?' : ' <= ?'), [$maximum]);
                        }
                    });
                }
            }
        }

        if ($request->filled('classification_slug')) {
            $query->whereHas('content.classification', function ($q) use ($request) {
                $q->where('slug', $request->classification_slug);
            });
        }

        if ($request->filled('content_slug')) {
            $query->whereHas('content', function ($q) use ($request) {
                $q->where('slug', $request->content_slug);
            });
        }

        // Filter by Employee ID / NIP / Name
        $employeeParam = $request->input('employee_id') ?? ($request->input('user_id') ?? $request->input('employee'));
        if (filled($employeeParam) && $employeeParam !== 'all') {
            if (is_numeric($employeeParam)) {
                $query->where('content_users.user_id', $employeeParam);
            } else {
                $query->whereHas('user', function ($userQuery) use ($employeeParam) {
                    $userQuery->where('name', 'like', '%' . $employeeParam . '%')
                        ->orWhere('id_emp', 'like', '%' . $employeeParam . '%');
                });
            }
        }

        // Filter by Learning Title
        $learningTitleParam = $request->input('learning_title') ?? ($request->input('title') ?? $request->input('content_title'));
        if (filled($learningTitleParam)) {
            $query->whereHas('content', function ($contentQuery) use ($learningTitleParam) {
                $contentQuery->where('title', 'like', '%' . $learningTitleParam . '%');
            });
        }

        if ($request->filled('search') && !filled($employeeParam) && !filled($learningTitleParam)) {
            $searchParam = $request->input('search');
            $customSearch = is_array($searchParam)
                ? trim((string) ($searchParam['value'] ?? ''))
                : trim((string) $searchParam);

            if ($customSearch !== '') {
                $query->where(function ($q) use ($customSearch) {
                    $q->where('content_users.status', 'like', '%' . $customSearch . '%')
                        ->orWhereHas('user', function ($userQuery) use ($customSearch) {
                            $userQuery->where('name', 'like', '%' . $customSearch . '%')
                                ->orWhere('id_emp', 'like', '%' . $customSearch . '%');
                        })
                        ->orWhereHas('content', function ($contentQuery) use ($customSearch) {
                            $contentQuery->where('title', 'like', '%' . $customSearch . '%')
                                ->orWhere('duration', 'like', '%' . $customSearch . '%')
                                ->orWhereHas('classification', function ($classificationQuery) use ($customSearch) {
                                    $classificationQuery->where('classification_name', 'like', '%' . $customSearch . '%');
                                });
                        });
                });
            }
        }

        if ($request->filled('sort')) {
            switch ($request->sort) {
                case 'newest':
                    $query->orderBy('content_users.created_at', 'desc');
                    break;
                case 'oldest':
                    $query->orderBy('content_users.created_at', 'asc');
                    break;
                case 'highest_progress':
                    $query->orderBy('content_users.progress', 'desc');
                    break;
                case 'lowest_progress':
                    $query->orderBy('content_users.progress', 'asc');
                    break;
            }
        }
    }

    private function applyGlobalSearch($query, Request $request): void
    {
        $search = trim((string) $request->input('search.value'));

        if ($search === '') {
            return;
        }

        $query->where(function ($q) use ($search) {
            $q->where('content_users.status', 'like', '%' . $search . '%')
                ->orWhereHas('user', function ($userQuery) use ($search) {
                    $userQuery->where('name', 'like', '%' . $search . '%')
                        ->orWhere('id_emp', 'like', '%' . $search . '%');
                })
                ->orWhereHas('content', function ($contentQuery) use ($search) {
                    $contentQuery->where('title', 'like', '%' . $search . '%')
                        ->orWhere('duration', 'like', '%' . $search . '%')
                        ->orWhereHas('classification', function ($classificationQuery) use ($search) {
                            $classificationQuery->where('classification_name', 'like', '%' . $search . '%');
                        });
                });
        });
    }

    private function statusBadge($row): string
    {
        $status = $row->status ?? '-';
        $color = $status === 'mandatory' ? 'red' : 'blue';

        return '<span class="badge bg-' . $color . '-lt text-' . $color . '">' . e(ucfirst($status)) . '</span>';
    }

    private function idEmpColumn($row): string
    {
        $idEmp = $row->user->id_emp ?? ($row->id_emp ?? '-');

        return '<span class="fw-semibold text-secondary">' . e($idEmp) . '</span>';
    }

    private function employeeColumn($row): string
    {
        $name = $row->user->name ?? '-';

        if (!$row->user || !$row->user->ssohash) {
            return e($name);
        }

        return '<a href="' . route('user.manage.profile', $row->user->ssohash) . '" class="fw-semibold">' . e($name) . '</a>';
    }

    private function learningTitleColumn($row): string
    {
        if (!$row->content) {
            return '-';
        }

        $icon = in_array($row->content->type, ['Video', 'YouTube'], true)
            ? '<span class="avatar avatar-sm bg-red-lt text-red me-2"><i class="ti ti-video"></i></span>'
            : '<span class="avatar avatar-sm bg-yellow-lt text-yellow me-2"><i class="ti ti-file-type-pdf"></i></span>';

        return '<div class="d-flex align-items-center">'
            . $icon
            . '<a href="' . route('learning.manage.content', $row->content->slug) . '" class="text-reset fw-semibold">'
            . e($row->content->title)
            . '</a></div>';
    }

    private function classificationColumn($row): string
    {
        $classification = $row->content->classification ?? null;

        if (!$classification) {
            return '-';
        }

        return '<a href="' . route('learning.manage.category', $classification->slug) . '" class="text-reset">'
            . e($classification->classification_name)
            . '</a>';
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
        if (!$row->content) {
            return '-';
        }

        return '<a href="' . route('learning.manage.content', $row->content->slug) . '" class="btn btn-sm btn-outline-primary rounded-pill">'
            . '<i class="ti ti-eye me-1"></i> View'
            . '</a>';
    }
}