<?php

namespace App\Http\Controllers;

use App\Models\AssesmentResult;
use App\Models\ContentUser;
use App\Models\TrainingPlan;
use App\Models\TrainingPlanDetail;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Yajra\DataTables\Facades\DataTables;

class MonitoringController extends Controller
{
    /**
     * Monitoring progress belajar user berbasis role & scope organisasi.
     *
     * - Admin (2) & Super Admin (3) : semua user.
     * - Subadmin / Team Leader (4)  : hanya scope organisasi miliknya,
     *   dengan prioritas team -> division -> department -> kosong.
     */

    /** Tipe group yang diperbolehkan. */
    private const GROUP_TYPES = ['division', 'department', 'team'];

    /*
    |--------------------------------------------------------------------------
    | Halaman & DataTable utama
    |--------------------------------------------------------------------------
    */

    public function index(Request $request)
    {
        $isScopeEmpty = $this->scopeIsEmpty();

        // Opsi dropdown filter diambil dari scope (distinct), bukan seluruh tabel.
        $divisions  = collect();
        $departments = collect();
        $teams      = collect();

        if (!$isScopeEmpty) {
            $divisions   = $this->distinctScopeValues('division');
            $departments = $this->distinctScopeValues('department');
            $teams       = $this->distinctScopeValues('team');
        }

        return view('main.monitoring.index', [
            'scopeLabel'   => $this->getScopeLabel(),
            'isScopeEmpty' => $isScopeEmpty,
            'isRole4'      => Auth::user()->isSubadminDivision(),
            'divisions'    => $divisions,
            'departments'  => $departments,
            'teams'        => $teams,
            'roles'        => User::roles(),
            'stats'        => $this->statsForUserQuery(fn () => $this->baseMonitoringUserQuery()),
        ]);
    }

    public function datatable(Request $request)
    {
        $query = $this->baseMonitoringUserQuery();
        $this->applyColumnFilters($query, $request);

        return $this->buildUserDataTable($query, $request);
    }

    /*
    |--------------------------------------------------------------------------
    | Halaman Group (per Division / Department / Team)
    |--------------------------------------------------------------------------
    */

    public function group($type, $value)
    {
        $value = $this->decodeGroupValue($value);
        $this->authorizeGroupAccess($type, $value);

        return view('main.monitoring.group', [
            'type'       => $type,
            'value'      => $value,
            'typeLabel'  => ucfirst($type),
            'stats'      => $this->statsForUserQuery(
                fn () => $this->baseMonitoringUserQuery()->where('users.' . $type, $value)
            ),
        ]);
    }

    public function groupDatatable($type, $value, Request $request)
    {
        $value = $this->decodeGroupValue($value);
        $this->authorizeGroupAccess($type, $value);

        $query = $this->baseMonitoringUserQuery()->where('users.' . $type, $value);

        return $this->buildUserDataTable($query, $request);
    }

    /*
    |--------------------------------------------------------------------------
    | Profile monitoring
    |--------------------------------------------------------------------------
    */

    public function profile($hash)
    {
        $profile = $this->getProfileUserOrFail($hash);
        $this->authorizeMonitoringUser($profile);

        return view('main.monitoring.profile', [
            'profile' => $profile,
            'summary' => $this->profileSummary($profile),
        ]);
    }

