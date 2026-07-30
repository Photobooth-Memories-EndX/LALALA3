@props([
    'cardType' => 'content',
    'title' => '-',
    'slug' => null,
    'classification' => '-',
    'duration' => '-',
    'type' => 'Content',
    'progress' => 0,
    'program' => 'optional',
    'month' => null,
    'year' => 'unknown',
    'publishedAt' => null,
    'deadlineAt' => null,
    'active' => '-',
    'statusLabel' => 'ongoing',
    'thumbnail' => null,
    // Nama Playlist asal content (badge kecil pada card Content). Kosong = tanpa badge.
    'playlistNames' => null,
    // Khusus card Playlist (Training Plan).
    'moduleCount' => 0,
    'completedCount' => 0,
    'continueUrl' => null,
])

@php
    $cardType = $cardType ?? 'content';
    $isPlaylist = $cardType === 'playlist';

    $title = $title ?? '-';
    $progress = (int) ($progress ?? 0);

    if ($isPlaylist) {
        // Card Playlist memakai desain yang sama seperti Video/Document, hanya berbeda konten.
        $typeLabel       = 'Playlist';
        $placeholderIcon = 'ti ti-playlist';
        $typeBadgeClass  = 'bg-green';
        $moduleCount     = (int) ($moduleCount ?? 0);
        $completedCount  = (int) ($completedCount ?? 0);
        $continueUrl     = !blank($continueUrl) ? $continueUrl : null;
        // Thumbnail playlist; jika kosong dipakai placeholder default Playlist di bawah.
        $thumbnail       = $thumbnail ?? null;
    } else {
        $playlistNames = collect($playlistNames ?? [])->filter()->values();
        $slug = $slug ?? null;
        $classification = $classification ?? '-';
        $duration = $duration ?? '-';
        $type = $type ?? 'Content';
        $program = strtolower($program ?? 'optional');
        $month = $month ?? null;
        $year = $year ?? 'unknown';
        $publishedDate = filled($publishedAt)
            ? \Carbon\Carbon::parse($publishedAt)
            : null;
        $deadlineDate = filled($deadlineAt)
            ? \Carbon\Carbon::parse($deadlineAt)->startOfDay()
            : null;
        $active = $active ?? '-';
        $statusLabel = $statusLabel ?? 'ongoing';
        $thumbnail = $thumbnail ?? null;

        $typeLabel = ucwords(str_replace('_', ' ', $type));
        $typeLower = strtolower($typeLabel);

        if (str_contains($typeLower, 'video') || str_contains($typeLower, 'youtube')) {
            $placeholderIcon = 'ti ti-video';
            $typeBadgeClass = 'bg-yellow text-white';
        } elseif (
            str_contains($typeLower, 'pdf')
            || str_contains($typeLower, 'doc')
            || str_contains($typeLower, 'sop')
            || str_contains($typeLower, 'regulation')
        ) {
            $placeholderIcon = 'ti ti-file-description';
            $typeBadgeClass = 'bg-cyan';
        } elseif ($typeLower === 'content') {
            $placeholderIcon = 'ti ti-book';
            $typeBadgeClass = 'bg-red';
        } else {
            $placeholderIcon = 'ti ti-book';
            $typeBadgeClass = 'bg-blue text-white';
        }

        $slugUrl = !blank($slug) ? route('content.user.view', $slug) : null;
    }
@endphp

