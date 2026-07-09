@extends('layouts.sidebar-master')

@section('content')

    @include('main.monitoring.components.style')
    @include('main.monitoring.components.datatable-assets')

    {{-- Header --}}
    <div class="page-header d-print-none mb-3">
        <div class="container-xl">
            <div class="row align-items-center">
                <div class="col">
                    <div class="page-pretitle">Manage &middot; Monitoring</div>
                    <h2 class="owl-page-title mb-1">Monitoring Profile</h2>
                    <div class="owl-page-subtitle">Detail progress learning, assessment, dan playlist user.</div>
                </div>
                <div class="col-auto ms-auto">
                    <a href="{{ route('monitoring.index') }}" class="btn btn-light owl-action-btn">
                        <i class="fas fa-arrow-left mr-1"></i> Back
                    </a>
                </div>
            </div>
        </div>
    </div>

    <div class="page-body">
        <div class="container-xl">

            @include('main.monitoring.components.profile-header', ['profile' => $profile])

            @include('main.monitoring.components.profile-summary', ['summary' => $summary])

            @include('main.monitoring.components.chart-cards', [
                'charts'      => $charts,
                'chartPrefix' => $chartPrefix,
            ])

            @include('main.monitoring.components.profile-tabs', ['profile' => $profile])

        </div>
    </div>

@endsection
