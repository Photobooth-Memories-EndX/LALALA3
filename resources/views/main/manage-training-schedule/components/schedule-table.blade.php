<div class="card border-0 shadow-sm rounded-4 bg-white overflow-hidden">
    <div class="table-responsive">
        <table class="table table-vcenter card-table table-hover align-middle mb-0" id="manageScheduleTable">
            <thead>
                <tr class="text-muted extra-small text-uppercase fw-bold text-center align-middle" style="letter-spacing: 0.05em; background-color: #f8fafc;">
                    <th class="w-1 py-3">NO</th>
                    <th class="py-3" style="min-width: 200px;">TRAINING TITLE</th>
                    <th class="py-3">CATEGORY</th>
                    <th class="py-3" style="min-width: 110px;">DATE</th>
                    <th class="py-3" style="min-width: 120px;">TIME</th>
                    <th class="py-3">LOCATION</th>
                    <th class="py-3">INSTRUCTOR</th>
                    <th class="py-3">QUOTA / SEATS</th>
                    <th class="py-3">STATUS</th>
                    <th class="py-3">ACTION</th>
                </tr>
            </thead>
            <tbody class="divide-y" id="scheduleTableBody">
                {{-- Row 1 --}}
                <tr class="schedule-table-row text-center align-middle" data-id="1" data-category="Soft Skill" data-status="open" data-year="2026" data-date="2026-09-18" data-instructor="Dr. Hendra Wijaya, M.M." data-location="Training Room Building A, 2nd Fl.">
                    <td class="text-muted fw-semibold schedule-row-index">1</td>
                    <td class="fw-bold text-dark text-start" style="min-width: 200px;">
                        <div class="fs-3 training-title-text">Leadership Development Program</div>
                        <div class="text-muted extra-small font-monospace">Code: PTKP-LDP-01 &bull; Batch 1 / 2026</div>
                    </td>
                    <td>
                        <span class="badge rounded-pill px-3 py-1-5 fw-semibold" style="background-color: #e0f2fe; color: #0369a1;">
                            Soft Skill
                        </span>
                    </td>
                    <td>
                        <div class="fw-semibold text-dark" style="min-width: 110px;">18 Sep 2026</div>
                    </td>
                    <td>
                        <div class="text-muted small" style="min-width: 120px;">08:00 - 16:00 WIB</div>
                    </td>
                    <td class="text-muted small">
                        Training Room Building A, 2nd Fl.
                    </td>
                    <td class="text-muted small">
                        <div class="fw-semibold text-dark">Dr. Hendra Wijaya, M.M.</div>
                        <div class="text-muted extra-small">Lead Leadership Consultant</div>
                    </td>
                    <td>
                        <div class="fw-semibold text-dark small">25 / 30 Seats</div>
                        <div class="progress progress-xs mt-1 mx-auto rounded-pill" style="height: 4px; max-width: 90px; background-color: #e2e8f0;">
                            <div class="progress-bar rounded-pill" role="progressbar" style="width: 83.3%; background-color: #f97316;"></div>
                        </div>
                    </td>
                    <td>
                        <span class="badge bg-success-subtle text-success rounded-pill px-3 py-1 fw-semibold">
                            Open Registration
                        </span>
                    </td>
                    <td>
                        <div class="d-flex align-items-center justify-content-center gap-1">
                            <button type="button" class="btn btn-sm btn-icon btn-outline-secondary rounded-3 btn-view-syllabus" 
                                    title="View Syllabus" data-bs-toggle="modal" data-bs-target="#syllabusModal" 
                                    data-id="1" data-title="Leadership Development Program" data-category="Soft Skill" data-instructor="Dr. Hendra Wijaya, M.M.">
                                <i class="ti ti-list-check fs-2"></i>
                            </button>
                            <button type="button" class="btn btn-sm btn-icon btn-outline-primary rounded-3 btn-edit-schedule" 
                                    title="Edit Schedule" data-id="1" data-title="Leadership Development Program">
                                <i class="ti ti-edit fs-2"></i>
                            </button>
                            <button type="button" class="btn btn-sm btn-icon btn-outline-info rounded-3 btn-participants-schedule" 
                                    title="View Participants" 
                                    data-id="1" data-title="Leadership Development Program">
                                <i class="ti ti-users fs-2"></i>
                            </button>
                            <button type="button" class="btn btn-sm btn-icon btn-outline-danger rounded-3 btn-delete-schedule" 
                                    title="Delete Schedule" data-id="1" data-title="Leadership Development Program">
                                <i class="ti ti-trash fs-2"></i>
                            </button>
                        </div>
                    </td>
                </tr>

                {{-- Row 2 --}}
                <tr class="schedule-table-row text-center align-middle" data-id="2" data-category="Technical Skill" data-status="open" data-year="2026" data-date="2026-09-18" data-instructor="Bambang Sujarwo, S.Kom." data-location="Computer Lab 2, 3rd Fl.">
                    <td class="text-muted fw-semibold schedule-row-index">2</td>
                    <td class="fw-bold text-dark text-start" style="min-width: 200px;">
                        <div class="fs-3 training-title-text">Excel Advanced for Data Analysis</div>
                        <div class="text-muted extra-small font-monospace">Code: PTKP-EXC-02 &bull; Batch 2 / 2026</div>
                    </td>
                    <td>
                        <span class="badge rounded-pill px-3 py-1-5 fw-semibold" style="background-color: #e0f2fe; color: #0284c7;">
                            Technical Skill
                        </span>
                    </td>
                    <td>
                        <div class="fw-semibold text-dark" style="min-width: 110px;">18 Sep 2026</div>
                    </td>
                    <td>
                        <div class="text-muted small" style="min-width: 120px;">09:00 - 15:00 WIB</div>
                    </td>
                    <td class="text-muted small">
                        Computer Lab 2, 3rd Fl.
                    </td>
                    <td class="text-muted small">
                        <div class="fw-semibold text-dark">Bambang Sujarwo, S.Kom.</div>
                        <div class="text-muted extra-small">Senior Data Analyst</div>
                    </td>
                    <td>
                        <div class="fw-semibold text-dark small">18 / 20 Seats</div>
                        <div class="progress progress-xs mt-1 mx-auto rounded-pill" style="height: 4px; max-width: 90px; background-color: #e2e8f0;">
                            <div class="progress-bar rounded-pill" role="progressbar" style="width: 90%; background-color: #f97316;"></div>
                        </div>
                    </td>
                    <td>
                        <span class="badge bg-success-subtle text-success rounded-pill px-3 py-1 fw-semibold">
                            Open Registration
                        </span>
                    </td>
                    <td>
                        <div class="d-flex align-items-center justify-content-center gap-1">
                            <button type="button" class="btn btn-sm btn-icon btn-outline-secondary rounded-3 btn-view-syllabus" 
                                    title="View Syllabus" data-bs-toggle="modal" data-bs-target="#syllabusModal" 
                                    data-id="2" data-title="Excel Advanced for Data Analysis" data-category="Technical Skill" data-instructor="Bambang Sujarwo, S.Kom.">
                                <i class="ti ti-list-check fs-2"></i>
                            </button>
                            <button type="button" class="btn btn-sm btn-icon btn-outline-primary rounded-3 btn-edit-schedule" 
                                    title="Edit Schedule" data-id="2" data-title="Excel Advanced for Data Analysis">
                                <i class="ti ti-edit fs-2"></i>
                            </button>
                            <button type="button" class="btn btn-sm btn-icon btn-outline-info rounded-3 btn-participants-schedule" 
                                    title="View Participants" 
                                    data-id="2" data-title="Excel Advanced for Data Analysis">
                                <i class="ti ti-users fs-2"></i>
                            </button>
                            <button type="button" class="btn btn-sm btn-icon btn-outline-danger rounded-3 btn-delete-schedule" 
                                    title="Delete Schedule" data-id="2" data-title="Excel Advanced for Data Analysis">
                                <i class="ti ti-trash fs-2"></i>
                            </button>
                        </div>
                    </td>
                </tr>

                {{-- Row 3 --}}
                <tr class="schedule-table-row text-center align-middle" data-id="3" data-category="Compliance & Safety" data-status="open" data-year="2026" data-date="2026-09-25" data-instructor="Ir. Agus Priyono, CSP" data-location="Auditorium Krakatau Posco">
                    <td class="text-muted fw-semibold schedule-row-index">3</td>
                    <td class="fw-bold text-dark text-start" style="min-width: 200px;">
                        <div class="fs-3 training-title-text">K3 & Safety Awareness in Factory</div>
                        <div class="text-muted extra-small font-monospace">Code: PTKP-SAF-03 &bull; Batch 3 / 2026</div>
                    </td>
                    <td>
                        <span class="badge rounded-pill px-3 py-1-5 fw-semibold" style="background-color: #fef3c7; color: #b45309;">
                            Compliance & Safety
                        </span>
                    </td>
                    <td>
                        <div class="fw-semibold text-dark" style="min-width: 110px;">25 Sep 2026</div>
                    </td>
                    <td>
                        <div class="text-muted small" style="min-width: 120px;">08:00 - 11:30 WIB</div>
                    </td>
                    <td class="text-muted small">
                        Auditorium Krakatau Posco
                    </td>
                    <td class="text-muted small">
                        <div class="fw-semibold text-dark">Ir. Agus Priyono, CSP</div>
                        <div class="text-muted extra-small">HSE Expert Krakatau Posco</div>
                    </td>
                    <td>
                        <div class="fw-semibold text-dark small">40 / 50 Seats</div>
                        <div class="progress progress-xs mt-1 mx-auto rounded-pill" style="height: 4px; max-width: 90px; background-color: #e2e8f0;">
                            <div class="progress-bar rounded-pill" role="progressbar" style="width: 80%; background-color: #f97316;"></div>
                        </div>
                    </td>
                    <td>
                        <span class="badge bg-success-subtle text-success rounded-pill px-3 py-1 fw-semibold">
                            Open Registration
                        </span>
                    </td>
                    <td>
                        <div class="d-flex align-items-center justify-content-center gap-1">
                            <button type="button" class="btn btn-sm btn-icon btn-outline-secondary rounded-3 btn-view-syllabus" 
                                    title="View Syllabus" data-bs-toggle="modal" data-bs-target="#syllabusModal" 
                                    data-id="3" data-title="K3 & Safety Awareness in Factory" data-category="Compliance & Safety" data-instructor="Ir. Agus Priyono, CSP">
                                <i class="ti ti-list-check fs-2"></i>
                            </button>
                            <button type="button" class="btn btn-sm btn-icon btn-outline-primary rounded-3 btn-edit-schedule" 
                                    title="Edit Schedule" data-id="3" data-title="K3 & Safety Awareness in Factory">
                                <i class="ti ti-edit fs-2"></i>
                            </button>
                            <button type="button" class="btn btn-sm btn-icon btn-outline-info rounded-3 btn-participants-schedule" 
                                    title="View Participants" 
                                    data-id="3" data-title="K3 & Safety Awareness in Factory">
                                <i class="ti ti-users fs-2"></i>
                            </button>
                            <button type="button" class="btn btn-sm btn-icon btn-outline-danger rounded-3 btn-delete-schedule" 
                                    title="Delete Schedule" data-id="3" data-title="K3 & Safety Awareness in Factory">
                                <i class="ti ti-trash fs-2"></i>
                            </button>
                        </div>
                    </td>
                </tr>

                {{-- Row 4 --}}
                <tr class="schedule-table-row text-center align-middle" data-id="4" data-category="Soft Skill" data-status="closed" data-year="2026" data-date="2026-09-29" data-instructor="Citra Lestari, M.Psi." data-location="Meeting Room Bintang 1">
                    <td class="text-muted fw-semibold schedule-row-index">4</td>
                    <td class="fw-bold text-dark text-start" style="min-width: 200px;">
                        <div class="fs-3 training-title-text">Effective Business Communication</div>
                        <div class="text-muted extra-small font-monospace">Code: PTKP-COMM-04 &bull; Batch 1 / 2026</div>
                    </td>
                    <td>
                        <span class="badge rounded-pill px-3 py-1-5 fw-semibold" style="background-color: #e0f2fe; color: #0369a1;">
                            Soft Skill
                        </span>
                    </td>
                    <td>
                        <div class="fw-semibold text-dark" style="min-width: 110px;">29 Sep 2026</div>
                    </td>
                    <td>
                        <div class="text-muted small" style="min-width: 120px;">13:00 - 17:00 WIB</div>
                    </td>
                    <td class="text-muted small">
                        Meeting Room Bintang 1
                    </td>
                    <td class="text-muted small">
                        <div class="fw-semibold text-dark">Citra Lestari, M.Psi.</div>
                        <div class="text-muted extra-small">Corporate HR Practitioner</div>
                    </td>
                    <td>
                        <div class="fw-semibold text-dark small">25 / 25 Seats (Full)</div>
                        <div class="progress progress-xs mt-1 mx-auto rounded-pill" style="height: 4px; max-width: 90px; background-color: #e2e8f0;">
                            <div class="progress-bar rounded-pill" role="progressbar" style="width: 100%; background-color: #ef4444;"></div>
                        </div>
                    </td>
                    <td>
                        <span class="badge bg-secondary-subtle text-secondary rounded-pill px-3 py-1 fw-semibold">
                            Closed Registration
                        </span>
                    </td>
                    <td>
                        <div class="d-flex align-items-center justify-content-center gap-1">
                            <button type="button" class="btn btn-sm btn-icon btn-outline-secondary rounded-3 btn-view-syllabus" 
                                    title="View Syllabus" data-bs-toggle="modal" data-bs-target="#syllabusModal" 
                                    data-id="4" data-title="Effective Business Communication" data-category="Soft Skill" data-instructor="Citra Lestari, M.Psi.">
                                <i class="ti ti-list-check fs-2"></i>
                            </button>
                            <button type="button" class="btn btn-sm btn-icon btn-outline-primary rounded-3 btn-edit-schedule" 
                                    title="Edit Schedule" data-id="4" data-title="Effective Business Communication">
                                <i class="ti ti-edit fs-2"></i>
                            </button>
                            <button type="button" class="btn btn-sm btn-icon btn-outline-info rounded-3 btn-participants-schedule" 
                                    title="View Participants" 
                                    data-id="4" data-title="Effective Business Communication">
                                <i class="ti ti-users fs-2"></i>
                            </button>
                            <button type="button" class="btn btn-sm btn-icon btn-outline-danger rounded-3 btn-delete-schedule" 
                                    title="Delete Schedule" data-id="4" data-title="Effective Business Communication">
                                <i class="ti ti-trash fs-2"></i>
                            </button>
                        </div>
                    </td>
                </tr>

                {{-- Row 5 --}}
                <tr class="schedule-table-row text-center align-middle" data-id="5" data-category="Compliance & Safety" data-status="completed" data-year="2025" data-date="2025-08-14" data-instructor="HSE Team Krakatau Posco" data-location="HSE Training Ground">
                    <td class="text-muted fw-semibold schedule-row-index">5</td>
                    <td class="fw-bold text-dark text-start" style="min-width: 200px;">
                        <div class="fs-3 training-title-text">Basic Fire Fighting & Safety Protocol</div>
                        <div class="text-muted extra-small font-monospace">Code: PTKP-BFF-05 &bull; Batch 4 / 2025</div>
                    </td>
                    <td>
                        <span class="badge rounded-pill px-3 py-1-5 fw-semibold" style="background-color: #fef3c7; color: #b45309;">
                            Compliance & Safety
                        </span>
                    </td>
                    <td>
                        <div class="fw-semibold text-dark" style="min-width: 110px;">14 Aug 2025</div>
                    </td>
                    <td>
                        <div class="text-muted small" style="min-width: 120px;">08:00 - 12:00 WIB</div>
                    </td>
                    <td class="text-muted small">
                        HSE Training Ground
                    </td>
                    <td class="text-muted small">
                        <div class="fw-semibold text-dark">HSE Team Krakatau Posco</div>
                        <div class="text-muted extra-small">Emergency Response Unit</div>
                    </td>
                    <td>
                        <div class="fw-semibold text-dark small">40 / 40 Seats (Full)</div>
                        <div class="progress progress-xs mt-1 mx-auto rounded-pill" style="height: 4px; max-width: 90px; background-color: #e2e8f0;">
                            <div class="progress-bar rounded-pill" role="progressbar" style="width: 100%; background-color: #ef4444;"></div>
                        </div>
                    </td>
                    <td>
                        <span class="badge bg-info-subtle text-info rounded-pill px-3 py-1 fw-semibold">
                            Completed
                        </span>
                    </td>
                    <td>
                        <div class="d-flex align-items-center justify-content-center gap-1">
                            <button type="button" class="btn btn-sm btn-icon btn-outline-secondary rounded-3 btn-view-syllabus" 
                                    title="View Syllabus" data-bs-toggle="modal" data-bs-target="#syllabusModal" 
                                    data-id="5" data-title="Basic Fire Fighting & Safety Protocol" data-category="Compliance & Safety" data-instructor="HSE Team Krakatau Posco">
                                <i class="ti ti-list-check fs-2"></i>
                            </button>
                            <button type="button" class="btn btn-sm btn-icon btn-outline-primary rounded-3 btn-edit-schedule" 
                                    title="Edit Schedule" data-id="5" data-title="Basic Fire Fighting & Safety Protocol">
                                <i class="ti ti-edit fs-2"></i>
                            </button>
                            <button type="button" class="btn btn-sm btn-icon btn-outline-info rounded-3 btn-participants-schedule" 
                                    title="View Participants" 
                                    data-id="5" data-title="Basic Fire Fighting & Safety Protocol">
                                <i class="ti ti-users fs-2"></i>
                            </button>
                            <button type="button" class="btn btn-sm btn-icon btn-outline-danger rounded-3 btn-delete-schedule" 
                                    title="Delete Schedule" data-id="5" data-title="Basic Fire Fighting & Safety Protocol">
                                <i class="ti ti-trash fs-2"></i>
                            </button>
                        </div>
                    </td>
                </tr>
            </tbody>
        </table>
    </div>

    <!-- Empty State for Table Filtering -->
    <div class="p-5 text-center d-none" id="scheduleTableEmpty">
        <div class="avatar avatar-xl rounded-circle bg-light text-muted mb-3 mx-auto">
            <i class="ti ti-calendar-off fs-1"></i>
        </div>
        <h4 class="fw-bold text-dark mb-1">No Training Schedules Found</h4>
        <p class="text-muted small mb-0">No PTKP training schedules match the selected filters. Try changing or clearing your filter criteria.</p>
    </div>
</div>
