<?php

namespace App\Exports\Manage;

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

    public static function headings(): array
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
        ini_set('max_execution_time', 0);
        if (function_exists('set_time_limit')) {
            @set_time_limit(0);
        }

        $tempXlsx = tempnam(sys_get_temp_dir(), 'all_record_manage_') . '.xlsx';
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
            . '<sheets><sheet name="All KP OWL Records" sheetId="1" r:id="rId1"/></sheets>'
            . '</workbook>'
        );

        // 5. xl/styles.xml
        $zip->addFile(
            'xl/styles.xml',
            '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            . '<styleSheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main">'
            . '<numFmts count="5">'
            . '<numFmt numFmtId="164" formatCode="&quot;Rp&quot;#,##0"/>'
            . '<numFmt numFmtId="165" formatCode="dd/mm/yyyy"/>'
            . '<numFmt numFmtId="166" formatCode="[hh]:mm:ss"/>'
            . '<numFmt numFmtId="167" formatCode="0.00&quot; h&quot;"/>'
            . '<numFmt numFmtId="168" formatCode="0&quot; Months&quot;"/>'
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
            . '<cellXfs count="18">'
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
            . '<xf numFmtId="166" fontId="2" fillId="3" borderId="0" xfId="0" applyFont="1" applyFill="1" applyNumberFormat="1" applyAlignment="1"><alignment horizontal="center" vertical="center"/></xf>'
            . '<xf numFmtId="2" fontId="2" fillId="3" borderId="0" xfId="0" applyFont="1" applyFill="1" applyNumberFormat="1" applyAlignment="1"><alignment horizontal="center" vertical="center"/></xf>'
            . '<xf numFmtId="168" fontId="0" fillId="0" borderId="0" xfId="0" applyNumberFormat="1" applyAlignment="1"><alignment horizontal="center" vertical="center"/></xf>'
            . '<xf numFmtId="168" fontId="2" fillId="3" borderId="0" xfId="0" applyFont="1" applyFill="1" applyNumberFormat="1" applyAlignment="1"><alignment horizontal="center" vertical="center"/></xf>'
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

        $query = $this->query();
        $rowCount = 0;
        $buffer = '';
        $batchSize = 1000;

        $contentCertSettings = DB::table('certificate_settings')
            ->whereNotNull('content_id')
            ->get()
            ->keyBy('content_id');
        $offlineCertSettings = DB::table('certificate_settings')
            ->whereNotNull('offline_training_id')
            ->get()
            ->keyBy('offline_training_id');
        $tpCertSettings = DB::table('certificate_settings')
            ->whereNotNull('trainingplan_id')
            ->get()
            ->keyBy('trainingplan_id');

        $contentUserCerts = DB::table('certificates as c')
            ->leftJoin('certificate_settings as cs', 'cs.id', '=', 'c.certificate_setting_id')
            ->whereNotNull('c.content_user_id')
            ->select([
                'c.content_user_id',
                'cs.issuer_name',
                'cs.institution as cs_institution',
                'cs.issued_date as cs_issued_date',
                'cs.expired_date as cs_expired_date',
                'c.institution as c_institution',
                'c.issued_at',
                'c.issued_date',
                'c.expired_date',
            ])
            ->get()
            ->keyBy('content_user_id');

        $offlineUserCerts = DB::table('certificates as c')
            ->leftJoin('certificate_settings as cs', 'cs.id', '=', 'c.certificate_setting_id')
            ->whereNotNull('c.offline_training_user_id')
            ->select([
                'c.offline_training_user_id',
                'cs.issuer_name',
                'cs.institution as cs_institution',
                'cs.issued_date as cs_issued_date',
                'cs.expired_date as cs_expired_date',
                'c.institution as c_institution',
                'c.issued_at',
                'c.issued_date',
                'c.expired_date',
            ])
            ->get()
            ->keyBy('offline_training_user_id');

        $pdo = DB::connection()->getPdo();
        $wasBuffered = $pdo->getAttribute(\PDO::MYSQL_ATTR_USE_BUFFERED_QUERY);
        $pdo->setAttribute(\PDO::MYSQL_ATTR_USE_BUFFERED_QUERY, false);

        try {
            foreach ($query->cursor() as $row) {
                $rowCount++;
                $r = $rowCount + 1;

                $isPlaylistRow = ($row->type === 'Playlist' || ($row->source_type ?? '') === 'playlist');
                $isOffline = ($row->source === 'offline');
                $parentPlaylistTitle = '-';
                if ($isOffline) {
                    $categoryVal = 'Non KP-OWL';
                } else {
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

                // Course Duration formatted as HH:MM:SS
                $durationFormatted = '-';
                if (!empty($row->duration_seconds)) {
                    $secs = (int) $row->duration_seconds;
                    $hours = floor($secs / 3600);
                    $mins = floor(($secs % 3600) / 60);
                    $seconds = $secs % 60;
                    $durationFormatted = sprintf('%02d:%02d:%02d', $hours, $mins, $seconds);
                }
                $courseSecs = (int) ($row->duration_seconds ?? 0);
                $courseDurVal = ($courseSecs > 0) ? ($courseSecs / 86400.0) : '-';

                // Recognized Duration formatted as HH:MM:SS
                $recognizedFormatted = '-';
                $recognizedDurVal = '-';
                if ($row->source !== 'offline') {
                    if (isset($row->recognized_duration_seconds)) {
                        $rSecs = (int) $row->recognized_duration_seconds;
                    } else {
                        $recognizedService = app(\App\Services\Learning\LearningRecordRecognizedDurationService::class);
                        $rSecs = $recognizedService->resolveFromRow($row);
                    }
                    $recognizedDurVal = ($rSecs >= 0) ? ($rSecs / 86400.0) : 0;
                }

// Total Engagement Duration formatted as HH:MM:SS (Col 51) and Total Engagement Time (LH) (Col 52)
                $engSecs = (int) ($row->afk_duration_seconds ?? 0);
                if ($engSecs <= 0 && $row->source === 'offline') {
                    $engSecs = (int) round(((float) ($row->training_hours ?? ($row->learning_hours ?? 0))) * 3600);
                }
                $eh = floor($engSecs / 3600);
                $em = floor(($engSecs % 3600) / 60);
                $es = $engSecs % 60;
                $totalEngagementDuration = sprintf('%02d:%02d:%02d', $eh, $em, $es);
                $totalEngagementLh = round($engSecs / 3600, 2);
                $totalEngagementVal = ($engSecs >= 0) ? ($engSecs / 86400.0) : 0;

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
                    $subDelivMethod = 'KP OWL';
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

                // Date & Time split with dd/mm/yyyy formatting and empty when missing
                $startDateStr = self::formatDate($row->start_date ?? null);
                $endDateStr = self::formatDate($row->end_date ?: ($row->finish_date ?? null));

                $startTimeStr = !empty($row->start_time) ? date('H:i:s', strtotime($row->start_time)) : '';
                $endTimeStr = !empty($row->end_time) ? date('H:i:s', strtotime($row->end_time)) : '';

                $recordMonthDate = $row->start_date ?: ($row->finish_date ?: ($row->record_date ?? null));
                $recordMonthStr = !empty($recordMonthDate) ? date('F', strtotime($recordMonthDate)) : '-';

                // Attendance: strictly Yes or No
                $attendanceStr = 'No';
                if ($row->attendance !== null && $row->attendance !== '') {
                    $rawAtt = strtolower((string) $row->attendance);
                    $attendanceStr = ((bool) $row->attendance || in_array($rawAtt, ['1', 'yes', 'present', 'true', 'hadir', 'completed', 'finished'], true)) ? 'Yes' : 'No';
                } elseif ($row->source === 'online') {
                    $attendanceStr = ($progressNum >= 100) ? 'Yes' : (($progressNum > 0) ? 'No' : 'No');
                }

                // Bond amount formatting (IDR only)
                $idr = $row->bond_amount_idr ?? $row->bond_amount ?? null;
                $hasBond = ($idr !== null && (float) $idr > 0);
                $bondEndDateStr = $hasBond && !empty($row->bond_end_date) ? self::formatDate($row->bond_end_date) : '';

                // Scores
                $scoreL1 = is_numeric($row->l1_program) ? (float) $row->l1_program : null;
                $scoreL2 = is_numeric($row->assessment_score)
                    ? (int) round((float) $row->assessment_score)
                    : (is_numeric($row->l2_numerical) ? (int) round((float) $row->l2_numerical) : null);
                $scoreL3 = is_numeric($row->l3_numerical) ? (float) $row->l3_numerical : null;

                $textStyle = $isPlaylistRow ? '2' : '0';
                $intStyle = $isPlaylistRow ? '10' : '4';
                $floatStyle = $isPlaylistRow ? '15' : '5';
                $pctStyle = $isPlaylistRow ? '11' : '6';
                $timeStyle = $isPlaylistRow ? '14' : '8';
                $bondDurStyle = $isPlaylistRow ? '17' : '16';

                // Employee ID: raw string/integer only
                $rawEmp = $row->employee_id ?: '-';
                $cleanEmpId = ($rawEmp !== '-') ? preg_replace('/\s*[\/\-]\s*NIP.*$/i', '', trim((string) $rawEmp)) : '-';

                // Certificate Availability, Institution, Issued Date, Expired Date
                $hasCert = false;
                $certInstitution = '';
                $certIssuedDate = '';
                $certExpiredDate = '';

                if ($row->source === 'offline') {
                    if (!empty($row->certificate) && $row->certificate !== '-' && $row->certificate !== 'No' && $row->certificate !== '0') {
                        $hasCert = true;
                        $certInstitution = $row->certificate;
                    }
                    if (!empty($row->certificate_issued_date)) {
                        $hasCert = true;
                        $certIssuedDate = self::formatDate($row->certificate_issued_date);
                    }
                    if (!empty($row->certificate_expired_date)) {
                        $hasCert = true;
                        $certExpiredDate = self::formatDate($row->certificate_expired_date);
                    }
                    $dbCert = !empty($row->source_id) ? $offlineUserCerts->get($row->source_id) : null;
                    if ($dbCert) {
                        $hasCert = true;
                        if ($dbCert->c_institution) $certInstitution = $dbCert->c_institution;
                        elseif ($dbCert->cs_institution) $certInstitution = $dbCert->cs_institution;
                        elseif ($dbCert->issuer_name) $certInstitution = $dbCert->issuer_name;

                        if (empty($certIssuedDate)) {
                            if (!empty($dbCert->issued_date)) $certIssuedDate = self::formatDate($dbCert->issued_date);
                            elseif (!empty($dbCert->cs_issued_date)) $certIssuedDate = self::formatDate($dbCert->cs_issued_date);
                            elseif (!empty($dbCert->issued_at)) $certIssuedDate = self::formatDate($dbCert->issued_at);
                        }
                        if (empty($certExpiredDate)) {
                            if (!empty($dbCert->expired_date)) $certExpiredDate = self::formatDate($dbCert->expired_date);
                            elseif (!empty($dbCert->cs_expired_date)) $certExpiredDate = self::formatDate($dbCert->cs_expired_date);
                        }
                    }
                    if (!$dbCert && !empty($row->offline_training_id)) {
                        $csSetting = $offlineCertSettings->get($row->offline_training_id);
                        if ($csSetting) {
                            $hasCert = true;
                            if (empty($certInstitution)) {
                                if ($csSetting->institution) $certInstitution = $csSetting->institution;
                                elseif ($csSetting->issuer_name) $certInstitution = $csSetting->issuer_name;
                            }
                            if (empty($certIssuedDate) && $csSetting->issued_date) $certIssuedDate = self::formatDate($csSetting->issued_date);
                            if (empty($certExpiredDate) && $csSetting->expired_date) $certExpiredDate = self::formatDate($csSetting->expired_date);
                        }
                    }
                } else {
                    $isFinishedOnline = ($progressNum >= 100 || ($row->status ?? '') === 'Finished' || ($row->finish ?? '') === '1');
                    $dbCert = !empty($row->source_id) ? $contentUserCerts->get($row->source_id) : null;
                    if ($dbCert) {
                        $hasCert = true;
                        $certInstitution = $dbCert->c_institution ?: ($dbCert->cs_institution ?: ($dbCert->issuer_name ?: 'PT Krakatau Posco'));
                        if (!empty($dbCert->issued_date)) $certIssuedDate = self::formatDate($dbCert->issued_date);
                        elseif (!empty($dbCert->cs_issued_date)) $certIssuedDate = self::formatDate($dbCert->cs_issued_date);
                        elseif (!empty($dbCert->issued_at)) $certIssuedDate = self::formatDate($dbCert->issued_at);

                        if (!empty($dbCert->expired_date)) $certExpiredDate = self::formatDate($dbCert->expired_date);
                        elseif (!empty($dbCert->cs_expired_date)) $certExpiredDate = self::formatDate($dbCert->cs_expired_date);
                    }
                    if (!$dbCert && !empty($row->content_id)) {
                        $csSetting = $contentCertSettings->get($row->content_id);
                        if ($csSetting) {
                            $hasCert = true;
                            if ($csSetting->institution) $certInstitution = $csSetting->institution;
                            elseif ($csSetting->issuer_name) $certInstitution = $csSetting->issuer_name;
                            if ($csSetting->issued_date) $certIssuedDate = self::formatDate($csSetting->issued_date);
                            if ($csSetting->expired_date) $certExpiredDate = self::formatDate($csSetting->expired_date);
                        }
                    }
                    if (!$hasCert && $isFinishedOnline) {
                        $hasCert = true;
                        if (empty($certInstitution)) $certInstitution = 'PT Krakatau Posco';
                        if (empty($certIssuedDate)) $certIssuedDate = $endDateStr !== '' ? $endDateStr : ($startDateStr !== '' ? $startDateStr : '');
                    }
                }

                $certAvailability = $hasCert ? 'Available' : '';
                $finalRecordMonth = ($row->source === 'online')
                    ? ($endDateStr !== '' ? date('F', strtotime(str_replace('/', '-', $endDateStr))) : $recordMonthStr)
                    : $recordMonthStr;

                // 52 Canonical Columns: A (No) through AZ (Total Engagement Time (LH))
                $cells = [
                    ['A', 'num', $rowCount, $textStyle],                                        // 1. No
                    ['B', 'str', $cleanEmpId, $textStyle],                                      // 2. Employee ID
                    ['C', 'str', $row->employee_name ?: '-', $textStyle],                       // 3. Employee Name
                    ['D', 'str', $row->grade ?: '-', $textStyle],                               // 4. Grade
                    ['E', 'str', $row->job_position ?: '-', $textStyle],                        // 5. Job Position
                    ['F', 'str', $row->team ?: '-', $textStyle],                                // 6. Team
                    ['G', 'str', $row->department ?: '-', $textStyle],                          // 7. Department
                    ['H', 'str', $row->company ?: '-', $textStyle],                             // 8. Company
                    ['I', 'str', $row->learning_title ?: '-', $textStyle],                      // 9. Course Title
                    ['J', 'str', $row->sub_course_name ?: '-', $textStyle],                     // 10. Sub Course Title
                    ['K', 'str', ($row->source === 'offline' || !$isKpOwl) ? '-' : ($row->type ?: '-'), $textStyle], // 11. Type
                    ['L', 'str', $categoryVal, $textStyle],                                     // 12. Category
                    ['M', 'str', $row->level ?: '-', $textStyle],                               // 13. Level
                    ['N', 'str', $row->batch ?: '-', $textStyle],                               // 14. Batch
                    ['O', 'str', $row->classification ?: 'Unclassified', $textStyle],           // 15. Main Classification
                    ['P', 'str', $row->sub_classification ?: '-', $textStyle],                  // 16. Sub Classification
                    ['Q', 'str', $delivMethod, $textStyle],                                     // 17. Delivery Method
                    ['R', 'str', $subDelivMethod, $textStyle],                                  // 18. Sub Delivery Method
                    ['S', 'str', $row->source === 'online' ? 'Internal' : ($row->provider ?: '-'), $textStyle], // 19. Provider
                    ['T', 'str', $row->source === 'online' ? '-' : ($row->sub_provider ?: '-'), $textStyle],    // 20. Sub Provider
                    ['U', 'str', $row->provider_name ?: ($row->source === 'offline' ? '-' : 'KP-OWL'), $textStyle], // 21. Provider Name
                    ['V', 'str', $row->venue ?: ($row->source === 'offline' ? '-' : 'Online LMS'), $textStyle],     // 22. Venue
                    ['W', 'str', $row->instructor ?: '-', $textStyle],                          // 23. Instructor
                    ['X', 'str', $row->source === 'offline' ? '-' : $durationFormatted, $textStyle], // 24. Course Duration
                    ['Y', 'str', $row->source === 'offline' ? '-' : $recognizedFormatted, $textStyle], // 25. Total Recognized Duration
                    ['Z', $lhNumeric !== null ? 'lh' : 'str', $lhNumeric !== null ? $lhNumeric : '-', $lhNumeric !== null ? $lhStyle : $textStyle], // 26. Learning Hours
                    ['AA', 'pct', $progressDecimal, $pctStyle],                                 // 27. % Progress
                    ['AB', 'str', $progressStatus, $textStyle],                                 // 28. Progress Status
                    ['AC', $startDateStr !== '' ? 'str' : 'blank', $startDateStr, $startDateStr !== '' ? '7' : $textStyle], // 29. Start Date
                    ['AD', $endDateStr !== '' ? 'str' : 'blank', $endDateStr, $endDateStr !== '' ? '7' : $textStyle],       // 30. End Date
                    ['AE', $startTimeStr !== '' ? 'str' : 'blank', $startTimeStr, $startTimeStr !== '' ? '8' : $textStyle], // 31. Start Time
                    ['AF', $endTimeStr !== '' ? 'str' : 'blank', $endTimeStr, $endTimeStr !== '' ? '8' : $textStyle],       // 32. End Time
                    ['AG', 'str', $finalRecordMonth, $textStyle],                               // 33. Record Month
                    ['AH', 'str', $row->requirement ?: 'Mandatory', $textStyle],                 // 34. Requirement
                    ['AI', 'str', $attendanceStr, $textStyle],                                  // 35. Attendance
                    ['AJ', 'str', $row->attendance_reason ?: '', $textStyle],                   // 36. Attendance Reason
                    ['AK', 'str', $row->bond_status ?: 'No', $textStyle],                       // 37. Bond Status
                    ['AL', (!empty($row->bond_duration) && is_numeric($row->bond_duration) && (int)$row->bond_duration > 0) ? 'month' : 'str', (!empty($row->bond_duration) && is_numeric($row->bond_duration) && (int)$row->bond_duration > 0) ? (int)$row->bond_duration : '-', (!empty($row->bond_duration) && is_numeric($row->bond_duration) && (int)$row->bond_duration > 0) ? $bondDurStyle : $textStyle], // 38. Bond Duration
                    ['AM', $bondEndDateStr !== '' ? 'str' : 'blank', $bondEndDateStr, $bondEndDateStr !== '' ? '7' : $textStyle], // 39. Bond End Date
                    ['AN', $hasBond ? 'curr' : 'blank', $hasBond ? (float) $idr : '', $hasBond ? '9' : $textStyle], // 40. Bond Amount
                    ['AO', $scoreL1 !== null ? 'float' : 'blank', $scoreL1 !== null ? $scoreL1 : '', $scoreL1 !== null ? '5' : $textStyle], // 41. Score (L1) - Course
                    ['AP', $scoreL2 !== null ? 'int' : 'blank', $scoreL2 !== null ? $scoreL2 : '', $scoreL2 !== null ? $intStyle : $textStyle], // 42. Score (L2)
                    ['AQ', 'str', $row->l2_verbal ?: ($scoreL2 !== null ? ($scoreL2 >= 70 ? 'Passed' : 'Failed') : ''), $textStyle], // 43. L2 Status
                    ['AR', $scoreL3 !== null ? 'float' : 'blank', $scoreL3 !== null ? $scoreL3 : '', $scoreL3 !== null ? '5' : $textStyle], // 44. Score (L3)
                    ['AS', 'str', $row->l3_verbal ?: '', $textStyle],                           // 45. L3 Status
                    ['AT', 'str', $certAvailability, $textStyle],                               // 46. Certificate Availability
                    ['AU', 'str', $certInstitution, $textStyle],                                // 47. Certificate Institution
                    ['AV', $certIssuedDate !== '' ? 'str' : 'blank', $certIssuedDate, $certIssuedDate !== '' ? '7' : $textStyle], // 48. Certificate Issued Date
                    ['AW', $certExpiredDate !== '' ? 'str' : 'blank', $certExpiredDate, $certExpiredDate !== '' ? '7' : $textStyle], // 49. Certificate Expired Date
                    ['AX', 'str', $row->pic ?: '', $textStyle],                                 // 50. Person in Charge (PIC)
                    ['AY', 'str', $totalEngagementDuration, $textStyle],                        // 51. Total Engagement Time
                    ['AZ', 'lh', $totalEngagementLh, $lhStyle],                                 // 52. Total Engagement Time (LH)
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

            if ($val === null || $val === '' || $val === '-' || $type === 'blank') {
                $xml .= "<c r=\"{$coord}\" t=\"inlineStr\" s=\"{$styleId}\"><is><t>" . ($val === '-' ? '-' : '') . "</t></is></c>";
            } elseif ($type === 'pct') {
                $xml .= "<c r=\"{$coord}\" s=\"{$styleId}\"><v>{$val}</v></c>";
            } elseif ($type === 'lh' || $type === 'hours') {
                $lhVal = (float) $val;
                $xml .= "<c r=\"{$coord}\" s=\"{$styleId}\"><v>{$lhVal}</v></c>";
            } elseif ($type === 'int' || $type === 'month') {
                $intVal = (int) $val;
                $xml .= "<c r=\"{$coord}\" s=\"{$styleId}\"><v>{$intVal}</v></c>";
            } elseif ($type === 'float') {
                $floatVal = number_format((float) $val, 2, '.', '');
                $xml .= "<c r=\"{$coord}\" s=\"{$styleId}\"><v>{$floatVal}</v></c>";
            } elseif ($type === 'curr') {
                $currVal = (float) $val;
                $xml .= "<c r=\"{$coord}\" s=\"{$styleId}\"><v>{$currVal}</v></c>";
            } elseif ($type === 'num') {
                $xml .= "<c r=\"{$coord}\" s=\"{$styleId}\"><v>{$val}</v></c>";
            } elseif ($type === 'dur' || $type === 'time') {
                if (is_numeric($val)) {
                    $numStr = sprintf('%.10F', (float) $val);
                    $numStr = rtrim(rtrim($numStr, '0'), '.');
                    if ($numStr === '') $numStr = '0';
                    $xml .= "<c r=\"{$coord}\" s=\"{$styleId}\"><v>{$numStr}</v></c>";
                } else {
                    $frac = $this->timeToExcelFraction((string) $val);
                    if ($frac !== null) {
                        $numStr = sprintf('%.10F', $frac);
                        $numStr = rtrim(rtrim($numStr, '0'), '.');
                        if ($numStr === '') $numStr = '0';
                        $xml .= "<c r=\"{$coord}\" s=\"{$styleId}\"><v>{$numStr}</v></c>";
                    } else {
                        $esc = $this->escapeXml((string) $val);
                        $xml .= "<c r=\"{$coord}\" t=\"inlineStr\" s=\"{$styleId}\"><is><t>{$esc}</t></is></c>";
                    }
                }
            } elseif ($type === 'date') {
                $days = $this->dateToExcelSerial((string) $val);
                if ($days > 0) {
                    $xml .= "<c r=\"{$coord}\" s=\"{$styleId}\"><v>{$days}</v></c>";
                } else {
                    $esc = $this->escapeXml((string) $val);
                    $xml .= "<c r=\"{$coord}\" t=\"inlineStr\" s=\"{$styleId}\"><is><t>{$esc}</t></is></c>";
                }
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

    protected function dateToExcelSerial(string $dateStr): int
    {
        $ts = strtotime($dateStr);
        if (!$ts || $ts <= 0) return 0;
        return (int) (25569 + ($ts / 86400));
    }
}
