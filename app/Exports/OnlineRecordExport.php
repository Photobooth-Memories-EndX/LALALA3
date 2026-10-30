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

class OnlineRecordExport implements Responsable, \Maatwebsite\Excel\Concerns\WithHeadings, \Maatwebsite\Excel\Concerns\WithMapping
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

        return "EmployeeOWLrecord_{$day}{$month}{$year} at {$time}.xlsx";
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
        $isPlaylist = (($row->source_type ?? '') === 'playlist') || (!empty($row->trainingplan_id));

        $durationFormatted = '-';
        if (!empty($row->content_duration)) {
            $durationFormatted = DurationFormatter::format($row->content_duration, '-');
        }

        $lHoursVal = $row->content_training_hours ?? ($row->playlist_training_hours ?? ($row->learning_hours ?? null));
        $formattedLHours = self::formatHours($lHoursVal);

        $progressNum = (float) ($row->progress ?? 0);
        $progressDecimal = round($progressNum / 100, 4);
        $isFinished = ($row->finish == '1' || $progressNum >= 100);
        $progressStatus = $isFinished ? 'Finished' : ($progressNum > 0 ? 'Ongoing' : 'Not Started');

        $createdAt = !empty($row->created_at) ? strtotime($row->created_at) : null;
        $completedAt = (!empty($row->completed_at) && $isFinished) ? strtotime($row->completed_at) : null;

        $startDateStr = $createdAt ? date('Y-m-d', $createdAt) : '-';
        $endDateStr = $completedAt ? date('Y-m-d', $completedAt) : '-';
        $startTimeStr = $createdAt ? date('H:i:s', $createdAt) : '-';
        $endTimeStr = $completedAt ? date('H:i:s', $completedAt) : '-';
        $recordMonthStr = $createdAt ? date('F', $createdAt) : '-';

        $attendanceStr = ($isFinished || $progressNum > 0) ? 'Yes' : 'No';

        $rawEmp = $row->id_emp ?: ($row->user_id_emp ?? '-');
        $cleanEmpId = ($rawEmp !== '-') ? preg_replace('/\s*[\/\-]\s*NIP.*$/i', '', trim((string) $rawEmp)) : '-';

        $courseTitle = $isPlaylist ? ($row->playlist_title ?: 'Playlist') : ($row->content_title ?: 'Untitled Content');
        $mainClass = $row->classification_name ?: 'Unclassified';

        $scoreL2Val = (isset($row->score_l2) && is_numeric($row->score_l2)) ? (int) round((float) $row->score_l2) : '-';

        $recognizedService = app(\App\Services\Learning\LearningRecordRecognizedDurationService::class);
        $recSec = $recognizedService->resolveFromRow($row);
        $recognizedFormatted = $recognizedService->formatSeconds($recSec);

        $gradeVal = $row->user_jobgrade ?: ($row->jobgrade ?? '-');
        $jobPosVal = $row->user_jobposition ?: ($row->jobposition ?? '-');

        $afkSec = (int) ($row->afk_duration_seconds ?? 0);
        $afkFormatted = $afkSec > 0 ? DurationFormatter::fromSeconds($afkSec) : ($row->afk_duration_seconds !== null ? '00:00:00' : '-');

        return [
            $this->rowCount,
            $cleanEmpId,
            $row->employee_name ?: ($row->user_name ?? '-'),
            $gradeVal, // Grade
            $jobPosVal, // Job Position
            $row->user_team ?: '-',
            $row->user_department ?: '-',
            $row->user_company ?: '-',
            $courseTitle,
            '-', // Sub Course Title
            $isPlaylist ? 'Playlist' : ($row->content_type ?: 'Content'),
            $isPlaylist ? 'Playlist' : 'Content',
            '-', // Level
            '-', // Batch
            $mainClass,
            '-', // Sub Classification
            'Online',
            'E-learning',
            'KP-OWL',
            'KP-OWL',
            ($row->content_author ?? ($row->author ?? '-')) ?: '-',
            $durationFormatted,
            $recognizedFormatted,
            $formattedLHours,
            $progressDecimal,
            $progressStatus,
            $startDateStr,
            $endDateStr,
            $startTimeStr,
            $endTimeStr,
            $recordMonthStr,
            'Mandatory',
            $attendanceStr,
            '-', // Attendance Reason
            'No', // Bond Status
            '-', // Bond Duration
            '-', // Bond End Date
            '-', // Bond Amount
            '-', // Score L1
            $scoreL2Val, // Score L2
            '-', // L2 Status
            '-', // Score L3
            '-', // L3 Status
            '-', // PIC
            '-', // Certificate
            $afkFormatted, // Total Engagement Time
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
            . '<numFmts count="4">'
            . '<numFmt numFmtId="164" formatCode="&quot;Rp&quot;#,##0"/>'
            . '<numFmt numFmtId="165" formatCode="yyyy-mm-dd"/>'
            . '<numFmt numFmtId="166" formatCode="hh:mm:ss"/>'
            . '<numFmt numFmtId="167" formatCode="General&quot; h&quot;"/>'
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
            . '<cellXfs count="15">'
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
            . '<xf numFmtId="167" fontId="0" fillId="0" borderId="0" xfId="0" applyNumberFormat="1" applyAlignment="1"><alignment horizontal="center" vertical="center"/></xf>'
            . '<xf numFmtId="167" fontId="2" fillId="3" borderId="0" xfId="0" applyFont="1" applyFill="1" applyNumberFormat="1" applyAlignment="1"><alignment horizontal="center" vertical="center"/></xf>'
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
            $lHoursNumeric = ($lHoursVal !== null && $lHoursVal !== '') ? (float) $lHoursVal : null;

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
            $attendanceStr = ($isFinished || $progressNum > 0) ? 'Yes' : 'No';

            // Titles & Classification
            $courseTitle = $isPlaylist ? ($row->playlist_title ?: 'Playlist') : ($row->content_title ?: 'Untitled Content');
            $mainClass = $row->classification_name ?: 'Unclassified';

            // Base cell style for parent playlist vs normal row
            $textStyle = $isPlaylist ? '2' : '0';
            $intStyle = $isPlaylist ? '10' : '4';
            $pctStyle = $isPlaylist ? '11' : '6';

            // Learning hours styling
            $lhStyle = $isPlaylist ? '14' : '13';
            $lhType = ($lHoursNumeric !== null) ? 'lh' : 'str';
            $lhVal = ($lHoursNumeric !== null) ? $lHoursNumeric : '-';
            $lhActualStyle = ($lHoursNumeric !== null) ? $lhStyle : $textStyle;

            // Score L2 integer
            $scoreL2Val = (isset($row->score_l2) && is_numeric($row->score_l2)) ? (int) round((float) $row->score_l2) : '-';
            $scoreL2Type = ($scoreL2Val !== '-') ? 'int' : 'str';
            $scoreL2Style = ($scoreL2Val !== '-') ? $intStyle : $textStyle;

            // Employee ID: raw string/integer only
            $rawEmp = $row->id_emp ?: ($row->user_id_emp ?? '-');
            $cleanEmpId = ($rawEmp !== '-') ? preg_replace('/\s*[\/\-]\s*NIP.*$/i', '', trim((string) $rawEmp)) : '-';

            $recognizedService = app(\App\Services\Learning\LearningRecordRecognizedDurationService::class);
            $recSec = $recognizedService->resolveFromRow($row);
            $recognizedFormatted = $recognizedService->formatSeconds($recSec);

            $gradeVal = $row->user_jobgrade ?: ($row->jobgrade ?? '-');
            $jobPosVal = $row->user_jobposition ?: ($row->jobposition ?? '-');

            $afkSec = (int) ($row->afk_duration_seconds ?? 0);
            $afkFormatted = $afkSec > 0 ? DurationFormatter::fromSeconds($afkSec) : ($row->afk_duration_seconds !== null ? '00:00:00' : '-');

            // Row cell mapping (52 canonical columns)
            $certAvailability = $isFinished ? 'Available' : '-';
            $certInstitution = $isFinished ? 'PT Krakatau Posco' : '-';
            $certIssuedDate = ($isFinished && $endDateStr !== '-') ? $endDateStr : '-';
            $certExpiredDate = '-';
            $finalRecordMonth = ($completedAt && $isFinished) ? date('F', $completedAt) : '-';

            $cells = [
                ['A', 'num', $rowCount, $textStyle],
                ['B', 'str', '-', $textStyle], // Source
                ['C', 'str', $cleanEmpId, $textStyle],
                ['D', 'str', $row->employee_name ?: ($row->user_name ?? '-'), $textStyle],
                ['E', 'str', $gradeVal, $textStyle], // Grade
                ['F', 'str', $jobPosVal, $textStyle], // Job Position
                ['G', 'str', $row->user_team ?: '-', $textStyle],
                ['H', 'str', $row->user_department ?: '-', $textStyle],
                ['I', 'str', $row->user_company ?: '-', $textStyle],
                ['J', 'str', $courseTitle, $textStyle],
                ['K', 'str', '-', $textStyle], // Sub Course Title
                ['L', 'str', $isPlaylist ? 'Playlist' : ($row->content_type ?: 'Content'), $textStyle],
                ['M', 'str', $isPlaylist ? 'Playlist' : 'Content', $textStyle],
                ['N', 'str', '-', $textStyle], // Level
                ['O', 'str', '-', $textStyle], // Batch
                ['P', 'str', $mainClass, $textStyle],
                ['Q', 'str', '-', $textStyle], // Sub Classification
                ['R', 'str', 'Online', $textStyle],
                ['S', 'str', 'E-learning', $textStyle],
                ['T', 'str', 'Internal', $textStyle], // Provider
                ['U', 'str', '-', $textStyle], // Sub Provider
                ['V', 'str', 'KP-OWL', $textStyle], // Provider Name
                ['W', 'str', 'Online LMS', $textStyle], // Venue
                ['X', 'str', ($row->content_author ?? ($row->author ?? '-')) ?: '-', $textStyle], // Instructor
                ['Y', 'str', $durationFormatted, $textStyle], // Course Duration
                ['Z', 'str', $recognizedFormatted, $textStyle], // Total Recognized Duration
                ['AA', $lhType, $lhVal, $lhActualStyle], // Learning Hours
                ['AB', 'str', $afkFormatted, $textStyle], // Total Engagement Time
                ['AC', 'pct', $progressDecimal, $pctStyle],
                ['AD', 'str', $progressStatus, $textStyle],
                ['AE', 'str', $startDateStr, $textStyle],
                ['AF', 'str', $endDateStr, $textStyle],
                ['AG', 'str', $startTimeStr, $textStyle],
                ['AH', 'str', $endTimeStr, $textStyle],
                ['AI', 'str', $finalRecordMonth, $textStyle],
                ['AJ', 'str', 'Mandatory', $textStyle],
                ['AK', 'str', $attendanceStr, $textStyle],
                ['AL', 'str', '-', $textStyle], // Attendance Reason
                ['AM', 'str', 'No', $textStyle], // Bond Status
                ['AN', 'str', '-', $textStyle], // Bond Duration
                ['AO', 'str', '-', $textStyle], // Bond End Date
                ['AP', 'str', '-', $textStyle], // Bond Amount
                ['AQ', 'str', '-', $textStyle], // Score L1
                ['AR', $scoreL2Type, $scoreL2Val, $scoreL2Style], // Score L2
                ['AS', 'str', '-', $textStyle], // L2 Status
                ['AT', 'str', '-', $textStyle], // Score L3
                ['AU', 'str', '-', $textStyle], // L3 Status
                ['AV', 'str', $certAvailability, $textStyle], // Certificate Availability
                ['AW', 'str', $certInstitution, $textStyle], // Certificate Institution
                ['AX', 'str', $certIssuedDate, $textStyle], // Certificate Issued Date
                ['AY', 'str', $certExpiredDate, $textStyle], // Certificate Expired Date
                ['AZ', 'str', '-', $textStyle], // PIC
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
                            ['B', 'str', $cleanEmpId, '3'],
                            ['C', 'str', $row->employee_name ?: ($row->user_name ?? '-'), '3'],
                            ['D', 'str', $gradeVal, '3'],
                            ['E', 'str', $jobPosVal, '3'],
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
                            ['W', 'str', '-', '3'], // Recognized Duration on child
                            ['X', 'str', '-', '3'], // Learning Hours on child to prevent double counting
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
                            ['AT', 'str', '-', '3'],
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
            } elseif ($type === 'lh') {
                $lhFloat = (float) $val;
                $xml .= "<c r=\"{$coord}\" s=\"{$styleId}\"><v>{$lhFloat}</v></c>";
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
