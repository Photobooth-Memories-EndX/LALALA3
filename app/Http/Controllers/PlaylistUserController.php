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
use App\Services\CertificateEligibilityService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;

class PlaylistUserController extends Controller
{
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
        // content columns:                 id, title, slug, type, link, classification_id, duration, learning_hours
        // playlist_content_users columns:  id, user_id, trainingplan_id, content_id, progress, finish
        $trainingplan = TrainingPlan::with([
                'trainingplandetail' => fn ($q) => $q
                    ->whereHas('content', fn ($cq) => $cq->where('active', 'Active'))
                    ->orderByRaw('CAST(content_order AS UNSIGNED) ASC')
                    ->select(['id', 'trainingplan_id', 'content_id', 'content_order']),
                'trainingplandetail.content' => fn ($q) => $q
                    ->where('active', 'Active')
                    ->select(['id', 'title', 'slug', 'type', 'link', 'classification_id', 'author', 'views', 'duration', 'learning_hours', 'active']),
                'trainingplandetail.content.classification' => fn ($q) => $q
                    ->select(['id', 'classification_name']),
                'playlistContentUsers' => fn ($q) => $q
                    ->where('user_id', $userId)
                    ->select(['id', 'user_id', 'trainingplan_id', 'content_id', 'progress', 'finish']),
            ])
            ->select(['id', 'title', 'slug', 'total_duration_seconds', 'learning_hours', 'url_link',
                      'active', 'created_at', 'thumbnail', 'has_deadline',
                      'from_date', 'to_date'])
            ->where('active', 'Active')
            ->whereHas('trainingplandetail.content', fn ($q) => $q->where('active', 'Active'))
            ->orderBy('created_at', 'desc')
            ->paginate(9);

        // Pre-compute per-card stats from eager-loaded data (no extra DB queries).
        // Also computes the thumbnail URL — moves File::exists() out of Blade.
        $playlistStats = [];
        foreach ($trainingplan as $tp) {
            $details      = $tp->trainingplandetail->filter(fn ($d) => !empty($d->content) && $d->content->exists && $d->content->active === 'Active');
            $totalContent = $details->count();
            $completedContent = 0;

            foreach ($details as $detail) {
                $pcu = $tp->playlistContentUsers->firstWhere('content_id', $detail->content_id);
                if ($pcu && ((int) ($pcu->finish ?? 0) === 1 || (int) ($pcu->progress ?? 0) >= 100)) {
                    $completedContent++;
                }
            }

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
        // Includes nextContent (first unfinished chapter) which per-card stats don't need.
        $featuredStats    = null;
        $featuredPlaylist = $trainingplan->first();

        if ($featuredPlaylist) {
            $details      = $featuredPlaylist->trainingplandetail->filter(fn ($d) => !empty($d->content) && $d->content->exists && $d->content->active === 'Active');
            $totalContent = $details->count();
            $completedContent = 0;
            $nextContent      = null;

            foreach ($details as $detail) {
                $pcu = $featuredPlaylist->playlistContentUsers->firstWhere('content_id', $detail->content_id);
                $isCompleted = $pcu && ((int) ($pcu->finish ?? 0) === 1 || (int) ($pcu->progress ?? 0) >= 100);

                if ($isCompleted) {
                    $completedContent++;
                }

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

    public function view($slug, $vidslug, CertificateEligibilityService $certificateEligibility)
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

        $content_user = PlaylistContentUser::firstOrCreate([
            'user_id'         => $userId,
            'trainingplan_id' => $trainingplan->id,
            'content_id'      => $content->id,
        ]);

        // Recompute in-memory active duration and learning hours for the view
        $activeDurationSeconds = $content_all->filter(fn ($item) => $item->content?->active === 'Active')->sum(function ($item) {
            return \App\Support\DurationFormatter::toSeconds($item->content?->duration) ?? 0;
        });
        $activeLearningHours = app(\App\Services\LearningHours\LearningHourConverterService::class)->convertFromSeconds($activeDurationSeconds);
        $trainingplan->total_duration_seconds = $activeDurationSeconds;
        $trainingplan->learning_hours = $activeLearningHours;

        $playlistContentUsers = PlaylistContentUser::where('user_id', $userId)
            ->where('trainingplan_id', $trainingplan->id)
            ->whereIn('content_id', $content_all->pluck('content_id'))
            ->get(['content_id', 'progress', 'finish']);

        $progressByContentId = $playlistContentUsers->pluck('progress', 'content_id');

        $completedContent = 0;
        foreach ($content_all as $item) {
            $pcu = $playlistContentUsers->firstWhere('content_id', $item->content_id);
            if ($pcu && ((int) ($pcu->finish ?? 0) === 1 || (int) ($pcu->progress ?? 0) >= 100)) {
                $completedContent++;
            }
        }

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
            $playlistCertificateSetting
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
