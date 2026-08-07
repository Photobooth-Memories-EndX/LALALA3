@extends('layouts.sidebar-master')

@section('style')
    <link href="{{ asset('Tabler/dist/css/tabler-vendors.min.css?1684106062') }}" rel="stylesheet"/>
    @include('components.Home.styles')
@endsection

@section('content')

    <div class="page-body">
        <div class="container-xl py-4">

            <a href="{{ Route::has('home.user.index') ? route('home.user.index') : url('/') }}" class="btn btn-link px-0 mb-3">
                <i class="fas fa-arrow-left me-2"></i> Back
            </a>

            <div class="home-modern-db-header mb-3">
                <div>
                    <h1 class="home-modern-db-title">Training Plan</h1>
                    <p class="home-modern-db-subtitle">Plan and track your learning path</p>
                </div>
            </div>

            <div class="home-modern-card">
                <div class="home-modern-card-body pt-4">
                    <div class="home-modern-empty">
                        <i class="ti ti-clipboard-list"></i>
                        <p>Coming Soon</p>
                    </div>
                </div>
            </div>

        </div>
    </div>

@endsection