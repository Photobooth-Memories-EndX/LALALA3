<!-- Back Navigation Link -->
<a href="{{ route('manage.dashboard') }}" class="btn btn-link p-0 mb-3 text-decoration-none d-inline-flex align-items-center gap-1 fw-semibold text-primary">
    <i class="ti ti-arrow-left fs-2"></i> Back
</a>

<!-- Certificate Setting Hero Header Card -->
<div class="card border border-light-subtle shadow-sm p-4 mb-3 rounded-3 bg-white hero-header-card admin-card-bordered">
    <div class="d-flex flex-column flex-md-row align-items-md-center justify-content-between gap-3">
        <div class="d-flex align-items-center gap-3">
            <div class="rounded-3 d-flex align-items-center justify-content-center flex-shrink-0 hero-icon-box"
                 style="width: 72px; height: 72px; background-color: #eef2ff; color: #003b6d;">
                <i class="ti ti-certificate" style="font-size: 2rem !important;"></i>
            </div>
            <div>
                <h2 class="h3 fw-bold mb-1 text-dark header-title">Certificate Setting</h2>
                <p class="text-muted small mb-0 header-subtitle">Manage OWL LMS certificate requirements, templates, and issuers.</p>
            </div>
        </div>
        <div class="flex-shrink-0">
            <button type="button"
                    class="btn btn-primary px-3 py-2 fw-semibold d-inline-flex align-items-center gap-2 rounded-3 shadow-none"
                    style="background-color: #003b6d; border-color: #003b6d;"
                    data-bs-toggle="modal"
                    data-bs-target="#addCertificateSettingModal"
                    data-toggle="modal"
                    data-target="#addCertificateSettingModal">
                <i class="ti ti-plus fs-2"></i>
                <span>Add Certificate</span>
            </button>
        </div>
    </div>
</div>
