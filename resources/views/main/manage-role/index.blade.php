@extends('layouts.sidebar-master')

@section('content')

    {{-- Reuse design system (owl-* cards/table + dark mode) --}}
    @include('main.monitoring.components.style')

    {{-- Page Header --}}
    <div class="page-header d-print-none mb-0 pb-0">
        <div class="container-xl">
            <!-- Back Navigation Link -->
            <a href="{{ route('manage.dashboard') }}" class="btn btn-link p-0 mb-3 text-decoration-none d-inline-flex align-items-center gap-1 fw-semibold text-primary">
                <i class="ti ti-arrow-left fs-2"></i> Back
            </a>

            <!-- Manage Role Hero Header Card -->
            <div class="card border border-light-subtle shadow-sm p-4 mb-3 rounded-3 bg-white hero-header-card">
                <div class="d-flex flex-column flex-md-row align-items-md-center justify-content-between gap-3">
                    <div class="d-flex align-items-center gap-3">
                        <div class="rounded-3 d-flex align-items-center justify-content-center flex-shrink-0 hero-icon-box"
                             style="width: 72px; height: 72px; background-color: #eef2ff; color: #003b6d;">
                            <i class="ti ti-user-check" style="font-size: 2rem !important;"></i>
                        </div>
                        <div>
                            <h2 class="h3 fw-bold mb-1 text-dark header-title">Manage Role</h2>
                            <p class="text-muted small mb-0 header-subtitle">Manage user roles, divisions, departments, teams, and organizational units.</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="page-body mt-0 pt-0">
        <div class="container-xl">

            <x-alert type="success" />
            <x-alert type="warning" />

            @if ($errors->any())
                <div class="alert alert-danger shadow-sm mb-3" role="alert">
                    {!! implode('', $errors->all('<div>:message</div>')) !!}
                </div>
            @endif

            @include('main.manage-role.components.stat-cards')

            @include('main.manage-role.components.filter-card')

            @include('main.manage-role.components.user-table')

            {{-- Modal wajib di luar table/tbody agar markup tabel tidak pecah --}}
            @foreach ($users as $user)
                @if (!$user->isSuperAdmin() && $user->id !== auth()->id())
                    @include('main.manage-role.components.edit-user-modal', ['user' => $user])
                @endif
            @endforeach

        </div>
    </div>

@endsection

