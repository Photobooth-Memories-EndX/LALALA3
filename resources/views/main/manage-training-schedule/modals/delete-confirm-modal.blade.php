{{-- Reusable Delete Training Schedule Confirmation Modal --}}
<div class="modal fade" id="deleteScheduleModal" tabindex="-1" aria-labelledby="deleteScheduleModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content rounded-4 border-0 shadow">
            <div class="modal-header border-bottom-0 pb-0">
                <h5 class="modal-title fw-bold text-danger d-flex align-items-center gap-2" id="deleteScheduleModalLabel">
                    <i class="ti ti-alert-triangle fs-2"></i>
                    <span>Delete Training Schedule</span>
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id="deleteScheduleForm" method="POST" action="">
                @csrf
                @method('DELETE')
                <div class="modal-body py-3">
                    <p class="text-dark mb-3">Are you sure you want to delete this training schedule?</p>
                    
                    <div class="bg-light rounded-3 p-3 mb-3 border border-light-subtle">
                        <div class="mb-2">
                            <span class="text-muted extra-small text-uppercase fw-bold d-block">Training</span>
                            <span class="fw-bold text-dark fs-3" id="deleteModalTrainingTitle">-</span>
                        </div>
                        <div class="row g-2">
                            <div class="col-6">
                                <span class="text-muted extra-small text-uppercase fw-bold d-block">Code / Batch</span>
                                <span class="badge bg-primary-lt text-primary fw-semibold" id="deleteModalTrainingCode">-</span>
                            </div>
                            <div class="col-6">
                                <span class="text-muted extra-small text-uppercase fw-bold d-block">Date</span>
                                <span class="text-dark fw-medium" id="deleteModalTrainingDate">-</span>
                            </div>
                        </div>
                    </div>

                    <div class="alert alert-danger-lt border-danger-subtle rounded-3 py-2 px-3 mb-0 d-flex align-items-center gap-2">
                        <i class="ti ti-info-circle fs-3 text-danger flex-shrink-0"></i>
                        <span class="small text-danger fw-semibold">This action cannot be undone.</span>
                    </div>
                </div>
                <div class="modal-footer border-top-0 pt-0">
                    <button type="button" class="btn btn-outline-secondary rounded-3 px-4" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-danger rounded-3 px-4 fw-semibold d-inline-flex align-items-center gap-1">
                        <i class="ti ti-trash fs-2"></i>
                        <span>Delete Training</span>
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
