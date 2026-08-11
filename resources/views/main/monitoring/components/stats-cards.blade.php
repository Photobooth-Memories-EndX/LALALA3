{{-- Props: $stats [total_user, avg_progress, finished_content, assessment_count] --}}
<div class="row mb-3">

    <div class="col-md-6 col-lg-3 mb-3">
        <div class="card owl-stat-card h-100">
            <div class="card-body">
                <div class="d-flex align-items-center">
                    <div class="owl-stat-icon bg-primary">
                        <i class="fas fa-users"></i>
                    </div>
                    <div class="owl-stat-content">
                        <div class="owl-stat-label">Total User</div>
                        <div class="owl-stat-number">{{ number_format($stats['total_user']) }}</div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="col-md-6 col-lg-3 mb-3">
        <div class="card owl-stat-card h-100">
            <div class="card-body">
                <div class="d-flex align-items-center">
                    <div class="owl-stat-icon bg-azure">
                        <i class="fas fa-chart-line"></i>
                    </div>
                    <div class="owl-stat-content">
                        <div class="owl-stat-label">Average Progress</div>
                        <div class="owl-stat-number">{{ $stats['avg_progress'] }}%</div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="col-md-6 col-lg-3 mb-3">
        <div class="card owl-stat-card h-100">
            <div class="card-body">
                <div class="d-flex align-items-center">
                    <div class="owl-stat-icon bg-success">
                        <i class="fas fa-check-circle"></i>
                    </div>
                    <div class="owl-stat-content">
                        <div class="owl-stat-label">Completed Content</div>
                        <div class="owl-stat-number">{{ number_format($stats['finished_content']) }}</div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="col-md-6 col-lg-3 mb-3">
        <div class="card owl-stat-card h-100">
            <div class="card-body">
                <div class="d-flex align-items-center">
                    <div class="owl-stat-icon bg-purple">
                        <i class="fas fa-clipboard-list"></i>
                    </div>
                    <div class="owl-stat-content">
                        <div class="owl-stat-label">Total Assessment</div>
                        <div class="owl-stat-number">{{ number_format($stats['assessment_count']) }}</div>
                    </div>
                </div>
            </div>
        </div>
    </div>

</div>
