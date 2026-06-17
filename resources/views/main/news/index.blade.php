@extends('layouts.sidebar-master')

@section('content')

<div class="page-header d-print-none">
    <div class="container-xl">

        <div class="row g-2 align-items-center">

            <div class="col">

                <div class="d-flex align-items-center gap-3">

                    <div class="avatar avatar-lg bg-primary-lt">
                        <i class="ti ti-news fs-1"></i>
                    </div>

                    <div>

                        <h2 class="page-title mb-1">
                            Manage News
                        </h2>

                        <div class="text-secondary">
                            Manage all company news and articles
                        </div>

                    </div>

                </div>

            </div>

            <div class="col-auto ms-auto d-print-none">

                <a href="{{ route('news.create') }}"
                    class="btn btn-primary">

                    <i class="ti ti-plus"></i>

                    Add News

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
        <div class="row row-deck row-cards mb-4">

            <div class="col-sm-6 col-lg-6">

                <div class="card stat-card shadow-sm h-100">

                    <div class="card-body">

                        <div class="d-flex justify-content-between align-items-center">

                            <div>

                                <div class="subheader">
                                    Total News
                                </div>

                                <div class="h1 mb-0">
                                    {{ $totalNews }}
                                </div>

                            </div>

                            <div class="avatar avatar-lg bg-primary-lt">
                                <i class="ti ti-news fs-3"></i>
                            </div>

                        </div>

                    </div>

                </div>

            </div>

            <div class="col-sm-6 col-lg-6">

                <div class="card stat-card shadow-sm h-100">

                    <div class="card-body">

                        <div class="d-flex justify-content-between align-items-center">

                            <div>

                                <div class="subheader">
                                    Contributors
                                </div>

                                <div class="h1 text-success mb-0">
                                    {{ $totalAuthor }}
                                </div>

                            </div>

                            <div class="avatar avatar-lg bg-success-lt">
                                <i class="ti ti-users fs-3"></i>
                            </div>

                        </div>

                    </div>

                </div>

            </div>

        </div>

        {{-- Search --}}
        <div class="row mb-4">

            <div class="col-md-12">

                <input
                    type="text"
                    id="newsSearch"
                    class="form-control"
                    placeholder="Search news...">

            </div>

        </div>

        {{-- News Grid --}}
        <div class="row row-cards g-4" id="newsContainer">

            @forelse($news as $list)

                <div class="col-md-6 col-xl-4 news-item"
                    data-title="{{ strtolower($list->title) }}">

                    <div class="card gallery-card h-100">

                        <div class="gallery-image-wrapper">

                            @if($list->image)

                                <img
                                    src="{{ asset('storage/news/'.$list->image) }}"
                                    class="gallery-image"
                                    alt="{{ $list->title }}">

                            @else

                                <div class="gallery-placeholder">

                                    <i class="ti ti-news fs-1"></i>

                                </div>

                            @endif

                        </div>

                        <div class="card-body">

                            <h3 class="card-title">

                                {{ \Illuminate\Support\Str::limit($list->title, 80) }}

                            </h3>

                            <div class="text-secondary mb-3">

                                {{ \Illuminate\Support\Str::limit(strip_tags($list->description), 120) }}

                            </div>

                            <div class="d-flex justify-content-between align-items-center">

                                <span class="badge bg-primary-lt">

                                    {{ $list->user->name }}

                                </span>

                                <span class="text-secondary small">

                                    {{ date('d M Y', strtotime($list->created_at)) }}

                                </span>

                            </div>

                        </div>

                        <div class="card-footer bg-transparent border-0">

                            <div class="d-flex gap-2">

                                <a
                                    href="{{ route('news.edit', $list->slug) }}"
                                    class="btn btn-primary w-100 action-btn">

                                    <i class="ti ti-edit me-1"></i>
                                    Edit

                                </a>

                                <form
                                    action="{{ route('news.destroy', $list->slug) }}"
                                    method="POST"
                                    class="w-100">

                                    @csrf
                                    @method('DELETE')

                                    <button
                                        type="submit"
                                        class="btn btn-danger w-100 action-btn"
                                        onclick="return confirm('Delete this news?')">

                                        <i class="ti ti-trash me-1"></i>
                                        Delete

                                    </button>

                                </form>

                            </div>

                        </div>

                    </div>

                </div>

            @empty

                <div class="col-12">

                    <div class="empty">

                        <div class="empty-img">
                            <i class="ti ti-news-off fs-1"></i>
                        </div>

                        <p class="empty-title">
                            No News Found
                        </p>

                    </div>

                </div>

            @endforelse

        </div>

    </div>
</div>

@endsection

@section('style')

<style>

.stat-card{
    border:none;
    border-radius:18px;
    overflow:hidden;
    transition:.3s;
}

.stat-card:hover{
    transform:translateY(-5px);
    box-shadow:0 15px 35px rgba(0,0,0,.08);
}

.gallery-card{
    border:none;
    border-radius:18px;
    overflow:hidden;
    transition:.3s;
}

.gallery-card:hover{
    transform:translateY(-5px);
    box-shadow:0 15px 35px rgba(0,0,0,.12);
}

.gallery-image{
    width:100%;
    height:220px;
    object-fit:cover;
}

.gallery-placeholder{
    height:220px;
    background:#f1f5f9;
    display:flex;
    justify-content:center;
    align-items:center;
}

.card-title{
    font-size:1rem;
    font-weight:700;
    line-height:1.5;
    min-height:52px;
}

.action-btn{
    height:42px;
    display:flex;
    justify-content:center;
    align-items:center;
    font-weight:600;
}

#newsSearch{
    border-radius:14px;
    padding:12px 16px;
    border:1px solid #e5e7eb;
}

[data-bs-theme="dark"] .gallery-card{
    background:#1f2937;
}

[data-bs-theme="dark"] .stat-card{
    background:#1f2937;
}

[data-bs-theme="dark"] .gallery-placeholder{
    background:#111827;
}

[data-bs-theme="dark"] #newsSearch{
    background:#111827;
    border-color:#374151;
    color:#fff;
}

</style>

@endsection

@section('script')

<script>

document.addEventListener('DOMContentLoaded', function(){

    const search =
        document.getElementById('newsSearch');

    const cards =
        document.querySelectorAll('.news-item');

    search.addEventListener('keyup', function(){

        let keyword =
            this.value.toLowerCase();

        cards.forEach(card => {

            let title =
                card.dataset.title;

            card.style.display =
                title.includes(keyword)
                ? ''
                : 'none';

        });

    });

});

</script>

@endsection
