<div class="home-modern-card">

    <div class="home-modern-card-header">
        <h2 class="home-modern-section-title">Latest Gallery</h2>
        <a href="{{ route('gallery.user.index') }}" class="home-modern-view-all view-all-link text-primary dark:text-white font-medium">
            All <i class="ti ti-arrow-right ms-1"></i>
        </a>
    </div>

    <div class="home-modern-card-body pt-0">

        @if($eventphoto && $eventphoto->count())

            <div id="homeLatestGalleryCarousel" class="carousel slide home-modern-gallery-carousel">
                <div class="carousel-inner">
                    @foreach($eventphoto->take(3) as $index => $gallery)
                    <div class="carousel-item {{ $index === 0 ? 'active' : '' }}">
                        <a href="{{ route('gallery.user.view', $gallery->slug) }}"
                           class="home-modern-gallery-slide"
                           title="{{ $gallery->title }}">

                            <div class="home-modern-gallery-slide-thumb">
                                @if(!empty($gallery->thumbnail))
                                    <img src="{{ asset('storage/gallery/photo/' . $gallery->id . '/' . $gallery->thumbnail) }}"
                                         alt="{{ $gallery->title }}"
                                         onerror="this.style.display='none'; this.nextElementSibling.style.display='flex';">
                                    <div class="home-modern-gallery-placeholder" style="display:none">
                                        <i class="ti ti-photo"></i>
                                    </div>
                                @else
                                    <div class="home-modern-gallery-placeholder">
                                        <i class="ti ti-photo"></i>
                                    </div>
                                @endif
                            </div>

                            <div class="home-modern-gallery-slide-body">
                                <div class="home-modern-gallery-slide-title" title="{{ $gallery->title }}">{{ Str::limit($gallery->title, 40) }}</div>
                                <div class="home-modern-gallery-slide-date">
                                    <i class="ti ti-calendar me-1"></i>
                                    {{ \Carbon\Carbon::parse($gallery->created_at)->format('d M Y') }}
                                </div>
                            </div>

                        </a>
                    </div>
                    @endforeach
                </div>

                @if($eventphoto->count() > 1)

                    <button class="home-modern-gallery-nav home-modern-gallery-nav-prev" type="button"
                            data-bs-target="#homeLatestGalleryCarousel" data-bs-slide="prev"
                            aria-label="Previous gallery">
                        <i class="ti ti-chevron-left"></i>
                    </button>
                    <button class="home-modern-gallery-nav home-modern-gallery-nav-next" type="button"
                            data-bs-target="#homeLatestGalleryCarousel" data-bs-slide="next"
                            aria-label="Next gallery">
                        <i class="ti ti-chevron-right"></i>
                    </button>

                    {{-- Bootstrap syncs the active dot via .carousel-indicators --}}
                    <div class="carousel-indicators home-modern-news-dots home-modern-gallery-dots">
                        @foreach($eventphoto->take(3) as $index => $gallery)
                        <button type="button"
                                data-bs-target="#homeLatestGalleryCarousel"
                                data-bs-slide-to="{{ $index }}"
                                class="{{ $index === 0 ? 'active' : '' }}"
                                aria-label="Gallery {{ $index + 1 }}"
                                @if($index === 0) aria-current="true" @endif></button>
                        @endforeach
                    </div>

                @endif

            </div>

        @else

        <div class="home-modern-empty">
            <i class="ti ti-photo-off"></i>
            <p>No gallery items available</p>
        </div>

        @endif

    </div>

</div>
