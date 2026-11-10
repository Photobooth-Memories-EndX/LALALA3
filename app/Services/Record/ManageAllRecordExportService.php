<?php

namespace App\Services\Record;

use App\Exports\AllRecordExport;
use App\Exports\Manage\ClassificationAllRecordExport;
use App\Support\DurationFormatter;
use Carbon\Carbon;
use Illuminate\Database\Query\Builder;
use Illuminate\Support\Facades\DB;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\Cell\DataType;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use ZipStream\Option\Archive as ArchiveOptions;
use ZipStream\ZipStream;

class ManageAllRecordExportService
{
    /**
     * Canonical 52 columns for All Record Export:
     * NO | Source | Employee ID | Employee Name | Grade | Job Position | Team | Department | Company |
     * Course Title | Sub Course Title | Type | Category | Level | Batch | Main Classification | Sub Classification |
     * Delivery Method | Sub Delivery Method | Provider | Sub Provider | Provider Name | Venue | Instructor |
     * Course Duration | Total Recognized Duration | Learning Hours | % Progress | Progress Status |
     * Start Date | End Date | Start Time | End Time | Record Month | Requirement | Attendance | Attendance Reason |
     * Bond Status | Bond Duration | Bond End Date | Bond Amount | Score (L1) - Course | Score (L2) | L2 Status |
     * Score (L3) | L3 Status | Certificate Availability | Certificate Institution | Certificate Issued Date |
     * Certificate Expired Date | Person in Charge (PIC) | Total Engagement Time
     */
    public static function headings(): array
    {
        return [
            'NO',
            'Source',
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
     * Sequential 52 column widths spanning A through AZ.
     */
    public static function columnWidths(): array
    {
        return [
            'A'  => 6,   // NO
            'B'  => 14,  // Source
            'C'  => 16,  // Employee ID
            'D'  => 30,  // Employee Name
            'E'  => 12,  // Grade
            'F'  => 25,  // Job Position
            'G'  => 20,  // Team
            'H'  => 25,  // Department
            'I'  => 25,  // Company
            'J'  => 35,  // Course Title
            'K'  => 25,  // Playlist
            'L'  => 25,  // Sub Course Title
            'M'  => 15,  // Type
            'N'  => 18,  // Category
            'O'  => 12,  // Level
            'P'  => 12,  // Batch
            'Q'  => 25,  // Main Classification
            'R'  => 25,  // Sub Classification
            'S'  => 18,  // Delivery Method
            'T'  => 20,  // Sub Delivery Method
            'U'  => 16,  // Provider
            'V'  => 18,  // Sub Provider
            'W'  => 25,  // Provider Name
            'X'  => 20,  // Venue
            'Y'  => 22,  // Instructor
            'Z'  => 18,  // Course Duration
            'AA' => 22,  // Total Recognized Duration
            'AB' => 16,  // Learning Hours
            'AC' => 14,  // % Progress
            'AD' => 16,  // Progress Status
            'AE' => 14,  // Start Date
            'AF' => 14,  // End Date
            'AG' => 14,  // Start Time
            'AH' => 14,  // End Time
            'AI' => 16,  // Record Month
            'AJ' => 15,  // Requirement
            'AK' => 14,  // Attendance
            'AL' => 25,  // Attendance Reason
            'AM' => 14,  // Bond Status
            'AN' => 16,  // Bond Duration
            'AO' => 16,  // Bond End Date
            'AP' => 18,  // Bond Amount
            'AQ' => 20,  // Score (L1) - Course
            'AR' => 15,  // Score (L2)
            'AS' => 15,  // L2 Status
            'AT' => 15,  // Score (L3)
            'AU' => 15,  // L3 Status
            'AV' => 22,  // Certificate Availability
            'AW' => 25,  // Certificate Institution
            'AX' => 20,  // Certificate Issued Date
            'AY' => 20,  // Certificate Expired Date
            'AZ' => 25,  // Person in Charge (PIC)
            'BA' => 25,  // Total Engagement Time
        ];
    }

    /**
     * Generate canonical export file name.
     */
    public static function generateFileName(): string
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

        return "EmployeeAllrecord_{$day}{$month}{$year} at {$time}.xlsx";
    }

    /**
     * Build unified query for All Record export (combining KP OWL and Non KP OWL).
     */
    public function buildQuery(array $filters = [], ?string $classification = null): Builder
    {
        $effectiveFilters = $filters;
        if (!empty($classification)) {
            $effectiveFilters['classification'] = $classification;
            $effectiveFilters['classification_slug'] = $classification;
        }

        $service = app(RecordAllCombinedService::class);
        $query = $service->buildCombinedQuery($effectiveFilters);

        if (!empty($classification)) {
            $slugNorm = str_replace('-', ' ', strtolower(trim($classification)));
            $query->where(function ($q) use ($classification, $slugNorm) {
                $q->where('cl.classification_slug', $classification)
                    ->orWhere('ot.main_classification', $classification)
                    ->orWhereRaw('LOWER(TRIM(cl.classification_name)) = ?', [$slugNorm])
                    ->orWhereRaw('LOWER(TRIM(ot.main_classification)) = ?', [$slugNorm]);
            });
        }

        return $query->orderBy('raw_finish_date', 'desc')
            ->orderBy('record_date', 'desc');
    }

    /**
     * Map a single record row to the canonical 52 sequential columns.
     *
     * Rules:
     * - Sub Course Title: Follow standard catalog sub-course title rules without applying KP OWL playlist title overrides.
     * - Employee ID: Clean string preserving leading zeros.
     * - Pure Integers: NO (col A) and Bond Duration (col AM).
     * - Currency: Bond Amount (col AO) without trailing decimals.
     * - Decimals: Learning Hours (col AA), % Progress (col AB), and Scores (cols AP, AQ, AS).
     * - Dates & Times: YYYY-MM-DD and HH:MM:SS.
     */
    public function mapRow(object $row, int $rowCount, array $context = []): array
    {
        $sourceRaw = strtolower(trim((string) ($row->source ?? 'online')));
        $isOffline = ($sourceRaw === 'offline' || str_contains($sourceRaw, 'non'));
        $sourceLabel = $isOffline ? 'Non KP OWL' : 'KP OWL';

        // 1. Employee ID: raw string/integer only
        $rawEmp = ($row->employee_id ?? null) ?: (($row->id_emp ?? null) ?: ($row->user_id_emp ?? '-'));
        $cleanEmpId = ($rawEmp !== '-') ? preg_replace('/\s*[\/\-]\s*NIP.*$/i', '', trim((string) $rawEmp)) : '-';

        // 2. Titles: Course Title & Sub Course Title
        $courseTitle = ($row->learning_title ?? null)
            ?: (($row->course_title ?? null)
            ?: (($row->content_title ?? null)
            ?: (($row->playlist_title ?? null) ?: '-')));

        // Sub Course Title Logic: Standard catalog sub-course title rules without applying KP OWL playlist title overrides
        if ($isOffline) {
            $subCourseTitle = ($row->sub_course_name ?? null)
                ?: (($row->sub_course_title ?? null) ?: '-');
        } else {
            $subCourseTitle = '-';
        }

        // 3. Type, Category & Playlist
        $typeVal = $isOffline ? '-' : (($row->type ?? null) ?: 'Document');
        $parentPlaylistTitle = '-';
        if ($isOffline) {
            $categoryVal = 'Non KP-OWL';
        } else {
            $tpdCache = $context['tpd_cache'] ?? [];
            $contentId = !empty($row->content_id) ? (int) $row->content_id : null;
            $parentPlaylist = ($contentId && isset($tpdCache[$contentId])) ? $tpdCache[$contentId] : null;

            if (!empty($row->playlist_title)) {
                $parentPlaylistTitle = trim((string) $row->playlist_title);
                $categoryVal = 'Content Playlist';
            } elseif ($parentPlaylist) {
                $parentPlaylistTitle = $parentPlaylist['title'];
                $categoryVal = 'Content Playlist';
            } else {
                $parentPlaylistTitle = '-';
                $categoryVal = 'All Content';
            }
        }

        // 4. Delivery Method & Sub Delivery Method
        $delivMethod = $isOffline ? 'Offline' : 'Online';
        if ($isOffline) {
            $sub = ($row->sub_delivery_method ?? null) ?: (($row->training_type ?? null) ?: ($row->type ?? null));
            if (!$sub || $sub === '-' || strtolower(trim((string) $sub)) === 'offline') {
                $rawDm = trim((string) ($row->delivery_method ?? ''));
                if ($rawDm !== '' && strtolower($rawDm) !== 'offline') {
                    $sub = $rawDm;
                }
            }
            $subDelivMethod = $sub ?: '-';
        } else {
            $subDelivMethod = 'KP OWL';
        }

        // 5. Providers & Venue
        $providerVal = $isOffline ? (($row->provider ?? null) ?: '-') : 'Internal';
        $subProviderVal = $isOffline ? (($row->sub_provider ?? null) ?: '-') : '-';
        $providerNameVal = ($row->provider_name ?? null) ?: ($isOffline ? '-' : 'KP-OWL');
        $venueVal = ($row->venue ?? null) ?: ($isOffline ? '-' : 'Online LMS');

        // 6. Instructor
        $instructorVal = ($row->instructor ?? null) ?: (($row->author ?? null) ?: '-');

        // 7. Durations (HH:MM:SS)
        $durationFormatted = '-';
        $durSecs = (int) ($row->duration_seconds ?? ($row->duration ?? 0));
        if ($durSecs > 0) {
            $durationFormatted = DurationFormatter::fromSeconds($durSecs);
        }

        // Recognized Duration
        $recognizedFormatted = '-';
        if (!$isOffline) {
            $recSecs = (int) ($row->recognized_duration_seconds ?? 0);
            if ($recSecs <= 0 && isset($context['recognized_service'])) {
                $recSecs = $context['recognized_service']->resolveFromRow($row);
            }
            if ($recSecs > 0) {
                $recognizedFormatted = DurationFormatter::fromSeconds($recSecs);
            }
        }

        // Total Engagement Duration (HH:MM:SS)
        $engSecs = (int) ($row->afk_duration_seconds ?? 0);
        if ($engSecs <= 0 && $isOffline) {
            $engSecs = (int) round(((float) ($row->training_hours ?? ($row->learning_hours ?? 0))) * 3600);
        }
        $totalEngagementDuration = $engSecs > 0 ? DurationFormatter::fromSeconds($engSecs) : '00:00:00';

        // 8. Learning Hours (raw numeric float for SUM calculations)
        $lhVal = ($row->learning_hours !== null && $row->learning_hours !== '' && is_numeric($row->learning_hours))
            ? round((float) $row->learning_hours, 2)
            : 0.00;

        // 9. Progress & Progress Status
        $progressNum = (float) ($row->progress ?? 0);
        $progressDecimal = round($progressNum / 100, 4);
        $isFinished = ($progressNum >= 100 || ($row->status ?? '') === 'Finished' || ($row->finish ?? '') === '1');
        $progressStatus = $isFinished ? 'Finished' : ($progressNum > 0 ? 'Ongoing' : 'Not Started');

        // 10. Dates and Times (YYYY-MM-DD and HH:MM:SS)
        $startDateStr = !empty($row->start_date) ? date('Y-m-d', strtotime($row->start_date)) : '-';
        $endDateStr = !empty($row->end_date)
            ? date('Y-m-d', strtotime($row->end_date))
            : (!empty($row->finish_date) ? date('Y-m-d', strtotime($row->finish_date)) : '-');

        $startTimeStr = !empty($row->start_time) ? date('H:i:s', strtotime($row->start_time)) : '-';
        $endTimeStr = !empty($row->end_time) ? date('H:i:s', strtotime($row->end_time)) : '-';

        $recordMonthDate = ($row->start_date ?? null) ?: (($row->finish_date ?? null) ?: ($row->record_date ?? null));
        $recordMonthStr = !empty($recordMonthDate) ? date('F', strtotime($recordMonthDate)) : '-';
        if (!$isOffline && $endDateStr !== '-') {
            $recordMonthStr = date('F', strtotime($endDateStr));
        }

        // 11. Requirement & Attendance
        $requirementStr = 'Mandatory';
        if (!empty($row->requirement)) {
            $requirementStr = ucfirst(strtolower(trim($row->requirement)));
        } elseif (!empty($row->content_status)) {
            $requirementStr = (strtolower(trim($row->content_status)) === 'mandatory') ? 'Mandatory' : 'Non-Mandatory';
        }

        $attendanceStr = '-';
        if (isset($row->attendance) && $row->attendance !== null && $row->attendance !== '') {
            $rawAtt = strtolower((string) $row->attendance);
            $attendanceStr = ((bool) $row->attendance || in_array($rawAtt, ['1', 'yes', 'present', 'true'], true)) ? 'Yes' : 'No';
        } elseif (!$isOffline) {
            $attendanceStr = ($progressNum >= 100) ? 'Yes' : (($progressNum > 0) ? 'No' : '-');
        }

        // 12. Bond Details (Strictly '-' for KP OWL)
        if (!$isOffline) {
            $bondStatusStr = '-';
            $bondDurationInt = '-';
            $bondEndDateStr = '-';
            $bondAmountNumeric = '-';
        } else {
            $idr = ($row->bond_amount_idr ?? null) ?: ($row->bond_amount ?? null);
            $bondAmountNumeric = ($idr !== null && is_numeric($idr) && (float) $idr > 0) ? (float) $idr : 0;
            $hasBond = ($bondAmountNumeric > 0 || strtolower(trim((string) ($row->bond_status ?? ''))) === 'yes');

            $bondStatusStr = $hasBond ? 'Yes' : 'No';
            $bondDurationInt = (!empty($row->bond_duration) && is_numeric($row->bond_duration)) ? (int) $row->bond_duration : 0;
            $bondEndDateStr = !empty($row->bond_end_date) ? date('Y-m-d', strtotime($row->bond_end_date)) : '-';
        }

        // 13. Assessment Scores
        $scoreL1 = (isset($row->l1_program) && is_numeric($row->l1_program)) ? round((float) $row->l1_program, 2) : '-';
        $scoreL2 = null;
        if (isset($row->assessment_score) && is_numeric($row->assessment_score)) {
            $scoreL2 = round((float) $row->assessment_score, 2);
        } elseif (isset($row->l2_numerical) && is_numeric($row->l2_numerical)) {
            $scoreL2 = round((float) $row->l2_numerical, 2);
        } elseif (isset($row->score_l2) && is_numeric($row->score_l2)) {
            $scoreL2 = round((float) $row->score_l2, 2);
        }
        $scoreL2Val = ($scoreL2 !== null) ? $scoreL2 : '-';

        $l2StatusVal = ($row->l2_verbal ?? null) ?: ($scoreL2 !== null ? ($scoreL2 >= 70 ? 'Passed' : 'Failed') : '-');
        $scoreL3 = (isset($row->l3_numerical) && is_numeric($row->l3_numerical)) ? round((float) $row->l3_numerical, 2) : '-';
        $l3StatusVal = ($row->l3_verbal ?? null) ?: '-';

        // 14. Certificate Details
        $hasCert = false;
        $certInstitution = '-';
        $certIssuedDate = '-';
        $certExpiredDate = '-';

        if ($isOffline) {
            if (!empty($row->certificate) && $row->certificate !== '-') {
                $hasCert = true;
                $certInstitution = $row->certificate;
            }
            $offlineUserCerts = $context['offline_user_certs'] ?? null;
            $dbCert = (!empty($row->source_id) && $offlineUserCerts) ? $offlineUserCerts->get($row->source_id) : null;
            if ($dbCert) {
                $hasCert = true;
                $certInstitution = $dbCert->c_institution ?: ($dbCert->cs_institution ?: ($dbCert->issuer_name ?: $certInstitution));
                if (!empty($dbCert->issued_date)) {
                    $certIssuedDate = date('Y-m-d', strtotime($dbCert->issued_date));
                } elseif (!empty($dbCert->issued_at)) {
                    $certIssuedDate = date('Y-m-d', strtotime($dbCert->issued_at));
                }
                if (!empty($dbCert->expired_date)) {
                    $certExpiredDate = date('Y-m-d', strtotime($dbCert->expired_date));
                } elseif (!empty($dbCert->expired_at)) {
                    $certExpiredDate = date('Y-m-d', strtotime($dbCert->expired_at));
                }
            }
            if (!$dbCert && !empty($row->offline_training_id)) {
                $offlineCertSettings = $context['offline_cert_settings'] ?? null;
                $csSetting = $offlineCertSettings ? $offlineCertSettings->get($row->offline_training_id) : null;
                if ($csSetting) {
                    $hasCert = true;
                    $certInstitution = $csSetting->institution ?: ($csSetting->issuer_name ?: $certInstitution);
                    if (!empty($csSetting->issued_date)) {
                        $certIssuedDate = date('Y-m-d', strtotime($csSetting->issued_date));
                    }
                    if (!empty($csSetting->expired_date)) {
                        $certExpiredDate = date('Y-m-d', strtotime($csSetting->expired_date));
                    }
                }
            }
        } else {
            $contentUserCerts = $context['content_user_certs'] ?? null;
            $dbCert = (!empty($row->source_id) && $contentUserCerts) ? $contentUserCerts->get($row->source_id) : null;
            if ($dbCert) {
                $hasCert = true;
                $certInstitution = $dbCert->c_institution ?: ($dbCert->cs_institution ?: ($dbCert->issuer_name ?: 'PT Krakatau Posco'));
                if (!empty($dbCert->issued_date)) {
                    $certIssuedDate = date('Y-m-d', strtotime($dbCert->issued_date));
                } elseif (!empty($dbCert->issued_at)) {
                    $certIssuedDate = date('Y-m-d', strtotime($dbCert->issued_at));
                }
                if (!empty($dbCert->expired_date)) {
                    $certExpiredDate = date('Y-m-d', strtotime($dbCert->expired_date));
                } elseif (!empty($dbCert->expired_at)) {
                    $certExpiredDate = date('Y-m-d', strtotime($dbCert->expired_at));
                }
            }
            if (!$dbCert && !empty($row->content_id)) {
                $contentCertSettings = $context['content_cert_settings'] ?? null;
                $csSetting = $contentCertSettings ? $contentCertSettings->get($row->content_id) : null;
                if ($csSetting) {
                    $hasCert = true;
                    $certInstitution = $csSetting->institution ?: ($csSetting->issuer_name ?: 'PT Krakatau Posco');
                    if (!empty($csSetting->issued_date)) {
                        $certIssuedDate = date('Y-m-d', strtotime($csSetting->issued_date));
                    }
                    if (!empty($csSetting->expired_date)) {
                        $certExpiredDate = date('Y-m-d', strtotime($csSetting->expired_date));
                    }
                }
            }
            if (!$hasCert && $isFinished) {
                $hasCert = true;
                if ($certInstitution === '-') $certInstitution = 'PT Krakatau Posco';
                if ($certIssuedDate === '-') $certIssuedDate = $endDateStr !== '-' ? $endDateStr : ($startDateStr !== '-' ? $startDateStr : '-');
            }
        }

        $certAvailability = $hasCert ? 'Available' : '-';

        // 15. PIC
        $picVal = ($row->pic ?? null) ?: 'HRDC Training Team';

        return [
            'no'                        => $rowCount,                     // 1. NO
            'source'                    => $sourceLabel,                  // 2. Source
            'employee_id'               => $cleanEmpId,                   // 3. Employee ID
            'employee_name'             => ($row->employee_name ?? null) ?: (($row->user_name ?? null) ?: '-'), // 4. Employee Name
            'grade'                     => ($row->grade ?? null) ?: (($row->user_jobgrade ?? null) ?: '-'), // 5. Grade
            'job_position'              => ($row->job_position ?? null) ?: (($row->user_jobposition ?? null) ?: '-'), // 6. Job Position
            'team'                      => ($row->team ?? null) ?: (($row->user_team ?? null) ?: '-'), // 7. Team
            'department'                => ($row->department ?? null) ?: (($row->user_department ?? null) ?: '-'), // 8. Department
            'company'                   => ($row->company ?? null) ?: (($row->user_company ?? null) ?: 'PT Krakatau Posco'), // 9. Company
            'course_title'              => $courseTitle,                  // 10. Course Title
            'playlist'                  => $parentPlaylistTitle,          // 11. Playlist
            'sub_course_title'          => $subCourseTitle,               // 12. Sub Course Title
            'type'                      => $typeVal,                      // 13. Type
            'category'                  => $categoryVal,                  // 14. Category
            'level'                     => ($row->level ?? null) ?: '-',  // 14. Level
            'batch'                     => ($row->batch ?? null) ?: '-',  // 15. Batch
            'main_classification'       => ($row->classification ?? null) ?: (($row->main_classification ?? null) ?: 'Unclassified'), // 16. Main Classification
            'sub_classification'        => ($row->sub_classification ?? null) ?: '-', // 17. Sub Classification
            'delivery_method'           => $delivMethod,                  // 18. Delivery Method
            'sub_delivery_method'       => $subDelivMethod,               // 19. Sub Delivery Method
            'provider'                  => $providerVal,                  // 20. Provider
            'sub_provider'              => $subProviderVal,               // 21. Sub Provider
            'provider_name'             => $providerNameVal,              // 22. Provider Name
            'venue'                     => $venueVal,                     // 23. Venue
            'instructor'                => $instructorVal,                // 24. Instructor
            'course_duration'           => $durationFormatted,            // 25. Course Duration
            'total_recognized_duration' => $recognizedFormatted,          // 26. Total Recognized Duration
            'learning_hours'            => $lhVal,                        // 27. Learning Hours
            'progress'                  => $progressDecimal,              // 28. % Progress
            'progress_status'           => $progressStatus,               // 29. Progress Status
            'start_date'                => $startDateStr,                 // 30. Start Date
            'end_date'                  => $endDateStr,                   // 31. End Date
            'start_time'                => $startTimeStr,                 // 32. Start Time
            'end_time'                  => $endTimeStr,                   // 33. End Time
            'record_month'              => $recordMonthStr,               // 34. Record Month
            'requirement'               => $requirementStr,               // 35. Requirement
            'attendance'                => $attendanceStr,                // 36. Attendance
            'attendance_reason'         => ($row->attendance_reason ?? null) ?: '-', // 37. Attendance Reason
            'bond_status'               => $bondStatusStr,                // 38. Bond Status
            'bond_duration'             => $bondDurationInt,              // 39. Bond Duration
            'bond_end_date'             => $bondEndDateStr,               // 40. Bond End Date
            'bond_amount'               => $bondAmountNumeric,            // 41. Bond Amount
            'score_l1'                  => $scoreL1,                      // 42. Score (L1) - Course
            'score_l2'                  => $scoreL2Val,                   // 43. Score (L2)
            'l2_status'                 => $l2StatusVal,                  // 44. L2 Status
            'score_l3'                  => $scoreL3,                      // 45. Score (L3)
            'l3_status'                 => $l3StatusVal,                  // 46. L3 Status
            'certificate_availability'  => $certAvailability,             // 47. Certificate Availability
            'certificate_institution'   => $certInstitution,              // 48. Certificate Institution
            'certificate_issued_date'   => $certIssuedDate,               // 49. Certificate Issued Date
            'certificate_expired_date'  => $certExpiredDate,              // 50. Certificate Expired Date
            'pic'                       => $picVal,                       // 51. Person in Charge (PIC)
            'total_engagement_time'     => $totalEngagementDuration,      // 52. Total Engagement Time
        ];
    }

    /**
     * Build PhpSpreadsheet instance with explicit cell data formatting:
     * - Employee ID: setCellValueExplicit('C'.$row, (string)$empId, DataType::TYPE_STRING)
     * - Hours & Scores: getNumberFormat()->setFormatCode('0.00')
     * - Pure Integers (NO, Bond Duration): getNumberFormat()->setFormatCode('0')
     * - Currency (Bond Amount): getNumberFormat()->setFormatCode('#,##0')
     * - Dates & Times: formatted as YYYY-MM-DD and HH:MM:SS strings
     */
    public function exportSpreadsheet(array $filters = [], ?string $classification = null): Spreadsheet
    {
        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('All KP OWL Records');

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

        // Pre-query context data
        $contentCertSettings = DB::table('certificate_settings')
            ->whereNotNull('content_id')
            ->get()
            ->keyBy('content_id');
        $offlineCertSettings = DB::table('certificate_settings')
            ->whereNotNull('offline_training_id')
            ->get()
            ->keyBy('offline_training_id');
        $contentUserCerts = DB::table('certificates as c')
            ->leftJoin('certificate_settings as cs', 'cs.id', '=', 'c.certificate_setting_id')
            ->whereNotNull('c.content_user_id')
            ->select([
                'c.content_user_id',
                'c.institution as c_institution',
                'cs.institution as cs_institution',
                'cs.issuer_name',
                'cs.issued_date',
                'cs.expired_date',
                'c.issued_at',
                'c.issued_date as c_issued_date',
                'c.expired_date as c_expired_date',
            ])
            ->get()
            ->keyBy('content_user_id');
        $offlineUserCerts = DB::table('certificates as c')
            ->leftJoin('certificate_settings as cs', 'cs.id', '=', 'c.certificate_setting_id')
            ->whereNotNull('c.offline_training_user_id')
            ->select([
                'c.offline_training_user_id',
                'c.institution as c_institution',
                'cs.institution as cs_institution',
                'cs.issuer_name',
                'cs.issued_date',
                'cs.expired_date',
                'c.issued_at',
                'c.issued_date as c_issued_date',
                'c.expired_date as c_expired_date',
            ])
            ->get()
            ->keyBy('offline_training_user_id');

        $context = [
            'recognized_service'    => app(\App\Services\Learning\LearningRecordRecognizedDurationService::class),
            'content_cert_settings' => $contentCertSettings,
            'offline_cert_settings' => $offlineCertSettings,
            'content_user_certs'    => $contentUserCerts,
            'offline_user_certs'    => $offlineUserCerts,
        ];

        $query = $this->buildQuery($filters, $classification);
        $rowIndex = 1;

        foreach ($query->cursor() as $record) {
            $rowIndex++;
            $mapped = $this->mapRow($record, $rowIndex - 1, $context);
            $values = array_values(array_slice($mapped, 0, 53));

            foreach ($values as $colIdx => $val) {
                $colLetter = Coordinate::stringFromColumnIndex($colIdx + 1);
                $cellCoord = "{$colLetter}{$rowIndex}";

                // Explicit Cell Data Formatting Rules:
                if ($colLetter === 'C') {
                    // Employee ID: Force Explicit String to preserve leading zeros
                    $sheet->setCellValueExplicit($cellCoord, (string) $val, DataType::TYPE_STRING);
                } elseif ($colLetter === 'A') {
                    // NO: Pure Integer
                    $sheet->setCellValueExplicit($cellCoord, (int) $val, DataType::TYPE_NUMERIC);
                } elseif ($colLetter === 'Z' || $colLetter === 'AA' || $colLetter === 'BA') {
                    // Durations: Course Duration (Z), Total Recognized Duration (AA), Total Engagement Time (BA)
                    $sec = ($val !== '-' && $val !== '' && $val !== null) ? DurationFormatter::toSeconds((string) $val) : null;
                    if ($sec !== null && $sec >= 0) {
                        $sheet->setCellValueExplicit($cellCoord, $sec / 86400, DataType::TYPE_NUMERIC);
                    } else {
                        $sheet->setCellValueExplicit($cellCoord, '-', DataType::TYPE_STRING);
                    }
                } elseif ($colLetter === 'AG' || $colLetter === 'AH') {
                    // Times: Start Time (AG), End Time (AH)
                    $sec = ($val !== '-' && $val !== '' && $val !== null) ? DurationFormatter::toSeconds((string) $val) : null;
                    if ($sec !== null && $sec >= 0) {
                        $sheet->setCellValueExplicit($cellCoord, $sec / 86400, DataType::TYPE_NUMERIC);
                    } else {
                        $sheet->setCellValueExplicit($cellCoord, '-', DataType::TYPE_STRING);
                    }
                } elseif ($colLetter === 'AB') {
                    // Learning Hours: Numeric decimal format 0.00" h"
                    if (is_numeric($val)) {
                        $sheet->setCellValueExplicit($cellCoord, (float) $val, DataType::TYPE_NUMERIC);
                    } else {
                        $sheet->setCellValueExplicit($cellCoord, (string) $val, DataType::TYPE_STRING);
                    }
                } elseif ($colLetter === 'AC') {
                    // % Progress: Numeric decimal with 0% format
                    if (is_numeric($val)) {
                        $sheet->setCellValueExplicit($cellCoord, (float) $val, DataType::TYPE_NUMERIC);
                    } else {
                        $sheet->setCellValueExplicit($cellCoord, 0.0, DataType::TYPE_NUMERIC);
                    }
                } elseif ($colLetter === 'AN') {
                    // Bond Duration: Pure Integer
                    if (is_numeric($val) && (int) $val > 0) {
                        $sheet->setCellValueExplicit($cellCoord, (int) $val, DataType::TYPE_NUMERIC);
                    } else {
                        $sheet->setCellValueExplicit($cellCoord, (string) $val, DataType::TYPE_STRING);
                    }
                } elseif ($colLetter === 'AP') {
                    // Bond Amount: Pure Integer Currency
                    if (is_numeric($val) && (float) $val > 0) {
                        $sheet->setCellValueExplicit($cellCoord, (float) $val, DataType::TYPE_NUMERIC);
                    } else {
                        $sheet->setCellValueExplicit($cellCoord, (string) $val, DataType::TYPE_STRING);
                    }
                } elseif ($colLetter === 'AQ' || $colLetter === 'AR' || $colLetter === 'AT') {
                    // Scores: Decimal 0.00
                    if (is_numeric($val)) {
                        $sheet->setCellValueExplicit($cellCoord, (float) $val, DataType::TYPE_NUMERIC);
                    } else {
                        $sheet->setCellValueExplicit($cellCoord, (string) $val, DataType::TYPE_STRING);
                    }
                } else {
                    $sheet->setCellValueExplicit($cellCoord, (string) ($val ?? '-'), DataType::TYPE_STRING);
                }
            }

            // Apply Row-level Number Formats
            $sheet->getStyle("A{$rowIndex}")->getNumberFormat()->setFormatCode('0');
            $sheet->getStyle("Z{$rowIndex}")->getNumberFormat()->setFormatCode('[hh]:mm:ss');
            $sheet->getStyle("AA{$rowIndex}")->getNumberFormat()->setFormatCode('[hh]:mm:ss');
            $sheet->getStyle("AB{$rowIndex}")->getNumberFormat()->setFormatCode('0.00" h"');
            $sheet->getStyle("AC{$rowIndex}")->getNumberFormat()->setFormatCode('0%');
            $sheet->getStyle("AG{$rowIndex}")->getNumberFormat()->setFormatCode('hh:mm:ss');
            $sheet->getStyle("AH{$rowIndex}")->getNumberFormat()->setFormatCode('hh:mm:ss');
            $sheet->getStyle("AN{$rowIndex}")->getNumberFormat()->setFormatCode('0');
            $sheet->getStyle("AP{$rowIndex}")->getNumberFormat()->setFormatCode('#,##0');
            $sheet->getStyle("AQ{$rowIndex}")->getNumberFormat()->setFormatCode('0.00');
            $sheet->getStyle("AR{$rowIndex}")->getNumberFormat()->setFormatCode('0.00');
            $sheet->getStyle("AT{$rowIndex}")->getNumberFormat()->setFormatCode('0.00');
            $sheet->getStyle("BA{$rowIndex}")->getNumberFormat()->setFormatCode('[hh]:mm:ss');
        }

        return $spreadsheet;
    }

    /**
     * Generate XLSX file using streaming ZipStream for large recordsets.
     */
    public function generateXlsxFile(array $filters = [], ?string $classification = null): string
    {
        ini_set('memory_limit', '2048M');
        ini_set('max_execution_time', 600);
        if (function_exists('set_time_limit')) {
            @set_time_limit(600);
        }

        $tempXlsx = tempnam(sys_get_temp_dir(), 'all_record_stream_') . '.xlsx';
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
        $sheetTitle = !empty($classification) ? 'Classification ' . ucwords(str_replace('-', ' ', $classification)) : 'All KP OWL Records';
        $cleanSheetTitle = $this->escapeXml(substr($sheetTitle, 0, 31));

        $zip->addFile(
            'xl/workbook.xml',
            '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            . '<workbook xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main" xmlns:r="http://schemas.openxmlformats.org/officeDocument/2006/relationships">'
            . '<sheets><sheet name="' . $cleanSheetTitle . '" sheetId="1" r:id="rId1"/></sheets>'
            . '</workbook>'
        );

        // 5. xl/styles.xml with precise numFmts:
        // s=0: text centered
        // s=1: header (bold, white, #002B49 fill, centered, wrap)
        // s=2: integer (0)
        // s=3: decimal (0.00)
        // s=4: currency (#,##0)
        // s=5: date (yyyy-mm-dd)
        // s=6: time (hh:mm:ss)
        // s=7: progress (0%)
        // s=8: learning hours (0.00" h")
        $zip->addFile(
            'xl/styles.xml',
            '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            . '<styleSheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main">'
            . '<numFmts count="7">'
            . '<numFmt numFmtId="164" formatCode="0"/>'
            . '<numFmt numFmtId="165" formatCode="0.00"/>'
            . '<numFmt numFmtId="166" formatCode="#,##0"/>'
            . '<numFmt numFmtId="167" formatCode="yyyy-mm-dd"/>'
            . '<numFmt numFmtId="168" formatCode="[hh]:mm:ss"/>'
            . '<numFmt numFmtId="169" formatCode="0%"/>'
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
            . '<cellXfs count="9">'
            . '<xf numFmtId="0" fontId="0" fillId="0" borderId="0" xfId="0" applyAlignment="1"><alignment horizontal="center" vertical="center"/></xf>'
            . '<xf numFmtId="0" fontId="1" fillId="2" borderId="0" xfId="0" applyFont="1" applyFill="1" applyAlignment="1"><alignment horizontal="center" vertical="center" wrapText="1"/></xf>'
            . '<xf numFmtId="164" fontId="0" fillId="0" borderId="0" xfId="0" applyNumberFormat="1" applyAlignment="1"><alignment horizontal="center" vertical="center"/></xf>'
            . '<xf numFmtId="165" fontId="0" fillId="0" borderId="0" xfId="0" applyNumberFormat="1" applyAlignment="1"><alignment horizontal="center" vertical="center"/></xf>'
            . '<xf numFmtId="166" fontId="0" fillId="0" borderId="0" xfId="0" applyNumberFormat="1" applyAlignment="1"><alignment horizontal="center" vertical="center"/></xf>'
            . '<xf numFmtId="167" fontId="0" fillId="0" borderId="0" xfId="0" applyNumberFormat="1" applyAlignment="1"><alignment horizontal="center" vertical="center"/></xf>'
            . '<xf numFmtId="168" fontId="0" fillId="0" borderId="0" xfId="0" applyNumberFormat="1" applyAlignment="1"><alignment horizontal="center" vertical="center"/></xf>'
            . '<xf numFmtId="169" fontId="0" fillId="0" borderId="0" xfId="0" applyNumberFormat="1" applyAlignment="1"><alignment horizontal="center" vertical="center"/></xf>'
            . '<xf numFmtId="171" fontId="0" fillId="0" borderId="0" xfId="0" applyNumberFormat="1" applyAlignment="1"><alignment horizontal="center" vertical="center"/></xf>'
            . '</cellXfs>'
            . '</styleSheet>'
        );

        // 6. Temporary file stream for sheet1.xml
        $sheetFile = tempnam(sys_get_temp_dir(), 'all_xlsx_sheet_');
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

        // Header row
        $headers = self::headings();
        fwrite($sfh, '<row r="1" ht="28" customHeight="1">' . "\n");
        foreach ($headers as $i => $h) {
            $coord = Coordinate::stringFromColumnIndex($i + 1) . '1';
            $val = $this->escapeXml($h);
            fwrite($sfh, "<c r=\"{$coord}\" t=\"inlineStr\" s=\"1\"><is><t>{$val}</t></is></c>\n");
        }
        fwrite($sfh, '</row>' . "\n");

        // Context data
        $contentCertSettings = DB::table('certificate_settings')
            ->whereNotNull('content_id')
            ->get()
            ->keyBy('content_id');
        $offlineCertSettings = DB::table('certificate_settings')
            ->whereNotNull('offline_training_id')
            ->get()
            ->keyBy('offline_training_id');
        $contentUserCerts = DB::table('certificates as c')
            ->leftJoin('certificate_settings as cs', 'cs.id', '=', 'c.certificate_setting_id')
            ->whereNotNull('c.content_user_id')
            ->select([
                'c.content_user_id',
                'c.institution as c_institution',
                'cs.institution as cs_institution',
                'cs.issuer_name',
                'cs.issued_date',
                'cs.expired_date',
                'c.issued_at',
                'c.issued_date as c_issued_date',
                'c.expired_date as c_expired_date',
            ])
            ->get()
            ->keyBy('content_user_id');
        $offlineUserCerts = DB::table('certificates as c')
            ->leftJoin('certificate_settings as cs', 'cs.id', '=', 'c.certificate_setting_id')
            ->whereNotNull('c.offline_training_user_id')
            ->select([
                'c.offline_training_user_id',
                'c.institution as c_institution',
                'cs.institution as cs_institution',
                'cs.issuer_name',
                'cs.issued_date',
                'cs.expired_date',
                'c.issued_at',
                'c.issued_date as c_issued_date',
                'c.expired_date as c_expired_date',
            ])
            ->get()
            ->keyBy('offline_training_user_id');

        $context = [
            'recognized_service'    => app(\App\Services\Learning\LearningRecordRecognizedDurationService::class),
            'content_cert_settings' => $contentCertSettings,
            'offline_cert_settings' => $offlineCertSettings,
            'content_user_certs'    => $contentUserCerts,
            'offline_user_certs'    => $offlineUserCerts,
        ];

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
        $context['tpd_cache'] = $tpdCache;

        $query = $this->buildQuery($filters, $classification);
        $rowCount = 0;
        $excelRow = 1;
        $buffer = '';
        $batchSize = 500;

        $pdo = DB::connection()->getPdo();
        $wasBuffered = $pdo->getAttribute(\PDO::MYSQL_ATTR_USE_BUFFERED_QUERY);
        $pdo->setAttribute(\PDO::MYSQL_ATTR_USE_BUFFERED_QUERY, false);

        try {
            foreach ($query->cursor() as $record) {
                $rowCount++;
                $excelRow++;
                $r = $excelRow;

                $mapped = $this->mapRow($record, $rowCount, $context);
                $values = array_values(array_slice($mapped, 0, 53));

                $xmlRow = "<row r=\"{$r}\">";
                foreach ($values as $colIdx => $val) {
                    $colLetter = Coordinate::stringFromColumnIndex($colIdx + 1);
                    $coord = "{$colLetter}{$r}";

                    if ($val === null || $val === '' || $val === '-') {
                        $xmlRow .= "<c r=\"{$coord}\" t=\"inlineStr\" s=\"0\"><is><t>-</t></is></c>";
                    } elseif ($colLetter === 'C') {
                        // Employee ID: Explicit String (s=0, t=inlineStr)
                        $escaped = $this->escapeXml((string) $val);
                        $xmlRow .= "<c r=\"{$coord}\" t=\"inlineStr\" s=\"0\"><is><t>{$escaped}</t></is></c>";
                    } elseif ($colLetter === 'A' || $colLetter === 'AN') {
                        // NO and Bond Duration: Pure Integer (s=2, format 0)
                        $intVal = (int) $val;
                        $xmlRow .= "<c r=\"{$coord}\" s=\"2\"><v>{$intVal}</v></c>";
                    } elseif ($colLetter === 'AP') {
                        // Bond Amount: Pure Integer Currency (s=4, format #,##0)
                        $currVal = (float) $val;
                        $xmlRow .= "<c r=\"{$coord}\" s=\"4\"><v>{$currVal}</v></c>";
                    } elseif ($colLetter === 'AB') {
                        // Learning Hours: Decimal with 0.00" h" format (s=8) so Excel can SUM natively!
                        $decVal = number_format((float) $val, 2, '.', '');
                        $xmlRow .= "<c r=\"{$coord}\" s=\"8\"><v>{$decVal}</v></c>";
                    } elseif ($colLetter === 'AC') {
                        // % Progress: Numeric decimal with 0% format (s=7)
                        $pctVal = (float) $val;
                        $xmlRow .= "<c r=\"{$coord}\" s=\"7\"><v>{$pctVal}</v></c>";
                    } elseif ($colLetter === 'AQ' || $colLetter === 'AR' || $colLetter === 'AT') {
                        // Scores: Decimal 0.00 (s=3)
                        $decVal = number_format((float) $val, 2, '.', '');
                        $xmlRow .= "<c r=\"{$coord}\" s=\"3\"><v>{$decVal}</v></c>";
                    } elseif ($colLetter === 'AE' || $colLetter === 'AF' || $colLetter === 'AO' || $colLetter === 'AX' || $colLetter === 'AY') {
                        // Dates: YYYY-MM-DD (s=5)
                        $escaped = $this->escapeXml((string) $val);
                        $xmlRow .= "<c r=\"{$coord}\" t=\"inlineStr\" s=\"5\"><is><t>{$escaped}</t></is></c>";
                    } elseif ($colLetter === 'Z' || $colLetter === 'AA' || $colLetter === 'AG' || $colLetter === 'AH' || $colLetter === 'BA') {
                        // Times & Durations: [hh]:mm:ss (s=6, numeric fraction so Excel can SUM)
                        if (is_numeric($val)) {
                            $numStr = sprintf('%.10F', (float) $val);
                            $numStr = rtrim(rtrim($numStr, '0'), '.');
                            if ($numStr === '') $numStr = '0';
                            $xmlRow .= "<c r=\"{$coord}\" s=\"6\"><v>{$numStr}</v></c>";
                        } else {
                            $frac = $this->timeToExcelFraction((string) $val);
                            if ($frac !== null) {
                                $numStr = sprintf('%.10F', $frac);
                                $numStr = rtrim(rtrim($numStr, '0'), '.');
                                if ($numStr === '') $numStr = '0';
                                $xmlRow .= "<c r=\"{$coord}\" s=\"6\"><v>{$numStr}</v></c>";
                            } else {
                                $escaped = $this->escapeXml((string) $val);
                                $xmlRow .= "<c r=\"{$coord}\" t=\"inlineStr\" s=\"6\"><is><t>{$escaped}</t></is></c>";
                            }
                        }
                    } else {
                        $escaped = $this->escapeXml((string) $val);
                        $xmlRow .= "<c r=\"{$coord}\" t=\"inlineStr\" s=\"0\"><is><t>{$escaped}</t></is></c>";
                    }
                }
                $xmlRow .= "</row>\n";
                $buffer .= $xmlRow;

                if ($rowCount % $batchSize === 0) {
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
     * Download Excel file for All Records.
     */
    public function download(array $filters = [], ?string $fileName = null): BinaryFileResponse
    {
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
     * Download Excel file for All Records Classification.
     */
    public function downloadClassification(?string $classification = null, array $filters = [], ?string $fileName = null): BinaryFileResponse
    {
        $fileName = $fileName ?: ClassificationAllRecordExport::generateFileName();
        $tempPath = $this->generateXlsxFile($filters, $classification);

        return response()->download(
            $tempPath,
            $fileName,
            [
                'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            ]
        )->deleteFileAfterSend(true);
    }

    /**
     * Helper to escape special XML characters.
     */
    protected function timeToExcelFraction(string $timeStr): ?float
    {
        $timeStr = trim($timeStr);
        if ($timeStr === '' || $timeStr === '-') return null;
        $parts = explode(':', $timeStr);
        if (count($parts) < 2) return null;
        $h = (int) $parts[0];
        $m = (int) $parts[1];
        $s = isset($parts[2]) ? (int) $parts[2] : 0;
        return ($h * 3600 + $m * 60 + $s) / 86400.0;
    }

    protected function escapeXml($val): string
    {
        return htmlspecialchars((string) $val, ENT_XML1 | ENT_QUOTES, 'UTF-8');
    }
}
