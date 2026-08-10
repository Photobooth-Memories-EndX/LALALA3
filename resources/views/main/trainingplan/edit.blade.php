@extends('layouts.sidebar-master')

@push('styles')
    <link rel="stylesheet" href="{{ asset('Tabler/dist/css/tabler-vendors.min.css?1684106062') }}">
    @include('main.trainingplan.components.tomselect-content-style')
@endpush

@section('content')
    @include('main.monitoring.components.style')

    {{-- Page Header --}}
    <div class="page-header d-print-none mb-3">
        <div class="container-xl">
            <div class="row align-items-center">
                <div class="col">
                    <div class="page-pretitle">Manage / Playlist</div>
                    <h2 class="owl-page-title mb-1">{{ $title }}</h2>
                    <div class="owl-page-subtitle">
                        {{ $trainingplan->title }}
                        <span class="badge bg-{{ $trainingplan->active == 'Active' ? 'success' : 'warning' }}-lt ms-2">
                            {{ $trainingplan->active }}
                        </span>
                    </div>
                </div>
                <div class="col-auto ms-auto">
                    <ol class="breadcrumb breadcrumb-arrows mb-0" aria-label="breadcrumbs">
                        <li class="breadcrumb-item"><a href="javascript:;">Manage</a></li>
                        <li class="breadcrumb-item"><a href="{{ route('playlist.index') }}">Playlist</a></li>
                        <li class="breadcrumb-item active" aria-current="page">{{ $title }}</li>
                    </ol>
                </div>
            </div>
        </div>
    </div>

    <div class="page-body">
        <div class="container-xl">
            <form action="{{ route('playlist.update', $trainingplan->slug) }}" method="post" autocomplete="off" id="trainingPlanForm" enctype="multipart/form-data">
                @csrf
                @method('PUT')
                <input type="hidden" name="savetype" value="update">

                <div class="row g-3">

                    {{-- Kolom Kiri: Konten Playlist --}}
                    <div class="col-lg-8">

                        {{-- Informasi Playlist --}}
                        <div class="card owl-main-card mb-3">
                            <div class="card-header">
                                <h3 class="card-title">Informasi Playlist</h3>
                            </div>
                            <div class="card-body">

                                <x-alert type="success" />
                                <x-alert type="warning" />

                                <div class="mb-3">
                                    <label for="title" class="form-label required">Title</label>
                                    <input type="text" class="form-control @error('title') is-invalid @enderror" placeholder="Enter playlist title"
                                        name="title" id="title"
                                        value="{{ old('title', $trainingplan->title) }}" required>
                                    @error('title')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                                <div class="mb-3">
                                    <label for="url-link" class="form-label">URL Link</label>
                                    <input type="url" name="url_link" id="url-link" class="form-control @error('url_link') is-invalid @enderror"
                                        value="{{ old('url_link', $trainingplan->url_link) }}"
                                        placeholder="https://...">
                                    <small class="form-hint">Opsional. Link eksternal terkait playlist ini.</small>
                                    @error('url_link')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                                <div class="mb-3">
                                    <label for="total_duration" class="form-label">Playlist Total Duration (HH:MM:SS)</label>
                                    <div class="row g-2 align-items-center">
                                        <div class="col-12 col-sm-7 col-md-8">
                                            <input type="text"
                                                class="form-control bg-light @error('total_duration') is-invalid @enderror"
                                                id="total_duration"
                                                name="total_duration"
                                                value="{{ old('total_duration', \App\Support\DurationFormatter::fromSeconds($trainingplan->total_duration_seconds ?? 0)) }}"
                                                readonly>
                                        </div>
                                        <div class="col-12 col-sm-5 col-md-4">
                                            <button type="button"
                                                class="btn btn-outline-primary w-100"
                                                id="btn-sync-playlist"
                                                data-url="{{ route('playlist.synchronize-learning-hours', $trainingplan->slug ?? $trainingplan->id) }}">
                                                <i class="ti ti-refresh me-1 icon-sync"></i>
                                                <span class="btn-text">Synchronize</span>
                                            </button>
                                        </div>
                                    </div>
                                    <small class="form-hint mt-1">Gunakan tombol Synchronize untuk menghitung ulang total duration dan learning hours.</small>
                                    @error('total_duration')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                                <div class="mb-3">
                                    <label for="learning_hours" class="form-label">Learning Hours</label>
                                    <input type="text"
                                        class="form-control bg-light @error('learning_hours') is-invalid @enderror"
                                        id="learning_hours"
                                        name="learning_hours"
                                        value="{{ old('learning_hours', $trainingplan->learning_hours ?? 0) }}"
                                        readonly>
                                    @error('learning_hours')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                        </div>

                        {{-- Konten Playlist --}}
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
                                <label class="form-label">Content</label>
                                <div class="playlist-content-select-wrapper">
                                    <select class="form-select js-content-select" id="select-content" name="content[]" multiple>
                                        @unless (count($content) == 0)
                                            @foreach ($content as $list)
                                                <option value="{{ $list->id }}"
                                                    @if (in_array($list->id, $activecontent)) selected @endif>
                                                    {{ $list->title }}
                                                </option>
                                            @endforeach
                                        @else
                                            <option value="" disabled>No content available</option>
                                        @endunless
                                    </select>
                                </div>
                                <div class="d-flex justify-content-between mt-2">
                                    <small class="form-hint">Only published content is available. Type to search.</small>
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
                                        Simpan Perubahan Playlist
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
                                @if($trainingplan->thumbnail_url)
                                    <div class="mb-2">
                                        <img src="{{ $trainingplan->thumbnail_url }}"
                                            alt="Thumbnail" class="img-fluid rounded"
                                            style="max-height:120px; object-fit:cover;">
                                        <div class="small text-muted mt-1">Thumbnail saat ini.</div>
                                    </div>
                                @endif
                                <div>
                                    <label class="form-label">{{ $trainingplan->thumbnail_url ? 'Ganti Thumbnail' : 'Upload Thumbnail' }}</label>
                                    <input type="file" name="thumbnail" id="thumbnail" class="form-control"
                                        accept="image/jpg,image/jpeg,image/png,image/webp">
                                    <small class="form-hint">Optional. Format: JPG, PNG, WEBP — maks. 2 MB.</small>
                                </div>
                            </div>
                        </div>

                        {{-- Deadline Playlist --}}
                        @php
                            $showDeadline = old('has_deadline') !== null
                                ? (bool) old('has_deadline')
                                : (bool) $trainingplan->has_deadline;
                        @endphp
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
                                            @if($showDeadline) checked @endif>
                                        <span class="form-check-label fw-medium">Set Deadline?</span>
                                    </label>
                                    <small class="form-hint d-block mt-1">If checked, specify the date range below.</small>
                                </div>
                                <div id="deadline-fields" style="{{ $showDeadline ? '' : 'display:none;' }}">
                                    <div class="mb-2">
                                        <label class="form-label">From Date</label>
                                        <input type="date" class="form-control" name="from_date" id="from_date"
                                            value="{{ old('from_date', $trainingplan->from_date ? $trainingplan->from_date->format('Y-m-d') : '') }}">
                                    </div>
                                    <div>
                                        <label class="form-label">To Date</label>
                                        <input type="date" class="form-control" name="to_date" id="to_date"
                                            value="{{ old('to_date', $trainingplan->to_date ? $trainingplan->to_date->format('Y-m-d') : '') }}">
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
                                    @if ($trainingplan->active == 'Active')
                                        <button type="submit" class="btn btn-warning" name="action" value="unpublish">
                                            <svg xmlns="http://www.w3.org/2000/svg" class="icon" width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round">
                                                <path stroke="none" d="M0 0h24v24H0z" fill="none"></path>
                                                <path d="M3 12a9 9 0 1 0 18 0a9 9 0 0 0 -18 0"></path>
                                                <path d="M9 12h6"></path>
                                            </svg>
                                            Unpublish This Playlist
                                        </button>
                                    @else
                                        <a type="button" class="btn btn-secondary" href="{{ route('playlist.order', $trainingplan->slug) }}">
                                            <svg xmlns="http://www.w3.org/2000/svg" class="icon" width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round">
                                                <path stroke="none" d="M0 0h24v24H0z" fill="none"></path>
                                                <path d="M3 9l4 -4l4 4m-4 -4v14"></path>
                                                <path d="M21 15l-4 4l-4 -4m4 4v-14"></path>
                                            </svg>
                                            Re-order Content
                                        </a>
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
                                    @endif
                                    <a href="{{ route('playlist.index') }}" class="btn btn-light">Back to List</a>
                                </div>
                            </div>
                        </div>
                    </div>

                </div>
            </form>
        </div>
    </div>

