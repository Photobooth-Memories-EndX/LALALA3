@extends('layouts.sidebar-master')

@section('style')
<style>
.admin-news-img {
    width: 70px;
    height: 50px;
    object-fit: cover;
    border-radius: 8px;
    border: 1px solid var(--tblr-border-color);
    display: block;
}
.admin-news-placeholder {
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
.admin-news-stat {
    border-radius: 16px;
    transition: transform .2s, box-shadow .2s;
}
.admin-news-stat:hover {
    transform: translateY(-3px);
    box-shadow: 0 8px 24px rgba(0,0,0,.1);
}
.admin-news-action {
    width: 34px;
    height: 34px;
    padding: 0;
    display: inline-flex;
    align-items: center;
    justify-content: center;
}
.admin-news-row-title {
    font-weight: 600;
    max-width: 320px;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}
.admin-news-desc {
    font-size: .85rem;
    color: var(--tblr-secondary);
    max-width: 320px;
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
[data-bs-theme="dark"] #moreFiltersModal .text-dark {
    color: var(--tblr-body-color) !important;
}
.btn-quick-reset,
.btn-news-filter-reset {
    border-color: #0c5b9e !important;
    color: #0c5b9e !important;
    background-color: transparent !important;
    transition: background-color .15s ease-in-out, border-color .15s ease-in-out, color .15s ease-in-out;
}
.btn-quick-reset:hover,
.btn-news-filter-reset:hover {
    background-color: #e6f0fa !important;
    border-color: #0c5b9e !important;
    color: #0c5b9e !important;
}
.btn-quick-reset:hover i,
.btn-news-filter-reset:hover i {
    color: #0c5b9e !important;
}
[data-bs-theme="dark"] .btn-quick-reset:hover,
[data-bs-theme="dark"] .btn-news-filter-reset:hover {
    background-color: rgba(12, 91, 158, 0.25) !important;
    border-color: #3b82f6 !important;
    color: #60a5fa !important;
}
[data-bs-theme="dark"] .btn-quick-reset:hover i,
[data-bs-theme="dark"] .btn-news-filter-reset:hover i {
    color: #60a5fa !important;
}
.btn-back {
    color: #0c5b9e !important;
    transition: transform 0.2s ease, opacity 0.2s ease;
}
.btn-back:hover {
    opacity: 0.85;
    transform: translateX(-3px);
}
.manage-news-search-container {
    position: relative;
    width: 100%;
}
#manage-news-search-results {
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
#manage-news-autocomplete-list {
    max-height: 280px !important;
    overflow-y: auto !important;
    background-color: #ffffff !important;
    scrollbar-width: thin;
}
#manage-news-search-results .dropdown-item {
    padding: .6rem .85rem;
    transition: background-color .15s ease;
    text-decoration: none;
}
#manage-news-search-results .dropdown-item:hover,
#manage-news-search-results .dropdown-item:focus {
    background-color: rgba(15, 94, 156, .08);
}
#manage-news-search-results .dropdown-item:not(:last-child) {
    border-bottom: 1px solid rgba(0, 0, 0, .05);
}
[data-bs-theme="dark"] #manage-news-search-results,
body.dark-mode #manage-news-search-results,
body.theme-dark #manage-news-search-results {
    background: #1f2937;
    border-color: rgba(255, 255, 255, 0.12);
    box-shadow: 0 10px 25px rgba(0, 0, 0, 0.4);
}
[data-bs-theme="dark"] #manage-news-search-results .dropdown-item,
body.dark-mode #manage-news-search-results .dropdown-item,
body.theme-dark #manage-news-search-results .dropdown-item {
    color: #f8fafc;
}
[data-bs-theme="dark"] #manage-news-search-results .dropdown-item:not(:last-child),
body.dark-mode #manage-news-search-results .dropdown-item:not(:last-child),
body.theme-dark #manage-news-search-results .dropdown-item:not(:last-child) {
    border-bottom-color: rgba(255, 255, 255, 0.08);
}
[data-bs-theme="dark"] #manage-news-search-results .dropdown-item:hover,
[data-bs-theme="dark"] #manage-news-search-results .dropdown-item:focus,
body.dark-mode #manage-news-search-results .dropdown-item:hover,
body.dark-mode #manage-news-search-results .dropdown-item:focus,
body.theme-dark #manage-news-search-results .dropdown-item:hover,
body.theme-dark #manage-news-search-results .dropdown-item:focus {
    background: rgba(106, 174, 214, 0.16);
}
[data-bs-theme="dark"] #manage-news-search-results .dropdown-item .text-dark,
body.dark-mode #manage-news-search-results .dropdown-item .text-dark,
body.theme-dark #manage-news-search-results .dropdown-item .text-dark {
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
                    <div class="avatar avatar-lg bg-blue-lt">
                        <i class="ti ti-news fs-2"></i>
                    </div>
                    <div>
                        <h2 class="page-title mb-1">News Management</h2>
                        <div class="text-secondary">Manage company learning news and announcements</div>
                    </div>
                </div>
            </div>
            <div class="col-auto">
                <a href="{{ route('news.create') }}" class="btn btn-primary">
                    <i class="ti ti-plus me-1"></i>Add News
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
                <div class="card admin-news-stat">
                    <div class="card-body">
                        <div class="d-flex align-items-center">
                            <div class="flex-fill">
                                <div class="text-secondary text-uppercase fw-bold small mb-1">Total News</div>
                                <div class="display-6 fw-bold">{{ $totalNews ?? 0 }}</div>
                            </div>
                            <span class="avatar bg-blue-lt ms-3"><i class="ti ti-news"></i></span>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-sm-6 col-xl-3">
                <div class="card admin-news-stat">
                    <div class="card-body">
                        <div class="d-flex align-items-center">
                            <div class="flex-fill">
                                <div class="text-secondary text-uppercase fw-bold small mb-1">Total Authors</div>
                                <div class="display-6 fw-bold text-purple">{{ $totalAuthor ?? 0 }}</div>
                            </div>
                            <span class="avatar bg-purple-lt ms-3"><i class="ti ti-users"></i></span>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-sm-6 col-xl-3">
                <div class="card admin-news-stat">
                    <div class="card-body">
                        <div class="d-flex align-items-center">
                            <div class="flex-fill">
                                <div class="text-secondary text-uppercase fw-bold small mb-1">This Month</div>
                                <div class="display-6 fw-bold text-success">
                                    {{ isset($totalNews) ? \App\Models\News::whereMonth('created_at', now()->month)->count() : 0 }}
                                </div>
                            </div>
                            <span class="avatar bg-success-lt ms-3"><i class="ti ti-calendar-stats"></i></span>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-sm-6 col-xl-3">
                <div class="card admin-news-stat">
                    <div class="card-body">
                        <div class="d-flex align-items-center">
                            <div class="flex-fill">
                                <div class="text-secondary text-uppercase fw-bold small mb-1">Showing</div>
                                <div class="display-6 fw-bold text-info">{{ $news->count() }}</div>
                            </div>
                            <span class="avatar bg-info-lt ms-3"><i class="ti ti-list"></i></span>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        {{-- Quick Filter Component --}}
        <div class="card border-0 shadow-sm rounded-4 mb-4">
            <div class="card-body p-3">
                <!-- LABEL QUICK FILTER -->
                <div class="text-secondary small fw-semibold mb-2" style="color: #6c757d !important; font-size: 0.85rem;">
                    Quick Filter
                </div>

                <form action="{{ route('manage.news.index') }}" method="GET" id="manageNewsFilterForm">
                    <div class="row g-2 align-items-center">
                        <!-- Quick Filter Dropdowns -->
                        <div class="col-6 col-md-auto">
                            <select name="month" class="form-select rounded-3" onchange="document.getElementById('manageNewsFilterForm').submit()">
                                <option value="">Month: All</option>
                                @foreach(range(1, 12) as $m)
                                    <option value="{{ $m }}" {{ request('month') == $m ? 'selected' : '' }}>
                                        {{ date('F', mktime(0, 0, 0, $m, 1)) }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-6 col-md-auto">
                            <select name="year" class="form-select rounded-3" onchange="document.getElementById('manageNewsFilterForm').submit()">
                                <option value="">Year: All</option>
                                @foreach(range(date('Y'), 2020) as $y)
                                    <option value="{{ $y }}" {{ request('year') == $y ? 'selected' : '' }}>{{ $y }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-6 col-md-auto">
                            <select name="views" class="form-select rounded-3" onchange="document.getElementById('manageNewsFilterForm').submit()">
                                <option value="">Views: All Views</option>
                                <option value="most" {{ (request('views') == 'most' || request('views') == 'most_viewed') ? 'selected' : '' }}>Most Viewed</option>
                                <option value="least" {{ (request('views') == 'least' || request('views') == 'least_viewed') ? 'selected' : '' }}>Least Viewed</option>
                            </select>
                        </div>
                        <!-- TAMBAHAN: STATUS FILTER (Approved, Pending, Rejected) -->
                        <div class="col-6 col-md-auto">
                            <select name="status" class="form-select rounded-3" onchange="document.getElementById('manageNewsFilterForm').submit()">
                                <option value="">Status: All</option>
                                <option value="approved" {{ request('status') == 'approved' ? 'selected' : '' }}>Approved</option>
                                <option value="pending" {{ request('status') == 'pending' ? 'selected' : '' }}>Pending</option>
                                <option value="rejected" {{ (request('status') == 'rejected' || request('status') == 'reject') ? 'selected' : '' }}>Rejected</option>
                            </select>
                        </div>

                        <!-- Search Bar -->
                        <div class="col-12 col-md">
                            <div class="position-relative manage-search-container flex-grow-1">
                                <div class="input-icon">
                                    <span class="input-icon-addon"><i class="ti ti-search"></i></span>
                                    <input type="text" name="q" id="manage-news-search-input" class="form-control" placeholder="Search news..." value="{{ request('q') ?? request('search') }}" autocomplete="off">
                                </div>
                                <div id="manage-news-search-results" class="dropdown-menu shadow-sm w-100 position-absolute mt-1 p-0 overflow-hidden" 
                                     style="display: none; z-index: 2000; background-color: #ffffff;">
                                    <!-- Container 1: Scrollable List Area (Max 280px height with vertical scrollbar) -->
                                    <div id="manage-news-autocomplete-list" class="list-group list-group-flush" 
                                         style="max-height: 280px; overflow-y: auto; background-color: #ffffff;"></div>
                                    
                                    <!-- Container 2: Fixed/Sticky Bottom Footer -->
                                    <div id="manage-news-search-footer" class="border-top p-2 text-center" 
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
                            <button type="button" class="btn btn-outline-primary btn-icon px-3 position-relative" data-bs-toggle="modal" data-bs-target="#moreFiltersModal" title="More Filters">
                                <i class="ti ti-adjustments-horizontal fs-2"></i>
                                @if(request('date_from') || request('date_to') || request('author') || request('category') || request('status'))
                                    <span class="position-absolute top-0 start-100 translate-middle p-1 bg-primary border border-light rounded-circle">
                                        <span class="visually-hidden">Active filter</span>
                                    </span>
                                @endif
                            </button>

                            <!-- 3. Reset Button -->
                            <a href="{{ route('manage.news.index') }}" class="btn btn-outline-secondary btn-icon px-3" title="Reset Filters">
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
                <h3 class="card-title">List News</h3>
            </div>

            <div id="newsTableWrapper">
                <div class="table-responsive">
                    <table class="table table-vcenter card-table">
                        <thead>
                            <tr>
                                <th style="width:50px">NO</th>
                                <th style="width:90px">IMAGE</th>
                                <th>TITLE</th>
                                <th style="width:130px">CATEGORY</th>
                                <th style="width:110px" class="text-center">STATUS</th>
                                <th style="width:140px">AUTHOR</th>
                                <th style="width:120px" class="text-center">DATE</th>
                                <th style="width:100px" class="text-center">ACTION</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($news as $item)
                            <tr>
                                <td class="text-center text-secondary small">
                                    {{ $loop->iteration + (($news->currentPage()-1) * $news->perPage()) }}
                                </td>
                                <td>
                                    @if($item->image)
                                        <img src="{{ asset('storage/news/'.$item->image) }}"
                                             class="admin-news-img"
                                             alt="{{ $item->title }}"
                                             onerror="this.outerHTML='<div class=&quot;admin-news-placeholder&quot;><i class=&quot;ti ti-news&quot;></i></div>'">
                                    @else
                                        <div class="admin-news-placeholder">
                                            <i class="ti ti-news"></i>
                                        </div>
                                    @endif
                                </td>
                                <td>
                                    <div class="admin-news-row-title" title="{{ $item->title }}">
                                        {{ $item->title }}
                                    </div>
                                    @if($item->description)
                                    <div class="admin-news-desc">
                                        {{ Str::limit(strip_tags($item->description), 60) }}
                                    </div>
                                    @endif
                                </td>
                                <td>
                                    @if($item->category)
                                        <span class="badge bg-blue-lt text-blue">{{ $item->category }}</span>
                                    @else
                                        <span class="text-muted small">&mdash;</span>
                                    @endif
                                </td>
                                <td class="text-center">
                                    @php
                                        $statusVal = strtolower($item->status ?? 'approved');
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
                                <td>
                                    @if($item->user)
                                        <div class="d-flex align-items-center gap-2">
                                            <span class="avatar avatar-xs bg-blue-lt">
                                                {{ strtoupper(substr($item->user->name, 0, 1)) }}
                                            </span>
                                            <span class="small">{{ $item->user->name }}</span>
                                        </div>
                                    @else
                                        <span class="text-muted small">&mdash;</span>
                                    @endif
                                </td>
                                <td class="text-center text-secondary small">
                                    {{ $item->created_at ? $item->created_at->format('d M Y') : '&mdash;' }}
                                </td>
                                <td class="text-center">
                                    <div class="d-flex justify-content-center gap-1">
                                        <a href="{{ route('news.edit', $item->slug) }}"
                                           class="btn btn-sm btn-primary admin-news-action"
                                           title="Edit">
                                            <i class="ti ti-edit"></i>
                                        </a>
                                        <form action="{{ route('news.destroy', $item->slug) }}" method="POST" class="m-0">
                                            @csrf
                                            @method('DELETE')
                                            <button type="button"
                                                    onclick="if(confirm('Delete this news article? This cannot be undone.')){this.closest('form').submit()}"
                                                    class="btn btn-sm btn-danger admin-news-action"
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
                                            <i class="ti ti-news-off" style="font-size:3rem;opacity:.4;"></i>
                                        </div>
                                        <p class="empty-title">No news articles found</p>
                                        <p class="empty-subtitle text-secondary">Try adjusting your search or add a new article.</p>
                                        <div class="empty-action">
                                            <a href="{{ route('news.create') }}" class="btn btn-primary btn-sm">
                                                <i class="ti ti-plus me-1"></i>Add News
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
                    {{ $news->withQueryString()->links() }}
                </div>
            </div>

        </div>

    </div>
</div>

{{-- More Filters Modal --}}
<div class="modal fade" id="moreFiltersModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content rounded-4 border-0 shadow">
            <div class="modal-header border-0 pb-0">
                <h5 class="modal-title fw-bold d-flex align-items-center gap-2">
                    <i class="ti ti-adjustments-horizontal text-primary"></i> More Filters
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form action="{{ route('manage.news.index') }}" method="GET">
                <div class="modal-body py-3">
                    <!-- Input tersembunyi untuk mempertahankan nilai Quick Filter saat ini -->
                    <input type="hidden" name="search" value="{{ request('search') }}">
                    <input type="hidden" name="status" value="{{ request('status') }}">
                    <input type="hidden" name="month" value="{{ request('month') }}">
                    <input type="hidden" name="year" value="{{ request('year') }}">
                    <input type="hidden" name="views" value="{{ request('views') }}">

                    <div class="mb-3">
                        <label class="form-label fw-bold text-dark small">Author</label>
                        <select name="author_id" class="form-select rounded-3">
                            <option value="">All Authors</option>
                            @foreach($authors as $author)
                                <option value="{{ $author->id }}" {{ (request('author_id') == $author->id || request('author') == $author->id) ? 'selected' : '' }}>{{ $author->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="row g-2 mb-3">
                        <div class="col-6">
                            <label class="form-label fw-bold text-dark small">Category / Topic</label>
                            <select name="category_id" class="form-select rounded-3">
                                <option value="">All Categories</option>
                                @foreach($categories as $cat)
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
                        <label class="form-label fw-bold text-dark small"><i class="ti ti-calendar me-1"></i> Period</label>
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
                    <a href="{{ route('manage.news.index') }}" class="btn btn-outline-secondary px-3 d-inline-flex align-items-center gap-1">
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
