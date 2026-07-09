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

        return view('user.learning.dashboard', compact(
            'user',
            'learningHours',
            'totalLearning',
            'completedLearning',
            'ongoingLearning',
            'passedAssessment'
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
                $ongoing_content_user = $this->getOngoingLearningContents($userId);
                $ongoingLearning      = ContentUser::where('user_id', $userId)->where('finish', '0')->count();
                $averageProgress      = (int) round(ContentUser::where('user_id', $userId)->avg('progress') ?? 0);

                return view('user.learning.dashboard-partials.ongoing', compact(
                    'ongoing_content_user',
                    'ongoingLearning',
                    'averageProgress'
                ));

            case 'record':
                $content_user = ContentUser::with([
                        'content.classification',
                        'assesment' => fn ($q) => $q->with([
                            'scores' => fn ($q) => $q->where('user_id', $userId),
                        ]),
                    ])
                    ->where('user_id', $userId)
                    ->where('finish', '1')
                    ->latest()
                    ->get();

                $averageProgress = (int) round(
                    ContentUser::where('user_id', $userId)->where('finish', '1')->avg('progress') ?? 0
                );

                $certificateSettings = CertificateSetting::select([
                        'id', 'certificate_type', 'content_id', 'is_active',
                        'minimum_progress', 'minimum_score',
                    ])
                    ->where('is_active', 1)
                    ->get();

                return view('user.learning.dashboard-partials.record', compact(
                    'content_user',
                    'averageProgress',
                    'certificateSettings'
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

    private function getOngoingLearningContents($userId)
    {
        $search       = request('ongoing_search');
        $status       = request('ongoing_status', 'all');
        $year         = request('ongoing_year', 'all');
        $activitySort = request('ongoing_activity_sort', 'newest_activity');
        $progressSort = request('ongoing_progress_sort', 'all');

        $query = ContentUser::with([
                'content.classification',
                'assesment' => fn ($q) => $q->with([
                    'scores' => fn ($q) => $q->where('user_id', $userId),
                ]),
            ])
            ->where('user_id', $userId);

        if ($progressSort === 'completed') {
            $query->where('finish', '1');
        } else {
            $query->where('finish', '0');
        }

        $query->when($search, function ($q) use ($search) {
            $q->whereHas('content', function ($q) use ($search) {
                $q->where('title', 'like', '%' . $search . '%')
                    ->orWhereHas('classification', function ($q) use ($search) {
                        $q->where('classification_name', 'like', '%' . $search . '%');
                    });
            });
        });

        $query->when($status !== 'all', fn ($q) => $q->where('status', $status));
        $query->when($year !== 'all', fn ($q) => $q->whereYear('created_at', $year));
        $query->when($progressSort === 'not_started', fn ($q) => $q->where('progress', 0));
        $query->when($progressSort === 'in_progress', fn ($q) => $q->whereBetween('progress', [1, 99]));
        $query->when($progressSort === 'almost_done', fn ($q) => $q->where('progress', '>=', 80)->where('progress', '<', 100));

        if ($progressSort === 'progress_highest') {
            $query->orderByDesc('progress');
        } elseif ($progressSort === 'progress_lowest') {
            $query->orderBy('progress');
        } elseif ($activitySort === 'oldest_activity') {
            $query->orderBy('updated_at');
        } else {
            $query->orderByDesc('updated_at');
        }

        return $query
            ->paginate(9, ['*'], 'ongoing_page')
            ->withQueryString()
            ->withPath(route('user.learning.dashboard'));
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
