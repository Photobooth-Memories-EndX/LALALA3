<?php

namespace App\Services\Learning;

use App\Models\OfflineTraining;
use App\Models\OfflineTrainingUser;
use App\Services\OfflineCertificate\OfflineCertificateEligibilityResult;
use App\Services\OfflineCertificate\OfflineCertificateEligibilityService;
use App\Support\DurationFormatter;
use App\Support\LearningFilterHelper;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

/**
 * Service to fetch and normalize Offline Training Records for an authenticated user.
 */
class UserRecordOfflineService
{
    /**
     * Query OfflineTrainingUser records for the specific user.
     */
    public function queryForUser(int $userId, ?string $userEmpId): Builder
    {
        if (empty($userEmpId)) {
            $userEmpId = \App\Models\User::where('id', $userId)->value('id_emp');
        }

        return OfflineTrainingUser::query()
            ->with(['training' => fn ($q) => $q->withTrashed()])
            ->where(function (Builder $q) use ($userId, $userEmpId) {
                $q->where('user_id', $userId);
                if (!empty($userEmpId)) {
                    $q->orWhere('employee_id', $userEmpId);
                }
            })
            ->whereNull('deleted_at')
            ->where(function (Builder $q) {
                // My Learning Record only shows completed trainings or finalized attendance records.
                // Ongoing schedule training records (attendance is null and progress < 100) are excluded.
                $q->whereNotNull('attendance')
                  ->orWhere('progress', '>=', 100);
            });
    }

    /**
     * Calculate canonical total learning hours for Offline Learning Record of a user.
     *
     * @param int $userId
     * @param string|null $userEmpId
     * @return float Rounded to 2 decimal places
     */
    public function getTotalLearningHours(int $userId, ?string $userEmpId = null): float
    {
        if ($userEmpId === null) {
            $userEmpId = \App\Models\User::where('id', $userId)->value('id_emp');
        }

        $rawRecords = $this->queryForUser($userId, $userEmpId)
            ->get()
            ->filter(fn (OfflineTrainingUser $otu) => $otu->training !== null);

        $totalHours = (float) $rawRecords->sum(function (OfflineTrainingUser $otu) {
            return (float) ($otu->learning_hours ?? $otu->training->learning_hours ?? 0);
        });

        return round($totalHours, 2);
    }

    /**
     * Get and normalize all offline records for user with filters applied.
     */
    public function getRecords(int $userId, ?string $userEmpId, array $filters = []): Collection
    {
        $rawRecords = $this->queryForUser($userId, $userEmpId)
            ->get()
            ->filter(fn (OfflineTrainingUser $otu) => $otu->training !== null);

        $eligibilityMap = app(OfflineCertificateEligibilityService::class)->evaluateMany($rawRecords);

        $normalized = $rawRecords->map(fn (OfflineTrainingUser $otu) => $this->normalizeRow($otu, $eligibilityMap->get($otu->id)));

        return $this->applyFilters($normalized, $filters);
    }

