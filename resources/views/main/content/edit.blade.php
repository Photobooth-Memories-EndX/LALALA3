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
    {{-- @push('css')
        <link rel="stylesheet" href="{{ $url_dropzone_css }}">
    @endpush --}}
    <div class="page-header d-print-none">
        <div class="container-xl">
            <div class="row g-2 align-items-center">
                <div class="col">
                    <h2 class="page-title">
                        {{-- {{ $title }} --}}
                    </h2>
                </div>
                <!-- Page title actions -->
                <div class="col-auto ms-auto d-print-none">
                    <div class="d-flex">
                        <ol class="breadcrumb breadcrumb-arrows" aria-label="breadcrumbs">
                            <li class="breadcrumb-item"><a href="#">Manage</a></li>
                            <li class="breadcrumb-item"><a href="{{ route('content.index') }}">Content</a></li>
                            <li class="breadcrumb-item active" aria-current="page"><a href="#">Edit Content</a>
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
            {{-- <div class="container container-tight py-4"> --}}
            <div class="container container-sm py-4">

                {{-- <div class="container container-slim py-4 d-none" id="overlay">
                    <div class="text-center">

                        <div class="text-muted mb-3">Getting employee data</div>
                        <div class="progress progress-sm">
                            <div class="progress-bar progress-bar-indeterminate"></div>
                        </div>
                    </div>
                </div> --}}

                {{-- <form class="card card-md" action="{{ route('content.store') }}" method="post"> --}}
                <x-alert type='success' />
                <x-alert type='warning' />
                <form class="card card-md" action="{{ route('content.assign', $content->slug) }}" method="post" autocomplete="off">
                    @csrf
                    @method('POST')
                    <div class="card-body">
                        <h2 class="card-title text-center mb-4">Assign Content to Employee</h2>
                        <div class="row">
                            <div class="col-xl-12">
                                <div class="mb-3">
                                    <label class="form-label">Employee</label>
                                    {{-- <input type="text" class="form-control" placeholder="Enter Type" name="type"
                                        id="type" required> --}}
                                    <select type="text" class="form-select" id="select-user" name="employee[]" value="" multiple>
                                        @unless (count($user) == 0)
                                            @foreach ($user as $list)
                                                <option value="{{ $list->id }}">{{ $list->id_emp }} - {{ $list->name }}</option>
                                            @endforeach
                                        @else
                                            <option value="">No content available</option>
                                        @endunless
                                    </select>
                                </div>
                            </div>
                        </div>
                        <input type="hidden" name="content_id" value="{{ $content->id }}">
                        <input type="hidden" name="slug" value="{{ $content->slug }}">
                        <button type="submit" class="btn btn-primary" name="action" value="assign">Assign</button>
                    </div>
                </form>
                <br>
                <form class="card card-md" action="{{ route('content.update', $content->slug) }}" method="post" autocomplete="off">
                    @csrf
                    @method('PUT')
                    <div class="card-header">
                        <h3 class="card-title">Edit content</h3>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-xl-6">
                                <div class="mb-3">
                                    <label class="form-label">Title</label>
                                    <input type="text" class="form-control" placeholder="Enter title" name="title" id="title" value="{{ $content->title ?? '' }}" required>
                                </div>
                            </div>
                            <div class="col-xl-6">
                                <div class="mb-3">
                                    <label class="form-label">Type</label>
                                    {{-- <input type="text" class="form-control" placeholder="Enter Type" name="type" id="type" value="{{ $content->type ?? '' }}" required> --}}
                                    <select name="type" id="type" class="form-control" onchange="typevalue(this)">
                                        <option value="">---------Select Type---------</option>
                                        <option value="Video" @if ($content->type == 'Video') selected='selected' @endif>Video</option>
                                        <option value="Document" @if ($content->type == 'Document') selected='selected' @endif>Document</option>
                                        <option value="YouTube" @if ($content->type == 'YouTube') selected='selected' @endif>YouTube</option>
                                        <option value="SOP/Regulation" @if ($content->type == 'SOP/Regulation') selected='selected' @endif>SOP/Regulation</option>
                                    </select>
                                </div>
                            </div>
                        </div>
                        <div class="row" style="display: none;" id="linkrow">

                            <div class="col-xl-12">
                                <div class="mb-3">
                                    <label class="form-label">YouTube Link</label>
                                    <input type="text" class="form-control" placeholder="Enter youtube link" name="link" id="link" value="https://www.youtube.com/watch?v={{ $content->link }}">
                                </div>
                            </div>
                        </div>
                        {{-- <input type="hidden" id="gettype"> --}}
                        <div class="row">
                            <div class="col-xl-6">
                                <div class="mb-3">
                                    <label class="form-label">Classification</label>
                                    <select name="classification" id="classification" class="form-control">
                                        <option value="">-------Select Classification---------</option>
                                        @foreach ($classification as $list)
                                            <option value="{{ $list->id }}" @if ($list->id == $content->classification_id) selected='selected' @endif>
                                                {{ $list->classification_name }}</option>
                                        @endforeach

                                    </select>
                                </div>
                            </div>
                            <div class="col-xl-6">
                                <div class="mb-3">
                                    <label class="form-label">Duration (mm:ss)</label>
                                    <input type="text" class="form-control" placeholder="Enter duration" name="duration" id="duration" value="{{ $content->duration ?? '' }}" data-mask="00:00" data-mask-visible="true" placeholder="00:00" autocomplete="off">
                                    <small class="form-hint">Format mm:ss. Required if video and youtube content for progress status</small>
                                </div>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-xl-6">
                                <div class="mb-3">
                                    <label class="form-label">Date</label>
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
                                        <input class="form-control" placeholder="Select a date" id="datepicker-icon-prepend" name="date" value="{{ $content->date ?? '' }}" />
                                    </div>
                                </div>
                            </div>
                            <div class="col-xl-6">
                                <div class="mb-3">
                                    <label class="form-label">Author</label>
                                    <input type="text" class="form-control" placeholder="Enter author" name="author" id="author" value="{{ $content->author ?? '' }}" required>
                                </div>
                            </div>
                        </div>
                        <div class="row">

                            <div class="col-xl-6">
                                <div class="mb-3">
                                    <label class="form-check">
                                        <input type="hidden" name="playlistonly" value="N">
                                        <input class="form-check-input" type="checkbox" name="playlistonly" value="Y" @if ($content->playlistonly == 'Y') checked @endif>
                                        <span class="form-check-label">Only show this content on Playlist</span>

                                    </label>

                                </div>
                            </div>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Content Description</label>
                            <textarea name="description" id="description" cols="30" rows="10" class="form-control">{{ $content->description ?? '' }}</textarea>
                            {{-- <input type="text" class="form-control" placeholder="Enter duration" name="duration"
                              id="duration"> --}}
                        </div>
                        <div class="mb-3">
                            @php
                                $contentfile = json_decode($content->file);
                            @endphp
                            <label class="form-label">Existing File</label>
                            <div style="text-align: center" id="contentContainer">
                                @php
                                    if (isset($contentfile[0])) {
                                        $getcontentfile = $contentfile[0];
                                        $displaycontent = 'display:block;';
                                    } elseif ($content->type == 'YouTube') {
                                        $getcontentfile = '';
                                        $displaycontent = 'display:none;';
                                    } else {
                                        $getcontentfile = '';
                                        $displaycontent = 'display:none;';
                                        echo 'No file exist';
                                    }
                                @endphp
                                @if ($content->type == 'Video')
                                    <video width="854" height="480" controls style="{{ $displaycontent }}" style="display: block; margin-left: auto; margin-right: auto;">
                                        <source src="{{ asset('storage/files/content/' . $content->id . '/' . $getcontentfile . '') }}" type="video/mp4">
                                        Your browser does not support the video tag.
                                        <input type="hidden" name="replacecontent" id="replacecontent" value="{{ $getcontentfile }}">
                                    </video>
                                @elseif ($content->type == 'Document' || $content->type == 'SOP/Regulation')
                                    <object data="{{ asset('storage/files/content/' . $content->id . '/' . $getcontentfile . '') }}" width="800px" height="800px" style="text-align: center"></object>
                                    {{-- <embed src="{{ asset('storage/files/content/' . $content->id . '/' . $getcontentfile . '') }}" width="800px" height="800px" style="text-align: center" /> --}}
                                @elseif ($content->type == 'YouTube')
                                    <div id="player-youtube" data-plyr-provider="youtube" data-plyr-embed-id="{{ $content->link }}"></div>
                                    {{-- <iframe width="854" height="480" src="https://www.youtube.com/embed/{{ $content->link }}"></iframe> --}}
                                @endif
                                <br>
                                @if ($content->type != 'YouTube')
                                    <a href="#" data-url="{{ route('contentdelete.deletecontentajax', $content->slug) }}" class="btn btn-danger btn-remove-content" @if ($content->active == 'Active') style="pointer-events: none" @endif>
                                        Delete Content
                                    </a>
                                @endif

                            </div>

                        </div>
                        <div class="mb-3">
                            <label class="form-label">Replace File</label>
                            <div class="dropzone" id="document-dropzone">
                                <div class="fallback">
                                    {{-- <input name="file" type="file" /> --}}
                                </div>
                            </div>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Upload Thumbnail</label>
                            <div class="dropzone" id="thumbnail-dropzone">
                                <div class="fallback">
                                    {{-- <input name="file" type="file" /> --}}
                                </div>
                            </div>
                        </div>


                        <div class="form-footer" style="float: right">
                            <input type="hidden" value="{{ $content->id }}" name="contentid">
                            @if ($content->active == 'Active')
                                <button type="submit" class="btn btn-warning" name="action" value="unpublish">Unpublish This Content</button>
                            @else
                                <button type="submit" class="btn btn-success" name="action" value="save">Save Content</button>
                                <button type="submit" class="btn btn-primary" name="action" value="publish">Save & Publish Content</button>
                            @endif

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
    <link rel="stylesheet" href="{{ $url_ytplayer_css }}">
    <link rel="stylesheet" href="{{ $url_vendor }}" />
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
    </style>
