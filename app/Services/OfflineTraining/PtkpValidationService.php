<?php

namespace App\Services\OfflineTraining;

use App\Services\OfflineTraining\ExcelDateTimeParser;

use App\Models\OfflineTraining;
use App\Models\OfflineTrainingUser;
use App\Models\User;
use Carbon\Carbon;
use PhpOffice\PhpSpreadsheet\Shared\Date as ExcelDate;
use Throwable;

class PtkpValidationService
{
    public const STATUS_VALID = 'VALID';
    public const STATUS_WARNING = 'WARNING';
    public const STATUS_DUPLICATE = 'DUPLICATE';
    public const STATUS_INVALID = 'INVALID';
    public const STATUS_INVALID_COMPANY = 'INVALID COMPANY';

    /**
     * Batch lookup users by Employee ID (id_emp).
     *
     * @param array<string> $employeeIds
     * @return array<string, \Illuminate\Database\Eloquent\Collection<int, User>>
     */
    public function batchLookupUsers(array $employeeIds): array
    {
        $normalizedIds = [];
        foreach ($employeeIds as $id) {
            $trimmed = trim((string) $id);
            if ($trimmed !== '') {
                $key = strtolower($trimmed);
                $normalizedIds[$key] = $trimmed;

                // For purely numeric IDs with < 6 digits, also query 6-digit zero-padded variant
                if (ctype_digit($trimmed) && strlen($trimmed) < 6) {
                    $padded = str_pad($trimmed, 6, '0', STR_PAD_LEFT);
                    $normalizedIds[strtolower($padded)] = $padded;
                }
            }
        }

        if (empty($normalizedIds)) {
            return [];
        }

        $users = User::whereIn('id_emp', array_values($normalizedIds))
            ->get(['id', 'id_emp', 'name', 'jobgrade', 'jobposition', 'team', 'department', 'company_name']);

        $lookup = [];
        foreach ($users as $user) {
            $key = strtolower(trim((string) $user->id_emp));
            if (!isset($lookup[$key])) {
                $lookup[$key] = collect();
            }
            $lookup[$key]->push($user);

            // Also map unpadded variant if numeric and has leading zeros
            if (ctype_digit($key)) {
                $unpadded = ltrim($key, '0');
                if ($unpadded !== '' && $unpadded !== $key) {
                    if (!isset($lookup[$unpadded])) {
                        $lookup[$unpadded] = collect();
                    }
                    $lookup[$unpadded]->push($user);
                }
            }
        }

        return $lookup;
    }

    /**
     * Match participant with LMS user.
     *
     * @param string|null $employeeId
     * @param array<string, \Illuminate\Database\Eloquent\Collection<int, User>> $userLookup
     * @return array{
     *     user: User|null,
     *     status: string,
     *     errors: array<string>,
     *     warnings: array<string>,
     *     is_ambiguous: bool,
     *     is_not_found: bool
     * }
     */
    public function matchUser(?string $employeeId, array $userLookup): array
    {
        $rawId = trim((string) $employeeId);
        if ($rawId === '') {
            return [
                'user'         => null,
                'status'       => self::STATUS_INVALID,
                'errors'       => ['Employee ID is empty or missing.'],
                'warnings'     => [],
                'is_ambiguous' => false,
                'is_not_found' => true,
            ];
        }

        $lookupKey = strtolower($rawId);
        $matches = $userLookup[$lookupKey] ?? collect();

        if ($matches->isEmpty()) {
            return [
                'user'         => null,
                'status'       => self::STATUS_INVALID,
                'errors'       => ["Employee ID '{$rawId}' was not found in LMS users database."],
                'warnings'     => [],
                'is_ambiguous' => false,
                'is_not_found' => true,
            ];
        }

        if ($matches->count() > 1) {
            return [
                'user'         => null,
                'status'       => self::STATUS_INVALID,
                'errors'       => ["Employee ID '{$rawId}' matches multiple users ({$matches->count()} accounts found). Ambiguous identifier."],
                'warnings'     => [],
                'is_ambiguous' => true,
                'is_not_found' => false,
            ];
        }

        $user = $matches->first();

        return [
            'user'         => $user,
            'status'       => self::STATUS_VALID,
            'errors'       => [],
            'warnings'     => [],
            'is_ambiguous' => false,
            'is_not_found' => false,
        ];
    }

