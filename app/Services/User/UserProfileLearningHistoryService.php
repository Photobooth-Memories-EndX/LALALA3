<?php

namespace App\Services\User;

use App\Http\Controllers\Manage\Record\RecordAllController;
use App\Models\Classification;
use App\Models\Content;
use App\Models\ContentUser;
use App\Models\User;
use App\Services\Record\RecordAllCombinedService;
use App\Support\DurationFormatter;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder as EloquentBuilder;
use Illuminate\Database\Query\Builder as QueryBuilder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Yajra\DataTables\Facades\DataTables;

class UserProfileLearningHistoryService
{
    protected static array $l2ScoreCache = [];

    /**
     * Build unified combined database query across KP OWL (Content, Playlist)
     * and Non KP OWL (Offline Training) for a specific user.
     */
    public static function buildCombinedQuery(User $user, array $filters = []): QueryBuilder
    {
        $cleanIdEmp = preg_replace('/\s*[\/\-]\s*NIP.*$/i', '', trim((string) $user->id_emp));

        $userFilters = array_merge($filters, [
            'exact_participant' => [
                'user_id'     => $user->id,
                'employee_id' => $cleanIdEmp,
                'name'        => $user->name,
            ],
        ]);

        return app(RecordAllCombinedService::class)->buildCombinedQuery($userFilters);
    }

