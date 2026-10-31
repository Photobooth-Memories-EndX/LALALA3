<?php

namespace App\Exports\Manage;

use App\Services\Manage\RecordSharedQueryService;
use Carbon\Carbon;
use Illuminate\Contracts\Support\Responsable;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use ZipStream\Option\Archive as ArchiveOptions;
use ZipStream\ZipStream;

class AllRecordExport implements Responsable
{
    protected array $filters = [];
    protected string $fileName;

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

        return "EmployeeAllrecord_{$day}{$month}{$year} at {$time}.xlsx";
    }

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
            'Total Engagement Time (LH)',
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
            'AZ' => 25,  // Total Engagement Time (LH)
        ];
    }

    public function toResponse($request): BinaryFileResponse
    {
        return $this->download();
    }

    public function download(): BinaryFileResponse
    {
        $filePath = $this->generateXlsxFile();

        return response()->download($filePath, $this->fileName, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        ])->deleteFileAfterSend(true);
    }

    public function getFileName(): string
    {
        return $this->fileName;
    }

    public function generateXlsxFile(): string
    {
        ini_set('memory_limit', '2048M');
        ini_set('max_execution_time', 600);
        if (function_exists('set_time_limit')) {
            @set_time_limit(600);
        }

        $tempXlsx = tempnam(sys_get_temp_dir(), 'all_record_') . '.xlsx';
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
            . '<sheets><sheet name="All Learning Records" sheetId="1" r:id="rId1"/></sheets>'
            . '</workbook>'
        );

        // 5. xl/styles.xml with #002B49 dark blue header and bold white text
        $zip->addFile(
            'xl/styles.xml',
            '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            . '<styleSheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main">'
            . '<numFmts count="4">'
            . '<numFmt numFmtId="164" formatCode="&quot;Rp&quot;#,##0"/>'
            . '<numFmt numFmtId="165" formatCode="yyyy-mm-dd"/>'
            . '<numFmt numFmtId="166" formatCode="hh:mm:ss"/>'
            . '<numFmt numFmtId="167" formatCode="0.##&quot; h&quot;"/>'
            . '</numFmts>'
            . '<fonts count="3">'
            . '<font><sz val="10"/><name val="Calibri"/></font>'
            . '<font><b/><sz val="10"/><color rgb="FFFFFFFF"/><name val="Calibri"/></font>'
            . '<font><b/><sz val="10"/><name val="Calibri"/></font>'
            . '</fonts>'
            . '<fills count="4">'
            . '<fill><patternFill patternType="none"/></fill>'
            . '<fill><patternFill patternType="gray125"/></fill>'
            . '<fill><patternFill patternType="solid"><fgColor rgb="FF002B49"/><bgColor rgb="FF002B49"/></patternFill></fill>'
            . '<fill><patternFill patternType="solid"><fgColor rgb="FFEBF3FB"/><bgColor rgb="FFEBF3FB"/></patternFill></fill>'
            . '</fills>'
            . '<borders count="1">'
            . '<border><left/><right/><top/><bottom/><diagonal/></border>'
            . '</borders>'
            . '<cellStyleXfs count="1">'
            . '<xf numFmtId="0" fontId="0" fillId="0" borderId="0"/>'
            . '</cellStyleXfs>'
            . '<cellXfs count="14">'
            . '<xf numFmtId="49" fontId="0" fillId="0" borderId="0" xfId="0" applyNumberFormat="1" applyAlignment="1"><alignment horizontal="center" vertical="center"/></xf>'
            . '<xf numFmtId="0" fontId="1" fillId="2" borderId="0" xfId="0" applyFont="1" applyFill="1" applyAlignment="1"><alignment horizontal="center" vertical="center" wrapText="1"/></xf>'
            . '<xf numFmtId="49" fontId="2" fillId="3" borderId="0" xfId="0" applyFont="1" applyFill="1" applyNumberFormat="1" applyAlignment="1"><alignment horizontal="center" vertical="center"/></xf>'
            . '<xf numFmtId="0" fontId="0" fillId="0" borderId="0" xfId="0" applyAlignment="1"><alignment horizontal="center" vertical="center"/></xf>'
            . '<xf numFmtId="1" fontId="0" fillId="0" borderId="0" xfId="0" applyNumberFormat="1" applyAlignment="1"><alignment horizontal="center" vertical="center"/></xf>'
            . '<xf numFmtId="2" fontId="0" fillId="0" borderId="0" xfId="0" applyNumberFormat="1" applyAlignment="1"><alignment horizontal="center" vertical="center"/></xf>'
            . '<xf numFmtId="9" fontId="0" fillId="0" borderId="0" xfId="0" applyNumberFormat="1" applyAlignment="1"><alignment horizontal="center" vertical="center"/></xf>'
            . '<xf numFmtId="165" fontId="0" fillId="0" borderId="0" xfId="0" applyNumberFormat="1" applyAlignment="1"><alignment horizontal="center" vertical="center"/></xf>'
            . '<xf numFmtId="166" fontId="0" fillId="0" borderId="0" xfId="0" applyNumberFormat="1" applyAlignment="1"><alignment horizontal="center" vertical="center"/></xf>'
            . '<xf numFmtId="164" fontId="0" fillId="0" borderId="0" xfId="0" applyNumberFormat="1" applyAlignment="1"><alignment horizontal="center" vertical="center"/></xf>'
            . '<xf numFmtId="1" fontId="2" fillId="3" borderId="0" xfId="0" applyFont="1" applyFill="1" applyNumberFormat="1" applyAlignment="1"><alignment horizontal="center" vertical="center"/></xf>'
            . '<xf numFmtId="9" fontId="2" fillId="3" borderId="0" xfId="0" applyFont="1" applyFill="1" applyNumberFormat="1" applyAlignment="1"><alignment horizontal="center" vertical="center"/></xf>'
            . '<xf numFmtId="167" fontId="0" fillId="0" borderId="0" xfId="0" applyNumberFormat="1" applyAlignment="1"><alignment horizontal="center" vertical="center"/></xf>'
            . '<xf numFmtId="167" fontId="2" fillId="3" borderId="0" xfId="0" applyFont="1" applyFill="1" applyNumberFormat="1" applyAlignment="1"><alignment horizontal="center" vertical="center"/></xf>'
            . '</cellXfs>'
            . '</styleSheet>'
        );

        // 6. Temporary file stream for sheet1.xml
        $sheetFile = tempnam(sys_get_temp_dir(), 'xlsx_sheet_');
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

        $query = app(RecordSharedQueryService::class)->buildCombinedQuery($this->filters)
            ->orderBy('raw_finish_date', 'desc')
            ->orderBy('record_date', 'desc');

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

                $isPlaylistRow = ($row->type === 'Playlist' || ($row->source_type ?? '') === 'playlist');

                // Course Duration formatted as HH:MM:SS
                $durationFormatted = '-';
                if (!empty($row->duration_seconds)) {
                    $secs = (int) $row->duration_seconds;
                    $hours = floor($secs / 3600);
                    $mins = floor(($secs % 3600) / 60);
                    $seconds = $secs % 60;
                    $durationFormatted = sprintf('%02d:%02d:%02d', $hours, $mins, $seconds);
                }

                // Total Recognized Duration formatted as HH:MM:SS (only for KP OWL, '-' for Non KP OWL)
                $recognizedFormatted = '-';
                if ($row->source !== 'offline') {
                    if (isset($row->recognized_duration_seconds)) {
                        $rSecs = (int) $row->recognized_duration_seconds;
                    } else {
                        $recognizedService = app(\App\Services\Learning\LearningRecordRecognizedDurationService::class);
                        $rSecs = $recognizedService->resolveFromRow($row);
                    }
                    $rHours = floor($rSecs / 3600);
                    $rMins = floor(($rSecs % 3600) / 60);
                    $rSeconds = $rSecs % 60;
                    $recognizedFormatted = sprintf('%02d:%02d:%02d', $rHours, $rMins, $rSeconds);
                }

                // Total Engagement Time (LH) formatted as HH:MM:SS (Column 52!)
                $engSecs = (int) ($row->afk_duration_seconds ?? 0);
                if ($engSecs <= 0 && $row->source === 'offline') {
                    $engSecs = (int) round(((float) ($row->training_hours ?? ($row->learning_hours ?? 0))) * 3600);
                }
                $eh = floor($engSecs / 3600);
                $em = floor(($engSecs % 3600) / 60);
                $es = $engSecs % 60;
                $totalEngagementDuration = sprintf('%02d:%02d:%02d', $eh, $em, $es);

                // Learning Hours
                $lhNumeric = ($row->learning_hours !== null && $row->learning_hours !== '' && is_numeric($row->learning_hours))
                    ? (float) $row->learning_hours
                    : null;
                $lhStyle = $isPlaylistRow ? '13' : '12';

                // Progress & Progress Status
                $progressNum = (float) ($row->progress ?? 0);
                $progressDecimal = round($progressNum / 100, 4);
                $progressStatus = ($progressNum >= 100 || ($row->status ?? '') === 'Finished') ? 'Finished' : (($progressNum > 0) ? 'Ongoing' : 'Not Started');

                // Delivery method alignment
                $sourceRaw = strtolower(trim((string) ($row->source ?? '')));
                $isKpOwl = in_array($sourceRaw, ['online', 'kp owl', 'kp-owl', 'owl'], true);
                $delivMethod = $isKpOwl ? 'Online' : 'Offline';

                if ($isKpOwl) {
                    $subDelivMethod = $row->sub_delivery_method ?: (($row->source_type ?? '') === 'playlist' ? 'Playlist / Path' : 'Self-Paced / LMS');
                } else {
                    $sub = $row->sub_delivery_method ?? $row->training_type ?? $row->type ?? null;
                    if (!$sub || $sub === '-' || strtolower(trim((string) $sub)) === 'offline') {
                        $rawDm = trim((string) ($row->delivery_method ?? ''));
                        if ($rawDm !== '' && strtolower($rawDm) !== 'offline') {
                            $sub = $rawDm;
                        }
                    }
                    $subDelivMethod = $sub ?: '-';
                }

                // Dates & Times
                $startDateStr = !empty($row->start_date) ? date('Y-m-d', strtotime($row->start_date)) : '-';
                $endDateStr = !empty($row->end_date)
                    ? date('Y-m-d', strtotime($row->end_date))
                    : (!empty($row->finish_date) ? date('Y-m-d', strtotime($row->finish_date)) : '-');

                $startTimeStr = !empty($row->start_time) ? date('H:i:s', strtotime($row->start_time)) : '-';
                $endTimeStr = !empty($row->end_time) ? date('H:i:s', strtotime($row->end_time)) : '-';

                $recordMonthDate = $row->start_date ?: ($row->finish_date ?: ($row->record_date ?? null));
                $recordMonthStr = !empty($recordMonthDate) ? date('F', strtotime($recordMonthDate)) : '-';
                $finalRecordMonth = ($row->source === 'online')
                    ? ($endDateStr !== '-' ? date('F', strtotime($endDateStr)) : '-')
                    : $recordMonthStr;

                // Attendance
                $attendanceStr = '-';
                if ($row->attendance !== null && $row->attendance !== '') {
                    $rawAtt = strtolower((string) $row->attendance);
                    $attendanceStr = ((bool) $row->attendance || in_array($rawAtt, ['1', 'yes', 'present', 'true', 'hadir'], true)) ? 'Yes' : 'No';
                } elseif ($row->source === 'online') {
                    $attendanceStr = ($progressNum >= 100) ? 'Yes' : (($progressNum > 0) ? 'No' : '-');
                }

                // Service Bond
                $idr = $row->bond_amount_idr ?? $row->bond_amount ?? null;
                $hasBond = ($idr !== null && (float) $idr > 0);

                // Scores
                $scoreL1 = is_numeric($row->l1_program) ? (float) $row->l1_program : null;
                $scoreL2 = is_numeric($row->assessment_score)
                    ? (int) round((float) $row->assessment_score)
                    : (is_numeric($row->l2_numerical) ? (int) round((float) $row->l2_numerical) : null);
                $scoreL3 = is_numeric($row->l3_numerical) ? (float) $row->l3_numerical : null;

                $textStyle = $isPlaylistRow ? '2' : '0';
                $intStyle = $isPlaylistRow ? '10' : '4';
                $pctStyle = $isPlaylistRow ? '11' : '6';

                // Employee ID cleaning
                $rawEmp = $row->employee_id ?: '-';
                $cleanEmpId = ($rawEmp !== '-') ? preg_replace('/\s*[\/\-]\s*NIP.*$/i', '', trim((string) $rawEmp)) : '-';

                // Certificate details
                $hasCert = false;
                $certInstitution = '-';
                $certIssuedDate = '-';
                $certExpiredDate = '-';

                if ($row->source === 'offline') {
                    if (!empty($row->certificate) && $row->certificate !== '-') {
                        $hasCert = true;
                        $certInstitution = $row->certificate;
                        $certIssuedDate = $endDateStr !== '-' ? $endDateStr : ($startDateStr !== '-' ? $startDateStr : '-');
                    }
                } else {
                    $isFinishedOnline = ($progressNum >= 100 || ($row->status ?? '') === 'Finished' || ($row->finish ?? '') === '1');
                    if ($isFinishedOnline) {
                        $hasCert = true;
                        $certInstitution = 'PT Krakatau Posco';
                        $certIssuedDate = $endDateStr !== '-' ? $endDateStr : ($startDateStr !== '-' ? $startDateStr : '-');
                    }
                }

                $certAvailability = $hasCert ? 'Available' : '-';

                // EXACT 52 COLUMNS FROM SPREADSHEET SPECIFICATION
                $cells = [
                    ['A',  'num',   $rowCount, $textStyle],                                                                                              // 1. No
                    ['B',  'str',   $row->source === 'offline' ? 'Non KP OWL' : 'KP OWL', $textStyle],                                                  // 2. Source
                    ['C',  'str',   $cleanEmpId, $textStyle],                                                                                           // 3. Employee ID
                    ['D',  'str',   $row->employee_name ?: '-', $textStyle],                                                                            // 4. Employee Name
                    ['E',  'str',   $row->grade ?: '-', $textStyle],                                                                                    // 5. Grade
                    ['F',  'str',   $row->job_position ?: '-', $textStyle],                                                                             // 6. Job Position
                    ['G',  'str',   $row->team ?: '-', $textStyle],                                                                                     // 7. Team
                    ['H',  'str',   $row->department ?: '-', $textStyle],                                                                               // 8. Department
                    ['I',  'str',   $row->company ?: '-', $textStyle],                                                                                  // 9. Company
                    ['J',  'str',   $row->learning_title ?: '-', $textStyle],                                                                           // 10. Course Title
                    ['K',  'str',   $row->sub_course_name ?: '-', $textStyle],                                                                          // 11. Sub Course Title
                    ['L',  'str',   $row->type ?: '-', $textStyle],                                                                                     // 12. Type
                    ['M',  'str',   $row->category ?: '-', $textStyle],                                                                                 // 13. Category
                    ['N',  'str',   $row->level ?: '-', $textStyle],                                                                                    // 14. Level
                    ['O',  'str',   $row->batch ?: '-', $textStyle],                                                                                    // 15. Batch
                    ['P',  'str',   $row->classification ?: 'Unclassified', $textStyle],                                                                // 16. Main Classification
                    ['Q',  'str',   $row->sub_classification ?: '-', $textStyle],                                                                       // 17. Sub Classification
                    ['R',  'str',   $delivMethod, $textStyle],                                                                                          // 18. Delivery Method
                    ['S',  'str',   $subDelivMethod, $textStyle],                                                                                       // 19. Sub Delivery Method
                    ['T',  'str',   $row->source === 'online' ? 'Internal' : ($row->provider ?: '-'), $textStyle],                                     // 20. Provider
                    ['U',  'str',   $row->source === 'online' ? '-' : ($row->sub_provider ?: '-'), $textStyle],                                        // 21. Sub Provider
                    ['V',  'str',   $row->provider_name ?: ($row->source === 'offline' ? '-' : 'KP-OWL'), $textStyle],                                 // 22. Provider Name
                    ['W',  'str',   $row->venue ?: ($row->source === 'offline' ? '-' : 'Online LMS'), $textStyle],                                     // 23. Venue
                    ['X',  'str',   $row->instructor ?: '-', $textStyle],                                                                               // 24. Instructor
                    ['Y',  'str',   $durationFormatted, $textStyle],                                                                                    // 25. Course Duration
                    ['Z',  'str',   $row->source === 'offline' ? '-' : $recognizedFormatted, $textStyle],                                              // 26. Total Recognized Duration
                    ['AA', $lhNumeric !== null ? 'lh' : 'str', $lhNumeric !== null ? $lhNumeric : '-', $lhNumeric !== null ? $lhStyle : $textStyle], // 27. Learning Hours
                    ['AB', 'pct',   $progressDecimal, $pctStyle],                                                                                       // 28. % Progress
                    ['AC', 'str',   $progressStatus, $textStyle],                                                                                       // 29. Progress Status
                    ['AD', $startDateStr !== '-' ? 'date' : 'str', $startDateStr, $startDateStr !== '-' ? '7' : $textStyle],                          // 30. Start Date
                    ['AE', $endDateStr !== '-' ? 'date' : 'str', $endDateStr, $endDateStr !== '-' ? '7' : $textStyle],                              // 31. End Date
                    ['AF', $startTimeStr !== '-' ? 'time' : 'str', $startTimeStr, $startTimeStr !== '-' ? '8' : $textStyle],                          // 32. Start Time
                    ['AG', $endTimeStr !== '-' ? 'time' : 'str', $endTimeStr, $endTimeStr !== '-' ? '8' : $textStyle],                              // 33. End Time
                    ['AH', 'str',   $finalRecordMonth, $textStyle],                                                                                     // 34. Record Month
                    ['AI', 'str',   $row->requirement ?: 'Mandatory', $textStyle],                                                                      // 35. Requirement
                    ['AJ', 'str',   $attendanceStr, $textStyle],                                                                                        // 36. Attendance
                    ['AK', 'str',   $row->attendance_reason ?: '-', $textStyle],                                                                        // 37. Attendance Reason
                    ['AL', 'str',   $row->bond_status ?: 'No', $textStyle],                                                                             // 38. Bond Status
                    ['AM', 'str',   !empty($row->bond_duration) ? $row->bond_duration . ' Months' : '-', $textStyle],                                  // 39. Bond Duration
                    ['AN', !empty($row->bond_end_date) ? 'date' : 'str', !empty($row->bond_end_date) ? date('Y-m-d', strtotime($row->bond_end_date)) : '-', !empty($row->bond_end_date) ? '7' : $textStyle], // 40. Bond End Date
                    ['AO', $hasBond ? 'curr' : 'str', $hasBond ? (float) $idr : '-', $hasBond ? '9' : $textStyle],                                     // 41. Bond Amount
                    ['AP', $scoreL1 !== null ? 'float' : 'str', $scoreL1 !== null ? $scoreL1 : '-', $scoreL1 !== null ? '5' : $textStyle],             // 42. Score (L1) - Course
                    ['AQ', $scoreL2 !== null ? 'int' : 'str', $scoreL2 !== null ? $scoreL2 : '-', $scoreL2 !== null ? $intStyle : $textStyle],        // 43. Score (L2)
                    ['AR', 'str',   $row->l2_verbal ?: ($scoreL2 !== null ? ($scoreL2 >= 70 ? 'Passed' : 'Failed') : '-'), $textStyle],                 // 44. L2 Status
                    ['AS', $scoreL3 !== null ? 'float' : 'str', $scoreL3 !== null ? $scoreL3 : '-', $scoreL3 !== null ? '5' : $textStyle],             // 45. Score (L3)
                    ['AT', 'str',   $row->l3_verbal ?: '-', $textStyle],                                                                                // 46. L3 Status
                    ['AU', 'str',   $certAvailability, $textStyle],                                                                                     // 47. Certificate Availability
                    ['AV', 'str',   $certInstitution, $textStyle],                                                                                      // 48. Certificate Institution
                    ['AW', $certIssuedDate !== '-' ? 'date' : 'str', $certIssuedDate, $certIssuedDate !== '-' ? '7' : $textStyle],                    // 49. Certificate Issued Date
                    ['AX', $certExpiredDate !== '-' ? 'date' : 'str', $certExpiredDate, $certExpiredDate !== '-' ? '7' : $textStyle],                  // 50. Certificate Expired Date
                    ['AY', 'str',   $row->pic ?: '-', $textStyle],                                                                                      // 51. Person in Charge (PIC)
                    ['AZ', 'str',   $totalEngagementDuration, $textStyle],                                                                              // 52. Total Engagement Time (LH)
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

    protected function buildXmlRow(int $rowNum, array $cells): string
    {
        $xml = "<row r=\"{$rowNum}\" ht=\"20\" customHeight=\"1\">\n";
        foreach ($cells as $c) {
            [$colLetter, $type, $val, $style] = $c;
            $coord = "{$colLetter}{$rowNum}";

            if ($val === '-' || $val === null || $val === '') {
                $xml .= "<c r=\"{$coord}\" t=\"inlineStr\" s=\"{$style}\"><is><t>-</t></is></c>\n";
            } elseif ($type === 'num' || $type === 'int' || $type === 'float' || $type === 'lh' || $type === 'pct' || $type === 'curr') {
                $numStr = (string) $val;
                $xml .= "<c r=\"{$coord}\" s=\"{$style}\"><v>{$numStr}</v></c>\n";
            } elseif ($type === 'date') {
                $days = $this->dateToExcelSerial((string) $val);
                if ($days > 0) {
                    $xml .= "<c r=\"{$coord}\" s=\"{$style}\"><v>{$days}</v></c>\n";
                } else {
                    $esc = $this->escapeXml((string) $val);
                    $xml .= "<c r=\"{$coord}\" t=\"inlineStr\" s=\"{$style}\"><is><t>{$esc}</t></is></c>\n";
                }
            } elseif ($type === 'time') {
                $esc = $this->escapeXml((string) $val);
                $xml .= "<c r=\"{$coord}\" t=\"inlineStr\" s=\"{$style}\"><is><t>{$esc}</t></is></c>\n";
            } else {
                $esc = $this->escapeXml((string) $val);
                $xml .= "<c r=\"{$coord}\" t=\"inlineStr\" s=\"{$style}\"><is><t>{$esc}</t></is></c>\n";
            }
        }
        $xml .= "</row>\n";

        return $xml;
    }

    protected function dateToExcelSerial(string $dateStr): int
    {
        $ts = strtotime($dateStr);
        if (!$ts) return 0;

        return (int) round(($ts - strtotime('1899-12-30')) / 86400);
    }

    protected function escapeXml(string $val): string
    {
        return htmlspecialchars($val, ENT_XML1 | ENT_COMPAT, 'UTF-8');
    }
}
