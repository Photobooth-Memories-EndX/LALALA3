<?php

namespace App\Http\Controllers;

use App\Models\Assesment;
use App\Models\News;
use App\Models\Content;
use App\Models\ContentUser;
use App\Models\EventPhoto;
use App\Models\TrainingPlan;
use App\Models\TrainingPlanDetail;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Session;

class HomeUserController extends Controller
{
    public function index()
    {
        $top_video    = Content::select('id', 'title', 'type', 'slug', 'duration', 'link')->where('playlistonly', 'N')->whereIn('type', ['YouTube', 'Video'])->where('active', 'Active')->orderby('views', 'desc')->offset(0)->limit(8)->get();
        $content      = Content::select('id', 'title', 'type', 'slug', 'duration', 'link')->where('playlistonly', 'N')->where('active', 'Active')->get();
        $playlist     = TrainingPlan::where('active', 'Active')->latest()->get();
        $news         = News::latest()->offset(0)->limit(3)->get();
        $eventphoto   = EventPhoto::where('active', 'Active')->latest()->get();
        $content_user = ContentUser::where('user_id', auth()->user()->id)->get();

        return view('homepage', compact('top_video', 'content', 'playlist', 'news', 'eventphoto', 'content_user'));
    }

    public function accessAssessmentByUniqueCode(Request $request)
    {
        $request->validate([
            'unique_code' => 'required',
        ]);

        $assessment = Assesment::where('unique_code', $request->unique_code)->first();

        if ($assessment) {
            return redirect()->route('assesment.user.view', ['slug' => $assessment->slug]);
        } else {
            Session::flash('unique_code', $request->unique_code);
            Session::flash('error', 'The unique assessment code is not found.');

            return redirect()->back();
        }
    }
}
