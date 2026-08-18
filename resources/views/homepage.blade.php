@extends('layouts.sidebar-master')

@section('style')
    <link href="{{ asset('Tabler/dist/css/tabler-vendors.min.css?1684106062') }}" rel="stylesheet"/>
    <link rel="stylesheet" href="{{ asset('css/learning-assessment.css') }}">
    <link rel="stylesheet" href="{{ asset('css/home.css') }}">
    @include('components.Home.styles')
@endsection

@section('content')

    @php
        $content_video = $content->filter(fn($c) => in_array($c->type, ['YouTube', 'Video']))->take(6);
        $content_pdf   = $content->filter(fn($c) => $c->type === 'Document')->take(6);
    @endphp

    <div class="page-body owl-unified-theme-page home-modern-page">
        <div class="container-xl py-4">

            {{-- ── Dashboard Header ── --}}
            @include('components.Home.page-header', ['showProfileCard' => false])
            
            {{-- ── Main 2-Column Layout ── --}}
            <div class="row g-4 home-modern-main-row">
                {{-- HERO SECTION --}}
                <div class="col-12 home-hero-wrapper">
                    @include('components.Home.hero')
                </div>

                {{-- LEFT: Main Content (8 cols) --}}
                <div class="col-xl-8 col-lg-8 d-flex flex-column gap-4 home-main-left-col">

                    {{-- Continue Learning --}}
                    <div class="home-continue-learning-wrapper">
                        @include('components.Home.continue-learning')
                    </div>

                    {{-- Top Video Learning --}}
                    <div class="home-top-video-wrapper">
                        @include('components.Home.top-video')
                    </div>

                    {{-- Training Modules: Video / Document / Playlist tabs --}}
                    <div class="home-training-modules-wrapper">
                        @include('components.Home.training-modules')
                    </div>

                </div>

                {{-- RIGHT: Sidebar (4 cols) --}}
                <div class="col-xl-4 col-lg-4 d-flex flex-column gap-4 home-main-right-col">

                    {{-- Profile + Donut Chart / Training Schedule --}}
                    <div class="home-sidebar-profile-wrapper">
                        @include('components.Home.sidebar-profile')
                    </div>

                    {{-- Upcoming Deadlines --}}
                    <div class="home-upcoming-deadline-wrapper">
                        @include('components.Home.upcoming-deadline')
                    </div>

                    {{-- Latest Gallery --}}
                    <div class="home-latest-gallery-wrapper">
                        @include('components.Home.latest-gallery')
                    </div>

                    {{-- Latest News --}}
                    <div class="home-latest-news-wrapper">
                        @include('components.Home.latest-news')
                    </div>

                </div>

            </div>

        </div>
    </div>

    {{-- Assessment Access Modal --}}
    @include('components.Home.assessment-modal')

@endsection

{{-- Scripts: ApexCharts + Assessment AJAX --}}
@section('script')
    @include('components.Home.scripts')
@endsection
