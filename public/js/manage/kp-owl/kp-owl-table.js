/**
 * KP OWL DataTable Initialization (Standardized with Master Reference)
 */
let kpOwlTable = null;

$(document).ready(function () {
    if (!$('#kp-owl-datatable').length) {
        return;
    }

    const defaultEntries = 25;

    // Initialize Tooltips
    if (typeof bootstrap !== 'undefined' && bootstrap.Tooltip) {
        const tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
        tooltipTriggerList.map(function (tooltipTriggerEl) {
            return new bootstrap.Tooltip(tooltipTriggerEl);
        });
    }

    kpOwlTable = $('#kp-owl-datatable').DataTable({
        processing: true,
        serverSide: true,
        responsive: true,
        autoWidth: false,
        pageLength: defaultEntries,
        lengthMenu: [
            [10, 25, 50, 100],
            ['10 Entries', '25 Entries', '50 Entries', '100 Entries']
        ],
        order: [[20, 'desc']], // Sort by Learning Period / created_at default
        dom: 't',
        ajax: {
            url: window.KP_OWL_ROUTES.data,
            type: 'GET',
            data: function (d) {
                // Collect filters from quick filter bar
                d.search = $('#filter-search').val() || '';
                d.main_classification = $('#filter-classification').val() || 'all';
                d.category = $('#filter-category').val() || 'all';
                d.progress_status = $('#filter-status').val() || 'all';
                d.type = $('#filter-type').val() || 'all';
                d.year = $('#filter-year').val() || 'all';
                d.length = parseInt($('#table-entries-select').val(), 10) || defaultEntries;

                // Collect extra filters from hidden modal inputs
                d.department = $('#filter-department').val() || 'all';
                d.team = $('#filter-team').val() || 'all';
                d.company = $('#filter-company').val() || 'all';
                d.record_month = $('#filter-month').val() || 'all';
                d.from_date = $('#filter-from-date').val() || '';
                d.to_date = $('#filter-to-date').val() || '';
                d.progress_range = $('#filter-progress-range').val() || 'all';
            },
            error: function (xhr, error, code) {
                console.error('DataTable AJAX Error:', error, code, xhr.responseText);
            }
        },
        columns: [
            { data: 'DT_RowIndex', name: 'DT_RowIndex', orderable: false, searchable: false, className: 'text-center' },
            { data: 'employee_id', name: 'u.id_emp', className: 'text-center' },
            { data: 'employee_name', name: 'u.name' },
            { data: 'team', name: 'u.team' },
            { data: 'department', name: 'u.department' },
            { data: 'company', name: 'u.company_name' },
            { data: 'course_title', name: 'c.title' },
            { data: 'type', name: 'c.type', className: 'text-center' },
            { data: 'category', name: 'unified.source_type', className: 'text-center' },
            { data: 'main_classification', name: 'cl.classification_name' },
            { data: 'sub_classification', name: 'sub_classification', orderable: false, searchable: false, className: 'text-center' },
            { data: 'delivery_method', name: 'delivery_method', orderable: false, searchable: false, className: 'text-center' },
            { data: 'provider', name: 'provider', orderable: false, searchable: false, className: 'text-center' },
            { data: 'provider_name', name: 'provider_name', orderable: false, searchable: false, className: 'text-center' },
            { data: 'venue', name: 'venue', orderable: false, searchable: false, className: 'text-center' },
            { data: 'course_duration', name: 'c.duration', className: 'text-center' },
            { data: 'total_recognized_duration', name: 'total_recognized_duration', orderable: false, searchable: false, className: 'text-center' },
            { data: 'learning_hours', name: 'unified.learning_hours', className: 'text-center' },
            { data: 'progress', name: 'unified.progress', className: 'text-center' },
            { data: 'progress_status', name: 'unified.finish', className: 'text-center' },
            { data: 'learning_period', name: 'unified.created_at' },
            { data: 'record_month', name: 'unified.completed_at', className: 'text-center' },
            { data: 'requirement', name: 'unified.requirement_status', className: 'text-center' },
            { data: 'attendance', name: 'attendance', orderable: false, searchable: false, className: 'text-center' },
            { data: 'score_l1', name: 'score_l1', orderable: false, searchable: false, className: 'text-center' },
            { data: 'score_l2', name: 'score_l2', orderable: false, searchable: false, className: 'text-center' },
            { data: 'score_l3', name: 'score_l3', orderable: false, searchable: false, className: 'text-center' },
            { data: 'certificate_availability', name: 'certificate_availability', orderable: false, searchable: false, className: 'text-center' },
            { data: 'pic', name: 'pic', orderable: false, searchable: false, className: 'text-center' },
            { data: 'total_engagement_time', name: 'total_engagement_time', orderable: false, searchable: false, className: 'text-center' },
            { data: 'action', name: 'action', orderable: false, searchable: false, className: 'text-center sticky-action-col' },
        ],
        language: {
            processing: '<div class="py-4 text-center"><div class="spinner-border text-primary" role="status"></div><div class="mt-2 text-muted">Loading KP OWL records...</div></div>',
            emptyTable: '<div class="p-4 text-center text-muted"><i class="ti ti-folder-off fs-1 d-block mb-2"></i>No KP OWL learning records found matching your filters.</div>',
            zeroRecords: '<div class="p-4 text-center text-muted"><i class="ti ti-search-off fs-1 d-block mb-2"></i>No records found matching query.</div>',
        },
        drawCallback: function (settings) {
            const api = this.api();
            const pageInfo = api.page.info();
            const formatNum = (num) => (num || 0).toLocaleString();

            // 1. Render custom info text
            if (pageInfo.recordsTotal > 0 && pageInfo.recordsDisplay > 0) {
                const start = pageInfo.start + 1;
                const end = pageInfo.end;
                const total = pageInfo.recordsDisplay;
                $('#kp-owl-table-info-container').html(
                    `<span class="text-muted small">Showing ${formatNum(start)} to ${formatNum(end)} of ${formatNum(total)} entries</span>`
                );
            } else {
                $('#kp-owl-table-info-container').html(
                    '<span class="text-muted small">Showing 0 to 0 of 0 entries</span>'
                );
            }

            // 2. Render custom pagination controls (Matching master reference)
            const $paginationContainer = $('#kp-owl-table-pagination-container');
            $paginationContainer.empty();

            if (pageInfo.pages > 1) {
                let paginationHtml = '<ul class="pagination pagination-sm mb-0">';

                // Previous Button
                const prevDisabled = pageInfo.page === 0 ? 'disabled' : '';
                paginationHtml += `
                    <li class="page-item ${prevDisabled}">
                        <a class="page-link" href="javascript:void(0);" data-page="${pageInfo.page - 1}" aria-label="Previous">
                            <i class="ti ti-chevron-left"></i>
                        </a>
                    </li>
                `;

                // Page Numbers
                const currentPage = pageInfo.page;
                const totalPages = pageInfo.pages;
                let startPage = Math.max(0, currentPage - 2);
                let endPage = Math.min(totalPages - 1, currentPage + 2);

                if (startPage > 0) {
                    paginationHtml += `<li class="page-item"><a class="page-link" href="javascript:void(0);" data-page="0">1</a></li>`;
                    if (startPage > 1) {
                        paginationHtml += `<li class="page-item disabled"><span class="page-link">…</span></li>`;
                    }
                }

                for (let p = startPage; p <= endPage; p++) {
                    const active = p === currentPage ? 'active' : '';
                    paginationHtml += `
                        <li class="page-item ${active}">
                            <a class="page-link" href="javascript:void(0);" data-page="${p}">${p + 1}</a>
                        </li>
                    `;
                }

                if (endPage < totalPages - 1) {
                    if (endPage < totalPages - 2) {
                        paginationHtml += `<li class="page-item disabled"><span class="page-link">…</span></li>`;
                    }
                    paginationHtml += `<li class="page-item"><a class="page-link" href="javascript:void(0);" data-page="${totalPages - 1}">${totalPages}</a></li>`;
                }

                // Next Button
                const nextDisabled = pageInfo.page >= totalPages - 1 ? 'disabled' : '';
                paginationHtml += `
                    <li class="page-item ${nextDisabled}">
                        <a class="page-link" href="javascript:void(0);" data-page="${pageInfo.page + 1}" aria-label="Next">
                            <i class="ti ti-chevron-right"></i>
                        </a>
                    </li>
                `;

                paginationHtml += '</ul>';
                $paginationContainer.html(paginationHtml);
            }

            // 3. Re-initialize tooltips
            if (typeof bootstrap !== 'undefined' && bootstrap.Tooltip) {
                const tooltipTriggerList = [].slice.call(document.querySelectorAll('#kp-owl-datatable [data-bs-toggle="tooltip"]'));
                tooltipTriggerList.forEach(function (tooltipTriggerEl) {
                    bootstrap.Tooltip.getOrCreateInstance(tooltipTriggerEl);
                });
            }

            // 4. Sync dual scrollbars
            syncKpOwlTopScroll();
        }
    });

    // Pagination click handler
    $(document).on('click', '#kp-owl-table-pagination-container .page-link', function (e) {
        e.preventDefault();
        const page = $(this).data('page');
        if (page !== undefined && page !== null && page >= 0) {
            kpOwlTable.page(page).draw('page');
        }
    });

    // Show entries select change handler
    $('#table-entries-select').on('change', function () {
        const nextLength = parseInt($(this).val(), 10) || defaultEntries;
        kpOwlTable.page.len(nextLength).page(0).draw(false);
    });

    // Dual synchronized horizontal scrollbars
    const $topScroll = $('#kp-owl-scroll-top-wrapper');
    const $tableScroll = $('#kp-owl-scroll-container');
    const $topInner = $('#kp-owl-scroll-top-inner');

    function syncKpOwlTopScroll() {
        const tableWidth = $('#kp-owl-datatable').outerWidth();
        $topInner.css('width', tableWidth + 'px');
    }

    let isSyncingTop = false;
    let isSyncingBottom = false;

    $topScroll.on('scroll', function () {
        if (!isSyncingTop) {
            isSyncingBottom = true;
            $tableScroll.scrollLeft($(this).scrollLeft());
        }
        isSyncingTop = false;
    });

    $tableScroll.on('scroll', function () {
        if (!isSyncingBottom) {
            isSyncingTop = true;
            $topScroll.scrollLeft($(this).scrollLeft());
        }
        isSyncingBottom = false;
    });

    $(window).on('resize', syncKpOwlTopScroll);

    // Initial load for statistics
    loadKpOwlStatistics();
});

function loadKpOwlStatistics() {
    if (!window.KP_OWL_ROUTES || !window.KP_OWL_ROUTES.statistics) return;

    $.ajax({
        url: window.KP_OWL_ROUTES.statistics,
        type: 'GET',
        dataType: 'json',
        success: function (res) {
            if (res.success && res.data) {
                const d = res.data;
                $('#stat-total-learning').text(Number(d.total_learning || 0).toLocaleString());
                $('#stat-ongoing').text(Number(d.ongoing || 0).toLocaleString());
                $('#stat-finished').text(Number(d.finished || 0).toLocaleString());
                $('#stat-completion-rate').text((d.completion_rate || 0) + '%');
                $('#stat-learning-hours').text(Number(d.total_learning_hours || 0).toLocaleString(undefined, { minimumFractionDigits: 2, maximumFractionDigits: 2 }) + ' h');
            }
        },
        error: function (err) {
            console.error('Error loading KP OWL statistics:', err);
        }
    });
}
