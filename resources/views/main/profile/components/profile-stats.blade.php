<!-- 4 KPI SUMMARY CARDS (Standardized to Offline Participant Design) -->
<div class="row g-3 mb-3 profile-kpi-row">
    <!-- Card 1: Total Learning (Biru Muda) -->
    <div class="col-6 col-lg-3">
        <div class="card border-0 shadow-sm p-3 rounded-3 bg-white stat-kpi-card h-100">
            <div class="d-flex align-items-center">
                <div class="rounded-3 d-flex align-items-center justify-content-center flex-shrink-0 me-3 kpi-icon-box"
                     style="width: 44px; height: 44px; background-color: #0284c7; color: #ffffff;">
                    <i class="ti ti-notebook fs-2"></i>
                </div>
                <div class="min-w-0">
                    <div class="text-muted fw-semibold" style="font-size: 0.8rem; line-height: 1.2;">Total Learning</div>
                    <div class="fw-bold fs-3 text-dark mt-1" style="line-height: 1.1;" id="kpi-total-learning">
                        {{ number_format($totalLearning) }}
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Card 2: Finished (Hijau) -->
    <div class="col-6 col-lg-3">
        <div class="card border-0 shadow-sm p-3 rounded-3 bg-white stat-kpi-card h-100">
            <div class="d-flex align-items-center">
                <div class="rounded-3 d-flex align-items-center justify-content-center flex-shrink-0 me-3 kpi-icon-box"
                     style="width: 44px; height: 44px; background-color: #16a34a; color: #ffffff;">
                    <i class="ti ti-circle-check fs-2"></i>
                </div>
                <div class="min-w-0">
                    <div class="text-muted fw-semibold" style="font-size: 0.8rem; line-height: 1.2;">Finished</div>
                    <div class="fw-bold fs-3 text-dark mt-1" style="line-height: 1.1;" id="kpi-finished">
                        {{ number_format($totalFinished) }}
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Card 3: Ongoing (Orange) -->
    <div class="col-6 col-lg-3">
        <div class="card border-0 shadow-sm p-3 rounded-3 bg-white stat-kpi-card h-100">
            <div class="d-flex align-items-center">
                <div class="rounded-3 d-flex align-items-center justify-content-center flex-shrink-0 me-3 kpi-icon-box"
                     style="width: 44px; height: 44px; background-color: #f59e0b; color: #ffffff;">
                    <i class="ti ti-clock-hour-4 fs-2"></i>
                </div>
                <div class="min-w-0">
                    <div class="text-muted fw-semibold" style="font-size: 0.8rem; line-height: 1.2;">Ongoing</div>
                    <div class="fw-bold fs-3 text-dark mt-1" style="line-height: 1.1;" id="kpi-ongoing">
                        {{ number_format($totalOngoing) }}
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Card 4: Completion (Ungu) -->
    <div class="col-6 col-lg-3">
        <div class="card border-0 shadow-sm p-3 rounded-3 bg-white stat-kpi-card h-100">
            <div class="d-flex align-items-center">
                <div class="rounded-3 d-flex align-items-center justify-content-center flex-shrink-0 me-3 kpi-icon-box"
                     style="width: 44px; height: 44px; background-color: #7c3aed; color: #ffffff;">
                    <i class="ti ti-chart-donut fs-2"></i>
                </div>
                <div class="min-w-0">
                    <div class="text-muted fw-semibold" style="font-size: 0.8rem; line-height: 1.2;">Completion</div>
                    <div class="fw-bold fs-3 text-dark mt-1" style="line-height: 1.1;" id="kpi-completion">
                        {{ $percentage }}%
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
