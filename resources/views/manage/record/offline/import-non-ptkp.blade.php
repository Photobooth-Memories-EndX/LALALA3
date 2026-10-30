@extends('layouts.sidebar-master')

@section('title', 'Import Non-PTKP Data — OWL LMS')

@section('style')
<link rel="stylesheet" href="{{ asset('css/manage/non-ptkp-import.css') }}">
@endsection

@section('content')
<div class="page-body non-ptkp-import-page py-4">
    <div class="container-xl">
        <!-- BREADCRUMB / BACK LINK -->
        <div class="mb-3">
            <a href="{{ route('manage.record.offline') }}" class="btn-back-non-ptkp d-inline-flex align-items-center gap-1.5 text-decoration-none fw-medium">
                <i class="ti ti-arrow-left"></i>
                <span>Back to Offline Records</span>
            </a>
        </div>

        <!-- PAGE TITLE CARD -->
        <div class="card border-0 shadow-sm p-3 p-md-4 mb-4 rounded-3 bg-white">
            <div class="d-flex align-items-center justify-content-between flex-wrap gap-3">
                <div class="d-flex align-items-center gap-3">
                    <div class="rounded-3 d-flex align-items-center justify-content-center flex-shrink-0"
                         style="width: 54px; height: 54px; background-color: #fef3c7; color: #d97706; border-radius: 12px;">
                        <i class="ti ti-file-spreadsheet fs-1"></i>
                    </div>
                    <div>
                        <h2 class="h3 fw-bold mb-1 text-dark">Import Non-PTKP Data</h2>
                        <p class="text-muted small mb-0">Upload and validate Non-PTKP offline training records before importing into OWL LMS.</p>
                    </div>
                </div>
                <div>
                    <a href="{{ route('manage.record.offline.import-history.index') }}" class="btn btn-outline-secondary rounded-pill d-inline-flex align-items-center gap-2 px-3 py-2 me-2" id="btn-non-ptkp-history"><i class="ti ti-history fs-2"></i><span>Import History</span></a>
                    <a href="{{ route('manage.record.offline.import-non-ptkp.template') }}" class="btn btn-outline-primary rounded-pill d-inline-flex align-items-center gap-2 px-3 py-2" id="btn-download-non-ptkp-template">
                        <i class="ti ti-download fs-2"></i>
                        <span>Download Non-PTKP Template (.xlsx)</span>
                    </a>
                </div>
            </div>
        </div>

        <!-- 4-STAGE PROCESS INDICATOR -->
        <div class="card border-0 shadow-sm p-3 mb-4 rounded-3 bg-white">
            <div class="non-ptkp-steps">
                <div class="non-ptkp-step-item active" id="step-ind-1"><div class="non-ptkp-step-bubble">1</div><div class="non-ptkp-step-title">Upload Spreadsheet</div></div>
                <div class="non-ptkp-step-item" id="step-ind-2"><div class="non-ptkp-step-bubble">2</div><div class="non-ptkp-step-title">Validation Preview</div></div>
                <div class="non-ptkp-step-item" id="step-ind-3"><div class="non-ptkp-step-bubble">3</div><div class="non-ptkp-step-title">Review Data</div></div>
                <div class="non-ptkp-step-item" id="step-ind-4"><div class="non-ptkp-step-bubble">4</div><div class="non-ptkp-step-title">Import Records</div></div>
            </div>
        </div>

        <!-- STAGE 1: UPLOAD SPREADSHEET CARD -->
        <div class="card border-0 shadow-sm rounded-4 mb-4" id="non-ptkp-upload-card">
            <div class="card-body p-4">
                <div class="d-flex align-items-center justify-content-between mb-3 flex-wrap gap-2">
                    <div class="d-flex align-items-center gap-2">
                        <span class="badge bg-primary text-white rounded-circle d-inline-flex align-items-center justify-content-center" style="width: 26px; height: 26px; font-size: 0.8rem;">1</span>
                        <h4 class="card-title fw-bold text-dark mb-0">Upload Non-PTKP Spreadsheet</h4>
                    </div>
                </div>

                <!-- Dropzone Area -->
                <div class="non-ptkp-dropzone p-5 text-center my-3 position-relative" id="non-ptkp-dropzone">
                    <input type="file" id="non-ptkp-file-input" class="position-absolute top-0 start-0 w-100 h-100 opacity-0" style="cursor: pointer;" accept=".xlsx,.xls" />
                    <div class="dropzone-content py-3">
                        <div class="avatar avatar-lg rounded-circle mx-auto mb-3" style="background-color: #fef3c7; color: #d97706; width: 64px; height: 64px;">
                            <i class="ti ti-upload fs-1"></i>
                        </div>
                        <h5 class="fw-bold text-dark mb-1">Choose an Excel file or drag & drop here</h5>
                        <p class="text-muted small mb-2">Supported formats: <strong>.xlsx, .xls</strong> (Max 20MB)</p>
                        <div class="d-inline-flex align-items-center gap-1 text-primary fw-medium small mt-1">
                            <i class="ti ti-paperclip"></i>
                            <span id="non-ptkp-selected-filename">No file selected</span>
                        </div>
                    </div>
                </div>

                <!-- Upload Error Alert -->
                <div class="alert alert-danger border-0 rounded-3 small mb-3 py-2 px-3" id="non-ptkp-upload-error" style="display: none;"></div>

                <!-- Guidelines Box -->
                <div class="alert alert-info border-0 rounded-3 small mb-4" style="background-color: #f0f9ff; color: #0369a1;">
                    <div class="d-flex align-items-start gap-2">
                        <i class="ti ti-info-circle fs-3 flex-shrink-0 mt-0.5"></i>
                        <div>
                            <strong class="d-block mb-1">Non-PTKP OWL Spreadsheet Guidelines:</strong>
                            <ul class="mb-0 ps-3">
                                <li><strong>Template Structure:</strong> Supports official canonical 44 columns as well as two-row grouped and single-row headers.</li>
                                <li><strong>Training Event Grouping:</strong> Rows with matching course, batch, dates, type, and venue are automatically grouped into a single training event.</li>
                                <li><strong>Duplicate Prevention:</strong> Participants already enrolled in the same training in database or earlier rows are safely skipped (DUPLICATE status).</li>
                                <li><strong>Participant Snapshot Preservation:</strong> Participant profile data from the spreadsheet is safely preserved during import.</li>
                            </ul>
                        </div>
                    </div>
                </div>

                <!-- Action Button -->
                <div class="d-flex justify-content-end gap-2">
                    <button type="button" id="non-ptkp-btn-validate" class="btn btn-primary rounded-3 px-4 d-inline-flex align-items-center gap-2" style="background-color: #d97706; border-color: #d97706;" disabled>
                        <i class="ti ti-sparkles"></i>
                        <span>Validate & Preview Records</span>
                    </button>
                </div>
            </div>
        </div>

        <!-- LOADING CARD (VALIDATING STATE) -->
        <div class="card border-0 shadow-sm rounded-4 mb-4 py-5 text-center" id="non-ptkp-loading-card" style="display: none;">
            <div class="card-body">
                <div class="spinner-border text-primary" style="width: 3.5rem; height: 3.5rem; color: #d97706 !important;" role="status"></div>
                <h4 class="fw-bold text-dark mt-3 mb-1" id="non-ptkp-loading-title">Validating Spreadsheet...</h4>
                <p class="text-muted small mb-0" id="non-ptkp-loading-subtitle">Reading columns, resolving header groups, and checking existing records...</p>
            </div>
        </div>

        <!-- STAGE 2: VALIDATION PREVIEW CARD -->
        <div class="card border-0 shadow-sm rounded-4 mb-4" id="non-ptkp-preview-card" style="display: none;">
            <div class="card-header bg-white border-0 pt-4 pb-0 px-4">
                <div class="d-flex align-items-center justify-content-between flex-wrap gap-2">
                    <div class="d-flex align-items-center gap-2">
                        <span class="badge bg-primary text-white rounded-circle d-inline-flex align-items-center justify-content-center" style="width: 26px; height: 26px; font-size: 0.8rem;">2</span>
                        <h4 class="card-title fw-bold text-dark mb-0">Validation Preview</h4>
                        <span class="badge bg-light text-secondary border ms-1" id="non-ptkp-preview-filename">-</span>
                    </div>
                    <button type="button" class="btn btn-sm btn-outline-secondary rounded-pill px-3" id="non-ptkp-btn-reupload">
                        <i class="ti ti-arrow-left me-1"></i> Re-upload File
                    </button>
                </div>
            </div>

            <div class="card-body p-4">
                <!-- TEMPLATE HEADER CHECK CARD -->
                <div class="card border rounded-3 mb-3 shadow-none bg-light" id="non-ptkp-header-check-card" style="display: none;">
                    <div class="card-body p-3">
                        <div class="d-flex align-items-center justify-content-between flex-wrap gap-2">
                            <div class="d-flex align-items-center gap-2">
                                <div class="avatar avatar-sm rounded-circle d-flex align-items-center justify-content-center flex-shrink-0" style="background-color: #e0f2fe; color: #0284c7; width: 32px; height: 32px;">
                                    <i class="ti ti-layout-grid fs-3"></i>
                                </div>
                                <div>
                                    <h6 class="fw-bold mb-0 text-dark" id="non-ptkp-header-check-title">Template Header Validation</h6>
                                    <div class="text-muted extra-small" id="non-ptkp-header-check-subtitle">44 Expected Canonical Columns</div>
                                </div>
                            </div>
                            <div class="d-flex align-items-center gap-2 flex-wrap" id="non-ptkp-header-check-badges">
                                <span class="badge bg-success-lt px-2 py-1" id="non-ptkp-badge-header-valid">✓ 44 Valid</span>
                                <span class="badge bg-warning-lt px-2 py-1" id="non-ptkp-badge-header-matched" style="display: none;">⚠ Auto-Matched</span>
                                <span class="badge bg-danger-lt px-2 py-1" id="non-ptkp-badge-header-invalid" style="display: none;">✕ Issues Found</span>
                            </div>
                        </div>
                        <div id="non-ptkp-header-check-details" style="display: none;" class="mt-2 pt-2 border-top small"></div>
                    </div>
                </div>

                <!-- 6 KPI SUMMARY METRIC CARDS -->
                <div class="row g-2 mb-4">
                    <div class="col-4 col-md-2">
                        <div class="card border-0 shadow-none bg-light p-2.5 rounded-3 text-center h-100 kpi-card" title="Total spreadsheet rows read">
                            <div class="text-muted small fw-medium mb-0">Total</div>
                            <div class="h3 fw-bold text-dark mb-0" id="non-ptkp-kpi-total">0</div>
                        </div>
                    </div>
                    <div class="col-4 col-md-2">
                        <div class="card border-0 shadow-none p-2.5 rounded-3 text-center h-100 kpi-card" style="background-color: #ecfdf5;" title="Valid records ready to import">
                            <div class="small fw-medium mb-0" style="color: #065f46;">Valid</div>
                            <div class="h3 fw-bold mb-0" style="color: #047857;" id="non-ptkp-kpi-valid">0</div>
                        </div>
                    </div>
                    <div class="col-4 col-md-2">
                        <div class="card border-0 shadow-none p-2.5 rounded-3 text-center h-100 kpi-card" style="background-color: #fffbeb;" title="Valid records with nonfatal warnings">
                            <div class="small fw-medium mb-0" style="color: #92400e;">Warnings</div>
                            <div class="h3 fw-bold mb-0" style="color: #d97706;" id="non-ptkp-kpi-warning">0</div>
                        </div>
                    </div>
                    <div class="col-4 col-md-2">
                        <div class="card border-0 shadow-none p-2.5 rounded-3 text-center h-100 kpi-card" style="background-color: #f5f3ff;" title="Duplicate records (safely skipped)">
                            <div class="small fw-medium mb-0" style="color: #5b21b6;">Duplicates</div>
                            <div class="h3 fw-bold mb-0" style="color: #7c3aed;" id="non-ptkp-kpi-duplicate">0</div>
                        </div>
                    </div>
                    <div class="col-4 col-md-2">
                        <div class="card border-0 shadow-none p-2.5 rounded-3 text-center h-100 kpi-card" style="background-color: #fef2f2;" title="Records with fatal validation errors">
                            <div class="small fw-medium mb-0" style="color: #991b1b;">Invalid</div>
                            <div class="h3 fw-bold mb-0" style="color: #dc2626;" id="non-ptkp-kpi-invalid">0</div>
                        </div>
                    </div>
                    <div class="col-4 col-md-2">
                        <div class="card border-0 shadow-none p-2.5 rounded-3 text-center h-100 kpi-card" style="background-color: #f0fdfa;" title="Identified unique training sessions">
                            <div class="small fw-medium mb-0" style="color: #115e59;">Events</div>
                            <div class="h3 fw-bold mb-0" style="color: #0f766e;" id="non-ptkp-kpi-events">0</div>
                        </div>
                    </div>
                </div>

                <!-- NO NEW RECORDS TO IMPORT ALERT BANNER -->
                <div class="alert alert-warning border-0 rounded-3 mb-4 p-3" id="non-ptkp-no-new-records-alert" style="display: none; background-color: #fffbeb; color: #92400e;">
                    <div class="d-flex align-items-center gap-3">
                        <div class="avatar rounded-circle bg-warning text-white flex-shrink-0" style="width: 40px; height: 40px;">
                            <i class="ti ti-alert-circle fs-2"></i>
                        </div>
                        <div>
                            <h5 class="fw-bold mb-1">No New Records to Import</h5>
                            <p class="mb-0 small" id="non-ptkp-no-new-records-desc">All records already exist in the database. No new records will be imported.</p>
                        </div>
                    </div>
                </div>

                <!-- UNKNOWN HEADERS ALERT -->
                <div class="alert alert-secondary border-0 rounded-3 small mb-3 py-2 px-3" id="non-ptkp-unknown-headers-alert" style="display: none;">
                    <i class="ti ti-info-circle me-1"></i> Additional unrecognized columns skipped: <span id="non-ptkp-unknown-headers-list" class="fw-semibold"></span>
                </div>

                <!-- TABLE TOOLBAR: FILTER TABS & SEARCH -->
                <div class="d-flex align-items-center justify-content-between flex-wrap gap-3 mb-3">
                    <div class="d-flex align-items-center gap-1 bg-light p-1 rounded-pill border flex-wrap" id="non-ptkp-filter-tabs">
                        <button type="button" class="filter-tab-pill active" data-filter="all">All (<span id="count-all">0</span>)</button>
                        <button type="button" class="filter-tab-pill" data-filter="valid">Valid (<span id="count-valid">0</span>)</button>
                        <button type="button" class="filter-tab-pill" data-filter="warning">Warnings (<span id="count-warning">0</span>)</button>
                        <button type="button" class="filter-tab-pill" data-filter="duplicate">Duplicates (<span id="count-duplicate">0</span>)</button>
                        <button type="button" class="filter-tab-pill" data-filter="invalid">Invalid (<span id="count-invalid">0</span>)</button>
                    </div>

                    <div class="d-flex align-items-center gap-2 ms-auto flex-wrap">
                        <div class="input-icon" style="min-width: 260px;">
                            <span class="input-icon-addon"><i class="ti ti-search"></i></span>
                            <input type="text" class="form-control form-control-sm rounded-pill" id="non-ptkp-search-input" placeholder="Search NIP, Name, Course, PIC..." />
                        </div>
                        <select class="form-select form-select-sm rounded-pill" id="non-ptkp-page-size" style="width: 105px;">
                            <option value="10">10 / page</option>
                            <option value="25" selected>25 / page</option>
                            <option value="50">50 / page</option>
                            <option value="100">100 / page</option>
                        </select>
                    </div>
                </div>

                <!-- PREVIEW DATA TABLE -->
                <div class="table-responsive border rounded-3 mb-3" style="max-height: 480px; overflow-y: auto;">
                    <table class="table table-sm table-hover table-vcenter align-middle mb-0 non-ptkp-table" id="non-ptkp-preview-table" style="font-size: 0.825rem;">
                        <thead class="table-light sticky-top">
                            <tr>
                                <th style="width: 50px;" class="text-center">No</th>
                                <th style="width: 140px;">Status</th>
                                <th style="width: 120px;">Employee ID</th>
                                <th>Participant Name</th>
                                <th>Company</th>
                                <th>Course Title</th>
                                <th style="width: 110px;">Type</th>
                                <th style="width: 130px;">PIC</th>
                                <th style="min-width: 200px;">Validation Details</th>
                                <th style="width: 70px;" class="text-center">Action</th>
                            </tr>
                        </thead>
                        <tbody id="non-ptkp-preview-tbody">
                            <!-- Populated dynamically via JS -->
                        </tbody>
                    </table>
                </div>

                <!-- TABLE PAGINATION FOOTER -->
                <div class="d-flex align-items-center justify-content-between flex-wrap gap-2 pt-1 mb-4">
                    <div class="text-muted small" id="non-ptkp-pagination-info">
                        Showing 0 to 0 of 0 entries
                    </div>
                    <ul class="pagination pagination-sm m-0" id="non-ptkp-pagination-nav">
                        <!-- Populated via JS -->
                    </ul>
                </div>

                <!-- CONFIRM ACTION BAR -->
                <div class="d-flex align-items-center justify-content-between flex-wrap gap-3 pt-3 border-top">
                    <div>
                        <button type="button" class="btn btn-outline-secondary rounded-3 px-4" id="non-ptkp-btn-cancel-bottom">
                            Cancel
                        </button>
                    </div>
                    <div class="d-flex align-items-center gap-3">
                        <div class="text-end small text-muted d-none d-sm-block">
                            <span id="non-ptkp-summary-action-text">Ready to process records.</span>
                        </div>
                        <button type="button" class="btn btn-success rounded-3 px-4 py-2 d-inline-flex align-items-center gap-2 fw-semibold" id="non-ptkp-btn-confirm-import">
                            <i class="ti ti-check fs-2"></i>
                            <span>Import Valid Records (<span id="non-ptkp-import-btn-count">0</span>)</span>
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <!-- STAGE 3: LOADING DURING IMPORT -->
        <div class="card border-0 shadow-sm rounded-4 mb-4 py-5 text-center" id="non-ptkp-importing-card" style="display: none;">
            <div class="card-body">
                <div class="spinner-border text-success" style="width: 3.5rem; height: 3.5rem;" role="status"></div>
                <h4 class="fw-bold text-dark mt-3 mb-1">Importing Training Records...</h4>
                <p class="text-muted small mb-0">Executing database transaction, creating training sessions, and registering participants...</p>
            </div>
        </div>

        <!-- STAGE 4: RESULT CARD -->
        <div class="card border-0 shadow-sm rounded-4 mb-4" id="non-ptkp-result-card" style="display: none;">
            <div class="card-body p-5 text-center">
                <div class="avatar avatar-xl rounded-circle mx-auto mb-3" style="background-color: #ecfdf5; color: #059669; width: 72px; height: 72px;">
                    <i class="ti ti-circle-check fs-1"></i>
                </div>
                <h3 class="fw-bold text-dark mb-1">Import Completed Successfully!</h3>
                <p class="text-muted small mb-4">The offline training records have been processed and saved into the database.</p>

                <!-- 6 Result Metrics -->
                <div class="row g-3 justify-content-center mb-4 text-start" style="max-width: 820px; margin: 0 auto;">
                    <div class="col-6 col-sm-4">
                        <div class="card border-0 bg-light p-3 rounded-3 text-center">
                            <div class="text-muted small mb-1">Total Processed</div>
                            <div class="h3 fw-bold text-dark mb-0" id="res-non-ptkp-total">0</div>
                        </div>
                    </div>
                    <div class="col-6 col-sm-4">
                        <div class="card border-0 p-3 rounded-3 text-center" style="background-color: #ecfdf5;">
                            <div class="small fw-semibold mb-1" style="color: #065f46;">Participants Imported</div>
                            <div class="h3 fw-bold mb-0" style="color: #047857;" id="res-non-ptkp-imported">0</div>
                        </div>
                    </div>
                    <div class="col-6 col-sm-4">
                        <div class="card border-0 p-3 rounded-3 text-center" style="background-color: #f5f3ff;">
                            <div class="small fw-semibold mb-1" style="color: #5b21b6;">Duplicates Skipped</div>
                            <div class="h3 fw-bold mb-0" style="color: #7c3aed;" id="res-non-ptkp-duplicates">0</div>
                        </div>
                    </div>
                    <div class="col-6 col-sm-4">
                        <div class="card border-0 p-3 rounded-3 text-center" style="background-color: #fef2f2;">
                            <div class="small fw-semibold mb-1" style="color: #991b1b;">Invalid Rows Skipped</div>
                            <div class="h3 fw-bold mb-0" style="color: #dc2626;" id="res-non-ptkp-invalid">0</div>
                        </div>
                    </div>
                    <div class="col-6 col-sm-4">
                        <div class="card border-0 p-3 rounded-3 text-center" style="background-color: #f0fdfa;">
                            <div class="small fw-semibold mb-1" style="color: #115e59;">New Trainings Created</div>
                            <div class="h3 fw-bold mb-0" style="color: #0f766e;" id="res-non-ptkp-created">0</div>
                        </div>
                    </div>
                    <div class="col-6 col-sm-4">
                        <div class="card border-0 p-3 rounded-3 text-center" style="background-color: #eff6ff;">
                            <div class="small fw-semibold mb-1" style="color: #1e40af;">Existing Trainings Reused</div>
                            <div class="h3 fw-bold mb-0" style="color: #2563eb;" id="res-non-ptkp-reused">0</div>
                        </div>
                    </div>
                </div>

                <div class="d-flex align-items-center justify-content-center gap-3">
                    <button type="button" class="btn btn-outline-secondary rounded-pill px-4" id="non-ptkp-btn-import-another">
                        <i class="ti ti-upload me-1"></i> Import Another Spreadsheet
                    </button>
                    <a href="{{ route('manage.record.offline') }}" class="btn btn-primary rounded-pill px-4" style="background-color: #d97706; border-color: #d97706;">
                        <i class="ti ti-table me-1"></i> Kembali ke Offline Record
                    </a>
                </div>
            </div>
        </div>

    </div>
