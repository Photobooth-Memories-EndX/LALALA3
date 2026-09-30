<!-- More Filters Modal for Manage Training Schedule -->
<div class="modal fade" id="moreScheduleFiltersModal" tabindex="-1" aria-labelledby="moreScheduleFiltersTitle" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content rounded-4 border-0 shadow">
            <!-- Modal Header -->
            <div class="modal-header border-0 pb-1">
                <div>
                    <h3 class="modal-title fw-bold d-flex align-items-center gap-2 mb-1" id="moreScheduleFiltersTitle" style="font-size: 1.15rem; color: #1e293b;">
                        <i class="ti ti-adjustments-horizontal text-primary fs-2"></i>
                        <span>All Filters</span>
                    </h3>
                    <p class="text-muted extra-small mb-0">
                        All filters can be combined with the search &amp; the filter chip above.
                    </p>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>

            <!-- Modal Body -->
            <div class="modal-body py-3">
                <form id="moreScheduleFiltersForm" onsubmit="return false;">
                    <div class="row g-3">
                        <!-- Category Filter -->
                        <div class="col-12 col-md-6">
                            <label class="form-label fw-bold text-dark small" for="modalFilterCategory">Training Category</label>
                            <select id="modalFilterCategory" class="form-select rounded-3">
                                <option value="">All Category</option>
                                <option value="Soft Skill">Soft Skill</option>
                                <option value="Technical Skill">Technical Skill</option>
                                <option value="Compliance & Safety">Compliance & Safety</option>
                                <option value="Leadership">Leadership</option>
                            </select>
                        </div>

                        <!-- Registration Status Filter -->
                        <div class="col-12 col-md-6">
                            <label class="form-label fw-bold text-dark small" for="modalFilterStatus">Registration Status</label>
                            <select id="modalFilterStatus" class="form-select rounded-3">
                                <option value="">All Status</option>
                                <option value="open">Open Registration</option>
                                <option value="running">Running</option>
                                <option value="closed">Closed Registration</option>
                            </select>
                        </div>

                        <!-- Instructor Filter -->
                        <div class="col-12 col-md-6">
                            <label class="form-label fw-bold text-dark small" for="modalFilterInstructor">Instructor</label>
                            <select id="modalFilterInstructor" class="form-select rounded-3">
                                <option value="">All Instructors</option>
                                <option value="Dr. Hendra Wijaya, M.M.">Dr. Hendra Wijaya, M.M.</option>
                                <option value="Bambang Sujarwo, S.Kom.">Bambang Sujarwo, S.Kom.</option>
                                <option value="Ir. Agus Priyono, CSP">Ir. Agus Priyono, CSP</option>
                                <option value="Citra Lestari, M.Psi.">Citra Lestari, M.Psi.</option>
                                <option value="HSE Team Krakatau Posco">HSE Team Krakatau Posco</option>
                            </select>
                        </div>

                        <!-- Location / Room Filter -->
                        <div class="col-12 col-md-6">
                            <label class="form-label fw-bold text-dark small" for="modalFilterLocation">Location / Room</label>
                            <select id="modalFilterLocation" class="form-select rounded-3">
                                <option value="">All Locations</option>
                                <option value="Training Room Building A, 2nd Fl.">Training Room Building A, 2nd Fl.</option>
                                <option value="Computer Lab 2, 3rd Fl.">Computer Lab 2, 3rd Fl.</option>
                                <option value="Auditorium Krakatau Posco">Auditorium Krakatau Posco</option>
                                <option value="Meeting Room Bintang 1">Meeting Room Bintang 1</option>
                                <option value="HSE Training Ground">HSE Training Ground</option>
                            </select>
                        </div>

                        <!-- Date Range: From Date & To Date -->
                        <div class="col-12">
                            <label class="form-label fw-bold text-dark small mb-1">
                                <i class="ti ti-calendar me-1 text-primary"></i> Schedule Date Range
                            </label>
                            <div class="row g-2">
                                <div class="col-12 col-sm-6">
                                    <label class="form-label small text-muted mb-1" for="modalFilterFromDate">From Date</label>
                                    <input type="date" id="modalFilterFromDate" class="form-control rounded-3">
                                </div>
                                <div class="col-12 col-sm-6">
                                    <label class="form-label small text-muted mb-1" for="modalFilterToDate">To Date</label>
                                    <input type="date" id="modalFilterToDate" class="form-control rounded-3">
                                </div>
                            </div>
                        </div>
                    </div>
                </form>
            </div>

            <!-- Modal Footer (Right-aligned Reset & Apply Filter) -->
            <div class="modal-footer border-top pt-3 d-flex justify-content-end align-items-center gap-2">
                <button type="button" id="btnResetMoreScheduleFilters" class="btn btn-outline-secondary px-3 rounded-3 d-inline-flex align-items-center gap-1">
                    <i class="ti ti-refresh fs-2"></i> Reset
                </button>
                <button type="button" id="btnApplyMoreScheduleFilters" class="btn btn-primary px-4 rounded-3 d-inline-flex align-items-center gap-1" 
                        style="background-color: #003b6d; border-color: #003b6d;" data-bs-dismiss="modal">
                    <i class="ti ti-search fs-2"></i> Apply Filter
                </button>
            </div>
        </div>
    </div>
</div>
