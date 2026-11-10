<?php

namespace App\Services\Learning;

use App\Models\ContentUser;
use App\Models\PlaylistContentUser;
use App\Models\TrainingPlan;
use App\Models\TrainingPlanDetail;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * Business logic card Playlist (Training Plan) pada tab Learning On Going.
 *
 * Seluruh perhitungan Playlist (jumlah module, module completed, progress,
 * total durasi, status) berasal dari LearningProgressResolver — mendukung
 * progres playlist_content_users baru (authoritative) dan kompatibilitas
 * historical fallback Category A secara terisolasi.
 */
class OngoingPlaylistService
{
    public function __construct(private LearningProgressResolver $resolver)
    {
    }

    /** Same consumption precedence used by cards and hierarchical export children. */
    public function consumedSeconds(?PlaylistContentUser $pcu, int $moduleDuration): int
    {
        if (!$pcu) {
            return 0;
        }
        $duration = (int) ($pcu->duration_seconds ?? 0);
        $duration = $duration > 0 ? $duration : $moduleDuration;
        if ((int) $pcu->actual_watch_seconds > 0) {
            return (int) $pcu->actual_watch_seconds;
        }
        if ((int) $pcu->current_time > 0) {
            return (int) $pcu->current_time;
        }
        if ($pcu->isFinished() && $duration > 0) {
            return $duration;
        }
        if ((int) $pcu->progress > 0 && $duration > 0) {
            return (int) round($pcu->progress / 100 * $duration);
        }
        return (float) $pcu->learning_hour > 0 ? (int) round($pcu->learning_hour * 3600) : 0;
    }

    /**
     * Daftar Playlist (Training Plan) beserta agregat progres milik seorang user.
     *
     * Hanya playlist yang SUDAH DISENTUH user (ada content dgn progress > 0 atau finish = 1)
     * yang dikembalikan. Semua agregat dihitung dinamis via LearningProgressResolver.
     *
     * Setiap item memiliki atribut tambahan:
     *   total_items, finished_items, avg_progress, last_finished_at, last_activity_at,
     *   total_duration_seconds, progress_percent (0-100), is_completed (bool).
     *
     * @return \Illuminate\Support\Collection<int, TrainingPlan>
     */
    public function userPlaylists(int $userId): Collection
    {
        $plans = TrainingPlan::with([
                'trainingplandetail' => fn ($q) => $q->orderByRaw('CAST(content_order AS UNSIGNED) ASC'),
                'trainingplandetail.content' => fn ($q) => $q->where('active', 'Active'),
            ])
            ->select([
                'trainingplan.id',
                'trainingplan.title',
                'trainingplan.slug',
                'trainingplan.thumbnail',
                'trainingplan.total_duration_seconds',
                'trainingplan.learning_hours',
                'trainingplan.active',
                'trainingplan.created_at',
            ])
            ->where('active', 'Active')
            ->orderBy('trainingplan.title')
            ->get();

        $planIds = $plans->pluck('id')->all();
        $userPcuMap = PlaylistContentUser::where('user_id', $userId)
            ->whereIn('trainingplan_id', $planIds)
            ->get()
            ->groupBy('trainingplan_id');

        $result = collect();

        foreach ($plans as $plan) {
            $validDetails = $plan->trainingplandetail->filter(
                fn ($d) => !empty($d->content) && $d->content->exists && $d->content->active === 'Active'
            );

            $total = $validDetails->count();
            if ($total === 0) {
                continue;
            }

            $contentIds = $validDetails->pluck('content_id');

            // Batch resolve progress via LearningProgressResolver (PCU authoritative + Category A fallback)
            $resolvedBatch = $this->resolver->resolvePlaylistBatch($userId, $plan->id, $contentIds);

            // Only include playlists touched by user (started/in_progress/completed)
            $hasStarted = $resolvedBatch->filter(fn ($r) => $r->hasStarted())->isNotEmpty();
            if (!$hasStarted) {
                continue;
            }

            $finished    = $resolvedBatch->filter(fn ($r) => $r->isFinished())->count();
            $avgProgress = (int) round($resolvedBatch->avg(fn ($r) => $r->progress) ?? 0);

            // Progress playlist = module selesai / total module
            $plan->total_items      = $total;
            $plan->finished_items   = $finished;
            $plan->avg_progress     = $avgProgress;
            $plan->progress_percent = $total > 0 ? (int) round($finished / $total * 100) : 0;
            $plan->is_completed     = $total > 0 && $finished >= $total;

            $seconds = max(0, (int) ($plan->total_duration_seconds ?? 0));
            $plan->duration = \App\Support\DurationFormatter::fromSeconds($seconds);

            // Calculate actual consumed learning hours from playlist_content_users
            $planPcus = $userPcuMap->get($plan->id, collect())->keyBy('content_id');
            $consumedPlaylistSeconds = 0;
            $calculatedTotalSeconds = 0;

            foreach ($validDetails as $d) {
                $cid = $d->content_id;
                $pcu = $planPcus->get($cid);
                $moduleDuration = \App\Support\DurationFormatter::toSeconds($d->content?->duration ?? '') ?? 0;
                $calculatedTotalSeconds += $moduleDuration;

                $consumedPlaylistSeconds += $this->consumedSeconds($pcu, $moduleDuration);
            }

            $planTotalSec = max(0, (int) ($plan->total_duration_seconds ?? 0));
            if ($planTotalSec <= 0) {
                $planTotalSec = $calculatedTotalSeconds;
            }

            $plan->current_learning_hours = round($consumedPlaylistSeconds / 3600, 4);
            $plan->total_learning_hours   = $planTotalSec > 0 ? round($planTotalSec / 3600, 4) : 0.0;

            // Fetch activity timestamps
            $pcuTimestamps = PlaylistContentUser::where('user_id', $userId)
                ->where('trainingplan_id', $plan->id)
                ->whereIn('content_id', $contentIds)
                ->selectRaw('MAX(updated_at) as last_act, MAX(CASE WHEN finish = "1" OR progress >= 100 THEN (CASE WHEN completed_at IS NOT NULL AND completed_at <= updated_at THEN completed_at ELSE updated_at END) END) as last_fin')
                ->first();

            $cuTimestamps = ContentUser::where('user_id', $userId)
                ->whereIn('content_id', $contentIds)
                ->selectRaw('MAX(updated_at) as last_act, MAX(CASE WHEN finish = "1" OR progress >= 100 THEN (CASE WHEN completed_at IS NOT NULL AND completed_at <= updated_at THEN completed_at ELSE updated_at END) END) as last_fin')
                ->first();

            $lastActivity = $pcuTimestamps?->last_act ?? $cuTimestamps?->last_act ?? null;
            if ($pcuTimestamps?->last_act && $cuTimestamps?->last_act) {
                $lastActivity = max($pcuTimestamps->last_act, $cuTimestamps->last_act);
            }

            $lastFinished = $pcuTimestamps?->last_fin ?? $cuTimestamps?->last_fin ?? null;
            if ($pcuTimestamps?->last_fin && $cuTimestamps?->last_fin) {
                $lastFinished = max($pcuTimestamps->last_fin, $cuTimestamps->last_fin);
            }

            $plan->last_activity_at = $lastActivity;
            $plan->last_finished_at = $lastFinished;

            $result->push($plan);
        }

        return $result;
    }

