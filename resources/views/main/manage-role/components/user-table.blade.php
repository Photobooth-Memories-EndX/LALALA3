<div class="card">
    <div class="card-header">
        <h3 class="card-title">Daftar User</h3>
    </div>

    <div class="table-responsive">
        <table class="table table-vcenter card-table">
            <thead>
                <tr>
                    <th>No</th>
                    <th>Nama</th>
                    <th>NIP</th>
                    <th>Division</th>
                    <th>Department</th>
                    <th>Team</th>
                    <th>Org Unit</th>
                    <th>Role Saat Ini</th>
                    <th class="w-1">Action</th>
                </tr>
            </thead>

            <tbody>
                @forelse ($users as $user)
                    <tr>
                        <td>{{ $loop->iteration + ($users->firstItem() - 1) }}</td>

                        <td>
                            <div class="fw-bold">{{ $user->name }}</div>
                            <div class="text-muted small">{{ $user->email }}</div>
                        </td>

                        <td>{{ $user->id_emp ?? '-' }}</td>
                        <td>{{ $user->division ?? '-' }}</td>
                        <td>{{ $user->department ?? '-' }}</td>
                        <td>{{ $user->team ?? '-' }}</td>
                        <td>{{ $user->id_org_unit ?? '-' }}</td>

                        <td>
                            <span class="badge bg-blue-lt">
                                {{ $user->role_name }}
                            </span>
                        </td>

                        <td>
                            @if ($user->isSuperAdmin())
                                <span class="text-muted small">Terkunci</span>
                            @elseif ($user->id === auth()->id())
                                <span class="text-muted small">Akun Anda</span>
                            @else
                                <button type="button"
                                        class="btn btn-sm btn-primary"
                                        data-bs-toggle="modal"
                                        data-bs-target="#editUserModal{{ $user->id }}">
                                    Edit
                                </button>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="9" class="text-center text-muted">
                            Tidak ada user ditemukan.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="card-footer d-flex align-items-center">
        {{ $users->links() }}
    </div>
</div>
