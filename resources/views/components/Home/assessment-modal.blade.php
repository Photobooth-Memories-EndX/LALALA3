<div class="modal fade" id="modal-assessment-access" tabindex="-1" role="dialog"
     aria-labelledby="assessment-access-title" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered px-2 px-sm-0" style="max-width: 480px; margin: 1.75rem auto;">
        <div class="modal-content rounded-4 border-0 shadow">

            <div class="modal-header border-0 pb-0">
                <h5 class="modal-title fw-bold text-dark d-flex align-items-center gap-2" id="assessment-access-title">
                    <i class="ti ti-clipboard-check text-primary fs-5"></i> Assessment Access
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>

            <div class="modal-body py-3">
                <div class="alert alert-success border-0 small mb-3" role="alert">
                    <i class="ti ti-info-circle me-1"></i> Please input the unique code to access the assessment.
                </div>
                <input type="text"
                       name="unique_code"
                       id="unique-code"
                       class="form-control"
                       placeholder="Input unique code"
                       autocomplete="off"
                       required>
                <div class="invalid-feedback d-none" id="invalid-text-unique-code"></div>
            </div>

            <!-- FOOTER ORIGINAL: Rata Kanan, Cancel Merah, Submit Hijau -->
            <div class="modal-footer border-0 pt-0 d-flex justify-content-end gap-2">
                <button type="button" 
                        class="btn btn-danger text-white fw-semibold px-3 py-2" 
                        data-bs-dismiss="modal"
                        style="background-color: #dc3545 !important; border-color: #dc3545 !important;">
                    <i class="ti ti-x me-1"></i> Cancel
                </button>
                <button type="button" 
                        onclick="submitAssessmentAccess()" 
                        class="btn btn-success text-white fw-semibold px-3 py-2"
                        style="background-color: #28a745 !important; border-color: #28a745 !important;">
                    <i class="ti ti-check me-1"></i> Submit
                </button>
            </div>

        </div>
    </div>
</div>

<x-shared.assessment-confirmation-modal />
<x-shared.assessment-continue-modal />
<x-shared.assessment-access-modal />
@include('components.shared.assessment-result-modal')
