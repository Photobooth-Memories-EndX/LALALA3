<!-- Synchronize Playlist Durations Modal -->
<div class="modal modal-blur fade playlist-sync-modal" id="modalSyncPlaylistDuration" tabindex="-1" aria-labelledby="modalSyncPlaylistDurationLabel" aria-hidden="true" data-bs-backdrop="static">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content rounded-4 border-0 shadow">
            <div class="modal-header border-0 pb-0">
                <h5 class="modal-title fw-bold text-dark d-flex align-items-center gap-2" id="modalSyncPlaylistDurationLabel">
                    <i class="ti ti-clock-check text-primary fs-2"></i>
                    <span id="syncDurationModalTitle">Synchronize Playlist Durations</span>
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close" id="syncDurationCloseHeaderBtn"></button>
            </div>

            <!-- Initial Prompt / Confirmation State -->
            <div class="modal-body py-3" id="syncDurationPromptBody">
                <p class="text-muted small mb-3">
                    Recalculates the total duration for all playlists by summing the durations of all assigned materials.
                </p>

                <!-- Formula Card -->
                <div class="p-3 rounded-3 bg-light border mb-3 sync-formula-box">
                    <div class="fw-semibold text-dark small mb-1 d-flex align-items-center gap-1">
                        <i class="ti ti-calculator text-primary me-1"></i> Calculation Formula:
                    </div>
                    <div class="text-muted font-monospace small mb-2">
                        Playlist Duration = SUM(Content Duration)
                    </div>
                    <div class="small text-secondary">
                        Stored as seconds in <code>total_duration_seconds</code> &bull; Display format: <code>HH:MM:SS</code>.
                    </div>
                </div>

                <!-- Guidelines Box -->
                <div class="p-3 rounded-3 bg-light border mb-3 sync-info-box">
                    <div class="fw-semibold text-dark small mb-2 d-flex align-items-center gap-1">
                        <i class="ti ti-info-circle text-primary me-1"></i> Synchronization Guidelines:
                    </div>
                    <ul class="text-muted small mb-0 ps-3">
                        <li class="mb-1">Target scope: <strong>{{ number_format($totalTrainingPlan ?? 0) }} Playlists</strong> ({{ number_format($totalMaterials ?? 0) }} total materials).</li>
                        <li class="mb-1">All assigned materials in the syllabus are calculated (including inactive content).</li>
                        <li class="mb-1">Soft-deleted content is safely excluded from calculation.</li>
                        <li>Existing accurate durations will be preserved without redundant database updates.</li>
                    </ul>
                </div>

                <div class="alert alert-info py-2 px-3 small mb-0 d-flex align-items-center gap-2">
                    <i class="ti ti-shield-check fs-3 text-info flex-shrink-0"></i>
                    <span>This operation only updates <strong>Duration</strong>. <strong>Learning Hours</strong> are strictly isolated and will not be altered.</span>
                </div>
            </div>

            <!-- Loading State -->
            <div class="modal-body py-4 text-center" id="syncDurationLoadingBody" style="display: none;">
                <div class="spinner-border text-primary mb-3" style="width: 3rem; height: 3rem;" role="status">
                    <span class="visually-hidden">Loading...</span>
                </div>
                <h5 class="fw-bold mb-1" id="syncDurationLoadingTitle">Calculating Playlist Durations...</h5>
                <p class="text-muted small mb-3" id="syncDurationLoadingText">Processing materials and summing durations for all playlists. Please do not refresh or close this window.</p>

                <!-- Indeterminate progress bar -->
                <div class="progress progress-sm rounded-pill mb-2" style="height: 6px;">
                    <div class="progress-bar progress-bar-indeterminate bg-primary" role="progressbar"></div>
                </div>
                <div class="small text-muted" id="syncDurationProgressCounter">Recalculating playlist totals...</div>
            </div>

            <!-- Result Summary State -->
            <div class="modal-body py-3" id="syncDurationResultBody" style="display: none;">
                <div class="text-center mb-3">
                    <span class="avatar avatar-md bg-success-lt text-success rounded-circle mb-2 d-inline-flex align-items-center justify-content-center">
                        <i class="ti ti-check fs-2"></i>
                    </span>
                    <h5 class="fw-bold mb-0 text-success">Synchronization Complete</h5>
                    <p class="text-muted small mb-0" id="syncDurationResultMessage">Playlist durations have been synchronized successfully.</p>
                </div>

                <!-- 4 Summary Cards -->
                <div class="row g-2 mb-3">
                    <!-- Card 1: Total Playlists Checked -->
                    <div class="col-6 col-sm-3">
                        <div class="p-2 border rounded-3 bg-light text-center h-100 sync-stat-card">
                            <div class="text-muted fw-semibold small mb-1" style="font-size: 0.75rem;">
                                <i class="ti ti-playlist me-1 text-primary"></i>Total Playlists
                            </div>
                            <div class="h3 fw-bold text-dark mb-0" id="resTotalPlaylists">0</div>
                        </div>
                    </div>

                    <!-- Card 2: Durations Updated -->
                    <div class="col-6 col-sm-3">
                        <div class="p-2 border rounded-3 bg-light text-center h-100 sync-stat-card">
                            <div class="text-muted fw-semibold small mb-1" style="font-size: 0.75rem;">
                                <i class="ti ti-refresh me-1 text-success"></i>Updated
                            </div>
                            <div class="h3 fw-bold text-success mb-0" id="resUpdatedPlaylists">0</div>
                        </div>
                    </div>

                    <!-- Card 3: Already Accurate / Unchanged -->
                    <div class="col-6 col-sm-3">
                        <div class="p-2 border rounded-3 bg-light text-center h-100 sync-stat-card">
                            <div class="text-muted fw-semibold small mb-1" style="font-size: 0.75rem;">
                                <i class="ti ti-check me-1 text-info"></i>Already Accurate
                            </div>
                            <div class="h3 fw-bold text-info mb-0" id="resUnchangedPlaylists">0</div>
                        </div>
                    </div>

                    <!-- Card 4: Total Materials Processed -->
                    <div class="col-6 col-sm-3">
                        <div class="p-2 border rounded-3 bg-light text-center h-100 sync-stat-card">
                            <div class="text-muted fw-semibold small mb-1" style="font-size: 0.75rem;">
                                <i class="ti ti-books me-1 text-primary"></i>Materials
                            </div>
                            <div class="h3 fw-bold text-dark mb-0" id="resTotalMaterials">0</div>
                        </div>
                    </div>
                </div>

                <!-- Collapsible Detailed List -->
                <div id="syncDurationDetailsContainer" class="mt-3 pt-2 border-top" style="display: none;">
                    <div class="d-flex justify-content-between align-items-center mb-2">
                        <span class="small fw-semibold text-secondary" id="syncDurationDetailsHeader">Playlist Duration Details</span>
                        <button type="button" class="btn btn-sm btn-outline-secondary px-2 py-1" id="btnToggleDurationDetails" data-bs-toggle="collapse" data-bs-target="#collapseSyncDurationDetails" aria-expanded="false" aria-controls="collapseSyncDurationDetails">
                            <span id="toggleDurationDetailsText">View Details</span>
                            <i class="ti ti-chevron-down ms-1" id="toggleDurationDetailsIcon"></i>
                        </button>
                    </div>
                    <div class="collapse" id="collapseSyncDurationDetails">
                        <div class="border rounded-3 p-2 bg-light-subtle sync-details-scroll-box" style="max-height: 220px; overflow-y: auto;">
                            <div class="list-group list-group-flush list-group-hoverable" id="syncDurationDetailsList"></div>
                        </div>
                    </div>
                </div>

                <div class="small text-muted text-center mt-2" id="syncDurationResultNote">
                    All playlist duration totals match the sum of active materials in their syllabus.
                </div>
            </div>

            <!-- Error State Alert Container -->
            <div class="px-3 pb-2" id="syncDurationErrorContainer" style="display: none;">
                <div class="alert alert-danger py-2 px-3 small mb-0 d-flex align-items-center gap-2">
                    <i class="ti ti-alert-circle fs-3 flex-shrink-0"></i>
                    <span id="syncDurationErrorText">Unable to synchronize playlist durations. Please try again.</span>
                </div>
            </div>

            <div class="modal-footer bg-light d-flex justify-content-end align-items-center gap-2 border-0">
                <button type="button" class="btn btn-outline-secondary px-3" data-bs-dismiss="modal" id="btnCancelSyncDuration">
                    Cancel
                </button>
                <button type="button" class="btn btn-primary px-3 d-inline-flex align-items-center gap-1" id="btnExecuteSyncDuration" data-url="{{ route('manage.playlist.synchronize-durations') }}">
                    <i class="ti ti-clock-check me-1" id="btnExecuteSyncDurationIcon"></i>
                    <span id="btnExecuteSyncDurationText">Synchronize Durations</span>
                </button>
                <button type="button" class="btn btn-primary px-4" id="btnDoneSyncDuration" style="display: none;">
                    Done
                </button>
            </div>
        </div>
    </div>
