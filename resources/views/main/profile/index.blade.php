@extends('layouts.sidebar-master')

@php
    $totalLearning = count($content);
    $totalProgress = 0;
    $totalOngoing = 0;
    $totalFinished = 0;

    foreach ($content as $item) {
        $totalProgress += (int) ($item->progress ?? 0);

        if ((int) ($item->finish ?? 0) === 1) {
            $totalFinished++;
        } else {
            $totalOngoing++;
        }
    }

    $percentage = $totalLearning > 0
        ? round($totalProgress / $totalLearning, 2)
        : 0;
@endphp

@section('content')
<div class="page-body">
    <div class="container-xl">

        @include('main.profile.components.profile-hero')

        @include('main.profile.components.profile-stats')

        @include('main.profile.components.profile-table')

        @include('main.profile.components.profile-more-filters-modal')

    </div>
</div>
@endsection

@section('style')
<link rel="stylesheet" href="{{ asset('css/dataTables.bootstrap5.min.css') }}">

<style>
    .rounded-4 {
        border-radius: 20px !important;
    }

    #MyTable {
        font-size: 13px;
    }

    #MyTable thead th {
        background: #f8fafc;
        color: #475569;
        font-size: 12px;
        text-transform: uppercase;
        letter-spacing: .04em;
        white-space: nowrap;
    }

    #MyTable tbody td {
        vertical-align: middle;
        padding-top: 14px;
        padding-bottom: 14px;
    }

    .avatar-xl {
        width: 96px;
        height: 96px;
        font-size: 32px;
    }

    .profile-hero-card {
        background:
            radial-gradient(circle at top right, rgba(32, 107, 196, .12), transparent 35%),
            linear-gradient(135deg, rgba(32, 107, 196, .05), rgba(255, 255, 255, 1));
    }

    .avatar-2xl {
        width: 120px !important;
        height: 120px !important;
        font-size: 46px !important;
    }

    .profile-progress-box {
        min-width: 260px;
        padding: 16px;
        border-radius: 18px;
        background: rgba(255, 255, 255, .75);
        border: 1px solid rgba(226, 232, 240, .9);
    }

    .profile-summary-card {
        border-radius: 16px !important;
        transition: all .2s ease;
    }

    .profile-summary-card:hover {
        transform: translateY(-2px);
        box-shadow: 0 10px 24px rgba(15, 23, 42, .10) !important;
    }

    .min-w-0 {
        min-width: 0;
    }

    @media (max-width: 991.98px) {
        .profile-progress-box {
            min-width: 100%;
        }

        .avatar-2xl {
            width: 96px !important;
            height: 96px !important;
            font-size: 36px !important;
        }
    }

    /* ── Quick Filter Flex Toolbar Layout (Uniform with Manage Learning) ── */
    .quick-filter-toolbar {
        display: flex;
        align-items: center;
        gap: 8px;
        width: 100%;
    }

    @media (min-width: 1200px) {
        .quick-filter-toolbar {
            flex-wrap: nowrap !important;
        }
    }

    @media (max-width: 1199.98px) {
        .quick-filter-toolbar {
            flex-wrap: wrap;
        }
    }

    .quick-filter-title {
        flex: 1 1 auto;
        min-width: 180px;
    }

    .quick-filter-classification {
        flex: 0 0 160px;
        width: 160px;
        min-width: 140px;
    }

    .quick-filter-status {
        flex: 0 0 115px;
        width: 115px;
        min-width: 105px;
    }

    .quick-filter-type {
        flex: 0 0 110px;
        width: 110px;
        min-width: 100px;
    }

    .quick-filter-year {
        flex: 0 0 95px;
        width: 95px;
        min-width: 85px;
    }

    .quick-filter-select {
        padding-left: 0.5rem !important;
        padding-right: 1.5rem !important;
        font-size: 0.85rem !important;
        height: 36px !important;
    }

    .quick-filter-action-btn {
        width: 36px !important;
        height: 36px !important;
        min-width: 36px !important;
        padding: 0 !important;
        display: inline-flex !important;
        align-items: center !important;
        justify-content: center !important;
        flex-shrink: 0 !important;
    }

    .quick-filter-download {
        flex-shrink: 0 !important;
    }

    .quick-filter-download .btn-download {
        height: 36px !important;
        display: inline-flex !important;
        align-items: center !important;
        justify-content: center !important;
    }

    .quick-filter-toolbar .form-control {
        height: 36px !important;
        font-size: 0.875rem !important;
    }

    .btn-download {
        background-color: #2fb344 !important;
        border-color: #2fb344 !important;
        color: #ffffff !important;
        transition: all 0.2s ease;
    }
    .btn-download:hover,
    .btn-download:focus,
    .btn-download:active {
        background-color: #269639 !important;
        border-color: #269639 !important;
        color: #ffffff !important;
    }
    .btn-download:hover i,
    .btn-download:focus i,
    .btn-download:active i {
        color: #ffffff !important;
    }

    .btn-more-filters,
    #btn-profile-more-filters {
        border-color: #0c5b9e !important;
        color: #0c5b9e !important;
        background-color: transparent !important;
        transition: all 0.2s ease;
    }
    .btn-more-filters:hover,
    .btn-more-filters:focus,
    .btn-more-filters:active,
    #btn-profile-more-filters:hover,
    #btn-profile-more-filters:focus,
    #btn-profile-more-filters:active {
        background-color: #0c5b9e !important;
        color: #ffffff !important;
        border-color: #0c5b9e !important;
    }
    .btn-more-filters:hover i,
    .btn-more-filters:focus i,
    .btn-more-filters:active i,
    #btn-profile-more-filters:hover i,
    #btn-profile-more-filters:focus i,
    #btn-profile-more-filters:active i {
        color: #ffffff !important;
    }

    .btn-quick-reset {
        transition: all 0.2s ease;
    }
    .btn-quick-reset:hover {
        background-color: #0c5b9e !important;
        color: #ffffff !important;
    }

    /* Hide redundant default DataTables Search Box */
    #MyTable_filter {
        display: none !important;
    }

    /* Modal Styling */
    .modal-backdrop {
        z-index: 1050 !important;
    }
    #profileMoreFiltersModal {
        z-index: 1060 !important;
    }

    /* Dark Mode */
    [data-bs-theme="dark"] #MyTable thead th,
    body.dark-mode #MyTable thead th,
    body.theme-dark #MyTable thead th {
        background: var(--tblr-bg-surface-secondary, #1a2942);
        color: #94a3b8;
        border-color: rgba(255, 255, 255, 0.1);
    }
    [data-bs-theme="dark"] .btn-quick-reset,
    body.dark-mode .btn-quick-reset,
    body.theme-dark .btn-quick-reset {
        background-color: transparent !important;
    }
    [data-bs-theme="dark"] .btn-more-filters,
    [data-bs-theme="dark"] #btn-profile-more-filters,
    body.dark-mode .btn-more-filters,
    body.dark-mode #btn-profile-more-filters,
    body.theme-dark .btn-more-filters,
    body.theme-dark #btn-profile-more-filters {
        background-color: transparent !important;
        border-color: #3b82f6 !important;
        color: #60a5fa !important;
    }
