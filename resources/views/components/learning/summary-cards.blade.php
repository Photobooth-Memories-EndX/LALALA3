@props([
    'totalLearning' => 0,
    'completedLearning' => 0,
    'ongoingLearning' => 0,
    'passedAssessment' => 0,
])

<div class="row g-3 mb-3">
    <div class="col-sm-6 col-lg-3">
        <div class="card border-0 shadow-sm rounded-4">
            <div class="card-body">
                <div class="text-muted">Total Learning</div>
                <div class="h2 mb-0">{{ $totalLearning }}</div>
            </div>
        </div>
    </div>

    <div class="col-sm-6 col-lg-3">
        <div class="card border-0 shadow-sm rounded-4">
            <div class="card-body">
                <div class="text-muted">Completed</div>
                <div class="h2 mb-0 text-success">{{ $completedLearning }}</div>
            </div>
        </div>
    </div>

    <div class="col-sm-6 col-lg-3">
        <div class="card border-0 shadow-sm rounded-4">
            <div class="card-body">
                <div class="text-muted">On Going</div>
                <div class="h2 mb-0 text-primary">{{ $ongoingLearning }}</div>
            </div>
        </div>
    </div>

    <div class="col-sm-6 col-lg-3">
        <div class="card border-0 shadow-sm rounded-4">
            <div class="card-body">
                <div class="text-muted">Assessment Pass</div>
                <div class="h2 mb-0 text-info">{{ $passedAssessment }}</div>
            </div>
        </div>
    </div>
</div>