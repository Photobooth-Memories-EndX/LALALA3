<?php

namespace App\Services\Record;

class PtkpColumnDefinitions
{
    /**
     * Columns that trigger non-blocking WARNING when left empty.
     * Covers all 33 designated warning fields per business specification.
     *
     * @return array<string, string> Key => Canonical Header Label
     */
    public static function getWarningOnEmptyColumns(): array
    {
        return [
            'type'                => 'Type',
            'category'            => 'Category',
            'level'               => 'Level',
            'batch'               => 'Batch',
            'main_classification' => 'Main Classification',
            'sub_classification'  => 'Sub Classification',
            'delivery_method'     => 'Delivery Method',
            'sub_delivery_method' => 'Sub Delivery Method',
            'provider_name'       => 'Provider Name',
            'venue'               => 'Venue',
            'instructor'          => 'Instructor',
            'course_duration'     => 'Course Duration',
            'learning_hours'      => 'Learning Hours',
            'progress'            => '% Progress',
            'progress_status'     => 'Progress Status',
            'start_date'          => 'Start Date',
            'end_date'            => 'End Date',
            'start_time'          => 'Start Time',
            'end_time'            => 'End Time',
            'month'               => 'Record Month',
            'requirement'         => 'Requirement',
            'attendance'          => 'Attendance',
            'attendance_reason'   => 'Attendance Reason',
            'bond_status'         => 'Bond Status',
            'bond_duration'       => 'Bond Duration',
            'bond_end_date'       => 'Bond End Date',
            'bond_amount'         => 'Bond Amount',
            'l1_program'          => 'Score (L1) - Course',
            'assessment_score'    => 'Score (L2)',
            'l2_verbal'           => 'L2 Status',
            'l3_numerical'        => 'Score (L3)',
            'l3_verbal'           => 'L3 Status',
            'pic'                 => 'Person in Charge (PIC)',
        ];
    }

    /**
     * Check if a column key triggers WARNING when empty.
     */
    public static function isWarningOnEmpty(string $key): bool
    {
        return isset(self::getWarningOnEmptyColumns()[$key]);
    }

    /**
     * Check if a warning-designated field value should be treated as empty.
     * Treats ONLY genuinely empty values (null, empty string, or whitespace-only string) as empty.
     * Hyphens/dashes ("-", " - ", "--", "–", "—") are considered POPULATED data and do NOT trigger warning.
     * Legitimate values like 0, "0", 0.0, "0.0", "No", "N/A" are NOT empty.
     *
     * @param mixed $value
     * @return bool
     */
    public static function isWarningFieldEmpty($value): bool
    {
        if ($value === null) {
            return true;
        }

        if (!is_string($value)) {
            return false;
        }

        return trim($value) === '';
    }

    /**
     * Format an empty warning field value for user-facing display in the inspection table.
     *
     * @param mixed $value
     * @return string
     */
    public static function formatWarningEmptyDisplay($value): string
    {
        return 'Empty';
    }

