<?php

namespace App\Services\Learning;

use App\Http\Controllers\LearningManageController;
use App\Http\Controllers\Manage\Record\RecordOfflineController;
use App\Models\ContentUser;
use App\Models\PlaylistContentUser;
use App\Support\DurationFormatter;
use Carbon\Carbon;
use Illuminate\Database\Query\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Yajra\DataTables\Facades\DataTables;

class LearningDatatableService
{
    public const PLAYLIST_CUTOFF = LearningManageController::PLAYLIST_CUTOFF;

    protected static ?bool $hasPcuStatus = null;

    protected array $l2ScoreCache = [];

    /**
     * Resolve Score (L2) from assesments and assesment_results for online records.
     */
    public function resolveL2Score(int $userId, string $sourceType, ?int $contentId, ?int $trainingplanId): ?float
    {
        $cacheKey = "{$userId}_{$sourceType}_{$contentId}_{$trainingplanId}";
        if (array_key_exists($cacheKey, $this->l2ScoreCache)) {
            return $this->l2ScoreCache[$cacheKey];
        }

        $query = DB::table('assesment_results as ar')
            ->join('assesments as a', 'a.id', '=', 'ar.assesment_id')
            ->where('ar.user_id', $userId)
            ->where('ar.final', 1);

        if ($sourceType === 'playlist') {
            $query->where(function ($q) use ($contentId, $trainingplanId) {
                $matched = false;
                if ($trainingplanId) {
                    $q->where('a.trainingplan_id', $trainingplanId);
                    $matched = true;
                }
                if ($contentId) {
                    if ($matched) {
                        $q->orWhere('a.content_id', $contentId);
                    } else {
                        $q->where('a.content_id', $contentId);
                        $matched = true;
                    }
                }
                if (!$matched) {
                    $q->whereRaw('1 = 0');
                }
            });
        } else {
            if ($contentId) {
                $query->where('a.content_id', $contentId);
            } else {
                $this->l2ScoreCache[$cacheKey] = null;
                return null;
            }
        }

        $score = $query->orderByDesc('ar.score')->orderByDesc('ar.id')->value('ar.score');

        $this->l2ScoreCache[$cacheKey] = ($score !== null && is_numeric($score)) ? (float) $score : null;

        return $this->l2ScoreCache[$cacheKey];
    }

    /**
     * Check if playlist_content_users table has status column (cached per process).
     */
    public static function hasPcuStatusColumn(): bool
    {
        if (self::$hasPcuStatus === null) {
            try {
                self::$hasPcuStatus = \Illuminate\Support\Facades\Schema::hasColumn('playlist_content_users', 'status');
            } catch (\Throwable $e) {
                self::$hasPcuStatus = false;
            }
        }

        return self::$hasPcuStatus;
    }

