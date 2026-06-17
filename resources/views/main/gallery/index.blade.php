@extends('layouts.sidebar-master')

@section('content')

<div class="page-header d-print-none">
    <div class="container-xl">
        <div class="row g-2 align-items-center">
            <div class="col">

                <div class="d-flex align-items-center gap-3">

                    <div class="avatar avatar-lg bg-primary-lt">
                        <i class="ti ti-photo fs-1"></i>
                    </div>

                    <div>
                        <h2 class="page-title mb-1">
                            Event Photo Gallery
                        </h2>

                        <div class="text-secondary">
                            Manage all gallery events and photo collections
                        </div>
                    </div>

                </div>

            </div>

        <div class="col-auto ms-auto d-print-none">
            <a href="{{ route('eventphoto.create') }}" class="btn btn-primary">
                <svg xmlns="http://www.w3.org/2000/svg"
                    class="icon"
                    width="24"
                    height="24"
                    viewBox="0 0 24 24"
                    stroke-width="2"
                    stroke="currentColor"
                    fill="none">

                    <path d="M12 5l0 14"></path>
                    <path d="M5 12l14 0"></path>
                </svg>

                Add Event
            </a>
        </div>
    </div>
</div>

</div>

<div class="page-body">
    <div class="container-xl">

    <x-alert type="success" />
    <x-alert type="warning" />


    {{-- Statistics --}}

<div class="row row-deck row-cards mb-4">

<div class="col-sm-6 col-lg-3">
    <div class="card stat-card shadow-sm h-100">
        <div class="card-body">

            <div class="d-flex justify-content-between align-items-center">

                <div>
                    <div class="subheader">
                        Total Event
                    </div>

                    <div class="h1 mb-0">
                        {{ $totalEvent }}
                    </div>
                </div>

                <div class="avatar avatar-lg bg-primary-lt">
                    <i class="ti ti-photo fs-3"></i>
                </div>

            </div>

        </div>
    </div>
</div>

<div class="col-sm-6 col-lg-3">
    <div class="card stat-card shadow-sm h-100">
        <div class="card-body">

            <div class="d-flex justify-content-between align-items-center">

                <div>
                    <div class="subheader">
                        Published
                    </div>

                    <div class="h1 text-success mb-0">
                        {{ $activeEvent }}
                    </div>
                </div>

                <div class="avatar avatar-lg bg-success-lt">
                    <i class="ti ti-circle-check fs-3"></i>
                </div>

            </div>

        </div>
    </div>
</div>

<div class="col-sm-6 col-lg-3">
    <div class="card stat-card shadow-sm h-100">
        <div class="card-body">

            <div class="d-flex justify-content-between align-items-center">

                <div>
                    <div class="subheader">
                        Draft
                    </div>

                    <div class="h1 text-warning mb-0">
                        {{ $draftEvent }}
                    </div>
                </div>

                <div class="avatar avatar-lg bg-warning-lt">
                    <i class="ti ti-edit fs-3"></i>
                </div>

            </div>

        </div>
    </div>
</div>

<div class="col-sm-6 col-lg-3">
    <div class="card stat-card shadow-sm h-100">
        <div class="card-body">

            <div class="d-flex justify-content-between align-items-center">

                <div>
                    <div class="subheader">
                        Total Photos
                    </div>

                    <div class="h1 text-primary mb-0">
                        {{ $totalPhotos }}
                    </div>
                </div>

                <div class="avatar avatar-lg bg-primary-lt">
                    <i class="ti ti-images fs-3"></i>
                </div>

            </div>

        </div>
    </div>
</div>

</div>


    {{-- Search --}}
    <div class="row mb-4">

    <div class="col-md-8">

        <div class="input-icon">



            <input
                type="text"
                id="gallerySearch"
                class="form-control"
                placeholder="Search event gallery...">

        </div>

    </div>

    <div class="col-md-4">

        <select
            id="statusFilter"
            class="form-select">

            <option value="">
                All Status
            </option>

            <option value="Active">
                Active
            </option>

            <option value="Inactive">
                Inactive
            </option>

        </select>

    </div>

