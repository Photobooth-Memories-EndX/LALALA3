{{-- Modal Detail User Resigned (Read-Only Monitoring) --}}
<div class="modal fade" id="resignDetailModal" tabindex="-1" aria-labelledby="resignDetailModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg modal-dialog-scrollable">
        <div class="modal-content rounded-4 border-0 shadow">
            <div class="modal-header border-bottom">
                <div class="d-flex align-items-center gap-3">
                    <div class="rounded-3 d-flex align-items-center justify-content-center flex-shrink-0"
                         style="width: 48px; height: 48px; background-color: #fee2e2; color: #dc2626;">
                        <i class="ti ti-user-off fs-2"></i>
                    </div>
                    <div>
                        <h5 class="modal-title fw-bold text-dark mb-0" id="detail-modal-user-name">-</h5>
                        <div class="text-muted small">
                            NIP: <span id="detail-modal-user-nip" class="fw-semibold">-</span> &bull; 
                            SAP: <span id="detail-modal-user-sap" class="fw-semibold">-</span>
                        </div>
                    </div>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>

            <div class="modal-body py-3">
                <!-- Status Banner -->
                <div class="alert alert-danger d-flex align-items-center justify-content-between p-3 rounded-3 mb-3">
                    <div class="d-flex align-items-center gap-2">
                        <i class="ti ti-alert-triangle fs-2 text-danger"></i>
                        <div>
                            <div class="fw-bold">Status Karyawan: Resigned (Soft-Deleted)</div>
                            <small class="text-muted">Akun ini telah dinonaktifkan dari sistem pembelajaran OWL melalui mekanisme Soft Delete.</small>
                        </div>
                    </div>
                    <span class="badge bg-danger text-white px-3 py-2 fs-5" id="detail-modal-status-badge">Resigned</span>
                </div>

                <div class="row g-3">
                    <!-- Section 1: Data Akun & Profil Karyawan -->
                    <div class="col-12 col-md-6">
                        <div class="card border rounded-3 p-3 h-100 bg-light-subtle">
                            <h6 class="fw-bold text-dark mb-3 d-flex align-items-center gap-2 border-bottom pb-2">
                                <i class="ti ti-id text-primary"></i> Data Personal & Akun
                            </h6>
                            <table class="table table-sm table-borderless mb-0">
                                <tr>
                                    <td class="text-muted" style="width: 140px;">Employee ID (NIP)</td>
                                    <td class="fw-semibold text-dark" id="modal-field-id-emp">-</td>
                                </tr>
                                <tr>
                                    <td class="text-muted">Nama Lengkap</td>
                                    <td class="fw-semibold text-dark" id="modal-field-name">-</td>
                                </tr>
                                <tr>
                                    <td class="text-muted">Email</td>
                                    <td class="text-dark" id="modal-field-email">-</td>
                                </tr>
                                <tr>
                                    <td class="text-muted">SAP Code</td>
                                    <td class="text-dark font-monospace" id="modal-field-sap">-</td>
                                </tr>
                                <tr>
                                    <td class="text-muted">Nomor HP</td>
                                    <td class="text-dark" id="modal-field-phone">-</td>
                                </tr>
                                <tr>
                                    <td class="text-muted">Jenis Kelamin</td>
                                    <td class="text-dark" id="modal-field-sex">-</td>
                                </tr>
                                <tr>
                                    <td class="text-muted">Role Terakhir</td>
                                    <td class="text-dark" id="modal-field-role">-</td>
                                </tr>
                            </table>
                        </div>
                    </div>

                    <!-- Section 2: Struktur Organisasi -->
                    <div class="col-12 col-md-6">
                        <div class="card border rounded-3 p-3 h-100 bg-light-subtle">
                            <h6 class="fw-bold text-dark mb-3 d-flex align-items-center gap-2 border-bottom pb-2">
                                <i class="ti ti-sitemap text-primary"></i> Struktur Organisasi
                            </h6>
                            <table class="table table-sm table-borderless mb-0">
                                <tr>
                                    <td class="text-muted" style="width: 140px;">Perusahaan</td>
                                    <td class="fw-semibold text-dark" id="modal-field-company">-</td>
                                </tr>
                                <tr>
                                    <td class="text-muted">Division</td>
                                    <td class="text-dark" id="modal-field-division">-</td>
                                </tr>
                                <tr>
                                    <td class="text-muted">Department</td>
                                    <td class="text-dark" id="modal-field-department">-</td>
                                </tr>
                                <tr>
                                    <td class="text-muted">Team</td>
                                    <td class="text-dark" id="modal-field-team">-</td>
                                </tr>
                                <tr>
                                    <td class="text-muted">Organization Unit</td>
                                    <td class="text-dark" id="modal-field-orgunit">-</td>
                                </tr>
                            </table>
                        </div>
                    </div>

                    <!-- Section 3: Posisi & Job Level -->
                    <div class="col-12 col-md-6">
                        <div class="card border rounded-3 p-3 h-100 bg-light-subtle">
                            <h6 class="fw-bold text-dark mb-3 d-flex align-items-center gap-2 border-bottom pb-2">
                                <i class="ti ti-briefcase text-primary"></i> Posisi & Jabatan
                            </h6>
                            <table class="table table-sm table-borderless mb-0">
                                <tr>
                                    <td class="text-muted" style="width: 140px;">Job Position</td>
                                    <td class="fw-semibold text-dark" id="modal-field-jobposition">-</td>
                                </tr>
                                <tr>
                                    <td class="text-muted">Grade</td>
                                    <td class="text-dark" id="modal-field-jobgrade">-</td>
                                </tr>
                                <tr>
                                    <td class="text-muted">Position ID</td>
                                    <td class="text-dark font-monospace" id="modal-field-position-id">-</td>
                                </tr>
                                <tr>
                                    <td class="text-muted">Job Clan</td>
                                    <td class="text-dark" id="modal-field-job-clan">-</td>
                                </tr>
                                <tr>
                                    <td class="text-muted">Job Family</td>
                                    <td class="text-dark" id="modal-field-job-family">-</td>
                                </tr>
                                <tr>
                                    <td class="text-muted">Approver 1</td>
                                    <td class="text-dark" id="modal-field-appr1">-</td>
                                </tr>
                                <tr>
                                    <td class="text-muted">Approver 2</td>
                                    <td class="text-dark" id="modal-field-appr2">-</td>
                                </tr>
                            </table>
                        </div>
                    </div>

                    <!-- Section 4: Log Resign & Timestamps -->
                    <div class="col-12 col-md-6">
                        <div class="card border rounded-3 p-3 h-100 bg-light-subtle">
                            <h6 class="fw-bold text-dark mb-3 d-flex align-items-center gap-2 border-bottom pb-2">
                                <i class="ti ti-calendar-event text-danger"></i> Riwayat & Timestamp
                            </h6>
                            <table class="table table-sm table-borderless mb-0">
                                <tr>
                                    <td class="text-muted" style="width: 140px;">Tanggal Resign</td>
                                    <td class="fw-bold text-danger" id="modal-field-resign-date">-</td>
                                </tr>
                                <tr>
                                    <td class="text-muted">Resign Flag (API)</td>
                                    <td class="text-dark" id="modal-field-resign-flag">-</td>
                                </tr>
                                <tr>
                                    <td class="text-muted">Akun Dibuat</td>
                                    <td class="text-dark" id="modal-field-created-at">-</td>
                                </tr>
                                <tr>
                                    <td class="text-muted">Terakhir Diperbarui</td>
                                    <td class="text-dark" id="modal-field-updated-at">-</td>
                                </tr>
                            </table>
                            <div class="mt-3 p-2 bg-light border rounded-2 small text-muted">
                                <i class="ti ti-info-circle text-primary me-1"></i>
                                Data ini diambil langsung dari kolom <code>deleted_at</code> tabel <code>users</code>.
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="modal-footer border-top bg-light rounded-bottom-4">
                <button type="button" class="btn btn-secondary px-4" data-bs-dismiss="modal">
                    <i class="ti ti-x me-1"></i> Tutup
                </button>
            </div>
        </div>
    </div>
</div>
