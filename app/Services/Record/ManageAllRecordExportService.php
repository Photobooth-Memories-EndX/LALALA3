<?php

namespace App\Services\Record;

use App\Exports\AllRecordExport;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class ManageAllRecordExportService
{
    /**
     * All Record Export: Include standalone content courses AND parent Playlist title rows ONLY
     * (do NOT expand or list child sub-contents under playlists).
     */
    public function download(array $filters = [], ?string $fileName = null): BinaryFileResponse
    {
        ini_set('max_execution_time', 300);
        ini_set('memory_limit', '512M');
        if (function_exists('set_time_limit')) {
            @set_time_limit(300);
        }

        $fileName = $fileName ?: AllRecordExport::generateFileName();
        $export = new AllRecordExport($filters, $fileName);

        return $export->download($fileName);
    }
}
