<?php

namespace App\Services\Record;

use App\Models\OfflineTraining;
use App\Models\OfflineTrainingUser;
use App\Models\User;
use Carbon\Carbon;
use DateTimeInterface;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class OfflineTrainingUpdateService
{
    /**
     * Update an offline training participant record and its parent training event.
     *
     * @param  OfflineTrainingUser  $participant
     * @param  array  $data
     * @return OfflineTrainingUser
     *
     * @throws ValidationException
     */
    public function update(OfflineTrainingUser $participant, array $data): OfflineTrainingUser
    {
        $training = $participant->training;
        if (!$training) {
            throw ValidationException::withMessages([
                'training' => ['Associated training event could not be found.'],
            ]);
        }

        $normalizedTraining = $this->normalizeTrainingData($data, $training);
        $this->validateTrainingData($normalizedTraining);

        $normalizedParticipant = $this->normalizeParticipantData(
            $data,
            $normalizedTraining['learning_hours'] ?? $training->learning_hours
        );
        $this->validateParticipantData($normalizedParticipant, $participant, $training);

        return DB::transaction(function () use ($participant, $training, $normalizedTraining, $normalizedParticipant) {
            $training->update($normalizedTraining);
            $participant->update($normalizedParticipant);

            // Auto-register legitimate options to master options vocabulary
            app(OfflineOptionService::class)->ensureOptionsFromTraining($normalizedTraining, 'manual', auth()->id());

            return $participant->fresh(['training']);
        });
    }

    /**
     * Normalize training event level data.
     */
    public function normalizeTrainingData(array $raw, ?OfflineTraining $existing = null): array
    {
        $durationSeconds = $this->normalizeDuration($raw['duration'] ?? ($raw['duration_seconds'] ?? null));
        $learningHours = $this->normalizeLearningHours($raw['learning_hours'] ?? ($raw['course_learning_hours'] ?? null));

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
            $subKpi = $kpiYear ? (string) $kpiYear : $this->normalizeString($raw['sub_corporate_kpi'] ?? null);
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
            'start_time'            => array_key_exists('start_time', $raw)
                ? $this->normalizeTime($raw['start_time'], $existing?->start_time ?: '08:00:00')
                : ($existing?->start_time ?: '08:00:00'),
            'end_time'              => array_key_exists('end_time', $raw)
                ? $this->normalizeTime($raw['end_time'], $existing?->end_time ?: '16:00:00')
                : ($existing?->end_time ?: '16:00:00'),
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
     * Normalize participant specific data.
     */
    public function normalizeParticipantData(array $raw, ?float $fallbackHours = null): array
    {
        $employeeId = $this->normalizeString($raw['employee_id'] ?? null);
        $userId = !empty($raw['user_id']) ? (int) $raw['user_id'] : null;

        if (!$userId && $employeeId) {
            $matchedUser = User::where('id_emp', $employeeId)->first();
            if ($matchedUser) {
                $userId = $matchedUser->id;
            }
        }

        $participantHours = $this->normalizeLearningHours(
            $raw['participant_learning_hours'] ?? ($raw['learning_hours'] ?? null)
        );
        if ($participantHours === null && $fallbackHours !== null) {
            $participantHours = $fallbackHours;
        }

        $progress = $this->normalizeProgress($raw['progress'] ?? null);
        $attendance = $this->normalizeBoolean($raw['attendance'] ?? null);
        $bondStatus = $this->normalizeString($raw['bond_status'] ?? 'No');
        $isBonded = $bondStatus && in_array(strtolower($bondStatus), ['yes', 'y', '1', 'true'], true);

        return [
            'user_id'           => $userId,
            'employee_id'       => $employeeId,
            'name'              => $this->normalizeString($raw['name'] ?? ($raw['participant_name'] ?? null)),
            'grade'             => $this->normalizeString($raw['grade'] ?? null),
            'job_position'      => $this->normalizeString($raw['job_position'] ?? ($raw['position'] ?? null)),
            'team'              => $this->normalizeString($raw['team'] ?? null),
            'department'        => $this->normalizeString($raw['department'] ?? null),
            'company'           => $this->normalizeString($raw['company'] ?? null),
            'learning_hours'    => $participantHours,
            'progress'          => $progress !== null ? $progress : 0.0,
            'assessment_score'  => $this->normalizeAssessmentScore($raw['assessment_score'] ?? null),
            'certificate'       => $this->normalizeString($raw['certificate'] ?? null),
            'attendance'        => $attendance !== null ? $attendance : true,
            'attendance_reason' => $this->normalizeString($raw['attendance_reason'] ?? null),
            'bond_status'       => $bondStatus ?: 'No',
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

    /**
     * Validate participant data.
     */
    protected function validateParticipantData(array $p, OfflineTrainingUser $currentParticipant, OfflineTraining $training): void
    {
        $errors = [];

        if (empty($p['employee_id'])) {
            $errors['employee_id'] = ['Employee ID is required.'];
        }

        if (empty($p['name'])) {
            $errors['name'] = ['Participant name is required.'];
        }

        if ($p['progress'] !== null && ($p['progress'] < 0 || $p['progress'] > 100)) {
            $errors['progress'] = ['Progress must be between 0% and 100%.'];
        }

        if ($p['assessment_score'] !== null && ($p['assessment_score'] < 0 || $p['assessment_score'] > 100)) {
            $errors['assessment_score'] = ['Assessment score must be between 0 and 100.'];
        }

        // Duplicate participant check within same training (excluding current record)
        if (!empty($p['employee_id'])) {
            $duplicate = OfflineTrainingUser::where('offline_training_id', $training->id)
                ->where('id', '!=', $currentParticipant->id)
                ->where('employee_id', $p['employee_id'])
                ->whereNull('deleted_at')
                ->exists();

            if ($duplicate) {
                $errors['employee_id'] = ["Another participant with Employee ID {$p['employee_id']} is already enrolled in this training."];
            }
        }

        if (!empty($errors)) {
            throw ValidationException::withMessages($errors);
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
        if ($value === null || $value === '') return null;
        if (is_bool($value)) return $value;

        $str = strtolower(trim((string) $value));
        if (in_array($str, ['1', 'true', 'yes', 'y', 'ya'], true) || preg_match('/^(?:yes|ya)\s*\(\s*\d{2,4}\s*\)$/', $str)) return true;
        if (in_array($str, ['0', 'false', 'no', 'n', 'tidak'], true)) return false;

        return null;
    }

    public function normalizeDate($value): ?string
    {
        if (empty($value)) return null;
        if ($value instanceof DateTimeInterface) {
            return $value->format('Y-m-d');
        }

        $str = trim((string) $value);
        if ($str === '' || $str === '-' || $str === '0000-00-00') {
            return null;
        }

        try {
            return Carbon::parse($str)->format('Y-m-d');
        } catch (\Throwable $e) {
            return null;
        }
    }

    public function normalizeDuration($value): ?int
    {
        if ($value === null || $value === '') return null;
        if (is_numeric($value)) {
            return max(0, (int) $value);
        }

        $str = trim((string) $value);
        $parts = explode(':', $str);
        if (count($parts) === 3) {
            return ((int) $parts[0] * 3600) + ((int) $parts[1] * 60) + (int) $parts[2];
        } elseif (count($parts) === 2) {
            return ((int) $parts[0] * 3600) + ((int) $parts[1] * 60);
        }

        return null;
    }

    public function normalizeLearningHours($value): ?float
    {
        if ($value === null || $value === '') return null;
        $cleaned = str_replace(',', '.', trim((string) $value));
        if (is_numeric($cleaned)) {
            return round(max(0, (float) $cleaned), 4);
        }
        return null;
    }

    public function normalizeProgress($value): ?float
    {
        if ($value === null || $value === '') return null;
        $cleaned = str_replace(['%', ','], ['', '.'], trim((string) $value));
        if (is_numeric($cleaned)) {
            return round((float) $cleaned, 4);
        }
        return null;
    }

    public function normalizeAssessmentScore($value): ?float
    {
        if ($value === null || $value === '') return null;
        $cleaned = str_replace(',', '.', trim((string) $value));
        if (is_numeric($cleaned)) {
            return round((float) $cleaned, 2);
        }
        return null;
    }

    public function normalizeMoney($value): ?float
    {
        if ($value === null || $value === '') return null;
        $cleaned = preg_replace('/[^\d.]/', '', str_replace(',', '.', trim((string) $value)));
        if (is_numeric($cleaned)) {
            return round((float) $cleaned, 2);
        }
        return null;
    }

    public function normalizeDecimal($value, int $precision = 4): ?float
    {
        if ($value === null || $value === '') return null;
        $cleaned = str_replace(',', '.', trim((string) $value));
        if (is_numeric($cleaned)) {
            return round((float) $cleaned, $precision);
        }
        return null;
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
