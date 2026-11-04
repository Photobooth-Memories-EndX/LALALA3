<?php

namespace App\Services\Record;

use App\Exports\Manage\ClassificationOnlineRecordExport;
use App\Exports\OnlineRecordExport;
use App\Services\Learning\LearningDatatableService;
use App\Services\Learning\LearningRecordRecognizedDurationService;
use App\Support\DurationFormatter;
use Carbon\Carbon;
use Illuminate\Database\Query\Builder;
use Illuminate\Support\Facades\DB;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\Cell\DataType;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\NumberFormat;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use ZipStream\Option\Archive as ArchiveOptions;
use ZipStream\ZipStream;

class ManageOnlineRecordExportService
{
    /**
     * Canonical 51-column reporting headings for KP OWL (Online) Admin Record Export,
     * mapped sequentially according to KP OWL catalog specifications.
     */
    public static function headings(): array
    {
        return [
            'NO',
            'Employee ID',
            'Employee Name',
            'Grade',
            'Job Position',
            'Team',
            'Department',
            'Company',
            'Course Title',
            'Playlist',
            'Sub Course Title',
            'Type',
            'Category',
            'Level',
            'Batch',
            'Main Classification',
            'Sub Classification',
            'Delivery Method',
            'Sub Delivery Method',
            'Provider',
            'Sub Provider',
            'Provider Name',
            'Venue',
            'Instructor',
            'Course Duration',
            'Total Recognized Duration',
            'Learning Hours',
            '% Progress',
            'Progress Status',
            'Start Date',
            'End Date',
            'Start Time',
            'End Time',
            'Record Month',
            'Requirement',
            'Attendance',
            'Attendance Reason',
            'Bond Status',
            'Bond Duration',
            'Bond End Date',
            'Bond Amount',
            'Score (L1) - Course',
            'Score (L2)',
            'L2 Status',
            'Score (L3)',
            'L3 Status',
            'Certificate Availability',
            'Certificate Institution',
            'Certificate Issued Date',
            'Certificate Expired Date',
            'Person in Charge (PIC)',
            'Total Engagement Time',
        ];
    }

    /**
     * Backward-compatible alias for headings().
     */
    public static function headers(): array
    {
        return self::headings();
    }

    /**
     * Standard column widths for columns A to AY (51 columns).
     */
    public static function columnWidths(): array
    {
        return [
            'A'  => 6,   // NO
            'B'  => 16,  // Employee ID
            'C'  => 30,  // Employee Name
            'D'  => 12,  // Grade
            'E'  => 25,  // Job Position
            'F'  => 20,  // Team
            'G'  => 25,  // Department
            'H'  => 25,  // Company
            'I'  => 35,  // Course Title
            'J'  => 25,  // Playlist
            'K'  => 25,  // Sub Course Title
            'L'  => 15,  // Type
            'M'  => 18,  // Category
            'N'  => 12,  // Level
            'O'  => 12,  // Batch
            'P'  => 25,  // Main Classification
            'Q'  => 25,  // Sub Classification
            'R'  => 18,  // Delivery Method
            'S'  => 20,  // Sub Delivery Method
            'T'  => 16,  // Provider
            'U'  => 20,  // Sub Provider
            'V'  => 25,  // Provider Name
            'W'  => 20,  // Venue
            'X'  => 22,  // Instructor
            'Y'  => 16,  // Course Duration
            'Z'  => 22,  // Total Recognized Duration
            'AA' => 16,  // Learning Hours
            'AB' => 14,  // % Progress
            'AC' => 16,  // Progress Status
            'AD' => 14,  // Start Date
            'AE' => 14,  // End Date
            'AF' => 14,  // Start Time
            'AG' => 14,  // End Time
            'AH' => 16,  // Record Month
            'AI' => 15,  // Requirement
            'AJ' => 14,  // Attendance
            'AK' => 25,  // Attendance Reason
            'AL' => 14,  // Bond Status
            'AM' => 16,  // Bond Duration
            'AN' => 16,  // Bond End Date
            'AO' => 18,  // Bond Amount
            'AP' => 20,  // Score (L1) - Course
            'AQ' => 15,  // Score (L2)
            'AR' => 15,  // L2 Status
            'AS' => 15,  // Score (L3)
            'AT' => 15,  // L3 Status
            'AU' => 22,  // Certificate Availability
            'AV' => 25,  // Certificate Institution
            'AW' => 20,  // Certificate Issued Date
            'AX' => 20,  // Certificate Expired Date
            'AY' => 25,  // Person in Charge (PIC)
            'AZ' => 25,  // Total Engagement Time
        ];
    }

    /**
     * Generate standard timestamped export filename.
     */
    public static function generateFileName(?string $prefix = null): string
    {
        $dt = Carbon::now();
        $monthMap = [
            1 => 'Jan', 2 => 'Feb', 3 => 'Mar', 4 => 'Apr',
            5 => 'Mei', 6 => 'Jun', 7 => 'Jul', 8 => 'Agu',
            9 => 'Sep', 10 => 'Okt', 11 => 'Nov', 12 => 'Des',
        ];
        $day = $dt->format('d');
        $month = $monthMap[(int) $dt->format('n')] ?? $dt->format('M');
        $year = $dt->format('Y');
        $time = $dt->format('H.i.s');

        $base = $prefix ?: 'EmployeeOWLrecord';
        return "{$base}_{$day}{$month}{$year} at {$time}.xlsx";
    }

    /**
     * Resolve Parent Playlist title for a content item.
     * When Category == 'Content Playlist', derive parent Playlist title via content_id / playlist_id
     * from trainingplan or trainingplandetail tables; otherwise return '-'.
     */
    public function resolveParentPlaylistTitle(?int $contentId, ?int $trainingPlanId = null): string
    {
        if (!empty($trainingPlanId)) {
            $tpTitle = DB::table('trainingplan')->where('id', $trainingPlanId)->value('title');
            if (!empty($tpTitle)) {
                return trim($tpTitle);
            }
        }

        if (!empty($contentId)) {
            $parent = DB::table('trainingplandetail as tpd')
                ->join('trainingplan as tp', 'tpd.trainingplan_id', '=', 'tp.id')
                ->where('tpd.content_id', $contentId)
                ->orderBy('tpd.id', 'asc')
                ->value('tp.title');

            if (!empty($parent)) {
                return trim($parent);
            }
        }

        return '-';
    }