<div class="card border-0 shadow-sm rounded-4 overflow-hidden h-100 learning-modern-card">
    <div class="position-relative">
        @if ($thumbnail)
            <img src="{{ $thumbnail }}"
                 alt="{{ $title }}"
                 class="w-100 learning-thumb"
                 loading="lazy"
                 decoding="async">
        @else
            <div class="learning-placeholder">
                <div class="text-center text-primary">
                    <div class="avatar avatar-xl bg-white text-primary shadow-sm mb-2 mx-auto">
                        <i class="{{ $placeholderIcon }} fs-1"></i>
                    </div>

                    <div class="fw-semibold small text-uppercase">
                        {{ $typeLabel }}
                    </div>
                </div>
            </div>
        @endif

        <div class="position-absolute top-0 start-0 p-3 d-flex flex-column gap-2">
            <span class="badge {{ $typeBadgeClass }} shadow-sm">
                {{ $typeLabel }}
            </span>

            <span class="badge bg-azure-lt text-azure border border-1 border-white">
                Sedang Berlangsung
            </span>
        </div>

        <div class="position-absolute top-0 end-0 p-3">
            <span class="badge bg-dark text-white bg-opacity-50 shadow-sm">
                @if ($isPlaylist)
                    {{ $moduleCount }} Modules
                @else
                    {{ $duration }}
                @endif
            </span>
        </div>
    </div>

    <div class="card-body d-flex flex-column">
        <div class="mb-2">
            <h3 class="card-title mb-2 line-clamp-2 learning-card-title">
                {{ $title }}
            </h3>

            <div class="text-muted small line-clamp-2">
                @if ($isPlaylist)
                    {{ $moduleCount }} Modules
                @else
                    {{ $classification }}
                @endif
            </div>
        </div>

        @if ($isPlaylist)
            <div class="d-flex flex-wrap align-items-center gap-2 mb-3">
                <span class="badge bg-teal-lt text-teal">
                    <i class="ti ti-playlist me-1"></i>Playlist
                </span>
            </div>
        @else
            <div class="d-grid gap-1 small text-muted mb-3">
                <div class="d-flex align-items-center gap-2">
                    <i class="ti ti-calendar-plus flex-shrink-0"></i>
                    <span>Published: {{ $publishedDate ? $publishedDate->format('d M Y') : '-' }}</span>
                </div>
                <div class="d-flex align-items-center gap-2">
                    <i class="ti ti-calendar-due flex-shrink-0"></i>
                    <span>Deadline: {{ $deadlineDate ? $deadlineDate->format('d M Y') : 'No deadline' }}</span>
                </div>
            </div>

                {{-- Badge Playlist asal — hanya tampil bila content berasal dari Playlist.
                     Bila lebih dari satu, tampilkan satu nama + "+N" agar card tetap ringkas. --}}
                @if ($playlistNames->isNotEmpty())
                    <div class="d-flex flex-wrap align-items-center gap-2 mb-3">
                        <span class="badge bg-teal-lt text-teal" title="Playlist: {{ $playlistNames->implode(', ') }}">
                            <i class="ti ti-playlist me-1"></i>{{ $playlistNames->first() }}
                        </span>

                        @if ($playlistNames->count() > 1)
                            <span class="badge bg-teal-lt text-teal" title="Playlist: {{ $playlistNames->implode(', ') }}">
                                +{{ $playlistNames->count() - 1 }}
                            </span>
                        @endif
                    </div>
                @endif
        @endif

        <div class="mb-2">
            <div class="d-flex justify-content-between small mb-1">
                @if ($isPlaylist)
                    <span class="text-muted">{{ $completedCount }} / {{ $moduleCount }} Completed</span>
                @else
                    <span class="text-muted">Progress</span>
                @endif
                <span class="fw-semibold">{{ $progress }}%</span>
            </div>

            <div class="progress progress-sm learning-progress-bar">
                <div class="progress-bar bg-primary" style="width: {{ $progress }}%"></div>
            </div>
        </div>

        @if (! $isPlaylist)
            <div class="small text-muted mb-3">
                Aktiv: {{ $active }}
            </div>
        @else
            <div class="small text-muted mb-3">&nbsp;</div>
        @endif

        @php
            $cardUrl = $isPlaylist ? $continueUrl : ($slugUrl ?? null);
        @endphp

        @if ($cardUrl)
            {{-- Card Playlist: seluruh card dapat diklik (stretched-link) menuju
                 halaman playlist /playlist/{playlistSlug}/{contentSlug}. --}}
            <a href="{{ $cardUrl }}" class="btn btn-primary btn-learning-primary btn-learning-lg w-100 mt-auto {{ $isPlaylist ? 'stretched-link' : '' }}">
                <i class="ti ti-player-play"></i>
                Continue Learning
            </a>
        @else
            <button type="button" class="btn btn-primary btn-learning-primary btn-learning-lg w-100 mt-auto" disabled>
                <i class="ti ti-player-play"></i>
                Continue Learning
            </button>
        @endif
    </div>
</div>
