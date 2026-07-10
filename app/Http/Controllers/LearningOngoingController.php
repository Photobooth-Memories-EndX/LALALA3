<?php

namespace App\Http\Controllers;

use App\Models\ContentUser;
use App\Models\TrainingPlan;
use App\Models\TrainingPlanDetail;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

/**
 * Learning On Going — pembelajaran yang sedang berjalan.
 *
 * Menangani seluruh logic tab "ongoing" pada My Learning dashboard:
 * card Playlist + card Content, filter, search, sorting, dan pagination.
 * Dipanggil dari LearningController::dashboardPartial (routing only).
 */
class LearningOngoingController extends Controller
{
    /**
     * HTML fragment tab "ongoing" pada My Learning dashboard.
     */
    public function partial()
    {
        $userId = (int) Auth::id();

        // Playlist (Training Plan) yang sedang dikerjakan user (belum 100%).
        $ongoingPlaylists = $this->getOngoingPlaylists($userId);

        // Target "Continue Learning" tiap playlist = content berikutnya yang belum selesai.
        $nextSlugs = $this->playlistNextContent($ongoingPlaylists->pluck('id')->all(), $userId);
        $ongoingPlaylists->each(fn ($p) => $p->next_slug = $nextSlugs[$p->id] ?? null);

        $ongoing_content_user = $this->getOngoingLearningContents($userId, $ongoingPlaylists);
        $ongoingLearning      = ContentUser::where('user_id', $userId)->where('finish', '0')->count();
        $averageProgress      = (int) round(ContentUser::where('user_id', $userId)->avg('progress') ?? 0);

        return view('user.learning.dashboard-partials.ongoing', compact(
            'ongoing_content_user',
            'ongoingLearning',
            'averageProgress',
            'ongoingPlaylists'
        ));
    }

    /**
     * Learning On Going = gabungan card Playlist + card Content (Video/Document).
     *
     * Aturan (best practice LMS — Playlist dan Content hidup berdampingan):
     *  - Playlist tampil bila user punya progress di dalamnya (belum 100%).
     *  - Content TETAP tampil walaupun berada di dalam Playlist — Content berfungsi
     *    melanjutkan pembelajaran terakhir, Playlist menunjukkan progres keseluruhan.
     *  - Content yang berasal dari Playlist diberi badge nama Playlist-nya di card.
     *  - "playlist" adalah pseudo-type pada filter Type.
     *  - Filter Playlist (More Filters): playlist terpilih tampil + semua Content
     *    di dalam playlist tersebut ikut tampil.
     *
     * Menggabungkan 2 sumber lalu dipaginasi manual (LengthAwarePaginator) agar Search,
     * Filter, Sorting, dan Pagination tetap berjalan tanpa N+1 Query.
     *
     * @param  \Illuminate\Support\Collection|null  $ongoingPlaylists  playlist on-going (belum 100%)
     */
    private function getOngoingLearningContents($userId, $ongoingPlaylists = null)
    {
        $filters = $this->ongoingFilterState();

        $playlistCards = $filters['wants_playlist']
            ? $this->applyPlaylistFilters(collect($ongoingPlaylists ?? collect()), $filters)
            : collect();

        $contentCards = $filters['wants_content']
            ? $this->getOngoingContents((int) $userId, $filters)
            : collect();

        return $this->paginateLearningItems(
            $this->mergeLearningItems($playlistCards, $contentCards),
            $filters['progress_sort'],
            $filters['activity_sort']
        );
    }

