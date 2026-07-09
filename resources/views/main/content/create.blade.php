@extends('layouts.sidebar-master')

@section('content')
    @php
        $url_dropzone_css = asset('Tabler/dist/libs/dropzone/dist/dropzone.css?1684106062');
        $url_dropzone_js = asset('Tabler/dist/libs/dropzone/dist/dropzone-min.js?1684106062');
        $url_litepicker = asset('Tabler/dist/libs/litepicker/dist/litepicker.js?1684106062');
    @endphp

    @include('main.monitoring.components.style')

    {{-- Page Header --}}
    <div class="page-header d-print-none mb-3">
        <div class="container-xl">
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

    <div class="page-body">
        <div class="container-xl">
            <form action="{{ route('content.store') }}" method="post" autocomplete="off">
                @csrf
                @method('POST')

                <div class="row g-3">

                    {{-- Kolom Kiri: Konten Utama --}}
                    <div class="col-lg-8">

                        {{-- Main Information --}}
                        <div class="card owl-main-card mb-3">
                            <div class="card-header">
                                <h3 class="card-title">Main Information</h3>
                            </div>
                            <div class="card-body">
                                <div class="row g-3">
                                    <div class="col-md-8">
                                        <label class="form-label required">Title</label>
                                        <input type="text" class="form-control" placeholder="Enter title"
                                            name="title" id="title" required>
                                    </div>
                                    <div class="col-md-4">
                                        <label class="form-label required">Type</label>
                                        <select name="type" id="type" class="form-select" onchange="typevalue(this)" required>
                                            <option value="">-- Select Type --</option>
                                            <option value="Video">Video</option>
                                            <option value="Document">Document</option>
                                            <option value="YouTube">YouTube</option>
                                            <option value="SOP/Regulation">SOP/Regulation</option>
                                        </select>
                                    </div>

                                    <div class="col-12" style="display: none;" id="linkrow">
                                        <label class="form-label">YouTube Link</label>
                                        <div class="input-group">
                                            <span class="input-group-text">
                                                <svg xmlns="http://www.w3.org/2000/svg" class="icon text-red" width="16" height="16" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round">
                                                    <path stroke="none" d="M0 0h24v24H0z" fill="none"></path>
                                                    <path d="M2 8a4 4 0 0 1 4 -4h12a4 4 0 0 1 4 4v8a4 4 0 0 1 -4 4h-12a4 4 0 0 1 -4 -4v-8z"></path>
                                                    <path d="M10 9l5 3l-5 3z"></path>
                                                </svg>
                                            </span>
                                            <input type="text" class="form-control" placeholder="https://www.youtube.com/watch?v=..."
                                                name="link" id="link">
                                        </div>
                                    </div>

                                    <div class="col-12">
                                        <label class="form-label required">Content Description</label>
                                        <textarea name="description" id="description" rows="6" class="form-control" required></textarea>
                                    </div>
                                </div>
                            </div>
                        </div>

                        {{-- Media / File --}}
                        <div class="card owl-main-card">
                            <div class="card-header">
                                <h3 class="card-title">Media / File</h3>
                            </div>
                            <div class="card-body">
                                <div class="mb-3">
                                    <label class="form-label">Upload File</label>
                                    <small class="text-muted d-block mb-2">Format: PDF, MP4, AVI — maks. 1 file, 1 GB</small>
                                    <div class="dropzone" id="document-dropzone">
                                        <div class="fallback"></div>
                                    </div>
                                </div>
                                <div>
                                    <label class="form-label">Upload Thumbnail</label>
                                    <small class="text-muted d-block mb-2">Format: JPG only</small>
                                    <div class="dropzone" id="thumbnail-dropzone">
                                        <div class="fallback"></div>
                                    </div>
                                </div>
                            </div>
                        </div>

                    </div>

                    {{-- Kolom Kanan: Pengaturan & Aksi --}}
                    <div class="col-lg-4">

                        {{-- Klasifikasi --}}
                        <div class="card owl-main-card mb-3">
                            <div class="card-header">
                                <h3 class="card-title">Klasifikasi</h3>
                            </div>
                            <div class="card-body">
                                <div class="mb-3">
                                    <label class="form-label required">Classification</label>
                                    <select name="classification" id="classification" class="form-select" required>
                                        <option value="">-- Select Classification --</option>
                                        @foreach ($classification as $list)
                                            <option value="{{ $list->id }}">{{ $list->classification_name }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                <div>
                                    <label class="form-label">Duration (mm:ss)</label>
                                    <input type="text" class="form-control" placeholder="00:00"
                                        name="duration" id="duration"
                                        data-mask="00:00" data-mask-visible="true" autocomplete="off">
                                    <small class="form-hint">Format: mm:ss. Required for Video and YouTube content.</small>
                                </div>
                            </div>
                        </div>

                        {{-- Pengaturan Publikasi --}}
                        <div class="card owl-main-card mb-3">
                            <div class="card-header">
                                <h3 class="card-title">Pengaturan Publikasi</h3>
                            </div>
                            <div class="card-body">
                                <div class="mb-3">
                                    <label class="form-label required">Date</label>
                                    <div class="input-icon">
                                        <span class="input-icon-addon">
                                            <svg xmlns="http://www.w3.org/2000/svg" class="icon" width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round">
                                                <path stroke="none" d="M0 0h24v24H0z" fill="none" />
                                                <path d="M4 7a2 2 0 0 1 2 -2h12a2 2 0 0 1 2 2v12a2 2 0 0 1 -2 2h-12a2 2 0 0 1 -2 -2v-12z" />
                                                <path d="M16 3v4" />
                                                <path d="M8 3v4" />
                                                <path d="M4 11h16" />
                                                <path d="M11 15h1" />
                                                <path d="M12 15v3" />
                                            </svg>
                                        </span>
                                        <input class="form-control" placeholder="Select a date"
                                            id="datepicker-icon-prepend" name="date"
                                            value="{{ date('Y-m-d') }}" required>
                                    </div>
                                </div>
                                <div class="mb-3">
                                    <label class="form-label required">Author</label>
                                    <input type="text" class="form-control" placeholder="Enter author"
                                        name="author" id="author" required>
                                </div>
                                <div>
                                    <label class="form-check cursor-pointer">
                                        <input type="hidden" name="playlistonly" value="N">
                                        <input class="form-check-input" type="checkbox" name="playlistonly" value="Y">
                                        <span class="form-check-label">Only show on Playlist</span>
                                    </label>
                                    <small class="form-hint d-block mt-1">This content is only available within playlists and will not appear in the general content list.</small>
                                </div>
                            </div>
                        </div>

                        {{-- Deadline Content --}}
                        <div class="card owl-main-card mb-3">
                            <div class="card-header">
                                <h3 class="card-title">Deadline Content</h3>
                            </div>
                            <div class="card-body">
                                <div class="mb-3">
                                    <label class="form-check cursor-pointer">
                                        <input type="hidden" name="has_deadline" value="0">
                                        <input class="form-check-input" type="checkbox" id="has_deadline_content"
                                            name="has_deadline" value="1"
                                            @if(old('has_deadline')) checked @endif>
                                        <span class="form-check-label fw-medium">Set Deadline?</span>
                                    </label>
                                    <small class="form-hint d-block mt-1">Optional. If enabled, specify the date range.</small>
                                </div>
                                <div id="content-deadline-fields" style="{{ old('has_deadline') ? '' : 'display:none;' }}">
                                    <div class="mb-2">
                                        <label class="form-label">From Date</label>
                                        <input type="date" class="form-control" name="from_date" id="content_from_date"
                                            value="{{ old('from_date') }}">
                                    </div>
                                    <div>
                                        <label class="form-label">To Date</label>
                                        <input type="date" class="form-control" name="to_date" id="content_to_date"
                                            value="{{ old('to_date') }}">
                                    </div>
                                </div>
                            </div>
                        </div>

                        {{-- Live Preview --}}
                        @include('main.content.components.upload-preview')

                        {{-- Action Buttons --}}
                        <div class="card owl-main-card">
                            <div class="card-body">
                                <div class="d-grid gap-2">
                                    <button type="submit" class="btn btn-success" name="action" value="save" formnovalidate>
                                        <svg xmlns="http://www.w3.org/2000/svg" class="icon" width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round">
                                            <path stroke="none" d="M0 0h24v24H0z" fill="none"></path>
                                            <path d="M6 4h10l4 4v10a2 2 0 0 1 -2 2h-12a2 2 0 0 1 -2 -2v-12a2 2 0 0 1 2 -2z"></path>
                                            <path d="M12 14m-2 0a2 2 0 1 0 4 0a2 2 0 1 0 -4 0"></path>
                                            <path d="M14 4l0 4l-6 0l0 -4"></path>
                                        </svg>
                                        Save Content (Draft)
                                    </button>
                                    <button type="submit" class="btn btn-primary" name="action" value="publish">
                                        <svg xmlns="http://www.w3.org/2000/svg" class="icon" width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round">
                                            <path stroke="none" d="M0 0h24v24H0z" fill="none"></path>
                                            <path d="M7 11v8a1 1 0 0 1 -1 1h-2a1 1 0 0 1 -1 -1v-7a1 1 0 0 1 1 -1h3a4 4 0 0 0 4 -4v-1a2 2 0 0 1 4 0v5h3a2 2 0 0 1 2 2l-1 5a2 3 0 0 1 -2 2h-7a3 3 0 0 1 -3 -3"></path>
                                        </svg>
                                        Save & Publish
                                    </button>
                                    <a href="{{ route('content.index') }}" class="btn btn-light">Cancel</a>
                                </div>
                            </div>
                        </div>

                    </div>
                </div>
            </form>
        </div>
    </div>

@endsection

@section('style')
    <link rel="stylesheet" href="{{ $url_dropzone_css }}">
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
    </style>
@endsection

@section('script')
    <script src="{{ $url_dropzone_js }}"></script>
    <script src="{{ $url_litepicker }}"></script>
    <script>
        function typevalue(select) {
            var value = select.value;
            if (value == "YouTube") {
                document.getElementById("linkrow").style.display = "block";
            } else {
                document.getElementById("linkrow").style.display = "none";
            }
        }

        let uploadedDocumentMap = {};
        Dropzone.autoDiscover = false;
        let myDropzone = new Dropzone("div#document-dropzone", {
            url: '{{ route('content.uploadfileajax') }}',
            autoProcessQueue: true,
            uploadMultiple: true,
            addRemoveLinks: true,
            acceptedFiles: ".pdf,.mp4,.avi",
            parallelUploads: 2,
            headers: { 'X-CSRF-TOKEN': "{{ csrf_token() }}" },
            maxFiles: 1,
            maxFilesize: 1000,
            init: function() {
                this.on("error", function(file, message) {
                    alert(message);
                    this.removeFile(file);
                });
            },
            successmultiple: function(data, response) {
                $.each(response['name'], function(key, val) {
                    $('form').append('<input type="hidden" name="documents[]" value="' + val + '" required>');
                    uploadedDocumentMap[data[key].name] = val;
                });
                console.log(response.name);
            },
            removedfile: function(file) {
                file.previewElement.remove();
                let name = typeof file.file_name !== 'undefined' ? file.file_name : uploadedDocumentMap[file.name];
                $.ajax({
                    url: "{{ route('content.deletefileajax') }}",
                    data: { name: name },
                    success: function(data) { console.log('sukses : ' + data); }
                });
                $('form').find('input[name="documents[]"][value="' + name + '"]').remove();
            }
        });

        let uploadedThumbnailMap = {};
        let myThumbnailDropzone = new Dropzone("div#thumbnail-dropzone", {
            url: '{{ route('content.uploadthumbnailajax') }}',
            autoProcessQueue: true,
            uploadMultiple: true,
            addRemoveLinks: true,
            acceptedFiles: ".jpg",
            parallelUploads: 2,
            headers: { 'X-CSRF-TOKEN': "{{ csrf_token() }}" },
            maxFiles: 1,
            maxFilesize: 1000,
            init: function() {
                this.on("error", function(file, message) {
                    alert(message);
                    this.removeFile(file);
                });
            },
            successmultiple: function(data, response) {
                $.each(response['name'], function(key, val) {
                    $('form').append('<input type="hidden" name="thumbnail[]" value="' + val + '" required>');
                    uploadedThumbnailMap[data[key].name] = val;
                });
                console.log(response.name);
            },
            removedfile: function(file) {
                file.previewElement.remove();
                let name = typeof file.file_name !== 'undefined' ? file.file_name : uploadedThumbnailMap[file.name];
                $.ajax({
                    url: "{{ route('content.deletethumbnailajax') }}",
                    data: { name: name },
                    success: function(data) { console.log('sukses : ' + data); }
                });
                $('form').find('input[name="thumbnail[]"][value="' + name + '"]').remove();
            }
        });

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

        // ── Live Preview ──────────────────────────────────────────────────
        if (window.OwlPreview) {
            OwlPreview.initThumb(myThumbnailDropzone);
            OwlPreview.initFile(myDropzone);
        }
    </script>
@endsection
