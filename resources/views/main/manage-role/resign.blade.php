@extends('layouts.sidebar-master')

@section('content')

    {{-- Reuse design system (owl-* cards/table + dark mode) --}}
    @include('main.monitoring.components.style')
    @include('main.monitoring.components.datatable-assets')

    {{-- Page Header --}}
    <div class="page-header d-print-none mb-0 pb-0">
        <div class="container-xl">
            <!-- Back Navigation Link -->
            <a href="{{ route('manage.dashboard') }}" class="btn btn-link p-0 mb-3 text-decoration-none d-inline-flex align-items-center gap-1 fw-semibold text-primary">
                <i class="ti ti-arrow-left fs-2"></i> Back
            </a>

            <!-- Resign Management Hero Header Card -->
            <div class="card border border-light-subtle shadow-sm p-4 mb-3 rounded-3 bg-white hero-header-card">
                <div class="d-flex flex-column flex-md-row align-items-md-center justify-content-between gap-3">
                    <div class="d-flex align-items-center gap-3">
                        <div class="rounded-3 d-flex align-items-center justify-content-center flex-shrink-0 hero-icon-box"
                             style="width: 72px; height: 72px; background-color: #fee2e2; color: #dc2626;">
                            <i class="ti ti-user-off" style="font-size: 2rem !important;"></i>
                        </div>
                        <div>
                            <div class="d-flex align-items-center gap-2 mb-1">
                                <h2 class="h3 fw-bold mb-0 text-dark header-title">Resign Management</h2>
                                <span class="badge bg-danger text-white rounded-pill px-2 py-1 small">Soft Delete Monitoring</span>
                            </div>
                            <p class="text-muted small mb-0 header-subtitle">
                                Monitoring dan pencatatan seluruh data karyawan yang telah mengalami Soft Delete pada tabel <code>users</code> secara otomatis.
                            </p>
                        </div>
                    </div>

                    <!-- Right Header Info / Status -->
                    <div class="d-flex align-items-center gap-2 flex-shrink-0">
                        <span class="badge bg-danger-lt text-danger fs-4 py-2 px-3 rounded-3 d-inline-flex align-items-center gap-2 border border-danger-subtle shadow-sm">
                            <i class="ti ti-shield-check fs-2"></i>
                            <span class="fw-semibold">Read-Only Monitoring</span>
                        </span>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="page-body mt-0 pt-0">
        <div class="container-xl">

            <x-alert type="success" />
            <x-alert type="warning" />

            {{-- Role Navigation Tabs (Active vs Resigned) --}}
            @include('main.manage-role.components.role-nav-tabs')

            {{-- Resign Stat Cards --}}
            @include('main.manage-role.components.resign-stat-cards')

            {{-- Resign Filter Card --}}
            @include('main.manage-role.components.resign-filter-card')

            {{-- Resign Table Card --}}
            @include('main.manage-role.components.resign-table')

            {{-- Resign Read-Only Detail Modal --}}
            @include('main.manage-role.components.resign-detail-modal')

        </div>
    </div>

