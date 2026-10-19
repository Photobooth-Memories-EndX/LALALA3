<?php

namespace App\Exports;

use App\Services\Learning\UserRecordOfflineService;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithColumnFormatting;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\NumberFormat;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class ExportMyOfflineLearningRecord implements FromArray, ShouldAutoSize, WithColumnFormatting, WithEvents, WithStyles
{
    private Collection $rows;

    public function __construct(private readonly array $filters = [])
    {
        $this->rows = collect();
    }

    private function toExcelTime(?string $dateTimeOrTime): float|string
    {
        if (empty($dateTimeOrTime) || $dateTimeOrTime === '-' || $dateTimeOrTime === '—') {
            return '-';
        }

        try {
            $dt = Carbon::parse($dateTimeOrTime);
            return (($dt->hour * 3600) + ($dt->minute * 60) + $dt->second) / 86400;
        } catch (\Throwable) {
            return '-';
        }
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
            $durationSeconds = max(0, (int) ($row['duration_seconds'] ?? $row['duration_sort'] ?? 0));
            $durationCell = $durationSeconds > 0 ? ($durationSeconds / 86400) : '-';

            $lhValue = round((float) ($row['learning_hour_value'] ?? 0), 2);

            $rawProgress = $row['progress'] ?? 0;
            $progressInt = is_numeric($rawProgress) ? max(0, min(100, (int) $rawProgress)) : 0;
            $progressCell = $progressInt / 100;
            $completionStatus = $progressInt >= 100 ? 'Complete' : 'Ongoing';

            $attendance = !empty($row['attendance_status']) ? $row['attendance_status'] : '-';
            $requirement = $row['requirement'] ?? 'Non-Mandatory';

            $startDate = !empty($row['start_date']) ? $row['start_date'] : '-';
            $endDate = !empty($row['finish_date']) ? $row['finish_date'] : ($startDate !== '-' ? $startDate : '-');
            $startTime = $this->toExcelTime($row['start_time'] ?? null);
            $endTime = $this->toExcelTime($row['end_time'] ?? null);

            $hasScore = ($row['score'] !== null && $row['score'] !== '' && is_numeric($row['score']));
            $scoreVal = $hasScore ? (float) $row['score'] : '-';
            $minScore = (float) ($row['min_score'] ?? 70);
            $assessmentStatus = $hasScore
                ? ($scoreVal >= $minScore ? 'Passed' : 'Failed')
                : '-';

            $certificate = ($row['has_certificate'] ?? false) ? 'Available' : 'Not Available';

            $output[] = [
                $number++,
                $row['title'] ?? '-',
                $requirement,
                $row['type'] ?? '-',
                $row['category'] ?? '-',
                $row['classification'] ?? '-',
                $durationCell,
                $lhValue,
                $completionStatus,
                $progressCell,
                $attendance,
                $startDate,
                $endDate,
                $startTime,
                $endTime,
                $assessmentStatus,
                $scoreVal,
                $certificate,
            ];
        }

        return $output;
    }

    public function headings(): array
    {
        return [
            'No',
            'Training Title',
            'Requirement',
            'Type',
            'Category',
            'Classification',
            'Duration',
            'Learning Hour',
            'Completion Status',
            'Progress',
            'Attendance',
            'Start Date',
            'End Date',
            'Start Time',
            'End Time',
            'Assessment Status',
            'Assessment Score',
            'Certificate',
        ];
    }

    public function columnFormats(): array
    {
        return [
            'A' => NumberFormat::FORMAT_NUMBER,
            'B' => NumberFormat::FORMAT_GENERAL,
            'C' => NumberFormat::FORMAT_GENERAL,
            'D' => NumberFormat::FORMAT_GENERAL,
            'E' => NumberFormat::FORMAT_GENERAL,
            'F' => NumberFormat::FORMAT_GENERAL,
            'G' => NumberFormat::FORMAT_DATE_TIME4,
            'H' => NumberFormat::FORMAT_NUMBER_00,
            'I' => NumberFormat::FORMAT_GENERAL,
            'J' => NumberFormat::FORMAT_PERCENTAGE,
            'K' => NumberFormat::FORMAT_GENERAL,
            'L' => NumberFormat::FORMAT_GENERAL,
            'M' => NumberFormat::FORMAT_GENERAL,
            'N' => NumberFormat::FORMAT_DATE_TIME4,
            'O' => NumberFormat::FORMAT_DATE_TIME4,
            'P' => NumberFormat::FORMAT_GENERAL,
            'Q' => NumberFormat::FORMAT_GENERAL,
            'R' => NumberFormat::FORMAT_GENERAL,
        ];
    }

    public function styles(Worksheet $sheet): array
    {
        return [
            1 => [
                'font' => [
                    'bold'  => true,
                    'color' => ['rgb' => 'FFFFFF'],
                ],
                'fill' => [
                    'fillType'   => Fill::FILL_SOLID,
                    'startColor' => ['rgb' => '002B49'],
                ],
            ],
        ];
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event) {
                $sheet = $event->sheet->getDelegate();
                $sheet->freezePane('A2');
                $highestColumn = $sheet->getHighestColumn();
                $highestRow = max(1, $sheet->getHighestRow());

                $sheet->getStyle('A1:' . $highestColumn . '1')
                    ->getAlignment()
                    ->setHorizontal(Alignment::HORIZONTAL_CENTER);

                $sheet->getStyle('A1:' . $highestColumn . $highestRow)
                    ->getAlignment()
                    ->setVertical(Alignment::VERTICAL_CENTER);

                if ($highestRow > 1) {
                    $sheet->getStyle('A2:A' . $highestRow)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
                    $sheet->getStyle('C2:E' . $highestRow)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
                    $sheet->getStyle('G2:R' . $highestRow)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
                }
            },
        ];
    }
}