    /**
     * Build unified query for KP OWL records.
     */
    public function buildQuery(array $filters = [], ?string $classification = null): Builder
    {
        $effectiveFilters = $filters;
        if (!empty($classification)) {
            $effectiveFilters['classification'] = $classification;
            $effectiveFilters['classification_slug'] = $classification;
        }

        $query = LearningDatatableService::buildBaseQuery(
            $effectiveFilters,
            (string) ($effectiveFilters['type'] ?? ''),
            (string) ($effectiveFilters['slug'] ?? ($classification ?? ''))
        );

        $query->addSelect([
            'c.author as content_author',
            'c.status as content_status',
            'c.learning_hours as content_training_hours',
            'tp.learning_hours as playlist_training_hours',
        ]);

        if (empty($effectiveFilters['sort'])) {
            $query->orderBy('unified.created_at', 'desc')
                  ->orderBy('unified.source_type', 'desc')
                  ->orderBy('unified.source_id', 'desc');
        }

        return $query;
    }

    /**
     * Map a raw record row to the canonical 51 KP OWL columns.
     *
     * Rules:
     * - Sub Course Title: Strictly '-'
     * - Parent Playlist Logic: If Category == 'Content Playlist', fetch parent Playlist title of current Course Title
     *   and write to Playlist; otherwise '-'
     * - Date / Time format: YYYY-MM-DD and HH:MM:SS
     */
    public function mapRow(object $row, int $rowCount, array $context = []): array
    {
        $isPlaylist = (($row->source_type ?? '') === 'playlist') || (!empty($row->trainingplan_id));

        // 1. Employee ID
        $rawEmp = ($row->id_emp ?? null) ?: (($row->employee_id ?? null) ?: ($row->user_id_emp ?? '-'));
        $cleanEmpId = ($rawEmp !== '-') ? preg_replace('/\s*[\/\-]\s*NIP.*$/i', '', trim((string) $rawEmp)) : '-';

        // 2. Category determination & Parent Playlist derivation
        $tpdCache = $context['tpd_cache'] ?? [];
        $contentId = !empty($row->content_id) ? (int) $row->content_id : null;
        $parentPlaylist = ($contentId && isset($tpdCache[$contentId])) ? $tpdCache[$contentId] : null;

        if (!empty($row->playlist_title)) {
            $parentPlaylistTitle = trim((string) $row->playlist_title);
            $category = 'Content Playlist';
        } elseif ($parentPlaylist) {
            $parentPlaylistTitle = $parentPlaylist['title'];
            $category = 'Content Playlist';
        } else {
            $parentPlaylistTitle = '-';
            $category = 'All Content';
        }

        // 3. Course Title & Sub Course Title
        // Course Title: Content title or Playlist title
        if ($category === 'Playlist') {
            $courseTitle = ($row->playlist_title ?? null) ?: (($row->content_title ?? null) ?: 'Playlist');
        } else {
            $courseTitle = ($row->content_title ?? null) ?: (($row->title ?? null) ?: 'Untitled Content');
        }

        // Sub Course Title: Strictly '-'
        $subCourseTitle = '-';

        // 4. Type (Video / Document / Playlist)
        $rawType = strtolower(trim((string) ($row->content_type ?? '')));
        $typeVal = in_array($rawType, ['video', 'youtube', 'mooc'], true)
            ? 'Video'
            : ($isPlaylist && empty($row->content_id) ? 'Playlist' : ($rawType ? ucfirst($rawType) : 'Document'));

        // 5. Instructor
        $instructorVal = trim((string) ($row->content_author ?? ($row->author ?? '')));
        if ($instructorVal === '' || $instructorVal === '-') {
            $instructorVal = 'Training Team';
        }

        // 6. Durations (HH:MM:SS format)
        $durationFormatted = '-';
        if (!empty($row->content_duration)) {
            $durationFormatted = DurationFormatter::format($row->content_duration, '-');
        }

        $recognizedService = $context['recognized_service'] ?? app(LearningRecordRecognizedDurationService::class);
        $recSec = $recognizedService->resolveFromRow($row);
        $recognizedFormatted = $recognizedService->formatSeconds($recSec);

        // 7. Learning Hours (decimal format 0.00)
        $lHoursVal = $row->content_training_hours ?? ($row->playlist_training_hours ?? ($row->learning_hours ?? 0));
        $lHoursNumeric = round((float) $lHoursVal, 2);

        // 8. Progress & Progress Status
        $progressNum = (float) ($row->progress ?? 0);
        $progressDecimal = round($progressNum / 100, 4);
        $isFinished = (($row->finish ?? 0) == '1' || $progressNum >= 100);
        $progressStatus = $isFinished ? 'Finished' : ($progressNum > 0 ? 'Ongoing' : 'Not Started');

        // 9. Dates and Times (YYYY-MM-DD and HH:MM:SS)
        $createdAt = !empty($row->created_at) ? strtotime($row->created_at) : null;
        $completedAt = (!empty($row->completed_at) && $isFinished) ? strtotime($row->completed_at) : null;

        $startDateStr = $createdAt ? date('Y-m-d', $createdAt) : '-';
        $endDateStr = ($completedAt && $isFinished) ? date('Y-m-d', $completedAt) : '-';
        $startTimeStr = $createdAt ? date('H:i:s', $createdAt) : '-';
        $endTimeStr = ($completedAt && $isFinished) ? date('H:i:s', $completedAt) : '-';
        $recordMonthStr = ($completedAt && $isFinished) ? date('F', $completedAt) : ($createdAt ? date('F', $createdAt) : '-');

        // 10. Requirement & Attendance
        $statusRaw = strtolower(trim((string) ($row->status ?? ($row->content_status ?? 'non mandatory'))));
        $requirementStr = ($statusRaw === 'mandatory') ? 'Mandatory' : 'Non-Mandatory';
        $attendanceStr = ($isFinished || $progressNum > 0) ? 'Yes' : 'No';

        // 11. Grade & Job Position
        $gradeVal = ($row->user_jobgrade ?? null) ?: (($row->jobgrade ?? null) ?: '-');
        $jobPosVal = ($row->user_jobposition ?? null) ?: (($row->jobposition ?? null) ?: '-');

        // 12. Assessment Scores
        $scoreL2Val = (isset($row->score_l2) && is_numeric($row->score_l2)) ? round((float) $row->score_l2, 2) : '-';

        // 13. Certificate
        $certAvailability = $isFinished ? 'Available' : '-';
        $certInstitution = $isFinished ? 'PT Krakatau Posco' : '-';
        $certIssuedDate = ($isFinished && $endDateStr !== '-') ? $endDateStr : '-';
        $certExpiredDate = '-';

        $contentCertSettings = $context['content_cert_settings'] ?? null;
        $tpCertSettings = $context['tp_cert_settings'] ?? null;
        $csSetting = null;
        if (!empty($row->content_id) && $contentCertSettings) {
            $csSetting = $contentCertSettings->get($row->content_id);
        } elseif (!empty($row->trainingplan_id) && $tpCertSettings) {
            $csSetting = $tpCertSettings->get($row->trainingplan_id);
        }
        if ($csSetting) {
            if (!empty($csSetting->institution)) {
                $certInstitution = $csSetting->institution;
            } elseif (!empty($csSetting->issuer_name)) {
                $certInstitution = $csSetting->issuer_name;
            }
            if (!empty($csSetting->issued_date)) {
                $certIssuedDate = date('Y-m-d', strtotime($csSetting->issued_date));
            }
            if (!empty($csSetting->expired_date)) {
                $certExpiredDate = date('Y-m-d', strtotime($csSetting->expired_date));
            }
        }

        // 14. PIC
        $picName = $context['pic_name'] ?? 'MOHAMAD NAFFISYA KANCANA GUMILANG';

        // 15. Total Engagement Time (HH:MM:SS)
        $afkSec = (int) ($row->afk_duration_seconds ?? 0);
        $afkFormatted = $afkSec > 0 ? DurationFormatter::fromSeconds($afkSec) : '00:00:00';

        $row->playlist = $parentPlaylistTitle;
        $row->Playlist = $parentPlaylistTitle;

        // Return sequential 51 columns with explicit keys
        return [
            'no'                        => $rowCount,                     // 1. NO
            'employee_id'               => $cleanEmpId,                   // 2. Employee ID
            'employee_name'             => ($row->employee_name ?? null) ?: (($row->user_name ?? null) ?: '-'), // 3. Employee Name
            'grade'                     => $gradeVal,                     // 4. Grade
            'job_position'              => $jobPosVal,                    // 5. Job Position
            'team'                      => ($row->user_team ?? null) ?: '-',        // 6. Team
            'department'                => ($row->user_department ?? null) ?: '-',  // 7. Department
            'company'                   => ($row->user_company ?? null) ?: (($row->company_name ?? null) ?: 'PT Krakatau Posco'), // 8. Company
            'course_title'              => $courseTitle,                  // 9. Course Title
            'playlist'                  => $parentPlaylistTitle,          // 10. Playlist
            'sub_course_title'          => $subCourseTitle,               // 11. Sub Course Title (Strictly '-')
            'type'                      => $typeVal,                      // 12. Type
            'category'                  => $category,                     // 13. Category
            'level'                     => '-',                           // 13. Level
            'batch'                     => '-',                           // 14. Batch
            'main_classification'       => ($row->classification_name ?? null) ?: 'Unclassified', // 15. Main Classification
            'sub_classification'        => '-',                           // 16. Sub Classification
            'delivery_method'           => 'Online',                      // 17. Delivery Method
            'sub_delivery_method'       => 'KP OWL',                  // 18. Sub Delivery Method
            'provider'                  => 'Internal',                    // 19. Provider
            'sub_provider'              => '-',                           // 20. Sub Provider
            'provider_name'             => 'KP-OWL',                      // 21. Provider Name
            'venue'                     => 'Online LMS',                  // 22. Venue
            'instructor'                => $instructorVal,                // 23. Instructor
            'course_duration'           => $durationFormatted,            // 24. Course Duration
            'total_recognized_duration' => $recognizedFormatted,          // 25. Total Recognized Duration
            'learning_hours'            => $lHoursNumeric,                // 26. Learning Hours
            'progress'                  => $progressDecimal,              // 27. % Progress
            'progress_status'           => $progressStatus,               // 28. Progress Status
            'start_date'                => $startDateStr,                 // 29. Start Date
            'end_date'                  => $endDateStr,                   // 30. End Date
            'start_time'                => $startTimeStr,                 // 31. Start Time
            'end_time'                  => $endTimeStr,                   // 32. End Time
            'record_month'              => $recordMonthStr,               // 33. Record Month
            'requirement'               => $requirementStr,               // 34. Requirement
            'attendance'                => $attendanceStr,                // 35. Attendance
            'attendance_reason'         => '-',                           // 36. Attendance Reason
            'bond_status'               => '-',                           // 37. Bond Status
            'bond_duration'             => '-',                           // 38. Bond Duration
            'bond_end_date'             => '-',                           // 39. Bond End Date
            'bond_amount'               => '-',                           // 40. Bond Amount
            'score_l1'                  => '-',                           // 41. Score (L1) - Course
            'score_l2'                  => $scoreL2Val,                   // 42. Score (L2)
            'l2_status'                 => '-',                           // 43. L2 Status
            'score_l3'                  => '-',                           // 44. Score (L3)
            'l3_status'                 => '-',                           // 45. L3 Status
            'certificate_availability'  => $certAvailability,             // 46. Certificate Availability
            'certificate_institution'   => $certInstitution,              // 47. Certificate Institution
            'certificate_issued_date'   => $certIssuedDate,               // 48. Certificate Issued Date
            'certificate_expired_date'  => $certExpiredDate,              // 49. Certificate Expired Date
            'pic'                       => $picName,                      // 50. Person in Charge (PIC)
            'total_engagement_time'     => $afkFormatted,                 // 52. Total Engagement Time
        ];
    }

