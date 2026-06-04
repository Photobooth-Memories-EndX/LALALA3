<div class="row mb-4">

    <div class="col-md-6 col-lg-3 mb-3">
        <div class="card owl-stat-card h-100">
            <div class="card-body p-3">
                <div class="d-flex align-items-center">
                    <div class="owl-stat-icon bg-primary">
                        <i class="fas fa-clipboard-list"></i>
                    </div>

                    <div class="owl-stat-content">
                        <div class="owl-stat-label">Total Assessment</div>
                        <div class="owl-stat-number">
                            {{ method_exists($assesment, 'total') ? $assesment->total() : $assesment->count() }}
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="col-md-6 col-lg-3 mb-3">
        <div class="card owl-stat-card h-100">
            <div class="card-body p-3">
                <div class="d-flex align-items-center">
                    <div class="owl-stat-icon bg-success">
                        <i class="fas fa-check-circle"></i>
                    </div>

                    <div class="owl-stat-content">
                        <div class="owl-stat-label">Active</div>
                        <div class="owl-stat-number">
                            {{ $assesment->where('status', 'OK')->count() }}
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="col-md-6 col-lg-3 mb-3">
        <div class="card owl-stat-card h-100">
            <div class="card-body p-3">
                <div class="d-flex align-items-center">
                    <div class="owl-stat-icon bg-secondary">
                        <i class="fas fa-times-circle"></i>
                    </div>

                    <div class="owl-stat-content">
                        <div class="owl-stat-label">Inactive</div>
                        <div class="owl-stat-number">
                            {{ $assesment->where('status', 'NOT')->count() }}
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="col-md-6 col-lg-3 mb-3">
        <div class="card owl-stat-card h-100">
            <div class="card-body p-3">
                <div class="d-flex align-items-center">
                    <div class="owl-stat-icon bg-info">
                        <i class="fas fa-bullseye"></i>
                    </div>

                    <div class="owl-stat-content">
                        <div class="owl-stat-label">Avg Passing Grade</div>
                        <div class="owl-stat-number">
                            {{ number_format($assesment->avg('passing_grade') ?? 0, 0) }}
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

</div>