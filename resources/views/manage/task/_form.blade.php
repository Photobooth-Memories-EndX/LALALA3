{{-- Shared create & edit form — task type aware, streamlined (2026-10-01).
    6 types: File Submission | Course Assignment | Assessment/Quiz | Survey | Essay/Reflection | Action Plan
    NOTE: this is a partial included via @include, so it must NOT @extend any layout. --}}
@php
    $defaultParentType = request('parent_type')
        ?? (request('scope') === 'non_kp' ? 'offline_training' : 'playlist');
    if (! array_key_exists($defaultParentType, \App\Models\Task::PARENT_TYPES)) {
        $defaultParentType = 'playlist';
    }
    $currentType = old('type', $task->type ?? 'action_plan');
    $currentLinkType = old('link_type', $task->link_type ?? 'lms');
    $currentParentType = old('parent_type', $task->parent_type ?? ($lockedParent['type'] ?? $defaultParentType));
    $currentParentId = old('parent_id', isset($task) ? $task->parentId() : ($lockedParent['id'] ?? ''));
    $linkedCourse = old('linked_training_plan_id', $task->linked_training_plan_id ?? '');
    $sameChecked = $currentParentType === 'playlist'
        && $currentParentId !== '' && $currentParentId !== null
        && $linkedCourse !== '' && (string) $currentParentId === (string) $linkedCourse;