@section('style')
<style>
    /* Hero header & Card styling */
    .hero-header-card {
        transition: background-color 0.2s ease, border-color 0.2s ease;
    }

    .filter-label-head {
        color: #1e293b;
    }

    /* Dark Mode Overrides */
    [data-bs-theme="dark"] .hero-header-card,
    [data-theme="dark"] .hero-header-card,
    body.theme-dark .hero-header-card,
    [data-bs-theme="dark"] .stat-card-item,
    [data-theme="dark"] .stat-card-item,
    body.theme-dark .stat-card-item,
    [data-bs-theme="dark"] .role-filter-card,
    [data-theme="dark"] .role-filter-card,
    body.theme-dark .role-filter-card,
    [data-bs-theme="dark"] .role-table-card,
    [data-theme="dark"] .role-table-card,
    body.theme-dark .role-table-card {
        background-color: #1e293b !important;
        border: 1px solid rgba(255, 255, 255, 0.12) !important;
        color: #f8fafc !important;
    }

    [data-bs-theme="dark"] .hero-icon-box,
    [data-theme="dark"] .hero-icon-box,
    body.theme-dark .hero-icon-box {
        background-color: rgba(0, 59, 109, 0.35) !important;
        color: #60a5fa !important;
    }

    [data-bs-theme="dark"] .header-title,
    [data-theme="dark"] .header-title,
    body.theme-dark .header-title,
    [data-bs-theme="dark"] .card-title,
    [data-theme="dark"] .card-title,
    body.theme-dark .card-title,
    [data-bs-theme="dark"] .filter-label-head,
    [data-theme="dark"] .filter-label-head,
    body.theme-dark .filter-label-head {
        color: #f8fafc !important;
    }

    [data-bs-theme="dark"] .header-subtitle,
    [data-theme="dark"] .header-subtitle,
    body.theme-dark .header-subtitle {
        color: #94a3b8 !important;
    }

    [data-bs-theme="dark"] #moreRoleFiltersModal .modal-content,
    [data-theme="dark"] #moreRoleFiltersModal .modal-content,
    body.theme-dark #moreRoleFiltersModal .modal-content {
        background-color: var(--tblr-bg-surface, #1e293b) !important;
        color: #f8fafc !important;
        border: 1px solid rgba(255, 255, 255, 0.12) !important;
    }

    [data-bs-theme="dark"] #moreRoleFiltersModal .text-dark,
    [data-theme="dark"] #moreRoleFiltersModal .text-dark,
    body.theme-dark #moreRoleFiltersModal .text-dark {
        color: #f8fafc !important;
    }

    [data-bs-theme="dark"] #moreRoleFiltersModal .modal-header,
    [data-bs-theme="dark"] #moreRoleFiltersModal .modal-footer,
    [data-theme="dark"] #moreRoleFiltersModal .modal-header,
    [data-theme="dark"] #moreRoleFiltersModal .modal-footer,
    body.theme-dark #moreRoleFiltersModal .modal-header,
    body.theme-dark #moreRoleFiltersModal .modal-footer {
        border-color: rgba(255, 255, 255, 0.1) !important;
    }

    [data-bs-theme="dark"] #moreRoleFiltersModal .form-control,
    [data-bs-theme="dark"] #moreRoleFiltersModal .form-select,
    [data-bs-theme="dark"] .role-filter-card .form-control,
    [data-bs-theme="dark"] .role-filter-card .form-select,
    [data-theme="dark"] #moreRoleFiltersModal .form-control,
    [data-theme="dark"] #moreRoleFiltersModal .form-select,
    [data-theme="dark"] .role-filter-card .form-control,
    [data-theme="dark"] .role-filter-card .form-select,
    body.theme-dark #moreRoleFiltersModal .form-control,
    body.theme-dark #moreRoleFiltersModal .form-select,
    body.theme-dark .role-filter-card .form-control,
    body.theme-dark .role-filter-card .form-select {
        background-color: #0f172a !important;
        border-color: rgba(255, 255, 255, 0.15) !important;
        color: #f8fafc !important;
    }

    [data-bs-theme="dark"] .role-filter-card .form-select option,
    [data-bs-theme="dark"] #moreRoleFiltersModal .form-select option,
    [data-theme="dark"] .role-filter-card .form-select option,
    [data-theme="dark"] #moreRoleFiltersModal .form-select option,
    body.theme-dark .role-filter-card .form-select option,
    body.theme-dark #moreRoleFiltersModal .form-select option {
        background-color: #0f172a !important;
        color: #f8fafc !important;
    }

    /* Live Search Dropdown Styling & Dark Mode */
    #role-search-results {
        box-shadow: 0 10px 30px rgba(0, 0, 0, 0.15) !important;
        border: 1px solid #e2e8f0 !important;
    }

    #role-search-results .dropdown-item:hover,
    #role-search-results .dropdown-item:focus {
        background-color: rgba(15, 94, 156, .08);
    }

    #role-search-results .dropdown-item:not(:last-child) {
        border-bottom: 1px solid rgba(0, 0, 0, .05);
    }

    [data-bs-theme="dark"] #role-search-results,
    [data-theme="dark"] #role-search-results,
    body.theme-dark #role-search-results {
        background-color: #1e293b !important;
        border-color: rgba(255, 255, 255, 0.12) !important;
        box-shadow: 0 10px 25px rgba(0, 0, 0, 0.4) !important;
    }

    [data-bs-theme="dark"] #role-search-items,
    [data-theme="dark"] #role-search-items,
    body.theme-dark #role-search-items {
        background-color: #1e293b !important;
    }

    [data-bs-theme="dark"] #role-see-all-results,
    [data-theme="dark"] #role-see-all-results,
    body.theme-dark #role-see-all-results {
        background-color: #0f172a !important;
        border-color: rgba(255, 255, 255, 0.1) !important;
        color: #38bdf8 !important;
    }

    [data-bs-theme="dark"] #role-search-results .dropdown-item,
    [data-theme="dark"] #role-search-results .dropdown-item,
    body.theme-dark #role-search-results .dropdown-item {
        color: #f8fafc !important;
    }

    [data-bs-theme="dark"] #role-search-results .dropdown-item .text-dark,
    [data-theme="dark"] #role-search-results .dropdown-item .text-dark,
    body.theme-dark #role-search-results .dropdown-item .text-dark {
        color: #f8fafc !important;
    }

    [data-bs-theme="dark"] #role-search-results .dropdown-item:not(:last-child),
    [data-theme="dark"] #role-search-results .dropdown-item:not(:last-child),
    body.theme-dark #role-search-results .dropdown-item:not(:last-child) {
        border-bottom-color: rgba(255, 255, 255, 0.08) !important;
    }

    [data-bs-theme="dark"] #role-search-results .dropdown-item:hover,
    [data-bs-theme="dark"] #role-search-results .dropdown-item:focus,
    [data-theme="dark"] #role-search-results .dropdown-item:hover,
    [data-theme="dark"] #role-search-results .dropdown-item:focus,
    body.theme-dark #role-search-results .dropdown-item:hover,
    body.theme-dark #role-search-results .dropdown-item:focus {
        background-color: rgba(106, 174, 214, 0.16) !important;
    }
