<?php

namespace App\Http\Controllers;

use App\Models\Assesment;
use App\Models\News;
use App\Models\Content;
use App\Models\ContentUser;
use App\Models\EventPhoto;
use App\Models\TrainingPlan;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class HomeUserController extends Controller
{
    public function index()
    {
        // Top Video
        $top_video = Content::select(
                'id',
                'title',
                'type',
                'slug',
                'duration',
                'link'
            )
            ->where('playlistonly', 'N')
            ->whereIn('type', ['YouTube', 'Video'])
            ->where('active', 'Active')
            ->orderBy('views', 'desc')
            ->limit(8)
            ->get();

        // All Content
        $content = Content::select(
                'id',
                'title',
                'type',
                'slug',
                'duration',
                'link'
            )
            ->where('playlistonly', 'N')
            ->where('active', 'Active')
            ->get();

        // Playlist
        $playlist = TrainingPlan::where('active', 'Active')
            ->latest()
            ->get();

        // News
        $news = News::latest()
            ->limit(5)
            ->get();

        // Gallery
        $eventphoto = EventPhoto::where('active', 'Active')
            ->latest()
            ->limit(10)
            ->get();

        // User Learning Data
        $content_user = ContentUser::where(
                'user_id',
                auth()->id()
            )->get();

        /*
        |--------------------------------------------------------------------------
        | Dashboard Statistics
        |--------------------------------------------------------------------------
        */

        $completedCourse = $content_user
            ->where('finish', 1)
            ->count();

        $ongoingCourse = $content_user
            ->where('finish', 0)
            ->count();

        $totalCourse = $completedCourse + $ongoingCourse;

        $progressPercent = $totalCourse > 0
            ? round(($completedCourse / $totalCourse) * 100)
            : 0;

        /*
        |--------------------------------------------------------------------------
        | Continue Learning
        |--------------------------------------------------------------------------
        */

        $continueLearning = ContentUser::where(
                'user_id',
                auth()->id()
            )
            ->where('finish', 0)
            ->latest()
            ->first();

        $continueContent = null;

        if ($continueLearning) {

            $continueContent = Content::find(
                $continueLearning->content_id
            );
        }

        /*
        |--------------------------------------------------------------------------
        | Additional Dashboard Cards
        |--------------------------------------------------------------------------
        */

        $totalGallery = $eventphoto->count();

        $totalNews = $news->count();

        $totalPlaylist = $playlist->count();

        return view(
            'homepage',
            compact(
                'top_video',
                'content',
                'playlist',
                'news',
                'eventphoto',
                'content_user',

                // Dashboard Modern
                'completedCourse',
                'ongoingCourse',
                'progressPercent',
                'continueLearning',
                'continueContent',
                'totalGallery',
                'totalNews',
                'totalPlaylist'
            )
        );
    }

    public function accessAssessmentByUniqueCode(Request $request)
    {
        $validator = Validator::make(
            $request->all(),
            [
                'unique_code' => 'required',
            ]
        );

        if ($validator->fails()) {
            return response()->json(
                $validator->errors(),
                422
            );
        }

        $assessment = Assesment::where(
            'unique_code',
            $request->unique_code
        )->first();

        if ($assessment) {

            return response()->json([
                'assessment_slug' => $assessment->slug,
            ], 200);

        } else {

            return response()->json([
                'error_message' =>
                    'The assessment with unique code '
                    . $request->unique_code .
                    ' is not found!'
            ], 422);
        }
    }
}
