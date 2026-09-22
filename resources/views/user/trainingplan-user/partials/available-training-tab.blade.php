{{-- Tab Training Tersedia / Available Training --}}
<div class="card border border-light-subtle shadow-sm rounded-4 bg-white p-4 mb-4">
    {{-- Quick Filter Bar --}}
    <div class="row g-3 align-items-center mb-4 pb-2 border-bottom border-light-subtle">
        <div class="col-lg-5 col-md-6">
            <div class="input-icon">
                <span class="input-icon-addon">
                    <i class="ti ti-search text-muted"></i>
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
        <div class="col-lg-2 col-md-3">
            <select id="filterTrainingStatus" class="form-select rounded-3">
                <option value="">All Status</option>
                <option value="open">Open Registration</option>
                <option value="registered">Registered</option>
            </select>
        </div>
        <div class="col-lg-2 col-md-12 d-flex justify-content-end">
            <button type="button" id="btnResetFilter" class="btn btn-outline-secondary rounded-3 w-100 d-inline-flex align-items-center justify-content-center gap-1">
                <i class="ti ti-rotate-clockwise"></i>
                <span>Reset</span>
            </button>
        </div>
    </div>

    {{-- Training Cards Grid --}}
    <div class="row g-4" id="availableTrainingGrid">
        {{-- Card 1 --}}
        <div class="col-lg-4 col-md-6 training-card-item" data-id="1" data-category="Soft Skill" data-status="open" data-title="Leadership Development Program">
            <div class="card h-100 border border-light-subtle shadow-sm rounded-4 overflow-hidden training-card-hover bg-white">
                <div class="position-relative">
                    <img src="{{ asset('tabler/static/photos/group-of-people-brainstorming-and-collaborating.jpg') }}" 
                         onerror="this.src='https://images.unsplash.com/photo-1552664730-d307ca884978?w=500&auto=format&fit=crop&q=60'"
                         class="card-img-top object-fit-cover" 
                         style="height: 180px;" 
                         alt="Leadership Development Program">
                    <span class="position-absolute top-0 start-0 m-3 badge bg-primary text-white rounded-pill px-3 py-1-5 shadow-sm">
                        Soft Skill
                    </span>
                    <span class="position-absolute top-0 end-0 m-3 badge bg-success text-white rounded-pill px-2 py-1 shadow-sm">
                        Open Registration
                    </span>
                </div>
                <div class="card-body p-4 d-flex flex-column">
                    <h4 class="fw-bold text-dark mb-2 line-clamp-2">Leadership Development Program</h4>
                    <p class="text-muted small mb-3 flex-grow-1 line-clamp-2">
                        Enhance managerial capabilities, team communication, strategic decision-making, and effective delegation.
                    </p>

                    <div class="d-flex flex-column gap-2 mb-4 p-3 bg-light-subtle rounded-3 border border-light-subtle small">
                        <div class="d-flex align-items-center text-muted">
                            <i class="ti ti-calendar me-2 text-primary"></i>
                            <span class="fw-semibold text-dark">18 Sep 2026, 08:00 - 16:00 WIB</span>
                        </div>
                        <div class="d-flex align-items-center text-muted">
                            <i class="ti ti-map-pin me-2 text-primary"></i>
                            <span>Training Room Building A, 2nd Fl.</span>
                        </div>
                        <div class="d-flex align-items-center text-muted">
                            <i class="ti ti-user-check me-2 text-primary"></i>
                            <span>Dr. Hendra Wijaya, M.M.</span>
                        </div>
                    </div>

                    <div class="mt-auto">
                        <button type="button" class="btn btn-primary w-100 rounded-3 py-2 fw-semibold d-inline-flex align-items-center justify-content-center gap-2" style="background-color: #003b6d; border-color: #003b6d;">
                            <i class="ti ti-user-plus"></i>
                            <span>Register</span>
                        </button>
                    </div>
                </div>
            </div>
        </div>

        {{-- Card 2 --}}
        <div class="col-lg-4 col-md-6 training-card-item" data-id="2" data-category="Technical Skill" data-status="registered" data-title="Excel Advanced for Data Analysis">
            <div class="card h-100 border border-light-subtle shadow-sm rounded-4 overflow-hidden training-card-hover bg-white">
                <div class="position-relative">
                    <img src="{{ asset('tabler/static/photos/working-in-a-coffee-shop.jpg') }}" 
                         onerror="this.src='https://images.unsplash.com/photo-1460925895917-afdab827c52f?w=500&auto=format&fit=crop&q=60'"
                         class="card-img-top object-fit-cover" 
                         style="height: 180px;" 
                         alt="Excel Advanced for Data Analysis">
                    <span class="position-absolute top-0 start-0 m-3 badge bg-info text-white rounded-pill px-3 py-1-5 shadow-sm">
                        Technical Skill
                    </span>
                    <span class="position-absolute top-0 end-0 m-3 badge bg-secondary text-white rounded-pill px-2 py-1 shadow-sm">
                        Registered
                    </span>
                </div>
                <div class="card-body p-4 d-flex flex-column">
                    <h4 class="fw-bold text-dark mb-2 line-clamp-2">Excel Advanced for Data Analysis</h4>
                    <p class="text-muted small mb-3 flex-grow-1 line-clamp-2">
                        Advanced formulas, XLOOKUP, PivotTables, Power Query integration, and automated dashboard visualization.
                    </p>

                    <div class="d-flex flex-column gap-2 mb-4 p-3 bg-light-subtle rounded-3 border border-light-subtle small">
                        <div class="d-flex align-items-center text-muted">
                            <i class="ti ti-calendar me-2 text-primary"></i>
                            <span class="fw-semibold text-dark">18 Sep 2026, 09:00 - 15:00 WIB</span>
                        </div>
                        <div class="d-flex align-items-center text-muted">
                            <i class="ti ti-map-pin me-2 text-primary"></i>
                            <span>Computer Lab 2, 3rd Fl.</span>
                        </div>
                        <div class="d-flex align-items-center text-muted">
                            <i class="ti ti-user-check me-2 text-primary"></i>
                            <span>Bambang Sujarwo, S.Kom.</span>
                        </div>
                    </div>

                    <div class="mt-auto">
                        <button type="button" class="btn btn-outline-success w-100 rounded-3 py-2 fw-semibold d-inline-flex align-items-center justify-content-center gap-2" disabled>
                            <i class="ti ti-circle-check"></i>
                            <span>Registered</span>
                        </button>
                    </div>
                </div>
            </div>
        </div>

        {{-- Card 3 --}}
        <div class="col-lg-4 col-md-6 training-card-item" data-id="3" data-category="Compliance & Safety" data-status="open" data-title="K3 & Safety Awareness in Factory">
            <div class="card h-100 border border-light-subtle shadow-sm rounded-4 overflow-hidden training-card-hover bg-white">
                <div class="position-relative">
                    <img src="{{ asset('tabler/static/photos/hard-working-in-office.jpg') }}" 
                         onerror="this.src='https://images.unsplash.com/photo-1581092160607-ee22621dd758?w=500&auto=format&fit=crop&q=60'"
                         class="card-img-top object-fit-cover" 
                         style="height: 180px;" 
                         alt="K3 & Safety Awareness in Factory">
                    <span class="position-absolute top-0 start-0 m-3 badge bg-warning text-dark rounded-pill px-3 py-1-5 shadow-sm">
                        Compliance & Safety
                    </span>
                    <span class="position-absolute top-0 end-0 m-3 badge bg-success text-white rounded-pill px-2 py-1 shadow-sm">
                        Open Registration
                    </span>
                </div>
                <div class="card-body p-4 d-flex flex-column">
                    <h4 class="fw-bold text-dark mb-2 line-clamp-2">K3 & Safety Awareness in Factory</h4>
                    <p class="text-muted small mb-3 flex-grow-1 line-clamp-2">
                        Factory occupational health and safety standards refresher, emergency procedures, and zero incident culture.
                    </p>

                    <div class="d-flex flex-column gap-2 mb-4 p-3 bg-light-subtle rounded-3 border border-light-subtle small">
                        <div class="d-flex align-items-center text-muted">
                            <i class="ti ti-calendar me-2 text-primary"></i>
                            <span class="fw-semibold text-dark">25 Sep 2026, 08:00 - 11:30 WIB</span>
                        </div>
                        <div class="d-flex align-items-center text-muted">
                            <i class="ti ti-map-pin me-2 text-primary"></i>
                            <span>Auditorium Krakatau Posco</span>
                        </div>
                        <div class="d-flex align-items-center text-muted">
                            <i class="ti ti-user-check me-2 text-primary"></i>
                            <span>Ir. Agus Priyono, CSP</span>
                        </div>
                    </div>

                    <div class="mt-auto">
                        <button type="button" class="btn btn-primary w-100 rounded-3 py-2 fw-semibold d-inline-flex align-items-center justify-content-center gap-2" style="background-color: #003b6d; border-color: #003b6d;">
                            <i class="ti ti-user-plus"></i>
                            <span>Register</span>
                        </button>
                    </div>
                </div>
            </div>
        </div>

        {{-- Card 4 --}}
        <div class="col-lg-4 col-md-6 training-card-item" data-id="4" data-category="Soft Skill" data-status="open" data-title="Effective Business Communication">
            <div class="card h-100 border border-light-subtle shadow-sm rounded-4 overflow-hidden training-card-hover bg-white">
                <div class="position-relative">
                    <img src="{{ asset('tabler/static/photos/modern-office-space.jpg') }}" 
                         onerror="this.src='https://images.unsplash.com/photo-1517245386807-bb43f82c33c4?w=500&auto=format&fit=crop&q=60'"
                         class="card-img-top object-fit-cover" 
                         style="height: 180px;" 
                         alt="Effective Business Communication">
                    <span class="position-absolute top-0 start-0 m-3 badge bg-teal text-white rounded-pill px-3 py-1-5 shadow-sm">
                        Soft Skill
                    </span>
                    <span class="position-absolute top-0 end-0 m-3 badge bg-success text-white rounded-pill px-2 py-1 shadow-sm">
                        Open Registration
                    </span>
                </div>
                <div class="card-body p-4 d-flex flex-column">
                    <h4 class="fw-bold text-dark mb-2 line-clamp-2">Effective Business Communication</h4>
                    <p class="text-muted small mb-3 flex-grow-1 line-clamp-2">
                        Master persuasive executive presentation, professional negotiation, and high-impact corporate writing skills.
                    </p>

                    <div class="d-flex flex-column gap-2 mb-4 p-3 bg-light-subtle rounded-3 border border-light-subtle small">
                        <div class="d-flex align-items-center text-muted">
                            <i class="ti ti-calendar me-2 text-primary"></i>
                            <span class="fw-semibold text-dark">29 Sep 2026, 13:00 - 17:00 WIB</span>
                        </div>
                        <div class="d-flex align-items-center text-muted">
                            <i class="ti ti-map-pin me-2 text-primary"></i>
                            <span>Meeting Room Bintang 1</span>
                        </div>
                        <div class="d-flex align-items-center text-muted">
                            <i class="ti ti-user-check me-2 text-primary"></i>
                            <span>Citra Lestari, M.Psi.</span>
                        </div>
                    </div>

                    <div class="mt-auto">
                        <button type="button" class="btn btn-primary w-100 rounded-3 py-2 fw-semibold d-inline-flex align-items-center justify-content-center gap-2" style="background-color: #003b6d; border-color: #003b6d;">
                            <i class="ti ti-user-plus"></i>
                            <span>Register</span>
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
