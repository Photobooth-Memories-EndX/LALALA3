{{-- Hero tab My Task (disuntik ke #learning-hero-region oleh dashboard-script) --}}
<template data-learning-my-task-hero>
    <x-shared.hero-learning-my-task :stats="[
        ['label' => 'Total Task', 'value' => $summary['total'] ?? 0],
        ['label' => 'Belum Dikerjakan', 'value' => $summary['not_started'] ?? 0],
        ['label' => 'Selesai', 'value' => $summary['completed'] ?? 0],
        ['label' => 'Terlambat', 'value' => $summary['overdue'] ?? 0],
    ]" />
</template>

@php
    $statusLabels = [
        'all'         => 'Semua',
        'not_started' => 'Belum dikerjakan',
        'submitted'   => 'Menunggu koreksi',
        'completed'   => 'Selesai',
        'overdue'     => 'Terlambat',
    ];
    $statusBadges = [
        'not_started' => 'bg-secondary-lt text-secondary',
        'submitted'   => 'bg-warning-lt text-warning',
        'completed'   => 'bg-success-lt text-success',
        'overdue'     => 'bg-danger-lt text-danger',
    ];
    $filterUrl = fn ($key) => route('user.learning.dashboard', array_filter([
        'learning_tab'   => 'my-task',
        'my_task_status' => $key === 'all' ? null : $key,
    ]));
@endphp

{{-- Filter status --}}
<div class="card border-0 shadow-sm rounded-4 mb-3">
    <div class="card-body py-2">
        <div class="d-flex flex-wrap gap-2">
            @foreach ($statusLabels as $key => $label)
                <a href="{{ $filterUrl($key) }}"
                   class="btn {{ $filter === $key ? 'btn-primary' : 'btn-white' }} btn-sm">
                    {{ $label }}
                    @if ($key !== 'all')
                        <span class="badge {{ $filter === $key ? 'bg-white text-primary' : 'bg-primary-lt text-primary' }} ms-1">{{ $summary[$key] ?? 0 }}</span>
                    @endif
                </a>
            @endforeach
        </div>
    </div>
</div>

{{-- Daftar task --}}
<div class="row g-3">
    @forelse ($tasks as $task)
        @php
            $status     = $task->userStatusFor(auth()->user());
            $submission = $task->submissionFor(auth()->user());
        @endphp
        <div class="col-12">
            <div class="card border-0 shadow-sm rounded-4">
                <div class="card-body p-4">
                    <div class="d-flex flex-wrap align-items-start justify-content-between gap-2 mb-2">
                        <div>
                            <h3 class="card-title mb-1">{{ $task->title }}</h3>
                            <div class="text-muted small">
                                <i class="ti ti-book-2 me-1"></i>{{ $task->trainingPlan->title ?? '—' }}
                                @if ($task->deadline)
                                    <span class="ms-2"><i class="ti ti-calendar-due me-1"></i>Deadline: {{ $task->deadline->format('d M Y H:i') }}</span>
                                @endif
                            </div>
                        </div>
                        <span class="badge {{ $statusBadges[$status] }}">{{ $statusLabels[$status] }}</span>
                    </div>

                    @if ($task->description)
                        <p class="text-muted mb-2">{{ $task->description }}</p>
                    @endif

                    @if ($task->attachment)
                        <div class="mb-3">
                            <a href="{{ Storage::url($task->attachment) }}" target="_blank" class="btn btn-sm btn-outline-primary">
                                <i class="ti ti-download me-1"></i>Unduh lampiran tugas
                            </a>
                        </div>
                    @endif

                    @if ($status === 'completed')
                        <div class="alert alert-success mb-0">
                            <i class="ti ti-check me-1"></i>Selesai dikoreksi admin.
                            @if ($submission && !is_null($submission->score))
                                <span class="badge bg-success ms-2">Nilai: {{ $submission->score }}</span>
                            @endif
                            @if ($submission && $submission->file_path)
                                <a href="{{ Storage::url($submission->file_path) }}" target="_blank" class="alert-link ms-2">Lihat jawabanmu</a>
                            @endif
                        </div>
                        @if ($submission && $submission->feedback)
                            <div class="alert alert-info mt-2 mb-0">
                                <i class="ti ti-message-circle me-1"></i>
                                <strong>Feedback admin:</strong><br>{{ $submission->feedback }}
                            </div>
                        @endif
                    @else
                        @if ($submission)
                            <div class="alert alert-warning d-flex flex-wrap align-items-center justify-content-between gap-2">
                                <div>
                                    <i class="ti ti-clock me-1"></i>
                                    Jawaban dikumpulkan {{ $submission->submitted_at?->format('d M Y H:i') }} — menunggu koreksi admin.
                                    <a href="{{ Storage::url($submission->file_path) }}" target="_blank" class="alert-link ms-1">Lihat file</a>
                                </div>
                                <span class="badge bg-warning text-dark">Pending</span>
                            </div>
                        @endif

                        <form action="{{ route('my-task.submit', $task) }}" method="POST" enctype="multipart/form-data" class="mt-2">
                            @csrf
                            <div class="mb-2">
                                <label class="form-label small fw-semibold">
                                    {{ $submission ? 'Perbarui jawaban (file lama diganti)' : 'Kumpulkan jawaban' }}
                                </label>
                                <input type="file" name="file" class="form-control" required>
                                <div class="form-hint">Maksimal 20 MB.</div>
                            </div>
                            <div class="mb-3">
                                <textarea name="answer" rows="2" class="form-control" placeholder="Catatan jawaban (opsional)">{{ old('answer', $submission->answer ?? '') }}</textarea>
                            </div>
                            <button type="submit" class="btn btn-primary">
                                <i class="ti ti-upload me-1"></i>{{ $submission ? 'Kirim ulang' : 'Kumpulkan' }}
                            </button>
                        </form>
                    @endif
                </div>
            </div>
        </div>
    @empty
        <div class="col-12">
            <div class="card border-0 shadow-sm rounded-4">
                <div class="card-body p-4 text-center text-muted">
                    <i class="ti ti-list-check fs-1 d-block mb-2"></i>
                    @if ($filter === 'all')
                        Belum ada task. Buka course yang kamu ikuti dulu — task akan muncul di sini setelah kamu mengklik course tersebut.
                    @else
                        Tidak ada task pada filter ini.
                    @endif
                </div>
            </div>
        </div>
    @endforelse
</div>
