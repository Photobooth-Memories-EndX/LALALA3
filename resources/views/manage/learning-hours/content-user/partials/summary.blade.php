<div class="row row-deck g-3 mb-4">
    <div class="col-sm-6 col-xl-3">
        <div class="card stat-card stat-card-total border-0 shadow-sm h-100">
            <div class="card-body p-3 p-md-4">
                <div class="d-flex align-items-center justify-content-between mb-2">
                    <span class="text-uppercase small text-muted fw-bold tracking-wider">Total Content Users</span>
                    <span class="avatar avatar-sm bg-primary-lt text-primary rounded-circle">
                        <i class="ti ti-users fs-2"></i>
                    </span>
                </div>
                <div class="d-flex align-items-baseline justify-content-between">
                    <div class="display-6 fw-bold mb-0 text-primary" id="stat-total">{{ number_format($statistics['total'] ?? 0) }}</div>
                    <span class="text-muted small">records</span>
                </div>
            </div>
        </div>
    </div>

    <div class="col-sm-6 col-xl-3">
        <div class="card stat-card stat-card-calculated border-0 shadow-sm h-100">
            <div class="card-body p-3 p-md-4">
                <div class="d-flex align-items-center justify-content-between mb-2">
                    <span class="text-uppercase small text-muted fw-bold tracking-wider">Calculated</span>
                    <span class="avatar avatar-sm bg-teal-lt text-teal rounded-circle">
                        <i class="ti ti-circle-check fs-2"></i>
                    </span>
                </div>
                <div class="d-flex align-items-baseline justify-content-between">
                    <div class="display-6 fw-bold mb-0 text-teal" id="stat-calculated">{{ number_format($statistics['calculated'] ?? 0) }}</div>
                    <span class="badge bg-teal-lt text-teal font-monospace" id="stat-percentage">{{ number_format($statistics['percentage'] ?? 0, 1) }}%</span>
                </div>
            </div>
        </div>
    </div>

    <div class="col-sm-6 col-xl-3">
        <div class="card stat-card stat-card-remaining border-0 shadow-sm h-100">
            <div class="card-body p-3 p-md-4">
                <div class="d-flex align-items-center justify-content-between mb-2">
                    <span class="text-uppercase small text-muted fw-bold tracking-wider">Not Calculated</span>
                    <span class="avatar avatar-sm bg-yellow-lt text-yellow rounded-circle">
                        <i class="ti ti-clock-pause fs-2"></i>
                    </span>
                </div>
                <div class="d-flex align-items-baseline justify-content-between">
                    <div class="display-6 fw-bold mb-0 text-yellow" id="stat-remaining">{{ number_format($statistics['remaining'] ?? 0) }}</div>
                    <span class="text-muted small">pending</span>
                </div>
            </div>
        </div>
    </div>

    <div class="col-sm-6 col-xl-3">
        <div class="card stat-card stat-card-failed border-0 shadow-sm h-100">
            <div class="card-body p-3 p-md-4">
                <div class="d-flex align-items-center justify-content-between mb-2">
                    <span class="text-uppercase small text-muted fw-bold tracking-wider">Failed / Missing Content</span>
                    <span class="avatar avatar-sm bg-danger-lt text-danger rounded-circle">
                        <i class="ti ti-alert-triangle fs-2"></i>
                    </span>
                </div>
                <div class="d-flex align-items-baseline justify-content-between">
                    <div class="display-6 fw-bold mb-0 text-danger" id="stat-failed">{{ number_format($statistics['failed'] ?? 0) }}</div>
                    <span class="text-muted small">issues</span>
                </div>
            </div>
        </div>
    </div>
</div>
