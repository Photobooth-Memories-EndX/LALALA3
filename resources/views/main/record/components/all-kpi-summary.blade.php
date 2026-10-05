<!-- 4 KPI SUMMARY CARDS -->
<div class="row g-3 mb-4">
    <!-- Card 1: Total Learning -->
    <div class="col-sm-6 col-lg-3">
        <div class="card border-0 shadow-sm p-3 rounded-3 bg-white stat-kpi-card">
            <div class="d-flex align-items-center">
                <div class="rounded-3 d-flex align-items-center justify-content-center flex-shrink-0 me-3" 
                     style="width: 44px; height: 44px; background-color: #ede9fe; color: #7c3aed;">
                    <i class="ti ti-stack-2 fs-2"></i>
                </div>
                <div>
                    <div class="text-muted fw-semibold" style="font-size: 0.8rem; line-height: 1.2;">Total Learning</div>
                    <div class="fw-bold fs-3 text-dark mt-1" style="line-height: 1.1;">{{ number_format($total_learning ?? 0) }}</div>
                </div>
            </div>
        </div>
    </div>

    <!-- Card 2: Ongoing -->
    <div class="col-sm-6 col-lg-3">
        <div class="card border-0 shadow-sm p-3 rounded-3 bg-white stat-kpi-card">
            <div class="d-flex align-items-center">
                <div class="rounded-3 d-flex align-items-center justify-content-center flex-shrink-0 me-3" 
                     style="width: 44px; height: 44px; background-color: #fef3c7; color: #d97706;">
                    <i class="ti ti-progress fs-2"></i>
                </div>
                <div>
                    <div class="text-muted fw-semibold" style="font-size: 0.8rem; line-height: 1.2;">Ongoing</div>
                    <div class="fw-bold fs-3 text-dark mt-1" style="line-height: 1.1;">{{ number_format($ongoing ?? 0) }}</div>
                </div>
            </div>
        </div>
    </div>

    <!-- Card 3: Finished -->
    <div class="col-sm-6 col-lg-3">
        <div class="card border-0 shadow-sm p-3 rounded-3 bg-white stat-kpi-card">
            <div class="d-flex align-items-center">
                <div class="rounded-3 d-flex align-items-center justify-content-center flex-shrink-0 me-3" 
                     style="width: 44px; height: 44px; background-color: #ecfdf5; color: #059669;">
                    <i class="ti ti-circle-check fs-2"></i>
                </div>
                <div>
                    <div class="text-muted fw-semibold" style="font-size: 0.8rem; line-height: 1.2;">Finished</div>
                    <div class="fw-bold fs-3 text-dark mt-1" style="line-height: 1.1;">{{ number_format($finished ?? 0) }}</div>
                </div>
            </div>
        </div>
    </div>

    <!-- Card 4: Learning Hours -->
    <div class="col-sm-6 col-lg-3">
        <div class="card border-0 shadow-sm p-3 rounded-3 bg-white stat-kpi-card">
            <div class="d-flex align-items-center">
                <div class="rounded-3 d-flex align-items-center justify-content-center flex-shrink-0 me-3" 
                     style="width: 44px; height: 44px; background-color: #e0f2fe; color: #0284c7;">
                    <i class="ti ti-clock fs-2"></i>
                </div>
                <div>
                    <div class="text-muted fw-semibold" style="font-size: 0.8rem; line-height: 1.2;">Learning Hours</div>
                    <div class="fw-bold fs-3 text-dark mt-1" style="line-height: 1.1;">{{ number_format($total_learning_hours ?? 0, 1) }} Hours</div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- SOURCE SUMMARY SECTION -->
<div class="row g-3 mb-4">
    <div class="col-md-6">
        <div class="card border-0 shadow-sm p-3 rounded-3 bg-white h-100">
            <div class="d-flex align-items-center justify-content-between">
                <div class="d-flex align-items-center gap-3">
                    <span class="rounded-3 d-flex align-items-center justify-content-center flex-shrink-0"
                          style="width: 46px; height: 46px; background-color: #e0f2fe; color: #0284c7;">
                        <i class="ti ti-device-laptop fs-2"></i>
                    </span>
                    <div>
                        <div class="fw-bold text-dark fs-3 mb-0" style="line-height: 1.1;">{{ number_format($online['total_learning'] ?? 0) }}</div>
                        <div class="text-muted small fw-medium">KP OWL — Online Learning Records</div>
                    </div>
                </div>
                <div class="text-end">
                    <span class="badge bg-blue-lt fw-semibold px-2.5 py-1.5" style="font-size: 0.8rem;">
                        <i class="ti ti-clock me-1"></i>{{ number_format($online['total_learning_hours'] ?? 0, 1) }} Hours
                    </span>
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-6">
        <div class="card border-0 shadow-sm p-3 rounded-3 bg-white h-100">
            <div class="d-flex align-items-center justify-content-between">
                <div class="d-flex align-items-center gap-3">
                    <span class="rounded-3 d-flex align-items-center justify-content-center flex-shrink-0"
                          style="width: 46px; height: 46px; background-color: #fef3c7; color: #d97706;">
                        <i class="ti ti-certificate fs-2"></i>
                    </span>
                    <div>
                        <div class="fw-bold text-dark fs-3 mb-0" style="line-height: 1.1;">{{ number_format($offline['total_learning'] ?? 0) }}</div>
                        <div class="text-muted small fw-medium">Non KP OWL — Offline Training Records</div>
                    </div>
                </div>
                <div class="text-end">
                    <span class="badge bg-amber-lt fw-semibold px-2.5 py-1.5" style="font-size: 0.8rem; color: #b45309 !important;">
                        <i class="ti ti-clock me-1"></i>{{ number_format($offline['total_learning_hours'] ?? 0, 1) }} Hours
                    </span>
                </div>
            </div>
        </div>
    </div>
</div>
