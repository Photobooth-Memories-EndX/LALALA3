@php
    $latestGallery = $eventphoto->first();
    $latestPhotos = $latestGallery ? (json_decode($latestGallery->photos, true) ?? []) : [];
    $latestCover = $latestGallery?->thumbnail ?: ($latestPhotos[0] ?? null);
    $latestImage = ($latestGallery && $latestCover)
        ? asset('storage/gallery/photo/' . $latestGallery->id . '/' . $latestCover)
        : null;
@endphp

<div class="card content-hero-card mb-4 overflow-hidden border-0 shadow-sm">
    <div class="card-body content-hero-surface text-white">
        <div class="row align-items-center g-4">
            <div class="col-lg-8 content-hero-copy">
                <div class="d-inline-flex align-items-center gap-2 badge bg-white text-primary mb-2 rounded-pill content-hero-badge">
                    <i class="ti ti-photo"></i>
                    GALLERY
                </div>

                <h1 class="mb-1 fw-bold">Learning Event Gallery Hub</h1>

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
                <div class="gallery-hero-media">
                    @if($latestImage)
                        <img
                            src="{{ $latestImage }}"
                            alt="{{ $latestGallery->title }}"
                            class="gallery-hero-img"
                            onerror="this.style.display='none';this.nextElementSibling.style.display='flex'"
                        >
                        <div class="kp-hero-media-placeholder" style="display:none">
                            <i class="ti ti-photo"></i>
                        </div>
                    @else
                        <div class="kp-hero-media-placeholder">
                            <i class="ti ti-photo"></i>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>
