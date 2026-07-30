<?php

namespace App\Http\Controllers;

use App\Models\Assesment;
use App\Models\News;
use App\Models\Content;
use App\Models\ContentUser;
use App\Models\EventPhoto;
use App\Models\TrainingPlan;
use App\Services\LearningAnalyticsService;
use App\Support\DurationFormatter;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
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
        $heroSummary = $analyticsService->heroSummary((int) auth()->id());
        $content_user = $heroSummary['content_user'];

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

        $completedCourse = $heroSummary['completedCourse'];

        $ongoingCourse = $heroSummary['ongoingCourse'];

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
        $continueProgressDetail = null;

        if ($continueCourse && $continueCourse->content) {
            $continueProgress = min(100, max(0, (int) ($continueCourse->progress ?? 0)));
            $continueContentType = $continueCourse->content->type;

            if (in_array($continueContentType, ['Document', 'SOP/Regulation'], true)) {
                $totalPages = (int) ($continueCourse->total_pages ?? 0);
                $savedPages = (int) ($continueCourse->last_page ?? 0);
                $estimatedPages = $totalPages > 0
                    ? (int) round(($continueProgress / 100) * $totalPages)
                    : 0;

                $continueProgressDetail = [
                    'type' => 'pages',
                    'read' => min($totalPages, max($savedPages, $estimatedPages)),
                    'total' => $totalPages,
                ];
            } elseif (in_array($continueContentType, ['Video', 'YouTube', 'Audio'], true)) {
                $totalSeconds = DurationFormatter::toSeconds($continueCourse->content->duration)
                    ?? (int) ($continueCourse->duration_seconds ?? 0);
                $savedSeconds = (int) ($continueCourse->current_time ?? 0);
                $estimatedSeconds = $totalSeconds > 0
                    ? (int) round(($continueProgress / 100) * $totalSeconds)
                    : 0;

                $continueProgressDetail = [
                    'type' => 'duration',
                    'watched' => min($totalSeconds, max($savedSeconds, $estimatedSeconds)),
                    'total' => $totalSeconds,
                ];
            }

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
        $learningHours = $heroSummary['learningHours'];

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
                'continueProgressDetail',
                'learningHours',
            )
        );
    }

    public function updateHeroBackground(Request $request)
    {
        $uploadLog = Log::build([
            'driver' => 'single',
            'path' => storage_path('logs/laravel.log'),
            'level' => 'debug',
        ]);

        $uploadLog->info('Hero background upload request received.', [
            'user_id' => optional($request->user())->id,
            'input_keys' => array_keys($request->except('_token')),
            'has_file' => $request->hasFile('hero_background'),
            'file' => $request->hasFile('hero_background') ? [
                'name' => $request->file('hero_background')->getClientOriginalName(),
                'mime' => $request->file('hero_background')->getMimeType(),
                'size' => $request->file('hero_background')->getSize(),
                'error' => $request->file('hero_background')->getError(),
            ] : null,
        ]);

        $request->validate([
            'hero_background' => ['required', 'image', 'mimes:jpg,jpeg,png,webp', 'max:5120'],
        ], [
            'hero_background.max' => 'Maximum image size is 5 MB.',
        ]);

        $user = $request->user();
        $background = $request->file('hero_background');
        $disk = Storage::disk('public');

        $fileName = sprintf(
            'background_%s_%s.%s',
            $user->id,
            now()->format('Uu'),
            strtolower($background->extension())
        );
        $relativePath = 'files/thumbnail/' . $fileName;
        $oldRelativePath = $user->hero_background;

        try {
            $storedPath = $disk->putFileAs('files/thumbnail', $background, $fileName);

            if ($storedPath !== $relativePath) {
                throw new \RuntimeException('The uploaded background could not be stored.');
            }

            $uploadLog->info('Hero background file stored.', [
                'user_id' => $user->id,
                'path' => $storedPath,
            ]);
        } catch (\Throwable $exception) {
            $uploadLog->error('Hero background file move failed.', [
                'user_id' => $user->id,
                'destination' => $relativePath,
                'exception' => $exception,
            ]);

            return response()->json([
                'message' => 'Upload failed while saving the file: ' . $exception->getMessage(),
            ], 500);
        }

        try {
            $user->hero_background = $relativePath;

            if (!$user->save()) {
                throw new \RuntimeException('The user background path was not updated.');
            }

            $user->refresh();

            if ($user->hero_background !== $relativePath) {
                throw new \RuntimeException('The saved user background path could not be verified.');
            }

            $uploadLog->info('Hero background database path updated.', [
                'user_id' => $user->id,
                'path' => $user->hero_background,
            ]);
        } catch (\Throwable $exception) {
            try {
                $disk->delete($relativePath);
            } catch (\Throwable $cleanupException) {
                $uploadLog->error('Failed to clean up a Hero background after a database error.', [
                    'user_id' => $user->id,
                    'path' => $relativePath,
                    'exception' => $cleanupException,
                ]);
            }

            $uploadLog->error('Hero background database update failed.', [
                'user_id' => $user->id,
                'relative_path' => $relativePath,
                'exception' => $exception,
            ]);

            return response()->json([
                'message' => 'Database update failed: ' . $exception->getMessage(),
            ], 500);
        }

        $ownedBackgroundPattern = sprintf(
            '/\Afiles\/thumbnail\/background_%s_\d+\.(?:jpe?g|png|webp)\z/i',
            preg_quote((string) $user->id, '/')
        );

        if ($oldRelativePath
            && $oldRelativePath !== $relativePath
            && preg_match($ownedBackgroundPattern, $oldRelativePath)) {
            try {
                if ($disk->exists($oldRelativePath) && !$disk->delete($oldRelativePath)) {
                    $uploadLog->warning('The previous Hero background could not be deleted.', [
                        'user_id' => $user->id,
                        'path' => $oldRelativePath,
                    ]);
                }
            } catch (\Throwable $exception) {
                $uploadLog->error('Deleting the previous Hero background failed.', [
                    'user_id' => $user->id,
                    'path' => $oldRelativePath,
                    'exception' => $exception,
                ]);
            }
        }

        $imageUrl = asset('storage/' . $relativePath) . '?v=' . now()->timestamp;

        return response()->json([
            'success' => true,
            'message' => 'Background updated successfully.',
            'image_url' => $imageUrl,
            'image_path' => $relativePath,
            // Keep the existing response keys for older clients of this endpoint.
            'background_path' => $relativePath,
            'background_url' => $imageUrl,
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