</style>
@endsection

@section('script')
<script>
document.addEventListener('DOMContentLoaded', function () {
    const searchInput = document.getElementById('role-search-input');
    const searchResultsDropdown = document.getElementById('role-search-results');
    const searchItemsContainer = document.getElementById('role-search-items');
    const seeAllResultsBtn = document.getElementById('role-see-all-results');
    const searchKeywordText = document.getElementById('search-keyword-text');
    let debounceTimer = null;

    if (!searchInput || !searchResultsDropdown) return;

    searchInput.addEventListener('input', function () {
        clearTimeout(debounceTimer);
        const query = this.value.trim();

        if (query.length === 0) {
            searchResultsDropdown.classList.add('d-none');
            searchResultsDropdown.style.display = 'none';
            return;
        }

        if (searchKeywordText) {
            searchKeywordText.textContent = query;
        }

        debounceTimer = setTimeout(() => {
            fetch(`/manage/role/live-search?q=${encodeURIComponent(query)}`)
                .then(response => response.json())
                .then(data => {
                    searchItemsContainer.innerHTML = '';

                    if (data && data.length > 0) {
                        data.forEach(user => {
                            const item = document.createElement('a');
                            item.className = 'dropdown-item d-flex align-items-center justify-content-between px-3 py-2 border-bottom';
                            item.href = `/manage/role?q=${encodeURIComponent(user.name)}`;
                            item.innerHTML = `
                                <div class="text-truncate me-2" style="max-width: 280px;">
                                    <strong class="d-block text-dark small text-truncate">${user.name}</strong>
                                    <span class="text-muted extra-small d-block text-truncate" style="font-size: 0.75rem;">${user.id_emp || ''} - ${user.email || ''}</span>
                                </div>
                                <span class="badge bg-primary-subtle text-primary rounded-pill extra-small px-2 py-1 flex-shrink-0">USER</span>
                            `;
                            searchItemsContainer.appendChild(item);
                        });
                    } else {
                        searchItemsContainer.innerHTML = '<div class="px-3 py-2 text-muted small">No users found</div>';
                    }

                    searchResultsDropdown.classList.remove('d-none');
                    searchResultsDropdown.style.display = 'block';
                })
                .catch(err => {
                    console.error('Role Live Search Error:', err);
                    searchResultsDropdown.classList.add('d-none');
                    searchResultsDropdown.style.display = 'none';
                });
        }, 250);
    });

    if (seeAllResultsBtn) {
        seeAllResultsBtn.addEventListener('click', function (e) {
            e.preventDefault();
            if (searchInput.form) {
                searchInput.form.submit();
            }
        });
    }

    document.addEventListener('click', function (e) {
        if (!searchInput.contains(e.target) && !searchResultsDropdown.contains(e.target)) {
            searchResultsDropdown.classList.add('d-none');
            searchResultsDropdown.style.display = 'none';
        }
    });
});
</script>
@endsection
