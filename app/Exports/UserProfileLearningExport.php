<?php

namespace App\Exports;

use App\Models\User;
use App\Services\Learning\LearningDatatableService;
use App\Services\Learning\LearningExportService;
use Illuminate\Contracts\Support\Responsable;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use Maatwebsite\Excel\Concerns\Exportable;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithColumnWidths;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class UserProfileLearningExport implements FromCollection, WithHeadings, WithMapping, WithColumnWidths, WithStyles, WithEvents, Responsable
{
    use Exportable;

    protected User $user;
    protected array $filters;
    protected int $rowNumber = 0;

    public function __construct(User $user, array $filters = [])
    {
        $this->user = $user;
        $this->filters = $filters;
        $this->rowNumber = 0;
    }

    public function collection(): Collection
    {
        return LearningDatatableService::buildBaseQuery(array_merge($this->filters, [
            'user_id' => $this->user->id,
            'employee_id' => $this->user->id,
        ]))->orderBy('unified.created_at', 'desc')->get();
    }

    public function headings(): array
    {
        return LearningExportService::headings();
    }

    public function columnWidths(): array
    {
        return LearningExportService::columnWidths();
    }

    public function map($row): array
    {
        $this->rowNumber++;

        return LearningExportService::mapRow($row, $this->rowNumber);
    }

    public function styles(Worksheet $sheet)
    {
        return LearningExportService::styles($sheet);
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event) {
                LearningExportService::afterSheetEvent($event);
            },
        ];
    }

    public function toResponse($request)
    {
        $filename = sprintf(
            '%s_%s_learning_history.xlsx',
            now()->format('Ymd_His'),
            Str::slug($this->user->name ?: 'user')
        );

        $filters = array_merge($this->filters, [
            'user_id' => $this->user->id,
            'employee_id' => $this->user->id,
        ]);

        return (new LearningExport($filters))->download($filename);
    }
}
