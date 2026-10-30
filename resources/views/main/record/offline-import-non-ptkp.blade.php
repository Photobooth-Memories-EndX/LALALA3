@extends('layouts.sidebar-master')

@section('content')
<div class="page-body manage-record-page manage-record-import-non-ptkp">
    <div class="container-xl">

        <!-- BREADCRUMB -->
        <nav aria-label="breadcrumb" class="mb-3">
            <ol class="breadcrumb mb-0">
                <li class="breadcrumb-item">
                    <a href="{{ route('manage.dashboard') }}" class="text-decoration-none">Manage Record</a>
                </li>
                <li class="breadcrumb-item">
                    <a href="{{ route('manage.record.offline') }}" class="text-decoration-none">Offline Record</a>
                </li>
                <li class="breadcrumb-item active" aria-current="page">Import Non-PTKP Data</li>
            </ol>
        </nav>

        <!-- TOP BACK ACTION -->
        <div class="mb-3">
            <a href="{{ route('manage.record.offline') }}" class="btn-back d-inline-flex align-items-center gap-1.5 text-decoration-none fw-medium" id="btn-top-back">
                <i class="ti ti-arrow-left fs-3"></i>
                <span>Back to Offline Records</span>
            </a>
        </div>

        <!-- PAGE TITLE CARD -->
        <div class="card border-0 shadow-sm p-3 p-md-4 mb-4 rounded-3 bg-white record-section-header">
            <div class="d-flex align-items-center justify-content-between flex-wrap gap-3">
                <div class="d-flex align-items-center gap-3">
                    <div class="rounded-3 d-flex align-items-center justify-content-center flex-shrink-0 record-section-icon"
                         style="width: 54px; height: 54px; background-color: #fef3c7; color: #d97706; border-radius: 12px;">
                        <i class="ti ti-file-spreadsheet fs-1"></i>
                    </div>
                    <div>
                        <h2 class="h3 fw-bold mb-1 text-dark">Import Non-PTKP Data</h2>
                        <p class="text-muted small mb-0">Upload and validate Non-PTKP offline training records before importing into OWL LMS.</p>
                    </div>
                </div>
                <div class="d-flex align-items-center gap-2">
                    <a href="{{ route('manage.record.offline.import.template') }}" class="btn btn-outline-primary rounded-3 px-3 d-inline-flex align-items-center gap-1.5" id="btn-download-template" title="Download Official Excel Template">
                        <i class="ti ti-download fs-2"></i>
                        <span>Download Template</span>
                    </a>
                </div>
            </div>
        </div>

        <!-- MODERN STEP PROGRESS INDICATOR -->
        <div class="card border-0 shadow-sm rounded-4 mb-4 bg-white stepper-card">
            <div class="card-body p-3 p-md-4">
                <div class="row g-2 stepper-wrapper">
                    <!-- Step 1 -->
                    <div class="col-6 col-md-3">
                        <div class="stepper-item active" id="stepper-step-1">
                            <div class="stepper-badge">
                                <span class="stepper-num">1</span>
                                <i class="ti ti-check stepper-check"></i>
                            </div>
                            <div class="stepper-content">
                                <div class="stepper-title">Upload Spreadsheet</div>
                                <div class="stepper-desc text-muted">Select .xlsx / .xls file</div>
                            </div>
                        </div>
                    </div>
                    <!-- Step 2 -->
                    <div class="col-6 col-md-3">
                        <div class="stepper-item pending" id="stepper-step-2">
                            <div class="stepper-badge">
                                <span class="stepper-num">2</span>
                                <i class="ti ti-check stepper-check"></i>
                            </div>
                            <div class="stepper-content">
                                <div class="stepper-title">Validation Preview</div>
                                <div class="stepper-desc text-muted">Validate columns & records</div>
                            </div>
                        </div>
                    </div>
                    <!-- Step 3 -->
                    <div class="col-6 col-md-3">
                        <div class="stepper-item pending" id="stepper-step-3">
                            <div class="stepper-badge">
                                <span class="stepper-num">3</span>
                                <i class="ti ti-check stepper-check"></i>
                            </div>
                            <div class="stepper-content">
                                <div class="stepper-title">Review Data</div>
                                <div class="stepper-desc text-muted">Review status & details</div>
                            </div>
                        </div>
                    </div>
                    <!-- Step 4 -->
                    <div class="col-6 col-md-3">
                        <div class="stepper-item pending" id="stepper-step-4">
                            <div class="stepper-badge">
                                <span class="stepper-num">4</span>
                                <i class="ti ti-check stepper-check"></i>
                            </div>
                            <div class="stepper-content">
                                <div class="stepper-title">Import Records</div>
                                <div class="stepper-desc text-muted">Save to database</div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- =================================================================== -->
        <!-- STATE 1: UPLOAD SPREADSHEET                                         -->
        <!-- =================================================================== -->
        <div class="card border-0 shadow-sm rounded-4 mb-4 panel-import-state" id="panel-state-upload">
            <div class="card-body p-4 p-md-5">
                <div class="d-flex align-items-center justify-content-between mb-3 flex-wrap gap-2">
                    <div class="d-flex align-items-center gap-2">
                        <span class="badge bg-primary text-white rounded-circle d-inline-flex align-items-center justify-content-center" style="width: 28px; height: 28px; font-size: 0.85rem; background-color: #d97706 !important;">1</span>
                        <h4 class="fw-bold text-dark mb-0">Upload Spreadsheet</h4>
                    </div>
                    <span class="badge bg-light text-secondary border px-2.5 py-1">Supported formats: .xlsx, .xls</span>
                </div>

                <!-- Dropzone Upload Area -->
                <div class="upload-dropzone border border-2 border-dashed rounded-4 p-5 text-center my-3 bg-light position-relative" id="dropzone-container">
                    <input type="file" id="excel-file-input" class="position-absolute top-0 start-0 w-100 h-100 opacity-0" style="cursor: pointer;" accept=".xlsx,.xls" />
                    <div class="dropzone-content py-2">
                        <div class="avatar avatar-lg rounded-circle mx-auto mb-3" style="background-color: #fef3c7; color: #d97706; width: 64px; height: 64px;">
                            <i class="ti ti-cloud-upload fs-1"></i>
                        </div>
                        <h5 class="fw-bold text-dark mb-1">Choose an Excel file or drag & drop here</h5>
                        <p class="text-muted small mb-2">Supported formats: <strong>.xlsx, .xls</strong> (Max 20MB)</p>
                        
                        <div class="selected-file-pill d-none align-items-center gap-2 mx-auto mt-3 py-1.5 px-3 rounded-pill bg-white border shadow-sm" id="selected-file-box" style="max-width: 480px;">
                            <i class="ti ti-file-spreadsheet text-success fs-3"></i>
                            <span class="text-dark fw-semibold small text-truncate" id="selected-filename-text">No file selected</span>
                            <button type="button" class="btn btn-sm btn-ghost-danger rounded-circle p-1 ms-auto" id="btn-clear-file" title="Remove file">
                                <i class="ti ti-x"></i>
                            </button>
                        </div>
                    </div>
                </div>

                <!-- Upload Error Box -->
                <div class="alert alert-danger border-0 rounded-3 small mb-4 py-2.5 px-3" id="upload-error-box" style="display: none;">
                    <div class="d-flex align-items-start gap-2">
                        <i class="ti ti-alert-triangle fs-3 flex-shrink-0 mt-0.5"></i>
                        <div id="upload-error-message">File validation error occurred.</div>
                    </div>
                </div>

                <!-- Guidelines Alert -->
                <div class="alert alert-info border-0 rounded-3 small mb-4 guidelines-alert" style="background-color: #f0f9ff; color: #0369a1;">
                    <div class="d-flex align-items-start gap-2.5">
                        <i class="ti ti-info-circle fs-2 flex-shrink-0 mt-0.5"></i>
                        <div class="w-100">
                            <strong class="d-block mb-1.5 text-dark">Non-PTKP OWL Spreadsheet Guidelines:</strong>
                            <ul class="mb-0 ps-3">
                                <li>Use the official OWL Non-PTKP template.</li>
                                <li>Template with <strong>canonical 44 columns</strong> is supported in accordance with backend validation.</li>
                                <li>Legacy format with <strong>two-row grouped headers</strong> is automatically supported.</li>
                                <li>Do not rename or remove mandatory headers: <code>Course Name</code> / <code>Course Title</code>, <code>ID</code> / <code>Employee ID</code>, and <code>Name</code>.</li>
                                <li>Each row represents <strong>one training participant</strong>.</li>
                                <li>Participants in the same training session will be <strong>automatically grouped into a single training event</strong> based on course, batch, dates, type, and venue.</li>
                                <li>Duplicate records are handled via safe deduplication and <strong>skipped without modifying existing database records</strong>.</li>
                            </ul>
                        </div>
                    </div>
                </div>

                <!-- Action Buttons: Cancel, Download Template, Validate & Preview -->
                <div class="d-flex align-items-center justify-content-between flex-wrap gap-2 pt-2 border-top">
                    <a href="{{ route('manage.record.offline') }}" class="btn btn-outline-secondary rounded-3 px-4">
                        <i class="ti ti-arrow-left me-1"></i> Back
                    </a>
                    <div class="d-flex align-items-center gap-2">
                        <a href="{{ route('manage.record.offline.import.template') }}" class="btn btn-outline-primary rounded-3 px-3">
                            <i class="ti ti-download me-1"></i> Download Template
                        </a>
                        <button type="button" class="btn btn-primary rounded-3 px-4 d-inline-flex align-items-center gap-2" id="btn-validate-upload" style="background-color: #d97706; border-color: #d97706;" disabled>
                            <i class="ti ti-sparkles"></i>
                            <span>Validate & Preview Records</span>
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <!-- =================================================================== -->
        <!-- STATE 2: VALIDATING / LOADING                                       -->
        <!-- =================================================================== -->
        <div class="card border-0 shadow-sm rounded-4 mb-4 py-5 text-center panel-import-state" id="panel-state-loading" style="display: none;">
            <div class="card-body py-5">
                <div class="spinner-border text-primary mb-3" style="width: 3.5rem; height: 3.5rem; color: #d97706 !important;" role="status"></div>
                <h4 class="fw-bold text-dark mt-2 mb-1" id="loading-state-title">Validating Spreadsheet...</h4>
                <p class="text-muted small mb-3" id="loading-state-subtitle">Reading columns, resolving header groups, and checking existing records...</p>
                <div class="progress progress-sm mx-auto" style="max-width: 320px; height: 4px;">
                    <div class="progress-bar progress-bar-indeterminate" style="background-color: #d97706;"></div>
                </div>
            </div>
        </div>

        <!-- =================================================================== -->
        <!-- STATE 3: PREVIEW & REVIEW DATA                                      -->
        <!-- =================================================================== -->
        <div class="card border-0 shadow-sm rounded-4 mb-4 panel-import-state" id="panel-state-preview" style="display: none;">
            <div class="card-header bg-white border-bottom pt-4 pb-3 px-4 rounded-top-4">
                <div class="d-flex align-items-center justify-content-between flex-wrap gap-2">
                    <div class="d-flex align-items-center gap-2.5 flex-wrap">
                        <span class="badge bg-primary text-white rounded-circle d-inline-flex align-items-center justify-content-center" style="width: 28px; height: 28px; font-size: 0.85rem; background-color: #d97706 !important;">2</span>
                        <h4 class="fw-bold text-dark mb-0">Non-PTKP Validation & Review</h4>
                        <span class="badge bg-light text-secondary border px-2.5 py-1" id="preview-filename-badge">-</span>
                    </div>
                    <div class="d-flex align-items-center gap-2">
                        <button type="button" class="btn btn-sm btn-outline-secondary rounded-pill px-3" id="btn-reupload-file">
                            <i class="ti ti-arrow-left me-1"></i> Cancel & Re-upload
                        </button>
                    </div>
                </div>
            </div>

            <div class="card-body p-4">

                <!-- 1. TEMPLATE HEADER STRUCTURE DIAGNOSTIC CARD -->
                <div class="card border rounded-3 mb-4 shadow-none" id="preview-header-check-card" style="display: none; background-color: #f8fafc; border-color: #e2e8f0 !important;">
                    <div class="card-body p-3">
                        <div class="d-flex align-items-center justify-content-between flex-wrap gap-2">
                            <div class="d-flex align-items-center gap-2.5">
                                <div class="avatar avatar-sm rounded-circle d-flex align-items-center justify-content-center flex-shrink-0" style="background-color: #e0f2fe; color: #0284c7; width: 36px; height: 36px;">
                                    <i class="ti ti-layout-grid fs-3"></i>
                                </div>
                                <div>
                                    <h6 class="fw-bold mb-0 text-dark">Template Header Validation</h6>
                                    <div class="text-muted extra-small" id="header-check-subtitle">44 Expected Columns &bull; 44 Detected Columns</div>
                                </div>
                            </div>
                            <div class="d-flex align-items-center gap-2 flex-wrap" id="header-check-badges">
                                <span class="badge bg-success-lt px-2 py-1" id="badge-header-valid">✓ 44 Valid</span>
                                <span class="badge bg-warning-lt px-2 py-1" id="badge-header-matched" style="display: none;">⚠ 0 Auto-Matched</span>
                                <span class="badge bg-danger-lt px-2 py-1" id="badge-header-invalid" style="display: none;">✕ 0 Needs Correction</span>
                            </div>
                        </div>

                        <!-- Diagnostic details list -->
                        <div id="header-check-details" style="display: none;" class="mt-3 pt-2 border-top">
                            <div id="header-check-issues-container" class="small"></div>
                        </div>
                    </div>
                </div>

                <!-- 2. 6 STATISTIK RINGKASAN HASIL VALIDASI -->
                <div class="row g-2 g-md-3 mb-4">
                    <!-- Total -->
                    <div class="col-4 col-md-2">
                        <div class="card border-0 shadow-none bg-light p-3 rounded-3 text-center h-100 stat-box" title="Total spreadsheet rows read">
                            <div class="text-muted extra-small fw-medium mb-1">Total Rows</div>
                            <div class="h3 fw-bold text-dark mb-0" id="stat-total-rows">0</div>
                            <div class="extra-small text-muted mt-1">records read</div>
                        </div>
                    </div>
                    <!-- Valid -->
                    <div class="col-4 col-md-2">
                        <div class="card border-0 shadow-none p-3 rounded-3 text-center h-100 stat-box" style="background-color: #ecfdf5;" title="Valid records ready to import">
                            <div class="extra-small fw-medium mb-1" style="color: #065f46;">VALID</div>
                            <div class="h3 fw-bold mb-0" style="color: #047857;" id="stat-valid-rows">0</div>
                            <div class="extra-small mt-1" style="color: #065f46;">ready to import</div>
                        </div>
                    </div>
                    <!-- Warnings -->
                    <div class="col-4 col-md-2">
                        <div class="card border-0 shadow-none p-3 rounded-3 text-center h-100 stat-box" style="background-color: #fffbeb;" title="Valid records with nonfatal warnings">
                            <div class="extra-small fw-medium mb-1" style="color: #92400e;">WARNING</div>
                            <div class="h3 fw-bold mb-0" style="color: #d97706;" id="stat-warning-rows">0</div>
                            <div class="extra-small mt-1" style="color: #92400e;">nonfatal issues</div>
                        </div>
                    </div>
                    <!-- Duplicates -->
                    <div class="col-4 col-md-2">
                        <div class="card border-0 shadow-none p-3 rounded-3 text-center h-100 stat-box" style="background-color: #f5f3ff;" title="Existing records or duplicates that will be skipped">
                            <div class="extra-small fw-medium mb-1" style="color: #5b21b6;">DUPLICATE</div>
                            <div class="h3 fw-bold mb-0" style="color: #7c3aed;" id="stat-duplicate-rows">0</div>
                            <div class="extra-small mt-1" style="color: #5b21b6;">will be skipped</div>
                        </div>
                    </div>
                    <!-- Invalid -->
                    <div class="col-4 col-md-2">
                        <div class="card border-0 shadow-none p-3 rounded-3 text-center h-100 stat-box" style="background-color: #fef2f2;" title="Records with fatal validation errors">
                            <div class="extra-small fw-medium mb-1" style="color: #991b1b;">INVALID</div>
                            <div class="h3 fw-bold mb-0" style="color: #dc2626;" id="stat-invalid-rows">0</div>
                            <div class="extra-small mt-1" style="color: #991b1b;">fatal errors</div>
                        </div>
                    </div>
                    <!-- Events -->
                    <div class="col-4 col-md-2">
                        <div class="card border-0 shadow-none p-3 rounded-3 text-center h-100 stat-box" style="background-color: #f0fdfa;" title="Total unique training sessions identified">
                            <div class="extra-small fw-medium mb-1" style="color: #115e59;">Events</div>
                            <div class="h3 fw-bold mb-0" style="color: #0f766e;" id="stat-training-events">0</div>
                            <div class="extra-small mt-1" style="color: #115e59;">unique sessions</div>
                        </div>
                    </div>
                </div>

                <!-- 3. RINGKASAN KONDISIONAL STATUS BANNER -->
                <div id="preview-state-banner" class="mb-4" style="display: none;"></div>

                <!-- 4. PERBANDINGAN DATA SEBELUM IMPORT (Tahap 8) -->
                <div class="card border rounded-3 mb-4 shadow-none comparison-summary-card" style="background-color: #fafbfc; border-color: #e2e8f0 !important;">
                    <div class="card-body p-3 p-md-4">
                        <div class="d-flex align-items-center justify-content-between flex-wrap gap-2 mb-3">
                            <div class="d-flex align-items-center gap-2">
                                <i class="ti ti-scale text-primary fs-2"></i>
                                <h5 class="fw-bold text-dark mb-0">Pre-Import Data Comparison</h5>
                            </div>
                            <span class="badge bg-secondary-lt small">Database Verification Result</span>
                        </div>

                        <div class="row g-3">
                            <div class="col-md-6">
                                <div class="p-3 rounded-3 bg-white border h-100">
                                    <div class="d-flex align-items-center justify-content-between mb-2">
                                        <span class="text-muted small fw-medium">New Records (Ready to Import)</span>
                                        <span class="badge bg-success-lt fw-bold fs-4" id="comp-new-records">0</span>
                                    </div>
                                    <p class="text-muted extra-small mb-0">New participants and training events that meet all validation rules and will be saved to the database.</p>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="p-3 rounded-3 bg-white border h-100">
                                    <div class="d-flex align-items-center justify-content-between mb-2">
                                        <span class="text-muted small fw-medium">Duplicate Records (Skipped)</span>
                                        <span class="badge bg-purple-lt fw-bold fs-4" id="comp-duplicate-records">0</span>
                                    </div>
                                    <p class="text-muted extra-small mb-0">Participants already enrolled in the same training in database or earlier rows in the file. <em>Existing database records will not be modified.</em></p>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="p-3 rounded-3 bg-white border h-100">
                                    <div class="d-flex align-items-center justify-content-between mb-2">
                                        <span class="text-muted small fw-medium">Records with Warnings</span>
                                        <span class="badge bg-warning-lt fw-bold fs-4" id="comp-warning-records">0</span>
                                    </div>
                                    <p class="text-muted extra-small mb-0">Valid records ready to import, but with nonfatal notes (e.g. participant has no LMS account or optional dates are blank).</p>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="p-3 rounded-3 bg-white border h-100">
                                    <div class="d-flex align-items-center justify-content-between mb-2">
                                        <span class="text-muted small fw-medium">Invalid Records (Errors)</span>
                                        <span class="badge bg-danger-lt fw-bold fs-4" id="comp-invalid-records">0</span>
                                    </div>
                                    <p class="text-muted extra-small mb-0">Records violating mandatory fields or invalid data formats. Must be corrected in spreadsheet before importing.</p>
                                </div>
                            </div>
                        </div>

                        <div class="mt-3 pt-2 border-top d-flex align-items-center gap-2 extra-small text-muted">
                            <i class="ti ti-shield-check text-success fs-3 flex-shrink-0"></i>
                            <span>Database integrity guaranteed: Preview only reads data and performs zero writes to the database.</span>
                        </div>
                    </div>
                </div>

                <!-- 5. FILTER TABS & SEARCH -->
                <div class="d-flex align-items-center justify-content-between flex-wrap gap-2 mb-3">
                    <div class="btn-group rounded-pill p-1 bg-light border flex-wrap" role="group" id="preview-filter-group">
                        <button type="button" class="btn btn-sm btn-white active rounded-pill px-3 filter-tab-btn" data-filter="all">
                            All (<span id="filter-count-all">0</span>)
                        </button>
                        <button type="button" class="btn btn-sm btn-ghost-secondary rounded-pill px-3 filter-tab-btn" data-filter="valid">
                            <span class="status-dot bg-success d-inline-block me-1"></span> Valid (<span id="filter-count-valid">0</span>)
                        </button>
                        <button type="button" class="btn btn-sm btn-ghost-secondary rounded-pill px-3 filter-tab-btn" data-filter="warning">
                            <span class="status-dot bg-warning d-inline-block me-1"></span> Warnings (<span id="filter-count-warning">0</span>)
                        </button>
                        <button type="button" class="btn btn-sm btn-ghost-secondary rounded-pill px-3 filter-tab-btn" data-filter="duplicate">
                            <span class="status-dot bg-purple d-inline-block me-1"></span> Duplicates (<span id="filter-count-duplicate">0</span>)
                        </button>
                        <button type="button" class="btn btn-sm btn-ghost-secondary rounded-pill px-3 filter-tab-btn" data-filter="invalid">
                            <span class="status-dot bg-danger d-inline-block me-1"></span> Invalid (<span id="filter-count-invalid">0</span>)
                        </button>
                    </div>

                    <div class="d-flex align-items-center gap-2">
                        <div class="input-icon" style="min-width: 220px;">
                            <input type="text" class="form-control form-control-sm rounded-pill" id="preview-search-input" placeholder="Filter records (name, ID, course, PIC)..." />
                            <span class="input-icon-addon"><i class="ti ti-search"></i></span>
                        </div>
                        <span class="text-muted extra-small">
                            Showing <span id="preview-visible-count">0</span> of <span id="preview-total-count">0</span> records
                        </span>
                    </div>
                </div>

                <!-- 6. TABEL PERBANDINGAN STATUS SEBELUM IMPORT (Tahap 7) -->
                <div class="table-responsive border rounded-3 mb-4 preview-table-container">
                    <table class="table table-sm table-hover table-vcenter mb-0 align-middle" id="preview-main-table">
                        <thead class="table-light text-uppercase extra-small text-muted sticky-top" style="z-index: 2; font-size: 0.75rem; letter-spacing: 0.04em;">
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
                        <tbody id="preview-main-tbody">
                            <!-- Populated dynamically by JavaScript -->
                        </tbody>
                    </table>
                </div>

                <!-- PAGINATION CONTROLS -->
                <div class="d-flex align-items-center justify-content-between flex-wrap gap-2 mb-4" id="preview-pagination-container" style="display: none;">
                    <div class="text-muted extra-small">
                        Showing page <span id="page-current">1</span> of <span id="page-total">1</span>
                    </div>
                    <div class="btn-group btn-group-sm">
                        <button type="button" class="btn btn-outline-secondary" id="btn-page-prev" disabled>
                            <i class="ti ti-chevron-left me-1"></i> Prev
                        </button>
                        <button type="button" class="btn btn-outline-secondary" id="btn-page-next" disabled>
                            Next <i class="ti ti-chevron-right ms-1"></i>
                        </button>
                    </div>
                </div>

                <!-- 7. TAHAP 4: FOOTER ACTIONS & CONFIRMATION (Tahap 9) -->
                <div class="d-flex align-items-center justify-content-between flex-wrap gap-3 pt-3 border-top">
                    <div>
                        <span class="text-muted small" id="preview-footer-summary-text">
                            Review validation results before confirming import.
                        </span>
                    </div>

                    <div class="d-flex align-items-center gap-2">
                        <button type="button" class="btn btn-outline-secondary rounded-3 px-3" id="btn-action-reupload">
                            <i class="ti ti-arrow-left me-1"></i> Cancel & Re-upload
                        </button>
                        <a href="{{ route('manage.record.offline') }}" class="btn btn-outline-secondary rounded-3 px-3" id="btn-action-cancel">
                            Cancel
                        </a>
                        <button type="button" class="btn btn-primary rounded-3 px-4 d-inline-flex align-items-center gap-1.5" id="btn-action-confirm-import" style="background-color: #d97706; border-color: #d97706;">
                            <i class="ti ti-check fs-2"></i>
                            <span id="btn-confirm-label">Confirm & Import</span>
                        </button>
                    </div>
                </div>

            </div>
        </div>

        <!-- =================================================================== -->
        <!-- STATE 4: IMPORTING IN PROGRESS                                      -->
        <!-- =================================================================== -->
        <div class="card border-0 shadow-sm rounded-4 mb-4 py-5 text-center panel-import-state" id="panel-state-importing" style="display: none;">
            <div class="card-body py-5">
                <div class="spinner-border text-primary mb-3" style="width: 3.5rem; height: 3.5rem; color: #d97706 !important;" role="status"></div>
                <h4 class="fw-bold text-dark mt-2 mb-1">Importing Training Records...</h4>
                <p class="text-muted small mb-3">Executing database transaction, creating training sessions, and registering participants...</p>
                <div class="progress progress-sm mx-auto" style="max-width: 340px; height: 4px;">
                    <div class="progress-bar progress-bar-indeterminate" style="background-color: #d97706;"></div>
                </div>
            </div>
        </div>

        <!-- =================================================================== -->
        <!-- STATE 5: IMPORT RESULT SUMMARY                                      -->
        <!-- =================================================================== -->
        <div class="card border-0 shadow-sm rounded-4 mb-4 panel-import-state" id="panel-state-result" style="display: none;">
            <div class="card-body p-4 p-md-5 text-center">
                <div class="avatar avatar-xl rounded-circle mx-auto mb-3" style="background-color: #ecfdf5; color: #059669; width: 72px; height: 72px;">
                    <i class="ti ti-check fs-1"></i>
                </div>
                <h3 class="fw-bold text-dark mb-1">Non-PTKP Import Completed!</h3>
                <p class="text-muted small mb-4">Summary of Non-PTKP offline training records processed and saved to database.</p>

                <!-- 6 Metrics Result Grid -->
                <div class="row g-3 justify-content-center mb-4 text-start" style="max-width: 880px; margin: 0 auto;">
                    <div class="col-6 col-sm-4 col-md-2">
                        <div class="card border-0 bg-light p-3 rounded-3 text-center h-100">
                            <div class="text-muted extra-small mb-1">Total Processed</div>
                            <div class="h3 fw-bold text-dark mb-0" id="res-total-processed">0</div>
                        </div>
                    </div>
                    <div class="col-6 col-sm-4 col-md-2">
                        <div class="card border-0 p-3 rounded-3 text-center h-100" style="background-color: #ecfdf5;">
                            <div class="extra-small fw-semibold mb-1" style="color: #065f46;">Successfully Imported</div>
                            <div class="h3 fw-bold mb-0" style="color: #047857;" id="res-imported-count">0</div>
                        </div>
                    </div>
                    <div class="col-6 col-sm-4 col-md-2">
                        <div class="card border-0 p-3 rounded-3 text-center h-100" style="background-color: #f5f3ff;">
                            <div class="extra-small fw-semibold mb-1" style="color: #5b21b6;">Duplicates Skipped</div>
                            <div class="h3 fw-bold mb-0" style="color: #7c3aed;" id="res-duplicates-skipped">0</div>
                        </div>
                    </div>
                    <div class="col-6 col-sm-4 col-md-2">
                        <div class="card border-0 p-3 rounded-3 text-center h-100" style="background-color: #fef2f2;">
                            <div class="extra-small fw-semibold mb-1" style="color: #991b1b;">Invalid Skipped</div>
                            <div class="h3 fw-bold mb-0" style="color: #dc2626;" id="res-invalid-skipped">0</div>
                        </div>
                    </div>
                    <div class="col-6 col-sm-4 col-md-2">
                        <div class="card border-0 p-3 rounded-3 text-center h-100" style="background-color: #f0fdfa;">
                            <div class="extra-small fw-semibold mb-1" style="color: #115e59;">Trainings Created</div>
                            <div class="h3 fw-bold mb-0" style="color: #0f766e;" id="res-trainings-created">0</div>
                        </div>
                    </div>
                    <div class="col-6 col-sm-4 col-md-2">
                        <div class="card border-0 p-3 rounded-3 text-center h-100" style="background-color: #eff6ff;">
                            <div class="extra-small fw-semibold mb-1" style="color: #1e40af;">Trainings Reused</div>
                            <div class="h3 fw-bold mb-0" style="color: #2563eb;" id="res-trainings-reused">0</div>
                        </div>
                    </div>
                </div>

                <div class="d-flex align-items-center justify-content-center gap-3 pt-2">
                    <button type="button" class="btn btn-outline-secondary rounded-pill px-4" id="btn-import-another">
                        <i class="ti ti-upload me-1"></i> Import Another Spreadsheet
                    </button>
                    <a href="{{ route('manage.record.offline') }}" class="btn btn-primary rounded-pill px-4" style="background-color: #d97706; border-color: #d97706;">
                        <i class="ti ti-table me-1"></i> Back to Offline Records
                    </a>
                </div>
            </div>
        </div>

    </div>