@endphp
<form method="POST"
      action="{{ isset($task) ? route('manage.task.update', $task) : route('manage.task.store') }}"
      enctype="multipart/form-data"
      id="taskForm">
    @csrf
    @if (isset($task))
        @method('PUT')
    @endif

    <style>
        .form-section-card {
            border: 1px solid #e2e8f0 !important;
            border-radius: 16px !important;
            background: #ffffff !important;
            box-shadow: 0 2px 6px rgba(15, 23, 42, 0.04);
            margin-bottom: 1.5rem;
        }
        .form-section-card .card-header {
            border-bottom: 1px solid #f1f5f9 !important;
        }
        .section-badge-num {
            width: 32px;
            height: 32px;
            border-radius: 10px;
            background-color: #e0f2fe;
            color: #0284c7;
            font-weight: 800;
            font-size: 0.95rem;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            flex-shrink: 0;
        }
        .field-card {
            border: 1px solid #e2e8f0 !important;
            border-radius: 12px !important;
            background: #ffffff !important;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.03);
            transition: all 0.2s ease;
        }
        .field-card:hover {
            border-color: #cbd5e1 !important;
            box-shadow: 0 4px 12px rgba(15, 23, 42, 0.05);
        }
        [data-bs-theme="dark"] .form-section-card,
        body.dark-mode .form-section-card,
        body.theme-dark .form-section-card {
            background: #1e293b !important;
            border-color: rgba(255, 255, 255, 0.1) !important;
        }
        [data-bs-theme="dark"] .section-badge-num,
        body.dark-mode .section-badge-num,
        body.theme-dark .section-badge-num {
            background-color: rgba(14, 165, 233, 0.15) !important;
            color: #38bdf8 !important;
        }
        [data-bs-theme="dark"] .field-card,
        body.dark-mode .field-card,
        body.theme-dark .field-card {
            background: #0f172a !important;
            border-color: rgba(255, 255, 255, 0.08) !important;
        }
    </style>

    {{-- ======================================================== --}}
    {{-- SECTION 1: PENEMPATAN & SASARAN TUGAS (PLACEMENT & TARGET) --}}
    {{-- ======================================================== --}}
    <div class="card form-section-card">
        <div class="card-header bg-white py-3 px-4 d-flex align-items-center gap-3">
            <span class="section-badge-num"><i class="ti ti-map-pin"></i></span>
            <div>
                <h3 class="card-title fw-bold text-dark mb-0">1. Penempatan & Sasaran Tugas</h3>
                <div class="text-muted small">Tentukan di mana tugas ini ditempatkan dan bagaimana peserta dapat mengaksesnya.</div>
            </div>
        </div>
        <div class="card-body p-4">
            @if (!empty($lockedParent))
                <div class="alert alert-info d-flex align-items-center mb-0 rounded-3">
                    <i class="ti ti-book-2 fs-2 me-2"></i>
                    <div>
                        Tugas ini akan otomatis ditambahkan ke dalam
                        <strong>{{ $lockedParent['name'] }}</strong>
                        ({{ $lockedParent['label'] }}).
                    </div>
                </div>
                <input type="hidden" name="parent_type" value="{{ $lockedParent['type'] }}">
                <input type="hidden" name="parent_id" value="{{ $lockedParent['id'] }}">
                @if ($lockedParent['type'] === 'standalone')
                    <div class="mt-3">
                        <label class="form-label" for="access_code">Unique Access Code (Optional)</label>
                        <input type="text" name="access_code" id="access_code"
                               class="form-control text-uppercase @error('access_code') is-invalid @enderror"
                               value="{{ old('access_code', $task->access_code ?? '') }}"
                               maxlength="8" placeholder="Masukkan kode unik (cth: TUGAS01)" style="max-width:280px"
                               autocomplete="off">
                        <small class="text-muted d-block mt-1">Kode unik untuk membuka tugas mandiri ini (opsional, maks 8 karakter alfanumerik).</small>
                        @error('access_code')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                    </div>
                @endif
            @else
                <div class="row g-3">
                    <div class="col-md-5">
                        <label class="form-label required" for="parent_type">Kategori Penempatan</label>
                        <select class="form-select @error('parent_type') is-invalid @enderror"
                                id="parent_type" name="parent_type" required>
                            @foreach (\App\Models\Task::PARENT_TYPES as $key => $label)
                                <option value="{{ $key }}" {{ $currentParentType === $key ? 'selected' : '' }}>
                                    {{ $label }}
                                </option>
                            @endforeach
                        </select>
                        <div class="form-hint mt-1">Pilih apakah tugas terikat ke Playlist, Konten, Offline Training, atau Global.</div>
                        @error('parent_type')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                    </div>

                    <div class="col-md-7">
                        <label class="form-label required" id="parentPickerLabel">{{ \App\Models\Task::PARENT_TYPES[$currentParentType] ?? 'Playlist' }}</label>
                        <div class="position-relative" id="parentPicker">
                            <input type="hidden" name="parent_id" id="parent_id" value="{{ $currentParentId }}"
                                   data-url-template="{{ route('manage.task.parents', ['type' => '__TYPE__']) }}">
                            <button type="button"
                                    class="form-select text-start d-flex justify-content-between align-items-center"
                                    id="parentPickerBtn" aria-haspopup="listbox" aria-expanded="false">
                                <span id="parentPickerText">{{ $selectedParentName ?: '— Pilih Target Penempatan —' }}</span>
                                <i class="ti ti-chevron-down ms-2"></i>
                            </button>
                            <div class="card position-absolute w-100 shadow mt-1" id="parentPanel"
                                 style="display:none; z-index:1050;">
                                <div class="p-2 border-bottom">
                                    <input type="search" id="parentPanelSearch" class="form-control"
                                           placeholder="Ketik untuk mencari..." autocomplete="off">
                                </div>
                                <div id="parentPanelList" class="overflow-auto" style="max-height:240px;" role="listbox"></div>
                            </div>
                        </div>
                        <div class="invalid-feedback" id="parentIdError" style="display:none">Pilih data target di mana tugas ini ditempatkan.</div>
                        @error('parent_id')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                    </div>
                </div>

                {{-- Unique Code: optional, standalone only --}}
                <div class="mt-3 pt-3 border-top" id="accessCodeWrap" style="display:none">
                    <div class="row">
                        <div class="col-md-6">
                            <label class="form-label" for="access_code">Unique Access Code (Optional)</label>
                            <input type="text" name="access_code" id="access_code"
                                   class="form-control text-uppercase @error('access_code') is-invalid @enderror"
                                   value="{{ old('access_code', $task->access_code ?? '') }}"
                                   maxlength="8" placeholder="Contoh: TUGAS01" style="max-width:280px"
                                   autocomplete="off">
                            <small class="text-muted d-block mt-1">Kode unik untuk membuka tugas mandiri ini (opsional, maks 8 karakter). Jika dikosongkan, tugas dapat diakses melalui tautan rahasia atau scan QR.</small>
                            @error('access_code')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                        </div>
                    </div>
                </div>
            @endif
        </div>
    </div>

    {{-- ======================================================== --}}
    {{-- SECTION 2: INFORMASI DASAR TUGAS (BASIC INFORMATION)     --}}
    {{-- ======================================================== --}}
    <div class="card form-section-card">
        <div class="card-header bg-white py-3 px-4 d-flex align-items-center gap-3">
            <span class="section-badge-num"><i class="ti ti-file-text"></i></span>
            <div>
                <h3 class="card-title fw-bold text-dark mb-0">2. Informasi Dasar Tugas</h3>
                <div class="text-muted small">Tentukan jenis penugasan, judul tugas, dan instruksi pengerjaan bagi peserta.</div>
            </div>
        </div>
        <div class="card-body p-4">
            <div class="row g-3">
                <div class="col-md-4">
                    <label class="form-label required" for="type">Tipe Tugas (Task Type)</label>
                    <select class="form-select @error('type') is-invalid @enderror"
                            id="type" name="type" required>
                        @foreach (\App\Models\Task::TYPES as $key => $label)
                            <option value="{{ $key }}" {{ $currentType === $key ? 'selected' : '' }}>
                                {{ $label }}
                            </option>
                        @endforeach
                    </select>
                    @error('type')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                    <div class="form-hint mt-1" id="typeHint"></div>
                </div>

                <div class="col-md-8">
                    <label class="form-label required" for="title">Judul Tugas (Task Title)</label>
                    <input type="text" class="form-control @error('title') is-invalid @enderror"
                           id="title" name="title"
                           value="{{ old('title', $task->title ?? '') }}"
                           placeholder="Contoh: Laporan Observasi Lapangan K3 & Praktik Bengkel" required>
                    @error('title')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
            </div>

            <div class="mt-3">
                <label class="form-label" for="description">Deskripsi & Instruksi Pengerjaan</label>
                <textarea class="form-control @error('description') is-invalid @enderror"
                          id="description" name="description"
                          rows="3"
                          placeholder="Tuliskan petunjuk pengerjaan, ketentuan berkas, atau panduan yang perlu dipahami peserta...">{{ old('description', $task->description ?? '') }}</textarea>
                @error('description')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
            </div>
        </div>
    </div>

    {{-- ======================================================== --}}
    {{-- SECTION 3: JADWAL & MATERI PENDUKUNG (TIMELINE & FILES)   --}}
    {{-- ======================================================== --}}
    <div class="card form-section-card">
        <div class="card-header bg-white py-3 px-4 d-flex align-items-center gap-3">
            <span class="section-badge-num"><i class="ti ti-calendar-time"></i></span>
            <div>
                <h3 class="card-title fw-bold text-dark mb-0">3. Tenggat Waktu & Berkas Pendukung</h3>
                <div class="text-muted small">Atur batas waktu pengumpulan tugas serta unggah template soal atau materi panduan (opsional).</div>
            </div>
        </div>
        <div class="card-body p-4">
            <div class="row g-3">
                <div class="col-md-6">
                    <label class="form-label" for="deadline">Batas Waktu Pengumpulan (Deadline)</label>
                    <div class="input-icon">
                        <span class="input-icon-addon">
                            <i class="ti ti-calendar-event"></i>
                        </span>
                        <input type="datetime-local" class="form-control @error('deadline') is-invalid @enderror"
                               id="deadline" name="deadline"
                               value="{{ old('deadline', isset($task) && $task->deadline ? $task->deadline->format('Y-m-d\TH:i') : '') }}">
                    </div>
                    <small class="text-muted d-block mt-1">Kosongkan jika tugas ini tidak memiliki batas waktu pengumpulan.</small>
                    @error('deadline')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                </div>

                <div class="col-md-6">
                    <label class="form-label" for="attachment">Lampiran Berkas / Template Tugas (Optional)</label>
                    <input type="file" class="form-control @error('attachment') is-invalid @enderror"
                           id="attachment" name="attachment">
                    @if (isset($task) && $task->attachment)
                        <div class="form-hint mt-1 d-flex align-items-center gap-1">
                            <i class="ti ti-file-check text-success"></i>
                            <span>Berkas tersimpan:</span>
                            <a href="{{ asset('storage/' . $task->attachment) }}" target="_blank" class="fw-semibold">Lihat Berkas</a>
                        </div>
                    @else
                        <small class="text-muted d-block mt-1">Format PDF, DOCX, ZIP, XLSX maks 5 MB sebagai referensi pengerjaan peserta.</small>
                    @endif
                    @error('attachment')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                </div>
            </div>
        </div>
    </div>

    {{-- ======================================================== --}}
    {{-- SECTION 4: KONFIGURASI PENGERJAAN / PERTANYAAN           --}}
    {{-- ======================================================== --}}
    {{-- ── 4A: Course Link section (only for Course Assignment) ── --}}
    <div id="linkSection" class="card form-section-card" style="display:none">
        <div class="card-header bg-white py-3 px-4 d-flex align-items-center gap-3">
            <span class="section-badge-num"><i class="ti ti-link"></i></span>
            <div>
                <h3 class="card-title fw-bold text-dark mb-0">4. Target Kursus Penugasan</h3>
                <div class="text-muted small">Tentukan kursus di dalam LMS atau tautan kursus eksternal yang harus diselesaikan peserta.</div>
            </div>
        </div>
        <div class="card-body p-4">
            <div class="mb-3">
                <label class="form-label fw-semibold">Tipe Tujuan Kursus</label>
                <div class="d-flex gap-3">
                    <label class="form-check form-check-inline mb-0">
                        <input type="radio" class="form-check-input" name="link_type" value="lms"
                               {{ $currentLinkType === 'lms' ? 'checked' : '' }}>
                        <span class="form-check-label fw-semibold">Kursus di dalam OWL LMS (Auto-Complete 100%)</span>
                    </label>
                    <label class="form-check form-check-inline mb-0">
                        <input type="radio" class="form-check-input" name="link_type" value="external"
                               {{ $currentLinkType === 'external' ? 'checked' : '' }}>
                        <span class="form-check-label fw-semibold">Tautan Kursus Eksternal (URL)</span>
                    </label>
                </div>
                @error('link_type')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
            </div>

            <div id="linkLmsWrap">
                <label class="form-label required" for="linked_training_plan_id">Target Kursus di LMS</label>
                <select class="form-select d-none @error('linked_training_plan_id') is-invalid @enderror"
                        id="linked_training_plan_id" name="linked_training_plan_id" tabindex="-1" aria-hidden="true">
                    <option value="">— Pilih Target Kursus —</option>
                    @foreach ($trainingPlans as $trainingPlan)
                        <option value="{{ $trainingPlan->id }}"
                            {{ (string) $linkedCourse === (string) $trainingPlan->id ? 'selected' : '' }}>
                            {{ $trainingPlan->title }}
                        </option>
                    @endforeach
                </select>
                <div class="position-relative" id="coursePicker">
                    <button type="button"
                            class="form-select text-start d-flex justify-content-between align-items-center"
                            id="coursePickerBtn" aria-haspopup="listbox" aria-expanded="false">
                        <span id="coursePickerText">— Pilih Target Kursus —</span>
                        <i class="ti ti-chevron-down ms-2"></i>
                    </button>
                    <div class="card position-absolute w-100 shadow mt-1" id="coursePanel"
                         style="display:none; z-index:1050;">
                        <div class="p-2 border-bottom">
                            <input type="search" id="coursePanelSearch" class="form-control"
                                   placeholder="Ketik nama kursus untuk mencari..." autocomplete="off">
                        </div>
                        <div id="coursePanelList" class="overflow-auto" style="max-height:240px;" role="listbox"></div>
                    </div>
                </div>
                @error('linked_training_plan_id')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                <div id="sameCourseWrap" class="mt-2" style="{{ $currentParentType === 'playlist' ? '' : 'display:none' }}">
                    <label class="form-check mb-0">
                        <input type="checkbox" class="form-check-input" id="sameCourse"
                               {{ $sameChecked ? 'checked' : '' }}>
                        <span class="form-check-label text-muted">Sama dengan playlist/kursus yang dipilih di atas</span>
                    </label>
                </div>
            </div>

            <div id="linkExternalWrap" style="display:none">
                <label class="form-label required" for="external_url">URL Kursus Eksternal</label>
                <input type="url" class="form-control @error('external_url') is-invalid @enderror"
                       id="external_url" name="external_url"
                       placeholder="https://example.com/course/..."
                       value="{{ old('external_url', $task->external_url ?? '') }}">
                <small class="text-muted d-block mt-1">Masukkan tautan lengkap termasuk https://</small>
                @error('external_url')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
            </div>
        </div>
    </div>

    {{-- ── 4B: Questions section (hidden for Course Assignment) ── --}}
    <div id="builderSection" class="card form-section-card">
        <div class="card-header bg-white py-3 px-4 d-flex align-items-center justify-content-between flex-wrap gap-2">
            <div class="d-flex align-items-center gap-3">
                <span class="section-badge-num"><i class="ti ti-list-check"></i></span>
                <div>
                    <h3 class="card-title fw-bold text-dark mb-0">4. Daftar Pertanyaan & Formulir Pengumpulan Jawaban</h3>
                    <div class="text-muted small" id="builderHint"></div>
                </div>
            </div>
            <button type="button" class="btn btn-primary btn-sm rounded-pill px-3 shadow-sm" id="btnAddField">
                <i class="ti ti-plus me-1"></i> Tambah Pertanyaan
            </button>
        </div>
        <div class="card-body p-4">
            <div id="fieldsWrap"></div>

            @error('fields')
                <div class="alert alert-danger mt-2">{{ $message }}</div>
            @enderror
        </div>
    </div>

    {{-- ======================================================== --}}
    {{-- SECTION 5: ACTION BAR / FOOTER                           --}}
    {{-- ======================================================== --}}
    <div class="card border-0 shadow-sm rounded-4 mb-4 p-3 bg-white">
        <div class="d-flex align-items-center justify-content-between flex-wrap gap-2">
            <div class="text-muted small">
                @if (isset($task))
                    <i class="ti ti-info-circle me-1"></i>Mode Edit: Perubahan akan langsung diperbarui pada tugas peserta.
                @else
                    <i class="ti ti-info-circle me-1"></i>Pilih <strong>Simpan Draf</strong> untuk menyimpan sementara, atau <strong>Publikasikan</strong> agar langsung tampil untuk peserta.
                @endif
            </div>
            <div class="d-flex align-items-center gap-2">
                <a href="{{ route('manage.task.index') }}" class="btn btn-outline-secondary rounded-3 px-3">Batal</a>

                @if (isset($task))
                    <button type="submit" class="btn btn-primary rounded-3 px-4">
                        <i class="ti ti-device-floppy me-1"></i> Simpan Perubahan
                    </button>
                @else
                    <button type="submit" name="action" value="draft" class="btn btn-outline-primary rounded-3 px-3">
                        <i class="ti ti-file me-1"></i> Simpan Draf
                    </button>
                    <button type="submit" name="action" value="publish" class="btn btn-primary rounded-3 px-4">
                        <i class="ti ti-send me-1"></i> Publikasikan Tugas
                    </button>
                @endif
            </div>
        </div>
    </div>
