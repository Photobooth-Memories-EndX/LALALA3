{{-- resources/views/user/my-task/index.blade.php --}}
{{-- My Task — sisi user: daftar tugas dari training plan yang diikuti + upload jawaban. --}}
@extends('layouts.sidebar-master')

@section('content')
    <div class="page-header d-print-none mb-3">
        <div class="container-xl">
            <div class="mb-2">
                <a href="{{ route('user.learning.dashboard', ['learning_tab' => 'my-task']) }}" class="text-primary text-decoration-none fw-semibold d-inline-flex align-items-center gap-1">
                    <i class="ti ti-arrow-left"></i> Back
                </a>
            </div>
            <div class="row align-items-center">
                <div class="col">
                    <div class="page-pretitle">Learning</div>
                    <h2 class="page-title">My Task</h2>
                </div>
            </div>
        </div>
    </div>

    <div class="page-body">
        <div class="container-xl">
            @if (session('success'))
                <div class="alert alert-success">{{ session('success') }}</div>
            @endif
            @if ($errors->any())
                <div class="alert alert-danger">
                    <ul class="mb-0">
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            {{-- Ringkasan --}}
            <div class="row row-cards mb-3">
                <div class="col-6 col-md-3">
                    <div class="card"><div class="card-body text-center">
                        <div class="h1 mb-0">{{ $summary['not_started'] }}</div>
                        <div class="text-muted">Belum dikerjakan</div>
                    </div></div>
                </div>
                <div class="col-6 col-md-3">
                    <div class="card"><div class="card-body text-center">
                        <div class="h1 mb-0">{{ $summary['submitted'] }}</div>
                        <div class="text-muted">Menunggu koreksi</div>
                    </div></div>
                </div>
                <div class="col-6 col-md-3">
                    <div class="card"><div class="card-body text-center">
                        <div class="h1 mb-0">{{ $summary['completed'] }}</div>
                        <div class="text-muted">Selesai</div>
                    </div></div>
                </div>
                <div class="col-6 col-md-3">
                    <div class="card"><div class="card-body text-center">
                        <div class="h1 mb-0 text-danger">{{ $summary['overdue'] }}</div>
                        <div class="text-muted">Terlambat</div>
                    </div></div>
                </div>
            </div>

            {{-- Filter --}}
            <div class="btn-list mb-3">
                @foreach (['all' => 'Semua', 'not_started' => 'Belum dikerjakan', 'submitted' => 'Menunggu koreksi', 'completed' => 'Selesai', 'overdue' => 'Terlambat'] as $key => $label)
                    <a href="{{ route('my-task.index', ['status' => $key]) }}"
                        class="btn {{ $filter === $key ? 'btn-primary' : 'btn-outline-secondary' }} btn-sm">
                        {{ $label }}
                    </a>
                @endforeach
            </div>

            {{-- Daftar task --}}
            <div class="row row-cards">
                @forelse ($tasks as $task)
                    @php
                        $status = $task->userStatusFor(auth()->user());
                        $submission = $task->submissionFor(auth()->user());
                        $badges = [
                            'not_started' => '<span class="badge bg-secondary">Belum dikerjakan</span>',
                            'submitted'   => '<span class="badge bg-info">Menunggu koreksi</span>',
                            'completed'   => '<span class="badge bg-success">Selesai</span>',
                            'overdue'     => '<span class="badge bg-danger">Terlambat</span>',
                            'resubmit'    => '<span class="badge bg-warning text-dark">Perlu Revisi</span>',
                        ];
                    @endphp
                    <div class="col-12">
                        <div class="card">
                            <div class="card-body">
                                <div class="d-flex justify-content-between align-items-start flex-wrap gap-2">
                                    <div>
                                        <h3 class="card-title mb-1">{{ $task->title }}</h3>
                                        <div class="text-muted small mb-2">
                                            {{ $task->trainingPlan?->title }}
                                            @if ($task->deadline)
                                                &middot; Deadline: <strong class="{{ $task->isOverdueFor(auth()->user()) ? 'text-danger' : '' }}">{{ $task->deadline->format('d M Y H:i') }}</strong>
                                            @endif
                                        </div>
                                        @if ($task->description)
                                            <p class="text-muted">{{ $task->description }}</p>
                                        @endif
                                        @if ($task->attachment)
                                            <a href="{{ Storage::url($task->attachment) }}" target="_blank"
                                                class="btn btn-sm btn-outline-secondary">
                                                <i class="ti ti-download"></i> Unduh lampiran
                                            </a>
                                        @endif
                                    </div>
                                    <div>{!! $badges[$status] !!}</div>
                                </div>

                                @if ($status !== 'completed')
                                    <hr>
                                    @if ($task->isCourseAssignment())
                                        @php $tp = $task->linkedPlan ?? $task->trainingPlan; @endphp
                                        <div class="d-flex flex-wrap gap-2 align-items-center">
                                            @if ($tp)
                                                <a href="{{ route('user.playlist.show', $tp->slug) }}" class="btn btn-primary btn-sm">
                                                    <i class="ti ti-book-2 me-1"></i> Buka Course: {{ $tp->title }}
                                                </a>
                                            @elseif ($task->external_url)
                                                <a href="{{ $task->external_url }}" target="_blank" class="btn btn-primary btn-sm">
                                                    <i class="ti ti-external-link me-1"></i> Buka External Course
                                                </a>
                                            @endif
                                            <span class="text-muted small">Selesaikan course (100% progress) — task ini akan selesai secara otomatis.</span>
                                        </div>
                                    @else
                                        <form action="{{ route('my-task.submit', $task) }}" method="POST"
                                            enctype="multipart/form-data" class="row g-2 align-items-end">
                                            @csrf
                                            <div class="col-md-5">
                                                <label class="form-label">Jawaban (opsional)</label>
                                                <textarea name="answer" rows="2" class="form-control" placeholder="Tulis jawaban / catatan...">{{ old('answer', $submission?->answer) }}</textarea>
                                            </div>
                                            <div class="col-md-5">
                                                <label class="form-label">Upload file jawaban</label>
                                                <input type="file" name="file" class="form-control" required>
                                                @if ($submission?->file_path)
                                                    <div class="form-hint">Sudah ada file terkumpul — upload lagi untuk mengganti.</div>
                                                @endif
                                            </div>
                                            <div class="col-md-2">
                                                <button type="submit" class="btn btn-primary w-100">
                                                    <i class="ti ti-upload"></i> Kumpulkan
                                                </button>
                                            </div>
                                        </form>
                                    @endif
                                @else
                                    <div class="alert alert-success mt-3 mb-0">
                                        <i class="ti ti-check"></i> {{ $task->isCourseAssignment() ? 'Course telah selesai (100%).' : 'Task selesai dikoreksi admin.' }}
                                    </div>
                                @endif
                            </div>
                        </div>
                    </div>
                @empty
                    <div class="col-12">
                        <div class="alert alert-info">
                            @if ($filter === 'all')
                                Belum ada task. Buka course yang kamu ikuti dulu — task akan muncul di sini setelah kamu mengklik course tersebut.
                            @else
                                Tidak ada task pada filter ini.
                            @endif
                        </div>
                    </div>
                @endforelse
            </div>
        </div>
    </div>
@endsection
