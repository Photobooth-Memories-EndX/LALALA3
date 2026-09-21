<?php

namespace App\Http\Controllers\Manage\Record;

use App\Exports\LearningExport;
use App\Http\Controllers\Controller;
use App\Http\Controllers\LearningManageController;
use App\Services\Learning\LearningDatatableService;
use Illuminate\Http\Request;

class RecordOnlineController extends Controller
{
    protected LearningManageController $learningManageController;

    public function __construct(LearningManageController $learningManageController)
    {
        $this->learningManageController = $learningManageController;
    }

    public function index(Request $request)
    {
        $viewData = [
            'title' => 'KP OWL',
            'subtitle' => 'Online Learning Record',
            'years' => LearningManageController::learningYears(),
            'entries' => (int) $request->input('entries', 25),
            'search' => (string) $request->input('search', ''),
            'downloadslug' => '',
            'content' => collect(),
        ];

        return view('main.record.online', $viewData);
    }

    public function ajaxSummary(Request $request)
    {
        $kpi = $this->learningManageController->getKpiSummary();
        $totalLearningHours = $this->learningManageController->getTotalLearningHours();

        $data = [
            'total_learning' => $kpi['total_learning'],
            'ongoing' => $kpi['ongoing'],
            'finished' => $kpi['finished'],
            'completion_rate' => $kpi['completion_rate'],
            'total_learning_hours' => $totalLearningHours,
            'totalLearning' => $kpi['total_learning'],
            'finish' => $kpi['finished'],
            'completionRate' => $kpi['completion_rate'],
            'learningHours' => $totalLearningHours,
        ];

        if ($request->input('format') === 'html') {
            return view('main.record.components.online-kpi-summary', $data);
        }

        return response()->json([
            'success' => true,
            'total_learning' => $data['total_learning'],
            'ongoing' => $data['ongoing'],
            'finished' => $data['finished'],
            'completion_rate' => $data['completion_rate'],
            'total_learning_hours' => $data['total_learning_hours'],
            'data' => [
                'total_learning' => $data['total_learning'],
                'ongoing' => $data['ongoing'],
                'finished' => $data['finished'],
                'completion_rate' => $data['completion_rate'],
                'total_learning_hours' => $data['total_learning_hours'],
            ],
            'html' => view('main.record.components.online-kpi-summary', $data)->render(),
        ]);
    }

    public function ajaxClassification(Request $request)
    {
        $classification = $this->learningManageController->getClassificationSummary();

        if ($request->wantsJson() || $request->input('format') === 'json') {
            return response()->json([
                'success' => true,
                'data' => $classification,
            ]);
        }

        return view('main.record.components.online-classification-summary', [
            'classification' => $classification,
        ]);
    }

    public function datatable(Request $request, LearningDatatableService $datatableService)
    {
        return $datatableService->make($request);
    }

    public function searchEmployees(Request $request)
    {
        return $this->learningManageController->searchEmployees($request);
    }

    public function searchTitles(Request $request)
    {
        return $this->learningManageController->searchTitles($request);
    }

    public function download(Request $request)
    {
        return (new LearningExport($request->all(), $request->input('type', ''), $request->input('slug', '')))
            ->download(now()->format('Ymd_His') . '_kp_owl_learning_data.xlsx');
    }
}
