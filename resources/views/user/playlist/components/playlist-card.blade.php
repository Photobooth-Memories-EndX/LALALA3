@php
    // All values pre-computed in PlaylistUserController@index()
    // No DB queries, no File::exists(), no progress loops in Blade
    $totalContent     = $stats['totalContent'] ?? 0;
    $completedContent = $stats['completedContent'] ?? 0;
    $progress         = $stats['progress'] ?? 0;
    $firstContent     = $stats['firstContent'] ?? null;
    $hasDeadline      = $stats['has_deadline'] ?? false;
    $fromDate         = $stats['from_date'] ?? null;
    $toDate           = $stats['to_date'] ?? null;
    $isExpired        = $hasDeadline && $toDate && now()->gt($toDate);
    // thumbnail_url is null when missing, external URL, or file not on disk
    $thumbUrl         = $playlist->thumbnail_url;
@endphp

<div class="col-xl-4 col-lg-6 mb-4 playlist-item">

    <div class="card playlist-card border-0 shadow-sm rounded-4 h-100 overflow-hidden">

        <div class="position-relative">

            @if($thumbUrl)
                <img src="{{ $thumbUrl }}"
                     class="playlist-card-image"
                     alt="{{ $playlist->title }}"
                     onerror="this.onerror=null; this.style.display='none'; this.nextElementSibling.style.display='flex';">
                <div class="playlist-card-placeholder" style="display:none;">
                    <div class="playlist-card-placeholder-icon">
                        <i class="ti ti-playlist"></i>
                    </div>
                    <div class="playlist-card-placeholder-text">Learning Playlist</div>
                </div>
            @else
                <div class="playlist-card-placeholder">
                    <div class="playlist-card-placeholder-icon">
                        <i class="ti ti-playlist"></i>
                    </div>
                    <div class="playlist-card-placeholder-text">Learning Playlist</div>
                </div>
            @endif

            <span class="badge bg-primary position-absolute top-0 end-0 m-3">
                Playlist
            </span>

        </div>

        <div class="card-body">

            <div class="small text-muted mb-2">
                <i class="ti ti-book-2 me-1"></i>
                {{ $totalContent }} Learning Materials
            </div>

            <div class="playlist-title mb-2">
                {{ $playlist->title }}
            </div>

            <div class="text-muted small mb-2">
                <i class="ti ti-calendar me-1"></i>
                Published on {{ date('d M Y', strtotime($playlist->created_at)) }}
            </div>

            @if($hasDeadline && $fromDate && $toDate)
                <div class="mb-3">
                    @if($isExpired)
                        <span class="badge bg-danger-lt text-danger">
                            <i class="ti ti-clock-x me-1"></i>
                            Expired: {{ $fromDate->format('d M Y') }} – {{ $toDate->format('d M Y') }}
                        </span>
                    @else
                        <span class="badge bg-warning-lt text-warning">
                            <i class="ti ti-calendar-due me-1"></i>
                            Deadline: {{ $fromDate->format('d M Y') }} – {{ $toDate->format('d M Y') }}
                        </span>
                    @endif
                </div>
            @endif

            <div class="playlist-content-list small text-muted">

                @foreach($playlist->trainingplandetail->take(3) as $detail)
                    @if($detail->content)
                        <div class="mb-1">
                            • {{ Str::limit($detail->content->title, 45) }}
                        </div>
                    @endif
                @endforeach

                @if($totalContent > 3)
                    <div class="text-primary mt-1">
                        + {{ $totalContent - 3 }} more materials
                    </div>
                @endif

            </div>

            <div class="mt-3">
                <div class="progress progress-sm mb-2">
                    <div class="progress-bar bg-primary"
                         style="width: {{ $progress }}%">
                    </div>
                </div>

                <div class="d-flex justify-content-between align-items-center small">
                    <span class="text-primary fw-semibold">
                        {{ $progress }}% Completed
                    </span>

                    <span class="text-muted">
                        {{ $completedContent }} of {{ $totalContent }} finished
                    </span>
                </div>
            </div>

        </div>

        <div class="card-footer bg-transparent border-0">

            @if($firstContent)
                <a href="{{ route('playlist.user.view', [$playlist->slug, $firstContent->slug]) }}"
                   class="btn btn-primary w-100 rounded-pill">
                    Continue Learning
                    <i class="ti ti-arrow-right ms-1"></i>
                </a>
            @else
                <button class="btn btn-secondary w-100 rounded-pill" disabled>
                    No Material Available
                </button>
            @endif

        </div>

    </div>

</div>
