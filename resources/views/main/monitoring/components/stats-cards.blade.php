{{-- Props: $stats [total_user, avg_progress, finished_content, assessment_count] --}}
<div class="row g-3 mb-3">

    <!-- Card 1: Total User -->
    <div class="col-sm-6 col-lg-3">
        <div class="card border border-light-subtle shadow-sm p-3 rounded-3 bg-white stat-card-item h-100">
            <div class="d-flex align-items-center">
                <div class="rounded-3 d-flex align-items-center justify-content-center flex-shrink-0 me-3"
                     style="width: 44px; height: 44px; background-color: #003b6d; color: #ffffff;">
                    <i class="ti ti-users fs-2"></i>
                </div>
                <div>
                    <div class="text-muted fw-semibold" style="font-size: 0.8rem; line-height: 1.2;">Total User</div>
                    <div class="fw-bold fs-3 text-dark mt-1" style="line-height: 1.1;">{{ number_format($stats['total_user']) }}</div>
                </div>
            </div>
        </div>
    </div>

    <!-- Card 2: Average Progress -->
    <div class="col-sm-6 col-lg-3">
        <div class="card border border-light-subtle shadow-sm p-3 rounded-3 bg-white stat-card-item h-100">
            <div class="d-flex align-items-center">
                <div class="rounded-3 d-flex align-items-center justify-content-center flex-shrink-0 me-3"
                     style="width: 44px; height: 44px; background-color: #0284c7; color: #ffffff;">
                    <i class="ti ti-chart-bar fs-2"></i>
                </div>
                <div>
                    <div class="text-muted fw-semibold" style="font-size: 0.8rem; line-height: 1.2;">Average Progress</div>
                    <div class="fw-bold fs-3 text-dark mt-1" style="line-height: 1.1;">{{ $stats['avg_progress'] }}%</div>
                </div>
            </div>
        </div>
    </div>

    <!-- Card 3: Completed Content -->
    <div class="col-sm-6 col-lg-3">
        <div class="card border border-light-subtle shadow-sm p-3 rounded-3 bg-white stat-card-item h-100">
            <div class="d-flex align-items-center">
                <div class="rounded-3 d-flex align-items-center justify-content-center flex-shrink-0 me-3"
                     style="width: 44px; height: 44px; background-color: #38b000; color: #ffffff;">
                    <i class="ti ti-circle-check fs-2"></i>
                </div>
                <div>
                    <div class="text-muted fw-semibold" style="font-size: 0.8rem; line-height: 1.2;">Completed Content</div>
                    <div class="fw-bold fs-3 text-dark mt-1" style="line-height: 1.1;">{{ number_format($stats['finished_content']) }}</div>
                </div>
            </div>
        </div>
    </div>

    <!-- Card 4: Total Assessment -->
    <div class="col-sm-6 col-lg-3">
        <div class="card border border-light-subtle shadow-sm p-3 rounded-3 bg-white stat-card-item h-100">
            <div class="d-flex align-items-center">
                <div class="rounded-3 d-flex align-items-center justify-content-center flex-shrink-0 me-3"
                     style="width: 44px; height: 44px; background-color: #7209b7; color: #ffffff;">
                    <i class="ti ti-clipboard-list fs-2"></i>
                </div>
                <div>
                    <div class="text-muted fw-semibold" style="font-size: 0.8rem; line-height: 1.2;">Total Assessment</div>
                    <div class="fw-bold fs-3 text-dark mt-1" style="line-height: 1.1;">{{ number_format($stats['assessment_count']) }}</div>
                </div>
            </div>
        </div>
    </div>

</div>

