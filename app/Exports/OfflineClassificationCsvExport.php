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

        // Month derivation
        $month = '-';
        $dateForMonth = $row->start_date ?: $row->finish_date;
        if (!empty($dateForMonth)) {
            try {
                $month = Carbon::parse($dateForMonth)->format('F');
            } catch (\Throwable $e) {
                $month = '-';
            }
        }

        // Attendance
        $attendanceStr = '-';
        if ($row->attendance !== null) {
            $attendanceStr = (bool) $row->attendance ? 'Yes' : 'No';
        }

        // Counted as Corporate KPI
        $kpiStr = '-';
        if ($row->counted_corporate_kpi !== null) {
            if ((bool) $row->counted_corporate_kpi) {
                $year = !empty($row->corporate_kpi_year)
                    ? $row->corporate_kpi_year
                    : (!empty($row->sub_corporate_kpi) && preg_match('/^\d{4}$/', (string) $row->sub_corporate_kpi) ? $row->sub_corporate_kpi : null);
                $kpiStr = $year ? "Yes ({$year})" : "Yes";
            } else {
                $kpiStr = 'No';
            }
        }

        return [
            $this->rowNumber,                                                             // 1. No
            $this->sanitizeCsvValue($row->employee_id ?: '-'),                            // 2. ID
            $this->sanitizeCsvValue($row->participant_name ?: ($row->user_name ?: '-')),  // 3. Name
            $this->sanitizeCsvValue($row->grade ?: '-'),                                  // 4. Grade
            $this->sanitizeCsvValue($row->job_position ?: '-'),                           // 5. Job Position
            $this->sanitizeCsvValue($row->team ?: '-'),                                   // 6. Team
            $this->sanitizeCsvValue($row->department ?: '-'),                             // 7. Department
            $this->sanitizeCsvValue($row->company ?: '-'),                                // 8. Company
            $this->sanitizeCsvValue($row->course_name ?: '-'),                            // 9. Course Name
            $this->sanitizeCsvValue($row->sub_course_name ?: '-'),                        // 10. Sub_Course Name
            $this->sanitizeCsvValue($row->type ?: '-'),                                   // 11. Type
            $this->sanitizeCsvValue($row->category ?: '-'),                               // 12. Category
            $this->sanitizeCsvValue($row->level ?: '-'),                                  // 13. Level
            $this->sanitizeCsvValue($row->batch ?: '-'),                                  // 14. Batch
            $this->sanitizeCsvValue($row->main_classification ?: '-'),                    // 15. Main Classification
            $this->sanitizeCsvValue($row->sub_classification ?: '-'),                     // 16. Sub Classification
            $kpiStr,                                                                      // 17. Counted as Corporate KPI
            $this->sanitizeCsvValue($row->sub_corporate_kpi ?: '-'),                      // 18. Sub Corporate KPI
            $this->sanitizeCsvValue($row->delivery_method ?: '-'),                        // 19. Delivery Method
            $this->sanitizeCsvValue($row->sub_delivery_method ?: '-'),                    // 20. Sub_Delivery Method
            $this->sanitizeCsvValue($row->provider ?: '-'),                               // 21. Provider
            $this->sanitizeCsvValue($row->sub_provider ?: '-'),                           // 22. Sub_Provider
            $durationFormatted,                                                           // 23. Duration
            $row->learning_hours !== null ? (float) $row->learning_hours : 0,             // 24. Learning (Hours)
            $row->progress !== null ? (float) $row->progress : 0,                         // 25. Progress
            $row->start_date ?: '-',                                                      // 26. Start Date
            $row->finish_date ?: '-',                                                     // 27. Finish Date
            $month,                                                                       // 28. Month
            $this->sanitizeCsvValue($row->provider_name ?: '-'),                          // 29. Provider Name
            $this->sanitizeCsvValue($row->venue ?: '-'),                                  // 30. Venue
            $this->sanitizeCsvValue($row->instructor ?: '-'),                             // 31. Instructor
            $row->assessment_score !== null ? (float) $row->assessment_score : '-',       // 32. Assessment Score
            $this->sanitizeCsvValue($row->certificate ?: '-'),                            // 33. Certificate
            $attendanceStr,                                                               // 34. Attendance Yes/No
            $this->sanitizeCsvValue($row->attendance_reason ?: '-'),                      // 35. Reason
            $this->sanitizeCsvValue($row->bond_status ?: 'No'),                           // 36. Bond Status
            $row->bond_end_date ?: '-',                                                   // 37. Bond End Date
            $row->bond_amount !== null ? (float) $row->bond_amount : '-',                 // 38. Bond Amount
            $this->sanitizeCsvValue($row->l1_program ?: '-'),                             // 39. Program
            $row->l2_numerical !== null ? (float) $row->l2_numerical : '-',               // 40. Numerical
            $this->sanitizeCsvValue($row->l2_verbal ?: '-'),                              // 41. Verbal
            $row->l3_numerical !== null ? (float) $row->l3_numerical : '-',               // 42. Numerical
            $this->sanitizeCsvValue($row->l3_verbal ?: '-'),                              // 43. Verbal
            $this->sanitizeCsvValue($row->pic ?: '-'),                                    // 44. PIC
        ];
    }

    public function chunkSize(): int
    {
        return 2000;
    }
}
