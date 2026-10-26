<?php

namespace App\Exports\Manage;

use App\Models\OfflineTraining;
use App\Services\Record\RecordOfflineQueryService;
use Carbon\Carbon;
use Illuminate\Contracts\Support\Responsable;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\Exportable;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithColumnWidths;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class ClassificationOfflineRecordExport implements FromCollection, WithHeadings, WithColumnWidths, WithStyles, WithEvents, Responsable
{
    use Exportable;

    protected ?string $classification;
    protected array $filters = [];
    protected string $fileName;

    public function __construct(?string $classification = null, $filters = [], ?string $fileName = null)
    {
        $this->classification = !empty($classification) ? (string) $classification : null;
        $this->filters = $filters instanceof Request ? $filters->all() : (array) $filters;
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

        return "EmployeeNonOWLrecord_{$day}{$month}{$year} at {$time}.xlsx";
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
            'AS' => 25,  // Total Engagement Time (LH)
        ];
    }

    public function collection(): Collection
    {
        ini_set('memory_limit', '2048M');
        ini_set('max_execution_time', 600);
        if (function_exists('set_time_limit')) {
            @set_time_limit(600);
        }

        if ($this->classification !== null) {
            $query = RecordOfflineQueryService::buildClassificationQuery($this->classification, $this->filters)
                ->orderBy('ot.start_date', 'desc')
                ->orderBy('ot.id', 'desc')
                ->orderBy('otu.id', 'asc');
        } else {
            $query = RecordOfflineQueryService::buildGlobalQuery($this->filters)
                ->orderBy('ot.start_date', 'desc')
                ->orderBy('ot.id', 'desc')
                ->orderBy('otu.id', 'asc');
        }

        $rows = new Collection();
        $rowCount = 0;

        foreach ($query->cursor() as $row) {
            $rowCount++;

            // Duration formatted as HH:MM:SS
            $durationFormatted = '-';
            if (!empty($row->duration_seconds)) {
                $secs = (int) $row->duration_seconds;
                $hours = floor($secs / 3600);
                $mins = floor(($secs % 3600) / 60);
                $seconds = $secs % 60;
                $durationFormatted = sprintf('%02d:%02d:%02d', $hours, $mins, $seconds);
            }

            // Learning Hours
            $lHoursStr = ($row->course_learning_hours !== null && $row->course_learning_hours !== '')
                ? (number_format((float) $row->course_learning_hours, 2) . ' h')
                : '-';

            // Progress & Status
            $progressNum = (float) ($row->progress ?? 0);
            $progressPercentStr = round($progressNum) . '%';
            $progressStatus = ($progressNum >= 100) ? 'Finished' : (($progressNum > 0) ? 'Ongoing' : 'Not Started');

            // Date & Time split
            $startDateStr = !empty($row->start_date) ? date('Y-m-d', strtotime($row->start_date)) : '-';
            $endDateStr = !empty($row->end_date) ? date('Y-m-d', strtotime($row->end_date)) : '-';
            $startTimeStr = !empty($row->start_time) ? date('H:i:s', strtotime($row->start_time)) : '-';
            $endTimeStr = !empty($row->end_time) ? date('H:i:s', strtotime($row->end_time)) : '-';

            $recordMonthDate = $row->start_date ?: ($row->end_date ?? null);
            $recordMonthStr = !empty($recordMonthDate) ? date('F', strtotime($recordMonthDate)) : '-';

            // Attendance: strictly Yes or No
            $attendanceStr = '-';
            if ($row->attendance !== null && $row->attendance !== '') {
                $rawAtt = strtolower((string) $row->attendance);
                $attendanceStr = ((bool) $row->attendance || in_array($rawAtt, ['1', 'yes', 'present', 'true'], true)) ? 'Yes' : 'No';
            }

            // Delivery method alignment
            $delivMethod = OfflineTraining::normalizeDeliveryMethod($row->delivery_method ?: 'Offline');
            $subDelivMethod = $row->sub_delivery_method ?: 'On-site';

            // Bond amount
            $idr = $row->bond_amount_idr ?? $row->bond_amount ?? null;
            $hasBond = ($idr !== null && (float) $idr > 0);
            $bondAmtStr = $hasBond ? 'Rp ' . number_format((float) $idr, 0, ',', '.') : '-';

            // Scores
            $scoreL1 = is_numeric($row->l1_program) ? (float) $row->l1_program : null;
            $scoreL2 = is_numeric($row->assessment_score)
                ? (int) round((float) $row->assessment_score)
                : (is_numeric($row->l2_numerical) ? (int) round((float) $row->l2_numerical) : null);
            $scoreL3 = is_numeric($row->l3_numerical) ? (float) $row->l3_numerical : null;

            $totalEngagementStr = ($row->learning_hours !== null && $row->learning_hours !== '')
                ? (number_format((float) $row->learning_hours, 2) . ' h')
                : '-';

            $rows->push([
                $rowCount,
                $row->employee_id ?: '-',
                $row->participant_name ?: ($row->user_name ?? '-'),
                $row->grade ?: '-',
                $row->job_position ?: '-',
                $row->team ?: '-',
                $row->department ?: '-',
                $row->company ?: '-',
                $row->course_name ?: ($row->title ?: '-'),
                $row->sub_course_name ?: '-',
                $row->type ?: '-',
                $row->category ?: '-',
                $row->level ?: '-',
                $row->batch ?: '-',
                $row->main_classification ?: 'Unclassified',
                $row->sub_classification ?: '-',
                $delivMethod,
                $subDelivMethod,
                $row->provider_name ?: ($row->provider ?: '-'),
                $row->venue ?: '-',
                $row->instructor ?: '-',
                $durationFormatted,
                $lHoursStr,
                $progressPercentStr,
                $progressStatus,
                $startDateStr,
                $endDateStr,
                $startTimeStr,
                $endTimeStr,
                $recordMonthStr,
                $row->requirement ?: 'Mandatory',
                $attendanceStr,
                $row->attendance_reason ?: '-',
                $row->bond_status ?: 'No',
                !empty($row->bond_duration) ? $row->bond_duration . ' Months' : '-',
                !empty($row->bond_end_date) ? date('Y-m-d', strtotime($row->bond_end_date)) : '-',
                $bondAmtStr,
                $scoreL1 !== null ? number_format($scoreL1, 2) : '-',
                $scoreL2 !== null ? (int) $scoreL2 : '-', // strictly integer
                $row->l2_verbal ?: ($scoreL2 !== null ? ($scoreL2 >= 70 ? 'Passed' : 'Failed') : '-'),
                $scoreL3 !== null ? number_format($scoreL3, 2) : '-',
                $row->l3_verbal ?: '-',
                $row->pic ?: '-',
                $row->certificate ?: '-',
                $totalEngagementStr, // Total Engagement Time (LH)
            ]);
        }

        return $rows;
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
