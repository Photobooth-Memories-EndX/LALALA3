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
                    <h2 class="owl-page-title mb-1">Monitoring Group</h2>
                    <div class="owl-page-subtitle">
                        {{ $typeLabel }}: <strong>{{ $value }}</strong>
                    </div>
                </div>
                <div class="col-auto ms-auto">
                    <a href="{{ route('monitoring.index') }}" class="btn btn-light owl-action-btn">
                        <i class="fas fa-arrow-left mr-1"></i> Kembali
                    </a>
                </div>
            </div>
        </div>
    </div>

    <div class="page-body">
        <div class="container-xl">

            @include('main.monitoring.components.stats-cards', ['stats' => $stats])

            @include('main.monitoring.components.user-table', [
                'tableId'     => 'group-table',
                'ajaxUrl'     => route('monitoring.group.datatable', ['type' => $type, 'value' => $value]),
                'withFilters' => false,
                'title'       => $typeLabel . ': ' . $value,
                'subtitle'    => 'Daftar user dalam group ini.',
            ])

        </div>
    </div>

@endsection