    /**
     * Build PhpSpreadsheet instance with explicit cell data formatting:
     * - Employee ID: setCellValueExplicit('B'.$row, (string)$empId, DataType::TYPE_STRING)
     * - Hours & Scores: getNumberFormat()->setFormatCode('0.00')
     * - Pure Integers (NO, Bond Duration): getNumberFormat()->setFormatCode('0')
     * - Currency (Bond Amount): getNumberFormat()->setFormatCode('#,##0')
     * - Dates & Times: formatted as YYYY-MM-DD and HH:MM:SS strings
     */
    public function exportSpreadsheet(array $filters = [], ?string $classification = null): Spreadsheet
    {
        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('KP OWL Online Records');

        // Apply column widths
        foreach (self::columnWidths() as $colLetter => $width) {
            $sheet->getColumnDimension($colLetter)->setWidth($width);
        }

        // Header Row (Row 1)
        $headings = self::headings();
        foreach ($headings as $idx => $heading) {
            $colLetter = Coordinate::stringFromColumnIndex($idx + 1);
            $sheet->setCellValueExplicit("{$colLetter}1", $heading, DataType::TYPE_STRING);
        }

        // Style Header Row (Dark Blue background, White bold text, Centered)
        $highestCol = Coordinate::stringFromColumnIndex(count($headings));
        $sheet->getStyle("A1:{$highestCol}1")->applyFromArray([
            'font' => [
                'bold'  => true,
                'color' => ['rgb' => 'FFFFFF'],
                'size'  => 10,
                'name'  => 'Calibri',
            ],
            'fill' => [
                'fillType'   => Fill::FILL_SOLID,
                'startColor' => ['rgb' => '002B49'],
            ],
            'alignment' => [
                'horizontal' => Alignment::HORIZONTAL_CENTER,
                'vertical'   => Alignment::VERTICAL_CENTER,
                'wrapText'   => true,
            ],
        ]);
        $sheet->getRowDimension(1)->setRowHeight(28);

        // Pre-query context data to avoid N+1 queries
        $contentCertSettings = DB::table('certificate_settings')
            ->whereNotNull('content_id')
            ->get()
            ->keyBy('content_id');
        $tpCertSettings = DB::table('certificate_settings')
            ->whereNotNull('trainingplan_id')
            ->get()
            ->keyBy('trainingplan_id');
        $adminPic = DB::table('users')
            ->where('team', 'LIKE', '%Training Team%')
            ->where(function ($q) {
                $q->where('id_emp', '129458')->orWhere('name', 'LIKE', '%NAFFISYA%');
            })
            ->value('name') ?: 'MOHAMAD NAFFISYA KANCANA GUMILANG';

        $tpdRows = DB::table('trainingplandetail as tpd')
            ->join('trainingplan as tp', 'tp.id', '=', 'tpd.trainingplan_id')
            ->whereNull('tp.deleted_at')
            ->orderBy('tpd.id', 'asc')
            ->select('tpd.content_id', 'tp.id as tp_id', 'tp.title as tp_title')
            ->get();
        $tpdCache = [];
        foreach ($tpdRows as $tpd) {
            if (!isset($tpdCache[$tpd->content_id])) {
                $tpdCache[$tpd->content_id] = [
                    'id'    => (int) $tpd->tp_id,
                    'title' => (string) $tpd->tp_title,
                ];
            }
        }

        $context = [
            'recognized_service'    => app(LearningRecordRecognizedDurationService::class),
            'content_cert_settings' => $contentCertSettings,
            'tp_cert_settings'      => $tpCertSettings,
            'pic_name'              => $adminPic,
            'tpd_cache'             => $tpdCache,
        ];

        $query = $this->buildQuery($filters, $classification);
        $rowIndex = 1;

        foreach ($query->cursor() as $record) {
            $rowIndex++;
            $mapped = $this->mapRow($record, $rowIndex - 1, $context);
            $values = array_values(array_slice($mapped, 0, 52));

            foreach ($values as $colIdx => $val) {
                $colLetter = Coordinate::stringFromColumnIndex($colIdx + 1);
                $cellCoord = "{$colLetter}{$rowIndex}";

                // Explicit Cell Data Formatting Rules:
                if ($colLetter === 'B') {
                    // Employee ID: Force Explicit String to preserve leading zeros
                    $sheet->setCellValueExplicit($cellCoord, (string) $val, DataType::TYPE_STRING);
                } elseif ($colLetter === 'A') {
                    // NO: Pure Integer
                    $sheet->setCellValueExplicit($cellCoord, (int) $val, DataType::TYPE_NUMERIC);
                } elseif ($colLetter === 'Y' || $colLetter === 'Z' || $colLetter === 'AZ') {
                    // Duration Columns (Course Duration, Total Recognized Duration, Total Engagement Time):
                    // Convert seconds to Excel date fraction ($sec / 86400) so Excel SUM works!
                    $sec = ($val !== '-' && $val !== '' && $val !== null) ? DurationFormatter::toSeconds((string) $val) : null;
                    if ($sec !== null && $sec >= 0) {
                        $sheet->setCellValueExplicit($cellCoord, $sec / 86400, DataType::TYPE_NUMERIC);
                    } else {
                        $sheet->setCellValueExplicit($cellCoord, '-', DataType::TYPE_STRING);
                    }
                } elseif ($colLetter === 'AF' || $colLetter === 'AG') {
                    // Time Columns (Start Time, End Time)
                    $sec = ($val !== '-' && $val !== '' && $val !== null) ? DurationFormatter::toSeconds((string) $val) : null;
                    if ($sec !== null && $sec >= 0) {
                        $sheet->setCellValueExplicit($cellCoord, $sec / 86400, DataType::TYPE_NUMERIC);
                    } else {
                        $sheet->setCellValueExplicit($cellCoord, (string) ($val ?? '-'), DataType::TYPE_STRING);
                    }
                } elseif ($colLetter === 'AA') {
                    // Learning Hours: Numeric decimal format 0.00" h"
                    if (is_numeric($val)) {
                        $sheet->setCellValueExplicit($cellCoord, (float) $val, DataType::TYPE_NUMERIC);
                    } else {
                        $sheet->setCellValueExplicit($cellCoord, '-', DataType::TYPE_STRING);
                    }
                } elseif ($colLetter === 'AB') {
                    // % Progress: Numeric decimal with 0% format
                    if (is_numeric($val)) {
                        $sheet->setCellValueExplicit($cellCoord, (float) $val, DataType::TYPE_NUMERIC);
                    } else {
                        $sheet->setCellValueExplicit($cellCoord, 0.0, DataType::TYPE_NUMERIC);
                    }
                } elseif ($colLetter === 'AL' || $colLetter === 'AM' || $colLetter === 'AN' || $colLetter === 'AO') {
                    // Bond fields (Status, Duration, End Date, Amount): strictly '-' for KP OWL
                    $sheet->setCellValueExplicit($cellCoord, '-', DataType::TYPE_STRING);
                } elseif ($colLetter === 'AP' || $colLetter === 'AQ' || $colLetter === 'AS') {
                    // Scores: Decimal format 0.00
                    if (is_numeric($val)) {
                        $sheet->setCellValueExplicit($cellCoord, (float) $val, DataType::TYPE_NUMERIC);
                    } else {
                        $sheet->setCellValueExplicit($cellCoord, '-', DataType::TYPE_STRING);
                    }
                } else {
                    $sheet->setCellValueExplicit($cellCoord, (string) ($val ?? '-'), DataType::TYPE_STRING);
                }
            }
        }

        // Apply Global Number Formatting Rules
        if ($rowIndex >= 2) {
            // Pure Integers (NO: A) -> Format as '0' without .0 trailing decimals
            $sheet->getStyle("A2:A{$rowIndex}")->getNumberFormat()->setFormatCode('0');

            // Durations (Course Duration: Y, Total Recognized Duration: Z, Total Engagement Time: AZ) -> '[hh]:mm:ss'
            $sheet->getStyle("Y2:Y{$rowIndex}")->getNumberFormat()->setFormatCode('[hh]:mm:ss');
            $sheet->getStyle("Z2:Z{$rowIndex}")->getNumberFormat()->setFormatCode('[hh]:mm:ss');
            $sheet->getStyle("AZ2:AZ{$rowIndex}")->getNumberFormat()->setFormatCode('[hh]:mm:ss');

            // Times (Start Time: AF, End Time: AG) -> 'hh:mm:ss'
            $sheet->getStyle("AF2:AF{$rowIndex}")->getNumberFormat()->setFormatCode('hh:mm:ss');
            $sheet->getStyle("AG2:AG{$rowIndex}")->getNumberFormat()->setFormatCode('hh:mm:ss');

            // Hours & Scores (Learning Hours: AA, Scores: AP, AQ, AS)
            $sheet->getStyle("AA2:AA{$rowIndex}")->getNumberFormat()->setFormatCode('0.00" h"');
            $sheet->getStyle("AP2:AP{$rowIndex}")->getNumberFormat()->setFormatCode('0.00');
            $sheet->getStyle("AQ2:AQ{$rowIndex}")->getNumberFormat()->setFormatCode('0.00');
            $sheet->getStyle("AS2:AS{$rowIndex}")->getNumberFormat()->setFormatCode('0.00');

            // % Progress (AB) -> Format as '0%'
            $sheet->getStyle("AB2:AB{$rowIndex}")->getNumberFormat()->setFormatCode('0%');

            // Dates (Start Date: AD, End Date: AE, Issued Date: AW, Expired Date: AX) -> 'yyyy-mm-dd'
            $sheet->getStyle("AD2:AD{$rowIndex}")->getNumberFormat()->setFormatCode('yyyy-mm-dd');
            $sheet->getStyle("AE2:AE{$rowIndex}")->getNumberFormat()->setFormatCode('yyyy-mm-dd');
            $sheet->getStyle("AW2:AW{$rowIndex}")->getNumberFormat()->setFormatCode('yyyy-mm-dd');
            $sheet->getStyle("AX2:AX{$rowIndex}")->getNumberFormat()->setFormatCode('yyyy-mm-dd');

            // Center alignment across all data cells
            $sheet->getStyle("A2:{$highestCol}{$rowIndex}")->applyFromArray([
                'alignment' => [
                    'horizontal' => Alignment::HORIZONTAL_CENTER,
                    'vertical'   => Alignment::VERTICAL_CENTER,
                ],
                'font' => [
                    'size' => 10,
                    'name' => 'Calibri',
                ],
            ]);
        }

        $sheet->freezePane('A2');
        return $spreadsheet;
    }