<style>
    /* Hero header & Card styling */
    .hero-header-card {
        transition: background-color 0.2s ease, border-color 0.2s ease;
    }

    .filter-label-head {
        color: #1e293b;
    }

    .emp-name {
        font-size: 0.95rem;
        line-height: 1.25;
    }

    .emp-email {
        font-size: 0.8rem;
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
    body.theme-dark .role-table-card,
    [data-bs-theme="dark"] .role-nav-tabs-card,
    [data-theme="dark"] .role-nav-tabs-card,
    body.theme-dark .role-nav-tabs-card {
        background-color: #1e293b !important;
        border: 1px solid rgba(255, 255, 255, 0.12) !important;
        color: #f8fafc !important;
    }

    [data-bs-theme="dark"] .header-title,
    [data-theme="dark"] .header-title,
    body.theme-dark .header-title,
    [data-bs-theme="dark"] .card-title,
    [data-theme="dark"] .card-title,
    body.theme-dark .card-title,
    [data-bs-theme="dark"] .filter-label-head,
    [data-theme="dark"] .filter-label-head,
    body.theme-dark .filter-label-head,
    [data-bs-theme="dark"] .emp-name,
    [data-theme="dark"] .emp-name,
    body.theme-dark .emp-name {
        color: #f8fafc !important;
    }

    [data-bs-theme="dark"] .header-subtitle,
    [data-theme="dark"] .header-subtitle,
    body.theme-dark .header-subtitle {
        color: #94a3b8 !important;
    }

    [data-bs-theme="dark"] #moreResignFiltersModal .modal-content,
    [data-theme="dark"] #moreResignFiltersModal .modal-content,
    body.theme-dark #moreResignFiltersModal .modal-content,
    [data-bs-theme="dark"] #resignDetailModal .modal-content,
    [data-theme="dark"] #resignDetailModal .modal-content,
    body.theme-dark #resignDetailModal .modal-content {
        background-color: var(--tblr-bg-surface, #1e293b) !important;
        color: #f8fafc !important;
        border: 1px solid rgba(255, 255, 255, 0.12) !important;
    }

    [data-bs-theme="dark"] #moreResignFiltersModal .modal-footer,
    [data-theme="dark"] #moreResignFiltersModal .modal-footer,
    body.theme-dark #moreResignFiltersModal .modal-footer,
    [data-bs-theme="dark"] #resignDetailModal .modal-footer,
    [data-theme="dark"] #resignDetailModal .modal-footer,
    body.theme-dark #resignDetailModal .modal-footer {
        background-color: #0f172a !important;
        border-color: rgba(255, 255, 255, 0.1) !important;
    }

    [data-bs-theme="dark"] #resignDetailModal .card,
    [data-theme="dark"] #resignDetailModal .card,
    body.theme-dark #resignDetailModal .card {
        background-color: #0f172a !important;
        border-color: rgba(255, 255, 255, 0.1) !important;
        color: #f8fafc !important;
    }

    [data-bs-theme="dark"] #resignDetailModal td,
    [data-theme="dark"] #resignDetailModal td,
    body.theme-dark #resignDetailModal td {
        color: #f8fafc !important;
    }

    [data-bs-theme="dark"] #resignDetailModal .text-dark,
    [data-theme="dark"] #resignDetailModal .text-dark,
    body.theme-dark #resignDetailModal .text-dark {
        color: #f8fafc !important;
    }

    [data-bs-theme="dark"] #moreResignFiltersModal .text-dark,
    [data-theme="dark"] #moreResignFiltersModal .text-dark,
    body.theme-dark #moreResignFiltersModal .text-dark {
        color: #f8fafc !important;
    }

    [data-bs-theme="dark"] #resign-users-table,
    [data-theme="dark"] #resign-users-table,
    body.theme-dark #resign-users-table {
        color: #f8fafc !important;
    }

    [data-bs-theme="dark"] #resign-users-table tbody tr:hover,
    [data-theme="dark"] #resign-users-table tbody tr:hover,
    body.theme-dark #resign-users-table tbody tr:hover {
        background-color: rgba(255, 255, 255, 0.04) !important;
    }

    [data-bs-theme="dark"] .dataTables_info,
    [data-theme="dark"] .dataTables_info,
    body.theme-dark .dataTables_info {
        color: #94a3b8 !important;
    }

    [data-bs-theme="dark"] .page-link,
    [data-theme="dark"] .page-link,
    body.theme-dark .page-link {
        background-color: #1e293b !important;
        border-color: rgba(255, 255, 255, 0.12) !important;
        color: #f8fafc !important;
    }

    [data-bs-theme="dark"] .page-item.active .page-link,
    [data-theme="dark"] .page-item.active .page-link,
    body.theme-dark .page-item.active .page-link {
        background-color: #003b6d !important;
        border-color: #003b6d !important;
        color: #ffffff !important;
    }

    [data-bs-theme="dark"] .page-item.disabled .page-link,
    [data-theme="dark"] .page-item.disabled .page-link,
    body.theme-dark .page-item.disabled .page-link {
        color: #64748b !important;
        background-color: #0f172a !important;
    }
</style>