</div>

<!-- ROW DETAIL INSPECTION MODAL -->
<div class="modal fade" id="nonPtkpRowDetailModal" tabindex="-1" aria-labelledby="nonPtkpRowDetailModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-xl modal-dialog-scrollable">
        <div class="modal-content rounded-4 border-0 shadow">
            <div class="modal-header border-bottom py-3 px-4 bg-white rounded-top-4">
                <div class="d-flex align-items-center gap-2">
                    <span class="avatar rounded-3" style="background-color: #fef3c7; color: #d97706; width: 42px; height: 42px;">
                        <i class="ti ti-file-search fs-2"></i>
                    </span>
                    <div>
                        <h5 class="modal-title fw-bold text-dark mb-0" id="nonPtkpRowDetailModalLabel">Row Data Inspection</h5>
                        <div class="text-muted extra-small" style="font-size: 0.75rem;">Excel Row <span id="insp-row-num">-</span> &bull; 44 Canonical Fields</div>
                    </div>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>

            <div class="modal-body p-4 bg-white">
                <div id="insp-messages-container" class="mb-3"></div>

                <div class="row g-3">
                    <!-- 1. Participant / Employee Info -->
                    <div class="col-md-6">
                        <div class="card border-0 bg-light p-3 rounded-3 h-100">
                            <div class="fw-bold small text-dark mb-2 text-uppercase" style="font-size: 0.75rem; letter-spacing: 0.05em;">
                                <i class="ti ti-user me-1 text-primary"></i> 1. Participant Information
                            </div>
                            <div class="row g-2 small">
                                <div class="col-6"><span class="text-muted d-block">Employee ID:</span><span class="fw-semibold text-dark" id="insp-p-nip">-</span></div>
                                <div class="col-6"><span class="text-muted d-block">Name:</span><span class="fw-semibold text-dark" id="insp-p-name">-</span></div>
                                <div class="col-6"><span class="text-muted d-block">Grade:</span><span class="fw-semibold text-dark" id="insp-p-grade">-</span></div>
                                <div class="col-6"><span class="text-muted d-block">Job Position:</span><span class="fw-semibold text-dark" id="insp-p-position">-</span></div>
                                <div class="col-6"><span class="text-muted d-block">Team:</span><span class="fw-semibold text-dark" id="insp-p-team">-</span></div>
                                <div class="col-6"><span class="text-muted d-block">Department:</span><span class="fw-semibold text-dark" id="insp-p-dept">-</span></div>
                                <div class="col-6"><span class="text-muted d-block">Company:</span><span class="fw-semibold text-dark" id="insp-p-company">-</span></div>
                                <div class="col-6"><span class="text-muted d-block">LMS User Match:</span><span id="insp-p-user-match">-</span></div>
                            </div>
                        </div>
                    </div>

                    <!-- 2. Course & Batch Info -->
                    <div class="col-md-6">
                        <div class="card border-0 bg-light p-3 rounded-3 h-100">
                            <div class="fw-bold small text-dark mb-2 text-uppercase" style="font-size: 0.75rem; letter-spacing: 0.05em;">
                                <i class="ti ti-book me-1 text-primary"></i> 2. Training Information
                            </div>
                            <div class="row g-2 small">
                                <div class="col-12"><span class="text-muted d-block">Course Title:</span><span class="fw-semibold text-dark" id="insp-t-course">-</span></div>
                                <div class="col-6"><span class="text-muted d-block">Sub Course Title:</span><span class="fw-semibold text-dark" id="insp-t-subcourse">-</span></div>
                                <div class="col-6"><span class="text-muted d-block">Batch:</span><span class="fw-semibold text-dark" id="insp-t-batch">-</span></div>
                                <div class="col-6"><span class="text-muted d-block">Type:</span><span class="fw-semibold text-dark" id="insp-t-type">-</span></div>
                                <div class="col-6"><span class="text-muted d-block">Category:</span><span class="fw-semibold text-dark" id="insp-t-category">-</span></div>
                                <div class="col-6"><span class="text-muted d-block">Level:</span><span class="fw-semibold text-dark" id="insp-t-level">-</span></div>
                                <div class="col-6"><span class="text-muted d-block">Venue:</span><span class="fw-semibold text-dark" id="insp-t-venue">-</span></div>
                                <div class="col-6"><span class="text-muted d-block">Instructor:</span><span class="fw-semibold text-dark" id="insp-t-instructor">-</span></div>
                            </div>
                        </div>
                    </div>

                    <!-- 3. Schedule, Provider & Classification -->
                    <div class="col-md-6">
                        <div class="card border-0 bg-light p-3 rounded-3 h-100">
                            <div class="fw-bold small text-dark mb-2 text-uppercase" style="font-size: 0.75rem; letter-spacing: 0.05em;">
                                <i class="ti ti-calendar-event me-1 text-primary"></i> 3. Schedule & Classification
                            </div>
                            <div class="row g-2 small">
                                <div class="col-6"><span class="text-muted d-block">Start Date:</span><span class="fw-semibold text-dark" id="insp-t-start-date">-</span></div>
                                <div class="col-6"><span class="text-muted d-block">End Date:</span><span class="fw-semibold text-dark" id="insp-t-end-date">-</span></div>
                                <div class="col-6"><span class="text-muted d-block">Start Time:</span><span class="fw-semibold text-dark" id="insp-t-start-time">-</span></div>
                                <div class="col-6"><span class="text-muted d-block">End Time:</span><span class="fw-semibold text-dark" id="insp-t-end-time">-</span></div>
                                <div class="col-6"><span class="text-muted d-block">Learning Hours:</span><span class="fw-semibold text-dark" id="insp-t-hours">-</span></div>
                                <div class="col-6"><span class="text-muted d-block">Duration (Seconds):</span><span class="fw-semibold text-dark" id="insp-t-duration">-</span></div>
                                <div class="col-6"><span class="text-muted d-block">Main Classification:</span><span class="fw-semibold text-dark" id="insp-t-main-class">-</span></div>
                                <div class="col-6"><span class="text-muted d-block">Sub Classification:</span><span class="fw-semibold text-dark" id="insp-t-sub-class">-</span></div>
                                <div class="col-6"><span class="text-muted d-block">Delivery Method:</span><span class="fw-semibold text-dark" id="insp-t-delivery">-</span></div>
                                <div class="col-6"><span class="text-muted d-block">Provider Name:</span><span class="fw-semibold text-dark" id="insp-t-provider">-</span></div>
                            </div>
                        </div>
                    </div>

                    <!-- 4. Participation, Obligation & Evaluation -->
                    <div class="col-md-6">
                        <div class="card border-0 bg-light p-3 rounded-3 h-100">
                            <div class="fw-bold small text-dark mb-2 text-uppercase" style="font-size: 0.75rem; letter-spacing: 0.05em;">
                                <i class="ti ti-star me-1 text-primary"></i> 4. Evaluation & Obligation
                            </div>
                            <div class="row g-2 small">
                                <div class="col-6"><span class="text-muted d-block">% Progress:</span><span class="fw-semibold text-dark" id="insp-o-progress">-</span></div>
                                <div class="col-6"><span class="text-muted d-block">Progress Status:</span><span class="fw-semibold text-dark" id="insp-o-progress-status">-</span></div>
                                <div class="col-6"><span class="text-muted d-block">Attendance:</span><span class="fw-semibold text-dark" id="insp-o-attendance">-</span></div>
                                <div class="col-6"><span class="text-muted d-block">Attendance Reason:</span><span class="fw-semibold text-dark" id="insp-o-attendance-reason">-</span></div>
                                <div class="col-6"><span class="text-muted d-block">Bond Status:</span><span class="fw-semibold text-dark" id="insp-o-bond-status">-</span></div>
                                <div class="col-6"><span class="text-muted d-block">Bond Duration:</span><span class="fw-semibold text-dark" id="insp-o-bond-duration">-</span></div>
                                <div class="col-6"><span class="text-muted d-block">Bond End Date:</span><span class="fw-semibold text-dark" id="insp-o-bond-end-date">-</span></div>
                                <div class="col-6"><span class="text-muted d-block">Bond Amount:</span><span class="fw-semibold text-dark" id="insp-o-bond-amount">-</span></div>
                                <div class="col-6"><span class="text-muted d-block">L1 Score:</span><span class="fw-semibold text-dark" id="insp-e-l1">-</span></div>
                                <div class="col-6"><span class="text-muted d-block">L2 (Score / Status):</span><span class="fw-semibold text-dark" id="insp-e-l2">-</span></div>
                                <div class="col-6"><span class="text-muted d-block">L3 (Score / Status):</span><span class="fw-semibold text-dark" id="insp-e-l3">-</span></div>
                                <div class="col-6"><span class="text-muted d-block">PIC:</span><span class="fw-semibold text-dark" id="insp-e-pic">-</span></div>
                                <div class="col-6"><span class="text-muted d-block">Certificate:</span><span class="fw-semibold text-dark" id="insp-e-cert">-</span></div>
                                <div class="col-6"><span class="text-muted d-block">Total Engagement Time:</span><span class="fw-semibold text-dark" id="insp-e-engagement">-</span></div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="modal-footer border-top py-3 px-4 bg-light rounded-bottom-4">
                <button type="button" class="btn btn-secondary rounded-pill px-4" data-bs-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>
@endsection

@section('script')
<script>
    window.NON_PTKP_CONFIG = {
        previewUrl: "{{ route('manage.record.offline.import-non-ptkp.preview') }}",
        confirmUrl: "{{ route('manage.record.offline.import-non-ptkp.confirm') }}",
        cancelUrl:  "{{ route('manage.record.offline.import-non-ptkp.cancel') }}",
        offlineUrl: "{{ route('manage.record.offline') }}",
        csrfToken:  "{{ csrf_token() }}"
    };
</script>
<script src="{{ asset('js/manage/non-ptkp-import.js') }}"></script>
@endsection
