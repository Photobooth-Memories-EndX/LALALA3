@extends('layouts.sidebar-master')

@section('content')
    @include('main.monitoring.components.style')

    {{-- Page Header --}}
    <div class="page-header d-print-none mb-0 pb-0">
        <div class="container-xl">
            <!-- Back Navigation Link -->
            <div class="mb-3">
                <a href="{{ url('/manage') }}" class="text-decoration-none text-muted small d-inline-flex align-items-center gap-1">
                    <i class="ti ti-arrow-left fs-3"></i><span>Back</span>
                </a>
            </div>

            <!-- Content Management Hero Header Box -->
            <div class="card border-0 shadow-sm p-4 mb-3 rounded-3 bg-white">
                <div class="d-flex flex-column flex-md-row align-items-md-center justify-content-between gap-3">
                    <div class="d-flex align-items-center gap-3">
                        <div class="rounded-3 d-flex align-items-center justify-content-center flex-shrink-0" 
                             style="width: 72px; height: 72px; background-color: #eef2ff; color: #003b6d;">
                            <i class="ti ti-books" style="font-size: 2rem !important;"></i>
                        </div>
                        <div>
                            <h2 class="h3 fw-bold mb-1 text-dark">Content Management</h2>
                            <p class="text-muted small mb-0">Manage learning materials, videos, documents, assessments, and playlist content.</p>
                        </div>
                    </div>
                    <div class="flex-shrink-0 d-flex align-items-center gap-2">
                        <button type="button" class="btn btn-outline-primary px-3 py-2 fw-semibold" data-bs-toggle="modal" data-bs-target="#modalSyncLearningHours" id="btnOpenSyncLhModal">
                            <i class="ti ti-refresh me-1"></i>Sync Learning Hours
                        </button>
                        <button type="button" id="btnSyncPdfTotalPage" class="btn btn-outline-primary px-3 py-2 fw-semibold" data-bs-toggle="modal" data-bs-target="#modalTotalPageSynchronization">
                            <i class="ti ti-file-description me-1"></i>Sync Total Page PDF
                        </button>
                        <a href="{{ route('content.create') }}" class="btn btn-primary px-3 py-2 fw-semibold d-inline-flex align-items-center gap-2" style="background-color: #003b6d; border-color: #003b6d;">
                            <i class="ti ti-plus fs-2"></i><span>Add Content</span>
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="page-body mt-0 pt-0">
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

            @if (session()->has('error'))
                <div class="alert alert-important alert-danger alert-dismissible mb-3" role="alert">
                    <div class="d-flex">
                        <div>
                            <svg xmlns="http://www.w3.org/2000/svg" class="icon alert-icon" width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round">
                                <path stroke="none" d="M0 0h24v24H0z" fill="none"></path>
                                <circle cx="12" cy="12" r="9"></circle>
                                <line x1="12" y1="8" x2="12" y2="12"></line>
                                <line x1="12" y1="16" x2="12.01" y2="16"></line>
                            </svg>
                        </div>
                        <div style="white-space: pre-line;">{{ session('error') }}</div>
                    </div>
                    <a class="btn-close btn-close-white" data-bs-dismiss="alert" aria-label="close"></a>
                </div>
            @endif

            {{-- Stat Cards --}}
            <div class="row g-3 mb-3">
                <!-- Card 1: Total Content -->
                <div class="col-md-3">
                    <div class="card border-0 shadow-sm p-3 rounded-3 bg-white">
                        <div class="d-flex align-items-center">
                            <div class="rounded-3 d-flex align-items-center justify-content-center flex-shrink-0 me-3" 
                                 style="width: 44px; height: 44px; background-color: #0f4c81; color: #ffffff;">
                                <i class="ti ti-books fs-2"></i>
                            </div>
                            <div>
                                <div class="text-muted fw-semibold" style="font-size: 0.8rem; line-height: 1.2;">Total Content</div>
                                <div class="fw-bold fs-3 text-dark mt-1" style="line-height: 1.1;">{{ number_format($totalContent ?? 0) }}</div>
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
                                <div class="fw-bold fs-3 text-dark mt-1" style="line-height: 1.1;">{{ number_format($activeContent ?? 0) }}</div>
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
                                <div class="fw-bold fs-3 text-dark mt-1" style="line-height: 1.1;">{{ number_format($inactiveContent ?? 0) }}</div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Card 4: Playlist Only -->
                <div class="col-md-3">
                    <div class="card border-0 shadow-sm p-3 rounded-3 bg-white">
                        <div class="d-flex align-items-center">
                            <div class="rounded-3 d-flex align-items-center justify-content-center flex-shrink-0 me-3" 
                                 style="width: 44px; height: 44px; background-color: #0284c7; color: #ffffff;">
                                <i class="ti ti-playlist fs-2"></i>
                            </div>
                            <div>
                                <div class="text-muted fw-semibold" style="font-size: 0.8rem; line-height: 1.2;">Playlist Content</div>
                                <div class="fw-bold fs-3 text-dark mt-1" style="line-height: 1.1;">{{ number_format($playlistOnly ?? 0) }}</div>
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
                                <div class="position-relative manage-search-container flex-grow-1">
                                    <div class="input-icon">
                                        <span class="input-icon-addon"><i class="ti ti-search"></i></span>
                                        <input type="text" id="manage-content-search-input" name="q" value="{{ request('q') ?? (request('s') ?? request('search')) }}"
                                            class="form-control" placeholder="Search content..." autocomplete="off">
                                    </div>
                                    
                                    <div id="manage-content-search-results" class="dropdown-menu shadow-sm w-100 position-absolute mt-1 p-0 overflow-hidden" 
                                         style="display: none; z-index: 2000; background-color: #ffffff;">
                                        <!-- Container 1: Scrollable List Area (Max 280px height with vertical scrollbar) -->
                                        <div id="manage-content-autocomplete-list" class="list-group list-group-flush" 
                                             style="max-height: 280px; overflow-y: auto; background-color: #ffffff;"></div>
                                        
                                        <!-- Container 2: Fixed/Sticky Bottom Footer -->
                                        <div id="manage-content-search-footer" class="border-top p-2 text-center" 
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
                                <button type="button" class="btn btn-outline-primary btn-icon px-3 position-relative" data-bs-toggle="modal" data-bs-target="#moreContentFiltersModal" title="More Filters">
                                    <i class="ti ti-adjustments-horizontal fs-2"></i>
                                    @if(request('duration') || request('learning_hour') || request('has_exam') || request('date_from') || request('date_to') || request('author') || request('category'))
                                        <span class="position-absolute top-0 start-100 translate-middle p-1 bg-primary border border-light rounded-circle">
                                            <span class="visually-hidden">Active filter</span>
                                        </span>
                                    @endif
                                </button>

                                <!-- 3. Reset Button -->
                                <a href="{{ route('content.index') }}" class="btn btn-outline-secondary btn-icon px-3" title="Reset Filters">
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
                    <div class="modal-footer bg-light d-flex justify-content-end align-items-center gap-2">
                        <!-- Reset Button (Placed on the left of Apply Filter) -->
                        <a href="{{ route('content.index') }}" class="btn btn-outline-secondary px-3 d-inline-flex align-items-center gap-1">
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

    <!-- Synchronize Learning Hours Modal -->
    <div class="modal modal-blur fade" id="modalSyncLearningHours" tabindex="-1" aria-labelledby="modalSyncLearningHoursLabel" aria-hidden="true" data-bs-backdrop="static">
        <div class="modal-dialog modal-dialog-centered modal-md">
            <div class="modal-content rounded-4 border-0 shadow">
                <div class="modal-header border-0 pb-0">
                    <h5 class="modal-title fw-bold text-dark d-flex align-items-center gap-2" id="modalSyncLearningHoursLabel">
                        <i class="ti ti-refresh text-primary fs-2"></i> Synchronize Learning Hours
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close" id="syncLhCloseHeaderBtn"></button>
                </div>

                <!-- Initial Prompt State -->
                <div class="modal-body py-3" id="syncLhPromptBody">
                    <p class="text-muted small mb-3">
                        This action will recalculate <strong>Learning Hours</strong> for all <strong>Content</strong> and <strong>Playlist</strong> master records using their actual duration.
                    </p>

                    <div class="p-3 rounded-3 bg-light border mb-3">
                        <div class="fw-semibold text-dark small mb-1">
                            <i class="ti ti-calculator text-primary me-1"></i> Actual Calculation Formula:
                        </div>
                        <div class="text-muted font-monospace small mb-2">
                            Actual Hours = Duration in seconds / 3600
                        </div>
                        <div class="small text-secondary">
                            Precision: <strong>4 decimal places</strong> (e.g. <code>00:50:00</code> &rarr; <code>0.8333</code>, <code>01:06:26</code> &rarr; <code>1.1072</code>).
                        </div>
                    </div>

                    <div class="alert alert-info py-2 px-3 small mb-0 d-flex align-items-center gap-2">
                        <i class="ti ti-info-circle fs-3 text-info flex-shrink-0"></i>
                        <span>User tracking and completed actual learning hours will remain intact and safe.</span>
                    </div>
                </div>

                <!-- Loading State -->
                <div class="modal-body py-4 text-center" id="syncLhLoadingBody" style="display: none;">
                    <div class="spinner-border text-primary mb-3" style="width: 3rem; height: 3rem;" role="status">
                        <span class="visually-hidden">Loading...</span>
                    </div>
                    <h5 class="fw-bold mb-1">Synchronizing Learning Hours...</h5>
                    <p class="text-muted small mb-0">Recalculating Content and Playlist actual hours. Please do not refresh or close this window.</p>
                </div>

                <!-- Result Summary State -->
                <div class="modal-body py-3" id="syncLhResultBody" style="display: none;">
                    <div class="text-center mb-3">
                        <span class="avatar avatar-md bg-success-lt text-success rounded-circle mb-2 d-inline-flex align-items-center justify-content-center">
                            <i class="ti ti-check fs-2"></i>
                        </span>
                        <h5 class="fw-bold mb-0 text-success">Synchronization Complete</h5>
                        <p class="text-muted small mb-0" id="syncLhResultMessage">Learning Hours synchronized successfully.</p>
                    </div>

                    <div class="row g-2 mb-3">
                        <div class="col-6">
                            <div class="p-2 border rounded-3 bg-light text-center">
                                <div class="fw-bold text-dark mb-1 small"><i class="ti ti-books me-1"></i>Content</div>
                                <div class="small text-muted">Total: <strong id="resContentTotal" class="text-dark">0</strong></div>
                                <div class="small text-muted">Updated: <strong id="resContentUpdated" class="text-success">0</strong></div>
                                <div class="small text-muted">Skipped: <strong id="resContentSkipped" class="text-secondary">0</strong></div>
                            </div>
                        </div>
                        <div class="col-6">
                            <div class="p-2 border rounded-3 bg-light text-center">
                                <div class="fw-bold text-dark mb-1 small"><i class="ti ti-playlist me-1"></i>Playlist</div>
                                <div class="small text-muted">Total: <strong id="resPlaylistTotal" class="text-dark">0</strong></div>
                                <div class="small text-muted">Updated: <strong id="resPlaylistUpdated" class="text-success">0</strong></div>
                                <div class="small text-muted">Skipped: <strong id="resPlaylistSkipped" class="text-secondary">0</strong></div>
                            </div>
                        </div>
                    </div>

                    <div class="small text-muted text-center" id="syncLhResultNote">
                        All master records have been updated to actual learning hours.
                    </div>
                </div>

                <!-- Error State Alert Container -->
                <div class="px-3 pb-2" id="syncLhErrorContainer" style="display: none;">
                    <div class="alert alert-danger py-2 px-3 small mb-0" id="syncLhErrorText"></div>
                </div>

                <div class="modal-footer bg-light d-flex justify-content-end align-items-center gap-2 border-0">
                    <button type="button" class="btn btn-outline-secondary px-3" data-bs-dismiss="modal" id="btnCancelSyncLh">
                        Cancel
                    </button>
                    <button type="button" class="btn btn-primary px-3 d-inline-flex align-items-center gap-1" id="btnExecuteSyncLh" data-url="{{ route('learning-hours.sync') }}">
                        <i class="ti ti-refresh me-1" id="btnExecuteSyncLhIcon"></i>
                        <span id="btnExecuteSyncLhText">Synchronize Learning Hours</span>
                    </button>
                    <button type="button" class="btn btn-primary px-4" id="btnDoneSyncLh" style="display: none;" onclick="window.location.reload();">
                        Done
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Synchronize Total Pages Modal -->
    <div class="modal modal-blur fade" id="modalTotalPageSynchronization" tabindex="-1" aria-labelledby="modalTotalPageSynchronizationLabel" aria-hidden="true" data-bs-backdrop="static">
        <div class="modal-dialog modal-dialog-centered modal-md">
            <div class="modal-content rounded-4 border-0 shadow">
                <div class="modal-header border-0 pb-0">
                    <h5 class="modal-title fw-bold text-dark d-flex align-items-center gap-2" id="modalTotalPageSynchronizationLabel">
                        <i class="ti ti-files text-primary fs-2"></i>
                        <span id="syncTotalPageModalTitle">Synchronize Total Pages</span>
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close" id="syncTotalPageCloseHeaderBtn"></button>
                </div>

                <!-- Initial Prompt / Confirmation State -->
                <div class="modal-body py-3" id="syncTotalPagePromptBody">
                    <p class="text-muted small mb-3">
                        Synchronize total page information for document content using the available PDF metadata.
                    </p>

                    <div class="p-3 rounded-3 bg-light border mb-3">
                        <div class="fw-semibold text-dark small mb-2 d-flex align-items-center gap-1">
                            <i class="ti ti-info-circle text-primary me-1"></i> Synchronization Guidelines:
                        </div>
                        <ul class="text-muted small mb-0 ps-3">
                            <li class="mb-1">PDF documents will be checked.</li>
                            <li class="mb-1">Existing synchronized values will be preserved when already accurate.</li>
                            <li>Missing values may use the configured default (35 pages).</li>
                        </ul>
                    </div>

                    <div class="alert alert-info py-2 px-3 small mb-0 d-flex align-items-center gap-2">
                        <i class="ti ti-file-description fs-3 text-info flex-shrink-0"></i>
                        <span>Only Document type contents with PDF files will have their pages counted and synchronized.</span>
                    </div>
                </div>

                <!-- Loading State -->
                <div class="modal-body py-4 text-center" id="syncTotalPageLoadingBody" style="display: none;">
                    <div class="spinner-border text-primary mb-3" style="width: 3rem; height: 3rem;" role="status">
                        <span class="visually-hidden">Loading...</span>
                    </div>
                    <h5 class="fw-bold mb-1" id="syncTotalPageLoadingTitle">Synchronizing Document Pages...</h5>
                    <p class="text-muted small mb-3" id="syncTotalPageLoadingText">Reading PDF metadata and synchronizing document pages. Please do not refresh or close this window.</p>

                    <!-- Progress bar -->
                    <div class="progress progress-sm rounded-pill mb-2" style="height: 6px;">
                        <div class="progress-bar bg-primary" id="syncTotalPageProgressBar" role="progressbar" style="width: 0%" aria-valuenow="0" aria-valuemin="0" aria-valuemax="100"></div>
                    </div>
                    <div class="small text-muted" id="syncTotalPageProgressCounter">Preparing...</div>
                </div>

                <!-- Result Summary State -->
                <div class="modal-body py-3" id="syncTotalPageResultBody" style="display: none;">
                    <div class="text-center mb-3">
                        <span class="avatar avatar-md bg-success-lt text-success rounded-circle mb-2 d-inline-flex align-items-center justify-content-center">
                            <i class="ti ti-check fs-2"></i>
                        </span>
                        <h5 class="fw-bold mb-0 text-success">Total Page Synchronization Complete</h5>
                        <p class="text-muted small mb-0" id="syncTotalPageResultMessage">Document pages have been synchronized successfully.</p>
                    </div>

                    <!-- 4 Summary Cards -->
                    <div class="row g-2 mb-3">
                        <!-- Card 1: Total Documents -->
                        <div class="col-6 col-sm-3">
                            <div class="p-2 border rounded-3 bg-light text-center h-100">
                                <div class="text-muted fw-semibold small mb-1" style="font-size: 0.75rem;">
                                    <i class="ti ti-files me-1 text-primary"></i>Total Documents
                                </div>
                                <div class="h3 fw-bold text-dark mb-0" id="resTotalDocuments">0</div>
                            </div>
                        </div>

                        <!-- Card 2: Updated from PDF -->
                        <div class="col-6 col-sm-3">
                            <div class="p-2 border rounded-3 bg-light text-center h-100">
                                <div class="text-muted fw-semibold small mb-1" style="font-size: 0.75rem;">
                                    <i class="ti ti-refresh me-1 text-success"></i>Updated from PDF
                                </div>
                                <div class="h3 fw-bold text-success mb-0" id="resUpdatedPdf">0</div>
                            </div>
                        </div>

                        <!-- Card 3: Already Synchronized -->
                        <div class="col-6 col-sm-3">
                            <div class="p-2 border rounded-3 bg-light text-center h-100">
                                <div class="text-muted fw-semibold small mb-1" style="font-size: 0.75rem;">
                                    <i class="ti ti-check me-1 text-info"></i>Already Synced
                                </div>
                                <div class="h3 fw-bold text-info mb-0" id="resAlreadySynced">0</div>
                            </div>
                        </div>

                        <!-- Card 4: Missing / Defaulted -->
                        <div class="col-6 col-sm-3">
                            <div class="p-2 border rounded-3 bg-light text-center h-100">
                                <div class="text-muted fw-semibold small mb-1" style="font-size: 0.75rem;">
                                    <i class="ti ti-alert-triangle me-1 text-warning"></i>Defaulted (35)
                                </div>
                                <div class="h3 fw-bold text-warning mb-0" id="resMissingDefaulted">0</div>
                            </div>
                        </div>
                    </div>

                    <!-- Collapsible Missing / Defaulted Details -->
                    <div id="syncTotalPageMissingContainer" class="mt-3 pt-2 border-top" style="display: none;">
                        <div class="d-flex justify-content-between align-items-center mb-2">
                            <span class="small fw-semibold text-secondary" id="syncTotalPageMissingHeader">Missing / Defaulted Items (0)</span>
                            <button type="button" class="btn btn-sm btn-outline-secondary px-2 py-1" id="btnToggleMissingDetails" data-bs-toggle="collapse" data-bs-target="#collapseMissingDetails" aria-expanded="false" aria-controls="collapseMissingDetails">
                                <span id="toggleMissingDetailsText">View Details</span>
                                <i class="ti ti-chevron-down ms-1" id="toggleMissingDetailsIcon"></i>
                            </button>
                        </div>
                        <div class="collapse" id="collapseMissingDetails">
                            <div class="border rounded-3 p-2 bg-light-subtle" style="max-height: 200px; overflow-y: auto;">
                                <div class="list-group list-group-flush list-group-hoverable" id="syncTotalPageMissingList"></div>
                                <div class="text-muted small text-center pt-2 pb-1" id="syncTotalPageMissingMoreNote" style="display: none;"></div>
                            </div>
                        </div>
                    </div>

                    <div class="small text-muted text-center mt-2" id="syncTotalPageResultNote">
                        Document page counts have been reviewed and updated based on PDF metadata.
                    </div>
                </div>

                <!-- Error State Alert Container -->
                <div class="px-3 pb-2" id="syncTotalPageErrorContainer" style="display: none;">
                    <div class="alert alert-danger py-2 px-3 small mb-0 d-flex align-items-center gap-2">
                        <i class="ti ti-alert-circle fs-3 flex-shrink-0"></i>
                        <span id="syncTotalPageErrorText">Unable to synchronize total pages. Please try again.</span>
                    </div>
                </div>

                <div class="modal-footer bg-light d-flex justify-content-end align-items-center gap-2 border-0">
                    <button type="button" class="btn btn-outline-secondary px-3" data-bs-dismiss="modal" id="btnCancelSyncTotalPage">
                        Cancel
                    </button>
                    <button type="button" class="btn btn-primary px-3 d-inline-flex align-items-center gap-1" id="btnExecuteSyncTotalPage">
                        <i class="ti ti-refresh me-1" id="btnExecuteSyncTotalPageIcon"></i>
                        <span id="btnExecuteSyncTotalPageText">Synchronize Total Pages</span>
                    </button>
                    <button type="button" class="btn btn-primary px-4" id="btnCloseSyncTotalPage" data-bs-dismiss="modal" style="display: none;">
                        Close
                    </button>
                </div>
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
[data-bs-theme="dark"] #modalSyncLearningHours .modal-content,
[data-bs-theme="dark"] #modalTotalPageSynchronization .modal-content,
body.dark-mode #modalSyncLearningHours .modal-content,
body.dark-mode #modalTotalPageSynchronization .modal-content,
body.theme-dark #modalSyncLearningHours .modal-content,
body.theme-dark #modalTotalPageSynchronization .modal-content {
    background-color: var(--tblr-bg-surface, #1f2937) !important;
    color: #f8fafc !important;
}
[data-bs-theme="dark"] #modalSyncLearningHours .modal-footer,
[data-bs-theme="dark"] #modalTotalPageSynchronization .modal-footer,
body.dark-mode #modalSyncLearningHours .modal-footer,
body.dark-mode #modalTotalPageSynchronization .modal-footer,
body.theme-dark #modalSyncLearningHours .modal-footer,
body.theme-dark #modalTotalPageSynchronization .modal-footer {
    background-color: var(--tblr-bg-surface-secondary, #111827) !important;
}
[data-bs-theme="dark"] #modalSyncLearningHours .bg-light,
[data-bs-theme="dark"] #modalTotalPageSynchronization .bg-light,
[data-bs-theme="dark"] #modalTotalPageSynchronization .bg-light-subtle,
body.dark-mode #modalSyncLearningHours .bg-light,
body.dark-mode #modalTotalPageSynchronization .bg-light,
body.dark-mode #modalTotalPageSynchronization .bg-light-subtle,
body.theme-dark #modalSyncLearningHours .bg-light,
body.theme-dark #modalTotalPageSynchronization .bg-light,
body.theme-dark #modalTotalPageSynchronization .bg-light-subtle {
    background-color: var(--tblr-bg-surface-secondary, #111827) !important;
    border-color: rgba(255, 255, 255, 0.1) !important;
}
[data-bs-theme="dark"] #modalSyncLearningHours .text-dark,
[data-bs-theme="dark"] #modalTotalPageSynchronization .text-dark,
body.dark-mode #modalSyncLearningHours .text-dark,
body.dark-mode #modalTotalPageSynchronization .text-dark,
body.theme-dark #modalSyncLearningHours .text-dark,
body.theme-dark #modalTotalPageSynchronization .text-dark {
    color: #f8fafc !important;
}
[data-bs-theme="dark"] #modalTotalPageSynchronization .list-group-item,
body.dark-mode #modalTotalPageSynchronization .list-group-item,
body.theme-dark #modalTotalPageSynchronization .list-group-item {
    color: #cbd5e1 !important;
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
    z-index: 2000;
    border-radius: 8px;
    box-shadow: 0 10px 30px rgba(0, 0, 0, 0.15) !important;
    border: 1px solid #e2e8f0 !important;
    background: #ffffff !important;
    overflow: hidden !important;
}
#manage-content-autocomplete-list {
    max-height: 280px !important;
    overflow-y: auto !important;
    background-color: #ffffff !important;
    scrollbar-width: thin;
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
                modalId: 'modalTotalPageSynchronization',
                executeBtnId: 'btnExecuteSyncTotalPage',
                cancelBtnId: 'btnCancelSyncTotalPage',
                closeBtnId: 'btnCloseSyncTotalPage',
                headerCloseBtnId: 'syncTotalPageCloseHeaderBtn',
                fetchListUrl: "{{ route('content.document-pdf-list') }}",
                syncUrl: "{{ route('content.sync-total-page') }}",
                csrfToken: "{{ csrf_token() }}",
                workerSrc: "{{ asset('js/pdf.worker.min.js') }}"
            });

            // Autocomplete is handled by standardized initManageAutocomplete

            // Synchronize Learning Hours Handler
            const btnExecuteSyncLh = document.getElementById('btnExecuteSyncLh');
            const syncLhPromptBody = document.getElementById('syncLhPromptBody');
            const syncLhLoadingBody = document.getElementById('syncLhLoadingBody');
            const syncLhResultBody = document.getElementById('syncLhResultBody');
            const syncLhErrorContainer = document.getElementById('syncLhErrorContainer');
            const syncLhErrorText = document.getElementById('syncLhErrorText');
            const btnCancelSyncLh = document.getElementById('btnCancelSyncLh');
            const btnDoneSyncLh = document.getElementById('btnDoneSyncLh');
            const syncLhCloseHeaderBtn = document.getElementById('syncLhCloseHeaderBtn');
            const modalEl = document.getElementById('modalSyncLearningHours');

            if (btnExecuteSyncLh) {
                btnExecuteSyncLh.addEventListener('click', function () {
                    const syncUrl = btnExecuteSyncLh.getAttribute('data-url');
                    if (!syncUrl) return;

                    // Set Loading State
                    btnExecuteSyncLh.disabled = true;
                    btnExecuteSyncLh.innerHTML = '<span class="spinner-border spinner-border-sm me-1" role="status"></span> Syncing Learning Hours...';
                    if (btnCancelSyncLh) btnCancelSyncLh.style.display = 'none';
                    if (syncLhCloseHeaderBtn) syncLhCloseHeaderBtn.style.display = 'none';
                    if (syncLhPromptBody) syncLhPromptBody.style.display = 'none';
                    if (syncLhErrorContainer) syncLhErrorContainer.style.display = 'none';
                    if (syncLhLoadingBody) syncLhLoadingBody.style.display = 'block';

                    $.ajax({
                        url: syncUrl,
                        type: 'POST',
                        dataType: 'json',
                        headers: {
                            'X-CSRF-TOKEN': '{{ csrf_token() }}',
                            'Accept': 'application/json'
                        }
                    }).done(function (res) {
                        if (res && res.success && res.data) {
                            if (syncLhLoadingBody) syncLhLoadingBody.style.display = 'none';
                            if (syncLhResultBody) syncLhResultBody.style.display = 'block';

                            const resContentTotal = document.getElementById('resContentTotal');
                            const resContentUpdated = document.getElementById('resContentUpdated');
                            const resContentSkipped = document.getElementById('resContentSkipped');
                            const resPlaylistTotal = document.getElementById('resPlaylistTotal');
                            const resPlaylistUpdated = document.getElementById('resPlaylistUpdated');
                            const resPlaylistSkipped = document.getElementById('resPlaylistSkipped');

                            if (resContentTotal) resContentTotal.textContent = res.data.content_total || 0;
                            if (resContentUpdated) resContentUpdated.textContent = res.data.content_updated || 0;
                            if (resContentSkipped) resContentSkipped.textContent = res.data.content_skipped || 0;

                            if (resPlaylistTotal) resPlaylistTotal.textContent = res.data.playlist_total || 0;
                            if (resPlaylistUpdated) resPlaylistUpdated.textContent = res.data.playlist_updated || 0;
                            if (resPlaylistSkipped) resPlaylistSkipped.textContent = res.data.playlist_skipped || 0;

                            btnExecuteSyncLh.style.display = 'none';
                            if (btnDoneSyncLh) btnDoneSyncLh.style.display = 'inline-block';
                            if (syncLhCloseHeaderBtn) syncLhCloseHeaderBtn.style.display = 'block';
                        } else {
                            handleSyncError(res ? res.message : 'Unknown synchronization error occurred.');
                        }
                    }).fail(function (xhr) {
                        let errMsg = 'Failed to execute synchronization.';
                        if (xhr.responseJSON && xhr.responseJSON.message) {
                            errMsg = xhr.responseJSON.message;
                        }
                        handleSyncError(errMsg);
                    });

                    function handleSyncError(message) {
                        if (syncLhLoadingBody) syncLhLoadingBody.style.display = 'none';
                        if (syncLhPromptBody) syncLhPromptBody.style.display = 'block';
                        if (syncLhErrorContainer) {
                            syncLhErrorContainer.style.display = 'block';
                            if (syncLhErrorText) syncLhErrorText.textContent = message;
                        }
                        btnExecuteSyncLh.disabled = false;
                        btnExecuteSyncLh.innerHTML = '<i class="ti ti-refresh me-1"></i> Synchronize Learning Hours';
                        if (btnCancelSyncLh) btnCancelSyncLh.style.display = 'inline-block';
                        if (syncLhCloseHeaderBtn) syncLhCloseHeaderBtn.style.display = 'block';
                    }
                });
            }

            if (modalEl) {
                modalEl.addEventListener('hidden.bs.modal', function () {
                    // Reset or reload when modal is closed after completed sync
                    if (btnDoneSyncLh && btnDoneSyncLh.style.display !== 'none') {
                        window.location.reload();
                    }
                });
            }
        });
    </script>
@endsection
