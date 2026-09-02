<?php

namespace App\Http\Controllers;

use App\Models\Assesment;
use App\Models\AssesmentResult;
use App\Models\News;
use App\Models\Content;
use App\Models\ContentUser;
use App\Models\EventPhoto;
use App\Models\TrainingPlan;
use App\Services\LearningAnalyticsService;
use App\Services\AssessmentAccessService;
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

        $upcomingDeadlines = Assesment::where('status', 'OK')
            ->whereNotNull('todate')
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

    public function removeHeroBackground(Request $request)
    {
        $user = $request->user();

        if ($user && $user->hero_background) {
            $disk = Storage::disk('public');
            $oldPath = $user->hero_background;

            $user->hero_background = null;
            $user->save();

            if ($oldPath && $disk->exists($oldPath)) {
                try {
                    $disk->delete($oldPath);
                } catch (\Throwable $e) {
                    Log::error('Failed deleting hero background on remove.', [
                        'user_id' => $user->id,
                        'path' => $oldPath,
                        'error' => $e->getMessage(),
                    ]);
                }
            }
        }

        $defaultUrl = asset('uploads/template/exhero-1600-320.png');

        return response()->json([
            'success' => true,
            'message' => 'Hero background reset to default template.',
            'image_url' => $defaultUrl,
            'is_default' => true,
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

    return Assesment::where('status', 'OK')
        ->whereNotNull('todate')
        ->whereDate('todate', '>=', now())
        ->whereIn('content_id', $currentContentIds)
        ->orderBy('todate', 'asc')
        ->take(5)
        ->get();
}

    public function accessAssessmentByUniqueCode(Request $request, AssessmentAccessService $assessmentAccess)
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
            $user = auth()->user();
            $currentUserId = $user ? $user->id : auth()->id();

            // Check for active unsubmitted draft (Snapshot detection)
            $activeDraft = AssesmentResult::where('user_id', $currentUserId)
                ->where('assesment_id', $assessment->id)
                ->where('batch', $assessment->batch)
                ->where('final', 0)
                ->latest('id')
                ->first();

            $hasSnapshot = $activeDraft && (!empty($activeDraft->user_answer) || !empty($activeDraft->question_snapshot));

            $answeredCount = ($activeDraft && is_array($activeDraft->user_answer))
                ? count(array_filter($activeDraft->user_answer, fn($v) => $v !== null && $v !== ''))
                : 0;

            $totalCount = ($activeDraft && is_array($activeDraft->question_snapshot) && count($activeDraft->question_snapshot) > 0)
                ? count($activeDraft->question_snapshot)
                : ($assessment->questions()->count() ?: 10);

            $completedResults = AssesmentResult::where('user_id', $currentUserId)
                ->where('assesment_id', $assessment->id)
                ->where('batch', $assessment->batch)
                ->where('final', 1)
                ->orderBy('id', 'asc')
                ->get();

            $userAttempts = $completedResults->count();
            $totalRemedials = (int) ($assessment->total_remedials ?? 0);
            $isGlobal = (string) ($assessment->type ?? '') === 'global';
            $maxAttempts = $isGlobal ? 1 : (1 + $totalRemedials);
            $passingGrade = (int) ($assessment->passing_grade ?? 0);

            $highestResult = $completedResults->sortByDesc('score')->first();
            $resultScore = $highestResult ? (int) ($highestResult->score ?? 0) : 0;
            $resultIsPassed = $highestResult ? (bool) ($highestResult->pass ?? ($resultScore >= $passingGrade)) : false;
            $resultCurrentAttempt = $completedResults->count();

            $showResult = false;
            $canStart = false;
            $actionType = 'start';
            $reason = $assessmentAccess->denialReason($assessment, $user);

            $assessmentStatus = 'active';
            $statusMessage = null;

            $isBypassUser = $user && in_array((int) ($user->role ?? $user->role_id ?? 0), [2, 3], true);

            if ($reason === AssessmentAccessService::REASON_INACTIVE) {
                $assessmentStatus = 'inactive';
                $statusMessage = 'This assessment is currently inactive and cannot be started.';
                $canStart = false;
                $showResult = false;
            } elseif ($reason === AssessmentAccessService::REASON_EXPIRED) {
                $assessmentStatus = 'expired';
                $statusMessage = 'This assessment has expired and can no longer be started.';
                $canStart = false;
                $showResult = false;
            } elseif ($hasSnapshot) {
                // User has active unsubmitted draft -> Continue test
                $canStart = true;
                $showResult = false;
                $actionType = 'continue';
            } elseif ($isBypassUser) {
                // Admin / Super Admin (Role 2 & 3): Unlimited QA re-attempts (Never blocked by previous completed results)
                $canStart = true;
                $showResult = false;
                $actionType = 'start';
            } elseif ($isGlobal && $userAttempts >= 1) {
                // Global assessment: 1 attempt per batch completed -> Show Result Modal
                $showResult = true;
                $canStart = false;
            } elseif (!$isGlobal && $completedResults->isNotEmpty() && ($resultIsPassed || $userAttempts >= $maxAttempts)) {
                // Content/Playlist: Passed or all attempts exhausted -> Show Result Modal with Highest Score
                $showResult = true;
                $canStart = false;
            } elseif (!$isGlobal && $completedResults->isNotEmpty() && !$resultIsPassed && $userAttempts < $maxAttempts) {
                // Content/Playlist: Failed previous attempt but has remedial available
                $canStart = ($reason === null);
                $showResult = false;
                $actionType = 'remedial';
            } else {
                // First attempt / not started yet
                $canStart = ($reason === null);
                $showResult = false;
                $actionType = 'start';
            }

            $fromDateDisplay = !empty($assessment->fromdate)
                ? date('d M Y, H:i', strtotime($assessment->fromdate))
                : null;
            $toDateDisplay = !empty($assessment->todate)
                ? date('d M Y, H:i', strtotime($assessment->todate))
                : null;

            $periodDisplay = '-';
            if ($fromDateDisplay && $toDateDisplay) {
                $periodDisplay = $fromDateDisplay . ' - ' . $toDateDisplay;
            } elseif ($fromDateDisplay) {
                $periodDisplay = 'From ' . $fromDateDisplay;
            } elseif ($toDateDisplay) {
                $periodDisplay = 'Until ' . $toDateDisplay;
            }

            $typeLabel = match (strtolower((string) ($assessment->type ?? ''))) {
                'trainingplan', 'playlist' => 'Playlist Assessment',
                'youtube'                  => 'MOOC Assessment',
                'global'                   => 'Global Assessment',
                default                    => ucwords(str_replace('_', ' ', $assessment->type ?? '-')) . ' Assessment',
            };

            return response()->json([
                'status' => $assessmentStatus,
                'assessment_status' => $assessmentStatus,
                'is_active' => $assessmentStatus === 'active',
                'is_inactive' => $assessmentStatus === 'inactive',
                'is_expired' => $assessmentStatus === 'expired',
                'status_message' => $statusMessage,
                'can_start' => $canStart,
                'show_result' => $showResult,
                'denial_reason' => $reason,
                'assessment_id' => $assessment->id,
                'assessment_slug' => $assessment->slug,
                'assessment_title' => $assessment->title ?? '-',
                'assessment_type' => $assessment->type ?? 'content',
                'assessment_type_label' => $typeLabel,
                'assessment_batch' => $assessment->batch ?? '-',
                'assessment_from_date' => $fromDateDisplay ?? '-',
                'assessment_to_date' => $toDateDisplay ?? '-',
                'assessment_period' => $periodDisplay,
                'assessment_passing_grade' => $assessment->passing_grade ?? '-',
                'assessment_duration' => $assessment->duration ?? ($assessment->time_limit_minutes ?? 60),
                'assessment_user_attempts' => $userAttempts,
                'assessment_max_attempts' => $maxAttempts,
                'assessment_score' => $resultScore,
                'assessment_is_passed' => $resultIsPassed,
                'assessment_current_attempt' => $resultCurrentAttempt,
                'has_snapshot' => $hasSnapshot,
                'action_type' => $actionType,
                'answered_count' => $answeredCount,
                'total_count' => $totalCount,
                'assessment_url' => route('assesment.user.view', $assessment->slug, false),
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
