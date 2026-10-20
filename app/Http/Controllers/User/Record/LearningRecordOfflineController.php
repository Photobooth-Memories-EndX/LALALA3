<?php

namespace App\Http\Controllers\User\Record;

use App\Http\Controllers\Controller;
use App\Http\Controllers\LearningRecordController;
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

        $recordStats = app(LearningRecordController::class)->calculateHeroStats('offline', $userId, $userEmpId);

        $hasOfflineRecords = $this->offlineService->queryForUser($userId, $userEmpId)->exists();

        return view('user.learning.record.learning-record-offline', [
            'rows'              => $rows,
            'filterOptions'     => $filterOptions,
            'recordMode'        => 'offline',
            'recordStats'       => $recordStats,
            'stats'             => $recordStats,
            'hasOfflineRecords' => $hasOfflineRecords,
        ]);
    }
}
