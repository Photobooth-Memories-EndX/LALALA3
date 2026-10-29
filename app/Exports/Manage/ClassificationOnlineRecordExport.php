<?php

namespace App\Exports\Manage;

use App\Services\Learning\LearningDatatableService;
use App\Support\DurationFormatter;
use Carbon\Carbon;
use Illuminate\Contracts\Support\Responsable;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Concerns\Exportable;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithColumnFormatting;
use Maatwebsite\Excel\Concerns\WithColumnWidths;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\NumberFormat;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class ClassificationOnlineRecordExport implements FromCollection, WithHeadings, WithColumnWidths, WithColumnFormatting, WithStyles, WithEvents, Responsable
{
    use Exportable;

    protected ?string $classification;
    protected array $filters = [];
    protected string $fileName;

    public function __construct(?string $classification = null, $filters = [], ?string $fileName = null)
    {
        $this->classification = !empty($classification) ? (string) $classification : null;
        $this->filters = $filters instanceof Request ? $filters->all() : (array) $filters;

        if ($this->classification !== null) {
            $this->filters['classification'] = $this->classification;
            $this->filters['classification_slug'] = $this->classification;
        }

        $this->fileName = $fileName ?? self::generateFileName();
    }

    public static function generateFileName(): string
    {
        $monthMap = [
            1 => 'Jan', 2 => 'Feb', 3 => 'Mar', 4 => 'Apr',
            5 => 'Mei', 6 => 'Jun', 7 => 'Jul', 8 => 'Agu',
            9 => 'Sep', 10 => 'Okt', 11 => 'Nov', 12 => 'Des',
        ];
        $dt = Carbon::now();
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

    public function headings(): array
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
            'Recognized Duration',
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

    public function columnWidths(): array
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
            'W'  => 16,  // Recognized Duration
            'X'  => 16,  // Learning Hours
            'Y'  => 14,  // % Progress
            'Z'  => 16,  // Progress Status
            'AA' => 14,  // Start Date
            'AB' => 14,  // End Date
            'AC' => 14,  // Start Time
            'AD' => 14,  // End Time
            'AE' => 16,  // Record Month
            'AF' => 15,  // Requirement
            'AG' => 14,  // Attendance
            'AH' => 25,  // Attendance Reason
            'AI' => 14,  // Bond Status
            'AJ' => 16,  // Bond Duration
            'AK' => 15,  // Bond End Date
            'AL' => 18,  // Bond Amount
            'AM' => 20,  // Score (L1) - Course
            'AN' => 14,  // Score (L2)
            'AO' => 14,  // L2 Status
            'AP' => 14,  // Score (L3)
            'AQ' => 14,  // L3 Status
            'AR' => 22,  // Person in Charge (PIC)
            'AS' => 14,  // Certificate
            'AT' => 25,  // Total Engagement Time
        ];
    }

    public function columnFormats(): array
    {
        return [
            'B'  => NumberFormat::FORMAT_TEXT,
            'X'  => 'General" h"',
            'Y'  => '0%',
            'AA' => 'yyyy-mm-dd',
            'AB' => 'yyyy-mm-dd',
            'AC' => 'hh:mm:ss',
            'AD' => 'hh:mm:ss',
            'AL' => '"Rp"#,##0',
            'AN' => '0',
            'AT' => 'General" h"',
        ];
    }

    public function collection(): Collection
    {
        ini_set('memory_limit', '2048M');
        ini_set('max_execution_time', 600);
        if (function_exists('set_time_limit')) {
            @set_time_limit(600);
        }

        $query = LearningDatatableService::buildBaseQuery(
            $this->filters,
            (string) ($this->filters['type'] ?? 'category'),
            (string) ($this->classification ?? ($this->filters['slug'] ?? ''))
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

        $childrenMap = $this->getPlaylistChildrenMap();
        $rows = new Collection();
        $rowCount = 0;

        foreach ($query->cursor() as $row) {
            $rowCount++;
            $isPlaylist = (($row->source_type ?? '') === 'playlist') || (!empty($row->trainingplan_id));

            // Durations
            $durationFormatted = '-';
            if (!empty($row->content_duration)) {
                $durationFormatted = DurationFormatter::format($row->content_duration, '-');
            }

            // Learning hours
            $lHoursVal = $row->content_training_hours ?? ($row->playlist_training_hours ?? ($row->learning_hours ?? null));
            if ($lHoursVal !== null && $lHoursVal !== '' && is_numeric($lHoursVal)) {
                $hours = (float) $lHoursVal;
                $lHoursNumeric = ($hours == (int) $hours) ? (int) $hours : round($hours, 2);
            } else {
                $lHoursNumeric = '-';
            }

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

            // Score L2 integer
            $scoreL2Val = (isset($row->score_l2) && is_numeric($row->score_l2)) ? (int) round((float) $row->score_l2) : '-';

            // Employee ID: raw string/integer only
            $rawEmp = $row->id_emp ?: ($row->user_id_emp ?? '-');
            $cleanEmpId = ($rawEmp !== '-') ? preg_replace('/\s*[\/\-]\s*NIP.*$/i', '', trim((string) $rawEmp)) : '-';

            // Titles & Classification
            $courseTitle = $isPlaylist ? ($row->playlist_title ?: 'Playlist') : ($row->content_title ?: 'Untitled Content');
            $mainClass = $row->classification_name ?: 'Unclassified';

            $recognizedService = app(\App\Services\Learning\LearningRecordRecognizedDurationService::class);
            $recSec = $recognizedService->resolveFromRow($row);
            $recognizedFormatted = $recognizedService->formatSeconds($recSec);

            $gradeVal = $row->user_jobgrade ?: ($row->jobgrade ?? '-');
            $jobPosVal = $row->user_jobposition ?: ($row->jobposition ?? '-');

            $afkSec = (int) ($row->afk_duration_seconds ?? 0);
            $afkFormatted = $afkSec > 0 ? DurationFormatter::fromSeconds($afkSec) : ($row->afk_duration_seconds !== null ? '00:00:00' : '-');

            $rows->push([
                $rowCount,
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
                $recognizedFormatted, // Recognized Duration
                $lHoursNumeric,
                $progressDecimal, // numeric decimal for 0% format code
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
                $scoreL2Val, // Score L2 (integer)
                '-', // L2 Status
                '-', // Score L3
                '-', // L3 Status
                '-', // PIC
                '-', // Certificate
                $afkFormatted, // Total Engagement Time
            ]);

            // Expand Child Contents for Playlist
            if ($isPlaylist) {
                $tpId = (int) ($row->trainingplan_id ?? 0);
                if ($tpId > 0 && isset($childrenMap[$tpId])) {
                    foreach ($childrenMap[$tpId] as $child) {
                        $childDuration = !empty($child->duration) ? DurationFormatter::format($child->duration, '-') : '-';
                        $childClass = $child->classification_name ?: '-';

                        $rows->push([
                            '-',
                            $cleanEmpId,
                            $row->employee_name ?: ($row->user_name ?? '-'),
                            $gradeVal,
                            $jobPosVal,
                            $row->user_team ?: '-',
                            $row->user_department ?: '-',
                            $row->user_company ?: '-',
                            '    ' . $child->title, // Indented title
                            'Child of ' . $courseTitle,
                            ucfirst($child->type ?: 'Content'),
                            'Content Playlist',
                            '-',
                            '-',
                            $childClass,
                            '-',
                            'Online',
                            'E-learning',
                            'KP-OWL',
                            'KP-OWL',
                            $child->author ?: '-',
                            $childDuration,
                            '-', // Recognized Duration on child
                            '-', // '-' on child to prevent double counting
                            '-',
                            '-',
                            '-',
                            '-',
                            '-',
                            '-',
                            '-',
                            '-',
                            '-',
                            '-',
                            '-',
                            '-',
                            '-',
                            '-',
                            '-',
                            '-',
                            '-',
                            '-',
                            '-',
                            '-',
                            '-',
                            '-', // Total Engagement Time - child is '-'
                        ]);
                    }
                }
            }
        }

        return $rows;
    }

    public function map($row): array
    {
        if (is_array($row)) {
            return $row;
        }

        $durationFormatted = '-';
        if (!empty($row->content_duration)) {
            $durationFormatted = DurationFormatter::format($row->content_duration, '-');
        }

        $lHoursVal = $row->content_training_hours ?? ($row->playlist_training_hours ?? ($row->learning_hours ?? null));
        $lHoursNumeric = ($lHoursVal !== null && $lHoursVal !== '') ? (float) $lHoursVal : '-';

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

        $isPlaylist = (($row->source_type ?? '') === 'playlist') || (!empty($row->trainingplan_id));
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
            1,
            $cleanEmpId,
            $row->employee_name ?: ($row->user_name ?? '-'),
            $gradeVal,
            $jobPosVal,
            $row->user_team ?: '-',
            $row->user_department ?: '-',
            $row->user_company ?: '-',
            $courseTitle,
            '-',
            $isPlaylist ? 'Playlist' : ($row->content_type ?: 'Content'),
            $isPlaylist ? 'Playlist' : 'Content',
            '-',
            '-',
            $mainClass,
            '-',
            'Online',
            'E-learning',
            'KP-OWL',
            'KP-OWL',
            ($row->content_author ?? ($row->author ?? '-')) ?: '-',
            $durationFormatted,
            $recognizedFormatted, // Recognized Duration
            $lHoursNumeric,
            $progressDecimal,
            $progressStatus,
            $startDateStr,
            $endDateStr,
            $startTimeStr,
            $endTimeStr,
            $recordMonthStr,
            'Mandatory',
            $attendanceStr,
            '-',
            'No',
            '-',
            '-',
            '-',
            '-',
            $scoreL2Val,
            '-',
            '-',
            '-',
            '-',
            '-',
            $afkFormatted,
        ];
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

            $map = [];
            foreach ($items as $item) {
                $map[$item->trainingplan_id][] = $item;
            }

            return $map;
        } catch (\Throwable $e) {
            return [];
        }
    }

    public function styles(Worksheet $sheet)
    {
        return [
            1 => [
                'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
                'fill' => [
                    'fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,
                    'startColor' => ['rgb' => '002B49'],
                ],
                'alignment' => [
                    'horizontal' => Alignment::HORIZONTAL_CENTER,
                    'vertical' => Alignment::VERTICAL_CENTER,
                    'wrapText' => true,
                ],
            ],
        ];
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event) {
                $sheet = $event->sheet->getDelegate();
                $highestColumn = $sheet->getHighestColumn();
                $highestRow = $sheet->getHighestRow();

                $sheet->getRowDimension(1)->setRowHeight(28);

                $sheet->getStyle('A1:' . $highestColumn . $highestRow)
                    ->getAlignment()
                    ->setHorizontal(Alignment::HORIZONTAL_CENTER)
                    ->setVertical(Alignment::VERTICAL_CENTER);
            },
        ];
    }
}
