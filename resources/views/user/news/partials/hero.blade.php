@php
    $featured = $news->first();
    $readTime = ceil(str_word_count(strip_tags($featured->description ?? '')) / 200);
    $category = $featured->category ?? 'Technology';
@endphp

@if($featured)

<div class="card border-0 overflow-hidden mb-5 owl-hero-card">

    <div class="row g-0 align-items-center h-100">

        {{-- LEFT CONTENT --}}
        <div class="col-lg-6 d-flex align-items-center">

            <div class="card-body p-5 p-xl-5 text-white position-relative z-2">

                <span class="badge owl-hero-badge bg-white text-primary rounded-pill fw-semibold px-3 py-2 mb-4">
                    <i class="ti ti-star-filled me-2"></i>
                    Featured story
                </span>

                <h1 class="owl-hero-title fw-bold mb-4">
                    {{ $featured->title }}
                </h1>

                <p class="owl-hero-description mb-5">
                    {{ Str::limit(strip_tags($featured->description), 240) }}
                </p>

                <div class="d-flex flex-column flex-sm-row gap-3 gap-sm-4 mb-5">
                    <span class="owl-hero-pill">
                        <i class="ti ti-clock me-2"></i>
                        {{ $readTime }} min read
                    </span>
                    <span class="owl-hero-pill">
                        <i class="ti ti-eye me-2"></i>
                        {{ $featured->views }} views
                    </span>
                    <span class="owl-hero-pill owl-hero-pill-secondary">
                        <i class="ti ti-tag me-2"></i>
                        {{ $category }}
                    </span>
                </div>

                <div class="d-flex flex-column flex-sm-row gap-3">
                    <a href="{{ route('news.user.view', $featured->slug) }}"
                       class="btn btn-light rounded-pill px-5 py-3 owl-btn-hero fw-semibold">
                        <i class="ti ti-book-open me-2"></i>
                        Read Article
                    </a>
                    <button class="btn btn-outline-light rounded-pill px-5 py-3 owl-btn-hero-outline fw-semibold"
                            data-bs-toggle="tooltip"
                            title="Save for later">
                        <i class="ti ti-bookmark me-2"></i>
                        Save
                    </button>
                </div>

            </div>

        </div>

        {{-- RIGHT IMAGE --}}
        <div class="col-lg-6 position-relative overflow-hidden h-100">

            <div class="owl-hero-image-wrapper h-100">
                <img src="{{ asset('default-video.jpg') }}"
                     class="owl-hero-image"
                     alt="{{ $featured->title }}"
                     loading="lazy">

                <div class="owl-hero-overlay"></div>
                <div class="owl-hero-glow"></div>
            </div>

        </div>

    </div>

</div>

@endif