    /**
     * Server-side DataTables AJAX endpoint returning all 34 UI Table columns
     * conforming to the 52-field KP OWL specification + legacy compatibility columns.
     */
    public static function datatable(User $user, Request $request): JsonResponse
    {
        $entries = (int) ($request->input('entries') ?: $request->input('length', 25));
        if ($entries <= 0) {
            $entries = 25;
        }
        $request->merge(['length' => $entries]);
        if (!$request->has('start')) {
            $request->merge(['start' => 0]);
        }
        app()->instance('request', $request);
        app()->forgetInstance('datatables.request');

        $cleanIdEmp = preg_replace('/\s*[\/\-]\s*NIP.*$/i', '', trim((string) $user->id_emp));
        $filters = $request->all();

        $query = self::buildCombinedQuery($user, $filters);

        // Default ordering
        if (!$request->filled('order')) {
            $query->orderBy('raw_finish_date', 'desc')
                ->orderBy('record_date', 'desc');
        }

        // Calculate total records for this specific user
        $userTotalQuery = self::buildCombinedQuery($user, []);
        $userTotal = (int) (clone $userTotalQuery)->count();

        $hasFilters = filled($request->input('status')) && $request->input('status') !== 'all'
            || filled($request->input('type')) && $request->input('type') !== 'all'
            || filled($request->input('classification')) && $request->input('classification') !== 'all'
            || filled($request->input('year')) && $request->input('year') !== 'all'
            || filled($request->input('from_date'))
            || filled($request->input('to_date'))
            || filled($request->input('progress_range')) && $request->input('progress_range') !== 'all'
            || filled($request->input('duration')) && $request->input('duration') !== 'all'
            || filled($request->input('learning_title'))
            || filled($request->input('title'))
            || filled($request->input('source')) && $request->input('source') !== 'all'
            || filled(trim((string) $request->input('search.value')));

        $dataTable = DataTables::of($query)
            ->setTotalRecords($userTotal);

        if ($hasFilters) {
            $dataTable->setFilteredRecords((clone $query)->count());
        } else {
            $dataTable->setFilteredRecords($userTotal);
        }

        return $dataTable
            ->addIndexColumn()
            ->setRowId(fn ($row) => $row->row_id ?? ($row->source . ':' . $row->source_id))

            // 1. NO (handled by addIndexColumn)

            // 2. SOURCE
            ->addColumn('source_badge', function ($row) {
                if ($row->source === 'online') {
                    return '<span class="badge bg-blue-lt text-blue fw-semibold">KP OWL</span>';
                }
                return '<span class="badge bg-amber-lt text-amber fw-semibold">Non KP OWL</span>';
            })
            ->addColumn('source', function ($row) {
                return $row->source === 'online' ? 'KP OWL' : 'Non KP OWL';
            })

            // 3. EMPLOYEE ID
            ->addColumn('employee_id', function ($row) use ($cleanIdEmp) {
                $raw = $row->employee_id ?: $cleanIdEmp;
                $clean = ($raw !== '-') ? preg_replace('/\s*[\/\-]\s*NIP.*$/i', '', trim((string) $raw)) : '-';
                return '<span class="font-monospace fw-bold text-dark">' . htmlspecialchars($clean) . '</span>';
            })

            // 4. EMPLOYEE NAME
            ->addColumn('employee_name', function ($row) use ($user) {
                $name = $row->employee_name ?: $user->name ?: '-';
                $escaped = htmlspecialchars($name, ENT_QUOTES, 'UTF-8');
                return '<div class="fw-semibold text-dark text-nowrap" title="' . $escaped . '">' . $escaped . '</div>';
            })

            // 5. TEAM
            ->addColumn('team', function ($row) {
                return htmlspecialchars($row->team ?: '-');
            })

            // 6. DEPARTMENT
            ->addColumn('department', function ($row) {
                return htmlspecialchars($row->department ?: '-');
            })

            // 7. COMPANY
            ->addColumn('company', function ($row) {
                return htmlspecialchars($row->company ?: 'PT Krakatau Posco');
            })

            // 8. COURSE TITLE
            ->addColumn('course_title', function ($row) {
                $title = htmlspecialchars($row->learning_title ?: '-');
                $isPlaylist = ($row->source_type ?? '') === 'playlist';
                $isOffline = ($row->source ?? '') === 'offline';

                if ($isPlaylist) {
                    $icon = '<i class="ti ti-route me-1 text-purple"></i>';
                } elseif ($isOffline) {
                    $icon = '<i class="ti ti-chalkboard me-1 text-info"></i>';
                } else {
                    $icon = '<i class="ti ti-file-text me-1 text-primary"></i>';
                }

                $contentSlug = $row->learning_slug ?? ($row->content_slug ?? null);
                if ($contentSlug && !$isOffline) {
                    $url = route('learning.manage.content', $contentSlug);
                    return '<div class="d-flex align-items-center">' . $icon . '<a href="' . e($url) . '" class="text-reset fw-semibold text-truncate" style="max-width: 280px;" title="' . $title . '">' . $title . '</a></div>';
                }

                return '<div class="d-flex align-items-center">' . $icon . '<span class="fw-semibold text-dark text-truncate" style="max-width: 280px;" title="' . $title . '">' . $title . '</span></div>';
            })

            // 9. TYPE
            ->addColumn('type', function ($row) {
                if (($row->source_type ?? '') === 'playlist') {
                    return '<span class="badge" style="background-color: #8b5cf6; color: #ffffff; font-size: 11px; font-weight: 600; padding: 3px 8px; border-radius: 6px;">Playlist</span>';
                }
                $rawType = strtolower(trim((string) ($row->type ?? 'document')));
                if (in_array($rawType, ['video', 'youtube', 'mooc'], true)) {
                    return '<span class="badge" style="background-color: #f59e0b; color: #ffffff; font-size: 11px; font-weight: 600; padding: 3px 8px; border-radius: 6px;">Video</span>';
                }
                if ($rawType === 'document' || $rawType === 'pdf') {
                    return '<span class="badge" style="background-color: #0ea5e9; color: #ffffff; font-size: 11px; font-weight: 600; padding: 3px 8px; border-radius: 6px;">Document</span>';
                }
                return '<span class="badge bg-light text-dark border">' . htmlspecialchars(ucfirst($row->type ?: '-')) . '</span>';
            })

            // 10. CATEGORY
            ->addColumn('category', function ($row) {
                if (($row->source_type ?? '') === 'playlist') {
                    return '<span class="badge bg-purple-lt text-purple">Playlist</span>';
                }
                if (($row->source ?? '') === 'online') {
                    return '<span class="badge bg-azure-lt text-azure">All Content</span>';
                }
                return '<span class="badge bg-light text-dark border">' . htmlspecialchars($row->category ?: 'Non KP-OWL') . '</span>';
            })

            // 11. MAIN CLASSIFICATION
            ->addColumn('main_classification', function ($row) {
                $class = $row->classification ?: 'Unclassified';
                return '<span class="badge bg-light text-dark border">' . htmlspecialchars($class) . '</span>';
            })

            // 12. SUB CLASSIFICATION
            ->addColumn('sub_classification', function ($row) {
                $sub = $row->sub_classification ?: null;
                return $sub ? htmlspecialchars($sub) : '<span class="text-muted small">-</span>';
            })

            // 13. DELIVERY METHOD
            ->addColumn('delivery_method', function ($row) {
                if ($row->source === 'online') {
                    return '<span class="badge bg-blue-lt text-blue">Online</span>';
                }
                $deliv = ucfirst(strtolower((string) ($row->delivery_method ?: 'Offline')));
                return '<span class="badge bg-secondary-lt text-secondary">' . htmlspecialchars($deliv) . '</span>';
            })

            // 14. SUB DELIVERY METHOD
            ->addColumn('sub_delivery_method', function ($row) {
                if ($row->source === 'online') {
                    return ($row->source_type ?? '') === 'playlist' ? 'Playlist / Path' : 'Self-Paced / LMS';
                }
                return htmlspecialchars($row->sub_delivery_method ?: '-');
            })

            // 15. PROVIDER
            ->addColumn('provider', function ($row) {
                if ($row->source === 'online') {
                    return '<span class="badge bg-blue-lt text-blue">Internal</span>';
                }
                return htmlspecialchars($row->provider ?: '-');
            })

            // 16. PROVIDER NAME
            ->addColumn('provider_name', function ($row) {
                if ($row->source === 'online') {
                    return 'KP OWL';
                }
                return htmlspecialchars($row->provider_name ?: '-');
            })

            // 17. VENUE
            ->addColumn('venue', function ($row) {
                if ($row->source === 'online') {
                    return 'Online LMS';
                }
                return htmlspecialchars($row->venue ?: '-');
            })

            // 18. COURSE DURATION
            ->addColumn('course_duration', function ($row) {
                $sec = (int) ($row->duration_seconds ?? 0);
                return '<span class="font-monospace text-secondary">' . ($sec > 0 ? DurationFormatter::fromSeconds($sec) : '-') . '</span>';
            })

            // 19. TOTAL RECOGNIZED DURATION
            ->addColumn('total_recognized_duration', function ($row) {
                if (($row->source ?? '') === 'offline') {
                    return '<span class="text-muted small">-</span>';
                }
                $sec = (int) ($row->recognized_duration_seconds ?? 0);
                return '<span class="fw-semibold font-monospace text-primary">' . ($sec > 0 ? DurationFormatter::fromSeconds($sec) : '-') . '</span>';
            })

            // 20. LEARNING HOURS
            ->addColumn('learning_hours', function ($row) {
                $hours = (float) ($row->learning_hours ?? 0);
                if ($hours <= 0) {
                    return '<span class="text-muted">0 h</span>';
                }
                $fmt = ($hours == (int) $hours) ? (int) $hours . ' h' : rtrim(rtrim(number_format($hours, 2, '.', ''), '0'), '.') . ' h';
                return '<span class="fw-semibold text-dark">' . $fmt . '</span>';
            })

            // 21. % PROGRESS
            ->addColumn('progress_bar', function ($row) {
                $pct = max(0, min(100, (int) round((float) ($row->progress ?? 0))));
                $color = $pct >= 100 ? 'bg-success' : ($pct > 0 ? 'bg-warning' : 'bg-secondary');
                return '<div class="d-flex align-items-center gap-2" style="min-width: 85px;">
                    <div class="progress progress-xs flex-grow-1" style="width: 50px; height: 6px;">
                        <div class="progress-bar ' . $color . '" style="width: ' . $pct . '%"></div>
                    </div>
                    <span class="small fw-semibold text-muted">' . $pct . '%</span>
                </div>';
            })

            // 22. PROGRESS STATUS
            ->addColumn('progress_status', function ($row) {
                $pct = (float) ($row->progress ?? 0);
                $isFinished = (($row->status ?? '') === 'Finished' || $pct >= 100);
                if ($isFinished) {
                    return '<span class="badge bg-success-lt text-success fw-semibold"><i class="ti ti-check me-1"></i>Finished</span>';
                }
                if ($pct > 0) {
                    return '<span class="badge bg-warning-lt text-warning fw-semibold"><i class="ti ti-clock me-1"></i>Ongoing</span>';
                }
                return '<span class="badge bg-secondary-lt text-secondary">Not Started</span>';
            })

            // 23. DATE (START - END) [Merged]
            ->addColumn('date_merged', function ($row) {
                return self::formatMergedDate($row->start_date, $row->end_date ?? $row->finish_date);
            })

            // 24. TIME (START - END) [Merged]
            ->addColumn('time_merged', function ($row) {
                return self::formatMergedTime($row->start_time, $row->end_time);
            })

            // 25. RECORD MONTH
            ->addColumn('record_month', function ($row) {
                $d = !empty($row->finish_date) ? $row->finish_date : (!empty($row->end_date) ? $row->end_date : null);
                if ($d && $d !== '-') {
                    try {
                        return '<span class="badge bg-blue-lt text-blue">' . date('F', strtotime($d)) . '</span>';
                    } catch (\Throwable $e) {}
                }
                return '<span class="text-muted small">-</span>';
            })

            // 26. REQUIREMENT
            ->addColumn('requirement', function ($row) {
                $req = strtolower(trim((string) ($row->requirement ?? 'non mandatory')));
                if ($req === 'mandatory') {
                    return '<span class="badge bg-red-lt text-red fw-bold">Mandatory</span>';
                }
                return '<span class="badge bg-blue-lt text-blue">Non-Mandatory</span>';
            })

            // 27. ATTENDANCE
            ->addColumn('attendance', function ($row) {
                $att = strtolower(trim((string) ($row->attendance ?? '')));
                $pct = (float) ($row->progress ?? 0);
                $isFinished = (($row->status ?? '') === 'Finished' || $pct >= 100);
                $isYes = in_array($att, ['1', 'yes', 'present', 'true', 'hadir', 'completed', 'finished'], true) || ($pct > 0 || $isFinished);

                if ($isYes) {
                    return '<span class="badge bg-success-lt text-success fw-bold px-2 py-1">Yes</span>';
                }
                return '<span class="badge bg-danger-lt text-danger fw-bold px-2 py-1">No</span>';
            })

            // 28. SCORE (L1) - COURSE
            ->addColumn('score_l1', function ($row) {
                $l1 = $row->l1_program ?? ($row->assessment_score ?? null);
                if (!empty($l1) && $l1 !== '-') {
                    return '<span class="fw-semibold">' . htmlspecialchars((string) $l1) . '</span>';
                }
                return '<span class="text-muted small">-</span>';
            })

            // 29. SCORE (L2)
            ->addColumn('score_l2', function ($row) {
                if (($row->source ?? '') === 'offline') {
                    if (isset($row->l2_numerical) && is_numeric($row->l2_numerical)) {
                        $s = (float) $row->l2_numerical;
                        $color = $s >= 70 ? 'text-success' : 'text-danger';
                        return '<span class="fw-bold ' . $color . '">' . number_format($s, 1) . '</span>';
                    }
                    return '<span class="text-muted small">-</span>';
                }

                $score = self::resolveL2Score(
                    (int) ($row->user_id ?? 0),
                    (string) ($row->source_type ?? 'content'),
                    !empty($row->content_id) ? (int) $row->content_id : null,
                    !empty($row->trainingplan_id) ? (int) $row->trainingplan_id : null
                );

                if ($score !== null) {
                    $color = $score >= 70 ? 'text-success' : 'text-danger';
                    return '<span class="fw-bold ' . $color . '">' . number_format($score, 1) . '</span>';
                }
                return '<span class="text-muted small">-</span>';
            })

            // 30. SCORE (L3)
            ->addColumn('score_l3', function ($row) {
                if (isset($row->l3_numerical) && is_numeric($row->l3_numerical)) {
                    return '<span class="fw-semibold">' . number_format((float) $row->l3_numerical, 1) . '</span>';
                }
                return '<span class="text-muted small">-</span>';
            })

            // 31. CERTIFICATE AVAILABILITY
            ->addColumn('certificate_availability', function ($row) {
                $isFinished = (($row->status ?? '') === 'Finished' || (float) ($row->progress ?? 0) >= 100);
                $hasCert = (!empty($row->certificate) && $row->certificate !== '-') || (($row->source ?? '') === 'online' && $isFinished);

                if ($hasCert) {
                    return '<span class="badge bg-teal-lt text-teal px-2 py-1"><i class="ti ti-certificate me-1"></i>Available</span>';
                }
                return '<span class="text-muted small">-</span>';
            })

            // 32. PERSON IN CHARGE (PIC)
            ->addColumn('pic', function ($row) {
                return !empty($row->pic) && $row->pic !== '-' ? htmlspecialchars($row->pic) : '<span class="text-muted small">-</span>';
            })

            // 33. TOTAL ENGAGEMENT TIME
            ->addColumn('total_engagement_time', function ($row) {
                $afkSec = (int) ($row->afk_duration_seconds ?? 0);
                if ($afkSec <= 0 && ($row->source ?? '') === 'offline') {
                    $afkSec = (int) round(((float) ($row->learning_hours ?? 0)) * 3600);
                }
                if ($afkSec <= 0 && ($row->source ?? '') === 'online') {
                    $durSec = (int) ($row->duration_seconds ?? 0);
                    $watch = (int) ($row->actual_watch_seconds ?? 0);
                    $curr = (int) ($row->current_time ?? 0);
                    $pct = (float) ($row->progress ?? 0);
                    if ($watch > 0) $afkSec = $watch;
                    elseif ($curr > 0) $afkSec = $curr;
                    elseif ($pct >= 100 && $durSec > 0) $afkSec = $durSec;
                    elseif ($pct > 0 && $durSec > 0) $afkSec = (int) round(($pct / 100) * $durSec);
                }
                return '<span class="font-monospace fw-semibold">' . ($afkSec > 0 ? DurationFormatter::fromSeconds($afkSec) : '-') . '</span>';
            })

            // 34. ACTION (Tabler 3 vertical dots icon `ti ti-dots-vertical`)
            ->addColumn('action', function ($row) {
                $source = $row->source ?? 'online';
                $sourceType = $row->source_type ?? 'content';
                $id = $row->source_id ?? '';
                $detailSource = ($sourceType === 'playlist') ? 'playlist' : (($source === 'offline') ? 'offline' : 'online');
                $slug = $row->learning_slug ?? ($row->content_slug ?? '');
                $contentUrl = ($slug && $source === 'online') ? route('learning.manage.content', $slug) : '';
                $editUrl = route('manage.record.edit', $id) . '?source=' . e($source);

                $viewContentHtml = '';
                if ($contentUrl) {
                    $viewContentHtml = '<li><a class="dropdown-item d-flex align-items-center gap-2" href="' . e($contentUrl) . '"><i class="ti ti-external-link text-info"></i> View Content</a></li>';
                }

                return '<div class="dropdown">
                    <button class="btn btn-sm btn-icon btn-ghost-secondary rounded-2" type="button" data-bs-toggle="dropdown" aria-expanded="false" title="Action Options">
                        <i class="ti ti-dots-vertical"></i>
                    </button>
                    <ul class="dropdown-menu dropdown-menu-end shadow-sm border rounded-3 py-1">
                        <li>
                            <a class="dropdown-item d-flex align-items-center gap-2 btn-view-learning-detail btn-more-info" href="javascript:void(0);" data-source="' . e($detailSource) . '" data-source-type="' . e($sourceType) . '" data-source-id="' . e($id) . '" data-id="' . e($id) . '">
                                <i class="ti ti-eye text-primary"></i> More Information
                            </a>
                        </li>
                        ' . $viewContentHtml . '
                        <li>
                            <a class="dropdown-item d-flex align-items-center gap-2" href="' . e($editUrl) . '">
                                <i class="ti ti-edit text-warning"></i> Edit Record
                            </a>
                        </li>
                    </ul>
                </div>';
            })

            // ── Backward Compatibility Column Aliases ─────────────────────
            ->addColumn('status_badge', function ($row) {
                $pct = (float) ($row->progress ?? 0);
                $isFinished = (($row->status ?? '') === 'Finished' || $pct >= 100);
                return $isFinished
                    ? '<span class="badge bg-green-lt text-green">Finished</span>'
                    : '<span class="badge bg-yellow-lt text-yellow">Ongoing</span>';
            })
            ->addColumn('user_name', function ($row) use ($user) {
                return htmlspecialchars($row->employee_name ?: $user->name ?: '-');
            })
            ->addColumn('title_html', function ($row) {
                $title = htmlspecialchars($row->learning_title ?: '-');
                $type = htmlspecialchars($row->type ?: '-');
                $slug = $row->learning_slug ?? ($row->content_slug ?? null);
                $url = ($slug && ($row->source ?? '') === 'online') ? route('learning.manage.content', $slug) : null;
                $link = $url ? '<a href="' . e($url) . '" class="text-reset fw-semibold">' . $title . '</a>' : '<span class="fw-semibold">' . $title . '</span>';
                return '<div class="d-flex align-items-center"><div>' . $link . '<div class="text-muted small">' . $type . '</div></div></div>';
            })
            ->addColumn('classification_html', function ($row) {
                return '<span class="badge bg-light text-dark border">' . htmlspecialchars($row->classification ?: 'Unclassified') . '</span>';
            })
            ->addColumn('duration_formatted', function ($row) {
                $sec = (int) ($row->duration_seconds ?? 0);
                return $sec > 0 ? DurationFormatter::fromSeconds($sec) : '-';
            })
            ->addColumn('date_formatted', function ($row) {
                return self::formatMergedDate($row->start_date, $row->end_date ?? $row->finish_date);
            })
            ->addColumn('progress_html', function ($row) {
                $pct = max(0, min(100, (int) round((float) ($row->progress ?? 0))));
                return '<div class="d-flex align-items-center gap-2">'
                    . '<div class="progress progress-sm flex-fill">'
                    . '<div class="progress-bar bg-primary" style="width: ' . $pct . '%"></div>'
                    . '</div>'
                    . '<span class="small text-muted">' . $pct . '%</span>'
                    . '</div>';
            })

            ->rawColumns([
                'source_badge',
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
                'progress_bar',
                'progress_status',
                'date_merged',
                'time_merged',
                'record_month',
                'requirement',
                'attendance',
                'score_l1',
                'score_l2',
                'score_l3',
                'certificate_availability',
                'pic',
                'total_engagement_time',
                'action',
                'status_badge',
                'title_html',
                'classification_html',
                'progress_html',
                'date_formatted',
            ])
            ->make(true);
    }

