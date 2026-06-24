<div class="home-modern-card">

    <div class="home-modern-card-header">
        <h2 class="home-modern-section-title">Latest Gallery</h2>
        <a href="{{ route('gallery.user.index') }}" class="home-modern-view-all">
            All <i class="ti ti-arrow-right ms-1"></i>
        </a>
    </div>

    <div class="home-modern-card-body pt-0">

        @if($eventphoto && $eventphoto->count())

            @foreach($eventphoto->take(5) as $gallery)
            <a href="{{ route('gallery.user.view', $gallery->slug) }}"
               class="home-modern-gallery-row">

                <div class="home-modern-gallery-row-thumb">
                    <img src="{{ asset('storage/gallery/photo/' . $gallery->id . '/' . $gallery->thumbnail) }}"
                         alt="{{ $gallery->title }}"
                         onerror="this.src='{{ asset('default-video.jpg') }}'">
                </div>

                <div class="home-modern-gallery-row-body">
                    <div class="home-modern-gallery-row-title">{{ Str::limit($gallery->title, 40) }}</div>
                    <div class="home-modern-gallery-row-date">
                        <i class="ti ti-calendar me-1"></i>
                        {{ \Carbon\Carbon::parse($gallery->created_at)->format('d M Y') }}
                    </div>
                </div>

            </a>
            @endforeach

        @else

        <div class="home-modern-empty">
            <i class="ti ti-photo-off"></i>
            <p>No gallery items available</p>
        </div>

        @endif

    </div>

</div>
