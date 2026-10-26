<?php

namespace App\Services\Record;

use App\Exports\OfflineTrainingExport;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class ManageOfflineRecordExportService
{
    /**
     * Non-KP OWL Export: Include list of offline courses only (no playlists or sub-contents).
     */
    public function download(array $filters = [], ?string $fileName = null): BinaryFileResponse
    {
        ini_set('max_execution_time', 300);
        ini_set('memory_limit', '512M');
        if (function_exists('set_time_limit')) {
            @set_time_limit(300);
        }

        $fileName = $fileName ?: OfflineTrainingExcelSchema::generateManageFileName();
        $export = new OfflineTrainingExport(null, $filters, $fileName);

        return $export->download($fileName);
    }
}
