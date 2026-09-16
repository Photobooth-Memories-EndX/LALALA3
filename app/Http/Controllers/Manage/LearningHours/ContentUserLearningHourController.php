<?php

namespace App\Http\Controllers\Manage\LearningHours;

use App\Http\Controllers\Controller;
use App\Models\Classification;
use App\Models\ContentUser;
use App\Models\PlaylistContentUser;
use App\Services\LearningHours\ContentUserLearningHourManagementService;
use App\Support\DurationFormatter;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Yajra\DataTables\Facades\DataTables;

class ContentUserLearningHourController extends Controller
{
    public function __construct(
        protected ContentUserLearningHourManagementService $service
    ) {}

    /**
     * Display the main management page.
     */
    public function index()
    {
        $statistics = $this->service->getStatistics();
        $classifications = Classification::orderBy('classification_name')->get();
        $years = ContentUser::whereNotNull('completed_at')
            ->selectRaw('DISTINCT YEAR(completed_at) as year')
            ->orderByDesc('year')
            ->pluck('year')
            ->toArray();

        if (empty($years)) {
            $years = [2026, 2025, 2024, 2023];
        }

        return view('manage.learning-hours.content-user.index', [
            'statistics' => $statistics,
            'classifications' => $classifications,
            'years' => $years,
        ]);
    }

