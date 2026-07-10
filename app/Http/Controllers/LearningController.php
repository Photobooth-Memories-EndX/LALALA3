<?php

namespace App\Http\Controllers;

use App\Models\AssesmentResult;
use App\Models\ContentUser;
use App\Services\CertificateEligibilityService;
use App\Services\LearningAnalyticsService;
use Illuminate\Support\Facades\Auth;

/**
 * My Learning — coordinator controller (entry point).
 *
 * Hanya menangani halaman dashboard (ringkasan ringan) dan routing tab.
 * Seluruh business logic per-tab didelegasikan ke controller khusus:
 *
 *  - Learning On Going  -> LearningOngoingController
 *  - Learning Record    -> LearningRecordController (termasuk assessment & certificate)
 *  - Monitoring         -> LearningMonitoringController
 */
class LearningController extends Controller
{
    /**
     * Lightweight dashboard – only loads summary counts and profile data.
     * Tab content is fetched on-demand via dashboardPartial().
     */
    public function dashboard(LearningAnalyticsService $analyticsService)
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

        $ongoingLearning = ContentUser::where('user_id', $userId)
            ->where('finish', '0')
            ->count();

        $passedAssessment = AssesmentResult::where('user_id', $userId)
            ->where('final', 1)
            ->where('pass', 1)
            ->count();

        $averageProgress = (int) round(ContentUser::where('user_id', $userId)->avg('progress') ?? 0);

        return view('user.learning.dashboard', compact(
            'user',
            'learningHours',
            'totalLearning',
            'completedLearning',
            'ongoingLearning',
            'passedAssessment',
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
            'ongoing'     => app(LearningOngoingController::class)->partial(),
            'record'      => app(LearningRecordController::class)->partial($eligibilityService),
            'assessment'  => app(LearningRecordController::class)->assessment(),
            'claim'       => view('user.learning.dashboard-partials.claim'),
            'certificate' => app(LearningRecordController::class)->certificate(),
            'monitoring'  => app(LearningMonitoringController::class)->partial(),
            default       => abort(404),
        };
    }
}
