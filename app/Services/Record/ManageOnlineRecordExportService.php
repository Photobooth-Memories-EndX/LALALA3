<?php

namespace App\Services\Record;

use App\Exports\OnlineRecordExport;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class ManageOnlineRecordExportService
{
    /**
     * KP OWL Export: Include parent Playlist rows AND expand child Content rows underneath.
     * Highlight main Playlist rows with light blue/gray fill and bold text.
     */
    public function download(array $filters = [], ?string $fileName = null): BinaryFileResponse
    {
        ini_set('max_execution_time', 300);
        ini_set('memory_limit', '512M');
        if (function_exists('set_time_limit')) {
            @set_time_limit(300);
        }

        $fileName = $fileName ?: OnlineRecordExport::generateFileName();
        $export = new OnlineRecordExport($filters, $fileName);

        return $export->download($fileName);
    }
}