    /**
     * Build unified query builder for learning data (ContentUser + PlaylistContentUser).
     */
    public static function buildBaseQuery(array $filters = [], string $type = '', string $slug = ''): Builder
    {
        $categoryParam = strtolower(trim((string) ($filters['category'] ?? ($filters['learning_source'] ?? ($filters['source'] ?? 'all')))));

        $cuSelect = DB::table('content_users as cu')
            ->select([
                'cu.id as source_id',
                DB::raw("'content' as source_type"),
                DB::raw("'All Content' as category_source"),
                'cu.user_id',
                'cu.content_id',
                DB::raw('CAST(NULL AS UNSIGNED) as trainingplan_id'),
                'cu.status',
                'cu.finish',
                'cu.progress',
                'cu.current_time',
                'cu.actual_watch_seconds',
                'cu.afk_duration_seconds',
                'cu.created_at',
                'cu.updated_at',
                'cu.completed_at',
                'cu.learning_hours',
            ]);

        $hasPcuStatus = self::hasPcuStatusColumn();
        $pcuStatusSelect = $hasPcuStatus ? 'pcu.status' : DB::raw("'non mandatory' as status");

        $pcuSelect = DB::table('playlist_content_users as pcu')
            ->select([
                'pcu.id as source_id',
                DB::raw("'playlist' as source_type"),
                DB::raw("'Playlist' as category_source"),
                'pcu.user_id',
                'pcu.content_id',
                'pcu.trainingplan_id',
                $pcuStatusSelect,
                'pcu.finish',
                'pcu.progress',
                'pcu.current_time',
                'pcu.actual_watch_seconds',
                'pcu.afk_duration_seconds',
                'pcu.created_at',
                'pcu.updated_at',
                'pcu.completed_at',
                'pcu.learning_hour as learning_hours',
            ]);

        // 1. Status Filter Pushdown
        $status = $filters['status'] ?? null;
        if (filled($status) && $status !== 'all') {
            $statusStr = strtolower(trim((string) $status));
            if ($statusStr === 'finished' || $statusStr === 'finish' || $statusStr === '1') {
                $cuSelect->where('cu.finish', '1');
                $pcuSelect->where('pcu.finish', '1');
            } elseif ($statusStr === 'ongoing' || $statusStr === '0') {
                $cuSelect->where('cu.finish', '0')->where('cu.progress', '>', 0);
                $pcuSelect->where('pcu.finish', '0')->where('pcu.progress', '>', 0);
            } elseif ($statusStr === 'not_started') {
                $cuSelect->where('cu.finish', '0')->where(function ($q) {
                    $q->where('cu.progress', 0)->orWhereNull('cu.progress');
                });
                $pcuSelect->where('pcu.finish', '0')->where(function ($q) {
                    $q->where('pcu.progress', 0)->orWhereNull('pcu.progress');
                });
            } elseif (in_array($statusStr, ['mandatory', 'non mandatory', 'non-mandatory', 'optional'], true)) {
                $dbStatusVal = ($statusStr === 'mandatory') ? 'mandatory' : 'non mandatory';
                $cuSelect->where('cu.status', $dbStatusVal);
                if ($hasPcuStatus) {
                    $pcuSelect->where('pcu.status', $dbStatusVal);
                } else {
                    $pcuSelect->whereRaw('1 = 0');
                }
            } elseif ($statusStr === 'playlist') {
                $cuSelect->whereRaw('1 = 0');
            }
        }

        // 2. Program Type / Status Filter Pushdown
        $programType = $filters['program_type'] ?? ($filters['program'] ?? ($filters['status_type'] ?? null));
        if (filled($programType) && $programType !== 'all') {
            $programTypeStr = strtolower(trim((string) $programType));
            if (in_array($programTypeStr, ['mandatory', 'non mandatory', 'non-mandatory', 'optional'], true)) {
                $dbProgramVal = ($programTypeStr === 'mandatory') ? 'mandatory' : 'non mandatory';
                $cuSelect->where('cu.status', $dbProgramVal);
                if ($hasPcuStatus) {
                    $pcuSelect->where('pcu.status', $dbProgramVal);
                } else {
                    $pcuSelect->whereRaw('1 = 0');
                }
            } elseif ($programTypeStr === 'playlist') {
                $cuSelect->whereRaw('1 = 0');
            }
        }

        // 3. Year Filter Pushdown
        $year = $filters['year'] ?? ($filters['years'] ?? null);
        if (is_array($year) && !empty($year)) {
            $yearInts = array_map('intval', array_filter($year, 'is_numeric'));
            if (!empty($yearInts)) {
                $cuSelect->whereIn(DB::raw('YEAR(cu.created_at)'), $yearInts);
                $pcuSelect->whereIn(DB::raw('YEAR(pcu.created_at)'), $yearInts);
            }
        } elseif (filled($year) && $year !== 'all') {
            $cuSelect->whereYear('cu.created_at', (int) $year);
            $pcuSelect->whereYear('pcu.created_at', (int) $year);
        }

        // 4. Date Range Filter Pushdown
        $fromDate = $filters['from_date'] ?? ($filters['date_from'] ?? null);
        $toDate = $filters['to_date'] ?? ($filters['date_to'] ?? null);
        if (filled($fromDate)) {
            try {
                $fromDate = Carbon::parse(str_replace('/', '-', $fromDate))->format('Y-m-d');
            } catch (\Throwable $e) {
            }
        }
        if (filled($toDate)) {
            try {
                $toDate = Carbon::parse(str_replace('/', '-', $toDate))->format('Y-m-d');
            } catch (\Throwable $e) {
            }
        }
        if (filled($fromDate) && filled($toDate)) {
            $cuSelect->whereBetween('cu.created_at', [$fromDate . ' 00:00:00', $toDate . ' 23:59:59']);
            $pcuSelect->whereBetween('pcu.created_at', [$fromDate . ' 00:00:00', $toDate . ' 23:59:59']);
        } elseif (filled($fromDate)) {
            $cuSelect->whereDate('cu.created_at', '>=', $fromDate);
            $pcuSelect->whereDate('pcu.created_at', '>=', $fromDate);
        } elseif (filled($toDate)) {
            $cuSelect->whereDate('cu.created_at', '<=', $toDate);
            $pcuSelect->whereDate('pcu.created_at', '<=', $toDate);
        }

        // 5. Progress Range Filter Pushdown
        $progressRange = $filters['progress_range'] ?? null;
        if (filled($progressRange) && $progressRange !== 'all') {
            $progressVal = str_replace([' ', '%'], '', (string) $progressRange);
            switch ($progressVal) {
                case '0':
                    $cuSelect->where(function ($q) {
                        $q->where('cu.progress', 0)->orWhereNull('cu.progress');
                    });
                    $pcuSelect->where(function ($q) {
                        $q->where('pcu.progress', 0)->orWhereNull('pcu.progress');
                    });
                    break;
                case '1-25':
                    $cuSelect->whereBetween('cu.progress', [1, 25]);
                    $pcuSelect->whereBetween('pcu.progress', [1, 25]);
                    break;
                case '26-50':
                    $cuSelect->whereBetween('cu.progress', [26, 50]);
                    $pcuSelect->whereBetween('pcu.progress', [26, 50]);
                    break;
                case '51-75':
                    $cuSelect->whereBetween('cu.progress', [51, 75]);
                    $pcuSelect->whereBetween('pcu.progress', [51, 75]);
                    break;
                case '76-99':
                    $cuSelect->whereBetween('cu.progress', [76, 99]);
                    $pcuSelect->whereBetween('pcu.progress', [76, 99]);
                    break;
                case '100':
                    $cuSelect->where('cu.progress', 100);
                    $pcuSelect->where('pcu.progress', 100);
                    break;
            }
        }

        // 6. Classification Filter Pushdown (via indexed content_id)
        $classParam = $filters['classification_slug'] ?? ($filters['classification'] ?? ($filters['classification_id'] ?? ($type === 'category' ? $slug : null)));
        if (filled($classParam) && $classParam !== 'all') {
            $classContentQuery = DB::table('content')
                ->join('classification', 'classification.id', '=', 'content.classification_id');
            if (is_numeric($classParam)) {
                $classContentQuery->where('classification.id', (int) $classParam);
            } else {
                $classContentQuery->where(function ($q) use ($classParam) {
                    $q->where('classification.slug', $classParam)
                      ->orWhere('classification.classification_name', $classParam);
                });
            }
            $classContentIds = $classContentQuery->pluck('content.id')->toArray();
            $cuSelect->whereIn('cu.content_id', !empty($classContentIds) ? $classContentIds : [-1]);
            $pcuSelect->whereIn('pcu.content_id', !empty($classContentIds) ? $classContentIds : [-1]);
        }

        // 6b. Content Type Filter Pushdown (Document / Video / MOOC)
        $typeFilter = $filters['type'] ?? ($filters['content_type'] ?? null);
        if (filled($typeFilter) && $typeFilter !== 'all') {
            $typeStr = strtolower(trim((string) $typeFilter));
            $typeContentIds = DB::table('content')
                ->where(function ($q) use ($typeStr) {
                    if ($typeStr === 'document') {
                        $q->where('type', 'Document');
                    } elseif ($typeStr === 'video') {
                        $q->whereIn('type', ['Video', 'YouTube']);
                    } else {
                        $q->where('type', $typeStr);
                    }
                })
                ->pluck('id')
                ->toArray();
            $cuSelect->whereIn('cu.content_id', !empty($typeContentIds) ? $typeContentIds : [-1]);
            $pcuSelect->whereIn('pcu.content_id', !empty($typeContentIds) ? $typeContentIds : [-1]);
        }

        // 7. Content Filter Pushdown (via indexed content_id)
        $contentSlug = $filters['content_slug'] ?? ($type === 'content' ? $slug : null);
        if (filled($contentSlug)) {
            $contentId = DB::table('content')->where('slug', $contentSlug)->value('id');
            $cuSelect->where('cu.content_id', $contentId ?: -1);
            $pcuSelect->where('pcu.content_id', $contentId ?: -1);
        }

        // 7b. Playlist Filter Pushdown (via indexed trainingplan_id)
        $playlistParam = $filters['playlist_id'] ?? ($filters['trainingplan_id'] ?? ($type === 'playlist' ? $slug : null));
        if (filled($playlistParam)) {
            $playlistId = is_numeric($playlistParam)
                ? (int) $playlistParam
                : (int) DB::table('trainingplan')->where('slug', $playlistParam)->value('id');

            // Standalone content_users records never belong to a playlist
            $cuSelect->whereRaw('1 = 0');
            $pcuSelect->where('pcu.trainingplan_id', $playlistId ?: -1);
        }

        // 8. Employee ID / NIP / Name Filter Pushdown (via indexed user_id)
        if (isset($filters['user_id']) && is_numeric($filters['user_id'])) {
            $exactUserId = (int) $filters['user_id'];
            $cuSelect->where('cu.user_id', $exactUserId);
            $pcuSelect->where('pcu.user_id', $exactUserId);
        } else {
            $employeeParam = $filters['employee_id'] ?? ($filters['employee'] ?? ($filters['employees'] ?? null));
            if (is_array($employeeParam) && !empty($employeeParam)) {
                $userIds = DB::table('users')
                    ->where(function ($q) use ($employeeParam) {
                        $q->whereIn('id_emp', $employeeParam)
                          ->orWhereIn('name', $employeeParam);
                    })
                    ->pluck('id')
                    ->toArray();
                $cuSelect->whereIn('cu.user_id', !empty($userIds) ? $userIds : [-1]);
                $pcuSelect->whereIn('pcu.user_id', !empty($userIds) ? $userIds : [-1]);
            } elseif (filled($employeeParam) && $employeeParam !== 'all') {
                if (is_numeric($employeeParam)) {
                    // Check if numeric ID matches user ID directly or exact id_emp
                    $exactUserIds = DB::table('users')
                        ->where('id', (int) $employeeParam)
                        ->orWhere('id_emp', (string) $employeeParam)
                        ->pluck('id')
                        ->toArray();
                    $cuSelect->whereIn('cu.user_id', !empty($exactUserIds) ? $exactUserIds : [-1]);
                    $pcuSelect->whereIn('pcu.user_id', !empty($exactUserIds) ? $exactUserIds : [-1]);
                } else {
                    $userIds = DB::table('users')
                        ->where(function ($q) use ($employeeParam) {
                            $q->where('id_emp', 'like', '%' . $employeeParam . '%')
                              ->orWhere('name', 'like', '%' . $employeeParam . '%');
                        })
                        ->pluck('id')
                        ->toArray();
                    $cuSelect->whereIn('cu.user_id', !empty($userIds) ? $userIds : [-1]);
                    $pcuSelect->whereIn('pcu.user_id', !empty($userIds) ? $userIds : [-1]);
                }
            }
        }

        // 9. Learning Title Filter Pushdown (via indexed content_id and trainingplan_id)
        $learningTitleParam = $filters['learning_title'] ?? ($filters['title'] ?? ($filters['content_title'] ?? null));
        if (is_array($learningTitleParam)) {
            $learningTitles = array_values(array_filter(array_map('trim', $learningTitleParam), fn($t) => $t !== '' && strtolower($t) !== 'all'));
            if (!empty($learningTitles)) {
                $contentIds = DB::table('content')->whereIn('title', $learningTitles)->pluck('id')->toArray();
                $tpIds = DB::table('trainingplan')->whereIn('title', $learningTitles)->pluck('id')->toArray();

                $cuSelect->whereIn('cu.content_id', !empty($contentIds) ? $contentIds : [-1]);
                $pcuSelect->where(function ($q) use ($contentIds, $tpIds) {
                    $matched = false;
                    if (!empty($contentIds)) {
                        $q->whereIn('pcu.content_id', $contentIds);
                        $matched = true;
                    }
                    if (!empty($tpIds)) {
                        if ($matched) {
                            $q->orWhereIn('pcu.trainingplan_id', $tpIds);
                        } else {
                            $q->whereIn('pcu.trainingplan_id', $tpIds);
                            $matched = true;
                        }
                    }
                    if (!$matched) {
                        $q->whereRaw('1 = 0');
                    }
                });
            }
        } elseif (filled($learningTitleParam) && strtolower(trim((string) $learningTitleParam)) !== 'all') {
            $contentIds = DB::table('content')->where('title', 'like', '%' . $learningTitleParam . '%')->pluck('id')->toArray();
            $tpIds = DB::table('trainingplan')->where('title', 'like', '%' . $learningTitleParam . '%')->pluck('id')->toArray();

            $cuSelect->whereIn('cu.content_id', !empty($contentIds) ? $contentIds : [-1]);
            $pcuSelect->where(function ($q) use ($contentIds, $tpIds) {
                $matched = false;
                if (!empty($contentIds)) {
                    $q->whereIn('pcu.content_id', $contentIds);
                    $matched = true;
                }
                if (!empty($tpIds)) {
                    if ($matched) {
                        $q->orWhereIn('pcu.trainingplan_id', $tpIds);
                    } else {
                        $q->whereIn('pcu.trainingplan_id', $tpIds);
                        $matched = true;
                    }
                }
                if (!$matched) {
                    $q->whereRaw('1 = 0');
                }
            });
        }

        // 10. Search Param Pushdown
        $searchParam = $filters['search'] ?? null;
        $search = is_array($searchParam)
            ? trim((string) ($searchParam['value'] ?? ''))
            : trim((string) ($searchParam ?? ''));

        if ($search !== '' && !filled($employeeParam) && !filled($learningTitleParam)) {
            $searchLower = strtolower($search);

            $searchUserIds = DB::table('users')
                ->where('name', 'like', "%{$search}%")
                ->orWhere('id_emp', 'like', "%{$search}%")
                ->pluck('id')
                ->toArray();

            $searchContentIds = DB::table('content')
                ->leftJoin('classification', 'classification.id', '=', 'content.classification_id')
                ->where('content.title', 'like', "%{$search}%")
                ->orWhere('content.duration', 'like', "%{$search}%")
                ->orWhere('classification.classification_name', 'like', "%{$search}%")
                ->pluck('content.id')
                ->toArray();

            $searchTpIds = DB::table('trainingplan')
                ->where('title', 'like', "%{$search}%")
                ->pluck('id')
                ->toArray();

            $statusMatches = [];
            foreach (['mandatory', 'optional', 'playlist'] as $st) {
                if (str_contains($st, $searchLower)) {
                    $statusMatches[] = $st;
                }
            }
            $matchCatContent = str_contains('all content', $searchLower);
            $matchCatPlaylist = str_contains('playlist', $searchLower);

            $cuSelect->where(function ($q) use ($searchUserIds, $searchContentIds, $statusMatches, $matchCatContent) {
                $matched = false;
                if (!empty($searchUserIds)) {
                    $q->orWhereIn('cu.user_id', $searchUserIds);
                    $matched = true;
                }
                if (!empty($searchContentIds)) {
                    $q->orWhereIn('cu.content_id', $searchContentIds);
                    $matched = true;
                }
                if (!empty($statusMatches)) {
                    $q->orWhereIn('cu.status', $statusMatches);
                    $matched = true;
                }
                if ($matchCatContent) {
                    $q->orWhereRaw('1 = 1');
                    $matched = true;
                }
                if (!$matched) {
                    $q->whereRaw('1 = 0');
                }
            });

            $pcuSelect->where(function ($q) use ($searchUserIds, $searchContentIds, $searchTpIds, $statusMatches, $matchCatPlaylist) {
                $matched = false;
                if (!empty($searchUserIds)) {
                    $q->orWhereIn('pcu.user_id', $searchUserIds);
                    $matched = true;
                }
                if (!empty($searchContentIds)) {
                    $q->orWhereIn('pcu.content_id', $searchContentIds);
                    $matched = true;
                }
                if (!empty($searchTpIds)) {
                    $q->orWhereIn('pcu.trainingplan_id', $searchTpIds);
                    $matched = true;
                }
                if (!empty($statusMatches)) {
                    if ($hasPcuStatus) {
                        $q->orWhereIn('pcu.status', $statusMatches);
                    }
                    $matched = true;
                }
                if (in_array('playlist', $statusMatches) || $matchCatPlaylist) {
                    $q->orWhereRaw('1 = 1');
                    $matched = true;
                }
                if (!$matched) {
                    $q->whereRaw('1 = 0');
                }
            });
        }

        // Union Assembly
        if ($categoryParam === 'all_content') {
            $union = $cuSelect;
        } elseif ($categoryParam === 'playlist' || filled($playlistParam)) {
            $union = $pcuSelect;
        } else {
            $union = $cuSelect->unionAll($pcuSelect);
        }

        $query = DB::query()
            ->fromSub($union, 'unified')
            ->leftJoin('users as u', 'u.id', '=', 'unified.user_id')
            ->leftJoin('content as c', 'c.id', '=', 'unified.content_id')
            ->leftJoin('classification as cl', 'cl.id', '=', 'c.classification_id')
            ->leftJoin('trainingplan as tp', 'tp.id', '=', 'unified.trainingplan_id')
            ->select([
                'unified.source_id',
                'unified.source_type',
                'unified.category_source',
                'unified.user_id',
                'unified.content_id',
                'unified.trainingplan_id',
                'unified.status',
                'unified.finish',
                'unified.progress',
                'unified.current_time',
                'unified.actual_watch_seconds',
                'unified.afk_duration_seconds',
                'unified.created_at',
                'unified.updated_at',
                'unified.completed_at',
                'unified.learning_hours',
                'u.id_emp',
                'u.id_emp as user_id_emp',
                'u.name as employee_name',
                'u.name as user_name',
                'u.jobgrade as user_jobgrade',
                'u.jobposition as user_jobposition',
                'u.jobgrade',
                'u.jobposition',
                'u.department as user_department',
                'u.team as user_team',
                'u.company_name as user_company',
                'u.ssohash as user_ssohash',
                'c.title as content_title',
                'c.slug as content_slug',
                'c.type as content_type',
                'c.duration as content_duration',
                'cl.id as classification_id',
                'cl.classification_name',
                'cl.slug as classification_slug',
                'tp.title as playlist_title',
            ]);

        // 11. Duration Filter (calculated from content duration)
        $duration = $filters['duration'] ?? null;
        if (filled($duration) && $duration !== 'all') {
            $durationInput = strtolower(trim((string) $duration));
            $durationRange = match ($durationInput) {
                'lt10', 'under_10', '< 10 minutes', '<10min', '<10' => [null, 600, false, true],
                '10-30', '10_30', '10 - 30 minutes', '10-30min' => [600, 1800, true, false],
                '30-60', '30_60', '30 - 60 minutes', '30-60min' => [1800, 3600, false, false],
                'gt60', 'above_60', '> 1 hour', '>1hour', '>60', '>60min' => [3600, null, false, false],
                default => null,
            };

            if ($durationRange !== null) {
                [$minimum, $maximum, $includeMinimum, $excludeMaximum] = $durationRange;
                $durationInSeconds = "CASE
                    WHEN c.duration IS NULL OR TRIM(c.duration) = '' THEN NULL
                    WHEN LENGTH(c.duration) - LENGTH(REPLACE(c.duration, ':', '')) = 1 THEN
                        CAST(SUBSTRING_INDEX(c.duration, ':', 1) AS UNSIGNED) * 60
                        + CAST(SUBSTRING_INDEX(c.duration, ':', -1) AS UNSIGNED)
                    WHEN LENGTH(c.duration) - LENGTH(REPLACE(c.duration, ':', '')) = 2 THEN
                        CAST(SUBSTRING_INDEX(c.duration, ':', 1) AS UNSIGNED) * 3600
                        + CAST(SUBSTRING_INDEX(SUBSTRING_INDEX(c.duration, ':', 2), ':', -1) AS UNSIGNED) * 60
                        + CAST(SUBSTRING_INDEX(c.duration, ':', -1) AS UNSIGNED)
                    ELSE NULL
                END";

                if ($minimum !== null) {
                    $query->whereRaw($durationInSeconds . ($includeMinimum ? ' >= ?' : ' > ?'), [$minimum]);
                }
                if ($maximum !== null) {
                    $query->whereRaw($durationInSeconds . ($excludeMaximum ? ' < ?' : ' <= ?'), [$maximum]);
                }
            }
        }

        // 11b. Department, Company, Division & Team Filters
        $department = $filters['department'] ?? ($filters['departments'] ?? 'all');
        if (is_array($department) && !empty($department)) {
            $query->whereIn('u.department', $department);
        } elseif (is_string($department) && filled($department) && $department !== 'all') {
            $query->where('u.department', trim($department));
        }

        $company = $filters['company'] ?? ($filters['companies'] ?? 'all');
        if (is_array($company) && !empty($company)) {
            $query->whereIn('u.company_name', $company);
        } elseif (is_string($company) && filled($company) && $company !== 'all') {
            $query->where('u.company_name', trim($company));
        }

        $division = $filters['division'] ?? ($filters['divisions'] ?? 'all');
        if (is_array($division) && !empty($division)) {
            $query->whereIn('u.division', $division);
        } elseif (is_string($division) && filled($division) && $division !== 'all') {
            $query->where('u.division', trim($division));
        }

        $team = $filters['team'] ?? ($filters['teams'] ?? 'all');
        if (is_array($team) && !empty($team)) {
            $query->whereIn('u.team', $team);
        } elseif (is_string($team) && filled($team) && $team !== 'all') {
            $query->where('u.team', 'like', '%' . trim($team) . '%');
        }

        // 12. Sorting
        $sort = $filters['sort'] ?? null;
        if (filled($sort)) {
            switch ($sort) {
                case 'newest':
                    $query->orderBy('unified.created_at', 'desc');
                    break;
                case 'oldest':
                    $query->orderBy('unified.created_at', 'asc');
                    break;
                case 'highest_progress':
                    $query->orderBy('unified.progress', 'desc');
                    break;
                case 'lowest_progress':
                    $query->orderBy('unified.progress', 'asc');
                    break;
            }
        }

        return $query;
    }

