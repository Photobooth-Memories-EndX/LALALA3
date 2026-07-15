<?php

namespace App\Http\Controllers;

use App\Models\Assesment;
use App\Models\AssesmentResult;
use Illuminate\Support\Facades\Auth;

/**
 * My Learning Assessment tab.
 *
 * Owns the assessment data and AJAX-rendered dashboard partial while the
 * LearningController remains the dashboard orchestrator.
 */
class LearningAssessmentController extends Controller
{
    /**
     * Render the Assessment tab fragment requested by the learning dashboard.
     */
    public function partial()
    {
        $user   = Auth::user();
        $userId = Auth::id();

        return view('user.learning.dashboard-partials.assessment', [
            'assessments' => $this->getUserAssessments($user, $userId),
            'result'      => $this->getUserAssessmentResults($userId),
        ]);
    }

    private function getUserAssessments($user, $userId)
    {
        return Assesment::with([
                'assesments_assigned',
                'contents',
                'scores'      => fn ($q) => $q->where('user_id', $userId),
                'trainingplan',
            ])
            ->where(function ($query) use ($user, $userId) {
                $query->whereHas('assesments_assigned', function ($q) use ($user) {
                    $q->where('assigned_id', $user->id_emp)
                        ->orWhere('assigned_id', $user->id_team ?? $user->id_tean)
                        ->orWhere('assigned_id', $user->id_department);
                })
                ->orWhereHas('contents', function ($q) use ($userId) {
                    $q->whereHas('contentuser', function ($q) use ($userId) {
                        $q->where('progress', '>=', 100)->where('user_id', $userId);
                    });
                })
                ->orWhereHas('scores', function ($q) use ($userId) {
                    $q->where('user_id', $userId);
                });
            })
            ->latest()
            ->get();
    }

    private function getUserAssessmentResults($userId)
    {
        return AssesmentResult::with('assesment.trainingplan')
            ->where('user_id', $userId)
            ->whereFinal(1)
            ->latest()
            ->get();
    }
}
