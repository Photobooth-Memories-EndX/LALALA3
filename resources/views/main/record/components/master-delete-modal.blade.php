<!-- DELETE MASTER CONTENT / PLAYLIST CONFIRMATION MODAL -->
<div class="modal fade" id="deleteMasterModal" tabindex="-1" aria-labelledby="deleteMasterModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-sm">
        <div class="modal-content rounded-3 border-0 shadow">
            <div class="modal-body text-center p-4">
                <div class="text-danger mb-3">
                    <span class="avatar avatar-lg bg-danger-lt rounded-circle">
                        <i class="ti ti-alert-triangle fs-1"></i>
                    </span>
                </div>
                <h4 class="modal-title fw-bold text-dark mb-2" id="deleteMasterModalLabel">Delete Master Course?</h4>
                <p class="text-muted small mb-3">
                    Are you sure you want to delete this master entity from the catalog?
                </p>
                <div class="bg-light rounded-2 p-2 mb-3 text-start small border">
                    <div class="mb-1"><span class="text-muted">Entity:</span> <span class="badge bg-purple-lt small" id="delete-master-type-badge">Content</span></div>
                    <div><span class="text-muted">Title:</span> <strong id="delete-master-title" class="text-dark">-</strong></div>
                </div>
                <div class="alert alert-danger py-2 px-3 extra-small mb-3 text-start" style="font-size: 0.75rem;">
                    <i class="ti ti-alert-circle text-danger me-1"></i> 
                    <strong>Danger:</strong> This will soft-delete the master catalog entry. Enrolled participants will lose access to this learning material.
                </div>
                <div class="d-flex justify-content-center gap-2">
                    <button type="button" class="btn btn-outline-secondary px-3 rounded-2" data-bs-dismiss="modal">Cancel</button>
                    <button type="button" class="btn btn-danger px-3 rounded-2 d-inline-flex align-items-center gap-1" id="btn-confirm-delete-master">
                        <i class="ti ti-trash"></i>
                        <span>Delete Master</span>
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>
