@extends('layouts.sidebar-master')

@section('content')

<div class="page-body">

    <div class="container-fluid px-4 py-4">

        {{-- HERO --}}
        @include('components.Home.hero')

        {{-- STATS --}}
        @include('components.Home.stats')

        {{-- QUICK ACTIONS --}}
        {{-- <div class="row g-2 mt-3">
            <div class="col-6 col-sm-3">
                <a href="{{ route('user.learning.dashboard') }}" class="home-modern-quick-btn">
                    <i class="ti ti-layout-dashboard"></i>
                    <span>My Learning</span>
                </a>
            </div>
            <div class="col-6 col-sm-3">
                <a href="{{ route('content.user.index') }}" class="home-modern-quick-btn">
                    <i class="ti ti-books"></i>
                    <span>All Content</span>
                </a>
            </div>
            <div class="col-6 col-sm-3">
                <a href="{{ route('playlist.user.index') }}" class="home-modern-quick-btn">
                    <i class="ti ti-list-details"></i>
                    <span>Playlist</span>
                </a>
            </div>
            <div class="col-6 col-sm-3">
                <a href="{{ route('assesment.user.index') }}" class="home-modern-quick-btn">
                    <i class="ti ti-clipboard-check"></i>
                    <span>Assessment</span>
                </a>
            </div>
        </div> --}}

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
