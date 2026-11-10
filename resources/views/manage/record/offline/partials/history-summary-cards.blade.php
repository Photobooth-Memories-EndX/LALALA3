{{-- Statistics Cards matching Content Management design --}}
<div class="row g-3 mb-3" id="import-summary-cards-container">
    <!-- Card 1: Total Batches -->
    <div class="col-6 col-md-3">
        <div class="card border-0 shadow-sm p-3 rounded-3 content-stat-card">
            <div class="d-flex align-items-center">
                <div class="rounded-3 d-flex align-items-center justify-content-center flex-shrink-0 me-3" 
                     style="width: 44px; height: 44px; background-color: #0f4c81; color: #ffffff;">
                    <i class="ti ti-archive fs-2"></i>
                </div>
                <div>
                    <div class="text-muted fw-semibold" style="font-size: 0.8rem; line-height: 1.2;">Total Batches</div>
                    <div class="fw-bold fs-3 text-dark mt-1" id="card-total-batches" style="line-height: 1.1;">{{ number_format($summary['total_batches'] ?? 0) }}</div>
                </div>
            </div>
        </div>
    </div>

    <!-- Card 2: Import Successful -->
    <div class="col-6 col-md-3">
        <div class="card border-0 shadow-sm p-3 rounded-3 content-stat-card">
            <div class="d-flex align-items-center">
                <div class="rounded-3 d-flex align-items-center justify-content-center flex-shrink-0 me-3" 
                     style="width: 44px; height: 44px; background-color: #2fb344; color: #ffffff;">
                    <i class="ti ti-circle-check fs-2"></i>
                </div>
                <div>
                    <div class="text-muted fw-semibold" style="font-size: 0.8rem; line-height: 1.2;">Import Successful</div>
                    <div class="fw-bold fs-3 text-dark mt-1 text-success" id="card-completed-batches" style="line-height: 1.1;">{{ number_format($summary['completed_batches'] ?? 0) }}</div>
                </div>
            </div>
        </div>
    </div>

    <!-- Card 3: With Warning -->
    <div class="col-6 col-md-3">
        <div class="card border-0 shadow-sm p-3 rounded-3 content-stat-card">
            <div class="d-flex align-items-center">
                <div class="rounded-3 d-flex align-items-center justify-content-center flex-shrink-0 me-3" 
                     style="width: 44px; height: 44px; background-color: #f76707; color: #ffffff;">
                    <i class="ti ti-alert-triangle fs-2"></i>
                </div>
                <div>
                    <div class="text-muted fw-semibold" style="font-size: 0.8rem; line-height: 1.2;">With Warning</div>
                    <div class="fw-bold fs-3 text-dark mt-1 text-warning" id="card-warning-batches" style="line-height: 1.1;">{{ number_format($summary['warning_batches'] ?? 0) }}</div>
                </div>
            </div>
        </div>
    </div>

    <!-- Card 4: Import Failed -->
    <div class="col-6 col-md-3">
        <div class="card border-0 shadow-sm p-3 rounded-3 content-stat-card">
            <div class="d-flex align-items-center">
                <div class="rounded-3 d-flex align-items-center justify-content-center flex-shrink-0 me-3" 
                     style="width: 44px; height: 44px; background-color: #d63939; color: #ffffff;">
                    <i class="ti ti-alert-circle fs-2"></i>
                </div>
                <div>
                    <div class="text-muted fw-semibold" style="font-size: 0.8rem; line-height: 1.2;">Import Failed</div>
                    <div class="fw-bold fs-3 text-dark mt-1 text-danger" id="card-failed-batches" style="line-height: 1.1;">{{ number_format($summary['failed_batches'] ?? 0) }}</div>
                </div>
            </div>
        </div>
    </div>
</div>