</form>

@once
<script>
    (function () {
        var FIELD_TYPES   = @json(\App\Models\TaskField::TYPES);
        var TASK_TYPES    = @json(\App\Models\Task::TYPES);
        var FIELD_BY_TASK = @json(\App\Models\Task::FIELD_TYPES_BY_TASK);
        // Types without a question builder (their cards would sit hidden and
        // their `required` inputs would silently block form submission).
        function typeHasBuilder(t) { return t !== 'course_assignment'; }
        var TYPE_HINTS    = @json(\App\Models\Task::TYPE_HINTS);
        var FILE_TYPE_GROUPS  = @json(\App\Models\TaskField::FILE_TYPE_GROUPS);
        var MAX_FILES_OPTIONS = @json(\App\Models\TaskField::MAX_FILES_OPTIONS);
        var MAX_SIZE_OPTIONS  = @json(\App\Models\TaskField::MAX_SIZE_OPTIONS);
        var DEFAULT_MAX_SIZE_MB = @json(\App\Models\TaskField::DEFAULT_MAX_SIZE_MB);

        var typeSelect    = document.getElementById('type');
        var typeHint      = document.getElementById('typeHint');
        var linkSection   = document.getElementById('linkSection');
        var builderSection= document.getElementById('builderSection');
        var builderHint   = document.getElementById('builderHint');
        var wrap          = document.getElementById('fieldsWrap');
        var linkedSelect  = document.getElementById('linked_training_plan_id');
        var sameCourse    = document.getElementById('sameCourse');
        var fieldIndex    = 0;

        var BUILDER_HINTS = {
            file_submission: 'The user answers by uploading file(s). Just write what they need to submit.',
            survey: 'Collect opinions — multiple choice or written answers, no scoring.',
            essay: 'The user writes a reflection in long text; you score it manually.',
            action_plan: 'File uploads and written answers. Reviewed manually.'
        };

        function esc(s) {
            var d = document.createElement('div');
            d.textContent = (s === null || s === undefined) ? '' : s;
            return d.innerHTML;
        }

        function currentType() { return typeSelect.value; }
        function allowedTypes() { return FIELD_BY_TASK[currentType()] || []; }

        function typeOptions(selected) {
            var html = '';
            allowedTypes().forEach(function (key) {
                html += '<option value="' + key + '"' + (key === selected ? ' selected' : '') + '>'
                    + esc(FIELD_TYPES[key]) + '</option>';
            });
            return html;
        }

        // Type column: a dropdown when several types are allowed,
        // otherwise a fixed badge (no dropdown needed).
        function typeColHTML(i, selType) {
            var allowed = allowedTypes();
            if (allowed.length === 1) {
                return '<input type="hidden" name="fields[' + i + '][type]" value="' + allowed[0] + '">' +
                       '<span class="badge bg-blue-lt mt-2">' + esc(FIELD_TYPES[allowed[0]]) + '</span>';
            }
            return '<select class="form-select field-type-select" name="fields[' + i + '][type]">' +
                   typeOptions(selType) + '</select>';
        }

        // ── Multiple-choice options editor ──────────────────────────────
        function addOptionRow(listEl, i, value, isCorrect) {
            var idx = listEl.querySelectorAll('.option-row').length;
            var row = document.createElement('div');
            row.className = 'input-group input-group-sm mb-1 option-row';
            row.innerHTML =
                '<span class="input-group-text correct-radio-wrap" title="Tick the correct answer">' +
                    '<input type="radio" class="form-check-input mt-0 correct-radio" ' +
                        'name="fields[' + i + '][correct]" value="' + idx + '"' +
                        (isCorrect ? ' checked' : '') + '>' +
                '</span>' +
                '<input type="text" class="form-control" name="fields[' + i + '][options][]" ' +
                    'placeholder="Option ' + (idx + 1) + '" value="' + esc(value || '') + '">' +
                '<button type="button" class="btn btn-outline-danger btnRemoveOption" title="Remove option">' +
                    '<i class="ti ti-x"></i></button>';
            listEl.appendChild(row);
            reindexOptions(listEl);
        }

        // Keep radio values = option position (0..n-1); hide the tick column
        // when this task type has no correct answer.
        function reindexOptions(listEl) {
            var rows = listEl.querySelectorAll('.option-row');
            rows.forEach(function (row, pos) {
                row.querySelector('.correct-radio').value = pos;
                row.querySelector('input[type="text"]').placeholder = 'Option ' + (pos + 1);
            });
            listEl.querySelectorAll('.correct-radio-wrap').forEach(function (w) {
                w.style.display = isQuiz() ? '' : 'none';
            });
        }

        function toggleOptionsEditor(card) {
            var show = card.dataset.ftype === 'choice';
            card.querySelector('.optionsEditor').style.display = show ? '' : 'none';
            if (show) reindexOptions(card.querySelector('.optionsList'));
        }

        // ── File-upload settings (ala Google Form) ──────────────────────
        function fileTypeSelectHTML(i, selected) {
            var html = '<option value="">All types</option>';
            Object.keys(FILE_TYPE_GROUPS).forEach(function (key) {
                html += '<option value="' + key + '"' + (key === selected ? ' selected' : '') + '>' +
                    esc(FILE_TYPE_GROUPS[key].label) + '</option>';
            });
            return html;
        }

        function selectOptionsHTML(options, selected, suffix) {
            var html = '';
            options.forEach(function (v) {
                html += '<option value="' + v + '"' + (String(v) === String(selected) ? ' selected' : '') + '>' +
                    v + (suffix || '') + '</option>';
            });
            return html;
        }

        function fileSettingsHTML(i, data) {
            return '<div class="fileSettings mt-2" style="display:none">' +
                '<div class="mb-2" style="max-width:280px">' +
                    '<label class="form-label small mb-1">File type</label>' +
                    '<select class="form-select form-select-sm" name="fields[' + i + '][file_types]">' +
                        fileTypeSelectHTML(i, data.file_types || '') +
                    '</select>' +
                '</div>' +
                '<div class="d-flex flex-wrap gap-3">' +
                    '<div>' +
                        '<label class="form-label small mb-1">Maximum number of files</label>' +
                        '<select class="form-select form-select-sm" name="fields[' + i + '][max_files]" style="width:auto">' +
                            selectOptionsHTML(MAX_FILES_OPTIONS, data.max_files || 1, '') +
                        '</select>' +
                    '</div>' +
                    '<div>' +
                        '<label class="form-label small mb-1">Maximum file size</label>' +
                        '<select class="form-select form-select-sm" name="fields[' + i + '][max_size_mb]" style="width:auto">' +
                            selectOptionsHTML(MAX_SIZE_OPTIONS, data.max_size_mb || DEFAULT_MAX_SIZE_MB, ' MB') +
                        '</select>' +
                    '</div>' +
                '</div>' +
                '<label class="form-check mt-2 mb-0">' +
                    '<input type="checkbox" class="form-check-input" name="fields[' + i + '][allow_link]" value="1"' +
                        (data.allow_link ? ' checked' : '') + '>' +
                    '<span class="form-check-label">Allow Drive link as an alternative</span>' +
                '</label>' +
            '</div>';
        }

        function toggleFileSettings(card) {
            var show = card.dataset.ftype === 'file';
            card.querySelector('.fileSettings').style.display = show ? '' : 'none';
        }

        // ── One question card (compact single row) ──────────────────────
        function addField(data) {
            data = data || {};
            var i = fieldIndex++;
            var allowed = allowedTypes();
            var selType = allowed.indexOf(data.type) !== -1 ? data.type : (allowed[0] || 'textarea');

            var card = document.createElement('div');
            card.className = 'border rounded p-3 mb-2 field-card bg-white';
            card.dataset.index = i;
            card.dataset.ftype = selType;
            card.innerHTML =
                '<input type="hidden" name="fields[' + i + '][id]" value="' + esc(data.id || '') + '">' +
                '<div class="d-flex gap-2 align-items-start">' +
                    '<div class="flex-fill">' +
                        '<input type="text" class="form-control" name="fields[' + i + '][label]" ' +
                            'placeholder="Write the question" value="' + esc(data.label || '') + '" required>' +
                    '</div>' +
                    '<div class="typeCol" style="width:150px;flex-shrink:0">' + typeColHTML(i, selType) + '</div>' +
                    '<label class="form-check mt-2 text-nowrap" title="Make this question mandatory">' +
                        '<input type="checkbox" class="form-check-input" name="fields[' + i + '][required]" value="1"' +
                            (data.required ? ' checked' : '') + '>' +
                        '<span class="form-check-label">Required</span>' +
                    '</label>' +
                    '<button type="button" class="btn btn-outline-danger btn-sm btnRemoveField mt-1" title="Delete question">' +
                        '<i class="ti ti-trash"></i>' +
                    '</button>' +
                '</div>' +
                '<div class="optionsEditor mt-2" style="display:none">' +
                    '<div class="optionsList"></div>' +
                    '<button type="button" class="btn btn-sm btn-outline-secondary btnAddOption">' +
                        '<i class="ti ti-plus"></i> Add option</button>' +
                    '<div class="form-hint mt-1 optionsHint"></div>' +
                '</div>' +
                fileSettingsHTML(i, data);
            wrap.appendChild(card);

            var list = card.querySelector('.optionsList');
            var opts = (data.options && data.options.length) ? data.options : ['', ''];
            opts.forEach(function (opt, idx) {
                addOptionRow(list, i, opt, idx === (data.correct || 0));
            });
            card.querySelector('.optionsHint').textContent = isQuiz()
                ? 'Tick the radio on the correct answer — scoring is automatic.'
                : 'No correct answer in a survey — just collect the options.';

            toggleOptionsEditor(card);
            toggleFileSettings(card);
        }

        // ── Events (delegation) ─────────────────────────────────────────
        document.getElementById('btnAddField').addEventListener('click', function () {
            addField();
        });

        wrap.addEventListener('click', function (e) {
            var rmField = e.target.closest('.btnRemoveField');
            if (rmField) {
                if (wrap.querySelectorAll('.field-card').length <= 1) {
                    alert('A task needs at least 1 question.');
                    return;
                }
                rmField.closest('.field-card').remove();
                return;
            }
            var addOpt = e.target.closest('.btnAddOption');
            if (addOpt) {
                var card = addOpt.closest('.field-card');
                addOptionRow(card.querySelector('.optionsList'), card.dataset.index, '', false);
                return;
            }
            var rmOpt = e.target.closest('.btnRemoveOption');
            if (rmOpt) {
                var list = rmOpt.closest('.optionsList');
                if (list.querySelectorAll('.option-row').length <= 2) {
                    alert('Multiple choice needs at least 2 options.');
                    return;
                }
                rmOpt.closest('.option-row').remove();
                reindexOptions(list);
            }
        });

        wrap.addEventListener('change', function (e) {
            if (e.target.classList.contains('field-type-select')) {
                var card = e.target.closest('.field-card');
                card.dataset.ftype = e.target.value;
                toggleOptionsEditor(card);
                toggleFileSettings(card);
            }
        });

        // ── Placement picker: type dropdown + searchable item dropdown ────
        var parentTypeSelect = document.getElementById('parent_type');
        var parentIdInput    = document.getElementById('parent_id');
        var parentPicker     = document.getElementById('parentPicker');
        var parentPickerBtn  = document.getElementById('parentPickerBtn');
        var parentPickerText = document.getElementById('parentPickerText');
        var parentPickerLabel= document.getElementById('parentPickerLabel');
        var parentPanel      = document.getElementById('parentPanel');
        var parentPanelSearch= document.getElementById('parentPanelSearch');
        var parentPanelList  = document.getElementById('parentPanelList');
        var parentIdError    = document.getElementById('parentIdError');
        var PARENT_LABELS    = @json(\App\Models\Task::PARENT_TYPES);
        var currentPlacement = @json($currentParentType);

        function placementType() { return currentPlacement; }

        function setPickerLabel() {
            if (parentPickerLabel) parentPickerLabel.textContent = PARENT_LABELS[currentPlacement] || 'Item';
        }

        function syncSameCourseWrap() {
            var w = document.getElementById('sameCourseWrap');
            if (w) w.style.display = (currentPlacement === 'playlist') ? '' : 'none';
        }

        // Standalone tasks are not tied to any placement → hide the item picker,
        // show the (optional, manual) access code field instead.
        function syncPlacementPicker() {
            var isStandalone = currentPlacement === 'standalone';
            if (parentPickerLabel) parentPickerLabel.style.display = isStandalone ? 'none' : '';
            if (parentPicker) parentPicker.style.display = isStandalone ? 'none' : '';
            var accessCodeWrap = document.getElementById('accessCodeWrap');
            if (accessCodeWrap) accessCodeWrap.style.display = isStandalone ? '' : 'none';
        }

        function renderParentItems(items) {
            parentPanelList.innerHTML = '';
            if (!items.length) {
                var emptyMsg = currentPlacement === 'offline_training'
                    ? 'No offline training records found.'
                    : 'No results found.';
                parentPanelList.innerHTML = '<div class="p-3 text-muted text-center">' + emptyMsg + '</div>';
                return;
            }
            items.forEach(function (it) {
                var b = document.createElement('button');
                b.type = 'button';
                b.className = 'list-group-item list-group-item-action';
                b.textContent = it.title;
                b.setAttribute('role', 'option');
                if (String(it.id) === String(parentIdInput.value)) b.classList.add('active');
                b.addEventListener('click', function () { pickParent(it.id, it.title); });
                parentPanelList.appendChild(b);
            });
        }

        function pickParent(id, title) {
            parentIdInput.value = id;
            parentPickerText.textContent = title;
            if (parentIdError) parentIdError.style.display = 'none';
            closeParentPanel();
            syncSameCourse();
        }

        function openParentPanel() {
            if (!parentPanel) return;
            parentPanel.style.display = '';
            parentPickerBtn.setAttribute('aria-expanded', 'true');
            parentPanelSearch.value = '';
            parentPanelSearch.disabled = false;
            loadParentItems('');
            setTimeout(function () { parentPanelSearch.focus(); }, 50);
        }

        function closeParentPanel() {
            if (!parentPanel) return;
            parentPanel.style.display = 'none';
            parentPickerBtn.setAttribute('aria-expanded', 'false');
        }

        var parentSearchTimer = null;
        function loadParentItems(query) {
            var url = parentIdInput.dataset.urlTemplate.replace('__TYPE__', currentPlacement)
                + '?q=' + encodeURIComponent(query || '');
            parentPanelList.innerHTML = '<div class="p-3 text-muted text-center">Loading...</div>';
            fetch(url, { headers: { 'X-Requested-With': 'XMLHttpRequest' } })
                .then(function (r) { return r.json(); })
                .then(renderParentItems)
                .catch(function () {
                    parentPanelList.innerHTML = '<div class="p-3 text-muted text-center">Failed to load. Try again.</div>';
                });
        }

        if (parentTypeSelect) {
            parentTypeSelect.addEventListener('change', function () {
                currentPlacement = parentTypeSelect.value;
                parentIdInput.value = '';
                parentPickerText.textContent = '— Select —';
                if (sameCourse) sameCourse.checked = false;
                setPickerLabel();
                syncSameCourseWrap();
                syncPlacementPicker();
                syncSameCourse();
            });
        }
        if (parentPickerBtn) {
            parentPickerBtn.addEventListener('click', function (e) {
                e.stopPropagation();
                if (parentPanel.style.display === 'none') openParentPanel();
                else closeParentPanel();
            });
            document.addEventListener('click', function (e) {
                if (parentPicker && !parentPicker.contains(e.target)) closeParentPanel();
            });
            document.addEventListener('keydown', function (e) {
                if (e.key === 'Escape') closeParentPanel();
            });
        }
        if (parentPanelSearch) {
            parentPanelSearch.addEventListener('click', function (e) { e.stopPropagation(); });
            parentPanelSearch.addEventListener('input', function () {
                clearTimeout(parentSearchTimer);
                var q = parentPanelSearch.value;
                parentSearchTimer = setTimeout(function () { loadParentItems(q); }, 300);
            });
        }

        // ── Target course: searchable dropdown (dibangun dari <option> yang ada) ──
        var coursePickerBtn   = document.getElementById('coursePickerBtn');
        var coursePickerText  = document.getElementById('coursePickerText');
        var coursePicker      = document.getElementById('coursePicker');
        var coursePanel       = document.getElementById('coursePanel');
        var coursePanelSearch = document.getElementById('coursePanelSearch');
        var coursePanelList   = document.getElementById('coursePanelList');

        function courseOptions() {
            var out = [];
            if (!linkedSelect) return out;
            Array.prototype.forEach.call(linkedSelect.options, function (o) {
                if (!o.value) return; // lewati placeholder
                out.push({ id: o.value, title: o.textContent.trim() });
            });
            return out;
        }

        function syncCoursePickerText() {
            if (!linkedSelect || !coursePickerText) return;
            var sel = linkedSelect.options[linkedSelect.selectedIndex];
            coursePickerText.textContent = (sel && sel.value)
                ? sel.textContent.trim()
                : '— Select target course —';
        }

        function renderCourseItems(items) {
            coursePanelList.innerHTML = '';
            if (!items.length) {
                coursePanelList.innerHTML = '<div class="p-3 text-muted text-center">No results found.</div>';
                return;
            }
            items.forEach(function (it) {
                var b = document.createElement('button');
                b.type = 'button';
                b.className = 'list-group-item list-group-item-action';
                b.textContent = it.title;
                b.setAttribute('role', 'option');
                if (linkedSelect && String(linkedSelect.value) === String(it.id)) b.classList.add('active');
                b.addEventListener('click', function () {
                    linkedSelect.value = it.id;
                    linkedSelect.dispatchEvent(new Event('change'));
                    closeCoursePanel();
                });
                coursePanelList.appendChild(b);
            });
        }

        function filterCourseItems(q) {
            q = (q || '').toLowerCase().trim();
            var items = courseOptions();
            if (q) items = items.filter(function (it) {
                return it.title.toLowerCase().indexOf(q) !== -1;
            });
            renderCourseItems(items);
        }

        function openCoursePanel() {
            if (!coursePanel) return;
            coursePanel.style.display = '';
            coursePickerBtn.setAttribute('aria-expanded', 'true');
            coursePanelSearch.value = '';
            filterCourseItems('');
            setTimeout(function () { coursePanelSearch.focus(); }, 50);
        }

        function closeCoursePanel() {
            if (!coursePanel) return;
            coursePanel.style.display = 'none';
            coursePickerBtn.setAttribute('aria-expanded', 'false');
        }

        if (coursePickerBtn && linkedSelect) {
            linkedSelect.addEventListener('change', syncCoursePickerText);
            coursePickerBtn.addEventListener('click', function (e) {
                e.stopPropagation();
                if (coursePanel.style.display === 'none') openCoursePanel();
                else closeCoursePanel();
            });
            document.addEventListener('click', function (e) {
                if (coursePicker && !coursePicker.contains(e.target)) closeCoursePanel();
            });
            document.addEventListener('keydown', function (e) {
                if (e.key === 'Escape') closeCoursePanel();
            });
            if (coursePanelSearch) {
                coursePanelSearch.addEventListener('click', function (e) { e.stopPropagation(); });
                coursePanelSearch.addEventListener('input', function () {
                    filterCourseItems(coursePanelSearch.value);
                });
            }
            syncCoursePickerText();
        }
        // Guard: don't submit without a picked parent (hidden inputs skip browser validation).
        var taskFormEl = document.getElementById('taskForm');
        if (taskFormEl && parentIdInput) {
            taskFormEl.addEventListener('submit', function (e) {
                // Standalone tasks are not tied to any placement → no parent needed.
                if (!parentIdInput.value && currentPlacement !== 'standalone') {
                    e.preventDefault();
                    if (parentIdError) parentIdError.style.display = '';
                    openParentPanel();
                }
            });
        }
        setPickerLabel();
        syncSameCourseWrap();
        syncPlacementPicker();

        // ── Course Assignment: link type radios + "same playlist" ────────
        function courseField() {
            return document.getElementById('parent_id')
                || document.querySelector('input[name="parent_id"]');
        }

        function syncSameCourse() {
            if (!sameCourse || !linkedSelect || !sameCourse.checked) return;
            if (placementType() !== 'playlist') return;
            var cf = courseField();
            if (cf && cf.value) {
                linkedSelect.value = cf.value;
                linkedSelect.dispatchEvent(new Event('change'));
            }
        }
        if (sameCourse) {
            sameCourse.addEventListener('change', syncSameCourse);
            var cf0 = courseField();
            if (cf0 && cf0.tagName === 'SELECT') cf0.addEventListener('change', syncSameCourse);
        }
        document.querySelectorAll('input[name="link_type"]').forEach(function (r) {
            r.addEventListener('change', refreshLinkWraps);
        });
        function refreshLinkWraps() {
            var linkType = document.querySelector('input[name="link_type"]:checked');
            var isLms = !linkType || linkType.value === 'lms';
            document.getElementById('linkLmsWrap').style.display = isLms ? '' : 'none';
            document.getElementById('linkExternalWrap').style.display = isLms ? 'none' : '';
        }

        // ── Task type switching ─────────────────────────────────────────
        function refreshTypeUI() {
            var t = currentType();
            typeHint.textContent = TYPE_HINTS[t] || '';
            var isLink = (t === 'course_assignment');

            linkSection.style.display       = isLink ? '' : 'none';
            builderSection.style.display    = isLink ? 'none' : '';
            builderHint.textContent = BUILDER_HINTS[t] || '';

            if (isLink) {
                // These types have no question builder: disable every input
                // inside the hidden cards. Hidden `required` inputs would
                // otherwise block form submission silently (browser refuses
                // to submit, no visible error). Disabled inputs are also
                // excluded from the POST.
                wrap.querySelectorAll('input, select, textarea, button').forEach(function (el) {
                    el.disabled = true;
                });
                if (isLink) {
                    refreshLinkWraps();
                    syncSameCourse();
                }
                return;
            }

            // Builder visible again: re-enable the cards' inputs.
            wrap.querySelectorAll('input, select, textarea, button').forEach(function (el) {
                el.disabled = false;
            });

            // Rebuild each card's type column for the newly allowed set.
            var allowed = allowedTypes();
            wrap.querySelectorAll('.field-card').forEach(function (card) {
                var cur = card.dataset.ftype;
                var next = allowed.indexOf(cur) !== -1 ? cur : allowed[0];
                card.dataset.ftype = next;
                card.querySelector('.typeCol').innerHTML =
                    typeColHTML(card.dataset.index, next);
                toggleOptionsEditor(card);
                toggleFileSettings(card);
            });
            // refresh the per-editor hint (quiz vs survey wording)
            wrap.querySelectorAll('.field-card').forEach(function (card) {
                var hint = card.querySelector('.optionsHint');
                if (hint) hint.textContent = isQuiz()
                    ? 'Tick the radio on the correct answer — scoring is automatic.'
                    : 'No correct answer in a survey — just collect the options.';
            });
        }
        typeSelect.addEventListener('change', function () {
            refreshTypeUI();
            // Coming back to a type with a builder and an empty builder → start with 1 card.
            // (Never auto-add for types without a builder: a hidden card's
            // `required` input would silently block submit.)
            var t = currentType();
            if (typeHasBuilder(t) && wrap.querySelectorAll('.field-card').length === 0) {
                addField();
            }
        });

        // ── Initial render ──────────────────────────────────────────────
        var initialFields = @json($initialFields ?? []);
        refreshTypeUI();
        setPickerLabel();
        syncPlacementPicker();
        syncSameCourseWrap();
        syncSameCourse();
        // No builder for course_assignment → never seed cards there.
        if (typeHasBuilder(currentType())) {
            initialFields.forEach(function (f) { addField(f); });
            if (initialFields.length === 0) addField();
        }
    })();
</script>
@endonce
