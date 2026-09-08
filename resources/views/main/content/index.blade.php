@extends('layouts.sidebar-master')

@section('style')
    @include('main.content.components.content-styles')
@endsection

@section('content')
    @include('main.monitoring.components.style')

    @include('main.content.components.content-header')

    <div class="page-body mt-0 pt-0">
        <div class="container-xl">
            @include('main.content.components.content-alerts')

            @include('main.content.components.content-statistics')

            @include('main.content.components.content-quick-filter')

            @include('main.content.components.content-table')
        </div>
    </div>

    {{-- Modals --}}
    @include('main.content.components.content-more-filters-modal')

    @include('main.content.components.content-sync-learning-hours-modal')

    @include('main.content.components.content-sync-total-page-modal')

    @include('main.content.components.content-detail-modal')
@endsection

@section('script')
    @include('main.content.components.content-scripts')
@endsection
