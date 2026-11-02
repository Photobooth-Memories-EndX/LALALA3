<?php

namespace App\Http\Controllers\Manage\Record;

use App\Http\Controllers\Controller;
use App\Models\ImportBatch;
use App\Models\ImportBatchRow;
use App\Services\ImportLog\ImportFileStorageService;
use App\Services\ImportLog\ImportHistoryQueryService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Yajra\DataTables\Facades\DataTables;

class ImportHistoryController extends Controller
{
    protected ImportHistoryQueryService $queryService;
    protected ImportFileStorageService $fileStorage;

    public function __construct(
        ImportHistoryQueryService $queryService,
        ImportFileStorageService $fileStorage
    ) {
        $this->queryService = $queryService;
        $this->fileStorage = $fileStorage;
    }

    /**
     * Display the centralized Import History index view.
     */
    public function index(Request $request)
    {
        $summary = $this->queryService->getSummaryCards();
        $users = $this->queryService->getDistinctUsers();

        return view('manage.record.offline.import-history', [
            'summary' => $summary,
            'users'   => $users,
        ]);
    }

    /**
     * Fetch fresh summary cards metrics via AJAX.
     */
    public function summary(): JsonResponse
    {
        return response()->json($this->queryService->getSummaryCards());
    }

    /**
     * Server-side DataTables provider for import batches.
     */
    public function datatable(Request $request): JsonResponse
    {
        app()->instance('request', $request);
        app()->forgetInstance('datatables.request');

        $filters = [
            'source_type' => $request->input('source_type'),
            'status'      => $request->input('status'),
            'user_id'     => $request->input('user_id'),
            'date_start'  => $request->input('date_start'),
            'date_end'    => $request->input('date_end'),
            'search'      => $request->input('search.value') ?: $request->input('search_query'),
        ];

        $query = $this->queryService->getBatchesQuery($filters);

        return DataTables::of($query)
            ->addIndexColumn()
            ->addColumn('batch_uuid_short', function (ImportBatch $batch) {
                return substr($batch->batch_uuid, 0, 8) . '...';
            })
            ->addColumn('source_badge', function (ImportBatch $batch) {
                if ($batch->source_type === ImportBatch::SOURCE_PTKP) {
                    return '<span class="badge bg-blue-lt text-blue fw-bold px-2 py-1"><i class="ti ti-file-upload me-1"></i>PTKP</span>';
                }
                return '<span class="badge bg-azure-lt text-azure fw-bold px-2 py-1"><i class="ti ti-file-spreadsheet me-1"></i>Non-PTKP</span>';
            })
            ->filterColumn('source_badge', function ($query, $keyword) {
                $query->where('import_batches.source_type', 'LIKE', "%{$keyword}%");
            })
            ->orderColumn('source_badge', function ($query, $order) {
                $query->orderBy('import_batches.source_type', $order);
            })
            ->addColumn('user_name', function (ImportBatch $batch) {
                return e($batch->importedBy?->name ?? $batch->importedBy?->email ?? 'System');
            })
            ->filterColumn('user_name', function ($query, $keyword) {
                $query->whereHas('importedBy', function ($sub) use ($keyword) {
                    $sub->where('name', 'LIKE', "%{$keyword}%")
                        ->orWhere('email', 'LIKE', "%{$keyword}%");
                });
            })
            ->orderColumn('user_name', function ($query, $order) {
                $query->leftJoin('users as u_sort', 'u_sort.id', '=', 'import_batches.imported_by')
                    ->orderBy('u_sort.name', $order);
            })
            ->addColumn('started_at_formatted', function (ImportBatch $batch) {
                return $batch->started_at ? $batch->started_at->format('Y-m-d H:i:s') : '-';
            })
            ->orderColumn('started_at_formatted', function ($query, $order) {
                $query->orderBy('import_batches.started_at', $order);
            })
            ->addColumn('finished_at_formatted', function (ImportBatch $batch) {
                return $batch->finished_at ? $batch->finished_at->format('Y-m-d H:i:s') : '-';
            })
            ->orderColumn('finished_at_formatted', function ($query, $order) {
                $query->orderBy('import_batches.finished_at', $order);
            })
            ->addColumn('status_badge', function (ImportBatch $batch) {
                return match ($batch->status) {
                    ImportBatch::STATUS_COMPLETED => '<span class="badge bg-success-lt text-success fw-bold px-2 py-1"><i class="ti ti-check me-1"></i>Success</span>',
                    ImportBatch::STATUS_COMPLETED_WITH_WARNINGS => '<span class="badge bg-warning-lt text-warning fw-bold px-2 py-1"><i class="ti ti-alert-triangle me-1"></i>Warning</span>',
                    ImportBatch::STATUS_FAILED => '<span class="badge bg-danger-lt text-danger fw-bold px-2 py-1"><i class="ti ti-x me-1"></i>Failed</span>',
                    ImportBatch::STATUS_IMPORTING => '<span class="badge bg-cyan-lt text-cyan fw-bold px-2 py-1"><i class="ti ti-loader me-1 spin"></i>Processing</span>',
                    default => '<span class="badge bg-secondary-lt text-secondary fw-bold px-2 py-1">' . e($batch->status) . '</span>',
                };
            })
            ->filterColumn('status_badge', function ($query, $keyword) {
                $query->where('import_batches.status', 'LIKE', "%{$keyword}%");
            })
            ->orderColumn('status_badge', function ($query, $order) {
                $query->orderBy('import_batches.status', $order);
            })
            ->addColumn('has_file', function (ImportBatch $batch) {
                return $batch->hasStoredFile();
            })
            ->addColumn('actions', function (ImportBatch $batch) {
                $uuid = e($batch->batch_uuid);
                $detailBtn = sprintf(
                    '<a href="%s" class="btn btn-sm btn-outline-primary owl-action-btn btn-batch-detail" data-uuid="%s" data-bs-toggle="tooltip" data-bs-placement="top" title="View Detail" aria-label="View Detail"><i class="ti ti-eye"></i></a>',
                    route('manage.record.offline.import-history.detail', $uuid),
                    $uuid
                );

                $downloadBtn = '';
                if ($batch->hasStoredFile()) {
                    $downloadUrl = route('manage.record.offline.import-history.download', $batch->batch_uuid);
                    $downloadBtn = sprintf(
                        '<a href="%s" class="btn btn-sm btn-outline-secondary owl-action-btn ms-1" data-bs-toggle="tooltip" data-bs-placement="top" title="Download Original File" aria-label="Download Original File"><i class="ti ti-download"></i></a>',
                        $downloadUrl
                    );
                }

                return '<div class="d-inline-flex align-items-center justify-content-center">' . $detailBtn . $downloadBtn . '</div>';
            })
            ->rawColumns(['source_badge', 'status_badge', 'actions'])
            ->make(true);
    }

