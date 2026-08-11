@php
    $totalNewsCount = $newsCount ?? (method_exists($news, 'total') ? $news->total() : count($news));
@endphp

@include('components.shared.hero-style')

<div class="card content-hero-card news-hero-card mb-4 overflow-hidden border-0 shadow-sm">
    <div class="card-body content-hero-surface news-hero-surface text-white">
        <div class="row align-items-center g-4">
            <div class="col-lg-8 news-hero-copy">
                <div class="d-inline-flex align-items-center gap-2 badge bg-white text-primary mb-2 rounded-pill content-hero-badge px-3 py-2 shadow-sm">
                    <i class="ti ti-sparkles"></i>
                    <span class="fw-bold">News Hub</span>
                </div>

                <h1 class="mb-1 fw-bold display-6">News</h1>

                <p class="mb-0 text-white-75">
                    Get the latest updates, articles, and announcements across OWL Learning Hub.
                </p>
            </div>

            <div class="col-lg-4 d-flex justify-content-lg-end text-end text-white">
                <div class="d-flex flex-column align-items-end justify-content-center">
                    <div class="text-uppercase fw-bold big" style="letter-spacing: 0.5px;">ALL NEWS</div>
                    <div class="display-4 fw-bold lh-1 mt-1">{{ $totalNewsCount }}</div>
                </div>
            </div>
        </div>
    </div>
</div>
