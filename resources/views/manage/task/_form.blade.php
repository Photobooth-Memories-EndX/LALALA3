blade
@extends('layouts.app')
{{-- Form bersama create & edit --}}
<form method="POST"
      action="{{ isset($task) ? route('manage.task.update', $task) : route('manage.task.store') }}"
      enctype="multipart/form-data"
      id="taskForm">
    @csrf
    @if (isset($task))
        @method('PUT')
    @endif

    <div class="card">
        <div class="card-header">
            <h3 class="card-title">{{ isset($task) ? 'Edit Task' : 'Detail Task' }}</h3>
        </div>
        <div class="card-body">
            <div class="row g-4">

                {{-- Course (Training Plan) --}}
                <div class="col-md-12">
                    <label class="form-label required" for="training_plan_id">Course</label>
                    <select class="form-select @error('training_plan_id') is-invalid @enderror"
                            id="training_plan_id" name="training_plan_id" required>
                        <option value="">— Pilih Course —</option>
                        @foreach ($trainingPlans as $trainingPlan)
                            <option value="{{ $trainingPlan->id }}"
                                {{ (string) old('training_plan_id', $task->training_plan_id ?? '') === (string) $trainingPlan->id ? 'selected' : '' }}>
                                {{ $trainingPlan->title }}
                            </option>
                        @endforeach
                    </select>
                    @error('training_plan_id')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                </div>


                {{-- Judul --}}
                <div class="col-md-8">
                    <label class="form-label required" for="title">Judul Task</label>
                    <input type="text" class="form-control @error('title') is-invalid @enderror"
                           id="title" name="title"
                           value="{{ old('title', $task->title ?? '') }}" required>
                    @error('title')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>

                {{-- Tipe --}}
                <div class="col-md-4">
                    <label class="form-label required" for="type">Tipe Task</label>
                    <select class="form-select @error('type') is-invalid @enderror" id="type" name="type" required>
                        @foreach (['assignment' => 'Assignment', 'quiz' => 'Quiz', 'reading' => 'Reading', 'other' => 'Lainnya'] as $value => $label)
                            <option value="{{ $value }}"
                                {{ old('type', $task->type ?? 'assignment') === $value ? 'selected' : '' }}>
                                {{ $label }}
                            </option>
                        @endforeach
                    </select>
                    @error('type')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                </div>

                {{-- Deskripsi --}}
                <div class="col-12">
                    <label class="form-label" for="description">Deskripsi</label>
                    <textarea class="form-control @error('description') is-invalid @enderror"
                              id="description" name="description"
                              rows="4">{{ old('description', $task->description ?? '') }}</textarea>
                    @error('description')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                </div>

                {{-- Deadline --}}
                <div class="col-md-6">
                    <label class="form-label" for="deadline">Deadline</label>
                    <input type="datetime-local" class="form-control @error('deadline') is-invalid @enderror"
                           id="deadline" name="deadline"
                           value="{{ old('deadline', isset($task) && $task->deadline ? $task->deadline->format('Y-m-d\TH:i') : '') }}">
                    @error('deadline')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                </div>

                {{-- Attachment --}}
                <div class="col-md-6">
                    <label class="form-label" for="attachment">Attachment (opsional)</label>
                    <input type="file" class="form-control @error('attachment') is-invalid @enderror"
                           id="attachment" name="attachment">
                    @if (isset($task) && $task->attachment)
                        <div class="form-hint">
                            File saat ini:
                            <a href="{{ asset('storage/' . $task->attachment) }}" target="_blank">lihat file</a>
                        </div>
                    @endif
                    @error('attachment')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                </div>

            </div>
        </div>

        <div class="card-footer text-end">
            <a href="{{ route('manage.task.index') }}" class="btn btn-outline-secondary me-2">Batal</a>

            @if (isset($task))
                <button type="submit" class="btn btn-primary">
                    <i class="ti ti-device-floppy"></i> Simpan Perubahan
                </button>
            @else
                <button type="submit" name="action" value="draft" class="btn btn-outline-primary me-2">
                    <i class="ti ti-file"></i> Simpan sebagai Draft
                </button>
                <button type="button" class="btn btn-primary" id="btnPreview"
                        data-bs-toggle="modal" data-bs-target="#previewModal">
                    <i class="ti ti-eye"></i> Preview Task
                </button>
            @endif
        </div>
    </div>
</form>

{{-- Modal Preview → "Data sudah benar?" → Publish --}}
@if (!isset($task))
    <div class="modal modal-blur fade" id="previewModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-lg modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title"><i class="ti ti-eye me-2"></i>Preview Task</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body" id="previewBody"></div>
                <div class="modal-footer">
                    <div class="me-auto text-muted">Data sudah benar?</div>
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">
                        Tidak, perbaiki data
                    </button>
                    <button type="submit" name="action" value="publish" form="taskForm" class="btn btn-primary">
                        <i class="ti ti-send"></i> Ya, Publish
                    </button>
                </div>
            </div>
        </div>
    </div>
@endif

@once
<script>
    (function () {
        function escapeHtml(text) {
            var div = document.createElement('div');
            div.textContent = text || '';
            return div.innerHTML;
        }

        var courseSelect  = document.getElementById('training_plan_id');
        // Preview modal: isi modal dengan nilai form saat ini
        var btnPreview = document.getElementById('btnPreview');
        if (btnPreview) {
            btnPreview.addEventListener('click', function () {
                var form = document.getElementById('taskForm');
                if (!form.reportValidity()) return;

                var courseText = (courseSelect.selectedOptions[0] || {}).text || '-';
                var typeText = (document.getElementById('type').selectedOptions[0] || {}).text || '-';
                var deadlineVal = document.getElementById('deadline').value;
                var fileInput = document.getElementById('attachment');

                document.getElementById('previewBody').innerHTML =
                    '<dl class="row mb-0">' +
                        '<dt class="col-sm-4">Course</dt><dd class="col-sm-8">' + escapeHtml(courseText) + '</dd>' +
                        '<dt class="col-sm-4">Judul Task</dt><dd class="col-sm-8">' + escapeHtml(document.getElementById('title').value) + '</dd>' +
                        '<dt class="col-sm-4">Tipe</dt><dd class="col-sm-8">' + escapeHtml(typeText) + '</dd>' +
                        '<dt class="col-sm-4">Deskripsi</dt><dd class="col-sm-8">' + escapeHtml(document.getElementById('description').value || '-') + '</dd>' +
                        '<dt class="col-sm-4">Deadline</dt><dd class="col-sm-8">' + (deadlineVal ? escapeHtml(deadlineVal.replace('T', ' ')) : '-') + '</dd>' +
                        '<dt class="col-sm-4">Attachment</dt><dd class="col-sm-8">' + (fileInput.files.length ? escapeHtml(fileInput.files[0].name) : '-') + '</dd>' +
                    '</dl>';
            });
        }
    })();
</script>
@endonce
