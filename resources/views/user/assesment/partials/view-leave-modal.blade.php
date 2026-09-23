<div class="modal fade" id="leaveAssessmentModal" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered" role="document">
        <div class="modal-content rounded-4 border-0 shadow">
            <div class="modal-header border-0 pb-0">
                <h5 class="modal-title fw-bold text-danger">
                    <i class="ti ti-alert-triangle me-1"></i> Leave Assessment?
                </h5>
                <button type="button" class="btn-close close" data-bs-dismiss="modal" data-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body py-3 assessment-modal-body fw-normal">
                You have answered <strong id="answeredCount">0</strong> out of <strong id="totalQuestionsCount">0</strong> questions. 
                Are you sure you want to leave this test? Leaving the test will automatically submit your current answers and generate your test results.
            </div>
            <div class="modal-footer border-0 pt-0">
                <button type="button" class="btn btn-light rounded-pill px-4 text-secondary" data-bs-dismiss="modal" data-dismiss="modal">Cancel</button>
                <button type="button" id="btnConfirmLeaveTest" class="btn btn-danger rounded-pill px-4 fw-semibold">Yes, Leave Test</button>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="finishAssessmentModal" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered" role="document">
        <div class="modal-content rounded-4 border-0 shadow">
            <div class="modal-header border-0 pb-0">
                <h5 class="modal-title fw-bold" style="color: #28be58 !important;">
                    <i class="ti ti-circle-check me-1"></i> Submit Assessment?
                </h5>
                <button type="button" class="btn-close close" data-bs-dismiss="modal" data-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body py-3 assessment-modal-body fw-normal">
                You have answered all questions. Are you sure you want to submit your assessment?
            </div>
            <div class="modal-footer border-0 pt-0">
                <button type="button" class="btn btn-light rounded-pill px-4 text-secondary" data-bs-dismiss="modal" data-dismiss="modal">Cancel</button>
                <button type="button" id="btnConfirmFinishSubmit" class="btn rounded-pill px-4 fw-semibold text-white shadow-sm" style="background-color: #28be58 !important; border-color: #28be58 !important;">Yes, Submit Test</button>
            </div>
        </div>
    </div>
</div>
