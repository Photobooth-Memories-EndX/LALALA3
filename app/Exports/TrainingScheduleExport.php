<?php

namespace App\Exports;

use Carbon\Carbon;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\Exportable;
use Maatwebsite\Excel\Concerns\FromCollection;
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

class TrainingScheduleExport implements FromCollection, WithHeadings, WithMapping, ShouldAutoSize, WithStyles, WithEvents
{
    use Exportable;

    /**
     * @var Collection
     */
    protected Collection $records;

    /**
     * @var int
     */
    protected int $rowNumber = 0;

    /**
     * @param mixed $records
     */
    public function __construct($records)
    {
        $this->records = $records instanceof Collection ? $records : collect($records);
        $this->rowNumber = 0;
    }

    /**
     * @return Collection
     */
    public function collection(): Collection
    {
        return $this->records;
    }

    /**
     * Define the Excel sheet headers.
     *
     * @return array
     */
    public function headings(): array
    {
        return [
            'No',
            'Training Code',
            'Training Title',
            'Category',
            'Instructor',
            'Location / Venue',
            'Start Date',
            'End Date',
            'Start Time',
            'End Time',
            'Quota',
            'Registered Count',
            'Status',
            'Created At',
        ];
    }

    /**
     * Map each row into columns.
     *
     * @param mixed $item
     * @return array
     */
    public function map($item): array
    {
        $this->rowNumber++;

        $startDate = $item->start_date ? Carbon::parse($item->start_date)->format('Y-m-d') : '-';
        $endDate = $item->end_date ? Carbon::parse($item->end_date)->format('Y-m-d') : '-';
        $startTime = $item->start_time ? substr($item->start_time, 0, 5) : '-';
        $endTime = $item->end_time ? substr($item->end_time, 0, 5) : '-';

        return [
            $this->rowNumber,
            $item->training_code ?? ('PTKP-TR-' . $item->id),
            $item->title ?? ($item->course_name ?? '-'),
            $item->category ?? 'General',
            $item->instructor ?? '-',
            $item->location ?? ($item->venue ?? '-'),
            $startDate,
            $endDate,
            $startTime,
            $endTime,
            (int) ($item->quota ?? 0),
            (int) ($item->registrations_count ?? 0),
            $item->status ?? 'Open Registration',
            $item->created_at ? Carbon::parse($item->created_at)->format('Y-m-d H:i:s') : '-',
        ];
    }

    /**
     * Header styles matching Krakatau Posco / OWL brand theme.
     *
     * @param Worksheet $sheet
     * @return array
     */
    public function styles(Worksheet $sheet): array
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
                    'startColor' => ['rgb' => '002B49'], // Krakatau Posco Deep Navy
                ],
                'alignment' => [
                    'horizontal' => Alignment::HORIZONTAL_CENTER,
                    'vertical' => Alignment::VERTICAL_CENTER,
                ],
            ],
        ];
    }

    /**
     * Sheet border and column alignments.
     *
     * @return array
     */
    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event) {
                $highestRow = $event->sheet->getHighestRow();
                $highestColumn = $event->sheet->getHighestColumn();

                // Set header row height
                $event->sheet->getDelegate()->getRowDimension(1)->setRowHeight(28);

                // Apply borders
                $event->sheet->getDelegate()->getStyle("A1:{$highestColumn}{$highestRow}")->applyFromArray([
                    'borders' => [
                        'allBorders' => [
                            'borderStyle' => Border::BORDER_THIN,
                            'color' => ['rgb' => 'D1D5DB'],
                        ],
                    ],
                ]);

                if ($highestRow > 1) {
                    // Center align specific columns (No, Training Code)
                    $event->sheet->getDelegate()->getStyle("A2:B{$highestRow}")
                        ->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

                    // Center align dates, times, quota, count, status, created at
                    $event->sheet->getDelegate()->getStyle("G2:N{$highestRow}")
                        ->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

                    // Left align Title, Category, Instructor, Location
                    $event->sheet->getDelegate()->getStyle("C2:F{$highestRow}")
                        ->getAlignment()->setHorizontal(Alignment::HORIZONTAL_LEFT);
                }
            },
        ];
    }
}
