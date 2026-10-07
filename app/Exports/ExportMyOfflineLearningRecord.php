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

                // Extended fields
                $row['batch'] ?? '-',
                $row['sub_classification'] ?? '-',
                $row['delivery_method'] ?? '-',
                $row['sub_delivery_method'] ?? '-',
                $row['provider'] ?? '-',
                $row['venue'] ?? '-',
                $row['instructor'] ?? '-',
                $row['start_time'] ?? '-',
                $row['end_time'] ?? '-',
                $row['attendance_status'] ?? '-',
                $row['attendance_reason'] ?? '-',
                $row['l1_program'] ?? '-',
                $row['l2_numerical'] !== null ? $row['l2_numerical'] : '-',
                $row['l2_verbal'] ?? '-',
                $row['l3_numerical'] !== null ? $row['l3_numerical'] : '-',
                $row['l3_verbal'] ?? '-',
                $row['pic'] ?? '-',
                $row['bond_status'] ?? '-',
                $row['bond_end_date'] ?: '-',
                $row['bond_amount_formatted'] ?? '-',
                $row['employee_id'] ?? '-',
                $row['grade'] ?? '-',
                $row['job_position'] ?? '-',
                $row['team'] ?? '-',
                $row['department'] ?? '-',
                $row['company'] ?? '-',
            ];
        }

        $durationSeconds = (int) $this->rows->sum(fn (array $r) => max(0, (int) ($r['duration_seconds'] ?? 0)));
        $learningHours = (float) $this->rows->sum(fn (array $r) => (float) ($r['learning_hour_value'] ?? 0));

        $output[] = array_fill(0, count($this->headings()), '');
        $summaryRowData = array_merge([
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
        ], array_fill(0, max(0, count($this->headings()) - 12), ''));
        $output[] = $summaryRowData;

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

            // Extended Personal Export Columns
            'Batch',
            'Sub Classification',
            'Delivery Method',
            'Sub Delivery Method',
            'Provider',
            'Venue',
            'Instructor',
            'Start Time',
            'End Time',
            'Attendance',
            'Attendance Reason',
            'L1 Program',
            'L2 Numerical',
            'L2 Verbal',
            'L3 Numerical',
            'L3 Verbal',
            'PIC',
            'Bond Status',
            'Bond End Date',
            'Bond Amount',
            'Employee ID',
            'Grade',
            'Job Position',
            'Team',
            'Department',
            'Company',
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
            $highestColumn = $sheet->getHighestColumn();
            $highestRow = max(1, $sheet->getHighestRow());

            $sheet->getStyle('A1:' . $highestColumn . '1')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
            $sheet->getStyle('A1:' . $highestColumn . $highestRow)->getAlignment()->setVertical(Alignment::VERTICAL_CENTER);

            $summaryRow = $highestRow;
            $sheet->getStyle('A' . $summaryRow . ':' . $highestColumn . $summaryRow)->applyFromArray([
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
