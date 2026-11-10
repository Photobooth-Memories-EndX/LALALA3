<?php

namespace App\Exports;

use App\Http\Controllers\Manage\Record\RecordOfflineController;
use App\Services\Record\RecordOfflineQueryService;

class OfflineClassificationExport extends OfflineRecordExport
{
    protected ?string $classification;

    /**
     * Classification-scoped Excel export for Non KP OWL offline records.
     *
     * @param  string  $classification
     * @param  mixed  $filters
     * @param  string|null  $fileName
     */
    public function __construct(string $classification, $filters = [], ?string $fileName = null)
    {
        $this->classification = $classification;

        if ($fileName === null) {
            $resolved = RecordOfflineController::resolveClassification($classification);
            $slug = $resolved['slug'] ?: 'unclassified';
            $fileName = sprintf('non-kp-owl-%s-%s.xlsx', $slug, now()->format('Y-m-d'));
        }

        parent::__construct($filters, $fileName);
    }

    public function query()
    {
        return RecordOfflineQueryService::buildClassificationQuery($this->classification, $this->filters)
            ->orderBy('ot.start_date', 'desc')
            ->orderBy('ot.id', 'desc')
            ->orderBy('otu.id', 'asc');
    }
}
