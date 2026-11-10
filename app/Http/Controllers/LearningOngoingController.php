<?php

namespace App\Http\Controllers;

use App\Models\ContentUser;
use App\Services\Learning\OngoingContentService;
use App\Services\Learning\OngoingPlaylistService;
use App\Support\LearningFilterHelper;
use App\Support\DurationFormatter;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Facades\Excel;
use PhpOffice\PhpSpreadsheet\Shared\Date as ExcelDate;

/**
 * Learning On Going — pembelajaran yang sedang berjalan.
 *
 * Terdiri dari DUA mode yang benar-benar terpisah (tidak ada lagi konsep "All"):
 *
 *  - Mode Content  -> content()  : hanya Video/Document (OngoingContentService)
 *  - Mode Playlist -> playlist() : hanya Playlist       (OngoingPlaylistService)
 *
 * Masing-masing punya route, filter state, dan view partial sendiri.
 * Content tidak mengetahui Playlist; Playlist tidak mengetahui Content.
 * Dipanggil via route langsung ataupun LearningController::dashboardPartial.
 */
class LearningOngoingController extends Controller
{
    public function download(Request $request)
    {
        $request->validate([
            'ongoing_mode' => 'sometimes|in:content,playlist,mooc',
            'format' => 'sometimes|in:excel,xlsx,pdf,csv',
        ]);
        $mode = $request->input('ongoing_mode', 'content');
        $format = $request->input('format', 'excel');
        if (!$request->has('ongoing_search') && $request->has('q')) {
            $request->query->set('ongoing_search', $request->query('q'));
        }
        // Only Ongoing parameters may determine this dataset. Record filters are unrelated.
        $request->query->replace(collect($request->query())->filter(
            fn ($value, $key) => str_starts_with($key, 'ongoing_')
        )->all());
        $filters = $mode === 'playlist' ? $this->playlistFilterState() : $this->contentFilterState();
        $user = $request->user();
        $cards = $mode === 'playlist'
            ? $this->playlistService->ongoingCards($this->playlistService->userPlaylists((int) $user->id), $filters)
            : ($mode === 'mooc'
                ? $this->contentService->moocCards((int) $user->id, $filters)
                : $this->contentService->ongoingCards((int) $user->id, $filters));

        $cards = $this->sortOngoingCards($cards, $filters['progress_sort'], $filters['activity_sort']);
        $export = new \App\Exports\ExportMyLearningOngoing($cards, $user);
        $filename = 'MyLearningOngoing_' . ucfirst($mode) . '_' . now()->format('Ymd_His');

        if ($format === 'pdf') {
            return Pdf::loadView('user.learning.exports.ongoing-pdf', [
                'rows' => $this->displayOngoingRows($export->array()),
                'generatedAt' => now(),
                'mode' => ucfirst($mode),
            ])->setPaper('a4', 'landscape')->download($filename . '.pdf');
        }

        if ($format === 'csv') {
            $rows = $this->displayOngoingRows($export->array());

            return response()->streamDownload(function () use ($rows) {
                $output = fopen('php://output', 'wb');
                fwrite($output, "\xEF\xBB\xBF");
                foreach ($rows as $row) {
                    fputcsv($output, array_map($this->safeCsvCell(...), $row));
                }
                fclose($output);
            }, $filename . '.csv', ['Content-Type' => 'text/csv; charset=UTF-8']);
        }

        return Excel::download($export, $filename . '.xlsx');
    }

    /** Convert Excel serials into readable values for CSV/PDF while keeping the same 13-column export. */
    private function displayOngoingRows(array $rows): array
    {
        foreach ($rows as $rowIndex => $row) {
            if ($rowIndex === 0) {
                continue;
            }

            foreach ([5 => 'duration', 7 => 'percent', 8 => 'date', 9 => 'date'] as $column => $kind) {
                if (!isset($row[$column]) || !is_numeric($row[$column])) {
                    continue;
                }

                $row[$column] = match ($kind) {
                    'duration' => DurationFormatter::fromSeconds((int) round((float) $row[$column] * 86400)),
                    'percent' => rtrim(rtrim(number_format((float) $row[$column] * 100, 2, '.', ''), '0'), '.') . '%',
                    'date' => ExcelDate::excelToDateTimeObject((float) $row[$column])->format('Y-m-d H:i'),
                };
            }
            $rows[$rowIndex] = $row;
        }

        return $rows;
    }

    private function safeCsvCell(mixed $value): mixed
    {
        if (is_string($value) && preg_match('/^\s*[=+@\\-]/u', $value)) {
            return "'" . $value;
        }

        return $value;
    }

