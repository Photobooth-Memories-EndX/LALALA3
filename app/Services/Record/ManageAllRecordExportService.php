<?php

namespace App\Services\Record;

use App\Exports\AllRecordExport;
use App\Exports\Manage\ClassificationAllRecordExport;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class ManageAllRecordExportService
{
    /**
     * All Record Export: Include standalone content courses AND parent Playlist title rows ONLY
     * (do NOT expand or list child sub-contents under playlists).
     */
    public function download(array $filters = [], ?string $fileName = null): BinaryFileResponse
    {
        ini_set('memory_limit', '2048M');
        ini_set('max_execution_time', 600);
        if (function_exists('set_time_limit')) {
            @set_time_limit(600);
        }

        $fileName = $fileName ?: AllRecordExport::generateFileName();
        $export = new AllRecordExport($filters, $fileName);

        return $export->download($fileName);
    }

    /**
     * All Record Classification Export
     */
    public function downloadClassification(?string $classification = null, array $filters = [], ?string $fileName = null)
    {
        ini_set('memory_limit', '2048M');
        ini_set('max_execution_time', 600);
        if (function_exists('set_time_limit')) {
            @set_time_limit(600);
        }

        $fileName = $fileName ?: ClassificationAllRecordExport::generateFileName();
        $export = new ClassificationAllRecordExport($classification, $filters, $fileName);

        return $export->download($fileName);
    }
}
