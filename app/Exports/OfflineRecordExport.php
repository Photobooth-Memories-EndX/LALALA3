<?php

namespace App\Exports;

use App\Services\Record\RecordOfflineQueryService;
use Carbon\Carbon;
use Illuminate\Contracts\Support\Responsable;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use ZipStream\Option\Archive as ArchiveOptions;
use ZipStream\ZipStream;

class OfflineRecordExport implements Responsable, \Maatwebsite\Excel\Concerns\WithHeadings, \Maatwebsite\Excel\Concerns\WithMapping
{
    protected array $filters = [];
    protected string $fileName;
    protected int $rowCount = 0;

    public function __construct($filters = [], ?string $fileName = null)
    {
        $this->filters = $filters instanceof Request ? $filters->all() : (array) $filters;
        $this->fileName = $fileName ?? self::generateFileName();
    }

    public static function generateFileName(): string
    {
        $dt = Carbon::now();
        $timestamp = $dt->format('Y-m-d \a\t H-i-s');

        return "EmployeeNonOWLrecord_{$timestamp}.xlsx";
    }

    public static function formatHours($val): string
    {
        if ($val === null || $val === '' || $val === '-') {
            return '-';
        }
        $hours = (float) $val;
        return ($hours == (int) $hours)
            ? (int) $hours . ' h'
            : rtrim(rtrim(number_format($hours, 2, '.', ''), '0'), '.') . ' h';
    }

    public static function formatDate($date): string
    {
        if (empty($date) || $date === '-' || $date === '0000-00-00') {
            return '';
        }
        $ts = strtotime((string) $date);
        if (!$ts || $ts <= 0) {
            return '';
        }
        return date('d/m/Y', $ts);
    }

    public function getFileName(): string
    {
        return $this->fileName;
    }

    public static function headers(): array
    {
        return [
            'No',
            'Employee ID',
            'Employee Name',
            'Grade',
            'Job Position',
            'Team',
            'Department',
            'Company',
            'Course Title',
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
            'Total Engagement Time (LH)',
        ];
    }

    public function headings(): array
    {
        return self::headers();
    }

