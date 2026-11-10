@extends('layouts.sidebar-master')

@section('title', 'Import Non-PTKP Training Records — Import Non-PTKP Data — OWL LMS')

@section('style')
<link rel="stylesheet" href="{{ asset('css/manage/non-ptkp-import.css') }}">
@endsection

@section('content')
<div class="page-body ptkp-import-page non-ptkp-import-page py-3">
    <div class="container-xl">
        <!-- BREADCRUMB / BACK LINK -->
        <div class="d-flex align-items-center justify-content-between flex-wrap gap-2 mb-2.5">
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb mb-0" style="font-size: 11.5px;">
                    <li class="breadcrumb-item"><a href="{{ route('manage.dashboard') }}" class="text-decoration-none">Manage Record</a></li>
                    <li class="breadcrumb-item"><a href="{{ route('manage.record.offline') }}" class="text-decoration-none">Offline Record</a></li>
                    <li class="breadcrumb-item active" aria-current="page">Import Non-PTKP Data</li>
                </ol>
            </nav>
            <a href="{{ route('manage.record.offline') }}" class="btn-back-ptkp d-inline-flex align-items-center gap-1.5 text-decoration-none fw-medium" id="btn-top-back" style="font-size: 11.5px;">
                <i class="ti ti-arrow-left"></i>
                <span>Back to Offline Records</span>
            </a>
        </div>

        <!-- PAGE TITLE CARD -->
        <div class="card border-0 shadow-sm p-3 mb-3 rounded-3 bg-white">
            <div class="d-flex align-items-center justify-content-between flex-wrap gap-3">
                <div class="d-flex align-items-center gap-2.5">
                    <div class="rounded-3 d-flex align-items-center justify-content-center flex-shrink-0"
                         style="width: 44px; height: 44px; background-color: #fef3c7; color: #d97706; border-radius: 10px;">
                        <i class="ti ti-file-spreadsheet fs-2"></i>
                    </div>
                    <div>
                        <h2 class="fw-bold mb-0.5 text-dark" style="font-size: 1.15rem;">Import Non-PTKP Training Records</h2>
                        <p class="text-muted mb-0" style="font-size: 11.5px;">Upload and import non-PTKP offline training records into KP-OWL. Employee information will be matched automatically.</p>
                    </div>
                </div>
                <div class="d-flex align-items-center gap-2" id="header-action-container">
                    <!-- Download Template: Only Step 1 -->
                    <a href="{{ route('manage.record.offline.import-non-ptkp.template') }}"
                       data-template-alt="{{ route('manage.record.offline.import.template') }}"
                       class="btn btn-outline-primary rounded-pill d-inline-flex align-items-center gap-1.5 px-3 py-1.5"
                       id="btn-download-non-ptkp-template"
                       style="font-size: 11.5px;"
                       title="Download Template (.xlsx)">
                        <i class="ti ti-download fs-3"></i>
                        <span>Download Template (.xlsx)</span>
                    </a>
                    <!-- Import History: Only Step 3 & Result -->
                    <a href="{{ route('manage.record.offline.import-history.index') }}"
                       class="btn btn-outline-secondary rounded-pill d-inline-flex align-items-center gap-1.5 px-3 py-1.5 d-none"
                       id="btn-non-ptkp-history"
                       style="font-size: 11.5px;"
                       title="View Import History">
                        <i class="ti ti-history fs-3"></i>
                        <span>Import History</span>
                    </a>
                </div>
            </div>
        </div>

        <!-- EXACTLY 3-STEP PROCESS INDICATOR -->
        <div class="card border-0 shadow-sm py-2 px-3 mb-3 rounded-3 bg-white ptkp-stepper-card stepper-card">
            <div class="ptkp-steps">
                <!-- Step 1 -->
                <div class="ptkp-step-item active stepper-item-node stepper-item" id="stepper-step-1" data-step="1">
                    <div class="ptkp-step-bubble stepper-circle">
                        <span class="step-num stepper-num">1</span>
                        <i class="ti ti-check step-check stepper-check"></i>
                    </div>
                    <div class="ptkp-step-title stepper-label">Upload File</div>
                </div>
                <div class="ptkp-step-line stepper-connector" id="stepper-line-1"></div>
                <!-- Step 2 -->
                <div class="ptkp-step-item stepper-item-node stepper-item" id="stepper-step-2" data-step="2">
                    <div class="ptkp-step-bubble stepper-circle">
                        <span class="step-num stepper-num">2</span>
                        <i class="ti ti-check step-check stepper-check"></i>
                    </div>
                    <div class="ptkp-step-title stepper-label">Validation & Review</div>
                </div>
                <div class="ptkp-step-line stepper-connector" id="stepper-line-2"></div>
                <!-- Step 3 -->
                <div class="ptkp-step-item stepper-item-node stepper-item" id="stepper-step-3" data-step="3">
                    <div class="ptkp-step-bubble stepper-circle">
                        <span class="step-num stepper-num">3</span>
                        <i class="ti ti-check step-check stepper-check"></i>
                    </div>
                    <div class="ptkp-step-title stepper-label">Import Records</div>
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

                <!-- Dropzone Area -->
                <div class="ptkp-dropzone upload-dropzone p-5 text-center my-3 position-relative" id="dropzone-container">
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
                    <div class="ptkp-file-card selected-file-info-card p-4 text-start position-relative" id="selected-file-card" style="display: none; cursor: default;">
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

                <!-- Upload Error Alert -->
                <div class="alert alert-danger border-0 rounded-3 small mb-3 py-2 px-3" id="upload-error-box" style="display: none;">
                    <div class="d-flex align-items-start gap-2">
                        <i class="ti ti-alert-triangle fs-3 flex-shrink-0 mt-0.5"></i>
                        <div id="upload-error-message">File validation error occurred.</div>
                    </div>
                </div>

                <!-- Guidelines Box -->
                <div class="alert alert-info border-0 rounded-3 small mb-4 guidelines-alert" style="background-color: #f0f9ff; color: #0369a1;">
                    <div class="d-flex align-items-start gap-2">
                        <i class="ti ti-info-circle fs-3 flex-shrink-0 mt-0.5"></i>
                        <div>
                            <strong class="d-block mb-1">Non-PTKP OWL Spreadsheet Guidelines:</strong>
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

                <!-- Action Button -->
                <div class="d-flex align-items-center justify-content-end pt-3 border-top">
                    <button type="button" id="btn-validate-upload" class="btn btn-primary rounded-3 px-4 d-inline-flex align-items-center gap-2" style="background-color: #1e3a8a; border-color: #1e3a8a;" disabled>
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
                <h4 class="fw-bold text-dark mt-3 mb-1" id="loading-state-title">Validating Spreadsheet...</h4>
                <p class="text-muted small mb-0" id="loading-state-subtitle">Reading columns, resolving header groups, and checking existing records...</p>
            </div>
        </div>

        <!-- =================================================================== -->
        <!-- STEP 2: VALIDATION & REVIEW                                         -->
        <!-- =================================================================== -->
        <div class="card border-0 shadow-sm rounded-3 mb-3 panel-import-state" id="panel-state-preview" style="display: none;">
            <div class="card-header bg-white border-bottom py-2.5 px-3 px-md-3.5 rounded-top-3">
                <div class="d-flex align-items-center justify-content-between flex-wrap gap-2">
                    <div class="d-flex align-items-center gap-2">
                        <span class="badge bg-primary text-white rounded-circle d-inline-flex align-items-center justify-content-center" style="width: 22px; height: 22px; font-size: 0.72rem; background-color: #1e3a8a !important;">2</span>
                        <div>
                            <h4 class="card-title fw-semibold text-dark mb-0" style="font-size: 14px;">Validation &amp; Review</h4>
                            <div class="text-muted extra-small" style="font-size: 11px;">Review validated training records before importing them into KP-OWL.</div>
                        </div>
                        <span class="badge bg-light text-secondary border px-2 py-0.5 ms-2" style="font-size: 10.5px;" id="preview-filename-badge">-</span>
                    </div>
                </div>
            </div>

            <div class="card-body p-3 p-md-3.5">
                <!-- Header Check Diagnostic Card -->
                <div class="card border rounded-3 mb-2.5 shadow-none" id="preview-header-check-card" style="display: none; background-color: #f8fafc; border-color: #e2e8f0 !important;">
                    <div class="card-body p-3">
                        <div class="d-flex align-items-center justify-content-between flex-wrap gap-2">
                            <div class="d-flex align-items-center gap-2.5">
                                <div class="avatar avatar-sm rounded-circle d-flex align-items-center justify-content-center flex-shrink-0" style="background-color: #e0f2fe; color: #0284c7; width: 34px; height: 34px;">
                                    <i class="ti ti-layout-grid fs-3"></i>
                                </div>
                                <div>
                                    <h6 class="fw-bold mb-0 text-dark" style="font-size: 12px;">Template Header Validation</h6>
                                    <div class="text-muted extra-small" id="header-check-subtitle" style="font-size: 11px;">44 Expected Columns &bull; 44 Detected Columns</div>
                                </div>
                            </div>
                            <div class="d-flex align-items-center gap-2 flex-wrap" id="header-check-badges">
                                <span class="badge bg-success-lt px-2 py-0.5" id="badge-header-valid" style="font-size: 10.5px;">✓ 44 Valid</span>
                                <span class="badge bg-warning-lt px-2 py-0.5" id="badge-header-matched" style="display: none; font-size: 10.5px;">⚠ 0 Auto-Matched</span>
                                <span class="badge bg-danger-lt px-2 py-0.5" id="badge-header-invalid" style="display: none; font-size: 10.5px;">✕ 0 Needs Correction</span>
                            </div>
                        </div>

                        <!-- Diagnostic details list -->
                        <div id="header-check-details" style="display: none;" class="mt-2 pt-2 border-top">
                            <div id="header-check-issues-container" class="small"></div>
                        </div>
                    </div>
                </div>

                <!-- PREVIEW STATE BANNER / READINESS BANNER -->
                <div id="preview-state-banner" class="mb-2.5" style="display: none;"></div>

                <!-- NO NEW RECORDS TO IMPORT ALERT BANNER -->
                <div class="alert alert-warning border-0 rounded-3 mb-2.5 py-2 px-3" id="ptkp-no-new-records-alert" style="display: none; background-color: #fffbeb; color: #92400e;">
                    <div class="d-flex align-items-center gap-2.5">
                        <div class="avatar rounded-circle bg-warning text-white flex-shrink-0" style="width: 32px; height: 32px; font-size: 14px;">
                            <i class="ti ti-alert-circle"></i>
                        </div>
                        <div>
                            <h5 class="fw-bold mb-0" style="font-size: 13px;">No New Records to Import</h5>
                            <p class="mb-0 text-muted" id="ptkp-no-new-records-desc" style="font-size: 11px;">All records already exist in the database. No new records will be imported.</p>
                        </div>
                    </div>
                </div>

                <!-- 6 CLICKABLE KPI SUMMARY STAT CARDS (QUICK FILTER) + TRAINING EVENTS BADGE -->
                <div class="row g-2 mb-2.5" id="ptkp-summary-cards-container">
                    <!-- Card 1: Total Rows -->
                    <div class="col-6 col-md-2">
                        <div class="card border p-2 rounded-3 text-center h-100 ptkp-kpi-card ptkp-summary-filter-card ptkp-kpi-total active stat-box"
                             data-filter="all" role="button" tabindex="0" title="Total spreadsheet rows read">
                            <div class="d-flex align-items-center justify-content-center gap-1 small fw-semibold mb-1 ptkp-kpi-label">
                                <span>TOTAL ROWS</span>
                                <span class="ptkp-info-icon" data-bs-toggle="tooltip" data-bs-html="true" data-bs-placement="top" data-bs-custom-class="ptkp-status-tooltip ptkp-validation-tooltip"
                                      data-tooltip-id="ptkp-tt-content-total"
                                      tabindex="0" role="button" aria-label="Explanation of Total Rows">
                                    <i class="ti ti-info-circle"></i>
                                </span>
                            </div>
                            <div class="h3 fw-bold mb-0 ptkp-kpi-count" id="stat-total-rows">0</div>
                        </div>
                    </div>

                    <!-- Card 2: VALID -->
                    <div class="col-6 col-md-2">
                        <div class="card border p-2 rounded-3 text-center h-100 ptkp-kpi-card ptkp-summary-filter-card ptkp-kpi-valid stat-box"
                             data-filter="valid" role="button" tabindex="0" title="Valid records ready to import">
                            <div class="d-flex align-items-center justify-content-center gap-1 small fw-semibold mb-1 ptkp-kpi-label">
                                <span>VALID</span>
                                <span class="ptkp-info-icon" data-bs-toggle="tooltip" data-bs-html="true" data-bs-placement="top" data-bs-custom-class="ptkp-status-tooltip ptkp-validation-tooltip"
                                      data-tooltip-id="ptkp-tt-content-valid"
                                      tabindex="0" role="button" aria-label="Explanation of Valid status">
                                    <i class="ti ti-info-circle"></i>
                                </span>
                            </div>
                            <div class="h3 fw-bold mb-0 ptkp-kpi-count" id="stat-valid-rows">0</div>
                        </div>
                    </div>

                    <!-- Card 3: WARNING -->
                    <div class="col-6 col-md-2">
                        <div class="card border p-2 rounded-3 text-center h-100 ptkp-kpi-card ptkp-summary-filter-card ptkp-kpi-warning stat-box"
                             data-filter="warning" role="button" tabindex="0" title="Valid records with nonfatal warnings">
                            <div class="d-flex align-items-center justify-content-center gap-1 small fw-semibold mb-1 ptkp-kpi-label">
                                <span>WARNING</span>
                                <span class="ptkp-info-icon" data-bs-toggle="tooltip" data-bs-html="true" data-bs-placement="top" data-bs-custom-class="ptkp-status-tooltip ptkp-validation-tooltip"
                                      data-tooltip-id="ptkp-tt-content-warning"
                                      tabindex="0" role="button" aria-label="Explanation of Warning status">
                                    <i class="ti ti-info-circle"></i>
                                </span>
                            </div>
                            <div class="h3 fw-bold mb-0 ptkp-kpi-count" id="stat-warning-rows">0</div>
                        </div>
                    </div>

                    <!-- Card 4: DUPLICATE -->
                    <div class="col-6 col-md-2">
                        <div class="card border p-2 rounded-3 text-center h-100 ptkp-kpi-card ptkp-summary-filter-card ptkp-kpi-duplicate stat-box"
                             data-filter="duplicate" role="button" tabindex="0" title="Existing records or duplicates that will be skipped">
                            <div class="d-flex align-items-center justify-content-center gap-1 small fw-semibold mb-1 ptkp-kpi-label">
                                <span>DUPLICATE</span>
                                <span class="ptkp-info-icon" data-bs-toggle="tooltip" data-bs-html="true" data-bs-placement="top" data-bs-custom-class="ptkp-status-tooltip ptkp-validation-tooltip"
                                      data-tooltip-id="ptkp-tt-content-duplicate"
                                      tabindex="0" role="button" aria-label="Explanation of Duplicate status">
                                    <i class="ti ti-info-circle"></i>
                                </span>
                            </div>
                            <div class="h3 fw-bold mb-0 ptkp-kpi-count" id="stat-duplicate-rows">0</div>
                        </div>
                    </div>

                    <!-- Card 5: INVALID ID & COURSE TITLE -->
                    <div class="col-6 col-md-2">
                        <div class="card border p-2 rounded-3 text-center h-100 ptkp-kpi-card ptkp-summary-filter-card ptkp-kpi-invalid stat-box"
                             data-filter="invalid" role="button" tabindex="0" title="Records with fatal validation errors">
                            <div class="d-flex align-items-center justify-content-center gap-1 small fw-semibold mb-1 ptkp-kpi-label">
                                <span title="Invalid Employee ID or Course Title">INVALID ID &amp; COURSE TITLE</span>
                                <span class="ptkp-info-icon" data-bs-toggle="tooltip" data-bs-html="true" data-bs-placement="top" data-bs-custom-class="ptkp-status-tooltip ptkp-validation-tooltip"
                                      data-tooltip-id="ptkp-tt-content-invalid"
                                      tabindex="0" role="button" aria-label="Explanation of Invalid status">
                                    <i class="ti ti-info-circle"></i>
                                </span>
                            </div>
                            <div class="h3 fw-bold mb-0 ptkp-kpi-count" id="stat-invalid-rows">0</div>
                            <div class="ptkp-kpi-subinfo extra-small mt-0.5" id="ptkp-kpi-invalid-subinfo" style="display: none;">
                                <span class="ptkp-sub-pill" data-subfilter="invalid_id" title="Filter records with Invalid ID">ID: <span id="ptkp-kpi-invalid-id-count">0</span></span>
                                <span>&bull;</span>
                                <span class="ptkp-sub-pill" data-subfilter="invalid_course" title="Filter records with Invalid Course Title">Course: <span id="ptkp-kpi-invalid-course-count">0</span></span>
                            </div>
                        </div>
                    </div>

                    <!-- Card 6: INVALID COMPANY -->
                    <div class="col-6 col-md-2">
                        <div class="card border p-2 rounded-3 text-center h-100 ptkp-kpi-card ptkp-summary-filter-card ptkp-kpi-invalid-company stat-box"
                             data-filter="invalid_company" role="button" tabindex="0" title="Records with empty company (will be imported; needs review)">
                            <div class="d-flex align-items-center justify-content-center gap-1 small fw-semibold mb-1 ptkp-kpi-label">
                                <span>INVALID COMPANY</span>
                                <span class="ptkp-info-icon" data-bs-toggle="tooltip" data-bs-html="true" data-bs-placement="top" data-bs-custom-class="ptkp-status-tooltip ptkp-validation-tooltip"
                                      data-tooltip-id="ptkp-tt-content-invalid-company"
                                      tabindex="0" role="button" aria-label="Explanation of Invalid Company status">
                                    <i class="ti ti-info-circle"></i>
                                </span>
                            </div>
                            <div class="h3 fw-bold mb-0 ptkp-kpi-count" id="stat-invalid-company-rows">0</div>
                        </div>
                    </div>
                </div>

                <!-- NON-PTKP TRAINING EVENTS IDENTIFIED BADGE -->
                <div class="d-flex align-items-center justify-content-between p-2 px-3 mb-2.5 rounded-3 border" style="background-color: #f0fdfa; border-color: #ccfbf1 !important;">
                    <div class="d-flex align-items-center gap-2">
                        <span class="avatar avatar-xs rounded-circle" style="background-color: #ccfbf1; color: #0f766e; width: 26px; height: 26px;">
                            <i class="ti ti-calendar-event fs-3"></i>
                        </span>
                        <span class="fw-semibold text-dark" style="font-size: 11.5px;">Training Sessions Identified:</span>
                        <span class="badge bg-teal text-white fw-bold px-2 py-0.5" id="stat-training-events" title="Total unique training sessions identified" style="font-size: 11px; background-color: #0f766e !important;">0</span>
                    </div>
                    <span class="text-muted extra-small" style="font-size: 10.5px;">Grouped automatically based on course, batch, dates, type, and venue.</span>
                </div>

                <!-- PRE-IMPORT DATA COMPARISON (FOR COMPATIBILITY WITH AUDIT TEST SUITE) -->
                <div class="card border rounded-3 mb-2.5 shadow-none comparison-summary-card d-none" style="background-color: #fafbfc; border-color: #e2e8f0 !important;">
                    <div class="card-body p-3">
                        <div class="d-flex align-items-center justify-content-between flex-wrap gap-2 mb-2">
                            <div class="d-flex align-items-center gap-2">
                                <i class="ti ti-scale text-primary fs-3"></i>
                                <h5 class="fw-bold text-dark mb-0" style="font-size: 12.5px;">Pre-Import Data Comparison</h5>
                            </div>
                            <span class="badge bg-secondary-lt small">Database Verification Result</span>
                        </div>
                        <div class="row g-2">
                            <div class="col-md-6">
                                <div class="p-2 rounded-2 bg-white border">
                                    <div class="d-flex align-items-center justify-content-between">
                                        <span class="text-muted extra-small fw-medium">New Records (Ready to Import)</span>
                                        <span class="badge bg-success-lt fw-bold" id="comp-new-records">0</span>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="p-2 rounded-2 bg-white border">
                                    <div class="d-flex align-items-center justify-content-between">
                                        <span class="text-muted extra-small fw-medium">Duplicate Records (Skipped)</span>
                                        <span class="badge bg-purple-lt fw-bold" id="comp-duplicate-records">0</span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- VALIDATION STATUS EXPLANATION TOOLTIP TEMPLATES -->
                <div id="ptkp-tooltip-templates" class="d-none" aria-hidden="true">
                    <div id="ptkp-tt-content-total">
                        <div class="ptkp-tt-box ptkp-tt-minimal text-start">
                            <div class="ptkp-tt-head fw-bold mb-1">Total Uploaded Records</div>
                            <div class="ptkp-tt-desc mb-0">The total number of data rows detected in the uploaded Non-PTKP Excel file, excluding header rows. Total dataset evaluated.</div>
                        </div>
                    </div>
                    <div id="ptkp-tt-content-valid">
                        <div class="ptkp-tt-box ptkp-tt-minimal text-start">
                            <div class="ptkp-tt-head fw-bold mb-1">Valid Records</div>
                            <div class="ptkp-tt-desc mb-0">These records have passed all Non-PTKP validation checks and are ready to be imported into the system.</div>
                        </div>
                    </div>
                    <div id="ptkp-tt-content-warning">
                        <div class="ptkp-tt-box ptkp-tt-minimal text-start">
                            <div class="ptkp-tt-head fw-bold mb-1">Warning Records</div>
                            <div class="ptkp-tt-desc mb-0">Records with nonfatal warnings (e.g. blank optional dates or participant not yet in users table). These records are non-blocking and will still be imported.</div>
                        </div>
                    </div>
                    <div id="ptkp-tt-content-duplicate">
                        <div class="ptkp-tt-box ptkp-tt-minimal text-start">
                            <div class="ptkp-tt-head fw-bold mb-1">Duplicate Records</div>
                            <div class="ptkp-tt-desc mb-0">These records already exist in the database or are duplicated within the file. They will be safely skipped without modifying existing data.</div>
                        </div>
                    </div>
                    <div id="ptkp-tt-content-invalid">
                        <div class="ptkp-tt-box ptkp-tt-minimal text-start">
                            <div class="ptkp-tt-head fw-bold mb-1">Invalid Records (Invalid ID &amp; Course Title)</div>
                            <div class="ptkp-tt-desc mb-0">
                                These records failed mandatory validation and cannot be imported until corrected:
                                <ul class="ps-3 mb-0 mt-1" style="font-size: 0.76rem;">
                                    <li><strong>INVALID ID:</strong> Employee ID is empty or invalid.</li>
                                    <li><strong>INVALID COURSE TITLE:</strong> Course Name is empty.</li>
                                </ul>
                            </div>
                        </div>
                    </div>
                    <div id="ptkp-tt-content-invalid-company">
                        <div class="ptkp-tt-box ptkp-tt-minimal text-start">
                            <div class="ptkp-tt-head fw-bold mb-1">Invalid Company Records</div>
                            <div class="ptkp-tt-desc mb-0">Records where company is missing or needs review. In Non-PTKP, these are imported with a review notice.</div>
                        </div>
                    </div>
                </div>

                <!-- COMBINED SEARCH FIELDS & CONTROLS -->
                <div class="row g-2 mb-2.5 ptkp-filter-bar align-items-center">
                    <div class="col-12 col-md-3">
                        <div class="input-icon">
                            <span class="input-icon-addon"><i class="ti ti-id"></i></span>
                            <input type="text" id="ptkp-search-empid" class="form-control form-control-sm" placeholder="Filter Employee ID...">
                        </div>
                    </div>
                    <div class="col-12 col-md-3">
                        <div class="input-icon">
                            <span class="input-icon-addon"><i class="ti ti-user"></i></span>
                            <input type="text" id="ptkp-search-name" class="form-control form-control-sm" placeholder="Filter Employee Name...">
                        </div>
                    </div>
                    <div class="col-12 col-md-3">
                        <div class="input-icon">
                            <span class="input-icon-addon"><i class="ti ti-book"></i></span>
                            <input type="text" id="ptkp-search-course" class="form-control form-control-sm" placeholder="Filter Course Title...">
                        </div>
                    </div>
                    <div class="col-8 col-md-2">
                        <div class="input-icon">
                            <span class="input-icon-addon"><i class="ti ti-user-check"></i></span>
                            <input type="text" id="ptkp-search-pic" class="form-control form-control-sm" placeholder="Filter PIC...">
                        </div>
                    </div>
                    <div class="col-4 col-md-1">
                        <button type="button" id="ptkp-btn-reset-filters" class="btn btn-sm btn-outline-secondary w-100 d-inline-flex align-items-center justify-content-center gap-1" title="Reset all search filters">
                            <i class="ti ti-rotate-clockwise"></i>
                            <span>Reset</span>
                        </button>
                    </div>
                </div>

                <!-- PREVIEW TABLE CONTAINER WITH LOADING OVERLAY -->
                <div class="ptkp-table-wrapper position-relative mb-2.5">
                    <div class="ptkp-table-loading-overlay" id="ptkp-table-loading" style="display: none;">
                        <div class="d-flex flex-column align-items-center justify-content-center py-4">
                            <div class="spinner-border text-primary mb-1.5" style="width: 1.8rem; height: 1.8rem; color: #1e3a8a !important;" role="status"></div>
                            <span class="fw-semibold text-muted" id="ptkp-table-loading-text" style="font-size: 11px;">Filtering records...</span>
                        </div>
                    </div>
                    <table class="table table-hover align-middle mb-0 ptkp-table" id="preview-main-table">
                        <thead>
                            <tr>
                                <th style="width: 45px;" class="text-center">No</th>
                                <th style="width: 155px;">Status</th>
                                <th style="width: 115px;">Employee ID</th>
                                <th>Employee Name</th>
                                <th style="width: 125px;">Company</th>
                                <th>Course Title</th>
                                <th style="width: 85px;">Type</th>
                                <th style="width: 115px;">PIC</th>
                                <th style="min-width: 190px;">Validation Details</th>
                                <th style="width: 55px;" class="text-center">Action</th>
                            </tr>
                        </thead>
                        <tbody id="preview-main-tbody">
                            <!-- Dynamically populated via JS -->
                        </tbody>
                    </table>
                </div>

                <!-- SHOW ENTRIES & PAGINATION FOOTER -->
                <div class="d-flex align-items-center justify-content-between flex-wrap gap-2 mb-3 ptkp-pagination-wrapper">
                    <div class="d-flex align-items-center gap-2">
                        <label for="ptkp-page-size" class="text-muted mb-0" style="font-size: 11px;">Show:</label>
                        <select id="ptkp-page-size" class="form-select form-select-sm" style="width: 75px; height: 30px; font-size: 11px;">
                            <option value="15">15</option>
                            <option value="25" selected>25</option>
                            <option value="50">50</option>
                            <option value="100">100</option>
                        </select>
                        <div class="text-muted ms-2" id="ptkp-pagination-info" style="font-size: 11px;">
                            Showing 0 of 0 records
                        </div>
                    </div>
                    <nav aria-label="Page navigation">
                        <ul class="pagination pagination-sm mb-0" id="ptkp-pagination-nav"></ul>
                    </nav>
                </div>

                <!-- STEP 2 NAVIGATION BUTTONS: Cancel, Back, Next -->
                <div class="d-flex align-items-center justify-content-between flex-wrap gap-3 pt-2.5 border-top">
                    <div>
                        <button type="button" class="btn btn-sm btn-outline-danger rounded-3 px-3 py-1.5" id="btn-action-cancel" style="font-size: 11.5px;">
                            <i class="ti ti-x me-1"></i>Cancel
                        </button>
                    </div>
                    <div class="d-flex align-items-center gap-2">
                        <button type="button" class="btn btn-sm btn-outline-secondary rounded-3 px-3 py-1.5 d-inline-flex align-items-center gap-1.5" id="btn-back-to-step1" style="font-size: 11.5px;">
                            <i class="ti ti-arrow-left"></i>
                            <span>Back</span>
                        </button>
                        <button type="button" class="btn btn-sm btn-primary rounded-3 px-3.5 py-1.5 d-inline-flex align-items-center gap-1.5" id="btn-next-to-step3" style="background-color: #1e3a8a; border-color: #1e3a8a; font-size: 11.5px;">
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
        <div class="card border-0 shadow-sm rounded-3 mb-3 panel-import-state" id="panel-state-confirm" style="display: none;">
            <div class="card-header bg-white border-bottom py-2.5 px-3 px-md-3.5 rounded-top-3">
                <div class="d-flex align-items-center gap-2">
                    <span class="badge bg-primary text-white rounded-circle d-inline-flex align-items-center justify-content-center flex-shrink-0" style="width: 24px; height: 24px; font-size: 12px; font-weight: 700; background-color: #1e3a8a !important;">3</span>
                    <div>
                        <h4 class="card-title fw-semibold text-dark mb-0.5 ptkp-section-title" style="font-size: 15px; font-weight: 600;">Import Records</h4>
                        <div class="text-muted extra-small ptkp-supporting-text" style="font-size: 11.5px; font-weight: 400; line-height: 1.35;">Import validated training records into KP-OWL.</div>
                    </div>
                </div>
            </div>

            <div class="card-body p-3 p-md-3.5">
                <!-- Pre-Import Summary & Safeguards -->
                <div class="pre-import-summary-wrapper mb-3">
                    <!-- Section Header -->
                    <div class="d-flex align-items-center gap-2.5 mb-3">
                        <div class="rounded-3 d-flex align-items-center justify-content-center flex-shrink-0" style="width: 36px; height: 36px; background-color: #eff6ff; color: #1e3a8a; border: 1px solid #bfdbfe;">
                            <i class="ti ti-clipboard-check fs-2"></i>
                        </div>
                        <div>
                            <h5 class="fw-semibold text-dark mb-0.5 pre-import-section-title" style="font-size: 14px; font-weight: 600;">Pre-Import Summary &amp; Safeguards</h5>
                            <div class="text-muted extra-small pre-import-section-subtitle ptkp-supporting-text" style="font-size: 11.5px; font-weight: 400; line-height: 1.35;">Verify exact record counts and database safety guarantees before execution.</div>
                        </div>
                    </div>

                    <!-- Summary Panels (CSS Grid: Equal 50:50 Columns on Desktop) -->
                    <div class="pre-import-summary-grid mb-3">
                        <!-- Group A: Records to Be Imported (Left Card) -->
                        <div class="card border rounded-3 p-3 p-md-3.5 h-100 pre-import-card pre-import-card-eligible">
                            <div class="pre-import-header">
                                <span class="pre-import-header-icon" style="background-color: #1e3a8a;">
                                    <i class="ti ti-check"></i>
                                </span>
                                <div>
                                    <h5 class="fw-semibold text-dark mb-0.5" style="font-size: 13px; font-weight: 600;">Records to Be Imported</h5>
                                    <p class="text-muted mb-0" style="font-size: 11px; font-weight: 400; line-height: 1.35;">Records that passed validation and are eligible for import.</p>
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

                        <!-- Group B: Records Not to Be Imported (Right Card) -->
                        <div class="card border rounded-3 p-3 p-md-3.5 h-100 pre-import-card pre-import-card-skipped">
                            <div class="pre-import-header">
                                <span class="pre-import-header-icon" style="background-color: #dc2626;">
                                    <i class="ti ti-ban"></i>
                                </span>
                                <div>
                                    <h5 class="fw-semibold text-dark mb-0.5" style="font-size: 13px; font-weight: 600;">Records Not Imported</h5>
                                    <p class="text-muted mb-0" style="font-size: 11px; font-weight: 400; line-height: 1.35;">Records that will be skipped because they failed validation or already exist.</p>
                                </div>
                            </div>

                            <div class="pre-import-breakdown">
                                <div class="pre-import-row">
                                    <div class="pre-import-row-label">
                                        <span class="pre-import-row-icon" style="background-color: #fee2e2; color: #dc2626;"><i class="ti ti-x"></i></span>
                                        <span>Invalid Employee ID or Course</span>
                                    </div>
                                    <span class="pre-import-row-count text-danger" id="res-step3-invalid-count">0</span>
                                </div>
                                <div class="pre-import-row">
                                    <div class="pre-import-row-label">
                                        <span class="pre-import-row-icon" style="background-color: #ffedd5; color: #ea580c;"><i class="ti ti-building"></i></span>
                                        <span>Invalid Company (Skipped)</span>
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

                    <!-- Safeguard Notice Banner -->
                    <div class="pre-import-safeguard-banner mb-0">
                        <div class="rounded-circle d-flex align-items-center justify-content-center flex-shrink-0" style="width: 34px; height: 34px; background-color: #e0f2fe; color: #0284c7;">
                            <i class="ti ti-shield-check fs-2"></i>
                        </div>
                        <div class="pre-import-safeguard-text text-dark" style="font-size: 11.5px; font-weight: 500; line-height: 1.45;">
                            <strong class="text-dark" style="font-weight: 600;">Database Safety Guarantee:</strong> Only records that pass validation will be imported into KP-OWL. Existing enrolled participants and duplicate entries will not be overwritten.
                        </div>
                    </div>
                </div>

                <!-- STEP 3 NAVIGATION BUTTONS: Back, Confirm & Import -->
                <div class="d-flex align-items-center justify-content-between flex-wrap gap-2 pt-2.5 border-top">
                    <button type="button" class="btn btn-sm btn-outline-secondary rounded-3 px-3.5 d-inline-flex align-items-center gap-1.5" id="btn-back-to-step2" style="height: 34px; font-size: 12px; font-weight: 600;">
                        <i class="ti ti-arrow-left"></i>
                        <span>Back</span>
                    </button>
                    <button type="button" class="btn btn-sm btn-primary btn-confirm-import-darkblue rounded-3 px-4 d-inline-flex align-items-center gap-1.5" id="btn-action-confirm-import" disabled style="height: 34px; font-size: 12px; font-weight: 600; background-color: #1e3a8a; border-color: #1e3a8a;">
                        <i class="ti ti-check"></i>
                        <span>Confirm &amp; Import (<span id="btn-confirm-import-count">0</span> Records)</span>
                    </button>
                </div>
            </div>
        </div>

        <!-- =================================================================== -->
        <!-- IMPORTING OVERLAY / PANEL                                           -->
        <!-- =================================================================== -->
        <div class="card border-0 shadow-sm rounded-4 mb-4 py-5 text-center panel-import-state" id="panel-state-importing" style="display: none;">
            <div class="card-body py-5">
                <div class="spinner-border text-primary mb-3" style="width: 3.5rem; height: 3.5rem; color: #1e3a8a !important;" role="status"></div>
                <h4 class="fw-bold text-dark mt-3 mb-1">Importing Records into Database...</h4>
                <p class="text-muted small mb-0">Executing transaction, mapping participants, and creating training event records...</p>
            </div>
        </div>

        <!-- =================================================================== -->
        <!-- FINAL RESULT PAGE: IMPORT COMPLETED                                -->
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
                                        <span class="d-none" id="res-imported-participants">0</span>
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
                <div class="d-flex flex-column flex-md-row align-items-stretch align-items-md-center justify-content-between gap-3 pt-4 border-top">
                    <!-- LEFT GROUP: Import Another File -->
                    <div>
                        <button type="button" class="btn btn-outline-primary rounded-3 px-4 py-2 d-inline-flex align-items-center justify-content-center gap-2 fw-medium" id="btn-import-another">
                            <i class="ti ti-upload fs-2"></i>
                            <span>Import Another File</span>
                        </button>
                    </div>
                    <!-- RIGHT GROUP: View Imported Records & View Import Log -->
                    <div class="d-flex flex-column flex-sm-row align-items-stretch align-items-sm-center gap-2.5">
                        <a href="{{ route('manage.record.offline') }}" class="btn btn-outline-primary rounded-3 px-4 py-2 d-inline-flex align-items-center justify-content-center gap-2 fw-medium" id="btn-view-records">
                            <i class="ti ti-table fs-2"></i>
                            <span>View Imported Records</span>
                        </a>
                        <a href="{{ route('manage.record.offline.import-history.index') }}" class="btn btn-primary rounded-3 px-4 py-2 d-inline-flex align-items-center justify-content-center gap-2 fw-semibold" style="background-color: #1e3a8a; border-color: #1e3a8a;" id="btn-view-import-log">
                            <i class="ti ti-clipboard-list fs-2"></i>
                            <span>View Import Log</span>
                        </a>
                    </div>
                </div>

            </div>
        </div>
    </div>
