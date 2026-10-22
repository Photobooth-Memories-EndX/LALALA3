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

            $deliveryMethod = \App\Models\OfflineTraining::normalizeDeliveryMethod($row['delivery_method'] ?? 'Online');
            $provider = 'Internal';
            $providerName = 'KP-OWL';
            $venue = 'KP-OWL';

            $rawScore = $row['score'] ?? $row['assessment_score'] ?? null;
            $scoreVal = $this->formatCleanScore($rawScore);

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

            $category = $isPlaylist ? 'Playlist' : 'All Content';

            $rowArr = [
                $number++,                                                                                          // A: NO
                $empId,                                                                                             // B: Employee ID
                $userName,                                                                                          // C: Employee Name
                $userTeam,                                                                                          // D: Team
                $row['title'] ?? '-',                                                                               // E: Course Title
                $row['type'] ?? '-',                                                                                // F: Type
                $category,                                                                                          // G: Category
                (!empty($row['classification']) && $row['classification'] !== '-') ? $row['classification'] : '-',  // H: Main Classification
                $durationCell,                                                                                      // I: Duration
                $lhValue,                                                                                           // J: Learning Hour
                $progressCell,                                                                                      // K: Progress
                $startDate,                                                                                         // L: Start Date
                $endDate,                                                                                           // M: End Date
                $startTime,                                                                                         // N: Start Time
                $endTime,                                                                                           // O: End Time
                $requirement ?: '-',                                                                                // P: Requirement
                $deliveryMethod,                                                                                    // Q: Delivery Method
                $provider,                                                                                          // R: Provider
                $providerName,                                                                                      // S: Provider Name
                $venue,                                                                                             // T: Venue
                $scoreVal,                                                                                          // U: Assessment Score (L2)
                $bondStatus,                                                                                        // V: Bond Status
                $bondDuration,                                                                                      // W: Bond Duration
                $bondEndDate,                                                                                       // X: Bond End Date
                $bondAmount,                                                                                        // Y: Bond Amount
                $certificate,                                                                                       // Z: Certificate
            ];

            // Auto-fallback empty values to '-'
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

                        $childRowArr = [
                            '-',                                                    // A: No
                            $empId,                                                 // B: Employee ID
                            $userName,                                              // C: Name
                            $userTeam,                                              // D: Team
                            '    ' . ($child['title'] ?? '-'),                      // E: Course Title
                            $child['type'] ?? '-',                                  // F: Type
                            'Content Playlist',                                     // G: Category (Dynamic Playlist Content)
                            $child['classification'] ?? '-',                        // H: Main Classification
                            $childDurCell,                                          // I: Duration
                            '-',                                                    // J: Learning Hour (Prevent double calculation in SUM)
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
                            $childScore,                                            // U: Assessment Score (L2)
                            '-',                                                    // V: Bond Status
                            '-',                                                    // W: Bond Duration
                            '-',                                                    // X: Bond End Date
                            '-',                                                    // Y: Bond Amount
                            '-',                                                    // Z: Certificate
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

                // Highlight main Playlist items with light blue/gray fill (#EBF3FB) and bold text
                foreach ($this->parentPlaylistRows as $r) {
                    $sheet->getStyle("A{$r}:{$highestColumn}{$r}")->getFont()->setBold(true);
                    $sheet->getStyle("A{$r}:{$highestColumn}{$r}")->getFill()
                        ->setFillType(Fill::FILL_SOLID)
                        ->getStartColor()->setARGB('FFEBF3FB');
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
