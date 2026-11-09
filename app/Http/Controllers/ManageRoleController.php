<?php

namespace App\Http\Controllers;

use App\Models\AssesmentResult;
use App\Models\ContentUser;
use App\Models\OfflineTrainingRegister;
use App\Models\User;
use App\Services\EmployeeSync\EmployeeUserSyncService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Throwable;
use Yajra\DataTables\Facades\DataTables;

class ManageRoleController extends Controller
{
    /**
     * Role yang boleh di-set lewat Manage Role.
     */
    private const ASSIGNABLE_ROLES = [
        User::ROLE_USER,               // 1 - User
        User::ROLE_SUBADMIN_DIVISION,  // 4 - Subadmin Division / Sub Admin
        User::ROLE_ADMIN,              // 2 - Administrator / Admin
        User::ROLE_SUPER_ADMIN,        // 3 - Super Admin
        User::ROLE_APPROVER,           // 5 - Approver
    ];

    /**
     * Daftar user untuk pengelolaan role (Super Admin only).
     */
    public function index(Request $request)
    {
        $search     = $request->input('q') ?? ($request->input('s') ?? $request->input('search'));
        $division   = $request->input('division');
        $department = $request->input('department');
        $team       = $request->input('team');
        $orgUnit    = $request->input('org_unit');
        $entries    = (int) ($request->input('entries', 15));
        if (!in_array($entries, [10, 15, 25, 50, 100])) {
            $entries = 15;
        }

        $users = User::query()
            ->when($search, function ($query) use ($search) {
                $query->where(function ($q) use ($search) {
                    $q->where('name', 'like', "%{$search}%")
                        ->orWhere('email', 'like', "%{$search}%")
                        ->orWhere('id_emp', 'like', "%{$search}%")
                        ->orWhere('division', 'like', "%{$search}%")
                        ->orWhere('department', 'like', "%{$search}%")
                        ->orWhere('team', 'like', "%{$search}%")
                        ->orWhere('id_org_unit', 'like', "%{$search}%");
                });
            })
            ->when($division, function ($query) use ($division) {
                $query->where('division', $division);
            })
            ->when($department, function ($query) use ($department) {
                $query->where('department', $department);
            })
            ->when($team, function ($query) use ($team) {
                $query->where('team', $team);
            })
            ->when($orgUnit, function ($query) use ($orgUnit) {
                $query->where('id_org_unit', $orgUnit);
            })
            ->orderBy('name')
            ->paginate($entries)
            ->withQueryString();

        $divisions = User::query()
            ->whereNotNull('division')
            ->whereRaw("TRIM(division) != ''")
            ->selectRaw('TRIM(division) as division')
            ->distinct()
            ->orderBy('division')
            ->pluck('division');

        $departments = User::query()
            ->whereNotNull('department')
            ->whereRaw("TRIM(department) != ''")
            ->selectRaw('TRIM(department) as department')
            ->distinct()
            ->orderBy('department')
            ->pluck('department');

        $teams = User::query()
            ->whereNotNull('team')
            ->whereRaw("TRIM(team) != ''")
            ->selectRaw('TRIM(team) as team')
            ->distinct()
            ->orderBy('team')
            ->pluck('team');

        $orgUnits = User::query()
            ->whereNotNull('id_org_unit')
            ->whereRaw("TRIM(id_org_unit) != ''")
            ->selectRaw('TRIM(id_org_unit) as id_org_unit')
            ->distinct()
            ->orderBy('id_org_unit')
            ->pluck('id_org_unit');

        return view('main.manage-role.index', [
            'users'           => $users,
            'search'          => $search,
            'division'        => $division,
            'department'      => $department,
            'team'            => $team,
            'orgUnit'         => $orgUnit,
            'entries'         => $entries,
            'divisions'       => $divisions,
            'departments'     => $departments,
            'teams'           => $teams,
            'orgUnits'        => $orgUnits,
            'assignableRoles' => $this->assignableRoleLabels(),

            'totalUsers'      => User::count(),
            'totalResigned'   => User::onlyTrashed()->count(),
            'totalDivision'   => $divisions->count(),
            'totalDepartment' => $departments->count(),
            'totalTeam'       => $teams->count(),
        ]);
    }

