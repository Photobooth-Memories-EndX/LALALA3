<?php

namespace App\Exports\Manage;

use App\Exports\OfflineRecordExport;
use App\Http\Controllers\Manage\Record\RecordOfflineController;
use App\Services\Record\RecordOfflineQueryService;

class ClassificationOfflineRecordExport extends OfflineRecordExport
{
    protected ?string $classification;

    public function __construct(?string $classification = null, $filters = [], ?string $fileName = null)
    {
        $this->classification = !empty($classification) ? (string) $classification : null;

        if ($fileName !== null) {
            $resolvedFileName = $fileName;
        } elseif ($this->classification !== null) {
            $resolved = RecordOfflineController::resolveClassification($this->classification);
            $slug = $resolved['slug'] ?: 'unclassified';
            $resolvedFileName = sprintf('non-kp-owl-%s-%s.xlsx', $slug, now()->format('Y-m-d'));
        } else {
            $resolvedFileName = self::generateFileName();
        }

        parent::__construct($filters, $resolvedFileName);
    }

    public function query()
    {
        if ($this->classification !== null) {
            return RecordOfflineQueryService::buildClassificationQuery($this->classification, $this->filters)
                ->orderBy('ot.start_date', 'desc')
                ->orderBy('ot.id', 'desc')
                ->orderBy('otu.id', 'asc');
        }

        return parent::query();
    }
}
