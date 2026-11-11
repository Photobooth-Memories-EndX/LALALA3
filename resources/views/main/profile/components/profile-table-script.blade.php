<script src="{{ asset('js/jquery.dataTables.min.js') }}"></script>
<script src="{{ asset('js/dataTables.bootstrap5.min.js') }}"></script>

<script>
    $(function () {
        const defaultEntries = 25;

        // Initialize Bootstrap tooltips
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

        // Initialize DataTable with Standardized layout (dom: 't')
        let historyTable = $('#MyTable').DataTable({
            processing: true,
            serverSide: true,
            responsive: false,
            autoWidth: false,
            pageLength: defaultEntries,
            lengthMenu: [
                [10, 25, 50, 100],
                ['10 Entries', '25 Entries', '50 Entries', '100 Entries']
            ],
            order: [], // Server-side ordering by date desc
            dom: 't',
            ajax: {
                url: datatableUrl,
                type: 'POST',
                headers: {
                    'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                },
                data: function (d) {
                    d._token = $('meta[name="csrf-token"]').attr('content');
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
                }
            },
            columns: [
                { data: 'DT_RowIndex', name: 'DT_RowIndex', orderable: false, searchable: false, className: 'text-center' },
                { data: 'source_badge', name: 'source', className: 'text-center' },
                { data: 'employee_id', name: 'employee_id', className: 'text-center' },
                { data: 'employee_name', name: 'employee_name', className: 'text-start' },
                { data: 'team', name: 'team', className: 'text-start' },
                { data: 'department', name: 'department', className: 'text-start' },
                { data: 'company', name: 'company', className: 'text-start' },
                { data: 'course_title', name: 'course_title', className: 'text-start' },
                { data: 'type', name: 'type', className: 'text-center' },
                { data: 'category', name: 'category', className: 'text-start' },
                { data: 'main_classification', name: 'main_classification', className: 'text-start' },
                { data: 'sub_classification', name: 'sub_classification', orderable: false, searchable: false, className: 'text-center' },
                { data: 'delivery_method', name: 'delivery_method', orderable: false, searchable: false, className: 'text-center' },
                { data: 'sub_delivery_method', name: 'sub_delivery_method', orderable: false, searchable: false, className: 'text-start' },
                { data: 'provider', name: 'provider', orderable: false, searchable: false, className: 'text-center' },
                { data: 'provider_name', name: 'provider_name', orderable: false, searchable: false, className: 'text-start' },
                { data: 'venue', name: 'venue', orderable: false, searchable: false, className: 'text-start' },
                { data: 'course_duration', name: 'course_duration', className: 'text-center' },
                { data: 'total_recognized_duration', name: 'total_recognized_duration', orderable: false, searchable: false, className: 'text-center' },
                { data: 'learning_hours', name: 'learning_hours', className: 'text-center' },
                { data: 'progress_bar', name: 'progress', className: 'text-center' },
                { data: 'progress_status', name: 'status', className: 'text-center' },
                { data: 'date_merged', name: 'start_date', className: 'text-start' },
                { data: 'time_merged', name: 'start_time', className: 'text-start' },
                { data: 'record_month', name: 'record_date', className: 'text-center' },
                { data: 'requirement', name: 'requirement', className: 'text-center' },
                { data: 'attendance', name: 'attendance', orderable: false, searchable: false, className: 'text-center' },
                { data: 'score_l1', name: 'score_l1', orderable: false, searchable: false, className: 'text-center' },
                { data: 'score_l2', name: 'score_l2', orderable: false, searchable: false, className: 'text-center' },
                { data: 'score_l3', name: 'score_l3', orderable: false, searchable: false, className: 'text-center' },
                { data: 'certificate_availability', name: 'certificate_availability', orderable: false, searchable: false, className: 'text-center' },
                { data: 'pic', name: 'pic', orderable: false, searchable: false, className: 'text-start' },
                { data: 'total_engagement_time', name: 'total_engagement_time', className: 'text-center' },
                {
                    data: 'action',
                    name: 'action',
                    orderable: false,
                    searchable: false,
                    className: 'text-end text-nowrap',
                    render: function (data, type, row) {
                        if (data) return data;
                        const recordId = (row && (row.source_id || row.id)) ? (row.source_id || row.id) : '';
                        const source = (row && row.source) ? row.source : 'online';
                        const sourceType = (row && row.source_type) ? row.source_type : 'content';
                        const contentSlug = (row && (row.learning_slug || row.content_slug)) ? (row.learning_slug || row.content_slug) : '';
                        const contentUrl = contentSlug ? ("{{ url('manage/learning/content') }}/" + encodeURIComponent(contentSlug)) : '';
                        const editUrl = "{{ url('manage/record') }}/" + encodeURIComponent(recordId) + "/edit?source=" + encodeURIComponent(source);

                        let items = `
                            <li>
                                <a class="dropdown-item d-flex align-items-center gap-2 btn-view-learning-detail btn-more-info"
                                   href="javascript:void(0);"
                                   data-source="${source}"
                                   data-source-type="${sourceType}"
                                   data-source-id="${recordId}"
                                   data-id="${recordId}">
                                    <i class="ti ti-eye text-primary"></i> More Information
                                </a>
                            </li>
                        `;

                        if (contentUrl) {
                            items += `
                                <li>
                                    <a class="dropdown-item d-flex align-items-center gap-2" href="${contentUrl}">
                                        <i class="ti ti-external-link text-info"></i> View Content
                                    </a>
                                </li>
                            `;
                        }

                        items += `
                            <li>
                                <a class="dropdown-item d-flex align-items-center gap-2" href="${editUrl}">
                                    <i class="ti ti-edit text-warning"></i> Edit Record
                                </a>
                            </li>
                        `;

                        return `
                            <div class="dropdown">
                                <button class="btn btn-sm btn-icon btn-ghost-secondary rounded-2"
                                        type="button"
                                        data-bs-toggle="dropdown"
                                        data-bs-boundary="viewport"
                                        data-bs-popper-config='{"strategy":"fixed"}'
                                        aria-expanded="false"
                                        title="Action Options">
                                    <i class="ti ti-dots-vertical"></i>
                                </button>
                                <ul class="dropdown-menu dropdown-menu-end shadow-sm border rounded-3 py-1">
                                    ${items}
                                </ul>
                            </div>
                        `;
                    }
                }
            ],
            language: {
                processing: '<div class="py-4 text-center"><div class="spinner-border text-primary" role="status"></div><div class="mt-2 text-muted">Loading learning history...</div></div>',
                emptyTable: '<div class="text-center p-4 text-muted"><i class="ti ti-notebook-off fs-1 mb-2 d-block"></i>No learning history records found</div>'
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
                    $('#profile-table-info-container').html(
                        `<span class="text-muted small">Showing ${formatNum(start)} to ${formatNum(end)} of ${formatNum(total)} entries</span>`
                    );
                } else {
                    $('#profile-table-info-container').html(
                        '<span class="text-muted small">Showing 0 to 0 of 0 entries</span>'
                    );
                }

                // 2. Render custom pagination controls (Matching master reference)
                const $paginationContainer = $('#profile-table-pagination-container');
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
                    const tooltipTriggerList = [].slice.call(document.querySelectorAll('#MyTable [data-bs-toggle="tooltip"]'));
                    tooltipTriggerList.forEach(function (tooltipTriggerEl) {
                        bootstrap.Tooltip.getOrCreateInstance(tooltipTriggerEl);
                    });
                }

                // 4. Sync dual scrollbar
                syncTopScroll();
            }
        });

        // Pagination click handler
        $(document).on('click', '#profile-table-pagination-container .page-link', function (e) {
            e.preventDefault();
            const page = $(this).data('page');
            if (page !== undefined && page !== null && page >= 0) {
                historyTable.page(page).draw('page');
            }
        });

        // Entries select change handler
        $('#profile-table-entries-select').on('change', function () {
            const nextLength = parseInt($(this).val(), 10) || defaultEntries;
            historyTable.page.len(nextLength).page(0).draw(false);
        });

        // Dual synchronized horizontal scrollbars
        const $topScroll = $('#profile-table-scroll-top-wrapper');
        const $tableScroll = $('#profile-table-scroll-container');
        const $topInner = $('#profile-table-scroll-top-inner');

        function syncTopScroll() {
            const tableWidth = $('#MyTable').outerWidth();
            $topInner.css('width', tableWidth + 'px');
        }

        let isSyncingTop = false;
        let isSyncingBottom = false;

        $topScroll.on('scroll', function() {
            if (!isSyncingTop) {
                isSyncingBottom = true;
                $tableScroll.scrollLeft($(this).scrollLeft());
            }
            isSyncingTop = false;
        });

        $tableScroll.on('scroll', function() {
            if (!isSyncingBottom) {
                isSyncingTop = true;
                $topScroll.scrollLeft($(this).scrollLeft());
            }
            isSyncingBottom = false;
        });

        $(window).on('resize', syncTopScroll);

        // Helper: Calculate active More Filters badge
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

        // Trigger Filter Reload
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

        // Reset All Filters
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

        // More Filters Modal Apply
        $('#btn-profile-modal-apply').on('click', function () {
            $('#profile-filter-from-date').val($('#profile-modal-from-date').val() || '');
            $('#profile-filter-to-date').val($('#profile-modal-to-date').val() || '');
            $('#profile-filter-progress-range').val($('input[name="profile_modal_progress_range"]:checked').val() || 'all');
            $('#profile-filter-duration').val($('input[name="profile_modal_duration"]:checked').val() || 'all');

            updateMoreFilterBadge();
            historyTable.ajax.reload();
        });

        // More Filters Modal Reset
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

        // Export Query Collector Helper
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

        // Export Triggers
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
    });
</script>
