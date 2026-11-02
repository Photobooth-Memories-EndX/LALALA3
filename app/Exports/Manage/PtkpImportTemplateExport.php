<?php

namespace App\Exports\Manage;

use App\Services\Record\PtkpExcelSchema;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\Cell\DataType;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class PtkpImportTemplateExport
{
    /**
     * Build the official PTKP import template workbook with leading visual NO column and 46 canonical columns (47 columns total).
     * Single-row header structure on Row 1 (completely free of group headers and STATUS).
     */
    public function buildSpreadsheet(): Spreadsheet
    {
        $spreadsheet = new Spreadsheet();
        $spreadsheet->getProperties()
            ->setCreator('OWL LMS')
            ->setTitle('PTKP Offline Training Import Template')
            ->setDescription('Official single-row Excel template for importing PTKP offline training records with leading NO column and 46 canonical import columns (47 columns total, free of STATUS and group headers)');

        // Sheet 1: Data sheet
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle(PtkpExcelSchema::DATA_SHEET_TITLE);
        $this->buildDataSheet($sheet);

        // Sheet 2: Instructions sheet
        $instructionsSheet = $spreadsheet->createSheet();
        $instructionsSheet->setTitle(PtkpExcelSchema::INSTRUCTIONS_SHEET_TITLE);
        $this->buildInstructionsSheet($instructionsSheet);

        $spreadsheet->setActiveSheetIndex(0);

        return $spreadsheet;
    }

    protected function buildDataSheet(Worksheet $sheet): void
    {
        $columns = PtkpExcelSchema::columns(); // 46 canonical columns (Employee ID ... PIC)
        $totalCols = count($columns) + 1;      // 47 total columns (NO + 46 canonical columns)
        $lastColLetter = Coordinate::stringFromColumnIndex($totalCols); // AU

        // 1. Row 1: Single Main Header Row (No Group Headers, No Merged Cells)
        // Col A (1): NO
        $sheet->setCellValue('A1', 'NO');
        $sheet->getStyle('A1')->applyFromArray([
            'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF'], 'size' => 10],
            'alignment' => [
                'horizontal' => Alignment::HORIZONTAL_CENTER,
                'vertical'   => Alignment::VERTICAL_CENTER,
            ],
            'fill' => [
                'fillType'   => Fill::FILL_SOLID,
                'startColor' => ['rgb' => '002B49'], // Deep PTKP navy blue
            ],
            'borders' => [
                'allBorders' => [
                    'borderStyle' => Border::BORDER_THIN,
                    'color'       => ['rgb' => '475569'],
                ],
            ],
        ]);

        // Cols B..AU (2..47): 46 Canonical Column Headers
        foreach ($columns as $idx => $col) {
            $letter = Coordinate::stringFromColumnIndex($idx + 1);
            $sheet->setCellValue("{$letter}1", $col['header']);
            $sheet->getStyle("{$letter}1")->applyFromArray([
                'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF'], 'size' => 10],
                'alignment' => [
                    'horizontal' => Alignment::HORIZONTAL_CENTER,
                    'vertical'   => Alignment::VERTICAL_CENTER,
                    'wrapText'   => true,
                ],
                'fill' => [
                    'fillType'   => Fill::FILL_SOLID,
                    'startColor' => ['rgb' => '002B49'], // Deep PTKP navy blue
                ],
                'borders' => [
                    'allBorders' => [
                        'borderStyle' => Border::BORDER_THIN,
                        'color'       => ['rgb' => '475569'],
                    ],
                ],
            ]);
        }
        $sheet->getRowDimension(1)->setRowHeight(28);

        // 2. Row 2: Example Data Row (NO = 1, followed by canonical example values)
        $sheet->setCellValueExplicit('A2', 1, DataType::TYPE_NUMERIC);
        $sheet->getStyle('A2')->applyFromArray([
            'font' => ['color' => ['rgb' => '334155'], 'size' => 9],
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
            'fill' => [
                'fillType'   => Fill::FILL_SOLID,
                'startColor' => ['rgb' => 'F8FAFC'],
            ],
        ]);

        foreach ($columns as $idx => $col) {
            $letter = Coordinate::stringFromColumnIndex($idx + 1);
            $exampleVal = (string) ($col['example'] ?? '');
            $sheet->setCellValueExplicit("{$letter}2", $exampleVal, DataType::TYPE_STRING);
            $sheet->getStyle("{$letter}2")->applyFromArray([
                'font' => ['color' => ['rgb' => '334155'], 'size' => 9],
                'alignment' => ['vertical' => Alignment::VERTICAL_CENTER],
                'borders' => [
                    'allBorders' => [
                        'borderStyle' => Border::BORDER_THIN,
                        'color'       => ['rgb' => 'CBD5E1'],
                    ],
                ],
                'fill' => [
                    'fillType'   => Fill::FILL_SOLID,
                    'startColor' => ['rgb' => 'F8FAFC'],
                ],
            ]);
        }
        $sheet->getRowDimension(2)->setRowHeight(20);

        // 3. Rows 3 through 7: Empty Data Rows with sequential numbers 2, 3, 4, 5, 6
        for ($r = 3; $r <= 7; $r++) {
            $seq = $r - 1; // 2, 3, 4, 5, 6
            $sheet->setCellValueExplicit("A{$r}", $seq, DataType::TYPE_NUMERIC);
            $sheet->getStyle("A{$r}")->applyFromArray([
                'font' => ['color' => ['rgb' => '64748B'], 'size' => 9],
                'alignment' => [
                    'horizontal' => Alignment::HORIZONTAL_CENTER,
                    'vertical'   => Alignment::VERTICAL_CENTER,
                ],
                'borders' => [
                    'allBorders' => [
                        'borderStyle' => Border::BORDER_THIN,
                        'color'       => ['rgb' => 'E2E8F0'],
                    ],
                ],
                'fill' => [
                    'fillType'   => Fill::FILL_SOLID,
                    'startColor' => ['rgb' => $r % 2 === 0 ? 'F8FAFC' : 'FFFFFF'],
                ],
            ]);

            for ($c = 2; $c <= $totalCols; $c++) {
                $letter = Coordinate::stringFromColumnIndex($c);
                $sheet->getStyle("{$letter}{$r}")->applyFromArray([
                    'borders' => [
                        'allBorders' => [
                            'borderStyle' => Border::BORDER_THIN,
                            'color'       => ['rgb' => 'E2E8F0'],
                        ],
                    ],
                    'fill' => [
                        'fillType'   => Fill::FILL_SOLID,
                        'startColor' => ['rgb' => $r % 2 === 0 ? 'F8FAFC' : 'FFFFFF'],
                    ],
                ]);
            }
            $sheet->getRowDimension($r)->setRowHeight(20);
        }

        // 4. Set column widths
        foreach (PtkpExcelSchema::columnWidths() as $colLetter => $width) {
            $sheet->getColumnDimension($colLetter)->setWidth($width);
        }

        // 5. Freeze panes at B2 (sticky NO column A, sticky header Row 1)
        $sheet->freezePane('B2');

        // 6. Auto filter covering all 47 columns on Row 1 (A1:AU1)
        $sheet->setAutoFilter("A1:{$lastColLetter}1");
    }

    protected function buildInstructionsSheet(Worksheet $sheet): void
    {
        $sheet->setCellValue('A1', 'INSTRUCTIONS FOR FILLING OUT PTKP OWL IMPORT TEMPLATE (47 COLUMNS)');
        $sheet->getStyle('A1')->getFont()->setBold(true)->setSize(14);
        $sheet->getRowDimension(1)->setRowHeight(30);

        $instructions = [
            ['1. Template Structure', 'The template uses a single header row on Row 1 (47 columns). Column A (NO) is a visual sequence number only. Data entry begins on Row 2 (Employee ID through PIC).'],
            ['2. "NO" Column', 'Column A contains sequential row numbering (1, 2, 3...) for user reference. It is not saved to the database and does not affect data validation.'],
            ['3. Employee ID', 'Mandatory employee NIP / ID at Column 2 (B). Used for matching employee accounts in the OWL LMS database.'],
            ['4. Five Identity Columns', 'Employee Name, Grade, Job Position, Team, and Department are automatically synchronized from actual records in the users table.'],
            ['5. Course Title', 'Mandatory training title. Rows with empty Course Title will be rejected (INVALID status).'],
            ['6. Company Mismatch (PTKP)', 'If the Company in Excel differs from the user record in database, the record CAN STILL BE IMPORTED (non-blocking WARNING status).'],
            ['7. Provider & Sub Provider', 'Training provider categories (Internal / External, Vendor, etc.) and provider name.'],
            ['8. Attendance & Bond', 'Attendance (Yes/No), attendance reason, service bond obligation duration and bond amount.'],
            ['9. Kirkpatrick Evaluations', 'Score (L1) Course, Score (L2), L2 Status, Score (L3), and L3 Status.'],
            ['10. Certificate Columns', 'Certificate Availability (Yes/No), Institution, Issued Date, and Expired Date.'],
            ['11. Person in Charge (PIC)', 'Person in Charge (PIC) is located at the final column (Column 47 / AU).'],
            ['12. Date & Time Format', 'Use YYYY-MM-DD for dates and HH:mm or HH:mm:ss for times.'],
            ['13. Duplicate Prevention', 'Records with identical participant and training combinations within the file or existing database records will be skipped automatically.'],
        ];

        $row = 3;
        foreach ($instructions as $inst) {
            $sheet->setCellValue("A{$row}", $inst[0]);
            $sheet->setCellValue("B{$row}", $inst[1]);
            $sheet->getStyle("A{$row}")->getFont()->setBold(true);
            $row++;
        }

        $sheet->getColumnDimension('A')->setWidth(30);
        $sheet->getColumnDimension('B')->setWidth(95);
    }
}
