@extends('layouts.sidebar-master')

@section('title', 'Import PTKP Training Records — Import PTKP Data — OWL LMS')

@section('style')
<link rel="stylesheet" href="{{ asset('css/manage/ptkp-import.css') }}">
@endsection

@section('content')
<div class="page-body ptkp-import-page py-3">
    <div class="container-xl">
        <!-- BREADCRUMB / BACK LINK -->
        <div class="d-flex align-items-center justify-content-between flex-wrap gap-2 mb-2.5">
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb mb-0" style="font-size: 11.5px;">
                    <li class="breadcrumb-item"><a href="{{ route('manage.dashboard') }}" class="text-decoration-none">Manage Record</a></li>
                    <li class="breadcrumb-item"><a href="{{ route('manage.record.offline') }}" class="text-decoration-none">Offline Record</a></li>
                    <li class="breadcrumb-item active" aria-current="page">Import PTKP Data</li>
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
                        <h2 class="fw-bold mb-0.5 text-dark" style="font-size: 1.15rem;">Import PTKP Training Records</h2>
                        <p class="text-muted mb-0" style="font-size: 11.5px;">Upload and import offline training records into KP-OWL. Employee information will be matched automatically.</p>
                    </div>
                </div>
                <div class="d-flex align-items-center gap-2" id="ptkp-header-actions">
                    <!-- Download Template: Only Step 1 -->
                    <a href="{{ route('manage.record.offline.import-ptkp.template') }}" class="btn btn-outline-primary rounded-pill d-inline-flex align-items-center gap-1.5 px-3 py-1.5" id="btn-download-ptkp-template" style="font-size: 11.5px;" title="Download Template (.xlsx)">
                        <i class="ti ti-download fs-3"></i>
                        <span>Download PTKP Template (.xlsx)</span>
                    </a>
                    <!-- Import History: Only Step 3 & Result -->
                    <a href="{{ route('manage.record.offline.import-history.index') }}" class="btn btn-outline-secondary rounded-pill d-inline-flex align-items-center gap-1.5 px-3 py-1.5 d-none" id="btn-ptkp-history" style="font-size: 11.5px;" title="View Import History">
                        <i class="ti ti-history fs-3"></i>
                        <span>Import History</span>
                    </a>
                </div>
            </div>
        </div>

        <!-- EXACTLY 3-STEP PROCESS INDICATOR -->
        <div class="card border-0 shadow-sm py-2 px-3 mb-3 rounded-3 bg-white ptkp-stepper-card">
            <div class="ptkp-steps">
                <!-- Step 1 -->
                <div class="ptkp-step-item active" id="ptkp-step-ind-1" data-step="1">
                    <div class="ptkp-step-bubble">
                        <span class="step-num">1</span>
                        <i class="ti ti-check step-check"></i>
                    </div>
                    <div class="ptkp-step-title">Upload File</div>
                </div>
                <div class="ptkp-step-line" id="ptkp-step-line-1"></div>
                <!-- Step 2 -->
                <div class="ptkp-step-item" id="ptkp-step-ind-2" data-step="2">
                    <div class="ptkp-step-bubble">
                        <span class="step-num">2</span>
                        <i class="ti ti-check step-check"></i>
                    </div>
                    <div class="ptkp-step-title">Validation & Review</div>
                </div>
                <div class="ptkp-step-line" id="ptkp-step-line-2"></div>
                <!-- Step 3 -->
                <div class="ptkp-step-item" id="ptkp-step-ind-3" data-step="3">
                    <div class="ptkp-step-bubble">
                        <span class="step-num">3</span>
                        <i class="ti ti-check step-check"></i>
                    </div>
                    <div class="ptkp-step-title">Import Records</div>
                </div>
            </div>
        </div>

        <!-- =================================================================== -->
        <!-- STEP 1: UPLOAD FILE                                                 -->
        <!-- =================================================================== -->
        <div class="card border-0 shadow-sm rounded-4 mb-4" id="ptkp-upload-card">
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
                <div class="ptkp-dropzone p-5 text-center my-3 position-relative" id="ptkp-dropzone">
                    <input type="file" id="ptkp-file-input" class="position-absolute top-0 start-0 w-100 h-100 opacity-0" style="cursor: pointer;" accept=".xlsx,.xls" />
                    
                    <!-- Initial State (Hidden when file is selected) -->
                    <div class="dropzone-content py-3" id="ptkp-dropzone-initial">
                        <div class="avatar avatar-lg rounded-circle mx-auto mb-3" style="background-color: #eff6ff; color: #1e3a8a; width: 64px; height: 64px;">
                            <i class="ti ti-cloud-upload fs-1"></i>
                        </div>
                        <h5 class="fw-bold text-dark mb-1">Choose an Excel file or drag & drop here</h5>
                        <p class="text-muted small mb-2">Supported formats: <strong>.xlsx, .xls</strong> (Max 20MB)</p>
                        <div class="d-inline-flex align-items-center gap-1 text-muted small mt-1">
                            <span class="badge bg-light text-muted border px-2.5 py-1" id="ptkp-selected-filename">
                                <i class="ti ti-paperclip me-1"></i>No file selected
                            </span>
                        </div>
                    </div>

                    <!-- Selected File Info Card (Only shown when file is selected) -->
                    <div class="ptkp-file-card p-4 text-start position-relative" id="ptkp-file-info-card" style="display: none; cursor: default;">
                        <div class="d-flex align-items-center justify-content-between flex-wrap gap-3">
                            <div class="d-flex align-items-center gap-3">
                                <div class="avatar avatar-lg rounded-3 flex-shrink-0 d-flex align-items-center justify-content-center" style="background-color: #dcfce7; color: #16a34a; width: 56px; height: 56px;">
                                    <i class="ti ti-file-spreadsheet fs-1"></i>
                                </div>
                                <div style="min-width: 0;">
                                    <h5 class="fw-bold text-dark mb-1 text-truncate" id="ptkp-card-filename">file.xlsx</h5>
                                    <div class="d-flex align-items-center gap-2 flex-wrap">
                                        <span class="text-muted small" id="ptkp-card-filesize">0 KB</span>
                                        <span class="badge bg-success-lt text-success"><i class="ti ti-check me-1"></i>File ready for validation</span>
                                    </div>
                                </div>
                            </div>
                            <div class="d-flex align-items-center gap-2">
                                <button type="button" class="btn btn-sm btn-outline-secondary rounded-pill px-3 d-inline-flex align-items-center gap-1" id="ptkp-btn-change-file">
                                    <i class="ti ti-refresh"></i>
                                    <span>Change File</span>
                                </button>
                                <button type="button" class="btn btn-sm btn-outline-danger rounded-pill px-3 d-inline-flex align-items-center gap-1" id="ptkp-btn-remove-file">
                                    <i class="ti ti-trash"></i>
                                    <span>Remove</span>
                                </button>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Upload Error Alert -->
                <div class="alert alert-danger border-0 rounded-3 small mb-3 py-2 px-3" id="ptkp-upload-error" style="display: none;"></div>

                <!-- Guidelines Box -->
                <div class="alert alert-info border-0 rounded-3 small mb-4" style="background-color: #f0f9ff; color: #0369a1;">
                    <div class="d-flex align-items-start gap-2">
                        <i class="ti ti-info-circle fs-3 flex-shrink-0 mt-0.5"></i>
                        <div>
                            <strong class="d-block mb-1">Spreadsheet Guidelines & PTKP Business Rules:</strong>
                            <ul class="mb-0 ps-3">
                                <li><strong>Template Structure:</strong> Uses the simplified single-row PTKP template (46 columns). Obsolete columns and group headers have been removed.</li>
                                <li><strong>Employee Matching:</strong> <em>Employee ID</em> is matched against user accounts in the <code>users</code> table. If missing, not found, or ambiguous, the row is rejected (INVALID).</li>
                                <li><strong>Identity Synchronization:</strong> Five identity fields (<em>Employee Name</em>, <em>Grade</em>, <em>Job Position</em>, <em>Team</em>, <em>Department</em>) are automatically populated from the <code>users</code> table.</li>
                                <li><strong>Company Mismatch (PTKP):</strong> Discrepancies between spreadsheet company and database user profile are treated as a <strong>non-blocking warning (INVALID COMPANY)</strong>. The record will still be imported.</li>
                                <li><strong>Duplicate Prevention:</strong> Duplicate records within the file or already enrolled in the database will be skipped automatically (DUPLICATE).</li>
                            </ul>
                        </div>
                    </div>
                </div>

                <!-- Action Button -->
                <div class="d-flex align-items-center justify-content-end pt-3 border-top">
                    <button type="button" id="ptkp-btn-validate" class="btn btn-primary rounded-3 px-4 d-inline-flex align-items-center gap-2" style="background-color: #1e3a8a; border-color: #1e3a8a;" disabled>
                        <i class="ti ti-sparkles"></i>
                        <span>Validate</span>
                    </button>
                </div>
            </div>
        </div>

        <!-- =================================================================== -->
        <!-- LOADING CARD (VALIDATING STATE)                                     -->
        <!-- =================================================================== -->
        <div class="card border-0 shadow-sm rounded-4 mb-4 py-5 text-center" id="ptkp-loading-card" style="display: none;">
            <div class="card-body py-5">
                <div class="spinner-border text-primary mb-3" style="width: 3.5rem; height: 3.5rem; color: #1e3a8a !important;" role="status"></div>
                <h4 class="fw-bold text-dark mt-3 mb-1" id="ptkp-loading-title">Validating Spreadsheet...</h4>
                <p class="text-muted small mb-0" id="ptkp-loading-subtitle">Reading PTKP columns, verifying Employee IDs, and checking duplicates...</p>
            </div>
        </div>

        <!-- =================================================================== -->
        <!-- STEP 2: VALIDATION & REVIEW                                         -->
        <!-- =================================================================== -->
        <div class="card border-0 shadow-sm rounded-3 mb-3" id="ptkp-preview-card" style="display: none;">
            <div class="card-header bg-white border-bottom py-2.5 px-3 px-md-3.5 rounded-top-3">
                <div class="d-flex align-items-center justify-content-between flex-wrap gap-2">
                    <div class="d-flex align-items-center gap-2">
                        <span class="badge bg-primary text-white rounded-circle d-inline-flex align-items-center justify-content-center" style="width: 22px; height: 22px; font-size: 0.72rem; background-color: #1e3a8a !important;">2</span>
                        <div>
                            <h4 class="card-title fw-semibold text-dark mb-0" style="font-size: 14px;">Validation &amp; Review</h4>
                            <div class="text-muted extra-small" style="font-size: 11px;">Review validated training records before importing them into KP-OWL.</div>
                        </div>
                        <span class="badge bg-light text-secondary border px-2 py-0.5 ms-2" style="font-size: 10.5px;" id="ptkp-preview-filename">-</span>
                    </div>
                </div>
            </div>

            <div class="card-body p-3 p-md-3.5">
                <!-- Header Check Diagnostic Alert -->
                <div class="alert alert-warning border-0 rounded-3 small mb-2.5 py-2 px-3" id="ptkp-header-alert" style="display: none;">
                    <div class="d-flex align-items-start gap-2">
                        <i class="ti ti-alert-triangle fs-3 flex-shrink-0 mt-0.5"></i>
                        <div>
                            <strong class="d-block mb-1" style="font-size: 12px;">Header Validation Notes:</strong>
                            <div id="ptkp-header-alert-body" style="font-size: 11px;"></div>
                        </div>
                    </div>
                </div>

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

                <!-- 6 CLICKABLE KPI SUMMARY STAT CARDS (QUICK FILTER) -->
                <div class="row g-2 mb-2.5" id="ptkp-summary-cards-container">
                    <!-- Card 1: Total Rows -->
                    <div class="col-6 col-md-2">
                        <div class="card border p-2 rounded-3 text-center h-100 ptkp-kpi-card ptkp-summary-filter-card ptkp-kpi-total active"
                             data-filter="all" role="button" tabindex="0" title="Click to view all records">
                            <div class="d-flex align-items-center justify-content-center gap-1 small fw-semibold mb-1 ptkp-kpi-label">
                                <span>TOTAL ROWS</span>
                                <span class="ptkp-info-icon" data-bs-toggle="tooltip" data-bs-html="true" data-bs-placement="top" data-bs-custom-class="ptkp-status-tooltip ptkp-validation-tooltip"
                                      data-tooltip-id="ptkp-tt-content-total"
                                      tabindex="0" role="button" aria-label="Explanation of Total Rows">
                                    <i class="ti ti-info-circle"></i>
                                </span>
                            </div>
                            <div class="h3 fw-bold mb-0 ptkp-kpi-count" id="ptkp-kpi-total">0</div>
                        </div>
                    </div>

                    <!-- Card 2: VALID -->
                    <div class="col-6 col-md-2">
                        <div class="card border p-2 rounded-3 text-center h-100 ptkp-kpi-card ptkp-summary-filter-card ptkp-kpi-valid"
                             data-filter="valid" role="button" tabindex="0" title="Click to filter Valid records">
                            <div class="d-flex align-items-center justify-content-center gap-1 small fw-semibold mb-1 ptkp-kpi-label">
                                <span>VALID</span>
                                <span class="ptkp-info-icon" data-bs-toggle="tooltip" data-bs-html="true" data-bs-placement="top" data-bs-custom-class="ptkp-status-tooltip ptkp-validation-tooltip"
                                      data-tooltip-id="ptkp-tt-content-valid"
                                      tabindex="0" role="button" aria-label="Explanation of Valid status">
                                    <i class="ti ti-info-circle"></i>
                                </span>
                            </div>
                            <div class="h3 fw-bold mb-0 ptkp-kpi-count" id="ptkp-kpi-valid">0</div>
                        </div>
                    </div>

                    <!-- Card 3: WARNING -->
                    <div class="col-6 col-md-2">
                        <div class="card border p-2 rounded-3 text-center h-100 ptkp-kpi-card ptkp-summary-filter-card ptkp-kpi-warning"
                             data-filter="warning" role="button" tabindex="0" title="Click to filter Warning records">
                            <div class="d-flex align-items-center justify-content-center gap-1 small fw-semibold mb-1 ptkp-kpi-label">
                                <span>WARNING</span>
                                <span class="ptkp-info-icon" data-bs-toggle="tooltip" data-bs-html="true" data-bs-placement="top" data-bs-custom-class="ptkp-status-tooltip ptkp-validation-tooltip"
                                      data-tooltip-id="ptkp-tt-content-warning"
                                      tabindex="0" role="button" aria-label="Explanation of Warning status">
                                    <i class="ti ti-info-circle"></i>
                                </span>
                            </div>
                            <div class="h3 fw-bold mb-0 ptkp-kpi-count" id="ptkp-kpi-warning">0</div>
                        </div>
                    </div>

                    <!-- Card 4: DUPLICATE -->
                    <div class="col-6 col-md-2">
                        <div class="card border p-2 rounded-3 text-center h-100 ptkp-kpi-card ptkp-summary-filter-card ptkp-kpi-duplicate"
                             data-filter="duplicate" role="button" tabindex="0" title="Click to filter Duplicate records">
                            <div class="d-flex align-items-center justify-content-center gap-1 small fw-semibold mb-1 ptkp-kpi-label">
                                <span>DUPLICATE</span>
                                <span class="ptkp-info-icon" data-bs-toggle="tooltip" data-bs-html="true" data-bs-placement="top" data-bs-custom-class="ptkp-status-tooltip ptkp-validation-tooltip"
                                      data-tooltip-id="ptkp-tt-content-duplicate"
                                      tabindex="0" role="button" aria-label="Explanation of Duplicate status">
                                    <i class="ti ti-info-circle"></i>
                                </span>
                            </div>
                            <div class="h3 fw-bold mb-0 ptkp-kpi-count" id="ptkp-kpi-duplicate">0</div>
                        </div>
                    </div>

                    <!-- Card 5: INVALID ID & COURSE TITLE -->
                    <div class="col-6 col-md-2">
                        <div class="card border p-2 rounded-3 text-center h-100 ptkp-kpi-card ptkp-summary-filter-card ptkp-kpi-invalid"
                             data-filter="invalid" role="button" tabindex="0" title="Click to filter Invalid ID & Course Title records">
                            <div class="d-flex align-items-center justify-content-center gap-1 small fw-semibold mb-1 ptkp-kpi-label">
                                <span title="Invalid Employee ID or Course Title">INVALID ID &amp; COURSE TITLE</span>
                                <span class="ptkp-info-icon" data-bs-toggle="tooltip" data-bs-html="true" data-bs-placement="top" data-bs-custom-class="ptkp-status-tooltip ptkp-validation-tooltip"
                                      data-tooltip-id="ptkp-tt-content-invalid"
                                      tabindex="0" role="button" aria-label="Explanation of Invalid status">
                                    <i class="ti ti-info-circle"></i>
                                </span>
                            </div>
                            <div class="h3 fw-bold mb-0 ptkp-kpi-count" id="ptkp-kpi-invalid">0</div>
                            <div class="ptkp-kpi-subinfo extra-small mt-0.5" id="ptkp-kpi-invalid-subinfo" style="display: none;">
                                <span class="ptkp-sub-pill" data-subfilter="invalid_id" title="Filter records with Invalid ID">ID: <span id="ptkp-kpi-invalid-id-count">0</span></span>
                                <span>&bull;</span>
                                <span class="ptkp-sub-pill" data-subfilter="invalid_course" title="Filter records with Invalid Course Title">Course: <span id="ptkp-kpi-invalid-course-count">0</span></span>
                            </div>
                        </div>
                    </div>

                    <!-- Card 6: INVALID COMPANY -->
                    <div class="col-6 col-md-2">
                        <div class="card border p-2 rounded-3 text-center h-100 ptkp-kpi-card ptkp-summary-filter-card ptkp-kpi-invalid-company"
                             data-filter="invalid_company" role="button" tabindex="0" title="Click to filter Invalid Company records">
                            <div class="d-flex align-items-center justify-content-center gap-1 small fw-semibold mb-1 ptkp-kpi-label">
                                <span>INVALID COMPANY</span>
                                <span class="ptkp-info-icon" data-bs-toggle="tooltip" data-bs-html="true" data-bs-placement="top" data-bs-custom-class="ptkp-status-tooltip ptkp-validation-tooltip"
                                      data-tooltip-id="ptkp-tt-content-invalid-company"
                                      tabindex="0" role="button" aria-label="Explanation of Invalid Company status">
                                    <i class="ti ti-info-circle"></i>
                                </span>
                            </div>
                            <div class="h3 fw-bold mb-0 ptkp-kpi-count" id="ptkp-kpi-invalid-company">0</div>
                        </div>
                    </div>
                </div>

                <!-- VALIDATION STATUS EXPLANATION TOOLTIP TEMPLATES -->
                <div id="ptkp-tooltip-templates" class="d-none" aria-hidden="true">
                    <!-- Template 1: Total Rows (Simplified Minimal Tooltip) -->
                    <div id="ptkp-tt-content-total">
                        <div class="ptkp-tt-box ptkp-tt-minimal text-start">
                            <div class="ptkp-tt-head fw-bold mb-1">Total Uploaded Records</div>
                            <div class="ptkp-tt-desc mb-0">
                                The total number of data rows detected in the uploaded Excel file, excluding the header row. Total dataset evaluated.
                            </div>
                        </div>
                    </div>

                    <!-- Template 2: VALID (Simplified Minimal Tooltip) -->
                    <div id="ptkp-tt-content-valid">
                        <div class="ptkp-tt-box ptkp-tt-minimal text-start">
                            <div class="ptkp-tt-head fw-bold mb-1">Valid Records</div>
                            <div class="ptkp-tt-desc mb-0">
                                These records have passed all validation checks and are ready to be imported into the system.
                            </div>
                        </div>
                    </div>

                    <!-- Template 3: WARNING (Simplified Minimal Tooltip) -->
                    <div id="ptkp-tt-content-warning">
                        <div class="ptkp-tt-box ptkp-tt-minimal text-start">
                            <div class="ptkp-tt-head fw-bold mb-1">Warning Records</div>
                            <div class="ptkp-tt-desc mb-0">
                                Records with one or more empty fields that have RED HEADERS in the Excel template. Non-red empty fields do not trigger warnings. These records are non-blocking and will still be imported.
                            </div>
                        </div>
                    </div>

                    <!-- Template 4: DUPLICATE (Simplified Minimal Tooltip) -->
                    <div id="ptkp-tt-content-duplicate">
                        <div class="ptkp-tt-box ptkp-tt-minimal text-start">
                            <div class="ptkp-tt-head fw-bold mb-1">Duplicate Records</div>
                            <div class="ptkp-tt-desc mb-0">
                                These records have been identified as duplicates based on the system's existing duplicate detection rules. Importing them again could create duplicate training records.
                            </div>
                        </div>
                    </div>

                    <!-- Template 5: INVALID ID & COURSE TITLE -->
                    <div id="ptkp-tt-content-invalid">
                        <div class="ptkp-tt-box ptkp-tt-minimal text-start">
                            <div class="ptkp-tt-head fw-bold mb-1">Invalid ID &amp; Course Title Records</div>
                            <div class="ptkp-tt-desc mb-0">
                                These records failed mandatory validation and will be safely skipped:
                                <ul class="ps-3 mb-0 mt-1" style="font-size: 0.76rem;">
                                    <li><strong>INVALID ID:</strong> Employee ID is empty, not found in LMS users database, or ambiguous.</li>
                                    <li><strong>INVALID COURSE TITLE:</strong> Course Title is empty.</li>
                                </ul>
                            </div>
                        </div>
                    </div>

                    <!-- Template 6: INVALID COMPANY (Simplified Minimal Tooltip) -->
                    <div id="ptkp-tt-content-invalid-company">
                        <div class="ptkp-tt-box ptkp-tt-minimal text-start">
                            <div class="ptkp-tt-head fw-bold mb-1">Invalid Company Records</div>
                            <div class="ptkp-tt-desc mb-0">
                                Discrepancies between Excel company and LMS user profile. In PTKP, this is a non-blocking warning and the record will still be imported with a review tag.
                            </div>
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
                    <table class="table table-hover align-middle mb-0 ptkp-table" id="ptkp-preview-table">
                        <thead>
                            <tr>
                                <th style="width: 45px;" class="text-center">No</th>
                                <th style="width: 155px;">Status</th>
                                <th style="width: 115px;">Employee ID</th>
                                <th>Employee Name (from users)</th>
                                <th style="width: 125px;">Company</th>
                                <th>Course Title</th>
                                <th style="width: 85px;">Type</th>
                                <th style="width: 115px;">PIC</th>
                                <th style="min-width: 190px;">Validation Details</th>
                                <th style="width: 55px;" class="text-center">Action</th>
                            </tr>
                        </thead>
                        <tbody id="ptkp-preview-tbody">
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
                        <button type="button" class="btn btn-sm btn-outline-danger rounded-3 px-3 py-1.5" id="ptkp-btn-cancel-preview" style="font-size: 11.5px;">
                            <i class="ti ti-x me-1"></i>Cancel
                        </button>
                    </div>
                    <div class="d-flex align-items-center gap-2">
                        <button type="button" class="btn btn-sm btn-outline-secondary rounded-3 px-3 py-1.5 d-inline-flex align-items-center gap-1.5" id="ptkp-btn-back-to-step1" style="font-size: 11.5px;">
                            <i class="ti ti-arrow-left"></i>
                            <span>Back</span>
                        </button>
                        <button type="button" class="btn btn-sm btn-primary rounded-3 px-3.5 py-1.5 d-inline-flex align-items-center gap-1.5" id="ptkp-btn-next-to-step3" style="background-color: #1e3a8a; border-color: #1e3a8a; font-size: 11.5px;">
                            <span>Next / Continue to Import</span>
                            <i class="ti ti-arrow-right"></i>
                        </button>
                    </div>
                </div>
            </div>
        </div>
            </div>
        </div>

        <!-- =================================================================== -->
        <!-- STEP 3: IMPORT RECORDS                                              -->
        <!-- =================================================================== -->
        <div class="card border-0 shadow-sm rounded-4 mb-4" id="ptkp-confirm-card" style="display: none;">
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
                                        <h5 class="fw-bold text-dark mb-1">Records to Be Imported</h5>
                                        <p class="text-muted small mb-0">Records that passed validation and are eligible for import.</p>
                                    </div>
                                </div>

                                <div class="pre-import-breakdown">
                                    <div class="pre-import-row">
                                        <div class="pre-import-row-label">
                                            <span class="pre-import-row-icon" style="background-color: #dcfce7; color: #16a34a;"><i class="ti ti-check"></i></span>
                                            <span>Valid Records</span>
                                        </div>
                                        <span class="pre-import-row-count text-success" id="ptkp-step3-valid-count">0</span>
                                    </div>
                                    <div class="pre-import-row">
                                        <div class="pre-import-row-label">
                                            <span class="pre-import-row-icon" style="background-color: #fef3c7; color: #d97706;"><i class="ti ti-alert-triangle"></i></span>
                                            <span>Warning Records (Non-blocking)</span>
                                        </div>
                                        <span class="pre-import-row-count text-warning" id="ptkp-step3-warning-count">0</span>
                                    </div>
                                    <div class="pre-import-row" id="ptkp-step3-invalid-company-eligible-row" style="display: none;">
                                        <div class="pre-import-row-label">
                                            <span class="pre-import-row-icon" style="background-color: #ffedd5; color: #ea580c;"><i class="ti ti-building"></i></span>
                                            <span>Invalid Company (Imported with Review Tag)</span>
                                        </div>
                                        <span class="pre-import-row-count text-orange" id="ptkp-step3-invalid-company-count">0</span>
                                    </div>
                                </div>

                                <div class="pre-import-total-box mt-auto">
                                    <div>
                                        <div class="pre-import-total-label">Total to Import</div>
                                        <div class="pre-import-total-desc">Ready to write into database</div>
                                    </div>
                                    <div class="pre-import-total-value" id="ptkp-step3-total-to-import">0 Records</div>
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
                                        <h5 class="fw-bold text-dark mb-1">Records Not Imported</h5>
                                        <p class="text-muted small mb-0">Records that will be skipped because they failed validation or already exist.</p>
                                    </div>
                                </div>

                                <div class="pre-import-breakdown">
                                    <div class="pre-import-row">
                                        <div class="pre-import-row-label">
                                            <span class="pre-import-row-icon" style="background-color: #fee2e2; color: #dc2626;"><i class="ti ti-x"></i></span>
                                            <span>Invalid Employee ID or Course</span>
                                        </div>
                                        <span class="pre-import-row-count text-danger" id="ptkp-step3-invalid-count">0</span>
                                    </div>
                                    <div class="pre-import-row">
                                        <div class="pre-import-row-label">
                                            <span class="pre-import-row-icon" style="background-color: #ffedd5; color: #ea580c;"><i class="ti ti-building"></i></span>
                                            <span>Invalid Company (Skipped)</span>
                                        </div>
                                        <span class="pre-import-row-count text-muted" id="ptkp-step3-invalid-company-skipped">0</span>
                                    </div>
                                    <div class="pre-import-row">
                                        <div class="pre-import-row-label">
                                            <span class="pre-import-row-icon" style="background-color: #f3e8ff; color: #7c3aed;"><i class="ti ti-copy"></i></span>
                                            <span>Duplicate Records</span>
                                        </div>
                                        <span class="pre-import-row-count text-purple" id="ptkp-step3-duplicate-count">0</span>
                                    </div>
                                </div>

                                <div class="pre-import-total-box mt-auto">
                                    <div>
                                        <div class="pre-import-total-label">Total Not Imported</div>
                                        <div class="pre-import-total-desc">Safely skipped to prevent bad data</div>
                                    </div>
                                    <div class="pre-import-total-value" id="ptkp-step3-total-not-imported">0 Records</div>
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
                            <strong class="text-dark">Database Safety Guarantee:</strong> Only records that pass validation will be imported into KP-OWL. Existing enrolled participants and duplicate entries will not be overwritten.
                        </div>
                    </div>
                </div>

                <!-- STEP 3 NAVIGATION BUTTONS: Back, Confirm & Import -->
                <div class="d-flex align-items-center justify-content-between flex-wrap gap-3 pt-4 border-top">
                    <button type="button" class="btn btn-outline-secondary rounded-3 px-4 py-2.5 d-inline-flex align-items-center gap-2 fw-medium" id="ptkp-btn-back-to-step2">
                        <i class="ti ti-arrow-left fs-2"></i>
                        <span>Back</span>
                    </button>
                    <button type="button" class="btn btn-primary btn-confirm-import-darkblue rounded-3 px-4 py-2.5 d-inline-flex align-items-center gap-2 fw-semibold" id="ptkp-btn-confirm" disabled>
                        <i class="ti ti-check fs-2"></i>
                        <span>Confirm &amp; Import (<span id="ptkp-btn-confirm-count">0</span> Records)</span>
                    </button>
                </div>
            </div>
        </div>

        <!-- =================================================================== -->
        <!-- FINAL RESULT PAGE: IMPORT COMPLETED (DASHBOARD REDESIGN)           -->
        <!-- =================================================================== -->
        <div class="card border-0 shadow-sm rounded-4 mb-4" id="ptkp-result-card" style="display: none;">
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
                                        <div class="h2 fw-bold mb-0" style="color: #15803d;" id="res-imported-participants">0</div>
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

                <!-- 4. BOTTOM ACTION BUTTONS (REORGANIZED LEFT & RIGHT GROUPS) -->
                <div class="d-flex flex-column flex-md-row align-items-stretch align-items-md-center justify-content-between gap-3 pt-4 border-top" id="ptkp-result-actions">
                    <!-- LEFT GROUP: Import Another File -->
                    <div class="ptkp-result-action-left">
                        <button type="button" class="btn btn-outline-primary rounded-3 px-4 py-2 d-inline-flex align-items-center justify-content-center gap-2 fw-medium ptkp-btn-action" id="ptkp-btn-import-another">
                            <i class="ti ti-upload fs-2"></i>
                            <span>Import Another File</span>
                        </button>
                    </div>
                    <!-- RIGHT GROUP: View Imported Records & View Import Log -->
                    <div class="ptkp-result-action-right d-flex flex-column flex-sm-row align-items-stretch align-items-sm-center gap-2.5">
                        <a href="{{ route('manage.record.offline') }}" class="btn btn-outline-primary rounded-3 px-4 py-2 d-inline-flex align-items-center justify-content-center gap-2 fw-medium ptkp-btn-action" id="ptkp-btn-view-records">
                            <i class="ti ti-table fs-2"></i>
                            <span>View Imported Records</span>
                        </a>
                        <a href="{{ route('manage.record.offline.import-history.index') }}" class="btn btn-primary rounded-3 px-4 py-2 d-inline-flex align-items-center justify-content-center gap-2 fw-semibold ptkp-btn-action" style="background-color: #1e3a8a; border-color: #1e3a8a;" id="ptkp-btn-view-log">
                            <i class="ti ti-clipboard-list fs-2"></i>
                            <span>View Import Log</span>
                        </a>
                    </div>
                </div>

            </div>
        </div>
            </div>
        </div>
    </div>
