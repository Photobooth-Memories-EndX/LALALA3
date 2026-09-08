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
            <div class="card border-0 shadow-sm p-4 mb-3 rounded-3 content-hero-card">
                <div class="d-flex flex-column flex-md-row align-items-md-center justify-content-between gap-3">
                    <div class="d-flex align-items-center gap-3">
                        <div class="content-hero-icon rounded-3 d-flex align-items-center justify-content-center flex-shrink-0">
                            <i class="ti ti-books" style="font-size: 2rem !important;"></i>
                        </div>
                        <div>
                            <h2 class="h3 fw-bold mb-1 text-dark">Content Management</h2>
                            <p class="text-muted small mb-0">Manage learning materials, videos, documents, assessments, and playlist content.</p>
                        </div>
                    </div>
                    <div class="flex-shrink-0 d-flex flex-wrap align-items-center gap-2">
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
                <div class="col-6 col-md-3">
                    <div class="card border-0 shadow-sm p-3 rounded-3 content-stat-card">
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
                <div class="col-6 col-md-3">
                    <div class="card border-0 shadow-sm p-3 rounded-3 content-stat-card">
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
                <div class="col-6 col-md-3">
                    <div class="card border-0 shadow-sm p-3 rounded-3 content-stat-card">
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
                <div class="col-6 col-md-3">
                    <div class="card border-0 shadow-sm p-3 rounded-3 content-stat-card">
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
                        {{-- Preserve active More Filter parameters upon Quick Filter submission --}}
                        @if(request()->filled('min_learning_hour'))
                            <input type="hidden" name="min_learning_hour" value="{{ request('min_learning_hour') }}">
                        @endif
                        @if(request()->filled('max_learning_hour'))
                            <input type="hidden" name="max_learning_hour" value="{{ request('max_learning_hour') }}">
                        @endif
                        @if(request()->filled('learning_hour'))
                            <input type="hidden" name="learning_hour" value="{{ request('learning_hour') }}">
                        @endif
                        @if(request()->filled('duration'))
                            <input type="hidden" name="duration" value="{{ request('duration') }}">
                        @endif
                        @if(request()->filled('author'))
                            <input type="hidden" name="author" value="{{ request('author') }}">
                        @endif
                        @if(request()->filled('sort'))
                            <input type="hidden" name="sort" value="{{ request('sort') }}">
                        @endif
                        @if(request()->filled('view_order') || request()->filled('sort_views'))
                            <input type="hidden" name="view_order" value="{{ request('view_order') ?? request('sort_views') }}">
                        @endif
                        @if(request()->filled('created_from_date') || request()->filled('created_from'))
                            <input type="hidden" name="created_from_date" value="{{ request('created_from_date') ?? request('created_from') }}">
                        @endif
                        @if(request()->filled('created_to_date') || request()->filled('created_to'))
                            <input type="hidden" name="created_to_date" value="{{ request('created_to_date') ?? request('created_to') }}">
                        @endif
                        @if(request()->filled('deadline_from_date') || request()->filled('period_from') || request()->filled('date_from'))
                            <input type="hidden" name="deadline_from_date" value="{{ request('deadline_from_date') ?? (request('period_from') ?? request('date_from')) }}">
                        @endif
                        @if(request()->filled('deadline_to_date') || request()->filled('period_to') || request()->filled('date_to'))
                            <input type="hidden" name="deadline_to_date" value="{{ request('deadline_to_date') ?? (request('period_to') ?? request('date_to')) }}">
                        @endif
                        @if(request()->filled('deadline_status') || request()->filled('deadline'))
                            <input type="hidden" name="deadline_status" value="{{ request('deadline_status') ?? request('deadline') }}">
                        @endif
                        @if(request()->filled('exam'))
                            <input type="hidden" name="exam" value="{{ request('exam') }}">
                        @endif
                        @if(request()->filled('playlistonly') || request()->filled('playlist'))
                            <input type="hidden" name="playlistonly" value="{{ request('playlistonly') ?? request('playlist') }}">
                        @endif

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
                                         style="display: none; z-index: 2000;">
                                        <!-- Container 1: Scrollable List Area (Max 280px height with vertical scrollbar) -->
                                        <div id="manage-content-autocomplete-list" class="list-group list-group-flush" 
                                             style="max-height: 280px; overflow-y: auto;"></div>
                                        
                                        <!-- Container 2: Fixed/Sticky Bottom Footer -->
                                        <div id="manage-content-search-footer" class="border-top p-2 text-center" 
                                             style="display: none; position: sticky; bottom: 0; z-index: 2005;"></div>
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
                                    @if(request('duration') || request('learning_hour') || request('min_learning_hour') || request('max_learning_hour') || request('has_exam') || request('date_from') || request('date_to') || request('author') || request('category'))
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
                                        <th>Learning Hour</th>
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
                                            <td>
                                                @if ($list->learning_hours !== null)
                                                    <span class="badge bg-blue-lt font-monospace text-nowrap" style="letter-spacing: 0.02em;">
                                                        <i class="ti ti-clock me-1 opacity-75"></i>{{ number_format((float) $list->learning_hours, 4, '.', '') }} h
                                                    </span>
                                                @else
                                                    <span class="text-muted small">-</span>
                                                @endif
                                            </td>
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
                                                    <button type="button"
                                                        class="btn btn-sm btn-outline-primary owl-action-btn btn-view-content-detail"
                                                        data-id="{{ $list->id }}"
                                                        title="View Detail"
                                                        aria-label="View content details">
                                                        <i class="ti ti-eye"></i>
                                                    </button>
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
                        <input type="hidden" name="q" value="{{ request('q') ?? (request('s') ?? request('search')) }}">
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
                                    <option value="lh_asc" {{ (request('sort') == 'lh_asc' || request('sort') == 'learning_hours_asc') ? 'selected' : '' }}>Learning Hours (Lowest)</option>
                                    <option value="lh_desc" {{ (request('sort') == 'lh_desc' || request('sort') == 'learning_hours_desc') ? 'selected' : '' }}>Learning Hours (Highest)</option>
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

                        <!-- Learning Hour Filter (Numeric Range & Presets) -->
                        <div class="mb-3">
                            <label class="form-label fw-bold text-dark small d-block">
                                <i class="ti ti-clock me-1 text-primary"></i> Learning Hour
                            </label>

                            <!-- Numeric Range Min & Max -->
                            <div class="row g-2 mb-2">
                                <div class="col-6">
                                    <label class="form-label small text-muted mb-1">Min Learning Hour (h)</label>
                                    <input type="number" step="0.0001" min="0" name="min_learning_hour" class="form-control rounded-3" placeholder="e.g. 0.2500" value="{{ request('min_learning_hour') }}">
                                </div>
                                <div class="col-6">
                                    <label class="form-label small text-muted mb-1">Max Learning Hour (h)</label>
                                    <input type="number" step="0.0001" min="0" name="max_learning_hour" class="form-control rounded-3" placeholder="e.g. 1.5000" value="{{ request('max_learning_hour') }}">
                                </div>
                            </div>

                            <!-- Preset Radios -->
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
                                    <label class="form-check-label text-dark" for="contentLh3">1 - 3 Hours</label>
                                </div>
                                <div class="form-check">
                                    <input class="form-check-input" type="radio" name="learning_hour" id="contentLhAbove3" value="gt3h" {{ $currLH === 'gt3h' ? 'checked' : '' }}>
                                    <label class="form-check-label text-dark" for="contentLhAbove3">&gt; 3 Hours</label>
                                </div>
                                <div class="form-check">
                                    <input class="form-check-input" type="radio" name="learning_hour" id="contentLhMissing" value="missing_lh" {{ $currLH === 'missing_lh' ? 'checked' : '' }}>
                                    <label class="form-check-label text-dark" for="contentLhMissing">Missing LH</label>
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

    <!-- Content Detail Modal -->
    <div class="modal modal-blur fade" id="contentDetailModal" tabindex="-1" aria-labelledby="contentDetailModalTitle" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-lg">
            <div class="modal-content rounded-4 border-0 shadow">
                <div class="modal-header border-0 pb-0">
                    <div class="d-flex align-items-center gap-2 flex-grow-1 min-w-0">
                        <div class="avatar avatar-md bg-primary-lt text-primary rounded-3 flex-shrink-0">
                            <i class="ti ti-file-description fs-2"></i>
                        </div>
                        <div class="min-w-0 flex-grow-1">
                            <h5 class="modal-title fw-bold text-dark mb-0 text-truncate" id="contentDetailModalTitle">Content Details</h5>
                            <small class="text-muted" id="contentDetailModalSubtitle">View comprehensive information for this content</small>
                        </div>
                    </div>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>

                <!-- Loading State -->
                <div class="modal-body py-5 text-center" id="contentDetailLoading">
                    <div class="spinner-border text-primary mb-3" style="width: 3rem; height: 3rem;" role="status">
                        <span class="visually-hidden">Loading...</span>
                    </div>
                    <h5 class="fw-bold mb-1">Loading Content Details...</h5>
                    <p class="text-muted small mb-0">Please wait while we retrieve the content information.</p>
                </div>

                <!-- Error State -->
                <div class="modal-body py-4" id="contentDetailError" style="display: none;">
                    <div class="alert alert-danger d-flex align-items-center gap-2 mb-0" id="detailError">
                        <i class="ti ti-alert-circle fs-2 flex-shrink-0"></i>
                        <div class="flex-grow-1">
                            <strong class="d-block" id="contentDetailErrorTitle">Failed to load content details</strong>
                            <span class="small" id="contentDetailErrorMessage">Unable to load content details. Please try again.</span>
                        </div>
                        <button type="button" class="btn btn-sm btn-danger px-3 ms-auto d-inline-flex align-items-center gap-1" id="btnRetryContentDetail">
                            <i class="ti ti-refresh me-1" id="btnRetrySpinner"></i>
                            <span id="btnRetryText">Retry</span>
                        </button>
                    </div>
                </div>

                <!-- Content Data Body -->
                <div class="modal-body py-3" id="contentDetailContent" data-detail-content style="display: none;">
                    <!-- Title & Badge Header Section -->
                    <div class="mb-3">
                        <h4 class="fw-bold text-dark mb-2" id="detailTitle">-</h4>
                        <div class="d-flex flex-wrap align-items-center gap-2">
                            <span class="badge bg-blue-lt" id="detailTypeBadge">-</span>
                            <span class="badge bg-success-lt" id="detailStatusBadge">-</span>
                            <span class="badge bg-secondary-lt" id="detailClassificationBadge">-</span>
                            <span class="badge bg-azure-lt" id="detailViewsBadge"><i class="ti ti-eye me-1"></i><span id="detailViews">0</span> Views</span>
                        </div>
                    </div>

                    <!-- Learning Information Featured Card -->
                    <div class="card border-0 bg-light rounded-3 p-3 mb-3">
                        <div class="fw-semibold text-dark small mb-2 d-flex align-items-center gap-1">
                            <i class="ti ti-clock-check text-primary me-1"></i> Learning Information
                        </div>
                        <div class="row g-3">
                            <div class="col-sm-6">
                                <div class="p-2 border rounded-3 bg-white text-center h-100">
                                    <div class="text-muted small mb-1">Duration</div>
                                    <div class="h3 fw-bold text-dark mb-0 font-monospace" id="detailDuration">00:00:00</div>
                                </div>
                            </div>
                            <div class="col-sm-6">
                                <div class="p-2 border rounded-3 bg-white text-center h-100">
                                    <div class="text-muted small mb-1">Learning Hour</div>
                                    <div class="h3 fw-bold text-primary mb-0 font-monospace" id="detailLearningHour">0.0000 h</div>
                                </div>
                            </div>
                        </div>
                        <div class="small text-muted text-center mt-2">
                            <i class="ti ti-info-circle me-1"></i>Actual learning hours based on content duration.
                        </div>
                    </div>

                    <!-- Document Information Section (Conditional) -->
                    <div class="card border-0 bg-light rounded-3 p-3 mb-3" id="detailDocumentSection" style="display: none;">
                        <div class="fw-semibold text-dark small mb-2 d-flex align-items-center gap-1">
                            <i class="ti ti-files text-primary me-1"></i> Document Information
                        </div>
                        <div class="row g-2">
                            <div class="col-6">
                                <div class="small text-muted">Total Pages:</div>
                                <div class="fw-bold text-dark" id="detailTotalPage">0</div>
                            </div>
                            <div class="col-6">
                                <div class="small text-muted">Page Duration:</div>
                                <div class="fw-bold text-dark" id="detailPageDuration">0 seconds</div>
                            </div>
                        </div>
                    </div>

                    <!-- Content Details & Metadata -->
                    <div class="row g-3 mb-3">
                        <!-- Description -->
                        <div class="col-12" id="detailDescriptionContainer">
                            <label class="form-label small fw-semibold text-muted mb-1">Description</label>
                            <div class="p-3 border rounded-3 bg-light text-dark small" id="detailDescription" style="max-height: 120px; overflow-y: auto; white-space: pre-wrap;">-</div>
                        </div>

                        <!-- Author & Date -->
                        <div class="col-sm-6">
                            <div class="border rounded-3 p-2 bg-light">
                                <span class="small text-muted d-block">Author</span>
                                <strong class="text-dark small" id="detailAuthor">-</strong>
                            </div>
                        </div>
                        <div class="col-sm-6">
                            <div class="border rounded-3 p-2 bg-light">
                                <span class="small text-muted d-block">Content Date</span>
                                <strong class="text-dark small" id="detailDate">-</strong>
                            </div>
                        </div>

                        <!-- Exam & Playlist Status -->
                        <div class="col-sm-6">
                            <div class="border rounded-3 p-2 bg-light">
                                <span class="small text-muted d-block">Assessment / Exam</span>
                                <strong class="text-dark small" id="detailExam">-</strong>
                            </div>
                        </div>
                        <div class="col-sm-6">
                            <div class="border rounded-3 p-2 bg-light">
                                <span class="small text-muted d-block">Playlist Status</span>
                                <strong class="text-dark small" id="detailPlaylist">-</strong>
                            </div>
                        </div>

                        <!-- Deadline Information -->
                        <div class="col-12" id="detailDeadlineContainer">
                            <div class="border rounded-3 p-2 bg-light">
                                <span class="small text-muted d-block">Deadline</span>
                                <strong class="text-dark small" id="detailDeadline">No Deadline</strong>
                            </div>
                        </div>

                        <!-- Associated Playlists -->
                        <div class="col-12" id="detailAssociatedPlaylistsContainer" style="display: none;">
                            <label class="form-label small fw-semibold text-muted mb-1">Included in Playlists</label>
                            <div class="d-flex flex-wrap gap-1" id="detailPlaylistsList"></div>
                        </div>
                    </div>
                </div>

                <div class="modal-footer bg-light d-flex justify-content-between align-items-center border-0">
                    <a href="#" class="btn btn-primary d-inline-flex align-items-center gap-1" id="btnEditContentShortcut">
                        <i class="ti ti-edit me-1"></i> Edit Content
                    </a>
                    <button type="button" class="btn btn-outline-secondary px-4" data-bs-dismiss="modal">
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

