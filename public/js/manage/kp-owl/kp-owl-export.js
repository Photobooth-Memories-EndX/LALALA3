/**
 * KP OWL Export Controller (Standardized with Master Reference)
 */
$(document).ready(function () {
    function collectKpOwlExportParams() {
        return {
            search: $('#filter-search').val() || '',
            main_classification: $('#filter-classification').val() || 'all',
            category: $('#filter-category').val() || 'all',
            progress_status: $('#filter-status').val() || 'all',
            type: $('#filter-type').val() || 'all',
            year: $('#filter-year').val() || 'all',
            department: $('#filter-department').val() || 'all',
            team: $('#filter-team').val() || 'all',
            company: $('#filter-company').val() || 'all',
            record_month: $('#filter-month').val() || 'all',
            from_date: $('#filter-from-date').val() || '',
            to_date: $('#filter-to-date').val() || '',
            progress_range: $('#filter-progress-range').val() || 'all'
        };
    }

    // 1. Export Excel (52 Columns)
    $('#btn-export-excel').on('click', function (e) {
        e.preventDefault();

        const $btn = $(this);
        const originalHtml = $btn.html();
        $btn.addClass('disabled').html('<span class="spinner-border spinner-border-sm me-1"></span> Preparing Excel...');

        const params = collectKpOwlExportParams();
        const exportUrl = window.KP_OWL_ROUTES.export + '?' + $.param(params);
        window.location.href = exportUrl;

        setTimeout(function () {
            $btn.removeClass('disabled').html(originalHtml);
        }, 2500);
    });

    // 2. Export PDF
    $('#btn-export-pdf').on('click', function (e) {
        e.preventDefault();
        const params = collectKpOwlExportParams();
        params.format = 'pdf';
        const exportUrl = window.KP_OWL_ROUTES.export + '?' + $.param(params);
        window.location.href = exportUrl;
    });

    // 3. Export CSV
    $('#btn-export-csv').on('click', function (e) {
        e.preventDefault();
        const params = collectKpOwlExportParams();
        params.format = 'csv';
        const exportUrl = window.KP_OWL_ROUTES.export + '?' + $.param(params);
        window.location.href = exportUrl;
    });
});
