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
     * Build the official PTKP import template workbook.
     */
    public function buildSpreadsheet(): Spreadsheet
    {
        $spreadsheet = new Spreadsheet();
        $spreadsheet->getProperties()
            ->setCreator('OWL LMS')
            ->setTitle('PTKP Offline Training Import Template')
            ->setDescription('Official Excel template for importing PTKP offline training records');

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

        // 5. Freeze panes at row 3 (header rows sticky)
        $sheet->freezePane('C3');
    }

    protected function buildInstructionsSheet(Worksheet $sheet): void
    {
        $sheet->setCellValue('A1', 'PETUNJUK PENGISIAN TEMPLATE IMPORT DATA PTKP OWL');
        $sheet->getStyle('A1')->getFont()->setBold(true)->setSize(14);
        $sheet->getRowDimension(1)->setRowHeight(30);

        $instructions = [
            ['1. Kolom "No"', 'Nomor urut preview saja. Bukan identifier karyawan dan tidak disimpan ke database.'],
            ['2. Employee ID', 'Wajib diisi dengan NIP/Employee ID karyawan. Digunakan untuk mencocokkan user di LMS OWL.'],
            ['3. Lima Kolom Identitas', 'Employee Name, Grade, Job Position, Team, dan Department akan diambil otomatis dari tabel users. Nilai di Excel tidak akan menimpa data user.'],
            ['4. Course Title', 'Wajib diisi dengan judul pelatihan. Baris dengan Course Title kosong akan ditandai INVALID.'],
            ['5. Perbedaan Company (PTKP)', 'Jika Company di Excel berbeda dengan Company user di database, data tetap DAPAT DIIMPORT (status WARNING non-blocking).'],
            ['6. Provider & Sub Provider', 'Dua kolom baru untuk kategori penyedia training (Internal/External, Vendor, dsb).'],
            ['7. Kolom Dihapus', 'Kolom Total Engagement Time dan Certificate (lama) telah dihapus dari template PTKP.'],
            ['8. Format Tanggal & Waktu', 'Gunakan format YYYY-MM-DD untuk tanggal dan HH:mm atau HH:mm:ss untuk waktu.'],
            ['9. Aturan Duplikat', 'Data dengan kombinasi event dan karyawan yang sama dalam file atau database akan dilewati secara aman.'],
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