    /**
     * Format merged date range: Start Date – End Date.
     */
    public static function formatMergedDate(?string $startDate, ?string $endDate): string
    {
        $s = !empty($startDate) && $startDate !== '-' ? date('d M Y', strtotime($startDate)) : null;
        $e = !empty($endDate) && $endDate !== '-' ? date('d M Y', strtotime($endDate)) : null;

        if ($s && $e && $s !== $e) {
            return htmlspecialchars("{$s} – {$e}");
        }
        if ($s) {
            return htmlspecialchars($s);
        }
        if ($e) {
            return htmlspecialchars($e);
        }
        return '<span class="text-muted small">-</span>';
    }

    /**
     * Format merged time range: Start Time – End Time.
     */
    public static function formatMergedTime(?string $startTime, ?string $endTime): string
    {
        $s = !empty($startTime) && $startTime !== '-' ? date('H:i', strtotime($startTime)) : null;
        $e = !empty($endTime) && $endTime !== '-' ? date('H:i', strtotime($endTime)) : null;

        if ($s && $e && $s !== $e) {
            return htmlspecialchars("{$s} – {$e}");
        }
        if ($s) {
            return htmlspecialchars($s);
        }
        if ($e) {
            return htmlspecialchars($e);
        }
        return '<span class="text-muted small">-</span>';
    }

