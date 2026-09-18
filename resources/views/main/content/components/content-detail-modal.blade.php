<!-- Content Detail Modal -->
<div class="modal modal-blur fade" id="contentDetailModal" tabindex="-1" aria-labelledby="contentDetailModalTitle" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg" style="max-width: 780px;">
        <div class="modal-content rounded-3 border-0 shadow">
            <!-- Modal Header: Compact & Clean -->
            <div class="modal-header px-3 py-2 border-bottom">
                <div class="d-flex align-items-center gap-2 flex-grow-1 min-w-0">
                    <div class="avatar avatar-sm bg-primary-lt text-primary rounded-2 flex-shrink-0">
                        <i class="ti ti-file-description fs-3"></i>
                    </div>
                    <div class="min-w-0 flex-grow-1">
                        <h5 class="modal-title fw-bold text-dark mb-0 text-truncate" id="contentDetailModalTitle">Content Details</h5>
                        <small class="text-muted d-block lh-1" id="contentDetailModalSubtitle">View comprehensive information for this content</small>
                    </div>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>

            <!-- Loading State -->
            <div class="modal-body py-4 text-center" id="contentDetailLoading">
                <div class="spinner-border text-primary mb-2" style="width: 2.5rem; height: 2.5rem;" role="status">
                    <span class="visually-hidden">Loading...</span>
                </div>
                <h5 class="fw-bold mb-1">Loading Content Details...</h5>
                <p class="text-muted small mb-0">Please wait while we retrieve the content information.</p>
            </div>

            <!-- Error State -->
            <div class="modal-body py-3" id="contentDetailError" style="display: none;">
                <div class="alert alert-danger d-flex align-items-center gap-2 mb-0 py-2 px-3" id="detailError">
                    <i class="ti ti-alert-circle fs-2 flex-shrink-0"></i>
                    <div class="flex-grow-1 min-w-0">
                        <strong class="d-block small" id="contentDetailErrorTitle">Failed to load content details</strong>
                        <span class="small text-truncate" id="contentDetailErrorMessage">Unable to load content details. Please try again.</span>
                    </div>
                    <button type="button" class="btn btn-sm btn-danger px-3 ms-auto d-inline-flex align-items-center gap-1 flex-shrink-0" id="btnRetryContentDetail">
                        <i class="ti ti-refresh me-1" id="btnRetrySpinner"></i>
                        <span id="btnRetryText">Retry</span>
                    </button>
                </div>
            </div>

            <!-- Content Data Body: Compact Information Density -->
            <div class="modal-body px-3 py-2" id="contentDetailContent" data-detail-content style="display: none;">
                <!-- Title & Status Badges -->
                <div class="mb-2">
                    <h4 class="fw-bold text-dark mb-1 lh-sm" id="detailTitle">-</h4>
                    <div class="d-flex flex-wrap align-items-center gap-1">
                        <span class="badge bg-blue-lt" id="detailTypeBadge">-</span>
                        <span class="badge bg-success-lt" id="detailStatusBadge">-</span>
                        <span class="badge bg-secondary-lt" id="detailClassificationBadge">-</span>
                        <span class="badge bg-azure-lt" id="detailViewsBadge"><i class="ti ti-eye me-1"></i><span id="detailViews">0</span> Views</span>
                    </div>
                </div>

                <!-- Learning Information: Compact 2-Column Row -->
                <div class="rounded-3 border bg-light p-2 mb-2">
                    <div class="row g-2 align-items-center">
                        <div class="col-6">
                            <div class="p-2 rounded-2 bg-white border text-center">
                                <div class="text-muted small lh-1 mb-1">Duration</div>
                                <div class="h4 fw-bold text-dark mb-0 font-monospace" id="detailDuration">00:00:00</div>
                            </div>
                        </div>
                        <div class="col-6">
                            <div class="p-2 rounded-2 bg-white border text-center">
                                <div class="text-muted small lh-1 mb-1">Learning Hour</div>
                                <div class="h4 fw-bold text-primary mb-0 font-monospace" id="detailLearningHour">0.0000 h</div>
                            </div>
                        </div>
                    </div>
                    <div class="text-muted text-center mt-1" style="font-size: 11px;">
                        <i class="ti ti-info-circle me-1"></i>Actual learning hours based on content duration.
                    </div>
                </div>

                <!-- Document Information Section (Conditional) -->
                <div class="rounded-3 border bg-light px-3 py-2 mb-2" id="detailDocumentSection" style="display: none;">
                    <div class="row g-2 align-items-center">
                        <div class="col-6 d-flex align-items-center justify-content-between gap-2">
                            <span class="text-muted small"><i class="ti ti-files text-primary me-1"></i>Total Pages:</span>
                            <strong class="text-dark small" id="detailTotalPage">0</strong>
                        </div>
                        <div class="col-6 d-flex align-items-center justify-content-between gap-2">
                            <span class="text-muted small"><i class="ti ti-clock text-primary me-1"></i>Page Duration:</span>
                            <strong class="text-dark small" id="detailPageDuration">0 seconds</strong>
                        </div>
                    </div>
                </div>

                <!-- Description: Compact Scrollable Panel -->
                <div class="mb-2" id="detailDescriptionContainer">
                    <div class="border rounded-2 p-2 bg-light">
                        <span class="small fw-semibold text-muted d-block mb-1">Description</span>
                        <div class="text-dark small lh-base" id="detailDescription" style="max-height: 85px; overflow-y: auto; white-space: pre-wrap;">-</div>
                    </div>
                </div>

                <!-- Metadata Grid: Compact 2-Column Grid -->
                <div class="row g-2 mb-2">
                    <div class="col-sm-6">
                        <div class="border rounded-2 p-2 bg-light d-flex align-items-center justify-content-between">
                            <span class="small text-muted">Author</span>
                            <strong class="text-dark small text-truncate ms-2" id="detailAuthor">-</strong>
                        </div>
                    </div>
                    <div class="col-sm-6">
                        <div class="border rounded-2 p-2 bg-light d-flex align-items-center justify-content-between">
                            <span class="small text-muted">Content Date</span>
                            <strong class="text-dark small text-truncate ms-2" id="detailDate">-</strong>
                        </div>
                    </div>
                    <div class="col-sm-6">
                        <div class="border rounded-2 p-2 bg-light d-flex align-items-center justify-content-between">
                            <span class="small text-muted">Assessment / Exam</span>
                            <strong class="text-dark small text-truncate ms-2" id="detailExam">-</strong>
                        </div>
                    </div>
                    <div class="col-sm-6">
                        <div class="border rounded-2 p-2 bg-light d-flex align-items-center justify-content-between">
                            <span class="small text-muted">Playlist Status</span>
                            <strong class="text-dark small text-truncate ms-2" id="detailPlaylist">-</strong>
                        </div>
                    </div>
                    <div class="col-12" id="detailDeadlineContainer">
                        <div class="border rounded-2 p-2 bg-light d-flex align-items-center justify-content-between">
                            <span class="small text-muted">Deadline</span>
                            <strong class="text-dark small text-truncate ms-2" id="detailDeadline">No Deadline</strong>
                        </div>
                    </div>
                </div>

                <!-- Associated Playlists -->
                <div class="border rounded-2 p-2 bg-light mb-1" id="detailAssociatedPlaylistsContainer" style="display: none;">
                    <span class="small fw-semibold text-muted d-block mb-1">Included in Playlists</span>
                    <div class="d-flex flex-wrap gap-1" id="detailPlaylistsList"></div>
                </div>
            </div>

            <!-- Modal Footer: Compact Action Bar -->
            <div class="modal-footer px-3 py-2 bg-light border-top d-flex justify-content-between align-items-center">
                <a href="#" class="btn btn-primary btn-sm d-inline-flex align-items-center gap-1 px-3" id="btnEditContentShortcut">
                    <i class="ti ti-edit me-1"></i> Edit Content
                </a>
                <button type="button" class="btn btn-outline-secondary btn-sm px-4" data-bs-dismiss="modal">
                    Close
                </button>
            </div>
        </div>
    </div>
</div>