    public function __construct(
        private OngoingPlaylistService $playlistService,
        private OngoingContentService $contentService,
    ) {
    }

    /**
     * AJAX live autocomplete search for ongoing learning items.
     * Filtered ONLY from the user's active ongoing dataset.
     */
    public function autocomplete(Request $request): JsonResponse
    {
        $query = trim((string) $request->get('q', ''));
        if ($query === '') {
            return response()->json([]);
        }

        $userId = (int) Auth::id();
        $mode = (string) $request->get('mode', 'content');
        $results = collect();

        // 1. Search Active Ongoing Content
        if ($mode !== 'playlist') {
            $contentUsers = ContentUser::with(['content.classification'])
                ->where('user_id', $userId)
                ->where('finish', '0')
                ->where(function ($q) {
                    $q->whereNull('learning_status')->orWhere('learning_status', '!=', 'finished');
                })
                ->where('progress', '<', 100)
                ->where('progress', '>=', 5)
                ->whereNotExists(function ($q) {
                    $q->from('trainingplandetail')
                        ->whereColumn('trainingplandetail.content_id', 'content_users.content_id');
                })
                ->withAvailableContent()
                ->whereHas('content', function ($q) use ($query) {
                    $q->where('title', 'LIKE', "%{$query}%")
                        ->orWhereHas('classification', function ($cq) use ($query) {
                            $cq->where('classification_name', 'LIKE', "%{$query}%");
                        });
                })
                ->limit(8)
                ->get();

            foreach ($contentUsers as $cu) {
                if ($cu->content) {
                    $results->push([
                        'id'       => $cu->content->id,
                        'title'    => $cu->content->title,
                        'slug'     => $cu->content->slug,
                        'category' => $cu->content->classification->classification_name ?? 'Content',
                        'type'     => strtolower($cu->content->type ?? 'content'),
                        'progress' => (int) $cu->progress,
                        'url'      => route('content.user.view', $cu->content->slug),
                    ]);
                }
            }
        }

        // 2. Search Active Ongoing Playlist
        if ($mode === 'playlist' || ($mode === 'all' && $results->count() < 8)) {
            $userPlaylists = $this->playlistService->userPlaylists($userId)
                ->filter(fn ($plan) => (int) ($plan->finished_items ?? 0) >= 1 && (int) ($plan->progress_percent ?? 0) < 100 && !$plan->is_completed);

            $matchingPlaylists = $userPlaylists->filter(function ($plan) use ($query) {
                return stripos((string) $plan->title, $query) !== false;
            })->take(8);

            if ($matchingPlaylists->isNotEmpty()) {
                $firstSlugs = DB::table('trainingplandetail as tpd')
                    ->join('content as c', 'c.id', '=', 'tpd.content_id')
                    ->whereIn('tpd.trainingplan_id', $matchingPlaylists->pluck('id')->all())
                    ->whereNull('c.deleted_at')
                    ->orderByRaw('CAST(tpd.content_order AS UNSIGNED) ASC')
                    ->get(['tpd.trainingplan_id as plan_id', 'c.slug as slug'])
                    ->groupBy('plan_id')
                    ->map(fn ($items) => optional($items->first())->slug);

                foreach ($matchingPlaylists as $plan) {
                    $firstSlug = $firstSlugs[$plan->id] ?? null;
                    $playlistUrl = $firstSlug
                        ? route('playlist.user.view', ['slug' => $plan->slug, 'vidslug' => $firstSlug])
                        : url('/playlist/' . $plan->slug);

                    $results->push([
                        'id'       => $plan->id,
                        'title'    => $plan->title,
                        'slug'     => $plan->slug,
                        'category' => 'Playlist',
                        'type'     => 'playlist',
                        'progress' => (int) $plan->progress_percent,
                        'url'      => $playlistUrl,
                    ]);
                }
            }
        }

        return response()->json($results->values());
    }

    /**
     * Mode CONTENT — HTML fragment card Video/Document Learning On Going.
     * Tidak ada query playlist sama sekali.
     */
    public function content()
    {
        $userId  = (int) Auth::id();
        $filters = $this->contentFilterState();

        $cards = $this->contentService->cards($userId, $filters);

        $ongoingContents = $this->paginateOngoingCards(
            $this->sortOngoingCards($cards, $filters['progress_sort'], $filters['activity_sort'])
        );

        return view('user.learning.dashboard-partials.ongoing-content', compact('ongoingContents'));
    }

