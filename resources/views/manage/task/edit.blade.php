@extends('layouts.sidebar-master') {{-- ⚠️ SESUAIKAN --}}

@section('content')
    <div class="page-header d-print-none mb-3">
        <div class="container-xl">
            <div class="row align-items-center">
                <div class="col">
                    <div class="page-pretitle">Task Management</div>
                    <h2 class="page-title">Edit Task</h2>
                </div>
                <div class="col-auto ms-auto">
                    <a href="{{ route('manage.task.index') }}" class="btn btn-outline-secondary">
                        <i class="ti ti-arrow-left"></i> Back
                    </a>
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