<div class="card border-0 shadow-sm rounded-4 bg-white overflow-hidden">
    <div class="table-responsive">
        <table class="table table-vcenter card-table table-hover align-middle mb-0" id="manageScheduleTable">
            <thead>
                <tr class="text-muted extra-small text-uppercase fw-bold" style="letter-spacing: 0.05em; background-color: #f8fafc;">
                    <th class="w-1 text-center py-3">NO</th>
                    <th class="py-3">TRAINING TITLE</th>
                    <th class="py-3">CATEGORY</th>
                    <th class="py-3">DATE & TIME</th>
                    <th class="py-3">LOCATION</th>
                    <th class="py-3">INSTRUCTOR</th>
                    <th class="py-3 text-center">QUOTA / SEATS</th>
                    <th class="py-3 text-center">STATUS</th>
                    <th class="text-end py-3 pe-4">ACTION</th>
                </tr>
            </thead>
            <tbody class="divide-y" id="scheduleTableBody">
                {{-- Row 1 --}}
                <tr class="schedule-table-row" data-id="1" data-category="Soft Skill" data-status="open" data-year="2026" data-date="2026-09-18" data-instructor="Dr. Hendra Wijaya, M.M." data-location="Training Room Building A, 2nd Fl.">
                    <td class="text-center text-muted fw-semibold schedule-row-index">1</td>
                    <td>
                        <div class="d-flex align-items-center gap-3">
                            <div class="rounded-3 d-flex align-items-center justify-content-center flex-shrink-0" 
                                 style="width: 40px; height: 40px; background-color: #eef2ff; color: #003b6d;">
                                <i class="ti ti-school fs-2"></i>
                            </div>
                            <div>
                                <div class="fw-bold text-dark fs-3 training-title-text">Leadership Development Program</div>
                                <div class="text-muted extra-small">
                                    <span>Code: PTKP-LDP-01</span> &bull; <span>Batch 1 / 2026</span>
                                </div>
                            </div>
                        </div>
                    </td>
                    <td>
                        <span class="badge rounded-pill px-3 py-1 fw-semibold" style="background-color: #e0f2fe; color: #0369a1;">
                            Soft Skill
                        </span>
                    </td>
                    <td>
                        <div class="fw-semibold text-dark">18 Sep 2026</div>
                        <div class="text-muted extra-small">08:00 - 16:00 WIB</div>
                    </td>
                    <td>
                        <div class="d-flex align-items-center gap-1 text-muted small">
                            <i class="ti ti-map-pin text-primary fs-2"></i>
                            <span>Training Room Building A, 2nd Fl.</span>
                        </div>
                    </td>
                    <td>
                        <div class="fw-semibold text-dark small">Dr. Hendra Wijaya, M.M.</div>
                        <div class="text-muted extra-small">Lead Leadership Consultant</div>
                    </td>
                    <td class="text-center">
                        <span class="fw-semibold text-dark small">25 / 30 Seats</span>
                        <div class="progress progress-xs mt-1" style="height: 4px;">
                            <div class="progress-bar bg-primary" style="width: 83.3%;"></div>
                        </div>
                    </td>
                    <td class="text-center">
                        <span class="badge bg-success-subtle text-success rounded-pill px-3 py-1 fw-semibold">
                            Open Registration
                        </span>
                    </td>
                    <td class="text-end pe-4">
                        <div class="d-inline-flex align-items-center gap-1">
                            <button type="button" class="btn btn-sm btn-icon btn-outline-primary rounded-3 btn-edit-schedule" 
                                    title="Edit Schedule" data-id="1" data-title="Leadership Development Program">
                                <i class="ti ti-edit fs-2"></i>
                            </button>
                            <button type="button" class="btn btn-sm btn-icon btn-outline-info rounded-3 btn-participants-schedule" 
                                    title="View Participants" data-id="1" data-title="Leadership Development Program">
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
                <tr class="schedule-table-row" data-id="2" data-category="Technical Skill" data-status="open" data-year="2026" data-date="2026-09-18" data-instructor="Bambang Sujarwo, S.Kom." data-location="Computer Lab 2, 3rd Fl.">
                    <td class="text-center text-muted fw-semibold schedule-row-index">2</td>
                    <td>
                        <div class="d-flex align-items-center gap-3">
                            <div class="rounded-3 d-flex align-items-center justify-content-center flex-shrink-0" 
                                 style="width: 40px; height: 40px; background-color: #e0f2fe; color: #0284c7;">
                                <i class="ti ti-file-spreadsheet fs-2"></i>
                            </div>
                            <div>
                                <div class="fw-bold text-dark fs-3 training-title-text">Excel Advanced for Data Analysis</div>
                                <div class="text-muted extra-small">
                                    <span>Code: PTKP-EXC-02</span> &bull; <span>Batch 2 / 2026</span>
                                </div>
                            </div>
                        </div>
                    </td>
                    <td>
                        <span class="badge rounded-pill px-3 py-1 fw-semibold" style="background-color: #e0f2fe; color: #0284c7;">
                            Technical Skill
                        </span>
                    </td>
                    <td>
                        <div class="fw-semibold text-dark">18 Sep 2026</div>
                        <div class="text-muted extra-small">09:00 - 15:00 WIB</div>
                    </td>
                    <td>
                        <div class="d-flex align-items-center gap-1 text-muted small">
                            <i class="ti ti-map-pin text-primary fs-2"></i>
                            <span>Computer Lab 2, 3rd Fl.</span>
                        </div>
                    </td>
                    <td>
                        <div class="fw-semibold text-dark small">Bambang Sujarwo, S.Kom.</div>
                        <div class="text-muted extra-small">Senior Data Analyst</div>
                    </td>
                    <td class="text-center">
                        <span class="fw-semibold text-dark small">18 / 20 Seats</span>
                        <div class="progress progress-xs mt-1" style="height: 4px;">
                            <div class="progress-bar bg-primary" style="width: 90%;"></div>
                        </div>
                    </td>
                    <td class="text-center">
                        <span class="badge bg-success-subtle text-success rounded-pill px-3 py-1 fw-semibold">
                            Open Registration
                        </span>
                    </td>
                    <td class="text-end pe-4">
                        <div class="d-inline-flex align-items-center gap-1">
                            <button type="button" class="btn btn-sm btn-icon btn-outline-primary rounded-3 btn-edit-schedule" 
                                    title="Edit Schedule" data-id="2" data-title="Excel Advanced for Data Analysis">
                                <i class="ti ti-edit fs-2"></i>
                            </button>
                            <button type="button" class="btn btn-sm btn-icon btn-outline-info rounded-3 btn-participants-schedule" 
                                    title="View Participants" data-id="2" data-title="Excel Advanced for Data Analysis">
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
                <tr class="schedule-table-row" data-id="3" data-category="Compliance & Safety" data-status="open" data-year="2026" data-date="2026-09-25" data-instructor="Ir. Agus Priyono, CSP" data-location="Auditorium Krakatau Posco">
                    <td class="text-center text-muted fw-semibold schedule-row-index">3</td>
                    <td>
                        <div class="d-flex align-items-center gap-3">
                            <div class="rounded-3 d-flex align-items-center justify-content-center flex-shrink-0" 
                                 style="width: 40px; height: 40px; background-color: #fef3c7; color: #d97706;">
                                <i class="ti ti-shield-check fs-2"></i>
                            </div>
                            <div>
                                <div class="fw-bold text-dark fs-3 training-title-text">K3 & Safety Awareness in Factory</div>
                                <div class="text-muted extra-small">
                                    <span>Code: PTKP-SAF-03</span> &bull; <span>Batch 3 / 2026</span>
                                </div>
                            </div>
                        </div>
                    </td>
                    <td>
                        <span class="badge rounded-pill px-3 py-1 fw-semibold" style="background-color: #fef3c7; color: #b45309;">
                            Compliance & Safety
                        </span>
                    </td>
                    <td>
                        <div class="fw-semibold text-dark">25 Sep 2026</div>
                        <div class="text-muted extra-small">08:00 - 11:30 WIB</div>
                    </td>
                    <td>
                        <div class="d-flex align-items-center gap-1 text-muted small">
                            <i class="ti ti-map-pin text-primary fs-2"></i>
                            <span>Auditorium Krakatau Posco</span>
                        </div>
                    </td>
                    <td>
                        <div class="fw-semibold text-dark small">Ir. Agus Priyono, CSP</div>
                        <div class="text-muted extra-small">HSE Expert Krakatau Posco</div>
                    </td>
                    <td class="text-center">
                        <span class="fw-semibold text-dark small">40 / 50 Seats</span>
                        <div class="progress progress-xs mt-1" style="height: 4px;">
                            <div class="progress-bar bg-warning" style="width: 80%;"></div>
                        </div>
                    </td>
                    <td class="text-center">
                        <span class="badge bg-success-subtle text-success rounded-pill px-3 py-1 fw-semibold">
                            Open Registration
                        </span>
                    </td>
                    <td class="text-end pe-4">
                        <div class="d-inline-flex align-items-center gap-1">
                            <button type="button" class="btn btn-sm btn-icon btn-outline-primary rounded-3 btn-edit-schedule" 
                                    title="Edit Schedule" data-id="3" data-title="K3 & Safety Awareness in Factory">
                                <i class="ti ti-edit fs-2"></i>
                            </button>
                            <button type="button" class="btn btn-sm btn-icon btn-outline-info rounded-3 btn-participants-schedule" 
                                    title="View Participants" data-id="3" data-title="K3 & Safety Awareness in Factory">
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
                <tr class="schedule-table-row" data-id="4" data-category="Soft Skill" data-status="closed" data-year="2026" data-date="2026-09-29" data-instructor="Citra Lestari, M.Psi." data-location="Meeting Room Bintang 1">
                    <td class="text-center text-muted fw-semibold schedule-row-index">4</td>
                    <td>
                        <div class="d-flex align-items-center gap-3">
                            <div class="rounded-3 d-flex align-items-center justify-content-center flex-shrink-0" 
                                 style="width: 40px; height: 40px; background-color: #f1f5f9; color: #64748b;">
                                <i class="ti ti-message-dots fs-2"></i>
                            </div>
                            <div>
                                <div class="fw-bold text-dark fs-3 training-title-text">Effective Business Communication</div>
                                <div class="text-muted extra-small">
                                    <span>Code: PTKP-COMM-04</span> &bull; <span>Batch 1 / 2026</span>
                                </div>
                            </div>
                        </div>
                    </td>
                    <td>
                        <span class="badge rounded-pill px-3 py-1 fw-semibold" style="background-color: #e0f2fe; color: #0369a1;">
                            Soft Skill
                        </span>
                    </td>
                    <td>
                        <div class="fw-semibold text-dark">29 Sep 2026</div>
                        <div class="text-muted extra-small">13:00 - 17:00 WIB</div>
                    </td>
                    <td>
                        <div class="d-flex align-items-center gap-1 text-muted small">
                            <i class="ti ti-map-pin text-primary fs-2"></i>
                            <span>Meeting Room Bintang 1</span>
                        </div>
                    </td>
                    <td>
                        <div class="fw-semibold text-dark small">Citra Lestari, M.Psi.</div>
                        <div class="text-muted extra-small">Corporate HR Practitioner</div>
                    </td>
                    <td class="text-center">
                        <span class="fw-semibold text-dark small">25 / 25 Seats (Full)</span>
                        <div class="progress progress-xs mt-1" style="height: 4px;">
                            <div class="progress-bar bg-secondary" style="width: 100%;"></div>
                        </div>
                    </td>
                    <td class="text-center">
                        <span class="badge bg-secondary-subtle text-secondary rounded-pill px-3 py-1 fw-semibold">
                            Closed Registration
                        </span>
                    </td>
                    <td class="text-end pe-4">
                        <div class="d-inline-flex align-items-center gap-1">
                            <button type="button" class="btn btn-sm btn-icon btn-outline-primary rounded-3 btn-edit-schedule" 
                                    title="Edit Schedule" data-id="4" data-title="Effective Business Communication">
                                <i class="ti ti-edit fs-2"></i>
                            </button>
                            <button type="button" class="btn btn-sm btn-icon btn-outline-info rounded-3 btn-participants-schedule" 
                                    title="View Participants" data-id="4" data-title="Effective Business Communication">
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
                <tr class="schedule-table-row" data-id="5" data-category="Compliance & Safety" data-status="completed" data-year="2025" data-date="2025-08-14" data-instructor="HSE Team Krakatau Posco" data-location="HSE Training Ground">
                    <td class="text-center text-muted fw-semibold schedule-row-index">5</td>
                    <td>
                        <div class="d-flex align-items-center gap-3">
                            <div class="rounded-3 d-flex align-items-center justify-content-center flex-shrink-0" 
                                 style="width: 40px; height: 40px; background-color: #ecfdf5; color: #059669;">
                                <i class="ti ti-flame fs-2"></i>
                            </div>
                            <div>
                                <div class="fw-bold text-dark fs-3 training-title-text">Basic Fire Fighting & Safety Protocol</div>
                                <div class="text-muted extra-small">
                                    <span>Code: PTKP-BFF-05</span> &bull; <span>Batch 4 / 2025</span>
                                </div>
                            </div>
                        </div>
                    </td>
                    <td>
                        <span class="badge rounded-pill px-3 py-1 fw-semibold" style="background-color: #fef3c7; color: #b45309;">
                            Compliance & Safety
                        </span>
                    </td>
                    <td>
                        <div class="fw-semibold text-dark">14 Aug 2025</div>
                        <div class="text-muted extra-small">08:00 - 12:00 WIB</div>
                    </td>
                    <td>
                        <div class="d-flex align-items-center gap-1 text-muted small">
                            <i class="ti ti-map-pin text-primary fs-2"></i>
                            <span>HSE Training Ground</span>
                        </div>
                    </td>
                    <td>
                        <div class="fw-semibold text-dark small">HSE Team Krakatau Posco</div>
                        <div class="text-muted extra-small">Emergency Response Unit</div>
                    </td>
                    <td class="text-center">
                        <span class="fw-semibold text-dark small">40 / 40 Seats (Full)</span>
                        <div class="progress progress-xs mt-1" style="height: 4px;">
                            <div class="progress-bar bg-info" style="width: 100%;"></div>
                        </div>
                    </td>
                    <td class="text-center">
                        <span class="badge bg-info-subtle text-info rounded-pill px-3 py-1 fw-semibold">
                            Completed
                        </span>
                    </td>
                    <td class="text-end pe-4">
                        <div class="d-inline-flex align-items-center gap-1">
                            <button type="button" class="btn btn-sm btn-icon btn-outline-primary rounded-3 btn-edit-schedule" 
                                    title="Edit Schedule" data-id="5" data-title="Basic Fire Fighting & Safety Protocol">
                                <i class="ti ti-edit fs-2"></i>
                            </button>
                            <button type="button" class="btn btn-sm btn-icon btn-outline-info rounded-3 btn-participants-schedule" 
                                    title="View Participants" data-id="5" data-title="Basic Fire Fighting & Safety Protocol">
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
