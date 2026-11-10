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
        $isPlaylist = (($row->source_type ?? '') === 'playlist') || (!empty($row->trainingplan_id));

        $durationFormatted = '-';
        if (!empty($row->content_duration)) {
            $durationFormatted = DurationFormatter::format($row->content_duration, '-');
        }

        $lHoursVal = $row->content_training_hours ?? ($row->playlist_training_hours ?? ($row->learning_hours ?? null));
        $lHoursNumeric = ($lHoursVal !== null && $lHoursVal !== '' && is_numeric($lHoursVal)) ? (float) $lHoursVal : null;

        $progressNum = (float) ($row->progress ?? 0);
        $progressDecimal = round($progressNum / 100, 4);
        $isFinished = ($row->finish == '1' || $progressNum >= 100);
        $progressStatus = $isFinished ? 'Finished' : ($progressNum > 0 ? 'Ongoing' : 'Not Started');

        $createdAt = !empty($row->created_at) ? strtotime($row->created_at) : null;
        $completedAt = (!empty($row->completed_at) && $isFinished) ? strtotime($row->completed_at) : null;

        $startDateStr = $createdAt ? date('d/m/Y', $createdAt) : '';
        $endDateStr = $completedAt ? date('d/m/Y', $completedAt) : '';
        $startTimeStr = $createdAt ? date('H:i:s', $createdAt) : '';
        $endTimeStr = $completedAt ? date('H:i:s', $completedAt) : '';
        $recordMonthStr = $createdAt ? date('F', $createdAt) : '-';

        $attendanceStr = ($isFinished || $progressNum > 0) ? 'Yes' : 'No';

        $rawEmp = $row->id_emp ?: ($row->user_id_emp ?? '-');
        $cleanEmpId = ($rawEmp !== '-') ? preg_replace('/\s*[\/\-]\s*NIP.*$/i', '', trim((string) $rawEmp)) : '-';

        $courseTitle = $isPlaylist ? ($row->playlist_title ?: 'Playlist') : ($row->content_title ?: 'Untitled Content');
        $mainClass = $row->classification_name ?: 'Unclassified';

        $scoreL2Val = (isset($row->score_l2) && is_numeric($row->score_l2)) ? (int) round((float) $row->score_l2) : '';

        $recognizedService = app(\App\Services\Learning\LearningRecordRecognizedDurationService::class);
        $recSec = $recognizedService->resolveFromRow($row);
        $recognizedFormatted = $recognizedService->formatSeconds($recSec);

        $gradeVal = $row->user_jobgrade ?: ($row->jobgrade ?? '-');
        $jobPosVal = $row->user_jobposition ?: ($row->jobposition ?? '-');

        $afkSec = (int) ($row->afk_duration_seconds ?? 0);
        $afkHours = floor($afkSec / 3600);
        $afkMins = floor(($afkSec % 3600) / 60);
        $afkSeconds = $afkSec % 60;
        $totalEngagementDuration = sprintf('%02d:%02d:%02d', $afkHours, $afkMins, $afkSeconds);
        $totalEngagementLh = round($afkSec / 3600, 2);

        $certAvailability = $isFinished ? 'Available' : '';
        $certInstitution = $isFinished ? 'PT Krakatau Posco' : '';
        $certIssuedDate = ($isFinished && $endDateStr !== '') ? $endDateStr : '';
        $certExpiredDate = '';

        $csSetting = null;
        if (!empty($row->content_id)) {
            $csSetting = DB::table('certificate_settings')->where('content_id', $row->content_id)->first();
        } elseif (!empty($row->trainingplan_id)) {
            $csSetting = DB::table('certificate_settings')->where('trainingplan_id', $row->trainingplan_id)->first();
        }
        if ($csSetting) {
            $certAvailability = 'Available';
            if ($csSetting->institution) {
                $certInstitution = $csSetting->institution;
            } elseif ($csSetting->issuer_name) {
                $certInstitution = $csSetting->issuer_name;
            }
            if ($csSetting->issued_date) {
                $certIssuedDate = self::formatDate($csSetting->issued_date);
            }
            if ($csSetting->expired_date) {
                $certExpiredDate = self::formatDate($csSetting->expired_date);
            }
        }

        return [
            $this->rowCount,                                                  // 1. No
            $cleanEmpId,                                                      // 2. Employee ID
            $row->employee_name ?: ($row->user_name ?? '-'),                  // 3. Employee Name
            $gradeVal,                                                        // 4. Grade
            $jobPosVal,                                                       // 5. Job Position
            $row->user_team ?: '-',                                           // 6. Team
            $row->user_department ?: '-',                                     // 7. Department
            $row->user_company ?: '-',                                        // 8. Company
            $courseTitle,                                                     // 9. Course Title
            '-',                                                              // 10. Sub Course Title
            $isPlaylist ? 'Playlist' : ($row->content_type ?: 'Document'),    // 11. Type
            $isPlaylist ? 'Content Playlist' : 'All Content',                 // 12. Category
            '-',                                                              // 13. Level
            '-',                                                              // 14. Batch
            $mainClass,                                                       // 15. Main Classification
            '-',                                                              // 16. Sub Classification
            'Online',                                                         // 17. Delivery Method
            'KP OWL',                                                         // 18. Sub Delivery Method
            'Internal',                                                       // 19. Provider
            '-',                                                              // 20. Sub Provider
            'KP-OWL',                                                         // 21. Provider Name
            'Online LMS',                                                     // 22. Venue
            ($row->content_author ?? ($row->author ?? '-')) ?: '-',           // 23. Instructor
            $durationFormatted,                                               // 24. Course Duration
            $recognizedFormatted,                                             // 25. Total Recognized Duration
            $lHoursNumeric !== null ? $lHoursNumeric : '-',                   // 26. Learning Hours
            $progressDecimal,                                                 // 27. % Progress
            $progressStatus,                                                  // 28. Progress Status
            $startDateStr,                                                    // 29. Start Date
            $endDateStr,                                                      // 30. End Date
            $startTimeStr,                                                    // 31. Start Time
            $endTimeStr,                                                      // 32. End Time
            $recordMonthStr,                                                  // 33. Record Month
            'Mandatory',                                                      // 34. Requirement
            $attendanceStr,                                                   // 35. Attendance
            '-',                                                              // 36. Attendance Reason
            'No',                                                             // 37. Bond Status
            '-',                                                              // 38. Bond Duration
            '-',                                                              // 39. Bond End Date
            '-',                                                              // 40. Bond Amount
            '-',                                                              // 41. Score (L1) - Course
            $scoreL2Val !== '' ? $scoreL2Val : '-',                           // 42. Score (L2)
            $scoreL2Val !== '' && $scoreL2Val !== '-' ? ($scoreL2Val >= 70 ? 'Passed' : 'Failed') : '-', // 43. L2 Status
            '-',                                                              // 44. Score (L3)
            '-',                                                              // 45. L3 Status
            $certAvailability,                                                // 46. Certificate Availability
            $certInstitution,                                                 // 47. Certificate Institution
            $certIssuedDate,                                                  // 48. Certificate Issued Date
            $certExpiredDate,                                                 // 49. Certificate Expired Date
            '-',                                                              // 50. Person in Charge (PIC)
            $totalEngagementDuration,                                         // 51. Total Engagement Time
            $totalEngagementLh,                                               // 52. Total Engagement Time (LH)
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

        if (!empty($this->filters['limit'])) {
            $query->limit((int) $this->filters['limit']);
        } elseif (app()->environment('testing')) {
            $query->limit(500);
        }

        return $query;
    }

    protected function getPlaylistChildrenMap(): array
    {
        try {
            $items = DB::table('trainingplandetail as tc')
                ->join('content as c', 'tc.content_id', '=', 'c.id')
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
        ini_set('max_execution_time', 0);
        if (function_exists('set_time_limit')) {
            @set_time_limit(0);
        }

        $tempXlsx = tempnam(sys_get_temp_dir(), 'online_record_') . '.xlsx';
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
            . '<numFmt numFmtId="165" formatCode="dd/mm/yyyy"/>'
            . '<numFmt numFmtId="166" formatCode="hh:mm:ss"/>'
            . '<numFmt numFmtId="167" formatCode="0.00&quot; h&quot;"/>'
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
        $recognizedService = app(\App\Services\Learning\LearningRecordRecognizedDurationService::class);
        $rowCount = 0;
        $excelRow = 1;
        $buffer = '';
        $batchSize = 1000;

        $contentCertSettings = DB::table('certificate_settings')
            ->whereNotNull('content_id')
            ->get()
            ->keyBy('content_id');
        $tpCertSettings = DB::table('certificate_settings')
            ->whereNotNull('trainingplan_id')
            ->get()
            ->keyBy('trainingplan_id');

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
                $lHoursNumeric = ($lHoursVal !== null && $lHoursVal !== '' && is_numeric($lHoursVal)) ? (float) $lHoursVal : null;

                // Progress & Status
                $progressNum = (float) ($row->progress ?? 0);
                $progressDecimal = round($progressNum / 100, 4);
                $isFinished = ($row->finish == '1' || $progressNum >= 100);
                $progressStatus = $isFinished ? 'Finished' : ($progressNum > 0 ? 'Ongoing' : 'Not Started');

                // Date & Time split with dd/mm/yyyy formatting and empty when missing
                $createdAt = !empty($row->created_at) ? strtotime($row->created_at) : null;
                $completedAt = (!empty($row->completed_at) && $isFinished) ? strtotime($row->completed_at) : null;

                $startDateStr = $createdAt ? date('d/m/Y', $createdAt) : '';
                $endDateStr = $completedAt ? date('d/m/Y', $completedAt) : '';
                $startTimeStr = $createdAt ? date('H:i:s', $createdAt) : '';
                $endTimeStr = $completedAt ? date('H:i:s', $completedAt) : '';

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
                $lhType = ($lHoursNumeric !== null) ? 'lh' : 'blank';
                $lhVal = ($lHoursNumeric !== null) ? $lHoursNumeric : '';
                $lhActualStyle = ($lHoursNumeric !== null) ? $lhStyle : $textStyle;

                // Score L2 integer
                $scoreL2Val = (isset($row->score_l2) && is_numeric($row->score_l2)) ? (int) round((float) $row->score_l2) : '';
                $scoreL2Type = ($scoreL2Val !== '') ? 'int' : 'blank';
                $scoreL2Style = ($scoreL2Val !== '') ? $intStyle : $textStyle;

                // Employee ID: raw string/integer only
                $rawEmp = $row->id_emp ?: ($row->user_id_emp ?? '-');
                $cleanEmpId = ($rawEmp !== '-') ? preg_replace('/\s*[\/\-]\s*NIP.*$/i', '', trim((string) $rawEmp)) : '-';

                $recSec = $recognizedService->resolveFromRow($row);
                $recognizedFormatted = $recognizedService->formatSeconds($recSec);

                $gradeVal = $row->user_jobgrade ?: ($row->jobgrade ?? '-');
                $jobPosVal = $row->user_jobposition ?: ($row->jobposition ?? '-');

                // Engagement time calculation
                $afkSec = (int) ($row->afk_duration_seconds ?? 0);
                $afkHours = floor($afkSec / 3600);
                $afkMins = floor(($afkSec % 3600) / 60);
                $afkSeconds = $afkSec % 60;
                $totalEngagementDuration = sprintf('%02d:%02d:%02d', $afkHours, $afkMins, $afkSeconds);
                $totalEngagementLh = round($afkSec / 3600, 2);

                // Row cell mapping (52 canonical columns)
                $certAvailability = $isFinished ? 'Available' : '';
                $certInstitution = $isFinished ? 'PT Krakatau Posco' : '';
                $certIssuedDate = ($isFinished && $endDateStr !== '') ? $endDateStr : '';
                $certExpiredDate = '';

                $csSetting = null;
                if (!empty($row->content_id)) {
                    $csSetting = $contentCertSettings->get($row->content_id);
                } elseif (!empty($row->trainingplan_id)) {
                    $csSetting = $tpCertSettings->get($row->trainingplan_id);
                }
                if ($csSetting) {
                    $certAvailability = 'Available';
                    if ($csSetting->institution) {
                        $certInstitution = $csSetting->institution;
                    } elseif ($csSetting->issuer_name) {
                        $certInstitution = $csSetting->issuer_name;
                    }
                    if ($csSetting->issued_date) {
                        $certIssuedDate = self::formatDate($csSetting->issued_date);
                    }
                    if ($csSetting->expired_date) {
                        $certExpiredDate = self::formatDate($csSetting->expired_date);
                    }
                }
                $finalRecordMonth = ($completedAt && $isFinished) ? date('F', $completedAt) : $recordMonthStr;

                // Exactly 52 Canonical Columns: A (No) through AZ (Total Engagement Time (LH))
                $cells = [
                    ['A', 'num', $rowCount, $textStyle],                                          // 1. No
                    ['B', 'str', $cleanEmpId, $textStyle],                                        // 2. Employee ID
                    ['D', 'str', $row->employee_name ?: ($row->user_name ?? '-'), $textStyle],   // 3. Employee Name (Col C)
                ];
                // Fix column letters for exact 52
                $cells = [
                    ['A', 'num', $rowCount, $textStyle],                                          // 1. No
                    ['B', 'str', $cleanEmpId, $textStyle],                                        // 2. Employee ID
                    ['C', 'str', $row->employee_name ?: ($row->user_name ?? '-'), $textStyle],   // 3. Employee Name
                    ['D', 'str', $gradeVal, $textStyle],                                          // 4. Grade
                    ['E', 'str', $jobPosVal, $textStyle],                                         // 5. Job Position
                    ['F', 'str', $row->user_team ?: '-', $textStyle],                             // 6. Team
                    ['G', 'str', $row->user_department ?: '-', $textStyle],                       // 7. Department
                    ['H', 'str', $row->user_company ?: '-', $textStyle],                          // 8. Company
                    ['I', 'str', $courseTitle, $textStyle],                                       // 9. Course Title
                    ['J', 'str', '-', $textStyle],                                                // 10. Sub Course Title
                    ['K', 'str', $isPlaylist ? 'Playlist' : ($row->content_type ?: 'Content'), $textStyle], // 11. Type
                    ['L', 'str', $isPlaylist ? 'Playlist' : 'Content', $textStyle],               // 12. Category
                    ['M', 'str', '-', $textStyle],                                                // 13. Level
                    ['N', 'str', '-', $textStyle],                                                // 14. Batch
                    ['O', 'str', $mainClass, $textStyle],                                         // 15. Main Classification
                    ['P', 'str', '-', $textStyle],                                                // 16. Sub Classification
                    ['Q', 'str', 'Online', $textStyle],                                           // 17. Delivery Method
                    ['R', 'str', 'E-learning', $textStyle],                                       // 18. Sub Delivery Method
                    ['S', 'str', 'Internal', $textStyle],                                         // 19. Provider
                    ['T', 'str', '-', $textStyle],                                                // 20. Sub Provider
                    ['U', 'str', 'KP-OWL', $textStyle],                                           // 21. Provider Name
                    ['V', 'str', 'Online LMS', $textStyle],                                       // 22. Venue
                    ['W', 'str', ($row->content_author ?? ($row->author ?? '-')) ?: '-', $textStyle], // 23. Instructor
                    ['X', 'str', $durationFormatted, $textStyle],                                 // 24. Course Duration
                    ['Y', 'str', $recognizedFormatted, $textStyle],                               // 25. Total Recognized Duration
                    ['Z', $lhType, $lhVal, $lhActualStyle],                                       // 26. Learning Hours
                    ['AA', 'pct', $progressDecimal, $pctStyle],                                   // 27. % Progress
                    ['AB', 'str', $progressStatus, $textStyle],                                   // 28. Progress Status
                    ['AC', $startDateStr !== '' ? 'str' : 'blank', $startDateStr, $startDateStr !== '' ? '7' : $textStyle], // 29. Start Date
                    ['AD', $endDateStr !== '' ? 'str' : 'blank', $endDateStr, $endDateStr !== '' ? '7' : $textStyle],       // 30. End Date
                    ['AE', $startTimeStr !== '' ? 'str' : 'blank', $startTimeStr, $startTimeStr !== '' ? '8' : $textStyle], // 31. Start Time
                    ['AF', $endTimeStr !== '' ? 'str' : 'blank', $endTimeStr, $endTimeStr !== '' ? '8' : $textStyle],       // 32. End Time
                    ['AG', 'str', $finalRecordMonth, $textStyle],                                 // 33. Record Month
                    ['AH', 'str', 'Mandatory', $textStyle],                                       // 34. Requirement
                    ['AI', 'str', $attendanceStr, $textStyle],                                    // 35. Attendance
                    ['AJ', 'str', '', $textStyle],                                                // 36. Attendance Reason
                    ['AK', 'str', 'No', $textStyle],                                              // 37. Bond Status
                    ['AL', 'str', '', $textStyle],                                                // 38. Bond Duration
                    ['AM', 'blank', '', $textStyle],                                              // 39. Bond End Date
                    ['AN', 'blank', '', $textStyle],                                              // 40. Bond Amount
                    ['AO', 'blank', '', $textStyle],                                              // 41. Score (L1) - Course
                    ['AP', $scoreL2Type, $scoreL2Val, $scoreL2Style],                             // 42. Score (L2)
                    ['AQ', 'str', $scoreL2Val !== '' ? ($scoreL2Val >= 70 ? 'Passed' : 'Failed') : '', $textStyle], // 43. L2 Status
                    ['AR', 'blank', '', $textStyle],                                              // 44. Score (L3)
                    ['AS', 'str', '', $textStyle],                                                // 45. L3 Status
                    ['AT', 'str', $certAvailability, $textStyle],                                 // 46. Certificate Availability
                    ['AU', 'str', $certInstitution, $textStyle],                                  // 47. Certificate Institution
                    ['AV', $certIssuedDate !== '' ? 'str' : 'blank', $certIssuedDate, $certIssuedDate !== '' ? '7' : $textStyle], // 48. Certificate Issued Date
                    ['AW', $certExpiredDate !== '' ? 'str' : 'blank', $certExpiredDate, $certExpiredDate !== '' ? '7' : $textStyle], // 49. Certificate Expired Date
                    ['AX', 'str', '', $textStyle],                                                // 50. Person in Charge (PIC)
                    ['AY', 'str', $totalEngagementDuration, $textStyle],                          // 51. Total Engagement Time
                    ['AZ', 'lh', $totalEngagementLh, $lhActualStyle],                             // 52. Total Engagement Time (LH)
                ];

                $buffer .= $this->buildXmlRow($r, $cells);

                // Child rows for playlists
                if ($isPlaylist && !empty($row->trainingplan_id)) {
                    $tpId = (int) $row->trainingplan_id;
                    if (isset($childrenMap[$tpId])) {
                        foreach ($childrenMap[$tpId] as $child) {
                            $excelRow++;
                            $cr = $excelRow;

                            $childDuration = !empty($child->duration) ? DurationFormatter::format($child->duration, '-') : '-';
                            $childClass = $child->classification_name ?: '-';

                            $childCells = [
                                ['A', 'str', '-', '3'],                                           // 1. No
                                ['B', 'str', $cleanEmpId, '3'],                                   // 2. Employee ID
                                ['C', 'str', $row->employee_name ?: ($row->user_name ?? '-'), '3'], // 3. Employee Name
                                ['D', 'str', $gradeVal, '3'],                                     // 4. Grade
                                ['E', 'str', $jobPosVal, '3'],                                    // 5. Job Position
                                ['F', 'str', $row->user_team ?: '-', '3'],                         // 6. Team
                                ['G', 'str', $row->user_department ?: '-', '3'],                   // 7. Department
                                ['H', 'str', $row->user_company ?: '-', '3'],                      // 8. Company
                                ['I', 'str', '    ' . $child->title, '3'],                        // 9. Course Title
                                ['J', 'str', '-', '3'],                                           // 10. Sub Course Title
                                ['K', 'str', ucfirst($child->type ?: 'Content'), '3'],            // 11. Type
                                ['L', 'str', 'Content Playlist', '3'],                            // 12. Category
                                ['M', 'str', '-', '3'],                                           // 13. Level
                                ['N', 'str', '-', '3'],                                           // 14. Batch
                                ['O', 'str', $childClass, '3'],                                   // 15. Main Classification
                                ['P', 'str', '-', '3'],                                           // 16. Sub Classification
                                ['Q', 'str', 'Online', '3'],                                      // 17. Delivery Method
                                ['R', 'str', 'E-learning', '3'],                                  // 18. Sub Delivery Method
                                ['S', 'str', 'Internal', '3'],                                    // 19. Provider
                                ['T', 'str', '-', '3'],                                           // 20. Sub Provider
                                ['U', 'str', 'KP-OWL', '3'],                                      // 21. Provider Name
                                ['V', 'str', 'Online LMS', '3'],                                  // 22. Venue
                                ['W', 'str', $child->author ?: '-', '3'],                         // 23. Instructor
                                ['X', 'str', $childDuration, '3'],                                // 24. Course Duration
                                ['Y', 'str', '-', '3'],                                           // 25. Total Recognized Duration
                                ['Z', 'blank', '', '3'],                                          // 26. Learning Hours
                                ['AA', 'blank', '', '3'],                                         // 27. % Progress
                                ['AB', 'str', '-', '3'],                                          // 28. Progress Status
                                ['AC', 'blank', '', '3'],                                         // 29. Start Date
                                ['AD', 'blank', '', '3'],                                         // 30. End Date
                                ['AE', 'blank', '', '3'],                                         // 31. Start Time
                                ['AF', 'blank', '', '3'],                                         // 32. End Time
                                ['AG', 'str', '-', '3'],                                          // 33. Record Month
                                ['AH', 'str', '-', '3'],                                          // 34. Requirement
                                ['AI', 'str', '-', '3'],                                          // 35. Attendance
                                ['AJ', 'str', '', '3'],                                           // 36. Attendance Reason
                                ['AK', 'str', '-', '3'],                                          // 37. Bond Status
                                ['AL', 'str', '', '3'],                                           // 38. Bond Duration
                                ['AM', 'blank', '', '3'],                                         // 39. Bond End Date
                                ['AN', 'blank', '', '3'],                                         // 40. Bond Amount
                                ['AO', 'blank', '', '3'],                                         // 41. Score (L1) - Course
                                ['AP', 'blank', '', '3'],                                         // 42. Score (L2)
                                ['AQ', 'str', '', '3'],                                           // 43. L2 Status
                                ['AR', 'blank', '', '3'],                                         // 44. Score (L3)
                                ['AS', 'str', '', '3'],                                           // 45. L3 Status
                                ['AT', 'str', '', '3'],                                           // 46. Certificate Availability
                                ['AU', 'str', '', '3'],                                           // 47. Certificate Institution
                                ['AV', 'blank', '', '3'],                                         // 48. Certificate Issued Date
                                ['AW', 'blank', '', '3'],                                         // 49. Certificate Expired Date
                                ['AX', 'str', '', '3'],                                           // 50. Person in Charge (PIC)
                                ['AY', 'str', '-', '3'],                                          // 51. Total Engagement Time
                                ['AZ', 'blank', '', '3'],                                         // 52. Total Engagement Time (LH)
                            ];

                            $buffer .= $this->buildXmlRow($cr, $childCells);
                        }
                    }
                }

                if ($excelRow % $batchSize === 0) {
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
