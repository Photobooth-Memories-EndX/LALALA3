@php
    $trackingData = $trainings->mapWithKeys(function ($t) {
        $quota = $t->quota ?? 0;
        $regs = $t->registrations ?? collect();
        $approvedCount = $t->approved_count ?? $regs->where('status', 'Approved')->count();
        $pendingCount = $t->pending_count ?? $regs->where('status', 'Pending')->count();
        $completedCount = $t->completed_count ?? $regs->where('status', 'Completed')->count();
        $rejectedCount = $t->rejected_count ?? $regs->where('status', 'Rejected')->count();
        $registeredCount = $t->registrations_count ?? $regs->count();

        return [
            (string) $t->id => [
                'id'          => $t->id,
                'title'       => $t->title,
                'code'        => $t->training_code ?? ('PTKP-TR-' . $t->id),
                'quota'       => $quota . ' Seats',
                'registered'  => (string) $registeredCount,
                'approved'    => (string) $approvedCount,
                'pending'     => (string) $pendingCount,
                'completed'   => (string) $completedCount,
                'rejected'    => (string) $rejectedCount,
                'participants'=> $regs->map(function ($reg, $idx) {
                    $user = $reg->user;
                    $name = $user->name ?? 'Unknown User';
                    $words = explode(' ', trim($name));
                    $initials = strtoupper(substr($words[0] ?? 'U', 0, 1) . (isset($words[1]) ? substr($words[1], 0, 1) : ''));
                    return [
                        'id'             => $reg->id,
                        'no'             => $idx + 1,
                        'user_id'        => $reg->user_id,
                        'name'           => $name,
                        'nip'            => $user->id_emp ?? '-',
                        'email'          => $user->email ?? '-',
                        'dept'           => $user->department ?? '-',
                        'date'           => $reg->registered_at ? $reg->registered_at->format('d M Y') : '-',
                        'status'         => $reg->status,
                        'initials'       => $initials ?: 'U',
                        'color'          => match($reg->status) {
                            'Approved'   => 'success',
                            'Pending'    => 'warning',
                            'Completed'  => 'info',
                            'Rejected'   => 'danger',
                            default      => 'secondary',
                        },
                    ];
                })->values()->toArray(),
            ]
        ];
    })->toArray();

    // Collect unique registered employees across all schedule trainings
    $registeredEmployees = collect();
    foreach ($trainings as $t) {
        foreach ($t->registrations as $reg) {
            if ($reg->user && !$registeredEmployees->has($reg->user_id)) {
                $u = $reg->user;
                $words = explode(' ', trim($u->name));
                $initials = strtoupper(substr($words[0] ?? 'U', 0, 1) . (isset($words[1]) ? substr($words[1], 0, 1) : ''));
                $registeredEmployees->put($reg->user_id, [
                    'user_id'   => $u->id,
                    'name'      => $u->name,
                    'nip'       => $u->id_emp ?? '-',
                    'email'     => $u->email ?? '-',
                    'dept'      => $u->department ?? '-',
                    'role'      => ($u->job_position ?? 'Employee') . ' &bull; ' . ($u->department ?? 'General'),
                    'initials'  => $initials ?: 'U',
                    'trainings' => [],
                ]);
            }
        }
    }

    foreach ($trainings as $t) {
        foreach ($t->registrations as $reg) {
            if ($reg->user && $registeredEmployees->has($reg->user_id)) {
                $emp = $registeredEmployees->get($reg->user_id);
                $emp['trainings'][] = [
                    'reg_id'   => $reg->id,
                    'no'       => count($emp['trainings']) + 1,
                    'title'    => $t->title,
                    'code'     => $t->training_code ?? ('PTKP-TR-' . $t->id),
                    'cat'      => $t->category ?? 'General',
                    'catBg'    => '#e0f2fe',
                    'catCol'   => '#0369a1',
                    'date'     => $t->start_date ? \Carbon\Carbon::parse($t->start_date)->format('d M Y') : '-',
                    'sub'      => $reg->registered_at ? $reg->registered_at->format('d M Y') : '-',
                    'status'   => $reg->status,
                ];
                $registeredEmployees->put($reg->user_id, $emp);
            }
        }
    }

    $firstTraining = $selectedTraining ?? $trainings->first();
    $firstTrainingId = $firstTraining ? (string) $firstTraining->id : '';
    $firstEmp = $registeredEmployees->first();
    $firstEmpId = $firstEmp ? (string) $firstEmp['user_id'] : '';
