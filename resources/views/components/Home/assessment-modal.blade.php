<div class="modal fade" id="modal-assessment-access" tabindex="-1" role="dialog"
     aria-labelledby="assessment-access-title" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered" role="document">
        <div class="modal-content">

            <div class="modal-header">
                <h5 class="modal-title d-flex align-items-center gap-2" id="assessment-access-title">
                    <i class="ti ti-clipboard-list home-modern-text-primary"></i>
                    Assessment Access
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>

            <div class="modal-body">
                <div class="alert alert-info d-flex align-items-center gap-2 mb-3">
                    <i class="ti ti-info-circle flex-shrink-0"></i>
                    <span>Please input the unique code to access the assessment.</span>
                </div>
                <input type="text"
                       name="unique_code"
                       id="unique-code"
                       class="form-control"
                       placeholder="Input unique code"
                       autocomplete="off">
                <div class="invalid-feedback d-none" id="invalid-text-unique-code"></div>
            </div>

            <div class="modal-footer">
                <button type="button" class="btn btn-ghost-secondary" data-bs-dismiss="modal">
                    <i class="ti ti-x me-1"></i>Cancel
                </button>
                <button type="button" onclick="submitAssessmentAccess()" class="btn btn-primary">
                    <i class="ti ti-clipboard-check me-1"></i>Submit
                </button>
            </div>

        </div>
    </div>
</div>
