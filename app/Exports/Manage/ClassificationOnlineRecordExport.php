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
            'AS' => 25,  // Total Engagement Time
        ];
    }

    public function columnFormats(): array
    {
        return [
            'B'  => NumberFormat::FORMAT_TEXT,
            'W'  => 'General" h"',
            'X'  => '0%',
            'Z'  => 'yyyy-mm-dd',
            'AA' => 'yyyy-mm-dd',
            'AB' => 'hh:mm:ss',
            'AC' => 'hh:mm:ss',
            'AK' => '"Rp"#,##0',
            'AM' => '0',
            'AS' => 'General" h"',
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

            $rows->push([
                $rowCount,
                $cleanEmpId,
                $row->employee_name ?: ($row->user_name ?? '-'),
                '-', // Grade
                '-', // Job Position
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
                $lHoursNumeric, // Total Engagement Time
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
                            '-',
                            '-',
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

        return [
            1,
            $cleanEmpId,
            $row->employee_name ?: ($row->user_name ?? '-'),
            '-',
            '-',
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
            $lHoursNumeric,
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
