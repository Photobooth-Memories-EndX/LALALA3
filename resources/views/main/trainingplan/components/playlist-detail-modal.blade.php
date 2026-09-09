<!-- Playlist Detail Modal -->
<div class="modal modal-blur fade playlist-detail-modal" id="playlistDetailModal" tabindex="-1" aria-labelledby="playlistDetailModalTitle" aria-hidden="true" data-bs-backdrop="static">
    <div class="modal-dialog modal-dialog-centered modal-lg" style="max-width: 780px;">
        <div class="modal-content rounded-3 border-0 shadow">
            <!-- Modal Header -->
            <div class="modal-header px-3 py-2 border-bottom">
                <div class="d-flex align-items-center gap-2 flex-grow-1 min-w-0">
                    <div class="avatar avatar-sm bg-primary-lt text-primary rounded-2 flex-shrink-0">
                        <i class="ti ti-playlist fs-3"></i>
                    </div>
                    <div class="min-w-0 flex-grow-1">
                        <h5 class="modal-title fw-bold text-dark mb-0 text-truncate" id="playlistDetailModalTitle">Playlist Details</h5>
                        <small class="text-muted d-block lh-1" id="playlistDetailModalSubtitle">View comprehensive information and syllabus for this playlist</small>
                    </div>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>

            <!-- Loading State -->
            <div class="modal-body py-4 text-center" id="playlistDetailLoading">
                <div class="spinner-border text-primary mb-2" style="width: 2.5rem; height: 2.5rem;" role="status">
                    <span class="visually-hidden">Loading...</span>
                </div>
                <h5 class="fw-bold mb-1">Loading Playlist Details...</h5>
                <p class="text-muted small mb-0">Please wait while we retrieve the playlist information.</p>
            </div>

            <!-- Error State -->
            <div class="modal-body py-3" id="playlistDetailError" style="display: none;">
                <div class="alert alert-danger d-flex align-items-center gap-2 mb-0 py-2 px-3">
                    <i class="ti ti-alert-circle fs-2 flex-shrink-0"></i>
                    <div class="flex-grow-1 min-w-0">
                        <strong class="d-block small" id="playlistDetailErrorTitle">Failed to load playlist details</strong>
                        <span class="small text-truncate" id="playlistDetailErrorMessage">Unable to load playlist details. Please try again.</span>
                    </div>
                    <button type="button" class="btn btn-sm btn-danger px-3 ms-auto d-inline-flex align-items-center gap-1 flex-shrink-0" id="btnRetryPlaylistDetail">
                        <i class="ti ti-refresh me-1" id="btnRetryPlaylistSpinner"></i>
                        <span>Retry</span>
                    </button>
                </div>
            </div>

            <!-- Content Data Body -->
            <div class="modal-body px-3 py-2" id="playlistDetailContent" style="display: none;">
                <!-- Section 1: Playlist Identity & Metadata -->
                <div class="p-3 rounded-3 bg-light border mb-2 playlist-detail-hero-box">
                    <div class="d-flex align-items-center justify-content-between flex-wrap gap-2 mb-2">
                        <div class="d-flex align-items-center gap-2 flex-grow-1 min-w-0">
                            <div class="avatar avatar-sm bg-primary-lt text-primary rounded-2 flex-shrink-0">
                                <i class="ti ti-playlist fs-2"></i>
                            </div>
                            <h4 class="fw-bold text-dark mb-0 text-break lh-sm min-w-0" id="detailPlaylistTitle">-</h4>
                        </div>
                        <div class="d-flex flex-wrap align-items-center gap-1 flex-shrink-0">
                            <span class="badge bg-success-lt" id="detailPlaylistStatusBadge">Active</span>
                            <span class="badge bg-azure-lt" id="detailPlaylistMaterialsBadge">
                                <i class="ti ti-books me-1"></i><span id="detailPlaylistMaterialsCount">0</span> Materials
                            </span>
                            <span class="badge bg-cyan-lt" id="detailPlaylistDeadlineBadge">
                                <i class="ti ti-calendar me-1"></i><span id="detailPlaylistDeadline">No Deadline</span>
                            </span>
                        </div>
                    </div>

                    <!-- Metadata Grid: Slug, URL Link, Created/Updated At -->
                    <div class="row g-2 pt-2 border-top playlist-detail-meta-grid" style="font-size: 12px;">
                        <div class="col-12">
                            <div class="d-flex align-items-start gap-1">
                                <span class="text-muted flex-shrink-0" style="min-width: 80px;"><i class="ti ti-link me-1"></i>Slug:</span>
                                <code class="font-monospace text-break user-select-all text-dark bg-white px-2 py-1 rounded border w-100" id="detailPlaylistSlug" style="word-break: break-all; white-space: pre-wrap;">-</code>
                            </div>
                        </div>
                        <div class="col-12">
                            <div class="d-flex align-items-start gap-1">
                                <span class="text-muted flex-shrink-0" style="min-width: 80px;"><i class="ti ti-external-link me-1"></i>URL Link:</span>
                                <span id="detailPlaylistUrlWrapper" class="text-break min-w-0">—</span>
                            </div>
                        </div>
                        <div class="col-sm-6">
                            <div class="d-flex align-items-center gap-1 text-muted">
                                <span style="min-width: 80px;"><i class="ti ti-calendar me-1"></i>Created:</span>
                                <span class="text-dark fw-medium font-monospace" id="detailPlaylistCreatedAt">—</span>
                            </div>
                        </div>
                        <div class="col-sm-6">
                            <div class="d-flex align-items-center gap-1 text-muted">
                                <span style="min-width: 80px;"><i class="ti ti-clock-edit me-1"></i>Updated:</span>
                                <span class="text-dark fw-medium font-monospace" id="detailPlaylistUpdatedAt">—</span>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Section 2: Learning Information Cards -->
                <div class="rounded-3 border bg-light p-2 mb-2 playlist-detail-metrics-box">
                    <div class="row g-2 align-items-center">
                        <div class="col-6">
                            <div class="p-2 rounded-2 bg-white border text-center playlist-detail-inner-card">
                                <div class="text-muted small lh-1 mb-1"><i class="ti ti-clock me-1 text-primary"></i>Total Duration</div>
                                <div class="h4 fw-bold text-dark mb-0 font-monospace" id="detailPlaylistDuration">00:00:00</div>
                            </div>
                        </div>
                        <div class="col-6">
                            <div class="p-2 rounded-2 bg-white border text-center playlist-detail-inner-card">
                                <div class="text-muted small lh-1 mb-1"><i class="ti ti-calculator me-1 text-primary"></i>Learning Hours</div>
                                <div class="h4 fw-bold text-primary mb-0 font-monospace" id="detailPlaylistLearningHour">0.0000 h</div>
                            </div>
                        </div>
                    </div>
                    <div class="text-muted text-center mt-1" style="font-size: 11px;">
                        <i class="ti ti-info-circle me-1"></i>Summed duration and calculated learning hours of assigned materials.
                    </div>
                </div>

                <!-- Section 3: Playlist Materials / Syllabus -->
                <div class="border rounded-3 p-2 bg-light playlist-detail-syllabus-box">
                    <div class="d-flex justify-content-between align-items-center mb-2 px-1">
                        <span class="small fw-semibold text-secondary d-flex align-items-center gap-1">
                            <i class="ti ti-list-numbers text-primary me-1"></i> Playlist Syllabus / Materials
                        </span>
                        <span class="badge bg-secondary-lt text-secondary" id="detailMaterialsBadgeCount">0 materials</span>
                    </div>

                    <!-- Scrollable Table Container -->
                    <div class="table-responsive bg-white rounded-2 border playlist-detail-table-container" style="max-height: 220px; overflow-y: auto;">
                        <table class="table table-sm table-hover mb-0" id="detailPlaylistMaterialsTable">
                            <thead>
                                <tr class="text-muted" style="font-size: 11px;">
                                    <th style="width: 40px;" class="text-center">#</th>
                                    <th>Material Title</th>
                                    <th style="width: 100px;">Type</th>
                                    <th style="width: 90px;" class="text-end">Duration</th>
                                </tr>
                            </thead>
                            <tbody id="detailPlaylistMaterialsTableBody" style="font-size: 12px;"></tbody>
                        </table>
                        <div class="text-center py-4 text-muted small" id="detailPlaylistEmptyMaterials" style="display: none;">
                            <i class="ti ti-playlist-x fs-2 d-block mb-1 opacity-50"></i>
                            <span>No materials assigned to this playlist.</span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Modal Footer with Context Actions -->
            <div class="modal-footer bg-light px-3 py-2 border-top d-flex justify-content-between align-items-center gap-2">
                <div class="d-flex gap-2">
                    <a href="#" class="btn btn-sm btn-info d-inline-flex align-items-center gap-1 px-3" id="btnDetailEditPlaylist">
                        <i class="ti ti-edit me-1"></i> Edit Playlist
                    </a>
                    <a href="#" class="btn btn-sm btn-secondary d-inline-flex align-items-center gap-1 px-3" id="btnDetailOrderPlaylist">
                        <i class="ti ti-arrows-sort me-1"></i> Order Content
                    </a>
                </div>
                <button type="button" class="btn btn-sm btn-outline-secondary px-3" data-bs-dismiss="modal">
                    Close
                </button>
            </div>
        </div>
    </div>
