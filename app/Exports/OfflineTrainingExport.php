<?php

namespace App\Exports;

use App\Exports\Sheets\OfflineTrainingDataSheet;
use App\Exports\Sheets\OfflineTrainingInstructionsSheet;
use App\Http\Controllers\Manage\Record\RecordOfflineController;
use App\Services\Record\OfflineTrainingExcelSchema;
use App\Services\Record\RecordOfflineQueryService;
use Carbon\Carbon;
use Illuminate\Contracts\Support\Responsable;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Concerns\Exportable;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\WithChunkReading;
use Maatwebsite\Excel\Concerns\WithColumnWidths;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithMultipleSheets;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Concerns\WithColumnFormatting;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class OfflineTrainingExport implements WithMultipleSheets, FromQuery, WithHeadings, WithMapping, WithChunkReading, WithStyles, WithColumnWidths, Responsable
{
    use Exportable;

    protected ?string $classification = null;
    protected array $filters = [];
    protected int $rowNumber = 0;
    protected string $fileName;

    public function __construct(?string $classification = null, $filters = [], ?string $fileName = null)
    {
        $this->classification = !empty($classification) ? (string) $classification : null;
        $this->filters = $filters instanceof Request ? $filters->all() : (array) $filters;
        $this->rowNumber = 0;

        if ($fileName !== null) {
            $this->fileName = $fileName;
        } elseif ($this->classification !== null) {
            $resolved = RecordOfflineController::resolveClassification($this->classification);
            $slug = $resolved['slug'] ?: 'unclassified';
            $this->fileName = sprintf('non-kp-owl-%s-%s.xlsx', $slug, now()->format('Y-m-d'));
        } else {
            $this->fileName = sprintf('non-kp-owl-all-records-%s.xlsx', now()->format('Y-m-d'));
        }
    }

    public function getFileName(): string
    {
        return $this->fileName;
    }

    public function sheets(): array
    {
        return [
            new OfflineTrainingDataSheet($this->classification, $this->filters),
            new OfflineTrainingInstructionsSheet(),
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

    public function headings(): array
    {
        return OfflineTrainingExcelSchema::legacyHeaders();
    }

    public function map($row): array
    {
        $this->rowNumber++;

        // Duration formatted as HH:MM:SS
        $durationFormatted = '';
        if (!empty($row->duration_seconds)) {
            $secs = (int) $row->duration_seconds;
            $hours = floor($secs / 3600);
            $mins = floor(($secs % 3600) / 60);
            $seconds = $secs % 60;
            $durationFormatted = sprintf('%02d:%02d:%02d', $hours, $mins, $seconds);
        }

        // Month derivation
        $month = '';
        $dateForMonth = $row->start_date ?: $row->finish_date;
        if (!empty($dateForMonth)) {
            try {
                $month = Carbon::parse($dateForMonth)->format('F');
            } catch (\Throwable $e) {
                $month = '';
            }
        }

        // Attendance
        $attendanceStr = '';
        if ($row->attendance !== null) {
            $attendanceStr = (bool) $row->attendance ? 'Yes' : 'No';
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

        // Dates & Times split
        $startDateStr = !empty($row->start_date) ? (is_string($row->start_date) && strlen($row->start_date) >= 10 ? substr($row->start_date, 0, 10) : Carbon::parse($row->start_date)->format('Y-m-d')) : '-';
        $endDateStr = !empty($row->end_date)
            ? (is_string($row->end_date) && strlen($row->end_date) >= 10 ? substr($row->end_date, 0, 10) : Carbon::parse($row->end_date)->format('Y-m-d'))
            : (!empty($row->finish_date) ? (is_string($row->finish_date) && strlen($row->finish_date) >= 10 ? substr($row->finish_date, 0, 10) : Carbon::parse($row->finish_date)->format('Y-m-d')) : '-');
        $startTimeStr = !empty($row->start_time) ? Carbon::parse($row->start_time)->format('H:i:s') : '-';
        $endTimeStr = !empty($row->end_time) ? Carbon::parse($row->end_time)->format('H:i:s') : '-';

        $progressNum = (float) ($row->progress ?? 0);
        $progressDecimal = round($progressNum / 100, 4);
        $progressStatus = ($progressNum >= 100) ? 'Finished' : (($progressNum > 0) ? 'Ongoing' : 'Not Started');

        $rawLh = $row->course_learning_hours ?? ($row->learning_hours ?? null);
        $lHoursNumeric = ($rawLh !== null && $rawLh !== '' && is_numeric($rawLh))
            ? (((float)$rawLh == (int)$rawLh) ? (int)$rawLh : round((float)$rawLh, 2))
            : '-';

        $totalEngVal = $row->learning_hours ?? ($row->course_learning_hours ?? null);
        $totalEngNumeric = ($totalEngVal !== null && $totalEngVal !== '' && is_numeric($totalEngVal))
            ? (((float)$totalEngVal == (int)$totalEngVal) ? (int)$totalEngVal : round((float)$totalEngVal, 2))
            : '-';

        $rawDeliv = strtolower(trim((string) ($row->delivery_method ?? '')));
        $delivMethod = in_array($rawDeliv, ['online', 'e-learning', 'webinar', 'kp-owl', 'kp owl'], true) ? 'Online' : 'Offline';
        $subDelivMethod = $row->sub_delivery_method ?: 'On-site';

        $idr = $row->bond_amount_idr ?? ($row->bond_amount ?? null);
        $hasBond = ($idr !== null && is_numeric($idr) && (float) $idr > 0);
        $bondDurationMonths = null;
        if (!empty($row->bond_duration)) {
            $parsedDur = (int) preg_replace('/\D/', '', (string) $row->bond_duration);
            if ($parsedDur > 0) {
                $bondDurationMonths = $parsedDur;
            }
        }
        $bondEndDateStr = !empty($row->bond_end_date)
            ? (is_string($row->bond_end_date) && strlen($row->bond_end_date) >= 10 ? substr($row->bond_end_date, 0, 10) : Carbon::parse($row->bond_end_date)->format('Y-m-d'))
            : '-';

        $rawEmp = $row->employee_id ?: ($row->id_emp ?? '-');
        $cleanEmpId = ($rawEmp !== '-') ? preg_replace('/\s*[\/\-]\s*NIP.*$/i', '', trim((string) $rawEmp)) : '-';

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

        return [
            $cleanEmpId,                                                // 1. Employee ID
            $row->participant_name ?: ($row->user_name ?: '-'),         // 2. Employee Name
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
            $month ?: '-',                                              // 29. Record Month
            $row->requirement ?: ($row->status ?: 'Mandatory'),         // 30. Requirement
            $attendanceStr ?: 'No',                                     // 31. Attendance
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

    public function chunkSize(): int
    {
        return 2000;
    }

    public function columnWidths(): array
    {
        return OfflineTrainingExcelSchema::legacyColumnWidths();
    }

    public function styles(Worksheet $sheet)
    {
        return [
            1 => [
                'font'      => [
                    'bold'  => true,
                    'color' => ['rgb' => 'FFFFFF'],
                ],
                'fill'      => [
                    'fillType'   => Fill::FILL_SOLID,
                    'startColor' => ['rgb' => '002B49'],
                ],
                'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
            ],
        ];
    }
}
