@extends('layouts.sidebar-master')

@section('content')
<div class="page-body manage-record-page">
    <div class="container-xl">
        <!-- TOMBOL BACK RATA KIRI ATAS -->
        <div class="mb-3">
            <a href="{{ route('manage.index') }}" class="btn-back d-inline-flex align-items-center gap-1 text-decoration-none fw-medium">
                <i class="ti ti-arrow-left"></i>
                <span>Back</span>
            </a>
        </div>

        @include('main.record.components.record-hero')

        @include('main.record.components.record-navigation')

        @if(request()->get('saved'))
        <div class="alert alert-success alert-dismissible fade show rounded-3 shadow-sm mb-4" role="alert">
            <div class="d-flex align-items-center gap-2">
                <i class="ti ti-circle-check fs-2 text-success"></i>
                <div>
                    <strong>Success!</strong> Non KP OWL training has been created successfully.
                </div>
            </div>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
        @endif

        <!-- SECTION HEADER: Non KP OWL -->
        <div class="card border-0 shadow-sm p-3 p-md-4 mb-4 rounded-3 bg-white record-section-header">
            <div class="d-flex align-items-center justify-content-between flex-wrap gap-3">
                <div class="d-flex align-items-center gap-3">
                    <div class="rounded-3 d-flex align-items-center justify-content-center flex-shrink-0 record-section-icon"
                         style="width: 54px; height: 54px; background-color: #fef3c7; color: #d97706; border-radius: 12px;">
                        <i class="ti ti-certificate fs-1"></i>
                    </div>
                    <div>
                        <h2 class="h3 fw-bold mb-1 text-dark">Non KP OWL</h2>
                        <p class="text-muted small mb-0">Offline Training Record — Manage offline training programs, classroom sessions, external certifications, and vendor learning records.</p>
                    </div>
                </div>

                <!-- Action Buttons: + Add Training | Import Excel | Download Template | Export Non KP OWL -->
                <div class="d-flex align-items-center gap-2 flex-wrap">
                    <a href="{{ route('manage.record.offline.create') }}" class="btn btn-primary rounded-3 px-3 d-inline-flex align-items-center gap-1.5"
                       style="background-color: #d97706; border-color: #d97706;" id="btn-open-offline-add"
                       data-bs-placement="top" title="Add Non KP OWL Training">
                        <i class="ti ti-plus fs-2"></i>
                        <span>Add Training</span>
                    </a>
                    <button type="button" class="btn btn-outline-primary rounded-3 px-3 d-inline-flex align-items-center gap-1.5"
                            data-bs-toggle="modal" data-bs-target="#offlineImportModal" id="btn-open-offline-import">
                        <i class="ti ti-file-spreadsheet fs-2"></i>
                        <span>Import Excel</span>
                    </button>
                    <div class="dropdown">
                        <button type="button" class="btn btn-outline-secondary dropdown-toggle rounded-3 px-3 d-inline-flex align-items-center gap-1.5"
                                data-bs-toggle="dropdown" data-toggle="dropdown" aria-expanded="false" id="btn-download-dropdown">
                            <i class="ti ti-download fs-2"></i>
                            <span>Download</span>
                        </button>
                        <ul class="dropdown-menu dropdown-menu-end shadow-sm border rounded-3 mt-1 py-1" aria-labelledby="btn-download-dropdown" style="min-width: 250px;">
                            <li>
                                <a class="dropdown-item d-flex align-items-center gap-2 py-2" href="{{ route('manage.record.offline.import.template') }}" id="btn-download-offline-template">
                                    <i class="ti ti-file-description text-secondary fs-3"></i>
                                    <span>Download Template</span>
                                </a>
                            </li>
                            <li><hr class="dropdown-divider my-1"></li>
                            <li>
                                <a class="dropdown-item d-flex align-items-center gap-2 py-2" href="{{ route('manage.record.offline.export') }}" id="btn-export-offline-xlsx">
                                    <i class="ti ti-file-spreadsheet text-success fs-3"></i>
                                    <span>Download Offline Record (.xlsx)</span>
                                </a>
                            </li>
                            <li>
                                <a class="dropdown-item d-flex align-items-center gap-2 py-2" href="{{ route('manage.record.offline.export.pdf') }}" id="btn-export-offline-pdf">
                                    <i class="ti ti-file-type-pdf text-danger fs-3"></i>
                                    <span>Download PDF (.pdf)</span>
                                </a>
                            </li>
                            <li>
                                <a class="dropdown-item d-flex align-items-center gap-2 py-2" href="{{ route('manage.record.offline.export.csv') }}" id="btn-export-offline-csv">
                                    <i class="ti ti-file-type-csv text-primary fs-3"></i>
                                    <span>Download CSV (.csv)</span>
                                </a>
                            </li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>

        <!-- 4 KPI SUMMARY CARDS (ASYNC SKELETON SHELL) -->
        <div id="offline-kpi-summary-container">
            @include('main.record.components.offline-kpi-skeleton')
        </div>

        <!-- CLASSIFICATION SUMMARY (ASYNC SKELETON SHELL) -->
        <div class="card border-0 shadow-sm mb-4 classification-summary-card">
            <div class="card-header border-0 pb-0">
                <div>
                    <h3 class="card-title mb-1">Classification Summary</h3>
                    <div class="text-muted small">Aggregate overview per classification category in Non KP OWL.</div>
                </div>
            </div>

            <div class="card-body" id="offline-classification-summary-container">
                @include('main.record.components.offline-classification-skeleton')
            </div>
        </div>
    </div>
