<div class="home-modern-card mt-4 mb-4">

    <div class="home-modern-card-header">
        <h2 class="home-modern-section-title">Recent Activity</h2>
        <a href="{{ route('gallery.user.index') }}" class="home-modern-view-all">
            View All <i class="ti ti-arrow-right ms-1"></i>
        </a>
    </div>

    <div class="home-modern-card-body">

        @if($eventphoto && $eventphoto->count())

        <div class="row g-3">

            @foreach($eventphoto->take(3) as $gallery)
            <div class="col-lg-4 col-md-6">
                <a href="{{ route('gallery.user.view', $gallery->slug) }}" class="home-modern-gallery-card">

                    <div class="home-modern-gallery-thumb">
                        <img src="{{ asset('default-video.jpg') }}" alt="{{ $gallery->title }}">
                    </div>

                    <div class="home-modern-gallery-body">
                        <span class="home-modern-category-badge home-modern-category-green">Gallery</span>
                        <h5 class="home-modern-gallery-title">
                            {{ Str::limit($gallery->title, 50) }}
                        </h5>
                        <div class="home-modern-gallery-footer">
                            <small>
                                <i class="ti ti-calendar me-1"></i>
                                {{ \Carbon\Carbon::parse($gallery->created_at)->format('d M Y') }}
                            </small>
                            <span class="home-modern-readmore">View →</span>
                        </div>
                    </div>

                </a>
            </div>
            @endforeach

        </div>

        @else

        <div class="home-modern-empty">
            <i class="ti ti-photo-off"></i>
            <p>No gallery items available</p>
        </div>

        @endif

    </div>

</div>
