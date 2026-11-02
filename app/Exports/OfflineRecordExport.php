<?php

namespace App\Exports;

use App\Models\OfflineTraining;
use App\Services\Record\OfflineTrainingExcelSchema;
use App\Services\Record\RecordOfflineQueryService;
use Carbon\Carbon;
use Illuminate\Contracts\Support\Responsable;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use ZipStream\Option\Archive as ArchiveOptions;
use ZipStream\ZipStream;

class OfflineRecordExport implements Responsable, \Maatwebsite\Excel\Concerns\WithHeadings, \Maatwebsite\Excel\Concerns\WithMapping, \Maatwebsite\Excel\Concerns\WithStyles
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

        return sprintf('EmployeeNonOWLrecord_%s at %s.xlsx', $dt->format('Y-m-d'), $dt->format('H-i-s'));
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

    public function getFileName(): string
    {
        return $this->fileName;
    }

    public static function headers(): array
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

        // 2. Course Duration: formatted as HH:MM:SS
        $durationFormatted = '-';
        $courseDurSec = !empty($row->duration_seconds) && (int) $row->duration_seconds > 0
            ? (int) $row->duration_seconds
            : (!empty($row->course_learning_hours) && (float) $row->course_learning_hours > 0 ? (int) ((float) $row->course_learning_hours * 3600) : null);

        if ($courseDurSec !== null && $courseDurSec > 0) {
            $hours = floor($courseDurSec / 3600);
            $mins = floor(($courseDurSec % 3600) / 60);
            $seconds = $courseDurSec % 60;
            $durationFormatted = sprintf('%02d:%02d:%02d', $hours, $mins, $seconds);
        }

        // 3. Learning Hours (raw numeric float for SUM calculations)
        $rawLh = $row->learning_hours ?? ($row->course_learning_hours ?? null);
        $lHoursNumeric = ($rawLh !== null && $rawLh !== '' && is_numeric($rawLh))
            ? (float) $rawLh
            : '-';

        // 4. Progress & Status
        $progressNum = (float) ($row->progress ?? 0);
        $progressDecimal = round($progressNum / 100, 4);
        $progressStatus = ($progressNum >= 100) ? 'Finished' : (($progressNum > 0) ? 'Ongoing' : 'Not Started');

        // 5. Date & Time (yyyy/mm/dd and hh:mm:ss)
        $startDateStr = !empty($row->start_date) ? date('Y/m/d', strtotime($row->start_date)) : '-';
        $endDateStr = !empty($row->end_date)
            ? date('Y/m/d', strtotime($row->end_date))
            : (!empty($row->finish_date) ? date('Y/m/d', strtotime($row->finish_date)) : '-');

        $startTimeStr = !empty($row->start_time) ? date('H:i:s', strtotime($row->start_time)) : '-';
        $endTimeStr = !empty($row->end_time) ? date('H:i:s', strtotime($row->end_time)) : '-';

        $recordMonthDate = $row->start_date ?: ($row->end_date ?: ($row->created_at ?? null));
        $recordMonthStr = !empty($recordMonthDate) ? date('F', strtotime($recordMonthDate)) : '-';

        // 6. Attendance: strictly Yes or No
        $rawAtt = strtolower(trim((string) ($row->attendance ?? '')));
        if ($rawAtt !== '') {
            $attendanceStr = in_array($rawAtt, ['1', 'yes', 'present', 'true', 'hadir', 'completed', 'finished'], true) ? 'Yes' : 'No';
        } else {
            $attendanceStr = ($progressNum >= 100 || !empty($row->finish_date)) ? 'Yes' : 'No';
        }

        // 7. Bond Amount, Duration & End Date
        $idr = $row->bond_amount_idr ?? ($row->bond_amount ?? null);
        $hasBond = ($idr !== null && is_numeric($idr) && (float) $idr > 0);

        $bondDurationVal = 6;
        if (!empty($row->bond_duration)) {
            $parsedDur = (int) preg_replace('/\D/', '', (string) $row->bond_duration);
            if ($parsedDur > 0) {
                $bondDurationVal = $parsedDur;
            }
        }

        $bondEndDateStr = !empty($row->bond_end_date) ? date('Y/m/d', strtotime($row->bond_end_date)) : '-';

        // 8. Scores (Float 0.00)
        $scoreL1 = (isset($row->l1_program) && is_numeric($row->l1_program)) ? (float) $row->l1_program : null;

        $rawScoreL2 = $row->assessment_score ?? ($row->l2_numerical ?? null);
        $scoreL2 = ($rawScoreL2 !== null && is_numeric($rawScoreL2)) ? (float) $rawScoreL2 : null;

        $scoreL3 = (isset($row->l3_numerical) && is_numeric($row->l3_numerical)) ? (float) $row->l3_numerical : null;

        // 9. L2 Status: strictly PASSED, FAILED, or -
        $l2Status = '-';
        $rawL2Verbal = strtolower(trim((string) ($row->l2_verbal ?? '')));
        if ($rawL2Verbal !== '' && $rawL2Verbal !== '-') {
            if (str_contains($rawL2Verbal, 'pass') || str_contains($rawL2Verbal, 'good') || str_contains($rawL2Verbal, 'excellent') || str_contains($rawL2Verbal, 'satisfactory')) {
                $l2Status = 'PASSED';
            } elseif (str_contains($rawL2Verbal, 'fail')) {
                $l2Status = 'FAILED';
            }
        }
        if ($l2Status === '-' && $scoreL2 !== null) {
            $l2Status = ($scoreL2 >= 70) ? 'PASSED' : 'FAILED';
        }

        // 10. L3 Status: free text string
        $l3Status = !empty($row->l3_verbal) && $row->l3_verbal !== '-' ? trim((string) $row->l3_verbal) : '-';

        // 11. Total Engagement Time: from duration for attendees, 0 for absent
        $totalEngagementFormatted = '-';
        if ($attendanceStr === 'Yes') {
            $totSec = $courseDurSec ?: (!empty($row->learning_hours) && (float) $row->learning_hours > 0 ? (int) ((float) $row->learning_hours * 3600) : 0);
            if ($totSec > 0) {
                $hours = floor($totSec / 3600);
                $mins = floor(($totSec % 3600) / 60);
                $seconds = $totSec % 60;
                $totalEngagementFormatted = sprintf('%02d:%02d:%02d', $hours, $mins, $seconds);
            } elseif ($courseDurSec !== null) {
                $totalEngagementFormatted = '00:00:00';
            }
        } elseif ($attendanceStr === 'No') {
            $totalEngagementFormatted = '00:00:00';
        }

        // 12. Employee ID & Certificate
        $rawEmp = $row->employee_id ?? ($row->id_emp ?? '-');
        $cleanEmpId = ($rawEmp !== '-') ? preg_replace('/\s*[\/\-]\s*NIP.*$/i', '', trim((string) $rawEmp)) : '-';
        $hasCert = (!empty($row->certificate) && $row->certificate !== '-');

        if (property_exists($row, 'cert_institution') || property_exists($row, 'cs_ot_institution')) {
            $hasDbCert = !empty($row->cert_id);
            $hasCsSetting = !empty($row->cs_ot_id);

            if ($hasDbCert || $hasCsSetting) {
                $hasCert = true;
            }

            $certInst = $row->cert_institution
                ?: ($row->cs_cert_institution
                    ?: ($row->cs_ot_institution
                        ?: ($row->cs_cert_issuer_name
                            ?: ($row->cs_ot_issuer_name ?: ($hasCert ? $row->certificate : '-')))));

            $rawCertIssued = $row->cert_issued_date
                ?: ($row->cs_cert_issued_date
                    ?: ($row->cs_ot_issued_date
                        ?: ($row->cert_issued_at ?? null)));
            $certIssued = !empty($rawCertIssued) ? date('Y-m-d', strtotime($rawCertIssued)) : '-';

            $rawCertExpired = $row->cert_expired_date
                ?: ($row->cs_cert_expired_date
                    ?: ($row->cs_ot_expired_date ?? null));
            $certExpired = !empty($rawCertExpired) ? date('Y-m-d', strtotime($rawCertExpired)) : '-';
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

            if ($dbCert || $csSetting) {
                $hasCert = true;
            }

            $certInst = $dbCert?->c_institution
                ?: ($dbCert?->cs_institution
                    ?: ($csSetting?->institution
                        ?: ($dbCert?->issuer_name
                            ?: ($csSetting?->issuer_name ?: ($hasCert ? $row->certificate : '-')))));

            $rawCertIssued = $dbCert?->issued_date
                ?: ($csSetting?->issued_date
                    ?: ($dbCert?->issued_at ?? null));
            $certIssued = !empty($rawCertIssued) ? date('Y-m-d', strtotime($rawCertIssued)) : '-';

            $rawCertExpired = $dbCert?->expired_date
                ?: ($csSetting?->expired_date
                    ?: ($dbCert?->expired_at ?? null));
            $certExpired = !empty($rawCertExpired) ? date('Y-m-d', strtotime($rawCertExpired)) : '-';
        }

        return [
            $this->rowCount,                                                                                    // 1. NO
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
            $durationFormatted,                                                                                 // 24. Course Duration
            $lHoursNumeric,                                                                                     // 25. Learning Hours
            $progressDecimal,                                                                                   // 26. % Progress
            $progressStatus,                                                                                    // 27. Progress Status
            $startDateStr,                                                                                      // 28. Start Date
            $endDateStr,                                                                                        // 29. End Date
            $startTimeStr,                                                                                      // 30. Start Time
            $endTimeStr,                                                                                        // 31. End Time
            $recordMonthStr,                                                                                    // 32. Record Month
            $row->requirement ?: 'Mandatory',                                                                   // 33. Requirement
            $attendanceStr,                                                                                     // 34. Attendance
            $row->attendance_reason ?: '-',                                                                     // 35. Attendance Reason
            $hasBond ? 'Yes' : ($row->bond_status ?: 'No'),                                                     // 36. Bond Status
            $bondDurationVal,                                                                                   // 37. Bond Duration
            $bondEndDateStr,                                                                                    // 38. Bond End Date
            $hasBond ? (float) $idr : '-',                                                                      // 39. Bond Amount
            $scoreL1 !== null ? $scoreL1 : '-',                                                                 // 40. Score (L1) - Course
            $scoreL2 !== null ? $scoreL2 : '-',                                                                 // 41. Score (L2)
            $l2Status,                                                                                          // 42. L2 Status
            $scoreL3 !== null ? $scoreL3 : '-',                                                                 // 43. Score (L3)
            $l3Status,                                                                                          // 44. L3 Status
            $hasCert ? 'Available' : '-',                                                                       // 45. Certificate Availability
            $certInst,                                                                                          // 46. Certificate Institution
            $certIssued,                                                                                        // 47. Certificate Issued Date
            $certExpired,                                                                                       // 48. Certificate Expired Date
            $row->pic ?: '-',                                                                                   // 49. Person in Charge (PIC)
            $totalEngagementFormatted,                                                                          // 50. Total Engagement Time (LH)
        ];
    }

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
            'X'  => 18,  // Course Duration
            'Y'  => 16,  // Learning Hours
            'Z'  => 14,  // % Progress
            'AA' => 16,  // Progress Status
            'AB' => 14,  // Start Date
            'AC' => 14,  // End Date
            'AD' => 14,  // Start Time
            'AE' => 14,  // End Time
            'AF' => 16,  // Record Month
            'AG' => 15,  // Requirement
            'AH' => 14,  // Attendance
            'AI' => 25,  // Attendance Reason
            'AJ' => 14,  // Bond Status
            'AK' => 16,  // Bond Duration
            'AL' => 16,  // Bond End Date
            'AM' => 18,  // Bond Amount
            'AN' => 20,  // Score (L1) - Course
            'AO' => 15,  // Score (L2)
            'AP' => 15,  // L2 Status
            'AQ' => 15,  // Score (L3)
            'AR' => 15,  // L3 Status
            'AS' => 22,  // Certificate Availability
            'AT' => 25,  // Certificate Institution
            'AU' => 20,  // Certificate Issued Date
            'AV' => 20,  // Certificate Expired Date
            'AW' => 25,  // Person in Charge (PIC)
            'AX' => 25,  // Total Engagement Time (LH)
        ];
    }

    public function styles(\PhpOffice\PhpSpreadsheet\Worksheet\Worksheet $sheet)
    {
        return [
            1 => [
                'font' => [
                    'bold' => true,
                    'color' => ['rgb' => 'FFFFFF'],
                    'size' => 11,
                    'name' => 'Calibri',
                ],
                'fill' => [
                    'fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,
                    'startColor' => ['rgb' => '002B49'],
                ],
                'alignment' => [
                    'horizontal' => \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER,
                    'vertical' => \PhpOffice\PhpSpreadsheet\Style\Alignment::VERTICAL_CENTER,
                    'wrapText' => true,
                ],
            ],
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
            ->orderBy('otu.id', 'asc');
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
        ini_set('max_execution_time', 600);
        if (function_exists('set_time_limit')) {
            @set_time_limit(600);
        }

        $tempXlsx = tempnam(sys_get_temp_dir(), 'offline_record_') . '.xlsx';
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
        $zip->addFile(
            'xl/workbook.xml',
            '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            . '<workbook xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main" xmlns:r="http://schemas.openxmlformats.org/officeDocument/2006/relationships">'
            . '<sheets><sheet name="Non KP OWL Import" sheetId="1" r:id="rId1"/></sheets>'
            . '</workbook>'
        );

        // 5. xl/styles.xml - Blue Header (Row 1) & Default Excel Normal Theme (Row 2+)
        $zip->addFile(
            'xl/styles.xml',
            '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            . '<styleSheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main">'
            . '<numFmts count="6">'
            . '<numFmt numFmtId="164" formatCode="yyyy/mm/dd"/>'
            . '<numFmt numFmtId="165" formatCode="hh:mm:ss"/>'
            . '<numFmt numFmtId="166" formatCode="[h]:mm:ss"/>'
            . '<numFmt numFmtId="167" formatCode="0.00&quot; h&quot;"/>'
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

                // 2. Course Duration: numeric Excel duration ([h]:mm:ss)
                $courseDurVal = null;
                $courseDurSec = !empty($row->duration_seconds) && (int) $row->duration_seconds > 0
                    ? (int) $row->duration_seconds
                    : (!empty($row->course_learning_hours) && (float) $row->course_learning_hours > 0 ? (int) ((float) $row->course_learning_hours * 3600) : null);

                if ($courseDurSec !== null && $courseDurSec > 0) {
                    $courseDurVal = $courseDurSec / 86400.0;
                }

                // 3. Learning Hours: float numeric (0.00" h")
                $rawLh = $row->learning_hours ?? ($row->course_learning_hours ?? null);
                $lHoursNumeric = ($rawLh !== null && $rawLh !== '' && is_numeric($rawLh))
                    ? (float) $rawLh
                    : null;

                // 4. Progress & Status
                $progressNum = (float) ($row->progress ?? 0);
                $progressDecimal = round($progressNum / 100, 4);
                $progressStatus = ($progressNum >= 100) ? 'Finished' : (($progressNum > 0) ? 'Ongoing' : 'Not Started');

                // 5. Dates & Times (Date & Time Serials)
                $startDateSerial = null;
                if (!empty($row->start_date) && strlen($row->start_date) >= 10) {
                    $cDate = substr($row->start_date, 0, 10);
                    $cParts = explode('-', $cDate);
                    if (count($cParts) === 3 && checkdate((int) $cParts[1], (int) $cParts[2], (int) $cParts[0])) {
                        $startDateSerial = 25569 + (int) (gmmktime(0, 0, 0, (int) $cParts[1], (int) $cParts[2], (int) $cParts[0]) / 86400);
                    }
                }

                $endDateSerial = null;
                $rawEndDate = $row->end_date ?: ($row->finish_date ?? null);
                if (!empty($rawEndDate) && strlen($rawEndDate) >= 10) {
                    $eDate = substr($rawEndDate, 0, 10);
                    $eParts = explode('-', $eDate);
                    if (count($eParts) === 3 && checkdate((int) $eParts[1], (int) $eParts[2], (int) $eParts[0])) {
                        $endDateSerial = 25569 + (int) (gmmktime(0, 0, 0, (int) $eParts[1], (int) $eParts[2], (int) $eParts[0]) / 86400);
                    }
                }

                $startTimeSerial = null;
                if (!empty($row->start_time)) {
                    $st = trim((string) $row->start_time);
                    if (preg_match('/^(\d{1,2}):(\d{2})(?::(\d{2}))?/', $st, $m)) {
                        $th = (int) $m[1];
                        $tm = (int) $m[2];
                        $ts = isset($m[3]) ? (int) $m[3] : 0;
                        $startTimeSerial = ($th * 3600 + $tm * 60 + $ts) / 86400.0;
                    }
                }

                $endTimeSerial = null;
                if (!empty($row->end_time)) {
                    $et = trim((string) $row->end_time);
                    if (preg_match('/^(\d{1,2}):(\d{2})(?::(\d{2}))?/', $et, $m)) {
                        $th = (int) $m[1];
                        $tm = (int) $m[2];
                        $ts = isset($m[3]) ? (int) $m[3] : 0;
                        $endTimeSerial = ($th * 3600 + $tm * 60 + $ts) / 86400.0;
                    }
                }

                $recordMonthDate = $row->start_date ?: ($row->end_date ?: ($row->created_at ?? null));
                $recordMonthStr = !empty($recordMonthDate) ? date('F', strtotime($recordMonthDate)) : '-';

                // 6. Attendance: strictly Yes or No
                $rawAtt = strtolower(trim((string) ($row->attendance ?? '')));
                if ($rawAtt !== '') {
                    $attendanceStr = in_array($rawAtt, ['1', 'yes', 'present', 'true', 'hadir', 'completed', 'finished'], true) ? 'Yes' : 'No';
                } else {
                    $attendanceStr = ($progressNum >= 100 || !empty($row->finish_date)) ? 'Yes' : 'No';
                }

                // 7. Bond Amount, Duration & End Date
                $idr = $row->bond_amount_idr ?? ($row->bond_amount ?? null);
                $hasBond = ($idr !== null && is_numeric($idr) && (float) $idr > 0);

                $bondDurationVal = 6;
                if (!empty($row->bond_duration)) {
                    $parsedDur = (int) preg_replace('/\D/', '', (string) $row->bond_duration);
                    if ($parsedDur > 0) {
                        $bondDurationVal = $parsedDur;
                    }
                }

                $bondEndDateSerial = null;
                if (!empty($row->bond_end_date) && strlen($row->bond_end_date) >= 10) {
                    $bDate = substr($row->bond_end_date, 0, 10);
                    $bParts = explode('-', $bDate);
                    if (count($bParts) === 3 && checkdate((int) $bParts[1], (int) $bParts[2], (int) $bParts[0])) {
                        $bondEndDateSerial = 25569 + (int) (gmmktime(0, 0, 0, (int) $bParts[1], (int) $bParts[2], (int) $bParts[0]) / 86400);
                    }
                }

                // 8. Scores (Float 0.00)
                $scoreL1 = (isset($row->l1_program) && is_numeric($row->l1_program)) ? (float) $row->l1_program : null;

                $rawScoreL2 = $row->assessment_score ?? ($row->l2_numerical ?? null);
                $scoreL2 = ($rawScoreL2 !== null && is_numeric($rawScoreL2)) ? (float) $rawScoreL2 : null;

                $scoreL3 = (isset($row->l3_numerical) && is_numeric($row->l3_numerical)) ? (float) $row->l3_numerical : null;

                // 9. L2 Status: strictly PASSED, FAILED, or -
                $l2Status = '-';
                $rawL2Verbal = strtolower(trim((string) ($row->l2_verbal ?? '')));
                if ($rawL2Verbal !== '' && $rawL2Verbal !== '-') {
                    if (str_contains($rawL2Verbal, 'pass') || str_contains($rawL2Verbal, 'good') || str_contains($rawL2Verbal, 'excellent') || str_contains($rawL2Verbal, 'satisfactory')) {
                        $l2Status = 'PASSED';
                    } elseif (str_contains($rawL2Verbal, 'fail')) {
                        $l2Status = 'FAILED';
                    }
                }
                if ($l2Status === '-' && $scoreL2 !== null) {
                    $l2Status = ($scoreL2 >= 70) ? 'PASSED' : 'FAILED';
                }

                // 10. L3 Status: free text string
                $l3Status = !empty($row->l3_verbal) && $row->l3_verbal !== '-' ? trim((string) $row->l3_verbal) : '-';

                // 11. Total Engagement Time: from duration for attendees, 0 for absent
                $totEngVal = null;
                if ($attendanceStr === 'Yes') {
                    $totSec = $courseDurSec ?: (!empty($row->learning_hours) && (float) $row->learning_hours > 0 ? (int) ((float) $row->learning_hours * 3600) : null);
                    if ($totSec !== null && $totSec > 0) {
                        $totEngVal = $totSec / 86400.0;
                    } elseif ($courseDurSec !== null) {
                        $totEngVal = 0.0;
                    }
                } elseif ($attendanceStr === 'No') {
                    $totEngVal = 0.0;
                }

                // 12. Employee ID & Certificate
                $rawEmp = $row->employee_id ?? ($row->id_emp ?? '-');
                $cleanEmpId = ($rawEmp !== '-') ? preg_replace('/\s*[\/\-]\s*NIP.*$/i', '', trim((string) $rawEmp)) : '-';
                $hasCert = (!empty($row->certificate) && $row->certificate !== '-');

                if (property_exists($row, 'cert_institution') || property_exists($row, 'cs_ot_institution')) {
                    $hasDbCert = !empty($row->cert_id);
                    $hasCsSetting = !empty($row->cs_ot_id);

                    if ($hasDbCert || $hasCsSetting) {
                        $hasCert = true;
                    }

                    $certInst = $row->cert_institution
                        ?: ($row->cs_cert_institution
                            ?: ($row->cs_ot_institution
                                ?: ($row->cs_cert_issuer_name
                                    ?: ($row->cs_ot_issuer_name ?: ($hasCert ? $row->certificate : '-')))));

                    $rawCertIssued = $row->cert_issued_date
                        ?: ($row->cs_cert_issued_date
                            ?: ($row->cs_ot_issued_date
                                ?: ($row->cert_issued_at ?? null)));
                    $certIssued = !empty($rawCertIssued) ? date('Y-m-d', strtotime($rawCertIssued)) : '-';

                    $rawCertExpired = $row->cert_expired_date
                        ?: ($row->cs_cert_expired_date
                            ?: ($row->cs_ot_expired_date ?? null));
                    $certExpired = !empty($rawCertExpired) ? date('Y-m-d', strtotime($rawCertExpired)) : '-';
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

                    if ($dbCert || $csSetting) {
                        $hasCert = true;
                    }

                    $certInst = $dbCert?->c_institution
                        ?: ($dbCert?->cs_institution
                            ?: ($csSetting?->institution
                                ?: ($dbCert?->issuer_name
                                    ?: ($csSetting?->issuer_name ?: ($hasCert ? $row->certificate : '-')))));

                    $rawCertIssued = $dbCert?->issued_date
                        ?: ($csSetting?->issued_date
                            ?: ($dbCert?->issued_at ?? null));
                    $certIssued = !empty($rawCertIssued) ? date('Y-m-d', strtotime($rawCertIssued)) : '-';

                    $rawCertExpired = $dbCert?->expired_date
                        ?: ($csSetting?->expired_date
                            ?: ($dbCert?->expired_at ?? null));
                    $certExpired = !empty($rawCertExpired) ? date('Y-m-d', strtotime($rawCertExpired)) : '-';
                }

                $certIssuedSerial = null;
                if ($certIssued !== '-' && strlen($certIssued) >= 10) {
                    $cParts = explode('-', substr($certIssued, 0, 10));
                    if (count($cParts) === 3 && checkdate((int) $cParts[1], (int) $cParts[2], (int) $cParts[0])) {
                        $certIssuedSerial = 25569 + (int) (gmmktime(0, 0, 0, (int) $cParts[1], (int) $cParts[2], (int) $cParts[0]) / 86400);
                    }
                }

                $certExpiredSerial = null;
                if ($certExpired !== '-' && strlen($certExpired) >= 10) {
                    $cParts = explode('-', substr($certExpired, 0, 10));
                    if (count($cParts) === 3 && checkdate((int) $cParts[1], (int) $cParts[2], (int) $cParts[0])) {
                        $certExpiredSerial = 25569 + (int) (gmmktime(0, 0, 0, (int) $cParts[1], (int) $cParts[2], (int) $cParts[0]) / 86400);
                    }
                }

                // 50 Canonical Cells (A through AX) - Uniform default style
                $cells = [
                    ['A',  'int',   $rowCount, '1'],                                                                                    // 1. NO
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
                    ['M',  'str',   $row->level ?: '-', '0'],                                                                           // 13. Level
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
                    ['X',  $courseDurVal !== null ? 'dur' : 'str', $courseDurVal !== null ? $courseDurVal : '-', $courseDurVal !== null ? '5' : '0'], // 24. Course Duration
                    ['Y',  $lHoursNumeric !== null ? 'lh' : 'str', $lHoursNumeric !== null ? $lHoursNumeric : '-', $lHoursNumeric !== null ? '6' : '0'], // 25. Learning Hours
                    ['Z',  'pct',   $progressDecimal, '2'],                                                                             // 26. % Progress
                    ['AA', 'str',   $progressStatus, '0'],                                                                              // 27. Progress Status
                    ['AB', $startDateSerial !== null ? 'date' : 'str', $startDateSerial !== null ? $startDateSerial : '-', $startDateSerial !== null ? '3' : '0'], // 28. Start Date
                    ['AC', $endDateSerial !== null ? 'date' : 'str', $endDateSerial !== null ? $endDateSerial : '-', $endDateSerial !== null ? '3' : '0'],     // 29. End Date
                    ['AD', $startTimeSerial !== null ? 'time' : 'str', $startTimeSerial !== null ? $startTimeSerial : '-', $startTimeSerial !== null ? '4' : '0'], // 30. Start Time
                    ['AE', $endTimeSerial !== null ? 'time' : 'str', $endTimeSerial !== null ? $endTimeSerial : '-', $endTimeSerial !== null ? '4' : '0'],     // 31. End Time
                    ['AF', 'str',   $recordMonthStr, '0'],                                                                              // 32. Record Month
                    ['AG', 'str',   $row->requirement ?: 'Mandatory', '0'],                                                            // 33. Requirement
                    ['AH', 'str',   $attendanceStr, '0'],                                                                               // 34. Attendance
                    ['AI', 'str',   $row->attendance_reason ?: '-', '0'],                                                              // 35. Attendance Reason
                    ['AJ', 'str',   $hasBond ? 'Yes' : ($row->bond_status ?: 'No'), '0'],                                              // 36. Bond Status
                    ['AK', 'int',   $bondDurationVal, '7'],                                                                             // 37. Bond Duration
                    ['AL', $bondEndDateSerial !== null ? 'date' : 'str', $bondEndDateSerial !== null ? $bondEndDateSerial : '-', $bondEndDateSerial !== null ? '3' : '0'], // 38. Bond End Date
                    ['AM', $hasBond ? 'curr' : 'str', $hasBond ? (float) $idr : '-', $hasBond ? '9' : '0'],                           // 39. Bond Amount
                    ['AN', $scoreL1 !== null ? 'float' : 'str', $scoreL1 !== null ? $scoreL1 : '-', $scoreL1 !== null ? '8' : '0'],  // 40. Score (L1) - Course
                    ['AO', $scoreL2 !== null ? 'float' : 'str', $scoreL2 !== null ? $scoreL2 : '-', $scoreL2 !== null ? '8' : '0'],  // 41. Score (L2)
                    ['AP', 'str',   $l2Status, '0'],                                                                                    // 42. L2 Status
                    ['AQ', $scoreL3 !== null ? 'float' : 'str', $scoreL3 !== null ? $scoreL3 : '-', $scoreL3 !== null ? '8' : '0'],  // 43. Score (L3)
                    ['AR', 'str',   $l3Status, '0'],                                                                                    // 44. L3 Status
                    ['AS', 'str',   $hasCert ? 'Available' : '-', '0'],                                                                // 45. Certificate Availability
                    ['AT', 'str',   $certInst, '0'],                                                                                    // 46. Certificate Institution
                    ['AU', $certIssuedSerial !== null ? 'date' : 'str', $certIssuedSerial !== null ? $certIssuedSerial : '-', $certIssuedSerial !== null ? '3' : '0'], // 47. Certificate Issued Date
                    ['AV', $certExpiredSerial !== null ? 'date' : 'str', $certExpiredSerial !== null ? $certExpiredSerial : '-', $certExpiredSerial !== null ? '3' : '0'], // 48. Certificate Expired Date
                    ['AW', 'str',   $row->pic ?: '-', '0'],                                                                            // 49. Person in Charge (PIC)
                    ['AX', $totEngVal !== null ? 'dur' : 'str', $totEngVal !== null ? $totEngVal : '-', $totEngVal !== null ? '5' : '0'], // 50. Total Engagement Time (LH)
                ];

                $buffer .= $this->buildXmlRow($r, $cells);

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

    protected function buildXmlRow(int $r, array $cells): string
    {
        $xml = "<row r=\"{$r}\">";
        foreach ($cells as $c) {
            [$colLetter, $type, $val, $styleId] = $c;
            $coord = "{$colLetter}{$r}";

            if ($val === null || $val === '' || $val === '-') {
                $xml .= "<c r=\"{$coord}\" t=\"inlineStr\" s=\"0\"><is><t>-</t></is></c>";
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
