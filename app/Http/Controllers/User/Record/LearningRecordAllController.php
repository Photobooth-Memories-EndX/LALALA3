<?php

namespace App\Http\Controllers\User\Record;

use App\Http\Controllers\Controller;
use App\Models\ContentUser;
use App\Services\CertificateEligibilityService;
use App\Services\Learning\CompletedPlaylistService;
use App\Services\Learning\LearningRecordLearningHourService;
use App\Services\Learning\UserRecordAllService;
use Illuminate\Support\Facades\Auth;

/**
 * Learning Record All (Combined Online & Offline) Controller (User side).
 */
class LearningRecordAllController extends Controller
{
    public function __construct(
        private readonly UserRecordAllService $allService,
        private readonly CompletedPlaylistService $completedPlaylistService,
        private readonly LearningRecordLearningHourService $recordLhService,
    ) {
    }

    public function partial(CertificateEligibilityService $eligibilityService)
    {
        $userId = (int) Auth::id();
        $userEmpId = Auth::user()?->id_emp;

        $rows = $this->allService->getRecords($userId, $userEmpId, $eligibilityService);

        // Filter options based on unfiltered combined collection
        $unfilteredRows = $this->allService->getRecords($userId, $userEmpId, $eligibilityService, [
            'search'         => '',
            'source'         => 'all',
            'type'           => 'all',
            'classification' => 'all',
            'year'           => 'all',
        ]);
        $filterOptions = $this->allService->getFilterOptions($unfilteredRows);

        // Canonical hero stats agar hero card tetap konsisten (Step 18)
        $completedPlaylists = $this->completedPlaylistService->forLearningRecord($userId);
        $completedPlaylistLearning = $completedPlaylists->count();
        $completedContentLearning = ContentUser::join('content', 'content.id', '=', 'content_users.content_id')
            ->where('content_users.user_id', $userId)
            ->where('content_users.finish', '1')
            ->whereNull('content.deleted_at')
            ->whereIn('content.type', ['Video', 'Document'])
            ->count();
        $learningHours = $this->recordLhService->getTotalLearningHours($userId);

        $recordStats = [
            'courseCompleted'    => $completedContentLearning,
            'playlistCompleted'  => $completedPlaylistLearning,
            'totalLearningHours' => $learningHours,
        ];

        return view('user.learning.record.learning-record-all', [
            'rows'          => $rows,
            'filterOptions' => $filterOptions,
            'recordStats'   => $recordStats,
        ]);
    }
}
