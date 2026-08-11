<div class="row g-3">

    <div class="col-6">
        <div class="card bg-primary-lt border-0 h-100">
            <div class="card-body text-center">
                <div class="text-primary mb-1">
                    <i class="ti ti-notebook fs-2"></i>
                </div>

                <h2 class="mb-0">
                    {{ $totalLearning }}
                </h2>

                <div class="text-muted small">
                    Total Learning
                </div>
            </div>
        </div>
    </div>

    <div class="col-6">
        <div class="card bg-yellow-lt border-0 h-100">
            <div class="card-body text-center">
                <div class="text-yellow mb-1">
                    <i class="ti ti-refresh fs-2"></i>
                </div>

                <h2 class="mb-0">
                    {{ $totalOngoing }}
                </h2>

                <div class="text-muted small">
                    Ongoing
                </div>
            </div>
        </div>
    </div>

    <div class="col-6">
        <div class="card bg-green-lt border-0 h-100">
            <div class="card-body text-center">
                <div class="text-green mb-1">
                    <i class="ti ti-circle-check fs-2"></i>
                </div>

                <h2 class="mb-0">
                    {{ $totalFinished }}
                </h2>

                <div class="text-muted small">
                    Finished
                </div>
            </div>
        </div>
    </div>

    <div class="col-6">
        <div class="card bg-purple-lt border-0 h-100">
            <div class="card-body text-center">
                <div class="text-purple mb-1">
                    <i class="ti ti-chart-donut fs-2"></i>
                </div>

                <h2 class="mb-0">
                    {{ $percentage }}%
                </h2>

                <div class="text-muted small">
                    Completion
                </div>
            </div>
        </div>
    </div>

</div>