    /**
     * AJAX endpoint for Yajra DataTables Server-Side Processing.
     */
    public function datatable(Request $request): JsonResponse
    {
        if ($request->input('source') === 'playlist') {
            $query = $this->service->buildPlaylistDatatableQuery($request);

            return DataTables::eloquent($query)
                ->filter(function ($query) use ($request) {
                    $this->applyPlaylistGlobalSearch($query, $request);
                })
                ->orderColumn('learning_hours_badge', 'playlist_content_users.learning_hour $1')
                ->orderColumn('learning_hours', 'playlist_content_users.learning_hour $1')
                ->orderColumn('learning_hour', 'playlist_content_users.learning_hour $1')
                ->orderColumn('progress_bar', 'playlist_content_users.progress $1')
                ->orderColumn('progress', 'playlist_content_users.progress $1')
                ->orderColumn('completed_at', 'playlist_content_users.completed_at $1')
                ->addColumn('checkbox', function (PlaylistContentUser $row) {
                    return '<input type="checkbox" class="form-check-input record-checkbox" value="' . $row->id . '">';
                })
                ->addIndexColumn()
                ->addColumn('employee', function (PlaylistContentUser $row) {
                    $name = $row->user->name ?? '-';
                    $idEmp = $row->user->id_emp ?? null;
                    $ssohash = $row->user->ssohash ?? null;

                    $empInfo = $idEmp ? '<div class="small text-muted font-monospace">' . e($idEmp) . '</div>' : '';

                    if ($ssohash) {
                        return '<div class="d-flex flex-column">'
                            . '<a href="' . route('user.manage.profile', $ssohash) . '" class="fw-semibold text-reset">'
                            . e($name)
                            . '</a>'
                            . $empInfo
                            . '</div>';
                    }

                    return '<div class="d-flex flex-column"><span class="fw-semibold">' . e($name) . '</span>' . $empInfo . '</div>';
                })
                ->addColumn('content_title', function (PlaylistContentUser $row) {
                    if (!$row->content) {
                        return '<span class="text-muted fst-italic">Content unavailable</span>';
                    }

                    $type = $row->content->type ?? 'Content';
                    $icon = in_array($type, ['Video', 'YouTube'], true)
                        ? '<i class="ti ti-video text-danger"></i>'
                        : '<i class="ti ti-file-type-pdf text-warning"></i>';

                    $planTitle = $row->trainingPlan ? '<div class="small text-muted text-truncate" style="max-width: 280px;" title="' . e($row->trainingPlan->title) . '"><i class="ti ti-list me-1"></i>' . e($row->trainingPlan->title) . '</div>' : '';

                    return '<div class="d-flex align-items-center gap-2">'
                        . '<span class="avatar avatar-xs bg-transparent">' . $icon . '</span>'
                        . '<div class="d-flex flex-column">'
                        . '<span class="text-truncate" style="max-width: 280px;" title="' . e($row->content->title) . '">' . e($row->content->title) . '</span>'
                        . $planTitle
                        . '</div>'
                        . '</div>';
                })
                ->addColumn('content_type', function (PlaylistContentUser $row) {
                    $type = $row->content->type ?? '-';
                    $badgeClass = match ($type) {
                        'Video' => 'bg-danger-lt text-danger',
                        'YouTube' => 'bg-red-lt text-red',
                        'Document' => 'bg-warning-lt text-warning',
                        default => 'bg-secondary-lt text-secondary',
                    };

                    return '<span class="badge ' . $badgeClass . '">' . e($type) . '</span>';
                })
                ->addColumn('duration', function (PlaylistContentUser $row) {
                    return e(DurationFormatter::format($row->content->duration ?? null));
                })
                ->addColumn('progress_bar', function (PlaylistContentUser $row) {
                    $p = (int) ($row->progress ?? 0);
                    $barColor = $p >= 100 ? 'bg-success' : ($p > 0 ? 'bg-primary' : 'bg-secondary');

                    return '<div class="d-flex align-items-center gap-2" style="min-width: 100px;">'
                        . '<div class="progress flex-grow-1" style="height: 6px;">'
                        . '<div class="progress-bar ' . $barColor . '" style="width: ' . $p . '%"></div>'
                        . '</div>'
                        . '<span class="small text-muted font-monospace">' . $p . '%</span>'
                        . '</div>';
                })
                ->addColumn('learning_hours_badge', function (PlaylistContentUser $row) {
                    if ($row->learning_hour === null) {
                        return '<span class="badge bg-yellow-lt text-yellow"><i class="ti ti-clock-pause me-1"></i> Not Calculated</span>';
                    }

                    return '<span class="badge bg-teal-lt text-teal fw-bold"><i class="ti ti-clock-check me-1"></i> '
                        . e($this->service->formatHours((float) $row->learning_hour))
                        . '</span>';
                })
                ->addColumn('completed_at', function (PlaylistContentUser $row) {
                    return $row->completed_at ? $row->completed_at->format('d M Y H:i') : '-';
                })
                ->addColumn('year', function (PlaylistContentUser $row) {
                    return $row->completed_at ? $row->completed_at->format('Y') : '-';
                })
                ->addColumn('status_badge', function (PlaylistContentUser $row) {
                    return '<span class="badge bg-cyan-lt text-cyan">Playlist</span>';
                })
                ->addColumn('action', function (PlaylistContentUser $row) {
                    return '<button type="button" class="btn btn-sm btn-outline-primary btn-recalculate-single rounded-pill" data-id="' . $row->id . '" data-context="playlist">'
                        . '<i class="ti ti-refresh me-1"></i> Recalculate'
                        . '</button>';
                })
                ->rawColumns([
                    'checkbox',
                    'employee',
                    'content_title',
                    'content_type',
                    'progress_bar',
                    'learning_hours_badge',
                    'status_badge',
                    'action',
                ])
                ->toJson();
        }

        $query = $this->service->buildDatatableQuery($request);

        return DataTables::eloquent($query)
            ->filter(function ($query) use ($request) {
                $this->applyGlobalSearch($query, $request);
            })
            ->orderColumn('learning_hours_badge', 'content_users.learning_hours $1')
            ->orderColumn('learning_hours', 'content_users.learning_hours $1')
            ->orderColumn('progress_bar', 'content_users.progress $1')
            ->orderColumn('progress', 'content_users.progress $1')
            ->orderColumn('completed_at', 'content_users.completed_at $1')
            ->addColumn('checkbox', function (ContentUser $row) {
                return '<input type="checkbox" class="form-check-input record-checkbox" value="' . $row->id . '">';
            })
            ->addIndexColumn()
            ->addColumn('employee', function (ContentUser $row) {
                $name = $row->user->name ?? '-';
                $idEmp = $row->user->id_emp ?? null;
                $ssohash = $row->user->ssohash ?? null;

                $empInfo = $idEmp ? '<div class="small text-muted font-monospace">' . e($idEmp) . '</div>' : '';

                if ($ssohash) {
                    return '<div class="d-flex flex-column">'
                        . '<a href="' . route('user.manage.profile', $ssohash) . '" class="fw-semibold text-reset">'
                        . e($name)
                        . '</a>'
                        . $empInfo
                        . '</div>';
                }

                return '<div class="d-flex flex-column"><span class="fw-semibold">' . e($name) . '</span>' . $empInfo . '</div>';
            })
            ->addColumn('content_title', function (ContentUser $row) {
                if (!$row->content) {
                    return '<span class="text-muted fst-italic">Content unavailable</span>';
                }

                $type = $row->content->type ?? 'Content';
                $icon = in_array($type, ['Video', 'YouTube'], true)
                    ? '<i class="ti ti-video text-danger"></i>'
                    : '<i class="ti ti-file-type-pdf text-warning"></i>';

                return '<div class="d-flex align-items-center gap-2">'
                    . '<span class="avatar avatar-xs bg-transparent">' . $icon . '</span>'
                    . '<span class="text-truncate" style="max-width: 280px;" title="' . e($row->content->title) . '">'
                    . e($row->content->title)
                    . '</span>'
                    . '</div>';
            })
            ->addColumn('content_type', function (ContentUser $row) {
                $type = $row->content->type ?? '-';
                $badgeClass = match ($type) {
                    'Video' => 'bg-danger-lt text-danger',
                    'YouTube' => 'bg-red-lt text-red',
                    'Document' => 'bg-warning-lt text-warning',
                    default => 'bg-secondary-lt text-secondary',
                };

                return '<span class="badge ' . $badgeClass . '">' . e($type) . '</span>';
            })
            ->addColumn('duration', function (ContentUser $row) {
                return e(DurationFormatter::format($row->content->duration ?? null));
            })
            ->addColumn('progress_bar', function (ContentUser $row) {
                $p = (int) ($row->progress ?? 0);
                $barColor = $p >= 100 ? 'bg-success' : ($p > 0 ? 'bg-primary' : 'bg-secondary');

                return '<div class="d-flex align-items-center gap-2" style="min-width: 100px;">'
                    . '<div class="progress flex-grow-1" style="height: 6px;">'
                    . '<div class="progress-bar ' . $barColor . '" style="width: ' . $p . '%"></div>'
                    . '</div>'
                    . '<span class="small text-muted font-monospace">' . $p . '%</span>'
                    . '</div>';
            })
            ->addColumn('learning_hours_badge', function (ContentUser $row) {
                if ($row->learning_hours === null) {
                    return '<span class="badge bg-yellow-lt text-yellow"><i class="ti ti-clock-pause me-1"></i> Not Calculated</span>';
                }

                return '<span class="badge bg-teal-lt text-teal fw-bold"><i class="ti ti-clock-check me-1"></i> '
                    . e($this->service->formatHours((float) $row->learning_hours))
                    . '</span>';
            })
            ->addColumn('completed_at', function (ContentUser $row) {
                return $row->completed_at ? $row->completed_at->format('d M Y H:i') : '-';
            })
            ->addColumn('year', function (ContentUser $row) {
                return $row->completed_at ? $row->completed_at->format('Y') : '-';
            })
            ->addColumn('status_badge', function (ContentUser $row) {
                $status = $row->status ?? 'optional';
                $color = $status === 'mandatory' ? 'red' : 'blue';

                return '<span class="badge bg-' . $color . '-lt text-' . $color . '">' . e(ucfirst($status)) . '</span>';
            })
            ->addColumn('action', function (ContentUser $row) {
                return '<button type="button" class="btn btn-sm btn-outline-primary btn-recalculate-single rounded-pill" data-id="' . $row->id . '" data-context="direct">'
                    . '<i class="ti ti-refresh me-1"></i> Recalculate'
                    . '</button>';
            })
            ->rawColumns([
                'checkbox',
                'employee',
                'content_title',
                'content_type',
                'progress_bar',
                'learning_hours_badge',
                'status_badge',
                'action',
            ])
            ->toJson();
    }