    public function map($row): array
    {
        $this->rowCount++;

        // 1. Delivery Method & Sub Delivery Method normalization based on FIXS TEMPLATE PTKP.xlsx
        $rawDeliv = strtolower(trim((string) ($row->delivery_method ?? '')));
        $rawSubDeliv = strtolower(trim((string) ($row->sub_delivery_method ?? '')));

        if (in_array($rawDeliv, ['online', 'e-learning', 'elearning', 'webinar'], true)) {
            $delivMethod = 'Online';
        } elseif (in_array($rawDeliv, ['blended', 'hybrid'], true)) {
            $delivMethod = 'Blended';
        } else {
            $delivMethod = 'Offline';
        }

        if (str_contains($rawSubDeliv, 'off-site') || str_contains($rawSubDeliv, 'offsite') || str_contains($rawSubDeliv, 'off site')) {
            $subDelivMethod = 'Off-Site';
            $delivMethod = 'Offline';
        } elseif (str_contains($rawSubDeliv, 'webinar')) {
            $subDelivMethod = 'Webinar';
            $delivMethod = 'Online';
        } elseif (str_contains($rawSubDeliv, 'e-learning') || str_contains($rawSubDeliv, 'elearning')) {
            $subDelivMethod = 'E-learning';
            $delivMethod = 'Online';
        } elseif (str_contains($rawSubDeliv, 'hybrid') || str_contains($rawSubDeliv, 'blended')) {
            $subDelivMethod = 'Hybrid';
            $delivMethod = 'Blended';
        } elseif (str_contains($rawSubDeliv, 'on-site') || str_contains($rawSubDeliv, 'onsite') || str_contains($rawSubDeliv, 'on site')) {
            $subDelivMethod = 'On-Site';
            if ($delivMethod !== 'Blended' && $delivMethod !== 'Online') {
                $delivMethod = 'Offline';
            }
        } else {
            if ($delivMethod === 'Online') {
                $subDelivMethod = 'E-learning';
            } elseif ($delivMethod === 'Blended') {
                $subDelivMethod = 'Hybrid';
            } else {
                $subDelivMethod = 'On-Site';
            }
        }

        // 2. Learning Hours (raw numeric float for SUM calculations)
        $rawLh = $row->learning_hours ?? ($row->course_learning_hours ?? null);
        $lHoursNumeric = ($rawLh !== null && $rawLh !== '' && is_numeric($rawLh))
            ? (float) $rawLh
            : null;

        // 3. Progress & Status
        $progressNum = (float) ($row->progress ?? 0);
        $progressDecimal = round($progressNum / 100, 4);
        $progressStatus = ($progressNum >= 100) ? 'Finished' : (($progressNum > 0) ? 'Ongoing' : 'Not Started');

        // 4. Date & Time
        $startDateStr = self::formatDate($row->start_date ?? null);
        $endDateStr = self::formatDate($row->end_date ?: ($row->finish_date ?? null));

        $startTimeStr = !empty($row->start_time) ? date('H:i:s', strtotime($row->start_time)) : '';
        $endTimeStr = !empty($row->end_time) ? date('H:i:s', strtotime($row->end_time)) : '';

        $recordMonthDate = $row->start_date ?: ($row->end_date ?: ($row->created_at ?? null));
        $recordMonthStr = !empty($recordMonthDate) ? date('F', strtotime($recordMonthDate)) : '-';

        // 5. Attendance: strictly Yes or No
        $rawAtt = strtolower(trim((string) ($row->attendance ?? '')));
        if ($rawAtt !== '') {
            $attendanceStr = in_array($rawAtt, ['1', 'yes', 'present', 'true', 'hadir', 'completed', 'finished'], true) ? 'Yes' : 'No';
        } else {
            $attendanceStr = ($progressNum >= 100 || !empty($row->finish_date)) ? 'Yes' : 'No';
        }

        // 6. Bond Amount, Duration & End Date
        $idr = $row->bond_amount_idr ?? ($row->bond_amount ?? null);
        $hasBond = ($idr !== null && is_numeric($idr) && (float) $idr > 0);

        $bondDurationVal = '';
        if (!empty($row->bond_duration)) {
            $parsedDur = (int) preg_replace('/\D/', '', (string) $row->bond_duration);
            if ($parsedDur > 0) {
                $bondDurationVal = $parsedDur;
            }
        }
        $bondEndDateStr = $hasBond && !empty($row->bond_end_date) ? self::formatDate($row->bond_end_date) : '';

        // 7. Scores (Float 0.00)
        $scoreL1 = (isset($row->l1_program) && is_numeric($row->l1_program)) ? (float) $row->l1_program : null;

        $rawScoreL2 = $row->assessment_score ?? ($row->l2_numerical ?? null);
        $scoreL2 = ($rawScoreL2 !== null && is_numeric($rawScoreL2)) ? (float) $rawScoreL2 : null;

        $scoreL3 = (isset($row->l3_numerical) && is_numeric($row->l3_numerical)) ? (float) $row->l3_numerical : null;

        // 8. L2 Status: strictly PASSED, FAILED, or empty
        $l2Status = '';
        $rawL2Verbal = strtolower(trim((string) ($row->l2_verbal ?? '')));
        if ($rawL2Verbal !== '' && $rawL2Verbal !== '-') {
            if (str_contains($rawL2Verbal, 'pass') || str_contains($rawL2Verbal, 'good') || str_contains($rawL2Verbal, 'excellent') || str_contains($rawL2Verbal, 'satisfactory')) {
                $l2Status = 'PASSED';
            } elseif (str_contains($rawL2Verbal, 'fail')) {
                $l2Status = 'FAILED';
            }
        }
        if ($l2Status === '' && $scoreL2 !== null) {
            $l2Status = ($scoreL2 >= 70) ? 'PASSED' : 'FAILED';
        }

        // 9. L3 Status: free text string
        $l3Status = !empty($row->l3_verbal) && $row->l3_verbal !== '-' ? trim((string) $row->l3_verbal) : '';

        // 10. Total Engagement Time: from duration for attendees, 0 for absent
        $courseDurSec = !empty($row->duration_seconds) && (int) $row->duration_seconds > 0
            ? (int) $row->duration_seconds
            : (!empty($row->learning_hours) && (float) $row->learning_hours > 0 ? (int) round((float) $row->learning_hours * 3600) : 0);

        $totalEngagementFormatted = '00:00:00';
        $totalEngagementLh = 0.00;
        if ($attendanceStr === 'Yes') {
            $totSec = $courseDurSec ?: (!empty($row->learning_hours) && (float) $row->learning_hours > 0 ? (int) round((float) $row->learning_hours * 3600) : 0);
            if ($totSec > 0) {
                $hours = floor($totSec / 3600);
                $mins = floor(($totSec % 3600) / 60);
                $seconds = $totSec % 60;
                $totalEngagementFormatted = sprintf('%02d:%02d:%02d', $hours, $mins, $seconds);
                $totalEngagementLh = round($totSec / 3600, 2);
            }
        }

        // 11. Employee ID & Certificate
        $rawEmp = $row->employee_id ?? ($row->id_emp ?? '-');
        $cleanEmpId = ($rawEmp !== '-') ? preg_replace('/\s*[\/\-]\s*NIP.*$/i', '', trim((string) $rawEmp)) : '-';
        $hasCert = (!empty($row->certificate) && $row->certificate !== '-' && $row->certificate !== 'No' && $row->certificate !== '0');

        $certInst = '';
        $rawCertIssued = $row->certificate_issued_date ?? null;
        $rawCertExpired = $row->certificate_expired_date ?? null;

        if (property_exists($row, 'cert_institution') || property_exists($row, 'cs_ot_institution')) {
            $hasDbCert = !empty($row->cert_id);
            $hasCsSetting = !empty($row->cs_ot_id);

            if ($hasDbCert || $hasCsSetting || !empty($rawCertIssued)) {
                $hasCert = true;
            }

            $certInst = $row->cert_institution
                ?: ($row->cs_cert_institution
                    ?: ($row->cs_ot_institution
                        ?: ($row->cs_cert_issuer_name
                            ?: ($row->cs_ot_issuer_name ?: ($hasCert ? $row->certificate : '')))));

            if (empty($rawCertIssued)) {
                $rawCertIssued = $row->cert_issued_date
                    ?: ($row->cs_cert_issued_date
                        ?: ($row->cs_ot_issued_date
                            ?: ($row->cert_issued_at ?? null)));
            }
            if (empty($rawCertExpired)) {
                $rawCertExpired = $row->cert_expired_date
                    ?: ($row->cs_cert_expired_date
                        ?: ($row->cs_ot_expired_date ?? null));
            }
        } else {
            $dbCert = null;
            if (!empty($row->id)) {
                $dbCert = DB::table('certificates as c')
                    ->leftJoin('certificate_settings as cs', 'cs.id', '=', 'c.certificate_setting_id')
                    ->where('c.offline_training_user_id', $row->id)
                    ->select([
                        'cs.issuer_name',
                        'cs.institution as cs_institution',
                        'cs.issued_date as cs_issued_date',
                        'cs.expired_date as cs_expired_date',
                        'c.institution as c_institution',
                        'c.issued_at',
                        'c.issued_date',
                        'c.expired_date',
                    ])
                    ->first();
            }
            $csSetting = null;
            if (!empty($row->offline_training_id)) {
                $csSetting = DB::table('certificate_settings')->where('offline_training_id', $row->offline_training_id)->first();
            }

            if ($dbCert || $csSetting || !empty($rawCertIssued)) {
                $hasCert = true;
            }

            $certInst = $dbCert?->c_institution
                ?: ($dbCert?->cs_institution
                    ?: ($csSetting?->institution
                        ?: ($dbCert?->issuer_name
                            ?: ($csSetting?->issuer_name ?: ($hasCert ? $row->certificate : '')))));

            if (empty($rawCertIssued)) {
                $rawCertIssued = $dbCert?->issued_date
                    ?: ($csSetting?->issued_date
                        ?: ($dbCert?->issued_at ?? null));
            }
            if (empty($rawCertExpired)) {
                $rawCertExpired = $dbCert?->expired_date
                    ?: ($csSetting?->expired_date
                        ?: ($dbCert?->expired_at ?? null));
            }
        }

        $certIssued = self::formatDate($rawCertIssued);
        $certExpired = self::formatDate($rawCertExpired);

        return [
            $this->rowCount,                                                                                    // 1. No
            $cleanEmpId,                                                                                        // 2. Employee ID
            $row->participant_name ?: ($row->user_name ?? ($row->name ?? '-')),                                 // 3. Employee Name
            $row->grade ?: '-',                                                                                 // 4. Grade
            $row->job_position ?: '-',                                                                          // 5. Job Position
            $row->team ?: '-',                                                                                  // 6. Team
            $row->department ?: '-',                                                                            // 7. Department
            $row->company ?: '-',                                                                               // 8. Company
            $row->course_name ?: ($row->title ?: '-'),                                                          // 9. Course Title
            $row->sub_course_name ?: '-',                                                                       // 10. Sub Course Title
            $row->type ?: '-',                                                                                  // 11. Type
            $row->category ?: '-',                                                                              // 12. Category
            $row->level ?: '-',                                                                                 // 13. Level
            $row->batch ?: '-',                                                                                 // 14. Batch
            $row->main_classification ?: 'Unclassified',                                                        // 15. Main Classification
            $row->sub_classification ?: '-',                                                                    // 16. Sub Classification
            $delivMethod,                                                                                       // 17. Delivery Method
            $subDelivMethod,                                                                                    // 18. Sub Delivery Method
            $row->provider ?: '-',                                                                              // 19. Provider
            $row->sub_provider ?: '-',                                                                          // 20. Sub Provider
            $row->provider_name ?: ($row->provider ?: '-'),                                                     // 21. Provider Name
            $row->venue ?: '-',                                                                                 // 22. Venue
            $row->instructor ?: '-',                                                                            // 23. Instructor
            '-',                                                                                                // 24. Course Duration
            '-',                                                                                                // 25. Total Recognized Duration
            $lHoursNumeric !== null ? $lHoursNumeric : '-',                                                     // 26. Learning Hours
            $progressDecimal,                                                                                   // 27. % Progress
            $progressStatus,                                                                                    // 28. Progress Status
            $startDateStr,                                                                                      // 29. Start Date
            $endDateStr,                                                                                        // 30. End Date
            $startTimeStr,                                                                                      // 31. Start Time
            $endTimeStr,                                                                                        // 32. End Time
            $recordMonthStr,                                                                                    // 33. Record Month
            $row->requirement ?: 'Mandatory',                                                                   // 34. Requirement
            $attendanceStr,                                                                                     // 35. Attendance
            $row->attendance_reason ?: '',                                                                      // 36. Attendance Reason
            $hasBond ? 'Yes' : ($row->bond_status ?: 'No'),                                                     // 37. Bond Status
            $bondDurationVal !== '' ? $bondDurationVal . ' Months' : '',                                        // 38. Bond Duration
            $bondEndDateStr,                                                                                    // 39. Bond End Date
            $hasBond ? (float) $idr : '',                                                                       // 40. Bond Amount
            $scoreL1 !== null ? $scoreL1 : '',                                                                  // 41. Score (L1) - Course
            $scoreL2 !== null ? $scoreL2 : '',                                                                  // 42. Score (L2)
            $l2Status,                                                                                          // 43. L2 Status
            $scoreL3 !== null ? $scoreL3 : '',                                                                  // 44. Score (L3)
            $l3Status,                                                                                          // 45. L3 Status
            $hasCert ? 'Available' : '',                                                                        // 46. Certificate Availability
            $certInst,                                                                                          // 47. Certificate Institution
            $certIssued,                                                                                        // 48. Certificate Issued Date
            $certExpired,                                                                                       // 49. Certificate Expired Date
            $row->pic ?: '',                                                                                    // 50. Person in Charge (PIC)
            $totalEngagementFormatted,                                                                          // 51. Total Engagement Time
            $totalEngagementLh,                                                                                 // 52. Total Engagement Time (LH)
        ];
    }

