@extends('layouts.sidebar-master')

@section('content')

    @include('main.monitoring.components.style')
    @include('main.monitoring.components.datatable-assets')

    {{-- Page Header --}}
    <div class="page-header d-print-none mb-0 pb-0">
        <div class="container-xl">
            <!-- Back Navigation Link -->
            <a href="{{ route('manage.dashboard') }}" class="btn btn-link p-0 mb-3 text-decoration-none d-inline-flex align-items-center gap-1 fw-semibold text-primary">
                <i class="ti ti-arrow-left fs-2"></i> Back
            </a>

            <!-- Monitoring System Hero Header Card -->
            <div class="card border border-light-subtle shadow-sm p-4 mb-3 rounded-3 bg-white hero-header-card">
                <div class="d-flex flex-column flex-md-row align-items-md-center justify-content-between gap-3">
                    <div class="d-flex align-items-center gap-3">
                        <div class="rounded-3 d-flex align-items-center justify-content-center flex-shrink-0 hero-icon-box"
                             style="width: 72px; height: 72px; background-color: #eef2ff; color: #003b6d;">
                            <i class="ti ti-chart-line" style="font-size: 2rem !important;"></i>
                        </div>
                        <div>
                            <h2 class="h3 fw-bold mb-1 text-dark header-title">Monitoring System</h2>
                            <p class="text-muted small mb-0 header-subtitle">Monitor user learning progress, assessments, playlists, and certificates.</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="page-body mt-0 pt-0">
        <div class="container-xl">

            {{-- Alert scope khusus role 4 --}}
            @if ($isRole4)
                @if ($isScopeEmpty)
                    <div class="alert alert-warning" role="alert">
                        <i class="fas fa-exclamation-triangle mr-1"></i>
                        Your organizational scope has not been configured. Please contact the Super Admin.
                    </div>
                @else
                    <div class="alert alert-info" role="alert">
                        <i class="fas fa-info-circle mr-1"></i>
                        You are viewing monitoring data for <strong>{{ $scopeLabel }}</strong>.
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
                'title'       => 'List User',
                'subtitle'    => 'Scope: ' . $scopeLabel,
            ])

        </div>
    </div>

@endsection
