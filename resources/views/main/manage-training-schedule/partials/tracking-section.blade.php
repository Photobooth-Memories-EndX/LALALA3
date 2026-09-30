{{-- Inline Participant Tracking & Approvals Section --}}
<div class="card border-0 shadow-sm rounded-4 bg-white p-4 mb-4">
    <!-- Section Top Header & View Switcher -->
    <div class="d-flex flex-column flex-md-row align-items-md-center justify-content-between gap-3 pb-3 border-bottom border-light-subtle mb-4">
        <div>
            <h4 class="fw-bold text-dark mb-1 d-inline-flex align-items-center gap-2">
                <i class="ti ti-users-group text-primary fs-1"></i>
                <span>Participant Tracking & Approvals</span>
            </h4>
            <p class="text-muted small mb-0">Track employee applications, manage approval statuses, and review training history across the organization.</p>
        </div>

        <div class="d-flex align-items-center gap-2 flex-wrap">
            {{-- Dual-View Nav Pills --}}
            <ul class="nav nav-pills bg-light p-1 rounded-pill border border-light-subtle" id="inlineTrackingTabs" role="tablist">
                <li class="nav-item" role="presentation">
                    <button class="nav-link active fw-semibold rounded-pill px-3 py-1-5 d-inline-flex align-items-center gap-2" 
                            id="tab-inline-training-btn" data-bs-toggle="pill" data-bs-target="#tab-inline-training" type="button" role="tab" aria-selected="true">
                        <i class="ti ti-school fs-2"></i>
                        <span>By Training Title</span>
                    </button>
                </li>
                <li class="nav-item" role="presentation">
                    <button class="nav-link fw-semibold rounded-pill px-3 py-1-5 d-inline-flex align-items-center gap-2" 
                            id="tab-inline-employee-btn" data-bs-toggle="pill" data-bs-target="#tab-inline-employee" type="button" role="tab" aria-selected="false">
                        <i class="ti ti-user fs-2"></i>
                        <span>By User / Employee</span>
                    </button>
                </li>
            </ul>

            <button type="button" class="btn btn-outline-secondary rounded-3 px-3 py-1-5 fw-semibold d-inline-flex align-items-center gap-1" id="btnExportInlineRoster">
                <i class="ti ti-download me-1 fs-2"></i>
                <span>Download</span>
            </button>
        </div>
    </div>

    <!-- Tab Content -->
    <div class="tab-content" id="inlineTrackingTabContent">

        {{-- ========================================================================= --}}
        {{-- VIEW 1: BY TRAINING TITLE                                                 --}}
        {{-- ========================================================================= --}}
        <div class="tab-pane fade show active" id="tab-inline-training" role="tabpanel">
            <!-- Training Selection & Metrics Row -->
            <div class="card border border-light-subtle rounded-3 bg-light-subtle p-3 mb-4 shadow-none">
                <div class="row g-3 align-items-center">
                    <div class="col-lg-5 col-12">
                        <label class="form-label fw-bold small text-dark mb-1">Select PTKP Training Schedule</label>
                        <select id="selectInlineTraining" class="form-select rounded-3 bg-white">
                            <option value="1" selected>Leadership Development Program (Batch 1 / 2026)</option>
                            <option value="2">Excel Advanced for Data Analysis (Batch 2 / 2026)</option>
                            <option value="3">K3 & Safety Awareness in Factory (Batch 3 / 2026)</option>
                            <option value="4">Effective Business Communication (Batch 1 / 2026)</option>
                            <option value="5">Basic Fire Fighting & Safety Protocol (Batch 4 / 2025)</option>
                        </select>
                    </div>

                    <div class="col-lg-7 col-12">
                        <div class="d-flex align-items-center justify-content-lg-end gap-2 flex-wrap">
                            <div class="px-3 py-2 rounded-3 bg-white border border-light-subtle text-center shadow-none flex-grow-1 flex-md-grow-0" style="min-width: 90px;">
                                <div class="extra-small text-muted text-uppercase fw-semibold">Quota</div>
                                <div class="fw-bold text-dark fs-3" id="inlineMetricQuota">30 Seats</div>
                            </div>
                            <div class="px-3 py-2 rounded-3 bg-white border border-light-subtle text-center shadow-none flex-grow-1 flex-md-grow-0" style="min-width: 90px;">
                                <div class="extra-small text-muted text-uppercase fw-semibold">Registered</div>
                                <div class="fw-bold text-primary fs-3" id="inlineMetricRegistered">25</div>
                            </div>
                            <div class="px-3 py-2 rounded-3 bg-success-subtle border border-success-subtle text-center flex-grow-1 flex-md-grow-0" style="min-width: 90px;">
                                <div class="extra-small text-success text-uppercase fw-semibold">Approved</div>
                                <div class="fw-bold text-success fs-3" id="inlineMetricApproved">22</div>
                            </div>
                            <div class="px-3 py-2 rounded-3 bg-warning-subtle border border-warning-subtle text-center flex-grow-1 flex-md-grow-0" style="min-width: 90px;">
                                <div class="extra-small text-warning text-uppercase fw-semibold">Pending</div>
                                <div class="fw-bold text-warning fs-3" id="inlineMetricPending">3</div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Participants Table -->
            <div class="card border border-light-subtle rounded-3 bg-white shadow-none overflow-hidden">
                <div class="card-header bg-white border-bottom border-light-subtle py-2-5 px-3 d-flex align-items-center justify-content-between flex-wrap gap-2">
                    <span class="fw-bold text-dark small" id="inlineTableLabel">
                        <i class="ti ti-list me-1 text-primary"></i> Registered Participants Roster
                    </span>
                    <div class="input-icon" style="width: 250px;">
                        <span class="input-icon-addon">
                            <i class="ti ti-search text-muted"></i>
                        </span>
                        <input type="text" id="searchInlineParticipant" class="form-control form-control-sm rounded-3" placeholder="Filter employee name / NIP...">
                    </div>
                </div>
                <div class="table-responsive">
                    <table class="table table-vcenter table-hover align-middle mb-0" id="tableInlineByTraining">
                        <thead>
                            <tr class="text-muted extra-small text-uppercase fw-bold" style="background-color: #f8fafc;">
                                <th class="w-1 text-center align-middle py-2-5">NO</th>
                                <th class="text-center align-middle py-2-5">EMPLOYEE</th>
                                <th class="text-center align-middle py-2-5">NIP</th>
                                <th class="text-center align-middle py-2-5">DEPARTMENT</th>
                                <th class="text-center align-middle py-2-5">REGISTRATION DATE</th>
                                <th class="text-center align-middle py-2-5">STATUS</th>
                                <th class="text-center align-middle py-2-5">ACTIONS</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y" id="tbodyInlineByTraining">
                            {{-- Dynamically populated via JavaScript --}}
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        {{-- ========================================================================= --}}
        {{-- VIEW 2: BY USER / EMPLOYEE                                                --}}
        {{-- ========================================================================= --}}
        <div class="tab-pane fade" id="tab-inline-employee" role="tabpanel">
            <!-- Employee Selector & Profile Card -->
            <div class="card border border-light-subtle rounded-3 bg-light-subtle p-3 mb-4 shadow-none">
                <div class="row g-3 align-items-center">
                    <div class="col-lg-5 col-12">
                        <label class="form-label fw-bold small text-dark mb-1">Select Employee</label>
                        <select id="selectInlineEmployee" class="form-select rounded-3 bg-white">
                            <option value="20210045" selected>Budi Santoso (NIP: 20210045) &bull; Manufacturing</option>
                            <option value="20220112">Dewi Anggraini (NIP: 20220112) &bull; Supply Chain</option>
                            <option value="20190089">Fajar Pratama (NIP: 20190089) &bull; Maintenance</option>
                            <option value="20200156">Siti Rahmawati (NIP: 20200156) &bull; QA</option>
                            <option value="20230023">Rian Hidayat (NIP: 20230023) &bull; Finance</option>
                        </select>
                    </div>
                    <div class="col-lg-7 col-12">
                        <div class="d-flex align-items-center gap-3 bg-white rounded-3 p-3 border border-light-subtle">
                            <div class="avatar avatar-md rounded-circle bg-primary text-white fw-bold" id="inlineEmpProfileAvatar">BS</div>
                            <div class="flex-grow-1">
                                <div class="fw-bold text-dark fs-3" id="inlineEmpProfileName">Budi Santoso</div>
                                <div class="small text-muted" id="inlineEmpProfileMeta">NIP: 20210045 &bull; Senior Plant Technician &bull; Manufacturing Operations</div>
                            </div>
                            <div class="text-end pe-1">
                                <span class="badge bg-primary-subtle text-primary border border-primary-subtle rounded-pill px-3 py-1-5 fw-semibold" id="inlineEmpProfileTotal">
                                    4 Applications
                                </span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Employee Training History Table -->
            <div class="card border border-light-subtle rounded-3 bg-white shadow-none overflow-hidden">
                <div class="card-header bg-white border-bottom border-light-subtle py-2-5 px-3 d-flex align-items-center justify-content-between">
                    <span class="fw-bold text-dark small">
                        <i class="ti ti-history me-1 text-primary"></i> Registered Trainings & Application History
                    </span>
                </div>
                <div class="table-responsive">
                    <table class="table table-vcenter table-hover align-middle mb-0" id="tableInlineByEmployee">
                        <thead>
                            <tr class="text-muted extra-small text-uppercase fw-bold" style="background-color: #f8fafc;">
                                <th class="w-1 text-center align-middle py-2-5">NO</th>
                                <th class="text-center align-middle py-2-5">TRAINING TITLE</th>
                                <th class="text-center align-middle py-2-5">CATEGORY</th>
                                <th class="text-center align-middle py-2-5">SCHEDULE DATE</th>
                                <th class="text-center align-middle py-2-5">SUBMITTED ON</th>
                                <th class="text-center align-middle py-2-5">STATUS</th>
                                <th class="text-center align-middle py-2-5">ACTION</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y" id="tbodyInlineByEmployee">
                            {{-- Dynamically populated via JavaScript --}}
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
    // --- Data Store for Training Participants ---
    const inlineTrainingStore = {
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
    const inlineEmployeeStore = {
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
    window.renderInlineTrackingByTraining = function(trainingId) {
        const data = inlineTrainingStore[trainingId] || inlineTrainingStore['1'];
        
        const qEl = document.getElementById('inlineMetricQuota');
        const rEl = document.getElementById('inlineMetricRegistered');
        const aEl = document.getElementById('inlineMetricApproved');
        const pEl = document.getElementById('inlineMetricPending');
        const tbody = document.getElementById('tbodyInlineByTraining');

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
                    statusBadge = '<span class="badge bg-warning-subtle text-warning border border-warning-subtle rounded-pill px-3 py-1 fw-semibold">Pending Approval</span>';
                    actionContent = `
                        <div class="d-inline-flex align-items-center gap-1">
                            <button type="button" class="btn btn-sm btn-outline-success rounded-pill px-2 py-0-5 fw-semibold d-inline-flex align-items-center gap-1 btn-inline-approve" data-name="${p.name}">
                                <i class="ti ti-check"></i> Approve
                            </button>
                            <button type="button" class="btn btn-sm btn-outline-danger rounded-pill px-2 py-0-5 fw-semibold d-inline-flex align-items-center gap-1 btn-inline-reject" data-name="${p.name}">
                                <i class="ti ti-x"></i> Reject
                            </button>
                        </div>
                    `;
                } else if (p.status === 'Rejected') {
                    statusBadge = '<span class="badge bg-danger-subtle text-danger rounded-pill px-2-5 py-1 fw-semibold">Rejected</span>';
                    actionContent = '<span class="text-muted extra-small">Over-quota</span>';
                }

                html += `
                    <tr class="inline-participant-row">
                        <td class="text-center align-middle text-muted fw-semibold">${p.no}</td>
                        <td class="text-center align-middle">
                            <div class="d-inline-flex align-items-center text-start gap-2">
                                <div class="avatar avatar-sm rounded-circle bg-${p.color}-subtle text-${p.color} fw-bold flex-shrink-0">${p.initials}</div>
                                <div>
                                    <div class="fw-bold text-dark small">${p.name}</div>
                                    <div class="extra-small text-muted">${p.email}</div>
                                </div>
                            </div>
                        </td>
                        <td class="text-center align-middle small fw-semibold text-secondary">${p.nip}</td>
                        <td class="text-center align-middle small text-dark">${p.dept}</td>
                        <td class="text-center align-middle small text-muted">${p.date}</td>
                        <td class="text-center align-middle inline-status-cell">${statusBadge}</td>
                        <td class="text-center align-middle inline-action-cell">${actionContent}</td>
                    </tr>
                `;
            });
            tbody.innerHTML = html;
        }
    };

    // Render Tab 2 (By Employee)
    window.renderInlineTrackingByEmployee = function(nip) {
        const emp = inlineEmployeeStore[nip] || inlineEmployeeStore['20210045'];
        
        const avatarEl = document.getElementById('inlineEmpProfileAvatar');
        const nameEl = document.getElementById('inlineEmpProfileName');
        const metaEl = document.getElementById('inlineEmpProfileMeta');
        const totalEl = document.getElementById('inlineEmpProfileTotal');
        const tbody = document.getElementById('tbodyInlineByEmployee');

        if (avatarEl) avatarEl.textContent = emp.initials;
        if (nameEl) nameEl.textContent = emp.name;
        if (metaEl) metaEl.innerHTML = `NIP: ${emp.nip} &bull; ${emp.role}`;
        if (totalEl) totalEl.textContent = `${emp.trainings.length} Application${emp.trainings.length > 1 ? 's' : ''}`;

        if (tbody) {
            let html = '';
            emp.trainings.forEach(t => {
                let badge = '<span class="badge bg-success-subtle text-success rounded-pill px-2-5 py-1 fw-semibold">Approved</span>';
                let actionHtml = '<span class="text-muted extra-small fst-italic">Confirmed</span>';

                if (t.status === 'Pending Approval') {
                    badge = '<span class="badge bg-warning-subtle text-warning border border-warning-subtle rounded-pill px-3 py-1 fw-semibold">Pending Approval</span>';
                    actionHtml = `
                        <div class="d-inline-flex align-items-center gap-1">
                            <button type="button" class="btn btn-sm btn-outline-success rounded-pill px-2 py-0-5 fw-semibold d-inline-flex align-items-center gap-1 btn-inline-emp-approve" data-title="${t.title}" data-name="${emp.name}">
                                <i class="ti ti-check"></i> Approve
                            </button>
                            <button type="button" class="btn btn-sm btn-outline-danger rounded-pill px-2 py-0-5 fw-semibold d-inline-flex align-items-center gap-1 btn-inline-emp-reject" data-title="${t.title}" data-name="${emp.name}">
                                <i class="ti ti-x"></i> Reject
                            </button>
                        </div>
                    `;
                } else if (t.status === 'Completed') {
                    badge = '<span class="badge bg-info-subtle text-info rounded-pill px-2-5 py-1 fw-semibold">Completed</span>';
                    actionHtml = '<span class="text-muted extra-small fst-italic">Completed</span>';
                } else if (t.status === 'Rejected') {
                    badge = '<span class="badge bg-danger-subtle text-danger rounded-pill px-2-5 py-1 fw-semibold">Rejected</span>';
                    actionHtml = '<span class="text-muted extra-small">Rejected</span>';
                } else if (t.status === 'Approved') {
                    badge = '<span class="badge bg-success-subtle text-success rounded-pill px-2-5 py-1 fw-semibold">Approved</span>';
                    actionHtml = '<span class="text-muted extra-small fst-italic">Confirmed</span>';
                }

                html += `
                    <tr class="inline-employee-row">
                        <td class="text-center align-middle text-muted fw-semibold">${t.no}</td>
                        <td class="text-center align-middle">
                            <div class="fw-bold text-dark small">${t.title}</div>
                            <div class="extra-small text-muted">Code: ${t.code}</div>
                        </td>
                        <td class="text-center align-middle">
                            <span class="badge rounded-pill px-2-5 py-0-5 fw-semibold" style="background-color: ${t.catBg}; color: ${t.catCol};">${t.cat}</span>
                        </td>
                        <td class="text-center align-middle small text-dark">${t.date}</td>
                        <td class="text-center align-middle small text-muted">${t.sub}</td>
                        <td class="text-center align-middle emp-status-cell">${badge}</td>
                        <td class="text-center align-middle emp-action-cell">${actionHtml}</td>
                    </tr>
                `;
            });
            tbody.innerHTML = html;
        }
    };

    // Select Training Change Listener
    const selectTraining = document.getElementById('selectInlineTraining');
    if (selectTraining) {
        selectTraining.addEventListener('change', function () {
            renderInlineTrackingByTraining(this.value);
        });
    }

    // Select Employee Change Listener
    const selectEmployee = document.getElementById('selectInlineEmployee');
    if (selectEmployee) {
        selectEmployee.addEventListener('change', function () {
            renderInlineTrackingByEmployee(this.value);
        });
    }

    // Live Search Filter for Participants
    const searchInput = document.getElementById('searchInlineParticipant');
    if (searchInput) {
        searchInput.addEventListener('input', function () {
            const query = this.value.toLowerCase().trim();
            const rows = document.querySelectorAll('#tbodyInlineByTraining tr.inline-participant-row');
            rows.forEach(r => {
                const text = r.textContent.toLowerCase();
                r.style.display = (!query || text.includes(query)) ? '' : 'none';
            });
        });
    }

    // Approve / Reject Actions within Table
    document.addEventListener('click', function (e) {
        const approveBtn = e.target.closest('.btn-inline-approve');
        if (approveBtn) {
            const row = approveBtn.closest('tr');
            const name = approveBtn.getAttribute('data-name') || 'Participant';
            if (row) {
                const statusCell = row.querySelector('.inline-status-cell');
                const actionCell = row.querySelector('.inline-action-cell');
                if (statusCell) {
                    statusCell.innerHTML = '<span class="badge bg-success-subtle text-success rounded-pill px-2-5 py-1 fw-semibold">Approved</span>';
                }
                if (actionCell) {
                    actionCell.innerHTML = '<span class="text-muted extra-small fst-italic">Confirmed</span>';
                }
            }

            // Update pending / approved metric counts
            const pEl = document.getElementById('inlineMetricPending');
            const aEl = document.getElementById('inlineMetricApproved');
            if (pEl && parseInt(pEl.textContent) > 0) {
                pEl.textContent = parseInt(pEl.textContent) - 1;
            }
            if (aEl) {
                aEl.textContent = parseInt(aEl.textContent) + 1;
            }

            if (typeof Swal !== 'undefined') {
                Swal.fire({
                    toast: true,
                    position: 'top-end',
                    icon: 'success',
                    title: `Approved application for ${name}`,
                    showConfirmButton: false,
                    timer: 2500
                });
            } else {
                alert(`Approved application for ${name}`);
            }
            return;
        }

        const rejectBtn = e.target.closest('.btn-inline-reject');
        if (rejectBtn) {
            const row = rejectBtn.closest('tr');
            const name = rejectBtn.getAttribute('data-name') || 'Participant';
            if (row) {
                const statusCell = row.querySelector('.inline-status-cell');
                const actionCell = row.querySelector('.inline-action-cell');
                if (statusCell) {
                    statusCell.innerHTML = '<span class="badge bg-danger-subtle text-danger rounded-pill px-2-5 py-1 fw-semibold">Rejected</span>';
                }
                if (actionCell) {
                    actionCell.innerHTML = '<span class="text-muted extra-small">Rejected</span>';
                }
            }

            // Update pending metric count
            const pEl = document.getElementById('inlineMetricPending');
            if (pEl && parseInt(pEl.textContent) > 0) {
                pEl.textContent = parseInt(pEl.textContent) - 1;
            }

            if (typeof Swal !== 'undefined') {
                Swal.fire({
                    toast: true,
                    position: 'top-end',
                    icon: 'info',
                    title: `Application rejected for ${name}`,
                    showConfirmButton: false,
                    timer: 2500
                });
            } else {
                alert(`Application rejected for ${name}`);
            }
            return;
        }

        // By-Employee View: Approve
        const empApproveBtn = e.target.closest('.btn-inline-emp-approve');
        if (empApproveBtn) {
            const row = empApproveBtn.closest('tr');
            const title = empApproveBtn.getAttribute('data-title') || 'Training';
            const name = empApproveBtn.getAttribute('data-name') || 'Employee';
            if (row) {
                const statusCell = row.querySelector('.emp-status-cell');
                const actionCell = row.querySelector('.emp-action-cell');
                if (statusCell) {
                    statusCell.innerHTML = '<span class="badge bg-success-subtle text-success rounded-pill px-2-5 py-1 fw-semibold">Approved</span>';
                }
                if (actionCell) {
                    actionCell.innerHTML = '<span class="text-muted extra-small fst-italic">Confirmed</span>';
                }
            }

            if (typeof Swal !== 'undefined') {
                Swal.fire({
                    toast: true,
                    position: 'top-end',
                    icon: 'success',
                    title: `Approved: "${title}" for ${name}`,
                    showConfirmButton: false,
                    timer: 2500
                });
            } else {
                alert(`Approved: "${title}" for ${name}`);
            }
            return;
        }

        // By-Employee View: Reject
        const empRejectBtn = e.target.closest('.btn-inline-emp-reject');
        if (empRejectBtn) {
            const row = empRejectBtn.closest('tr');
            const title = empRejectBtn.getAttribute('data-title') || 'Training';
            const name = empRejectBtn.getAttribute('data-name') || 'Employee';
            if (row) {
                const statusCell = row.querySelector('.emp-status-cell');
                const actionCell = row.querySelector('.emp-action-cell');
                if (statusCell) {
                    statusCell.innerHTML = '<span class="badge bg-danger-subtle text-danger rounded-pill px-2-5 py-1 fw-semibold">Rejected</span>';
                }
                if (actionCell) {
                    actionCell.innerHTML = '<span class="text-muted extra-small">Rejected</span>';
                }
            }

            if (typeof Swal !== 'undefined') {
                Swal.fire({
                    toast: true,
                    position: 'top-end',
                    icon: 'info',
                    title: `Rejected: "${title}" for ${name}`,
                    showConfirmButton: false,
                    timer: 2500
                });
            } else {
                alert(`Rejected: "${title}" for ${name}`);
            }
            return;
        }
    });

    // Download Roster Button
    const btnExport = document.getElementById('btnExportInlineRoster');
    if (btnExport) {
        btnExport.addEventListener('click', function () {
            if (typeof Swal !== 'undefined') {
                Swal.fire({
                    icon: 'success',
                    title: 'Roster Downloaded',
                    text: 'Participant list downloaded as CSV / Excel format.',
                    timer: 2000,
                    showConfirmButton: false
                });
            } else {
                alert('Participant roster downloaded!');
            }
        });
    }

    // Initial render
    renderInlineTrackingByTraining('1');
    renderInlineTrackingByEmployee('20210045');
});
</script>
