@php
    $featured = $news->first();
    $readTime = ceil(str_word_count(strip_tags($featured->description ?? '')) / 200);
    $readTime = max(1, $readTime);
    $category = $featured->category ?? 'Technology';
@endphp

@if($featured)

<div class="card border-0 owl-hero-card mb-5">
    <div class="row g-0 h-100">

        {{-- LEFT: TEXT --}}
        <div class="col-lg-7">
            <div class="card-body text-white">

                <span class="badge owl-hero-badge bg-white text-primary rounded-pill fw-semibold px-3 py-2 mb-3">
                    <i class="ti ti-star-filled me-2"></i>Featured story
                </span>

                <h1 class="owl-hero-title">
                    {{ Str::limit($featured->title ?? '', 90) }}
                </h1>

                <p class="owl-hero-description">
                    {{ Str::limit(strip_tags($featured->description ?? ''), 120) }}
                </p>

                <div class="d-flex flex-wrap gap-2 mb-4">
                    <span class="owl-hero-pill">
                        <i class="ti ti-clock me-1"></i>{{ $readTime }} min read
                    </span>
                    <span class="owl-hero-pill">
                        <i class="ti ti-eye me-1"></i>{{ number_format($featured->views ?? 0) }} views
                    </span>
                    <span class="owl-hero-pill">
                        <i class="ti ti-tag me-1"></i>{{ $category }}
                    </span>
                </div>

                <div class="d-flex gap-3">
                    <a href="{{ route('news.user.view', $featured->slug) }}"
                       class="btn btn-light rounded-pill px-4">
                        Read Article
                    </a>
                    <button class="btn btn-outline-light rounded-pill px-4">
                        Save
                    </button>
                </div>

            </div>
        </div>

        {{-- RIGHT: IMAGE --}}
        <div class="col-lg-5">
            <div class="owl-hero-image-wrapper">

                {{-- Placeholder always behind --}}
                <div class="news-img-placeholder news-hero-img-placeholder">
                    <i class="ti ti-news"></i>
                </div>

                {{-- Real image on top --}}
                @if(!empty($featured->image))
                    <img
                        src="{{ asset('storage/news/' . $featured->image) }}"
                        class="owl-hero-image"
                        alt="{{ $featured->title ?? '' }}"
                        loading="lazy"
                        onerror="this.style.display='none'"
                    >
                @endif

                <div class="owl-hero-overlay"></div>

            </div>
        </div>

    </div>
</div>

@endif
