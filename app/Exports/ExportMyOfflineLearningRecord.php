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
use PhpOffice\PhpSpreadsheet\Shared\Date as ExcelDate;
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

    private function toExcelDate(?string $dateStr): float|int|string
    {
        if (empty($dateStr) || $dateStr === '-' || $dateStr === '—') {
            return '-';
        }

        try {
            $dt = Carbon::parse($dateStr)->startOfDay();
            return ExcelDate::PHPToExcel($dt);
        } catch (\Throwable) {
            return '-';
        }
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

        $empId = !empty($user->id_emp) ? (string) $user->id_emp : '-';
        $userName = !empty($user->name) ? (string) $user->name : '-';
        $userTeam = !empty($user->team) ? (string) $user->team : (!empty($user->department) ? (string) $user->department : '-');

        $offlineService = app(UserRecordOfflineService::class);
        $this->rows = $offlineService->getRecords((int) $user->id, $user->id_emp, $this->filters);

        $output = [$this->headings()];
        $number = 1;

        foreach ($this->rows as $row) {
            $rowEmpId = (!empty($row['employee_id']) && $row['employee_id'] !== '-') ? (string) $row['employee_id'] : $empId;
            $rowName = (!empty($row['participant_name']) && $row['participant_name'] !== '-') ? (string) $row['participant_name'] : $userName;
            $rowTeam = (!empty($row['team']) && $row['team'] !== '-') ? (string) $row['team'] : $userTeam;

            $durationSeconds = max(0, (int) ($row['duration_seconds'] ?? $row['duration_sort'] ?? 0));
            $durationCell = $durationSeconds > 0 ? ($durationSeconds / 86400) : '-';

            $lhValue = round((float) ($row['learning_hour_value'] ?? 0), 2);

            $rawProgress = $row['progress'] ?? 0;
            $progressInt = is_numeric($rawProgress) ? max(0, min(100, (int) $rawProgress)) : 0;
            $progressCell = $progressInt / 100;

            $startRaw = !empty($row['start_date']) ? $row['start_date'] : '';
            $finishRaw = !empty($row['finish_date']) ? $row['finish_date'] : ($startRaw !== '' ? $startRaw : '');
            $startDate = $this->toExcelDate($startRaw);
            $endDate = $this->toExcelDate($finishRaw);
            $startTime = $this->toExcelTime($row['start_time'] ?? null);
            $endTime = $this->toExcelTime($row['end_time'] ?? null);

            $requirement = $row['requirement'] ?? 'Non-Mandatory';
            $deliveryMethod = (!empty($row['delivery_method']) && $row['delivery_method'] !== '—') ? $row['delivery_method'] : '-';
            $provider = (!empty($row['provider_type']) && $row['provider_type'] !== '—')
                ? $row['provider_type']
                : ((!empty($row['provider']) && $row['provider'] !== '—') ? $row['provider'] : '-');
            $providerName = (!empty($row['raw_provider_name']) && $row['raw_provider_name'] !== '—')
                ? $row['raw_provider_name']
                : ((!empty($row['provider_name']) && $row['provider_name'] !== '—') ? $row['provider_name'] : '-');
            $venue = (!empty($row['venue']) && $row['venue'] !== '—') ? $row['venue'] : '-';
            $attendance = (!empty($row['attendance_status']) && $row['attendance_status'] !== '—') ? $row['attendance_status'] : '-';

            $rawScore = $row['l2_numerical'] ?? $row['score'] ?? $row['assessment_score'] ?? null;
            $hasScore = ($rawScore !== null && $rawScore !== '' && is_numeric($rawScore));
            $scoreVal = $hasScore ? (float) $rawScore : '-';

            $bondStatus = (!empty($row['bond_status']) && $row['bond_status'] !== '—') ? $row['bond_status'] : '-';
            $bondDuration = (isset($row['bond_duration']) && $row['bond_duration'] !== '' && is_numeric($row['bond_duration']))
                ? (float) $row['bond_duration']
                : '-';
            $bondEndDate = $this->toExcelDate($row['bond_end_date'] ?? null);
            $bondAmount = (isset($row['bond_amount']) && $row['bond_amount'] !== '' && is_numeric($row['bond_amount']))
                ? (float) $row['bond_amount']
                : '-';

            $certificate = ($row['has_certificate'] ?? false) ? 'Available' : 'Not Available';

            $output[] = [
                $number++,                                          // A: No
                $rowEmpId,                                          // B: Employee. ID
                $rowName,                                           // C: Name
                $rowTeam,                                           // D: Team
                $row['title'] ?? '-',                               // E: Course Title
                $row['type'] ?? '-',                                // F: Type
                $row['category'] ?? '-',                            // G: Category
                $row['classification'] ?? '-',                      // H: Main Classification
                $durationCell,                                      // I: Duration
                $lhValue,                                           // J: Learning Hour
                $progressCell,                                      // K: Progress
                $startDate,                                         // L: Start Date
                $endDate,                                           // M: End Date
                $startTime,                                         // N: Start Time
                $endTime,                                           // O: End Time
                $requirement,                                       // P: Requirement
                $deliveryMethod,                                    // Q: Delivery Method
                $provider,                                          // R: Provider
                $providerName,                                      // S: Provider Name
                $venue,                                             // T: Venue
                $attendance,                                        // U: Attendance
                $scoreVal,                                          // V: Score (L2)
                $bondStatus,                                        // W: Bond Status
                $bondDuration,                                      // X: Bond Duration
                $bondEndDate,                                       // Y: Bond End Date
                $bondAmount,                                        // Z: Bond Amount
                $certificate,                                       // AA: Certificate
            ];
        }

        return $output;
    }

    public function headings(): array
    {
        return [
            'No',                   // A
            'Employee. ID',         // B
            'Name',                 // C
            'Team',                 // D
            'Course Title',         // E
            'Type',                 // F
            'Category',             // G
            'Main Classification',  // H
            'Duration',             // I
            'Learning Hour',        // J
            'Progress',             // K
            'Start Date',           // L
            'End Date',             // M
            'Start Time',           // N
            'End Time',             // O
            'Requirement',          // P
            'Delivery Method',      // Q
            'Provider',             // R
            'Provider Name',        // S
            'Venue',                // T
            'Attendance',           // U
            'Score (L2)',           // V
            'Bond Status',          // W
            'Bond Duration',        // X
            'Bond End Date',        // Y
            'Bond Amount',          // Z
            'Certificate',          // AA
        ];
    }

    public function columnFormats(): array
    {
        return [
            'A'  => NumberFormat::FORMAT_NUMBER,        // No
            'B'  => NumberFormat::FORMAT_GENERAL,       // Employee. ID
            'C'  => NumberFormat::FORMAT_GENERAL,       // Name
            'D'  => NumberFormat::FORMAT_GENERAL,       // Team
            'E'  => NumberFormat::FORMAT_GENERAL,       // Course Title
            'F'  => NumberFormat::FORMAT_GENERAL,       // Type
            'G'  => NumberFormat::FORMAT_GENERAL,       // Category
            'H'  => NumberFormat::FORMAT_GENERAL,       // Main Classification
            'I'  => NumberFormat::FORMAT_DATE_TIME4,    // Duration (HH:mm:ss)
            'J'  => NumberFormat::FORMAT_NUMBER_00,     // Learning Hour (Decimal)
            'K'  => NumberFormat::FORMAT_PERCENTAGE,    // Progress (0% - 100%)
            'L'  => 'yyyy-mm-dd',                       // Start Date
            'M'  => 'yyyy-mm-dd',                       // End Date
            'N'  => NumberFormat::FORMAT_DATE_TIME4,    // Start Time (HH:mm:ss)
            'O'  => NumberFormat::FORMAT_DATE_TIME4,    // End Time (HH:mm:ss)
            'P'  => NumberFormat::FORMAT_GENERAL,       // Requirement
            'Q'  => NumberFormat::FORMAT_GENERAL,       // Delivery Method
            'R'  => NumberFormat::FORMAT_GENERAL,       // Provider
            'S'  => NumberFormat::FORMAT_GENERAL,       // Provider Name
            'T'  => NumberFormat::FORMAT_GENERAL,       // Venue
            'U'  => NumberFormat::FORMAT_GENERAL,       // Attendance
            'V'  => '0.##',                             // Score (L2)
            'W'  => NumberFormat::FORMAT_GENERAL,       // Bond Status
            'X'  => '0.##',                             // Bond Duration
            'Y'  => 'yyyy-mm-dd',                       // Bond End Date
            'Z'  => '"Rp "#,##0',                       // Bond Amount
            'AA' => NumberFormat::FORMAT_GENERAL,       // Certificate
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
                    $sheet->getStyle('A2:B' . $highestRow)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
                    $sheet->getStyle('F2:G' . $highestRow)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
                    $sheet->getStyle('I2:R' . $highestRow)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
                    $sheet->getStyle('U2:Y' . $highestRow)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
                    $sheet->getStyle('Z2:Z' . $highestRow)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);
                    $sheet->getStyle('AA2:AA' . $highestRow)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
                }
            },
        ];
    }
}
