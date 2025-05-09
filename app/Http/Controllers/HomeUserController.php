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
use Illuminate\Support\Facades\Validator;

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
        $validator = Validator::make($request->all(), [
            'unique_code' => 'required',
        ]);

        if ($validator->fails()) {
            return response()->json($validator->errors(), 422);
        }

        $assessment = Assesment::where('unique_code', $request->unique_code)->first();

        if ($assessment) {
            return response()->json([
                'assessment_slug' => $assessment->slug,
            ], 200);
        } else {
            return response()->json([
                'error_message' => 'The assessment with unique code ' . $request->unique_code . ' is not found!',
            ], 422);
        }
    }
}
