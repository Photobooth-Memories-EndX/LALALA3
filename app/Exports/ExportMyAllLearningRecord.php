<?php

namespace App\Exports;

use App\Models\Content;
use App\Services\CertificateEligibilityService;
use App\Services\Learning\LearningRecordPlaylistExportService;
use App\Services\Learning\UserRecordAllService;
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

class ExportMyAllLearningRecord implements FromArray, ShouldAutoSize, WithColumnFormatting, WithEvents, WithStyles
{
    private Collection $rows;
    private array $parentPlaylistRows = [];
    private array $childRows = [];

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
        $this->parentPlaylistRows = [];
        $this->childRows = [];

        $user = Auth::user();
        if (!$user) {
            return [$this->headings()];
        }

        $empId = !empty($user->id_emp) ? (string) $user->id_emp : '-';
        $userName = !empty($user->name) ? (string) $user->name : '-';
        $userTeam = !empty($user->team) ? (string) $user->team : (!empty($user->department) ? (string) $user->department : '-');

        $allService = app(UserRecordAllService::class);
        $eligibilityService = app(CertificateEligibilityService::class);
        $this->rows = $allService->getRecords((int) $user->id, $user->id_emp, $eligibilityService, $this->filters);

        // Batch load playlist children (Zero N+1)
        $playlistIds = $this->rows->filter(function ($row) {
            return ($row['row_kind'] ?? '') === 'playlist' || ($row['type'] ?? '') === 'Playlist';
        })->map(function ($row) {
            $pid = $row['plan_id'] ?? $row['playlist_id'] ?? ($row['playlist_ids'][0] ?? null);
            if (!$pid && isset($row['item_id']) && str_starts_with((string) $row['item_id'], 'playlist_')) {
                $pid = (int) str_replace('playlist_', '', $row['item_id']);
            }
            return $pid ? (int) $pid : null;
        })->filter()->unique()->values()->all();

        $playlistChildrenService = app(LearningRecordPlaylistExportService::class);
        $childrenMap = $playlistChildrenService->getPlaylistChildrenBatch((int) $user->id, $playlistIds);

        $output = [$this->headings()];
        $number = 1;

