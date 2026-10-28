<?php

namespace App\Exports;

use App\Models\OfflineTraining;
use App\Services\Record\RecordAllCombinedService;
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
            'Person in Charge (PIC)',
            'Certificate',
            'Total Engagement Time',
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
            'T'  => 25,  // Provider Name
            'U'  => 20,  // Venue
            'V'  => 22,  // Instructor
            'W'  => 16,  // Course Duration
            'X'  => 22,  // Total Recognized Duration
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
            'AL' => 15,  // Bond End Date
            'AM' => 18,  // Bond Amount
            'AN' => 20,  // Score (L1) - Course
            'AO' => 14,  // Score (L2)
            'AP' => 14,  // L2 Status
            'AQ' => 14,  // Score (L3)
            'AR' => 14,  // L3 Status
            'AS' => 22,  // Person in Charge (PIC)
            'AT' => 14,  // Certificate
            'AU' => 25,  // Total Engagement Time
        ];
    }

    public function query()
    {
        $service = app(RecordAllCombinedService::class);

        return $service->buildCombinedQuery($this->filters)
            ->orderBy('raw_finish_date', 'desc')
            ->orderBy('record_date', 'desc');
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
            . '<sheets><sheet name="All KP OWL Records" sheetId="1" r:id="rId1"/></sheets>'
            . '</workbook>'
        );

        // 5. xl/styles.xml with bold white text on #002B49 header and #EBF3FB playlist highlight
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

        // Header row
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

                $isPlaylistRow = ($row->type === 'Playlist' || ($row->source_type ?? '') === 'playlist');

                // Duration formatted as HH:MM:SS
                $durationFormatted = '-';
                if (!empty($row->duration_seconds)) {
                    $secs = (int) $row->duration_seconds;
                    $hours = floor($secs / 3600);
                    $mins = floor(($secs % 3600) / 60);
                    $seconds = $secs % 60;
                    $durationFormatted = sprintf('%02d:%02d:%02d', $hours, $mins, $seconds);
                }

                // Recognized Duration formatted as HH:MM:SS
                $recognizedFormatted = '-';
                if ($row->source !== 'offline') {
                    $rSecs = (int) ($row->recognized_duration_seconds ?? 0);
                    $rHours = floor($rSecs / 3600);
                    $rMins = floor(($rSecs % 3600) / 60);
                    $rSeconds = $rSecs % 60;
                    $recognizedFormatted = sprintf('%02d:%02d:%02d', $rHours, $rMins, $rSeconds);
                }

                // Learning Hours: raw numeric float for SUM calculations
                $lhNumeric = ($row->learning_hours !== null && $row->learning_hours !== '' && is_numeric($row->learning_hours))
                    ? (float) $row->learning_hours
                    : null;
                $lhStyle = $isPlaylistRow ? '13' : '12';

                // Progress & Status
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

                // Date & Time split
                $startDateStr = !empty($row->start_date) ? date('Y-m-d', strtotime($row->start_date)) : '-';
                $endDateStr = !empty($row->end_date)
                    ? date('Y-m-d', strtotime($row->end_date))
                    : (!empty($row->finish_date) ? date('Y-m-d', strtotime($row->finish_date)) : '-');

                $startTimeStr = !empty($row->start_time) ? date('H:i:s', strtotime($row->start_time)) : '-';
                $endTimeStr = !empty($row->end_time) ? date('H:i:s', strtotime($row->end_time)) : '-';

                $recordMonthDate = $row->start_date ?: ($row->finish_date ?: ($row->record_date ?? null));
                $recordMonthStr = !empty($recordMonthDate) ? date('F', strtotime($recordMonthDate)) : '-';

                // Attendance: strictly Yes or No
                $attendanceStr = '-';
                if ($row->attendance !== null && $row->attendance !== '') {
                    $rawAtt = strtolower((string) $row->attendance);
                    $attendanceStr = ((bool) $row->attendance || in_array($rawAtt, ['1', 'yes', 'present', 'true'], true)) ? 'Yes' : 'No';
                } elseif ($row->source === 'online') {
                    $attendanceStr = ($progressNum >= 100) ? 'Yes' : (($progressNum > 0) ? 'No' : '-');
                }

                // Bond amount formatting (IDR only)
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

                // Employee ID: raw string/integer only
                $rawEmp = $row->employee_id ?: '-';
                $cleanEmpId = ($rawEmp !== '-') ? preg_replace('/\s*[\/\-]\s*NIP.*$/i', '', trim((string) $rawEmp)) : '-';

                // 47 columns
                $cells = [
                    ['A', 'num', $rowCount, $textStyle],
                    ['B', 'str', $row->source === 'offline' ? 'Non KP OWL' : 'KP OWL', $textStyle],
                    ['C', 'str', $cleanEmpId, $textStyle],
                    ['D', 'str', $row->employee_name ?: '-', $textStyle],
                    ['E', 'str', $row->grade ?: '-', $textStyle],
                    ['F', 'str', $row->job_position ?: '-', $textStyle],
                    ['G', 'str', $row->team ?: '-', $textStyle],
                    ['H', 'str', $row->department ?: '-', $textStyle],
                    ['I', 'str', $row->company ?: '-', $textStyle],
                    ['J', 'str', $row->learning_title ?: '-', $textStyle],
                    ['K', 'str', $row->sub_course_name ?: '-', $textStyle],
                    ['L', 'str', $row->type ?: '-', $textStyle],
                    ['M', 'str', $row->category ?: '-', $textStyle],
                    ['N', 'str', $row->level ?: '-', $textStyle],
                    ['O', 'str', $row->batch ?: '-', $textStyle],
                    ['P', 'str', $row->classification ?: 'Unclassified', $textStyle],
                    ['Q', 'str', $row->sub_classification ?: '-', $textStyle],
                    ['R', 'str', $delivMethod, $textStyle],
                    ['S', 'str', $subDelivMethod, $textStyle],
                    ['T', 'str', $row->provider_name ?: ($row->source === 'offline' ? '-' : 'KP-OWL'), $textStyle],
                    ['U', 'str', $row->venue ?: ($row->source === 'offline' ? '-' : 'KP-OWL'), $textStyle],
                    ['V', 'str', $row->instructor ?: '-', $textStyle],
                    ['W', 'str', $durationFormatted, $textStyle], // Course Duration
                    ['X', 'str', $recognizedFormatted, $textStyle], // Total Recognized Duration
                    ['Y', $lhNumeric !== null ? 'lh' : 'str', $lhNumeric !== null ? $lhNumeric : '-', $lhNumeric !== null ? $lhStyle : $textStyle], // Learning Hours
                    ['Z', 'pct', $progressDecimal, $pctStyle],
                    ['AA', 'str', $progressStatus, $textStyle],
                    ['AB', $startDateStr !== '-' ? 'date' : 'str', $startDateStr, $startDateStr !== '-' ? '7' : $textStyle],
                    ['AC', $endDateStr !== '-' ? 'date' : 'str', $endDateStr, $endDateStr !== '-' ? '7' : $textStyle],
                    ['AD', $startTimeStr !== '-' ? 'time' : 'str', $startTimeStr, $startTimeStr !== '-' ? '8' : $textStyle],
                    ['AE', $endTimeStr !== '-' ? 'time' : 'str', $endTimeStr, $endTimeStr !== '-' ? '8' : $textStyle],
                    ['AF', 'str', $recordMonthStr, $textStyle],
                    ['AG', 'str', $row->requirement ?: 'Mandatory', $textStyle],
                    ['AH', 'str', $attendanceStr, $textStyle],
                    ['AI', 'str', $row->attendance_reason ?: '-', $textStyle],
                    ['AJ', 'str', $row->bond_status ?: 'No', $textStyle],
                    ['AK', 'str', !empty($row->bond_duration) ? $row->bond_duration . ' Months' : '-', $textStyle],
                    ['AL', !empty($row->bond_end_date) ? 'date' : 'str', !empty($row->bond_end_date) ? date('Y-m-d', strtotime($row->bond_end_date)) : '-', !empty($row->bond_end_date) ? '7' : $textStyle],
                    ['AM', $hasBond ? 'curr' : 'str', $hasBond ? (float) $idr : '-', $hasBond ? '9' : $textStyle],
                    ['AN', $scoreL1 !== null ? 'float' : 'str', $scoreL1 !== null ? $scoreL1 : '-', $scoreL1 !== null ? '5' : $textStyle],
                    ['AO', $scoreL2 !== null ? 'int' : 'str', $scoreL2 !== null ? $scoreL2 : '-', $scoreL2 !== null ? $intStyle : $textStyle],
                    ['AP', 'str', $row->l2_verbal ?: ($scoreL2 !== null ? ($scoreL2 >= 70 ? 'Passed' : 'Failed') : '-'), $textStyle],
                    ['AQ', $scoreL3 !== null ? 'float' : 'str', $scoreL3 !== null ? $scoreL3 : '-', $scoreL3 !== null ? '5' : $textStyle],
                    ['AR', 'str', $row->l3_verbal ?: '-', $textStyle],
                    ['AS', 'str', $row->pic ?: '-', $textStyle],
                    ['AT', 'str', $row->certificate ?: '-', $textStyle],
                    ['AU', $lhNumeric !== null ? 'lh' : 'str', $lhNumeric !== null ? $lhNumeric : '-', $lhNumeric !== null ? $lhStyle : $textStyle], // Total Engagement Time
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
            } elseif ($type === 'lh' || $type === 'hours') {
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
