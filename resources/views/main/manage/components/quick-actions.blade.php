<div class="card border-0 shadow-sm mt-4">
    <div class="card-body">

        <div class="d-flex justify-content-between align-items-center mb-3">
            <div>
                <h3 class="mb-1">Quick Actions</h3>
                <div class="text-muted small">
                    Akses cepat untuk membuat data baru.
                </div>
            </div>
        </div>

        <div class="d-flex flex-wrap gap-2">

            <a href="{{ url('manage/content/create') }}"
               class="btn btn-primary">
                <i class="ti ti-plus me-1"></i>
                Add Content
            </a>

            <a href="{{ url('manage/news/create') }}"
               class="btn btn-outline-primary">
                <i class="ti ti-news me-1"></i>
                Add News
            </a>

            <a href="{{ url('manage/assesment/create') }}"
               class="btn btn-outline-primary">
                <i class="ti ti-clipboard-check me-1"></i>
                Add Assessment
            </a>

            <a href="{{ url('manage/playlist/create') }}"
               class="btn btn-outline-primary">
                <i class="ti ti-route me-1"></i>
                Add Playlist
            </a>

            <a href="{{ url('manage/classification/create') }}"
               class="btn btn-outline-primary">
                <i class="ti ti-category me-1"></i>
                Add Category
            </a>

        </div>

    </div>
</div>