<?php

namespace App\Exports\Manage;

use App\Services\Manage\RecordSharedQueryService;
use Carbon\Carbon;
use Illuminate\Contracts\Support\Responsable;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use ZipStream\Option\Archive as ArchiveOptions;
use ZipStream\ZipStream;

class ClassificationRecordExport implements Responsable
{
    protected string $classification;
    protected array $resolvedClassification;
    protected array $filters = [];
    protected string $fileName;

    public function __construct(string $classification, $filters = [], ?string $fileName = null)
    {
        $this->classification = $classification;
        $sharedService = app(RecordSharedQueryService::class);
        $this->resolvedClassification = $sharedService->resolveClassification($classification, false);

        $this->filters = $filters instanceof Request ? $filters->all() : (array) $filters;
        $this->filters['classification'] = $this->resolvedClassification['is_unclassified'] ? 'Unclassified' : $this->resolvedClassification['name'];

        $this->fileName = $fileName ?? self::generateFileName($this->resolvedClassification['slug']);
    }

    public static function generateFileName(string $slug): string
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

        return "EmployeeClassification_{$slug}_{$day}{$month}{$year} at {$time}.xlsx";
    }

    public static function headings(): array
    {
        return AllRecordExport::headings();
    }

    public static function columnWidths(): array
    {
        return AllRecordExport::columnWidths();
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

        $tempXlsx = tempnam(sys_get_temp_dir(), 'class_record_') . '.xlsx';
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

        // 4. xl/workbook.xml (Sheet name max 31 chars)
        $sheetTitle = mb_substr($this->resolvedClassification['name'] . ' Records', 0, 31);
        $sheetTitleXml = $this->escapeXml($sheetTitle);
        $zip->addFile(
            'xl/workbook.xml',
            '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            . '<workbook xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main" xmlns:r="http://schemas.openxmlformats.org/officeDocument/2006/relationships">'
            . '<sheets><sheet name="' . $sheetTitleXml . '" sheetId="1" r:id="rId1"/></sheets>'
            . '</workbook>'
        );

        // 5. xl/styles.xml
        $zip->addFile(
            'xl/styles.xml',
            '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            . '<styleSheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main">'
            . '<numFmts count="5">'
            . '<numFmt numFmtId="164" formatCode="&quot;Rp&quot;#,##0"/>'
            . '<numFmt numFmtId="165" formatCode="yyyy-mm-dd"/>'
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

        // 6. Sheet stream
        $sheetFile = tempnam(sys_get_temp_dir(), 'xlsx_class_sheet_');
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

        $query = app(RecordSharedQueryService::class)->buildClassificationQuery($this->classification, $this->filters)
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

                // Course Duration as Excel day fraction (numeric) so Excel can SUM natively
                $courseSecs = (int) ($row->duration_seconds ?? 0);
                $courseDurVal = ($courseSecs > 0) ? ($courseSecs / 86400.0) : '-';

                // Total Recognized Duration as Excel day fraction (only for KP OWL, '-' for Non KP OWL)
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

                // Total Engagement Time (LH) as Excel day fraction (Column 52!)
                $engSecs = (int) ($row->afk_duration_seconds ?? 0);
                if ($engSecs <= 0 && $row->source === 'offline') {
                    $engSecs = (int) round(((float) ($row->training_hours ?? ($row->learning_hours ?? 0))) * 3600);
                }
                $totalEngagementVal = ($engSecs >= 0) ? ($engSecs / 86400.0) : 0;

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
                $floatStyle = $isPlaylistRow ? '15' : '5';
                $pctStyle = $isPlaylistRow ? '11' : '6';
                $timeStyle = $isPlaylistRow ? '14' : '8';
                $bondDurStyle = $isPlaylistRow ? '17' : '16';

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

                // EXACT 53 COLUMNS FROM SPREADSHEET SPECIFICATION
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
                    ['K',  'str',   $parentPlaylistTitle, $textStyle],                                                                                 // 11. Playlist
                    ['L',  'str',   $row->sub_course_name ?: '-', $textStyle],                                                                          // 12. Sub Course Title
                    ['M',  'str',   ($row->source === 'offline' || !$isKpOwl) ? '-' : ($row->type ?: '-'), $textStyle],                                // 13. Type
                    ['N',  'str',   $categoryVal, $textStyle],                                                                                          // 14. Category
                    ['O',  'str',   $row->level ?: '-', $textStyle],                                                                                    // 15. Level
                    ['P',  'str',   $row->batch ?: '-', $textStyle],                                                                                    // 16. Batch
                    ['Q',  'str',   $row->classification ?: 'Unclassified', $textStyle],                                                                // 17. Main Classification
                    ['R',  'str',   $row->sub_classification ?: '-', $textStyle],                                                                      // 18. Sub Classification
                    ['S',  'str',   $delivMethod, $textStyle],                                                                                          // 19. Delivery Method
                    ['T',  'str',   $subDelivMethod, $textStyle],                                                                                       // 20. Sub Delivery Method
                    ['U',  'str',   $row->source === 'online' ? 'Internal' : ($row->provider ?: '-'), $textStyle],                                     // 21. Provider
                    ['V',  'str',   $row->source === 'online' ? '-' : ($row->sub_provider ?: '-'), $textStyle],                                        // 22. Sub Provider
                    ['W',  'str',   $row->provider_name ?: ($row->source === 'offline' ? '-' : 'KP-OWL'), $textStyle],                                 // 23. Provider Name
                    ['X',  'str',   $row->venue ?: ($row->source === 'offline' ? '-' : 'Online LMS'), $textStyle],                                     // 24. Venue
                    ['Y',  'str',   $row->instructor ?: '-', $textStyle],                                                                               // 25. Instructor
                    ['Z',  is_numeric($courseDurVal) ? 'dur' : 'str', $courseDurVal, is_numeric($courseDurVal) ? $timeStyle : $textStyle],            // 26. Course Duration
                    ['AA', is_numeric($recognizedDurVal) ? 'dur' : 'str', $recognizedDurVal, is_numeric($recognizedDurVal) ? $timeStyle : $textStyle],    // 27. Total Recognized Duration
                    ['AB', $lhNumeric !== null ? 'lh' : 'str', $lhNumeric !== null ? $lhNumeric : '-', $lhNumeric !== null ? $lhStyle : $textStyle], // 28. Learning Hours
                    ['AC', 'pct',   $progressDecimal, $pctStyle],                                                                                       // 29. % Progress
                    ['AD', 'str',   $progressStatus, $textStyle],                                                                                       // 30. Progress Status
                    ['AE', $startDateStr !== '-' ? 'date' : 'str', $startDateStr, $startDateStr !== '-' ? '7' : $textStyle],                          // 31. Start Date
                    ['AF', $endDateStr !== '-' ? 'date' : 'str', $endDateStr, $endDateStr !== '-' ? '7' : $textStyle],                              // 32. End Date
                    ['AG', $startTimeStr !== '-' ? 'dur' : 'str', $startTimeStr, $startTimeStr !== '-' ? $timeStyle : $textStyle],                    // 33. Start Time
                    ['AH', $endTimeStr !== '-' ? 'dur' : 'str', $endTimeStr, $endTimeStr !== '-' ? $timeStyle : $textStyle],                        // 34. End Time
                    ['AI', 'str',   $finalRecordMonth, $textStyle],                                                                                     // 35. Record Month
                    ['AJ', 'str',   $row->requirement ?: 'Mandatory', $textStyle],                                                                      // 36. Requirement
                    ['AK', 'str',   $attendanceStr, $textStyle],                                                                                        // 37. Attendance
                    ['AL', 'str',   $row->attendance_reason ?: '-', $textStyle],                                                                        // 38. Attendance Reason
                    ['AM', 'str',   $row->bond_status ?: 'No', $textStyle],                                                                             // 39. Bond Status
                    ['AN', (!empty($row->bond_duration) && is_numeric($row->bond_duration) && (int)$row->bond_duration > 0) ? 'month' : 'str', (!empty($row->bond_duration) && is_numeric($row->bond_duration) && (int)$row->bond_duration > 0) ? (int)$row->bond_duration : '-', (!empty($row->bond_duration) && is_numeric($row->bond_duration) && (int)$row->bond_duration > 0) ? $bondDurStyle : $textStyle], // 40. Bond Duration
                    ['AO', !empty($row->bond_end_date) ? 'date' : 'str', !empty($row->bond_end_date) ? date('Y-m-d', strtotime($row->bond_end_date)) : '-', !empty($row->bond_end_date) ? '7' : $textStyle], // 41. Bond End Date
                    ['AP', $hasBond ? 'curr' : 'str', $hasBond ? (float) $idr : '-', $hasBond ? '9' : $textStyle],                                     // 42. Bond Amount
                    ['AQ', $scoreL1 !== null ? 'float' : 'str', $scoreL1 !== null ? $scoreL1 : '-', $scoreL1 !== null ? $floatStyle : $textStyle],    // 43. Score (L1) - Course
                    ['AR', $scoreL2 !== null ? 'int' : 'str', $scoreL2 !== null ? $scoreL2 : '-', $scoreL2 !== null ? $intStyle : $textStyle],        // 44. Score (L2)
                    ['AS', 'str',   $row->l2_verbal ?: ($scoreL2 !== null ? ($scoreL2 >= 70 ? 'Passed' : 'Failed') : '-'), $textStyle],                 // 45. L2 Status
                    ['AT', $scoreL3 !== null ? 'float' : 'str', $scoreL3 !== null ? $scoreL3 : '-', $scoreL3 !== null ? $floatStyle : $textStyle],    // 46. Score (L3)
                    ['AU', 'str',   $row->l3_verbal ?: '-', $textStyle],                                                                                // 47. L3 Status
                    ['AV', 'str',   $certAvailability, $textStyle],                                                                                     // 48. Certificate Availability
                    ['AW', 'str',   $certInstitution, $textStyle],                                                                                      // 49. Certificate Institution
                    ['AX', $certIssuedDate !== '-' ? 'date' : 'str', $certIssuedDate, $certIssuedDate !== '-' ? '7' : $textStyle],                    // 50. Certificate Issued Date
                    ['AY', $certExpiredDate !== '-' ? 'date' : 'str', $certExpiredDate, $certExpiredDate !== '-' ? '7' : $textStyle],                  // 51. Certificate Expired Date
                    ['AZ', 'str',   $row->pic ?: '-', $textStyle],                                                                                      // 52. Person in Charge (PIC)
                    ['BA', 'dur',   $totalEngagementVal, $timeStyle],                                                                                   // 53. Total Engagement Time (LH)
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
            } elseif ($type === 'num' || $type === 'int' || $type === 'float' || $type === 'lh' || $type === 'pct' || $type === 'curr' || $type === 'month') {
                $numStr = (string) $val;
                $xml .= "<c r=\"{$coord}\" s=\"{$style}\"><v>{$numStr}</v></c>\n";
            } elseif ($type === 'dur' || $type === 'time') {
                if (is_numeric($val)) {
                    $numStr = sprintf('%.10F', (float) $val);
                    $numStr = rtrim(rtrim($numStr, '0'), '.');
                    if ($numStr === '') $numStr = '0';
                    $xml .= "<c r=\"{$coord}\" s=\"{$style}\"><v>{$numStr}</v></c>\n";
                } else {
                    $frac = $this->timeToExcelFraction((string) $val);
                    if ($frac !== null) {
                        $numStr = sprintf('%.10F', $frac);
                        $numStr = rtrim(rtrim($numStr, '0'), '.');
                        if ($numStr === '') $numStr = '0';
                        $xml .= "<c r=\"{$coord}\" s=\"{$style}\"><v>{$numStr}</v></c>\n";
                    } else {
                        $esc = $this->escapeXml((string) $val);
                        $xml .= "<c r=\"{$coord}\" t=\"inlineStr\" s=\"{$style}\"><is><t>{$esc}</t></is></c>\n";
                    }
                }
            } elseif ($type === 'date') {
                $days = $this->dateToExcelSerial((string) $val);
                if ($days > 0) {
                    $xml .= "<c r=\"{$coord}\" s=\"{$style}\"><v>{$days}</v></c>\n";
                } else {
                    $esc = $this->escapeXml((string) $val);
                    $xml .= "<c r=\"{$coord}\" t=\"inlineStr\" s=\"{$style}\"><is><t>{$esc}</t></is></c>\n";
                }
            } else {
                $esc = $this->escapeXml((string) $val);
                $xml .= "<c r=\"{$coord}\" t=\"inlineStr\" s=\"{$style}\"><is><t>{$esc}</t></is></c>\n";
            }
        }
        $xml .= "</row>\n";

        return $xml;
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
        if (!$ts) return 0;

        return (int) round(($ts - strtotime('1899-12-30')) / 86400);
    }

    protected function escapeXml(string $val): string
    {
        return htmlspecialchars($val, ENT_XML1 | ENT_COMPAT, 'UTF-8');
    }
}
