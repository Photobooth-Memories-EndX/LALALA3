<?php

namespace App\Exports;

use App\Services\Learning\UserRecordOfflineService;
use Carbon\Carbon;
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

        $str = trim((string) $dateTimeOrTime);
        if (!str_contains($str, ':')) {
            return '-';
        }

        try {
            $dt = Carbon::parse($str);
            return (($dt->hour * 3600) + ($dt->minute * 60) + $dt->second) / 86400;
        } catch (\Throwable) {
            return '-';
        }
    }

    private function formatCleanScore($score): string|int
    {
        return is_numeric($score) ? (int) round((float) $score) : '-';
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
        $rawRows = $offlineService->getRecords((int) $user->id, $user->id_emp);
        $this->rows = $offlineService->applyFilters($rawRows, $this->filters);

        $output = [$this->headings()];
        $number = 1;

        foreach ($this->rows as $row) {
            $rowEmpId = (!empty($row['employee_id']) && $row['employee_id'] !== '-') ? (string) $row['employee_id'] : $empId;
            $rowName = (!empty($row['participant_name']) && $row['participant_name'] !== '-') ? (string) $row['participant_name'] : $userName;
            $rowTeam = (!empty($row['team']) && $row['team'] !== '-') ? (string) $row['team'] : $userTeam;

            $courseTitle = (!empty($row['title']) && $row['title'] !== '-') ? $row['title'] : ((!empty($row['course_name']) && $row['course_name'] !== '-') ? $row['course_name'] : '-');
            $type = '-';
            $category = 'Non KP-OWL';
            $mainClassification = (!empty($row['main_classification']) && $row['main_classification'] !== '-')
                ? $row['main_classification']
                : ((!empty($row['classification']) && $row['classification'] !== '-') ? $row['classification'] : '-');

            $durationSeconds = max(0, (int) ($row['duration_seconds'] ?? $row['duration_sort'] ?? 0));
            $durationCell = $durationSeconds > 0 ? ($durationSeconds / 86400) : '-';

            $lhValue = round((float) ($row['learning_hour_value'] ?? 0), 2);

            $rawProgress = $row['progress'] ?? 100;
            $progressInt = is_numeric($rawProgress) ? max(0, min(100, (int) $rawProgress)) : 100;
            $progressCell = $progressInt / 100;

            $startRaw = !empty($row['start_date']) ? $row['start_date'] : '';
            $endRaw = !empty($row['end_date']) ? $row['end_date'] : (!empty($row['finish_date']) ? $row['finish_date'] : ($startRaw !== '' ? $startRaw : ''));
            $startDate = $this->toExcelDate($startRaw);
            $endDate = $this->toExcelDate($endRaw);
            $startTime = $this->toExcelTime($row['start_time'] ?? null);
            $endTime = $this->toExcelTime($row['end_time'] ?? null);

            $reqRaw = strtolower(trim((string) ($row['requirement'] ?? '')));
            $requirement = ($reqRaw === 'mandatory') ? 'Mandatory' : 'Non-Mandatory';

$deliveryMethod = \App\Models\OfflineTraining::normalizeDeliveryMethod($row['delivery_method'] ?? 'Offline');

            $provider = (!empty($row['provider']) && $row['provider'] !== '—' && $row['provider'] !== '-')
                ? $row['provider']
                : ((!empty($row['provider_type']) && $row['provider_type'] !== '—') ? $row['provider_type'] : '-');
            $providerName = (!empty($row['provider_name']) && $row['provider_name'] !== '—' && $row['provider_name'] !== '-')
                ? $row['provider_name']
                : ((!empty($row['raw_provider_name']) && $row['raw_provider_name'] !== '—') ? $row['raw_provider_name'] : '-');
            $venue = (!empty($row['venue']) && $row['venue'] !== '—') ? $row['venue'] : '-';

            $rawScore = $row['assessment_score'] ?? $row['l2_numerical'] ?? $row['score'] ?? null;
            $scoreVal = $this->formatCleanScore($rawScore);

            $bondStatus = (!empty($row['bond_status']) && $row['bond_status'] !== '—') ? $row['bond_status'] : '-';
            $bondDuration = (isset($row['bond_duration']) && $row['bond_duration'] !== '' && $row['bond_duration'] !== '-' && is_numeric($row['bond_duration']))
                ? (float) $row['bond_duration']
                : '-';
            $bondEndDate = $this->toExcelDate($row['bond_end_date'] ?? null);

            $rawIdr = $row['bond_amount_idr'] ?? $row['bond_amount'] ?? null;
            $bondAmount = (isset($rawIdr) && $rawIdr !== '' && $rawIdr !== '-' && is_numeric($rawIdr) && (float) $rawIdr > 0)
                ? (float) $rawIdr
                : '-';

            $certificate = (!empty($row['certificate']) && $row['certificate'] !== '-' || !empty($row['has_certificate'])) ? 'Available' : 'Not Available';

            $rowArr = [
                $number++,                                          // A: NO
                $rowEmpId,                                          // B: Employee ID
                $rowName,                                           // C: Employee Name
                $rowTeam,                                           // D: Team
                $courseTitle,                                       // E: Course Title
                $type,                                              // F: Type
                $category,                                          // G: Category
                $mainClassification,                                // H: Main Classification
                $durationCell,                                      // I: Course Duration
                $lhValue,                                           // J: Learning Hour
                $progressCell,                                      // K: % Progress
                $startDate,                                         // L: Start Date
                $endDate,                                           // M: End Date
                $startTime,                                         // N: Start Time
                $endTime,                                           // O: End Time
                $requirement,                                       // P: Requirement
                $deliveryMethod,                                    // Q: Delivery Method
                $provider,                                          // R: Provider
                $providerName,                                      // S: Provider Name
                $venue,                                             // T: Venue
                $scoreVal,                                          // U: Assessment Score (L2)
                $bondStatus,                                        // V: Bond Status
                $bondDuration,                                      // W: Bond Duration
                $bondEndDate,                                       // X: Bond End Date
                $bondAmount,                                        // Y: Bond Amount
                $certificate,                                       // Z: Certificate
            ];

            foreach ($rowArr as $k => $v) {
                if ($v === null || $v === '') {
                    $rowArr[$k] = '-';
                }
            }

            $output[] = $rowArr;
        }

        return $output;
    }

    public function headings(): array
    {
        return [
            'NO',                   // A
            'Employee ID',          // B
            'Employee Name',        // C
            'Team',                 // D
            'Course Title',         // E
            'Type',                 // F
            'Category',             // G
            'Main Classification',  // H
            'Course Duration',      // I
            'Learning Hour',        // J
            '% Progress',           // K
            'Start Date',           // L
            'End Date',             // M
            'Start Time',           // N
            'End Time',             // O
            'Requirement',          // P
            'Delivery Method',      // Q
            'Provider',             // R
            'Provider Name',        // S
            'Venue',                // T
            'Assessment Score (L2)',// U
            'Bond Status',          // V
            'Bond Duration',        // W
            'Bond End Date',        // X
            'Bond Amount',          // Y
            'Certificate',          // Z
        ];
    }

    public function columnFormats(): array
    {
        return [
            'A'  => NumberFormat::FORMAT_GENERAL,       // NO
            'B'  => NumberFormat::FORMAT_GENERAL,       // Employee ID
            'C'  => NumberFormat::FORMAT_GENERAL,       // Employee Name
            'D'  => NumberFormat::FORMAT_GENERAL,       // Team
            'E'  => NumberFormat::FORMAT_GENERAL,       // Course Title
            'F'  => NumberFormat::FORMAT_GENERAL,       // Type
            'G'  => NumberFormat::FORMAT_GENERAL,       // Category
            'H'  => NumberFormat::FORMAT_GENERAL,       // Main Classification
            'I'  => 'hh:mm:ss',                         // Course Duration (Native Time)
            'J'  => '0.00 "h"',                         // Learning Hour
            'K'  => '0%',                               // % Progress
            'L'  => 'yyyy-mm-dd',                       // Start Date
            'M'  => 'yyyy-mm-dd',                       // End Date
            'N'  => 'hh:mm:ss',                         // Start Time (Native Time)
            'O'  => 'hh:mm:ss',                         // End Time (Native Time)
            'P'  => NumberFormat::FORMAT_GENERAL,       // Requirement
            'Q'  => NumberFormat::FORMAT_GENERAL,       // Delivery Method
            'R'  => NumberFormat::FORMAT_GENERAL,       // Provider
            'S'  => NumberFormat::FORMAT_GENERAL,       // Provider Name
            'T'  => NumberFormat::FORMAT_GENERAL,       // Venue
            'U'  => '0',                                // Assessment Score (L2)
            'V'  => NumberFormat::FORMAT_GENERAL,       // Bond Status
            'W'  => '0.##',                             // Bond Duration
            'X'  => 'yyyy-mm-dd',                       // Bond End Date
            'Y'  => '"Rp "#,##0;;"Rp -"',               // Bond Amount
            'Z'  => NumberFormat::FORMAT_GENERAL,       // Certificate
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

                // Center-align ALL data cells horizontally and vertically across all columns
                $sheet->getStyle('A1:' . $highestColumn . $highestRow)
                    ->getAlignment()
                    ->setHorizontal(Alignment::HORIZONTAL_CENTER)
                    ->setVertical(Alignment::VERTICAL_CENTER);

                if ($highestRow >= 2) {
                    $sheet->getStyle('U2:U' . $highestRow)->getNumberFormat()->setFormatCode('0');
                }

                $sheet->setShowSummaryBelow(false);
            },
        ];
    }

    public function getRows(): Collection
    {
        return $this->rows;
    }
}
