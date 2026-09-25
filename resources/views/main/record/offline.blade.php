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

                <!-- Action Buttons: + Add Training | Import Excel | Download Template -->
                <div class="d-flex align-items-center gap-2 flex-wrap">
                    <button type="button" class="btn btn-primary rounded-3 px-3 d-inline-flex align-items-center gap-1.5"
                            style="background-color: #d97706; border-color: #d97706;"
                            data-bs-toggle="modal" data-bs-target="#offlineAddTrainingModal" id="btn-open-offline-add"
                            data-bs-placement="top" title="Add Non KP OWL Training">
                        <i class="ti ti-plus fs-2"></i>
                        <span>Add Training</span>
                    </button>
                    <button type="button" class="btn btn-outline-primary rounded-3 px-3 d-inline-flex align-items-center gap-1.5"
                            data-bs-toggle="modal" data-bs-target="#offlineImportModal" id="btn-open-offline-import">
                        <i class="ti ti-file-spreadsheet fs-2"></i>
                        <span>Import Excel</span>
                    </button>
                    <a href="{{ route('manage.record.offline.import.template') }}" class="btn btn-outline-secondary rounded-3 px-3 d-inline-flex align-items-center gap-1.5" id="btn-download-offline-template">
                        <i class="ti ti-download fs-2"></i>
                        <span>Download Template</span>
                    </a>
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
@include('main.record.components.offline-add-modal')

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

        // =============================================================
        // 4. Manual Add Training Modal Logic (Phase 2F.3)
        // =============================================================
        const $addModalEl = $('#offlineAddTrainingModal');
        const $addScrollBody = $('#modal-add-training-scroll-body');
        const $addForm = $('#offline-manual-add-form');
        const $addAlert = $('#add-training-alert');
        const $btnSubmitAdd = $('#btn-submit-add-training');

        let addParticipants = [];
        let currentActiveParticipantIndex = -1;
        let empSearchTimer = null;

        // --- Helper: Format Month from Date ---
        function deriveMonthString(dateStr) {
            if (!dateStr) return '';
            const parts = dateStr.split('-');
            if (parts.length < 3) return '';
            const d = new Date(parseInt(parts[0], 10), parseInt(parts[1], 10) - 1, parseInt(parts[2], 10));
            if (isNaN(d.getTime())) return '';
            const months = ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'];
            return `${months[d.getMonth()]} ${d.getFullYear()}`;
        }

        // --- Helper: Parse duration string to hours ---
        function parseDurationToHours(durStr) {
            if (!durStr) return null;
            const parts = durStr.trim().split(':');
            if (parts.length >= 2) {
                const h = parseFloat(parts[0]) || 0;
                const m = parseFloat(parts[1]) || 0;
                const s = parseFloat(parts[2]) || 0;
                return (h + (m / 60) + (s / 3600)).toFixed(1);
            }
            return null;
        }

        // --- Schedule & Duration Watchers ---
        $('#add-start-date').on('change', function () {
            const startVal = $(this).val();
            const monthText = deriveMonthString(startVal);
            $('#add-month').val(monthText);

            const $finishDate = $('#add-finish-date');
            if (startVal && (!$finishDate.val() || $finishDate.val() < startVal)) {
                $finishDate.val(startVal);
            }
            updateSection4Review();
        });

        $('#add-finish-date').on('change', function () {
            updateSection4Review();
        });

        $('#add-duration').on('change', function () {
            const val = $(this).val();
            const $learningHours = $('#add-learning-hours');
            if (!$learningHours.val() || $learningHours.val() === '0') {
                const suggested = parseDurationToHours(val);
                if (suggested) {
                    $learningHours.val(suggested);
                }
            }
            updateSection4Review();
        });

        // Trigger review update on any Section 1 input change
        $('#section-1-training-info input, #section-1-training-info select').on('input change', function () {
            updateSection4Review();
        });

        // --- Section 2: Participant Search & Autocomplete ---
        const $empSearchInput = $('#participant-search-input');
        const $empSearchResults = $('#participant-search-results');

        $empSearchInput.on('input', function () {
            clearTimeout(empSearchTimer);
            const query = $(this).val().trim();
            if (query.length < 2) {
                $empSearchResults.hide().empty();
                return;
            }

            empSearchTimer = setTimeout(function () {
                $.ajax({
                    url: "{{ route('manage.record.offline.search-employees') }}",
                    type: 'GET',
                    data: { q: query },
                    success: function (res) {
                        const list = (res && res.results) ? res.results : [];
                        if (list.length === 0) {
                            $empSearchResults.html('<div class="p-2 text-muted small text-center">No employee found.</div>').show();
                            return;
                        }

                        let html = '';
                        list.forEach(function (emp) {
                            const empJson = escapeHtml(JSON.stringify(emp));
                            html += `
                                <a href="#" class="dropdown-item py-2 border-bottom autocomplete-emp-item" data-employee='${empJson}'>
                                    <div class="fw-semibold text-dark">${escapeHtml(emp.name)}</div>
                                    <div class="text-muted extra-small d-flex align-items-center gap-2">
                                        <span class="badge bg-light text-secondary border">${escapeHtml(emp.employee_id || emp.id_emp || '-')}</span>
                                        <span>${escapeHtml(emp.job_position || emp.department || 'Employee')}</span>
                                        <span>&bull; ${escapeHtml(emp.company || 'PT Krakatau Posco')}</span>
                                    </div>
                                </a>
                            `;
                        });
                        $empSearchResults.html(html).show();
                    },
                    error: function () {
                        $empSearchResults.html('<div class="p-2 text-danger small text-center">Search error.</div>').show();
                    }
                });
            }, 300);
        });

        $('#btn-search-participant').on('click', function () {
            $empSearchInput.trigger('input');
        });

        $(document).on('click', function (e) {
            if (!$(e.target).closest('#participant-search-input, #participant-search-results').length) {
                $empSearchResults.hide();
            }
        });

        // Selecting from search dropdown
        $(document).on('click', '.autocomplete-emp-item', function (e) {
            e.preventDefault();
            const empData = $(this).data('employee');
            if (!empData) return;

            addParticipantToList({
                user_id: empData.user_id || empData.id || null,
                employee_id: empData.employee_id || empData.id_emp || '',
                name: empData.name || '',
                grade: empData.grade || '',
                job_position: empData.job_position || '',
                team: empData.team || '',
                department: empData.department || '',
                company: empData.company || 'PT Krakatau Posco',
            });

            $empSearchInput.val('');
            $empSearchResults.hide().empty();
        });

        // --- Section 2: Manual Participant Entry ---
        $('#btn-toggle-manual-participant').on('click', function () {
            $('#manual-participant-card').slideToggle(200);
        });

        $('#btn-cancel-manual-participant').on('click', function () {
            $('#manual-participant-card').slideUp(200);
        });

        $('#btn-add-manual-participant').on('click', function () {
            const empId = $('#manual-emp-id').val().trim();
            const empName = $('#manual-emp-name').val().trim();

            if (!empId || !empName) {
                alert('Employee ID / NIP and Name are required for manual participant entry.');
                return;
            }

            addParticipantToList({
                user_id: null,
                employee_id: empId,
                name: empName,
                grade: $('#manual-emp-grade').val().trim(),
                job_position: $('#manual-emp-position').val().trim(),
                team: $('#manual-emp-team').val().trim(),
                department: $('#manual-emp-dept').val().trim(),
                company: $('#manual-emp-company').val().trim() || 'PT Krakatau Posco',
            });

            // Reset manual form fields
            $('#manual-emp-id').val('');
            $('#manual-emp-name').val('');
            $('#manual-emp-grade').val('');
            $('#manual-emp-position').val('');
            $('#manual-emp-team').val('');
            $('#manual-emp-dept').val('');
            $('#manual-participant-card').slideUp(200);
        });

        // Generic function to add participant with duplicate check
        function addParticipantToList(empObj) {
            const normEmpId = (empObj.employee_id || '').trim().toLowerCase();
            const normUserId = empObj.user_id ? parseInt(empObj.user_id, 10) : null;

            // Check if already in list
            const isDuplicate = addParticipants.some(function (p) {
                if (normUserId && p.user_id && parseInt(p.user_id, 10) === normUserId) return true;
                if (normEmpId && (p.employee_id || '').trim().toLowerCase() === normEmpId) return true;
                return false;
            });

            if (isDuplicate) {
                alert(`Employee [${empObj.name} (${empObj.employee_id})] is already enrolled in this training.`);
                return;
            }

            const defaultLearningHours = parseFloat($('#add-learning-hours').val()) || null;

            // Push participant with default learning results
            addParticipants.push({
                user_id: normUserId,
                employee_id: empObj.employee_id || '',
                name: empObj.name || '',
                grade: empObj.grade || '',
                job_position: empObj.job_position || '',
                team: empObj.team || '',
                department: empObj.department || '',
                company: empObj.company || 'PT Krakatau Posco',
                learning_hours: defaultLearningHours,
                progress: 100,
                assessment_score: '',
                certificate: 'Yes',
                attendance: 1,
                attendance_reason: '',
                bond_status: 'No',
                bond_end_date: '',
                bond_amount: '',
                l1_program: '',
                l2_numerical: '',
                l2_verbal: '',
                l3_numerical: '',
                l3_verbal: '',
                pic: ''
            });

            currentActiveParticipantIndex = addParticipants.length - 1;
            renderParticipantsTable();
            populateActiveParticipantSelect();
            loadActiveParticipantForm(currentActiveParticipantIndex);
            updateSection4Review();
        }

        // Render Section 2 Participants Table
        function renderParticipantsTable() {
            const count = addParticipants.length;
            $('#badge-participants-count').text(`${count} Enrolled`);

            if (count === 0) {
                $('#selected-participants-tbody').html(`
                    <tr id="empty-participants-row">
                        <td colspan="9" class="text-center text-muted py-4 small">
                            <i class="ti ti-users fs-2 d-block mb-1 text-secondary"></i>
                            No participants added yet. Search employee or click Input Manually.
                        </td>
                    </tr>
                `);
                $('#no-participants-alert').show();
                $('#active-participant-form-container').hide();
                return;
            }

            $('#no-participants-alert').hide();
            $('#active-participant-form-container').show();

            let rows = '';
            addParticipants.forEach(function (p, index) {
                const isActive = (index === currentActiveParticipantIndex);
                const activeClass = isActive ? 'table-warning fw-medium' : '';
                rows += `
                    <tr class="${activeClass} participant-table-row" data-index="${index}" style="cursor: pointer;">
                        <td class="text-center text-muted">${index + 1}</td>
                        <td><span class="badge bg-light text-secondary border font-monospace">${escapeHtml(p.employee_id || '-')}</span></td>
                        <td>
                            <div class="fw-semibold text-dark">${escapeHtml(p.name)}</div>
                            ${p.user_id ? '<span class="badge bg-green-lt extra-small py-0">LMS User</span>' : ''}
                        </td>
                        <td>${escapeHtml(p.grade || '-')}</td>
                        <td>${escapeHtml(p.job_position || '-')}</td>
                        <td>${escapeHtml(p.team || '-')}</td>
                        <td>${escapeHtml(p.department || '-')}</td>
                        <td>${escapeHtml(p.company || '-')}</td>
                        <td class="text-end">
                            <button type="button" class="btn btn-sm btn-ghost-danger p-1 btn-remove-participant" data-index="${index}" title="Remove participant">
                                <i class="ti ti-trash"></i>
                            </button>
                        </td>
                    </tr>
                `;
            });

            $('#selected-participants-tbody').html(rows);
        }

        // Click row to switch active participant
        $(document).on('click', '.participant-table-row', function (e) {
            if ($(e.target).closest('.btn-remove-participant').length) return;
            const idx = parseInt($(this).data('index'), 10);
            if (!isNaN(idx) && idx !== currentActiveParticipantIndex) {
                saveCurrentActiveParticipantForm();
                currentActiveParticipantIndex = idx;
                populateActiveParticipantSelect();
                loadActiveParticipantForm(currentActiveParticipantIndex);
                renderParticipantsTable();
            }
        });

        // Remove participant
        $(document).on('click', '.btn-remove-participant', function (e) {
            e.stopPropagation();
            const removeIdx = parseInt($(this).data('index'), 10);
            if (isNaN(removeIdx) || removeIdx < 0 || removeIdx >= addParticipants.length) return;

            addParticipants.splice(removeIdx, 1);

            if (addParticipants.length === 0) {
                currentActiveParticipantIndex = -1;
            } else if (currentActiveParticipantIndex >= addParticipants.length) {
                currentActiveParticipantIndex = addParticipants.length - 1;
            }

            renderParticipantsTable();
            populateActiveParticipantSelect();
            if (currentActiveParticipantIndex >= 0) {
                loadActiveParticipantForm(currentActiveParticipantIndex);
            }
            updateSection4Review();
        });

        // --- Section 3: Learning Results Management ---
        function populateActiveParticipantSelect() {
            const $select = $('#select-active-participant');
            $select.empty();

            if (addParticipants.length === 0) {
                $select.html('<option value="">No participants enrolled</option>');
                $('#participant-indicator-text').text('0 of 0');
                $('#btn-prev-participant').prop('disabled', true);
                $('#btn-next-participant').prop('disabled', true);
                return;
            }

            addParticipants.forEach(function (p, idx) {
                $select.append(`<option value="${idx}">${idx + 1}. ${p.name} (${p.employee_id})</option>`);
            });

            $select.val(currentActiveParticipantIndex);
            $('#participant-indicator-text').text(`${currentActiveParticipantIndex + 1} of ${addParticipants.length}`);
            $('#btn-prev-participant').prop('disabled', currentActiveParticipantIndex <= 0);
            $('#btn-next-participant').prop('disabled', currentActiveParticipantIndex >= addParticipants.length - 1);
        }

        $('#select-active-participant').on('change', function () {
            saveCurrentActiveParticipantForm();
            const newIdx = parseInt($(this).val(), 10);
            if (!isNaN(newIdx) && newIdx >= 0 && newIdx < addParticipants.length) {
                currentActiveParticipantIndex = newIdx;
                populateActiveParticipantSelect();
                loadActiveParticipantForm(currentActiveParticipantIndex);
                renderParticipantsTable();
            }
        });

        $('#btn-prev-participant').on('click', function () {
            if (currentActiveParticipantIndex > 0) {
                saveCurrentActiveParticipantForm();
                currentActiveParticipantIndex--;
                populateActiveParticipantSelect();
                loadActiveParticipantForm(currentActiveParticipantIndex);
                renderParticipantsTable();
            }
        });

        $('#btn-next-participant').on('click', function () {
            if (currentActiveParticipantIndex < addParticipants.length - 1) {
                saveCurrentActiveParticipantForm();
                currentActiveParticipantIndex++;
                populateActiveParticipantSelect();
                loadActiveParticipantForm(currentActiveParticipantIndex);
                renderParticipantsTable();
            }
        });

        // Load active participant into Section 3 form
        function loadActiveParticipantForm(idx) {
            if (idx < 0 || idx >= addParticipants.length) return;
            const p = addParticipants[idx];

            $('#res-progress').val(p.progress !== undefined ? p.progress : 100);
            updateStatusBadgeFromProgress(p.progress !== undefined ? p.progress : 100);

            $('#res-assessment-score').val(p.assessment_score || '');
            $('#res-certificate').val(p.certificate || 'Yes');

            if (p.attendance == 1 || p.attendance === true || p.attendance === '1') {
                $('#res-attendance-yes').prop('checked', true);
            } else {
                $('#res-attendance-no').prop('checked', true);
            }
            $('#res-attendance-reason').val(p.attendance_reason || '');

            const isBonded = (p.bond_status === 'Yes');
            $('#res-bond-status').val(p.bond_status || 'No');
            $('#res-bond-end-date').val(p.bond_end_date || '').prop('disabled', !isBonded);
            $('#res-bond-amount').val(p.bond_amount || '').prop('disabled', !isBonded);

            $('#res-l1-program').val(p.l1_program || '');
            $('#res-l2-numerical').val(p.l2_numerical || '');
            $('#res-l2-verbal').val(p.l2_verbal || '');
            $('#res-l3-numerical').val(p.l3_numerical || '');
            $('#res-l3-verbal').val(p.l3_verbal || '');
            $('#res-pic').val(p.pic || '');

            $('#save-result-status-msg').text(`Configuring results for: ${p.name}`);
        }

        // Save current Section 3 inputs back into active participant object
        function saveCurrentActiveParticipantForm() {
            if (currentActiveParticipantIndex < 0 || currentActiveParticipantIndex >= addParticipants.length) return;
            const p = addParticipants[currentActiveParticipantIndex];

            const rawProg = parseFloat($('#res-progress').val());
            p.progress = isNaN(rawProg) ? 100 : Math.min(100, Math.max(0, rawProg));
            p.assessment_score = $('#res-assessment-score').val() !== '' ? parseFloat($('#res-assessment-score').val()) : null;
            p.certificate = $('#res-certificate').val();
            p.attendance = $('#res-attendance-yes').is(':checked') ? 1 : 0;
            p.attendance_reason = $('#res-attendance-reason').val().trim();
            p.bond_status = $('#res-bond-status').val();
            p.bond_end_date = p.bond_status === 'Yes' ? $('#res-bond-end-date').val() : null;
            p.bond_amount = p.bond_status === 'Yes' && $('#res-bond-amount').val() !== '' ? parseFloat($('#res-bond-amount').val()) : null;
            p.l1_program = $('#res-l1-program').val().trim();
            p.l2_numerical = $('#res-l2-numerical').val() !== '' ? parseFloat($('#res-l2-numerical').val()) : null;
            p.l2_verbal = $('#res-l2-verbal').val().trim();
            p.l3_numerical = $('#res-l3-numerical').val() !== '' ? parseFloat($('#res-l3-numerical').val()) : null;
            p.l3_verbal = $('#res-l3-verbal').val().trim();
            p.pic = $('#res-pic').val().trim();
        }

        // Live status badge updater
        function updateStatusBadgeFromProgress(val) {
            const $badge = $('#res-status-badge');
            const num = parseFloat(val);
            if (isNaN(num) || num >= 100) {
                $badge.attr('class', 'badge bg-success-lt fw-bold').text('FINISHED');
            } else if (num > 0) {
                $badge.attr('class', 'badge bg-warning-lt fw-bold').text('ONGOING');
            } else {
                $badge.attr('class', 'badge bg-secondary-lt fw-bold').text('NOT STARTED');
            }
        }

        $('#res-progress').on('input', function () {
            updateStatusBadgeFromProgress($(this).val());
            saveCurrentActiveParticipantForm();
            updateSection4Review();
        });

        $('#res-bond-status').on('change', function () {
            const isBonded = ($(this).val() === 'Yes');
            $('#res-bond-end-date').prop('disabled', !isBonded);
            $('#res-bond-amount').prop('disabled', !isBonded);
            if (!isBonded) {
                $('#res-bond-end-date').val('');
                $('#res-bond-amount').val('');
            }
            saveCurrentActiveParticipantForm();
            updateSection4Review();
        });

        $('input[name="res_attendance"]').on('change', function () {
            saveCurrentActiveParticipantForm();
            updateSection4Review();
        });

        $('#section-3-results input, #section-3-results select').on('input change', function () {
            saveCurrentActiveParticipantForm();
            updateSection4Review();
        });

        $('#btn-save-participant-result').on('click', function () {
            saveCurrentActiveParticipantForm();
            updateSection4Review();
            $('#save-result-status-msg').html('<span class="text-success fw-bold"><i class="ti ti-check"></i> Result saved for this participant!</span>');
            setTimeout(function () {
                if (currentActiveParticipantIndex >= 0 && addParticipants[currentActiveParticipantIndex]) {
                    $('#save-result-status-msg').text(`Configuring results for: ${addParticipants[currentActiveParticipantIndex].name}`);
                }
            }, 2000);
        });

        // --- Section 4: Dynamic Review Update ---
        function updateSection4Review() {
            // Training Info Card
            $('#rev-course').text($('#add-course-name').val() || '-');
            $('#rev-batch').text($('#add-batch').val() || '-');

            const mainClass = $('#add-main-classification').val();
            const subClass = $('#add-sub-classification').val();
            $('#rev-classification').text(mainClass ? (subClass ? `${mainClass} (${subClass})` : mainClass) : '-');

            const delivery = $('#add-delivery-method').val();
            const subDelivery = $('#add-sub-delivery-method').val();
            $('#rev-delivery').text(delivery ? (subDelivery ? `${delivery} (${subDelivery})` : delivery) : '-');

            const provider = $('#add-provider').val();
            const providerName = $('#add-provider-name').val();
            $('#rev-provider').text(provider ? (providerName ? `${provider} - ${providerName}` : provider) : '-');

            const start = $('#add-start-date').val();
            const finish = $('#add-finish-date').val();
            const month = $('#add-month').val();
            $('#rev-schedule').text((start || finish) ? `${start || '-'} ~ ${finish || '-'} ${month ? '(' + month + ')' : ''}` : '-');

            const hours = $('#add-learning-hours').val();
            const dur = $('#add-duration').val();
            $('#rev-hours').text(hours ? `${hours} hrs ${dur ? '(' + dur + ')' : ''}` : '-');

            $('#rev-venue').text($('#add-venue').val() || '-');
            $('#rev-instructor').text($('#add-instructor').val() || '-');

            // Participants List Card
            const total = addParticipants.length;
            $('#rev-part-count').text(total);
            if (total === 0) {
                $('#rev-part-list').html('<li class="text-muted">No participants added</li>');
            } else {
                let listHtml = '';
                addParticipants.forEach(function (p) {
                    listHtml += `<li><strong>${escapeHtml(p.name)}</strong> (${escapeHtml(p.employee_id)}) &bull; <span class="text-muted">${escapeHtml(p.job_position || p.department || 'Enrolled')}</span></li>`;
                });
                $('#rev-part-list').html(listHtml);
            }

            // Metrics Card
            $('#rev-metric-total').text(total);

            let attYes = 0;
            let attNo = 0;
            let finished = 0;
            let ongoing = 0;
            let totalScores = 0;
            let scoreCount = 0;
            let bonded = 0;

            addParticipants.forEach(function (p) {
                if (p.attendance == 1 || p.attendance === true || p.attendance === '1') {
                    attYes++;
                } else {
                    attNo++;
                }

                const prog = parseFloat(p.progress);
                if (!isNaN(prog)) {
                    if (prog >= 100) finished++;
                    else if (prog > 0) ongoing++;
                } else {
                    finished++;
                }

                if (p.assessment_score !== null && p.assessment_score !== '' && !isNaN(p.assessment_score)) {
                    totalScores += parseFloat(p.assessment_score);
                    scoreCount++;
                }

                if (p.bond_status === 'Yes') {
                    bonded++;
                }
            });

            $('#rev-metric-att-yes').text(`${attYes} Yes`);
            $('#rev-metric-att-no').text(`${attNo} No`);
            $('#rev-metric-finished').text(finished);
            $('#rev-metric-ongoing').text(ongoing);
            $('#rev-metric-avg-score').text(scoreCount > 0 ? (totalScores / scoreCount).toFixed(1) : '-');
            $('#rev-metric-bond').text(bonded);
        }

        // Edit buttons in Section 4 smooth scroll to corresponding section
        function scrollToSection(sectionId, stepNum) {
            const target = $(sectionId);
            if (target.length) {
                $addScrollBody.animate({
                    scrollTop: target.position().top + $addScrollBody.scrollTop() - 10
                }, 250);
            }
            $('.modal-step-item').removeClass('active');
            $(`#step-nav-${stepNum}`).addClass('active');
        }

        $('#btn-review-edit-1').on('click', function () { scrollToSection('#section-1-training-info', 1); });
        $('#btn-review-edit-2').on('click', function () { scrollToSection('#section-2-participants', 2); });
        $('#btn-review-edit-3').on('click', function () { scrollToSection('#section-3-results', 3); });

        // Left Step Nav Clicks
        $('.modal-step-item').on('click', function (e) {
            e.preventDefault();
            const targetId = $(this).attr('href');
            const stepNum = $(this).data('step');
            scrollToSection(targetId, stepNum);
        });

        // Scroll spy for modal body
        $addScrollBody.on('scroll', function () {
            const scrollPos = $addScrollBody.scrollTop() + 50;
            const sections = [
                { id: '#section-1-training-info', step: 1 },
                { id: '#section-2-participants', step: 2 },
                { id: '#section-3-results', step: 3 },
                { id: '#section-4-review', step: 4 }
            ];

            for (let i = sections.length - 1; i >= 0; i--) {
                const $sec = $(sections[i].id);
                if ($sec.length) {
                    const top = $sec.position().top + $addScrollBody.scrollTop();
                    if (scrollPos >= top) {
                        $('.modal-step-item').removeClass('active');
                        $(`#step-nav-${sections[i].step}`).addClass('active');
                        break;
                    }
                }
            }
        });

        // --- SUBMIT TRAINING FORM ---
        $btnSubmitAdd.on('click', function () {
            // First save currently edited participant
            saveCurrentActiveParticipantForm();

            $addAlert.hide().empty();

            // Client Validation: Course Name
            const courseName = $('#add-course-name').val().trim();
            if (!courseName) {
                $addAlert.text('Course Name is required in Section 1.').show();
                scrollToSection('#section-1-training-info', 1);
                $('#add-course-name').focus();
                return;
            }

            // Client Validation: Dates
            const startDate = $('#add-start-date').val();
            const finishDate = $('#add-finish-date').val();
            if (startDate && finishDate && finishDate < startDate) {
                $addAlert.text(`Finish Date (${finishDate}) cannot be earlier than Start Date (${startDate}).`).show();
                scrollToSection('#section-1-training-info', 1);
                return;
            }

            // Client Validation: Participants
            if (addParticipants.length === 0) {
                $addAlert.text('At least one participant is required in Section 2.').show();
                scrollToSection('#section-2-participants', 2);
                return;
            }

            // Prepare Payload
            const payload = {
                course_name: courseName,
                sub_course_name: $('#add-sub-course-name').val().trim(),
                type: $('#add-type').val(),
                category: $('#add-category').val().trim(),
                level: $('#add-level').val(),
                batch: $('#add-batch').val().trim(),
                main_classification: $('#add-main-classification').val(),
                sub_classification: $('#add-sub-classification').val().trim(),
                counted_corporate_kpi: $('input[name="counted_corporate_kpi"]:checked').val(),
                sub_corporate_kpi: $('#add-sub-corporate-kpi').val().trim(),
                delivery_method: $('#add-delivery-method').val(),
                sub_delivery_method: $('#add-sub-delivery-method').val().trim(),
                provider: $('#add-provider').val(),
                sub_provider: $('#add-sub-provider').val(),
                provider_name: $('#add-provider-name').val().trim(),
                duration: $('#add-duration').val().trim(),
                learning_hours: $('#add-learning-hours').val(),
                start_date: startDate || null,
                finish_date: finishDate || null,
                venue: $('#add-venue').val().trim(),
                instructor: $('#add-instructor').val().trim(),
                participants: addParticipants,
                _token: $('meta[name="csrf-token"]').attr('content') || '{{ csrf_token() }}'
            };

            // Loading state
            $btnSubmitAdd.prop('disabled', true).html('<span class="spinner-border spinner-border-sm me-1" role="status"></span> Saving...');

            $.ajax({
                url: "{{ route('manage.record.offline.store') }}",
                type: 'POST',
                data: JSON.stringify(payload),
                contentType: 'application/json',
                dataType: 'json',
                headers: {
                    'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') || '{{ csrf_token() }}',
                    'Accept': 'application/json'
                },
                success: function (res) {
                    $btnSubmitAdd.prop('disabled', false).html('<i class="ti ti-check fs-2"></i><span>Save Training</span>');

                    if (res && res.success) {
                        $addModalEl.modal('hide');
                        resetAddTrainingModal();

                        alert(res.message || 'Offline training successfully saved.');

                        // Reload KPI & Classification Summaries asynchronously
                        if (typeof window.loadOfflineKpiSummary === 'function') {
                            window.loadOfflineKpiSummary();
                        }
                        if (typeof window.loadOfflineClassificationSummary === 'function') {
                            window.loadOfflineClassificationSummary();
                        }
                    } else {
                        $addAlert.text(res.message || 'Failed to save training.').show();
                    }
                },
                error: function (xhr) {
                    $btnSubmitAdd.prop('disabled', false).html('<i class="ti ti-check fs-2"></i><span>Save Training</span>');

                    let errMsg = 'Failed to save training.';
                    if (xhr.responseJSON) {
                        if (xhr.responseJSON.errors) {
                            const errs = Object.values(xhr.responseJSON.errors).flat();
                            errMsg = errs.join(' ');
                        } else if (xhr.responseJSON.message) {
                            errMsg = xhr.responseJSON.message;
                        }
                    }
                    $addAlert.text(errMsg).show();
                }
            });
        });

        // Reset Add Training Modal
        function resetAddTrainingModal() {
            $addForm[0].reset();
            $('#add-month').val('');
            addParticipants = [];
            currentActiveParticipantIndex = -1;
            renderParticipantsTable();
            populateActiveParticipantSelect();
            updateSection4Review();
            $addAlert.hide().empty();
            $('#manual-participant-card').hide();
            $empSearchResults.hide().empty();
            $addScrollBody.scrollTop(0);
            $('.modal-step-item').removeClass('active');
            $('#step-nav-1').addClass('active');
        }

        // Cleanup on modal hidden
        $addModalEl.on('hidden.bs.modal', function () {
            resetAddTrainingModal();
        });

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