    public function make(Request $request)
    {
        $entries = (int) ($request->input('entries') ?: $request->input('length', 25));
        if ($entries > 0) {
            $request->merge(['length' => $entries]);
            request()->merge(['length' => $entries]);
        }
        app()->forgetInstance('datatables.request');

        $categoryParam = strtolower(trim((string) ($request->input('category') ?? ($request->input('learning_source') ?? ($request->input('source') ?? 'all')))));

        $hasFilters = (filled($request->input('status')) && $request->input('status') !== 'all')
            || (filled($request->input('type')) && $request->input('type') !== 'all')
            || (filled($request->input('program_type')) && $request->input('program_type') !== 'all')
            || (filled($request->input('program')) && $request->input('program') !== 'all')
            || (filled($request->input('status_type')) && $request->input('status_type') !== 'all')
            || (filled($request->input('year')) && $request->input('year') !== 'all')
            || filled($request->input('from_date'))
            || filled($request->input('to_date'))
            || (filled($request->input('progress_range')) && $request->input('progress_range') !== 'all')
            || (filled($request->input('duration')) && $request->input('duration') !== 'all')
            || filled($request->input('classification_slug'))
            || filled($request->input('content_slug'))
            || (filled($request->input('employee_id')) && $request->input('employee_id') !== 'all')
            || (filled($request->input('user_id')) && $request->input('user_id') !== 'all')
            || (filled($request->input('employee')) && $request->input('employee') !== 'all')
            || filled($request->input('learning_title'))
            || filled($request->input('title'))
            || filled($request->input('content_title'))
            || filled(trim((string) $request->input('search.value')))
            || (is_string($request->input('search')) && trim($request->input('search')) !== '');

        $totalAll = ContentUser::count() + PlaylistContentUser::count();

        $query = self::buildBaseQuery($request->all());

        $dataTable = DataTables::query($query)
            ->setTotalRecords($totalAll);

        if ($hasFilters) {
            $dataTable->setFilteredRecords($query->count());
        } elseif ($categoryParam === 'all_content') {
            $dataTable->setFilteredRecords(ContentUser::count());
        } elseif ($categoryParam === 'playlist') {
            $dataTable->setFilteredRecords(PlaylistContentUser::count());
        } else {
            $dataTable->setFilteredRecords($totalAll);
        }

        return $dataTable
            ->addIndexColumn()
            ->setRowId(fn ($row) => $row->source_type . ':' . $row->source_id)
            ->addColumn('source_badge', fn ($row) => '<span class="badge bg-blue-lt text-blue">KP OWL</span>')
            ->addColumn('source', fn ($row) => '<span class="badge bg-blue-lt text-blue">KP OWL</span>')
            ->addColumn('status_badge', fn ($row) => $this->statusBadge($row))
            ->addColumn('id_emp', fn ($row) => $this->idEmpColumn($row))
            ->addColumn('employee_id', fn ($row) => $this->idEmpColumn($row))
            ->addColumn('employee', fn ($row) => $this->employeeColumn($row))
            ->addColumn('employee_name', fn ($row) => $this->employeeColumn($row))
            ->addColumn('team', fn ($row) => e($row->user_team ?: '-'))
            ->addColumn('department', fn ($row) => e($row->user_department ?: '-'))
            ->addColumn('company', fn ($row) => e($row->user_company ?: '-'))
            ->addColumn('grade', fn ($row) => e($row->jobgrade ?? ($row->user_jobgrade ?: '-')))
            ->addColumn('job_position', fn ($row) => e($row->jobposition ?? ($row->user_jobposition ?: '-')))
            ->addColumn('learning_title', fn ($row) => $this->learningTitleColumn($row))
            ->addColumn('course_title', fn ($row) => $this->learningTitleColumn($row))
            ->addColumn('sub_course_title', fn () => '<span class="text-muted small">-</span>')
            ->addColumn('type_badge', fn ($row) => $this->typeBadge($row))
            ->addColumn('type', fn ($row) => $this->typeBadge($row))
            ->addColumn('category_badge', fn ($row) => $this->categoryBadge($row))
            ->addColumn('category', fn ($row) => $this->categoryBadge($row))
            ->addColumn('level', fn () => '<span class="text-muted small">-</span>')
            ->addColumn('batch', fn () => '<span class="text-muted small">-</span>')
            ->addColumn('classification_name', fn ($row) => $this->classificationColumn($row))
            ->addColumn('main_classification', fn ($row) => $this->classificationColumn($row))
            ->addColumn('sub_classification', fn () => '<span class="text-muted small">-</span>')
            ->addColumn('delivery_method', fn () => '<span class="badge bg-blue-lt text-blue">Online</span>')
            ->addColumn('sub_delivery_method', fn ($row) => e($row->category_source === 'Playlist' ? 'Playlist / Path' : 'Self-Paced / LMS'))
            ->addColumn('provider_name', fn () => 'KP OWL')
            ->addColumn('venue', fn () => 'Online LMS')
            ->addColumn('duration', fn ($row) => e(DurationFormatter::format($row->content_duration ?? null)))
            ->addColumn('course_duration', fn ($row) => e(DurationFormatter::format($row->content_duration ?? null)))
            ->addColumn('recognized_duration', function ($row) {
                $sec = app(\App\Services\Learning\LearningRecordRecognizedDurationService::class)->resolveFromRow($row);
                return '<span class="fw-semibold font-monospace">' . DurationFormatter::fromSeconds($sec) . '</span>';
            })
            ->addColumn('total_recognized_duration', function ($row) {
                $sec = app(\App\Services\Learning\LearningRecordRecognizedDurationService::class)->resolveFromRow($row);
                return '<span class="fw-semibold font-monospace">' . DurationFormatter::fromSeconds($sec) . '</span>';
            })
            ->addColumn('total_engagement_duration', function ($row) {
                $hours = (float) ($row->learning_hours ?? 0);
                $fmt = ($hours == (int) $hours) ? (int) $hours . ' h' : rtrim(rtrim(number_format($hours, 2, '.', ''), '0'), '.') . ' h';
                return '<span class="fw-semibold">' . $fmt . '</span>';
            })
            ->addColumn('total_engagement_time', function ($row) {
                $hours = (float) ($row->learning_hours ?? 0);
                $fmt = ($hours == (int) $hours) ? (int) $hours . ' h' : rtrim(rtrim(number_format($hours, 2, '.', ''), '0'), '.') . ' h';
                return '<span class="fw-semibold">' . $fmt . '</span>';
            })
            ->addColumn('learning_hours', function ($row) {
                $hours = (float) ($row->learning_hours ?? 0);
                return ($hours == (int) $hours) ? (int) $hours . ' h' : rtrim(rtrim(number_format($hours, 2, '.', ''), '0'), '.') . ' h';
            })
            ->addColumn('learning_date', fn ($row) => $row->created_at ? Carbon::parse($row->created_at)->format('d M Y') : '-')
            ->addColumn('date_time_merged', function ($row) {
                $startDate = $row->created_at ? Carbon::parse($row->created_at)->format('Y-m-d') : null;
                $startTime = $row->created_at ? Carbon::parse($row->created_at)->format('H:i') : null;
                $endDate = $row->completed_at ? Carbon::parse($row->completed_at)->format('Y-m-d') : ($row->finish == 1 && $row->updated_at ? Carbon::parse($row->updated_at)->format('Y-m-d') : null);
                $endTime = $row->completed_at ? Carbon::parse($row->completed_at)->format('H:i') : ($row->finish == 1 && $row->updated_at ? Carbon::parse($row->updated_at)->format('H:i') : null);

                return RecordOfflineController::renderMergedDateTime($startDate, $endDate, $startTime, $endTime);
            })
            ->addColumn('record_month', function ($row) {
                $d = $row->completed_at ?: ($row->finish == 1 ? $row->updated_at : $row->created_at);
                if (!empty($d) && $d !== '-') {
                    try {
                        return Carbon::parse($d)->format('F');
                    } catch (\Throwable $e) {
                        return '-';
                    }
                }
                return '-';
            })
            ->addColumn('requirement', function ($row) {
                $status = strtolower((string) ($row->status ?? 'non mandatory'));
                return $status === 'mandatory' ? '<span class="badge bg-red-lt text-red">Mandatory</span>' : '<span class="badge bg-blue-lt text-blue">Non-Mandatory</span>';
            })
            ->addColumn('attendance', function ($row) {
                $progress = (float) ($row->progress ?? 0);
                if ($progress > 0 || $row->finish == 1) {
                    return '<span class="badge bg-success-lt text-success fw-bold px-2 py-1">Yes</span>';
                }
                return '<span class="badge bg-danger-lt text-danger fw-bold px-2 py-1">No</span>';
            })
            ->addColumn('attendance_reason', fn () => '<span class="text-muted small">-</span>')
            ->addColumn('score_l1', fn () => '<span class="text-muted small">-</span>')
            ->addColumn('score_l2', function ($row) {
                $score = $this->resolveL2Score(
                    (int) $row->user_id,
                    (string) ($row->source_type ?? 'content'),
                    !empty($row->content_id) ? (int) $row->content_id : null,
                    !empty($row->trainingplan_id) ? (int) $row->trainingplan_id : null
                );
                if ($score !== null) {
                    return '<span class="fw-semibold">' . number_format($score, 1) . '</span>';
                }
                return '<span class="text-muted small">-</span>';
            })
            ->addColumn('score_l3', fn () => '<span class="text-muted small">-</span>')
            ->addColumn('pic', fn () => '<span class="text-muted small">-</span>')
            ->addColumn('certificate', fn () => '<span class="text-muted small">-</span>')
            ->addColumn('progress_bar', fn ($row) => $this->progressBar($row))
            ->addColumn('progress_percent', fn ($row) => $this->progressBar($row))
            ->addColumn('progress_status', fn ($row) => $this->progressStatusBadge($row))
            ->addColumn('action', fn ($row) => $this->actionColumn($row))
            ->orderColumn('status_badge', 'unified.status $1')
            ->orderColumn('id_emp', 'u.id_emp $1')
            ->orderColumn('employee_id', 'u.id_emp $1')
            ->orderColumn('employee', 'u.name $1')
            ->orderColumn('employee_name', 'u.name $1')
            ->orderColumn('team', 'u.team $1')
            ->orderColumn('department', 'u.department $1')
            ->orderColumn('company', 'u.company_name $1')
            ->orderColumn('learning_title', 'c.title $1')
            ->orderColumn('course_title', 'c.title $1')
            ->orderColumn('type_badge', 'c.type $1')
            ->orderColumn('type', 'c.type $1')
            ->orderColumn('classification_name', 'cl.classification_name $1')
            ->orderColumn('main_classification', 'cl.classification_name $1')
            ->orderColumn('category_badge', 'unified.category_source $1')
            ->orderColumn('category', 'unified.category_source $1')
            ->orderColumn('duration', 'c.duration $1')
            ->orderColumn('course_duration', 'c.duration $1')
            ->orderColumn('learning_hours', 'unified.learning_hours $1')
            ->orderColumn('total_engagement_time', 'unified.learning_hours $1')
            ->orderColumn('learning_date', 'unified.created_at $1')
            ->orderColumn('date_time_merged', 'unified.created_at $1')
            ->orderColumn('record_month', 'unified.created_at $1')
            ->orderColumn('requirement', 'unified.status $1')
            ->orderColumn('progress_bar', 'unified.progress $1')
            ->orderColumn('progress_percent', 'unified.progress $1')
            ->orderColumn('progress_status', 'unified.finish $1')
            ->rawColumns([
                'source_badge',
                'source',
                'status_badge',
                'id_emp',
                'employee_id',
                'employee',
                'employee_name',
                'learning_title',
                'course_title',
                'sub_course_title',
                'type_badge',
                'type',
                'classification_name',
                'main_classification',
                'sub_classification',
                'category_badge',
                'category',
                'level',
                'batch',
                'delivery_method',
                'sub_delivery_method',
                'recognized_duration',
                'total_recognized_duration',
                'total_engagement_duration',
                'total_engagement_time',
                'progress_bar',
                'progress_percent',
                'progress_status',
                'date_time_merged',
                'requirement',
                'attendance',
                'attendance_reason',
                'score_l1',
                'score_l2',
                'score_l3',
                'pic',
                'certificate',
                'action',
            ])
            ->make(true);
    }

