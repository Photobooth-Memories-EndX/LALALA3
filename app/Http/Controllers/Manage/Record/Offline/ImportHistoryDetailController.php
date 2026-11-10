<?php

namespace App\Http\Controllers\Manage\Record\Offline;

use App\Http\Controllers\Controller;
use App\Models\ImportBatch;
use App\Services\ImportLog\ImportHistoryQueryService;
use Carbon\CarbonInterface;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ImportHistoryDetailController extends Controller
{
    protected ImportHistoryQueryService $queryService;

    public function __construct(ImportHistoryQueryService $queryService)
    {
        $this->queryService = $queryService;
    }

    /**
     * Display the dedicated batch detail page or return JSON payload for API/AJAX requests.
     *
     * @param string $batch_uuid
     * @param Request $request
     * @return View|JsonResponse
     */
    public function show(string $batch_uuid, Request $request)
    {
        if (!preg_match('/^[a-f0-9\-]{36}$/i', $batch_uuid)) {
            abort(404, 'Invalid batch UUID format.');
        }

        $batch = $this->queryService->getBatchByUuid($batch_uuid);
        if (!$batch) {
            abort(404, 'The requested import batch does not exist.');
        }

        $duration = null;
        if ($batch->started_at && $batch->finished_at) {
            $duration = $batch->finished_at->diffForHumans($batch->started_at, [
                'syntax' => CarbonInterface::DIFF_ABSOLUTE,
                'parts'  => 2,
            ]);
        }

        // Backward compatibility: If requested via JSON / AJAX (e.g. automated tests or API callers)
        if ($request->wantsJson() || $request->ajax()) {
            return response()->json([
                'batch_uuid'        => $batch->batch_uuid,
                'source_type'       => $batch->source_type,
                'original_filename' => $batch->original_filename,
                'status'            => $batch->status,
                'imported_by'       => [
                    'id'     => $batch->importedBy?->id,
                    'name'   => $batch->importedBy?->name ?? 'System',
                    'email'  => $batch->importedBy?->email,
                    'id_emp' => $batch->importedBy?->id_emp,
                ],
                'started_at'        => $batch->started_at?->format('Y-m-d H:i:s'),
                'finished_at'       => $batch->finished_at?->format('Y-m-d H:i:s'),
                'duration'          => $duration,
                'counters'          => [
                    'total_rows'        => (int) $batch->total_rows,
                    'valid_rows'        => (int) $batch->valid_rows,
                    'warning_rows'      => (int) $batch->warning_rows,
                    'duplicate_rows'    => (int) $batch->duplicate_rows,
                    'invalid_rows'      => (int) $batch->invalid_rows,
                    'imported_rows'     => (int) $batch->imported_rows,
                    'skipped_rows'      => (int) $batch->skipped_rows,
                    'failed_rows'       => (int) $batch->failed_rows,
                    'trainings_created' => (int) $batch->trainings_created,
                    'trainings_reused'  => (int) $batch->trainings_reused,
                ],
                'error_summary'     => $batch->error_summary,
                'metadata'          => $batch->metadata,
                'has_file'          => $batch->hasStoredFile(),
                'file_size'         => $batch->file_size,
                'download_url'      => $batch->hasStoredFile()
                    ? route('manage.record.offline.import-history.download', $batch->batch_uuid)
                    : null,
            ]);
        }

        $rowCounts = $batch->rows()
            ->selectRaw('validation_status, COUNT(*) as count')
            ->groupBy('validation_status')
            ->pluck('count', 'validation_status')
            ->toArray();

        $hasRows = !empty($rowCounts);

        $metrics = [
            'valid'           => $hasRows ? (int) ($rowCounts[\App\Models\ImportBatchRow::STATUS_VALID] ?? 0) : (int) $batch->valid_rows,
            'warning'         => $hasRows ? (int) ($rowCounts[\App\Models\ImportBatchRow::STATUS_WARNING] ?? 0) : (int) $batch->warning_rows,
            'duplicate'       => $hasRows ? (int) ($rowCounts[\App\Models\ImportBatchRow::STATUS_DUPLICATE] ?? 0) : (int) $batch->duplicate_rows,
            'invalid'         => $hasRows ? (int) ($rowCounts[\App\Models\ImportBatchRow::STATUS_INVALID] ?? 0) : (int) $batch->invalid_rows,
            'invalid_company' => (int) ($rowCounts[\App\Models\ImportBatchRow::STATUS_INVALID_COMPANY] ?? 0),
            'all_total'       => (int) $batch->total_rows,
        ];

        return view('manage.record.offline.import-history.detail', [
            'batch'    => $batch,
            'duration' => $duration,
            'metrics'  => $metrics,
        ]);
    }
}
