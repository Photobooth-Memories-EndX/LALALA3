@extends('layouts.sidebar-master')

@section('content')
<div class="page-body">
    <div class="container-xl">

        @include('main.certificate.components.header')

        @include('main.certificate.components.alert')

        @include('main.certificate.components.table', [
            'settings' => $settings
        ])

        @include('main.certificate.components.add-modal')

    </div>
</div>
@endsection

@section('style')
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
</style>
@endsection

@section('script')
    @include('main.certificate.scripts.dynamic-dropdown')
@endsection