@endphp

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

            <a href="{{ route('manage.roster.export') }}" class="btn btn-outline-success rounded-3 px-3 py-1-5 fw-semibold d-inline-flex align-items-center gap-1" id="btnExportInlineRoster" title="Export Participant Roster (CSV / Excel)">
                <i class="ti ti-download me-1 fs-2"></i>
                <span>Download Roster</span>
            </a>
        </div>
    </div>

    {{-- Standardized Participant Tracking Quick Filter Bar --}}
    <div class="card border border-light-subtle rounded-3 bg-light-subtle p-3 mb-4 shadow-none">
        <div class="text-secondary small fw-semibold mb-2" style="color: #6c757d !important; font-size: 0.85rem;">
            Participant Filters &amp; Search
        </div>
        <form id="trackingFilterForm" onsubmit="return false;">
            <div class="d-flex align-items-center gap-2 flex-wrap flex-lg-nowrap">
                <!-- 1. Category Filter -->
                <div style="min-width: 130px; flex-shrink: 0;">
                    <select id="trackingCategorySelect" class="form-select rounded-3 bg-white">
                        <option value="">All Category</option>
                        <option value="Basic & Common Duties">Basic & Common Duties</option>
                        <option value="Compliance">Compliance</option>
                        <option value="Innovation">Innovation</option>
                        <option value="Job Functional">Job Functional</option>
                        <option value="Language">Language</option>
                        <option value="Leadership">Leadership</option>
                    </select>
                </div>

                <!-- 2. Status Filter -->
                <div style="min-width: 120px; flex-shrink: 0;">
                    <select id="trackingStatusSelect" class="form-select rounded-3 bg-white">
                        <option value="">All Status</option>
                        <option value="Pending">Pending</option>
                        <option value="Approved">Approved</option>
                        <option value="Completed">Completed</option>
                        <option value="Rejected">Rejected</option>
                    </select>
                </div>

                <!-- 3. Month Filter -->
                <div style="min-width: 110px; flex-shrink: 0;">
                    <select id="trackingMonthSelect" class="form-select rounded-3 bg-white">
                        <option value="" selected>All Month</option>
                        <option value="1">January</option>
                        <option value="2">February</option>
                        <option value="3">March</option>
                        <option value="4">April</option>
                        <option value="5">May</option>
                        <option value="6">June</option>
                        <option value="7">July</option>
                        <option value="8">August</option>
                        <option value="9">September</option>
                        <option value="10">October</option>
                        <option value="11">November</option>
                        <option value="12">December</option>
                    </select>
                </div>

                <!-- 4. Year Filter -->
                <div style="min-width: 100px; flex-shrink: 0;">
                    <select id="trackingYearSelect" class="form-select rounded-3 bg-white">
                        <option value="" selected>All Year</option>
                        <option value="2026">2026</option>
                        <option value="2025">2025</option>
                        <option value="2027">2027</option>
                    </select>
                </div>

                <!-- 5. Search Input -->
                <div class="position-relative flex-grow-1" style="min-width: 200px;">
                    <div class="input-icon">
                        <span class="input-icon-addon">
                            <i class="ti ti-search text-muted fs-2"></i>
                        </span>
                        <input type="text" id="trackingSearchInput" class="form-control rounded-3 bg-white" 
                               placeholder="Search participant name, NIP, or department..." autocomplete="off">
                    </div>
                </div>

                <!-- 6. Action Buttons -->
                <div class="d-flex align-items-center gap-1 flex-shrink-0">
                    <button type="submit" id="btnSubmitTrackingFilter" class="btn btn-primary px-3 rounded-3" 
                            style="background-color: #003b6d; border-color: #003b6d;" title="Apply Filter">
                        <i class="ti ti-search fs-2"></i>
                    </button>

                    <button type="button" id="btnResetTrackingFilter" class="btn btn-outline-primary px-3 rounded-3 d-inline-flex align-items-center justify-content-center" 
                            style="color: #003b6d; border-color: #003b6d;" title="Reset Filters">
                        <i class="ti ti-refresh fs-2"></i>
                    </button>
                </div>
            </div>
        </form>
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
                            @forelse ($trainings as $t)
                                <option value="{{ $t->id }}" {{ ($firstTrainingId == $t->id) ? 'selected' : '' }}>
                                    {{ $t->title }} ({{ $t->training_code ?? 'PTKP-TR-' . $t->id }})
                                </option>
                            @empty
                                <option value="">No training schedules available</option>
                            @endforelse
                        </select>
                    </div>

                    <div class="col-lg-7 col-12">
                        <div class="d-flex align-items-center justify-content-lg-end gap-2 flex-wrap">
                            <div class="px-3 py-2 rounded-3 bg-white border border-light-subtle text-center shadow-none flex-grow-1 flex-md-grow-0" style="min-width: 90px;">
                                <div class="extra-small text-muted text-uppercase fw-semibold">Quota</div>
                                <div class="fw-bold text-dark fs-3" id="inlineMetricQuota">{{ $firstTraining ? ($firstTraining->quota . ' Seats') : '0 Seats' }}</div>
                            </div>
                            <div class="px-3 py-2 rounded-3 bg-white border border-light-subtle text-center shadow-none flex-grow-1 flex-md-grow-0" style="min-width: 90px;">
                                <div class="extra-small text-muted text-uppercase fw-semibold">Registered</div>
                                <div class="fw-bold text-primary fs-3" id="inlineMetricRegistered">{{ $firstTraining->registrations_count ?? 0 }}</div>
                            </div>
                            <div class="px-3 py-2 rounded-3 bg-success-subtle border border-success-subtle text-center flex-grow-1 flex-md-grow-0" style="min-width: 90px;">
                                <div class="extra-small text-success text-uppercase fw-semibold">Approved</div>
                                <div class="fw-bold text-success fs-3" id="inlineMetricApproved">{{ $firstTraining->approved_count ?? 0 }}</div>
                            </div>
                            <div class="px-3 py-2 rounded-3 bg-warning-subtle border border-warning-subtle text-center flex-grow-1 flex-md-grow-0" style="min-width: 90px;">
                                <div class="extra-small text-warning text-uppercase fw-semibold">Pending</div>
                                <div class="fw-bold text-warning fs-3" id="inlineMetricPending">{{ $firstTraining->pending_count ?? 0 }}</div>
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
                            {{-- Dynamically populated via JavaScript from live trackingData --}}
                        </tbody>
                    </table>
                </div>

                {{-- Clean Empty State Container --}}
                <div class="text-center py-5 d-none" id="emptyStateInlineByTraining">
                    <div class="avatar avatar-xl rounded-circle bg-light text-muted mb-3 mx-auto">
                        <i class="ti ti-users-off fs-1"></i>
                    </div>
                    <h5 class="fw-bold text-dark mb-1">No participants have registered for this training yet.</h5>
                    <p class="text-muted small mb-0">Participant registrations from Training Schedule will appear here.</p>
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
                            @forelse ($registeredEmployees as $emp)
                                <option value="{{ $emp['user_id'] }}">
                                    {{ $emp['name'] }} (NIP: {{ $emp['nip'] }}) &bull; {{ $emp['dept'] }}
                                </option>
                            @empty
                                <option value="">No employee registrations available</option>
                            @endforelse
                        </select>
                    </div>
                    <div class="col-lg-7 col-12">
                        <div class="d-flex align-items-center gap-3 bg-white rounded-3 p-3 border border-light-subtle">
                            <div class="avatar avatar-md rounded-circle bg-primary text-white fw-bold" id="inlineEmpProfileAvatar">
                                {{ $firstEmp['initials'] ?? '-' }}
                            </div>
                            <div class="flex-grow-1">
                                <div class="fw-bold text-dark fs-3" id="inlineEmpProfileName">
                                    {{ $firstEmp['name'] ?? 'No Employee Selected' }}
                                </div>
                                <div class="small text-muted" id="inlineEmpProfileMeta">
                                    @if ($firstEmp)
                                        NIP: {{ $firstEmp['nip'] }} &bull; {{ $firstEmp['role'] }}
                                    @else
                                        Select an employee to inspect their training registrations.
                                    @endif
                                </div>
                            </div>
                            <div class="text-end pe-1">
                                <span class="badge bg-primary-subtle text-primary border border-primary-subtle rounded-pill px-3 py-1-5 fw-semibold" id="inlineEmpProfileTotal">
                                    {{ $firstEmp ? count($firstEmp['trainings']) : 0 }} Applications
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

                {{-- Empty State for Employee View --}}
                <div class="text-center py-5 d-none" id="emptyStateInlineByEmployee">
                    <div class="avatar avatar-xl rounded-circle bg-light text-muted mb-3 mx-auto">
                        <i class="ti ti-history-off fs-1"></i>
                    </div>
                    <h5 class="fw-bold text-dark mb-1">No registration history found for this employee.</h5>
                    <p class="text-muted small mb-0">Applications submitted from Training Schedule will be tracked here.</p>
                </div>
            </div>
        </div>

    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
    // --- Live Data Stores from Database ---
    const liveTrackingStore = @json($trackingData);
    const liveEmployeeStore = @json($registeredEmployees->toArray());
    const csrfToken = '{{ csrf_token() }}';

    // Render Tab 1 (By Training Title)
    window.renderInlineTrackingByTraining = function(trainingId) {
        const data = liveTrackingStore[String(trainingId)];
        
        const qEl = document.getElementById('inlineMetricQuota');
        const rEl = document.getElementById('inlineMetricRegistered');
        const aEl = document.getElementById('inlineMetricApproved');
        const pEl = document.getElementById('inlineMetricPending');
        const tbody = document.getElementById('tbodyInlineByTraining');
        const emptyState = document.getElementById('emptyStateInlineByTraining');

        if (!data) {
            if (qEl) qEl.textContent = '0 Seats';
            if (rEl) rEl.textContent = '0';
            if (aEl) aEl.textContent = '0';
            if (pEl) pEl.textContent = '0';
            if (tbody) tbody.innerHTML = '';
            if (emptyState) emptyState.classList.remove('d-none');
            return;
        }

        if (qEl) qEl.textContent = data.quota;
        if (rEl) rEl.textContent = data.registered;
        if (aEl) aEl.textContent = data.approved;
        if (pEl) pEl.textContent = data.pending;

        if (tbody) {
            if (!data.participants || data.participants.length === 0) {
                tbody.innerHTML = '';
                if (emptyState) emptyState.classList.remove('d-none');
            } else {
                if (emptyState) emptyState.classList.add('d-none');
                let html = '';
                data.participants.forEach(p => {
                    let statusBadge = '<span class="badge bg-secondary-subtle text-secondary rounded-pill px-2-5 py-1 fw-semibold">' + p.status + '</span>';
                    let actionContent = '<span class="text-muted extra-small fst-italic">-</span>';

                    if (p.status === 'Pending') {
                        statusBadge = '<span class="badge bg-warning-subtle text-warning border border-warning-subtle rounded-pill px-3 py-1 fw-semibold">Pending Approval</span>';
                        actionContent = `
                            <div class="d-inline-flex align-items-center gap-1">
                                <button type="button" class="btn btn-sm btn-outline-success rounded-pill px-2 py-0-5 fw-semibold d-inline-flex align-items-center gap-1 btn-action-approve" data-id="${p.id}" data-name="${p.name}">
                                    <i class="ti ti-check"></i> Approve
                                </button>
                                <button type="button" class="btn btn-sm btn-outline-danger rounded-pill px-2 py-0-5 fw-semibold d-inline-flex align-items-center gap-1 btn-action-reject" data-id="${p.id}" data-name="${p.name}">
                                    <i class="ti ti-x"></i> Reject
                                </button>
                            </div>
                        `;
                    } else if (p.status === 'Approved') {
                        statusBadge = '<span class="badge bg-success-subtle text-success rounded-pill px-2-5 py-1 fw-semibold">Approved</span>';
                        actionContent = `
                            <div class="d-inline-flex align-items-center gap-1">
                                <button type="button" class="btn btn-sm btn-outline-primary rounded-pill px-2-5 py-0-5 fw-semibold d-inline-flex align-items-center gap-1 btn-action-complete" data-id="${p.id}" data-name="${p.name}">
                                    <i class="ti ti-check-double"></i> Complete
                                </button>
                                <button type="button" class="btn btn-sm btn-outline-danger rounded-pill px-2 py-0-5 fw-semibold d-inline-flex align-items-center gap-1 btn-action-reject" data-id="${p.id}" data-name="${p.name}">
                                    <i class="ti ti-x"></i> Reject
                                </button>
                            </div>
                        `;
                    } else if (p.status === 'Completed') {
                        statusBadge = '<span class="badge bg-info-subtle text-info rounded-pill px-2-5 py-1 fw-semibold">Completed</span>';
                        actionContent = '<span class="text-muted extra-small fst-italic">Completed & Synced</span>';
                    } else if (p.status === 'Rejected') {
                        statusBadge = '<span class="badge bg-danger-subtle text-danger rounded-pill px-2-5 py-1 fw-semibold">Rejected</span>';
                        actionContent = '<span class="text-muted extra-small text-danger">Rejected</span>';
                    }

                    html += `
                        <tr class="inline-participant-row" data-id="${p.id}">
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
        }
    };

    // Render Tab 2 (By Employee)
    window.renderInlineTrackingByEmployee = function(empId) {
        const emp = liveEmployeeStore[String(empId)];
        
        const avatarEl = document.getElementById('inlineEmpProfileAvatar');
        const nameEl = document.getElementById('inlineEmpProfileName');
        const metaEl = document.getElementById('inlineEmpProfileMeta');
        const totalEl = document.getElementById('inlineEmpProfileTotal');
        const tbody = document.getElementById('tbodyInlineByEmployee');
        const emptyState = document.getElementById('emptyStateInlineByEmployee');

        if (!emp) {
            if (avatarEl) avatarEl.textContent = '-';
            if (nameEl) nameEl.textContent = 'No Employee Selected';
            if (metaEl) metaEl.textContent = 'No registered employees found.';
            if (totalEl) totalEl.textContent = '0 Applications';
            if (tbody) tbody.innerHTML = '';
            if (emptyState) emptyState.classList.remove('d-none');
            return;
        }

        if (avatarEl) avatarEl.textContent = emp.initials;
        if (nameEl) nameEl.textContent = emp.name;
        if (metaEl) metaEl.innerHTML = `NIP: ${emp.nip} &bull; ${emp.role}`;
        if (totalEl) totalEl.textContent = `${emp.trainings.length} Application${emp.trainings.length !== 1 ? 's' : ''}`;

        if (tbody) {
            if (!emp.trainings || emp.trainings.length === 0) {
                tbody.innerHTML = '';
                if (emptyState) emptyState.classList.remove('d-none');
            } else {
                if (emptyState) emptyState.classList.add('d-none');
                let html = '';
                emp.trainings.forEach(t => {
                    let badge = '<span class="badge bg-secondary-subtle text-secondary rounded-pill px-2-5 py-1 fw-semibold">' + t.status + '</span>';
                    let actionHtml = '<span class="text-muted extra-small fst-italic">-</span>';

                    if (t.status === 'Pending') {
                        badge = '<span class="badge bg-warning-subtle text-warning border border-warning-subtle rounded-pill px-3 py-1 fw-semibold">Pending Approval</span>';
                        actionHtml = `
                            <div class="d-inline-flex align-items-center gap-1">
                                <button type="button" class="btn btn-sm btn-outline-success rounded-pill px-2 py-0-5 fw-semibold d-inline-flex align-items-center gap-1 btn-action-approve" data-id="${t.reg_id}" data-name="${emp.name}">
                                    <i class="ti ti-check"></i> Approve
                                </button>
                                <button type="button" class="btn btn-sm btn-outline-danger rounded-pill px-2 py-0-5 fw-semibold d-inline-flex align-items-center gap-1 btn-action-reject" data-id="${t.reg_id}" data-name="${emp.name}">
                                    <i class="ti ti-x"></i> Reject
                                </button>
                            </div>
                        `;
                    } else if (t.status === 'Approved') {
                        badge = '<span class="badge bg-success-subtle text-success rounded-pill px-2-5 py-1 fw-semibold">Approved</span>';
                        actionHtml = `
                            <button type="button" class="btn btn-sm btn-outline-primary rounded-pill px-2-5 py-0-5 fw-semibold d-inline-flex align-items-center gap-1 btn-action-complete" data-id="${t.reg_id}" data-name="${emp.name}">
                                <i class="ti ti-check-double"></i> Complete
                            </button>
                        `;
                    } else if (t.status === 'Completed') {
                        badge = '<span class="badge bg-info-subtle text-info rounded-pill px-2-5 py-1 fw-semibold">Completed</span>';
                        actionHtml = '<span class="text-muted extra-small fst-italic">Completed</span>';
                    } else if (t.status === 'Rejected') {
                        badge = '<span class="badge bg-danger-subtle text-danger rounded-pill px-2-5 py-1 fw-semibold">Rejected</span>';
                        actionHtml = '<span class="text-muted extra-small">Rejected</span>';
                    }

                    html += `
                        <tr class="inline-employee-row" data-id="${t.reg_id}">
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
        }
    };

    // Change listeners for dropdowns
    const selectTraining = document.getElementById('selectInlineTraining');
    if (selectTraining) {
        selectTraining.addEventListener('change', function () {
            renderInlineTrackingByTraining(this.value);
        });
    }

    const selectEmployee = document.getElementById('selectInlineEmployee');
    if (selectEmployee) {
        selectEmployee.addEventListener('change', function () {
            renderInlineTrackingByEmployee(this.value);
        });
    }

    // Tracking Filter Elements
    const trackingSearch = document.getElementById('trackingSearchInput');
    const trackingCategory = document.getElementById('trackingCategorySelect');
    const trackingStatus = document.getElementById('trackingStatusSelect');
    const trackingMonth = document.getElementById('trackingMonthSelect');
    const trackingYear = document.getElementById('trackingYearSelect');
    const btnResetTracking = document.getElementById('btnResetTrackingFilter');
    const btnExportRoster = document.getElementById('btnExportInlineRoster');

    function updateRosterExportUrl() {
        if (!btnExportRoster) return;
        const params = new URLSearchParams();
        const activeTrainingId = selectTraining?.value;
        const activeEmpId = selectEmployee?.value;

        if (activeTrainingId) params.set('training_id', activeTrainingId);
        if (activeEmpId && document.getElementById('tab-inline-employee')?.classList.contains('active')) {
            params.set('user_id', activeEmpId);
        }
        if (trackingStatus?.value) params.set('status', trackingStatus.value);
        if (trackingMonth?.value) params.set('month', trackingMonth.value);
        if (trackingYear?.value) params.set('year', trackingYear.value);

        const baseUrl = '{{ route("manage.roster.export") }}';
        btnExportRoster.href = params.toString() ? `${baseUrl}?${params.toString()}` : baseUrl;
    }

    function filterTrackingView() {
        const q = (trackingSearch?.value || '').toLowerCase().trim();
        const stat = (trackingStatus?.value || '').trim();

        // 1. Filter rows in Training view
        const participantRows = document.querySelectorAll('#tbodyInlineByTraining tr.inline-participant-row');
        participantRows.forEach(r => {
            const text = r.textContent.toLowerCase();
            const matchesSearch = !q || text.includes(q);
            const matchesStatus = !stat || text.includes(stat.toLowerCase());
            r.style.display = (matchesSearch && matchesStatus) ? '' : 'none';
        });

        // 2. Filter rows in Employee view
        const empRows = document.querySelectorAll('#tbodyInlineByEmployee tr.inline-employee-row');
        empRows.forEach(r => {
            const text = r.textContent.toLowerCase();
            const matchesSearch = !q || text.includes(q);
            const matchesStatus = !stat || text.includes(stat.toLowerCase());
            r.style.display = (matchesSearch && matchesStatus) ? '' : 'none';
        });

        updateRosterExportUrl();
    }

    if (trackingSearch) trackingSearch.addEventListener('input', filterTrackingView);
    if (trackingCategory) trackingCategory.addEventListener('change', filterTrackingView);
    if (trackingStatus) trackingStatus.addEventListener('change', filterTrackingView);
    if (trackingMonth) trackingMonth.addEventListener('change', filterTrackingView);
    if (trackingYear) trackingYear.addEventListener('change', filterTrackingView);

    if (btnResetTracking) {
        btnResetTracking.addEventListener('click', function () {
            if (trackingSearch) trackingSearch.value = '';
            if (trackingCategory) trackingCategory.value = '';
            if (trackingStatus) trackingStatus.value = '';
            if (trackingMonth) trackingMonth.value = '';
            if (trackingYear) trackingYear.value = '';
            filterTrackingView();
        });
    }

    // Live search filter for participants roster
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

    // Server-backed status update helper
    async function updateRegistrationStatus(regId, status, reason = '') {
        const url = `/manage/training-schedule/registrations/${regId}/status`;
        const res = await fetch(url, {
            method: 'PATCH',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': csrfToken,
                'Accept': 'application/json'
            },
            body: JSON.stringify({ status: status, reason: reason })
        });
        return await res.json();
    }

    // Server-backed completion helper
    async function completeRegistration(regId) {
        const url = `/manage/training-schedule/registrations/${regId}/complete`;
        const res = await fetch(url, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': csrfToken,
                'Accept': 'application/json'
            }
        });
        return await res.json();
    }

    // Action button delegated clicks
    document.addEventListener('click', async function (e) {
        // 1. Approve
        const approveBtn = e.target.closest('.btn-action-approve');
        if (approveBtn) {
            const regId = approveBtn.getAttribute('data-id');
            const name = approveBtn.getAttribute('data-name') || 'Participant';
            approveBtn.disabled = true;

            try {
                const result = await updateRegistrationStatus(regId, 'Approved');
                if (result.success) {
                    if (typeof Swal !== 'undefined') {
                        Swal.fire({
                            toast: true, position: 'top-end', icon: 'success',
                            title: `Approved application for ${name}`,
                            showConfirmButton: false, timer: 2000
                        });
                    }
                    setTimeout(() => window.location.reload(), 600);
                } else {
                    alert(result.message || 'Failed to approve registration.');
                    approveBtn.disabled = false;
                }
            } catch (err) {
                alert('Network error while approving registration.');
                approveBtn.disabled = false;
            }
            return;
        }

        // 2. Reject
        const rejectBtn = e.target.closest('.btn-action-reject');
        if (rejectBtn) {
            const regId = rejectBtn.getAttribute('data-id');
            const name = rejectBtn.getAttribute('data-name') || 'Participant';

            if (!confirm(`Are you sure you want to reject the application for ${name}?`)) {
                return;
            }

            rejectBtn.disabled = true;
            try {
                const result = await updateRegistrationStatus(regId, 'Rejected');
                if (result.success) {
                    if (typeof Swal !== 'undefined') {
                        Swal.fire({
                            toast: true, position: 'top-end', icon: 'info',
                            title: `Rejected application for ${name}`,
                            showConfirmButton: false, timer: 2000
                        });
                    }
                    setTimeout(() => window.location.reload(), 600);
                } else {
                    alert(result.message || 'Failed to reject registration.');
                    rejectBtn.disabled = false;
                }
            } catch (err) {
                alert('Network error while rejecting registration.');
                rejectBtn.disabled = false;
            }
            return;
        }

        // 3. Complete
        const completeBtn = e.target.closest('.btn-action-complete');
        if (completeBtn) {
            const regId = completeBtn.getAttribute('data-id');
            const name = completeBtn.getAttribute('data-name') || 'Participant';

            if (!confirm(`Mark training as Completed for ${name}? This will record their attendance and credit learning hours.`)) {
                return;
            }

            completeBtn.disabled = true;
            try {
                const result = await completeRegistration(regId);
                if (result.success) {
                    if (typeof Swal !== 'undefined') {
                        Swal.fire({
                            toast: true, position: 'top-end', icon: 'success',
                            title: `Training completed for ${name}`,
                            showConfirmButton: false, timer: 2000
                        });
                    }
                    setTimeout(() => window.location.reload(), 600);
                } else {
                    alert(result.message || 'Failed to complete registration.');
                    completeBtn.disabled = false;
                }
            } catch (err) {
                alert('Network error while completing registration.');
                completeBtn.disabled = false;
            }
            return;
        }
    });

    // Initial render
    const initialTrainingId = '{{ $firstTrainingId }}';
    const initialEmpId = '{{ $firstEmpId }}';

    if (initialTrainingId) {
        renderInlineTrackingByTraining(initialTrainingId);
    }
    if (initialEmpId) {
        renderInlineTrackingByEmployee(initialEmpId);
    }

    // Check query params for tab/training preselection
    const urlParams = new URLSearchParams(window.location.search);
    const tabParam = urlParams.get('tab');
    const trainingParam = urlParams.get('training');

    if (tabParam === 'participants') {
        const trackingTabBtn = document.getElementById('tab-participant-tracking-btn');
        if (trackingTabBtn) {
            const tabTrigger = new bootstrap.Tab(trackingTabBtn);
            tabTrigger.show();
        }
        if (trainingParam && selectTraining) {
            selectTraining.value = trainingParam;
            renderInlineTrackingByTraining(trainingParam);
        }
    }
});
</script>
