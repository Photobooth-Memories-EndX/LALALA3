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

    .permission-page .permission-muted {
        color: rgba(var(--tblr-muted-rgb, 102, 115, 130), 1);
    }

    .permission-page .permission-section-header {
        background: #fff;
    }

    body[data-bs-theme="dark"] .permission-page .role-summary,
    body.dark-mode .permission-page .role-summary {
        background: var(--tblr-bg-surface, #182433);
        border-color: var(--tblr-border-color, #25384d);
        color: var(--tblr-body-color, #fcfdfe);
    }

    body[data-bs-theme="dark"] .permission-page .permission-module-section,
    body.dark-mode .permission-page .permission-module-section {
        background: var(--tblr-bg-surface, #182433);
        border-color: var(--tblr-border-color, #25384d);
        color: var(--tblr-body-color, #fcfdfe);
    }

    body[data-bs-theme="dark"] .permission-page .permission-section-header,
    body.dark-mode .permission-page .permission-section-header {
        background: var(--tblr-bg-surface, #182433);
        border-color: var(--tblr-border-color, #25384d);
        color: var(--tblr-body-color, #fcfdfe);
    }

    body[data-bs-theme="dark"] .permission-page .permission-card,
    body.dark-mode .permission-page .permission-card {
        background: var(--tblr-bg-surface-secondary, #1b293a);
        border-color: var(--tblr-border-color, #33465f);
        color: var(--tblr-body-color, #fcfdfe);
    }

    body[data-bs-theme="dark"] .permission-page .permission-card:hover,
    body.dark-mode .permission-page .permission-card:hover {
        border-color: var(--tblr-primary, #206bc4);
        box-shadow: 0 8px 20px rgba(0, 0, 0, .2);
    }

    body[data-bs-theme="dark"] .permission-page .permission-muted,
    body.dark-mode .permission-page .permission-muted,
    body[data-bs-theme="dark"] .permission-page .permission-key,
    body.dark-mode .permission-page .permission-key {
        color: var(--owl-text-tertiary, #bbbcbc);
    }

    body[data-bs-theme="dark"] .permission-page .permission-card .form-check-input:not(:checked),
    body.dark-mode .permission-page .permission-card .form-check-input:not(:checked) {
        background-color: var(--tblr-bg-surface, #182433);
        border-color: var(--owl-border, #8a8d8f);
    }

    body[data-bs-theme="dark"] .permission-page .sticky-footer-action,
    body.dark-mode .permission-page .sticky-footer-action {
        background: var(--tblr-bg-surface, #182433);
        border-color: var(--tblr-border-color, #25384d);
        color: var(--tblr-body-color, #fcfdfe);
    }
</style>

<div class="permission-page">

    <div class="page-header d-print-none mb-3">
        <div class="container-xl">
            <div class="row g-2 align-items-center">
                <div class="col">
                    <h2 class="page-title mb-1">Edit Access Permission</h2>
                    <div class="permission-muted">
                        Configure Manage LMS modules for the <strong>{{ $roleName }}</strong> role.
                    </div>
                </div>

                <div class="col-auto">
                    <a href="{{ route('manage.access-permission.index') }}" class="btn btn-outline-secondary">
                        Back
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
                            <div class="permission-muted small">Role ID</div>
                            <div class="fw-bold">Role {{ $role }}</div>
                        </div>

                        <div class="col-md-4">
                            <div class="permission-muted small">Role Name</div>
                            <div class="fw-bold">{{ $roleName }}</div>
                        </div>

                        <div class="col-md-4">
                            <div class="permission-muted small">Effect</div>
                            <div class="fw-bold">
                                Applies to all user roles. {{ $roleName }}.
                            </div>
                        </div>
                    </div>
                </div>

                @php
                    $debuggerModuleKeys = [
                        'learning_hours_management',
                        'playlist_debug_tools',
                    ];
                    $standardModules = collect($modules)->except($debuggerModuleKeys);
                    $debuggerModules = collect($modules)->only($debuggerModuleKeys);
                @endphp

                <div class="card permission-module-section border-0 shadow-sm">
                    <div class="card-header permission-section-header">
                        <div>
                            <h3 class="card-title mb-1">Module Permission</h3>
                            <div class="permission-muted small">
                               Select the modules this role can access.
                            </div>
                        </div>
                    </div>

                    <div class="card-body">
                        <div class="row g-3">
                            @foreach ($standardModules as $moduleKey => $moduleLabel)
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

                    <div class="card-header permission-section-header border-top">
                        <div>
                            <h3 class="card-title mb-1">Debugger Tools</h3>
                            <div class="permission-muted small">
                                Configure developer and QA tools available for this role.
                            </div>
                        </div>
                    </div>

                    <div class="card-body">
                        <div class="row g-3">
                            @foreach ($debuggerModules as $moduleKey => $moduleLabel)
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
                            <div class="permission-muted small">
                                Changes will automatically apply to all users with the role <strong>{{ $roleName }}</strong>.
                            </div>

                            <div class="d-flex gap-2 justify-content-end">
                                <a href="{{ route('manage.access-permission.index') }}" class="btn btn-outline-secondary">
                                    Cancel
                                </a>

                                <button type="submit" class="btn btn-primary">
                                    Save Permissions
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
