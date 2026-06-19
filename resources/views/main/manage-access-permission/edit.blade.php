@extends('layouts.sidebar-master')

@section('content')

<style>
    .permission-page .role-summary {
        border: 1px solid #e6eaf0;
        border-radius: 14px;
        background: #fff;
    }

    .permission-page .permission-card {
        border: 1px solid #e6eaf0;
        border-radius: 14px;
        transition: .15s ease-in-out;
        background: #fff;
    }

    .permission-page .permission-card:hover {
        border-color: #206bc4;
        box-shadow: 0 8px 20px rgba(29, 39, 59, .08);
    }

    .permission-page .permission-card .form-check-input {
        transform: scale(1.15);
        cursor: pointer;
    }

    .permission-page .permission-key {
        font-size: 11px;
        color: #667085;
        word-break: break-all;
    }

    .permission-page .sticky-footer-action {
        position: sticky;
        bottom: 0;
        z-index: 10;
        background: #fff;
        border-top: 1px solid #e6eaf0;
        border-radius: 0 0 14px 14px;
    }
</style>

<div class="permission-page">

    <div class="page-header d-print-none mb-3">
        <div class="container-xl">
            <div class="row g-2 align-items-center">
                <div class="col">
                    <h2 class="page-title mb-1">Edit Access Permission</h2>
                    <div class="text-muted">
                        Atur module Manage LMS untuk role <strong>{{ $roleName }}</strong>.
                    </div>
                </div>

                <div class="col-auto">
                    <a href="{{ route('manage.access-permission.index') }}" class="btn btn-outline-secondary">
                        Kembali
                    </a>
                </div>
            </div>
        </div>
    </div>

    <div class="page-body">
        <div class="container-xl">

            <form method="POST" action="{{ route('manage.access-permission.update', $role) }}">
                @csrf
                @method('PUT')

                <div class="role-summary p-3 mb-3">
                    <div class="row g-3 align-items-center">
                        <div class="col-md-4">
                            <div class="text-muted small">Role ID</div>
                            <div class="fw-bold">Role {{ $role }}</div>
                        </div>

                        <div class="col-md-4">
                            <div class="text-muted small">Role Name</div>
                            <div class="fw-bold">{{ $roleName }}</div>
                        </div>

                        <div class="col-md-4">
                            <div class="text-muted small">Effect</div>
                            <div class="fw-bold">
                                Berlaku untuk semua user role {{ $roleName }}.
                            </div>
                        </div>
                    </div>
                </div>

                <div class="card border-0 shadow-sm">
                    <div class="card-header bg-white">
                        <div>
                            <h3 class="card-title mb-1">Module Permission</h3>
                            <div class="text-muted small">
                                Aktifkan module yang boleh diakses oleh role ini.
                            </div>
                        </div>
                    </div>

                    <div class="card-body">
                        <div class="row g-3">
                            @foreach ($modules as $moduleKey => $moduleLabel)
                                @php
                                    $checked = isset($permissions[$moduleKey])
                                        ? (bool) $permissions[$moduleKey]
                                        : $role === \App\Models\User::ROLE_ADMIN;
                                @endphp

                                <div class="col-sm-6 col-xl-4">
                                    <label class="permission-card d-flex align-items-start gap-3 p-3 h-100">
                                        <input class="form-check-input mt-1"
                                               type="checkbox"
                                               name="permissions[]"
                                               value="{{ $moduleKey }}"
                                               {{ $checked ? 'checked' : '' }}>

                                        <div class="flex-fill">
                                            <div class="fw-bold mb-1">
                                                {{ $moduleLabel }}
                                            </div>

                                            <div class="permission-key">
                                                {{ $moduleKey }}
                                            </div>
                                        </div>
                                    </label>
                                </div>
                            @endforeach
                        </div>
                    </div>

                    <div class="sticky-footer-action p-3">
                        <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3">
                            <div class="text-muted small">
                                Perubahan otomatis berlaku ke semua user dengan role <strong>{{ $roleName }}</strong>.
                            </div>

                            <div class="d-flex gap-2 justify-content-end">
                                <a href="{{ route('manage.access-permission.index') }}" class="btn btn-outline-secondary">
                                    Batal
                                </a>

                                <button type="submit" class="btn btn-primary">
                                    Simpan Permission
                                </button>
                            </div>
                        </div>
                    </div>
                </div>

            </form>

        </div>
    </div>

</div>

@endsection
