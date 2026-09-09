<!-- Synchronize Playlist Learning Hours Modal -->
<div class="modal modal-blur fade playlist-lh-sync-modal" id="modalSyncPlaylistLearningHours" tabindex="-1" aria-labelledby="modalSyncPlaylistLearningHoursLabel" aria-hidden="true" data-bs-backdrop="static">
    <div class="modal-dialog modal-dialog-centered modal-md">
        <div class="modal-content rounded-4 border-0 shadow">
            <div class="modal-header border-0 pb-0">
                <h5 class="modal-title fw-bold text-dark d-flex align-items-center gap-2" id="modalSyncPlaylistLearningHoursLabel">
                    <i class="ti ti-refresh text-primary fs-2"></i>
                    <span id="syncPlaylistLhModalTitle">Synchronize Learning Hours</span>
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close" id="syncPlaylistLhCloseHeaderBtn"></button>
            </div>

            <!-- Initial Confirmation / Prompt State -->
            <div class="modal-body py-3" id="syncPlaylistLhPromptBody">
                <p class="text-muted small mb-3">
                    This action will recalculate <strong>Learning Hours</strong> for all <strong>Playlist</strong> master records using the actual duration of their assigned content.
                </p>

                <!-- Calculation Formula Card -->
                <div class="p-3 rounded-3 bg-light border mb-3 playlist-lh-formula-box">
                    <div class="fw-semibold text-dark small mb-1 d-flex align-items-center gap-1">
                        <i class="ti ti-calculator text-primary me-1"></i> Actual Calculation Formula:
                    </div>
                    <div class="text-muted font-monospace small mb-2">
                        Actual Hours = Total Duration in seconds / 3600
                    </div>
                    <div class="small text-secondary">
                        Precision: <strong>4 decimal places</strong> (e.g. <code>00:50:00</code> &rarr; <code>0.8333</code>, <code>01:06:26</code> &rarr; <code>1.1072</code>).
                    </div>
                </div>

                <!-- Isolation & Safety Box -->
                <div class="alert alert-info py-2 px-3 small mb-0 d-flex align-items-center gap-2">
                    <i class="ti ti-shield-check fs-3 text-info flex-shrink-0"></i>
                    <span>Playlist Durations and User learning progress will remain intact and will not be altered.</span>
                </div>
            </div>

            <!-- Loading State -->
            <div class="modal-body py-4 text-center" id="syncPlaylistLhLoadingBody" style="display: none;">
                <div class="spinner-border text-primary mb-3" style="width: 3rem; height: 3rem;" role="status">
                    <span class="visually-hidden">Loading...</span>
                </div>
                <h5 class="fw-bold mb-1" id="syncPlaylistLhLoadingTitle">Synchronizing Learning Hours...</h5>
                <p class="text-muted small mb-0" id="syncPlaylistLhLoadingText">Recalculating Playlist actual hours from assigned content. Please do not refresh or close this window.</p>
            </div>

            <!-- Result Summary State -->
            <div class="modal-body py-3" id="syncPlaylistLhResultBody" style="display: none;">
                <div class="text-center mb-3">
                    <span class="avatar avatar-md bg-success-lt text-success rounded-circle mb-2 d-inline-flex align-items-center justify-content-center">
                        <i class="ti ti-check fs-2"></i>
                    </span>
                    <h5 class="fw-bold mb-0 text-success">Synchronization Complete</h5>
                    <p class="text-muted small mb-0" id="syncPlaylistLhResultMessage">Playlist Learning Hours synchronized successfully.</p>
                </div>

                <!-- 3 Summary Stat Cards -->
                <div class="row g-2 mb-3">
                    <div class="col-4">
                        <div class="p-2 border rounded-3 bg-light text-center h-100 playlist-lh-stat-card">
                            <div class="text-muted fw-semibold small mb-1" style="font-size: 0.75rem;">
                                <i class="ti ti-playlist me-1 text-primary"></i>Total
                            </div>
                            <div class="h3 fw-bold text-dark mb-0" id="resPlaylistLhTotal">0</div>
                        </div>
                    </div>
                    <div class="col-4">
                        <div class="p-2 border rounded-3 bg-light text-center h-100 playlist-lh-stat-card">
                            <div class="text-muted fw-semibold small mb-1" style="font-size: 0.75rem;">
                                <i class="ti ti-refresh me-1 text-success"></i>Updated
                            </div>
                            <div class="h3 fw-bold text-success mb-0" id="resPlaylistLhUpdated">0</div>
                        </div>
                    </div>
                    <div class="col-4">
                        <div class="p-2 border rounded-3 bg-light text-center h-100 playlist-lh-stat-card">
                            <div class="text-muted fw-semibold small mb-1" style="font-size: 0.75rem;">
                                <i class="ti ti-check me-1 text-secondary"></i>Skipped
                            </div>
                            <div class="h3 fw-bold text-secondary mb-0" id="resPlaylistLhSkipped">0</div>
                        </div>
                    </div>
                </div>

                <div class="small text-muted text-center" id="syncPlaylistLhResultNote">
                    All playlist master records have been updated to actual learning hours.
                </div>
            </div>

            <!-- Error State Alert Container -->
            <div class="px-3 pb-2" id="syncPlaylistLhErrorContainer" style="display: none;">
                <div class="alert alert-danger py-2 px-3 small mb-0 d-flex align-items-center gap-2">
                    <i class="ti ti-alert-circle fs-3 flex-shrink-0"></i>
                    <span id="syncPlaylistLhErrorText">Unable to synchronize playlist learning hours. Please try again.</span>
                </div>
            </div>

            <div class="modal-footer bg-light d-flex justify-content-end align-items-center gap-2 border-0">
                <button type="button" class="btn btn-outline-secondary px-3" data-bs-dismiss="modal" id="btnCancelSyncPlaylistLh">
                    Cancel
                </button>
                <button type="button" class="btn btn-primary px-3 d-inline-flex align-items-center gap-1" id="btnExecuteSyncPlaylistLh" data-url="{{ route('manage.playlist.synchronize-learning-hours') }}">
                    <i class="ti ti-refresh me-1" id="btnExecuteSyncPlaylistLhIcon"></i>
                    <span id="btnExecuteSyncPlaylistLhText">Synchronize Learning Hours</span>
                </button>
                <button type="button" class="btn btn-primary px-4" id="btnDoneSyncPlaylistLh" style="display: none;">
                    Done
                </button>
            </div>
        </div>
    </div>
