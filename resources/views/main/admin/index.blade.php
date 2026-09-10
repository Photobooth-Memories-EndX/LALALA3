@extends('layouts.sidebar-master')

@section('content')
@include('main.monitoring.components.style')

{{-- Page Header --}}
<div class="page-header d-print-none mb-0 pb-0">
    <div class="container-xl">
        <!-- Back Navigation Link -->
        <a href="{{ route('manage.dashboard') }}" class="btn btn-link p-0 mb-3 text-decoration-none d-inline-flex align-items-center gap-1 fw-semibold text-primary">
            <i class="ti ti-arrow-left fs-2"></i> Back
        </a>

        <!-- Manage Admin Hero Header Card -->
        <div class="card border border-light-subtle shadow-sm p-4 mb-3 rounded-3 bg-white hero-header-card admin-card-bordered">
            <div class="d-flex flex-column flex-md-row align-items-md-center justify-content-between gap-3">
                <div class="d-flex align-items-center gap-3">
                    <div class="rounded-3 d-flex align-items-center justify-content-center flex-shrink-0 hero-icon-box" 
                         style="width: 72px; height: 72px; background-color: #eef2ff; color: #003b6d;">
                        <i class="ti ti-shield-lock" style="font-size: 2rem !important;"></i>
                    </div>
                    <div>
                        <h2 class="h3 fw-bold mb-1 text-dark header-title">Manage Admin</h2>
                        <p class="text-muted small mb-0 header-subtitle">Admin, Role, and Permission Management</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

