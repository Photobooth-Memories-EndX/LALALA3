<div class="modal fade" id="leaveAssessmentModal" tabindex="-1" aria-labelledby="leaveAssessmentModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content rounded-4 border-0 shadow">
            <div class="modal-header border-0 pb-0">
                <h5 class="modal-title fw-bold text-danger" id="leaveAssessmentModalLabel">
                    <i class="ti ti-alert-triangle me-1"></i> Leave Assessment?
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" data-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body py-3">
                <p class="mb-0 text-secondary">
                    Are you sure you want to leave this test? Your current progress might not be saved, and the timer will continue running.
                </p>
            </div>
            <div class="modal-footer border-0 pt-0">
                <button type="button" class="btn btn-light rounded-3 px-3" data-bs-dismiss="modal" data-dismiss="modal">
                    Cancel
                </button>
                <a href="{{ url('/my-learning?learning_tab=assessment') }}" 
                   class="btn btn-danger rounded-3 px-3 fw-semibold">
                    Yes, Leave Test
                </a>
            </div>
        </div>
    </div>
</div>