</div>

<style>
/* Scoped Dark Mode Styling for Playlist Detail Modal */
[data-bs-theme="dark"] #playlistDetailModal .modal-content,
body.dark-mode #playlistDetailModal .modal-content,
body.theme-dark #playlistDetailModal .modal-content {
    background-color: var(--tblr-bg-surface, #1f2937) !important;
    color: #f8fafc !important;
}
[data-bs-theme="dark"] #playlistDetailModal .modal-header,
[data-bs-theme="dark"] #playlistDetailModal .modal-footer,
body.dark-mode #playlistDetailModal .modal-header,
body.dark-mode #playlistDetailModal .modal-footer,
body.theme-dark #playlistDetailModal .modal-header,
body.theme-dark #playlistDetailModal .modal-footer {
    background-color: var(--tblr-bg-surface-secondary, #111827) !important;
    border-color: rgba(255, 255, 255, 0.08) !important;
}
[data-bs-theme="dark"] #playlistDetailModal .bg-light,
[data-bs-theme="dark"] #playlistDetailModal .playlist-detail-hero-box,
[data-bs-theme="dark"] #playlistDetailModal .playlist-detail-metrics-box,
[data-bs-theme="dark"] #playlistDetailModal .playlist-detail-syllabus-box,
body.dark-mode #playlistDetailModal .bg-light,
body.dark-mode #playlistDetailModal .playlist-detail-hero-box,
body.dark-mode #playlistDetailModal .playlist-detail-metrics-box,
body.dark-mode #playlistDetailModal .playlist-detail-syllabus-box,
body.theme-dark #playlistDetailModal .bg-light,
body.theme-dark #playlistDetailModal .playlist-detail-hero-box,
body.theme-dark #playlistDetailModal .playlist-detail-metrics-box,
body.theme-dark #playlistDetailModal .playlist-detail-syllabus-box {
    background-color: var(--tblr-bg-surface-secondary, #111827) !important;
    border-color: rgba(255, 255, 255, 0.08) !important;
}
[data-bs-theme="dark"] #playlistDetailModal .playlist-detail-inner-card,
[data-bs-theme="dark"] #playlistDetailModal .playlist-detail-table-container,
body.dark-mode #playlistDetailModal .playlist-detail-inner-card,
body.dark-mode #playlistDetailModal .playlist-detail-table-container,
body.theme-dark #playlistDetailModal .playlist-detail-inner-card,
body.theme-dark #playlistDetailModal .playlist-detail-table-container {
    background-color: var(--tblr-bg-surface, #1f2937) !important;
    border-color: rgba(255, 255, 255, 0.08) !important;
    color: #f8fafc !important;
}
[data-bs-theme="dark"] #playlistDetailModal .text-dark,
body.dark-mode #playlistDetailModal .text-dark,
body.theme-dark #playlistDetailModal .text-dark {
    color: #f8fafc !important;
}
[data-bs-theme="dark"] #playlistDetailModal .table,
body.dark-mode #playlistDetailModal .table,
body.theme-dark #playlistDetailModal .table {
    color: #f8fafc !important;
}
[data-bs-theme="dark"] #playlistDetailModal .table th,
[data-bs-theme="dark"] #playlistDetailModal .table td,
body.dark-mode #playlistDetailModal .table th,
body.dark-mode #playlistDetailModal .table td,
body.theme-dark #playlistDetailModal .table th,
body.theme-dark #playlistDetailModal .table td {
    border-color: rgba(255, 255, 255, 0.08) !important;
}
[data-bs-theme="dark"] #playlistDetailModal .playlist-detail-meta-grid code,
body.dark-mode #playlistDetailModal .playlist-detail-meta-grid code,
body.theme-dark #playlistDetailModal .playlist-detail-meta-grid code {
    background-color: var(--tblr-bg-surface, #1f2937) !important;
    color: #f8fafc !important;
    border-color: rgba(255, 255, 255, 0.12) !important;
}
[data-bs-theme="dark"] #playlistDetailModal .border-top,
body.dark-mode #playlistDetailModal .border-top,
body.theme-dark #playlistDetailModal .border-top {
    border-color: rgba(255, 255, 255, 0.08) !important;
}
</style>

