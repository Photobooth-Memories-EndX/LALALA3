@extends('layouts.sidebar-master')

@section('content')

    @include('main.monitoring.components.style')
    @include('main.monitoring.components.datatable-assets')

    {{-- Header --}}
    <div class="page-header d-print-none mb-3">
        <div class="container-xl">
            <div class="row align-items-center">
                <div class="col">
                    <div class="page-pretitle">Manage</div>
                    <h2 class="owl-page-title mb-1">Monitoring System</h2>
                    <div class="owl-page-subtitle">
                        Monitoring progress learning, assessment, playlist, dan sertifikat user.
                    </div>
                </div>
                <div class="col-auto ms-auto">
                    <ol class="breadcrumb breadcrumb-arrows mb-0" aria-label="breadcrumbs">
                        <li class="breadcrumb-item"><a href="#">Manage</a></li>
                        <li class="breadcrumb-item active" aria-current="page"><a href="#">Monitoring</a></li>
                    </ol>
                </div>
            </div>
        </div>
    </div>

    <div class="page-body">
        <div class="container-xl">

            {{-- Alert scope khusus role 4 --}}
            @if ($isRole4)
                @if ($isScopeEmpty)
                    <div class="alert alert-warning" role="alert">
                        <i class="fas fa-exclamation-triangle mr-1"></i>
                        Scope organisasi Anda belum diset. Hubungi Super Admin.
                    </div>
                @else
                    <div class="alert alert-info" role="alert">
                        <i class="fas fa-info-circle mr-1"></i>
                        Anda melihat monitoring untuk <strong>{{ $scopeLabel }}</strong>.
                    </div>
                @endif
            @endif

            @include('main.monitoring.components.stats-cards', ['stats' => $stats])

            @include('main.monitoring.components.chart-cards', [
                'charts'      => $charts,
                'chartPrefix' => $chartPrefix,
            ])

            @include('main.monitoring.components.filter-card', [
                'divisions'   => $divisions,
                'departments' => $departments,
                'teams'       => $teams,
                'roles'       => $roles,
            ])

            @include('main.monitoring.components.user-table', [
                'tableId'     => 'monitoring-table',
                'ajaxUrl'     => route('monitoring.datatable'),
                'withFilters' => true,
                'title'       => 'Daftar User',
                'subtitle'    => 'Scope: ' . $scopeLabel,
            ])

        </div>
    </div>

@endsection