    /**
     * Mode PLAYLIST — HTML fragment card Playlist Learning On Going.
     * Tidak ada query content individual sama sekali (hanya agregat playlist).
     */
    public function playlist()
    {
        $userId  = (int) Auth::id();
        $filters = $this->playlistFilterState();

        $cards = $this->playlistService->cards(
            $this->playlistService->userPlaylists($userId),
            $filters
        );

        $ongoingPlaylists = $this->paginateOngoingCards(
            $this->sortOngoingCards($cards, $filters['progress_sort'], $filters['activity_sort'])
        );

        return view('user.learning.dashboard-partials.ongoing-playlist', compact('ongoingPlaylists'));
    }

    /**
     * Mode MOOC — HTML fragment card MOOC Learning On Going.
     */
    public function mooc()
    {
        $userId  = (int) Auth::id();
        $filters = $this->contentFilterState();

        $cards = $this->contentService->moocCards($userId, $filters);

        $ongoingMoocs = $this->paginateOngoingCards(
            $this->sortOngoingCards($cards, $filters['progress_sort'], $filters['activity_sort'])
        );

        return view('user.learning.dashboard-partials.ongoing-mooc', compact('ongoingMoocs'));
    }

    /**
     * Mode Non KP OWL — HTML fragment Non KP OWL (Offline Training) Learning On Going.
     */
    public function nonKpOwl()
    {
        $userId = (int) Auth::id();
        $userEmpId = Auth::user()?->id_emp;
        $resolver = app(\App\Services\Learning\LearningProgressResolver::class);
        $offlineService = app(\App\Services\Learning\UserRecordOfflineService::class);

        $rawTrainings = $resolver->getOngoingNonKpOwl($userId, $userEmpId);
        $ongoingNonKpOwl = $rawTrainings->map(fn ($otu) => $offlineService->normalizeRow($otu));

        $filterOptions = $offlineService->getFilterOptions($userId, $userEmpId);

        $search = trim((string) request('ongoing_search', request('record_search', request('search', request('q', '')))));
        $filterType = request('ongoing_filter_type', request('record_filter_type', request('type', 'all')));
        $filterClassification = request('ongoing_filter_classification', request('record_filter_classification', request('classification', 'all')));
        $filterYear = request('ongoing_filter_year', request('record_filter_year', request('year', 'all')));

        if ($filterType !== 'all' && $filterType !== '') {
            $ongoingNonKpOwl = $ongoingNonKpOwl->filter(function ($row) use ($filterType) {
                return strcasecmp((string)($row['type'] ?? ''), (string)$filterType) === 0;
            });
        }
        if ($filterClassification !== 'all' && $filterClassification !== '') {
            $ongoingNonKpOwl = $ongoingNonKpOwl->filter(function ($row) use ($filterClassification) {
                return strcasecmp((string)($row['classification'] ?? ($row['main_classification'] ?? '')), (string)$filterClassification) === 0;
            });
        }
        if ($filterYear !== 'all' && $filterYear !== '') {
            $ongoingNonKpOwl = $ongoingNonKpOwl->filter(function ($row) use ($filterYear) {
                $y1 = !empty($row['start_date']) ? substr($row['start_date'], 0, 4) : '';
                $y2 = !empty($row['finish_date']) ? substr($row['finish_date'], 0, 4) : '';
                return $y1 === (string)$filterYear || $y2 === (string)$filterYear;
            });
        }
        if ($search !== '') {
            $ongoingNonKpOwl = $ongoingNonKpOwl->filter(function ($row) use ($search) {
                $q = mb_strtolower($search);
                $title = mb_strtolower((string)($row['title'] ?? ''));
                $batch = mb_strtolower((string)($row['batch'] ?? ''));
                $provider = mb_strtolower((string)($row['provider'] ?? ($row['provider_name'] ?? '')));
                $sub = mb_strtolower((string)($row['subtitle'] ?? ''));
                return str_contains($title, $q) || str_contains($batch, $q) || str_contains($provider, $q) || str_contains($sub, $q);
            });
        }
        $ongoingNonKpOwl = $ongoingNonKpOwl->values();

        return view('user.learning.dashboard-partials.ongoing-non-kp-owl', compact('ongoingNonKpOwl', 'filterOptions'));
    }