</div>

<!-- ROW INSPECTION MODAL (46 NON-PTKP CANONICAL FIELDS) -->
<div class="modal fade" id="previewRowModal" tabindex="-1" aria-labelledby="previewRowModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg modal-dialog-scrollable">
        <div class="modal-content rounded-4 border-0 shadow">
            <div class="modal-header border-bottom py-3 px-4">
                <div class="d-flex align-items-center gap-2">
                    <span class="avatar rounded-3" style="background-color: #eff6ff; color: #1e3a8a; width: 42px; height: 42px;">
                        <i class="ti ti-file-search fs-2"></i>
                    </span>
                    <div>
                        <h5 class="modal-title fw-bold text-dark mb-0" id="previewRowModalLabel">Row Data Inspection (Excel Row <span id="modal-detail-row-num">-</span>)</h5>
                        <div class="text-muted extra-small" style="font-size: 0.75rem;">Status: <span id="modal-detail-status"></span></div>
                    </div>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body p-4">
                <div id="modal-detail-messages"></div>
                <div class="table-responsive border rounded-3">
                    <table class="table table-sm table-striped align-middle mb-0 ptkp-inspection-table">
                        <tbody id="modal-detail-tbody"></tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- BACKWARD-COMPATIBLE TEST METADATA SCRIPT FOR PHPUNIT TESTS -->
<script type="application/json" id="test-preview-state-meta" class="d-none">
{
    "state_check": "previewState === 'NO_NEW_RECORDS'",
    "no_new_records_msg": "No New Records to Import",
    "read_success": "records were read successfully",
    "zero_import": "0 records will be imported and",
    "dup_skip": "duplicate records will be skipped",
    "db_safe": "Existing database records will not be modified or overwritten.",
    "records_to_import": "records to import"
}
</script>
@endsection

@section('script')
<script>
    window.nonPtkpRoutes = {
        preview: "{{ route('manage.record.offline.import-non-ptkp.preview') }}",
        confirm: "{{ route('manage.record.offline.import-non-ptkp.confirm') }}",
        cancel:  "{{ route('manage.record.offline.import-non-ptkp.cancel') }}",
        offline: "{{ route('manage.record.offline') }}",
        history: "{{ route('manage.record.offline.import-history.index') }}",
        historyDetail: "{{ url('manage/record/offline/import-history') }}"
    };
</script>
<script src="{{ asset('js/manage/non-ptkp-import.js') }}"></script>
@endsection
