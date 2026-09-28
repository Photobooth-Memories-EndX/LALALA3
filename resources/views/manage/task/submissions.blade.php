{{-- resources/views/manage/task/submissions.blade.php --}}
{{-- Review submission: admin melihat pengumpulan user, unduh file, tandai selesai. --}}
@extends('layouts.sidebar-master')

@section('content')
    <div class="page-header d-print-none mb-3">
        <div class="container-xl">
            <div class="row align-items-center">
                <div class="col">
                    <div class="page-pretitle">
                        <a href="{{ route('manage.task.index') }}">Task Management</a>
                        &nbsp;/&nbsp;
                        <a href="{{ route('manage.task.plan', $task->trainingPlan) }}">{{ $task->trainingPlan->title }}</a>
                    </div>
                    <h2 class="page-title">Submission: {{ $task->title }}</h2>
                    <div class="text-muted">
                        @if ($task->deadline)
                            Deadline: {{ $task->deadline->format('d M Y H:i') }}
                        @else
                            Tanpa deadline
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="page-body">
        <div class="container-xl">
            @if (session('success'))
                <div class="alert alert-success">{{ session('success') }}</div>
            @endif

            <form method="GET" class="mb-3">
                <select name="status" class="form-select w-auto d-inline-block" onchange="this.form.submit()">
                    <option value="">Semua status</option>
                    <option value="pending" @selected(request('status') === 'pending')>Menunggu koreksi</option>
                    <option value="completed" @selected(request('status') === 'completed')>Selesai</option>
                </select>
            </form>

            <div class="card">
                <div class="table-responsive">
                    <table class="table table-vcenter">
                        <thead>
                            <tr>
                                <th>User</th>
                                <th>Dikumpulkan</th>
                                <th>Jawaban / File</th>
                                <th>Status</th>
                                <th>Nilai</th>
                                <th class="w-1">Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($submissions as $submission)
                                <tr>
                                    <td>
                                        <div class="fw-bold">{{ $submission->user?->name ?? 'User #' . $submission->user_id }}</div>
                                        <div class="text-muted small">{{ $submission->user?->email }}</div>
                                    </td>
                                    <td>
                                        {{ $submission->submitted_at?->format('d M Y H:i') ?? '-' }}
                                        @if ($task->deadline && $submission->submitted_at && $submission->submitted_at->gt($task->deadline))
                                            <span class="badge bg-danger ms-1">Terlambat</span>
                                        @endif
                                    </td>
                                    <td>
                                        @if ($submission->answer)
                                            <div class="text-muted small mb-1">{{ Str::limit($submission->answer, 120) }}</div>
                                        @endif
                                        @if ($submission->file_path)
                                            <a href="{{ Storage::url($submission->file_path) }}" target="_blank"
                                                class="btn btn-sm btn-outline-secondary">
                                                <i class="ti ti-download"></i> Unduh file
                                            </a>
                                        @else
                                            <span class="text-muted">-</span>
                                        @endif
                                    </td>
                                    <td>
                                        @if ($submission->status === 'completed')
                                            <span class="badge bg-success">Selesai</span>
                                        @else
                                            <span class="badge bg-info">Menunggu koreksi</span>
                                        @endif
                                    </td>
                                    <td>
                                        @if (!is_null($submission->score))
                                            <span class="badge bg-primary fs-5">{{ $submission->score }}</span>
                                        @else
                                            <span class="text-muted">-</span>
                                        @endif
                                        @if ($submission->feedback)
                                            <div class="text-muted small mt-1" style="max-width: 200px;"
                                                title="{{ $submission->feedback }}">
                                                <i class="ti ti-message-circle me-1"></i>{{ Str::limit($submission->feedback, 60) }}
                                            </div>
                                        @endif
                                    </td>
                                    <td>
                                        @if ($submission->status !== 'completed')
                                            <form action="{{ route('manage.task.submissions.complete', [$task, $submission]) }}"
                                                method="POST" style="min-width: 220px;"
                                                onsubmit="return confirm('Tandai submission ini selesai?')">
                                                @csrf
                                                <div class="input-group input-group-sm mb-2">
                                                    <span class="input-group-text">Nilai</span>
                                                    <input type="number" name="score" class="form-control"
                                                        min="0" max="100" placeholder="0–100">
                                                </div>
                                                <textarea name="feedback" rows="2" class="form-control form-control-sm mb-2"
                                                    placeholder="Feedback untuk user (opsional)"></textarea>
                                                <button class="btn btn-sm btn-success w-100">
                                                    <i class="ti ti-check"></i> Tandai selesai
                                                </button>
                                            </form>
                                        @else
                                            <span class="text-muted">-</span>
                                        @endif
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="6" class="text-center text-muted py-4">
                                        Belum ada user yang mengumpulkan jawaban.
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>

            <a href="{{ route('manage.task.plan', $task->trainingPlan) }}" class="btn btn-outline-secondary mt-3">
                <i class="ti ti-arrow-left"></i> Kembali ke daftar task
            </a>
        </div>
    </div>
@endsection