    /**
     * Resolve Score (L2) from assesments and assesment_results for online records.
     */
    public static function resolveL2Score(int $userId, string $sourceType, ?int $contentId, ?int $trainingplanId): ?float
    {
        $cacheKey = "{$userId}_{$sourceType}_{$contentId}_{$trainingplanId}";
        if (array_key_exists($cacheKey, self::$l2ScoreCache)) {
            return self::$l2ScoreCache[$cacheKey];
        }

        $query = DB::table('assesment_results as ar')
            ->join('assesments as a', 'a.id', '=', 'ar.assesment_id')
            ->where('ar.user_id', $userId)
            ->where('ar.final', 1);

        if ($sourceType === 'playlist') {
            $query->where(function ($q) use ($contentId, $trainingplanId) {
                $matched = false;
                if ($trainingplanId) {
                    $q->where('a.trainingplan_id', $trainingplanId);
                    $matched = true;
                }
                if ($contentId) {
                    if ($matched) {
                        $q->orWhere('a.content_id', $contentId);
                    } else {
                        $q->where('a.content_id', $contentId);
                        $matched = true;
                    }
                }
                if (!$matched) {
                    $q->whereRaw('1 = 0');
                }
            });
        } else {
            if ($contentId) {
                $query->where('a.content_id', $contentId);
            } else {
                self::$l2ScoreCache[$cacheKey] = null;
                return null;
            }
        }

        $score = $query->orderByDesc('ar.score')->orderByDesc('ar.id')->value('ar.score');
        self::$l2ScoreCache[$cacheKey] = ($score !== null && is_numeric($score)) ? (float) $score : null;

        return self::$l2ScoreCache[$cacheKey];
    }

