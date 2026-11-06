<?php

namespace App\Exports;

use App\Http\Controllers\LearningRecordController;
use App\Models\Content;
use App\Services\CertificateEligibilityService;
use App\Services\Learning\LearningRecordPlaylistExportService;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
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

        // Preload certificate settings and user certificates (Zero N+1)
        $contentIds = [];
        $planIds = [];
        foreach ($this->rows as $r) {
            if (!empty($r['content_id'])) $contentIds[] = (int) $r['content_id'];
            if (!empty($r['plan_id'])) $planIds[] = (int) $r['plan_id'];
            if (!empty($r['playlist_id'])) $planIds[] = (int) $r['playlist_id'];
        }
        $certSettingsContent = !empty($contentIds)
            ? DB::table('certificate_settings')->whereIn('content_id', array_unique($contentIds))->get()->keyBy('content_id')
            : collect();
        $certSettingsPlan = !empty($planIds)
            ? DB::table('certificate_settings')->whereIn('trainingplan_id', array_unique($planIds))->get()->keyBy('trainingplan_id')
            : collect();
        $userCertificates = DB::table('certificates')->where('user_id', $user->id)->get();

        $output = [$this->headings()];
        $number = 1;

        foreach ($this->rows as $row) {
            $durationSeconds = max(0, (int) ($row['duration_sort'] ?? 0));
            $durationCell = $durationSeconds > 0 ? ($durationSeconds / 86400) : '-';

            $recognizedSeconds = max(0, (int) ($row['recognized_duration_seconds'] ?? 0));
            $recognizedCell = $recognizedSeconds > 0 ? ($recognizedSeconds / 86400) : '-';

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

            $rawAttendance = $row['attendance_status'] ?? $row['attendance'] ?? ($progressInt >= 100 ? 'Yes' : 'No');
            $attendance = in_array(strtolower(trim((string) $rawAttendance)), ['1', 'true', 'present', 'yes'], true) ? 'Yes' : 'No';
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

            $cid = $row['content_id'] ?? ($row['id'] ?? null);
            $pid = $row['plan_id'] ?? ($row['playlist_id'] ?? null);
            $onCs = ($isPlaylist && $pid && isset($certSettingsPlan[$pid]))
                ? $certSettingsPlan[$pid]
                : ($cid && isset($certSettingsContent[$cid]) ? $certSettingsContent[$cid] : null);

            $userCert = $userCertificates->first(function ($c) use ($cid, $pid, $isPlaylist) {
                if ($isPlaylist && $pid) {
                    return $c->source_id == $pid && in_array($c->certificate_type, ['playlist', 'trainingplan']);
                }
                return $c->source_id == $cid && in_array($c->certificate_type, ['content', 'course']);
            });

            $isReady = ($row['cert_state'] ?? 'none') === 'ready' || $progressInt >= 100;
            $certInstitution = $userCert?->institution ?: ($onCs?->institution ?: ($onCs?->issuer_name ?: ($isReady ? 'PT Krakatau Posco' : '-')));
            $certIssuedDate = $this->toExcelDate($userCert?->issued_date ?: ($onCs?->issued_date ?: ($userCert?->issued_at ?: ($isReady && !empty($finishRaw) ? $finishRaw : null))));
            $certExpiredDate = $this->toExcelDate($userCert?->expired_date ?: ($onCs?->expired_date ?: null));

            $category = $isPlaylist ? 'Playlist' : 'All Content';

            $rowArr = [
                $number++,                                                                                          // 1. NO
                $empId,                                                                                             // 2. Employee ID
                $userName,                                                                                          // 3. Employee Name
                $userTeam,                                                                                          // 4. Team
                $row['title'] ?? '-',                                                                               // 5. Course Title
                $row['type'] ?? '-',                                                                                // 6. Type
                $category,                                                                                          // 7. Category
                (!empty($row['classification']) && $row['classification'] !== '-') ? $row['classification'] : '-',  // 8. Main Classification
                $durationCell,                                                                                      // 9. Course Duration
                $recognizedCell,                                                                                    // 10. Total Recognized Duration
                $lhValue,                                                                                           // 11. Learning Hours
                $progressCell,                                                                                      // 12. % Progress
                $startDate,                                                                                         // 13. Start Date
                $endDate,                                                                                           // 14. End Date
                $startTime,                                                                                         // 15. Start Time
                $endTime,                                                                                           // 16. End Time
                $requirement ?: '-',                                                                                // 17. Requirement
                $deliveryMethod,                                                                                    // 18. Delivery Method
                $provider,                                                                                          // 19. Provider
                $providerName,                                                                                      // 20. Provider Name
                $venue,                                                                                             // 21. Venue
                $attendance,                                                                                        // 22. Attendance
                $scoreVal,                                                                                          // 23. Assessment Score (L2)
                $bondStatus,                                                                                        // 24. Bond Status
                $bondDuration,                                                                                      // 25. Bond Duration
                $bondEndDate,                                                                                       // 26. Bond End Date
                $bondAmount,                                                                                        // 27. Bond Amount
                $certificate,                                                                                       // 28. Certificate
                $certInstitution,                                                                                   // 29. Certificate Institution
                $certIssuedDate,                                                                                    // 30. Certificate Issued Date
                $certExpiredDate,                                                                                   // 31. Certificate Expired Date
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
                        $childRecDurSec = max(0, (int) ($child['recognized_duration_seconds'] ?? 0));
                        $childRecDurCell = $childRecDurSec > 0 ? ($childRecDurSec / 86400) : '-';
                        $childProgInt = max(0, min(100, (int) ($child['progress'] ?? 0)));
                        $childScore = $this->formatCleanScore($child['score'] ?? null);

                        $childRowArr = [
                            '-',                                                    // 1. NO
                            $empId,                                                 // 2. Employee ID
                            $userName,                                              // 3. Employee Name
                            $userTeam,                                              // 4. Team
                            '    ' . ($child['title'] ?? '-'),                      // 5. Course Title
                            $child['type'] ?? '-',                                  // 6. Type
                            'Content Playlist',                                     // 7. Category (Dynamic Playlist Content)
                            $child['classification'] ?? '-',                        // 8. Main Classification
                            '-',                                                    // 9. Course Duration (Prevent double calculation in SUM)
                            '-',                                                    // 10. Total Recognized Duration (Prevent double calculation in SUM)
                            '-',                                                    // 11. Learning Hours (Prevent double calculation in SUM)
                            $childProgInt / 100,                                    // 12. % Progress
                            $this->toExcelDate($child['start_date'] ?? null),       // 13. Start Date
                            $this->toExcelDate($child['end_date'] ?? null),         // 14. End Date
                            $this->toExcelTime($child['start_time'] ?? null),       // 15. Start Time
                            $this->toExcelTime($child['end_time'] ?? null),         // 16. End Time
                            $child['requirement'] ?? 'Non-Mandatory',               // 17. Requirement
                            'Online',                                               // 18. Delivery Method
                            'Internal',                                             // 19. Provider
                            'KP-OWL',                                               // 20. Provider Name
                            'KP-OWL',                                               // 21. Venue
                            $childProgInt >= 100 ? 'Yes' : 'No',                    // 22. Attendance
                            $childScore,                                            // 23. Assessment Score (L2)
                            '-',                                                    // 24. Bond Status
                            '-',                                                    // 25. Bond Duration
                            '-',                                                    // 26. Bond End Date
                            '-',                                                    // 27. Bond Amount
                            '-',                                                    // 28. Certificate
                            '-',                                                    // 29. Certificate Institution
                            '-',                                                    // 30. Certificate Issued Date
                            '-',                                                    // 31. Certificate Expired Date
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
            'A'  => '0',                                // NO (Pure integer)
            'B'  => NumberFormat::FORMAT_TEXT,          // Employee ID (Explicit String)
            'C'  => NumberFormat::FORMAT_GENERAL,       // Employee Name
            'D'  => NumberFormat::FORMAT_GENERAL,       // Team
            'E'  => NumberFormat::FORMAT_GENERAL,       // Course Title
            'F'  => NumberFormat::FORMAT_GENERAL,       // Type
            'G'  => NumberFormat::FORMAT_GENERAL,       // Category
            'H'  => NumberFormat::FORMAT_GENERAL,       // Main Classification
            'I'  => 'hh:mm:ss',                         // Course Duration (Native Time)
            'J'  => 'hh:mm:ss',                         // Total Recognized Duration (Native Time)
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
            'Y'  => '0',                                // Bond Duration (Pure integer)
            'Z'  => 'yyyy-mm-dd',                       // Bond End Date
            'AA' => '#,##0',                            // Bond Amount (Pure integer currency)
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
                    for ($r = 2; $r <= $highestRow; $r++) {
                        $val = $sheet->getCell('B' . $r)->getValue();
                        if ($val !== null && $val !== '' && $val !== '-') {
                            $sheet->setCellValueExplicit('B' . $r, (string) $val, \PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_STRING);
                        }
                    }
                    $sheet->getStyle('A2:A' . $highestRow)->getNumberFormat()->setFormatCode('0');
                    $sheet->getStyle('Y2:Y' . $highestRow)->getNumberFormat()->setFormatCode('0');
                    $sheet->getStyle('W2:W' . $highestRow)->getNumberFormat()->setFormatCode('0.00');
                    $sheet->getStyle('AA2:AA' . $highestRow)->getNumberFormat()->setFormatCode('#,##0');
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
