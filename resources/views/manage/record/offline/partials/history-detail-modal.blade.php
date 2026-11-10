<!-- Batch Detail & Audit Rows Modal -->
<div class="modal fade" id="importDetailModal" tabindex="-1" aria-labelledby="importDetailModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-xl modal-dialog-centered modal-dialog-scrollable">
        <div class="modal-content shadow-lg">
            <div class="modal-header border-bottom py-3 px-4">
                <div class="d-flex align-items-center gap-2 flex-wrap">
                    <h5 class="modal-title fw-bold text-dark mb-0" id="importDetailModalLabel">
                        <i class="ti ti-file-analytics text-primary me-1"></i>Import Batch Detail
                    </h5>
                    <span class="badge bg-secondary-lt text-muted font-monospace" id="detail-uuid">-</span>
                </div>
                <div class="d-flex align-items-center gap-2">
                    <a href="#" class="btn btn-sm btn-outline-primary d-inline-flex align-items-center gap-1" id="modal-btn-download" style="display: none;" title="Download Original Audit Excel">
                        <i class="ti ti-download"></i>
                        <span>Download Excel</span>
                    </a>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
            </div>

            <div class="modal-body p-4">
                <!-- Loading Spinner -->
                <div id="modal-loading-spinner" class="text-center py-5">
                    <div class="spinner-border text-primary" role="status">
                        <span class="visually-hidden">Loading...</span>
                    </div>
                    <div class="text-muted small mt-2">Fetching batch detail and audit records...</div>
                </div>

                <!-- Modal Detail Content -->
                <div id="modal-detail-content" style="display: none;">
                    <!-- Overview Card -->
                    <div class="card border rounded-3 mb-4 shadow-none">
                        <div class="card-body p-3 p-md-4">
                            <div class="row g-3">
                                <div class="col-6 col-md-3">
                                    <div class="text-muted small fw-semibold text-uppercase">Source Type</div>
                                    <div class="fw-bold fs-3 text-dark mt-1" id="detail-source">-</div>
                                </div>
                                <div class="col-6 col-md-3">
                                    <div class="text-muted small fw-semibold text-uppercase">Batch Status</div>
                                    <div class="mt-1" id="detail-status">-</div>
                                </div>
                                <div class="col-6 col-md-3">
                                    <div class="text-muted small fw-semibold text-uppercase">Imported By</div>
                                    <div class="fw-semibold text-dark mt-1" id="detail-user">-</div>
                                </div>
                                <div class="col-6 col-md-3">
                                    <div class="text-muted small fw-semibold text-uppercase">Duration</div>
                                    <div class="fw-semibold text-dark mt-1" id="detail-duration">-</div>
                                </div>
                                <div class="col-12 col-md-6">
                                    <div class="text-muted small fw-semibold text-uppercase">Original Filename</div>
                                    <div class="fw-semibold text-dark mt-1 text-truncate" id="detail-filename">-</div>
                                </div>
                                <div class="col-6 col-md-3">
                                    <div class="text-muted small fw-semibold text-uppercase">Started At</div>
                                    <div class="text-muted small mt-1" id="detail-started">-</div>
                                </div>
                                <div class="col-6 col-md-3">
                                    <div class="text-muted small fw-semibold text-uppercase">Finished At</div>
                                    <div class="text-muted small mt-1" id="detail-finished">-</div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Error Summary Box (If Failed) -->
                    <div class="alert alert-danger mb-4 shadow-sm" id="detail-error-box" style="display: none;">
                        <div class="d-flex align-items-start gap-2">
                            <i class="ti ti-alert-triangle fs-2 text-danger flex-shrink-0 mt-0.5"></i>
                            <div>
                                <h6 class="fw-bold mb-1">Execution Failure / Rollback</h6>
                                <p class="small mb-0" id="detail-error-message">-</p>
                            </div>
                        </div>
                    </div>

                    <!-- Statistics Grid -->
                    <h6 class="fw-bold text-dark text-uppercase small mb-2">
                        <i class="ti ti-chart-bar text-muted me-1"></i>Evaluation & Persistence Metrics
                    </h6>
                    <div class="row g-2 mb-4">
                        <div class="col-4 col-md">
                            <div class="batch-stats-box">
                                <div class="stats-val text-dark" id="stat-total-rows">0</div>
                                <div class="stats-lbl">Total Evaluated</div>
                            </div>
                        </div>
                        <div class="col-4 col-md">
                            <div class="batch-stats-box">
                                <div class="stats-val text-success" id="stat-valid-rows">0</div>
                                <div class="stats-lbl">Valid</div>
                            </div>
                        </div>
                        <div class="col-4 col-md">
                            <div class="batch-stats-box">
                                <div class="stats-val text-warning" id="stat-warning-rows">0</div>
                                <div class="stats-lbl">Warning</div>
                            </div>
                        </div>
                        <div class="col-4 col-md">
                            <div class="batch-stats-box">
                                <div class="stats-val text-info" id="stat-duplicate-rows">0</div>
                                <div class="stats-lbl">Duplicate</div>
                            </div>
                        </div>
                        <div class="col-4 col-md">
                            <div class="batch-stats-box">
                                <div class="stats-val text-danger" id="stat-invalid-rows">0</div>
                                <div class="stats-lbl">Invalid</div>
                            </div>
                        </div>
                        <div class="col-4 col-md">
                            <div class="batch-stats-box">
                                <div class="stats-val text-success" id="stat-imported-rows">0</div>
                                <div class="stats-lbl">Imported</div>
                            </div>
                        </div>
                        <div class="col-4 col-md">
                            <div class="batch-stats-box">
                                <div class="stats-val text-muted" id="stat-skipped-rows">0</div>
                                <div class="stats-lbl">Skipped</div>
                            </div>
                        </div>
                        <div class="col-4 col-md">
                            <div class="batch-stats-box">
                                <div class="stats-val text-danger" id="stat-failed-rows">0</div>
                                <div class="stats-lbl">Failed</div>
                            </div>
                        </div>
                        <div class="col-6 col-md">
                            <div class="batch-stats-box">
                                <div class="stats-val text-primary" id="stat-trainings-created">0</div>
                                <div class="stats-lbl">Training Created</div>
                            </div>
                        </div>
                        <div class="col-6 col-md">
                            <div class="batch-stats-box">
                                <div class="stats-val text-secondary" id="stat-trainings-reused">0</div>
                                <div class="stats-lbl">Training Reused</div>
                            </div>
                        </div>
                    </div>

                    <!-- Row Records Section -->
                    <div class="d-flex align-items-center justify-content-between mb-2 flex-wrap gap-2">
                        <h6 class="fw-bold text-dark text-uppercase small mb-0">
                            <i class="ti ti-list text-muted me-1"></i>Spreadsheet Rows Audit Log
                        </h6>
                    </div>

                    <!-- Row Filters -->
                    <div class="card border rounded-3 p-3 mb-3 bg-light-subtle shadow-none">
                        <div class="row g-2 align-items-center">
                            <div class="col-12 col-md-3">
                                <select class="form-select form-select-sm" id="row-filter-action">
                                    <option value="">All Actions</option>
                                    <option value="IMPORTED">Action: IMPORTED</option>
                                    <option value="SKIPPED">Action: SKIPPED</option>
                                    <option value="FAILED">Action: FAILED</option>
                                </select>
                            </div>
                            <div class="col-12 col-md-3">
                                <select class="form-select form-select-sm" id="row-filter-status">
                                    <option value="">All Validation Statuses</option>
                                    <option value="VALID">Status: VALID</option>
                                    <option value="WARNING">Status: WARNING</option>
                                    <option value="DUPLICATE">Status: DUPLICATE</option>
                                    <option value="INVALID">Status: INVALID</option>
                                    <option value="INVALID COMPANY">Status: INVALID COMPANY</option>
                                </select>
                            </div>
                            <div class="col-12 col-md-4">
                                <input type="text" class="form-control form-control-sm" id="row-filter-search" placeholder="Search Employee ID, Name, or Course...">
                            </div>
                            <div class="col-12 col-md-2 d-flex gap-1">
                                <button type="button" class="btn btn-sm btn-primary flex-fill" id="btn-row-filter-apply">Filter</button>
                                <button type="button" class="btn btn-sm btn-outline-secondary" id="btn-row-filter-reset" title="Reset Filters"><i class="ti ti-refresh"></i></button>
                            </div>
                        </div>
                    </div>

                    <!-- Detail Rows Table Container -->
                    <div class="table-responsive">
                        <table class="table table-vcenter table-hover table-striped card-table fs-5 border" id="batch-rows-table" style="width: 100%;">
                            <thead>
                                <tr>
                                    <th class="w-1">Row</th>
                                    <th>Sheet</th>
                                    <th>Employee ID</th>
                                    <th>Employee Name</th>
                                    <th>Course Name</th>
                                    <th class="text-center">Validation</th>
                                    <th class="text-center">Action</th>
                                    <th>Notes</th>
                                    <th class="text-center">Refs</th>
                                </tr>
                            </thead>
                            <tbody></tbody>
                        </table>
                    </div>
                </div>
            </div>

            <div class="modal-footer py-2 px-4 border-top">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>