    /**
     * Legacy Eloquent query builder for backwards compatibility.
     */
    public static function buildQuery(User $user, array $filters = []): EloquentBuilder
    {
        $query = ContentUser::query()
            ->with([
                'user:id,name,id_emp,ssohash,department,division,team',
                'content:id,title,slug,type,duration,classification_id',
                'content.classification:id,classification_name,slug',
            ])
            ->where('content_users.user_id', $user->id);

        $rawSearch = $filters['learning_title'] ?? ($filters['title'] ?? ($filters['title_q'] ?? null));
        if ((!filled($rawSearch)) && isset($filters['search'])) {
            $rawSearch = is_array($filters['search']) ? ($filters['search']['value'] ?? '') : $filters['search'];
        }
        $search = is_string($rawSearch) ? trim($rawSearch) : '';
        if ($search !== '') {
            $query->whereHas('content', function ($q) use ($search) {
                $q->where('title', 'like', "%{$search}%");
            });
        }

        $classification = $filters['classification'] ?? ($filters['classification_id'] ?? ($filters['classification_slug'] ?? null));
        if (filled($classification) && $classification !== 'all') {
            $query->whereHas('content.classification', function ($q) use ($classification) {
                if (is_numeric($classification)) {
                    $q->where('id', (int) $classification);
                } else {
                    $q->where('slug', $classification)->orWhere('classification_name', $classification);
                }
            });
        }

        $status = strtolower(trim((string) ($filters['status'] ?? 'all')));
        if (filled($status) && $status !== 'all') {
            if (in_array($status, ['finished', 'finish', '1'], true)) {
                $query->where('content_users.finish', 1);
            } elseif (in_array($status, ['ongoing', '0'], true)) {
                $query->where('content_users.finish', 0);
            }
        }

        return $query;
    }

