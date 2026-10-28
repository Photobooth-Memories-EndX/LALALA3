<?php

namespace App\Exports;

use App\Http\Controllers\Manage\Record\RecordOfflineController;
use App\Services\Record\OfflineTrainingExcelSchema;
use App\Services\Record\RecordOfflineQueryService;
use Carbon\Carbon;
use Illuminate\Contracts\Support\Responsable;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Concerns\Exportable;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\WithChunkReading;
use Maatwebsite\Excel\Concerns\WithCustomCsvSettings;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;

class OfflineClassificationCsvExport implements FromQuery, WithHeadings, WithMapping, WithCustomCsvSettings, WithChunkReading, Responsable
{
    use Exportable;

    protected string $classification;
    protected array $filters = [];
    protected int $rowNumber = 0;
    protected string $fileName;

    public function __construct(string $classification, $filters = [])
    {
        $this->classification = $classification;
        $this->filters = $filters instanceof Request ? $filters->all() : (array) $filters;
        $this->rowNumber = 0;

        $resolved = RecordOfflineController::resolveClassification($this->classification);
        $slug = $resolved['slug'] ?: 'unclassified';
        $this->fileName = sprintf('offline-record-%s-%s.csv', $slug, now()->format('Y-m-d'));
    }

    public function getFileName(): string
    {
        return $this->fileName;
    }

    public function getCsvSettings(): array
    {
        return [
            'delimiter'              => ',',
            'enclosure'              => '"',
            'line_ending'            => "\r\n",
            'use_bom'                => true,
            'include_separator_line' => false,
            'excel_compatibility'    => false,
        ];
    }

    public function query()
    {
        return RecordOfflineQueryService::buildClassificationQuery($this->classification, $this->filters)
            ->orderBy('ot.start_date', 'desc')
            ->orderBy('ot.id', 'desc')
            ->orderBy('otu.id', 'asc');
    }

    public function headings(): array
    {
        return OfflineTrainingExcelSchema::headers();
    }

    /**
     * Prevent CSV formula injection on user-supplied text values.
     * Prefixes values starting with '=', '+', '-', '@', tab, or carriage return with a single quote.
     *
     * @param  mixed  $value
     * @return mixed
     */
    protected function sanitizeCsvValue($value)
    {
        if (!is_string($value) || $value === '' || $value === '-' || is_numeric($value)) {
            return $value;
        }

        $trimmed = ltrim($value);
        if ($trimmed !== '') {
            $first = $trimmed[0];
            if (in_array($first, ['=', '+', '-', '@', "\t", "\r"], true)) {
                return "'" . $value;
            }
        }

        return $value;
    }

