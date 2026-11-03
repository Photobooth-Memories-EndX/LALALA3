@extends('layouts.sidebar-master')

@section('title', 'Import Non-PTKP Training Records — Import Non-PTKP Data — OWL LMS')

@section('style')
<link rel="stylesheet" href="{{ asset('css/manage/non-ptkp-import.css') }}">
@endsection

@section('content')
<div class="page-body manage-record-page manage-record-import-non-ptkp py-4">
    <div class="container-xl">

        <!-- BREADCRUMB / TOP BACK LINK -->
        <div class="d-flex align-items-center justify-content-between flex-wrap gap-2 mb-3">
            <nav aria-label="breadcrumb">
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
                        <h2 class="h3 fw-bold mb-1 text-dark">Import Non-PTKP Training Records</h2>
                        <p class="text-muted small mb-0">Upload and import non-PTKP offline training records into KP-OWL. Employee information will be matched automatically.</p>
                    </div>
                </div>
                <div class="d-flex align-items-center gap-2" id="header-action-container">
                    <!-- Download Template: Only Step 1 -->
                    <a href="{{ route('manage.record.offline.import.template') }}" class="btn btn-outline-primary rounded-pill d-inline-flex align-items-center gap-2 px-3 py-2" id="btn-download-template" title="Download Template (.xlsx)">
                        <i class="ti ti-download fs-2"></i>
                        <span>Download Non-PTKP Template (.xlsx)</span>
                    </a>
                    <!-- Import History: Only Step 3 & Result -->
                    <a href="{{ route('manage.record.offline.import-history.index') }}" class="btn btn-outline-secondary rounded-pill d-inline-flex align-items-center gap-2 px-3 py-2 d-none" id="btn-import-history" title="View Import History">
                        <i class="ti ti-history fs-2"></i>
                        <span>Import History</span>
                    </a>
                </div>
            </div>
        </div>

        <!-- EXACTLY 3-STEP PROCESS INDICATOR -->
        <div class="card border-0 shadow-sm p-3 mb-4 rounded-3 bg-white stepper-card">
            <div class="owl-stepper">
                <!-- Step 1 -->
                <div class="stepper-item-node active stepper-item" id="stepper-step-1" data-step="1">
                    <div class="stepper-circle">
                        <span class="stepper-num">1</span>
                        <i class="ti ti-check stepper-check"></i>
                    </div>
                    <div class="stepper-label">Upload File</div>
                </div>
                <div class="stepper-connector" id="stepper-line-1"></div>
                <!-- Step 2 -->
                <div class="stepper-item-node stepper-item" id="stepper-step-2" data-step="2">
                    <div class="stepper-circle">
                        <span class="stepper-num">2</span>
                        <i class="ti ti-check stepper-check"></i>
                    </div>
                    <div class="stepper-label">Validation & Review</div>
                </div>
                <div class="stepper-connector" id="stepper-line-2"></div>
                <!-- Step 3 -->
                <div class="stepper-item-node stepper-item" id="stepper-step-3" data-step="3">
                    <div class="stepper-circle">
                        <span class="stepper-num">3</span>
                        <i class="ti ti-check stepper-check"></i>
                    </div>
                    <div class="stepper-label">Import Records</div>
                </div>
            </div>
        </div>

        <!-- =================================================================== -->
        <!-- STEP 1: UPLOAD FILE                                                 -->
        <!-- =================================================================== -->
        <div class="card border-0 shadow-sm rounded-4 mb-4 panel-import-state" id="panel-state-upload">
            <div class="card-body p-4">
                <div class="d-flex align-items-center justify-content-between mb-3 flex-wrap gap-2">
                    <div class="d-flex align-items-center gap-2">
                        <span class="badge bg-primary text-white rounded-circle d-inline-flex align-items-center justify-content-center" style="width: 26px; height: 26px; font-size: 0.8rem; background-color: #1e3a8a !important;">1</span>
                        <div>
                            <h4 class="card-title fw-bold text-dark mb-0">Upload File</h4>
                            <div class="text-muted small">Upload your Excel spreadsheet to begin the import process.</div>
                        </div>
                    </div>
                </div>

                <!-- Dropzone Upload Area -->
                <div class="upload-dropzone p-5 text-center my-3 position-relative" id="dropzone-container">
                    <input type="file" id="excel-file-input" class="position-absolute top-0 start-0 w-100 h-100 opacity-0" style="cursor: pointer;" accept=".xlsx,.xls" />
                    
                    <!-- Initial State (Hidden when file is selected) -->
                    <div class="dropzone-content py-3" id="dropzone-initial">
                        <div class="avatar avatar-lg rounded-circle mx-auto mb-3" style="background-color: #eff6ff; color: #1e3a8a; width: 64px; height: 64px;">
                            <i class="ti ti-cloud-upload fs-1"></i>
                        </div>
                        <h5 class="fw-bold text-dark mb-1">Choose an Excel file or drag & drop here</h5>
                        <p class="text-muted small mb-2">Supported formats: <strong>.xlsx, .xls</strong> (Max 20MB)</p>
                        <div class="d-inline-flex align-items-center gap-1 text-muted small mt-1">
                            <span class="badge bg-light text-muted border px-2.5 py-1" id="selected-file-box">
                                <i class="ti ti-paperclip me-1"></i><span id="selected-filename-text">No file selected</span>
                            </span>
                        </div>
                    </div>

                    <!-- Selected File Info Card (Only shown when file is selected) -->
                    <div class="selected-file-info-card p-4 text-start position-relative" id="selected-file-card" style="display: none; cursor: default;">
                        <div class="d-flex align-items-center justify-content-between flex-wrap gap-3">
                            <div class="d-flex align-items-center gap-3">
                                <div class="avatar avatar-lg rounded-3 flex-shrink-0 d-flex align-items-center justify-content-center" style="background-color: #dcfce7; color: #16a34a; width: 56px; height: 56px;">
                                    <i class="ti ti-file-spreadsheet fs-1"></i>
                                </div>
                                <div style="min-width: 0;">
                                    <h5 class="fw-bold text-dark mb-1 text-truncate" id="selected-filename-display">file.xlsx</h5>
                                    <div class="d-flex align-items-center gap-2 flex-wrap">
                                        <span class="text-muted small" id="selected-filesize-display">0 KB</span>
                                        <span class="badge bg-success-lt text-success"><i class="ti ti-check me-1"></i>File ready for validation</span>
                                    </div>
                                </div>
                            </div>
                            <div class="d-flex align-items-center gap-2">
                                <button type="button" class="btn btn-sm btn-outline-secondary rounded-pill px-3 d-inline-flex align-items-center gap-1" id="btn-change-file">
                                    <i class="ti ti-refresh"></i>
                                    <span>Change File</span>
                                </button>
                                <button type="button" class="btn btn-sm btn-outline-danger rounded-pill px-3 d-inline-flex align-items-center gap-1" id="btn-clear-file">
                                    <i class="ti ti-trash"></i>
                                    <span>Remove</span>
                                </button>
                            </div>
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
                                <li>Use the official OWL Non-PTKP template. Canonical single-row 46 columns and grouped headers are supported.</li>
                                <li>Do not rename or remove mandatory headers: <code>Course Name</code> / <code>Course Title</code>, <code>ID</code> / <code>Employee ID</code>, and <code>Name</code>.</li>
                                <li>Each row represents <strong>one training participant</strong>.</li>
                                <li>Participants in the same training session will be <strong>automatically grouped into a single training event</strong> based on course, batch, dates, type, and venue.</li>
                                <li>Duplicate records are handled via safe deduplication and <strong>skipped without modifying existing database records</strong>.</li>
                            </ul>
                        </div>
                    </div>
                </div>

                <!-- Action Buttons: Validate -->
                <div class="d-flex align-items-center justify-content-end pt-3 border-top">
                    <button type="button" class="btn btn-primary rounded-3 px-4 d-inline-flex align-items-center gap-2" id="btn-validate-upload" style="background-color: #1e3a8a; border-color: #1e3a8a;" disabled>
                        <i class="ti ti-sparkles"></i>
                        <span>Validate</span>
                    </button>
                </div>
            </div>
        </div>

        <!-- =================================================================== -->
        <!-- LOADING CARD (VALIDATING STATE)                                     -->
        <!-- =================================================================== -->
        <div class="card border-0 shadow-sm rounded-4 mb-4 py-5 text-center panel-import-state" id="panel-state-loading" style="display: none;">
            <div class="card-body py-5">
                <div class="spinner-border text-primary mb-3" style="width: 3.5rem; height: 3.5rem; color: #1e3a8a !important;" role="status"></div>
                <h4 class="fw-bold text-dark mt-2 mb-1" id="loading-state-title">Validating Spreadsheet...</h4>
                <p class="text-muted small mb-3" id="loading-state-subtitle">Reading columns, resolving header groups, and checking existing records...</p>
                <div class="progress progress-sm mx-auto" style="max-width: 320px; height: 4px;">
                    <div class="progress-bar progress-bar-indeterminate" style="background-color: #1e3a8a;"></div>
                </div>
            </div>
        </div>

        <!-- =================================================================== -->
        <!-- STEP 2: VALIDATION & REVIEW                                         -->
        <!-- =================================================================== -->
        <div class="card border-0 shadow-sm rounded-4 mb-4 panel-import-state" id="panel-state-preview" style="display: none;">
            <div class="card-header bg-white border-bottom pt-4 pb-3 px-4 rounded-top-4">
                <div class="d-flex align-items-center justify-content-between flex-wrap gap-2">
                    <div class="d-flex align-items-center gap-2 flex-wrap">
                        <span class="badge bg-primary text-white rounded-circle d-inline-flex align-items-center justify-content-center" style="width: 26px; height: 26px; font-size: 0.8rem; background-color: #1e3a8a !important;">2</span>
                        <div>
                            <h4 class="fw-bold text-dark mb-0">Validation & Review</h4>
                            <div class="text-muted small">Review validated training records before importing them into KP-OWL.</div>
                        </div>
                        <span class="badge bg-light text-secondary border px-2.5 py-1 ms-2" id="preview-filename-badge">-</span>
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

                <!-- 2. 7 STATISTIK RINGKASAN HASIL VALIDASI -->
                <div class="row g-2 g-md-3 mb-4">
                    <!-- Total -->
                    <div class="col-6 col-md-3 col-lg">
                        <div class="card border-0 shadow-none bg-light p-3 rounded-3 text-center h-100 stat-box" title="Total spreadsheet rows read">
                            <div class="text-muted extra-small fw-medium mb-1">Total Rows</div>
                            <div class="h3 fw-bold text-dark mb-0" id="stat-total-rows">0</div>
                            <div class="extra-small text-muted mt-1">records read</div>
                        </div>
                    </div>
                    <!-- Valid -->
                    <div class="col-6 col-md-3 col-lg">
                        <div class="card border-0 shadow-none p-3 rounded-3 text-center h-100 stat-box" style="background-color: #ecfdf5;" title="Valid records ready to import">
                            <div class="extra-small fw-medium mb-1" style="color: #065f46;">VALID</div>
                            <div class="h3 fw-bold mb-0" style="color: #047857;" id="stat-valid-rows">0</div>
                            <div class="extra-small mt-1" style="color: #065f46;">ready to import</div>
                        </div>
                    </div>
                    <!-- Warnings -->
                    <div class="col-6 col-md-3 col-lg">
                        <div class="card border-0 shadow-none p-3 rounded-3 text-center h-100 stat-box" style="background-color: #fffbeb;" title="Valid records with nonfatal warnings">
                            <div class="extra-small fw-medium mb-1" style="color: #92400e;">WARNING</div>
                            <div class="h3 fw-bold mb-0" style="color: #d97706;" id="stat-warning-rows">0</div>
                            <div class="extra-small mt-1" style="color: #92400e;">nonfatal issues</div>
                        </div>
                    </div>
                    <!-- Duplicates -->
                    <div class="col-6 col-md-3 col-lg">
                        <div class="card border-0 shadow-none p-3 rounded-3 text-center h-100 stat-box" style="background-color: #f5f3ff;" title="Existing records or duplicates that will be skipped">
                            <div class="extra-small fw-medium mb-1" style="color: #5b21b6;">DUPLICATE</div>
                            <div class="h3 fw-bold mb-0" style="color: #7c3aed;" id="stat-duplicate-rows">0</div>
                            <div class="extra-small mt-1" style="color: #5b21b6;">will be skipped</div>
                        </div>
                    </div>
                    <!-- Invalid -->
                    <div class="col-6 col-md-3 col-lg">
                        <div class="card border-0 shadow-none p-3 rounded-3 text-center h-100 stat-box" style="background-color: #fef2f2;" title="Records with fatal validation errors">
                            <div class="extra-small fw-medium mb-1" style="color: #991b1b;">INVALID</div>
                            <div class="h3 fw-bold mb-0" style="color: #dc2626;" id="stat-invalid-rows">0</div>
                            <div class="extra-small mt-1" style="color: #991b1b;">fatal errors</div>
                        </div>
                    </div>
                    <!-- Invalid Company -->
                    <div class="col-6 col-md-3 col-lg">
                        <div class="card border-0 shadow-none p-3 rounded-3 text-center h-100 stat-box" style="background-color: #fff7ed;" title="Records with empty company (will be imported; needs review)">
                            <div class="extra-small fw-medium mb-1" style="color: #c2410c;">INVALID COMPANY</div>
                            <div class="h3 fw-bold mb-0" style="color: #ea580c;" id="stat-invalid-company-rows">0</div>
                            <div class="extra-small mt-1" style="color: #c2410c;">needs review</div>
                        </div>
                    </div>
                    <!-- Events -->
                    <div class="col-6 col-md-3 col-lg">
                        <div class="card border-0 shadow-none p-3 rounded-3 text-center h-100 stat-box" style="background-color: #f0fdfa;" title="Total unique training sessions identified">
                            <div class="extra-small fw-medium mb-1" style="color: #115e59;">Events</div>
                            <div class="h3 fw-bold mb-0" style="color: #0f766e;" id="stat-training-events">0</div>
                            <div class="extra-small mt-1" style="color: #115e59;">unique sessions</div>
                        </div>
                    </div>
                </div>

                <!-- 3. RINGKASAN KONDISIONAL STATUS BANNER -->
                <div id="preview-state-banner" class="mb-4" style="display: none;"></div>

                <!-- 4. PERBANDINGAN DATA SEBELUM IMPORT -->
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
                        <button type="button" class="btn btn-sm btn-ghost-secondary rounded-pill px-3 filter-tab-btn" data-filter="invalid_company">
                            <span class="status-dot bg-orange d-inline-block me-1"></span> Invalid Company (<span id="filter-count-invalid-company">0</span>)
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

                <!-- 6. PREVIEW DATA TABLE -->
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

                <!-- STEP 2 NAVIGATION: Cancel, Back, Next -->
                <div class="d-flex align-items-center justify-content-between flex-wrap gap-3 pt-3 border-top">
                    <div>
                        <button type="button" class="btn btn-outline-danger rounded-3 px-4" id="btn-action-cancel">
                            <i class="ti ti-x me-1"></i>Cancel
                        </button>
                    </div>
                    <div class="d-flex align-items-center gap-2">
                        <button type="button" class="btn btn-outline-secondary rounded-3 px-4 d-inline-flex align-items-center gap-1.5" id="btn-back-to-step1">
                            <i class="ti ti-arrow-left"></i>
                            <span>Back</span>
                        </button>
                        <button type="button" class="btn btn-primary rounded-3 px-4 d-inline-flex align-items-center gap-2" id="btn-next-to-step3" style="background-color: #1e3a8a; border-color: #1e3a8a;">
                            <span>Next / Continue to Import</span>
                            <i class="ti ti-arrow-right"></i>
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <!-- =================================================================== -->
        <!-- STEP 3: IMPORT RECORDS                                              -->
        <!-- =================================================================== -->
        <div class="card border-0 shadow-sm rounded-4 mb-4 panel-import-state" id="panel-state-confirm" style="display: none;">
            <div class="card-header bg-white border-bottom pt-4 pb-3 px-4 rounded-top-4">
                <div class="d-flex align-items-center gap-2">
                    <span class="badge bg-primary text-white rounded-circle d-inline-flex align-items-center justify-content-center" style="width: 26px; height: 26px; font-size: 0.8rem; background-color: #1e3a8a !important;">3</span>
                    <div>
                        <h4 class="card-title fw-bold text-dark mb-0">Import Records</h4>
                        <div class="text-muted small">Import validated training records into KP-OWL.</div>
                    </div>
                </div>
            </div>

            <div class="card-body p-4 p-md-5">
                <!-- Pre-Import Summary & Safeguards -->
                <div class="pre-import-summary-wrapper mb-4">
                    <!-- Section Header -->
                    <div class="d-flex align-items-center gap-3 mb-4">
                        <div class="rounded-3 d-flex align-items-center justify-content-center flex-shrink-0" style="width: 44px; height: 44px; background-color: #eff6ff; color: #1e3a8a; border: 1px solid #bfdbfe;">
                            <i class="ti ti-clipboard-check fs-1"></i>
                        </div>
                        <div>
                            <h4 class="fw-bold text-dark mb-0">Pre-Import Summary &amp; Safeguards</h4>
                            <div class="text-muted small">Verify exact record counts and database safety guarantees before execution.</div>
                        </div>
                    </div>

                    <!-- Summary Cards (50:50, Equal Height) -->
                    <div class="row g-4 mb-4">
                        <!-- Group A: Records to Be Imported (Left Card) -->
                        <div class="col-12 col-lg-6">
                            <div class="card border rounded-4 p-4 h-100 pre-import-card pre-import-card-eligible">
                                <div class="pre-import-header">
                                    <span class="pre-import-header-icon" style="background-color: #1e3a8a;">
                                        <i class="ti ti-check"></i>
                                    </span>
                                    <div>
                                        <h5 class="fw-bold text-dark mb-1">Jumlah Record yang Akan Di-import</h5>
                                        <p class="text-muted small mb-0">Records that passed validation and are eligible for import.</p>
                                    </div>
                                </div>

                                <div class="pre-import-breakdown">
                                    <div class="pre-import-row">
                                        <div class="pre-import-row-label">
                                            <span class="pre-import-row-icon" style="background-color: #dcfce7; color: #16a34a;"><i class="ti ti-check"></i></span>
                                            <span>Valid Records</span>
                                        </div>
                                        <span class="pre-import-row-count text-success" id="res-step3-valid-count">0</span>
                                    </div>
                                    <div class="pre-import-row">
                                        <div class="pre-import-row-label">
                                            <span class="pre-import-row-icon" style="background-color: #fef3c7; color: #d97706;"><i class="ti ti-alert-triangle"></i></span>
                                            <span>Warning Records (Non-blocking)</span>
                                        </div>
                                        <span class="pre-import-row-count text-warning" id="res-step3-warning-count">0</span>
                                    </div>
                                    <div class="pre-import-row" id="res-step3-invalid-company-eligible-row" style="display: none;">
                                        <div class="pre-import-row-label">
                                            <span class="pre-import-row-icon" style="background-color: #ffedd5; color: #ea580c;"><i class="ti ti-building"></i></span>
                                            <span>Invalid Company (Imported with Review Tag)</span>
                                        </div>
                                        <span class="pre-import-row-count text-orange" id="res-step3-invalid-company-count">0</span>
                                    </div>
                                </div>

                                <div class="pre-import-total-box mt-auto">
                                    <div>
                                        <div class="pre-import-total-label">Total to Import</div>
                                        <div class="pre-import-total-desc">Ready to write into database</div>
                                    </div>
                                    <div class="pre-import-total-value" id="res-step3-total-to-import">0 Records</div>
                                </div>
                            </div>
                        </div>

                        <!-- Group B: Records Not to Be Imported (Right Card) -->
                        <div class="col-12 col-lg-6">
                            <div class="card border rounded-4 p-4 h-100 pre-import-card pre-import-card-skipped">
                                <div class="pre-import-header">
                                    <span class="pre-import-header-icon" style="background-color: #dc2626;">
                                        <i class="ti ti-ban"></i>
                                    </span>
                                    <div>
                                        <h5 class="fw-bold text-dark mb-1">Jumlah Record yang Tidak Di-import</h5>
                                        <p class="text-muted small mb-0">Records that will be skipped because they failed validation or already exist.</p>
                                    </div>
                                </div>

                                <div class="pre-import-breakdown">
                                    <div class="pre-import-row">
                                        <div class="pre-import-row-label">
                                            <span class="pre-import-row-icon" style="background-color: #fee2e2; color: #dc2626;"><i class="ti ti-x"></i></span>
                                            <span>Invalid Employee ID &amp; Course</span>
                                        </div>
                                        <span class="pre-import-row-count text-danger" id="res-step3-invalid-count">0</span>
                                    </div>
                                    <div class="pre-import-row">
                                        <div class="pre-import-row-label">
                                            <span class="pre-import-row-icon" style="background-color: #f1f5f9; color: #64748b;"><i class="ti ti-info-circle"></i></span>
                                            <span>Invalid Company (Skipped) <span class="text-muted extra-small" title="Non-blocking warning in OWL backend; imported with review note">(Non-blocking in OWL)</span></span>
                                        </div>
                                        <span class="pre-import-row-count text-muted" id="res-step3-invalid-company-skipped">0</span>
                                    </div>
                                    <div class="pre-import-row">
                                        <div class="pre-import-row-label">
                                            <span class="pre-import-row-icon" style="background-color: #f3e8ff; color: #7c3aed;"><i class="ti ti-copy"></i></span>
                                            <span>Duplicate Records</span>
                                        </div>
                                        <span class="pre-import-row-count text-purple" id="res-step3-duplicate-count">0</span>
                                    </div>
                                </div>

                                <div class="pre-import-total-box mt-auto">
                                    <div>
                                        <div class="pre-import-total-label">Total Not Imported</div>
                                        <div class="pre-import-total-desc">Safely skipped to prevent bad data</div>
                                    </div>
                                    <div class="pre-import-total-value" id="res-step3-total-not-imported">0 Records</div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Safeguard Notice Banner -->
                    <div class="pre-import-safeguard-banner mb-0">
                        <div class="rounded-circle d-flex align-items-center justify-content-center flex-shrink-0" style="width: 40px; height: 40px; background-color: #e0f2fe; color: #0284c7;">
                            <i class="ti ti-shield-check fs-1"></i>
                        </div>
                        <div class="small lh-base text-dark">
                            <strong class="text-dark">Database Safety Guarantee:</strong> Only records that passed validation will be imported into KP-OWL. Existing enrolled participants and duplicate entries will not be overwritten.
                        </div>
                    </div>
                </div>

                <!-- STEP 3 NAVIGATION BUTTONS: Back, Confirm & Import -->
                <div class="d-flex align-items-center justify-content-between flex-wrap gap-3 pt-4 border-top">
                    <button type="button" class="btn btn-outline-secondary rounded-3 px-4 py-2.5 d-inline-flex align-items-center gap-2 fw-medium" id="btn-back-to-step2">
                        <i class="ti ti-arrow-left fs-2"></i>
                        <span>Back</span>
                    </button>
                    <button type="button" class="btn btn-primary btn-confirm-import-darkblue rounded-3 px-4 py-2.5 d-inline-flex align-items-center gap-2 fw-semibold" id="btn-action-confirm-import">
                        <i class="ti ti-check fs-2"></i>
                        <span>Confirm &amp; Import (<span id="btn-confirm-import-count">0</span> Records)</span>
                    </button>
                </div>
            </div>
        </div>

        <!-- =================================================================== -->
        <!-- IMPORTING IN PROGRESS LOADING STATE                                 -->
        <!-- =================================================================== -->
        <div class="card border-0 shadow-sm rounded-4 mb-4 py-5 text-center panel-import-state" id="panel-state-importing" style="display: none;">
            <div class="card-body py-5">
                <div class="spinner-border text-primary mb-3" style="width: 3.5rem; height: 3.5rem; color: #1e3a8a !important;" role="status"></div>
                <h4 class="fw-bold text-dark mt-2 mb-1">Importing Training Records...</h4>
                <p class="text-muted small mb-3">Executing database transaction, creating training sessions, and registering participants...</p>
                <div class="progress progress-sm mx-auto" style="max-width: 340px; height: 4px;">
                    <div class="progress-bar progress-bar-indeterminate" style="background-color: #1e3a8a;"></div>
                </div>
            </div>
        </div>

        <!-- =================================================================== -->
        <!-- FINAL RESULT PAGE: IMPORT COMPLETED (DASHBOARD REDESIGN)           -->
        <!-- =================================================================== -->
        <div class="card border-0 shadow-sm rounded-4 mb-4 panel-import-state" id="panel-state-result" style="display: none;">
            <div class="card-body p-4 p-md-5">

                <!-- 1. SUCCESS BANNER -->
                <div class="p-4 rounded-4 mb-4" id="res-success-banner" style="background-color: #ecfdf5; border: 1px solid #a7f3d0;">
                    <div class="d-flex align-items-start gap-3">
                        <div class="rounded-circle d-flex align-items-center justify-content-center flex-shrink-0" style="width: 48px; height: 48px; background-color: #10b981; color: #ffffff;">
                            <i class="ti ti-check fs-1 fw-bold"></i>
                        </div>
                        <div>
                            <h3 class="fw-bold mb-1" style="color: #064e3b;" id="res-header-title">Import Completed</h3>
                            <p class="mb-0 text-muted" id="res-header-desc" style="color: #047857 !important;">Your training records have been processed successfully. Please review the import results below.</p>
                        </div>
                    </div>
                </div>

                <!-- 2. IMPORT SUCCESS RATE SECTION -->
                <div class="card border rounded-4 mb-4 shadow-none" style="background-color: #ffffff; border-color: #e2e8f0 !important;">
                    <div class="card-body p-4">
                        <div class="d-flex align-items-center justify-content-between mb-3">
                            <h4 class="fw-bold text-dark mb-0">Import Success Rate</h4>
                            <span class="h1 fw-bold text-dark mb-0" id="res-rate-percentage">0.0%</span>
                        </div>

                        <!-- Progress Bar -->
                        <div class="progress rounded-pill mb-3" style="height: 14px; background-color: #e2e8f0;">
                            <div class="progress-bar rounded-pill" id="res-rate-progress-bar" role="progressbar" style="width: 0%; background-color: #1e3a8a; transition: width 0.8s ease;" aria-valuenow="0" aria-valuemin="0" aria-valuemax="100"></div>
                        </div>

                        <div class="d-flex align-items-center justify-content-between small">
                            <span class="d-inline-flex align-items-center gap-1.5 fw-bold" style="color: #15803d;">
                                <i class="ti ti-check"></i> <span id="res-rate-imported-count">0</span> imported
                            </span>
                            <span class="d-inline-flex align-items-center gap-1.5 text-muted fw-semibold">
                                <span id="res-rate-skipped-count">0</span> skipped
                            </span>
                        </div>
                    </div>
                </div>

                <!-- 3. IMPORT SUMMARY — 6 CARDS IN 2 COLUMNS -->
                <div class="mb-4">
                    <h4 class="fw-bold text-dark mb-3">Import Summary</h4>
                    <div class="row g-3 g-md-4">
                        <!-- Card 1: Total Records Processed -->
                        <div class="col-12 col-md-6">
                            <div class="card border rounded-3 p-3 p-md-4 h-100 import-summary-card" style="background-color: #f8fafc; border-color: #e2e8f0 !important;">
                                <div class="d-flex align-items-center gap-3">
                                    <div class="rounded-circle d-flex align-items-center justify-content-center flex-shrink-0" style="width: 52px; height: 52px; background-color: #eff6ff; color: #1e3a8a;">
                                        <i class="ti ti-file-text fs-1"></i>
                                    </div>
                                    <div class="flex-grow-1">
                                        <div class="text-muted small fw-semibold">Total Records Processed</div>
                                        <div class="h2 fw-bold text-dark mb-0" id="res-total-processed">0</div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Card 2: Successfully Imported -->
                        <div class="col-12 col-md-6">
                            <div class="card border rounded-3 p-3 p-md-4 h-100 import-summary-card" style="background-color: #f0fdf4; border-color: #bbf7d0 !important;">
                                <div class="d-flex align-items-center gap-3">
                                    <div class="rounded-circle d-flex align-items-center justify-content-center flex-shrink-0" style="width: 52px; height: 52px; background-color: #dcfce7; color: #16a34a;">
                                        <i class="ti ti-circle-check fs-1"></i>
                                    </div>
                                    <div class="flex-grow-1">
                                        <div class="small fw-semibold" style="color: #166534;">Successfully Imported</div>
                                        <div class="h2 fw-bold mb-0" style="color: #15803d;" id="res-imported-count">0</div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Card 3: Records Imported with Warnings -->
                        <div class="col-12 col-md-6">
                            <div class="card border rounded-3 p-3 p-md-4 h-100 import-summary-card" style="background-color: #fffbeb; border-color: #fde68a !important;">
                                <div class="d-flex align-items-center gap-3">
                                    <div class="rounded-circle d-flex align-items-center justify-content-center flex-shrink-0" style="width: 52px; height: 52px; background-color: #fef3c7; color: #d97706;">
                                        <i class="ti ti-alert-triangle fs-1"></i>
                                    </div>
                                    <div class="flex-grow-1">
                                        <div class="small fw-semibold" style="color: #92400e;">Records Imported with Warnings</div>
                                        <div class="h2 fw-bold mb-0" style="color: #b45309;" id="res-warnings-imported">0</div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Card 4: Duplicate Records Skipped -->
                        <div class="col-12 col-md-6">
                            <div class="card border rounded-3 p-3 p-md-4 h-100 import-summary-card" style="background-color: #faf5ff; border-color: #e9d5ff !important;">
                                <div class="d-flex align-items-center gap-3">
                                    <div class="rounded-circle d-flex align-items-center justify-content-center flex-shrink-0" style="width: 52px; height: 52px; background-color: #f3e8ff; color: #7c3aed;">
                                        <i class="ti ti-copy fs-1"></i>
                                    </div>
                                    <div class="flex-grow-1">
                                        <div class="small fw-semibold" style="color: #6b21a8;">Duplicate Records Skipped</div>
                                        <div class="h2 fw-bold mb-0" style="color: #7c3aed;" id="res-duplicates-skipped">0</div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Card 5: Invalid Employee ID / Course -->
                        <div class="col-12 col-md-6">
                            <div class="card border rounded-3 p-3 p-md-4 h-100 import-summary-card" style="background-color: #fef2f2; border-color: #fecaca !important;">
                                <div class="d-flex align-items-center gap-3">
                                    <div class="rounded-circle d-flex align-items-center justify-content-center flex-shrink-0" style="width: 52px; height: 52px; background-color: #fee2e2; color: #dc2626;">
                                        <i class="ti ti-user-x fs-1"></i>
                                    </div>
                                    <div class="flex-grow-1">
                                        <div class="small fw-semibold" style="color: #991b1b;">Invalid Employee ID / Course</div>
                                        <div class="h2 fw-bold mb-0" style="color: #dc2626;" id="res-invalid-skipped">0</div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Card 6: Invalid Company Records -->
                        <div class="col-12 col-md-6">
                            <div class="card border rounded-3 p-3 p-md-4 h-100 import-summary-card" style="background-color: #fff7ed; border-color: #fed7aa !important;">
                                <div class="d-flex align-items-center gap-3">
                                    <div class="rounded-circle d-flex align-items-center justify-content-center flex-shrink-0" style="width: 52px; height: 52px; background-color: #ffedd5; color: #ea580c;">
                                        <i class="ti ti-building fs-1"></i>
                                    </div>
                                    <div class="flex-grow-1">
                                        <div class="small fw-semibold" style="color: #9a3412;">Invalid Company Records</div>
                                        <div class="h2 fw-bold mb-0" style="color: #ea580c;" id="res-invalid-company-imported">0</div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Additional Session Metadata (trainings created & reused) -->
                    <div class="d-flex align-items-center justify-content-end gap-3 mt-3 text-muted extra-small">
                        <span><i class="ti ti-sparkles me-1 text-primary"></i>Training Sessions Created: <strong class="text-dark" id="res-trainings-created">0</strong></span>
                        <span>·</span>
                        <span><i class="ti ti-rotate-clockwise me-1 text-secondary"></i>Sessions Reused: <strong class="text-dark" id="res-trainings-reused">0</strong></span>
                    </div>
                </div>

                <!-- 4. BOTTOM ACTION BUTTONS -->
                <div class="d-flex align-items-center justify-content-between flex-wrap gap-3 pt-3 border-top">
                    <button type="button" class="btn btn-outline-secondary rounded-3 px-4 py-2.5 d-inline-flex align-items-center gap-2" id="btn-import-another">
                        <i class="ti ti-upload fs-2"></i>
                        <span>Import Another File</span>
                    </button>
                    <a href="{{ route('manage.record.offline') }}" class="btn btn-primary rounded-3 px-4 py-2.5 d-inline-flex align-items-center gap-2" style="background-color: #1e3a8a; border-color: #1e3a8a;" id="btn-view-imported-records">
                        <i class="ti ti-table fs-2"></i>
                        <span>View Imported Records</span>
                    </a>
                </div>

            </div>
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
            <div class="modal-header border-bottom py-3 px-4">
                <div class="d-flex align-items-center gap-2">
                    <span class="avatar rounded-3" style="background-color: #eff6ff; color: #1e3a8a; width: 42px; height: 42px;">
                        <i class="ti ti-file-search fs-2"></i>
                    </span>
                    <div>
                        <h5 class="modal-title fw-bold text-dark mb-0">Record Detail Inspection (Row <span id="modal-detail-row-num">-</span>)</h5>
                        <div class="text-muted extra-small">Detailed comparison between spreadsheet columns and system schema</div>
                    </div>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>

            <div class="modal-body p-4">
                <div id="modal-detail-status-banner" class="mb-4"></div>

                <div class="row g-3">
                    <!-- 1. Participant Details -->
                    <div class="col-md-6">
                        <div class="card border-0 bg-light p-3 rounded-3 h-100">
                            <div class="fw-bold small text-dark mb-2 text-uppercase" style="font-size: 0.75rem; letter-spacing: 0.05em;">
                                <i class="ti ti-user me-1 text-primary"></i> 1. Participant Information
                            </div>
                            <div class="row g-2 small">
                                <div class="col-6"><span class="text-muted d-block">Employee ID:</span><span class="fw-semibold text-dark font-monospace" id="modal-detail-nip">-</span></div>
                                <div class="col-6"><span class="text-muted d-block">Full Name:</span><span class="fw-semibold text-dark" id="modal-detail-name">-</span></div>
                                <div class="col-6"><span class="text-muted d-block">Grade:</span><span class="fw-semibold text-dark" id="modal-detail-grade">-</span></div>
                                <div class="col-6"><span class="text-muted d-block">Job Position:</span><span class="fw-semibold text-dark" id="modal-detail-position">-</span></div>
                                <div class="col-6"><span class="text-muted d-block">Team:</span><span class="fw-semibold text-dark" id="modal-detail-team">-</span></div>
                                <div class="col-6"><span class="text-muted d-block">Department:</span><span class="fw-semibold text-dark" id="modal-detail-dept">-</span></div>
                                <div class="col-6"><span class="text-muted d-block">Company:</span><span class="fw-semibold text-dark" id="modal-detail-company">-</span></div>
                                <div class="col-6"><span class="text-muted d-block">LMS Match:</span><span id="modal-detail-user-match">-</span></div>
                            </div>
                        </div>
                    </div>

                    <!-- 2. Training Event Details -->
                    <div class="col-md-6">
                        <div class="card border-0 bg-light p-3 rounded-3 h-100">
                            <div class="fw-bold small text-dark mb-2 text-uppercase" style="font-size: 0.75rem; letter-spacing: 0.05em;">
                                <i class="ti ti-book me-1 text-primary"></i> 2. Training Event
                            </div>
                            <div class="row g-2 small">
                                <div class="col-12"><span class="text-muted d-block">Course Title:</span><span class="fw-semibold text-dark" id="modal-detail-course">-</span></div>
                                <div class="col-12"><span class="text-muted d-block">Sub Course Title:</span><span class="fw-semibold text-dark" id="modal-detail-subcourse">-</span></div>
                                <div class="col-6"><span class="text-muted d-block">Batch:</span><span class="fw-semibold text-dark" id="modal-detail-batch">-</span></div>
                                <div class="col-6"><span class="text-muted d-block">Type:</span><span class="fw-semibold text-dark" id="modal-detail-type">-</span></div>
                                <div class="col-6"><span class="text-muted d-block">Category:</span><span class="fw-semibold text-dark" id="modal-detail-category">-</span></div>
                                <div class="col-6"><span class="text-muted d-block">Level:</span><span class="fw-semibold text-dark" id="modal-detail-level">-</span></div>
                                <div class="col-12"><span class="text-muted d-block">Venue:</span><span class="fw-semibold text-dark" id="modal-detail-venue">-</span></div>
                            </div>
                        </div>
                    </div>

                    <!-- 3. Schedule, Hours & Provider -->
                    <div class="col-md-6">
                        <div class="card border-0 bg-light p-3 rounded-3 h-100">
                            <div class="fw-bold small text-dark mb-2 text-uppercase" style="font-size: 0.75rem; letter-spacing: 0.05em;">
                                <i class="ti ti-calendar me-1 text-primary"></i> 3. Schedule & Provider
                            </div>
                            <div class="row g-2 small">
                                <div class="col-6"><span class="text-muted d-block">Start Date:</span><span class="fw-semibold text-dark" id="modal-detail-start-date">-</span></div>
                                <div class="col-6"><span class="text-muted d-block">Finish Date:</span><span class="fw-semibold text-dark" id="modal-detail-finish-date">-</span></div>
                                <div class="col-6"><span class="text-muted d-block">Learning Hours:</span><span class="fw-semibold text-dark" id="modal-detail-hours">-</span></div>
                                <div class="col-6"><span class="text-muted d-block">Duration:</span><span class="fw-semibold text-dark" id="modal-detail-duration">-</span></div>
                                <div class="col-6"><span class="text-muted d-block">Main Class:</span><span class="fw-semibold text-dark" id="modal-detail-main-class">-</span></div>
                                <div class="col-6"><span class="text-muted d-block">Sub Class:</span><span class="fw-semibold text-dark" id="modal-detail-sub-class">-</span></div>
                                <div class="col-6"><span class="text-muted d-block">Corp KPI:</span><span class="fw-semibold text-dark" id="modal-detail-kpi">-</span></div>
                                <div class="col-6"><span class="text-muted d-block">Provider:</span><span class="fw-semibold text-dark" id="modal-detail-provider">-</span></div>
                            </div>
                        </div>
                    </div>

                    <!-- 4. Progress, Obligation & Kirkpatrick -->
                    <div class="col-md-6">
                        <div class="card border-0 bg-light p-3 rounded-3 h-100">
                            <div class="fw-bold small text-dark mb-2 text-uppercase" style="font-size: 0.75rem; letter-spacing: 0.05em;">
                                <i class="ti ti-award me-1 text-primary"></i> 4. Evaluation & Obligation
                            </div>
                            <div class="row g-2 small">
                                <div class="col-6"><span class="text-muted d-block">Progress & Score:</span><span class="fw-semibold text-dark" id="modal-detail-progress">-</span></div>
                                <div class="col-6"><span class="text-muted d-block">Attendance:</span><span class="fw-semibold text-dark" id="modal-detail-attendance">-</span></div>
                                <div class="col-6"><span class="text-muted d-block">Service Bond:</span><span class="fw-semibold text-dark" id="modal-detail-bond">-</span></div>
                                <div class="col-6"><span class="text-muted d-block">Bond Amount:</span><span class="fw-semibold text-dark" id="modal-detail-bond-amt">-</span></div>
                                <div class="col-6"><span class="text-muted d-block">Score L1:</span><span class="fw-semibold text-dark" id="modal-detail-l1">-</span></div>
                                <div class="col-6"><span class="text-muted d-block">Score L2:</span><span class="fw-semibold text-dark" id="modal-detail-l2">-</span></div>
                                <div class="col-6"><span class="text-muted d-block">Score L3:</span><span class="fw-semibold text-dark" id="modal-detail-l3">-</span></div>
                                <div class="col-6"><span class="text-muted d-block">PIC:</span><span class="fw-semibold text-dark" id="modal-detail-pic">-</span></div>
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
    const $dropzoneInitial = $('#dropzone-initial');
    const $selectedFileCard = $('#selected-file-card');
    const $selectedFilenameDisplay = $('#selected-filename-display');
    const $selectedFilesizeDisplay = $('#selected-filesize-display');
    const $selectedFilenameText = $('#selected-filename-text');
    const $btnClearFile = $('#btn-clear-file');
    const $btnChangeFile = $('#btn-change-file');
    const $uploadErrorBox = $('#upload-error-box');
    const $uploadErrorMsg = $('#upload-error-message');
    const $btnValidate = $('#btn-validate-upload');

    // Header buttons
    const $btnDownloadTemplate = $('#btn-download-template');
    const $btnImportHistory = $('#btn-import-history');

    // State panels
    const $panelUpload = $('#panel-state-upload');
    const $panelLoading = $('#panel-state-loading');
    const $panelPreview = $('#panel-state-preview');
    const $panelConfirm = $('#panel-state-confirm');
    const $panelImporting = $('#panel-state-importing');
    const $panelResult = $('#panel-state-result');

    // Stepper node elements
    const $step1 = $('#stepper-step-1');
    const $step2 = $('#stepper-step-2');
    const $step3 = $('#stepper-step-3');
    const $line1 = $('#stepper-line-1');
    const $line2 = $('#stepper-line-2');

    // CSRF Setup
    const csrfToken = $('meta[name="csrf-token"]').attr('content') || '{{ csrf_token() }}';

    // =========================================================================
    // CONDITIONAL HEADER BUTTON VISIBILITY
    // =========================================================================
    function updateHeaderActions(currentStep) {
        const downloadTemplate = document.getElementById('btn-download-template') || document.getElementById('downloadTemplate');
        const importHistory = document.getElementById('btn-import-history') || document.getElementById('importHistory');

        if (downloadTemplate) {
            downloadTemplate.classList.toggle('d-none', currentStep !== 1);
        }

        if (importHistory) {
            importHistory.classList.toggle('d-none', currentStep < 3);
        }
    }

    // =========================================================================
    // 3-STEP PROCESS STEPPER & HEADER ACTION PLACEMENT
    // =========================================================================
    function setStepState(activeStep) {
        // activeStep:
        // 1: Upload File
        // 2: Validation & Review
        // 3: Import Records
        // 4: Import Summary (Result)
        const steps = [$step1, $step2, $step3];
        const lines = [$line1, $line2];

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

        lines.forEach(function ($line, idx) {
            const lineNum = idx + 1;
            if (lineNum < activeStep) {
                $line.addClass('completed');
            } else {
                $line.removeClass('completed');
            }
        });

        if (activeStep >= 4) {
            $step1.removeClass('active pending').addClass('completed');
            $step2.removeClass('active pending').addClass('completed');
            $step3.removeClass('active pending').addClass('completed');
            $line1.addClass('completed');
            $line2.addClass('completed');
        }

        updateHeaderActions(activeStep);
    }

    function switchPanel(panelName) {
        $panelUpload.hide();
        $panelLoading.hide();
        $panelPreview.hide();
        $panelConfirm.hide();
        $panelImporting.hide();
        $panelResult.hide();

        if (panelName === 'upload') {
            setStepState(1);
            updateHeaderActions(1);
            $panelUpload.show();
        } else if (panelName === 'loading') {
            // Validation loading indicator (stays at Step 1 visually)
            $panelLoading.show();
        } else if (panelName === 'preview') {
            setStepState(2);
            updateHeaderActions(2);
            $panelPreview.show();
        } else if (panelName === 'confirm') {
            setStepState(3);
            updateHeaderActions(3);
            $panelConfirm.show();
        } else if (panelName === 'importing') {
            $panelImporting.show();
        } else if (panelName === 'result') {
            setStepState(4);
            updateHeaderActions(4);
            $panelResult.show();
        }
    }

    // Initialize Stepper & Header Actions at Step 1
    setStepState(1);
    updateHeaderActions(1);

    // =========================================================================
    // FILE SELECTION, DRAG-AND-DROP & UI FIX
    // =========================================================================
    $dropzone.on('click', function (e) {
        if ($(e.target).closest('button, a').length === 0) {
            $fileInput.trigger('click');
        }
    });

    $fileInput.on('click', function (e) {
        e.stopPropagation();
    });

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
        e.preventDefault();
        e.stopPropagation();
        resetFileInput();
    });

    $btnChangeFile.on('click', function (e) {
        e.preventDefault();
        e.stopPropagation();
        $fileInput.trigger('click');
    });

    function resetFileInput() {
        $fileInput.val('');
        $dropzoneInitial.show();
        $selectedFileCard.hide();
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
        $selectedFilenameDisplay.text(file.name);
        $selectedFilesizeDisplay.text(`${sizeKb} KB`);

        // Visual fix: Hide initial state, show file info card
        $dropzoneInitial.hide();
        $selectedFileCard.show();

        $btnValidate.prop('disabled', false);
    }

    // =========================================================================
    // STEP 1: VALIDATE ACTION (LOADING STATE)
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
    // STEP 2: RENDER PREVIEW STATE & STATS
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

        // Pre-populate Step 3 Pre-Import Summary & Safeguards
        const invalidCompanyRows = Number(stats.invalid_company || 0);
        const totalToImport = validRows + warningRows + invalidCompanyRows;
        $('#res-step3-valid-count').text(validRows.toLocaleString());
        $('#res-step3-warning-count').text(warningRows.toLocaleString());
        if (invalidCompanyRows > 0) {
            $('#res-step3-invalid-company-count').text(invalidCompanyRows.toLocaleString());
            $('#res-step3-invalid-company-eligible-row').show();
        } else {
            $('#res-step3-invalid-company-eligible-row').hide();
        }
        $('#res-step3-total-to-import').text(totalToImport.toLocaleString() + ' Records');

        const invalidCompanySkipped = 0;
        const totalNotImported = invalidRows + duplicateRows + invalidCompanySkipped;
        $('#res-step3-invalid-count').text(invalidRows.toLocaleString());
        $('#res-step3-invalid-company-skipped').text(invalidCompanySkipped.toLocaleString());
        $('#res-step3-duplicate-count').text(duplicateRows.toLocaleString());
        $('#res-step3-total-not-imported').text(totalNotImported.toLocaleString() + ' Records');
        $('#btn-confirm-import-count').text(totalToImport.toLocaleString());

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

        // Check if importable
        const importableCount = validRows + warningRows;
        if (importableCount > 0 && isHeaderValid) {
            $('#btn-next-to-step3').prop('disabled', false);
            $('#btn-action-confirm-import').prop('disabled', false);
        } else {
            $('#btn-next-to-step3').prop('disabled', true);
            $('#btn-action-confirm-import').prop('disabled', true);
        }

        renderTable();
        switchPanel('preview');
    }

    function renderHeaderCheckCard(headerCheck) {
        if (!headerCheck) {
            $('#preview-header-check-card').hide();
            return;
        }

        const validCount = Number(headerCheck.valid_headers_count || 0);
        const matchedCount = Number(headerCheck.matched_aliases_count || 0);
        const issuesCount = Number(headerCheck.issues_count || 0);
        const totalDetected = Number(headerCheck.detected_columns_count || 0);
        const expectedCount = Number(headerCheck.expected_columns_count || 44);

        $('#header-check-subtitle').text(`${expectedCount} Expected Columns • ${totalDetected} Detected Columns`);
        $('#badge-header-valid').text(`✓ ${validCount} Valid`);

        if (matchedCount > 0) {
            $('#badge-header-matched').text(`⚠ ${matchedCount} Auto-Matched`).show();
        } else {
            $('#badge-header-matched').hide();
        }

        if (issuesCount > 0) {
            $('#badge-header-invalid').text(`✕ ${issuesCount} Issues Found`).show();
        } else {
            $('#badge-header-invalid').hide();
        }

        const issues = headerCheck.issues || [];
        if (issues.length > 0) {
            let issuesHtml = '<div class="alert alert-warning py-2 px-3 mb-0 rounded-3"><ul class="mb-0 ps-3">';
            issues.forEach(function (iss) {
                issuesHtml += `<li><strong>${escapeHtml(iss.header || 'Header')}:</strong> ${escapeHtml(iss.message || '')}</li>`;
            });
            issuesHtml += '</ul></div>';
            $('#header-check-issues-container').html(issuesHtml);
            $('#header-check-details').show();
        } else {
            $('#header-check-details').hide();
        }

        $('#preview-header-check-card').show();
    }

    function renderStateBanner(state, total, valid, warning, duplicate, invalid) {
        const $banner = $('#preview-state-banner');
        let html = '';

        if (state === 'NO_NEW_RECORDS') {
            html = `
                <div class="alert alert-warning border-0 rounded-3 p-3">
                    <div class="d-flex align-items-center gap-3">
                        <div class="avatar rounded-circle bg-warning text-white flex-shrink-0">
                            <i class="ti ti-alert-circle fs-2"></i>
                        </div>
                        <div>
                            <h5 class="fw-bold mb-1">No New Records to Import</h5>
                            <p class="mb-0 small">All ${total} records in the file are duplicates or already enrolled in OWL LMS. No new records will be imported.</p>
                        </div>
                    </div>
                </div>`;
        } else if (state === 'DATA_ERROR' || state === 'HEADER_ERROR') {
            html = `
                <div class="alert alert-danger border-0 rounded-3 p-3">
                    <div class="d-flex align-items-center gap-3">
                        <div class="avatar rounded-circle bg-danger text-white flex-shrink-0">
                            <i class="ti ti-circle-x fs-2"></i>
                        </div>
                        <div>
                            <h5 class="fw-bold mb-1">Validation Errors Found</h5>
                            <p class="mb-0 small">${invalid} invalid record(s) contain formatting or mandatory field errors. Please correct the spreadsheet before confirming.</p>
                        </div>
                    </div>
                </div>`;
        } else if (state === 'READY_WITH_DUPLICATES') {
            html = `
                <div class="alert alert-info border-0 rounded-3 p-3" style="background-color: #f0fdf4; color: #166534;">
                    <div class="d-flex align-items-center gap-3">
                        <div class="avatar rounded-circle bg-success text-white flex-shrink-0">
                            <i class="ti ti-circle-check fs-2"></i>
                        </div>
                        <div>
                            <h5 class="fw-bold mb-1">Spreadsheet Validated Successfully</h5>
                            <p class="mb-0 small">${valid} valid record(s) ready to import. ${duplicate} duplicate record(s) will be safely skipped.</p>
                        </div>
                    </div>
                </div>`;
        } else {
            html = `
                <div class="alert alert-success border-0 rounded-3 p-3" style="background-color: #ecfdf5; color: #065f46;">
                    <div class="d-flex align-items-center gap-3">
                        <div class="avatar rounded-circle bg-success text-white flex-shrink-0">
                            <i class="ti ti-circle-check fs-2"></i>
                        </div>
                        <div>
                            <h5 class="fw-bold mb-1">All Records Validated</h5>
                            <p class="mb-0 small">All ${valid} records meet Non-PTKP OWL LMS schema rules and are ready to be imported.</p>
                        </div>
                    </div>
                </div>`;
        }

        $banner.html(html).show();
    }

    // =========================================================================
    // TABLE RENDERING & FILTERING
    // =========================================================================
    function getFilteredRows() {
        return previewRowsData.filter(function (row) {
            if (currentFilter !== 'all' && row.status !== currentFilter) {
                return false;
            }
            if (searchQuery) {
                const q = searchQuery.toLowerCase();
                const d = row.data || {};
                const match = (
                    String(d.employee_id || '').toLowerCase().includes(q) ||
                    String(d.name || '').toLowerCase().includes(q) ||
                    String(d.course_name || '').toLowerCase().includes(q) ||
                    String(d.pic || '').toLowerCase().includes(q) ||
                    String(d.company || '').toLowerCase().includes(q)
                );
                if (!match) return false;
            }
            return true;
        });
    }

    function renderTable() {
        const filtered = getFilteredRows();
        const totalFiltered = filtered.length;
        const totalPages = Math.ceil(totalFiltered / pageSize) || 1;

        if (currentPage > totalPages) currentPage = totalPages;
        if (currentPage < 1) currentPage = 1;

        const startIdx = (currentPage - 1) * pageSize;
        const endIdx = Math.min(startIdx + pageSize, totalFiltered);
        const pagedRows = filtered.slice(startIdx, endIdx);

        $('#preview-visible-count').text(totalFiltered.toLocaleString());
        $('#preview-total-count').text(previewRowsData.length.toLocaleString());

        if (totalPages > 1) {
            $('#page-current').text(currentPage);
            $('#page-total').text(totalPages);
            $('#btn-page-prev').prop('disabled', currentPage <= 1);
            $('#btn-page-next').prop('disabled', currentPage >= totalPages);
            $('#preview-pagination-container').show();
        } else {
            $('#preview-pagination-container').hide();
        }

        const $tbody = $('#preview-main-tbody');
        $tbody.empty();

        if (pagedRows.length === 0) {
            $tbody.append(`
                <tr>
                    <td colspan="10" class="text-center py-5 text-muted">
                        <i class="ti ti-inbox fs-1 d-block mb-2 text-secondary"></i>
                        No records match the active filter criteria.
                    </td>
                </tr>
            `);
            return;
        }

        pagedRows.forEach(function (row, localIdx) {
            const globalIdx = previewRowsData.indexOf(row);
            const d = row.data || {};

            let badgeClass = 'badge-ptkp-valid';
            let badgeLabel = 'VALID';
            let badgeIcon = 'ti-check';

            if (row.status === 'warning') {
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
                '<td>' + (row.messages && row.messages.length > 0 ? ('<div class="ptkp-row-msg text-muted">' + escapeHtml(row.messages[0]) + (row.messages.length > 1 ? ' <span class="badge bg-light text-secondary border">+' + (row.messages.length - 1) + '</span>' : '') + '</div>') : '<span class="text-success small"><i class="ti ti-check me-1"></i>Ready to import</span>') + '</td>',
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
    // STEP 2 NAVIGATION: Cancel, Back, Next
    // =========================================================================
    $('#btn-action-cancel').on('click', function () {
        if (!confirm('Are you sure you want to cancel this import session? Temporary files will be removed.')) return;
        if (importToken) {
            $.ajax({
                url: "{{ route('manage.record.offline.import.cancel') }}",
                type: 'POST',
                data: { token: importToken, _token: csrfToken },
                headers: { 'X-CSRF-TOKEN': csrfToken },
                complete: function () {
                    window.location.href = "{{ route('manage.record.offline') }}";
                }
            });
        } else {
            window.location.href = "{{ route('manage.record.offline') }}";
        }
    });

    // Back to Step 1 without losing file or validation data
    $('#btn-back-to-step1').on('click', function () {
        switchPanel('upload');
        updateHeaderActions(1);
    });

    // Next to Step 3
    $('#btn-next-to-step3').on('click', function () {
        switchPanel('confirm');
        updateHeaderActions(3);
    });

    // =========================================================================
    // STEP 3: BACK & CONFIRM IMPORT
    // =========================================================================
    $('#btn-back-to-step2').on('click', function () {
        switchPanel('preview');
        updateHeaderActions(2);
    });

    $('#btn-action-confirm-import').on('click', function () {
        if (!importToken) return;

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
                    const total = Number(r.rows_processed ?? r.total_rows_processed ?? 0);
                    const imported = Number(r.imported ?? 0);
                    const warnings = Number(r.warnings_imported ?? 0);
                    const duplicates = Number(r.skipped_duplicates ?? 0);
                    const invalid = Number(r.invalid ?? r.skipped_invalid ?? 0);
                    const invalidCompany = Number(r.invalid_company_imported ?? 0);
                    const created = Number(r.trainings_created ?? 0);
                    const reused = Number(r.trainings_reused ?? 0);
                    const totalSkipped = duplicates + invalid;

                    // 1. Success Banner Text
                    if (totalSkipped > 0) {
                        $('#res-header-title').text('Import Completed');
                        $('#res-header-desc').text('Your training records have been processed. Please review the import results below.');
                    } else {
                        $('#res-header-title').text('Import Completed');
                        $('#res-header-desc').text('Your training records have been processed successfully. All eligible records have been imported.');
                    }

                    // 2. Success Rate Calculation
                    let rate = '0.0';
                    if (total > 0) {
                        rate = ((imported / total) * 100).toFixed(1);
                    }
                    $('#res-rate-percentage').text(rate + '%');
                    $('#res-rate-progress-bar').css('width', rate + '%').attr('aria-valuenow', rate);
                    $('#res-rate-imported-count').text(imported.toLocaleString());
                    $('#res-rate-skipped-count').text(totalSkipped.toLocaleString());

                    // 3. 6 Summary Cards
                    $('#res-total-processed').text(total.toLocaleString());
                    $('#res-imported-count').text(imported.toLocaleString());
                    $('#res-warnings-imported').text(warnings.toLocaleString());
                    $('#res-duplicates-skipped').text(duplicates.toLocaleString());
                    $('#res-invalid-skipped').text(invalid.toLocaleString());
                    $('#res-invalid-company-imported').text(invalidCompany.toLocaleString());
                    $('#res-trainings-created').text(created.toLocaleString());
                    $('#res-trainings-reused').text(reused.toLocaleString());

                    switchPanel('result');
                } else {
                    switchPanel('confirm');
                    $btn.prop('disabled', false);
                    alert(res.message || 'Import execution failed.');
                }
            },
            error: function (xhr) {
                switchPanel('confirm');
                $btn.prop('disabled', false);
                let errMsg = 'Failed to execute import into database.';
                if (xhr.responseJSON && xhr.responseJSON.message) {
                    errMsg = xhr.responseJSON.message;
                }
                alert(errMsg);
            }
        });
    });

    // Import Another button
    $('#btn-import-another').on('click', function () {
        importCompleted = false;
        importToken = null;
        previewRowsData = [];
        resetFileInput();
        switchPanel('upload');
        updateHeaderActions(1);
    });

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
