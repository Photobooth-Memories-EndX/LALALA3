@extends('layouts.sidebar-master')

@php
    $url_lightbox_js = asset('Tabler/dist/libs/fslightbox/index.js?1684106062');

    $heroBg = '';
    if (!empty($eventphoto->thumbnail)) {
        $heroBg = asset('storage/gallery/photo/' . $eventphoto->id . '/' . $eventphoto->thumbnail);
    } elseif (!empty($photos)) {
        $heroBg = asset('storage/gallery/photo/' . $eventphoto->id . '/' . $photos[0]);
    }
@endphp

@section('content')
<div class="container-xl py-4">

    {{-- BACK --}}
    <a href="{{ route('gallery.user.index') }}" class="btn btn-outline-secondary mb-4 rounded-pill px-3">
        <i class="ti ti-arrow-left me-1"></i> Back to Gallery
    </a>

    {{-- ============================================================
         HERO
         ============================================================ --}}
    <div
        class="view-hero mb-4"
        @if($heroBg) style="background-image: url('{{ $heroBg }}')" @endif
    >
        <div class="view-overlay">
            <span class="view-badge">
                {{ strtoupper($eventphoto->category ?? 'Workshop') }}
            </span>
            <h1 class="view-title">{{ $eventphoto->title }}</h1>
            <div class="view-meta">
                <span>
                    <i class="ti ti-calendar me-1"></i>
                    {{ date('d M Y', strtotime($eventphoto->eventdate)) }}
                </span>
                <span>
                    <i class="ti ti-photo me-1"></i>
                    {{ $photoCount }} Photos
                </span>
                <span>
                    <i class="ti ti-eye me-1"></i>
                    {{ $eventphoto->views }} Views
                </span>
                @if($eventphoto->uploader)
                    <span>
                        <i class="ti ti-user me-1"></i>
                        {{ $eventphoto->uploader }}
                    </span>
                @endif
            </div>
        </div>
    </div>

    {{-- ============================================================
         CONTENT ROW
         ============================================================ --}}
    <div class="row g-4">

        {{-- PHOTO GRID --}}
        <div class="col-lg-8">
            <div class="card view-card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h3 class="card-title mb-0">
                        <i class="ti ti-layout-grid me-2 text-primary"></i>Event Gallery
                    </h3>
                    <span class="badge bg-primary-lt text-primary fw-semibold">
                        {{ $photoCount }} Photos
                    </span>
                </div>
                <div class="card-body">
                    @if(!empty($photos))
                        <div class="view-photo-grid">
                            @foreach($photos as $photo)
                                @php
                                    $photoSrc = asset('storage/gallery/photo/' . $eventphoto->id . '/' . $photo);
                                @endphp
                                <a
                                    data-fslightbox="gallery"
                                    href="{{ $photoSrc }}"
                                    class="view-photo-item"
                                >
                                    <div class="view-photo-placeholder">
                                        <i class="ti ti-photo"></i>
                                    </div>
                                    <img
                                        src="{{ $photoSrc }}"
                                        alt="{{ $eventphoto->title }}"
                                        class="view-photo-img"
                                        onerror="this.style.display='none'"
                                        loading="lazy"
                                    >
                                </a>
                            @endforeach
                        </div>
                    @else
                        <div class="text-center py-5">
                            <div class="view-empty-icon">
                                <i class="ti ti-photo-off"></i>
                            </div>
                            <p class="text-muted mt-3">No photos available for this event.</p>
                        </div>
                    @endif
                </div>
            </div>
        </div>

        {{-- SIDEBAR --}}
        <div class="col-lg-4">
            <div class="card view-card view-info-card">
                <div class="card-header">
                    <h3 class="card-title mb-0">
                        <i class="ti ti-info-circle me-2 text-primary"></i>Event Information
                    </h3>
                </div>
                <div class="card-body p-0">

                    <div class="view-info-row">
                        <div class="text-muted small mb-1">Uploader</div>
                        <div class="fw-semibold">{{ $eventphoto->uploader ?? '—' }}</div>
                    </div>

                    <div class="view-info-row">
                        <div class="text-muted small mb-1">Category</div>
                        <div>
                            <span class="badge bg-blue-lt text-blue">
                                {{ $eventphoto->category ?? 'Workshop' }}
                            </span>
                        </div>
                    </div>

                    <div class="view-info-row">
                        <div class="text-muted small mb-1">Event Date</div>
                        <div class="fw-semibold">
                            {{ date('d M Y', strtotime($eventphoto->eventdate)) }}
                        </div>
                    </div>

                    <div class="view-info-row">
                        <div class="text-muted small mb-1">Total Photos</div>
                        <div class="fw-semibold">{{ $photoCount }} Photos</div>
                    </div>

                    <div class="view-info-row" style="border-bottom:none">
                        <div class="text-muted small mb-1">Total Views</div>
                        <div class="fw-semibold">{{ $eventphoto->views }}</div>
                    </div>

                </div>
                <div class="card-footer bg-transparent">
                    <a href="{{ route('gallery.user.index') }}" class="btn btn-outline-secondary w-100">
                        <i class="ti ti-arrow-left me-1"></i> Back to Gallery
                    </a>
                </div>
            </div>
        </div>

    </div>

    {{-- ============================================================
         DISCUSSION
         ============================================================ --}}
    <div class="card view-card mt-4 mb-4">
        <div class="card-header">
            <h3 class="card-title mb-0">
                <i class="ti ti-messages me-2 text-primary"></i>Discussion &amp; Reflection
            </h3>
        </div>
        <div class="card-body">
            <x-comment
                :content="$eventphoto"
                :comment="$comment"
                type="eventphoto"
            />
        </div>
    </div>

