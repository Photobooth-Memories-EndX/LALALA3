<!-- DELETE CONFIRMATION MODAL -->
<div class="modal fade" id="deleteOfflineRecordModal" tabindex="-1" aria-labelledby="deleteOfflineRecordModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-sm">
        <div class="modal-content rounded-3 border-0 shadow">
            <div class="modal-body text-center p-4">
                <div class="text-danger mb-3">
                    <span class="avatar avatar-lg bg-danger-lt rounded-circle">
                        <i class="ti ti-trash fs-1"></i>
                    </span>
                </div>
                <h4 class="modal-title fw-bold text-dark mb-2" id="deleteOfflineRecordModalLabel">Delete Training Record?</h4>
                <p class="text-muted small mb-3">
                    Are you sure you want to delete this offline training record?
                </p>
                <div class="bg-light rounded-2 p-2 mb-3 text-start small border">
                    <div class="mb-1"><span class="text-muted">Course:</span> <strong id="delete-modal-course-name" class="text-dark">-</strong></div>
                    <div class="mb-1"><span class="text-muted">Participant:</span> <strong id="delete-modal-employee-name" class="text-dark">-</strong></div>
                    <div id="delete-modal-nip-wrapper" style="display: none;"><span class="text-muted">NIP:</span> <span id="delete-modal-nip" class="text-dark fw-semibold">-</span></div>
                </div>
                <p class="text-muted extra-small mb-3" style="font-size: 0.75rem;">
                    <i class="ti ti-alert-triangle text-warning me-1"></i> This action cannot be undone.
                </p>
                <div class="d-flex justify-content-center gap-2">
                    <button type="button" class="btn btn-outline-secondary px-3 rounded-2" data-bs-dismiss="modal">Cancel</button>
                    <button type="button" class="btn btn-danger px-3 rounded-2 d-inline-flex align-items-center gap-1" id="btn-confirm-delete-offline-record">
                        <i class="ti ti-trash"></i>
                        <span>Delete Record</span>
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>