    /**
     * AJAX autocomplete live search for Manage Role.
     */
    /**
     * Tampilkan halaman Resign Management berdasarkan Soft-Deleted users (deleted_at IS NOT NULL).
     */
    public function resign(Request $request)
    {
        $totalResigned = User::onlyTrashed()->count();
        $totalUsers    = User::count();

        // Distinct companies, divisions, and departments for dropdown filters
        $companies = User::withTrashed()
            ->whereNotNull('company_name')
            ->whereRaw("TRIM(company_name) != ''")
            ->selectRaw('TRIM(company_name) as company_name')
            ->distinct()
            ->orderBy('company_name')
            ->pluck('company_name');

        $divisions = User::withTrashed()
            ->whereNotNull('division')
            ->whereRaw("TRIM(division) != ''")
            ->selectRaw('TRIM(division) as division')
            ->distinct()
            ->orderBy('division')
            ->pluck('division');

        $departments = User::withTrashed()
            ->whereNotNull('department')
            ->whereRaw("TRIM(department) != ''")
            ->selectRaw('TRIM(department) as department')
            ->distinct()
            ->orderBy('department')
            ->pluck('department');

        $totalCompanies = User::onlyTrashed()
            ->whereNotNull('company_name')
            ->whereRaw("TRIM(company_name) != ''")
            ->distinct()
            ->count('company_name');

        $totalDivisions = User::onlyTrashed()
            ->whereNotNull('division')
            ->whereRaw("TRIM(division) != ''")
            ->distinct()
            ->count('division');

        $totalDepartments = User::onlyTrashed()
            ->whereNotNull('department')
            ->whereRaw("TRIM(department) != ''")
            ->distinct()
            ->count('department');

        return view('main.manage-role.resign', [
            'totalResigned'    => $totalResigned,
            'totalUsers'       => $totalUsers,
            'totalCompanies'   => $totalCompanies,
            'totalDivisions'   => $totalDivisions,
            'totalDepartments' => $totalDepartments,
            'companies'        => $companies,
            'divisions'        => $divisions,
            'departments'      => $departments,
        ]);
    }

    /**
     * DataTables AJAX server-side provider untuk Resign Management.
     */
    public function resignDatatable(Request $request)
    {
        $query = User::onlyTrashed()->select([
            'id',
            'id_emp',
            'name',
            'email',
            'sap_code',
            'company_name',
            'jobgrade',
            'jobposition',
            'team',
            'department',
            'division',
            'orgunit',
            'id_org_unit',
            'cell_phone',
            'sex',
            'id_user',
            'positionID',
            'appr1',
            'appr2',
            'role',
            'resign',
            'flag',
            'job_clan',
            'job_family',
            'deleted_at',
            'created_at',
            'updated_at',
        ]);

        // Filter: Company
        if ($request->filled('company')) {
            $query->where('company_name', $request->input('company'));
        }

        // Filter: Division
        if ($request->filled('division')) {
            $query->where('division', $request->input('division'));
        }

        // Filter: Department
        if ($request->filled('department')) {
            $query->where('department', $request->input('department'));
        }

        // Filter: Resign Date Range (From & To)
        if ($request->filled('date_from')) {
            $query->whereDate('deleted_at', '>=', $request->input('date_from'));
        }
        if ($request->filled('date_to')) {
            $query->whereDate('deleted_at', '<=', $request->input('date_to'));
        }

        // Filter: Specific Resign Date
        if ($request->filled('resign_date')) {
            $query->whereDate('deleted_at', $request->input('resign_date'));
        }

        // Custom Search (Employee ID, Name, SAP Code, Email, Job Position, etc.)
        $search = trim((string) ($request->input('search_keyword') ?: $request->input('search.value')));
        if ($search !== '') {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('id_emp', 'like', "%{$search}%")
                    ->orWhere('sap_code', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%")
                    ->orWhere('company_name', 'like', "%{$search}%")
                    ->orWhere('jobposition', 'like', "%{$search}%")
                    ->orWhere('jobgrade', 'like', "%{$search}%")
                    ->orWhere('team', 'like', "%{$search}%")
                    ->orWhere('department', 'like', "%{$search}%")
                    ->orWhere('division', 'like', "%{$search}%")
                    ->orWhere('orgunit', 'like', "%{$search}%")
                    ->orWhere('id_org_unit', 'like', "%{$search}%");
            });
        }

        // Default sorting by newest Resign Date if not specified by user
        if (!$request->has('order')) {
            $query->orderBy('users.deleted_at', 'desc');
        }

