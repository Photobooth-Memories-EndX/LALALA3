@extends('layouts.sidebar-master')

@section('content')
    @include('main.monitoring.components.style')

    {{-- Page Header --}}
    <div class="page-header d-print-none">
        <div class="container-xl">
            <!-- TOMBOL BACK KE DASHBOARD MANAGE -->
            <div class="mb-3">
                <a href="{{ route('manage.index') }}" class="btn-back mb-3 d-inline-flex align-items-center gap-1 text-decoration-none">
                    <i class="ti ti-arrow-left"></i>
                    <span>Back</span>
                </a>
            </div>

            <div class="row align-items-center">
                <div class="col">
                    <div class="d-flex align-items-center gap-3">
                        <div class="avatar avatar-lg bg-primary-lt">
                            <i class="ti ti-books fs-2"></i>
                        </div>
                        <div>
                            <h2 class="page-title mb-1">Content Management</h2>
                            <div class="text-secondary">Manage learning materials, videos, documents, assessments, and playlist content.</div>
                        </div>
                    </div>
                </div>
                <div class="col-auto d-flex gap-2">
                    <form action="{{ route('learning-hours.sync') }}" method="POST" class="d-inline">
                        @csrf
                        <button type="submit" class="btn btn-outline-primary" onclick="return confirm('Synchronize all Learning Hours?')">
                            <i class="ti ti-refresh me-1"></i>Sync Learning Hours
                        </button>
                    </form>
                    <button type="button" id="btnSyncPdfTotalPage" class="btn btn-outline-primary">
                        <i class="ti ti-file-description me-1"></i>Sync Total Page PDF
                    </button>
                    <a href="{{ route('content.create') }}" class="btn btn-primary">
                        <i class="ti ti-plus me-1"></i>Add Content
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

            {{-- Stat Cards --}}
<div class="row row-cards mb-4">

    {{-- Total Content --}}
    <div class="col-12 col-md-6 col-xl-4">
        <div class="card admin-gallery-stat h-100">
            <div class="card-body">
                <div class="d-flex align-items-center justify-content-between">
                    <div>
                        <div class="text-secondary text-uppercase fw-bold small mb-2">
                            Total Content
                        </div>
                        <div class="display-6 fw-bold">
                            {{ number_format($totalContent) }}
                        </div>
                    </div>

                    <span class="avatar avatar-lg bg-primary-lt">
                        <i class="ti ti-books fs-2"></i>
                    </span>
                </div>
            </div>
        </div>
    </div>

    {{-- Active --}}
    <div class="col-12 col-md-6 col-xl-4">
        <div class="card admin-gallery-stat h-100">
            <div class="card-body">
                <div class="d-flex align-items-center justify-content-between">
                    <div>
                        <div class="text-secondary text-uppercase fw-bold small mb-2">
                            Active
                        </div>
                        <div class="display-6 fw-bold text-success">
                            {{ number_format($activeContent) }}
                        </div>
                    </div>

                    <span class="avatar avatar-lg bg-success-lt">
                        <i class="ti ti-circle-check fs-2"></i>
                    </span>
                </div>
            </div>
        </div>
    </div>

    {{-- Inactive --}}
    <div class="col-12 col-md-6 col-xl-4">
        <div class="card admin-gallery-stat h-100">
            <div class="card-body">
                <div class="d-flex align-items-center justify-content-between">
                    <div>
                        <div class="text-secondary text-uppercase fw-bold small mb-2">
                            Inactive
                        </div>
                        <div class="display-6 fw-bold text-warning">
                            {{ number_format($inactiveContent) }}
                        </div>
                    </div>

                    <span class="avatar avatar-lg bg-warning-lt">
                        <i class="ti ti-circle-x fs-2"></i>
                    </span>
                </div>
            </div>
        </div>
    </div>

    {{-- Playlist Only --}}
    <div class="col-12 col-md-6 col-xl-4">
        <div class="card admin-gallery-stat h-100">
            <div class="card-body">
                <div class="d-flex align-items-center justify-content-between">
                    <div>
                        <div class="text-secondary text-uppercase fw-bold small mb-2">
                            Playlist
                        </div>
                        <div class="display-6 fw-bold text-purple">
                            {{ number_format($playlistOnly) }}
                        </div>
                    </div>

                    <span class="avatar avatar-lg bg-purple-lt">
                        <i class="ti ti-playlist fs-2"></i>
                    </span>
                </div>
            </div>
        </div>
    </div>

    {{-- With Deadline --}}
    <div class="col-12 col-md-6 col-xl-4">
        <div class="card admin-gallery-stat h-100">
            <div class="card-body">
                <div class="d-flex align-items-center justify-content-between">
                    <div>
                        <div class="text-secondary text-uppercase fw-bold small mb-2">
                            With Deadline
                        </div>
                        <div class="display-6 fw-bold text-info">
                            {{ number_format($withDeadline) }}
                        </div>
                    </div>

                    <span class="avatar avatar-lg bg-info-lt">
                        <i class="ti ti-clock fs-2"></i>
                    </span>
                </div>
            </div>
        </div>
    </div>

    {{-- Total Views --}}
    <div class="col-12 col-md-6 col-xl-4">
        <div class="card admin-gallery-stat h-100">
            <div class="card-body">
                <div class="d-flex align-items-center justify-content-between">
                    <div>
                        <div class="text-secondary text-uppercase fw-bold small mb-2">
                            Total Views
                        </div>
                        <div class="display-6 fw-bold text-azure">
                            {{ number_format($totalViews) }}
                        </div>
                    </div>

                    <span class="avatar avatar-lg bg-azure-lt">
                        <i class="ti ti-eye fs-2"></i>
                    </span>
                </div>
            </div>
        </div>
    </div>

