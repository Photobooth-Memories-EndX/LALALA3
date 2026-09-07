<div class="card border-0 shadow-sm p-4 mb-3 rounded-3 bg-white">
    <div class="d-flex flex-column flex-md-row align-items-md-center justify-content-between gap-3">
        <div class="d-flex align-items-center gap-3">
            <div class="rounded-3 d-flex align-items-center justify-content-center flex-shrink-0" 
                 style="width: 72px; height: 72px; background-color: #eef2ff; color: #003b6d;">
                <i class="ti ti-users" style="font-size: 2rem !important;"></i>
            </div>
            <div>
                <h2 class="h3 fw-bold mb-1 text-dark">User Management</h2>
                <p class="text-muted small mb-0">Manage PTKP and Non-PTKP employee user data, roles, and access in OWL LMS.</p>
            </div>
        </div>
        <div class="d-flex align-items-center gap-2 flex-shrink-0">
            <button type="button" class="btn btn-primary px-3 py-2 fw-semibold d-inline-flex align-items-center gap-2" 
                    style="background-color: #003b6d; border-color: #003b6d;" 
                    data-bs-toggle="modal" data-bs-target="#modalCreatePtkp">
                <i class="ti ti-plus fs-2"></i> Add User PTKP
            </button>
            <button type="button" class="btn btn-outline-primary px-3 py-2 fw-semibold d-inline-flex align-items-center gap-2" 
                    data-bs-toggle="modal" data-bs-target="#modalCreateOutsourcing">
                <i class="ti ti-plus fs-2"></i> Add User Non-PTKP
            </button>
        </div>
    </div>
</div>
