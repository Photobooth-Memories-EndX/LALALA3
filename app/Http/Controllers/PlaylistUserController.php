<?php

namespace App\Http\Controllers;

use App\Models\CertificateSetting;
use App\Models\Comment;
use App\Models\Content;
use App\Models\Assesment;
use App\Models\AssesmentResult;
use App\Models\ContentUser;
use App\Models\PlaylistContentUser;
use App\Models\TrainingPlan;
use App\Models\TrainingPlanDetail;
use App\Services\AssessmentAccessService;
use App\Services\CertificateEligibilityService;
use App\Services\Learning\LearningProgressResolver;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;

class PlaylistUserController extends Controller
{
    public function __construct(private LearningProgressResolver $resolver)
    {
    }

    /**
     * AJAX live autocomplete search for active published playlists.
     */
    public function autocomplete(Request $request): JsonResponse
    {
        $query = trim((string) $request->get('q', ''));
        if ($query === '') {
            return response()->json([]);
        }

        $keyword = '%' . $query . '%';

        $playlists = TrainingPlan::withCount([
                'trainingplandetail as total_contents' => function ($q) {
                    $q->whereHas('content', fn ($cq) => $cq->where('active', 'Active'));
                }
            ])
            ->where('active', 'Active')
            ->whereHas('trainingplandetail.content', fn ($q) => $q->where('active', 'Active'))
            ->where(function ($q) use ($keyword) {
                $q->where('title', 'like', $keyword)
                    ->orWhere('slug', 'like', $keyword)
                    ->orWhere('url_link', 'like', $keyword)
                    ->orWhereHas('trainingplandetail.content', fn ($cq) => $cq->where('title', 'like', $keyword));
            })
            ->latest('created_at')
            ->limit(10)
            ->get();

        $results = $playlists->map(function ($playlist) {
            return [
                'id'             => $playlist->id,
                'title'          => $playlist->title,
                'slug'           => $playlist->slug,
                'total_contents' => (int) ($playlist->total_contents ?? 0),
                'learning_hours' => $playlist->learning_hours,
            ];
        });

        return response()->json($results->values());
    }

