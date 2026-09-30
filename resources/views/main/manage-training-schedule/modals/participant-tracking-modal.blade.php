<!-- Dual-View Participant Tracking Modal -->
<div class="modal fade" id="participantTrackingModal" tabindex="-1" aria-labelledby="participantTrackingModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-xl modal-dialog-scrollable">
        <div class="modal-content border-0 shadow rounded-4 overflow-hidden">
            <!-- Modal Header -->
            <div class="modal-header border-bottom border-light-subtle pb-3 px-4 pt-4 bg-white">
                <div class="d-flex align-items-center gap-3">
                    <div class="rounded-3 d-flex align-items-center justify-content-center flex-shrink-0" 
                         style="width: 48px; height: 48px; background-color: #e0f2fe; color: #0284c7;">
                        <i class="ti ti-users fs-1"></i>
                    </div>
                    <div>
                        <h4 class="h4 fw-bold mb-0 text-dark" id="participantTrackingModalLabel">Participant Tracking & Approvals</h4>
                        <div class="text-muted small">Manage training applications, review participant quotas, and inspect employee training history.</div>
                    </div>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>

            <!-- Modal Nav Tabs: Dual-View -->
            <div class="px-4 pt-3 pb-0 bg-white border-bottom border-light-subtle">
                <ul class="nav nav-pills gap-2 pb-3" id="trackingViewTabs" role="tablist">
                    <li class="nav-item" role="presentation">
                        <button class="nav-link active fw-semibold rounded-pill px-3 py-1-5 d-inline-flex align-items-center gap-2" 
                                id="tab-tracking-training-btn" data-bs-toggle="pill" data-bs-target="#tab-tracking-training" type="button" role="tab" aria-selected="true">
                            <i class="ti ti-school fs-2"></i>
                            <span>By Training Title</span>
                        </button>
                    </li>
                    <li class="nav-item" role="presentation">
                        <button class="nav-link fw-semibold rounded-pill px-3 py-1-5 d-inline-flex align-items-center gap-2" 
                                id="tab-tracking-employee-btn" data-bs-toggle="pill" data-bs-target="#tab-tracking-employee" type="button" role="tab" aria-selected="false">
                            <i class="ti ti-user fs-2"></i>
                            <span>By User / Employee</span>
                        </button>
                    </li>
                </ul>
            </div>

            <!-- Modal Body Container -->
            <div class="modal-body px-4 py-3 bg-light-subtle" style="max-height: 70vh;">
                <div class="tab-content" id="trackingViewTabContent">
                    
                    {{-- TAB 1: BY TRAINING TITLE --}}
                    <div class="tab-pane fade show active" id="tab-tracking-training" role="tabpanel">
                        <!-- Training Selection & Quick Metrics Bar -->
                        <div class="card border border-light-subtle rounded-3 bg-white p-3 mb-3 shadow-none">
                            <div class="row g-3 align-items-center">
                                <div class="col-lg-6 col-12">
                                    <label class="form-label fw-bold small text-dark mb-1">Select PTKP Training</label>
                                    <select id="selectTrackingTraining" class="form-select rounded-3">
                                        <option value="1" selected>Leadership Development Program (Batch 1 / 2026)</option>
                                        <option value="2">Excel Advanced for Data Analysis (Batch 2 / 2026)</option>
                                        <option value="3">K3 & Safety Awareness in Factory (Batch 3 / 2026)</option>
                                        <option value="4">Effective Business Communication (Batch 1 / 2026)</option>
                                        <option value="5">Basic Fire Fighting & Safety Protocol (Batch 4 / 2025)</option>
                                    </select>
                                </div>
                                <div class="col-lg-6 col-12">
                                    <div class="d-flex align-items-center justify-content-lg-end gap-2 flex-wrap">
                                        <div class="px-3 py-1-5 rounded-3 bg-light border border-light-subtle text-center">
                                            <div class="extra-small text-muted text-uppercase fw-semibold">Quota</div>
                                            <div class="fw-bold text-dark fs-3" id="trackingMetricQuota">30 Seats</div>
                                        </div>
                                        <div class="px-3 py-1-5 rounded-3 bg-light border border-light-subtle text-center">
                                            <div class="extra-small text-muted text-uppercase fw-semibold">Registered</div>
                                            <div class="fw-bold text-primary fs-3" id="trackingMetricRegistered">25</div>
                                        </div>
                                        <div class="px-3 py-1-5 rounded-3 bg-success-subtle border border-success-subtle text-center">
                                            <div class="extra-small text-success text-uppercase fw-semibold">Approved</div>
                                            <div class="fw-bold text-success fs-3" id="trackingMetricApproved">22</div>
                                        </div>
                                        <div class="px-3 py-1-5 rounded-3 bg-warning-subtle border border-warning-subtle text-center">
                                            <div class="extra-small text-warning text-uppercase fw-semibold">Pending</div>
                                            <div class="fw-bold text-warning fs-3" id="trackingMetricPending">3</div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Participants Table Card -->
                        <div class="card border border-light-subtle rounded-3 bg-white shadow-none overflow-hidden">
                            <div class="card-header bg-white border-bottom border-light-subtle py-2 px-3 d-flex align-items-center justify-content-between flex-wrap gap-2">
                                <span class="fw-bold text-dark small" id="trackingTableLabel">
                                    <i class="ti ti-list me-1 text-primary"></i> Registered Participants List
                                </span>
                                <div class="input-icon" style="width: 220px;">
                                    <span class="input-icon-addon">
                                        <i class="ti ti-search text-muted"></i>
                                    </span>
                                    <input type="text" id="searchTrackingParticipant" class="form-control form-control-sm rounded-3" placeholder="Filter employee / NIP...">
                                </div>
                            </div>
                            <div class="table-responsive">
                                <table class="table table-vcenter table-hover align-middle mb-0" id="tableTrackingByTraining">
                                    <thead>
                                        <tr class="text-muted extra-small text-uppercase fw-bold" style="background-color: #f8fafc;">
                                            <th class="w-1 text-center py-2-5">NO</th>
                                            <th class="py-2-5">EMPLOYEE</th>
                                            <th class="py-2-5">NIP</th>
                                            <th class="py-2-5">DEPARTMENT</th>
                                            <th class="py-2-5">REGISTRATION DATE</th>
                                            <th class="py-2-5 text-center">STATUS</th>
                                            <th class="text-end py-2-5 pe-3">ACTIONS</th>
                                        </tr>
                                    </thead>
                                    <tbody class="divide-y" id="tbodyTrackingByTraining">
                                        {{-- Row 1: Approved --}}
                                        <tr class="participant-row">
                                            <td class="text-center text-muted fw-semibold">1</td>
                                            <td>
                                                <div class="d-flex align-items-center gap-2">
                                                    <div class="avatar avatar-sm rounded-circle bg-primary-subtle text-primary fw-bold">BS</div>
                                                    <div>
                                                        <div class="fw-bold text-dark small">Budi Santoso</div>
                                                        <div class="extra-small text-muted">budi.santoso@krakatauposco.co.id</div>
                                                    </div>
                                                </div>
                                            </td>
                                            <td class="small fw-semibold text-secondary">20210045</td>
                                            <td class="small text-dark">Manufacturing & Plant Operations</td>
                                            <td class="small text-muted">05 Sep 2026</td>
                                            <td class="text-center status-cell">
                                                <span class="badge bg-success-subtle text-success rounded-pill px-2-5 py-1 fw-semibold">Approved</span>
                                            </td>
                                            <td class="text-end pe-3 action-cell">
                                                <span class="text-muted extra-small fst-italic">Confirmed</span>
                                            </td>
                                        </tr>

                                        {{-- Row 2: Approved --}}
                                        <tr class="participant-row">
                                            <td class="text-center text-muted fw-semibold">2</td>
                                            <td>
                                                <div class="d-flex align-items-center gap-2">
                                                    <div class="avatar avatar-sm rounded-circle bg-info-subtle text-info fw-bold">DA</div>
                                                    <div>
                                                        <div class="fw-bold text-dark small">Dewi Anggraini</div>
                                                        <div class="extra-small text-muted">dewi.anggraini@krakatauposco.co.id</div>
                                                    </div>
                                                </div>
                                            </td>
                                            <td class="small fw-semibold text-secondary">20220112</td>
                                            <td class="small text-dark">Supply Chain & Logistics</td>
                                            <td class="small text-muted">06 Sep 2026</td>
                                            <td class="text-center status-cell">
                                                <span class="badge bg-success-subtle text-success rounded-pill px-2-5 py-1 fw-semibold">Approved</span>
                                            </td>
                                            <td class="text-end pe-3 action-cell">
                                                <span class="text-muted extra-small fst-italic">Confirmed</span>
                                            </td>
                                        </tr>

                                        {{-- Row 3: Pending Approval --}}
                                        <tr class="participant-row">
                                            <td class="text-center text-muted fw-semibold">3</td>
                                            <td>
                                                <div class="d-flex align-items-center gap-2">
                                                    <div class="avatar avatar-sm rounded-circle bg-warning-subtle text-warning fw-bold">FP</div>
                                                    <div>
                                                        <div class="fw-bold text-dark small">Fajar Pratama</div>
                                                        <div class="extra-small text-muted">fajar.pratama@krakatauposco.co.id</div>
                                                    </div>
                                                </div>
                                            </td>
                                            <td class="small fw-semibold text-secondary">20190089</td>
                                            <td class="small text-dark">Maintenance & Engineering</td>
                                            <td class="small text-muted">08 Sep 2026</td>
                                            <td class="text-center status-cell">
                                                <span class="badge bg-warning-subtle text-warning rounded-pill px-2-5 py-1 fw-semibold">Pending Approval</span>
                                            </td>
                                            <td class="text-end pe-3 action-cell">
                                                <div class="d-inline-flex align-items-center gap-1">
                                                    <button type="button" class="btn btn-sm btn-outline-success rounded-pill px-2 py-0-5 fw-semibold d-inline-flex align-items-center gap-1 btn-action-approve" title="Approve Registration">
                                                        <i class="ti ti-check"></i> Approve
                                                    </button>
                                                    <button type="button" class="btn btn-sm btn-outline-danger rounded-pill px-2 py-0-5 fw-semibold d-inline-flex align-items-center gap-1 btn-action-reject" title="Reject Registration">
                                                        <i class="ti ti-x"></i> Reject
                                                    </button>
                                                </div>
                                            </td>
                                        </tr>

                                        {{-- Row 4: Pending Approval --}}
                                        <tr class="participant-row">
                                            <td class="text-center text-muted fw-semibold">4</td>
                                            <td>
                                                <div class="d-flex align-items-center gap-2">
                                                    <div class="avatar avatar-sm rounded-circle bg-primary-subtle text-primary fw-bold">SR</div>
                                                    <div>
                                                        <div class="fw-bold text-dark small">Siti Rahmawati</div>
                                                        <div class="extra-small text-muted">siti.rahmawati@krakatauposco.co.id</div>
                                                    </div>
                                                </div>
                                            </td>
                                            <td class="small fw-semibold text-secondary">20200156</td>
                                            <td class="small text-dark">Quality Assurance</td>
                                            <td class="small text-muted">10 Sep 2026</td>
                                            <td class="text-center status-cell">
                                                <span class="badge bg-warning-subtle text-warning rounded-pill px-2-5 py-1 fw-semibold">Pending Approval</span>
                                            </td>
                                            <td class="text-end pe-3 action-cell">
                                                <div class="d-inline-flex align-items-center gap-1">
                                                    <button type="button" class="btn btn-sm btn-outline-success rounded-pill px-2 py-0-5 fw-semibold d-inline-flex align-items-center gap-1 btn-action-approve" title="Approve Registration">
                                                        <i class="ti ti-check"></i> Approve
                                                    </button>
                                                    <button type="button" class="btn btn-sm btn-outline-danger rounded-pill px-2 py-0-5 fw-semibold d-inline-flex align-items-center gap-1 btn-action-reject" title="Reject Registration">
                                                        <i class="ti ti-x"></i> Reject
                                                    </button>
                                                </div>
                                            </td>
                                        </tr>

                                        {{-- Row 5: Rejected --}}
                                        <tr class="participant-row">
                                            <td class="text-center text-muted fw-semibold">5</td>
                                            <td>
                                                <div class="d-flex align-items-center gap-2">
                                                    <div class="avatar avatar-sm rounded-circle bg-danger-subtle text-danger fw-bold">RH</div>
                                                    <div>
                                                        <div class="fw-bold text-dark small">Rian Hidayat</div>
                                                        <div class="extra-small text-muted">rian.hidayat@krakatauposco.co.id</div>
                                                    </div>
                                                </div>
                                            </td>
                                            <td class="small fw-semibold text-secondary">20230023</td>
                                            <td class="small text-dark">Finance & Accounting</td>
                                            <td class="small text-muted">11 Sep 2026</td>
                                            <td class="text-center status-cell">
                                                <span class="badge bg-danger-subtle text-danger rounded-pill px-2-5 py-1 fw-semibold">Rejected</span>
                                            </td>
                                            <td class="text-end pe-3 action-cell">
                                                <span class="text-muted extra-small">Over-quota</span>
                                            </td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>

                    {{-- TAB 2: BY USER / EMPLOYEE --}}
                    <div class="tab-pane fade" id="tab-tracking-employee" role="tabpanel">
                        <!-- Employee Selector & Profile Card -->
                        <div class="card border border-light-subtle rounded-3 bg-white p-3 mb-3 shadow-none">
                            <div class="row g-3">
                                <div class="col-lg-6 col-12">
                                    <label class="form-label fw-bold small text-dark mb-1">Select Employee</label>
                                    <select id="selectTrackingEmployee" class="form-select rounded-3">
                                        <option value="20210045" selected>Budi Santoso (NIP: 20210045) &bull; Manufacturing</option>
                                        <option value="20220112">Dewi Anggraini (NIP: 20220112) &bull; Supply Chain</option>
                                        <option value="20190089">Fajar Pratama (NIP: 20190089) &bull; Maintenance</option>
                                        <option value="20200156">Siti Rahmawati (NIP: 20200156) &bull; QA</option>
                                        <option value="20230023">Rian Hidayat (NIP: 20230023) &bull; Finance</option>
                                    </select>
                                </div>
                                <div class="col-lg-6 col-12">
                                    <div class="d-flex align-items-center gap-3 bg-light rounded-3 p-2-5 border border-light-subtle">
                                        <div class="avatar avatar-md rounded-circle bg-primary text-white fw-bold" id="empProfileAvatar">BS</div>
                                        <div class="flex-grow-1">
                                            <div class="fw-bold text-dark" id="empProfileName">Budi Santoso</div>
                                            <div class="small text-muted" id="empProfileMeta">NIP: 20210045 &bull; Senior Plant Technician</div>
                                        </div>
                                        <div class="text-end pe-2">
                                            <span class="badge bg-primary-subtle text-primary border border-primary-subtle rounded-pill px-3 py-1 fw-semibold small" id="empProfileTotal">
                                                4 Applications
                                            </span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Employee Registration History Table -->
                        <div class="card border border-light-subtle rounded-3 bg-white shadow-none overflow-hidden">
                            <div class="card-header bg-white border-bottom border-light-subtle py-2 px-3 d-flex align-items-center justify-content-between">
                                <span class="fw-bold text-dark small">
                                    <i class="ti ti-history me-1 text-primary"></i> Registered Trainings & Application History
                                </span>
                            </div>
                            <div class="table-responsive">
                                <table class="table table-vcenter table-hover align-middle mb-0" id="tableTrackingByEmployee">
                                    <thead>
                                        <tr class="text-muted extra-small text-uppercase fw-bold" style="background-color: #f8fafc;">
                                            <th class="w-1 text-center py-2-5">NO</th>
                                            <th class="py-2-5">TRAINING TITLE</th>
                                            <th class="py-2-5">CATEGORY</th>
                                            <th class="py-2-5">SCHEDULE DATE</th>
                                            <th class="py-2-5">SUBMITTED ON</th>
                                            <th class="py-2-5 text-center">STATUS</th>
                                        </tr>
                                    </thead>
                                    <tbody class="divide-y" id="tbodyTrackingByEmployee">
                                        <tr>
                                            <td class="text-center text-muted fw-semibold">1</td>
                                            <td>
                                                <div class="fw-bold text-dark small">Leadership Development Program</div>
                                                <div class="extra-small text-muted">Code: PTKP-LDP-01</div>
                                            </td>
                                            <td>
                                                <span class="badge rounded-pill px-2-5 py-0-5 fw-semibold" style="background-color: #e0f2fe; color: #0369a1;">Soft Skill</span>
                                            </td>
                                            <td class="small text-dark">18 Sep 2026</td>
                                            <td class="small text-muted">05 Sep 2026</td>
                                            <td class="text-center">
                                                <span class="badge bg-success-subtle text-success rounded-pill px-2-5 py-1 fw-semibold">Approved</span>
                                            </td>
                                        </tr>
                                        <tr>
                                            <td class="text-center text-muted fw-semibold">2</td>
                                            <td>
                                                <div class="fw-bold text-dark small">Excel Advanced for Data Analysis</div>
                                                <div class="extra-small text-muted">Code: PTKP-EXC-02</div>
                                            </td>
                                            <td>
                                                <span class="badge rounded-pill px-2-5 py-0-5 fw-semibold" style="background-color: #e0f2fe; color: #0284c7;">Technical Skill</span>
                                            </td>
                                            <td class="small text-dark">18 Sep 2026</td>
                                            <td class="small text-muted">07 Sep 2026</td>
                                            <td class="text-center">
                                                <span class="badge bg-warning-subtle text-warning rounded-pill px-2-5 py-1 fw-semibold">Pending Approval</span>
                                            </td>
                                        </tr>
                                        <tr>
                                            <td class="text-center text-muted fw-semibold">3</td>
                                            <td>
                                                <div class="fw-bold text-dark small">K3 & Safety Awareness in Factory</div>
                                                <div class="extra-small text-muted">Code: PTKP-SAF-03</div>
                                            </td>
                                            <td>
                                                <span class="badge rounded-pill px-2-5 py-0-5 fw-semibold" style="background-color: #fef3c7; color: #b45309;">Compliance & Safety</span>
                                            </td>
                                            <td class="small text-dark">25 Sep 2026</td>
                                            <td class="small text-muted">01 Sep 2026</td>
                                            <td class="text-center">
                                                <span class="badge bg-success-subtle text-success rounded-pill px-2-5 py-1 fw-semibold">Approved</span>
                                            </td>
                                        </tr>
                                        <tr>
                                            <td class="text-center text-muted fw-semibold">4</td>
                                            <td>
                                                <div class="fw-bold text-dark small">Basic Fire Fighting & Safety Protocol</div>
                                                <div class="extra-small text-muted">Code: PTKP-BFF-05</div>
                                            </td>
                                            <td>
                                                <span class="badge rounded-pill px-2-5 py-0-5 fw-semibold" style="background-color: #fef3c7; color: #b45309;">Compliance & Safety</span>
                                            </td>
                                            <td class="small text-dark">14 Aug 2025</td>
                                            <td class="small text-muted">01 Aug 2025</td>
                                            <td class="text-center">
                                                <span class="badge bg-info-subtle text-info rounded-pill px-2-5 py-1 fw-semibold">Completed</span>
                                            </td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>

                </div>
            </div>

            <!-- Modal Footer -->
            <div class="modal-footer border-top border-light-subtle px-4 py-3 bg-white d-flex justify-content-between align-items-center">
                <button type="button" class="btn btn-outline-secondary px-3 py-2 rounded-3 fw-semibold d-inline-flex align-items-center gap-1" id="btnExportTrackingList">
                    <i class="ti ti-file-export fs-2"></i>
                    <span>Export Participant Roster</span>
                </button>
                <button type="button" class="btn btn-primary px-4 py-2 rounded-3 fw-semibold" style="background-color: #003b6d; border-color: #003b6d;" data-bs-dismiss="modal">
                    Close
                </button>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
    // --- Data Store for Training Participants ---
    const trainingParticipantsStore = {
        '1': {
            title: 'Leadership Development Program (Batch 1 / 2026)',
            quota: '30 Seats', registered: '25', approved: '22', pending: '3',
            participants: [
                { no: 1, name: 'Budi Santoso', nip: '20210045', email: 'budi.santoso@krakatauposco.co.id', dept: 'Manufacturing & Plant Operations', date: '05 Sep 2026', status: 'Approved', initials: 'BS', color: 'primary' },
                { no: 2, name: 'Dewi Anggraini', nip: '20220112', email: 'dewi.anggraini@krakatauposco.co.id', dept: 'Supply Chain & Logistics', date: '06 Sep 2026', status: 'Approved', initials: 'DA', color: 'info' },
                { no: 3, name: 'Fajar Pratama', nip: '20190089', email: 'fajar.pratama@krakatauposco.co.id', dept: 'Maintenance & Engineering', date: '08 Sep 2026', status: 'Pending Approval', initials: 'FP', color: 'warning' },
                { no: 4, name: 'Siti Rahmawati', nip: '20200156', email: 'siti.rahmawati@krakatauposco.co.id', dept: 'Quality Assurance', date: '10 Sep 2026', status: 'Pending Approval', initials: 'SR', color: 'primary' },
                { no: 5, name: 'Rian Hidayat', nip: '20230023', email: 'rian.hidayat@krakatauposco.co.id', dept: 'Finance & Accounting', date: '11 Sep 2026', status: 'Rejected', initials: 'RH', color: 'danger' }
            ]
        },
        '2': {
            title: 'Excel Advanced for Data Analysis (Batch 2 / 2026)',
            quota: '20 Seats', registered: '18', approved: '16', pending: '2',
            participants: [
                { no: 1, name: 'Dewi Anggraini', nip: '20220112', email: 'dewi.anggraini@krakatauposco.co.id', dept: 'Supply Chain & Logistics', date: '07 Sep 2026', status: 'Approved', initials: 'DA', color: 'info' },
                { no: 2, name: 'Rian Hidayat', nip: '20230023', email: 'rian.hidayat@krakatauposco.co.id', dept: 'Finance & Accounting', date: '08 Sep 2026', status: 'Approved', initials: 'RH', color: 'danger' },
                { no: 3, name: 'Budi Santoso', nip: '20210045', email: 'budi.santoso@krakatauposco.co.id', dept: 'Manufacturing & Plant Operations', date: '09 Sep 2026', status: 'Pending Approval', initials: 'BS', color: 'primary' },
                { no: 4, name: 'Siti Rahmawati', nip: '20200156', email: 'siti.rahmawati@krakatauposco.co.id', dept: 'Quality Assurance', date: '10 Sep 2026', status: 'Pending Approval', initials: 'SR', color: 'primary' }
            ]
        },
        '3': {
            title: 'K3 & Safety Awareness in Factory (Batch 3 / 2026)',
            quota: '50 Seats', registered: '40', approved: '38', pending: '2',
            participants: [
                { no: 1, name: 'Fajar Pratama', nip: '20190089', email: 'fajar.pratama@krakatauposco.co.id', dept: 'Maintenance & Engineering', date: '01 Sep 2026', status: 'Approved', initials: 'FP', color: 'warning' },
                { no: 2, name: 'Budi Santoso', nip: '20210045', email: 'budi.santoso@krakatauposco.co.id', dept: 'Manufacturing & Plant Operations', date: '02 Sep 2026', status: 'Approved', initials: 'BS', color: 'primary' },
                { no: 3, name: 'Dewi Anggraini', nip: '20220112', email: 'dewi.anggraini@krakatauposco.co.id', dept: 'Supply Chain & Logistics', date: '03 Sep 2026', status: 'Pending Approval', initials: 'DA', color: 'info' }
            ]
        },
        '4': {
            title: 'Effective Business Communication (Batch 1 / 2026)',
            quota: '25 Seats', registered: '25', approved: '25', pending: '0',
            participants: [
                { no: 1, name: 'Siti Rahmawati', nip: '20200156', email: 'siti.rahmawati@krakatauposco.co.id', dept: 'Quality Assurance', date: '01 Sep 2026', status: 'Approved', initials: 'SR', color: 'primary' },
                { no: 2, name: 'Dewi Anggraini', nip: '20220112', email: 'dewi.anggraini@krakatauposco.co.id', dept: 'Supply Chain & Logistics', date: '02 Sep 2026', status: 'Approved', initials: 'DA', color: 'info' }
            ]
        },
        '5': {
            title: 'Basic Fire Fighting & Safety Protocol (Batch 4 / 2025)',
            quota: '40 Seats', registered: '40', approved: '40', pending: '0',
            participants: [
                { no: 1, name: 'Budi Santoso', nip: '20210045', email: 'budi.santoso@krakatauposco.co.id', dept: 'Manufacturing & Plant Operations', date: '01 Aug 2025', status: 'Approved', initials: 'BS', color: 'primary' },
                { no: 2, name: 'Fajar Pratama', nip: '20190089', email: 'fajar.pratama@krakatauposco.co.id', dept: 'Maintenance & Engineering', date: '01 Aug 2025', status: 'Approved', initials: 'FP', color: 'warning' }
            ]
        }
    };

    // --- Data Store for Employee History ---
    const employeeHistoryStore = {
        '20210045': {
            name: 'Budi Santoso', nip: '20210045', role: 'Senior Plant Technician &bull; Manufacturing Operations', initials: 'BS',
            trainings: [
                { no: 1, title: 'Leadership Development Program', code: 'PTKP-LDP-01', cat: 'Soft Skill', catBg: '#e0f2fe', catCol: '#0369a1', date: '18 Sep 2026', sub: '05 Sep 2026', status: 'Approved' },
                { no: 2, title: 'Excel Advanced for Data Analysis', code: 'PTKP-EXC-02', cat: 'Technical Skill', catBg: '#e0f2fe', catCol: '#0284c7', date: '18 Sep 2026', sub: '09 Sep 2026', status: 'Pending Approval' },
                { no: 3, title: 'K3 & Safety Awareness in Factory', code: 'PTKP-SAF-03', cat: 'Compliance & Safety', catBg: '#fef3c7', catCol: '#b45309', date: '25 Sep 2026', sub: '02 Sep 2026', status: 'Approved' },
                { no: 4, title: 'Basic Fire Fighting & Safety Protocol', code: 'PTKP-BFF-05', cat: 'Compliance & Safety', catBg: '#fef3c7', catCol: '#b45309', date: '14 Aug 2025', sub: '01 Aug 2025', status: 'Completed' }
            ]
        },
        '20220112': {
            name: 'Dewi Anggraini', nip: '20220112', role: 'Logistics Analyst &bull; Supply Chain Management', initials: 'DA',
            trainings: [
                { no: 1, title: 'Leadership Development Program', code: 'PTKP-LDP-01', cat: 'Soft Skill', catBg: '#e0f2fe', catCol: '#0369a1', date: '18 Sep 2026', sub: '06 Sep 2026', status: 'Approved' },
                { no: 2, title: 'Excel Advanced for Data Analysis', code: 'PTKP-EXC-02', cat: 'Technical Skill', catBg: '#e0f2fe', catCol: '#0284c7', date: '18 Sep 2026', sub: '07 Sep 2026', status: 'Approved' },
                { no: 3, title: 'Effective Business Communication', code: 'PTKP-COMM-04', cat: 'Soft Skill', catBg: '#e0f2fe', catCol: '#0369a1', date: '29 Sep 2026', sub: '02 Sep 2026', status: 'Approved' }
            ]
        },
        '20190089': {
            name: 'Fajar Pratama', nip: '20190089', role: 'Maintenance Lead Engineer &bull; Mechanical Plant Unit', initials: 'FP',
            trainings: [
                { no: 1, title: 'Leadership Development Program', code: 'PTKP-LDP-01', cat: 'Soft Skill', catBg: '#e0f2fe', catCol: '#0369a1', date: '18 Sep 2026', sub: '08 Sep 2026', status: 'Pending Approval' },
                { no: 2, title: 'K3 & Safety Awareness in Factory', code: 'PTKP-SAF-03', cat: 'Compliance & Safety', catBg: '#fef3c7', catCol: '#b45309', date: '25 Sep 2026', sub: '01 Sep 2026', status: 'Approved' },
                { no: 3, title: 'Basic Fire Fighting & Safety Protocol', code: 'PTKP-BFF-05', cat: 'Compliance & Safety', catBg: '#fef3c7', catCol: '#b45309', date: '14 Aug 2025', sub: '01 Aug 2025', status: 'Completed' }
            ]
        },
        '20200156': {
            name: 'Siti Rahmawati', nip: '20200156', role: 'Quality Assurance Officer &bull; Steel Metallurgy Division', initials: 'SR',
            trainings: [
                { no: 1, title: 'Leadership Development Program', code: 'PTKP-LDP-01', cat: 'Soft Skill', catBg: '#e0f2fe', catCol: '#0369a1', date: '18 Sep 2026', sub: '10 Sep 2026', status: 'Pending Approval' },
                { no: 2, title: 'Excel Advanced for Data Analysis', code: 'PTKP-EXC-02', cat: 'Technical Skill', catBg: '#e0f2fe', catCol: '#0284c7', date: '18 Sep 2026', sub: '10 Sep 2026', status: 'Pending Approval' },
                { no: 3, title: 'Effective Business Communication', code: 'PTKP-COMM-04', cat: 'Soft Skill', catBg: '#e0f2fe', catCol: '#0369a1', date: '29 Sep 2026', sub: '01 Sep 2026', status: 'Approved' }
            ]
        },
        '20230023': {
            name: 'Rian Hidayat', nip: '20230023', role: 'Junior Budget Accountant &bull; Corporate Finance', initials: 'RH',
            trainings: [
                { no: 1, title: 'Leadership Development Program', code: 'PTKP-LDP-01', cat: 'Soft Skill', catBg: '#e0f2fe', catCol: '#0369a1', date: '18 Sep 2026', sub: '11 Sep 2026', status: 'Rejected' },
                { no: 2, title: 'Excel Advanced for Data Analysis', code: 'PTKP-EXC-02', cat: 'Technical Skill', catBg: '#e0f2fe', catCol: '#0284c7', date: '18 Sep 2026', sub: '08 Sep 2026', status: 'Approved' }
            ]
        }
    };

    // Render Tab 1 (By Training)
    function renderTrackingByTraining(trainingId) {
        const data = trainingParticipantsStore[trainingId] || trainingParticipantsStore['1'];
        
        const qEl = document.getElementById('trackingMetricQuota');
        const rEl = document.getElementById('trackingMetricRegistered');
        const aEl = document.getElementById('trackingMetricApproved');
        const pEl = document.getElementById('trackingMetricPending');
        const tbody = document.getElementById('tbodyTrackingByTraining');

        if (qEl) qEl.textContent = data.quota;
        if (rEl) rEl.textContent = data.registered;
        if (aEl) aEl.textContent = data.approved;
        if (pEl) pEl.textContent = data.pending;

        if (tbody) {
            let html = '';
            data.participants.forEach(p => {
                let statusBadge = '<span class="badge bg-success-subtle text-success rounded-pill px-2-5 py-1 fw-semibold">Approved</span>';
                let actionContent = '<span class="text-muted extra-small fst-italic">Confirmed</span>';

                if (p.status === 'Pending Approval') {
                    statusBadge = '<span class="badge bg-warning-subtle text-warning rounded-pill px-2-5 py-1 fw-semibold">Pending Approval</span>';
                    actionContent = `
                        <div class="d-inline-flex align-items-center gap-1">
                            <button type="button" class="btn btn-sm btn-outline-success rounded-pill px-2 py-0-5 fw-semibold d-inline-flex align-items-center gap-1 btn-action-approve" data-name="${p.name}">
                                <i class="ti ti-check"></i> Approve
                            </button>
                            <button type="button" class="btn btn-sm btn-outline-danger rounded-pill px-2 py-0-5 fw-semibold d-inline-flex align-items-center gap-1 btn-action-reject" data-name="${p.name}">
                                <i class="ti ti-x"></i> Reject
                            </button>
                        </div>
                    `;
                } else if (p.status === 'Rejected') {
                    statusBadge = '<span class="badge bg-danger-subtle text-danger rounded-pill px-2-5 py-1 fw-semibold">Rejected</span>';
                    actionContent = '<span class="text-muted extra-small">Over-quota</span>';
                }

                html += `
                    <tr class="participant-row">
                        <td class="text-center text-muted fw-semibold">${p.no}</td>
                        <td>
                            <div class="d-flex align-items-center gap-2">
                                <div class="avatar avatar-sm rounded-circle bg-${p.color}-subtle text-${p.color} fw-bold">${p.initials}</div>
                                <div>
                                    <div class="fw-bold text-dark small">${p.name}</div>
                                    <div class="extra-small text-muted">${p.email}</div>
                                </div>
                            </div>
                        </td>
                        <td class="small fw-semibold text-secondary">${p.nip}</td>
                        <td class="small text-dark">${p.dept}</td>
                        <td class="small text-muted">${p.date}</td>
                        <td class="text-center status-cell">${statusBadge}</td>
                        <td class="text-end pe-3 action-cell">${actionContent}</td>
                    </tr>
                `;
            });
            tbody.innerHTML = html;
        }
    }

    // Render Tab 2 (By Employee)
    function renderTrackingByEmployee(nip) {
        const emp = employeeHistoryStore[nip] || employeeHistoryStore['20210045'];
        
        const avatarEl = document.getElementById('empProfileAvatar');
        const nameEl = document.getElementById('empProfileName');
        const metaEl = document.getElementById('empProfileMeta');
        const totalEl = document.getElementById('empProfileTotal');
        const tbody = document.getElementById('tbodyTrackingByEmployee');

        if (avatarEl) avatarEl.textContent = emp.initials;
        if (nameEl) nameEl.textContent = emp.name;
        if (metaEl) metaEl.innerHTML = `NIP: ${emp.nip} &bull; ${emp.role}`;
        if (totalEl) totalEl.textContent = `${emp.trainings.length} Application${emp.trainings.length > 1 ? 's' : ''}`;

        if (tbody) {
            let html = '';
            emp.trainings.forEach(t => {
                let badge = '<span class="badge bg-success-subtle text-success rounded-pill px-2-5 py-1 fw-semibold">Approved</span>';
                if (t.status === 'Pending Approval') {
                    badge = '<span class="badge bg-warning-subtle text-warning rounded-pill px-2-5 py-1 fw-semibold">Pending Approval</span>';
                } else if (t.status === 'Completed') {
                    badge = '<span class="badge bg-info-subtle text-info rounded-pill px-2-5 py-1 fw-semibold">Completed</span>';
                } else if (t.status === 'Rejected') {
                    badge = '<span class="badge bg-danger-subtle text-danger rounded-pill px-2-5 py-1 fw-semibold">Rejected</span>';
                }

                html += `
                    <tr>
                        <td class="text-center text-muted fw-semibold">${t.no}</td>
                        <td>
                            <div class="fw-bold text-dark small">${t.title}</div>
                            <div class="extra-small text-muted">Code: ${t.code}</div>
                        </td>
                        <td>
                            <span class="badge rounded-pill px-2-5 py-0-5 fw-semibold" style="background-color: ${t.catBg}; color: ${t.catCol};">${t.cat}</span>
                        </td>
                        <td class="small text-dark">${t.date}</td>
                        <td class="small text-muted">${t.sub}</td>
                        <td class="text-center">${badge}</td>
                    </tr>
                `;
            });
            tbody.innerHTML = html;
        }
    }

    // Select Training change
    const selectTraining = document.getElementById('selectTrackingTraining');
    if (selectTraining) {
        selectTraining.addEventListener('change', function () {
            renderTrackingByTraining(this.value);
        });
    }

    // Select Employee change
    const selectEmployee = document.getElementById('selectTrackingEmployee');
    if (selectEmployee) {
        selectEmployee.addEventListener('change', function () {
            renderTrackingByEmployee(this.value);
        });
    }

    // Search participant in Tab 1
    const searchParticipant = document.getElementById('searchTrackingParticipant');
    if (searchParticipant) {
        searchParticipant.addEventListener('input', function () {
            const q = this.value.toLowerCase().trim();
            const rows = document.querySelectorAll('#tbodyTrackingByTraining tr.participant-row');
            rows.forEach(r => {
                const text = r.textContent.toLowerCase();
                r.style.display = (!q || text.includes(q)) ? '' : 'none';
            });
        });
    }

    // Approve / Reject Actions in Tab 1
    document.addEventListener('click', function (e) {
        const btnApprove = e.target.closest('.btn-action-approve');
        if (btnApprove) {
            const name = btnApprove.getAttribute('data-name') || 'Participant';
            const row = btnApprove.closest('tr');
            if (row) {
                const statusCell = row.querySelector('.status-cell');
                const actionCell = row.querySelector('.action-cell');
                if (statusCell) {
                    statusCell.innerHTML = '<span class="badge bg-success-subtle text-success rounded-pill px-2-5 py-1 fw-semibold">Approved</span>';
                }
                if (actionCell) {
                    actionCell.innerHTML = '<span class="text-success extra-small fw-semibold"><i class="ti ti-check me-0-5"></i>Just Approved</span>';
                }
            }
            if (typeof Swal !== 'undefined') {
                Swal.fire({
                    icon: 'success',
                    title: 'Registration Approved',
                    text: `${name}'s training registration has been approved.`,
                    timer: 2000,
                    showConfirmButton: false
                });
            } else {
                alert(`${name}'s registration approved!`);
            }
            return;
        }

        const btnReject = e.target.closest('.btn-action-reject');
        if (btnReject) {
            const name = btnReject.getAttribute('data-name') || 'Participant';
            const row = btnReject.closest('tr');
            if (row) {
                const statusCell = row.querySelector('.status-cell');
                const actionCell = row.querySelector('.action-cell');
                if (statusCell) {
                    statusCell.innerHTML = '<span class="badge bg-danger-subtle text-danger rounded-pill px-2-5 py-1 fw-semibold">Rejected</span>';
                }
                if (actionCell) {
                    actionCell.innerHTML = '<span class="text-danger extra-small fw-semibold"><i class="ti ti-x me-0-5"></i>Rejected</span>';
                }
            }
            if (typeof Swal !== 'undefined') {
                Swal.fire({
                    icon: 'warning',
                    title: 'Registration Rejected',
                    text: `${name}'s registration has been rejected.`,
                    timer: 2000,
                    showConfirmButton: false
                });
            } else {
                alert(`${name}'s registration rejected.`);
            }
            return;
        }
    });

    // Global listener to trigger opening the participant tracking modal with selected training
    window.openParticipantTrackingModal = function (trainingId, trainingTitle) {
        if (selectTraining) {
            selectTraining.value = trainingId || '1';
            renderTrackingByTraining(trainingId || '1');
        }
        const trackingTabBtn = document.getElementById('tab-tracking-training-btn');
        if (trackingTabBtn) {
            const tabInstance = bootstrap.Tab.getOrCreateInstance(trackingTabBtn);
            tabInstance.show();
        }
        const modalEl = document.getElementById('participantTrackingModal');
        if (modalEl) {
            const modalInstance = bootstrap.Modal.getOrCreateInstance(modalEl);
            modalInstance.show();
        }
    };

    // Export list simulation
    const btnExport = document.getElementById('btnExportTrackingList');
    if (btnExport) {
        btnExport.addEventListener('click', function () {
            if (typeof Swal !== 'undefined') {
                Swal.fire({
                    icon: 'success',
                    title: 'Exporting Roster',
                    text: 'Participant registration roster has been exported to Excel (.xlsx).',
                    timer: 2000,
                    showConfirmButton: false
                });
            } else {
                alert('Participant roster exported successfully.');
            }
        });
    }
});
</script>
