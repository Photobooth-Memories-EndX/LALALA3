@php
    $thumbnailPath = public_path('storage/files/content/' . $item->id . '/thumb.jpg');

    if ($item->type == 'YouTube') {
        $thumbnail = 'http://img.youtube.com/vi/' . $item->link . '/mqdefault.jpg';
    } elseif (file_exists($thumbnailPath)) {
        $thumbnail = asset('storage/files/content/' . $item->id . '/thumb.jpg');
    } else {
        $thumbnail = asset('default-video.jpg');
    }
@endphp

<div class="card content-card">
    <a href="{{ route('content.user.view', $item->slug) }}">
        <div class="content-card-thumb" style="background-image: url('{{ $thumbnail }}')"></div>
    </a>

    <div class="card-body">
        <div class="d-flex align-items-center mb-2">
            <span class="badge bg-blue-lt">
                {{ $item->type }}
            </span>

            <span class="ms-auto text-muted small">
                <i class="ti ti-clock"></i>
                {{ $item->duration ?? '-' }}
            </span>
        </div>

        <h3 class="card-title content-card-title mb-2">
            <a href="{{ route('content.user.view', $item->slug) }}" class="text-decoration-none">
                {{ \Illuminate\Support\Str::limit($item->title, 55) }}
            </a>
        </h3>

        <div class="content-card-meta text-muted mb-2">
            <i class="ti ti-folder me-1"></i>
            {{ $item->classification->classification_name ?? '-' }}
        </div>

        <div class="d-flex align-items-center content-card-meta text-muted mb-3">
            <span>
                <i class="ti ti-user me-1"></i>
                {{ $item->author ?? 'Training Team' }}
            </span>

            <span class="ms-auto">
                <i class="ti ti-eye me-1"></i>
                {{ $item->views ?? 0 }}
            </span>
        </div>

        <a href="{{ route('content.user.view', $item->slug) }}"
           class="btn btn-primary w-100 content-card-action">
            <i class="ti ti-player-play me-1"></i>
            Start Learning
        </a>
    </div>
</div>