    /**
     * Normalize an OfflineTrainingUser record into standard row format.
     */
    public function normalizeRow(OfflineTrainingUser $otu, ?OfflineCertificateEligibilityResult $eligibility = null): array
    {
        $training = $otu->training;

        $rawDuration = $training->duration_seconds;
        if ($rawDuration !== null && $rawDuration !== '' && is_numeric($rawDuration)) {
            $sec = max(0, (int) $rawDuration);
            $hours = (int) floor($sec / 3600);
            $minutes = (int) floor(($sec % 3600) / 60);
            $seconds = (int) ($sec % 60);
            $durationFormatted = sprintf('%02d:%02d:%02d', $hours, $minutes, $seconds);
            $durationSeconds = $sec;
        } else {
            $durationFormatted = '-';
            $durationSeconds = 0;
        }

        $learningHours = (float) ($otu->learning_hours ?? $training->learning_hours ?? 0);

        $startDateStr = $training->start_date
            ? Carbon::parse($training->start_date)->format('Y-m-d')
            : '';
        $finishDateStr = $training->finish_date
            ? Carbon::parse($training->finish_date)->format('Y-m-d')
            : ($startDateStr ?: '');

        // Format Date Time display
        if ($startDateStr && $finishDateStr && $startDateStr !== $finishDateStr) {
            $dateTimeDisplay = Carbon::parse($startDateStr)->format('d M Y') . ' - ' . Carbon::parse($finishDateStr)->format('d M Y');
        } elseif ($finishDateStr) {
            $dateTimeDisplay = Carbon::parse($finishDateStr)->format('d M Y');
        } elseif ($startDateStr) {
            $dateTimeDisplay = Carbon::parse($startDateStr)->format('d M Y');
        } else {
            $dateTimeDisplay = '-';
        }

        $startTime = $training->start_time ? Carbon::parse($training->start_time)->format('H:i') : '';
        $endTime = $training->end_time ? Carbon::parse($training->end_time)->format('H:i') : '';
        $startTimeFull = $training->start_time ? Carbon::parse($training->start_time)->format('H:i:s') : '00:00:00';
        $endTimeFull = $training->end_time ? Carbon::parse($training->end_time)->format('H:i:s') : '00:00:00';
        $timeDisplay = ($startTime && $endTime) ? "{$startTime} - {$endTime}" : ($startTime ?: ($endTime ?: '-'));

        $startDatetimeStr = $startDateStr ? "{$startDateStr} {$startTimeFull}" : '';
        $endDatetimeStr = $finishDateStr ? "{$finishDateStr} {$endTimeFull}" : '';

        $progress = (float) ($otu->progress ?? 0);
        $score = $otu->assessment_score !== null ? (float) $otu->assessment_score : null;

        $attendanceRaw = $otu->attendance;
        $attendanceNorm = is_string($attendanceRaw) ? strtolower(trim($attendanceRaw)) : $attendanceRaw;
        if (in_array($attendanceNorm, [true, 1, '1', 'present', 'yes', 'y', 'attended'], true)) {
            $attendanceStatus = 'Yes';
        } elseif (in_array($attendanceNorm, [false, 0, '0', 'absent', 'no', 'n', null, '', '-'], true)) {
            $attendanceStatus = 'No';
        } else {
            $attendanceStatus = 'No';
        }

        $rawAttendanceReason = (!empty($otu->attendance_reason) && trim((string) $otu->attendance_reason) !== '-')
            ? trim((string) $otu->attendance_reason)
            : null;

        if ($rawAttendanceReason !== null && $rawAttendanceReason !== '') {
            $attendanceReasonDisplay = $rawAttendanceReason;
        } elseif ($attendanceStatus === 'Yes') {
            $attendanceReasonDisplay = 'Employee attended the training.';
        } else {
            $attendanceReasonDisplay = 'No attendance reason recorded.';
        }

        $bondAmount = $otu->bond_amount !== null ? (float) $otu->bond_amount : null;
        $bondAmountFormatted = $bondAmount !== null && $bondAmount > 0
            ? 'Rp ' . number_format($bondAmount, 0, ',', '.')
            : '-';
        $bondEndDateStr = $otu->bond_end_date ? Carbon::parse($otu->bond_end_date)->format('d M Y') : '-';

        $l2Num = $otu->l2_numerical !== null ? (float) $otu->l2_numerical : null;
        $l3Num = $otu->l3_numerical !== null ? (float) $otu->l3_numerical : null;

        if ($eligibility === null) {
            $eligibility = app(OfflineCertificateEligibilityService::class)->evaluate($otu);
        }

        $isEligible = $eligibility->eligible;
        $certReason = $eligibility->reason;
        $hasCertificate = $eligibility->hasExistingCertificate();
        $certState = ($hasCertificate || $isEligible) ? 'ready' : 'none';

        if ($hasCertificate) {
            $certActionUrl = route('certificate.show', $eligibility->certificate->credential_id);
        } elseif ($isEligible) {
            $certActionUrl = route('certificate.generate.offline', $otu->id);
        } else {
            $certActionUrl = null;
        }

        $existingCert = $hasCertificate ? $eligibility->certificate : null;
        $cs = null;
        if ($training) {
            $cs = $training->relationLoaded('certificateSetting') ? $training->certificateSetting : $training->certificateSetting()->first();
        }
        $certInstitution = $existingCert?->institution
            ?: ($cs?->institution
                ?: ($existingCert?->issuer_name
                    ?: ($cs?->issuer_name ?: ($otu->certificate ?: '-'))));

        $certIssuedDate = $existingCert?->issued_date
            ? Carbon::parse($existingCert->issued_date)->format('Y-m-d')
            : ($cs?->issued_date
                ? Carbon::parse($cs->issued_date)->format('Y-m-d')
                : ($existingCert?->issued_at ? Carbon::parse($existingCert->issued_at)->format('Y-m-d') : '-'));

        $certExpiredDate = $existingCert?->expired_date
            ? Carbon::parse($existingCert->expired_date)->format('Y-m-d')
            : ($cs?->expired_date ? Carbon::parse($cs->expired_date)->format('Y-m-d') : '-');

        $statusRaw = strtolower(trim((string) ($training->requirement ?? $training->status ?? 'non mandatory')));
        $requirement = ($statusRaw === 'mandatory') ? 'Mandatory' : 'Non-Mandatory';

        return [
            'id'                        => $otu->id,
            'offline_training_id'       => $otu->offline_training_id,
            'source'                    => 'offline',
            'row_kind'                  => 'offline',
            'title'                     => optional($training)->title ?: (optional($training)->course_name ?: 'Offline Training'),
            'subtitle'                  => optional($training)->sub_course_name ?: (optional($training)->provider_name ?: ''),
            'requirement'               => $requirement,
            'course_name'               => optional($training)->course_name ?: (optional($training)->title ?: 'Offline Training'),
            'sub_course_name'           => optional($training)->sub_course_name ?: '',
            'type'                      => optional($training)->type ?: '-',
            'category'                  => 'Non KP-OWL',
            'level'                     => optional($training)->level ?: '-',
            'batch'                     => optional($training)->batch ?: '-',
            'classification'            => optional($training)->main_classification ?: 'Unclassified',
            'main_classification'       => optional($training)->main_classification ?: 'Unclassified',
            'sub_classification'        => optional($training)->sub_classification ?: '-',
            'delivery_method'           => \App\Models\OfflineTraining::normalizeDeliveryMethod(optional($training)->delivery_method ?: 'Offline'),
            'sub_delivery_method'       => optional($training)->sub_delivery_method ?: '-',
            'duration'                  => $durationFormatted,
            'duration_seconds'          => $durationSeconds,
            'duration_sort'             => $durationSeconds,
            'learning_hour'             => number_format($learningHours, 2) . ' h',
            'learning_hours'            => $learningHours,
            'learning_hour_value'       => $learningHours,
            'progress'                  => (int) round($progress),
            'start_date'                => $startDateStr,
            'end_date'                  => optional($training)->end_date ? Carbon::parse($training->end_date)->format('Y-m-d') : ($finishDateStr ?: $startDateStr),
            'finish_date'               => $finishDateStr,
            'start_datetime'            => $startDatetimeStr,
            'end_datetime'              => $endDatetimeStr,
            'start_time'                => $startTime ?: '-',
            'end_time'                  => $endTime ?: '-',
            'time_display'              => $timeDisplay,
            'date_display'              => $dateTimeDisplay,
            'score'                     => $score,
            'assessment_score'          => $score,
            'min_score'                 => (int) (optional($training)->passing_grade ?? optional($training)->min_score ?? 70),
            'certificate'               => $otu->certificate,
            'certificate_institution'   => $certInstitution,
            'certificate_issued_date'   => $certIssuedDate,
            'certificate_expired_date'  => $certExpiredDate,
            'has_certificate'           => $hasCertificate,
            'is_eligible'               => $isEligible,
            'cert_state'                => $certState,
            'cert_reason'               => $certReason,
            'cert_action_url'           => $certActionUrl,
            'cert_is_form'              => false,
            'attendance'                => $attendanceStatus,
            'attendance_status'         => $attendanceStatus,
            'attendance_reason'         => $rawAttendanceReason ?: '-',
            'raw_attendance_reason'     => $rawAttendanceReason,
            'attendance_reason_display' => $attendanceReasonDisplay,
            'venue'                     => $training->venue ?: ($training->location ?: '-'),
            'instructor'                => $training->instructor ?: '-',
            'provider'                  => $training->provider ?: ($training->provider_name ?: '-'),
            'provider_type'             => $training->provider ?: '-',
            'provider_name'             => $training->provider_name ?: ($training->provider ?: '-'),
            'raw_provider_name'         => $training->provider_name ?: '-',

            // Evaluation (Kirkpatrick L1 - L3)
            'l1_program'           => $otu->l1_program ?: '-',
            'l2_numerical'         => $l2Num !== null ? round($l2Num, 2) : null,
            'l2_numerical_display' => $l2Num !== null ? (string) round($l2Num, 2) : '-',
            'l2_verbal'            => $otu->l2_verbal ?: '-',
            'l3_numerical'         => $l3Num !== null ? round($l3Num, 2) : null,
            'l3_numerical_display' => $l3Num !== null ? (string) round($l3Num, 2) : '-',
            'l3_verbal'            => $otu->l3_verbal ?: '-',
            'pic'                  => $otu->pic ?: '-',

            // Employee Snapshot at Training Time
            'employee_id'          => $otu->employee_id ?: '-',
            'user_name'            => $otu->name ?: '-',
            'participant_name'     => $otu->name ?: '-',
            'grade'                => $otu->grade ?: '-',
            'job_position'         => $otu->job_position ?: '-',
            'team'                 => $otu->team ?: '-',
            'department'           => $otu->department ?: '-',
            'company'              => $otu->company ?: '-',

            // Obligation / Service Bond
            'bond_status'          => $training->bond_status ?: ($otu->bond_status ?: '-'),
            'bond_duration'        => $training->bond_duration ?? ($otu->bond_duration ?? null),
            'bond_end_date'        => $training->bond_end_date ? Carbon::parse($training->bond_end_date)->format('Y-m-d') : ($otu->bond_end_date ? Carbon::parse($otu->bond_end_date)->format('Y-m-d') : ''),
            'bond_end_date_display'=> $bondEndDateStr,
            'bond_amount_idr'      => $otu->bond_amount_idr ?? ($training->bond_amount_idr ?? ($otu->bond_amount ?? null)),
            'bond_amount'          => $training->bond_amount ?? ($bondAmount ?: null),
            'bond_amount_formatted'=> $bondAmountFormatted,
        ];
    }