{{-- Page Body --}}
<div class="page-body mt-0 pt-0">
    <div class="container-xl">

        {{-- Alerts --}}
        <x-alert type="success" />
        <x-alert type="warning" />

        @if($errors->any())
            <div class="alert alert-danger shadow-sm mb-3">
                {!! implode('', $errors->all('<div>:message</div>')) !!}
            </div>
        @endif

        <div class="row g-3">
            {{-- Left Column: List Admin --}}
            <div class="col-12 col-lg-7 col-xl-8">
                <div class="card border border-light-subtle shadow-sm rounded-3 admin-card-bordered">
                    <div class="card-header bg-transparent border-0 pb-0 pt-3 px-3 px-md-4">
                        <div class="d-flex align-items-center justify-content-between">
                            <h3 class="card-title fw-bold text-dark m-0">List Admin</h3>
                            <span class="badge bg-primary-subtle text-primary rounded-pill px-3 py-1 fw-semibold">
                                {{ count($admin) }} Admins
                            </span>
                        </div>
                    </div>

                    <div class="card-body p-3 p-md-4">
                        {{-- Quick Filter Toolbar --}}
                        <div class="card border-0 shadow-none bg-light p-2 mb-3 rounded-3 quick-filter-box">
                            <div class="d-flex align-items-center gap-2 flex-wrap flex-md-nowrap w-100">
                                
                                <!-- 1. Entries Select -->
                                <div style="min-width: 110px; width: 110px;" class="flex-shrink-0">
                                    <select class="form-select rounded-3 shadow-none" id="entries-select">
                                        <option value="10">10 Entries</option>
                                        <option value="25" selected>25 Entries</option>
                                        <option value="50">50 Entries</option>
                                        <option value="100">100 Entries</option>
                                    </select>
                                </div>

                                <!-- 2. Search Input -->
                                <div class="position-relative flex-grow-1 min-w-0">
                                    <input type="text" id="admin-search-input" 
                                           class="form-control rounded-3 shadow-none" 
                                           placeholder="Search admin name, ID..." 
                                           autocomplete="off">
                                </div>

                                <!-- 3. Search Submit Button -->
                                <button type="button" id="btn-admin-search" 
                                        class="btn btn-primary px-3 shadow-none flex-shrink-0" 
                                        style="background-color: #003b6d; border-color: #003b6d;" 
                                        title="Search">
                                    <i class="ti ti-search fs-2"></i>
                                </button>

                                <!-- 4. More Filter Button -->
                                <button type="button" class="btn btn-outline-primary px-3 shadow-none flex-shrink-0" 
                                        style="color: #003b6d; border-color: #003b6d;" 
                                        data-bs-toggle="modal" data-bs-target="#moreFilterModal" 
                                        title="More Filters">
                                    <i class="ti ti-adjustments-horizontal fs-2"></i>
                                </button>

                                <!-- 5. Reset Button -->
                                <button type="button" id="btn-admin-reset" 
                                        class="btn btn-outline-primary px-3 shadow-none flex-shrink-0" 
                                        style="color: #003b6d; border-color: #003b6d;" 
                                        title="Reset">
                                    <i class="ti ti-refresh fs-2"></i>
                                </button>
                            </div>
                        </div>

                        {{-- Table --}}
                        <div class="table-responsive">
                            @if(count($admin) > 0)
                                <table class="table table-modern table-hover align-middle mb-0" id="MyTable">
                                    <thead>
                                        <tr>
                                            <th style="width: 60px;" class="text-center">No</th>
                                            <th style="width: 140px;">Employee ID</th>
                                            <th>Admin Name</th>
                                            <th style="width: 80px;" class="text-center">Action</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach ($admin as $list)
                                            <tr>
                                                <td class="text-center fw-medium text-muted">{{ $loop->iteration }}</td>
                                                <td>
                                                    <span class="badge bg-light text-dark border px-2 py-1 font-monospace">
                                                        {{ $list->id_emp }}
                                                    </span>
                                                </td>
                                                <td>
                                                    <div class="d-flex align-items-center gap-2">
                                                        <div class="admin-avatar-box rounded-circle d-flex align-items-center justify-content-center fw-bold"
                                                             style="width: 32px; height: 32px; background: #e0e7ff; color: #3730a3; font-size: 13px;">
                                                            {{ strtoupper(substr($list->name, 0, 1)) }}
                                                        </div>
                                                        <span class="fw-semibold text-dark admin-name">{{ $list->name }}</span>
                                                    </div>
                                                </td>
                                                <td class="text-center">
                                                    <x-form action="{{ route('admin.update', $list->id) }}" method="put" class="d-inline">
                                                        <button type="submit" title="Remove Admin" class="btn btn-outline-danger btn-icon rounded-3" onclick="return confirm('Are you sure you want to remove {{ $list->name }} from admin list?')">
                                                            <i class="ti ti-trash fs-2"></i>
                                                        </button>
                                                    </x-form>
                                                </td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            @else
                                <div class="text-center py-5 text-muted">
                                    <i class="ti ti-shield-off fs-1 d-block mb-2 text-secondary"></i>
                                    <p class="mb-0">No admin data found.</p>
                                </div>
                            @endif
                        </div>
                    </div>
                </div>
            </div>

            {{-- Right Column: Add Admin --}}
            <div class="col-12 col-lg-5 col-xl-4">
                <div class="card border border-light-subtle shadow-sm rounded-3 admin-card-bordered">
                    <div class="card-header bg-transparent border-0 pb-0 pt-3 px-3 px-md-4">
                        <div class="d-flex align-items-center gap-2">
                            <i class="ti ti-user-plus text-primary fs-2"></i>
                            <h3 class="card-title fw-bold text-dark m-0">Add Admin</h3>
                        </div>
                    </div>

                    <x-form action="{{ route('admin.store') }}" method="post">
                        <div class="card-body p-3 p-md-4">
                            <p class="text-muted small mb-3">
                                Assign admin privileges to an employee from the Training Team.
                            </p>

                            <div class="mb-3">
                                <label class="form-label fw-semibold text-dark" for="id_user">Training Team Member</label>
                                <x-select class="form-select rounded-3" name="id_user" id="id_user" required>
                                    <option value="">- Select User -</option>
                                    @foreach ($trainingteam as $list)
                                        <option value="{{ $list->id }}">{{ $list->name }}</option>
                                    @endforeach
                                </x-select>
                                <div class="form-text text-muted small mt-1">
                                    <i class="ti ti-info-circle me-1"></i>Add user if not exist yet at above list.
                                </div>
                            </div>
                        </div>

                        <div class="card-footer bg-transparent border-0 pt-0 pb-3 px-3 px-md-4 text-end">
                            <button type="submit" class="btn btn-primary px-4 fw-semibold rounded-3 d-inline-flex align-items-center gap-2" style="background-color: #003b6d; border-color: #003b6d;">
                                <i class="ti ti-check fs-2"></i> Submit
                            </button>
                        </div>
                    </x-form>
                </div>
            </div>
        </div>

    </div>
</div>

