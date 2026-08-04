@extends('layouts.sidebar-master')

@section('content')
<main class="container-xl py-4 gallery-page" data-gallery-page>
    @include('user.gallery.components.gallery-hero')
    @include('user.gallery.components.gallery-filters')
    <div id="gallery-results" data-gallery-results aria-live="polite" aria-busy="false">
        @include('user.gallery.components.gallery-results')
    </div>
</main>
@endsection

@section('style')
@include('user.gallery.components.gallery-style')
@endsection

@section('script')
@include('user.gallery.components.gallery-script')
@endsection