    /**
     * Fast XML streaming generator implementing identical 51 canonical columns (A-AY)
     * and explicit number formats to safely export hundreds of thousands of records.
     */
    public function generateXlsxFile(array $filters = [], ?string $classification = null): string
    {
        ini_set('memory_limit', '2048M');
        ini_set('max_execution_time', 600);
        if (function_exists('set_time_limit')) {
            @set_time_limit(600);
        }

        $tempXlsx = tempnam(sys_get_temp_dir(), 'kp_owl_record_') . '.xlsx';
        $opt = new ArchiveOptions();
        $opt->setSendHttpHeaders(false);
        $fh = fopen($tempXlsx, 'w+b');
        $opt->setOutputStream($fh);

        $zip = new ZipStream(null, $opt);

        // 1. [Content_Types].xml
        $zip->addFile(
            '[Content_Types].xml',
            '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            . '<Types xmlns="http://schemas.openxmlformats.org/package/2006/content-types">'
            . '<Default Extension="rels" ContentType="application/vnd.openxmlformats-package.relationships+xml"/>'
            . '<Default Extension="xml" ContentType="application/xml"/>'
            . '<Override PartName="/xl/workbook.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.sheet.main+xml"/>'
            . '<Override PartName="/xl/worksheets/sheet1.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.worksheet+xml"/>'
            . '<Override PartName="/xl/styles.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.styles+xml"/>'
            . '</Types>'
        );

        // 2. _rels/.rels
        $zip->addFile(
            '_rels/.rels',
            '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            . '<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">'
            . '<Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/officeDocument" Target="xl/workbook.xml"/>'
            . '</Relationships>'
        );

        // 3. xl/_rels/workbook.xml.rels
        $zip->addFile(
            'xl/_rels/workbook.xml.rels',
            '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            . '<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">'
            . '<Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/worksheet" Target="worksheets/sheet1.xml"/>'
            . '<Relationship Id="rId2" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/styles" Target="styles.xml"/>'
            . '</Relationships>'
        );

        // 4. xl/workbook.xml
        $sheetTitle = !empty($classification) ? 'Classification ' . ucwords(str_replace('-', ' ', $classification)) : 'KP OWL Online Records';
        $cleanSheetTitle = $this->escapeXml(substr($sheetTitle, 0, 31));

        $zip->addFile(
            'xl/workbook.xml',
            '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            . '<workbook xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main" xmlns:r="http://schemas.openxmlformats.org/officeDocument/2006/relationships">'
            . '<sheets><sheet name="' . $cleanSheetTitle . '" sheetId="1" r:id="rId1"/></sheets>'
            . '</workbook>'
        );

        // 5. xl/styles.xml with explicit number formats: 0.00, 0, #,##0, yyyy-mm-dd, hh:mm:ss, [hh]:mm:ss, 0%
        $zip->addFile(
            'xl/styles.xml',
            '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            . '<styleSheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main">'
            . '<numFmts count="8">'
            . '<numFmt numFmtId="164" formatCode="yyyy-mm-dd"/>'
            . '<numFmt numFmtId="165" formatCode="hh:mm:ss"/>'
            . '<numFmt numFmtId="166" formatCode="0.00"/>'
            . '<numFmt numFmtId="167" formatCode="#,##0"/>'
            . '<numFmt numFmtId="168" formatCode="0"/>'
            . '<numFmt numFmtId="169" formatCode="[hh]:mm:ss"/>'
            . '<numFmt numFmtId="170" formatCode="0%"/>'
            . '<numFmt numFmtId="171" formatCode="0.00&quot; h&quot;"/>'
            . '</numFmts>'
            . '<fonts count="2">'
            . '<font><sz val="10"/><name val="Calibri"/></font>'
            . '<font><b/><sz val="10"/><color rgb="FFFFFFFF"/><name val="Calibri"/></font>'
            . '</fonts>'
            . '<fills count="3">'
            . '<fill><patternFill patternType="none"/></fill>'
            . '<fill><patternFill patternType="gray125"/></fill>'
            . '<fill><patternFill patternType="solid"><fgColor rgb="FF002B49"/><bgColor rgb="FF002B49"/></patternFill></fill>'
            . '</fills>'
            . '<borders count="1">'
            . '<border><left/><right/><top/><bottom/><diagonal/></border>'
            . '</borders>'
            . '<cellStyleXfs count="1">'
            . '<xf numFmtId="0" fontId="0" fillId="0" borderId="0"/>'
            . '</cellStyleXfs>'
            . '<cellXfs count="10">'
            . '<xf numFmtId="49" fontId="0" fillId="0" borderId="0" xfId="0" applyNumberFormat="1" applyAlignment="1"><alignment horizontal="center" vertical="center"/></xf>' // s=0 (string centered)
            . '<xf numFmtId="0" fontId="1" fillId="2" borderId="0" xfId="0" applyFont="1" applyFill="1" applyAlignment="1"><alignment horizontal="center" vertical="center" wrapText="1"/></xf>' // s=1 (header dark blue)
            . '<xf numFmtId="168" fontId="0" fillId="0" borderId="0" xfId="0" applyNumberFormat="1" applyAlignment="1"><alignment horizontal="center" vertical="center"/></xf>' // s=2 (pure integer 0)
            . '<xf numFmtId="166" fontId="0" fillId="0" borderId="0" xfId="0" applyNumberFormat="1" applyAlignment="1"><alignment horizontal="center" vertical="center"/></xf>' // s=3 (decimal 0.00)
            . '<xf numFmtId="167" fontId="0" fillId="0" borderId="0" xfId="0" applyNumberFormat="1" applyAlignment="1"><alignment horizontal="center" vertical="center"/></xf>' // s=4 (currency #,##0)
            . '<xf numFmtId="164" fontId="0" fillId="0" borderId="0" xfId="0" applyNumberFormat="1" applyAlignment="1"><alignment horizontal="center" vertical="center"/></xf>' // s=5 (date yyyy-mm-dd)
            . '<xf numFmtId="165" fontId="0" fillId="0" borderId="0" xfId="0" applyNumberFormat="1" applyAlignment="1"><alignment horizontal="center" vertical="center"/></xf>' // s=6 (time hh:mm:ss)
            . '<xf numFmtId="169" fontId="0" fillId="0" borderId="0" xfId="0" applyNumberFormat="1" applyAlignment="1"><alignment horizontal="center" vertical="center"/></xf>' // s=7 (duration [hh]:mm:ss)
            . '<xf numFmtId="170" fontId="0" fillId="0" borderId="0" xfId="0" applyNumberFormat="1" applyAlignment="1"><alignment horizontal="center" vertical="center"/></xf>' // s=8 (percentage 0%)
            . '<xf numFmtId="171" fontId="0" fillId="0" borderId="0" xfId="0" applyNumberFormat="1" applyAlignment="1"><alignment horizontal="center" vertical="center"/></xf>' // s=9 (learning hours 0.00 h)
            . '</cellXfs>'
            . '</styleSheet>'
        );

        // 6. Temporary stream for sheet1.xml
        $sheetFile = tempnam(sys_get_temp_dir(), 'xlsx_online_sheet_');
        $sfh = fopen($sheetFile, 'w+b');

        fwrite($sfh, '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>' . "\n");
        fwrite($sfh, '<worksheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main">' . "\n");
        fwrite($sfh, '<cols>' . "\n");
        foreach (self::columnWidths() as $colLetter => $w) {
            $colIdx = Coordinate::columnIndexFromString($colLetter);
            fwrite($sfh, "<col min=\"{$colIdx}\" max=\"{$colIdx}\" width=\"{$w}\" customWidth=\"1\"/>\n");
        }
        fwrite($sfh, '</cols>' . "\n");
        fwrite($sfh, '<sheetData>' . "\n");

        // Header row (Row 1) - Dark blue background, bold white text
        $headers = self::headings();
        fwrite($sfh, '<row r="1" ht="28" customHeight="1">' . "\n");
        foreach ($headers as $i => $h) {
            $coord = Coordinate::stringFromColumnIndex($i + 1) . '1';
            $val = $this->escapeXml($h);
            fwrite($sfh, "<c r=\"{$coord}\" t=\"inlineStr\" s=\"1\"><is><t>{$val}</t></is></c>\n");
        }
        fwrite($sfh, '</row>' . "\n");

        // Preload context settings
        $contentCertSettings = DB::table('certificate_settings')
            ->whereNotNull('content_id')
            ->get()
            ->keyBy('content_id');
        $tpCertSettings = DB::table('certificate_settings')
            ->whereNotNull('trainingplan_id')
            ->get()
            ->keyBy('trainingplan_id');
        $adminPic = DB::table('users')
            ->where('team', 'LIKE', '%Training Team%')
            ->where(function ($q) {
                $q->where('id_emp', '129458')->orWhere('name', 'LIKE', '%NAFFISYA%');
            })
            ->value('name') ?: 'MOHAMAD NAFFISYA KANCANA GUMILANG';

        $tpdRows = DB::table('trainingplandetail as tpd')
            ->join('trainingplan as tp', 'tp.id', '=', 'tpd.trainingplan_id')
            ->whereNull('tp.deleted_at')
            ->orderBy('tpd.id', 'asc')
            ->select('tpd.content_id', 'tp.id as tp_id', 'tp.title as tp_title')
            ->get();
        $tpdCache = [];
        foreach ($tpdRows as $tpd) {
            if (!isset($tpdCache[$tpd->content_id])) {
                $tpdCache[$tpd->content_id] = [
                    'id'    => (int) $tpd->tp_id,
                    'title' => (string) $tpd->tp_title,
                ];
            }
        }

        $context = [
            'recognized_service'    => app(LearningRecordRecognizedDurationService::class),
            'content_cert_settings' => $contentCertSettings,
            'tp_cert_settings'      => $tpCertSettings,
            'pic_name'              => $adminPic,
            'tpd_cache'             => $tpdCache,
        ];

        $query = $this->buildQuery($filters, $classification);
        $rowCount = 0;
        $excelRow = 1;
        $buffer = '';
        $batchSize = 1000;

        $pdo = DB::connection()->getPdo();
        $wasBuffered = $pdo->getAttribute(\PDO::MYSQL_ATTR_USE_BUFFERED_QUERY);
        $pdo->setAttribute(\PDO::MYSQL_ATTR_USE_BUFFERED_QUERY, false);

        try {
            foreach ($query->cursor() as $record) {
                $rowCount++;
                $excelRow++;
                $r = $excelRow;

                $mapped = $this->mapRow($record, $rowCount, $context);
                $values = array_values(array_slice($mapped, 0, 52));

                $xmlRow = "<row r=\"{$r}\">";
                foreach ($values as $colIdx => $val) {
                    $colLetter = Coordinate::stringFromColumnIndex($colIdx + 1);
                    $coord = "{$colLetter}{$r}";

                    if ($val === null || $val === '' || $val === '-') {
                        $xmlRow .= "<c r=\"{$coord}\" t=\"inlineStr\" s=\"0\"><is><t>-</t></is></c>";
                    } elseif ($colLetter === 'B') {
                        // Employee ID: Explicit String (s=0, t=inlineStr)
                        $escaped = $this->escapeXml((string) $val);
                        $xmlRow .= "<c r=\"{$coord}\" t=\"inlineStr\" s=\"0\"><is><t>{$escaped}</t></is></c>";
                    } elseif ($colLetter === 'A') {
                        // NO: Pure Integer (s=2, format 0)
                        $intVal = (int) $val;
                        $xmlRow .= "<c r=\"{$coord}\" s=\"2\"><v>{$intVal}</v></c>";
                    } elseif ($colLetter === 'Y' || $colLetter === 'Z' || $colLetter === 'AZ') {
                        // Duration Columns: Course Duration (Y), Total Recognized Duration (Z), Total Engagement Time (AZ)
                        // Numeric Excel date fraction ($sec / 86400) with format [hh]:mm:ss (s=7) so Excel SUM works!
                        $sec = ($val !== '-' && $val !== '' && $val !== null) ? DurationFormatter::toSeconds((string) $val) : null;
                        if ($sec !== null && $sec >= 0) {
                            $fraction = $sec / 86400;
                            $xmlRow .= "<c r=\"{$coord}\" s=\"7\"><v>{$fraction}</v></c>";
                        } else {
                            $xmlRow .= "<c r=\"{$coord}\" t=\"inlineStr\" s=\"0\"><is><t>-</t></is></c>";
                        }
                    } elseif ($colLetter === 'AF' || $colLetter === 'AG') {
                        // Time Columns: Start Time (AF), End Time (AG) (s=6, format hh:mm:ss)
                        $sec = ($val !== '-' && $val !== '' && $val !== null) ? DurationFormatter::toSeconds((string) $val) : null;
                        if ($sec !== null && $sec >= 0) {
                            $fraction = $sec / 86400;
                            $xmlRow .= "<c r=\"{$coord}\" s=\"6\"><v>{$fraction}</v></c>";
                        } else {
                            $xmlRow .= "<c r=\"{$coord}\" t=\"inlineStr\" s=\"0\"><is><t>-</t></is></c>";
                        }
                    } elseif ($colLetter === 'AA') {
                        // Learning Hours: Numeric float with 0.00" h" format (s=9) so Excel can SUM natively!
                        if (is_numeric($val)) {
                            $decVal = number_format((float) $val, 2, '.', '');
                            $xmlRow .= "<c r=\"{$coord}\" s=\"9\"><v>{$decVal}</v></c>";
                        } else {
                            $xmlRow .= "<c r=\"{$coord}\" t=\"inlineStr\" s=\"0\"><is><t>-</t></is></c>";
                        }
                    } elseif ($colLetter === 'AP' || $colLetter === 'AQ' || $colLetter === 'AS') {
                        // Scores: Decimal 0.00 (s=3)
                        if (is_numeric($val)) {
                            $decVal = number_format((float) $val, 2, '.', '');
                            $xmlRow .= "<c r=\"{$coord}\" s=\"3\"><v>{$decVal}</v></c>";
                        } else {
                            $xmlRow .= "<c r=\"{$coord}\" t=\"inlineStr\" s=\"0\"><is><t>-</t></is></c>";
                        }
                    } elseif ($colLetter === 'AB') {
                        // % Progress: Numeric with 0% format (s=8)
                        if (is_numeric($val)) {
                            $pctVal = (float) $val;
                            $xmlRow .= "<c r=\"{$coord}\" s=\"8\"><v>{$pctVal}</v></c>";
                        } else {
                            $xmlRow .= "<c r=\"{$coord}\" s=\"8\"><v>0</v></c>";
                        }
                    } elseif ($colLetter === 'AL' || $colLetter === 'AM' || $colLetter === 'AN' || $colLetter === 'AO') {
                        // Bond fields: strictly '-'
                        $xmlRow .= "<c r=\"{$coord}\" t=\"inlineStr\" s=\"0\"><is><t>-</t></is></c>";
                    } elseif ($colLetter === 'AD' || $colLetter === 'AE' || $colLetter === 'AW' || $colLetter === 'AX') {
                        // Dates: YYYY-MM-DD (s=5)
                        $escaped = $this->escapeXml($val);
                        $xmlRow .= "<c r=\"{$coord}\" t=\"inlineStr\" s=\"5\"><is><t>{$escaped}</t></is></c>";
                    } else {
                        $escaped = $this->escapeXml($val);
                        $xmlRow .= "<c r=\"{$coord}\" t=\"inlineStr\" s=\"0\"><is><t>{$escaped}</t></is></c>";
                    }
                }
                $xmlRow .= "</row>\n";

                $buffer .= $xmlRow;
                if ($excelRow % $batchSize === 0) {
                    fwrite($sfh, $buffer);
                    $buffer = '';
                }
            }
        } finally {
            $pdo->setAttribute(\PDO::MYSQL_ATTR_USE_BUFFERED_QUERY, $wasBuffered);
        }

        if ($buffer !== '') {
            fwrite($sfh, $buffer);
            $buffer = '';
        }

        fwrite($sfh, '</sheetData>' . "\n");
        fwrite($sfh, '</worksheet>' . "\n");
        rewind($sfh);

        $zip->addFileFromStream('xl/worksheets/sheet1.xml', $sfh);
        fclose($sfh);
        @unlink($sheetFile);

        $zip->finish();
        fclose($fh);

        return $tempXlsx;
    }