</style>
@endsection

@section('script')
<script src="{{ asset('js/jquery.dataTables.min.js') }}"></script>
<script src="{{ asset('js/dataTables.bootstrap5.min.js') }}"></script>

<script>
    $(function () {
        // Initialize tooltips
        if (typeof bootstrap !== 'undefined' && bootstrap.Tooltip) {
            const tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
            tooltipTriggerList.map(function (tooltipTriggerEl) {
                return new bootstrap.Tooltip(tooltipTriggerEl);
            });
        }

        const datatableUrl = "{{ route('user.manage.profile.learning.datatable', $profile->ssohash) }}";
        const exportExcelUrl = "{{ route('user.manage.profile.export.excel', $profile->ssohash) }}";
        const exportCsvUrl = "{{ route('user.manage.profile.export.csv', $profile->ssohash) }}";
        const exportPdfUrl = "{{ route('user.manage.profile.export.pdf', $profile->ssohash) }}";

        // Initialize DataTable with server-side AJAX
        let historyTable = $('#MyTable').DataTable({
            processing: true,
            serverSide: true,
            responsive: true,
            autoWidth: false,
            pageLength: 25,
            lengthMenu: [
                [10, 25, 50, 100],
                [10, 25, 50, 100]
            ],
            order: [[6, 'desc']], // Sort by date descending
            dom: "<'row p-3 align-items-center'<'col-sm-12 col-md-6'l><'col-sm-12 col-md-6 text-end'>>" +
                 "<'row'<'col-12'tr>>" +
                 "<'row p-3 align-items-center'<'col-sm-12 col-md-5'i><'col-sm-12 col-md-7 text-end'p>>",
            ajax: {
                url: datatableUrl,
                type: 'GET',
                data: function (d) {
                    d.learning_title = $('#profile-title-input').val() || '';
                    d.classification = $('#profile-classification-select').val() || '';
                    d.status = $('#profile-status-select').val() || '';
                    d.type = $('#profile-type-select').val() || '';
                    d.year = $('#profile-year-select').val() || '';
                    d.from_date = $('#profile-filter-from-date').val() || '';
                    d.to_date = $('#profile-filter-to-date').val() || '';
                    d.progress_range = $('#profile-filter-progress-range').val() || '';
                    d.duration = $('#profile-filter-duration').val() || '';
                },
                error: function (xhr, error, thrown) {
                    console.error('DataTables AJAX error:', xhr.status, thrown, xhr.responseText);
                    $('#MyTable_processing').hide();
                }
            },
            columns: [
                { data: 'DT_RowIndex', name: 'DT_RowIndex', orderable: false, searchable: false, className: 'text-muted text-center' },
                { data: 'status_badge', name: 'content_users.finish', orderable: true, searchable: false, className: 'text-center' },
                { data: 'user_name', name: 'user.name', orderable: true, searchable: false },
                { data: 'title_html', name: 'content.title', orderable: true, searchable: true },
                { data: 'classification_html', name: 'content.classification.classification_name', orderable: true, searchable: true },
                { data: 'duration_formatted', name: 'content.duration', orderable: true, searchable: false, className: 'text-center' },
                { data: 'date_formatted', name: 'content_users.created_at', orderable: true, searchable: false, className: 'text-center' },
                { data: 'progress_html', name: 'content_users.progress', orderable: true, searchable: false, width: '15%' }
            ],
            language: {
                processing: '<div class="py-3 text-center text-muted"><div class="spinner-border spinner-border-sm text-primary me-2"></div>Loading learning history...</div>',
                emptyTable: '<div class="text-center p-4 text-muted"><i class="ti ti-notebook-off fs-1 mb-2 d-block"></i>No learning history records found</div>'
            }
        });

        // ── Helper: Calculate active More Filters badge ────────
        function updateMoreFilterBadge() {
            let activeCount = 0;
            const fromDate = $('#profile-filter-from-date').val();
            const toDate = $('#profile-filter-to-date').val();
            const pr = $('#profile-filter-progress-range').val();
            const dur = $('#profile-filter-duration').val();

            if (fromDate || toDate) activeCount++;
            if (pr && pr !== 'all') activeCount++;
            if (dur && dur !== 'all') activeCount++;

            const $badge = $('#profileActiveFilterBadge');
            if (activeCount > 0) {
                $badge.text(activeCount).removeClass('d-none');
            } else {
                $badge.text(0).addClass('d-none');
            }
        }

        // ── Trigger Filter Reload ─────────────────────────────
        $('#btn-profile-apply-filter').on('click', function () {
            historyTable.ajax.reload();
        });

        $('#profile-title-input').on('keyup', function (e) {
            if (e.key === 'Enter') {
                historyTable.ajax.reload();
            }
        });

        $('#profile-classification-select, #profile-status-select, #profile-type-select, #profile-year-select').on('change', function () {
            historyTable.ajax.reload();
        });

        // ── Reset All Filters ─────────────────────────────────
        $('#btn-profile-reset-filter').on('click', function () {
            $('#profile-title-input').val('');
            $('#profile-classification-select').val('all');
            $('#profile-status-select').val('all');
            $('#profile-type-select').val('all');
            $('#profile-year-select').val('all');

            // Reset modal values
            $('#profile-modal-from-date').val('');
            $('#profile-modal-to-date').val('');
            $('input[name="profile_modal_progress_range"][value="all"]').prop('checked', true);
            $('input[name="profile_modal_duration"][value="all"]').prop('checked', true);

            // Reset hidden values
            $('#profile-filter-from-date').val('');
            $('#profile-filter-to-date').val('');
            $('#profile-filter-progress-range').val('all');
            $('#profile-filter-duration').val('all');

            updateMoreFilterBadge();
            historyTable.ajax.reload();
        });

        // ── More Filters Modal Apply ──────────────────────────
        $('#btn-profile-modal-apply').on('click', function () {
            $('#profile-filter-from-date').val($('#profile-modal-from-date').val() || '');
            $('#profile-filter-to-date').val($('#profile-modal-to-date').val() || '');
            $('#profile-filter-progress-range').val($('input[name="profile_modal_progress_range"]:checked').val() || 'all');
            $('#profile-filter-duration').val($('input[name="profile_modal_duration"]:checked').val() || 'all');

            updateMoreFilterBadge();
            historyTable.ajax.reload();
        });

        // ── More Filters Modal Reset ──────────────────────────
        $('#btn-profile-modal-reset').on('click', function () {
            $('#profile-modal-from-date').val('');
            $('#profile-modal-to-date').val('');
            $('input[name="profile_modal_progress_range"][value="all"]').prop('checked', true);
            $('input[name="profile_modal_duration"][value="all"]').prop('checked', true);

            $('#profile-filter-from-date').val('');
            $('#profile-filter-to-date').val('');
            $('#profile-filter-progress-range').val('all');
            $('#profile-filter-duration').val('all');

            updateMoreFilterBadge();
            historyTable.ajax.reload();
        });

        // ── Export Query Collector Helper ─────────────────────
        function collectProfileFilterParams() {
            const params = new URLSearchParams();

            const title = $('#profile-title-input').val();
            if (title) params.append('learning_title', title);

            const cl = $('#profile-classification-select').val();
            if (cl && cl !== 'all') params.append('classification', cl);

            const st = $('#profile-status-select').val();
            if (st && st !== 'all') params.append('status', st);

            const tp = $('#profile-type-select').val();
            if (tp && tp !== 'all') params.append('type', tp);

            const yr = $('#profile-year-select').val();
            if (yr && yr !== 'all') params.append('year', yr);

            const fromDate = $('#profile-filter-from-date').val();
            if (fromDate) params.append('from_date', fromDate);

            const toDate = $('#profile-filter-to-date').val();
            if (toDate) params.append('to_date', toDate);

            const pr = $('#profile-filter-progress-range').val();
            if (pr && pr !== 'all') params.append('progress_range', pr);

            const dur = $('#profile-filter-duration').val();
            if (dur && dur !== 'all') params.append('duration', dur);

            return params.toString();
        }

        // ── Export Triggers ───────────────────────────────────
        $('#btn-profile-download-excel').on('click', function () {
            const query = collectProfileFilterParams();
            window.location.href = exportExcelUrl + (query ? '?' + query : '');
        });

        $('#btn-profile-download-csv').on('click', function () {
            const query = collectProfileFilterParams();
            window.location.href = exportCsvUrl + (query ? '?' + query : '');
        });

        $('#btn-profile-download-pdf').on('click', function () {
            const query = collectProfileFilterParams();
            window.location.href = exportPdfUrl + (query ? '?' + query : '');
        });
    });
</script>
@endsection