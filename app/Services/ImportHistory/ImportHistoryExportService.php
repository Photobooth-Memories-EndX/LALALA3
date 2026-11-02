<?php

namespace App\Services\ImportHistory;

use App\Models\ImportBatch;
use App\Models\ImportBatchRow;
use App\Services\ImportLog\ImportHistoryQueryService;
use App\Services\Record\PtkpColumnDefinitions;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Support\Facades\Storage;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\Cell\DataType;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class ImportHistoryExportService
{
    public const STATUS_VALID = ImportBatchRow::STATUS_VALID;
    public const STATUS_WARNING = ImportBatchRow::STATUS_WARNING;
    public const STATUS_DUPLICATE = ImportBatchRow::STATUS_DUPLICATE;
    public const STATUS_INVALID = ImportBatchRow::STATUS_INVALID;
    public const STATUS_INVALID_COMPANY = ImportBatchRow::STATUS_INVALID_COMPANY;

    public const ALL_STATUSES = [
        self::STATUS_VALID,
        self::STATUS_WARNING,
        self::STATUS_DUPLICATE,
        self::STATUS_INVALID,
        self::STATUS_INVALID_COMPANY,
    ];

    protected ImportHistoryQueryService $queryService;

    public function __construct(ImportHistoryQueryService $queryService)
    {
        $this->queryService = $queryService;
    }

    /**
     * Build the filtered Excel Spreadsheet for an import batch.
     *
     * @param ImportBatch $batch
     * @param array<string, mixed> $filters
     * @return Spreadsheet
     */
    public function buildSpreadsheet(ImportBatch $batch, array $filters = []): Spreadsheet
    {
        $rows = $this->fetchFilteredRows($batch->id, $filters);
        $canonicalCols = array_values(PtkpColumnDefinitions::all());

        // Load stored file worksheet if available to extract full original cell data
        $sourceSheet = null;
        $colMap = [];
        if ($batch->hasStoredFile()) {
            try {
                $disk = $batch->file_disk ?: 'local';
                $fullPath = Storage::disk($disk)->path($batch->file_path);
                if (file_exists($fullPath)) {
                    $reader = IOFactory::createReaderForFile($fullPath);
                    $reader->setReadDataOnly(true);
                    $sourceSpreadsheet = $reader->load($fullPath);
                    $sourceSheet = $sourceSpreadsheet->getSheet(0);
                    $colMap = $this->buildColumnMapping($sourceSheet, $canonicalCols);
                }
            } catch (\Throwable $e) {
                // Stored file reading failed, fall back gracefully to DB row_data
                $sourceSheet = null;
                $colMap = [];
            }
        }

        $activeStatus = !empty($filters['validation_status']) ? strtoupper(trim($filters['validation_status'])) : null;
        if ($activeStatus && !in_array($activeStatus, self::ALL_STATUSES, true)) {
            $activeStatus = null;
        }

        $spreadsheet = new Spreadsheet();
        $spreadsheet->getProperties()
            ->setCreator('OWL LMS')
            ->setTitle("Import Batch {$batch->batch_uuid} Export")
            ->setSubject("Import History Audit Export - " . ($activeStatus ?: 'ALL'));

        $sheet = $spreadsheet->getActiveSheet();

        if ($activeStatus) {
            // Case 1: Specific Status filtered (e.g. INVALID, VALID, etc.)
            $this->buildSingleStatusSheet($sheet, $batch, $rows, $activeStatus, $canonicalCols, $sourceSheet, $colMap);
        } else {
            // Case 2: ALL (5 groups side-by-side)
            $this->buildAllStatusesSheet($sheet, $batch, $rows, $canonicalCols, $sourceSheet, $colMap);
        }

        return $spreadsheet;
    }

    /**
     * Retrieve matching rows based on active filters (unpaginated).
     */
    protected function fetchFilteredRows(int $batchId, array $filters): EloquentCollection
    {
        $query = $this->queryService->getBatchRowsQuery($batchId, $filters);
        return $query->get();
    }

    /**
     * Map stored Excel header columns to canonical column keys.
     *
     * @param Worksheet $sourceSheet
     * @param array $canonicalCols
     * @return array<string, string> Key => ColumnLetter (e.g. 'employee_id' => 'B')
     */
    protected function buildColumnMapping(Worksheet $sourceSheet, array $canonicalCols): array
    {
        $colMap = [];
        $highestCol = $sourceSheet->getHighestColumn();
        $highestColIndex = Coordinate::columnIndexFromString($highestCol);

        for ($c = 1; $c <= $highestColIndex; $c++) {
            $letter = Coordinate::stringFromColumnIndex($c);
            $rawHeader = trim((string) $sourceSheet->getCell($letter . '1')->getValue());
            if ($rawHeader === '') {
                continue;
            }
            $norm = strtolower((string) preg_replace('/[^a-zA-Z0-9]/', '', $rawHeader));

            foreach ($canonicalCols as $colDef) {
                $key = $colDef['key'];
                if (isset($colMap[$key])) {
                    continue;
                }
                $headerNorm = strtolower((string) preg_replace('/[^a-zA-Z0-9]/', '', $colDef['header']));
                if ($norm === $headerNorm) {
                    $colMap[$key] = $letter;
                    break;
                }
                foreach ($colDef['aliases'] ?? [] as $alias) {
                    if ($norm === strtolower((string) preg_replace('/[^a-zA-Z0-9]/', '', $alias))) {
                        $colMap[$key] = $letter;
                        break 2;
                    }
                }
            }
        }

        return $colMap;
    }

    /**
     * Extract canonical cell values for a single ImportBatchRow.
     */
    protected function extractRowValues(
        ImportBatchRow $row,
        array $canonicalCols,
        ?Worksheet $sourceSheet,
        array $colMap
    ): array {
        $extracted = [];
        $rowData = is_array($row->row_data) ? $row->row_data : (json_decode((string) $row->row_data, true) ?: []);

        foreach ($canonicalCols as $colDef) {
            $key = $colDef['key'];
            $val = null;

            if ($sourceSheet && $row->row_number && isset($colMap[$key])) {
                $cellCoord = $colMap[$key] . $row->row_number;
                $val = $sourceSheet->getCell($cellCoord)->getValue();
            }

            if ($val === null || $val === '') {
                $val = $rowData[$key] ?? null;
            }

            if ($val === null || $val === '') {
                if ($key === 'name' || $key === 'employee_name') {
                    $val = $row->employee_name;
                } elseif ($key === 'employee_id') {
                    $val = $row->employee_id;
                } elseif ($key === 'course_name') {
                    $val = $row->course_name;
                }
            }

            $extracted[$key] = $this->formatCellValue($key, $val, $colDef);
        }

        $extracted['__status__'] = $row->validation_status;
        return $extracted;
    }

    /**
     * Normalize cell value for Excel presentation.
     */
    protected function formatCellValue(string $key, $val, array $colDef): string
    {
        if ($val === null || $val === '') {
            return '';
        }

        $type = $colDef['type'] ?? 'string';

        // Excel numeric date to YYYY-MM-DD
        if ($type === 'date' && is_numeric($val) && (float) $val > 1000) {
            try {
                return \PhpOffice\PhpSpreadsheet\Shared\Date::excelToDateTimeObject($val)->format('Y-m-d');
            } catch (\Throwable $e) {
                return (string) $val;
            }
        }

        // Excel decimal time to HH:mm:ss
        if (in_array($key, ['start_time', 'end_time'], true) && is_numeric($val) && (float) $val < 1.0) {
            try {
                return \PhpOffice\PhpSpreadsheet\Shared\Date::excelToDateTimeObject($val)->format('H:i:s');
            } catch (\Throwable $e) {
                return (string) $val;
            }
        }

        if (is_bool($val)) {
            return $val ? 'Yes' : 'No';
        }

        return (string) $val;
    }

    /**
     * Build worksheet for a single filtered status (e.g. INVALID, VALID, WARNING, etc.).
     */
    protected function buildSingleStatusSheet(
        Worksheet $sheet,
        ImportBatch $batch,
        EloquentCollection $rows,
        string $status,
        array $canonicalCols,
        ?Worksheet $sourceSheet,
        array $colMap
    ): void {
        $sheet->setTitle(substr($status, 0, 31));

        $totalCanonical = count($canonicalCols); // 46
        $statusColIdx = $totalCanonical + 1;    // 47 (AU)
        $lastColLetter = Coordinate::stringFromColumnIndex($statusColIdx);
        $canonicalEndColLetter = Coordinate::stringFromColumnIndex($totalCanonical);

        $color = $this->getStatusColor($status);

        // Row 1: Group Header merged across canonical columns
        $sheet->setCellValue('A1', $status);
        $sheet->mergeCells("A1:{$canonicalEndColLetter}1");
        $sheet->getStyle("A1:{$canonicalEndColLetter}1")->applyFromArray([
            'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF'], 'size' => 11],
            'alignment' => [
                'horizontal' => Alignment::HORIZONTAL_CENTER,
                'vertical'   => Alignment::VERTICAL_CENTER,
            ],
            'fill' => [
                'fillType'   => Fill::FILL_SOLID,
                'startColor' => ['rgb' => $color['fill']],
            ],
            'borders' => [
                'allBorders' => [
                    'borderStyle' => Border::BORDER_MEDIUM,
                    'color'       => ['rgb' => $color['border']],
                ],
            ],
        ]);

        // Status header top right cell on Row 1
        $sheet->setCellValue("{$lastColLetter}1", 'STATUS');
        $sheet->getStyle("{$lastColLetter}1")->applyFromArray([
            'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF'], 'size' => 10],
            'alignment' => [
                'horizontal' => Alignment::HORIZONTAL_CENTER,
                'vertical'   => Alignment::VERTICAL_CENTER,
            ],
            'fill' => [
                'fillType'   => Fill::FILL_SOLID,
                'startColor' => ['rgb' => '0F172A'], // Dark slate
            ],
            'borders' => [
                'allBorders' => [
                    'borderStyle' => Border::BORDER_THIN,
                    'color'       => ['rgb' => '334155'],
                ],
            ],
        ]);

        $sheet->getRowDimension(1)->setRowHeight(30);

        // Row 2: Canonical Column Headers (Cols 1..46)
        foreach ($canonicalCols as $idx => $colDef) {
            $letter = Coordinate::stringFromColumnIndex(1 + $idx);
            $sheet->setCellValue("{$letter}2", $colDef['header']);
            $sheet->getStyle("{$letter}2")->applyFromArray([
                'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF'], 'size' => 9.5],
                'alignment' => [
                    'horizontal' => Alignment::HORIZONTAL_CENTER,
                    'vertical'   => Alignment::VERTICAL_CENTER,
                    'wrapText'   => true,
                ],
                'fill' => [
                    'fillType'   => Fill::FILL_SOLID,
                    'startColor' => ['rgb' => '002B49'], // Tabler Navy
                ],
                'borders' => [
                    'allBorders' => [
                        'borderStyle' => Border::BORDER_THIN,
                        'color'       => ['rgb' => '475569'],
                    ],
                ],
            ]);
            $sheet->getColumnDimension($letter)->setWidth($this->getDefaultWidth($colDef['key']));
        }

        // Far right Status Header on Row 2
        $sheet->setCellValue("{$lastColLetter}2", 'Status');
        $sheet->getStyle("{$lastColLetter}2")->applyFromArray([
            'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF'], 'size' => 9.5],
            'alignment' => [
                'horizontal' => Alignment::HORIZONTAL_CENTER,
                'vertical'   => Alignment::VERTICAL_CENTER,
            ],
            'fill' => [
                'fillType'   => Fill::FILL_SOLID,
                'startColor' => ['rgb' => '0F172A'],
            ],
            'borders' => [
                'allBorders' => [
                    'borderStyle' => Border::BORDER_THIN,
                    'color'       => ['rgb' => '334155'],
                ],
            ],
        ]);
        $sheet->getColumnDimension($lastColLetter)->setWidth(18);
        $sheet->getRowDimension(2)->setRowHeight(28);

        // Rows 3..N: Data Rows
        $currentRow = 3;
        foreach ($rows as $row) {
            $extracted = $this->extractRowValues($row, $canonicalCols, $sourceSheet, $colMap);

            foreach ($canonicalCols as $idx => $colDef) {
                $letter = Coordinate::stringFromColumnIndex(1 + $idx);
                $val = $extracted[$colDef['key']] ?? '';
                $sheet->setCellValueExplicit("{$letter}{$currentRow}", (string) $val, DataType::TYPE_STRING);
                $sheet->getStyle("{$letter}{$currentRow}")->applyFromArray([
                    'font' => ['size' => 9.5, 'color' => ['rgb' => '1E293B']],
                    'alignment' => [
                        'vertical' => Alignment::VERTICAL_CENTER,
                        'horizontal' => in_array($colDef['type'], ['number', 'date', 'boolean']) ? Alignment::HORIZONTAL_CENTER : Alignment::HORIZONTAL_LEFT,
                    ],
                    'borders' => [
                        'allBorders' => [
                            'borderStyle' => Border::BORDER_THIN,
                            'color'       => ['rgb' => 'E2E8F0'],
                        ],
                    ],
                ]);
            }

            // Far right Status column
            $sheet->setCellValueExplicit("{$lastColLetter}{$currentRow}", $row->validation_status, DataType::TYPE_STRING);
            $sheet->getStyle("{$lastColLetter}{$currentRow}")->applyFromArray([
                'font' => ['bold' => true, 'size' => 9.5, 'color' => ['rgb' => $color['text_dark']]],
                'alignment' => [
                    'horizontal' => Alignment::HORIZONTAL_CENTER,
                    'vertical'   => Alignment::VERTICAL_CENTER,
                ],
                'fill' => [
                    'fillType'   => Fill::FILL_SOLID,
                    'startColor' => ['rgb' => $color['bg_subtle']],
                ],
                'borders' => [
                    'allBorders' => [
                        'borderStyle' => Border::BORDER_THIN,
                        'color'       => ['rgb' => 'CBD5E1'],
                    ],
                ],
            ]);

            $sheet->getRowDimension($currentRow)->setRowHeight(22);
            $currentRow++;
        }

        // Freeze panes at row 3 (so both header rows remain visible on scroll)
        $sheet->freezePane('A3');
        $sheet->setAutoFilter("A2:{$lastColLetter}2");
    }

    /**
     * Build worksheet when filter = ALL (5 groups side-by-side).
     */
    protected function buildAllStatusesSheet(
        Worksheet $sheet,
        ImportBatch $batch,
        EloquentCollection $rows,
        array $canonicalCols,
        ?Worksheet $sourceSheet,
        array $colMap
    ): void {
        $sheet->setTitle('Audit Log (All Groups)');

        $canonicalCount = count($canonicalCols); // 46 columns per group
        $groups = self::ALL_STATUSES;

        // Group rows by status
        $groupedRows = [];
        foreach ($groups as $g) {
            $groupedRows[$g] = [];
        }
        foreach ($rows as $row) {
            $st = $row->validation_status;
            if (isset($groupedRows[$st])) {
                $groupedRows[$st][] = $row;
            } else {
                $groupedRows[self::STATUS_VALID][] = $row;
            }
        }

        $currentColIndex = 1;
        $groupRanges = [];

        // Build Headers for each group
        foreach ($groups as $statusName) {
            $startColIdx = $currentColIndex;
            $endColIdx = $startColIdx + $canonicalCount - 1;
            $startLetter = Coordinate::stringFromColumnIndex($startColIdx);
            $endLetter = Coordinate::stringFromColumnIndex($endColIdx);
            $groupRanges[$statusName] = [
                'start_idx'    => $startColIdx,
                'end_idx'      => $endColIdx,
                'start_letter' => $startLetter,
                'end_letter'   => $endLetter,
            ];

            $color = $this->getStatusColor($statusName);

            // Row 1: Merged Group Header
            $sheet->setCellValue("{$startLetter}1", $statusName);
            $sheet->mergeCells("{$startLetter}1:{$endLetter}1");
            $sheet->getStyle("{$startLetter}1:{$endLetter}1")->applyFromArray([
                'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF'], 'size' => 11],
                'alignment' => [
                    'horizontal' => Alignment::HORIZONTAL_CENTER,
                    'vertical'   => Alignment::VERTICAL_CENTER,
                ],
                'fill' => [
                    'fillType'   => Fill::FILL_SOLID,
                    'startColor' => ['rgb' => $color['fill']],
                ],
                'borders' => [
                    'allBorders' => [
                        'borderStyle' => Border::BORDER_MEDIUM,
                        'color'       => ['rgb' => $color['border']],
                    ],
                ],
            ]);

            // Row 2: Canonical Column Headers
            foreach ($canonicalCols as $idx => $colDef) {
                $colLetter = Coordinate::stringFromColumnIndex($startColIdx + $idx);
                $sheet->setCellValue("{$colLetter}2", $colDef['header']);
                $sheet->getStyle("{$colLetter}2")->applyFromArray([
                    'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF'], 'size' => 9.5],
                    'alignment' => [
                        'horizontal' => Alignment::HORIZONTAL_CENTER,
                        'vertical'   => Alignment::VERTICAL_CENTER,
                        'wrapText'   => true,
                    ],
                    'fill' => [
                        'fillType'   => Fill::FILL_SOLID,
                        'startColor' => ['rgb' => '002B49'],
                    ],
                    'borders' => [
                        'allBorders' => [
                            'borderStyle' => Border::BORDER_THIN,
                            'color'       => ['rgb' => '475569'],
                        ],
                    ],
                ]);
                $sheet->getColumnDimension($colLetter)->setWidth($this->getDefaultWidth($colDef['key']));
            }

            $currentColIndex = $endColIdx + 1;
        }

        // Far right Status Header column
        $statusColLetter = Coordinate::stringFromColumnIndex($currentColIndex);
        $sheet->setCellValue("{$statusColLetter}1", 'STATUS');
        $sheet->setCellValue("{$statusColLetter}2", 'Status');
        $sheet->getStyle("{$statusColLetter}1:{$statusColLetter}2")->applyFromArray([
            'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF'], 'size' => 10],
            'alignment' => [
                'horizontal' => Alignment::HORIZONTAL_CENTER,
                'vertical'   => Alignment::VERTICAL_CENTER,
            ],
            'fill' => [
                'fillType'   => Fill::FILL_SOLID,
                'startColor' => ['rgb' => '0F172A'],
            ],
            'borders' => [
                'allBorders' => [
                    'borderStyle' => Border::BORDER_THIN,
                    'color'       => ['rgb' => '334155'],
                ],
            ],
        ]);
        $sheet->getColumnDimension($statusColLetter)->setWidth(18);

        $sheet->getRowDimension(1)->setRowHeight(30);
        $sheet->getRowDimension(2)->setRowHeight(28);

        // Populate Data Rows starting at Row 3
        $maxGroupCount = 0;
        foreach ($groups as $g) {
            $cnt = count($groupedRows[$g]);
            if ($cnt > $maxGroupCount) {
                $maxGroupCount = $cnt;
            }
        }

        for ($rIdx = 0; $rIdx < $maxGroupCount; $rIdx++) {
            $currentRow = 3 + $rIdx;
            $rowStatusValues = [];

            foreach ($groups as $statusName) {
                $range = $groupRanges[$statusName];
                $row = $groupedRows[$statusName][$rIdx] ?? null;

                if ($row) {
                    $rowStatusValues[] = $statusName;
                    $extracted = $this->extractRowValues($row, $canonicalCols, $sourceSheet, $colMap);

                    foreach ($canonicalCols as $cIdx => $colDef) {
                        $colLetter = Coordinate::stringFromColumnIndex($range['start_idx'] + $cIdx);
                        $val = $extracted[$colDef['key']] ?? '';
                        $sheet->setCellValueExplicit("{$colLetter}{$currentRow}", (string) $val, DataType::TYPE_STRING);
                        $sheet->getStyle("{$colLetter}{$currentRow}")->applyFromArray([
                            'font' => ['size' => 9.5, 'color' => ['rgb' => '1E293B']],
                            'alignment' => [
                                'vertical' => Alignment::VERTICAL_CENTER,
                                'horizontal' => in_array($colDef['type'], ['number', 'date', 'boolean']) ? Alignment::HORIZONTAL_CENTER : Alignment::HORIZONTAL_LEFT,
                            ],
                            'borders' => [
                                'allBorders' => [
                                    'borderStyle' => Border::BORDER_THIN,
                                    'color'       => ['rgb' => 'E2E8F0'],
                                ],
                            ],
                        ]);
                    }
                } else {
                    // Empty cells for alignment
                    foreach ($canonicalCols as $cIdx => $colDef) {
                        $colLetter = Coordinate::stringFromColumnIndex($range['start_idx'] + $cIdx);
                        $sheet->getStyle("{$colLetter}{$currentRow}")->applyFromArray([
                            'borders' => [
                                'allBorders' => [
                                    'borderStyle' => Border::BORDER_THIN,
                                    'color'       => ['rgb' => 'F1F5F9'],
                                ],
                            ],
                        ]);
                    }
                }
            }

            // Far right Status column value
            $displayStatus = !empty($rowStatusValues) ? implode(', ', array_unique($rowStatusValues)) : '-';
            $sheet->setCellValueExplicit("{$statusColLetter}{$currentRow}", $displayStatus, DataType::TYPE_STRING);
            $sheet->getStyle("{$statusColLetter}{$currentRow}")->applyFromArray([
                'font' => ['bold' => true, 'size' => 9.5, 'color' => ['rgb' => '334155']],
                'alignment' => [
                    'horizontal' => Alignment::HORIZONTAL_CENTER,
                    'vertical'   => Alignment::VERTICAL_CENTER,
                ],
                'borders' => [
                    'allBorders' => [
                        'borderStyle' => Border::BORDER_THIN,
                        'color'       => ['rgb' => 'CBD5E1'],
                    ],
                ],
            ]);

            $sheet->getRowDimension($currentRow)->setRowHeight(22);
        }

        $sheet->freezePane('A3');
        $sheet->setAutoFilter("A2:{$statusColLetter}2");
    }

    /**
     * Get theme colors for a status.
     *
     * @return array{fill: string, border: string, text_dark: string, bg_subtle: string}
     */
    protected function getStatusColor(string $status): array
    {
        return match ($status) {
            self::STATUS_VALID => [
                'fill'      => '15803D', // Forest Green
                'border'    => '14532D',
                'text_dark' => '15803D',
                'bg_subtle' => 'F0FDF4',
            ],
            self::STATUS_WARNING => [
                'fill'      => 'D97706', // Amber 600
                'border'    => 'B45309',
                'text_dark' => 'B45309',
                'bg_subtle' => 'FFFBEB',
            ],
            self::STATUS_DUPLICATE => [
                'fill'      => '0284C7', // Sky/Azure 600
                'border'    => '0369A1',
                'text_dark' => '0369A1',
                'bg_subtle' => 'F0F9FF',
            ],
            self::STATUS_INVALID_COMPANY => [
                'fill'      => 'EA580C', // Orange 600
                'border'    => 'C2410C',
                'text_dark' => 'C2410C',
                'bg_subtle' => 'FFF7ED',
            ],
            self::STATUS_INVALID => [
                'fill'      => 'DC2626', // Red 600
                'border'    => 'B91C1C',
                'text_dark' => 'B91C1C',
                'bg_subtle' => 'FEF2F2',
            ],
            default => [
                'fill'      => '475569',
                'border'    => '334155',
                'text_dark' => '334155',
                'bg_subtle' => 'F8FAFC',
            ],
        };
    }

    /**
     * Default width for canonical columns.
     */
    protected function getDefaultWidth(string $key): float
    {
        return match ($key) {
            'employee_id'               => 16.0,
            'name', 'employee_name'     => 28.0,
            'grade'                     => 12.0,
            'job_position'              => 24.0,
            'team'                      => 20.0,
            'department'                => 24.0,
            'company'                   => 24.0,
            'course_name'               => 32.0,
            'sub_course_name'           => 24.0,
            'type'                      => 14.0,
            'category'                  => 18.0,
            'level', 'batch'            => 12.0,
            'main_classification'       => 22.0,
            'sub_classification'        => 22.0,
            'delivery_method'           => 16.0,
            'sub_delivery_method'       => 18.0,
            'provider', 'sub_provider'  => 16.0,
            'provider_name'             => 24.0,
            'venue'                     => 20.0,
            'instructor'                => 22.0,
            'learning_hours'            => 15.0,
            'progress'                  => 13.0,
            'start_date', 'end_date'    => 14.0,
            'start_time', 'end_time'    => 13.0,
            'month', 'record_month'     => 15.0,
            'requirement'               => 15.0,
            'attendance'                => 14.0,
            'attendance_reason'         => 24.0,
            'bond_status'               => 14.0,
            'bond_duration'             => 15.0,
            'bond_end_date'             => 14.0,
            'bond_amount'               => 16.0,
            'l1_program'                => 18.0,
            'assessment_score'          => 14.0,
            'l2_verbal'                 => 14.0,
            'l3_numerical'              => 14.0,
            'l3_verbal'                 => 16.0,
            'certificate_availability'  => 20.0,
            'certificate_institution'   => 22.0,
            'certificate_issued_date'   => 20.0,
            'certificate_expired_date'  => 20.0,
            'pic'                       => 22.0,
            default                     => 16.0,
        };
    }
}
