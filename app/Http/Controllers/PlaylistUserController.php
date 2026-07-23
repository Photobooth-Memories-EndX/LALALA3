<?php

namespace App\Http\Controllers;

use App\Models\CertificateSetting;
use App\Models\Comment;
use App\Models\Content;
use App\Models\Assesment;
use App\Models\AssesmentResult;
use App\Models\ContentUser;
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
            return TrainingPlan::where('active', 'Active')->count();
        });

        $totalContents = Cache::remember('playlist_active_content_count', 300, function () {
            return TrainingPlanDetail::whereHas('trainingplan', function ($q) {
                $q->where('active', 'Active');
            })->count();
        });

        // trainingplan columns used: id, slug, title, url_link, active, created_at,
        //                            thumbnail, has_deadline, from_date, to_date
        // content columns:      id, title, slug, type, link
        // content_users columns: id, user_id, content_id, progress, finish
        $trainingplan = TrainingPlan::with([
                'trainingplandetail' => fn ($q) => $q
                    ->orderBy('content_order')
                    ->select(['id', 'trainingplan_id', 'content_id', 'content_order']),
                'trainingplandetail.content' => fn ($q) => $q
                    ->select(['id', 'title', 'slug', 'type', 'link']),
                'trainingplandetail.content.contentuser' => fn ($q) => $q
                    ->where('user_id', $userId)
                    ->select(['id', 'user_id', 'content_id', 'progress', 'finish']),
            ])
            ->select(['id', 'title', 'slug', 'url_link', 'active', 'created_at',
                      'thumbnail', 'has_deadline', 'from_date', 'to_date'])
            ->where('active', 'Active')
            ->orderBy('created_at', 'desc')
            ->paginate(9);

        // Pre-compute per-card stats from eager-loaded data (no extra DB queries).
        // Also computes the thumbnail URL — moves File::exists() out of Blade.
        $playlistStats = [];
        foreach ($trainingplan as $tp) {
            $details      = $tp->trainingplandetail->filter(fn ($d) => !empty($d->content));
            $totalContent = $details->count();
            $completedContent = 0;

            foreach ($details as $detail) {
                $cu = $detail->content->contentuser->first(); // already filtered to $userId
                if ($cu && (int) ($cu->finish ?? 0) === 1) {
                    $completedContent++;
                }
            }

            $firstContent = optional($details->first())->content;

            $playlistStats[$tp->id] = [
                'totalContent'     => $totalContent,
                'completedContent' => $completedContent,
                'progress'         => $totalContent > 0
                    ? round(($completedContent / $totalContent) * 100)
                    : 0,
                'firstContent' => $firstContent,
                'thumbnail'    => $this->getPlaylistThumbnail($tp, $firstContent),
                'has_deadline' => $tp->has_deadline,
                'from_date'    => $tp->from_date,
                'to_date'      => $tp->to_date,
            ];
        }

        // Pre-compute featured playlist stats (first on page) for the "Continue Learning" card.
        // Includes nextContent (first unfinished chapter) which per-card stats don't need.
        $featuredStats    = null;
        $featuredPlaylist = $trainingplan->first();

        if ($featuredPlaylist) {
            $details      = $featuredPlaylist->trainingplandetail->filter(fn ($d) => !empty($d->content));
            $totalContent = $details->count();
            $completedContent = 0;
            $totalProgress    = 0;
            $nextContent      = null;

            foreach ($details as $detail) {
                $cu = $detail->content->contentuser->first();
                $p  = (int) ($cu->progress ?? 0);
                $totalProgress += $p;

                if ($cu && (int) ($cu->finish ?? 0) === 1) {
                    $completedContent++;
                }

                if (!$nextContent && (!$cu || (int) ($cu->finish ?? 0) === 0)) {
                    $nextContent = $detail->content;
                }
            }

            $featuredStats = [
                'totalContent'     => $totalContent,
                'completedContent' => $completedContent,
                'remainingContent' => max($totalContent - $completedContent, 0),
                'progress'         => $totalContent > 0 ? round($totalProgress / $totalContent) : 0,
                'firstContent'     => optional($details->first())->content,
                'nextContent'      => $nextContent ?? optional($details->first())->content,
            ];
        }

        return view('user.playlist.index', [
            'title'          => 'Playlist',
            'trainingplan'   => $trainingplan,
            'totalPlaylists' => $totalPlaylists,
            'totalContents'  => $totalContents,
            'playlistStats'  => $playlistStats,
            'featuredStats'  => $featuredStats,
        ]);
    }

    public function view($slug, $vidslug, CertificateEligibilityService $certificateEligibility)
    {
        $userId = Auth::id();

        $trainingplan = TrainingPlan::findBySlug($slug);
        $content      = Content::findBySlug($vidslug);

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

        $content->increment('views');

        $content_user = ContentUser::firstOrCreate([
            'user_id'    => $userId,
            'content_id' => $content->id,
        ]);

        $content_all = TrainingPlanDetail::where('trainingplan_id', $trainingplan->id)
            ->with('content')
            ->orderBy('content_order')
            ->get();

        // Single query replaces the N+1 in view-playlist-sidebar.blade.php
        $progressByContentId = ContentUser::where('user_id', $userId)
            ->whereIn('content_id', $content_all->pluck('content_id'))
            ->pluck('progress', 'content_id');

        $progressContent = $progressByContentId->sum();
        $totalContent    = $content_all->count();

        $percentageProgressPlaylist = $totalContent > 0
            ? $progressContent / $totalContent
            : 0;

        $playlistProgressComplete = $percentageProgressPlaylist >= 100;

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

        $playlistCertificateSetting = CertificateSetting::where('certificate_type', 'playlist')
            ->where('trainingplan_id', $trainingplan->id)
            ->where('is_active', 1)
            ->first();

        $playlistCertificateEligibility = $playlistCertificateSetting
            ? $certificateEligibility->check($playlistCertificateSetting, Auth::user())
            : null;

        $assessmentContent = Assesment::where('type', 'content')
            ->where('content_id', $content->id)
            ->first();

        $queryAssessmentContentResult = AssesmentResult::select(['id', 'user_id', 'assesment_id', 'score', 'pass', 'final', 'created_at'])
            ->where('user_id', $userId)
            ->where('assesment_id', $assessmentContent->id ?? '');

        $assessmentContentCount        = $queryAssessmentContentResult->count();
        $assessmentContentLatestScores = (clone $queryAssessmentContentResult)->latest()->first()?->score ?? 0;

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
            'playlistProgressComplete'       => $playlistProgressComplete,
            'hasPlaylistAssessment'          => $hasPlaylistAssessment,
            'playlistAssessmentDone'         => $playlistAssessmentDone,
            'playlistCertificateSetting'     => $playlistCertificateSetting,
            'playlistCertificateEligibility' => $playlistCertificateEligibility,
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