        foreach ($this->rows as $row) {
            $isOffline = ($row['source'] ?? '') === 'offline';

            $rowEmpId = ($isOffline && !empty($row['employee_id']) && $row['employee_id'] !== '-') ? (string) $row['employee_id'] : $empId;
            $rowName = ($isOffline && !empty($row['participant_name']) && $row['participant_name'] !== '-') ? (string) $row['participant_name'] : $userName;
            $rowTeam = ($isOffline && !empty($row['team']) && $row['team'] !== '-') ? (string) $row['team'] : $userTeam;

            $title = !empty($row['training_title']) ? $row['training_title'] : (!empty($row['title']) ? $row['title'] : '-');
            $requirement = $row['requirement'] ?? Content::mapRequirement($row['subtitle'] ?? null);

            $durationSeconds = max(0, (int) ($row['duration_seconds'] ?? $row['duration_sort'] ?? 0));
            $durationCell = $durationSeconds > 0 ? ($durationSeconds / 86400) : '-';

            $lhValue = round((float) ($row['learning_hour_value'] ?? 0), 2);

            $rawProgress = $row['progress'] ?? 0;
            $progressInt = is_numeric($rawProgress) ? max(0, min(100, (int) $rawProgress)) : 0;
            $progressCell = $progressInt / 100;

            $attendance = (!empty($row['attendance_status']) && $row['attendance_status'] !== '—') ? $row['attendance_status'] : '-';

            $startRaw = $row['start_date'] ?? '';
            $finishRaw = $row['finish_date'] ?? '';
            $startDate = $this->toExcelDate($startRaw);
            $endDate = $finishRaw !== ''
                ? $this->toExcelDate($finishRaw)
                : ($startRaw !== '' && $isOffline ? $startDate : '-');

            if ($isOffline) {
                $startTime = $this->toExcelTime($row['start_time'] ?? null);
                $endTime = $this->toExcelTime($row['end_time'] ?? null);
                $deliveryMethod = (!empty($row['delivery_method']) && $row['delivery_method'] !== '—') ? $row['delivery_method'] : '-';
                $provider = (!empty($row['provider_type']) && $row['provider_type'] !== '—')
                    ? $row['provider_type']
                    : ((!empty($row['provider']) && $row['provider'] !== '—') ? $row['provider'] : '-');
                $providerName = (!empty($row['raw_provider_name']) && $row['raw_provider_name'] !== '—')
                    ? $row['raw_provider_name']
                    : ((!empty($row['provider_name']) && $row['provider_name'] !== '—') ? $row['provider_name'] : '-');
                $venue = (!empty($row['venue']) && $row['venue'] !== '—') ? $row['venue'] : '-';
            } else {
                $startTime = $this->toExcelTime($startRaw);
                $endTime = $this->toExcelTime($finishRaw);
                $deliveryMethod = '-';
                $provider = '-';
                $providerName = '-';
                $venue = '-';
            }

            $rawScore = $isOffline
                ? ($row['l2_numerical'] ?? $row['score'] ?? $row['assessment_score'] ?? null)
                : ($row['score'] ?? $row['assessment_score'] ?? null);
            $hasScore = ($rawScore !== null && $rawScore !== '' && is_numeric($rawScore));
            $scoreVal = $hasScore ? (float) $rawScore : '-';

            $bondStatus = ($isOffline && !empty($row['bond_status']) && $row['bond_status'] !== '—') ? $row['bond_status'] : '-';
            $bondDuration = ($isOffline && isset($row['bond_duration']) && $row['bond_duration'] !== '' && is_numeric($row['bond_duration']))
                ? (float) $row['bond_duration']
                : '-';
            $bondEndDate = $isOffline ? $this->toExcelDate($row['bond_end_date'] ?? null) : '-';
            $bondAmount = ($isOffline && isset($row['bond_amount']) && $row['bond_amount'] !== '' && is_numeric($row['bond_amount']))
                ? (float) $row['bond_amount']
                : '-';

            $certificate = (($row['has_certificate'] ?? false) || ($row['cert_state'] ?? '') === 'ready') ? 'Available' : 'Not Available';

            $isPlaylist = ($row['row_kind'] ?? '') === 'playlist' || ($row['type'] ?? '') === 'Playlist';
            $playlistId = $row['plan_id'] ?? $row['playlist_id'] ?? ($row['playlist_ids'][0] ?? null);
            if (!$playlistId && isset($row['item_id']) && str_starts_with((string) $row['item_id'], 'playlist_')) {
                $playlistId = (int) str_replace('playlist_', '', $row['item_id']);
            }

            $output[] = [
                $number++,                              // A: No
                $rowEmpId,                              // B: Employee. ID
                $rowName,                               // C: Name
                $rowTeam,                               // D: Team
                $title,                                 // E: Course Title
                $row['type'] ?? '-',                    // F: Type
                $row['category'] ?? '-',                // G: Category
                $row['classification'] ?? '-',          // H: Main Classification
                $durationCell,                          // I: Duration
                $lhValue,                               // J: Learning Hour
                $progressCell,                          // K: Progress
                $startDate,                             // L: Start Date
                $endDate,                               // M: End Date
                $startTime,                             // N: Start Time
                $endTime,                               // O: End Time
                $requirement,                           // P: Requirement
                $deliveryMethod,                        // Q: Delivery Method
                $provider,                              // R: Provider
                $providerName,                          // S: Provider Name
                $venue,                                 // T: Venue
                $attendance,                            // U: Attendance
                $scoreVal,                              // V: Score (L2)
                $bondStatus,                            // W: Bond Status
                $bondDuration,                          // X: Bond Duration
                $bondEndDate,                           // Y: Bond End Date
                $bondAmount,                            // Z: Bond Amount
                $certificate,                           // AA: Certificate
            ];

            if ($isPlaylist) {
                $this->parentPlaylistRows[] = count($output);

                if ($playlistId && $childrenMap->has((int) $playlistId)) {
                    $children = $childrenMap->get((int) $playlistId);
                    foreach ($children as $child) {
                        $childDurSec = max(0, (int) ($child['duration_seconds'] ?? 0));
                        $childDurCell = $childDurSec > 0 ? ($childDurSec / 86400) : '-';
                        $childLhVal = round((float) ($child['learning_hour_value'] ?? 0), 2);
                        $childProgInt = max(0, min(100, (int) ($child['progress'] ?? 0)));
                        $childScore = ($child['score'] !== null && $child['score'] !== '' && $child['score'] !== '-' && is_numeric($child['score']))
                            ? (float) $child['score']
                            : '-';

                        $output[] = [
                            '',                                                     // A: No
                            $empId,                                                 // B: Employee. ID
                            $userName,                                              // C: Name
                            $userTeam,                                              // D: Team
                            '    ' . ($child['title'] ?? '-'),                      // E: Course Title
                            $child['type'] ?? '-',                                  // F: Type
                            'Playlist',                                             // G: Category
                            $child['classification'] ?? '-',                        // H: Main Classification
                            $childDurCell,                                          // I: Duration
                            $childLhVal,                                            // J: Learning Hour
                            $childProgInt / 100,                                    // K: Progress
                            $this->toExcelDate($child['start_date'] ?? null),       // L: Start Date
                            $this->toExcelDate($child['end_date'] ?? null),         // M: End Date
                            $this->toExcelTime($child['start_time'] ?? null),       // N: Start Time
                            $this->toExcelTime($child['end_time'] ?? null),         // O: End Time
                            $child['requirement'] ?? 'Non-Mandatory',               // P: Requirement
                            '-',                                                    // Q: Delivery Method
                            '-',                                                    // R: Provider
                            '-',                                                    // S: Provider Name
                            '-',                                                    // T: Venue
                            $child['attendance'] ?? '-',                            // U: Attendance
                            $childScore,                                            // V: Score (L2)
                            '-',                                                    // W: Bond Status
                            '-',                                                    // X: Bond Duration
                            '-',                                                    // Y: Bond End Date
                            '-',                                                    // Z: Bond Amount
                            '-',                                                    // AA: Certificate
                        ];
                        $this->childRows[] = count($output);
                    }
                }
            }
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

                if ($highestRow >= 2) {
                    $sheet->getStyle('A2:B' . $highestRow)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
                    $sheet->getStyle('F2:G' . $highestRow)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
                    $sheet->getStyle('I2:R' . $highestRow)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
                    $sheet->getStyle('U2:Y' . $highestRow)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
                    $sheet->getStyle('Z2:Z' . $highestRow)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);
                    $sheet->getStyle('AA2:AA' . $highestRow)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
                }

                foreach ($this->parentPlaylistRows as $r) {
                    $sheet->getStyle("A{$r}:{$highestColumn}{$r}")->getFont()->setBold(true);
                }

                foreach ($this->childRows as $r) {
                    $sheet->getStyle("E{$r}")->getAlignment()->setIndent(1);
                    $sheet->getRowDimension($r)->setOutlineLevel(1);
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
