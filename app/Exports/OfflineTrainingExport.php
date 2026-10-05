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
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class OfflineTrainingExport implements WithMultipleSheets, FromQuery, WithHeadings, WithMapping, WithChunkReading, WithStyles, WithColumnWidths, Responsable
{
    use Exportable;

    protected ?string $classification = null;
    protected array $filters = [];
    protected int $rowNumber = 0;
    protected string $fileName;

    public function __construct(?string $classification = null, $filters = [])
    {
        $this->classification = !empty($classification) ? (string) $classification : null;
        $this->filters = $filters instanceof Request ? $filters->all() : (array) $filters;
        $this->rowNumber = 0;

        if ($this->classification !== null) {
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
        return OfflineTrainingExcelSchema::headers();
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
            $kpiStr = (bool) $row->counted_corporate_kpi ? 'Yes' : 'No';
        }

        return [
            $this->rowNumber,                                                   // 1. No
            $row->employee_id ?: '-',                                           // 2. ID
            $row->participant_name ?: ($row->user_name ?: '-'),                 // 3. Name
            $row->grade ?: '-',                                                 // 4. Grade
            $row->job_position ?: '-',                                          // 5. Job Position
            $row->team ?: '-',                                                  // 6. Team
            $row->department ?: '-',                                            // 7. Department
            $row->company ?: '-',                                               // 8. Company
            $row->course_name ?: '-',                                           // 9. Course Name
            $row->sub_course_name ?: '-',                                       // 10. Sub_Course Name
            $row->type ?: '-',                                                  // 11. Type
            $row->category ?: '-',                                              // 12. Category
            $row->level ?: '-',                                                 // 13. Level
            $row->batch ?: '-',                                                 // 14. Batch
            $row->main_classification ?: '-',                                   // 15. Main Classification
            $row->sub_classification ?: '-',                                    // 16. Sub Classification
            $kpiStr,                                                            // 17. Counted as Corporate KPI
            $row->sub_corporate_kpi ?: '-',                                     // 18. Sub Corporate KPI
            $row->delivery_method ?: '-',                                       // 19. Delivery Method
            $row->sub_delivery_method ?: '-',                                   // 20. Sub_Delivery Method
            $row->provider ?: '-',                                              // 21. Provider
            $row->sub_provider ?: '-',                                          // 22. Sub_Provider
            $durationFormatted,                                                 // 23. Duration
            $row->learning_hours !== null ? (float) $row->learning_hours : 0,   // 24. Learning (Hours)
            $row->progress !== null ? (float) $row->progress : 0,               // 25. Progress
            $row->start_date ?: '-',                                            // 26. Start Date
            $row->finish_date ?: '-',                                           // 27. Finish Date
            $month,                                                             // 28. Month
            $row->provider_name ?: '-',                                         // 29. Provider Name
            $row->venue ?: '-',                                                 // 30. Venue
            $row->instructor ?: '-',                                            // 31. Instructor
            $row->assessment_score !== null ? (float) $row->assessment_score : '-', // 32. Assessment Score
            $row->certificate ?: '-',                                           // 33. Certificate
            $attendanceStr,                                                     // 34. Attendance Yes/No
            $row->attendance_reason ?: '-',                                     // 35. Reason
            $row->bond_status ?: 'No',                                          // 36. Bond Status
            $row->bond_end_date ?: '-',                                         // 37. Bond End Date
            $row->bond_amount !== null ? (float) $row->bond_amount : '-',       // 38. Bond Amount
            $row->l1_program ?: '-',                                            // 39. Program
            $row->l2_numerical !== null ? (float) $row->l2_numerical : '-',     // 40. Numerical
            $row->l2_verbal ?: '-',                                             // 41. Verbal
            $row->l3_numerical !== null ? (float) $row->l3_numerical : '-',     // 42. Numerical
            $row->l3_verbal ?: '-',                                             // 43. Verbal
            $row->pic ?: '-',                                                   // 44. PIC
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

    public function styles(Worksheet $sheet)
    {
        return [
            1 => [
                'font'      => ['bold' => true],
                'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
            ],
        ];
    }
}
