<?php

namespace App\Services\Learning;

use App\Models\ContentUser;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * Business logic card Content (Video / Document) pada mode "Content"
 * tab Learning On Going.
 *
 * Seluruh perhitungan Content (progress, duration, current page, total page,
 * badge, status) HANYA berasal dari content_users + relasi content — terpisah
 * penuh dari flow Playlist (OngoingPlaylistService). Tidak ada query playlist
 * sama sekali di sini.
 */
class OngoingContentService
{
    /**
     * Bentuk card Content Learning On Going sesuai seluruh filter aktif.
     *
     * @return \Illuminate\Support\Collection<int, array>
     */
    public function cards(int $userId, array $filters): Collection
    {
        $query = ContentUser::with([
                'content.classification',
                'assesment' => fn ($q) => $q->with([
                    'scores' => fn ($q) => $q->where('user_id', $userId),
                ]),
            ])
            ->where('user_id', $userId)
            ->withAvailableContent();

        $this->applyStatusScope($query, $filters);
        $this->applyFilters($query, $filters);

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
     * Scope status dasar card Content.
     *
     * Quick filter Status (dropdown cepat) menang bila dipilih; jika tidak,
     * berlaku perilaku lama: tab ongoing = finish 0, kecuali Completion Status
     * "completed" pada All Filters = finish 1.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     */
    private function applyStatusScope($query, array $filters): void
    {
        switch ($filters['quick_status']) {
            case 'not_started':
                $query->where('finish', '0')->where('progress', 0);
                return;
            case 'ongoing':
                $query->where('finish', '0')->whereBetween('progress', [1, 99]);
                return;
            case 'paused':
                $query->where('finish', '0')->where('learning_status', 'paused');
                return;
            case 'completed':
                $query->where(function ($q) {
                    $q->where('finish', '1')->orWhere('progress', '>=', 100);
                });
                return;
        }

        if ($filters['progress_sort'] === 'completed') {
            $query->where('finish', '1');
        } else {
            $query->where('finish', '0');
        }
    }

    /**
     * Terapkan seluruh filter request pada query ContentUser (Learning On Going).
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     */
    private function applyFilters($query, array $filters): void
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
}
