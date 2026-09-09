<!-- Playlist Delete Confirmation Modal -->
<div class="modal modal-blur fade playlist-delete-modal" id="modalDeletePlaylist" tabindex="-1" aria-labelledby="modalDeletePlaylistTitle" aria-hidden="true" data-bs-backdrop="static">
    <div class="modal-dialog modal-dialog-centered" style="max-width: 520px;">
        <div class="modal-content rounded-3 border-0 shadow">
            <!-- Modal Header -->
            <div class="modal-header px-3 py-2 border-bottom">
                <div class="d-flex align-items-center gap-2 flex-grow-1 min-w-0">
                    <div class="avatar avatar-sm bg-danger-lt text-danger rounded-2 flex-shrink-0">
                        <i class="ti ti-trash fs-3"></i>
                    </div>
                    <div class="min-w-0 flex-grow-1">
                        <h5 class="modal-title fw-bold text-dark mb-0 text-truncate" id="modalDeletePlaylistTitle">Delete Playlist</h5>
                        <small class="text-muted d-block lh-1">Confirm playlist removal from the system</small>
                    </div>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close" id="btnCloseDeleteModalHeader"></button>
            </div>

            <!-- Modal Body -->
            <div class="modal-body px-3 py-3">
                <p class="text-dark mb-3" style="font-size: 0.95rem;">
                    Are you sure you want to delete this playlist?
                </p>

                <!-- Playlist Summary Card -->
                <div class="p-3 rounded-3 bg-light border mb-3 playlist-delete-card">
                    <h4 class="fw-bold text-dark mb-2 text-break" id="deleteModalPlaylistTitle">-</h4>
                    <div class="d-flex flex-wrap align-items-center gap-2">
                        <span class="badge bg-azure-lt" id="deleteModalMaterialsBadge">
                            <i class="ti ti-books me-1"></i><span id="deleteModalMaterialsCount">0</span> materials
                        </span>
                        <span class="badge" id="deleteModalStatusBadge">Active</span>
                    </div>
                </div>

                <!-- Active Playlist Warning Alert (Shown only when status is Active) -->
                <div class="alert alert-warning d-flex align-items-start gap-2 mb-3 py-2 px-3" id="deleteModalActiveWarning" style="display: none;">
                    <i class="ti ti-alert-triangle fs-2 text-warning flex-shrink-0 mt-1"></i>
                    <div>
                        <strong class="d-block text-dark small mb-1">Active Playlist Warning</strong>
                        <span class="small text-secondary">
                            This playlist is currently active and may be available to learners. Deleting it will remove it from the active playlist catalog.
                        </span>
                    </div>
                </div>

                <!-- Informational Safety Notice -->
                <div class="p-2 rounded-2 bg-light border-0 mb-0">
                    <p class="text-muted small mb-0 d-flex align-items-center gap-1">
                        <i class="ti ti-info-circle text-primary fs-3 flex-shrink-0"></i>
                        <span>Underlying learning materials will remain available in the repository. User learning history will be preserved.</span>
                    </p>
                </div>

                <!-- Error Alert (Hidden by default) -->
                <div class="alert alert-danger d-flex align-items-center gap-2 mt-3 mb-0 py-2 px-3" id="deleteModalErrorAlert" style="display: none;">
                    <i class="ti ti-alert-circle fs-3 text-danger flex-shrink-0"></i>
                    <span class="small flex-grow-1" id="deleteModalErrorMessage">Failed to delete playlist.</span>
                </div>
            </div>

            <!-- Modal Footer -->
            <div class="modal-footer px-3 py-2 bg-light d-flex justify-content-between align-items-center">
                <button type="button" class="btn btn-light" data-bs-dismiss="modal" id="btnCancelDeletePlaylist">Cancel</button>
                <button type="button" class="btn btn-danger d-inline-flex align-items-center gap-1" id="btnConfirmDeletePlaylist">
                    <span class="spinner-border spinner-border-sm me-1 d-none" id="deleteSpinner" role="status" aria-hidden="true"></span>
                    <i class="ti ti-trash me-1 icon-delete" id="deleteIcon"></i>
                    <span class="btn-text" id="deleteBtnText">Delete Playlist</span>
                </button>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
    var modalEl = document.getElementById('modalDeletePlaylist');
    if (!modalEl) return;

    var titleEl = document.getElementById('deleteModalPlaylistTitle');
    var countEl = document.getElementById('deleteModalMaterialsCount');
    var statusBadgeEl = document.getElementById('deleteModalStatusBadge');
    var activeWarningEl = document.getElementById('deleteModalActiveWarning');
    var errorAlertEl = document.getElementById('deleteModalErrorAlert');
    var errorMessageEl = document.getElementById('deleteModalErrorMessage');
    var confirmBtn = document.getElementById('btnConfirmDeletePlaylist');
    var cancelBtn = document.getElementById('btnCancelDeletePlaylist');
    var headerCloseBtn = document.getElementById('btnCloseDeleteModalHeader');
    var spinnerEl = document.getElementById('deleteSpinner');
    var iconEl = document.getElementById('deleteIcon');
    var btnTextEl = document.getElementById('deleteBtnText');

    var currentDeleteUrl = '';

    // Delegate click on delete triggers
    $(document).on('click', '.btn-delete-playlist', function (e) {
        e.preventDefault();
        var $btn = $(this);

        var title = $btn.data('title') || 'Untitled Playlist';
        var materialsCount = $btn.data('materials-count') || 0;
        var status = $btn.data('status') || 'Inactive';
        var deleteUrl = $btn.data('delete-url') || '';

        currentDeleteUrl = deleteUrl;

        if (titleEl) titleEl.textContent = title;
        if (countEl) countEl.textContent = materialsCount;

        if (statusBadgeEl) {
            statusBadgeEl.textContent = status;
            if (status === 'Active') {
                statusBadgeEl.className = 'badge bg-success-lt text-success';
                if (activeWarningEl) activeWarningEl.style.display = 'flex';
            } else {
                statusBadgeEl.className = 'badge bg-warning-lt text-warning';
                if (activeWarningEl) activeWarningEl.style.display = 'none';
            }
        }

        // Reset error state
        if (errorAlertEl) errorAlertEl.style.display = 'none';
        if (errorMessageEl) errorMessageEl.textContent = '';

        // Reset button state
        resetDeleteButtonState();

        // Show Modal
        if (typeof bootstrap !== 'undefined' && bootstrap.Modal) {
            var modalInstance = bootstrap.Modal.getOrCreateInstance(modalEl);
            modalInstance.show();
        } else if (typeof $ !== 'undefined' && $.fn && $.fn.modal) {
            $(modalEl).modal('show');
        }
    });

    function resetDeleteButtonState() {
        if (!confirmBtn) return;
        confirmBtn.disabled = false;
        confirmBtn.removeAttribute('data-loading');
        if (cancelBtn) cancelBtn.disabled = false;
        if (headerCloseBtn) headerCloseBtn.disabled = false;
        if (spinnerEl) spinnerEl.classList.add('d-none');
        if (iconEl) iconEl.classList.remove('d-none');
        if (btnTextEl) btnTextEl.textContent = 'Delete Playlist';
    }

    function setDeleteButtonLoading() {
        if (!confirmBtn) return;
        confirmBtn.disabled = true;
        confirmBtn.setAttribute('data-loading', 'true');
        if (cancelBtn) cancelBtn.disabled = true;
        if (headerCloseBtn) headerCloseBtn.disabled = true;
        if (spinnerEl) spinnerEl.classList.remove('d-none');
        if (iconEl) iconEl.classList.add('d-none');
        if (btnTextEl) btnTextEl.textContent = 'Deleting...';
    }

    function hideDeleteModal() {
        if (typeof bootstrap !== 'undefined' && bootstrap.Modal) {
            var modalInstance = bootstrap.Modal.getInstance(modalEl);
            if (modalInstance) modalInstance.hide();
        } else if (typeof $ !== 'undefined' && $.fn && $.fn.modal) {
            $(modalEl).modal('hide');
        }
    }

    // Confirm Delete Handler
    if (confirmBtn) {
        confirmBtn.addEventListener('click', function (e) {
            e.preventDefault();

            // Double click protection
            if (confirmBtn.getAttribute('data-loading') === 'true') {
                return;
            }

            if (!currentDeleteUrl) {
                if (errorAlertEl && errorMessageEl) {
                    errorMessageEl.textContent = 'Invalid delete target URL.';
                    errorAlertEl.style.display = 'flex';
                }
                return;
            }

            setDeleteButtonLoading();
            if (errorAlertEl) errorAlertEl.style.display = 'none';

            var csrfToken = $('meta[name="csrf-token"]').attr('content') || '{{ csrf_token() }}';

            $.ajax({
                url: currentDeleteUrl,
                type: 'DELETE',
                data: {
                    _token: csrfToken
                },
                headers: {
                    'X-CSRF-TOKEN': csrfToken,
                    'Accept': 'application/json'
                }
            }).done(function (res) {
                if (res && res.success) {
                    hideDeleteModal();
                    if (typeof showPlaylistToast === 'function') {
                        showPlaylistToast(res.message || 'Playlist deleted successfully.', 'success');
                    }
                    setTimeout(function () {
                        window.location.reload();
                    }, 400);
                } else {
                    var msg = (res && res.message) ? res.message : 'Failed to delete playlist.';
                    if (errorAlertEl && errorMessageEl) {
                        errorMessageEl.textContent = msg;
                        errorAlertEl.style.display = 'flex';
                    }
                    resetDeleteButtonState();
                }
            }).fail(function (xhr) {
                var message = 'Failed to delete playlist. Please try again.';
                if (xhr && xhr.responseJSON && xhr.responseJSON.message) {
                    message = xhr.responseJSON.message;
                } else if (xhr.status === 404) {
                    message = 'This playlist has already been deleted.';
                }

                if (errorAlertEl && errorMessageEl) {
                    errorMessageEl.textContent = message;
                    errorAlertEl.style.display = 'flex';
                }

                resetDeleteButtonState();

                // If already deleted, refresh page after brief delay
                if (xhr.status === 404) {
                    setTimeout(function () {
                        window.location.reload();
                    }, 1200);
                }
            });
        });
    }

    // Toast helper fallback if not globally defined
    if (typeof window.showPlaylistToast !== 'function') {
        window.showPlaylistToast = function (message, type) {
            var bgClass = type === 'success' ? 'bg-success' : 'bg-danger';
            var icon = type === 'success' ? 'ti ti-check' : 'ti ti-alert-circle';
            var toastHtml = '<div class="toast owl-toast ' + bgClass + ' text-white align-items-center mb-2 shadow" role="alert" aria-live="assertive" aria-atomic="true">' +
                '<div class="toast-body d-flex align-items-center justify-content-between p-3">' +
                '<div class="d-flex align-items-center">' +
                '<i class="' + icon + ' me-2 fs-3"></i>' +
                '<span>' + message + '</span>' +
                '</div>' +
                '</div>' +
                '</div>';
            if ($('#owl-toast-wrapper').length === 0) {
                $('body').append('<div id="owl-toast-wrapper" style="position: fixed; top: 1.5rem; right: 1.5rem; z-index: 9999; width: 320px;"></div>');
            }
            var $toast = $(toastHtml);
            $('#owl-toast-wrapper').append($toast);
            $toast.fadeIn(200).delay(3000).fadeOut(400, function () { $(this).remove(); });
        };
    }
});
</script>