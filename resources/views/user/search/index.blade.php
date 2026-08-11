@extends('layouts.sidebar-master')

@section('style')
<style>
/* ── Search Modern UI ─────────────────────────────────────────── */
.search-modern-sidebar .list-group-item {
    border-left: 3px solid transparent;
    padding: .625rem .875rem;
    transition: border-color .2s, background-color .2s;
}
.search-modern-sidebar .list-group-item.active {
    border-left-color: var(--tblr-primary);
    background-color: var(--tblr-primary-lt);
    color: var(--tblr-primary);
    font-weight: 600;
}
[data-bs-theme="dark"] .search-modern-sidebar .list-group-item.active {
    background-color: rgba(var(--tblr-primary-rgb), .2);
}
.search-modern-sidebar .list-group-item:not(.active):hover {
    background-color: var(--tblr-bg-surface-secondary);
}

.search-modern-card {
    transition: transform .15s, box-shadow .15s;
    overflow: hidden;
    border: 1px solid var(--tblr-border-color);
}
.search-modern-card:hover {
    transform: translateY(-2px);
    box-shadow: 0 8px 24px rgba(0, 0, 0, .1);
}
[data-bs-theme="dark"] .search-modern-card:hover {
    box-shadow: 0 8px 24px rgba(0, 0, 0, .4);
}

