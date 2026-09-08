<!-- Synchronize Learning Hours Modal -->
<div class="modal modal-blur fade" id="modalSyncLearningHours" tabindex="-1" aria-labelledby="modalSyncLearningHoursLabel" aria-hidden="true" data-bs-backdrop="static">
    <div class="modal-dialog modal-dialog-centered modal-md">
        <div class="modal-content rounded-4 border-0 shadow">
            <div class="modal-header border-0 pb-0">
                <h5 class="modal-title fw-bold text-dark d-flex align-items-center gap-2" id="modalSyncLearningHoursLabel">
                    <i class="ti ti-refresh text-primary fs-2"></i> Synchronize Learning Hours
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close" id="syncLhCloseHeaderBtn"></button>
            </div>

            <!-- Initial Prompt State -->
            <div class="modal-body py-3" id="syncLhPromptBody">
                <p class="text-muted small mb-3">
                    This action will recalculate <strong>Learning Hours</strong> for all <strong>Content</strong> and <strong>Playlist</strong> master records using their actual duration.
                </p>

                <div class="p-3 rounded-3 bg-light border mb-3">
                    <div class="fw-semibold text-dark small mb-1">
                        <i class="ti ti-calculator text-primary me-1"></i> Actual Calculation Formula:
                    </div>
                    <div class="text-muted font-monospace small mb-2">
                        Actual Hours = Duration in seconds / 3600
                    </div>
                    <div class="small text-secondary">
                        Precision: <strong>4 decimal places</strong> (e.g. <code>00:50:00</code> &rarr; <code>0.8333</code>, <code>01:06:26</code> &rarr; <code>1.1072</code>).
                    </div>
                </div>

                <div class="alert alert-info py-2 px-3 small mb-0 d-flex align-items-center gap-2">
                    <i class="ti ti-info-circle fs-3 text-info flex-shrink-0"></i>
                    <span>User tracking and completed actual learning hours will remain intact and safe.</span>
                </div>
            </div>

            <!-- Loading State -->
            <div class="modal-body py-4 text-center" id="syncLhLoadingBody" style="display: none;">
                <div class="spinner-border text-primary mb-3" style="width: 3rem; height: 3rem;" role="status">
                    <span class="visually-hidden">Loading...</span>
                </div>
                <h5 class="fw-bold mb-1">Synchronizing Learning Hours...</h5>
                <p class="text-muted small mb-0">Recalculating Content and Playlist actual hours. Please do not refresh or close this window.</p>
            </div>

            <!-- Result Summary State -->
            <div class="modal-body py-3" id="syncLhResultBody" style="display: none;">
                <div class="text-center mb-3">
                    <span class="avatar avatar-md bg-success-lt text-success rounded-circle mb-2 d-inline-flex align-items-center justify-content-center">
                        <i class="ti ti-check fs-2"></i>
                    </span>
                    <h5 class="fw-bold mb-0 text-success">Synchronization Complete</h5>
                    <p class="text-muted small mb-0" id="syncLhResultMessage">Learning Hours synchronized successfully.</p>
                </div>

                <div class="row g-2 mb-3">
                    <div class="col-6">
                        <div class="p-2 border rounded-3 bg-light text-center">
                            <div class="fw-bold text-dark mb-1 small"><i class="ti ti-books me-1"></i>Content</div>
                            <div class="small text-muted">Total: <strong id="resContentTotal" class="text-dark">0</strong></div>
                            <div class="small text-muted">Updated: <strong id="resContentUpdated" class="text-success">0</strong></div>
                            <div class="small text-muted">Skipped: <strong id="resContentSkipped" class="text-secondary">0</strong></div>
                        </div>
                    </div>
                    <div class="col-6">
                        <div class="p-2 border rounded-3 bg-light text-center">
                            <div class="fw-bold text-dark mb-1 small"><i class="ti ti-playlist me-1"></i>Playlist</div>
                            <div class="small text-muted">Total: <strong id="resPlaylistTotal" class="text-dark">0</strong></div>
                            <div class="small text-muted">Updated: <strong id="resPlaylistUpdated" class="text-success">0</strong></div>
                            <div class="small text-muted">Skipped: <strong id="resPlaylistSkipped" class="text-secondary">0</strong></div>
                        </div>
                    </div>
                </div>

                <div class="small text-muted text-center" id="syncLhResultNote">
                    All master records have been updated to actual learning hours.
                </div>
            </div>

            <!-- Error State Alert Container -->
            <div class="px-3 pb-2" id="syncLhErrorContainer" style="display: none;">
                <div class="alert alert-danger py-2 px-3 small mb-0" id="syncLhErrorText"></div>
            </div>

            <div class="modal-footer bg-light d-flex justify-content-end align-items-center gap-2 border-0">
                <button type="button" class="btn btn-outline-secondary px-3" data-bs-dismiss="modal" id="btnCancelSyncLh">
                    Cancel
                </button>
                <button type="button" class="btn btn-primary px-3 d-inline-flex align-items-center gap-1" id="btnExecuteSyncLh" data-url="{{ route('learning-hours.sync') }}">
                    <i class="ti ti-refresh me-1" id="btnExecuteSyncLhIcon"></i>
                    <span id="btnExecuteSyncLhText">Synchronize Learning Hours</span>
                </button>
                <button type="button" class="btn btn-primary px-4" id="btnDoneSyncLh" style="display: none;" onclick="window.location.reload();">
                    Done
                </button>
            </div>
        </div>
    </div>
</div>
