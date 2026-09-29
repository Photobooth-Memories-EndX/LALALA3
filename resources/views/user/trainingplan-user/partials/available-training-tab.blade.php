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
                <option value="closed">Closed Registration</option>
                <option value="registered">Registered</option>
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
        <table class="table table-vcenter card-table table-hover align-middle" id="availableTrainingTable">
            <thead>
                <tr class="text-muted extra-small text-uppercase fw-bold" style="letter-spacing: 0.05em; background-color: #f8fafc;">
                    <th class="w-1 text-center py-3">NO</th>
                    <th class="py-3">TRAINING NAME</th>
                    <th class="py-3">CATEGORY</th>
                    <th class="py-3">DATE</th>
                    <th class="py-3">TIME</th>
                    <th class="py-3">LOCATION</th>
                    <th class="py-3 text-center">APPROVED PARTICIPANT</th>
                    <th class="py-3 text-center">STATUS</th>
                    <th class="text-end py-3 pe-4">ACTION</th>
                </tr>
            </thead>
            <tbody class="divide-y">
                {{-- Row 1 --}}
                <tr class="training-table-row" data-id="1" data-category="Soft Skill" data-status="open" data-instructor="Dr. Hendra Wijaya, M.M.">
                    <td class="text-center text-muted fw-semibold">1</td>
                    <td>
                        <div class="d-flex align-items-center gap-3">
                            <div class="rounded-3 d-flex align-items-center justify-content-center flex-shrink-0" style="width: 40px; height: 40px; background-color: #eef2ff; color: #003b6d;">
                                <i class="ti ti-school fs-2"></i>
                            </div>
                            <div class="fw-bold text-dark fs-3">Leadership Development Program</div>
                        </div>
                    </td>
                    <td>
                        <span class="badge rounded-pill px-3 py-1-5 fw-semibold" style="background-color: #e0f2fe; color: #0369a1;">
                            Soft Skill
                        </span>
                    </td>
                    <td>
                        <div class="fw-semibold text-dark">18 Sep 2026</div>
                    </td>
                    <td>
                        <div class="text-muted small">08:00 - 16:00 WIB</div>
                    </td>
                    <td>
                        <div class="d-flex align-items-center gap-1 text-muted small">
                            <i class="ti ti-map-pin text-primary fs-2"></i>
                            <span>Training Room Building A, 2nd Fl.</span>
                        </div>
                    </td>
                    <td class="text-center" style="min-width: 130px;">
                        <div class="fw-semibold text-dark small">25 / 30 Seats</div>
                        <div class="progress progress-xs mt-1 mx-auto rounded-pill" style="height: 4px; max-width: 90px; background-color: #e2e8f0;">
                            <div class="progress-bar rounded-pill" role="progressbar" style="width: 83%; background-color: #003b6d;" aria-valuenow="83" aria-valuemin="0" aria-valuemax="100"></div>
                        </div>
                    </td>
                    <td class="text-center">
                        <span class="badge bg-success-subtle text-success rounded-pill px-3 py-1 fw-semibold">
                            Open Registration
                        </span>
                    </td>
                    <td class="text-end pe-4">
                        <button type="button" class="btn btn-owl-register rounded-pill px-4 py-1-5 fw-semibold d-inline-flex align-items-center gap-1">
                            <span>Register</span>
                        </button>
                    </td>
                </tr>

                {{-- Row 2 --}}
                <tr class="training-table-row" data-id="2" data-category="Technical Skill" data-status="open" data-instructor="Bambang Sujarwo, S.Kom.">
                    <td class="text-center text-muted fw-semibold">2</td>
                    <td>
                        <div class="d-flex align-items-center gap-3">
                            <div class="rounded-3 d-flex align-items-center justify-content-center flex-shrink-0" style="width: 40px; height: 40px; background-color: #e0f2fe; color: #0284c7;">
                                <i class="ti ti-file-spreadsheet fs-2"></i>
                            </div>
                            <div class="fw-bold text-dark fs-3">Excel Advanced for Data Analysis</div>
                        </div>
                    </td>
                    <td>
                        <span class="badge rounded-pill px-3 py-1-5 fw-semibold" style="background-color: #e0f2fe; color: #0284c7;">
                            Technical Skill
                        </span>
                    </td>
                    <td>
                        <div class="fw-semibold text-dark">18 Sep 2026</div>
                    </td>
                    <td>
                        <div class="text-muted small">09:00 - 15:00 WIB</div>
                    </td>
                    <td>
                        <div class="d-flex align-items-center gap-1 text-muted small">
                            <i class="ti ti-map-pin text-primary fs-2"></i>
                            <span>Computer Lab 2, 3rd Fl.</span>
                        </div>
                    </td>
                    <td class="text-center" style="min-width: 130px;">
                        <div class="fw-semibold text-dark small">18 / 20 Seats</div>
                        <div class="progress progress-xs mt-1 mx-auto rounded-pill" style="height: 4px; max-width: 90px; background-color: #e2e8f0;">
                            <div class="progress-bar rounded-pill" role="progressbar" style="width: 90%; background-color: #0284c7;" aria-valuenow="90" aria-valuemin="0" aria-valuemax="100"></div>
                        </div>
                    </td>
                    <td class="text-center">
                        <span class="badge bg-success-subtle text-success rounded-pill px-3 py-1 fw-semibold">
                            Open Registration
                        </span>
                    </td>
                    <td class="text-end pe-4">
                        <button type="button" class="btn btn-owl-register rounded-pill px-4 py-1-5 fw-semibold d-inline-flex align-items-center gap-1">
                            <span>Register</span>
                        </button>
                    </td>
                </tr>

                {{-- Row 3 --}}
                <tr class="training-table-row" data-id="3" data-category="Compliance & Safety" data-status="open" data-instructor="Ir. Agus Priyono, CSP">
                    <td class="text-center text-muted fw-semibold">3</td>
                    <td>
                        <div class="d-flex align-items-center gap-3">
                            <div class="rounded-3 d-flex align-items-center justify-content-center flex-shrink-0" style="width: 40px; height: 40px; background-color: #fef3c7; color: #d97706;">
                                <i class="ti ti-shield-check fs-2"></i>
                            </div>
                            <div class="fw-bold text-dark fs-3">K3 & Safety Awareness in Factory</div>
                        </div>
                    </td>
                    <td>
                        <span class="badge rounded-pill px-3 py-1-5 fw-semibold" style="background-color: #fef3c7; color: #b45309;">
                            Compliance & Safety
                        </span>
                    </td>
                    <td>
                        <div class="fw-semibold text-dark">25 Sep 2026</div>
                    </td>
                    <td>
                        <div class="text-muted small">08:00 - 11:30 WIB</div>
                    </td>
                    <td>
                        <div class="d-flex align-items-center gap-1 text-muted small">
                            <i class="ti ti-map-pin text-primary fs-2"></i>
                            <span>Auditorium Krakatau Posco</span>
                        </div>
                    </td>
                    <td class="text-center" style="min-width: 130px;">
                        <div class="fw-semibold text-dark small">40 / 50 Seats</div>
                        <div class="progress progress-xs mt-1 mx-auto rounded-pill" style="height: 4px; max-width: 90px; background-color: #e2e8f0;">
                            <div class="progress-bar rounded-pill" role="progressbar" style="width: 80%; background-color: #d97706;" aria-valuenow="80" aria-valuemin="0" aria-valuemax="100"></div>
                        </div>
                    </td>
                    <td class="text-center">
                        <span class="badge bg-success-subtle text-success rounded-pill px-3 py-1 fw-semibold">
                            Open Registration
                        </span>
                    </td>
                    <td class="text-end pe-4">
                        <button type="button" class="btn btn-owl-register rounded-pill px-4 py-1-5 fw-semibold d-inline-flex align-items-center gap-1">
                            <span>Register</span>
                        </button>
                    </td>
                </tr>

                {{-- Row 4 --}}
                <tr class="training-table-row" data-id="4" data-category="Soft Skill" data-status="closed" data-instructor="Citra Lestari, M.Psi.">
                    <td class="text-center text-muted fw-semibold">4</td>
                    <td>
                        <div class="d-flex align-items-center gap-3">
                            <div class="rounded-3 d-flex align-items-center justify-content-center flex-shrink-0" style="width: 40px; height: 40px; background-color: #f1f5f9; color: #64748b;">
                                <i class="ti ti-message-dots fs-2"></i>
                            </div>
                            <div class="fw-bold text-dark fs-3">Effective Business Communication</div>
                        </div>
                    </td>
                    <td>
                        <span class="badge rounded-pill px-3 py-1-5 fw-semibold" style="background-color: #e0f2fe; color: #0369a1;">
                            Soft Skill
                        </span>
                    </td>
                    <td>
                        <div class="fw-semibold text-dark">29 Sep 2026</div>
                    </td>
                    <td>
                        <div class="text-muted small">13:00 - 17:00 WIB</div>
                    </td>
                    <td>
                        <div class="d-flex align-items-center gap-1 text-muted small">
                            <i class="ti ti-map-pin text-primary fs-2"></i>
                            <span>Meeting Room Bintang 1</span>
                        </div>
                    </td>
                    <td class="text-center" style="min-width: 130px;">
                        <div class="fw-semibold text-dark small">25 / 25 Seats (Full)</div>
                        <div class="progress progress-xs mt-1 mx-auto rounded-pill" style="height: 4px; max-width: 90px; background-color: #e2e8f0;">
                            <div class="progress-bar rounded-pill" role="progressbar" style="width: 100%; background-color: #64748b;" aria-valuenow="100" aria-valuemin="0" aria-valuemax="100"></div>
                        </div>
                    </td>
                    <td class="text-center">
                        <span class="badge bg-danger-subtle text-danger rounded-pill px-3 py-1 fw-semibold">
                            Closed
                        </span>
                    </td>
                    <td class="text-end pe-4">
                        <button type="button" class="btn btn-secondary rounded-pill px-4 py-1-5 fw-semibold d-inline-flex align-items-center gap-1 opacity-75" disabled>
                            <span>Closed</span>
                        </button>
                    </td>
                </tr>
            </tbody>
        </table>
    </div>
</div>