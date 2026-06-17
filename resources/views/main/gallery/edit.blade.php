@extends('layouts.sidebar-master')
@section('content')
    @php
        $url_dropzone_css = asset('Tabler/dist/libs/dropzone/dist/dropzone.css?1684106062');
        $url_dropzone_js = asset('Tabler/dist/libs/dropzone/dist/dropzone-min.js?1684106062');
        $url_lightbox_js = asset('Tabler/dist/libs/fslightbox/index.js?1684106062');
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
                                Edit Event Gallery
                            </h2>

                            <div class="text-secondary">
                                Manage photos, thumbnail and publishing status
                            </div>

                        </div>

                        <span class="badge bg-{{ $eventphoto->active == 'Active' ? 'success' : 'warning' }}">
                            {{ $eventphoto->active }}
                        </span>

                    </div>
                </div>
                <!-- Page title actions -->
                <div class="col-auto ms-auto d-print-none">
                    <div class="d-flex">
                        <ol class="breadcrumb breadcrumb-arrows" aria-label="breadcrumbs">
                            <li class="breadcrumb-item"><a href="#">Manage</a></li>
                            <li class="breadcrumb-item"><a href="{{ route('eventphoto.index') }}">Event Photo</a></li>
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
                <x-alert type='success' />
                <x-alert type='warning' />
                <form class="card card-md" action="{{ route('eventphoto.update', $eventphoto->slug) }}" method="post" autocomplete="off">
                    @csrf
                    @method('PUT')
                    <input type="hidden" name="id" value="{{ $eventphoto->id }}">

                    @if($eventphoto->thumbnail)

                    <div class="card border-0 shadow-sm mb-4">

                        <div class="card-header">
                            <h3 class="card-title mb-0">
                                Featured Thumbnail
                            </h3>
                        </div>

                        <div class="card-body">

                            <img
                                src="{{ asset('storage/gallery/photo/'.$eventphoto->id.'/'.$eventphoto->thumbnail) }}"
                                class="cover-thumbnail">

                        </div>

                    </div>

                    @endif
                    <div class="card-body">
                        <div class="row row-cards mb-4">

                            <div class="col-md-4">

                                <div class="card stat-card">

                                    <div class="card-body">

                                        <div class="subheader">
                                            Total Photos
                                        </div>
                                        @php

                                        $arraypic =
                                            json_decode(
                                                $eventphoto->photos,
                                                true
                                            ) ?? [];

                                        @endphp
                                        <div class="h1 mb-0">
                                            {{ count($arraypic ?? []) }}
                                        </div>

                                    </div>

                                </div>

                            </div>

                            <div class="col-md-4">

                                <div class="card stat-card">

                                    <div class="card-body">

                                        <div class="subheader">
                                            Event Date
                                        </div>

                                        <div class="h4 mb-0">
                                            {{ date('d M Y', strtotime($eventphoto->eventdate)) }}
                                        </div>

                                    </div>

                                </div>

                            </div>

                            <div class="col-md-4">

                                <div class="card stat-card">

                                    <div class="card-body">

                                        <div class="subheader">
                                            Status
                                        </div>

                                        <div class="h4 mb-0">
                                            {{ $eventphoto->active }}
                                        </div>

                                    </div>

                                </div>

                            </div>

                        </div>
                        <h2 class="card-title text-center mb-4">Edit event</h2>
                        <div class="row g-4">
                            <div class="col-lg-8">
                                <div class="mb-3">
                                    <label class="form-label">Title</label>
                                    <input type="text" class="form-control" placeholder="Enter title" name="title" id="title" value="{{ $eventphoto->title }}" required>
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
                                        <input class="form-control" placeholder="Select a date" id="datepicker-icon-prepend" name="eventdate" value="{{ $eventphoto->eventdate }}" required>
                                    </div>
                                </div>
                            </div>
                        </div>
                       @php
                        $search = ['[', ']', '"'];
                        $arraypic = $eventphoto->photos
                        ? explode(',', $eventphoto->photos)
                        : [];
                        @endphp

                        <div class="card mb-4 border-0 shadow-sm">


                        <div class="card-header">
                            <h3 class="card-title mb-0">
                                Photo Gallery
                            </h3>

                            <div class="text-secondary">
                                {{ count($arraypic) }} Photos
                            </div>
                        </div>

                        <div class="card-body">

                            <div class="row g-4">

                                @foreach ($arraypic as $listphoto)

                                    @php
                                        $photo = str_replace($search, '', $listphoto);
                                        $isThumbnail = $photo == $eventphoto->thumbnail;
                                    @endphp

                                    <div
                                        class="col-sm-6 col-md-4 col-lg-3"
                                        id="{{ $eventphoto->id . $photo }}">

                                        <div class="card photo-card h-100">

                                            <div class="photo-wrapper">

                                                <a
                                                    data-fslightbox="gallery"
                                                    href="{{ asset('storage/gallery/photo/' . $eventphoto->id . '/' . $photo) }}">

                                                    <img
                                                        src="{{ asset('storage/gallery/photo/' . $eventphoto->id . '/' . $photo) }}"
                                                        class="photo-image">

                                                </a>

                                                @if($isThumbnail)

                                                    <span class="badge bg-primary thumbnail-badge">
                                                        Thumbnail
                                                    </span>

                                                @endif

                                            </div>

                                            <div class="card-body py-2">

                                                <label class="form-check">

                                                    <input
                                                        class="form-check-input"
                                                        type="radio"
                                                        name="thumbnail"
                                                        value="{{ $photo }}"
                                                        @checked($isThumbnail)>

                                                    <span class="form-check-label">
                                                        Set Thumbnail
                                                    </span>

                                                </label>

                                            </div>

                                            @if ($eventphoto->active == 'Inactive')

                                                @if(!$isThumbnail)

                                                    <div class="card-footer bg-transparent">

                                                        <button
                                                            type="button"
                                                            onclick="deleteimage(event)"
                                                            class="btn btn-danger btn-sm w-100 btndelete"
                                                            data-id="{{ $eventphoto->id }}"
                                                            data-option="{{ $photo }}">

                                                            Delete Photo

                                                        </button>

                                                    </div>

                                                @endif

                                            @endif

                                        </div>

                                    </div>

                                @endforeach

                            </div>

                        </div>


                        </div>

                        {{-- <div class="mb-3">
                            <label class="form-label">Content Description</label>
                            <textarea name="description" id="description" cols="30" rows="10" class="form-control" required></textarea>
                        </div> --}}
                        <br>
                        <div class="mb-3">
                            <h3 class="mb-3">
                                Upload Additional Photos
                            </h3>

                            <div class="alert alert-info">

                                Current photos:
                                <strong>
                                    {{ count($arraypic) }}
                                </strong>

                            </div>
                        </div>

                        <div class="d-flex justify-content-end gap-2 mt-4">
                            @if ($eventphoto->active == 'Active')
                                <button type="submit" class="btn btn-warning" name="action" value="unpublish">Unpublish</button>
                            @else
                                <button type="submit"class="btn btn-success btn-lg">
                                <button type="submit" class="btn btn-primary" name="action" value="publish">Save & Publish</button>
                            @endif
                        </div>
                    </div>
                </form>
            </div>

        </div>
        <!-- Container Page -->
    </div>
    <!-- Page body -->