    /**
     * Canonical 46-column definition array for PTKP and Non-PTKP Import.
     * Single-row header structure: Employee ID (col 1, A) through Person in Charge (PIC) (col 46, AT).
     */
    public static function all(): array
    {
        return [
            1  => self::def(1,  'A',  'Employee ID',              'JOB INFORMATION',          'employee_id',              true,  'string',   '104921',       'Employee NIP / ID (Mandatory)', ['employee id', 'emp id', 'id emp', 'nip', 'nik', 'id', 'employee_id', 'no pegawai', 'employee no', 'emp no', 'employee number', 'nomor pegawai', 'id employee', 'no. pegawai', 'nip / id', 'id/nip', 'nip/id', 'id karyawan']),
            2  => self::def(2,  'B',  'Employee Name',            'JOB INFORMATION',          'name',                     false, 'string',   'Budi Santoso', 'Participant name (populated from users)', ['employee name', 'name', 'nama', 'participant name', 'nama peserta', 'nama karyawan', 'peserta', 'full name', 'fullname', 'nama lengkap', 'employee_name', 'peserta training']),
            3  => self::def(3,  'C',  'Grade',                    'JOB INFORMATION',          'grade',                    false, 'string',   '4A',           'Employee grade (populated from users)', ['grade', 'golongan', 'level grade', 'employee grade', 'jobgrade', 'job grade', 'job_grade', 'pangkat']),
            4  => self::def(4,  'D',  'Job Position',             'JOB INFORMATION',          'job_position',             false, 'string',   'Operations Specialist', 'Job position (populated from users)', ['job position', 'position', 'jabatan', 'posisi', 'job_position', 'job title', 'jobposition', 'position_title', 'posisi jabatan']),
            5  => self::def(5,  'E',  'Team',                     'JOB INFORMATION',          'team',                     false, 'string',   'Rolling Mill 1', 'Team (populated from users)', ['team', 'tim', 'unit', 'regu', 'seksi', 'work team']),
            6  => self::def(6,  'F',  'Department',               'JOB INFORMATION',          'department',               false, 'string',   'Maintenance Department', 'Department (populated from users)', ['department', 'dept', 'departemen', 'departement', 'divisi', 'department name']),
            7  => self::def(7,  'G',  'Company',                  'JOB INFORMATION',          'company',                  false, 'string',   'PT Krakatau Posco', 'Company name (Mismatch is warning)', ['company', 'perusahaan', 'pt', 'entity', 'company name', 'legal entity', 'company_name', 'nama perusahaan', 'nama pt', 'organization', 'instansi', 'unit kerja']),
            8  => self::def(8,  'H',  'Course Title',             'TRAINING INFORMATION',     'course_name',              true,  'string',   'High Voltage Safety & Operation', 'Training course title (Mandatory)', ['course title', 'course name', 'course', 'nama course', 'training name', 'nama training', 'title', 'judul training', 'training title', 'nama pelatihan', 'topik training', 'judul kursus', 'nama kursus', 'course_title', 'course_name']),
            9  => self::def(9,  'I',  'Sub Course Title',         'TRAINING INFORMATION',     'sub_course_name',          false, 'string',   'Switchgear Troubleshooting Module', 'Sub course or module title', ['sub course title', 'sub course name', 'sub_course_name', 'sub course', 'module', 'modul', 'sub training', 'topic', 'nama sub course']),
            10 => self::def(10, 'J',  'Type',                     'TRAINING INFORMATION',     'type',                     false, 'string',   'In-House',     'In-House, Public, Certification', ['type', 'tipe', 'training type', 'tipe training', 'jenis training', 'tipe pelatihan']),
            11 => self::def(11, 'K',  'Category',                 'TRAINING INFORMATION',     'category',                 false, 'string',   'Technical Competency', 'Training category', ['category', 'kategori', 'training category', 'kategori training', 'kategori pelatihan']),
            12 => self::def(12, 'L',  'Level',                    'TRAINING INFORMATION',     'level',                    false, 'string',   'Intermediate', 'Basic, Intermediate, Advanced', ['level', 'tingkat', 'level training', 'jenjang']),
            13 => self::def(13, 'M',  'Batch',                    'TRAINING INFORMATION',     'batch',                    false, 'string',   'Batch 1',      'Training batch or wave', ['batch', 'angkatan', 'gelombang', 'wave']),
            14 => self::def(14, 'N',  'Main Classification',      'CLASSIFICATION',           'main_classification',      false, 'string',   'Basic & Common Duties', 'Main classification grouping', ['main classification', 'klasifikasi utama', 'classification', 'klasifikasi', 'main category']),
            15 => self::def(15, 'O',  'Sub Classification',       'CLASSIFICATION',           'sub_classification',       false, 'string',   'Plant Maintenance', 'Sub classification grouping', ['sub classification', 'sub klasifikasi', 'sub_classification']),
            16 => self::def(16, 'P',  'Delivery Method',          'DELIVERY',                 'delivery_method',          false, 'string',   'Offline',      'Offline / Online / Blended', ['delivery method', 'metode delivery', 'metode pembelajaran', 'metode training', 'delivery_method', 'method']),
            17 => self::def(17, 'Q',  'Sub Delivery Method',      'DELIVERY',                 'sub_delivery_method',      false, 'string',   'On-site',      'Sub delivery technique', ['sub delivery method', 'sub_delivery_method', 'sub metode delivery', 'sub method']),
            18 => self::def(18, 'R',  'Provider',                 'PROVIDER & INSTRUCTOR',    'provider',                 false, 'string',   'External',     'Internal / External provider type', ['provider', 'penyedia', 'tipe provider', 'provider type', 'jenis provider', 'penyelenggara', 'tipe penyelenggara', 'training provider', 'provider pelatihan']),
            19 => self::def(19, 'S',  'Sub Provider',             'PROVIDER & INSTRUCTOR',    'sub_provider',             false, 'string',   'Vendor',       'Sub provider category', ['sub provider', 'sub_provider', 'sub penyedia', 'kategori provider', 'sub vendor', 'sub penyelenggara', 'sub_penyelenggara']),
            20 => self::def(20, 'T',  'Provider Name',            'PROVIDER & INSTRUCTOR',    'provider_name',            false, 'string',   'PT Schneider Indonesia', 'Institution or vendor name', ['provider name', 'nama provider', 'lembaga training', 'vendor name', 'nama vendor', 'lembaga', 'vendor', 'nama lembaga', 'nama penyelenggara', 'provider_name', 'partner']),
            21 => self::def(21, 'U',  'Venue',                    'PROVIDER & INSTRUCTOR',    'venue',                    false, 'string',   'Auditorium Hall B', 'Training venue / location', ['venue', 'lokasi', 'tempat training', 'location', 'ruangan', 'tempat', 'ruang', 'room']),
            22 => self::def(22, 'V',  'Instructor',               'PROVIDER & INSTRUCTOR',    'instructor',               false, 'string',   'Ir. Gunawan, M.T.', 'Trainer / instructor name', ['instructor', 'trainer', 'instruktur', 'pengajar', 'facilitator', 'narasumber', 'trainer name', 'nama trainer', 'nama instruktur']),
            23 => self::def(23, 'W',  'Learning Hours',           'LEARNING & PROGRESS',      'learning_hours',           false, 'number',   8.0,            'Credited learning hours', ['learning hours', 'learning (hours)', 'jam pelajaran', 'jp', 'hours', 'learning_hours', 'total hours', 'durasi jam', 'credited hours', 'jam training', 'learning hour']),
            24 => self::def(24, 'X',  '% Progress',               'LEARNING & PROGRESS',      'progress',                 false, 'number',   100,            '0 to 100 (%)', ['% progress', 'progress %', 'progress', 'kemajuan', 'persentase progress', 'progres', 'progress percentage', 'persentase']),
            25 => self::def(25, 'Y',  'Start Date',               'SCHEDULE & REQUIREMENT',   'start_date',               false, 'date',     '10/08/2026',   'Format: DD/MM/YYYY', ['start date', 'tanggal mulai', 'tgl mulai', 'start_date', 'begin date', 'start']),
            26 => self::def(26, 'Z',  'End Date',                 'SCHEDULE & REQUIREMENT',   'end_date',                 false, 'date',     '10/08/2026',   'Format: DD/MM/YYYY', ['end date', 'finish date', 'tanggal selesai', 'tgl selesai', 'end_date', 'finish_date', 'completion date', 'finish', 'end']),
            27 => self::def(27, 'AA', 'Start Time',               'SCHEDULE & REQUIREMENT',   'start_time',               false, 'string',   '08:00:00',     'Format: HH:mm or HH:mm:ss', ['start time', 'jam mulai', 'waktu mulai', 'start_time', 'time start']),
            28 => self::def(28, 'AB', 'End Time',                 'SCHEDULE & REQUIREMENT',   'end_time',                 false, 'string',   '17:00:00',     'Format: HH:mm or HH:mm:ss', ['end time', 'jam selesai', 'waktu selesai', 'end_time', 'finish time', 'time end']),
            29 => self::def(29, 'AC', 'Record Month',             'SCHEDULE & REQUIREMENT',   'month',                    false, 'string',   'August',       'Derived month name', ['record month', 'month', 'bulan', 'periode bulan']),
            30 => self::def(30, 'AD', 'Requirement',              'SCHEDULE & REQUIREMENT',   'requirement',              false, 'string',   'Mandatory',    'Mandatory / Elective', ['requirement', 'kebutuhan', 'sifat training', 'training requirement']),
            31 => self::def(31, 'AE', 'Attendance',               'ATTENDANCE & BOND',        'attendance',               false, 'boolean',  'Yes',          'Yes / No', ['attendance', 'kehadiran', 'presensi', 'status kehadiran', 'hadir']),
            32 => self::def(32, 'AF', 'Attendance Reason',        'ATTENDANCE & BOND',        'attendance_reason',        false, 'string',   'Present in all sessions', 'Attendance justification if absent', ['attendance reason', 'reason', 'alasan', 'alasan ketidakhadiran', 'justification']),
            33 => self::def(33, 'AG', 'Bond Status',              'ATTENDANCE & BOND',        'bond_status',              false, 'string',   'No',           'Yes / No', ['bond status', 'status ikatan dinas', 'ikatan dinas', 'bond', 'service bond']),
            34 => self::def(34, 'AH', 'Bond Duration',            'ATTENDANCE & BOND',        'bond_duration',            false, 'number',   12,             'Bond duration in months', ['bond duration', 'durasi ikatan dinas', 'lama ikatan dinas', 'bond duration (months)']),
            35 => self::def(35, 'AI', 'Bond End Date',            'ATTENDANCE & BOND',        'bond_end_date',            false, 'date',     '10/08/2028',   'Format: DD/MM/YYYY', ['bond end date', 'tanggal selesai ikatan', 'tgl berakhir ikatan dinas', 'bond completion date']),
            36 => self::def(36, 'AJ', 'Bond Amount',              'ATTENDANCE & BOND',        'bond_amount',              false, 'number',   '',             'Bond value (IDR)', ['bond amount', 'nilai ikatan dinas', 'biaya ikatan dinas', 'nominal ikatan dinas', 'bond amount (idr)', 'bond_amount_idr', 'bond amount idr', 'bond cost']),
            37 => self::def(37, 'AK', 'Score (L1) - Course',      'EVALUATION & COMPLETION',  'l1_program',               false, 'string',   'Very Good',    'Reaction evaluation rating', ['score (l1) - course', 'score (l1)', 'score l1', 'program', 'l1 program', 'l1', 'evaluasi l1', 'level 1', 'reaction evaluation', 'l1 score', 'nilai l1']),
            38 => self::def(38, 'AL', 'Score (L2)',               'EVALUATION & COMPLETION',  'assessment_score',         false, 'number',   85,             'Post-test numerical score (0-100)', ['score (l2)', 'score l2', 'assessment score', 'post test score', 'nilai post test', 'l2 numerical', 'assessment score (l2)', 'post test numerical', 'l2 numeric', 'l2 post test numerical', 'nilai assessment']),
            39 => self::def(39, 'AM', 'L2 Status',                'EVALUATION & COMPLETION',  'l2_verbal',                false, 'string',   'Passed',       'Post-test status (Passed / Failed)', ['l2 status', 'status l2', 'l2 verbal', 'post test verbal', 'hasil post test', 'l2 post test verbal', 'l2 result']),
            40 => self::def(40, 'AN', 'Score (L3)',               'EVALUATION & COMPLETION',  'l3_numerical',             false, 'number',   90.0,           'Project implementation score (0-100)', ['score (l3)', 'score l3', 'l3 numerical', 'nilai project', 'project score', 'l3 project numerical', 'project numerical', 'l3 numeric']),
            41 => self::def(41, 'AO', 'L3 Status',                'EVALUATION & COMPLETION',  'l3_verbal',                false, 'string',   'Implemented successfully', 'Behavior / project result status', ['l3 status', 'status l3', 'l3 verbal', 'project verbal', 'hasil project', 'l3 project verbal', 'l3 result']),
            42 => self::def(42, 'AP', 'Certificate Availability', 'CERTIFICATE DETAILS',      'certificate_availability', false, 'string',   'Yes',          'Yes / No availability of certificate', ['certificate availability', 'ketersediaan sertifikat', 'ada sertifikat', 'certificate_availability', 'certificate', 'sertifikat', 'certificate status', 'ada sertifikat?']),
            43 => self::def(43, 'AQ', 'Certificate Institution',  'CERTIFICATE DETAILS',      'certificate_institution',  false, 'string',   'BNSP / Kemnaker', 'Issuing institution for external certificate', ['certificate institution', 'lembaga sertifikat', 'certificate_institution', 'penerbit sertifikat', 'issuing institution']),
            44 => self::def(44, 'AR', 'Certificate Issued Date',  'CERTIFICATE DETAILS',      'certificate_issued_date',  false, 'date',     '10/08/2026',   'Format: DD/MM/YYYY', ['certificate issued date', 'tanggal terbit sertifikat', 'tgl terbit sertifikat', 'certificate_issued_date', 'issue date']),
            45 => self::def(45, 'AS', 'Certificate Expired Date', 'CERTIFICATE DETAILS',      'certificate_expired_date', false, 'date',     '10/08/2029',   'Format: DD/MM/YYYY', ['certificate expired date', 'tanggal kedaluwarsa sertifikat', 'tgl expired sertifikat', 'certificate_expired_date', 'expiry date', 'expired date']),
            46 => self::def(46, 'AT', 'Person in Charge (PIC)',   'CLOSING & VERIFICATION',   'pic',                      false, 'string',   'HRDC Team',    'Person in charge / HR training admin', ['person in charge (pic)', 'person in charge', 'pic', 'penanggung jawab', 'admin pic', 'pic hrdc', 'pic training', 'pic admin']),
        ];
    }

