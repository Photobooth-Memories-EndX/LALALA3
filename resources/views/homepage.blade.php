@extends('layouts.sidebar-master')

@section('style')
    <link href="{{ asset('Tabler/dist/css/tabler-vendors.min.css?1684106062') }}" rel="stylesheet"/>
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
            <div class="row g-4">
                {{-- HERO SECTION --}}
                @include('components.Home.hero')

                {{-- LEFT: Main Content (8 cols) --}}
                <div class="col-xl-8 col-lg-8 d-flex flex-column gap-4">

                    {{-- Continue Learning --}}
                    @include('components.Home.continue-learning')

                    {{-- Top Video Learning --}}
                    @include('components.Home.top-video')

                    {{-- Training Modules: Video / Document / Playlist tabs --}}
                    @include('components.Home.training-modules')

                </div>

                {{-- RIGHT: Sidebar (4 cols) --}}
                <div class="col-xl-4 col-lg-4 d-flex flex-column gap-4">

                    {{-- Profile + Donut Chart --}}
                    @include('components.Home.sidebar-profile')

                    {{-- Upcoming Deadlines --}}
                    @include('components.Home.upcoming-deadline')

                    {{-- Latest Gallery --}}
                    @include('components.Home.latest-gallery')

                    {{-- Latest News --}}
                    @include('components.Home.latest-news')

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
