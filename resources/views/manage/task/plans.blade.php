{{-- resources/views/manage/task/plans.blade.php --}}
{{-- Langkah 1 (course-first): tabel course ala Assessment Management. Klik course → masuk ke task-tasknya. --}}
@extends('layouts.sidebar-master')

@section('content')

    @include('main.assesment.partials.style')

    {{-- ===== Header ala Assessment Management ===== --}}
    <div class="assessment-page-header page-header d-print-none mb-3">
        <div class="container-xl">

            <div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2">
                <div class="assessment-back">
                    <a href="{{ route('manage.index') }}" class="assessment-back-link">
                        <i class="ti ti-arrow-left me-1" aria-hidden="true"></i>
                        Back
                    </a>
                </div>
                <div class="assessment-breadcrumb">
                    <ol class="breadcrumb breadcrumb-arrows mb-0" aria-label="breadcrumbs">
                        <li class="breadcrumb-item"><a href="{{ route('manage.index') }}">Manage</a></li>
                        <li class="breadcrumb-item active" aria-current="page">Task</li>
                    </ol>
                </div>
            </div>

            <div class="row align-items-center">
                <div class="col">
                    <div class="page-pretitle text-muted">
                        Learning Management System
                    </div>
                    <h2 class="owl-page-title mb-1">
                        Task Management
                    </h2>
                    <div class="owl-page-subtitle">
                        Manage course tasks, assignments, and submission reviews.
                    </div>
                </div>
                <div class="col-auto ms-auto">
                    <div class="btn-list">
                        <a href="{{ route('manage.task.create') }}"
                           class="btn btn-primary d-inline-flex align-items-center">
                            <i class="fas fa-plus me-2"></i>
                            <span>Create New Task</span>
                        </a>
                    </div>
                </div>
            </div>

        </div>
    </div>

    <div class="page-body">
        <div class="container-xl">

            <x-alert type="success" />

            {{-- ===== Stat cards ===== --}}
            <div class="row mb-4">

                <div class="col-md-6 col-lg-3 mb-3">
                    <div class="card owl-stat-card h-100">
                        <div class="card-body p-3">
                            <div class="d-flex align-items-center">
                                <div class="owl-stat-icon bg-primary">
                                    <i class="fas fa-book"></i>
                                </div>
                                <div class="owl-stat-content">
                                    <div class="owl-stat-label">Total Course</div>
                                    <div class="owl-stat-number">{{ $stats->count() }}</div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="col-md-6 col-lg-3 mb-3">
                    <div class="card owl-stat-card h-100">
                        <div class="card-body p-3">
                            <div class="d-flex align-items-center">
                                <div class="owl-stat-icon bg-info">
                                    <i class="fas fa-clipboard-list"></i>
                                </div>
                                <div class="owl-stat-content">
                                    <div class="owl-stat-label">Total Task</div>
                                    <div class="owl-stat-number">{{ $stats->sum('tasks_count') }}</div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="col-md-6 col-lg-3 mb-3">
                    <div class="card owl-stat-card h-100">
                        <div class="card-body p-3">
                            <div class="d-flex align-items-center">
                                <div class="owl-stat-icon bg-success">
                                    <i class="fas fa-check-circle"></i>
                                </div>
                                <div class="owl-stat-content">
                                    <div class="owl-stat-label">Published</div>
                                    <div class="owl-stat-number">{{ $stats->sum('published_tasks_count') }}</div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="col-md-6 col-lg-3 mb-3">
                    <div class="card owl-stat-card h-100">
                        <div class="card-body p-3">
                            <div class="d-flex align-items-center">
                                <div class="owl-stat-icon bg-warning">
                                    <i class="fas fa-clock"></i>
                                </div>
                                <div class="owl-stat-content">
                                    <div class="owl-stat-label">Pending Review</div>
                                    <div class="owl-stat-number">{{ $pendingByPlan->sum() }}</div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

            </div>

            {{-- ===== Tabel course ===== --}}
            <div class="card owl-main-card">

                <div class="card-header bg-white border-0">
                    <div>
                        <h3 class="card-title mb-1">
                            Course List
                        </h3>
                        <div class="text-muted small">
                            Pilih course untuk mengelola task-tasknya.
                        </div>
                    </div>
                </div>

                <div class="card-body border-top border-bottom py-3">
                    <form method="GET" action="{{ route('manage.task.index') }}">
                        <label class="form-label small text-muted mb-2" for="taskCourseSearch">
                            Quick Filter
                        </label>
                        <div class="d-flex gap-2">
                            <div class="input-icon flex-fill">
                                <span class="input-icon-addon">
                                    <i class="ti ti-search"></i>
                                </span>
                                <input type="search"
                                       name="search"
                                       id="taskCourseSearch"
                                       class="form-control"
                                       value="{{ request('search') }}"
                                       placeholder="Search course..."
                                       autocomplete="off">
                            </div>
                            <button type="submit" class="btn btn-primary" title="Search" aria-label="Search">
                                <i class="ti ti-search"></i>
                            </button>
                            <a href="{{ route('manage.task.index') }}" class="btn btn-outline-secondary" title="Reset" aria-label="Reset">
                                <i class="ti ti-rotate-clockwise"></i>
                            </a>
                        </div>
                    </form>
                </div>

                <div class="table-responsive">
                    <table class="table table-vcenter">
                        <thead>
                            <tr>
                                <th class="w-1">NO</th>
                                <th>COURSE</th>
                                <th class="text-center">TOTAL TASK</th>
                                <th class="text-center">PUBLISHED</th>
                                <th class="text-center">DRAFT</th>
                                <th class="text-center">PENDING REVIEW</th>
                                <th class="text-end">ACTION</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($plans as $i => $plan)
                                @php $pending = $pendingByPlan[$plan->id] ?? 0; @endphp
                                <tr>
                                    <td class="text-muted">{{ ($plans->firstItem() ?? 0) + $i }}</td>

                                    <td>
                                        <a href="{{ route('manage.task.plan', $plan) }}" class="fw-semibold">
                                            {{ $plan->title }}
                                        </a>
                                    </td>
                                    <td class="text-center">
                                        <span class="badge bg-secondary">{{ $plan->tasks_count }}</span>
                                    </td>
                                    <td class="text-center">{{ $plan->published_tasks_count }}</td>
                                    <td class="text-center">{{ $plan->draft_tasks_count }}</td>
                                    <td class="text-center">
                                        @if ($pending > 0)
                                            <span class="badge bg-warning text-dark">{{ $pending }}</span>
                                        @else
                                            <span class="text-muted">-</span>
                                        @endif
                                    </td>
                                    <td class="text-end">
                                        <a href="{{ route('manage.task.plan', $plan) }}"
                                           class="btn btn-sm btn-outline-primary">
                                            Manage Tasks <i class="ti ti-arrow-right ms-1"></i>
                                        </a>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="7" class="text-center text-muted py-4">
                                        Belum ada course. Buat task baru untuk mulai.
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
@if ($plans->hasPages())
    <div class="card-footer d-flex justify-content-center py-3">
        {{ $plans->links() }}
    </div>
@endif
            </div>

        </div>
    </div>

@endsection