@endsection

@section('style')
    <link rel="stylesheet" href="{{ $url_dropzone_css }}" defer>
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

        .btndelete {
            margin: 2px;
            padding: 2px;
        }

        .cover-thumbnail{
    width:100%;
    height:400px;
    object-fit:cover;
    border-radius:16px;
}

.photo-card{
    border:none;
    transition:.3s;
    overflow:hidden;
}

.photo-card:hover{
    transform:translateY(-5px);
    box-shadow:0 15px 35px rgba(0,0,0,.15);
}

.photo-image{
    width:100%;
    height:220px;
    object-fit:cover;
}

.thumbnail-badge{
    position:absolute;
    top:10px;
    right:10px;
}

.photo-wrapper{
    position:relative;
}

.stat-card{
    border:none;
    border-radius:16px;
}

.dropzone{
    min-height:250px;
    border-radius:20px;
    border:2px dashed var(--tblr-primary);
}

[data-bs-theme="dark"] .photo-card{
    background:#1f2937;
}

[data-bs-theme="dark"] .stat-card{
    background:#1f2937;
}

[data-bs-theme="dark"] .dropzone{
    background:#111827;
}

    </style>
@endsection

@section('script')
    <script src={{ $url_dropzone_js }}></script>
    <script src={{ $url_litepicker }}></script>
    <script src={{ $url_lightbox_js }}></script>
    <script>
function deleteimage(e) {

    e.preventDefault();

    if (!confirm("Are you sure want to delete this image?")) {
        return;
    }

    let id = $(e.target).data('id');
    let option = $(e.target).data('option');

    $.ajax({
        type: "POST",
        url: "{{ route('eventphoto.deleteImage') }}",
        data: {
            _token: "{{ csrf_token() }}",
            id: id,
            option: option
        },
        success: function(data) {

            if (data.status == 200) {

                $("#" + id + option)
                    .fadeOut(300, function() {
                        $(this).remove();
                    });

            }

        }
    });
}

        let uploadedDocumentMap = {};
        Dropzone.autoDiscover = false;
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
            maxFilesize: 1000, //max file size in MB,
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
                file.previewElement.remove()
                let name = '';
                if (typeof file.file_name !== 'undefined') {
                    name = file.file_name;
                } else {
                    name = uploadedDocumentMap[file.name];
                }
                $.ajax({
                    url: "{{ route('content.deletefileajax') }}",
                    data: {
                        name: name
                    },
                    success: function(data) {
                        console.log('sukses : ' + data);
                    }
                })
                $('form').find('input[name="documents[]"][value="' + name + '"]').remove()
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
    </script>
@endsection