    public function index()
    {
        $userId = Auth::id();

        // Global aggregate stats — safe to cache, no per-user data
        $totalPlaylists = Cache::remember('playlist_active_count', 300, function () {
            return TrainingPlan::where('active', 'Active')
                ->whereHas('trainingplandetail.content', function ($q) {
                    $q->where('active', 'Active');
                })
                ->count();
        });

        $totalContents = Cache::remember('playlist_active_content_count', 300, function () {
            return TrainingPlanDetail::whereHas('trainingplan', function ($q) {
                $q->where('active', 'Active');
            })->whereHas('content', function ($q) {
                $q->where('active', 'Active');
            })->count();
        });

        // trainingplan columns used: id, slug, title, total_duration_seconds,
        //                            url_link, active, created_at, thumbnail,
        //                            has_deadline, from_date, to_date
        // content columns:           id, title, slug, type, link, classification_id, duration, learning_hours, playlistonly
        $trainingplan = TrainingPlan::with([
                'trainingplandetail' => fn ($q) => $q
                    ->whereHas('content', fn ($cq) => $cq->where('active', 'Active'))
                    ->orderByRaw('CAST(content_order AS UNSIGNED) ASC')
                    ->select(['id', 'trainingplan_id', 'content_id', 'content_order']),
                'trainingplandetail.content' => fn ($q) => $q
                    ->where('active', 'Active')
                    ->select(['id', 'title', 'slug', 'type', 'link', 'classification_id', 'author', 'views', 'duration', 'learning_hours', 'active', 'playlistonly']),
                'trainingplandetail.content.classification' => fn ($q) => $q
                    ->select(['id', 'classification_name']),
            ])
            ->select(['id', 'title', 'slug', 'total_duration_seconds', 'learning_hours', 'url_link',
                      'active', 'created_at', 'thumbnail', 'has_deadline',
                      'from_date', 'to_date'])
            ->where('active', 'Active')
            ->whereHas('trainingplandetail.content', fn ($q) => $q->where('active', 'Active'))
            ->orderBy('created_at', 'desc')
            ->paginate(9);

        // Pre-compute per-card stats using LearningProgressResolver (authoritative PCU + Category A fallback)
        $playlistStats = [];
        foreach ($trainingplan as $tp) {
            $details      = $tp->trainingplandetail->filter(fn ($d) => !empty($d->content) && $d->content->exists && $d->content->active === 'Active');
            $totalContent = $details->count();
            $detailContentIds = $details->pluck('content_id');

            $resolvedBatch    = $this->resolver->resolvePlaylistBatch($userId, $tp->id, $detailContentIds);
            $completedContent = $resolvedBatch->filter(fn ($r) => $r->isFinished())->count();

            $firstContent = optional($details->first())->content;
            $activeDurationSeconds = $details->sum(function ($d) {
                return \App\Support\DurationFormatter::toSeconds($d->content->duration) ?? 0;
            });
            $activeLearningHours = app(\App\Services\LearningHours\LearningHourConverterService::class)->convertFromSeconds($activeDurationSeconds);

            $tp->total_duration_seconds = $activeDurationSeconds;
            $tp->learning_hours = $activeLearningHours;

            $playlistStats[$tp->id] = [
                'totalContent'           => $totalContent,
                'completedContent'       => $completedContent,
                'progress'               => TrainingPlan::calculateProgressPercentage($completedContent, $totalContent),
                'firstContent'           => $firstContent,
                'thumbnail'              => $this->getPlaylistThumbnail($tp, $firstContent),
                'has_deadline'           => $tp->has_deadline,
                'from_date'              => $tp->from_date,
                'to_date'                => $tp->to_date,
                'total_duration_seconds' => $activeDurationSeconds,
                'learning_hours'         => $activeLearningHours,
            ];
        }

        // Pre-compute featured playlist stats (first on page) for the "Continue Learning" card.
        $featuredStats    = null;
        $featuredPlaylist = $trainingplan->first();

        if ($featuredPlaylist) {
            $details      = $featuredPlaylist->trainingplandetail->filter(fn ($d) => !empty($d->content) && $d->content->exists && $d->content->active === 'Active');
            $totalContent = $details->count();
            $detailContentIds = $details->pluck('content_id');

            $featuredResolvedBatch = $this->resolver->resolvePlaylistBatch($userId, $featuredPlaylist->id, $detailContentIds);
            $completedContent      = $featuredResolvedBatch->filter(fn ($r) => $r->isFinished())->count();
            $nextContent           = null;

            foreach ($details as $detail) {
                $resolved = $featuredResolvedBatch->get($detail->content_id);
                $isCompleted = $resolved && $resolved->isFinished();

                if (!$nextContent && !$isCompleted) {
                    $nextContent = $detail->content;
                }
            }

            $featuredStats = [
                'totalContent'     => $totalContent,
                'completedContent' => $completedContent,
                'remainingContent' => max($totalContent - $completedContent, 0),
                'progress'         => TrainingPlan::calculateProgressPercentage($completedContent, $totalContent),
                'firstContent'     => optional($details->first())->content,
                'nextContent'      => $nextContent ?? optional($details->first())->content,
            ];
        }

        $playlistAdvancedFilterCount = collect([
            filled(request('author')) && request('author') !== 'all',
            filled(request('date_from')),
            filled(request('date_to')),
            filled(request('period_from')),
            filled(request('period_to')),
            request('sort') && !in_array(request('sort'), ['default', 'latest', 'all']),
            request('sort_views') && !in_array(request('sort_views'), ['default', 'all']),
            request('duration') && request('duration') !== 'all',
        ])->filter()->count();

        return view('user.playlist.index', [
            'title'                       => 'Playlist',
            'trainingplan'                => $trainingplan,
            'totalPlaylists'              => $totalPlaylists,
            'totalContents'               => $totalContents,
            'playlistStats'               => $playlistStats,
            'featuredStats'               => $featuredStats,
            'playlistAdvancedFilterCount' => $playlistAdvancedFilterCount,
        ]);
    }

