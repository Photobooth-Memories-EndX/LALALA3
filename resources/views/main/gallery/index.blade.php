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
    z-index: 2000;
    border-radius: 8px;
    box-shadow: 0 10px 30px rgba(0, 0, 0, 0.15) !important;
    border: 1px solid #e2e8f0 !important;
    background: #ffffff !important;
    overflow: hidden !important;
}
#manage-eventphoto-autocomplete-list {
    max-height: 280px !important;
    overflow-y: auto !important;
    background-color: #ffffff !important;
    scrollbar-width: thin;
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

<div class="page-header d-print-none mb-3">
    <div class="container-xl">
        <!-- Back Navigation Link -->
        <div class="mb-3">
            <a href="{{ url('/manage') }}" class="text-decoration-none text-muted small d-inline-flex align-items-center gap-1">
                <i class="ti ti-arrow-left fs-3"></i><span>Back</span>
            </a>
        </div>

        <!-- Event Photo Gallery Hero Header Box -->
        <div class="card border-0 shadow-sm p-4 mb-4 rounded-3 bg-white">
            <div class="d-flex flex-column flex-md-row align-items-md-center justify-content-between gap-3">
                <div class="d-flex align-items-center gap-3">
                    <div class="rounded-3 d-flex align-items-center justify-content-center flex-shrink-0" 
                         style="width: 72px; height: 72px; background-color: #eef2ff; color: #003b6d;">
                        <i class="ti ti-calendar-event" style="font-size: 2rem !important;"></i>
                    </div>
                    <div>
                        <h2 class="h3 fw-bold mb-1 text-dark">Event Photo Gallery</h2>
                        <p class="text-muted small mb-0">Manage all gallery events, albums, and photo collections.</p>
                    </div>
                </div>
                <div class="flex-shrink-0">
                    <a href="{{ route('eventphoto.create') }}" class="btn btn-primary px-3 py-2 fw-semibold d-inline-flex align-items-center gap-2" style="background-color: #003b6d; border-color: #003b6d;">
                        <i class="ti ti-plus fs-2"></i><span>Add Event</span>
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="page-body">
    <div class="container-xl">

        <x-alert type="success" />
        <x-alert type="warning" />

        <!-- Event Photo Gallery 3-Column Stats Cards Grid -->
        <div class="row g-3 mb-4">
            <!-- Card 1: Total Event -->
            <div class="col-md-4">
                <div class="card border-0 shadow-sm p-3 rounded-3 bg-white">
                    <div class="d-flex align-items-center">
                        <div class="rounded-3 d-flex align-items-center justify-content-center flex-shrink-0 me-3" 
                             style="width: 44px; height: 44px; background-color: #003b6d; color: #ffffff;">
                            <i class="ti ti-calendar-event fs-2"></i>
                        </div>
                        <div>
                            <div class="text-muted fw-semibold" style="font-size: 0.8rem; line-height: 1.2;">Total Event</div>
                            <div class="fw-bold fs-3 text-dark mt-1" style="line-height: 1.1;">{{ $totalEvent ?? 0 }}</div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Card 2: Draft -->
            <div class="col-md-4">
                <div class="card border-0 shadow-sm p-3 rounded-3 bg-white">
                    <div class="d-flex align-items-center">
                        <div class="rounded-3 d-flex align-items-center justify-content-center flex-shrink-0 me-3" 
                             style="width: 44px; height: 44px; background-color: #e63946; color: #ffffff;">
                            <i class="ti ti-file-pencil fs-2"></i>
                        </div>
                        <div>
                            <div class="text-muted fw-semibold" style="font-size: 0.8rem; line-height: 1.2;">Draft</div>
                            <div class="fw-bold fs-3 text-dark mt-1" style="line-height: 1.1;">{{ $draftCount ?? ($draftEvent ?? 0) }}</div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Card 3: Total Photos -->
            <div class="col-md-4">
                <div class="card border-0 shadow-sm p-3 rounded-3 bg-white">
                    <div class="d-flex align-items-center">
                        <div class="rounded-3 d-flex align-items-center justify-content-center flex-shrink-0 me-3" 
                             style="width: 44px; height: 44px; background-color: #0284c7; color: #ffffff;">
                            <i class="ti ti-photo fs-2"></i>
                        </div>
                        <div>
                            <div class="text-muted fw-semibold" style="font-size: 0.8rem; line-height: 1.2;">Total Photos</div>
                            <div class="fw-bold fs-3 text-dark mt-1" style="line-height: 1.1;">{{ $totalPhotos ?? 0 }}</div>
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
                            <div class="position-relative manage-search-container flex-grow-1">
                                <div class="input-icon">
                                    <span class="input-icon-addon"><i class="ti ti-search"></i></span>
                                    <input type="text" name="q" id="manage-eventphoto-search-input" class="form-control" placeholder="Search gallery..." value="{{ request('q') ?? request('search') }}" autocomplete="off">
                                </div>
                                <div id="manage-eventphoto-search-results" class="dropdown-menu shadow-sm w-100 position-absolute mt-1 p-0 overflow-hidden" 
                                     style="display: none; z-index: 2000; background-color: #ffffff;">
                                    <!-- Container 1: Scrollable List Area (Max 280px height with vertical scrollbar) -->
                                    <div id="manage-eventphoto-autocomplete-list" class="list-group list-group-flush" 
                                         style="max-height: 280px; overflow-y: auto; background-color: #ffffff;"></div>
                                    
                                    <!-- Container 2: Fixed/Sticky Bottom Footer -->
                                    <div id="manage-eventphoto-search-footer" class="border-top p-2 text-center" 
                                         style="display: none; position: sticky; bottom: 0; z-index: 2005; background-color: #f8f9fa;"></div>
                                </div>
                            </div>
                        </div>

                        <!-- Action Buttons -->
                        <div class="col-auto d-flex align-items-center gap-1 flex-shrink-0">
                            <!-- 1. Search Submit Button -->
                            <button type="submit" class="btn btn-primary btn-icon px-3" title="Search">
                                <i class="ti ti-search fs-2"></i>
                            </button>

                            <!-- 2. More Filters Modal Trigger Button (Icon Only - No Text) -->
                            <button type="button" class="btn btn-outline-primary btn-icon px-3 position-relative" data-bs-toggle="modal" data-bs-target="#moreGalleryFiltersModal" title="More Filters">
                                <i class="ti ti-adjustments-horizontal fs-2"></i>
                                @if(request('date_from') || request('date_to') || request('author') || request('category') || request('status') || request('views'))
                                    <span class="position-absolute top-0 start-100 translate-middle p-1 bg-primary border border-light rounded-circle">
                                        <span class="visually-hidden">Active filter</span>
                                    </span>
                                @endif
                            </button>

                            <!-- 3. Reset Button -->
                            <a href="{{ route('manage.eventphoto.index') }}" class="btn btn-outline-secondary btn-icon px-3" title="Reset Filters">
                                <i class="ti ti-refresh fs-2"></i>
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
                <div class="modal-footer bg-light d-flex justify-content-end align-items-center gap-2">
                    <!-- Reset Button (Placed on the left of Apply Filter) -->
                    <a href="{{ route('manage.eventphoto.index') }}" class="btn btn-outline-secondary px-3 d-inline-flex align-items-center gap-1">
                        <i class="ti ti-rotate me-1"></i>
                        <span>Reset</span>
                    </a>

                    <!-- Apply Filter Button -->
                    <button type="submit" class="btn btn-primary px-3 d-inline-flex align-items-center gap-1">
                        <i class="ti ti-check me-1"></i>
                        <span>Apply Filter</span>
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

@endsection

@section('script')
<script>
document.addEventListener('DOMContentLoaded', function () {
    // Autocomplete is handled by standardized initManageAutocomplete
});
</script>
@endsection
