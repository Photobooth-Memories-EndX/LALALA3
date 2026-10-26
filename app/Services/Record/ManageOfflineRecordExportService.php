<?php

namespace App\Services\Record;

use App\Exports\OfflineRecordExport;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class ManageOfflineRecordExportService
{
    /**
     * Non-KP OWL Export: Include offline course items only (no playlists or child content expansion).
     */
    public function download(array $filters = [], ?string $fileName = null): BinaryFileResponse
    {
        ini_set('max_execution_time', 300);
        ini_set('memory_limit', '512M');
        if (function_exists('set_time_limit')) {
            @set_time_limit(300);
        }

        $fileName = $fileName ?: OfflineRecordExport::generateFileName();
        $export = new OfflineRecordExport($filters, $fileName);

        return $export->download($fileName);
    }
}
