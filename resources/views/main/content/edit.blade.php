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
    @endphp

    @include('main.monitoring.components.style')

    {{-- Page Header --}}
    <div class="page-header d-print-none mb-3">
        <div class="container-xl">
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

    <div class="page-body">
        <div class="container-xl">

            <x-alert type='success' />
            <x-alert type='warning' />

            {{-- ============================================================
                 FORM 1: Assign Content to Employee
            ============================================================ --}}
            <div class="card owl-main-card mb-4">
                <div class="card-header">
                    <h3 class="card-title">Assign Content ke Karyawan</h3>
                    <div class="card-subtitle text-muted mt-1">Pilih karyawan yang akan mendapatkan konten ini sebagai mandatory.</div>
                </div>
                <form action="{{ route('content.assign', $content->slug) }}" method="post" autocomplete="off">
                    @csrf
                    @method('POST')
                    <div class="card-body">
                        <div class="row g-3 align-items-end">
                            <div class="col-md-10">
                                <label class="form-label">Employee</label>
                                <select type="text" class="form-select" id="select-user" name="employee[]" value="" multiple>
                                    @unless (count($user) == 0)
                                        @foreach ($user as $list)
                                            <option value="{{ $list->id }}">{{ $list->id_emp }} - {{ $list->name }}</option>
                                        @endforeach
                                    @else
                                        <option value="">No employee available</option>
                                    @endunless
                                </select>
                            </div>
                            <div class="col-md-2">
                                <input type="hidden" name="content_id" value="{{ $content->id }}">
                                <input type="hidden" name="slug" value="{{ $content->slug }}">
                                <button type="submit" class="btn btn-primary w-100" name="action" value="assign">
                                    Assign
                                </button>
                            </div>
                        </div>
                    </div>
                </form>
            </div>

            {{-- ============================================================
                 FORM 2: Edit Content
            ============================================================ --}}
            <form id="contentEditForm" action="{{ route('content.update', $content->slug) }}" method="post" autocomplete="off" enctype="multipart/form-data">
                @csrf
                @method('PUT')
                <input type="hidden" value="{{ $content->id }}" name="contentid">

                <div class="row g-3">

                    {{-- Kolom Kiri --}}
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
                                            name="title" id="title" value="{{ $content->title ?? '' }}" required>
                                    </div>
                                    <div class="col-md-4">
                                        <label class="form-label">Type</label>
                                        <select name="type" id="type" class="form-select" onchange="typevalue(this)">
                                            <option value="">-- Select Type --</option>
                                            <option value="Video" @selected(old('type', $content->type) === 'Video')>Video</option>
                                            <option value="Document" @selected(old('type', $content->type) === 'Document')>Document</option>
                                            <option value="YouTube" @selected(old('type', $content->type) === 'YouTube')>YouTube</option>
                                            <option value="SOP/Regulation" @selected(old('type', $content->type) === 'SOP/Regulation')>SOP/Regulation</option>
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
                                                name="link" id="link"
                                                value="https://www.youtube.com/watch?v={{ $content->link }}">
                                        </div>
                                    </div>

                                    <div class="col-12">
                                        <label class="form-label">Content Description</label>
                                        <textarea name="description" id="description" rows="6" class="form-control">{{ $content->description ?? '' }}</textarea>
                                    </div>
                                </div>
                            </div>
                        </div>

                        {{-- File yang Ada --}}
                        <div class="card owl-main-card mb-3">
                            <div class="card-header">
                                <h3 class="card-title">File Konten Saat Ini</h3>
                            </div>
                            <div class="card-body">
                                @php
                                    $contentfile = json_decode($content->file);
                                    if (isset($contentfile[0])) {
                                        $getcontentfile = $contentfile[0];
                                        $displaycontent = 'display:block;';
                                    } elseif ($content->type == 'YouTube') {
                                        $getcontentfile = '';
                                        $displaycontent = 'display:none;';
                                    } else {
                                        $getcontentfile = '';
                                        $displaycontent = 'display:none;';
                                    }
                                @endphp

                                <div class="text-center" id="contentContainer">
                                    @if ($content->type == 'Video')
                                        <video width="100%" controls style="{{ $displaycontent }} max-width:854px;">
                                            <source src="{{ asset('storage/files/content/' . $content->id . '/' . $getcontentfile) }}" type="video/mp4">
                                            Your browser does not support the video tag.
                                            <input type="hidden" name="replacecontent" id="replacecontent" value="{{ $getcontentfile }}">
                                        </video>
                                        @if (!isset($contentfile[0]))
                                            <p class="text-muted mt-2">Tidak ada file video.</p>
                                        @endif
                                    @elseif ($content->type == 'Document' || $content->type == 'SOP/Regulation')
                                        <object data="{{ asset('storage/files/content/' . $content->id . '/' . $getcontentfile) }}"
                                            width="100%" height="600px" style="max-width:800px;"></object>
                                        @if (!isset($contentfile[0]))
                                            <p class="text-muted mt-2">Tidak ada file dokumen.</p>
                                        @endif
                                    @elseif ($content->type == 'YouTube')
                                        <div id="player-youtube" data-plyr-provider="youtube" data-plyr-embed-id="{{ $content->link }}"
                                            style="max-width:854px; margin: 0 auto;"></div>
                                    @else
                                        <p class="text-muted">No file exist</p>
                                    @endif

                                    @if ($content->type != 'YouTube')
                                        <div class="mt-3">
                                            <a href="#"
                                                data-url="{{ route('contentdelete.deletecontentajax', $content->slug) }}"
                                                class="btn btn-danger btn-sm btn-remove-content"
                                                @if ($content->active == 'Active') style="pointer-events: none; opacity:.6;" @endif>
                                                <svg xmlns="http://www.w3.org/2000/svg" class="icon" width="16" height="16" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round">
                                                    <path stroke="none" d="M0 0h24v24H0z" fill="none"></path>
                                                    <path d="M4 7l16 0"></path>
                                                    <path d="M10 11l0 6"></path>
                                                    <path d="M14 11l0 6"></path>
                                                    <path d="M5 7l1 12a2 2 0 0 0 2 2h8a2 2 0 0 0 2 -2l1 -12"></path>
                                                    <path d="M9 7v-3a1 1 0 0 1 1 -1h4a1 1 0 0 1 1 1v3"></path>
                                                </svg>
                                                Delete Content
                                            </a>
                                            @if ($content->active == 'Active')
                                                <small class="text-muted d-block mt-1">Unpublish konten terlebih dahulu untuk menghapus file.</small>
                                            @endif
                                        </div>
                                    @endif
                                </div>
                            </div>
                        </div>

                        {{-- Ganti / Tambah File --}}
                        <div class="card owl-main-card">
                            <div class="card-header">
                                <h3 class="card-title">Ganti / Tambah File</h3>
                            </div>
                            <div class="card-body">
                                <div class="mb-3">
                                    <label class="form-label">Replace File</label>
                                    <small class="text-muted d-block mb-2">Format: PDF, MP4 — maks. 1 file, 1 GB</small>
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

                    {{-- Kolom Kanan --}}
                    <div class="col-lg-4">

                        {{-- Klasifikasi --}}
                        <div class="card owl-main-card mb-3">
                            <div class="card-header">
                                <h3 class="card-title">Klasifikasi</h3>
                            </div>
                            <div class="card-body">
                                <div class="mb-3">
                                    <label class="form-label">Classification</label>
                                    <select name="classification" id="classification" class="form-select">
                                        <option value="">-- Select Classification --</option>
                                        @foreach ($classification as $list)
                                            <option value="{{ $list->id }}"
                                                @if ($list->id == $content->classification_id) selected @endif>
                                                {{ $list->classification_name }}
                                            </option>
                                        @endforeach
                                    </select>
                                </div>
                                <div>
                                    <label class="form-label">Duration (HH:MM:SS)</label>
                                    <input type="text" class="form-control @error('duration') is-invalid @enderror" placeholder="00:06:26"
                                        name="duration" id="duration"
                                        value="{{ old('duration', \App\Support\DurationFormatter::format($content->duration, '')) }}"
                                        pattern="^(\d+:[0-5]\d|\d{2}:[0-5]\d:[0-5]\d)$"
                                        autocomplete="off">
                                    @error('duration')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                    <small class="form-hint">Format HH:MM:SS. Input mm:ss lama tetap diterima dan disimpan sebagai HH:MM:SS.</small>
                                </div>
                                @include('main.content.components.page-duration-field', ['content' => $content])
                            </div>
                        </div>

                        {{-- Pengaturan --}}
                        <div class="card owl-main-card mb-3">
                            <div class="card-header">
                                <h3 class="card-title">Pengaturan</h3>
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
                                            value="{{ $content->date ?? '' }}" />
                                    </div>
                                </div>
                                <div class="mb-3">
                                    <label class="form-label required">Author</label>
                                    <input type="text" class="form-control" placeholder="Enter author"
                                        name="author" id="author"
                                        value="{{ $content->author ?? '' }}" required>
                                </div>
                                <div>
                                    <label class="form-check cursor-pointer">
                                        <input type="hidden" name="playlistonly" value="N">
                                        <input class="form-check-input" type="checkbox" name="playlistonly" value="Y"
                                            @if ($content->playlistonly == 'Y') checked @endif>
                                        <span class="form-check-label">Only show on Playlist</span>
                                    </label>
                                </div>
                            </div>
                        </div>

                        {{-- Deadline Content --}}
                        @php
                            $showContentDeadline = old('has_deadline') !== null
                                ? (bool) old('has_deadline')
                                : (bool) $content->has_deadline;
                        @endphp
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
                                            @if($showContentDeadline) checked @endif>
                                        <span class="form-check-label fw-medium">Set Deadline?</span>
                                    </label>
                                    <small class="form-hint d-block mt-1">Optional. If enabled, specify the date range.</small>
                                </div>
                                <div id="content-deadline-fields" style="{{ $showContentDeadline ? '' : 'display:none;' }}">
                                    <div class="mb-2">
                                        <label class="form-label">From Date</label>
                                        <input type="date" class="form-control" name="from_date" id="content_from_date"
                                            value="{{ old('from_date', $content->from_date ? $content->from_date->format('Y-m-d') : '') }}">
                                    </div>
                                    <div>
                                        <label class="form-label">To Date</label>
                                        <input type="date" class="form-control" name="to_date" id="content_to_date"
                                            value="{{ old('to_date', $content->to_date ? $content->to_date->format('Y-m-d') : '') }}">
                                    </div>
                                </div>
                            </div>
                        </div>

                        {{-- Live Preview --}}
                        @include('main.content.components.upload-preview')

                        {{-- Action Buttons --}}
                        <div class="card owl-main-card">
                            <div class="card-header">
                                <h3 class="card-title">Action</h3>
                            </div>
                            <div class="card-body">
                                <div class="d-grid gap-2">
                                    {{-- Simpan Perubahan: selalu tampil, preserve status aktif --}}
                                    <button type="submit" form="contentEditForm" class="btn btn-primary" name="action" value="update">
                                        <i class="ti ti-device-floppy me-1"></i>
                                        Simpan Perubahan Content
                                    </button>

                                    @if ($content->active == 'Active')
                                        <button type="submit" class="btn btn-warning" name="action" value="unpublish">
                                            <svg xmlns="http://www.w3.org/2000/svg" class="icon" width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round">
                                                <path stroke="none" d="M0 0h24v24H0z" fill="none"></path>
                                                <path d="M3 12a9 9 0 1 0 18 0a9 9 0 0 0 -18 0"></path>
                                                <path d="M9 12h6"></path>
                                            </svg>
                                            Unpublish This Content
                                        </button>
                                    @else
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
                                    @endif
                                    <a href="{{ route('content.index') }}" class="btn btn-light">Back to List</a>
                                </div>
                            </div>
                        </div>

                    </div>
                </div>
            </form>

        </div>
    </div>

    {{-- Sticky footer: tombol simpan selalu terlihat saat scroll --}}
    <div class="content-sticky-actions">
        <a href="{{ route('content.index') }}" class="btn btn-light btn-sm">
            <i class="ti ti-arrow-left me-1"></i>Back to List
        </a>
        <button type="submit" form="contentEditForm" class="btn btn-primary btn-sm" name="action" value="update">
            <i class="ti ti-device-floppy me-1"></i>
            Simpan Perubahan
        </button>
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

        .content-sticky-actions {
            position: sticky;
            bottom: 0;
            z-index: 50;
            background: rgba(255, 255, 255, .92);
            backdrop-filter: blur(10px);
            -webkit-backdrop-filter: blur(10px);
            border-top: 1px solid rgba(0, 0, 0, .08);
            padding: 10px 16px;
            display: flex;
            justify-content: flex-end;
            align-items: center;
            gap: 8px;
        }

        [data-bs-theme="dark"] .content-sticky-actions,
        body.theme-dark .content-sticky-actions,
        body.dark .content-sticky-actions {
            background: rgba(17, 24, 39, .92);
            border-top-color: rgba(255, 255, 255, .12);
        }
    </style>