    /**
     * Download full KP OWL record Excel file.
     */
    public function download(array $filters = [], ?string $fileName = null): BinaryFileResponse
    {
        ini_set('memory_limit', '2048M');
        ini_set('max_execution_time', 600);
        if (function_exists('set_time_limit')) {
            @set_time_limit(600);
        }

        $fileName = $fileName ?: self::generateFileName();
        $tempPath = $this->generateXlsxFile($filters);

        return response()->download(
            $tempPath,
            $fileName,
            [
                'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            ]
        )->deleteFileAfterSend(true);
    }

    /**
     * Download classification-filtered KP OWL record Excel file.
     */
    public function downloadClassification(?string $classification = null, array $filters = [], ?string $fileName = null): BinaryFileResponse
    {
        ini_set('memory_limit', '2048M');
        ini_set('max_execution_time', 600);
        if (function_exists('set_time_limit')) {
            @set_time_limit(600);
        }

        $prefix = !empty($classification) ? 'Classification_' . ucwords(str_replace('-', '_', $classification)) : 'EmployeeOWLrecord';
        $fileName = $fileName ?: self::generateFileName($prefix);
        $tempPath = $this->generateXlsxFile($filters, $classification);

        return response()->download(
            $tempPath,
            $fileName,
            [
                'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            ]
        )->deleteFileAfterSend(true);
    }

    protected function escapeXml($value): string
    {
        $clean = preg_replace('/[\x00-\x08\x0B\x0C\x0E-\x1F\x7F]/u', '', (string) $value);
        return htmlspecialchars($clean, ENT_XML1 | ENT_COMPAT, 'UTF-8');
    }
}
