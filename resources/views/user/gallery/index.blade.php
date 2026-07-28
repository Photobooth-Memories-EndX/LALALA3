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
         HERO
         ============================================================ --}}
    <div class="gallery-hero kp-enterprise-hero mb-4">
        <div class="row align-items-center g-4">

            <div class="col-lg-7">
                <span class="gallery-hero-badge kp-hero-badge">INTERNAL MEMORIES</span>
                <h1 class="gallery-hero-title mt-3">
                    Learning Event<br>Gallery Hub
                </h1>
                <p class="gallery-hero-sub mt-3">
                    Capturing the journey of growth and innovation across workshops,
                    technical training and professional development.
                </p>
                <a href="#gallery-list" class="btn btn-light btn-lg mt-3 rounded-pill px-4">
                    <i class="ti ti-arrow-down me-1"></i> Explore Gallery
                </a>
            </div>

            <div class="col-lg-5">
                <div class="kp-hero-media gallery-hero-media">
                    @if($latestImage)
                        <img
                            src="{{ $latestImage }}"
                            alt="{{ $latestGallery->title }}"
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

                <div class="row g-3 mt-3">
                    <div class="col-6">
                        <div class="gallery-stat-box kp-hero-stat">
                            <div class="gallery-stat-icon">
                                <i class="ti ti-calendar-event"></i>
                            </div>
                            <div class="gallery-stat-num">{{ number_format($totalEvents) }}</div>
                            <div class="gallery-stat-label">Total Events</div>
                        </div>
                    </div>
                    <div class="col-6">
                        <div class="gallery-stat-box kp-hero-stat">
                            <div class="gallery-stat-icon">
                                <i class="ti ti-photo"></i>
                            </div>
                            <div class="gallery-stat-num">{{ number_format($totalPhotos) }}</div>
                            <div class="gallery-stat-label">Total Photos</div>
                        </div>
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
                    <button type="submit" class="btn btn-primary">
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
   HERO
   ============================================================ */
.gallery-hero {
    border-radius: 20px;
    padding: 32px;
    color: #fff;
}

.gallery-hero-badge {
    display: inline-block;
    background: rgba(255,255,255,.18);
    border: 1px solid rgba(255,255,255,.25);
    border-radius: 999px;
    padding: 6px 16px;
    font-size: 11px;
    font-weight: 600;
    letter-spacing: .08em;
}

.gallery-hero-title {
    font-size: clamp(30px, 5vw, 52px);
    font-weight: 800;
    line-height: 1.1;
    color: #fff;
    margin: 0;
}

.gallery-hero-sub {
    font-size: 16px;
    color: rgba(255,255,255,.8);
    max-width: 540px;
    margin: 0;
}

.gallery-stat-box {
    border: 1px solid rgba(255,255,255,.2);
    border-radius: 16px;
    padding: 16px 14px;
    text-align: center;
    color: #fff;
    backdrop-filter: blur(4px);
}

.gallery-stat-icon {
    font-size: 22px;
    opacity: .8;
    margin-bottom: 4px;
}

.gallery-stat-num {
    font-size: 30px;
    font-weight: 800;
    line-height: 1;
}

.gallery-stat-label {
    font-size: 11px;
    opacity: .75;
    margin-top: 4px;
    text-transform: uppercase;
    letter-spacing: .04em;
}

/* ============================================================
   FILTER BAR
   ============================================================ */
.gallery-filter-card {
    background: var(--tblr-bg-surface, #fff);
    border: 1px solid var(--tblr-border-color, rgba(98,105,118,.16));
    border-radius: 16px;
    padding: 20px 24px;
}

/* ============================================================
   GALLERY CARD
   ============================================================ */
.gallery-card {
    border: 1px solid var(--tblr-border-color, rgba(98,105,118,.16));
    border-radius: 18px;
    overflow: hidden;
    transition: transform .25s ease, box-shadow .25s ease;
    background: var(--tblr-bg-surface, #fff);
}

.gallery-card:hover {
    transform: translateY(-5px);
    box-shadow: 0 16px 40px rgba(37,99,235,.14);
}

/* ============================================================
   THUMBNAIL
   ============================================================ */
.gallery-thumb {
    position: relative;
    height: 220px;
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
    transition: transform .3s ease;
}

.gallery-card:hover .gallery-thumb-img {
    transform: scale(1.05);
}

.gallery-badge {
    position: absolute;
    top: 12px;
    left: 12px;
    background: #05507D;
    color: #fff;
    font-size: 10px;
    font-weight: 700;
    padding: 4px 12px;
    border-radius: 999px;
    text-transform: uppercase;
    letter-spacing: .05em;
    z-index: 1;
}

.gallery-photo-count {
    position: absolute;
    bottom: 12px;
    right: 12px;
    background: rgba(0,0,0,.55);
    color: #fff;
    font-size: 11px;
    padding: 4px 10px;
    border-radius: 999px;
    backdrop-filter: blur(4px);
    z-index: 1;
}

/* ============================================================
   CARD TITLE
   ============================================================ */
.gallery-card-title {
    font-size: 17px;
    font-weight: 700;
    line-height: 1.4;
    display: -webkit-box;
    -webkit-line-clamp: 2;
    -webkit-box-orient: vertical;
    overflow: hidden;
    min-height: 48px;
    color: var(--tblr-body-color);
    margin-bottom: 16px;
}

/* ============================================================
   EMPTY STATE
   ============================================================ */
.gallery-empty-state {
    text-align: center;
    padding: 80px 20px;
}

.gallery-empty-icon {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    width: 80px;
    height: 80px;
    border-radius: 50%;
    background: var(--tblr-bg-surface-secondary, #f6f8fb);
    font-size: 36px;
    color: var(--tblr-muted, #9ba9b4);
}

/* ============================================================
   DARK MODE
   ============================================================ */
[data-bs-theme="dark"] .gallery-filter-card {
    background: var(--tblr-bg-surface, #1f2937);
    border-color: rgba(255,255,255,.08);
}

[data-bs-theme="dark"] .gallery-card {
    background: var(--tblr-bg-surface, #1f2937);
    border-color: rgba(255,255,255,.08);
}

[data-bs-theme="dark"] .gallery-thumb {
    background: #53565A;
}

[data-bs-theme="dark"] .gallery-empty-icon {
    background: #53565A;
}

[data-bs-theme="dark"] .form-control,
[data-bs-theme="dark"] .form-select {
    background-color: #53565A;
    border-color: rgba(255,255,255,.1);
    color: #e5e7eb;
}

[data-bs-theme="dark"] .form-control::placeholder {
    color: #6b7280;
}

[data-bs-theme="dark"] .input-group-text {
    background-color: #53565A;
    border-color: rgba(255,255,255,.1);
    color: #9ca3af;
}

[data-bs-theme="dark"] .gallery-card-title {
    color: #f1f5f9;
}

/* ============================================================
   RESPONSIVE
   ============================================================ */
@media (max-width: 576px) {
    .gallery-hero {
        padding: 28px 20px;
    }
}

</style>
@endsection
