@extends('layouts.sidebar-master')

@section('content')
<div class="page-body">
    <div class="container-xl">

        @include('main.manage.components.dashboard-hero')

        @include('main.manage.components.dashboard-stats')

        @include('main.manage.components.dashboard-modules')

        @include('main.manage.components.quick-actions')

        <div class="row g-3 mt-4">
            <div class="col-lg-8">
                @include('main.manage.components.recent-activity')
            </div>

            <div class="col-lg-4">
                @include('main.manage.components.system-status')
            </div>
        </div>

    </div>
</div>
@endsection

@section('style')
<style>
    .admin-menu-card {
        border-radius: 18px;
        transition: all .2s ease;
    }

    .admin-menu-card:hover {
        transform: translateY(-4px);
        box-shadow: 0 12px 30px rgba(15, 23, 42, .12) !important;
    }

    .admin-menu-card .avatar {
        width: 46px;
        height: 46px;
        font-size: 22px;
    }
</style>
@endsection