    public static function columnWidths(): array
    {
        return [
            'A'  => 6,   // No
            'B'  => 16,  // Employee ID
            'C'  => 30,  // Employee Name
            'D'  => 12,  // Grade
            'E'  => 25,  // Job Position
            'F'  => 20,  // Team
            'G'  => 25,  // Department
            'H'  => 25,  // Company
            'I'  => 35,  // Course Title
            'J'  => 25,  // Sub Course Title
            'K'  => 15,  // Type
            'L'  => 18,  // Category
            'M'  => 12,  // Level
            'N'  => 12,  // Batch
            'O'  => 25,  // Main Classification
            'P'  => 25,  // Sub Classification
            'Q'  => 18,  // Delivery Method
            'R'  => 20,  // Sub Delivery Method
            'S'  => 16,  // Provider
            'T'  => 18,  // Sub Provider
            'U'  => 25,  // Provider Name
            'V'  => 20,  // Venue
            'W'  => 22,  // Instructor
            'X'  => 16,  // Course Duration
            'Y'  => 22,  // Total Recognized Duration
            'Z'  => 16,  // Learning Hours
            'AA' => 14,  // % Progress
            'AB' => 16,  // Progress Status
            'AC' => 14,  // Start Date
            'AD' => 14,  // End Date
            'AE' => 14,  // Start Time
            'AF' => 14,  // End Time
            'AG' => 16,  // Record Month
            'AH' => 15,  // Requirement
            'AI' => 14,  // Attendance
            'AJ' => 25,  // Attendance Reason
            'AK' => 14,  // Bond Status
            'AL' => 16,  // Bond Duration
            'AM' => 16,  // Bond End Date
            'AN' => 18,  // Bond Amount
            'AO' => 20,  // Score (L1) - Course
            'AP' => 15,  // Score (L2)
            'AQ' => 15,  // L2 Status
            'AR' => 15,  // Score (L3)
            'AS' => 15,  // L3 Status
            'AT' => 22,  // Certificate Availability
            'AU' => 25,  // Certificate Institution
            'AV' => 20,  // Certificate Issued Date
            'AW' => 20,  // Certificate Expired Date
            'AX' => 25,  // Person in Charge (PIC)
            'AY' => 22,  // Total Engagement Time
            'AZ' => 24,  // Total Engagement Time (LH)
        ];
    }

