<?php

namespace App\Exports;

use App\Http\Controllers\LearningRecordController;
use App\Models\Content;
use App\Services\CertificateEligibilityService;
use App\Services\Learning\LearningRecordPlaylistExportService;
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

class ExportMyLearningRecord implements FromArray, ShouldAutoSize, WithColumnFormatting, WithEvents, WithStyles
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

        $controller = app(LearningRecordController::class);
        $eligibilityService = app(CertificateEligibilityService::class);
        $allRows = $controller->getRawOnlineRows((int) $user->id, $eligibilityService);
        $this->rows = $controller->applyRecordFilters($allRows, $this->filters);

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
            $durationSeconds = max(0, (int) ($row['duration_sort'] ?? 0));
            $durationCell = $durationSeconds > 0 ? ($durationSeconds / 86400) : '-';

            $lhValue = round((float) ($row['learning_hour_value'] ?? 0), 2);

            $rawProgress = $row['progress'] ?? 0;
            $progressInt = is_numeric($rawProgress) ? max(0, min(100, (int) $rawProgress)) : 0;
            $progressCell = $progressInt / 100;

            $startRaw = $row['start_date'] ?? '';
            $finishRaw = $row['finish_date'] ?? '';
            $startDate = $this->toExcelDate($startRaw);
            $endDate = $this->toExcelDate($finishRaw);
            $startTime = $this->toExcelTime($startRaw);
            $endTime = $this->toExcelTime($finishRaw);

            $attendance = 'Yes';
            $requirement = $row['requirement'] ?? Content::mapRequirement($row['subtitle'] ?? ($row['status'] ?? null));

            $deliveryMethod = 'Online';
            $provider = 'Internal';
            $providerName = 'KP-OWL';
            $venue = 'KP-OWL';

            $hasScore = ($row['score'] !== null && $row['score'] !== '' && is_numeric($row['score']));
            $scoreVal = $hasScore ? (float) $row['score'] : '-';

            $bondStatus = '-';
            $bondDuration = '-';
            $bondEndDate = '-';
            $bondAmount = '-';

            $certificate = ($row['cert_state'] ?? 'none') === 'ready' ? 'Available' : 'Not Available';

            $isPlaylist = ($row['row_kind'] ?? '') === 'playlist' || ($row['type'] ?? '') === 'Playlist';
            $playlistId = $row['plan_id'] ?? $row['playlist_id'] ?? ($row['playlist_ids'][0] ?? null);
            if (!$playlistId && isset($row['item_id']) && str_starts_with((string) $row['item_id'], 'playlist_')) {
                $playlistId = (int) str_replace('playlist_', '', $row['item_id']);
            }

            $output[] = [
                $number++,                                                                                          // A: NO
                $empId,                                                                                             // B: Employee ID
                $userName,                                                                                          // C: Employee Name
                $userTeam,                                                                                          // D: Team
                $row['title'] ?? '-',                                                                               // E: Course Title
                $row['type'] ?? '-',                                                                                // F: Type
                $isPlaylist ? 'Playlist' : 'All Content',                                                           // G: Category
                (!empty($row['classification']) && $row['classification'] !== '-') ? $row['classification'] : '-',  // H: Main Classification
                $durationCell,                                                                                      // I: Duration
                $lhValue,                                                                                           // J: Learning Hour
                $progressCell,                                                                                      // K: Progress
                $startDate,                                                                                         // L: Start Date
                $endDate,                                                                                           // M: End Date
                $startTime,                                                                                         // N: Start Time
                $endTime,                                                                                           // O: End Time
                $requirement,                                                                                       // P: Requirement
                $deliveryMethod,                                                                                    // Q: Delivery Method
                $provider,                                                                                          // R: Provider
                $providerName,                                                                                      // S: Provider Name
                $venue,                                                                                             // T: Venue
                $attendance,                                                                                        // U: Attendance
                $scoreVal,                                                                                          // V: Assessment Score (L2)
                $bondStatus,                                                                                        // W: Bond Status
                $bondDuration,                                                                                      // X: Bond Duration
                $bondEndDate,                                                                                       // Y: Bond End Date
                $bondAmount,                                                                                        // Z: Bond Amount
                $certificate,                                                                                       // AA: Certificate
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
                        $rawChildAtt = $child['attendance'] ?? ($childProgInt >= 100 ? 'Yes' : 'No');
                        $childAtt = in_array(strtolower(trim((string) $rawChildAtt)), ['1', 'true', 'present', 'yes'], true) ? 'Yes' : 'No';

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
                            '',                                                     // J: Learning Hour (Empty on child rows to avoid double-counting in SUM)
                            $childProgInt / 100,                                    // K: Progress
                            $this->toExcelDate($child['start_date'] ?? null),       // L: Start Date
                            $this->toExcelDate($child['end_date'] ?? null),         // M: End Date
                            $this->toExcelTime($child['start_time'] ?? null),       // N: Start Time
                            $this->toExcelTime($child['end_time'] ?? null),         // O: End Time
                            $child['requirement'] ?? 'Non-Mandatory',               // P: Requirement
                            'Online',                                               // Q: Delivery Method
                            'Internal',                                             // R: Provider
                            'KP-OWL',                                               // S: Provider Name
                            'KP-OWL',                                               // T: Venue
                            'Yes',                                                  // U: Attendance
                            $childScore,                                            // V: Assessment Score (L2)
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
            'NO',                   // A
            'Employee ID',          // B
            'Employee Name',        // C
            'Team',                 // D
            'Course Title',         // E
            'Type',                 // F
            'Category',             // G
            'Main Classification',  // H
            'Duration',             // I
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
            'Attendance',           // U
            'Assessment Score (L2)',// V
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
            'A'  => NumberFormat::FORMAT_NUMBER,        // NO
            'B'  => NumberFormat::FORMAT_GENERAL,       // Employee ID
            'C'  => NumberFormat::FORMAT_GENERAL,       // Employee Name
            'D'  => NumberFormat::FORMAT_GENERAL,       // Team
            'E'  => NumberFormat::FORMAT_GENERAL,       // Course Title
            'F'  => NumberFormat::FORMAT_GENERAL,       // Type
            'G'  => NumberFormat::FORMAT_GENERAL,       // Category
            'H'  => NumberFormat::FORMAT_GENERAL,       // Main Classification
            'I'  => 'hh:mm:ss',                         // Duration (Native Time)
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
            'U'  => NumberFormat::FORMAT_GENERAL,       // Attendance
            'V'  => '0.##',                             // Assessment Score (L2)
            'W'  => NumberFormat::FORMAT_GENERAL,       // Bond Status
            'X'  => '0.##',                             // Bond Duration
            'Y'  => 'yyyy-mm-dd',                       // Bond End Date
            'Z'  => '#,##0.00',                         // Bond Amount
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
                    $sheet->getStyle('I2:I' . $highestRow)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
                    $sheet->getStyle('J2:J' . $highestRow)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);
                    $sheet->getStyle('J2:J' . $highestRow)->getNumberFormat()->setFormatCode('0.00 "h"');
                    $sheet->getStyle('K2:R' . $highestRow)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
                    $sheet->getStyle('U2:U' . $highestRow)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
                    $sheet->getStyle('V2:V' . $highestRow)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);
                    $sheet->getStyle('W2:W' . $highestRow)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
                    $sheet->getStyle('X2:X' . $highestRow)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);
                    $sheet->getStyle('Y2:Y' . $highestRow)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
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
