<?php

namespace App\Exports;

use App\Services\Learning\LearningDatatableService;
use App\Support\DurationFormatter;
use Carbon\Carbon;
use Illuminate\Contracts\Support\Responsable;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use ZipStream\Option\Archive as ArchiveOptions;
use ZipStream\ZipStream;

class OnlineRecordExport implements Responsable
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

        return "EmployeeOWLrecord_{$day}{$month}{$year} at {$time}.xlsx";
    }

    public function getFileName(): string
    {
        return $this->fileName;
    }

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
            'S'  => 25,  // Provider Name
            'T'  => 20,  // Venue
            'U'  => 22,  // Instructor
            'V'  => 16,  // Course Duration
            'W'  => 16,  // Learning Hours
            'X'  => 14,  // % Progress
            'Y'  => 16,  // Progress Status
            'Z'  => 14,  // Start Date
            'AA' => 14,  // End Date
            'AB' => 14,  // Start Time
            'AC' => 14,  // End Time
            'AD' => 16,  // Record Month
            'AE' => 15,  // Requirement
            'AF' => 14,  // Attendance
            'AG' => 25,  // Attendance Reason
            'AH' => 14,  // Bond Status
            'AI' => 16,  // Bond Duration
            'AJ' => 15,  // Bond End Date
            'AK' => 18,  // Bond Amount
            'AL' => 20,  // Score (L1) - Course
            'AM' => 14,  // Score (L2)
            'AN' => 14,  // L2 Status
            'AO' => 14,  // Score (L3)
            'AP' => 14,  // L3 Status
            'AQ' => 22,  // Person in Charge (PIC)
            'AR' => 14,  // Certificate
            'AS' => 22,  // Total Engagement Time
        ];
    }

    public function query()
    {
        $query = LearningDatatableService::buildBaseQuery(
            $this->filters,
            (string) ($this->filters['type'] ?? ''),
            (string) ($this->filters['slug'] ?? '')
        );

        $query->addSelect([
            'c.author as content_author',
            'c.status as content_status',
            'c.learning_hours as content_training_hours',
            'tp.learning_hours as playlist_training_hours',
        ]);

        if (empty($this->filters['sort'])) {
            $query->orderBy('unified.created_at', 'desc')
                  ->orderBy('unified.source_type', 'desc')
                  ->orderBy('unified.source_id', 'desc');
        }

        return $query;
    }

    protected function getPlaylistChildrenMap(): array
    {
        try {
            $items = DB::table('trainingplan_contents as tc')
                ->join('contents as c', 'tc.content_id', '=', 'c.id')
                ->leftJoin('classification as cl', 'c.classification_id', '=', 'cl.id')
                ->select([
                    'tc.trainingplan_id',
                    'c.id as content_id',
                    'c.title',
                    'c.type',
                    'c.duration',
                    'c.author',
                    'cl.classification_name',
                ])
                ->orderBy('tc.id', 'asc')
                ->get();

            $grouped = [];
            foreach ($items as $item) {
                $grouped[(int) $item->trainingplan_id][] = $item;
            }

            return $grouped;
        } catch (\Throwable $e) {
            return [];
        }
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

        $tempXlsx = tempnam(sys_get_temp_dir(), 'online_record_') . '.xlsx';
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
            . '<sheets><sheet name="KP OWL Online Records" sheetId="1" r:id="rId1"/></sheets>'
            . '</workbook>'
        );

        // 5. xl/styles.xml with Center Alignment globally, explicit number formats & styles
        $zip->addFile(
            'xl/styles.xml',
            '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            . '<styleSheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main">'
            . '<numFmts count="3">'
            . '<numFmt numFmtId="164" formatCode="&quot;Rp&quot;#,##0"/>'
            . '<numFmt numFmtId="165" formatCode="yyyy-mm-dd"/>'
            . '<numFmt numFmtId="166" formatCode="hh:mm:ss"/>'
            . '</numFmts>'
            . '<fonts count="4">'
            . '<font><sz val="10"/><name val="Calibri"/></font>'
            . '<font><b/><sz val="10"/><color rgb="FFFFFFFF"/><name val="Calibri"/></font>'
            . '<font><b/><sz val="10"/><name val="Calibri"/></font>'
            . '<font><sz val="9"/><name val="Calibri"/></font>'
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
            . '<cellXfs count="13">'
            . '<xf numFmtId="49" fontId="0" fillId="0" borderId="0" xfId="0" applyNumberFormat="1" applyAlignment="1"><alignment horizontal="center" vertical="center"/></xf>'
            . '<xf numFmtId="0" fontId="1" fillId="2" borderId="0" xfId="0" applyFont="1" applyFill="1" applyAlignment="1"><alignment horizontal="center" vertical="center" wrapText="1"/></xf>'
            . '<xf numFmtId="49" fontId="2" fillId="3" borderId="0" xfId="0" applyFont="1" applyFill="1" applyNumberFormat="1" applyAlignment="1"><alignment horizontal="center" vertical="center"/></xf>'
            . '<xf numFmtId="49" fontId="3" fillId="0" borderId="0" xfId="0" applyFont="1" applyNumberFormat="1" applyAlignment="1"><alignment horizontal="center" vertical="center"/></xf>'
            . '<xf numFmtId="1" fontId="0" fillId="0" borderId="0" xfId="0" applyNumberFormat="1" applyAlignment="1"><alignment horizontal="center" vertical="center"/></xf>'
            . '<xf numFmtId="2" fontId="0" fillId="0" borderId="0" xfId="0" applyNumberFormat="1" applyAlignment="1"><alignment horizontal="center" vertical="center"/></xf>'
            . '<xf numFmtId="9" fontId="0" fillId="0" borderId="0" xfId="0" applyNumberFormat="1" applyAlignment="1"><alignment horizontal="center" vertical="center"/></xf>'
            . '<xf numFmtId="165" fontId="0" fillId="0" borderId="0" xfId="0" applyNumberFormat="1" applyAlignment="1"><alignment horizontal="center" vertical="center"/></xf>'
            . '<xf numFmtId="166" fontId="0" fillId="0" borderId="0" xfId="0" applyNumberFormat="1" applyAlignment="1"><alignment horizontal="center" vertical="center"/></xf>'
            . '<xf numFmtId="164" fontId="0" fillId="0" borderId="0" xfId="0" applyNumberFormat="1" applyAlignment="1"><alignment horizontal="center" vertical="center"/></xf>'
            . '<xf numFmtId="1" fontId="2" fillId="3" borderId="0" xfId="0" applyFont="1" applyFill="1" applyNumberFormat="1" applyAlignment="1"><alignment horizontal="center" vertical="center"/></xf>'
            . '<xf numFmtId="9" fontId="2" fillId="3" borderId="0" xfId="0" applyFont="1" applyFill="1" applyNumberFormat="1" applyAlignment="1"><alignment horizontal="center" vertical="center"/></xf>'
            . '<xf numFmtId="165" fontId="2" fillId="3" borderId="0" xfId="0" applyFont="1" applyFill="1" applyNumberFormat="1" applyAlignment="1"><alignment horizontal="center" vertical="center"/></xf>'
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
        $childrenMap = $this->getPlaylistChildrenMap();
        $rowCount = 0;
        $excelRow = 1;
        $buffer = '';
        $batchSize = 1000;

        $pdo = DB::connection()->getPdo();
        $wasBuffered = $pdo->getAttribute(\PDO::MYSQL_ATTR_USE_BUFFERED_QUERY);
        $pdo->setAttribute(\PDO::MYSQL_ATTR_USE_BUFFERED_QUERY, false);

        try {
            foreach ($query->cursor() as $row) {
            $rowCount++;
            $excelRow++;
            $r = $excelRow;

            $isPlaylist = (($row->source_type ?? '') === 'playlist') || (!empty($row->trainingplan_id));

            // Durations
            $durationFormatted = '-';
            if (!empty($row->content_duration)) {
                $durationFormatted = DurationFormatter::format($row->content_duration, '-');
            }

            // Learning hours
            $lHoursVal = $row->content_training_hours ?? ($row->playlist_training_hours ?? ($row->learning_hours ?? null));
            $lHoursStr = ($lHoursVal !== null && $lHoursVal !== '') ? (number_format((float) $lHoursVal, 2) . ' h') : '-';

            // Progress & Status
            $progressNum = (float) ($row->progress ?? 0);
            $progressDecimal = round($progressNum / 100, 4);
            $isFinished = ($row->finish == '1' || $progressNum >= 100);
            $progressStatus = $isFinished ? 'Finished' : ($progressNum > 0 ? 'Ongoing' : 'Not Started');

            // Date & Time split
            $createdAt = !empty($row->created_at) ? strtotime($row->created_at) : null;
            $completedAt = (!empty($row->completed_at) && $isFinished) ? strtotime($row->completed_at) : null;

            $startDateStr = $createdAt ? date('Y-m-d', $createdAt) : '-';
            $endDateStr = $completedAt ? date('Y-m-d', $completedAt) : '-';
            $startTimeStr = $createdAt ? date('H:i:s', $createdAt) : '-';
            $endTimeStr = $completedAt ? date('H:i:s', $completedAt) : '-';

            $recordMonthStr = $createdAt ? date('F', $createdAt) : '-';

            // Attendance: strictly Yes or No
            $attendanceStr = $isFinished ? 'Yes' : ($progressNum > 0 ? 'No' : '-');

            // Titles & Classification
            $courseTitle = $isPlaylist ? ($row->playlist_title ?: 'Playlist') : ($row->content_title ?: 'Untitled Content');
            $mainClass = $row->classification_name ?: 'Unclassified';

            // Base cell style for parent playlist vs normal row
            $textStyle = $isPlaylist ? '2' : '0';
            $intStyle = $isPlaylist ? '10' : '4';
            $pctStyle = $isPlaylist ? '11' : '6';

            // Row cell mapping (45 columns)
            $cells = [
                ['A', 'num', $rowCount, $textStyle],
                ['B', 'str', $row->id_emp ?: ($row->user_id_emp ?? '-'), $textStyle],
                ['C', 'str', $row->employee_name ?: ($row->user_name ?? '-'), $textStyle],
                ['D', 'str', '-', $textStyle], // Grade
                ['E', 'str', '-', $textStyle], // Job Position
                ['F', 'str', $row->user_team ?: '-', $textStyle],
                ['G', 'str', $row->user_department ?: '-', $textStyle],
                ['H', 'str', $row->user_company ?: '-', $textStyle],
                ['I', 'str', $courseTitle, $textStyle],
                ['J', 'str', '-', $textStyle], // Sub Course Title
                ['K', 'str', $isPlaylist ? 'Playlist' : ($row->content_type ?: 'Content'), $textStyle],
                ['L', 'str', $isPlaylist ? 'Playlist' : 'Content', $textStyle],
                ['M', 'str', '-', $textStyle], // Level
                ['N', 'str', '-', $textStyle], // Batch
                ['O', 'str', $mainClass, $textStyle],
                ['P', 'str', '-', $textStyle], // Sub Classification
                ['Q', 'str', 'Online', $textStyle],
                ['R', 'str', 'E-learning', $textStyle],
                ['S', 'str', 'KP-OWL', $textStyle],
                ['T', 'str', 'KP-OWL', $textStyle],
                ['U', 'str', $row->content_author ?: '-', $textStyle],
                ['V', 'str', $durationFormatted, $textStyle],
                ['W', 'str', $lHoursStr, $textStyle],
                ['X', 'pct', $progressDecimal, $pctStyle],
                ['Y', 'str', $progressStatus, $textStyle],
                ['Z', 'str', $startDateStr, $textStyle],
                ['AA', 'str', $endDateStr, $textStyle],
                ['AB', 'str', $startTimeStr, $textStyle],
                ['AC', 'str', $endTimeStr, $textStyle],
                ['AD', 'str', $recordMonthStr, $textStyle],
                ['AE', 'str', 'Mandatory', $textStyle],
                ['AF', 'str', $attendanceStr, $textStyle],
                ['AG', 'str', '-', $textStyle], // Attendance Reason
                ['AH', 'str', 'No', $textStyle], // Bond Status
                ['AI', 'str', '-', $textStyle], // Bond Duration
                ['AJ', 'str', '-', $textStyle], // Bond End Date
                ['AK', 'str', '-', $textStyle], // Bond Amount
                ['AL', 'str', '-', $textStyle], // Score L1
                ['AM', 'str', '-', $textStyle], // Score L2
                ['AN', 'str', '-', $textStyle], // L2 Status
                ['AO', 'str', '-', $textStyle], // Score L3
                ['AP', 'str', '-', $textStyle], // L3 Status
                ['AQ', 'str', '-', $textStyle], // PIC
                ['AR', 'str', '-', $textStyle], // Certificate
                ['AS', 'str', $lHoursStr, $textStyle], // Total Engagement Time
            ];

            $buffer .= $this->buildXmlRow($r, $cells);

            // Expand Child Contents for Playlist
            if ($isPlaylist) {
                $tpId = (int) ($row->trainingplan_id ?? 0);
                if ($tpId > 0 && isset($childrenMap[$tpId])) {
                    foreach ($childrenMap[$tpId] as $child) {
                        $excelRow++;
                        $cr = $excelRow;

                        $childDuration = !empty($child->duration) ? DurationFormatter::format($child->duration, '-') : '-';
                        $childClass = $child->classification_name ?: '-';

                        $childCells = [
                            ['A', 'str', '-', '3'],
                            ['B', 'str', $row->id_emp ?: ($row->user_id_emp ?? '-'), '3'],
                            ['C', 'str', $row->employee_name ?: ($row->user_name ?? '-'), '3'],
                            ['D', 'str', '-', '3'],
                            ['E', 'str', '-', '3'],
                            ['F', 'str', $row->user_team ?: '-', '3'],
                            ['G', 'str', $row->user_department ?: '-', '3'],
                            ['H', 'str', $row->user_company ?: '-', '3'],
                            ['I', 'str', '    ' . $child->title, '3'], // Indented title
                            ['J', 'str', 'Child of ' . $courseTitle, '3'],
                            ['K', 'str', ucfirst($child->type ?: 'Content'), '3'],
                            ['L', 'str', 'Content Playlist', '3'],
                            ['M', 'str', '-', '3'],
                            ['N', 'str', '-', '3'],
                            ['O', 'str', $childClass, '3'],
                            ['P', 'str', '-', '3'],
                            ['Q', 'str', 'Online', '3'],
                            ['R', 'str', 'E-learning', '3'],
                            ['S', 'str', 'KP-OWL', '3'],
                            ['T', 'str', 'KP-OWL', '3'],
                            ['U', 'str', $child->author ?: '-', '3'],
                            ['V', 'str', $childDuration, '3'],
                            ['W', 'str', '-', '3'], // '-' on child to prevent double counting
                            ['X', 'str', '-', '3'],
                            ['Y', 'str', '-', '3'],
                            ['Z', 'str', '-', '3'],
                            ['AA', 'str', '-', '3'],
                            ['AB', 'str', '-', '3'],
                            ['AC', 'str', '-', '3'],
                            ['AD', 'str', '-', '3'],
                            ['AE', 'str', '-', '3'],
                            ['AF', 'str', '-', '3'],
                            ['AG', 'str', '-', '3'],
                            ['AH', 'str', '-', '3'],
                            ['AI', 'str', '-', '3'],
                            ['AJ', 'str', '-', '3'],
                            ['AK', 'str', '-', '3'],
                            ['AL', 'str', '-', '3'],
                            ['AM', 'str', '-', '3'],
                            ['AN', 'str', '-', '3'],
                            ['AO', 'str', '-', '3'],
                            ['AP', 'str', '-', '3'],
                            ['AQ', 'str', '-', '3'],
                            ['AR', 'str', '-', '3'],
                            ['AS', 'str', '-', '3'],
                        ];

                        $buffer .= $this->buildXmlRow($cr, $childCells);
                    }
                }
            }

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
