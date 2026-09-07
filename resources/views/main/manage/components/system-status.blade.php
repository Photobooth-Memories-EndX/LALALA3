<div class="card border-0 shadow-sm h-100 admin-system-card">
    <div class="card-body p-4">

        <h3 class="mb-1">
            Monthly Upload
        </h3>

        <div class="text-muted small mb-3">
            Monthly Upload Activity
        </div>

        <div class="d-flex justify-content-between align-items-center mb-2.5 py-1">
            <span class="upload-label">Content</span>
            <span class="upload-pill">+{{ $monthlyUploads['content'] ?? 0 }}</span>
        </div>

        <div class="d-flex justify-content-between align-items-center mb-2.5 py-1">
            <span class="upload-label">Assessment</span>
            <span class="upload-pill">+{{ $monthlyUploads['assessment'] ?? 0 }}</span>
        </div>

        <div class="d-flex justify-content-between align-items-center mb-2.5 py-1">
            <span class="upload-label">Playlist</span>
            <span class="upload-pill">+{{ $monthlyUploads['playlist'] ?? 0 }}</span>
        </div>

        <div class="d-flex justify-content-between align-items-center mb-3 py-1">
            <span class="upload-label">News</span>
            <span class="upload-pill">+{{ $monthlyUploads['news'] ?? 0 }}</span>
        </div>

        <hr class="my-4 admin-section-divider">

        <h3 class="mb-1">
            Top Contributor
        </h3>

        <div class="text-muted small mb-3">
            Top Content Authors in the Last 6 Months
        </div>

        <div class="contributor-list">
            @forelse ($topContributors as $contributor)
                <div class="d-flex justify-content-between align-items-center mb-2.5 py-1">
                    <div class="text-truncate me-2 min-w-0 flex-grow-1 contributor-name" title="{{ $contributor->author }}">
                        <i class="ti ti-user me-1.5 text-primary"></i>
                        <span>{{ $contributor->author }}</span>
                    </div>

                    <span class="badge bg-primary-lt px-2.5 py-1 flex-shrink-0">
                        {{ $contributor->total_upload }} {{ \Illuminate\Support\Str::plural('upload', $contributor->total_upload) }}
                    </span>
                </div>
            @empty
                <div class="text-muted small py-2">
                    Belum ada contributor dalam 6 bulan terakhir.
                </div>
            @endforelse
        </div>

    </div>
</div>
