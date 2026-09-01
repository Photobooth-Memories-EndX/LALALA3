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
});
</script>
