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
            @include('manage.task._form')
        </div>
    </div>
@endsection