    /**
     * Get filter options for UI dropdowns.
     */
    public static function getFilterOptions(User $user): array
    {
        $classifications = Classification::query()
            ->whereNotNull('classification_name')
            ->where('classification_name', '!=', '')
            ->orderBy('classification_name')
            ->get();

        $trainingTypes = Content::query()
            ->whereNotNull('type')
            ->where('type', '!=', '')
            ->distinct()
            ->pluck('type')
            ->filter(fn ($t) => in_array($t, ['Video', 'Document', 'YouTube'], true))
            ->map(fn ($t) => $t === 'YouTube' ? 'Video' : $t)
            ->unique()
            ->values()
            ->toArray();

        if (empty($trainingTypes)) {
            $trainingTypes = ['Document', 'Video', 'Playlist'];
        } else {
            $trainingTypes[] = 'Playlist';
        }

        $cleanIdEmp = preg_replace('/\s*[\/\-]\s*NIP.*$/i', '', trim((string) $user->id_emp));
        $years = DB::table('content_users')
            ->where('user_id', $user->id)
            ->whereNotNull('created_at')
            ->selectRaw('DISTINCT YEAR(created_at) as yr')
            ->pluck('yr')
            ->merge(
                DB::table('offline_trainings as ot')
                    ->join('offline_training_users as otu', 'ot.id', '=', 'otu.offline_training_id')
                    ->where(fn($q) => $q->where('otu.user_id', $user->id)->orWhere('otu.employee_id', $cleanIdEmp))
                    ->whereNotNull('ot.start_date')
                    ->selectRaw('DISTINCT YEAR(ot.start_date) as yr')
                    ->pluck('yr')
            )
            ->filter()
            ->unique()
            ->sortDesc()
            ->values()
            ->toArray();

        if (empty($years)) {
            $currentYear = (int) date('Y');
            $years = range($currentYear, $currentYear - 3);
        }

        return [
            'classifications' => $classifications,
            'trainingTypes'   => $trainingTypes,
            'years'           => $years,
        ];
    }

