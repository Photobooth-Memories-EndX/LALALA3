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
        $monthMap = [
            1 => 'Jan', 2 => 'Feb', 3 => 'Mar', 4 => 'Apr',
            5 => 'Mei', 6 => 'Jun', 7 => 'Jul', 8 => 'Agu',
            9 => 'Sep', 10 => 'Okt', 11 => 'Nov', 12 => 'Des',
        ];
        $day = $dt->format('d');
        $month = $monthMap[(int) $dt->format('n')] ?? $dt->format('M');
        $year = $dt->format('Y');
        $time = $dt->format('H.i.s');

        return sprintf('non-kp-owl-all-records-%s.xlsx', now()->format('Y-m-d'));
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
            'Source',
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
            'Total Engagement Time',
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
        ];
    }

    public function headings(): array
    {
        return self::headers();
    }

    public function map($row): array
    {
        $this->rowCount++;

        // Duration formatted as HH:MM:SS
        $durationFormatted = '-';
        if (!empty($row->duration_seconds)) {
            $secs = (int) $row->duration_seconds;
            $hours = floor($secs / 3600);
            $mins = floor(($secs % 3600) / 60);
            $seconds = $secs % 60;
            $durationFormatted = sprintf('%02d:%02d:%02d', $hours, $mins, $seconds);
        }

        // Learning Hours (raw numeric float for SUM calculations)
        $rawLh = $row->course_learning_hours ?? ($row->learning_hours ?? null);
        $lHoursNumeric = ($rawLh !== null && $rawLh !== '' && is_numeric($rawLh))
            ? (float) $rawLh
            : '-';

        // Progress & Status (raw numeric decimal)
        $progressNum = (float) ($row->progress ?? 0);
        $progressDecimal = round($progressNum / 100, 4);
        $progressStatus = ($progressNum >= 100) ? 'Finished' : (($progressNum > 0) ? 'Ongoing' : 'Not Started');

        // Date & Time split
        $startDateStr = !empty($row->start_date) ? date('Y-m-d', strtotime($row->start_date)) : '-';
        $endDateStr = !empty($row->end_date)
            ? date('Y-m-d', strtotime($row->end_date))
            : (!empty($row->finish_date) ? date('Y-m-d', strtotime($row->finish_date)) : '-');

        $startTimeStr = !empty($row->start_time) ? date('H:i:s', strtotime($row->start_time)) : '-';
        $endTimeStr = !empty($row->end_time) ? date('H:i:s', strtotime($row->end_time)) : '-';

        $recordMonthDate = $row->start_date ?: ($row->end_date ?: ($row->created_at ?? null));
        $recordMonthStr = !empty($recordMonthDate) ? date('F', strtotime($recordMonthDate)) : '-';

        // Attendance: strictly Yes or No
        $rawAtt = strtolower(trim((string) ($row->attendance ?? '')));
        if ($rawAtt !== '') {
            $attendanceStr = in_array($rawAtt, ['1', 'yes', 'present', 'true', 'hadir', 'completed', 'finished'], true) ? 'Yes' : 'No';
        } else {
            $attendanceStr = ($progressNum >= 100 || !empty($row->finish_date)) ? 'Yes' : 'No';
        }

        // Delivery Method: strictly Online or Offline
        $rawDeliv = strtolower(trim((string) ($row->delivery_method ?? '')));
        $delivMethod = in_array($rawDeliv, ['online', 'e-learning', 'webinar', 'kp-owl', 'kp owl'], true) ? 'Online' : 'Offline';
        $subDelivMethod = $row->sub_delivery_method ?: 'On-site';

        // Bond Amount & Duration
        $idr = $row->bond_amount_idr ?? ($row->bond_amount ?? null);
        $hasBond = ($idr !== null && is_numeric($idr) && (float) $idr > 0);

        $bondDurationMonths = null;
        if (!empty($row->bond_duration)) {
            $parsedDur = (int) preg_replace('/\D/', '', (string) $row->bond_duration);
            if ($parsedDur > 0) {
                $bondDurationMonths = $parsedDur;
            }
        }

        $bondEndDateStr = !empty($row->bond_end_date) ? date('Y-m-d', strtotime($row->bond_end_date)) : '-';

        // Scores: strictly whole integers if whole number
        $scoreL1 = null;
        if (isset($row->l1_program) && is_numeric($row->l1_program)) {
            $val = (float) $row->l1_program;
            $intVal = (int) round($val);
            $scoreL1 = (abs($val - $intVal) < 0.0001) ? $intVal : round($val, 2);
        }

        $scoreL2 = null;
        $rawScoreL2 = $row->assessment_score ?? ($row->l2_numerical ?? null);
        if ($rawScoreL2 !== null && is_numeric($rawScoreL2)) {
            $val = (float) $rawScoreL2;
            $intVal = (int) round($val);
            $scoreL2 = (abs($val - $intVal) < 0.0001) ? $intVal : round($val, 2);
        }

        $scoreL3 = null;
        if (isset($row->l3_numerical) && is_numeric($row->l3_numerical)) {
            $val = (float) $row->l3_numerical;
            $intVal = (int) round($val);
            $scoreL3 = (abs($val - $intVal) < 0.0001) ? $intVal : round($val, 2);
        }

        $totalEngVal = $row->learning_hours ?? ($row->course_learning_hours ?? null);
        $totalEngNumeric = ($totalEngVal !== null && $totalEngVal !== '' && is_numeric($totalEngVal))
            ? (float) $totalEngVal
            : '-';

        // Employee ID: clean without / NIP
        $rawEmp = $row->employee_id ?? ($row->id_emp ?? '-');
        $cleanEmpId = ($rawEmp !== '-') ? preg_replace('/\s*[\/\-]\s*NIP.*$/i', '', trim((string) $rawEmp)) : '-';

        return [
            $cleanEmpId,                                                         // 1. Employee ID
            $row->participant_name ?? ($row->user_name ?? ($row->name ?? '-')),  // 2. Employee Name
            $row->grade ?? '-',                                                  // 3. Grade
            $row->job_position ?? '-',                                           // 4. Job Position
            $row->team ?? '-',                                                   // 5. Team
            $row->department ?? '-',                                             // 6. Department
            $row->company ?? '-',                                                // 7. Company
            $row->course_name ?? ($row->title ?? '-'),                           // 8. Course Title
            $row->sub_course_name ?? '-',                                        // 9. Sub Course Title
            $row->type ?? '-',                                                   // 10. Type
            $row->category ?? '-',                                               // 11. Category
            $row->level ?? '-',                                                  // 12. Level
            $row->batch ?? '-',                                                  // 13. Batch
            $row->main_classification ?? 'Unclassified',                         // 14. Main Classification
            $row->sub_classification ?? '-',                                     // 15. Sub Classification
            $delivMethod,                                                        // 16. Delivery Method
            $subDelivMethod,                                                     // 17. Sub Delivery Method
            $row->provider_name ?? ($row->provider ?? '-'),                      // 18. Provider Name
            $row->venue ?? '-',                                                  // 19. Venue
            $row->instructor ?? '-',                                             // 20. Instructor
            $durationFormatted,                                                  // 21. Course Duration
            $lHoursNumeric,                                                      // 22. Learning Hours
            $progressDecimal,                                                    // 23. % Progress
            $progressStatus,                                                     // 24. Progress Status
            $startDateStr,                                                       // 25. Start Date
            $endDateStr,                                                         // 26. End Date
            $startTimeStr,                                                       // 27. Start Time
            $endTimeStr,                                                         // 28. End Time
            $recordMonthStr,                                                     // 29. Record Month
            $row->requirement ?? 'Mandatory',                                    // 30. Requirement
            $attendanceStr,                                                      // 31. Attendance
            $row->attendance_reason ?? '-',                                      // 32. Attendance Reason
            $hasBond ? 'Yes' : ($row->bond_status ?? 'No'),                      // 33. Bond Status
            $bondDurationMonths !== null ? $bondDurationMonths : '-',            // 34. Bond Duration
            $bondEndDateStr,                                                     // 35. Bond End Date
            $hasBond ? (float) $idr : '-',                                       // 36. Bond Amount
            $scoreL1 !== null ? $scoreL1 : '-',                                  // 37. Score (L1) - Course
            $scoreL2 !== null ? $scoreL2 : '-',                                  // 38. Score (L2)
            $row->l2_verbal ?? ($scoreL2 !== null ? ($scoreL2 >= 70 ? 'Passed' : 'Failed') : '-'), // 39. L2 Status
            $scoreL3 !== null ? $scoreL3 : '-',                                  // 40. Score (L3)
            $row->l3_verbal ?? '-',                                              // 41. L3 Status
            $row->pic ?? '-',                                                    // 42. Person in Charge (PIC)
            $row->certificate ?? '-',                                            // 43. Certificate
            $totalEngNumeric,                                                    // 44. Total Engagement Time
        ];
    }

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
            'U'  => 18,  // Sub Provider
            'V'  => 25,  // Provider Name
            'W'  => 20,  // Venue
            'X'  => 22,  // Instructor
            'Y'  => 16,  // Course Duration
            'Z'  => 22,  // Total Recognized Duration
            'AA' => 16,  // Learning Hours
            'AB' => 22,  // Total Engagement Time
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
        ];
    }

    public function query()
    {
        return RecordOfflineQueryService::buildGlobalQuery($this->filters)
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

        // 5. xl/styles.xml with global center alignment
        $zip->addFile(
            'xl/styles.xml',
            '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            . '<styleSheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main">'
            . '<numFmts count="5">'
            . '<numFmt numFmtId="164" formatCode="&quot;Rp&quot;#,##0"/>'
            . '<numFmt numFmtId="165" formatCode="yyyy-mm-dd"/>'
            . '<numFmt numFmtId="166" formatCode="hh:mm:ss"/>'
            . '<numFmt numFmtId="167" formatCode="0.##&quot; h&quot;"/>'
            . '<numFmt numFmtId="168" formatCode="0.##"/>'
            . '</numFmts>'
            . '<fonts count="3">'
            . '<font><sz val="10"/><name val="Calibri"/></font>'
            . '<font><b/><sz val="10"/><color rgb="FFFFFFFF"/><name val="Calibri"/></font>'
            . '<font><b/><sz val="10"/><name val="Calibri"/></font>'
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
            . '<cellXfs count="12">'
            . '<xf numFmtId="49" fontId="0" fillId="0" borderId="0" xfId="0" applyNumberFormat="1" applyAlignment="1"><alignment horizontal="center" vertical="center"/></xf>'
            . '<xf numFmtId="0" fontId="1" fillId="2" borderId="0" xfId="0" applyFont="1" applyFill="1" applyAlignment="1"><alignment horizontal="center" vertical="center" wrapText="1"/></xf>'
            . '<xf numFmtId="49" fontId="2" fillId="0" borderId="0" xfId="0" applyFont="1" applyNumberFormat="1" applyAlignment="1"><alignment horizontal="center" vertical="center"/></xf>'
            . '<xf numFmtId="0" fontId="0" fillId="0" borderId="0" xfId="0" applyAlignment="1"><alignment horizontal="center" vertical="center"/></xf>'
            . '<xf numFmtId="1" fontId="0" fillId="0" borderId="0" xfId="0" applyNumberFormat="1" applyAlignment="1"><alignment horizontal="center" vertical="center"/></xf>'
            . '<xf numFmtId="2" fontId="0" fillId="0" borderId="0" xfId="0" applyNumberFormat="1" applyAlignment="1"><alignment horizontal="center" vertical="center"/></xf>'
            . '<xf numFmtId="9" fontId="0" fillId="0" borderId="0" xfId="0" applyNumberFormat="1" applyAlignment="1"><alignment horizontal="center" vertical="center"/></xf>'
            . '<xf numFmtId="165" fontId="0" fillId="0" borderId="0" xfId="0" applyNumberFormat="1" applyAlignment="1"><alignment horizontal="center" vertical="center"/></xf>'
            . '<xf numFmtId="166" fontId="0" fillId="0" borderId="0" xfId="0" applyNumberFormat="1" applyAlignment="1"><alignment horizontal="center" vertical="center"/></xf>'
            . '<xf numFmtId="164" fontId="0" fillId="0" borderId="0" xfId="0" applyNumberFormat="1" applyAlignment="1"><alignment horizontal="center" vertical="center"/></xf>'
            . '<xf numFmtId="167" fontId="0" fillId="0" borderId="0" xfId="0" applyNumberFormat="1" applyAlignment="1"><alignment horizontal="center" vertical="center"/></xf>'
            . '<xf numFmtId="168" fontId="0" fillId="0" borderId="0" xfId="0" applyNumberFormat="1" applyAlignment="1"><alignment horizontal="center" vertical="center"/></xf>'
            . '</cellXfs>'
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

        // Header row (Row 1)
        $headers = self::headings();
        fwrite($sfh, '<row r="1" ht="28" customHeight="1">' . "\n");
        foreach ($headers as $i => $h) {
            $coord = Coordinate::stringFromColumnIndex($i + 1) . '1';
            $val = $this->escapeXml($h);
            fwrite($sfh, "<c r=\"{$coord}\" t=\"inlineStr\" s=\"1\"><is><t>{$val}</t></is></c>\n");
        }
        fwrite($sfh, '</row>' . "\n");

        $query = $this->query();
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

            // Duration formatted as HH:MM:SS
            $durationFormatted = '-';
            if (!empty($row->duration_seconds)) {
                $secs = (int) $row->duration_seconds;
                $hours = floor($secs / 3600);
                $mins = floor(($secs % 3600) / 60);
                $seconds = $secs % 60;
                $durationFormatted = sprintf('%02d:%02d:%02d', $hours, $mins, $seconds);
            }

            // Learning Hours (raw numeric float for SUM calculations)
            $rawLh = $row->course_learning_hours ?? ($row->learning_hours ?? null);
            $lHoursNumeric = null;
            if ($rawLh !== null && $rawLh !== '' && is_numeric($rawLh)) {
                $lHoursNumeric = (float) $rawLh;
            }
            $lHoursCell = ($lHoursNumeric !== null) ? ['V', 'lh', $lHoursNumeric, '10'] : ['V', 'str', '-', '0'];

            // Progress & Status
            $progressNum = (float) ($row->progress ?? 0);
            $progressDecimal = round($progressNum / 100, 4);
            $progressStatus = ($progressNum >= 100) ? 'Finished' : (($progressNum > 0) ? 'Ongoing' : 'Not Started');

            // Date & Time split
            $startDateStr = !empty($row->start_date) ? date('Y-m-d', strtotime($row->start_date)) : '-';
            $endDateStr = !empty($row->end_date)
                ? date('Y-m-d', strtotime($row->end_date))
                : (!empty($row->finish_date) ? date('Y-m-d', strtotime($row->finish_date)) : '-');

            $startTimeStr = !empty($row->start_time) ? date('H:i:s', strtotime($row->start_time)) : '-';
            $endTimeStr = !empty($row->end_time) ? date('H:i:s', strtotime($row->end_time)) : '-';

            $recordMonthDate = $row->start_date ?: ($row->end_date ?: ($row->created_at ?? null));
            $recordMonthStr = !empty($recordMonthDate) ? date('F', strtotime($recordMonthDate)) : '-';

            // Attendance: strictly Yes or No
            $rawAtt = strtolower(trim((string) ($row->attendance ?? '')));
            if ($rawAtt !== '') {
                $attendanceStr = in_array($rawAtt, ['1', 'yes', 'present', 'true', 'hadir', 'completed', 'finished'], true) ? 'Yes' : 'No';
            } else {
                $attendanceStr = ($progressNum >= 100 || !empty($row->finish_date)) ? 'Yes' : 'No';
            }

            // Delivery method alignment: strictly Online or Offline
            $rawDeliv = strtolower(trim((string) ($row->delivery_method ?? '')));
            $delivMethod = in_array($rawDeliv, ['online', 'e-learning', 'webinar', 'kp-owl', 'kp owl'], true) ? 'Online' : 'Offline';
            $subDelivMethod = $row->sub_delivery_method ?: 'On-site';

            // Bond amount & duration
            $idr = $row->bond_amount_idr ?? ($row->bond_amount ?? null);
            $hasBond = ($idr !== null && is_numeric($idr) && (float) $idr > 0);

            $bondDurationMonths = null;
            if (!empty($row->bond_duration)) {
                $parsedDur = (int) preg_replace('/\D/', '', (string) $row->bond_duration);
                if ($parsedDur > 0) {
                    $bondDurationMonths = $parsedDur;
                }
            }

            $bondEndDateStr = !empty($row->bond_end_date) ? date('Y-m-d', strtotime($row->bond_end_date)) : '-';

            // Scores: strictly whole integers if whole number
            $scoreL1 = null;
            if (isset($row->l1_program) && is_numeric($row->l1_program)) {
                $val = (float) $row->l1_program;
                $intVal = (int) round($val);
                $scoreL1 = (abs($val - $intVal) < 0.0001) ? $intVal : round($val, 2);
            }

            $scoreL2 = null;
            $rawScoreL2 = $row->assessment_score ?? ($row->l2_numerical ?? null);
            if ($rawScoreL2 !== null && is_numeric($rawScoreL2)) {
                $val = (float) $rawScoreL2;
                $intVal = (int) round($val);
                $scoreL2 = (abs($val - $intVal) < 0.0001) ? $intVal : round($val, 2);
            }

            $scoreL3 = null;
            if (isset($row->l3_numerical) && is_numeric($row->l3_numerical)) {
                $val = (float) $row->l3_numerical;
                $intVal = (int) round($val);
                $scoreL3 = (abs($val - $intVal) < 0.0001) ? $intVal : round($val, 2);
            }

            $totalEngVal = $row->learning_hours ?? ($row->course_learning_hours ?? null);
            $totalEngNumeric = null;
            if ($totalEngVal !== null && $totalEngVal !== '' && is_numeric($totalEngVal)) {
                $totalEngNumeric = (float) $totalEngVal;
            }
            $totalEngCell = ($totalEngNumeric !== null) ? ['AR', 'lh', $totalEngNumeric, '10'] : ['AR', 'str', '-', '0'];

            // Employee ID: raw string/integer only
            $rawEmp = $row->employee_id ?: ($row->id_emp ?? '-');
            $cleanEmpId = ($rawEmp !== '-') ? preg_replace('/\s*[\/\-]\s*NIP.*$/i', '', trim((string) $rawEmp)) : '-';

            $hasCert = (!empty($row->certificate) && $row->certificate !== '-');
            $lHoursCell = ($lHoursNumeric !== null) ? ['AA', 'lh', $lHoursNumeric, '10'] : ['AA', 'str', '-', '0'];
            $totalEngCell = ($lHoursNumeric !== null) ? ['AB', 'lh', $lHoursNumeric, '10'] : ['AB', 'str', '-', '0'];

            $cells = [
                ['A', 'num', $rowCount, '0'],                                                                       // 1. NO
                ['B', 'str', '-', '0'],                                                                           // 2. Source
                ['C', 'str', $cleanEmpId, '0'],                                                                     // 3. Employee ID
                ['D', 'str', $row->participant_name ?: ($row->user_name ?? ($row->name ?? '-')), '0'],             // 4. Employee Name
                ['E', 'str', $row->grade ?: '-', '0'],                                                              // 5. Grade
                ['F', 'str', $row->job_position ?: '-', '0'],                                                       // 6. Job Position
                ['G', 'str', $row->team ?: '-', '0'],                                                               // 7. Team
                ['H', 'str', $row->department ?: '-', '0'],                                                         // 8. Department
                ['I', 'str', $row->company ?: '-', '0'],                                                            // 9. Company
                ['J', 'str', $row->course_name ?: ($row->title ?: '-'), '0'],                                       // 10. Course Title
                ['K', 'str', $row->sub_course_name ?: '-', '0'],                                                    // 11. Sub Course Title
                ['L', 'str', $row->type ?: '-', '0'],                                                               // 12. Type
                ['M', 'str', $row->category ?: '-', '0'],                                                           // 13. Category
                ['N', 'str', $row->level ?: '-', '0'],                                                              // 14. Level
                ['O', 'str', $row->batch ?: '-', '0'],                                                              // 15. Batch
                ['P', 'str', $row->main_classification ?: 'Unclassified', '0'],                                     // 16. Main Classification
                ['Q', 'str', $row->sub_classification ?: '-', '0'],                                                 // 17. Sub Classification
                ['R', 'str', $delivMethod, '0'],                                                                    // 18. Delivery Method
                ['S', 'str', $subDelivMethod, '0'],                                                                 // 19. Sub Delivery Method
                ['T', 'str', $row->provider ?: '-', '0'],                                                         // 20. Provider
                ['U', 'str', $row->sub_provider ?: '-', '0'],                                                     // 21. Sub Provider
                ['V', 'str', $row->provider_name ?: ($row->provider ?: '-'), '0'],                                  // 22. Provider Name
                ['W', 'str', $row->venue ?: '-', '0'],                                                              // 23. Venue
                ['X', 'str', $row->instructor ?: '-', '0'],                                                         // 24. Instructor
                ['Y', 'str', '-', '0'],                                                                           // 25. Course Duration (strictly -)
                ['Z', 'str', '-', '0'],                                                                           // 26. Total Recognized Duration (strictly -)
                $lHoursCell,                                                                                              // 27. Learning Hours ('AA')
                $totalEngCell,                                                                                            // 28. Total Engagement Time ('AB')
                ['AC', 'pct', $progressDecimal, '6'],                                                                // 29. % Progress
                ['AD', 'str', $progressStatus, '0'],                                                                 // 30. Progress Status
                ['AE', $startDateStr !== '-' ? 'date' : 'str', $startDateStr, $startDateStr !== '-' ? '7' : '0'],  // 31. Start Date
                ['AF', $endDateStr !== '-' ? 'date' : 'str', $endDateStr, $endDateStr !== '-' ? '7' : '0'],      // 32. End Date
                ['AG', $startTimeStr !== '-' ? 'time' : 'str', $startTimeStr, $startTimeStr !== '-' ? '8' : '0'],  // 33. Start Time
                ['AH', $endTimeStr !== '-' ? 'time' : 'str', $endTimeStr, $endTimeStr !== '-' ? '8' : '0'],      // 34. End Time
                ['AI', 'str', $recordMonthStr, '0'],                                                                // 35. Record Month
                ['AJ', 'str', $row->requirement ?: 'Mandatory', '0'],                                               // 36. Requirement
                ['AK', 'str', $attendanceStr, '0'],                                                                 // 37. Attendance
                ['AL', 'str', $row->attendance_reason ?: '-', '0'],                                                 // 38. Attendance Reason
                ['AM', 'str', $hasBond ? 'Yes' : ($row->bond_status ?: 'No'), '0'],                                // 39. Bond Status
                $bondDurationMonths !== null ? ['AN', 'int', $bondDurationMonths, '4'] : ['AN', 'str', '-', '0'], // 40. Bond Duration
                ['AO', $bondEndDateStr !== '-' ? 'date' : 'str', $bondEndDateStr, $bondEndDateStr !== '-' ? '7' : '0'], // 41. Bond End Date
                ['AP', $hasBond ? 'curr' : 'str', $hasBond ? (float) $idr : '-', $hasBond ? '9' : '0'],            // 42. Bond Amount
                $scoreL1 !== null ? (is_int($scoreL1) ? ['AQ', 'int', $scoreL1, '4'] : ['AQ', 'float', $scoreL1, '11']) : ['AQ', 'str', '-', '0'], // 43. Score (L1)
                $scoreL2 !== null ? (is_int($scoreL2) ? ['AR', 'int', $scoreL2, '4'] : ['AR', 'float', $scoreL2, '11']) : ['AR', 'str', '-', '0'], // 44. Score (L2)
                ['AS', 'str', $row->l2_verbal ?: ($scoreL2 !== null ? ($scoreL2 >= 70 ? 'Passed' : 'Failed') : '-'), '0'], // 45. L2 Status
                $scoreL3 !== null ? (is_int($scoreL3) ? ['AT', 'int', $scoreL3, '4'] : ['AT', 'float', $scoreL3, '11']) : ['AT', 'str', '-', '0'], // 46. Score (L3)
                ['AU', 'str', $row->l3_verbal ?: '-', '0'],                                                       // 47. L3 Status
                ['AV', 'str', $hasCert ? 'Available' : '-', '0'],                                                // 48. Certificate Availability
                ['AW', 'str', $hasCert ? $row->certificate : '-', '0'],                                            // 49. Certificate Institution
                ['AX', 'str', '-', '0'],                                                                           // 50. Certificate Issued Date
                ['AY', 'str', '-', '0'],                                                                           // 51. Certificate Expired Date
                ['AZ', 'str', $row->pic ?: '-', '0'],                                                             // 52. Person in Charge (PIC)
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
                $xml .= "<c r=\"{$coord}\" t=\"inlineStr\" s=\"{$styleId}\"><is><t>-</t></is></c>";
            } elseif ($type === 'pct') {
                $xml .= "<c r=\"{$coord}\" s=\"{$styleId}\"><v>{$val}</v></c>";
            } elseif ($type === 'lh') {
                $lhVal = (float) $val;
                $xml .= "<c r=\"{$coord}\" s=\"{$styleId}\"><v>{$lhVal}</v></c>";
            } elseif ($type === 'int') {
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
