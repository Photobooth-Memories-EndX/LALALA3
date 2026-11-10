/**
 * KP OWL Filter Controller (Standardized with Master Reference)
 */
$(document).ready(function () {
    // 1. Search Box Apply Filter Button & Enter Key
    $('#btn-apply-filter').on('click', function () {
        if (kpOwlTable) {
            kpOwlTable.ajax.reload();
        }
    });

    $('#filter-search').on('keypress', function (e) {
        if (e.which === 13) {
            e.preventDefault();
            $('#btn-apply-filter').trigger('click');
        }
    });

    // 2. Dropdown Filters Instant Reload
    $('#filter-classification, #filter-category, #filter-status, #filter-type, #filter-year').on('change', function () {
        if (kpOwlTable) {
            kpOwlTable.ajax.reload();
        }
    });

    // 3. More Filters Modal Apply
    $('#btn-modal-apply').on('click', function () {
        $('#filter-department').val($('#modal-filter-department').val() || 'all');
        $('#filter-team').val($('#modal-filter-team').val() || 'all');
        $('#filter-company').val($('#modal-filter-company').val() || 'all');
        $('#filter-month').val($('#modal-filter-month').val() || 'all');
        $('#filter-from-date').val($('#modal-filter-from-date').val() || '');
        $('#filter-to-date').val($('#modal-filter-to-date').val() || '');
        $('#filter-progress-range').val($('input[name="modal_kp_progress_range"]:checked').val() || 'all');

        updateActiveFilterBadge();

        if (kpOwlTable) {
            kpOwlTable.ajax.reload();
        }
    });

    // 4. More Filters Modal Reset
    $('#btn-modal-reset').on('click', function () {
        $('#modal-filter-department').val('all');
        $('#modal-filter-team').val('all');
        $('#modal-filter-company').val('all');
        $('#modal-filter-month').val('all');
        $('#modal-filter-from-date').val('');
        $('#modal-filter-to-date').val('');
        $('input[name="modal_kp_progress_range"][value="all"]').prop('checked', true);

        $('#filter-department').val('all');
        $('#filter-team').val('all');
        $('#filter-company').val('all');
        $('#filter-month').val('all');
        $('#filter-from-date').val('');
        $('#filter-to-date').val('');
        $('#filter-progress-range').val('all');

        updateActiveFilterBadge();

        if (kpOwlTable) {
            kpOwlTable.ajax.reload();
        }
    });

    // 5. Global Reset Button
    $('#btn-reset-filter').on('click', function () {
        $('#filter-search').val('');
        $('#filter-classification').val('all');
        $('#filter-category').val('all');
        $('#filter-status').val('all');
        $('#filter-type').val('all');
        $('#filter-year').val(new Date().getFullYear().toString());

        // Reset modal controls
        $('#modal-filter-department').val('all');
        $('#modal-filter-team').val('all');
        $('#modal-filter-company').val('all');
        $('#modal-filter-month').val('all');
        $('#modal-filter-from-date').val('');
        $('#modal-filter-to-date').val('');
        $('input[name="modal_kp_progress_range"][value="all"]').prop('checked', true);

        // Reset hidden controls
        $('#filter-department').val('all');
        $('#filter-team').val('all');
        $('#filter-company').val('all');
        $('#filter-month').val('all');
        $('#filter-from-date').val('');
        $('#filter-to-date').val('');
        $('#filter-progress-range').val('all');

        updateActiveFilterBadge();

        if (kpOwlTable) {
            kpOwlTable.ajax.reload();
        }
    });

    // 6. Active Filter Badge Calculator
    function updateActiveFilterBadge() {
        let count = 0;
        if ($('#filter-department').val() !== 'all') count++;
        if ($('#filter-team').val() !== 'all') count++;
        if ($('#filter-company').val() !== 'all') count++;
        if ($('#filter-month').val() !== 'all') count++;
        if ($('#filter-from-date').val() !== '') count++;
        if ($('#filter-to-date').val() !== '') count++;
        if ($('#filter-progress-range').val() !== 'all') count++;

        const $badge = $('#activeFilterBadge');
        if (count > 0) {
            $badge.text(count).removeClass('d-none');
        } else {
            $badge.text(0).addClass('d-none');
        }
    }
});