</div>

<!-- ======================================================================= -->
<!-- ROW DETAIL INSPECTION MODAL                                             -->
<!-- ======================================================================= -->
<div class="modal fade" id="previewRowModal" tabindex="-1" aria-labelledby="previewRowModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-xl modal-dialog-scrollable">
        <div class="modal-content rounded-4 border-0 shadow">
            <div class="modal-header border-bottom py-3 px-4 bg-white rounded-top-4">
                <div class="d-flex align-items-center gap-2.5">
                    <span class="avatar rounded-3" style="background-color: #fef3c7; color: #d97706; width: 42px; height: 42px;">
                        <i class="ti ti-file-search fs-2"></i>
                    </span>
                    <div>
                        <h5 class="modal-title fw-bold text-dark mb-0" id="previewRowModalLabel">Participant Record Details</h5>
                        <div class="text-muted extra-small">Excel Row #<span id="modal-detail-row-num">-</span> &bull; 44 Non-PTKP OWL Canonical Columns</div>
                    </div>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>

            <div class="modal-body p-4 bg-light">
                <!-- Status & Message Banner -->
                <div id="modal-detail-status-banner" class="mb-3"></div>

                <div class="row g-3">
                    <!-- 1. Participant / Employee Info -->
                    <div class="col-md-6">
                        <div class="card border-0 bg-white p-3 rounded-3 shadow-none h-100">
                            <div class="fw-bold small text-dark mb-2 text-uppercase d-flex align-items-center gap-1.5" style="letter-spacing: 0.04em;">
                                <i class="ti ti-user text-primary"></i> 1. Participant Information
                            </div>
                            <div class="row g-2 small">
                                <div class="col-6">
                                    <span class="text-muted d-block extra-small">Employee ID (NIP):</span>
                                    <span class="fw-semibold text-dark" id="modal-detail-nip">-</span>
                                </div>
                                <div class="col-6">
                                    <span class="text-muted d-block extra-small">Name:</span>
                                    <span class="fw-semibold text-dark" id="modal-detail-name">-</span>
                                </div>
                                <div class="col-6">
                                    <span class="text-muted d-block extra-small">Grade:</span>
                                    <span class="fw-semibold text-dark" id="modal-detail-grade">-</span>
                                </div>
                                <div class="col-6">
                                    <span class="text-muted d-block extra-small">Job Position:</span>
                                    <span class="fw-semibold text-dark" id="modal-detail-position">-</span>
                                </div>
                                <div class="col-6">
                                    <span class="text-muted d-block extra-small">Team:</span>
                                    <span class="fw-semibold text-dark" id="modal-detail-team">-</span>
                                </div>
                                <div class="col-6">
                                    <span class="text-muted d-block extra-small">Department:</span>
                                    <span class="fw-semibold text-dark" id="modal-detail-dept">-</span>
                                </div>
                                <div class="col-6">
                                    <span class="text-muted d-block extra-small">Company:</span>
                                    <span class="fw-semibold text-dark" id="modal-detail-company">-</span>
                                </div>
                                <div class="col-6">
                                    <span class="text-muted d-block extra-small">LMS User Match:</span>
                                    <span id="modal-detail-user-match">-</span>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- 2. Training Information -->
                    <div class="col-md-6">
                        <div class="card border-0 bg-white p-3 rounded-3 shadow-none h-100">
                            <div class="fw-bold small text-dark mb-2 text-uppercase d-flex align-items-center gap-1.5" style="letter-spacing: 0.04em;">
                                <i class="ti ti-book text-primary"></i> 2. Training Information
                            </div>
                            <div class="row g-2 small">
                                <div class="col-12">
                                    <span class="text-muted d-block extra-small">Course Name:</span>
                                    <span class="fw-semibold text-dark" id="modal-detail-course">-</span>
                                </div>
                                <div class="col-6">
                                    <span class="text-muted d-block extra-small">Sub Course Name:</span>
                                    <span class="fw-semibold text-dark" id="modal-detail-subcourse">-</span>
                                </div>
                                <div class="col-6">
                                    <span class="text-muted d-block extra-small">Batch:</span>
                                    <span class="fw-semibold text-dark" id="modal-detail-batch">-</span>
                                </div>
                                <div class="col-6">
                                    <span class="text-muted d-block extra-small">Type:</span>
                                    <span class="fw-semibold text-dark" id="modal-detail-type">-</span>
                                </div>
                                <div class="col-6">
                                    <span class="text-muted d-block extra-small">Category:</span>
                                    <span class="fw-semibold text-dark" id="modal-detail-category">-</span>
                                </div>
                                <div class="col-6">
                                    <span class="text-muted d-block extra-small">Level:</span>
                                    <span class="fw-semibold text-dark" id="modal-detail-level">-</span>
                                </div>
                                <div class="col-6">
                                    <span class="text-muted d-block extra-small">Venue:</span>
                                    <span class="fw-semibold text-dark" id="modal-detail-venue">-</span>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- 3. Schedule & Classification -->
                    <div class="col-md-6">
                        <div class="card border-0 bg-white p-3 rounded-3 shadow-none h-100">
                            <div class="fw-bold small text-dark mb-2 text-uppercase d-flex align-items-center gap-1.5" style="letter-spacing: 0.04em;">
                                <i class="ti ti-calendar-event text-primary"></i> 3. Schedule & Classification
                            </div>
                            <div class="row g-2 small">
                                <div class="col-6">
                                    <span class="text-muted d-block extra-small">Start Date:</span>
                                    <span class="fw-semibold text-dark" id="modal-detail-start-date">-</span>
                                </div>
                                <div class="col-6">
                                    <span class="text-muted d-block extra-small">Finish Date:</span>
                                    <span class="fw-semibold text-dark" id="modal-detail-finish-date">-</span>
                                </div>
                                <div class="col-6">
                                    <span class="text-muted d-block extra-small">Learning Hours:</span>
                                    <span class="fw-semibold text-dark" id="modal-detail-hours">-</span>
                                </div>
                                <div class="col-6">
                                    <span class="text-muted d-block extra-small">Duration:</span>
                                    <span class="fw-semibold text-dark" id="modal-detail-duration">-</span>
                                </div>
                                <div class="col-6">
                                    <span class="text-muted d-block extra-small">Main Classification:</span>
                                    <span class="fw-semibold text-dark" id="modal-detail-main-class">-</span>
                                </div>
                                <div class="col-6">
                                    <span class="text-muted d-block extra-small">Sub Classification:</span>
                                    <span class="fw-semibold text-dark" id="modal-detail-sub-class">-</span>
                                </div>
                                <div class="col-6">
                                    <span class="text-muted d-block extra-small">Corporate KPI:</span>
                                    <span class="fw-semibold text-dark" id="modal-detail-kpi">-</span>
                                </div>
                                <div class="col-6">
                                    <span class="text-muted d-block extra-small">Provider:</span>
                                    <span class="fw-semibold text-dark" id="modal-detail-provider">-</span>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- 4. Obligation & Kirkpatrick Evaluations -->
                    <div class="col-md-6">
                        <div class="card border-0 bg-white p-3 rounded-3 shadow-none h-100">
                            <div class="fw-bold small text-dark mb-2 text-uppercase d-flex align-items-center gap-1.5" style="letter-spacing: 0.04em;">
                                <i class="ti ti-award text-primary"></i> 4. Evaluation & Obligation
                            </div>
                            <div class="row g-2 small">
                                <div class="col-6">
                                    <span class="text-muted d-block extra-small">Progress & Score:</span>
                                    <span class="fw-semibold text-dark" id="modal-detail-progress">-</span>
                                </div>
                                <div class="col-6">
                                    <span class="text-muted d-block extra-small">Attendance:</span>
                                    <span class="fw-semibold text-dark" id="modal-detail-attendance">-</span>
                                </div>
                                <div class="col-6">
                                    <span class="text-muted d-block extra-small">Bond Status:</span>
                                    <span class="fw-semibold text-dark" id="modal-detail-bond">-</span>
                                </div>
                                <div class="col-6">
                                    <span class="text-muted d-block extra-small">Bond Amount:</span>
                                    <span class="fw-semibold text-dark" id="modal-detail-bond-amt">-</span>
                                </div>
                                <div class="col-6">
                                    <span class="text-muted d-block extra-small">L1 Program:</span>
                                    <span class="fw-semibold text-dark" id="modal-detail-l1">-</span>
                                </div>
                                <div class="col-6">
                                    <span class="text-muted d-block extra-small">L2 Evaluation:</span>
                                    <span class="fw-semibold text-dark" id="modal-detail-l2">-</span>
                                </div>
                                <div class="col-6">
                                    <span class="text-muted d-block extra-small">L3 Evaluation:</span>
                                    <span class="fw-semibold text-dark" id="modal-detail-l3">-</span>
                                </div>
                                <div class="col-6">
                                    <span class="text-muted d-block extra-small">PIC:</span>
                                    <span class="fw-semibold text-dark" id="modal-detail-pic">-</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="modal-footer border-top py-2.5 px-4 bg-white rounded-bottom-4">
                <button type="button" class="btn btn-secondary rounded-pill px-4 ms-auto" data-bs-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>

