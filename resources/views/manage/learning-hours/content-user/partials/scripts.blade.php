@once
<script src="{{ asset('js/jquery.dataTables.min.js') }}"></script>
<script src="{{ asset('js/dataTables.bootstrap5.min.js') }}"></script>

<script>
$(function () {
    let selectedIds = new Set();
    let currentBatchId = null;
    let isBatchRunning = false;

    const table = $('#content-user-lh-table').DataTable({
        processing: true,
        serverSide: true,
        responsive: true,
        autoWidth: false,
        pageLength: 25,
        lengthMenu: [
            [25, 50, 100, 250],
            [25, 50, 100, 250]
        ],
        order: [[8, 'desc']], // order by updated_at by default
        ajax: {
            url: "{{ route('manage.learning-hours.content-user.datatable') }}",
            data: function (d) {
                d.learning_hours_status = $('#filter-lh-status').val();
                d.status = $('#filter-status').val();
                d.content_type = $('#filter-type').val();
                d.classification_slug = $('#filter-classification').val();
            }
        },
        columns: [
            { data: 'checkbox', name: 'checkbox', orderable: false, searchable: false, className: 'text-center' },
            { data: 'DT_RowIndex', name: 'DT_RowIndex', orderable: false, searchable: false },
            { data: 'employee', name: 'user.name' },
            { data: 'content_title', name: 'content.title' },
            { data: 'content_type', name: 'content.type' },
            { data: 'duration', name: 'content.duration' },
            { data: 'learning_hours_badge', name: 'content.learning_hours' },
            { data: 'status_badge', name: 'status' },
            { data: 'calculated_date', name: 'updated_at' },
            { data: 'action', name: 'action', orderable: false, searchable: false, className: 'text-center' }
        ],
        language: {
            processing: '<div class="py-4 text-center"><div class="spinner-border text-primary" role="status"></div><div class="mt-2 text-muted">Loading learning hours data...</div></div>'
        },
        drawCallback: function () {
            // Restore checkbox state
            $('.record-checkbox').each(function () {
                const id = $(this).val();
                if (selectedIds.has(id)) {
                    $(this).prop('checked', true);
                }
            });
            updateBulkButtons();
        }
    });

    // Checkbox selection
    $('#check-all').on('change', function () {
        const isChecked = $(this).is(':checked');
        $('.record-checkbox').each(function () {
            const id = $(this).val();
            $(this).prop('checked', isChecked);
            if (isChecked) {
                selectedIds.add(id);
            } else {
                selectedIds.delete(id);
            }
        });
        updateBulkButtons();
    });

    $(document).on('change', '.record-checkbox', function () {
        const id = $(this).val();
        if ($(this).is(':checked')) {
            selectedIds.add(id);
        } else {
            selectedIds.delete(id);
            $('#check-all').prop('checked', false);
        }
        updateBulkButtons();
    });

    function updateBulkButtons() {
        const count = selectedIds.size;
        $('#selected-count').text(count);
        $('#btn-calculate-selected').prop('disabled', count === 0);
        $('#btn-recalculate-selected').prop('disabled', count === 0);
    }

    // Apply & Reset Filters
    $('#btn-apply-filters').on('click', function () {
        selectedIds.clear();
        updateBulkButtons();
        table.ajax.reload();
    });

    $('#btn-reset-filters').on('click', function () {
        $('#filter-form')[0].reset();
        $('#filter-lh-status').val('all');
        $('#filter-status').val('all');
        $('#filter-type').val('all');
        $('#filter-classification').val('all');
        selectedIds.clear();
        updateBulkButtons();
        table.search('').draw();
    });

    // Calculate Selected
    $('#btn-calculate-selected').on('click', function () {
        if (selectedIds.size === 0) return;
        processSelected("{{ route('manage.learning-hours.content-user.calculate-selected') }}", 'Calculate Selected');
    });

    // Recalculate Selected
    $('#btn-recalculate-selected').on('click', function () {
        if (selectedIds.size === 0) return;
        processSelected("{{ route('manage.learning-hours.content-user.recalculate-selected') }}", 'Recalculate Selected');
    });

    function processSelected(url, actionTitle) {
        const ids = Array.from(selectedIds);
        const $btn = $(event.currentTarget);
        const originalHtml = $btn.html();

        $btn.prop('disabled', true).html('<span class="spinner-border spinner-border-sm me-1"></span> Processing...');

        $.ajax({
            url: url,
            method: 'POST',
            data: {
                _token: '{{ csrf_token() }}',
                ids: ids
            },
            success: function (res) {
                if (res.success) {
                    selectedIds.clear();
                    updateBulkButtons();
                    $('#check-all').prop('checked', false);
                    updateStatsUI(res.statistics);
                    table.ajax.reload(null, false);
                    if (window.Swal) {
                        Swal.fire({
                            icon: 'success',
                            title: 'Success',
                            text: res.message,
                            timer: 3000,
                            showConfirmButton: false
                        });
                    } else {
                        alert(res.message);
                    }
                }
            },
            error: function (xhr) {
                const msg = xhr.responseJSON?.message || 'Calculation request failed.';
                alert(msg);
            },
            complete: function () {
                $btn.prop('disabled', false).html(originalHtml);
                updateBulkButtons();
            }
        });
    }

    // Recalculate Single Row
    $(document).on('click', '.btn-recalculate-single', function () {
        const id = $(this).data('id');
        const $btn = $(this);
        const originalHtml = $btn.html();

        $btn.prop('disabled', true).html('<span class="spinner-border spinner-border-sm"></span>');

        $.ajax({
            url: "{{ route('manage.learning-hours.content-user.recalculate-selected') }}",
            method: 'POST',
            data: {
                _token: '{{ csrf_token() }}',
                ids: [id]
            },
            success: function (res) {
                if (res.success) {
                    updateStatsUI(res.statistics);
                    table.ajax.reload(null, false);
                }
            },
            error: function (xhr) {
                alert(xhr.responseJSON?.message || 'Failed to recalculate record.');
            },
            complete: function () {
                $btn.prop('disabled', false).html(originalHtml);
            }
        });
    });

    // Bulk Batch Calculation (Calculate All Remaining & Recalculate All)
    $('#btn-calculate-all-remaining').on('click', function () {
        startBulkBatch('all_remaining', 'Calculate All Remaining Learning Hours');
    });

    $('#btn-recalculate-all').on('click', function () {
        if (!confirm('Are you sure you want to recalculate learning hours for ALL 160K+ Content User records?')) {
            return;
        }
        startBulkBatch('recalculate_all', 'Recalculate All Learning Hours');
    });

    function startBulkBatch(type, heading) {
        if (isBatchRunning) return;

        $('#batch-modal-heading').text(heading);
        $('#batch-modal-icon').html('<i class="ti ti-hourglass-high fs-1"></i>');
        $('#batch-progress-bar').removeClass('bg-success bg-danger').addClass('bg-primary').css('width', '0%');
        $('#batch-progress-text').text('0%');
        $('#batch-processed-count').text('0');
        $('#batch-total-count').text('Calculating...');
        $('#batch-status-text').text('Initializing batch...');
        $('#btn-cancel-batch').hide();

        const modal = new bootstrap.Modal(document.getElementById('modal-batch-calculation'));
        modal.show();

        isBatchRunning = true;

        $.ajax({
            url: "{{ route('manage.learning-hours.content-user.batch.start') }}",
            method: 'POST',
            data: {
                _token: '{{ csrf_token() }}',
                type: type
            },
            success: function (res) {
                if (res.success) {
                    currentBatchId = res.batch_id;
                    $('#batch-total-count').text(res.total_records.toLocaleString());
                    if (res.total_records === 0) {
                        finishBatchUI(true, 'No records require calculation. All records are up to date.');
                    } else {
                        processNextChunk();
                    }
                }
            },
            error: function (xhr) {
                finishBatchUI(false, xhr.responseJSON?.message || 'Failed to start calculation batch.');
            }
        });
    }

    function processNextChunk() {
        if (!currentBatchId || !isBatchRunning) return;

        $.ajax({
            url: "{{ route('manage.learning-hours.content-user.batch.process') }}",
            method: 'POST',
            data: {
                _token: '{{ csrf_token() }}',
                batch_id: currentBatchId
            },
            success: function (res) {
                if (res.success) {
                    const pct = (res.percentage || 0).toFixed(1);
                    $('#batch-progress-bar').css('width', pct + '%');
                    $('#batch-progress-text').text(pct + '%');
                    $('#batch-processed-count').text((res.processed || 0).toLocaleString());
                    $('#batch-status-text').text(res.is_completed ? 'Completed' : 'Processing next chunk...');

                    if (res.is_completed) {
                        finishBatchUI(true, 'Bulk calculation completed successfully!');
                        if (res.statistics) {
                            updateStatsUI(res.statistics);
                        }
                    } else {
                        // Recursively trigger next chunk after short tick
                        setTimeout(processNextChunk, 200);
                    }
                } else {
                    finishBatchUI(false, res.message || 'Batch step encountered an error.');
                }
            },
            error: function (xhr) {
                finishBatchUI(false, xhr.responseJSON?.message || 'Network error during batch processing.');
            }
        });
    }

    function finishBatchUI(success, message) {
        isBatchRunning = false;
        currentBatchId = null;

        if (success) {
            $('#batch-progress-bar').removeClass('bg-primary').addClass('bg-success').css('width', '100%');
            $('#batch-progress-text').text('100%');
            $('#batch-modal-icon').html('<i class="ti ti-circle-check fs-1 text-success"></i>');
            $('#batch-status-text').text('Completed').removeClass('text-primary').addClass('text-success');
        } else {
            $('#batch-progress-bar').removeClass('bg-primary').addClass('bg-danger');
            $('#batch-modal-icon').html('<i class="ti ti-alert-triangle fs-1 text-danger"></i>');
            $('#batch-status-text').text('Error').removeClass('text-primary').addClass('text-danger');
        }

        $('#batch-modal-desc').text(message);
        $('#btn-cancel-batch').show();
        table.ajax.reload(null, false);
    }

    function updateStatsUI(stats) {
        if (!stats) return;
        $('#stat-total').text(Number(stats.total).toLocaleString());
        $('#stat-calculated').text(Number(stats.calculated).toLocaleString());
        $('#stat-remaining').text(Number(stats.remaining).toLocaleString());
        $('#stat-failed').text(Number(stats.failed).toLocaleString());
        $('#stat-percentage').text(Number(stats.percentage).toFixed(1) + '%');
    }
});
</script>
@endonce
