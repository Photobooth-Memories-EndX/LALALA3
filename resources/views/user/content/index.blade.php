@extends('layouts.sidebar-master')

@section('style')
    <style>
        .content-filter-card {
            border-radius: 18px;
        }

        .learning-path-card {
            border-radius: 18px;
        }

        .learning-path-card .card-body {
            padding: 24px;
        }

        .learning-path-card:hover,
        .content-card:hover {
            transform: translateY(-3px);
            transition: all .2s ease;
        }
    </style>
@endsection

@section('content')
<div class="page-body">
    <div class="container-xl">

        @php
            $selectedCategory = $classifications->firstWhere('id', $filterClassification);
            $hasActiveFilter = filled($filterSearch) || filled($filterClassification) || filled($filterType) || filled($filterAuthor) || filled($filterViewOrder) || $filterDateOrder !== 'newest';
        @endphp

        {{-- HERO --}}
        <div class="card mb-4 overflow-hidden rounded-4 border-0 shadow-sm">
            <div class="card-body p-4 p-lg-5 text-white" style="background: linear-gradient(135deg, #2f6de1 0%, #4d8df5 55%, #5da0ff 100%);">
                <div class="row align-items-center g-4">
                    <div class="col-lg-8">
                        <div class="d-inline-flex align-items-center gap-2 badge bg-white text-primary mb-3 px-3 py-2 rounded-pill">
                            <i class="ti ti-sparkles"></i>
                            Learning Content
                        </div>

                        <h1 class="mb-2 fw-bold">
                            {{ $selectedCategory->classification_name ?? 'All Learning Content' }}
                        </h1>

                        <p class="mb-0 text-white-75">
                            Temukan materi pembelajaran berdasarkan kategori, tipe konten, dan urutan terbaru dalam satu halaman.
                        </p>
                    </div>

                    <div class="col-lg-4 text-lg-end">
                        <div class="d-inline-flex align-items-center gap-2 bg-white text-primary rounded-pill px-3 py-2 shadow-sm">
                            <i class="ti ti-folder"></i>
                            <strong>{{ $contents->total() }}</strong>
                            <span>Content</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        @include('user.content.components.learning-paths')

        @include('user.content.components.content-search-filter')

        {{-- MAIN CONTENT --}}
        <div class="row">

            {{-- LEFT CONTENT --}}
            <div class="col-lg-9">

                <div class="d-flex align-items-center mb-3">
                    <div>
                        <h2 class="mb-0">Learning Materials</h2>
                        <div class="text-muted">
                            Menampilkan daftar materi pembelajaran OWL LMS.
                        </div>
                    </div>
                </div>

                <div class="row row-cards">
                    @forelse ($contents as $item)
                        <div class="col-sm-6 col-xl-4">
                            @if (in_array($item->type, ['Video', 'YouTube']))
                                @include('user.content.components.card-video', ['item' => $item])
                            @else
                                @include('user.content.components.card-document', ['item' => $item])
                            @endif
                        </div>
                    @empty
                        <div class="col-12">
                            <div class="empty card card-body rounded-4 shadow-sm border-0">
                                <div class="empty-img">
                                    <i class="ti ti-folder-off" style="font-size: 64px;"></i>
                                </div>
                                <p class="empty-title">Content not found</p>
                                <p class="empty-subtitle text-muted">
                                    Tidak ada content yang sesuai dengan filter yang dipilih.
                                </p>
                                <div class="empty-action">
                                    <a href="{{ route('content.user.index') }}" class="btn btn-primary">
                                        Reset Filter
                                    </a>
                                </div>
                            </div>
                        </div>
                    @endforelse
                </div>

                <div class="mt-4">
                    {{ $contents->links() }}
                </div>

            </div>

            {{-- RIGHT SIDEBAR --}}
            <div class="col-lg-3 mt-4 mt-lg-0">

                {{-- SUGGESTED SKILLS --}}
                <div class="card mb-3 rounded-4 border-0 shadow-sm">
                    <div class="card-body">
                        <h3 class="card-title">
                            <i class="ti ti-sparkles me-1"></i>
                            Suggested Skills
                        </h3>

                        <div class="d-flex flex-wrap gap-2">
                            @foreach ($classifications->take(8) as $skill)
                                <a href="{{ route('content.user.index', ['classification' => $skill->id]) }}"
                                   class="badge bg-primary-lt text-decoration-none rounded-pill px-3 py-2">
                                    {{ $skill->classification_name }}
                                </a>
                            @endforeach
                        </div>
                    </div>
                </div>

                {{-- QUICK FILTER --}}
                <div class="card mb-3 rounded-4 border-0 shadow-sm">
                    <div class="card-body">
                        <h3 class="card-title">
                            <i class="ti ti-filter me-1"></i>
                            Quick Filter
                        </h3>

                        <div class="list-group list-group-flush">
                            <a href="{{ route('content.user.index', ['type' => 'Video']) }}"
                               class="list-group-item list-group-item-action px-0">
                                <i class="ti ti-video me-2 text-primary"></i>
                                Video Content
                            </a>

                            <a href="{{ route('content.user.index', ['type' => 'YouTube']) }}"
                               class="list-group-item list-group-item-action px-0">
                                <i class="ti ti-brand-youtube me-2 text-danger"></i>
                                YouTube Content
                            </a>

                            <a href="{{ route('content.user.index', ['type' => 'Document']) }}"
                               class="list-group-item list-group-item-action px-0">
                                <i class="ti ti-file-text me-2 text-success"></i>
                                Document Content
                            </a>

                            <a href="{{ route('content.user.index', ['date_order' => 'newest']) }}"
                               class="list-group-item list-group-item-action px-0">
                                <i class="ti ti-clock me-2 text-warning"></i>
                                Newest Content
                            </a>
                        </div>
                    </div>
                </div>

                {{-- INFO --}}
                <div class="card bg-primary text-white rounded-4 border-0 shadow-sm">
                    <div class="card-body">
                        <h3 class="text-white">
                            <i class="ti ti-bulb me-1"></i>
                            Learning Tips
                        </h3>
                        <p class="mb-0">
                            Gunakan filter kategori dan tipe konten agar materi pembelajaran lebih mudah ditemukan.
                        </p>
                    </div>
                </div>

            </div>

        </div>

    </div>
</div>
@endsection