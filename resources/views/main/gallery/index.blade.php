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
.btn-quick-reset {
    border-color: #0c5b9e !important;
    color: #0c5b9e !important;
    background-color: transparent !important;
    transition: background-color .15s ease-in-out, border-color .15s ease-in-out, color .15s ease-in-out;
}
.btn-quick-reset:hover {
    background-color: #e6f0fa !important;
    border-color: #0c5b9e !important;
    color: #0c5b9e !important;
}
.btn-quick-reset:hover i {
    color: #0c5b9e !important;
}
[data-bs-theme="dark"] .btn-quick-reset:hover {
    background-color: rgba(12, 91, 158, 0.25) !important;
    border-color: #3b82f6 !important;
    color: #60a5fa !important;
}
[data-bs-theme="dark"] .btn-quick-reset:hover i {
    color: #60a5fa !important;
}
[data-bs-theme="dark"] #moreGalleryFiltersModal .text-dark {
    color: var(--tblr-body-color) !important;
}
.btn-back {
    color: #0c5b9e !important;
    transition: transform 0.2s ease, opacity 0.2s ease;
}
.btn-back:hover {
    opacity: 0.85;
    transform: translateX(-3px);
}
.manage-eventphoto-search-container {
    position: relative;
    width: 100%;
}
#manage-eventphoto-search-results {
    position: absolute;
    top: 100%;
    left: 0;
    z-index: 1085;
    max-height: 280px;
    border-radius: 12px;
    box-shadow: 0 10px 25px rgba(15, 23, 42, .12);
    padding: .35rem 0;
    border: 1px solid rgba(0, 0, 0, .08);
    background: #ffffff;
}
#manage-eventphoto-search-results .dropdown-item {
    padding: .6rem .85rem;
    transition: background-color .15s ease;
    text-decoration: none;
}
#manage-eventphoto-search-results .dropdown-item:hover,
#manage-eventphoto-search-results .dropdown-item:focus {
    background-color: rgba(15, 94, 156, .08);
}
#manage-eventphoto-search-results .dropdown-item:not(:last-child) {
    border-bottom: 1px solid rgba(0, 0, 0, .05);
}
[data-bs-theme="dark"] #manage-eventphoto-search-results,
body.dark-mode #manage-eventphoto-search-results,
body.theme-dark #manage-eventphoto-search-results {
    background: #1f2937;
    border-color: rgba(255, 255, 255, 0.12);
    box-shadow: 0 10px 25px rgba(0, 0, 0, 0.4);
}
[data-bs-theme="dark"] #manage-eventphoto-search-results .dropdown-item,
body.dark-mode #manage-eventphoto-search-results .dropdown-item,
body.theme-dark #manage-eventphoto-search-results .dropdown-item {
    color: #f8fafc;
}
[data-bs-theme="dark"] #manage-eventphoto-search-results .dropdown-item:not(:last-child),
body.dark-mode #manage-eventphoto-search-results .dropdown-item:not(:last-child),
body.theme-dark #manage-eventphoto-search-results .dropdown-item:not(:last-child) {
    border-bottom-color: rgba(255, 255, 255, 0.08);
}
[data-bs-theme="dark"] #manage-eventphoto-search-results .dropdown-item:hover,
[data-bs-theme="dark"] #manage-eventphoto-search-results .dropdown-item:focus,
body.dark-mode #manage-eventphoto-search-results .dropdown-item:hover,
body.dark-mode #manage-eventphoto-search-results .dropdown-item:focus,
body.theme-dark #manage-eventphoto-search-results .dropdown-item:hover,
body.theme-dark #manage-eventphoto-search-results .dropdown-item:focus {
    background: rgba(106, 174, 214, 0.16);
}
[data-bs-theme="dark"] #manage-eventphoto-search-results .dropdown-item .text-dark,
body.dark-mode #manage-eventphoto-search-results .dropdown-item .text-dark,
body.theme-dark #manage-eventphoto-search-results .dropdown-item .text-dark {
    color: #f8fafc !important;
}
</style>
@endsection

@section('content')

