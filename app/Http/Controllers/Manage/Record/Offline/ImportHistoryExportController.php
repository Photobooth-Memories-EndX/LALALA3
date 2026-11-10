<?php

namespace App\Http\Controllers\Manage\Record\Offline;

use App\Exports\ImportHistoryFilteredExport;
use App\Http\Controllers\Controller;
use App\Services\ImportHistory\ImportHistoryExportService;
use App\Services\ImportLog\ImportHistoryQueryService;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ImportHistoryExportController extends Controller
{
    protected ImportHistoryQueryService $queryService;
    protected ImportHistoryExportService $exportService;

    public function __construct(
        ImportHistoryQueryService $queryService,
        ImportHistoryExportService $exportService
    ) {
        $this->queryService = $queryService;
        $this->exportService = $exportService;
    }

    /**
     * Download filter-aware Excel audit export for an import batch.
     *
     * @param string $batch_uuid
     * @param Request $request
     * @return StreamedResponse
     */
    public function export(string $batch_uuid, Request $request): StreamedResponse
    {
        if (!preg_match('/^[a-f0-9\-]{36}$/i', $batch_uuid)) {
            abort(404, 'Invalid batch UUID format.');
        }

        $batch = $this->queryService->getBatchByUuid($batch_uuid);
        if (!$batch) {
            abort(404, 'The requested import batch does not exist.');
        }

        $filters = [
            'action'            => $request->input('action'),
            'validation_status' => $request->input('validation_status'),
            'search'            => $request->input('search.value') ?: $request->input('search_query'),
        ];

        $export = new ImportHistoryFilteredExport($batch, $filters, $this->exportService);

        return $export->download();
    }
}