@endsection

@push('scripts')
    <script src="{{ asset('Tabler/dist/libs/tom-select/dist/js/tom-select.complete.min.js?1684106062') }}"></script>
    <script src="https://cdn.jsdelivr.net/npm/sortablejs@1.15.6/Sortable.min.js"></script>
    <script>
        var activeContentOrdered = @json(
            $trainingplan->trainingplandetail->sortBy('content_order')->pluck('content_id')->values()->toArray()
        );

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
                            return '<div class="playlist-section-item-inner">' +
                                '<span class="playlist-drag-handle" title="Tarik untuk mengubah urutan">&#9776;</span>' +
                                '<span class="playlist-section-badge"></span>' +
                                '<span class="playlist-section-title">' + escape(data.text) + '</span>' +
                                '</div>';
                        },
                        option: function (data, escape) {
                            return '<div class="playlist-option-item"><span>' + escape(data.text) + '</span></div>';
                        },
                        no_results: function () {
                            return '<div class="p-2 text-muted" style="font-size:13px;">Konten tidak ditemukan</div>';
                        }
                    },
                    onInitialize: function () {
                        var self = this;
                        self.wrapper.classList.add('playlist-ts-wrapper');

                        if (activeContentOrdered.length > 0) {
                            self.clear(true);
                            activeContentOrdered.forEach(function (id) {
                                self.addItem(String(id), true);
                            });
                        }

                        enablePlaylistSortable(self);
                        updatePlaylistSectionNumbers(self);
                        updateCountLabel(self);
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

            $(document).on('click', '#btn-sync-playlist, #btn-sync-playlist-lh', function(e) {
                e.preventDefault();
                let $btn = $(this);
                let url = $btn.data('url');

                if (!url || $btn.data('loading')) return;

                $btn.data('loading', true);
                let $form = $btn.closest('form');
                let selectedContents = [];
                $form.find('input[name="content[]"]:checked, select[name="content[]"] option:selected, input.js-content-ordered').each(function() {
                    selectedContents.push($(this).val());
                });

                $btn.prop('disabled', true);
                $btn.find('.icon-sync').attr('class', 'spinner-border spinner-border-sm me-1 icon-sync');
                $btn.find('.btn-text').text('Synchronizing...');
                $('body').css('cursor', 'wait');

                let csrfToken = $('meta[name="csrf-token"]').attr('content') || '{{ csrf_token() }}';

                $.ajax({
                    url: url,
                    type: 'POST',
                    dataType: 'json',
                    data: {
                        _token: csrfToken,
                        content: selectedContents
                    },
                    success: function(res) {
                        if (res && res.success) {
                            if (res.duration !== undefined) {
                                $form.find('[name="total_duration"]').val(res.duration);
                            }
                            if (res.learning_hours !== undefined) {
                                $form.find('[name="learning_hours"]').val(res.learning_hours);
                            }
                            showToast(res.message || 'Playlist duration synchronized successfully.', 'success');
                        } else {
                            showToast(res && res.message ? res.message : 'Unable to synchronize duration.', 'danger');
                        }
                    },
                    error: function(xhr) {
                        let message = 'Unable to synchronize duration.';
                        if (xhr && xhr.responseJSON) {
                            if (xhr.responseJSON.errors) {
                                message = Object.values(xhr.responseJSON.errors).flat().join('<br>');
                            } else if (xhr.responseJSON.message) {
                                message = xhr.responseJSON.message;
                            }
                        }
                        showToast(message, 'danger');
                    },
                    complete: function() {
                        $btn.find('.icon-sync').attr('class', 'ti ti-refresh me-1 icon-sync');
                        $btn.find('.btn-text').text('Synchronize');
                        $btn.prop('disabled', false);
                        $btn.data('loading', false);
                        $('body').css('cursor', 'default');
                    }
                });
            });
        });
    </script>
@endpush