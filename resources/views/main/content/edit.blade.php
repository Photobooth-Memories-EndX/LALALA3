@extends('layouts.sidebar-master')

@section('content')
    @php
        $url_dropzone_css = asset('Tabler/dist/libs/dropzone/dist/dropzone.css?1684106062');
        $url_dropzone_js = asset('Tabler/dist/libs/dropzone/dist/dropzone-min.js?1684106062');
        $url_ytplayer_js = asset('Tabler/dist/libs/plyr/dist/plyr.min.js?1684106062');
        $url_ytplayer_css = asset('Tabler/dist/libs/plyr/dist/plyr.css?1684106062');
        $url_litepicker = asset('Tabler/dist/libs/litepicker/dist/litepicker.js?1684106062');
        $url_select = asset('Tabler/dist/libs/tom-select/dist/js/tom-select.complete.min.js?1684106062');
        $url_vendor = asset('Tabler/dist/css/tabler-vendors.min.css?1684106062');
        $isLocked = $content && $content->isPublished();
    @endphp

    @include('main.monitoring.components.style')

    {{-- Page Header --}}
    <div class="page-header d-print-none mb-3">
        <div class="container-xl">
            <div style="max-width: 1100px; margin: 0 auto;">
                <div class="mb-3">
                    <a href="{{ route('content.index') }}" class="btn-back mb-3 d-inline-flex align-items-center gap-1 text-decoration-none">
                        <i class="ti ti-arrow-left"></i>
                        <span>Back</span>
                    </a>
                </div>
                <div class="row align-items-center">
                    <div class="col">
                        <div class="page-pretitle">Manage / Content</div>
                        <h2 class="owl-page-title mb-1">Edit Content</h2>
                        <div class="owl-page-subtitle">
                            {{ $content->title }}
                            <span class="badge bg-{{ $content->active == 'Active' ? 'success' : 'warning' }}-lt ms-2">
                                {{ $content->active }}
                            </span>
                        </div>
                    </div>
                    <div class="col-auto ms-auto">
                        <ol class="breadcrumb breadcrumb-arrows mb-0" aria-label="breadcrumbs">
                            <li class="breadcrumb-item"><a href="#">Manage</a></li>
                            <li class="breadcrumb-item"><a href="{{ route('content.index') }}">Content</a></li>
                            <li class="breadcrumb-item active" aria-current="page">Edit Content</li>
                        </ol>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="page-body">
        <div class="container-xl">
            <div style="max-width: 1100px; margin: 0 auto;">

                <x-alert type='success' />
                <x-alert type='warning' />

                <form id="contentEditForm" action="{{ route('content.update', $content->slug) }}" method="post" autocomplete="off" enctype="multipart/form-data">
                    @csrf
                    @method('PUT')
                    <input type="hidden" value="{{ $content->id }}" name="contentid">

                    <div class="row g-3">
                        <div class="col-12">

                            {{-- ① Publish Status --}}
                            @include('main.content.components.section-publish-status', [
                                'mode' => 'edit',
                                'stepNumber' => 1,
                                'content' => $content,
                                'isLocked' => $isLocked,
                            ])

                            {{-- ② Main Information --}}
                            @include('main.content.components.section-main-information', [
                                'mode' => 'edit',
                                'stepNumber' => 2,
                                'content' => $content,
                                'classification' => $classification,
                                'isLocked' => $isLocked,
                            ])

                            {{-- ③ Content Files --}}
                            @include('main.content.components.section-content-files', [
                                'mode' => 'edit',
                                'stepNumber' => 3,
                                'content' => $content,
                                'isLocked' => $isLocked,
                            ])

                            {{-- ④ Metadata Content --}}
                            @include('main.content.components.section-metadata', [
                                'mode' => 'edit',
                                'stepNumber' => 4,
                                'content' => $content,
                                'isLocked' => $isLocked,
                            ])

                            {{-- ⑤ Live Preview --}}
                            @include('main.content.components.section-preview', [
                                'mode' => 'edit',
                                'stepNumber' => 5,
                                'content' => $content,
                                'isLocked' => $isLocked,
                            ])

                        </div>
                    </div>
                </form>

                {{-- ⑥ Assignment & Action --}}
                @include('main.content.components.section-action', [
                    'mode' => 'edit',
                    'stepNumber' => 6,
                    'content' => $content,
                    'user' => $user,
                    'isLocked' => $isLocked,
                ])

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

