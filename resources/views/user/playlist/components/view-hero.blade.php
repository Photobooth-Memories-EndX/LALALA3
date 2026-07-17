@php
    $lastWatchedThumb = null;
    if ($content->type === 'YouTube' && !empty($content->link)) {
        $lastWatchedThumb = 'http://img.youtube.com/vi/' . $content->link . '/mqdefault.jpg';
    } else {
        $thumbPath = 'storage/files/content/' . $content->id . '/thumb.jpg';
        $lastWatchedThumb = \File::exists(public_path($thumbPath)) && !is_dir(public_path($thumbPath))
            ? asset($thumbPath)
            : asset('default-video.jpg');
    }

    $heroThumb = $playlist->thumbnail_url ?: $lastWatchedThumb;
    $playlistProgress = $percentageProgressPlaylist ?? 0;
    $chapterTotal = ($content_all ?? collect())->count();
    $playlistViews = $playlist->views ?? $playlist->view ?? $playlist->total_views ?? 0;
    $periodStart = $playlist->from_date ? $playlist->from_date->format('d M Y') : null;
    $periodEnd = $playlist->to_date ? $playlist->to_date->format('d M Y') : null;
    $periodLabel = $periodStart && $periodEnd
        ? $periodStart . ' - ' . $periodEnd
        : ($periodStart ?: ($periodEnd ?: '-'));
    $playlistDuration = \App\Support\DurationFormatter::fromSeconds((int) ($playlist->total_duration_seconds ?? 0));
@endphp

<div class="card border-0 shadow-sm overflow-hidden mb-3 view-hero-card">
    <div class="view-hero-bg" @if($heroThumb) style="background-image: url('{{ $heroThumb }}')" @endif>
        <div class="view-hero-overlay">
            <div class="view-hero-content text-white w-100">

                <div class="view-hero-copy">
                    <span class="badge bg-primary mb-2">Playlist</span>

                    <h1 class="view-hero-title">
                        {{ $playlist->title }}
                    </h1>

                    <div class="view-hero-meta">
                        <span>
                            <i class="fas fa-list me-1"></i>
                            {{ $chapterTotal }} Chapters
                        </span>
                        <span>
                            <i class="fas fa-eye me-1"></i>
                            {{ number_format($playlistViews) }} Playlist Views
                        </span>
                        <span>
                            <i class="fas fa-users me-1"></i>
                            {{ $content->author ?? '-' }}
                        </span>
                        <span>
                            <i class="far fa-calendar-alt me-1"></i>
                            {{ $periodLabel }}
                        </span>
                        <span>
                            <i class="far fa-clock me-1"></i>
                            {{ $playlistDuration }}
                        </span>
                    </div>

                    <div class="view-hero-bottom">
                        <span class="badge bg-success">{{ $content->type ?? 'Content' }}</span>
                        <span class="badge bg-dark bg-opacity-75">{{ number_format($playlistProgress) }}% Progress</span>
                    </div>
                </div>

                <a href="#contentContainer" class="view-hero-continue">
                    <span class="view-hero-continue-thumb" style="background-image: url('{{ $lastWatchedThumb }}')">
                        <i class="fas fa-play"></i>
                    </span>
                    <span>
                        <span class="small text-white-50 d-block">Last watched</span>
                        <strong>Continue Learning</strong>
                    </span>
                </a>

            </div>
        </div>
    </div>
</div>