</div>

<style>
/* Scoped Dark Mode & Responsive Styling for Playlist Learning Hours Sync Modal */
[data-bs-theme="dark"] #modalSyncPlaylistLearningHours .modal-content,
body.dark-mode #modalSyncPlaylistLearningHours .modal-content,
body.theme-dark #modalSyncPlaylistLearningHours .modal-content {
    background-color: var(--tblr-bg-surface, #1f2937) !important;
    color: #f8fafc !important;
}
[data-bs-theme="dark"] #modalSyncPlaylistLearningHours .modal-footer,
body.dark-mode #modalSyncPlaylistLearningHours .modal-footer,
body.theme-dark #modalSyncPlaylistLearningHours .modal-footer {
    background-color: var(--tblr-bg-surface-secondary, #111827) !important;
}
[data-bs-theme="dark"] #modalSyncPlaylistLearningHours .bg-light,
[data-bs-theme="dark"] #modalSyncPlaylistLearningHours .playlist-lh-stat-card,
[data-bs-theme="dark"] #modalSyncPlaylistLearningHours .playlist-lh-formula-box,
body.dark-mode #modalSyncPlaylistLearningHours .bg-light,
body.dark-mode #modalSyncPlaylistLearningHours .playlist-lh-stat-card,
body.dark-mode #modalSyncPlaylistLearningHours .playlist-lh-formula-box,
body.theme-dark #modalSyncPlaylistLearningHours .bg-light,
body.theme-dark #modalSyncPlaylistLearningHours .playlist-lh-stat-card,
body.theme-dark #modalSyncPlaylistLearningHours .playlist-lh-formula-box {
    background-color: var(--tblr-bg-surface-secondary, #111827) !important;
    border-color: rgba(255, 255, 255, 0.08) !important;
}
[data-bs-theme="dark"] #modalSyncPlaylistLearningHours .text-dark,
body.dark-mode #modalSyncPlaylistLearningHours .text-dark,
body.theme-dark #modalSyncPlaylistLearningHours .text-dark {
    color: #f8fafc !important;
}
</style>