    private function progressStatusBadge($row): string
    {
        if ((int) ($row->finish ?? 0) === 1) {
            return '<span class="badge bg-success-lt text-success">Finished</span>';
        }
        if ((float) ($row->progress ?? 0) > 0) {
            return '<span class="badge bg-warning-lt text-warning">Ongoing</span>';
        }
        return '<span class="badge bg-secondary-lt text-secondary">Not Started</span>';
    }

    private function typeBadge($row): string
    {
        $rawType = (string) ($row->content_type ?? '');
        $type = in_array(strtolower($rawType), ['video', 'youtube', 'mooc'], true) ? 'Video' : 'Document';

        if ($type === 'Video') {
            return '<span class="badge" style="background-color: #f59e0b; color: #ffffff; font-size: 11.5px; font-weight: 600; padding: 4px 10px; border-radius: 999px;">Video</span>';
        }

        return '<span class="badge" style="background-color: #0ea5e9; color: #ffffff; font-size: 11.5px; font-weight: 600; padding: 4px 10px; border-radius: 999px;">Document</span>';
    }

    private function statusBadge($row): string
    {
        $status = strtolower((string) ($row->status ?? 'non mandatory'));

        if ($status === 'mandatory') {
            return '<span class="badge bg-red-lt text-red">Mandatory</span>';
        }

        if (in_array($status, ['non mandatory', 'non-mandatory', 'optional'], true)) {
            return '<span class="badge bg-blue-lt text-blue">Non-Mandatory</span>';
        }

        if ($status === 'playlist') {
            return '<span class="badge bg-purple-lt text-purple">Playlist</span>';
        }

        return '<span class="badge bg-secondary-lt text-secondary">' . e(ucfirst((string) ($row->status ?? '-'))) . '</span>';
    }