@endsection

@section('script')
    <script src="{{ $url_dropzone_js }}"></script>
    <script src="{{ $url_litepicker }}"></script>
    <script src="{{ $url_ytplayer_js }}"></script>
    <script src="{{ $url_select }}"></script>
    <script src="{{ asset('js/pdf.min.js') }}"></script>
    <script src="{{ asset('js/content-page-duration.js') }}"></script>
    <script>
        document.addEventListener("DOMContentLoaded", function() {
            window.Plyr && (new Plyr('#player-youtube'));
        });

        $(document).on('click', '.btn-remove-content', function(e) {
            e.preventDefault();
            if (confirm("Are you sure want to delete this media content?")) {
                $.ajax({
                    type: "PUT",
                    url: $(this).data('url'),
                    data: { '_token': '{{ csrf_token() }}' },
                    success: function(data) {
                        console.log(data);
                        if (data.status == 200) {
                            $("#contentContainer").load(" #contentContainer > *");
                        }
                    }
                });
            }
        });

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

        let uploadedDocumentMap = {};
        Dropzone.autoDiscover = false;
        let myDropzone = new Dropzone("div#document-dropzone", {
            url: '{{ route('content.uploadfileajax') }}',
            autoProcessQueue: true,
            uploadMultiple: true,
            addRemoveLinks: true,
            acceptedFiles: ".pdf,.mp4",
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
                    $('#contentEditForm').append('<input type="hidden" name="documents[]" value="' + val + '">');
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
                $('#contentEditForm').find('input[name="documents[]"][value="' + name + '"]').remove();
            }
        });

        var pdfjsLib = window.pdfjsLib || window["pdfjs-dist/build/pdf"];
        if (pdfjsLib) {
            pdfjsLib.GlobalWorkerOptions.workerSrc = "{{ asset('js/pdf.worker.min.js') }}";
        }

        var documentDurationForm = new OwlDocumentDurationForm({
            formId: 'contentEditForm',
            existingPdfUrl: @json(
                $content->type === 'Document' && !empty($getcontentfile)
                    ? asset('storage/files/content/' . $content->id . '/' . $getcontentfile)
                    : null
            )
        });

        myDropzone.on('addedfile', function(file) {
            documentDurationForm.readFile(file);
        });
        myDropzone.on('removedfile', function() {
            documentDurationForm.clearUploadedPdf();
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
                    $('#contentEditForm').append('<input type="hidden" name="thumbnail[]" value="' + val + '" required>');
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
                $('#contentEditForm').find('input[name="thumbnail[]"][value="' + name + '"]').remove();
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
            @php
                $_cf      = json_decode($content->file ?? '[]') ?: [];
                $_fn      = $_cf[0] ?? null;
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
    </script>
@endsection
