<?php

namespace App\Http\Controllers\Manage\LearningHours;

use App\Http\Controllers\Controller;
use App\Models\Classification;
use App\Models\PlaylistContentUser;
use App\Models\TrainingPlan;
use App\Services\Learning\LearningHourService;
use App\Services\LearningHours\ManualPlaylistContentUserLearningHourService;
use App\Services\LearningHours\PlaylistContentUserLearningHourManagementService;
use App\Support\DurationFormatter;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Yajra\DataTables\Facades\DataTables;

class PlaylistContentUserLearningHourController extends Controller
{
    public function __construct(
        protected PlaylistContentUserLearningHourManagementService $service,
        protected LearningHourService $legacyLearningHourService
    ) {}

    /**
     * Display the main management page for Playlist Content User Learning Hours.
     */
    public function index()
    {
        $statistics = $this->service->getStatistics();
        $classifications = Classification::orderBy('classification_name')->get();
        $trainingPlans = TrainingPlan::orderBy('title')->get(['id', 'title']);

        $years = PlaylistContentUser::whereNotNull('completed_at')
            ->selectRaw('DISTINCT YEAR(completed_at) as year')
            ->orderByDesc('year')
            ->pluck('year')
            ->toArray();

        if (empty($years)) {
            $years = [2026, 2025, 2024, 2023];
        }

        return view('manage.learning-hours.playlist-content-user.index', [
            'statistics'      => $statistics,
            'classifications' => $classifications,
            'trainingPlans'   => $trainingPlans,
            'years'           => $years,
        ]);
    }

