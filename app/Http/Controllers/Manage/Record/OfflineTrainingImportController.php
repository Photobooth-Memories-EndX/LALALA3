<?php

namespace App\Http\Controllers\Manage\Record;

use App\Http\Controllers\Controller;
use App\Services\Record\OfflineTrainingExcelSchema;
use App\Services\Record\OfflineTrainingImportService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Symfony\Component\HttpFoundation\StreamedResponse;

class OfflineTrainingImportController extends Controller
{
    protected OfflineTrainingImportService $importService;

    public function __construct(OfflineTrainingImportService $importService)
    {
        $this->importService = $importService;
    }

    /**
     * Display the Excel Import landing and upload view (redirects to Non KP OWL with modal trigger).
     */
    public function index(Request $request)
    {
        if ($request->has('legacy')) {
            return view('main.record.offline-import', [
                'title'    => 'Import Offline Training',
                'subtitle' => 'Upload and import Non KP OWL Excel training records',
            ]);
        }

        if ($request->routeIs('manage.record.offline.import')) {
            return redirect()->route('manage.record.offline.import-non-ptkp');
        }

        return view('main.record.offline-import-non-ptkp', [
            'title'    => 'Import Non-PTKP Data',
            'subtitle' => 'Upload and validate Non-PTKP offline training records before importing into OWL LMS.',
        ]);
    }

    /**
     * Display dedicated Non PTKP Import full-page view.
     */
    public function indexNonPtkp(Request $request)
    {
        return $this->index($request);
    }

    /**
     * Generate and download blank official Non KP OWL Excel import template.
     */
    public function downloadTemplate(Request $request): StreamedResponse
    {
        $spreadsheet = OfflineTrainingExcelSchema::generateTemplateSpreadsheet();
        $filename = OfflineTrainingExcelSchema::TEMPLATE_FILENAME;

        return response()->streamDownload(function () use ($spreadsheet) {
            $writer = new Xlsx($spreadsheet);
            $writer->save('php://output');
        }, $filename, [
            'Content-Type'        => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'Content-Disposition' => 'attachment; filename="' . $filename . '"',
            'Cache-Control'       => 'max-age=0, no-cache, no-store, must-revalidate',
            'Pragma'              => 'public',
        ]);
    }

    /**
     * Upload spreadsheet, store in temporary storage, parse and return validation preview.
     */
    public function preview(Request $request): JsonResponse
    {
        try {
            $request->validate([
                'file' => [
                    'required',
                    'file',
                    'mimes:xlsx,xls',
                    'max:20480', // 20 MB max
                ],
            ], [
                'file.required' => 'Please select an Excel file (.xlsx or .xls) to upload.',
                'file.file'     => 'The uploaded item must be a valid file.',
                'file.mimes'    => 'Invalid file format. Only Excel spreadsheets (.xlsx, .xls) are accepted.',
                'file.max'      => 'File size exceeds maximum allowed limit of 20MB.',
            ]);

            $userId = (int) Auth::id();
            $meta = $this->importService->storeTemporaryFile($request->file('file'), $userId);
            $preview = $this->importService->parseAndPreview($meta['token'], $userId);

            return response()->json([
                'success'      => true,
                'data'         => $preview,
                'token'        => $preview['token'] ?? null,
                'stats'        => $preview['stats'] ?? null,
                'rows'         => $preview['preview_rows'] ?? [],
                'preview_rows' => $preview['preview_rows'] ?? [],
                'header_check'     => $preview['header_check'] ?? null,
                'can_confirm'      => $preview['can_confirm'] ?? false,
                'preview_state'    => $preview['preview_state'] ?? null,
                'importable_count' => $preview['importable_count'] ?? 0,
            ]);
        } catch (ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => $e->validator->errors()->first(),
                'errors'  => $e->validator->errors(),
            ], 422);
        } catch (\Throwable $e) {
            Log::error('OfflineTrainingImport preview failed: ' . $e->getMessage(), [
                'exception' => $e,
                'user_id'   => Auth::id(),
            ]);

            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 422);
        }
    }

    /**
     * Confirm and execute import within database transactions.
     */
    public function confirm(Request $request): JsonResponse
    {
        try {
            $request->validate([
                'token' => ['required', 'string', 'size:32'],
            ], [
                'token.required' => 'Import session token is required.',
                'token.size'     => 'Invalid import session token format.',
            ]);

            $userId = (int) Auth::id();
            $result = $this->importService->executeImport($request->input('token'), $userId);

            return response()->json([
                'success' => true,
                'message' => 'Offline training records successfully imported.',
                'result'  => $result,
            ]);
        } catch (ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => $e->validator->errors()->first(),
            ], 422);
        } catch (\Throwable $e) {
            Log::error('OfflineTrainingImport confirm failed: ' . $e->getMessage(), [
                'exception' => $e,
                'user_id'   => Auth::id(),
            ]);

            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 422);
        }
    }

    /**
     * Cancel import session and clean up temporary file.
     */
    public function cancel(Request $request): JsonResponse
    {
        try {
            $token = (string) $request->input('token', '');
            if (!empty($token)) {
                $this->importService->cleanupTemporaryFile($token, (int) Auth::id());
            }

            return response()->json([
                'success' => true,
                'message' => 'Import session cancelled and temporary files cleared.',
            ]);
        } catch (\Throwable $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 422);
        }
    }
}
