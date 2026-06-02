<div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3 mb-4">
    <div>
        <div class="text-uppercase text-muted small fw-semibold">Manage Learning</div>
        <h1 class="page-title mb-1">{{ $title ?? 'Learning' }}</h1>
        <div class="text-muted">Monitor progress, status, and completion without loading the full dataset at once.</div>
    </div>

    <div class="d-flex gap-2">
        <a href="{{ route('learning.manage.all') }}" class="btn btn-outline-primary">
            <i class="ti ti-layout-grid me-1"></i>
            All Learning
        </a>
        <a href="{{ route('manage.dashboard') }}" class="btn btn-outline-secondary">
            <i class="ti ti-arrow-left me-1"></i>
            Back
        </a>
    </div>
</div>