</div>
@endsection

@section('style')
<style>

/* ============================================================
   HERO
   ============================================================ */
.view-hero {
    position: relative;
    min-height: 400px;
    border-radius: 20px;
    overflow: hidden;
    background-color: #1d4ed8;
    background-size: cover;
    background-position: center;
    background-repeat: no-repeat;
}

.view-overlay {
    position: absolute;
    inset: 0;
    background: linear-gradient(
        to top,
        rgba(0,0,0,.85) 0%,
        rgba(0,0,0,.35) 55%,
        rgba(0,0,0,.08) 100%
    );
    display: flex;
    flex-direction: column;
    justify-content: flex-end;
    padding: 40px;
}

.view-badge {
    display: inline-block;
    width: max-content;
    background: #2563eb;
    color: #fff;
    font-size: 11px;
    font-weight: 700;
    letter-spacing: .08em;
    text-transform: uppercase;
    padding: 6px 16px;
    border-radius: 999px;
    margin-bottom: 14px;
}

.view-title {
    color: #fff;
    font-size: clamp(22px, 4vw, 40px);
    font-weight: 800;
    line-height: 1.2;
    max-width: 800px;
    margin: 0 0 14px;
    display: -webkit-box;
    -webkit-line-clamp: 3;
    -webkit-box-orient: vertical;
    overflow: hidden;
}

.view-meta {
    display: flex;
    flex-wrap: wrap;
    gap: 14px;
    color: rgba(255,255,255,.85);
    font-size: 14px;
}

/* ============================================================
   SECTION CARD
   ============================================================ */
.view-card {
    border: 1px solid var(--tblr-border-color, rgba(98,105,118,.16));
    border-radius: 16px;
    background: var(--tblr-bg-surface, #fff);
}

.view-info-card {
    position: sticky;
    top: 20px;
}

/* ============================================================
   PHOTO GRID
   ============================================================ */
.view-photo-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(180px, 1fr));
    gap: 10px;
}

.view-photo-item {
    position: relative;
    display: block;
    height: 190px;
    border-radius: 12px;
    overflow: hidden;
    background: var(--tblr-bg-surface-secondary, #f6f8fb);
}

.view-photo-placeholder {
    position: absolute;
    inset: 0;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 30px;
    color: var(--tblr-muted, #9ba9b4);
}

.view-photo-img {
    position: absolute;
    inset: 0;
    width: 100%;
    height: 100%;
    object-fit: cover;
    transition: transform .3s ease;
}

.view-photo-item:hover .view-photo-img {
    transform: scale(1.06);
}

/* ============================================================
   INFO ROWS
   ============================================================ */
.view-info-row {
    padding: 14px 20px;
    border-bottom: 1px solid var(--tblr-border-color, rgba(98,105,118,.1));
}

/* ============================================================
   EMPTY
   ============================================================ */
.view-empty-icon {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    width: 72px;
    height: 72px;
    border-radius: 50%;
    background: var(--tblr-bg-surface-secondary, #f6f8fb);
    font-size: 32px;
    color: var(--tblr-muted, #9ba9b4);
}

/* ============================================================
   DARK MODE
   ============================================================ */
[data-bs-theme="dark"] .view-card {
    background: var(--tblr-bg-surface, #1f2937);
    border-color: rgba(255,255,255,.08);
}

[data-bs-theme="dark"] .view-photo-item {
    background: #111827;
}

[data-bs-theme="dark"] .view-info-row {
    border-color: rgba(255,255,255,.07);
}

[data-bs-theme="dark"] .view-empty-icon {
    background: #111827;
}

[data-bs-theme="dark"] .card-footer {
    border-color: rgba(255,255,255,.07);
}

/* ============================================================
   RESPONSIVE
   ============================================================ */
@media (max-width: 768px) {
    .view-overlay {
        padding: 24px;
    }

    .view-hero {
        min-height: 320px;
    }

    .view-photo-grid {
        grid-template-columns: repeat(auto-fill, minmax(140px, 1fr));
        gap: 8px;
    }

    .view-photo-item {
        height: 150px;
    }

    .view-meta {
        gap: 10px;
        font-size: 13px;
    }

    .view-info-card {
        position: static;
    }
}

</style>
@endsection

@section('script')
<script src="{{ $url_lightbox_js }}"></script>
@endsection
