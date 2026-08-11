<?php

namespace App\Services\Learning;

use App\Models\ContentUser;
use App\Support\DurationFormatter;
use Illuminate\Http\Request;
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
            ->addColumn('employee', fn ($row) => $this->employeeColumn($row))
            ->addColumn('learning_title', fn ($row) => $this->learningTitleColumn($row))
            ->addColumn('classification_name', fn ($row) => $this->classificationColumn($row))
            ->addColumn('duration', fn ($row) => e(DurationFormatter::format($row->content->duration ?? null)))
            ->addColumn('learning_date', fn ($row) => optional($row->created_at)->format('d M Y'))
            ->addColumn('progress_bar', fn ($row) => $this->progressBar($row))
            ->addColumn('action', fn ($row) => $this->actionColumn($row))
            ->rawColumns([
                'status_badge',
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
            $query->where('status', $request->status);
        }

        if ($request->filled('year') && $request->year !== 'all') {
            $query->whereYear('created_at', $request->year);
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
    }

    private function applyGlobalSearch($query, Request $request): void
    {
        $search = trim((string) $request->input('search.value'));

        if ($search === '') {
            return;
        }

        $query->where(function ($q) use ($search) {
            $q->where('status', 'like', '%' . $search . '%')
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