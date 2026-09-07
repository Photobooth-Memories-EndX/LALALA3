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

    $playlistProgress = $playlistUiState['learning_progress']
        ?? $percentageProgressPlaylist
        ?? 0;
    $chapterTotal = ($content_all ?? collect())->count();
    $playlistViews = $playlist->views ?? $playlist->view ?? $playlist->total_views ?? 0;
    $periodStart = $playlist->from_date ? $playlist->from_date->format('d M Y') : null;
    $periodEnd = $playlist->to_date ? $playlist->to_date->format('d M Y') : null;
    $periodLabel = $periodStart && $periodEnd
        ? $periodStart . ' - ' . $periodEnd
        : ($periodStart ?: ($periodEnd ?: '-'));
    $playlistDuration = \App\Support\DurationFormatter::fromSeconds((int) ($playlist->total_duration_seconds ?? 0));
    $contentType = strtolower((string) ($content->type ?? 'Content'));
    $contentTypeBadgeClass = match (true) {
        in_array($contentType, ['video', 'youtube'], true)                 => 'bg-yellow text-white',
        in_array($contentType, ['document', 'pdf', 'sop/regulation'], true) => 'bg-cyan',
        $contentType === 'playlist'                                       => 'bg-green',
        $contentType === 'content'                                        => 'bg-red',
        default                                                           => 'bg-secondary',
    };

    $classificationName = $content->classification->classification_name ?? 'Learning';
    $classificationSlug = \Illuminate\Support\Str::slug($classificationName);

    $continueThumb = $lastWatchedThumb;
    $continueUrl = '#contentContainer';
    $continueLabel = 'Continue Learning';

    if (!empty($isPlaylistFullyCompleted)) {
        $continueLabel = 'Playlist Completed';
    } elseif (!empty($continueLearningContent) && $continueLearningContent->id !== $content->id) {
        $continueUrl = route('playlist.user.view', [$playlist->slug, $continueLearningContent->slug]);
        if ($continueLearningContent->type === 'YouTube' && !empty($continueLearningContent->link)) {
            $continueThumb = 'http://img.youtube.com/vi/' . $continueLearningContent->link . '/mqdefault.jpg';
        } else {
            $cThumbPath = 'storage/files/content/' . $continueLearningContent->id . '/thumb.jpg';
            $continueThumb = \File::exists(public_path($cThumbPath)) && !is_dir(public_path($cThumbPath))
                ? asset($cThumbPath)
                : asset('default-video.jpg');
        }
    }
@endphp

<div class="card border-0 shadow-sm overflow-hidden mb-3 view-hero-card">
    <div class="view-hero-bg">
        <div class="view-hero-overlay">
            <div class="view-hero-content text-white w-100">

                <div class="view-hero-copy">
                    <span class="view-hero-badge bg-green mb-2">
                        <i class="fas fa-layer-group me-1"></i>
                        Playlist
                    </span>

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
                            <i class="fas fa-users-cog me-1"></i>
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
                        <span class="badge bg-white text-primary classification-badge classification-badge-{{ $classificationSlug }}" data-classification="{{ $classificationName }}">
                            {{ $classificationName }}
                        </span>
                        <span class="badge {{ $contentTypeBadgeClass }}">{{ \App\Support\ContentTypeHelper::label($content->type ?? 'Content') }}</span>
                    </div>
                </div>

                <a href="{{ $continueUrl }}" class="view-hero-continue continue-learning-card rounded-4 p-3 shadow-sm border-0" style="background-color: #002b66 !important; border-color: rgba(255, 255, 255, 0.15) !important;">
                    <span class="view-hero-continue-thumb ratio ratio-16x9 rounded-3 overflow-hidden" style="background-image: url('{{ $continueThumb }}')"></span>
                    <div class="text-center mt-2">
                        <span class="text-white fw-bold small text-uppercase" style="letter-spacing: 0.5px; color: #ffffff !important;">
                            {{ $continueLabel }}
                        </span>
                    </div>
                </a>

            </div>
        </div>
    </div>
</div>
