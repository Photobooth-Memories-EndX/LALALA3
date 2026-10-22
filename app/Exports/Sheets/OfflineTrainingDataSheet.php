<?php

namespace App\Exports\Sheets;

use App\Services\Record\OfflineTrainingExcelSchema;
use App\Services\Record\RecordOfflineQueryService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\WithChunkReading;
use Maatwebsite\Excel\Concerns\WithColumnWidths;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Events\AfterSheet;

class OfflineTrainingDataSheet implements FromQuery, WithHeadings, WithMapping, WithChunkReading, WithColumnWidths, WithTitle, WithEvents
{
    protected ?string $classification = null;
    protected array $filters = [];
    protected int $rowNumber = 0;

    public function __construct(?string $classification = null, $filters = [])
    {
        $this->classification = !empty($classification) ? (string) $classification : null;
        $this->filters = $filters instanceof Request ? $filters->all() : (array) $filters;
        $this->rowNumber = 0;
    }

    public function title(): string
    {
        return OfflineTrainingExcelSchema::DATA_SHEET_TITLE;
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
        return [
            OfflineTrainingExcelSchema::headers(),
        ];
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

        // Format dates as YYYY-MM-DD cleanly
        $startDateStr = '';
        if (!empty($row->start_date)) {
            $startDateStr = is_string($row->start_date) && strlen($row->start_date) >= 10
                ? substr($row->start_date, 0, 10)
                : Carbon::parse($row->start_date)->format('Y-m-d');
        }

        $finishDateStr = '';
        if (!empty($row->finish_date)) {
            $finishDateStr = is_string($row->finish_date) && strlen($row->finish_date) >= 10
                ? substr($row->finish_date, 0, 10)
                : Carbon::parse($row->finish_date)->format('Y-m-d');
        }

        $bondEndDateStr = '';
        if (!empty($row->bond_end_date)) {
            $bondEndDateStr = is_string($row->bond_end_date) && strlen($row->bond_end_date) >= 10
                ? substr($row->bond_end_date, 0, 10)
                : Carbon::parse($row->bond_end_date)->format('Y-m-d');
        }

        return [
            $this->rowNumber,                                                           // 1. No
            $row->employee_id ?: '',                                                    // 2. ID
            $row->participant_name ?: ($row->user_name ?: ''),                         // 3. Name
            $row->grade ?: '',                                                         // 4. Grade
            $row->job_position ?: '',                                                  // 5. Job Position
            $row->team ?: '',                                                          // 6. Team
            $row->department ?: '',                                                    // 7. Department
            $row->company ?: '',                                                       // 8. Company
            $row->course_name ?: '',                                                   // 9. Course Name
            $row->sub_course_name ?: '',                                               // 10. Sub_Course Name
            $row->type ?: '',                                                          // 11. Type
            $row->category ?: '',                                                      // 12. Category
            $row->level ?: '',                                                         // 13. Level
            $row->batch ?: '',                                                         // 14. Batch
            $row->main_classification ?: '',                                           // 15. Main Classification
            $row->sub_classification ?: '',                                            // 16. Sub Classification
            $kpiStr,                                                                    // 17. Counted as Corporate KPI
            $row->sub_corporate_kpi ?: '',                                             // 18. Sub Corporate KPI
            \App\Models\OfflineTraining::normalizeDeliveryMethod($row->delivery_method), // 19. Delivery Method
            $row->sub_delivery_method ?: '',                                           // 20. Sub_Delivery Method
            $row->provider ?: '',                                                      // 21. Provider
            $row->sub_provider ?: '',                                                  // 22. Sub_Provider
            $durationFormatted,                                                         // 23. Duration
            $row->learning_hours !== null ? (float) $row->learning_hours : '',         // 24. Learning (Hours)
            $row->progress !== null ? (float) $row->progress : '',                     // 25. Progress
            $startDateStr,                                                              // 26. Start Date
            $finishDateStr,                                                             // 27. Finish Date
            $month,                                                                     // 28. Month
            $row->provider_name ?: '',                                                 // 29. Provider Name
            $row->venue ?: '',                                                         // 30. Venue
            $row->instructor ?: '',                                                    // 31. Instructor
            is_numeric($row->assessment_score) ? (int) round((float) $row->assessment_score) : '-',     // 32. Assessment Score
            $row->certificate ?: '',                                                   // 33. Certificate
            $row->attendance_reason ?: '',                                             // 34. Reason
            $row->bond_status ?: 'No',                                                 // 35. Bond Status
            $bondEndDateStr,                                                            // 36. Bond End Date
            $row->bond_amount !== null ? (float) $row->bond_amount : '',               // 37. Bond Amount
            $row->l1_program ?: '',                                                    // 38. Program
            $row->l2_numerical !== null ? (float) $row->l2_numerical : '',             // 39. Numerical
            $row->l2_verbal ?: '',                                                     // 40. Verbal
            $row->l3_numerical !== null ? (float) $row->l3_numerical : '',             // 41. Numerical
            $row->l3_verbal ?: '',                                                     // 42. Verbal
            $row->pic ?: '',                                                           // 43. PIC
        ];
    }

    public function chunkSize(): int
    {
        return 2000;
    }

    public function columnWidths(): array
    {
        return OfflineTrainingExcelSchema::columnWidths();
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event) {
                OfflineTrainingExcelSchema::applyDataSheetHeaderStyles($event->sheet->getDelegate());
            },
        ];
    }
}
