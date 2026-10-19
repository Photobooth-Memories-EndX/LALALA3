<?php

namespace App\Services\Learning;

use App\Http\Controllers\LearningRecordController;
use App\Models\OfflineTrainingUser;
use App\Services\CertificateEligibilityService;
use App\Support\DurationFormatter;
use App\Support\LearningFilterHelper;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

/**
 * Service to combine, normalize and filter Online and Offline Learning Records for an authenticated user.
 */
class UserRecordAllService
{
    public function __construct(
        private readonly LearningRecordController $learningRecordController,
        private readonly UserRecordOfflineService $offlineService,
    ) {
    }

    /**
     * Check if user has ANY online or offline records regardless of filter.
     */
    public function hasAnyRecords(int $userId, ?string $userEmpId, CertificateEligibilityService $eligibilityService): bool
    {
        $hasOnline = $this->learningRecordController->getRawOnlineRows($userId, $eligibilityService)->isNotEmpty();
        $hasOffline = $this->offlineService->queryForUser($userId, $userEmpId)->exists();

        return $hasOnline || $hasOffline;
    }

    /**
     * Get combined and normalized learning records (Online + Offline) for a user.
     */
    public function getRecords(int $userId, ?string $userEmpId, CertificateEligibilityService $eligibilityService, array $filters = []): Collection
    {
        // 1. Get raw Online rows (Content + Playlist)
        $onlineRows = $this->learningRecordController->getRawOnlineRows($userId, $eligibilityService);
        $normalizedOnline = $onlineRows->map(function (array $row) {
            $finishDateStr = !empty($row['finish_date']) ? Carbon::parse($row['finish_date'])->format('Y-m-d') : '';
            $startDateStr = !empty($row['start_date']) ? Carbon::parse($row['start_date'])->format('Y-m-d') : '';

            $dateTimeDisplay = $finishDateStr
                ? Carbon::parse($finishDateStr)->format('d M Y')
                : ($startDateStr ? Carbon::parse($startDateStr)->format('d M Y') : '—');

            $durationSeconds = max(0, (int) ($row['duration_sort'] ?? 0));
            $durationFormatted = $durationSeconds > 0 ? DurationFormatter::fromSeconds($durationSeconds) : '—';

            $learningHourVal = (float) ($row['learning_hour_value'] ?? 0);
            $hasCertificate = ($row['cert_state'] ?? 'none') === 'ready';

            $isOnlinePresent = ((int) ($row['progress'] ?? 0) >= 100);
            $onlineAttendance = $isOnlinePresent ? 'Present' : '-';
            $attendance = $row['attendance'] ?? $onlineAttendance;
            $attendanceStatus = $row['attendance_status'] ?? $attendance;

            return array_merge($row, [
                'unique_id'         => 'online_' . ($row['item_id'] ?? $row['slug'] ?? uniqid()),
                'source'            => 'online',
                'source_label'      => 'Online',
                'training_title'    => $row['title'] ?? '-',
                'requirement'       => $row['requirement'] ?? \App\Models\Content::mapRequirement($row['subtitle'] ?? null),
                'score'             => $row['score'] ?? $row['assessment_score'] ?? null,
                'assessment_score'  => $row['assessment_score'] ?? $row['score'] ?? null,
                'duration'          => $durationFormatted,
                'duration_seconds'  => $durationSeconds,
                'learning_hour'     => number_format($learningHourVal, 2) . ' hrs',
                'learning_hour_value' => $learningHourVal,
                'progress'          => (int) ($row['progress'] ?? 0),
                'attendance'        => $attendance,
                'attendance_status' => $attendanceStatus,
                'date_display'      => $dateTimeDisplay,
                'venue'             => '',
                'instructor'        => '',
                'provider'          => 'OWL Learning Hub',
                'has_certificate'   => $hasCertificate,
                'cert_state'        => $hasCertificate ? 'ready' : 'none',
                'cert_action_url'   => $row['cert_action_url'] ?? null,
                'cert_is_form'      => (bool) ($row['cert_is_form'] ?? false),
                'playlist_id'       => $row['plan_id'] ?? $row['playlist_id'] ?? ($row['playlist_ids'][0] ?? null),
            ]);
        });

        // 2. Get raw Offline rows
        $offlineRaw = $this->offlineService->queryForUser($userId, $userEmpId)
            ->get()
            ->filter(fn (OfflineTrainingUser $otu) => $otu->training !== null);

        $normalizedOffline = $offlineRaw->map(function (OfflineTrainingUser $otu) {
            $row = $this->offlineService->normalizeRow($otu);

            $dur = ($row['duration'] ?? '') !== '-' && !empty($row['duration'])
                ? $row['duration']
                : '—';

            $attStatus = ($row['attendance_status'] ?? '') !== '-' && !empty($row['attendance_status'])
                ? $row['attendance_status']
                : '—';

            return array_merge($row, [
                'unique_id'         => 'offline_' . $otu->id,
                'source'            => 'offline',
                'source_label'      => 'Offline',
                'training_title'    => $row['title'] ?? '-',
                'requirement'       => $row['requirement'] ?? 'Non-Mandatory',
                'score'             => $row['score'] ?? $row['assessment_score'] ?? null,
                'assessment_score'  => $row['assessment_score'] ?? $row['score'] ?? null,
                'duration'          => $dur,
                'attendance_status' => $attStatus,
                'cert_action_url'   => null,
                'cert_is_form'      => false,
            ]);
        });

        // 3. Merge both collections
        $combined = $normalizedOnline->concat($normalizedOffline);

        // 4. Apply combined filters
        return $this->applyFilters($combined, $filters);
    }