@endsection

@section('script')
    <script src={{ $url_dropzone_js }}></script>
    <script src={{ $url_litepicker }}></script>
    <script src={{ $url_ytplayer_js }}></script>
    <script src={{ $url_select }}></script>
    <script>
        // @formatter:off
        // document.addEventListener("DOMContentLoaded", function() {
        //     new Dropzone("#document-dropzone")
        // })
        document.addEventListener("DOMContentLoaded", function() {
            window.Plyr && (new Plyr('#player-youtube'));
        });
        $(document).on('click', '.btn-remove-content', function(e) {
            e.preventDefault();
            if (confirm("Are you sure want to delete this media content?")) {
                var column = $(this).data('column');
                $.ajax({
                    type: "PUT",
                    url: $(this).data('url'),
                    data: {
                        '_token': '{{ csrf_token() }}'
                    },
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
            headers: {
                'X-CSRF-TOKEN': "{{ csrf_token() }}"
            },
            maxFiles: 1,
            maxFilesize: 1000, //max file size in MB,
            init: function() {
                this.on("error", function(file, message) {
                    alert(message);
                    this.removeFile(file);
                });
            },

            successmultiple: function(data, response) {
                $.each(response['name'], function(key, val) {
                    $('form').append('<input type="hidden" name="documents[]" value="' + val + '">');
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

        //----------------------------------------------------------------------------------------
        let uploadedThumbnailMap = {};
        Dropzone.autoDiscover = false;
        let myThumbnailDropzone = new Dropzone("div#thumbnail-dropzone", {
            url: '{{ route('content.uploadthumbnailajax') }}',
            autoProcessQueue: true,
            uploadMultiple: true,
            addRemoveLinks: true,
            acceptedFiles: ".jpg",
            parallelUploads: 2,
            headers: {
                'X-CSRF-TOKEN': "{{ csrf_token() }}"
            },
            maxFiles: 1,
            maxFilesize: 1000, //max file size in MB,
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
                file.previewElement.remove()
                let name = '';
                if (typeof file.file_name !== 'undefined') {
                    name = file.file_name;
                } else {
                    name = uploadedThumbnailMap[file.name];
                }
                $.ajax({
                    url: "{{ route('content.deletethumbnailajax') }}",
                    data: {
                        name: name
                    },
                    success: function(data) {
                        console.log('sukses : ' + data);
                    }
                })
                $('form').find('input[name="thumbnail[]"][value="' + name + '"]').remove()
            }
        });
        // @formatter:off
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
