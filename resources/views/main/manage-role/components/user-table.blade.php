{{-- Daftar user + aksi edit role. Data: $users --}}
<div class="card owl-main-card">

    <div class="card-header bg-white border-0">
        <div>
            <h3 class="card-title mb-1">List User</h3>
            <div class="text-muted small">User, role, and organizational data.</div>
        </div>
    </div>

    <div class="table-responsive">
        <table class="table table-hover table-vcenter owl-table mb-0">
            <thead>
                <tr>
                    <th class="text-center">No</th>
                    <th>Name</th>
                    <th>NIP</th>
                    <th>Division</th>
                    <th>Department</th>
                    <th>Team</th>
                    <th>Organization Unit</th>
                    <th>Current Role</th>
                    <th class="text-right">Action</th>
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

                        <td class="text-right">
                            @if ($user->isSuperAdmin())
                                <span class="text-muted small"><i class="ti ti-lock mr-1"></i>Terkunci</span>
                            @elseif ($user->id === auth()->id())
                                <span class="text-muted small"><i class="ti ti-user-check mr-1"></i>Akun Anda</span>
                            @else
                                <button type="button"
                                        class="btn btn-sm btn-outline-primary owl-action-btn"
                                        data-bs-toggle="modal"
                                        data-bs-target="#editUserModal{{ $user->id }}">
                                    <i class="ti ti-edit mr-1"></i> Edit
                                </button>
                            @endif
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
        <div class="card-footer d-flex flex-column flex-md-row justify-content-between align-items-md-center">
            <div class="text-muted small mb-2 mb-md-0">
                Menampilkan <strong>{{ $users->firstItem() }}</strong>–<strong>{{ $users->lastItem() }}</strong>
                dari <strong>{{ $users->total() }}</strong> user
            </div>
            <div>{{ $users->links() }}</div>
        </div>
    @endif

</div>
