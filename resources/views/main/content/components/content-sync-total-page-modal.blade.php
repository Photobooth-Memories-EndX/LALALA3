<!-- Synchronize Total Pages Modal -->
<div class="modal modal-blur fade" id="modalTotalPageSynchronization" tabindex="-1" aria-labelledby="modalTotalPageSynchronizationLabel" aria-hidden="true" data-bs-backdrop="static">
    <div class="modal-dialog modal-dialog-centered modal-md">
        <div class="modal-content rounded-4 border-0 shadow">
            <div class="modal-header border-0 pb-0">
                <h5 class="modal-title fw-bold text-dark d-flex align-items-center gap-2" id="modalTotalPageSynchronizationLabel">
                    <i class="ti ti-files text-primary fs-2"></i>
                    <span id="syncTotalPageModalTitle">Synchronize Total Pages</span>
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close" id="syncTotalPageCloseHeaderBtn"></button>
            </div>

            <!-- Initial Prompt / Confirmation State -->
            <div class="modal-body py-3" id="syncTotalPagePromptBody">
                <p class="text-muted small mb-3">
                    Synchronize total page information for document content using the available PDF metadata.
                </p>

                <div class="p-3 rounded-3 bg-light border mb-3">
                    <div class="fw-semibold text-dark small mb-2 d-flex align-items-center gap-1">
                        <i class="ti ti-info-circle text-primary me-1"></i> Synchronization Guidelines:
                    </div>
                    <ul class="text-muted small mb-0 ps-3">
                        <li class="mb-1">PDF documents will be checked.</li>
                        <li class="mb-1">Existing synchronized values will be preserved when already accurate.</li>
                        <li>Missing values may use the configured default (35 pages).</li>
                    </ul>
                </div>

                <div class="alert alert-info py-2 px-3 small mb-0 d-flex align-items-center gap-2">
                    <i class="ti ti-file-description fs-3 text-info flex-shrink-0"></i>
                    <span>Only Document type contents with PDF files will have their pages counted and synchronized.</span>
                </div>
            </div>

            <!-- Loading State -->
            <div class="modal-body py-4 text-center" id="syncTotalPageLoadingBody" style="display: none;">
                <div class="spinner-border text-primary mb-3" style="width: 3rem; height: 3rem;" role="status">
                    <span class="visually-hidden">Loading...</span>
                </div>
                <h5 class="fw-bold mb-1" id="syncTotalPageLoadingTitle">Synchronizing Document Pages...</h5>
                <p class="text-muted small mb-3" id="syncTotalPageLoadingText">Reading PDF metadata and synchronizing document pages. Please do not refresh or close this window.</p>

                <!-- Progress bar -->
                <div class="progress progress-sm rounded-pill mb-2" style="height: 6px;">
                    <div class="progress-bar bg-primary" id="syncTotalPageProgressBar" role="progressbar" style="width: 0%" aria-valuenow="0" aria-valuemin="0" aria-valuemax="100"></div>
                </div>
                <div class="small text-muted" id="syncTotalPageProgressCounter">Preparing...</div>
            </div>

            <!-- Result Summary State -->
            <div class="modal-body py-3" id="syncTotalPageResultBody" style="display: none;">
                <div class="text-center mb-3">
                    <span class="avatar avatar-md bg-success-lt text-success rounded-circle mb-2 d-inline-flex align-items-center justify-content-center">
                        <i class="ti ti-check fs-2"></i>
                    </span>
                    <h5 class="fw-bold mb-0 text-success">Total Page Synchronization Complete</h5>
                    <p class="text-muted small mb-0" id="syncTotalPageResultMessage">Document pages have been synchronized successfully.</p>
                </div>

                <!-- 4 Summary Cards -->
                <div class="row g-2 mb-3">
                    <!-- Card 1: Total Documents -->
                    <div class="col-6 col-sm-3">
                        <div class="p-2 border rounded-3 bg-light text-center h-100">
                            <div class="text-muted fw-semibold small mb-1" style="font-size: 0.75rem;">
                                <i class="ti ti-files me-1 text-primary"></i>Total Documents
                            </div>
                            <div class="h3 fw-bold text-dark mb-0" id="resTotalDocuments">0</div>
                        </div>
                    </div>

                    <!-- Card 2: Updated from PDF -->
                    <div class="col-6 col-sm-3">
                        <div class="p-2 border rounded-3 bg-light text-center h-100">
                            <div class="text-muted fw-semibold small mb-1" style="font-size: 0.75rem;">
                                <i class="ti ti-refresh me-1 text-success"></i>Updated from PDF
                            </div>
                            <div class="h3 fw-bold text-success mb-0" id="resUpdatedPdf">0</div>
                        </div>
                    </div>

                    <!-- Card 3: Already Synchronized -->
                    <div class="col-6 col-sm-3">
                        <div class="p-2 border rounded-3 bg-light text-center h-100">
                            <div class="text-muted fw-semibold small mb-1" style="font-size: 0.75rem;">
                                <i class="ti ti-check me-1 text-info"></i>Already Synced
                            </div>
                            <div class="h3 fw-bold text-info mb-0" id="resAlreadySynced">0</div>
                        </div>
                    </div>

                    <!-- Card 4: Missing / Defaulted -->
                    <div class="col-6 col-sm-3">
                        <div class="p-2 border rounded-3 bg-light text-center h-100">
                            <div class="text-muted fw-semibold small mb-1" style="font-size: 0.75rem;">
                                <i class="ti ti-alert-triangle me-1 text-warning"></i>Defaulted (35)
                            </div>
                            <div class="h3 fw-bold text-warning mb-0" id="resMissingDefaulted">0</div>
                        </div>
                    </div>
                </div>

                <!-- Collapsible Missing / Defaulted Details -->
                <div id="syncTotalPageMissingContainer" class="mt-3 pt-2 border-top" style="display: none;">
                    <div class="d-flex justify-content-between align-items-center mb-2">
                        <span class="small fw-semibold text-secondary" id="syncTotalPageMissingHeader">Missing / Defaulted Items (0)</span>
                        <button type="button" class="btn btn-sm btn-outline-secondary px-2 py-1" id="btnToggleMissingDetails" data-bs-toggle="collapse" data-bs-target="#collapseMissingDetails" aria-expanded="false" aria-controls="collapseMissingDetails">
                            <span id="toggleMissingDetailsText">View Details</span>
                            <i class="ti ti-chevron-down ms-1" id="toggleMissingDetailsIcon"></i>
                        </button>
                    </div>
                    <div class="collapse" id="collapseMissingDetails">
                        <div class="border rounded-3 p-2 bg-light-subtle" style="max-height: 200px; overflow-y: auto;">
                            <div class="list-group list-group-flush list-group-hoverable" id="syncTotalPageMissingList"></div>
                            <div class="text-muted small text-center pt-2 pb-1" id="syncTotalPageMissingMoreNote" style="display: none;"></div>
                        </div>
                    </div>
                </div>

                <div class="small text-muted text-center mt-2" id="syncTotalPageResultNote">
                    Document page counts have been reviewed and updated based on PDF metadata.
                </div>
            </div>

            <!-- Error State Alert Container -->
            <div class="px-3 pb-2" id="syncTotalPageErrorContainer" style="display: none;">
                <div class="alert alert-danger py-2 px-3 small mb-0 d-flex align-items-center gap-2">
                    <i class="ti ti-alert-circle fs-3 flex-shrink-0"></i>
                    <span id="syncTotalPageErrorText">Unable to synchronize total pages. Please try again.</span>
                </div>
            </div>

            <div class="modal-footer bg-light d-flex justify-content-end align-items-center gap-2 border-0">
                <button type="button" class="btn btn-outline-secondary px-3" data-bs-dismiss="modal" id="btnCancelSyncTotalPage">
                    Cancel
                </button>
                <button type="button" class="btn btn-primary px-3 d-inline-flex align-items-center gap-1" id="btnExecuteSyncTotalPage">
                    <i class="ti ti-refresh me-1" id="btnExecuteSyncTotalPageIcon"></i>
                    <span id="btnExecuteSyncTotalPageText">Synchronize Total Pages</span>
                </button>
                <button type="button" class="btn btn-primary px-4" id="btnCloseSyncTotalPage" data-bs-dismiss="modal" style="display: none;">
                    Close
                </button>
            </div>
        </div>
    </div>
</div>