    /**
     * Validate an individual row and assign deterministic status.
     *
     * Priority:
     * 1. INVALID (Employee ID missing/not found/ambiguous OR Course Title empty)
     * 2. DUPLICATE (In file or in database)
     * 3. INVALID COMPANY / WARNING (Company mismatch or other warning)
     * 4. VALID
     *
     * @param array<string, mixed> $normalized
     * @param User|null $user
     * @param array{is_duplicate: bool, duplicate_reason: string} $duplicateInfo
     * @return array{
     *     status: string,
     *     is_blocking: bool,
     *     messages: array<string>,
     *     warnings: array<string>,
     *     errors: array<string>,
     *     company_mismatch: bool
     * }
     */
    public function evaluateRowStatus(array $normalized, ?User $user, array $duplicateInfo, array $matchResult = []): array
    {
        $errors = [];
        $warnings = [];
        $messages = [];
        $isCompanyMismatch = false;

        // 1. Employee ID check
        $empId = trim((string) ($normalized['employee_id'] ?? ''));
        if ($empId === '') {
            $errors[] = 'Employee ID is required.';
        } elseif (!$user) {
            if (!empty($matchResult['errors'])) {
                $errors = array_merge($errors, $matchResult['errors']);
            } else {
                $errors[] = "Employee ID '{$empId}' not found or ambiguous in LMS users database.";
            }
        }

        // 2. Course Title check (Mandatory)
        $courseTitle = trim((string) ($normalized['course_name'] ?? ''));
        if ($courseTitle === '') {
            $errors[] = 'Course Title is required and cannot be empty.';
        }

        // 3. Company mismatch check (Non-blocking for PTKP)
        if ($user && !empty($user->company_name) && !empty($normalized['company'])) {
            $userCompany = trim(mb_strtolower($user->company_name, 'UTF-8'));
            $excelCompany = trim(mb_strtolower($normalized['company'], 'UTF-8'));

            if ($userCompany !== $excelCompany) {
                $isCompanyMismatch = true;
                $warnings[] = "Company mismatch: Excel has '{$normalized['company']}' while user record has '{$user->company_name}'.";
            }
        }

        // 4. Date and Time Format Validations
        $rowNum = $normalized['excel_row'] ?? null;
        $empId = trim((string) ($normalized['employee_id'] ?? ''));
        $ctx = ($rowNum ? "Excel Row {$rowNum}" : "") . ($empId !== '' ? " (Employee ID: '{$empId}')" : "");
        $ctx = $ctx ? "{$ctx}: " : "";

        if (!empty($normalized['start_time_invalid'])) {
            $errors[] = "{$ctx}Invalid Start Time format: '{$normalized['raw_start_time']}'. " . ($normalized['start_time_error'] ?? 'Expected HH:mm or HH:mm:ss (00:00:00 - 23:59:59).');
        }
        if (!empty($normalized['end_time_invalid'])) {
            $errors[] = "{$ctx}Invalid End Time format: '{$normalized['raw_end_time']}'. " . ($normalized['end_time_error'] ?? 'Expected HH:mm or HH:mm:ss (00:00:00 - 23:59:59).');
        }
        if (!empty($normalized['start_date_invalid'])) {
            $errors[] = "{$ctx}Invalid Start Date format: '{$normalized['raw_start_date']}'. " . ($normalized['start_date_error'] ?? 'Expected valid date (DD/MM/YYYY).');
        }
        if (!empty($normalized['end_date_invalid'])) {
            $errors[] = "{$ctx}Invalid End Date format: '{$normalized['raw_end_date']}'. " . ($normalized['end_date_error'] ?? 'Expected valid date (DD/MM/YYYY).');
        }
        if (!empty($normalized['bond_end_date_invalid'])) {
            $errors[] = "{$ctx}Invalid Bond End Date format: '{$normalized['raw_bond_end_date']}'. " . ($normalized['bond_end_date_error'] ?? 'Expected valid date (DD/MM/YYYY).');
        }
        if (!empty($normalized['certificate_issued_date_invalid'])) {
            $errors[] = "{$ctx}Invalid Certificate Issued Date format: '{$normalized['raw_certificate_issued_date']}'. " . ($normalized['certificate_issued_date_error'] ?? 'Expected valid date (DD/MM/YYYY).');
        }
        if (!empty($normalized['certificate_expired_date_invalid'])) {
            $errors[] = "{$ctx}Invalid Certificate Expired Date format: '{$normalized['raw_certificate_expired_date']}'. " . ($normalized['certificate_expired_date_error'] ?? 'Expected valid date (DD/MM/YYYY).');
        }

        // 5. Date sequence validations: Start Date cannot be after End Date when both are present
        if (!empty($normalized['start_date']) && !empty($normalized['finish_date'])) {
            if ($normalized['start_date'] > $normalized['finish_date']) {
                $errors[] = "{$ctx}Start Date ({$normalized['start_date']}) cannot be after End Date ({$normalized['finish_date']}).";
            }
        }

        // 6. Certificate sequence validation: Issued Date cannot be after Expired Date when both are present
        if (!empty($normalized['certificate_issued_date']) && !empty($normalized['certificate_expired_date'])) {
            if ($normalized['certificate_issued_date'] > $normalized['certificate_expired_date']) {
                $errors[] = "{$ctx}Certificate Issued Date ({$normalized['certificate_issued_date']}) cannot be after Certificate Expired Date ({$normalized['certificate_expired_date']}).";
            }
        }

        // Status Determination according to priority
        if (!empty($errors)) {
            $status = self::STATUS_INVALID;
            $isBlocking = true;
            $messages = array_merge($errors, $warnings);
        } elseif ($duplicateInfo['is_duplicate']) {
            $status = self::STATUS_DUPLICATE;
            $isBlocking = true;
            $messages[] = $duplicateInfo['duplicate_reason'];
            if (!empty($warnings)) {
                $messages = array_merge($messages, $warnings);
            }
        } elseif ($isCompanyMismatch) {
            $status = self::STATUS_INVALID_COMPANY;
            $isBlocking = false;
            $messages = $warnings;
        } elseif (!empty($warnings)) {
            $status = self::STATUS_WARNING;
            $isBlocking = false;
            $messages = $warnings;
        } else {
            $status = self::STATUS_VALID;
            $isBlocking = false;
            $messages[] = 'Row is valid and ready for import.';
        }

        return [
            'status'             => $status,
            'is_blocking'        => $isBlocking,
            'messages'           => $messages,
            'warnings'           => $warnings,
            'errors'             => $errors,
            'company_mismatch'   => $isCompanyMismatch,
            'duplicate_source'   => $duplicateInfo['duplicate_source'] ?? null,
            'duplicate_type'     => $duplicateInfo['duplicate_type'] ?? null,
            'matched_record_id'  => $duplicateInfo['matched_record_id'] ?? null,
            'original_excel_row' => $duplicateInfo['original_excel_row'] ?? null,
            'matched_fields'     => $duplicateInfo['matched_fields'] ?? [],
        ];
    }