        return DataTables::eloquent($query)
            ->addIndexColumn()
            ->addColumn('id_emp_formatted', function (User $user) {
                return $user->id_emp
                    ? '<span class="badge bg-blue-lt font-monospace text-primary fw-semibold px-2 py-1">' . e($user->id_emp) . '</span>'
                    : '<span class="text-muted">-</span>';
            })
            ->addColumn('name_formatted', function (User $user) {
                $name = e($user->name ?: '-');
                $email = e($user->email ?: '-');
                return '<div class="fw-bold text-dark emp-name">' . $name . '</div>'
                    . '<div class="text-muted small emp-email"><i class="ti ti-mail me-1"></i>' . $email . '</div>';
            })
            ->editColumn('sap_code', function (User $user) {
                return $user->sap_code
                    ? '<span class="font-monospace text-dark">' . e($user->sap_code) . '</span>'
                    : '<span class="text-muted">-</span>';
            })
            ->editColumn('company_name', function (User $user) {
                return $user->company_name
                    ? '<span class="badge bg-secondary-lt text-dark fw-medium">' . e($user->company_name) . '</span>'
                    : '<span class="text-muted">-</span>';
            })
            ->editColumn('jobgrade', fn (User $user) => $user->jobgrade ? e($user->jobgrade) : '<span class="text-muted">-</span>')
            ->editColumn('jobposition', fn (User $user) => $user->jobposition ? e($user->jobposition) : '<span class="text-muted">-</span>')
            ->editColumn('team', fn (User $user) => $user->team ? e($user->team) : '<span class="text-muted">-</span>')
            ->editColumn('department', fn (User $user) => $user->department ? e($user->department) : '<span class="text-muted">-</span>')
            ->editColumn('division', fn (User $user) => $user->division ? e($user->division) : '<span class="text-muted">-</span>')
            ->editColumn('orgunit', fn (User $user) => ($user->orgunit ?: $user->id_org_unit) ? e($user->orgunit ?: $user->id_org_unit) : '<span class="text-muted">-</span>')
            ->editColumn('deleted_at', function (User $user) {
                if (!$user->deleted_at) {
                    return '<span class="text-muted">-</span>';
                }
                $dateFormatted = $user->deleted_at->format('d M Y');
                $timeFormatted = $user->deleted_at->format('H:i') . ' WIB';
                $diffForHumans = $user->deleted_at->diffForHumans();
                return '<div class="d-flex flex-column">'
                    . '<span class="fw-semibold text-danger d-inline-flex align-items-center gap-1" title="' . e($diffForHumans) . '">'
                    . '<i class="ti ti-calendar-time fs-4"></i>' . e($dateFormatted) . '</span>'
                    . '<small class="text-muted">' . e($timeFormatted) . '</small>'
                    . '</div>';
            })
            ->addColumn('status_badge', function (User $user) {
                return '<span class="badge bg-danger-lt text-danger fw-semibold d-inline-flex align-items-center gap-1">'
                    . '<i class="ti ti-user-off fs-4"></i> Resigned</span>';
            })
            ->addColumn('action', function (User $user) {
                $userJson = htmlspecialchars(json_encode([
                    'id'           => $user->id,
                    'name'         => $user->name,
                    'id_emp'       => $user->id_emp,
                    'sap_code'     => $user->sap_code,
                    'email'        => $user->email,
                    'company_name' => $user->company_name,
                    'jobgrade'     => $user->jobgrade,
                    'jobposition'  => $user->jobposition,
                    'team'         => $user->team,
                    'department'   => $user->department,
                    'division'     => $user->division,
                    'orgunit'      => $user->orgunit ?: $user->id_org_unit,
                    'cell_phone'   => $user->cell_phone,
                    'sex'          => $user->sex,
                    'role'         => $user->role_name ?? 'User',
                    'positionID'   => $user->positionID,
                    'appr1'        => $user->appr1,
                    'appr2'        => $user->appr2,
                    'job_clan'     => $user->job_clan,
                    'job_family'   => $user->job_family,
                    'resign_flag'  => $user->resign,
                    'resign_date'  => $user->deleted_at ? $user->deleted_at->format('d M Y H:i:s') . ' WIB (' . $user->deleted_at->diffForHumans() . ')' : '-',
                    'created_at'   => $user->created_at ? $user->created_at->format('d M Y H:i:s') : '-',
                    'updated_at'   => $user->updated_at ? $user->updated_at->format('d M Y H:i:s') : '-',
                ]), ENT_QUOTES, 'UTF-8');

                return '<button type="button" class="btn btn-sm btn-outline-info btn-view-detail" '
                    . 'data-user="' . $userJson . '" title="View Detail (Read-Only)">'
                    . '<i class="ti ti-eye"></i> Detail</button>';
            })
            ->rawColumns([
                'id_emp_formatted',
                'name_formatted',
                'sap_code',
                'company_name',
                'jobgrade',
                'jobposition',
                'team',
                'department',
                'division',
                'orgunit',
                'deleted_at',
                'status_badge',
                'action',
            ])
            ->make(true);
    }

    public function liveSearch(Request $request)
    {
        $q = trim($request->get('q', ''));
        if ($q === '') {
            return response()->json([]);
        }

        $users = User::query()
            ->where(function ($query) use ($q) {
                $query->where('name', 'LIKE', "%{$q}%")
                      ->orWhere('email', 'LIKE', "%{$q}%")
                      ->orWhere('id_emp', 'LIKE', "%{$q}%");
            })
            ->limit(8)
            ->get(['id', 'name', 'email', 'id_emp']);

        return response()->json($users);
    }

    /**
     * Tampilkan halaman View Detail user lengkap dengan seluruh informasi akun, organisasi, dan profil HR.
     */
    public function show(User $user)
    {
        $divisions = User::query()
            ->whereNotNull('division')
            ->whereRaw("TRIM(division) != ''")
            ->selectRaw('TRIM(division) as division')
            ->distinct()
            ->orderBy('division')
            ->pluck('division');

        $departments = User::query()
            ->whereNotNull('department')
            ->whereRaw("TRIM(department) != ''")
            ->selectRaw('TRIM(department) as department')
            ->distinct()
            ->orderBy('department')
            ->pluck('department');

        $teams = User::query()
            ->whereNotNull('team')
            ->whereRaw("TRIM(team) != ''")
            ->selectRaw('TRIM(team) as team')
            ->distinct()
            ->orderBy('team')
            ->pluck('team');

        return view('main.manage-role.show', [
            'user'            => $user,
            'assignableRoles' => $this->assignableRoleLabels(),
            'divisions'       => $divisions,
            'departments'     => $departments,
            'teams'           => $teams,
        ]);
    }

    /**
     * Update user role and organizational assignment.
     */
    public function update(Request $request, User $user)
    {
        $request->validate([
            'role'        => ['required', 'in:' . implode(',', self::ASSIGNABLE_ROLES)],
            'active'      => ['nullable', 'in:0,1'],
            'division'    => ['nullable', 'string', 'max:255'],
            'department'  => ['nullable', 'string', 'max:255'],
            'team'        => ['nullable', 'string', 'max:255'],
            'id_org_unit' => ['nullable', 'string', 'max:20'],
        ], [
            'role.in' => 'Role yang dipilih tidak valid.',
        ]);

        if ($user->id === Auth::id()) {
            return redirect()->route('manage.role.index')
                ->with('warning', 'Anda tidak dapat mengubah data akun Anda sendiri.');
        }

        if ($user->isSuperAdmin()) {
            return redirect()->route('manage.role.index')
                ->with('warning', 'Data Super Admin tidak dapat diubah melalui halaman ini.');
        }

        if (!empty($request->id_org_unit) && strlen($request->id_org_unit) > 20) {
            return redirect()->back()
                ->withInput()
                ->with('warning', 'ID Org Unit terlalu panjang. Maksimal 20 karakter.');
        }

        $user->role = (int) $request->role;
        if ($request->has('active')) {
            $user->active = (int) $request->active;
        }
        $user->division    = $request->division;
        $user->department  = $request->department;
        $user->team        = $request->team;
        $user->id_org_unit = $request->id_org_unit;

        $user->save();

        return redirect()->route('manage.role.index')
            ->with('success', "Data {$user->name} berhasil diperbarui.");
    }

    /**
     * Soft-delete akun user dengan validasi integritas relasi.
     */
    public function destroy(User $user)
    {
        if ($user->id === Auth::id()) {
            return redirect()->route('manage.role.index')
                ->with('warning', 'Anda tidak dapat menghapus akun Anda sendiri.');
        }

        if ($user->isSuperAdmin()) {
            return redirect()->route('manage.role.index')
                ->with('warning', 'Akun Super Admin tidak dapat dihapus.');
        }

        // Periksa apakah user memiliki data pembelajaran aktif
        $hasLearningData = ContentUser::where('user_id', $user->id)->exists()
            || AssesmentResult::where('user_id', $user->id)->exists()
            || OfflineTrainingRegister::where('user_id', $user->id)->exists();

        if ($hasLearningData) {
            return redirect()->route('manage.role.index')
                ->with('warning', "User {$user->name} ({$user->id_emp}) tidak dapat dihapus karena memiliki riwayat pembelajaran / assessment / training aktif.");
        }

        $userName = $user->name;
        $empId = $user->id_emp ?: '-';
        $user->delete();

        return redirect()->route('manage.role.index')
            ->with('success', "User {$userName} (NIP: {$empId}) berhasil dihapus (soft-deleted).");
    }

    /**
     * Jalankan sinkronisasi employee NYATA (Write-Enabled ke Database OWL).
     */
    public function sync(Request $request)
    {
        @set_time_limit(300);

        $lockKey = 'owl:sync-employee-users:lock';
        $lock = Cache::lock($lockKey, 300);

        if (!$lock->get()) {
            return response()->json([
                'success' => false,
                'message' => 'Proses sinkronisasi lain sedang berjalan. Silakan tunggu beberapa saat.',
            ], 409);
        }

        try {
            $syncService = new EmployeeUserSyncService();
            $result = $syncService->sync([
                'dry_run' => false,
                'allow_create' => true,
            ]);

            return response()->json([
                'success'                    => true,
                'status'                     => strtoupper($result->status),
                'mode'                       => 'REAL (Synchronize Database)',
                'is_dry_run'                 => false,
                'duration'                   => $result->durationSeconds(),
                'total_api'                  => $result->totalApi,
                'total_owl'                  => $result->totalOwl,
                'created_count'              => $result->createdCount,
                'updated_count'              => $result->updatedCount,
                'updated_active_count'       => $result->updatedActiveCount,
                'updated_soft_deleted_count' => $result->updatedSoftDeletedCount,
                'unchanged_count'            => $result->unchangedCount,
                'skipped_count'              => $result->skippedCount,
                'conflict_count'             => $result->conflictCount,
                'conflicts'                  => array_slice($result->conflicts, 0, 5),
                'created_sample'             => array_slice($result->createdDetails, 0, 5),
                'message'                    => "Sinkronisasi berhasil! {$result->createdCount} user baru dibuat dan {$result->updatedCount} profil karyawan berhasil diperbarui ke database OWL.",
            ]);
        } catch (Throwable $e) {
            return response()->json([
                'success' => false,
                'message' => 'Gagal menjalankan sinkronisasi: ' . $e->getMessage(),
            ], 500);
        } finally {
            $lock->release();
        }
    }

    /**
     * Jalankan simulasi sinkronisasi employee (Dry-Run 100% Zero-Write).
     */
    /**
     * Memulai proses sinkronisasi employee di latar belakang (Non-Blocking).
     */
    public function startSync(Request $request)
    {
        $lockKey = 'owl:sync-employee-users:lock';
        $lock = Cache::lock($lockKey, 300);

        if (!$lock->get()) {
            $progress = EmployeeUserSyncService::getProgress();
            return response()->json([
                'success' => false,
                'message' => 'Proses sinkronisasi lain sedang berjalan atau memegang kunci eksekusi.',
                'is_running' => true,
                'progress' => $progress,
            ], 409);
        }

        // Lepas lock pemeriksaan agar background runner dapat mengambilnya
        $lock->release();

        $isDryRun = (bool) $request->input('dry_run', false);

        // Reset status progres di Cache sebelum proses dimulai
        EmployeeUserSyncService::resetProgress($isDryRun);

        // Jalankan background process CLI
        $this->dispatchBackgroundSync($isDryRun);

        return response()->json([
            'success' => true,
            'message' => 'Proses sinkronisasi berhasil dimulai di latar belakang.',
            'is_dry_run' => $isDryRun,
        ]);
    }

    /**
     * Membaca status dan progres sinkronisasi secara real-time via Cache (Polling).
     */
    public function syncProgress(Request $request)
    {
        $progress = EmployeeUserSyncService::getProgress();

        $elapsedSeconds = 0;
        if (!empty($progress['started_at'])) {
            try {
                $started = \Illuminate\Support\Carbon::parse($progress['started_at']);
                $ended = !empty($progress['finished_at']) ? \Illuminate\Support\Carbon::parse($progress['finished_at']) : now();
                $elapsedSeconds = max(0, $started->diffInSeconds($ended));
            } catch (\Throwable $e) {
                $elapsedSeconds = (int) ($progress['duration_seconds'] ?? 0);
            }
        } else {
            $elapsedSeconds = (int) ($progress['duration_seconds'] ?? 0);
        }

        return response()->json(array_merge([
            'success' => ($progress['status'] ?? '') !== 'failed',
        ], $progress, [
            'elapsed_seconds' => $elapsedSeconds,
        ]));
    }

    /**
     * Jalankan perintah CLI artisan sinkronisasi di latar belakang.
     */
    protected function dispatchBackgroundSync(bool $isDryRun = false): void
    {
        if (app()->environment('testing')) {
            return;
        }

        $phpFinder = new \Symfony\Component\Process\PhpExecutableFinder();
        $php = $phpFinder->find(false) ?: PHP_BINARY;

        if (!file_exists($php) || !str_ends_with(strtolower($php), 'php.exe')) {
            $laragonPhps = glob('C:/laragon/bin/php/*/php.exe');
            if (!empty($laragonPhps)) {
                $php = $laragonPhps[0];
            } else {
                $php = 'php';
            }
        }

        $artisan = base_path('artisan');
        $args = 'owl:sync-employee-users';
        if ($isDryRun) {
            $args .= ' --dry-run';
        }

        \Illuminate\Support\Facades\Log::info('ManageRoleController: Menjalankan worker latar belakang sinkronisasi', [
            'php' => $php,
            'artisan' => $artisan,
            'is_dry_run' => $isDryRun,
        ]);

        if (strtoupper(substr(PHP_OS, 0, 3)) === 'WIN') {
            // On Windows CMD, start requires a title parameter (use empty quotes "")
            // before the first quoted executable path:
            // Syntax: start "" /B "path\to\php.exe" "path\to\artisan" owl:sync-employee-users
            $cmd = 'start "" /B ' . escapeshellarg($php) . ' ' . escapeshellarg($artisan) . ' ' . $args . ' > NUL 2>&1';
            pclose(popen($cmd, 'r'));
        } else {
            $cmd = escapeshellarg($php) . ' ' . escapeshellarg($artisan) . ' ' . $args . ' > /dev/null 2>&1 &';
            exec($cmd);
        }
    }

    public function syncDryRun(Request $request)
    {
        $lockKey = 'owl:sync-employee-users:lock';
        $lock = Cache::lock($lockKey, 300);

        if (!$lock->get()) {
            return response()->json([
                'success' => false,
                'message' => 'Proses sinkronisasi lain sedang berjalan. Silakan tunggu beberapa saat.',
            ], 409);
        }

        try {
            $syncService = new EmployeeUserSyncService();
            $result = $syncService->sync([
                'dry_run' => true,
                'allow_create' => true,
            ]);

            return response()->json([
                'success'                    => true,
                'status'                     => strtoupper($result->status),
                'mode'                       => 'DRY-RUN (Zero-Write Simulation)',
                'is_dry_run'                 => true,
                'duration'                   => $result->durationSeconds(),
                'total_api'                  => $result->totalApi,
                'total_owl'                  => $result->totalOwl,
                'created_count'              => $result->createdCount,
                'updated_count'              => $result->updatedCount,
                'updated_active_count'       => $result->updatedActiveCount,
                'updated_soft_deleted_count' => $result->updatedSoftDeletedCount,
                'unchanged_count'            => $result->unchangedCount,
                'skipped_count'              => $result->skippedCount,
                'conflict_count'             => $result->conflictCount,
                'conflicts'                  => array_slice($result->conflicts, 0, 5),
                'created_sample'             => array_slice($result->createdDetails, 0, 5),
                'message'                    => 'Simulasi sinkronisasi berhasil dijalankan tanpa mengubah data di database.',
            ]);
        } catch (Throwable $e) {
            return response()->json([
                'success' => false,
                'message' => 'Gagal menjalankan simulasi sinkronisasi: ' . $e->getMessage(),
            ], 500);
        } finally {
            $lock->release();
        }
    }

    /**
     * Pasangan value => label untuk pilihan role yang boleh di-set.
     */
    private function assignableRoleLabels(): array
    {
        $labels = User::roles();

        return collect(self::ASSIGNABLE_ROLES)
            ->mapWithKeys(fn ($role) => [$role => $labels[$role] ?? $role])
            ->toArray();
    }
}