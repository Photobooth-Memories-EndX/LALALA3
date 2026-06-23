<div class="home-modern-card mt-4">

    <div class="home-modern-card-header">
        <h2 class="home-modern-section-title">Top Video Learning</h2>
        <a href="{{ route('content.user.index') }}" class="home-modern-view-all">
            View All <i class="ti ti-arrow-right ms-1"></i>
        </a>
    </div>

    <div class="home-modern-card-body">

        @if($top_video && $top_video->count())

        <div class="row g-3">

            @foreach($top_video->take(2) as $item)
            <div class="col-lg-6">
                <a href="{{ route('content.user.view', $item->slug) }}" class="home-modern-video-card">

                    <div class="home-modern-video-thumb">
                        <img src="{{ asset('default-video.jpg') }}" alt="{{ $item->title }}">
                        <div class="home-modern-video-play">
                            <i class="ti ti-player-play-filled"></i>
                        </div>
                    </div>

                    <div class="home-modern-video-body">
                        <span class="home-modern-category-badge">Video Learning</span>
                        <h4 class="home-modern-video-title">{{ Str::limit($item->title, 65) }}</h4>
                        <div class="home-modern-video-meta">
                            <i class="ti ti-clock me-1"></i>{{ $item->duration ?? '15 Min' }}
                        </div>
                    </div>

                </a>
            </div>
            @endforeach

        </div>

        @else

        <div class="home-modern-empty">
            <i class="ti ti-video-off"></i>
            <p>No videos available</p>
        </div>

        @endif

    </div>

</div>