    public function query()
    {
        return RecordOfflineQueryService::buildGlobalQuery($this->filters)
            ->leftJoin('certificates as cert', 'cert.offline_training_user_id', '=', 'otu.id')
            ->leftJoin('certificate_settings as cs_cert', 'cs_cert.id', '=', 'cert.certificate_setting_id')
            ->leftJoin('certificate_settings as cs_ot', 'cs_ot.offline_training_id', '=', 'ot.id')
            ->addSelect([
                'cert.id as cert_id',
                'cert.institution as cert_institution',
                'cs_cert.institution as cs_cert_institution',
                'cs_ot.institution as cs_ot_institution',
                'cs_cert.issuer_name as cs_cert_issuer_name',
                'cs_ot.issuer_name as cs_ot_issuer_name',
                'cert.issued_date as cert_issued_date',
                'cs_cert.issued_date as cs_cert_issued_date',
                'cs_ot.issued_date as cs_ot_issued_date',
                'cert.issued_at as cert_issued_at',
                'cert.expired_date as cert_expired_date',
                'cs_cert.expired_date as cs_cert_expired_date',
                'cs_ot.expired_date as cs_ot_expired_date',
                'cs_ot.id as cs_ot_id',
            ])
            ->orderBy('ot.start_date', 'desc')
            ->orderBy('ot.id', 'desc')
            ->orderBy('otu.id', 'asc')
            ->when(app()->environment('testing') && !isset($this->filters['limit']), fn($q) => $q->limit(500));
    }

