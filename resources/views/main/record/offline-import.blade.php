@extends('layouts.sidebar-master')

@section('content')
<div class="page-body manage-record-page">
    <div class="container-xl">
        <!-- BREADCRUMB / BACK LINK -->
        <div class="mb-3">
            <a href="{{ route('manage.record.offline') }}" class="btn-back d-inline-flex align-items-center gap-1 text-decoration-none fw-medium text-secondary">
                <i class="ti ti-arrow-left"></i>
                <span>Back to Non KP OWL</span>
            </a>
        </div>

        <!-- PAGE TITLE CARD -->
        <div class="card border-0 shadow-sm p-3 p-md-4 mb-4 rounded-3 bg-white record-section-header">
            <div class="d-flex align-items-center justify-content-between flex-wrap gap-3">
                <div class="d-flex align-items-center gap-3">
                    <div class="rounded-3 d-flex align-items-center justify-content-center flex-shrink-0"
                         style="width: 54px; height: 54px; background-color: #fef3c7; color: #d97706; border-radius: 12px;">
                        <i class="ti ti-file-spreadsheet fs-1"></i>
                    </div>
                    <div>
                        <h2 class="h3 fw-bold mb-1 text-dark">Non KP OWL — Excel Import</h2>
                        <p class="text-muted small mb-0">Upload HRDC offline training records (.xlsx, .xls) to validate, preview, and import directly into the database.</p>
                    </div>
                </div>
            </div>
        </div>

        <!-- STEP 1: UPLOAD CARD -->
        <div class="card border-0 shadow-sm rounded-4 mb-4" id="upload-card">
            <div class="card-body p-4">
                <h4 class="card-title fw-bold text-dark mb-3 d-flex align-items-center gap-2">
                    <span class="badge bg-primary text-white rounded-circle d-inline-flex align-items-center justify-content-center" style="width: 26px; height: 26px; font-size: 0.8rem;">1</span>
                    <span>Upload Spreadsheet</span>
                </h4>

                <!-- Upload Dropzone Area -->
                <div class="upload-dropzone border border-2 border-dashed rounded-4 p-5 text-center my-3 bg-light position-relative" id="dropzone-container" style="border-color: #cbd5e1 !important; cursor: pointer; transition: all 0.2s ease;">
                    <input type="file" id="excel-file-input" class="position-absolute top-0 start-0 w-100 h-100 opacity-0" style="cursor: pointer;" accept=".xlsx,.xls" />
                    <div class="dropzone-content">
                        <div class="avatar avatar-lg rounded-circle mx-auto mb-3" style="background-color: #fef3c7; color: #d97706; width: 64px; height: 64px;">
                            <i class="ti ti-upload fs-1"></i>
                        </div>
                        <h5 class="fw-bold text-dark mb-1">Choose an Excel file or drag & drop here</h5>
                        <p class="text-muted small mb-2">Supported formats: <strong>.xlsx, .xls</strong> (Max 20MB)</p>
                        <div class="d-inline-flex align-items-center gap-1 text-primary fw-medium small mt-1">
                            <i class="ti ti-paperclip"></i>
                            <span id="selected-filename">No file selected</span>
                        </div>
                    </div>
                </div>

                <!-- Guidelines Alert -->
                <div class="alert alert-info border-0 rounded-3 small mb-4" style="background-color: #f0f9ff; color: #0369a1;">
                    <div class="d-flex align-items-start gap-2">
                        <i class="ti ti-info-circle fs-3 flex-shrink-0 mt-0.5"></i>
                        <div>
                            <strong class="d-block mb-1">Spreadsheet Guidelines & Format Rules:</strong>
                            <ul class="mb-0 ps-3">
                                <li><strong>Grouped Headers:</strong> Supports HRDC two-row grouped headers (e.g. <em>JOB INFORMATION, TRAINING INFORMATION, ATTENDANCE, OBLIGATION, L1, L2, L3</em>) as well as single-row headers.</li>
                                <li><strong>Training Event Grouping:</strong> Multiple participant rows belonging to the exact same training session (same course, batch, dates, type, venue) are grouped into <strong>one training record</strong> automatically.</li>
                                <li><strong>Safe Duplicate Policy:</strong> Participants already recorded for the same event (either inside the file or in the database) are safely skipped without overwriting existing data.</li>
                                <li><strong>Historical Preservation:</strong> Employee snapshot fields (name, grade, job position, team, department, company) are recorded exactly as entered in Excel.</li>
                            </ul>
                        </div>
                    </div>
                </div>

                <!-- Action Button -->
                <div class="d-flex justify-content-end gap-2">
                    <button type="button" id="btn-upload-preview" class="btn btn-primary rounded-3 px-4 d-inline-flex align-items-center gap-2" style="background-color: #d97706; border-color: #d97706;" disabled>
                        <i class="ti ti-sparkles"></i>
                        <span>Validate & Preview Records</span>
                    </button>
                </div>
            </div>
        </div>

        <!-- SKELETON / LOADING STATE -->
        <div class="card border-0 shadow-sm rounded-4 mb-4 py-5 text-center" id="loading-card" style="display: none;">
            <div class="card-body">
                <div class="spinner-border text-primary" style="width: 3rem; height: 3rem; color: #d97706 !important;" role="status"></div>
                <h5 class="fw-bold text-dark mt-3 mb-1" id="loading-title">Parsing Spreadsheet...</h5>
                <p class="text-muted small mb-0" id="loading-subtitle">Reading 44 columns, resolving header groups, and validating training rows...</p>
            </div>
        </div>

        <!-- STEP 2: PREVIEW CARD -->
        <div class="card border-0 shadow-sm rounded-4 mb-4" id="preview-card" style="display: none;">
            <div class="card-header bg-white border-0 pt-4 pb-0 px-4">
                <div class="d-flex align-items-center justify-content-between flex-wrap gap-2">
                    <h4 class="card-title fw-bold text-dark mb-0 d-flex align-items-center gap-2">
                        <span class="badge bg-primary text-white rounded-circle d-inline-flex align-items-center justify-content-center" style="width: 26px; height: 26px; font-size: 0.8rem;">2</span>
                        <span>Validation Preview</span>
                    </h4>
                    <div class="d-flex align-items-center gap-2">
                        <span class="badge bg-light text-secondary border px-2.5 py-1.5" id="preview-filename">-</span>
                        <button type="button" class="btn btn-sm btn-outline-secondary rounded-pill" id="btn-cancel-preview">
                            <i class="ti ti-x me-1"></i> Cancel & Re-upload
                        </button>
                    </div>
                </div>
            </div>

            <div class="card-body p-4">
                <!-- 6 KPI SUMMARY STATS -->
                <div class="row g-3 mb-4">
                    <!-- Total Rows -->
                    <div class="col-6 col-md-4 col-lg-2">
                        <div class="card border-0 shadow-none bg-light p-3 rounded-3 text-center h-100">
                            <div class="text-muted small fw-medium mb-1">Total Rows</div>
                            <div class="h3 fw-bold text-dark mb-0" id="kpi-total-rows">0</div>
                        </div>
                    </div>
                    <!-- Valid Rows -->
                    <div class="col-6 col-md-4 col-lg-2">
                        <div class="card border-0 shadow-none p-3 rounded-3 text-center h-100" style="background-color: #ecfdf5;">
                            <div class="small fw-medium mb-1" style="color: #065f46;">Valid / Importable</div>
                            <div class="h3 fw-bold mb-0" style="color: #047857;" id="kpi-valid-rows">0</div>
                        </div>
                    </div>
                    <!-- Warnings -->
                    <div class="col-6 col-md-4 col-lg-2">
                        <div class="card border-0 shadow-none p-3 rounded-3 text-center h-100" style="background-color: #fffbeb;">
                            <div class="small fw-medium mb-1" style="color: #92400e;">Warnings</div>
                            <div class="h3 fw-bold mb-0" style="color: #d97706;" id="kpi-warning-rows">0</div>
                        </div>
                    </div>
                    <!-- Duplicates -->
                    <div class="col-6 col-md-4 col-lg-2">
                        <div class="card border-0 shadow-none p-3 rounded-3 text-center h-100" style="background-color: #f5f3ff;">
                            <div class="small fw-medium mb-1" style="color: #5b21b6;">Duplicates (Skip)</div>
                            <div class="h3 fw-bold mb-0" style="color: #7c3aed;" id="kpi-duplicate-rows">0</div>
                        </div>
                    </div>
                    <!-- Invalid Rows -->
                    <div class="col-6 col-md-4 col-lg-2">
                        <div class="card border-0 shadow-none p-3 rounded-3 text-center h-100" style="background-color: #fef2f2;">
                            <div class="small fw-medium mb-1" style="color: #991b1b;">Invalid (Errors)</div>
                            <div class="h3 fw-bold mb-0" style="color: #dc2626;" id="kpi-invalid-rows">0</div>
                        </div>
                    </div>
                    <!-- Training Events -->
                    <div class="col-6 col-md-4 col-lg-2">
                        <div class="card border-0 shadow-none p-3 rounded-3 text-center h-100" style="background-color: #f0fdfa;">
                            <div class="small fw-medium mb-1" style="color: #115e59;">Training Events</div>
                            <div class="h4 fw-bold mb-0" style="color: #0f766e;" id="kpi-training-events">0</div>
                        </div>
                    </div>
                </div>

                <!-- Unknown Headers Notice (if any) -->
                <div class="alert alert-warning border-0 rounded-3 small mb-4" id="unknown-headers-alert" style="display: none;">
                    <div class="d-flex align-items-center gap-2">
                        <i class="ti ti-alert-triangle fs-3 flex-shrink-0"></i>
                        <div>
                            <strong>Note:</strong> Some columns were not recognized and will be skipped:
                            <span id="unknown-headers-list" class="fw-semibold"></span>
                        </div>
                    </div>
                </div>

                <!-- PREVIEW TABLE FILTER TABS -->
                <div class="d-flex align-items-center justify-content-between flex-wrap gap-2 mb-3">
                    <div class="btn-group rounded-pill p-1 bg-light border" role="group" id="filter-tabs">
                        <button type="button" class="btn btn-sm btn-white active rounded-pill px-3 filter-tab-btn" data-filter="all">All (<span id="count-tab-all">0</span>)</button>
                        <button type="button" class="btn btn-sm btn-ghost-secondary rounded-pill px-3 filter-tab-btn" data-filter="valid">Valid (<span id="count-tab-valid">0</span>)</button>
                        <button type="button" class="btn btn-sm btn-ghost-secondary rounded-pill px-3 filter-tab-btn" data-filter="warning">Warnings (<span id="count-tab-warning">0</span>)</button>
                        <button type="button" class="btn btn-sm btn-ghost-secondary rounded-pill px-3 filter-tab-btn" data-filter="duplicate">Duplicates (<span id="count-tab-duplicate">0</span>)</button>
                        <button type="button" class="btn btn-sm btn-ghost-secondary rounded-pill px-3 filter-tab-btn" data-filter="invalid">Invalid (<span id="count-tab-invalid">0</span>)</button>
                    </div>
                    <div class="text-muted small">
                        Showing first <span id="preview-showing-count">0</span> rows of <span id="preview-total-count">0</span>
                    </div>
                </div>

                <!-- PREVIEW TABLE -->
                <div class="table-responsive border rounded-3 mb-4">
                    <table class="table table-hover align-middle mb-0" id="preview-table">
                        <thead class="bg-light text-muted small text-uppercase">
                            <tr>
                                <th style="width: 50px;">Row</th>
                                <th style="width: 110px;">Status</th>
                                <th>Participant / Employee</th>
                                <th>Training Course</th>
                                <th>Batch / Dates</th>
                                <th>Hours</th>
                                <th>Notes / Validation</th>
                                <th class="text-end" style="width: 80px;">Action</th>
                            </tr>
                        </thead>
                        <tbody id="preview-table-body">
                            <!-- Populated dynamically via JS -->
                        </tbody>
                    </table>
                </div>

                <!-- FOOTER ACTIONS -->
                <div class="d-flex align-items-center justify-content-between flex-wrap gap-3 pt-2">
                    <button type="button" class="btn btn-outline-secondary rounded-3 px-4" id="btn-footer-cancel">
                        Cancel
                    </button>
                    <button type="button" class="btn btn-success rounded-3 px-4 d-inline-flex align-items-center gap-2" id="btn-confirm-import">
                        <i class="ti ti-check"></i>
                        <span>Confirm & Import (<span id="btn-import-count">0</span> Records)</span>
                    </button>
                </div>
            </div>
        </div>

        <!-- STEP 3: RESULT SUMMARY CARD -->
        <div class="card border-0 shadow-sm rounded-4 mb-4" id="result-card" style="display: none;">
            <div class="card-body p-5 text-center">
                <div class="avatar avatar-xl rounded-circle mx-auto mb-3" style="background-color: #ecfdf5; color: #059669; width: 72px; height: 72px;">
                    <i class="ti ti-circle-check fs-1"></i>
                </div>
                <h3 class="fw-bold text-dark mb-1">Import Completed Successfully</h3>
                <p class="text-muted small mb-4">Offline training records have been processed and safely stored into the database.</p>

                <!-- 6 Result Metrics -->
                <div class="row g-3 justify-content-center mb-4 text-start" style="max-width: 800px; margin: 0 auto;">
                    <div class="col-sm-6 col-md-4">
                        <div class="card border-0 bg-light p-3 rounded-3 text-center">
                            <div class="text-muted small">Total Processed</div>
                            <div class="h3 fw-bold text-dark mb-0" id="res-total-processed">0</div>
                        </div>
                    </div>
                    <div class="col-sm-6 col-md-4">
                        <div class="card border-0 p-3 rounded-3 text-center" style="background-color: #ecfdf5;">
                            <div class="small fw-semibold" style="color: #065f46;">Participants Imported</div>
                            <div class="h3 fw-bold mb-0" style="color: #047857;" id="res-imported-participants">0</div>
                        </div>
                    </div>
                    <div class="col-sm-6 col-md-4">
                        <div class="card border-0 p-3 rounded-3 text-center" style="background-color: #f5f3ff;">
                            <div class="small fw-semibold" style="color: #5b21b6;">Duplicates Skipped</div>
                            <div class="h3 fw-bold mb-0" style="color: #7c3aed;" id="res-duplicates-skipped">0</div>
                        </div>
                    </div>
                    <div class="col-sm-6 col-md-4">
                        <div class="card border-0 p-3 rounded-3 text-center" style="background-color: #fef2f2;">
                            <div class="small fw-semibold" style="color: #991b1b;">Invalid Rows Skipped</div>
                            <div class="h3 fw-bold mb-0" style="color: #dc2626;" id="res-invalid-skipped">0</div>
                        </div>
                    </div>
                    <div class="col-sm-6 col-md-4">
                        <div class="card border-0 p-3 rounded-3 text-center" style="background-color: #f0fdfa;">
                            <div class="small fw-semibold" style="color: #115e59;">New Trainings Created</div>
                            <div class="h3 fw-bold mb-0" style="color: #0f766e;" id="res-trainings-created">0</div>
                        </div>
                    </div>
                    <div class="col-sm-6 col-md-4">
                        <div class="card border-0 p-3 rounded-3 text-center" style="background-color: #eff6ff;">
                            <div class="small fw-semibold" style="color: #1e40af;">Existing Trainings Reused</div>
                            <div class="h3 fw-bold mb-0" style="color: #2563eb;" id="res-trainings-reused">0</div>
                        </div>
                    </div>
                </div>

                <div class="d-flex align-items-center justify-content-center gap-3">
                    <button type="button" class="btn btn-outline-secondary rounded-pill px-4" id="btn-import-another">
                        <i class="ti ti-upload me-1"></i> Import Another Spreadsheet
                    </button>
                    <a href="{{ route('manage.record.offline') }}" class="btn btn-primary rounded-pill px-4" style="background-color: #d97706; border-color: #d97706;">
                        <i class="ti ti-table me-1"></i> View Non KP OWL Records
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- ROW DETAIL INSPECTION MODAL -->
<div class="modal fade" id="previewRowModal" tabindex="-1" aria-labelledby="previewRowModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-xl modal-dialog-scrollable">
        <div class="modal-content rounded-4 border-0 shadow">
            <div class="modal-header border-0 pb-0">
                <div class="d-flex align-items-center gap-2">
                    <span class="avatar rounded-3" style="background-color: #fef3c7; color: #d97706; width: 42px; height: 42px;">
                        <i class="ti ti-file-search fs-2"></i>
                    </span>
                    <div>
                        <h5 class="modal-title fw-bold text-dark mb-0" id="previewRowModalLabel">Row Data Inspection</h5>
                        <div class="text-muted extra-small" style="font-size: 0.75rem;">Excel Row <span id="modal-row-num">-</span> Normalized Fields</div>
                    </div>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>

            <div class="modal-body py-3">
                <!-- Status & Error Alert -->
                <div id="modal-messages-container" class="mb-3"></div>

                <div class="row g-3">
                    <!-- 1. Participant / Employee Info -->
                    <div class="col-md-6">
                        <div class="card border-0 bg-light p-3 rounded-3 h-100">
                            <div class="fw-bold small text-dark mb-2 text-uppercase" style="font-size: 0.75rem; letter-spacing: 0.05em;">
                                <i class="ti ti-user me-1 text-primary"></i> 1. Participant Information
                            </div>
                            <div class="row g-2 small">
                                <div class="col-6">
                                    <span class="text-muted d-block">Employee ID (NIP):</span>
                                    <span class="fw-semibold text-dark" id="modal-p-nip">-</span>
                                </div>
                                <div class="col-6">
                                    <span class="text-muted d-block">Name:</span>
                                    <span class="fw-semibold text-dark" id="modal-p-name">-</span>
                                </div>
                                <div class="col-6">
                                    <span class="text-muted d-block">Grade:</span>
                                    <span class="fw-semibold text-dark" id="modal-p-grade">-</span>
                                </div>
                                <div class="col-6">
                                    <span class="text-muted d-block">Job Position:</span>
                                    <span class="fw-semibold text-dark" id="modal-p-position">-</span>
                                </div>
                                <div class="col-6">
                                    <span class="text-muted d-block">Team:</span>
                                    <span class="fw-semibold text-dark" id="modal-p-team">-</span>
                                </div>
                                <div class="col-6">
                                    <span class="text-muted d-block">Department:</span>
                                    <span class="fw-semibold text-dark" id="modal-p-dept">-</span>
                                </div>
                                <div class="col-6">
                                    <span class="text-muted d-block">Company:</span>
                                    <span class="fw-semibold text-dark" id="modal-p-company">-</span>
                                </div>
                                <div class="col-6">
                                    <span class="text-muted d-block">LMS User Match:</span>
                                    <span id="modal-p-user-match">-</span>
                                </div>
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
                                <div class="col-12">
                                    <span class="text-muted d-block">Course Name:</span>
                                    <span class="fw-semibold text-dark" id="modal-t-course">-</span>
                                </div>
                                <div class="col-6">
                                    <span class="text-muted d-block">Sub Course Name:</span>
                                    <span class="fw-semibold text-dark" id="modal-t-subcourse">-</span>
                                </div>
                                <div class="col-6">
                                    <span class="text-muted d-block">Batch:</span>
                                    <span class="fw-semibold text-dark" id="modal-t-batch">-</span>
                                </div>
                                <div class="col-6">
                                    <span class="text-muted d-block">Type:</span>
                                    <span class="fw-semibold text-dark" id="modal-t-type">-</span>
                                </div>
                                <div class="col-6">
                                    <span class="text-muted d-block">Category:</span>
                                    <span class="fw-semibold text-dark" id="modal-t-category">-</span>
                                </div>
                                <div class="col-6">
                                    <span class="text-muted d-block">Level:</span>
                                    <span class="fw-semibold text-dark" id="modal-t-level">-</span>
                                </div>
                                <div class="col-6">
                                    <span class="text-muted d-block">Venue:</span>
                                    <span class="fw-semibold text-dark" id="modal-t-venue">-</span>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- 3. Classification & Schedule -->
                    <div class="col-md-6">
                        <div class="card border-0 bg-light p-3 rounded-3 h-100">
                            <div class="fw-bold small text-dark mb-2 text-uppercase" style="font-size: 0.75rem; letter-spacing: 0.05em;">
                                <i class="ti ti-calendar-event me-1 text-primary"></i> 3. Schedule, Provider & Classification
                            </div>
                            <div class="row g-2 small">
                                <div class="col-6">
                                    <span class="text-muted d-block">Start Date:</span>
                                    <span class="fw-semibold text-dark" id="modal-t-start-date">-</span>
                                </div>
                                <div class="col-6">
                                    <span class="text-muted d-block">Finish Date:</span>
                                    <span class="fw-semibold text-dark" id="modal-t-finish-date">-</span>
                                </div>
                                <div class="col-6">
                                    <span class="text-muted d-block">Learning Hours:</span>
                                    <span class="fw-semibold text-dark" id="modal-t-hours">-</span>
                                </div>
                                <div class="col-6">
                                    <span class="text-muted d-block">Duration (Seconds):</span>
                                    <span class="fw-semibold text-dark" id="modal-t-duration">-</span>
                                </div>
                                <div class="col-6">
                                    <span class="text-muted d-block">Main Classification:</span>
                                    <span class="fw-semibold text-dark" id="modal-t-main-class">-</span>
                                </div>
                                <div class="col-6">
                                    <span class="text-muted d-block">Sub Classification:</span>
                                    <span class="fw-semibold text-dark" id="modal-t-sub-class">-</span>
                                </div>
                                <div class="col-6">
                                    <span class="text-muted d-block">Corporate KPI:</span>
                                    <span class="fw-semibold text-dark" id="modal-t-kpi">-</span>
                                </div>
                                <div class="col-6">
                                    <span class="text-muted d-block">Provider / Name:</span>
                                    <span class="fw-semibold text-dark" id="modal-t-provider">-</span>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- 4. Obligations & Evaluation (L1-L3) -->
                    <div class="col-md-6">
                        <div class="card border-0 bg-light p-3 rounded-3 h-100">
                            <div class="fw-bold small text-dark mb-2 text-uppercase" style="font-size: 0.75rem; letter-spacing: 0.05em;">
                                <i class="ti ti-star me-1 text-primary"></i> 4. Participation, Bond & Evaluation
                            </div>
                            <div class="row g-2 small">
                                <div class="col-6">
                                    <span class="text-muted d-block">Progress / Score:</span>
                                    <span class="fw-semibold text-dark" id="modal-o-progress">-</span>
                                </div>
                                <div class="col-6">
                                    <span class="text-muted d-block">Attendance:</span>
                                    <span class="fw-semibold text-dark" id="modal-o-attendance">-</span>
                                </div>
                                <div class="col-6">
                                    <span class="text-muted d-block">Bond Status:</span>
                                    <span class="fw-semibold text-dark" id="modal-o-bond-status">-</span>
                                </div>
                                <div class="col-6">
                                    <span class="text-muted d-block">Bond Amount:</span>
                                    <span class="fw-semibold text-dark" id="modal-o-bond-amount">-</span>
                                </div>
                                <div class="col-6">
                                    <span class="text-muted d-block">L1 Program:</span>
                                    <span class="fw-semibold text-dark" id="modal-e-l1">-</span>
                                </div>
                                <div class="col-6">
                                    <span class="text-muted d-block">L2 (Numerical / Verbal):</span>
                                    <span class="fw-semibold text-dark" id="modal-e-l2">-</span>
                                </div>
                                <div class="col-6">
                                    <span class="text-muted d-block">L3 (Numerical / Verbal):</span>
                                    <span class="fw-semibold text-dark" id="modal-e-l3">-</span>
                                </div>
                                <div class="col-6">
                                    <span class="text-muted d-block">PIC:</span>
                                    <span class="fw-semibold text-dark" id="modal-e-pic">-</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="modal-footer border-0 pt-0">
                <button type="button" class="btn btn-secondary rounded-pill px-4" data-bs-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>