</div>

@include('main.record.components.offline-detail-modal')
@include('main.record.components.offline-import-modal')

@endsection

@section('style')
<style>
    .manage-record-page {
        background: #fff;
    }

    [data-bs-theme="dark"] .manage-record-page,
    body.dark-mode .manage-record-page,
    body.theme-dark .manage-record-page {
        background: var(--tblr-body-bg, #111827);
    }

    .btn-back {
        color: #0c5b9e !important;
        font-weight: 500;
        transition: transform 0.2s ease, opacity 0.2s ease;
    }
    .btn-back:hover {
        opacity: 0.85;
        transform: translateX(-3px);
    }
    [data-bs-theme="dark"] .btn-back,
    body.dark-mode .btn-back,
    body.theme-dark .btn-back {
        color: #60a5fa !important;
    }

    .record-section-header {
        border-radius: 14px !important;
        border: 1px solid #e2e8f0 !important;
    }

    [data-bs-theme="dark"] .record-section-header,
    body.dark-mode .record-section-header,
    body.theme-dark .record-section-header {
        background-color: #1e293b !important;
        border-color: rgba(255, 255, 255, 0.1) !important;
    }

    [data-bs-theme="dark"] .record-section-header .text-dark,
    body.dark-mode .record-section-header .text-dark,
    body.theme-dark .record-section-header .text-dark {
        color: #f8fafc !important;
    }

    [data-bs-theme="dark"] .stat-kpi-card,
    body.dark-mode .stat-kpi-card,
    body.theme-dark .stat-kpi-card {
        background-color: #1e293b !important;
        border-color: rgba(255, 255, 255, 0.1) !important;
    }

    [data-bs-theme="dark"] .stat-kpi-card .text-dark,
    body.dark-mode .stat-kpi-card .text-dark,
    body.theme-dark .stat-kpi-card .text-dark {
        color: #f8fafc !important;
    }

    [data-bs-theme="dark"] .classification-summary-card,
    body.dark-mode .classification-summary-card,
    body.theme-dark .classification-summary-card {
        background-color: #1e293b !important;
        border-color: rgba(255, 255, 255, 0.1) !important;
    }

    [data-bs-theme="dark"] .classification-summary-card .table,
    body.dark-mode .classification-summary-card .table,
    body.theme-dark .classification-summary-card .table {
        color: #f8fafc !important;
    }

    [data-bs-theme="dark"] .classification-summary-card thead.bg-light,
    body.dark-mode .classification-summary-card thead.bg-light,
    body.theme-dark .classification-summary-card thead.bg-light {
        background-color: #1e293b !important;
        color: #94a3b8 !important;
    }

    [data-bs-theme="dark"] .classification-summary-card .text-dark,
    body.dark-mode .classification-summary-card .text-dark,
    body.theme-dark .classification-summary-card .text-dark {
        color: #f8fafc !important;
    }

    /* Modal dark mode styling */
    [data-bs-theme="dark"] #offlineTrainingDetailModal .modal-content,
    body.dark-mode #offlineTrainingDetailModal .modal-content,
    body.theme-dark #offlineTrainingDetailModal .modal-content,
    [data-bs-theme="dark"] #offlineImportModal .modal-content,
    body.dark-mode #offlineImportModal .modal-content,
    body.theme-dark #offlineImportModal .modal-content {
        background-color: var(--tblr-bg-surface, #1f2937) !important;
        color: #f8fafc !important;
    }
    [data-bs-theme="dark"] #offlineTrainingDetailModal .bg-light,
    body.dark-mode #offlineTrainingDetailModal .bg-light,
    body.theme-dark #offlineTrainingDetailModal .bg-light,
    [data-bs-theme="dark"] #offlineImportModal .bg-light,
    body.dark-mode #offlineImportModal .bg-light,
    body.theme-dark #offlineImportModal .bg-light {
        background-color: #111827 !important;
    }
    [data-bs-theme="dark"] #offlineTrainingDetailModal .text-dark,
    body.dark-mode #offlineTrainingDetailModal .text-dark,
    body.theme-dark #offlineTrainingDetailModal .text-dark,
    [data-bs-theme="dark"] #offlineImportModal .text-dark,
    body.dark-mode #offlineImportModal .text-dark,
    body.theme-dark #offlineImportModal .text-dark {
        color: #f8fafc !important;
    }
    [data-bs-theme="dark"] #offlineImportModal .bg-white,
    body.dark-mode #offlineImportModal .bg-white,
    body.theme-dark #offlineImportModal .bg-white {
        background-color: var(--tblr-bg-surface, #1f2937) !important;
    }
    [data-bs-theme="dark"] #offlineImportModal .table-light,
    body.dark-mode #offlineImportModal .table-light,
    body.theme-dark #offlineImportModal .table-light {
        background-color: #1e293b !important;
        color: #f8fafc !important;
    }
