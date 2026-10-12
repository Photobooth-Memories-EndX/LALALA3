{{-- Shared Offline Training Record Detail Modal (Used by Offline & All Record pages) --}}
<div class="modal fade" id="recordOfflineDetailModal" tabindex="-1" aria-labelledby="recordOfflineDetailModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg modal-dialog-scrollable">
        <div class="modal-content rounded-3 border-0 shadow">
            <div class="modal-header border-bottom py-3 bg-light">
                <div class="d-flex align-items-center gap-2">
                    <i class="ti ti-certificate text-primary fs-1"></i>
                    <div>
                        <h5 class="modal-title fw-bold mb-0" id="recordOfflineDetailModalLabel">Training Record Detail</h5>
                        <div class="text-muted small" id="detailCourseSubHeader">-</div>
                    </div>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body p-3 p-md-4">
                {{-- 1. TRAINING INFORMATION --}}
                <div class="card border mb-3 rounded-3 shadow-none">
                    <div class="card-header bg-light-subtle py-2 px-3 border-bottom d-flex align-items-center gap-2">
                        <i class="ti ti-school text-primary fs-3"></i>
                        <h6 class="card-title mb-0 fw-bold fs-4 text-dark">Training Information</h6>
                    </div>
                    <div class="card-body p-3">
                        <div class="row g-2">
                            <div class="col-md-8 col-12">
                                <span class="text-muted small d-block">Course Name</span>
                                <span class="fw-semibold text-dark fs-4" id="dtCourseName">-</span>
                            </div>
                            <div class="col-md-4 col-12">
                                <span class="text-muted small d-block">Sub Course Name</span>
                                <span class="fw-medium text-dark" id="dtSubCourseName">-</span>
                            </div>
                            <div class="col-md-3 col-6">
                                <span class="text-muted small d-block">Type</span>
                                <span class="badge bg-secondary-lt" id="dtType">-</span>
                            </div>
                            <div class="col-md-3 col-6">
                                <span class="text-muted small d-block">Category</span>
                                <span class="badge bg-light text-muted border" id="dtCategory">-</span>
                            </div>
                            <div class="col-md-3 col-6">
                                <span class="text-muted small d-block">Level</span>
                                <span class="fw-medium text-dark" id="dtLevel">-</span>
                            </div>
                            <div class="col-md-3 col-6">
                                <span class="text-muted small d-block">Batch</span>
                                <span class="fw-medium text-dark" id="dtBatch">-</span>
                            </div>
                            <div class="col-md-6 col-12">
                                <span class="text-muted small d-block">Main Classification</span>
                                <span class="badge bg-blue-lt" id="dtClassification">-</span>
                            </div>
                            <div class="col-md-6 col-12">
                                <span class="text-muted small d-block">Sub Classification</span>
                                <span class="fw-medium text-dark" id="dtSubClassification">-</span>
                            </div>
                            <div class="col-md-6 col-12">
                                <span class="text-muted small d-block">Delivery Method</span>
                                <span class="fw-medium text-dark" id="dtDeliveryMethod">-</span>
                            </div>
                            <div class="col-md-6 col-12">
                                <span class="text-muted small d-block">Sub Delivery Method</span>
                                <span class="fw-medium text-dark" id="dtSubDeliveryMethod">-</span>
                            </div>
                            <div class="col-md-6 col-12">
                                <span class="text-muted small d-block">Provider</span>
                                <span class="fw-medium text-dark" id="dtProvider">-</span>
                            </div>
                            <div class="col-md-6 col-12">
                                <span class="text-muted small d-block">Provider Name</span>
                                <span class="fw-medium text-dark" id="dtProviderName">-</span>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- 2. SCHEDULE & VENUE --}}
                <div class="card border mb-3 rounded-3 shadow-none">
                    <div class="card-header bg-light-subtle py-2 px-3 border-bottom d-flex align-items-center gap-2">
                        <i class="ti ti-calendar-time text-primary fs-3"></i>
                        <h6 class="card-title mb-0 fw-bold fs-4 text-dark">Schedule & Venue</h6>
                    </div>
                    <div class="card-body p-3">
                        <div class="row g-2">
                            <div class="col-md-6 col-12">
                                <span class="text-muted small d-block">Date</span>
                                <span class="fw-medium text-dark" id="dtDateDisplay">-</span>
                            </div>
                            <div class="col-md-6 col-12">
                                <span class="text-muted small d-block">Training Hours / Time</span>
                                <span class="fw-medium text-dark" id="dtTimeDisplay">-</span>
                            </div>
                            <div class="col-md-6 col-12">
                                <span class="text-muted small d-block">Duration</span>
                                <span class="fw-medium text-dark" id="dtDuration">-</span>
                            </div>
                            <div class="col-md-6 col-12">
                                <span class="text-muted small d-block">Credited Learning Hours</span>
                                <span class="fw-bold text-primary" id="dtLearningHours">-</span>
                            </div>
                            <div class="col-md-6 col-12">
                                <span class="text-muted small d-block">Venue / Location</span>
                                <span class="fw-medium text-dark" id="dtVenue">-</span>
                            </div>
                            <div class="col-md-6 col-12">
                                <span class="text-muted small d-block">Instructor / Facilitator</span>
                                <span class="fw-medium text-dark" id="dtInstructor">-</span>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- 3. EMPLOYEE SNAPSHOT (AT RECORD TIME) --}}
                <div class="card border mb-3 rounded-3 shadow-none">
                    <div class="card-header bg-light-subtle py-2 px-3 border-bottom d-flex align-items-center justify-content-between">
                        <div class="d-flex align-items-center gap-2">
                            <i class="ti ti-user-check text-secondary fs-3"></i>
                            <h6 class="card-title mb-0 fw-bold fs-4 text-dark">Employee Snapshot (At Record Time)</h6>
                        </div>
                        <span class="text-muted small" style="font-size: 0.72rem;">Historical Profile</span>
                    </div>
                    <div class="card-body p-3">
                        <div class="row g-2">
                            <div class="col-md-4 col-6">
                                <span class="text-muted small d-block">Employee ID</span>
                                <span class="fw-medium text-dark" id="dtEmployeeId">-</span>
                            </div>
                            <div class="col-md-4 col-6">
                                <span class="text-muted small d-block">Participant Name</span>
                                <span class="fw-medium text-dark" id="dtParticipantName">-</span>
                            </div>
                            <div class="col-md-4 col-6">
                                <span class="text-muted small d-block">Grade</span>
                                <span class="fw-medium text-dark" id="dtGrade">-</span>
                            </div>
                            <div class="col-md-4 col-6">
                                <span class="text-muted small d-block">Job Position</span>
                                <span class="fw-medium text-dark" id="dtJobPosition">-</span>
                            </div>
                            <div class="col-md-4 col-6">
                                <span class="text-muted small d-block">Team</span>
                                <span class="fw-medium text-dark" id="dtTeam">-</span>
                            </div>
                            <div class="col-md-4 col-6">
                                <span class="text-muted small d-block">Department</span>
                                <span class="fw-medium text-dark" id="dtDepartment">-</span>
                            </div>
                            <div class="col-12">
                                <span class="text-muted small d-block">Company</span>
                                <span class="fw-medium text-dark" id="dtCompany">-</span>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- 4. SERVICE BOND (OBLIGATION) --}}
                <div class="card border mb-0 rounded-3 shadow-none">
                    <div class="card-header bg-light-subtle py-2 px-3 border-bottom d-flex align-items-center gap-2">
                        <i class="ti ti-file-certificate text-warning fs-3"></i>
                        <h6 class="card-title mb-0 fw-bold fs-4 text-dark">Service Bond & Obligation</h6>
                    </div>
                    <div class="card-body p-3">
                        <div class="row g-2 align-items-center">
                            <div class="col-md-4 col-12">
                                <span class="text-muted small d-block">Bond Status</span>
                                <span class="fw-medium text-dark" id="dtBondStatus">-</span>
                            </div>
                            <div class="col-md-4 col-12">
                                <span class="text-muted small d-block">Bond End Date</span>
                                <span class="fw-medium text-dark" id="dtBondEndDate">-</span>
                            </div>
                            <div class="col-md-4 col-12">
                                <span class="text-muted small d-block">Bond Amount</span>
                                <span class="fw-bold text-dark" id="dtBondAmount">-</span>
                            </div>
                            <div class="col-12 mt-2" id="dtBondNotice" style="display: none;">
                                <div class="alert alert-info py-2 px-3 mb-0 small d-flex align-items-center gap-2">
                                    <i class="ti ti-info-circle fs-3"></i>
                                    <span>This training entails an active corporate service bond agreement.</span>
                                </div>
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
