<?php

namespace App\Services\Learning;

use App\Models\ContentUser;
use App\Models\TrainingPlan;
use App\Models\TrainingPlanDetail;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * Business logic card Playlist (Training Plan) pada tab Learning On Going.
 *
 * Seluruh perhitungan Playlist (jumlah module, module completed, progress,
 * total durasi, status) HANYA berasal dari agregat playlist itu sendiri —
 * terpisah penuh dari flow Content (OngoingContentService).
 */
class OngoingPlaylistService
{
    /**
     * Daftar Playlist (Training Plan) beserta agregat progres milik seorang user.
     *
     * Hanya playlist yang SUDAH DISENTUH user (ada content dgn progress > 0 atau finish = 1)
     * yang dikembalikan. Semua agregat dihitung dinamis via subquery — tidak ada kolom baru
     * dan tidak ada query di dalam loop (bebas N+1).
     *
     * Setiap item memiliki atribut tambahan:
     *   total_items, finished_items, avg_progress, last_finished_at, last_activity_at,
     *   total_duration_seconds, progress_percent (0-100), is_completed (bool).
     *
     * @return \Illuminate\Support\Collection<int, TrainingPlan>
     */
    public function userPlaylists(int $userId): Collection
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
                ContentUser::selectRaw('COALESCE(SUM(content_users.duration_seconds), 0)')
                    ->join('trainingplandetail', 'trainingplandetail.content_id', '=', 'content_users.content_id')
                    ->whereColumn('trainingplandetail.trainingplan_id', 'trainingplan.id')
                    ->where('content_users.user_id', $userId),
                'total_duration_seconds'
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

                // Progress playlist = module selesai / total module (bukan progress content).
                $plan->progress_percent = $total > 0 ? (int) round($finished / $total * 100) : 0;
                $plan->is_completed     = $total > 0 && $finished >= $total;

                return $plan;
            });
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

        // Default tab ongoing = playlist belum 100%; quick status Completed = playlist 100%.
        $plans = $filters['quick_status'] === 'completed'
            ? $userPlaylists->filter->is_completed->values()
            : $userPlaylists->reject->is_completed->values();

        $plans = $this->applyFilters($plans, $filters);

        // Entry point tiap playlist = content pertama — satu query untuk semua playlist.
        $firstSlugs = $this->firstContentSlugs($plans->pluck('id')->all());

        return $plans->map(function (TrainingPlan $plan) use ($firstSlugs) {
            $plan->first_content_slug = $firstSlugs[$plan->id] ?? null;

            return [
                'card_type'     => 'playlist',
                'plan'          => $plan,
                'sort_activity' => $plan->last_activity_at ? strtotime($plan->last_activity_at) : 0,
                'sort_progress' => (int) $plan->progress_percent,
                'sort_duration' => (int) $plan->total_duration_seconds,
            ];
        })->values();
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
