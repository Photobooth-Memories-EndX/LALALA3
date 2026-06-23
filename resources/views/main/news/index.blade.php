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

<div class="row row-cards mb-4">

    <div class="col-md-3">
        <div class="card">
            <div class="card-body">
                <div class="row align-items-center">
                    <div class="col">
                        <div class="text-secondary text-uppercase fw-bold small">
                            Total Event
                        </div>
                        <div class="display-6 fw-bold">
                            {{ $totalEvent }}
                        </div>
                    </div>
                    <div class="col-auto">
                        <span class="bg-primary-lt avatar">
                            <i class="ti ti-photo"></i>
                        </span>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="col-md-3">
        <div class="card">
            <div class="card-body">
                <div class="row align-items-center">
                    <div class="col">
                        <div class="text-secondary text-uppercase fw-bold small">
                            Published
                        </div>
                        <div class="display-6 fw-bold text-success">
                            {{ $activeEvent }}
                        </div>
                    </div>
                    <div class="col-auto">
                        <span class="bg-success-lt avatar">
                            <i class="ti ti-circle-check"></i>
                        </span>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="col-md-3">
        <div class="card">
            <div class="card-body">
                <div class="row align-items-center">
                    <div class="col">
                        <div class="text-secondary text-uppercase fw-bold small">
                            Draft
                        </div>
                        <div class="display-6 fw-bold text-warning">
                            {{ $draftEvent }}
                        </div>
                    </div>
                    <div class="col-auto">
                        <span class="bg-warning-lt avatar">
                            <i class="ti ti-edit"></i>
                        </span>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="col-md-3">
        <div class="card">
            <div class="card-body">
                <div class="row align-items-center">
                    <div class="col">
                        <div class="text-secondary text-uppercase fw-bold small">
                            Total Photos
                        </div>
                        <div class="display-6 fw-bold text-primary">
                            {{ $totalPhotos }}
                        </div>
                    </div>
                    <div class="col-auto">
                        <span class="bg-primary-lt avatar">
                            <i class="ti ti-images"></i>
                        </span>
                    </div>
                </div>
            </div>


</div>

</div>
    {{-- Gallery --}}
<div class="card shadow-sm border-0">

    <div class="card-header">

        <h3 class="card-title mb-0">
            List Event Gallery
        </h3>

    </div>

    <div class="card-body border-bottom">

        <div class="row g-3">

            <div class="col-md-2">

                <div class="d-flex align-items-center">

                    <span class="me-2">
                        Show
                    </span>

                    <select
                        id="showFilter"
                        class="form-select">

                        <option value="10">10</option>
                        <option value="25" selected>25</option>
                        <option value="50">50</option>
                        <option value="100">100</option>

                    </select>

                </div>

            </div>

            <div class="col-md-3">

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

            <div class="col-md-5">

                <input
                    type="text"
                    id="searchInput"
                    class="form-control"
                    placeholder="Search event gallery...">

            </div>

            <div class="col-md-2">

                <button
                    id="btnSearch"
                    class="btn btn-primary w-100">

                    Search

                </button>

            </div>

        </div>

    </div>

<div id="galleryTableWrapper">

    <div class="table-responsive">

        <table class="table table-vcenter card-table">

        <thead>
        <tr>

            <th style="width:60px">NO</th>

            <th style="width:90px">IMAGE</th>

            <th>TITLE</th>

            <th style="width:120px" class="text-center">
                STATUS
            </th>

            <th style="width:80px" class="text-center">
                PHOTOS
            </th>

            <th style="width:120px" class="text-center">
                DATE
            </th>

            <th style="width:100px" class="text-center">
                ACTION
            </th>

        </tr>
        </thead>

            <tbody>

                @forelse($eventphoto as $list)

                @php
                    $photos = json_decode($list->photos, true) ?? [];
                    $photoCount = count($photos);
                @endphp

                <tr>

                    <td class="text-center">
                        {{ $loop->iteration + (($eventphoto->currentPage()-1) * $eventphoto->perPage()) }}
                    </td>

                    <td>

                        @if($list->thumbnail)

                        <img src="{{ asset('storage/gallery/photo/'.$list->id.'/'.$list->thumbnail) }}"class="table-image">

                        @endif

                    </td>

                    <td>
                        <div class="news-title">
                            {{ $list->title }}
                        </div>
                    </td>

                    <td class="text-center">
                        <span class="badge bg-{{ $list->active == 'Active' ? 'success' : 'warning' }}">
                            {{ $list->active }}
                        </span>

                    </td>

                    <td class="text-center">
                        {{ $photoCount }}
                    </td>

                    <td class="text-center">

                        @if($list->eventdate)
                            {{ date('d M Y', strtotime($list->eventdate)) }}
                        @else
                            -
                        @endif

                    </td>

