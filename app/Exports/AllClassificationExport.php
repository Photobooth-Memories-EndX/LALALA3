<?php

namespace App\Exports;

use App\Http\Controllers\Manage\Record\RecordAllController;
use App\Services\Record\RecordAllCombinedService;
use Illuminate\Contracts\Support\Responsable;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Concerns\Exportable;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\WithChunkReading;
use Maatwebsite\Excel\Concerns\WithColumnWidths;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class AllClassificationExport implements FromQuery, WithHeadings, WithMapping, WithChunkReading, WithStyles, WithColumnWidths, Responsable
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

        $resolved = RecordAllController::resolveClassification($this->classification, false);
        $slug = $resolved['slug'] ?: 'unclassified';
        $this->fileName = sprintf('all-kp-owl-%s-%s.xlsx', $slug, now()->format('Y-m-d'));
    }

    public function getFileName(): string
    {
        return $this->fileName;
    }

    public function query()
    {
        $service = app(RecordAllCombinedService::class);
        $queryFilters = $this->filters;
        $queryFilters['classification'] = $this->classification;

        return $service->buildCombinedQuery($queryFilters)
            ->orderBy('raw_finish_date', 'desc')
            ->orderBy('record_date', 'desc');
    }

    public function headings(): array
    {
        return [
            'No',
            'Source',
            'Employee ID',
            'Employee Name',
            'Status',
            'Grade',
            'Job Position',
            'Team',
            'Department',
            'Company',
            'Learning Title',
            'Sub Title',
            'Type',
            'Category',
            'Level',
            'Batch',
            'Main Classification',
            'Sub Classification',
            'Corporate KPI',
            'Sub Corporate KPI',
            'Delivery Method',
            'Sub Delivery Method',
            'Provider',
            'Sub Provider',
            'Provider Name',
            'Venue',
            'Instructor',
            'Duration',
            'Training Hours',
            'Start Date',
            'Finish Date',
            'Participant Hours',
            'Progress',
            'Assessment Score',
            'Certificate',
            'Attendance',
            'Attendance Reason',
            'Bond Status',
            'Bond End Date',
            'Bond Amount',
            'L1 Program',
            'L2 Numerical',
            'L2 Verbal',
            'L3 Numerical',
            'L3 Verbal',
            'PIC',
        ];
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

        // Attendance string
        $attendanceStr = '-';
        if ($row->attendance !== null) {
            $attendanceStr = (bool) $row->attendance ? 'Present' : 'Absent';
        }

        // Counted as Corporate KPI
        $kpiStr = '-';
        if ($row->counted_corporate_kpi !== null) {
            $kpiStr = (bool) $row->counted_corporate_kpi ? 'Yes' : 'No';
        }

        return [
            $this->rowNumber,
            $row->source === 'offline' ? 'Non KP OWL' : 'KP OWL',
            $row->employee_id ?: '-',
            $row->employee_name ?: '-',
            ucfirst((string) ($row->status ?: '-')),
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
            $row->classification ?: '-',
            $row->sub_classification ?: '-',
            $kpiStr,
            $row->sub_corporate_kpi ?: '-',
            $row->delivery_method ?: '-',
            $row->sub_delivery_method ?: '-',
            $row->provider ?: '-',
            $row->sub_provider ?: '-',
            $row->provider_name ?: '-',
            $row->venue ?: '-',
            $row->instructor ?: '-',
            $durationFormatted,
            $row->training_hours !== null ? number_format((float) $row->training_hours, 2) : '-',
            $row->start_date ? date('Y-m-d', strtotime($row->start_date)) : '-',
            $row->finish_date ? date('Y-m-d', strtotime($row->finish_date)) : '-',
            number_format((float) ($row->learning_hours ?? 0), 2),
            ($row->progress ?? 0) . '%',
            $row->assessment_score !== null ? number_format((float) $row->assessment_score, 2) : '-',
            $row->certificate ?: '-',
            $attendanceStr,
            $row->attendance_reason ?: '-',
            $row->bond_status ?: '-',
            $row->bond_end_date ? date('Y-m-d', strtotime($row->bond_end_date)) : '-',
            $row->bond_amount !== null ? number_format((float) $row->bond_amount, 2) : '-',
            $row->l1_program ?: '-',
            $row->l2_numerical !== null ? number_format((float) $row->l2_numerical, 2) : '-',
            $row->l2_verbal ?: '-',
            $row->l3_numerical !== null ? number_format((float) $row->l3_numerical, 2) : '-',
            $row->l3_verbal ?: '-',
            $row->pic ?: '-',
        ];
    }

    public function chunkSize(): int
    {
        return 1000;
    }

    public function columnWidths(): array
    {
        return [
            'A' => 6,   // No
            'B' => 14,  // Source
            'C' => 14,  // Employee ID
            'D' => 30,  // Employee Name
            'E' => 14,  // Status
            'F' => 10,  // Grade
            'G' => 25,  // Job Position
            'H' => 20,  // Team
            'I' => 25,  // Department
            'J' => 25,  // Company
            'K' => 35,  // Learning Title
            'L' => 25,  // Sub Title
            'M' => 15,  // Type
            'N' => 18,  // Category
            'O' => 12,  // Level
            'P' => 12,  // Batch
            'Q' => 25,  // Main Classification
            'R' => 25,  // Sub Classification
            'S' => 15,  // Corporate KPI
            'T' => 22,  // Sub Corporate KPI
            'U' => 18,  // Delivery Method
            'V' => 20,  // Sub Delivery Method
            'W' => 15,  // Provider
            'X' => 18,  // Sub Provider
            'Y' => 25,  // Provider Name
            'Z' => 20,  // Venue
            'AA' => 22, // Instructor
            'AB' => 14, // Duration
            'AC' => 16, // Training Hours
            'AD' => 14, // Start Date
            'AE' => 14, // Finish Date
            'AF' => 18, // Participant Hours
            'AG' => 12, // Progress
            'AH' => 18, // Assessment Score
            'AI' => 14, // Certificate
            'AJ' => 14, // Attendance
            'AK' => 25, // Attendance Reason
            'AL' => 14, // Bond Status
            'AM' => 15, // Bond End Date
            'AN' => 16, // Bond Amount
            'AO' => 14, // L1 Program
            'AP' => 15, // L2 Numerical
            'AQ' => 14, // L2 Verbal
            'AR' => 15, // L3 Numerical
            'AS' => 14, // L3 Verbal
            'AT' => 14, // PIC
        ];
    }

    public function styles(Worksheet $sheet)
    {
        return [
            1 => [
                'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
                'fill' => [
                    'fillType' => Fill::FILL_SOLID,
                    'startColor' => ['rgb' => '003B6D'],
                ],
                'alignment' => [
                    'vertical' => Alignment::VERTICAL_CENTER,
                    'horizontal' => Alignment::HORIZONTAL_CENTER,
                ],
            ],
        ];
    }
}