    /**
     * Kumpulkan seluruh nilai filter Learning On Going dari request.
     *
     * "playlist" = pseudo-type pada filter Type; sisanya adalah content.type asli
     * (Video, Document, YouTube, dll). Semua filter opsional & bisa dikombinasikan.
     *
     * @return array<string, mixed>
     */
    private function ongoingFilterState(): array
    {
        $rawTypes = array_map('strval', array_filter((array) request('ongoing_type', [])));

        $realTypes = array_values(array_diff($rawTypes, ['playlist']));

        return [
            'search'        => trim((string) request('ongoing_search')),
            'status'        => request('ongoing_status', 'all'),
            'year'          => request('ongoing_year', 'all'),
            'activity_sort' => request('ongoing_activity_sort', 'newest_activity'),
            'progress_sort' => request('ongoing_progress_sort', 'all'),

            'types'          => $realTypes,
            'wants_playlist' => empty($rawTypes) || in_array('playlist', $rawTypes, true),
            'wants_content'  => empty($rawTypes) || !empty($realTypes),

            // Filter Playlist spesifik (More Filters) — id trainingplan, bisa multi.
            'playlist_ids' => array_map('strval', array_filter((array) request('ongoing_playlist', []))),

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
     * Saring koleksi Playlist on-going (in-memory, data sudah dimuat sekali tanpa N+1)
     * lalu bentuk menjadi card siap gabung.
     *
     * @param  \Illuminate\Support\Collection  $playlists
     * @return \Illuminate\Support\Collection
     */
    private function applyPlaylistFilters($playlists, array $filters)
    {
        if (!empty($filters['playlist_ids'])) {
            $playlists = $playlists->filter(fn ($p) => in_array((string) $p->id, $filters['playlist_ids'], true));
        }
        if ($filters['search'] !== '') {
            $playlists = $playlists->filter(fn ($p) => stripos((string) $p->title, $filters['search']) !== false);
        }
        if (!empty($filters['progress_buckets'])) {
            $playlists = $playlists->filter(
                fn ($p) => $this->matchesProgressBucket((int) $p->progress_percent, $filters['progress_buckets'])
            );
        }

        return $playlists->map(fn ($p) => [
            'card_type'     => 'playlist',
            'plan'          => $p,
            'sort_activity' => $p->last_activity_at ? strtotime($p->last_activity_at) : 0,
            'sort_progress' => (int) $p->progress_percent,
            'sort_duration' => 0,
        ])->values();
    }

    /**
     * Card Content (Video/Document) Learning On Going.
     *
     * Content yang berada di dalam Playlist TIDAK dikecualikan — tetap tampil
     * berdampingan dengan card Playlist-nya. Relasi trainingPlans di-eager-load
     * untuk badge "Playlist" pada card tanpa N+1.
     *
     * @return \Illuminate\Support\Collection
     */
    private function getOngoingContents(int $userId, array $filters)
    {
        $query = ContentUser::with([
                'content.classification',
                // Playlist pemilik content — untuk badge "Playlist" di card.
                'content.trainingPlans' => fn ($q) => $q->select('trainingplan.id', 'trainingplan.title'),
                'assesment' => fn ($q) => $q->with([
                    'scores' => fn ($q) => $q->where('user_id', $userId),
                ]),
            ])
            ->where('user_id', $userId);

        if ($filters['progress_sort'] === 'completed') {
            $query->where('finish', '1');
        } else {
            $query->where('finish', '0');
        }

        $this->applyContentFilters($query, $filters);

        return $query->get()
            ->filter(fn ($cu) => $cu->content !== null)
            ->map(fn ($cu) => [
                'card_type'     => 'content',
                'list'          => $cu,
                'sort_activity' => optional($cu->updated_at)->timestamp ?? 0,
                'sort_progress' => (int) ($cu->progress ?? 0),
                'sort_duration' => (int) ($cu->duration_seconds ?? 0),
            ])
            ->values();
    }

    /**
     * Terapkan seluruh filter request pada query ContentUser (Learning On Going).
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     */
    private function applyContentFilters($query, array $filters): void
    {
        $search          = $filters['search'];
        $realTypes       = $filters['types'];
        $learningStatus  = $filters['learning_status'];
        $progressBuckets = $filters['progress_buckets'];
        $durations       = $filters['durations'];
        $months          = $filters['months'];
        $currentTimes    = $filters['current_times'];
        $lastPages       = $filters['last_pages'];
        $totalPagesFilt  = $filters['total_pages'];
        $progressSort    = $filters['progress_sort'];

        $query->when($search !== '', function ($q) use ($search) {
            $q->whereHas('content', function ($q) use ($search) {
                $q->where('title', 'like', '%' . $search . '%')
                    ->orWhereHas('classification', function ($q) use ($search) {
                        $q->where('classification_name', 'like', '%' . $search . '%');
                    });
            });
        });

        $query->when($filters['status'] !== 'all', fn ($q) => $q->where('status', $filters['status']));
        $query->when($filters['year'] !== 'all', fn ($q) => $q->whereYear('created_at', $filters['year']));
        $query->when($progressSort === 'not_started', fn ($q) => $q->where('progress', 0));
        $query->when($progressSort === 'in_progress', fn ($q) => $q->whereBetween('progress', [1, 99]));
        $query->when($progressSort === 'almost_done', fn ($q) => $q->where('progress', '>=', 80)->where('progress', '<', 100));

        // Filter Playlist: hanya content yang menjadi bagian playlist terpilih —
        // user sedang fokus pada playlist tersebut, isinya ikut tampil.
        $query->when(!empty($filters['playlist_ids']), function ($q) use ($filters) {
            $q->whereExists(function ($sub) use ($filters) {
                $sub->from('trainingplandetail')
                    ->whereColumn('trainingplandetail.content_id', 'content_users.content_id')
                    ->whereIn('trainingplandetail.trainingplan_id', $filters['playlist_ids']);
            });
        });

        // ── Filter tambahan (data asli content_users & relasi content) ──────────────
        // Type diambil dari kolom content.type (Video, Document, YouTube, dll).
        $query->when(!empty($realTypes), fn ($q) => $q->whereHas('content', fn ($c) => $c->whereIn('type', $realTypes)));

        // Learning status diambil dari kolom content_users.learning_status.
        $query->when(!empty($learningStatus), fn ($q) => $q->whereIn('learning_status', $learningStatus));

        // Bulan berdasarkan content_users.created_at (angka 1-12).
        $query->when(!empty($months), fn ($q) => $q->whereIn(DB::raw('MONTH(created_at)'), $months));

        // Progress dikelompokkan per rentang; antar pilihan bersifat OR.
        $query->when(!empty($progressBuckets), function ($q) use ($progressBuckets) {
            $q->where(function ($sub) use ($progressBuckets) {
                foreach ($progressBuckets as $bucket) {
                    switch ($bucket) {
                        case '0':     $sub->orWhere('progress', 0); break;
                        case '1-25':  $sub->orWhereBetween('progress', [1, 25]); break;
                        case '26-50': $sub->orWhereBetween('progress', [26, 50]); break;
                        case '51-75': $sub->orWhereBetween('progress', [51, 75]); break;
                        case '76-99': $sub->orWhereBetween('progress', [76, 99]); break;
                        case '100':   $sub->orWhere('progress', '>=', 100); break;
                    }
                }
            });
        });

        // Durasi berdasarkan content_users.duration_seconds (detik); antar pilihan OR.
        $query->when(!empty($durations), function ($q) use ($durations) {
            $q->where(function ($sub) use ($durations) {
                foreach ($durations as $d) {
                    switch ($d) {
                        case 'lt10':  $sub->orWhere('duration_seconds', '<', 600); break;
                        case '10-30': $sub->orWhereBetween('duration_seconds', [600, 1800]); break;
                        case '30-60': $sub->orWhereBetween('duration_seconds', [1801, 3600]); break;
                        case 'gt60':  $sub->orWhere('duration_seconds', '>', 3600); break;
                    }
                }
            });
        });

        // Current learning time (content_users.current_time, posisi belajar dalam detik).
        $query->when(!empty($currentTimes), function ($q) use ($currentTimes) {
            $q->where(function ($sub) use ($currentTimes) {
                foreach ($currentTimes as $c) {
                    switch ($c) {
                        case 'start':  $sub->orWhere('current_time', '<', 300); break;
                        case 'middle': $sub->orWhereBetween('current_time', [300, 1800]); break;
                        case 'almost': $sub->orWhere('current_time', '>', 1800); break;
                    }
                }
            });
        });

        // Last page (content_users.last_page).
        $query->when(!empty($lastPages), function ($q) use ($lastPages) {
            $q->where(function ($sub) use ($lastPages) {
                foreach ($lastPages as $p) {
                    switch ($p) {
                        case '1-10':  $sub->orWhereBetween('last_page', [1, 10]); break;
                        case '11-20': $sub->orWhereBetween('last_page', [11, 20]); break;
                        case '21-50': $sub->orWhereBetween('last_page', [21, 50]); break;
                        case 'gt50':  $sub->orWhere('last_page', '>', 50); break;
                    }
                }
            });
        });

        // Total pages (content_users.total_pages).
        $query->when(!empty($totalPagesFilt), function ($q) use ($totalPagesFilt) {
            $q->where(function ($sub) use ($totalPagesFilt) {
                foreach ($totalPagesFilt as $p) {
                    switch ($p) {
                        case 'lt10':  $sub->orWhere('total_pages', '<', 10); break;
                        case '10-20': $sub->orWhereBetween('total_pages', [10, 20]); break;
                        case '21-50': $sub->orWhereBetween('total_pages', [21, 50]); break;
                        case 'gt50':  $sub->orWhere('total_pages', '>', 50); break;
                    }
                }
            });
        });
    }

    /**
     * Gabungkan card Playlist + card Content menjadi satu koleksi Learning On Going.
     * Playlist dan Content hidup berdampingan — tidak saling menggantikan.
     *
     * @param  \Illuminate\Support\Collection  $playlistCards
     * @param  \Illuminate\Support\Collection  $contentCards
     * @return \Illuminate\Support\Collection
     */
    private function mergeLearningItems($playlistCards, $contentCards)
    {
        return collect($playlistCards)->concat($contentCards)->values();
    }

    /**
     * Paginasi manual koleksi card Learning On Going (playlist + content).
     *
     * @param  \Illuminate\Support\Collection  $cards
     */
    private function paginateLearningItems($cards, string $progressSort, string $activitySort)
    {
        $cards   = $this->sortOngoingCards($cards, $progressSort, $activitySort);
        $perPage = 9;
        $page    = LengthAwarePaginator::resolveCurrentPage('ongoing_page');
        $items   = $cards->forPage($page, $perPage)->values();

        return (new LengthAwarePaginator($items, $cards->count(), $perPage, $page, [
            'path'     => route('user.learning.dashboard'),
            'pageName' => 'ongoing_page',
        ]))->appends(request()->query());
    }

    /**
     * Urutkan koleksi card (playlist + content) sesuai opsi sort yang aktif.
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

    /**
     * Playlist (Training Plan) yang sedang dikerjakan user — punya progress tapi belum 100%.
     */
    private function getOngoingPlaylists(int $userId)
    {
        return $this->getUserPlaylists($userId)->reject->is_completed->values();
    }

    /**
     * Ambil daftar Playlist (Training Plan) beserta agregat progres milik seorang user.
     *
     * Hanya playlist yang SUDAH DISENTUH user (ada content dgn progress > 0 atau finish = 1)
     * yang dikembalikan. Semua agregat dihitung dinamis via subquery — tidak ada kolom baru
     * dan tidak ada query di dalam loop (bebas N+1).
     *
     * Setiap item memiliki atribut tambahan:
     *   total_items, finished_items, avg_progress, last_finished_at,
     *   progress_percent (0-100), is_completed (bool).
     *
     * @return \Illuminate\Support\Collection<int, TrainingPlan>
     */
    private function getUserPlaylists(int $userId)
    {
        return TrainingPlan::query()
            ->select('trainingplan.id', 'trainingplan.title', 'trainingplan.slug', 'trainingplan.thumbnail')
            ->whereExists(function ($q) use ($userId) {
                $q->from('trainingplandetail')
                    ->join('content_users', 'content_users.content_id', '=', 'trainingplandetail.content_id')
                    ->whereColumn('trainingplandetail.trainingplan_id', 'trainingplan.id')
                    ->where('content_users.user_id', $userId)
                    ->where(function ($w) {
                        $w->where('content_users.progress', '>', 0)
                            ->orWhere('content_users.finish', '1');
                    });
            })
            ->selectSub(
                TrainingPlanDetail::selectRaw('COUNT(*)')
                    ->whereColumn('trainingplandetail.trainingplan_id', 'trainingplan.id'),
                'total_items'
            )
            ->selectSub(
                ContentUser::selectRaw('COUNT(*)')
                    ->join('trainingplandetail', 'trainingplandetail.content_id', '=', 'content_users.content_id')
                    ->whereColumn('trainingplandetail.trainingplan_id', 'trainingplan.id')
                    ->where('content_users.user_id', $userId)
                    ->where('content_users.finish', '1'),
                'finished_items'
            )
            ->selectSub(
                ContentUser::selectRaw('COALESCE(ROUND(AVG(content_users.progress)), 0)')
                    ->join('trainingplandetail', 'trainingplandetail.content_id', '=', 'content_users.content_id')
                    ->whereColumn('trainingplandetail.trainingplan_id', 'trainingplan.id')
                    ->where('content_users.user_id', $userId),
                'avg_progress'
            )
            ->selectSub(
                ContentUser::selectRaw('MAX(content_users.updated_at)')
                    ->join('trainingplandetail', 'trainingplandetail.content_id', '=', 'content_users.content_id')
                    ->whereColumn('trainingplandetail.trainingplan_id', 'trainingplan.id')
                    ->where('content_users.user_id', $userId)
                    ->where('content_users.finish', '1'),
                'last_finished_at'
            )
            ->selectSub(
                ContentUser::selectRaw('MAX(content_users.updated_at)')
                    ->join('trainingplandetail', 'trainingplandetail.content_id', '=', 'content_users.content_id')
                    ->whereColumn('trainingplandetail.trainingplan_id', 'trainingplan.id')
                    ->where('content_users.user_id', $userId),
                'last_activity_at'
            )
            ->orderBy('trainingplan.title')
            ->get()
            ->map(function (TrainingPlan $plan) {
                $total    = (int) $plan->total_items;
                $finished = (int) $plan->finished_items;

                $plan->progress_percent = $total > 0 ? (int) round($finished / $total * 100) : 0;
                $plan->is_completed     = $total > 0 && $finished >= $total;

                return $plan;
            });
    }

    /**
     * Untuk sekumpulan playlist, tentukan slug content berikutnya yang harus dilanjutkan user
     * (content pertama—berdasar content_order—yang belum finish; fallback content pertama).
     *
     * Satu query untuk semua playlist sekaligus → bebas N+1.
     *
     * @param  array<int, mixed>  $planIds
     * @return \Illuminate\Support\Collection<string, ?string>  map [trainingplan_id => slug]
     */
    private function playlistNextContent(array $planIds, int $userId)
    {
        if (empty($planIds)) {
            return collect();
        }

        $rows = DB::table('trainingplandetail as tpd')
            ->join('content as c', 'c.id', '=', 'tpd.content_id')
            ->leftJoin('content_users as cu', function ($j) use ($userId) {
                $j->on('cu.content_id', '=', 'c.id')->where('cu.user_id', $userId);
            })
            ->whereIn('tpd.trainingplan_id', $planIds)
            ->whereNull('c.deleted_at')
            ->orderByRaw('CAST(tpd.content_order AS UNSIGNED) ASC')
            ->get(['tpd.trainingplan_id as plan_id', 'c.slug as slug', 'cu.finish as finish']);

        return $rows->groupBy('plan_id')->map(function ($items) {
            $next = $items->first(fn ($r) => empty($r->finish) || (string) $r->finish === '0');

            return optional($next ?? $items->first())->slug;
        });
    }

    /**
     * Apakah nilai progress (0-100) cocok dengan salah satu bucket progress terpilih.
     *
     * @param  array<int, string>  $buckets
     */
    private function matchesProgressBucket(int $progress, array $buckets): bool
    {
        foreach ($buckets as $bucket) {
            switch ($bucket) {
                case '0':     if ($progress === 0) return true; break;
                case '1-25':  if ($progress >= 1 && $progress <= 25) return true; break;
                case '26-50': if ($progress >= 26 && $progress <= 50) return true; break;
                case '51-75': if ($progress >= 51 && $progress <= 75) return true; break;
                case '76-99': if ($progress >= 76 && $progress <= 99) return true; break;
                case '100':   if ($progress >= 100) return true; break;
            }
        }

        return false;
    }
}
