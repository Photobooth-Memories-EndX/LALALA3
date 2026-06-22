@extends('layouts.sidebar-master')

@section('content')

    {{-- Reuse design system (owl-* cards/table + dark mode) agar konsisten dgn Monitoring Group --}}
    @include('main.monitoring.components.style')

    {{-- Header --}}
    <div class="page-header d-print-none mb-3">
        <div class="container-xl">
            <div class="row align-items-center">
                <div class="col">
                    <div class="page-pretitle">Manage &middot; Role</div>
                    <h2 class="owl-page-title mb-1">Manage Role</h2>
                    <div class="owl-page-subtitle">
                        Kelola role, division, department, team, dan org unit user.
                    </div>
                </div>
                <div class="col-auto ms-auto">
                    <ol class="breadcrumb breadcrumb-arrows mb-0" aria-label="breadcrumbs">
                        <li class="breadcrumb-item"><a href="#">Manage</a></li>
                        <li class="breadcrumb-item active" aria-current="page"><a href="#">Role</a></li>
                    </ol>
                </div>
            </div>
        </div>
    </div>

    <div class="page-body">
        <div class="container-xl">

            <x-alert type="success" />
            <x-alert type="warning" />

            @if ($errors->any())
                <div class="alert alert-danger" role="alert">
                    {!! implode('', $errors->all('<div>:message</div>')) !!}
                </div>
            @endif

            @include('main.manage-role.components.stat-cards')

            @include('main.manage-role.components.filter-card')

            @include('main.manage-role.components.user-table')

            {{-- Modal wajib di luar table/tbody agar markup tabel tidak pecah --}}
            @foreach ($users as $user)
                @if (!$user->isSuperAdmin() && $user->id !== auth()->id())
                    @include('main.manage-role.components.edit-user-modal', ['user' => $user])
                @endif
            @endforeach

        </div>
    </div>

@endsection
