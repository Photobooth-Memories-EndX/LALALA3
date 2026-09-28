{{-- Modal: Add Training Schedule --}}
<div class="modal fade" id="addTrainingModal" tabindex="-1" aria-labelledby="addTrainingModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg modal-dialog-scrollable">
        <div class="modal-content border-0 shadow rounded-4 overflow-hidden">
            <!-- Modal Header -->
            <div class="modal-header border-bottom border-light-subtle pb-3 px-4 pt-4 bg-white">
                <div class="d-flex align-items-center gap-3">
                    <div class="rounded-3 d-flex align-items-center justify-content-center flex-shrink-0" 
                         style="width: 48px; height: 48px; background-color: #eef2ff; color: #003b6d;">
                        <i class="ti ti-calendar-plus fs-1"></i>
                    </div>
                    <div>
                        <h4 class="h4 fw-bold mb-0 text-dark" id="addTrainingModalLabel">Add Training Schedule</h4>
                        <div class="text-muted small">Input new PTKP training session details, capacity, and schedule.</div>
                    </div>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>

            <!-- Modal Form Body -->
            <form id="addTrainingForm" novalidate>
                <div class="modal-body px-4 py-3">
                    <div class="row g-3">
                        <!-- Training Title -->
                        <div class="col-12">
                            <label class="form-label fw-semibold text-dark required">Training Title</label>
                            <div class="input-icon">
                                <span class="input-icon-addon">
                                    <i class="ti ti-school text-muted"></i>
                                </span>
                                <input type="text" class="form-control rounded-3" id="inputTrainingTitle" 
                                       placeholder="e.g. Leadership Development Program Batch 2" required>
                            </div>
                            <div class="invalid-feedback">Please enter a training title.</div>
                        </div>

                        <!-- Category -->
                        <div class="col-md-6 col-12">
                            <label class="form-label fw-semibold text-dark required">Category</label>
                            <select class="form-select rounded-3" id="inputTrainingCategory" required>
                                <option value="" selected disabled>Select training category...</option>
                                <option value="Soft Skill">Soft Skill</option>
                                <option value="Technical Skill">Technical Skill</option>
                                <option value="Compliance & Safety">Compliance & Safety</option>
                                <option value="Leadership">Leadership</option>
                            </select>
                            <div class="invalid-feedback">Please select a category.</div>
                        </div>

                        <!-- Instructor Name -->
                        <div class="col-md-6 col-12">
                            <label class="form-label fw-semibold text-dark required">Instructor Name</label>
                            <div class="input-icon">
                                <span class="input-icon-addon">
                                    <i class="ti ti-user-check text-muted"></i>
                                </span>
                                <input type="text" class="form-control rounded-3" id="inputTrainingInstructor" 
                                       placeholder="e.g. Dr. Hendra Wijaya, M.M." required>
                            </div>
                            <div class="invalid-feedback">Please enter instructor name.</div>
                        </div>

                        <!-- Location / Room -->
                        <div class="col-md-6 col-12">
                            <label class="form-label fw-semibold text-dark required">Location / Room</label>
                            <div class="input-icon">
                                <span class="input-icon-addon">
                                    <i class="ti ti-map-pin text-muted"></i>
                                </span>
                                <input type="text" class="form-control rounded-3" id="inputTrainingLocation" 
                                       placeholder="e.g. Training Room Building A, 2nd Fl." required>
                            </div>
                            <div class="invalid-feedback">Please enter training location or room.</div>
                        </div>

                        <!-- Max Participant Quota -->
                        <div class="col-md-3 col-6">
                            <label class="form-label fw-semibold text-dark required">Max Quota (Seats)</label>
                            <div class="input-icon">
                                <span class="input-icon-addon">
                                    <i class="ti ti-users text-muted"></i>
                                </span>
                                <input type="number" class="form-control rounded-3" id="inputTrainingQuota" 
                                       placeholder="30" min="1" max="500" required>
                            </div>
                            <div class="invalid-feedback">Please specify quota.</div>
                        </div>

                        <!-- Registration Status -->
                        <div class="col-md-3 col-6">
                            <label class="form-label fw-semibold text-dark required">Registration Status</label>
                            <select class="form-select rounded-3" id="inputTrainingStatus" required>
                                <option value="open" selected>Open Registration</option>
                                <option value="closed">Closed Registration</option>
                                <option value="draft">Draft</option>
                            </select>
                            <div class="invalid-feedback">Please select registration status.</div>
                        </div>

                        <!-- Start Date -->
                        <div class="col-md-6 col-12">
                            <label class="form-label fw-semibold text-dark required">Start Date</label>
                            <div class="input-icon">
                                <span class="input-icon-addon">
                                    <i class="ti ti-calendar text-muted"></i>
                                </span>
                                <input type="date" class="form-control rounded-3" id="inputTrainingStartDate" required>
                            </div>
                            <div class="invalid-feedback">Please choose start date.</div>
                        </div>

                        <!-- End Date -->
                        <div class="col-md-6 col-12">
                            <label class="form-label fw-semibold text-dark required">End Date</label>
                            <div class="input-icon">
                                <span class="input-icon-addon">
                                    <i class="ti ti-calendar text-muted"></i>
                                </span>
                                <input type="date" class="form-control rounded-3" id="inputTrainingEndDate" required>
                            </div>
                            <div class="invalid-feedback">Please choose end date.</div>
                        </div>

                        <!-- Start Time -->
                        <div class="col-md-6 col-6">
                            <label class="form-label fw-semibold text-dark required">Start Time</label>
                            <div class="input-icon">
                                <span class="input-icon-addon">
                                    <i class="ti ti-clock text-muted"></i>
                                </span>
                                <input type="time" class="form-control rounded-3" id="inputTrainingStartTime" value="08:00" required>
                            </div>
                            <div class="invalid-feedback">Please enter start time.</div>
                        </div>

                        <!-- End Time -->
                        <div class="col-md-6 col-6">
                            <label class="form-label fw-semibold text-dark required">End Time</label>
                            <div class="input-icon">
                                <span class="input-icon-addon">
                                    <i class="ti ti-clock text-muted"></i>
                                </span>
                                <input type="time" class="form-control rounded-3" id="inputTrainingEndTime" value="16:00" required>
                            </div>
                            <div class="invalid-feedback">Please enter end time.</div>
                        </div>

                        <!-- Description / Material Outline -->
                        <div class="col-12">
                            <label class="form-label fw-semibold text-dark">Description / Material Outline</label>
                            <textarea class="form-control rounded-3" id="inputTrainingDescription" rows="3" 
                                      placeholder="Provide an overview of learning goals, target audience, prerequisites, and syllabus outline..."></textarea>
                        </div>
                    </div>
                </div>

                <!-- Modal Footer -->
                <div class="modal-footer border-top border-light-subtle px-4 py-3 bg-light-subtle d-flex justify-content-between align-items-center">
                    <button type="button" class="btn btn-outline-secondary px-3 py-2 rounded-3 fw-semibold" data-bs-dismiss="modal">
                        Cancel
                    </button>
                    <button type="submit" class="btn btn-primary px-4 py-2 rounded-3 fw-semibold d-inline-flex align-items-center gap-2"
                            id="btnSaveTrainingSchedule" style="background-color: #003b6d; border-color: #003b6d;">
                        <i class="ti ti-check fs-2"></i>
                        <span>Save Training Schedule</span>
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
