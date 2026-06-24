<div class="home-modern-card">

    <div class="home-modern-card-header">
        <h2 class="home-modern-section-title">Latest News</h2>
        <a href="{{ route('news.user.index') }}" class="home-modern-view-all">
            All <i class="ti ti-arrow-right ms-1"></i>
        </a>
    </div>

    <div class="home-modern-card-body pt-0">

        @if($news && $news->count())

            @foreach($news as $item)
            <a href="{{ route('news.user.view', $item->slug) }}"
               class="home-modern-news-row">

                <div class="home-modern-news-row-icon">
                    <i class="ti ti-news"></i>
                </div>

                <div class="home-modern-news-row-body">
                    <div class="home-modern-news-row-title">{{ Str::limit($item->title, 55) }}</div>
                    <div class="home-modern-news-row-date">
                        <i class="ti ti-calendar me-1"></i>
                        {{ $item->created_at->format('d M Y') }}
                    </div>
                </div>

            </a>
            @endforeach

        @else

        <div class="home-modern-empty">
            <i class="ti ti-news-off"></i>
            <p>No news available</p>
        </div>

        @endif

    </div>

</div>
