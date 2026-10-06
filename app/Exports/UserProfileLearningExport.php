<?php

namespace App\Exports;

use App\Models\User;
use App\Services\User\UserProfileLearningHistoryService;
use App\Support\DurationFormatter;
use Maatwebsite\Excel\Concerns\Exportable;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class UserProfileLearningExport implements FromQuery, WithHeadings, WithMapping, ShouldAutoSize, WithStyles, WithEvents
{
    use Exportable;

    protected User $user;
    protected array $filters;
    protected int $rowNumber = 0;

    public function __construct(User $user, array $filters = [])
    {
        $this->user = $user;
        $this->filters = $filters;
    }

    public function query()
    {
        return UserProfileLearningHistoryService::buildQuery($this->user, $this->filters)
            ->orderBy('content_users.created_at', 'desc');
    }

    public function headings(): array
    {
        return [
            'No',
            'Status',
            'Employee Name',
            'Learning Title',
            'Type',
            'Classification',
            'Duration',
            'Date',
            'Progress',
        ];
    }

    public function map($row): array
    {
        $this->rowNumber++;
        $content = $row->content;
        $classification = $content?->classification;

        return [
            $this->rowNumber,
            (int) ($row->finish ?? 0) === 1 ? 'Finished' : 'Ongoing',
            $row->user?->name ?? $this->user->name ?? '-',
            $content?->title ?? '-',
            $content?->type ?? '-',
            $classification?->classification_name ?? '-',
            DurationFormatter::format($content?->duration ?? null),
            optional($row->created_at)->format('d M Y') ?: '-',
            ((int) ($row->progress ?? 0)) . '%',
        ];
    }

    public function styles(Worksheet $sheet)
    {
        return [
            1 => [
                'font' => [
                    'bold' => true,
                    'color' => ['rgb' => 'FFFFFF'],
                    'size' => 11,
                ],
                'fill' => [
                    'fillType' => Fill::FILL_SOLID,
                    'startColor' => ['rgb' => '003B6D'],
                ],
                'alignment' => [
                    'horizontal' => Alignment::HORIZONTAL_CENTER,
                    'vertical' => Alignment::VERTICAL_CENTER,
                ],
            ],
        ];
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event) {
                $highestRow = $event->sheet->getHighestRow();
                $highestColumn = $event->sheet->getHighestColumn();

                $event->sheet->getDelegate()->getStyle("A1:{$highestColumn}{$highestRow}")->applyFromArray([
                    'borders' => [
                        'allBorders' => [
                            'borderStyle' => Border::BORDER_THIN,
                            'color' => ['rgb' => 'D1D5DB'],
                        ],
                    ],
                ]);

                // Center align specific columns (No, Status, Type, Duration, Date, Progress)
                $event->sheet->getDelegate()->getStyle("A2:B{$highestRow}")
                    ->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
                $event->sheet->getDelegate()->getStyle("E2:E{$highestRow}")
                    ->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
                $event->sheet->getDelegate()->getStyle("G2:I{$highestRow}")
                    ->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
            },
        ];
    }
}
