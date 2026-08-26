@extends('layouts.sidebar-master')

@section('content')

    @include('main.assesment.partials.style')

    @include('main.assesment.partials.header')

    <div class="page-body">
        <div class="container-xl">

            <x-alert type="success" />
            <x-alert type="warning" />
            <x-alert type="danger" />

            @include('main.assesment.partials.stats', [
                'assesment' => $assesment
            ])

            @include('main.assesment.partials.table', [
                'assesment' => $assesment
            ])

        </div>
    </div>

    @include('main.assesment.partials.modal-duplicate')

@endsection

@push('scripts')
    @include('main.assesment.partials.scripts')
@endpush