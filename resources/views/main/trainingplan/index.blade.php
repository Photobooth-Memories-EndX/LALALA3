@extends('layouts.sidebar-master')

@section('content')
    @include('main.monitoring.components.style')

    {{-- Page Header --}}
    <div class="page-header d-print-none">
        <div class="container-xl">
            <div class="row align-items-center">
                <div class="col">
                    <div class="d-flex align-items-center gap-3">
                        <div class="avatar avatar-lg bg-primary-lt">
                            <i class="ti ti-playlist fs-2"></i>
                        </div>
                        <div>
                            <h2 class="page-title mb-1">Training Plan Management</h2>
                            <div class="text-secondary">Manage learning playlists, content order, thumbnails, and training deadlines.</div>
                        </div>
                    </div>
                </div>
                <div class="col-auto">
                    <a href="{{ route('trainingplan.create') }}" class="btn btn-primary">
                        <i class="ti ti-plus me-1"></i>Add Training Plan
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
                        <div>{{ session('success') }}</div>
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
                                    <div class="text-secondary text-uppercase fw-bold small mb-2">Total Training Plan</div>
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

            {{-- Filter Card --}}
            <div class="card mb-4">
                <div class="card-header">
                    <h3 class="card-title"><i class="ti ti-filter me-2"></i>Filter & Search</h3>
                </div>
                <div class="card-body">
                    <form method="GET" action="{{ route('trainingplan.index') }}" id="filterForm">
                        <div class="row g-2">
                            <div class="col-md-4 col-lg-3">
                                <label class="form-label small fw-semibold">Search</label>
                                <input type="text" name="s" value="{{ request('s') }}"
                                    class="form-control form-control-sm"
                                    placeholder="Title, slug, URL...">
                            </div>
                            <div class="col-md-3 col-lg-2">
                                <label class="form-label small fw-semibold">Status</label>
                                <select name="active" class="form-select form-select-sm">
                                    <option value="">All Status</option>
                                    <option value="Active" @selected(request('active') == 'Active')>Active</option>
                                    <option value="Inactive" @selected(request('active') == 'Inactive')>Inactive</option>
                                </select>
                            </div>
                            <div class="col-md-3 col-lg-2">
                                <label class="form-label small fw-semibold">Deadline</label>
                                <select name="deadline" class="form-select form-select-sm">
                                    <option value="">All</option>
                                    <option value="with" @selected(request('deadline') == 'with')>With Deadline</option>
                                    <option value="without" @selected(request('deadline') == 'without')>Without Deadline</option>
                                    <option value="active" @selected(request('deadline') == 'active')>Active Deadline</option>
                                    <option value="expired" @selected(request('deadline') == 'expired')>Expired</option>
                                </select>
                            </div>
                            <div class="col-md-3 col-lg-2">
                                <label class="form-label small fw-semibold">Thumbnail</label>
                                <select name="thumbnail" class="form-select form-select-sm">
                                    <option value="">All</option>
                                    <option value="with" @selected(request('thumbnail') == 'with')>With Thumbnail</option>
                                    <option value="without" @selected(request('thumbnail') == 'without')>Without Thumbnail</option>
                                </select>
                            </div>
                            <div class="col-md-3 col-lg-2">
                                <label class="form-label small fw-semibold">Created From</label>
                                <input type="date" name="created_from" value="{{ request('created_from') }}"
                                    class="form-control form-control-sm">
                            </div>
                            <div class="col-md-3 col-lg-2">
                                <label class="form-label small fw-semibold">Created To</label>
                                <input type="date" name="created_to" value="{{ request('created_to') }}"
                                    class="form-control form-control-sm">
                            </div>
                            <div class="col-md-3 col-lg-2">
                                <label class="form-label small fw-semibold">Sort By</label>
                                <select name="sort" class="form-select form-select-sm">
                                    <option value="" @selected(!request('sort'))>Newest</option>
                                    <option value="oldest" @selected(request('sort') == 'oldest')>Oldest</option>
                                    <option value="title" @selected(request('sort') == 'title')>Title A-Z</option>
                                    <option value="materials" @selected(request('sort') == 'materials')>Most Materials</option>
                                </select>
                            </div>
                            <div class="col-12 d-flex gap-2 justify-content-end mt-1">
                                <button type="submit" class="btn btn-primary btn-sm">
                                    <i class="ti ti-search me-1"></i>Apply Filter
                                </button>
                                <a href="{{ route('trainingplan.index') }}" class="btn btn-secondary btn-sm">
                                    <i class="ti ti-x me-1"></i>Reset
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
                            Showing {{ $trainingplans->firstItem() ?? 0 }}–{{ $trainingplans->lastItem() ?? 0 }} of {{ $trainingplans->total() }} training plan(s)
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
                                                    <a href="{{ route('trainingplan.edit', $list->slug) }}"
                                                        class="btn btn-sm btn-info owl-action-btn" title="Edit">
                                                        <i class="ti ti-edit"></i>
                                                    </a>
                                                    <a href="{{ route('trainingplan.order', $list->slug) }}"
                                                        class="btn btn-sm btn-secondary owl-action-btn" title="Order Content">
                                                        <i class="ti ti-arrows-sort"></i>
                                                    </a>
                                                    <form action="{{ route('trainingplan.destroy', $list->slug) }}" method="POST" class="d-inline">
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
                                    <a href="{{ route('trainingplan.create') }}" class="btn btn-primary btn-sm">
                                        <i class="ti ti-plus me-1"></i>Add Training Plan
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

@endsection

@section('style')
<style>
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
</style>
@endsection
