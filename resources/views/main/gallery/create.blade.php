@extends('layouts.sidebar-master')
@section('content')
    @php
        $url_dropzone_css = asset('Tabler/dist/libs/dropzone/dist/dropzone.css?1684106062');
        $url_dropzone_js = asset('Tabler/dist/libs/dropzone/dist/dropzone-min.js?1684106062');
        $url_litepicker = asset('Tabler/dist/libs/litepicker/dist/litepicker.js?1684106062');
    @endphp
    {{-- @push('css')
        <link rel="stylesheet" href="{{ $url_dropzone_css }}">
    @endpush --}}
    <div class="page-header d-print-none">
        <div class="container-xl">
            <div class="row g-2 align-items-center">
                <div class="col">
                    <div class="d-flex justify-content-between align-items-center">

                        <div>

                            <h2 class="page-title mb-1">
                                Create Gallery Event
                            </h2>

                            <div class="text-secondary">
                                Upload and manage event photo galleries
                            </div>

                        </div>

                        <div>

                            <span class="badge bg-primary">
                                New Gallery
                            </span>

                        </div>

                    </div>
                </div>
                <!-- Page title actions -->
                <div class="col-auto ms-auto d-print-none">
                    <div class="d-flex">
                        <ol class="breadcrumb breadcrumb-arrows" aria-label="breadcrumbs">
                            <li class="breadcrumb-item"><a href="#">Manage</a></li>
                            <li class="breadcrumb-item"><a href="{{ route('content.index') }}">Event Photo</a></li>
                            <li class="breadcrumb-item active" aria-current="page"><a href="#">Add Event</a>
                            </li>
                        </ol>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Page body -->
    <div class="page-body">
        <!-- Container Page -->
        <div class="page">
            <div class="container-xl py-4">

                {{-- <div class="container container-slim py-4 d-none" id="overlay">
                    <div class="text-center">

                        <div class="text-muted mb-3">Getting employee data</div>
                        <div class="progress progress-sm">
                            <div class="progress-bar progress-bar-indeterminate"></div>
                        </div>
                    </div>
                </div> --}}

                {{-- <form class="card shadow-lg border-0" action="{{ route('content.store') }}" method="post"> --}}
                <form class="card shadow-lg border-0" action="{{ route('eventphoto.store') }}" method="post" autocomplete="off">
                    @csrf
                    @method('POST')
                    <div class="card-body">
                       <div class="mb-4">

                                <h2 class="mb-1">

                                    Gallery Information

                                </h2>

                                <div class="text-secondary">

                                    Enter event details before uploading photos

                                </div>

                    </div>
                        <div class="row g-4">
                            <div class="col-lg-8">
                                <div class="mb-3">
                                    <label class="form-label">Title</label>
                                    <input type="text" class="form-control form-control-lg" placeholder="Enter title" name="title" id="title" required>
                                </div>
                            </div>
                            <div class="col-lg-4">
                                <div class="mb-3">
                                    <label class="form-label">Event Date</label>
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
                                        <input class="form-control form-control-lg" placeholder="Select a date" id="datepicker-icon-prepend" name="eventdate" value="{{ date('Y-m-d') }}" required>
                                    </div>
                                </div>
                            </div>
                        </div>



                        {{-- <div class="mb-3">
                            <label class="form-label">Content Description</label>
                            <textarea name="description" id="description" cols="30" rows="10" class="form-control" required></textarea>
                        </div> --}}
                        <div class="mb-3">
                           <h3 class="mb-3">

                                Upload Photos

                            </h3>

                            <div class="alert alert-info">

                                Upload JPG, JPEG or PNG files.
                                Maximum 20 files.

                            </div>
                            <div class="dropzone" id="document-dropzone">
                                <div class="fallback">
                                    {{-- <input name="file" type="file" /> --}}
                                </div>
                            </div>

                            <div class="mt-3">

                                <span class="badge bg-primary">

                                    Uploaded Files:
                                    <span id="photoCounter">0</span>

                                </span>

                            </div>

                        </div>

                        <div class="d-flex justify-content-end gap-2 mt-5">
                            {{-- <input type="hidden" name="filetype" id="filetype" value=".jpg, .png"> --}}
                            <button type="submit" class="btn btn-success btn-lg" name="action" value="save" formnovalidate>Save Event Photo</button>
                            <button type="submit" class="btn btn-primary btn-lg" name="action" value="publish">Save & Publish Event Photo</button>
                        </div>
                    </div>
                </form>
                {{-- <form class="dropzone" id="document-dropzone" action="./" autocomplete="off" novalidate>
                    <div class="fallback">
                        <input name="file" type="file" />
                    </div>
                </form> --}}
            </div>

        </div>
        <!-- Container Page -->
    </div>
    <!-- Page body -->
@endsection

