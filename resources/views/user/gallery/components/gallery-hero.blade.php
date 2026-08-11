@php
    $latestGallery = $latestGallery ?? \App\Models\EventPhoto::where('active', 'Active')->latest()->first();
    $latestPhotos = $latestGallery ? (json_decode($latestGallery->photos, true) ?? []) : [];
    $latestPhotos = is_array($latestPhotos) ? $latestPhotos : [];
    $latestCover = $latestGallery?->thumbnail ?: ($latestPhotos[0] ?? null);
    $latestImage = ($latestGallery && $latestCover)
        ? asset('storage/gallery/photo/' . $latestGallery->id . '/' . $latestCover)
        : null;
@endphp

<section class="card content-hero-card gallery-hero mb-4 overflow-hidden border-0 shadow-sm rounded-4" aria-labelledby="gallery-page-title">
    <div class="card-body content-hero-surface text-white py-3 px-4">
        <div class="row align-items-center gy-2">
            <!-- Kolom Kiri: Teks & Tombol -->
            <div class="col-lg-8 col-xl-8 content-hero-copy">
                <span class="d-inline-flex align-items-center gap-1 badge bg-white text-primary mb-2 rounded-pill content-hero-badge px-3 py-1 fw-bold shadow-sm small">
                    <i class="ti ti-photo fs-6"></i>
                    <span>GALLERY</span>
                </span>

                <h1 id="gallery-page-title" class="mb-1 fw-bold display-6 text-white">Learning Event Gallery</h1>

                <p class="mb-3 text-white-75 small pe-lg-4">
                    Capturing the journey of growth and innovation across workshops, technical training, and professional development.
                </p>

                <div>
                    <a href="#gallery-results" class="btn btn-white btn-sm text-primary fw-semibold rounded-pill px-3 py-1.5 shadow-sm">
                        <i class="ti ti-arrow-down me-1"></i> Explore Gallery
                    </a>
                </div>
            </div>

            <!-- Kolom Kanan: Preview Gambar 16:9 Ringkas -->
            <div class="col-lg-4 col-xl-4 d-none d-lg-flex justify-content-end align-items-center">
                <div class="gallery-hero-media ratio ratio-16x9 rounded-3 overflow-hidden shadow-sm border border-2 border-white-20 w-100" style="max-width: 260px;">
                    @if($latestImage)
                        <a href="{{ $latestGallery ? route('gallery.user.view', array_merge(['slug' => $latestGallery->slug], request()->query())) : '#' }}" class="d-block w-100 h-100 text-decoration-none">
                            <img
                                src="{{ $latestImage }}"
                                alt="Latest gallery: {{ $latestGallery->title ?: 'Learning event' }}"
                                class="gallery-hero-img w-100 h-100 object-fit-cover"
                                data-gallery-image
                            >
                        </a>
                    @else
                        <div class="kp-hero-media-placeholder w-100 h-100 d-flex align-items-center justify-content-center">
                            <i class="ti ti-photo fs-3"></i>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</section>
