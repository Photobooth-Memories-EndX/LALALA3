<?php

namespace App\Http\Controllers;

use App\Models\Assesment;
use App\Models\AssesmentResult;
use App\Models\Classification;
use App\Models\Comment;
use App\Models\Content;
use App\Models\ContentUser;
use App\Services\LearningAnalyticsService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ContentUserController extends Controller
{

    public function index(Request $request, LearningAnalyticsService $analyticsService)
    {
        $filterSearch = $request->s;
        $filterType = $request->type;
        $filterClassification = $request->classification;
        $filterDateOrder = $request->date_order ?? 'newest';
        $filterAuthor = $request->author;
        $filterViewOrder = $request->view_order;
        $filterSort = $request->filled('sort') ? $request->sort
            : null;
        $filterSort = $filterSort
            ?? ($filterDateOrder === 'oldest' ? 'oldest_course' : 'newest_course');
        $filterDeadlineStatus = $request->deadline_status ?? 'all';

        // Keep the legacy from_date/to_date parameters as aliases for the
        // original created-date filter so previously shared URLs still work.
        $filterCreatedFromDate = $request->filled('created_from_date')
            ? $request->created_from_date
            : $request->from_date;
        $filterCreatedToDate = $request->filled('created_to_date')
            ? $request->created_to_date
            : $request->to_date;
        $filterDeadlineFromDate = $request->deadline_from_date;
        $filterDeadlineToDate = $request->deadline_to_date;
        $filterUpdatedFromDate = $request->updated_from_date;
        $filterUpdatedToDate = $request->updated_to_date;
        $today = Carbon::today();
        $todayDate = $today->toDateString();
        $sevenDaysFromToday = $today->copy()->addDays(7)->toDateString();
        $thirtyDaysFromToday = $today->copy()->addDays(30)->toDateString();

        $classifications = Classification::select('id', 'classification_name', 'slug')
            ->orderBy('classification_name', 'asc')
            ->get()
            ->unique('classification_name')
            ->values();

        $authors = Content::select('author')
            ->whereNotNull('author')
            ->where('author', '!=', '')
            ->distinct()
            ->orderBy('author', 'asc')
            ->pluck('author');

        $queryContent = Content::with('classification')
            ->where('playlistonly', 'N')
            ->where('active', 'Active');

        if ($filterSearch) {
            $queryContent->where(function ($query) use ($filterSearch) {
                $query->where('title', 'like', '%' . $filterSearch . '%')
                    ->orWhere('description', 'like', '%' . $filterSearch . '%')
                    ->orWhere('author', 'like', '%' . $filterSearch . '%');
            });
        }

        if ($filterType) {
            $queryContent->where('type', $filterType);
        }

        if ($filterClassification) {
            $queryContent->where('classification_id', $filterClassification);
        }

        if ($filterAuthor) {
            $queryContent->where('author', $filterAuthor);
        }

        if (filled($filterDeadlineFromDate) && filled($filterDeadlineToDate)) {
            $queryContent->whereBetween('content.date', [
                $filterDeadlineFromDate,
                $filterDeadlineToDate,
            ]);
        } elseif (filled($filterDeadlineFromDate)) {
            $queryContent->whereDate('content.date', '>=', $filterDeadlineFromDate);
        } elseif (filled($filterDeadlineToDate)) {
            $queryContent->whereDate('content.date', '<=', $filterDeadlineToDate);
        }

        if (filled($filterCreatedFromDate)) {
            $queryContent->whereDate('content.created_at', '>=', $filterCreatedFromDate);
        }

        if (filled($filterCreatedToDate)) {
            $queryContent->whereDate('content.created_at', '<=', $filterCreatedToDate);
        }

        if (filled($filterUpdatedFromDate)) {
            $queryContent->whereDate('content.updated_at', '>=', $filterUpdatedFromDate);
        }

        if (filled($filterUpdatedToDate)) {
            $queryContent->whereDate('content.updated_at', '<=', $filterUpdatedToDate);
        }

        if ($request->filled('month')) {
            $queryContent->whereMonth('content.created_at', (int) $request->month);
            $queryContent->whereYear('content.created_at', (int) ($request->year ?: $today->year));
        } elseif ($request->filled('year')) {
            $queryContent->whereYear('content.created_at', (int) $request->year);
        }

        switch ($filterDeadlineStatus) {
            case 'expired':
                $queryContent->whereDate('content.date', '<', $todayDate);
                break;
            case 'today':
                $queryContent->whereDate('content.date', $todayDate);
                break;
            case 'less_than_7_days':
                $queryContent->whereDate('content.date', '>', $todayDate)
                    ->whereDate('content.date', '<=', $sevenDaysFromToday);
                break;
            case 'less_than_30_days':
                $queryContent->whereDate('content.date', '>', $todayDate)
                    ->whereDate('content.date', '<=', $thirtyDaysFromToday);
                break;
            case 'more_than_30_days':
                $queryContent->whereDate('content.date', '>', $thirtyDaysFromToday);
                break;
        }

        $durationRange = match ($request->get('duration', 'all')) {
            'lt10' => [null, 600, false, true],
            '10-30' => [600, 1800, true, false],
            '30-60' => [1800, 3600, false, false],
            'gt60' => [3600, null, false, false],
            default => null,
        };

        if ($durationRange !== null) {
            // Supports both legacy mm:ss and normalized HH:mm:ss values.
            $durationInSeconds = "CASE
                WHEN content.duration IS NULL OR TRIM(content.duration) = '' THEN NULL
                WHEN LENGTH(content.duration) - LENGTH(REPLACE(content.duration, ':', '')) = 1 THEN
                    CAST(SUBSTRING_INDEX(content.duration, ':', 1) AS UNSIGNED) * 60
                    + CAST(SUBSTRING_INDEX(content.duration, ':', -1) AS UNSIGNED)
                WHEN LENGTH(content.duration) - LENGTH(REPLACE(content.duration, ':', '')) = 2 THEN
                    CAST(SUBSTRING_INDEX(content.duration, ':', 1) AS UNSIGNED) * 3600
                    + CAST(SUBSTRING_INDEX(SUBSTRING_INDEX(content.duration, ':', 2), ':', -1) AS UNSIGNED) * 60
                    + CAST(SUBSTRING_INDEX(content.duration, ':', -1) AS UNSIGNED)
                ELSE NULL
            END";

            [$minimum, $maximum, $includeMinimum, $excludeMaximum] = $durationRange;

            if ($minimum !== null) {
                $queryContent->whereRaw(
                    $durationInSeconds . ($includeMinimum ? ' >= ?' : ' > ?'),
                    [$minimum]
                );
            }

            if ($maximum !== null) {
                $queryContent->whereRaw(
                    $durationInSeconds . ($excludeMaximum ? ' < ?' : ' <= ?'),
                    [$maximum]
                );
            }
        }

        if ($filterViewOrder === 'most_viewed') {
            $queryContent->orderBy('content.views', 'desc');
        } elseif ($filterViewOrder === 'least_viewed') {
            $queryContent->orderBy('content.views', 'asc');
        }

        switch ($filterSort) {
            case 'oldest_course':
                $queryContent->orderBy('content.created_at', 'asc');
                break;
            case 'recently_updated':
                $queryContent->orderBy('content.updated_at', 'desc');
                break;
            case 'old_update':
                $queryContent->orderBy('content.updated_at', 'asc');
                break;
            default:
                $queryContent->orderBy('content.created_at', 'desc');
                break;
        }

        $queryContent->orderBy('content.id', 'desc');

        $contents = $queryContent->paginate(12)->appends($request->query());
        $heroSummary = $analyticsService->heroSummary((int) Auth::id());

        return view('user.content.index', array_merge(compact(
            'contents',
            'classifications',
            'authors',
            'filterSearch',
            'filterType',
            'filterClassification',
            'filterAuthor',
            'filterViewOrder',
            'filterSort',
            'filterDeadlineStatus',
            'filterDeadlineFromDate',
            'filterDeadlineToDate',
            'filterCreatedFromDate',
            'filterCreatedToDate',
            'filterUpdatedFromDate',
            'filterUpdatedToDate'
        ), $heroSummary));
    }

        public function view($slug)
    {
        $content = Content::findBySlug($slug);

        if ($content === null) {
            abort(404);
        }

        if (!$content->isAccessibleByDeadline()) {
            return response()->view('user.learning.deadline-blocked', [
                'title'    => $content->title,
                'type'     => 'content',
                'status'   => $content->isExpired() ? 'expired' : 'not_started',
                'fromDate' => $content->from_date,
                'toDate'   => $content->to_date,
            ], 403);
        }

        $content->views += 1;
        $content->save();

        // add content to user learning on going if not exist yet
        $content_user = ContentUser::firstOrCreate([
            'user_id' => auth()->user()->id,
            'content_id' => $content->id
        ]);

        $queryAssessment = Assesment::query()->where('type', 'content');

        $assesment = $queryAssessment
            ->where('content_id', $content->id)
            ->first();

        if (isset($assesment)) {
            $queryAssessmentResult = AssesmentResult::with('user', 'assesment')
                ->where('user_id', Auth::user()->id)
                ->where('assesment_id', $assesment->id);
        } else {
            $queryAssessmentResult = AssesmentResult::with('user', 'assesment')
                ->where('user_id', Auth::user()->id);
        }

        $assessmentCount = $queryAssessmentResult
            ->where('final', 1)
            ->count();

        $assessmentLatestScores = $queryAssessmentResult
            ->latest()
            ->first()
            ->score ?? 0;

        // comment pagination + reply + like
        $comment = Comment::with([
                'user',
                'replies.user',
                'replies.likes',
                'replies.likedByUser',
                'likes',
                'likedByUser'
            ])
            ->withCount('replies')
            ->whereNull('parent_id')
            ->where('type', 'content')
            ->where('content_id', $content->id)
            ->latest()
            ->paginate(5)->appends(request()->query());

        $discussionCount = Comment::query()
            ->where('type', 'content')
            ->where('content_id', $content->id)
            ->count();

        $bookmarkCount = $content->bookmarkedByUsers()->count();
        $isBookmarked = auth()->user()->bookmarkedContents()
            ->where('content_id', $content->id)
            ->exists();

        return view('user.content.view', compact(
            'content',
            'assesment',
            'assessmentCount',
            'assessmentLatestScores',
            'comment',
            'content_user',
            'discussionCount',
            'bookmarkCount',
            'isBookmarked'
        ));
    }

    public function toggleBookmark($slug)
    {
        $content = Content::findBySlug($slug);

        if ($content === null) {
            abort(404);
        }

        $user = auth()->user();
        $isBookmarked = $user->bookmarkedContents()
            ->where('content_id', $content->id)
            ->exists();

        if ($isBookmarked) {
            $user->bookmarkedContents()->detach($content->id);

            return back()
                ->with('success', 'Content removed from bookmarks.')
                ->withFragment('comment');
        }

        $user->bookmarkedContents()->attach($content->id);

        return back()
            ->with('success', 'Content saved to bookmarks.')
            ->withFragment('comment');
    }

    /**
     * Auto learning progress endpoint (MVP).
     *
     * Receives raw playback/reading position from the frontend and recomputes
     * progress server-side — the client-sent progress value is never trusted.
     * The old PUT progress/{slug} route stays as the manual/fallback flow.
     */
    public function updateProgress(Request $request)
    {
        $validated = $request->validate([
            'content_id'       => 'required|integer|exists:content,id',
            'type'             => 'required|in:document,video',
            'last_page'        => 'nullable|integer|min:1',
            'total_pages'      => 'nullable|integer|min:1',
            'current_time'     => 'nullable|numeric|min:0',
            'duration_seconds' => 'nullable|numeric|min:0',
            'status'           => 'nullable|in:in_progress,paused,completed',
        ]);

        $contentUser = ContentUser::firstOrCreate([
            'user_id'    => auth()->id(),
            'content_id' => $validated['content_id'],
        ]);

        $previousProgress = (int) $contentUser->progress;
        $computed = null;

        if ($validated['type'] === 'document') {
            $totalPages = (int) ($validated['total_pages'] ?? $contentUser->total_pages ?? 0);
            $lastPage   = (int) ($validated['last_page'] ?? 0);

            if ($totalPages > 0) {
                $contentUser->total_pages = $totalPages;
            }

            if ($lastPage > 0) {
                $contentUser->last_page = $totalPages > 0 ? min($lastPage, $totalPages) : $lastPage;

                if ($totalPages > 0) {
                    $computed = ($contentUser->last_page / $totalPages) * 100;
                }
            }
        } else {
            $duration    = (float) ($validated['duration_seconds'] ?? $contentUser->duration_seconds ?? 0);
            $currentTime = $validated['current_time'] ?? null;

            if ($duration > 0) {
                $contentUser->duration_seconds = (int) round($duration);
            }

            if ($currentTime !== null) {
                $currentTime = (float) $currentTime;

                if ($duration > 0) {
                    $currentTime = min($currentTime, $duration);
                    $computed = ($currentTime / $duration) * 100;
                }

                $contentUser->current_time = (int) round($currentTime);
            }
        }

        // Progress never decreases (rewinding/re-reading keeps the highest reached).
        $progress = $previousProgress;

        if ($computed !== null) {
            $progress = max($previousProgress, max(0, min(100, (int) round($computed))));
        }

        // "completed" from the client (video ended) is only honored when the
        // recomputed position actually backs it up.
        if (($validated['status'] ?? null) === 'completed' && $computed !== null && $computed >= 90) {
            $progress = 100;
        }

        $contentUser->progress = $progress;

        if ($progress >= 100) {
            $contentUser->finish = '1';
            $contentUser->learning_status = 'completed';
        } elseif (($validated['status'] ?? null) === 'paused') {
            $contentUser->learning_status = 'paused';
        } elseif (($validated['status'] ?? null) === 'in_progress' || $computed !== null) {
            $contentUser->learning_status = 'in_progress';
        }

        $contentUser->save();

        return response()->json([
            'success'      => true,
            'progress'     => (int) $contentUser->progress,
            'status'       => $contentUser->learning_status,
            'finish'       => (int) $contentUser->finish,
            'current_time' => (int) $contentUser->current_time,
            'last_page'    => $contentUser->last_page,
        ]);
    }

    public function progress(Request $request, $slug)
    {
        $content = Content::findBySlug($slug);
        if ($content === null) {
            abort(404);
        }
        $content_user = ContentUser::query()->where('user_id', auth()->user()->id)->where('content_id', $content->id)->first();

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
