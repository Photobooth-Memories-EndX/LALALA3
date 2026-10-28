@extends('layouts.sidebar-master') {{-- ⚠️ SESUAIKAN --}}

@section('content')
    <div class="page-header d-print-none mb-3">
        <div class="container-xl">
            <div class="mb-2">
                <a href="{{ !empty($task->parentRouteParams()) ? route('manage.task.parent', $task->parentRouteParams()) : route('manage.task.index') }}" class="text-primary text-decoration-none fw-semibold d-inline-flex align-items-center gap-1">
                    <i class="ti ti-arrow-left"></i> Back
                </a>
            </div>
            <div class="row align-items-center">
                <div class="col">
                    <div class="page-pretitle">Task Management</div>
                    <h2 class="page-title">Edit Task</h2>
                </div>
            </div>
        </div>
    </div>

    <div class="page-body">
        <div class="container-xl">
            @if (session('success'))
                <div class="alert alert-success alert-dismissible d-flex align-items-center gap-2 mb-3 rounded-3" role="alert">
                    <i class="ti ti-circle-check fs-2 text-success"></i>
                    <div>{{ session('success') }}</div>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            @endif
            @if (session('error'))
                <div class="alert alert-danger alert-dismissible d-flex align-items-center gap-2 mb-3 rounded-3" role="alert">
                    <i class="ti ti-alert-circle fs-2 text-danger"></i>
                    <div>{{ session('error') }}</div>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            @endif
            @include('manage.task._form')
        </div>
    </div>
@endsection