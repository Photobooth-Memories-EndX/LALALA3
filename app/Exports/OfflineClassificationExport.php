<?php

namespace App\Exports;

use App\Exports\Manage\ClassificationOfflineRecordExport;

class OfflineClassificationExport extends ClassificationOfflineRecordExport
{
    /**
     * Classification-scoped Excel export for Non KP OWL offline records.
     *
     * @param  string  $classification
     * @param  mixed  $filters
     * @param  string|null  $fileName
     */
    public function __construct(string $classification, $filters = [], ?string $fileName = null)
    {
        parent::__construct($classification, $filters, $fileName);
    }
}