    private function categoryBadge($row): string
    {
        if ($row->category_source === 'Playlist') {
            if (!empty($row->playlist_title) && !empty($row->trainingplan_id)) {
                $url = route('learning.manage.playlist', ['id' => $row->trainingplan_id]);

                return '<div class="playlist-category text-start">'
                    . '<span class="fw-semibold playlist-category-label">Playlist:</span> '
                    . '<a href="' . e($url) . '" class="text-primary fw-semibold playlist-cell-link" title="' . e($row->playlist_title) . '">'
                    . e($row->playlist_title)
                    . '</a>'
                    . '</div>';
            }

            return '<div class="playlist-category text-start">'
                . '<span class="fw-semibold playlist-category-label">Playlist</span>'
                . '</div>';
        }

        return '<span class="badge bg-azure-lt text-azure">All Content</span>';
    }

    private function idEmpColumn($row): string
    {
        $idEmp = $row->id_emp ?? '-';
        if ($idEmp !== '-') {
            $idEmp = preg_replace('/\s*[\/\-]\s*NIP.*$/i', '', trim((string) $idEmp));
        }

        return '<span class="fw-semibold text-secondary">' . e($idEmp) . '</span>';
    }

    private function employeeColumn($row): string
    {
        $name = $row->employee_name ?? '-';

        if (empty($row->user_ssohash)) {
            return e($name);
        }

        return '<a href="' . route('user.manage.profile', $row->user_ssohash) . '" class="fw-semibold">' . e($name) . '</a>';
    }

