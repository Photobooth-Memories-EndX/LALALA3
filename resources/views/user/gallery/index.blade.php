@extends('layouts.sidebar-master')

@section('content')
@include('components.shared.hero-style')

<div class="container-xl py-4">
    @include('user.gallery.components.gallery-hero')
    @include('user.gallery.components.gallery-filters')
    @include('user.gallery.components.gallery-grid')
</div>
@endsection

@section('style')
@include('user.gallery.components.gallery-style')
@endsection

@section('script')
@include('user.gallery.components.gallery-script')
@endsection
