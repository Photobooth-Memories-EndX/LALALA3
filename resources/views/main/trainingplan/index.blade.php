@extends('layouts.sidebar-master')

@section('content')
    @include('main.monitoring.components.style')

    {{-- Page Header --}}
    <div class="page-header d-print-none mb-3">
        <div class="container-xl">
            <!-- Back Navigation Link -->
            <div class="mb-3">
                <a href="{{ url('/manage') }}" class="text-decoration-none text-muted small d-inline-flex align-items-center gap-1">
                    <i class="ti ti-arrow-left fs-3"></i>
                    <span>Back</span>
                </a>
            </div>

            <!-- Playlist Management Hero Header Box (Matching Content Management) -->
            <div class="card border-0 shadow-sm p-4 mb-4 rounded-3 bg-white">
                <div class="d-flex flex-column flex-md-row align-items-md-center justify-content-between gap-3">
                    <!-- Left Group: Large Icon Box + Title & Description -->
                    <div class="d-flex align-items-center gap-3">
                        <!-- Playlist Icon Box (Equal size to Content: 72px x 72px) -->
                        <div class="rounded-3 d-flex align-items-center justify-content-center flex-shrink-0" 
                             style="width: 72px; height: 72px; background-color: #eef2ff; color: #003b6d;">
                            <i class="ti ti-playlist fs-1" style="font-size: 2rem !important;"></i>
                        </div>

                        <!-- Title & Subtitle -->
                        <div>
                            <h2 class="h3 fw-bold mb-1 text-dark">Playlist Management</h2>
                            <p class="text-muted small mb-0">Manage learning playlists, content order, thumbnails, and training deadlines.</p>
                        </div>
                    </div>

                    <!-- Right Group: Action Button -->
                    <div class="flex-shrink-0">
                        <a href="{{ route('manage.playlist.create') }}" class="btn btn-primary px-3 py-2 fw-semibold d-inline-flex align-items-center gap-2" style="background-color: #003b6d; border-color: #003b6d;">
                            <i class="ti ti-plus fs-2"></i>
                            <span>Add Playlist</span>
                        </a>
                    </div>
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
            <div class="row g-3 mb-4">
                <!-- Card 1: Total Playlist -->
                <div class="col-md-3">
                    <div class="card border-0 shadow-sm p-3 rounded-3 bg-white">
                        <div class="d-flex align-items-center">
                            <div class="rounded-3 d-flex align-items-center justify-content-center flex-shrink-0 me-3" 
                                 style="width: 44px; height: 44px; background-color: #0f4c81; color: #ffffff;">
                                <i class="ti ti-playlist fs-2"></i>
                            </div>
                            <div>
                                <div class="text-muted fw-semibold" style="font-size: 0.8rem; line-height: 1.2;">Total Playlist</div>
                                <div class="fw-bold fs-3 text-dark mt-1" style="line-height: 1.1;">{{ number_format($totalTrainingPlan ?? 0) }}</div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Card 2: Active -->
                <div class="col-md-3">
                    <div class="card border-0 shadow-sm p-3 rounded-3 bg-white">
                        <div class="d-flex align-items-center">
                            <div class="rounded-3 d-flex align-items-center justify-content-center flex-shrink-0 me-3" 
                                 style="width: 44px; height: 44px; background-color: #38b000; color: #ffffff;">
                                <i class="ti ti-check fs-2"></i>
                            </div>
                            <div>
                                <div class="text-muted fw-semibold" style="font-size: 0.8rem; line-height: 1.2;">Active</div>
                                <div class="fw-bold fs-3 text-dark mt-1" style="line-height: 1.1;">{{ number_format($activeTrainingPlan ?? 0) }}</div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Card 3: Inactive -->
                <div class="col-md-3">
                    <div class="card border-0 shadow-sm p-3 rounded-3 bg-white">
                        <div class="d-flex align-items-center">
                            <div class="rounded-3 d-flex align-items-center justify-content-center flex-shrink-0 me-3" 
                                 style="width: 44px; height: 44px; background-color: #e63946; color: #ffffff;">
                                <i class="ti ti-x fs-2"></i>
                            </div>
                            <div>
                                <div class="text-muted fw-semibold" style="font-size: 0.8rem; line-height: 1.2;">Inactive</div>
                                <div class="fw-bold fs-3 text-dark mt-1" style="line-height: 1.1;">{{ number_format($inactiveTrainingPlan ?? 0) }}</div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Card 4: Total Materials -->
                <div class="col-md-3">
                    <div class="card border-0 shadow-sm p-3 rounded-3 bg-white">
                        <div class="d-flex align-items-center">
                            <div class="rounded-3 d-flex align-items-center justify-content-center flex-shrink-0 me-3" 
                                 style="width: 44px; height: 44px; background-color: #0284c7; color: #ffffff;">
                                <i class="ti ti-books fs-2"></i>
                            </div>
                            <div>
                                <div class="text-muted fw-semibold" style="font-size: 0.8rem; line-height: 1.2;">Total Materials</div>
                                <div class="fw-bold fs-3 text-dark mt-1" style="line-height: 1.1;">{{ number_format($totalMaterials ?? 0) }}</div>
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
                                <div class="position-relative manage-search-container flex-grow-1">
                                    <div class="input-icon">
                                        <span class="input-icon-addon"><i class="ti ti-search"></i></span>
                                        <input type="text" id="manage-playlist-search-input" name="q" value="{{ request('q') ?? (request('search') ?? request('s')) }}"
                                            class="form-control" placeholder="Search playlist..." autocomplete="off">
                                    </div>
                                    
                                    <div id="manage-playlist-search-results" class="dropdown-menu shadow-sm w-100 position-absolute mt-1 p-0 overflow-hidden" 
                                         style="display: none; z-index: 2000; background-color: #ffffff;">
                                        <!-- Container 1: Scrollable List Area (Max 280px height with vertical scrollbar) -->
                                        <div id="manage-playlist-autocomplete-list" class="list-group list-group-flush" 
                                             style="max-height: 280px; overflow-y: auto; background-color: #ffffff;"></div>
                                        
                                        <!-- Container 2: Fixed/Sticky Bottom Footer -->
                                        <div id="manage-playlist-search-footer" class="border-top p-2 text-center" 
                                             style="display: none; position: sticky; bottom: 0; z-index: 2005; background-color: #f8f9fa;"></div>
                                    </div>
                                </div>
                            </div>

                            <!-- Action Buttons (Search, More Filters & Reset) -->
                            <div class="col-auto d-flex align-items-center gap-1 flex-shrink-0">
                                <!-- 1. Search Submit Button -->
                                <button type="submit" class="btn btn-primary btn-icon px-3" title="Search">
                                    <i class="ti ti-search fs-2"></i>
                                </button>

                                <!-- 2. More Filters Modal Trigger Button (Icon Only - No Text) -->
                                <button type="button" class="btn btn-outline-primary btn-icon px-3 position-relative" data-bs-toggle="modal" data-bs-target="#morePlaylistFiltersModal" title="More Filters">
                                    <i class="ti ti-adjustments-horizontal fs-2"></i>
                                    @if(request('date_from') || request('date_to') || request('author') || request('category') || request('status') || request('sort'))
                                        <span class="position-absolute top-0 start-100 translate-middle p-1 bg-primary border border-light rounded-circle">
                                            <span class="visually-hidden">Active filter</span>
                                        </span>
                                    @endif
                                </button>

                                <!-- 3. Reset Button -->
                                <a href="{{ route('playlist.index') }}" class="btn btn-outline-secondary btn-icon px-3" title="Reset Filters">
                                    <i class="ti ti-refresh fs-2"></i>
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
                    <div class="modal-footer bg-light d-flex justify-content-end align-items-center gap-2">
                        <!-- Reset Button (Placed on the left of Apply Filter) -->
                        <a href="{{ route('playlist.index') }}" class="btn btn-outline-secondary px-3 d-inline-flex align-items-center gap-1">
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
.manage-playlist-search-container {
    position: relative;
    width: 100%;
}
#manage-playlist-search-results {
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
#manage-playlist-autocomplete-list {
    max-height: 280px !important;
    overflow-y: auto !important;
    background-color: #ffffff !important;
    scrollbar-width: thin;
}
#manage-playlist-search-results .dropdown-item {
    padding: .6rem .85rem;
    transition: background-color .15s ease;
    text-decoration: none;
}
#manage-playlist-search-results .dropdown-item:hover,
#manage-playlist-search-results .dropdown-item:focus {
    background-color: rgba(15, 94, 156, .08);
}
#manage-playlist-search-results .dropdown-item:not(:last-child) {
    border-bottom: 1px solid rgba(0, 0, 0, .05);
}
[data-bs-theme="dark"] #manage-playlist-search-results,
body.dark-mode #manage-playlist-search-results,
body.theme-dark #manage-playlist-search-results {
    background: #1f2937;
    border-color: rgba(255, 255, 255, 0.12);
    box-shadow: 0 10px 25px rgba(0, 0, 0, 0.4);
}
[data-bs-theme="dark"] #manage-playlist-search-results .dropdown-item,
body.dark-mode #manage-playlist-search-results .dropdown-item,
body.theme-dark #manage-playlist-search-results .dropdown-item {
    color: #f8fafc;
}
[data-bs-theme="dark"] #manage-playlist-search-results .dropdown-item:not(:last-child),
body.dark-mode #manage-playlist-search-results .dropdown-item:not(:last-child),
body.theme-dark #manage-playlist-search-results .dropdown-item:not(:last-child) {
    border-bottom-color: rgba(255, 255, 255, 0.08);
}
[data-bs-theme="dark"] #manage-playlist-search-results .dropdown-item:hover,
[data-bs-theme="dark"] #manage-playlist-search-results .dropdown-item:focus,
body.dark-mode #manage-playlist-search-results .dropdown-item:hover,
body.dark-mode #manage-playlist-search-results .dropdown-item:focus,
body.theme-dark #manage-playlist-search-results .dropdown-item:hover,
body.theme-dark #manage-playlist-search-results .dropdown-item:focus {
    background: rgba(106, 174, 214, 0.16);
}
[data-bs-theme="dark"] #manage-playlist-search-results .dropdown-item .text-dark,
body.dark-mode #manage-playlist-search-results .dropdown-item .text-dark,
body.theme-dark #manage-playlist-search-results .dropdown-item .text-dark {
    color: #f8fafc !important;
}
</style>
@endsection

@section('script')
<script>
document.addEventListener('DOMContentLoaded', function () {
    // Autocomplete is handled by standardized initManageAutocomplete
});
</script>
@endsection
