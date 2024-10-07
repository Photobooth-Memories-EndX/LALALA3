<?php

namespace App\Http\Controllers;

use App\Models\Assesment;
use App\Models\AssesmentResult;
use App\Models\Classification;
use App\Models\Comment;
use App\Models\Content;
use App\Models\ContentUser;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ContentUserController extends Controller
{
    public function index()
    {
        $content = Content::where('playlistonly', 'N')->whereActive('Active')->get();
        return view('user.content.index', compact('content'));
    }
    public function view($slug)
    {
        $content = Content::findBySlug($slug);
        if ($content === null) {
            abort(404);
        }
        $content->views += 1;
        $content->save();

        //add content to user learning on going if not exist yet
        $content_user = ContentUser::firstOrCreate([
            'user_id' => auth()->user()->id, 'content_id' => $content->id
        ]);

        $queryAssessment = Assesment::where('type', 'content');
            
        $assesment          = $queryAssessment->where('content_id', $content->id)->first();

        if (isset($assesment)) {
            $queryAssessmentResult = AssesmentResult::with('user', 'assesment')
                ->where('user_id', Auth::user()->id)
                ->where('assesment_id', $assesment->id);
        } else {
            $queryAssessmentResult = AssesmentResult::with('user', 'assesment')
                ->where('user_id', Auth::user()->id);
        }

        $assessmentCount        = $queryAssessmentResult->count();
        $assessmentLatestScores = $queryAssessmentResult->latest()->first()->score ?? 0;

        $comment = Comment::with('user')->where('type', 'content')->where('content_id', $content->id)->latest()->get();

        return view('user.content.view', compact('content', 'assesment', 'assessmentCount', 'assessmentLatestScores', 'comment', 'content_user'));
    }

    public function category($slug)
    {
        $category = Classification::findBySlug($slug);
        if ($category === null) {
            abort(404);
        }
        $content = Content::where('classification_id', $category->id)->whereActive('Active')->get();
        return view('user.content.category', compact('content', 'category'));
    }

    public function progress(Request $request, $slug)
    {
        $content = Content::findBySlug($slug);
        if ($content === null) {
            abort(404);
        }
        $content_user = ContentUser::where('user_id', auth()->user()->id)->where('content_id', $content->id)->first();

        if ($content_user->progress < 100) {
            $progression = ((float) $request->current_time / (float) $request->duration) * 100;
            $content_user->progress = $progression >= 100 ? 100 : $progression;

            if ($content_user->progress >= 100) {
                $content_user->finish = "1";
            }

            $content_user->save();

            return response()->json(['success' => 'Progress saved', 'status' => 200]);
        } else {
            $content_user->finish = "1";
            $content_user->save();

            return response()->json(['success' => 'Watching again', 'status' => 200]);
        }
    }
}
