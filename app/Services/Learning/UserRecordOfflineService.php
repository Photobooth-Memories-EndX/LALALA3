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
            ->whereNull('deleted_at');
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

        $durationSeconds = (int) ($training->duration_seconds ?? 0);
        $durationFormatted = $durationSeconds > 0
            ? DurationFormatter::formatForLearningRecord(DurationFormatter::fromSeconds($durationSeconds))
            : '-';

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

        $progress = (float) ($otu->progress ?? 0);
        $score = $otu->assessment_score !== null ? (float) $otu->assessment_score : null;

        return [
            'id'                   => $otu->id,
            'source'               => 'offline',
            'row_kind'             => 'offline',
            'title'                => $training->course_name ?: 'Offline Training',
            'subtitle'             => $training->sub_course_name ?: ($training->provider_name ?: ''),
            'type'                 => $training->type ?: 'Offline',
            'category'             => $training->category ?: 'Offline',
            'classification'       => $training->main_classification ?: 'Unclassified',
            'sub_classification'   => $training->sub_classification ?: '',
            'duration'             => $durationFormatted,
            'duration_seconds'     => $durationSeconds,
            'duration_sort'        => $durationSeconds,
            'learning_hour'        => number_format($learningHours, 2) . ' hrs',
            'learning_hour_value'  => $learningHours,
            'progress'             => (int) round($progress),
            'start_date'           => $startDateStr,
            'finish_date'          => $finishDateStr,
            'date_display'         => $dateTimeDisplay,
            'score'                => $score,
            'certificate'          => $otu->certificate,
            'attendance'           => (bool) $otu->attendance,
            'venue'                => $training->venue ?: '',
            'instructor'           => $training->instructor ?: '',
            'provider'             => $training->provider_name ?: ($training->provider ?: ''),
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

        $filtered = $rows->filter(function (array $row) use ($search, $type, $classification, $year, $month) {
            // 1. Search
            if ($search !== '') {
                $searchContent = mb_strtolower(implode(' ', [
                    $row['title'] ?? '',
                    $row['subtitle'] ?? '',
                    $row['type'] ?? '',
                    $row['category'] ?? '',
                    $row['classification'] ?? '',
                    $row['sub_classification'] ?? '',
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

        return [
            'types'           => $types,
            'classifications' => $classifications,
            'years'           => $years,
        ];
    }
}
