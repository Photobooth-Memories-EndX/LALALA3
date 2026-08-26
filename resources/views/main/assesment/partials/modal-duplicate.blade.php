<div class="modal fade" id="modalDuplicateAssessment" tabindex="-1" aria-labelledby="modalDuplicateAssessmentLabel" aria-hidden="true" role="dialog">
    <div class="modal-dialog modal-dialog-centered" style="max-width: 480px;">
        <form id="formDuplicateAssessment" method="POST" action="" class="modal-content rounded-4 border-0 shadow">
            @csrf

            <div class="modal-header border-0 pb-0">
                <h4 class="modal-title fw-bold d-flex align-items-center" id="modalDuplicateAssessmentLabel">
                    <i class="fas fa-copy text-warning mr-2 me-2"></i>
                    Duplicate Assessment
                </h4>
                <button type="button" class="btn-close close" data-bs-dismiss="modal" data-dismiss="modal" aria-label="Close"></button>
            </div>

            <div class="modal-body py-3">
                <p class="text-muted mb-3">
                    Are you sure you want to duplicate this assessment?
                </p>

                <div class="p-3 bg-light rounded-3 border mb-3 duplicate-target-box">
                    <div class="text-muted small mb-1">Assessment Target:</div>
                    <div class="fw-bold text-dark duplicate-target-title" id="duplicateAssessmentTitle" style="word-break: break-word;">
                        -
                    </div>
                </div>

                <div class="text-muted small d-flex align-items-start">
                    <i class="fas fa-info-circle mr-2 me-2 text-info mt-1"></i>
                    <span>A new assessment copy will be created with its configuration and questions preserved. Participant attempts and unique code will not be copied.</span>
                </div>
            </div>

            <div class="modal-footer border-top bg-light rounded-bottom-4">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal" data-dismiss="modal" id="btnCancelDuplicate">
                    Cancel
                </button>
                <button type="submit" class="btn btn-warning text-dark font-weight-semibold" id="btnSubmitDuplicate">
                    <i class="fas fa-copy mr-1 me-1"></i>
                    <span class="btn-text">Duplicate Assessment</span>
                </button>
            </div>
        </form>
    </div>
</div>
