@once
<script src="{{ asset('js/jquery.dataTables.min.js') }}"></script>
<script src="{{ asset('js/dataTables.bootstrap5.min.js') }}"></script>

<script>
    $(function () {
        const initialSearch = @json($search ?? '');
        const defaultEntries = @json((int) ($entries ?? 25));

        // ── 1. Helper: Render Autocomplete Results with Sticky Footer ──────
        function renderAutocompleteResults(inputId, dropdownId, listId, footerId, results, isEmployee) {
            const $input = $(`#${inputId}`);
            const $dropdown = $(`#${dropdownId}`);
            const $list = $(`#${listId}`);
            const $footer = $(`#${footerId}`);
            const keyword = $input.val().trim();

            $list.empty();

            if (keyword.length === 0) {
                $dropdown.hide();
                return;
            }

            const items = Array.isArray(results) ? results : (results.results || []);

            if (items && items.length > 0) {
                items.forEach(item => {
                    const label = item.title || item.label || item.name || item.text || '';
                    const val = item.value || item.title || item.name || item.id || label;
                    const subtext = item.type ? (item.type + (item.subtext ? ' • ' + item.subtext : '')) : (item.subtext || (item.category ? item.category : ''));

                    $list.append(`
                        <a href="javascript:void(0);" class="list-group-item list-group-item-action autocomplete-item-row select-autocomplete-item" data-input="${inputId}" data-value="${val}" data-label="${label}">
                            <div class="pe-2 text-truncate" style="flex: 1 1 auto; min-width: 0;">
                                <div class="fw-semibold text-truncate" style="line-height: 1.35;">${label}</div>
                                ${subtext ? `<div class="text-muted extra-small text-truncate" style="font-size: 0.75rem; line-height: 1.25; margin-top: 2px;">${subtext}</div>` : ''}
                            </div>
                            ${item.type ? `<span class="badge bg-light text-secondary border small px-2 py-1 ms-auto flex-shrink-0">${item.type}</span>` : ''}
                        </a>
                    `);
                });

                // Attach "See all results" sticky footer link
                $footer.html(`
                    <a href="javascript:void(0);" class="text-primary small fw-semibold text-decoration-none trigger-submit-filter" data-input="${inputId}">
                        See all results for "${keyword}" <i class="ti ti-arrow-right ms-1"></i>
                    </a>
                `).show();
            } else {
                $list.append(`
                    <div class="p-3 text-center text-muted small">No matches found for "${keyword}"</div>
                `);
                $footer.hide();
            }

            $dropdown.show();
        }

        // ── 1b. Employee Live Autocomplete Listener ────────────────────────
        let employeeDebounceTimer = null;
        let employeeXhr = null;
        $('#manage-employee-input').on('input', function () {
            const query = $(this).val().trim();
            clearTimeout(employeeDebounceTimer);

            if (employeeXhr) {
                employeeXhr.abort();
                employeeXhr = null;
            }

            if (query.length === 0) {
                $('#manage-employee-dropdown').hide();
                $('#learn-employee').val('');
                return;
            }

            employeeDebounceTimer = setTimeout(function () {
                employeeXhr = $.ajax({
                    url: "{{ $employeeSearchUrl ?? route('learning.manage.search-employees') }}",
                    type: 'GET',
                    data: { q: query, search: query, employee_q: query },
                    dataType: 'json',
                    success: function (data) {
                        employeeXhr = null;
                        const items = data.results || data || [];
                        renderAutocompleteResults('manage-employee-input', 'manage-employee-dropdown', 'manage-employee-list', 'manage-employee-footer', items, true);
                    },
                    error: function (xhr, status) {
                        if (status === 'abort') {
                            return;
                        }
                        employeeXhr = null;
                        $('#manage-employee-dropdown').hide();
                    }
                });
            }, 250);
        });

        // ── 1c. Learning Title Live Autocomplete Listener ───────────────────
        let titleDebounceTimer = null;
        let titleXhr = null;
        $('#manage-title-input').on('input', function () {
            const query = $(this).val().trim();
            clearTimeout(titleDebounceTimer);

            if (titleXhr) {
                titleXhr.abort();
                titleXhr = null;
            }

            if (query.length === 0) {
                $('#manage-title-dropdown').hide();
                $('#learn-title').val('');
                return;
            }

            titleDebounceTimer = setTimeout(function () {
                titleXhr = $.ajax({
                    url: "{{ $titleSearchUrl ?? route('learning.manage.search-titles') }}",
                    type: 'GET',
                    data: {
                        q: query,
                        search: query,
                        title_q: query,
                        playlist_id: $('#playlist-id').val() || ''
                    },
                    dataType: 'json',
                    success: function (data) {
                        titleXhr = null;
                        const items = data.results || data || [];
                        renderAutocompleteResults('manage-title-input', 'manage-title-dropdown', 'manage-title-list', 'manage-title-footer', items, false);
                    },
                    error: function (xhr, status) {
                        if (status === 'abort') {
                            return;
                        }
                        titleXhr = null;
                        $('#manage-title-dropdown').hide();
                    }
                });
            }, 250);
        });

        // ── 1d. Autocomplete Selection Handlers ─────────────────────────────
        $(document).on('click', '.select-autocomplete-item', function (e) {
            e.preventDefault();
            if (employeeXhr) { employeeXhr.abort(); employeeXhr = null; }
            if (titleXhr) { titleXhr.abort(); titleXhr = null; }

            const targetInputId = $(this).data('input');
            const val = $(this).data('value');
            const label = $(this).data('label');

            if (targetInputId === 'manage-employee-input') {
                $('#manage-employee-input').val(label);
                $('#learn-employee').val(val);
                $('#manage-employee-dropdown').hide();
            } else if (targetInputId === 'manage-title-input') {
                $('#manage-title-input').val(label);
                $('#learn-title').val(val);
                $('#manage-title-dropdown').hide();
            }

            $('#btn-apply-filter').trigger('click');
        });

        // Handle clicking on "See all results" or pressing Enter
        $(document).on('click', '.trigger-submit-filter', function () {
            if (employeeXhr) { employeeXhr.abort(); employeeXhr = null; }
            if (titleXhr) { titleXhr.abort(); titleXhr = null; }

            const targetInputId = $(this).data('input');
            if (targetInputId === 'manage-employee-input') {
                $('#learn-employee').val($('#manage-employee-input').val().trim());
                $('#manage-employee-dropdown').hide();
            } else if (targetInputId === 'manage-title-input') {
                $('#learn-title').val($('#manage-title-input').val().trim());
                $('#manage-title-dropdown').hide();
            }
            $('#btn-apply-filter').trigger('click');
        });

        // Hide dropdowns when clicking outside
        $(document).on('click', function (e) {
            if (!$(e.target).closest('#manage-employee-input, #manage-employee-dropdown').length) {
                $('#manage-employee-dropdown').hide();
            }
            if (!$(e.target).closest('#manage-title-input, #manage-title-dropdown').length) {
                $('#manage-title-dropdown').hide();
            }
        });

        $('#manage-employee-input, #manage-title-input').on('keypress', function (e) {
            if (e.which === 13) {
                e.preventDefault();
                if (employeeXhr) { employeeXhr.abort(); employeeXhr = null; }
                if (titleXhr) { titleXhr.abort(); titleXhr = null; }

                $('#manage-employee-dropdown, #manage-title-dropdown').hide();
                $('#learn-employee').val($('#manage-employee-input').val().trim());
                $('#learn-title').val($('#manage-title-input').val().trim());
                $('#btn-apply-filter').trigger('click');
            }
        });

        // Initialize Bootstrap tooltips
        if (typeof bootstrap !== 'undefined' && bootstrap.Tooltip) {
            const tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
            tooltipTriggerList.map(function (tooltipTriggerEl) {
                return new bootstrap.Tooltip(tooltipTriggerEl);
            });
        }

        // ── 2. Initialize DataTable ───────────────────────────────────────
        if ($.fn.DataTable.isDataTable('#learning-table')) {
            $('#learning-table').DataTable().destroy();
        }

        let learningTable = $('#learning-table').DataTable({
            processing: true,
            serverSide: true,
            responsive: true,
            autoWidth: false,
            pageLength: defaultEntries,
            lengthMenu: [
                [10, 25, 50, 100],
                [10, 25, 50, 100]
            ],
            order: [[9, 'desc']],
            dom: '<"table-responsive"t>',
            ajax: {
                url: "{{ $datatableUrl ?? route('learning.manage.datatable') }}",
                type: 'GET',
                headers: {
                    'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') || '{{ csrf_token() }}'
                },
                data: function (d) {
                    d._token = '{{ csrf_token() }}';
                    d.employee_id = $('#learn-employee').val() || '';
                    d.learning_title = $('#learn-title').val() || '';
                    d.search = $('#learn-title').val() || '';
                    d.category = $('#learn-category').val();
                    d.learning_source = $('#learn-category').val();
                    d.status = $('#learn-status').val();
                    d.type = $('#filter-type').val();
                    d.year = $('#learn-year').val();
                    d.entries = $('#table-entries-select').val() || defaultEntries;
                    d.length = parseInt($('#table-entries-select').val(), 10) || defaultEntries;
                    d.classification_slug = $('#classification-slug').val();
                    d.content_slug = $('#content-slug').val();
                    d.playlist_id = $('#playlist-id').val();
                    d.from_date = $('#filter-from-date').val();
                    d.to_date = $('#filter-to-date').val();
                    d.program_type = $('#filter-program-type').val();
                    d.progress_range = $('#filter-progress-range').val();
                    d.duration = $('#filter-duration').val();
                }
            },
            columns: [
                { data: 'DT_RowIndex', name: 'DT_RowIndex', orderable: false, searchable: false, className: 'text-center' },
                { data: 'status_badge', name: 'unified.status', className: 'text-center' },
                { data: 'id_emp', name: 'u.id_emp', className: 'text-center' },
                { data: 'employee', name: 'u.name' },
                { data: 'learning_title', name: 'c.title' },
                { data: 'type_badge', name: 'c.type', className: 'text-center' },
                { data: 'classification_name', name: 'cl.classification_name' },
                { data: 'category_badge', name: 'unified.category_source', className: 'text-center' },
                { data: 'duration', name: 'c.duration', className: 'text-center' },
                { data: 'learning_date', name: 'unified.created_at', className: 'text-center' },
                { data: 'progress_bar', name: 'unified.progress', orderable: false, searchable: false, className: 'text-center' },
                { data: 'action', name: 'action', orderable: false, searchable: false, className: 'text-center' }
            ],
            language: {
                processing: '<div class="py-4 text-center"><div class="spinner-border text-primary" role="status"></div><div class="mt-2 text-muted">Loading learning data...</div></div>'
            },
            drawCallback: function (settings) {
                const api = this.api();
                const pageInfo = api.page.info();

                // Format number with commas
                const formatNum = (num) => (num || 0).toLocaleString();

                // Render DataTables info next to bottom-left entries dropdown
                if (pageInfo.recordsTotal > 0 && pageInfo.recordsDisplay > 0) {
                    const start = pageInfo.start + 1;
                    const end = pageInfo.end;
                    const total = pageInfo.recordsDisplay;
                    $('#learning-table-info-container').html(
                        `<span class="text-muted small">Showing ${formatNum(start)} to ${formatNum(end)} of ${formatNum(total)} entries</span>`
                    );
                } else {
                    $('#learning-table-info-container').html(
                        '<span class="text-muted small">Showing 0 to 0 of 0 entries</span>'
                    );
                }

                // Render Pagination
                const $paginationContainer = $('#learning-table-pagination-container');
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

                // Re-initialize tooltips for newly rendered action buttons
                if (typeof bootstrap !== 'undefined' && bootstrap.Tooltip) {
                    const tooltipTriggerList = [].slice.call(document.querySelectorAll('#learning-table [data-bs-toggle="tooltip"]'));
                    tooltipTriggerList.forEach(function (tooltipTriggerEl) {
                        bootstrap.Tooltip.getOrCreateInstance(tooltipTriggerEl);
                    });
                }
            }
        });

        // Pagination click handler
        $(document).on('click', '#learning-table-pagination-container .page-link', function (e) {
            e.preventDefault();
            const page = $(this).data('page');
            if (page !== undefined && page !== null && page >= 0) {
                learningTable.page(page).draw('page');
            }
        });

        // Function to update active filter counter badge
        function updateActiveFilterBadge() {
            let activeCount = 0;
            if ($('#filter-from-date').val()) activeCount++;
            if ($('#filter-to-date').val()) activeCount++;
            if ($('#filter-program-type').val() && $('#filter-program-type').val() !== 'all') activeCount++;
            if ($('#filter-progress-range').val() && $('#filter-progress-range').val() !== 'all') activeCount++;
            if ($('#filter-duration').val() && $('#filter-duration').val() !== 'all') activeCount++;

            const badge = $('#activeFilterBadge');
            if (activeCount > 0) {
                badge.text(activeCount).removeClass('d-none');
            } else {
                badge.addClass('d-none');
            }
        }

        // Helper function to safely hide More Filters modal
        function hideMoreFiltersModal() {
            const modalEl = document.getElementById('moreLearningFiltersModal');
            if (modalEl) {
                if (typeof bootstrap !== 'undefined' && bootstrap.Modal) {
                    const modalInstance = bootstrap.Modal.getInstance(modalEl) || bootstrap.Modal.getOrCreateInstance(modalEl);
                    if (modalInstance) {
                        modalInstance.hide();
                    }
                }
                if ($.fn.modal) {
                    $('#moreLearningFiltersModal').modal('hide');
                }
            }
            $('.modal-backdrop').remove();
            $('body').removeClass('modal-open').css({ overflow: '', paddingRight: '' });
        }

        // Helper function to update URL search parameters without full page reload
        function updateUrlParams(paramsObj) {
            if (window.history && window.history.replaceState) {
                const url = new URL(window.location.href);
                for (const [key, value] of Object.entries(paramsObj)) {
                    if (value !== null && value !== undefined && value !== '' && value !== 'all') {
                        url.searchParams.set(key, value);
                    } else {
                        url.searchParams.delete(key);
                    }
                }
                window.history.replaceState({}, document.title, url.toString());
            }
        }

        updateActiveFilterBadge();

        // ── 3. Quick Filter Event Listeners ───────────────────────────────
        $('#btn-apply-filter').on('click', function () {
            // Synchronize text inputs with hidden filter values if user typed directly
            const empInput = $('#manage-employee-input').val().trim();
            if (empInput && !$('#learn-employee').val()) {
                $('#learn-employee').val(empInput);
            } else if (!empInput) {
                $('#learn-employee').val('');
            }

            const titleInput = $('#manage-title-input').val().trim();
            if (titleInput && !$('#learn-title').val()) {
                $('#learn-title').val(titleInput);
            } else if (!titleInput) {
                $('#learn-title').val('');
            }

            $('#manage-employee-dropdown, #manage-title-dropdown').hide();

            const currentLength = parseInt($('#table-entries-select').val(), 10) || defaultEntries;

            updateUrlParams({
                employee_id: $('#learn-employee').val() || null,
                learning_title: $('#learn-title').val() || null,
                category: $('#learn-category').val() !== 'all' ? $('#learn-category').val() : null,
                status: $('#learn-status').val() !== 'all' ? $('#learn-status').val() : null,
                type: $('#filter-type').val() !== 'all' ? $('#filter-type').val() : null,
                year: $('#learn-year').val() !== 'all' ? $('#learn-year').val() : null,
                entries: currentLength !== defaultEntries ? currentLength : null,
            });
            learningTable.ajax.reload(null, true);
        });

        $('#learn-title, #manage-title-input').on('keypress', function (e) {
            if (e.which === 13) {
                e.preventDefault();
                $('#btn-apply-filter').trigger('click');
            }
        });

        $('#filter-type, #learn-category, #learn-status, #learn-year').on('change', function () {
            $('#btn-apply-filter').trigger('click');
        });

        $('#table-entries-select').on('change', function () {
            const nextLength = parseInt($(this).val(), 10) || defaultEntries;
            updateUrlParams({ entries: nextLength !== defaultEntries ? nextLength : null });
            learningTable.page.len(nextLength).page(0).draw(false);
        });

        // ── 4. More Filters Modal Events ──────────────────────────────────
        $('#btn-modal-apply').on('click', function () {
            // 1. Gather all active field values from modal inputs
            const fromDate = $('#modal-from-date').val() || '';
            const toDate = $('#modal-to-date').val() || '';
            const programType = $('input[name="modal_program_type"]:checked').val() 
                || $('input[name="modal_status"]:checked').val() 
                || 'all';
            const progressRange = $('input[name="modal_progress_range"]:checked').val() || 'all';
            const duration = $('input[name="modal_duration"]:checked').val() || 'all';

            // 2. Sync values to hidden filter form inputs
            $('#filter-from-date').val(fromDate);
            $('#filter-to-date').val(toDate);
            $('#filter-program-type').val(programType);
            $('#filter-progress-range').val(progressRange);
            $('#filter-duration').val(duration);

            // 3. Update active filter badge count
            updateActiveFilterBadge();

            // 4. Close modal automatically
            hideMoreFiltersModal();

            // 5. Update URL search query parameters
            updateUrlParams({
                from_date: fromDate,
                to_date: toDate,
                program_type: programType !== 'all' ? programType : null,
                progress_range: progressRange !== 'all' ? progressRange : null,
                duration: duration !== 'all' ? duration : null
            });

            // 6. Reload DataTable with page reset to page 1
            learningTable.ajax.reload(null, true);
        });

        $('#btn-modal-reset').on('click', function () {
            // 1. Clear modal input selections back to default
            $('#modal-from-date').val('');
            $('#modal-to-date').val('');
            $('input[name="modal_program_type"][value="all"], input[name="modal_status"][value="all"]').prop('checked', true);
            $('input[name="modal_progress_range"][value="all"]').prop('checked', true);
            $('input[name="modal_duration"][value="all"]').prop('checked', true);

            // 2. Clear hidden form inputs
            $('#filter-from-date').val('');
            $('#filter-to-date').val('');
            $('#filter-program-type').val('all');
            $('#filter-progress-range').val('all');
            $('#filter-duration').val('all');

            // 3. Update badge
            updateActiveFilterBadge();

            // 4. Update URL params
            updateUrlParams({
                from_date: null,
                to_date: null,
                program_type: null,
                progress_range: null,
                duration: null
            });

            // 5. Close modal
            hideMoreFiltersModal();

            // 6. Reload DataTable without modal filter constraints, resetting to page 1
            learningTable.ajax.reload(null, true);
        });

        // ── 5. Full Filter Reset Event ────────────────────────────────────
        $('#btn-reset-filter').on('click', function () {
            if (employeeXhr) { employeeXhr.abort(); employeeXhr = null; }
            if (titleXhr) { titleXhr.abort(); titleXhr = null; }

            // Reset Employee Search input & hidden value
            $('#manage-employee-input').val('');
            $('#learn-employee').val('');
            $('#manage-employee-dropdown').hide();

            // Reset Learning Title Search input & hidden value
            $('#manage-title-input').val('');
            $('#learn-title').val('');
            $('#manage-title-dropdown').hide();

            // Reset Quick Filter dropdowns
            $('#learn-category').val('all');
            $('#learn-status').val('all');
            $('#filter-type').val('all');
            $('#learn-year').val('all');
            $('#table-entries-select').val(defaultEntries);

            // Reset Modal inputs & hidden filter inputs
            $('#modal-from-date, #filter-from-date').val('');
            $('#modal-to-date, #filter-to-date').val('');
            $('input[name="modal_program_type"][value="all"], input[name="modal_status"][value="all"]').prop('checked', true);
            $('#filter-program-type').val('all');
            $('input[name="modal_progress_range"][value="all"]').prop('checked', true);
            $('#filter-progress-range').val('all');
            $('input[name="modal_duration"][value="all"]').prop('checked', true);
            $('#filter-duration').val('all');

            updateActiveFilterBadge();

            // Clear URL search parameters without page reload
            if (window.history && window.history.replaceState) {
                window.history.replaceState({}, document.title, window.location.pathname);
            }

            learningTable.page.len(defaultEntries).page(0).draw(false);
        });

        // ── 6. Filtered Data Download Handlers (Excel, PDF, CSV) ────────
        function collectLearningFilterParams() {
            const params = new URLSearchParams();

            const empVal = $('#learn-employee').val();
            if (empVal) params.append('employee_id', empVal);

            const titleVal = $('#learn-title').val();
            if (titleVal) params.append('learning_title', titleVal);

            const catVal = $('#learn-category').val();
            if (catVal && catVal !== 'all') params.append('category', catVal);

            const statusVal = $('#learn-status').val();
            if (statusVal && statusVal !== 'all') params.append('status', statusVal);

            const typeVal = $('#filter-type').val();
            if (typeVal && typeVal !== 'all') params.append('type', typeVal);

            const yearVal = $('#learn-year').val();
            if (yearVal && yearVal !== 'all') params.append('year', yearVal);

            const classSlug = $('#classification-slug').val();
            if (classSlug) params.append('classification_slug', classSlug);

            const contentSlug = $('#content-slug').val();
            if (contentSlug) params.append('content_slug', contentSlug);

            const playlistId = $('#playlist-id').val();
            if (playlistId) params.append('playlist_id', playlistId);

            const fromDate = $('#filter-from-date').val();
            if (fromDate) params.append('from_date', fromDate);

            const toDate = $('#filter-to-date').val();
            if (toDate) params.append('to_date', toDate);

            const programType = $('#filter-program-type').val();
            if (programType && programType !== 'all') params.append('program_type', programType);

            const progressRange = $('#filter-progress-range').val();
            if (progressRange && progressRange !== 'all') params.append('progress_range', progressRange);

            const duration = $('#filter-duration').val();
            if (duration && duration !== 'all') params.append('duration', duration);

            return params;
        }

        // 6a. Excel Export Handler (Existing, Untouched Logic)
        $('#btn-download-export').on('click', function (e) {
            e.preventDefault();
            const params = collectLearningFilterParams();
            params.append('action', 'download');

            const downloadBaseUrl = "{{ $downloadUrl ?? route('manage.learning.download') }}";
            const downloadUrl = downloadBaseUrl + (downloadBaseUrl.indexOf('?') !== -1 ? '&' : '?') + params.toString();
            window.open(downloadUrl, '_blank');
        });

        // 6b. PDF Export Handler
        $('#btn-download-pdf').on('click', function (e) {
            e.preventDefault();
            const params = collectLearningFilterParams();
            const pdfBaseUrl = "{{ route('manage.learning.download.pdf') }}";
            const pdfUrl = pdfBaseUrl + (pdfBaseUrl.indexOf('?') !== -1 ? '&' : '?') + params.toString();
            window.open(pdfUrl, '_blank');
        });

        // 6c. CSV Export Handler
        $('#btn-download-csv').on('click', function (e) {
            e.preventDefault();
            const params = collectLearningFilterParams();
            const csvBaseUrl = "{{ route('manage.learning.download.csv') }}";
            const csvUrl = csvBaseUrl + (csvBaseUrl.indexOf('?') !== -1 ? '&' : '?') + params.toString();
            window.open(csvUrl, '_blank');
        });

        // ── 7. Action Column Handlers ─────────────────────────────────────
        let isFetchingRecordDetail = false;

        // A. View Details Handler (AJAX Read-Only Modal)
        $(document).on('click', '.btn-view-learning-detail', function (e) {
            e.preventDefault();

            if (isFetchingRecordDetail) {
                return;
            }

            const sourceType = $(this).data('source-type');
            const sourceId = $(this).data('source-id');

            if (!sourceType || !sourceId) {
                return;
            }

            const modalEl = document.getElementById('learningDetailModal');
            if (!modalEl) {
                return;
            }

            const modalInstance = (typeof bootstrap !== 'undefined' && bootstrap.Modal)
                ? (bootstrap.Modal.getInstance(modalEl) || new bootstrap.Modal(modalEl))
                : null;

            // Show loading state, hide error and content
            $('#learningDetailLoading').show();
            $('#learningDetailError').hide();
            $('#learningDetailContent').hide();

            if (modalInstance) {
                modalInstance.show();
            } else if ($.fn.modal) {
                $('#learningDetailModal').modal('show');
            }

            isFetchingRecordDetail = true;

            const detailUrl = "{{ url('manage/learning/record-detail') }}/" + encodeURIComponent(sourceType) + "/" + encodeURIComponent(sourceId);

            $.ajax({
                url: detailUrl,
                type: 'GET',
                dataType: 'json',
                headers: {
                    'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') || '{{ csrf_token() }}'
                },
                success: function (res) {
                    if (res && res.status === 'success' && res.data) {
                        const d = res.data;
                        const emp = d.employee || {};
                        const lr = d.learning || {};
                        const pr = d.progress || {};
                        const ts = d.timestamps || {};

                        // Employee Information
                        $('#ld-emp-id').text(emp.id_emp || '-');
                        $('#ld-emp-name').text(emp.name || '-');
                        $('#ld-emp-dept').text(emp.department || '-');
                        $('#ld-emp-team').text(emp.team || '-');

                        // Learning Information
                        $('#ld-content-title').text(lr.title || '-');
                        $('#ld-content-type').text(lr.type || '-');
                        $('#ld-content-classification').text(lr.classification || '-');
                        $('#ld-content-category').text(lr.category || '-');
                        $('#ld-content-source').text(lr.source || '-');

                        if (d.source_type === 'playlist' && lr.playlist_name && lr.playlist_name !== '-') {
                            $('#ld-playlist-name').text(lr.playlist_name);
                            $('#ld-playlist-container').show();
                        } else {
                            $('#ld-playlist-container').hide();
                        }

                        // Progress Information
                        const pct = Math.max(0, Math.min(100, parseInt(pr.progress, 10) || 0));
                        $('#ld-progress-text').text(pct + '%');
                        $('#ld-progress-bar').css('width', pct + '%');

                        const isFinished = pr.finish === 'Finished';
                        $('#ld-finish-badge')
                            .text(pr.finish || '-')
                            .removeClass('bg-success-lt text-success bg-warning-lt text-warning')
                            .addClass(isFinished ? 'bg-success-lt text-success' : 'bg-warning-lt text-warning');

                        $('#ld-learning-status').text(pr.learning_status || '-');
                        $('#ld-status').text(pr.status || '-');
                        $('#ld-learning-hours').text(pr.learning_hours || '-');
                        $('#ld-duration').text(pr.duration || '-');
                        $('#ld-current-time').text(pr.current_time || '-');
                        $('#ld-actual-watch').text(pr.actual_watch_seconds || '-');
                        $('#ld-afk-duration').text(pr.afk_duration || '-');

                        // Timestamps
                        $('#ld-created-at').text(ts.created_at || '-');
                        $('#ld-updated-at').text(ts.updated_at || '-');
                        $('#ld-completed-at').text(ts.completed_at || '-');

                        // Transition to content view
                        $('#learningDetailLoading').hide();
                        $('#learningDetailContent').fadeIn(150);
                    } else {
                        $('#learningDetailLoading').hide();
                        $('#learningDetailErrorMessage').text((res && res.message) ? res.message : 'Unable to load learning record details.');
                        $('#learningDetailError').show();
                    }
                },
                error: function (xhr) {
                    $('#learningDetailLoading').hide();
                    let errMsg = 'Unable to load learning record details.';
                    if (xhr.responseJSON && xhr.responseJSON.message) {
                        errMsg = xhr.responseJSON.message;
                    }
                    $('#learningDetailErrorMessage').text(errMsg);
                    $('#learningDetailError').show();
                },
                complete: function () {
                    isFetchingRecordDetail = false;
                }
            });
        });

        // ── 8. Success Alert Notification Helper ──────────────────────────
        function showLearningAlert(message, type) {
            type = type || 'success';
            const alertId = 'learning-action-alert';
            let $alert = $('#' + alertId);
            if (!$alert.length) {
                $alert = $(`
                    <div id="${alertId}" class="alert alert-${type} alert-dismissible fade show mb-3 shadow-sm rounded-3" role="alert">
                        <div class="d-flex align-items-center">
                            <i class="ti ${type === 'success' ? 'ti-circle-check text-success' : 'ti-alert-circle text-danger'} fs-2 me-2"></i>
                            <div class="alert-message fw-semibold small"></div>
                            <button type="button" class="btn-close ms-auto" data-bs-dismiss="alert" aria-label="Close"></button>
                        </div>
                    </div>
                `);
                $('.card:has(#learning-table)').before($alert);
            } else {
                $alert.removeClass('alert-success alert-danger alert-warning').addClass('alert-' + type);
                $alert.find('i').removeClass('ti-circle-check text-success ti-alert-circle text-danger')
                    .addClass(type === 'success' ? 'ti-circle-check text-success' : 'ti-alert-circle text-danger');
            }
            $alert.find('.alert-message').text(message);
            $alert.show();
            setTimeout(function () {
                $alert.fadeOut(300);
            }, 4500);
        }

        // B. Edit Button Handler (Functional AJAX Edit Modal)
        let isFetchingEditRecord = false;

        $(document).on('click', '.btn-edit-learning-record', function (e) {
            e.preventDefault();

            if (isFetchingEditRecord) {
                return;
            }

            const sourceType = $(this).data('source-type');
            const sourceId = $(this).data('source-id');

            if (!sourceType || !sourceId) {
                return;
            }

            const modalEl = document.getElementById('learningEditModal');
            if (!modalEl) {
                return;
            }

            const modalInstance = (typeof bootstrap !== 'undefined' && bootstrap.Modal)
                ? (bootstrap.Modal.getInstance(modalEl) || new bootstrap.Modal(modalEl))
                : null;

            // Reset form states
            $('#edit-source-type').val(sourceType);
            $('#edit-source-id').val(sourceId);
            $('#edit-original-finish').val('0');
            $('#edit-duration-seconds').val('0');

            $('#learningEditSubmitError').hide().find('#learningEditSubmitErrorMessage').empty();
            $('#learningEditForm .form-control').removeClass('is-invalid');
            $('#learningEditForm .invalid-feedback').hide().empty();

            $('#learningEditLoading').show();
            $('#learningEditFetchError').hide();
            $('#learningEditContent').hide();

            $('#btn-save-learning-edit').prop('disabled', false);
            $('#edit-save-spinner').hide();
            $('#edit-save-text').text('Save Changes');

            if (modalInstance) {
                modalInstance.show();
            } else if ($.fn.modal) {
                $('#learningEditModal').modal('show');
            }

            isFetchingEditRecord = true;

            const detailUrl = "{{ url('manage/learning/record-detail') }}/" + encodeURIComponent(sourceType) + "/" + encodeURIComponent(sourceId);

            $.ajax({
                url: detailUrl,
                type: 'GET',
                dataType: 'json',
                headers: {
                    'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') || '{{ csrf_token() }}'
                },
                success: function (res) {
                    if (res && res.status === 'success' && res.data) {
                        const d = res.data;
                        const emp = d.employee || {};
                        const lr = d.learning || {};
                        const pr = d.progress || {};

                        // Store tracking metadata
                        $('#edit-source-type').val(d.source_type);
                        $('#edit-source-id').val(d.source_id);
                        const isFinished = (pr.is_finished || pr.finish === 'Finished' || parseInt(pr.progress, 10) >= 100);
                        $('#edit-original-finish').val(isFinished ? '1' : '0');

                        const durationSec = parseInt(pr.duration_seconds, 10) || 0;
                        $('#edit-duration-seconds').val(durationSec);

                        // Read-only context
                        $('#le-emp-id').text(emp.id_emp || '-');
                        $('#le-emp-name').text(emp.name || '-');
                        $('#le-emp-dept').text(emp.department || '-');

                        $('#le-content-title').text(lr.title || '-');
                        $('#le-content-type').text(lr.type || '-');
                        $('#le-content-classification').text(lr.classification || '-');
                        $('#le-content-category').text(lr.category || '-');
                        $('#le-content-source').text(lr.source || '-');
                        $('#le-content-duration').text(pr.duration || '-');

                        if (d.source_type === 'playlist' && lr.playlist_name && lr.playlist_name !== '-') {
                            $('#le-playlist-name').text(lr.playlist_name);
                            $('#le-playlist-container').show();
                        } else {
                            $('#le-playlist-container').hide();
                        }

                        // Editable tracking values
                        $('#edit-progress').val(pr.progress !== undefined ? parseInt(pr.progress, 10) : 0);
                        $('#edit-current-time').val(pr.raw_current_time !== undefined ? parseInt(pr.raw_current_time, 10) : 0);
                        $('#edit-actual-watch').val(pr.raw_actual_watch_seconds !== undefined ? parseInt(pr.raw_actual_watch_seconds, 10) : 0);
                        $('#edit-afk-duration').val(pr.raw_afk_duration_seconds !== undefined ? parseInt(pr.raw_afk_duration_seconds, 10) : 0);

                        if (durationSec > 0) {
                            $('#help-edit-current-time').text(`Position in seconds (maximum duration: ${durationSec}s).`);
                        } else {
                            $('#help-edit-current-time').text('Current position in seconds.');
                        }

                        $('#learningEditLoading').hide();
                        $('#learningEditContent').fadeIn(150);
                    } else {
                        $('#learningEditLoading').hide();
                        $('#learningEditFetchErrorMessage').text((res && res.message) ? res.message : 'Unable to load learning record data.');
                        $('#learningEditFetchError').show();
                    }
                },
                error: function (xhr) {
                    $('#learningEditLoading').hide();
                    let errMsg = 'Unable to load learning record data.';
                    if (xhr.responseJSON && xhr.responseJSON.message) {
                        errMsg = xhr.responseJSON.message;
                    }
                    $('#learningEditFetchErrorMessage').text(errMsg);
                    $('#learningEditFetchError').show();
                },
                complete: function () {
                    isFetchingEditRecord = false;
                }
            });
        });

        // Edit Form Submit Handler
        $('#learningEditForm').on('submit', function (e) {
            e.preventDefault();

            // Clear previous errors
            $('#learningEditSubmitError').hide().find('#learningEditSubmitErrorMessage').empty();
            $('#learningEditForm .form-control').removeClass('is-invalid');
            $('#learningEditForm .invalid-feedback').hide().empty();

            const sourceType = $('#edit-source-type').val();
            const sourceId = $('#edit-source-id').val();
            const originalFinish = $('#edit-original-finish').val();
            const durationSec = parseInt($('#edit-duration-seconds').val(), 10) || 0;

            const progressVal = $('#edit-progress').val().trim();
            const currentTimeVal = $('#edit-current-time').val().trim();
            const actualWatchVal = $('#edit-actual-watch').val().trim();
            const afkDurationVal = $('#edit-afk-duration').val().trim();

            const progress = parseInt(progressVal, 10);
            const currentTime = parseInt(currentTimeVal, 10);
            const actualWatch = parseInt(actualWatchVal, 10);
            const afkDuration = parseInt(afkDurationVal, 10);

            // Client Validation Checks
            let hasClientError = false;

            if (isNaN(progress) || progress < 0 || progress > 100) {
                $('#edit-progress').addClass('is-invalid');
                $('#err-edit-progress').text('Progress must be an integer between 0 and 100.').show();
                hasClientError = true;
            }

            if (isNaN(currentTime) || currentTime < 0) {
                $('#edit-current-time').addClass('is-invalid');
                $('#err-edit-current-time').text('Current time must be an integer greater than or equal to 0.').show();
                hasClientError = true;
            } else if (durationSec > 0 && currentTime > durationSec) {
                $('#edit-current-time').addClass('is-invalid');
                $('#err-edit-current-time').text(`Current time (${currentTime}s) cannot exceed content duration (${durationSec}s).`).show();
                hasClientError = true;
            }

            if (isNaN(actualWatch) || actualWatch < 0) {
                $('#edit-actual-watch').addClass('is-invalid');
                $('#err-edit-actual-watch').text('Actual watch seconds must be an integer greater than or equal to 0.').show();
                hasClientError = true;
            }

            if (isNaN(afkDuration) || afkDuration < 0) {
                $('#edit-afk-duration').addClass('is-invalid');
                $('#err-edit-afk-duration').text('AFK duration must be an integer greater than or equal to 0.').show();
                hasClientError = true;
            }

            if (hasClientError) {
                return;
            }

            // Safety Confirmation (Section 17): If previously finished and now reducing progress < 100
            if (originalFinish === '1' && progress < 100) {
                const confirmed = window.confirm(
                    "This record is currently completed. Reducing progress below 100% will reopen the learning record and may affect Learning Record and Learning Hours. Continue?"
                );
                if (!confirmed) {
                    return;
                }
            }

            // Lock save button and show loading state
            const $saveBtn = $('#btn-save-learning-edit');
            const $spinner = $('#edit-save-spinner');
            const $btnText = $('#edit-save-text');

            $saveBtn.prop('disabled', true);
            $spinner.show();
            $btnText.text('Saving Changes...');

            const updateUrl = "{{ url('manage/learning/record') }}/" + encodeURIComponent(sourceType) + "/" + encodeURIComponent(sourceId);

            $.ajax({
                url: updateUrl,
                type: 'POST',
                data: {
                    _method: 'PUT',
                    _token: '{{ csrf_token() }}',
                    progress: progress,
                    current_time: currentTime,
                    actual_watch_seconds: actualWatch,
                    afk_duration_seconds: afkDuration
                },
                dataType: 'json',
                headers: {
                    'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') || '{{ csrf_token() }}',
                    'Accept': 'application/json'
                },
                success: function (res) {
                    $saveBtn.prop('disabled', false);
                    $spinner.hide();
                    $btnText.text('Save Changes');

                    if (res && res.status === 'success') {
                        // 1. Close Edit Modal
                        const modalEl = document.getElementById('learningEditModal');
                        if (modalEl) {
                            if (typeof bootstrap !== 'undefined' && bootstrap.Modal) {
                                const inst = bootstrap.Modal.getInstance(modalEl);
                                if (inst) inst.hide();
                            }
                            if ($.fn.modal) {
                                $('#learningEditModal').modal('hide');
                            }
                        }

                        // 2. Display success feedback banner
                        showLearningAlert(res.message || 'Learning record updated successfully.', 'success');

                        // 3. Reload DataTable without resetting pagination
                        learningTable.ajax.reload(null, false);
                    } else {
                        const msg = (res && res.message) ? res.message : 'Failed to update learning record.';
                        $('#learningEditSubmitErrorMessage').text(msg);
                        $('#learningEditSubmitError').show();
                    }
                },
                error: function (xhr) {
                    $saveBtn.prop('disabled', false);
                    $spinner.hide();
                    $btnText.text('Save Changes');

                    if (xhr.status === 422 && xhr.responseJSON && xhr.responseJSON.errors) {
                        const errors = xhr.responseJSON.errors;
                        let errorSummary = [];

                        if (errors.progress) {
                            $('#edit-progress').addClass('is-invalid');
                            $('#err-edit-progress').text(errors.progress[0]).show();
                            errorSummary.push(errors.progress[0]);
                        }
                        if (errors.current_time) {
                            $('#edit-current-time').addClass('is-invalid');
                            $('#err-edit-current-time').text(errors.current_time[0]).show();
                            errorSummary.push(errors.current_time[0]);
                        }
                        if (errors.actual_watch_seconds) {
                            $('#edit-actual-watch').addClass('is-invalid');
                            $('#err-edit-actual-watch').text(errors.actual_watch_seconds[0]).show();
                            errorSummary.push(errors.actual_watch_seconds[0]);
                        }
                        if (errors.afk_duration_seconds) {
                            $('#edit-afk-duration').addClass('is-invalid');
                            $('#err-edit-afk-duration').text(errors.afk_duration_seconds[0]).show();
                            errorSummary.push(errors.afk_duration_seconds[0]);
                        }

                        const mainMsg = xhr.responseJSON.message || 'Please correct the validation errors below.';
                        $('#learningEditSubmitErrorMessage').html(
                            `<strong>${mainMsg}</strong>` + (errorSummary.length ? `<br>${errorSummary.join('<br>')}` : '')
                        );
                        $('#learningEditSubmitError').show();
                    } else {
                        let errMsg = 'Failed to update learning record. Please try again.';
                        if (xhr.responseJSON && xhr.responseJSON.message) {
                            errMsg = xhr.responseJSON.message;
                        }
                        $('#learningEditSubmitErrorMessage').text(errMsg);
                        $('#learningEditSubmitError').show();
                    }
                }
            });
        });

        // C. Reset Learning Progress Handler (Functional Reset Modal)
        let isFetchingResetRecord = false;

        $(document).on('click', '.btn-delete-learning-record, .btn-reset-learning-record', function (e) {
            e.preventDefault();

            if (isFetchingResetRecord) {
                return;
            }

            const sourceType = $(this).data('source-type');
            const sourceId = $(this).data('source-id');

            if (!sourceType || !sourceId) {
                return;
            }

            const modalEl = document.getElementById('learningResetModal');
            if (!modalEl) {
                return;
            }

            const modalInstance = (typeof bootstrap !== 'undefined' && bootstrap.Modal)
                ? (bootstrap.Modal.getInstance(modalEl) || new bootstrap.Modal(modalEl))
                : null;

            // Reset modal state
            $('#reset-source-type').val(sourceType);
            $('#reset-source-id').val(sourceId);

            $('#learningResetSubmitError').hide().find('#learningResetSubmitErrorMessage').empty();
            $('#learningResetFetchError').hide();
            $('#learningResetContent').hide();
            $('#learningResetLoading').show();

            $('#lr-completed-warning').hide();
            $('#lr-playlist-warning').hide();

            const $confirmBtn = $('#btn-confirm-learning-reset');
            $confirmBtn.prop('disabled', false);
            $('#reset-confirm-spinner').hide();
            $('#reset-confirm-text').text('Reset Progress');

            if (modalInstance) {
                modalInstance.show();
            } else if ($.fn.modal) {
                $('#learningResetModal').modal('show');
            }

            isFetchingResetRecord = true;

            const detailUrl = "{{ url('manage/learning/record-detail') }}/" + encodeURIComponent(sourceType) + "/" + encodeURIComponent(sourceId);

            $.ajax({
                url: detailUrl,
                type: 'GET',
                dataType: 'json',
                headers: {
                    'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') || '{{ csrf_token() }}'
                },
                success: function (res) {
                    if (res && res.status === 'success' && res.data) {
                        const d = res.data;
                        const emp = d.employee || {};
                        const lr = d.learning || {};
                        const pr = d.progress || {};

                        // Store tracking metadata
                        $('#reset-source-type').val(d.source_type);
                        $('#reset-source-id').val(d.source_id);

                        // Populate Target Record details
                        $('#lr-emp-id').text(emp.id_emp ? `NIP: ${emp.id_emp}` : '-');
                        $('#lr-emp-name').text(emp.name || '-');
                        $('#lr-content-title').text(lr.title || '-');
                        $('#lr-content-source').text(lr.source || (d.source_type === 'playlist' ? 'Playlist' : 'Direct Content'));

                        if (d.source_type === 'playlist' && lr.playlist_name && lr.playlist_name !== '-') {
                            $('#lr-playlist-name').text(lr.playlist_name);
                            $('#lr-playlist-container').show();
                            $('#lr-playlist-warning').show();
                        } else {
                            $('#lr-playlist-container').hide();
                            $('#lr-playlist-warning').hide();
                        }

                        const progressVal = parseInt(pr.progress, 10) || 0;
                        $('#lr-current-progress').text(progressVal + '%');
                        $('#lr-learning-hours').text(pr.learning_hours || '0.0000 h');

                        const isFinished = (pr.is_finished || pr.finish === 'Finished' || progressVal >= 100);
                        if (isFinished) {
                            $('#lr-status-badge').removeClass('bg-warning-lt bg-secondary-lt').addClass('bg-success-lt').text('Finished');
                            $('#lr-completed-warning').show();
                        } else {
                            $('#lr-status-badge').removeClass('bg-success-lt bg-secondary-lt').addClass('bg-warning-lt').text(pr.learning_status || 'In Progress');
                            $('#lr-completed-warning').hide();
                        }

                        $('#learningResetLoading').hide();
                        $('#learningResetContent').fadeIn(150);
                    } else {
                        $('#learningResetLoading').hide();
                        $('#learningResetFetchErrorMessage').text((res && res.message) ? res.message : 'Unable to load learning record data.');
                        $('#learningResetFetchError').show();
                    }
                },
                error: function (xhr) {
                    $('#learningResetLoading').hide();
                    let errMsg = 'Unable to load learning record data.';
                    if (xhr.responseJSON && xhr.responseJSON.message) {
                        errMsg = xhr.responseJSON.message;
                    }
                    $('#learningResetFetchErrorMessage').text(errMsg);
                    $('#learningResetFetchError').show();
                },
                complete: function () {
                    isFetchingResetRecord = false;
                }
            });
        });

        // Reset Confirm Button Click Handler
        $('#btn-confirm-learning-reset').on('click', function (e) {
            e.preventDefault();

            const sourceType = $('#reset-source-type').val();
            const sourceId = $('#reset-source-id').val();

            if (!sourceType || !sourceId) {
                return;
            }

            const $btn = $(this);
            const $spinner = $('#reset-confirm-spinner');
            const $btnText = $('#reset-confirm-text');

            $btn.prop('disabled', true);
            $spinner.show();
            $btnText.text('Resetting...');
            $('#learningResetSubmitError').hide().find('#learningResetSubmitErrorMessage').empty();

            const resetUrl = "{{ url('manage/learning/record') }}/" + encodeURIComponent(sourceType) + "/" + encodeURIComponent(sourceId) + "/reset";

            $.ajax({
                url: resetUrl,
                type: 'POST',
                data: {
                    _token: '{{ csrf_token() }}'
                },
                dataType: 'json',
                headers: {
                    'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') || '{{ csrf_token() }}',
                    'Accept': 'application/json'
                },
                success: function (res) {
                    $btn.prop('disabled', false);
                    $spinner.hide();
                    $btnText.text('Reset Progress');

                    if (res && res.status === 'success') {
                        // 1. Close Reset Modal
                        const modalEl = document.getElementById('learningResetModal');
                        if (modalEl) {
                            if (typeof bootstrap !== 'undefined' && bootstrap.Modal) {
                                const inst = bootstrap.Modal.getInstance(modalEl);
                                if (inst) inst.hide();
                            }
                            if ($.fn.modal) {
                                $('#learningResetModal').modal('hide');
                            }
                        }

                        // 2. Display success feedback banner
                        showLearningAlert(res.message || 'Learning progress has been reset successfully.', 'success');

                        // 3. Reload DataTable without resetting pagination
                        learningTable.ajax.reload(null, false);
                    } else {
                        const msg = (res && res.message) ? res.message : 'Failed to reset learning progress.';
                        $('#learningResetSubmitErrorMessage').text(msg);
                        $('#learningResetSubmitError').show();
                    }
                },
                error: function (xhr) {
                    $btn.prop('disabled', false);
                    $spinner.hide();
                    $btnText.text('Reset Progress');

                    let errMsg = 'Failed to reset learning progress. Please try again.';
                    if (xhr.responseJSON && xhr.responseJSON.message) {
                        errMsg = xhr.responseJSON.message;
                    }
                    $('#learningResetSubmitErrorMessage').text(errMsg);
                    $('#learningResetSubmitError').show();
                }
            });
        });
    });
</script>
@endonce