    /**
     * Bentuk card Playlist mode "Playlist" pada Learning On Going: pilih kandidat
     * sesuai quick status, terapkan seluruh filter, lengkapi entry point
     * (content pertama playlist), lalu map ke struktur card.
     *
     * @param  \Illuminate\Support\Collection<int, TrainingPlan>  $userPlaylists  hasil userPlaylists()
     * @return \Illuminate\Support\Collection<int, array>
     */
    public function cards(Collection $userPlaylists, array $filters): Collection
    {
        // Playlist tidak memiliki konsep "paused" — status tersebut khusus Content.
        if ($filters['quick_status'] === 'paused') {
            return collect();
        }

        // Default tab ongoing = playlist dengan minimal 1 item selesai (finished_items >= 1 atau progress_percent > 0) dan belum 100%.
        // Quick status Completed = playlist 100%.
        // Quick status Not Started = playlist 0% (finished_items == 0).
        $plans = match ($filters['quick_status']) {
            'completed'   => $userPlaylists->filter->is_completed->values(),
            'not_started' => $userPlaylists->filter(fn ($p) => (int) ($p->finished_items ?? 0) === 0 && (int) ($p->progress_percent ?? 0) === 0)->values(),
            default       => $userPlaylists->filter(fn ($p) => (int) ($p->finished_items ?? 0) >= 1 && (int) ($p->progress_percent ?? 0) < 100 && !$p->is_completed)->values(),
        };

        $plans = $this->applyFilters($plans, $filters);

        // Entry point tiap playlist = content pertama — satu query untuk semua playlist.
        $firstSlugs = $this->firstContentSlugs($plans->pluck('id')->all());

        return $plans->map(function (TrainingPlan $plan) use ($firstSlugs) {
            $plan->first_content_slug = $firstSlugs[$plan->id] ?? null;

            return [
                'card_type'              => 'playlist',
                'plan'                   => $plan,
                'current_learning_hours' => $plan->current_learning_hours,
                'total_learning_hours'   => $plan->total_learning_hours,
                'sort_activity'          => $plan->last_activity_at ? strtotime($plan->last_activity_at) : 0,
                'sort_progress'          => (int) $plan->progress_percent,
                'sort_duration'          => (int) $plan->total_duration_seconds,
            ];
        })->values();
    }

    /** Return only incomplete playlists with at least one completed child, after normal filters. */
    public function ongoingCards(Collection $userPlaylists, array $filters): Collection
    {
        return $this->cards($userPlaylists, $filters)
            ->filter(function (array $card): bool {
                $plan = $card['plan'];
                $finished = (int) ($plan->finished_items ?? 0);
                $total = (int) ($plan->total_items ?? 0);

                return $finished > 0 && $total > $finished && !$plan->is_completed;
            })
            ->values();
    }

