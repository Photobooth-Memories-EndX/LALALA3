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
        return OfflineTrainingExcelSchema::legacyHeaders();
    }

    public function columnWidths(): array
    {
        return OfflineTrainingExcelSchema::legacyColumnWidths();
    }

    public function columnFormats(): array
    {
        return OfflineTrainingExcelSchema::legacyColumnFormats();
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

        return [
            $cleanEmpId,                                                // 1. Employee ID
            $row->participant_name ?: ($row->user_name ?? '-'),         // 2. Employee Name
            $row->grade ?: '-',                                         // 3. Grade
            $row->job_position ?: '-',                                  // 4. Job Position
            $row->team ?: '-',                                          // 5. Team
            $row->department ?: '-',                                    // 6. Department
            $row->company ?: '-',                                       // 7. Company
            $row->course_name ?: ($row->title ?: '-'),                  // 8. Course Title
            $row->sub_course_name ?: '-',                               // 9. Sub Course Title
            $row->type ?: '-',                                          // 10. Type
            $row->category ?: '-',                                      // 11. Category
            $row->level ?: '-',                                         // 12. Level
            $row->batch ?: '-',                                         // 13. Batch
            $row->main_classification ?: 'Unclassified',                // 14. Main Classification
            $row->sub_classification ?: '-',                            // 15. Sub Classification
            $delivMethod,                                               // 16. Delivery Method
            $subDelivMethod,                                            // 17. Sub Delivery Method
            $row->provider_name ?: ($row->provider ?: '-'),             // 18. Provider Name
            $row->venue ?: '-',                                         // 19. Venue
            $row->instructor ?: '-',                                    // 20. Instructor
            $durationFormatted,                                         // 21. Course Duration
            $lHoursNumeric,                                             // 22. Learning Hours
            $progressDecimal,                                           // 23. % Progress
            $progressStatus,                                            // 24. Progress Status
            $startDateStr,                                              // 25. Start Date
            $endDateStr,                                                // 26. End Date
            $startTimeStr,                                              // 27. Start Time
            $endTimeStr,                                                // 28. End Time
            $recordMonthStr,                                            // 29. Record Month
            $row->requirement ?: ($row->status ?: 'Mandatory'),         // 30. Requirement
            $attendanceStr,                                             // 31. Attendance
            $row->attendance_reason ?: '-',                             // 32. Attendance Reason
            $hasBond ? 'Yes' : ($row->bond_status ?: 'No'),             // 33. Bond Status
            $bondDurationMonths !== null ? $bondDurationMonths : '-',   // 34. Bond Duration
            $bondEndDateStr,                                            // 35. Bond End Date
            $hasBond ? (float) $idr : '-',                              // 36. Bond Amount
            $scoreL1 !== null ? $scoreL1 : '-',                         // 37. Score (L1) - Course
            $scoreL2 !== null ? $scoreL2 : '-',                         // 38. Score (L2)
            $row->l2_verbal ?: ($scoreL2 !== null ? ($scoreL2 >= 70 ? 'Passed' : 'Failed') : '-'), // 39. L2 Status
            $scoreL3 !== null ? $scoreL3 : '-',                         // 40. Score (L3)
            $row->l3_verbal ?: '-',                                     // 41. L3 Status
            $row->pic ?: '-',                                           // 42. Person in Charge (PIC)
            $row->certificate ?: '-',                                   // 43. Certificate
            $totalEngNumeric,                                           // 44. Total Engagement Time
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
                $sheet->getStyle('V2:V' . $highestRow)->getNumberFormat()->setFormatCode('0.##" h"');
                $sheet->getStyle('W2:W' . $highestRow)->getNumberFormat()->setFormatCode('0%');
                $sheet->getStyle('Y2:Y' . $highestRow)->getNumberFormat()->setFormatCode('yyyy-mm-dd');
                $sheet->getStyle('Z2:Z' . $highestRow)->getNumberFormat()->setFormatCode('yyyy-mm-dd');
                $sheet->getStyle('AA2:AA' . $highestRow)->getNumberFormat()->setFormatCode('hh:mm:ss');
                $sheet->getStyle('AB2:AB' . $highestRow)->getNumberFormat()->setFormatCode('hh:mm:ss');
                $sheet->getStyle('AH2:AH' . $highestRow)->getNumberFormat()->setFormatCode('0');
                $sheet->getStyle('AI2:AI' . $highestRow)->getNumberFormat()->setFormatCode('yyyy-mm-dd');
                $sheet->getStyle('AJ2:AJ' . $highestRow)->getNumberFormat()->setFormatCode('"Rp"#,##0');
                $sheet->getStyle('AR2:AR' . $highestRow)->getNumberFormat()->setFormatCode('0.##" h"');
            },
        ];
    }
}