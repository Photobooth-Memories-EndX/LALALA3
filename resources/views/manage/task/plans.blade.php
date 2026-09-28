{{-- resources/views/manage/task/plans.blade.php --}}
{{-- Langkah 1 (course-first): admin pilih training plan dulu sebelum kelola task. --}}
@extends('layouts.sidebar-master')

@section('content')
    <div class="page-header d-print-none mb-3">
        <div class="container-xl">
            <div class="row align-items-center">
                <div class="col">
                    <div class="page-pretitle">Manage LMS</div>
                    <h2 class="page-title">Task Management</h2>
                    <div class="text-muted">Pilih training plan untuk mengelola task-nya.</div>
                </div>
                <div class="col-auto ms-auto">
                    <a href="{{ route('manage.task.create') }}" class="btn btn-primary">
                        <i class="ti ti-plus"></i> Create New Task
                    </a>
                </div>
            </div>
        </div>
    </div>

    <div class="page-body">
        <div class="container-xl">
            <form method="GET" class="mb-3">
                <div class="input-group">
                    <input type="text" name="search" class="form-control" placeholder="Cari training plan..."
                        value="{{ request('search') }}">
                    <button class="btn btn-outline-secondary" type="submit">
                        <i class="ti ti-search"></i> Cari
                    </button>
                </div>
            </form>

            @if (session('success'))
                <div class="alert alert-success">{{ session('success') }}</div>
            @endif

            <div class="row row-cards">
                @forelse ($plans as $plan)
                    <div class="col-md-6 col-lg-4">
                        <div class="card h-100">
                            <div class="card-body">
                                <h3 class="card-title mb-2">
                                    <a href="{{ route('manage.task.plan', $plan) }}">{{ $plan->title }}</a>
                                </h3>
                                <div class="d-flex gap-2 flex-wrap">
                                    <span class="badge bg-secondary">{{ $plan->tasks_count }} task</span>
                                    <span class="badge bg-success">{{ $plan->published_tasks_count }} published</span>
                                    <span class="badge bg-warning">{{ $plan->draft_tasks_count }} draft</span>
                                </div>
                            </div>
                            <div class="card-footer">
                                <a href="{{ route('manage.task.plan', $plan) }}" class="btn btn-outline-primary w-100">
                                    Kelola Task <i class="ti ti-arrow-right"></i>
                                </a>
                            </div>
                        </div>
                    </div>
                @empty
                    <div class="col-12">
                        <div class="alert alert-info">Belum ada training plan.</div>
                    </div>
                @endforelse
            </div>
        </div>
    </div>
@endsection
