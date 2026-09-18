{{-- Modal Schedule Detail --}}
<div class="modal fade" id="eventDetailModal" tabindex="-1" aria-labelledby="eventDetailModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content border-0 shadow rounded-4 overflow-hidden">
            <div class="modal-header border-0 pb-0 px-4 pt-4">
                <div class="d-flex align-items-center gap-2">
                    <span class="badge px-3 py-2 rounded-pill fw-semibold" id="modalEventBadge" style="background-color: #eef2ff; color: #003b6d;">
                        Training Schedule
                    </span>
                    <span class="badge bg-success-lt px-3 py-2 rounded-pill fw-semibold" id="modalEventCategory">
                        Soft Skill
                    </span>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>

            <div class="modal-body px-4 py-3">
                <!-- Modal Title Header Section -->
                <div class="d-flex align-items-center gap-3 mb-2">
                    <div class="rounded-4 d-flex align-items-center justify-content-center flex-shrink-0 shadow-sm" style="width: 56px; height: 56px; background-color: #eef2ff; color: #003b6d;">
                        <i class="ti ti-calendar-event fs-1"></i>
                    </div>
                    <div>
                        <h4 class="h4 fw-bold mb-1 text-dark" id="modalEventTitle">Leadership Development Program</h4>
                        <div class="text-muted small d-flex align-items-center gap-1" id="modalEventInstructorWrap">
                            <i class="ti ti-user fs-2 text-primary"></i>
                            <span>Instructor: <strong class="text-dark" id="modalEventInstructor">Dr. Hendra Wijaya, M.M.</strong></span>
                        </div>
                    </div>
                </div>

                <!-- Info Box Container -->
                <div class="rounded-4 p-3 mt-4 mb-4" style="background-color: #f8fafc; border: 1px solid #e2e8f0;">
                    <div class="row g-3 align-items-center">
                        <!-- 1. Date & Time -->
                        <div class="col-12 col-md-4 d-flex align-items-center gap-3">
                            <div class="rounded-circle d-flex align-items-center justify-content-center flex-shrink-0 shadow-sm" style="width: 42px; height: 42px; background-color: #ffffff; color: #003b6d; border: 1px solid #cbd5e1;">
                                <i class="ti ti-calendar fs-2"></i>
                            </div>
                            <div class="min-w-0">
                                <div class="text-muted extra-small fw-medium" style="font-size: 0.75rem;">Date & Time</div>
                                <div class="fw-semibold text-dark small" id="modalEventDateTime" style="line-height: 1.3;">18 September 2026,<br>08:00 - 16:00 WIB</div>
                            </div>
                        </div>

                        <!-- 2. Location / Room -->
                        <div class="col-12 col-md-4 d-flex align-items-center gap-3">
                            <div class="rounded-circle d-flex align-items-center justify-content-center flex-shrink-0 shadow-sm" style="width: 42px; height: 42px; background-color: #ffffff; color: #003b6d; border: 1px solid #cbd5e1;">
                                <i class="ti ti-map-pin fs-2"></i>
                            </div>
                            <div class="min-w-0">
                                <div class="text-muted extra-small fw-medium" style="font-size: 0.75rem;">Location / Room</div>
                                <div class="fw-semibold text-dark small" id="modalEventLocation" style="line-height: 1.3;">Training Room Building A, 2nd Fl.</div>
                            </div>
                        </div>

                        <!-- 3. Max Participant -->
                        <div class="col-12 col-md-4 d-flex align-items-center gap-3">
                            <div class="rounded-circle d-flex align-items-center justify-content-center flex-shrink-0 shadow-sm" style="width: 42px; height: 42px; background-color: #ffffff; color: #003b6d; border: 1px solid #cbd5e1;">
                                <i class="ti ti-users fs-2"></i>
                            </div>
                            <div class="min-w-0">
                                <div class="text-muted extra-small fw-medium" style="font-size: 0.75rem;">Max Participant</div>
                                <div class="fw-semibold text-dark small" id="modalEventQuota" style="line-height: 1.3;">32 / 40 Participants</div>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- Description --}}
                <div class="mb-2">
                    <h5 class="fw-bold text-dark mb-2">Training Description</h5>
                    <p class="text-muted small lh-base mb-0" id="modalEventDescription">
                        This leadership development course is designed to enhance managerial capabilities, team communication, strategic decision-making, and effective delegation skills for current supervisors and emerging managers.
                    </p>
                </div>
            </div>

            <div class="modal-footer border-0 px-4 pb-4 pt-0 d-flex justify-content-end">
                <button type="button" class="btn btn-primary px-4 py-2 rounded-3 fw-semibold d-inline-flex align-items-center gap-2" id="modalBtnViewDetail" style="background-color: #003b6d; border-color: #003b6d;">
                    <span>View Detail</span>
                    <i class="ti ti-arrow-right fs-3"></i>
                </button>
            </div>
        </div>
    </div>
</div>
