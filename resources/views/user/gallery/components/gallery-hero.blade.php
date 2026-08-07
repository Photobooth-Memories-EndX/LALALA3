@php
    $latestGallery = $latestGallery ?? \App\Models\EventPhoto::where('active', 'Active')->latest()->first();
    $latestPhotos = $latestGallery ? (json_decode($latestGallery->photos, true) ?? []) : [];
    $latestPhotos = is_array($latestPhotos) ? $latestPhotos : [];
    $latestCover = $latestGallery?->thumbnail ?: ($latestPhotos[0] ?? null);
    $latestImage = ($latestGallery && $latestCover)
        ? asset('storage/gallery/photo/' . $latestGallery->id . '/' . $latestCover)
        : null;
@endphp

<section class="card content-hero-card gallery-hero mb-4 overflow-hidden border-0 shadow-sm" aria-labelledby="gallery-page-title">
    <div class="card-body content-hero-surface text-white">
        <div class="row align-items-center g-4">
            <div class="col-lg-8 content-hero-copy">
                <div class="d-inline-flex align-items-center gap-2 badge bg-white text-primary mb-2 rounded-pill content-hero-badge px-3 py-2 shadow-sm">
                    <i class="ti ti-photo"></i>
                    <span>GALLERY</span>
                </div>

                <h1 id="gallery-page-title" class="mb-1 fw-bold">Learning Event Gallery Hub</h1>

                <p class="mb-0 text-white-75">
                    Capturing the journey of growth and innovation across workshops, technical training, and professional development.
                </p>

                <div class="mt-3">
                    <a href="#gallery-list" class="btn btn-sm btn-white text-primary fw-bold rounded-pill px-3 py-2 shadow-sm">
                        <i class="ti ti-arrow-down me-1"></i> Explore Gallery
                    </a>
                </div>
            </div>

            <div class="col-lg-4 d-flex justify-content-lg-end justify-content-center">
                <div class="gallery-hero-media position-relative rounded-4 overflow-hidden shadow">
                    @if($latestImage)
                        <a href="{{ $latestGallery ? route('gallery.user.view', $latestGallery->slug) : '#' }}" class="d-block text-decoration-none">
                            <img
                                src="{{ $latestImage }}"
                                alt="Latest gallery: {{ $latestGallery->title ?: 'Learning event' }}"
                                class="gallery-hero-img w-100"
                                data-gallery-image
                                style="object-fit: cover; max-height: 180px; border-radius: 16px;"
                            >
                        </a>
                    @else
                        <div class="kp-hero-media-placeholder">
                            <i class="ti ti-photo"></i>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</section>
