/**
 * Import History Management Script — OWL LMS
 */
(function ($) {
    'use strict';

    let batchesTable = null;
    let rowsTable = null;
    let activeBatchUuid = null;

    $(document).ready(function () {
        initBatchesTable();
        initFilterEvents();
        initDetailModalEvents();
    });

    /**
     * Initialize Batches List Server-Side DataTable
     */
    function initBatchesTable() {
        const tableEl = $('#import-batches-table');
        if (!tableEl.length) return;

        batchesTable = tableEl.DataTable({
            processing: true,
            serverSide: true,
            responsive: true,
            pageLength: 10,
            lengthMenu: [[10, 25, 50, 100], [10, 25, 50, 100]],
            dom: '<"top">rt<"bottom d-flex flex-column flex-md-row justify-content-between align-items-center mt-3 gap-2"l ip><"clear">',
            searchDelay: 500,
            ajax: {
                url: window.importHistoryConfig.routes.datatable,
                type: 'GET',
                data: function (d) {
                    d.source_type = $('#filter-source-type').val();
                    d.status = $('#filter-status').val();
                    d.user_id = $('#filter-user').val();
                    d.date_start = $('#filter-date-start').val();
                    d.date_end = $('#filter-date-end').val();
                    d.search_query = $('#filter-search').val();
                },
                error: function (xhr) {
                    console.error('Failed to load batch list', xhr);
                }
            },
            columns: [
                { data: 'DT_RowIndex', name: 'id', orderable: false, searchable: false, width: '40px', className: 'text-center' },
                {
                    data: 'batch_uuid',
                    name: 'batch_uuid',
                    render: function (data, type, row) {
                        const short = data ? data.substring(0, 8) + '...' : '-';
                        return `<a href="javascript:void(0)" class="fw-bold text-decoration-none btn-batch-detail" data-uuid="${data}" title="Click to view detail (${data})">${short}</a>`;
                    }
                },
                { data: 'source_badge', name: 'source_type', className: 'text-center' },
                {
                    data: 'original_filename',
                    name: 'original_filename',
                    render: function (data) {
                        const safe = data ? $('<div>').text(data).html() : '-';
                        return `<span class="filename-truncate" title="${safe}"><i class="ti ti-file-text text-muted me-1"></i>${safe}</span>`;
                    }
                },
                { data: 'user_name', name: 'user_name' },
                { data: 'started_at_formatted', name: 'started_at', className: 'text-nowrap small text-muted' },
                { data: 'finished_at_formatted', name: 'finished_at', className: 'text-nowrap small text-muted' },
                { data: 'total_rows', name: 'total_rows', className: 'text-center fw-bold' },
                {
                    data: 'imported_rows',
                    name: 'imported_rows',
                    className: 'text-center',
                    render: function (data) {
                        return `<span class="text-success fw-bold">${data || 0}</span>`;
                    }
                },
                {
                    data: 'skipped_rows',
                    name: 'skipped_rows',
                    className: 'text-center',
                    render: function (data) {
                        return `<span class="text-muted">${data || 0}</span>`;
                    }
                },
                {
                    data: 'failed_rows',
                    name: 'failed_rows',
                    className: 'text-center',
                    render: function (data) {
                        return data > 0 ? `<span class="text-danger fw-bold">${data}</span>` : `<span class="text-muted">0</span>`;
                    }
                },
                { data: 'status_badge', name: 'status', className: 'text-center' },
                { data: 'actions', name: 'actions', orderable: false, searchable: false, className: 'text-center' }
            ],
            order: [[5, 'desc']],
            language: {
                emptyTable: '<div class="py-4 text-center text-muted"><i class="ti ti-inbox fs-1 d-block mb-2"></i>No import history records found.</div>',
                processing: '<div class="spinner-border spinner-border-sm text-primary me-2"></div> Loading import records...'
            }
        });
    }

    /**
     * Setup Filters & Summary Refresh
     */
    function initFilterEvents() {
        $('#btn-apply-filters').on('click', function (e) {
            e.preventDefault();
            if (batchesTable) batchesTable.ajax.reload();
            refreshSummaryCards();
        });

        $('#btn-reset-filters').on('click', function (e) {
            e.preventDefault();
            $('#filter-source-type').val('');
            $('#filter-status').val('');
            $('#filter-user').val('');
            $('#filter-date-start').val('');
            $('#filter-date-end').val('');
            $('#filter-search').val('');
            if (batchesTable) batchesTable.ajax.reload();
            refreshSummaryCards();
        });

        $('#filter-source-type, #filter-status, #filter-user, #filter-date-start, #filter-date-end').on('change', function () {
            if (batchesTable) batchesTable.ajax.reload();
        });

        $('#filter-search').on('keyup', function (e) {
            if (e.key === 'Enter' && batchesTable) {
                batchesTable.ajax.reload();
            }
        });
    }

    function refreshSummaryCards() {
        $.ajax({
            url: window.importHistoryConfig.routes.summary,
            type: 'GET',
            dataType: 'json',
            success: function (data) {
                $('#card-total-batches').text(Number(data.total_batches || 0).toLocaleString());
                $('#card-completed-batches').text(Number(data.completed_batches || 0).toLocaleString());
                $('#card-warning-batches').text(Number(data.warning_batches || 0).toLocaleString());
                $('#card-failed-batches').text(Number(data.failed_batches || 0).toLocaleString());
            }
        });
    }

    /**
     * Batch Detail Modal & Rows Datatable
     */
    function initDetailModalEvents() {
        $(document).on('click', '.btn-batch-detail', function (e) {
            e.preventDefault();
            const uuid = $(this).data('uuid');
            if (!uuid) return;

            openBatchDetailModal(uuid);
        });

        $('#row-filter-action, #row-filter-status').on('change', function () {
            if (rowsTable) rowsTable.ajax.reload();
        });

        $('#row-filter-search').on('keyup', function (e) {
            if (e.key === 'Enter' && rowsTable) {
                rowsTable.ajax.reload();
            }
        });

        $('#btn-row-filter-apply').on('click', function (e) {
            e.preventDefault();
            if (rowsTable) rowsTable.ajax.reload();
        });

        $('#btn-row-filter-reset').on('click', function (e) {
            e.preventDefault();
            $('#row-filter-action').val('');
            $('#row-filter-status').val('');
            $('#row-filter-search').val('');
            if (rowsTable) rowsTable.ajax.reload();
        });
    }

    function openBatchDetailModal(uuid) {
        activeBatchUuid = uuid;
        const modalEl = document.getElementById('importDetailModal');
        if (!modalEl) return;

        const modal = bootstrap.Modal.getOrCreateInstance(modalEl);
        modal.show();

        $('#modal-loading-spinner').show();
        $('#modal-detail-content').hide();

        const detailUrl = window.importHistoryConfig.routes.detail.replace('__UUID__', uuid);

        $.ajax({
            url: detailUrl,
            type: 'GET',
            dataType: 'json',
            success: function (data) {
                populateModalDetails(data);
                $('#modal-loading-spinner').hide();
                $('#modal-detail-content').show();
                initRowsTable(uuid);
            },
            error: function (xhr) {
                $('#modal-loading-spinner').hide();
                alert(xhr.responseJSON?.error || 'Failed to fetch batch details.');
            }
        });
    }

    function populateModalDetails(data) {
        $('#detail-uuid').text(data.batch_uuid);
        $('#detail-source').html(
            data.source_type === 'PTKP'
                ? '<span class="badge badge-outline text-primary border-primary">PTKP</span>'
                : '<span class="badge badge-outline text-warning border-warning">Non PTKP</span>'
        );
        $('#detail-filename').text(data.original_filename);
        $('#detail-user').text(data.imported_by?.name || data.imported_by?.username || '-');
        $('#detail-started').text(data.started_at || '-');
        $('#detail-finished').text(data.finished_at || '-');
        $('#detail-duration').text(data.duration || '-');

        // Status badge
        let statusHtml = '';
        if (data.status === 'COMPLETED') {
            statusHtml = '<span class="badge bg-success-lt text-success fw-bold px-2.5 py-1"><i class="ti ti-check me-1"></i>Completed</span>';
        } else if (data.status === 'COMPLETED_WITH_WARNINGS') {
            statusHtml = '<span class="badge bg-warning-lt text-warning fw-bold px-2.5 py-1"><i class="ti ti-alert-triangle me-1"></i>Completed with Warnings</span>';
        } else if (data.status === 'FAILED') {
            statusHtml = '<span class="badge bg-danger-lt text-danger fw-bold px-2.5 py-1"><i class="ti ti-x me-1"></i>Failed</span>';
        } else {
            statusHtml = `<span class="badge bg-info-lt text-info fw-bold px-2.5 py-1">${data.status}</span>`;
        }
        $('#detail-status').html(statusHtml);

        // Counters
        const c = data.counters || {};
        $('#stat-total-rows').text(c.total_rows || 0);
        $('#stat-valid-rows').text(c.valid_rows || 0);
        $('#stat-warning-rows').text(c.warning_rows || 0);
        $('#stat-duplicate-rows').text(c.duplicate_rows || 0);
        $('#stat-invalid-rows').text(c.invalid_rows || 0);
        $('#stat-imported-rows').text(c.imported_rows || 0);
        $('#stat-skipped-rows').text(c.skipped_rows || 0);
        $('#stat-failed-rows').text(c.failed_rows || 0);
        $('#stat-trainings-created').text(c.trainings_created || 0);
        $('#stat-trainings-reused').text(c.trainings_reused || 0);

        // Error Summary
        if (data.error_summary) {
            $('#detail-error-box').show();
            $('#detail-error-message').text(data.error_summary);
        } else {
            $('#detail-error-box').hide();
        }

        // Download Button
        const btnDownload = $('#modal-btn-download');
        if (data.has_file && data.download_url) {
            btnDownload.attr('href', data.download_url).show();
        } else {
            btnDownload.hide();
        }
    }

    function initRowsTable(uuid) {
        const tableEl = $('#batch-rows-table');
        if (!tableEl.length) return;

        if (rowsTable) {
            rowsTable.destroy();
            tableEl.empty();
            tableEl.html(`
                <thead>
                    <tr>
                        <th class="w-1">Row</th>
                        <th>Sheet</th>
                        <th>Employee ID</th>
                        <th>Employee Name</th>
                        <th>Course Name</th>
                        <th class="text-center">Validation</th>
                        <th class="text-center">Action</th>
                        <th>Notes</th>
                        <th class="text-center">Refs</th>
                    </tr>
                </thead>
                <tbody></tbody>
            `);
        }

        const rowsUrl = window.importHistoryConfig.routes.rows.replace('__UUID__', uuid);

        rowsTable = tableEl.DataTable({
            processing: true,
            serverSide: true,
            responsive: true,
            pageLength: 10,
            lengthMenu: [[10, 25, 50, 100], [10, 25, 50, 100]],
            dom: '<"top">rt<"bottom d-flex flex-column flex-md-row justify-content-between align-items-center mt-3 gap-2"l ip><"clear">',
            searchDelay: 400,
            ajax: {
                url: rowsUrl,
                type: 'GET',
                data: function (d) {
                    d.action = $('#row-filter-action').val();
                    d.validation_status = $('#row-filter-status').val();
                    d.search_query = $('#row-filter-search').val();
                }
            },
            columns: [
                { data: 'row_number', name: 'row_number', className: 'text-center fw-bold' },
                { data: 'sheet_name', name: 'sheet_name', defaultContent: '-' },
                { data: 'employee_id', name: 'employee_id', defaultContent: '-' },
                { data: 'employee_name', name: 'employee_name', defaultContent: '-' },
                { data: 'course_name', name: 'course_name', defaultContent: '-' },
                { data: 'validation_status_badge', name: 'validation_status', className: 'text-center' },
                { data: 'action_badge', name: 'action', className: 'text-center' },
                { data: 'messages_formatted', name: 'messages' },
                { data: 'references', name: 'references', className: 'text-center' }
            ],
            order: [[0, 'asc']],
            language: {
                emptyTable: '<div class="py-3 text-center text-muted">No rows match the filter criteria.</div>',
                processing: '<div class="spinner-border spinner-border-sm text-primary me-2"></div> Loading rows...'
            }
        });
    }

})(jQuery);
