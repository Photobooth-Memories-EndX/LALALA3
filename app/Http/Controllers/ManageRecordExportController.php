<?php

namespace App\Http\Controllers;

use App\Services\Record\ManageAllRecordExportService;
use App\Services\Record\ManageOfflineRecordExportService;
use App\Services\Record\ManageOnlineRecordExportService;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class ManageRecordExportController extends Controller
{
    protected ManageOnlineRecordExportService $onlineService;
    protected ManageOfflineRecordExportService $offlineService;
    protected ManageAllRecordExportService $allService;

    public function __construct(
        ManageOnlineRecordExportService $onlineService,
        ManageOfflineRecordExportService $offlineService,
        ManageAllRecordExportService $allService
    ) {
        $this->onlineService = $onlineService;
        $this->offlineService = $offlineService;
        $this->allService = $allService;
    }

    public function exportOnline(Request $request): BinaryFileResponse
    {
        ini_set('max_execution_time', 300);
        ini_set('memory_limit', '512M');
        if (function_exists('set_time_limit')) {
            @set_time_limit(300);
        }

        return $this->onlineService->download($request->all());
    }

    public function exportOffline(Request $request): BinaryFileResponse
    {
        ini_set('max_execution_time', 300);
        ini_set('memory_limit', '512M');
        if (function_exists('set_time_limit')) {
            @set_time_limit(300);
        }

        return $this->offlineService->download($request->all());
    }

    public function exportAll(Request $request): BinaryFileResponse
    {
        ini_set('max_execution_time', 300);
        ini_set('memory_limit', '512M');
        if (function_exists('set_time_limit')) {
            @set_time_limit(300);
        }

        return $this->allService->download($request->all());
    }
}
