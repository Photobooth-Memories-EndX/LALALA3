@extends('layouts.sidebar-master')

@section('content')
<div class="page-body">
    <div class="container-xl">

        @include('main.certificate.components.header')

        @include('main.certificate.components.alert')
        @include('main.certificate.components.summary-cards')
        @include('main.certificate.components.filters')
        @include('main.certificate.components.table')
        @include('main.certificate.components.add-modal')
        @include('main.certificate.components.edit-modal')

    </div>
</div>
@endsection

@section('style')
<link rel="stylesheet" href="{{ asset('css/dataTables.bootstrap5.min.css') }}">
<style>
    .certificate-row:hover {
        background: rgba(32, 107, 196, .08);
    }

    .certificate-row a {
        text-decoration: none;
    }

    .certificate-row a:hover {
        text-decoration: underline;
    }

    .certificate-info-alert {
        display: none;
    }
    #certificate-settings-table td:last-child { white-space: nowrap; }
    .certificate-summary-card .card-body { min-height: 105px; }
    .dataTables_wrapper .dataTables_filter { display: none; }
</style>
@endsection

@section('script')
    @include('main.certificate.scripts.datatable')
    @include('main.certificate.scripts.dynamic-dropdown')
@endsection