@section('style')
    <link rel="stylesheet" href="{{ $url_dropzone_css }}">
    <link rel="stylesheet" href="{{ $url_ytplayer_css }}">
    <link rel="stylesheet" href="{{ $url_vendor }}" />
    <style>
        #overlay {
            position: absolute;
            top: 0; right: 0; left: 0; bottom: 0;
            display: flex;
            align-items: center;
            justify-content: center;
            overflow: hidden;
            border-radius: 0.25rem;
            z-index: 1000;
        }

        .btn-back {
            color: #0c5b9e !important;
            transition: transform 0.2s ease, opacity 0.2s ease;
        }
        .btn-back:hover {
            opacity: 0.85;
            transform: translateX(-3px);
        }

        .document-duration-estimate {
            padding: .75rem;
            background: var(--tblr-bg-surface-secondary, rgba(98, 105, 118, .06));
            border: 1px solid var(--tblr-border-color, #dce1e7);
            border-radius: var(--tblr-border-radius, .375rem);
        }

        [data-bs-theme="dark"] .document-duration-estimate,
        body.dark-mode .document-duration-estimate {
            background: rgba(255, 255, 255, .04);
            border-color: rgba(255, 255, 255, .12);
        }

        .dropzone {
            min-height: 100px !important;
            padding: 14px !important;
            border: 2px dashed var(--tblr-border-color, #dce1e7) !important;
            border-radius: var(--tblr-border-radius, .375rem) !important;
            background: var(--tblr-bg-surface-secondary, rgba(98, 105, 118, .03)) !important;
        }

        .compact-dropzone.is-locked {
            pointer-events: none !important;
            cursor: not-allowed !important;
            opacity: 0.7 !important;
            background: var(--tblr-bg-surface-secondary, rgba(98, 105, 118, .06)) !important;
            border-style: solid !important;
        }

        .form-control:disabled,
        .form-select:disabled,
        .form-check-input:disabled {
            background-color: var(--tblr-bg-surface-secondary, #f8fafc) !important;
            color: var(--tblr-muted, #64748b) !important;
            cursor: not-allowed !important;
            opacity: 0.9 !important;
        }
    </style>
@endsection

@section('script')
    <script src="{{ $url_dropzone_js }}"></script>
    <script src="{{ $url_litepicker }}"></script>
    <script src="{{ $url_ytplayer_js }}"></script>
    <script src="{{ $url_select }}"></script>
    <script src="{{ asset('js/pdf.min.js') }}"></script>
    <script src="{{ asset('js/edit-content-document-duration.js') }}?v={{ filemtime(public_path('js/edit-content-document-duration.js')) }}"></script>
    <script src="{{ asset('js/content-file-handler.js') }}?v={{ filemtime(public_path('js/content-file-handler.js')) }}"></script>
    <script>
        document.addEventListener("DOMContentLoaded", function() {
            window.Plyr && (new Plyr('#player-youtube'));
        });

        @if (!$isLocked)
        $(document).on('click', '.btn-remove-content', function(e) {
            e.preventDefault();
            e.stopPropagation();
            e.stopImmediatePropagation();
            if (confirm("Are you sure want to delete this media content?")) {
                $.ajax({
                    type: "PUT",
                    url: $(this).data('url'),
                    headers: {
                        'X-CSRF-TOKEN': '{{ csrf_token() }}',
                        'X-Requested-With': 'XMLHttpRequest'
                    },
                    data: { '_token': '{{ csrf_token() }}' },
                    success: function(data) {
                        console.log(data);
                        if (data.status == 200) {
                            window.location.reload();
                        }
                    }
                });
            }
        });

        $(document).on('click', '.btn-remove-thumb', function(e) {
            e.preventDefault();
            e.stopPropagation();
            e.stopImmediatePropagation();
            var $btn = $(this);
            var url = $btn.data('url');
            if (confirm("Are you sure want to delete this thumbnail?")) {
                var performDomRemove = function() {
                    $('#current-thumbnail-wrapper').remove();
                    $('#label-thumbnail-file').text('Thumbnail');
                    $('#remove_thumbnail').val('1');
                    if (window.OwlPreview && typeof window.OwlPreview.setServerThumb === 'function') {
                        window.OwlPreview.setServerThumb(null);
                    }
                };

                if (url) {
                    $.ajax({
                        type: "PUT",
                        url: url,
                        headers: {
                            'X-CSRF-TOKEN': '{{ csrf_token() }}',
                            'X-Requested-With': 'XMLHttpRequest'
                        },
                        data: { '_token': '{{ csrf_token() }}' },
                        success: function(data) {
                            performDomRemove();
                        },
                        error: function(xhr, status, error) {
                            console.warn('Remove thumbnail AJAX error status:', xhr.status, error);
                            performDomRemove();
                        }
                    });
                } else {
                    performDomRemove();
                }
            }
        });
        @endif

        var gettype = document.getElementById("type").value;
        if (gettype == "YouTube") {
            document.getElementById("linkrow").style.display = "block";
        }

        function typevalue(select) {
            var value = select.value;
            if (value == "YouTube") {
                document.getElementById("linkrow").style.display = "block";
            } else {
                document.getElementById("linkrow").style.display = "none";
            }
        }

        @if (!$isLocked)
        window.contentFileHandler = new ContentFileHandler({
            formId: 'contentEditForm',
            typeSelectId: 'type',
            documentDropzoneSelector: 'div#document-dropzone',
            thumbnailDropzoneSelector: 'div#thumbnail-dropzone',
            uploadFileUrl: '{{ route('content.uploadfileajax') }}',
            deleteFileUrl: '{{ route('content.deletefileajax') }}',
            uploadThumbUrl: '{{ route('content.uploadthumbnailajax') }}',
            deleteThumbUrl: '{{ route('content.deletethumbnailajax') }}',
            csrfToken: "{{ csrf_token() }}",
            mode: 'edit'
        });
        window.contentFileHandler.initialize();

        let myDropzone = window.contentFileHandler.getDocDropzone();
        let myThumbnailDropzone = window.contentFileHandler.getThumbDropzone();
        @else
        let myDropzone = null;
        let myThumbnailDropzone = null;
        @endif

        var pdfjsLib = window.pdfjsLib || window["pdfjs-dist/build/pdf"];
        if (pdfjsLib) {
            pdfjsLib.GlobalWorkerOptions.workerSrc = "{{ asset('js/pdf.worker.min.js') }}";
        }

        @php
            $_cf      = json_decode($content->file ?? '[]') ?: [];
            $_fn      = $_cf[0] ?? null;
        @endphp

        var documentDurationForm = new EditContentDocumentDuration({
            existingPdfUrl: @json(
                $content->type === 'Document' && !empty($_fn)
                    ? asset('storage/files/content/' . $content->id . '/' . $_fn)
                    : null
            )
        });

        if (myDropzone) {
            myDropzone.on('addedfile', function(file) {
                if (file.rejected || file.status === 'error') return;
                documentDurationForm.readFile(file);
            });
            myDropzone.on('removedfile', function() {
                documentDurationForm.restoreExistingPdf();
            });
        }

        @if (!$isLocked)
        document.addEventListener("DOMContentLoaded", function() {
            window.Litepicker && (new Litepicker({
                element: document.getElementById('datepicker-icon-prepend'),
                format: 'YYYY-MM-DD',
                buttonText: {
                    previousMonth: `<svg xmlns="http://www.w3.org/2000/svg" class="icon" width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M15 6l-6 6l6 6" /></svg>`,
                    nextMonth: `<svg xmlns="http://www.w3.org/2000/svg" class="icon" width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M9 6l6 6l-6 6" /></svg>`,
                },
            }));
        });

        (function () {
            var cb = document.getElementById('has_deadline_content');
            var fields = document.getElementById('content-deadline-fields');
            if (!cb || !fields) return;
            cb.addEventListener('change', function () {
                if (this.checked) {
                    fields.style.display = '';
                } else {
                    fields.style.display = 'none';
                    var fd = document.getElementById('content_from_date');
                    var td = document.getElementById('content_to_date');
                    if (fd) fd.value = '';
                    if (td) td.value = '';
                }
            });
        })();
        @endif

        // ── Live Preview ──────────────────────────────────────────────────
        if (window.OwlPreview) {
            OwlPreview.initThumb(myThumbnailDropzone);
            OwlPreview.initFile(myDropzone);
            @php
                $_tpath   = public_path('storage/files/content/' . $content->id . '/thumb.jpg');
                $_thumbUrl = file_exists($_tpath)
                    ? asset('storage/files/content/' . $content->id . '/thumb.jpg')
                    : ($content->type === 'YouTube' && $content->link
                        ? 'https://img.youtube.com/vi/' . $content->link . '/mqdefault.jpg'
                        : null);
            @endphp
            @if(!empty($_thumbUrl))
            OwlPreview.setServerThumb(@json($_thumbUrl));
            @endif
            @if($_fn && $content->type === 'Video')
            OwlPreview.setServerVideoFile(
                @json(asset('storage/files/content/' . $content->id . '/' . $_fn)),
                @json($_fn)
            );
            @elseif($_fn && in_array($content->type, ['Document', 'SOP/Regulation']))
            OwlPreview.setServerPdfFile(@json($_fn));
            @endif
        }

        document.addEventListener("DOMContentLoaded", function() {
            var el = document.getElementById('select-user');
            if (el && window.TomSelect) {
                var ts = new TomSelect(el, {
                    copyClassesToDropdown: false,
                    dropdownParent: 'body',
                    controlInput: '<input>',
                    plugins: ['remove_button'],
                    render: {
                        item: function(data, escape) {
                            if (data.customProperties) {
                                return '<div><span class="dropdown-item-indicator">' + data.customProperties + '</span>' + escape(data.text) + '</div>';
                            }
                            return '<div>' + escape(data.text) + '</div>';
                        },
                        option: function(data, escape) {
                            if (data.customProperties) {
                                return '<div><span class="dropdown-item-indicator">' + data.customProperties + '</span>' + escape(data.text) + '</div>';
                            }
                            return '<div>' + escape(data.text) + '</div>';
                        },
                    },
                });
                @if ($isLocked)
                ts.disable();
                @endif
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
            window.location.href = "{{ route('content.index') }}";
        });

        // 4. "Save as Draft & Exit" handler
        $(document).on('click', '#btnSaveDraftAndExit', function (e) {
            e.preventDefault();
            var saveBtn = this;
            var btnText = saveBtn.querySelector('.btn-save-text');
            saveBtn.disabled = true;
            if (btnText) btnText.textContent = 'Saving Draft...';

            var contentForm = document.getElementById('contentEditForm') || document.querySelector('form');
            if (contentForm) {
                var formData = new FormData(contentForm);
                formData.set('action', 'draft');

                fetch(contentForm.action, {
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
                    window.location.href = "{{ route('content.index') }}";
                })
                .catch(function (err) {
                    console.error('AJAX draft save failed, submitting fallback:', err);
                    var actionInput = contentForm.querySelector('input[name="action"]');
                    if (!actionInput) {
                        actionInput = document.createElement('input');
                        actionInput.type = 'hidden';
                        actionInput.name = 'action';
                        contentForm.appendChild(actionInput);
                    }
                    actionInput.value = 'draft';
                    contentForm.submit();
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
                window.location.href = "{{ route('content.index') }}";
                return;
            }
        });
    </script>
@endsection
