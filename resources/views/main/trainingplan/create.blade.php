@extends('layouts.sidebar-master')

@push('styles')
    <link rel="stylesheet" href="{{ asset('Tabler/dist/css/tabler-vendors.min.css?1684106062') }}">
    @include('main.trainingplan.components.tomselect-content-style')
    <style>
        .btn-back {
            color: #0c5b9e !important;
            transition: transform 0.2s ease, opacity 0.2s ease;
        }
        .btn-back:hover {
            opacity: 0.85;
            transform: translateX(-3px);
        }
    </style>
@endpush

@section('content')
    @include('main.monitoring.components.style')

    {{-- Page Header --}}
    <div class="page-header d-print-none mb-3">
        <div class="container-xl">
            <!-- TOMBOL BACK RATA KIRI ATAS -->
            <div class="mb-3">
                <a href="{{ route('playlist.index') }}" class="btn-back d-inline-flex align-items-center text-primary text-decoration-none fw-semibold small">
                    <i class="ti ti-arrow-left me-1 fs-5"></i> Back
                </a>
            </div>

            <div class="row align-items-center">
                <div class="col">
                    <div class="page-pretitle">Manage / Playlist</div>
                    <h2 class="owl-page-title mb-1">{{ $title }}</h2>
                    <div class="owl-page-subtitle">Create a new playlist by combining multiple learning materials.</div>
                </div>
            </div>
        </div>
    </div>

    <div class="page-body">
        <div class="container-xl">
            <form action="{{ route('playlist.store') }}" method="post" autocomplete="off" id="trainingPlanForm" enctype="multipart/form-data">
                @csrf
                @method('POST')

                <div class="row g-3">

                    {{-- Kolom Kiri: Konten Playlist --}}
                    <div class="col-lg-8">

                        {{-- Informasi Playlist --}}
                        <div class="card owl-main-card mb-3">
                            <div class="card-header">
                                <h3 class="card-title">Informasi Playlist</h3>
                            </div>
                            <div class="card-body">

                                <x-alert type='warning'/>

                                <div class="mb-3">
                                    <label for="title" class="form-label required">Title</label>
                                    <input type="text" class="form-control @error('title') is-invalid @enderror" placeholder="Enter playlist title"
                                        name="title" id="title" value="{{ old('title') }}" required>
                                    @error('title')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                                <div class="mb-3">
                                    <label for="url-link" class="form-label">URL Link</label>
                                    <input type="text" name="url_link" id="url-link" class="form-control @error('url_link') is-invalid @enderror"
                                        placeholder="https://..." value="{{ old('url_link') }}">
                                    <small class="form-hint">Optional. An external link related to this playlist.</small>
                                    @error('url_link')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                                <div class="mb-3">
                                    <label for="total_duration" class="form-label">Playlist Total Duration (HH:MM:SS)</label>
                                    <input type="text"
                                        class="form-control @error('total_duration') is-invalid @enderror"
                                        id="total_duration"
                                        name="total_duration"
                                        placeholder="00:00:00"
                                        value="{{ old('total_duration', '00:00:00') }}">
                                    <small class="form-hint">Format: HH:MM:SS (contoh: 02:30:00). Dapat diisi manual.</small>
                                    @error('total_duration')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                                <div class="mb-3">
                                    <label for="learning_hours" class="form-label">Learning Hours</label>
                                    <input type="text"
                                        class="form-control @error('learning_hours') is-invalid @enderror"
                                        id="learning_hours"
                                        name="learning_hours"
                                        placeholder="0"
                                        value="{{ old('learning_hours', 0) }}">
                                    <small class="form-hint">Learning Hours dapat diisi manual.</small>
                                    <div class="invalid-feedback" id="learning_hours_error" style="{{ $errors->has('learning_hours') ? 'display:block;' : '' }}">
                                        @error('learning_hours')
                                            {{ $message }}
                                        @enderror
                                    </div>
                                </div>
                            </div>
                        </div>

                        {{-- Konten / Playlist --}}
                        <div class="card owl-main-card">
                            <div class="card-header">
                                <div>
                                    <h3 class="card-title">Konten Playlist</h3>
                                    <p class="text-muted mb-0" style="font-size:13px;">
                                        Select content, then drag and drop items to arrange the section order.
                                    </p>
                                </div>
                                <span class="badge bg-blue-lt text-blue">Drag &amp; Drop</span>
                            </div>
                            <div class="card-body">
                                <label for="select-content" class="form-label">Content</label>
                                <div class="playlist-content-select-wrapper">
                                    <select class="form-select js-content-select" id="select-content" name="content[]" multiple>
                                        @unless (count($content) == 0)
                                            @foreach ($content as $list)
                                                <option value="{{ $list->id }}" data-status="{{ $list->active }}">{{ $list->title }}</option>
                                            @endforeach
                                        @else
                                            <option value="" disabled>No content available</option>
                                        @endunless
                                    </select>
                                </div>
                                <div class="d-flex justify-content-between mt-2">
                                    <small class="form-hint">Type to search content. Drag items to reorder sections.</small>
                                    <small class="content-select-count" id="content-count-label"></small>
                                </div>
                                <div class="mt-3">
                                    <button type="submit" form="trainingPlanForm" class="btn btn-primary w-100" name="action" value="save" formnovalidate>
                                        <svg xmlns="http://www.w3.org/2000/svg" class="icon me-1" width="16" height="16" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round">
                                            <path stroke="none" d="M0 0h24v24H0z" fill="none"></path>
                                            <path d="M6 4h10l4 4v10a2 2 0 0 1 -2 2h-12a2 2 0 0 1 -2 -2v-12a2 2 0 0 1 2 -2z"></path>
                                            <path d="M12 14m-2 0a2 2 0 1 0 4 0a2 2 0 1 0 -4 0"></path>
                                            <path d="M14 4l0 4l-6 0l0 -4"></path>
                                        </svg>
                                        Save Playlist
                                    </button>
                                </div>
                            </div>
                        </div>

                    </div>

                    {{-- Kolom Kanan: Thumbnail + Deadline + Aksi --}}
                    <div class="col-lg-4">

                        {{-- Thumbnail Playlist --}}
                        <div class="card owl-main-card mb-3">
                            <div class="card-header">
                                <h3 class="card-title">Thumbnail Playlist</h3>
                            </div>
                            <div class="card-body">
                                <div>
                                    <label class="form-label">Upload Thumbnail</label>
                                    <input type="file" name="thumbnail" id="thumbnail" class="form-control"
                                        accept="image/jpg,image/jpeg,image/png,image/webp">
                                    <small class="form-hint">Optional. Format: JPG, PNG, WEBP — maks. 2 MB.</small>
                                </div>
                            </div>
                        </div>

                        {{-- Deadline Playlist --}}
                        <div class="card owl-main-card mb-3">
                            <div class="card-header">
                                <h3 class="card-title">Deadline Playlist</h3>
                            </div>
                            <div class="card-body">
                                <div class="mb-3">
                                    <label class="form-check cursor-pointer">
                                        <input type="hidden" name="has_deadline" value="0">
                                        <input class="form-check-input" type="checkbox" id="has_deadline"
                                            name="has_deadline" value="1"
                                            @if(old('has_deadline')) checked @endif>
                                        <span class="form-check-label fw-medium">Set Deadline?</span>
                                    </label>
                                    <small class="form-hint d-block mt-1">If checked, specify the date range below.</small>
                                </div>
                                <div id="deadline-fields" style="{{ old('has_deadline') ? '' : 'display:none;' }}">
                                    <div class="mb-2">
                                        <label class="form-label">From Date</label>
                                        <input type="date" class="form-control" name="from_date" id="from_date"
                                            value="{{ old('from_date') }}">
                                    </div>
                                    <div>
                                        <label class="form-label">To Date</label>
                                        <input type="date" class="form-control" name="to_date" id="to_date"
                                            value="{{ old('to_date') }}">
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="card owl-main-card">
                            <div class="card-header">
                                <h3 class="card-title">Action</h3>
                            </div>
                            <div class="card-body">
                                <div class="d-grid gap-2">
                                    <button type="submit" class="btn btn-success" name="action" value="save" formnovalidate>
                                        <svg xmlns="http://www.w3.org/2000/svg" class="icon" width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round">
                                            <path stroke="none" d="M0 0h24v24H0z" fill="none"></path>
                                            <path d="M6 4h10l4 4v10a2 2 0 0 1 -2 2h-12a2 2 0 0 1 -2 -2v-12a2 2 0 0 1 2 -2z"></path>
                                            <path d="M12 14m-2 0a2 2 0 1 0 4 0a2 2 0 1 0 -4 0"></path>
                                            <path d="M14 4l0 4l-6 0l0 -4"></path>
                                        </svg>
                                        Save (Draft)
                                    </button>
                                    <button type="submit" class="btn btn-primary" name="action" value="publish">
                                        <svg xmlns="http://www.w3.org/2000/svg" class="icon" width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round">
                                            <path stroke="none" d="M0 0h24v24H0z" fill="none"></path>
                                            <path d="M7 11v8a1 1 0 0 1 -1 1h-2a1 1 0 0 1 -1 -1v-7a1 1 0 0 1 1 -1h3a4 4 0 0 0 4 -4v-1a2 2 0 0 1 4 0v5h3a2 2 0 0 1 2 2l-1 5a2 3 0 0 1 -2 2h-7a3 3 0 0 1 -3 -3"></path>
                                        </svg>
                                        Save & Publish
                                    </button>
                                    <a href="{{ route('playlist.index') }}" class="btn btn-light">Cancel</a>
                                </div>
                            </div>
                        </div>
                    </div>

                </div>
            </form>
        </div>
    </div>

    {{-- Unsaved Changes Confirmation Modal --}}
    <div class="modal modal-blur fade" id="unsavedChangesModal" tabindex="-1" role="dialog" aria-labelledby="unsavedChangesModalLabel" aria-hidden="true" data-bs-backdrop="static" data-backdrop="static">
        <div class="modal-dialog modal-dialog-centered" role="document">
            <div class="modal-content shadow rounded-3 border-0">
                <div class="modal-header border-0 pb-0">
                    <div class="d-flex align-items-center gap-2">
                        <span class="d-inline-flex align-items-center justify-content-center bg-warning-lt text-warning rounded" style="width: 20px; height: 20px; font-size: 14px;">
                            <i class="ti ti-alert-triangle" style="font-size: 14px;"></i>
                        </span>
                        <h5 class="modal-title fw-bold text-dark mb-0" id="unsavedChangesModalLabel">Unsaved Changes</h5>
                    </div>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" data-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body py-3">
                    <p class="text-dark mb-0" style="color: #212529 !important; font-size: 0.95rem; line-height: 1.5;">
                        Are you sure you want to leave? Any unsaved changes will be lost, or you can stay to finish editing.
                    </p>
                </div>
                <div class="modal-footer border-0 pt-0 d-flex justify-content-between align-items-center">
                    <button type="button" class="btn btn-outline-danger d-inline-flex align-items-center gap-1" id="btnDiscardAndLeave">
                        <i class="ti ti-trash"></i> Discard & Leave
                    </button>
                    <div class="d-flex align-items-center gap-2">
                        <button type="button" class="btn btn-info text-white" id="btnKeepEditing" data-bs-dismiss="modal" data-dismiss="modal">
                            Keep Editing
                        </button>
                        <button type="button" class="btn btn-primary d-inline-flex align-items-center gap-1" id="btnSaveDraftAndExit">
                            <i class="ti ti-device-floppy"></i> <span class="btn-save-text">Save as Draft & Exit</span>
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
    <script src="{{ asset('Tabler/dist/libs/tom-select/dist/js/tom-select.complete.min.js?1684106062') }}"></script>
    <script src="{{ asset('vendor/sortablejs/Sortable.min.js') }}"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            document.querySelectorAll('.js-content-select').forEach(function (selectEl) {
                if (selectEl.tomselect) return;

                var tom = new TomSelect(selectEl, {
                    plugins: ['remove_button'],
                    maxItems: null,
                    create: false,
                    persist: false,
                    hideSelected: true,
                    closeAfterSelect: false,
                    sortField: [],
                    dropdownParent: 'body',
                    placeholder: 'Type to search content...',
                    searchField: ['text'],
                    maxOptions: 80,
                    render: {
                        item: function (data, escape) {
                            var status = data.status || (data.$option ? data.$option.getAttribute('data-status') : null);
                            var isInactive = status && (status === 'Inactive' || status === 'unpublish' || status === 'draft');
                            var statusBadge = isInactive
                                ? '<span class="badge rounded-pill text-white fw-semibold px-2.5 py-1 ms-2" style="background-color: #f59e0b !important; font-size: 0.75rem; flex-shrink: 0;">Inactive</span>'
                                : '<span class="badge rounded-pill text-white fw-semibold px-2.5 py-1 ms-2" style="background-color: #28a745 !important; font-size: 0.75rem; flex-shrink: 0;">Active</span>';

                            return '<div class="playlist-section-item-inner d-flex align-items-center justify-content-between w-100">' +
                                '<div class="d-flex align-items-center gap-2 text-truncate me-2 min-w-0 flex-grow-1">' +
                                '<span class="playlist-drag-handle" title="Tarik untuk mengubah urutan">&#9776;</span>' +
                                '<span class="playlist-section-badge"></span>' +
                                '<span class="playlist-section-title fw-semibold text-truncate" title="' + escape(data.text) + '">' + escape(data.text) + '</span>' +
                                '</div>' +
                                '<div class="d-flex align-items-center gap-2 flex-shrink-0">' +
                                statusBadge +
                                '</div>' +
                                '</div>';
                        },
                        option: function (data, escape) {
                            var status = data.status || (data.$option ? data.$option.getAttribute('data-status') : null);
                            var isInactive = status && (status === 'Inactive' || status === 'unpublish' || status === 'draft');
                            var statusBadge = isInactive
                                ? '<span class="badge rounded-pill text-white fw-semibold px-2.5 py-1" style="background-color: #f59e0b !important; font-size: 0.75rem;">Inactive</span>'
                                : '<span class="badge rounded-pill text-white fw-semibold px-2.5 py-1" style="background-color: #28a745 !important; font-size: 0.75rem;">Active</span>';

                            return '<div class="playlist-option-item d-flex align-items-center justify-content-between"><span>' + escape(data.text) + '</span>' + statusBadge + '</div>';
                        },
                        no_results: function () {
                            return '<div class="p-2 text-muted" style="font-size:13px;">Konten tidak ditemukan</div>';
                        }
                    },
                    onInitialize: function () {
                        this.wrapper.classList.add('playlist-ts-wrapper');
                        enablePlaylistSortable(this);
                        updatePlaylistSectionNumbers(this);
                        updateCountLabel(this);
                    },
                    onItemAdd: function () {
                        updatePlaylistSectionNumbers(this);
                        updateCountLabel(this);
                    },
                    onItemRemove: function () {
                        updatePlaylistSectionNumbers(this);
                        updateCountLabel(this);
                    }
                });

                function enablePlaylistSortable(tomInstance) {
                    if (typeof Sortable === 'undefined') {
                        console.error('SortableJS belum dimuat.');
                        return;
                    }
                    new Sortable(tomInstance.control, {
                        animation: 180,
                        handle: '.playlist-drag-handle',
                        draggable: '.item',
                        forceFallback: true,
                        fallbackOnBody: true,
                        swapThreshold: 0.65,
                        ghostClass: 'playlist-sortable-ghost',
                        chosenClass: 'playlist-sortable-chosen',
                        dragClass: 'playlist-sortable-drag',
                        onEnd: function () {
                            var orderedValues = Array.from(tomInstance.control.querySelectorAll('.item[data-value]'))
                                .map(function (item) { return item.getAttribute('data-value'); })
                                .filter(Boolean);
                            tomInstance.items = orderedValues;
                            tomInstance.setCaret(orderedValues.length);
                            tomInstance.refreshItems();
                            tomInstance.refreshOptions(false);
                            updatePlaylistSectionNumbers(tomInstance);
                            updateCountLabel(tomInstance);
                        }
                    });
                }

                function updatePlaylistSectionNumbers(tomInstance) {
                    Array.from(tomInstance.control.querySelectorAll('.item[data-value]')).forEach(function (item, index) {
                        var badge = item.querySelector('.playlist-section-badge');
                        if (badge) badge.textContent = 'SECTION ' + (index + 1);
                    });
                }

                function updateCountLabel(tomInstance) {
                    var count = tomInstance.items.length;
                    var total = Object.keys(tomInstance.options).length;
                    var label = document.getElementById('content-count-label');
                    if (!label) return;
                    label.textContent = count > 0 ? count + ' dari ' + total + ' konten dipilih' : '';
                }

                var formEl = selectEl.closest('form');
                if (formEl) {
                    formEl.addEventListener('submit', function () {
                        if (!selectEl.tomselect) return;
                        formEl.querySelectorAll('input.js-content-ordered').forEach(function (el) { el.remove(); });
                        selectEl.removeAttribute('name');
                        Array.from(tom.control.querySelectorAll('.item[data-value]')).forEach(function (item) {
                            var inp = document.createElement('input');
                            inp.type = 'hidden';
                            inp.name = 'content[]';
                            inp.value = item.getAttribute('data-value');
                            inp.className = 'js-content-ordered';
                            formEl.appendChild(inp);
                        });
                    });
                }
            });
        });
    </script>
    <script>
        (function () {
            var cb = document.getElementById('has_deadline');
            var fields = document.getElementById('deadline-fields');
            if (!cb || !fields) return;
            cb.addEventListener('change', function () {
                if (this.checked) {
                    fields.style.display = '';
                } else {
                    fields.style.display = 'none';
                    var fd = document.getElementById('from_date');
                    var td = document.getElementById('to_date');
                    if (fd) fd.value = '';
                    if (td) td.value = '';
                }
            });
        })();
    </script>
    <script>
        (function () {
            var $input = $('#learning_hours');
            var $form = $('#trainingPlanForm');

            function isValidLearningHours(val) {
                if (val === '' || val === null || val === undefined) return false;
                var num = parseFloat(val);
                if (isNaN(num)) return false;
                if (num < 0) return false;
                if (num === 0) return true;

                var timesTwo = Math.round(num * 10000) / 10000 * 2;
                return Math.abs(timesTwo - Math.round(timesTwo)) < 0.0001;
            }

            function validateInput() {
                var val = $input.val();
                var $errorDiv = $('#learning_hours_error');

                if (!isValidLearningHours(val)) {
                    $input.addClass('is-invalid');
                    if ($errorDiv.length) {
                        $errorDiv.html('Learning Hours must be 0 or in increments of 0.5.<br><small class="text-muted">Examples: 0, 0.5, 1, 1.5, 2, 2.5</small>').show();
                    }
                    return false;
                } else {
                    $input.removeClass('is-invalid');
                    if ($errorDiv.length) {
                        $errorDiv.empty().hide();
                    }
                    return true;
                }
            }

            $input.on('input change blur', function () {
                validateInput();
            });

            if ($form.length) {
                $form.on('submit', function (e) {
                    if (!validateInput()) {
                        e.preventDefault();
                        e.stopPropagation();
                        $input.focus();
                        return false;
                    }
                });
            }
        })();

        $(document).ready(function() {
            function showToast(message, type) {
                let bgClass = type === 'success' ? 'bg-success' : 'bg-danger';
                let icon = type === 'success' ? 'fa-check-circle' : 'fa-times-circle';

                let toastHtml = `
                    <div class="toast owl-toast ${bgClass} text-white align-items-center mb-2 shadow" role="alert" aria-live="assertive" aria-atomic="true">
                        <div class="toast-body d-flex align-items-center justify-content-between p-3">
                            <div class="d-flex align-items-center">
                                <i class="fas ${icon} me-2 fs-4"></i>
                                <span>${message}</span>
                            </div>
                        </div>
                    </div>
                `;

                if ($('#owl-toast-wrapper').length === 0) {
                    $('body').append('<div id="owl-toast-wrapper" style="position: fixed; top: 1.5rem; right: 1.5rem; z-index: 9999; width: 320px;"></div>');
                }

                let $toast = $(toastHtml);
                $('#owl-toast-wrapper').append($toast);
                $toast.fadeIn(200).delay(3000).fadeOut(400, function() { $(this).remove(); });
            }
        });

        function openUnsavedChangesModal() {
            var modalEl = document.getElementById('unsavedChangesModal');
            if (!modalEl) return;

            if (typeof $ !== 'undefined' && $.fn && typeof $.fn.modal === 'function') {
                $(modalEl).modal('show');
            } else if (typeof bootstrap !== 'undefined' && typeof bootstrap.Modal === 'function') {
                var modalInstance = bootstrap.Modal.getOrCreateInstance ? bootstrap.Modal.getOrCreateInstance(modalEl) : new bootstrap.Modal(modalEl);
                modalInstance.show();
            } else if (window.bootstrap && typeof window.bootstrap.Modal === 'function') {
                var modalInstance = window.bootstrap.Modal.getOrCreateInstance ? window.bootstrap.Modal.getOrCreateInstance(modalEl) : new window.bootstrap.Modal(modalEl);
                modalInstance.show();
            } else {
                modalEl.style.display = 'block';
                modalEl.classList.add('show');
                document.body.classList.add('modal-open');
                if (!document.querySelector('.modal-backdrop')) {
                    var bd = document.createElement('div');
                    bd.className = 'modal-backdrop fade show';
                    document.body.appendChild(bd);
                }
            }
        }

        function closeUnsavedChangesModal() {
            var modalEl = document.getElementById('unsavedChangesModal');
            if (!modalEl) return;

            if (typeof $ !== 'undefined' && $.fn && typeof $.fn.modal === 'function') {
                $(modalEl).modal('hide');
            } else if (typeof bootstrap !== 'undefined' && typeof bootstrap.Modal === 'function') {
                var modalInstance = bootstrap.Modal.getInstance ? bootstrap.Modal.getInstance(modalEl) : null;
                if (modalInstance) {
                    modalInstance.hide();
                } else {
                    (new bootstrap.Modal(modalEl)).hide();
                }
            } else if (window.bootstrap && typeof window.bootstrap.Modal === 'function') {
                var modalInstance = window.bootstrap.Modal.getInstance ? window.bootstrap.Modal.getInstance(modalEl) : null;
                if (modalInstance) {
                    modalInstance.hide();
                }
            } else {
                modalEl.style.display = 'none';
                modalEl.classList.remove('show');
                document.body.classList.remove('modal-open');
                var bd = document.querySelector('.modal-backdrop');
                if (bd) bd.remove();
            }
        }

        // 1. Back button click handler (both jQuery and native)
        $(document).on('click', '.btn-back', function (e) {
            e.preventDefault();
            e.stopPropagation();
            openUnsavedChangesModal();
        });

        // 2. Modal dismissal handlers ("X" and "Keep Editing")
        $(document).on('click', '#unsavedChangesModal .btn-close, #unsavedChangesModal [data-bs-dismiss="modal"], #unsavedChangesModal [data-dismiss="modal"], #btnKeepEditing', function (e) {
            e.preventDefault();
            closeUnsavedChangesModal();
        });

        // 3. "Discard & Leave" handler
        $(document).on('click', '#btnDiscardAndLeave', function (e) {
            e.preventDefault();
            window.location.href = "{{ route('playlist.index') }}";
        });

        // 4. "Save as Draft & Exit" handler
        $(document).on('click', '#btnSaveDraftAndExit', function (e) {
            e.preventDefault();
            var saveBtn = this;
            var btnText = saveBtn.querySelector('.btn-save-text');
            saveBtn.disabled = true;
            if (btnText) btnText.textContent = 'Saving Draft...';

            var form = document.getElementById('trainingPlanForm') || document.querySelector('form');
            if (form) {
                var selectEl = document.querySelector('.js-content-select');
                if (selectEl && selectEl.tomselect) {
                    form.querySelectorAll('input.js-content-ordered').forEach(function (el) { el.remove(); });
                    selectEl.removeAttribute('name');
                    Array.from(selectEl.tomselect.control.querySelectorAll('.item[data-value]')).forEach(function (item) {
                        var inp = document.createElement('input');
                        inp.type = 'hidden';
                        inp.name = 'content[]';
                        inp.value = item.getAttribute('data-value');
                        inp.className = 'js-content-ordered';
                        form.appendChild(inp);
                    });
                }

                var formData = new FormData(form);
                formData.set('action', 'draft');

                fetch(form.action, {
                    method: 'POST',
                    body: formData,
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest',
                        'Accept': 'application/json'
                    }
                })
                .then(function (response) {
                    if (!response.ok) {
                        return response.json().then(function (err) { throw err; });
                    }
                    return response.json();
                })
                .then(function (data) {
                    window.location.href = "{{ route('playlist.index') }}";
                })
                .catch(function (err) {
                    console.error('AJAX draft save failed, submitting fallback:', err);
                    var actionInput = form.querySelector('input[name="action"]');
                    if (!actionInput) {
                        actionInput = document.createElement('input');
                        actionInput.type = 'hidden';
                        actionInput.name = 'action';
                        form.appendChild(actionInput);
                    }
                    actionInput.value = 'draft';
                    form.submit();
                });
            }
        });

        // Vanilla fallback listener
        document.addEventListener('click', function (e) {
            var backBtn = e.target.closest('.btn-back');
            if (backBtn) {
                e.preventDefault();
                e.stopPropagation();
                openUnsavedChangesModal();
                return;
            }

            var dismissBtn = e.target.closest('#unsavedChangesModal .btn-close, #unsavedChangesModal [data-bs-dismiss="modal"], #unsavedChangesModal [data-dismiss="modal"], #btnKeepEditing');
            if (dismissBtn) {
                e.preventDefault();
                closeUnsavedChangesModal();
                return;
            }

            var discardBtn = e.target.closest('#btnDiscardAndLeave');
            if (discardBtn) {
                e.preventDefault();
                window.location.href = "{{ route('playlist.index') }}";
                return;
            }
        });
    </script>
@endpush