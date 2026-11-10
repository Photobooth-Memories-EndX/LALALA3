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
     * Canonical 48-column specification for PTKP/Non-PTKP Import.
     */
    public static function canonicalColumns(): array
    {
        return PtkpColumnDefinitions::all();
    }

    /**
     * Complete column specification. Defaults to standardized 48 columns, or legacy 44 columns if requested.
     */
    public static function columns(?string $type = null): array
    {
        if ($type === 'legacy') {
            return self::legacyColumns();
        }
        return self::canonicalColumns();
    }

    /**
     * Complete legacy 44-column specification matching Section 4 of audit specification.
     */
    public static function legacyColumns(): array
    {
        return [
            // JOB INFORMATION (Cols 1-7 / A-G)
            1 => [
                'col_index'  => 1,
                'col_letter' => 'A',
                'header'     => 'Employee ID',
                'group'      => 'JOB INFORMATION',
                'key'        => 'employee_id',
                'required'   => true,
                'type'       => 'string',
                'example'    => '100234',
                'note'       => 'Employee ID / NIP (Required for participant matching)',
                'aliases'    => ['employee id', 'id', 'nip', 'nik', 'id emp', 'emp id', 'employee_id', 'no pegawai', 'employe id', 'employee no', 'emp no', 'employee number', 'nomor pegawai', 'id employee', 'no. pegawai', 'nip / id', 'id/nip', 'nip/id'],
            ],
            2 => [
                'col_index'  => 2,
                'col_letter' => 'B',
                'header'     => 'Employee Name',
                'group'      => 'JOB INFORMATION',
                'key'        => 'name',
                'required'   => true,
                'type'       => 'string',
                'example'    => 'Budi Santoso',
                'note'       => 'Participant full name',
                'aliases'    => ['employee name', 'name', 'nama', 'participant name', 'nama peserta', 'nama karyawan', 'peserta', 'full name', 'fullname', 'nama lengkap', 'employee_name', 'peserta training'],
            ],
            3 => [
                'col_index'  => 3,
                'col_letter' => 'C',
                'header'     => 'Grade',
                'group'      => 'JOB INFORMATION',
                'key'        => 'grade',
                'required'   => false,
                'type'       => 'string',
                'example'    => '4A',
                'note'       => 'Employee grade level',
                'aliases'    => ['grade', 'golongan', 'level grade', 'employee grade', 'pangkat'],
            ],
            4 => [
                'col_index'  => 4,
                'col_letter' => 'D',
                'header'     => 'Job Position',
                'group'      => 'JOB INFORMATION',
                'key'        => 'job_position',
                'required'   => false,
                'type'       => 'string',
                'example'    => 'Electrical Maintenance Engineer',
                'note'       => 'Official job title',
                'aliases'    => ['job position', 'position', 'jabatan', 'posisi', 'job_position', 'job title', 'job positon', 'jobposition'],
            ],
            5 => [
                'col_index'  => 5,
                'col_letter' => 'E',
                'header'     => 'Team',
                'group'      => 'JOB INFORMATION',
                'key'        => 'team',
                'required'   => false,
                'type'       => 'string',
                'example'    => 'Rolling Mill 1',
                'note'       => 'Work unit or team',
                'aliases'    => ['team', 'tim', 'unit', 'regu', 'seksi', 'work team'],
            ],
            6 => [
                'col_index'  => 6,
                'col_letter' => 'F',
                'header'     => 'Department',
                'group'      => 'JOB INFORMATION',
                'key'        => 'department',
                'required'   => false,
                'type'       => 'string',
                'example'    => 'Maintenance Department',
                'note'       => 'Department division',
                'aliases'    => ['department', 'dept', 'departemen', 'departement', 'divisi', 'department name'],
            ],
            7 => [
                'col_index'  => 7,
                'col_letter' => 'G',
                'header'     => 'Company',
                'group'      => 'JOB INFORMATION',
                'key'        => 'company',
                'required'   => false,
                'type'       => 'string',
                'example'    => 'PT Krakatau Posco',
                'note'       => 'Company legal entity',
                'aliases'    => ['company', 'perusahaan', 'pt', 'entity', 'company name', 'legal entity', 'company_name', 'nama perusahaan', 'nama pt'],
            ],

            // TRAINING INFORMATION (Cols 8-24 / H-X)
            8 => [
                'col_index'  => 8,
                'col_letter' => 'H',
                'header'     => 'Course Title',
                'group'      => 'TRAINING INFORMATION',
                'key'        => 'course_name',
                'required'   => true,
                'type'       => 'string',
                'example'    => 'High Voltage Safety & Operation',
                'note'       => 'Primary course / training title',
                'aliases'    => ['course title', 'course name', 'course', 'nama course', 'training name', 'nama training', 'judul training', 'training title', 'title', 'training', 'judul kursus', 'nama kursus', 'course_title', 'course_name'],
            ],
            9 => [
                'col_index'  => 9,
                'col_letter' => 'I',
                'header'     => 'Sub Course Title',
                'group'      => 'TRAINING INFORMATION',
                'key'        => 'sub_course_name',
                'required'   => false,
                'type'       => 'string',
                'example'    => 'Switchgear Troubleshooting Module',
                'note'       => 'Specific module or topic name',
                'aliases'    => ['sub course title', 'sub_course name', 'sub course name', 'sub_course_name', 'sub course', 'nama sub course', 'sub training', 'topic', 'module', 'modul'],
            ],
            10 => [
                'col_index'  => 10,
                'col_letter' => 'J',
                'header'     => 'Type',
                'group'      => 'TRAINING INFORMATION',
                'key'        => 'type',
                'required'   => false,
                'type'       => 'string',
                'example'    => 'In-House',
                'note'       => 'In-House, Public, Certification, Workshop, etc.',
                'aliases'    => ['type', 'tipe', 'training type', 'tipe training', 'jenis training', 'tipe pelatihan'],
            ],
            11 => [
                'col_index'  => 11,
                'col_letter' => 'K',
                'header'     => 'Category',
                'group'      => 'TRAINING INFORMATION',
                'key'        => 'category',
                'required'   => false,
                'type'       => 'string',
                'example'    => 'Technical Competency',
                'note'       => 'Training category classification',
                'aliases'    => ['category', 'kategori', 'training category', 'kategori training', 'kategori pelatihan'],
            ],
            12 => [
                'col_index'  => 12,
                'col_letter' => 'L',
                'header'     => 'Level',
                'group'      => 'TRAINING INFORMATION',
                'key'        => 'level',
                'required'   => false,
                'type'       => 'string',
                'example'    => 'Intermediate',
                'note'       => 'Basic, Intermediate, Advanced',
                'aliases'    => ['level', 'tingkat', 'level training', 'jenjang'],
            ],
            13 => [
                'col_index'  => 13,
                'col_letter' => 'M',
                'header'     => 'Batch',
                'group'      => 'TRAINING INFORMATION',
                'key'        => 'batch',
                'required'   => false,
                'type'       => 'string',
                'example'    => 'Batch 1',
                'note'       => 'Training batch or wave designation',
                'aliases'    => ['batch', 'angkatan', 'gelombang', 'wave'],
            ],
            14 => [
                'col_index'  => 14,
                'col_letter' => 'N',
                'header'     => 'Main Classification',
                'group'      => 'TRAINING INFORMATION',
                'key'        => 'main_classification',
                'required'   => false,
                'type'       => 'string',
                'example'    => 'Basic & Common Duties',
                'note'       => 'Main classification grouping',
                'aliases'    => ['main classification', 'klasifikasi utama', 'classification', 'klasifikasi', 'main category'],
            ],
            15 => [
                'col_index'  => 15,
                'col_letter' => 'O',
                'header'     => 'Sub Classification',
                'group'      => 'TRAINING INFORMATION',
                'key'        => 'sub_classification',
                'required'   => false,
                'type'       => 'string',
                'example'    => 'Plant Maintenance',
                'note'       => 'Sub category classification',
                'aliases'    => ['sub classification', 'sub klasifikasi', 'sub_classification'],
            ],
            16 => [
                'col_index'  => 16,
                'col_letter' => 'P',
                'header'     => 'Delivery Method',
                'group'      => 'TRAINING INFORMATION',
                'key'        => 'delivery_method',
                'required'   => false,
                'type'       => 'string',
                'example'    => 'Offline',
                'note'       => 'Offline / Online / Blended',
                'aliases'    => ['delivery method', 'metode delivery', 'metode pembelajaran', 'method', 'metode training'],
            ],
            17 => [
                'col_index'  => 17,
                'col_letter' => 'Q',
                'header'     => 'Sub Delivery Method',
                'group'      => 'TRAINING INFORMATION',
                'key'        => 'sub_delivery_method',
                'required'   => false,
                'type'       => 'string',
                'example'    => 'On-site',
                'note'       => 'Sub delivery technique',
                'aliases'    => ['sub delivery method', 'sub_delivery method', 'sub_delivery_method', 'sub metode delivery', 'sub method'],
            ],
            18 => [
                'col_index'  => 18,
                'col_letter' => 'R',
                'header'     => 'Provider Name',
                'group'      => 'TRAINING INFORMATION',
                'key'        => 'provider_name',
                'required'   => false,
                'type'       => 'string',
                'example'    => 'PT Schneider Indonesia',
                'note'       => 'Training provider / institution name',
                'aliases'    => ['provider name', 'nama provider', 'lembaga training', 'vendor name', 'nama vendor', 'institution', 'institution name', 'provider', 'training provider', 'vendor', 'nama lembaga', 'penyelenggara'],
            ],
            19 => [
                'col_index'  => 19,
                'col_letter' => 'S',
                'header'     => 'Venue',
                'group'      => 'TRAINING INFORMATION',
                'key'        => 'venue',
                'required'   => false,
                'type'       => 'string',
                'example'    => 'Auditorium Hall B',
                'note'       => 'Training venue / room / location',
                'aliases'    => ['venue', 'lokasi', 'tempat training', 'location', 'ruangan', 'tempat'],
            ],
            20 => [
                'col_index'  => 20,
                'col_letter' => 'T',
                'header'     => 'Instructor',
                'group'      => 'TRAINING INFORMATION',
                'key'        => 'instructor',
                'required'   => false,
                'type'       => 'string',
                'example'    => 'Ir. Gunawan, M.T.',
                'note'       => 'Trainer / instructor name',
                'aliases'    => ['instructor', 'trainer', 'instruktur', 'pengajar', 'narasumber', 'facilitator'],
            ],
            21 => [
                'col_index'  => 21,
                'col_letter' => 'U',
                'header'     => 'Course Duration',
                'group'      => 'TRAINING INFORMATION',
                'key'        => 'duration_seconds',
                'required'   => false,
                'type'       => 'duration',
                'example'    => '08:00:00',
                'note'       => 'Format: HH:MM:SS',
                'aliases'    => ['course duration', 'duration', 'durasi', 'training duration', 'durasi training', 'duration_seconds', 'duration (hh:mm:ss)'],
            ],
            22 => [
                'col_index'  => 22,
                'col_letter' => 'V',
                'header'     => 'Learning Hours',
                'group'      => 'TRAINING INFORMATION',
                'key'        => 'learning_hours',
                'required'   => false,
                'type'       => 'number',
                'example'    => 8.0,
                'note'       => 'Credited learning hours (e.g. 8.0)',
                'aliases'    => ['learning hours', 'learning (hours)', 'jam pelajaran', 'jp', 'hours', 'credited hours', 'jam training', 'learning hour'],
            ],
            23 => [
                'col_index'  => 23,
                'col_letter' => 'W',
                'header'     => '% Progress',
                'group'      => 'TRAINING INFORMATION',
                'key'        => 'progress',
                'required'   => false,
                'type'       => 'number',
                'example'    => 100,
                'note'       => '0 to 100 (%)',
                'aliases'    => ['% progress', 'progress %', 'progress', 'kemajuan', 'persentase progress', 'progres'],
            ],
            24 => [
                'col_index'  => 24,
                'col_letter' => 'X',
                'header'     => 'Progress Status',
                'group'      => 'TRAINING INFORMATION',
                'key'        => 'progress_status',
                'required'   => false,
                'type'       => 'string',
                'example'    => 'Finished',
                'note'       => 'Finished / Ongoing / Not Started (system derived)',
                'aliases'    => ['progress status', 'status progress', 'status kemajuan'],
            ],

            // SCHEDULE & TIME (Cols 25-28 / Y-AB)
            25 => [
                'col_index'  => 25,
                'col_letter' => 'Y',
                'header'     => 'Start Date',
                'group'      => 'SCHEDULE & TIME',
                'key'        => 'start_date',
                'required'   => false,
                'type'       => 'date',
                'example'    => '2026-08-10',
                'note'       => 'Format: YYYY-MM-DD',
                'aliases'    => ['start date', 'tanggal mulai', 'tgl mulai', 'start_date', 'begin date', 'start'],
            ],
            26 => [
                'col_index'  => 26,
                'col_letter' => 'Z',
                'header'     => 'End Date',
                'group'      => 'SCHEDULE & TIME',
                'key'        => 'end_date',
                'required'   => false,
                'type'       => 'date',
                'example'    => '2026-08-10',
                'note'       => 'Format: YYYY-MM-DD',
                'aliases'    => ['end date', 'finish date', 'tanggal selesai', 'tgl selesai', 'end_date', 'finish_date', 'completion date', 'finish', 'end'],
            ],
            27 => [
                'col_index'  => 27,
                'col_letter' => 'AA',
                'header'     => 'Start Time',
                'group'      => 'SCHEDULE & TIME',
                'key'        => 'start_time',
                'required'   => false,
                'type'       => 'string',
                'example'    => '08:00:00',
                'note'       => 'Format: HH:mm or HH:mm:ss',
                'aliases'    => ['start time', 'jam mulai', 'waktu mulai', 'start_time', 'time start'],
            ],
            28 => [
                'col_index'  => 28,
                'col_letter' => 'AB',
                'header'     => 'End Time',
                'group'      => 'SCHEDULE & TIME',
                'key'        => 'end_time',
                'required'   => false,
                'type'       => 'string',
                'example'    => '17:00:00',
                'note'       => 'Format: HH:mm or HH:mm:ss',
                'aliases'    => ['end time', 'jam selesai', 'waktu selesai', 'end_time', 'finish time', 'time end'],
            ],

            // ADMINISTRATION (Cols 29-30 / AC-AD)
            29 => [
                'col_index'  => 29,
                'col_letter' => 'AC',
                'header'     => 'Record Month',
                'group'      => 'ADMINISTRATION',
                'key'        => 'month',
                'required'   => false,
                'type'       => 'string',
                'example'    => 'August',
                'note'       => 'Derived month name (e.g. August)',
                'aliases'    => ['record month', 'month', 'bulan', 'periode bulan'],
            ],
            30 => [
                'col_index'  => 30,
                'col_letter' => 'AD',
                'header'     => 'Requirement',
                'group'      => 'ADMINISTRATION',
                'key'        => 'requirement',
                'required'   => false,
                'type'       => 'string',
                'example'    => 'Mandatory',
                'note'       => 'Mandatory / Elective',
                'aliases'    => ['requirement', 'status', 'kebutuhan', 'sifat training', 'training requirement'],
            ],

            // ATTENDANCE (Cols 31-32 / AE-AF)
            31 => [
                'col_index'  => 31,
                'col_letter' => 'AE',
                'header'     => 'Attendance',
                'group'      => 'ATTENDANCE',
                'key'        => 'attendance',
                'required'   => false,
                'type'       => 'boolean',
                'example'    => 'Yes',
                'note'       => 'Yes / No',
                'aliases'    => ['attendance', 'kehadiran', 'presensi', 'status kehadiran', 'hadir'],
            ],
            32 => [
                'col_index'  => 32,
                'col_letter' => 'AF',
                'header'     => 'Attendance Reason',
                'group'      => 'ATTENDANCE',
                'key'        => 'attendance_reason',
                'required'   => false,
                'type'       => 'string',
                'example'    => 'Present in all sessions',
                'note'       => 'Attendance justification if absent',
                'aliases'    => ['attendance reason', 'reason', 'alasan', 'alasan ketidakhadiran', 'justification'],
            ],

            // OBLIGATION (Cols 33-36 / AG-AJ)
            33 => [
                'col_index'  => 33,
                'col_letter' => 'AG',
                'header'     => 'Bond Status',
                'group'      => 'OBLIGATION',
                'key'        => 'bond_status',
                'required'   => false,
                'type'       => 'string',
                'example'    => 'No',
                'note'       => 'Yes / No',
                'aliases'    => ['bond status', 'status ikatan dinas', 'ikatan dinas', 'service bond', 'bond'],
            ],
            34 => [
                'col_index'  => 34,
                'col_letter' => 'AH',
                'header'     => 'Bond Duration',
                'group'      => 'OBLIGATION',
                'key'        => 'bond_duration',
                'required'   => false,
                'type'       => 'number',
                'example'    => 12,
                'note'       => 'Bond duration in months (e.g. 12)',
                'aliases'    => ['bond duration', 'durasi ikatan dinas', 'lama ikatan dinas', 'bond duration (months)'],
            ],
            35 => [
                'col_index'  => 35,
                'col_letter' => 'AI',
                'header'     => 'Bond End Date',
                'group'      => 'OBLIGATION',
                'key'        => 'bond_end_date',
                'required'   => false,
                'type'       => 'date',
                'example'    => '',
                'note'       => 'Format: YYYY-MM-DD',
                'aliases'    => ['bond end date', 'tanggal selesai ikatan', 'tgl berakhir ikatan dinas', 'bond completion date'],
            ],
            36 => [
                'col_index'  => 36,
                'col_letter' => 'AJ',
                'header'     => 'Bond Amount',
                'group'      => 'OBLIGATION',
                'key'        => 'bond_amount',
                'required'   => false,
                'type'       => 'number',
                'example'    => '',
                'note'       => 'Service bond value (IDR)',
                'aliases'    => ['bond amount', 'nilai ikatan dinas', 'biaya ikatan dinas', 'nominal ikatan dinas', 'bond cost', 'bond amount (idr)', 'bond amount idr', 'bond_amount_idr'],
            ],

            // L1 (Col 37 / AK)
            37 => [
                'col_index'  => 37,
                'col_letter' => 'AK',
                'header'     => 'Score (L1) - Course',
                'group'      => 'L1',
                'key'        => 'l1_program',
                'required'   => false,
                'type'       => 'string',
                'example'    => 'Very Good',
                'note'       => 'Reaction evaluation / rating',
                'aliases'    => ['score (l1) - course', 'score (l1)', 'score l1', 'program', 'l1 program', 'l1', 'evaluasi l1', 'level 1', 'reaction evaluation'],
            ],

            // L2 (POST TEST) (Cols 38-39 / AL-AM)
            38 => [
                'col_index'  => 38,
                'col_letter' => 'AL',
                'header'     => 'Score (L2)',
                'group'      => 'L2 (POST TEST)',
                'key'        => 'assessment_score',
                'required'   => false,
                'type'       => 'number',
                'example'    => 85,
                'note'       => 'Post-test score (0-100)',
                'aliases'    => [
                    'score (l2)',
                    'score l2',
                    'assessment score (l2)',
                    'numerical',
                    'l2 numerical',
                    'l2 post test numerical',
                    'nilai post test',
                    'post test score',
                    'post test numerical',
                    'l2 numeric',
                    'assessment score',
                    'post test score',
                    'nilai assessment',
                ],
            ],
            39 => [
                'col_index'  => 39,
                'col_letter' => 'AM',
                'header'     => 'L2 Status',
                'group'      => 'L2 (POST TEST)',
                'key'        => 'l2_verbal',
                'required'   => false,
                'type'       => 'string',
                'example'    => 'Passed',
                'note'       => 'Post-test qualitative status (Passed / Failed)',
                'aliases'    => ['l2 status', 'status l2', 'verbal', 'l2 verbal', 'l2 post test verbal', 'post test verbal', 'hasil post test', 'l2 result'],
            ],

            // L3 (PROJECT) (Cols 40-41 / AN-AO)
            40 => [
                'col_index'  => 40,
                'col_letter' => 'AN',
                'header'     => 'Score (L3)',
                'group'      => 'L3 (PROJECT)',
                'key'        => 'l3_numerical',
                'required'   => false,
                'type'       => 'number',
                'example'    => 90.0,
                'note'       => 'Project implementation score (0-100)',
                'aliases'    => ['score (l3)', 'score l3', 'numerical', 'l3 numerical', 'l3 project numerical', 'nilai project', 'project score', 'project numerical', 'l3 numeric'],
            ],
            41 => [
                'col_index'  => 41,
                'col_letter' => 'AO',
                'header'     => 'L3 Status',
                'group'      => 'L3 (PROJECT)',
                'key'        => 'l3_verbal',
                'required'   => false,
                'type'       => 'string',
                'example'    => 'Implemented successfully',
                'note'       => 'Behavior / project result evaluation',
                'aliases'    => ['l3 status', 'status l3', 'verbal', 'l3 verbal', 'l3 project verbal', 'project verbal', 'hasil project', 'l3 result'],
            ],

            // CLOSING & VERIFICATION (Cols 42-44 / AP-AR)
            42 => [
                'col_index'  => 42,
                'col_letter' => 'AP',
                'header'     => 'Person in Charge (PIC)',
                'group'      => 'CLOSING & VERIFICATION',
                'key'        => 'pic',
                'required'   => false,
                'type'       => 'string',
                'example'    => 'HRDC Team',
                'note'       => 'HR / Training administrator PIC',
                'aliases'    => ['person in charge (pic)', 'person in charge', 'pic', 'admin pic', 'pic hrdc', 'penanggung jawab'],
            ],
            43 => [
                'col_index'  => 43,
                'col_letter' => 'AQ',
                'header'     => 'Certificate',
                'group'      => 'CLOSING & VERIFICATION',
                'key'        => 'certificate',
                'required'   => false,
                'type'       => 'string',
                'example'    => 'Yes',
                'note'       => 'Yes / No',
                'aliases'    => ['certificate', 'sertifikat', 'ada sertifikat', 'certificate yes/no', 'status sertifikat'],
            ],
            44 => [
                'col_index'  => 44,
                'col_letter' => 'AR',
                'header'     => 'Total Engagement Time',
                'group'      => 'CLOSING & VERIFICATION',
                'key'        => 'total_engagement_time',
                'required'   => false,
                'type'       => 'number',
                'example'    => 8.0,
                'note'       => 'Total recognized engagement time (hours)',
                'aliases'    => ['total engagement time', 'total engagement time (lh)', 'engagement time', 'total hours', 'total durasi'],
            ],
        ];
    }

    /**
     * Exact flat list of 48 canonical header strings.
     */
    public static function canonicalHeaders(): array
    {
        $headers = [];
        foreach (self::canonicalColumns() as $col) {
            $headers[] = $col['header'];
        }
        return $headers;
    }

    /**
     * Exact flat list of 44 legacy header strings.
     */
    public static function legacyHeaders(): array
    {
        $headers = [];
        foreach (self::legacyColumns() as $col) {
            $headers[] = $col['header'];
        }
        return $headers;
    }

    /**
     * Header list matching specified mode ('canonical' default 48, or 'legacy' 44).
     */
    public static function headers(?string $type = null): array
    {
        if ($type === 'legacy') {
            return self::legacyHeaders();
        }
        return self::canonicalHeaders();
    }

    /**
     * Total number of supported template columns.
     */
    public static function totalColumns(?string $type = null): int
    {
        return count(self::columns($type));
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
     * Grouped Row 1 headers array (43 elements), preserved for legacy format compatibility.
     */
    /**
     * Parent grouping specification for standardized 46 columns (or legacy 44).
     */
    public static function groups(?string $type = null): array
    {
        if ($type === 'legacy') {
            return self::legacyGroups();
        }
        return PtkpExcelSchema::groups();
    }

    /**
     * Parent grouping specification for legacy 44 columns (A to AR).
     */
    public static function legacyGroups(): array
    {
        return [
            'job_information' => [
                'title'     => 'JOB INFORMATION',
                'start_col' => 'A',
                'end_col'   => 'G',
                'fill'      => '002B49',
                'color'     => 'FFFFFF',
                'columns'   => [1, 2, 3, 4, 5, 6, 7],
            ],
            'training_information' => [
                'title'     => 'TRAINING INFORMATION',
                'start_col' => 'H',
                'end_col'   => 'M',
                'fill'      => '0C5B9E',
                'color'     => 'FFFFFF',
                'columns'   => [8, 9, 10, 11, 12, 13],
            ],
            'classification' => [
                'title'     => 'CLASSIFICATION',
                'start_col' => 'N',
                'end_col'   => 'O',
                'fill'      => '1B4D3E',
                'color'     => 'FFFFFF',
                'columns'   => [14, 15],
            ],
            'delivery' => [
                'title'     => 'DELIVERY',
                'start_col' => 'P',
                'end_col'   => 'Q',
                'fill'      => '3B82F6',
                'color'     => 'FFFFFF',
                'columns'   => [16, 17],
            ],
            'provider_instructor' => [
                'title'     => 'PROVIDER & INSTRUCTOR',
                'start_col' => 'R',
                'end_col'   => 'T',
                'fill'      => '475569',
                'color'     => 'FFFFFF',
                'columns'   => [18, 19, 20],
            ],
            'duration_hours' => [
                'title'     => 'DURATION & LEARNING HOURS',
                'start_col' => 'U',
                'end_col'   => 'V',
                'fill'      => '0284C7',
                'color'     => 'FFFFFF',
                'columns'   => [21, 22],
            ],
            'progress' => [
                'title'     => 'PROGRESS',
                'start_col' => 'W',
                'end_col'   => 'X',
                'fill'      => '16A34A',
                'color'     => 'FFFFFF',
                'columns'   => [23, 24],
            ],
            'schedule_requirement' => [
                'title'     => 'SCHEDULE & REQUIREMENT',
                'start_col' => 'Y',
                'end_col'   => 'AD',
                'fill'      => 'D97706',
                'color'     => 'FFFFFF',
                'columns'   => [25, 26, 27, 28, 29, 30],
            ],
            'attendance_bond' => [
                'title'     => 'ATTENDANCE & BOND',
                'start_col' => 'AE',
                'end_col'   => 'AJ',
                'fill'      => '7C3AED',
                'color'     => 'FFFFFF',
                'columns'   => [31, 32, 33, 34, 35, 36],
            ],
            'evaluation_completion' => [
                'title'     => 'EVALUATION & COMPLETION',
                'start_col' => 'AK',
                'end_col'   => 'AR',
                'fill'      => 'BE185D',
                'color'     => 'FFFFFF',
                'columns'   => [37, 38, 39, 40, 41, 42, 43, 44],
            ],
        ];
    }

    public static function groupedRow1Headers(): array
    {
        $groups = self::groups();
        if (empty($groups)) {
            $groups = self::legacyGroups();
        }
        $groupStartMap = [];
        foreach ($groups as $group) {
            $groupStartMap[$group['start_col']] = $group['title'];
        }

        $totalCols = self::totalColumns();
        $row1 = [];
        for ($col = 1; $col <= $totalCols; $col++) {
            $colLetter = Coordinate::stringFromColumnIndex($col);
            $row1[] = $groupStartMap[$colLetter] ?? '';
        }

        return $row1;
    }

    /**
     * Column widths matching specified mode (canonical 48 or legacy 44).
     */
    public static function columnWidths(?string $type = null): array
    {
        if ($type === 'legacy') {
            return self::legacyColumnWidths();
        }
        return PtkpExcelSchema::columnWidths();
    }

    /**
     * Canonical column widths matching legacy 44 template specifications.
     */
    public static function legacyColumnWidths(): array
    {
        return [
            'A'  => 16,  // Employee ID
            'B'  => 30,  // Employee Name
            'C'  => 12,  // Grade
            'D'  => 25,  // Job Position
            'E'  => 20,  // Team
            'F'  => 25,  // Department
            'G'  => 25,  // Company
            'H'  => 35,  // Course Title
            'I'  => 25,  // Sub Course Title
            'J'  => 15,  // Type
            'K'  => 18,  // Category
            'L'  => 12,  // Level
            'M'  => 12,  // Batch
            'N'  => 25,  // Main Classification
            'O'  => 25,  // Sub Classification
            'P'  => 18,  // Delivery Method
            'Q'  => 20,  // Sub Delivery Method
            'R'  => 25,  // Provider Name
            'S'  => 20,  // Venue
            'T'  => 22,  // Instructor
            'U'  => 16,  // Course Duration
            'V'  => 16,  // Learning Hours
            'W'  => 14,  // % Progress
            'X'  => 16,  // Progress Status
            'Y'  => 14,  // Start Date
            'Z'  => 14,  // End Date
            'AA' => 14,  // Start Time
            'AB' => 14,  // End Time
            'AC' => 16,  // Record Month
            'AD' => 15,  // Requirement
            'AE' => 14,  // Attendance
            'AF' => 25,  // Attendance Reason
            'AG' => 14,  // Bond Status
            'AH' => 16,  // Bond Duration
            'AI' => 15,  // Bond End Date
            'AJ' => 18,  // Bond Amount
            'AK' => 20,  // Score (L1) - Course
            'AL' => 14,  // Score (L2)
            'AM' => 14,  // L2 Status
            'AN' => 14,  // Score (L3)
            'AO' => 14,  // L3 Status
            'AP' => 22,  // Person in Charge (PIC)
            'AQ' => 14,  // Certificate
            'AR' => 25,  // Total Engagement Time
        ];
    }

    public static function columnFormats(?string $type = null): array
    {
        if ($type === 'legacy') {
            return self::legacyColumnFormats();
        }
        return [
            'B'  => \PhpOffice\PhpSpreadsheet\Style\NumberFormat::FORMAT_TEXT,
            'X'  => '0.##" h"',
            'Y'  => '0%',
            'Z'  => 'yyyy-mm-dd',
            'AA' => 'yyyy-mm-dd',
            'AB' => 'hh:mm:ss',
            'AC' => 'hh:mm:ss',
            'AI' => '0',
            'AJ' => 'yyyy-mm-dd',
            'AK' => '"Rp"#,##0',
            'AM' => '0',
            'AO' => '0.##',
            'AS' => 'yyyy-mm-dd',
            'AT' => 'yyyy-mm-dd',
        ];
    }

    public static function legacyColumnFormats(): array
    {
        return [
            'A'  => \PhpOffice\PhpSpreadsheet\Style\NumberFormat::FORMAT_TEXT,
            'V'  => '0.##" h"',
            'W'  => '0%',
            'Y'  => 'yyyy-mm-dd',
            'Z'  => 'yyyy-mm-dd',
            'AA' => 'hh:mm:ss',
            'AB' => 'hh:mm:ss',
            'AH' => '0',
            'AI' => 'yyyy-mm-dd',
            'AJ' => '"Rp"#,##0',
            'AK' => '0.##',
            'AL' => '0',
            'AN' => '0.##',
            'AR' => '0.##" h"',
        ];
    }

    /**
     * Standardize Delivery Method into: "Offline", "Online", or "Blended".
     */
    public static function normalizeDeliveryMethod(?string $method): string
    {
        $raw = strtolower(trim((string) $method));
        if (in_array($raw, ['in class', 'classroom', 'on-site', 'off-site', 'offline'], true)) {
            return 'Offline';
        }
        if (in_array($raw, ['e-learning', 'webinar', 'online', 'kp-owl', 'kp owl'], true)) {
            return 'Online';
        }
        if (in_array($raw, ['hybrid', 'blended'], true)) {
            return 'Blended';
        }
        return !empty($method) && $method !== '-' ? ucfirst(trim((string) $method)) : '-';
    }

    /**
     * Normalize header string for consistent, resilient comparison.
     */
    public static function normalizeHeaderString(?string $header): string
    {
        if ($header === null) {
            return '';
        }

        $h = trim($header);
        $h = mb_strtolower($h, 'UTF-8');
        // Treat underscores, hyphens, slashes as space
        $h = str_replace(['_', '-', '/', '\\'], ' ', $h);
        // Remove non-alphanumeric except spaces and apostrophe (e.g. '26)
        $h = preg_replace('/[^\p{L}\p{N}\s\']/u', ' ', $h);
        // Collapse multiple whitespace
        $h = preg_replace('/\s+/', ' ', $h);

        return trim($h);
    }

    public static function supplementalFields(): array
    {
        return [
            'provider' => [
                'header'   => 'Provider',
                'key'      => 'provider',
                'required' => false,
                'aliases'  => ['provider', 'penyedia', 'tipe provider', 'provider type', 'jenis provider', 'training provider type'],
            ],
            'sub_provider' => [
                'header'   => 'Sub Provider',
                'key'      => 'sub_provider',
                'required' => false,
                'aliases'  => ['sub provider', 'sub_provider', 'sub penyedia', 'kategori provider', 'sub vendor'],
            ],
        ];
    }

    public static function matchSupplementalField(string $normHeader): ?array
    {
        foreach (self::supplementalFields() as $field) {
            $normCanon = self::normalizeHeaderString($field['header']);
            if ($normHeader === $normCanon) {
                return $field;
            }
            foreach ($field['aliases'] as $alias) {
                if ($normHeader === self::normalizeHeaderString($alias)) {
                    return $field;
                }
            }
        }
        return null;
    }

    /**
     * Automatically detect workbook format: New Single-Row vs Legacy Two-Row Grouped.
     */
    public static function detectTemplateFormat(Worksheet $worksheet): array
    {
        $highestColumn = $worksheet->getHighestDataColumn();
        $highestColumnIndex = Coordinate::columnIndexFromString($highestColumn);

        $row1Cells = [];
        $row2Cells = [];

        for ($col = 1; $col <= max($highestColumnIndex, count(self::columns())); $col++) {
            $colLetter = Coordinate::stringFromColumnIndex($col);
            $r1Val = $worksheet->getCell($colLetter . '1')->getValue();
            $r2Val = $worksheet->getCell($colLetter . '2')->getValue();
            $row1Cells[$col] = $r1Val !== null ? trim((string) $r1Val) : '';
            $row2Cells[$col] = $r2Val !== null ? trim((string) $r2Val) : '';
        }

        // Check if Row 1 contains grouping indicators (PTKP 48-col or legacy 44-col)
        $row1Joined = mb_strtolower(implode(' ', $row1Cells), 'UTF-8');
        $r1Col1 = mb_strtolower($row1Cells[1] ?? '', 'UTF-8');
        $r1Col3 = mb_strtolower($row1Cells[3] ?? '', 'UTF-8');
        $r2Col1 = mb_strtolower($row2Cells[1] ?? '', 'UTF-8');
        $r2Col2 = mb_strtolower($row2Cells[2] ?? '', 'UTF-8');
        $r2Col3 = mb_strtolower($row2Cells[3] ?? '', 'UTF-8');

        $row1HasCourseHeader = str_contains($row1Joined, 'course title') ||
                               str_contains($row1Joined, 'course name') ||
                               str_contains($row1Joined, 'nama course') ||
                               str_contains($row1Joined, 'judul');

        $row2Joined = mb_strtolower(implode(' ', $row2Cells), 'UTF-8');
        $row2HasCourseHeader = str_contains($row2Joined, 'course title') ||
                               str_contains($row2Joined, 'course name') ||
                               str_contains($row2Joined, 'nama course') ||
                               str_contains($row2Joined, 'judul');

        $isTwoRow = !$row1HasCourseHeader && (
            $r1Col1 === 'status' ||
            $r1Col1 === 'general' ||
            str_contains($r1Col1, 'participant profile') ||
            str_contains($r1Col3, 'participant profile') ||
            str_contains($r1Col3, 'job information') ||
            str_contains($row1Joined, 'participant profile') ||
            str_contains($row1Joined, 'job information') ||
            str_contains($row1Joined, 'training information') ||
            str_contains($row1Joined, 'evaluation') ||
            (str_contains($row1Joined, 'l2') && str_contains($row1Joined, 'post test')) ||
            (str_contains($row1Joined, 'l3') && str_contains($row1Joined, 'project'))
        ) && (
            $row2HasCourseHeader ||
            str_contains($r2Col1, 'status') ||
            str_contains($r2Col1, 'employee') ||
            in_array($r2Col1, ['id', 'nip'], true) ||
            in_array($r2Col2, ['no', 'no.', 'nomor'], true) ||
            str_contains($r2Col3, 'employee') ||
            in_array($r2Col3, ['id', 'nip'], true)
        );

        if ($isTwoRow) {
            // Trim trailing empty cells in Row 2
            $foundHeaders = [];
            $lastNonEmpty = 0;
            for ($c = 1; $c <= count($row2Cells); $c++) {
                if ($row2Cells[$c] !== '') {
                    $lastNonEmpty = $c;
                }
            }
            $targetColCount = $lastNonEmpty;
            for ($c = 1; $c <= $targetColCount; $c++) {
                $foundHeaders[$c] = $row2Cells[$c] ?? '';
            }

            return [
                'format'          => 'legacy_two_row',
                'header_row'      => 2,
                'data_start_row'  => 3,
                'group_row'       => 1,
                'detected_count'  => $targetColCount,
                'found_headers'   => $foundHeaders,
            ];
        }

        // Default: Single Header Row on Row 1
        $foundHeaders = [];
        $lastNonEmpty = 0;
        for ($c = 1; $c <= count($row1Cells); $c++) {
            if ($row1Cells[$c] !== '') {
                $lastNonEmpty = $c;
            }
        }
        $targetColCount = $lastNonEmpty;
        for ($c = 1; $c <= $targetColCount; $c++) {
            $foundHeaders[$c] = $row1Cells[$c] ?? '';
        }

        return [
            'format'          => 'single_row',
            'header_row'      => 1,
            'data_start_row'  => 2,
            'group_row'       => null,
            'detected_count'  => $targetColCount,
            'found_headers'   => $foundHeaders,
        ];
    }

    /**
     * Smart per-column header matching & validation engine.
     * Evaluates every column positionally with controlled typo-tolerance, alias matching,
     * duplicate mapping guards, and explicit L2/L3 disambiguation.
     */
    public static function analyzeHeaders(array $foundHeaders, string $format = 'single_row'): array
    {
        $firstHeaderNorm = isset($foundHeaders[1]) ? self::normalizeHeaderString($foundHeaders[1]) : '';
        $secondHeaderNorm = isset($foundHeaders[2]) ? self::normalizeHeaderString($foundHeaders[2]) : '';
        $thirdHeaderNorm = isset($foundHeaders[3]) ? self::normalizeHeaderString($foundHeaders[3]) : '';
        $detectedCount = count($foundHeaders);

        $statusAliases = ['status', 'status validasi', 'system status', 'validation status', 'row status', 'stat'];
        $noAliases = ['no', 'no.', 'nomor', '#', 'row no', 'seq'];

        // Determine if file is legacy 44 columns (Non-PTKP original format without separate Provider/Sub Provider)
        $isLegacy44 = false;
        if ($detectedCount <= 45 && !in_array($firstHeaderNorm, $statusAliases, true)) {
            $h18 = isset($foundHeaders[18]) ? self::normalizeHeaderString($foundHeaders[18]) : '';
            $h19 = isset($foundHeaders[19]) ? self::normalizeHeaderString($foundHeaders[19]) : '';
            if (str_contains($h18, 'provider name') || str_contains($h18, 'nama provider') || $h19 === 'venue' || $h19 === 'lokasi') {
                $isLegacy44 = true;
            } elseif ($detectedCount === 44 && !str_contains($h19, 'sub provider') && !str_contains($h18, 'provider type')) {
                $isLegacy44 = true;
            }
        }

        $columns = $isLegacy44 ? self::legacyColumns() : self::canonicalColumns();
        $canonicalCount = count($columns);

        $hasLeadingNo = false;
        $hasLeadingStatus = false;
        $offset = 0;

        if ($isLegacy44) {
            $empIdAliases = array_map([self::class, 'normalizeHeaderString'], array_merge(['Employee ID'], self::legacyColumns()[1]['aliases'] ?? []));
            if (in_array($firstHeaderNorm, $noAliases, true) && in_array($secondHeaderNorm, $empIdAliases, true)) {
                $hasLeadingNo = true;
            }
        } else {
            if (in_array($firstHeaderNorm, $statusAliases, true)) {
                $hasLeadingStatus = true;
                if (in_array($secondHeaderNorm, $noAliases, true)) {
                    $hasLeadingNo = true;
                    $offset = 2; // Leading Status (1) and No (2)
                } else {
                    $offset = 1; // Leading Status (1)
                }
            } elseif (in_array($firstHeaderNorm, $noAliases, true)) {
                $hasLeadingNo = true;
                $offset = 1; // Leading No (1)
            }
        }

        $expectedCount = $canonicalCount + $offset;

        // Precompute normalized canonical headers and aliases for fast lookup
        $canonicalMap = [];
        $aliasToCols = [];
        foreach ($columns as $idx => $col) {
            $normHeader = self::normalizeHeaderString($col['header']);
            $canonicalMap[$idx] = [
                'col'          => $idx,
                'letter'       => Coordinate::stringFromColumnIndex($idx),
                'header'       => $col['header'],
                'norm_header'  => $normHeader,
                'key'          => $col['key'],
                'required'     => $col['required'],
                'aliases'      => array_map([self::class, 'normalizeHeaderString'], $col['aliases'] ?? []),
            ];

            foreach ($canonicalMap[$idx]['aliases'] as $a) {
                if ($a !== '') {
                    $aliasToCols[$a][] = $idx;
                }
            }
        }

        // Ambiguous standalone keywords that must NEVER be guessed without context
        $ambiguousKeywords = [
            'score', 'nilai', 'evaluasi', 'test', 'result', 'hasil', 'data'
        ];

        $columnResults = [];
        $mappedKeys = []; // key => list of col indices
        $issues = [];
        $autoMatchedList = [];

        $validCount = 0;
        $autoMatchedCount = 0;
        $warningCount = 0;
        $invalidCount = 0;
        $missingCount = 0;
        $duplicateCount = 0;
        $ambiguousCount = 0;

        $maxCol = max($expectedCount, $detectedCount);

        for ($pos = 1; $pos <= $maxCol; $pos++) {
            $colLetter = Coordinate::stringFromColumnIndex($pos);
            $rawFound = isset($foundHeaders[$pos]) ? trim((string) $foundHeaders[$pos]) : '';
            $normFound = self::normalizeHeaderString($rawFound);

            // Handle leading auxiliary columns (Status / No) for canonical format
            if ($offset > 0 && $pos <= $offset) {
                $isStatusCol = ($pos === 1 && $hasLeadingStatus);
                $isNoCol = ($pos === 1 && $hasLeadingNo && !$hasLeadingStatus) || ($pos === 2 && $hasLeadingNo);

                if ($isStatusCol) {
                    $validCount++;
                    $columnResults[$pos] = [
                        'col'         => $pos,
                        'letter'      => $colLetter,
                        'expected'    => 'Status',
                        'found'       => $rawFound,
                        'status'      => 'VALID',
                        'mapped_to'   => 'Status',
                        'key'         => 'status',
                        'message'     => 'Legacy validation status column (optional on upload).',
                    ];
                    continue;
                }

                if ($isNoCol) {
                    $validCount++;
                    $columnResults[$pos] = [
                        'col'         => $pos,
                        'letter'      => $colLetter,
                        'expected'    => 'No',
                        'found'       => $rawFound,
                        'status'      => 'VALID',
                        'mapped_to'   => 'No',
                        'key'         => 'row_no',
                        'message'     => 'Legacy sequence number column (preview only).',
                    ];
                    continue;
                }
            }

            $canonicalIdx = $isLegacy44 ? ($hasLeadingNo ? $pos - 1 : $pos) : ($pos - $offset);
            $canonical = $canonicalMap[$canonicalIdx] ?? null;

            // Extra unexpected column beyond expected count
            if ($pos > $expectedCount) {
                if ($rawFound !== '') {
                    $suppField = self::matchSupplementalField($normFound);
                    if ($suppField && empty($mappedKeys[$suppField['key']])) {
                        $autoMatchedCount++;
                        $res = [
                            'col'         => $pos,
                            'letter'      => $colLetter,
                            'expected'    => $suppField['header'],
                            'found'       => $rawFound,
                            'status'      => 'AUTO_MATCHED',
                            'mapped_to'   => $suppField['header'],
                            'key'         => $suppField['key'],
                            'message'     => "Matched recognized supplemental column '{$suppField['header']}'.",
                        ];
                        $columnResults[$pos] = $res;
                        $autoMatchedList[] = $res;
                        $mappedKeys[$suppField['key']][] = $pos;
                        continue;
                    }

                    $invalidCount++;
                    $issue = [
                        'col'         => $pos,
                        'letter'      => $colLetter,
                        'expected'    => null,
                        'found'       => $rawFound,
                        'status'      => 'INVALID',
                        'mapped_to'   => null,
                        'key'         => null,
                        'message'     => "Unexpected extra column {$pos} ({$colLetter}: '{$rawFound}'). Canonical schema requires exactly {$expectedCount} columns.",
                    ];
                    $columnResults[$pos] = $issue;
                    $issues[] = $issue;
                }
                continue;
            }

            // Missing or empty column header at canonical position
            if ($rawFound === '') {
                $missingCount++;
                $issue = [
                    'col'         => $pos,
                    'letter'      => $colLetter,
                    'expected'    => $canonical['header'],
                    'found'       => '',
                    'status'      => 'MISSING',
                    'mapped_to'   => null,
                    'key'         => $canonical['key'],
                    'message'     => "Missing column {$pos} ({$colLetter}: '{$canonical['header']}'). Canonical template requires all {$expectedCount} headers.",
                ];
                $columnResults[$pos] = $issue;
                $issues[] = $issue;
                continue;
            }

            // 1. EXACT CANONICAL MATCH
            if ($rawFound === $canonical['header']) {
                $validCount++;
                $res = [
                    'col'         => $pos,
                    'letter'      => $colLetter,
                    'expected'    => $canonical['header'],
                    'found'       => $rawFound,
                    'status'      => 'VALID',
                    'mapped_to'   => $canonical['header'],
                    'key'         => $canonical['key'],
                    'message'     => 'Exact canonical match.',
                ];
                $columnResults[$pos] = $res;
                if ($canonical['key']) {
                    $mappedKeys[$canonical['key']][] = $pos;
                }
                continue;
            }

            // 2. NORMALIZED EXACT MATCH
            if ($normFound === $canonical['norm_header']) {
                $autoMatchedCount++;
                $res = [
                    'col'         => $pos,
                    'letter'      => $colLetter,
                    'expected'    => $canonical['header'],
                    'found'       => $rawFound,
                    'status'      => 'AUTO_MATCHED',
                    'mapped_to'   => $canonical['header'],
                    'key'         => $canonical['key'],
                    'message'     => 'Matched via normalization (capitalization/spacing/punctuation).',
                ];
                $columnResults[$pos] = $res;
                $autoMatchedList[] = $res;
                if ($canonical['key']) {
                    $mappedKeys[$canonical['key']][] = $pos;
                }
                continue;
            }

            // 3. DISAMBIGUATION FOR L2 VS L3 DUPLICATE DISPLAY LABELS
            if ($canonical['key'] === 'l2_numerical') {
                // Expected: L2 Numerical
                if ($normFound === 'numerical' || in_array($normFound, ['l2 numerical', 'l2 post test numerical', 'post test numerical', 'l2 numeric'])) {
                    $autoMatchedCount++;
                    $res = [
                        'col'         => $pos,
                        'letter'      => $colLetter,
                        'expected'    => 'Numerical (L2)',
                        'found'       => $rawFound,
                        'status'      => 'AUTO_MATCHED',
                        'mapped_to'   => 'Numerical (L2)',
                        'key'         => 'l2_numerical',
                        'message'     => 'Matched to L2 Numerical post-test score.',
                    ];
                    $columnResults[$pos] = $res;
                    $autoMatchedList[] = $res;
                    $mappedKeys['l2_numerical'][] = $pos;
                    continue;
                }
                if (str_contains($normFound, 'l3') && (str_contains($normFound, 'numeric') || str_contains($normFound, 'numerical'))) {
                    $invalidCount++;
                    $issue = [
                        'col'         => $pos,
                        'letter'      => $colLetter,
                        'expected'    => 'Numerical (L2 Post Test)',
                        'found'       => $rawFound,
                        'status'      => 'INVALID',
                        'mapped_to'   => null,
                        'key'         => null,
                        'message'     => "Found '{$rawFound}' at column {$pos} which belongs to L3 Project, but column {$pos} is reserved for L2 Post Test.",
                    ];
                    $columnResults[$pos] = $issue;
                    $issues[] = $issue;
                    continue;
                }
            }

            if ($canonical['key'] === 'l2_verbal') {
                // Expected: L2 Verbal
                if ($normFound === 'verbal' || in_array($normFound, ['l2 verbal', 'l2 post test verbal', 'post test verbal', 'l2 result'])) {
                    $autoMatchedCount++;
                    $res = [
                        'col'         => $pos,
                        'letter'      => $colLetter,
                        'expected'    => 'Verbal (L2)',
                        'found'       => $rawFound,
                        'status'      => 'AUTO_MATCHED',
                        'mapped_to'   => 'Verbal (L2)',
                        'key'         => 'l2_verbal',
                        'message'     => 'Matched to L2 Verbal post-test qualitative result.',
                    ];
                    $columnResults[$pos] = $res;
                    $autoMatchedList[] = $res;
                    $mappedKeys['l2_verbal'][] = $pos;
                    continue;
                }
                if (str_contains($normFound, 'l3') && str_contains($normFound, 'verbal')) {
                    $invalidCount++;
                    $issue = [
                        'col'         => $pos,
                        'letter'      => $colLetter,
                        'expected'    => 'Verbal (L2 Post Test)',
                        'found'       => $rawFound,
                        'status'      => 'INVALID',
                        'mapped_to'   => null,
                        'key'         => null,
                        'message'     => "Found '{$rawFound}' at column {$pos} which belongs to L3 Project, but column {$pos} is reserved for L2 Post Test.",
                    ];
                    $columnResults[$pos] = $issue;
                    $issues[] = $issue;
                    continue;
                }
            }

            if ($canonical['key'] === 'l3_numerical') {
                // Expected: L3 Numerical
                if ($normFound === 'numerical' || in_array($normFound, ['l3 numerical', 'l3 project numerical', 'project numerical', 'l3 numeric'])) {
                    $autoMatchedCount++;
                    $res = [
                        'col'         => $pos,
                        'letter'      => $colLetter,
                        'expected'    => 'Numerical (L3)',
                        'found'       => $rawFound,
                        'status'      => 'AUTO_MATCHED',
                        'mapped_to'   => 'Numerical (L3)',
                        'key'         => 'l3_numerical',
                        'message'     => 'Matched to L3 Numerical project score.',
                    ];
                    $columnResults[$pos] = $res;
                    $autoMatchedList[] = $res;
                    $mappedKeys['l3_numerical'][] = $pos;
                    continue;
                }
                if (str_contains($normFound, 'l2') && (str_contains($normFound, 'numeric') || str_contains($normFound, 'numerical'))) {
                    $invalidCount++;
                    $issue = [
                        'col'         => $pos,
                        'letter'      => $colLetter,
                        'expected'    => 'Numerical (L3 Project)',
                        'found'       => $rawFound,
                        'status'      => 'INVALID',
                        'mapped_to'   => null,
                        'key'         => null,
                        'message'     => "Found '{$rawFound}' at column {$pos} which belongs to L2 Post Test, but column {$pos} is reserved for L3 Project.",
                    ];
                    $columnResults[$pos] = $issue;
                    $issues[] = $issue;
                    continue;
                }
            }

            if ($canonical['key'] === 'l3_verbal') {
                // Expected: L3 Verbal
                if ($normFound === 'verbal' || in_array($normFound, ['l3 verbal', 'l3 project verbal', 'project verbal', 'l3 result'])) {
                    $autoMatchedCount++;
                    $res = [
                        'col'         => $pos,
                        'letter'      => $colLetter,
                        'expected'    => 'Verbal (L3)',
                        'found'       => $rawFound,
                        'status'      => 'AUTO_MATCHED',
                        'mapped_to'   => 'Verbal (L3)',
                        'key'         => 'l3_verbal',
                        'message'     => 'Matched to L3 Verbal project qualitative evaluation.',
                    ];
                    $columnResults[$pos] = $res;
                    $autoMatchedList[] = $res;
                    $mappedKeys['l3_verbal'][] = $pos;
                    continue;
                }
                if (str_contains($normFound, 'l2') && str_contains($normFound, 'verbal')) {
                    $invalidCount++;
                    $issue = [
                        'col'         => $pos,
                        'letter'      => $colLetter,
                        'expected'    => 'Verbal (L3 Project)',
                        'found'       => $rawFound,
                        'status'      => 'INVALID',
                        'mapped_to'   => null,
                        'key'         => null,
                        'message'     => "Found '{$rawFound}' at column {$pos} which belongs to L2 Post Test, but column {$pos} is reserved for L3 Project.",
                    ];
                    $columnResults[$pos] = $issue;
                    $issues[] = $issue;
                    continue;
                }
            }

            // 4. CHECK IF BARE KEYWORD IS DANGEROUSLY AMBIGUOUS
            if (in_array($normFound, $ambiguousKeywords, true)) {
                $ambiguousCount++;
                $issue = [
                    'col'         => $pos,
                    'letter'      => $colLetter,
                    'expected'    => $canonical['header'],
                    'found'       => $rawFound,
                    'status'      => 'AMBIGUOUS',
                    'mapped_to'   => null,
                    'key'         => null,
                    'message'     => "Header '{$rawFound}' is ambiguous at column {$pos}. Expected '{$canonical['header']}'. Please specify full header name.",
                ];
                $columnResults[$pos] = $issue;
                $issues[] = $issue;
                continue;
            }

            // 4b. YEAR-AGNOSTIC CORPORATE KPI HEADER MATCHING (Cols 17 & 18)
            if ($canonical['key'] === 'counted_corporate_kpi') {
                if (preg_match('/counted\s+(as\s+)?(\'?\d{2,4}\s+)?corporate\s+kpi/i', $normFound) ||
                    preg_match('/^corporate\s+kpi/i', $normFound) ||
                    preg_match('/^kpi\s+corporate/i', $normFound)) {
                    $autoMatchedCount++;
                    $res = [
                        'col'         => $pos,
                        'letter'      => $colLetter,
                        'expected'    => $canonical['header'],
                        'found'       => $rawFound,
                        'status'      => 'AUTO_MATCHED',
                        'mapped_to'   => $canonical['header'],
                        'key'         => $canonical['key'],
                        'message'     => "Matched Corporate KPI header (year-agnostic) to '{$canonical['header']}'.",
                    ];
                    $columnResults[$pos] = $res;
                    $autoMatchedList[] = $res;
                    $mappedKeys[$canonical['key']][] = $pos;
                    continue;
                }
            }

            if ($canonical['key'] === 'sub_corporate_kpi') {
                if (preg_match('/^sub\s+(\'?\d{2,4}\s+)?corporate\s+kpi/i', $normFound) ||
                    preg_match('/^sub\s+kpi/i', $normFound)) {
                    $autoMatchedCount++;
                    $res = [
                        'col'         => $pos,
                        'letter'      => $colLetter,
                        'expected'    => $canonical['header'],
                        'found'       => $rawFound,
                        'status'      => 'AUTO_MATCHED',
                        'mapped_to'   => $canonical['header'],
                        'key'         => $canonical['key'],
                        'message'     => "Matched Sub Corporate KPI header (year-agnostic) to '{$canonical['header']}'.",
                    ];
                    $columnResults[$pos] = $res;
                    $autoMatchedList[] = $res;
                    $mappedKeys[$canonical['key']][] = $pos;
                    continue;
                }
            }

            // 5. PREDEFINED ALIAS MATCH FOR THIS COLUMN
            if (in_array($normFound, $canonical['aliases'], true)) {
                $autoMatchedCount++;
                $res = [
                    'col'         => $pos,
                    'letter'      => $colLetter,
                    'expected'    => $canonical['header'],
                    'found'       => $rawFound,
                    'status'      => 'AUTO_MATCHED',
                    'mapped_to'   => $canonical['header'],
                    'key'         => $canonical['key'],
                    'message'     => "Matched canonical header '{$canonical['header']}' via recognized alias.",
                ];
                $columnResults[$pos] = $res;
                $autoMatchedList[] = $res;
                if ($canonical['key']) {
                    $mappedKeys[$canonical['key']][] = $pos;
                }
                continue;
            }

            // 6. SEMANTIC MISMATCH & DUPLICATE GUARD: Check if found header clearly belongs to ANOTHER canonical column
            $belongsToOtherCol = null;
            foreach ($canonicalMap as $otherIdx => $otherCanon) {
                if ($otherIdx === $pos) {
                    continue;
                }
                if ($normFound === $otherCanon['norm_header'] || in_array($normFound, $otherCanon['aliases'], true)) {
                    $belongsToOtherCol = $otherCanon;
                    break;
                }
            }

            if ($belongsToOtherCol) {
                $otherKey = $belongsToOtherCol['key'];
                // If that canonical field was already mapped by an earlier column, this is a DUPLICATE!
                if (!empty($mappedKeys[$otherKey])) {
                    $res = [
                        'col'         => $pos,
                        'letter'      => $colLetter,
                        'expected'    => $canonical['header'],
                        'found'       => $rawFound,
                        'status'      => 'DUPLICATE',
                        'mapped_to'   => $belongsToOtherCol['header'],
                        'key'         => $otherKey,
                        'message'     => "Duplicate column mapping: column {$pos} ({$colLetter}: '{$rawFound}') duplicates field '{$belongsToOtherCol['header']}'.",
                    ];
                    $columnResults[$pos] = $res;
                    $mappedKeys[$otherKey][] = $pos;
                    continue;
                }

                $invalidCount++;
                $issue = [
                    'col'         => $pos,
                    'letter'      => $colLetter,
                    'expected'    => $canonical['header'],
                    'found'       => $rawFound,
                    'status'      => 'INVALID',
                    'mapped_to'   => null,
                    'key'         => null,
                    'message'     => "Wrong semantic header: found '{$rawFound}' (which corresponds to Column {$belongsToOtherCol['col']} '{$belongsToOtherCol['header']}'), but Column {$pos} expects '{$canonical['header']}'.",
                ];
                $columnResults[$pos] = $issue;
                $issues[] = $issue;
                continue;
            }

            // 7. SAFE POSITION-AWARE TYPO / FUZZY MATCH
            $isSafeTypo = false;
            $matchedViaAlias = null;

            // Only attempt typo matching if length is at least 4 characters to avoid false short collisions
            if (mb_strlen($normFound) >= 4) {
                // Check against canonical header
                $lev = levenshtein($normFound, $canonical['norm_header']);
                $maxLen = max(mb_strlen($normFound), mb_strlen($canonical['norm_header']));
                $simPercent = (1 - ($lev / $maxLen)) * 100;

                $maxAllowedLev = mb_strlen($normFound) >= 8 ? 2 : 1;
                if ($lev <= $maxAllowedLev && $simPercent >= 75) {
                    $isSafeTypo = true;
                }

                // If not matched, test against close aliases of this column
                if (!$isSafeTypo) {
                    foreach ($canonical['aliases'] as $aliasNorm) {
                        if (mb_strlen($aliasNorm) >= 4) {
                            $levAlias = levenshtein($normFound, $aliasNorm);
                            $maxAliasLen = max(mb_strlen($normFound), mb_strlen($aliasNorm));
                            $simAliasPercent = (1 - ($levAlias / $maxAliasLen)) * 100;
                            if ($levAlias <= $maxAllowedLev && $simAliasPercent >= 75) {
                                $isSafeTypo = true;
                                $matchedViaAlias = $aliasNorm;
                                break;
                            }
                        }
                    }
                }
            }

            if ($isSafeTypo) {
                $autoMatchedCount++;
                $res = [
                    'col'         => $pos,
                    'letter'      => $colLetter,
                    'expected'    => $canonical['header'],
                    'found'       => $rawFound,
                    'status'      => 'AUTO_MATCHED',
                    'mapped_to'   => $canonical['header'],
                    'key'         => $canonical['key'],
                    'message'     => "Matched canonical header '{$canonical['header']}' via safe typo tolerance.",
                ];
                $columnResults[$pos] = $res;
                $autoMatchedList[] = $res;
                if ($canonical['key']) {
                    $mappedKeys[$canonical['key']][] = $pos;
                }
                continue;
            }


            // 8. UNKNOWN / INVALID HEADER
            $invalidCount++;
            $issue = [
                'col'         => $pos,
                'letter'      => $colLetter,
                'expected'    => $canonical['header'],
                'found'       => $rawFound,
                'status'      => 'INVALID',
                'mapped_to'   => null,
                'key'         => null,
                'message'     => "Unrecognized header '{$rawFound}' at column {$pos} ({$colLetter}). Expected '{$canonical['header']}'.",
            ];
            $columnResults[$pos] = $issue;
            $issues[] = $issue;
        }

        // 9. DETECT DUPLICATE CANONICAL MAPPINGS
        $reportedDuplicateCols = [];
        foreach ($mappedKeys as $key => $colIndices) {
            if ($key && count($colIndices) > 1) {
                foreach ($colIndices as $dupCol) {
                    if (!isset($reportedDuplicateCols[$dupCol])) {
                        $duplicateCount++;
                        $reportedDuplicateCols[$dupCol] = true;
                    }
                    $origRes = $columnResults[$dupCol];
                    $dupIssue = [
                        'col'         => $dupCol,
                        'letter'      => Coordinate::stringFromColumnIndex($dupCol),
                        'expected'    => $origRes['expected'] ?? null,
                        'found'       => $origRes['found'] ?? null,
                        'status'      => 'DUPLICATE',
                        'mapped_to'   => $origRes['expected'] ?? null,
                        'key'         => $key,
                        'message'     => "Duplicate canonical mapping: multiple columns (" . implode(', ', array_map([Coordinate::class, 'stringFromColumnIndex'], $colIndices)) . ") mapped to field '{$key}'.",
                    ];
                    $columnResults[$dupCol] = $dupIssue;
                    $issues[] = $dupIssue;
                }
            }
        }

        // 10. VERIFY ALL REQUIRED CANONICAL KEYS ARE MAPPED
        $requiredKeys = self::requiredDatabaseKeys();
        foreach ($requiredKeys as $reqKey) {
            if (empty($mappedKeys[$reqKey])) {
                $missingCol = null;
                foreach ($columns as $cIdx => $cDef) {
                    if ($cDef['key'] === $reqKey) {
                        $missingCol = $cDef;
                        break;
                    }
                }
                $headerTitle = $missingCol ? $missingCol['header'] : $reqKey;
                $issues[] = [
                    'col'         => $missingCol ? $missingCol['col_index'] : null,
                    'letter'      => $missingCol ? Coordinate::stringFromColumnIndex($missingCol['col_index']) : null,
                    'expected'    => $headerTitle,
                    'found'       => null,
                    'status'      => 'MISSING',
                    'mapped_to'   => null,
                    'key'         => $reqKey,
                    'message'     => "Required field '{$headerTitle}' is not mapped to any column.",
                ];
                $missingCount++;
            }
        }

        // Build column letter positions map for row reader
        $columnPositions = [];
        $columnKeysList = [];
        foreach ($columnResults as $cIdx => $res) {
            if (!empty($res['key']) && in_array($res['status'], ['VALID', 'AUTO_MATCHED', 'WARNING'], true)) {
                $columnPositions[$res['key']] = Coordinate::stringFromColumnIndex($cIdx);
                $columnKeysList[] = $res['key'];
            }
        }

        $canProceed = ($invalidCount === 0 && $missingCount === 0 && $duplicateCount === 0 && $ambiguousCount === 0);

        return [
            'can_proceed'        => $canProceed,
            'is_valid'           => $canProceed,
            'format'             => $format,
            'expected_columns'   => $expectedCount,
            'detected_columns'   => $detectedCount,
            'stats'              => [
                'total'          => $expectedCount,
                'detected'       => $detectedCount,
                'valid'          => $validCount,
                'auto_matched'   => $autoMatchedCount,
                'warning'        => $warningCount,
                'invalid'        => $invalidCount,
                'missing'        => $missingCount,
                'duplicate'      => $duplicateCount,
                'ambiguous'      => $ambiguousCount,
            ],
            'columns'            => $columnResults,
            'issues'             => $issues,
            'auto_matched_list'  => $autoMatchedList,
            'column_positions'   => $columnPositions,
            'column_keys'        => array_values(array_unique($columnKeysList)),
        ];
    }

    /**
     * Apply legacy single 44-column header styles on Row 1.
     */
    public static function applyLegacyDataSheetHeaderStyles(Worksheet $sheet): void
    {
        $columns = self::legacyColumns();
        foreach ($columns as $idx => $col) {
            $colLetter = Coordinate::stringFromColumnIndex($idx);
            $sheet->setCellValue($colLetter . '1', $col['header']);

            $isReq = $col['required'];
            $sheet->getStyle($colLetter . '1')->applyFromArray([
                'font' => [
                    'bold'  => true,
                    'size'  => 10,
                    'color' => ['rgb' => $isReq ? 'B91C1C' : '1E293B'],
                ],
                'alignment' => [
                    'horizontal' => Alignment::HORIZONTAL_CENTER,
                    'vertical'   => Alignment::VERTICAL_CENTER,
                    'wrapText'   => true,
                ],
                'fill' => [
                    'fillType'   => Fill::FILL_SOLID,
                    'startColor' => ['rgb' => $isReq ? 'FEF2F2' : 'F1F5F9'],
                ],
                'borders' => [
                    'allBorders' => [
                        'borderStyle' => Border::BORDER_THIN,
                        'color'       => ['rgb' => 'CBD5E1'],
                    ],
                ],
            ]);
        }

        $sheet->getRowDimension(1)->setRowHeight(32);
        $sheet->freezePane('A2');
    }

    /**
     * Apply standardized 47-column single-row header styles.
     * Row 1: Header NO (Col A) + 46 Canonical Column Headers (Cols B..AU). No group headers, no merged cells.
     * Row 2: Example data row (NO = 1, followed by canonical example values).
     * Rows 3..7: Sequential numbers 2, 3, 4, 5, 6 with empty styled cells.
     * Freeze panes at B2, AutoFilter on A1:AU1.
     */
    public static function applyDataSheetHeaderStyles(Worksheet $sheet): void
    {
        $columns = self::canonicalColumns();
        $totalCols = count($columns) + 1; // 47 total columns (NO + 46 canonical columns)
        $lastColLetter = Coordinate::stringFromColumnIndex($totalCols); // AU

        // 1. Row 1: Single Main Column Headers (No Group Headers, No Merged Cells)
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
                'startColor' => ['rgb' => '1E293B'], // Dark slate header
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
                    'startColor' => ['rgb' => '1E293B'], // Dark slate header
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
        $sheet->setCellValueExplicit('A2', 1, \PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_NUMERIC);
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
            $sheet->setCellValueExplicit("{$letter}2", $exampleVal, \PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_STRING);
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
            $sheet->setCellValueExplicit("A{$r}", $seq, \PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_NUMERIC);
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
        foreach (self::columnWidths() as $colLetter => $width) {
            $sheet->getColumnDimension($colLetter)->setWidth($width);
        }

        // 5. Freeze panes at B2 (sticky NO column A, sticky header Row 1)
        $sheet->freezePane('B2');

        // 6. Auto filter covering all 47 columns on Row 1 (A1:AU1)
        $sheet->setAutoFilter("A1:{$lastColLetter}1");
    }

    /**
     * Populate the Instructions worksheet with guidelines for standardized 47-column Non-PTKP import.
     */
    public static function populateInstructionsSheet(Worksheet $instructionSheet): void
    {
        $instructionSheet->setCellValue('A1', 'KP OWL LMS - Non KP OWL Offline Training Import Guidelines (47 Columns)');
        $instructionSheet->getStyle('A1')->getFont()->setBold(true)->setSize(14)->getColor()->setRGB('0F172A');
        $instructionSheet->getRowDimension(1)->setRowHeight(30);

        $instructions = [
            ['1. Template Structure', 'The template uses a single header row on Row 1 (47 columns). Column A (NO) is a visual sequence number only. Data entry begins on Row 2 (Employee ID through PIC).'],
            ['2. "NO" Column', 'Column A contains sequential row numbering (1, 2, 3...) for user reference. It is not saved to the database and does not affect data validation.'],
            ['3. Employee ID', 'Mandatory employee ID / NIP at Column 2 (B). LMS user accounts are linked automatically; external participants without LMS accounts are preserved as WARNING.'],
            ['4. Participant Records', 'Employee Name, Grade, Job Position, Team, and Department are synchronized with LMS users when available. Each row represents a single participant record.'],
            ['5. Course Title', 'Mandatory training title. Rows with empty Course Title will be rejected (INVALID status).'],
            ['6. Company Information', 'Any non-empty company is accepted. Empty company triggers non-blocking review (INVALID COMPANY status).'],
            ['7. Provider & Sub Provider', 'Training provider categories (Internal / External, Vendor, etc.) and provider name.'],
            ['8. Attendance & Bond', 'Attendance (Yes/No), attendance reason, service bond duration, bond completion date, and bond amount.'],
            ['9. Kirkpatrick Evaluations', 'Score (L1) Course, Score (L2), L2 Status, Score (L3), and L3 Status.'],
            ['10. Certificate Columns', 'Certificate Availability (Yes/No), Institution, Issued Date, and Expired Date.'],
            ['11. Person in Charge (PIC)', 'Person in Charge (PIC) is located at the final column (Column 47 / AU).'],
            ['12. Date & Time Format', 'Use YYYY-MM-DD for dates and HH:mm or HH:mm:ss for times.'],
            ['13. Duplicate Prevention', 'Records with identical participant and training combinations within the file or existing database records will be skipped automatically (DUPLICATE status).'],
            ['14. Event Grouping', 'Participants in the same course, batch, dates, type, and venue are grouped into a single training session automatically.'],
        ];

        $row = 3;
        foreach ($instructions as $inst) {
            $instructionSheet->setCellValue("A{$row}", $inst[0]);
            $instructionSheet->setCellValue("B{$row}", $inst[1]);
            $instructionSheet->getStyle("A{$row}")->getFont()->setBold(true);
            $instructionSheet->getStyle("A{$row}:B{$row}")->applyFromArray([
                'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['rgb' => 'E2E8F0']]],
                'alignment' => ['vertical' => Alignment::VERTICAL_TOP, 'wrapText' => true],
            ]);
            $row++;
        }

        $instructionSheet->getColumnDimension('A')->setWidth(30);
        $instructionSheet->getColumnDimension('B')->setWidth(95);
    }

    /**
     * Generate PhpSpreadsheet instance containing standardized 48-column 2-row template + instructions.
     */
    public static function generateTemplateSpreadsheet(): Spreadsheet
    {
        $spreadsheet = new Spreadsheet();
        $spreadsheet->getProperties()
            ->setCreator('KP OWL LMS')
            ->setLastModifiedBy('KP OWL LMS')
            ->setTitle('Non KP OWL Offline Training Import Template')
            ->setSubject('Non KP OWL Training Records Template')
            ->setDescription('Official Excel template for uploading Non-PTKP offline training records into KP OWL LMS (48 columns).');

        // ----------------------------------------------------
        // SHEET 1: Non KP OWL Import (48 Columns with 2-Row Grouped Headers)
        // ----------------------------------------------------
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle(self::DATA_SHEET_TITLE);

        self::applyDataSheetHeaderStyles($sheet);

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

    /**
     * Format timestamp as dM Y \a\t H.i.s (e.g., 21Okt2026 at 09.35.00)
     */
    public static function formatExportTimestamp(?\DateTimeInterface $date = null): string
    {
        $dt = $date ? \Illuminate\Support\Carbon::parse($date) : \Illuminate\Support\Carbon::now();
        $monthMap = [
            1 => 'Jan', 2 => 'Feb', 3 => 'Mar', 4 => 'Apr',
            5 => 'Mei', 6 => 'Jun', 7 => 'Jul', 8 => 'Agu',
            9 => 'Sep', 10 => 'Okt', 11 => 'Nov', 12 => 'Des',
        ];
        $day = $dt->format('d');
        $month = $monthMap[(int) $dt->format('n')] ?? $dt->format('M');
        $year = $dt->format('Y');
        $time = $dt->format('H.i.s');

        return "{$day}{$month}{$year} at {$time}";
    }

    public static function generateUserFileName(string $employeeId, ?\DateTimeInterface $date = null): string
    {
        $timestamp = self::formatExportTimestamp($date);
        return "MyNonOWLrecord_{$employeeId}_{$timestamp}.xlsx";
    }

    public static function generateManageFileName(?\DateTimeInterface $date = null): string
    {
        $timestamp = self::formatExportTimestamp($date);
        return "EmployeeNonOWLrecord_{$timestamp}.xlsx";
    }
}
