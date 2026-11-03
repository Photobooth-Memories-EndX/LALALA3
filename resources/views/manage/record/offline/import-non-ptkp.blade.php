@extends('layouts.sidebar-master')

@section('title', 'Import Non-PTKP Training Records — Import Non-PTKP Data — OWL LMS')

@section('style')
<link rel="stylesheet" href="{{ asset('css/manage/non-ptkp-import.css') }}">
@endsection

@section('content')
<div class="page-body non-ptkp-import-page py-4">
    <div class="container-xl">
        <!-- BREADCRUMB / BACK LINK -->
        <div class="d-flex align-items-center justify-content-between flex-wrap gap-2 mb-3">
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb mb-0">
                    <li class="breadcrumb-item"><a href="{{ route('manage.dashboard') }}" class="text-decoration-none">Manage Record</a></li>
                    <li class="breadcrumb-item"><a href="{{ route('manage.record.offline') }}" class="text-decoration-none">Offline Record</a></li>
                    <li class="breadcrumb-item active" aria-current="page">Import Non-PTKP Data</li>
                </ol>
            </nav>
            <a href="{{ route('manage.record.offline') }}" class="btn-back-non-ptkp d-inline-flex align-items-center gap-1.5 text-decoration-none fw-medium" id="btn-top-back">
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
                        <h2 class="h3 fw-bold mb-1 text-dark">Import Non-PTKP Training Records</h2>
                        <p class="text-muted small mb-0">Upload and import non-PTKP offline training records into KP-OWL. Employee information will be matched automatically.</p>
                    </div>
                </div>
                <div class="d-flex align-items-center gap-2" id="header-action-container">
                    <a href="{{ route('manage.record.offline.import-non-ptkp.template') }}" class="btn btn-outline-primary rounded-pill d-inline-flex align-items-center gap-2 px-3 py-2" id="btn-download-non-ptkp-template" title="Download Template (.xlsx)">
                        <i class="ti ti-download fs-2"></i>
                        <span>Download Non-PTKP Template (.xlsx)</span>
                    </a>
                    <a href="{{ route('manage.record.offline.import-history.index') }}" class="btn btn-outline-secondary rounded-pill d-inline-flex align-items-center gap-2 px-3 py-2 d-none" id="btn-non-ptkp-history" title="View Import History">
                        <i class="ti ti-history fs-2"></i>
                        <span>Import History</span>
                    </a>
                </div>
            </div>
        </div>

        <!-- EXACTLY 3-STEP PROCESS INDICATOR -->
        <div class="card border-0 shadow-sm p-3 mb-4 rounded-3 bg-white non-ptkp-stepper-card">
            <div class="non-ptkp-steps">
                <!-- Step 1 -->
                <div class="non-ptkp-step-item active" id="step-ind-1" data-step="1">
                    <div class="non-ptkp-step-bubble">
                        <span class="step-num">1</span>
                        <i class="ti ti-check step-check"></i>
                    </div>
                    <div class="non-ptkp-step-title">Upload File</div>
                </div>
                <div class="non-ptkp-step-line" id="step-line-1"></div>
                <!-- Step 2 -->
                <div class="non-ptkp-step-item" id="step-ind-2" data-step="2">
                    <div class="non-ptkp-step-bubble">
                        <span class="step-num">2</span>
                        <i class="ti ti-check step-check"></i>
                    </div>
                    <div class="non-ptkp-step-title">Validation & Review</div>
                </div>
                <div class="non-ptkp-step-line" id="step-line-2"></div>
                <!-- Step 3 -->
                <div class="non-ptkp-step-item" id="step-ind-3" data-step="3">
                    <div class="non-ptkp-step-bubble">
                        <span class="step-num">3</span>
                        <i class="ti ti-check step-check"></i>
                    </div>
                    <div class="non-ptkp-step-title">Import Records</div>
                </div>
            </div>
        </div>

        <!-- =================================================================== -->
        <!-- STEP 1: UPLOAD FILE CARD                                            -->
        <!-- =================================================================== -->
        <div class="card border-0 shadow-sm rounded-4 mb-4" id="non-ptkp-upload-card">
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
                <div class="non-ptkp-dropzone p-5 text-center my-3 position-relative" id="non-ptkp-dropzone">
                    <input type="file" id="non-ptkp-file-input" class="position-absolute top-0 start-0 w-100 h-100 opacity-0" style="cursor: pointer;" accept=".xlsx,.xls" />
                    
                    <!-- Initial State -->
                    <div class="dropzone-content py-3" id="non-ptkp-dropzone-initial">
                        <div class="avatar avatar-lg rounded-circle mx-auto mb-3" style="background-color: #eff6ff; color: #1e3a8a; width: 64px; height: 64px;">
                            <i class="ti ti-cloud-upload fs-1"></i>
                        </div>
                        <h5 class="fw-bold text-dark mb-1">Choose an Excel file or drag & drop here</h5>
                        <p class="text-muted small mb-2">Supported formats: <strong>.xlsx, .xls</strong> (Max 20MB)</p>
                        <div class="d-inline-flex align-items-center gap-1 text-muted small mt-1">
                            <span class="badge bg-light text-muted border px-2.5 py-1" id="non-ptkp-selected-filename">
                                <i class="ti ti-paperclip me-1"></i>No file selected
                            </span>
                        </div>
                    </div>

                    <!-- Selected File Info Card -->
                    <div class="non-ptkp-file-card p-4 text-start position-relative" id="non-ptkp-file-info-card" style="display: none; cursor: default;">
                        <div class="d-flex align-items-center justify-content-between flex-wrap gap-3">
                            <div class="d-flex align-items-center gap-3">
                                <div class="avatar avatar-lg rounded-3 flex-shrink-0 d-flex align-items-center justify-content-center" style="background-color: #dcfce7; color: #16a34a; width: 56px; height: 56px;">
                                    <i class="ti ti-file-spreadsheet fs-1"></i>
                                </div>
                                <div style="min-width: 0;">
                                    <h5 class="fw-bold text-dark mb-1 text-truncate" id="non-ptkp-card-filename">file.xlsx</h5>
                                    <div class="d-flex align-items-center gap-2 flex-wrap">
                                        <span class="text-muted small" id="non-ptkp-card-filesize">0 KB</span>
                                        <span class="badge bg-success-lt text-success"><i class="ti ti-check me-1"></i>File ready for validation</span>
                                    </div>
                                </div>
                            </div>
                            <div class="d-flex align-items-center gap-2">
                                <button type="button" class="btn btn-sm btn-outline-secondary rounded-pill px-3 d-inline-flex align-items-center gap-1" id="non-ptkp-btn-change-file">
                                    <i class="ti ti-refresh"></i>
                                    <span>Change File</span>
                                </button>
                                <button type="button" class="btn btn-sm btn-outline-danger rounded-pill px-3 d-inline-flex align-items-center gap-1" id="non-ptkp-btn-remove-file">
                                    <i class="ti ti-trash"></i>
                                    <span>Remove</span>
                                </button>
                            </div>
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
                <div class="d-flex align-items-center justify-content-end pt-3 border-top">
                    <button type="button" id="non-ptkp-btn-validate" class="btn btn-primary rounded-3 px-4 d-inline-flex align-items-center gap-2" style="background-color: #1e3a8a; border-color: #1e3a8a;" disabled>
                        <i class="ti ti-sparkles"></i>
                        <span>Validate</span>
                    </button>
                </div>
            </div>
        </div>

        <!-- =================================================================== -->
        <!-- LOADING CARD (VALIDATING STATE)                                     -->
        <!-- =================================================================== -->
        <div class="card border-0 shadow-sm rounded-4 mb-4 py-5 text-center" id="non-ptkp-loading-card" style="display: none;">
            <div class="card-body py-5">
                <div class="spinner-border text-primary mb-3" style="width: 3.5rem; height: 3.5rem; color: #1e3a8a !important;" role="status"></div>
                <h4 class="fw-bold text-dark mt-3 mb-1" id="non-ptkp-loading-title">Validating Spreadsheet...</h4>
                <p class="text-muted small mb-0" id="non-ptkp-loading-subtitle">Reading columns, resolving header groups, and checking existing records...</p>
            </div>
        </div>

        <!-- =================================================================== -->
        <!-- STEP 2: VALIDATION & REVIEW                                         -->
        <!-- =================================================================== -->
        <div class="card border-0 shadow-sm rounded-4 mb-4" id="non-ptkp-preview-card" style="display: none;">
            <div class="card-header bg-white border-bottom pt-4 pb-3 px-4 rounded-top-4">
                <div class="d-flex align-items-center justify-content-between flex-wrap gap-2">
                    <div class="d-flex align-items-center gap-2">
                        <span class="badge bg-primary text-white rounded-circle d-inline-flex align-items-center justify-content-center" style="width: 26px; height: 26px; font-size: 0.8rem; background-color: #1e3a8a !important;">2</span>
                        <div>
                            <h4 class="card-title fw-bold text-dark mb-0">Validation & Review</h4>
                            <div class="text-muted small">Review validated training records before importing them into KP-OWL.</div>
                        </div>
                        <span class="badge bg-light text-secondary border ms-2" id="non-ptkp-preview-filename">-</span>
                    </div>
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

                <!-- 7 KPI SUMMARY METRIC CARDS -->
                <div class="row g-2 mb-4">
                    <div class="col-6 col-md-3 col-lg">
                        <div class="card border p-2.5 rounded-3 text-center h-100 kpi-card" title="Total spreadsheet rows read">
                            <div class="text-muted small fw-medium mb-0">Total</div>
                            <div class="h3 fw-bold text-dark mb-0" id="stat-non-ptkp-total">0</div>
                            <div class="extra-small text-muted">rows read</div>
                        </div>
                    </div>
                    <div class="col-6 col-md-3 col-lg">
                        <div class="card border p-2.5 rounded-3 text-center h-100 kpi-card" style="background-color: #f0fdf4; border-color: #bbf7d0 !important;" title="Valid records ready to import">
                            <div class="small fw-semibold mb-0" style="color: #15803d;">VALID</div>
                            <div class="h3 fw-bold mb-0" style="color: #15803d;" id="stat-non-ptkp-valid">0</div>
                            <div class="extra-small" style="color: #15803d;">ready to import</div>
                        </div>
                    </div>
                    <div class="col-6 col-md-3 col-lg">
                        <div class="card border p-2.5 rounded-3 text-center h-100 kpi-card" style="background-color: #fffbeb; border-color: #fde68a !important;" title="Rows with non-blocking warnings">
                            <div class="small fw-semibold mb-0" style="color: #92400e;">WARNING</div>
                            <div class="h3 fw-bold mb-0" style="color: #d97706;" id="stat-non-ptkp-warning">0</div>
                            <div class="extra-small" style="color: #92400e;">non-blocking</div>
                        </div>
                    </div>
                    <div class="col-6 col-md-3 col-lg">
                        <div class="card border p-2.5 rounded-3 text-center h-100 kpi-card" style="background-color: #fefce8; border-color: #fef08a !important;" title="Duplicate rows that will be skipped">
                            <div class="small fw-semibold mb-0" style="color: #a16207;">DUPLICATE</div>
                            <div class="h3 fw-bold mb-0" style="color: #ca8a04;" id="stat-non-ptkp-duplicate">0</div>
                            <div class="extra-small" style="color: #a16207;">will be skipped</div>
                        </div>
                    </div>
                    <div class="col-6 col-md-3 col-lg">
                        <div class="card border p-2.5 rounded-3 text-center h-100 kpi-card" style="background-color: #fef2f2; border-color: #fecaca !important;" title="Fatal validation errors that must be fixed">
                            <div class="small fw-semibold mb-0" style="color: #b91c1c;">INVALID</div>
                            <div class="h3 fw-bold mb-0" style="color: #dc2626;" id="stat-non-ptkp-invalid">0</div>
                            <div class="extra-small" style="color: #b91c1c;">fatal errors</div>
                        </div>
                    </div>
                    <div class="col-6 col-md-3 col-lg">
                        <div class="card border p-2.5 rounded-3 text-center h-100 kpi-card" style="background-color: #eff6ff; border-color: #bfdbfe !important;" title="Unique training events grouped from rows">
                            <div class="small fw-semibold mb-0" style="color: #1e40af;">Events</div>
                            <div class="h3 fw-bold mb-0" style="color: #2563eb;" id="stat-non-ptkp-events">0</div>
                            <div class="extra-small" style="color: #1e40af;">unique events</div>
                        </div>
                    </div>
                </div>

                <!-- PREVIEW DATA TABLE -->
                <div class="table-responsive border rounded-3 mb-3">
                    <table class="table table-hover align-middle mb-0" id="non-ptkp-preview-table">
                        <thead class="table-light">
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
                            <!-- Populated via JS -->
                        </tbody>
                    </table>
                </div>

                <!-- PAGINATION CONTROLS -->
                <div class="d-flex align-items-center justify-content-between flex-wrap gap-2 mb-4">
                    <div class="text-muted small" id="non-ptkp-pagination-info">
                        Showing 0 to 0 of 0 entries
                    </div>
                    <ul class="pagination pagination-sm m-0" id="non-ptkp-pagination-nav">
                        <!-- Populated via JS -->
                    </ul>
                </div>

                <!-- STEP 2 NAVIGATION: Cancel, Back, Next -->
                <div class="d-flex align-items-center justify-content-between flex-wrap gap-3 pt-3 border-top">
                    <div>
                        <button type="button" class="btn btn-outline-danger rounded-3 px-4" id="non-ptkp-btn-cancel-bottom">
                            <i class="ti ti-x me-1"></i>Cancel
                        </button>
                    </div>
                    <div class="d-flex align-items-center gap-2">
                        <button type="button" class="btn btn-outline-secondary rounded-3 px-4 d-inline-flex align-items-center gap-1.5" id="non-ptkp-btn-back-to-step1">
                            <i class="ti ti-arrow-left"></i>
                            <span>Back</span>
                        </button>
                        <button type="button" class="btn btn-primary rounded-3 px-4 d-inline-flex align-items-center gap-2" id="non-ptkp-btn-next-to-step3" style="background-color: #1e3a8a; border-color: #1e3a8a;">
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
        <div class="card border-0 shadow-sm rounded-4 mb-4" id="non-ptkp-confirm-card" style="display: none;">
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
                                        <span class="pre-import-row-count text-success" id="non-ptkp-step3-valid-count">0</span>
                                    </div>
                                    <div class="pre-import-row">
                                        <div class="pre-import-row-label">
                                            <span class="pre-import-row-icon" style="background-color: #fef3c7; color: #d97706;"><i class="ti ti-alert-triangle"></i></span>
                                            <span>Warning Records (Non-blocking)</span>
                                        </div>
                                        <span class="pre-import-row-count text-warning" id="non-ptkp-step3-warning-count">0</span>
                                    </div>
                                    <div class="pre-import-row" id="non-ptkp-step3-invalid-company-eligible-row" style="display: none;">
                                        <div class="pre-import-row-label">
                                            <span class="pre-import-row-icon" style="background-color: #ffedd5; color: #ea580c;"><i class="ti ti-building"></i></span>
                                            <span>Invalid Company (Imported with Review Tag)</span>
                                        </div>
                                        <span class="pre-import-row-count text-orange" id="non-ptkp-step3-invalid-company-count">0</span>
                                    </div>
                                </div>

                                <div class="pre-import-total-box mt-auto">
                                    <div>
                                        <div class="pre-import-total-label">Total to Import</div>
                                        <div class="pre-import-total-desc">Ready to write into database</div>
                                    </div>
                                    <div class="pre-import-total-value" id="non-ptkp-step3-total-to-import">0 Records</div>
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
                                        <span class="pre-import-row-count text-danger" id="non-ptkp-step3-invalid-count">0</span>
                                    </div>
                                    <div class="pre-import-row">
                                        <div class="pre-import-row-label">
                                            <span class="pre-import-row-icon" style="background-color: #f1f5f9; color: #64748b;"><i class="ti ti-info-circle"></i></span>
                                            <span>Invalid Company (Skipped) <span class="text-muted extra-small" title="Non-blocking warning in OWL backend; imported with review note">(Non-blocking in OWL)</span></span>
                                        </div>
                                        <span class="pre-import-row-count text-muted" id="non-ptkp-step3-invalid-company-skipped">0</span>
                                    </div>
                                    <div class="pre-import-row">
                                        <div class="pre-import-row-label">
                                            <span class="pre-import-row-icon" style="background-color: #f3e8ff; color: #7c3aed;"><i class="ti ti-copy"></i></span>
                                            <span>Duplicate Records</span>
                                        </div>
                                        <span class="pre-import-row-count text-purple" id="non-ptkp-step3-duplicate-count">0</span>
                                    </div>
                                </div>

                                <div class="pre-import-total-box mt-auto">
                                    <div>
                                        <div class="pre-import-total-label">Total Not Imported</div>
                                        <div class="pre-import-total-desc">Safely skipped to prevent bad data</div>
                                    </div>
                                    <div class="pre-import-total-value" id="non-ptkp-step3-total-not-imported">0 Records</div>
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
                    <button type="button" class="btn btn-outline-secondary rounded-3 px-4 py-2.5 d-inline-flex align-items-center gap-2 fw-medium" id="non-ptkp-btn-back-to-step2">
                        <i class="ti ti-arrow-left fs-2"></i>
                        <span>Back</span>
                    </button>
                    <button type="button" class="btn btn-primary btn-confirm-import-darkblue rounded-3 px-4 py-2.5 d-inline-flex align-items-center gap-2 fw-semibold" id="non-ptkp-btn-confirm-import">
                        <i class="ti ti-check fs-2"></i>
                        <span>Confirm &amp; Import (<span id="non-ptkp-btn-confirm-import-count">0</span> Records)</span>
                    </button>
                </div>
                    </button>
                </div>
            </div>
        </div>

        <!-- =================================================================== -->
        <!-- LOADING CARD (IMPORTING IN PROGRESS)                                -->
        <!-- =================================================================== -->
        <div class="card border-0 shadow-sm rounded-4 mb-4 py-5 text-center" id="non-ptkp-importing-card" style="display: none;">
            <div class="card-body py-5">
                <div class="spinner-border text-primary mb-3" style="width: 3.5rem; height: 3.5rem; color: #1e3a8a !important;" role="status"></div>
                <h4 class="fw-bold text-dark mt-3 mb-1">Importing Training Records...</h4>
                <p class="text-muted small mb-0">Executing database transaction, creating training sessions, and registering participants...</p>
            </div>
        </div>

        <!-- =================================================================== -->
        <!-- FINAL RESULT PAGE: IMPORT COMPLETED (DASHBOARD REDESIGN)            -->
        <!-- =================================================================== -->
        <div class="card border-0 shadow-sm rounded-4 mb-4" id="non-ptkp-result-card" style="display: none;">
            <div class="card-body p-4 p-md-5">

                <!-- 1. SUCCESS BANNER -->
                <div class="p-4 rounded-4 mb-4 import-completed-banner" id="res-nonptkp-success-banner" style="background-color: #ecfdf5; border: 1px solid #a7f3d0;">
                    <div class="d-flex align-items-start gap-3">
                        <div class="rounded-circle d-flex align-items-center justify-content-center flex-shrink-0" style="width: 48px; height: 48px; background-color: #10b981; color: #ffffff;">
                            <i class="ti ti-check fs-1 fw-bold"></i>
                        </div>
                        <div>
                            <h3 class="fw-bold mb-1" style="color: #064e3b;" id="res-nonptkp-header-title">Import Completed</h3>
                            <p class="mb-0 text-muted" id="res-nonptkp-header-desc" style="color: #047857 !important;">Your training records have been processed. Please review the import results below.</p>
                        </div>
                    </div>
                </div>

                <!-- 2. IMPORT SUCCESS RATE SECTION -->
                <div class="card border rounded-4 mb-4 shadow-none" style="background-color: #ffffff; border-color: #e2e8f0 !important;">
                    <div class="card-body p-4">
                        <div class="d-flex align-items-center justify-content-between mb-3">
                            <h4 class="fw-bold text-dark mb-0">Import Success Rate</h4>
                            <span class="h1 fw-bold text-dark mb-0" id="res-nonptkp-rate-percentage">0.0%</span>
                        </div>

                        <!-- Progress Bar -->
                        <div class="progress rounded-pill mb-3" style="height: 14px; background-color: #e2e8f0;">
                            <div class="progress-bar rounded-pill" id="res-nonptkp-rate-progress-bar" role="progressbar" style="width: 0%; background-color: #1e3a8a; transition: width 0.8s ease;" aria-valuenow="0" aria-valuemin="0" aria-valuemax="100"></div>
                        </div>

                        <div class="d-flex align-items-center justify-content-between small">
                            <span class="d-inline-flex align-items-center gap-1.5 fw-bold" style="color: #15803d;">
                                <i class="ti ti-check"></i> <span id="res-nonptkp-rate-imported-count">0</span> imported
                            </span>
                            <span class="d-inline-flex align-items-center gap-1.5 text-muted fw-semibold">
                                <span id="res-nonptkp-rate-skipped-count">0</span> skipped
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
                                        <div class="h2 fw-bold text-dark mb-0" id="res-nonptkp-total-processed">0</div>
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
                                        <div class="h2 fw-bold mb-0" style="color: #15803d;" id="res-nonptkp-imported-count">0</div>
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
                                        <div class="h2 fw-bold mb-0" style="color: #b45309;" id="res-nonptkp-warnings-imported">0</div>
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
                                        <div class="h2 fw-bold mb-0" style="color: #7c3aed;" id="res-nonptkp-duplicates-skipped">0</div>
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
                                        <div class="h2 fw-bold mb-0" style="color: #dc2626;" id="res-nonptkp-invalid-skipped">0</div>
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
                                        <div class="h2 fw-bold mb-0" style="color: #ea580c;" id="res-nonptkp-invalid-company-imported">0</div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Additional Session Metadata (trainings created & reused) -->
                    <div class="d-flex align-items-center justify-content-end gap-3 mt-3 text-muted extra-small">
                        <span><i class="ti ti-sparkles me-1 text-primary"></i>Training Sessions Created: <strong class="text-dark" id="res-nonptkp-trainings-created">0</strong></span>
                        <span>·</span>
                        <span><i class="ti ti-rotate-clockwise me-1 text-secondary"></i>Sessions Reused: <strong class="text-dark" id="res-nonptkp-trainings-reused">0</strong></span>
                    </div>
                </div>

                <!-- 4. BOTTOM ACTION BUTTONS -->
                <div class="d-flex align-items-center justify-content-between flex-wrap gap-3 pt-3 border-top">
                    <button type="button" class="btn btn-outline-secondary rounded-3 px-4 py-2.5 d-inline-flex align-items-center gap-2" id="non-ptkp-btn-import-another">
                        <i class="ti ti-upload fs-2"></i>
                        <span>Import Another File</span>
                    </button>
                    <a href="{{ route('manage.record.offline') }}" class="btn btn-primary rounded-3 px-4 py-2.5 d-inline-flex align-items-center gap-2" style="background-color: #1e3a8a; border-color: #1e3a8a;" id="btn-nonptkp-view-imported-records">
                        <i class="ti ti-table fs-2"></i>
                        <span>View Imported Records</span>
                    </a>
                </div>

            </div>
        </div>

    </div>
