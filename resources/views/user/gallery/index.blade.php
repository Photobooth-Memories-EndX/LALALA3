@extends('layouts.sidebar-master')

@section('content')
@include('components.shared.hero-style')
<div class="container-xl py-4">
    @php
        $latestGallery = $eventphoto->first();
        $latestPhotos = $latestGallery ? (json_decode($latestGallery->photos, true) ?? []) : [];
        $latestCover = $latestGallery?->thumbnail ?: ($latestPhotos[0] ?? null);
        $latestImage = ($latestGallery && $latestCover)
            ? asset('storage/gallery/photo/' . $latestGallery->id . '/' . $latestCover)
            : null;
    @endphp

    {{-- ============================================================
         HERO GALLERY
         ============================================================ --}}
    <div class="card content-hero-card mb-4 overflow-hidden border-0 shadow-sm">
        <div class="card-body content-hero-surface text-white">
            <div class="row align-items-center g-4">

                {{-- Sisi Kiri: Teks & Badge --}}
                <div class="col-lg-8 content-hero-copy">
                    <div class="d-inline-flex align-items-center gap-2 badge bg-white text-primary mb-2 rounded-pill content-hero-badge">
                        <i class="ti ti-photo"></i>
                        GALLERY
                    </div>

                    <h1 class="mb-1 fw-bold">
                        Learning Event Gallery Hub
                    </h1>

                    <p class="mb-0 text-white-75">
                        Capturing the journey of growth and innovation across workshops, technical training, and professional development.
                    </p>

                    <div class="mt-3">
                        <a href="#gallery-list" class="btn btn-sm btn-white text-primary fw-bold rounded-pill px-3 py-2 shadow-sm">
                            <i class="ti ti-arrow-down me-1"></i> Explore Gallery
                        </a>
                    </div>
                </div>

                {{-- Sisi Kanan: Preview Media / Counter --}}
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

    {{-- ============================================================
         FILTER BAR
         ============================================================ --}}
    <div class="gallery-filter-card mb-4">
        <form action="{{ route('gallery.user.index') }}" method="GET" id="gallery-filter-form">
            <div class="row g-2 align-items-end">

                <div class="col-lg-4 col-md-6">
                    <label class="form-label text-muted small mb-1">Search Events</label>
                    <div class="input-group">
                        <span class="input-group-text bg-transparent">
                            <i class="ti ti-search"></i>
                        </span>
                        <input
                            type="text"
                            name="q"
                            value="{{ request('q') }}"
                            class="form-control"
                            placeholder="Search by title..."
                        >
                    </div>
                </div>

                @if($uploaders->count())
                <div class="col-lg-3 col-md-6">
                    <label class="form-label text-muted small mb-1">Uploader</label>
                    <select name="uploader" class="form-select">
                        <option value="">All Uploaders</option>
                        @foreach($uploaders as $up)
                            <option value="{{ $up }}" {{ request('uploader') === $up ? 'selected' : '' }}>
                                {{ $up }}
                            </option>
                        @endforeach
                    </select>
                </div>
                @endif

                @if($categories->count())
                <div class="col-lg-2 col-md-4">
                    <label class="form-label text-muted small mb-1">Category</label>
                    <select name="category" class="form-select">
                        <option value="">All Categories</option>
                        @foreach($categories as $cat)
                            <option value="{{ $cat }}" {{ request('category') === $cat ? 'selected' : '' }}>
                                {{ $cat }}
                            </option>
                        @endforeach
                    </select>
                </div>
                @endif

                <div class="col-lg-2 col-md-4">
                    <label class="form-label text-muted small mb-1">Sort By</label>
                    <select
                        name="sort"
                        class="form-select"
                        onchange="document.getElementById('gallery-filter-form').submit()"
                    >
                        <option value="latest" {{ request('sort', 'latest') === 'latest' ? 'selected' : '' }}>Latest</option>
                        <option value="oldest" {{ request('sort') === 'oldest' ? 'selected' : '' }}>Oldest</option>
                        <option value="az"     {{ request('sort') === 'az'     ? 'selected' : '' }}>A → Z</option>
                        <option value="za"     {{ request('sort') === 'za'     ? 'selected' : '' }}>Z → A</option>
                    </select>
                </div>

                <div class="col-lg-auto col-md-4 d-flex gap-2">
                    <button type="submit" class="btn btn-primary" style="background-color: #008a43; border-color: #008a43;">
                        <i class="ti ti-filter me-1"></i> Filter
                    </button>
                    @if(request('q') || request('uploader') || request('category') || (request('sort') && request('sort') !== 'latest'))
                        <a href="{{ route('gallery.user.index') }}" class="btn btn-outline-secondary" title="Reset filters">
                            <i class="ti ti-x"></i>
                        </a>
                    @endif
                </div>

            </div>
        </form>
    </div>

    {{-- ============================================================
         GALLERY GRID
         ============================================================ --}}
    <div class="row g-4" id="gallery-list">

        @forelse($eventphoto as $list)

            @php
                $photosArr = json_decode($list->photos, true);
                $cover     = $list->thumbnail;
                if (!empty($photosArr)) {
                    $cover = $photosArr[0];
                }
                $imgSrc = $cover
                    ? asset('storage/gallery/photo/' . $list->id . '/' . $cover)
                    : null;
                $pCount = is_array($photosArr) ? count($photosArr) : 0;
            @endphp

            <div class="col-xl-4 col-md-6">
                <div class="card gallery-card h-100">

                    {{-- Thumbnail --}}
                    <div class="gallery-thumb">
                        <div class="gallery-thumb-placeholder">
                            <i class="ti ti-photo"></i>
                        </div>
                        @if($imgSrc)
                            <img
                                src="{{ $imgSrc }}"
                                alt="{{ $list->title }}"
                                class="gallery-thumb-img"
                                onerror="this.style.display='none'"
                                loading="lazy"
                            >
                        @endif
                        <span class="gallery-badge">
                            {{ $list->category ?? 'Event' }}
                        </span>
                        @if($pCount > 0)
                            <span class="gallery-photo-count">
                                <i class="ti ti-photo"></i> {{ $pCount }}
                            </span>
                        @endif
                    </div>

                    {{-- Body --}}
                    <div class="card-body d-flex flex-column">
                        <h3 class="gallery-card-title">
                            {{ $list->title }}
                        </h3>

                        <div class="mt-auto">
                            <div class="d-flex justify-content-between align-items-center mb-3">
                                <div style="min-width:0">
                                    <div class="fw-semibold text-truncate">
                                        {{ $list->uploader ?? '—' }}
                                    </div>
                                    <small class="text-muted">
                                        {{ $list->created_at->diffForHumans() }}
                                    </small>
                                </div>
                                <div class="d-flex gap-2 text-muted small flex-shrink-0 ms-2">
                                    <span title="Views">
                                        <i class="ti ti-eye"></i> {{ $list->views ?? 0 }}
                                    </span>
                                    <span title="Comments">
                                        <i class="ti ti-message"></i> {{ $list->comments_count }}
                                    </span>
                                </div>
                            </div>

                            <a
                                href="{{ route('gallery.user.view', $list->slug) }}"
                                class="btn btn-primary w-100"
                            >
                                <i class="ti ti-eye me-1"></i> View Gallery
                            </a>
                        </div>
                    </div>

                </div>
            </div>

        @empty

            <div class="col-12">
                <div class="gallery-empty-state">
                    <div class="gallery-empty-icon">
                        <i class="ti ti-photo-off"></i>
                    </div>
                    <h4 class="mt-4 fw-bold">No Gallery Found</h4>
                    <p class="text-muted mt-1">
                        @if(request('q') || request('uploader') || request('category'))
                            No results match your current filters. Try adjusting your search.
                        @else
                            No event galleries have been published yet.
                        @endif
                    </p>
                    @if(request('q') || request('uploader') || request('category') || request('sort'))
                        <a href="{{ route('gallery.user.index') }}" class="btn btn-outline-primary mt-2">
                            <i class="ti ti-x me-1"></i> Clear Filters
                        </a>
                    @endif
                </div>
            </div>

        @endforelse

    </div>

    {{-- ============================================================
         PAGINATION
         ============================================================ --}}
    @if($eventphoto->hasPages())
        <div class="d-flex justify-content-center mt-5 pb-3">
            {{ $eventphoto->links() }}
        </div>
    @endif