    /**
     * Apply filter criteria on offline rows collection.
     */
    public function applyFilters(Collection $rows, array $filters = []): Collection
    {
        $search = mb_strtolower(trim((string) ($filters['search'] ?? request('record_search') ?? request('search') ?? request('q') ?? '')));
        $type = (string) ($filters['type'] ?? request('record_filter_type') ?? request('type') ?? 'all');
        $classification = (string) ($filters['classification'] ?? request('record_filter_classification') ?? request('classification') ?? 'all');
        $year = (string) ($filters['year'] ?? request('record_filter_year') ?? request('year') ?? 'all');
        $rawMonth = (string) ($filters['month'] ?? request('record_filter_month') ?? request('month') ?? 'all');
        $month = LearningFilterHelper::resolveMonth($rawMonth);

        // More Filters
        $category = (string) ($filters['category'] ?? request('record_filter_category') ?? request('category') ?? 'all');
        $subClassification = (string) ($filters['sub_classification'] ?? request('record_filter_sub_classification') ?? request('sub_classification') ?? 'all');
        $batch = (string) ($filters['batch'] ?? request('record_filter_batch') ?? request('batch') ?? 'all');
        $deliveryMethod = (string) ($filters['delivery_method'] ?? request('record_filter_delivery_method') ?? request('delivery_method') ?? 'all');
        $provider = (string) ($filters['provider'] ?? request('record_filter_provider') ?? request('provider') ?? 'all');
        $attendance = (string) ($filters['attendance'] ?? request('record_filter_attendance') ?? request('attendance') ?? 'all');
        $certificate = (string) ($filters['certificate'] ?? request('record_filter_certificate') ?? request('certificate') ?? 'all');

        $filtered = $rows->filter(function (array $row) use (
            $search, $type, $classification, $year, $month,
            $category, $subClassification, $batch, $deliveryMethod, $provider, $attendance, $certificate
        ) {
            // 1. Search
            if ($search !== '') {
                $searchContent = mb_strtolower(implode(' ', [
                    $row['title'] ?? '',
                    $row['subtitle'] ?? '',
                    $row['type'] ?? '',
                    $row['category'] ?? '',
                    $row['classification'] ?? '',
                    $row['sub_classification'] ?? '',
                    $row['batch'] ?? '',
                    $row['delivery_method'] ?? '',
                    $row['provider'] ?? '',
                    $row['venue'] ?? '',
                    $row['instructor'] ?? '',
                ]));
                if (!str_contains($searchContent, $search)) {
                    return false;
                }
            }

            // 2. Type filter
            if ($type !== 'all' && strtolower(trim((string) ($row['type'] ?? ''))) !== strtolower(trim($type))) {
                return false;
            }

            // 3. Classification filter
            if ($classification !== 'all' && (string) ($row['classification'] ?? '') !== $classification) {
                return false;
            }

            // 4. Year filter
            if ($year !== 'all') {
                $finishDate = !empty($row['finish_date']) ? Carbon::parse($row['finish_date']) : null;
                $startDate = !empty($row['start_date']) ? Carbon::parse($row['start_date']) : null;
                $rowYear = $finishDate ? (string) $finishDate->year : ($startDate ? (string) $startDate->year : '');
                if ($rowYear !== $year) {
                    return false;
                }
            }

            // 5. Month filter
            if ($month !== null) {
                $finishDate = !empty($row['finish_date']) ? Carbon::parse($row['finish_date']) : null;
                $startDate = !empty($row['start_date']) ? Carbon::parse($row['start_date']) : null;
                $rowMonth = $finishDate ? (int) $finishDate->month : ($startDate ? (int) $startDate->month : null);
                if ($rowMonth !== $month) {
                    return false;
                }
            }

            // 6. Category filter
            if ($category !== 'all' && strtolower(trim((string) ($row['category'] ?? ''))) !== strtolower(trim($category))) {
                return false;
            }

            // 7. Sub Classification filter
            if ($subClassification !== 'all' && strtolower(trim((string) ($row['sub_classification'] ?? ''))) !== strtolower(trim($subClassification))) {
                return false;
            }

            // 8. Batch filter
            if ($batch !== 'all' && strtolower(trim((string) ($row['batch'] ?? ''))) !== strtolower(trim($batch))) {
                return false;
            }

            // 9. Delivery Method filter
            if ($deliveryMethod !== 'all' && strtolower(trim((string) ($row['delivery_method'] ?? ''))) !== strtolower(trim($deliveryMethod))) {
                return false;
            }

            // 10. Provider filter
            if ($provider !== 'all' && strtolower(trim((string) ($row['provider'] ?? ''))) !== strtolower(trim($provider))) {
                return false;
            }

            // 11. Attendance filter (all, present/yes, absent/no)
            if ($attendance !== 'all') {
                $normAttendance = strtolower(trim($attendance));
                $rowAtt = strtolower(trim((string) ($row['attendance_status'] ?? '')));
                if (in_array($normAttendance, ['present', 'yes', '1', 'attended'], true)) {
                    if (!in_array($rowAtt, ['yes', 'present'], true)) {
                        return false;
                    }
                } elseif (in_array($normAttendance, ['absent', 'no', '0'], true)) {
                    if (!in_array($rowAtt, ['no', 'absent'], true)) {
                        return false;
                    }
                }
            }

            // 12. Certificate filter (all, certified, uncertified / yes, no)
            if ($certificate !== 'all') {
                $normCert = strtolower(trim($certificate));
                $hasCert = (bool) (($row['has_certificate'] ?? false) || ($row['cert_state'] ?? '') === 'ready');
                if (in_array($normCert, ['certified', 'yes', '1'], true)) {
                    if (!$hasCert) {
                        return false;
                    }
                } elseif (in_array($normCert, ['uncertified', 'not certified', 'no', '0'], true)) {
                    if ($hasCert) {
                        return false;
                    }
                }
            }

            return true;
        });

        $sort = (string) ($filters['record_sort'] ?? $filters['sort'] ?? request('record_sort', ''));
        $direction = (string) ($filters['record_sort_direction'] ?? $filters['direction'] ?? request('record_sort_direction', 'asc'));
        $activitySort = (string) ($filters['record_filter_activity_sort'] ?? $filters['activitySort'] ?? request('record_filter_activity_sort') ?? request('activitySort') ?? 'all');

        $sortKeys = [
            'title'          => fn (array $row) => mb_strtolower((string) ($row['title'] ?? '')),
            'category'       => fn (array $row) => mb_strtolower((string) ($row['category'] ?? '')),
            'type'           => fn (array $row) => mb_strtolower((string) ($row['type'] ?? '')),
            'classification' => fn (array $row) => mb_strtolower((string) ($row['classification'] ?? '')),
            'duration'       => fn (array $row) => (float) ($row['duration_seconds'] ?? -1),
            'learning-hour'  => fn (array $row) => (float) ($row['learning_hour_value'] ?? -1),
            'progress'       => fn (array $row) => (float) ($row['progress'] ?? -1),
            'date'           => fn (array $row) => (string) ($row['finish_date'] ?: ($row['start_date'] ?: '')),
            'score'          => fn (array $row) => (float) ($row['score'] ?? -1),
        ];

        if (isset($sortKeys[$sort]) && in_array($direction, ['asc', 'desc'], true)) {
            $filtered = $direction === 'desc'
                ? $filtered->sortByDesc($sortKeys[$sort])
                : $filtered->sortBy($sortKeys[$sort]);
        } elseif ($activitySort === 'oldest') {
            $filtered = $filtered->sortBy(fn (array $r) => $r['finish_date'] ?: ($r['start_date'] ?: '0000-00-00'));
        } elseif ($activitySort === 'newest') {
            $filtered = $filtered->sortByDesc(fn (array $r) => $r['finish_date'] ?: ($r['start_date'] ?: '0000-00-00'));
        } elseif ($activitySort === 'ascending') {
            $filtered = $filtered->sortBy(fn (array $r) => mb_strtolower((string) ($row['title'] ?? '')));
        } elseif ($activitySort === 'descending') {
            $filtered = $filtered->sortByDesc(fn (array $r) => mb_strtolower((string) ($row['title'] ?? '')));
        } else {
            // Default sort: finish_date DESC, start_date DESC
            $filtered = $filtered->sortByDesc(fn (array $r) => $r['finish_date'] ?: ($r['start_date'] ?: '0000-00-00'));
        }

        return $filtered->values();
    }