    /**
     * Apply filter criteria on combined rows collection.
     */
    public function applyFilters(Collection $rows, array $filters = []): Collection
    {
        $search = mb_strtolower(trim((string) ($filters['search'] ?? request('record_search') ?? request('search') ?? request('q') ?? '')));
        $source = strtolower(trim((string) ($filters['source'] ?? request('record_filter_source') ?? request('source') ?? 'all')));
        $type = (string) ($filters['type'] ?? request('record_filter_type') ?? request('type') ?? 'all');
        $classification = (string) ($filters['classification'] ?? request('record_filter_classification') ?? request('classification') ?? 'all');
        $year = (string) ($filters['year'] ?? request('record_filter_year') ?? request('year') ?? 'all');
        $rawMonth = (string) ($filters['month'] ?? request('record_filter_month') ?? request('month') ?? 'all');
        $month = LearningFilterHelper::resolveMonth($rawMonth);

        // More Filters - General
        $category = (string) ($filters['category'] ?? request('record_filter_category') ?? request('category') ?? 'all');
        $status = (string) ($filters['status'] ?? request('record_filter_status') ?? request('status') ?? request('progress_status') ?? 'all');

        $rawDurMin = $filters['duration_min'] ?? request('record_filter_duration_min') ?? request('duration_min') ?? null;
        $durMin = ($rawDurMin !== null && $rawDurMin !== '' && is_numeric($rawDurMin)) ? (float) $rawDurMin : null;

        $rawDurMax = $filters['duration_max'] ?? request('record_filter_duration_max') ?? request('duration_max') ?? null;
        $durMax = ($rawDurMax !== null && $rawDurMax !== '' && is_numeric($rawDurMax)) ? (float) $rawDurMax : null;

        $rawLhMin = $filters['lh_min'] ?? request('record_filter_lh_min') ?? request('lh_min') ?? null;
        $lhMin = ($rawLhMin !== null && $rawLhMin !== '' && is_numeric($rawLhMin)) ? (float) $rawLhMin : null;

        $rawLhMax = $filters['lh_max'] ?? request('record_filter_lh_max') ?? request('lh_max') ?? null;
        $lhMax = ($rawLhMax !== null && $rawLhMax !== '' && is_numeric($rawLhMax)) ? (float) $rawLhMax : null;

        $certificate = (string) ($filters['certificate'] ?? request('record_filter_certificate') ?? request('certificate') ?? 'all');

        // More Filters - Offline Specific
        $attendance = (string) ($filters['attendance'] ?? request('record_filter_attendance') ?? request('attendance') ?? 'all');
        $subClassification = (string) ($filters['sub_classification'] ?? request('record_filter_sub_classification') ?? request('sub_classification') ?? 'all');
        $deliveryMethod = (string) ($filters['delivery_method'] ?? request('record_filter_delivery_method') ?? request('delivery_method') ?? 'all');
        $provider = (string) ($filters['provider'] ?? request('record_filter_provider') ?? request('provider') ?? 'all');

        $filtered = $rows->filter(function (array $row) use (
            $search, $source, $type, $classification, $year, $month,
            $category, $status, $durMin, $durMax, $lhMin, $lhMax, $certificate,
            $attendance, $subClassification, $deliveryMethod, $provider
        ) {
            // 1. Source filter (Online | Offline)
            if ($source !== 'all' && $source !== '') {
                if (strtolower((string) ($row['source'] ?? '')) !== $source) {
                    return false;
                }
            }

            // 2. Search keyword
            if ($search !== '') {
                $searchContent = mb_strtolower(implode(' ', [
                    $row['title'] ?? '',
                    $row['subtitle'] ?? '',
                    $row['requirement'] ?? '',
                    $row['type'] ?? '',
                    $row['category'] ?? '',
                    $row['classification'] ?? '',
                    $row['sub_classification'] ?? '',
                    $row['delivery_method'] ?? '',
                    $row['provider'] ?? '',
                    $row['source'] ?? '',
                    $row['venue'] ?? '',
                    $row['instructor'] ?? '',
                ]));
                if (!str_contains($searchContent, $search)) {
                    return false;
                }
            }

            // 3. Type filter
            if ($type !== 'all' && $type !== '') {
                if (strtolower(trim((string) ($row['type'] ?? ''))) !== strtolower(trim($type))) {
                    return false;
                }
            }

            // 4. Category filter
            if ($category !== 'all' && $category !== '') {
                if (strtolower(trim((string) ($row['category'] ?? ''))) !== strtolower(trim($category))) {
                    return false;
                }
            }

            // 5. Classification filter
            if ($classification !== 'all' && $classification !== '') {
                if ((string) ($row['classification'] ?? '') !== $classification) {
                    return false;
                }
            }

            // 6. Year filter
            if ($year !== 'all' && $year !== '') {
                $finishDate = !empty($row['finish_date']) ? Carbon::parse($row['finish_date']) : null;
                $startDate = !empty($row['start_date']) ? Carbon::parse($row['start_date']) : null;
                $rowYear = $finishDate ? (string) $finishDate->year : ($startDate ? (string) $startDate->year : '');
                if ($rowYear !== $year) {
                    return false;
                }
            }

            // 7. Month filter
            if ($month !== null) {
                $finishDate = !empty($row['finish_date']) ? Carbon::parse($row['finish_date']) : null;
                $startDate = !empty($row['start_date']) ? Carbon::parse($row['start_date']) : null;
                $rowMonth = $finishDate ? (int) $finishDate->month : ($startDate ? (int) $startDate->month : null);
                if ($rowMonth !== $month) {
                    return false;
                }
            }

            // 8. Learning Status / Progress filter (General)
            if ($status !== 'all' && $status !== '') {
                $prog = (int) round((float) ($row['progress'] ?? 0));
                $normStatus = strtolower(trim($status));
                if ($normStatus === 'completed' && $prog < 100) {
                    return false;
                } elseif ($normStatus === 'in_progress' && ($prog <= 0 || $prog >= 100)) {
                    return false;
                } elseif ($normStatus === 'not_started' && $prog > 0) {
                    return false;
                }
            }

            // 9. Duration Range filter (General - minutes converted to seconds)
            $durationSeconds = max(0, (int) ($row['duration_seconds'] ?? 0));
            if ($durMin !== null) {
                $minSeconds = $durMin * 60;
                if ($durationSeconds < $minSeconds) {
                    return false;
                }
            }
            if ($durMax !== null) {
                $maxSeconds = $durMax * 60;
                if ($durationSeconds > $maxSeconds) {
                    return false;
                }
            }

            // 10. Learning Hours Range filter (General)
            $lhValue = (float) ($row['learning_hour_value'] ?? 0);
            if ($lhMin !== null && $lhValue < $lhMin) {
                return false;
            }
            if ($lhMax !== null && $lhValue > $lhMax) {
                return false;
            }

            // 11. Certificate filter (General)
            if ($certificate !== 'all' && $certificate !== '') {
                $normCert = strtolower(trim($certificate));
                $hasCert = (bool) ($row['has_certificate'] ?? false);
                if (in_array($normCert, ['certified', 'yes', '1', 'ready'], true)) {
                    if (!$hasCert) {
                        return false;
                    }
                } elseif (in_array($normCert, ['uncertified', 'not certified', 'no', '0', 'none'], true)) {
                    if ($hasCert) {
                        return false;
                    }
                }
            }

            // 12. Attendance filter (Offline Specific)
            if ($attendance !== 'all' && $attendance !== '') {
                $normAttendance = strtolower(trim($attendance));
                $rowAttendanceStatus = strtolower(trim((string) ($row['attendance_status'] ?? '')));
                if (in_array($normAttendance, ['present', '1', 'attended'], true)) {
                    if ($rowAttendanceStatus !== 'present') {
                        return false;
                    }
                } elseif (in_array($normAttendance, ['absent', '0'], true)) {
                    if ($rowAttendanceStatus !== 'absent') {
                        return false;
                    }
                }
            }

            // 13. Sub Classification filter (Offline Specific)
            if ($subClassification !== 'all' && $subClassification !== '') {
                $rowSubClass = strtolower(trim((string) ($row['sub_classification'] ?? '')));
                if ($rowSubClass !== strtolower(trim($subClassification))) {
                    return false;
                }
            }

            // 14. Delivery Method filter (Offline Specific)
            if ($deliveryMethod !== 'all' && $deliveryMethod !== '') {
                $rowMethod = strtolower(trim((string) ($row['delivery_method'] ?? '')));
                if ($rowMethod !== strtolower(trim($deliveryMethod))) {
                    return false;
                }
            }

            // 15. Provider filter (Offline Specific)
            if ($provider !== 'all' && $provider !== '') {
                $rowProv = strtolower(trim((string) ($row['provider'] ?? '')));
                if ($rowProv !== strtolower(trim($provider))) {
                    return false;
                }
            }

            return true;
        });

        $sort = (string) ($filters['record_sort'] ?? $filters['sort'] ?? request('record_sort', ''));
        $direction = (string) ($filters['record_sort_direction'] ?? $filters['direction'] ?? request('record_sort_direction', 'asc'));
        $activitySort = (string) ($filters['record_filter_activity_sort'] ?? $filters['activitySort'] ?? request('record_filter_activity_sort') ?? request('activitySort') ?? 'all');

        $sortKeys = [
            'title'          => fn (array $row) => mb_strtolower((string) ($row['training_title'] ?: ($row['title'] ?? ''))),
            'category'       => fn (array $row) => mb_strtolower((string) ($row['category'] ?? '')),
            'type'           => fn (array $row) => mb_strtolower((string) ($row['type'] ?? '')),
            'classification' => fn (array $row) => mb_strtolower((string) ($row['classification'] ?? '')),
            'duration'       => fn (array $row) => (float) ($row['duration_seconds'] ?? -1),
            'learning-hour'  => fn (array $row) => (float) ($row['learning_hour_value'] ?? -1),
            'progress'       => fn (array $row) => (float) ($row['progress'] ?? -1),
            'attendance'     => fn (array $row) => mb_strtolower((string) ($row['attendance_status'] ?: ($row['attendance'] ?? ''))),
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
            $filtered = $filtered->sortBy(fn (array $r) => mb_strtolower((string) ($r['training_title'] ?: ($r['title'] ?? ''))));
        } elseif ($activitySort === 'descending') {
            $filtered = $filtered->sortByDesc(fn (array $r) => mb_strtolower((string) ($r['training_title'] ?: ($r['title'] ?? ''))));
        } else {
            // Default sort: finish_date DESC, start_date DESC
            $filtered = $filtered->sortByDesc(fn (array $r) => $r['finish_date'] ?: ($r['start_date'] ?: '0000-00-00'));
        }

        return $filtered->values();
    }

