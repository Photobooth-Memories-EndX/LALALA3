{{-- Tab Riwayat Saya / My History --}}
<div class="card border border-light-subtle shadow-sm rounded-4 bg-white p-4">
    <div class="d-flex flex-wrap align-items-center justify-content-between gap-3 mb-4">
        <div>
            <h3 class="fw-bold mb-1 text-dark">Training Registration & History</h3>
            <p class="text-muted small mb-0">List of your enrolled training sessions, approval statuses, and completion history.</p>
        </div>
        <div class="d-flex align-items-center gap-2">
            <span class="badge bg-primary-lt text-primary px-3 py-2 rounded-pill fw-semibold" id="myHistoryTotalBadge">
                Total: 4 Trainings
            </span>
        </div>
    </div>

    <div class="table-responsive">
        <table class="table table-hover table-vcenter align-middle mb-0" id="myHistoryTable">
            <thead>
                <tr class="text-muted extra-small text-uppercase fw-bold text-center align-middle" style="letter-spacing: 0.05em; background-color: #f8fafc;">
                    <th class="w-1 py-3">NO</th>
                    <th class="py-3" style="min-width: 200px;">TRAINING NAME</th>
                    <th class="py-3">CATEGORY</th>
                    <th class="py-3" style="min-width: 110px;">DATE</th>
                    <th class="py-3" style="min-width: 120px;">TIME</th>
                    <th class="py-3">LOCATION</th>
                    <th class="py-3">STATUS</th>
                    <th class="py-3" style="min-width: 160px;">ACTION</th>
                </tr>
            </thead>
            <tbody>
                {{-- Row 1: Approved --}}
                <tr id="history-row-1" class="text-center align-middle">
                    <td class="text-muted fw-semibold">1</td>
                    <td class="fw-bold text-dark" style="min-width: 200px;">Leadership Development Program</td>
                    <td>
                        <span class="badge rounded-pill px-3 py-1-5 fw-semibold" style="background-color: #e0f2fe; color: #0369a1;">Soft Skill</span>
                    </td>
                    <td class="fw-semibold text-dark" style="min-width: 110px;">18 Sep 2026</td>
                    <td class="text-muted small" style="min-width: 120px;">08:00 - 16:00 WIB</td>
                    <td class="text-muted small">Training Room Building A, 2nd Fl.</td>
                    <td>
                        <span class="badge bg-success-subtle text-success rounded-pill px-3 py-1 fw-semibold">Approved</span>
                    </td>
                    <td>
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
                <tr id="history-row-2" class="text-center align-middle">
                    <td class="text-muted fw-semibold">2</td>
                    <td class="fw-bold text-dark" style="min-width: 200px;">Excel Advanced for Data Analysis</td>
                    <td>
                        <span class="badge rounded-pill px-3 py-1-5 fw-semibold" style="background-color: #e0f2fe; color: #0369a1;">Technical Skill</span>
                    </td>
                    <td class="fw-semibold text-dark" style="min-width: 110px;">18 Sep 2026</td>
                    <td class="text-muted small" style="min-width: 120px;">09:00 - 15:00 WIB</td>
                    <td class="text-muted small">Computer Lab 2, 3rd Fl.</td>
                    <td>
                        <span class="badge bg-warning-subtle text-warning border border-warning-subtle rounded-pill px-3 py-1 fw-semibold">Pending</span>
                    </td>
                    <td>
                        <div class="d-flex align-items-center justify-content-center gap-1">
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
                            <button type="button" class="btn btn-outline-danger btn-sm rounded-pill px-3 py-1 fw-semibold d-inline-flex align-items-center gap-1" onclick="confirmCancelRegistration(2)" title="Cancel Registration">
                                <i class="ti ti-trash"></i>
                                <span>Cancel</span>
                            </button>
                        </div>
                    </td>
                </tr>

                {{-- Row 3: Completed --}}
                <tr id="history-row-3" class="text-center align-middle">
                    <td class="text-muted fw-semibold">3</td>
                    <td class="fw-bold text-dark" style="min-width: 200px;">Basic Fire Fighting & Safety Protocol</td>
                    <td>
                        <span class="badge rounded-pill px-3 py-1-5 fw-semibold" style="background-color: #fef3c7; color: #b45309;">Compliance & Safety</span>
                    </td>
                    <td class="fw-semibold text-dark" style="min-width: 110px;">20 Jul 2026</td>
                    <td class="text-muted small" style="min-width: 120px;">08:00 - 12:00 WIB</td>
                    <td class="text-muted small">HSE Training Ground</td>
                    <td>
                        <span class="badge rounded-pill px-3 py-1 fw-semibold" style="background-color: rgba(2, 132, 199, 0.12) !important; color: #0284c7 !important;">Completed</span>
                    </td>
                    <td>
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
                <tr id="history-row-4" class="text-center align-middle">
                    <td class="text-muted fw-semibold">4</td>
                    <td class="fw-bold text-dark" style="min-width: 200px;">PLC Siemens S7-1500 Advanced</td>
                    <td>
                        <span class="badge rounded-pill px-3 py-1-5 fw-semibold" style="background-color: #e0f2fe; color: #0369a1;">Technical Skill</span>
                    </td>
                    <td class="fw-semibold text-dark" style="min-width: 110px;">12 Jun 2026</td>
                    <td class="text-muted small" style="min-width: 120px;">09:00 - 16:00 WIB</td>
                    <td class="text-muted small">Automation Workshop</td>
                    <td>
                        <span class="badge bg-danger-subtle text-danger rounded-pill px-3 py-1 fw-semibold">Rejected</span>
                    </td>
                    <td>
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

<script>
function confirmCancelRegistration(id) {
    if (confirm('Are you sure you want to cancel your training registration?')) {
        // Form submission or API fetch call to delete registration record
        console.log('Cancelling registration for ID:', id);

        const row = document.getElementById('history-row-' + id);
        if (row) {
            row.style.transition = 'all 0.3s ease';
            row.style.opacity = '0';
            setTimeout(function () {
                row.remove();
                const remainingRows = document.querySelectorAll('#myHistoryTable tbody tr').length;
                const totalBadge = document.getElementById('myHistoryTotalBadge');
                if (totalBadge) {
                    totalBadge.textContent = 'Total: ' + remainingRows + ' Trainings';
                }
            }, 300);
        }
    }
}
window.confirmCancelRegistration = confirmCancelRegistration;
</script>
