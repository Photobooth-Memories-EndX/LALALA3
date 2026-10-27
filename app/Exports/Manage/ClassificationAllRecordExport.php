<?php

namespace App\Exports\Manage;

use App\Models\OfflineTraining;
use App\Services\Record\RecordAllCombinedService;
use Carbon\Carbon;
use Illuminate\Contracts\Support\Responsable;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
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

class ClassificationAllRecordExport implements FromCollection, WithHeadings, WithColumnWidths, WithColumnFormatting, WithStyles, WithEvents, Responsable
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

        return "EmployeeAllrecord_{$day}{$month}{$year} at {$time}.xlsx";
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
            'Provider Name',
            'Venue',
            'Instructor',
            'Course Duration',
            'Total Engagement Duration',
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
            'Certificate',
            'Total Engagement Time (LH)',
        ];
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
            'T'  => 25,  // Provider Name
            'U'  => 20,  // Venue
            'V'  => 22,  // Instructor
            'W'  => 16,  // Course Duration
            'X'  => 22,  // Total Engagement Duration
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
            'AM' => 15,  // Bond End Date
            'AN' => 18,  // Bond Amount
            'AO' => 20,  // Score (L1) - Course
            'AP' => 14,  // Score (L2)
            'AQ' => 14,  // L2 Status
            'AR' => 14,  // Score (L3)
            'AS' => 14,  // L3 Status
            'AT' => 22,  // Person in Charge (PIC)
            'AU' => 14,  // Certificate
            'AV' => 25,  // Total Engagement Time (LH)
        ];
    }

    public function columnFormats(): array
    {
        return [
            'A'  => '0',
            'C'  => NumberFormat::FORMAT_TEXT,
            'Y'  => '0.##" h"',
            'Z'  => '0.##" h"',
            'AA' => '0%',
            'AC' => 'yyyy-mm-dd',
            'AD' => 'yyyy-mm-dd',
            'AE' => 'hh:mm:ss',
            'AF' => 'hh:mm:ss',
            'AM' => 'yyyy-mm-dd',
            'AN' => '"Rp"#,##0',
            'AO' => '0.##',
            'AP' => '0',
            'AR' => '0.##',
            'AV' => '0.##" h"',
        ];
    }

    public function collection(): Collection
    {
        ini_set('memory_limit', '2048M');
        ini_set('max_execution_time', 600);
        if (function_exists('set_time_limit')) {
            @set_time_limit(600);
        }

        $service = app(RecordAllCombinedService::class);
        $queryFilters = $this->filters;
        if ($this->classification !== null) {
            $queryFilters['classification'] = $this->classification;
        }

        $query = $service->buildCombinedQuery($queryFilters)
            ->orderBy('raw_finish_date', 'desc')
            ->orderBy('record_date', 'desc');

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

            // Learning Hours (raw numeric float for SUM calculations)
            $lhNumeric = ($row->learning_hours !== null && $row->learning_hours !== '' && is_numeric($row->learning_hours))
                ? (float) $row->learning_hours
                : '-';

            // Progress & Status
            $progressNum = (float) ($row->progress ?? 0);
            $progressDecimal = round($progressNum / 100, 4);
            $progressStatus = ($progressNum >= 100 || ($row->status ?? '') === 'Finished') ? 'Finished' : (($progressNum > 0) ? 'Ongoing' : 'Not Started');

            // Delivery method alignment
            $delivMethod = OfflineTraining::normalizeDeliveryMethod($row->delivery_method ?: ($row->source === 'offline' ? 'Offline' : 'Online'));
            $subDelivMethod = $row->sub_delivery_method ?: ($row->source === 'offline' ? 'On-site' : 'E-learning');

            // Date & Time split
            $startDateStr = !empty($row->start_date) ? date('Y-m-d', strtotime($row->start_date)) : '-';
            $endDateStr = !empty($row->end_date)
                ? date('Y-m-d', strtotime($row->end_date))
                : (!empty($row->finish_date) ? date('Y-m-d', strtotime($row->finish_date)) : '-');

            $startTimeStr = !empty($row->start_time) ? date('H:i:s', strtotime($row->start_time)) : '-';
            $endTimeStr = !empty($row->end_time) ? date('H:i:s', strtotime($row->end_time)) : '-';

            $recordMonthDate = $row->start_date ?: ($row->finish_date ?: ($row->record_date ?? null));
            $recordMonthStr = !empty($recordMonthDate) ? date('F', strtotime($recordMonthDate)) : '-';

            // Attendance: strictly Yes or No
            $attendanceStr = '-';
            if ($row->attendance !== null && $row->attendance !== '') {
                $rawAtt = strtolower(trim((string) $row->attendance));
                $attendanceStr = in_array($rawAtt, ['1', 'yes', 'present', 'true', 'hadir', 'completed', 'finished'], true) ? 'Yes' : 'No';
            } elseif ($row->source === 'online') {
                $attendanceStr = ($progressNum >= 100) ? 'Yes' : (($progressNum > 0) ? 'No' : '-');
            }

            // Bond amount formatting
            $idr = $row->bond_amount_idr ?? $row->bond_amount ?? null;
            $hasBond = ($idr !== null && (float) $idr > 0);

            // Scores
            $scoreL1 = is_numeric($row->l1_program) ? (float) $row->l1_program : null;
            $scoreL2 = is_numeric($row->assessment_score)
                ? (int) round((float) $row->assessment_score)
                : (is_numeric($row->l2_numerical) ? (int) round((float) $row->l2_numerical) : null);
            $scoreL3 = is_numeric($row->l3_numerical) ? (float) $row->l3_numerical : null;

            // Employee ID: raw string/integer only
            $rawEmp = $row->employee_id ?: '-';
            $cleanEmpId = ($rawEmp !== '-') ? preg_replace('/\s*[\/\-]\s*NIP.*$/i', '', trim((string) $rawEmp)) : '-';

            $rows->push([
                $rowCount,
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
                $row->type ?: '-',
                $row->category ?: '-',
                $row->level ?: '-',
                $row->batch ?: '-',
                $row->classification ?: 'Unclassified',
                $row->sub_classification ?: '-',
                $delivMethod,
                $subDelivMethod,
                $row->provider_name ?: ($row->source === 'offline' ? '-' : 'KP-OWL'),
                $row->venue ?: ($row->source === 'offline' ? '-' : 'KP-OWL'),
                $row->instructor ?: '-',
                $durationFormatted, // Course Duration
                $durationFormatted, // Total Engagement Duration
                '-', // Total Recognized Duration
                $lhNumeric, // Learning Hours
                $progressDecimal, // numeric decimal for 0% format code
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
                $hasBond ? (float) $idr : '-',
                $scoreL1 !== null ? (float) $scoreL1 : '-',
                $scoreL2 !== null ? (int) $scoreL2 : '-', // strictly pure integer
                $row->l2_verbal ?: ($scoreL2 !== null ? ($scoreL2 >= 70 ? 'Passed' : 'Failed') : '-'),
                $scoreL3 !== null ? (float) $scoreL3 : '-',
                $row->l3_verbal ?: '-',
                $row->pic ?: '-',
                $row->certificate ?: '-',
                $lhNumeric, // Total Engagement Time (LH)
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

                // Set format codes for SUM calculation
                $sheet->getStyle('Y2:Y' . $highestRow)->getNumberFormat()->setFormatCode('0.##" h"');
                $sheet->getStyle('Z2:Z' . $highestRow)->getNumberFormat()->setFormatCode('0.##" h"');
                $sheet->getStyle('AV2:AV' . $highestRow)->getNumberFormat()->setFormatCode('0.##" h"');
            },
        ];
    }
}
