<?php

namespace App\Exports\Manage;

use App\Exports\OnlineRecordExport;
use App\Services\Learning\LearningDatatableService;
use App\Services\Learning\LearningRecordRecognizedDurationService;
use App\Support\DurationFormatter;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use ZipStream\Option\Archive as ArchiveOptions;
use ZipStream\ZipStream;

class ClassificationOnlineRecordExport extends OnlineRecordExport
{
    protected ?string $classification;

    public function __construct(?string $classification = null, $filters = [], ?string $fileName = null)
    {
        $this->classification = !empty($classification) ? (string) $classification : null;
        $filterArr = $filters instanceof Request ? $filters->all() : (array) $filters;

        if ($this->classification !== null) {
            $filterArr['classification'] = $this->classification;
            $filterArr['classification_slug'] = $this->classification;
        }

        parent::__construct($filterArr, $fileName);
    }

    public function getClassification(): ?string
    {
        return $this->classification;
    }

    /**
     * Canonical 51 columns (A-AY) without Source.
     */
    public static function headers(): array
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
        ];
    }

    public function headings(): array
    {
        return self::headers();
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
            'J'  => 30,  // Sub Course Title
            'K'  => 15,  // Type
            'L'  => 18,  // Category
            'M'  => 12,  // Level
            'N'  => 12,  // Batch
            'O'  => 25,  // Main Classification
            'P'  => 25,  // Sub Classification
            'Q'  => 18,  // Delivery Method
            'R'  => 20,  // Sub Delivery Method
            'S'  => 16,  // Provider
            'T'  => 25,  // Sub Provider
            'U'  => 25,  // Provider Name
            'V'  => 20,  // Venue
            'W'  => 25,  // Instructor
            'X'  => 18,  // Course Duration
            'Y'  => 22,  // Total Recognized Duration
            'Z'  => 16,  // Learning Hours
            'AA' => 14,  // % Progress
            'AB' => 16,  // Progress Status
            'AC' => 14,  // Start Date
            'AD' => 14,  // End Date
            'AE' => 14,  // Start Time
            'AF' => 14,  // End Time
            'AG' => 16,  // Record Month
            'AH' => 16,  // Requirement
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
            'AX' => 35,  // Person in Charge (PIC)
            'AY' => 25,  // Total Engagement Time (LH)
        ];
    }

    public function map($row): array
    {
        $this->rowCount++;
        $isPlaylist = (($row->source_type ?? '') === 'playlist') || (!empty($row->trainingplan_id));

        // 1. Employee ID
        $rawEmp = $row->id_emp ?: ($row->user_id_emp ?? '-');
        $cleanEmpId = ($rawEmp !== '-') ? preg_replace('/\s*[\/\-]\s*NIP.*$/i', '', trim((string) $rawEmp)) : '-';

        // 2. Titles (Playlist: Course Title = Playlist Title, Sub Course Title = Content Title)
        if ($isPlaylist) {
            $courseTitle = $row->playlist_title ?: ($row->content_title ?: 'Playlist');
            $subCourseTitle = '-';
        } else {
            $courseTitle = $row->content_title ?: 'Untitled Content';
            $subCourseTitle = '-';
        }

        // 3. Type: Strictly Document or Video (YouTube maps to Video, never Playlist)
        $rawType = strtolower(trim((string) ($row->content_type ?? '')));
        $typeVal = in_array($rawType, ['video', 'youtube', 'mooc'], true) ? 'Video' : 'Document';

        // 4. Instructor: Team associated with content
        $instructorVal = trim((string) ($row->content_author ?? ($row->author ?? '')));
        if ($instructorVal === '' || $instructorVal === '-') {
            $instructorVal = 'Training Team';
        }

        // 5. Durations (Recognized Duration)
        $recognizedService = app(LearningRecordRecognizedDurationService::class);
        $recSec = $recognizedService->resolveFromRow($row);
        $recFormatted = $recognizedService->formatSeconds($recSec);
        $courseDurationFormatted = ($recFormatted !== '00:00:00') ? $recFormatted : '-';
        $totalRecognizedFormatted = ($recFormatted !== '00:00:00') ? $recFormatted : '-';

        // 6. Learning hours
        $lHoursVal = $row->content_training_hours ?? ($row->playlist_training_hours ?? ($row->learning_hours ?? null));
        $formattedLHours = self::formatHours($lHoursVal);

        // 7. Progress & Status
        $progressNum = (float) ($row->progress ?? 0);
        $progressDecimal = round($progressNum / 100, 4);
        $isFinished = ($row->finish == '1' || $progressNum >= 100);
        $progressStatus = $isFinished ? 'Finished' : ($progressNum > 0 ? 'Ongoing' : 'Not Started');

        // 8. Dates & Times (YYYY/MM/DD and HH:MM:SS)
        $createdAt = !empty($row->created_at) ? strtotime($row->created_at) : null;
        $completedAt = (!empty($row->completed_at) && $isFinished) ? strtotime($row->completed_at) : null;

        $startDateStr = $createdAt ? date('Y-m-d', $createdAt) : '-';
        $endDateStr = ($completedAt && $isFinished) ? date('Y-m-d', $completedAt) : '-';
        $startTimeStr = $createdAt ? date('H:i:s', $createdAt) : '-';
        $endTimeStr = ($completedAt && $isFinished) ? date('H:i:s', $completedAt) : '-';

        $recordMonthStr = ($completedAt && $isFinished) ? date('F', $completedAt) : ($createdAt ? date('F', $createdAt) : '-');

        // 9. Requirement & Attendance
        $statusRaw = strtolower(trim((string) ($row->status ?? 'non mandatory')));
        $requirementStr = ($statusRaw === 'mandatory') ? 'Mandatory' : 'Non-Mandatory';
        $attendanceStr = ($isFinished || $progressNum > 0) ? 'Yes' : 'No';

        // 10. Grade & Position
        $gradeVal = $row->user_jobgrade ?: ($row->jobgrade ?? '-');
        $jobPosVal = $row->user_jobposition ?: ($row->jobposition ?? '-');

        // 11. Scores & Certificates
        $scoreL2Val = (isset($row->score_l2) && is_numeric($row->score_l2)) ? (int) round((float) $row->score_l2) : '-';
        $certAvailability = $isFinished ? 'Available' : '-';
        $certInstitution = $isFinished ? 'PT Krakatau Posco' : '-';
        $certIssuedDate = ($isFinished && $endDateStr !== '-') ? $endDateStr : '-';

        // 12. Total Engagement Time from afk_duration_seconds
        $afkSec = (int) ($row->afk_duration_seconds ?? 0);
        $afkFormatted = $afkSec > 0 ? DurationFormatter::fromSeconds($afkSec) : '00:00:00';

        // 13. PIC: Admin Training Team Full Name
        $picName = 'MOHAMAD NAFFISYA KANCANA GUMILANG';

        return [
            $this->rowCount,                                                  // 1. NO
            $cleanEmpId,                                                      // 2. Employee ID
            $row->employee_name ?: ($row->user_name ?? '-'),                  // 3. Employee Name
            $gradeVal,                                                        // 4. Grade
            $jobPosVal,                                                       // 5. Job Position
            $row->user_team ?: '-',                                           // 6. Team
            $row->user_department ?: '-',                                     // 7. Department
            $row->user_company ?: ($row->company_name ?? 'PT Krakatau Posco'), // 8. Company
            $courseTitle,                                                     // 9. Course Title
            $subCourseTitle,                                                  // 10. Sub Course Title
            $typeVal,                                                         // 11. Type (Document / Video)
            $isPlaylist ? 'Playlist' : 'Content',                             // 12. Category
            '-',                                                              // 13. Level
            '-',                                                              // 14. Batch
            $row->classification_name ?: 'Unclassified',                      // 15. Main Classification
            '-',                                                              // 16. Sub Classification
            'Online',                                                         // 17. Delivery Method
            'E-learning',                                                     // 18. Sub Delivery Method
            'Internal',                                                       // 19. Provider
            'INTERNAL TRAINING TEAM',                                         // 20. Sub Provider
            'KP-OWL',                                                         // 21. Provider Name
            'Online LMS',                                                     // 22. Venue
            $instructorVal,                                                   // 23. Instructor (Team)
            $courseDurationFormatted,                                         // 24. Course Duration
            $totalRecognizedFormatted,                                        // 25. Total Recognized Duration
            $formattedLHours,                                                 // 26. Learning Hours
            $progressDecimal,                                                 // 27. % Progress
            $progressStatus,                                                  // 28. Progress Status
            $startDateStr,                                                    // 29. Start Date
            $endDateStr,                                                      // 30. End Date
            $startTimeStr,                                                    // 31. Start Time
            $endTimeStr,                                                      // 32. End Time
            $recordMonthStr,                                                  // 33. Record Month
            $requirementStr,                                                  // 34. Requirement
            $attendanceStr,                                                   // 35. Attendance
            '-',                                                              // 36. Attendance Reason
            '-',                                                              // 37. Bond Status
            '-',                                                              // 38. Bond Duration
            '-',                                                              // 39. Bond End Date
            '-',                                                              // 40. Bond Amount
            '-',                                                              // 41. Score (L1) - Course
            $scoreL2Val,                                                      // 42. Score (L2)
            '-',                                                              // 43. L2 Status
            '-',                                                              // 44. Score (L3)
            '-',                                                              // 45. L3 Status
            $certAvailability,                                                // 46. Certificate Availability
            $certInstitution,                                                 // 47. Certificate Institution
            $certIssuedDate,                                                  // 48. Certificate Issued Date
            '-',                                                              // 49. Certificate Expired Date
            $picName,                                                         // 50. Person in Charge (PIC)
            $afkFormatted,                                                    // 51. Total Engagement Time (LH)
        ];
    }

    /**
     * Backward-compatible collection accessor.
     */
    public function collection(): Collection
    {
        return $this->query()->get()->map(fn($row) => $this->map($row));
    }

    /**
     * High-performance XML streaming export for 51 canonical columns (A-AY)
     * strictly 1 row per user learning activity without child row multiplication.
     */
    public function generateXlsxFile(): string
    {
        ini_set('memory_limit', '2048M');
        ini_set('max_execution_time', 600);
        if (function_exists('set_time_limit')) {
            @set_time_limit(600);
        }

        $tempXlsx = tempnam(sys_get_temp_dir(), 'classification_online_record_') . '.xlsx';
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
        $sheetTitle = !empty($this->classification) ? 'Classification ' . ucwords(str_replace('-', ' ', $this->classification)) : 'Online Records';
        $cleanSheetTitle = $this->escapeXml(substr($sheetTitle, 0, 31));

        $zip->addFile(
            'xl/workbook.xml',
            '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            . '<workbook xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main" xmlns:r="http://schemas.openxmlformats.org/officeDocument/2006/relationships">'
            . '<sheets><sheet name="' . $cleanSheetTitle . '" sheetId="1" r:id="rId1"/></sheets>'
            . '</workbook>'
        );

        // 5. xl/styles.xml - Default Excel Normal Theme (clean, no custom colors or zebra striping)
        $zip->addFile(
            'xl/styles.xml',
            '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            . '<styleSheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main">'
            . '<numFmts count="4">'
            . '<numFmt numFmtId="164" formatCode="yyyy-mm-dd"/>'
            . '<numFmt numFmtId="165" formatCode="hh:mm:ss"/>'
            . '<numFmt numFmtId="166" formatCode="[h]:mm:ss"/>'
            . '<numFmt numFmtId="167" formatCode="0.0&quot; h&quot;"/>'
            . '</numFmts>'
            . '<fonts count="1">'
            . '<font><sz val="11"/><name val="Calibri"/><family val="2"/></font>'
            . '</fonts>'
            . '<fills count="2">'
            . '<fill><patternFill patternType="none"/></fill>'
            . '<fill><patternFill patternType="gray125"/></fill>'
            . '</fills>'
            . '<borders count="1">'
            . '<border><left/><right/><top/><bottom/><diagonal/></border>'
            . '</borders>'
            . '<cellStyleXfs count="1">'
            . '<xf numFmtId="0" fontId="0" fillId="0" borderId="0"/>'
            . '</cellStyleXfs>'
            . '<cellXfs count="7">'
            . '<xf numFmtId="0" fontId="0" fillId="0" borderId="0" xfId="0"/>'
            . '<xf numFmtId="1" fontId="0" fillId="0" borderId="0" xfId="0" applyNumberFormat="1"/>'
            . '<xf numFmtId="9" fontId="0" fillId="0" borderId="0" xfId="0" applyNumberFormat="1"/>'
            . '<xf numFmtId="164" fontId="0" fillId="0" borderId="0" xfId="0" applyNumberFormat="1"/>'
            . '<xf numFmtId="165" fontId="0" fillId="0" borderId="0" xfId="0" applyNumberFormat="1"/>'
            . '<xf numFmtId="166" fontId="0" fillId="0" borderId="0" xfId="0" applyNumberFormat="1"/>'
            . '<xf numFmtId="167" fontId="0" fillId="0" borderId="0" xfId="0" applyNumberFormat="1"/>'
            . '</cellXfs>'
            . '<cellStyles count="1">'
            . '<cellStyle name="Normal" xfId="0" builtinId="0"/>'
            . '</cellStyles>'
            . '</styleSheet>'
        );

        // 6. Temporary stream for sheet1.xml
        $sheetFile = tempnam(sys_get_temp_dir(), 'xlsx_classification_sheet_');
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

        // Header row (Row 1) - Standard Normal Theme
        $headers = self::headings();
        fwrite($sfh, '<row r="1">' . "\n");
        foreach ($headers as $i => $h) {
            $coord = Coordinate::stringFromColumnIndex($i + 1) . '1';
            $val = $this->escapeXml($h);
            fwrite($sfh, "<c r=\"{$coord}\" t=\"inlineStr\" s=\"0\"><is><t>{$val}</t></is></c>\n");
        }
        fwrite($sfh, '</row>' . "\n");

        $query = $this->query();
        $recognizedService = app(LearningRecordRecognizedDurationService::class);

        // Pre-query Training Team Admin PIC identity once to avoid N+1 queries
        $adminPic = DB::table('users')
            ->where('team', 'LIKE', '%Training Team%')
            ->where(function ($q) {
                $q->where('id_emp', '129458')->orWhere('name', 'LIKE', '%NAFFISYA%');
            })
            ->value('name') ?: 'MOHAMAD NAFFISYA KANCANA GUMILANG';

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

                // 1. Employee ID
                $rawEmp = $row->id_emp ?: ($row->user_id_emp ?? '-');
                $cleanEmpId = ($rawEmp !== '-') ? preg_replace('/\s*[\/\-]\s*NIP.*$/i', '', trim((string) $rawEmp)) : '-';

                // 2. Titles (Playlist: Course Title = Playlist Title, Sub Course Title = Content Title)
                if ($isPlaylist) {
                    $courseTitle = $row->playlist_title ?: ($row->content_title ?: 'Playlist');
                    $subCourseTitle = '-';
                } else {
                    $courseTitle = $row->content_title ?: 'Untitled Content';
                    $subCourseTitle = '-';
                }

                // 3. Type: Strictly Document or Video (YouTube maps to Video, never Playlist)
                $rawType = strtolower(trim((string) ($row->content_type ?? '')));
                $typeVal = in_array($rawType, ['video', 'youtube', 'mooc'], true) ? 'Video' : 'Document';

                // 4. Instructor: Team associated with content
                $instructorVal = trim((string) ($row->content_author ?? ($row->author ?? '')));
                if ($instructorVal === '' || $instructorVal === '-') {
                    $instructorVal = 'Training Team';
                }

                // 5. Durations (Course Duration & Total Recognized Duration: [h]:mm:ss)
                $recSec = $recognizedService->resolveFromRow($row);
                if ($recSec > 0) {
                    $courseDurType = 'dur';
                    $courseDurVal = $recSec / 86400.0;
                    $courseDurStyle = '5';
                } else {
                    $courseDurType = 'str';
                    $courseDurVal = '-';
                    $courseDurStyle = '0';
                }

                // 6. Learning hours (0.0" h")
                $lHoursVal = $row->content_training_hours ?? ($row->playlist_training_hours ?? ($row->learning_hours ?? null));
                if ($lHoursVal !== null && $lHoursVal !== '' && is_numeric($lHoursVal)) {
                    $lhType = 'lh';
                    $lhVal = (float) $lHoursVal;
                    $lhStyle = '6';
                } else {
                    $lhType = 'str';
                    $lhVal = '-';
                    $lhStyle = '0';
                }

                // 7. Progress & Status
                $progressNum = (float) ($row->progress ?? 0);
                $progressDecimal = round($progressNum / 100, 4);
                $isFinished = ($row->finish == '1' || $progressNum >= 100);
                $progressStatus = $isFinished ? 'Finished' : ($progressNum > 0 ? 'Ongoing' : 'Not Started');

                // 8. Dates & Times (yyyy/mm/dd and hh:mm:ss)
                $startDateSerial = null;
                $startTimeSerial = null;
                if (!empty($row->created_at) && strlen($row->created_at) >= 10) {
                    $cDate = substr($row->created_at, 0, 10);
                    $cParts = explode('-', $cDate);
                    if (count($cParts) === 3 && checkdate((int)$cParts[1], (int)$cParts[2], (int)$cParts[0])) {
                        $startDateSerial = 25569 + (int)(gmmktime(0, 0, 0, (int)$cParts[1], (int)$cParts[2], (int)$cParts[0]) / 86400);
                    }
                    if (strlen($row->created_at) >= 19) {
                        $ch = (int)substr($row->created_at, 11, 2);
                        $cm = (int)substr($row->created_at, 14, 2);
                        $cs = (int)substr($row->created_at, 17, 2);
                        $startTimeSerial = ($ch * 3600 + $cm * 60 + $cs) / 86400.0;
                    }
                }

                $endDateSerial = null;
                $endTimeSerial = null;
                if ($isFinished && !empty($row->completed_at) && strlen($row->completed_at) >= 10) {
                    $eDate = substr($row->completed_at, 0, 10);
                    $eParts = explode('-', $eDate);
                    if (count($eParts) === 3 && checkdate((int)$eParts[1], (int)$eParts[2], (int)$eParts[0])) {
                        $endDateSerial = 25569 + (int)(gmmktime(0, 0, 0, (int)$eParts[1], (int)$eParts[2], (int)$eParts[0]) / 86400);
                    }
                    if (strlen($row->completed_at) >= 19) {
                        $eh = (int)substr($row->completed_at, 11, 2);
                        $em = (int)substr($row->completed_at, 14, 2);
                        $es = (int)substr($row->completed_at, 17, 2);
                        $endTimeSerial = ($eh * 3600 + $em * 60 + $es) / 86400.0;
                    }
                }

                $recordMonthStr = '-';
                if ($completedAtTs = ($isFinished && !empty($row->completed_at) ? strtotime($row->completed_at) : null)) {
                    $recordMonthStr = date('F', $completedAtTs);
                } elseif (!empty($row->created_at)) {
                    $recordMonthStr = date('F', strtotime($row->created_at));
                }

                // 9. Requirement & Attendance
                $statusRaw = strtolower(trim((string) ($row->status ?? 'non mandatory')));
                $requirementStr = ($statusRaw === 'mandatory') ? 'Mandatory' : 'Non-Mandatory';
                $attendanceStr = ($isFinished || $progressNum > 0) ? 'Yes' : 'No';

                // 10. Grade & Position
                $gradeVal = $row->user_jobgrade ?: ($row->jobgrade ?? '-');
                $jobPosVal = $row->user_jobposition ?: ($row->jobposition ?? '-');

                // 11. Scores & Certificates
                if (isset($row->score_l2) && is_numeric($row->score_l2)) {
                    $scoreL2Type = 'int';
                    $scoreL2Val = (int) round((float) $row->score_l2);
                    $scoreL2Style = '1';
                } else {
                    $scoreL2Type = 'str';
                    $scoreL2Val = '-';
                    $scoreL2Style = '0';
                }

                $certAvailability = $isFinished ? 'Available' : '-';
                $certInstitution = $isFinished ? 'PT Krakatau Posco' : '-';

                // 12. Total Engagement Time from afk_duration_seconds ([h]:mm:ss)
                $afkSec = isset($row->afk_duration_seconds) && is_numeric($row->afk_duration_seconds) ? (int) $row->afk_duration_seconds : 0;
                $afkDurVal = $afkSec / 86400.0;

                // 51 Canonical Cells (A through AY) - Uniform default style
                $cells = [
                    ['A', 'num', $rowCount, '1'],                                                // 1. NO
                    ['B', 'str', $cleanEmpId, '0'],                                              // 2. Employee ID
                    ['C', 'str', $row->employee_name ?: ($row->user_name ?? '-'), '0'],         // 3. Employee Name
                    ['D', 'str', $gradeVal, '0'],                                                // 4. Grade
                    ['E', 'str', $jobPosVal, '0'],                                               // 5. Job Position
                    ['F', 'str', $row->user_team ?: '-', '0'],                                   // 6. Team
                    ['G', 'str', $row->user_department ?: '-', '0'],                             // 7. Department
                    ['H', 'str', $row->user_company ?: ($row->company_name ?? 'PT Krakatau Posco'), '0'], // 8. Company
                    ['I', 'str', $courseTitle, '0'],                                             // 9. Course Title
                    ['J', 'str', $subCourseTitle, '0'],                                          // 10. Sub Course Title
                    ['K', 'str', $typeVal, '0'],                                                 // 11. Type (Document / Video)
                    ['L', 'str', $isPlaylist ? 'Playlist' : 'Content', '0'],                     // 12. Category
                    ['M', 'str', '-', '0'],                                                      // 13. Level
                    ['N', 'str', '-', '0'],                                                      // 14. Batch
                    ['O', 'str', $row->classification_name ?: 'Unclassified', '0'],             // 15. Main Classification
                    ['P', 'str', '-', '0'],                                                      // 16. Sub Classification
                    ['Q', 'str', 'Online', '0'],                                                 // 17. Delivery Method
                    ['R', 'str', 'E-learning', '0'],                                             // 18. Sub Delivery Method
                    ['S', 'str', 'Internal', '0'],                                               // 19. Provider
                    ['T', 'str', 'INTERNAL TRAINING TEAM', '0'],                                 // 20. Sub Provider
                    ['U', 'str', 'KP-OWL', '0'],                                                 // 21. Provider Name
                    ['V', 'str', 'Online LMS', '0'],                                             // 22. Venue
                    ['W', 'str', $instructorVal, '0'],                                           // 23. Instructor (Team)
                    ['X', $courseDurType, $courseDurVal, $courseDurStyle],                       // 24. Course Duration
                    ['Y', $courseDurType, $courseDurVal, $courseDurStyle],                       // 25. Total Recognized Duration
                    ['Z', $lhType, $lhVal, $lhStyle],                                            // 26. Learning Hours
                    ['AA', 'pct', $progressDecimal, '2'],                                        // 27. % Progress
                    ['AB', 'str', $progressStatus, '0'],                                         // 28. Progress Status
                    ['AC', $startDateSerial !== null ? 'date' : 'str', $startDateSerial ?? '-', $startDateSerial !== null ? '3' : '0'], // 29. Start Date
                    ['AD', $endDateSerial !== null ? 'date' : 'str', $endDateSerial ?? '-', $endDateSerial !== null ? '3' : '0'],       // 30. End Date
                    ['AE', $startTimeSerial !== null ? 'time' : 'str', $startTimeSerial ?? '-', $startTimeSerial !== null ? '4' : '0'], // 31. Start Time
                    ['AF', $endTimeSerial !== null ? 'time' : 'str', $endTimeSerial ?? '-', $endTimeSerial !== null ? '4' : '0'],       // 32. End Time
                    ['AG', 'str', $recordMonthStr, '0'],                                         // 33. Record Month
                    ['AH', 'str', $requirementStr, '0'],                                         // 34. Requirement
                    ['AI', 'str', $attendanceStr, '0'],                                          // 35. Attendance
                    ['AJ', 'str', '-', '0'],                                                     // 36. Attendance Reason
                    ['AK', 'str', '-', '0'],                                                     // 37. Bond Status
                    ['AL', 'str', '-', '0'],                                                     // 38. Bond Duration
                    ['AM', 'str', '-', '0'],                                                     // 39. Bond End Date
                    ['AN', 'str', '-', '0'],                                                     // 40. Bond Amount
                    ['AO', 'str', '-', '0'],                                                     // 41. Score (L1) - Course
                    ['AP', $scoreL2Type, $scoreL2Val, $scoreL2Style],                            // 42. Score (L2)
                    ['AQ', 'str', '-', '0'],                                                     // 43. L2 Status
                    ['AR', 'str', '-', '0'],                                                     // 44. Score (L3)
                    ['AS', 'str', '-', '0'],                                                     // 45. L3 Status
                    ['AT', 'str', $certAvailability, '0'],                                       // 46. Certificate Availability
                    ['AU', 'str', $certInstitution, '0'],                                        // 47. Certificate Institution
                    ['AV', $endDateSerial !== null ? 'date' : 'str', $endDateSerial ?? '-', $endDateSerial !== null ? '3' : '0'],       // 48. Certificate Issued Date
                    ['AW', 'str', '-', '0'],                                                     // 49. Certificate Expired Date
                    ['AX', 'str', $adminPic, '0'],                                               // 50. Person in Charge (PIC)
                    ['AY', 'dur', $afkDurVal, '5'],                                              // 51. Total Engagement Time (LH)
                ];

                $buffer .= $this->buildXmlRow($r, $cells);

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

    /**
     * Build row XML for the 51 canonical columns with uniform default styling
     * and proper Excel numeric date/time/duration/percentage serialization.
     */
    protected function buildXmlRow(int $r, array $cells): string
    {
        $xml = "<row r=\"{$r}\">";
        foreach ($cells as $c) {
            [$colLetter, $type, $val, $styleId] = $c;
            $coord = "{$colLetter}{$r}";

            if ($val === null || $val === '' || $val === '-') {
                $xml .= "<c r=\"{$coord}\" t=\"inlineStr\" s=\"0\"><is><t>-</t></is></c>";
            } elseif ($type === 'pct') {
                $xml .= "<c r=\"{$coord}\" s=\"{$styleId}\"><v>{$val}</v></c>";
            } elseif ($type === 'lh') {
                $lhFloat = number_format((float) $val, 1, '.', '');
                $xml .= "<c r=\"{$coord}\" s=\"{$styleId}\"><v>{$lhFloat}</v></c>";
            } elseif ($type === 'dur') {
                $durFloat = number_format((float) $val, 10, '.', '');
                $xml .= "<c r=\"{$coord}\" s=\"{$styleId}\"><v>{$durFloat}</v></c>";
            } elseif ($type === 'date') {
                $dateInt = (int) $val;
                $xml .= "<c r=\"{$coord}\" s=\"{$styleId}\"><v>{$dateInt}</v></c>";
            } elseif ($type === 'time') {
                $timeFloat = number_format((float) $val, 10, '.', '');
                $xml .= "<c r=\"{$coord}\" s=\"{$styleId}\"><v>{$timeFloat}</v></c>";
            } elseif ($type === 'int' || $type === 'num') {
                $intVal = (int) $val;
                $xml .= "<c r=\"{$coord}\" s=\"{$styleId}\"><v>{$intVal}</v></c>";
            } else {
                $escaped = $this->escapeXml($val);
                $xml .= "<c r=\"{$coord}\" t=\"inlineStr\" s=\"0\"><is><t>{$escaped}</t></is></c>";
            }
        }
        $xml .= "</row>\n";
        return $xml;
    }
}
