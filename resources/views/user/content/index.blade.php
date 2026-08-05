@extends('layouts.sidebar-master')

@section('style')
    @include('user.content.partials.content-style')
@endsection

@section('content')
<div class="page-body">
    <div class="container-xl">
        @php
            $selectedCategory = $classifications->firstWhere('id', $filterClassification);
            $activeCategoryLabel = $selectedCategory->classification_name ?? 'All Categories';
        @endphp

        <div class="card content-hero-card mb-4 overflow-hidden border-0 shadow-sm">
            <div class="card-body content-hero-surface text-white">
                <div class="row align-items-center g-4">
                    <div class="col-lg-8 content-hero-copy">
                        <div class="d-inline-flex align-items-center gap-2 badge bg-white text-primary mb-2 rounded-pill content-hero-badge">
                            <i class="ti ti-sparkles"></i>
                            Learning Content
                        </div>

                        <h1 class="mb-1 fw-bold">All Content</h1>
                        <p class="mb-0 text-white-75">
                            Explore curated videos, documents, and learning materials that help you keep growing every day.
                        </p>
                    </div>

                    <div class="col-lg-4 d-flex justify-content-lg-end">
                        <div class="content-hero-count">
                            <span>{{ $activeCategoryLabel }}</span>
                            <strong>{{ $contents->total() }}</strong>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        @include('user.content.components.content-search-filter')
        @include('user.content.components.content-grid')
    </div>
</div>
@endsection

@section('script')
    @include('user.content.partials.content-script')
@endsection