<?php

namespace App\Http\Controllers;

use App\Models\Assesment;
use App\Models\News;
use App\Models\Content;
use App\Models\ContentUser;
use App\Models\EventPhoto;
use App\Models\TrainingPlan;
use App\Services\LearningAnalyticsService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;

class HomeUserController extends Controller
{
    public function index(LearningAnalyticsService $analyticsService)
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
            ->limit(6)
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
            ->limit(3)
            ->get();

        // Gallery
        $eventphoto = EventPhoto::where('active', 'Active')
            ->orderByRaw("CASE WHEN thumbnail IS NOT NULL AND thumbnail != '' THEN 0 ELSE 1 END")
            ->latest()
            ->limit(3)
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

        $continuePeriod = null;

        if ($continueCourse && $continueCourse->content) {
            $periodAssessment = Assesment::where('content_id', $continueCourse->content->id)
                ->whereNotNull('fromdate')
                ->whereNotNull('todate')
                ->first();

            if ($periodAssessment) {
                $continuePeriod = \Carbon\Carbon::parse($periodAssessment->fromdate)->format('d M Y')
                    . ' - '
                    . \Carbon\Carbon::parse($periodAssessment->todate)->format('d M Y');
            }
        }

        /*
        |--------------------------------------------------------------------------
        | Additional Dashboard Cards
        |--------------------------------------------------------------------------
        */

        $totalGallery = $eventphoto->count();

        $totalNews = $news->count();

        $totalPlaylist = $playlist->count();

        // Learning Hours — reused from My Learning page (same calculation)
        $learningHours = $analyticsService->userLearningHours((int) auth()->id());

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
                'continuePeriod',
                'learningHours',
            )
        );
    }

    public function updateHeroBackground(Request $request)
    {
        $request->validate([
            'hero_background' => ['required', 'image', 'mimes:jpg,jpeg,png,webp', 'max:5120'],
        ], [
            'hero_background.max' => 'Maximum image size is 5 MB.',
        ]);

        $user = $request->user();
        $background = $request->file('hero_background');
        $destination = public_path('storage/files/thumbnail');
        $uploadLog = Log::build([
            'driver' => 'single',
            'path' => storage_path('logs/laravel.log'),
            'level' => 'debug',
        ]);

        $fileName = sprintf(
            'background_%s_%s.%s',
            $user->id,
            now()->format('Uu'),
            strtolower($background->extension())
        );
        $relativePath = 'files/thumbnail/' . $fileName;
        $newFilePath = $destination . DIRECTORY_SEPARATOR . $fileName;
        $oldRelativePath = $user->hero_background;

        try {
            if (!File::exists($destination) && !File::makeDirectory($destination, 0755, true)) {
                throw new \RuntimeException('Unable to create the background upload directory.');
            }
        } catch (\Throwable $exception) {
            $uploadLog->error('Hero background directory creation failed.', [
                'user_id' => $user->id,
                'directory' => $destination,
                'exception' => $exception,
            ]);

            return response()->json([
                'message' => 'Upload directory could not be created.',
            ], 500);
        }

        if (!File::isDirectory($destination) || !File::isWritable($destination)) {
            $uploadLog->error('Hero background directory is not writable.', [
                'user_id' => $user->id,
                'directory' => $destination,
            ]);

            return response()->json([
                'message' => 'Directory not writable.',
            ], 500);
        }

        try {
            $background->move($destination, $fileName);
        } catch (\Throwable $exception) {
            $uploadLog->error('Hero background file move failed.', [
                'user_id' => $user->id,
                'destination' => $newFilePath,
                'exception' => $exception,
            ]);

            return response()->json([
                'message' => 'Upload failed while saving the file.',
            ], 500);
        }

        try {
            if (!$user->update(['hero_background' => $relativePath])) {
                throw new \RuntimeException('The user background path was not updated.');
            }
        } catch (\Throwable $exception) {
            try {
                if (File::exists($newFilePath) && !is_dir($newFilePath)) {
                    File::delete($newFilePath);
                }
            } catch (\Throwable $cleanupException) {
                $uploadLog->error('Failed to clean up a Hero background after a database error.', [
                    'user_id' => $user->id,
                    'path' => $newFilePath,
                    'exception' => $cleanupException,
                ]);
            }

            $uploadLog->error('Hero background database update failed.', [
                'user_id' => $user->id,
                'relative_path' => $relativePath,
                'exception' => $exception,
            ]);

            return response()->json([
                'message' => 'Database update failed. The previous background is unchanged.',
            ], 500);
        }

        $ownedBackgroundPattern = sprintf(
            '/\Afiles\/thumbnail\/background_%s_\d+\.(?:jpe?g|png|webp)\z/i',
            preg_quote((string) $user->id, '/')
        );

        if ($oldRelativePath
            && $oldRelativePath !== $relativePath
            && preg_match($ownedBackgroundPattern, $oldRelativePath)) {
            $oldFilePath = public_path('storage/' . $oldRelativePath);

            try {
                if (File::exists($oldFilePath)
                    && !is_dir($oldFilePath)
                    && !File::delete($oldFilePath)) {
                    $uploadLog->warning('The previous Hero background could not be deleted.', [
                        'user_id' => $user->id,
                        'path' => $oldFilePath,
                    ]);
                }
            } catch (\Throwable $exception) {
                $uploadLog->error('Deleting the previous Hero background failed.', [
                    'user_id' => $user->id,
                    'path' => $oldFilePath,
                    'exception' => $exception,
                ]);
            }
        }

        return response()->json([
            'message' => 'Background updated successfully.',
            'background_path' => $relativePath,
            'background_url' => asset('storage/' . $relativePath) . '?v=' . now()->timestamp,
        ]);
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
