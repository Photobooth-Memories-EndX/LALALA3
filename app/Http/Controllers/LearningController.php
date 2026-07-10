<?php

namespace App\Http\Controllers;

use App\Models\Assesment;
use App\Models\AssesmentResult;
use App\Models\ContentUser;
use App\Models\CertificateSetting;
use App\Models\Certificate;
use App\Models\TrainingPlan;
use App\Models\TrainingPlanDetail;
use App\Models\User;
use App\Services\CertificateEligibilityService;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Yajra\DataTables\Facades\DataTables;

class LearningController extends Controller
{
    public function ongoing()
    {
        $content_user = ContentUser::with('content')
            ->where('user_id', auth()->id())
            ->where('finish', '0')
            ->latest()
            ->get();

        return view('user.learning.ongoing', compact('content_user'));
    }

    public function record(CertificateEligibilityService $eligibilityService)
    {
        $userId = auth()->id();

        $content_user = ContentUser::with([
                'content.classification',
                'assesment.scores',
            ])
            ->where('user_id', $userId)
            ->where('finish', '1')
            ->latest()
            ->get();

        $certificateSettings = CertificateSetting::with([
                'content',
                'trainingPlan.trainingplandetail.content',
                'assessment',
            ])
            ->where('is_active', 1)
            ->get();

        $certificates = Certificate::where('user_id', $userId)
            ->get()->keyBy('certificate_setting_id');

        $certificateOpportunities = $certificateSettings->map(function ($setting) use ($eligibilityService, $certificates) {
            return [
                'setting'     => $setting,
                'eligibility' => $eligibilityService->check($setting, auth()->user()),
                'certificate' => $certificates->get($setting->id),
            ];
        });

        return view('user.learning.finish', compact(
            'content_user',
            'certificateSettings',
            'certificateOpportunities'
        ));
    }

    public function assesment()
    {
        $user   = Auth::user();
        $userId = Auth::id();

        $assessments = Assesment::with([
                'assesments_assigned',
                'contents',
                'scores',
            ])
            ->whereHas('assesments_assigned', function ($query) use ($user) {
                $query->where('assigned_id', $user->id_emp)
                    ->orWhere('assigned_id', $user->id_team ?? $user->id_tean)
                    ->orWhere('assigned_id', $user->id_department);
            })
            ->orWhereHas('contents', function ($query) {
                $query->whereHas('contentuser', function ($q) {
                    $q->where('progress', '>=', 100)->where('user_id', Auth::id());
                });
            })
            ->orWhereHas('scores', function ($query) {
                $query->where('user_id', Auth::id());
            })
            ->latest()
            ->get();

        $result = AssesmentResult::with('assesment')
            ->where('user_id', $userId)
            ->whereFinal(1)
            ->latest()
            ->get();

        return view('user.learning.result', compact('result', 'assessments'));
    }

    /**
     * Lightweight dashboard – only loads summary counts and profile data.
     * Tab content is fetched on-demand via dashboardPartial().
     */
    public function dashboard()
    {
        $userId = Auth::id();
        $user   = Auth::user();

        $learningHours = $this->getUserLearningHours($userId);

        $totalLearning = ContentUser::where('user_id', $userId)->count();

        $completedLearning = ContentUser::where('user_id', $userId)
            ->where(function ($q) {
                $q->where('progress', '>=', 100)->orWhere('finish', '1');
            })
            ->count();

        $ongoingLearning = ContentUser::where('user_id', $userId)
            ->where('finish', '0')
            ->count();

        $passedAssessment = AssesmentResult::where('user_id', $userId)
            ->where('final', 1)
            ->where('pass', 1)
            ->count();

        $averageProgress = (int) round(ContentUser::where('user_id', $userId)->avg('progress') ?? 0);

        return view('user.learning.dashboard', compact(
            'user',
            'learningHours',
            'totalLearning',
            'completedLearning',
            'ongoingLearning',
            'passedAssessment',
            'averageProgress'
        ));
    }

