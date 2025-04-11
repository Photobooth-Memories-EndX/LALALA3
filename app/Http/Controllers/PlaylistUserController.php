<?php

namespace App\Http\Controllers;

use App\Models\Comment;
use App\Models\Content;
use App\Models\Assesment;
use App\Models\AssesmentResult;
use App\Models\ContentUser;
use App\Models\TrainingPlan;
use App\Models\TrainingPlanDetail;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class PlaylistUserController extends Controller
{
    public function index()
    {
        $trainingplan = TrainingPlan::with(['trainingplandetail' => function ($query) {
                $query->orderBy('content_order');
            }, 'trainingplandetail.content'
        ])->where('active', 'Active')->get();

        return view('user.playlist.index', [
            "title" =>  "Playlist",
            "trainingplan" => $trainingplan
        ]);
    }

    public function view($slug, $vidslug)
    {
        $trainingplan   = TrainingPlan::findBySlug($slug);
        $content        = Content::findBySlug($vidslug);
        $content->views += 1;
        $content->save();

        //add content to user learning on going if not exist yet
        $content_user = ContentUser::firstOrCreate([
            'user_id' => auth()->user()->id, 'content_id' => $content->id
        ]);

        $content_user_all = ContentUser::where('user_id', auth()->user()->id)->get();
        $content_all      = TrainingPlanDetail::where('trainingplan_id', $trainingplan->id)->with('content')->get();
        $assessmentAccess = false;
        $progressContent  = 0;

        foreach ($content_all as $ca) {
            $contentUser = ContentUser::where('user_id', auth()->user()->id)->where('content_id', $ca->content->id)->first()->progress ?? 0;

            $progressContent += $contentUser;
        }

        $percentageProgressPlaylist = $progressContent / count($content_all);

        if ($percentageProgressPlaylist >= 100) {
            $assessmentAccess = true;
        }

        $assesment = Assesment::where('type', 'trainingplan')->where('trainingplan_id', $trainingplan->id)->first();
        $comment   = Comment::with('user')->where('parent_id', null)->where('type', 'content')->where('content_id', $content->id)->latest()->get();

        $queryAssessmentResult = AssesmentResult::with('user', 'assesment')
            ->where('user_id', Auth::user()->id)
            ->where('assesment_id', $assesment->id ?? '');

        $assessmentCount        = $queryAssessmentResult->count();
        $assessmentLatestScores = $queryAssessmentResult->latest()->first()->score ?? 0;
        
        // Assessment Content
        $assessmentContent = Assesment::where('type', 'content')->where('content_id', $content->id)->first();
        $queryAssessmentContentResult = AssesmentResult::with('user', 'assesment')
            ->where('user_id', Auth::user()->id)
            ->where('assesment_id', $assessmentContent->id ?? '');

        $assessmentContentCount        = $queryAssessmentContentResult->count();
        $assessmentContentLatestScores = $queryAssessmentContentResult->latest()->first()->score ?? 0;

        return view('user.playlist.view', [
            "playlist" => $trainingplan,
            "content" => $content,
            "content_all" => $content_all,
            "content_user_all" => $content_user_all,
            "content_user" => $content_user,
            "assesment" => $assesment,
            "comment" => $comment,
            "assessmentAccess" => $assessmentAccess,
            "assessmentCount" => $assessmentCount,
            "assessmentLatestScores" => $assessmentLatestScores,
            "percentageProgressPlaylist" => $percentageProgressPlaylist,
            "assessmentContent" => $assessmentContent,
            "assessmentContentCount" => $assessmentContentCount,
            "assessmentContentLatestScores" => $assessmentContentLatestScores,
        ]);
    }
}