    /**
     * Get distinct filter dropdown options for the combined dataset.
     */
    public function getFilterOptions(Collection $combinedUnfilteredRows): array
    {
        $types = $combinedUnfilteredRows
            ->map(fn ($r) => trim((string) ($r['type'] ?? '')))
            ->filter()
            ->unique()
            ->sort()
            ->values()
            ->all();

        $categories = $combinedUnfilteredRows
            ->map(fn ($r) => trim((string) ($r['category'] ?? '')))
            ->filter()
            ->unique()
            ->sort()
            ->values()
            ->all();

        $classifications = $combinedUnfilteredRows
            ->map(fn ($r) => trim((string) ($r['classification'] ?? '')))
            ->filter()
            ->unique()
            ->sort()
            ->values()
            ->all();

        $years = $combinedUnfilteredRows
            ->map(function ($r) {
                if (!empty($r['finish_date'])) {
                    return Carbon::parse($r['finish_date'])->format('Y');
                }
                if (!empty($r['start_date'])) {
                    return Carbon::parse($r['start_date'])->format('Y');
                }
                return null;
            })
            ->filter()
            ->unique()
            ->sortDesc()
            ->values()
            ->all();

        $subClassifications = $combinedUnfilteredRows
            ->map(fn ($r) => trim((string) ($r['sub_classification'] ?? '')))
            ->filter(fn ($v) => $v !== '' && $v !== '-')
            ->unique()
            ->sort()
            ->values()
            ->all();

        $deliveryMethods = $combinedUnfilteredRows
            ->map(fn ($r) => trim((string) ($r['delivery_method'] ?? '')))
            ->filter(fn ($v) => $v !== '' && $v !== '-')
            ->unique()
            ->sort()
            ->values()
            ->all();

        $providers = $combinedUnfilteredRows
            ->map(fn ($r) => trim((string) ($r['provider'] ?? '')))
            ->filter(fn ($v) => $v !== '' && $v !== '-')
            ->unique()
            ->sort()
            ->values()
            ->all();

        return [
            'sources'            => ['all' => 'All Sources', 'online' => 'Online', 'offline' => 'Offline'],
            'types'              => $types,
            'categories'         => $categories,
            'classifications'    => $classifications,
            'years'              => $years,
            'subClassifications' => $subClassifications,
            'deliveryMethods'    => $deliveryMethods,
            'providers'          => $providers,
        ];
    }
}
