<?php

namespace App\Exports;

use App\Services\Record\RecordOfflineQueryService;
use Illuminate\Support\Str;

class OfflineParticipantExport extends OfflineRecordExport
{
    protected array $participant;

    public function __construct(array $participant, $filters = [], ?string $fileName = null)
    {
        $this->participant = $participant;

        if ($fileName === null) {
            $identifier = $participant['employee_id'] ?: ($participant['name'] ?: 'participant');
            $slug = Str::slug($identifier) ?: 'participant';
            $fileName = sprintf('non-kp-owl-participant-%s-%s.xlsx', $slug, now()->format('Y-m-d'));
        }

        parent::__construct($filters, $fileName);
    }

    public function query()
    {
        return RecordOfflineQueryService::buildParticipantQuery($this->participant, $this->filters)
            ->orderBy('ot.start_date', 'desc')
            ->orderBy('ot.id', 'desc');
    }
}
