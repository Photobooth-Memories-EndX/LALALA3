@extends('layouts.sidebar-master')

@section('content')

<div class="page-header d-print-none">
    <div class="container-xl">
        <div class="row g-2 align-items-center">
            <div class="col">
                <h2 class="page-title">Manage Access Permission</h2>
                <div class="text-muted mt-1">
                    Atur akses module Manage LMS per user.
                </div>
            </div>
        </div>
    </div>
</div>

<div class="page-body">
    <div class="container-xl">

        <x-alert type="success" />
        <x-alert type="warning" />

        <div class="card">
            <div class="card-header">
                <h3 class="card-title">Daftar User</h3>

                <div class="card-actions">
                    <form method="GET" action="{{ route('manage.access-permission.index') }}" class="d-flex">
                        <input type="text"
                               name="s"
                               value="{{ $search }}"
                               class="form-control form-control-sm me-2"
                               placeholder="Cari nama / email / NIP / division...">

                        <button type="submit" class="btn btn-sm btn-primary">
                            Cari
                        </button>
                    </form>
                </div>
            </div>

            <div class="table-responsive">
                <table class="table table-vcenter card-table">
                    <thead>
                        <tr>
                            <th>No</th>
                            <th>Nama</th>
                            <th>Email</th>
                            <th>NIP</th>
                            <th>Division</th>
                            <th>Role</th>
                            <th class="w-1">Action</th>
                        </tr>
                    </thead>

                    <tbody>
                        @forelse ($users as $user)
                            <tr>
                                <td>{{ $loop->iteration + ($users->firstItem() - 1) }}</td>

                                <td>{{ $user->name }}</td>

                                <td>{{ $user->email }}</td>

                                <td>{{ $user->id_emp ?? '-' }}</td>

                                <td>{{ $user->division ?? '-' }}</td>

                                <td>
                                    <span class="badge bg-blue-lt">
                                        {{ $user->role_name }}
                                    </span>
                                </td>

                                <td>
                                    @if ($user->isSuperAdmin())
                                        <span class="text-muted small">
                                            Full Access
                                        </span>
                                    @else
                                        <a href="{{ route('manage.access-permission.edit', $user->id) }}"
                                           class="btn btn-sm btn-primary">
                                            Atur Permission
                                        </a>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="text-center text-muted">
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

    </div>
</div>

@endsection