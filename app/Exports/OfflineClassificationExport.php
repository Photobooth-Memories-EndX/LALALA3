<?php

namespace App\Exports;

class OfflineClassificationExport extends OfflineTrainingExport
{
    /**
     * Classification-scoped Excel export for Non KP OWL offline records.
     *
     * @param  string  $classification
     * @param  mixed  $filters
     */
    public function __construct(string $classification, $filters = [])
    {
        parent::__construct($classification, $filters);
    }
}
