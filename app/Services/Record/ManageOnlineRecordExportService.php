<?php

namespace App\Services\Record;

use App\Services\Learning\LearningDatatableService;
use App\Services\Learning\LearningExportContext;
use App\Services\Learning\LearningExportService;
use App\Support\DurationFormatter;
use Carbon\Carbon;
use Illuminate\Database\Query\Builder;
use Illuminate\Support\Facades\DB;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class ManageOnlineRecordExportService
{
    /**
     * Headings for Manage KP OWL Online Export.
     */
    public static function headings(): array
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
            'Assessment Score (L2)',
            'Certificate',
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

    /**
     * Column widths mapping (A to AS - 45 columns).
     */
    public static function columnWidths(): array
    {
        return [
            'A'  => 6,   // No
            'B'  => 14,  // Source
            'C'  => 16,  // Employee ID
            'D'  => 30,  // Employee Name
            'E'  => 14,  // Status
            'F'  => 10,  // Grade
            'G'  => 25,  // Job Position
            'H'  => 20,  // Team
            'I'  => 25,  // Department
            'J'  => 25,  // Company
            'K'  => 35,  // Learning Title
            'L'  => 25,  // Sub Title
            'M'  => 15,  // Type
            'N'  => 24,  // Category
            'O'  => 12,  // Level
            'P'  => 12,  // Batch
            'Q'  => 25,  // Main Classification
            'R'  => 25,  // Sub Classification
            'S'  => 15,  // Corporate KPI
            'T'  => 22,  // Sub Corporate KPI
            'U'  => 18,  // Delivery Method
            'V'  => 20,  // Sub Delivery Method
            'W'  => 15,  // Provider
            'X'  => 18,  // Sub Provider
            'Y'  => 25,  // Provider Name
            'Z'  => 20,  // Venue
            'AA' => 22,  // Instructor
            'AB' => 14,  // Duration
            'AC' => 16,  // Training Hours
            'AD' => 14,  // Start Date
            'AE' => 14,  // Finish Date
            'AF' => 18,  // Participant Hours
            'AG' => 12,  // Progress
            'AH' => 20,  // Assessment Score (L2)
            'AI' => 16,  // Certificate
            'AJ' => 25,  // Attendance Reason
            'AK' => 14,  // Bond Status
            'AL' => 15,  // Bond End Date
            'AM' => 16,  // Bond Amount
            'AN' => 14,  // L1 Program
            'AO' => 15,  // L2 Numerical
            'AP' => 14,  // L2 Verbal
            'AQ' => 15,  // L3 Numerical
            'AR' => 14,  // L3 Verbal
            'AS' => 14,  // PIC
        ];
    }

    /**
     * Build base query for KP OWL records.
     */
    public function buildQuery(array $filters = []): Builder
    {
        $query = LearningDatatableService::buildBaseQuery(
            $filters,
            (string) ($filters['type'] ?? ''),
            (string) ($filters['slug'] ?? '')
        );

        $query->addSelect([
            'c.author as content_author',
            'c.status as content_status',
            'c.learning_hours as content_training_hours',
            'tp.learning_hours as playlist_training_hours',
        ]);

        if (empty($filters['sort'])) {
            $query->orderBy('unified.created_at', 'desc')
                  ->orderBy('unified.source_type', 'desc')
                  ->orderBy('unified.source_id', 'desc');
        }

        return $query;
    }

    /**
     * Fetch children content mapping for all playlists.
     */
    protected function getPlaylistChildrenMap(): array
    {
        try {
            $items = DB::table('trainingplan_contents as tc')
                ->join('contents as c', 'tc.content_id', '=', 'c.id')
                ->leftJoin('classifications as cl', 'c.classification_id', '=', 'cl.id')
                ->select([
                    'tc.trainingplan_id',
                    'c.id as content_id',
                    'c.title',
                    'c.type',
                    'c.duration',
                    'c.learning_hours',
                    'c.status',
                    'c.author',
                    'cl.classification_name',
                ])
                ->orderBy('tc.trainingplan_id')
                ->orderBy('tc.order', 'asc')
                ->get();

            $map = [];
            foreach ($items as $item) {
                $tpId = (int) $item->trainingplan_id;
                if (!isset($map[$tpId])) {
                    $map[$tpId] = [];
                }
                $map[$tpId][] = $item;
            }
            return $map;
        } catch (\Throwable $e) {
            return [];
        }
    }

    /**
     * Generate XLSX file honoring Hierarchy:
     * Include parent Playlist rows AND all child Content items under each Playlist.
     */
    public function generateXlsxFile(array $filters = []): string
    {
        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('KP OWL Online Records');

        $headers = self::headings();
        $sheet->fromArray($headers, null, 'A1');

        $headerStyle = [
            'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
            'fill' => [
                'fillType' => Fill::FILL_SOLID,
                'startColor' => ['rgb' => '0F4C81'],
            ],
            'alignment' => [
                'horizontal' => Alignment::HORIZONTAL_CENTER,
                'vertical' => Alignment::VERTICAL_CENTER,
                'wrapText' => true,
            ],
        ];
        $sheet->getStyle('A1:AS1')->applyFromArray($headerStyle);
        $sheet->getRowDimension(1)->setRowHeight(30);

        foreach (self::columnWidths() as $col => $width) {
            $sheet->getColumnDimension($col)->setWidth($width);
        }

        $query = $this->buildQuery($filters);
        $rows = $query->get();
        $childrenMap = $this->getPlaylistChildrenMap();
        $onlineService = app(OnlineFullRecordExportService::class);
        $context = $onlineService->buildBatchContext($rows);

        $rowNum = 1;
        $rowCursor = 2;

        foreach ($rows as $row) {
            $mapped = $onlineService->mapRow($row, $rowNum++, $context);
            $sheet->fromArray($mapped, null, "A{$rowCursor}");

            $isPlaylist = (($row->source_type ?? '') === 'playlist') || (!empty($row->trainingplan_id));
            if ($isPlaylist) {
                // Style parent playlist row with light accent
                $sheet->getStyle("A{$rowCursor}:AS{$rowCursor}")->getFill()
                    ->setFillType(Fill::FILL_SOLID)
                    ->getStartColor()->setRGB('EBF3FB');
                $sheet->getStyle("K{$rowCursor}")->getFont()->setBold(true);

                $tpId = (int) ($row->trainingplan_id ?? 0);
                if ($tpId > 0 && isset($childrenMap[$tpId])) {
                    foreach ($childrenMap[$tpId] as $child) {
                        $rowCursor++;
                        $childRow = [
                            '-',                                      // No
                            'KP OWL',                                 // Source
                            $row->user_id_emp ?? ($row->employee_id ?? '-'), // ID
                            $row->employee_name ?? ($row->user_name ?? '-'), // Name
                            '-',                                      // Status
                            '-',                                      // Grade
                            '-',                                      // Job Position
                            $row->user_team ?? '-',                   // Team
                            $row->user_department ?? '-',             // Department
                            $row->user_company ?? '-',                // Company
                            '    ' . $child->title,                   // Learning Title (indented)
                            'Child of ' . ($row->playlist_title ?? 'Playlist'), // Sub Title
                            ucfirst($child->type ?? 'Content'),       // Type
                            'Content Playlist',                       // Category
                            '-', '-',                                 // Level, Batch
                            $child->classification_name ?? '-',       // Classification
                            '-', '-', '-',                            // Sub Class, KPI, Sub KPI
                            'Online', 'E-learning',                   // Delivery
                            'Internal', '-', 'KP-OWL', 'KP-OWL',      // Provider info
                            $child->author ?: '-',                    // Instructor
                            DurationFormatter::format($child->duration, '-'), // Duration
                            '-',                                      // Training Hours
                            '-', '-', '-',                            // Dates, Participant Hours
                            '-',                                      // 33: Progress
                            '-',                                      // 34: Assessment Score (L2)
                            '-',                                      // 35: Certificate
                            '-',                                      // 36: Attendance Reason
                            '-',                                      // 37: Bond Status
                            '-',                                      // 38: Bond End Date
                            '-',                                      // 39: Bond Amount
                            '-',                                      // 40: L1 Program
                            '-',                                      // 41: L2 Numerical
                            '-',                                      // 42: L2 Verbal
                            '-',                                      // 43: L3 Numerical
                            '-',                                      // 44: L3 Verbal
                            '-',                                      // 45: PIC
                        ];
                        $sheet->fromArray($childRow, null, "A{$rowCursor}");
                        $sheet->getStyle("A{$rowCursor}:AS{$rowCursor}")->getFont()->setSize(9);
                    }
                }
            }

            $rowCursor++;
        }

        $sheet->freezePane('A2');

        $tempPath = tempnam(sys_get_temp_dir(), 'kp_owl_admin_') . '.xlsx';
        $writer = new Xlsx($spreadsheet);
        $writer->save($tempPath);

        return $tempPath;
    }

    public function download(array $filters = [], ?string $fileName = null): BinaryFileResponse
    {
        ini_set('max_execution_time', 300);
        ini_set('memory_limit', '512M');
        if (function_exists('set_time_limit')) {
            @set_time_limit(300);
        }

        $fileName = $fileName ?: 'KP_OWL_Online_Records_' . now()->format('Y-m-d') . '.xlsx';
        $tempPath = $this->generateXlsxFile($filters);

        return response()->download($tempPath, $fileName, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        ])->deleteFileAfterSend(true);
    }
}
