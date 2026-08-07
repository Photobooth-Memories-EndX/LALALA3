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
    $isNotStarted     = $hasDeadline && $fromDate && now()->lt($fromDate);
    // thumbnail_url is null when missing, external URL, or file not on disk
    $thumbUrl         = $playlist->thumbnail_url;

    $durationSec       = (int) ($playlist->total_duration_seconds ?? 0);
    $formattedDuration = $playlist->duration ?? ($durationSec > 0 ? sprintf('%02d:%02d:%02d', floor($durationSec / 3600), floor(($durationSec % 3600) / 60), $durationSec % 60) : '00:00:00');
    $learningHours     = $playlist->learning_hours ?? ($durationSec > 0 ? round($durationSec / 3600, 1) : 0);
    
    // Identik dengan view-hero.blade.php ($content->author)
    $authorName        = $firstContent->author ?? $playlist->author ?? 'Training Team';
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

            <span class="badge bg-green position-absolute top-0 end-0 m-3">
                Playlist
            </span>

        </div>

        <div class="card-body">

            <div class="small text-muted mb-1 d-flex flex-wrap align-items-center gap-2">
                <span>
                    <i class="ti ti-bookmarks me-1"></i>{{ $totalContent }} Content
                </span>
                <span class="text-muted">&bull;</span>
                <span>
                    <i class="ti ti-clock me-1"></i>Duration: {{ $formattedDuration }}
                </span>
            </div>

            <div class="playlist-title mb-1">
                {{ $playlist->title }}
            </div>

            {{-- Keterangan Author / Team --}}
            <div class="small text-muted mb-2 d-flex align-items-center">
                <i class="ti ti-users me-1"></i>
                <span>Author : {{ $authorName }}</span>
            </div>

            <div class="small text-muted mb-2 d-flex flex-wrap align-items-center gap-2">
                <span>
                    <i class="ti ti-hourglass me-1"></i>Learning Hours: {{ $learningHours }} {{ $learningHours == 1 ? 'Hour' : 'Hours' }}
                </span>
                <span class="text-muted">&bull;</span>
                <span>
                    <i class="ti ti-calendar me-1"></i>Published on {{ date('d M Y', strtotime($playlist->created_at)) }}
                </span>
            </div>

            @if($hasDeadline && $fromDate && $toDate)
                <div class="mb-3">
                    @if($isExpired)
                        <span class="badge bg-danger-lt text-danger">
                            <i class="ti ti-clock-x me-1"></i>
                            Expired: {{ $fromDate->format('d M Y') }} – {{ $toDate->format('d M Y') }}
                        </span>
                    @elseif($isNotStarted)
                        <span class="badge bg-secondary-lt text-secondary">
                            <i class="ti ti-calendar-time me-1"></i>
                            Belum Mulai: {{ $fromDate->format('d M Y') }} – {{ $toDate->format('d M Y') }}
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

                @foreach($playlist->trainingplandetail->take(1) as $detail)
                    @if($detail->content)
                        <div class="mb-1">
                            • {{ Str::limit($detail->content->title, 45) }}
                        </div>
                    @endif
                @endforeach

                @if($totalContent > 1)
                    <div class="text-primary mt-1">
                        + {{ $totalContent - 1 }} more contents
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

            @if($isExpired)
                <button class="btn btn-danger-lt w-100 rounded-pill" disabled>
                    <i class="ti ti-lock me-1"></i>
                    Akses Expired
                </button>
            @elseif($isNotStarted)
                <button class="btn btn-secondary w-100 rounded-pill" disabled>
                    <i class="ti ti-calendar-time me-1"></i>
                    Belum Tersedia
                </button>
            @elseif($firstContent)
                <a href="{{ route('playlist.user.view', [$playlist->slug, $firstContent->slug]) }}"
                   class="btn btn-primary w-100 rounded-pill">
                    Continue Learning
                    <i class="ti ti-arrow-right ms-1"></i>
                </a>
            @else
                <button class="btn btn-secondary w-100 rounded-pill" disabled>
                    No Content Available
                </button>
            @endif

        </div>

    </div>

</div>