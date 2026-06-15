@php
    $heroThumb = asset('default-video.jpg');
    $thumbPath = 'storage/files/content/' . $content->id . '/thumb.jpg';
    if (\File::exists(public_path($thumbPath))) {
        $heroThumb = asset($thumbPath);
    }
    $playlistProgress = $percentageProgressPlaylist ?? 0;
@endphp

<div class="card border-0 shadow-sm overflow-hidden mb-3 view-hero-card">
    <div class="view-hero-bg" style="background-image: url('{{ $heroThumb }}')">
        <div class="view-hero-overlay">
            <div class="px-3 py-2 text-white w-100">

                <div class="d-flex flex-wrap gap-2 mb-1">
                    <span class="badge bg-primary">
                        {{ $content->classification->classification_name ?? 'Learning' }}
                    </span>
                    <span class="badge bg-success">
                        {{ $content->type ?? 'Content' }}
                    </span>
                    <span class="badge bg-dark bg-opacity-75">
                        {{ number_format($playlistProgress) }}% Progress
                    </span>
                </div>

                <h2 class="fw-bold mb-1 lh-sm" style="font-size:1.1rem;">
                    {{ $playlist->title }}
                </h2>

                <div class="d-flex flex-wrap gap-3 small opacity-90">
                    <span>
                        <i class="fas fa-list me-1"></i>
                        {{ ($content_all ?? collect())->count() }} Chapters
                    </span>
                    <span>
                        <i class="fas fa-eye me-1"></i>
                        {{ $content->views ?? 0 }} Views
                    </span>
                    <span class="d-none d-sm-inline">
                        <i class="fas fa-user me-1"></i>
                        {{ $content->author ?? '-' }}
                    </span>
                </div>

            </div>
        </div>
    </div>
</div>