<script>
document.addEventListener('DOMContentLoaded', function () {
    const detailModalEl = document.getElementById('playlistDetailModal');
    if (!detailModalEl) return;

    const loadingEl = document.getElementById('playlistDetailLoading');
    const errorEl = document.getElementById('playlistDetailError');
    const contentEl = document.getElementById('playlistDetailContent');
    const errorMsgEl = document.getElementById('playlistDetailErrorMessage');
    const btnRetry = document.getElementById('btnRetryPlaylistDetail');

    let currentPlaylistId = null;

    function showPlaylistDetailModal() {
        if (window.bootstrap && window.bootstrap.Modal && typeof window.bootstrap.Modal.getOrCreateInstance === 'function') {
            window.bootstrap.Modal.getOrCreateInstance(detailModalEl).show();
            return;
        }
        if (window.bootstrap && typeof window.bootstrap.Modal === 'function') {
            new window.bootstrap.Modal(detailModalEl).show();
            return;
        }
        if (typeof $ !== 'undefined' && $.fn && typeof $.fn.modal === 'function') {
            $(detailModalEl).modal('show');
            return;
        }
        detailModalEl.style.display = 'block';
        detailModalEl.classList.add('show');
    }

    function showDetailError(message) {
        if (loadingEl) loadingEl.style.display = 'none';
        if (contentEl) contentEl.style.display = 'none';
        if (errorEl) errorEl.style.display = 'block';
        if (errorMsgEl) errorMsgEl.textContent = message;
    }

    function renderPlaylistDetail(data) {
        if (!data) return;

        // Title & Slug
        const titleEl = document.getElementById('detailPlaylistTitle');
        const slugEl = document.getElementById('detailPlaylistSlug');
        if (titleEl) titleEl.textContent = data.title || '-';
        if (slugEl) slugEl.textContent = data.slug || '-';

        // URL Link
        const urlWrapper = document.getElementById('detailPlaylistUrlWrapper');
        if (urlWrapper) {
            if (data.url_link && data.url_link.trim() !== '') {
                const safeUrl = encodeURI(data.url_link);
                urlWrapper.innerHTML = '<a href="' + safeUrl + '" target="_blank" rel="noopener noreferrer" class="text-primary text-decoration-none fw-medium d-inline-flex align-items-center gap-1"><i class="ti ti-external-link"></i><span class="text-break">' + $('<div>').text(data.url_link).html() + '</span></a>';
            } else {
                urlWrapper.innerHTML = '<span class="text-muted">—</span>';
            }
        }

        // Created At & Updated At
        const createdAtEl = document.getElementById('detailPlaylistCreatedAt');
        const updatedAtEl = document.getElementById('detailPlaylistUpdatedAt');
        if (createdAtEl) createdAtEl.textContent = data.created_at_formatted || data.created_at || '—';
        if (updatedAtEl) updatedAtEl.textContent = data.updated_at || '—';

        // Status Badge
        const statusBadge = document.getElementById('detailPlaylistStatusBadge');
        if (statusBadge) {
            const isActive = (data.status === 'Active' || data.active === 'Active');
            statusBadge.className = 'badge ' + (isActive ? 'bg-success-lt text-success' : 'bg-warning-lt text-warning');
            statusBadge.textContent = data.status || data.active || '-';
        }

        // Materials Count Badge
        const materialsCountEl = document.getElementById('detailPlaylistMaterialsCount');
        const materialsBadgeCount = document.getElementById('detailMaterialsBadgeCount');
        const count = Array.isArray(data.materials) ? data.materials.length : (data.materials_count || 0);
        if (materialsCountEl) materialsCountEl.textContent = count;
        if (materialsBadgeCount) materialsBadgeCount.textContent = count + (count === 1 ? ' material' : ' materials');

        // Deadline Badge
        const deadlineBadge = document.getElementById('detailPlaylistDeadlineBadge');
        const deadlineEl = document.getElementById('detailPlaylistDeadline');
        if (deadlineEl) deadlineEl.textContent = data.deadline || 'No Deadline';
        if (deadlineBadge) {
            if (data.has_deadline) {
                deadlineBadge.className = 'badge ' + (data.is_expired ? 'bg-danger-lt text-danger' : 'bg-cyan-lt text-cyan');
            } else {
                deadlineBadge.className = 'badge bg-secondary-lt text-secondary';
            }
        }

        // Duration & Learning Hours
        const durationEl = document.getElementById('detailPlaylistDuration');
        const learningHourEl = document.getElementById('detailPlaylistLearningHour');
        if (durationEl) durationEl.textContent = data.total_duration || '00:00:00';
        if (learningHourEl) learningHourEl.textContent = data.learning_hours || '0.0000 h';

        // Materials Table
        const tableBody = document.getElementById('detailPlaylistMaterialsTableBody');
        const emptyState = document.getElementById('detailPlaylistEmptyMaterials');
        const tableEl = document.getElementById('detailPlaylistMaterialsTable');

        if (tableBody) {
            tableBody.innerHTML = '';
            if (Array.isArray(data.materials) && data.materials.length > 0) {
                data.materials.forEach(function (mat, index) {
                    const tr = document.createElement('tr');
                    const rowNum = mat.display_order ?? (index + 1);
                    const typeLabel = mat.type_label ?? mat.type ?? 'Other';
                    const durationText = mat.duration ?? '-';

                    tr.innerHTML = `
                        <td class="text-center text-muted font-monospace">${rowNum}</td>
                        <td>
                            <div class="fw-semibold text-dark text-truncate" style="max-width: 360px;" title="${mat.title ?? ''}">
                                ${mat.title ?? '-'}
                            </div>
                        </td>
                        <td>
                            <span class="badge bg-secondary-lt">${typeLabel}</span>
                        </td>
                        <td class="text-end font-monospace">
                            ${durationText}
                        </td>
                    `;
                    tableBody.appendChild(tr);
                });
                if (tableEl) tableEl.style.display = 'table';
                if (emptyState) emptyState.style.display = 'none';
            } else {
                if (tableEl) tableEl.style.display = 'none';
                if (emptyState) emptyState.style.display = 'block';
            }
        }

        // Action Buttons
        const editBtn = document.getElementById('btnDetailEditPlaylist');
        const orderBtn = document.getElementById('btnDetailOrderPlaylist');
        if (editBtn && data.edit_url) editBtn.href = data.edit_url;
        if (orderBtn && data.order_url) orderBtn.href = data.order_url;
    }

    function loadPlaylistDetail(playlistId) {
        currentPlaylistId = playlistId;
        showPlaylistDetailModal();

        if (loadingEl) loadingEl.style.display = 'block';
        if (errorEl) errorEl.style.display = 'none';
        if (contentEl) contentEl.style.display = 'none';

        const url = "{{ url('/manage/playlist') }}/" + encodeURIComponent(playlistId) + "/details";

        $.ajax({
            url: url,
            type: 'GET',
            dataType: 'json',
            headers: {
                'Accept': 'application/json'
            }
        }).done(function (res) {
            if (res && res.success && res.data) {
                renderPlaylistDetail(res.data);
                if (loadingEl) loadingEl.style.display = 'none';
                if (errorEl) errorEl.style.display = 'none';
                if (contentEl) contentEl.style.display = 'block';
            } else {
                showDetailError(res && res.message ? res.message : 'Unable to load playlist details. Please try again.');
            }
        }).fail(function (xhr, textStatus) {
            let msg = 'Unable to load playlist details. Please try again.';
            if (xhr.status === 403) {
                msg = 'You do not have permission to view this playlist.';
            } else if (xhr.status === 404) {
                msg = 'Playlist not found.';
            } else if (xhr.status === 500) {
                msg = 'Internal server error while loading playlist details.';
            } else if (xhr.status === 0 || textStatus === 'timeout') {
                msg = 'Network connection failed. Please check your internet and try again.';
            } else if (xhr.responseJSON && xhr.responseJSON.message) {
                msg = xhr.responseJSON.message;
            }
            showDetailError(msg);
        });
    }

    // Delegated click listener for Eye buttons across table and pagination
    document.addEventListener('click', function (e) {
        const btn = e.target.closest('.btn-view-playlist-detail');
        if (!btn) return;
        e.preventDefault();
        const playlistId = btn.getAttribute('data-id');
        if (playlistId) {
            loadPlaylistDetail(playlistId);
        }
    });

    // Retry button handler
    if (btnRetry) {
        btnRetry.addEventListener('click', function () {
            if (currentPlaylistId) {
                loadPlaylistDetail(currentPlaylistId);
            }
        });
    }
});
</script>
