<?php

namespace App\Http\Controllers;

use App\Models\AssesmentResult;
use App\Models\Certificate;
use App\Models\ContentUser;
use App\Models\TrainingPlan;
use App\Models\TrainingPlanDetail;
use App\Models\User;
use App\Services\LearningAnalyticsService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Yajra\DataTables\Facades\DataTables;

/**
 * My Learning Monitoring — personal insight (role 1/2) dan organization/team
 * monitoring (role 3/4).
 *
 * Tabel monitoring tetap memakai Yajra server-side (jumlah user organisasi bisa
 * besar). Dipanggil dari LearningController::dashboardPartial (routing only)
 * untuk tab, plus endpoint datatable & halaman profil sendiri.
 */
class LearningMonitoringController extends Controller
{
    /**
     * HTML fragment tab "monitoring" pada My Learning dashboard, sesuai role.
     * Role 1/2 -> personal insight. Role 3/4 -> organization/team monitoring.
     */
    public function partial()
    {
        $user   = Auth::user();
        $userId = Auth::id();

        $analyticsService = app(LearningAnalyticsService::class);

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
            'analytics' => app(LearningAnalyticsService::class)->personal((int) $profile->id),
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
            'learning_hours'        => app(LearningAnalyticsService::class)->userLearningHours((int) $userId),
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