<script>
document.addEventListener('DOMContentLoaded', function () {
    const modalEl = document.getElementById('modalSyncPlaylistLearningHours');
    if (!modalEl) return;

    const btnExecuteSyncPlaylistLh = document.getElementById('btnExecuteSyncPlaylistLh');
    const btnCancelSyncPlaylistLh = document.getElementById('btnCancelSyncPlaylistLh');
    const btnDoneSyncPlaylistLh = document.getElementById('btnDoneSyncPlaylistLh');
    const syncPlaylistLhCloseHeaderBtn = document.getElementById('syncPlaylistLhCloseHeaderBtn');

    const syncPlaylistLhPromptBody = document.getElementById('syncPlaylistLhPromptBody');
    const syncPlaylistLhLoadingBody = document.getElementById('syncPlaylistLhLoadingBody');
    const syncPlaylistLhResultBody = document.getElementById('syncPlaylistLhResultBody');
    const syncPlaylistLhErrorContainer = document.getElementById('syncPlaylistLhErrorContainer');
    const syncPlaylistLhErrorText = document.getElementById('syncPlaylistLhErrorText');

    const resPlaylistLhTotal = document.getElementById('resPlaylistLhTotal');
    const resPlaylistLhUpdated = document.getElementById('resPlaylistLhUpdated');
    const resPlaylistLhSkipped = document.getElementById('resPlaylistLhSkipped');

    let syncCompleted = false;

    function resetModalState() {
        if (syncCompleted) return;
        if (syncPlaylistLhPromptBody) syncPlaylistLhPromptBody.style.display = 'block';
        if (syncPlaylistLhLoadingBody) syncPlaylistLhLoadingBody.style.display = 'none';
        if (syncPlaylistLhResultBody) syncPlaylistLhResultBody.style.display = 'none';
        if (syncPlaylistLhErrorContainer) syncPlaylistLhErrorContainer.style.display = 'none';

        if (btnExecuteSyncPlaylistLh) {
            btnExecuteSyncPlaylistLh.disabled = false;
            btnExecuteSyncPlaylistLh.style.display = 'inline-flex';
            btnExecuteSyncPlaylistLh.innerHTML = '<i class="ti ti-refresh me-1" id="btnExecuteSyncPlaylistLhIcon"></i><span id="btnExecuteSyncPlaylistLhText">Synchronize Learning Hours</span>';
        }
        if (btnCancelSyncPlaylistLh) btnCancelSyncPlaylistLh.style.display = 'inline-block';
        if (btnDoneSyncPlaylistLh) btnDoneSyncPlaylistLh.style.display = 'none';
        if (syncPlaylistLhCloseHeaderBtn) syncPlaylistLhCloseHeaderBtn.style.display = 'block';
    }

    modalEl.addEventListener('hidden.bs.modal', function () {
        if (syncCompleted) {
            window.location.reload();
        } else {
            resetModalState();
        }
    });

    if (btnDoneSyncPlaylistLh) {
        btnDoneSyncPlaylistLh.addEventListener('click', function () {
            window.location.reload();
        });
    }

    function handleSyncError(message) {
        if (syncPlaylistLhLoadingBody) syncPlaylistLhLoadingBody.style.display = 'none';
        if (syncPlaylistLhPromptBody) syncPlaylistLhPromptBody.style.display = 'block';
        if (syncPlaylistLhErrorContainer) {
            syncPlaylistLhErrorContainer.style.display = 'block';
            if (syncPlaylistLhErrorText) syncPlaylistLhErrorText.textContent = message;
        }
        if (btnExecuteSyncPlaylistLh) {
            btnExecuteSyncPlaylistLh.disabled = false;
            btnExecuteSyncPlaylistLh.style.display = 'inline-flex';
            btnExecuteSyncPlaylistLh.innerHTML = '<i class="ti ti-refresh me-1"></i> Synchronize Learning Hours';
        }
        if (btnCancelSyncPlaylistLh) btnCancelSyncPlaylistLh.style.display = 'inline-block';
        if (syncPlaylistLhCloseHeaderBtn) syncPlaylistLhCloseHeaderBtn.style.display = 'block';
    }

    if (btnExecuteSyncPlaylistLh) {
        btnExecuteSyncPlaylistLh.addEventListener('click', function () {
            const syncUrl = btnExecuteSyncPlaylistLh.getAttribute('data-url');
            if (!syncUrl) return;

            // Loading state
            btnExecuteSyncPlaylistLh.disabled = true;
            btnExecuteSyncPlaylistLh.innerHTML = '<span class="spinner-border spinner-border-sm me-1" role="status"></span> Syncing Learning Hours...';
            if (btnCancelSyncPlaylistLh) btnCancelSyncPlaylistLh.style.display = 'none';
            if (syncPlaylistLhCloseHeaderBtn) syncPlaylistLhCloseHeaderBtn.style.display = 'none';
            if (syncPlaylistLhPromptBody) syncPlaylistLhPromptBody.style.display = 'none';
            if (syncPlaylistLhErrorContainer) syncPlaylistLhErrorContainer.style.display = 'none';
            if (syncPlaylistLhLoadingBody) syncPlaylistLhLoadingBody.style.display = 'block';

            $.ajax({
                url: syncUrl,
                type: 'POST',
                dataType: 'json',
                headers: {
                    'X-CSRF-TOKEN': '{{ csrf_token() }}',
                    'Accept': 'application/json'
                }
            }).done(function (res) {
                if (res && res.success && res.data) {
                    syncCompleted = true;
                    if (syncPlaylistLhLoadingBody) syncPlaylistLhLoadingBody.style.display = 'none';
                    if (syncPlaylistLhResultBody) syncPlaylistLhResultBody.style.display = 'block';

                    const data = res.data;
                    if (resPlaylistLhTotal) resPlaylistLhTotal.textContent = Number(data.total ?? data.playlist_total ?? 0).toLocaleString();
                    if (resPlaylistLhUpdated) resPlaylistLhUpdated.textContent = Number(data.updated ?? data.playlist_updated ?? 0).toLocaleString();
                    if (resPlaylistLhSkipped) resPlaylistLhSkipped.textContent = Number(data.skipped ?? data.playlist_skipped ?? 0).toLocaleString();

                    if (btnExecuteSyncPlaylistLh) btnExecuteSyncPlaylistLh.style.display = 'none';
                    if (btnDoneSyncPlaylistLh) btnDoneSyncPlaylistLh.style.display = 'inline-block';
                    if (syncPlaylistLhCloseHeaderBtn) syncPlaylistLhCloseHeaderBtn.style.display = 'block';
                } else {
                    handleSyncError(res ? res.message : 'Unknown synchronization error occurred.');
                }
            }).fail(function (xhr) {
                let errMsg = 'Failed to execute playlist learning hours synchronization.';
                if (xhr.responseJSON && xhr.responseJSON.message) {
                    errMsg = xhr.responseJSON.message;
                }
                handleSyncError(errMsg);
            });
        });
    }
});
</script>
