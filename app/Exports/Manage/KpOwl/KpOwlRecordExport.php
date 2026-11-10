<?php

namespace App\Exports\Manage\KpOwl;

use App\Queries\Manage\KpOwl\KpOwlRecordQuery;
use App\Support\DurationFormatter;
use Carbon\Carbon;
use Illuminate\Contracts\Support\Responsable;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use ZipStream\Option\Archive as ArchiveOptions;
use ZipStream\ZipStream;

class KpOwlRecordExport implements Responsable
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
        $day = $dt->format('d');
        $month = $dt->format('M');
        $year = $dt->format('Y');
        $time = $dt->format('H.i.s');

        return "KP_OWL_Learning_Records_{$day}{$month}{$year}_{$time}.xlsx";
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
            'Person in Charge (PIC)',
            'Certificate Availability',
            'Certificate Institution',
            'Certificate Issued Date',
            'Certificate Expired Date',
            'Total Engagement Time',
        ];
    }

    public static function columnWidths(): array
    {
        return [
            'A' => 6,   // NO
            'B' => 12,  // Source
            'C' => 16,  // Employee ID
            'D' => 28,  // Employee Name
            'E' => 10,  // Grade
            'F' => 24,  // Job Position
            'G' => 20,  // Team
            'H' => 25,  // Department
            'I' => 24,  // Company
            'J' => 35,  // Course Title
            'K' => 18,  // Sub Course Title
            'L' => 14,  // Type
            'M' => 16,  // Category
            'N' => 10,  // Level
            'O' => 10,  // Batch
            'P' => 24,  // Main Classification
            'Q' => 18,  // Sub Classification
            'R' => 16,  // Delivery Method
            'S' => 20,  // Sub Delivery Method
            'T' => 14,  // Provider
            'U' => 14,  // Sub Provider
            'V' => 16,  // Provider Name
            'W' => 16,  // Venue
            'X' => 24,  // Instructor
            'Y' => 16,  // Course Duration
            'Z' => 24,  // Total Recognized Duration
            'AA' => 16, // Learning Hours
            'AB' => 14, // % Progress
            'AC' => 16, // Progress Status
            'AD' => 14, // Start Date
            'AE' => 14, // End Date
            'AF' => 12, // Start Time
            'AG' => 12, // End Time
            'AH' => 16, // Record Month
            'AI' => 16, // Requirement
            'AJ' => 14, // Attendance
            'AK' => 20, // Attendance Reason
            'AL' => 14, // Bond Status
            'AM' => 15, // Bond Duration
            'AN' => 15, // Bond End Date
            'AO' => 16, // Bond Amount
            'AP' => 18, // Score (L1) - Course
            'AQ' => 14, // Score (L2)
            'AR' => 14, // L2 Status
            'AS' => 14, // Score (L3)
            'AT' => 14, // L3 Status
            'AU' => 24, // Person in Charge (PIC)
            'AV' => 22, // Certificate Availability
            'AW' => 24, // Certificate Institution
            'AX' => 20, // Certificate Issued Date
            'AY' => 20, // Certificate Expired Date
            'AZ' => 22, // Total Engagement Time
        ];
    }

    public function download(?string $fileName = null): BinaryFileResponse
    {
        $fileName = $fileName ?? $this->fileName;
        $tempPath = $this->generateXlsxFile();

        return response()->download($tempPath, $fileName, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        ])->deleteFileAfterSend(true);
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

        $tempXlsx = tempnam(sys_get_temp_dir(), 'kp_owl_export_') . '.xlsx';
        $opt = new ArchiveOptions();
        $opt->setSendHttpHeaders(false);
        $fh = fopen($tempXlsx, 'w+b');
        $opt->setOutputStream($fh);
        $zip = new ZipStream(null, $opt);

        $zip->addFile('[Content_Types].xml',
            '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            . '<Types xmlns="http://schemas.openxmlformats.org/package/2006/content-types">'
            . '<Default Extension="rels" ContentType="application/vnd.openxmlformats-package.relationships+xml"/>'
            . '<Default Extension="xml" ContentType="application/xml"/>'
            . '<Override PartName="/xl/workbook.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.sheet.main+xml"/>'
            . '<Override PartName="/xl/worksheets/sheet1.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.worksheet+xml"/>'
            . '<Override PartName="/xl/styles.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.styles+xml"/>'
            . '</Types>'
        );

        $zip->addFile('_rels/.rels',
            '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            . '<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">'
            . '<Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/officeDocument" Target="xl/workbook.xml"/>'
            . '</Relationships>'
        );

        $zip->addFile('xl/_rels/workbook.xml.rels',
            '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            . '<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">'
            . '<Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/worksheet" Target="worksheets/sheet1.xml"/>'
            . '<Relationship Id="rId2" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/styles" Target="styles.xml"/>'
            . '</Relationships>'
        );

        $zip->addFile('xl/workbook.xml',
            '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            . '<workbook xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main" xmlns:r="http://schemas.openxmlformats.org/officeDocument/2006/relationships">'
            . '<sheets><sheet name="KP OWL Learning Records" sheetId="1" r:id="rId1"/></sheets>'
            . '</workbook>'
        );

        $zip->addFile('xl/styles.xml',
            '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            . '<styleSheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main">'
            . '<numFmts count="3">'
            . '<numFmt numFmtId="164" formatCode="&quot;Rp&quot;#,##0"/>'
            . '<numFmt numFmtId="165" formatCode="yyyy-mm-dd"/>'
            . '<numFmt numFmtId="166" formatCode="hh:mm:ss"/>'
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
            . '<cellXfs count="10">'
            . '<xf numFmtId="49" fontId="0" fillId="0" borderId="0" xfId="0" applyNumberFormat="1" applyAlignment="1"><alignment horizontal="center" vertical="center"/></xf>' // 0: text center
            . '<xf numFmtId="0" fontId="1" fillId="2" borderId="0" xfId="0" applyFont="1" applyFill="1" applyAlignment="1"><alignment horizontal="center" vertical="center" wrapText="1"/></xf>' // 1: header
            . '<xf numFmtId="1" fontId="0" fillId="0" borderId="0" xfId="0" applyNumberFormat="1" applyAlignment="1"><alignment horizontal="center" vertical="center"/></xf>' // 2: int
            . '<xf numFmtId="2" fontId="0" fillId="0" borderId="0" xfId="0" applyNumberFormat="1" applyAlignment="1"><alignment horizontal="center" vertical="center"/></xf>' // 3: decimal
            . '<xf numFmtId="9" fontId="0" fillId="0" borderId="0" xfId="0" applyNumberFormat="1" applyAlignment="1"><alignment horizontal="center" vertical="center"/></xf>' // 4: percent
            . '<xf numFmtId="165" fontId="0" fillId="0" borderId="0" xfId="0" applyNumberFormat="1" applyAlignment="1"><alignment horizontal="center" vertical="center"/></xf>' // 5: date
            . '<xf numFmtId="166" fontId="0" fillId="0" borderId="0" xfId="0" applyNumberFormat="1" applyAlignment="1"><alignment horizontal="center" vertical="center"/></xf>' // 6: time
            . '<xf numFmtId="164" fontId="0" fillId="0" borderId="0" xfId="0" applyNumberFormat="1" applyAlignment="1"><alignment horizontal="center" vertical="center"/></xf>' // 7: currency
            . '<xf numFmtId="49" fontId="0" fillId="0" borderId="0" xfId="0" applyNumberFormat="1" applyAlignment="1"><alignment horizontal="left" vertical="center"/></xf>' // 8: text left
            . '</cellXfs>'
            . '</styleSheet>'
        );

        $sheetFile = tempnam(sys_get_temp_dir(), 'xlsx_sheet_kp_owl_');
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

        $query = app(KpOwlRecordQuery::class)->buildQuery($this->filters)
            ->orderBy('unified.created_at', 'desc');

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

                $isPlaylist = ($row->source_type === 'playlist');
                $pct = (float) ($row->progress ?? 0);
                $isFinished = ($row->finish === '1' || $pct >= 100);

                // Course Duration
                $durSec = (int) ($row->duration_seconds ?? 0);
                if ($durSec <= 0 && !empty($row->content_duration)) {
                    $durSec = DurationFormatter::toSeconds($row->content_duration) ?? 0;
                }
                $durFmt = $durSec > 0 ? DurationFormatter::fromSeconds($durSec) : '-';

                // Engagement & Recognized Duration
                $watch = (int) ($row->actual_watch_seconds ?? 0);
                $curr = (int) ($row->current_time ?? 0);
                $lh = (float) ($row->learning_hours ?? 0);

                if ($watch > 0) $actualSec = $watch;
                elseif ($curr > 0) $actualSec = $curr;
                elseif ($isFinished && $durSec > 0) $actualSec = $durSec;
                elseif ($pct > 0 && $durSec > 0) $actualSec = (int) round(($pct / 100) * $durSec);
                elseif ($lh > 0) $actualSec = (int) round($lh * 3600);
                else $actualSec = 0;

                $maxCap = $durSec > 0 ? ($durSec * 2) : 0;
                $recSec = ($maxCap > 0) ? min($actualSec, $maxCap) : $actualSec;
                $recFmt = $recSec > 0 ? DurationFormatter::fromSeconds($recSec) : '-';
                $engFmt = $actualSec > 0 ? DurationFormatter::fromSeconds($actualSec) : '-';

                // Learning hours
                $lhNumeric = round($actualSec / 3600, 4);
                if ($lhNumeric <= 0 && $lh > 0) {
                    $lhNumeric = $lh;
                }

                // Clean Employee ID
                $rawEmp = $row->id_emp ?: '-';
                $cleanEmpId = ($rawEmp !== '-') ? preg_replace('/\s*[\/\-]\s*NIP.*$/i', '', trim((string) $rawEmp)) : '-';

                // Dates & Times
                $startDate = $row->created_at ? date('Y-m-d', strtotime($row->created_at)) : '-';
                $startTime = $row->created_at ? date('H:i:s', strtotime($row->created_at)) : '-';
                $endDate = ($isFinished && $row->completed_at) ? date('Y-m-d', strtotime($row->completed_at)) : '-';
                $endTime = ($isFinished && $row->completed_at) ? date('H:i:s', strtotime($row->completed_at)) : '-';

                // Record Month MUST be taken from End Date per business logic
                $recordMonth = ($endDate !== '-') ? date('F', strtotime($endDate)) : '-';

                // Attendance
                $attendance = ($pct > 0 || $isFinished) ? 'Yes' : 'No';

                // Status string
                $statusStr = $isFinished ? 'Finished' : ($pct > 0 ? 'Ongoing' : 'Not Started');

                // Requirement
                $reqStr = strtolower(trim((string) ($row->requirement_status ?? ''))) === 'mandatory' ? 'Mandatory' : 'Non-Mandatory';

                // Titles & Types
                $courseTitle = $isPlaylist ? ($row->playlist_title ?: 'Playlist') : ($row->content_title ?: 'Untitled Content');
                $typeStr = $isPlaylist ? 'Playlist' : ucfirst(strtolower($row->content_type ?: 'Document'));
                $catStr = $isPlaylist ? 'Playlist' : 'All Content';
                $subDelivery = $isPlaylist ? 'Playlist / Path' : 'Self-Paced / LMS';

                // Certificate
                $certAvail = $isFinished ? 'Available' : '-';
                $certInst = $isFinished ? 'PT Krakatau Posco' : '-';
                $certIssued = ($isFinished && $endDate !== '-') ? $endDate : '-';

                // 52 CELLS IN EXACT ORDER
                $cells = [
                    ['A', 'num', $rowCount, '0'],                                          // 1. NO
                    ['B', 'str', 'KP OWL', '0'],                                           // 2. Source
                    ['C', 'str', $cleanEmpId, '0'],                                        // 3. Employee ID
                    ['D', 'str', $row->employee_name ?: '-', '8'],                         // 4. Employee Name
                    ['E', 'str', $row->jobgrade ?: '-', '0'],                              // 5. Grade
                    ['F', 'str', $row->jobposition ?: '-', '0'],                           // 6. Job Position
                    ['G', 'str', $row->team ?: '-', '0'],                                  // 7. Team
                    ['H', 'str', $row->department ?: '-', '0'],                            // 8. Department
                    ['I', 'str', $row->company_name ?: 'PT Krakatau Posco', '0'],          // 9. Company
                    ['J', 'str', $courseTitle, '8'],                                       // 10. Course Title
                    ['K', 'str', '-', '0'],                                                // 11. Sub Course Title
                    ['L', 'str', $typeStr, '0'],                                           // 12. Type
                    ['M', 'str', $catStr, '0'],                                            // 13. Category
                    ['N', 'str', '-', '0'],                                                // 14. Level
                    ['O', 'str', '-', '0'],                                                // 15. Batch
                    ['P', 'str', $row->classification_name ?: 'Unclassified', '0'],        // 16. Main Classification
                    ['Q', 'str', '-', '0'],                                                // 17. Sub Classification
                    ['R', 'str', 'Online', '0'],                                           // 18. Delivery Method
                    ['S', 'str', $subDelivery, '0'],                                       // 19. Sub Delivery Method
                    ['T', 'str', 'Internal', '0'],                                         // 20. Provider
                    ['U', 'str', '-', '0'],                                                // 21. Sub Provider
                    ['V', 'str', 'KP-OWL', '0'],                                           // 22. Provider Name
                    ['W', 'str', 'Online LMS', '0'],                                       // 23. Venue
                    ['X', 'str', $row->content_author ?: '-', '0'],                        // 24. Instructor
                    ['Y', 'str', $durFmt, '0'],                                            // 25. Course Duration
                    ['Z', 'str', $recFmt, '0'],                                            // 26. Total Recognized Duration
                    ['AA', 'dec', $lhNumeric, '3'],                                        // 27. Learning Hours
                    ['AB', 'pct', round($pct / 100, 4), '4'],                              // 28. % Progress
                    ['AC', 'str', $statusStr, '0'],                                        // 29. Progress Status
                    ['AD', 'str', $startDate, '0'],                                        // 30. Start Date
                    ['AE', 'str', $endDate, '0'],                                          // 31. End Date
                    ['AF', 'str', $startTime, '0'],                                        // 32. Start Time
                    ['AG', 'str', $endTime, '0'],                                          // 33. End Time
                    ['AH', 'str', $recordMonth, '0'],                                      // 34. Record Month
                    ['AI', 'str', $reqStr, '0'],                                           // 35. Requirement
                    ['AJ', 'str', $attendance, '0'],                                       // 36. Attendance
                    ['AK', 'str', '-', '0'],                                                // 37. Attendance Reason
                    ['AL', 'str', 'No', '0'],                                               // 38. Bond Status
                    ['AM', 'str', '-', '0'],                                                // 39. Bond Duration
                    ['AN', 'str', '-', '0'],                                                // 40. Bond End Date
                    ['AO', 'str', '-', '0'],                                                // 41. Bond Amount
                    ['AP', 'str', '-', '0'],                                                // 42. Score (L1) - Course
                    ['AQ', 'str', '-', '0'],                                                // 43. Score (L2)
                    ['AR', 'str', '-', '0'],                                                // 44. L2 Status
                    ['AS', 'str', '-', '0'],                                                // 45. Score (L3)
                    ['AT', 'str', '-', '0'],                                                // 46. L3 Status
                    ['AU', 'str', '-', '0'],                                                // 47. Person in Charge (PIC)
                    ['AV', 'str', $certAvail, '0'],                                        // 48. Certificate Availability
                    ['AW', 'str', $certInst, '0'],                                         // 49. Certificate Institution
                    ['AX', 'str', $certIssued, '0'],                                       // 50. Certificate Issued Date
                    ['AY', 'str', '-', '0'],                                                // 51. Certificate Expired Date
                    ['AZ', 'str', $engFmt, '0'],                                           // 52. Total Engagement Time
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
        }

        fwrite($sfh, '</sheetData>' . "\n");
        fwrite($sfh, '</worksheet>' . "\n");
        fclose($sfh);

        $zip->addFileFromPath('xl/worksheets/sheet1.xml', $sheetFile);
        $zip->finish();
        fclose($fh);
        @unlink($sheetFile);

        return $tempXlsx;
    }

    protected function buildXmlRow(int $rowNum, array $cells): string
    {
        $xml = "<row r=\"{$rowNum}\">";
        foreach ($cells as [$colLetter, $type, $val, $style]) {
            $coord = $colLetter . $rowNum;
            if ($val === null || $val === '') {
                $val = '-';
            }
            if ($type === 'num' || $type === 'dec' || $type === 'pct') {
                if (is_numeric($val)) {
                    $xml .= "<c r=\"{$coord}\" s=\"{$style}\"><v>{$val}</v></c>";
                    continue;
                }
            }
            $escaped = $this->escapeXml((string) $val);
            $xml .= "<c r=\"{$coord}\" t=\"inlineStr\" s=\"{$style}\"><is><t>{$escaped}</t></is></c>";
        }
        $xml .= "</row>\n";
        return $xml;
    }

    protected function escapeXml(string $str): string
    {
        return htmlspecialchars($str, ENT_XML1 | ENT_QUOTES, 'UTF-8');
    }
}
