<?php

namespace App\Exports\Sheets;

use App\Services\Record\OfflineTrainingExcelSchema;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Events\AfterSheet;

class OfflineTrainingInstructionsSheet implements WithTitle, WithEvents
{
    public function title(): string
    {
        return OfflineTrainingExcelSchema::INSTRUCTIONS_SHEET_TITLE;
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event) {
                $delegate = $event->sheet->getDelegate();
                OfflineTrainingExcelSchema::populateInstructionsSheet($delegate);

                // Ensure Sheet 1 (index 0) remains the active sheet on open
                $delegate->getParent()->setActiveSheetIndex(0);
            },
        ];
    }
}