</style>
@endsection

@section('script')
<script>
    $(function () {
        const summaryUrl = @json(route('manage.record.offline.ajax.summary'));
        const classificationUrl = @json(route('manage.record.offline.ajax.classification'));
        const kpiSkeletonHtml = @json(view('main.record.components.offline-kpi-skeleton')->render());
        const classSkeletonHtml = @json(view('main.record.components.offline-classification-skeleton')->render());

        let kpiXhr = null;
        let classificationXhr = null;

        // 1. Asynchronous Offline KPI Summary Loader
        window.loadOfflineKpiSummary = function () {
            if (kpiXhr) {
                kpiXhr.abort();
                kpiXhr = null;
            }

            $('#offline-kpi-summary-container').html(kpiSkeletonHtml);

            kpiXhr = $.ajax({
                url: summaryUrl,
                type: 'GET',
                dataType: 'json',
                headers: {
                    'X-Requested-With': 'XMLHttpRequest'
                },
                success: function (response) {
                    kpiXhr = null;
                    if (response && response.html) {
                        $('#offline-kpi-summary-container').html(response.html);
                    }
                },
                error: function (xhr, status) {
                    if (status === 'abort') return;
                    kpiXhr = null;
                    $('#offline-kpi-summary-container').html(`
                        <div class="card border-0 shadow-sm p-3 mb-4 rounded-3 bg-white stat-kpi-card">
                            <div class="d-flex align-items-center justify-content-between flex-wrap gap-2">
                                <div class="d-flex align-items-center gap-2 text-danger small">
                                    <i class="ti ti-alert-circle fs-3"></i>
                                    <span>Unable to load offline training summary.</span>
                                </div>
                                <button type="button" class="btn btn-sm btn-outline-primary btn-retry-offline-kpi">
                                    <i class="ti ti-refresh me-1"></i> Retry
                                </button>
                            </div>
                        </div>
                    `);
                }
            });
        };

        // 2. Asynchronous Offline Classification Summary Loader
        window.loadOfflineClassificationSummary = function () {
            if (classificationXhr) {
                classificationXhr.abort();
                classificationXhr = null;
            }

            $('#offline-classification-summary-container').html(classSkeletonHtml);

            classificationXhr = $.ajax({
                url: classificationUrl,
                type: 'GET',
                headers: {
                    'X-Requested-With': 'XMLHttpRequest'
                },
                success: function (response) {
                    classificationXhr = null;
                    const html = typeof response === 'object' && response.html ? response.html : response;
                    $('#offline-classification-summary-container').html(html);
                },
                error: function (xhr, status) {
                    if (status === 'abort') return;
                    classificationXhr = null;
                    $('#offline-classification-summary-container').html(`
                        <div class="p-4 text-center">
                            <div class="text-danger small mb-2 d-flex align-items-center justify-content-center gap-1">
                                <i class="ti ti-alert-circle fs-3"></i>
                                <span>Unable to load classification summary.</span>
                            </div>
                            <button type="button" class="btn btn-sm btn-outline-primary btn-retry-offline-classification d-inline-flex align-items-center gap-1">
                                <i class="ti ti-refresh"></i>
                                <span>Retry</span>
                            </button>
                        </div>
                    `);
                }
            });
        };

        $(document).on('click', '.btn-retry-offline-kpi', function (e) {
            e.preventDefault();
            window.loadOfflineKpiSummary();
        });

        $(document).on('click', '.btn-retry-offline-classification', function (e) {
            e.preventDefault();
            window.loadOfflineClassificationSummary();
        });

        // Trigger parallel async loading on DOM ready
        window.loadOfflineKpiSummary();
        window.loadOfflineClassificationSummary();

        // ==========================================
        // 3. Excel Import Modal Logic (Phase 2F.1)
        // ==========================================
        let importToken = null;
        let importCompleted = false;
        let previewRowsData = [];
        const $importModalEl = $('#offlineImportModal');
        const $importDialog = $('#offline-import-modal-dialog');
        const $fileInput = $('#modal-excel-file-input');
        const $btnValidate = $('#modal-btn-validate');
        const $selectedFilename = $('#modal-selected-filename');
        const $uploadError = $('#modal-upload-error');
        const $dropzone = $('#modal-dropzone-container');

        const $stateUpload = $('#modal-import-state-upload');
        const $stateLoading = $('#modal-import-state-loading');
        const $statePreview = $('#modal-import-state-preview');
        const $stateImporting = $('#modal-import-state-importing');
        const $stateResult = $('#modal-import-state-result');

        const $footerUploadBtns = $('#modal-footer-upload-buttons');
        const $footerPreviewBtns = $('#modal-footer-preview-buttons');
        const $footerResultBtns = $('#modal-footer-result-buttons');
        const $footerHint = $('#modal-footer-hint');

        // Drag & Drop
        $dropzone.on('dragover dragenter', function (e) {
            e.preventDefault();
            e.stopPropagation();
            $(this).addClass('border-primary bg-amber-subtle');
        });

        $dropzone.on('dragleave drop', function (e) {
            e.preventDefault();
            e.stopPropagation();
            $(this).removeClass('border-primary bg-amber-subtle');
        });

        $dropzone.on('drop', function (e) {
            const dt = e.originalEvent.dataTransfer;
            if (dt && dt.files && dt.files.length > 0) {
                $fileInput[0].files = dt.files;
                $fileInput.trigger('change');
            }
        });

        // File selection change
        $fileInput.on('change', function () {
            const file = this.files && this.files[0];
            $uploadError.hide().text('');
            if (file) {
                const ext = file.name.split('.').pop().toLowerCase();
                if (ext !== 'xlsx' && ext !== 'xls') {
                    $uploadError.text('Only .xlsx and .xls Excel spreadsheet files are allowed.').show();
                    $fileInput.val('');
                    $selectedFilename.text('No file selected');
                    $btnValidate.prop('disabled', true);
                    return;
                }
                const sizeKb = (file.size / 1024).toFixed(1);
                $selectedFilename.text(`${file.name} (${sizeKb} KB)`);
                $btnValidate.prop('disabled', false);
            } else {
                $selectedFilename.text('No file selected');
                $btnValidate.prop('disabled', true);
            }
        });

        // Helper: Escape HTML
        function escapeHtml(text) {
            if (text === null || text === undefined) return '';
            return String(text)
                .replace(/&/g, '&amp;')
                .replace(/</g, '&lt;')
                .replace(/>/g, '&gt;')
                .replace(/"/g, '&quot;')
                .replace(/'/g, '&#039;');
        }

        // Helper: Switch modal views
        function switchImportState(state) {
            $stateUpload.hide();
            $stateLoading.hide();
            $statePreview.hide();
            $stateImporting.hide();
            $stateResult.hide();
            $footerUploadBtns.hide();
            $footerPreviewBtns.hide();
            $footerResultBtns.hide();

            if (state === 'upload') {
                $importDialog.removeClass('modal-xl').addClass('modal-lg');
                $stateUpload.show();
                $footerUploadBtns.show();
                $footerHint.text('Only .xlsx and .xls formats supported');
                $('#modal-import-btn-close').prop('disabled', false);
            } else if (state === 'loading') {
                $stateLoading.show();
                $footerHint.text('Analyzing spreadsheet structure and content...');
                $('#modal-import-btn-close').prop('disabled', true);
            } else if (state === 'preview') {
                $importDialog.removeClass('modal-lg').addClass('modal-xl');
                $statePreview.show();
                $footerPreviewBtns.show();
                $footerHint.text('Review validation status before confirming import');
                $('#modal-import-btn-close').prop('disabled', false);
            } else if (state === 'importing') {
                $stateImporting.show();
                $footerHint.text('Saving records to database...');
                $('#modal-import-btn-close').prop('disabled', true);
            } else if (state === 'result') {
                $importDialog.removeClass('modal-xl').addClass('modal-lg');
                $stateResult.show();
                $footerResultBtns.show();
                $footerHint.text('Import complete');
                $('#modal-import-btn-close').prop('disabled', false);
            }
        }

        // Validate & Preview button click
        $btnValidate.on('click', function () {
            const file = $fileInput[0].files && $fileInput[0].files[0];
            if (!file) {
                $uploadError.text('Please select an Excel file to upload.').show();
                return;
            }

            const formData = new FormData();
            formData.append('file', file);
            formData.append('_token', $('meta[name="csrf-token"]').attr('content') || '{{ csrf_token() }}');

            switchImportState('loading');

            $.ajax({
                url: "{{ route('manage.record.offline.import.preview') }}",
                type: 'POST',
                data: formData,
                processData: false,
                contentType: false,
                headers: {
                    'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') || '{{ csrf_token() }}'
                },
                success: function (res) {
                    if (res && res.success) {
                        importToken = res.token;
                        previewRowsData = res.rows || [];
                        renderPreviewState(res);
                    } else {
                        switchImportState('upload');
                        $uploadError.text(res.message || 'Validation failed.').show();
                    }
                },
                error: function (xhr) {
                    switchImportState('upload');
                    let errMsg = 'Failed to analyze file. Please ensure it follows the OWL Excel template format.';
                    if (xhr.responseJSON && xhr.responseJSON.message) {
                        errMsg = xhr.responseJSON.message;
                    }
                    $uploadError.text(errMsg).show();
                }
            });
        });

        // Render preview table and stats
        function renderPreviewState(data) {
            const stats = data.stats || {};
            const rows = data.rows || [];

            $('#modal-stat-total').text((stats.total_rows || 0).toLocaleString());
            $('#modal-stat-valid').text((stats.valid || 0).toLocaleString());
            $('#modal-stat-warning').text((stats.warning || 0).toLocaleString());
            $('#modal-stat-invalid').text((stats.invalid || 0).toLocaleString());
            $('#modal-stat-events').text((stats.unique_events || 0).toLocaleString());

            const $tbody = $('#modal-preview-tbody');
            $tbody.empty();

            if (rows.length === 0) {
                $tbody.append('<tr><td colspan="8" class="text-center text-muted py-4">No data rows found in spreadsheet.</td></tr>');
            } else {
                rows.forEach(function (row) {
                    let badgeClass = 'bg-secondary-lt';
                    let badgeLabel = 'Pending';
                    if (row.status === 'valid') {
                        badgeClass = 'bg-success-lt text-success';
                        badgeLabel = 'Valid';
                    } else if (row.status === 'warning') {
                        badgeClass = 'bg-warning-lt text-warning';
                        badgeLabel = 'Warning';
                    } else if (row.status === 'invalid') {
                        badgeClass = 'bg-danger-lt text-danger';
                        badgeLabel = 'Invalid';
                    }

                    const d = row.data || {};
                    const msgText = (row.messages && row.messages.length > 0) ? row.messages.join('; ') : '-';
                    const userMatch = row.user_matched ? '<i class="ti ti-check text-success ms-1" title="Matched system user"></i>' : '';

                    const tr = [
                        '<tr>',
                        '<td class="text-muted fw-bold">' + row.excel_row + '</td>',
                        '<td><span class="badge ' + badgeClass + ' px-2 py-0.5">' + badgeLabel + '</span></td>',
                        '<td>',
                            '<div class="fw-semibold text-dark">' + escapeHtml(d.name || '-') + userMatch + '</div>',
                            '<div class="text-muted extra-small">ID: ' + escapeHtml(d.employee_id || '-') + '</div>',
                        '</td>',
                        '<td>',
                            '<div class="fw-medium text-dark">' + escapeHtml(d.course_name || '-') + '</div>',
                            '<div class="text-muted extra-small">' + escapeHtml(d.type || '-') + '</div>',
                        '</td>',
                        '<td><span class="small">' + escapeHtml(d.batch || '-') + '</span></td>',
                        '<td><span class="small fw-semibold">' + (d.learning_hours || 0) + ' hrs</span></td>',
                        '<td><span class="small">' + (d.progress !== null && d.progress !== undefined ? d.progress + '%' : '-') + '</span></td>',
                        '<td><span class="extra-small ' + (row.status === 'invalid' ? 'text-danger fw-semibold' : 'text-muted') + '">' + escapeHtml(msgText) + '</span></td>',
                        '</tr>'
                    ].join('');

                    $tbody.append(tr);
                });
            }

            const importableCount = (stats.valid || 0) + (stats.warning || 0);
            if (importableCount <= 0) {
                $('#modal-btn-confirm').prop('disabled', true).text('No Valid Records');
            } else {
                $('#modal-btn-confirm').prop('disabled', false).html('<i class="ti ti-check me-1"></i> Confirm Import (' + importableCount + ' Records)');
            }

            switchImportState('preview');
        }

        // Cancel preview / Re-upload button handler
        function handleImportCancel() {
            if (importToken) {
                $.ajax({
                    url: "{{ route('manage.record.offline.import.cancel') }}",
                    type: 'POST',
                    data: {
                        token: importToken,
                        _token: $('meta[name="csrf-token"]').attr('content') || '{{ csrf_token() }}'
                    },
                    headers: {
                        'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') || '{{ csrf_token() }}'
                    }
                });
            }
            importToken = null;
            previewRowsData = [];
            $fileInput.val('');
            $selectedFilename.text('No file selected');
            $btnValidate.prop('disabled', true);
            $uploadError.hide().text('');
            switchImportState('upload');
        }

        $('#modal-btn-cancel-preview, #modal-btn-preview-cancel').on('click', handleImportCancel);

        // Confirm & Execute import
        $('#modal-btn-confirm').on('click', function () {
            if (!importToken) return;

            switchImportState('importing');

            $.ajax({
                url: "{{ route('manage.record.offline.import.confirm') }}",
                type: 'POST',
                data: {
                    token: importToken,
                    _token: $('meta[name="csrf-token"]').attr('content') || '{{ csrf_token() }}'
                },
                headers: {
                    'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') || '{{ csrf_token() }}'
                },
                success: function (res) {
                    if (res && res.success && res.result) {
                        importCompleted = true;
                        importToken = null;
                        const result = res.result;

                        $('#modal-result-imported').text((result.imported || 0).toLocaleString());
                        $('#modal-result-created').text((result.trainings_created || 0).toLocaleString());
                        $('#modal-result-reused').text((result.trainings_reused || 0).toLocaleString());
                        $('#modal-result-skipped').text((result.skipped_duplicates || 0).toLocaleString());

                        switchImportState('result');
                    } else {
                        switchImportState('preview');
                        alert(res.message || 'Import execution failed.');
                    }
                },
                error: function (xhr) {
                    switchImportState('preview');
                    let errMsg = 'Failed to execute import.';
                    if (xhr.responseJSON && xhr.responseJSON.message) {
                        errMsg = xhr.responseJSON.message;
                    }
                    alert(errMsg);
                }
            });
        });

        // Done button in result state
        $('#modal-btn-result-close').on('click', function () {
            $importModalEl.modal('hide');
        });

        // Modal hidden event (cleanup)
        $importModalEl.on('hidden.bs.modal', function () {
            if (importCompleted) {
                window.location.href = "{{ route('manage.record.offline') }}";
                return;
            }

            if (importToken) {
                $.ajax({
                    url: "{{ route('manage.record.offline.import.cancel') }}",
                    type: 'POST',
                    data: {
                        token: importToken,
                        _token: $('meta[name="csrf-token"]').attr('content') || '{{ csrf_token() }}'
                    },
                    headers: {
                        'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') || '{{ csrf_token() }}'
                    }
                });
            }

            importToken = null;
            previewRowsData = [];
            $fileInput.val('');
            $selectedFilename.text('No file selected');
            $btnValidate.prop('disabled', true);
            $uploadError.hide().text('');
            switchImportState('upload');
        });

        // Auto-open modal if URL has ?open_import=1
        const urlParams = new URLSearchParams(window.location.search);
        if (urlParams.get('open_import') === '1') {
            if (typeof bootstrap !== 'undefined' && bootstrap.Modal) {
                const modalInstance = bootstrap.Modal.getOrCreateInstance(document.getElementById('offlineImportModal'));
                modalInstance.show();
            } else {
                $importModalEl.modal('show');
            }
        }

        // Initialize tooltips
        if (typeof bootstrap !== 'undefined' && bootstrap.Tooltip) {
            const tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
            tooltipTriggerList.map(function (tooltipTriggerEl) {
                return new bootstrap.Tooltip(tooltipTriggerEl);
            });
        }
    });
</script>
@endsection