    /**
     * Parse human-readable active filter labels for PDF export.
     */
    public static function activeFilterLabels(array $filters): array
    {
        $labels = [];

        $rawTitle = $filters['learning_title'] ?? ($filters['title'] ?? null);
        if (is_array($rawTitle)) {
            $rawTitle = $rawTitle['value'] ?? null;
        }
        if (filled($rawTitle) && is_string($rawTitle)) {
            $labels['Title'] = trim($rawTitle);
        }

        if (filled($filters['classification'] ?? null) && $filters['classification'] !== 'all') {
            $cl = Classification::find($filters['classification']);
            $labels['Classification'] = $cl ? $cl->classification_name : $filters['classification'];
        }

        if (filled($filters['status'] ?? null) && $filters['status'] !== 'all') {
            $labels['Status'] = ucfirst($filters['status']);
        }

        if (filled($filters['type'] ?? null) && $filters['type'] !== 'all') {
            $labels['Type'] = ucfirst($filters['type']);
        }

        if (filled($filters['year'] ?? null) && $filters['year'] !== 'all') {
            $labels['Year'] = $filters['year'];
        }

        if (filled($filters['source'] ?? null) && $filters['source'] !== 'all') {
            $labels['Source'] = $filters['source'] === 'online' ? 'KP OWL' : 'Non KP OWL';
        }

        if (filled($filters['from_date'] ?? null) || filled($filters['to_date'] ?? null)) {
            $from = $filters['from_date'] ?? 'Start';
            $to = $filters['to_date'] ?? 'End';
            $labels['Date Range'] = "{$from} - {$to}";
        }

        if (filled($filters['progress_range'] ?? null) && $filters['progress_range'] !== 'all') {
            $labels['Progress'] = $filters['progress_range'] . '%';
        }

        if (filled($filters['duration'] ?? null) && $filters['duration'] !== 'all') {
            $labels['Duration'] = $filters['duration'];
        }

        return $labels;
    }
}