</div>

<!-- ROW INSPECTION MODAL -->
<div class="modal fade" id="ptkpInspectionModal" tabindex="-1" aria-labelledby="ptkpInspectionModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg modal-dialog-scrollable">
        <div class="modal-content rounded-4 border-0 shadow">
            <div class="modal-header border-bottom py-3 px-4">
                <div class="d-flex align-items-center gap-2">
                    <span class="avatar rounded-3" style="background-color: #eff6ff; color: #1e3a8a; width: 42px; height: 42px;">
                        <i class="ti ti-file-search fs-2"></i>
                    </span>
                    <div>
                        <h5 class="modal-title fw-bold text-dark mb-0" id="ptkpInspectionModalLabel">Row Data Inspection (Excel Row <span id="modal-inspect-row-num">-</span>)</h5>
                        <div class="text-muted extra-small" style="font-size: 0.75rem;">Status: <span id="modal-inspect-status"></span></div>
                    </div>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body p-4">
                <div id="modal-inspect-messages"></div>
                <div class="table-responsive border rounded-3">
                    <table class="table table-sm table-striped align-middle mb-0 ptkp-inspection-table">
                        <tbody id="modal-inspect-tbody"></tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@section('script')
<script>
    window.ptkpRoutes = {
        preview: "{{ route('manage.record.offline.import-ptkp.preview') }}",
        confirm: "{{ route('manage.record.offline.import-ptkp.confirm') }}",
        cancel:  "{{ route('manage.record.offline.import-ptkp.cancel') }}",
        offline: "{{ route('manage.record.offline') }}",
        history: "{{ route('manage.record.offline.import-history.index') }}",
        historyDetail: "{{ url('manage/record/offline/import-history') }}"
    };
</script>
<script src="{{ asset('js/manage/ptkp-import.js') }}"></script>
@endsection
