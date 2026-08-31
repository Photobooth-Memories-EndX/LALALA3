@extends('layouts.sidebar-master')

@section('content')
    @include('main.monitoring.components.style')

    {{-- Page Header --}}
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
                            <i class="ti ti-playlist fs-2"></i>
                        </div>
                        <div>
                            <h2 class="page-title mb-1">Playlist Management</h2>
                            <div class="text-secondary">Manage learning playlists, content order, thumbnails, and training deadlines.</div>
                        </div>
                    </div>
                </div>
                <div class="col-auto d-flex gap-2">
                    <form action="{{ route('learning-hours.sync') }}" method="POST" class="d-inline">
                        @csrf
                        <button type="submit" class="btn btn-outline-primary" onclick="return confirm('Synchronize all Learning Hours?')">
                            <i class="ti ti-refresh me-1"></i>Synchronize
                        </button>
                    </form>
                    <a href="{{ route('playlist.create') }}" class="btn btn-primary">
                        <i class="ti ti-plus me-1"></i>Add Playlist
                    </a>
                </div>
            </div>
        </div>
    </div>

    <div class="page-body">
        <div class="container-xl">

            @if (session()->has('success'))
                <div class="alert alert-important alert-success alert-dismissible mb-3" role="alert">
                    <div class="d-flex">
                        <div>
                            <svg xmlns="http://www.w3.org/2000/svg" class="icon alert-icon" width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round">
                                <path stroke="none" d="M0 0h24v24H0z" fill="none"></path>
                                <path d="M5 12l5 5l10 -10"></path>
                            </svg>
                        </div>
                        <div style="white-space: pre-line;">{{ session('success') }}</div>
                    </div>
                    <a class="btn-close btn-close-white" data-bs-dismiss="alert" aria-label="close"></a>
                </div>
            @endif

            @if (session()->has('warning'))
                <div class="alert alert-important alert-warning alert-dismissible mb-3" role="alert">
                    <div class="d-flex">
                        <div>{{ session('warning') }}</div>
                    </div>
                    <a class="btn-close btn-close-white" data-bs-dismiss="alert" aria-label="close"></a>
                </div>
            @endif

            {{-- Stat Cards --}}
            <div class="row row-cards mb-4">
                <div class="col-12 col-md-6 col-xl-4">
                    <div class="card admin-gallery-stat h-100">
                        <div class="card-body">
                            <div class="d-flex align-items-center justify-content-between">
                                <div>
                                    <div class="text-secondary text-uppercase fw-bold small mb-2">Total Playlist</div>
                                    <div class="display-6 fw-bold">{{ number_format($totalTrainingPlan) }}</div>
                                </div>
                                <span class="avatar avatar-lg bg-primary-lt">
                                    <i class="ti ti-playlist fs-2"></i>
                                </span>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-12 col-md-6 col-xl-4">
                    <div class="card admin-gallery-stat h-100">
                        <div class="card-body">
                            <div class="d-flex align-items-center justify-content-between">
                                <div>
                                    <div class="text-secondary text-uppercase fw-bold small mb-2">Active</div>
                                    <div class="display-6 fw-bold text-success">{{ number_format($activeTrainingPlan) }}</div>
                                </div>
                                <span class="avatar avatar-lg bg-success-lt">
                                    <i class="ti ti-circle-check fs-2"></i>
                                </span>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-12 col-md-6 col-xl-4">
                    <div class="card admin-gallery-stat h-100">
                        <div class="card-body">
                            <div class="d-flex align-items-center justify-content-between">
                                <div>
                                    <div class="text-secondary text-uppercase fw-bold small mb-2">Inactive</div>
                                    <div class="display-6 fw-bold text-warning">{{ number_format($inactiveTrainingPlan) }}</div>
                                </div>
                                <span class="avatar avatar-lg bg-warning-lt">
                                    <i class="ti ti-circle-x fs-2"></i>
                                </span>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-12 col-md-6 col-xl-4">
                    <div class="card admin-gallery-stat h-100">
                        <div class="card-body">
                            <div class="d-flex align-items-center justify-content-between">
                                <div>
                                    <div class="text-secondary text-uppercase fw-bold small mb-2">With Deadline</div>
                                    <div class="display-6 fw-bold text-info">{{ number_format($withDeadline) }}</div>
                                </div>
                                <span class="avatar avatar-lg bg-info-lt">
                                    <i class="ti ti-clock fs-2"></i>
                                </span>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-12 col-md-6 col-xl-4">
                    <div class="card admin-gallery-stat h-100">
                        <div class="card-body">
                            <div class="d-flex align-items-center justify-content-between">
                                <div>
                                    <div class="text-secondary text-uppercase fw-bold small mb-2">Expired Deadline</div>
                                    <div class="display-6 fw-bold text-danger">{{ number_format($expiredDeadline) }}</div>
                                </div>
                                <span class="avatar avatar-lg bg-danger-lt">
                                    <i class="ti ti-calendar-x fs-2"></i>
                                </span>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-12 col-md-6 col-xl-4">
                    <div class="card admin-gallery-stat h-100">
                        <div class="card-body">
                            <div class="d-flex align-items-center justify-content-between">
                                <div>
                                    <div class="text-secondary text-uppercase fw-bold small mb-2">Total Materials</div>
                                    <div class="display-6 fw-bold text-azure">{{ number_format($totalMaterials) }}</div>
                                </div>
                                <span class="avatar avatar-lg bg-azure-lt">
                                    <i class="ti ti-books fs-2"></i>
                                </span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Quick Filter Component -->
            <div class="card border-0 shadow-sm rounded-4 mb-4">
                <div class="card-body p-3">
                    <!-- Label Header Quick Filter -->
                    <div class="text-secondary small fw-semibold mb-2" style="color: #6c757d !important; font-size: 0.85rem;">
                        Quick Filter
                    </div>

                    <form action="{{ route('playlist.index') }}" method="GET" id="managePlaylistFilterForm">
                        <div class="row g-2 align-items-center">
                            <!-- Filter Status -->
                            <div class="col-6 col-md-auto">
                                <select name="status" class="form-select rounded-3">
                                    <option value="">All Status</option>
                                    <option value="Active" {{ (request('status') == 'Active' || request('active') == 'Active') ? 'selected' : '' }}>Active</option>
                                    <option value="Inactive" {{ (request('status') == 'Inactive' || request('active') == 'Inactive') ? 'selected' : '' }}>Inactive</option>
                                    <option value="Draft" {{ request('status') == 'Draft' ? 'selected' : '' }}>Draft</option>
                                </select>
                            </div>
                            <!-- Filter Year & Month -->
                            <div class="col-6 col-md-auto">
                                <select name="year" class="form-select rounded-3">
                                    <option value="">All Year</option>
                                    @foreach(range(date('Y'), 2020) as $y)
                                        <option value="{{ $y }}" {{ request('year') == $y ? 'selected' : '' }}>{{ $y }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-6 col-md-auto">
                                <select name="month" class="form-select rounded-3">
                                    <option value="">All Month</option>
                                    @foreach(range(1, 12) as $m)
                                        <option value="{{ $m }}" {{ request('month') == $m ? 'selected' : '' }}>
                                            {{ date('F', mktime(0, 0, 0, $m, 1)) }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>

                            <!-- Input Search Bar -->
                            <div class="col-12 col-md">
                                <div class="input-group">
                                    <span class="input-group-text bg-white border-end-0 rounded-start-3"><i class="ti ti-search text-muted"></i></span>
                                    <input type="text" name="search" class="form-control border-start-0 rounded-end-3" placeholder="Search playlist..." value="{{ request('search') ?? request('s') }}">
                                </div>
                            </div>

                            <!-- Action Buttons (Search, More Filter, Reset) -->
                            <div class="col-auto d-flex gap-1">
                                <button type="submit" class="btn btn-primary rounded-3 px-3" style="background-color: #0c5b9e; border-color: #0c5b9e;" title="Search">
                                    <i class="ti ti-search"></i>
                                </button>
                                <button type="button" class="btn btn-outline-primary rounded-3 px-3" style="border-color: #0c5b9e; color: #0c5b9e;" data-bs-toggle="modal" data-bs-target="#morePlaylistFiltersModal" title="More Filters">
                                    <i class="ti ti-adjustments-horizontal"></i>
                                </button>
                                <a href="{{ route('playlist.index') }}" class="btn btn-outline-primary rounded-3 px-3 btn-quick-reset" style="border-color: #0c5b9e; color: #0c5b9e;" title="Reset Filter">
                                    <i class="ti ti-refresh"></i>
                                </a>
                            </div>
                        </div>
                    </form>
                </div>
            </div>

            {{-- Table Card --}}
            <div class="card owl-main-card">
                <div class="card-header">
                    <div>
                        <h3 class="card-title">{{ $title }}</h3>
                        <div class="card-subtitle text-muted mt-1">
                            Showing {{ $trainingplans->firstItem() ?? 0 }}–{{ $trainingplans->lastItem() ?? 0 }} of {{ $trainingplans->total() }} playlist(s)
                        </div>
                    </div>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        @if ($trainingplans->count() > 0)
                            <table class="table table-hover owl-table mb-0">
                                <thead>
                                    <tr>
                                        <th style="width:48px">No</th>
                                        <th style="width:80px">Thumbnail</th>
                                        <th>Title</th>
                                        <th>Slug</th>
                                        <th class="text-center" style="width:90px">Materials</th>
                                        <th style="width:90px">Status</th>
                                        <th>Deadline</th>
                                        <th>URL Link</th>
                                        <th style="width:110px">Created At</th>
                                        <th style="width:120px">Action</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach ($trainingplans as $list)
                                        <tr>
                                            <td>{{ $trainingplans->firstItem() + $loop->index }}</td>
                                            <td>
                                                @if($list->thumbnail_url)
                                                    <img src="{{ $list->thumbnail_url }}"
                                                        alt="thumb"
                                                        class="tp-thumb-img"
                                                        onerror="this.style.display='none';this.nextElementSibling.style.display='inline-flex';">
                                                    <span class="avatar avatar-sm bg-blue-lt text-blue" style="display:none;">
                                                        <i class="ti ti-playlist"></i>
                                                    </span>
                                                @else
                                                    <span class="avatar avatar-sm bg-blue-lt text-blue">
                                                        <i class="ti ti-playlist"></i>
                                                    </span>
                                                @endif
                                            </td>
                                            <td>
                                                <span class="monitoring-link fw-semibold">
                                                    {{ Str::limit($list->title, 35) }}
                                                </span>
                                            </td>
                                            <td>
                                                <code class="text-muted" style="font-size:11px;">{{ Str::limit($list->slug, 30) }}</code>
                                            </td>
                                            <td class="text-center">
                                                <span class="badge bg-azure-lt text-azure">
                                                    {{ $list->trainingplandetail_count }}
                                                </span>
                                            </td>
                                            <td>
                                                @php $isActive = $list->active == 'Active'; @endphp
                                                <span class="badge bg-{{ $isActive ? 'success' : 'warning' }}-lt text-{{ $isActive ? 'success' : 'warning' }}">
                                                    {{ $list->active }}
                                                </span>
                                            </td>
                                            <td>
                                                @if($list->has_deadline && $list->from_date && $list->to_date)
                                                    @php $expired = now()->gt($list->to_date); @endphp
                                                    <span class="badge bg-{{ $expired ? 'danger' : 'cyan' }}-lt text-{{ $expired ? 'danger' : 'cyan' }}" style="font-size:11px;">
                                                        {{ $list->from_date->format('d/m/Y') }} – {{ $list->to_date->format('d/m/Y') }}
                                                    </span>
                                                @else
                                                    <span class="badge bg-secondary-lt text-secondary" style="font-size:11px;">No Deadline</span>
                                                @endif
                                            </td>
                                            <td>
                                                @if($list->url_link)
                                                    <a href="{{ $list->url_link }}" target="_blank" class="text-truncate d-inline-block" style="max-width:140px;" title="{{ $list->url_link }}">
                                                        <i class="ti ti-external-link me-1"></i>{{ Str::limit($list->url_link, 20) }}
                                                    </a>
                                                @else
                                                    <span class="text-muted">—</span>
                                                @endif
                                            </td>
                                            <td class="text-secondary small">
                                                {{ $list->created_at->format('d/m/Y') }}
                                            </td>
                                            <td>
                                                <div class="d-flex gap-1 flex-wrap">
                                                    <a href="{{ route('playlist.edit', $list->slug) }}"
                                                        class="btn btn-sm btn-info owl-action-btn" title="Edit">
                                                        <i class="ti ti-edit"></i>
                                                    </a>
                                                    <a href="{{ route('playlist.order', $list->slug) }}"
                                                        class="btn btn-sm btn-secondary owl-action-btn" title="Order Content">
                                                        <i class="ti ti-arrows-sort"></i>
                                                    </a>
                                                    <form action="{{ route('playlist.destroy', $list->slug) }}" method="POST" class="d-inline">
                                                        @csrf
                                                        @method('DELETE')
                                                        <button type="submit"
                                                            class="btn btn-sm btn-danger owl-action-btn" title="Delete"
                                                            onclick="return confirm('Are you sure want to delete this data?')">
                                                            <i class="ti ti-trash"></i>
                                                        </button>
                                                    </form>
                                                </div>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        @else
                            <div class="empty py-5">
                                <div class="empty-icon">
                                    <i class="ti ti-playlist" style="font-size:3rem;opacity:.4;"></i>
                                </div>
                                <p class="empty-title">No training plans found</p>
                                <p class="empty-subtitle text-secondary">Try adjusting your filters or add a new training plan.</p>
                                <div class="empty-action">
                                    <a href="{{ route('playlist.create') }}" class="btn btn-primary btn-sm">
                                        <i class="ti ti-plus me-1"></i>Add Playlist
                                    </a>
                                </div>
                            </div>
                        @endif
                    </div>
                </div>
                @if ($trainingplans->hasPages())
                    <div class="card-footer d-flex align-items-center justify-content-center py-3">
                        {{ $trainingplans->links() }}
                    </div>
                @endif
            </div>

        </div>
    </div>

    <!-- More Filters Modal -->
    <div class="modal fade" id="morePlaylistFiltersModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-lg">
            <div class="modal-content rounded-4 border-0 shadow">
                <div class="modal-header border-0 pb-0">
                    <h5 class="modal-title fw-bold text-dark d-flex align-items-center gap-2">
                        <i class="ti ti-adjustments-horizontal text-primary"></i> More Filters
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form action="{{ route('playlist.index') }}" method="GET">
                    <div class="modal-body py-3">
                        <input type="hidden" name="search" value="{{ request('search') ?? request('s') }}">
                        <input type="hidden" name="status" value="{{ request('status') ?? request('active') }}">
                        <input type="hidden" name="year" value="{{ request('year') }}">
                        <input type="hidden" name="month" value="{{ request('month') }}">

                        <!-- Author Dropdown -->
                        <div class="mb-3">
                            <label class="form-label fw-bold text-dark small">Author</label>
                            <select name="author_id" class="form-select rounded-3">
                                <option value="">All Authors</option>
                                @foreach($authors ?? [] as $author)
                                    <option value="{{ $author->id ?? $author }}" {{ (request('author_id') == ($author->id ?? $author) || request('author') == ($author->id ?? $author)) ? 'selected' : '' }}>{{ $author->name ?? $author }}</option>
                                @endforeach
                            </select>
                        </div>

                        <!-- Created Date (dd/mm/yyyy) -->
                        <div class="mb-3">
                            <label class="form-label fw-bold text-dark small"><i class="ti ti-calendar me-1"></i> Created Date</label>
                            <div class="row g-2">
                                <div class="col-6">
                                    <input type="text" name="created_from" onfocus="(this.type='date')" onblur="if(!this.value)this.type='text'" class="form-control rounded-3" placeholder="dd/mm/yyyy" value="{{ request('created_from') }}">
                                </div>
                                <div class="col-6">
                                    <input type="text" name="created_to" onfocus="(this.type='date')" onblur="if(!this.value)this.type='text'" class="form-control rounded-3" placeholder="dd/mm/yyyy" value="{{ request('created_to') }}">
                                </div>
                            </div>
                        </div>

                        <!-- Period Range (dd/mm/yyyy) -->
                        <div class="mb-3">
                            <label class="form-label fw-bold text-dark small"><i class="ti ti-calendar me-1"></i> Period</label>
                            <div class="row g-2">
                                <div class="col-6">
                                    <input type="text" name="period_from" onfocus="(this.type='date')" onblur="if(!this.value)this.type='text'" class="form-control rounded-3" placeholder="dd/mm/yyyy" value="{{ request('period_from') }}">
                                </div>
                                <div class="col-6">
                                    <input type="text" name="period_to" onfocus="(this.type='date')" onblur="if(!this.value)this.type='text'" class="form-control rounded-3" placeholder="dd/mm/yyyy" value="{{ request('period_to') }}">
                                </div>
                            </div>
                        </div>

                        <!-- Sort By Dropdown -->
                        <div class="mb-3">
                            <label class="form-label fw-bold text-dark small">Sort By</label>
                            <select name="sort" class="form-select rounded-3">
                                <option value="">All</option>
                                <option value="newest" {{ request('sort') == 'newest' ? 'selected' : '' }}>Newest</option>
                                <option value="oldest" {{ request('sort') == 'oldest' ? 'selected' : '' }}>Oldest</option>
                                <option value="title_asc" {{ (request('sort') == 'title_asc' || request('sort') == 'title') ? 'selected' : '' }}>Title (A-Z)</option>
                            </select>
                        </div>

                        <!-- Sort Views Radio -->
                        <div class="mb-3">
                            <label class="form-label fw-bold text-dark small d-block">Sort Views</label>
                            <div class="d-flex gap-3 align-items-center">
                                <div class="form-check">
                                    <input class="form-check-input" type="radio" name="sort_views" id="viewDefault" value="" {{ !request('sort_views') ? 'checked' : '' }}>
                                    <label class="form-check-label text-dark" for="viewDefault">Default</label>
                                </div>
                                <div class="form-check">
                                    <input class="form-check-input" type="radio" name="sort_views" id="viewMost" value="most" {{ request('sort_views') == 'most' ? 'checked' : '' }}>
                                    <label class="form-check-label text-dark" for="viewMost">Most Viewed</label>
                                </div>
                                <div class="form-check">
                                    <input class="form-check-input" type="radio" name="sort_views" id="viewLeast" value="least" {{ request('sort_views') == 'least' ? 'checked' : '' }}>
                                    <label class="form-check-label text-dark" for="viewLeast">Least Viewed</label>
                                </div>
                            </div>
                        </div>

                        <!-- Playlist Duration Radio -->
                        <div class="mb-2">
                            <label class="form-label fw-bold text-dark small d-block">Playlist Duration</label>
                            <div class="d-flex flex-wrap gap-3 align-items-center">
                                <div class="form-check">
                                    <input class="form-check-input" type="radio" name="duration" id="durAll" value="" {{ !request('duration') ? 'checked' : '' }}>
                                    <label class="form-check-label text-dark" for="durAll">All Durations</label>
                                </div>
                                <div class="form-check">
                                    <input class="form-check-input" type="radio" name="duration" id="dur10" value="under_10" {{ request('duration') == 'under_10' ? 'checked' : '' }}>
                                    <label class="form-check-label text-dark" for="dur10">&lt; 10 Menit</label>
                                </div>
                                <div class="form-check">
                                    <input class="form-check-input" type="radio" name="duration" id="dur30" value="10_30" {{ request('duration') == '10_30' ? 'checked' : '' }}>
                                    <label class="form-check-label text-dark" for="dur30">10 - 30 Menit</label>
                                </div>
                                <div class="form-check">
                                    <input class="form-check-input" type="radio" name="duration" id="dur60" value="30_60" {{ request('duration') == '30_60' ? 'checked' : '' }}>
                                    <label class="form-check-label text-dark" for="dur60">30 - 60 Menit</label>
                                </div>
                                <div class="form-check">
                                    <input class="form-check-input" type="radio" name="duration" id="durAbove60" value="above_60" {{ request('duration') == 'above_60' ? 'checked' : '' }}>
                                    <label class="form-check-label text-dark" for="durAbove60">&gt; 1 Jam</label>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer border-0 pt-0 d-flex gap-2">
                        <a href="{{ route('playlist.index') }}" class="btn btn-outline-secondary rounded-pill flex-fill"><i class="ti ti-refresh me-1"></i> Reset</a>
                        <button type="submit" class="btn btn-primary rounded-pill flex-fill" style="background-color: #0c5b9e; border-color: #0c5b9e;"><i class="ti ti-check me-1"></i> Apply Filter</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

@endsection

@section('style')
<style>
.page-body {
    background: #fff;
}
[data-bs-theme="dark"] .page-body,
body.dark-mode .page-body,
body.theme-dark .page-body {
    background: var(--tblr-body-bg, #111827);
}
.admin-gallery-stat {
    border-radius: 16px;
    transition: transform .2s, box-shadow .2s;
}
.admin-gallery-stat:hover {
    transform: translateY(-3px);
    box-shadow: 0 8px 24px rgba(0,0,0,.1);
}
.tp-thumb-img {
    width: 72px;
    height: 48px;
    object-fit: cover;
    border-radius: 6px;
    border: 1px solid var(--tblr-border-color);
    display: block;
}
[data-theme="dark"] .card,
body.theme-dark .card {
    background: var(--tblr-bg-surface, #1f2937);
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
[data-bs-theme="dark"] .btn-quick-reset:hover i {
    color: #60a5fa !important;
}
[data-bs-theme="dark"] #morePlaylistFiltersModal .text-dark {
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
</style>
@endsection
