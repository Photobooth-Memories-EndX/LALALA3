<?php

namespace App\Services\User;

use App\Models\Classification;
use App\Models\Content;
use App\Models\ContentUser;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;

class UserProfileLearningHistoryService
{
    /**
     * Build the filtered Eloquent query for user's learning history (ContentUser).
     */
    public static function buildQuery(User $user, array $filters = []): Builder
    {
        $query = ContentUser::query()
            ->with([
                'user:id,name,id_emp,ssohash,department,division,team',
                'content:id,title,slug,type,duration,classification_id',
                'content.classification:id,classification_name,slug',
            ])
            ->where('content_users.user_id', $user->id);

        // 1. Search Learning Title
        $rawSearch = $filters['learning_title'] ?? ($filters['title'] ?? ($filters['title_q'] ?? null));
        if ((!filled($rawSearch)) && isset($filters['search'])) {
            $rawSearch = is_array($filters['search']) ? ($filters['search']['value'] ?? '') : $filters['search'];
        }
        $search = is_string($rawSearch) ? trim($rawSearch) : '';
        if ($search !== '') {
            $query->whereHas('content', function ($q) use ($search) {
                $q->where('title', 'like', "%{$search}%");
            });
        }

        // 2. Classification Filter
        $classification = $filters['classification'] ?? ($filters['classification_id'] ?? ($filters['classification_slug'] ?? null));
        if (filled($classification) && $classification !== 'all') {
            $query->whereHas('content.classification', function ($q) use ($classification) {
                if (is_numeric($classification)) {
                    $q->where('id', (int) $classification);
                } else {
                    $q->where('slug', $classification)->orWhere('classification_name', $classification);
                }
            });
        }

        // 3. Status Filter (finished / ongoing)
        $status = strtolower(trim((string) ($filters['status'] ?? 'all')));
        if (filled($status) && $status !== 'all') {
            if (in_array($status, ['finished', 'finish', '1'], true)) {
                $query->where('content_users.finish', 1);
            } elseif (in_array($status, ['ongoing', '0'], true)) {
                $query->where('content_users.finish', 0);
            }
        }

        // 4. Content Type Filter (Video / Document)
        $type = strtolower(trim((string) ($filters['type'] ?? 'all')));
        if (filled($type) && $type !== 'all') {
            $query->whereHas('content', function ($q) use ($type) {
                if ($type === 'video') {
                    $q->whereIn('type', ['Video', 'YouTube']);
                } elseif ($type === 'document' || $type === 'pdf') {
                    $q->where('type', 'Document');
                } else {
                    $q->where('type', $type);
                }
            });
        }

        // 5. Year Filter
        $year = $filters['year'] ?? null;
        if (filled($year) && $year !== 'all') {
            $query->whereYear('content_users.created_at', (int) $year);
        }

        // 6. Date Range (from_date & to_date)
        $fromDate = $filters['from_date'] ?? ($filters['date_from'] ?? null);
        $toDate = $filters['to_date'] ?? ($filters['date_to'] ?? null);
        if (filled($fromDate)) {
            try {
                $fromDate = Carbon::parse(str_replace('/', '-', $fromDate))->format('Y-m-d');
            } catch (\Throwable $e) {
            }
        }
        if (filled($toDate)) {
            try {
                $toDate = Carbon::parse(str_replace('/', '-', $toDate))->format('Y-m-d');
            } catch (\Throwable $e) {
            }
        }
        if (filled($fromDate) && filled($toDate)) {
            $query->whereBetween('content_users.created_at', [$fromDate . ' 00:00:00', $toDate . ' 23:59:59']);
        } elseif (filled($fromDate)) {
            $query->whereDate('content_users.created_at', '>=', $fromDate);
        } elseif (filled($toDate)) {
            $query->whereDate('content_users.created_at', '<=', $toDate);
        }

        // 7. Progress Range
        $progressRange = $filters['progress_range'] ?? null;
        if (filled($progressRange) && $progressRange !== 'all') {
            $progressVal = str_replace([' ', '%'], '', (string) $progressRange);
            switch ($progressVal) {
                case '0':
                    $query->where(function ($q) {
                        $q->where('content_users.progress', 0)->orWhereNull('content_users.progress');
                    });
                    break;
                case '1-25':
                    $query->whereBetween('content_users.progress', [1, 25]);
                    break;
                case '26-50':
                    $query->whereBetween('content_users.progress', [26, 50]);
                    break;
                case '51-75':
                    $query->whereBetween('content_users.progress', [51, 75]);
                    break;
                case '76-99':
                    $query->whereBetween('content_users.progress', [76, 99]);
                    break;
                case '100':
                    $query->where('content_users.progress', 100);
                    break;
            }
        }

        // 8. Duration Filter
        $duration = $filters['duration'] ?? null;
        if (filled($duration) && $duration !== 'all') {
            $durationInput = strtolower(trim((string) $duration));
            $durationRange = match ($durationInput) {
                'lt10', 'under_10', '< 10 minutes', '<10min', '<10' => [null, 600, false, true],
                '10-30', '10_30', '10 - 30 minutes', '10-30min' => [600, 1800, true, false],
                '30-60', '30_60', '30 - 60 minutes', '30-60min' => [1800, 3600, false, false],
                'gt60', 'above_60', '> 1 hour', '>1hour', '>60', '>60min' => [3600, null, false, false],
                default => null,
            };

            if ($durationRange !== null) {
                [$minimum, $maximum, $includeMinimum, $excludeMaximum] = $durationRange;
                $durationInSeconds = "CASE
                    WHEN content.duration IS NULL OR TRIM(content.duration) = '' THEN NULL
                    WHEN LENGTH(content.duration) - LENGTH(REPLACE(content.duration, ':', '')) = 1 THEN
                        CAST(SUBSTRING_INDEX(content.duration, ':', 1) AS UNSIGNED) * 60
                        + CAST(SUBSTRING_INDEX(content.duration, ':', -1) AS UNSIGNED)
                    WHEN LENGTH(content.duration) - LENGTH(REPLACE(content.duration, ':', '')) = 2 THEN
                        CAST(SUBSTRING_INDEX(content.duration, ':', 1) AS UNSIGNED) * 3600
                        + CAST(SUBSTRING_INDEX(SUBSTRING_INDEX(content.duration, ':', 2), ':', -1) AS UNSIGNED) * 60
                        + CAST(SUBSTRING_INDEX(content.duration, ':', -1) AS UNSIGNED)
                    ELSE NULL
                END";

                $query->whereHas('content', function ($q) use ($durationInSeconds, $minimum, $maximum, $includeMinimum, $excludeMaximum) {
                    if ($minimum !== null) {
                        $q->whereRaw($durationInSeconds . ($includeMinimum ? ' >= ?' : ' > ?'), [$minimum]);
                    }
                    if ($maximum !== null) {
                        $q->whereRaw($durationInSeconds . ($excludeMaximum ? ' < ?' : ' <= ?'), [$maximum]);
                    }
                });
            }
        }

        return $query;
    }

