@extends('layouts.sidebar-master')

@section('content')

<div class="page-header d-print-none">
    <div class="container-xl">
        <div class="row g-2 align-items-center">
            <div class="col">
                <h2 class="page-title">Edit Access Permission</h2>
                <div class="text-muted mt-1">
                    Atur module Manage LMS yang boleh diakses user.
                </div>
            </div>

            <div class="col-auto">
                <a href="{{ route('manage.access-permission.index') }}" class="btn btn-secondary">
                    Kembali
                </a>
            </div>
        </div>
    </div>
</div>

<div class="page-body">
    <div class="container-xl">

        <div class="card mb-3">
            <div class="card-body">
                <div class="row g-3">
                    <div class="col-md-3">
                        <div class="text-muted">Nama</div>
                        <div class="fw-bold">{{ $user->name }}</div>
                    </div>

                    <div class="col-md-3">
                        <div class="text-muted">Email</div>
                        <div class="fw-bold">{{ $user->email }}</div>
                    </div>

                    <div class="col-md-3">
                        <div class="text-muted">Role</div>
                        <span class="badge bg-blue-lt">{{ $user->role_name }}</span>
                    </div>

                    <div class="col-md-3">
                        <div class="text-muted">Division</div>
                        <div class="fw-bold">{{ $user->division ?? '-' }}</div>
                    </div>
                </div>
            </div>
        </div>

        <form method="POST" action="{{ route('manage.access-permission.update', $user->id) }}">
            @csrf
            @method('PUT')

            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">Module Permission</h3>
                </div>

                <div class="card-body">
                    <div class="row g-3">
                        @foreach ($modules as $moduleKey => $moduleLabel)
                            @php
                                $checked = isset($permissions[$moduleKey])
                                    ? (bool) $permissions[$moduleKey]
                                    : $user->isAdmin();
                            @endphp

                            <div class="col-md-6 col-lg-4">
                                <label class="form-check form-switch border rounded p-3 h-100">
                                    <input class="form-check-input"
                                           type="checkbox"
                                           name="permissions[]"
                                           value="{{ $moduleKey }}"
                                           {{ $checked ? 'checked' : '' }}>

                                    <span class="form-check-label fw-bold">
                                        {{ $moduleLabel }}
                                    </span>

                                    <div class="text-muted small mt-1">
                                        Key: {{ $moduleKey }}
                                    </div>
                                </label>
                            </div>
                        @endforeach
                    </div>
                </div>

                <div class="card-footer text-end">
                    <button type="submit" class="btn btn-primary">
                        Simpan Permission
                    </button>
                </div>
            </div>
        </form>

    </div>
</div>

@endsection