<?php

namespace App\Exports;

use App\Models\Content;
use App\Services\CertificateEligibilityService;
use App\Services\Learning\LearningRecordPlaylistExportService;
use App\Services\Learning\UserRecordAllService;
use Carbon\Carbon;
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

        // Preload certificate settings and user certificates (Zero N+1)
        $contentIds = [];
        $planIds = [];
        $offlineIds = [];
        foreach ($this->rows as $r) {
            if (($r['source'] ?? '') === 'offline') {
                if (!empty($r['offline_training_id'])) {
                    $offlineIds[] = (int) $r['offline_training_id'];
                } elseif (!empty($r['id'])) {
                    $offlineIds[] = (int) $r['id'];
                }
            } else {
                if (!empty($r['content_id'])) $contentIds[] = (int) $r['content_id'];
                if (!empty($r['plan_id'])) $planIds[] = (int) $r['plan_id'];
                if (!empty($r['playlist_id'])) $planIds[] = (int) $r['playlist_id'];
            }
        }
        $certSettingsContent = !empty($contentIds)
            ? DB::table('certificate_settings')->whereIn('content_id', array_unique($contentIds))->get()->keyBy('content_id')
            : collect();
        $certSettingsPlan = !empty($planIds)
            ? DB::table('certificate_settings')->whereIn('trainingplan_id', array_unique($planIds))->get()->keyBy('trainingplan_id')
            : collect();
        $certSettingsOffline = !empty($offlineIds)
            ? DB::table('certificate_settings')->whereIn('offline_training_id', array_unique($offlineIds))->get()->keyBy('offline_training_id')
            : collect();
        $userCertificates = DB::table('certificates')->where('user_id', $user->id)->get();

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

            $recDurationSeconds = max(0, (int) ($row['recognized_duration_seconds'] ?? 0));
            $recDurationCell = (!$isOffline && $durationSeconds > 0) ? ($recDurationSeconds / 86400) : '-';

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
                $bondAmount = (isset($rawIdr) && $rawIdr !== '' && $rawIdr !== '-' && is_numeric($rawIdr) && (float) $rawIdr > 0)
                    ? (float) $rawIdr
                    : '-';

                $certificate = (!empty($row['certificate']) && $row['certificate'] !== '-' || !empty($row['has_certificate'])) ? 'Available' : 'Not Available';

                $offId = $row['offline_training_id'] ?? ($row['id'] ?? null);
                $offCs = $offId && isset($certSettingsOffline[$offId]) ? $certSettingsOffline[$offId] : null;
                $userCert = $userCertificates->firstWhere('offline_training_user_id', $row['id'] ?? null);

                $certInstitution = $row['certificate_institution'] ?? ($userCert?->institution ?: ($offCs?->institution ?: ($offCs?->issuer_name ?: '-')));
                $certIssuedDate = $this->toExcelDate($row['certificate_issued_date'] ?? ($userCert?->issued_date ?: ($offCs?->issued_date ?: ($userCert?->issued_at ?: null))));
                $certExpiredDate = $this->toExcelDate($row['certificate_expired_date'] ?? ($userCert?->expired_date ?: ($offCs?->expired_date ?: null)));
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

                $rawScore = $row['score'] ?? $row['assessment_score'] ?? null;
                $scoreVal = $this->formatCleanScore($rawScore);

                $bondStatus = '-';
                $bondDuration = '-';
                $bondEndDate = '-';
                $bondAmount = '-';
                $certificate = ($row['cert_state'] ?? 'none') === 'ready' ? 'Available' : 'Not Available';

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
                $certIssuedDate = $this->toExcelDate($userCert?->issued_date ?: ($onCs?->issued_date ?: ($userCert?->issued_at ?: ($isReady && !empty($endRaw) ? $endRaw : null))));
                $certExpiredDate = $this->toExcelDate($userCert?->expired_date ?: ($onCs?->expired_date ?: null));
            }

            $isPlaylist = ($row['row_kind'] ?? '') === 'playlist' || ($row['type'] ?? '') === 'Playlist';
            $playlistId = $row['plan_id'] ?? $row['playlist_id'] ?? ($row['playlist_ids'][0] ?? null);
            if (!$playlistId && isset($row['item_id']) && str_starts_with((string) $row['item_id'], 'playlist_')) {
                $playlistId = (int) str_replace('playlist_', '', $row['item_id']);
            }

            $rowArr = [
                $number++,                              // 1. NO
                $sourceLabel,                           // 2. Source (All Record view only)
                $rowEmpId,                              // 3. Employee ID
                $rowName,                               // 4. Employee Name
                $rowTeam,                               // 5. Team
                $title,                                 // 6. Course Title
                $type,                                  // 7. Type
                $category,                              // 8. Category
                $mainClassification,                    // 9. Main Classification
                $durationCell,                          // 10. Course Duration
                $recDurationCell,                       // 11. Total Recognized Duration (KP OWL & All Record only; exclude/hyphen '-' for Non-KP OWL)
                $lhValue,                               // 12. Learning Hours
                $progressCell,                          // 13. % Progress
                $startDate,                             // 14. Start Date
                $endDate,                               // 15. End Date
                $startTime,                             // 16. Start Time
                $endTime,                               // 17. End Time
                $requirement ?: '-',                    // 18. Requirement
                $deliveryMethod,                        // 19. Delivery Method
                $provider,                              // 20. Provider
                $providerName,                          // 21. Provider Name
                $venue,                                 // 22. Venue
                $attendance,                            // 23. Attendance
                $scoreVal,                              // 24. Assessment Score (L2)
                $bondStatus,                            // 25. Bond Status
                $bondDuration,                          // 26. Bond Duration
                $bondEndDate,                           // 27. Bond End Date
                $bondAmount,                            // 28. Bond Amount
                $certificate,                           // 29. Certificate
                $certInstitution,                       // 30. Certificate Institution
                $certIssuedDate,                        // 31. Certificate Issued Date
                $certExpiredDate,                       // 32. Certificate Expired Date
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
                        $childRecDurSec = max(0, (int) ($child['recognized_duration_seconds'] ?? 0));
                        $childRecDurCell = $childRecDurSec > 0 ? ($childRecDurSec / 86400) : '-';
                        $childProgInt = max(0, min(100, (int) ($child['progress'] ?? 0)));
                        $childScore = $this->formatCleanScore($child['score'] ?? null);

                        $childRowArr = [
                            '-',                                                    // 1. NO
                            'KP OWL',                                               // 2. Source
                            $empId,                                                 // 3. Employee ID
                            $userName,                                              // 4. Employee Name
                            $userTeam,                                              // 5. Team
                            '    ' . ($child['title'] ?? '-'),                      // 6. Course Title
                            $child['type'] ?? '-',                                  // 7. Type
                            'Content Playlist',                                     // 8. Category (Dynamic Content Playlist)
                            $child['classification'] ?? '-',                        // 9. Main Classification
                            '-',                                                    // 10. Course Duration (Prevent double calculation in SUM)
                            '-',                                                    // 11. Total Recognized Duration (Prevent double calculation in SUM)
                            '-',                                                    // 12. Learning Hours (Prevent double calculation in SUM)
                            $childProgInt / 100,                                    // 13. % Progress
                            $this->toExcelDate($child['start_date'] ?? null),       // 14. Start Date
                            $this->toExcelDate($child['end_date'] ?? null),         // 15. End Date
                            $this->toExcelTime($child['start_time'] ?? null),       // 16. Start Time
                            $this->toExcelTime($child['end_time'] ?? null),         // 17. End Time
                            $child['requirement'] ?? 'Non-Mandatory',               // 18. Requirement
                            'Online',                                               // 19. Delivery Method
                            'Internal',                                             // 20. Provider
                            'KP-OWL',                                               // 21. Provider Name
                            'KP-OWL',                                               // 22. Venue
                            $childProgInt >= 100 ? 'Yes' : 'No',                    // 23. Attendance
                            $childScore,                                            // 24. Assessment Score (L2)
                            '-',                                                    // 25. Bond Status
                            '-',                                                    // 26. Bond Duration
                            '-',                                                    // 27. Bond End Date
                            '-',                                                    // 28. Bond Amount
                            '-',                                                    // 29. Certificate
                            '-',                                                    // 30. Certificate Institution
                            '-',                                                    // 31. Certificate Issued Date
                            '-',                                                    // 32. Certificate Expired Date
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
            'Source',                       // 2
            'Employee ID',                  // 3
            'Employee Name',                // 4
            'Team',                         // 5
            'Course Title',                 // 6
            'Type',                         // 7
            'Category',                     // 8
            'Main Classification',          // 9
            'Course Duration',              // 10
            'Total Recognized Duration',    // 11
            'Learning Hours',               // 12
            '% Progress',                   // 13
            'Start Date',                   // 14
            'End Date',                     // 15
            'Start Time',                   // 16
            'End Time',                     // 17
            'Requirement',                  // 18
            'Delivery Method',              // 19
            'Provider',                     // 20
            'Provider Name',                // 21
            'Venue',                        // 22
            'Attendance',                   // 23
            'Assessment Score (L2)',        // 24
            'Bond Status',                  // 25
            'Bond Duration',                // 26
            'Bond End Date',                // 27
            'Bond Amount',                  // 28
            'Certificate',                  // 29
            'Certificate Institution',      // 30
            'Certificate Issued Date',      // 31
            'Certificate Expired Date',     // 32
        ];
    }

    public function columnFormats(): array
    {
        return [
            'A'  => '0',                                // NO (Pure integer)
            'B'  => NumberFormat::FORMAT_GENERAL,       // Source
            'C'  => NumberFormat::FORMAT_TEXT,          // Employee ID (Explicit String)
            'D'  => NumberFormat::FORMAT_GENERAL,       // Employee Name
            'E'  => NumberFormat::FORMAT_GENERAL,       // Team
            'F'  => NumberFormat::FORMAT_GENERAL,       // Course Title
            'G'  => NumberFormat::FORMAT_GENERAL,       // Type
            'H'  => NumberFormat::FORMAT_GENERAL,       // Category
            'I'  => NumberFormat::FORMAT_GENERAL,       // Main Classification
            'J'  => 'hh:mm:ss',                         // Course Duration (Native Time)
            'K'  => 'hh:mm:ss',                         // Total Recognized Duration (Native Time)
            'L'  => '0.00 "h"',                         // Learning Hours
            'M'  => '0%',                               // % Progress
            'N'  => 'yyyy-mm-dd',                       // Start Date
            'O'  => 'yyyy-mm-dd',                       // End Date
            'P'  => 'hh:mm:ss',                         // Start Time (Native Time)
            'Q'  => 'hh:mm:ss',                         // End Time (Native Time)
            'R'  => NumberFormat::FORMAT_GENERAL,       // Requirement
            'S'  => NumberFormat::FORMAT_GENERAL,       // Delivery Method
            'T'  => NumberFormat::FORMAT_GENERAL,       // Provider
            'U'  => NumberFormat::FORMAT_GENERAL,       // Provider Name
            'V'  => NumberFormat::FORMAT_GENERAL,       // Venue
            'W'  => NumberFormat::FORMAT_GENERAL,       // Attendance
            'X'  => '0.00',                             // Assessment Score (L2)
            'Y'  => NumberFormat::FORMAT_GENERAL,       // Bond Status
            'Z'  => '0',                                // Bond Duration (Pure integer)
            'AA' => 'yyyy-mm-dd',                       // Bond End Date
            'AB' => '#,##0',                            // Bond Amount (Pure integer currency)
            'AC' => NumberFormat::FORMAT_GENERAL,       // Certificate
            'AD' => NumberFormat::FORMAT_GENERAL,       // Certificate Institution
            'AE' => 'yyyy-mm-dd',                       // Certificate Issued Date
            'AF' => 'yyyy-mm-dd',                       // Certificate Expired Date
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
                        $val = $sheet->getCell('C' . $r)->getValue();
                        if ($val !== null && $val !== '' && $val !== '-') {
                            $sheet->setCellValueExplicit('C' . $r, (string) $val, \PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_STRING);
                        }
                    }
                    $sheet->getStyle('A2:A' . $highestRow)->getNumberFormat()->setFormatCode('0');
                    $sheet->getStyle('Z2:Z' . $highestRow)->getNumberFormat()->setFormatCode('0');
                    $sheet->getStyle('X2:X' . $highestRow)->getNumberFormat()->setFormatCode('0.00');
                    $sheet->getStyle('AB2:AB' . $highestRow)->getNumberFormat()->setFormatCode('#,##0');
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