    /**
     * Calculate learning hours for selected records.
     */
    public function calculateSelected(Request $request): JsonResponse
    {
        $request->validate([
            'ids' => 'required|array|min:1',
            'ids.*' => 'integer',
            'context' => 'nullable|in:auto,direct,playlist',
        ]);

        $ids = $request->input('ids', []);
        $context = $request->input('context', 'auto');
        $result = $this->service->calculateSelected($ids, force: false, context: $context);
        $statistics = ($context === 'playlist') ? $this->service->getPlaylistStatistics() : $this->service->getStatistics();

        return response()->json([
            'success' => true,
            'message' => "Successfully calculated {$result['updated']} records ({$result['skipped']} already up to date, {$result['failed']} failed).",
            'result' => $result,
            'statistics' => $statistics,
        ]);
    }

    /**
     * Recalculate learning hours for selected records.
     */
    public function recalculateSelected(Request $request): JsonResponse
    {
        $request->validate([
            'ids' => 'required|array|min:1',
            'ids.*' => 'integer',
            'context' => 'nullable|in:auto,direct,playlist',
        ]);

        $ids = $request->input('ids', []);
        $context = $request->input('context', 'auto');
        $result = $this->service->recalculateSelected($ids, context: $context);
        $statistics = ($context === 'playlist') ? $this->service->getPlaylistStatistics() : $this->service->getStatistics();

        return response()->json([
            'success' => true,
            'message' => "Successfully recalculated {$result['updated']} records ({$result['failed']} failed).",
            'result' => $result,
            'statistics' => $statistics,
        ]);
    }

