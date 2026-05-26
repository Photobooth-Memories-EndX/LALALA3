<?php

namespace App\Http\Controllers;

use App\Models\Assesment;
use App\Models\ContentUser;
use App\Models\AssesmentResult;
use Illuminate\Support\Facades\Auth;

class LearningController extends Controller
{
    public function ongoing()
    {
        $content_user = ContentUser::with('content')
            ->where('user_id', auth()->user()->id)
            ->where('finish', '0')
            ->orderBy('created_at', 'desc')->get();

        return view('user.learning.ongoing', compact('content_user'));
    }

    public function record()
    {
        $content_user = ContentUser::with('content.classification', 'assesment.scores')->where('user_id', auth()->user()->id)->where('finish', '1')->latest()->get();

        return view('user.learning.finish', compact('content_user'));
    }

    public function assesment()
    {
        $assessments = Assesment::with('assesments_assigned', 'contents', 'scores')
            ->whereHas('assesments_assigned', function($query) {
                return $query->where('assigned_id', Auth::user()->id_emp)
                    ->orWhere('assigned_id', Auth::user()->id_tean)
                    ->orWhere('assigned_id', Auth::user()->id_department);
            })->orwhereHas('contents', function ($query) {
                return $query->with('contentuser')
                    ->whereHas('contentuser', function ($query) {
                        return $query->where('progress', '>=' , 100)
                            ->where('user_id', Auth::user()->id);
                    });
            })->orWhereHas('scores', function($query) {
                return $query->where('user_id', Auth::user()->id);
            })->latest()->get();
        $result      = AssesmentResult::with(['assesment'])->where('user_id', auth()->user()->id)->whereFinal(1)->latest()->get();

        return view('user.learning.result', compact('result', 'assessments'));
    }
    public function dashboard()
    {
        $userId = auth()->id();

        $content_user = ContentUser::with([
                'content.classification',
                'assesment.scores',
            ])
            ->where('user_id', $userId)
            ->latest()
            ->get();

        $ongoing_content_user = ContentUser::with([
                'content.classification',
                'assesment.scores',
            ])
            ->where('user_id', $userId)
            ->where('finish', '0')
            ->latest()
            ->paginate(9, ['*'], 'ongoing_page');

        $assessments = Assesment::with('assesments_assigned', 'contents', 'scores', 'trainingplan')
            ->whereHas('assesments_assigned', function ($query) {
                $query->where('assigned_id', Auth::user()->id_emp)
                    ->orWhere('assigned_id', Auth::user()->id_team ?? Auth::user()->id_tean)
                    ->orWhere('assigned_id', Auth::user()->id_department);
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

        $result = AssesmentResult::with(['assesment.trainingplan'])
            ->where('user_id', $userId)
            ->whereFinal(1)
            ->latest()
            ->get();

        return view('user.learning.dashboard', compact(
            'content_user',
            'ongoing_content_user',
            'assessments',
            'result'
        ));
    }
}
