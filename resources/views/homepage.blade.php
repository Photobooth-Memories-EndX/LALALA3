@extends('layouts.sidebar-master')

@section('content')

<div class="page-body">


<div class="container-fluid px-4 py-4">

    {{-- HERO --}}
    @include('components.Home.hero')

    {{-- STATS --}}
    @include('components.Home.stats')

    {{-- CONTINUE LEARNING --}}
    @include('components.Home.continue-learning')

    {{-- TOP VIDEO LEARNING --}}
    @include('components.Home.recommended-learning')

    {{-- TRAINING MODULES --}}
    @include('components.Home.training-modules')

    {{-- UPCOMING DEADLINES --}}
    @include('components.Home.upcoming-deadline')

    {{-- LATEST NEWS --}}
    @include('components.Home.latest-news')

    {{-- RECENT ACTIVITY --}}
    @include('components.Home.latest-gallery')

</div>


</div>

@endsection

@section('style')
@include('components.Home.styles')
@endsection

@section('script')
@include('components.Home.scripts')
@endsection
