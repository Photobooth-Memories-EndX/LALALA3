<?php

namespace App\Exports;

use App\Exports\Manage\ClassificationAllRecordExport;

class AllClassificationExport extends ClassificationAllRecordExport
{
    /**
     * Classification-scoped Excel export for All KP OWL combined records.
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