    /**
     * Legacy 48-column definition array with leading Status and No columns.
     */
    public static function legacy48(): array
    {
        return [
            1  => self::def(1,  'A',  'Status',                   'GENERAL',                  'status',                   false, 'string',   'Valid',        'Validation status (system evaluated)', ['status', 'status validasi', 'system status', 'validation status', 'row status', 'stat']),
            2  => self::def(2,  'B',  'No',                       'GENERAL',                  'row_no',                   false, 'integer',  1,              'Sequence number for preview', ['no', 'no.', 'nomor', 'row no', 'seq', '#']),
            3  => self::def(3,  'C',  'Employee ID',              'JOB INFORMATION',          'employee_id',              true,  'string',   '104921',       'Employee NIP / ID (Mandatory)', ['employee id', 'emp id', 'id emp', 'nip', 'nik', 'id', 'employee_id', 'no pegawai', 'employee no', 'emp no', 'employee number', 'nomor pegawai', 'id employee', 'no. pegawai', 'nip / id', 'id/nip', 'nip/id', 'id karyawan']),
            4  => self::def(4,  'D',  'Employee Name',            'JOB INFORMATION',          'name',                     false, 'string',   'Budi Santoso', 'Participant name (populated from users)', ['employee name', 'name', 'nama', 'participant name', 'nama peserta', 'nama karyawan', 'peserta', 'full name', 'fullname', 'nama lengkap', 'employee_name', 'peserta training']),
            5  => self::def(5,  'E',  'Grade',                    'JOB INFORMATION',          'grade',                    false, 'string',   '4A',           'Employee grade (populated from users)', ['grade', 'golongan', 'level grade', 'employee grade', 'jobgrade', 'job grade', 'job_grade', 'pangkat']),
            6  => self::def(6,  'F',  'Job Position',             'JOB INFORMATION',          'job_position',             false, 'string',   'Operations Specialist', 'Job position (populated from users)', ['job position', 'position', 'jabatan', 'posisi', 'job_position', 'job title', 'jobposition', 'position_title', 'posisi jabatan']),
            7  => self::def(7,  'G',  'Team',                     'JOB INFORMATION',          'team',                     false, 'string',   'Rolling Mill 1', 'Team (populated from users)', ['team', 'tim', 'unit', 'regu', 'seksi', 'work team']),
            8  => self::def(8,  'H',  'Department',               'JOB INFORMATION',          'department',               false, 'string',   'Maintenance Department', 'Department (populated from users)', ['department', 'dept', 'departemen', 'departement', 'divisi', 'department name']),
            9  => self::def(9,  'I',  'Company',                  'JOB INFORMATION',          'company',                  false, 'string',   'PT Krakatau Posco', 'Company name (Mismatch is warning)', ['company', 'perusahaan', 'pt', 'entity', 'company name', 'legal entity', 'company_name', 'nama perusahaan', 'nama pt', 'organization', 'instansi', 'unit kerja']),
            10 => self::def(10, 'J',  'Course Title',             'TRAINING INFORMATION',     'course_name',              true,  'string',   'High Voltage Safety & Operation', 'Training course title (Mandatory)', ['course title', 'course name', 'course', 'nama course', 'training name', 'nama training', 'title', 'judul training', 'training title', 'nama pelatihan', 'topik training', 'judul kursus', 'nama kursus', 'course_title', 'course_name']),
            11 => self::def(11, 'K',  'Sub Course Title',         'TRAINING INFORMATION',     'sub_course_name',          false, 'string',   'Switchgear Troubleshooting Module', 'Sub course or module title', ['sub course title', 'sub course name', 'sub_course_name', 'sub course', 'module', 'modul', 'sub training', 'topic', 'nama sub course']),
            12 => self::def(12, 'L',  'Type',                     'TRAINING INFORMATION',     'type',                     false, 'string',   'In-House',     'In-House, Public, Certification', ['type', 'tipe', 'training type', 'tipe training', 'jenis training', 'tipe pelatihan']),
            13 => self::def(13, 'M',  'Category',                 'TRAINING INFORMATION',     'category',                 false, 'string',   'Technical Competency', 'Training category', ['category', 'kategori', 'training category', 'kategori training', 'kategori pelatihan']),
            14 => self::def(14, 'N',  'Level',                    'TRAINING INFORMATION',     'level',                    false, 'string',   'Intermediate', 'Basic, Intermediate, Advanced', ['level', 'tingkat', 'level training', 'jenjang']),
            15 => self::def(15, 'O',  'Batch',                    'TRAINING INFORMATION',     'batch',                    false, 'string',   'Batch 1',      'Training batch or wave', ['batch', 'angkatan', 'gelombang', 'wave']),
            16 => self::def(16, 'P',  'Main Classification',      'CLASSIFICATION',           'main_classification',      false, 'string',   'Basic & Common Duties', 'Main classification grouping', ['main classification', 'klasifikasi utama', 'classification', 'klasifikasi', 'main category']),
            17 => self::def(17, 'Q',  'Sub Classification',       'CLASSIFICATION',           'sub_classification',       false, 'string',   'Plant Maintenance', 'Sub classification grouping', ['sub classification', 'sub klasifikasi', 'sub_classification']),
            18 => self::def(18, 'R',  'Delivery Method',          'DELIVERY',                 'delivery_method',          false, 'string',   'Offline',      'Offline / Online / Blended', ['delivery method', 'metode delivery', 'metode pembelajaran', 'metode training', 'delivery_method', 'method']),
            19 => self::def(19, 'S',  'Sub Delivery Method',      'DELIVERY',                 'sub_delivery_method',      false, 'string',   'On-site',      'Sub delivery technique', ['sub delivery method', 'sub_delivery_method', 'sub metode delivery', 'sub method']),
            20 => self::def(20, 'T',  'Provider',                 'PROVIDER & INSTRUCTOR',    'provider',                 false, 'string',   'External',     'Internal / External provider type', ['provider', 'penyedia', 'tipe provider', 'provider type', 'jenis provider', 'penyelenggara', 'tipe penyelenggara', 'training provider', 'provider pelatihan']),
            21 => self::def(21, 'U',  'Sub Provider',             'PROVIDER & INSTRUCTOR',    'sub_provider',             false, 'string',   'Vendor',       'Sub provider category', ['sub provider', 'sub_provider', 'sub penyedia', 'kategori provider', 'sub vendor', 'sub penyelenggara', 'sub_penyelenggara']),
            22 => self::def(22, 'V',  'Provider Name',            'PROVIDER & INSTRUCTOR',    'provider_name',            false, 'string',   'PT Schneider Indonesia', 'Institution or vendor name', ['provider name', 'nama provider', 'lembaga training', 'vendor name', 'nama vendor', 'lembaga', 'vendor', 'nama lembaga', 'nama penyelenggara', 'provider_name', 'partner']),
            23 => self::def(23, 'W',  'Venue',                    'PROVIDER & INSTRUCTOR',    'venue',                    false, 'string',   'Auditorium Hall B', 'Training venue / location', ['venue', 'lokasi', 'tempat training', 'location', 'ruangan', 'tempat', 'ruang', 'room']),
            24 => self::def(24, 'X',  'Instructor',               'PROVIDER & INSTRUCTOR',    'instructor',               false, 'string',   'Ir. Gunawan, M.T.', 'Trainer / instructor name', ['instructor', 'trainer', 'instruktur', 'pengajar', 'facilitator', 'narasumber', 'trainer name', 'nama trainer', 'nama instruktur']),
            25 => self::def(25, 'Y',  'Learning Hours',           'LEARNING & PROGRESS',      'learning_hours',           false, 'number',   8.0,            'Credited learning hours', ['learning hours', 'learning (hours)', 'jam pelajaran', 'jp', 'hours', 'learning_hours', 'total hours', 'durasi jam', 'credited hours', 'jam training', 'learning hour']),
            26 => self::def(26, 'Z',  '% Progress',               'LEARNING & PROGRESS',      'progress',                 false, 'number',   100,            '0 to 100 (%)', ['% progress', 'progress %', 'progress', 'kemajuan', 'persentase progress', 'progres', 'progress percentage', 'persentase']),
            27 => self::def(27, 'AA', 'Start Date',               'SCHEDULE & REQUIREMENT',   'start_date',               false, 'date',     '10/08/2026',   'Format: DD/MM/YYYY', ['start date', 'tanggal mulai', 'tgl mulai', 'start_date', 'begin date', 'start']),
            28 => self::def(28, 'AB', 'End Date',                 'SCHEDULE & REQUIREMENT',   'end_date',                 false, 'date',     '10/08/2026',   'Format: DD/MM/YYYY', ['end date', 'finish date', 'tanggal selesai', 'tgl selesai', 'end_date', 'finish_date', 'completion date', 'finish', 'end']),
            29 => self::def(29, 'AC', 'Start Time',               'SCHEDULE & REQUIREMENT',   'start_time',               false, 'string',   '08:00:00',     'Format: HH:mm or HH:mm:ss', ['start time', 'jam mulai', 'waktu mulai', 'start_time', 'time start']),
            30 => self::def(30, 'AD', 'End Time',                 'SCHEDULE & REQUIREMENT',   'end_time',                 false, 'string',   '17:00:00',     'Format: HH:mm or HH:mm:ss', ['end time', 'jam selesai', 'waktu selesai', 'end_time', 'finish time', 'time end']),
            31 => self::def(31, 'AE', 'Record Month',             'SCHEDULE & REQUIREMENT',   'month',                    false, 'string',   'August',       'Derived month name', ['record month', 'month', 'bulan', 'periode bulan']),
            32 => self::def(32, 'AF', 'Requirement',              'SCHEDULE & REQUIREMENT',   'requirement',              false, 'string',   'Mandatory',    'Mandatory / Elective', ['requirement', 'kebutuhan', 'sifat training', 'training requirement']),
            33 => self::def(33, 'AG', 'Attendance',               'ATTENDANCE & BOND',        'attendance',               false, 'boolean',  'Yes',          'Yes / No', ['attendance', 'kehadiran', 'presensi', 'status kehadiran', 'hadir']),
            34 => self::def(34, 'AH', 'Attendance Reason',        'ATTENDANCE & BOND',        'attendance_reason',        false, 'string',   'Present in all sessions', 'Attendance justification if absent', ['attendance reason', 'reason', 'alasan', 'alasan ketidakhadiran', 'justification']),
            35 => self::def(35, 'AI', 'Bond Status',              'ATTENDANCE & BOND',        'bond_status',              false, 'string',   'No',           'Yes / No', ['bond status', 'status ikatan dinas', 'ikatan dinas', 'bond', 'service bond']),
            36 => self::def(36, 'AJ', 'Bond Duration',            'ATTENDANCE & BOND',        'bond_duration',            false, 'number',   12,             'Bond duration in months', ['bond duration', 'durasi ikatan dinas', 'lama ikatan dinas', 'bond duration (months)']),
            37 => self::def(37, 'AK', 'Bond End Date',            'ATTENDANCE & BOND',        'bond_end_date',            false, 'date',     '10/08/2028',   'Format: DD/MM/YYYY', ['bond end date', 'tanggal selesai ikatan', 'tgl berakhir ikatan dinas', 'bond completion date']),
            38 => self::def(38, 'AL', 'Bond Amount',              'ATTENDANCE & BOND',        'bond_amount',              false, 'number',   '',             'Bond value (IDR)', ['bond amount', 'nilai ikatan dinas', 'biaya ikatan dinas', 'nominal ikatan dinas', 'bond amount (idr)', 'bond_amount_idr', 'bond amount idr', 'bond cost']),
            39 => self::def(39, 'AM', 'Score (L1) - Course',      'EVALUATION & COMPLETION',  'l1_program',               false, 'string',   'Very Good',    'Reaction evaluation rating', ['score (l1) - course', 'score (l1)', 'score l1', 'program', 'l1 program', 'l1', 'evaluasi l1', 'level 1', 'reaction evaluation', 'l1 score', 'nilai l1']),
            40 => self::def(40, 'AN', 'Score (L2)',               'EVALUATION & COMPLETION',  'assessment_score',         false, 'number',   85,             'Post-test numerical score (0-100)', ['score (l2)', 'score l2', 'assessment score', 'post test score', 'nilai post test', 'l2 numerical', 'assessment score (l2)', 'post test numerical', 'l2 numeric', 'l2 post test numerical', 'nilai assessment']),
            41 => self::def(41, 'AO', 'L2 Status',                'EVALUATION & COMPLETION',  'l2_verbal',                false, 'string',   'Passed',       'Post-test status (Passed / Failed)', ['l2 status', 'status l2', 'l2 verbal', 'post test verbal', 'hasil post test', 'l2 post test verbal', 'l2 result']),
            42 => self::def(42, 'AP', 'Score (L3)',               'EVALUATION & COMPLETION',  'l3_numerical',             false, 'number',   90.0,           'Project implementation score (0-100)', ['score (l3)', 'score l3', 'l3 numerical', 'nilai project', 'project score', 'l3 project numerical', 'project numerical', 'l3 numeric']),
            43 => self::def(43, 'AQ', 'L3 Status',                'EVALUATION & COMPLETION',  'l3_verbal',                false, 'string',   'Implemented successfully', 'Behavior / project result status', ['l3 status', 'status l3', 'l3 verbal', 'project verbal', 'hasil project', 'l3 project verbal', 'l3 result']),
            44 => self::def(44, 'AR', 'Certificate Availability', 'CERTIFICATE DETAILS',      'certificate_availability', false, 'string',   'Yes',          'Yes / No availability of certificate', ['certificate availability', 'ketersediaan sertifikat', 'ada sertifikat', 'certificate_availability', 'certificate', 'sertifikat', 'certificate status', 'ada sertifikat?']),
            45 => self::def(45, 'AS', 'Certificate Institution',  'CERTIFICATE DETAILS',      'certificate_institution',  false, 'string',   'BNSP / Kemnaker', 'Issuing institution for external certificate', ['certificate institution', 'lembaga sertifikat', 'certificate_institution', 'penerbit sertifikat', 'issuing institution']),
            46 => self::def(46, 'AT', 'Certificate Issued Date',  'CERTIFICATE DETAILS',      'certificate_issued_date',  false, 'date',     '10/08/2026',   'Format: DD/MM/YYYY', ['certificate issued date', 'tanggal terbit sertifikat', 'tgl terbit sertifikat', 'certificate_issued_date', 'issue date']),
            47 => self::def(47, 'AU', 'Certificate Expired Date', 'CERTIFICATE DETAILS',      'certificate_expired_date', false, 'date',     '10/08/2029',   'Format: DD/MM/YYYY', ['certificate expired date', 'tanggal kedaluwarsa sertifikat', 'tgl expired sertifikat', 'certificate_expired_date', 'expiry date', 'expired date']),
            48 => self::def(48, 'AV', 'Person in Charge (PIC)',   'CLOSING & VERIFICATION',   'pic',                      false, 'string',   'HRDC Team',    'Person in charge / HR training admin', ['person in charge (pic)', 'person in charge', 'pic', 'penanggung jawab', 'admin pic', 'pic hrdc', 'pic training', 'pic admin']),
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
            'warning_on_empty' => self::isWarningOnEmpty($key),
            'type'       => $type,
            'example'    => $example,
            'note'       => $note,
            'aliases'    => $aliases,
        ];
    }
}
