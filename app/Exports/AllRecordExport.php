<?php

namespace App\Exports;

use App\Services\Record\RecordAllCombinedService;
use Illuminate\Contracts\Support\Responsable;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\WithChunkReading;
use Maatwebsite\Excel\Concerns\WithColumnWidths;
use Maatwebsite\Excel\Concerns\WithHeadings;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use ZipStream\Option\Archive as ArchiveOptions;
use ZipStream\ZipStream;

class AllRecordExport implements FromQuery, WithHeadings, WithChunkReading, WithColumnWidths, Responsable
{
    protected array $filters = [];
    protected int $rowNumber = 0;
    protected string $fileName;

    public function __construct($filters = [], ?string $fileName = null)
    {
        $this->filters = $filters instanceof Request ? $filters->all() : (array) $filters;
        $this->rowNumber = 0;
        $this->fileName = $fileName ?? sprintf('all-kp-owl-combined-records-%s.xlsx', now()->format('Y-m-d'));
    }

    public function getFileName(): string
    {
        return $this->fileName;
    }

    public function query()
    {
        $service = app(RecordAllCombinedService::class);

        return $service->buildCombinedQuery($this->filters)
            ->orderBy('raw_finish_date', 'desc')
            ->orderBy('record_date', 'desc');
    }

    public function headings(): array
    {
        return [
            'No',
            'Source',
            'Employee ID',
            'Employee Name',
            'Status',
            'Grade',
            'Job Position',
            'Team',
            'Department',
            'Company',
            'Learning Title',
            'Sub Title',
            'Type',
            'Category',
            'Level',
            'Batch',
            'Main Classification',
            'Sub Classification',
            'Corporate KPI',
            'Sub Corporate KPI',
            'Delivery Method',
            'Sub Delivery Method',
            'Provider',
            'Sub Provider',
            'Provider Name',
            'Venue',
            'Instructor',
            'Duration',
            'Training Hours',
            'Start Date',
            'Finish Date',
            'Participant Hours',
            'Progress',
            'Assessment Score (L2)',
            'Certificate',
            'Attendance',
            'Attendance Reason',
            'Bond Status',
            'Bond End Date',
            'Bond Amount',
            'L1 Program',
            'L2 Numerical',
            'L2 Verbal',
            'L3 Numerical',
            'L3 Verbal',
            'PIC',
        ];
    }

    public function chunkSize(): int
    {
        return 2000;
    }

    public function columnWidths(): array
    {
        return [
            'A'  => 6,   // No
            'B'  => 14,  // Source
            'C'  => 14,  // Employee ID
            'D'  => 30,  // Employee Name
            'E'  => 14,  // Status
            'F'  => 10,  // Grade
            'G'  => 25,  // Job Position
            'H'  => 20,  // Team
            'I'  => 25,  // Department
            'J'  => 25,  // Company
            'K'  => 35,  // Learning Title
            'L'  => 25,  // Sub Title
            'M'  => 15,  // Type
            'N'  => 18,  // Category
            'O'  => 12,  // Level
            'P'  => 12,  // Batch
            'Q'  => 25,  // Main Classification
            'R'  => 25,  // Sub Classification
            'S'  => 15,  // Corporate KPI
            'T'  => 22,  // Sub Corporate KPI
            'U'  => 18,  // Delivery Method
            'V'  => 20,  // Sub Delivery Method
            'W'  => 15,  // Provider
            'X'  => 18,  // Sub Provider
            'Y'  => 25,  // Provider Name
            'Z'  => 20,  // Venue
            'AA' => 22,  // Instructor
            'AB' => 14,  // Duration
            'AC' => 16,  // Training Hours
            'AD' => 14,  // Start Date
            'AE' => 14,  // Finish Date
            'AF' => 18,  // Participant Hours
            'AG' => 12,  // Progress
            'AH' => 20,  // Assessment Score (L2)
            'AI' => 14,  // Certificate
            'AJ' => 14,  // Attendance
            'AK' => 25,  // Attendance Reason
            'AL' => 14,  // Bond Status
            'AM' => 15,  // Bond End Date
            'AN' => 16,  // Bond Amount
            'AO' => 14,  // L1 Program
            'AP' => 15,  // L2 Numerical
            'AQ' => 14,  // L2 Verbal
            'AR' => 15,  // L3 Numerical
            'AS' => 14,  // L3 Verbal
            'AT' => 14,  // PIC
        ];
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
            . '<cellXfs count="3">'
            . '<xf numFmtId="0" fontId="0" fillId="0" borderId="0" xfId="0" applyAlignment="1"><alignment horizontal="center" vertical="center"/></xf>'
            . '<xf numFmtId="0" fontId="1" fillId="2" borderId="0" xfId="0" applyFont="1" applyFill="1" applyAlignment="1"><alignment horizontal="center" vertical="center"/></xf>'
            . '<xf numFmtId="0" fontId="2" fillId="3" borderId="0" xfId="0" applyFont="1" applyFill="1" applyAlignment="1"><alignment horizontal="center" vertical="center"/></xf>'
            . '</cellXfs>'
            . '</styleSheet>'
        );

