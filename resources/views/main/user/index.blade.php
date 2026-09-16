@extends('layouts.sidebar-master')

@section('content')
@include('main.monitoring.components.style')

{{-- Page Header --}}
<div class="page-header d-print-none mb-0 pb-0">
    <div class="container-xl">
        <!-- Back Navigation Link -->
        <div class="mb-3">
            <a href="{{ url('/manage') }}" class="text-decoration-none text-muted small d-inline-flex align-items-center gap-1">
                <i class="ti ti-arrow-left fs-3"></i><span>Back</span>
            </a>
        </div>

        @include('main.user.partials.header')
    </div>
</div>

<div class="page-body mt-0 pt-0">
    <div class="container-xl">

        <x-alert type="success" />
        <x-alert type="warning" />

        @if($errors->any())
            <div class="alert alert-danger shadow-sm">
                {!! implode('', $errors->all('<div>:message</div>')) !!}
            </div>
        @endif

        @include('main.user.partials.stats')

        @include('main.user.partials.table')

    </div>
</div>

{{-- Modal Form --}}
@include('main.user.partials.modal-create-ptkp')
@include('main.user.partials.modal-edit-ptkp')
@include('main.user.partials.modal-create-outsourcing')
@include('main.user.partials.modal-edit-outsourcing')

{{-- Modal Action --}}
@include('main.user.partials.modal-action')

@endsection

@section('style')
@include('main.user.partials.style')
@endsection

@section('script')
@include('main.user.partials.script')
@endsection