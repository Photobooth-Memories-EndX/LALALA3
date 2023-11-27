@extends('layouts.master')
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
                    <h2 class="page-title">
                        {{ $title }}
                    </h2>
                </div>
                <!-- Page title actions -->
                <div class="col-auto ms-auto d-print-none">
                    <div class="d-flex">
                        <ol class="breadcrumb breadcrumb-arrows" aria-label="breadcrumbs">
                            <li class="breadcrumb-item"><a href="#">Manage</a></li>
                            <li class="breadcrumb-item"><a href="{{ route('content.index') }}">Content</a></li>
                            <li class="breadcrumb-item active" aria-current="page"><a href="#">{{ $title }}</a>
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
                <form class="card card-md" action="{{ route('content.store') }}" method="post" autocomplete="off">
                    @csrf
                    @method('POST')
                    <div class="card-header">
                        <h3 class="card-title">Create new content</h3>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-xl-6">
                                <div class="mb-3">
                                    <label class="form-label">Title</label>
                                    <input type="text" class="form-control" placeholder="Enter title" name="title" id="title" required>
                                </div>
                            </div>
                            <div class="col-xl-6">
                                <div class="mb-3">
                                    <label class="form-label">Type</label>
                                    {{-- <input type="text" class="form-control" placeholder="Enter Type" name="type"
                                        id="type" required> --}}
                                    <select name="type" id="type" class="form-control" onchange="typevalue(this)" required>
                                        <option value="">---------Select Type---------</option>
                                        <option value="Video">Video</option>
                                        <option value="Document">Document</option>
                                        <option value="YouTube">YouTube</option>
                                        <option value="SOP/Regulation">SOP/Regulation</option>
                                    </select>

                                </div>
                            </div>
                        </div>
                        <div class="row" style="display: none;" id="linkrow">

                            <div class="col-xl-12">
                                <div class="mb-3">
                                    <label class="form-label">YouTube Link</label>
                                    <input type="text" class="form-control" placeholder="Enter youtube link" name="link" id="link">
                                </div>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-xl-6">
                                <div class="mb-3">
                                    <label class="form-label">Classification</label>
                                    <select name="classification" id="classification" class="form-control" required>
                                        <option value="">---------Select Classification---------</option>
                                        @foreach ($classification as $list)
                                            <option value="{{ $list->id }}">{{ $list->classification_name }}</option>
                                        @endforeach

                                    </select>
                                </div>
                            </div>
                            <div class="col-xl-6">
                                <div class="mb-3">
                                    <label class="form-label">Duration (mm:ss)</label>
                                    <input type="text" class="form-control" placeholder="Enter duration" name="duration" id="duration" data-mask="00:00" data-mask-visible="true" placeholder="00:00" autocomplete="off">
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
                                        <input class="form-control" placeholder="Select a date" id="datepicker-icon-prepend" name="date" value="{{ date('Y-m-d') }}" required>
                                    </div>
                                </div>
                            </div>
                            <div class="col-xl-6">
                                <div class="mb-3">
                                    <label class="form-label">Author</label>
                                    <input type="text" class="form-control" placeholder="Enter author" name="author" id="author" required>
                                </div>
                            </div>
                        </div>
                        <div class="row">

                            <div class="col-xl-6">
                                <div class="mb-3">
                                    <label class="form-check">
                                        <input type="hidden" name="playlistonly" value="N">
                                        <input class="form-check-input" type="checkbox" name="playlistonly" value="Y">
                                        <span class="form-check-label">Only show this content on Playlist</span>

                                    </label>

                                </div>
                            </div>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Content Description</label>
                            <textarea name="description" id="description" cols="30" rows="10" class="form-control" required></textarea>
                            {{-- <input type="text" class="form-control" placeholder="Enter duration" name="duration"
                              id="duration"> --}}
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Upload File</label>
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
                            {{-- <input type="hidden" name="filetype" id="filetype" value=".jpg, .png"> --}}
                            <button type="submit" class="btn btn-success" name="action" value="save" formnovalidate>Save Content</button>
                            <button type="submit" class="btn btn-primary" name="action" value="publish">Save & Publish Content</button>
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
    </style>
@endsection

@section('script')
    <script src={{ $url_dropzone_js }}></script>
    <script src={{ $url_litepicker }}></script>
    <script>
        // @formatter:off
        // document.addEventListener("DOMContentLoaded", function() {
        //     new Dropzone("#document-dropzone")
        // })
        function typevalue(select) {
            // You can get the value like you did
            var value = select.value;
            // console.log(value);
            if (value == "YouTube") {
                document.getElementById("linkrow").style.display = "block";
            } else {
                document.getElementById("linkrow").style.display = "none";
            }

            // And here we get the name
            // var selectedOption = select.options[select.selectedIndex];
            // var name = selectedOption.getAttribute('name');
            // console.log(name);
        }

        let uploadedDocumentMap = {};
        Dropzone.autoDiscover = false;
        let myDropzone = new Dropzone("div#document-dropzone", {
            url: '{{ route('content.uploadfileajax') }}',
            autoProcessQueue: true,
            uploadMultiple: true,
            addRemoveLinks: true,
            // acceptedFiles: document.getElementById('filetype').value,
            // acceptedFiles: fileType,
            acceptedFiles: ".pdf,.mp4,.avi",
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
        // var fileType;
        // myDropzone.options.acceptedFiles = ".mp4";
        // function setfilerequire(type) {
        //     var typeValue = type.value;

        //     if (typeValue == 'Video') {
        //         // fileType = ".mp4";
        //         // fileType = document.getElementById('filetype').value = ".mp4";
        //         myDropzone.options.acceptedFiles = ".mp4";
        //     } else if (typeValue == 'PDF') {
        //         // fileType = ".PDF,.pdf";
        //         // fileType = document.getElementById('filetype').value = ".PDF,.pdf";
        //         myDropzone.options.acceptedFiles = ".PDF,.pdf";

        //     }
        //     // alert(document.getElementById('filetype').value);
        //     // return p1 * p2;
        // }
        // $(document).on('change', '#ddlcategory', function() {
        //     // myDropzone.options.url = "someurl.html?type=" + $('#ddlcategory').val();
        //     myDropzone.options.acceptedFiles = ".PDF,.pdf";
        // });
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
        // @formatter:on
        // $(document).on('click', '#btn-api', function(e) {
        //     e.preventDefault();
        //     $("#overlay").removeClass('d-none');
        //     var emp_id = $('#id_emp').val();
        //     $.ajax({
        //         type: "GET",
        //         url: '{{ config('api.employee.base_url') }}' + emp_id,
        //         dataType: "json",
        //         success: function(response) {
        //             if (response.status == 404) {
        //                 alert(response.message);
        //             } else {
        //                 $("#name").val(response.name);
        //                 $("#department").val(response.department);
        //                 $("#team").val(response.team);
        //                 $("#division").val(response.division);
        //                 $("#email").val(response.email);
        //                 $("#id_org_unit").val(response.id_ou);
        //                 $("#id_user").val(response.id_user);
        //             }
        //             $("#overlay").addClass('d-none');
        //         }
        //     });
        // });
    </script>
@endsection