        // 6. Temporary file stream for sheet1.xml
        $sheetFile = tempnam(sys_get_temp_dir(), 'xlsx_sheet_');
        $sfh = fopen($sheetFile, 'w+b');

        fwrite($sfh, '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>' . "\n");
        fwrite($sfh, '<worksheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main">' . "\n");
        fwrite($sfh, '<cols>' . "\n");
        foreach ($this->columnWidths() as $colLetter => $w) {
            $colIdx = Coordinate::columnIndexFromString($colLetter);
            fwrite($sfh, "<col min=\"{$colIdx}\" max=\"{$colIdx}\" width=\"{$w}\" customWidth=\"1\"/>\n");
        }
        fwrite($sfh, '</cols>' . "\n");
        fwrite($sfh, '<sheetData>' . "\n");

        // Header row
        $headers = $this->headings();
        fwrite($sfh, '<row r="1" ht="28" customHeight="1">' . "\n");
        foreach ($headers as $i => $h) {
            $coord = Coordinate::stringFromColumnIndex($i + 1) . '1';
            $val = $this->escapeXml($h);
            fwrite($sfh, "<c r=\"{$coord}\" t=\"inlineStr\" s=\"1\"><is><t>{$val}</t></is></c>\n");
        }
        fwrite($sfh, '</row>' . "\n");

        // Single unbuffered streaming query execution: zero offset loops, zero memory bloat
        $query = $this->query();
        $rowCount = 0;

        $pdo = DB::connection()->getPdo();
        $wasBuffered = $pdo->getAttribute(\PDO::MYSQL_ATTR_USE_BUFFERED_QUERY);
        $pdo->setAttribute(\PDO::MYSQL_ATTR_USE_BUFFERED_QUERY, false);

        $buffer = '';
        $batchSize = 2000;