    /**
     * Get filter options for UI dropdowns.
     */
    public static function getFilterOptions(User $user): array
    {
        $classifications = Classification::query()
            ->whereNotNull('classification_name')
            ->where('classification_name', '!=', '')
            ->orderBy('classification_name')
            ->get();

        $trainingTypes = Content::query()
            ->whereNotNull('type')
            ->where('type', '!=', '')
            ->distinct()
            ->pluck('type')
            ->filter(fn ($t) => in_array($t, ['Video', 'Document', 'YouTube'], true))
            ->map(fn ($t) => $t === 'YouTube' ? 'Video' : $t)
            ->unique()
            ->values()
            ->toArray();

        if (empty($trainingTypes)) {
            $trainingTypes = ['Document', 'Video'];
        }

        $years = ContentUser::where('user_id', $user->id)
            ->whereNotNull('created_at')
            ->selectRaw('DISTINCT YEAR(created_at) as yr')
            ->orderByDesc('yr')
            ->pluck('yr')
            ->filter()
            ->values()
            ->toArray();

        if (empty($years)) {
            $currentYear = (int) date('Y');
            $years = range($currentYear, $currentYear - 3);
        }

        return [
            'classifications' => $classifications,
            'trainingTypes' => $trainingTypes,
            'years' => $years,
        ];
    }

    /**
     * Parse human-readable active filter labels for PDF export.
     */
    public static function activeFilterLabels(array $filters): array
    {
        $labels = [];

        $rawTitle = $filters['learning_title'] ?? ($filters['title'] ?? null);
        if (is_array($rawTitle)) {
            $rawTitle = $rawTitle['value'] ?? null;
        }
        if (filled($rawTitle) && is_string($rawTitle)) {
            $labels['Title'] = trim($rawTitle);
        }

        if (filled($filters['classification'] ?? null) && $filters['classification'] !== 'all') {
            $cl = Classification::find($filters['classification']);
            $labels['Classification'] = $cl ? $cl->classification_name : $filters['classification'];
        }

        if (filled($filters['status'] ?? null) && $filters['status'] !== 'all') {
            $labels['Status'] = ucfirst($filters['status']);
        }

        if (filled($filters['type'] ?? null) && $filters['type'] !== 'all') {
            $labels['Type'] = ucfirst($filters['type']);
        }

        if (filled($filters['year'] ?? null) && $filters['year'] !== 'all') {
            $labels['Year'] = $filters['year'];
        }

        if (filled($filters['from_date'] ?? null) || filled($filters['to_date'] ?? null)) {
            $from = $filters['from_date'] ?? 'Start';
            $to = $filters['to_date'] ?? 'End';
            $labels['Date Range'] = "{$from} - {$to}";
        }

        if (filled($filters['progress_range'] ?? null) && $filters['progress_range'] !== 'all') {
            $labels['Progress'] = $filters['progress_range'] . '%';
        }

        if (filled($filters['duration'] ?? null) && $filters['duration'] !== 'all') {
            $labels['Duration'] = $filters['duration'];
        }

        return $labels;
    }
}
