<?php

namespace App\Services\LearningHours;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

/**
 * Service for Previewing Historical Playlist Progress Migration (Dry-Run).
 *
 * STRICTLY READ-ONLY:
 * Contains ZERO mutation queries (No INSERT, UPDATE, DELETE, UPSERT, TRUNCATE).
 */
class PlaylistProgressDryRunService
{
    public const DEFAULT_CUTOFF = '2026-08-28 23:59:59';
    public const DEFAULT_PER_PAGE = 25;

    /**
     * Get aggregate summary metrics for the KPI cards.
     * Cached for 5 minutes to prevent expensive repeated aggregations.
     *
     * @return array{
     *     old_playlists_count: int,
     *     new_playlists_count: int,
     *     contents_count: int,
     *     affected_users_count: int,
     *     total_candidates: int,
     *     existing_pcu_count: int,
     *     skipped_existing_count: int,
     *     to_insert_count: int,
     *     finished_count: int,
     *     in_progress_count: int,
     *     zero_count: int,
     * }
     */
    public function getSummaryMetrics(string $cutoff = self::DEFAULT_CUTOFF, ?int $playlistId = null): array
    {
        $cacheKey = 'playlist_dry_run_summary_' . md5($cutoff . '_' . ($playlistId ?? 'all'));

        return Cache::remember($cacheKey, 300, function () use ($cutoff, $playlistId) {
            // 1. Identify Old & New Playlists
            $oldPlaylistQuery = DB::table('trainingplan')->where('created_at', '<=', $cutoff);
            $newPlaylistQuery = DB::table('trainingplan')->where('created_at', '>', $cutoff);

            if ($playlistId !== null) {
                $oldPlaylistQuery->where('id', $playlistId);
                $newPlaylistQuery->where('id', $playlistId);
            }

            $oldPlaylistIds = $oldPlaylistQuery->pluck('id')->map(fn ($id) => (int) $id)->all();
            $newPlaylistCount = $newPlaylistQuery->count();
            $oldPlaylistCount = count($oldPlaylistIds);

            // 2. Distinct contents in old playlists
            $contentsCount = DB::table('trainingplandetail')
                ->whereIn('trainingplan_id', $oldPlaylistIds)
                ->distinct('content_id')
                ->count('content_id');

            // 3. Deduplicate content_users (best record per user_id & content_id)
            $dedupedCuQuery = DB::table('content_users')
                ->groupBy('user_id', 'content_id')
                ->selectRaw("
                    user_id,
                    content_id,
                    MAX(CASE WHEN finish = '1' OR progress >= 100 THEN 1 ELSE 0 END) as is_finished,
                    MAX(progress) as max_progress
                ");

            if ($playlistId !== null) {
                $specificContentIds = DB::table('trainingplandetail')
                    ->where('trainingplan_id', $playlistId)
                    ->pluck('content_id');
                $dedupedCuQuery->whereIn('content_id', $specificContentIds);
            }

            // 4. Candidate Query
            $candidateQuery = DB::table('trainingplandetail as tpd')
                ->join('trainingplan as tp', 'tp.id', '=', 'tpd.trainingplan_id')
                ->joinSub($dedupedCuQuery, 'cu', function ($join) {
                    $join->on('cu.content_id', '=', 'tpd.content_id');
                })
                ->where('tp.created_at', '<=', $cutoff);

            if ($playlistId !== null) {
                $candidateQuery->where('tp.id', $playlistId);
            }

            $stats = $candidateQuery->selectRaw("
                COUNT(*) as total_candidates,
                COUNT(DISTINCT cu.user_id) as affected_users,
                SUM(CASE WHEN cu.is_finished = 1 OR cu.max_progress >= 100 THEN 1 ELSE 0 END) as finished_count,
                SUM(CASE WHEN cu.is_finished = 0 AND cu.max_progress > 0 AND cu.max_progress < 100 THEN 1 ELSE 0 END) as in_progress_count,
                SUM(CASE WHEN cu.is_finished = 0 AND cu.max_progress = 0 THEN 1 ELSE 0 END) as zero_count
            ")->first();

            $totalCandidates = (int) ($stats->total_candidates ?? 0);
            $affectedUsers = (int) ($stats->affected_users ?? 0);
            $finishedCount = (int) ($stats->finished_count ?? 0);
            $inProgressCount = (int) ($stats->in_progress_count ?? 0);
            $zeroCount = (int) ($stats->zero_count ?? 0);

            // 5. Existing PCU records
            $existingPcuQuery = DB::table('playlist_content_users');
            if ($playlistId !== null) {
                $existingPcuQuery->where('trainingplan_id', $playlistId);
            }
            $existingPcuCount = $existingPcuQuery->count();

            // 6. Overlapping records (already exist in PCU)
            $existsQuery = DB::table('trainingplandetail as tpd')
                ->join('trainingplan as tp', 'tp.id', '=', 'tpd.trainingplan_id')
                ->joinSub($dedupedCuQuery, 'cu', function ($join) {
                    $join->on('cu.content_id', '=', 'tpd.content_id');
                })
                ->join('playlist_content_users as pcu', function ($join) {
                    $join->on('pcu.user_id', '=', 'cu.user_id')
                         ->on('pcu.trainingplan_id', '=', 'tpd.trainingplan_id')
                         ->on('pcu.content_id', '=', 'cu.content_id');
                })
                ->where('tp.created_at', '<=', $cutoff);

            if ($playlistId !== null) {
                $existsQuery->where('tp.id', $playlistId);
            }

            $skippedExistingCount = $existsQuery->count();
            $toInsertCount = max(0, $totalCandidates - $skippedExistingCount);

            return [
                'old_playlists_count'    => $oldPlaylistCount,
                'new_playlists_count'    => $newPlaylistCount,
                'contents_count'         => $contentsCount,
                'affected_users_count'   => $affectedUsers,
                'total_candidates'       => $totalCandidates,
                'existing_pcu_count'     => $existingPcuCount,
                'skipped_existing_count' => $skippedExistingCount,
                'to_insert_count'        => $toInsertCount,
                'finished_count'         => $finishedCount,
                'in_progress_count'      => $inProgressCount,
                'zero_count'             => $zeroCount,
            ];
        });
    }

    /**
     * Get validation statistics regarding duplicate content_users source data.
     *
     * @return array{
     *     duplicate_groups_count: int,
     *     total_duplicate_rows: int,
     *     conflicting_progress_groups: int,
     *     affected_pcu_records: int,
     *     prioritization_rules: array<string>,
     *     sample_conflicts: array<int, array{
     *         user_id: int,
     *         content_id: int,
     *         content_title: string,
     *         playlist_count: int,
     *         old_choice: string,
     *         new_choice: string,
     *         status: string
     *     }>
     * }
     */
    public function getSourceValidationMetrics(string $cutoff = self::DEFAULT_CUTOFF, ?int $playlistId = null): array
    {
        $cacheKey = 'playlist_dry_run_validation_' . md5($cutoff . '_' . ($playlistId ?? 'all'));

        return Cache::remember($cacheKey, 300, function () use ($cutoff, $playlistId) {
            $oldPlaylistQuery = DB::table('trainingplan')->where('created_at', '<=', $cutoff);
            if ($playlistId !== null) {
                $oldPlaylistQuery->where('id', $playlistId);
            }
            $oldPlaylistIds = $oldPlaylistQuery->pluck('id')->map(fn ($id) => (int) $id)->all();

            $linkedContentIds = DB::table('trainingplandetail')
                ->whereIn('trainingplan_id', $oldPlaylistIds)
                ->distinct('content_id')
                ->pluck('content_id')
                ->map(fn ($id) => (int) $id)
                ->all();

            if (empty($linkedContentIds)) {
                return [
                    'duplicate_groups_count'      => 0,
                    'total_duplicate_rows'        => 0,
                    'conflicting_progress_groups' => 0,
                    'affected_pcu_records'        => 0,
                    'prioritization_rules'        => [
                        '1. finish = 1',
                        '2. progress DESC',
                        '3. current_time DESC',
                        '4. id DESC',
                    ],
                    'sample_conflicts'            => [],
                ];
            }

            $duplicateGroups = DB::table('content_users')
                ->whereIn('content_id', $linkedContentIds)
                ->select('user_id', 'content_id', DB::raw('COUNT(*) as row_count'))
                ->groupBy('user_id', 'content_id')
                ->having('row_count', '>', 1)
                ->get();

            $totalDupRows = (int) $duplicateGroups->sum('row_count');
            $duplicateGroupsCount = $duplicateGroups->count();

            $conflictingCount = 0;
            $affectedPcuCount = 0;
            $sampleConflicts = [];

            foreach ($duplicateGroups as $group) {
                $rows = DB::table('content_users')
                    ->where('user_id', $group->user_id)
                    ->where('content_id', $group->content_id)
                    ->orderBy('id', 'asc')
                    ->get();

                $first = $rows[0];
                $maxProgress = $rows->max('progress');
                $maxFinish = $rows->max('finish');

                if ($first->progress < $maxProgress || ($first->finish == '0' && $maxFinish == '1')) {
                    $conflictingCount++;
                    $plCount = DB::table('trainingplandetail')
                        ->where('content_id', $group->content_id)
                        ->whereIn('trainingplan_id', $oldPlaylistIds)
                        ->count();
                    $affectedPcuCount += $plCount;

                    if (count($sampleConflicts) < 5) {
                        $bestRow = DB::table('content_users')
                            ->where('user_id', $group->user_id)
                            ->where('content_id', $group->content_id)
                            ->orderBy('user_id')
                            ->orderByDesc('finish')
                            ->orderByDesc('progress')
                            ->orderByDesc('current_time')
                            ->orderByDesc('id')
                            ->first();

                        $contentTitle = DB::table('content')->where('id', $group->content_id)->value('title') ?? "Content #{$group->content_id}";

                        $sampleConflicts[] = [
                            'user_id'        => (int) $group->user_id,
                            'content_id'     => (int) $group->content_id,
                            'content_title'  => $contentTitle,
                            'playlist_count' => $plCount,
                            'old_choice'     => "ID {$first->id} ({$first->progress}%, finish: {$first->finish})",
                            'new_choice'     => "ID {$bestRow->id} ({$bestRow->progress}%, finish: {$bestRow->finish})",
                            'status'         => 'RESOLVED',
                        ];
                    }
                }
            }

            return [
                'duplicate_groups_count'      => $duplicateGroupsCount,
                'total_duplicate_rows'        => $totalDupRows,
                'conflicting_progress_groups' => $conflictingCount,
                'affected_pcu_records'        => $affectedPcuCount,
                'prioritization_rules'        => [
                    '1. finish = 1 (completed courses prioritized)',
                    '2. progress DESC (highest completion rate)',
                    '3. current_time DESC (highest active watch time)',
                    '4. id DESC (latest recorded interaction)',
                ],
                'sample_conflicts'            => $sampleConflicts,
            ];
        });
    }

    /**
     * Get paginated candidate records for the preview table.
     * Uses strict server-side pagination with user, playlist, and content details.
     */
    public function getCandidatesPaginated(
        string $cutoff = self::DEFAULT_CUTOFF,
        ?int $playlistId = null,
        int $perPage = self::DEFAULT_PER_PAGE,
        int $page = 1,
        ?string $search = null
    ): LengthAwarePaginator {
        $perPage = max(10, min(100, $perPage));

        $dedupedCu = DB::table('content_users')
            ->groupBy('user_id', 'content_id')
            ->selectRaw("
                user_id,
                content_id,
                MAX(CASE WHEN finish = '1' OR progress >= 100 THEN 1 ELSE 0 END) as is_finished,
                MAX(progress) as progress,
                MAX(`current_time`) as `current_time`
            ");

        if ($playlistId !== null) {
            $specificContentIds = DB::table('trainingplandetail')
                ->where('trainingplan_id', $playlistId)
                ->pluck('content_id');
            $dedupedCu->whereIn('content_id', $specificContentIds);
        }

        $query = DB::table('trainingplandetail as tpd')
            ->join('trainingplan as tp', 'tp.id', '=', 'tpd.trainingplan_id')
            ->joinSub($dedupedCu, 'cu', function ($join) {
                $join->on('cu.content_id', '=', 'tpd.content_id');
            })
            ->join('content as c', 'c.id', '=', 'tpd.content_id')
            ->join('users as u', 'u.id', '=', 'cu.user_id')
            ->leftJoin('playlist_content_users as pcu', function ($join) {
                $join->on('pcu.user_id', '=', 'cu.user_id')
                     ->on('pcu.trainingplan_id', '=', 'tpd.trainingplan_id')
                     ->on('pcu.content_id', '=', 'cu.content_id');
            })
            ->where('tp.created_at', '<=', $cutoff);

        if ($playlistId !== null) {
            $query->where('tp.id', $playlistId);
        }

        if (!empty($search)) {
            $term = trim($search);
            $query->where(function ($q) use ($term) {
                $q->where('u.name', 'like', "%{$term}%")
                  ->orWhere('u.id_emp', 'like', "%{$term}%")
                  ->orWhere('c.title', 'like', "%{$term}%")
                  ->orWhere('tp.title', 'like', "%{$term}%");
            });
        }

        $query->select([
            'cu.user_id',
            'u.name as user_name',
            'u.id_emp',
            'u.ssohash',
            'tpd.trainingplan_id',
            'tp.title as playlist_title',
            'cu.content_id',
            'c.title as content_title',
            'c.type as content_type',
            'cu.progress',
            'cu.is_finished as finish',
            'cu.current_time',
            DB::raw('CASE WHEN pcu.id IS NOT NULL THEN 1 ELSE 0 END as already_exists'),
        ])
        ->orderBy('cu.user_id')
        ->orderBy('tpd.trainingplan_id')
        ->orderBy('cu.content_id');

        return $query->paginate($perPage, ['*'], 'page', $page);
    }

    /**
     * Get list of old playlists for filter select dropdown.
     */
    public function getPlaylistsForFilter(string $cutoff = self::DEFAULT_CUTOFF): Collection
    {
        return Cache::remember('playlist_dry_run_playlists_' . md5($cutoff), 300, function () use ($cutoff) {
            return DB::table('trainingplan')
                ->where('created_at', '<=', $cutoff)
                ->orderBy('title')
                ->select(['id', 'title', 'created_at'])
                ->get();
        });
    }
}
