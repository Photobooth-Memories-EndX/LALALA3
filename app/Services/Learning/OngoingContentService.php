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
     * Filter default untuk canonical card/count Ongoing Content.
     */
    public const DEFAULT_FILTERS = [
        'search'           => '',
        'status'           => 'all',
        'year'             => 'all',
        'activity_sort'    => 'newest_activity',
        'progress_sort'    => 'all',
        'date_from'        => null,
        'date_to'          => null,
        'learning_hour'    => 'all',
        'quick_status'     => 'all',
        'types'            => [],
        'learning_status'  => [],
        'progress_buckets' => [],
        'durations'        => [],
        'months'           => [],
        'current_times'    => [],
        'last_pages'       => [],
        'total_pages'      => [],
    ];

    /**
     * Hitung canonical total card Content Learning On Going untuk user.
     * Menggunakan rule yang persis sama dengan tab My Learning -> Ongoing -> Content.
     */
    public function ongoingContentCount(int $userId, array $filters = []): int
    {
        $mergedFilters = array_merge(self::DEFAULT_FILTERS, $filters);

        return $this->cards($userId, $mergedFilters)->count();
    }

    /**
     * Alias method count() untuk OngoingContentService.
     */
    public function count(int $userId, array $filters = []): int
    {
        return $this->ongoingContentCount($userId, $filters);
    }

    /**
     * Bentuk card Content Learning On Going sesuai seluruh filter aktif.
     *
     * @return \Illuminate\Support\Collection<int, array>
     */
    public function cards(int $userId, array $filters = []): Collection
    {
        $filters = array_merge(self::DEFAULT_FILTERS, $filters);
        $query = ContentUser::with([
                'content.classification',
                'assesment' => fn ($q) => $q->with([
                    'scores' => fn ($q) => $q->where('user_id', $userId),
                ]),
            ])
            ->where('user_id', $userId)
            ->withAvailableContent();

        // Base Eligibility Learning Ongoing Content:
        // 1. Progress minimum >= 5%
        $query->where('progress', '>=', 5);

        // 2. Exclude content yang merupakan child/member dari Playlist (terdaftar di trainingplandetail)
        $query->whereNotExists(function ($q) {
            $q->from('trainingplandetail')
                ->whereColumn('trainingplandetail.content_id', 'content_users.content_id');
        });

        // 3. Exclude MOOC/YouTube dari mode Content standar (Video/Document)
        $query->whereHas('content', fn ($q) => $q->whereNotIn('type', ['YouTube', 'MOOC']));

        $this->applyStatusScope($query, $filters);
        $this->applyFilters($query, $filters);

        return $query->get()
            ->filter(fn ($cu) => $cu->content !== null)
            ->map(function ($cu) {
                $durationSeconds = (int) ($cu->duration_seconds ?? 0);
                if ($durationSeconds <= 0 && $cu->content && !empty($cu->content->duration)) {
                    $durationSeconds = \App\Support\DurationFormatter::toSeconds($cu->content->duration) ?? 0;
                }

                $actualWatchSeconds = (int) ($cu->actual_watch_seconds ?? 0);
                $currentTime = (int) ($cu->current_time ?? 0);
                $progress = (int) ($cu->progress ?? 0);
                $isFinished = ((string) $cu->finish === '1' || $progress >= 100);

                if ($actualWatchSeconds > 0) {
                    $actualSeconds = $actualWatchSeconds;
                } elseif ($currentTime > 0) {
                    $actualSeconds = $currentTime;
                } elseif ($isFinished && $durationSeconds > 0) {
                    $actualSeconds = $durationSeconds;
                } elseif ($progress > 0 && $durationSeconds > 0) {
                    $actualSeconds = (int) round(($progress / 100) * $durationSeconds);
                } elseif ((float) ($cu->learning_hours ?? 0) > 0) {
                    $actualSeconds = (int) round((float) $cu->learning_hours * 3600);
                } else {
                    $actualSeconds = 0;
                }

                $currentHours = round($actualSeconds / 3600, 4);
                $totalHours = $durationSeconds > 0 ? round($durationSeconds / 3600, 4) : 0.0;

                $cu->current_learning_hours = $currentHours;
                $cu->total_learning_hours   = $totalHours;

                return [
                    'card_type'              => 'content',
                    'list'                   => $cu,
                    'current_learning_hours' => $currentHours,
                    'total_learning_hours'   => $totalHours,
                    'sort_activity'          => optional($cu->updated_at)->timestamp ?? 0,
                    'sort_progress'          => (int) ($cu->progress ?? 0),
                    'sort_duration'          => (int) ($cu->duration_seconds ?? 0),
                ];
            })
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

        $hasCompletedBucket = in_array('100', $filters['progress_buckets'], true);
        if ($filters['progress_sort'] === 'completed' || $hasCompletedBucket) {
            // allow finished contents if 100% bucket or completed progress sort is selected
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
        $dateFrom        = $filters['date_from'] ?? null;
        $dateTo          = $filters['date_to'] ?? null;
        $learningHour    = $filters['learning_hour'] ?? 'all';

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

        // Period filter (Date From & Date To)
        $query->when(filled($dateFrom), fn ($q) => $q->whereDate('created_at', '>=', $dateFrom));
        $query->when(filled($dateTo), fn ($q) => $q->whereDate('created_at', '<=', $dateTo));

        // Learning Hour filter (content.learning_hours)
        $query->when($learningHour !== 'all', function ($q) use ($learningHour) {
            $q->whereHas('content', function ($c) use ($learningHour) {
                switch ($learningHour) {
                    case 'lt1':  $c->where('learning_hours', '<', 1); break;
                    case '1-3':  $c->whereBetween('learning_hours', [1, 3]); break;
                    case 'gt3':  $c->where('learning_hours', '>', 3); break;
                }
            });
        });

        // Type (Video, Document, YouTube, dll)
        $query->when(!empty($realTypes), fn ($q) => $q->whereHas('content', fn ($c) => $c->whereIn('type', $realTypes)));

        // Learning status
        $query->when(!empty($learningStatus), fn ($q) => $q->whereIn('learning_status', $learningStatus));

        // Month
        $query->when(!empty($months), fn ($q) => $q->whereIn(DB::raw('MONTH(created_at)'), $months));

        // Progress buckets
        $query->when(!empty($progressBuckets), function ($q) use ($progressBuckets) {
            $q->where(function ($sub) use ($progressBuckets) {
                foreach ($progressBuckets as $bucket) {
                    switch ($bucket) {
                        case '0':     $sub->orWhere('progress', 0); break;
                        case '1-25':  $sub->orWhereBetween('progress', [1, 25]); break;
                        case '26-50': $sub->orWhereBetween('progress', [26, 50]); break;
                        case '51-75': $sub->orWhereBetween('progress', [51, 75]); break;
                        case '76-99': $sub->orWhereBetween('progress', [76, 99]); break;
                        case '100':   $sub->orWhere('progress', '>=', 100)->orWhere('finish', '1'); break;
                    }
                }
            });
        });

        // Durasi berdasarkan content_users.duration_seconds (detik) atau content.duration
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

        // Current learning time
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

        // Last page
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

        // Total pages
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
     * Bentuk card MOOC Learning On Going untuk user aktif.
     *
     * @return \Illuminate\Support\Collection<int, array>
     */
    public function moocCards(int $userId, array $filters): Collection
    {
        $query = ContentUser::with([
                'content.classification',
            ])
            ->where('user_id', $userId)
            ->withAvailableContent();

        // 1. Khusus tipe MOOC / YouTube
        $query->whereHas('content', fn ($q) => $q->whereIn('type', ['YouTube', 'MOOC']));

        // 2. Exclude content yang merupakan child/member dari Playlist
        $query->whereNotExists(function ($q) {
            $q->from('trainingplandetail')
                ->whereColumn('trainingplandetail.content_id', 'content_users.content_id');
        });

        // 3. Lifecycle ongoing MOOC:
        // finish = '0' (belum selesai) dan (learning_status = 'in_progress' atau progress > 0)
        $query->where('finish', '0')
            ->where(function ($q) {
                $q->where('learning_status', 'in_progress')
                    ->orWhere('progress', '>', 0);
            });

        // 4. Search filter jika ada
        if (!empty($filters['search'])) {
            $search = $filters['search'];
            $query->whereHas('content', function ($q) use ($search) {
                $q->where('title', 'like', '%' . $search . '%')
                    ->orWhere('description', 'like', '%' . $search . '%')
                    ->orWhere('author', 'like', '%' . $search . '%')
                    ->orWhereHas('classification', fn ($cq) => $cq->where('classification_name', 'like', '%' . $search . '%'));
            });
        }

        return $query->get()
            ->filter(fn ($cu) => $cu->content !== null)
            ->map(function ($cu) {
                $durationSeconds = (int) ($cu->duration_seconds ?? 0);
                if ($durationSeconds <= 0 && $cu->content && !empty($cu->content->duration)) {
                    $durationSeconds = \App\Support\DurationFormatter::toSeconds($cu->content->duration) ?? 0;
                }

                $cu->current_learning_hours = (float) ($cu->learning_hours ?? 0);
                $cu->total_learning_hours   = $durationSeconds > 0 ? round($durationSeconds / 3600, 4) : 0.0;

                return [
                    'card_type'              => 'mooc',
                    'list'                   => $cu,
                    'current_learning_hours' => $cu->current_learning_hours,
                    'total_learning_hours'   => $cu->total_learning_hours,
                    'sort_activity'          => optional($cu->updated_at)->timestamp ?? 0,
                    'sort_progress'          => (int) ($cu->progress ?? 0),
                    'sort_duration'          => $durationSeconds,
                ];
            })
            ->values();
    }
}