    private function learningTitleColumn($row): string
    {
        if (empty($row->content_title)) {
            return '-';
        }

        $titleLink = !empty($row->content_slug)
            ? '<a href="' . route('learning.manage.content', $row->content_slug) . '" class="text-reset fw-bold text-center">' . e($row->content_title) . '</a>'
            : '<span class="fw-bold text-center">' . e($row->content_title) . '</span>';

        $rawReq = $row->requirement ?? ($row->content_requirement ?? ($row->subtitle ?? 'Non-Mandatory'));
        $req = !empty($rawReq) ? htmlspecialchars($rawReq) : 'Non-Mandatory';

        return '<div class="d-flex flex-column align-items-center justify-content-center text-center w-100">'
            . '<span class="fw-bold text-dark text-center fs-6 mb-1">' . $titleLink . '</span>'
            . '<small class="text-muted text-center fs-7">Requirement: <span class="fw-semibold">' . $req . '</span></small>'
            . '</div>';
    }

    private function classificationColumn($row): string
    {
        if (empty($row->classification_name)) {
            return '-';
        }

        if (!empty($row->classification_slug)) {
            return '<a href="' . route('learning.manage.category', $row->classification_slug) . '" class="text-reset">'
                . e($row->classification_name)
                . '</a>';
        }

        return e($row->classification_name);
    }

