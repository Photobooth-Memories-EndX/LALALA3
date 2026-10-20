@extends('layouts.sidebar-master') {{-- ⚠️ SESUAIKAN --}}

@section('content')
    <div class="page-header d-print-none mb-3">
        <div class="container-xl">
            <div class="mb-2">
                <a href="{{ route('manage.task.index', array_filter(['scope' => (request('scope') === 'non_kp' || request('parent_type') === 'offline_training') ? 'non_kp' : (request('scope') ?: null)])) }}" class="text-primary text-decoration-none fw-semibold d-inline-flex align-items-center gap-1">
                    <i class="ti ti-arrow-left"></i> Back
                </a>
            </div>
            <div class="row align-items-center">
                <div class="col">
                    <div class="page-pretitle">Task Management</div>
                    <h2 class="page-title">Create New Task</h2>
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