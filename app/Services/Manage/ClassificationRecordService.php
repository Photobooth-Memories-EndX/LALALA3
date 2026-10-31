<?php

namespace App\Services\Manage;

use App\Exports\Manage\ClassificationRecordExport;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Yajra\DataTables\Facades\DataTables;

class ClassificationRecordService
{
    protected RecordSharedQueryService $queryService;
    protected RecordDetailService $detailService;

    public function __construct(
        RecordSharedQueryService $queryService,
        RecordDetailService $detailService
    ) {
        $this->queryService = $queryService;
        $this->detailService = $detailService;
    }

    public function resolveClassification(string $classification, bool $abortIfNotFound = true): array
    {
        return $this->queryService->resolveClassification($classification, $abortIfNotFound);
    }

    public function getFilterOptions(): array
    {
        return $this->queryService->getFilterOptions();
    }

    public function getRecordDetails(string $source, $id): JsonResponse
    {
        $detail = $this->detailService->getDetail($source, $id);

        return response()->json(array_merge([
            'success'     => $detail['success'] ?? true,
            'data'        => $detail,
            'source_type' => $detail['source'] ?? 'KP OWL',
        ], $detail));
    }

    public function export(string $classification, Request $request): BinaryFileResponse
    {
        $export = new ClassificationRecordExport($classification, $request);

        return $export->download();
    }

    /**
     * Server-side DataTable processing locked to the given classification.
     */
    public function datatable(string $classification, Request $request): JsonResponse
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

        $resolved = $this->resolveClassification($classification, false);
        $canonicalName = $resolved['is_unclassified'] ? 'Unclassified' : $resolved['name'];

        $filters = $request->all();
        $filters['classification'] = $canonicalName;

        $query = $this->queryService->buildClassificationQuery($classification, $filters);

        // Default sorting
        if (!$request->filled('order')) {
            $query->orderBy('raw_finish_date', 'desc')
                ->orderBy('record_date', 'desc');
        }

        $baseCountQuery = $this->queryService->buildClassificationQuery($classification, ['classification' => $canonicalName]);
        $totalClassificationRecords = $baseCountQuery->count();

        $dataTable = DataTables::of($query)->setTotalRecords($totalClassificationRecords);

        $hasFilters = $this->hasActiveFilters($filters);
        if ($hasFilters) {
            $dataTable->setFilteredRecords($query->count());
        } else {
            $dataTable->setFilteredRecords($totalClassificationRecords);
        }

        $classificationSlug = $resolved['slug'];