    /**
     * Filter mode Content — search, type (Video/Document), quick status,
     * year/month, serta seluruh filter modal All Filters. Tanpa unsur playlist.
     *
     * @return array<string, mixed>
     */
    private function contentFilterState(): array
    {
        $rawMonths = request('ongoing_month', request('month', []));
        if (!is_array($rawMonths)) {
            $rawMonths = [$rawMonths];
        }
        $resolvedMonths = [];
        foreach ($rawMonths as $m) {
            $res = LearningFilterHelper::resolveMonth((string) $m);
            if ($res !== null) {
                $resolvedMonths[] = $res;
            }
        }

        $rawTypes = request('ongoing_type', request('type', []));
        if (!is_array($rawTypes)) {
            $rawTypes = [$rawTypes];
        }

        $status = request('ongoing_status', request('status', 'all'));
        $quickStatus = (string) request('ongoing_quick_status', request('status', 'all'));

        return [
            'search'        => trim((string) request('ongoing_search', request('search', request('q', '')))),
            'status'        => $status,
            'year'          => request('ongoing_year', request('year', 'all')),
            'activity_sort' => request('ongoing_activity_sort', 'newest_activity'),
            'progress_sort' => request('ongoing_progress_sort', 'all'),

            'date_from'     => request('ongoing_date_from', request('ongoing_from_date')),
            'date_to'       => request('ongoing_date_to', request('ongoing_to_date')),
            'learning_hour' => request('ongoing_learning_hour', 'all'),

            // Quick filter Status (dropdown cepat): all|not_started|ongoing|completed|paused.
            'quick_status' => $quickStatus,

            // Type content asli (Video, Document, YouTube, dll) — multi.
            'types' => array_map('strval', array_filter($rawTypes)),

            'learning_status'  => array_filter((array) request('ongoing_learning_status', [])),
            'progress_buckets' => array_filter((array) request('ongoing_progress', [])),
            'durations'        => array_filter((array) request('ongoing_duration', [])),
            'months'           => $resolvedMonths,
            'current_times'    => array_filter((array) request('ongoing_current_time', [])),
            'last_pages'       => array_filter((array) request('ongoing_last_page', [])),
            'total_pages'      => array_filter((array) request('ongoing_total_pages', [])),
        ];
    }

    /**
     * Filter mode Playlist — search, quick status, year, month, progress bucket.
     * Tanpa unsur content (type, duration, page, dsb).
     *
     * @return array<string, mixed>
     */
    private function playlistFilterState(): array
    {
        $rawMonths = request('ongoing_month', request('month', []));
        if (!is_array($rawMonths)) {
            $rawMonths = [$rawMonths];
        }
        $resolvedMonths = [];
        foreach ($rawMonths as $m) {
            $res = LearningFilterHelper::resolveMonth((string) $m);
            if ($res !== null) {
                $resolvedMonths[] = $res;
            }
        }

        return [
            'search'           => trim((string) request('ongoing_search', request('search', request('q', '')))),
            'status'           => request('ongoing_status', request('status', 'all')),
            'quick_status'     => (string) request('ongoing_quick_status', request('status', 'all')),
            'year'             => request('ongoing_year', request('year', 'all')),
            'activity_sort'    => request('ongoing_activity_sort', 'newest_activity'),
            'progress_sort'    => request('ongoing_progress_sort', 'all'),
            'date_from'        => request('ongoing_date_from', request('ongoing_from_date')),
            'date_to'          => request('ongoing_date_to', request('ongoing_to_date')),
            'learning_hour'    => request('ongoing_learning_hour', 'all'),
            'months'           => $resolvedMonths,
            'progress_buckets' => array_filter((array) request('ongoing_progress', [])),
            'durations'        => array_filter((array) request('ongoing_duration', [])),
        ];
    }

    /**
     * Paginasi manual koleksi card Learning On Going (per mode).
     *
     * @param  \Illuminate\Support\Collection  $cards
     */
    private function paginateOngoingCards($cards)
    {
        $perPage = 9;
        $page    = LengthAwarePaginator::resolveCurrentPage('ongoing_page');
        $items   = $cards->forPage($page, $perPage)->values();

        return (new LengthAwarePaginator($items, $cards->count(), $perPage, $page, [
            'path'     => route('user.learning.dashboard'),
            'pageName' => 'ongoing_page',
        ]))->appends(request()->query());
    }

    /**
     * Urutkan koleksi card Content/Playlist sesuai opsi sort yang aktif.
     *
     * @param  \Illuminate\Support\Collection  $cards
     */
    private function sortOngoingCards($cards, string $progressSort, string $activitySort)
    {
        $sortKey = $activitySort !== 'newest_activity'
            ? $activitySort
            : ($progressSort !== 'all' ? $progressSort : $activitySort);

        return match ($sortKey) {
            'oldest_activity'   => $cards->sortBy('sort_activity')->values(),
            'newest_activity'   => $cards->sortByDesc('sort_activity')->values(),
            'duration_longest'  => $cards->sortByDesc('sort_duration')->values(),
            'duration_shortest' => $cards->sortBy('sort_duration')->values(),
            'progress_highest'  => $cards->sortByDesc('sort_progress')->values(),
            'progress_lowest'   => $cards->sortBy('sort_progress')->values(),
            default             => $cards->sortByDesc('sort_activity')->values(),
        };
    }
}