    /**
     * Get canonical column keys for Full Row Duplicate Detection.
     * Exactly 42 canonical columns organized in 7 logical sections:
     * - Employee Information (7)
     * - Training Information (6)
     * - Classification (2)
     * - Delivery & Provider (5)
     * - Learning Details (9)
     * - Assessment, Attendance & Bond (12)
     * - Person in Charge (1)
     *
     * Note: 'No', certificate columns, provider, and sub_provider are strictly excluded.
     *
     * @return array<int, string>
     */
    public static function getFullRowComparisonColumns(): array
    {
        return [
            // Employee Information (7)
            'employee_id',
            'name',
            'grade',
            'job_position',
            'team',
            'department',
            'company',

            // Training Information (6)
            'course_name',
            'sub_course_name',
            'type',
            'category',
            'level',
            'batch',

            // Classification (2)
            'main_classification',
            'sub_classification',

            // Delivery & Provider (5)
            'delivery_method',
            'sub_delivery_method',
            'provider_name',
            'venue',
            'instructor',

            // Learning Details (9)
            'course_duration',
            'learning_hours',
            'progress',
            'progress_status',
            'start_date',
            'end_date',
            'start_time',
            'end_time',
            'month',

            // Assessment, Attendance & Bond (12)
            'requirement',
            'attendance',
            'attendance_reason',
            'bond_status',
            'bond_duration',
            'bond_end_date',
            'bond_amount',
            'l1_program',
            'assessment_score',
            'l2_verbal',
            'l3_numerical',
            'l3_verbal',

            // Person in Charge (1)
            'pic',
        ];
    }

    /**
     * Get columns that are physically stored in the database (offline_trainings and offline_training_users).
     * Exactly 40 columns.
     *
     * @return array<int, string>
     */
    public static function getDatabaseStoredColumns(): array
    {
        return [
            'employee_id',
            'name',
            'grade',
            'job_position',
            'team',
            'department',
            'company',
            'course_name',
            'sub_course_name',
            'type',
            'category',
            'level',
            'batch',
            'main_classification',
            'sub_classification',
            'delivery_method',
            'sub_delivery_method',
            'provider_name',
            'venue',
            'instructor',
            'course_duration',
            'learning_hours',
            'progress',
            'start_date',
            'end_date',
            'start_time',
            'end_time',
            'requirement',
            'attendance',
            'attendance_reason',
            'bond_status',
            'bond_duration',
            'bond_end_date',
            'bond_amount',
            'l1_program',
            'assessment_score',
            'l2_verbal',
            'l3_numerical',
            'l3_verbal',
            'pic',
        ];
    }

    /**
     * Get columns that are NOT stored in the database (virtual / spreadsheet only).
     * Exactly 2 columns.
     *
     * @return array<int, string>
     */
    public static function getUnstoredDatabaseColumns(): array
    {
        return [
            'progress_status',
            'month',
        ];
    }

