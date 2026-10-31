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
     * Build the simplified official PTKP import template workbook with single-row header (46 canonical columns).
     */
    public function buildSpreadsheet(): Spreadsheet
    {
        $spreadsheet = new Spreadsheet();
        $spreadsheet->getProperties()
            ->setCreator('OWL LMS')
            ->setTitle('PTKP Offline Training Import Template')
            ->setDescription('Official simplified Excel template for importing PTKP offline training records (46 columns)');

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
        $columns = PtkpExcelSchema::columns(); // 46 columns (Employee ID ... PIC)
        $totalCols = count($columns);
        $lastColLetter = Coordinate::stringFromColumnIndex($totalCols);

        // 1. Row 1: Single Main Header Row (No Group Headers, No Merged Cells)
        foreach ($columns as $idx => $col) {
            $letter = Coordinate::stringFromColumnIndex($idx);
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

        // 2. Row 2: Example Data Row (Values start immediately on Row 2)
        foreach ($columns as $idx => $col) {
            $letter = Coordinate::stringFromColumnIndex($idx);
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

        // 3. Set column widths
        foreach (PtkpExcelSchema::columnWidths() as $colLetter => $width) {
            $sheet->getColumnDimension($colLetter)->setWidth($width);
        }

        // 4. Freeze panes directly below the single header row at A2
        $sheet->freezePane('A2');

        // 5. Auto filter covering all 46 columns (A1:AT1)
        $sheet->setAutoFilter("A1:{$lastColLetter}1");
    }

    protected function buildInstructionsSheet(Worksheet $sheet): void
    {
        $sheet->setCellValue('A1', 'INSTRUCTIONS FOR FILLING OUT PTKP OWL IMPORT TEMPLATE (46 COLUMNS)');
        $sheet->getStyle('A1')->getFont()->setBold(true)->setSize(14);
        $sheet->getRowDimension(1)->setRowHeight(30);

        $instructions = [
            ['1. Simplified Header', 'The template uses a single main header row on Row 1 without category groupings or merge cells. Data starts directly on Row 2.'],
            ['2. Employee ID', 'Mandatory employee NIP / ID at Column 1 (A). Used for matching employee accounts in the OWL LMS database.'],
            ['3. Five Identity Columns', 'Employee Name, Grade, Job Position, Team, and Department are automatically synchronized from actual records in the users table.'],
            ['4. Course Title', 'Mandatory training title. Rows with empty Course Title will be rejected (INVALID status).'],
            ['5. Company Mismatch (PTKP)', 'If the Company in Excel differs from the user record in database, the record CAN STILL BE IMPORTED (non-blocking WARNING status).'],
            ['6. Provider & Sub Provider', 'Training provider categories (Internal / External, Vendor, etc.) and provider name.'],
            ['7. Attendance & Bond', 'Attendance (Yes/No), attendance reason, service bond obligation duration and bond amount.'],
            ['8. Kirkpatrick Evaluations', 'Score (L1) Course, Score (L2), L2 Status, Score (L3), and L3 Status.'],
            ['9. Certificate Columns', 'Certificate Availability (Yes/No), Institution, Issued Date, and Expired Date.'],
            ['10. Person in Charge (PIC)', 'Person in Charge (PIC) is located at the final column (Column 46 / AT).'],
            ['11. Date & Time Format', 'Use YYYY-MM-DD for dates and HH:mm or HH:mm:ss for times.'],
            ['12. Duplicate Prevention', 'Records with identical participant and training combinations within the file or existing database records will be skipped automatically.'],
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
