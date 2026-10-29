<?php

namespace App\Http\Controllers\Manage\Record\Offline;

use App\Exports\Manage\PtkpImportTemplateExport;
use App\Http\Controllers\Controller;
use App\Services\OfflineTraining\PtkpImportService;
use App\Services\Record\PtkpExcelSchema;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Throwable;

class PtkpImportController extends Controller
{
    protected PtkpImportService $importService;

    public function __construct(PtkpImportService $importService)
    {
        $this->importService = $importService;
    }

    /**
     * Display the PTKP Excel Import page.
     */
    public function index()
    {
        return view('manage.record.offline.import-ptkp', [
            'title'    => 'Import Data PTKP',
            'subtitle' => 'Upload and import PTKP offline training records with automatic employee identity synchronization',
        ]);
    }

    /**
     * Download the official PTKP Excel import template.
     */
    public function downloadTemplate(): StreamedResponse
    {
        $exporter = new PtkpImportTemplateExport();
        $spreadsheet = $exporter->buildSpreadsheet();
        $filename = PtkpExcelSchema::TEMPLATE_FILENAME;

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
     * Upload spreadsheet, validate headers and data, return preview payload.
     */
    public function preview(Request $request): JsonResponse
    {
        try {
            $request->validate([
                'file' => [
                    'required',
                    'file',
                    'mimes:xlsx,xls',
                    'max:20480', // 20 MB
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
                'success'          => true,
                'token'            => $preview['token'],
                'filename'         => $preview['filename'],
                'header_check'     => $preview['header_check'],
                'stats'            => $preview['stats'],
                'rows'             => $preview['rows'],
                'total_rows'       => $preview['total_rows'],
                'importable_count' => $preview['importable_count'],
                'can_confirm'      => $preview['can_confirm'],
            ]);
        } catch (ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => $e->validator->errors()->first(),
                'errors'  => $e->validator->errors(),
            ], 422);
        } catch (Throwable $e) {
            Log::error('PtkpImport preview failed: ' . $e->getMessage(), [
                'user_id'   => Auth::id(),
                'exception' => $e,
            ]);

            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 422);
        }
    }

    /**
     * Confirm and execute import.
     */
    public function confirm(Request $request): JsonResponse
    {
        try {
            $request->validate([
                'token' => ['required', 'string', 'size:32'],
            ], [
                'token.required' => 'Import session token is required.',
                'token.size'     => 'Invalid session token format.',
            ]);

            $userId = (int) Auth::id();
            $result = $this->importService->executeImport($request->input('token'), $userId);

            return response()->json([
                'success' => true,
                'message' => 'PTKP offline training records have been successfully imported.',
                'result'  => $result,
            ]);
        } catch (ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => $e->validator->errors()->first(),
            ], 422);
        } catch (Throwable $e) {
            Log::error('PtkpImport confirm failed: ' . $e->getMessage(), [
                'user_id'   => Auth::id(),
                'exception' => $e,
            ]);

            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 422);
        }
    }

    /**
     * Cancel import session and cleanup temporary files.
     */
    public function cancel(Request $request): JsonResponse
    {
        $token = (string) $request->input('token');
        if ($token !== '') {
            $this->importService->cleanupTemporaryFile($token, (int) Auth::id());
        }

        return response()->json([
            'success' => true,
            'message' => 'Import session cancelled and temporary files removed.',
        ]);
    }
}
