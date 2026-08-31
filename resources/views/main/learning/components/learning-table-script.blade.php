@once
<script src="{{ asset('js/jquery.dataTables.min.js') }}"></script>
<script src="{{ asset('js/dataTables.bootstrap5.min.js') }}"></script>

<script>
    $(function () {
        const initialSearch = @json($search ?? '');
        const defaultEntries = @json((int) ($entries ?? 25));

        // ── 1. Initialize Live Search Dropdown (Select2 AJAX) ─────────────
        if ($.fn.select2) {
            $('#learn-search').select2({
                theme: 'bootstrap-5',
                allowClear: true,
                placeholder: $('#learn-search').data('placeholder') || 'Search employee, NIP, or title...',
                width: '100%',
                ajax: {
                    url: "{{ route('learning.manage.search-employees') }}",
                    dataType: 'json',
                    delay: 300,
                    headers: {
                        'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') || '{{ csrf_token() }}'
                    },
                    data: function (params) {
                        return {
                            _token: '{{ csrf_token() }}',
                            q: params.term || '',
                            page: params.page || 1
                        };
                    },
                    processResults: function (data) {
                        return {
                            results: data.results || []
                        };
                    },
                    cache: true
                },
                templateResult: function (item) {
                    if (!item.id) return item.text;
                    return $('<span>').text(item.text);
                },
                templateSelection: function (item) {
                    return item.text || item.id;
                },
                minimumInputLength: 0
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
                    d.search = $('#learn-search').val() || '';
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
            learningTable.ajax.reload(null, true);
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
            // Reset Select2 live search
            if ($.fn.select2 && $('#learn-search').data('select2')) {
                $('#learn-search').val(null).trigger('change');
            } else {
                $('#learn-search').val('');
            }

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

            const searchVal = $('#learn-search').val();
            if (searchVal) params.append('search', searchVal);

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