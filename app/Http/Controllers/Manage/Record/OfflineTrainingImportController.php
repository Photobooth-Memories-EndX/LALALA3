<?php

namespace App\Http\Controllers\Manage\Record;

use App\Http\Controllers\Controller;
use App\Services\Record\OfflineTrainingImportService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;

class OfflineTrainingImportController extends Controller
{
    protected OfflineTrainingImportService $importService;

    public function __construct(OfflineTrainingImportService $importService)
    {
        $this->importService = $importService;
    }

    /**
     * Display the Excel Import landing and upload view.
     */
    public function index(Request $request)
    {
        return view('main.record.offline-import', [
            'title'    => 'Import Offline Training',
            'subtitle' => 'Upload and import Non KP OWL Excel training records',
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
                'success' => true,
                'data'    => $preview,
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