</div>

            {{-- Quick Filter Card --}}
            <div class="card border-0 shadow-sm rounded-4 mb-4">
                <div class="card-body p-3">
                    <!-- Label Header Quick Filter -->
                    <div class="text-secondary small fw-semibold mb-2" style="color: #6c757d !important; font-size: 0.85rem;">
                        Quick Filter
                    </div>

                    <form action="{{ route('content.index') }}" method="GET" id="manageContentFilterForm">
                        <div class="row g-2 align-items-center">
                            <!-- Filter Status -->
                            <div class="col-6 col-md-auto">
                                <select name="active" class="form-select rounded-3">
                                    <option value="">All Status</option>
                                    <option value="Active" {{ (request('active') == 'Active' || request('status') == 'Active') ? 'selected' : '' }}>Active</option>
                                    <option value="Inactive" {{ (request('active') == 'Inactive' || request('status') == 'Inactive') ? 'selected' : '' }}>Inactive</option>
                                </select>
                            </div>

                            <!-- Filter Type -->
                            <div class="col-6 col-md-auto">
                                <select name="type" class="form-select rounded-3">
                                    <option value="">All Type</option>
                                    @foreach ($types as $t)
                                        <option value="{{ $t }}" {{ request('type') == $t ? 'selected' : '' }}>{{ \App\Support\ContentTypeHelper::label($t) }}</option>
                                    @endforeach
                                </select>
                            </div>

                            <!-- Filter Classification -->
                            <div class="col-6 col-md-auto">
                                <select name="classification_id" class="form-select rounded-3">
                                    <option value="">All Classification</option>
                                    @foreach ($classifications as $c)
                                        <option value="{{ $c->id }}" {{ (request('classification_id') == $c->id || request('classification') == $c->id) ? 'selected' : '' }}>{{ $c->classification_name }}</option>
                                    @endforeach
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
                                        @php $mVal = str_pad((string)$m, 2, '0', STR_PAD_LEFT); @endphp
                                        <option value="{{ $mVal }}" {{ (request('month') == $m || request('month') == $mVal) ? 'selected' : '' }}>
                                            {{ date('F', mktime(0, 0, 0, $m, 1)) }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>

                            <!-- Input Search Bar -->
                            <div class="col-12 col-md">
                                <div class="position-relative manage-content-search-container flex-grow-1">
                                    <div class="input-group">
                                        <span class="input-group-text bg-white border-end-0 rounded-start-3"><i class="ti ti-search text-muted"></i></span>
                                        <input type="text" id="manage-content-search-input" name="s" class="form-control border-start-0 rounded-end-3" placeholder="Search content..." value="{{ request('s') ?? request('search') }}" autocomplete="off">
                                    </div>
                                    <div id="manage-content-search-results" class="dropdown-menu shadow-sm w-100 position-absolute mt-1" style="display: none; max-height: 280px; overflow-y: auto; z-index: 1050;"></div>
                                </div>
                            </div>

                            <!-- Action Buttons (Search, More Filter, Reset) -->
                            <div class="col-auto d-flex gap-1">
                                <button type="submit" class="btn btn-primary rounded-3 px-3" style="background-color: #0c5b9e; border-color: #0c5b9e;" title="Search">
                                    <i class="ti ti-search"></i>
                                </button>
                                <button type="button" class="btn btn-outline-primary rounded-3 px-3" style="border-color: #0c5b9e; color: #0c5b9e;" data-bs-toggle="modal" data-bs-target="#moreContentFiltersModal" title="More Filters">
                                    <i class="ti ti-adjustments-horizontal"></i>
                                </button>
                                <a href="{{ route('content.index') }}" class="btn btn-outline-primary rounded-3 px-3 btn-quick-reset" style="border-color: #0c5b9e; color: #0c5b9e;" title="Reset Filter">
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
                            Showing {{ $contents->firstItem() ?? 0 }}–{{ $contents->lastItem() ?? 0 }} of {{ $contents->total() }} content(s)
                        </div>
                    </div>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        @if ($contents->count() > 0)
                            <table class="table table-hover owl-table mb-0">
                                <thead>
                                    <tr>
                                        <th>No</th>
                                        <th>Title</th>
                                        <th>Type</th>
                                        <th>Classification</th>
                                        <th>Duration</th>
                                        <th>Date</th>
                                        <th>Status</th>
                                        <th>Exam</th>
                                        <th>Playlist</th>
                                        <th>Deadline</th>
                                        <th class="text-center">Views</th>
                                        <th>Action</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach ($contents as $list)
                                        <tr>
                                            <td>{{ $contents->firstItem() + $loop->index }}</td>
                                            <td>
                                                <span class="monitoring-link fw-semibold"
                                                    data-bs-trigger="hover"
                                                    data-bs-toggle="popover"
                                                    title="Title"
                                                    data-bs-content="{{ $list->title }}">
                                                    {{ Str::limit($list->title, 30) }}
                                                </span>
                                            </td>
                                            <td>
                                                @php
                                                    $typeColor = [
                                                        'Video'          => 'blue',
                                                        'Document'       => 'cyan',
                                                        'YouTube'        => 'red',
                                                        'SOP/Regulation' => 'purple',
                                                    ][$list->type] ?? 'secondary';
                                                @endphp
                                                <span class="badge bg-{{ $typeColor }}-lt">{{ \App\Support\ContentTypeHelper::label($list->type) }}</span>
                                            </td>
                                            <td>{{ $list->classification->classification_name ?? '-' }}</td>
                                            <td>{{ \App\Support\DurationFormatter::format($list->duration) }}</td>
                                            <td>{{ $list->date ? \Carbon\Carbon::parse($list->date)->format('d/m/Y') : '-' }}</td>
                                            <td>
                                                @php $isActive = $list->active == 'Active'; @endphp
                                                <span class="badge bg-{{ $isActive ? 'success' : 'warning' }}-lt text-{{ $isActive ? 'success' : 'warning' }}">
                                                    {{ $list->active }}
                                                </span>
                                            </td>
                                            <td>
                                                @if ($list->exam == 'OK')
                                                    <span class="badge bg-success-lt text-success">OK</span>
                                                @else
                                                    <span class="badge bg-secondary-lt text-secondary">NOT</span>
                                                @endif
                                            </td>
                                            <td>
                                                @if ($list->playlistonly == 'Y')
                                                    <span class="badge bg-purple-lt text-purple">Yes</span>
                                                @else
                                                    <span class="badge bg-secondary-lt text-secondary">No</span>
                                                @endif
                                            </td>
                                            <td>
                                                @if ($list->has_deadline && $list->from_date && $list->to_date)
                                                    @php $expired = now()->gt($list->to_date); @endphp
                                                    <span class="badge bg-{{ $expired ? 'danger' : 'cyan' }}-lt text-{{ $expired ? 'danger' : 'cyan' }}" style="font-size:11px;">
                                                        {{ $list->from_date->format('d/m/Y') }} – {{ $list->to_date->format('d/m/Y') }}
                                                    </span>
                                                @else
                                                    <span class="badge bg-secondary-lt text-secondary" style="font-size:11px;">No Deadline</span>
                                                @endif
                                            </td>
                                            <td class="text-center">
                                                <span class="badge bg-azure-lt text-azure">{{ number_format($list->views ?? 0) }}</span>
                                            </td>
                                            <td>
                                                <div class="d-flex gap-1">
                                                    <a href="{{ route('content.edit', $list) }}"
                                                        class="btn btn-sm btn-info owl-action-btn"
                                                        title="Edit">
                                                        <i class="ti ti-edit"></i>
                                                    </a>
                                                    <form action="{{ route('content.destroy', $list) }}" method="POST" class="d-inline">
                                                        @csrf
                                                        @method('DELETE')
                                                        <button type="submit"
                                                            class="btn btn-sm btn-danger owl-action-btn"
                                                            title="Delete"
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
                                    <i class="ti ti-books" style="font-size:3rem;opacity:.4;"></i>
                                </div>
                                <p class="empty-title">No content found</p>
                                <p class="empty-subtitle text-secondary">Try adjusting your filters or add new content.</p>
                                <div class="empty-action">
                                    <a href="{{ route('content.create') }}" class="btn btn-primary btn-sm">
                                        <i class="ti ti-plus me-1"></i>Add Content
                                    </a>
                                </div>
                            </div>
                        @endif
                    </div>
                </div>
                @if ($contents->hasPages())
                    <div class="card-footer d-flex align-items-center justify-content-center py-3">
                        {{ $contents->links() }}
                    </div>
                @endif
            </div>

        </div>
    </div>

    <!-- More Filters Modal -->
    <div class="modal fade" id="moreContentFiltersModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-lg">
            <div class="modal-content rounded-4 border-0 shadow">
                <div class="modal-header border-0 pb-0">
                    <h5 class="modal-title fw-bold text-dark d-flex align-items-center gap-2">
                        <i class="ti ti-adjustments-horizontal text-primary"></i> More Filters
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form action="{{ route('content.index') }}" method="GET">
                    <div class="modal-body py-3">
                        <input type="hidden" name="s" value="{{ request('s') ?? request('search') }}">
                        <input type="hidden" name="active" value="{{ request('active') ?? request('status') }}">
                        <input type="hidden" name="type" value="{{ request('type') }}">
                        <input type="hidden" name="classification_id" value="{{ request('classification_id') ?? request('classification') }}">
                        <input type="hidden" name="year" value="{{ request('year') }}">
                        <input type="hidden" name="month" value="{{ request('month') }}">

                        <!-- Author Dropdown -->
                        <div class="mb-3">
                            <label class="form-label fw-bold text-dark small">Author</label>
                            <select name="author" class="form-select rounded-3">
                                <option value="">All Authors</option>
                                @foreach($authors ?? [] as $author)
                                    <option value="{{ $author }}" {{ request('author') == $author ? 'selected' : '' }}>{{ $author }}</option>
                                @endforeach
                            </select>
                        </div>

                        <!-- Created Date (dd/mm/yyyy) -->
                        <div class="mb-3">
                            <label class="form-label fw-bold text-dark small"><i class="ti ti-calendar me-1"></i> Created Date</label>
                            <div class="row g-2">
                                <div class="col-6">
                                    <label class="form-label small text-muted mb-1">From Date</label>
                                    <input type="text" name="created_from_date" onfocus="(this.type='date')" onblur="if(!this.value)this.type='text'" class="form-control rounded-3" placeholder="dd/mm/yyyy" value="{{ request('created_from_date') ?? request('created_from') }}">
                                </div>
                                <div class="col-6">
                                    <label class="form-label small text-muted mb-1">To Date</label>
                                    <input type="text" name="created_to_date" onfocus="(this.type='date')" onblur="if(!this.value)this.type='text'" class="form-control rounded-3" placeholder="dd/mm/yyyy" value="{{ request('created_to_date') ?? request('created_to') }}">
                                </div>
                            </div>
                        </div>

                        <!-- Period Range (dd/mm/yyyy) -->
                        <div class="mb-3">
                            <label class="form-label fw-bold text-dark small"><i class="ti ti-calendar me-1"></i> Period</label>
                            <div class="row g-2">
                                <div class="col-6">
                                    <label class="form-label small text-muted mb-1">From Date</label>
                                    <input type="text" name="deadline_from_date" onfocus="(this.type='date')" onblur="if(!this.value)this.type='text'" class="form-control rounded-3" placeholder="dd/mm/yyyy" value="{{ request('deadline_from_date') ?? (request('period_from') ?? request('date_from')) }}">
                                </div>
                                <div class="col-6">
                                    <label class="form-label small text-muted mb-1">To Date</label>
                                    <input type="text" name="deadline_to_date" onfocus="(this.type='date')" onblur="if(!this.value)this.type='text'" class="form-control rounded-3" placeholder="dd/mm/yyyy" value="{{ request('deadline_to_date') ?? (request('period_to') ?? request('date_to')) }}">
                                </div>
                            </div>
                        </div>

                        <!-- Deadline & Sort by Row -->
                        <div class="row g-3 mb-3">
                            <div class="col-12 col-md-6">
                                <label class="form-label fw-bold text-dark small">Deadline</label>
                                @php $currentDeadline = request('deadline_status') ?? request('deadline'); @endphp
                                <select name="deadline_status" class="form-select rounded-3">
                                    <option value="all" {{ ($currentDeadline == 'all' || blank($currentDeadline)) ? 'selected' : '' }}>All</option>
                                    <option value="expired" {{ $currentDeadline == 'expired' ? 'selected' : '' }}>Expired</option>
                                    <option value="today" {{ $currentDeadline == 'today' ? 'selected' : '' }}>Today</option>
                                    <option value="less_than_7_days" {{ $currentDeadline == 'less_than_7_days' ? 'selected' : '' }}>Less than 7 Days</option>
                                    <option value="less_than_30_days" {{ $currentDeadline == 'less_than_30_days' ? 'selected' : '' }}>Less than 30 Days</option>
                                    <option value="more_than_30_days" {{ $currentDeadline == 'more_than_30_days' ? 'selected' : '' }}>More than 30 Days</option>
                                </select>
                            </div>
                            <div class="col-12 col-md-6">
                                <label class="form-label fw-bold text-dark small">Sort By</label>
                                <select name="sort" class="form-select rounded-3">
                                    <option value="" {{ blank(request('sort')) ? 'selected' : '' }}>All</option>
                                    <option value="newest" {{ (request('sort') == 'newest' || request('sort') == 'newest_course') ? 'selected' : '' }}>Newest</option>
                                    <option value="oldest" {{ (request('sort') == 'oldest' || request('sort') == 'oldest_course') ? 'selected' : '' }}>Oldest</option>
                                    <option value="asc" {{ (request('sort') == 'asc' || request('sort') == 'title_asc' || request('sort') == 'title') ? 'selected' : '' }}>Title (A-Z)</option>
                                    <option value="desc" {{ (request('sort') == 'desc' || request('sort') == 'title_desc') ? 'selected' : '' }}>Title (Z-A)</option>
                                    <option value="views" {{ request('sort') == 'views' ? 'selected' : '' }}>Most Views</option>
                                </select>
                            </div>
                        </div>

                        <!-- Sort Views Radio -->
                        <div class="mb-3">
                            <label class="form-label fw-bold text-dark small d-block">Sort Views</label>
                            @php $currViewOrder = request('view_order') ?? request('sort_views'); @endphp
                            <div class="d-flex gap-3 align-items-center">
                                <div class="form-check">
                                    <input class="form-check-input" type="radio" name="view_order" id="contentViewDefault" value="" {{ blank($currViewOrder) ? 'checked' : '' }}>
                                    <label class="form-check-label text-dark" for="contentViewDefault">Default</label>
                                </div>
                                <div class="form-check">
                                    <input class="form-check-input" type="radio" name="view_order" id="contentViewMost" value="most_viewed" {{ ($currViewOrder === 'most_viewed' || $currViewOrder === 'most') ? 'checked' : '' }}>
                                    <label class="form-check-label text-dark" for="contentViewMost">Most Viewed</label>
                                </div>
                                <div class="form-check">
                                    <input class="form-check-input" type="radio" name="view_order" id="contentViewLeast" value="least_viewed" {{ ($currViewOrder === 'least_viewed' || $currViewOrder === 'least') ? 'checked' : '' }}>
                                    <label class="form-check-label text-dark" for="contentViewLeast">Least Viewed</label>
                                </div>
                            </div>
                        </div>

                        <!-- Duration Radio -->
                        <div class="mb-3">
                            <label class="form-label fw-bold text-dark small d-block">Duration</label>
                            @php $currDuration = request('duration', 'all'); @endphp
                            <div class="d-flex flex-wrap gap-3 align-items-center">
                                <div class="form-check">
                                    <input class="form-check-input" type="radio" name="duration" id="contentDurAll" value="all" {{ ($currDuration === 'all' || blank($currDuration)) ? 'checked' : '' }}>
                                    <label class="form-check-label text-dark" for="contentDurAll">All Durations</label>
                                </div>
                                <div class="form-check">
                                    <input class="form-check-input" type="radio" name="duration" id="contentDur10" value="under_10" {{ ($currDuration === 'under_10' || $currDuration === 'lt10') ? 'checked' : '' }}>
                                    <label class="form-check-label text-dark" for="contentDur10">&lt; 10 Menit</label>
                                </div>
                                <div class="form-check">
                                    <input class="form-check-input" type="radio" name="duration" id="contentDur30" value="10_30" {{ ($currDuration === '10_30' || $currDuration === '10-30') ? 'checked' : '' }}>
                                    <label class="form-check-label text-dark" for="contentDur30">10 - 30 Menit</label>
                                </div>
                                <div class="form-check">
                                    <input class="form-check-input" type="radio" name="duration" id="contentDur60" value="30_60" {{ ($currDuration === '30_60' || $currDuration === '30-60') ? 'checked' : '' }}>
                                    <label class="form-check-label text-dark" for="contentDur60">30 - 60 Menit</label>
                                </div>
                                <div class="form-check">
                                    <input class="form-check-input" type="radio" name="duration" id="contentDurAbove60" value="above_60" {{ ($currDuration === 'above_60' || $currDuration === 'gt60') ? 'checked' : '' }}>
                                    <label class="form-check-label text-dark" for="contentDurAbove60">&gt; 1 Jam</label>
                                </div>
                            </div>
                        </div>

                        <!-- Learning Hour Radio -->
                        <div class="mb-3">
                            <label class="form-label fw-bold text-dark small d-block">Learning Hour</label>
                            @php $currLH = request('learning_hour', 'all'); @endphp
                            <div class="d-flex flex-wrap gap-3 align-items-center">
                                <div class="form-check">
                                    <input class="form-check-input" type="radio" name="learning_hour" id="contentLhAll" value="all" {{ ($currLH === 'all' || blank($currLH)) ? 'checked' : '' }}>
                                    <label class="form-check-label text-dark" for="contentLhAll">All</label>
                                </div>
                                <div class="form-check">
                                    <input class="form-check-input" type="radio" name="learning_hour" id="contentLh1" value="lt1h" {{ $currLH === 'lt1h' ? 'checked' : '' }}>
                                    <label class="form-check-label text-dark" for="contentLh1">&lt; 1 Hour</label>
                                </div>
                                <div class="form-check">
                                    <input class="form-check-input" type="radio" name="learning_hour" id="contentLh3" value="1-3h" {{ $currLH === '1-3h' ? 'checked' : '' }}>
                                    <label class="form-check-label text-dark" for="contentLh3">1 - 3 hours</label>
                                </div>
                                <div class="form-check">
                                    <input class="form-check-input" type="radio" name="learning_hour" id="contentLhAbove3" value="gt3h" {{ $currLH === 'gt3h' ? 'checked' : '' }}>
                                    <label class="form-check-label text-dark" for="contentLhAbove3">&gt; 3 Hours</label>
                                </div>
                            </div>
                        </div>

                        <!-- Exam Status Radio -->
                        <div class="mb-3">
                            <label class="form-label fw-bold text-dark small d-block">Exam Status</label>
                            @php $currExam = request('exam', ''); @endphp
                            <div class="d-flex flex-wrap gap-3 align-items-center">
                                <div class="form-check">
                                    <input class="form-check-input" type="radio" name="exam" id="contentExamAll" value="" {{ blank($currExam) ? 'checked' : '' }}>
                                    <label class="form-check-label text-dark" for="contentExamAll">All</label>
                                </div>
                                <div class="form-check">
                                    <input class="form-check-input" type="radio" name="exam" id="contentExamOk" value="OK" {{ ($currExam === 'OK' || $currExam === 'Yes' || $currExam === '1') ? 'checked' : '' }}>
                                    <label class="form-check-label text-dark" for="contentExamOk">With Exam (OK)</label>
                                </div>
                                <div class="form-check">
                                    <input class="form-check-input" type="radio" name="exam" id="contentExamNot" value="NOT" {{ ($currExam === 'NOT' || $currExam === 'No' || $currExam === '0') ? 'checked' : '' }}>
                                    <label class="form-check-label text-dark" for="contentExamNot">Without Exam (NOT)</label>
                                </div>
                            </div>
                        </div>

                        <!-- Playlist Only Radio -->
                        <div class="mb-2">
                            <label class="form-label fw-bold text-dark small d-block">Playlist Only / Status</label>
                            @php $currPlaylist = request('playlistonly') ?? request('playlist'); @endphp
                            <div class="d-flex flex-wrap gap-3 align-items-center">
                                <div class="form-check">
                                    <input class="form-check-input" type="radio" name="playlistonly" id="contentPlaylistAll" value="" {{ blank($currPlaylist) ? 'checked' : '' }}>
                                    <label class="form-check-label text-dark" for="contentPlaylistAll">All</label>
                                </div>
                                <div class="form-check">
                                    <input class="form-check-input" type="radio" name="playlistonly" id="contentPlaylistYes" value="Y" {{ ($currPlaylist === 'Y' || $currPlaylist === 'Yes' || $currPlaylist === '1') ? 'checked' : '' }}>
                                    <label class="form-check-label text-dark" for="contentPlaylistYes">Playlist Only (Yes)</label>
                                </div>
                                <div class="form-check">
                                    <input class="form-check-input" type="radio" name="playlistonly" id="contentPlaylistNo" value="N" {{ ($currPlaylist === 'N' || $currPlaylist === 'No' || $currPlaylist === '0') ? 'checked' : '' }}>
                                    <label class="form-check-label text-dark" for="contentPlaylistNo">Non-Playlist (No)</label>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer border-0 pt-0 d-flex gap-2">
                        <a href="{{ route('content.index') }}" class="btn btn-outline-secondary rounded-pill flex-fill"><i class="ti ti-refresh me-1"></i> Reset</a>
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
.btn-back {
    color: #0c5b9e !important;
    transition: transform 0.2s ease, opacity 0.2s ease;
}
.btn-back:hover {
    opacity: 0.85;
    transform: translateX(-3px);
}
.btn-quick-reset {
    transition: all 0.2s ease;
}
.btn-quick-reset:hover {
    background-color: #0c5b9e !important;
    color: #ffffff !important;
}
[data-bs-theme="dark"] .btn-quick-reset,
body.dark-mode .btn-quick-reset,
body.theme-dark .btn-quick-reset {
    background-color: transparent !important;
}
[data-bs-theme="dark"] .btn-quick-reset:hover,
body.dark-mode .btn-quick-reset:hover,
body.theme-dark .btn-quick-reset:hover {
    background-color: #0c5b9e !important;
    color: #ffffff !important;
}
[data-bs-theme="dark"] #moreContentFiltersModal .modal-content,
body.dark-mode #moreContentFiltersModal .modal-content,
body.theme-dark #moreContentFiltersModal .modal-content {
    background-color: var(--tblr-bg-surface, #1f2937) !important;
    color: #f8fafc !important;
}
[data-bs-theme="dark"] #moreContentFiltersModal .text-dark,
body.dark-mode #moreContentFiltersModal .text-dark,
body.theme-dark #moreContentFiltersModal .text-dark {
    color: #f8fafc !important;
}
[data-bs-theme="dark"] #moreContentFiltersModal .form-control,
[data-bs-theme="dark"] #moreContentFiltersModal .form-select,
body.dark-mode #moreContentFiltersModal .form-control,
body.dark-mode #moreContentFiltersModal .form-select,
body.theme-dark #moreContentFiltersModal .form-control,
body.theme-dark #moreContentFiltersModal .form-select {
    background-color: var(--tblr-bg-surface-secondary, #111827) !important;
    border-color: rgba(255, 255, 255, 0.15) !important;
    color: #f8fafc !important;
}
.admin-gallery-stat {
    border-radius: 16px;
    transition: transform .2s, box-shadow .2s;
}
.admin-gallery-stat:hover {
    transform: translateY(-3px);
    box-shadow: 0 8px 24px rgba(0,0,0,.1);
}
.content-manage-filter .form-label {
    margin-bottom: 4px;
}
[data-theme="dark"] .card,
body.theme-dark .card {
    background: var(--tblr-bg-surface, #1f2937);
}
.manage-content-search-container {
    position: relative;
    width: 100%;
}
#manage-content-search-results {
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
#manage-content-search-results .dropdown-item {
    padding: .6rem .85rem;
    transition: background-color .15s ease;
    text-decoration: none;
}
#manage-content-search-results .dropdown-item:hover,
#manage-content-search-results .dropdown-item:focus {
    background-color: rgba(15, 94, 156, .08);
}
#manage-content-search-results .dropdown-item:not(:last-child) {
    border-bottom: 1px solid rgba(0, 0, 0, .05);
}
[data-bs-theme="dark"] #manage-content-search-results,
body.dark-mode #manage-content-search-results,
body.theme-dark #manage-content-search-results {
    background: #1f2937;
    border-color: rgba(255, 255, 255, 0.12);
    box-shadow: 0 10px 25px rgba(0, 0, 0, 0.4);
}
[data-bs-theme="dark"] #manage-content-search-results .dropdown-item,
body.dark-mode #manage-content-search-results .dropdown-item,
body.theme-dark #manage-content-search-results .dropdown-item {
    color: #f8fafc;
}
[data-bs-theme="dark"] #manage-content-search-results .dropdown-item:not(:last-child),
body.dark-mode #manage-content-search-results .dropdown-item:not(:last-child),
body.theme-dark #manage-content-search-results .dropdown-item:not(:last-child) {
    border-bottom-color: rgba(255, 255, 255, 0.08);
}
[data-bs-theme="dark"] #manage-content-search-results .dropdown-item:hover,
[data-bs-theme="dark"] #manage-content-search-results .dropdown-item:focus,
body.dark-mode #manage-content-search-results .dropdown-item:hover,
body.dark-mode #manage-content-search-results .dropdown-item:focus,
body.theme-dark #manage-content-search-results .dropdown-item:hover,
body.theme-dark #manage-content-search-results .dropdown-item:focus {
    background: rgba(106, 174, 214, 0.16);
}
[data-bs-theme="dark"] #manage-content-search-results .dropdown-item .text-dark,
body.dark-mode #manage-content-search-results .dropdown-item .text-dark,
body.theme-dark #manage-content-search-results .dropdown-item .text-dark {
    color: #f8fafc !important;
}
</style>
@endsection

