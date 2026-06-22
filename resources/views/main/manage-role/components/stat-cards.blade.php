{{-- Summary cards (mengikuti style Monitoring Group). Data: $totalUsers, $totalDivision, $totalDepartment, $totalTeam --}}
<div class="row row-cards mb-3">

    <div class="col-sm-6 col-lg-3 mb-3">
        <div class="card owl-stat-card h-100">
            <div class="card-body">
                <div class="d-flex align-items-center">
                    <div class="owl-stat-icon bg-primary">
                        <i class="ti ti-users"></i>
                    </div>
                    <div class="owl-stat-content">
                        <div class="owl-stat-label">Total User</div>
                        <div class="owl-stat-number">{{ number_format($totalUsers) }}</div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="col-sm-6 col-lg-3 mb-3">
        <div class="card owl-stat-card h-100">
            <div class="card-body">
                <div class="d-flex align-items-center">
                    <div class="owl-stat-icon bg-azure">
                        <i class="ti ti-building-community"></i>
                    </div>
                    <div class="owl-stat-content">
                        <div class="owl-stat-label">Division</div>
                        <div class="owl-stat-number">{{ number_format($totalDivision) }}</div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="col-sm-6 col-lg-3 mb-3">
        <div class="card owl-stat-card h-100">
            <div class="card-body">
                <div class="d-flex align-items-center">
                    <div class="owl-stat-icon bg-success">
                        <i class="ti ti-building"></i>
                    </div>
                    <div class="owl-stat-content">
                        <div class="owl-stat-label">Department</div>
                        <div class="owl-stat-number">{{ number_format($totalDepartment) }}</div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="col-sm-6 col-lg-3 mb-3">
        <div class="card owl-stat-card h-100">
            <div class="card-body">
                <div class="d-flex align-items-center">
                    <div class="owl-stat-icon bg-purple">
                        <i class="ti ti-users-group"></i>
                    </div>
                    <div class="owl-stat-content">
                        <div class="owl-stat-label">Team</div>
                        <div class="owl-stat-number">{{ number_format($totalTeam) }}</div>
                    </div>
                </div>
            </div>
        </div>
    </div>

</div>
