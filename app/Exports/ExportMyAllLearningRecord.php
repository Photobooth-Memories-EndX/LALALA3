<?php

namespace App\Exports;

use App\Services\CertificateEligibilityService;
use App\Services\Learning\UserRecordAllService;
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

class ExportMyAllLearningRecord implements FromArray, ShouldAutoSize, WithEvents, WithStyles
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

        $allService = app(UserRecordAllService::class);
        $eligibilityService = app(CertificateEligibilityService::class);
        $this->rows = $allService->getRecords((int) $user->id, $user->id_emp, $eligibilityService, $this->filters);

        $output = [$this->headings()];
        $number = 1;

        foreach ($this->rows as $row) {
            $output[] = [
                $number++,
                strtoupper((string) ($row['source'] ?? 'ONLINE')),
                $row['title'] ?? '-',
                $row['type'] ?? '-',
                $row['category'] ?? '-',
                $row['classification'] ?? '-',
                $row['duration'] ?? '-',
                round((float) ($row['learning_hour_value'] ?? 0), 2),
                ($row['progress'] ?? 0) . '%',
                $row['attendance_status'] ?? '—',
                $row['date_display'] ?: ($row['finish_date'] ?: ($row['start_date'] ?: '-')),
                $row['score'] !== null ? $row['score'] : '-',
                ($row['has_certificate'] ?? false) ? 'Yes' : 'No',
            ];
        }

        $durationSeconds = (int) $this->rows->sum(fn (array $r) => max(0, (int) ($r['duration_seconds'] ?? 0)));
        $learningHours = (float) $this->rows->sum(fn (array $r) => (float) ($row_lh = $r['learning_hour_value'] ?? 0));

        $output[] = array_fill(0, count($this->headings()), '');
        $output[] = [
            'TOTAL',
            '',
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
            'Source',
            'Training Title',
            'Type',
            'Category',
            'Classification',
            'Duration',
            'Learning Hour',
            'Progress',
            'Attendance',
            'Date',
            'Score',
            'Certificate',
        ];
    }

    public function styles(Worksheet $sheet): array
    {
        return [1 => ['font' => ['bold' => true], 'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'EDE9FE']]]];
    }

    public function registerEvents(): array
    {
        return [AfterSheet::class => function (AfterSheet $event) {
            $sheet = $event->sheet->getDelegate();
            $sheet->freezePane('A2');
            $sheet->getStyle('A1:M1')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
            $sheet->getStyle('A1:M' . max(1, $sheet->getHighestRow()))->getAlignment()->setVertical(Alignment::VERTICAL_CENTER);

            $summaryRow = $sheet->getHighestRow();
            $sheet->getStyle('A' . $summaryRow . ':M' . $summaryRow)->applyFromArray([
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
            $sheet->getStyle('G' . $summaryRow . ':H' . $summaryRow)
                ->getAlignment()
                ->setHorizontal(Alignment::HORIZONTAL_RIGHT);
            $sheet->getStyle('H2:H' . $summaryRow)
                ->getNumberFormat()
                ->setFormatCode('0.00');
        }];
    }
}