    public function map($row): array
    {
        $this->rowNumber++;

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
        $rawLh = $row->course_learning_hours ?? ($row->learning_hours ?? null);
        $lHoursNumeric = ($rawLh !== null && $rawLh !== '' && is_numeric($rawLh))
            ? (((float)$rawLh == (int)$rawLh) ? (int)$rawLh : round((float)$rawLh, 2))
            : '-';

        // Progress & Status
        $progressNum = (float) ($row->progress ?? 0);
        $progressDecimal = round($progressNum / 100, 4);
        $progressStatus = ($progressNum >= 100) ? 'Finished' : (($progressNum > 0) ? 'Ongoing' : 'Not Started');

        // Dates & Times split
        $startDateStr = !empty($row->start_date) ? date('Y-m-d', strtotime($row->start_date)) : '-';
        $endDateStr = !empty($row->end_date)
            ? date('Y-m-d', strtotime($row->end_date))
            : (!empty($row->finish_date) ? date('Y-m-d', strtotime($row->finish_date)) : '-');
        $startTimeStr = !empty($row->start_time) ? date('H:i:s', strtotime($row->start_time)) : '-';
        $endTimeStr = !empty($row->end_time) ? date('H:i:s', strtotime($row->end_time)) : '-';

        $recordMonthDate = $row->start_date ?: ($row->end_date ?: ($row->created_at ?? null));
        $recordMonthStr = !empty($recordMonthDate) ? date('F', strtotime($recordMonthDate)) : '-';

        // Attendance
        $rawAtt = strtolower(trim((string) ($row->attendance ?? '')));
        if ($rawAtt !== '') {
            $attendanceStr = in_array($rawAtt, ['1', 'yes', 'present', 'true', 'hadir', 'completed', 'finished'], true) ? 'Yes' : 'No';
        } else {
            $attendanceStr = ($progressNum >= 100 || !empty($row->finish_date)) ? 'Yes' : 'No';
        }

        // Delivery Method
        $rawDeliv = strtolower(trim((string) ($row->delivery_method ?? '')));
        $delivMethod = in_array($rawDeliv, ['online', 'e-learning', 'webinar', 'kp-owl', 'kp owl'], true) ? 'Online' : 'Offline';
        $subDelivMethod = $row->sub_delivery_method ?: 'On-site';

        // Bond
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

        // Scores
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

        $totalEngVal = $row->learning_hours ?? ($row->course_learning_hours ?? null);
        $totalEngNumeric = ($totalEngVal !== null && $totalEngVal !== '' && is_numeric($totalEngVal))
            ? (((float)$totalEngVal == (int)$totalEngVal) ? (int)$totalEngVal : round((float)$totalEngVal, 2))
            : '-';

        $rawEmp = $row->employee_id ?: ($row->id_emp ?? '-');
        $cleanEmpId = ($rawEmp !== '-') ? preg_replace('/\s*[\/\-]\s*NIP.*$/i', '', trim((string) $rawEmp)) : '-';

        return [
            $this->sanitizeCsvValue($cleanEmpId),                                         // 1. Employee ID
            $this->sanitizeCsvValue($row->participant_name ?: ($row->user_name ?: '-')),  // 2. Employee Name
            $this->sanitizeCsvValue($row->grade ?: '-'),                                  // 3. Grade
            $this->sanitizeCsvValue($row->job_position ?: '-'),                           // 4. Job Position
            $this->sanitizeCsvValue($row->team ?: '-'),                                   // 5. Team
            $this->sanitizeCsvValue($row->department ?: '-'),                             // 6. Department
            $this->sanitizeCsvValue($row->company ?: '-'),                                // 7. Company
            $this->sanitizeCsvValue($row->course_name ?: ($row->title ?: '-')),           // 8. Course Title
            $this->sanitizeCsvValue($row->sub_course_name ?: '-'),                        // 9. Sub Course Title
            $this->sanitizeCsvValue($row->type ?: '-'),                                   // 10. Type
            $this->sanitizeCsvValue($row->category ?: '-'),                               // 11. Category
            $this->sanitizeCsvValue($row->level ?: '-'),                                  // 12. Level
            $this->sanitizeCsvValue($row->batch ?: '-'),                                  // 13. Batch
            $this->sanitizeCsvValue($row->main_classification ?: 'Unclassified'),          // 14. Main Classification
            $this->sanitizeCsvValue($row->sub_classification ?: '-'),                     // 15. Sub Classification
            $delivMethod,                                                                 // 16. Delivery Method
            $this->sanitizeCsvValue($subDelivMethod),                                     // 17. Sub Delivery Method
            $this->sanitizeCsvValue($row->provider_name ?: ($row->provider ?: '-')),      // 18. Provider Name
            $this->sanitizeCsvValue($row->venue ?: '-'),                                  // 19. Venue
            $this->sanitizeCsvValue($row->instructor ?: '-'),                             // 20. Instructor
            $durationFormatted,                                                           // 21. Course Duration
            $lHoursNumeric,                                                               // 22. Learning Hours
            $progressDecimal,                                                             // 23. % Progress
            $progressStatus,                                                              // 24. Progress Status
            $startDateStr,                                                                // 25. Start Date
            $endDateStr,                                                                  // 26. End Date
            $startTimeStr,                                                                // 27. Start Time
            $endTimeStr,                                                                  // 28. End Time
            $recordMonthStr,                                                              // 29. Record Month
            $this->sanitizeCsvValue($row->requirement ?: ($row->status ?: 'Mandatory')),  // 30. Requirement
            $attendanceStr,                                                               // 31. Attendance
            $this->sanitizeCsvValue($row->attendance_reason ?: '-'),                      // 32. Attendance Reason
            $hasBond ? 'Yes' : ($row->bond_status ?: 'No'),                               // 33. Bond Status
            $bondDurationMonths !== null ? $bondDurationMonths : '-',                     // 34. Bond Duration
            $bondEndDateStr,                                                              // 35. Bond End Date
            $hasBond ? (float) $idr : '-',                                                // 36. Bond Amount
            $scoreL1 !== null ? $scoreL1 : '-',                                           // 37. Score (L1) - Course
            $scoreL2 !== null ? $scoreL2 : '-',                                           // 38. Score (L2)
            $this->sanitizeCsvValue($row->l2_verbal ?: ($scoreL2 !== null ? ($scoreL2 >= 70 ? 'Passed' : 'Failed') : '-')), // 39. L2 Status
            $scoreL3 !== null ? $scoreL3 : '-',                                           // 40. Score (L3)
            $this->sanitizeCsvValue($row->l3_verbal ?: '-'),                              // 41. L3 Status
            $this->sanitizeCsvValue($row->pic ?: '-'),                                    // 42. Person in Charge (PIC)
            $this->sanitizeCsvValue($row->certificate ?: '-'),                            // 43. Certificate
            $totalEngNumeric,                                                             // 44. Total Engagement Time
        ];
    }

    public function chunkSize(): int
    {
        return 2000;
    }
}
