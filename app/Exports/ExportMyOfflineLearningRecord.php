<?php

namespace App\Exports;

use App\Services\Learning\UserRecordOfflineService;
use App\Support\DurationFormatter;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class ExportMyOfflineLearningRecord implements FromArray, ShouldAutoSize, WithEvents, WithStyles
{
    private Collection $rows;

    public function __construct(private readonly array $filters = [])
    {
        $this->rows = collect();
    }

    public function array(): array
    {
        $user = Auth::user();
        if (!$user) {
            return [$this->headings()];
        }

        $offlineService = app(UserRecordOfflineService::class);
        $this->rows = $offlineService->getRecords((int) $user->id, $user->id_emp, $this->filters);

        $output = [$this->headings()];
        $number = 1;

        foreach ($this->rows as $row) {
            $output[] = [
                $number++,
                $row['title'] ?? '-',
                $row['type'] ?? '-',
                $row['category'] ?? '-',
                $row['classification'] ?? '-',
                $row['duration'] ?? '-',
                round((float) ($row['learning_hour_value'] ?? 0), 2),
                ($row['progress'] ?? 0) . '%',
                $row['start_date'] ?: '-',
                $row['finish_date'] ?: '-',
                $row['score'] !== null ? $row['score'] : '-',
                $row['certificate'] ?: '-',
            ];
        }

        $durationSeconds = (int) $this->rows->sum(fn (array $r) => max(0, (int) ($r['duration_seconds'] ?? 0)));
        $learningHours = (float) $this->rows->sum(fn (array $r) => (float) ($r['learning_hour_value'] ?? 0));

        $output[] = array_fill(0, count($this->headings()), '');
        $output[] = [
            'TOTAL',
            '',
            '',
            '',
            '',
            DurationFormatter::fromSeconds($durationSeconds),
            round($learningHours, 2),
            '',
            '',
            '',
            '',
            '',
        ];

        return $output;
    }

    public function headings(): array
    {
        return [
            'No',
            'Training Title',
            'Type',
            'Category',
            'Classification',
            'Duration',
            'Learning Hour',
            'Learning Progress',
            'Start Date',
            'Finish Date',
            'Assessment Score',
            'Certificate',
        ];
    }

    public function styles(Worksheet $sheet): array
    {
        return [1 => ['font' => ['bold' => true], 'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'FEF3C7']]]];
    }

    public function registerEvents(): array
    {
        return [AfterSheet::class => function (AfterSheet $event) {
            $sheet = $event->sheet->getDelegate();
            $sheet->freezePane('A2');
            $sheet->getStyle('A1:L1')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
            $sheet->getStyle('A1:L' . max(1, $sheet->getHighestRow()))->getAlignment()->setVertical(Alignment::VERTICAL_CENTER);

            $summaryRow = $sheet->getHighestRow();
            $sheet->getStyle('A' . $summaryRow . ':L' . $summaryRow)->applyFromArray([
                'font' => ['bold' => true],
                'fill' => [
                    'fillType' => Fill::FILL_SOLID,
                    'startColor' => ['rgb' => 'EDEDED'],
                ],
                'borders' => [
                    'top' => [
                        'borderStyle' => Border::BORDER_THIN,
                        'color' => ['rgb' => '808080'],
                    ],
                ],
            ]);
            $sheet->getStyle('F' . $summaryRow . ':G' . $summaryRow)
                ->getAlignment()
                ->setHorizontal(Alignment::HORIZONTAL_RIGHT);
            $sheet->getStyle('G2:G' . $summaryRow)
                ->getNumberFormat()
                ->setFormatCode('0.00');
        }];
    }
}
