{{-- Modal Schedule Detail / List by Status & Date --}}
<div class="modal fade" id="eventDetailModal" tabindex="-1" aria-labelledby="eventDetailModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg modal-dialog-scrollable">
        <div class="modal-content border-0 shadow rounded-4 overflow-hidden">
            <div class="modal-header border-bottom border-light-subtle pb-3 px-4 pt-4 bg-white">
                <div class="d-flex align-items-center gap-3">
                    <div class="rounded-3 d-flex align-items-center justify-content-center flex-shrink-0" id="modalStatusIconWrap" style="width: 44px; height: 44px; background-color: #ecfdf5; color: #059669;">
                        <i class="ti ti-calendar-event fs-2" id="modalStatusIcon"></i>
                    </div>
                    <div>
                        <div class="d-flex align-items-center gap-2 mb-1">
                            <h4 class="h4 fw-bold mb-0 text-dark" id="modalStatusTitle">Open Trainings</h4>
                            <span class="badge rounded-pill fw-semibold px-2 py-0-5 fs-6" id="modalStatusCountBadge">
                                Open: 2
                            </span>
                        </div>
                        <div class="text-muted small d-flex align-items-center gap-1" id="modalStatusDate">
                            <i class="ti ti-calendar text-primary"></i>
                            <span id="modalSelectedDateText">Friday, 18 September 2026</span>
                        </div>
                    </div>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>

            <div class="modal-body px-4 py-3" style="max-height: 60vh;">
                <!-- Training Items List Container -->
                <div class="d-flex flex-column gap-3" id="modalTrainingItemsList">
                    {{-- Dynamically populated via JavaScript --}}
                </div>

                <!-- Empty State Container -->
                <div class="text-center py-5 d-none" id="modalEmptyState">
                    <div class="avatar avatar-lg rounded-circle bg-light text-muted mb-3 mx-auto">
                        <i class="ti ti-calendar-off fs-1"></i>
                    </div>
                    <h5 class="fw-bold text-dark mb-1">No Trainings Found</h5>
                    <p class="text-muted small mb-0" id="modalEmptyStateText">
                        There are no training sessions scheduled matching this status on the selected date.
                    </p>
                </div>
            </div>

            <div class="modal-footer border-top border-light-subtle px-4 py-3 bg-light-subtle d-flex justify-content-between align-items-center">
                <button type="button" class="btn btn-outline-secondary px-3 py-2 rounded-3 fw-semibold" data-bs-dismiss="modal">
                    Close
                </button>
                <button type="button" class="btn btn-primary px-4 py-2 rounded-3 fw-semibold d-inline-flex align-items-center gap-2" id="modalBtnViewMore" style="background-color: #003b6d; border-color: #003b6d;">
                    <span>View More</span>
                    <i class="ti ti-arrow-right fs-3"></i>
                </button>
            </div>
        </div>
    </div>
</div>
