{{-- Tab Calendar & Agenda (Dynamic Interactive JS Calendar) --}}
<div class="row g-4">
    {{-- Left Column: Monthly Calendar Grid (col-lg-8) --}}
    <div class="col-lg-8">
        <div class="card border border-light-subtle shadow-sm rounded-4 h-100 bg-white">
            <div class="card-body p-4">
                {{-- Calendar Header Bar --}}
                <div class="d-flex flex-wrap align-items-center justify-content-between gap-3 mb-4">
                    <div class="d-flex align-items-center gap-2">
                        <h3 class="fw-bold mb-0 text-dark" id="calendarMonthYear">September 2026</h3>
                        <div class="btn-group btn-group-sm ms-2" role="group">
                            <button type="button" class="btn btn-outline-secondary px-2 py-1" id="calPrevBtn" title="Previous Month">
                                <i class="ti ti-chevron-left"></i>
                            </button>
                            <button type="button" class="btn btn-outline-secondary px-2 py-1" id="calNextBtn" title="Next Month">
                                <i class="ti ti-chevron-right"></i>
                            </button>
                        </div>
                    </div>
                    <div>
                        <button type="button" class="btn btn-sm btn-outline-primary fw-semibold px-3 py-1-5 rounded-pill" id="calTodayBtn">
                            <i class="ti ti-calendar-event me-1"></i> Today
                        </button>
                    </div>
                </div>

                {{-- Calendar Grid Table --}}
                <div class="table-responsive">
                    <table class="table table-bordered border-light-subtle mb-0 text-center cal-grid-table" style="table-layout: fixed;">
                        <thead>
                            <tr class="bg-light-subtle text-muted text-uppercase fs-6">
                                <th class="py-2" style="width: 14.28%;">MON</th>
                                <th class="py-2" style="width: 14.28%;">TUE</th>
                                <th class="py-2" style="width: 14.28%;">WED</th>
                                <th class="py-2" style="width: 14.28%;">THU</th>
                                <th class="py-2" style="width: 14.28%;">FRI</th>
                                <th class="py-2 text-danger" style="width: 14.28%;">SAT</th>
                                <th class="py-2 text-danger" style="width: 14.28%;">SUN</th>
                            </tr>
                        </thead>
                        <tbody id="calGridBody">
                            {{-- Dynamically populated by JavaScript --}}
                        </tbody>
                    </table>
                </div>

                {{-- Legend --}}
                <div class="d-flex flex-wrap align-items-center gap-3 mt-3 pt-2 text-muted small">
                    <span class="d-inline-flex align-items-center gap-2">
                        <span class="badge bg-success-subtle text-success border border-success-subtle px-2 py-0-5 rounded-pill fw-semibold">Open: X</span>
                        <span>Open Registration</span>
                    </span>
                    <span class="d-inline-flex align-items-center gap-2">
                        <span class="badge bg-danger-subtle text-danger border border-danger-subtle px-2 py-0-5 rounded-pill fw-semibold">Close: Y</span>
                        <span>Registration Closed</span>
                    </span>
                </div>
            </div>
        </div>
    </div>

    {{-- Right Column: Today's Agenda Panel (col-lg-4) --}}
    <div class="col-lg-4">
        <div class="card border border-light-subtle shadow-sm rounded-4 h-100 bg-white">
            <div class="card-body p-4 d-flex flex-column">
                <div class="d-flex align-items-center justify-content-between mb-2">
                    <div class="d-flex align-items-center gap-2">
                        <div class="avatar avatar-sm rounded-3 d-flex align-items-center justify-content-center"
                             style="background-color: #eef2ff; color: #003b6d;">
                            <i class="ti ti-calendar-time fs-2"></i>
                        </div>
                        <h4 class="fw-bold mb-0 text-dark">Today's Agenda</h4>
                    </div>
                    <span class="badge bg-primary text-white rounded-pill px-2 py-1 fw-semibold small">
                        Active Date
                    </span>
                </div>
                
                <div class="text-primary fw-semibold small mb-3" id="todayAgendaDateLabel">
                    <i class="ti ti-clock me-1"></i> Friday, 18 September 2026
                </div>

                {{-- Timeline / Agenda List --}}
                <div class="timeline-schedule flex-grow-1" id="agendaTimelineList">
                    {{-- Item 1 --}}
                    <div class="timeline-schedule-item mb-3 p-3 rounded-3 border border-light-subtle bg-light-subtle position-relative"
                         role="button"
                         data-id="1"
                         data-title="Leadership Development Program"
                         data-category="Soft Skill"
                         data-date="18 September 2026, 08:00 - 16:00 WIB"
                         data-location="Training Room Building A, 2nd Fl."
                         data-instructor="Dr. Hendra Wijaya, M.M."
                         data-description="This leadership development course is designed to enhance managerial capabilities, team communication, strategic decision-making, and effective delegation skills for current supervisors and emerging managers.">
                        <div class="d-flex align-items-center justify-content-between mb-1">
                            <span class="badge bg-primary text-white rounded-pill px-2 py-0-5 small fw-semibold">08:00 - 16:00 WIB</span>
                            <span class="badge bg-primary-lt text-primary small">Soft Skill</span>
                        </div>
                        <h5 class="fw-bold text-dark mb-1">Leadership Development Program</h5>
                        <div class="text-muted small d-flex align-items-center gap-1 mb-1">
                            <i class="ti ti-map-pin text-primary"></i>
                            <span>Training Room Building A, 2nd Fl.</span>
                        </div>
                        <div class="text-muted small d-flex align-items-center gap-1">
                            <i class="ti ti-user-check text-primary"></i>
                            <span>Dr. Hendra Wijaya, M.M.</span>
                        </div>
                    </div>

                    {{-- Item 2 --}}
                    <div class="timeline-schedule-item mb-3 p-3 rounded-3 border border-light-subtle bg-light-subtle position-relative"
                         role="button"
                         data-id="2"
                         data-title="Excel Advanced for Data Analysis"
                         data-category="Technical Skill"
                         data-date="18 September 2026, 09:00 - 15:00 WIB"
                         data-location="Computer Lab 2, 3rd Fl."
                         data-instructor="Bambang Sujarwo, S.Kom."
                         data-description="Advanced formulas, XLOOKUP/INDEX-MATCH, dynamic array functions, PivotTables, Power Query integration, and automated dashboard visualization using Microsoft Excel.">
                        <div class="d-flex align-items-center justify-content-between mb-1">
                            <span class="badge bg-info text-white rounded-pill px-2 py-0-5 small fw-semibold">09:00 - 15:00 WIB</span>
                            <span class="badge bg-info-lt text-info small">Technical</span>
                        </div>
                        <h5 class="fw-bold text-dark mb-1">Excel Advanced for Data Analysis</h5>
                        <div class="text-muted small d-flex align-items-center gap-1 mb-1">
                            <i class="ti ti-map-pin text-info"></i>
                            <span>Computer Lab 2, 3rd Fl.</span>
                        </div>
                        <div class="text-muted small d-flex align-items-center gap-1">
                            <i class="ti ti-user-check text-info"></i>
                            <span>Bambang Sujarwo, S.Kom.</span>
                        </div>
                    </div>
                </div>

                <div class="mt-auto pt-2 border-top border-light-subtle">
                    <button type="button" class="btn btn-outline-primary w-100 rounded-3 py-2 fw-semibold d-inline-flex align-items-center justify-content-center gap-2 btn-switch-to-available">
                        <span>Explore All Available Training</span>
                        <i class="ti ti-arrow-right"></i>
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>
