@extends('layouts.sidebar-master')

@section('content')
    @php
        $url_dropzone_css = asset('Tabler/dist/libs/dropzone/dist/dropzone.css?1684106062');
        $url_dropzone_js = asset('Tabler/dist/libs/dropzone/dist/dropzone-min.js?1684106062');
        $url_litepicker = asset('Tabler/dist/libs/litepicker/dist/litepicker.js?1684106062');
        $url_select = asset('Tabler/dist/libs/tom-select/dist/js/tom-select.complete.min.js?1684106062');
        $url_vendor = asset('Tabler/dist/css/tabler-vendors.min.css?1684106062');
    @endphp

    @include('main.monitoring.components.style')

    {{-- Page Header --}}
    <div class="page-header d-print-none mb-3">
        <div class="container-xl">
            <div style="max-width: 1100px; margin: 0 auto;">
                <div class="row align-items-center">
                    <div class="col">
                        <div class="page-pretitle">Manage / Content</div>
                        <h2 class="owl-page-title mb-1">{{ $title }}</h2>
                        <div class="owl-page-subtitle">Complete the form below to add new content.</div>
                    </div>
                    <div class="col-auto ms-auto">
                        <ol class="breadcrumb breadcrumb-arrows mb-0" aria-label="breadcrumbs">
                            <li class="breadcrumb-item"><a href="#">Manage</a></li>
                            <li class="breadcrumb-item"><a href="{{ route('content.index') }}">Content</a></li>
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
                <form id="contentCreateForm" action="{{ route('content.store') }}" method="post" autocomplete="off">
                    @csrf
                    @method('POST')

                    <div class="row g-3">
                        <div class="col-12">
                            {{-- ① Publish Status --}}
                            {{-- @include('main.content.components.section-publish-status', [
                                'mode' => 'create',
                                'stepNumber' => 1
                            ]) --}}

                            {{-- ② Main Information --}}
                            @include('main.content.components.section-main-information', [
                                'mode' => 'create',
                                'stepNumber' => 1,
                                'classification' => $classification
                            ])

                            {{-- ③ Content Files --}}
                            @include('main.content.components.section-content-files', [
                                'mode' => 'create',
                                'stepNumber' => 2
                            ])

                            {{-- ④ Metadata Content --}}
                            @include('main.content.components.section-metadata', [
                                'mode' => 'create',
                                'stepNumber' => 3
                            ])

                            {{-- ⑤ Live Preview --}}
                            @include('main.content.components.section-preview', [
                                'mode' => 'create',
                                'stepNumber' => 4
                            ])

                            {{-- ⑥ Assignment & Action --}}
                            @include('main.content.components.section-action', [
                                'mode' => 'create',
                                'stepNumber' => 5,
                                'user' => $user ?? []
                            ])
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>
@endsection

@section('style')
    <link rel="stylesheet" href="{{ $url_dropzone_css }}">
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

        .dropzone {
            min-height: 100px !important;
            padding: 14px !important;
            border: 2px dashed var(--tblr-border-color, #dce1e7) !important;
            border-radius: var(--tblr-border-radius, .375rem) !important;
            background: var(--tblr-bg-surface-secondary, rgba(98, 105, 118, .03)) !important;
        }
    </style>
@endsection

@section('script')
    <script src="{{ $url_dropzone_js }}"></script>
    <script src="{{ $url_litepicker }}"></script>
    <script src="{{ $url_select }}"></script>
    <script src="{{ asset('js/pdf.min.js') }}"></script>
    <script src="{{ asset('js/content-page-duration.js') }}"></script>
    <script src="{{ asset('js/content-file-handler.js') }}?v={{ filemtime(public_path('js/content-file-handler.js')) }}"></script>
    <script>
        function typevalue(select) {
            var value = select.value;
            if (value == "YouTube") {
                document.getElementById("linkrow").style.display = "block";
            } else {
                document.getElementById("linkrow").style.display = "none";
            }
        }

        window.contentFileHandler = new ContentFileHandler({
            formId: 'contentCreateForm',
            typeSelectId: 'type',
            documentDropzoneSelector: 'div#document-dropzone',
            thumbnailDropzoneSelector: 'div#thumbnail-dropzone',
            uploadFileUrl: '{{ route('content.uploadfileajax') }}',
            deleteFileUrl: '{{ route('content.deletefileajax') }}',
            uploadThumbUrl: '{{ route('content.uploadthumbnailajax') }}',
            deleteThumbUrl: '{{ route('content.deletethumbnailajax') }}',
            csrfToken: "{{ csrf_token() }}",
            mode: 'create'
        });
        window.contentFileHandler.initialize();

        let myDropzone = window.contentFileHandler.getDocDropzone();
        let myThumbnailDropzone = window.contentFileHandler.getThumbDropzone();

        var pdfjsLib = window.pdfjsLib || window["pdfjs-dist/build/pdf"];
        if (pdfjsLib) {
            pdfjsLib.GlobalWorkerOptions.workerSrc = "{{ asset('js/pdf.worker.min.js') }}";
        }

        var documentDurationForm = new OwlDocumentDurationForm({
            formId: 'contentCreateForm'
        });

        if (myDropzone) {
            myDropzone.on('addedfile', function(file) {
                if (file.rejected || file.status === 'error') return;
                documentDurationForm.readFile(file);
            });
            myDropzone.on('removedfile', function() {
                documentDurationForm.clearUploadedPdf();
            });
        }

        document.addEventListener("DOMContentLoaded", function() {
            window.Litepicker && (new Litepicker({
                element: document.getElementById('datepicker-icon-prepend'),
                format: 'YYYY-MM-DD',
                buttonText: {
                    previousMonth: `<svg xmlns="http://www.w3.org/2000/svg" class="icon" width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M15 6l-6 6l6 6" /></svg>`,
                    nextMonth: `<svg xmlns="http://www.w3.org/2000/svg" class="icon" width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M9 6l6 6l-6 6" /></svg>`,
                },
            }));

            var el;
            window.TomSelect && (new TomSelect(el = document.getElementById('select-user'), {
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

        // ── Live Preview ──────────────────────────────────────────────────
        if (window.OwlPreview) {
            OwlPreview.initThumb(myThumbnailDropzone);
            OwlPreview.initFile(myDropzone);
        }
    </script>
@endsection
