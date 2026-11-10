{{-- Shared Attendance Detail Modal (Used by Offline & All Record pages) --}}
<div class="modal fade" id="attendanceDetailModal" tabindex="-1" aria-labelledby="attendanceDetailModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-md">
        <div class="modal-content rounded-3 border-0 shadow">
            <div class="modal-header border-bottom py-3 bg-light">
                <div class="d-flex align-items-center gap-2">
                    <i class="ti ti-calendar-check text-primary fs-2"></i>
                    <h5 class="modal-title fw-bold mb-0 text-dark" id="attendanceDetailModalLabel">Attendance Detail</h5>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body p-3 p-md-4">
                <div class="d-flex flex-column gap-3">
                    {{-- Training Title --}}
                    <div>
                        <span class="text-muted small fw-semibold text-uppercase d-block mb-1" style="font-size: 0.72rem; letter-spacing: 0.5px;">Training Title</span>
                        <div class="fw-bold text-dark fs-3 text-break" id="attendanceTrainingTitle" style="word-break: break-word; overflow-wrap: anywhere; line-height: 1.35;">-</div>
                    </div>

                    {{-- Attendance Status --}}
                    <div>
                        <span class="text-muted small fw-semibold text-uppercase d-block mb-1" style="font-size: 0.72rem; letter-spacing: 0.5px;">Attendance Status</span>
                        <div id="attendanceStatusContainer">
                            <span class="badge bg-secondary-lt fw-bold px-2 py-1" id="attendanceStatus">-</span>
                        </div>
                    </div>

                    {{-- Attendance Reason --}}
                    <div>
                        <span class="text-muted small fw-semibold text-uppercase d-block mb-1" style="font-size: 0.72rem; letter-spacing: 0.5px;">Attendance Reason</span>
                        <div class="p-2.5 rounded-2 bg-light border text-break" style="word-break: break-word; overflow-wrap: anywhere; line-height: 1.45;">
                            <span class="text-dark fw-normal" id="attendanceReason">-</span>
                        </div>
                    </div>

                    <div class="row g-3 pt-1">
                        {{-- Training Date --}}
                        <div class="col-sm-6 col-12">
                            <span class="text-muted small fw-semibold text-uppercase d-block mb-1" style="font-size: 0.72rem; letter-spacing: 0.5px;">Training Date</span>
                            <div class="d-flex align-items-center gap-1.5 text-dark fw-medium text-break" style="word-break: break-word;">
                                <i class="ti ti-calendar text-muted"></i>
                                <span id="attendanceDate">-</span>
                            </div>
                        </div>

                        {{-- Location / Venue --}}
                        <div class="col-sm-6 col-12">
                            <span class="text-muted small fw-semibold text-uppercase d-block mb-1" style="font-size: 0.72rem; letter-spacing: 0.5px;">Location / Venue</span>
                            <div class="d-flex align-items-center gap-1.5 text-dark fw-medium text-break" style="word-break: break-word; overflow-wrap: anywhere;">
                                <i class="ti ti-map-pin text-muted"></i>
                                <span id="attendanceVenue">-</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="modal-footer border-top py-2 px-3">
                <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>