{{-- More Filter Modal --}}
<div class="modal fade" id="moreFilterModal" tabindex="-1" aria-labelledby="moreFilterModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow rounded-3">
            <div class="modal-header border-bottom">
                <h5 class="modal-title fw-bold" id="moreFilterModalLabel">
                    <i class="ti ti-adjustments-horizontal me-2 text-primary"></i>More Filters
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="mb-3">
                    <label class="form-label fw-semibold">Search by Name or ID</label>
                    <input type="text" id="modal-filter-q" class="form-control rounded-3" placeholder="Filter admin name or employee ID...">
                </div>
            </div>
            <div class="modal-footer border-top">
                <button type="button" class="btn btn-outline-secondary rounded-3" data-bs-dismiss="modal">Close</button>
                <button type="button" class="btn btn-primary rounded-3" id="btn-apply-modal-filter" data-bs-dismiss="modal" style="background-color: #003b6d; border-color: #003b6d;">
                    <i class="ti ti-check me-1"></i> Apply Filter
                </button>
            </div>
        </div>
    </div>
</div>

@endsection

@section('style')
    <!-- DataTables -->
    <link rel="stylesheet" href="{{ asset('css/dataTables.bootstrap5.min.css') }}">
    <link href="{{ asset('css/bootstrap-toggle.min.css') }}" rel="stylesheet">
    <style>
        /* Thin Subtle Card Border Definition */
        .admin-card-bordered,
        .card.admin-card-bordered {
            border: 1px solid #e2e8f0 !important; /* Thin 1px subtle light gray border */
            border-radius: 0.75rem !important;
        }

        .table-modern thead th {
            font-size: 11px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            color: #64748b;
            background: #f8fafc;
            border-bottom: 1px solid #e2e8f0;
            white-space: nowrap;
            padding: 12px 14px;
        }

        .table-modern tbody td {
            vertical-align: middle;
            padding: 12px 14px;
            border-color: #f1f5f9;
            font-size: 13px;
        }

        .table-modern tbody tr:hover {
            background-color: #f8fafc;
        }

        .btn-icon {
            width: 32px;
            height: 32px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            padding: 0;
        }

        /* Dark Mode Support */
        [data-bs-theme="dark"],
        [data-theme="dark"],
        body.theme-dark {
            --bg-card-dark: #1e293b;
            --bg-input-dark: #0f172a;
            --border-dark: #334155;
            --text-main-dark: #f8fafc;
            --text-muted-dark: #94a3b8;
        }

        /* Dark Mode Subtle Border Override */
        [data-bs-theme="dark"] .admin-card-bordered,
        [data-bs-theme="dark"] .card.admin-card-bordered,
        [data-theme="dark"] .admin-card-bordered,
        [data-theme="dark"] .card.admin-card-bordered,
        body.theme-dark .admin-card-bordered,
        body.theme-dark .card.admin-card-bordered {
            border: 1px solid rgba(255, 255, 255, 0.12) !important; /* Thin subtle dark mode border */
            background-color: #1e293b !important;
        }

        [data-bs-theme="dark"] .hero-header-card,
        [data-theme="dark"] .hero-header-card,
        body.theme-dark .hero-header-card,
        [data-bs-theme="dark"] .card,
        [data-theme="dark"] .card,
        body.theme-dark .card {
            background-color: #1e293b !important;
            color: #f8fafc !important;
        }

        [data-bs-theme="dark"] .quick-filter-box,
        [data-theme="dark"] .quick-filter-box,
        body.theme-dark .quick-filter-box {
            background-color: #0f172a !important;
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
        [data-bs-theme="dark"] .admin-name,
        [data-theme="dark"] .admin-name,
        body.theme-dark .admin-name {
            color: #f8fafc !important;
        }

        [data-bs-theme="dark"] .header-subtitle,
        [data-theme="dark"] .header-subtitle,
        body.theme-dark .header-subtitle {
            color: #94a3b8 !important;
        }

        [data-bs-theme="dark"] .form-control,
        [data-theme="dark"] .form-control,
        body.theme-dark .form-control,
        [data-bs-theme="dark"] .form-select,
        [data-theme="dark"] .form-select,
        body.theme-dark .form-select {
            background-color: #0f172a !important;
            border-color: #334155 !important;
            color: #f8fafc !important;
        }

        [data-bs-theme="dark"] .form-control::placeholder,
        [data-theme="dark"] .form-control::placeholder,
        body.theme-dark .form-control::placeholder {
            color: #64748b !important;
        }

        [data-bs-theme="dark"] .table-modern thead th,
        [data-theme="dark"] .table-modern thead th,
        body.theme-dark .table-modern thead th {
            background: #0f172a !important;
            color: #94a3b8 !important;
            border-color: #334155 !important;
        }

        [data-bs-theme="dark"] .table-modern tbody td,
        [data-theme="dark"] .table-modern tbody td,
        body.theme-dark .table-modern tbody td {
            border-color: #334155 !important;
            color: #f8fafc !important;
        }

        [data-bs-theme="dark"] .table-modern tbody tr:hover,
        [data-theme="dark"] .table-modern tbody tr:hover,
        body.theme-dark .table-modern tbody tr:hover {
            background-color: rgba(255, 255, 255, 0.03) !important;
        }

        [data-bs-theme="dark"] .modal-content,
        [data-theme="dark"] .modal-content,
        body.theme-dark .modal-content {
            background-color: #1e293b !important;
            color: #f8fafc !important;
            border-color: #334155 !important;
        }

        [data-bs-theme="dark"] .modal-header,
        [data-theme="dark"] .modal-header,
        body.theme-dark .modal-header,
        [data-bs-theme="dark"] .modal-footer,
        [data-theme="dark"] .modal-footer,
        body.theme-dark .modal-footer {
            border-color: #334155 !important;
        }

        [data-bs-theme="dark"] .admin-avatar-box,
        [data-theme="dark"] .admin-avatar-box,
        body.theme-dark .admin-avatar-box {
            background: #312e81 !important;
            color: #c7d2fe !important;
        }

        /* Hide default DataTables top search and length inputs */
        .dataTables_wrapper .dataTables_length,
        .dataTables_wrapper .dataTables_filter {
            display: none;
        }
    </style>
@endsection

@section('script')
    <script src="{{ asset('js/jquery.dataTables.min.js') }}"></script>
    <script src="{{ asset('js/dataTables.bootstrap5.min.js') }}"></script>
    <script src="{{ asset('js/bootstrap-toggle.min.js') }}"></script>
    <script>
        $(document).ready(function() {
            @if(count($admin) > 0)
                let table = $("#MyTable").DataTable({
                    "dom": "t<'d-flex align-items-center justify-content-between p-3'ip>",
                    "pageLength": 25,
                    "autoWidth": false,
                    "ordering": true,
                    "language": {
                        "emptyTable": "No admin data found",
                        "zeroRecords": "No matching admin found",
                        "info": "Showing _START_ to _END_ of _TOTAL_ admins",
                        "infoEmpty": "Showing 0 to 0 of 0 admins",
                        "infoFiltered": "(filtered from _MAX_ total admins)"
                    }
                });

                // Entries dropdown hook
                $('#entries-select').on('change', function() {
                    let len = parseInt($(this).val());
                    table.page.len(len).draw();
                });

                // Search input keyup (Enter) or input
                $('#admin-search-input').on('keyup', function(e) {
                    if (e.key === 'Enter') {
                        table.search(this.value).draw();
                    }
                });

                // Search submit button
                $('#btn-admin-search').on('click', function() {
                    table.search($('#admin-search-input').val()).draw();
                });

                // Reset button
                $('#btn-admin-reset').on('click', function() {
                    $('#admin-search-input').val('');
                    $('#modal-filter-q').val('');
                    $('#entries-select').val(25);
                    table.page.len(25).search('').draw();
                });

                // More Filter modal apply
                $('#btn-apply-modal-filter').on('click', function() {
                    let query = $('#modal-filter-q').val();
                    $('#admin-search-input').val(query);
                    table.search(query).draw();
                    let modalEl = document.getElementById('moreFilterModal');
                    if (modalEl) {
                        if (typeof bootstrap !== 'undefined' && bootstrap.Modal) {
                            let modal = bootstrap.Modal.getInstance(modalEl);
                            if (modal) modal.hide();
                        }
                        $(modalEl).modal('hide');
                        $('.modal-backdrop').remove();
                        $('body').removeClass('modal-open').css('overflow', '');
                    }
                });
            @endif
        });
    </script>
@endsection