    /**
     * Returns HTML fragment for a single tab.
     * Called via AJAX from dashboard-script.blade.php.
     */
    public function dashboardPartial(string $tab, CertificateEligibilityService $eligibilityService)
    {
        $user   = Auth::user();
        $userId = Auth::id();

        switch ($tab) {
            case 'ongoing':
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

            case 'record':
                $content_user = $this->getCompletedContents($userId);

                $averageProgress = (int) round(
                    ContentUser::where('user_id', $userId)->where('finish', '1')->avg('progress') ?? 0
                );

                $certificateSettings = CertificateSetting::select([
                        'id', 'certificate_type', 'content_id', 'is_active',
                        'minimum_progress', 'minimum_score',
                    ])
                    ->where('is_active', 1)
                    ->get();

                // Playlist (Training Plan) yang telah diselesaikan 100% oleh user.
                $completedPlaylists = $this->getCompletedPlaylists($userId);

                return view('user.learning.dashboard-partials.record', compact(
                    'content_user',
                    'averageProgress',
                    'certificateSettings',
                    'completedPlaylists'
                ));

            case 'assessment':
                $assessments = $this->getUserAssessments($user, $userId);
                $result      = $this->getUserAssessmentResults($userId);

                return view('user.learning.dashboard-partials.assessment', compact(
                    'assessments',
                    'result'
                ));

            case 'claim':
                return view('user.learning.dashboard-partials.claim');

            case 'certificate':
                $userCertificates = Certificate::where('user_id', $userId)->latest()->get();

                return view('user.learning.dashboard-partials.certificate', compact('userCertificates'));

            case 'monitoring':
                return $this->monitoringTab($user, $userId);

            default:
                abort(404);
        }
    }

    // ─── Private helpers ────────────────────────────────────────────────────────

