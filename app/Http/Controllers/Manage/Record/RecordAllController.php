<?php

namespace App\Http\Controllers\Manage\Record;

use App\Http\Controllers\Controller;
use App\Services\Record\RecordAllCombinedService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class RecordAllController extends Controller
{
    protected RecordAllCombinedService $combinedService;

    public function __construct(RecordAllCombinedService $combinedService)
    {
        $this->combinedService = $combinedService;
    }

    /**
     * Render the lightweight All KP OWL page shell.
     */
    public function index(Request $request)
    {
        $filterOptions = $this->combinedService->getFilterOptions();

        return view('main.record.all', [
            'title'           => 'All KP OWL',
            'subtitle'        => 'Combined Online & Offline Learning Record',
            'classifications' => $filterOptions['classifications'],
            'years'           => $filterOptions['years'],
            'entries'         => (int) $request->input('entries', 25),
        ]);
    }

    /**
     * Asynchronously return combined KPI and Source summary.
     */
    public function ajaxSummary(Request $request): JsonResponse
    {
        $summary = $this->combinedService->getSummary();

        return response()->json([
            'success'              => true,
            'total_learning'       => $summary['total_learning'],
            'ongoing'              => $summary['ongoing'],
            'finished'             => $summary['finished'],
            'total_learning_hours' => $summary['total_learning_hours'],
            'completion_rate'      => $summary['completion_rate'],
            'data'                 => $summary,
            'html'                 => view('main.record.components.all-kpi-summary', $summary)->render(),
        ]);
    }

    /**
     * Asynchronously return combined Classification Summary.
     */
    public function ajaxClassification(Request $request): JsonResponse
    {
        $classification = $this->combinedService->getClassificationSummary();

        if ($request->input('format') === 'json') {
            return response()->json([
                'success' => true,
                'data'    => $classification,
            ]);
        }

        return response()->json([
            'success' => true,
            'data'    => $classification,
            'html'    => view('main.record.components.all-classification-summary', [
                'classification' => $classification,
            ])->render(),
        ]);
    }

    /**
     * Server-side DataTable for combined learning records.
     */
    public function datatable(Request $request): JsonResponse
    {
        return $this->combinedService->datatable($request);
    }
}
