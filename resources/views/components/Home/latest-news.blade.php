<div class="home-modern-card mt-4">

    <div class="home-modern-card-header">
        <h2 class="home-modern-section-title">Latest News</h2>
        <a href="{{ route('news.user.index') }}" class="home-modern-view-all">
            View All <i class="ti ti-arrow-right ms-1"></i>
        </a>
    </div>

    <div class="home-modern-card-body">

        @if($news && $news->count())

        <div class="row g-3">

            @foreach($news->take(3) as $item)
            <div class="col-lg-4 col-md-6">
                <a href="{{ route('news.user.view', $item->slug) }}" class="home-modern-news-card">

                    <div class="home-modern-news-thumb">
                        <img src="{{ asset('default-video.jpg') }}" alt="{{ $item->title }}">
                    </div>

                    <div class="home-modern-news-body">
                        <span class="home-modern-category-badge">News</span>
                        <h5 class="home-modern-news-title">
                            {{ Str::limit($item->title, 65) }}
                        </h5>
                        <div class="home-modern-news-footer">
                            <small>
                                <i class="ti ti-calendar me-1"></i>
                                {{ $item->created_at->format('d M Y') }}
                            </small>
                            <span class="home-modern-readmore">Read →</span>
                        </div>
                    </div>

                </a>
            </div>
            @endforeach

        </div>

        @else

        <div class="home-modern-empty">
            <i class="ti ti-news-off"></i>
            <p>No news available</p>
        </div>

        @endif

    </div>

</div>
