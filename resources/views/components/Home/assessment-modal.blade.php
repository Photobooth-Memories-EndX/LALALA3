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
                <div class="alert alert-success d-flex align-items-center text-success mb-3" role="alert" style="background-color: #e8f5e9; border-color: #c8e6c9; color: #2e7d32 !important;">
                    <i class="ti ti-info-circle me-2 fs-4 flex-shrink-0"></i>
                    <div>Please input the unique code to access the assessment.</div>
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
                <button type="button" 
                        class="btn text-white" 
                        data-bs-dismiss="modal" 
                        style="background-color: #d63939 !important; border-color: #d63939 !important; color: #ffffff !important;">
                    <i class="ti ti-x me-1"></i>Cancel
                </button>
                <button type="button" 
                        onclick="submitAssessmentAccess()" 
                        class="btn text-white" 
                        style="background-color: #2fb344 !important; border-color: #2fb344 !important; color: #ffffff !important;">
                    <i class="ti ti-clipboard-check me-1"></i>Submit
                </button>
            </div>

        </div>
    </div>
</div>

<x-shared.assessment-confirmation-modal />
<x-shared.assessment-continue-modal />
<x-shared.assessment-access-modal />
@include('components.shared.assessment-result-modal')