    /**
     * Resolve a column value from row data with support for key aliases.
     */
    public function resolveColumnValue(array $data, string $col): mixed
    {
        if (array_key_exists($col, $data) && $data[$col] !== null) {
            return $data[$col];
        }

        switch ($col) {
            case 'course_duration':
                return $data['duration_seconds'] ?? ($data['course_duration'] ?? null);
            case 'duration_seconds':
                return $data['course_duration'] ?? ($data['duration_seconds'] ?? null);
            case 'progress_status':
                return $data['progress_status'] ?? null;
            case 'end_date':
                return $data['finish_date'] ?? ($data['end_date'] ?? null);
            case 'finish_date':
                return $data['end_date'] ?? ($data['finish_date'] ?? null);
            case 'bond_amount':
                return $data['bond_amount_idr'] ?? ($data['bond_amount'] ?? null);
            case 'bond_amount_idr':
                return $data['bond_amount'] ?? ($data['bond_amount_idr'] ?? null);
            case 'assessment_score':
                return $data['l2_numerical'] ?? ($data['assessment_score'] ?? null);
            case 'l2_numerical':
                return $data['assessment_score'] ?? ($data['l2_numerical'] ?? null);
            case 'employee_id':
                return $data['id_emp'] ?? ($data['employee_id'] ?? null);
            case 'name':
                return $data['employee_name'] ?? ($data['name'] ?? null);
            case 'grade':
                return $data['jobgrade'] ?? ($data['grade'] ?? null);
            case 'job_position':
                return $data['jobposition'] ?? ($data['job_position'] ?? null);
            case 'company':
                return $data['company_name'] ?? ($data['company'] ?? null);
            case 'course_name':
                return $data['course_title'] ?? ($data['course_name'] ?? null);
            case 'sub_course_name':
                return $data['sub_course_title'] ?? ($data['sub_course_name'] ?? null);
            case 'month':
                return $data['record_month'] ?? ($data['month'] ?? null);
            case 'certificate_availability':
                return $data['certificate'] ?? ($data['certificate_availability'] ?? null);
            case 'certificate':
                return $data['certificate_availability'] ?? ($data['certificate'] ?? null);
            default:
                return $data[$col] ?? null;
        }
    }

    /**
     * Normalize an individual value according to Full Row Duplicate Detection specifications.
     */
    public function normalizeValueForComparison(string $field, mixed $val, array $context = []): string
    {
        if ($val === null) {
            return '';
        }

        $str = trim((string) $val);
        $str = (string) preg_replace('/\s+/', ' ', $str);
        if ($str === '' || $str === '-' || strtolower($str) === 'null') {
            return '';
        }

        // Date columns: canonical YYYY-MM-DD
        if (in_array($field, ['start_date', 'end_date', 'finish_date', 'bond_end_date', 'certificate_issued_date', 'certificate_expired_date'], true)) {
            $parsed = ExcelDateTimeParser::parseDateResult($val);
            if ($parsed['valid'] && $parsed['value'] !== null) {
                return $parsed['value'];
            }
            if (preg_match('/^(\d{4})[-\/](\d{1,2})[-\/](\d{1,2})/', $str, $m)) {
                return sprintf('%04d-%02d-%02d', (int) $m[1], (int) $m[2], (int) $m[3]);
            }
            if (preg_match('/^(\d{1,2})[-\/](\d{1,2})[-\/](\d{4})/', $str, $m)) {
                return sprintf('%04d-%02d-%02d', (int) $m[3], (int) $m[2], (int) $m[1]);
            }
            return mb_strtolower($str, 'UTF-8');
        }

        // Time columns: canonical HH:MM
        if (in_array($field, ['start_time', 'end_time'], true)) {
            $parsed = ExcelDateTimeParser::parseTimeResult($val);
            if ($parsed['valid'] && $parsed['value'] !== null) {
                return substr($parsed['value'], 0, 5);
            }
            if (preg_match('/^(\d{1,2}):(\d{2})/', $str, $m)) {
                return sprintf('%02d:%02d', (int) $m[1], (int) $m[2]);
            }
            return mb_strtolower($str, 'UTF-8');
        }

        // Course duration: canonical duration in seconds (e.g. 28800 or "08:00:00" -> "28800")
        if (in_array($field, ['course_duration', 'duration_seconds'], true)) {
            $seconds = $this->parseDurationToSeconds($val);
            if ($seconds !== null) {
                return (string) $seconds;
            }
            if (is_numeric($str)) {
                return (string) (int) $str;
            }
            return mb_strtolower($str, 'UTF-8');
        }

        // Decimal and number columns: normalized representation (1, 1.0, 1.00 -> 1)
        if (in_array($field, ['learning_hours', 'progress', 'bond_duration', 'bond_amount', 'bond_amount_idr', 'assessment_score', 'l2_numerical', 'l3_numerical'], true)) {
            if (is_numeric($str)) {
                $float = (float) $str;
                return (string) (round($float, 4) == (int) round($float, 4) ? (int) round($float, 4) : round($float, 4));
            }
            return mb_strtolower($str, 'UTF-8');
        }

        // Boolean columns: canonical 1 / 0
        if (in_array($field, ['attendance', 'counted_corporate_kpi'], true)) {
            $lower = strtolower($str);
            if (in_array($lower, ['yes', '1', 'true', 'hadir', 'y'], true)) {
                return '1';
            }
            if (in_array($lower, ['no', '0', 'false', 'tidak', 'n'], true)) {
                return '0';
            }
            return $lower;
        }

        // Month column: canonical month number 1..12 or standardized name
        if ($field === 'month') {
            return $this->normalizeMonth($str, $context['start_date'] ?? null);
        }

        // Requirement: mandatory or non mandatory
        if ($field === 'requirement') {
            $lower = strtolower($str);
            if ($lower === 'mandatory') return 'mandatory';
            if ($lower === 'non mandatory' || $lower === 'non-mandatory') return 'non mandatory';
            return $lower;
        }

        // Employee ID: strict string with leading zero preservation
        if ($field === 'employee_id') {
            return mb_strtolower($str, 'UTF-8');
        }

        // General text fields: trimmed and lowercased
        return mb_strtolower($str, 'UTF-8');
    }

