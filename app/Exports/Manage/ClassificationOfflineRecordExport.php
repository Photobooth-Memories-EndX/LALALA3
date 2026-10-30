<?php

namespace App\Exports\Manage;

use App\Models\OfflineTraining;
use App\Services\Record\OfflineTrainingExcelSchema;
use App\Services\Record\RecordOfflineQueryService;
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

class ClassificationOfflineRecordExport implements FromCollection, WithHeadings, WithColumnWidths, WithColumnFormatting, WithStyles, WithEvents, Responsable
{
    use Exportable;

    protected ?string $classification;
    protected array $filters = [];
    protected string $fileName;

    public function __construct(?string $classification = null, $filters = [], ?string $fileName = null)
    {
        $this->classification = !empty($classification) ? (string) $classification : null;
        $this->filters = $filters instanceof Request ? $filters->all() : (array) $filters;

        if ($fileName !== null) {
            $this->fileName = $fileName;
        } elseif ($this->classification !== null) {
            $resolved = \App\Http\Controllers\Manage\Record\RecordOfflineController::resolveClassification($this->classification);
            $slug = $resolved['slug'] ?: 'unclassified';
            $this->fileName = sprintf('non-kp-owl-%s-%s.xlsx', $slug, now()->format('Y-m-d'));
        } else {
            $this->fileName = self::generateFileName();
        }
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

    public function columnFormats(): array
    {
        return [
            'A'  => '0',
            'C'  => NumberFormat::FORMAT_TEXT,
            'AA' => '0.##" h"',
            'AB' => '0.##" h"',
            'AC' => '0%',
            'AE' => 'yyyy-mm-dd',
            'AF' => 'yyyy-mm-dd',
            'AG' => 'hh:mm:ss',
            'AH' => 'hh:mm:ss',
            'AO' => 'yyyy-mm-dd',
            'AP' => '"Rp"#,##0',
            'AQ' => '0.##',
            'AR' => '0',
            'AT' => '0.##',
        ];

    }

    public function query()
    {
        if ($this->classification !== null) {
            return RecordOfflineQueryService::buildClassificationQuery($this->classification, $this->filters)
                ->orderBy('ot.start_date', 'desc')
                ->orderBy('ot.id', 'desc')
                ->orderBy('otu.id', 'asc');
        }

        return RecordOfflineQueryService::buildGlobalQuery($this->filters)
            ->orderBy('ot.start_date', 'desc')
            ->orderBy('ot.id', 'desc')
            ->orderBy('otu.id', 'asc');
    }

    public function map($row): array
    {
        return $this->formatRow($row);
    }

    public function formatRow($row): array
    {
        // 21. Course Duration formatted as HH:MM:SS
        $durationFormatted = '-';
        if (!empty($row->duration_seconds)) {
            $secs = (int) $row->duration_seconds;
            $hours = floor($secs / 3600);
            $mins = floor(($secs % 3600) / 60);
            $seconds = $secs % 60;
            $durationFormatted = sprintf('%02d:%02d:%02d', $hours, $mins, $seconds);
        }

        // 22. Learning Hours
        $rawLh = $row->course_learning_hours ?? ($row->learning_hours ?? null);
        if ($rawLh !== null && $rawLh !== '' && is_numeric($rawLh)) {
            $hours = (float) $rawLh;
            $lHoursNumeric = ($hours == (int) $hours) ? (int) $hours : round($hours, 2);
        } else {
            $lHoursNumeric = '-';
        }

        // 23 & 24. Progress & Status
        $progressNum = (float) ($row->progress ?? 0);
        $progressDecimal = round($progressNum / 100, 4);
        $progressStatus = ($progressNum >= 100) ? 'Finished' : (($progressNum > 0) ? 'Ongoing' : 'Not Started');

        // 25, 26, 27, 28. Dates and Times strictly separated
        $startDateStr = !empty($row->start_date) ? date('Y-m-d', strtotime($row->start_date)) : '-';
        $endDateStr = !empty($row->end_date)
            ? date('Y-m-d', strtotime($row->end_date))
            : (!empty($row->finish_date) ? date('Y-m-d', strtotime($row->finish_date)) : '-');
        $startTimeStr = !empty($row->start_time) ? date('H:i:s', strtotime($row->start_time)) : '-';
        $endTimeStr = !empty($row->end_time) ? date('H:i:s', strtotime($row->end_time)) : '-';

        // 29. Record Month
        $recordMonthDate = $row->start_date ?: ($row->end_date ?: ($row->created_at ?? null));
        $recordMonthStr = !empty($recordMonthDate) ? date('F', strtotime($recordMonthDate)) : '-';

        // 31. Attendance: strictly Yes or No
        $rawAtt = strtolower(trim((string) ($row->attendance ?? '')));
        if ($rawAtt !== '') {
            $attendanceStr = in_array($rawAtt, ['1', 'yes', 'present', 'true', 'hadir', 'completed', 'finished'], true) ? 'Yes' : 'No';
        } else {
            $attendanceStr = ($progressNum >= 100 || !empty($row->finish_date)) ? 'Yes' : 'No';
        }

        // 16 & 17. Delivery method alignment
        $rawDeliv = strtolower(trim((string) ($row->delivery_method ?? '')));
        $delivMethod = in_array($rawDeliv, ['online', 'e-learning', 'webinar', 'kp-owl', 'kp owl'], true) ? 'Online' : 'Offline';
        $subDelivMethod = $row->sub_delivery_method ?: 'On-site';

        // 33-36. Bond details
        $idr = $row->bond_amount_idr ?? ($row->bond_amount ?? null);
        $hasBond = ($idr !== null && is_numeric($idr) && (float) $idr > 0);

        $bondDurationMonths = null;
        if (!empty($row->bond_duration)) {
            $parsedDur = (int) preg_replace('/\D/', '', (string) $row->bond_duration);
            if ($parsedDur > 0) {
                $bondDurationMonths = $parsedDur;
            }
        }

        $bondEndDateStr = !empty($row->bond_end_date) ? date('Y-m-d', strtotime($row->bond_end_date)) : '-';

        // 37-41. Kirkpatrick Scores
        $scoreL1 = null;
        if (isset($row->l1_program) && is_numeric($row->l1_program)) {
            $val = (float) $row->l1_program;
            $intVal = (int) round($val);
            $scoreL1 = (abs($val - $intVal) < 0.0001) ? $intVal : round($val, 2);
        }

        $scoreL2 = null;
        $rawScoreL2 = $row->assessment_score ?? ($row->l2_numerical ?? null);
        if ($rawScoreL2 !== null && is_numeric($rawScoreL2)) {
            $val = (float) $rawScoreL2;
            $intVal = (int) round($val);
            $scoreL2 = (abs($val - $intVal) < 0.0001) ? $intVal : round($val, 2);
        }

        $scoreL3 = null;
        if (isset($row->l3_numerical) && is_numeric($row->l3_numerical)) {
            $val = (float) $row->l3_numerical;
            $intVal = (int) round($val);
            $scoreL3 = (abs($val - $intVal) < 0.0001) ? $intVal : round($val, 2);
        }

        // 44. Total Engagement Time
        $totalEngVal = $row->learning_hours ?? ($row->course_learning_hours ?? null);
        if ($totalEngVal !== null && $totalEngVal !== '' && is_numeric($totalEngVal)) {
            $hours = (float) $totalEngVal;
            $totalEngNumeric = ($hours == (int) $hours) ? (int) $hours : round($hours, 2);
        } else {
            $totalEngNumeric = '-';
        }

        // 1. Employee ID
        $rawEmp = $row->employee_id ?: ($row->id_emp ?? '-');
        $cleanEmpId = ($rawEmp !== '-') ? preg_replace('/\s*[\/\-]\s*NIP.*$/i', '', trim((string) $rawEmp)) : '-';

        $hasCert = (!empty($row->certificate) && $row->certificate !== '-');

        static $rowIdx = 0;
        $rowIdx++;

        return [
            $rowIdx,                                                    // 1. NO
            '-',                                                        // 2. Source
            $cleanEmpId,                                                // 3. Employee ID
            $row->participant_name ?: ($row->user_name ?? '-'),         // 4. Employee Name
            $row->grade ?: '-',                                         // 5. Grade
            $row->job_position ?: '-',                                  // 6. Job Position
            $row->team ?: '-',                                          // 7. Team
            $row->department ?: '-',                                    // 8. Department
            $row->company ?: '-',                                       // 9. Company
            $row->course_name ?: ($row->title ?: '-'),                  // 10. Course Title
            $row->sub_course_name ?: '-',                               // 11. Sub Course Title
            $row->type ?: '-',                                          // 12. Type
            $row->category ?: '-',                                      // 13. Category
            $row->level ?: '-',                                         // 14. Level
            $row->batch ?: '-',                                         // 15. Batch
            $row->main_classification ?: 'Unclassified',                // 16. Main Classification
            $row->sub_classification ?: '-',                            // 17. Sub Classification
            $delivMethod,                                               // 18. Delivery Method
            $subDelivMethod,                                            // 19. Sub Delivery Method
            $row->provider ?: '-',                                      // 20. Provider
            $row->sub_provider ?: '-',                                  // 21. Sub Provider
            $row->provider_name ?: ($row->provider ?: '-'),             // 22. Provider Name
            $row->venue ?: '-',                                         // 23. Venue
            $row->instructor ?: '-',                                    // 24. Instructor
            '-',                                                        // 25. Course Duration (strictly -)
            '-',                                                        // 26. Total Recognized Duration (strictly -)
            $lHoursNumeric,                                             // 27. Learning Hours (strictly admin input)
            $lHoursNumeric,                                             // 28. Total Engagement Time
            $progressDecimal,                                           // 29. % Progress
            $progressStatus,                                            // 30. Progress Status
            $startDateStr,                                              // 31. Start Date
            $endDateStr,                                                // 32. End Date
            $startTimeStr,                                              // 33. Start Time
            $endTimeStr,                                                // 34. End Time
            $recordMonthStr,                                            // 35. Record Month
            $row->requirement ?: ($row->status ?: 'Mandatory'),         // 36. Requirement
            $attendanceStr,                                             // 37. Attendance
            $row->attendance_reason ?: '-',                             // 38. Attendance Reason
            $hasBond ? 'Yes' : ($row->bond_status ?: 'No'),             // 39. Bond Status
            $bondDurationMonths !== null ? $bondDurationMonths : '-',   // 40. Bond Duration
            $bondEndDateStr,                                            // 41. Bond End Date
            $hasBond ? (float) $idr : '-',                              // 42. Bond Amount
            $scoreL1 !== null ? $scoreL1 : '-',                         // 43. Score (L1) - Course
            $scoreL2 !== null ? $scoreL2 : '-',                         // 44. Score (L2)
            $row->l2_verbal ?: ($scoreL2 !== null ? ($scoreL2 >= 70 ? 'Passed' : 'Failed') : '-'), // 45. L2 Status
            $scoreL3 !== null ? $scoreL3 : '-',                         // 46. Score (L3)
            $row->l3_verbal ?: '-',                                     // 47. L3 Status
            $hasCert ? 'Available' : '-',                                // 48. Certificate Availability
            $hasCert ? $row->certificate : '-',                          // 49. Certificate Institution
            '-',                                                        // 50. Certificate Issued Date
            '-',                                                        // 51. Certificate Expired Date
            $row->pic ?: '-',                                           // 52. Person in Charge (PIC)
        ];
    }

    public function collection(): Collection
    {
        ini_set('memory_limit', '2048M');
        ini_set('max_execution_time', 600);
        if (function_exists('set_time_limit')) {
            @set_time_limit(600);
        }

        $rows = new Collection();
        foreach ($this->query()->cursor() as $row) {
            $rows->push($this->formatRow($row));
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

                // Set format codes for numeric & date calculations
                $sheet->getStyle('AA2:AA' . $highestRow)->getNumberFormat()->setFormatCode('0.##" h"');
                $sheet->getStyle('AB2:AB' . $highestRow)->getNumberFormat()->setFormatCode('0.##" h"');
                $sheet->getStyle('AC2:AC' . $highestRow)->getNumberFormat()->setFormatCode('0%');
                $sheet->getStyle('AE2:AE' . $highestRow)->getNumberFormat()->setFormatCode('yyyy-mm-dd');
                $sheet->getStyle('AF2:AF' . $highestRow)->getNumberFormat()->setFormatCode('yyyy-mm-dd');
                $sheet->getStyle('AG2:AG' . $highestRow)->getNumberFormat()->setFormatCode('hh:mm:ss');
                $sheet->getStyle('AH2:AH' . $highestRow)->getNumberFormat()->setFormatCode('hh:mm:ss');
                $sheet->getStyle('AO2:AO' . $highestRow)->getNumberFormat()->setFormatCode('yyyy-mm-dd');
                $sheet->getStyle('AP2:AP' . $highestRow)->getNumberFormat()->setFormatCode('"Rp"#,##0');
            },
        ];
    }
}