@endsection

@section('style')
<style>
    .manage-record-page {
        background: #fff;
    }
    .upload-dropzone:hover {
        border-color: #d97706 !important;
        background-color: #fffbeb !important;
    }
    .filter-tab-btn.active {
        background-color: #fff !important;
        box-shadow: 0 1px 3px rgba(0,0,0,0.08);
        color: #1e293b !important;
        font-weight: 600;
    }
    .extra-small {
        font-size: 0.75rem;
    }
</style>
@endsection

@section('script')
<script>
$(document).ready(function () {
    let currentToken = null;
    let previewRowsData = [];
    let currentFilter = 'all';

    const $fileInput = $('#excel-file-input');
    const $btnPreview = $('#btn-upload-preview');
    const $selectedFilename = $('#selected-filename');
    const $uploadCard = $('#upload-card');
    const $loadingCard = $('#loading-card');
    const $previewCard = $('#preview-card');
    const $resultCard = $('#result-card');

    // CSRF Token setup for AJAX
    $.ajaxSetup({
        headers: {
            'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
        }
    });

    // File selection
    $fileInput.on('change', function () {
        const file = this.files[0];
        if (file) {
            $selectedFilename.text(file.name + ' (' + (file.size / 1024).toFixed(1) + ' KB)');
            $btnPreview.prop('disabled', false);
        } else {
            $selectedFilename.text('No file selected');
            $btnPreview.prop('disabled', true);
        }
    });

    // Upload & Analyze
    $btnPreview.on('click', function () {
        const file = $fileInput[0].files[0];
        if (!file) {
            alert('Please select an Excel file first.');
            return;
        }

        const formData = new FormData();
        formData.append('file', file);

        $uploadCard.hide();
        $previewCard.hide();
        $resultCard.hide();
        $loadingCard.show();
        $('#loading-title').text('Parsing Spreadsheet...');
        $('#loading-subtitle').text('Reading columns, resolving 2-row header groups, and validating training rows...');

        $.ajax({
            url: "{{ route('manage.record.offline.import.preview') }}",
            type: 'POST',
            data: formData,
            contentType: false,
            processData: false,
            success: function (res) {
                $loadingCard.hide();
                if (res.success && res.data) {
                    renderPreview(res.data);
                } else {
                    alert(res.message || 'Validation failed.');
                    $uploadCard.show();
                }
            },
            error: function (xhr) {
                $loadingCard.hide();
                $uploadCard.show();
                let errMsg = 'Failed to parse Excel file.';
                if (xhr.responseJSON && xhr.responseJSON.message) {
                    errMsg = xhr.responseJSON.message;
                }
                alert(errMsg);
            }
        });
    });

    function renderPreview(data) {
        currentToken = data.token;
        previewRowsData = data.preview_rows || [];
        const stats = data.stats || {};

        $('#preview-filename').text(data.filename);
        $('#kpi-total-rows').text(stats.total_rows || 0);
        $('#kpi-valid-rows').text(stats.valid || 0);
        $('#kpi-warning-rows').text(stats.warning || 0);
        $('#kpi-duplicate-rows').text(stats.duplicate || 0);
        $('#kpi-invalid-rows').text(stats.invalid || 0);

        const newTrainings = stats.new_trainings || 0;
        const reusedTrainings = stats.reused_trainings || 0;
        $('#kpi-training-events').html(
            '<span title="New: ' + newTrainings + ' | Existing: ' + reusedTrainings + '">' +
            (newTrainings + reusedTrainings) +
            ' <small class="fw-normal text-muted" style="font-size: 0.7rem;">(' + newTrainings + ' new, ' + reusedTrainings + ' exist)</small></span>'
        );

        // Tab counts
        $('#count-tab-all').text(stats.total_rows || 0);
        $('#count-tab-valid').text(stats.valid || 0);
        $('#count-tab-warning').text(stats.warning || 0);
        $('#count-tab-duplicate').text(stats.duplicate || 0);
        $('#count-tab-invalid').text(stats.invalid || 0);

        $('#preview-showing-count').text(previewRowsData.length);
        $('#preview-total-count').text(data.total_preview || previewRowsData.length);

        // Unknown headers
        if (data.unknown_headers && data.unknown_headers.length > 0) {
            $('#unknown-headers-list').text(data.unknown_headers.join(', '));
            $('#unknown-headers-alert').show();
        } else {
            $('#unknown-headers-alert').hide();
        }

        // Import button count
        const importableCount = stats.valid || 0;
        $('#btn-import-count').text(importableCount);
        if (importableCount <= 0) {
            $('#btn-confirm-import').prop('disabled', true);
        } else {
            $('#btn-confirm-import').prop('disabled', false);
        }

        currentFilter = 'all';
        $('.filter-tab-btn').removeClass('active');
        $('[data-filter="all"]').addClass('active');

        renderTableRows();
        $previewCard.show();
    }

    function renderTableRows() {
        const $tbody = $('#preview-table-body');
        $tbody.empty();

        let visibleRows = previewRowsData.filter(function (row) {
            if (currentFilter === 'all') return true;
            return row.status === currentFilter;
        });

        if (visibleRows.length === 0) {
            $tbody.append('<tr><td colspan="8" class="text-center py-4 text-muted">No rows match the selected filter.</td></tr>');
            return;
        }

        visibleRows.forEach(function (row, idx) {
            const d = row.data || {};
            let badgeClass = 'bg-secondary-lt';
            let badgeLabel = row.status;

            if (row.status === 'valid') {
                badgeClass = 'bg-success text-white';
                badgeLabel = 'Valid';
            } else if (row.status === 'warning') {
                badgeClass = 'bg-warning text-dark';
                badgeLabel = 'Warning';
            } else if (row.status === 'duplicate') {
                badgeClass = 'bg-purple text-white';
                badgeLabel = 'Duplicate';
            } else if (row.status === 'invalid') {
                badgeClass = 'bg-danger text-white';
                badgeLabel = 'Invalid';
            }

            let userMatchBadge = row.user_matched
                ? '<span class="badge bg-success-lt ms-1" style="font-size: 0.65rem;" title="Matched with LMS user"><i class="ti ti-check"></i> Matched</span>'
                : '<span class="badge bg-secondary-lt ms-1" style="font-size: 0.65rem;" title="User account not found in LMS (user_id will be NULL)"><i class="ti ti-help"></i> Not in LMS</span>';

            let msgHtml = '';
            if (row.messages && row.messages.length > 0) {
                msgHtml = '<ul class="mb-0 ps-3 extra-small ' + (row.status === 'invalid' ? 'text-danger' : 'text-muted') + '">';
                row.messages.forEach(function (m) {
                    msgHtml += '<li>' + escapeHtml(m) + '</li>';
                });
                msgHtml += '</ul>';
            } else {
                msgHtml = '<span class="text-muted extra-small">Ready to import</span>';
            }

            let dates = (d.start_date || '-') + (d.finish_date && d.finish_date !== d.start_date ? ' to ' + d.finish_date : '');
            let hours = (d.learning_hours || 0) + ' hrs';

            let tr = [
                '<tr>',
                '<td class="text-muted fw-semibold">' + row.excel_row + '</td>',
                '<td><span class="badge ' + badgeClass + ' px-2 py-1">' + badgeLabel + '</span></td>',
                '<td>',
                    '<div class="fw-bold text-dark">' + escapeHtml(d.name || '-') + userMatchBadge + '</div>',
                    '<div class="text-muted extra-small">NIP: ' + escapeHtml(d.employee_id || '-') + ' | ' + escapeHtml(d.job_position || '-') + '</div>',
                '</td>',
                '<td>',
                    '<div class="fw-semibold text-dark">' + escapeHtml(d.course_name || '-') + '</div>',
                    '<div class="text-muted extra-small">' + escapeHtml(d.type || '-') + ' | ' + escapeHtml(d.category || '-') + '</div>',
                '</td>',
                '<td>',
                    '<div class="small text-dark">' + escapeHtml(d.batch ? 'Batch: ' + d.batch : 'No Batch') + '</div>',
                    '<div class="text-muted extra-small">' + dates + '</div>',
                '</td>',
                '<td><span class="fw-semibold text-dark">' + hours + '</span></td>',
                '<td>' + msgHtml + '</td>',
                '<td class="text-end">',
                    '<button type="button" class="btn btn-sm btn-ghost-primary rounded-circle btn-view-row-detail" data-index="' + idx + '" title="View Full Details">',
                        '<i class="ti ti-eye"></i>',
                    '</button>',
                '</td>',
                '</tr>'
            ].join('');

            $tbody.append(tr);
        });
    }

    // Filter tabs click
    $('#filter-tabs').on('click', '.filter-tab-btn', function () {
        $('.filter-tab-btn').removeClass('active');
        $(this).addClass('active');
        currentFilter = $(this).data('filter');
        renderTableRows();
    });

    // View Row Detail Modal
    $(document).on('click', '.btn-view-row-detail', function () {
        const idx = $(this).data('index');
        const visibleRows = previewRowsData.filter(function (r) {
            return currentFilter === 'all' || r.status === currentFilter;
        });
        const row = visibleRows[idx];
        if (!row) return;

        const d = row.data || {};

        $('#modal-row-num').text(row.excel_row);

        // Status / message banner
        let msgContainer = $('#modal-messages-container');
        msgContainer.empty();
        if (row.messages && row.messages.length > 0) {
            let alertClass = row.status === 'invalid' ? 'alert-danger' : (row.status === 'warning' ? 'alert-warning' : 'alert-info');
            let alertHtml = '<div class="alert ' + alertClass + ' border-0 rounded-3 p-2 small mb-0"><ul class="mb-0 ps-3">';
            row.messages.forEach(function (m) { alertHtml += '<li>' + escapeHtml(m) + '</li>'; });
            alertHtml += '</ul></div>';
            msgContainer.html(alertHtml);
        }

        // 1. Participant
        $('#modal-p-nip').text(d.employee_id || '-');
        $('#modal-p-name').text(d.name || '-');
        $('#modal-p-grade').text(d.grade || '-');
        $('#modal-p-position').text(d.job_position || '-');
        $('#modal-p-team').text(d.team || '-');
        $('#modal-p-dept').text(d.department || '-');
        $('#modal-p-company').text(d.company || '-');
        $('#modal-p-user-match').html(row.user_matched
            ? '<span class="badge bg-success-lt">Matched (User ID: ' + d.user_id + ')</span>'
            : '<span class="badge bg-secondary-lt">Not Found (user_id = NULL)</span>'
        );

        // 2. Training
        $('#modal-t-course').text(d.course_name || '-');
        $('#modal-t-subcourse').text(d.sub_course_name || '-');
        $('#modal-t-batch').text(d.batch || '-');
        $('#modal-t-type').text(d.type || '-');
        $('#modal-t-category').text(d.category || '-');
        $('#modal-t-level').text(d.level || '-');
        $('#modal-t-venue').text(d.venue || '-');

        // 3. Schedule & Classification
        $('#modal-t-start-date').text(d.start_date || '-');
        $('#modal-t-finish-date').text(d.finish_date || '-');
        $('#modal-t-hours').text(d.learning_hours || '0');
        $('#modal-t-duration').text(d.duration_seconds ? (d.duration_seconds + ' s') : '-');
        $('#modal-t-main-class').text(d.main_classification || '-');
        $('#modal-t-sub-class').text(d.sub_classification || '-');
        $('#modal-t-kpi').text((d.counted_corporate_kpi ? 'Yes' : 'No') + (d.sub_corporate_kpi ? ' (' + d.sub_corporate_kpi + ')' : ''));
        $('#modal-t-provider').text((d.provider || '-') + (d.provider_name ? ' / ' + d.provider_name : ''));

        // 4. Obligations & Evaluation
        $('#modal-o-progress').text((d.progress !== null ? d.progress + '%' : '-') + (d.assessment_score !== null ? ' | Score: ' + d.assessment_score : ''));
        $('#modal-o-attendance').text((d.attendance ? 'Present' : 'Absent') + (d.attendance_reason ? ' (' + d.attendance_reason + ')' : ''));
        $('#modal-o-bond-status').text((d.bond_status ? 'Yes' : 'No') + (d.bond_end_date ? ' (End: ' + d.bond_end_date + ')' : ''));
        $('#modal-o-bond-amount').text(d.bond_amount ? 'IDR ' + Number(d.bond_amount).toLocaleString() : '-');
        $('#modal-e-l1').text(d.l1_program !== null ? d.l1_program : '-');
        $('#modal-e-l2').text((d.l2_numerical !== null ? 'Num: ' + d.l2_numerical : '-') + ' | ' + (d.l2_verbal || '-'));
        $('#modal-e-l3').text((d.l3_numerical !== null ? 'Num: ' + d.l3_numerical : '-') + ' | ' + (d.l3_verbal || '-'));
        $('#modal-e-pic').text(d.pic || '-');

        $('#previewRowModal').modal('show');
    });

    // Cancel import
    function handleCancel() {
        if (!currentToken) {
            resetToUpload();
            return;
        }

        $.ajax({
            url: "{{ route('manage.record.offline.import.cancel') }}",
            type: 'POST',
            data: { token: currentToken },
            complete: function () {
                resetToUpload();
            }
        });
    }

    $('#btn-cancel-preview, #btn-footer-cancel').on('click', handleCancel);

    // Confirm & Execute import
    $('#btn-confirm-import').on('click', function () {
        if (!currentToken) return;

        if (!confirm('Are you sure you want to import valid records into the database? Existing duplicates will be skipped.')) {
            return;
        }

        $previewCard.hide();
        $loadingCard.show();
        $('#loading-title').text('Executing Import Transaction...');
        $('#loading-subtitle').text('Grouping training events, inserting participants, and maintaining database integrity...');

        $.ajax({
            url: "{{ route('manage.record.offline.import.confirm') }}",
            type: 'POST',
            data: { token: currentToken },
            success: function (res) {
                $loadingCard.hide();
                if (res.success && res.result) {
                    renderResult(res.result);
                } else {
                    alert(res.message || 'Import execution failed.');
                    $previewCard.show();
                }
            },
            error: function (xhr) {
                $loadingCard.hide();
                $previewCard.show();
                let errMsg = 'Failed to execute import.';
                if (xhr.responseJSON && xhr.responseJSON.message) {
                    errMsg = xhr.responseJSON.message;
                }
                alert(errMsg);
            }
        });
    });

    function renderResult(result) {
        $('#res-total-processed').text(result.rows_processed || 0);
        $('#res-imported-participants').text(result.imported || 0);
        $('#res-duplicates-skipped').text(result.skipped_duplicates || 0);
        $('#res-invalid-skipped').text(result.invalid || 0);
        $('#res-trainings-created').text(result.trainings_created || 0);
        $('#res-trainings-reused').text(result.trainings_reused || 0);

        currentToken = null;
        $resultCard.show();
    }

    function resetToUpload() {
        currentToken = null;
        previewRowsData = [];
        $fileInput.val('');
        $selectedFilename.text('No file selected');
        $btnPreview.prop('disabled', true);
        $previewCard.hide();
        $loadingCard.hide();
        $resultCard.hide();
        $uploadCard.show();
    }

    $('#btn-import-another').on('click', resetToUpload);

    function escapeHtml(text) {
        if (!text) return '';
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
