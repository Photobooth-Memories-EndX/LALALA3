@php
    $progress = (int) ($content_user->progress ?? 0);
    $bookmarkCount = $bookmarkCount ?? 0;
    $discussionCount = $discussionCount ?? 0;
    $isBookmarked = $isBookmarked ?? false;
@endphp

<div class="d-flex flex-column gap-3 content-sidebar-stack">

    <div class="card border-0 shadow-sm rounded-4 glass-surface content-sidebar-card">
        <div class="card-body p-4">

            <div class="d-flex justify-content-between align-items-center mb-3">
                <div>
                    <div class="text-muted small fw-semibold">Your Progress</div>
                    <div class="fw-bold fs-2 text-primary">{{ $progress }}%</div>
                </div>

                <span class="badge rounded-pill bg-primary-lt text-primary px-3 py-2">
                    <i class="ti ti-rocket me-1"></i>
                    In progress
                </span>
            </div>

            <div class="progress progress-sm mb-3 sidebar-progress">
                <div class="progress-bar bg-primary" style="width: {{ $progress }}%"></div>
            </div>

            <div class="d-flex gap-2 flex-wrap">
                <a href="#player" class="btn btn-primary flex-fill">
                    Continue Learning
                    <i class="ti ti-player-play ms-1"></i>
                </a>

                <span class="btn btn-outline-secondary rounded-pill pe-none">
                    {{ $content->duration ?? 'Self-paced' }}
                </span>
            </div>

        </div>
    </div>

    <div class="card border-0 shadow-sm rounded-4 text-center glass-surface content-sidebar-card">
        <div class="card-body p-4">

            <div class="avatar avatar-xl mx-auto mb-3 rounded-circle bg-primary-lt text-primary shadow-sm">
                <i class="ti ti-user-circle fs-1"></i>
            </div>

            <div class="d-flex justify-content-center mb-2">
                <span class="badge rounded-pill bg-success-lt text-success">Instructor</span>
            </div>

            <h3 class="mb-1">
                {{ $content->author ?? 'Training Team' }}
            </h3>

            <div class="text-muted small mb-3">
                Content Author / Instructor
            </div>

            <div class="d-flex flex-wrap justify-content-center gap-2">
                <span class="badge rounded-pill bg-primary-lt text-primary px-3 py-2">
                    <i class="ti ti-badge me-1"></i>
                    All Level
                </span>

                <span class="badge rounded-pill bg-secondary-lt text-secondary px-3 py-2">
                    <i class="ti ti-clock me-1"></i>
                    {{ $content->duration ?? 'Flexible' }}
                </span>
            </div>

        </div>
    </div>

    <div class="card border-0 shadow-sm rounded-4 glass-surface content-sidebar-card">
        <div class="card-body p-4">

            <div class="d-flex align-items-center justify-content-between mb-3">
                <h3 class="card-title mb-0">
                    <i class="ti ti-chart-bar text-primary me-1"></i>
                    Content Stats
                </h3>

                @if($isBookmarked)
                    <span class="badge rounded-pill bg-primary-lt text-primary">Saved</span>
                @endif
            </div>

            <div class="row g-2">
                <div class="col-6">
                    <div class="sidebar-stat-card">
                        <div class="text-muted small">Views</div>
                        <div class="fw-bold fs-3">{{ number_format($content->views ?? 0) }}</div>
                    </div>
                </div>

                <div class="col-6">
                    <div class="sidebar-stat-card">
                        <div class="text-muted small">Comments</div>
                        <div class="fw-bold fs-3">{{ number_format($discussionCount) }}</div>
                    </div>
                </div>

                <div class="col-6">
                    <div class="sidebar-stat-card">
                        <div class="text-muted small">Saved</div>
                        <div class="fw-bold fs-3">{{ number_format($bookmarkCount) }}</div>
                    </div>
                </div>

                <div class="col-6">
                    <div class="sidebar-stat-card">
                        <div class="text-muted small">Type</div>
                        <div class="fw-bold fs-3 text-truncate">{{ $content->type }}</div>
                    </div>
                </div>
            </div>

        </div>
    </div>

    @if ($assesment)
        <div class="card border-0 shadow-sm rounded-4 glass-surface content-sidebar-card">
            <div class="card-body p-4">

                <h3 class="card-title mb-3">
                    <i class="ti ti-clipboard-check text-primary me-1"></i>
                    Assessment
                </h3>

                <div class="d-flex justify-content-between mb-2">
                    <span class="text-muted">Passing Grade</span>
                    <strong>{{ $assesment->passing_grade }}</strong>
                </div>

                <div class="d-flex justify-content-between mb-2">
                    <span class="text-muted">Latest Score</span>
                    <strong>{{ $assessmentLatestScores ?? 0 }}</strong>
                </div>

                <div class="d-flex justify-content-between mb-3">
                    <span class="text-muted">Attempt</span>
                    <strong>{{ $assessmentCount ?? 0 }}/{{ 1 + $assesment->total_remedials }}</strong>
                </div>

                <a href="{{ route('assesment.user.view', $assesment->slug) }}"
                   class="btn btn-primary w-100 {{ $content_user->progress < 100 || ($assessmentCount >= 1 + $assesment->total_remedials && $assessmentLatestScores < $assesment->passing_grade) || $assessmentLatestScores >= $assesment->passing_grade ? 'disabled' : '' }}">
                    Start Assessment
                </a>

            </div>
        </div>
    @endif

    <div class="card border-0 shadow-sm rounded-4 text-white tips-card">
        <div class="card-body p-4">
            <h3 class="text-white mb-2">
                <i class="ti ti-bulb me-1"></i>
                Learning Tips
            </h3>
            <p class="mb-0">
                Selesaikan materi terlebih dahulu sebelum membuka assessment.
            </p>
        </div>
    </div>

</div>