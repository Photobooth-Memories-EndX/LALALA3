<?php

namespace App\Http\Controllers\Manage\KpOwl;

use App\Http\Controllers\Controller;
use App\Services\Manage\KpOwl\KpOwlExportService;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class KpOwlExportController extends Controller
{
    protected KpOwlExportService $exportService;

    public function __construct(KpOwlExportService $exportService)
    {
        $this->exportService = $exportService;
    }

    /**
     * Download Excel export with all 52 canonical columns.
     */
    public function export(Request $request): BinaryFileResponse
    {
        return $this->exportService->download($request->all());
    }
}
