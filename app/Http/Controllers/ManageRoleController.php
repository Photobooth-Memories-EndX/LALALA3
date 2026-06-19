<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ManageRoleController extends Controller
{
    /**
     * Role yang boleh di-set lewat Manage Role.
     * Super Admin (3) sengaja TIDAK termasuk: hanya boleh ada satu Super Admin.
     */
    private const ASSIGNABLE_ROLES = [
        User::ROLE_USER,               // 1 - User / Employee
        User::ROLE_ADMIN,              // 2 - Admin Training
        User::ROLE_SUBADMIN_DIVISION,  // 4 - Subadmin Division
    ];

    /**
     * Daftar user untuk pengelolaan role (Super Admin only).
     */
    public function index(Request $request)
    {
        $search     = $request->input('s');
        $division   = $request->input('division');
        $department = $request->input('department');
        $team       = $request->input('team');

        $users = User::query()
            ->when($search, function ($query) use ($search) {
                $query->where(function ($q) use ($search) {
                    $q->where('name', 'like', "%{$search}%")
                        ->orWhere('email', 'like', "%{$search}%")
                        ->orWhere('id_emp', 'like', "%{$search}%")
                        ->orWhere('division', 'like', "%{$search}%")
                        ->orWhere('department', 'like', "%{$search}%")
                        ->orWhere('team', 'like', "%{$search}%");
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
            ->orderBy('name')
            ->paginate(15)
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

        return view('main.manage-role.index', [
            'users'           => $users,
            'search'          => $search,
            'division'        => $division,
            'department'      => $department,
            'team'            => $team,
            'divisions'       => $divisions,
            'departments'     => $departments,
            'teams'           => $teams,
            'assignableRoles' => $this->assignableRoleLabels(),

            'totalUsers'      => User::count(),
            'totalDivision'   => $divisions->count(),
            'totalDepartment' => $departments->count(),
            'totalTeam'       => $teams->count(),
        ]);
    }

   public function update(Request $request, User $user)
{
    $request->validate([
        'role'        => ['required', 'in:' . implode(',', self::ASSIGNABLE_ROLES)],
        'division'    => ['nullable', 'string', 'max:255'],
        'department'  => ['nullable', 'string', 'max:255'],
        'team'        => ['nullable', 'string', 'max:255'],
        'id_org_unit' => ['nullable', 'string'],
    ], [
        'role.in' => 'Role tidak valid. Super Admin tidak dapat diberikan melalui halaman ini.',
    ]);

    if ($user->id === Auth::id()) {
        return redirect()->route('manage.role.index')
            ->with('warning', 'Anda tidak dapat mengubah data akun Anda sendiri.');
    }

    if ($user->isSuperAdmin()) {
        return redirect()->route('manage.role.index')
            ->with('warning', 'Data Super Admin tidak dapat diubah melalui halaman ini.');
    }

    // Sesuaikan angka 5 dengan panjang kolom database
    if (!empty($request->id_org_unit) && strlen($request->id_org_unit) > 5) {
        return redirect()->back()
            ->withInput()
            ->with('warning', 'ID Org Unit terlalu panjang. Maksimal 5 karakter.');
    }

    $user->role        = $request->role;
    $user->division    = $request->division;
    $user->department  = $request->department;
    $user->team        = $request->team;
    $user->id_org_unit = $request->id_org_unit;

    $user->save();

    return redirect()->route('manage.role.index')
        ->with('success', "Data {$user->name} berhasil diperbarui.");
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