<td class="text-center">

    <div class="d-flex justify-content-center gap-2">

        <a
            href="{{ route('eventphoto.edit',$list->slug) }}"
            class="btn btn-primary btn-sm action-btn">

            <i class="ti ti-edit"></i>

        </a>

        <form
            action="{{ route('eventphoto.destroy',$list->slug) }}"
            method="POST">

            @csrf
            @method('DELETE')

            <button
                type="submit"
                class="btn btn-danger btn-sm action-btn">

                <i class="ti ti-trash"></i>

            </button>

        </form>

    </div>

</td>
                </tr>

                @empty

                <tr>

                    <td colspan="7" class="text-center py-5">

                        No Event Gallery Found

                    </td>

                </tr>

                @endforelse

            </tbody>

        </table>

    </div>

    <div class="card-footer">

        {{ $eventphoto->links() }}

    </div>

</div>

</div>

</div>

</div>


</div>

</div>
@endsection

@section('style')

<style>

.table{
    width:100%;
    table-layout:auto;
}

.table th{
    font-size:11px;
    font-weight:700;
    color:#6b7280;
    text-transform:uppercase;
}

.table td,
.table th{
    vertical-align:middle;
}

.table-image{
    width:70px;
    height:50px;
    object-fit:cover;
    border-radius:8px;
    border:1px solid #e5e7eb;
}

.news-title{
    font-weight:600;
    white-space:nowrap;
    overflow:hidden;
    text-overflow:ellipsis;
}

.action-btn{
    width:34px;
    height:34px;
    padding:0;
    display:flex;
    align-items:center;
    justify-content:center;
}

/* ==================================
   STATISTICS CARD
================================== */

.stat-card{
    border:none;
    border-radius:16px;
    transition:.3s;
}

.stat-card .card-body{
    padding:20px;
}

.stat-card:hover{
    transform:translateY(-5px);
    box-shadow:0 12px 25px rgba(0,0,0,.08);
}

/* ==================================
   TABLE STYLE
================================== */

.table td{
    vertical-align:middle;
}

.table-responsive{
    min-height:500px;
}

.card{
    border-radius:16px;
}

.page-body{
    padding-bottom:40px;
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
    min-width:40px;
    height:40px;
    display:flex;
    align-items:center;
    justify-content:center;
    border-radius:8px !important;
    color:#64748b;
    transition:.2s;
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
    gap:6px;
}

.page-item{
    margin:0;
}

.pagination .page-link{
    width:40px !important;
    height:40px !important;
    min-width:40px !important;

    border-radius:8px !important;

    display:flex !important;
    align-items:center !important;
    justify-content:center !important;

    border:none !important;
}

.page-link:hover{
    background:#e9f2ff;
    color:#206bc4;
}

.pagination .page-item.active .page-link{
    background:#206bc4 !important;
    color:#fff !important;
    border-radius:8px !important;
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


[data-bs-theme="dark"] .stat-card{
    background:#1f2937;
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
    width:38px;
    height:38px;
    padding:0;
    display:flex;
    align-items:center;
    justify-content:center;
    border-radius:8px;
}

</style>

@section('script')

<script>

function loadGallery(page = 1)
{
    $.get(
        "{{ route('eventphoto.index') }}",
        {
            search : $('#searchInput').val(),
            status : $('#statusFilter').val(),
            show   : $('#showFilter').val(),
            page   : page
        },
        function(response){

            let html =
                $(response)
                .find('#galleryTableWrapper')
                .html();

            $('#galleryTableWrapper')
                .html(html);

        }
    );
}

$('#btnSearch').on(
    'click',
    function(){

        loadGallery();

    }
);

$('#searchInput').on(
    'keyup',
    function(){

        clearTimeout(
            window.galleryTimer
        );

        window.galleryTimer =
            setTimeout(
                loadGallery,
                500
            );

    }
);

$('#statusFilter').on(
    'change',
    function(){

        loadGallery();

    }
);

$('#showFilter').on(
    'change',
    function(){

        loadGallery();

    }
);

$(document).on(
    'click',
    '.pagination a',
    function(e){

        e.preventDefault();

        let page =
            $(this)
            .attr('href')
            .split('page=')[1];

        loadGallery(page);

    }
);

</script>

@endsection
