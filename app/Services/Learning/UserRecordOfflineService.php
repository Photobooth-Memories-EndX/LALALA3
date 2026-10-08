<?php

namespace App\Services\Learning;

use App\Models\OfflineTraining;
use App\Models\OfflineTrainingUser;
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
     * Get and normalize all offline records for user with filters applied.
     */
    public function getRecords(int $userId, ?string $userEmpId, array $filters = []): Collection
    {
        $rawRecords = $this->queryForUser($userId, $userEmpId)
            ->get()
            ->filter(fn (OfflineTrainingUser $otu) => $otu->training !== null);

        $normalized = $rawRecords->map(fn (OfflineTrainingUser $otu) => $this->normalizeRow($otu));

        return $this->applyFilters($normalized, $filters);
    }

    /**
     * Normalize an OfflineTrainingUser record into standard row format.
     */
    public function normalizeRow(OfflineTrainingUser $otu): array
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
        $timeDisplay = ($startTime && $endTime) ? "{$startTime} - {$endTime}" : ($startTime ?: ($endTime ?: '-'));

        $progress = (float) ($otu->progress ?? 0);
        $score = $otu->assessment_score !== null ? (float) $otu->assessment_score : null;

        $attendanceRaw = $otu->attendance;
        if (is_string($attendanceRaw) && !in_array($attendanceRaw, ['0', '1', ''], true)) {
            $attendanceStatus = ucfirst(trim($attendanceRaw));
        } else {
            $attendanceStatus = match ($attendanceRaw) {
                true, 1, '1' => 'Present',
                false, 0, '0' => 'Absent',
                default => '-',
            };
        }

        $rawAttendanceReason = (!empty($otu->attendance_reason) && trim((string) $otu->attendance_reason) !== '-')
            ? trim((string) $otu->attendance_reason)
            : null;

        if ($rawAttendanceReason !== null && $rawAttendanceReason !== '') {
            $attendanceReasonDisplay = $rawAttendanceReason;
        } elseif ($attendanceStatus === 'Present') {
            $attendanceReasonDisplay = 'Employee attended the training.';
        } elseif ($attendanceStatus === 'Absent') {
            $attendanceReasonDisplay = 'No attendance reason recorded.';
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

        return [
            'id'                        => $otu->id,
            'source'                    => 'offline',
            'row_kind'                  => 'offline',
            'title'                     => $training->course_name ?: 'Offline Training',
            'subtitle'                  => $training->sub_course_name ?: ($training->provider_name ?: ''),
            'course_name'               => $training->course_name ?: ($training->title ?: 'Offline Training'),
            'sub_course_name'           => $training->sub_course_name ?: '',
            'type'                      => $training->type ?: 'Offline',
            'category'                  => $training->category ?: 'Offline',
            'level'                     => $training->level ?: '-',
            'batch'                     => $training->batch ?: '-',
            'classification'            => $training->main_classification ?: 'Unclassified',
            'main_classification'       => $training->main_classification ?: 'Unclassified',
            'sub_classification'        => $training->sub_classification ?: '-',
            'delivery_method'           => $training->delivery_method ?: '-',
            'sub_delivery_method'       => $training->sub_delivery_method ?: '-',
            'duration'                  => $durationFormatted,
            'duration_seconds'          => $durationSeconds,
            'duration_sort'             => $durationSeconds,
            'learning_hour'             => number_format($learningHours, 2) . ' hrs',
            'learning_hour_value'       => $learningHours,
            'progress'                  => (int) round($progress),
            'start_date'                => $startDateStr,
            'finish_date'               => $finishDateStr,
            'start_time'                => $startTime ?: '-',
            'end_time'                  => $endTime ?: '-',
            'time_display'              => $timeDisplay,
            'date_display'              => $dateTimeDisplay,
            'score'                     => $score,
            'certificate'               => $otu->certificate,
            'attendance'                => $attendanceRaw !== null ? (bool) $attendanceRaw : null,
            'attendance_status'         => $attendanceStatus,
            'attendance_reason'         => $rawAttendanceReason ?: '-',
            'raw_attendance_reason'     => $rawAttendanceReason,
            'attendance_reason_display' => $attendanceReasonDisplay,
            'venue'                     => $training->venue ?: ($training->location ?: '-'),
            'instructor'                => $training->instructor ?: '-',
            'provider'                  => $training->provider_name ?: ($training->provider ?: '-'),
            'provider_name'             => $training->provider_name ?: ($training->provider ?: '-'),

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
            'participant_name'     => $otu->name ?: '-',
            'grade'                => $otu->grade ?: '-',
            'job_position'         => $otu->job_position ?: '-',
            'team'                 => $otu->team ?: '-',
            'department'           => $otu->department ?: '-',
            'company'              => $otu->company ?: '-',

            // Obligation / Service Bond
            'bond_status'          => $otu->bond_status ?: '-',
            'bond_end_date'        => $otu->bond_end_date ? Carbon::parse($otu->bond_end_date)->format('Y-m-d') : '',
            'bond_end_date_display'=> $bondEndDateStr,
            'bond_amount'          => $bondAmount,
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

            // 11. Attendance filter (all, present, absent)
            if ($attendance !== 'all') {
                $normAttendance = strtolower(trim($attendance));
                if (in_array($normAttendance, ['present', '1', 'attended'], true)) {
                    if (($row['attendance_status'] ?? '') !== 'Present') {
                        return false;
                    }
                } elseif (in_array($normAttendance, ['absent', '0'], true)) {
                    if (($row['attendance_status'] ?? '') !== 'Absent') {
                        return false;
                    }
                }
            }

            // 12. Certificate filter (all, certified, uncertified / yes, no)
            if ($certificate !== 'all') {
                $normCert = strtolower(trim($certificate));
                $certVal = strtolower(trim((string) ($row['certificate'] ?? '')));
                if (in_array($normCert, ['certified', 'yes', '1'], true)) {
                    if ($certVal !== 'yes') {
                        return false;
                    }
                } elseif (in_array($normCert, ['uncertified', 'not certified', 'no', '0'], true)) {
                    if ($certVal !== 'no') {
                        return false;
                    }
                }
            }

            return true;
        });

        // Default sort: finish_date DESC, start_date DESC
        return $filtered->sortByDesc(fn (array $r) => $r['finish_date'] ?: ($r['start_date'] ?: '0000-00-00'))->values();
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
