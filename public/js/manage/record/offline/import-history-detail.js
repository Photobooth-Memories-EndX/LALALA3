/**
 * Import Batch Detail Page — OWL LMS
 * Manages Server-Side DataTables Audit Log and Filter-Aware Excel Export
 */
(function ($) {
    'use strict';

    let rowsTable = null;

    $(document).ready(function () {
        if (!window.detailConfig || !window.detailConfig.batchUuid) {
            console.error('detailConfig is not configured.');
            return;
        }

        initAuditRowsTable();
        initFilterEvents();
        initExportEvents();
        initCopyUuidButton();
        initBackButton();
    });

    /**
     * Initialize Server-Side DataTables for Spreadsheet Rows Audit Log
     */
    function initAuditRowsTable() {
        const tableEl = $('#detail-rows-table');
        if (!tableEl.length) return;

        const rowsUrl = window.detailConfig.routes.rows;

        rowsTable = tableEl.DataTable({
            processing: true,
            serverSide: true,
            responsive: true,
            pageLength: 10,
            lengthMenu: [[10, 25, 50, 100], [10, 25, 50, 100]],
            dom: '<"top">rt<"bottom d-flex flex-column flex-md-row justify-content-between align-items-center mt-3 gap-2 px-3 py-2"l ip><"clear">',
            searchDelay: 400,
            ajax: {
                url: rowsUrl,
                type: 'GET',
                data: function (d) {
                    d.action = $('#row-filter-action').val() || '';
                    d.validation_status = $('#row-filter-status').val() || '';
                    d.search_query = $('#row-filter-search').val() || '';
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
                emptyTable: '<div class="py-4 text-center text-muted"><i class="ti ti-database-off fs-1 d-block mb-1"></i>No audit records match the selected filter criteria.</div>',
                processing: '<div class="py-3 text-center"><div class="spinner-border spinner-border-sm text-primary me-2"></div><span class="text-muted">Loading audit records...</span></div>'
            },
            drawCallback: function () {
                const api = this.api();
                const info = api.page.info();
                if (info && info.recordsTotal > 0) {
                    const start = info.recordsDisplay > 0 ? info.start + 1 : 0;
                    const end = info.end;
                    const total = info.recordsDisplay;
                    $('#audit-rows-counter').text(`Showing ${start}–${end} of ${total} row(s)`);
                } else {
                    $('#audit-rows-counter').text('Showing 0 of 0 rows');
                }

                // Initialize Bootstrap tooltips on rendered elements
                if (window.bootstrap && bootstrap.Tooltip) {
                    const tooltips = [].slice.call(document.querySelectorAll('#detail-rows-table [data-bs-toggle="tooltip"]'));
                    tooltips.map(function (el) {
                        return bootstrap.Tooltip.getOrCreateInstance(el);
                    });
                }
            }
        });
    }

    /**
     * Filter Bar Controls
     */
    function initFilterEvents() {
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

    /**
     * Filter-Aware Excel Export
     * Exports ALL rows matching active action, validation_status, and search_query filters
     */
    function initExportEvents() {
        $('.btn-trigger-export').on('click', function (e) {
            e.preventDefault();
            const uuid = window.detailConfig.batchUuid;
            if (!uuid) return;

            const action = $('#row-filter-action').val() || '';
            const status = $('#row-filter-status').val() || '';
            const search = $('#row-filter-search').val() || '';

            let exportUrl = window.detailConfig.routes.export;
            const params = new URLSearchParams();
            if (action) params.append('action', action);
            if (status) params.append('validation_status', status);
            if (search) params.append('search_query', search);

            const queryString = params.toString();
            if (queryString) {
                exportUrl += (exportUrl.includes('?') ? '&' : '?') + queryString;
            }

            window.location.href = exportUrl;
        });
    }

    /**
     * Copy Batch UUID Button
     */
    function initCopyUuidButton() {
        $('#btn-copy-uuid').on('click', function (e) {
            e.preventDefault();
            const uuid = window.detailConfig.batchUuid;
            if (!uuid) return;

            navigator.clipboard.writeText(uuid).then(() => {
                const btn = $(this);
                const originalHtml = btn.html();
                btn.html('<i class="ti ti-check text-success"></i>');
                setTimeout(() => btn.html(originalHtml), 2000);
            }).catch(() => {
                prompt('Copy Batch UUID:', uuid);
            });
        });
    }

    /**
     * Back to Import History button
     * Returns using history.back() if user navigated from import history, preserving previous filter state
     */
    function initBackButton() {
        $('.btn-smart-back').on('click', function (e) {
            if (document.referrer && document.referrer.includes('/import-history')) {
                e.preventDefault();
                window.history.back();
            }
        });
    }

})(jQuery);
