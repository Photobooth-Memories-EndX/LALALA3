{{-- Tab Riwayat Saya / My History --}}
<div class="card border border-light-subtle shadow-sm rounded-4 bg-white p-4">
    <div class="d-flex flex-wrap align-items-center justify-content-between gap-3 mb-4">
        <div>
            <h3 class="fw-bold mb-1 text-dark">Training Registration & History</h3>
            <p class="text-muted small mb-0">List of your enrolled training sessions, approval statuses, and completion history.</p>
        </div>
        <div class="d-flex align-items-center gap-2">
            <span class="badge bg-primary-lt text-primary px-3 py-2 rounded-pill fw-semibold">
                Total: 4 Trainings
            </span>
        </div>
    </div>

    <div class="table-responsive">
        <table class="table table-hover table-vcenter align-middle mb-0">
            <thead>
                <tr class="bg-light-subtle text-muted text-uppercase fs-6">
                    <th class="text-center py-3" style="width: 50px;">NO</th>
                    <th class="py-3">TRAINING NAME</th>
                    <th class="py-3">CATEGORY</th>
                    <th class="py-3">SCHEDULE DATE</th>
                    <th class="py-3">LOCATION</th>
                    <th class="text-center py-3">STATUS</th>
                    <th class="text-center py-3" style="width: 120px;">ACTION</th>
                </tr>
            </thead>
            <tbody>
                {{-- Row 1: Approved --}}
                <tr>
                    <td class="text-center text-muted fw-semibold">1</td>
                    <td>
                        <div class="d-flex align-items-center gap-3">
                            <div class="avatar avatar-sm rounded-3 bg-primary-lt text-primary flex-shrink-0">
                                <i class="ti ti-school fs-2"></i>
                            </div>
                            <div>
                                <div class="fw-bold text-dark">Leadership Development Program</div>
                                <div class="text-muted small">Instructor: Dr. Hendra Wijaya, M.M.</div>
                            </div>
                        </div>
                    </td>
                    <td>
                        <span class="badge bg-primary-lt text-primary rounded-pill px-2 py-1">Soft Skill</span>
                    </td>
                    <td>
                        <div class="fw-semibold text-dark">18 Sep 2026</div>
                        <div class="text-muted small">08:00 - 16:00 WIB</div>
                    </td>
                    <td class="text-muted">
                        <i class="ti ti-map-pin text-primary me-1"></i> Training Room Building A, 2nd Fl.
                    </td>
                    <td class="text-center">
                        <span class="badge bg-success-lt text-success rounded-pill px-3 py-1-5 fw-semibold">
                            <i class="ti ti-check me-1"></i> Approved
                        </span>
                    </td>
                    <td class="text-center">
                        <button type="button" class="btn btn-sm btn-outline-secondary rounded-pill px-3"
                                data-bs-toggle="modal" 
                                data-bs-target="#eventDetailModal"
                                data-id="1"
                                data-title="Leadership Development Program"
                                data-category="Soft Skill"
                                data-date="18 September 2026, 08:00 - 16:00 WIB"
                                data-location="Training Room Building A, 2nd Fl."
                                data-instructor="Dr. Hendra Wijaya, M.M."
                                data-description="This leadership development course is designed to enhance managerial capabilities, team communication, strategic decision-making, and effective delegation skills for current supervisors and emerging managers.">
                            Detail
                        </button>
                    </td>
                </tr>

                {{-- Row 2: Pending --}}
                <tr>
                    <td class="text-center text-muted fw-semibold">2</td>
                    <td>
                        <div class="d-flex align-items-center gap-3">
                            <div class="avatar avatar-sm rounded-3 bg-info-lt text-info flex-shrink-0">
                                <i class="ti ti-file-analytics fs-2"></i>
                            </div>
                            <div>
                                <div class="fw-bold text-dark">Excel Advanced for Data Analysis</div>
                                <div class="text-muted small">Instructor: Bambang Sujarwo, S.Kom.</div>
                            </div>
                        </div>
                    </td>
                    <td>
                        <span class="badge bg-info-lt text-info rounded-pill px-2 py-1">Technical Skill</span>
                    </td>
                    <td>
                        <div class="fw-semibold text-dark">18 Sep 2026</div>
                        <div class="text-muted small">09:00 - 15:00 WIB</div>
                    </td>
                    <td class="text-muted">
                        <i class="ti ti-map-pin text-info me-1"></i> Computer Lab 2, 3rd Fl.
                    </td>
                    <td class="text-center">
                        <span class="badge bg-warning-lt text-warning rounded-pill px-3 py-1-5 fw-semibold">
                            <i class="ti ti-clock me-1"></i> Pending
                        </span>
                    </td>
                    <td class="text-center">
                        <button type="button" class="btn btn-sm btn-outline-secondary rounded-pill px-3"
                                data-bs-toggle="modal" 
                                data-bs-target="#eventDetailModal"
                                data-id="2"
                                data-title="Excel Advanced for Data Analysis"
                                data-category="Technical Skill"
                                data-date="18 September 2026, 09:00 - 15:00 WIB"
                                data-location="Computer Lab 2, 3rd Fl."
                                data-instructor="Bambang Sujarwo, S.Kom."
                                data-description="Advanced formulas, XLOOKUP/INDEX-MATCH, dynamic array functions, PivotTables, Power Query integration, and automated dashboard visualization using Microsoft Excel.">
                            Detail
                        </button>
                    </td>
                </tr>

                {{-- Row 3: Completed --}}
                <tr>
                    <td class="text-center text-muted fw-semibold">3</td>
                    <td>
                        <div class="d-flex align-items-center gap-3">
                            <div class="avatar avatar-sm rounded-3 bg-primary-lt text-primary flex-shrink-0">
                                <i class="ti ti-shield-check fs-2"></i>
                            </div>
                            <div>
                                <div class="fw-bold text-dark">Basic Fire Fighting & Safety Protocol</div>
                                <div class="text-muted small">Instructor: HSE Team Krakatau Posco</div>
                            </div>
                        </div>
                    </td>
                    <td>
                        <span class="badge bg-warning-lt text-warning rounded-pill px-2 py-1">Compliance & Safety</span>
                    </td>
                    <td>
                        <div class="fw-semibold text-dark">20 Jul 2026</div>
                        <div class="text-muted small">08:00 - 12:00 WIB</div>
                    </td>
                    <td class="text-muted">
                        <i class="ti ti-map-pin text-warning me-1"></i> HSE Training Ground
                    </td>
                    <td class="text-center">
                        <span class="badge rounded-pill px-3 py-1-5 fw-semibold" style="background-color: rgba(2, 132, 199, 0.12) !important; color: #0284c7 !important;">
                            <i class="ti ti-circle-check me-1"></i> Completed
                        </span>
                    </td>
                    <td class="text-center">
                        <button type="button" class="btn btn-sm btn-outline-secondary rounded-pill px-3"
                                data-bs-toggle="modal" 
                                data-bs-target="#eventDetailModal"
                                data-id="5"
                                data-title="Basic Fire Fighting & Safety Protocol"
                                data-category="Compliance & Safety"
                                data-date="20 July 2026, 08:00 - 12:00 WIB"
                                data-location="HSE Training Ground"
                                data-instructor="HSE Team Krakatau Posco"
                                data-description="Basic industrial fire emergency response, standard fire extinguisher usage, and factory evacuation procedures.">
                            Detail
                        </button>
                    </td>
                </tr>

                {{-- Row 4: Rejected --}}
                <tr>
                    <td class="text-center text-muted fw-semibold">4</td>
                    <td>
                        <div class="d-flex align-items-center gap-3">
                            <div class="avatar avatar-sm rounded-3 bg-danger-lt text-danger flex-shrink-0">
                                <i class="ti ti-cpu fs-2"></i>
                            </div>
                            <div>
                                <div class="fw-bold text-dark">PLC Siemens S7-1500 Advanced</div>
                                <div class="text-muted small">Instructor: Ahmad Fauzi, S.T.</div>
                            </div>
                        </div>
                    </td>
                    <td>
                        <span class="badge bg-info-lt text-info rounded-pill px-2 py-1">Technical Skill</span>
                    </td>
                    <td>
                        <div class="fw-semibold text-dark">12 Jun 2026</div>
                        <div class="text-muted small">09:00 - 16:00 WIB</div>
                    </td>
                    <td class="text-muted">
                        <i class="ti ti-map-pin text-danger me-1"></i> Automation Workshop
                    </td>
                    <td class="text-center">
                        <span class="badge bg-danger-lt text-danger rounded-pill px-3 py-1-5 fw-semibold">
                            <i class="ti ti-x me-1"></i> Rejected
                        </span>
                    </td>
                    <td class="text-center">
                        <button type="button" class="btn btn-sm btn-outline-secondary rounded-pill px-3"
                                data-bs-toggle="modal" 
                                data-bs-target="#eventDetailModal"
                                data-id="6"
                                data-title="PLC Siemens S7-1500 Advanced"
                                data-category="Technical Skill"
                                data-date="12 June 2026, 09:00 - 16:00 WIB"
                                data-location="Automation Workshop"
                                data-instructor="Ahmad Fauzi, S.T."
                                data-description="Advanced industrial automation ladder and structured text programming for Siemens S7-1500, PROFINET network diagnostic, and SCADA integration.">
                            Detail
                        </button>
                    </td>
                </tr>
            </tbody>
        </table>
    </div>
</div>
