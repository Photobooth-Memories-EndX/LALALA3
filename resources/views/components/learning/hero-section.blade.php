@props([
    'totalLearning' => 0,
    'completedLearning' => 0,
    'ongoingLearning' => 0,
    'passedAssessment' => 0,
    'averageProgress' => 0,
])

<div class="card border-0 shadow-sm rounded-4 learning-hero mb-3">
    <div class="card-body p-4 p-lg-5">
        <div class="row g-4 g-lg-5 align-items-center">
            <div class="col-xl-5">
                <div class="d-inline-flex align-items-center gap-2 badge bg-white text-primary shadow-sm mb-3 px-3 py-2 rounded-pill">
                    <i class="ti ti-flame"></i>
                    <span>{{ $ongoingLearning }} kelas aktif</span>
                </div>

                <h3 class="display-6 mb-2 text-white">
                    Continue Your Learning
                </h3>

                <p class="mb-3 text-white-75">
                    Complete ongoing material and increase your learning progress.
                </p>

                <a href="#learning-tab-content" class="btn btn-light btn-lg text-primary fw-semibold learning-hero-cta">
                    Start Learning
                </a>
                
            </div>

            <div class="col-xl-7">
                {{-- <div class="learning-hero-highlight mb-3">
                    <div class="learning-hero-highlight-label">Average Progress</div>
                    <div class="learning-hero-highlight-value">
                        {{ $averageProgress }}<span class="learning-hero-highlight-unit">%</span>
                    </div>
                    <div class="learning-hero-highlight-bar">
                        <div class="learning-hero-highlight-bar-fill"
                             style="width: {{ max(0, min(100, (int) $averageProgress)) }}%"></div>
                    </div>
                </div> --}}

                <div class="learning-hero-stats">
                    <div class="learning-mini-stat">
                        <div class="learning-mini-stat-label">Total Learning</div>
                        <div class="learning-mini-stat-value">{{ $totalLearning }}</div>
                    </div>

                    <div class="learning-mini-stat">
                        <div class="learning-mini-stat-label">Completed</div>
                        <div class="learning-mini-stat-value">{{ $completedLearning }}</div>
                    </div>

                    <div class="learning-mini-stat">
                        <div class="learning-mini-stat-label">On Going</div>
                        <div class="learning-mini-stat-value">{{ $ongoingLearning }}</div>
                    </div>

                    <div class="learning-mini-stat">
                        <div class="learning-mini-stat-label">Assessment Pass</div>
                        <div class="learning-mini-stat-value">{{ $passedAssessment }}</div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
