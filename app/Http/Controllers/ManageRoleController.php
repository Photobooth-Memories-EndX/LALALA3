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
            'totalDivision'   => $divisions->count(),
            'totalDepartment' => $departments->count(),
            'totalTeam'       => $teams->count(),
        ]);
    }

    /**
     * AJAX autocomplete live search for Manage Role.
     */
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