/* Content Hero Card & Icon */
.content-hero-card {
    background-color: #ffffff;
    border: 1px solid rgba(0, 0, 0, 0.06);
    transition: background-color 0.2s ease, border-color 0.2s ease;
}
.content-hero-icon {
    width: 48px;
    height: 48px;
    border-radius: 12px;
    background: rgba(15, 94, 156, 0.1);
    color: #0f5e9c;
    display: flex;
    align-items: center;
    justify-content: center;
}
[data-bs-theme="dark"] .content-hero-card,
body.dark-mode .content-hero-card,
body.theme-dark .content-hero-card {
    background-color: var(--tblr-bg-surface, #1f2937) !important;
    border-color: rgba(255, 255, 255, 0.08) !important;
}
[data-bs-theme="dark"] .content-hero-icon,
body.dark-mode .content-hero-icon,
body.theme-dark .content-hero-icon {
    background: rgba(106, 174, 214, 0.15) !important;
    color: #6aaed6 !important;
}

/* Content Stat Cards */
.content-stat-card {
    background-color: #ffffff;
    border-radius: 1rem;
    transition: transform 0.2s ease, box-shadow 0.2s ease, background-color 0.2s ease;
}
.content-stat-card:hover {
    transform: translateY(-2px);
    box-shadow: 0 6px 18px rgba(0, 0, 0, 0.08) !important;
}
[data-bs-theme="dark"] .content-stat-card,
body.dark-mode .content-stat-card,
body.theme-dark .content-stat-card {
    background-color: var(--tblr-bg-surface, #1f2937) !important;
    border-color: rgba(255, 255, 255, 0.08) !important;
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
[data-bs-theme="dark"] #contentDetailModal .modal-content,
body.dark-mode #modalSyncLearningHours .modal-content,
body.dark-mode #modalTotalPageSynchronization .modal-content,
body.dark-mode #contentDetailModal .modal-content,
body.theme-dark #modalSyncLearningHours .modal-content,
body.theme-dark #modalTotalPageSynchronization .modal-content,
body.theme-dark #contentDetailModal .modal-content {
    background-color: var(--tblr-bg-surface, #1f2937) !important;
    color: #f8fafc !important;
}
[data-bs-theme="dark"] #modalSyncLearningHours .modal-footer,
[data-bs-theme="dark"] #modalTotalPageSynchronization .modal-footer,
[data-bs-theme="dark"] #contentDetailModal .modal-footer,
body.dark-mode #modalSyncLearningHours .modal-footer,
body.dark-mode #modalTotalPageSynchronization .modal-footer,
body.dark-mode #contentDetailModal .modal-footer,
body.theme-dark #modalSyncLearningHours .modal-footer,
body.theme-dark #modalTotalPageSynchronization .modal-footer,
body.theme-dark #contentDetailModal .modal-footer {
    background-color: var(--tblr-bg-surface-secondary, #111827) !important;
}
[data-bs-theme="dark"] #modalSyncLearningHours .bg-light,
[data-bs-theme="dark"] #modalTotalPageSynchronization .bg-light,
[data-bs-theme="dark"] #modalTotalPageSynchronization .bg-light-subtle,
[data-bs-theme="dark"] #contentDetailModal .bg-light,
[data-bs-theme="dark"] #contentDetailModal .bg-white,
body.dark-mode #modalSyncLearningHours .bg-light,
body.dark-mode #modalTotalPageSynchronization .bg-light,
body.dark-mode #modalTotalPageSynchronization .bg-light-subtle,
body.dark-mode #contentDetailModal .bg-light,
body.dark-mode #contentDetailModal .bg-white,
body.theme-dark #modalSyncLearningHours .bg-light,
body.theme-dark #modalTotalPageSynchronization .bg-light,
body.theme-dark #modalTotalPageSynchronization .bg-light-subtle,
body.theme-dark #contentDetailModal .bg-light,
body.theme-dark #contentDetailModal .bg-white {
    background-color: var(--tblr-bg-surface-secondary, #111827) !important;
    border-color: rgba(255, 255, 255, 0.1) !important;
}
[data-bs-theme="dark"] #modalSyncLearningHours .text-dark,
[data-bs-theme="dark"] #modalTotalPageSynchronization .text-dark,
[data-bs-theme="dark"] #contentDetailModal .text-dark,
body.dark-mode #modalSyncLearningHours .text-dark,
body.dark-mode #modalTotalPageSynchronization .text-dark,
body.dark-mode #contentDetailModal .text-dark,
body.theme-dark #modalSyncLearningHours .text-dark,
body.theme-dark #modalTotalPageSynchronization .text-dark,
body.theme-dark #contentDetailModal .text-dark {
    color: #f8fafc !important;
}
[data-bs-theme="dark"] #modalTotalPageSynchronization .list-group-item,
body.dark-mode #modalTotalPageSynchronization .list-group-item,
body.theme-dark #modalTotalPageSynchronization .list-group-item {
    color: #cbd5e1 !important;
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
    box-shadow: 0 10px 30px rgba(0, 0, 0, 0.15);
    border: 1px solid #e2e8f0;
    background: #ffffff;
    overflow: hidden;
}
#manage-content-autocomplete-list {
    max-height: 280px;
    overflow-y: auto;
    background-color: #ffffff;
    scrollbar-width: thin;
}
#manage-content-search-footer {
    background-color: #f8f9fa;
    border-top: 1px solid rgba(0, 0, 0, 0.06);
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
    background: var(--tblr-bg-surface, #1f2937) !important;
    border-color: rgba(255, 255, 255, 0.12) !important;
    box-shadow: 0 10px 25px rgba(0, 0, 0, 0.4);
}
[data-bs-theme="dark"] #manage-content-autocomplete-list,
body.dark-mode #manage-content-autocomplete-list,
body.theme-dark #manage-content-autocomplete-list {
    background-color: var(--tblr-bg-surface, #1f2937) !important;
}
[data-bs-theme="dark"] #manage-content-search-footer,
body.dark-mode #manage-content-search-footer,
body.theme-dark #manage-content-search-footer {
    background-color: var(--tblr-bg-surface-secondary, #111827) !important;
    border-top-color: rgba(255, 255, 255, 0.1) !important;
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

            // View Content Detail Handler (Zero Native Alert, 100% In-Modal States)
            const contentDetailModalEl = document.getElementById('contentDetailModal');
            let contentDetailModalInstance = null;
            if (contentDetailModalEl && window.bootstrap && window.bootstrap.Modal) {
                contentDetailModalInstance = window.bootstrap.Modal.getOrCreateInstance(contentDetailModalEl);
            }

            const detailLoadingEl = document.getElementById('contentDetailLoading');
            const detailErrorEl = document.getElementById('contentDetailError');
            const detailContentEl = document.getElementById('contentDetailContent');
            const detailErrorMsgEl = document.getElementById('contentDetailErrorMessage');
            const btnRetryDetail = document.getElementById('btnRetryContentDetail');
            const btnRetryText = document.getElementById('btnRetryText');
            const btnRetrySpinner = document.getElementById('btnRetrySpinner');
            let currentDetailId = null;

            function safeVal(val, fallback) {
                if (fallback === undefined) fallback = '-';
                if (val === null || val === undefined) return fallback;
                const str = String(val).trim();
                if (str === '' || str === 'null' || str === 'undefined' || str === 'NaN') return fallback;
                return str;
            }

            function isNumericVal(val) {
                return val !== null && val !== undefined && val !== '' && !isNaN(Number(val));
            }

            function loadContentDetail(contentId) {
                currentDetailId = contentId;
                if (!contentDetailModalInstance && contentDetailModalEl && window.bootstrap && window.bootstrap.Modal) {
                    contentDetailModalInstance = window.bootstrap.Modal.getOrCreateInstance(contentDetailModalEl);
                }
                if (contentDetailModalInstance) {
                    contentDetailModalInstance.show();
                }

                // In-modal loading state: show spinner, hide content and error
                if (detailLoadingEl) detailLoadingEl.style.display = 'block';
                if (detailErrorEl) detailErrorEl.style.display = 'none';
                if (detailContentEl) detailContentEl.style.display = 'none';

                const url = "{{ url('/manage/content') }}/" + encodeURIComponent(contentId) + "/details";

                $.ajax({
                    url: url,
                    type: 'GET',
                    dataType: 'json',
                    headers: {
                        'Accept': 'application/json'
                    }
                }).done(function (res) {
                    if (res && res.success && res.data) {
                        renderContentDetail(res.data);
                        if (detailLoadingEl) detailLoadingEl.style.display = 'none';
                        if (detailErrorEl) detailErrorEl.style.display = 'none';
                        if (detailContentEl) detailContentEl.style.display = 'block';
                    } else {
                        const errMsg = (res && res.message) ? res.message : 'Unable to load content details. Please try again.';
                        showDetailError(errMsg);
                    }
                }).fail(function (xhr, textStatus) {
                    let msg = 'Unable to load content details. Please try again.';
                    if (xhr.status === 403) {
                        msg = 'You do not have permission to view this content.';
                    } else if (xhr.status === 404) {
                        msg = 'Content not found.';
                    } else if (xhr.status === 500) {
                        msg = 'Unable to load content details. Please try again.';
                    } else if (xhr.status === 0 || textStatus === 'timeout' || (!xhr.status && textStatus === 'error')) {
                        msg = 'Unable to connect to the server. Please try again.';
                    } else if (xhr.responseJSON && xhr.responseJSON.message) {
                        msg = xhr.responseJSON.message;
                    }
                    showDetailError(msg);
                }).always(function () {
                    // Reset retry button state
                    if (btnRetryDetail) {
                        btnRetryDetail.disabled = false;
                    }
                    if (btnRetryText) {
                        btnRetryText.textContent = 'Retry';
                    }
                    if (btnRetrySpinner) {
                        btnRetrySpinner.classList.remove('ti-spin');
                    }
                });
            }

            function showDetailError(msg) {
                if (detailLoadingEl) detailLoadingEl.style.display = 'none';
                if (detailContentEl) detailContentEl.style.display = 'none';
                if (detailErrorEl) detailErrorEl.style.display = 'block';
                if (detailErrorMsgEl) detailErrorMsgEl.textContent = msg || 'Unable to load content details. Please try again.';
            }

            // Retry button listener (attached once, zero duplicate listeners)
            if (btnRetryDetail) {
                btnRetryDetail.addEventListener('click', function (e) {
                    e.preventDefault();
                    if (currentDetailId) {
                        btnRetryDetail.disabled = true;
                        if (btnRetryText) btnRetryText.textContent = 'Retrying...';
                        if (btnRetrySpinner) btnRetrySpinner.classList.add('ti-spin');
                        loadContentDetail(currentDetailId);
                    }
                });
            }

            function renderContentDetail(data) {
                if (!data) return;

                const titleEl = document.getElementById('detailTitle');
                const subTitleEl = document.getElementById('contentDetailModalSubtitle');
                const typeBadgeEl = document.getElementById('detailTypeBadge');
                const statusBadgeEl = document.getElementById('detailStatusBadge');
                const classBadgeEl = document.getElementById('detailClassificationBadge');
                const viewsEl = document.getElementById('detailViews');
                const durationEl = document.getElementById('detailDuration');
                const lhEl = document.getElementById('detailLearningHour');
                const descEl = document.getElementById('detailDescription');
                const authorEl = document.getElementById('detailAuthor');
                const dateEl = document.getElementById('detailDate');
                const examEl = document.getElementById('detailExam');
                const playlistEl = document.getElementById('detailPlaylist');
                const deadlineEl = document.getElementById('detailDeadline');
                const docSectionEl = document.getElementById('detailDocumentSection');
                const totalPageEl = document.getElementById('detailTotalPage');
                const pageDurEl = document.getElementById('detailPageDuration');
                const editBtnEl = document.getElementById('btnEditContentShortcut');
                const playlistsContainer = document.getElementById('detailAssociatedPlaylistsContainer');
                const playlistsList = document.getElementById('detailPlaylistsList');

                if (titleEl) titleEl.textContent = safeVal(data.title);
                if (subTitleEl) {
                    const idText = safeVal(data.id, '');
                    const slugText = data.slug ? ' • ' + data.slug : '';
                    subTitleEl.textContent = idText ? ('Content #' + idText + slugText) : 'Content Details';
                }
                if (typeBadgeEl) typeBadgeEl.textContent = safeVal(data.type_label || data.type, 'Content');
                if (statusBadgeEl) {
                    const statusText = safeVal(data.status, 'Inactive');
                    statusBadgeEl.textContent = statusText;
                    statusBadgeEl.className = 'badge ' + (statusText === 'Active' ? 'bg-success-lt' : 'bg-warning-lt');
                }
                if (classBadgeEl) classBadgeEl.textContent = safeVal(data.classification);
                if (viewsEl) viewsEl.textContent = isNumericVal(data.views) ? (data.views_formatted || Number(data.views).toLocaleString()) : '0';
                if (durationEl) durationEl.textContent = safeVal(data.duration_formatted || data.duration, '00:00:00');
                if (lhEl) {
                    if (data.learning_hours !== null && data.learning_hours !== undefined && !isNaN(data.learning_hours)) {
                        lhEl.textContent = data.learning_hours_formatted || (Number(data.learning_hours).toFixed(4) + ' h');
                    } else {
                        lhEl.textContent = '-';
                    }
                }
                if (descEl) descEl.textContent = safeVal(data.description, 'No description provided.');
                if (authorEl) authorEl.textContent = safeVal(data.author);
                if (dateEl) dateEl.textContent = safeVal(data.date);
                if (examEl) {
                    if (data.exam === 'OK') {
                        examEl.textContent = 'With Exam (OK)';
                    } else if (data.exam === 'NOT') {
                        examEl.textContent = 'Without Exam';
                    } else {
                        examEl.textContent = safeVal(data.exam_label);
                    }
                }
                if (playlistEl) {
                    if (data.playlistonly === 'Y') {
                        playlistEl.textContent = 'Playlist Only';
                    } else if (data.playlistonly === 'N') {
                        playlistEl.textContent = 'General Content';
                    } else {
                        playlistEl.textContent = safeVal(data.playlist_label);
                    }
                }

                if (deadlineEl) {
                    if (data.has_deadline && data.from_date && data.to_date && data.from_date !== '-' && data.to_date !== '-') {
                        deadlineEl.textContent = data.from_date + ' – ' + data.to_date + (data.is_expired ? ' (Expired)' : '');
                    } else {
                        deadlineEl.textContent = 'No Deadline';
                    }
                }

                if (docSectionEl) {
                    const isDoc = data.type === 'Document' || (isNumericVal(data.total_page) && Number(data.total_page) > 0) || (isNumericVal(data.page_duration) && Number(data.page_duration) > 0);
                    if (isDoc) {
                        docSectionEl.style.display = 'block';
                        if (totalPageEl) totalPageEl.textContent = (isNumericVal(data.total_page) && Number(data.total_page) > 0) ? (data.total_page + ' pages') : '-';
                        if (pageDurEl) pageDurEl.textContent = (isNumericVal(data.page_duration) && Number(data.page_duration) > 0) ? (data.page_duration + ' seconds / page') : '-';
                    } else {
                        docSectionEl.style.display = 'none';
                    }
                }

                if (playlistsContainer && playlistsList) {
                    if (Array.isArray(data.playlists) && data.playlists.length > 0) {
                        playlistsContainer.style.display = 'block';
                        playlistsList.innerHTML = '';
                        data.playlists.forEach(function (p) {
                            const badge = document.createElement('span');
                            badge.className = 'badge bg-purple-lt';
                            badge.textContent = safeVal(p.title, 'Playlist #' + (p.id || ''));
                            playlistsList.appendChild(badge);
                        });
                    } else {
                        playlistsContainer.style.display = 'none';
                        playlistsList.innerHTML = '';
                    }
                }

                if (editBtnEl) {
                    editBtnEl.href = data.edit_url || (data.slug ? ('{{ url("/manage/content") }}/' + encodeURIComponent(data.slug) + '/edit') : '#');
                }
            }

            // Event delegation for View Detail buttons
            document.addEventListener('click', function (e) {
                const btn = e.target.closest('.btn-view-content-detail');
                if (!btn) return;
                e.preventDefault();
                const contentId = btn.getAttribute('data-id');
                if (contentId) {
                    loadContentDetail(contentId);
                }
            });
        });
    </script>
@endsection