    private function getUserLearningHours($userId)
    {
        $hours = ContentUser::join('content', 'content_users.content_id', '=', 'content.id')
            ->where('content_users.user_id', $userId)
            ->whereNull('content.deleted_at')
            ->whereNotNull('content.duration')
            ->where('content.duration', '!=', '')
            ->sum(DB::raw("
                CASE
                    WHEN LENGTH(content.duration) - LENGTH(REPLACE(content.duration, ':', '')) = 2
                    THEN TIME_TO_SEC(content.duration) / 3600

                    WHEN LENGTH(content.duration) - LENGTH(REPLACE(content.duration, ':', '')) = 1
                    THEN TIME_TO_SEC(CONCAT('00:', content.duration)) / 3600

                    ELSE 0
                END
            "));

        return round($hours, 1);
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

    private function getUserAssessments($user, $userId)
    {
        return Assesment::with([
                'assesments_assigned',
                'contents',
                'scores'      => fn ($q) => $q->where('user_id', $userId),
                'trainingplan',
            ])
            ->where(function ($query) use ($user, $userId) {
                $query->whereHas('assesments_assigned', function ($q) use ($user) {
                    $q->where('assigned_id', $user->id_emp)
                        ->orWhere('assigned_id', $user->id_team ?? $user->id_tean)
                        ->orWhere('assigned_id', $user->id_department);
                })
                ->orWhereHas('contents', function ($q) use ($userId) {
                    $q->whereHas('contentuser', function ($q) use ($userId) {
                        $q->where('progress', '>=', 100)->where('user_id', $userId);
                    });
                })
                ->orWhereHas('scores', function ($q) use ($userId) {
                    $q->where('user_id', $userId);
                });
            })
            ->latest()
            ->get();
    }

    private function getUserAssessmentResults($userId)
    {
        return AssesmentResult::with('assesment.trainingplan')
            ->where('user_id', $userId)
            ->whereFinal(1)
            ->latest()
            ->get();
    }

    /*
    |--------------------------------------------------------------------------
    | My Learning Monitoring (personal vs organization/team)
    |--------------------------------------------------------------------------
    */

    /**
     * Render tab monitoring sesuai role.
     * Role 1/2 -> personal insight. Role 3/4 -> organization/team monitoring.
     */
    private function monitoringTab($user, $userId)
    {
        $analyticsService = app(\App\Services\LearningAnalyticsService::class);

        if ($user->isUser() || $user->isAdmin()) {
            $recentLearning = ContentUser::with([
                    'content:id,title,slug,duration,classification_id',
                    'content.classification:id,classification_name',
                ])
                ->where('user_id', $userId)
                ->latest('updated_at')
                ->limit(5)
                ->get();

            $recentAssessment = AssesmentResult::with('assesment:id,title')
                ->where('user_id', $userId)
                ->latest()
                ->limit(5)
                ->get();

            return view('user.learning.dashboard-partials.monitoring', [
                'mode'             => 'personal',
                'summary'          => $this->buildLearningSummaryForUser($user),
                'analytics'        => $analyticsService->personal((int) $userId),
                'recentLearning'   => $recentLearning,
                'recentAssessment' => $recentAssessment,
            ]);
        }

        [$scopeType, $scopeValue] = $this->myLearningMonitoringScope();
        $isScopeEmpty = $user->isSubadminDivision() && $scopeType === null;

        // Hanya bangun analytics bila scope valid (hindari query sia-sia saat empty).
        $teamAnalytics = null;
        if (!$isScopeEmpty) {
            $cacheKey = $user->isSuperAdmin() ? 'all' : ($scopeType . ':' . $scopeValue);
            $teamAnalytics = $analyticsService->team($cacheKey, fn () => $this->myLearningMonitoringScopeQuery());
        }

        return view('user.learning.dashboard-partials.monitoring', [
            'mode'          => 'organization',
            'isSuperAdmin'  => $user->isSuperAdmin(),
            'scopeType'     => $scopeType,
            'scopeValue'    => $scopeValue,
            'scopeLabel'    => $this->myLearningMonitoringScopeLabel(),
            'isScopeEmpty'  => $isScopeEmpty,
            'summary'       => $this->buildLearningSummaryForScope(fn () => $this->myLearningMonitoringScopeQuery()),
            'teamAnalytics' => $teamAnalytics,
        ]);
    }

    /**
     * Data tabel user scope (role 3/4) - Yajra server-side.
     */
    public function monitoringUserDatatable(Request $request)
    {
        $user = Auth::user();
        abort_if($user->isUser() || $user->isAdmin(), 403);

        $query = $this->myLearningMonitoringScopeQuery()
            ->select([
                'users.id', 'users.name', 'users.email', 'users.id_emp',
                'users.division', 'users.department', 'users.team', 'users.ssohash',
            ])
            ->selectSub(
                ContentUser::selectRaw('COUNT(*)')
                    ->whereColumn('content_users.user_id', 'users.id')
                    ->where('finish', '1'),
                'finished_content_count'
            )
            ->selectSub(
                ContentUser::selectRaw('COALESCE(ROUND(AVG(progress)), 0)')
                    ->whereColumn('content_users.user_id', 'users.id'),
                'avg_progress'
            )
            ->selectSub(
                AssesmentResult::selectRaw('COUNT(*)')
                    ->whereColumn('assesment_results.user_id', 'users.id')
                    ->where('final', 1),
                'assessment_count'
            );

        return DataTables::eloquent($query)
            ->addIndexColumn()
            ->filter(function ($query) use ($request) {
                $search = trim((string) $request->input('search.value'));
                if ($search === '') {
                    return;
                }
                $query->where(function ($q) use ($search) {
                    $q->where('users.name', 'like', "%{$search}%")
                        ->orWhere('users.id_emp', 'like', "%{$search}%")
                        ->orWhere('users.email', 'like', "%{$search}%")
                        ->orWhere('users.division', 'like', "%{$search}%")
                        ->orWhere('users.department', 'like', "%{$search}%")
                        ->orWhere('users.team', 'like', "%{$search}%");
                });
            })
            ->addColumn('name_link', function (User $u) {
                $name = e($u->name ?: '-');
                $emp  = e($u->id_emp ?: '-');

                if (!empty($u->ssohash)) {
                    $url  = route('user.learning.monitoring.profile', $u->ssohash);
                    $name = '<a href="' . $url . '" class="fw-bold monitoring-link">' . $name . '</a>';
                } else {
                    $name = '<span class="fw-bold">' . $name . '</span>';
                }

                return $name . '<div class="text-muted small">' . $emp . '</div>';
            })
            ->editColumn('division', fn (User $u) => e($u->division ?: '-'))
            ->editColumn('department', fn (User $u) => e($u->department ?: '-'))
            ->editColumn('team', fn (User $u) => e($u->team ?: '-'))
            ->addColumn('progress_badge', fn (User $u) => $this->progressBarHtml((int) ($u->avg_progress ?? 0)))
            ->addColumn('action', function (User $u) {
                if (empty($u->ssohash)) {
                    return '<button class="btn btn-sm btn-outline-secondary" disabled>Detail</button>';
                }
                $url = route('user.learning.monitoring.profile', $u->ssohash);

                return '<a href="' . $url . '" class="btn btn-sm btn-outline-primary"><i class="ti ti-chart-line mr-1"></i>Detail</a>';
            })
            ->rawColumns(['name_link', 'progress_badge', 'action'])
            ->make(true);
    }

    /**
     * Halaman detail monitoring user.
     */
    public function monitoringProfile($hash)
    {
        $profile = $this->getMonitoringProfileOrFail($hash);
        abort_unless($this->canAccessLearningMonitoringProfile($profile), 403);

        return view('user.learning.monitoring-profile', [
            'profile'   => $profile,
            'summary'   => $this->buildLearningSummaryForUser($profile),
            'analytics' => app(\App\Services\LearningAnalyticsService::class)->personal((int) $profile->id),
        ]);
    }

    public function monitoringProfileLearningDatatable(Request $request, $hash)
    {
        $profile = $this->getMonitoringProfileOrFail($hash);
        abort_unless($this->canAccessLearningMonitoringProfile($profile), 403);

        $query = ContentUser::query()
            ->with(['content:id,title,type,slug,duration,classification_id', 'content.classification:id,classification_name,slug'])
            ->where('content_users.user_id', $profile->id);

        return DataTables::eloquent($query)
            ->addIndexColumn()
            ->filter(function ($query) use ($request) {
                $search = trim((string) $request->input('search.value'));
                if ($search === '') {
                    return;
                }
                $query->whereHas('content', fn ($q) => $q->where('title', 'like', "%{$search}%"));
            })
            ->addColumn('title', function (ContentUser $row) {
                if (!$row->content) {
                    return '<span class="text-danger">Content Deleted</span>';
                }

                return '<div class="fw-bold">' . e($row->content->title ?: '-') . '</div>'
                    . '<div class="text-muted small">' . e($row->content->type ?: '-') . '</div>';
            })
            ->addColumn('classification', function (ContentUser $row) {
                $name = optional(optional($row->content)->classification)->classification_name;

                return $name ? e($name) : '<span class="text-muted">-</span>';
            })
            ->addColumn('progress_badge', fn (ContentUser $row) => $this->progressBarHtml((int) ($row->progress ?? 0)))
            ->addColumn('finish_badge', fn (ContentUser $row) => (int) ($row->finish ?? 0) === 1
                ? '<span class="badge bg-green-lt text-green">Finished</span>'
                : '<span class="badge bg-yellow-lt text-yellow">Ongoing</span>')
            ->editColumn('updated_at', fn (ContentUser $row) => optional($row->updated_at)->format('d M Y H:i') ?: '-')
            ->rawColumns(['title', 'classification', 'progress_badge', 'finish_badge'])
            ->make(true);
    }

    public function monitoringProfileAssessmentDatatable(Request $request, $hash)
    {
        $profile = $this->getMonitoringProfileOrFail($hash);
        abort_unless($this->canAccessLearningMonitoringProfile($profile), 403);

        $query = AssesmentResult::query()
            ->with(['assesment:id,title,slug'])
            ->where('assesment_results.user_id', $profile->id);

        return DataTables::eloquent($query)
            ->addIndexColumn()
            ->filter(function ($query) use ($request) {
                $search = trim((string) $request->input('search.value'));
                if ($search === '') {
                    return;
                }
                $query->whereHas('assesment', fn ($q) => $q->where('title', 'like', "%{$search}%"));
            })
            ->addColumn('title', function (AssesmentResult $row) {
                $title = optional($row->assesment)->title;

                return $title ? e($title) : '<span class="text-muted">-</span>';
            })
            ->addColumn('score_badge', fn (AssesmentResult $row) => '<span class="badge bg-blue-lt text-blue">' . (int) ($row->score ?? 0) . '</span>')
            ->addColumn('status_badge', fn (AssesmentResult $row) => (int) ($row->pass ?? 0) === 1
                ? '<span class="badge bg-green-lt text-green">Pass</span>'
                : '<span class="badge bg-red-lt text-red">Not Pass</span>')
            ->addColumn('attempt', fn (AssesmentResult $row) => '<span class="text-muted">#' . (int) ($row->batch ?? 1) . '</span>')
            ->editColumn('created_at', fn (AssesmentResult $row) => optional($row->created_at)->format('d M Y H:i') ?: '-')
            ->rawColumns(['title', 'score_badge', 'status_badge', 'attempt'])
            ->make(true);
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
    /**
     * Playlist (Training Plan) yang sedang dikerjakan user — punya progress tapi belum 100%.
     */
    private function getOngoingPlaylists(int $userId)
    {
        return $this->getUserPlaylists($userId)->reject->is_completed->values();
    }

    /**
     * Playlist (Training Plan) yang telah diselesaikan 100% oleh user (Learning Record).
     */
    private function getCompletedPlaylists(int $userId)
    {
        return $this->getUserPlaylists($userId)->filter->is_completed->values();
    }

    /**
     * Content selesai untuk Learning Record.
     *
     * Content yang menjadi bagian Playlist TIDAK ditampilkan sebagai baris —
     * sudah diwakili oleh baris Playlist (bila playlist 100% selesai), sehingga
     * tidak ada duplikasi data. Content tanpa Playlist tetap tampil.
     */
    private function getCompletedContents(int $userId)
    {
        return ContentUser::with([
                'content.classification',
                'assesment' => fn ($q) => $q->with([
                    'scores' => fn ($q) => $q->where('user_id', $userId),
                ]),
            ])
            ->where('user_id', $userId)
            ->where('finish', '1')
            ->whereNotExists(function ($sub) {
                $sub->from('trainingplandetail')
                    ->whereColumn('trainingplandetail.content_id', 'content_users.content_id');
            })
            ->latest()
            ->get();
    }

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

    public function monitoringProfilePlaylistDatatable(Request $request, $hash)
    {
        $profile = $this->getMonitoringProfileOrFail($hash);
        abort_unless($this->canAccessLearningMonitoringProfile($profile), 403);

        $contentIds = ContentUser::where('user_id', $profile->id)->pluck('content_id');

        if ($contentIds->isEmpty()) {
            return DataTables::eloquent(TrainingPlan::query()->whereRaw('1 = 0'))->make(true);
        }

        $query = TrainingPlan::query()
            ->select('trainingplan.*')
            ->whereHas('trainingplandetail', fn ($q) => $q->whereIn('content_id', $contentIds))
            ->selectSub(
                TrainingPlanDetail::selectRaw('COUNT(*)')
                    ->whereColumn('trainingplandetail.trainingplan_id', 'trainingplan.id'),
                'total_items'
            )
            ->selectSub(
                ContentUser::selectRaw('COUNT(*)')
                    ->join('trainingplandetail', 'trainingplandetail.content_id', '=', 'content_users.content_id')
                    ->whereColumn('trainingplandetail.trainingplan_id', 'trainingplan.id')
                    ->where('content_users.user_id', $profile->id)
                    ->where('content_users.finish', '1'),
                'finished_items'
            )
            ->selectSub(
                ContentUser::selectRaw('COALESCE(ROUND(AVG(content_users.progress)), 0)')
                    ->join('trainingplandetail', 'trainingplandetail.content_id', '=', 'content_users.content_id')
                    ->whereColumn('trainingplandetail.trainingplan_id', 'trainingplan.id')
                    ->where('content_users.user_id', $profile->id),
                'avg_progress'
            );

        return DataTables::eloquent($query)
            ->addIndexColumn()
            ->filter(function ($query) use ($request) {
                $search = trim((string) $request->input('search.value'));
                if ($search === '') {
                    return;
                }
                $query->where('trainingplan.title', 'like', "%{$search}%");
            })
            ->addColumn('title', fn (TrainingPlan $row) => e($row->title ?: '-'))
            ->addColumn('items', fn (TrainingPlan $row) => '<span class="badge bg-azure-lt text-azure">'
                . (int) ($row->finished_items ?? 0) . ' / ' . (int) ($row->total_items ?? 0) . '</span>')
            ->addColumn('progress_badge', fn (TrainingPlan $row) => $this->progressBarHtml((int) ($row->avg_progress ?? 0)))
            ->addColumn('status_badge', function (TrainingPlan $row) {
                $finished = (int) ($row->finished_items ?? 0);
                $total    = (int) ($row->total_items ?? 0);

                return ($total > 0 && $finished >= $total)
                    ? '<span class="badge bg-green-lt text-green">Completed</span>'
                    : '<span class="badge bg-yellow-lt text-yellow">In Progress</span>';
            })
            ->rawColumns(['title', 'items', 'progress_badge', 'status_badge'])
            ->make(true);
    }

    // ─── Monitoring helpers ──────────────────────────────────────────────────────

    /**
     * Scope organisasi (type, value) untuk role 4. Prioritas team -> division -> department.
     *
     * @return array{0: ?string, 1: ?string}
     */
    private function myLearningMonitoringScope(): array
    {
        $u = Auth::user();

        if (!empty($u->team)) {
            return ['team', $u->team];
        }
        if (!empty($u->division)) {
            return ['division', $u->division];
        }
        if (!empty($u->department)) {
            return ['department', $u->department];
        }

        return [null, null];
    }

    private function myLearningMonitoringScopeLabel(): string
    {
        $u = Auth::user();

        if ($u->isSuperAdmin()) {
            return 'All User';
        }

        [$type, $value] = $this->myLearningMonitoringScope();

        return $type === null ? 'Scope belum diset' : ucfirst($type) . ': ' . $value;
    }

    /**
     * Query user sesuai scope My Learning monitoring.
     */
    private function myLearningMonitoringScopeQuery()
    {
        $u = Auth::user();

        if ($u->isUser() || $u->isAdmin()) {
            return User::query()->where('users.id', $u->id);
        }

        if ($u->isSuperAdmin()) {
            return User::query();
        }

        // Role 4 (subadmin / team leader).
        [$type, $value] = $this->myLearningMonitoringScope();

        return $type === null
            ? User::query()->whereRaw('1 = 0')
            : User::query()->where('users.' . $type, $value);
    }

    private function canAccessLearningMonitoringProfile(User $profile): bool
    {
        $u = Auth::user();

        if ($u->isUser() || $u->isAdmin()) {
            return $profile->id === $u->id;
        }

        if ($u->isSuperAdmin()) {
            return true;
        }

        return $this->myLearningMonitoringScopeQuery()
            ->where('users.id', $profile->id)
            ->exists();
    }

    private function getMonitoringProfileOrFail($hash): User
    {
        abort_if(empty($hash), 404);

        $user = User::firstWhere('ssohash', $hash);

        abort_if(!$user, 404);

        return $user;
    }

    private function buildLearningSummaryForUser(User $user): array
    {
        $userId = $user->id;

        $totalLearning    = ContentUser::where('user_id', $userId)->count();
        $finishedLearning = ContentUser::where('user_id', $userId)->where('finish', '1')->count();
        $averageProgress  = (int) round(ContentUser::where('user_id', $userId)->avg('progress') ?? 0);

        $assessmentCompleted   = AssesmentResult::where('user_id', $userId)->where('final', 1)->count();
        $certificateGenerated  = Certificate::where('user_id', $userId)->count();

        $content = ContentUser::with(['content:id,title,duration,classification_id', 'content.classification:id,classification_name'])
            ->where('user_id', $userId)
            ->get()
            ->filter(fn ($item) => !empty($item->content));

        $lastLearning = optional($content->sortByDesc('updated_at')->first()?->content)->title ?? '-';

        $favoriteCategory = $content
            ->groupBy(fn ($item) => $item->content->classification->classification_name ?? '-')
            ->sortByDesc(fn ($items) => $items->count())
            ->keys()
            ->first() ?? '-';

        return [
            'total_learning'        => $totalLearning,
            'finished_learning'     => $finishedLearning,
            'average_progress'      => $averageProgress,
            'assessment_completed'  => $assessmentCompleted,
            'certificate_generated' => $certificateGenerated,
            'last_learning'         => $lastLearning,
            'favorite_category'     => $favoriteCategory,
            'learning_hours'        => $this->getUserLearningHours($userId),
        ];
    }

    /**
     * Agregat ringan dalam cakupan scope (role 3/4).
     *
     * @param callable():\Illuminate\Database\Eloquent\Builder $factory
     */
    private function buildLearningSummaryForScope(callable $factory): array
    {
        $totalUsers = $factory()->count();

        $averageProgress = (int) round(
            ContentUser::whereIn('content_users.user_id', $factory()->select('users.id'))
                ->avg('content_users.progress') ?? 0
        );

        $finishedContent = ContentUser::whereIn('content_users.user_id', $factory()->select('users.id'))
            ->where('content_users.finish', '1')
            ->count();

        $assessmentCompleted = AssesmentResult::whereIn('assesment_results.user_id', $factory()->select('users.id'))
            ->where('final', 1)
            ->count();

        $certificateGenerated = Certificate::whereIn('certificates.user_id', $factory()->select('users.id'))->count();

        $activeUsers = ContentUser::whereIn('content_users.user_id', $factory()->select('users.id'))
            ->distinct('content_users.user_id')
            ->count('content_users.user_id');

        return [
            'total_users'           => $totalUsers,
            'average_progress'      => $averageProgress,
            'finished_content'      => $finishedContent,
            'assessment_completed'  => $assessmentCompleted,
            'certificate_generated' => $certificateGenerated,
            'active_users'          => $activeUsers,
        ];
    }

    private function progressBarHtml(int $progress): string
    {
        $progress = max(0, min(100, $progress));

        return '<div class="d-flex align-items-center">'
            . '<div class="progress progress-sm flex-fill" style="min-width:70px;">'
            . '<div class="progress-bar" style="width: ' . $progress . '%"></div>'
            . '</div>'
            . '<span class="ms-2 small text-muted">' . $progress . '%</span>'
            . '</div>';
    }
}
