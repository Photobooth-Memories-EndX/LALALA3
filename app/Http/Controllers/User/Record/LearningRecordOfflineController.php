<?php

namespace App\Http\Controllers\User\Record;

use App\Http\Controllers\Controller;
use App\Models\ContentUser;
use App\Services\Learning\CompletedPlaylistService;
use App\Services\Learning\LearningRecordLearningHourService;
use App\Services\Learning\UserRecordOfflineService;
use Illuminate\Support\Facades\Auth;

/**
 * Learning Record Offline Controller (User side).
 */
class LearningRecordOfflineController extends Controller
{
    public function __construct(
        private readonly UserRecordOfflineService $offlineService,
        private readonly CompletedPlaylistService $completedPlaylistService,
        private readonly LearningRecordLearningHourService $recordLhService,
    ) {
    }

    public function partial()
    {
        $userId = (int) Auth::id();
        $userEmpId = Auth::user()?->id_emp;

        $rows = $this->offlineService->getRecords($userId, $userEmpId);
        $filterOptions = $this->offlineService->getFilterOptions($userId, $userEmpId);

        // Canonical hero stats agar hero card tetap konsisten (Step 18)
        $completedPlaylists = $this->completedPlaylistService->forLearningRecord($userId);
        $completedPlaylistLearning = $completedPlaylists->count();
        $completedContentLearning = ContentUser::join('content', 'content.id', '=', 'content_users.content_id')
            ->where('content_users.user_id', $userId)
            ->where('content_users.finish', '1')
            ->whereNull('content.deleted_at')
            ->whereIn('content.type', ['Video', 'Document'])
            ->count();
        $learningHours = $this->recordLhService->getOfflineLearningHours($userId, $userEmpId);

        $recordStats = [
            'courseCompleted'    => $completedContentLearning,
            'playlistCompleted'  => $completedPlaylistLearning,
            'totalLearningHours' => $learningHours,
        ];

        return view('user.learning.record.learning-record-offline', [
            'rows'          => $rows,
            'filterOptions' => $filterOptions,
            'recordStats'   => $recordStats,
        ]);
    }
}
