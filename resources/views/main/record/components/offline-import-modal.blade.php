<!-- OFFLINE TRAINING IMPORT MODAL (PHASE 2F.1) -->
<div class="modal fade" id="offlineImportModal" tabindex="-1" aria-labelledby="offlineImportModalLabel" aria-hidden="true" data-bs-backdrop="static">
    <div class="modal-dialog modal-dialog-centered modal-lg" id="offline-import-modal-dialog">
        <div class="modal-content border-0 shadow-lg rounded-4">
            
            <!-- MODAL HEADER -->
            <div class="modal-header border-bottom py-3 px-4 bg-white rounded-top-4">
                <div class="d-flex align-items-center gap-3">
                    <div class="avatar rounded-3 d-flex align-items-center justify-content-center flex-shrink-0"
                         style="width: 44px; height: 44px; background-color: #fef3c7; color: #d97706; border-radius: 10px;">
                        <i class="ti ti-file-spreadsheet fs-2"></i>
                    </div>
                    <div>
                        <h4 class="modal-title fw-bold text-dark mb-0" id="offlineImportModalLabel">Import Non KP OWL Records</h4>
                        <p class="text-muted small mb-0">Upload offline training records from Excel spreadsheet.</p>
                    </div>
                </div>
                <button type="button" class="btn-close" id="modal-import-btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>

            <!-- MODAL BODY -->
            <div class="modal-body p-4 bg-white">

                <!-- STATE 1: UPLOAD FORM -->
                <div id="modal-import-state-upload">
                    <div class="d-flex align-items-center justify-content-between mb-3 flex-wrap gap-2">
                        <div class="d-flex align-items-center gap-2">
                            <span class="badge bg-primary text-white rounded-circle d-inline-flex align-items-center justify-content-center" style="width: 24px; height: 24px; font-size: 0.75rem;">1</span>
                            <span class="fw-bold text-dark">Upload Spreadsheet</span>
                        </div>
                        <a href="{{ route('manage.record.offline.import.template') }}" class="btn btn-sm btn-outline-primary rounded-pill d-inline-flex align-items-center gap-1.5 px-3">
                            <i class="ti ti-download"></i>
                            <span>Download Template</span>
                        </a>
                    </div>

                    <!-- Dropzone Area -->
                    <div class="upload-dropzone border border-2 border-dashed rounded-4 p-4 text-center my-3 bg-light position-relative" id="modal-dropzone-container" style="border-color: #cbd5e1 !important; cursor: pointer; transition: all 0.2s ease;">
                        <input type="file" id="modal-excel-file-input" class="position-absolute top-0 start-0 w-100 h-100 opacity-0" style="cursor: pointer;" accept=".xlsx,.xls" />
                        <div class="dropzone-content py-2">
                            <div class="avatar avatar-md rounded-circle mx-auto mb-2" style="background-color: #fef3c7; color: #d97706; width: 52px; height: 52px;">
                                <i class="ti ti-upload fs-2"></i>
                            </div>
                            <h5 class="fw-bold text-dark mb-1">Choose an Excel file or drag & drop here</h5>
                            <p class="text-muted small mb-2">Supported formats: <strong>.xlsx, .xls</strong> (Max 20MB)</p>
                            <div class="d-inline-flex align-items-center gap-1 text-primary fw-medium small mt-1">
                                <i class="ti ti-paperclip"></i>
                                <span id="modal-selected-filename">No file selected</span>
                            </div>
                        </div>
                    </div>

                    <!-- Upload Error Box -->
                    <div class="alert alert-danger border-0 rounded-3 small mb-3 py-2 px-3" id="modal-upload-error" style="display: none;"></div>

                    <!-- Guidelines Alert -->
                    <div class="alert alert-info border-0 rounded-3 small mb-0" style="background-color: #f0f9ff; color: #0369a1;">
                        <div class="d-flex align-items-start gap-2">
                            <i class="ti ti-info-circle fs-3 flex-shrink-0 mt-0.5"></i>
                            <div>
                                <strong class="d-block mb-1">Spreadsheet Guidelines:</strong>
                                <ul class="mb-0 ps-3">
                                    <li>Use the official Non KP OWL template (2 header rows: Grouped categories & 44 child columns).</li>
                                    <li>Do not rename or delete required headers (Course Name, ID, Name).</li>
                                    <li>One row represents one participant record.</li>
                                    <li>Multiple participants from the same training will automatically be grouped into one event.</li>
                                    <li>Existing duplicate records will be skipped safely without corrupting data.</li>
                                </ul>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- STATE 2: VALIDATING / LOADING -->
                <div id="modal-import-state-loading" class="py-5 text-center" style="display: none;">
                    <div class="spinner-border text-primary" style="width: 3rem; height: 3rem; color: #d97706 !important;" role="status"></div>
                    <h5 class="fw-bold text-dark mt-3 mb-1" id="modal-loading-title">Validating Spreadsheet...</h5>
                    <p class="text-muted small mb-0" id="modal-loading-subtitle">Reading 44 columns, resolving header groups, and checking duplicates...</p>
                </div>

                <!-- STATE 3: PREVIEW PAYLOAD -->
                <div id="modal-import-state-preview" style="display: none;">
                    <div class="d-flex align-items-center justify-content-between mb-3 flex-wrap gap-2">
                        <div class="d-flex align-items-center gap-2">
                            <span class="badge bg-primary text-white rounded-circle d-inline-flex align-items-center justify-content-center" style="width: 24px; height: 24px; font-size: 0.75rem;">2</span>
                            <span class="fw-bold text-dark">Validation Preview</span>
                            <span class="badge bg-light text-secondary border ms-1" id="modal-preview-filename">-</span>
                        </div>
                        <button type="button" class="btn btn-sm btn-outline-secondary rounded-pill px-3" id="modal-btn-cancel-preview">
                            <i class="ti ti-arrow-left me-1"></i> Re-upload File
                        </button>
                    </div>

                    <!-- 6 Compact Stat Cards -->
                    <div class="row g-2 mb-3">
                        <div class="col-4 col-md-2">
                            <div class="card border-0 shadow-none bg-light p-2 rounded-3 text-center h-100">
                                <div class="text-muted extra-small fw-medium mb-0" style="font-size: 0.75rem;">Total</div>
                                <div class="fs-4 fw-bold text-dark mb-0" id="modal-kpi-total-rows">0</div>
                            </div>
                        </div>
                        <div class="col-4 col-md-2">
                            <div class="card border-0 shadow-none p-2 rounded-3 text-center h-100" style="background-color: #ecfdf5;">
                                <div class="extra-small fw-medium mb-0" style="color: #065f46; font-size: 0.75rem;">Valid</div>
                                <div class="fs-4 fw-bold mb-0" style="color: #047857;" id="modal-kpi-valid-rows">0</div>
                            </div>
                        </div>
                        <div class="col-4 col-md-2">
                            <div class="card border-0 shadow-none p-2 rounded-3 text-center h-100" style="background-color: #fffbeb;">
                                <div class="extra-small fw-medium mb-0" style="color: #92400e; font-size: 0.75rem;">Warnings</div>
                                <div class="fs-4 fw-bold mb-0" style="color: #d97706;" id="modal-kpi-warning-rows">0</div>
                            </div>
                        </div>
                        <div class="col-4 col-md-2">
                            <div class="card border-0 shadow-none p-2 rounded-3 text-center h-100" style="background-color: #f5f3ff;">
                                <div class="extra-small fw-medium mb-0" style="color: #5b21b6; font-size: 0.75rem;">Duplicates</div>
                                <div class="fs-4 fw-bold mb-0" style="color: #7c3aed;" id="modal-kpi-duplicate-rows">0</div>
                            </div>
                        </div>
                        <div class="col-4 col-md-2">
                            <div class="card border-0 shadow-none p-2 rounded-3 text-center h-100" style="background-color: #fef2f2;">
                                <div class="extra-small fw-medium mb-0" style="color: #991b1b; font-size: 0.75rem;">Invalid</div>
                                <div class="fs-4 fw-bold mb-0" style="color: #dc2626;" id="modal-kpi-invalid-rows">0</div>
                            </div>
                        </div>
                        <div class="col-4 col-md-2">
                            <div class="card border-0 shadow-none p-2 rounded-3 text-center h-100" style="background-color: #f0fdfa;">
                                <div class="extra-small fw-medium mb-0" style="color: #115e59; font-size: 0.75rem;">Events</div>
                                <div class="fs-4 fw-bold mb-0" style="color: #0f766e;" id="modal-kpi-training-events">0</div>
                            </div>
                        </div>
                    </div>

                    <!-- Warning/Error Alert -->
                    <div class="alert alert-warning border-0 rounded-3 small py-2 px-3 mb-3" id="modal-preview-alert" style="display: none;"></div>

                    <!-- Compact Preview Table Container -->
                    <div class="border rounded-3 overflow-hidden" style="max-height: 280px; overflow-y: auto;">
                        <table class="table table-sm table-hover table-vcenter mb-0" id="modal-preview-table" style="font-size: 0.8rem;">
                            <thead class="table-light sticky-top" style="z-index: 2;">
                                <tr>
                                    <th style="width: 50px;">Row</th>
                                    <th style="width: 90px;">Status</th>
                                    <th>Employee</th>
                                    <th>Course Name</th>
                                    <th style="width: 80px;">Batch</th>
                                    <th style="width: 70px;">Hours</th>
                                    <th style="width: 80px;">Progress</th>
                                    <th>Message</th>
                                </tr>
                            </thead>
                            <tbody id="modal-preview-tbody">
                                <!-- Populated dynamically by JavaScript -->
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- STATE 4: IMPORTING / LOADING -->
                <div id="modal-import-state-importing" class="py-5 text-center" style="display: none;">
                    <div class="spinner-border text-primary" style="width: 3rem; height: 3rem; color: #d97706 !important;" role="status"></div>
                    <h5 class="fw-bold text-dark mt-3 mb-1">Importing Training Records...</h5>
                    <p class="text-muted small mb-0">Executing database transaction, creating training sessions, and registering participants...</p>
                </div>

                <!-- STATE 5: RESULT / COMPLETED -->
                <div id="modal-import-state-result" class="py-4 text-center" style="display: none;">
                    <div class="avatar avatar-xl rounded-circle mx-auto mb-3" style="background-color: #ecfdf5; color: #059669; width: 64px; height: 64px;">
                        <i class="ti ti-check fs-1"></i>
                    </div>
                    <h3 class="fw-bold text-dark mb-1">Import Completed Successfully!</h3>
                    <p class="text-muted small mb-4">The offline training records have been processed and saved into the database.</p>

                    <!-- Result Metrics Grid -->
                    <div class="row g-3 justify-content-center mb-4 text-start">
                        <div class="col-6 col-sm-3">
                            <div class="card border-0 bg-light p-3 rounded-3 text-center">
                                <div class="text-muted small mb-1">Imported</div>
                                <div class="h3 fw-bold text-success mb-0" id="modal-result-imported">0</div>
                            </div>
                        </div>
                        <div class="col-6 col-sm-3">
                            <div class="card border-0 bg-light p-3 rounded-3 text-center">
                                <div class="text-muted small mb-1">Trainings Created</div>
                                <div class="h3 fw-bold text-primary mb-0" id="modal-result-created">0</div>
                            </div>
                        </div>
                        <div class="col-6 col-sm-3">
                            <div class="card border-0 bg-light p-3 rounded-3 text-center">
                                <div class="text-muted small mb-1">Trainings Reused</div>
                                <div class="h3 fw-bold text-teal mb-0" id="modal-result-reused">0</div>
                            </div>
                        </div>
                        <div class="col-6 col-sm-3">
                            <div class="card border-0 bg-light p-3 rounded-3 text-center">
                                <div class="text-muted small mb-1">Duplicates Skipped</div>
                                <div class="h3 fw-bold text-purple mb-0" id="modal-result-skipped">0</div>
                            </div>
                        </div>
                    </div>
                </div>

            </div>

            <!-- MODAL FOOTER -->
            <div class="modal-footer border-top py-3 px-4 bg-light rounded-bottom-4 d-flex justify-content-between">
                <!-- Left footer items -->
                <div>
                    <span class="text-muted extra-small" id="modal-footer-hint" style="font-size: 0.75rem;">Only .xlsx and .xls formats supported</span>
                </div>

                <!-- Right footer buttons -->
                <div class="d-flex align-items-center gap-2">
                    <!-- Upload state buttons -->
                    <div id="modal-footer-upload-buttons">
                        <button type="button" class="btn btn-secondary rounded-pill px-3" data-bs-dismiss="modal">Cancel</button>
                        <button type="button" class="btn btn-primary rounded-pill px-4" id="modal-btn-validate" style="background-color: #d97706; border-color: #d97706;" disabled>
                            <i class="ti ti-sparkles me-1"></i> Validate & Preview
                        </button>
                    </div>

                    <!-- Preview state buttons -->
                    <div id="modal-footer-preview-buttons" style="display: none;">
                        <button type="button" class="btn btn-outline-secondary rounded-pill px-3" id="modal-btn-preview-cancel">Cancel</button>
                        <button type="button" class="btn btn-primary rounded-pill px-4" id="modal-btn-confirm" style="background-color: #d97706; border-color: #d97706;">
                            <i class="ti ti-check me-1"></i> Confirm Import
                        </button>
                    </div>

                    <!-- Result state buttons -->
                    <div id="modal-footer-result-buttons" style="display: none;">
                        <button type="button" class="btn btn-primary rounded-pill px-4" id="modal-btn-result-close" style="background-color: #d97706; border-color: #d97706;">
                            Done
                        </button>
                    </div>
                </div>
            </div>

        </div>
    </div>
</div>
