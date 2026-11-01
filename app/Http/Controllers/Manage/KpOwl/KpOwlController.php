<?php

namespace App\Http\Controllers\Manage\KpOwl;

use App\Http\Controllers\Controller;
use App\Services\Manage\KpOwl\KpOwlFilterService;
use App\Services\Manage\KpOwl\KpOwlRecordService;
use App\Services\Manage\KpOwl\KpOwlStatisticsService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class KpOwlController extends Controller
{
    protected KpOwlRecordService $recordService;
    protected KpOwlFilterService $filterService;
    protected KpOwlStatisticsService $statisticsService;

    public function __construct(
        KpOwlRecordService $recordService,
        KpOwlFilterService $filterService,
        KpOwlStatisticsService $statisticsService
    ) {
        $this->recordService = $recordService;
        $this->filterService = $filterService;
        $this->statisticsService = $statisticsService;
    }

    /**
     * Render the KP OWL learning records dashboard and table.
     */
    public function index(Request $request)
    {
        $filterOptions = $this->filterService->getFilterOptions();

        return view('manage.kp-owl.index', [
            'title'           => 'Manage — KP OWL',
            'subtitle'        => 'Online Learning Record (Content & Playlist)',
            'filterOptions'   => $filterOptions,
            'entries'         => (int) $request->input('entries', 25),
            'years'           => $filterOptions['years'] ?? [],
            'months'          => $filterOptions['months'] ?? [],
            'classifications' => $filterOptions['classifications'] ?? [],
            'departments'     => $filterOptions['departments'] ?? [],
            'teams'           => $filterOptions['teams'] ?? [],
            'companies'       => $filterOptions['companies'] ?? [],
        ]);
    }

    /**
     * Return JSON for Yajra DataTables (server-side).
     */
    public function data(Request $request): JsonResponse
    {
        return $this->recordService->datatable($request);
    }

    /**
     * Return JSON KPI statistics for dashboard cards.
     */
    public function statistics(Request $request): JsonResponse
    {
        $stats = $this->statisticsService->getStatistics($request->all());

        return response()->json([
            'success' => true,
            'data'    => $stats,
        ]);
    }
}
