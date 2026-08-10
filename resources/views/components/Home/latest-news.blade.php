<div class="home-modern-card">

    <div class="home-modern-card-header">
        <h2 class="home-modern-section-title">Latest News</h2>
        <a href="{{ route('news.user.index') }}" class="home-modern-view-all view-all-link text-primary dark:text-white font-medium">
            All <i class="ti ti-arrow-right ms-1"></i>
        </a>
    </div>

    <div class="home-modern-card-body pt-0">

        @if($news && $news->count())

            <div id="homeLatestNewsCarousel" class="carousel slide home-modern-news-carousel">

                <div class="carousel-inner">
                    @foreach($news as $index => $item)
                    <div class="carousel-item {{ $index === 0 ? 'active' : '' }}">
                        <a href="{{ route('news.user.view', $item->slug) }}"
                           class="home-modern-news-slide">

                            <div class="home-modern-news-slide-thumb">
                                @if(!empty($item->image))
                                    <img src="{{ asset('storage/news/' . $item->image) }}"
                                         alt="{{ $item->title }}"
                                         onerror="this.style.display='none'; this.nextElementSibling.style.display='flex';">
                                    <div class="home-modern-news-placeholder" style="display:none">
                                        <i class="ti ti-news"></i>
                                    </div>
                                @else
                                    <div class="home-modern-news-placeholder">
                                        <i class="ti ti-news"></i>
                                    </div>
                                @endif
                            </div>

                            <div class="home-modern-news-slide-body">
                                <div class="home-modern-news-slide-title">{{ Str::limit($item->title, 60) }}</div>
                                <div class="home-modern-news-slide-date">
                                    <i class="ti ti-calendar me-1"></i>
                                    {{ $item->created_at->format('d M Y') }}
                                </div>
                            </div>

                        </a>
                    </div>
                    @endforeach
                </div>

                @if($news->count() > 1)

                    <button class="home-modern-news-nav home-modern-news-nav-prev" type="button"
                            data-bs-target="#homeLatestNewsCarousel" data-bs-slide="prev"
                            aria-label="Previous news">
                        <i class="ti ti-chevron-left"></i>
                    </button>
                    <button class="home-modern-news-nav home-modern-news-nav-next" type="button"
                            data-bs-target="#homeLatestNewsCarousel" data-bs-slide="next"
                            aria-label="Next news">
                        <i class="ti ti-chevron-right"></i>
                    </button>

                    {{-- Bootstrap syncs the active dot via .carousel-indicators --}}
                    <div class="carousel-indicators home-modern-news-dots">
                        @foreach($news as $index => $item)
                        <button type="button"
                                data-bs-target="#homeLatestNewsCarousel"
                                data-bs-slide-to="{{ $index }}"
                                class="{{ $index === 0 ? 'active' : '' }}"
                                aria-label="News {{ $index + 1 }}"
                                @if($index === 0) aria-current="true" @endif></button>
                        @endforeach
                    </div>

                @endif

            </div>

        @else

        <div class="home-modern-empty">
            <i class="ti ti-news-off"></i>
            <p>No news available</p>
        </div>

        @endif

    </div>

</div>