    /**
     * Start a bulk calculation batch.
     */
    public function startBatch(Request $request): JsonResponse
    {
        $request->validate([
            'type' => 'required|in:all_remaining,recalculate_all',
        ]);

        $type = $request->input('type', 'all_remaining');
        $batch = $this->service->startBatch($type, Auth::id());

        return response()->json([
            'success' => true,
            'batch_id' => $batch->id,
            'total_records' => $batch->total_records,
            'percentage' => $batch->percentage,
            'status' => $batch->status,
        ]);
    }

    /**
     * Process one chunk of the calculation batch via AJAX.
     */
    public function processBatch(Request $request): JsonResponse
    {
        $request->validate([
            'batch_id' => 'required|integer|exists:learning_hour_calculation_batches,id',
        ]);

        $batchId = (int) $request->input('batch_id');
        $result = $this->service->processBatchStep($batchId, chunkSize: 2500);

        if ($result['is_completed'] ?? false) {
            $result['statistics'] = $this->service->getStatistics();
        }

        return response()->json($result);
    }

    /**
     * Get live statistics.
     */
    public function statistics(Request $request): JsonResponse
    {
        $stats = ($request->input('source') === 'playlist')
            ? $this->service->getPlaylistStatistics()
            : $this->service->getStatistics();

        return response()->json([
            'success' => true,
            'statistics' => $stats,
        ]);
    }

    /**
     * Apply search on user and content relationships for Direct Content.
     */
    protected function applyGlobalSearch($query, Request $request): void
    {
        $search = trim((string) $request->input('search.value'));

        if ($search === '') {
            return;
        }

        $query->where(function ($q) use ($search) {
            $q->where('content_users.status', 'like', '%' . $search . '%')
                ->orWhere('content_users.learning_hours', 'like', '%' . $search . '%')
                ->orWhere('content_users.progress', 'like', '%' . $search . '%')
                ->orWhereHas('user', function ($userQuery) use ($search) {
                    $userQuery->where('name', 'like', '%' . $search . '%')
                        ->orWhere('id_emp', 'like', '%' . $search . '%');
                })
                ->orWhereHas('content', function ($contentQuery) use ($search) {
                    $contentQuery->where('title', 'like', '%' . $search . '%')
                        ->orWhere('type', 'like', '%' . $search . '%')
                        ->orWhere('duration', 'like', '%' . $search . '%');
                });
        });
    }

    /**
     * Apply search on user and content relationships for Playlist Content.
     */
    protected function applyPlaylistGlobalSearch($query, Request $request): void
    {
        $search = trim((string) $request->input('search.value'));

        if ($search === '') {
            return;
        }

        $query->where(function ($q) use ($search) {
            $q->where('playlist_content_users.learning_hour', 'like', '%' . $search . '%')
                ->orWhere('playlist_content_users.progress', 'like', '%' . $search . '%')
                ->orWhereHas('user', function ($userQuery) use ($search) {
                    $userQuery->where('name', 'like', '%' . $search . '%')
                        ->orWhere('id_emp', 'like', '%' . $search . '%');
                })
                ->orWhereHas('trainingPlan', function ($tpQuery) use ($search) {
                    $tpQuery->where('title', 'like', '%' . $search . '%');
                })
                ->orWhereHas('content', function ($contentQuery) use ($search) {
                    $contentQuery->where('title', 'like', '%' . $search . '%')
                        ->orWhere('type', 'like', '%' . $search . '%')
                        ->orWhere('duration', 'like', '%' . $search . '%');
                });
        });
    }
}
