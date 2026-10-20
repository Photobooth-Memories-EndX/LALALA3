<?php

namespace App\Http\Controllers;

use App\Models\Certificate;
use App\Models\ContentUser;
use App\Services\CertificateEligibilityService;
use App\Services\Learning\CompletedPlaylistService;
use App\Services\LearningAnalyticsService;
use Illuminate\Support\Facades\Auth;

/**
 * My Learning — coordinator controller (entry point).
 *
 * Hanya menangani halaman dashboard (ringkasan ringan) dan routing tab.
 * Seluruh business logic per-tab didelegasikan ke controller khusus:
 *
 *  - Learning On Going  -> LearningOngoingController
 *  - Learning Record    -> LearningRecordController
 *  - Assessment         -> LearningAssessmentController
 *  - Monitoring         -> LearningMonitoringController
 */
class LearningController extends Controller
{
    /**
     * Lightweight dashboard – only loads summary counts and profile data.
     * Tab content is fetched on-demand via dashboardPartial().
     */
    public function dashboard(
        LearningAnalyticsService $analyticsService,
        CompletedPlaylistService $completedPlaylistService
    )
    {
        if (strtolower(trim((string) request('record_mode'))) === 'export') {
            $queryParams = request()->query();
            $queryParams['record_mode'] = 'online';
            if (!isset($queryParams['learning_tab'])) {
                $queryParams['learning_tab'] = 'record';
            }
            return redirect()->to(route('user.learning.dashboard', $queryParams));
        }

        if (request()->boolean('certificate_export') && request('learning_tab') === 'certificate') {
            return app(LearningRecordController::class)->certificate();
        }

        $userId = Auth::id();
        $user   = Auth::user();

        $totalLearning = ContentUser::where('user_id', $userId)->count();

        $completedLearning = ContentUser::where('user_id', $userId)
            ->where(function ($q) {
                $q->where('progress', '>=', 100)->orWhere('finish', '1');
            })
            ->count();

        $ongoingLearning = $analyticsService->ongoingLearningCount((int) $userId);

        $completedPlaylists = $completedPlaylistService->forLearningRecord((int) $userId);
        $completedPlaylistLearning = $completedPlaylists->count();

        $recordHourService = app(\App\Services\Learning\LearningRecordLearningHourService::class);
        $completedContentLearning = ContentUser::join('content', 'content.id', '=', 'content_users.content_id')
            ->where('content_users.user_id', $userId)
            ->where('content_users.finish', '1')
            ->whereNull('content.deleted_at')
            ->whereIn('content.type', ['Video', 'Document'])
            ->count();
        $requestedRecordMode = strtolower(trim((string) request('record_mode', 'online')));
        $recordMode = (request('learning_tab') === 'record' || request()->filled('record_mode'))
            && in_array($requestedRecordMode, ['online', 'offline', 'all'], true)
            ? $requestedRecordMode
            : 'online';
        $learningHours = $recordHourService->getTotalLearningHoursByMode($recordMode, (int) $userId, $user?->id_emp);

        $certificates = Certificate::where('user_id', $userId)
            ->get(['certificate_type']);

        $certificateStats = [
            'total' => $certificates->count(),
            'playlist' => $certificates->where('certificate_type', 'playlist')->count(),
            'content' => $certificates->where('certificate_type', 'content')->count(),
        ];

        $averageProgress = (int) round(ContentUser::where('user_id', $userId)->avg('progress') ?? 0);

        return view('user.learning.dashboard', compact(
            'user',
            'learningHours',
            'totalLearning',
            'completedLearning',
            'ongoingLearning',
            'completedContentLearning',
            'completedPlaylistLearning',
            'certificateStats',
            'averageProgress'
        ));
    }

    /**
     * Returns HTML fragment for a single tab.
     * Called via AJAX from dashboard-script.blade.php — routing only,
     * business logic tiap tab ada di controller masing-masing.
     */
    public function dashboardPartial(string $tab, CertificateEligibilityService $eligibilityService)
    {
        return match ($tab) {
            // Tab ongoing: Content (default), Playlist, dan placeholder MOOC.
            'ongoing' => match (request('ongoing_mode')) {
                'playlist' => app(LearningOngoingController::class)->playlist(),
                'mooc'     => app(LearningOngoingController::class)->mooc(),
                default    => strtolower((string) request('type')) === 'playlist'
                    ? app(LearningOngoingController::class)->playlist()
                    : app(LearningOngoingController::class)->content(),
            },
            'record'      => match (strtolower(trim((string) request('record_mode', 'online')))) {
                'offline' => app(\App\Http\Controllers\User\Record\LearningRecordOfflineController::class)->partial(),
                'all'     => app(\App\Http\Controllers\User\Record\LearningRecordAllController::class)->partial($eligibilityService),
                default   => app(\App\Http\Controllers\User\Record\LearningRecordOnlineController::class)->partial($eligibilityService),
            },
            'assessment'  => app(LearningAssessmentController::class)->partial(request()),
            'my-task'     => app(\App\Http\Controllers\MyTaskController::class)->tabPartial(request()),
            'claim'       => view('user.learning.dashboard-partials.claim'),
            'certificate' => app(LearningRecordController::class)->certificate(),
            'monitoring'  => app(LearningMonitoringController::class)->partial(),
            default       => abort(404),
        };
    }

    /**
     * Apply common Learning filters (from_date, to_date, program_type, progress_range, duration).
     */
    public function applyFilters($query, \Illuminate\Http\Request $request)
    {
        $fromDate = $request->input('from_date') ?? $request->input('date_from');
        $toDate = $request->input('to_date') ?? $request->input('date_to');

        if (filled($fromDate) && filled($toDate)) {
            $query->whereBetween('content_users.created_at', [$fromDate . ' 00:00:00', $toDate . ' 23:59:59']);
        } elseif (filled($fromDate)) {
            $query->whereDate('content_users.created_at', '>=', $fromDate);
        } elseif (filled($toDate)) {
            $query->whereDate('content_users.created_at', '<=', $toDate);
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
                $isSqlite = \Illuminate\Support\Facades\DB::connection()->getDriverName() === 'sqlite';
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

        if ($request->filled('employee_id') && $request->employee_id !== 'all') {
            if (is_numeric($request->employee_id)) {
                $query->where('content_users.user_id', $request->employee_id);
            } else {
                $query->whereHas('user', function ($uq) use ($request) {
                    $uq->where('name', 'like', '%' . $request->employee_id . '%')
                       ->orWhere('id_emp', 'like', '%' . $request->employee_id . '%');
                });
            }
        }

        if ($request->filled('learning_title')) {
            $query->whereHas('content', function ($cq) use ($request) {
                $cq->where('title', 'like', '%' . $request->learning_title . '%');
            });
        }

        return $query;
    }
}
