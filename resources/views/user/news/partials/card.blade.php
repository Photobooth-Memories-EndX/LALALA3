<div class="col">
    <div class="card owl-news-card h-100 border-0 shadow-sm"
         data-news-id="{{ $item->id }}"
         data-news-created="{{ $item->created_at->format('Y-m-d') }}"
         data-news-views="{{ (int) ($item->views ?? 0) }}">

        {{-- THUMBNAIL --}}
        <div class="owl-news-image-wrapper">
            <a href="{{ route('news.user.view', array_merge(['slug' => $item->slug], request()->query())) }}" class="d-block w-100 h-100 text-decoration-none text-reset">
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

            <div class="owl-news-badge bg-success text-white px-2 py-1 rounded-pill d-flex align-items-center gap-1" style="font-size: 0.75rem;">
                <i class="ti ti-tag"></i>
                <span>{{ $item->category ?? 'Technology' }}</span>
            </div>



        </div>

        {{-- BODY --}}
        <div class="card-body d-flex flex-column flex-grow-1 p-3">

            <h5 class="card-title line-clamp-2 mb-2 owl-card-title" title="{{ $item->title ?? '' }}" style="min-height: 2.8rem; display: -webkit-box; -webkit-line-clamp: 2; -webkit-box-orient: vertical; overflow: hidden; line-height: 1.4;">
                <a href="{{ route('news.user.view', array_merge(['slug' => $item->slug], request()->query())) }}" class="owl-card-title-link text-reset text-decoration-none fw-bold" title="{{ $item->title ?? '' }}">
                    {{ $item->title ?? '-' }}
                </a>
            </h5>

            {{-- AUTHOR & META INFO --}}
            <div class="mt-auto pt-2">
                {{-- Meta Author --}}
                <div class="d-flex align-items-center text-muted small mb-2">
                    <i class="ti ti-users me-1 fs-6"></i>
                    <span>Author : {{ $item->user->name ?? 'Unknown' }}</span>
                </div>

                {{-- Meta Stats (Satu Baris Sejajar Horizontal) --}}
                <div class="d-flex align-items-center gap-3 text-muted small mb-3">
                    <div class="d-flex align-items-center gap-1">
                        <i class="ti ti-calendar fs-6"></i>
                        <span>{{ $item->created_at ? $item->created_at->format('d M Y') : '-' }}</span>
                    </div>
                    <div class="d-flex align-items-center gap-1">
                        <i class="ti ti-eye fs-6"></i>
                        <span>{{ number_format($item->views ?? 0) }}</span>
                    </div>
                    <div class="d-flex align-items-center gap-1">
                        <i class="ti ti-message-dots fs-6"></i>
                        <span>{{ $item->comments_count ?? (isset($item->comments) ? $item->comments->count() : 0) }}</span>
                    </div>
                </div>
            </div>

        </div>

        {{-- FOOTER --}}
        <div class="card-footer bg-transparent border-top pt-3 pb-3 mt-auto">
            <a href="{{ route('news.user.view', array_merge(['slug' => $item->slug], request()->query())) }}"
               class="btn btn-primary w-100 rounded-pill">
                Read more <i class="ti ti-arrow-right ms-1"></i>
            </a>
        </div>

    </div>
</div>
