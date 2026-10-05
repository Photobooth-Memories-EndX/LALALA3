<?php

namespace App\Services\Learning;

use App\Http\Controllers\LearningRecordController;
use App\Models\OfflineTrainingUser;
use App\Services\CertificateEligibilityService;
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
     * Get combined and normalized learning records (Online + Offline) for a user.
     */
    public function getRecords(int $userId, ?string $userEmpId, CertificateEligibilityService $eligibilityService, array $filters = []): Collection
    {
        // 1. Get raw Online rows (Content + Playlist)
        $onlineRows = $this->learningRecordController->getRawOnlineRows($userId, $eligibilityService);
        $normalizedOnline = $onlineRows->map(function (array $row) {
            $finishDateStr = !empty($row['finish_date']) ? Carbon::parse($row['finish_date'])->format('Y-m-d') : '';
            $startDateStr = !empty($row['start_date']) ? Carbon::parse($row['start_date'])->format('Y-m-d') : '';

            $dateTimeDisplay = $finishDateStr ? Carbon::parse($finishDateStr)->format('d M Y') : '-';

            return array_merge($row, [
                'source'       => 'online',
                'source_label' => 'Online',
                'date_display' => $dateTimeDisplay,
                'venue'        => '',
                'instructor'   => '',
                'provider'     => 'OWL Learning Hub',
            ]);
        });

        // 2. Get raw Offline rows
        $offlineRaw = $this->offlineService->queryForUser($userId, $userEmpId)
            ->get()
            ->filter(fn (OfflineTrainingUser $otu) => $otu->training !== null);

        $normalizedOffline = $offlineRaw->map(fn (OfflineTrainingUser $otu) => $this->offlineService->normalizeRow($otu));

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

        $filtered = $rows->filter(function (array $row) use ($search, $source, $type, $classification, $year, $month) {
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
                    $row['type'] ?? '',
                    $row['category'] ?? '',
                    $row['classification'] ?? '',
                    $row['provider'] ?? '',
                    $row['source'] ?? '',
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

            // 4. Classification filter
            if ($classification !== 'all' && $classification !== '') {
                if ((string) ($row['classification'] ?? '') !== $classification) {
                    return false;
                }
            }

            // 5. Year filter
            if ($year !== 'all' && $year !== '') {
                $finishDate = !empty($row['finish_date']) ? Carbon::parse($row['finish_date']) : null;
                $startDate = !empty($row['start_date']) ? Carbon::parse($row['start_date']) : null;
                $rowYear = $finishDate ? (string) $finishDate->year : ($startDate ? (string) $startDate->year : '');
                if ($rowYear !== $year) {
                    return false;
                }
            }

            // 6. Month filter
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

        return [
            'sources'         => ['all' => 'All Sources', 'online' => 'Online', 'offline' => 'Offline'],
            'types'           => $types,
            'classifications' => $classifications,
            'years'           => $years,
        ];
    }
}
