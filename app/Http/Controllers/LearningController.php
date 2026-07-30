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
        $userId = Auth::id();
        $user   = Auth::user();

        $learningHours = $analyticsService->userLearningHours((int) $userId);

        $totalLearning = ContentUser::where('user_id', $userId)->count();

        $completedLearning = ContentUser::where('user_id', $userId)
            ->where(function ($q) {
                $q->where('progress', '>=', 100)->orWhere('finish', '1');
            })
            ->count();

        $ongoingLearning = $analyticsService->ongoingLearningCount((int) $userId);

        $completedContentLearning = ContentUser::query()
            ->join('content', 'content.id', '=', 'content_users.content_id')
            ->where('content_users.user_id', $userId)
            ->where('content_users.finish', '1')
            ->whereNull('content.deleted_at')
            ->whereIn('content.type', ['Video', 'Document'])
            ->count();

        $completedPlaylistLearning = $completedPlaylistService->forUser((int) $userId)->count();

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
            // Tab ongoing terdiri dari 2 mode terpisah: Content (default) & Playlist.
            'ongoing' => request('ongoing_mode') === 'playlist'
                ? app(LearningOngoingController::class)->playlist()
                : app(LearningOngoingController::class)->content(),
            'record'      => app(LearningRecordController::class)->partial($eligibilityService),
            'assessment'  => app(LearningAssessmentController::class)->partial(request()),
            'claim'       => view('user.learning.dashboard-partials.claim'),
            'certificate' => app(LearningRecordController::class)->certificate(),
            'monitoring'  => app(LearningMonitoringController::class)->partial(),
            default       => abort(404),
        };
    }
}
