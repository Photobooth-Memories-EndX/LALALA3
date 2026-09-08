{{-- Page Header --}}
<div class="page-header d-print-none mb-0 pb-0">
    <div class="container-xl">
        <!-- Back Navigation Link -->
        <div class="mb-3">
            <a href="{{ url('/manage') }}" class="text-decoration-none text-muted small d-inline-flex align-items-center gap-1">
                <i class="ti ti-arrow-left fs-3"></i><span>Back</span>
            </a>
        </div>

        <!-- Content Management Hero Header Box -->
        <div class="card border-0 shadow-sm p-4 mb-3 rounded-3 content-hero-card">
            <div class="d-flex flex-column flex-md-row align-items-md-center justify-content-between gap-3">
                <div class="d-flex align-items-center gap-3">
                    <div class="content-hero-icon rounded-3 d-flex align-items-center justify-content-center flex-shrink-0">
                        <i class="ti ti-books" style="font-size: 2rem !important;"></i>
                    </div>
                    <div>
                        <h2 class="h3 fw-bold mb-1 text-dark">Content Management</h2>
                        <p class="text-muted small mb-0">Manage learning materials, videos, documents, assessments, and playlist content.</p>
                    </div>
                </div>
                <div class="flex-shrink-0 d-flex flex-wrap align-items-center gap-2">
                    <button type="button" class="btn btn-outline-primary px-3 py-2 fw-semibold" data-bs-toggle="modal" data-bs-target="#modalSyncLearningHours" id="btnOpenSyncLhModal">
                        <i class="ti ti-refresh me-1"></i>Sync Learning Hours
                    </button>
                    <button type="button" id="btnSyncPdfTotalPage" class="btn btn-outline-primary px-3 py-2 fw-semibold" data-bs-toggle="modal" data-bs-target="#modalTotalPageSynchronization">
                        <i class="ti ti-file-description me-1"></i>Sync Total Page PDF
                    </button>
                    <a href="{{ route('content.create') }}" class="btn btn-primary px-3 py-2 fw-semibold d-inline-flex align-items-center gap-2" style="background-color: #003b6d; border-color: #003b6d;">
                        <i class="ti ti-plus fs-2"></i><span>Add Content</span>
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>
