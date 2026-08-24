<div class="modal modal-blur fade" id="modal-batch-calculation" data-bs-backdrop="static" data-bs-keyboard="false" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header border-0 pb-0">
                <h5 class="modal-title" id="batch-modal-title">Bulk Calculation Progress</h5>
            </div>
            <div class="modal-body py-4">
                <div class="text-center mb-3">
                    <span class="avatar avatar-lg bg-primary-lt text-primary rounded-circle mb-2" id="batch-modal-icon">
                        <i class="ti ti-hourglass-high fs-1"></i>
                    </span>
                    <h4 class="mb-1" id="batch-modal-heading">Processing Learning Hours...</h4>
                    <p class="text-muted small mb-0" id="batch-modal-desc">
                        Processing Content User records in safe database chunks. Please keep this window open.
                    </p>
                </div>

                <div class="progress progress-lg mb-2" style="height: 18px;">
                    <div class="progress-bar progress-bar-striped progress-bar-animated bg-primary" id="batch-progress-bar" role="progressbar" style="width: 0%;" aria-valuenow="0" aria-valuemin="0" aria-valuemax="100">
                        <span id="batch-progress-text" class="fw-bold small">0%</span>
                    </div>
                </div>

                <div class="d-flex justify-content-between text-muted small mt-2">
                    <span>Processed: <strong class="text-reset" id="batch-processed-count">0</strong> / <span id="batch-total-count">0</span></span>
                    <span>Status: <strong class="text-primary" id="batch-status-text">Starting...</strong></span>
                </div>
            </div>
            <div class="modal-footer border-0 pt-0 justify-content-center">
                <button type="button" class="btn btn-secondary" id="btn-cancel-batch" style="display: none;" data-bs-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>
