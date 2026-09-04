<?php

namespace App\Http\Controllers;

use App\Models\ContentUser;
use App\Services\Learning\OngoingContentService;
use App\Services\Learning\OngoingPlaylistService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

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
            $userPlaylists = $this->playlistService->userPlaylists($userId)->reject->is_completed;

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
     * Filter mode Content — search, type (Video/Document), quick status,
     * year/month, serta seluruh filter modal All Filters. Tanpa unsur playlist.
     *
     * @return array<string, mixed>
     */
    private function contentFilterState(): array
    {
        return [
            'search'        => trim((string) request('ongoing_search', request('q', ''))),
            'status'        => request('ongoing_status', 'all'),
            'year'          => request('ongoing_year', 'all'),
            'activity_sort' => request('ongoing_activity_sort', 'newest_activity'),
            'progress_sort' => request('ongoing_progress_sort', 'all'),

            'date_from'     => request('ongoing_date_from', request('ongoing_from_date')),
            'date_to'       => request('ongoing_date_to', request('ongoing_to_date')),
            'learning_hour' => request('ongoing_learning_hour', 'all'),

            // Quick filter Status (dropdown cepat): all|not_started|ongoing|completed|paused.
            'quick_status' => (string) request('ongoing_quick_status', 'all'),

            // Type content asli (Video, Document, YouTube, dll) — multi.
            'types' => array_map('strval', array_filter((array) request('ongoing_type', []))),

            'learning_status'  => array_filter((array) request('ongoing_learning_status', [])),
            'progress_buckets' => array_filter((array) request('ongoing_progress', [])),
            'durations'        => array_filter((array) request('ongoing_duration', [])),
            'months'           => array_filter((array) request('ongoing_month', [])),
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
        return [
            'search'           => trim((string) request('ongoing_search', request('q', ''))),
            'status'           => request('ongoing_status', 'all'),
            'quick_status'     => (string) request('ongoing_quick_status', 'all'),
            'year'             => request('ongoing_year', 'all'),
            'activity_sort'    => request('ongoing_activity_sort', 'newest_activity'),
            'progress_sort'    => request('ongoing_progress_sort', 'all'),
            'date_from'        => request('ongoing_date_from', request('ongoing_from_date')),
            'date_to'          => request('ongoing_date_to', request('ongoing_to_date')),
            'learning_hour'    => request('ongoing_learning_hour', 'all'),
            'months'           => array_filter((array) request('ongoing_month', [])),
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
