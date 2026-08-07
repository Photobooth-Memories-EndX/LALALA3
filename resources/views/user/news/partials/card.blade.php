<div class="col">
    <div class="card owl-news-card h-100 border-0 shadow-sm"
         data-news-id="{{ $item->id }}"
         data-news-created="{{ $item->created_at->format('Y-m-d') }}"
         data-news-views="{{ (int) ($item->views ?? 0) }}">

        {{-- THUMBNAIL --}}
        <div class="owl-news-image-wrapper">
            <a href="{{ route('news.user.view', $item->slug) }}" class="d-block w-100 h-100 text-decoration-none text-reset">
                {{-- Placeholder always behind; image overlays on top --}}
                <div class="news-img-placeholder">
                    <i class="ti ti-news"></i>
                </div>

                @if(!empty($item->image))
                    <img
                        src="{{ asset('storage/news/' . $item->image) }}"
                        class="owl-news-image"
                        alt="{{ $item->title ?? '' }}"
                        loading="lazy"
                        onerror="this.style.display='none'"
                    >
                @endif

                <div class="owl-news-overlay"></div>
            </a>

            <div class="owl-news-badge d-flex align-items-center gap-2">
                <i class="ti ti-tag"></i>
                <span>{{ $item->category ?? 'Technology' }}</span>
            </div>

            <button
                class="btn btn-icon btn-white rounded-circle owl-save-btn"
                data-bs-toggle="tooltip"
                title="Save for later"
                aria-label="Save article"
            >
                <i class="ti ti-bookmark"></i>
            </button>

        </div>

        {{-- BODY --}}
        <div class="card-body d-flex flex-column flex-grow-1">

            <div class="d-flex flex-wrap gap-2 align-items-center mb-3 owl-news-meta">
                {{-- BADGE DATE --}}
                <span class="owl-news-pill-muted">
                    <i class="ti ti-calendar me-1"></i>
                    {{ $item->created_at ? $item->created_at->format('M d, Y') : '-' }}
                </span>
            </div>

            <h3 class="card-title mb-3 flex-grow-1 owl-card-title">
                <a href="{{ route('news.user.view', $item->slug) }}"
                   class="owl-card-title-link">
                    {{ $item->title ?? '-' }}
                </a>
            </h3>

            <p class="owl-card-excerpt mb-4">
                {{ Str::limit(strip_tags($item->description ?? ''), 130) }}
            </p>

        </div>

        {{-- FOOTER --}}
        <div class="card-footer bg-transparent border-top pt-0 mt-auto">
            <div class="d-flex align-items-center justify-content-between gap-3 py-3">

                <div class="d-flex align-items-center gap-2 min-w-0">
                    <span class="avatar avatar-sm bg-blue-lt text-blue fw-bold flex-shrink-0">
                        {{ strtoupper(substr($item->user->name ?? 'U', 0, 1)) }}
                    </span>
                    <div class="min-w-0">
                        <div class="fw-semibold small text-truncate owl-card-author-name">
                            {{ $item->user->name ?? 'Unknown' }}
                        </div>
                        <div class="text-secondary owl-card-views">
                            {{ number_format($item->views ?? 0) }} views
                        </div>
                    </div>
                </div>

                <a href="{{ route('news.user.view', $item->slug) }}"
                   class="btn btn-sm btn-outline-primary rounded-pill owl-card-cta flex-shrink-0">
                    Read more <i class="ti ti-arrow-right ms-1"></i>
                </a>

            </div>
        </div>

    </div>
</div>
