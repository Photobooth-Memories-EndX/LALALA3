@extends('layouts.sidebar-master')

@section('content')

    <div class="page-header d-print-none">
        <div class="container-xl">
            <div class="row g-2 align-items-center">
                <div class="col">
                    <h2 class="page-title">Manage Role</h2>
                    <div class="text-muted mt-1">Kelola role user. Super Admin tidak dapat diberikan dari sini.</div>
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

            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">Daftar User</h3>
                    <div class="card-actions">
                        <form method="GET" action="{{ route('manage.role.index') }}" class="d-flex">
                            <input type="text" name="s" value="{{ $search }}"
                                   class="form-control form-control-sm me-2"
                                   placeholder="Cari nama / email / NIP / division...">
                            <button type="submit" class="btn btn-sm btn-primary">Cari</button>
                        </form>
                    </div>
                </div>

                <div class="table-responsive">
                    <table class="table table-vcenter card-table">
                        <thead>
                            <tr>
                                <th>No</th>
                                <th>Nama</th>
                                <th>NIP</th>
                                <th>Division</th>
                                <th>Role Saat Ini</th>
                                <th class="w-1">Ubah Role</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($users as $user)
                                <tr>
                                    <td>{{ $loop->iteration + ($users->firstItem() - 1) }}</td>
                                    <td>{{ $user->name }}</td>
                                    <td>{{ $user->id_emp ?? '-' }}</td>
                                    <td>{{ $user->division ?? '-' }}</td>
                                    <td>
                                        <span class="badge bg-blue-lt">{{ $user->role_name }}</span>
                                    </td>
                                    <td>
                                        @if ($user->isSuperAdmin())
                                            <span class="text-muted small">Terkunci (Super Admin)</span>
                                        @elseif ($user->id === auth()->id())
                                            <span class="text-muted small">Akun Anda</span>
                                        @else
                                            <form method="POST" action="{{ route('manage.role.update', $user->id) }}"
                                                  class="d-flex gap-2">
                                                @csrf
                                                @method('PUT')
                                                <select name="role" class="form-select form-select-sm">
                                                    @foreach ($assignableRoles as $value => $label)
                                                        <option value="{{ $value }}"
                                                            {{ $user->role === (string) $value ? 'selected' : '' }}>
                                                            {{ $label }}
                                                        </option>
                                                    @endforeach
                                                </select>
                                                <button type="submit" class="btn btn-sm btn-primary"
                                                        onclick="return confirm('Ubah role {{ $user->name }}?')">
                                                    Simpan
                                                </button>
                                            </form>
                                        @endif
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="6" class="text-center text-muted">Tidak ada user ditemukan.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                <div class="card-footer d-flex align-items-center">
                    {{ $users->links() }}
                </div>
            </div>

        </div>
    </div>

@endsection
