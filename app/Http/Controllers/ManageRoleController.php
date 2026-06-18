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
        $search = $request->input('s');

        $users = User::query()
            ->when($search, function ($query) use ($search) {
                $query->where(function ($q) use ($search) {
                    $q->where('name', 'like', "%{$search}%")
                        ->orWhere('email', 'like', "%{$search}%")
                        ->orWhere('id_emp', 'like', "%{$search}%")
                        ->orWhere('division', 'like', "%{$search}%");
                });
            })
            ->orderBy('name')
            ->paginate(15)
            ->withQueryString();

        return view('main.manage-role.index', [
            'users'           => $users,
            'search'          => $search,
            'assignableRoles' => $this->assignableRoleLabels(),
        ]);
    }

    /**
     * Ubah role seorang user.
     */
    public function update(Request $request, User $user)
    {
        $request->validate([
            'role' => ['required', 'in:' . implode(',', self::ASSIGNABLE_ROLES)],
        ], [
            'role.in' => 'Role tidak valid. Super Admin tidak dapat diberikan melalui halaman ini.',
        ]);

        // Tidak boleh mengubah role diri sendiri (mencegah Super Admin mengunci diri).
        if ($user->id === Auth::id()) {
            return redirect()->route('manage.role.index')
                ->with('warning', 'Anda tidak dapat mengubah role akun Anda sendiri.');
        }

        // Lindungi akun Super Admin: role 3 tidak boleh diubah dari sini.
        if ($user->isSuperAdmin()) {
            return redirect()->route('manage.role.index')
                ->with('warning', 'Role Super Admin tidak dapat diubah melalui halaman ini.');
        }

        // Direct assignment (bukan mass-assign) karena 'role' tidak termasuk
        // $fillable pada model User — sama seperti pola di AdminController.
        $user->role = $request->role;
        $user->save();

        return redirect()->route('manage.role.index')
            ->with('success', "Role {$user->name} berhasil diperbarui menjadi {$user->role_name}.");
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
