{{-- Daftar user + aksi edit role. Data: $users --}}
<div class="card border border-light-subtle shadow-sm rounded-3 role-table-card">

    <div class="card-header bg-transparent border-0 py-3 px-3 px-md-4">
        <div>
            <h3 class="card-title fw-bold text-dark mb-1">List User</h3>
            <div class="text-muted small">User, role, and organizational data.</div>
        </div>
    </div>

    <div class="table-responsive">
        <table class="table table-hover table-vcenter owl-table mb-0">
            <thead>
                <tr>
                    <th class="text-center" style="width: 50px;">No</th>
                    <th>Name</th>
                    <th>NIP</th>
                    <th>Division</th>
                    <th>Department</th>
                    <th>Team</th>
                    <th>Organization Unit</th>
                    <th>Current Role</th>
                    <th class="text-end" style="width: 140px;">Action</th>
                </tr>
            </thead>

            <tbody>
                @forelse ($users as $user)
                    <tr>
                        <td class="text-center text-muted">{{ $loop->iteration + ($users->firstItem() - 1) }}</td>

                        <td>
                            <div class="fw-bold">{{ $user->name }}</div>
                            <div class="text-muted small">{{ $user->email }}</div>
                        </td>

                        <td>{{ $user->id_emp ?: '-' }}</td>
                        <td>{{ $user->division ?: '-' }}</td>
                        <td>{{ $user->department ?: '-' }}</td>
                        <td>{{ $user->team ?: '-' }}</td>
                        <td>{{ $user->id_org_unit ?: '-' }}</td>

                        <td>
                            <span class="badge bg-blue-lt text-blue">{{ $user->role_name }}</span>
                        </td>

                        <td class="text-end">
                            <div class="btn-group btn-group-sm" role="group">
                                {{-- 1. View Detail Action --}}
                                <a href="{{ route('manage.role.show', $user->id) }}"
                                   class="btn btn-outline-info"
                                   title="View Detail (Profil Lengkap)">
                                    <i class="ti ti-eye"></i>
                                </a>

                                {{-- 2. Edit Action --}}
                                @if ($user->isSuperAdmin())
                                    <button type="button" class="btn btn-outline-secondary" disabled title="Super Admin Terkunci">
                                        <i class="ti ti-lock"></i>
                                    </button>
                                @elseif ($user->id === auth()->id())
                                    <button type="button" class="btn btn-outline-secondary" disabled title="Akun Anda Sendiri">
                                        <i class="ti ti-user-check"></i>
                                    </button>
                                @else
                                    <button type="button"
                                            class="btn btn-outline-primary"
                                            style="color: #003b6d; border-color: #003b6d;"
                                            data-bs-toggle="modal"
                                            data-bs-target="#editUserModal{{ $user->id }}"
                                            title="Edit Role & Organisasi">
                                        <i class="ti ti-edit"></i>
                                    </button>
                                @endif

                                {{-- 3. Delete Action --}}
                                @if ($user->isSuperAdmin() || $user->id === auth()->id())
                                    <button type="button" class="btn btn-outline-secondary" disabled title="Tidak dapat dihapus">
                                        <i class="ti ti-trash-off"></i>
                                    </button>
                                @else
                                    <button type="button"
                                            class="btn btn-outline-danger"
                                            data-bs-toggle="modal"
                                            data-bs-target="#deleteUserModal"
                                            data-action="{{ route('manage.role.destroy', $user->id) }}"
                                            data-name="{{ $user->name }}"
                                            data-emp="{{ $user->id_emp ?: '-' }}"
                                            title="Hapus User">
                                        <i class="ti ti-trash"></i>
                                    </button>
                                @endif
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="9" class="text-center text-muted py-4">
                            <i class="ti ti-user-off mb-2" style="font-size:28px;"></i>
                            <div>Tidak ada user ditemukan.</div>
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    @if ($users->hasPages())
        <div class="card-footer bg-transparent border-top py-3 d-flex flex-column flex-md-row justify-content-between align-items-md-center">
            <div class="text-muted small mb-2 mb-md-0">
                Menampilkan <strong>{{ $users->firstItem() }}</strong>–<strong>{{ $users->lastItem() }}</strong>
                dari <strong>{{ $users->total() }}</strong> user
            </div>
            <div>{{ $users->links() }}</div>
        </div>
    @endif

</div>

