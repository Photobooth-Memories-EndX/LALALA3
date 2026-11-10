<?php

namespace App\Exports;

use App\Http\Controllers\Manage\Record\RecordAllController;
use App\Services\Record\RecordAllCombinedService;
use Illuminate\Contracts\Support\Responsable;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\WithChunkReading;
use Maatwebsite\Excel\Concerns\WithColumnWidths;
use Maatwebsite\Excel\Concerns\WithHeadings;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use ZipStream\Option\Archive as ArchiveOptions;
use ZipStream\ZipStream;

class AllParticipantExport implements FromQuery, WithHeadings, WithChunkReading, WithColumnWidths, Responsable
{
    protected array $participant;
    protected array $filters = [];
    protected int $rowNumber = 0;
    protected string $fileName;

    public const CANONICAL_52_COLUMNS = [
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

    public function __construct(array $participant, $filters = [])
    {
        $this->participant = $participant;
        $this->filters = $filters instanceof Request ? $filters->all() : (array) $filters;
        $this->rowNumber = 0;

        $identifier = $participant['employee_id'] ?: ($participant['name'] ?: 'participant');
        $slug = Str::slug($identifier) ?: 'participant';
        $this->fileName = sprintf('all-kp-owl-participant-%s-%s.xlsx', $slug, now()->format('Y-m-d'));
    }

    public function getFileName(): string
    {
        return $this->fileName;
    }

    public function query()
    {
        $service = app(RecordAllCombinedService::class);
        $queryFilters = $this->filters;

        if (!empty($queryFilters['classification']) && strtolower($queryFilters['classification']) !== 'all') {
            $resolvedClass = RecordAllController::resolveClassification($queryFilters['classification'], false);
            $queryFilters['classification'] = $resolvedClass['is_unclassified'] ? 'Unclassified' : $resolvedClass['name'];
        }

        return $service->buildParticipantCombinedQuery($this->participant, $queryFilters)
            ->orderBy('raw_finish_date', 'desc')
            ->orderBy('record_date', 'desc');
    }

    public function headings(): array
    {
        return self::CANONICAL_52_COLUMNS;
    }

    public function chunkSize(): int
    {
        return 2000;
    }

    public function columnWidths(): array
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
            'AZ' => 24,  // Total Engagement Time (LH)
        ];
    }

    /**
     * Download the generated XLSX file as an HTTP response.
     */
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

    /**
     * Fulfill Responsable interface.
     */
    public function toResponse($request)
    {
        return $this->download();
    }

    /**
     * Generate XLSX file using zero-materialization unbuffered streaming.
     */
    public function generateXlsxFile(): string
    {
        $tempXlsx = tempnam(sys_get_temp_dir(), 'all_participant_') . '.xlsx';
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
            . '<sheets><sheet name="Participant Records" sheetId="1" r:id="rId1"/></sheets>'
            . '</workbook>'
        );

        // 5. xl/styles.xml with bold white text on #002B49 dark blue header
        $zip->addFile(
            'xl/styles.xml',
            '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            . '<styleSheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main">'
            . '<fonts count="2">'
            . '<font><sz val="10"/><name val="Calibri"/></font>'
            . '<font><b/><sz val="10"/><color rgb="FFFFFFFF"/><name val="Calibri"/></font>'
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
            . '<cellXfs count="2">'
            . '<xf numFmtId="0" fontId="0" fillId="0" borderId="0" xfId="0"/>'
            . '<xf numFmtId="0" fontId="1" fillId="2" borderId="0" xfId="0" applyFont="1" applyFill="1" applyAlignment="1"><alignment horizontal="center" vertical="center"/></xf>'
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

        // Header row (Row 1)
        $headers = $this->headings();
        fwrite($sfh, '<row r="1" ht="28" customHeight="1">' . "\n");
        foreach ($headers as $i => $h) {
            $coord = Coordinate::stringFromColumnIndex($i + 1) . '1';
            $val = $this->escapeXml($h);
            fwrite($sfh, "<c r=\"{$coord}\" t=\"inlineStr\" s=\"1\"><is><t>{$val}</t></is></c>\n");
        }
        fwrite($sfh, '</row>' . "\n");

        // Pre-fetch certificates for this participant to avoid N+1 queries
        $userCerts = [];
        if (!empty($this->participant['user_id'])) {
            $cRows = DB::table('certificates as c')
                ->leftJoin('certificate_settings as cs', 'cs.id', '=', 'c.certificate_setting_id')
                ->where('c.user_id', $this->participant['user_id'])
                ->select('c.content_user_id', 'c.offline_training_user_id', 'cs.issuer_name', 'c.institution', 'c.issued_at', 'c.issued_date', 'c.expired_date')
                ->get();
            foreach ($cRows as $cr) {
                if (!empty($cr->content_user_id)) {
                    $userCerts['content:' . $cr->content_user_id] = $cr;
                }
                if (!empty($cr->offline_training_user_id)) {
                    $userCerts['offline:' . $cr->offline_training_user_id] = $cr;
                }
            }
        }

        // Single unbuffered streaming query execution
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

                // Total Engagement Duration (LH) formatted as HH:MM:SS
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
                    : '-';

                // Progress & Status
                $progressNum = (float) ($row->progress ?? 0);
                $progressFormatted = round($progressNum, 2) . '%';
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

                // Attendance: strictly Yes or No
                $attendanceStr = '-';
                if ($row->attendance !== null && $row->attendance !== '') {
                    $rawAtt = strtolower(trim((string) $row->attendance));
                    $attendanceStr = in_array($rawAtt, ['1', 'yes', 'present', 'true', 'hadir', 'completed', 'finished'], true) ? 'Yes' : 'No';
                } elseif ($row->source === 'online') {
                    $attendanceStr = ($progressNum >= 100) ? 'Yes' : (($progressNum > 0) ? 'No' : '-');
                }

                // Bond details
                $idr = $row->bond_amount_idr ?? $row->bond_amount ?? null;
                $hasBond = ($idr !== null && (float) $idr > 0);
                $bondAmtStr = $hasBond ? 'Rp ' . number_format((float) $idr, 0, ',', '.') : '-';

                // Scores
                $scoreL1 = is_numeric($row->l1_program) ? (float) $row->l1_program : (!empty($row->l1_program) ? $row->l1_program : '-');
                $scoreL2 = is_numeric($row->assessment_score)
                    ? (int) round((float) $row->assessment_score)
                    : (is_numeric($row->l2_numerical) ? (int) round((float) $row->l2_numerical) : '-');
                $scoreL3 = is_numeric($row->l3_numerical) ? (float) $row->l3_numerical : '-';

                $l2Status = $row->l2_verbal ?: (is_numeric($scoreL2) ? ($scoreL2 >= 70 ? 'Passed' : 'Failed') : '-');
                $l3Status = $row->l3_verbal ?: '-';

                // Clean Employee ID
                $rawEmp = $row->employee_id ?: '-';
                $cleanEmpId = ($rawEmp !== '-') ? preg_replace('/\s*[\/\-]\s*NIP.*$/i', '', trim((string) $rawEmp)) : '-';

                // Certificates
                $hasCert = false;
                $certInstitution = '-';
                $certIssuedDate = '-';
                $certExpiredDate = '-';

                $certKey = ($row->source === 'offline') ? ('offline:' . ($row->source_id ?? '')) : ('content:' . ($row->source_id ?? ''));
                if (isset($userCerts[$certKey])) {
                    $cr = $userCerts[$certKey];
                    $hasCert = true;
                    if (!empty($cr->issuer_name)) $certInstitution = $cr->issuer_name;
                    elseif (!empty($cr->institution)) $certInstitution = $cr->institution;
                    if (!empty($cr->issued_date)) $certIssuedDate = date('Y-m-d', strtotime($cr->issued_date));
                    elseif (!empty($cr->issued_at)) $certIssuedDate = date('Y-m-d', strtotime($cr->issued_at));
                    if (!empty($cr->expired_date)) $certExpiredDate = date('Y-m-d', strtotime($cr->expired_date));
                } elseif ($row->source === 'offline') {
                    if (!empty($row->certificate) && $row->certificate !== '-') {
                        $hasCert = true;
                        $certInstitution = $row->certificate;
                    }
                    if (!empty($row->certificate_issued_date) && $row->certificate_issued_date !== '-') {
                        $certIssuedDate = date('Y-m-d', strtotime($row->certificate_issued_date));
                    }
                    if (!empty($row->certificate_expired_date) && $row->certificate_expired_date !== '-') {
                        $certExpiredDate = date('Y-m-d', strtotime($row->certificate_expired_date));
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

                // The 51 data columns (Cols B through AZ) matching the 52 canonical headings (Col A is rowCount)
                $data = [
                    $row->source === 'offline' ? 'Non KP OWL' : 'KP OWL',
                    $cleanEmpId,
                    $row->employee_name ?: '-',
                    $row->grade ?: '-',
                    $row->job_position ?: '-',
                    $row->team ?: '-',
                    $row->department ?: '-',
                    $row->company ?: '-',
                    $row->learning_title ?: '-',
                    $row->sub_course_name ?: '-',
                    ($row->source === 'offline' || !$isKpOwl) ? '-' : ($row->type ?: '-'),
                    $row->category ?: '-',
                    $row->level ?: '-',
                    $row->batch ?: '-',
                    $row->classification ?: 'Unclassified',
                    $row->sub_classification ?: '-',
                    $delivMethod,
                    $subDelivMethod,
                    $row->source === 'online' ? 'Internal' : ($row->provider ?: '-'),
                    $row->source === 'online' ? '-' : ($row->sub_provider ?: '-'),
                    $row->provider_name ?: ($row->source === 'offline' ? '-' : 'KP-OWL'),
                    $row->venue ?: ($row->source === 'offline' ? '-' : 'Online LMS'),
                    $row->instructor ?: '-',
                    $durationFormatted,
                    $row->source === 'offline' ? '-' : $recognizedFormatted,
                    $lhNumeric,
                    $progressFormatted,
                    $progressStatus,
                    $startDateStr,
                    $endDateStr,
                    $startTimeStr,
                    $endTimeStr,
                    $finalRecordMonth,
                    $row->requirement ?: 'Mandatory',
                    $attendanceStr,
                    $row->attendance_reason ?: '-',
                    $row->bond_status ?: 'No',
                    !empty($row->bond_duration) ? $row->bond_duration . ' Months' : '-',
                    !empty($row->bond_end_date) ? date('Y-m-d', strtotime($row->bond_end_date)) : '-',
                    $bondAmtStr,
                    $scoreL1,
                    $scoreL2,
                    $l2Status,
                    $scoreL3,
                    $l3Status,
                    $certAvailability,
                    $certInstitution,
                    $certIssuedDate,
                    $certExpiredDate,
                    $row->pic ?: '-',
                    $totalEngagementDuration,
                ];

                $buffer .= "<row r=\"{$r}\">\n";
                // Col 1: A (NO)
                $buffer .= "<c r=\"A{$r}\"><v>{$rowCount}</v></c>\n";

                foreach ($data as $colIdx => $val) {
                    $letter = Coordinate::stringFromColumnIndex($colIdx + 2);
                    $coord = "{$letter}{$r}";
                    if ($val === '' || $val === null || $val === '-') {
                        $buffer .= "<c r=\"{$coord}\" t=\"inlineStr\"><is><t>-</t></is></c>\n";
                    } elseif (is_numeric($val) && !preg_match('/^0\d+/', (string)$val)) {
                        $buffer .= "<c r=\"{$coord}\"><v>{$val}</v></c>\n";
                    } else {
                        $escapedVal = $this->escapeXml((string)$val);
                        $buffer .= "<c r=\"{$coord}\" t=\"inlineStr\"><is><t>{$escapedVal}</t></is></c>\n";
                    }
                }
                $buffer .= "</row>\n";

                if ($rowCount % $batchSize === 0) {
                    fwrite($sfh, $buffer);
                    $buffer = '';
                }
            }

            if ($buffer !== '') {
                fwrite($sfh, $buffer);
                $buffer = '';
            }
        } finally {
            $pdo->setAttribute(\PDO::MYSQL_ATTR_USE_BUFFERED_QUERY, $wasBuffered);
        }

        fwrite($sfh, '</sheetData>' . "\n");
        fwrite($sfh, '</worksheet>' . "\n");
        fclose($sfh);

        // Add sheet1.xml to zip from stream
        $sheetFh = fopen($sheetFile, 'rb');
        $zip->addFileFromStream('xl/worksheets/sheet1.xml', $sheetFh);
        fclose($sheetFh);
        @unlink($sheetFile);

        $zip->finish();
        fclose($fh);

        return $tempXlsx;
    }

    protected function escapeXml(string $string): string
    {
        return htmlspecialchars($string, ENT_XML1 | ENT_QUOTES, 'UTF-8');
    }
}