@push('scripts')
<script>
    $(function () {
        let searchTimer;

        // Initialize DataTables
        const table = $('#resign-users-table').DataTable({
            processing: true,
            serverSide: true,
            responsive: true,
            autoWidth: false,
            pageLength: 15,
            lengthMenu: [[10, 15, 25, 50, 100], [10, 15, 25, 50, 100]],
            // Order by Resign Date (Column Index 11) descending by default
            order: [[11, 'desc']],
            dom: "<'row'<'col-sm-12'tr>><'row align-items-center mt-3 px-3 pb-3'<'col-sm-12 col-md-5 text-muted small'i><'col-sm-12 col-md-7 d-flex justify-content-md-end'p>>",
            ajax: {
                url: @json(route('manage.role.resign.datatable')),
                data: function (d) {
                    d.company        = $('#filter-company').val();
                    d.division       = $('#filter-division').val();
                    d.department     = $('#modal-filter-department').val();
                    d.date_from      = $('#modal-filter-date-from').val();
                    d.date_to        = $('#modal-filter-date-to').val();
                    d.search_keyword = $('#resign-search-input').val();
                },
                error: function (xhr, error, thrown) {
                    console.error('DataTables Resign AJAX Error:', thrown);
                }
            },
            columns: [
                { data: 'DT_RowIndex', name: 'DT_RowIndex', orderable: false, searchable: false, className: 'text-center text-muted' },
                { data: 'id_emp_formatted', name: 'id_emp' },
                { data: 'name_formatted', name: 'name' },
                { data: 'sap_code', name: 'sap_code' },
                { data: 'company_name', name: 'company_name' },
                { data: 'jobgrade', name: 'jobgrade' },
                { data: 'jobposition', name: 'jobposition' },
                { data: 'team', name: 'team' },
                { data: 'department', name: 'department' },
                { data: 'division', name: 'division' },
                { data: 'orgunit', name: 'orgunit' },
                { data: 'deleted_at', name: 'deleted_at' },
                { data: 'status_badge', name: 'status_badge', orderable: false, searchable: false, className: 'text-center' },
                { data: 'action', name: 'action', orderable: false, searchable: false, className: 'text-end' }
            ],
            language: {
                emptyTable: '<div class="py-5 text-center text-muted"><i class="ti ti-user-off mb-2 fs-1 text-muted"></i><h4 class="fw-bold text-dark mb-1">Tidak ada data karyawan resign</h4><p class="text-muted small mb-0">Karyawan yang mengalami soft-delete pada tabel users akan otomatis ditampilkan di sini.</p></div>',
                zeroRecords: '<div class="py-5 text-center text-muted"><i class="ti ti-search-off mb-2 fs-1 text-muted"></i><h4 class="fw-bold text-dark mb-1">Tidak ada hasil ditemukan</h4><p class="text-muted small mb-0">Coba ubah kata kunci pencarian atau sesuaikan filter yang dipilih.</p></div>',
                processing: '<div class="d-flex align-items-center justify-content-center p-3 gap-2"><div class="spinner-border spinner-border-sm text-primary" role="status"></div><span class="fw-semibold">Memuat data karyawan resign...</span></div>',
                info: 'Menampilkan _START_ sampai _END_ dari _TOTAL_ data karyawan resign',
                infoEmpty: 'Menampilkan 0 data',
                infoFiltered: '(difilter dari _MAX_ total data)'
            },
            drawCallback: function (settings) {
                // Update badge counter with filtered count if available
                const json = settings.json;
                if (json && typeof json.recordsFiltered !== 'undefined') {
                    $('#badge-total-records').text(json.recordsFiltered.toLocaleString() + ' Resigned');
                }
            }
        });

        // Update badge indicator for active modal filters (Department & Dates)
        function updateModalFilterBadge() {
            const hasDept = $('#modal-filter-department').val() !== '';
            const hasDateFrom = $('#modal-filter-date-from').val() !== '';
            const hasDateTo = $('#modal-filter-date-to').val() !== '';

            if (hasDept || hasDateFrom || hasDateTo) {
                $('#more-filters-badge').show();
            } else {
                $('#more-filters-badge').hide();
            }
        }

        // 1. Entries per page change
        $('#filter-entries').on('change', function () {
            table.page.len(parseInt(this.value, 10)).draw();
        });

        // 2. Company dropdown filter
        $('#filter-company').on('change', function () {
            table.draw();
        });

        // 3. Division dropdown filter
        $('#filter-division').on('change', function () {
            table.draw();
        });

        // 4. Live Search Input with 350ms debounce
        $('#resign-search-input').on('keyup input', function () {
            clearTimeout(searchTimer);
            searchTimer = setTimeout(function () {
                table.draw();
            }, 350);
        });

        // 5. Apply Modal Filters (Department & Resign Date)
        $('#btn-apply-modal-filters').on('click', function () {
            updateModalFilterBadge();
            table.draw();
            const modalEl = document.getElementById('moreResignFiltersModal');
            const modal = bootstrap.Modal.getInstance(modalEl);
            if (modal) {
                modal.hide();
            }
        });

        // 6. Reset Modal Filters
        $('#btn-reset-modal-filters').on('click', function () {
            $('#modal-filter-department').val('');
            $('#modal-filter-date-from').val('');
            $('#modal-filter-date-to').val('');
            updateModalFilterBadge();
            table.draw();
        });

        // 7. Reset ALL Filters (Quick filters + Modal filters)
        $('#btn-reset-all-filters').on('click', function () {
            $('#filter-company').val('');
            $('#filter-division').val('');
            $('#modal-filter-department').val('');
            $('#modal-filter-date-from').val('');
            $('#modal-filter-date-to').val('');
            $('#resign-search-input').val('');
            $('#filter-entries').val(15);
            updateModalFilterBadge();
            table.page.len(15).draw();
        });

        // 8. Refresh Table Button
        $('#btn-refresh-table').on('click', function () {
            table.ajax.reload(null, false);
        });

        // 9. Quick Date Presets Handler
        $('.btn-date-preset').on('click', function () {
            const preset = $(this).data('preset');
            const today = new Date();

            if (preset === 'all') {
                $('#modal-filter-date-from').val('');
                $('#modal-filter-date-to').val('');
            } else if (preset === '30days') {
                const past30 = new Date();
                past30.setDate(today.getDate() - 30);
                $('#modal-filter-date-from').val(past30.toISOString().split('T')[0]);
                $('#modal-filter-date-to').val(today.toISOString().split('T')[0]);
            } else if (preset === 'thisyear') {
                const year = today.getFullYear();
                $('#modal-filter-date-from').val(`${year}-01-01`);
                $('#modal-filter-date-to').val(`${year}-12-31`);
            } else if (preset === 'lastyear') {
                const lastYear = today.getFullYear() - 1;
                $('#modal-filter-date-from').val(`${lastYear}-01-01`);
                $('#modal-filter-date-to').val(`${lastYear}-12-31`);
            }
        });

        // 10. View Detail Modal Population (Read-Only)
        $(document).on('click', '.btn-view-detail', function () {
            const user = $(this).data('user');
            if (!user) return;

            // Header info
            $('#detail-modal-user-name').text(user.name || '-');
            $('#detail-modal-user-nip').text(user.id_emp || '-');
            $('#detail-modal-user-sap').text(user.sap_code || '-');

            // Personal & Account
            $('#modal-field-id-emp').text(user.id_emp || '-');
            $('#modal-field-name').text(user.name || '-');
            $('#modal-field-email').text(user.email || '-');
            $('#modal-field-sap').text(user.sap_code || '-');
            $('#modal-field-phone').text(user.cell_phone || '-');
            $('#modal-field-sex').text(user.sex || '-');
            $('#modal-field-role').text(user.role || 'User');

            // Organization
            $('#modal-field-company').text(user.company_name || '-');
            $('#modal-field-division').text(user.division || '-');
            $('#modal-field-department').text(user.department || '-');
            $('#modal-field-team').text(user.team || '-');
            $('#modal-field-orgunit').text(user.orgunit || '-');

            // Job & Position
            $('#modal-field-jobposition').text(user.jobposition || '-');
            $('#modal-field-jobgrade').text(user.jobgrade || '-');
            $('#modal-field-position-id').text(user.positionID || '-');
            $('#modal-field-job-clan').text(user.job_clan || '-');
            $('#modal-field-job-family').text(user.job_family || '-');
            $('#modal-field-appr1').text(user.appr1 || '-');
            $('#modal-field-appr2').text(user.appr2 || '-');

            // Resign & Timestamps
            $('#modal-field-resign-date').text(user.resign_date || '-');
            $('#modal-field-resign-flag').text(user.resign_flag || 'N');
            $('#modal-field-created-at').text(user.created_at || '-');
            $('#modal-field-updated-at').text(user.updated_at || '-');

            const modal = new bootstrap.Modal(document.getElementById('resignDetailModal'));
            modal.show();
        });
    });
</script>
@endpush

@endsection
