<script src="{{ asset('js/jquery.dataTables.min.js') }}"></script>
<script src="{{ asset('js/dataTables.bootstrap5.min.js') }}"></script>

<script>
$(function () {
    'use strict';

    const datatableUrl = "{{ route('manage.learning-hours.playlist-content-user.datatable') }}";

    const table = $('#playlist-content-user-lh-table').DataTable({
        processing: true,
        serverSide: true,
        pageLength: 20,
        lengthMenu: [20, 50, 100],
        order: [[8, 'desc']], // Default order by Actual Learning Hour (Numeric)
        autoWidth: false,
        ajax: {
            url: datatableUrl,
            type: 'GET',
            data: function (d) {
                d.trainingplan_id = $('#filter-playlist').val();
                d.calculation_source = $('#filter-calculation-source').val();
                d.finish_status = $('#filter-finish-status').val();
                d.content_type = $('#filter-type').val();
                d.classification_slug = $('#filter-classification').val();
                d.year = $('#filter-year').val();
                d.min_hour = $('#filter-min-hour').val();
                d.max_hour = $('#filter-max-hour').val();
            }
        },
        columns: [
            { data: 'DT_RowIndex', name: 'id', orderable: false, searchable: false, className: 'text-center' },
            { data: 'employee', name: 'user.name' },
            { data: 'playlist_title', name: 'trainingPlan.title' },
            { data: 'content_title', name: 'content.title' },
            { data: 'content_type', name: 'content.type', className: 'text-center' },
            { data: 'progress_bar', name: 'progress' },
            { data: 'current_time_display', name: 'current_time' },
            { data: 'duration', name: 'content.duration', orderable: false, className: 'text-center' },
            { data: 'actual_learning_hour', name: 'actual_learning_hour', className: 'text-center font-monospace' },
            { data: 'master_learning_hour', name: 'master_learning_hour', orderable: false, className: 'text-center font-monospace' },
            { data: 'calculation_source', name: 'calculation_source', orderable: false, className: 'text-center' },
            { data: 'status_badge', name: 'finish', className: 'text-center' },
            { data: 'completed_at', name: 'completed_at', className: 'text-nowrap' }
        ],
        language: {
            processing: '<div class="spinner-border spinner-border-sm text-primary me-2"></div> Loading records...',
            search: "_INPUT_",
            searchPlaceholder: "Search user, playlist, content...",
            lengthMenu: "Show _MENU_ entries",
            info: "Showing _START_ to _END_ of _TOTAL_ entries",
            infoEmpty: "No matching records found",
            emptyTable: "No playlist content learning hours available"
        }
    });

    $('#btn-apply-filters').on('click', function () {
        table.ajax.reload();
    });

    $('#filter-form select, #filter-form input').on('change', function () {
        table.ajax.reload();
    });

    $('#btn-reset-filters').on('click', function () {
        $('#filter-form')[0].reset();
        $('#filter-playlist').val('all');
        $('#filter-calculation-source').val('all');
        $('#filter-finish-status').val('all');
        $('#filter-type').val('all');
        $('#filter-classification').val('all');
        $('#filter-year').val('all');
        $('#filter-min-hour').val('');
        $('#filter-max-hour').val('');
        table.ajax.reload();
    });

    $('#btn-refresh-table').on('click', function () {
        table.ajax.reload(null, false);
    });

    // Bulk Calculate Modal Handlers
    const calculateUrl = "{{ route('manage.learning-hours.playlist-content-user.calculate') }}";
    const $calcModal = $('#modal-bulk-calculate');
    const calcModalInstance = (typeof bootstrap !== 'undefined' && bootstrap.Modal)
        ? new bootstrap.Modal(document.getElementById('modal-bulk-calculate'))
        : null;

    $('#btn-bulk-calculate').on('click', function () {
        $('#calc-confirm-state').removeClass('d-none');
        $('#calc-loading-state').addClass('d-none');
        $('#calc-result-state').addClass('d-none');
        $('#calc-dry-run').prop('checked', false);
        $('#calc-force').prop('checked', false);
        $('#calc-mode-all').prop('checked', true);

        if (calcModalInstance) {
            calcModalInstance.show();
        } else {
            $calcModal.modal('show');
        }
    });

    $('#btn-start-calculate').on('click', function () {
        const mode = $('input[name="calc_mode"]:checked').val() || 'all';
        const dryRun = $('#calc-dry-run').is(':checked') ? 1 : 0;
        const force = $('#calc-force').is(':checked') ? 1 : 0;

        const payload = {
            _token: '{{ csrf_token() }}',
            mode: mode,
            dry_run: dryRun,
            force: force,
        };

        if (mode === 'filtered') {
            payload.trainingplan_id = $('#filter-playlist').val();
            payload.calculation_source = $('#filter-calculation-source').val();
            payload.finish_status = $('#filter-finish-status').val();
            payload.content_type = $('#filter-type').val();
            payload.classification_slug = $('#filter-classification').val();
            payload.year = $('#filter-year').val();
            payload.min_hour = $('#filter-min-hour').val();
            payload.max_hour = $('#filter-max-hour').val();
        }

        // Switch to Loading State
        $('#calc-confirm-state').addClass('d-none');
        $('#calc-loading-state').removeClass('d-none');
        $('#calc-result-state').addClass('d-none');
        $calcModal.find('.btn-close').prop('disabled', true);
        $('#btn-bulk-calculate').prop('disabled', true).html('<span class="spinner-border spinner-border-sm me-1"></span> Calculating...');

        $.ajax({
            url: calculateUrl,
            method: 'POST',
            data: payload,
            success: function (res) {
                if (res.success && res.summary) {
                    const s = res.summary;
                    $('#res-mode').text(s.mode === 'all' ? 'All Records' : 'Filtered Records');
                    $('#res-dry-run').html(s.dry_run ? '<span class="badge bg-azure-lt text-azure">YES (Preview Only)</span>' : '<span class="badge bg-green-lt text-green">NO (Database Updated)</span>');
                    $('#res-scanned').text(Number(s.total_scanned).toLocaleString());
                    $('#res-updated').text(Number(s.total_updated).toLocaleString());
                    $('#res-skipped').text(Number(s.total_skipped).toLocaleString());
                    $('#res-concurrency-skipped').text(Number(s.total_concurrency_skipped || s.total_concurrent_changed || 0).toLocaleString());
                    $('#res-fallback').text(Number(s.total_historical_fallback).toLocaleString());
                    $('#res-batches').text(Number(s.total_batches).toLocaleString());
                    $('#res-failed').text(Number(s.total_failed).toLocaleString());
                    $('#res-duration').text(s.duration_seconds + 's');

                    $('#calc-result-alert').text(res.message);
                    if (s.dry_run) {
                        $('#calc-result-title').html('<i class="ti ti-info-circle me-1"></i> Dry Run Completed');
                        $('#calc-result-alert').removeClass('alert-success bg-success-lt').addClass('alert-info bg-info-lt');
                    } else {
                        $('#calc-result-title').html('<i class="ti ti-circle-check me-1"></i> Bulk Calculation Completed');
                        $('#calc-result-alert').removeClass('alert-info bg-info-lt').addClass('alert-success bg-success-lt');
                    }

                    // Update Top Summary Cards if returned
                    if (res.statistics) {
                        const st = res.statistics;
                        $('#stat-total-records').text(Number(st.total_records).toLocaleString());
                        $('#stat-total-hours').text(Number(st.total_learning_hours).toLocaleString(undefined, {minimumFractionDigits: 2, maximumFractionDigits: 2}) + ' h');
                        $('#stat-actual-tracking').text(Number(st.actual_tracking_count).toLocaleString());
                        $('#stat-historical-fallback').text(Number(st.historical_fallback_count).toLocaleString());
                    }

                    // Switch to Result State
                    $('#calc-loading-state').addClass('d-none');
                    $('#calc-result-state').removeClass('d-none');

                    // Refresh table
                    table.ajax.reload(null, false);
                } else {
                    alert(res.message || 'Bulk calculation encountered an error.');
                    $('#calc-loading-state').addClass('d-none');
                    $('#calc-confirm-state').removeClass('d-none');
                }
            },
            error: function (xhr) {
                const msg = xhr.responseJSON?.message || 'Calculation request failed. Please check network/server logs.';
                alert(msg);
                $('#calc-loading-state').addClass('d-none');
                $('#calc-confirm-state').removeClass('d-none');
            },
            complete: function () {
                $calcModal.find('.btn-close').prop('disabled', false);
                $('#btn-bulk-calculate').prop('disabled', false).html('<i class="ti ti-calculator me-1"></i> Bulk Calculate Learning Hours');
            }
        });
    });

    function initTooltips() {
        if (typeof bootstrap !== 'undefined' && bootstrap.Tooltip) {
            var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
            tooltipTriggerList.map(function (tooltipTriggerEl) {
                return new bootstrap.Tooltip(tooltipTriggerEl);
            });
        }
    }

    initTooltips();
    table.on('draw', function () {
        initTooltips();
    });

    $(window).on('resize', function () {
        table.columns.adjust();
    });
});
</script>