    /**
     * Normalize month value (numeric 1-12, English name, or Indonesian name).
     */
    public function normalizeMonth(?string $month, ?string $startDate = null): string
    {
        $m = trim((string) $month);
        if ($m === '' || $m === '-' || strtolower($m) === 'null') {
            if ($startDate && strtotime($startDate)) {
                return (string) (int) date('n', strtotime($startDate));
            }
            return '';
        }

        if (is_numeric($m)) {
            $num = (int) $m;
            if ($num >= 1 && $num <= 12) {
                return (string) $num;
            }
        }

        $monthNames = [
            'january' => '1', 'jan' => '1', 'januari' => '1',
            'february' => '2', 'feb' => '2', 'februari' => '2',
            'march' => '3', 'mar' => '3', 'maret' => '3',
            'april' => '4', 'apr' => '4',
            'may' => '5', 'mei' => '5',
            'june' => '6', 'jun' => '6', 'juni' => '6',
            'july' => '7', 'jul' => '7', 'juli' => '7',
            'august' => '8', 'aug' => '8', 'agustus' => '8',
            'september' => '9', 'sep' => '9',
            'october' => '10', 'oct' => '10', 'oktober' => '10',
            'november' => '11', 'nov' => '11',
            'december' => '12', 'dec' => '12', 'desember' => '12',
        ];

        $clean = strtolower($m);
        return $monthNames[$clean] ?? $clean;
    }

    /**
     * Build SHA1 hash from comparison columns for fast indexing.
     * When $databaseColumnsOnly is true, only the 40 DB-stored columns are hashed.
     */
    public function buildFullRowHash(array $data, bool $databaseColumnsOnly = false): string
    {
        $cols = $databaseColumnsOnly ? self::getDatabaseStoredColumns() : self::getFullRowComparisonColumns();
        $canonical = [];
        foreach ($cols as $col) {
            $val = $this->resolveColumnValue($data, $col);
            $canonical[$col] = $this->normalizeValueForComparison($col, $val, $data);
        }

        return sha1(json_encode($canonical));
    }