</div>
@endsection

@section('style')
<style>
/* ============================================================
   HERO STYLES (SAMAKAN PRESISI DENGAN HERO NEWS)
   ============================================================ */
.content-hero-card {
    border-radius: 22px !important;
}

.content-hero-surface {
    min-height: 170px;
    padding: 28px 34px !important;
    display: flex;
    align-items: center;
    position: relative;
    overflow: hidden;
    
    /* Gradasi Biru */
    background: linear-gradient(
        135deg,
        #001b44 0%,
        #002b66 20%,
        #004b99 45%,
        #2a7bd1 70%,
        #82b8f5 88%,
        #dbeeff 100%
    ) !important;
}

/* Layer Radial Glow Efek Cahaya */
.content-hero-surface::before {
    content: "" !important;
    position: absolute !important;
    inset: 0 !important;
    display: block !important;
    background: radial-gradient(
        circle at 85% 20%,
        rgba(255, 255, 255, 0.22) 0%,
        transparent 28%
    ) !important;
    pointer-events: none !important;
    z-index: 1 !important;
}

.content-hero-surface > .row {
    width: 100%;
    position: relative;
    z-index: 2;
}

/* Hero Media & Image Preview Styling */
.gallery-hero-media {
    width: 220px;
    height: 130px;
    border-radius: 14px;
    overflow: hidden;
    position: relative;
    box-shadow: 0 8px 20px rgba(0, 0, 0, 0.25);
    background: rgba(255, 255, 255, 0.12);
    border: 1px solid rgba(255, 255, 255, 0.25);
    backdrop-filter: blur(6px);
}

