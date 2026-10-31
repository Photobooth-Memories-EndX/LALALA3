@extends('layouts.sidebar-master')

@section('title', 'Import PTKP Data — OWL LMS')

@section('style')
<link rel="stylesheet" href="{{ asset('css/manage/ptkp-import.css') }}">
@endsection

@section('content')
<div class="page-body ptkp-import-page py-4">
    <div class="container-xl">
        <!-- BREADCRUMB / BACK LINK -->
        <div class="mb-3">
            <a href="{{ route('manage.record.offline') }}" class="btn-back-ptkp d-inline-flex align-items-center gap-1.5 text-decoration-none fw-medium">
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
                        <h2 class="h3 fw-bold mb-1 text-dark">Import PTKP Data</h2>
                        <p class="text-muted small mb-0">Upload and validate PTKP offline training records before importing into OWL LMS with automatic employee identity synchronization.</p>
                    </div>
                </div>
                <div>
                    <a href="{{ route('manage.record.offline.import-history.index') }}" class="btn btn-outline-secondary rounded-pill d-inline-flex align-items-center gap-2 px-3 py-2 me-2" id="btn-ptkp-history"><i class="ti ti-history fs-2"></i><span>Import History</span></a>
                    <a href="{{ route('manage.record.offline.import-ptkp.template') }}" class="btn btn-outline-primary rounded-pill d-inline-flex align-items-center gap-2 px-3 py-2" id="btn-download-ptkp-template">
                        <i class="ti ti-download fs-2"></i>
                        <span>Download PTKP Template (.xlsx)</span>
                    </a>
                </div>
            </div>
        </div>

        <!-- 4-STAGE PROCESS INDICATOR -->
        <div class="card border-0 shadow-sm p-3 mb-4 rounded-3 bg-white">
            <div class="ptkp-steps">
                <div class="ptkp-step-item active" id="ptkp-step-ind-1"><div class="ptkp-step-bubble">1</div><div class="ptkp-step-title">Upload Spreadsheet</div></div>
                <div class="ptkp-step-item" id="ptkp-step-ind-2"><div class="ptkp-step-bubble">2</div><div class="ptkp-step-title">Validation Preview</div></div>
                <div class="ptkp-step-item" id="ptkp-step-ind-3"><div class="ptkp-step-bubble">3</div><div class="ptkp-step-title">Review Data</div></div>
                <div class="ptkp-step-item" id="ptkp-step-ind-4"><div class="ptkp-step-bubble">4</div><div class="ptkp-step-title">Import Records</div></div>
            </div>
        </div>

        <!-- STAGE 1: UPLOAD SPREADSHEET -->
        <div class="card border-0 shadow-sm rounded-4 mb-4" id="ptkp-upload-card">
            <div class="card-body p-4">
                <div class="d-flex align-items-center justify-content-between mb-3 flex-wrap gap-2">
                    <div class="d-flex align-items-center gap-2">
                        <span class="badge bg-primary text-white rounded-circle d-inline-flex align-items-center justify-content-center" style="width: 26px; height: 26px; font-size: 0.8rem;">1</span>
                        <h4 class="card-title fw-bold text-dark mb-0">Upload PTKP Spreadsheet</h4>
                    </div>
                </div>

                <!-- Dropzone Area -->
                <div class="ptkp-dropzone p-5 text-center my-3 position-relative" id="ptkp-dropzone">
                    <input type="file" id="ptkp-file-input" class="position-absolute top-0 start-0 w-100 h-100 opacity-0" style="cursor: pointer;" accept=".xlsx,.xls" />
                    <div class="dropzone-content py-3">
                        <div class="avatar avatar-lg rounded-circle mx-auto mb-3" style="background-color: #fef3c7; color: #d97706; width: 64px; height: 64px;">
                            <i class="ti ti-upload fs-1"></i>
                        </div>
                        <h5 class="fw-bold text-dark mb-1">Choose an Excel file or drag & drop here</h5>
                        <p class="text-muted small mb-2">Supported formats: <strong>.xlsx, .xls</strong> (Max 20MB)</p>
                        <div class="d-inline-flex align-items-center gap-1 text-primary fw-medium small mt-1">
                            <i class="ti ti-paperclip"></i>
                            <span id="ptkp-selected-filename">No file selected</span>
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
                <div class="d-flex justify-content-end gap-2">
                    <button type="button" id="ptkp-btn-validate" class="btn btn-primary rounded-3 px-4 d-inline-flex align-items-center gap-2" style="background-color: #d97706; border-color: #d97706;" disabled>
                        <i class="ti ti-sparkles"></i>
                        <span>Validate & Preview Records</span>
                    </button>
                </div>
            </div>
        </div>

        <!-- LOADING SKELETON STATE -->
        <div class="card border-0 shadow-sm rounded-4 mb-4 py-5 text-center" id="ptkp-loading-card" style="display: none;">
            <div class="card-body">
                <div class="spinner-border text-warning" style="width: 3.5rem; height: 3.5rem; color: #d97706 !important;" role="status"></div>
                <h5 class="fw-bold text-dark mt-3 mb-1" id="ptkp-loading-title">Validating Spreadsheet...</h5>
                <p class="text-muted small mb-0" id="ptkp-loading-subtitle">Reading PTKP columns, verifying Employee IDs, and checking duplicates...</p>
            </div>
        </div>

        <!-- STAGE 2: VALIDATION PREVIEW CARD -->
        <div class="card border-0 shadow-sm rounded-4 mb-4" id="ptkp-preview-card" style="display: none;">
            <div class="card-header bg-white border-0 pt-4 pb-0 px-4">
                <div class="d-flex align-items-center justify-content-between flex-wrap gap-2">
                    <div class="d-flex align-items-center gap-2">
                        <span class="badge bg-primary text-white rounded-circle d-inline-flex align-items-center justify-content-center" style="width: 26px; height: 26px; font-size: 0.8rem;">2</span>
                        <h4 class="card-title fw-bold text-dark mb-0">PTKP Validation & Review</h4>
                        <span class="badge bg-light text-secondary border px-2.5 py-1.5 ms-2" id="ptkp-preview-filename">-</span>
                    </div>
                    <div>
                        <button type="button" class="btn btn-sm btn-outline-secondary rounded-pill px-3" id="ptkp-btn-cancel-preview">
                            <i class="ti ti-arrow-left me-1"></i> Cancel & Re-upload
                        </button>
                    </div>
                </div>
            </div>

            <div class="card-body p-4">
                <!-- Header Check Diagnostic Alert -->
                <div class="alert alert-warning border-0 rounded-3 small mb-4" id="ptkp-header-alert" style="display: none;">
                    <div class="d-flex align-items-start gap-2">
                        <i class="ti ti-alert-triangle fs-3 flex-shrink-0 mt-0.5"></i>
                        <div>
                            <strong class="d-block mb-1">Header Validation Notes:</strong>
                            <div id="ptkp-header-alert-body"></div>
                        </div>
                    </div>
                </div>

                <!-- NO NEW RECORDS TO IMPORT ALERT BANNER -->
                <div class="alert alert-warning border-0 rounded-3 mb-4 p-3" id="ptkp-no-new-records-alert" style="display: none; background-color: #fffbeb; color: #92400e;">
                    <div class="d-flex align-items-center gap-3">
                        <div class="avatar rounded-circle bg-warning text-white flex-shrink-0" style="width: 40px; height: 40px;">
                            <i class="ti ti-alert-circle fs-2"></i>
                        </div>
                        <div>
                            <h5 class="fw-bold mb-1">No New Records to Import</h5>
                            <p class="mb-0 small" id="ptkp-no-new-records-desc">All records already exist in the database. No new records will be imported.</p>
                        </div>
                    </div>
                </div>

                <!-- 6 KPI SUMMARY STAT CARDS -->
                <div class="row g-2 mb-3">
                    <div class="col-6 col-md-2">
                        <div class="card border-0 shadow-none bg-light p-3 rounded-3 text-center h-100 ptkp-kpi-card">
                            <div class="text-muted small fw-medium mb-1">Total Rows</div>
                            <div class="h3 fw-bold text-dark mb-0" id="ptkp-kpi-total">0</div>
                        </div>
                    </div>
                    <div class="col-6 col-md-2">
                        <div class="card border-0 shadow-none p-3 rounded-3 text-center h-100 ptkp-kpi-card ptkp-kpi-valid">
                            <div class="small fw-semibold mb-1" style="color: #15803d;">VALID</div>
                            <div class="h3 fw-bold mb-0" style="color: #15803d;" id="ptkp-kpi-valid">0</div>
                        </div>
                    </div>
                    <div class="col-6 col-md-2">
                        <div class="card border-0 shadow-none p-3 rounded-3 text-center h-100 ptkp-kpi-card ptkp-kpi-warning">
                            <div class="small fw-semibold mb-1" style="color: #92400e;">WARNING</div>
                            <div class="h3 fw-bold mb-0" style="color: #92400e;" id="ptkp-kpi-warning">0</div>
                        </div>
                    </div>
                    <div class="col-6 col-md-2">
                        <div class="card border-0 shadow-none p-3 rounded-3 text-center h-100 ptkp-kpi-card ptkp-kpi-duplicate">
                            <div class="small fw-semibold mb-1" style="color: #a16207;">DUPLICATE</div>
                            <div class="h3 fw-bold mb-0" style="color: #a16207;" id="ptkp-kpi-duplicate">0</div>
                        </div>
                    </div>
                    <div class="col-6 col-md-2">
                        <div class="card border-0 shadow-none p-3 rounded-3 text-center h-100 ptkp-kpi-card ptkp-kpi-invalid">
                            <div class="small fw-semibold mb-1" style="color: #b91c1c;">INVALID</div>
                            <div class="h3 fw-bold mb-0" style="color: #b91c1c;" id="ptkp-kpi-invalid">0</div>
                        </div>
                    </div>
                    <div class="col-6 col-md-2">
                        <div class="card border-0 shadow-none p-3 rounded-3 text-center h-100 ptkp-kpi-card ptkp-kpi-invalid-company">
                            <div class="small fw-semibold mb-1" style="color: #c2410c;">INVALID COMPANY</div>
                            <div class="h3 fw-bold mb-0" style="color: #c2410c;" id="ptkp-kpi-invalid-company">0</div>
                        </div>
                    </div>
                </div>

                <!-- FILTER TABS & SEARCH -->
                <div class="row g-3 align-items-center justify-content-between mb-3">
                    <div class="col-lg-7">
                        <div class="d-flex align-items-center gap-1.5 flex-wrap">
                            <button type="button" class="btn btn-sm btn-light border ptkp-filter-tab active" data-filter="all">All (<span id="count-tab-all">0</span>)</button>
                            <button type="button" class="btn btn-sm btn-light border ptkp-filter-tab" data-filter="valid">VALID (<span id="count-tab-valid">0</span>)</button>
                            <button type="button" class="btn btn-sm btn-light border ptkp-filter-tab" data-filter="warning">WARNING (<span id="count-tab-warning">0</span>)</button>
                            <button type="button" class="btn btn-sm btn-light border ptkp-filter-tab" data-filter="duplicate">DUPLICATE (<span id="count-tab-duplicate">0</span>)</button>
                            <button type="button" class="btn btn-sm btn-light border ptkp-filter-tab" data-filter="invalid">INVALID (<span id="count-tab-invalid">0</span>)</button>
                            <button type="button" class="btn btn-sm btn-light border ptkp-filter-tab" data-filter="invalid_company">INVALID COMPANY (<span id="count-tab-invalid-company">0</span>)</button>
                        </div>
                    </div>
                    <div class="col-lg-5">
                        <div class="d-flex align-items-center justify-content-lg-end gap-2">
                            <label for="ptkp-page-size" class="small text-muted mb-0">Show:</label>
                            <select id="ptkp-page-size" class="form-select form-select-sm" style="width: 85px;">
                                <option value="15">15</option>
                                <option value="25" selected>25</option>
                                <option value="50">50</option>
                                <option value="100">100</option>
                            </select>
                        </div>
                    </div>
                </div>

                <!-- SEARCH FIELDS -->
                <div class="row g-2 mb-3 bg-light p-2.5 rounded-3 border">
                    <div class="col-md-3">
                        <input type="text" id="ptkp-search-empid" class="form-control form-control-sm" placeholder="Filter Employee ID...">
                    </div>
                    <div class="col-md-3">
                        <input type="text" id="ptkp-search-name" class="form-control form-control-sm" placeholder="Filter Employee Name...">
                    </div>
                    <div class="col-md-3">
                        <input type="text" id="ptkp-search-course" class="form-control form-control-sm" placeholder="Filter Course Title...">
                    </div>
                    <div class="col-md-3">
                        <input type="text" id="ptkp-search-pic" class="form-control form-control-sm" placeholder="Filter PIC...">
                    </div>
                </div>

                <!-- PREVIEW TABLE -->
                <div class="ptkp-table-wrapper mb-3">
                    <table class="table table-hover align-middle mb-0 ptkp-table" id="ptkp-preview-table">
                        <thead>
                            <tr>
                                <th style="width: 50px;" class="text-center">No</th>
                                <th style="width: 140px;">Status</th>
                                <th style="width: 120px;">Employee ID</th>
                                <th>Employee Name (from users)</th>
                                <th>Company</th>
                                <th>Course Title</th>
                                <th style="width: 110px;">Type</th>
                                <th style="width: 130px;">PIC</th>
                                <th style="min-width: 200px;">Validation Details</th>
                                <th style="width: 70px;" class="text-center">Action</th>
                            </tr>
                        </thead>
                        <tbody id="ptkp-preview-tbody">
                            <!-- Dynamically populated via JS -->
                        </tbody>
                    </table>
                </div>

                <!-- PAGINATION FOOTER -->
                <div class="d-flex align-items-center justify-content-between flex-wrap gap-2 mb-4">
                    <div class="text-muted small" id="ptkp-pagination-info">
                        Showing 0 of 0 records
                    </div>
                    <nav aria-label="Page navigation">
                        <ul class="pagination pagination-sm mb-0" id="ptkp-pagination-nav"></ul>
                    </nav>
                </div>

                <!-- FOOTER ACTIONS -->
                <div class="d-flex align-items-center justify-content-between flex-wrap gap-3 pt-3 border-top">
                    <button type="button" class="btn btn-outline-secondary rounded-3 px-4" id="ptkp-btn-footer-cancel">
                        Cancel
                    </button>
                    <button type="button" class="btn btn-success rounded-3 px-4 d-inline-flex align-items-center gap-2" id="ptkp-btn-confirm" disabled>
                        <i class="ti ti-check"></i>
                        <span>Confirm & Import (<span id="ptkp-btn-confirm-count">0</span> Records)</span>
                    </button>
                </div>
            </div>
        </div>

        <!-- STAGE 3: RESULT SUMMARY CARD -->
        <div class="card border-0 shadow-sm rounded-4 mb-4" id="ptkp-result-card" style="display: none;">
            <div class="card-body p-5 text-center">
                <div class="avatar avatar-xl rounded-circle mx-auto mb-3" style="background-color: #ecfdf5; color: #059669; width: 72px; height: 72px;">
                    <i class="ti ti-circle-check fs-1"></i>
                </div>
                <h3 class="fw-bold text-dark mb-1">PTKP Import Completed</h3>
                <p class="text-muted small mb-4">Summary of spreadsheet processing and offline training record persistence.</p>

                <!-- 8 Result Metrics -->
                <div class="row g-3 justify-content-center mb-4 text-start" style="max-width: 900px; margin: 0 auto;">
                    <div class="col-sm-6 col-md-3">
                        <div class="card border-0 bg-light p-3 rounded-3 text-center">
                            <div class="text-muted small">Total Processed</div>
                            <div class="h3 fw-bold text-dark mb-0" id="res-total-processed">0</div>
                        </div>
                    </div>
                    <div class="col-sm-6 col-md-3">
                        <div class="card border-0 p-3 rounded-3 text-center" style="background-color: #ecfdf5;">
                            <div class="small fw-semibold" style="color: #065f46;">Successfully Imported</div>
                            <div class="h3 fw-bold mb-0" style="color: #047857;" id="res-imported-participants">0</div>
                        </div>
                    </div>
                    <div class="col-sm-6 col-md-3">
                        <div class="card border-0 p-3 rounded-3 text-center" style="background-color: #fefce8;">
                            <div class="small fw-semibold" style="color: #a16207;">Duplicates Skipped</div>
                            <div class="h3 fw-bold mb-0" style="color: #a16207;" id="res-duplicates-skipped">0</div>
                        </div>
                    </div>
                    <div class="col-sm-6 col-md-3">
                        <div class="card border-0 p-3 rounded-3 text-center" style="background-color: #fef2f2;">
                            <div class="small fw-semibold" style="color: #991b1b;">Invalid Skipped</div>
                            <div class="h3 fw-bold mb-0" style="color: #dc2626;" id="res-invalid-skipped">0</div>
                        </div>
                    </div>
                    <div class="col-sm-6 col-md-3">
                        <div class="card border-0 p-3 rounded-3 text-center" style="background-color: #fffbeb;">
                            <div class="small fw-semibold" style="color: #92400e;">Warnings Imported</div>
                            <div class="h3 fw-bold mb-0" style="color: #92400e;" id="res-warnings-imported">0</div>
                        </div>
                    </div>
                    <div class="col-sm-6 col-md-3">
                        <div class="card border-0 p-3 rounded-3 text-center" style="background-color: #fff7ed;">
                            <div class="small fw-semibold" style="color: #c2410c;">Invalid Company Imported</div>
                            <div class="h3 fw-bold mb-0" style="color: #c2410c;" id="res-invalid-company-imported">0</div>
                        </div>
                    </div>
                    <div class="col-sm-6 col-md-3">
                        <div class="card border-0 p-3 rounded-3 text-center" style="background-color: #f0fdfa;">
                            <div class="small fw-semibold" style="color: #115e59;">New Trainings Created</div>
                            <div class="h3 fw-bold mb-0" style="color: #0f766e;" id="res-trainings-created">0</div>
                        </div>
                    </div>
                    <div class="col-sm-6 col-md-3">
                        <div class="card border-0 p-3 rounded-3 text-center" style="background-color: #eff6ff;">
                            <div class="small fw-semibold" style="color: #1e40af;">Trainings Reused</div>
                            <div class="h3 fw-bold mb-0" style="color: #2563eb;" id="res-trainings-reused">0</div>
                        </div>
                    </div>
                </div>

                <div class="d-flex align-items-center justify-content-center gap-3">
                    <button type="button" class="btn btn-outline-secondary rounded-pill px-4" id="ptkp-btn-import-another">
                        <i class="ti ti-upload me-1"></i> Import Another Spreadsheet
                    </button>
                    <a href="{{ route('manage.record.offline') }}" class="btn btn-primary rounded-pill px-4" style="background-color: #d97706; border-color: #d97706;">
                        <i class="ti ti-table me-1"></i> View Offline Records
                    </a>
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
                    <span class="avatar rounded-3" style="background-color: #fef3c7; color: #d97706; width: 42px; height: 42px;">
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
        cancel:  "{{ route('manage.record.offline.import-ptkp.cancel') }}"
    };
</script>
<script src="{{ asset('js/manage/ptkp-import.js') }}"></script>
@endsection
