<?php

namespace App\Http\Controllers\User\Record;

use App\Http\Controllers\Controller;
use App\Http\Controllers\LearningRecordController;
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
            'search'             => '',
            'source'             => 'all',
            'type'               => 'all',
            'category'           => 'all',
            'classification'     => 'all',
            'year'               => 'all',
            'status'             => 'all',
            'duration_min'       => '',
            'duration_max'       => '',
            'lh_min'             => '',
            'lh_max'             => '',
            'attendance'         => 'all',
            'certificate'        => 'all',
            'sub_classification' => 'all',
            'delivery_method'    => 'all',
            'provider'           => 'all',
        ]);
        $filterOptions = $this->allService->getFilterOptions($unfilteredRows);

        $recordStats = app(LearningRecordController::class)->calculateHeroStats('all', $userId, $userEmpId, $eligibilityService);

        $hasAnyRecords = $this->allService->hasAnyRecords($userId, $userEmpId, $eligibilityService);

        return view('user.learning.record.learning-record-all', [
            'rows'          => $rows,
            'filterOptions' => $filterOptions,
            'recordMode'    => 'all',
            'recordStats'   => $recordStats,
            'stats'         => $recordStats,
            'hasAnyRecords' => $hasAnyRecords,
        ]);
    }
}