    public function download(?string $fileName = null): BinaryFileResponse
    {
        $fileName = $fileName ?? $this->fileName;
        $tempPath = $this->generateXlsxFile();

        return response()->download(
            $tempPath,
            $fileName,
            [
                'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            ]
        )->deleteFileAfterSend(true);
    }

    public function toResponse($request)
    {
        return $this->download();
    }

    public function generateXlsxFile(): string
    {
        ini_set('memory_limit', '2048M');
        ini_set('max_execution_time', 0);
        if (function_exists('set_time_limit')) {
            @set_time_limit(0);
        }

        $tempXlsx = tempnam(sys_get_temp_dir(), 'offline_record_') . '.xlsx';
        $opt = new ArchiveOptions();
        $opt->setSendHttpHeaders(false);
        $opt->setDeflateLevel(1);
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
        $zip->addFile(
            'xl/workbook.xml',
            '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            . '<workbook xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main" xmlns:r="http://schemas.openxmlformats.org/officeDocument/2006/relationships">'
            . '<sheets><sheet name="Non KP OWL Import" sheetId="1" r:id="rId1"/></sheets>'
            . '</workbook>'
        );

        // 5. xl/styles.xml - Dark Blue Header (Row 1) & Default Excel Normal Theme (Row 2+)
        $zip->addFile(
            'xl/styles.xml',
            '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            . '<styleSheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main">'
            . '<numFmts count="6">'
            . '<numFmt numFmtId="164" formatCode="dd/mm/yyyy"/>'
            . '<numFmt numFmtId="165" formatCode="hh:mm:ss"/>'
            . '<numFmt numFmtId="166" formatCode="[h]:mm:ss"/>'
            . '<numFmt numFmtId="167" formatCode="0.00"/>'
            . '<numFmt numFmtId="168" formatCode="0&quot; MONTH&quot;"/>'
            . '<numFmt numFmtId="169" formatCode="&quot;Rp&quot;#,##0"/>'
            . '</numFmts>'
            . '<fonts count="2">'
            . '<font><sz val="11"/><name val="Calibri"/><family val="2"/></font>'
            . '<font><b/><sz val="11"/><color rgb="FFFFFFFF"/><name val="Calibri"/><family val="2"/></font>'
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
            . '<cellXfs count="11">'
            . '<xf numFmtId="0" fontId="0" fillId="0" borderId="0" xfId="0"/>'
            . '<xf numFmtId="1" fontId="0" fillId="0" borderId="0" xfId="0" applyNumberFormat="1"/>'
            . '<xf numFmtId="9" fontId="0" fillId="0" borderId="0" xfId="0" applyNumberFormat="1"/>'
            . '<xf numFmtId="164" fontId="0" fillId="0" borderId="0" xfId="0" applyNumberFormat="1"/>'
            . '<xf numFmtId="165" fontId="0" fillId="0" borderId="0" xfId="0" applyNumberFormat="1"/>'
            . '<xf numFmtId="166" fontId="0" fillId="0" borderId="0" xfId="0" applyNumberFormat="1"/>'
            . '<xf numFmtId="167" fontId="0" fillId="0" borderId="0" xfId="0" applyNumberFormat="1"/>'
            . '<xf numFmtId="168" fontId="0" fillId="0" borderId="0" xfId="0" applyNumberFormat="1"/>'
            . '<xf numFmtId="2" fontId="0" fillId="0" borderId="0" xfId="0" applyNumberFormat="1"/>'
            . '<xf numFmtId="169" fontId="0" fillId="0" borderId="0" xfId="0" applyNumberFormat="1"/>'
            . '<xf numFmtId="0" fontId="1" fillId="2" borderId="0" xfId="0" applyFont="1" applyFill="1" applyAlignment="1"><alignment horizontal="center" vertical="center" wrapText="1"/></xf>'
            . '</cellXfs>'
            . '<cellStyles count="1">'
            . '<cellStyle name="Normal" xfId="0" builtinId="0"/>'
            . '</cellStyles>'
            . '</styleSheet>'
        );

        // 6. Temporary stream for sheet1.xml
        $sheetFile = tempnam(sys_get_temp_dir(), 'xlsx_offline_sheet_');
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

        // Header row (Row 1) - Dark Blue (#002B49) background, White Bold text, Center aligned
        $headers = self::headings();
        fwrite($sfh, '<row r="1" ht="26" customHeight="1">' . "\n");
        foreach ($headers as $i => $h) {
            $coord = Coordinate::stringFromColumnIndex($i + 1) . '1';
            $val = $this->escapeXml($h);
            fwrite($sfh, "<c r=\"{$coord}\" t=\"inlineStr\" s=\"10\"><is><t>{$val}</t></is></c>\n");
        }
        fwrite($sfh, '</row>' . "\n");

        $query = $this->query();

        // Ensure certificate joins are present to avoid N+1 queries and connection conflict during unbuffered streaming
        $sql = $query->toSql();
        if (!str_contains($sql, 'as `cert`') && !str_contains($sql, 'as cert')) {
            $query->leftJoin('certificates as cert', 'cert.offline_training_user_id', '=', 'otu.id')
                ->leftJoin('certificate_settings as cs_cert', 'cs_cert.id', '=', 'cert.certificate_setting_id')
                ->leftJoin('certificate_settings as cs_ot', 'cs_ot.offline_training_id', '=', 'ot.id')
                ->addSelect([
                    'cert.id as cert_id',
                    'cert.institution as cert_institution',
                    'cs_cert.institution as cs_cert_institution',
                    'cs_ot.institution as cs_ot_institution',
                    'cs_cert.issuer_name as cs_cert_issuer_name',
                    'cs_ot.issuer_name as cs_ot_issuer_name',
                    'cert.issued_date as cert_issued_date',
                    'cs_cert.issued_date as cs_cert_issued_date',
                    'cs_ot.issued_date as cs_ot_issued_date',
                    'cert.issued_at as cert_issued_at',
                    'cert.expired_date as cert_expired_date',
                    'cs_cert.expired_date as cs_cert_expired_date',
                    'cs_ot.expired_date as cs_ot_expired_date',
                    'cs_ot.id as cs_ot_id',
                ]);
        }

        $rowCount = 0;
        $buffer = '';
        $batchSize = 1000;

        $pdo = DB::connection()->getPdo();
        $wasBuffered = $pdo->getAttribute(\PDO::MYSQL_ATTR_USE_BUFFERED_QUERY);
        $pdo->setAttribute(\PDO::MYSQL_ATTR_USE_BUFFERED_QUERY, false);

        try {
            foreach ($query->cursor() as $row) {
                $rowCount++;
                $r = $rowCount + 1;

                // 1. Delivery Method & Sub Delivery Method normalization based on FIXS TEMPLATE PTKP.xlsx
                $rawDeliv = strtolower(trim((string) ($row->delivery_method ?? '')));
                $rawSubDeliv = strtolower(trim((string) ($row->sub_delivery_method ?? '')));

                if (in_array($rawDeliv, ['online', 'e-learning', 'elearning', 'webinar'], true)) {
                    $delivMethod = 'Online';
                } elseif (in_array($rawDeliv, ['blended', 'hybrid'], true)) {
                    $delivMethod = 'Blended';
                } else {
                    $delivMethod = 'Offline';
                }

                if (str_contains($rawSubDeliv, 'off-site') || str_contains($rawSubDeliv, 'offsite') || str_contains($rawSubDeliv, 'off site')) {
                    $subDelivMethod = 'Off-Site';
                    $delivMethod = 'Offline';
                } elseif (str_contains($rawSubDeliv, 'webinar')) {
                    $subDelivMethod = 'Webinar';
                    $delivMethod = 'Online';
                } elseif (str_contains($rawSubDeliv, 'e-learning') || str_contains($rawSubDeliv, 'elearning')) {
                    $subDelivMethod = 'E-learning';
                    $delivMethod = 'Online';
                } elseif (str_contains($rawSubDeliv, 'hybrid') || str_contains($rawSubDeliv, 'blended')) {
                    $subDelivMethod = 'Hybrid';
                    $delivMethod = 'Blended';
                } elseif (str_contains($rawSubDeliv, 'on-site') || str_contains($rawSubDeliv, 'onsite') || str_contains($rawSubDeliv, 'on site')) {
                    $subDelivMethod = 'On-Site';
                    if ($delivMethod !== 'Blended' && $delivMethod !== 'Online') {
                        $delivMethod = 'Offline';
                    }
                } else {
                    if ($delivMethod === 'Online') {
                        $subDelivMethod = 'E-learning';
                    } elseif ($delivMethod === 'Blended') {
                        $subDelivMethod = 'Hybrid';
                    } else {
                        $subDelivMethod = 'On-Site';
                    }
                }

                // 2. Learning Hours: float numeric (0.00)
                $rawLh = $row->learning_hours ?? ($row->course_learning_hours ?? null);
                $lHoursNumeric = ($rawLh !== null && $rawLh !== '' && is_numeric($rawLh))
                    ? (float) $rawLh
                    : null;

                // 3. Progress & Status
                $progressNum = (float) ($row->progress ?? 0);
                $progressDecimal = round($progressNum / 100, 4);
                $progressStatus = ($progressNum >= 100) ? 'Finished' : (($progressNum > 0) ? 'Ongoing' : 'Not Started');

                // 4. Dates & Times with dd/mm/yyyy formatting and empty when missing
                $startDateStr = self::formatDate($row->start_date ?? null);
                $endDateStr = self::formatDate($row->end_date ?: ($row->finish_date ?? null));

                $startTimeStr = !empty($row->start_time) ? date('H:i:s', strtotime($row->start_time)) : '';
                $endTimeStr = !empty($row->end_time) ? date('H:i:s', strtotime($row->end_time)) : '';

                $recordMonthDate = $row->start_date ?: ($row->end_date ?: ($row->created_at ?? null));
                $recordMonthStr = !empty($recordMonthDate) ? date('F', strtotime($recordMonthDate)) : '-';

                // 5. Attendance: strictly Yes or No
                $rawAtt = strtolower(trim((string) ($row->attendance ?? '')));
                if ($rawAtt !== '') {
                    $attendanceStr = in_array($rawAtt, ['1', 'yes', 'present', 'true', 'hadir', 'completed', 'finished'], true) ? 'Yes' : 'No';
                } else {
                    $attendanceStr = ($progressNum >= 100 || !empty($row->finish_date)) ? 'Yes' : 'No';
                }

                // 6. Bond Amount, Duration & End Date
                $idr = $row->bond_amount_idr ?? ($row->bond_amount ?? null);
                $hasBond = ($idr !== null && is_numeric($idr) && (float) $idr > 0);

                $bondDurationVal = '';
                if (!empty($row->bond_duration)) {
                    $parsedDur = (int) preg_replace('/\D/', '', (string) $row->bond_duration);
                    if ($parsedDur > 0) {
                        $bondDurationVal = $parsedDur;
                    }
                }
                $bondEndDateStr = $hasBond && !empty($row->bond_end_date) ? self::formatDate($row->bond_end_date) : '';

                // 7. Scores (Float 0.00)
                $scoreL1 = (isset($row->l1_program) && is_numeric($row->l1_program)) ? (float) $row->l1_program : null;

                $rawScoreL2 = $row->assessment_score ?? ($row->l2_numerical ?? null);
                $scoreL2 = ($rawScoreL2 !== null && is_numeric($rawScoreL2)) ? (float) $rawScoreL2 : null;

                $scoreL3 = (isset($row->l3_numerical) && is_numeric($row->l3_numerical)) ? (float) $row->l3_numerical : null;

                // 8. L2 Status: strictly PASSED, FAILED, or empty
                $l2Status = '';
                $rawL2Verbal = strtolower(trim((string) ($row->l2_verbal ?? '')));
                if ($rawL2Verbal !== '' && $rawL2Verbal !== '-') {
                    if (str_contains($rawL2Verbal, 'pass') || str_contains($rawL2Verbal, 'good') || str_contains($rawL2Verbal, 'excellent') || str_contains($rawL2Verbal, 'satisfactory')) {
                        $l2Status = 'PASSED';
                    } elseif (str_contains($rawL2Verbal, 'fail')) {
                        $l2Status = 'FAILED';
                    }
                }
                if ($l2Status === '' && $scoreL2 !== null) {
                    $l2Status = ($scoreL2 >= 70) ? 'PASSED' : 'FAILED';
                }

                // 9. L3 Status: free text string
                $l3Status = !empty($row->l3_verbal) && $row->l3_verbal !== '-' ? trim((string) $row->l3_verbal) : '';

                // 10. Total Engagement Time: from duration for attendees, 0 for absent
                $courseDurSec = !empty($row->duration_seconds) && (int) $row->duration_seconds > 0
                    ? (int) $row->duration_seconds
                    : (!empty($row->learning_hours) && (float) $row->learning_hours > 0 ? (int) round((float) $row->learning_hours * 3600) : 0);

                $totalEngagementFormatted = '00:00:00';
                $totalEngagementLh = 0.00;
                if ($attendanceStr === 'Yes') {
                    $totSec = $courseDurSec ?: (!empty($row->learning_hours) && (float) $row->learning_hours > 0 ? (int) round((float) $row->learning_hours * 3600) : 0);
                    if ($totSec > 0) {
                        $hours = floor($totSec / 3600);
                        $mins = floor(($totSec % 3600) / 60);
                        $seconds = $totSec % 60;
                        $totalEngagementFormatted = sprintf('%02d:%02d:%02d', $hours, $mins, $seconds);
                        $totalEngagementLh = round($totSec / 3600, 2);
                    }
                }

                // 11. Employee ID & Certificate
                $rawEmp = $row->employee_id ?? ($row->id_emp ?? '-');
                $cleanEmpId = ($rawEmp !== '-') ? preg_replace('/\s*[\/\-]\s*NIP.*$/i', '', trim((string) $rawEmp)) : '-';
                $hasCert = (!empty($row->certificate) && $row->certificate !== '-' && $row->certificate !== 'No' && $row->certificate !== '0');

                $certInst = '';
                $rawCertIssued = $row->certificate_issued_date ?? null;
                $rawCertExpired = $row->certificate_expired_date ?? null;

                if (property_exists($row, 'cert_institution') || property_exists($row, 'cs_ot_institution')) {
                    $hasDbCert = !empty($row->cert_id);
                    $hasCsSetting = !empty($row->cs_ot_id);

                    if ($hasDbCert || $hasCsSetting || !empty($rawCertIssued)) {
                        $hasCert = true;
                    }

                    $certInst = $row->cert_institution
                        ?: ($row->cs_cert_institution
                            ?: ($row->cs_ot_institution
                                ?: ($row->cs_cert_issuer_name
                                    ?: ($row->cs_ot_issuer_name ?: ($hasCert ? $row->certificate : '')))));

                    if (empty($rawCertIssued)) {
                        $rawCertIssued = $row->cert_issued_date
                            ?: ($row->cs_cert_issued_date
                                ?: ($row->cs_ot_issued_date
                                    ?: ($row->cert_issued_at ?? null)));
                    }
                    if (empty($rawCertExpired)) {
                        $rawCertExpired = $row->cert_expired_date
                            ?: ($row->cs_cert_expired_date
                                ?: ($row->cs_ot_expired_date ?? null));
                    }
                } else {
                    $dbCert = null;
                    if (!empty($row->id)) {
                        $dbCert = DB::table('certificates as c')
                            ->leftJoin('certificate_settings as cs', 'cs.id', '=', 'c.certificate_setting_id')
                            ->where('c.offline_training_user_id', $row->id)
                            ->select([
                                'cs.issuer_name',
                                'cs.institution as cs_institution',
                                'cs.issued_date as cs_issued_date',
                                'cs.expired_date as cs_expired_date',
                                'c.institution as c_institution',
                                'c.issued_at',
                                'c.issued_date',
                                'c.expired_date',
                            ])
                            ->first();
                    }
                    $csSetting = null;
                    if (!empty($row->offline_training_id)) {
                        $csSetting = DB::table('certificate_settings')->where('offline_training_id', $row->offline_training_id)->first();
                    }

                    if ($dbCert || $csSetting || !empty($rawCertIssued)) {
                        $hasCert = true;
                    }

                    $certInst = $dbCert?->c_institution
                        ?: ($dbCert?->cs_institution
                            ?: ($csSetting?->institution
                                ?: ($dbCert?->issuer_name
                                    ?: ($csSetting?->issuer_name ?: ($hasCert ? $row->certificate : '')))));

                    if (empty($rawCertIssued)) {
                        $rawCertIssued = $dbCert?->issued_date
                            ?: ($csSetting?->issued_date
                                ?: ($dbCert?->issued_at ?? null));
                    }
                    if (empty($rawCertExpired)) {
                        $rawCertExpired = $dbCert?->expired_date
                            ?: ($csSetting?->expired_date
                                ?: ($dbCert?->expired_at ?? null));
                    }
                }

                $certIssued = self::formatDate($rawCertIssued);
                $certExpired = self::formatDate($rawCertExpired);

                // 52 Canonical Cells (A through AZ)
                $cells = [
                    ['A',  'int',   $rowCount, '1'],                                                                                    // 1. No
                    ['B',  'str',   $cleanEmpId, '0'],                                                                                  // 2. Employee ID
                    ['C',  'str',   $row->participant_name ?: ($row->user_name ?? ($row->name ?? '-')), '0'],                          // 3. Employee Name
                    ['D',  'str',   $row->grade ?: '-', '0'],                                                                           // 4. Grade
                    ['E',  'str',   $row->job_position ?: '-', '0'],                                                                    // 5. Job Position
                    ['F',  'str',   $row->team ?: '-', '0'],                                                                            // 6. Team
                    ['G',  'str',   $row->department ?: '-', '0'],                                                                      // 7. Department
                    ['H',  'str',   $row->company ?: '-', '0'],                                                                         // 8. Company
                    ['I',  'str',   $row->course_name ?: ($row->title ?: '-'), '0'],                                                    // 9. Course Title
                    ['J',  'str',   $row->sub_course_name ?: '-', '0'],                                                                 // 10. Sub Course Title
                    ['K',  'str',   $row->type ?: '-', '0'],                                                                            // 11. Type
                    ['L',  'str',   $row->category ?: '-', '0'],                                                                        // 12. Category
                    ['M',  'str',   $row->level ?: '-', '0'],                                                                           // 14. Level
                    ['N',  'str',   $row->batch ?: '-', '0'],                                                                           // 14. Batch
                    ['O',  'str',   $row->main_classification ?: 'Unclassified', '0'],                                                  // 15. Main Classification
                    ['P',  'str',   $row->sub_classification ?: '-', '0'],                                                              // 16. Sub Classification
                    ['Q',  'str',   $delivMethod, '0'],                                                                                 // 17. Delivery Method
                    ['R',  'str',   $subDelivMethod, '0'],                                                                              // 18. Sub Delivery Method
                    ['S',  'str',   $row->provider ?: '-', '0'],                                                                        // 19. Provider
                    ['T',  'str',   $row->sub_provider ?: '-', '0'],                                                                    // 20. Sub Provider
                    ['U',  'str',   $row->provider_name ?: ($row->provider ?: '-'), '0'],                                               // 21. Provider Name
                    ['V',  'str',   $row->venue ?: '-', '0'],                                                                           // 22. Venue
                    ['W',  'str',   $row->instructor ?: '-', '0'],                                                                      // 23. Instructor
                    ['X',  'str',   '-', '0'],                                                                                          // 24. Course Duration
                    ['Y',  'str',   '-', '0'],                                                                                          // 25. Total Recognized Duration
                    ['Z',  $lHoursNumeric !== null ? 'lh' : 'blank', $lHoursNumeric !== null ? $lHoursNumeric : '', $lHoursNumeric !== null ? '6' : '0'], // 26. Learning Hours
                    ['AA', 'pct',   $progressDecimal, '2'],                                                                             // 27. % Progress
                    ['AB', 'str',   $progressStatus, '0'],                                                                              // 28. Progress Status
                    ['AC', $startDateStr !== '' ? 'str' : 'blank', $startDateStr, $startDateStr !== '' ? '3' : '0'],                   // 29. Start Date
                    ['AD', $endDateStr !== '' ? 'str' : 'blank', $endDateStr, $endDateStr !== '' ? '3' : '0'],                         // 30. End Date
                    ['AE', $startTimeStr !== '' ? 'str' : 'blank', $startTimeStr, $startTimeStr !== '' ? '4' : '0'],                   // 31. Start Time
                    ['AF', $endTimeStr !== '' ? 'str' : 'blank', $endTimeStr, $endTimeStr !== '' ? '4' : '0'],                         // 32. End Time
                    ['AG', 'str',   $recordMonthStr, '0'],                                                                              // 33. Record Month
                    ['AH', 'str',   $row->requirement ?: 'Mandatory', '0'],                                                             // 34. Requirement
                    ['AI', 'str',   $attendanceStr, '0'],                                                                               // 35. Attendance
                    ['AJ', 'str',   $row->attendance_reason ?: '', '0'],                                                                // 36. Attendance Reason
                    ['AK', 'str',   $hasBond ? 'Yes' : ($row->bond_status ?: 'No'), '0'],                                               // 37. Bond Status
                    ['AL', $bondDurationVal !== '' ? 'int' : 'blank', $bondDurationVal !== '' ? $bondDurationVal : '', $bondDurationVal !== '' ? '7' : '0'], // 38. Bond Duration
                    ['AM', $bondEndDateStr !== '' ? 'str' : 'blank', $bondEndDateStr, $bondEndDateStr !== '' ? '3' : '0'],             // 39. Bond End Date
                    ['AN', $hasBond ? 'curr' : 'blank', $hasBond ? (float) $idr : '', $hasBond ? '9' : '0'],                            // 40. Bond Amount
                    ['AO', $scoreL1 !== null ? 'float' : 'blank', $scoreL1 !== null ? $scoreL1 : '', $scoreL1 !== null ? '8' : '0'],   // 41. Score (L1) - Course
                    ['AP', $scoreL2 !== null ? 'float' : 'blank', $scoreL2 !== null ? $scoreL2 : '', $scoreL2 !== null ? '8' : '0'],   // 42. Score (L2)
                    ['AQ', 'str',   $l2Status, '0'],                                                                                    // 43. L2 Status
                    ['AR', $scoreL3 !== null ? 'float' : 'blank', $scoreL3 !== null ? $scoreL3 : '', $scoreL3 !== null ? '8' : '0'],   // 44. Score (L3)
                    ['AS', 'str',   $l3Status, '0'],                                                                                    // 45. L3 Status
                    ['AT', 'str',   $hasCert ? 'Available' : '', '0'],                                                                  // 46. Certificate Availability
                    ['AU', 'str',   $certInst, '0'],                                                                                    // 47. Certificate Institution
                    ['AV', $certIssued !== '' ? 'str' : 'blank', $certIssued, $certIssued !== '' ? '3' : '0'],                         // 48. Certificate Issued Date
                    ['AW', $certExpired !== '' ? 'str' : 'blank', $certExpired, $certExpired !== '' ? '3' : '0'],                       // 49. Certificate Expired Date
                    ['AX', 'str',   $row->pic ?: '', '0'],                                                                              // 50. Person in Charge (PIC)
                    ['AY', 'str',   $totalEngagementFormatted, '0'],                                                                    // 51. Total Engagement Time
                    ['AZ', 'lh',    $totalEngagementLh, '6'],                                                                           // 52. Total Engagement Time (LH)
                ];

                $buffer .= $this->buildXmlRow($r, $cells);

                if ($rowCount % $batchSize === 0) {
                    fwrite($sfh, $buffer);
                    $buffer = '';
                    if (function_exists('set_time_limit')) {
                        @set_time_limit(0);
                    }
                }
            }
        } finally {
            $pdo->setAttribute(\PDO::MYSQL_ATTR_USE_BUFFERED_QUERY, $wasBuffered);
        }

        if ($buffer !== '') {
            fwrite($sfh, $buffer);
            $buffer = '';
                    if (function_exists('set_time_limit')) {
                        @set_time_limit(0);
                    }
        }

        fwrite($sfh, '</sheetData>' . "\n");
        fwrite($sfh, '</worksheet>' . "\n");
        rewind($sfh);

        if (function_exists('set_time_limit')) {
            @set_time_limit(0);
        }
        $zip->addFileFromStream('xl/worksheets/sheet1.xml', $sfh);
        fclose($sfh);
        @unlink($sheetFile);

        $zip->finish();
        fclose($fh);

        return $tempXlsx;
    }

    protected function buildXmlRow(int $r, array $cells): string
    {
        $xml = "<row r=\"{$r}\">";
        foreach ($cells as $c) {
            [$colLetter, $type, $val, $styleId] = $c;
            $coord = "{$colLetter}{$r}";

            if ($val === null || $val === '' || $type === 'blank') {
                $xml .= "<c r=\"{$coord}\" s=\"{$styleId}\"/>";
            } elseif ($type === 'pct') {
                $xml .= "<c r=\"{$coord}\" s=\"{$styleId}\"><v>{$val}</v></c>";
            } elseif ($type === 'dur') {
                $durVal = number_format((float) $val, 10, '.', '');
                $xml .= "<c r=\"{$coord}\" s=\"{$styleId}\"><v>{$durVal}</v></c>";
            } elseif ($type === 'lh') {
                $lhVal = number_format((float) $val, 2, '.', '');
                $xml .= "<c r=\"{$coord}\" s=\"{$styleId}\"><v>{$lhVal}</v></c>";
            } elseif ($type === 'date') {
                $dateInt = (int) $val;
                $xml .= "<c r=\"{$coord}\" s=\"{$styleId}\"><v>{$dateInt}</v></c>";
            } elseif ($type === 'time') {
                $timeVal = number_format((float) $val, 10, '.', '');
                $xml .= "<c r=\"{$coord}\" s=\"{$styleId}\"><v>{$timeVal}</v></c>";
            } elseif ($type === 'int' || $type === 'num') {
                $intVal = (int) $val;
                $xml .= "<c r=\"{$coord}\" s=\"{$styleId}\"><v>{$intVal}</v></c>";
            } elseif ($type === 'float') {
                $floatVal = number_format((float) $val, 2, '.', '');
                $xml .= "<c r=\"{$coord}\" s=\"{$styleId}\"><v>{$floatVal}</v></c>";
            } elseif ($type === 'curr') {
                $currVal = (float) $val;
                $xml .= "<c r=\"{$coord}\" s=\"{$styleId}\"><v>{$currVal}</v></c>";
            } else {
                $escaped = $this->escapeXml($val);
                $xml .= "<c r=\"{$coord}\" t=\"inlineStr\" s=\"{$styleId}\"><is><t>{$escaped}</t></is></c>";
            }
        }
        $xml .= "</row>\n";
        return $xml;
    }

    protected function escapeXml($value): string
    {
        $clean = preg_replace('/[\x00-\x08\x0B\x0C\x0E-\x1F\x7F]/u', '', (string) $value);
        return htmlspecialchars($clean, ENT_XML1 | ENT_COMPAT, 'UTF-8');
    }
}
