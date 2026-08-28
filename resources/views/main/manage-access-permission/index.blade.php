@extends('layouts.sidebar-master')

@section('content')

<div class="page-header d-print-none">
    <div class="container-xl">
        <div class="row g-2 align-items-center">
            <div class="col">
                <h2 class="page-title">Manage Access Permission</h2>
                <div class="text-muted mt-1">
                    Atur akses module Manage LMS berdasarkan role.
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
                <h3 class="card-title">Role Permission</h3>
            </div>

            <div class="table-responsive">
                <table class="table table-vcenter card-table">
                    <thead>
                        <tr>
                            <th>No</th>
                            {{-- <th>Role ID</th> --}}
                            <th>Role Name</th>
                            <th>Default Access</th>
                            <th class="w-1">Action</th>
                        </tr>
                    </thead>

                    <tbody>
                        @foreach ($roles as $role => $roleName)
                            <tr>
                                <td>{{ $loop->iteration }}</td>

                                {{-- <td>
                                    <span class="badge bg-blue-lt">
                                        Role {{ $role }}
                                    </span>
                                </td> --}}

                                <td class="fw-bold">
                                    {{ $roleName }}
                                </td>

                                <td>
                                    @if ($role === \App\Models\User::ROLE_SUPER_ADMIN)
                                        <span class="badge bg-green-lt">
                                            Full Access
                                        </span>
                                    @elseif ($role === \App\Models\User::ROLE_ADMIN)
                                        <span class="badge bg-blue-lt">
                                            Manage LMS Default
                                        </span>
                                    @elseif ($role === \App\Models\User::ROLE_SUBADMIN_DIVISION)
                                        <span class="badge bg-yellow-lt">
                                            Monitoring Division
                                        </span>
                                    @else
                                        <span class="badge bg-secondary-lt">
                                            Learning User
                                        </span>
                                    @endif
                                </td>

                                <td>
                                    <a href="{{ route('manage.access-permission.edit', $role) }}"
                                       class="btn btn-sm btn-primary">
                                        Settings Permission
                                    </a>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

        </div>

        <div class="alert alert-info mt-3">
            These permissions apply to all users assigned to this role. If access to the Content Repository is disabled for Role 2, all users with Role 2 will no longer be able to access that module.
        </div>

    </div>
</div>

@endsection
