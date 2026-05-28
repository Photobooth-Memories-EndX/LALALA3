<?php

namespace App\Http\Controllers;

use App\Models\Assesment;
use App\Models\AssesmentResult;
use App\Models\ContentUser;
use Illuminate\Support\Facades\Auth;

class LearningController extends Controller
{
    public function ongoing()
    {
        $content_user = ContentUser::with('content')
            ->where('user_id', auth()->id())
            ->where('finish', '0')
            ->latest()
            ->get();

        return view('user.learning.ongoing', compact('content_user'));
    }

    public function record()
    {
        $content_user = ContentUser::with([
                'content.classification',
                'assesment.scores',
            ])
            ->where('user_id', auth()->id())
            ->where('finish', '1')
            ->latest()
            ->get();

        return view('user.learning.finish', compact('content_user'));
    }

    public function assesment()
    {
        $user = Auth::user();

        $assessments = Assesment::with([
                'assesments_assigned',
                'contents',
                'scores',
            ])
            ->whereHas('assesments_assigned', function ($query) use ($user) {
                $query->where('assigned_id', $user->id_emp)
                    ->orWhere('assigned_id', $user->id_team ?? $user->id_tean)
                    ->orWhere('assigned_id', $user->id_department);
            })
            ->orWhereHas('contents', function ($query) {
                $query->whereHas('contentuser', function ($contentUserQuery) {
                    $contentUserQuery->where('progress', '>=', 100)
                        ->where('user_id', Auth::id());
                });
            })
            ->orWhereHas('scores', function ($query) {
                $query->where('user_id', Auth::id());
            })
            ->latest()
            ->get();

        $result = AssesmentResult::with('assesment')
            ->where('user_id', auth()->id())
            ->whereFinal(1)
            ->latest()
            ->get();

        return view('user.learning.result', compact('result', 'assessments'));
    }

    public function dashboard()
    {
        $user = Auth::user();
        $userId = Auth::id();

        $content_user = $this->getUserLearningContents($userId);

        $ongoing_content_user = $this->getOngoingLearningContents($userId);

        $assessments = $this->getUserAssessments($user, $userId);

        $result = $this->getUserAssessmentResults($userId);

        return view('user.learning.dashboard', compact(
            'content_user',
            'ongoing_content_user',
            'assessments',
            'result'
        ));
    }

    private function getUserLearningContents($userId)
    {
        return ContentUser::with([
                'content.classification',
                'assesment.scores',
            ])
            ->where('user_id', $userId)
            ->latest()
            ->get();
    }

    private function getOngoingLearningContents($userId)
    {
        $ongoingSearch = request('ongoing_search');
        $ongoingStatus = request('ongoing_status', 'all');
        $ongoingYear = request('ongoing_year', 'all');

        return ContentUser::with([
                'content.classification',
                'assesment.scores',
            ])
            ->where('user_id', $userId)
            ->where('finish', '0')
            ->when($ongoingSearch, function ($query) use ($ongoingSearch) {
                $query->whereHas('content', function ($contentQuery) use ($ongoingSearch) {
                    $contentQuery->where('title', 'like', '%' . $ongoingSearch . '%')
                        ->orWhereHas('classification', function ($classificationQuery) use ($ongoingSearch) {
                            $classificationQuery->where('classification_name', 'like', '%' . $ongoingSearch . '%');
                        });
                });
            })
            ->when($ongoingStatus !== 'all', function ($query) use ($ongoingStatus) {
                $query->where('status', $ongoingStatus);
            })
            ->when($ongoingYear !== 'all', function ($query) use ($ongoingYear) {
                $query->whereYear('created_at', $ongoingYear);
            })
            ->latest()
            ->paginate(9, ['*'], 'ongoing_page')
            ->withQueryString();
    }

    private function getUserAssessments($user, $userId)
    {
        return Assesment::with([
                'assesments_assigned',
                'contents',
                'scores',
                'trainingplan',
            ])
            ->whereHas('assesments_assigned', function ($query) use ($user) {
                $query->where('assigned_id', $user->id_emp)
                    ->orWhere('assigned_id', $user->id_team ?? $user->id_tean)
                    ->orWhere('assigned_id', $user->id_department);
            })
            ->orWhereHas('contents', function ($query) use ($userId) {
                $query->whereHas('contentuser', function ($contentUserQuery) use ($userId) {
                    $contentUserQuery->where('progress', '>=', 100)
                        ->where('user_id', $userId);
                });
            })
            ->orWhereHas('scores', function ($query) use ($userId) {
                $query->where('user_id', $userId);
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