<?php

namespace App\Services\LearningHours;

use App\Models\PlaylistContentUser;
use App\Support\DurationFormatter;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class PlaylistContentUserLearningHourManagementService
{
    /**
     * Build the server-side query for PlaylistContentUser DataTables / pagination.
     */
    public function buildDatatableQuery(Request $request): Builder
    {
        $query = PlaylistContentUser::query()
            ->with([
                'user:id,name,id_emp,ssohash,role',
                'trainingPlan:id,title,slug',
                'content:id,title,type,duration,slug,classification_id,learning_hours',
                'content.classification:id,classification_name,slug',
            ])
            ->select('playlist_content_users.*');

        $this->applyFilters($query, $request);

        return $query;
    }

    /**
     * Apply filter conditions at the database query level.
     */
    public function applyFilters(Builder $query, Request $request): void
    {
        // 1. Training Plan (Playlist) Filter
        if ($request->filled('trainingplan_id') && $request->trainingplan_id !== 'all') {
            $query->where('playlist_content_users.trainingplan_id', (int) $request->trainingplan_id);
        }

        // 2. Content Filter
        if ($request->filled('content_id') && $request->content_id !== 'all') {
            $query->where('playlist_content_users.content_id', (int) $request->content_id);
        }

        // 3. User Filter
        if ($request->filled('user_id') && $request->user_id !== 'all') {
            $query->where('playlist_content_users.user_id', (int) $request->user_id);
        }

        // 4. Finish / Learning Status Filter
        if ($request->filled('finish_status') && $request->finish_status !== 'all') {
            if ($request->finish_status === 'completed') {
                $query->where(function ($q) {
                    $q->where('playlist_content_users.finish', '1')
                      ->orWhere('playlist_content_users.progress', '>=', 100);
                });
            } elseif ($request->finish_status === 'in_progress') {
                $query->where('playlist_content_users.finish', '0')
                      ->where('playlist_content_users.progress', '<', 100);
            }
        }

        // 5. Calculation Source Filter
        if ($request->filled('calculation_source') && $request->calculation_source !== 'all') {
            if ($request->calculation_source === 'actual') {
                $query->where(function ($q) {
                    $q->where('playlist_content_users.actual_watch_seconds', '>', 0)
                      ->orWhere('playlist_content_users.current_time', '>', 0);
                });
            } elseif ($request->calculation_source === 'historical') {
                $query->where(function ($q) {
                    $q->where('playlist_content_users.actual_watch_seconds', '<=', 0)
                      ->orWhereNull('playlist_content_users.actual_watch_seconds');
                })->where('playlist_content_users.current_time', '<=', 0)
                  ->where(function ($q) {
                      $q->where('playlist_content_users.finish', '1')
                        ->orWhere('playlist_content_users.progress', '>=', 100);
                  });
            } elseif ($request->calculation_source === 'zero') {
                $query->where(function ($q) {
                    $q->where('playlist_content_users.actual_watch_seconds', '<=', 0)
                      ->orWhereNull('playlist_content_users.actual_watch_seconds');
                })->where('playlist_content_users.current_time', '<=', 0)
                  ->where('playlist_content_users.finish', '0')
                  ->where('playlist_content_users.progress', '<', 100);
            }
        }

        // 6. Actual Learning Hour Range Filter (Numeric)
        if ($request->filled('min_hour') && is_numeric($request->min_hour)) {
            $query->where('playlist_content_users.learning_hour', '>=', (float) $request->min_hour);
        }
        if ($request->filled('max_hour') && is_numeric($request->max_hour)) {
            $query->where('playlist_content_users.learning_hour', '<=', (float) $request->max_hour);
        }

        // 7. Content Type Filter (Video, Document, YouTube)
        if ($request->filled('content_type') && $request->content_type !== 'all') {
            $query->whereHas('content', function ($q) use ($request) {
                $q->where('type', $request->content_type);
            });
        }

        // 8. Classification Filter
        if ($request->filled('classification_slug') && $request->classification_slug !== 'all') {
            $query->whereHas('content.classification', function ($q) use ($request) {
                $q->where('slug', $request->classification_slug);
            });
        }

        // 9. Year Filter (based on completed_at)
        if ($request->filled('year') && $request->year !== 'all') {
            $year = (int) $request->year;
            if ($year > 2000 && $year < 2100) {
                $query->whereYear('playlist_content_users.completed_at', $year);
            }
        }

        // 10. Database-level Search across User, Content, and TrainingPlan
        $searchTerm = $request->input('search.value') ?? $request->input('q');
        if (!empty($searchTerm)) {
            $keyword = trim((string) $searchTerm);
            $query->where(function ($q) use ($keyword) {
                $q->whereHas('user', function ($uq) use ($keyword) {
                    $uq->where('name', 'like', "%{$keyword}%")
                       ->orWhere('id_emp', 'like', "%{$keyword}%");
                })
                ->orWhereHas('content', function ($cq) use ($keyword) {
                    $cq->where('title', 'like', "%{$keyword}%");
                })
                ->orWhereHas('trainingPlan', function ($tpq) use ($keyword) {
                    $tpq->where('title', 'like', "%{$keyword}%");
                });
            });
        }
    }

    /**
     * Get aggregate statistics for the KPI cards.
     * Cached for 5 minutes to maintain fast response times on large tables.
     *
     * @return array{
     *     total_records: int,
     *     total_learning_hours: float,
     *     actual_tracking_count: int,
     *     historical_fallback_count: int,
     *     zero_count: int,
     * }
     */
    public function getStatistics(): array
    {
        return Cache::remember('playlist_content_user_learning_hour_stats', 300, function () {
            $totalRecords = DB::table('playlist_content_users')->count();
            $totalHours = (float) (DB::table('playlist_content_users')->sum('learning_hour') ?? 0.0);
            $actualTrackingCount = DB::table('playlist_content_users')
                ->where(function ($q) {
                    $q->where('actual_watch_seconds', '>', 0)
                      ->orWhere('current_time', '>', 0);
                })->count();
            $historicalFallbackCount = DB::table('playlist_content_users')
                ->where(function ($q) {
                    $q->where('actual_watch_seconds', '<=', 0)
                      ->orWhereNull('actual_watch_seconds');
                })
                ->where('current_time', '<=', 0)
                ->where(function ($q) {
                    $q->where('finish', '1')->orWhere('progress', '>=', 100);
                })
                ->count();
            $zeroCount = DB::table('playlist_content_users')
                ->where(function ($q) {
                    $q->where('actual_watch_seconds', '<=', 0)
                      ->orWhereNull('actual_watch_seconds');
                })
                ->where('current_time', '<=', 0)
                ->where('finish', '0')
                ->where('progress', '<', 100)
                ->count();

            return [
                'total_records'             => $totalRecords,
                'total_learning_hours'      => round($totalHours, 4),
                'actual_tracking_count'     => $actualTrackingCount,
                'historical_fallback_count' => $historicalFallbackCount,
                'zero_count'                => $zeroCount,
            ];
        });
    }

    /**
     * Format current_time seconds into readable display string.
     * E.g. 1230 -> "00:20:30" with secondary badge "20m 30s".
     */
    public function formatCurrentTime(int $seconds): array
    {
        $hms = DurationFormatter::fromSeconds($seconds);
        $minutes = intdiv($seconds, 60);
        $remainingSeconds = $seconds % 60;
        $readable = $minutes > 0 ? "{$minutes}m {$remainingSeconds}s" : "{$remainingSeconds}s";

        return [
            'hms'      => $hms,
            'readable' => $readable,
            'seconds'  => $seconds,
        ];
    }

    /**
     * Format actual learning hour with exact 4 decimal precision.
     * E.g. 0.3417 -> "0.3417 h".
     */
    public function formatActualHours(?float $hours): string
    {
        if ($hours === null) {
            return '-';
        }

        return number_format($hours, 4, '.', '') . ' h';
    }

    /**
     * Format master learning hour from Content duration / learning_hours.
     * E.g. 0.5 -> "0.5 h".
     */
    public function formatMasterHours(?float $hours): string
    {
        if ($hours === null || $hours <= 0) {
            return '-';
        }

        return rtrim(rtrim(number_format($hours, 2, '.', ''), '0'), '.') . ' h';
    }

    /**
     * Resolve calculation source and badge presentation for a record.
     *
     * @return array{
     *     label: string,
     *     badge_class: string,
     *     icon: string,
     *     description: string,
     * }
     */
    public function resolveCalculationSource(PlaylistContentUser $row): array
    {
        $actualWatch = (int) ($row->actual_watch_seconds ?? 0);
        $currTime = (int) ($row->current_time ?? 0);
        $isFinished = ((string) $row->finish === '1' || (int) $row->progress >= 100);

        if ($actualWatch > 0) {
            return [
                'label'       => 'Actual Tracking',
                'badge_class' => 'bg-green-lt text-green border-green',
                'icon'        => 'ti-device-analytics',
                'description' => 'Calculated from actual watched seconds: ' . $actualWatch . 's / 3600',
            ];
        }

        if ($currTime > 0) {
            return [
                'label'       => 'Actual Tracking',
                'badge_class' => 'bg-green-lt text-green border-green',
                'icon'        => 'ti-device-analytics',
                'description' => 'Calculated from actual watched seconds: ' . $currTime . 's / 3600',
            ];
        }

        if ($isFinished) {
            return [
                'label'       => 'Historical Fallback',
                'badge_class' => 'bg-azure-lt text-azure border-azure',
                'icon'        => 'ti-history',
                'description' => 'Calculated from official master content duration fallback',
            ];
        }

        return [
            'label'       => 'Zero / Unfinished',
            'badge_class' => 'bg-secondary-lt text-secondary',
            'icon'        => 'ti-clock-pause',
            'description' => 'Unfinished record with zero tracked seconds',
        ];
    }
}
