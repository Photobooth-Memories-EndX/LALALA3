@extends('layouts.sidebar-master')

@section('content')
    <div class="page-header d-print-none">
        <div class="container-xl">
            <div class="row g-2 align-items-center">
                <div class="col">
                    <h2 class="page-title">Manage Role</h2>
                    <div class="text-muted mt-1">
                        Kelola role, division, department, team, dan org unit user.
                    </div>
                </div>

                <div class="col-auto ms-auto d-print-none">
                    <ol class="breadcrumb breadcrumb-arrows" aria-label="breadcrumbs">
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
                <div class="alert alert-danger">
                    {!! implode('', $errors->all('<div>:message</div>')) !!}
                </div>
            @endif

            @include('main.manage-role.components.stat-cards')

            @include('main.manage-role.components.filter-card')

            @include('main.manage-role.components.user-table')

            {{-- Modal wajib di luar table/tbody agar tidak bug --}}
            @foreach ($users as $user)
                @if (!$user->isSuperAdmin() && $user->id !== auth()->id())
                    @include('main.manage-role.components.edit-user-modal', [
                        'user' => $user,
                    ])
                @endif
            @endforeach

        </div>
    </div>
@endsection
