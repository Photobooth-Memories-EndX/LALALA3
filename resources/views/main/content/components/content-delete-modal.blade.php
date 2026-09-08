<!-- Delete Content Confirmation Modal -->
<div class="modal modal-blur fade" id="deleteContentModal" tabindex="-1" aria-labelledby="deleteContentModalTitle" aria-hidden="true" role="dialog">
    <div class="modal-dialog modal-sm modal-dialog-centered" role="document">
        <div class="modal-content rounded-3 border-0 shadow">
            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close" style="position: absolute; top: 12px; right: 12px; z-index: 10;"></button>
            <div class="modal-status bg-danger"></div>
            <div class="modal-body text-center py-4 px-3">
                <span class="avatar avatar-md bg-danger-lt text-danger rounded-circle mb-3 d-inline-flex align-items-center justify-content-center">
                    <i class="ti ti-alert-triangle fs-1"></i>
                </span>
                <h3 class="fw-bold text-dark mb-1" id="deleteContentModalTitle">Delete Content</h3>
                <p class="text-muted small mb-2">
                    Are you sure you want to delete this content?
                </p>
                <div class="p-2 rounded-2 bg-light border mb-2" id="deleteContentItemNameContainer" style="display: none;">
                    <strong class="text-dark small text-break" id="deleteContentItemTitle">-</strong>
                </div>
                <p class="text-danger small mb-0 fw-semibold">
                    <i class="ti ti-info-circle me-1"></i>This action cannot be undone.
                </p>
            </div>
            <div class="modal-footer bg-light px-3 py-2 border-top d-flex justify-content-end gap-2">
                <button type="button" class="btn btn-outline-secondary btn-sm px-3" data-bs-dismiss="modal" id="btnCancelDeleteContent">
                    Cancel
                </button>
                <button type="button" class="btn btn-danger btn-sm px-3 d-inline-flex align-items-center gap-1" id="btnConfirmDeleteContent">
                    <i class="ti ti-trash me-1" id="btnConfirmDeleteIcon"></i>
                    <span id="btnConfirmDeleteText">Delete</span>
                </button>
            </div>
        </div>
    </div>
</div>
