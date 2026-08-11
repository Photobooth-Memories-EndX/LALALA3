{{-- Props: $summary --}}
<div class="row mb-3">

    <div class="col-6 col-md-4 col-lg mb-3">
        <div class="card owl-stat-card h-100">
            <div class="card-body">
                <div class="owl-stat-label">Total Learning</div>
                <div class="owl-stat-number">{{ number_format($summary['total_content']) }}</div>
            </div>
        </div>
    </div>

    <div class="col-6 col-md-4 col-lg mb-3">
        <div class="card owl-stat-card h-100">
            <div class="card-body">
                <div class="owl-stat-label">Finished Learning</div>
                <div class="owl-stat-number">{{ number_format($summary['finished_content']) }}</div>
            </div>
        </div>
    </div>

    <div class="col-6 col-md-4 col-lg mb-3">
        <div class="card owl-stat-card h-100">
            <div class="card-body">
                <div class="owl-stat-label">Average Progress</div>
                <div class="owl-stat-number">{{ $summary['avg_progress'] }}%</div>
            </div>
        </div>
    </div>

    <div class="col-6 col-md-4 col-lg mb-3">
        <div class="card owl-stat-card h-100">
            <div class="card-body">
                <div class="owl-stat-label">Assessment</div>
                <div class="owl-stat-number">{{ number_format($summary['total_assessment']) }}</div>
            </div>
        </div>
    </div>

    <div class="col-6 col-md-4 col-lg mb-3">
        <div class="card owl-stat-card h-100">
            <div class="card-body">
                <div class="owl-stat-label">Average Score</div>
                <div class="owl-stat-number">{{ $summary['avg_assessment_score'] }}</div>
            </div>
        </div>
    </div>

</div>

<div class="row mb-3">
    <div class="col-md-4 mb-2">
        <div class="card owl-main-card h-100">
            <div class="card-body py-3">
                <div class="text-muted small">Last Learning</div>
                <div class="fw-bold text-truncate" title="{{ $summary['last_learning'] }}">{{ $summary['last_learning'] }}</div>
            </div>
        </div>
    </div>
    <div class="col-md-4 mb-2">
        <div class="card owl-main-card h-100">
            <div class="card-body py-3">
                <div class="text-muted small">Favorite Category</div>
                <div class="fw-bold text-truncate" title="{{ $summary['favorite_category'] }}">{{ $summary['favorite_category'] }}</div>
            </div>
        </div>
    </div>
    <div class="col-md-4 mb-2">
        <div class="card owl-main-card h-100">
            <div class="card-body py-3">
                <div class="text-muted small">Learning Hours</div>
                <div class="fw-bold">{{ $summary['learning_hours'] }} jam</div>
            </div>
        </div>
    </div>
</div>
