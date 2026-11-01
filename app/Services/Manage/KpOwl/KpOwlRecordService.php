<?php

namespace App\Services\Manage\KpOwl;

use App\Queries\Manage\KpOwl\KpOwlRecordQuery;
use App\Support\DurationFormatter;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Yajra\DataTables\Facades\DataTables;

class KpOwlRecordService
{
    protected KpOwlRecordQuery $recordQuery;
    protected array $l2ScoreCache = [];

    public function __construct(KpOwlRecordQuery $recordQuery)
    {
        $this->recordQuery = $recordQuery;
    }

    /**
     * Process server-side AJAX DataTable request.
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
        $query = $this->recordQuery->buildQuery($filters);

        // Default sorting
        if (!$request->filled('order')) {
            $query->orderBy('unified.created_at', 'desc');
        }

        // Total KP OWL records
        $totalAll = (int) (DB::table('content_users')->count() + DB::table('playlist_content_users')->selectRaw('COUNT(DISTINCT user_id, trainingplan_id) as cnt')->value('cnt'));

        $dataTable = DataTables::of($query)
            ->setTotalRecords($totalAll);

        return $dataTable
            ->addIndexColumn()
            ->setRowId(fn ($row) => $row->source_type . ':' . $row->source_id)
            ->addColumn('employee_id', fn ($row) => $this->renderEmployeeId($row))
            ->addColumn('employee_name', fn ($row) => $this->renderEmployeeName($row))
            ->addColumn('team', fn ($row) => e($row->team ?: '-'))
            ->addColumn('department', fn ($row) => e($row->department ?: '-'))
            ->addColumn('company', fn ($row) => e($row->company_name ?: 'PT Krakatau Posco'))
            ->addColumn('course_title', fn ($row) => $this->renderCourseTitle($row))
            ->addColumn('type', fn ($row) => $this->renderTypeBadge($row))
            ->addColumn('category', fn ($row) => $this->renderCategoryBadge($row))
            ->addColumn('main_classification', fn ($row) => $this->renderClassification($row))
            ->addColumn('sub_classification', fn () => '<span class="text-muted small">-</span>')
            ->addColumn('delivery_method', fn () => '<span class="badge bg-blue-lt text-blue">Online</span>')
            ->addColumn('provider', fn () => '<span class="badge bg-blue-lt text-blue">Internal</span>')
            ->addColumn('provider_name', fn () => 'KP-OWL')
            ->addColumn('venue', fn () => 'Online LMS')
            ->addColumn('course_duration', fn ($row) => $this->renderCourseDuration($row))
            ->addColumn('total_recognized_duration', fn ($row) => $this->renderRecognizedDuration($row))
            ->addColumn('learning_hours', fn ($row) => $this->renderLearningHours($row))
            ->addColumn('progress', fn ($row) => $this->renderProgressBar($row))
            ->addColumn('progress_status', fn ($row) => $this->renderProgressStatusBadge($row))
            ->addColumn('learning_period', fn ($row) => $this->renderLearningPeriod($row))
            ->addColumn('record_month', fn ($row) => $this->renderRecordMonth($row))
            ->addColumn('requirement', fn ($row) => $this->renderRequirementBadge($row))
            ->addColumn('attendance', fn ($row) => $this->renderAttendanceBadge($row))
            ->addColumn('score_l1', fn () => '<span class="text-muted small">-</span>')
            ->addColumn('score_l2', fn ($row) => $this->renderScoreL2($row))
            ->addColumn('score_l3', fn () => '<span class="text-muted small">-</span>')
            ->addColumn('certificate_availability', fn ($row) => $this->renderCertificateBadge($row))
            ->addColumn('pic', fn () => '<span class="text-muted small">-</span>')
            ->addColumn('total_engagement_time', fn ($row) => $this->renderEngagementTime($row))
            ->addColumn('action', fn ($row) => $this->renderActionColumn($row))
            ->rawColumns([
                'employee_id',
                'employee_name',
                'course_title',
                'type',
                'category',
                'main_classification',
                'sub_classification',
                'delivery_method',
                'provider',
                'course_duration',
                'total_recognized_duration',
                'learning_hours',
                'progress',
                'progress_status',
                'learning_period',
                'requirement',
                'attendance',
                'score_l1',
                'score_l2',
                'score_l3',
                'certificate_availability',
                'pic',
                'total_engagement_time',
                'action',
            ])
            ->make(true);
    }

    protected function renderEmployeeId($row): string
    {
        $id = trim((string) ($row->id_emp ?: '-'));
        $clean = ($id !== '-') ? preg_replace('/\s*[\/\-]\s*NIP.*$/i', '', $id) : '-';
        return '<span class="fw-bold font-monospace text-dark">' . e($clean) . '</span>';
    }

    protected function renderEmployeeName($row): string
    {
        $name = e($row->employee_name ?: '-');
        $dept = e($row->department ?: '');
        $sub = $dept ? '<div class="text-muted small" style="font-size: 11px;">' . $dept . '</div>' : '';
        return '<div class="d-flex flex-column"><span class="fw-semibold text-truncate" style="max-width: 200px;">' . $name . '</span>' . $sub . '</div>';
    }

    protected function renderCourseTitle($row): string
    {
        $isPlaylist = $row->source_type === 'playlist';
        $title = $isPlaylist ? ($row->playlist_title ?: 'Playlist') : ($row->content_title ?: 'Untitled Content');
        $icon = $isPlaylist ? '<i class="ti ti-route me-1 text-purple"></i>' : '<i class="ti ti-file-text me-1 text-primary"></i>';
        return '<div class="d-flex align-items-center"><span class="text-truncate fw-medium" style="max-width: 260px;" title="' . e($title) . '">' . $icon . e($title) . '</span></div>';
    }

    protected function renderTypeBadge($row): string
    {
        if ($row->source_type === 'playlist') {
            return '<span class="badge" style="background-color: #8b5cf6; color: #ffffff; font-size: 11px; font-weight: 600; padding: 3px 8px; border-radius: 6px;">Playlist</span>';
        }
        $rawType = strtolower(trim((string) ($row->content_type ?? 'document')));
        if (in_array($rawType, ['video', 'youtube', 'mooc'], true)) {
            return '<span class="badge" style="background-color: #f59e0b; color: #ffffff; font-size: 11px; font-weight: 600; padding: 3px 8px; border-radius: 6px;">Video</span>';
        }
        return '<span class="badge" style="background-color: #0ea5e9; color: #ffffff; font-size: 11px; font-weight: 600; padding: 3px 8px; border-radius: 6px;">Document</span>';
    }

    protected function renderCategoryBadge($row): string
    {
        if ($row->source_type === 'playlist') {
            return '<span class="badge bg-purple-lt text-purple">Playlist</span>';
        }
        return '<span class="badge bg-azure-lt text-azure">All Content</span>';
    }

    protected function renderClassification($row): string
    {
        $name = trim((string) ($row->classification_name ?? ''));
        return '<span class="badge bg-light text-dark border">' . e($name ?: 'Unclassified') . '</span>';
    }

    protected function renderCourseDuration($row): string
    {
        $sec = (int) ($row->duration_seconds ?? 0);
        if ($sec <= 0 && !empty($row->content_duration)) {
            $sec = DurationFormatter::toSeconds($row->content_duration) ?? 0;
        }
        return '<span class="font-monospace text-secondary">' . ($sec > 0 ? DurationFormatter::fromSeconds($sec) : '-') . '</span>';
    }

    protected function renderRecognizedDuration($row): string
    {
        $durSec = (int) ($row->duration_seconds ?? 0);
        if ($durSec <= 0 && !empty($row->content_duration)) {
            $durSec = DurationFormatter::toSeconds($row->content_duration) ?? 0;
        }
        $actualSec = $this->resolveActualSeconds($row, $durSec);
        $maxCap = $durSec > 0 ? ($durSec * 2) : 0;
        $recSec = ($maxCap > 0) ? min($actualSec, $maxCap) : $actualSec;

        return '<span class="fw-semibold font-monospace text-primary">' . ($recSec > 0 ? DurationFormatter::fromSeconds($recSec) : '-') . '</span>';
    }

    protected function renderLearningHours($row): string
    {
        $hours = (float) ($row->learning_hours ?? 0);
        if ($hours <= 0) {
            return '<span class="text-muted">0 h</span>';
        }
        $fmt = ($hours == (int) $hours) ? (int) $hours . ' h' : rtrim(rtrim(number_format($hours, 2, '.', ''), '0'), '.') . ' h';
        return '<span class="fw-semibold text-dark">' . $fmt . '</span>';
    }

    protected function renderProgressBar($row): string
    {
        $pct = (int) round((float) ($row->progress ?? 0));
        $color = $pct >= 100 ? 'bg-success' : ($pct > 0 ? 'bg-warning' : 'bg-secondary');
        return '<div class="d-flex align-items-center" style="min-width: 90px;"><div class="progress flex-grow-1 me-2" style="height: 6px;"><div class="progress-bar ' . $color . '" style="width: ' . $pct . '%;"></div></div><span class="small fw-semibold">' . $pct . '%</span></div>';
    }

    protected function renderProgressStatusBadge($row): string
    {
        $pct = (float) ($row->progress ?? 0);
        $isFinished = ($row->finish === '1' || $pct >= 100);
        if ($isFinished) {
            return '<span class="badge bg-success-lt text-success fw-semibold"><i class="ti ti-check me-1"></i>Finished</span>';
        }
        if ($pct > 0) {
            return '<span class="badge bg-warning-lt text-warning fw-semibold"><i class="ti ti-clock me-1"></i>Ongoing</span>';
        }
        return '<span class="badge bg-secondary-lt text-secondary">Not Started</span>';
    }

    protected function renderLearningPeriod($row): string
    {
        $start = $row->created_at ? Carbon::parse($row->created_at) : null;
        $finish = ($row->finish === '1' || (float) ($row->progress ?? 0) >= 100) && $row->completed_at ? Carbon::parse($row->completed_at) : null;

        $startDate = $start ? $start->format('d M Y') : '-';
        $startTime = $start ? $start->format('H:i') : '';
        $endDate = $finish ? $finish->format('d M Y') : '-';
        $endTime = $finish ? $finish->format('H:i') : '';

        $datePart = ($startDate !== '-' && $endDate !== '-') ? "{$startDate} – {$endDate}" : $startDate;
        $timePart = ($startTime && $endTime) ? "{$startTime} – {$endTime}" : ($startTime ?: '');

        return '<div class="d-flex flex-column small"><span class="fw-medium text-dark">' . e($datePart) . '</span>' . ($timePart ? '<span class="text-muted" style="font-size: 11px;">' . e($timePart) . '</span>' : '') . '</div>';
    }

    protected function renderRecordMonth($row): string
    {
        $finish = ($row->finish === '1' || (float) ($row->progress ?? 0) >= 100) && $row->completed_at ? Carbon::parse($row->completed_at) : null;
        if ($finish) {
            return '<span class="badge bg-blue-lt text-blue">' . $finish->format('F') . '</span>';
        }
        return '<span class="text-muted small">-</span>';
    }

    protected function renderRequirementBadge($row): string
    {
        $status = strtolower(trim((string) ($row->requirement_status ?? 'non mandatory')));
        if ($status === 'mandatory') {
            return '<span class="badge bg-red-lt text-red fw-bold">Mandatory</span>';
        }
        return '<span class="badge bg-blue-lt text-blue">Non-Mandatory</span>';
    }

    protected function renderAttendanceBadge($row): string
    {
        $pct = (float) ($row->progress ?? 0);
        $finish = ($row->finish === '1');
        if ($pct > 0 || $finish) {
            return '<span class="badge bg-success-lt text-success fw-bold px-2 py-1">Yes</span>';
        }
        return '<span class="badge bg-danger-lt text-danger fw-bold px-2 py-1">No</span>';
    }

    protected function renderScoreL2($row): string
    {
        $cacheKey = "{$row->user_id}_{$row->source_type}_{$row->content_id}_{$row->trainingplan_id}";
        if (array_key_exists($cacheKey, $this->l2ScoreCache)) {
            $score = $this->l2ScoreCache[$cacheKey];
        } else {
            $q = DB::table('assesment_results as ar')
                ->join('assesments as a', 'a.id', '=', 'ar.assesment_id')
                ->where('ar.user_id', $row->user_id)
                ->where('ar.final', 1);

            if ($row->source_type === 'playlist') {
                $q->where(function ($sub) use ($row) {
                    if ($row->trainingplan_id) $sub->where('a.trainingplan_id', $row->trainingplan_id);
                    if ($row->content_id) $sub->orWhere('a.content_id', $row->content_id);
                });
            } elseif ($row->content_id) {
                $q->where('a.content_id', $row->content_id);
            } else {
                $q->whereRaw('1 = 0');
            }

            $score = $q->orderByDesc('ar.score')->value('ar.score');
            $this->l2ScoreCache[$cacheKey] = ($score !== null && is_numeric($score)) ? (float) $score : null;
        }

        if ($score !== null) {
            $badgeClass = $score >= 70 ? 'text-success' : 'text-danger';
            return '<span class="fw-bold ' . $badgeClass . '">' . number_format($score, 1) . '</span>';
        }
        return '<span class="text-muted small">-</span>';
    }

    protected function renderCertificateBadge($row): string
    {
        $isFinished = ($row->finish === '1' || (float) ($row->progress ?? 0) >= 100);
        if ($isFinished) {
            return '<span class="badge bg-teal-lt text-teal px-2 py-1"><i class="ti ti-certificate me-1"></i>Available</span>';
        }
        return '<span class="text-muted small">-</span>';
    }

    protected function renderEngagementTime($row): string
    {
        $durSec = (int) ($row->duration_seconds ?? 0);
        $actualSec = $this->resolveActualSeconds($row, $durSec);
        if ($actualSec > 0) {
            return '<span class="fw-semibold font-monospace">' . DurationFormatter::fromSeconds($actualSec) . '</span>';
        }
        return '<span class="text-muted small">-</span>';
    }

    protected function renderActionColumn($row): string
    {
        $sourceType = e($row->source_type);
        $sourceId = (int) $row->source_id;

        return '<div class="btn-group">'
            . '<button type="button" class="btn btn-sm btn-outline-primary px-2.5 py-1 btn-kp-owl-detail" '
            . 'data-source-type="' . $sourceType . '" data-id="' . $sourceId . '" title="View Details">'
            . '<i class="ti ti-eye me-1"></i>Details'
            . '</button>'
            . '</div>';
    }

    protected function resolveActualSeconds(object $row, int $durSec): int
    {
        $watch = (int) ($row->actual_watch_seconds ?? 0);
        $curr = (int) ($row->current_time ?? 0);
        $pct = (float) ($row->progress ?? 0);
        $lh = (float) ($row->learning_hours ?? 0);
        $finished = ($row->finish === '1' || $pct >= 100);

        if ($watch > 0) return $watch;
        if ($curr > 0) return $curr;
        if ($finished && $durSec > 0) return $durSec;
        if ($pct > 0 && $durSec > 0) return (int) round(($pct / 100) * $durSec);
        if ($lh > 0) return (int) round($lh * 3600);

        return 0;
    }
}
