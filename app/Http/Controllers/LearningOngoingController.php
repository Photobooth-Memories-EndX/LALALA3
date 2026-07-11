<?php

namespace App\Http\Controllers;

use App\Services\Learning\OngoingContentService;
use App\Services\Learning\OngoingPlaylistService;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Auth;

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
            $cards->sortByDesc('sort_activity')->values()
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
            'search'        => trim((string) request('ongoing_search')),
            'status'        => request('ongoing_status', 'all'),
            'year'          => request('ongoing_year', 'all'),
            'activity_sort' => request('ongoing_activity_sort', 'newest_activity'),
            'progress_sort' => request('ongoing_progress_sort', 'all'),

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
            'search'           => trim((string) request('ongoing_search')),
            'quick_status'     => (string) request('ongoing_quick_status', 'all'),
            'year'             => request('ongoing_year', 'all'),
            'months'           => array_filter((array) request('ongoing_month', [])),
            'progress_buckets' => array_filter((array) request('ongoing_progress', [])),
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
     * Urutkan koleksi card Content sesuai opsi sort yang aktif.
     *
     * @param  \Illuminate\Support\Collection  $cards
     */
    private function sortOngoingCards($cards, string $progressSort, string $activitySort)
    {
        switch (true) {
            case $progressSort === 'progress_highest':  return $cards->sortByDesc('sort_progress')->values();
            case $progressSort === 'progress_lowest':   return $cards->sortBy('sort_progress')->values();
            case $activitySort === 'duration_longest':  return $cards->sortByDesc('sort_duration')->values();
            case $activitySort === 'duration_shortest': return $cards->sortBy('sort_duration')->values();
            case $activitySort === 'oldest_activity':   return $cards->sortBy('sort_activity')->values();
            default:                                    return $cards->sortByDesc('sort_activity')->values();
        }
    }
}
