<?php

namespace App\Exports;

use App\Exports\Sheets\OfflineParticipantDataSheet;
use App\Exports\Sheets\OfflineTrainingInstructionsSheet;
use Illuminate\Contracts\Support\Responsable;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Maatwebsite\Excel\Concerns\Exportable;
use Maatwebsite\Excel\Concerns\WithMultipleSheets;

class OfflineParticipantExport implements WithMultipleSheets, Responsable
{
    use Exportable;

    protected array $participant;
    protected array $filters = [];
    protected string $fileName;

    public function __construct(array $participant, $filters = [])
    {
        $this->participant = $participant;
        $this->filters = $filters instanceof Request ? $filters->all() : (array) $filters;

        $identifier = $participant['employee_id'] ?: ($participant['name'] ?: 'participant');
        $slug = Str::slug($identifier) ?: 'participant';
        $this->fileName = sprintf('non-kp-owl-participant-%s-%s.xlsx', $slug, now()->format('Y-m-d'));
    }

    public function getFileName(): string
    {
        return $this->fileName;
    }

    public function sheets(): array
    {
        return [
            new OfflineParticipantDataSheet($this->participant, $this->filters),
            new OfflineTrainingInstructionsSheet(),
        ];
    }
}
