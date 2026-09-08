<!-- Content Detail Modal -->
<div class="modal modal-blur fade" id="contentDetailModal" tabindex="-1" aria-labelledby="contentDetailModalTitle" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content rounded-4 border-0 shadow">
            <div class="modal-header border-0 pb-0">
                <div class="d-flex align-items-center gap-2 flex-grow-1 min-w-0">
                    <div class="avatar avatar-md bg-primary-lt text-primary rounded-3 flex-shrink-0">
                        <i class="ti ti-file-description fs-2"></i>
                    </div>
                    <div class="min-w-0 flex-grow-1">
                        <h5 class="modal-title fw-bold text-dark mb-0 text-truncate" id="contentDetailModalTitle">Content Details</h5>
                        <small class="text-muted" id="contentDetailModalSubtitle">View comprehensive information for this content</small>
                    </div>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>

            <!-- Loading State -->
            <div class="modal-body py-5 text-center" id="contentDetailLoading">
                <div class="spinner-border text-primary mb-3" style="width: 3rem; height: 3rem;" role="status">
                    <span class="visually-hidden">Loading...</span>
                </div>
                <h5 class="fw-bold mb-1">Loading Content Details...</h5>
                <p class="text-muted small mb-0">Please wait while we retrieve the content information.</p>
            </div>

            <!-- Error State -->
            <div class="modal-body py-4" id="contentDetailError" style="display: none;">
                <div class="alert alert-danger d-flex align-items-center gap-2 mb-0" id="detailError">
                    <i class="ti ti-alert-circle fs-2 flex-shrink-0"></i>
                    <div class="flex-grow-1">
                        <strong class="d-block" id="contentDetailErrorTitle">Failed to load content details</strong>
                        <span class="small" id="contentDetailErrorMessage">Unable to load content details. Please try again.</span>
                    </div>
                    <button type="button" class="btn btn-sm btn-danger px-3 ms-auto d-inline-flex align-items-center gap-1" id="btnRetryContentDetail">
                        <i class="ti ti-refresh me-1" id="btnRetrySpinner"></i>
                        <span id="btnRetryText">Retry</span>
                    </button>
                </div>
            </div>

            <!-- Content Data Body -->
            <div class="modal-body py-3" id="contentDetailContent" data-detail-content style="display: none;">
                <!-- Title & Badge Header Section -->
                <div class="mb-3">
                    <h4 class="fw-bold text-dark mb-2" id="detailTitle">-</h4>
                    <div class="d-flex flex-wrap align-items-center gap-2">
                        <span class="badge bg-blue-lt" id="detailTypeBadge">-</span>
                        <span class="badge bg-success-lt" id="detailStatusBadge">-</span>
                        <span class="badge bg-secondary-lt" id="detailClassificationBadge">-</span>
                        <span class="badge bg-azure-lt" id="detailViewsBadge"><i class="ti ti-eye me-1"></i><span id="detailViews">0</span> Views</span>
                    </div>
                </div>

                <!-- Learning Information Featured Card -->
                <div class="card border-0 bg-light rounded-3 p-3 mb-3">
                    <div class="fw-semibold text-dark small mb-2 d-flex align-items-center gap-1">
                        <i class="ti ti-clock-check text-primary me-1"></i> Learning Information
                    </div>
                    <div class="row g-3">
                        <div class="col-sm-6">
                            <div class="p-2 border rounded-3 bg-white text-center h-100">
                                <div class="text-muted small mb-1">Duration</div>
                                <div class="h3 fw-bold text-dark mb-0 font-monospace" id="detailDuration">00:00:00</div>
                            </div>
                        </div>
                        <div class="col-sm-6">
                            <div class="p-2 border rounded-3 bg-white text-center h-100">
                                <div class="text-muted small mb-1">Learning Hour</div>
                                <div class="h3 fw-bold text-primary mb-0 font-monospace" id="detailLearningHour">0.0000 h</div>
                            </div>
                        </div>
                    </div>
                    <div class="small text-muted text-center mt-2">
                        <i class="ti ti-info-circle me-1"></i>Actual learning hours based on content duration.
                    </div>
                </div>

                <!-- Document Information Section (Conditional) -->
                <div class="card border-0 bg-light rounded-3 p-3 mb-3" id="detailDocumentSection" style="display: none;">
                    <div class="fw-semibold text-dark small mb-2 d-flex align-items-center gap-1">
                        <i class="ti ti-files text-primary me-1"></i> Document Information
                    </div>
                    <div class="row g-2">
                        <div class="col-6">
                            <div class="small text-muted">Total Pages:</div>
                            <div class="fw-bold text-dark" id="detailTotalPage">0</div>
                        </div>
                        <div class="col-6">
                            <div class="small text-muted">Page Duration:</div>
                            <div class="fw-bold text-dark" id="detailPageDuration">0 seconds</div>
                        </div>
                    </div>
                </div>

                <!-- Content Details & Metadata -->
                <div class="row g-3 mb-3">
                    <!-- Description -->
                    <div class="col-12" id="detailDescriptionContainer">
                        <label class="form-label small fw-semibold text-muted mb-1">Description</label>
                        <div class="p-3 border rounded-3 bg-light text-dark small" id="detailDescription" style="max-height: 120px; overflow-y: auto; white-space: pre-wrap;">-</div>
                    </div>

                    <!-- Author & Date -->
                    <div class="col-sm-6">
                        <div class="border rounded-3 p-2 bg-light">
                            <span class="small text-muted d-block">Author</span>
                            <strong class="text-dark small" id="detailAuthor">-</strong>
                        </div>
                    </div>
                    <div class="col-sm-6">
                        <div class="border rounded-3 p-2 bg-light">
                            <span class="small text-muted d-block">Content Date</span>
                            <strong class="text-dark small" id="detailDate">-</strong>
                        </div>
                    </div>

                    <!-- Exam & Playlist Status -->
                    <div class="col-sm-6">
                        <div class="border rounded-3 p-2 bg-light">
                            <span class="small text-muted d-block">Assessment / Exam</span>
                            <strong class="text-dark small" id="detailExam">-</strong>
                        </div>
                    </div>
                    <div class="col-sm-6">
                        <div class="border rounded-3 p-2 bg-light">
                            <span class="small text-muted d-block">Playlist Status</span>
                            <strong class="text-dark small" id="detailPlaylist">-</strong>
                        </div>
                    </div>

                    <!-- Deadline Information -->
                    <div class="col-12" id="detailDeadlineContainer">
                        <div class="border rounded-3 p-2 bg-light">
                            <span class="small text-muted d-block">Deadline</span>
                            <strong class="text-dark small" id="detailDeadline">No Deadline</strong>
                        </div>
                    </div>

                    <!-- Associated Playlists -->
                    <div class="col-12" id="detailAssociatedPlaylistsContainer" style="display: none;">
                        <label class="form-label small fw-semibold text-muted mb-1">Included in Playlists</label>
                        <div class="d-flex flex-wrap gap-1" id="detailPlaylistsList"></div>
                    </div>
                </div>
            </div>

            <div class="modal-footer bg-light d-flex justify-content-between align-items-center border-0">
                <a href="#" class="btn btn-primary d-inline-flex align-items-center gap-1" id="btnEditContentShortcut">
                    <i class="ti ti-edit me-1"></i> Edit Content
                </a>
                <button type="button" class="btn btn-outline-secondary px-4" data-bs-dismiss="modal">
                    Close
                </button>
            </div>
        </div>
    </div>
</div>
