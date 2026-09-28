{{-- resources/views/manage/task/index.blade.php --}}
{{-- Langkah 2 (course-first): daftar task milik satu training plan terpilih. --}}
{{-- Ganti file lama dengan file ini. --}}
@extends('layouts.sidebar-master')

@section('content')
    <div class="page-header d-print-none mb-3">
        <div class="container-xl">
            <div class="row align-items-center">
                <div class="col">
                    <div class="page-pretitle">
                        <a href="{{ route('manage.task.index') }}">Task Management</a>
                    </div>
                    <h2 class="page-title">{{ $trainingPlan->title }}</h2>
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
            @if (session('success'))
                <div class="alert alert-success">{{ session('success') }}</div>
            @endif

            <form method="GET" class="row g-2 mb-3">
                <div class="col-md-6">
                    <input type="text" name="search" class="form-control" placeholder="Cari task..."
                        value="{{ request('search') }}">
                </div>
                <div class="col-md-3">
                    <select name="status" class="form-select" onchange="this.form.submit()">
                        <option value="">Semua status</option>
                        <option value="published" @selected(request('status') === 'published')>Published</option>
                        <option value="draft" @selected(request('status') === 'draft')>Draft</option>
                    </select>
                </div>
                <div class="col-md-3">
                    <button class="btn btn-outline-secondary w-100" type="submit">
                        <i class="ti ti-search"></i> Cari
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
                                <th>Submission</th>
                                <th class="w-1">Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($tasks as $task)
                                <tr>
                                    <td>
                                        <div class="fw-bold">{{ $task->title }}</div>
                                        <div class="text-muted small">
                                            {{ $task->content?->title ?? 'Langsung ke course' }}
                                        </div>
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
                                        <span class="badge bg-info">{{ $task->pending_submissions_count }} menunggu</span>
                                        <span class="badge bg-success">{{ $task->completed_submissions_count }} selesai</span>
                                    </td>
                                    <td>
                                        <div class="btn-list flex-nowrap">
                                            <a href="{{ route('manage.task.submissions', $task) }}"
                                                class="btn btn-sm btn-outline-primary">
                                                Submission ({{ $task->pending_submissions_count }})
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
                                                class="d-inline" onsubmit="return confirm('Hapus task ini?')">
                                                @csrf @method('DELETE')
                                                <button class="btn btn-sm btn-outline-danger">Hapus</button>
                                            </form>
                                        </div>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="5" class="text-center text-muted py-4">
                                        Belum ada task di training plan ini.
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>

            <a href="{{ route('manage.task.index') }}" class="btn btn-outline-secondary mt-3">
                <i class="ti ti-arrow-left"></i> Kembali ke daftar training plan
            </a>
        </div>
    </div>
@endsection