</div>
    {{-- Gallery --}}
<div class="row row-cards g-4" id="galleryContainer">

    @forelse($eventphoto as $list)

        @php
            $arrayphoto = $list->photos
                ? explode(',', $list->photos)
                : [];

            $totalPhoto = count($arrayphoto);

            $thumb = $list->thumbnail;

            $activeColor = $list->active == 'Active'
                ? 'success'
                : 'warning';
        @endphp

        <div class="col-md-6 col-xl-4 gallery-item" data-title="{{ strtolower($list->title) }}" data-status="{{ strtolower($list->active) }}">

            <div class="card gallery-card h-100">

                <div class="gallery-image-wrapper">

                    @if($thumb)

                        <img
                            src="{{ asset('storage/gallery/photo/'.$list->id.'/'.$thumb) }}"
                            class="gallery-image"
                            alt="{{ $list->title }}">

                    @else

                        <div class="gallery-placeholder">

                            <i class="ti ti-photo fs-1"></i>

                        </div>

                    @endif

                    <span class="badge bg-dark photo-count">
                        {{ $totalPhoto }} Photos
                    </span>

                </div>

                <div class="card-body">

                    <div class="d-flex justify-content-between align-items-start mb-2">

                        <h3 class="card-title mb-0">
                            {{ Str::limit($list->title, 55) }}
                        </h3>

                        <span class="badge bg-{{ $activeColor }}">
                            {{ $list->active }}
                        </span>

                    </div>

                    <div class="text-secondary">

                        <i class="ti ti-calendar-event"></i>

                        {{ date('d M Y', strtotime($list->eventdate)) }}

                    </div>

                </div>

                <div class="card-footer bg-transparent border-0">

                    <div class="d-flex gap-2">

                        <a
                            href="{{ route('eventphoto.edit', $list->slug) }}"
                            class="btn btn-primary w-100">

                            Edit

                        </a>

                        <form
                            action="{{ route('eventphoto.destroy', $list->slug) }}"
                            method="POST"
                            class="w-100">

                            @csrf
                            @method('DELETE')

                            <button
                                type="submit"
                                class="btn btn-danger w-100"
                                onclick="return confirm('Delete this event?')">

                                Delete

                            </button>

                        </form>

                    </div>

                </div>

            </div>

        </div>

    @empty

        <div class="col-12">

            <div class="empty">

                <div class="empty-img">

                    <i class="ti ti-photo-off fs-1"></i>

                </div>

                <p class="empty-title">

                    No event photo found

                </p>

            </div>

        </div>

    @endforelse

</div>

{{-- PAGINATION --}}
<div class="mt-5 mb-5 d-flex justify-content-center">

    {{ $eventphoto->links() }}

</div>
</div>
@endsection
@section('style')

<style>

/* ==================================
   STATISTICS CARD
================================== */

.stat-card{
    border:none;
    border-radius:18px;
    overflow:hidden;
    transition:all .3s ease;
    background:#fff;
}

.stat-card:hover{
    transform:translateY(-5px);
    box-shadow:0 12px 25px rgba(0,0,0,.08);
}

/* ==================================
   GALLERY GRID
================================== */

#galleryContainer{
    padding-bottom:20px;
}

.gallery-card{
    border:none;
    border-radius:18px;
    overflow:hidden;
    transition:.3s;
}

.gallery-card:hover{
    transform:translateY(-5px);
    box-shadow:0 15px 35px rgba(0,0,0,.12);
}

.gallery-image{
    width:100%;
    height:220px;
    object-fit:cover;
}

.gallery-placeholder{
    height:220px;
    background:#f1f5f9;
    display:flex;
    align-items:center;
    justify-content:center;
}

.gallery-image-wrapper{
    position:relative;
}

.photo-count{
    position:absolute;
    top:12px;
    right:12px;
}

