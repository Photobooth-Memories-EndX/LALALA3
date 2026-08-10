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
                            Playlist Only
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

            {{-- Filter Card --}}
            <div class="card mb-4">
                <div class="card-header">
                    <h3 class="card-title"><i class="ti ti-filter me-2"></i>Filter & Search</h3>
                </div>
                <div class="card-body">
                    <form method="GET" action="{{ route('content.index') }}" id="filterForm">
                        <div class="row g-2">
                            <div class="col-md-4 col-lg-3">
                                <label class="form-label small fw-semibold">Search</label>
                                <input type="text" name="s" value="{{ request('s') }}"
                                    class="form-control form-control-sm"
                                    placeholder="Title, description, author...">
                            </div>
                            <div class="col-md-4 col-lg-2">
                                <label class="form-label small fw-semibold">Type</label>
                                <select name="type" class="form-select form-select-sm">
                                    <option value="">All Types</option>
                                    @foreach ($types as $t)
                                        <option value="{{ $t }}" @selected(request('type') == $t)>{{ \App\Support\ContentTypeHelper::label($t) }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-4 col-lg-2">
                                <label class="form-label small fw-semibold">Classification</label>
                                <select name="classification_id" class="form-select form-select-sm">
                                    <option value="">All</option>
                                    @foreach ($classifications as $c)
                                        <option value="{{ $c->id }}" @selected(request('classification_id') == $c->id)>{{ $c->classification_name }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-3 col-lg-2">
                                <label class="form-label small fw-semibold">Status</label>
                                <select name="active" class="form-select form-select-sm">
                                    <option value="">All Status</option>
                                    <option value="Active" @selected(request('active') == 'Active')>Active</option>
                                    <option value="Inactive" @selected(request('active') == 'Inactive')>Inactive</option>
                                </select>
                            </div>
                            <div class="col-md-3 col-lg-1">
                                <label class="form-label small fw-semibold">Exam</label>
                                <select name="exam" class="form-select form-select-sm">
                                    <option value="">All</option>
                                    <option value="OK" @selected(request('exam') == 'OK')>OK</option>
                                    <option value="NOT" @selected(request('exam') == 'NOT')>NOT</option>
                                </select>
                            </div>
                            <div class="col-md-3 col-lg-2">
                                <label class="form-label small fw-semibold">Playlist Only</label>
                                <select name="playlistonly" class="form-select form-select-sm">
                                    <option value="">All</option>
                                    <option value="Y" @selected(request('playlistonly') == 'Y')>Yes</option>
                                    <option value="N" @selected(request('playlistonly') == 'N')>No</option>
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
                                <label class="form-label small fw-semibold">Date From</label>
                                <input type="date" name="date_from" value="{{ request('date_from') }}"
                                    class="form-control form-control-sm">
                            </div>
                            <div class="col-md-3 col-lg-2">
                                <label class="form-label small fw-semibold">Date To</label>
                                <input type="date" name="date_to" value="{{ request('date_to') }}"
                                    class="form-control form-control-sm">
                            </div>
                            <div class="col-md-3 col-lg-2">
                                <label class="form-label small fw-semibold">Sort By</label>
                                <select name="sort" class="form-select form-select-sm">
                                    <option value="" @selected(!request('sort'))>Newest</option>
                                    <option value="oldest" @selected(request('sort') == 'oldest')>Oldest</option>
                                    <option value="views" @selected(request('sort') == 'views')>Most Views</option>
                                    <option value="title" @selected(request('sort') == 'title')>Title A-Z</option>
                                </select>
                            </div>
                            <div class="col-12 d-flex gap-2 justify-content-end mt-1">
                                <button type="submit" class="btn btn-primary btn-sm">
                                    <i class="ti ti-search me-1"></i>Apply Filter
                                </button>
                                <a href="{{ route('content.index') }}" class="btn btn-secondary btn-sm">
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
.content-manage-filter .form-label {
    margin-bottom: 4px;
}
[data-theme="dark"] .card,
body.theme-dark .card {
    background: var(--tblr-bg-surface, #1f2937);
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
        });
    </script>
@endsection