    /**
     * Saring koleksi Playlist secara in-memory (data sudah dimuat sekali, bebas N+1).
     *
     * @param  \Illuminate\Support\Collection<int, TrainingPlan>  $plans
     * @return \Illuminate\Support\Collection<int, TrainingPlan>
     */
    private function applyFilters(Collection $plans, array $filters): Collection
    {
        if ($filters['search'] !== '') {
            $plans = $plans->filter(fn ($p) => stripos((string) $p->title, $filters['search']) !== false);
        }

        if (!empty($filters['progress_buckets'])) {
            $plans = $plans->filter(
                fn ($p) => $this->matchesProgressBucket((int) $p->progress_percent, $filters['progress_buckets'])
            );
        }

        if (in_array($filters['quick_status'], ['not_started', 'ongoing'], true)) {
            $plans = $plans->filter(fn ($p) => $this->matchesQuickStatus($p, $filters['quick_status']));
        }

        // Year & Month playlist mengacu pada aktivitas terakhir user di playlist tsb.
        if ($filters['year'] !== 'all') {
            $plans = $plans->filter(
                fn ($p) => $p->last_activity_at && date('Y', strtotime($p->last_activity_at)) === (string) $filters['year']
            );
        }

        if (!empty($filters['months'])) {
            $months = array_map('intval', $filters['months']);
            $plans  = $plans->filter(
                fn ($p) => $p->last_activity_at && in_array((int) date('n', strtotime($p->last_activity_at)), $months, true)
            );
        }

        // Duration filter (total_duration_seconds)
        if (!empty($filters['durations'])) {
            $durations = $filters['durations'];
            $plans = $plans->filter(function ($p) use ($durations) {
                $sec = (int) ($p->total_duration_seconds ?? 0);
                foreach ($durations as $d) {
                    switch ($d) {
                        case 'lt10':  if ($sec < 600) return true; break;
                        case '10-30': if ($sec >= 600 && $sec <= 1800) return true; break;
                        case '30-60': if ($sec >= 1801 && $sec <= 3600) return true; break;
                        case 'gt60':  if ($sec > 3600) return true; break;
                    }
                }
                return false;
            });
        }

        // Period filter (Date From & Date To)
        $dateFrom = $filters['date_from'] ?? null;
        $dateTo   = $filters['date_to'] ?? null;
        if (filled($dateFrom)) {
            $plans = $plans->filter(function ($p) use ($dateFrom) {
                $dateStr = $p->last_activity_at ?? $p->created_at;
                return $dateStr && date('Y-m-d', strtotime($dateStr)) >= $dateFrom;
            });
        }
        if (filled($dateTo)) {
            $plans = $plans->filter(function ($p) use ($dateTo) {
                $dateStr = $p->last_activity_at ?? $p->created_at;
                return $dateStr && date('Y-m-d', strtotime($dateStr)) <= $dateTo;
            });
        }

        // Learning Hour filter
        $learningHour = $filters['learning_hour'] ?? 'all';
        if ($learningHour !== 'all') {
            $plans = $plans->filter(function ($p) use ($learningHour) {
                $lh = (float) ($p->learning_hours ?? (($p->total_duration_seconds ?? 0) / 3600));
                return match ($learningHour) {
                    'lt1' => $lh < 1.0,
                    '1-3' => $lh >= 1.0 && $lh <= 3.0,
                    'gt3' => $lh > 3.0,
                    default => true,
                };
            });
        }

        return $plans->values();
    }

    /**
     * Status playlist diturunkan dari progress module — bukan dari data Content.
     */
    private function matchesQuickStatus(TrainingPlan $plan, string $status): bool
    {
        $percent = (int) $plan->progress_percent;

        return match ($status) {
            'not_started' => $percent === 0,
            'ongoing'     => $percent >= 1 && $percent <= 99,
            default       => true,
        };
    }

    /**
     * Untuk sekumpulan playlist, ambil slug content PERTAMA (berdasar content_order)
     * sebagai entry point halaman playlist: /playlist/{playlistSlug}/{contentSlug}.
     *
     * Satu query untuk semua playlist sekaligus → bebas N+1.
     *
     * @param  array<int, mixed>  $planIds
     * @return \Illuminate\Support\Collection<string, ?string>  map [trainingplan_id => slug]
     */
    private function firstContentSlugs(array $planIds): Collection
    {
        if (empty($planIds)) {
            return collect();
        }

        $rows = DB::table('trainingplandetail as tpd')
            ->join('content as c', 'c.id', '=', 'tpd.content_id')
            ->whereIn('tpd.trainingplan_id', $planIds)
            ->whereNull('c.deleted_at')
            ->orderByRaw('CAST(tpd.content_order AS UNSIGNED) ASC')
            ->get(['tpd.trainingplan_id as plan_id', 'c.slug as slug']);

        return $rows->groupBy('plan_id')->map(fn ($items) => optional($items->first())->slug);
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