        try {
            $stmt = $pdo->prepare($query->toSql());
            $stmt->execute($query->getBindings());

            while ($row = $stmt->fetch(\PDO::FETCH_OBJ)) {
                $rowCount++;
                $r = $rowCount + 1;

                // Duration formatted as HH:MM:SS
                $durationFormatted = '';
                if (!empty($row->duration_seconds)) {
                    $secs = (int) $row->duration_seconds;
                    $hours = floor($secs / 3600);
                    $mins = floor(($secs % 3600) / 60);
                    $seconds = $secs % 60;
                    $durationFormatted = sprintf('%02d:%02d:%02d', $hours, $mins, $seconds);
                }

                // Attendance
                $attendanceStr = 'No';
                if ($row->attendance !== null) {
                    $attLower = strtolower(trim((string) $row->attendance));
                    $attendanceStr = in_array($attLower, ['1', 'true', 'present', 'yes'], true) ? 'Yes' : 'No';
                } elseif (isset($row->progress) && (float) $row->progress >= 100) {
                    $attendanceStr = 'Yes';
                }

                // Counted as Corporate KPI
                $kpiStr = '';
                if ($row->counted_corporate_kpi !== null) {
                    if ((bool) $row->counted_corporate_kpi) {
                        $year = !empty($row->corporate_kpi_year) ? $row->corporate_kpi_year : (!empty($row->sub_corporate_kpi) && preg_match('/^\d{4}$/', (string)$row->sub_corporate_kpi) ? $row->sub_corporate_kpi : null);
                        $kpiStr = $year ? "Yes ({$year})" : "Yes";
                    } else {
                        $kpiStr = 'No';
                    }
                }

                // Clean Assessment Score formatting (remove trailing decimal zeros/dots)
                $cleanScore = is_numeric($row->assessment_score) ? (int) round((float) $row->assessment_score) : '-';

                // Prevent double calculation: under playlist, set learning_hours cell to '-' on child rows
                $isChildUnderPlaylist = (($row->source_type ?? '') === 'playlist' && !empty($row->content_id))
                    || (($row->category ?? '') === 'Content Playlist');

                if ($isChildUnderPlaylist) {
                    $lhVal = '-';
                } else {
                    $lhVal = ($row->learning_hours !== null && $row->learning_hours !== '') ? number_format((float) $row->learning_hours, 2) : '-';
                }

                // Delivery method alignment
                $delivMethod = \App\Models\OfflineTraining::normalizeDeliveryMethod($row->delivery_method ?: ($row->source === 'offline' ? 'Offline' : 'Online'));
                $subDelivMethod = $row->sub_delivery_method ?: ($row->source === 'offline' ? 'On-site' : 'E-learning');

                // Bond amount formatting
                $bondAmt = '-';
                $idr = $row->bond_amount_idr ?? $row->bond_amount ?? null;
                $usd = $row->bond_amount_usd ?? null;
                if ($idr !== null && (float) $idr > 0 && $usd !== null && (float) $usd > 0) {
                    $bondAmt = 'IDR ' . number_format((float) $idr, 0, ',', '.') . ' / USD ' . number_format((float) $usd, 2, '.', ',');
                } elseif ($idr !== null && (float) $idr > 0) {
                    $bondAmt = number_format((float) $idr, 2);
                } elseif ($usd !== null && (float) $usd > 0) {
                    $bondAmt = '$ ' . number_format((float) $usd, 2);
                }

                $data = [
                    $row->source === 'offline' ? 'Non KP OWL' : 'KP OWL',
                    $row->employee_id ?: '-',
                    $row->employee_name ?: '-',
                    $row->status ? ucfirst((string) $row->status) : '-',
                    $row->grade ?: '-',
                    $row->job_position ?: '-',
                    $row->team ?: '-',
                    $row->department ?: '-',
                    $row->company ?: '-',
                    $row->learning_title ?: '-',
                    $row->sub_course_name ?: '-',
                    $row->type ?: '-',
                    $row->category ?: '-',
                    $row->level ?: '-',
                    $row->batch ?: '-',
                    $row->classification ?: '-',
                    $row->sub_classification ?: '-',
                    $kpiStr ?: '-',
                    $row->sub_corporate_kpi ?: '-',
                    $delivMethod,
                    $subDelivMethod,
                    $row->provider ?: ($row->source === 'offline' ? '-' : 'Internal'),
                    $row->sub_provider ?: '-',
                    $row->provider_name ?: ($row->source === 'offline' ? '-' : 'KP-OWL'),
                    $row->venue ?: ($row->source === 'offline' ? '-' : 'KP-OWL'),
                    $row->instructor ?: '-',
                    $durationFormatted ?: '-',
                    $row->training_hours !== null ? number_format((float) $row->training_hours, 2) : '-',
                    $row->start_date ? date('Y-m-d', strtotime($row->start_date)) : '-',
                    $row->finish_date ? date('Y-m-d', strtotime($row->finish_date)) : '-',
                    $lhVal,
                    $row->progress !== null ? ($row->progress . '%') : '0%',
                    $cleanScore,
                    $row->certificate ?: '-',
                    $attendanceStr,
                    $row->attendance_reason ?: '-',
                    $row->bond_status ?: 'No',
                    $row->bond_end_date ? date('Y-m-d', strtotime($row->bond_end_date)) : '-',
                    $bondAmt,
                    $row->l1_program ?: '-',
                    $row->l2_numerical !== null ? number_format((float) $row->l2_numerical, 2) : '-',
                    $row->l2_verbal ?: '-',
                    $row->l3_numerical !== null ? number_format((float) $row->l3_numerical, 2) : '-',
                    $row->l3_verbal ?: '-',
                    $row->pic ?: '-',
                ];

                $isPlaylistRow = ($row->type === 'Playlist');
                $sAttr = $isPlaylistRow ? ' s="2"' : ' s="0"';

                $rowXml = "<row r=\"{$r}\"><c r=\"A{$r}\"{$sAttr}><v>{$rowCount}</v></c>";
                foreach ($data as $idx => $val) {
                    if ($val === '' || $val === null) {
                        $val = '-';
                    }
                    $colLetter = Coordinate::stringFromColumnIndex($idx + 2);
                    $escaped = $this->escapeXml($val);
                    $rowXml .= "<c r=\"{$colLetter}{$r}\" t=\"inlineStr\"{$sAttr}><is><t>{$escaped}</t></is></c>";
                }
                $rowXml .= "</row>\n";

                $buffer .= $rowXml;
                if ($rowCount % $batchSize === 0) {
                    fwrite($sfh, $buffer);
                    $buffer = '';
                }
            }

            if ($buffer !== '') {
                fwrite($sfh, $buffer);
                $buffer = '';
            }

            $stmt->closeCursor();
        } finally {
            $pdo->setAttribute(\PDO::MYSQL_ATTR_USE_BUFFERED_QUERY, $wasBuffered);
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

    protected function escapeXml($value): string
    {
        $clean = preg_replace('/[\x00-\x08\x0B\x0C\x0E-\x1F\x7F]/u', '', (string) $value);
        return htmlspecialchars($clean, ENT_XML1 | ENT_COMPAT, 'UTF-8');
    }
}