        return $dataTable
            ->addIndexColumn()
            ->setRowId('row_id')
            ->addColumn('source_badge', function ($row) {
                if ($row->source === 'online') {
                    return '<span class="badge bg-blue-100 text-blue-700 rounded-pill px-2.5 py-1" style="background-color: #dbeafe !important; color: #1d4ed8 !important; font-weight: 600; font-size: 0.75rem;">KP OWL</span>';
                }
                return '<span class="badge bg-amber-100 text-amber-700 rounded-pill px-2.5 py-1" style="background-color: #fef3c7 !important; color: #b45309 !important; font-weight: 600; font-size: 0.75rem;">Non KP OWL</span>';
            })
            ->addColumn('employee_id', function ($row) {
                $rawEmp = $row->employee_id ?: '-';
                $clean = ($rawEmp !== '-') ? preg_replace('/\s*[\/\-]\s*NIP.*$/i', '', trim((string) $rawEmp)) : '-';
                return htmlspecialchars($clean);
            })
            ->addColumn('employee_name', function ($row) use ($classificationSlug) {
                $name = $row->employee_name ?: '-';
                if ($name === '-' || $name === '') {
                    return '<span class="text-muted">-</span>';
                }
                $escaped = htmlspecialchars($name, ENT_QUOTES, 'UTF-8');
                $identifier = !empty($row->employee_id) ? $row->employee_id : (!empty($row->user_id) ? $row->user_id : null);
                if ($identifier) {
                    $url = route('manage.record.all.participant', [
                        'identifier'     => $identifier,
                        'classification' => $classificationSlug,
                    ]);
                    return '<div class="fw-semibold employee-name-cell"><a href="' . htmlspecialchars($url, ENT_QUOTES, 'UTF-8') . '" class="text-primary text-decoration-none hover-underline participant-link" title="View participant details">' . $escaped . '</a></div>';
                }
                return '<div class="fw-semibold text-dark employee-name-cell" title="' . $escaped . '">' . $escaped . '</div>';
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
            ->addColumn('course_title', function ($row) use ($classificationSlug) {
                $title = $row->learning_title ?: '-';
                if ($title === '-' || $title === '') {
                    return '<span class="text-muted">-</span>';
                }
                $escaped = htmlspecialchars($title, ENT_QUOTES, 'UTF-8');
                $rawReq = $row->requirement ?? ($row->status ?? 'Non-Mandatory');
                $req = !empty($rawReq) ? htmlspecialchars($rawReq) : 'Non-Mandatory';

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
                    $pSlug = !empty($row->playlist_slug) ? $row->playlist_slug : ($row->learning_slug ?? null);
                    if (!empty($pSlug)) {
                        $url = url('/playlist/' . $pSlug);
                    }
                }

                $titleHtml = $url
                    ? '<a href="' . htmlspecialchars($url, ENT_QUOTES, 'UTF-8') . '" class="course-link fw-bold text-dark text-center fs-6 mb-1 text-decoration-none hover-underline" title="View details">' . $escaped . '</a>'
                    : '<span class="fw-bold text-dark text-center fs-6 mb-1">' . $escaped . '</span>';

                return '<div class="d-flex flex-column align-items-center justify-content-center text-center w-100">'
                    . $titleHtml
                    . '<small class="text-muted text-center fs-7">Requirement: <span class="fw-semibold">' . $req . '</span></small>'
                    . '</div>';
            })
            ->addColumn('learning_title', function ($row) use ($classificationSlug) {
                $title = $row->learning_title ?: '-';
                if ($title === '-' || $title === '') {
                    return '<span class="text-muted">-</span>';
                }
                $escaped = htmlspecialchars($title, ENT_QUOTES, 'UTF-8');
                $rawReq = $row->requirement ?? ($row->status ?? 'Non-Mandatory');
                $req = !empty($rawReq) ? htmlspecialchars($rawReq) : 'Non-Mandatory';

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
                    $pSlug = !empty($row->playlist_slug) ? $row->playlist_slug : ($row->learning_slug ?? null);
                    if (!empty($pSlug)) {
                        $url = url('/playlist/' . $pSlug);
                    }
                }

                $titleHtml = $url
                    ? '<a href="' . htmlspecialchars($url, ENT_QUOTES, 'UTF-8') . '" class="course-link fw-bold text-dark text-center fs-6 mb-1 text-decoration-none hover-underline" title="View details">' . $escaped . '</a>'
                    : '<span class="fw-bold text-dark text-center fs-6 mb-1">' . $escaped . '</span>';

                return '<div class="d-flex flex-column align-items-center justify-content-center text-center w-100">'
                    . $titleHtml
                    . '<small class="text-muted text-center fs-7">Requirement: <span class="fw-semibold">' . $req . '</span></small>'
                    . '</div>';
            })
            ->addColumn('type', function ($row) {
                return htmlspecialchars($row->type ?: '-');
            })
            ->addColumn('category', function ($row) {
                return htmlspecialchars($row->category ?: '-');
            })
            ->addColumn('main_classification', function ($row) {
                return htmlspecialchars($row->classification ?: 'Unclassified');
            })
            ->addColumn('sub_classification', function ($row) {
                return htmlspecialchars($row->sub_classification ?: '-');
            })
            ->addColumn('delivery_method', function ($row) {
                return $row->source === 'online' ? 'Online' : htmlspecialchars($row->delivery_method ?: 'Offline');
            })
            ->addColumn('sub_delivery_method', function ($row) {
                if ($row->source === 'online') {
                    return ($row->source_type ?? '') === 'playlist' ? 'Playlist / Path' : 'Self-Paced / LMS';
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
            ->addColumn('course_duration', function ($row) {
                $sec = (int) ($row->duration_seconds ?? 0);
                if ($sec <= 0) return '-';
                $h = floor($sec / 3600);
                $m = floor(($sec % 3600) / 60);
                $s = $sec % 60;
                return sprintf('%02d:%02d:%02d', $h, $m, $s);
            })
            ->addColumn('total_recognized_duration', function ($row) {
                if (($row->source ?? '') === 'offline') return '-';
                $sec = (int) ($row->recognized_duration_seconds ?? 0);
                if ($sec <= 0) {
                    $sec = app(\App\Services\Learning\LearningRecordRecognizedDurationService::class)->resolveFromRow($row);
                }
                if ($sec <= 0) return '-';
                $h = floor($sec / 3600);
                $m = floor(($sec % 3600) / 60);
                $s = $sec % 60;
                return sprintf('%02d:%02d:%02d', $h, $m, $s);
            })
            ->addColumn('learning_hours', function ($row) {
                $hours = (float) ($row->training_hours ?? ($row->learning_hours ?? 0));
                return '<span class="fw-semibold">' . number_format($hours, 1) . 'h</span>';
            })
            ->addColumn('progress_bar', function ($row) {
                $progress = max(0, min(100, round((float) ($row->progress ?? 0))));
                $color = $progress >= 100 ? 'bg-success' : ($progress > 0 ? 'bg-warning' : 'bg-secondary');
                return '<div class="d-flex align-items-center gap-2" style="min-width: 85px;">
                    <div class="progress progress-xs flex-grow-1" style="width: 50px; height: 6px;">
                        <div class="progress-bar ' . $color . '" style="width: ' . $progress . '%"></div>
                    </div>
                    <span class="small fw-semibold text-muted">' . $progress . '%</span>
                </div>';
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
                if ($progress >= 100 || ($row->status ?? '') === 'Finished') {
                    return '<span class="badge bg-success-lt text-success fw-bold px-2 py-1">Finished</span>';
                } elseif ($progress > 0) {
                    return '<span class="badge bg-warning-lt text-warning fw-bold px-2 py-1">Ongoing</span>';
                }
                return '<span class="badge bg-secondary-lt fw-bold px-2 py-1">Not Started</span>';
            })
            ->addColumn('start_date', function ($row) {
                $d = $row->start_date ?? null;
                return ($d && $d !== '-') ? date('d M Y', strtotime($d)) : '-';
            })
            ->addColumn('end_date', function ($row) {
                $d = $row->end_date ?? ($row->finish_date ?? null);
                return ($d && $d !== '-') ? date('d M Y', strtotime($d)) : '-';
            })
            ->addColumn('start_time', function ($row) {
                $t = $row->start_time ?? null;
                return ($t && $t !== '-') ? date('H:i:s', strtotime($t)) : '-';
            })
            ->addColumn('end_time', function ($row) {
                $t = $row->end_time ?? null;
                return ($t && $t !== '-') ? date('H:i:s', strtotime($t)) : '-';
            })
            ->addColumn('record_month', function ($row) {
                $d = ($row->source === 'online')
                    ? ($row->finish_date ?? ($row->start_date ?? null))
                    : ($row->start_date ?: ($row->finish_date ?: ($row->record_date ?? null)));
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
                            <a class="dropdown-item d-flex align-items-center gap-2 btn-view-detail" href="#" data-id="' . e($id) . '" data-source="' . e($source) . '">
                                <i class="ti ti-eye text-primary"></i> Details
                            </a>
                        </li>
                        <li>
                            <a class="dropdown-item d-flex align-items-center gap-2" href="' . e($editUrl) . '">
                                <i class="ti ti-edit text-warning"></i> Edit Record
                            </a>
                        </li>
                    </ul>
                </div>';
            })
            ->rawColumns([
                'source_badge',
                'employee_name',
                'course_title',
                'learning_title',
                'provider',
                'learning_hours',
                'progress_bar',
                'progress_percent',
                'progress_status',
                'requirement',
                'attendance',
                'score_l1',
                'score_l2',
                'score_l3',
                'certificate_availability',
                'action',
            ])
            ->make(true);
    }

    protected function hasActiveFilters(array $filters): bool
    {
        $keys = ['employee_id', 'employee', 'learning_title', 'title', 'source', 'status', 'year', 'from_date', 'to_date', 'department', 'company'];
        foreach ($keys as $k) {
            if (!empty($filters[$k]) && strtolower((string)$filters[$k]) !== 'all') {
                return true;
            }
        }
        return false;
    }
}