</div>

<!-- ROW DETAIL INSPECTION MODAL -->
<div class="modal fade" id="nonPtkpInspectionModal" tabindex="-1" aria-labelledby="nonPtkpInspectionModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-xl modal-dialog-scrollable">
        <div class="modal-content rounded-4 border-0 shadow">
            <div class="modal-header border-bottom py-3 px-4">
                <div class="d-flex align-items-center gap-2">
                    <span class="avatar rounded-3" style="background-color: #eff6ff; color: #1e3a8a; width: 42px; height: 42px;">
                        <i class="ti ti-file-search fs-2"></i>
                    </span>
                    <div>
                        <h5 class="modal-title fw-bold text-dark mb-0" id="nonPtkpInspectionModalLabel">Row Data Inspection (Excel Row <span id="non-ptkp-modal-row-num">-</span>)</h5>
                        <div class="text-muted extra-small">Detailed inspection of spreadsheet values</div>
                    </div>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body p-4">
                <div id="non-ptkp-modal-messages" class="mb-3"></div>
                <div class="row g-3">
                    <!-- Participant Details -->
                    <div class="col-md-6">
                        <div class="card border-0 bg-light p-3 rounded-3 h-100">
                            <div class="fw-bold small text-dark mb-2 text-uppercase" style="font-size: 0.75rem; letter-spacing: 0.05em;">
                                <i class="ti ti-user me-1 text-primary"></i> 1. Participant Details
                            </div>
                            <div class="row g-2 small">
                                <div class="col-6"><span class="text-muted d-block">Employee ID:</span><span class="fw-semibold text-dark font-monospace" id="insp-p-empid">-</span></div>
                                <div class="col-6"><span class="text-muted d-block">Full Name:</span><span class="fw-semibold text-dark" id="insp-p-name">-</span></div>
                                <div class="col-6"><span class="text-muted d-block">Grade:</span><span class="fw-semibold text-dark" id="insp-p-grade">-</span></div>
                                <div class="col-6"><span class="text-muted d-block">Position:</span><span class="fw-semibold text-dark" id="insp-p-position">-</span></div>
                                <div class="col-6"><span class="text-muted d-block">Team:</span><span class="fw-semibold text-dark" id="insp-p-team">-</span></div>
                                <div class="col-6"><span class="text-muted d-block">Department:</span><span class="fw-semibold text-dark" id="insp-p-dept">-</span></div>
                                <div class="col-6"><span class="text-muted d-block">Company:</span><span class="fw-semibold text-dark" id="insp-p-company">-</span></div>
                                <div class="col-6"><span class="text-muted d-block">LMS User:</span><span class="fw-semibold text-dark" id="insp-p-user-match">-</span></div>
                            </div>
                        </div>
                    </div>

                    <!-- Training Details -->
                    <div class="col-md-6">
                        <div class="card border-0 bg-light p-3 rounded-3 h-100">
                            <div class="fw-bold small text-dark mb-2 text-uppercase" style="font-size: 0.75rem; letter-spacing: 0.05em;">
                                <i class="ti ti-book me-1 text-primary"></i> 2. Training Event
                            </div>
                            <div class="row g-2 small">
                                <div class="col-12"><span class="text-muted d-block">Course Title:</span><span class="fw-semibold text-dark" id="insp-t-course">-</span></div>
                                <div class="col-6"><span class="text-muted d-block">Batch:</span><span class="fw-semibold text-dark" id="insp-t-batch">-</span></div>
                                <div class="col-6"><span class="text-muted d-block">Type:</span><span class="fw-semibold text-dark" id="insp-t-type">-</span></div>
                                <div class="col-6"><span class="text-muted d-block">Category:</span><span class="fw-semibold text-dark" id="insp-t-category">-</span></div>
                                <div class="col-6"><span class="text-muted d-block">Venue:</span><span class="fw-semibold text-dark" id="insp-t-venue">-</span></div>
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
