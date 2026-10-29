<!-- DETAILED IMPORT STATUS GUIDE (INITIAL UPLOAD PAGE) -->
<div class="card border-0 shadow-sm rounded-4 mb-4 bg-white" id="ptkp-status-guide-card">
    <div class="card-header bg-white border-bottom py-3 px-4">
        <div class="d-flex align-items-center justify-content-between flex-wrap gap-2">
            <div class="d-flex align-items-center gap-2">
                <span class="avatar rounded-3" style="background-color: #eff6ff; color: #2563eb; width: 36px; height: 36px;">
                    <i class="ti ti-help fs-2"></i>
                </span>
                <div>
                    <h5 class="fw-bold text-dark mb-0">Import Status Guide</h5>
                    <p class="text-muted extra-small mb-0" style="font-size: 0.75rem;">Understanding validation statuses and PTKP-specific business rules before uploading</p>
                </div>
            </div>
            <span class="badge bg-light text-secondary border px-2.5 py-1">PTKP Validation Rules</span>
        </div>
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0 ptkp-guide-table">
                <thead class="table-light">
                    <tr>
                        <th style="width: 180px;" class="px-4 py-2.5 text-uppercase fw-bold text-muted small">Status</th>
                        <th class="px-4 py-2.5 text-uppercase fw-bold text-muted small">Description</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td class="px-4 py-3 align-top">
                            <span class="badge badge-ptkp-valid fw-bold px-2.5 py-1.5 fs-6">VALID</span>
                        </td>
                        <td class="px-4 py-3">
                            <div class="fw-semibold text-dark mb-1">The record will be imported into the system.</div>
                            <div class="text-muted small">The record has passed all validation checks and is eligible for import.</div>
                        </td>
                    </tr>
                    <tr>
                        <td class="px-4 py-3 align-top">
                            <span class="badge badge-ptkp-warning fw-bold px-2.5 py-1.5 fs-6">WARNING</span>
                        </td>
                        <td class="px-4 py-3">
                            <div class="fw-semibold text-dark mb-1">The record will be imported, but a warning is present.</div>
                            <div class="text-muted small">The record can be imported, but it contains a warning that should be reviewed.</div>
                        </td>
                    </tr>
                    <tr>
                        <td class="px-4 py-3 align-top">
                            <span class="badge badge-ptkp-duplicate fw-bold px-2.5 py-1.5 fs-6">DUPLICATE</span>
                        </td>
                        <td class="px-4 py-3">
                            <div class="fw-semibold text-dark mb-1">The record will not be imported because a duplicate was detected in the uploaded file or existing database records.</div>
                            <div class="text-muted small">Duplicate detection checks both duplicate participants within the uploaded file and records already stored in the LMS database.</div>
                        </td>
                    </tr>
                    <tr>
                        <td class="px-4 py-3 align-top">
                            <span class="badge badge-ptkp-invalid fw-bold px-2.5 py-1.5 fs-6">INVALID</span>
                        </td>
                        <td class="px-4 py-3">
                            <div class="fw-semibold text-dark mb-1">The record will not be imported. For PTKP, this applies when the Employee ID is invalid or unreadable, the Course Title is missing, or both fields are invalid or missing.</div>
                            <div class="text-muted small mt-1.5">
                                <ul class="mb-0 ps-3">
                                    <li>The Employee ID (Emp ID) is invalid or cannot be recognized.</li>
                                    <li>The Course Title is missing.</li>
                                    <li>Both the Employee ID and Course Title are invalid or missing.</li>
                                </ul>
                            </div>
                        </td>
                    </tr>
                    <tr>
                        <td class="px-4 py-3 align-top">
                            <span class="badge badge-ptkp-invalid-company fw-bold px-2.5 py-1.5 fs-6">INVALID COMPANY</span>
                        </td>
                        <td class="px-4 py-3">
                            <div class="fw-semibold text-dark mb-1">The record will still be imported, but the Company field contains an unrecognized PTKP company name or is empty.</div>
                            <div class="text-muted small mt-1.5">
                                <ul class="mb-0 ps-3">
                                    <li>The Company field contains a company name outside the recognized PTKP companies.</li>
                                    <li>The Company field is empty.</li>
                                </ul>
                                <div class="mt-2 text-warning fw-medium small d-flex align-items-center gap-1.5">
                                    <i class="ti ti-info-circle"></i>
                                    <span><strong>Important:</strong> INVALID COMPANY is a non-blocking warning. The record will still be imported into the system.</span>
                                </div>
                            </div>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>
</div>
