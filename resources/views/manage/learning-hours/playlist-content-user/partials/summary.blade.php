<div class="row row-deck row-cards mb-4">
    <!-- Total Records -->
    <div class="col-sm-6 col-lg-3">
        <div class="card border-0 shadow-sm stat-card stat-card-total">
            <div class="card-body p-3">
                <div class="d-flex align-items-center justify-content-between mb-2">
                    <span class="text-uppercase text-muted small fw-bold tracking-wider">Total Playlist Records</span>
                    <span class="avatar avatar-sm bg-primary-lt text-primary rounded-circle">
                        <i class="ti ti-list-check fs-2"></i>
                    </span>
                </div>
                <div class="h2 mb-1 fw-bold text-dark font-monospace" id="stat-total-records">
                    {{ number_format($statistics['total_records'] ?? 0) }}
                </div>
                <div class="text-muted small">
                    <span class="badge bg-green-lt text-green me-1">100% Calculated</span> in database
                </div>
            </div>
        </div>
    </div>

    <!-- Total Learning Hours -->
    <div class="col-sm-6 col-lg-3">
        <div class="card border-0 shadow-sm stat-card stat-card-calculated">
            <div class="card-body p-3">
                <div class="d-flex align-items-center justify-content-between mb-2">
                    <span class="text-uppercase text-muted small fw-bold tracking-wider">Total Actual Hours</span>
                    <span class="avatar avatar-sm bg-teal-lt text-teal rounded-circle">
                        <i class="ti ti-clock fs-2"></i>
                    </span>
                </div>
                <div class="h2 mb-1 fw-bold text-teal font-monospace" id="stat-total-hours">
                    {{ number_format($statistics['total_learning_hours'] ?? 0, 2) }} h
                </div>
                <div class="text-muted small">
                    Exact sum of actual learning hours
                </div>
            </div>
        </div>
    </div>

    <!-- Actual Tracking Records -->
    <div class="col-sm-6 col-lg-3">
        <div class="card border-0 shadow-sm stat-card stat-card-actual">
            <div class="card-body p-3">
                <div class="d-flex align-items-center justify-content-between mb-2">
                    <span class="text-uppercase text-muted small fw-bold tracking-wider">Actual Tracking</span>
                    <span class="avatar avatar-sm bg-green-lt text-green rounded-circle">
                        <i class="ti ti-device-analytics fs-2"></i>
                    </span>
                </div>
                <div class="h2 mb-1 fw-bold text-green font-monospace" id="stat-actual-tracking">
                    {{ number_format($statistics['actual_tracking_count'] ?? 0) }}
                </div>
                <div class="text-muted small">
                    <span class="badge bg-green-lt text-green">current_time &gt; 0</span> watched live
                </div>
            </div>
        </div>
    </div>

    <!-- Historical Fallback Records -->
    <div class="col-sm-6 col-lg-3">
        <div class="card border-0 shadow-sm stat-card stat-card-fallback">
            <div class="card-body p-3">
                <div class="d-flex align-items-center justify-content-between mb-2">
                    <span class="text-uppercase text-muted small fw-bold tracking-wider">Historical Fallback</span>
                    <span class="avatar avatar-sm bg-azure-lt text-azure rounded-circle">
                        <i class="ti ti-history fs-2"></i>
                    </span>
                </div>
                <div class="h2 mb-1 fw-bold text-azure font-monospace" id="stat-historical-fallback">
                    {{ number_format($statistics['historical_fallback_count'] ?? 0) }}
                </div>
                <div class="text-muted small">
                    <span class="badge bg-azure-lt text-azure">Fallback</span> master duration
                </div>
            </div>
        </div>
    </div>
</div>