    /**
     * Perform exact field-by-field comparison of two normalized records.
     *
     * Rules:
     * - When comparing two rows in the uploaded Excel file ($isDatabaseComparison = false):
     *   Strict comparison across all 42 canonical columns. Any difference makes is_match false.
     * - When comparing an imported row against an existing database record ($isDatabaseComparison = true):
     *   Only the 40 columns stored in the database are compared. The 2 unstored columns (progress_status, month)
     *   are recorded in unverified_fields metadata and do not block database duplicate detection.
     *   Database comparison strictly enforces Scenario 48: If the database record has empty/null for a stored column
     *   while the import file has a non-empty value, it CANNOT be assumed to match (marked as database_null_unverified).
     */
    public function compareFullRowValues(array $rowA, array $rowB, bool $isDatabaseComparison = false): array
    {
        $cols = self::getFullRowComparisonColumns();
        $matchedFields = [];
        $diffFields = [];
        $unverifiedFields = [];

        foreach ($cols as $col) {
            if ($isDatabaseComparison && in_array($col, self::getUnstoredDatabaseColumns(), true)) {
                $valA = $this->resolveColumnValue($rowA, $col);
                $normA = $this->normalizeValueForComparison($col, $valA, $rowA);
                $unverifiedFields[$col] = [
                    'val_a'  => $normA,
                    'reason' => 'not_stored_in_database',
                ];
                continue;
            }

            $valA = $this->resolveColumnValue($rowA, $col);
            $valB = $this->resolveColumnValue($rowB, $col);

            $normA = $this->normalizeValueForComparison($col, $valA, $rowA);
            $normB = $this->normalizeValueForComparison($col, $valB, $rowB);

            if ($normA === $normB) {
                $matchedFields[$col] = $normA;
            } else {
                if ($isDatabaseComparison) {
                    $diffFields[$col] = [
                        'val_a'  => $normA,
                        'val_b'  => $normB,
                        'reason' => ($normA !== '' && $normB === '') ? 'database_null_unverified' : 'value_mismatch',
                    ];
                } else {
                    $diffFields[$col] = [
                        'val_a'  => $normA,
                        'val_b'  => $normB,
                        'reason' => 'value_mismatch',
                    ];
                }
            }
        }

        return [
            'is_match'          => empty($diffFields),
            'matched_fields'    => $matchedFields,
            'diff_fields'       => $diffFields,
            'unverified_fields' => $unverifiedFields,
        ];
    }

    /**
     * Build training fingerprint to uniquely identify a training session.
     */
    public function buildTrainingFingerprint(array $data): string
    {
        $course = mb_strtolower(trim((string) ($data['course_name'] ?? '')), 'UTF-8');
        $batch = mb_strtolower(trim((string) ($data['batch'] ?? '')), 'UTF-8');
        $startDate = trim((string) ($data['start_date'] ?? ''));
        $finishDate = trim((string) ($data['finish_date'] ?? ($data['end_date'] ?? '')));
        $type = mb_strtolower(trim((string) ($data['type'] ?? '')), 'UTF-8');
        $venue = mb_strtolower(trim((string) ($data['venue'] ?? '')), 'UTF-8');

        return md5("{$course}|{$batch}|{$startDate}|{$finishDate}|{$type}|{$venue}");
    }

