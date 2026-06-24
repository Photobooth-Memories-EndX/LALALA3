@extends('layouts.sidebar-master')

@php
    $url_dropzone_css = asset('Tabler/dist/libs/dropzone/dist/dropzone.css?1684106062');
    $url_dropzone_js  = asset('Tabler/dist/libs/dropzone/dist/dropzone-min.js?1684106062');
    $url_litepicker   = asset('Tabler/dist/libs/litepicker/dist/litepicker.js?1684106062');
@endphp

@section('style')
<link rel="stylesheet" href="{{ $url_dropzone_css }}">
<style>
.admin-gallery-create-card {
    border-radius: 16px;
}
.admin-gallery-dropzone {
    min-height: 220px;
    border: 2px dashed var(--tblr-primary);
    border-radius: 16px;
    background: var(--tblr-bg-surface);
    transition: background .2s, transform .2s;
    cursor: pointer;
}
.admin-gallery-dropzone:hover {
    background: var(--tblr-bg-surface-secondary);
    transform: scale(1.005);
}
[data-bs-theme="dark"] .admin-gallery-dropzone {
    background: #111827;
    border-color: var(--tblr-primary);
}
</style>
@endsection

@section('content')

<div class="page-header d-print-none">
    <div class="container-xl">
        <div class="row align-items-center">
            <div class="col">
                <div class="d-flex align-items-center gap-3">
                    <div class="avatar avatar-lg bg-primary-lt">
                        <i class="ti ti-calendar-plus fs-2"></i>
                    </div>
                    <div>
                        <h2 class="page-title mb-1">Create Gallery Event</h2>
                        <div class="text-secondary">Upload and manage event photo galleries</div>
                    </div>
                </div>
            </div>
            <div class="col-auto">
                <a href="{{ route('eventphoto.index') }}" class="btn btn-outline-secondary">
                    <i class="ti ti-arrow-left me-1"></i>Back
                </a>
            </div>
        </div>
    </div>
</div>

<div class="page-body">
    <div class="container-xl">

        <form action="{{ route('eventphoto.store') }}" method="POST" autocomplete="off">
            @csrf

            <div class="card admin-gallery-create-card shadow-sm mb-4">
                <div class="card-header">
                    <h3 class="card-title">Gallery Information</h3>
                    <div class="card-subtitle">Enter event details before uploading photos</div>
                </div>
                <div class="card-body">
                    <div class="row g-4">
                        <div class="col-lg-8">
                            <label class="form-label required">Event Title</label>
                            <input type="text"
                                   name="title"
                                   id="title"
                                   class="form-control @error('title') is-invalid @enderror"
                                   placeholder="Enter event title"
                                   value="{{ old('title') }}"
                                   required>
                            @error('title')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                        <div class="col-lg-4">
                            <label class="form-label required">Event Date</label>
                            <div class="input-icon">
                                <span class="input-icon-addon">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="icon" width="24" height="24"
                                         viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none"
                                         stroke-linecap="round" stroke-linejoin="round">
                                        <path stroke="none" d="M0 0h24v24H0z" fill="none"/>
                                        <path d="M4 7a2 2 0 0 1 2-2h12a2 2 0 0 1 2 2v12a2 2 0 0 1-2 2h-12a2 2 0 0 1-2-2v-12z"/>
                                        <path d="M16 3v4"/><path d="M8 3v4"/><path d="M4 11h16"/>
                                        <path d="M11 15h1"/><path d="M12 15v3"/>
                                    </svg>
                                </span>
                                <input class="form-control @error('eventdate') is-invalid @enderror"
                                       id="datepicker-icon-prepend"
                                       name="eventdate"
                                       placeholder="Select a date"
                                       value="{{ old('eventdate', date('Y-m-d')) }}"
                                       required>
                                @error('eventdate')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="card admin-gallery-create-card shadow-sm mb-4">
                <div class="card-header">
                    <h3 class="card-title">Upload Photos</h3>
                    <div class="card-subtitle">JPG, JPEG or PNG — maximum 20 files</div>
                </div>
                <div class="card-body">
                    <div class="alert alert-info d-flex align-items-center gap-2 mb-3">
                        <i class="ti ti-info-circle"></i>
                        <div>Upload JPG, JPEG or PNG. Maximum <strong>20 files</strong>.</div>
                    </div>
                    <div class="admin-gallery-dropzone" id="document-dropzone">
                        <div class="fallback"></div>
                    </div>
                    <div class="mt-3">
                        <span class="badge bg-primary-lt text-primary">
                            <i class="ti ti-photos me-1"></i>
                            Uploaded: <span id="photoCounter">0</span> file(s)
                        </span>
                    </div>
                </div>
                <div class="card-footer d-flex justify-content-end gap-2">
                    <button type="submit" class="btn btn-success" name="action" value="save" formnovalidate>
                        <i class="ti ti-device-floppy me-1"></i>Save as Draft
                    </button>
                    <button type="submit" class="btn btn-primary" name="action" value="publish">
                        <i class="ti ti-send me-1"></i>Save & Publish
                    </button>
                </div>
            </div>

        </form>

    </div>
