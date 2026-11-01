@extends('layouts.sidebar-master')

@section('style')
<link rel="stylesheet" href="{{ asset('css/dataTables.bootstrap5.min.css') }}">
<link rel="stylesheet" href="{{ asset('css/manage/kp-owl/kp-owl.css') }}">
<link rel="stylesheet" href="{{ asset('css/manage/kp-owl/kp-owl-dark.css') }}">
@endsection

@section('content')
<div class="page-body">
    <div class="container-xl">

        {{-- Record Navigation Cards (Consistent with Record Management) --}}
        @include('main.record.components.record-navigation')

        {{-- Page Header --}}
        <div class="page-header d-print-none mb-3">
            <div class="row align-items-center">
                <div class="col">
                    <div class="page-pretitle text-muted">
                        <ol class="breadcrumb breadcrumb-arrows p-0 m-0">
                            <li class="breadcrumb-item"><a href="{{ url('manage') }}">Manage</a></li>
                            <li class="breadcrumb-item active" aria-current="page">KP OWL</li>
                        </ol>
                    </div>
                    <h2 class="page-title fw-bold text-dark mt-1">
                        KP OWL Learning Records
                    </h2>
                    <p class="text-muted small mb-0 mt-0.5">
                        Manage and monitor employee online learning records from standalone contents and structured training playlists.
                    </p>
                </div>
            </div>
        </div>

        {{-- Statistics KPI Cards --}}
        @include('manage.kp-owl.partials.statistics')

        {{-- Filters & Search Toolbar --}}
        @include('manage.kp-owl.partials.filters')

        {{-- 30-Column DataTable --}}
        @include('manage.kp-owl.partials.table')

        {{-- 17-Field Detail Modal --}}
        @include('manage.kp-owl.partials.detail-modal')

    </div>
</div>
@endsection

@section('script')
<script src="{{ asset('js/jquery.dataTables.min.js') }}"></script>
<script src="{{ asset('js/dataTables.bootstrap5.min.js') }}"></script>
<script>
    window.KP_OWL_ROUTES = {
        data: "{{ route('manage.kp-owl.data') }}",
        statistics: "{{ route('manage.kp-owl.statistics') }}",
        export: "{{ route('manage.kp-owl.export') }}",
        detailBase: "{{ url('manage/kp-owl') }}"
    };
</script>
<script src="{{ asset('js/manage/kp-owl/kp-owl-table.js') }}"></script>
<script src="{{ asset('js/manage/kp-owl/kp-owl-filter.js') }}"></script>
<script src="{{ asset('js/manage/kp-owl/kp-owl-detail.js') }}"></script>
<script src="{{ asset('js/manage/kp-owl/kp-owl-export.js') }}"></script>
@endsection