    private function progressBar($row): string
    {
        $progress = max(0, min(100, (int) ($row->progress ?? 0)));

        return '<div class="d-flex align-items-center gap-2">'
            . '<div class="progress progress-sm flex-fill" style="min-width:80px;">'
            . '<div class="progress-bar bg-primary" style="width: ' . $progress . '%"></div>'
            . '</div>'
            . '<span class="small text-muted">' . $progress . '%</span>'
            . '</div>';
    }

    private function actionColumn($row): string
    {
        $id = $row->source_id ?? '';
        $sourceType = $row->source_type ?? 'content';
        $editUrl = route('manage.record.edit', $id);

        return '<div class="dropdown">
            <button class="btn btn-sm btn-icon btn-ghost-secondary" type="button" data-bs-toggle="dropdown" aria-expanded="false">
                <i class="ti ti-dots-vertical"></i>
            </button>
            <ul class="dropdown-menu dropdown-menu-end shadow-sm">
                <li>
                    <a class="dropdown-item d-flex align-items-center gap-2 btn-view-learning-detail btn-more-info" href="javascript:void(0);" data-source-type="' . e($sourceType) . '" data-source-id="' . e($id) . '" data-id="' . e($id) . '" data-record-id="' . e($id) . '" data-source="online" data-record-source="online">
                        <i class="ti ti-eye text-primary"></i> More Information
                    </a>
                </li>
                <li>
                    <a class="dropdown-item d-flex align-items-center gap-2" href="' . e($editUrl) . '">
                        <i class="ti ti-edit text-warning"></i> Edit Record
                    </a>
                </li>
                <li>
                    <a class="dropdown-item d-flex align-items-center gap-2 text-danger" href="#" data-bs-toggle="modal" data-bs-target="#deleteRecordModal" data-record-id="' . e($id) . '" data-record-source="online">
                        <i class="ti ti-trash"></i> Delete Record
                    </a>
                </li>
                <li>
                    <a class="dropdown-item d-flex align-items-center gap-2 disabled text-muted" href="#" onclick="return false;" style="cursor: not-allowed;">
                        <i class="ti ti-certificate"></i> Generate Certificate
                    </a>
                </li>
            </ul>
        </div>';
    }
}