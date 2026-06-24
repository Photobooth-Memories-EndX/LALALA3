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
</style>
@endsection

@section('content')

<div class="page-header d-print-none">
    <div class="container-xl">
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

        {{-- Table Card --}}
        <div class="card shadow-sm">
            <div class="card-header">
                <h3 class="card-title">List News</h3>
            </div>

            <div class="card-body border-bottom py-3">
                <form method="GET" action="{{ route('news.index') }}" id="newsFilterForm">
                    <div class="row g-2 align-items-center">
                        <div class="col-md-2">
                            <div class="d-flex align-items-center gap-2">
                                <span class="text-nowrap text-secondary small">Show</span>
                                <select name="show" id="newsShow" class="form-select form-select-sm"
                                        onchange="document.getElementById('newsFilterForm').submit()">
                                    <option value="10"  {{ ($show ?? 25) == 10  ? 'selected' : '' }}>10</option>
                                    <option value="25"  {{ ($show ?? 25) == 25  ? 'selected' : '' }}>25</option>
                                    <option value="50"  {{ ($show ?? 25) == 50  ? 'selected' : '' }}>50</option>
                                    <option value="100" {{ ($show ?? 25) == 100 ? 'selected' : '' }}>100</option>
                                </select>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <select name="author" id="newsAuthorFilter" class="form-select form-select-sm"
                                    onchange="document.getElementById('newsFilterForm').submit()">
                                <option value="">All Authors</option>
                                @foreach($authors as $author)
                                    <option value="{{ $author->id }}"
                                            {{ request('author') == $author->id ? 'selected' : '' }}>
                                        {{ $author->name }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-5">
                            <input type="text" name="search" id="newsSearch"
                                   class="form-control form-control-sm"
                                   placeholder="Search news title or author..."
                                   value="{{ request('search') }}">
                        </div>
                        <div class="col-md-2">
                            <button type="submit" class="btn btn-primary btn-sm w-100">
                                <i class="ti ti-search me-1"></i>Search
                            </button>
                        </div>
                    </div>
                </form>
            </div>

            <div id="newsTableWrapper">
                <div class="table-responsive">
                    <table class="table table-vcenter card-table">
                        <thead>
                            <tr>
                                <th style="width:50px">NO</th>
                                <th style="width:90px">IMAGE</th>
                                <th>TITLE</th>
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
                                <td colspan="6">
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

@endsection

@section('script')
<script>
var newsSearchTimer;
document.getElementById('newsSearch').addEventListener('keyup', function() {
    clearTimeout(newsSearchTimer);
    var form = document.getElementById('newsFilterForm');
    newsSearchTimer = setTimeout(function() { form.submit(); }, 450);
});
</script>
@endsection
