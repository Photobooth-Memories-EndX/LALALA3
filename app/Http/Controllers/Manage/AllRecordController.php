<?php

namespace App\Http\Controllers\Manage;

use App\Http\Controllers\Controller;
use App\Services\Manage\AllRecordService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class AllRecordController extends Controller
{
    protected AllRecordService $allRecordService;

    public function __construct(AllRecordService $allRecordService)
    {
        $this->allRecordService = $allRecordService;
    }

    /**
     * Render the All Record landing and combined table page.
     */
    public function index(Request $request)
    {
        $filterOptions = $this->allRecordService->getFilterOptions();

        return view('main.record.all', [
            'title'           => 'All KP OWL',
            'subtitle'        => 'Combined Online & Offline Learning Record',
            'years'           => $filterOptions['years'] ?? [],
            'departments'     => $filterOptions['departments'] ?? [],
            'companies'       => $filterOptions['companies'] ?? [],
            'classifications' => $filterOptions['classifications'] ?? [],
            'entries'         => (int) $request->input('entries', 25),
        ]);
    }

    /**
     * Handle AJAX DataTable requests for combined learning records.
     */
    public function data(Request $request): JsonResponse
    {
        return $this->allRecordService->datatable($request);
    }

    /**
     * Handle single record details request for the Detail View modal.
     */
    public function details(Request $request, string $source, $id): JsonResponse
    {
        return $this->allRecordService->getRecordDetails($source, $id);
    }

    /**
     * Download Excel export for All Record with all 52 columns.
     */
    public function export(Request $request): BinaryFileResponse
    {
        return $this->allRecordService->export($request);
    }

    /**
     * Download CSV export for All Record.
     */
    public function exportCsv(Request $request)
    {
        $request->merge([
            'source' => 'all',
            'format' => 'csv',
        ]);

        return app(\App\Http\Controllers\Manage\Record\RecordExportController::class)->download($request);
    }

    /**
     * Download PDF export for All Record.
     */
    public function exportPdf(Request $request)
    {
        $request->merge([
            'source' => 'all',
            'format' => 'pdf',
        ]);

        return app(\App\Http\Controllers\Manage\Record\RecordExportController::class)->download($request);
    }

    /**
     * Asynchronously return combined KPI summary for dashboard cards.
     */
    public function ajaxSummary(Request $request): JsonResponse
    {
        $summary = $this->allRecordService->getSummary();

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
     * Asynchronously return combined Classification summary for landing cards.
     */
    public function ajaxClassification(Request $request): JsonResponse
    {
        $classification = $this->allRecordService->getClassificationSummary();

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
     * Redirect to the appropriate course details view.
     */
    public function courseRedirect(Request $request, string $type, $id)
    {
        $classification = $request->query('classification');
        $params = ['from' => 'all'];
        if (filled($classification)) {
            $params['classification'] = $classification;
        }

        if ($type === 'offline') {
            $params['training'] = $id;
            return redirect()->route('manage.record.offline.course', $params);
        }

        if ($type === 'content') {
            $content = DB::table('content')->where('id', $id)->first();
            if ($content && !empty($content->slug)) {
                $params['slug'] = $content->slug;
                return redirect()->route('learning.manage.content', $params);
            }
            return redirect()->route('learning.manage.content', array_merge(['slug' => $id], $params));
        }

        if ($type === 'playlist') {
            $playlist = DB::table('trainingplan')->where('id', $id)->first();
            if ($playlist && !empty($playlist->slug)) {
                return redirect()->to(url('/playlist/' . $playlist->slug . '?' . http_build_query($params)));
            }
            return redirect()->to(url('/playlist/' . $id . '?' . http_build_query($params)));
        }

        abort(404, 'Course type not supported.');
    }
}