    /**
     * Normalize raw row data according to PTKP schema specifications.
     */
    public function normalizeRowData(array $raw): array
    {
        $normalized = [];

        // Retain System status & No from input (Status is optional in spreadsheet, No is preview seq)
        $normalized['status'] = isset($raw['status']) ? trim((string) $raw['status']) : null;
        $normalized['row_no'] = isset($raw['row_no']) ? (int) $raw['row_no'] : null;

        // Employee ID (Strict string preservation, never cast to int to preserve leading zero)
        $normalized['employee_id'] = isset($raw['employee_id']) ? trim((string) $raw['employee_id']) : '';

        // Identity fields (will be overridden with user table values if user is found)
        $normalized['name'] = isset($raw['name']) ? trim((string) $raw['name']) : '';
        $normalized['grade'] = isset($raw['grade']) ? trim((string) $raw['grade']) : null;
        $normalized['job_position'] = isset($raw['job_position']) ? trim((string) $raw['job_position']) : null;
        $normalized['team'] = isset($raw['team']) ? trim((string) $raw['team']) : null;
        $normalized['department'] = isset($raw['department']) ? trim((string) $raw['department']) : null;
        $normalized['company'] = isset($raw['company']) ? trim((string) $raw['company']) : null;

        // Training info
        $normalized['course_name'] = isset($raw['course_name']) ? trim((string) $raw['course_name']) : '';
        $normalized['sub_course_name'] = isset($raw['sub_course_name']) ? trim((string) $raw['sub_course_name']) : null;
        $normalized['type'] = isset($raw['type']) ? trim((string) $raw['type']) : null;
        $normalized['category'] = isset($raw['category']) ? trim((string) $raw['category']) : null;
        $normalized['level'] = isset($raw['level']) ? trim((string) $raw['level']) : null;
        $normalized['batch'] = isset($raw['batch']) ? trim((string) $raw['batch']) : null;
        $normalized['main_classification'] = isset($raw['main_classification']) ? trim((string) $raw['main_classification']) : null;
        $normalized['sub_classification'] = isset($raw['sub_classification']) ? trim((string) $raw['sub_classification']) : null;
        $normalized['delivery_method'] = isset($raw['delivery_method']) ? trim((string) $raw['delivery_method']) : null;
        $normalized['sub_delivery_method'] = isset($raw['sub_delivery_method']) ? trim((string) $raw['sub_delivery_method']) : null;

        // Provider info
        $normalized['provider'] = isset($raw['provider']) ? trim((string) $raw['provider']) : null;
        $normalized['sub_provider'] = isset($raw['sub_provider']) ? trim((string) $raw['sub_provider']) : null;
        $normalized['provider_name'] = isset($raw['provider_name']) ? trim((string) $raw['provider_name']) : null;
        $normalized['venue'] = isset($raw['venue']) ? trim((string) $raw['venue']) : null;
        $normalized['instructor'] = isset($raw['instructor']) ? trim((string) $raw['instructor']) : null;

        // Numbers & durations
        $normalized['learning_hours'] = $this->parseNumeric($raw['learning_hours'] ?? null);
        $normalized['progress'] = $this->parseNumeric($raw['progress'] ?? null);

        // Dates & times
        $rawStartDate = $raw['start_date'] ?? null;
        $startDateResult = ExcelDateTimeParser::parseDateResult($rawStartDate);
        $normalized['start_date'] = $startDateResult['value'];
        $normalized['start_date_invalid'] = !$startDateResult['valid'];
        $normalized['start_date_error'] = $startDateResult['error'];
        $normalized['raw_start_date'] = $rawStartDate;
        $normalized['start_date_display'] = ExcelDateTimeParser::formatDisplayDate($startDateResult['value']);

        $rawEndDate = $raw['end_date'] ?? null;
        $endDateResult = ExcelDateTimeParser::parseDateResult($rawEndDate);
        $normalized['end_date'] = $endDateResult['value'];
        $normalized['end_date_invalid'] = !$endDateResult['valid'];
        $normalized['end_date_error'] = $endDateResult['error'];
        $normalized['raw_end_date'] = $rawEndDate;
        $normalized['end_date_display'] = ExcelDateTimeParser::formatDisplayDate($endDateResult['value']);

        $normalized['finish_date'] = $normalized['end_date'] ?? $normalized['start_date'];

        $rawStartTime = $raw['start_time'] ?? null;
        $startTimeResult = ExcelDateTimeParser::parseTimeResult($rawStartTime);
        $normalized['start_time'] = $startTimeResult['value'];
        $normalized['start_time_invalid'] = !$startTimeResult['valid'];
        $normalized['start_time_error'] = $startTimeResult['error'];
        $normalized['raw_start_time'] = $rawStartTime;

        $rawEndTime = $raw['end_time'] ?? null;
        $endTimeResult = ExcelDateTimeParser::parseTimeResult($rawEndTime);
        $normalized['end_time'] = $endTimeResult['value'];
        $normalized['end_time_invalid'] = !$endTimeResult['valid'];
        $normalized['end_time_error'] = $endTimeResult['error'];
        $normalized['raw_end_time'] = $rawEndTime;

        $normalized['month'] = isset($raw['month']) ? trim((string) $raw['month']) : null;

        // Requirement (Mandatory / Non Mandatory)
        $rawReq = strtolower(trim((string) ($raw['requirement'] ?? '')));
        $normalized['requirement'] = ($rawReq === 'mandatory') ? 'mandatory' : 'non mandatory';

        // Derived duration in seconds from times or learning hours if available
        $normalized['duration_seconds'] = $this->parseDurationToSeconds($raw['duration_seconds'] ?? null);
        if ($normalized['duration_seconds'] === null && !empty($normalized['start_time']) && !empty($normalized['end_time'])) {
            $t1 = strtotime($normalized['start_time']);
            $t2 = strtotime($normalized['end_time']);
            if ($t1 !== false && $t2 !== false) {
                if ($t2 >= $t1) {
                    $normalized['duration_seconds'] = $t2 - $t1;
                } else {
                    // Spans midnight (e.g. 22:00 to 02:00 = 4 hours)
                    $normalized['duration_seconds'] = ($t2 + 86400) - $t1;
                }
            }
        }
        if ($normalized['duration_seconds'] === null && !empty($normalized['learning_hours']) && $normalized['learning_hours'] > 0) {
            $normalized['duration_seconds'] = (int) round($normalized['learning_hours'] * 3600);
        }
        $normalized['course_duration'] = $raw['course_duration'] ?? $normalized['duration_seconds'];
        $normalized['progress_status'] = isset($raw['progress_status']) ? trim((string) $raw['progress_status']) : null;

        // Attendance & obligations
        $normalized['attendance'] = $this->parseBoolean($raw['attendance'] ?? null);
        $normalized['attendance_reason'] = isset($raw['attendance_reason']) ? trim((string) $raw['attendance_reason']) : null;
        $normalized['bond_status'] = isset($raw['bond_status']) ? trim((string) $raw['bond_status']) : null;
        $normalized['bond_duration'] = $this->parseNumeric($raw['bond_duration'] ?? null, true);
        $rawBondEndDate = $raw['bond_end_date'] ?? null;
        $bondEndDateResult = ExcelDateTimeParser::parseDateResult($rawBondEndDate);
        $normalized['bond_end_date'] = $bondEndDateResult['value'];
        $normalized['bond_end_date_invalid'] = !$bondEndDateResult['valid'];
        $normalized['bond_end_date_error'] = $bondEndDateResult['error'];
        $normalized['raw_bond_end_date'] = $rawBondEndDate;
        $normalized['bond_end_date_display'] = ExcelDateTimeParser::formatDisplayDate($bondEndDateResult['value']);
        $normalized['bond_amount'] = $this->parseNumeric($raw['bond_amount'] ?? null);
        $normalized['bond_amount_idr'] = $normalized['bond_amount'];

        // Evaluation scores
        $normalized['l1_program'] = isset($raw['l1_program']) ? trim((string) $raw['l1_program']) : null;
        $normalized['assessment_score'] = $this->parseNumeric($raw['assessment_score'] ?? null);
        $normalized['l2_numerical'] = $normalized['assessment_score'];
        $normalized['l2_verbal'] = isset($raw['l2_verbal']) ? trim((string) $raw['l2_verbal']) : null;
        $normalized['l3_numerical'] = $this->parseNumeric($raw['l3_numerical'] ?? null);
        $normalized['l3_verbal'] = isset($raw['l3_verbal']) ? trim((string) $raw['l3_verbal']) : null;

        // Certificate details & PIC
        $normalized['certificate_availability'] = isset($raw['certificate_availability']) ? trim((string) $raw['certificate_availability']) : null;
        $normalized['certificate_institution'] = isset($raw['certificate_institution']) ? trim((string) $raw['certificate_institution']) : null;
        $rawCertIssued = $raw['certificate_issued_date'] ?? null;
        $certIssuedResult = ExcelDateTimeParser::parseDateResult($rawCertIssued);
        $normalized['certificate_issued_date'] = $certIssuedResult['value'];
        $normalized['certificate_issued_date_invalid'] = !$certIssuedResult['valid'];
        $normalized['certificate_issued_date_error'] = $certIssuedResult['error'];
        $normalized['raw_certificate_issued_date'] = $rawCertIssued;
        $normalized['certificate_issued_date_display'] = ExcelDateTimeParser::formatDisplayDate($certIssuedResult['value']);

        $rawCertExpired = $raw['certificate_expired_date'] ?? null;
        $certExpiredResult = ExcelDateTimeParser::parseDateResult($rawCertExpired);
        $normalized['certificate_expired_date'] = $certExpiredResult['value'];
        $normalized['certificate_expired_date_invalid'] = !$certExpiredResult['valid'];
        $normalized['certificate_expired_date_error'] = $certExpiredResult['error'];
        $normalized['raw_certificate_expired_date'] = $rawCertExpired;
        $normalized['certificate_expired_date_display'] = ExcelDateTimeParser::formatDisplayDate($certExpiredResult['value']);
        $normalized['certificate'] = $normalized['certificate_availability'] ?? null;
        $normalized['pic'] = isset($raw['pic']) ? trim((string) $raw['pic']) : null;

        return $normalized;
    }

