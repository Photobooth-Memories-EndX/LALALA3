@extends('layouts.sidebar-master')

@section('content')

<div class="page-header d-print-none">
    <div class="container-xl">
        <div class="row g-2 align-items-center">
            <div class="col">
                <h2 class="page-title">Manage Role</h2>
                <div class="text-muted mt-1">
                    Kelola role, division, department, team, dan org unit user.
                </div>
            </div>

            <div class="col-auto ms-auto d-print-none">
                <ol class="breadcrumb breadcrumb-arrows" aria-label="breadcrumbs">
                    <li class="breadcrumb-item"><a href="#">Manage</a></li>
                    <li class="breadcrumb-item active" aria-current="page"><a href="#">Role</a></li>
                </ol>
            </div>
        </div>
    </div>
</div>

<div class="page-body">
    <div class="container-xl">

        <x-alert type="success" />
        <x-alert type="warning" />

        @if ($errors->any())
            <div class="alert alert-danger">
                {!! implode('', $errors->all('<div>:message</div>')) !!}
            </div>
        @endif

        <div class="row row-cards mb-4">
            <div class="col-sm-6 col-lg-3">
                <div class="card">
                    <div class="card-body">
                        <div class="text-muted">Total User</div>
                        <div class="h1 mb-0">{{ number_format($totalUsers) }}</div>
                    </div>
                </div>
            </div>

            <div class="col-sm-6 col-lg-3">
                <div class="card">
                    <div class="card-body">
                        <div class="text-muted">Division</div>
                        <div class="h1 mb-0">{{ number_format($totalDivision) }}</div>
                    </div>
                </div>
            </div>

            <div class="col-sm-6 col-lg-3">
                <div class="card">
                    <div class="card-body">
                        <div class="text-muted">Department</div>
                        <div class="h1 mb-0">{{ number_format($totalDepartment) }}</div>
                    </div>
                </div>
            </div>

            <div class="col-sm-6 col-lg-3">
                <div class="card">
                    <div class="card-body">
                        <div class="text-muted">Team</div>
                        <div class="h1 mb-0">{{ number_format($totalTeam) }}</div>
                    </div>
                </div>
            </div>
        </div>

        <div class="card mb-3">
            <div class="card-header">
                <h3 class="card-title">Filter User</h3>
            </div>

            <div class="card-body">
                <form method="GET" action="{{ route('manage.role.index') }}">
                    <div class="row g-2">
                        <div class="col-md-3">
                            <input type="text"
                                   name="s"
                                   value="{{ $search ?? '' }}"
                                   class="form-control"
                                   placeholder="Cari nama / email / NIP...">
                        </div>

                        <div class="col-md-3">
                            <select name="division" class="form-select">
                                <option value="">All Division</option>
                                @foreach ($divisions as $item)
                                    <option value="{{ $item }}" {{ ($division ?? '') == $item ? 'selected' : '' }}>
                                        {{ $item }}
                                    </option>
                                @endforeach
                            </select>
                        </div>

                        <div class="col-md-3">
                            <select name="department" class="form-select">
                                <option value="">All Department</option>
                                @foreach ($departments as $item)
                                    <option value="{{ $item }}" {{ ($department ?? '') == $item ? 'selected' : '' }}>
                                        {{ $item }}
                                    </option>
                                @endforeach
                            </select>
                        </div>

                        <div class="col-md-3">
                            <select name="team" class="form-select">
                                <option value="">All Team</option>
                                @foreach ($teams as $item)
                                    <option value="{{ $item }}" {{ ($team ?? '') == $item ? 'selected' : '' }}>
                                        {{ $item }}
                                    </option>
                                @endforeach
                            </select>
                        </div>

                        <div class="col-md-12 d-flex justify-content-end gap-2 mt-3">
                            <a href="{{ route('manage.role.index') }}" class="btn btn-outline-secondary">
                                Reset
                            </a>
                            <button type="submit" class="btn btn-primary">
                                Terapkan Filter
                            </button>
                        </div>
                    </div>
                </form>
            </div>
        </div>

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
                                        {{ $user->role_name ?? $user->role ?? '-' }}
                                    </span>
                                </td>

                                <td>
                                    @if (method_exists($user, 'isSuperAdmin') && $user->isSuperAdmin())
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

        {{-- Modal Laravel 10 / Bootstrap 5 / Tabler --}}
        @foreach ($users as $user)
            @if (!(method_exists($user, 'isSuperAdmin') && $user->isSuperAdmin()) && $user->id !== auth()->id())
                <div class="modal modal-blur fade"
                     id="editUserModal{{ $user->id }}"
                     tabindex="-1"
                     aria-hidden="true">

                    <div class="modal-dialog modal-xl modal-dialog-centered" role="document">
                        <form method="POST"
                              action="{{ route('manage.role.update', $user->id) }}"
                              class="modal-content">

                            @csrf
                            @method('PUT')

                            <div class="modal-header">
                                <h5 class="modal-title">
                                    <i class="ti ti-user-cog me-1"></i>
                                    Edit User Organization
                                </h5>

                                <button type="button"
                                        class="btn-close"
                                        data-bs-dismiss="modal"
                                        aria-label="Close"></button>
                            </div>

                            <div class="modal-body">
                                <div class="card bg-body border mb-4">
                                    <div class="card-body">
                                        <div class="d-flex align-items-center">
                                            <span class="avatar avatar-md me-3">
                                                {{ strtoupper(substr($user->name, 0, 1)) }}
                                            </span>

                                            <div>
                                                <div class="fw-bold">{{ $user->name }}</div>
                                                <div class="text-muted small">{{ $user->email }}</div>
                                                <div class="text-muted small">
                                                    NIP: {{ $user->id_emp ?? '-' }}
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <div class="row g-3">
                                    <div class="col-md-6">
                                        <label class="form-label">Role</label>
                                        <select name="role" class="form-select">
                                            @foreach ($assignableRoles as $value => $label)
                                                <option value="{{ $value }}"
                                                    {{ (string) $user->role === (string) $value ? 'selected' : '' }}>
                                                    {{ $label }}
                                                </option>
                                            @endforeach
                                        </select>
                                    </div>

                                    <div class="col-md-6">
                                        <label class="form-label">Division</label>
                                        <select name="division" class="form-select">
                                            <option value="">- Pilih Division -</option>
                                            @foreach ($divisions as $item)
                                                <option value="{{ $item }}"
                                                    {{ $user->division == $item ? 'selected' : '' }}>
                                                    {{ $item }}
                                                </option>
                                            @endforeach
                                        </select>
                                    </div>

                                    <div class="col-md-6">
                                        <label class="form-label">Department</label>
                                        <select name="department" class="form-select">
                                            <option value="">- Pilih Department -</option>
                                            @foreach ($departments as $item)
                                                <option value="{{ $item }}"
                                                    {{ $user->department == $item ? 'selected' : '' }}>
                                                    {{ $item }}
                                                </option>
                                            @endforeach
                                        </select>
                                    </div>

                                    <div class="col-md-6">
                                        <label class="form-label">Team</label>
                                        <select name="team" class="form-select">
                                            <option value="">- Pilih Team -</option>
                                            @foreach ($teams as $item)
                                                <option value="{{ $item }}"
                                                    {{ $user->team == $item ? 'selected' : '' }}>
                                                    {{ $item }}
                                                </option>
                                            @endforeach
                                        </select>
                                    </div>

                                    <div class="col-md-12">
                                        <label class="form-label">Organization Unit</label>
                                        <input type="text"
                                               name="id_org_unit"
                                               value="{{ $user->id_org_unit }}"
                                               class="form-control"
                                               placeholder="Contoh: ORG001">
                                    </div>
                                </div>
                            </div>

                            <div class="modal-footer">
                                <button type="button"
                                        class="btn btn-link link-secondary"
                                        data-bs-dismiss="modal">
                                    Batal
                                </button>

                                <button type="submit"
                                        class="btn btn-primary"
                                        onclick="return confirm('Simpan perubahan {{ $user->name }}?')">
                                    <i class="ti ti-device-floppy me-1"></i>
                                    Simpan Perubahan
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            @endif
        @endforeach

    </div>
</div>

@endsection
