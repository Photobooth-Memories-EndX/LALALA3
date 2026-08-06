<div class="home-modern-card">

    <div class="home-modern-card-header">
        <h2 class="home-modern-section-title">
            <i class="ti ti-crown me-2 home-modern-text-primary"></i>Top Video Learning
        </h2>
        <a href="{{ route('content.user.index') }}" class="home-modern-view-all">
            View All <i class="ti ti-arrow-right ms-1"></i>
        </a>
    </div>

    <div class="home-modern-card-body">

        @if($top_video && $top_video->count())

        <div class="home-modern-slide-carousel" data-autoplay="4000">
            <div class="home-modern-slide-viewport">
                <div class="home-modern-slide-track">
            @foreach($top_video->take(6) as $item)
            <div class="home-modern-slide-item">
                <a href="{{ route('content.user.view', $item->slug) }}" class="home-modern-video-card home-video-card">

                    <div class="home-modern-video-thumb home-video-thumbnail">
                        @if($item->type === 'YouTube')
                            <img src="https://img.youtube.com/vi/{{ $item->link }}/mqdefault.jpg"
                                 alt="{{ $item->title }}"
                                 onerror="this.src='{{ asset('default-video.jpg') }}'">
                        @else
                            @php
                                $tvSrc = 'storage/files/content/' . $item->id . '/thumb.jpg';
                                $tvThumb = (\File::exists(public_path($tvSrc)) && !is_dir(public_path($tvSrc)))
                                    ? asset($tvSrc)
                                    : asset('default-video.jpg');
                            @endphp
                            <img src="{{ $tvThumb }}"
                                 alt="{{ $item->title }}"
                                 onerror="this.src='{{ asset('default-video.jpg') }}'">
                        @endif

                        <div class="home-modern-video-play">
                            <i class="ti ti-player-play-filled"></i>
                        </div>

                        @if($item->duration)
                        <span class="home-modern-duration-badge">{{ \App\Support\DurationFormatter::format($item->duration) }}</span>
                        @endif
                    </div>

                    <div class="home-modern-video-body">
                        <span class="home-modern-category-badge">
                            {{ \App\Support\ContentTypeHelper::label($item->type) }}
                        </span>
                        <h4 class="home-modern-video-title">{{ Str::limit($item->title, 55) }}</h4>
                    </div>

                </a>
            </div>
            @endforeach
                </div>
            </div>

            <button class="home-modern-slide-nav home-modern-slide-nav-prev" type="button" aria-label="Previous videos">
                <i class="ti ti-chevron-left"></i>
            </button>
            <button class="home-modern-slide-nav home-modern-slide-nav-next" type="button" aria-label="Next videos">
                <i class="ti ti-chevron-right"></i>
            </button>
        </div>

        @else

        <div class="home-modern-empty">
            <i class="ti ti-video-off"></i>
            <p>No videos available</p>
        </div>

        @endif

    </div>

</div>
