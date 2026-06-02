<div class="col">
    <div class="card owl-news-card h-100 border-0 shadow-sm" data-news-id="{{ $item->id }}">

        <div class="owl-news-image-wrapper">
            <img src="{{ asset('default-video.jpg') }}"
                 class="owl-news-image"
                 alt="{{ $item->title }}"
                 loading="lazy">

            <div class="owl-news-overlay"></div>

            <div class="owl-news-badge d-flex align-items-center gap-2">
                <i class="ti ti-tag"></i>
                <span>{{ $item->category ?? 'Technology' }}</span>
            </div>

            <button class="btn btn-icon btn-white rounded-circle owl-save-btn"
                    data-bs-toggle="tooltip"
                    title="Save for later"
                    aria-label="Save article">
                <i class="ti ti-bookmark"></i>
            </button>
        </div>

        <div class="card-body d-flex flex-column flex-grow-1">

            <div class="d-flex flex-wrap gap-2 align-items-center mb-3 owl-news-meta">
                <span class="owl-news-pill">
                    <i class="ti ti-clock-hour-4 me-1"></i>
                    {{ ceil(str_word_count(strip_tags($item->description)) / 200) }} min read
                </span>
                <span class="owl-news-pill owl-news-pill-muted">
                    {{ $item->created_at->format('M d, Y') }}
                </span>
            </div>

            <h3 class="card-title mb-3 flex-grow-1 owl-card-title">
                <a href="{{ route('news.user.view', $item->slug) }}"
                   class="text-dark text-decoration-none">
                    {{ $item->title }}
                </a>
            </h3>

            <p class="text-secondary mb-4 flex-grow-1 owl-card-excerpt">
                {{ Str::limit(strip_tags($item->description), 130) }}
            </p>

        </div>

        <div class="card-footer bg-transparent border-top pt-0 mt-auto">
            <div class="d-flex align-items-center justify-content-between gap-3 py-3">

                <div class="d-flex align-items-center gap-2">
                    <span class="avatar avatar-sm bg-blue-lt text-blue fw-bold">
                        {{ strtoupper(substr($item->user->name ?? 'U', 0, 1)) }}
                    </span>
                    <div>
                        <div class="fw-semibold text-dark small mb-1">
                            {{ $item->user->name ?? 'Unknown' }}
                        </div>
                        <div class="text-secondary tiny">
                            {{ number_format($item->views ?? 0) }} views
                        </div>
                    </div>
                </div>

                <a href="{{ route('news.user.view', $item->slug) }}"
                   class="btn btn-sm btn-outline-primary rounded-pill owl-card-cta">
                    Read more
                    <i class="ti ti-arrow-right ms-2"></i>
                </a>
            </div>
        </div>

    </div>
</div>
