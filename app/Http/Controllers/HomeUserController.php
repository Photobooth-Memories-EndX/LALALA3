<?php

namespace App\Http\Controllers;

use App\Models\Assesment;
use App\Models\News;
use App\Models\Content;
use App\Models\ContentUser;
use App\Models\EventPhoto;
use App\Models\TrainingPlan;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Validator;

class HomeUserController extends Controller
{
    public function index()
    {
        // Content has no image column: the thumbnail comes from YouTube
        // (via `link`) or from storage/files/content/{id}/thumb.jpg on disk,
        // so "has thumbnail" must be checked per item, not in the query.
        $contentHasThumbnail = function ($item) {
            if ($item->type === 'YouTube') {
                return !empty($item->link);
            }

            $thumb = public_path(
                'storage/files/content/' . $item->id . '/thumb.jpg'
            );

            return File::exists($thumb) && !is_dir($thumb);
        };

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
            ->get()
            ->sortBy(fn ($item) => $contentHasThumbnail($item) ? 0 : 1)
            ->values();

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
            ->get()
            ->sortBy(fn ($item) => $contentHasThumbnail($item) ? 0 : 1)
            ->values();

        // Playlist
        $playlist = TrainingPlan::with([
                'trainingplandetail.content'
            ])
            ->where('active', 'Active')
            ->orderByRaw("CASE WHEN thumbnail IS NOT NULL AND thumbnail != '' THEN 0 ELSE 1 END")
            ->latest()
            ->get();

        // News
        $news = News::orderByRaw("CASE WHEN image IS NOT NULL AND image != '' THEN 0 ELSE 1 END")
            ->latest()
            ->limit(2)
            ->get();

        // Gallery
        $eventphoto = EventPhoto::where('active', 'Active')
            ->orderByRaw("CASE WHEN thumbnail IS NOT NULL AND thumbnail != '' THEN 0 ELSE 1 END")
            ->latest()
            ->limit(10)
            ->get();

        // User Learning Data
        $content_user = ContentUser::where(
                'user_id',
                auth()->id()
            )->get();

        $upcomingDeadlines = Assesment::whereNotNull('todate')
            ->whereDate('todate', '>=', now())
            ->orderBy('todate')
            ->take(2)
            ->get();

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

        $continueCourse = ContentUser::with('content')
            ->where('user_id', auth()->id())
            ->where('progress', '>', 0)
            ->orderByDesc('updated_at')
            ->first();

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
                'totalGallery',
                'totalNews',
                'totalPlaylist',
                'upcomingDeadlines',
                'continueCourse',
            )
        );
    }

public function upcomingDeadline()
{
    return response()->json(
        $this->getUpcomingDeadlines()
    );
}

    public function getUpcomingDeadlines()
{
    $currentContentIds = ContentUser::where(
            'user_id',
            auth()->id()
        )
        ->where('finish', 0)
        ->orderByDesc('progress')
        ->pluck('content_id')
        ->toArray();

    return Assesment::whereNotNull('todate')
        ->whereDate('todate', '>=', now())
        ->whereIn('content_id', $currentContentIds)
        ->orderBy('todate', 'asc')
        ->take(5)
        ->get();
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
