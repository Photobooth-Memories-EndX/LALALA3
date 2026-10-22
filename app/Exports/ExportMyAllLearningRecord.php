<?php

namespace App\Exports;

use App\Models\Content;
use App\Services\CertificateEligibilityService;
use App\Services\Learning\LearningRecordPlaylistExportService;
use App\Services\Learning\UserRecordAllService;
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
            $sourceLabel = $isOffline ? 'Non-KP OWL' : 'KP OWL';

            $rowEmpId = ($isOffline && !empty($row['employee_id']) && $row['employee_id'] !== '-') ? (string) $row['employee_id'] : $empId;
            $rowName = ($isOffline && !empty($row['participant_name']) && $row['participant_name'] !== '-') ? (string) $row['participant_name'] : $userName;
            $rowTeam = ($isOffline && !empty($row['team']) && $row['team'] !== '-') ? (string) $row['team'] : $userTeam;

            $title = (!empty($row['title']) && $row['title'] !== '-') ? $row['title'] : ((!empty($row['course_name']) && $row['course_name'] !== '-') ? $row['course_name'] : '-');

            $durationSeconds = max(0, (int) ($row['duration_seconds'] ?? $row['duration_sort'] ?? 0));
            $durationCell = $durationSeconds > 0 ? ($durationSeconds / 86400) : '-';

            $lhValue = round((float) ($row['learning_hour_value'] ?? 0), 2);

            $rawProgress = $row['progress'] ?? ($isOffline ? 100 : 0);
            $progressInt = is_numeric($rawProgress) ? max(0, min(100, (int) $rawProgress)) : ($isOffline ? 100 : 0);
            $progressCell = $progressInt / 100;

            $rawAttendance = $row['attendance_status'] ?? $row['attendance'] ?? ($progressInt >= 100 ? 'Yes' : 'No');
            $attendance = in_array(strtolower(trim((string) $rawAttendance)), ['1', 'true', 'present', 'yes'], true) ? 'Yes' : 'No';

            $startRaw = $row['start_date'] ?? '';
            $endRaw = !empty($row['end_date']) ? $row['end_date'] : (!empty($row['finish_date']) ? $row['finish_date'] : ($startRaw !== '' && $isOffline ? $startRaw : ''));
            $startDate = $this->toExcelDate($startRaw);
            $endDate = $this->toExcelDate($endRaw);

            if ($isOffline) {
                $type = '-';
                $category = 'Non KP-OWL';
                $mainClassification = (!empty($row['main_classification']) && $row['main_classification'] !== '-')
                    ? $row['main_classification']
                    : ((!empty($row['classification']) && $row['classification'] !== '-') ? $row['classification'] : '-');

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
                $bondAmountIdr = (isset($rawIdr) && $rawIdr !== '' && $rawIdr !== '-' && is_numeric($rawIdr) && (float) $rawIdr > 0)
                    ? (float) $rawIdr
                    : '-';

                $rawUsd = $row['bond_amount_usd'] ?? null;
                $bondAmountUsd = (isset($rawUsd) && $rawUsd !== '' && $rawUsd !== '-' && is_numeric($rawUsd) && (float) $rawUsd > 0)
                    ? (float) $rawUsd
                    : '-';

                $certificate = (!empty($row['certificate']) && $row['certificate'] !== '-' || !empty($row['has_certificate'])) ? 'Available' : 'Not Available';
            } else {
                $isPlaylist = ($row['row_kind'] ?? '') === 'playlist' || ($row['type'] ?? '') === 'Playlist' || (($row['category'] ?? '') === 'Playlist');
                $type = $row['type'] ?? '-';
                $category = $isPlaylist ? 'Playlist' : 'All Content';
                $mainClassification = (!empty($row['classification']) && $row['classification'] !== '-') ? $row['classification'] : '-';

                $startTime = $this->toExcelTime($startRaw);
                $endTime = $this->toExcelTime($endRaw);
                $requirement = $row['requirement'] ?? Content::mapRequirement($row['subtitle'] ?? ($row['status'] ?? null));
                $deliveryMethod = 'Online';
                $provider = 'Internal';
                $providerName = 'KP-OWL';
                $venue = 'KP-OWL';
                $attendance = 'Yes';

                $rawScore = $row['score'] ?? $row['assessment_score'] ?? null;
                $scoreVal = $this->formatCleanScore($rawScore);

                $bondStatus = '-';
                $bondDuration = '-';
                $bondEndDate = '-';
                $bondAmountIdr = '-';
                $bondAmountUsd = '-';
                $certificate = ($row['cert_state'] ?? 'none') === 'ready' ? 'Available' : 'Not Available';
            }

            $isPlaylist = ($row['row_kind'] ?? '') === 'playlist' || ($row['type'] ?? '') === 'Playlist';
            $playlistId = $row['plan_id'] ?? $row['playlist_id'] ?? ($row['playlist_ids'][0] ?? null);
            if (!$playlistId && isset($row['item_id']) && str_starts_with((string) $row['item_id'], 'playlist_')) {
                $playlistId = (int) str_replace('playlist_', '', $row['item_id']);
            }

            $rowArr = [
                $number++,                              // A: No
                $sourceLabel,                           // B: Source (KP OWL / Non-KP OWL)
                $rowEmpId,                              // C: Employee ID
                $rowName,                               // D: Name
                $rowTeam,                               // E: Team
                $title,                                 // F: Course Title
                $type,                                  // G: Type
                $category,                              // H: Category
                $mainClassification,                    // I: Main Classification
                $durationCell,                          // J: Duration
                $lhValue,                               // K: Learning Hours
                $progressCell,                          // L: % Progress
                $startDate,                             // M: Start Date
                $endDate,                               // N: End Date
                $startTime,                             // O: Start Time
                $endTime,                               // P: End Time
                $requirement ?: '-',                    // Q: Requirement
                $deliveryMethod,                        // R: Delivery Method
                $provider,                              // S: Provider
                $providerName,                          // T: Provider Name
                $venue,                                 // U: Venue
                $attendance,                            // V: Attendance
                $scoreVal,                              // W: Assessment Score (L2)
                $bondStatus,                            // X: Bond Status
                $bondDuration,                          // Y: Bond Duration
                $bondEndDate,                           // Z: Bond End Date
                $bondAmountIdr,                         // AA: Bond Amount (IDR)
                $bondAmountUsd,                         // AB: Bond Amount (USD)
                $certificate,                           // AC: Certificate
            ];

            foreach ($rowArr as $k => $v) {
                if ($v === null || $v === '') {
                    $rowArr[$k] = '-';
                }
            }

            $output[] = $rowArr;

            if ($isPlaylist) {
                $this->parentPlaylistRows[] = count($output);

                if ($playlistId && $childrenMap->has((int) $playlistId)) {
                    $children = $childrenMap->get((int) $playlistId);
                    foreach ($children as $child) {
                        $childDurSec = max(0, (int) ($child['duration_seconds'] ?? 0));
                        $childDurCell = $childDurSec > 0 ? ($childDurSec / 86400) : '-';
                        $childProgInt = max(0, min(100, (int) ($child['progress'] ?? 0)));
                        $childScore = $this->formatCleanScore($child['score'] ?? null);
                        $rawChildAtt = $child['attendance'] ?? ($childProgInt >= 100 ? 'Yes' : 'No');
                        $childAtt = in_array(strtolower(trim((string) $rawChildAtt)), ['1', 'true', 'present', 'yes'], true) ? 'Yes' : 'No';

                        $childRowArr = [
                            '-',                                                    // A: No
                            'KP OWL',                                               // B: Source
                            $empId,                                                 // C: Employee ID
                            $userName,                                              // D: Name
                            $userTeam,                                              // E: Team
                            '    ' . ($child['title'] ?? '-'),                      // F: Course Title
                            $child['type'] ?? '-',                                  // G: Type
                            'Content Playlist',                                     // H: Category (Dynamic Content Playlist)
                            $child['classification'] ?? '-',                        // I: Main Classification
                            $childDurCell,                                          // J: Duration
                            '-',                                                    // K: Learning Hours (Prevent double calculation in SUM)
                            $childProgInt / 100,                                    // L: % Progress
                            $this->toExcelDate($child['start_date'] ?? null),       // M: Start Date
                            $this->toExcelDate($child['end_date'] ?? null),         // N: End Date
                            $this->toExcelTime($child['start_time'] ?? null),       // O: Start Time
                            $this->toExcelTime($child['end_time'] ?? null),         // P: End Time
                            $child['requirement'] ?? 'Non-Mandatory',               // Q: Requirement
                            'Online',                                               // R: Delivery Method
                            'Internal',                                             // S: Provider
                            'KP-OWL',                                               // T: Provider Name
                            'KP-OWL',                                               // U: Venue
                            $childAtt,                                              // V: Attendance
                            $childScore,                                            // W: Assessment Score (L2)
                            '-',                                                    // X: Bond Status
                            '-',                                                    // Y: Bond Duration
                            '-',                                                    // Z: Bond End Date
                            '-',                                                    // AA: Bond Amount (IDR)
                            '-',                                                    // AB: Bond Amount (USD)
                            '-',                                                    // AC: Certificate
                        ];

                        foreach ($childRowArr as $k => $v) {
                            if ($v === null || $v === '') {
                                $childRowArr[$k] = '-';
                            }
                        }

                        $output[] = $childRowArr;
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
            'Source',               // B
            'Employee ID',          // C
            'Name',                 // D
            'Team',                 // E
            'Course Title',         // F
            'Type',                 // G
            'Category',             // H
            'Main Classification',  // I
            'Duration',             // J
            'Learning Hours',       // K
            '% Progress',           // L
            'Start Date',           // M
            'End Date',             // N
            'Start Time',           // O
            'End Time',             // P
            'Requirement',          // Q
            'Delivery Method',      // R
            'Provider',             // S
            'Provider Name',        // T
            'Venue',                // U
            'Attendance',           // V
            'Assessment Score (L2)',// W
            'Bond Status',          // X
            'Bond Duration',        // Y
            'Bond End Date',        // Z
            'Bond Amount (IDR)',    // AA
            'Bond Amount (USD)',    // AB
            'Certificate',          // AC
        ];
    }

    public function columnFormats(): array
    {
        return [
            'A'  => NumberFormat::FORMAT_GENERAL,       // NO
            'B'  => NumberFormat::FORMAT_GENERAL,       // Source
            'C'  => NumberFormat::FORMAT_GENERAL,       // Employee ID
            'D'  => NumberFormat::FORMAT_GENERAL,       // Employee Name
            'E'  => NumberFormat::FORMAT_GENERAL,       // Team
            'F'  => NumberFormat::FORMAT_GENERAL,       // Course Title
            'G'  => NumberFormat::FORMAT_GENERAL,       // Type
            'H'  => NumberFormat::FORMAT_GENERAL,       // Category
            'I'  => NumberFormat::FORMAT_GENERAL,       // Main Classification
            'J'  => 'hh:mm:ss',                         // Duration (Native Time)
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
            'AA' => '"Rp "#,##0;;"Rp -"',               // Bond Amount (IDR)
            'AB' => '"$ "#,##0.00;;"$ -"',              // Bond Amount (USD)
            'AC' => NumberFormat::FORMAT_GENERAL,       // Certificate
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

                // Highlight main Playlist items with light blue/gray fill (#EBF3FB) and bold text
                foreach ($this->parentPlaylistRows as $r) {
                    $sheet->getStyle("A{$r}:{$highestColumn}{$r}")->getFont()->setBold(true);
                    $sheet->getStyle("A{$r}:{$highestColumn}{$r}")->getFill()
                        ->setFillType(Fill::FILL_SOLID)
                        ->getStartColor()->setARGB('FFEBF3FB');
                }

                foreach ($this->childRows as $r) {
                    $sheet->getStyle("F{$r}")->getAlignment()->setIndent(1);
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
