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
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class PlaylistUserController extends Controller
{
    public function index()
{
    $trainingplan = TrainingPlan::with([
            'trainingplandetail' => function ($query) {
                $query->orderBy('content_order');
            },
            'trainingplandetail.content.contentuser',
        ])
        ->where('active', 'Active')
        ->orderBy('created_at', 'desc')
        ->paginate(9);

    $allTrainingplan = TrainingPlan::with([
            'trainingplandetail.content.contentuser',
        ])
        ->where('active', 'Active')
        ->get();

    return view('user.playlist.index', [
        'title' => 'Playlist',
        'trainingplan' => $trainingplan,
        'allTrainingplan' => $allTrainingplan,
    ]);
}

    public function view($slug, $vidslug)
{
    $userId = Auth::id();

    $trainingplan = TrainingPlan::findBySlug($slug);
    $content = Content::findBySlug($vidslug);

    abort_if(!$trainingplan || !$content, 404);

    $content->increment('views');

    // Add content to user learning on going if not exist yet
    $content_user = ContentUser::firstOrCreate([
        'user_id' => $userId,
        'content_id' => $content->id,
    ]);

    $content_user_all = ContentUser::where('user_id', $userId)->get();

    $content_all = TrainingPlanDetail::where('trainingplan_id', $trainingplan->id)
        ->with('content')
        ->orderBy('content_order')
        ->get();

    // Ambil semua progress user sekali saja, agar tidak N+1 query
    $progressByContentId = ContentUser::where('user_id', $userId)
        ->whereIn('content_id', $content_all->pluck('content_id'))
        ->pluck('progress', 'content_id');

    $progressContent = 0;

    foreach ($content_all as $ca) {
        $progressContent += $progressByContentId[$ca->content_id] ?? 0;
    }

    $totalContent = $content_all->count();

    $percentageProgressPlaylist = $totalContent > 0
        ? $progressContent / $totalContent
        : 0;

    $playlistProgressComplete = $percentageProgressPlaylist >= 100;

    $assesment = Assesment::where('type', 'trainingplan')
        ->where('trainingplan_id', $trainingplan->id)
        ->first();

    $hasPlaylistAssessment = $assesment !== null;

    // Batasi komentar yang tampil agar halaman tidak berat
    $comment = Comment::with([
            'user',
            'likes',
            'likedByUser',

            'replies.user',
            'replies.likes',
            'replies.likedByUser',

            'replies.replies.user',
            'replies.replies.likes',
            'replies.replies.likedByUser',

            'replies.replies.replies.user',
            'replies.replies.replies.likes',
            'replies.replies.replies.likedByUser',
        ])
        ->withCount('replies')
        ->whereNull('parent_id')
        ->where('type', 'content')
        ->where('content_id', $content->id)
        ->latest()
        ->paginate(5)
        ->withQueryString();

    // Total semua komentar untuk angka count
    $totalComment = Comment::whereNull('parent_id')
        ->where('type', 'content')
        ->where('content_id', $content->id)
        ->count();

    $queryAssessmentResult = AssesmentResult::with('user', 'assesment')
        ->where('user_id', $userId)
        ->where('assesment_id', $assesment->id ?? '');

    $assessmentCount = $queryAssessmentResult->count();

    $assessmentLatestScores = (clone $queryAssessmentResult)
        ->latest()
        ->first()
        ->score ?? 0;

    $playlistAssessmentDone = false;
    if ($hasPlaylistAssessment) {
        $playlistAssessmentDone = AssesmentResult::where('user_id', $userId)
            ->where('assesment_id', $assesment->id)
            ->exists();
    }

    $needsPlaylistAssessment = $playlistProgressComplete && $hasPlaylistAssessment && !$playlistAssessmentDone;
    $canGeneratePlaylistCertificate = $playlistProgressComplete && (!$hasPlaylistAssessment || $playlistAssessmentDone);

    $playlistCertificateSetting = CertificateSetting::where('certificate_type', 'playlist')
        ->where('trainingplan_id', $trainingplan->id)
        ->where('is_active', 1)
        ->first();

    // Assessment Content
    $assessmentContent = Assesment::where('type', 'content')
        ->where('content_id', $content->id)
        ->first();

    $queryAssessmentContentResult = AssesmentResult::with('user', 'assesment')
        ->where('user_id', $userId)
        ->where('assesment_id', $assessmentContent->id ?? '');

    $assessmentContentCount = $queryAssessmentContentResult->count();

    $assessmentContentLatestScores = (clone $queryAssessmentContentResult)
        ->latest()
        ->first()
        ->score ?? 0;

    return view('user.playlist.view', [
        "playlist" => $trainingplan,
        "content" => $content,
        "content_all" => $content_all,
        "content_user_all" => $content_user_all,
        "content_user" => $content_user,
        "assesment" => $assesment,
        "comment" => $comment,
        "totalComment" => $totalComment,
        "assessmentAccess" => $playlistProgressComplete,
        "assessmentCount" => $assessmentCount,
        "assessmentLatestScores" => $assessmentLatestScores,
        "percentageProgressPlaylist" => $percentageProgressPlaylist,
        "assessmentContent" => $assessmentContent,
        "assessmentContentCount" => $assessmentContentCount,
        "assessmentContentLatestScores" => $assessmentContentLatestScores,
        "playlistProgressComplete" => $playlistProgressComplete,
        "hasPlaylistAssessment" => $hasPlaylistAssessment,
        "playlistAssessmentDone" => $playlistAssessmentDone,
        "needsPlaylistAssessment" => $needsPlaylistAssessment,
        "canGeneratePlaylistCertificate" => $canGeneratePlaylistCertificate,
        "playlistCertificateSetting" => $playlistCertificateSetting,
    ]);
}
}