.gallery-hero-img {
    width: 100%;
    height: 100%;
    object-fit: cover;
    display: block;
}

.kp-hero-media-placeholder {
    width: 100%;
    height: 100%;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 42px;
    color: rgba(255, 255, 255, 0.85);
    background: rgba(255, 255, 255, 0.1);
}

/* Penyesuaian Tombol "Explore Gallery" */
.content-hero-copy .btn-white {
    background: #ffffff !important;
    color: #002b66 !important;
    border: none !important;
    transition: all 0.2s ease;
    box-shadow: 0 4px 10px rgba(0, 0, 0, 0.1);
}

.content-hero-copy .btn-white:hover {
    background: #f8fafc !important;
    color: #001b44 !important;
    transform: translateY(-1px);
    box-shadow: 0 6px 14px rgba(0, 0, 0, 0.15);
}

/* Dark Mode Support */
[data-bs-theme="dark"] .content-hero-surface,
body.dark-mode .content-hero-surface {
    background: linear-gradient(
        135deg,
        #00112c 0%,
        #001b44 20%,
        #003366 45%,
        #1d5996 70%,
        #4e82be 88%,
        #82b8f5 100%
    ) !important;
}

/* ============================================================
   FILTER & CARD STYLES
   ============================================================ */
.gallery-filter-card {
    background: var(--tblr-bg-surface, #fff);
    border: 1px solid var(--tblr-border-color, rgba(98,105,118,.12));
    border-radius: 16px;
    padding: 20px 24px;
}

.gallery-card {
    border: 1px solid var(--tblr-border-color, rgba(98,105,118,.12));
    border-radius: 18px;
    overflow: hidden;
    transition: transform .25s ease, box-shadow .25s ease;
    background: var(--tblr-bg-surface, #fff);
}

.gallery-card:hover {
    transform: translateY(-3px);
    box-shadow: 0 10px 28px rgba(83, 86, 90, .10);
}

.gallery-thumb {
    position: relative;
    height: 200px;
    background: var(--tblr-bg-surface-secondary, #f6f8fb);
    overflow: hidden;
    flex-shrink: 0;
}

.gallery-thumb-placeholder {
    position: absolute;
    inset: 0;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 48px;
    color: var(--tblr-muted, #9ba9b4);
}

.gallery-thumb-img {
    position: absolute;
    inset: 0;
    width: 100%;
    height: 100%;
    object-fit: cover;
}

.gallery-badge {
    position: absolute;
    top: 12px;
    left: 12px;
    background: #008a43;
    color: #fff;
    font-size: 10px;
    font-weight: 700;
    padding: 4px 12px;
    border-radius: 999px;
    text-transform: uppercase;
}

.gallery-photo-count {
    position: absolute;
    bottom: 12px;
    right: 12px;
    background: rgba(0,0,0,.65);
    color: #fff;
    font-size: 11px;
    padding: 4px 10px;
    border-radius: 999px;
}

.gallery-card-title {
    font-size: 16px;
    font-weight: 700;
    line-height: 1.4;
    display: -webkit-box;
    -webkit-line-clamp: 2;
    -webkit-box-orient: vertical;
    overflow: hidden;
    min-height: 45px;
    color: var(--tblr-body-color);
    margin-bottom: 16px;
}

.gallery-empty-state {
    text-align: center;
    padding: 48px 20px;
    background: var(--tblr-bg-surface, #fff);
    border-radius: 16px;
    border: 1px solid var(--tblr-border-color, rgba(98,105,118,.12));
}

.gallery-empty-icon {
    font-size: 56px;
    color: var(--tblr-muted, #9ba9b4);
}

@media (max-width: 991.98px) {
    .content-hero-surface {
        padding: 20px !important;
    }
    .gallery-hero-media {
        width: 100%;
        max-width: 280px;
        height: 150px;
        margin-top: 8px;
    }
}
</style>
@endsection