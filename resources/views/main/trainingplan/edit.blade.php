@extends('layouts.sidebar-master')

@php
    $trainingplan = $trainingplan ?? null;
    $isLocked = $trainingplan && $trainingplan->active === 'Active';
    $title = $title ?? 'Edit Playlist';
@endphp

@section('content')
    @include('main.monitoring.components.style')

    {{-- Page Header --}}
    <div class="page-header d-print-none mb-3">
        <div class="container-xl">
            <div style="max-width: 1100px; margin: 0 auto;">
                <div class="mb-3">
                    <a href="{{ route('playlist.index') }}" class="btn-back d-inline-flex align-items-center gap-1 text-decoration-none">
                        <i class="ti ti-arrow-left"></i>
                        <span>Back</span>
                    </a>
                </div>
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
    </div>

    <div class="page-body">
        <div class="container-xl">
            <div style="max-width: 1100px; margin: 0 auto;">

                <x-alert type="success" />
                <x-alert type="warning" />
                <x-alert type="danger" />

                <form action="{{ route('playlist.update', $trainingplan->slug) }}" method="post" autocomplete="off" id="trainingPlanForm" enctype="multipart/form-data">
                    @csrf
                    @method('PUT')
                    <input type="hidden" name="savetype" value="update">

                    <div class="row g-3">
                        <div class="col-12">

                            {{-- ① Publish Status --}}
                            @include('main.trainingplan.components.playlist-edit-publish-status', [
                                'trainingplan' => $trainingplan,
                                'stepNumber'   => 1,
                                'isLocked'     => $isLocked,
                            ])

                            {{-- ② Playlist Information --}}
                            @include('main.trainingplan.components.playlist-edit-information', [
                                'trainingplan' => $trainingplan,
                                'stepNumber'   => 2,
                                'isLocked'     => $isLocked,
                            ])

                            {{-- ③ Thumbnail & Live Preview --}}
                            @include('main.trainingplan.components.playlist-edit-thumbnail', [
                                'trainingplan' => $trainingplan,
                                'stepNumber'   => 3,
                                'isLocked'     => $isLocked,
                            ])

                            {{-- ④ Duration & Learning Hours --}}
                            @include('main.trainingplan.components.playlist-edit-duration', [
                                'trainingplan' => $trainingplan,
                                'stepNumber'   => 4,
                                'isLocked'     => $isLocked,
                            ])

                            {{-- ⑤ Playlist Content (Assignment & Drag and Drop) --}}
                            @include('main.trainingplan.components.playlist-edit-content', [
                                'trainingplan'  => $trainingplan,
                                'content'       => $content,
                                'activecontent' => $activecontent,
                                'stepNumber'    => 5,
                                'isLocked'      => $isLocked,
                            ])

                            {{-- ⑥ Deadline & Action Buttons --}}
                            @include('main.trainingplan.components.playlist-edit-action', [
                                'trainingplan' => $trainingplan,
                                'stepNumber'   => 6,
                                'isLocked'     => $isLocked,
                            ])

                        </div>
                    </div>
                </form>

            </div>
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
                        <i class="ti ti-trash"></i> Discard &amp; Leave
                    </button>
                    <div class="d-flex align-items-center gap-2">
                        <button type="button" class="btn btn-info text-white" id="btnKeepEditing" data-bs-dismiss="modal" data-dismiss="modal">
                            Keep Editing
                        </button>
                        <button type="button" class="btn btn-primary d-inline-flex align-items-center gap-1" id="btnSaveDraftAndExit">
                            <i class="ti ti-device-floppy"></i> <span class="btn-save-text">Save as Draft &amp; Exit</span>
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection

@section('style')
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

        .form-control:disabled,
        .form-select:disabled,
        .form-check-input:disabled:not(:checked),
        .form-check-input[disabled]:not(:checked) {
            background-color: var(--tblr-bg-surface-secondary, #f8fafc) !important;
            color: var(--tblr-muted, #64748b) !important;
            cursor: not-allowed !important;
            opacity: 0.85 !important;
        }

        .form-check-input:disabled:checked,
        .form-check-input[disabled]:checked {
            background-color: var(--tblr-primary, #206bc4) !important;
            border-color: var(--tblr-primary, #206bc4) !important;
            opacity: 0.7 !important;
            cursor: not-allowed !important;
        }

        [data-bs-theme="dark"] .form-control:disabled,
        [data-bs-theme="dark"] .form-select:disabled,
        body.dark-mode .form-control:disabled,
        body.dark-mode .form-select:disabled {
            background-color: rgba(255, 255, 255, .06) !important;
            border-color: rgba(255, 255, 255, .16) !important;
            color: rgba(255, 255, 255, .5) !important;
        }
    </style>
@endsection

@section('script')
    <script src="{{ asset('Tabler/dist/libs/tom-select/dist/js/tom-select.complete.min.js?1684106062') }}"></script>
    <script src="{{ asset('Tabler/dist/libs/fslightbox/index.js?1684106062') }}"></script>
    <script src="https://cdn.jsdelivr.net/npm/sortablejs@1.15.6/Sortable.min.js"></script>
    <script>
        var activeContentOrdered = @json(
            $trainingplan->trainingplandetail->sortBy(fn ($d) => (int) $d->content_order)->pluck('content_id')->values()->toArray()
        );

        var isPlaylistLocked = {{ $isLocked ? 'true' : 'false' }};

        document.addEventListener('DOMContentLoaded', function () {
            // ── TomSelect + SortableJS Initialization ───────────────────────
            document.querySelectorAll('.js-content-select').forEach(function (selectEl) {
                if (selectEl.tomselect) return;

                var tom = new TomSelect(selectEl, {
                    plugins: isPlaylistLocked ? [] : ['remove_button'],
                    maxItems: null,
                    create: false,
                    persist: false,
                    hideSelected: true,
                    closeAfterSelect: false,
                    sortField: [],
                    dropdownParent: 'body',
                    placeholder: isPlaylistLocked ? 'Playlist locked (Active)' : 'Type to search materials...',
                    searchField: ['text'],
                    maxOptions: 100,
                    render: {
                        item: function (data, escape) {
                            var status = data.status || (data.$option ? data.$option.getAttribute('data-status') : null);
                            var isInactive = status && (status === 'Inactive' || status === 'unpublish' || status === 'draft');
                            var statusBadge = isInactive
                                ? '<span class="badge rounded-pill text-white fw-semibold px-2.5 py-1 ms-2" style="background-color: #f59e0b !important; font-size: 0.75rem; flex-shrink: 0;">Inactive</span>'
                                : '<span class="badge rounded-pill text-white fw-semibold px-2.5 py-1 ms-2" style="background-color: #28a745 !important; font-size: 0.75rem; flex-shrink: 0;">Active</span>';

                            var dragHandle = isPlaylistLocked
                                ? ''
                                : '<span class="playlist-drag-handle" title="Drag to rearrange order">&#9776;</span>';

                            return '<div class="playlist-section-item-inner d-flex align-items-center justify-content-between w-100">' +
                                '<div class="d-flex align-items-center gap-2 text-truncate me-2 min-w-0 flex-grow-1">' +
                                dragHandle +
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
                            return '<div class="p-2 text-muted" style="font-size:13px;">No materials found</div>';
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

                        if (isPlaylistLocked) {
                            self.disable();
                        } else {
                            enablePlaylistSortable(self);
                        }

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
                    if (typeof Sortable === 'undefined' || isPlaylistLocked) return;

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
                    label.textContent = count > 0 ? count + ' of ' + total + ' materials selected' : '';
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

            // ── Deadline toggle ─────────────────────────────────────────────
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

            // ── Thumbnail Live Preview & Remove ─────────────────────────────
            (function () {
                var input = document.getElementById('playlist_thumbnail_input');
                var linkEl = document.getElementById('playlistThumbnailLink');
                var imgEl = document.getElementById('playlistThumbnailImg');
                var emptyEl = document.getElementById('playlistThumbnailEmpty');
                var removeBtn = document.getElementById('btn-remove-playlist-thumb');
                var removeHidden = document.getElementById('remove_thumbnail');
                var currentWrapper = document.getElementById('current-thumbnail-wrapper');
                var labelThumb = document.getElementById('label-thumbnail-file');

                if (input) {
                    input.addEventListener('change', function (e) {
                        var file = e.target.files && e.target.files[0];
                        if (file) {
                            var objectUrl = URL.createObjectURL(file);
                            if (imgEl && linkEl) {
                                imgEl.src = objectUrl;
                                linkEl.href = objectUrl;
                                linkEl.style.display = 'flex';
                                if (emptyEl) emptyEl.style.display = 'none';
                                if (window.refreshFsLightbox) {
                                    refreshFsLightbox();
                                }
                            }
                            if (removeHidden) removeHidden.value = '0';
                        }
                    });
                }

                if (removeBtn) {
                    removeBtn.addEventListener('click', function (e) {
                        e.preventDefault();
                        if (confirm('Are you sure you want to remove the thumbnail for this playlist?')) {
                            if (removeHidden) removeHidden.value = '1';
                            if (currentWrapper) currentWrapper.remove();
                            if (input) input.value = '';
                            if (labelThumb) labelThumb.textContent = 'Upload Thumbnail';
                            if (linkEl) linkEl.style.display = 'none';
                            if (imgEl) imgEl.src = '';
                            if (emptyEl) emptyEl.style.display = 'flex';
                            if (window.refreshFsLightbox) {
                                refreshFsLightbox();
                            }
                        }
                    });
                }
            })();
        });

        // ── Toast Notifications ─────────────────────────────────────────────
        function showPlaylistToast(message, type) {
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

        // ── Namespaced Synchronize Button Handler ───────────────────────────
        $(document)
            .off('click.playlistSync', '#btn-sync-playlist, #btn-sync-playlist-lh')
            .on('click.playlistSync', '#btn-sync-playlist, #btn-sync-playlist-lh', function(e) {
                e.preventDefault();
                let $btn = $(this);
                let url = $btn.data('url');

                if (!url || $btn.data('loading')) return;

                $btn.data('loading', true);
                let $form = $btn.closest('form');
                if (!$form.length) {
                    $form = $('#trainingPlanForm');
                }

                // Obtain current ordered content IDs from active TomSelect instance
                let selectedContents = [];
                let selectEl = document.querySelector('.js-content-select');
                if (selectEl && selectEl.tomselect) {
                    selectedContents = selectEl.tomselect.items ? Array.from(selectEl.tomselect.items) : [];
                } else {
                    $form.find('input[name="content[]"]:checked, select[name="content[]"] option:selected, input.js-content-ordered').each(function() {
                        selectedContents.push($(this).val());
                    });
                }

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
                            showPlaylistToast(res.message || 'Playlist duration & learning hours synchronized successfully.', 'success');
                        } else {
                            showPlaylistToast(res && res.message ? res.message : 'Unable to synchronize duration & learning hours.', 'danger');
                        }
                    },
                    error: function(xhr) {
                        let message = 'Unable to synchronize duration & learning hours.';
                        if (xhr && xhr.responseJSON) {
                            if (xhr.responseJSON.errors) {
                                message = Object.values(xhr.responseJSON.errors).flat().join('<br>');
                            } else if (xhr.responseJSON.message) {
                                message = xhr.responseJSON.message;
                            }
                        }
                        showPlaylistToast(message, 'danger');
                    },
                    complete: function() {
                        $btn.find('.icon-sync').attr('class', 'ti ti-refresh me-1 icon-sync');
                        $btn.find('.btn-text').text('Synchronize');
                        if (!isPlaylistLocked) {
                            $btn.prop('disabled', false);
                        }
                        $btn.data('loading', false);
                        $('body').css('cursor', 'default');
                    }
                });
            });

        // ── Unsaved Changes Confirmation Modal ──────────────────────────────
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

        // 1. Back button click handler
        $(document).on('click', '.btn-back', function (e) {
            e.preventDefault();
            e.stopPropagation();
            openUnsavedChangesModal();
        });

        // 2. Modal dismissal handlers
        $(document).on('click', '#unsavedChangesModal .btn-close, #unsavedChangesModal [data-bs-dismiss="modal"], #unsavedChangesModal [data-dismiss="modal"], #btnKeepEditing', function (e) {
            e.preventDefault();
            closeUnsavedChangesModal();
        });

        // 3. Discard & Leave handler
        $(document).on('click', '#btnDiscardAndLeave', function (e) {
            e.preventDefault();
            window.location.href = "{{ route('playlist.index') }}";
        });

        // 4. Save as Draft & Exit handler
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

        // Vanilla fallback listener for Back & Discard
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
@endsection