    public function learningDatatable(Request $request, $hash)
    {
        $profile = $this->getProfileUserOrFail($hash);
        $this->authorizeMonitoringUser($profile);

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
                $query->whereHas('content', function ($q) use ($search) {
                    $q->where('title', 'like', "%{$search}%");
                });
            })
            ->addColumn('title', function (ContentUser $row) {
                $content = $row->content;
                if (!$content) {
                    return '<span class="text-danger">Content Deleted</span>';
                }

                return '<div class="fw-semibold">' . e($content->title ?: '-') . '</div>'
                    . '<div class="text-muted small">' . e($content->type ?: '-') . '</div>';
            })
            ->addColumn('classification', function (ContentUser $row) {
                $name = optional(optional($row->content)->classification)->classification_name;

                return $name ? e($name) : '<span class="text-muted">-</span>';
            })
            ->addColumn('progress_badge', fn (ContentUser $row) => $this->progressBar((int) ($row->progress ?? 0), 'bg-primary'))
            ->addColumn('finish_badge', function (ContentUser $row) {
                return (int) ($row->finish ?? 0) === 1
                    ? '<span class="badge bg-green-lt text-green">Finished</span>'
                    : '<span class="badge bg-yellow-lt text-yellow">Ongoing</span>';
            })
            ->editColumn('updated_at', fn (ContentUser $row) => optional($row->updated_at)->format('d M Y H:i') ?: '-')
            ->rawColumns(['title', 'classification', 'progress_badge', 'finish_badge'])
            ->make(true);
    }

    public function assessmentDatatable(Request $request, $hash)
    {
        $profile = $this->getProfileUserOrFail($hash);
        $this->authorizeMonitoringUser($profile);

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
                $query->whereHas('assesment', function ($q) use ($search) {
                    $q->where('title', 'like', "%{$search}%");
                });
            })
            ->addColumn('title', function (AssesmentResult $row) {
                $title = optional($row->assesment)->title;

                return $title ? e($title) : '<span class="text-muted">-</span>';
            })
            ->addColumn('score_badge', fn (AssesmentResult $row) => '<span class="badge bg-blue-lt text-blue">' . (int) ($row->score ?? 0) . '</span>')
            ->addColumn('status_badge', function (AssesmentResult $row) {
                return (int) ($row->pass ?? 0) === 1
                    ? '<span class="badge bg-green-lt text-green">Pass</span>'
                    : '<span class="badge bg-red-lt text-red">Not Pass</span>';
            })
            ->addColumn('attempt', fn (AssesmentResult $row) => '<span class="text-muted">#' . (int) ($row->batch ?? 1) . '</span>')
            ->editColumn('created_at', fn (AssesmentResult $row) => optional($row->created_at)->format('d M Y H:i') ?: '-')
            ->rawColumns(['title', 'score_badge', 'status_badge', 'attempt'])
            ->make(true);
    }

    public function playlistDatatable(Request $request, $hash)
    {
        $profile = $this->getProfileUserOrFail($hash);
        $this->authorizeMonitoringUser($profile);

        $userId = $profile->id;
        $contentIds = ContentUser::where('user_id', $userId)->pluck('content_id');

        if ($contentIds->isEmpty()) {
            return DataTables::eloquent(TrainingPlan::query()->whereRaw('1 = 0'))->make(true);
        }

        $query = TrainingPlan::query()
            ->select('trainingplan.*')
            ->whereHas('trainingplandetail', function ($q) use ($contentIds) {
                $q->whereIn('content_id', $contentIds);
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
            ->addColumn('items', function (TrainingPlan $row) {
                $finished = (int) ($row->finished_items ?? 0);
                $total    = (int) ($row->total_items ?? 0);

                return '<span class="badge bg-azure-lt text-azure">' . $finished . ' / ' . $total . '</span>';
            })
            ->addColumn('progress_badge', fn (TrainingPlan $row) => $this->progressBar((int) ($row->avg_progress ?? 0), 'bg-purple'))
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

    /*
    |--------------------------------------------------------------------------
    | Helpers - scope & akses
    |--------------------------------------------------------------------------
    */

    /**
     * Scope organisasi role 4 dengan prioritas team -> division -> department.
     *
     * @return array{0: ?string, 1: ?string} [type, value] atau [null, null] bila kosong.
     */
    private function monitoringScope(): array
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

    /**
     * Query dasar user sesuai scope role.
     * Role 2/3 melihat semua user. Role 4 dibatasi scope organisasinya.
     */
    private function baseMonitoringUserQuery()
    {
        $query = User::query();

        if (!Auth::user()->isSubadminDivision()) {
            return $query;
        }

        [$type, $value] = $this->monitoringScope();

        // Scope belum diset -> tidak ada data yang boleh tampil.
        if ($type === null) {
            return $query->whereRaw('1 = 0');
        }

        return $query->where('users.' . $type, $value);
    }

    private function getScopeLabel(): string
    {
        if (!Auth::user()->isSubadminDivision()) {
            return 'Semua User';
        }

        [$type, $value] = $this->monitoringScope();

        return $type === null
            ? 'Scope belum diset'
            : ucfirst($type) . ': ' . $value;
    }

    private function scopeIsEmpty(): bool
    {
        if (!Auth::user()->isSubadminDivision()) {
            return false;
        }

        [$type] = $this->monitoringScope();

        return $type === null;
    }

    /**
     * Filter per kolom independen (AND bila lebih dari satu dipilih).
     */
    private function applyColumnFilters($query, Request $request): void
    {
        foreach (self::GROUP_TYPES as $column) {
            $value = trim((string) $request->input($column, ''));
            if ($value !== '') {
                $query->where('users.' . $column, $value);
            }
        }

        // Filter Role (independen, AND dengan filter lain bila dipilih).
        $role = trim((string) $request->input('role', ''));
        if ($role !== '') {
            $query->where('users.role', (int) $role);
        }
    }

    /**
     * Validasi akses halaman group. Role 4 hanya boleh membuka group
     * yang masih beririsan dengan scope organisasinya.
     */
    private function authorizeGroupAccess(string $type, string $value): void
    {
        abort_unless(in_array($type, self::GROUP_TYPES, true), 404);

        if (!Auth::user()->isSubadminDivision()) {
            return;
        }

        $exists = $this->baseMonitoringUserQuery()
            ->where('users.' . $type, $value)
            ->exists();

        abort_unless($exists, 403, 'Group berada di luar scope organisasi Anda.');
    }

    private function getProfileUserOrFail($hash): User
    {
        abort_if(empty($hash), 404);

        $user = User::firstWhere('ssohash', $hash);

        abort_if(!$user, 404);

        return $user;
    }

    /**
     * Profile harus berada dalam scope user yang sedang login.
     */
    private function authorizeMonitoringUser(User $profile): void
    {
        if (!Auth::user()->isSubadminDivision()) {
            return;
        }

        $inScope = $this->baseMonitoringUserQuery()
            ->where('users.id', $profile->id)
            ->exists();

        abort_unless($inScope, 403, 'User berada di luar scope organisasi Anda.');
    }

    /**
     * Route param sudah otomatis di-decode oleh framework (Laravel route()
     * meng-encode value, Symfony men-decode path). Trim sebagai pengaman saja;
     * tidak memanggil urldecode lagi agar value yang valid tidak terkorupsi.
     */
    private function decodeGroupValue($value): string
    {
        return trim((string) $value);
    }

    private function distinctScopeValues(string $column)
    {
        return $this->baseMonitoringUserQuery()
            ->whereNotNull('users.' . $column)
            ->where('users.' . $column, '!=', '')
            ->distinct()
            ->orderBy('users.' . $column)
            ->pluck('users.' . $column);
    }

    /*
    |--------------------------------------------------------------------------
    | Helpers - statistik & rendering
    |--------------------------------------------------------------------------
    */

    /**
     * Agregat ringan untuk satu user-query (scope/group).
     * Memakai subquery IN sehingga otomatis ikut semua constraint scope.
     *
     * @param callable():\Illuminate\Database\Eloquent\Builder $factory
     */
    private function statsForUserQuery(callable $factory): array
    {
        $totalUser = $factory()->count();

        $avgProgress = (int) round(
            ContentUser::whereIn('content_users.user_id', $factory()->select('users.id'))
                ->avg('content_users.progress') ?? 0
        );

        $finishedContent = ContentUser::whereIn('content_users.user_id', $factory()->select('users.id'))
            ->where('content_users.finish', '1')
            ->count();

        $assessmentCount = AssesmentResult::whereIn('assesment_results.user_id', $factory()->select('users.id'))
            ->count();

        return [
            'total_user'       => $totalUser,
            'avg_progress'     => $avgProgress,
            'finished_content' => $finishedContent,
            'assessment_count' => $assessmentCount,
        ];
    }

    /**
     * Builder Yajra DataTable untuk daftar user (dipakai index & group).
     */
    private function buildUserDataTable($query, Request $request)
    {
        $query = $query->select([
            'users.id',
            'users.name',
            'users.email',
            'users.id_emp',
            'users.division',
            'users.department',
            'users.team',
            'users.ssohash',
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
                AssesmentResult::select('created_at')
                    ->whereColumn('assesment_results.user_id', 'users.id')
                    ->latest()
                    ->limit(1),
                'last_assessment_at'
            )
            ->selectSub(
                AssesmentResult::select('score')
                    ->whereColumn('assesment_results.user_id', 'users.id')
                    ->latest()
                    ->limit(1),
                'last_assessment_score'
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
                        ->orWhere('users.email', 'like', "%{$search}%")
                        ->orWhere('users.id_emp', 'like', "%{$search}%")
                        ->orWhere('users.division', 'like', "%{$search}%")
                        ->orWhere('users.department', 'like', "%{$search}%")
                        ->orWhere('users.team', 'like', "%{$search}%");
                });
            })
            ->addColumn('name_link', function (User $user) {
                $name = e($user->name ?: '-');
                $emp  = e($user->id_emp ?: '-');

                if (!empty($user->ssohash)) {
                    $url  = route('monitoring.profile', $user->ssohash);
                    $name = '<a href="' . $url . '" class="fw-bold monitoring-link">' . $name . '</a>';
                } else {
                    $name = '<span class="fw-bold">' . $name . '</span>';
                }

                return $name . '<div class="text-muted small">' . $emp . '</div>';
            })
            ->addColumn('division', fn (User $user) => $this->groupLink('division', $user->division))
            ->addColumn('department', fn (User $user) => $this->groupLink('department', $user->department))
            ->addColumn('team', fn (User $user) => $this->groupLink('team', $user->team))
            ->addColumn('progress_badge', fn (User $user) => $this->progressBar((int) ($user->avg_progress ?? 0)))
            ->addColumn('last_assessment', function (User $user) {
                if (empty($user->last_assessment_at)) {
                    return '<span class="text-muted small">Belum ada</span>';
                }

                $date  = \Carbon\Carbon::parse($user->last_assessment_at)->format('d M Y');
                $score = (int) ($user->last_assessment_score ?? 0);

                return '<div>' . e($date) . '</div>'
                    . '<div class="text-muted small">Skor: ' . $score . '</div>';
            })
            ->addColumn('action', function (User $user) {
                if (empty($user->ssohash)) {
                    return '<button class="btn btn-sm btn-outline-secondary" disabled>'
                        . '<i class="ti ti-eye-off"></i> Profile</button>';
                }

                $url = route('monitoring.profile', $user->ssohash);

                return '<a href="' . $url . '" class="btn btn-sm btn-outline-primary">'
                    . '<i class="ti ti-user-search"></i> Profile</a>';
            })
            ->rawColumns(['name_link', 'division', 'department', 'team', 'progress_badge', 'last_assessment', 'action'])
            ->make(true);
    }

    /**
     * Link clickable ke halaman group (buka tab baru).
     */
    private function groupLink(string $type, ?string $value): string
    {
        if (empty($value)) {
            return '<span class="text-muted">-</span>';
        }

        // route() meng-encode value secara otomatis (aman untuk spasi & '&').
        $url = route('monitoring.group', ['type' => $type, 'value' => $value]);

        return '<a href="' . $url . '" target="_blank" rel="noopener"'
            . ' class="text-reset text-decoration-none monitoring-link">'
            . e($value) . '</a>';
    }

    private function progressBar(int $progress, string $barClass = 'bg-primary'): string
    {
        $progress = max(0, min(100, $progress));

        return '<div class="d-flex align-items-center">'
            . '<div class="progress progress-sm flex-fill" style="min-width:80px;">'
            . '<div class="progress-bar ' . $barClass . '" style="width: ' . $progress . '%"></div>'
            . '</div>'
            . '<span class="ms-2 small text-muted">' . $progress . '%</span>'
            . '</div>';
    }

    private function profileSummary(User $profile): array
    {
        $userId = $profile->id;

        $totalContent    = ContentUser::where('user_id', $userId)->count();
        $finishedContent = ContentUser::where('user_id', $userId)->where('finish', '1')->count();
        $avgProgress     = (int) round(ContentUser::where('user_id', $userId)->avg('progress') ?? 0);

        $totalAssessment = AssesmentResult::where('user_id', $userId)->count();
        $avgAssessment   = (int) round(AssesmentResult::where('user_id', $userId)->avg('score') ?? 0);

        // Riwayat content user (bounded ke 1 user) untuk last learning, kategori favorit, jam belajar.
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

        $learningHours = round($content->sum(function ($item) {
            $parts   = explode(':', $item->content->duration ?? '00:00');
            $minutes = (int) ($parts[0] ?? 0);

            return $minutes / 60;
        }), 1);

        return [
            'total_content'        => $totalContent,
            'finished_content'     => $finishedContent,
            'avg_progress'         => $avgProgress,
            'total_assessment'     => $totalAssessment,
            'avg_assessment_score' => $avgAssessment,
            'last_learning'        => $lastLearning,
            'favorite_category'    => $favoriteCategory,
            'learning_hours'       => $learningHours,
        ];
    }
}
