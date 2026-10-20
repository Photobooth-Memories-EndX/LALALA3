{{-- resources/views/manage/task/trash.blade.php --}}
{{-- Soft-deleted tasks: restore them or delete forever. --}}
@extends('layouts.sidebar-master')

@section('content')
    <div class="page-header d-print-none mb-3">
        <div class="container-xl">
            <div class="mb-2">
                <a href="{{ route('manage.task.index') }}" class="text-primary text-decoration-none fw-semibold d-inline-flex align-items-center gap-1">
                    <i class="ti ti-arrow-left"></i> Back
                </a>
            </div>
            <div class="row align-items-center">
                <div class="col">
                    <div class="page-pretitle">
                        <a href="{{ route('manage.task.index') }}">Task Management</a>
                    </div>
                    <h2 class="page-title">
                        <i class="ti ti-trash me-1"></i>Trash
                    </h2>
                </div>
            </div>
        </div>
    </div>

    <div class="page-body">
        <div class="container-xl">
            @if (session('success'))
                <div class="alert alert-success">{{ session('success') }}</div>
            @endif

            <div class="card">
                <div class="table-responsive">
                    <table class="table table-vcenter mb-0">
                        <thead>
                            <tr>
                                <th class="w-1">NO</th>
                                <th>TASK</th>
                                <th>TYPE</th>
                                <th>PLACEMENT</th>
                                <th>DELETED AT</th>
                                <th class="text-end">ACTION</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($tasks as $i => $task)
                                <tr>
                                    <td class="text-muted">{{ ($tasks->firstItem() ?? 0) + $i }}</td>
                                    <td class="fw-semibold">{{ $task->title }}</td>
                                    <td>
                                        <span class="badge bg-primary-lt text-primary">
                                            {{ \App\Models\Task::TYPES[$task->type] ?? $task->type }}
                                        </span>
                                    </td>
                                    <td>
                                        <span class="badge bg-secondary-lt text-secondary">
                                            {{ \App\Models\Task::PARENT_TYPES[$task->parent_type] ?? $task->parent_type }}
                                        </span>
                                        <span class="text-muted small ms-1">{{ $task->parentName() }}</span>
                                    </td>
                                    <td class="text-muted text-nowrap">{{ $task->deleted_at?->format('d M Y H:i') }}</td>
                                    <td class="text-end">
                                        <div class="d-inline-flex gap-1">
                                            <form action="{{ route('manage.task.restore', $task->id) }}" method="POST" class="d-inline">
                                                @csrf
                                                <button class="btn btn-sm btn-outline-success">
                                                    <i class="ti ti-restore me-1"></i>Restore
                                                </button>
                                            </form>
                                            <form action="{{ route('manage.task.force-destroy', $task->id) }}" method="POST"
                                                  class="d-inline" onsubmit="return confirm('Delete this task FOREVER? Questions, responses and files will be wiped and cannot be restored.')">
                                                @csrf @method('DELETE')
                                                <button class="btn btn-sm btn-outline-danger"><i class="ti ti-trash-x me-1"></i>Delete forever</button>
                                            </form>
                                        </div>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="6" class="text-center text-muted py-4">
                                        <i class="ti ti-trash fs-1 d-block mb-2"></i>
                                        Trash is empty.
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
                @if ($tasks->hasPages())
                    <div class="card-footer">{{ $tasks->links() }}</div>
                @endif
            </div>
        </div>
    </div>
@endsection