    /**
     * Retrieve detailed information of an import batch.
     */
    public function detail(string $uuid): JsonResponse
    {
        $batch = $this->queryService->getBatchByUuid($uuid);
        if (!$batch) {
            return response()->json(['error' => 'Batch not found.'], 404);
        }

        $duration = null;
        if ($batch->started_at && $batch->finished_at) {
            $duration = $batch->finished_at->diffForHumans($batch->started_at, [
                'syntax' => \Carbon\CarbonInterface::DIFF_ABSOLUTE,
                'parts'  => 2,
            ]);
        }

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

    /**
     * Server-side DataTables provider for import batch rows.
     */
    public function rowsDatatable(string $uuid, Request $request): JsonResponse
    {
        app()->instance('request', $request);
        app()->forgetInstance('datatables.request');

        $batch = $this->queryService->getBatchByUuid($uuid);
        if (!$batch) {
            return response()->json(['error' => 'Batch not found.'], 404);
        }

        $filters = [
            'action'            => $request->input('action'),
            'validation_status' => $request->input('validation_status'),
            'search'            => $request->input('search.value') ?: $request->input('search_query'),
        ];

        $query = $this->queryService->getBatchRowsQuery($batch->id, $filters);

        return DataTables::of($query)
            ->addColumn('validation_status_badge', function (ImportBatchRow $row) {
                return match ($row->validation_status) {
                    ImportBatchRow::STATUS_VALID => '<span class="badge bg-success-lt text-success fw-bold px-2 py-0.5">VALID</span>',
                    ImportBatchRow::STATUS_WARNING => '<span class="badge bg-warning-lt text-warning fw-bold px-2 py-0.5">WARNING</span>',
                    ImportBatchRow::STATUS_DUPLICATE => '<span class="badge bg-info-lt text-info fw-bold px-2 py-0.5">DUPLICATE</span>',
                    ImportBatchRow::STATUS_INVALID_COMPANY => '<span class="badge bg-warning-lt text-warning fw-bold px-2 py-0.5">INV. COMPANY</span>',
                    ImportBatchRow::STATUS_INVALID => '<span class="badge bg-danger-lt text-danger fw-bold px-2 py-0.5">INVALID</span>',
                    default => '<span class="badge bg-secondary-lt text-secondary fw-bold px-2 py-0.5">' . e($row->validation_status) . '</span>',
                };
            })
            ->addColumn('action_badge', function (ImportBatchRow $row) {
                return match ($row->action) {
                    ImportBatchRow::ACTION_IMPORTED => '<span class="badge bg-success text-white fw-bold px-2 py-0.5">IMPORTED</span>',
                    ImportBatchRow::ACTION_SKIPPED => '<span class="badge bg-secondary text-white fw-bold px-2 py-0.5">SKIPPED</span>',
                    ImportBatchRow::ACTION_FAILED => '<span class="badge bg-danger text-white fw-bold px-2 py-0.5">FAILED</span>',
                    default => '<span class="badge bg-secondary text-white fw-bold px-2 py-0.5">' . e($row->action) . '</span>',
                };
            })
            ->addColumn('messages_formatted', function (ImportBatchRow $row) {
                if (empty($row->messages)) {
                    return '-';
                }
                $msgs = is_array($row->messages) ? $row->messages : json_decode((string) $row->messages, true);
                if (is_array($msgs)) {
                    $html = '<ul class="mb-0 ps-3 small text-muted text-start">';
                    foreach ($msgs as $msg) {
                        $html .= '<li>' . e((string) $msg) . '</li>';
                    }
                    $html .= '</ul>';
                    return $html;
                }
                return '<span class="small text-muted">' . e((string) $row->messages) . '</span>';
            })
            ->addColumn('references', function (ImportBatchRow $row) {
                $refs = [];
                if ($row->offline_training_id) {
                    $refs[] = '<span class="badge badge-outline text-muted" title="Offline Training ID">T#' . (int) $row->offline_training_id . '</span>';
                }
                if ($row->offline_training_user_id) {
                    $refs[] = '<span class="badge badge-outline text-muted" title="Participant ID">P#' . (int) $row->offline_training_user_id . '</span>';
                }
                return !empty($refs) ? implode(' ', $refs) : '-';
            })
            ->rawColumns(['validation_status_badge', 'action_badge', 'messages_formatted', 'references'])
            ->make(true);
    }

    /**
     * Download the original Excel import file securely from private storage.
     */
    public function download(string $uuid): StreamedResponse
    {
        if (!preg_match('/^[a-f0-9\-]{36}$/i', $uuid)) {
            abort(404, 'Invalid batch UUID format.');
        }

        $batch = ImportBatch::where('batch_uuid', $uuid)->first();
        if (!$batch) {
            abort(404, 'The requested import batch does not exist.');
        }

        if (!$batch->hasStoredFile()) {
            abort(404, 'The audit Excel file is not available in private storage.');
        }

        $disk = $batch->file_disk ?: ImportFileStorageService::DISK;
        $safeFilename = $batch->getSafeDownloadFilename();

        $ext = strtolower(pathinfo($batch->file_path, PATHINFO_EXTENSION));
        $contentType = match ($ext) {
            'xls'   => 'application/vnd.ms-excel',
            'csv'   => 'text/csv',
            default => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        };

        return Storage::disk($disk)->download($batch->file_path, $safeFilename, [
            'Content-Type'              => $contentType,
            'X-Content-Type-Options'    => 'nosniff',
            'Content-Disposition'       => 'attachment; filename="' . $safeFilename . '"',
        ]);
    }
}
