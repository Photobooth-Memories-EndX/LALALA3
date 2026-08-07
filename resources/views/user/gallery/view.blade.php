@extends('layouts.sidebar-master')

@php
    $urlLightboxJs = asset('Tabler/dist/libs/fslightbox/index.js?1684106062');
    $validPhotos = collect($photos)->filter(static fn ($photo) => is_string($photo) && trim($photo) !== '')->values();
    $eventDate = $eventphoto->eventdate
        ? date('d M Y', strtotime($eventphoto->eventdate))
        : 'Date unavailable';
@endphp

@section('content')
<main class="container-xl py-4 gallery-page gallery-detail-page">

    {{-- 1. BACK BUTTON --}}
    <a href="{{ route('gallery.user.index') }}" class="btn btn-link text-decoration-none ps-0 mb-3">
        <i class="ti ti-arrow-left me-1"></i> Back
    </a>

    {{-- 2. COMPACT HERO BIRU GRADASI --}}
    <section class="card content-hero-card gallery-hero mb-4 overflow-hidden border-0 shadow-sm" aria-labelledby="gallery-detail-title">
        <div class="card-body content-hero-surface text-white p-4 p-lg-5">
            <div class="row align-items-center g-3">
                <div class="col-lg-12">
                    <div class="d-inline-flex align-items-center gap-2 badge bg-white text-primary mb-2 rounded-pill content-hero-badge px-3 py-2 shadow-sm">
                        <i class="ti ti-photo"></i>
                        <span>{{ $eventphoto->category ?: 'Event' }}</span>
                    </div>

                    <h1 class="mb-2 fw-bold text-white display-6" id="gallery-detail-title">
                        {{ $eventphoto->title ?: 'Untitled Gallery' }}
                    </h1>

                    <div class="d-flex flex-wrap gap-3 align-items-center text-white-75 mt-3">
                        <span class="d-inline-flex align-items-center">
                            <i class="ti ti-calendar me-1"></i>{{ $eventDate }}
                        </span>
                        <span class="d-inline-flex align-items-center">
                            <i class="ti ti-photo me-1"></i>{{ $photoCount }} Photos
                        </span>
                        <span class="d-inline-flex align-items-center">
                            <i class="ti ti-eye me-1"></i>{{ number_format((int) $eventphoto->views) }} Views
                        </span>
                        @if($eventphoto->uploader)
                            <span class="d-inline-flex align-items-center">
                                <i class="ti ti-user me-1"></i>{{ $eventphoto->uploader }}
                            </span>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </section>

    <div class="row g-4">
        <div class="col-lg-8">
            <section class="card gallery-view-card border-0 shadow-sm rounded-4" aria-labelledby="gallery-photo-grid-title">
                <div class="card-header d-flex justify-content-between align-items-center bg-transparent border-bottom">
                    <h2 class="card-title mb-0 fw-bold" id="gallery-photo-grid-title">
                        <i class="ti ti-layout-grid me-2 text-primary" aria-hidden="true"></i>Event Gallery
                    </h2>
                    <span class="badge bg-primary-lt text-primary fw-semibold rounded-pill px-3 py-2">{{ $photoCount }} Photos</span>
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
            <div class="card gallery-view-card gallery-view-info-card border-0 shadow-sm rounded-4">
                <div class="card-header bg-transparent border-bottom">
                    <h2 class="card-title mb-0 fw-bold"><i class="ti ti-info-circle me-2 text-primary" aria-hidden="true"></i>Event Information</h2>
                </div>
                <div class="card-body p-0">
                    <div class="gallery-view-info-row"><div class="text-muted small mb-1">Uploader</div><div class="fw-semibold">{{ $eventphoto->uploader ?: '—' }}</div></div>
                    <div class="gallery-view-info-row"><div class="text-muted small mb-1">Category</div><span class="badge bg-blue-lt text-blue rounded-pill px-3">{{ $eventphoto->category ?: 'Event' }}</span></div>
                    <div class="gallery-view-info-row"><div class="text-muted small mb-1">Event Date</div><div class="fw-semibold">{{ $eventDate }}</div></div>
                    <div class="gallery-view-info-row"><div class="text-muted small mb-1">Total Photos</div><div class="fw-semibold">{{ $photoCount }} Photos</div></div>
                    <div class="gallery-view-info-row"><div class="text-muted small mb-1">Total Views</div><div class="fw-semibold">{{ number_format((int) $eventphoto->views) }}</div></div>
                </div>
                {{-- BACK BUTTON REMOVED FROM EVENT INFORMATION CARD --}}
            </div>
        </aside>
    </div>

    <section class="card gallery-view-card border-0 shadow-sm rounded-4 mt-4 mb-4" aria-labelledby="gallery-discussion-title">
        <div class="card-header bg-transparent border-bottom">
            <h2 class="card-title mb-0 fw-bold" id="gallery-discussion-title">
                <i class="ti ti-messages me-2 text-primary" aria-hidden="true"></i>Comments
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
