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
        if (empty($dateStr) || $dateStr === '-' || $dateStr === 'â€”') {
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
        if (empty($dateTimeOrTime) || $dateTimeOrTime === '-' || $dateTimeOrTime === 'â€”') {
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
            $type = (!empty($row['type']) && $row['type'] !== '-') ? $row['type'] : '-';
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

            $rawAtt = $row['attendance_status'] ?? ($row['attendance'] ?? 'No');
            $attLower = strtolower(trim((string) $rawAtt));
            $attendance = in_array($attLower, ['1', 'true', 'present', 'yes'], true) ? 'Yes' : 'No';

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
            $certInstitution = $row['certificate_institution'] ?? '-';
            $certIssuedDate = $this->toExcelDate($row['certificate_issued_date'] ?? null);
            $certExpiredDate = $this->toExcelDate($row['certificate_expired_date'] ?? null);

            $rowArr = [
                $number++,                                          // 1. NO
                $rowEmpId,                                          // 2. Employee ID
                $rowName,                                           // 3. Employee Name
                $rowTeam,                                           // 4. Team
                $courseTitle,                                       // 5. Course Title
                $type,                                              // 6. Type
                $category,                                          // 7. Category
                $mainClassification,                                // 8. Main Classification
                $durationCell,                                      // 9. Course Duration
                '-',                                                // 10. Total Recognized Duration (KP OWL & All Record only; exclude/hyphen '-' for Non-KP OWL)
                $lhValue,                                           // 11. Learning Hours
                $progressCell,                                      // 12. % Progress
                $startDate,                                         // 13. Start Date
                $endDate,                                           // 14. End Date
                $startTime,                                         // 15. Start Time
                $endTime,                                           // 16. End Time
                $requirement,                                       // 17. Requirement
                $deliveryMethod,                                    // 18. Delivery Method
                $provider,                                          // 19. Provider
                $providerName,                                      // 20. Provider Name
                $venue,                                             // 21. Venue
                $attendance,                                        // 22. Attendance
                $scoreVal,                                          // 23. Assessment Score (L2)
                $bondStatus,                                        // 24. Bond Status
                $bondDuration,                                      // 25. Bond Duration
                $bondEndDate,                                       // 26. Bond End Date
                $bondAmount,                                        // 27. Bond Amount
                $certificate,                                       // 28. Certificate
                $certInstitution,                                   // 29. Certificate Institution
                $certIssuedDate,                                    // 30. Certificate Issued Date
                $certExpiredDate,                                   // 31. Certificate Expired Date
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
            'NO',                           // 1
            'Employee ID',                  // 2
            'Employee Name',                // 3
            'Team',                         // 4
            'Course Title',                 // 5
            'Type',                         // 6
            'Category',                     // 7
            'Main Classification',          // 8
            'Course Duration',              // 9
            'Total Recognized Duration',    // 10
            'Learning Hours',               // 11
            '% Progress',                   // 12
            'Start Date',                   // 13
            'End Date',                     // 14
            'Start Time',                   // 15
            'End Time',                     // 16
            'Requirement',                  // 17
            'Delivery Method',              // 18
            'Provider',                     // 19
            'Provider Name',                // 20
            'Venue',                        // 21
            'Attendance',                   // 22
            'Assessment Score (L2)',        // 23
            'Bond Status',                  // 24
            'Bond Duration',                // 25
            'Bond End Date',                // 26
            'Bond Amount',                  // 27
            'Certificate',                  // 28
            'Certificate Institution',      // 29
            'Certificate Issued Date',      // 30
            'Certificate Expired Date',     // 31
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
            'J'  => NumberFormat::FORMAT_GENERAL,       // Total Recognized Duration ('-')
            'K'  => '0.00 "h"',                         // Learning Hours
            'L'  => '0%',                               // % Progress
            'M'  => 'yyyy-mm-dd',                       // Start Date
            'N'  => 'yyyy-mm-dd',                       // End Date
            'O'  => 'hh:mm:ss',                         // Start Time (Native Time)
            'P'  => 'hh:mm:ss',                         // End Time (Native Time)
            'Q'  => NumberFormat::FORMAT_GENERAL,       // Requirement
            'R'  => NumberFormat::FORMAT_GENERAL,       // Delivery Method
            'S'  => NumberFormat::FORMAT_GENERAL,       // Provider
            'T'  => NumberFormat::FORMAT_GENERAL,       // Provider Name
            'U'  => NumberFormat::FORMAT_GENERAL,       // Venue
            'V'  => NumberFormat::FORMAT_GENERAL,       // Attendance
            'W'  => '0',                                // Assessment Score (L2)
            'X'  => NumberFormat::FORMAT_GENERAL,       // Bond Status
            'Y'  => '0.##',                             // Bond Duration
            'Z'  => 'yyyy-mm-dd',                       // Bond End Date
            'AA' => '"Rp "#,##0;;"Rp -"',               // Bond Amount
            'AB' => NumberFormat::FORMAT_GENERAL,       // Certificate
            'AC' => NumberFormat::FORMAT_GENERAL,       // Certificate Institution
            'AD' => 'yyyy-mm-dd',                       // Certificate Issued Date
            'AE' => 'yyyy-mm-dd',                       // Certificate Expired Date
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
                    $sheet->getStyle('W2:W' . $highestRow)->getNumberFormat()->setFormatCode('0');
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