    /**
     * Show/redirect to the first accessible video of the playlist, or back to playlist index.
     */
    public function show($slug)
    {
        $trainingplan = TrainingPlan::where('active', 'Active')
            ->where(function ($q) use ($slug) {
                $q->where('slug', $slug)
                  ->orWhere('id', $slug);
            })
            ->first();

        if (!$trainingplan) {
            return redirect()->route('playlist.user.index');
        }

        $firstDetail = $trainingplan->trainingplandetail()
            ->whereHas('content', fn ($q) => $q->where('active', 'Active'))
            ->orderByRaw('CAST(content_order AS UNSIGNED) ASC')
            ->with('content')
            ->first();

        if ($firstDetail && $firstDetail->content) {
            return redirect()->route('playlist.user.view', [
                'slug'    => $trainingplan->slug,
                'vidslug' => $firstDetail->content->slug,
            ]);
        }

        return redirect()->route('playlist.user.index');
    }

    public function view($slug, $vidslug, CertificateEligibilityService $certificateEligibility, AssessmentAccessService $assessmentAccessService)
    {
        $userId = Auth::id();

        $trainingplan = TrainingPlan::where('active', 'Active')->where('slug', $slug)->first();
        $content      = Content::where('active', 'Active')->where('slug', $vidslug)->first();

        abort_if(!$trainingplan || !$content, 404);

        if (!$trainingplan->isAccessibleByDeadline()) {
            return response()->view('user.learning.deadline-blocked', [
                'title'    => $trainingplan->title,
                'type'     => 'playlist',
                'status'   => $trainingplan->isExpired() ? 'expired' : 'not_started',
                'fromDate' => $trainingplan->from_date,
                'toDate'   => $trainingplan->to_date,
            ], 403);
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

        $content_all = TrainingPlanDetail::where('trainingplan_id', $trainingplan->id)
            ->with('content')
            ->orderByRaw('CAST(content_order AS UNSIGNED) ASC')
            ->get();

        abort_if(!$content_all->contains('content_id', $content->id), 404);

        $content->increment('views');

        // Resolve progress for current content via LearningProgressResolver
        $resolvedCurrent = $this->resolver->resolvePlaylist($userId, $trainingplan->id, $content->id);

        $content_user = PlaylistContentUser::firstOrCreate(
            [
                'user_id'         => $userId,
                'trainingplan_id' => $trainingplan->id,
                'content_id'      => $content->id,
            ],
            [
                'progress'         => $resolvedCurrent->progress,
                'finish'           => (string) $resolvedCurrent->finish,
                'learning_status'  => $resolvedCurrent->learningStatus,
                'current_time'     => $resolvedCurrent->currentTime ?? 0,
                'last_page'        => $resolvedCurrent->lastPage,
                'total_pages'      => $resolvedCurrent->totalPages,
                'duration_seconds' => $resolvedCurrent->durationSeconds,
                'status'           => $content->status ?? 'optional',
            ]
        );

        if ((empty($content_user->last_page) || (int) $content_user->last_page === 0) && !empty($resolvedCurrent->lastPage)) {
            $content_user->last_page = (int) $resolvedCurrent->lastPage;
        }

        // Recompute in-memory active duration and learning hours for the view
        $activeDurationSeconds = $content_all->filter(fn ($item) => $item->content?->active === 'Active')->sum(function ($item) {
            return \App\Support\DurationFormatter::toSeconds($item->content?->duration) ?? 0;
        });
        $activeLearningHours = app(\App\Services\LearningHours\LearningHourConverterService::class)->convertFromSeconds($activeDurationSeconds);
        $trainingplan->total_duration_seconds = $activeDurationSeconds;
        $trainingplan->learning_hours = $activeLearningHours;

        // Batch resolve all playlist items in one query (no N+1)
        $resolvedBatch = $this->resolver->resolvePlaylistBatch(
            $userId,
            $trainingplan->id,
            $content_all->pluck('content_id')
        );

        $progressByContentId = $resolvedBatch->map(fn ($r) => $r->progress);

        $completedContent = $resolvedBatch->filter(fn ($r) => $r->isFinished())->count();

        $totalContent = $content_all->count();

        $percentageProgressPlaylist = TrainingPlan::calculateProgressPercentage($completedContent, $totalContent);

        $playlistProgressComplete = $percentageProgressPlaylist >= 100;
        $playlistCertificateSetting = CertificateSetting::where('certificate_type', 'playlist')
            ->where('trainingplan_id', $trainingplan->id)
            ->where('is_active', 1)
            ->first();

        $contentAssessmentProgress = $certificateEligibility->contentAssessmentProgress(
            $content_all->pluck('content_id'),
            Auth::user(),
            $playlistCertificateSetting,
            (int) $trainingplan->id
        );
        $assessmentsByContent = $contentAssessmentProgress['assessmentsByContent'];

        $assesment             = Assesment::where('type', 'trainingplan')
            ->where('trainingplan_id', $trainingplan->id)
            ->where('status', 'OK')
            ->first();
        $hasPlaylistAssessment = $assesment !== null;

        // Load only the columns we display — no user/assesment relations needed
        $queryAssessmentResult = AssesmentResult::select(['id', 'user_id', 'assesment_id', 'score', 'pass', 'final', 'created_at'])
            ->where('user_id', $userId)
            ->where('assesment_id', $assesment->id ?? '');

        $assessmentCount        = $queryAssessmentResult->count();
        $assessmentLatestScores = (clone $queryAssessmentResult)->latest()->first()?->score ?? 0;

        // "Done" means the user has submitted a FINAL result (final = 1).
        // Must NOT be derived from $assessmentCount — that counts all attempts
        // including non-final in-progress drafts, which would unlock the certificate prematurely.
        $playlistAssessmentDone = false;
        if ($hasPlaylistAssessment) {
            $playlistAssessmentDone = AssesmentResult::where('user_id', $userId)
                ->where('assesment_id', $assesment->id)
                ->where('final', 1)
                ->exists();
        }

        $playlistCertificateEligibility = $playlistCertificateSetting
            ? $certificateEligibility->check($playlistCertificateSetting, Auth::user())
            : null;

        $assessmentContent = $assessmentsByContent->get($content->id);

        $queryAssessmentContentResult = AssesmentResult::select(['id', 'user_id', 'assesment_id', 'score', 'pass', 'final', 'created_at'])
            ->where('user_id', $userId)
            ->where('assesment_id', $assessmentContent->id ?? '');

        $assessmentContentCount        = $queryAssessmentContentResult->count();
        $assessmentContentLatestScores = (clone $queryAssessmentContentResult)->latest()->first()?->score ?? 0;

        $allAssessmentIds = collect([$assesment?->id])
            ->concat($assessmentsByContent->pluck('id'))
            ->filter()
            ->unique()
            ->values();

        $assessmentAttemptCounts = $allAssessmentIds->isNotEmpty()
            ? AssesmentResult::where('user_id', $userId)
                ->whereIn('assesment_id', $allAssessmentIds)
                ->groupBy('assesment_id')
                ->selectRaw('assesment_id, count(*) as total')
                ->pluck('total', 'assesment_id')
            : collect();

        // Comments: paginated root comments + 2 reply levels (was 4)
        $comment = Comment::with([
                'user',
                'likes',
                'likedByUser',

                'replies' => fn ($q) => $q->with([
                    'user',
                    'likes',
                    'likedByUser',

                    'replies' => fn ($q) => $q->with([
                        'user',
                        'likes',
                        'likedByUser',
                    ]),
                ]),
            ])
            ->withCount('replies')
            ->whereNull('parent_id')
            ->where('type', 'content')
            ->where('content_id', $content->id)
            ->latest()
            ->paginate(5)
            ->withQueryString();

        $totalComment = Comment::whereNull('parent_id')
            ->where('type', 'content')
            ->where('content_id', $content->id)
            ->count();

        // Resolve all content assessments associated with this playlist
        $playlistContentAssessments = Assesment::where('type', 'content')
            ->where('assasment_content_for', 'playlist')
            ->where('trainingplan_id', $trainingplan->id)
            ->whereIn('content_id', $content_all->pluck('content_id'))
            ->get()
            ->keyBy('content_id');

        $contentAssessmentStates = [];
        $authUser = Auth::user();

        foreach ($content_all as $item) {
            $cId = $item->content_id;
            $cAssessment = $playlistContentAssessments->get($cId);
            if (!$cAssessment) {
                continue;
            }

            $denialReason = $assessmentAccessService->denialReason($cAssessment, $authUser, null, (int) $trainingplan->id);

            $activeDraft = AssesmentResult::where('user_id', $userId)
                ->where('assesment_id', $cAssessment->id)
                ->where('batch', $cAssessment->batch)
                ->where('final', 0)
                ->latest('id')
                ->first();

            $finalAttempts = AssesmentResult::where('user_id', $userId)
                ->where('assesment_id', $cAssessment->id)
                ->where('batch', $cAssessment->batch)
                ->where('final', 1)
                ->count();

            $hasPassed = AssesmentResult::where('user_id', $userId)
                ->where('assesment_id', $cAssessment->id)
                ->where('batch', $cAssessment->batch)
                ->where('final', 1)
                ->where('pass', 1)
                ->exists();

            $isPassed = ($denialReason === AssessmentAccessService::REASON_PASSED) || $hasPassed;
            $isExpired = ($denialReason === AssessmentAccessService::REASON_EXPIRED);
            $isInactive = ($denialReason === AssessmentAccessService::REASON_INACTIVE);
            $isBeforeStart = ($denialReason === AssessmentAccessService::REASON_BEFORE_START);
            $isMaxAttempts = ($denialReason === AssessmentAccessService::REASON_MAX_ATTEMPTS);

            if ($isPassed) {
                $state = 'passed';
            } elseif ($isExpired) {
                $state = 'expired';
            } elseif ($isInactive) {
                $state = 'inactive';
            } elseif ($isBeforeStart) {
                $state = 'before_start';
            } elseif ($isMaxAttempts) {
                $state = 'max_attempts';
            } else {
                $state = 'active';
            }

            if ($activeDraft && !$isExpired) {
                $actionType = 'continue';
            } elseif ($finalAttempts > 0) {
                $actionType = 'remedial';
            } else {
                $actionType = 'start';
            }

            $answeredCount = 0;
            $totalQuestions = 0;
            if ($activeDraft) {
                $userAnswer = is_array($activeDraft->user_answer)
                    ? $activeDraft->user_answer
                    : json_decode($activeDraft->user_answer ?? '[]', true);
                $answeredCount = is_array($userAnswer)
                    ? count(array_filter($userAnswer, fn ($a) => !empty($a) || $a === '0' || $a === 0))
                    : 0;

                $qSnap = is_array($activeDraft->question_snapshot)
                    ? $activeDraft->question_snapshot
                    : json_decode($activeDraft->question_snapshot ?? '[]', true);
                $totalQuestions = is_array($qSnap) && count($qSnap) > 0
                    ? count($qSnap)
                    : $cAssessment->questions()->count();
            }

            $batchDisplay = !empty($cAssessment->batch) && $cAssessment->batch !== '-'
                ? (str_contains(strtolower((string) $cAssessment->batch), 'batch') ? $cAssessment->batch : 'Batch ' . $cAssessment->batch)
                : '-';
            $fromDateVal = !empty($cAssessment->fromdate) ? date('d M Y, H:i', strtotime($cAssessment->fromdate)) : '-';
            $toDateVal = !empty($cAssessment->todate) ? date('d M Y, H:i', strtotime($cAssessment->todate)) : '-';
            $passingGradeVal = $cAssessment->passing_grade !== null && $cAssessment->passing_grade !== '' ? $cAssessment->passing_grade : '-';
            $maxAttempts = (string) ($cAssessment->type ?? '') === 'global' ? 1 : (1 + (int) ($cAssessment->total_remedials ?? 0));
            $attemptsDisplay = $finalAttempts . ' / ' . $maxAttempts . ' Attempts';
            $duration = $cAssessment->time_limit_minutes ?? 60;

            $contentAssessmentStates[$cId] = [
                'assessment'      => $cAssessment,
                'assessmentId'    => $cAssessment->id,
                'title'           => $cAssessment->title,
                'slug'            => $cAssessment->slug,
                'state'           => $state,
                'actionType'      => $actionType,
                'isPassed'        => $isPassed,
                'isExpired'       => $isExpired,
                'isInactive'      => $isInactive,
                'isBeforeStart'   => $isBeforeStart,
                'isMaxAttempts'   => $isMaxAttempts,
                'activeDraft'     => $activeDraft,
                'finalAttempts'   => $finalAttempts,
                'maxAttempts'     => $maxAttempts,
                'attemptsDisplay' => $attemptsDisplay,
                'batchDisplay'    => $batchDisplay,
                'fromDateVal'     => $fromDateVal,
                'toDateVal'       => $toDateVal,
                'passingGrade'    => $passingGradeVal,
                'duration'        => $duration,
                'answeredCount'   => $answeredCount,
                'totalQuestions'  => $totalQuestions,
            ];
        }

        // Continue Learning Resolution:
        $currentProgress = $progressByContentId[$content->id] ?? 0;
        $isCurrentFinished = $currentProgress >= 100;

        $nextUnfinishedContent = null;
        foreach ($content_all as $item) {
            $itemContent = $item->content;
            if (!$itemContent || $itemContent->active !== 'Active') {
                continue;
            }
            $itemProgress = $progressByContentId[$item->content_id] ?? 0;
            if ($itemProgress < 100) {
                $nextUnfinishedContent = $itemContent;
                break;
            }
        }

        $continueLearningContent = !$isCurrentFinished ? $content : $nextUnfinishedContent;
        $isPlaylistFullyCompleted = $nextUnfinishedContent === null && $completedContent === $totalContent;

        return view('user.playlist.view', [
            'playlist'                       => $trainingplan,
            'content'                        => $content,
            'content_all'                    => $content_all,
            'content_user'                   => $content_user,
            'progressByContentId'            => $progressByContentId,
            'completedContent'               => $completedContent,
            'totalContent'                   => $totalContent,
            'assesment'                      => $assesment,
            'comment'                        => $comment,
            'totalComment'                   => $totalComment,
            'assessmentAccess'               => $playlistProgressComplete,
            'assessmentCount'                => $assessmentCount,
            'assessmentLatestScores'         => $assessmentLatestScores,
            'percentageProgressPlaylist'     => $percentageProgressPlaylist,
            'assessmentContent'              => $assessmentContent,
            'assessmentContentCount'         => $assessmentContentCount,
            'assessmentContentLatestScores'  => $assessmentContentLatestScores,
            'assessmentsByContent'            => $assessmentsByContent,
            'contentAssessmentTotal'         => $contentAssessmentProgress['total'],
            'contentAssessmentCompleted'     => $contentAssessmentProgress['completed'],
            'contentAssessmentPercentage'    => $contentAssessmentProgress['percentage'],
            'playlistProgressComplete'       => $playlistProgressComplete,
            'hasPlaylistAssessment'          => $hasPlaylistAssessment,
            'playlistAssessmentDone'         => $playlistAssessmentDone,
            'playlistCertificateSetting'     => $playlistCertificateSetting,
            'playlistCertificateEligibility' => $playlistCertificateEligibility,
            'assessmentAttemptCounts'        => $assessmentAttemptCounts,
            'contentAssessmentStates'        => $contentAssessmentStates,
            'continueLearningContent'        => $continueLearningContent,
            'isPlaylistFullyCompleted'       => $isPlaylistFullyCompleted,
        ]);
    }

    // ─── Private helpers ────────────────────────────────────────────────────────

    /**
     * Resolve the thumbnail URL for a playlist card.
     * Priority: trainingplan's own thumbnail → first content's thumbnail → default.
     *
     * The accessor (thumbnail_url) filters external/null values.
     * We additionally verify the file exists on disk before returning its URL,
     * so we never hand back a URL that would 404.
     */
    private function getPlaylistThumbnail(?object $trainingplan, ?object $content): string
    {
        if ($trainingplan) {
            $path = $trainingplan->thumbnail ?? null;
            if ($path
                && !Str::startsWith($path, ['http://', 'https://', '//'])
                && File::exists(public_path('storage/' . $path))) {
                return asset('storage/' . $path);
            }
        }

        if (!$content) {
            return asset('default-video.jpg');
        }

        if ($content->type === 'YouTube' && !empty($content->link)) {
            return 'http://img.youtube.com/vi/' . $content->link . '/mqdefault.jpg';
        }

        $path = 'storage/files/content/' . $content->id . '/thumb.jpg';
        if (File::exists(public_path($path)) && !is_dir(public_path($path))) {
            return asset($path);
        }

        return asset('default-video.jpg');
    }
}
