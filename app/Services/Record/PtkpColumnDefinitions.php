<?php

namespace App\Services\Record;

class PtkpColumnDefinitions
{
    /**
     * Canonical 49-column definition array for PTKP Import.
     */
    public static function all(): array
    {
        return [
            1  => self::def(1,  'A',  'No',                       'GENERAL',                  'row_no',                   false, 'integer',  1,              'Sequence number for preview', ['no', 'no.', 'nomor', 'row no', 'seq', '#']),
            2  => self::def(2,  'B',  'Employee ID',              'JOB INFORMATION',          'employee_id',              true,  'string',   '104921',       'Employee NIP / ID', ['employee id', 'emp id', 'id emp', 'nip', 'nik', 'id', 'employee_id', 'no pegawai']),
            3  => self::def(3,  'C',  'Employee Name',            'JOB INFORMATION',          'name',                     false, 'string',   'Budi Santoso', 'Participant name (populated from users)', ['employee name', 'name', 'nama', 'participant name', 'nama peserta', 'nama karyawan']),
            4  => self::def(4,  'D',  'Grade',                    'JOB INFORMATION',          'grade',                    false, 'string',   '4A',           'Employee grade (populated from users)', ['grade', 'golongan', 'level grade', 'employee grade', 'jobgrade']),
            5  => self::def(5,  'E',  'Job Position',             'JOB INFORMATION',          'job_position',             false, 'string',   'Operations Specialist', 'Job position (populated from users)', ['job position', 'position', 'jabatan', 'posisi', 'job_position', 'job title', 'jobposition']),
            6  => self::def(6,  'F',  'Team',                     'JOB INFORMATION',          'team',                     false, 'string',   'Rolling Mill 1', 'Team (populated from users)', ['team', 'tim', 'unit', 'regu', 'seksi', 'work team']),
            7  => self::def(7,  'G',  'Department',               'JOB INFORMATION',          'department',               false, 'string',   'Maintenance Department', 'Department (populated from users)', ['department', 'dept', 'departemen', 'departement', 'divisi']),
            8  => self::def(8,  'H',  'Company',                  'JOB INFORMATION',          'company',                  false, 'string',   'PT Krakatau Posco', 'Company name (Mismatch is warning)', ['company', 'perusahaan', 'pt', 'entity', 'company name']),
            9  => self::def(9,  'I',  'Course Title',             'TRAINING INFORMATION',     'course_name',              true,  'string',   'High Voltage Safety & Operation', 'Training course title (Mandatory)', ['course title', 'course name', 'course', 'nama course', 'training name', 'nama training', 'title']),
            10 => self::def(10, 'J',  'Sub Course Title',         'TRAINING INFORMATION',     'sub_course_name',          false, 'string',   'Switchgear Troubleshooting Module', 'Sub course or module title', ['sub course title', 'sub course name', 'sub_course_name', 'sub course', 'module', 'modul']),
            11 => self::def(11, 'K',  'Type',                     'TRAINING INFORMATION',     'type',                     false, 'string',   'In-House',     'In-House, Public, Certification', ['type', 'tipe', 'training type', 'tipe training', 'jenis training']),
            12 => self::def(12, 'L',  'Category',                 'TRAINING INFORMATION',     'category',                 false, 'string',   'Technical Competency', 'Training category', ['category', 'kategori', 'training category', 'kategori training']),
            13 => self::def(13, 'M',  'Level',                    'TRAINING INFORMATION',     'level',                    false, 'string',   'Intermediate', 'Basic, Intermediate, Advanced', ['level', 'tingkat', 'level training', 'jenjang']),
            14 => self::def(14, 'N',  'Batch',                    'TRAINING INFORMATION',     'batch',                    false, 'string',   'Batch 1',      'Training batch or wave', ['batch', 'angkatan', 'gelombang', 'wave']),
            15 => self::def(15, 'O',  'Main Classification',      'CLASSIFICATION',           'main_classification',      false, 'string',   'Basic & Common Duties', 'Main classification grouping', ['main classification', 'klasifikasi utama', 'classification', 'klasifikasi']),
            16 => self::def(16, 'P',  'Sub Classification',       'CLASSIFICATION',           'sub_classification',       false, 'string',   'Plant Maintenance', 'Sub classification grouping', ['sub classification', 'sub klasifikasi', 'sub_classification']),
            17 => self::def(17, 'Q',  'Delivery Method',          'DELIVERY',                 'delivery_method',          false, 'string',   'Offline',      'Offline / Online / Blended', ['delivery method', 'metode delivery', 'metode pembelajaran', 'metode training']),
            18 => self::def(18, 'R',  'Sub Delivery Method',      'DELIVERY',                 'sub_delivery_method',      false, 'string',   'On-site',      'Sub delivery technique', ['sub delivery method', 'sub_delivery_method', 'sub metode delivery']),
            19 => self::def(19, 'S',  'Provider',                 'PROVIDER & INSTRUCTOR',    'provider',                 false, 'string',   'External',     'Internal / External provider type', ['provider', 'penyedia', 'tipe provider']),
            20 => self::def(20, 'T',  'Sub Provider',             'PROVIDER & INSTRUCTOR',    'sub_provider',             false, 'string',   'Vendor',       'Sub provider category', ['sub provider', 'sub_provider', 'sub penyedia']),
            21 => self::def(21, 'U',  'Provider Name',            'PROVIDER & INSTRUCTOR',    'provider_name',            false, 'string',   'PT Schneider Indonesia', 'Institution or vendor name', ['provider name', 'nama provider', 'lembaga training', 'vendor name', 'nama vendor']),
            22 => self::def(22, 'V',  'Venue',                    'PROVIDER & INSTRUCTOR',    'venue',                    false, 'string',   'Auditorium Hall B', 'Training venue / location', ['venue', 'lokasi', 'tempat training', 'location', 'ruangan']),
            23 => self::def(23, 'W',  'Instructor',               'PROVIDER & INSTRUCTOR',    'instructor',               false, 'string',   'Ir. Gunawan, M.T.', 'Trainer / instructor name', ['instructor', 'trainer', 'instruktur', 'pengajar', 'facilitator']),
            24 => self::def(24, 'X',  'Course Duration',          'DURATION & LEARNING HOURS','duration_seconds',         false, 'duration', '08:00:00',     'Format: HH:MM:SS', ['course duration', 'duration', 'durasi', 'training duration', 'duration_seconds']),
            25 => self::def(25, 'Y',  'Learning Hours',           'DURATION & LEARNING HOURS','learning_hours',           false, 'number',   8.0,            'Credited learning hours', ['learning hours', 'learning (hours)', 'jam pelajaran', 'jp', 'hours']),
            26 => self::def(26, 'Z',  '% Progress',               'PROGRESS',                 'progress',                 false, 'number',   100,            '0 to 100 (%)', ['% progress', 'progress %', 'progress', 'kemajuan', 'persentase progress']),
            27 => self::def(27, 'AA', 'Progress Status',          'PROGRESS',                 'progress_status',          false, 'string',   'Finished',     'Finished / Ongoing / Not Started', ['progress status', 'status progress', 'status kemajuan']),
            28 => self::def(28, 'AB', 'Start Date',               'SCHEDULE & REQUIREMENT',   'start_date',               false, 'date',     '2026-08-10',   'Format: YYYY-MM-DD', ['start date', 'tanggal mulai', 'tgl mulai', 'start_date']),
            29 => self::def(29, 'AC', 'End Date',                 'SCHEDULE & REQUIREMENT',   'end_date',                 false, 'date',     '2026-08-10',   'Format: YYYY-MM-DD', ['end date', 'finish date', 'tanggal selesai', 'tgl selesai', 'end_date', 'finish_date']),
            30 => self::def(30, 'AD', 'Start Time',               'SCHEDULE & REQUIREMENT',   'start_time',               false, 'string',   '08:00:00',     'Format: HH:mm or HH:mm:ss', ['start time', 'jam mulai', 'waktu mulai', 'start_time']),
            31 => self::def(31, 'AE', 'End Time',                 'SCHEDULE & REQUIREMENT',   'end_time',                 false, 'string',   '17:00:00',     'Format: HH:mm or HH:mm:ss', ['end time', 'jam selesai', 'waktu selesai', 'end_time']),
            32 => self::def(32, 'AF', 'Record Month',             'SCHEDULE & REQUIREMENT',   'month',                    false, 'string',   'August',       'Derived month name', ['record month', 'month', 'bulan', 'periode bulan']),
            33 => self::def(33, 'AG', 'Requirement',              'SCHEDULE & REQUIREMENT',   'requirement',              false, 'string',   'Mandatory',    'Mandatory / Elective', ['requirement', 'status', 'kebutuhan', 'sifat training']),
            34 => self::def(34, 'AH', 'Attendance',               'ATTENDANCE & BOND',        'attendance',               false, 'boolean',  'Yes',          'Yes / No', ['attendance', 'kehadiran', 'presensi', 'status kehadiran', 'hadir']),
            35 => self::def(35, 'AI', 'Attendance Reason',        'ATTENDANCE & BOND',        'attendance_reason',        false, 'string',   'Present in all sessions', 'Attendance justification if absent', ['attendance reason', 'reason', 'alasan', 'alasan ketidakhadiran']),
            36 => self::def(36, 'AJ', 'Bond Status',              'ATTENDANCE & BOND',        'bond_status',              false, 'string',   'No',           'Yes / No', ['bond status', 'status ikatan dinas', 'ikatan dinas', 'bond']),
            37 => self::def(37, 'AK', 'Bond Duration',            'ATTENDANCE & BOND',        'bond_duration',            false, 'number',   12,             'Bond duration in months', ['bond duration', 'durasi ikatan dinas', 'lama ikatan dinas']),
            38 => self::def(38, 'AL', 'Bond End Date',            'ATTENDANCE & BOND',        'bond_end_date',            false, 'date',     '',             'Format: YYYY-MM-DD', ['bond end date', 'tanggal selesai ikatan', 'tgl berakhir ikatan dinas']),
            39 => self::def(39, 'AM', 'Bond Amount',              'ATTENDANCE & BOND',        'bond_amount',              false, 'number',   '',             'Bond value (IDR)', ['bond amount', 'nilai ikatan dinas', 'biaya ikatan dinas', 'nominal ikatan dinas', 'bond amount (idr)', 'bond_amount_idr']),
            40 => self::def(40, 'AN', 'Score (L1) - Course',      'EVALUATION & COMPLETION',  'l1_program',               false, 'string',   'Very Good',    'Reaction evaluation rating', ['score (l1) - course', 'score (l1)', 'score l1', 'program', 'l1 program', 'l1', 'evaluasi l1']),
            41 => self::def(41, 'AO', 'Score (L2)',               'EVALUATION & COMPLETION',  'assessment_score',         false, 'number',   85,             'Post-test numerical score (0-100)', ['score (l2)', 'score l2', 'assessment score', 'post test score', 'nilai post test', 'l2 numerical']),
            42 => self::def(42, 'AP', 'L2 Status',                'EVALUATION & COMPLETION',  'l2_verbal',                false, 'string',   'Passed',       'Post-test status (Passed / Failed)', ['l2 status', 'status l2', 'l2 verbal', 'post test verbal', 'hasil post test']),
            43 => self::def(43, 'AQ', 'Score (L3)',               'EVALUATION & COMPLETION',  'l3_numerical',             false, 'number',   90.0,           'Project implementation score (0-100)', ['score (l3)', 'score l3', 'l3 numerical', 'nilai project', 'project score']),
            44 => self::def(44, 'AR', 'L3 Status',                'EVALUATION & COMPLETION',  'l3_verbal',                false, 'string',   'Implemented successfully', 'Behavior / project result status', ['l3 status', 'status l3', 'l3 verbal', 'project verbal', 'hasil project']),
            45 => self::def(45, 'AS', 'Person in Charge (PIC)',   'CLOSING & VERIFICATION',   'pic',                      false, 'string',   'HRDC Team',    'Person in charge / HR training admin', ['person in charge (pic)', 'person in charge', 'pic', 'penanggung jawab', 'admin pic']),
            46 => self::def(46, 'AT', 'Certificate Availability', 'CERTIFICATE DETAILS',      'certificate_availability', false, 'string',   'Yes',          'Yes / No availability of certificate', ['certificate availability', 'ketersediaan sertifikat', 'ada sertifikat', 'certificate_availability']),
            47 => self::def(47, 'AU', 'Certificate Institution',  'CERTIFICATE DETAILS',      'certificate_institution',  false, 'string',   'BNSP / Kemnaker', 'Issuing institution for external certificate', ['certificate institution', 'lembaga sertifikat', 'certificate_institution', 'penerbit sertifikat']),
            48 => self::def(48, 'AV', 'Certificate Issued Date',  'CERTIFICATE DETAILS',      'certificate_issued_date',  false, 'date',     '2026-08-11',   'Format: YYYY-MM-DD', ['certificate issued date', 'tanggal terbit sertifikat', 'tgl terbit sertifikat', 'certificate_issued_date']),
            49 => self::def(49, 'AW', 'Certificate Expired Date', 'CERTIFICATE DETAILS',      'certificate_expired_date', false, 'date',     '2029-08-11',   'Format: YYYY-MM-DD', ['certificate expired date', 'tanggal kedaluwarsa sertifikat', 'tgl expired sertifikat', 'certificate_expired_date']),
        ];
    }

    private static function def(
        int $colIndex,
        string $colLetter,
        string $header,
        string $group,
        string $key,
        bool $required,
        string $type,
        $example,
        string $note,
        array $aliases
    ): array {
        return [
            'col_index'  => $colIndex,
            'col_letter' => $colLetter,
            'header'     => $header,
            'group'      => $group,
            'key'        => $key,
            'required'   => $required,
            'type'       => $type,
            'example'    => $example,
            'note'       => $note,
            'aliases'    => $aliases,
        ];
    }
}
