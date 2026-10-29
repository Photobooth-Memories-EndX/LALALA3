<?php

namespace App\Exports\Manage;

use App\Services\Record\PtkpExcelSchema;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class PtkpImportTemplateExport
{
    /**
     * Build the official PTKP import template workbook with exactly 48 canonical columns.
     */
    public function buildSpreadsheet(): Spreadsheet
    {
        $spreadsheet = new Spreadsheet();
        $spreadsheet->getProperties()
            ->setCreator('OWL LMS')
            ->setTitle('PTKP Offline Training Import Template')
            ->setDescription('Official Excel template for importing PTKP offline training records (48 columns)');

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
        $columns = PtkpExcelSchema::columns();
        $groups = PtkpExcelSchema::groups();

        // 1. Row 1: Group Headers
        foreach ($groups as $group) {
            $startCol = $group['start_col'];
            $endCol = $group['end_col'];
            $title = $group['title'];
            $fill = $group['fill'];
            $color = $group['color'];

            $range = $startCol === $endCol ? "{$startCol}1" : "{$startCol}1:{$endCol}1";
            if ($startCol !== $endCol) {
                $sheet->mergeCells($range);
            }
            $sheet->setCellValue("{$startCol}1", $title);
            $sheet->getStyle($range)->applyFromArray([
                'font' => ['bold' => true, 'color' => ['rgb' => $color], 'size' => 10],
                'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER],
                'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => $fill]],
            ]);
        }
        $sheet->getRowDimension(1)->setRowHeight(24);

        // 2. Row 2: Column Headers
        foreach ($columns as $idx => $col) {
            $letter = Coordinate::stringFromColumnIndex($idx);
            $sheet->setCellValue("{$letter}2", $col['header']);
            $sheet->getStyle("{$letter}2")->applyFromArray([
                'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF'], 'size' => 10],
                'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER, 'wrapText' => true],
                'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '1E293B']],
                'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['rgb' => '475569']]],
            ]);
        }
        $sheet->getRowDimension(2)->setRowHeight(28);

        // 3. Row 3: Example Data Row
        foreach ($columns as $idx => $col) {
            $letter = Coordinate::stringFromColumnIndex($idx);
            $sheet->setCellValueExplicit("{$letter}3", (string) ($col['example'] ?? ''), \PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_STRING);
            $sheet->getStyle("{$letter}3")->applyFromArray([
                'font' => ['color' => ['rgb' => '334155'], 'size' => 9],
                'alignment' => ['vertical' => Alignment::VERTICAL_CENTER],
                'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['rgb' => 'CBD5E1']]],
                'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'F8FAFC']],
            ]);
        }
        $sheet->getRowDimension(3)->setRowHeight(20);

        // 4. Set column widths
        foreach (PtkpExcelSchema::columnWidths() as $colLetter => $width) {
            $sheet->getColumnDimension($colLetter)->setWidth($width);
        }

        // 5. Freeze panes at row 3, column D (Status, No, Employee ID sticky)
        $sheet->freezePane('D3');
    }

    protected function buildInstructionsSheet(Worksheet $sheet): void
    {
        $sheet->setCellValue('A1', 'INSTRUCTIONS FOR FILLING OUT PTKP OWL IMPORT TEMPLATE (48 COLUMNS)');
        $sheet->getStyle('A1')->getFont()->setBold(true)->setSize(14);
        $sheet->getRowDimension(1)->setRowHeight(30);

        $instructions = [
            ['1. "Status" Column', 'System validation status (optional on upload). Validation results are automatically evaluated by the OWL LMS system.'],
            ['2. "No" Column', 'Preview sequence number only. Not a database primary key and not stored in data tables.'],
            ['3. Employee ID', 'Mandatory employee NIP / ID. Used for matching employee accounts in the OWL LMS database.'],
            ['4. Five Identity Columns', 'Employee Name, Grade, Job Position, Team, and Department are automatically synchronized from actual records in the users table.'],
            ['5. Course Title', 'Mandatory training title. Rows with empty Course Title will be rejected (INVALID status).'],
            ['6. Company Mismatch (PTKP)', 'If the Company in Excel differs from the user record in database, the record CAN STILL BE IMPORTED (non-blocking WARNING status).'],
            ['7. Provider & Sub Provider', 'Training provider categories (Internal / External, Vendor, etc.).'],
            ['8. Certificate Columns', 'Certificate Availability (Yes/No), Institution, Issued Date, and Expired Date.'],
            ['9. Person in Charge (PIC)', 'Person in Charge (PIC) is located at the final column (column 48).'],
            ['10. Date & Time Format', 'Use YYYY-MM-DD for dates and HH:mm or HH:mm:ss for times.'],
            ['11. Duplicate Prevention', 'Records with identical participant and training combinations within the file or existing database records will be skipped automatically.'],
        ];

        $row = 3;
        foreach ($instructions as $inst) {
            $sheet->setCellValue("A{$row}", $inst[0]);
            $sheet->setCellValue("B{$row}", $inst[1]);
            $sheet->getStyle("A{$row}")->getFont()->setBold(true);
            $row++;
        }

        $sheet->getColumnDimension('A')->setWidth(30);
        $sheet->getColumnDimension('B')->setWidth(90);
    }
}
