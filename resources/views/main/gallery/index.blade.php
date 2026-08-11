@extends('layouts.sidebar-master')

@section('style')
<style>
.admin-gallery-img {
    width: 70px;
    height: 50px;
    object-fit: cover;
    border-radius: 8px;
    border: 1px solid var(--tblr-border-color);
    display: block;
}
.admin-gallery-placeholder {
    width: 70px;
    height: 50px;
    border-radius: 8px;
    background: var(--tblr-bg-surface-secondary);
    display: flex;
    align-items: center;
    justify-content: center;
    color: var(--tblr-secondary);
    font-size: 1.25rem;
}
.admin-gallery-stat {
    border-radius: 16px;
    transition: transform .2s, box-shadow .2s;
}
.admin-gallery-stat:hover {
    transform: translateY(-3px);
    box-shadow: 0 8px 24px rgba(0,0,0,.1);
}
.admin-gallery-action {
    width: 34px;
    height: 34px;
    padding: 0;
    display: inline-flex;
    align-items: center;
    justify-content: center;
}
.admin-gallery-row-title {
    font-weight: 600;
    max-width: 300px;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}
.pagination .page-link {
    border-radius: 6px !important;
    min-width: 36px;
    height: 36px;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    border: 1px solid var(--tblr-border-color);
}
</style>
@endsection

@section('content')