<div class="page-header d-print-none">
    <div class="container-xl">
        <!-- TOMBOL BACK KE DASHBOARD MANAGE -->
        <div class="mb-3">
            <a href="{{ route('manage.index') }}" class="btn-back d-inline-flex align-items-center text-primary text-decoration-none fw-semibold small">
                <i class="ti ti-arrow-left me-1 fs-5"></i> Back
            </a>
        </div>

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

        {{-- Quick Filter Component --}}
        <div class="card border-0 shadow-sm rounded-4 mb-4">
            <div class="card-body p-3">
                <!-- Header Text Quick Filter -->
                <div class="text-secondary small fw-semibold mb-2" style="color: #6c757d !important; font-size: 0.85rem;">
                    Quick Filter
                </div>

                <form action="{{ route('manage.eventphoto.index') }}" method="GET" id="manageGalleryFilterForm">
                    <div class="row g-2 align-items-center">
                        <!-- Filter Year & Month -->
                        <div class="col-6 col-md-auto">
                            <select name="year" class="form-select rounded-3" onchange="document.getElementById('manageGalleryFilterForm').submit()">
                                <option value="">Year: All</option>
                                @foreach(range(date('Y'), 2020) as $y)
                                    <option value="{{ $y }}" {{ request('year') == $y ? 'selected' : '' }}>Year: {{ $y }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-6 col-md-auto">
                            <select name="month" class="form-select rounded-3" onchange="document.getElementById('manageGalleryFilterForm').submit()">
                                <option value="">Month: All</option>
                                @foreach(range(1, 12) as $m)
                                    <option value="{{ $m }}" {{ request('month') == $m ? 'selected' : '' }}>
                                        {{ date('F', mktime(0, 0, 0, $m, 1)) }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-6 col-md-auto">
                            <select name="views" class="form-select rounded-3" onchange="document.getElementById('manageGalleryFilterForm').submit()">
                                <option value="">Views: All</option>
                                <option value="most" {{ (request('views') == 'most' || request('views') == 'most_viewed') ? 'selected' : '' }}>Most Viewed</option>
                                <option value="least" {{ (request('views') == 'least' || request('views') == 'least_viewed') ? 'selected' : '' }}>Least Viewed</option>
                            </select>
                        </div>

                        <!-- FILTER STATUS: Approved, Pending, Reject -->
                        <div class="col-6 col-md-auto">
                            <select name="status" class="form-select rounded-3" onchange="document.getElementById('manageGalleryFilterForm').submit()">
                                <option value="">Status: All</option>
                                <option value="approved" {{ request('status') == 'approved' ? 'selected' : '' }}>Approved</option>
                                <option value="pending" {{ request('status') == 'pending' ? 'selected' : '' }}>Pending</option>
                                <option value="reject" {{ (request('status') == 'reject' || request('status') == 'rejected') ? 'selected' : '' }}>Reject</option>
                            </select>
                        </div>

                        <!-- Search Bar Input -->
                        <div class="col-12 col-md">
                            <div class="position-relative manage-eventphoto-search-container flex-grow-1">
                                <div class="input-group">
                                    <span class="input-group-text bg-white border-end-0 rounded-start-3"><i class="ti ti-search text-muted"></i></span>
                                    <input type="text" name="search" id="manage-eventphoto-search-input" class="form-control border-start-0 rounded-end-3" placeholder="Search by title..." value="{{ request('search') }}" autocomplete="off">
                                </div>
                                <div id="manage-eventphoto-search-results" class="dropdown-menu shadow-sm w-100 position-absolute mt-1" style="display: none; max-height: 280px; overflow-y: auto; z-index: 1050;"></div>
                            </div>
                        </div>

                        <!-- Action Buttons -->
                        <div class="col-auto d-flex gap-1">
                            <button type="submit" class="btn btn-primary rounded-3 px-3" style="background-color: #0c5b9e; border-color: #0c5b9e;" title="Search">
                                <i class="ti ti-search"></i>
                            </button>
                            <button type="button" class="btn btn-outline-primary rounded-3 px-3" style="border-color: #0c5b9e; color: #0c5b9e;" data-bs-toggle="modal" data-bs-target="#moreGalleryFiltersModal" title="More Filters">
                                <i class="ti ti-adjustments-horizontal"></i>
                            </button>
                            <!-- Tombol Reset Biru dengan Efek Hover Aman -->
                            <a href="{{ route('manage.eventphoto.index') }}" class="btn btn-outline-primary rounded-3 px-3 d-inline-flex align-items-center justify-content-center btn-quick-reset" style="border-color: #0c5b9e; color: #0c5b9e; background-color: transparent;" title="Reset Filter">
                                <i class="ti ti-refresh" style="font-size: 1.1rem; color: #0c5b9e !important;"></i>
                            </a>
                        </div>
                    </div>
                </form>
            </div>
        </div>

        {{-- Table Card --}}
        <div class="card shadow-sm">
            <div class="card-header">
                <h3 class="card-title">List Event Gallery</h3>
            </div>

            <div id="galleryTableWrapper">
                <div class="table-responsive">
                    <table class="table table-vcenter card-table">
                        <thead>
                            <tr>
                                <th style="width:50px">NO</th>
                                <th style="width:90px">IMAGE</th>
                                <th>TITLE</th>
                                <th style="width:130px">CATEGORY</th>
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
                                <td>
                                    @if($list->category)
                                        <span class="badge bg-blue-lt text-blue">{{ $list->category }}</span>
                                    @else
                                        <span class="text-muted small">&mdash;</span>
                                    @endif
                                </td>
                                <td class="text-center">
                                    @php
                                        $statusVal = strtolower($list->status ?? 'approved');
                                        $statusBadge = match($statusVal) {
                                            'approved' => 'bg-success-lt text-success',
                                            'pending'  => 'bg-warning-lt text-warning',
                                            'reject', 'rejected' => 'bg-danger-lt text-danger',
                                            default    => 'bg-secondary-lt text-secondary'
                                        };
                                    @endphp
                                    <span class="badge {{ $statusBadge }}">
                                        {{ ucfirst($statusVal) }}
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
                                <td colspan="8">
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
                    {{ $eventphoto->withQueryString()->links() }}
                </div>
            </div>

        </div>

    </div>
</div>

{{-- More Filters Modal --}}
<div class="modal fade" id="moreGalleryFiltersModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content rounded-4 border-0 shadow">
            <div class="modal-header border-0 pb-0">
                <h5 class="modal-title fw-bold text-dark d-flex align-items-center gap-2">
                    <i class="ti ti-adjustments-horizontal text-primary"></i> More Filters
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form action="{{ route('manage.eventphoto.index') }}" method="GET">
                <div class="modal-body py-3">
                    <input type="hidden" name="search" value="{{ request('search') }}">
                    <input type="hidden" name="status" value="{{ request('status') }}">
                    <input type="hidden" name="year" value="{{ request('year') }}">
                    <input type="hidden" name="month" value="{{ request('month') }}">
                    <input type="hidden" name="views" value="{{ request('views') }}">

                    <div class="mb-3">
                        <label class="form-label fw-bold text-dark small">Uploader / Organizer</label>
                        <select name="uploader_id" class="form-select rounded-3">
                            <option value="">All Uploaders / Organizers</option>
                            @foreach($uploaders ?? [] as $uploader)
                                <option value="{{ $uploader->id }}" {{ (request('uploader_id') == $uploader->id || request('uploader') == $uploader->id) ? 'selected' : '' }}>{{ $uploader->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="row g-2 mb-3">
                        <div class="col-6">
                            <label class="form-label fw-bold text-dark small">Type / Category</label>
                            <select name="category_id" class="form-select rounded-3">
                                <option value="">All Types</option>
                                @foreach($categories ?? [] as $cat)
                                    @php
                                        $catVal = is_object($cat) ? ($cat->id ?? $cat->name) : $cat;
                                        $catName = is_object($cat) ? $cat->name : $cat;
                                    @endphp
                                    <option value="{{ $catVal }}" {{ (request('category_id') == $catVal || request('category') == $catVal) ? 'selected' : '' }}>{{ $catName }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-6">
                            <label class="form-label fw-bold text-dark small">Sort by</label>
                            <select name="sort" class="form-select rounded-3">
                                <option value="newest" {{ request('sort', 'newest') == 'newest' ? 'selected' : '' }}>Newest</option>
                                <option value="oldest" {{ request('sort') == 'oldest' ? 'selected' : '' }}>Oldest</option>
                            </select>
                        </div>
                    </div>
                    <div class="mb-2">
                        <label class="form-label fw-bold text-dark small"><i class="ti ti-calendar me-1"></i> Event Date</label>
                        <div class="row g-2">
                            <div class="col-6">
                                <input type="text" name="from_date" onfocus="(this.type='date')" onblur="if(!this.value)this.type='text'" class="form-control rounded-3" placeholder="dd/mm/yyyy" value="{{ request('from_date', request('date_from')) }}">
                            </div>
                            <div class="col-6">
                                <input type="text" name="to_date" onfocus="(this.type='date')" onblur="if(!this.value)this.type='text'" class="form-control rounded-3" placeholder="dd/mm/yyyy" value="{{ request('to_date', request('date_to')) }}">
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer border-0 pt-0 d-flex gap-2">
                    <a href="{{ route('manage.eventphoto.index') }}" class="btn btn-outline-secondary rounded-pill flex-fill"><i class="ti ti-refresh me-1"></i> Reset</a>
                    <button type="submit" class="btn btn-primary rounded-pill flex-fill" style="background-color: #0c5b9e; border-color: #0c5b9e;"><i class="ti ti-check me-1"></i> Apply Filter</button>
                </div>
            </form>
        </div>
    </div>
</div>

@endsection

@section('script')
<script>
document.addEventListener('DOMContentLoaded', function () {
    function initManageEventPhotoAutocomplete() {
        var searchInput = document.getElementById('manage-eventphoto-search-input') || document.querySelector('input[name="search"]');
        var searchResults = document.getElementById('manage-eventphoto-search-results');
        var form = document.getElementById('manageGalleryFilterForm') || searchInput?.closest('form');
        var debounceTimer = null;

        if (!searchInput || !searchResults) return;

        function escapeHtml(text) {
            if (!text) return '';
            var div = document.createElement('div');
            div.textContent = text;
            return div.innerHTML;
        }

        searchInput.addEventListener('input', function () {
            var query = this.value.trim();
            clearTimeout(debounceTimer);

            if (query.length < 1) {
                searchResults.style.display = 'none';
                searchResults.innerHTML = '';
                return;
            }

            debounceTimer = setTimeout(function () {
                fetch('/manage/eventphoto/search-autocomplete?q=' + encodeURIComponent(query), {
                    headers: { 'X-Requested-With': 'XMLHttpRequest' }
                })
                .then(function (res) {
                    if (!res.ok) throw new Error('HTTP ' + res.status);
                    return res.json();
                })
                .then(function (data) {
                    searchResults.innerHTML = '';
                    if (data && data.length > 0) {
                        data.forEach(function (item) {
                            var a = document.createElement('a');
                            a.className = 'dropdown-item py-2 px-3 text-wrap border-bottom-subtle manage-eventphoto-search-item';
                            a.href = '#';

                            var titleText = item.title || item.name || '';
                            var photoCount = item.photo_count !== undefined ? item.photo_count : null;
                            var subDetails = [];
                            if (photoCount !== null) {
                                subDetails.push(photoCount + ' ' + (photoCount === 1 ? 'Photo' : 'Photos'));
                            }
                            if (item.category) subDetails.push(item.category);
                            if (item.event_date) subDetails.push(item.event_date);
                            if (item.status) subDetails.push('Status: ' + item.status);

                            a.innerHTML = '<div class="d-flex align-items-center justify-content-between mb-1">' +
                                    '<span class="text-dark small fw-semibold text-truncate me-2">' + escapeHtml(titleText) + '</span>' +
                                    '<span class="badge bg-purple-lt text-purple small flex-shrink-0">GALLERY</span>' +
                                '</div>' +
                                (subDetails.length > 0 ? '<div class="text-muted text-truncate" style="font-size: 0.75rem;">' + subDetails.map(escapeHtml).join(' &bull; ') + '</div>' : '');

                            a.addEventListener('click', function (e) {
                                e.preventDefault();
                                searchInput.value = titleText;
                                searchResults.style.display = 'none';
                                if (form) {
                                    if (typeof form.requestSubmit === 'function') {
                                        form.requestSubmit();
                                    } else {
                                        form.submit();
                                    }
                                }
                            });
                            searchResults.appendChild(a);
                        });
                        searchResults.style.display = 'block';
                    } else {
                        searchResults.innerHTML = '<div class="dropdown-item text-muted disabled py-2 px-3">No event found</div>';
                        searchResults.style.display = 'block';
                    }
                })
                .catch(function () {
                    searchResults.style.display = 'none';
                });
            }, 250);
        });

        searchInput.addEventListener('focus', function () {
            if (this.value.trim().length >= 1 && searchResults.children.length > 0) {
                searchResults.style.display = 'block';
            }
        });

        searchInput.addEventListener('keydown', function (e) {
            if (e.key === 'Enter') {
                searchResults.style.display = 'none';
            }
        });

        document.addEventListener('click', function (e) {
            if (searchInput && searchResults && !searchInput.contains(e.target) && !searchResults.contains(e.target)) {
                searchResults.style.display = 'none';
            }
        });
    }

    initManageEventPhotoAutocomplete();
});
</script>
@endsection
