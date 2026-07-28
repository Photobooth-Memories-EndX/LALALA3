@php
    $featured = $news->first();
    $readTime = ceil(str_word_count(strip_tags($featured->description ?? '')) / 200);
    $readTime = max(1, $readTime);
    $category = $featured->category ?? 'Technology';
@endphp

@if($featured)

@include('components.shared.hero-style')

<style>
/* Compact Hero khusus halaman News */
.news-hero-compact{
    min-height:170px !important;
}

.news-hero-compact .card-body{
    padding:1rem 1.5rem !important;
}

.news-hero-compact .row{
    --bs-gutter-x:1rem;
    --bs-gutter-y:.5rem;
}

.news-hero-compact .owl-hero-title{
    font-size: clamp(1.7rem, 2vw, 2.4rem) !important;
    line-height: 1.15 !important;
    font-weight: 700;
    margin-bottom: .45rem !important;
}

.news-hero-compact .owl-hero-description{
    font-size: .9rem;
    line-height: 1.45;
    margin-bottom: .65rem !important;
}

.news-hero-compact .kp-hero-badge{
    padding:.3rem .8rem !important;
    font-size:.8rem !important;
    margin-bottom:.5rem !important;
}

.news-hero-compact .owl-hero-pill{
    padding:.3rem .7rem !important;
    font-size:.8rem !important;
}

.news-hero-compact .btn{
    padding:.35rem 1rem !important;
    font-size:.85rem !important;
}
</style>

<div class="card kp-enterprise-hero owl-hero-card owl-hero-card--compact news-hero-compact mb-2">
    <div class="card-body text-white py-4 px-4">
        <div class="row align-items-center g-3">

            <div class="col-xl-8 col-lg-8">
                <span class="badge kp-hero-badge owl-hero-badge rounded-pill fw-semibold px-3 py-2 mb-2">
                    <i class="ti ti-star-filled me-2"></i>
                    Featured Story
                </span>

                <h1 class="owl-hero-title mb-3">
                    {{ Str::limit($featured->title ?? '', 90) }}
                </h1>

                <p class="owl-hero-description mb-3">
                    {{ Str::limit(strip_tags($featured->description ?? ''), 120) }}
                </p>

                <div class="d-flex flex-wrap gap-2">
                    <span class="owl-hero-pill">
                        <i class="ti ti-clock me-1"></i>
                        {{ $readTime }} min read
                    </span>

                    <span class="owl-hero-pill">
                        <i class="ti ti-eye me-1"></i>
                        {{ number_format($featured->views ?? 0) }} views
                    </span>

                    <span class="owl-hero-pill">
                        <i class="ti ti-tag me-1"></i>
                        {{ $category }}
                    </span>
                </div>
            </div>

            <div class="col-xl-4 col-lg-4">
                <div class="d-flex flex-wrap justify-content-lg-end gap-2">

                    <a href="{{ route('news.user.view', $featured->slug) }}"
                       class="btn btn-light btn-sm rounded-pill px-4">
                        Read Article
                    </a>

                    <button class="btn btn-outline-light btn-sm rounded-pill px-4">
                        Save
                    </button>

                </div>
            </div>

        </div>
    </div>
</div>

@endif