@section('style')
    <link rel="stylesheet" href="{{ $url_dropzone_css }}">
    <style>
        #overlay {
            position: absolute;
            top: 0rem;
            right: 0rem;
            left: 0rem;
            bottom: 0rem;
            display: -webkit-box;
            display: -webkit-flex;
            display: -ms-flexbox;
            display: flex;
            -webkit-align-items: center;
            -webkit-box-align: center;
            -ms-flex-align: center;
            align-items: center;
            -webkit-box-pack: center;
            -ms-flex-pack: center;
            -webkit-justify-content: center;
            justify-content: center;
            overflow: hidden;
            border-radius: 0.25rem;
            z-index: 1000;
        }

        .card{
    border-radius:20px;
}

.form-control{
    border-radius:12px;
}

.dropzone{

    min-height:250px;

    border:2px dashed var(--tblr-primary);

    border-radius:20px;

    background:
    var(--tblr-bg-surface);

    transition:.3s;
}

.dropzone:hover{
    transform:scale(1.01);
}

[data-bs-theme="dark"] .card{
    background:#1f2937;
}

[data-bs-theme="dark"] .dropzone{
    background:#111827;
}

    </style>
@endsection

@section('script')
<script src="{{ $url_dropzone_js }}"></script>
<script src="{{ $url_litepicker }}"></script>

<script>

    let uploadedDocumentMap = {};

    Dropzone.autoDiscover = false;

    function updateCounter() {

        let total =
            document.querySelectorAll(
                'input[name="documents[]"]'
            ).length;

        let counter =
            document.getElementById('photoCounter');

        if (counter) {
            counter.innerText = total;
        }
    }

    let myDropzone = new Dropzone("div#document-dropzone", {

        url: '{{ route('eventphoto.uploadfileajax') }}',

        autoProcessQueue: true,

        uploadMultiple: true,

        addRemoveLinks: true,

        acceptedFiles: ".jpg,.png,.jpeg",

        parallelUploads: 2,

        headers: {
            'X-CSRF-TOKEN': "{{ csrf_token() }}"
        },

        maxFiles: 20,

        maxFilesize: 1000,

        init: function() {

            this.on("error", function(file, message) {

                alert(message);

                this.removeFile(file);

            });

            this.on("maxfilesexceeded", function(file) {

                alert("Maximum 20 files allowed.");

                this.removeFile(file);

            });

        },

        successmultiple: function(files, response) {

            $.each(response.name, function(key, val) {

                $('form').append(
                    '<input type="hidden" name="documents[]" value="' +
                    val +
                    '" required>'
                );

                uploadedDocumentMap[
                    files[key].name
                ] = val;

            });

            updateCounter();

        },

        removedfile: function(file) {

            if (file.previewElement) {
                file.previewElement.remove();
            }

            let name = '';

            if (typeof file.file_name !== 'undefined') {

                name = file.file_name;

            } else {

                name = uploadedDocumentMap[file.name];

            }

            if (name) {

                $('form')
                    .find(
                        'input[name="documents[]"][value="' +
                        name +
                        '"]'
                    )
                    .remove();

                updateCounter();

                $.ajax({

                    url: "{{ route('eventphoto.deletefileajax') }}"

                    type: "GET",

                    data: {
                        name: name
                    },

                    success: function(data) {

                        console.log(
                            'File deleted: ',
                            data
                        );

                    },

                    error: function(err) {

                        console.log(err);

                    }

                });

            }

        }

    });

    document.addEventListener("DOMContentLoaded", function() {

        if (window.Litepicker) {

            new Litepicker({

                element: document.getElementById(
                    'datepicker-icon-prepend'
                ),

                format: 'YYYY-MM-DD',

                buttonText: {

                    previousMonth: `
                    <svg xmlns="http://www.w3.org/2000/svg"
                        class="icon"
                        width="24"
                        height="24"
                        viewBox="0 0 24 24"
                        stroke-width="2"
                        stroke="currentColor"
                        fill="none"
                        stroke-linecap="round"
                        stroke-linejoin="round">

                        <path stroke="none"
                            d="M0 0h24v24H0z"
                            fill="none"/>

                        <path d="M15 6l-6 6l6 6"/>

                    </svg>`,

                    nextMonth: `
                    <svg xmlns="http://www.w3.org/2000/svg"
                        class="icon"
                        width="24"
                        height="24"
                        viewBox="0 0 24 24"
                        stroke-width="2"
                        stroke="currentColor"
                        fill="none"
                        stroke-linecap="round"
                        stroke-linejoin="round">

                        <path stroke="none"
                            d="M0 0h24v24H0z"
                            fill="none"/>

                        <path d="M9 6l6 6l-6 6"/>

                    </svg>`
                }

            });

        }

        updateCounter();

    });

</script>
@endsection


