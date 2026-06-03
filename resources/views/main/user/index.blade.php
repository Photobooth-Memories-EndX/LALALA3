@extends('layouts.sidebar-master')

@section('content')

@include('main.user.partials.header')

<div class="page-body">
    <div class="container-xl">

        <x-alert type="success" />
        <x-alert type="warning" />

        @if($errors->any())
            <div class="alert alert-danger shadow-sm">
                {!! implode('', $errors->all('<div>:message</div>')) !!}
            </div>
        @endif

        @include('main.user.partials.stats')

        @include('main.user.partials.user-card')

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