</div>

@endsection

@section('script')
<script src="{{ $url_dropzone_js }}"></script>
<script src="{{ $url_litepicker }}"></script>
<script>
    var uploadedDocumentMap = {};

    Dropzone.autoDiscover = false;

    function updateCounter() {
        var total   = document.querySelectorAll('input[name="documents[]"]').length;
        var counter = document.getElementById('photoCounter');
        if (counter) counter.innerText = total;
    }

    var myDropzone = new Dropzone('div#document-dropzone', {
        url:             '{{ route('eventphoto.uploadfileajax') }}',
        autoProcessQueue: true,
        uploadMultiple:   true,
        addRemoveLinks:   true,
        acceptedFiles:    '.jpg,.png,.jpeg',
        parallelUploads:  2,
        headers:          { 'X-CSRF-TOKEN': '{{ csrf_token() }}' },
        maxFiles:         20,
        maxFilesize:      1000,
        init: function() {
            this.on('error', function(file, message) {
                alert(message);
                this.removeFile(file);
            });
            this.on('maxfilesexceeded', function(file) {
                alert('Maximum 20 files allowed.');
                this.removeFile(file);
            });
        },
        successmultiple: function(files, response) {
            $.each(response.name, function(key, val) {
                $('form').append('<input type="hidden" name="documents[]" value="' + val + '">');
                uploadedDocumentMap[files[key].name] = val;
            });
            updateCounter();
        },
        removedfile: function(file) {
            if (file.previewElement) file.previewElement.remove();
            var name = (typeof file.file_name !== 'undefined')
                ? file.file_name
                : uploadedDocumentMap[file.name];
            if (name) {
                $('form').find('input[name="documents[]"][value="' + name + '"]').remove();
                updateCounter();
                $.ajax({
                    url:  '{{ route('eventphoto.deletefileajax') }}',
                    type: 'GET',
                    data: { name: name },
                    success: function(data) { console.log('File deleted:', data); },
                    error:   function(err)  { console.log(err); }
                });
            }
        }
    });

    document.addEventListener('DOMContentLoaded', function() {
        if (window.Litepicker) {
            new Litepicker({
                element: document.getElementById('datepicker-icon-prepend'),
                format:  'YYYY-MM-DD',
                buttonText: {
                    previousMonth: '<svg xmlns="http://www.w3.org/2000/svg" class="icon" width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M15 6l-6 6l6 6"/></svg>',
                    nextMonth:     '<svg xmlns="http://www.w3.org/2000/svg" class="icon" width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M9 6l6 6l-6 6"/></svg>'
                }
            });
        }
        updateCounter();
    });
</script>
@endsection
