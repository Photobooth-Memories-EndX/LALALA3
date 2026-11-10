<?php

namespace App\Services\Manage\KpOwl;

use App\Exports\Manage\KpOwl\KpOwlRecordExport;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class KpOwlExportService
{
    /**
     * Download streaming XLSX export with full 52 columns.
     */
    public function download(array $filters = [], ?string $fileName = null): BinaryFileResponse
    {
        ini_set('memory_limit', '2048M');
        ini_set('max_execution_time', 600);
        if (function_exists('set_time_limit')) {
            @set_time_limit(600);
        }

        $fileName = $fileName ?: KpOwlRecordExport::generateFileName();
        $export = new KpOwlRecordExport($filters, $fileName);

        return $export->download($fileName);
    }
}
