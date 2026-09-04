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

            if (results && results.length > 0) {
                results.forEach(item => {
                    const label = item.label || item.name || item.title || item.text || '';
                    const val = isEmployee ? (item.value || item.id || label) : (item.value || item.title || item.id || label);
                    const subtext = item.subtext || (item.category ? item.category : '');

                    $list.append(`
                        <a href="javascript:void(0);" class="list-group-item list-group-item-action text-truncate py-2 px-3 small border-0 select-autocomplete-item" data-input="${inputId}" data-value="${val}" data-label="${label}">
                            <div class="fw-semibold text-dark text-truncate">${label}</div>
                            ${subtext ? `<div class="text-muted extra-small text-truncate" style="font-size: 0.75rem;">${subtext}</div>` : ''}
                        </a>
                    `);
                });

                // Attach "See all results" footer link
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
        $('#manage-employee-input').on('input', function () {
            const query = $(this).val().trim();
            clearTimeout(employeeDebounceTimer);

            if (query.length === 0) {
                $('#manage-employee-dropdown').hide();
                $('#learn-employee').val('');
                return;
            }

            employeeDebounceTimer = setTimeout(function () {
                $.ajax({
                    url: "{{ route('learning.manage.search-employees') }}",
                    type: 'GET',
                    data: { q: query, search: query, employee_q: query },
                    dataType: 'json',
                    success: function (data) {
                        const items = data.results || data || [];
                        renderAutocompleteResults('manage-employee-input', 'manage-employee-dropdown', 'manage-employee-list', 'manage-employee-footer', items, true);
                    },
                    error: function () {
                        $('#manage-employee-dropdown').hide();
                    }
                });
            }, 250);
        });

        // ── 1c. Learning Title Live Autocomplete Listener ───────────────────
        let titleDebounceTimer = null;
        $('#manage-title-input').on('input', function () {
            const query = $(this).val().trim();
            clearTimeout(titleDebounceTimer);

            if (query.length === 0) {
                $('#manage-title-dropdown').hide();
                $('#learn-title').val('');
                return;
            }

            titleDebounceTimer = setTimeout(function () {
                $.ajax({
                    url: "{{ route('learning.manage.search-titles') }}",
                    type: 'GET',
                    data: { q: query, search: query, title_q: query },
                    dataType: 'json',
                    success: function (data) {
                        const items = data.results || data || [];
                        renderAutocompleteResults('manage-title-input', 'manage-title-dropdown', 'manage-title-list', 'manage-title-footer', items, false);
                    },
                    error: function () {
                        $('#manage-title-dropdown').hide();
                    }
                });
            }, 250);
        });

        // ── 1d. Autocomplete Selection Handlers ─────────────────────────────
        $(document).on('click', '.select-autocomplete-item', function (e) {
            e.preventDefault();
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
            order: [[6, 'desc']],
            dom: '<"table-responsive"t><"d-flex flex-wrap align-items-center justify-content-between p-3"ip>',
            ajax: {
                url: "{{ route('learning.manage.datatable') }}",
                type: 'GET',
                headers: {
                    'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') || '{{ csrf_token() }}'
                },
                data: function (d) {
                    d._token = '{{ csrf_token() }}';
                    d.employee_id = $('#learn-employee').val() || '';
                    d.learning_title = $('#learn-title').val() || '';
                    d.search = $('#learn-title').val() || '';
                    d.status = $('#learn-status').val();
                    d.year = $('#learn-year').val();
                    d.classification_slug = $('#classification-slug').val();
                    d.content_slug = $('#content-slug').val();
                    d.from_date = $('#filter-from-date').val();
                    d.to_date = $('#filter-to-date').val();
                    d.program_type = $('#filter-program-type').val();
                    d.progress_range = $('#filter-progress-range').val();
                    d.duration = $('#filter-duration').val();
                }
            },
            columns: [
                { data: 'DT_RowIndex', name: 'DT_RowIndex', orderable: false, searchable: false },
                { data: 'status_badge', name: 'status' },
                { data: 'employee', name: 'user.name' },
                { data: 'learning_title', name: 'content.title' },
                { data: 'classification_name', name: 'content.classification.classification_name' },
                { data: 'duration', name: 'content.duration' },
                { data: 'learning_date', name: 'created_at' },
                { data: 'progress_bar', name: 'progress', orderable: false, searchable: false }
            ],
            language: {
                processing: '<div class="py-4 text-center"><div class="spinner-border text-primary" role="status"></div><div class="mt-2 text-muted">Loading learning data...</div></div>'
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
            updateUrlParams({
                employee_id: $('#learn-employee').val() || null,
                learning_title: $('#learn-title').val() || null,
                status: $('#learn-status').val() !== 'all' ? $('#learn-status').val() : null,
                year: $('#learn-year').val() !== 'all' ? $('#learn-year').val() : null,
            });
            learningTable.ajax.reload(null, true);
        });

        $('#learn-title').on('keypress', function (e) {
            if (e.which === 13) {
                e.preventDefault();
                $('#btn-apply-filter').trigger('click');
            }
        });

        $('#learn-entries').on('change', function () {
            const nextLength = parseInt($(this).val(), 10) || defaultEntries;
            learningTable.page.len(nextLength).draw();
        });

        // ── 4. More Filters Modal Events ──────────────────────────────────
        $('#btn-modal-apply').on('click', function () {
            // 1. Gather all active field values from modal inputs
            const fromDate = $('#modal-from-date').val() || '';
            const toDate = $('#modal-to-date').val() || '';
            const programType = $('input[name="modal_program_type"]:checked').val() || 'all';
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

            // 6. Reload DataTable with page reset
            learningTable.ajax.reload(null, true);
        });

        $('#btn-modal-reset').on('click', function () {
            // 1. Clear modal input selections back to default
            $('#modal-from-date').val('');
            $('#modal-to-date').val('');
            $('input[name="modal_program_type"][value="all"]').prop('checked', true);
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

            // 6. Reload DataTable without modal filter constraints
            learningTable.ajax.reload(null, true);
        });

        // ── 5. Full Filter Reset Event ────────────────────────────────────
        $('#btn-reset-filter').on('click', function () {
            // Reset Employee Search input & hidden value
            $('#manage-employee-input').val('');
            $('#learn-employee').val('');
            $('#manage-employee-dropdown').hide();

            // Reset Learning Title Search input & hidden value
            $('#manage-title-input').val('');
            $('#learn-title').val('');
            $('#manage-title-dropdown').hide();

            // Reset Quick Filter dropdowns
            $('#learn-status').val('all');
            $('#learn-year').val('all');
            $('#learn-entries').val(defaultEntries);

            // Reset Modal inputs & hidden filter inputs
            $('#modal-from-date, #filter-from-date').val('');
            $('#modal-to-date, #filter-to-date').val('');
            $('input[name="modal_program_type"][value="all"]').prop('checked', true);
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

            learningTable.page.len(defaultEntries).draw();
        });

        // ── 6. Filtered Data Download Handler (Export Excel) ─────────────
        $('#btn-download-export').on('click', function (e) {
            e.preventDefault();
            const params = new URLSearchParams();

            const empVal = $('#learn-employee').val();
            if (empVal) params.append('employee_id', empVal);

            const titleVal = $('#learn-title').val();
            if (titleVal) params.append('learning_title', titleVal);

            const statusVal = $('#learn-status').val();
            if (statusVal && statusVal !== 'all') params.append('status', statusVal);

            const yearVal = $('#learn-year').val();
            if (yearVal && yearVal !== 'all') params.append('year', yearVal);

            const classSlug = $('#classification-slug').val();
            if (classSlug) params.append('classification_slug', classSlug);

            const contentSlug = $('#content-slug').val();
            if (contentSlug) params.append('content_slug', contentSlug);

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

            params.append('action', 'download');

            const downloadUrl = "{{ route('manage.learning.download') }}?" + params.toString();
            window.open(downloadUrl, '_blank');
        });
    });
</script>
@endonce