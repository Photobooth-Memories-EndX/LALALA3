<div class="card border-0 shadow-sm h-100">
    <div class="card-body">

        <h3 class="mb-3">
            Monthly Upload
        </h3>

        <div class="text-muted small mb-3">
            Aktivitas upload bulan ini.
        </div>

        <div class="d-flex justify-content-between mb-2">
            <span class="text-muted">Content</span>
            <strong>+{{ $monthlyUploads['content'] ?? 0 }}</strong>
        </div>

        <div class="d-flex justify-content-between mb-2">
            <span class="text-muted">Assessment</span>
            <strong>+{{ $monthlyUploads['assessment'] ?? 0 }}</strong>
        </div>

        <div class="d-flex justify-content-between mb-2">
            <span class="text-muted">Playlist</span>
            <strong>+{{ $monthlyUploads['playlist'] ?? 0 }}</strong>
        </div>

        <div class="d-flex justify-content-between mb-3">
            <span class="text-muted">News</span>
            <strong>+{{ $monthlyUploads['news'] ?? 0 }}</strong>
        </div>

        <hr>

        <h3 class="mb-3">
            Top Contributor
        </h3>

        <div class="text-muted small mb-3">
            Author content terbanyak dalam 6 bulan terakhir.
        </div>

        @forelse ($topContributors as $contributor)
            <div class="d-flex justify-content-between align-items-center mb-2">
                <div class="text-truncate">
                    <i class="ti ti-user me-1 text-primary"></i>
                    {{ $contributor->author }}
                </div>

                <span class="badge bg-primary-lt">
                    {{ $contributor->total_upload }} uploads
                </span>
            </div>
        @empty
            <div class="text-muted small">
                Belum ada contributor dalam 6 bulan terakhir.
            </div>
        @endforelse

    </div>
</div>