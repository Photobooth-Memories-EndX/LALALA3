@extends('layouts.sidebar-master')

@php
    $urlLightboxJs = asset('Tabler/dist/libs/fslightbox/index.js?1684106062');
    $validPhotos = collect($photos)->filter(static fn ($photo) => is_string($photo) && trim($photo) !== '')->values();
    $heroFile = $eventphoto->thumbnail ?: $validPhotos->first();
    $heroImage = $heroFile
        ? asset('storage/gallery/photo/' . $eventphoto->id . '/' . $heroFile)
        : null;
    $eventDate = $eventphoto->eventdate
        ? date('d M Y', strtotime($eventphoto->eventdate))
        : 'Date unavailable';
@endphp

@section('content')
<main class="container-xl py-4 gallery-page gallery-detail-page">
    <a href="{{ route('gallery.user.index') }}" class="btn btn-outline-secondary mb-4 px-3 gallery-back-link">
        <i class="ti ti-arrow-left me-1" aria-hidden="true"></i> Back to Gallery
    </a>

    <section class="gallery-view-hero mb-4" aria-labelledby="gallery-detail-title">
        @if($heroImage)
            <img src="{{ $heroImage }}" alt="" class="gallery-view-hero-image" data-gallery-image aria-hidden="true">
        @endif
        <div class="gallery-view-overlay">
            <span class="gallery-view-badge">{{ $eventphoto->category ?: 'Event' }}</span>
            <h1 class="gallery-view-title" id="gallery-detail-title">{{ $eventphoto->title ?: 'Untitled Gallery' }}</h1>
            <div class="gallery-view-meta">
                <span><i class="ti ti-calendar me-1" aria-hidden="true"></i>{{ $eventDate }}</span>
                <span><i class="ti ti-photo me-1" aria-hidden="true"></i>{{ $photoCount }} Photos</span>
                <span><i class="ti ti-eye me-1" aria-hidden="true"></i>{{ number_format((int) $eventphoto->views) }} Views</span>
                @if($eventphoto->uploader)
                    <span><i class="ti ti-user me-1" aria-hidden="true"></i>{{ $eventphoto->uploader }}</span>
                @endif
            </div>
        </div>
    </section>

    <div class="row g-4">
        <div class="col-lg-8">
            <section class="card gallery-view-card" aria-labelledby="gallery-photo-grid-title">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h2 class="card-title mb-0" id="gallery-photo-grid-title">
                        <i class="ti ti-layout-grid me-2 text-primary" aria-hidden="true"></i>Event Gallery
                    </h2>
                    <span class="badge bg-primary-lt text-primary fw-semibold">{{ $photoCount }} Photos</span>
                </div>
                <div class="card-body">
                    @if($validPhotos->isNotEmpty())
                        <div class="gallery-view-photo-grid">
                            @foreach($validPhotos as $index => $photo)
                                @php($photoSrc = asset('storage/gallery/photo/' . $eventphoto->id . '/' . $photo))
                                <a data-fslightbox="gallery" href="{{ $photoSrc }}" class="gallery-view-photo-item"
                                    aria-label="Open photo {{ $index + 1 }} of {{ $photoCount }} from {{ $eventphoto->title ?: 'this event' }}">
                                    <span class="gallery-view-photo-placeholder" aria-hidden="true"><i class="ti ti-photo"></i></span>
                                    <img src="{{ $photoSrc }}" alt="{{ $eventphoto->title ?: 'Event gallery' }} — photo {{ $index + 1 }}"
                                        class="gallery-view-photo-img" data-gallery-image loading="lazy">
                                </a>
                            @endforeach
                        </div>
                    @else
                        <div class="gallery-empty-state border-0">
                            <div class="gallery-empty-icon"><i class="ti ti-photo-off" aria-hidden="true"></i></div>
                            <h3 class="h4 mt-3">No photos available</h3>
                            <p class="text-muted mb-0">Photos for this event have not been published yet.</p>
                        </div>
                    @endif
                </div>
            </section>
        </div>

        <aside class="col-lg-4" aria-label="Event information">
            <div class="card gallery-view-card gallery-view-info-card">
                <div class="card-header">
                    <h2 class="card-title mb-0"><i class="ti ti-info-circle me-2 text-primary" aria-hidden="true"></i>Event Information</h2>
                </div>
                <div class="card-body p-0">
                    <div class="gallery-view-info-row"><div class="text-muted small mb-1">Uploader</div><div class="fw-semibold">{{ $eventphoto->uploader ?: '—' }}</div></div>
                    <div class="gallery-view-info-row"><div class="text-muted small mb-1">Category</div><span class="badge bg-blue-lt text-blue">{{ $eventphoto->category ?: 'Event' }}</span></div>
                    <div class="gallery-view-info-row"><div class="text-muted small mb-1">Event Date</div><div class="fw-semibold">{{ $eventDate }}</div></div>
                    <div class="gallery-view-info-row"><div class="text-muted small mb-1">Total Photos</div><div class="fw-semibold">{{ $photoCount }} Photos</div></div>
                    <div class="gallery-view-info-row"><div class="text-muted small mb-1">Total Views</div><div class="fw-semibold">{{ number_format((int) $eventphoto->views) }}</div></div>
                </div>
                <div class="card-footer bg-transparent">
                    <a href="{{ route('gallery.user.index') }}" class="btn btn-outline-secondary w-100">
                        <i class="ti ti-arrow-left me-1" aria-hidden="true"></i> Back to Gallery
                    </a>
                </div>
            </div>
        </aside>
    </div>

    <section class="card gallery-view-card mt-4 mb-4" aria-labelledby="gallery-discussion-title">
        <div class="card-header">
            <h2 class="card-title mb-0" id="gallery-discussion-title">
                <i class="ti ti-messages me-2 text-primary" aria-hidden="true"></i>Discussion &amp; Reflection
            </h2>
        </div>
        <div class="card-body">
            <x-comment :content="$eventphoto" :comment="$comment" type="eventphoto" />
        </div>
    </section>
</main>
@endsection

@section('style')
@include('user.gallery.components.gallery-style')
@endsection

@section('script')
<script src="{{ $urlLightboxJs }}"></script>
@include('user.gallery.components.gallery-script')
@endsection