.card-title{
    font-size:1rem;
    font-weight:700;
    line-height:1.5;
    min-height:52px;
}

.pagination{
    justify-content:center;
    gap:8px;
}

.page-link{
    border:none;
    width:42px;
    height:42px;
    display:flex;
    align-items:center;
    justify-content:center;
    border-radius:50% !important;
}

.page-item.active .page-link{
    background:#206bc4;
}
/* ==================================
   BADGES
================================== */

.photo-count{
    position:absolute;
    top:12px;
    right:12px;
    font-size:12px;
    border-radius:999px;
    padding:6px 10px;
}

.status-badge{
    border-radius:999px;
}

/* ==================================
   CARD CONTENT
================================== */

.card-title{
    font-size:1.05rem;
    font-weight:700;
    line-height:1.5;
    min-height:60px;
}

.gallery-card .text-secondary{
    font-size:.9rem;
}

/* ==================================
   BUTTONS
================================== */

.gallery-card .btn{
    border-radius:10px;
    font-weight:600;
}

.gallery-card .btn-primary{
    background:#206bc4;
}

.gallery-card .btn-danger{
    background:#dc3545;
}

/* ==================================
   SEARCH
================================== */

#gallerySearch{
    border-radius:14px;
    border:1px solid #e5e7eb;
    padding:12px 16px;
}

#statusFilter{
    border-radius:14px;
}

/* ==================================
   PAGINATION
================================== */

.pagination{
    justify-content:center;
    gap:10px;
}

.page-item{
    margin:0;
}

.page-link{
    border:none;
    width:42px;
    height:42px;
    display:flex;
    align-items:center;
    justify-content:center;
    border-radius:50% !important;
    color:#64748b;
    transition:.2s;
}

.page-link:hover{
    background:#e9f2ff;
}

.page-item.active .page-link{
    background:#206bc4;
    color:#fff;
}

/* ==================================
   GLOBAL CARD
================================== */

.card{
    border-radius:20px;
}

/* ==================================
   DARK MODE
================================== */

[data-bs-theme="dark"] .gallery-card{
    background:#1f2937;
}

[data-bs-theme="dark"] .stat-card{
    background:#1f2937;
}

[data-bs-theme="dark"] .gallery-placeholder{
    background:#111827;
    color:#9ca3af;
}

[data-bs-theme="dark"] #gallerySearch{
    background:#111827;
    border-color:#374151;
    color:#fff;
}

[data-bs-theme="dark"] #statusFilter{
    background:#111827;
    border-color:#374151;
    color:#fff;
}

[data-bs-theme="dark"] .page-link{
    background:#1f2937;
    color:#fff;
}

[data-bs-theme="dark"] .page-link:hover{
    background:#374151;
}

[data-bs-theme="dark"] .card-title{
    color:#fff;
}

[data-bs-theme="dark"] .text-secondary{
    color:#9ca3af !important;
}

.action-btn{
    height:42px;
    display:flex;
    align-items:center;
    justify-content:center;
    font-weight:600;
}

</style>


@section('script')

<script>

document.addEventListener('DOMContentLoaded', function () {

    const searchInput =
        document.getElementById('gallerySearch');

    const statusFilter =
        document.getElementById('statusFilter');

    const cards =
        document.querySelectorAll('.gallery-item');

    function filterGallery() {

        const keyword =
            searchInput.value.toLowerCase();

        const status =
            statusFilter.value.toLowerCase();

        cards.forEach(card => {

            const title =
                card.dataset.title;

            const cardStatus =
                card.dataset.status;

            const matchKeyword =
                title.includes(keyword);

            const matchStatus =
                status === '' ||
                cardStatus === status;

            if (
                matchKeyword &&
                matchStatus
            ) {
                card.style.display = '';
            } else {
                card.style.display = 'none';
            }

        });

    }

    searchInput.addEventListener(
        'keyup',
        filterGallery
    );

    statusFilter.addEventListener(
        'change',
        filterGallery
    );

});

</script>


@endsection
