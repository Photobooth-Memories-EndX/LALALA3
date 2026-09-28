<?php

namespace App\Services\Record;

use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class OfflineTrainingExcelSchema
{
    public const TEMPLATE_FILENAME = 'Non_KP_OWL_Import_Template.xlsx';
    public const DATA_SHEET_TITLE = 'Non KP OWL Import';
    public const INSTRUCTIONS_SHEET_TITLE = 'Instructions';

    /**
     * Parent grouped header categories with column spans.
     */
    public static function groups(): array
    {
        return [
            'job_information' => [
                'title'     => 'JOB INFORMATION',
                'start_col' => 'B',
                'end_col'   => 'H',
                'span'      => 7,
                'color'     => 'E0E7FF', // Indigo 100
            ],
            'training_information' => [
                'title'     => 'TRAINING INFORMATION',
                'start_col' => 'I',
                'end_col'   => 'AG',
                'span'      => 25,
                'color'     => 'FEF3C7', // Amber 100
            ],
            'attendance' => [
                'title'     => 'ATTENDANCE',
                'start_col' => 'AH',
                'end_col'   => 'AI',
                'span'      => 2,
                'color'     => 'DCFCE7', // Emerald 100
            ],
            'obligation' => [
                'title'     => 'OBLIGATION',
                'start_col' => 'AJ',
                'end_col'   => 'AL',
                'span'      => 3,
                'color'     => 'FEE2E2', // Rose 100
            ],
            'l1' => [
                'title'     => 'L1',
                'start_col' => 'AM',
                'end_col'   => 'AM',
                'span'      => 1,
                'color'     => 'F3E8FF', // Purple 100
            ],
            'l2' => [
                'title'     => 'L2 (POST TEST)',
                'start_col' => 'AN',
                'end_col'   => 'AO',
                'span'      => 2,
                'color'     => 'EDE9FE', // Violet 100
            ],
            'l3' => [
                'title'     => 'L3 (PROJECT)',
                'start_col' => 'AP',
                'end_col'   => 'AR',
                'span'      => 3,
                'color'     => 'E0F2FE', // Sky 100
            ],
        ];
    }

    /**
     * Complete canonical 44-column specification.
     */
    public static function columns(): array
    {
        return [
            // JOB INFORMATION (Cols 1-8 / A-H)
            1  => ['header' => 'No', 'group' => null, 'key' => null, 'required' => false, 'type' => 'number', 'example' => 1, 'note' => 'Row number (optional)'],
            2  => ['header' => 'ID', 'group' => 'JOB INFORMATION', 'key' => 'employee_id', 'required' => true, 'type' => 'string', 'example' => '100234', 'note' => 'Employee ID / NIP (Required for matching)'],
            3  => ['header' => 'Name', 'group' => 'JOB INFORMATION', 'key' => 'name', 'required' => true, 'type' => 'string', 'example' => 'Budi Santoso', 'note' => 'Participant full name'],
            4  => ['header' => 'Grade', 'group' => 'JOB INFORMATION', 'key' => 'grade', 'required' => false, 'type' => 'string', 'example' => '4A', 'note' => 'Employee grade level'],
            5  => ['header' => 'Job Position', 'group' => 'JOB INFORMATION', 'key' => 'job_position', 'required' => false, 'type' => 'string', 'example' => 'Electrical Maintenance Engineer', 'note' => 'Official job title'],
            6  => ['header' => 'Team', 'group' => 'JOB INFORMATION', 'key' => 'team', 'required' => false, 'type' => 'string', 'example' => 'Rolling Mill 1', 'note' => 'Work unit or team'],
            7  => ['header' => 'Department', 'group' => 'JOB INFORMATION', 'key' => 'department', 'required' => false, 'type' => 'string', 'example' => 'Maintenance Department', 'note' => 'Department division'],
            8  => ['header' => 'Company', 'group' => 'JOB INFORMATION', 'key' => 'company', 'required' => false, 'type' => 'string', 'example' => 'PT Krakatau Posco', 'note' => 'Company legal entity'],

            // TRAINING INFORMATION (Cols 9-33 / I-AG)
            9  => ['header' => 'Course Name', 'group' => 'TRAINING INFORMATION', 'key' => 'course_name', 'required' => true, 'type' => 'string', 'example' => 'High Voltage Safety & Operation', 'note' => 'Primary course / training title'],
            10 => ['header' => 'Sub_Course Name', 'group' => 'TRAINING INFORMATION', 'key' => 'sub_course_name', 'required' => false, 'type' => 'string', 'example' => 'Switchgear Troubleshooting Module', 'note' => 'Specific module or topic name'],
            11 => ['header' => 'Type', 'group' => 'TRAINING INFORMATION', 'key' => 'type', 'required' => false, 'type' => 'string', 'example' => 'In-House', 'note' => 'In-House, Public, Certification, Workshop, etc.'],
            12 => ['header' => 'Category', 'group' => 'TRAINING INFORMATION', 'key' => 'category', 'required' => false, 'type' => 'string', 'example' => 'Technical Competency', 'note' => 'Training category classification'],
            13 => ['header' => 'Level', 'group' => 'TRAINING INFORMATION', 'key' => 'level', 'required' => false, 'type' => 'string', 'example' => 'Intermediate', 'note' => 'Basic, Intermediate, Advanced'],
            14 => ['header' => 'Batch', 'group' => 'TRAINING INFORMATION', 'key' => 'batch', 'required' => false, 'type' => 'string', 'example' => 'Batch 1', 'note' => 'Training batch or wave designation'],
            15 => ['header' => 'Main Classification', 'group' => 'TRAINING INFORMATION', 'key' => 'main_classification', 'required' => false, 'type' => 'string', 'example' => 'Technical', 'note' => 'Competency, Leadership, Technical, Quality & Safety'],
            16 => ['header' => 'Sub Classification', 'group' => 'TRAINING INFORMATION', 'key' => 'sub_classification', 'required' => false, 'type' => 'string', 'example' => 'Plant Maintenance', 'note' => 'Sub category classification'],
            17 => ['header' => "Counted as '26 Corporate KPI (Yes/No)", 'group' => 'TRAINING INFORMATION', 'key' => 'counted_corporate_kpi', 'required' => false, 'type' => 'boolean', 'example' => 'Yes', 'note' => 'Yes / No'],
            18 => ['header' => "Sub '26 Corporate KPI", 'group' => 'TRAINING INFORMATION', 'key' => 'sub_corporate_kpi', 'required' => false, 'type' => 'string', 'example' => 'Safety Incident Reduction', 'note' => 'KPI strategic program'],
            19 => ['header' => 'Delivery Method', 'group' => 'TRAINING INFORMATION', 'key' => 'delivery_method', 'required' => false, 'type' => 'string', 'example' => 'Classroom', 'note' => 'Classroom, Practical, Hybrid'],
            20 => ['header' => 'Sub_Delivery Method', 'group' => 'TRAINING INFORMATION', 'key' => 'sub_delivery_method', 'required' => false, 'type' => 'string', 'example' => 'Hands-on Lab', 'note' => 'Sub delivery technique'],
            21 => ['header' => 'Provider', 'group' => 'TRAINING INFORMATION', 'key' => 'provider', 'required' => false, 'type' => 'string', 'example' => 'External', 'note' => 'Internal / External'],
            22 => ['header' => 'Sub_Provider', 'group' => 'TRAINING INFORMATION', 'key' => 'sub_provider', 'required' => false, 'type' => 'string', 'example' => 'Vendor', 'note' => 'Vendor / University / Certified Center'],
            23 => ['header' => 'Duration', 'group' => 'TRAINING INFORMATION', 'key' => 'duration_seconds', 'required' => false, 'type' => 'duration', 'example' => '08:00:00', 'note' => 'Format: HH:MM:SS (e.g. 08:00:00)'],
            24 => ['header' => 'Learning (Hours)', 'group' => 'TRAINING INFORMATION', 'key' => 'learning_hours', 'required' => false, 'type' => 'number', 'example' => 8.0, 'note' => 'Credited learning hours (e.g. 8.0)'],
            25 => ['header' => 'Progress', 'group' => 'TRAINING INFORMATION', 'key' => 'progress', 'required' => false, 'type' => 'number', 'example' => 100, 'note' => '0 to 100 (%)'],
            26 => ['header' => 'Start Date', 'group' => 'TRAINING INFORMATION', 'key' => 'start_date', 'required' => false, 'type' => 'date', 'example' => '2026-08-10', 'note' => 'Format: YYYY-MM-DD'],
            27 => ['header' => 'Finish Date', 'group' => 'TRAINING INFORMATION', 'key' => 'finish_date', 'required' => false, 'type' => 'date', 'example' => '2026-08-10', 'note' => 'Format: YYYY-MM-DD'],
            28 => ['header' => 'Month', 'group' => 'TRAINING INFORMATION', 'key' => null, 'required' => false, 'type' => 'string', 'example' => 'August', 'note' => 'Excel display only (derived from date, not stored in DB)'],
            29 => ['header' => 'Provider Name', 'group' => 'TRAINING INFORMATION', 'key' => 'provider_name', 'required' => false, 'type' => 'string', 'example' => 'PT Schneider Indonesia', 'note' => 'Training institution / vendor name'],
            30 => ['header' => 'Venue', 'group' => 'TRAINING INFORMATION', 'key' => 'venue', 'required' => false, 'type' => 'string', 'example' => 'Auditorium Hall B', 'note' => 'Training location / room'],
            31 => ['header' => 'Instructor', 'group' => 'TRAINING INFORMATION', 'key' => 'instructor', 'required' => false, 'type' => 'string', 'example' => 'Ir. Gunawan, M.T.', 'note' => 'Trainer / instructor name'],
            32 => ['header' => 'Assessment Score', 'group' => 'TRAINING INFORMATION', 'key' => 'assessment_score', 'required' => false, 'type' => 'number', 'example' => 85.5, 'note' => 'Evaluation score (0-100)'],
            33 => ['header' => 'Certificate', 'group' => 'TRAINING INFORMATION', 'key' => 'certificate', 'required' => false, 'type' => 'string', 'example' => 'Yes', 'note' => 'Yes / No'],

            // ATTENDANCE (Cols 34-35 / AH-AI)
            34 => ['header' => 'Attendance Yes/No', 'group' => 'ATTENDANCE', 'key' => 'attendance', 'required' => false, 'type' => 'boolean', 'example' => 'Yes', 'note' => 'Yes / No'],
            35 => ['header' => 'Reason', 'group' => 'ATTENDANCE', 'key' => 'attendance_reason', 'required' => false, 'type' => 'string', 'example' => 'Present in all sessions', 'note' => 'Attendance justification if absent'],

            // OBLIGATION (Cols 36-38 / AJ-AL)
            36 => ['header' => 'Bond Status', 'group' => 'OBLIGATION', 'key' => 'bond_status', 'required' => false, 'type' => 'string', 'example' => 'No', 'note' => 'Yes / No'],
            37 => ['header' => 'Bond End Date', 'group' => 'OBLIGATION', 'key' => 'bond_end_date', 'required' => false, 'type' => 'date', 'example' => '', 'note' => 'Format: YYYY-MM-DD'],
            38 => ['header' => 'Bond Amount', 'group' => 'OBLIGATION', 'key' => 'bond_amount', 'required' => false, 'type' => 'number', 'example' => '', 'note' => 'Service bond value (IDR)'],

            // L1 (Col 39 / AM)
            39 => ['header' => 'Program', 'group' => 'L1', 'key' => 'l1_program', 'required' => false, 'type' => 'string', 'example' => 'Very Good', 'note' => 'Reaction evaluation / rating'],

            // L2 (POST TEST) (Cols 40-41 / AN-AO)
            40 => ['header' => 'Numerical', 'group' => 'L2 (POST TEST)', 'key' => 'l2_numerical', 'required' => false, 'type' => 'number', 'example' => 88.0, 'note' => 'Post-test score (0-100)'],
            41 => ['header' => 'Verbal', 'group' => 'L2 (POST TEST)', 'key' => 'l2_verbal', 'required' => false, 'type' => 'string', 'example' => 'Passed', 'note' => 'Post-test qualitative result'],

            // L3 (PROJECT) (Cols 42-44 / AP-AR)
            42 => ['header' => 'Numerical', 'group' => 'L3 (PROJECT)', 'key' => 'l3_numerical', 'required' => false, 'type' => 'number', 'example' => 90.0, 'note' => 'Project implementation score (0-100)'],
            43 => ['header' => 'Verbal', 'group' => 'L3 (PROJECT)', 'key' => 'l3_verbal', 'required' => false, 'type' => 'string', 'example' => 'Implemented successfully in plant', 'note' => 'Behavior / project result evaluation'],
            44 => ['header' => 'PIC', 'group' => 'L3 (PROJECT)', 'key' => 'pic', 'required' => false, 'type' => 'string', 'example' => 'HRDC Team', 'note' => 'HR / Training administrator PIC'],
        ];
    }

    /**
     * Exact flat list of 44 header strings for Row 2.
     */
    public static function headers(): array
    {
        $headers = [];
        foreach (self::columns() as $col) {
            $headers[] = $col['header'];
        }
        return $headers;
    }

    /**
     * Total number of supported template columns.
     */
    public static function totalColumns(): int
    {
        return count(self::columns()); // Exactly 44
    }

    /**
     * System fields that must NEVER appear in the Excel template.
     */
    public static function systemFields(): array
    {
        return [
            'id',
            'offline_training_id',
            'user_id',
            'created_by',
            'created_at',
            'updated_at',
            'deleted_at',
        ];
    }

    /**
     * Minimum required database keys for successful row import.
     */
    public static function requiredDatabaseKeys(): array
    {
        return ['course_name', 'employee_id', 'name'];
    }

    /**
     * Grouped Row 1 headers array (44 elements).
     */
    public static function groupedRow1Headers(): array
    {
        $row1 = [];
        $groups = self::groups();
        $groupStartMap = [];
        foreach ($groups as $group) {
            $groupStartMap[$group['start_col']] = $group['title'];
        }

        // Col 1 is 'A' -> 'No'
        $row1[] = 'No';

        // Cols 2 to 44 (B to AR)
        for ($col = 2; $col <= 44; $col++) {
            $colLetter = Coordinate::stringFromColumnIndex($col);
            $row1[] = $groupStartMap[$colLetter] ?? '';
        }

        return $row1;
    }

    /**
     * Canonical column widths matching template and export specifications.
     */
    public static function columnWidths(): array
    {
        return [
            'A'  => 6,   // No
            'B'  => 14,  // Employee ID
            'C'  => 25,  // Name
            'D'  => 10,  // Grade
            'E'  => 25,  // Job Position
            'F'  => 20,  // Team
            'G'  => 25,  // Department
            'H'  => 20,  // Company
            'I'  => 35,  // Course Name
            'J'  => 25,  // Sub Course Name
            'K'  => 15,  // Type
            'L'  => 20,  // Category
            'M'  => 15,  // Level
            'N'  => 15,  // Batch
            'O'  => 22,  // Main Classification
            'P'  => 20,  // Sub Classification
            'Q'  => 18,  // KPI
            'R'  => 20,  // Sub KPI
            'S'  => 15,  // Delivery Method
            'T'  => 18,  // Sub Delivery Method
            'U'  => 15,  // Provider
            'V'  => 18,  // Sub Provider
            'W'  => 12,  // Duration
            'X'  => 15,  // Learning Hours
            'Y'  => 12,  // Progress
            'Z'  => 14,  // Start Date
            'AA' => 14,  // Finish Date
            'AB' => 14,  // Month
            'AC' => 25,  // Provider Name
            'AD' => 20,  // Venue
            'AE' => 22,  // Instructor
            'AF' => 15,  // Score
            'AG' => 12,  // Certificate
            'AH' => 14,  // Attendance
            'AI' => 20,  // Reason
            'AJ' => 12,  // Bond Status
            'AK' => 14,  // Bond End Date
            'AL' => 16,  // Bond Amount
            'AM' => 14,  // L1
            'AN' => 12,  // L2 Numerical
            'AO' => 15,  // L2 Verbal
            'AP' => 12,  // L3 Numerical
            'AQ' => 25,  // L3 Verbal
            'AR' => 20,  // PIC
        ];
    }

    /**
     * Apply grouped Row 1, Row 2 column headers, colors, borders, row heights, and freeze pane.
     */
    public static function applyDataSheetHeaderStyles(Worksheet $sheet): void
    {
        // Row 1: No header in A1, followed by merged parent groups
        $sheet->setCellValue('A1', 'No');

        $groups = self::groups();
        foreach ($groups as $group) {
            $range = $group['start_col'] . '1:' . $group['end_col'] . '1';
            if ($group['start_col'] !== $group['end_col']) {
                $sheet->mergeCells($range);
            }
            $sheet->setCellValue($group['start_col'] . '1', $group['title']);

            // Style group header
            $sheet->getStyle($range)->applyFromArray([
                'font' => [
                    'bold'  => true,
                    'size'  => 11,
                    'color' => ['rgb' => '1E293B'], // Slate 800
                ],
                'alignment' => [
                    'horizontal' => Alignment::HORIZONTAL_CENTER,
                    'vertical'   => Alignment::VERTICAL_CENTER,
                ],
                'fill' => [
                    'fillType'   => Fill::FILL_SOLID,
                    'startColor' => ['rgb' => $group['color']],
                ],
                'borders' => [
                    'outline' => [
                        'borderStyle' => Border::BORDER_THIN,
                        'color'       => ['rgb' => '94A3B8'],
                    ],
                ],
            ]);
        }

        // Style A1 'No' header
        $sheet->getStyle('A1')->applyFromArray([
            'font' => [
                'bold'  => true,
                'size'  => 11,
                'color' => ['rgb' => '1E293B'],
            ],
            'alignment' => [
                'horizontal' => Alignment::HORIZONTAL_CENTER,
                'vertical'   => Alignment::VERTICAL_CENTER,
            ],
            'fill' => [
                'fillType'   => Fill::FILL_SOLID,
                'startColor' => ['rgb' => 'F1F5F9'],
            ],
            'borders' => [
                'outline' => [
                    'borderStyle' => Border::BORDER_THIN,
                    'color'       => ['rgb' => '94A3B8'],
                ],
            ],
        ]);

        // Row 2: Child Column Headers
        $columns = self::columns();
        foreach ($columns as $idx => $col) {
            $colLetter = Coordinate::stringFromColumnIndex($idx);
            $sheet->setCellValue($colLetter . '2', $col['header']);

            // Header cell styling
            $isReq = $col['required'];
            $sheet->getStyle($colLetter . '2')->applyFromArray([
                'font' => [
                    'bold'  => true,
                    'size'  => 10,
                    'color' => ['rgb' => $isReq ? 'B91C1C' : '334155'], // Red for required, slate for optional
                ],
                'alignment' => [
                    'horizontal' => Alignment::HORIZONTAL_CENTER,
                    'vertical'   => Alignment::VERTICAL_CENTER,
                    'wrapText'   => true,
                ],
                'fill' => [
                    'fillType'   => Fill::FILL_SOLID,
                    'startColor' => ['rgb' => $isReq ? 'FEF2F2' : 'F8FAFC'],
                ],
                'borders' => [
                    'allBorders' => [
                        'borderStyle' => Border::BORDER_THIN,
                        'color'       => ['rgb' => 'CBD5E1'],
                    ],
                ],
            ]);
        }

        // Row Heights
        $sheet->getRowDimension(1)->setRowHeight(28);
        $sheet->getRowDimension(2)->setRowHeight(36);

        // Freeze top 2 rows
        $sheet->freezePane('A3');
    }

    /**
     * Populate the Instructions worksheet with guidelines and styling.
     */
    public static function populateInstructionsSheet(Worksheet $instructionSheet): void
    {
        $instructionSheet->setCellValue('A1', 'KP OWL LMS — Non KP OWL Offline Training Import Guidelines');
        $instructionSheet->getStyle('A1')->getFont()->setBold(true)->setSize(14)->getColor()->setRGB('0F172A');
        $instructionSheet->getRowDimension(1)->setRowHeight(26);

        $guidelines = [
            ['Section', 'Guideline & Rule', 'Details / Examples'],
            ['1. Header Integrity', 'Do NOT rename, delete, or rearrange columns', 'The importer strictly verifies the 44-column structure. Required headers must remain in Row 2.'],
            ['2. Participant Records', 'One row represents one participant record', 'Each participant attending an offline training program must be on their own separate row.'],
            ['3. Event Grouping', 'Multiple attendees belonging to the same training are grouped', 'If Course Name, Batch, Type, Venue, Start Date, and Finish Date match, they will be unified into one training event with multiple participants.'],
            ['4. Employee Matching', 'Column B (ID) matches Employee ID / NIP', 'Provide valid employee ID. LMS will automatically link participant to existing user accounts when found.'],
            ['5. Progress & Hours', 'Progress uses numbers (0–100), Hours uses decimals', 'Example: Progress = 100, Learning (Hours) = 8.0 or 16.5.'],
            ['6. Date Format', 'Dates must be formatted as YYYY-MM-DD or Excel dates', 'Example: 2026-08-10. Finish Date cannot be earlier than Start Date.'],
            ['7. Duration Format', 'Duration should be formatted as HH:MM:SS or seconds', 'Example: 08:00:00 for an 8-hour session.'],
            ['8. Boolean Fields', 'Attendance, Corporate KPI, Certificate use Yes / No', 'Accepted values: Yes, Y, Ya, No, N.'],
            ['9. Post Test & Projects', 'L2 and L3 evaluations are tracked per participant', 'L2 Numerical (e.g. 85), L2 Verbal (Passed), L3 Numerical (e.g. 90), L3 Verbal (Applied in Dept).'],
            ['10. Safe Deduplication', 'Duplicate participants are skipped safely', 'If the same employee is already recorded for the same training session, the row is safely skipped without corrupting database state.'],
        ];

        $startRow = 3;
        foreach ($guidelines as $rIdx => $row) {
            $currRow = $startRow + $rIdx;
            $instructionSheet->setCellValue('A' . $currRow, $row[0]);
            $instructionSheet->setCellValue('B' . $currRow, $row[1]);
            $instructionSheet->setCellValue('C' . $currRow, $row[2]);

            if ($rIdx === 0) {
                $instructionSheet->getStyle("A{$currRow}:C{$currRow}")->applyFromArray([
                    'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
                    'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'D97706']],
                    'alignment' => ['vertical' => Alignment::VERTICAL_CENTER],
                ]);
                $instructionSheet->getRowDimension($currRow)->setRowHeight(24);
            } else {
                $instructionSheet->getStyle("A{$currRow}:C{$currRow}")->applyFromArray([
                    'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['rgb' => 'E2E8F0']]],
                    'alignment' => ['vertical' => Alignment::VERTICAL_TOP, 'wrapText' => true],
                ]);
            }
        }

        $instructionSheet->getColumnDimension('A')->setWidth(24);
        $instructionSheet->getColumnDimension('B')->setWidth(40);
        $instructionSheet->getColumnDimension('C')->setWidth(65);
    }

    /**
     * Generate PhpSpreadsheet instance containing clean blank template + instructions.
     */
    public static function generateTemplateSpreadsheet(): Spreadsheet
    {
        $spreadsheet = new Spreadsheet();
        $spreadsheet->getProperties()
            ->setCreator('KP OWL LMS')
            ->setLastModifiedBy('KP OWL LMS')
            ->setTitle('Non KP OWL Offline Training Import Template')
            ->setSubject('Non KP OWL Training Records Template')
            ->setDescription('Official Excel template for uploading offline training records into KP OWL LMS.');

        // ----------------------------------------------------
        // SHEET 1: Non KP OWL Import (Active Data Sheet)
        // ----------------------------------------------------
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle(self::DATA_SHEET_TITLE);

        self::applyDataSheetHeaderStyles($sheet);

        foreach (self::columnWidths() as $colLetter => $width) {
            $sheet->getColumnDimension($colLetter)->setWidth($width);
        }

        // ----------------------------------------------------
        // SHEET 2: Instructions
        // ----------------------------------------------------
        $instructionSheet = $spreadsheet->createSheet(1);
        $instructionSheet->setTitle(self::INSTRUCTIONS_SHEET_TITLE);

        self::populateInstructionsSheet($instructionSheet);

        // Ensure Sheet 1 is active on open
        $spreadsheet->setActiveSheetIndex(0);

        return $spreadsheet;
    }
}
