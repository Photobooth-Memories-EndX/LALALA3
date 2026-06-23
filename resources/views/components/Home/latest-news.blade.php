<div class="modern-card mt-4">

    <div class="card-header border-0 d-flex justify-content-between align-items-center">

        <h2 class="section-title mb-0">
            Latest News
        </h2>

        <a href="{{ route('news.user.index') }}">
            View All
        </a>

    </div>

    <div class="card-body">

        <div class="row g-4">

            @foreach($news->take(3) as $item)

            <div class="col-lg-4">

                <a
                    href="{{ route('news.user.view',$item->slug) }}"
                    class="news-card-modern">

                    <img
                        src="{{ asset('default-video.jpg') }}"
                        class="news-modern-image"
                        alt="{{ $item->title }}">

                    <div class="news-modern-body">

                        <span class="news-category">

                            News

                        </span>

                        <h5 class="news-modern-title">

                            {{ Str::limit($item->title,70) }}

                        </h5>

                        <div class="news-footer">

                            <small>

                                <i class="ti ti-calendar me-1"></i>

                                {{ $item->created_at->format('d M Y') }}

                            </small>

                            <span>

                                Read More →

                            </span>

                        </div>

                    </div>

                </a>

            </div>

            @endforeach

        </div>

    </div>

</div>
