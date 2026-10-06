<?php

namespace App\Http\Controllers\Manage\Record;

use App\Exports\LearningExport;
use App\Exports\OnlineRecordCsvExport;
use App\Http\Controllers\Controller;
use App\Http\Controllers\LearningManageController;
use App\Services\Learning\LearningDatatableService;
use Barryvdh\DomPDF\Facade\Pdf;
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

    public function export(Request $request)
    {
        $fileName = sprintf('kp-owl-online-records-%s.xlsx', now()->format('Y-m-d'));

        return (new LearningExport($request->all(), $request->input('type', ''), $request->input('slug', '')))
            ->download($fileName);
    }

    public function download(Request $request)
    {
        return $this->export($request);
    }

    public function exportCsv(Request $request)
    {
        $export = new OnlineRecordCsvExport(
            $request->all(),
            (string) $request->input('type', ''),
            (string) $request->input('slug', '')
        );

        return $export->download();
    }

    public function exportPdf(Request $request)
    {
        $filters = $request->all();
        $query = LearningDatatableService::buildBaseQuery(
            $filters,
            (string) $request->input('type', ''),
            (string) $request->input('slug', '')
        );

        if (empty($filters['sort'])) {
            $query->orderBy('unified.created_at', 'desc')
                  ->orderBy('unified.source_type', 'desc')
                  ->orderBy('unified.source_id', 'desc');
        }

        $totalRecords = (clone $query)->count();

        // Safe limit for DomPDF rendering to prevent memory exhaustion
        $limit = 500;
        $records = $query->limit($limit)->get();

        $pdf = Pdf::loadView('main.record.online.export-pdf', [
            'records'       => $records,
            'totalRecords'  => $totalRecords,
            'limit'         => $limit,
            'filters'       => $filters,
            'generatedAt'   => now()->format('d M Y H:i:s'),
        ]);

        $fileName = sprintf('kp-owl-online-records-%s.pdf', now()->format('Y-m-d'));

        return $pdf->setPaper('a4', 'landscape')->download($fileName);
    }
}
