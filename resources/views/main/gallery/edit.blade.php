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
                    <h2 class="page-title">
                        Add Event Photo
                    </h2>
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
        <div class="page page-center">
            <div class="container container-sm py-4">
                <x-alert type='success' />
                <x-alert type='warning' />
                <form class="card card-md" action="{{ route('eventphoto.update', $eventphoto->slug) }}" method="post" autocomplete="off">
                    @csrf
                    @method('PUT')
                    <input type="hidden" name="id" value="{{ $eventphoto->id }}">
                    <div class="card-body">
                        <h2 class="card-title text-center mb-4">Edit event</h2>
                        <div class="row">
                            <div class="col-xl-6">
                                <div class="mb-3">
                                    <label class="form-label">Title</label>
                                    <input type="text" class="form-control" placeholder="Enter title" name="title" id="title" value="{{ $eventphoto->title }}" required>
                                </div>
                            </div>
                            <div class="col-xl-6">
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
                            $arraypic = explode(',', $eventphoto->photos);
                            $i = 0;
                        @endphp
                        <div class="row row-cols-6 g-3">
                            @foreach ($arraypic as $listphoto)
                                @php
                                    $i++;
                                @endphp

                                <div class="col" id="{{ $eventphoto->id . str_replace($search, '', $listphoto) }}"
                                    @if ($i == 1) {{-- first --}}
                                        style='border-left : 1px solid #d9d9d9;border-top : 1px solid #d9d9d9; border-bottom: 1px solid #d9d9d9;border-top-left-radius: 0.5em 0.5em;border-bottom-left-radius: 0.5em 0.5em;'
                                    @elseif($i == count($arraypic)){{-- last --}}
                                        style='border: 1px solid #d9d9d9;border-top-right-radius: 0.5em 0.5em;border-bottom-right-radius: 0.5em 0.5em;'
                                    @elseif(fmod($i, 6) == 0){{-- last in row --}}
                                        style='border: 1px solid #d9d9d9;border-top-right-radius: 0.5em 0.5em;border-bottom-right-radius: 0.5em 0.5em;'
                                    @elseif(fmod($i, 6) == 1) {{-- first next row --}}
                                        style='border-left : 1px solid #d9d9d9;border-top : 1px solid #d9d9d9; border-bottom: 1px solid #d9d9d9;border-top-left-radius: 0.5em 0.5em;border-bottom-left-radius: 0.5em 0.5em;' 
                                    @else
                                        style='border-left : 1px solid #d9d9d9;border-top : 1px solid #d9d9d9; border-bottom: 1px solid #d9d9d9;' @endif>
                                    <div style="padding: 3px">
                                        <center>

                                            <input type="radio" name="thumbnail" value="{{ str_replace($search, '', $listphoto) }}" @if (str_replace($search, '', $listphoto) == $eventphoto->thumbnail) checked @endif> Thumbnail
                                        </center>
                                    </div>

                                    <a data-fslightbox="gallery" href="{{ asset('storage/gallery/photo/' . $eventphoto->id . '/' . str_replace($search, '', $listphoto)) }}">
                                        <!-- Photo -->
                                        <div class="img-responsive img-responsive-1x1 rounded border" style="background-image: url({{ asset('storage/gallery/photo/' . $eventphoto->id . '/' . str_replace($search, '', $listphoto)) }})"></div>
                                    </a>
                                    @if ($eventphoto->active == 'Inactive')
                                        @if (str_replace($search, '', $listphoto) != $eventphoto->thumbnail)
                                            <center>
                                                <button type="button" onclick="deleteimage()" class="btn btn-danger btndelete" data-id="{{ $eventphoto->id }}" data-option="{{ str_replace($search, '', $listphoto) }}" formnovalidate>Delete</button>
                                            </center>
                                        @endif
                                    @endif
                                </div>
                            @endforeach
                        </div>

                        {{-- <div class="mb-3">
                            <label class="form-label">Content Description</label>
                            <textarea name="description" id="description" cols="30" rows="10" class="form-control" required></textarea>
                        </div> --}}
                        <br>
                        <div class="mb-3">
                            <label class="form-label">Upload Photo</label>
                            <div class="dropzone" id="document-dropzone">
                                <div class="fallback">
                                </div>
                            </div>
                        </div>

                        <div class="form-footer" style="float: right">
                            @if ($eventphoto->active == 'Active')
                                <button type="submit" class="btn btn-warning" name="action" value="unpublish">Unpublish</button>
                            @else
                                <button type="submit" class="btn btn-success" name="action" value="save">Save</button>
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
    </style>
@endsection

@section('script')
    <script src={{ $url_dropzone_js }}></script>
    <script src={{ $url_litepicker }}></script>
    <script src={{ $url_lightbox_js }}></script>
    <script>
        function deleteimage(e) {
            if (confirm("Are you sure want to delete this image?")) {
                if (!e) var e = window.event; // Get the window event
                e.cancelBubble = true; // IE Stop propagation
                if (e.stopPropagation) e.stopPropagation(); // Other Broswers
                var id = $(e.target).data('id');
                var option = $(e.target).data('option');
                $.ajax({
                    type: "POST",
                    url: "{{ route('eventphoto.deleteImage') }}",
                    data: {
                        '_token': '{{ csrf_token() }}',
                        id: id,
                        option: option
                    },
                    success: function(data) {
                        if (data.status == 200) {
                            document.getElementById(id + option).style = "display:none;"
                            console.log(data.status);
                        }
                    }
                });
            }
        };

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
