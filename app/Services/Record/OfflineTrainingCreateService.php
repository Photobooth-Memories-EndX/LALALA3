<?php

namespace App\Services\Record;

use App\Models\OfflineTraining;
use App\Models\OfflineTrainingUser;
use App\Models\User;
use Carbon\Carbon;
use DateTimeInterface;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class OfflineTrainingCreateService
{
    /**
     * Create offline training and its participant records within a database transaction.
     *
     * @param  array  $data
     * @param  int|null  $createdByUserId
     * @return OfflineTraining
     *
     * @throws ValidationException
     */
    public function create(array $data, ?int $createdByUserId = null): OfflineTraining
    {
        $normalizedTraining = $this->normalizeTrainingData($data, $createdByUserId);
        $this->validateTrainingData($normalizedTraining);

        $rawParticipants = $data['participants'] ?? [];
        if (!is_array($rawParticipants) || empty($rawParticipants)) {
            throw ValidationException::withMessages([
                'participants' => ['At least one participant is required to create a training record.'],
            ]);
        }

        $normalizedParticipants = $this->normalizeParticipantsData(
            $rawParticipants,
            $normalizedTraining['learning_hours']
        );
        $this->validateParticipantsData($normalizedParticipants);

        return DB::transaction(function () use ($normalizedTraining, $normalizedParticipants, $createdByUserId) {
            $training = OfflineTraining::create($normalizedTraining);

            foreach ($normalizedParticipants as $participantData) {
                $participantData['offline_training_id'] = $training->id;
                OfflineTrainingUser::create($participantData);
            }

            // Auto-register legitimate options to master options vocabulary
            app(OfflineOptionService::class)->ensureOptionsFromTraining($normalizedTraining, 'manual', $createdByUserId);

            // Auto-provision CertificateSetting for this Offline Training
            app(\App\Services\OfflineCertificate\OfflineCertificateLifecycleService::class)->onTrainingCreated($training);

            return $training->load('participants');
        });
    }

    /**
     * Normalize training event level data.
     */
    public function normalizeTrainingData(array $raw, ?int $userId = null): array
    {
        $durationSeconds = $this->normalizeDuration($raw['duration'] ?? ($raw['duration_seconds'] ?? null));
        $learningHours = $this->normalizeLearningHours($raw['learning_hours'] ?? null);

        // If duration is provided but learning hours is null, suggest hours from duration
        if ($learningHours === null && $durationSeconds !== null && $durationSeconds > 0) {
            $learningHours = round($durationSeconds / 3600, 2);
        }

        $rawKpi = $raw['counted_corporate_kpi'] ?? null;
        $countedKpi = $this->normalizeBoolean($rawKpi);
        $kpiYear = null;
        $subKpi = null;

        // Support for "Yes (2026)" format in raw input
        if ($rawKpi !== null && is_string($rawKpi)) {
            $trimmedKpi = trim($rawKpi);
            if (preg_match('/^(?:yes|ya)\s*\(\s*(\d{2,4})\s*\)$/i', $trimmedKpi, $m)) {
                $countedKpi = true;
                $extractedYear = (int) $m[1];
                if ($extractedYear < 100) {
                    $extractedYear += 2000;
                }
                $kpiYear = $extractedYear;
            }
        }

        if ($countedKpi === true) {
            if (!$kpiYear && !empty($raw['corporate_kpi_year'])) {
                $kpiYear = (int) $raw['corporate_kpi_year'];
            } elseif (!$kpiYear && !empty($raw['sub_corporate_kpi']) && preg_match('/^\d{4}$/', trim((string) $raw['sub_corporate_kpi']))) {
                $kpiYear = (int) trim((string) $raw['sub_corporate_kpi']);
            } elseif (!$kpiYear && !empty($raw['start_date'])) {
                $kpiYear = (int) substr((string) $raw['start_date'], 0, 4);
            } elseif (!$kpiYear) {
                $kpiYear = (int) date('Y');
            }
            $subKpi = !empty($raw['sub_corporate_kpi']) ? $this->normalizeString($raw['sub_corporate_kpi']) : ($kpiYear ? (string) $kpiYear : null);
        } else {
            $countedKpi = false;
            $kpiYear = null;
            $subKpi = null;
        }

        return [
            'course_name'           => $this->normalizeString($raw['course_name'] ?? null),
            'sub_course_name'       => $this->normalizeString($raw['sub_course_name'] ?? null),
            'type'                  => $this->normalizeString($raw['type'] ?? null),
            'category'              => $this->normalizeString($raw['category'] ?? null),
            'level'                 => $this->normalizeString($raw['level'] ?? null),
            'batch'                 => $this->normalizeString($raw['batch'] ?? null),
            'main_classification'   => $this->normalizeString($raw['main_classification'] ?? null),
            'sub_classification'    => $this->normalizeString($raw['sub_classification'] ?? null),
            'counted_corporate_kpi' => $countedKpi,
            'sub_corporate_kpi'     => $subKpi,
            'corporate_kpi_year'    => $kpiYear,
            'delivery_method'       => $this->normalizeString($raw['delivery_method'] ?? null),
            'sub_delivery_method'   => $this->normalizeString($raw['sub_delivery_method'] ?? null),
            'provider'              => $this->normalizeString($raw['provider'] ?? null),
            'sub_provider'          => $this->normalizeString($raw['sub_provider'] ?? null),
            'provider_name'         => $this->normalizeString($raw['provider_name'] ?? null),
            'venue'                 => $this->normalizeString($raw['venue'] ?? null),
            'instructor'            => $this->normalizeString($raw['instructor'] ?? null),
            'duration_seconds'      => $durationSeconds,
            'learning_hours'        => $learningHours,
            'start_date'            => $this->normalizeDate($raw['start_date'] ?? null),
            'finish_date'           => $this->normalizeDate($raw['finish_date'] ?? null),
            'start_time'            => $this->normalizeTime($raw['start_time'] ?? null, '08:00:00'),
            'end_time'              => $this->normalizeTime($raw['end_time'] ?? null, '16:00:00'),
            'created_by'            => $userId,
        ];
    }

    /**
     * Validate training event data.
     */
    protected function validateTrainingData(array $t): void
    {
        $errors = [];

        if (empty($t['course_name'])) {
            $errors['course_name'] = ['Course name is required.'];
        }

        if (!empty($t['start_date']) && !empty($t['finish_date'])) {
            $startTime = !empty($t['start_time']) ? $t['start_time'] : '08:00:00';
            $endTime = !empty($t['end_time']) ? $t['end_time'] : '16:00:00';

            try {
                $start = Carbon::parse("{$t['start_date']} {$startTime}");
                $finish = Carbon::parse("{$t['finish_date']} {$endTime}");

                if ($finish->lt($start)) {
                    $errors['finish_date'] = ["Finish date and time ({$t['finish_date']} " . substr($endTime, 0, 5) . ") cannot be earlier than start date and time ({$t['start_date']} " . substr($startTime, 0, 5) . ")."];
                }
            } catch (\Throwable $e) {
                if ("{$t['finish_date']} {$endTime}" < "{$t['start_date']} {$startTime}") {
                    $errors['finish_date'] = ["Finish date ({$t['finish_date']}) cannot be earlier than start date ({$t['start_date']})."];
                }
            }
        }

        if (($t['counted_corporate_kpi'] === true || $t['counted_corporate_kpi'] === 1) && empty($t['corporate_kpi_year'])) {
            $errors['corporate_kpi_year'] = ['Corporate KPI Year is required when Counted as Corporate KPI is Yes.'];
        }

        if (!empty($errors)) {
            throw ValidationException::withMessages($errors);
        }
    }

    /**
     * Normalize list of participants.
     */
    public function normalizeParticipantsData(array $rawList, ?float $fallbackHours = null): array
    {
        $normalized = [];

        foreach ($rawList as $raw) {
            $employeeId = $this->normalizeString($raw['employee_id'] ?? ($raw['id'] ?? null));
            $userId = !empty($raw['user_id']) ? (int) $raw['user_id'] : null;

            // If user_id is not passed, attempt matching with existing LMS user account
            if (!$userId && $employeeId) {
                $matchedUser = User::withTrashed()->where('id_emp', $employeeId)->first();
                if ($matchedUser) {
                    $userId = $matchedUser->id;
                }
            }

            // Participant specific learning hours, fallback to course learning hours
            $participantHours = $this->normalizeLearningHours($raw['learning_hours'] ?? null);
            if ($participantHours === null && $fallbackHours !== null) {
                $participantHours = $fallbackHours;
            }

            $progress = $this->normalizeProgress($raw['progress'] ?? 100);
            $attendance = $this->normalizeBoolean($raw['attendance'] ?? true);
            $bondStatus = $this->normalizeString($raw['bond_status'] ?? 'No');
            $isBonded = $bondStatus && in_array(strtolower($bondStatus), ['yes', 'y', '1', 'true'], true);

            $normalized[] = [
                'user_id'           => $userId,
                'employee_id'       => $employeeId,
                'name'              => $this->normalizeString($raw['name'] ?? null),
                'grade'             => $this->normalizeString($raw['grade'] ?? null),
                'job_position'      => $this->normalizeString($raw['job_position'] ?? ($raw['position'] ?? null)),
                'team'              => $this->normalizeString($raw['team'] ?? null),
                'department'        => $this->normalizeString($raw['department'] ?? null),
                'company'           => $this->normalizeString($raw['company'] ?? null),
                'learning_hours'    => $participantHours,
                'progress'          => $progress !== null ? $progress : 100.0,
                'assessment_score'  => $this->normalizeAssessmentScore($raw['assessment_score'] ?? null),
                'certificate'       => $this->normalizeString($raw['certificate'] ?? null),
                'certificate_issued_date'  => $this->normalizeDate($raw['certificate_issued_date'] ?? null),
                'certificate_expired_date' => $this->normalizeDate($raw['certificate_expired_date'] ?? null),
                'attendance'        => $attendance !== null ? $attendance : true,
                'attendance_reason' => $this->normalizeString($raw['attendance_reason'] ?? null),
                'bond_status'       => $bondStatus,
                'bond_duration'     => $isBonded && isset($raw['bond_duration']) && is_numeric($raw['bond_duration']) ? (int) $raw['bond_duration'] : null,
                'bond_end_date'     => $isBonded ? $this->normalizeDate($raw['bond_end_date'] ?? null) : null,
                'bond_amount_idr'   => $isBonded ? $this->normalizeMoney($raw['bond_amount_idr'] ?? $raw['bond_amount'] ?? null) : null,
                'bond_amount'       => $isBonded ? $this->normalizeMoney($raw['bond_amount_idr'] ?? $raw['bond_amount'] ?? null) : null,
                'l1_program'        => $this->normalizeString($raw['l1_program'] ?? null),
                'l2_numerical'      => $this->normalizeDecimal($raw['l2_numerical'] ?? null, 4),
                'l2_verbal'         => $this->normalizeString($raw['l2_verbal'] ?? null),
                'l3_numerical'      => $this->normalizeDecimal($raw['l3_numerical'] ?? null, 4),
                'l3_verbal'         => $this->normalizeString($raw['l3_verbal'] ?? null),
                'pic'               => $this->normalizeString($raw['pic'] ?? null),
            ];
        }

        return $normalized;
    }

    /**
     * Validate participants for required fields and duplicate prevention within the same training.
     */
    protected function validateParticipantsData(array $participants): void
    {
        $errors = [];
        $seen = [];

        foreach ($participants as $index => $p) {
            $num = $index + 1;

            if (empty($p['employee_id'])) {
                $errors["participants.{$index}.employee_id"] = ["Participant #{$num} Employee ID is required."];
            }

            if (empty($p['name'])) {
                $errors["participants.{$index}.name"] = ["Participant #{$num} Name is required."];
            }

            if ($p['progress'] !== null && ($p['progress'] < 0 || $p['progress'] > 100)) {
                $errors["participants.{$index}.progress"] = ["Participant #{$num} Progress must be between 0% and 100%."];
            }

            // Duplicate participant check
            $empIdKey = strtolower(trim((string) ($p['employee_id'] ?? '')));
            $userKey = !empty($p['user_id']) ? "uid:{$p['user_id']}" : "emp:{$empIdKey}";

            if (isset($seen[$userKey])) {
                $name = $p['name'] ?? $p['employee_id'];
                $errors['participants'] = ["Duplicate participant detected: {$name} ({$p['employee_id']}) cannot be added more than once to this training."];
                break;
            }
            $seen[$userKey] = true;
        }

        if (!empty($errors)) {
            throw ValidationException::withMessages($errors);
        }
    }

    /**
     * Derive canonical Month string from Start Date (e.g. "Feb 2026").
     */
    public static function deriveMonth(?string $startDate): ?string
    {
        if (!$startDate) {
            return null;
        }

        try {
            return Carbon::parse($startDate)->format('M Y');
        } catch (\Throwable $e) {
            return null;
        }
    }

    // =========================================================================
    // FIELD NORMALIZATION HELPERS
    // =========================================================================

    public function normalizeString($value): ?string
    {
        if ($value === null) return null;
        $str = trim((string) $value);
        return $str === '' ? null : $str;
    }

    public function normalizeBoolean($value): ?bool
    {
        if ($value === null) return null;
        if (is_bool($value)) return $value;

        $str = strtolower(trim((string) $value));
        if ($str === '') return null;

        if (in_array($str, ['yes', 'y', '1', 'true', 'ya'], true) || preg_match('/^(?:yes|ya)\s*\(\s*\d{2,4}\s*\)$/', $str)) {
            return true;
        }
        if (in_array($str, ['no', 'n', '0', 'false', 'tidak'], true)) {
            return false;
        }

        return null;
    }

    public function normalizeProgress($value): ?float
    {
        if ($value === null) return null;
        $str = trim((string) $value);
        if ($str === '') return null;

        $str = str_replace(['%', ' '], '', $str);
        if (!is_numeric($str)) return null;

        $num = (float) $str;
        return round($num, 4);
    }

    public function normalizeDuration($value): ?int
    {
        if ($value === null) return null;
        $str = trim((string) $value);
        if ($str === '') return null;

        // HH:MM:SS or HH:MM
        if (preg_match('/^(\d{1,2}):(\d{2})(?::(\d{2}))?$/', $str, $matches)) {
            $h = (int) $matches[1];
            $m = (int) $matches[2];
            $s = isset($matches[3]) ? (int) $matches[3] : 0;
            return ($h * 3600) + ($m * 60) + $s;
        }

        if (is_numeric($str)) {
            $num = (float) $str;
            if ($num > 0 && $num <= 24) {
                return (int) round($num * 3600);
            }
            return (int) round($num);
        }

        return null;
    }

    public function normalizeLearningHours($value): ?float
    {
        if ($value === null) return null;
        $str = trim((string) $value);
        if ($str === '') return null;

        $str = str_replace(['hours', 'hour', 'jam', ' '], '', strtolower($str));
        $str = str_replace(',', '.', $str);

        if (!is_numeric($str)) return null;

        return round((float) $str, 4);
    }

    public function normalizeDate($value): ?string
    {
        if ($value === null) return null;
        if ($value instanceof DateTimeInterface) {
            return $value->format('Y-m-d');
        }

        $str = trim((string) $value);
        if ($str === '') return null;

        try {
            return Carbon::parse($str)->format('Y-m-d');
        } catch (\Throwable $e) {
            return null;
        }
    }

    public function normalizeMoney($value): ?float
    {
        if ($value === null) return null;
        $str = trim((string) $value);
        if ($str === '') return null;

        $str = preg_replace('/^(?:rp|idr|usd|\$)\.?\s*/i', '', $str);
        $str = trim($str);

        if (str_contains($str, '.') && str_contains($str, ',')) {
            if (strrpos($str, '.') > strrpos($str, ',')) {
                $str = str_replace(',', '', $str);
            } else {
                $str = str_replace('.', '', $str);
                $str = str_replace(',', '.', $str);
            }
        } elseif (str_contains($str, ',')) {
            $parts = explode(',', $str);
            if (count($parts) === 2 && strlen($parts[1]) <= 2) {
                $str = str_replace(',', '.', $str);
            } else {
                $str = str_replace(',', '', $str);
            }
        }

        $clean = preg_replace('/[^0-9.]/', '', $str);
        if ($clean === '' || !is_numeric($clean)) return null;

        return round((float) $clean, 2);
    }

    public function normalizeAssessmentScore($value): ?float
    {
        if ($value === null) return null;
        $str = trim((string) $value);
        if ($str === '') return null;

        $str = str_replace(',', '.', $str);
        if (!is_numeric($str)) return null;

        $num = (float) $str;
        return round($num, 2);
    }

    public function normalizeDecimal($value, int $decimals = 4): ?float
    {
        if ($value === null) return null;
        $str = trim((string) $value);
        if ($str === '') return null;

        $str = str_replace(',', '.', $str);
        if (!is_numeric($str)) return null;

        return round((float) $str, $decimals);
    }

    public function normalizeTime($value, ?string $default = null): ?string
    {
        if ($value === null) {
            return $default;
        }
        $str = trim((string) $value);
        if ($str === '') {
            return $default;
        }

        if (preg_match('/^(\d{1,2}):(\d{2})(?::(\d{2}))?$/', $str, $m)) {
            $h = (int) $m[1];
            $i = (int) $m[2];
            $s = isset($m[3]) ? (int) $m[3] : 0;
            if ($h >= 0 && $h <= 23 && $i >= 0 && $i <= 59 && $s >= 0 && $s <= 59) {
                return sprintf('%02d:%02d:%02d', $h, $i, $s);
            }
        }

        try {
            return Carbon::parse($str)->format('H:i:s');
        } catch (\Throwable $e) {
            return $default;
        }
    }
}