.search-modern-thumb-wrap {
    position: relative;
    overflow: hidden;
}
.search-modern-thumb {
    width: 100%;
    height: 160px;
    object-fit: cover;
    object-position: center;
    display: block;
}
.search-modern-placeholder {
    width: 100%;
    height: 160px;
    display: flex;
    align-items: center;
    justify-content: center;
    background-color: var(--tblr-bg-surface-secondary);
    color: var(--tblr-secondary);
    font-size: 2.5rem;
}
[data-bs-theme="dark"] .search-modern-placeholder {
    background-color: var(--tblr-dark-mode-bg, #1e2433);
    color: var(--tblr-muted);
}

.search-modern-badge-type {
    position: absolute;
    top: .5rem;
    right: .5rem;
    z-index: 1;
    pointer-events: none;
}

.search-modern-title {
    font-weight: 600;
    font-size: .875rem;
    line-height: 1.4;
    display: -webkit-box;
    -webkit-line-clamp: 2;
    -webkit-box-orient: vertical;
    overflow: hidden;
    color: var(--tblr-body-color);
}

.search-modern-empty {
    text-align: center;
    padding: 4rem 1rem;
    color: var(--tblr-muted);
}
.search-modern-empty i {
    font-size: 3.5rem;
    opacity: .4;
}

.search-modern-meta {
    font-size: .78rem;
    color: var(--tblr-muted);
}
</style>
@endsection

@section('content')
@php
    $keyword    = $keyword    ?? request('s', '');
    $searchtype = $searchtype ?? 'all';
    $totalAll   = $totalcontent + $totalnews + $totaleventphoto + $totalplaylist;
@endphp

<!-- Page header -->
<div class="page-header d-print-none">
    <div class="container-xl">
        <div class="row g-2 align-items-center">
            <div class="col">
                <h2 class="page-title">Search Results</h2>
                @if ($keyword)
                    <div class="text-muted mt-1">
                        Results for <strong class="text-body">"{{ $keyword }}"</strong>
                        &mdash; {{ $totalAll }} item(s) found
                    </div>
                @else
                    <div class="text-muted mt-1">{{ $totalAll }} item(s) found</div>
                @endif
            </div>
        </div>
    </div>
</div>

<!-- Page body -->
<div class="page-body">
    <div class="container-xl">
        <div class="row g-4">

            {{-- ── LEFT: Filter sidebar ─────────────────────────────── --}}
            <div class="col-12 col-md-3">
                <div class="card search-modern-sidebar mb-3">
                    <div class="card-header">
                        <h3 class="card-title">
                            <i class="ti ti-filter me-1"></i>Filter by Type
                        </h3>
                    </div>
                    <div class="list-group list-group-flush">
                        <a href="{{ route('search', 'all') }}?s={{ urlencode($keyword) }}"
                           class="list-group-item list-group-item-action d-flex align-items-center
                                  {{ $searchtype === 'all' ? 'active' : '' }}">
                            <i class="ti ti-layout-grid me-2"></i>
                            <span>All</span>
                            <span class="badge bg-secondary ms-auto">{{ $totalAll }}</span>
                        </a>
                        <a href="{{ route('search', 'content') }}?s={{ urlencode($keyword) }}"
                           class="list-group-item list-group-item-action d-flex align-items-center
                                  {{ $searchtype === 'content' ? 'active' : '' }}">
                            <i class="ti ti-file-text me-2 text-cyan"></i>
                            <span>Content</span>
                            <span class="badge bg-cyan-lt text-cyan ms-auto">{{ $totalcontent }}</span>
                        </a>
                        <a href="{{ route('search', 'news') }}?s={{ urlencode($keyword) }}"
                           class="list-group-item list-group-item-action d-flex align-items-center
                                  {{ $searchtype === 'news' ? 'active' : '' }}">
                            <i class="ti ti-news me-2 text-lime"></i>
                            <span>News</span>
                            <span class="badge bg-lime-lt text-lime ms-auto">{{ $totalnews }}</span>
                        </a>
                        <a href="{{ route('search', 'eventphoto') }}?s={{ urlencode($keyword) }}"
                           class="list-group-item list-group-item-action d-flex align-items-center
                                  {{ $searchtype === 'eventphoto' ? 'active' : '' }}">
                            <i class="ti ti-photo me-2 text-orange"></i>
                            <span>Gallery</span>
                            <span class="badge bg-orange-lt text-orange ms-auto">{{ $totaleventphoto }}</span>
                        </a>
                        <a href="{{ route('search', 'playlist') }}?s={{ urlencode($keyword) }}"
                           class="list-group-item list-group-item-action d-flex align-items-center
                                  {{ $searchtype === 'playlist' ? 'active' : '' }}">
                            <i class="ti ti-playlist me-2 text-purple"></i>
                            <span>Playlist</span>
                            <span class="badge bg-purple-lt text-purple ms-auto">{{ $totalplaylist }}</span>
                        </a>
                    </div>
                </div>
            </div>

            {{-- ── RIGHT: Results ───────────────────────────────────── --}}
            <div class="col-12 col-md-9">

                {{-- Search box --}}
                <div class="card mb-3">
                    <div class="card-body py-2">
                        <form action="{{ route('search', $searchtype) }}" method="get"
                              autocomplete="off" novalidate>
                            <div class="input-icon">
                                <span class="input-icon-addon">
                                    <i class="ti ti-search"></i>
                                </span>
                                <input type="text" name="s" class="form-control"
                                       placeholder="Search…" value="{{ $keyword }}">
                            </div>
                        </form>
                    </div>
                </div>

                {{-- Results label --}}
                <div class="d-flex align-items-center justify-content-between mb-3">
                    <h3 class="mb-0">
                        @if ($searchtype === 'all')      All Results
                        @elseif ($searchtype === 'content')    Content
                        @elseif ($searchtype === 'news')       News
                        @elseif ($searchtype === 'eventphoto') Gallery
                        @elseif ($searchtype === 'playlist')   Playlist
                        @endif
                    </h3>
                    <span class="text-muted small">{{ $result->count() }} result(s)</span>
                </div>

                {{-- Empty state --}}
                @if ($result->isEmpty())
                    <div class="card">
                        <div class="card-body search-modern-empty">
                            <i class="ti ti-search-off d-block mb-2"></i>
                            <p class="mb-0 fs-4">No results found</p>
                            @if ($keyword)
                                <p class="text-muted small mt-1">
                                    Nothing matched <em>"{{ e($keyword) }}"</em>.
                                    Try a different keyword.
                                </p>
                            @endif
                        </div>
                    </div>

                {{-- Result grid --}}
                @else
                    <div class="row row-cards g-3">
                        @foreach ($result as $list)
                            @php
                                $datediff = round((time() - strtotime($list->created_at)) / 86400);
                                if ($datediff === 0) {
                                    $posted = 'Today';
                                } elseif ($datediff === 1) {
                                    $posted = 'Yesterday';
                                } else {
                                    $posted = $datediff . ' days ago';
                                }

                                // Playlist helpers
                                $firstDetail  = null;
                                $firstContent = null;
                                $moduleCount  = 0;
                                $playlistUrl  = '#';
                                if ($list->searchtype === 'playlist') {
                                    $firstDetail  = $list->trainingplandetail->first();
                                    $firstContent = $firstDetail ? $firstDetail->content : null;
                                    $moduleCount  = $list->trainingplandetail->count();
                                    if ($firstContent && $firstContent->slug) {
                                        $playlistUrl = route('playlist.user.view', [$list->slug, $firstContent->slug]);
                                    }
                                }
                            @endphp

                            <div class="col-12 col-sm-6 col-lg-4">
                                <div class="card search-modern-card h-100">

                                    {{-- ── Thumbnail ── --}}
                                    <div class="search-modern-thumb-wrap">

                                        @if ($list->searchtype === 'eventphoto')
                                            <span class="badge bg-orange search-modern-badge-type">
                                                {{ $list->searchlabel }}
                                            </span>
                                            <a href="{{ route('gallery.user.view', $list->slug) }}">
                                                @if ($list->thumbnail)
                                                    <img src="{{ asset('storage/gallery/photo/' . $list->id . '/' . $list->thumbnail) }}"
                                                         class="search-modern-thumb"
                                                         onerror="this.style.display='none';this.nextElementSibling.style.display='flex'">
                                                    <div class="search-modern-placeholder" style="display:none">
                                                        <i class="ti ti-photo"></i>
                                                    </div>
                                                @else
                                                    <div class="search-modern-placeholder">
                                                        <i class="ti ti-photo"></i>
                                                    </div>
                                                @endif
                                            </a>

                                        @elseif ($list->searchtype === 'news')
                                            <span class="badge bg-lime search-modern-badge-type">
                                                {{ $list->searchlabel }}
                                            </span>
                                            <a href="{{ route('news.user.view', $list->slug) }}">
                                                @if ($list->image)
                                                    <img src="{{ asset('storage/news/' . $list->image) }}"
                                                         class="search-modern-thumb"
                                                         onerror="this.style.display='none';this.nextElementSibling.style.display='flex'">
                                                    <div class="search-modern-placeholder" style="display:none">
                                                        <i class="ti ti-news"></i>
                                                    </div>
                                                @else
                                                    <div class="search-modern-placeholder">
                                                        <i class="ti ti-news"></i>
                                                    </div>
                                                @endif
                                            </a>

                                        @elseif ($list->searchtype === 'content')
                                            <span class="badge bg-cyan search-modern-badge-type">
                                                {{ $list->searchlabel }}
                                            </span>
                                            <a href="{{ route('content.user.view', $list->slug) }}">
                                                @if ($list->type === 'YouTube' && $list->link)
                                                    <img src="https://img.youtube.com/vi/{{ $list->link }}/mqdefault.jpg"
                                                         class="search-modern-thumb"
                                                         onerror="this.src='{{ asset('default-video.jpg') }}'">
                                                @else
                                                    @php $thumbPath = 'storage/files/content/' . $list->id . '/thumb.jpg'; @endphp
                                                    @if (\File::exists(public_path($thumbPath)))
                                                        <img src="{{ asset($thumbPath) }}"
                                                             class="search-modern-thumb"
                                                             onerror="this.src='{{ asset('default-video.jpg') }}'">
                                                    @else
                                                        <img src="{{ asset('default-video.jpg') }}"
                                                             class="search-modern-thumb">
                                                    @endif
                                                @endif
                                            </a>

                                        @elseif ($list->searchtype === 'playlist')
                                            <span class="badge bg-purple search-modern-badge-type">
                                                {{ $list->searchlabel }}
                                            </span>
                                            <a href="{{ $playlistUrl }}">
                                                @if ($firstContent && $firstContent->type === 'YouTube' && $firstContent->link)
                                                    <img src="https://img.youtube.com/vi/{{ $firstContent->link }}/mqdefault.jpg"
                                                         class="search-modern-thumb"
                                                         onerror="this.src='{{ asset('default-video.jpg') }}'">
                                                @elseif ($firstContent)
                                                    @php $pThumb = 'storage/files/content/' . $firstContent->id . '/thumb.jpg'; @endphp
                                                    @if (\File::exists(public_path($pThumb)))
                                                        <img src="{{ asset($pThumb) }}"
                                                             class="search-modern-thumb"
                                                             onerror="this.src='{{ asset('default-video.jpg') }}'">
                                                    @else
                                                        <img src="{{ asset('default-video.jpg') }}"
                                                             class="search-modern-thumb">
                                                    @endif
                                                @else
                                                    <div class="search-modern-placeholder">
                                                        <i class="ti ti-playlist"></i>
                                                    </div>
                                                @endif
                                            </a>
                                        @endif

                                    </div>{{-- /thumb-wrap --}}

                                    {{-- ── Card body ── --}}
                                    <div class="card-body d-flex flex-column p-3">
                                        <div class="search-modern-title mb-2">{{ $list->title }}</div>

                                        <div class="search-modern-meta mb-3">
                                            <i class="ti ti-clock me-1"></i>{{ $posted }}
                                            @if ($list->searchtype === 'playlist')
                                                &nbsp;&bull;&nbsp;
                                                <i class="ti ti-stack-2 me-1"></i>{{ $moduleCount }} module(s)
                                            @endif
                                        </div>

                                        <div class="mt-auto">
                                            @if ($list->searchtype === 'eventphoto')
                                                <a href="{{ route('gallery.user.view', $list->slug) }}"
                                                   class="btn btn-sm btn-outline-secondary w-100">
                                                    <i class="ti ti-eye me-1"></i>View Detail
                                                </a>
                                            @elseif ($list->searchtype === 'news')
                                                <a href="{{ route('news.user.view', $list->slug) }}"
                                                   class="btn btn-sm btn-outline-secondary w-100">
                                                    <i class="ti ti-eye me-1"></i>View Detail
                                                </a>
                                            @elseif ($list->searchtype === 'content')
                                                <a href="{{ route('content.user.view', $list->slug) }}"
                                                   class="btn btn-sm btn-outline-secondary w-100">
                                                    <i class="ti ti-eye me-1"></i>View Detail
                                                </a>
                                            @elseif ($list->searchtype === 'playlist')
                                                <a href="{{ $playlistUrl }}"
                                                   class="btn btn-sm btn-outline-secondary w-100">
                                                    <i class="ti ti-player-play me-1"></i>View Playlist
                                                </a>
                                            @endif
                                        </div>
                                    </div>

                                </div>{{-- /card --}}
                            </div>{{-- /col --}}

                        @endforeach
                    </div>{{-- /row --}}
                @endif

            </div>{{-- /col-md-9 --}}

        </div>{{-- /row --}}
    </div>
</div>
@endsection
