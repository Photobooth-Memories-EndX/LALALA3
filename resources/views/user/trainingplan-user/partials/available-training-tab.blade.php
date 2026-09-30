{{-- Tab Training Tersedia / Available Training (Table View Layout) --}}
<style>
    .btn-owl-register {
        color: #003b6d !important;
        border-color: #003b6d !important;
        background-color: transparent !important;
        transition: all 0.2s ease-in-out;
    }
    .btn-owl-register:hover {
        color: #ffffff !important;
        background-color: #003b6d !important;
        border-color: #003b6d !important;
        box-shadow: 0 4px 6px -1px rgba(0, 59, 109, 0.2);
    }
    .bg-purple-subtle {
        background-color: #f3e8ff !important;
    }
    .text-purple {
        color: #6f42c1 !important;
    }
</style>
<div class="card border border-light-subtle shadow-sm rounded-4 bg-white p-4 mb-4">
    
    {{-- Quick Filter Bar --}}
    <div class="row g-3 align-items-center mb-4 pb-2 border-bottom border-light-subtle">
        <div class="col-lg-5 col-md-5">
            <div class="input-icon">
                <span class="input-icon-addon">
                    <i class="ti ti-search text-muted fs-2"></i>
                </span>
                <input type="text" id="filterTrainingSearch" class="form-control rounded-3" placeholder="Search training title, instructor, or keywords...">
            </div>
        </div>
        <div class="col-lg-3 col-md-3">
            <select id="filterTrainingCategory" class="form-select rounded-3">
                <option value="">All Category</option>
                <option value="Soft Skill">Soft Skill</option>
                <option value="Technical Skill">Technical Skill</option>
                <option value="Compliance & Safety">Compliance & Safety</option>
                <option value="Leadership">Leadership</option>
            </select>
        </div>
        <div class="col-lg-2 col-md-2">
            <select id="filterTrainingStatus" class="form-select rounded-3">
                <option value="">All Status</option>
                <option value="open">Open Registration</option>
                <option value="running">Running</option>
                <option value="pending">Pending Approval</option>
                <option value="registered">Registered</option>
                <option value="closed">Closed Registration</option>
            </select>
        </div>
        <div class="col-lg-2 col-md-2 d-flex justify-content-end">
            <span class="badge bg-light text-secondary border px-3 py-2 rounded-pill fw-medium align-self-center">
                Total: 4 Trainings
            </span>
        </div>
    </div>

    {{-- Available Training Table --}}
    <div class="table-responsive">
        <table class="table table-vcenter card-table table-hover align-middle mb-0" id="availableTrainingTable">
            <thead>
                <tr class="text-muted extra-small text-uppercase fw-bold" style="letter-spacing: 0.05em; background-color: #f8fafc;">
                    <th class="w-1 text-center align-middle py-3">NO</th>
                    <th class="text-center align-middle py-3" style="min-width: 200px;">TRAINING NAME</th>
                    <th class="text-center align-middle py-3">CATEGORY</th>
                    <th class="text-center align-middle py-3" style="min-width: 110px;">DATE</th>
                    <th class="text-center align-middle py-3" style="min-width: 120px;">TIME</th>
                    <th class="text-center align-middle py-3">LOCATION</th>
                    <th class="text-center align-middle py-3">SYLLABUS</th>
                    <th class="text-center align-middle py-3">APPROVED PARTICIPANT</th>
                    <th class="text-center align-middle py-3">STATUS</th>
                    <th class="text-center align-middle py-3 pe-4">ACTION</th>
                </tr>
            </thead>
            <tbody class="divide-y">
                {{-- Row 1: Open Registration --}}
                <tr class="training-table-row" data-id="1" data-category="Soft Skill" data-status="open" data-instructor="Dr. Hendra Wijaya, M.M.">
                    <td class="text-center align-middle text-muted fw-semibold">1</td>
                    <td class="text-center align-middle" style="min-width: 200px;">
                        <div class="fw-bold text-dark fs-3">Leadership Development Program</div>
                    </td>
                    <td class="text-center align-middle">
                        <span class="badge rounded-pill px-2 py-1 fw-semibold" style="background-color: #e0f2fe; color: #0369a1;">
                            Soft Skill
                        </span>
                    </td>
                    <td class="text-center align-middle" style="min-width: 110px;">
                        <div class="fw-semibold text-dark">18 Sep 2026</div>
                    </td>
                    <td class="text-center align-middle" style="min-width: 120px;">
                        <div class="text-muted small">08:00 - 16:00 WIB</div>
                    </td>
                    <td class="text-center align-middle">
                        <span class="text-muted small">Training Room Building A, 2nd Fl.</span>
                    </td>
                    <td class="text-center align-middle">
                        <button type="button" class="btn btn-sm btn-outline-secondary rounded-pill px-3 d-inline-flex align-items-center gap-1 btn-view-syllabus" 
                                data-bs-toggle="modal" data-bs-target="#syllabusModal" data-id="1" 
                                data-title="Leadership Development Program" data-category="Soft Skill" 
                                data-instructor="Dr. Hendra Wijaya, M.M.">
                            <i class="ti ti-list-check fs-2"></i>
                            <span>Syllabus</span>
                        </button>
                    </td>
                    <td class="text-center align-middle" style="min-width: 130px;">
                        <div class="fw-semibold text-dark small">25 / 30 Seats</div>
                        <div class="progress progress-xs mt-1 mx-auto rounded-pill" style="height: 4px; max-width: 90px; background-color: #e2e8f0;">
                            <div class="progress-bar rounded-pill" role="progressbar" style="width: 83%; background-color: #f97316;" aria-valuenow="83" aria-valuemin="0" aria-valuemax="100"></div>
                        </div>
                    </td>
                    <td class="text-center align-middle">
                        <span class="badge bg-success-subtle text-success rounded-pill px-3 py-1 fw-semibold">
                            Open Registration
                        </span>
                    </td>
                    <td class="text-center align-middle pe-4">
                        <button type="button" class="btn btn-owl-register rounded-pill px-4 py-1-5 fw-semibold d-inline-flex align-items-center gap-1">
                            <span>Register</span>
                        </button>
                    </td>
                </tr>

                {{-- Row 2: Running --}}
                <tr class="training-table-row" data-id="2" data-category="Technical Skill" data-status="running" data-instructor="Bambang Sujarwo, S.Kom.">
                    <td class="text-center align-middle text-muted fw-semibold">2</td>
                    <td class="text-center align-middle" style="min-width: 200px;">
                        <div class="fw-bold text-dark fs-3">Excel Advanced for Data Analysis</div>
                    </td>
                    <td class="text-center align-middle">
                        <span class="badge rounded-pill px-2 py-1 fw-semibold" style="background-color: #e0f2fe; color: #0284c7;">
                            Technical Skill
                        </span>
                    </td>
                    <td class="text-center align-middle" style="min-width: 110px;">
                        <div class="fw-semibold text-dark">18 Sep 2026</div>
                    </td>
                    <td class="text-center align-middle" style="min-width: 120px;">
                        <div class="text-muted small">09:00 - 15:00 WIB</div>
                    </td>
                    <td class="text-center align-middle">
                        <span class="text-muted small">Computer Lab 2, 3rd Fl.</span>
                    </td>
                    <td class="text-center align-middle">
                        <button type="button" class="btn btn-sm btn-outline-secondary rounded-pill px-3 d-inline-flex align-items-center gap-1 btn-view-syllabus" 
                                data-bs-toggle="modal" data-bs-target="#syllabusModal" data-id="2" 
                                data-title="Excel Advanced for Data Analysis" data-category="Technical Skill" 
                                data-instructor="Bambang Sujarwo, S.Kom.">
                            <i class="ti ti-list-check fs-2"></i>
                            <span>Syllabus</span>
                        </button>
                    </td>
                    <td class="text-center align-middle" style="min-width: 130px;">
                        <div class="fw-semibold text-dark small">18 / 40 Seats</div>
                        <div class="progress progress-xs mt-1 mx-auto rounded-pill" style="height: 4px; max-width: 90px; background-color: #e2e8f0;">
                            <div class="progress-bar rounded-pill" role="progressbar" style="width: 45%; background-color: #003b6d;" aria-valuenow="45" aria-valuemin="0" aria-valuemax="100"></div>
                        </div>
                    </td>
                    <td class="text-center align-middle">
                        <span class="badge bg-warning-subtle text-warning border border-warning-subtle rounded-pill px-3 py-1 fw-semibold">
                            Running
                        </span>
                    </td>
                    <td class="text-center align-middle pe-4">
                        <button type="button" class="btn btn-secondary rounded-pill px-4 py-1-5 fw-semibold" disabled>
                            <span>Closed</span>
                        </button>
                    </td>
                </tr>

                {{-- Row 3: Registered --}}
                <tr class="training-table-row" data-id="3" data-category="Compliance & Safety" data-status="registered" data-instructor="Ir. Agus Priyono, CSP">
                    <td class="text-center align-middle text-muted fw-semibold">3</td>
                    <td class="text-center align-middle" style="min-width: 200px;">
                        <div class="fw-bold text-dark fs-3">K3 & Safety Awareness in Factory</div>
                    </td>
                    <td class="text-center align-middle">
                        <span class="badge rounded-pill px-2 py-1 fw-semibold" style="background-color: #fef3c7; color: #b45309;">
                            Compliance & Safety
                        </span>
                    </td>
                    <td class="text-center align-middle" style="min-width: 110px;">
                        <div class="fw-semibold text-dark">25 Sep 2026</div>
                    </td>
                    <td class="text-center align-middle" style="min-width: 120px;">
                        <div class="text-muted small">08:00 - 11:30 WIB</div>
                    </td>
                    <td class="text-center align-middle">
                        <span class="text-muted small">Auditorium Krakatau Posco</span>
                    </td>
                    <td class="text-center align-middle">
                        <button type="button" class="btn btn-sm btn-outline-secondary rounded-pill px-3 d-inline-flex align-items-center gap-1 btn-view-syllabus" 
                                data-bs-toggle="modal" data-bs-target="#syllabusModal" data-id="3" 
                                data-title="K3 & Safety Awareness in Factory" data-category="Compliance & Safety" 
                                data-instructor="Ir. Agus Priyono, CSP">
                            <i class="ti ti-list-check fs-2"></i>
                            <span>Syllabus</span>
                        </button>
                    </td>
                    <td class="text-center align-middle" style="min-width: 130px;">
                        <div class="fw-semibold text-dark small">40 / 50 Seats</div>
                        <div class="progress progress-xs mt-1 mx-auto rounded-pill" style="height: 4px; max-width: 90px; background-color: #e2e8f0;">
                            <div class="progress-bar rounded-pill" role="progressbar" style="width: 80%; background-color: #f97316;" aria-valuenow="80" aria-valuemin="0" aria-valuemax="100"></div>
                        </div>
                    </td>
                    <td class="text-center align-middle">
                        <span class="badge bg-info-subtle text-info rounded-pill px-3 py-1 fw-semibold">
                            Registered
                        </span>
                    </td>
                    <td class="text-center align-middle pe-4">
                        <button type="button" class="btn btn-outline-success rounded-pill px-4 py-1-5 fw-semibold" disabled>
                            <i class="ti ti-check me-1"></i>
                            <span>Registered</span>
                        </button>
                    </td>
                </tr>

                {{-- Row 4: Closed --}}
                <tr class="training-table-row" data-id="4" data-category="Soft Skill" data-status="closed" data-instructor="Citra Lestari, M.Psi.">
                    <td class="text-center align-middle text-muted fw-semibold">4</td>
                    <td class="text-center align-middle" style="min-width: 200px;">
                        <div class="fw-bold text-dark fs-3">Effective Business Communication</div>
                    </td>
                    <td class="text-center align-middle">
                        <span class="badge rounded-pill px-2 py-1 fw-semibold" style="background-color: #e0f2fe; color: #0369a1;">
                            Soft Skill
                        </span>
                    </td>
                    <td class="text-center align-middle" style="min-width: 110px;">
                        <div class="fw-semibold text-dark">29 Sep 2026</div>
                    </td>
                    <td class="text-center align-middle" style="min-width: 120px;">
                        <div class="text-muted small">13:00 - 17:00 WIB</div>
                    </td>
                    <td class="text-center align-middle">
                        <span class="text-muted small">Meeting Room Bintang 1</span>
                    </td>
                    <td class="text-center align-middle">
                        <button type="button" class="btn btn-sm btn-outline-secondary rounded-pill px-3 d-inline-flex align-items-center gap-1 btn-view-syllabus" 
                                data-bs-toggle="modal" data-bs-target="#syllabusModal" data-id="4" 
                                data-title="Effective Business Communication" data-category="Soft Skill" 
                                data-instructor="Citra Lestari, M.Psi.">
                            <i class="ti ti-list-check fs-2"></i>
                            <span>Syllabus</span>
                        </button>
                    </td>
                    <td class="text-center align-middle" style="min-width: 130px;">
                        <div class="fw-semibold text-dark small">25 / 25 Seats (Full)</div>
                        <div class="progress progress-xs mt-1 mx-auto rounded-pill" style="height: 4px; max-width: 90px; background-color: #e2e8f0;">
                            <div class="progress-bar rounded-pill" role="progressbar" style="width: 100%; background-color: #ef4444;" aria-valuenow="100" aria-valuemin="0" aria-valuemax="100"></div>
                        </div>
                    </td>
                    <td class="text-center align-middle">
                        <span class="badge bg-secondary-subtle text-secondary rounded-pill px-3 py-1 fw-semibold">
                            Closed
                        </span>
                    </td>
                    <td class="text-center align-middle pe-4">
                        <button type="button" class="btn btn-secondary rounded-pill px-4 py-1-5 fw-semibold d-inline-flex align-items-center gap-1 opacity-75" disabled>
                            <span>Closed</span>
                        </button>
                    </td>
                </tr>
            </tbody>
        </table>
    </div>
</div>