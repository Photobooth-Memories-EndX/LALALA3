<?php

namespace App\Services\OfflineTraining;

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
                $normalizedIds[strtolower($trimmed)] = $trimmed;
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

        // 4. Other non-blocking validations
        if (!empty($normalized['start_date']) && !empty($normalized['finish_date'])) {
            if ($normalized['start_date'] > $normalized['finish_date']) {
                $warnings[] = "Start Date ({$normalized['start_date']}) is after End Date ({$normalized['finish_date']}).";
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
            'status'           => $status,
            'is_blocking'      => $isBlocking,
            'messages'         => $messages,
            'warnings'         => $warnings,
            'errors'           => $errors,
            'company_mismatch' => $isCompanyMismatch,
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
        $normalized['start_date'] = $this->parseDate($raw['start_date'] ?? null);
        $normalized['end_date'] = $this->parseDate($raw['end_date'] ?? null);
        $normalized['finish_date'] = $normalized['end_date'] ?? $normalized['start_date'];
        $normalized['start_time'] = $this->parseTime($raw['start_time'] ?? null);
        $normalized['end_time'] = $this->parseTime($raw['end_time'] ?? null);
        $normalized['month'] = isset($raw['month']) ? trim((string) $raw['month']) : null;
        $normalized['requirement'] = isset($raw['requirement']) ? trim((string) $raw['requirement']) : null;

        // Derived duration in seconds from times if available
        $normalized['duration_seconds'] = $this->parseDurationToSeconds($raw['duration_seconds'] ?? null);
        if ($normalized['duration_seconds'] === null && !empty($normalized['start_time']) && !empty($normalized['end_time'])) {
            $t1 = strtotime($normalized['start_time']);
            $t2 = strtotime($normalized['end_time']);
            if ($t1 !== false && $t2 !== false && $t2 > $t1) {
                $normalized['duration_seconds'] = $t2 - $t1;
            }
        }

        // Attendance & obligations
        $normalized['attendance'] = $this->parseBoolean($raw['attendance'] ?? null);
        $normalized['attendance_reason'] = isset($raw['attendance_reason']) ? trim((string) $raw['attendance_reason']) : null;
        $normalized['bond_status'] = isset($raw['bond_status']) ? trim((string) $raw['bond_status']) : null;
        $normalized['bond_duration'] = $this->parseNumeric($raw['bond_duration'] ?? null, true);
        $normalized['bond_end_date'] = $this->parseDate($raw['bond_end_date'] ?? null);
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
        $normalized['certificate_issued_date'] = $this->parseDate($raw['certificate_issued_date'] ?? null);
        $normalized['certificate_expired_date'] = $this->parseDate($raw['certificate_expired_date'] ?? null);
        $normalized['certificate'] = $normalized['certificate_availability'] ?? null;
        $normalized['pic'] = isset($raw['pic']) ? trim((string) $raw['pic']) : null;

        return $normalized;
    }

    public function parseDate($value): ?string
    {
        if ($value === null || $value === '') {
            return null;
        }

        if (is_numeric($value)) {
            try {
                return Carbon::instance(ExcelDate::excelToDateTimeObject((float) $value))->format('Y-m-d');
            } catch (Throwable $e) {
                // fall through
            }
        }

        $str = trim((string) $value);
        if ($str === '' || $str === '-') {
            return null;
        }

        try {
            return Carbon::parse($str)->format('Y-m-d');
        } catch (Throwable $e) {
            return null;
        }
    }

    public function parseTime($value): ?string
    {
        if ($value === null || $value === '') {
            return null;
        }

        if (is_numeric($value) && (float) $value < 1.0) {
            try {
                $seconds = round((float) $value * 86400);
                $h = floor($seconds / 3600);
                $m = floor(($seconds % 3600) / 60);
                $s = $seconds % 60;
                return sprintf('%02d:%02d:%02d', $h, $m, $s);
            } catch (Throwable $e) {
                // fall through
            }
        }

        $str = trim((string) $value);
        if (preg_match('/^(\d{1,2}):(\d{2})(?::(\d{2}))?$/', $str, $matches)) {
            $h = (int) $matches[1];
            $m = (int) $matches[2];
            $s = isset($matches[3]) ? (int) $matches[3] : 0;
            return sprintf('%02d:%02d:%02d', $h, $m, $s);
        }

        return $str !== '' && $str !== '-' ? $str : null;
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