<div class="page-header d-print-none">
    <div class="container-xl">
        <div class="row align-items-center">
            <div class="col">
                <div class="d-flex align-items-center gap-3">
                    <div class="avatar avatar-lg bg-primary-lt">
                        <i class="ti ti-calendar-event fs-2"></i>
                    </div>
                    <div>
                        <h2 class="page-title mb-1">Event Photo Gallery</h2>
                        <div class="text-secondary">Manage all gallery events and photo collections</div>
                    </div>
                </div>
            </div>
            <div class="col-auto">
                <a href="{{ route('eventphoto.create') }}" class="btn btn-primary">
                    <i class="ti ti-plus me-1"></i>Add Event
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
            <div class="col-sm-6 col-xl-3">
                <div class="card admin-gallery-stat">
                    <div class="card-body">
                        <div class="d-flex align-items-center">
                            <div class="flex-fill">
                                <div class="text-secondary text-uppercase fw-bold small mb-1">Total Event</div>
                                <div class="display-6 fw-bold">{{ $totalEvent }}</div>
                            </div>
                            <span class="avatar bg-primary-lt ms-3"><i class="ti ti-calendar-event"></i></span>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-sm-6 col-xl-3">
                <div class="card admin-gallery-stat">
                    <div class="card-body">
                        <div class="d-flex align-items-center">
                            <div class="flex-fill">
                                <div class="text-secondary text-uppercase fw-bold small mb-1">Published</div>
                                <div class="display-6 fw-bold text-success">{{ $activeEvent }}</div>
                            </div>
                            <span class="avatar bg-success-lt ms-3"><i class="ti ti-rosette-discount-check"></i></span>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-sm-6 col-xl-3">
                <div class="card admin-gallery-stat">
                    <div class="card-body">
                        <div class="d-flex align-items-center">
                            <div class="flex-fill">
                                <div class="text-secondary text-uppercase fw-bold small mb-1">Draft</div>
                                <div class="display-6 fw-bold text-warning">{{ $draftEvent }}</div>
                            </div>
                            <span class="avatar bg-warning-lt ms-3"><i class="ti ti-file-pencil"></i></span>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-sm-6 col-xl-3">
                <div class="card admin-gallery-stat">
                    <div class="card-body">
                        <div class="d-flex align-items-center">
                            <div class="flex-fill">
                                <div class="text-secondary text-uppercase fw-bold small mb-1">Total Photos</div>
                                <div class="display-6 fw-bold text-info">{{ $totalPhotos }}</div>
                            </div>
                            <span class="avatar bg-info-lt ms-3"><i class="ti ti-photo-scan"></i></span>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        {{-- Table Card --}}
        <div class="card shadow-sm">
            <div class="card-header">
                <h3 class="card-title">List Event Gallery</h3>
            </div>

            <div class="card-body border-bottom py-3">
                <div class="row g-2 align-items-center">
                    <div class="col-md-2">
                        <div class="d-flex align-items-center gap-2">
                            <span class="text-nowrap text-secondary small">Show</span>
                            <select id="showFilter" class="form-select form-select-sm">
                                <option value="10">10</option>
                                <option value="25" selected>25</option>
                                <option value="50">50</option>
                                <option value="100">100</option>
                            </select>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <select id="statusFilter" class="form-select form-select-sm">
                            <option value="">All Status</option>
                            <option value="Active">Active</option>
                            <option value="Inactive">Inactive</option>
                        </select>
                    </div>
                    <div class="col-md-5">
                        <input type="text" id="searchInput" class="form-control form-control-sm"
                               placeholder="Search event gallery...">
                    </div>
                    <div class="col-md-2">
                        <button id="btnSearch" class="btn btn-primary btn-sm w-100">
                            <i class="ti ti-search me-1"></i>Search
                        </button>
                    </div>
                </div>
            </div>

            <div id="galleryTableWrapper">
                <div class="table-responsive">
                    <table class="table table-vcenter card-table">
                        <thead>
                            <tr>
                                <th style="width:50px">NO</th>
                                <th style="width:90px">IMAGE</th>
                                <th>TITLE</th>
                                <th style="width:110px" class="text-center">STATUS</th>
                                <th style="width:80px" class="text-center">PHOTOS</th>
                                <th style="width:120px" class="text-center">DATE</th>
                                <th style="width:100px" class="text-center">ACTION</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($eventphoto as $list)
                            @php
                                $photos     = json_decode($list->photos, true) ?? [];
                                $photoCount = count($photos);
                            @endphp
                            <tr>
                                <td class="text-center text-secondary small">
                                    {{ $loop->iteration + (($eventphoto->currentPage()-1) * $eventphoto->perPage()) }}
                                </td>
                                <td>
                                    @if($list->thumbnail)
                                        <img src="{{ asset('storage/gallery/photo/'.$list->id.'/'.$list->thumbnail) }}"
                                             class="admin-gallery-img"
                                             alt="{{ $list->title }}"
                                             onerror="this.outerHTML='<div class=&quot;admin-gallery-placeholder&quot;><i class=&quot;ti ti-photo-off&quot;></i></div>'">
                                    @else
                                        <div class="admin-gallery-placeholder">
                                            <i class="ti ti-photo-off"></i>
                                        </div>
                                    @endif
                                </td>
                                <td>
                                    <div class="admin-gallery-row-title" title="{{ $list->title }}">
                                        {{ $list->title }}
                                    </div>
                                </td>
                                <td class="text-center">
                                    <span class="badge bg-{{ $list->active == 'Active' ? 'success' : 'warning' }}-lt text-{{ $list->active == 'Active' ? 'success' : 'warning' }}">
                                        {{ $list->active }}
                                    </span>
                                </td>
                                <td class="text-center">
                                    <span class="badge bg-azure-lt text-azure">{{ $photoCount }}</span>
                                </td>
                                <td class="text-center text-secondary small">
                                    @if($list->eventdate)
                                        {{ date('d M Y', strtotime($list->eventdate)) }}
                                    @else
                                        &mdash;
                                    @endif
                                </td>
                                <td class="text-center">
                                    <div class="d-flex justify-content-center gap-1">
                                        <a href="{{ route('eventphoto.edit', $list->slug) }}"
                                           class="btn btn-sm btn-primary admin-gallery-action"
                                           title="Edit">
                                            <i class="ti ti-edit"></i>
                                        </a>
                                        <form action="{{ route('eventphoto.destroy', $list->slug) }}" method="POST" class="m-0">
                                            @csrf
                                            @method('DELETE')
                                            <button type="button"
                                                    onclick="if(confirm('Delete this event? This cannot be undone.')){this.closest('form').submit()}"
                                                    class="btn btn-sm btn-danger admin-gallery-action"
                                                    title="Delete">
                                                <i class="ti ti-trash"></i>
                                            </button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                            @empty
                            <tr>
                                <td colspan="7">
                                    <div class="empty py-5">
                                        <div class="empty-icon">
                                            <i class="ti ti-photo-off" style="font-size:3rem;opacity:.4;"></i>
                                        </div>
                                        <p class="empty-title">No gallery events found</p>
                                        <p class="empty-subtitle text-secondary">Try adjusting your search or add a new event.</p>
                                        <div class="empty-action">
                                            <a href="{{ route('eventphoto.create') }}" class="btn btn-primary btn-sm">
                                                <i class="ti ti-plus me-1"></i>Add Event
                                            </a>
                                        </div>
                                    </div>
                                </td>
                            </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
                <div class="card-footer d-flex align-items-center justify-content-center py-3">
                    {{ $eventphoto->links() }}
                </div>
            </div>

        </div>

    </div>
</div>

@endsection

@section('script')
<script>
function loadGallery(page) {
    page = page || 1;
    $.get(
        "{{ route('eventphoto.index') }}",
        {
            search: $('#searchInput').val(),
            status: $('#statusFilter').val(),
            show:   $('#showFilter').val(),
            page:   page
        },
        function(response) {
            var html = $(response).find('#galleryTableWrapper').html();
            $('#galleryTableWrapper').html(html);
        }
    );
}

$('#btnSearch').on('click', function() {
    loadGallery();
});

$('#searchInput').on('keyup', function() {
    clearTimeout(window.galleryTimer);
    window.galleryTimer = setTimeout(loadGallery, 450);
});

$('#statusFilter, #showFilter').on('change', function() {
    loadGallery();
});

$(document).on('click', '.pagination a', function(e) {
    e.preventDefault();
    var href  = $(this).attr('href') || '';
    var match = href.match(/page=(\d+)/);
    loadGallery(match ? match[1] : 1);
});
</script>
@endsection