    /**
     * Get distinct filter dropdown options for the user's offline trainings.
     */
    public function getFilterOptions(int $userId, ?string $userEmpId): array
    {
        $allUserRecords = $this->queryForUser($userId, $userEmpId)
            ->get()
            ->filter(fn (OfflineTrainingUser $otu) => $otu->training !== null);

        $types = $allUserRecords
            ->map(fn ($r) => trim((string) $r->training?->type))
            ->filter()
            ->unique()
            ->sort()
            ->values()
            ->all();

        $classifications = $allUserRecords
            ->map(fn ($r) => trim((string) $r->training?->main_classification))
            ->filter()
            ->unique()
            ->sort()
            ->values()
            ->all();

        $years = $allUserRecords
            ->map(function ($r) {
                if ($r->training?->finish_date) {
                    return Carbon::parse($r->training->finish_date)->format('Y');
                }
                if ($r->training?->start_date) {
                    return Carbon::parse($r->training->start_date)->format('Y');
                }
                return null;
            })
            ->filter()
            ->unique()
            ->sortDesc()
            ->values()
            ->all();

        $categories = $allUserRecords
            ->map(fn ($r) => trim((string) $r->training?->category))
            ->filter()
            ->unique()
            ->sort()
            ->values()
            ->all();

        $subClassifications = $allUserRecords
            ->map(fn ($r) => trim((string) $r->training?->sub_classification))
            ->filter()
            ->unique()
            ->sort()
            ->values()
            ->all();

        $batches = $allUserRecords
            ->map(fn ($r) => trim((string) $r->training?->batch))
            ->filter()
            ->unique()
            ->sort()
            ->values()
            ->all();

        $deliveryMethods = $allUserRecords
            ->map(fn ($r) => trim((string) $r->training?->delivery_method))
            ->filter()
            ->unique()
            ->sort()
            ->values()
            ->all();

        $providers = $allUserRecords
            ->map(fn ($r) => trim((string) ($r->training?->provider_name ?: $r->training?->provider)))
            ->filter()
            ->unique()
            ->sort()
            ->values()
            ->all();

        return [
            'types'              => $types,
            'classifications'    => $classifications,
            'years'              => $years,
            'categories'         => $categories,
            'subClassifications' => $subClassifications,
            'batches'            => $batches,
            'deliveryMethods'    => $deliveryMethods,
            'providers'          => $providers,
        ];
    }
}