    public function parseDate($value): ?string
    {
        return ExcelDateTimeParser::parseDate($value);
    }

    public function parseTime($value): ?string
    {
        return ExcelDateTimeParser::parseTime($value);
    }

    public function parseDurationToSeconds($value): ?int
    {
        if ($value === null || $value === '') {
            return null;
        }

        if (is_numeric($value)) {
            return (int) $value;
        }

        $str = trim((string) $value);
        if (preg_match('/^(\d{1,2}):(\d{2})(?::(\d{2}))?$/', $str, $m)) {
            return ((int) $m[1] * 3600) + ((int) $m[2] * 60) + (isset($m[3]) ? (int) $m[3] : 0);
        }

        return null;
    }

    public function parseNumeric($value, bool $asInt = false)
    {
        if ($value === null || $value === '') {
            return null;
        }

        $cleaned = preg_replace('/[^\d.,-]/', '', (string) $value);
        $cleaned = str_replace(',', '.', $cleaned);

        if (!is_numeric($cleaned)) {
            return null;
        }

        return $asInt ? (int) $cleaned : (float) $cleaned;
    }

    public function parseBoolean($value): ?bool
    {
        if ($value === null || $value === '') {
            return null;
        }

        $lower = strtolower(trim((string) $value));
        if (in_array($lower, ['yes', 'y', '1', 'true', 'ya', 'hadir'], true)) {
            return true;
        }
        if (in_array($lower, ['no', 'n', '0', 'false', 'tidak', 'absen'], true)) {
            return false;
        }

        return null;
    }
}
