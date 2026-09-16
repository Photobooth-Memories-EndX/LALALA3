<div class="card border-0 shadow-sm mb-4 admin-quick-actions-card">
    <div class="card-body p-4">

        <div class="d-flex justify-content-between align-items-center mb-3">
            <div>
                <h3 class="mb-1">Quick Actions</h3>
                <div class="text-muted small">
                    Quick access to create new learning resources and system data.
                </div>
            </div>
        </div>

        <div class="d-flex flex-wrap gap-2">

            <a href="{{ url('manage/content/create') }}"
               class="btn btn-primary px-3 py-2 fw-semibold">
                <i class="ti ti-plus me-1.5"></i>
                Add Content
            </a>

            <a href="{{ url('manage/news/create') }}"
               class="btn btn-outline-primary px-3 py-2 fw-semibold">
                <i class="ti ti-news me-1.5"></i>
                Add News
            </a>

            <a href="{{ url('manage/assesment/create') }}"
               class="btn btn-outline-primary px-3 py-2 fw-semibold">
                <i class="ti ti-clipboard-check me-1.5"></i>
                Add Assessment
            </a>

            <a href="{{ url('manage/playlist/create') }}"
               class="btn btn-outline-primary px-3 py-2 fw-semibold">
                <i class="ti ti-route me-1.5"></i>
                Add Playlist
            </a>

            <a href="{{ url('manage/classification/create') }}"
               class="btn btn-outline-primary px-3 py-2 fw-semibold">
                <i class="ti ti-category me-1.5"></i>
                Add Category
            </a>

        </div>

    </div>
</div>
