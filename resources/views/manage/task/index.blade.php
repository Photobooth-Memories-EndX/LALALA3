{{-- resources/views/manage/task/index.blade.php --}}
{{-- Tasks of one parent (playlist / content / offline training). --}}
@extends('layouts.sidebar-master')

@section('content')
    <div class="page-header d-print-none mb-3">
        <div class="container-xl">
            <div class="row align-items-center">
                <div class="col">
                    <div class="page-pretitle">
                        <a href="{{ route('manage.task.index') }}">Task Management</a>
                    </div>
                    <h2 class="page-title">
                        <span class="badge bg-secondary me-2 align-middle" style="font-size:.65rem">
                            {{ \App\Models\Task::PARENT_TYPES[$parentType] ?? $parentType }}
                        </span>
                        {{ $parentName }}
                    </h2>
                </div>
                <div class="col-auto ms-auto">
                    {{-- Parent is auto-embedded: no need to pick it again in the form --}}
                    <a href="{{ route('manage.task.create', ['parent_type' => $parentType, 'parent_id' => $parentId]) }}" class="btn btn-primary">
                        <i class="ti ti-plus"></i> Create New Task
                    </a>
                </div>
            </div>
        </div>
    </div>

    <div class="page-body">
        <div class="container-xl">
            @if (session('success'))
                <div class="alert alert-success">{{ session('success') }}</div>
            @endif

            <form method="GET" class="row g-2 mb-3">
                <div class="col-md-6">
                    <input type="text" name="search" class="form-control" placeholder="Search tasks..."
                        value="{{ request('search') }}">
                </div>
                <div class="col-md-3">
                    <select name="status" class="form-select" onchange="this.form.submit()">
                        <option value="">All statuses</option>
                        <option value="published" @selected(request('status') === 'published')>Published</option>
                        <option value="draft" @selected(request('status') === 'draft')>Draft</option>
                    </select>
                </div>
                <div class="col-md-3">
                    <button class="btn btn-outline-secondary w-100" type="submit">
                        <i class="ti ti-search"></i> Search
                    </button>
                </div>
            </form>

            <div class="card">
                <div class="table-responsive">
                    <table class="table table-vcenter">
                        <thead>
                            <tr>
                                <th>Task</th>
                                <th>Deadline</th>
                                <th>Status</th>
                                <th>Submissions</th>
                                <th class="w-1">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($tasks as $task)
                                <tr>
                                    <td>
                                        <div class="fw-bold">{{ $task->title }}</div>
                                    </td>
                                    <td>{{ $task->deadline?->format('d M Y H:i') ?? '-' }}</td>
                                    <td>
                                        @if ($task->status === 'published')
                                            <span class="badge bg-success">Published</span>
                                        @else
                                            <span class="badge bg-warning">Draft</span>
                                        @endif
                                    </td>
                                    <td>
                                        <span class="badge bg-info">{{ $task->pending_submissions_count }} pending</span>
                                        <span class="badge bg-success">{{ $task->completed_submissions_count }} completed</span>
                                    </td>
                                    <td>
                                        <div class="btn-list flex-nowrap">
                                            <a href="{{ route('manage.task.submissions', $task) }}"
                                                class="btn btn-sm btn-outline-primary">
                                                Submissions ({{ $task->pending_submissions_count }})
                                            </a>
                                            <a href="{{ route('manage.task.edit', $task) }}"
                                                class="btn btn-sm btn-outline-secondary">Edit</a>
                                            @if ($task->status === 'draft')
                                                <form action="{{ route('manage.task.publish', $task) }}" method="POST"
                                                    class="d-inline">
                                                    @csrf
                                                    <button class="btn btn-sm btn-success">Publish</button>
                                                </form>
                                            @endif
                                            <form action="{{ route('manage.task.destroy', $task) }}" method="POST"
                                                class="d-inline" onsubmit="return confirm('Delete this task?')">
                                                @csrf @method('DELETE')
                                                <button class="btn btn-sm btn-outline-danger">Delete</button>
                                            </form>
                                        </div>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="5" class="text-center text-muted py-4">
                                        No tasks here yet.
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>

            <a href="{{ route('manage.task.index') }}" class="btn btn-outline-secondary mt-3">
                <i class="ti ti-arrow-left"></i> Back to parent list
            </a>
        </div>
    </div>
@endsection