</div>

<style>
/* Scoped Dark Mode & Styling for Playlist Duration Sync Modal */
[data-bs-theme="dark"] .playlist-sync-modal .modal-content,
body.dark-mode .playlist-sync-modal .modal-content,
body.theme-dark .playlist-sync-modal .modal-content {
    background-color: var(--tblr-bg-surface, #1f2937) !important;
    color: #f8fafc !important;
}
[data-bs-theme="dark"] .playlist-sync-modal .modal-footer,
body.dark-mode .playlist-sync-modal .modal-footer,
body.theme-dark .playlist-sync-modal .modal-footer {
    background-color: var(--tblr-bg-surface-secondary, #111827) !important;
}
[data-bs-theme="dark"] .playlist-sync-modal .sync-formula-box,
[data-bs-theme="dark"] .playlist-sync-modal .sync-info-box,
[data-bs-theme="dark"] .playlist-sync-modal .sync-stat-card,
body.dark-mode .playlist-sync-modal .sync-formula-box,
body.dark-mode .playlist-sync-modal .sync-info-box,
body.dark-mode .playlist-sync-modal .sync-stat-card,
body.theme-dark .playlist-sync-modal .sync-formula-box,
body.theme-dark .playlist-sync-modal .sync-info-box,
body.theme-dark .playlist-sync-modal .sync-stat-card {
    background-color: var(--tblr-bg-surface-secondary, #111827) !important;
    border-color: rgba(255, 255, 255, 0.08) !important;
}
[data-bs-theme="dark"] .playlist-sync-modal .sync-details-scroll-box,
body.dark-mode .playlist-sync-modal .sync-details-scroll-box,
body.theme-dark .playlist-sync-modal .sync-details-scroll-box {
    background-color: var(--tblr-bg-surface-secondary, #111827) !important;
    border-color: rgba(255, 255, 255, 0.08) !important;
}
[data-bs-theme="dark"] .playlist-sync-modal .text-dark,
body.dark-mode .playlist-sync-modal .text-dark,
body.theme-dark .playlist-sync-modal .text-dark {
    color: #f8fafc !important;
}
[data-bs-theme="dark"] .playlist-sync-modal .list-group-item,
body.dark-mode .playlist-sync-modal .list-group-item,
body.theme-dark .playlist-sync-modal .list-group-item {
    background-color: transparent !important;
    color: #f8fafc !important;
    border-color: rgba(255, 255, 255, 0.08) !important;
}
</style>

<script>
document.addEventListener('DOMContentLoaded', function () {
    const modalEl = document.getElementById('modalSyncPlaylistDuration');
    if (!modalEl) return;

    const btnExecuteSyncDuration = document.getElementById('btnExecuteSyncDuration');
    const btnCancelSyncDuration = document.getElementById('btnCancelSyncDuration');
    const btnDoneSyncDuration = document.getElementById('btnDoneSyncDuration');
    const syncDurationCloseHeaderBtn = document.getElementById('syncDurationCloseHeaderBtn');

    const syncDurationPromptBody = document.getElementById('syncDurationPromptBody');
    const syncDurationLoadingBody = document.getElementById('syncDurationLoadingBody');
    const syncDurationResultBody = document.getElementById('syncDurationResultBody');
    const syncDurationErrorContainer = document.getElementById('syncDurationErrorContainer');
    const syncDurationErrorText = document.getElementById('syncDurationErrorText');

    const resTotalPlaylists = document.getElementById('resTotalPlaylists');
    const resUpdatedPlaylists = document.getElementById('resUpdatedPlaylists');
    const resUnchangedPlaylists = document.getElementById('resUnchangedPlaylists');
    const resTotalMaterials = document.getElementById('resTotalMaterials');

    const syncDurationDetailsContainer = document.getElementById('syncDurationDetailsContainer');
    const syncDurationDetailsList = document.getElementById('syncDurationDetailsList');
    const btnToggleDurationDetails = document.getElementById('btnToggleDurationDetails');
    const toggleDurationDetailsText = document.getElementById('toggleDurationDetailsText');
    const toggleDurationDetailsIcon = document.getElementById('toggleDurationDetailsIcon');
    const collapseSyncDurationDetails = document.getElementById('collapseSyncDurationDetails');

    let syncCompleted = false;

    // Toggle Details Chevron
    if (collapseSyncDurationDetails && toggleDurationDetailsText && toggleDurationDetailsIcon) {
        collapseSyncDurationDetails.addEventListener('show.bs.collapse', function () {
            toggleDurationDetailsText.textContent = 'Hide Details';
            toggleDurationDetailsIcon.classList.remove('ti-chevron-down');
            toggleDurationDetailsIcon.classList.add('ti-chevron-up');
        });
        collapseSyncDurationDetails.addEventListener('hide.bs.collapse', function () {
            toggleDurationDetailsText.textContent = 'View Details';
            toggleDurationDetailsIcon.classList.remove('ti-chevron-up');
            toggleDurationDetailsIcon.classList.add('ti-chevron-down');
        });
    }

    function resetModalState() {
        if (syncCompleted) return; // do not reset if finished
        if (syncDurationPromptBody) syncDurationPromptBody.style.display = 'block';
        if (syncDurationLoadingBody) syncDurationLoadingBody.style.display = 'none';
        if (syncDurationResultBody) syncDurationResultBody.style.display = 'none';
        if (syncDurationErrorContainer) syncDurationErrorContainer.style.display = 'none';

        if (btnExecuteSyncDuration) {
            btnExecuteSyncDuration.disabled = false;
            btnExecuteSyncDuration.style.display = 'inline-flex';
            btnExecuteSyncDuration.innerHTML = '<i class="ti ti-clock-check me-1" id="btnExecuteSyncDurationIcon"></i><span id="btnExecuteSyncDurationText">Synchronize Durations</span>';
        }
        if (btnCancelSyncDuration) btnCancelSyncDuration.style.display = 'inline-block';
        if (btnDoneSyncDuration) btnDoneSyncDuration.style.display = 'none';
        if (syncDurationCloseHeaderBtn) syncDurationCloseHeaderBtn.style.display = 'block';
    }

    modalEl.addEventListener('hidden.bs.modal', function () {
        if (syncCompleted) {
            window.location.reload();
        } else {
            resetModalState();
        }
    });

    if (btnDoneSyncDuration) {
        btnDoneSyncDuration.addEventListener('click', function () {
            window.location.reload();
        });
    }

    function handleSyncError(message) {
        if (syncDurationLoadingBody) syncDurationLoadingBody.style.display = 'none';
        if (syncDurationPromptBody) syncDurationPromptBody.style.display = 'block';
        if (syncDurationErrorContainer) {
            syncDurationErrorContainer.style.display = 'block';
            if (syncDurationErrorText) syncDurationErrorText.textContent = message;
        }
        if (btnExecuteSyncDuration) {
            btnExecuteSyncDuration.disabled = false;
            btnExecuteSyncDuration.style.display = 'inline-flex';
            btnExecuteSyncDuration.innerHTML = '<i class="ti ti-clock-check me-1"></i> Synchronize Durations';
        }
        if (btnCancelSyncDuration) btnCancelSyncDuration.style.display = 'inline-block';
        if (syncDurationCloseHeaderBtn) syncDurationCloseHeaderBtn.style.display = 'block';
    }

    if (btnExecuteSyncDuration) {
        btnExecuteSyncDuration.addEventListener('click', function () {
            const syncUrl = btnExecuteSyncDuration.getAttribute('data-url');
            if (!syncUrl) return;

            // Set Loading State
            btnExecuteSyncDuration.disabled = true;
            btnExecuteSyncDuration.innerHTML = '<span class="spinner-border spinner-border-sm me-1" role="status"></span> Calculating Durations...';
            if (btnCancelSyncDuration) btnCancelSyncDuration.style.display = 'none';
            if (syncDurationCloseHeaderBtn) syncDurationCloseHeaderBtn.style.display = 'none';
            if (syncDurationPromptBody) syncDurationPromptBody.style.display = 'none';
            if (syncDurationErrorContainer) syncDurationErrorContainer.style.display = 'none';
            if (syncDurationLoadingBody) syncDurationLoadingBody.style.display = 'block';

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
                    if (syncDurationLoadingBody) syncDurationLoadingBody.style.display = 'none';
                    if (syncDurationResultBody) syncDurationResultBody.style.display = 'block';

                    const data = res.data;
                    if (resTotalPlaylists) resTotalPlaylists.textContent = Number(data.total_playlists || 0).toLocaleString();
                    if (resUpdatedPlaylists) resUpdatedPlaylists.textContent = Number(data.updated || 0).toLocaleString();
                    if (resUnchangedPlaylists) resUnchangedPlaylists.textContent = Number(data.unchanged || 0).toLocaleString();
                    if (resTotalMaterials) resTotalMaterials.textContent = Number(data.total_materials || 0).toLocaleString();

                    // Render duration list details
                    if (syncDurationDetailsList && Array.isArray(data.durations) && data.durations.length > 0) {
                        syncDurationDetailsList.innerHTML = '';
                        data.durations.forEach(function (item) {
                            const itemEl = document.createElement('div');
                            itemEl.className = 'list-group-item py-2 px-2 border-bottom';

                            const badgeClass = item.updated ? 'bg-success-lt text-success' : 'bg-secondary-lt text-secondary';
                            const badgeText = item.updated ? 'Updated' : 'Accurate';
                            const durationChange = item.updated
                                ? `<span class="text-muted text-decoration-line-through me-1">${item.old_duration}</span> &rarr; <strong class="text-success">${item.new_duration}</strong>`
                                : `<strong>${item.new_duration}</strong>`;

                            itemEl.innerHTML = `
                                <div class="d-flex justify-content-between align-items-center gap-2">
                                    <div class="text-truncate flex-grow-1" style="max-width: 65%;">
                                        <div class="fw-semibold text-truncate small text-dark">${item.title}</div>
                                        <div class="text-muted" style="font-size: 11px;">${item.materials_count} materials</div>
                                    </div>
                                    <div class="text-end flex-shrink-0">
                                        <div class="small font-monospace">${durationChange}</div>
                                        <span class="badge ${badgeClass}" style="font-size: 10px;">${badgeText}</span>
                                    </div>
                                </div>
                            `;
                            syncDurationDetailsList.appendChild(itemEl);
                        });

                        if (syncDurationDetailsContainer) {
                            syncDurationDetailsContainer.style.display = 'block';
                        }
                    }

                    if (btnExecuteSyncDuration) btnExecuteSyncDuration.style.display = 'none';
                    if (btnDoneSyncDuration) btnDoneSyncDuration.style.display = 'inline-block';
                    if (syncDurationCloseHeaderBtn) syncDurationCloseHeaderBtn.style.display = 'block';
                } else {
                    handleSyncError(res ? res.message : 'Unknown synchronization error occurred.');
                }
            }).fail(function (xhr) {
                let errMsg = 'Failed to execute playlist duration synchronization.';
                if (xhr.responseJSON && xhr.responseJSON.message) {
                    errMsg = xhr.responseJSON.message;
                }
                handleSyncError(errMsg);
            });
        });
    }
});
</script>