@section('script')
    <script src="{{ asset('js/pdf.min.js') }}"></script>
    <script>
        var pdfjsLib = window["pdfjs-dist/build/pdf"];
        if (pdfjsLib) {
            pdfjsLib.GlobalWorkerOptions.workerSrc = "{{ asset('js/pdf.worker.min.js') }}";
        }
    </script>
    <script src="{{ asset('js/sync-content-document-total-page.js') }}?v={{ filemtime(public_path('js/sync-content-document-total-page.js')) }}"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            new SyncContentDocumentTotalPage({
                buttonId: 'btnSyncPdfTotalPage',
                fetchListUrl: "{{ route('content.document-pdf-list') }}",
                syncUrl: "{{ route('content.sync-total-page') }}",
                csrfToken: "{{ csrf_token() }}",
                workerSrc: "{{ asset('js/pdf.worker.min.js') }}"
            });

            // ── Manage Content Repository Live Autocomplete ─────────────────────────
            function initManageContentAutocomplete() {
                var searchInput = document.getElementById('manage-content-search-input') || document.querySelector('input[name="s"]');
                var searchResults = document.getElementById('manage-content-search-results');
                var form = document.getElementById('manageContentFilterForm') || searchInput?.closest('form');
                var debounceTimer = null;

                if (!searchInput || !searchResults) return;

                function escapeHtml(text) {
                    if (!text) return '';
                    var div = document.createElement('div');
                    div.textContent = text;
                    return div.innerHTML;
                }

                function getTypeBadgeClass(type) {
                    switch (type) {
                        case 'Video': return 'bg-blue-lt text-blue';
                        case 'Document': return 'bg-cyan-lt text-cyan';
                        case 'YouTube': return 'bg-red-lt text-red';
                        case 'SOP/Regulation': return 'bg-purple-lt text-purple';
                        default: return 'bg-secondary-lt text-secondary';
                    }
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
                        fetch('/manage/content/search-autocomplete?q=' + encodeURIComponent(query), {
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
                                    a.className = 'dropdown-item py-2 px-3 text-wrap border-bottom-subtle manage-content-search-item';
                                    a.href = '#';

                                    var titleText = item.title || item.name || '';
                                    var typeLabel = item.type_label || item.type || 'Content';
                                    var badgeClass = getTypeBadgeClass(item.type);

                                    var subDetails = [];
                                    if (item.classification) subDetails.push(item.classification);
                                    if (item.author) subDetails.push('By: ' + item.author);
                                    if (item.active) subDetails.push(item.active);

                                    a.innerHTML = '<div class="d-flex align-items-center justify-content-between mb-1">' +
                                            '<span class="text-dark small fw-semibold text-truncate me-2">' + escapeHtml(titleText) + '</span>' +
                                            '<span class="badge ' + badgeClass + ' small flex-shrink-0">' + escapeHtml(typeLabel) + '</span>' +
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
                                searchResults.innerHTML = '<div class="dropdown-item text-muted disabled py-2 px-3">No content found</div>';
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

            initManageContentAutocomplete();
        });
    </script>
@endsection