@endsection

@section('style')
<style>
    .manage-record-import-non-ptkp {
        background: #f8fafc;
        min-height: 100vh;
    }

    [data-bs-theme="dark"] .manage-record-import-non-ptkp,
    body.dark-mode .manage-record-import-non-ptkp,
    body.theme-dark .manage-record-import-non-ptkp {
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
        border-left: 4px solid #d97706 !important;
    }

    [data-bs-theme="dark"] .record-section-header,
    body.dark-mode .record-section-header,
    body.theme-dark .record-section-header {
        background-color: #1e293b !important;
        border-color: rgba(255, 255, 255, 0.1) !important;
        border-left-color: #d97706 !important;
    }

    [data-bs-theme="dark"] .record-section-header .text-dark,
    body.dark-mode .record-section-header .text-dark,
    body.theme-dark .record-section-header .text-dark {
        color: #f8fafc !important;
    }

    /* Stepper Styling */
    .stepper-card {
        border-radius: 14px !important;
        border: 1px solid #e2e8f0 !important;
    }
    [data-bs-theme="dark"] .stepper-card,
    body.dark-mode .stepper-card,
    body.theme-dark .stepper-card {
        background-color: #1e293b !important;
        border-color: rgba(255, 255, 255, 0.1) !important;
    }

    .stepper-item {
        display: flex;
        align-items: center;
        gap: 12px;
        padding: 10px 14px;
        border-radius: 10px;
        border: 1px solid #e2e8f0;
        background: #f8fafc;
        transition: all 0.2s ease;
    }
    .stepper-badge {
        width: 32px;
        height: 32px;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        font-weight: 700;
        font-size: 0.85rem;
        background: #e2e8f0;
        color: #64748b;
        flex-shrink: 0;
        position: relative;
    }
    .stepper-check {
        display: none;
    }
    .stepper-title {
        font-size: 0.85rem;
        font-weight: 600;
        color: #64748b;
        line-height: 1.2;
    }
    .stepper-desc {
        font-size: 0.72rem;
        line-height: 1.2;
    }

    /* Stepper Active */
    .stepper-item.active {
        background: #fffbeb;
        border-color: #fcd34d;
    }
    .stepper-item.active .stepper-badge {
        background: #d97706;
        color: #ffffff;
        box-shadow: 0 0 0 3px rgba(217, 119, 6, 0.2);
    }
    .stepper-item.active .stepper-title {
        color: #92400e;
        font-weight: 700;
    }

    /* Stepper Completed */
    .stepper-item.completed {
        background: #ecfdf5;
        border-color: #a7f3d0;
    }
    .stepper-item.completed .stepper-badge {
        background: #10b981;
        color: #ffffff;
    }
    .stepper-item.completed .stepper-num {
        display: none;
    }
    .stepper-item.completed .stepper-check {
        display: inline-block;
        font-size: 1rem;
    }
    .stepper-item.completed .stepper-title {
        color: #065f46;
    }

    [data-bs-theme="dark"] .stepper-item,
    body.dark-mode .stepper-item,
    body.theme-dark .stepper-item {
        background: #111827;
        border-color: rgba(255, 255, 255, 0.1);
    }
    [data-bs-theme="dark"] .stepper-item.active,
    body.dark-mode .stepper-item.active,
    body.theme-dark .stepper-item.active {
        background: rgba(217, 119, 6, 0.15);
        border-color: #d97706;
    }
    [data-bs-theme="dark"] .stepper-item.active .stepper-title,
    body.dark-mode .stepper-item.active .stepper-title,
    body.theme-dark .stepper-item.active .stepper-title {
        color: #fbbf24;
    }
    [data-bs-theme="dark"] .stepper-item.completed,
    body.dark-mode .stepper-item.completed,
    body.theme-dark .stepper-item.completed {
        background: rgba(16, 185, 129, 0.15);
        border-color: #10b981;
    }
    [data-bs-theme="dark"] .stepper-item.completed .stepper-title,
    body.dark-mode .stepper-item.completed .stepper-title,
    body.theme-dark .stepper-item.completed .stepper-title {
        color: #34d399;
    }

    /* Dropzone Drag & Drop */
    .upload-dropzone {
        border-color: #cbd5e1 !important;
        transition: all 0.2s ease;
        background-color: #f8fafc !important;
    }
    .upload-dropzone.dragover {
        border-color: #d97706 !important;
        background-color: #fffbeb !important;
    }
    [data-bs-theme="dark"] .upload-dropzone,
    body.dark-mode .upload-dropzone,
    body.theme-dark .upload-dropzone {
        background-color: #111827 !important;
        border-color: rgba(255, 255, 255, 0.2) !important;
    }
    [data-bs-theme="dark"] .upload-dropzone.dragover,
    body.dark-mode .upload-dropzone.dragover,
    body.theme-dark .upload-dropzone.dragover {
        background-color: rgba(217, 119, 6, 0.15) !important;
        border-color: #d97706 !important;
    }

    /* KPI Stat Boxes */
    .stat-box {
        transition: transform 0.15s ease, box-shadow 0.15s ease;
    }
    .stat-box:hover {
        transform: translateY(-2px);
    }
    [data-bs-theme="dark"] .stat-box.bg-light,
    body.dark-mode .stat-box.bg-light,
    body.theme-dark .stat-box.bg-light {
        background-color: #1e293b !important;
        color: #f8fafc !important;
    }
    [data-bs-theme="dark"] .stat-box .text-dark,
    body.dark-mode .stat-box .text-dark,
    body.theme-dark .stat-box .text-dark {
        color: #f8fafc !important;
    }

    /* Table preview styling */
    .preview-table-container {
        max-height: 520px;
        overflow-y: auto;
    }
    .status-dot {
        width: 8px;
        height: 8px;
        border-radius: 50%;
        display: inline-block;
    }

    /* Status Badges */
    .badge-status-valid {
        background-color: #d1fae5;
        color: #065f46;
        border: 1px solid #a7f3d0;
    }
    .badge-status-warning {
        background-color: #fef3c7;
        color: #92400e;
        border: 1px solid #fde68a;
    }
    .badge-status-duplicate {
        background-color: #ede9fe;
        color: #5b21b6;
        border: 1px solid #ddd6fe;
    }
    .badge-status-invalid {
        background-color: #fee2e2;
        color: #991b1b;
        border: 1px solid #fecaca;
    }
    .badge-status-invalid-company {
        background-color: #ffedd5;
        color: #9a3412;
        border: 1px solid #fed7aa;
    }

    [data-bs-theme="dark"] .badge-status-valid,
    body.dark-mode .badge-status-valid,
    body.theme-dark .badge-status-valid {
        background-color: rgba(16, 185, 129, 0.2);
        color: #6ee7b7;
        border-color: rgba(16, 185, 129, 0.4);
    }
    [data-bs-theme="dark"] .badge-status-warning,
    body.dark-mode .badge-status-warning,
    body.theme-dark .badge-status-warning {
        background-color: rgba(245, 158, 11, 0.2);
        color: #fcd34d;
        border-color: rgba(245, 158, 11, 0.4);
    }
    [data-bs-theme="dark"] .badge-status-duplicate,
    body.dark-mode .badge-status-duplicate,
    body.theme-dark .badge-status-duplicate {
        background-color: rgba(139, 92, 246, 0.2);
        color: #c4b5fd;
        border-color: rgba(139, 92, 246, 0.4);
    }
    [data-bs-theme="dark"] .badge-status-invalid,
    body.dark-mode .badge-status-invalid,
    body.theme-dark .badge-status-invalid {
        background-color: rgba(239, 68, 68, 0.2);
        color: #fca5a5;
        border-color: rgba(239, 68, 68, 0.4);
    }
    [data-bs-theme="dark"] .badge-status-invalid-company,
    body.dark-mode .badge-status-invalid-company,
    body.theme-dark .badge-status-invalid-company {
        background-color: rgba(249, 115, 22, 0.2);
        color: #fdba74;
        border-color: rgba(249, 115, 22, 0.4);
    }

    /* Dark mode card backgrounds */
    [data-bs-theme="dark"] .card.bg-white,
    body.dark-mode .card.bg-white,
    body.theme-dark .card.bg-white,
    [data-bs-theme="dark"] .card-header.bg-white,
    body.dark-mode .card-header.bg-white,
    body.theme-dark .card-header.bg-white {
        background-color: var(--tblr-bg-surface, #1e293b) !important;
        color: #f8fafc !important;
    }
    [data-bs-theme="dark"] .text-dark,
    body.dark-mode .text-dark,
    body.theme-dark .text-dark {
        color: #f8fafc !important;
    }
    [data-bs-theme="dark"] .table-light,
    body.dark-mode .table-light,
    body.theme-dark .table-light {
        background-color: #1e293b !important;
        color: #94a3b8 !important;
    }
    [data-bs-theme="dark"] .comparison-summary-card,
    body.dark-mode .comparison-summary-card,
    body.theme-dark .comparison-summary-card {
        background-color: #111827 !important;
        border-color: rgba(255, 255, 255, 0.1) !important;
    }
    [data-bs-theme="dark"] .comparison-summary-card .bg-white,
    body.dark-mode .comparison-summary-card .bg-white,
    body.theme-dark .comparison-summary-card .bg-white {
        background-color: #1e293b !important;
        border-color: rgba(255, 255, 255, 0.1) !important;
    }
</style>
@endsection

@section('script')
<script>
$(document).ready(function () {
    // Session state variables
    let importToken = null;
    let importCompleted = false;
    let previewRowsData = [];
    let currentFilter = 'all';
    let searchQuery = '';
    let currentPage = 1;
    const pageSize = 50;

    // DOM Elements
    const $fileInput = $('#excel-file-input');
    const $dropzone = $('#dropzone-container');
    const $selectedFileBox = $('#selected-file-box');
    const $selectedFilenameText = $('#selected-filename-text');
    const $btnClearFile = $('#btn-clear-file');
    const $uploadErrorBox = $('#upload-error-box');
    const $uploadErrorMsg = $('#upload-error-message');
    const $btnValidate = $('#btn-validate-upload');

    // State panels
    const $panelUpload = $('#panel-state-upload');
    const $panelLoading = $('#panel-state-loading');
    const $panelPreview = $('#panel-state-preview');
    const $panelImporting = $('#panel-state-importing');
    const $panelResult = $('#panel-state-result');

    // Stepper node elements
    const $step1 = $('#stepper-step-1');
    const $step2 = $('#stepper-step-2');
    const $step3 = $('#stepper-step-3');
    const $step4 = $('#stepper-step-4');

    // CSRF Setup
    const csrfToken = $('meta[name="csrf-token"]').attr('content') || '{{ csrf_token() }}';

    // =========================================================================
    // STEPPER STATE MANAGEMENT
    // =========================================================================
    function setStepState(activeStep) {
        // activeStep: 1 (upload), 2 (validating), 3 (preview/review), 4 (importing/result)
        const steps = [$step1, $step2, $step3, $step4];

        steps.forEach(function ($step, idx) {
            const stepNum = idx + 1;
            $step.removeClass('active completed pending');

            if (stepNum < activeStep) {
                $step.addClass('completed');
            } else if (stepNum === activeStep) {
                $step.addClass('active');
            } else {
                $step.addClass('pending');
            }
        });
    }

    function switchPanel(panelName) {
        $panelUpload.hide();
        $panelLoading.hide();
        $panelPreview.hide();
        $panelImporting.hide();
        $panelResult.hide();

        if (panelName === 'upload') {
            setStepState(1);
            $panelUpload.show();
        } else if (panelName === 'loading') {
            setStepState(2);
            $panelLoading.show();
        } else if (panelName === 'preview') {
            setStepState(3);
            $panelPreview.show();
        } else if (panelName === 'importing') {
            setStepState(4);
            $panelImporting.show();
        } else if (panelName === 'result') {
            // All steps completed
            $step1.removeClass('active pending').addClass('completed');
            $step2.removeClass('active pending').addClass('completed');
            $step3.removeClass('active pending').addClass('completed');
            $step4.removeClass('active pending').addClass('completed');
            $panelResult.show();
        }
    }

    // =========================================================================
    // FILE SELECTION & DRAG-AND-DROP
    // =========================================================================
    $dropzone.on('dragover dragenter', function (e) {
        e.preventDefault();
        e.stopPropagation();
        $(this).addClass('dragover');
    });

    $dropzone.on('dragleave drop', function (e) {
        e.preventDefault();
        e.stopPropagation();
        $(this).removeClass('dragover');
    });

    $dropzone.on('drop', function (e) {
        const dt = e.originalEvent.dataTransfer;
        if (dt && dt.files && dt.files.length > 0) {
            $fileInput[0].files = dt.files;
            handleFileSelected($fileInput[0].files[0]);
        }
    });

    $fileInput.on('change', function () {
        const file = this.files && this.files[0];
        handleFileSelected(file);
    });

    $btnClearFile.on('click', function (e) {
        e.stopPropagation();
        resetFileInput();
    });

    function resetFileInput() {
        $fileInput.val('');
        $selectedFileBox.addClass('d-none').removeClass('d-inline-flex');
        $selectedFilenameText.text('No file selected');
        $btnValidate.prop('disabled', true);
        $uploadErrorBox.hide();
    }

    function handleFileSelected(file) {
        $uploadErrorBox.hide();
        if (!file) {
            resetFileInput();
            return;
        }

        const ext = file.name.split('.').pop().toLowerCase();
        if (ext !== 'xlsx' && ext !== 'xls') {
            $uploadErrorMsg.text('Unsupported file format. Please select an Excel spreadsheet (.xlsx or .xls).');
            $uploadErrorBox.show();
            resetFileInput();
            return;
        }

        const maxBytes = 20 * 1024 * 1024; // 20 MB
        if (file.size > maxBytes) {
            $uploadErrorMsg.text('File size exceeds the 20 MB limit.');
            $uploadErrorBox.show();
            resetFileInput();
            return;
        }

        const sizeKb = (file.size / 1024).toFixed(1);
        $selectedFilenameText.text(`${file.name} (${sizeKb} KB)`);
        $selectedFileBox.removeClass('d-none').addClass('d-inline-flex');
        $btnValidate.prop('disabled', false);
    }

    // =========================================================================
    // VALIDATE & PREVIEW (AJAX)
    // =========================================================================
    $btnValidate.on('click', function () {
        const file = $fileInput[0].files && $fileInput[0].files[0];
        if (!file) return;

        $btnValidate.prop('disabled', true);
        $('#loading-state-title').text('Validating Spreadsheet...');
        $('#loading-state-subtitle').text('Reading columns, resolving header groups, and checking existing records...');
        switchPanel('loading');

        const formData = new FormData();
        formData.append('file', file);
        formData.append('_token', csrfToken);

        $.ajax({
            url: "{{ route('manage.record.offline.import.preview') }}",
            type: 'POST',
            data: formData,
            processData: false,
            contentType: false,
            headers: {
                'X-CSRF-TOKEN': csrfToken
            },
            success: function (res) {
                if (res && res.success) {
                    const payload = res.data ? res.data : res;
                    importToken = res.token || payload.token;
                    previewRowsData = res.rows || payload.preview_rows || [];
                    renderPreviewState(res);
                } else {
                    switchPanel('upload');
                    $uploadErrorMsg.text(res.message || 'Spreadsheet validation failed.');
                    $uploadErrorBox.show();
                    $btnValidate.prop('disabled', false);
                }
            },
            error: function (xhr) {
                switchPanel('upload');
                let errMsg = 'Failed to validate spreadsheet. Please ensure the file uses the Non-PTKP OWL template format.';
                if (xhr.responseJSON && xhr.responseJSON.message) {
                    errMsg = xhr.responseJSON.message;
                }
                $uploadErrorMsg.text(errMsg);
                $uploadErrorBox.show();
                $btnValidate.prop('disabled', false);
            }
        });
    });

    // =========================================================================
    // RENDER PREVIEW STATE & STATS
    // =========================================================================
    function renderPreviewState(response) {
        const payload = (response && response.data) ? response.data : response;
        const stats = payload.stats || {};
        const rows = payload.preview_rows || payload.rows || [];
        const headerCheck = payload.header_check || null;

        if (payload.filename) {
            $('#preview-filename-badge').text(payload.filename);
        }

        // 1. KPI Stats Numbers
        const totalRows = Number(stats.total_rows || 0);
        const validRows = Number(stats.valid || 0);
        const warningRows = Number(stats.warning || 0);
        const duplicateRows = Number(stats.duplicate || 0);
        const invalidRows = Number(stats.invalid || 0);
        const eventsCount = Number(stats.unique_events || stats.events || 0);

        $('#stat-total-rows').text(totalRows.toLocaleString());
        $('#stat-valid-rows').text(validRows.toLocaleString());
        $('#stat-warning-rows').text(warningRows.toLocaleString());
        $('#stat-duplicate-rows').text(duplicateRows.toLocaleString());
        $('#stat-invalid-rows').text(invalidRows.toLocaleString());
        $('#stat-training-events').text(eventsCount.toLocaleString());

        // Comparison summary cards numbers
        $('#comp-new-records').text(validRows.toLocaleString());
        $('#comp-duplicate-records').text(duplicateRows.toLocaleString());
        $('#comp-warning-records').text(warningRows.toLocaleString());
        $('#comp-invalid-records').text(invalidRows.toLocaleString());

        // 2. Filter tabs counts
        $('#filter-count-all').text(previewRowsData.length.toLocaleString());
        $('#filter-count-valid').text(validRows.toLocaleString());
        $('#filter-count-warning').text(warningRows.toLocaleString());
        $('#filter-count-duplicate').text(duplicateRows.toLocaleString());
        $('#filter-count-invalid').text(invalidRows.toLocaleString());

        // 3. Template Header Diagnosis Card
        renderHeaderCheckCard(headerCheck);

        // 4. Determine Preview State & Import Readiness Banner
        const isHeaderValid = !!(headerCheck && headerCheck.can_proceed);
        let previewState = payload.preview_state || (response && response.preview_state);
        if (!previewState) {
            if (!isHeaderValid) {
                previewState = 'HEADER_ERROR';
            } else if (invalidRows > 0) {
                previewState = 'DATA_ERROR';
            } else if (totalRows === 0) {
                previewState = 'EMPTY_FILE';
            } else if (validRows === 0 && duplicateRows > 0) {
                previewState = 'NO_NEW_RECORDS';
            } else if (duplicateRows > 0 && validRows > 0) {
                previewState = 'READY_WITH_DUPLICATES';
            } else {
                previewState = 'READY';
            }
        }

        renderStateBanner(previewState, totalRows, validRows, warningRows, duplicateRows, invalidRows);

        // Reset filter & render table
        currentFilter = 'all';
        searchQuery = '';
        currentPage = 1;
        $('#preview-search-input').val('');
        $('.filter-tab-btn').removeClass('active');
        $('[data-filter="all"]').addClass('active');

        renderTable();
        switchPanel('preview');
    }

    // =========================================================================
    // HEADER STRUCTURE DIAGNOSTIC CARD
    // =========================================================================
    function renderHeaderCheckCard(headerCheck) {
        const $card = $('#preview-header-check-card');
        if (!headerCheck) {
            $card.hide();
            return;
        }

        $card.show();
        const hStats = headerCheck.stats || {};
        const formatLabel = headerCheck.format === 'legacy_two_row' ? 'Legacy 2-Row Format' : 'Single Header Row Format';
        $('#header-check-subtitle').html(`44 Expected Columns &bull; ${headerCheck.detected_columns} Detected Columns &bull; <span class="badge bg-secondary-lt">${formatLabel}</span>`);

        $('#badge-header-valid').text(`✓ ${hStats.valid || 0} Valid`).show();

        if ((hStats.auto_matched || 0) > 0) {
            $('#badge-header-matched').text(`⚠ ${hStats.auto_matched} Auto-Matched`).show();
        } else {
            $('#badge-header-matched').hide();
        }

        const totalIssues = (hStats.invalid || 0) + (hStats.missing || 0) + (hStats.duplicate || 0) + (hStats.ambiguous || 0);
        if (totalIssues > 0) {
            $('#badge-header-invalid').text(`✕ ${totalIssues} Needs Correction`).show();
        } else {
            $('#badge-header-invalid').hide();
        }

        const $details = $('#header-check-details');
        const $issuesContainer = $('#header-check-issues-container');
        $issuesContainer.empty();

        let hasDetails = false;

        // Issues list
        if (headerCheck.issues && headerCheck.issues.length > 0) {
            hasDetails = true;
            headerCheck.issues.forEach(function (issue) {
                const colLabel = issue.letter ? `Col ${issue.letter} (#${issue.col})` : `Col #${issue.col || '?'}`;
                $issuesContainer.append(`
                    <div class="alert alert-danger py-1.5 px-2 mb-1 rounded-2 d-flex align-items-start gap-2">
                        <i class="ti ti-alert-triangle fs-3 flex-shrink-0 text-danger mt-0.5"></i>
                        <div>
                            <span class="badge bg-danger text-white me-1">${escapeHtml(issue.status)}</span>
                            <strong>${escapeHtml(colLabel)}:</strong> ${escapeHtml(issue.message)}
                            ${issue.expected ? `<div class="extra-small text-muted mt-0.5">Expected header: <code>${escapeHtml(issue.expected)}</code></div>` : ''}
                        </div>
                    </div>
                `);
            });
        }

        // Auto-matched columns list
        if (headerCheck.auto_matched_list && headerCheck.auto_matched_list.length > 0) {
            hasDetails = true;
            const matchedItems = headerCheck.auto_matched_list.map(function (m) {
                return `<span class="badge bg-warning-subtle text-dark border me-1 mb-1">Col ${m.letter}: "<em>${escapeHtml(m.found)}</em>" &rarr; <strong>${escapeHtml(m.mapped_to)}</strong></span>`;
            }).join(' ');

            $issuesContainer.append(`
                <div class="alert alert-warning py-1.5 px-2 mb-1 rounded-2">
                    <div class="fw-semibold text-warning-emphasis mb-1"><i class="ti ti-sparkles me-1"></i> Auto-Matched Columns (${headerCheck.auto_matched_list.length}):</div>
                    <div>${matchedItems}</div>
                </div>
            `);
        }

        if (hasDetails) {
            $details.show();
        } else {
            $details.hide();
        }
    }

    // =========================================================================
    // STATE BANNER & CONFIRM BUTTON
    // =========================================================================
    function renderStateBanner(previewState, totalRows, validRows, warningRows, duplicateRows, invalidRows) {
        const $banner = $('#preview-state-banner');
        const $btnConfirm = $('#btn-action-confirm-import');
        const $footerSummary = $('#preview-footer-summary-text');
        const $confirmLabel = $('#btn-confirm-label');

        $btnConfirm.prop('disabled', true).removeClass('btn-danger btn-secondary btn-primary disabled');

        if (previewState === 'HEADER_ERROR') {
            $banner.show().html(`
                <div class="alert alert-danger border d-flex align-items-start gap-2.5 py-3 px-3 mb-0 rounded-3">
                    <i class="ti ti-alert-octagon fs-2 text-danger flex-shrink-0 mt-0.5"></i>
                    <div class="w-100">
                        <strong class="d-block text-danger fw-bold mb-0.5 fs-4">Header Correction Required</strong>
                        <div class="text-secondary small">Spreadsheet column structure does not match the official Non-PTKP OWL template format. Please review the highlighted issues above and re-upload the corrected file.</div>
                    </div>
                </div>
            `);
            $btnConfirm.addClass('btn-danger disabled').html('<i class="ti ti-ban me-1"></i> Header Correction Required');
            $footerSummary.html('<span class="text-danger fw-medium"><i class="ti ti-alert-circle me-1"></i>Import blocked: Header column structure must be corrected first.</span>');

        } else if (previewState === 'DATA_ERROR') {
            $banner.show().html(`
                <div class="alert alert-danger border d-flex align-items-start gap-2.5 py-3 px-3 mb-0 rounded-3">
                    <i class="ti ti-alert-triangle fs-2 text-danger flex-shrink-0 mt-0.5"></i>
                    <div class="w-100">
                        <strong class="d-block text-danger fw-bold mb-0.5 fs-4">Data Correction Required</strong>
                        <div class="small">A total of <strong>${totalRows.toLocaleString()}</strong> records were read.</div>
                        <div class="my-1.5 text-danger fw-semibold small">A total of <strong>${invalidRows.toLocaleString()}</strong> records contain invalid data and must be corrected before the import can proceed.</div>
                        <div class="d-flex flex-wrap align-items-center gap-1.5 my-2">
                            <span class="badge bg-secondary-lt"><i class="ti ti-file-spreadsheet me-1"></i>${totalRows.toLocaleString()} read</span>
                            <span class="badge bg-danger-lt fw-bold"><i class="ti ti-alert-triangle me-1"></i>${invalidRows.toLocaleString()} invalid</span>
                            <span class="badge bg-success-lt"><i class="ti ti-check me-1"></i>${validRows.toLocaleString()} ready</span>
                            ${duplicateRows > 0 ? `<span class="badge bg-purple-lt"><i class="ti ti-copy-off me-1"></i>${duplicateRows.toLocaleString()} duplicates skipped</span>` : ''}
                        </div>
                        <div class="extra-small text-muted pt-1 border-top border-danger-subtle">
                            Correct the highlighted red rows in your Excel file and upload again.
                        </div>
                    </div>
                </div>
            `);
            $btnConfirm.addClass('btn-danger disabled').html('<i class="ti ti-alert-triangle me-1"></i> Correct Invalid Records');
            $footerSummary.html('<span class="text-danger fw-medium"><i class="ti ti-alert-circle me-1"></i>Import blocked: ' + invalidRows.toLocaleString() + ' invalid records must be corrected.</span>');

        } else if (previewState === 'EMPTY_FILE') {
            $banner.show().html(`
                <div class="alert alert-warning border d-flex align-items-start gap-2.5 py-3 px-3 mb-0 rounded-3">
                    <i class="ti ti-alert-circle fs-2 text-warning flex-shrink-0 mt-0.5"></i>
                    <div class="w-100">
                        <strong class="d-block text-warning fw-bold mb-0.5 fs-4">No Data Rows Found</strong>
                        <div class="text-secondary small">The spreadsheet file contains no participant rows to import. Please check your file.</div>
                    </div>
                </div>
            `);
            $btnConfirm.addClass('btn-secondary disabled').html('<i class="ti ti-ban me-1"></i> No Data Found');
            $footerSummary.text('The spreadsheet file contains no records to import.');

        } else if (previewState === 'NO_NEW_RECORDS') {
            // SCREENSHOT CASE: All rows are duplicates, valid = 0, invalid = 0
            $banner.show().html(`
                <div class="alert alert-secondary border d-flex align-items-start gap-2.5 py-3 px-3 mb-0 rounded-3 bg-light-subtle text-secondary-emphasis">
                    <i class="ti ti-info-circle fs-2 text-secondary flex-shrink-0 mt-0.5"></i>
                    <div class="w-100">
                        <div class="fw-bold text-dark mb-0.5 fs-4">No New Records to Import</div>
                        <div class="text-secondary small">All <strong>${totalRows.toLocaleString()}</strong> records were read successfully, but they already exist in the database. No new records will be imported.</div>
                        <div class="my-1.5 text-dark fw-medium small"><strong>0 records will be imported and ${duplicateRows.toLocaleString()} duplicate records will be skipped.</strong></div>
                        <div class="d-flex flex-wrap align-items-center gap-1.5 my-2">
                            <span class="badge bg-secondary-lt"><i class="ti ti-file-spreadsheet me-1"></i>${totalRows.toLocaleString()} read</span>
                            <span class="badge bg-secondary-lt text-muted"><i class="ti ti-ban me-1"></i>0 new records</span>
                            <span class="badge bg-purple-lt"><i class="ti ti-copy-off me-1"></i>${duplicateRows.toLocaleString()} duplicates skipped</span>
                        </div>
                        <div class="extra-small text-muted pt-1 border-top border-secondary-subtle">
                            <i class="ti ti-shield-check text-success me-1"></i>Existing database records will not be modified or overwritten.
                        </div>
                    </div>
                </div>
            `);
            $btnConfirm.addClass('btn-secondary disabled')
                       .css({ 'opacity': '0.7', 'cursor': 'not-allowed', 'background-color': '#6c757d', 'border-color': '#6c757d' })
                       .html('<i class="ti ti-copy-off me-1"></i> No New Records to Import');
            $footerSummary.html('<span class="text-muted">' + totalRows.toLocaleString() + ' records read &bull; 0 imported &bull; ' + duplicateRows.toLocaleString() + ' duplicates skipped</span>');

        } else if (previewState === 'READY_WITH_DUPLICATES') {
            const warningInfo = (warningRows > 0)
                ? `<div class="extra-small text-muted mt-1"><i class="ti ti-alert-circle text-warning me-1"></i><strong>${warningRows.toLocaleString()}</strong> records have nonfatal notes.</div>`
                : '';

            $banner.show().html(`
                <div class="alert alert-info border d-flex align-items-start gap-2.5 py-3 px-3 mb-0 rounded-3">
                    <i class="ti ti-info-circle fs-2 text-info flex-shrink-0 mt-0.5"></i>
                    <div class="w-100">
                        <div class="fw-bold text-dark mb-0.5 fs-4">Ready to Import</div>
                        <div class="small">A total of <strong>${totalRows.toLocaleString()}</strong> records were read.</div>
                        <div class="my-1.5 text-dark fw-medium small"><strong>${validRows.toLocaleString()} records will be imported and ${duplicateRows.toLocaleString()} duplicate records will be skipped automatically.</strong></div>
                        ${warningInfo}
                        <div class="d-flex flex-wrap align-items-center gap-1.5 my-2">
                            <span class="badge bg-secondary-lt"><i class="ti ti-file-spreadsheet me-1"></i>${totalRows.toLocaleString()} read</span>
                            <span class="badge bg-success-lt fw-bold"><i class="ti ti-check me-1"></i>${validRows.toLocaleString()} records to import</span>
                            <span class="badge bg-purple-lt"><i class="ti ti-copy-off me-1"></i>${duplicateRows.toLocaleString()} duplicates skipped</span>
                        </div>
                        <div class="extra-small text-muted pt-1 border-top border-info-subtle">
                            <i class="ti ti-shield-check text-success me-1"></i>Existing duplicate records will remain intact without changes.
                        </div>
                    </div>
                </div>
            `);
            $btnConfirm.prop('disabled', false)
                       .addClass('btn-primary')
                       .css({ 'background-color': '#d97706', 'border-color': '#d97706' })
                       .html('<i class="ti ti-check me-1"></i> Confirm & Import ' + validRows.toLocaleString() + ' Records');
            $footerSummary.html('<span class="text-dark fw-medium">' + totalRows.toLocaleString() + ' read &bull; <strong>' + validRows.toLocaleString() + ' ready to import</strong> &bull; ' + duplicateRows.toLocaleString() + ' duplicates skipped</span>');

        } else {
            // READY (100% valid)
            const warningInfo = (warningRows > 0)
                ? `<div class="extra-small text-muted mt-1"><i class="ti ti-alert-circle text-warning me-1"></i><strong>${warningRows.toLocaleString()}</strong> records have nonfatal notes.</div>`
                : '';

            $banner.show().html(`
                <div class="alert alert-success border d-flex align-items-start gap-2.5 py-3 px-3 mb-0 rounded-3">
                    <i class="ti ti-circle-check fs-2 text-success flex-shrink-0 mt-0.5"></i>
                    <div class="w-100">
                        <div class="fw-bold text-dark mb-0.5 fs-4">Ready to Import</div>
                        <div class="small">All <strong>${totalRows.toLocaleString()}</strong> records passed validation and are ready to be imported into OWL LMS.</div>
                        <div class="my-1.5 text-dark fw-medium small"><strong>${validRows.toLocaleString()} records will be imported.</strong></div>
                        ${warningInfo}
                        <div class="d-flex flex-wrap align-items-center gap-1.5 my-2">
                            <span class="badge bg-secondary-lt"><i class="ti ti-file-spreadsheet me-1"></i>${totalRows.toLocaleString()} read</span>
                            <span class="badge bg-success-lt fw-bold"><i class="ti ti-check me-1"></i>${validRows.toLocaleString()} records ready</span>
                            <span class="badge bg-secondary-lt text-muted"><i class="ti ti-copy-off me-1"></i>0 duplicates</span>
                        </div>
                        <div class="extra-small text-muted pt-1 border-top border-success-subtle">
                            <i class="ti ti-shield-check text-success me-1"></i>All records are valid and ready to be imported into OWL LMS.
                        </div>
                    </div>
                </div>
            `);
            $btnConfirm.prop('disabled', false)
                       .addClass('btn-primary')
                       .css({ 'background-color': '#d97706', 'border-color': '#d97706' })
                       .html('<i class="ti ti-check me-1"></i> Confirm & Import ' + validRows.toLocaleString() + ' Records');
            $footerSummary.html('<span class="text-dark fw-medium">All ' + validRows.toLocaleString() + ' records are valid and ready to import</span>');
        }
    }

    // =========================================================================
    // TABLE FILTER & RENDERING (Tahap 7 & 8)
    // =========================================================================
    function getFilteredRows() {
        return previewRowsData.filter(function (row) {
            // Status tab filter
            if (currentFilter !== 'all') {
                if (row.status !== currentFilter) return false;
            }

            // Search query filter
            if (searchQuery) {
                const q = searchQuery.toLowerCase();
                const d = row.data || {};
                const name = (d.name || '').toLowerCase();
                const nip = (d.employee_id || '').toLowerCase();
                const course = (d.course_name || '').toLowerCase();
                const batch = (d.batch || '').toLowerCase();
                const msgs = (row.messages || []).join(' ').toLowerCase();

                return name.includes(q) || nip.includes(q) || course.includes(q) || batch.includes(q) || msgs.includes(q);
            }

            return true;
        });
    }

    function renderTable() {
        const $tbody = $('#preview-main-tbody');
        $tbody.empty();

        const filtered = getFilteredRows();
        $('#preview-visible-count').text(filtered.length.toLocaleString());

        if (filtered.length === 0) {
            $tbody.append('<tr><td colspan="10" class="text-center py-5 text-muted"><i class="ti ti-folder-off fs-1 d-block mb-2 text-secondary"></i>No records match the filter criteria.</td></tr>');
            $('#preview-pagination-container').hide();
            return;
        }

        // Pagination calculations
        const totalPages = Math.ceil(filtered.length / pageSize) || 1;
        if (currentPage > totalPages) currentPage = totalPages;
        if (currentPage < 1) currentPage = 1;

        $('#page-current').text(currentPage);
        $('#page-total').text(totalPages);
        $('#btn-page-prev').prop('disabled', currentPage <= 1);
        $('#btn-page-next').prop('disabled', currentPage >= totalPages);

        if (totalPages > 1) {
            $('#preview-pagination-container').show();
        } else {
            $('#preview-pagination-container').hide();
        }

        const startIdx = (currentPage - 1) * pageSize;
        const pageRows = filtered.slice(startIdx, startIdx + pageSize);

        pageRows.forEach(function (row, localIdx) {
            const globalIdx = previewRowsData.indexOf(row);
            const d = row.data || {};

            // Status badge classes & labels
            let badgeClass = 'badge-ptkp-valid';
            let badgeLabel = 'VALID';
            let badgeIcon = 'ti-check';

            if (row.status === 'valid') {
                badgeClass = 'badge-ptkp-valid';
                badgeLabel = 'VALID';
                badgeIcon = 'ti-check';
            } else if (row.status === 'warning') {
                badgeClass = 'badge-ptkp-warning';
                badgeLabel = 'WARNING';
                badgeIcon = 'ti-alert-triangle';
            } else if (row.status === 'duplicate') {
                badgeClass = 'badge-ptkp-duplicate';
                badgeLabel = 'DUPLICATE';
                badgeIcon = 'ti-copy';
            } else if (row.status === 'invalid') {
                badgeClass = 'badge-ptkp-invalid';
                badgeLabel = 'INVALID';
                badgeIcon = 'ti-x';
            } else if (row.status === 'invalid_company') {
                badgeClass = 'badge-ptkp-invalid-company';
                badgeLabel = 'INVALID COMPANY';
                badgeIcon = 'ti-building';
            }

            const userMatchIcon = row.user_matched
                ? '<i class="ti ti-check text-success ms-1" title="LMS user matched (ID: ' + escapeHtml(d.user_id || '') + ')"></i>'
                : '<i class="ti ti-help text-muted ms-1" title="LMS user account not found (user_id NULL)"></i>';

            const msgText = (row.messages && row.messages.length > 0) ? row.messages.join('; ') : 'Ready to import';
            const msgClass = (row.status === 'invalid' || row.status === 'invalid_company')
                ? 'text-danger fw-semibold'
                : (row.status === 'duplicate' ? 'text-purple' : (row.status === 'warning' ? 'text-warning-emphasis' : 'text-muted'));

            const tr = [
                '<tr>',
                '<td class="text-center fw-medium text-muted">' + escapeHtml(row.excel_row || (startIdx + localIdx + 1)) + '</td>',
                '<td><span class="badge ' + badgeClass + ' px-2 py-1"><i class="ti ' + badgeIcon + ' me-1"></i>' + badgeLabel + '</span></td>',
                '<td class="fw-semibold font-monospace">' + escapeHtml(d.employee_id || '-') + '</td>',
                '<td>',
                    '<div class="fw-semibold text-dark">' + escapeHtml(d.name || '-') + userMatchIcon + '</div>',
                    '<div class="text-muted extra-small">' + escapeHtml(d.job_position || d.department || '-') + '</div>',
                '</td>',
                '<td><span class="text-muted small">' + escapeHtml(d.company || '-') + '</span></td>',
                '<td>',
                    '<div class="fw-medium text-dark">' + escapeHtml(d.course_name || '-') + '</div>',
                    '<div class="text-muted extra-small" style="font-size: 0.72rem;">' + escapeHtml(d.batch || '') + (d.start_date ? ' &bull; ' + escapeHtml(d.start_date) : '') + '</div>',
                '</td>',
                '<td><span class="badge bg-light text-secondary border">' + escapeHtml(d.type || '-') + '</span></td>',
                '<td><span class="text-muted small">' + escapeHtml(d.pic || '-') + '</span></td>',

                '<td>' + (row.messages && row.messages.length > 0 ? ('<div class="ptkp-row-msg ' + msgClass + '">' + escapeHtml(row.messages[0]) + (row.messages.length > 1 ? ' <span class="badge bg-light text-secondary border">+' + (row.messages.length - 1) + '</span>' : '') + '</div>') : '<span class="text-success small"><i class="ti ti-check me-1"></i>Ready to import</span>') + '</td>',
                '<td class="text-center">',
                    '<button type="button" class="btn btn-sm btn-ghost-primary rounded-circle p-1 btn-inspect-row" data-index="' + globalIdx + '" title="Inspect Row">',
                        '<i class="ti ti-eye fs-3"></i>',
                    '</button>',
                '</td>',
                '</tr>'
            ].join('');

            $tbody.append(tr);
        });
    }

    // Filter Tab click
    $('.filter-tab-btn').on('click', function () {
        $('.filter-tab-btn').removeClass('active btn-white').addClass('btn-ghost-secondary');
        $(this).addClass('active btn-white').removeClass('btn-ghost-secondary');
        currentFilter = $(this).data('filter');
        currentPage = 1;
        renderTable();
    });

    // Search input
    let searchDebounce = null;
    $('#preview-search-input').on('input', function () {
        clearTimeout(searchDebounce);
        const val = $(this).val();
        searchDebounce = setTimeout(function () {
            searchQuery = val.trim();
            currentPage = 1;
            renderTable();
        }, 200);
    });

    // Pagination buttons
    $('#btn-page-prev').on('click', function () {
        if (currentPage > 1) {
            currentPage--;
            renderTable();
        }
    });
    $('#btn-page-next').on('click', function () {
        const filtered = getFilteredRows();
        const totalPages = Math.ceil(filtered.length / pageSize) || 1;
        if (currentPage < totalPages) {
            currentPage++;
            renderTable();
        }
    });

    // =========================================================================
    // ROW DETAIL INSPECTION MODAL
    // =========================================================================
    $(document).on('click', '.btn-inspect-row', function () {
        const idx = $(this).data('index');
        const row = previewRowsData[idx];
        if (!row) return;

        const d = row.data || {};
        $('#modal-detail-row-num').text(row.excel_row);

        // Status banner inside modal
        let alertClass = 'alert-info';
        if (row.status === 'valid') alertClass = 'alert-success';
        else if (row.status === 'warning') alertClass = 'alert-warning';
        else if (row.status === 'duplicate') alertClass = 'alert-secondary';
        else if (row.status === 'invalid' || row.status === 'invalid_company') alertClass = 'alert-danger';

        const msgList = (row.messages && row.messages.length > 0)
            ? row.messages.map(function (m) { return '<li>' + escapeHtml(m) + '</li>'; }).join('')
            : '<li>Record meets Non-PTKP OWL validation rules.</li>';

        $('#modal-detail-status-banner').html(`
            <div class="alert ${alertClass} py-2 px-3 mb-0 rounded-3 small">
                <div class="d-flex align-items-center gap-2 mb-1">
                    <span class="badge bg-dark text-white text-uppercase">${escapeHtml(row.status)}</span>
                    <strong>Row #${row.excel_row} Status</strong>
                </div>
                <ul class="mb-0 ps-3 extra-small">${msgList}</ul>
            </div>
        `);

        // 1. Participant
        $('#modal-detail-nip').text(d.employee_id || '-');
        $('#modal-detail-name').text(d.name || '-');
        $('#modal-detail-grade').text(d.grade || '-');
        $('#modal-detail-position').text(d.job_position || '-');
        $('#modal-detail-team').text(d.team || '-');
        $('#modal-detail-dept').text(d.department || '-');
        $('#modal-detail-company').text(d.company || '-');
        $('#modal-detail-user-match').html(row.user_matched
            ? '<span class="badge bg-success-lt">Matched (User ID: ' + escapeHtml(d.user_id) + ')</span>'
            : '<span class="badge bg-secondary-lt">Not Found (user_id NULL)</span>');

        // 2. Training
        $('#modal-detail-course').text(d.course_name || '-');
        $('#modal-detail-subcourse').text(d.sub_course_name || '-');
        $('#modal-detail-batch').text(d.batch || '-');
        $('#modal-detail-type').text(d.type || '-');
        $('#modal-detail-category').text(d.category || '-');
        $('#modal-detail-level').text(d.level || '-');
        $('#modal-detail-venue').text(d.venue || '-');

        // 3. Schedule & Classification
        $('#modal-detail-start-date').text(d.start_date || '-');
        $('#modal-detail-finish-date').text(d.finish_date || '-');
        $('#modal-detail-hours').text(d.learning_hours ? d.learning_hours + ' hrs' : '-');
        $('#modal-detail-duration').text(d.duration_seconds ? d.duration_seconds + ' s' : '-');
        $('#modal-detail-main-class').text(d.main_classification || '-');
        $('#modal-detail-sub-class').text(d.sub_classification || '-');
        $('#modal-detail-kpi').text(d.counted_corporate_kpi ? 'Yes' + (d.sub_corporate_kpi ? ' (' + d.sub_corporate_kpi + ')' : '') : 'No');
        $('#modal-detail-provider').text((d.provider || '-') + (d.provider_name ? ' / ' + d.provider_name : ''));

        // 4. Obligation & Kirkpatrick
        $('#modal-detail-progress').text((d.progress !== null && d.progress !== undefined ? d.progress + '%' : '-') + (d.assessment_score ? ' | Score: ' + d.assessment_score : ''));
        $('#modal-detail-attendance').text(d.attendance ? 'Present' : 'Absent');
        $('#modal-detail-bond').text(d.bond_status ? 'Yes' + (d.bond_end_date ? ' (s/d ' + d.bond_end_date + ')' : '') : 'No');
        $('#modal-detail-bond-amt').text(d.bond_amount ? 'IDR ' + Number(d.bond_amount).toLocaleString() : '-');
        $('#modal-detail-l1').text(d.l1_program !== null ? d.l1_program : '-');
        $('#modal-detail-l2').text((d.l2_numerical !== null ? 'Num: ' + d.l2_numerical : '-') + ' | ' + (d.l2_verbal || '-'));
        $('#modal-detail-l3').text((d.l3_numerical !== null ? 'Num: ' + d.l3_numerical : '-') + ' | ' + (d.l3_verbal || '-'));
        $('#modal-detail-pic').text(d.pic || '-');

        const modalEl = document.getElementById('previewRowModal');
        if (typeof bootstrap !== 'undefined' && bootstrap.Modal) {
            bootstrap.Modal.getOrCreateInstance(modalEl).show();
        } else {
            $('#previewRowModal').modal('show');
        }
    });

    // =========================================================================
    // CANCEL & RE-UPLOAD
    // =========================================================================
    function handleCancelSession() {
        if (importToken) {
            $.ajax({
                url: "{{ route('manage.record.offline.import.cancel') }}",
                type: 'POST',
                data: {
                    token: importToken,
                    _token: csrfToken
                },
                headers: {
                    'X-CSRF-TOKEN': csrfToken
                }
            });
        }

        importToken = null;
        previewRowsData = [];
        resetFileInput();
        switchPanel('upload');
    }

    $('#btn-reupload-file, #btn-action-reupload').on('click', handleCancelSession);

    // =========================================================================
    // CONFIRM & EXECUTE IMPORT (Tahap 4 & 9)
    // =========================================================================
    $('#btn-action-confirm-import').on('click', function () {
        if (!importToken) return;

        if (!confirm('Are you sure you want to import valid records into the database? Duplicate and invalid records will be skipped safely.')) {
            return;
        }

        // Disable button to prevent double-click
        const $btn = $(this);
        $btn.prop('disabled', true);

        switchPanel('importing');

        $.ajax({
            url: "{{ route('manage.record.offline.import.confirm') }}",
            type: 'POST',
            data: {
                token: importToken,
                _token: csrfToken
            },
            headers: {
                'X-CSRF-TOKEN': csrfToken
            },
            success: function (res) {
                if (res && res.success && res.result) {
                    importCompleted = true;
                    importToken = null;
                    const r = res.result;

                    $('#res-total-processed').text((r.rows_processed || 0).toLocaleString());
                    $('#res-imported-count').text((r.imported || 0).toLocaleString());
                    $('#res-duplicates-skipped').text((r.skipped_duplicates || 0).toLocaleString());
                    $('#res-invalid-skipped').text((r.invalid || 0).toLocaleString());
                    $('#res-trainings-created').text((r.trainings_created || 0).toLocaleString());
                    $('#res-trainings-reused').text((r.trainings_reused || 0).toLocaleString());

                    switchPanel('result');
                } else {
                    switchPanel('preview');
                    $btn.prop('disabled', false);
                    alert(res.message || 'Import execution failed.');
                }
            },
            error: function (xhr) {
                switchPanel('preview');
                $btn.prop('disabled', false);
                let errMsg = 'Failed to execute import into database.';
                if (xhr.responseJSON && xhr.responseJSON.message) {
                    errMsg = xhr.responseJSON.message;
                }
                alert(errMsg);
            }
        });
    });

    $('#btn-import-another').on('click', function () {
        importCompleted = false;
        importToken = null;
        previewRowsData = [];
        resetFileInput();
        switchPanel('upload');
    });

    // Helper HTML Escape
    function escapeHtml(text) {
        if (text === null || text === undefined) return '';
        return String(text)
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;')
            .replace(/'/g, '&#039;');
    }
});
</script>
@endsection
