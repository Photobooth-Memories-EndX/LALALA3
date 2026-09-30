<!-- REUSABLE MASTER OPTION MODAL (PHASE 2F DYNAMIC OPTIONS) -->
<div class="modal fade" id="modal-add-master-option" tabindex="-1" aria-labelledby="modal-add-option-title" aria-hidden="true" data-bs-backdrop="static">
    <div class="modal-dialog modal-dialog-centered modal-sm">
        <div class="modal-content border-0 shadow">
            <div class="modal-header py-2 px-3 bg-light">
                <h5 class="modal-title fw-bold fs-6 text-dark" id="modal-add-option-title">Add New Option</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body p-3">
                <form id="form-add-master-option" onsubmit="return false;">
                    <input type="hidden" id="modal-option-type" value="">
                    <input type="hidden" id="modal-option-target-select" value="">
                    <input type="hidden" id="modal-option-target-datalist" value="">
                    <input type="hidden" id="modal-option-year" value="">

                    <div id="modal-option-alert" class="alert alert-danger py-1 px-2 small mb-2" style="display:none;"></div>
                    <div id="modal-option-info" class="alert alert-info py-1 px-2 small mb-2" style="display:none;"></div>

                    <div class="mb-2">
                        <label for="modal-option-value" class="form-label small fw-semibold mb-1" id="modal-option-value-label">Option Value</label>
                        <input type="text" class="form-control form-control-sm rounded-2" id="modal-option-value" placeholder="Enter option value..." autocomplete="off" required>
                    </div>
                    <div id="modal-option-year-container" class="mb-2" style="display:none;">
                        <label for="modal-option-year-display" class="form-label small fw-semibold mb-1">Corporate KPI Year</label>
                        <input type="number" class="form-control form-control-sm rounded-2 bg-light text-muted" id="modal-option-year-display" readonly>
                    </div>
                </form>
            </div>
            <div class="modal-footer py-2 px-3 bg-light">
                <button type="button" class="btn btn-sm btn-link text-secondary text-decoration-none" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-sm btn-primary" id="btn-save-master-option">
                    <span class="spinner-border spinner-border-sm me-1" id="spinner-save-option" style="display:none;"></span>
                    <i class="ti ti-plus me-1" id="icon-save-option"></i> Save Option
                </button>
            </div>
        </div>
    </div>
</div>