    /**
     * AJAX endpoint for Yajra DataTables Server-Side Processing.
     * Strictly READ-ONLY with 0 write operations and database-level numeric sorting.
     */
    public function datatable(Request $request): JsonResponse
    {
        // Safeguard pagination bounds against memory exhaustion
        $length = min(100, max(1, (int) $request->input('length', 20)));
        $start = max(0, (int) $request->input('start', 0));
        $request->merge(['length' => $length, 'start' => $start]);

        $query = $this->service->buildDatatableQuery($request);

        return DataTables::eloquent($query)
            ->orderColumn('actual_learning_hour', 'playlist_content_users.learning_hour $1')
            ->orderColumn('learning_hour', 'playlist_content_users.learning_hour $1')
            ->orderColumn('current_time', 'playlist_content_users.current_time $1')
            ->orderColumn('progress', 'playlist_content_users.progress $1')
            ->orderColumn('completed_at', 'playlist_content_users.completed_at $1')
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
            ->addColumn('playlist_title', function (PlaylistContentUser $row) {
                if (!$row->trainingPlan) {
                    return '<span class="text-muted fst-italic">-</span>';
                }

                return '<div class="fw-medium text-truncate" style="max-width: 220px;" title="' . e($row->trainingPlan->title) . '">'
                    . '<i class="ti ti-list me-1 text-primary"></i>'
                    . e($row->trainingPlan->title)
                    . '</div>';
            })
            ->addColumn('content_title', function (PlaylistContentUser $row) {
                if (!$row->content) {
                    return '<span class="text-muted fst-italic">Content unavailable</span>';
                }

                $type = $row->content->type ?? 'Content';
                $icon = in_array($type, ['Video', 'YouTube'], true)
                    ? '<i class="ti ti-video text-danger"></i>'
                    : '<i class="ti ti-file-type-pdf text-warning"></i>';

                return '<div class="d-flex align-items-center gap-2">'
                    . '<span class="avatar avatar-xs bg-transparent">' . $icon . '</span>'
                    . '<span class="text-truncate" style="max-width: 250px;" title="' . e($row->content->title) . '">'
                    . e($row->content->title)
                    . '</span>'
                    . '</div>';
            })
            ->addColumn('content_type', function (PlaylistContentUser $row) {
                $type = $row->content->type ?? '-';
                $badgeClass = match ($type) {
                    'Video'    => 'bg-danger-lt text-danger',
                    'YouTube'  => 'bg-red-lt text-red',
                    'Document' => 'bg-warning-lt text-warning',
                    default    => 'bg-secondary-lt text-secondary',
                };

                return '<span class="badge ' . $badgeClass . '">' . e($type) . '</span>';
            })
            ->addColumn('current_time_display', function (PlaylistContentUser $row) {
                $timeData = $this->service->formatCurrentTime((int) ($row->current_time ?? 0));
                $badgeClass = $timeData['seconds'] > 0 ? 'badge bg-blue-lt text-blue' : 'badge bg-secondary-lt text-secondary';

                return '<div class="d-flex flex-column align-items-start">'
                    . '<span class="font-monospace fw-semibold">' . e($timeData['hms']) . '</span>'
                    . '<span class="' . $badgeClass . ' mt-1" style="font-size: 11px;">' . e($timeData['readable']) . '</span>'
                    . '</div>';
            })
            ->addColumn('duration', function (PlaylistContentUser $row) {
                return '<span class="font-monospace text-muted">' . e(DurationFormatter::format($row->content->duration ?? null)) . '</span>';
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
            ->addColumn('actual_learning_hour', function (PlaylistContentUser $row) {
                if ($row->learning_hour === null) {
                    return '<span class="badge bg-yellow-lt text-yellow"><i class="ti ti-clock-pause me-1"></i> Not Calculated</span>';
                }

                $formatted = $this->service->formatActualHours((float) $row->learning_hour);

                return '<span class="badge bg-teal-lt text-teal fw-bold fs-6">'
                    . '<i class="ti ti-clock-check me-1"></i> '
                    . e($formatted)
                    . '</span>';
            })
            ->addColumn('master_learning_hour', function (PlaylistContentUser $row) {
                $masterHours = $row->content?->learning_hours;
                if ($masterHours === null && !empty($row->content?->duration)) {
                    $masterHours = $this->legacyLearningHourService->calculate($row->content->duration);
                }

                return '<span class="badge bg-secondary-lt text-secondary fw-semibold">'
                    . e($this->service->formatMasterHours($masterHours !== null ? (float) $masterHours : null))
                    . '</span>';
            })
            ->addColumn('calculation_source', function (PlaylistContentUser $row) {
                $source = $this->service->resolveCalculationSource($row);

                return '<span class="badge ' . $source['badge_class'] . '" title="' . e($source['description']) . '">'
                    . '<i class="ti ' . $source['icon'] . ' me-1"></i> '
                    . e($source['label'])
                    . '</span>';
            })
            ->addColumn('status_badge', function (PlaylistContentUser $row) {
                $isFinished = ((string) $row->finish === '1' || (int) $row->progress >= 100);

                if ($isFinished) {
                    return '<span class="badge bg-success-lt text-success"><i class="ti ti-check me-1"></i> Completed</span>';
                }

                return '<span class="badge bg-warning-lt text-warning"><i class="ti ti-loader me-1"></i> In Progress</span>';
            })
            ->addColumn('completed_at', function (PlaylistContentUser $row) {
                return $row->completed_at ? $row->completed_at->format('d M Y H:i') : '-';
            })
            ->rawColumns([
                'employee',
                'playlist_title',
                'content_title',
                'content_type',
                'current_time_display',
                'duration',
                'progress_bar',
                'actual_learning_hour',
                'master_learning_hour',
                'calculation_source',
                'status_badge',
            ])
            ->make(true);
    }

    /**
     * AJAX endpoint for statistics.
     */
    public function statistics(): JsonResponse
    {
        return response()->json($this->service->getStatistics());
    }

    /**
     * Manual bulk calculation for Playlist Content User Learning Hours.
     */
    public function calculate(Request $request, ManualPlaylistContentUserLearningHourService $manualService): JsonResponse
    {
        $validated = $request->validate([
            'mode'                => 'required|in:all,filtered',
            'dry_run'             => 'nullable|boolean',
            'force'               => 'nullable|boolean',
            'trainingplan_id'     => 'nullable',
            'calculation_source'  => 'nullable|in:all,actual,historical,zero',
            'finish_status'       => 'nullable|in:all,completed,in_progress',
            'content_type'        => 'nullable|in:all,Video,Document,YouTube',
            'classification_slug' => 'nullable|string',
            'year'                => 'nullable',
            'min_hour'            => 'nullable|numeric|min:0',
            'max_hour'            => 'nullable|numeric|min:0',
        ]);

        try {
            $criteria = [
                'mode'                => $validated['mode'],
                'trainingplan_id'     => $request->input('trainingplan_id'),
                'calculation_source'  => $request->input('calculation_source'),
                'finish_status'       => $request->input('finish_status'),
                'content_type'        => $request->input('content_type'),
                'classification_slug' => $request->input('classification_slug'),
                'year'                => $request->input('year'),
                'min_hour'            => $request->input('min_hour'),
                'max_hour'            => $request->input('max_hour'),
            ];

            $options = [
                'dry_run'    => $request->boolean('dry_run'),
                'force'      => $request->boolean('force'),
                'admin_id'   => Auth::id(),
                'admin_name' => Auth::user()?->name,
            ];

            $result = $manualService->calculate($criteria, $options);
            $statistics = $this->service->getStatistics();

            $message = $result['dry_run']
                ? "Dry run completed: {$result['total_scanned']} scanned, {$result['total_updated']} would update, {$result['total_skipped']} already accurate."
                : "Bulk calculation completed: {$result['total_updated']} updated, {$result['total_skipped']} skipped, {$result['total_failed']} failed.";

            return response()->json([
                'success'    => true,
                'message'    => $message,
                'summary'    => $result,
                'statistics' => $statistics,
            ]);
        } catch (\RuntimeException $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 423); // 423 Locked
        } catch (\Throwable $e) {
            return response()->json([
                'success' => false,
                'message' => 'Bulk calculation failed: ' . $e->getMessage(),
            ], 500);
        }
    }
}
