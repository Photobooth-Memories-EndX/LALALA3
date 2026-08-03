@php
    $featured = $news->first();
    $readTime = ceil(str_word_count(strip_tags($featured->description ?? '')) / 200);
    $readTime = max(1, $readTime);
    $category = $featured->category ?? 'Technology';
@endphp

@if($featured)

@include('components.shared.hero-style')

<div class="card news-hero-card mb-4 overflow-hidden border-0 shadow-sm">
    <div class="card-body news-hero-surface text-white">
        <div class="row align-items-center g-3">
            <div class="col-lg-8 news-hero-copy">
                <div class="d-inline-flex align-items-center gap-1 badge bg-white text-primary mb-2 rounded-pill news-hero-badge">
                    <i class="ti ti-star-filled text-warning"></i>
                    NEWS
                </div>

                <h1 class="mb-1 fw-bold news-hero-title text-truncate-2">
                    {{ Str::limit($featured->title ?? '', 65) }}
                </h1>

                <p class="mb-2 text-white-75 news-hero-description text-truncate-1">
                    {{ Str::limit(strip_tags($featured->description ?? ''), 90) }}
                </p>

                <div class="d-flex flex-wrap gap-2 align-items-center mt-2">
                    <span class="news-hero-pill">
                        <i class="ti ti-clock me-1"></i>
                        {{ $readTime }} min read
                    </span>
                    <span class="news-hero-pill">
                        <i class="ti ti-eye me-1"></i>
                        {{ number_format($featured->views ?? 0) }} views
                    </span>
                    <span class="news-hero-pill">
                        <i class="ti ti-tag me-1"></i>
                        {{ $category }}
                    </span>
                </div>
            </div>

            <div class="col-lg-4 d-flex justify-content-lg-end align-items-center">
                <div class="d-flex flex-wrap gap-2 align-items-center">
                    <a href="{{ route('news.user.view', $featured->slug) }}" class="btn btn-sm btn-white text-primary fw-bold rounded-pill px-3 py-2 shadow-sm news-btn-white">
                        <i class="ti ti-book-open me-1"></i> Read Article
                    </a>
                    <button type="button" class="btn btn-sm btn-white text-primary fw-bold rounded-pill px-3 py-2 shadow-sm news-btn-white">
                        <i class="ti ti-bookmark me-1"></i> Save
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>

@endif
