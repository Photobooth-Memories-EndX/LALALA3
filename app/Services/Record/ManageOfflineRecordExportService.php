<?php

namespace App\Services\Record;

use App\Exports\Manage\ClassificationOfflineRecordExport;
use App\Exports\OfflineRecordExport;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class ManageOfflineRecordExportService
{
    /**
     * Non-KP OWL Export: Include offline course items only (no playlists or child content expansion).
     */
    public function download(array $filters = [], ?string $fileName = null): BinaryFileResponse
    {
        ini_set('memory_limit', '2048M');
        ini_set('max_execution_time', 600);
        if (function_exists('set_time_limit')) {
            @set_time_limit(600);
        }

        $fileName = $fileName ?: OfflineRecordExport::generateFileName();
        $export = new OfflineRecordExport($filters, $fileName);

        return $export->download($fileName);
    }

    /**
     * Non-KP OWL Classification Export
     */
    public function downloadClassification(?string $classification = null, array $filters = [], ?string $fileName = null)
    {
        ini_set('memory_limit', '2048M');
        ini_set('max_execution_time', 600);
        if (function_exists('set_time_limit')) {
            @set_time_limit(600);
        }

        $fileName = $fileName ?: ClassificationOfflineRecordExport::generateFileName();
        $export = new ClassificationOfflineRecordExport($classification, $filters, $fileName);

        return $export->download($fileName);
    }
}
