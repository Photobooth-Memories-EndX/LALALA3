@extends('layouts.master')
@section('content')
    @php
        $url_select = asset('Tabler/dist/libs/tom-select/dist/js/tom-select.complete.min.js?1684106062');
        $url_vendor = asset('Tabler/dist/css/tabler-vendors.min.css?1684106062');
    @endphp
    {{--  <link href="./dist/css/tabler-flags.min.css?1684106062" rel="stylesheet"/>
        <link href="./dist/css/tabler-payments.min.css?1684106062" rel="stylesheet"/>
        <link href="./dist/css/tabler-vendors.min.css?1684106062" rel="stylesheet"/> --}}
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
                            <li class="breadcrumb-item"><a href="{{ route('trainingplan.index') }}">Playlist</a></li>
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
                <form class="card card-md" action="{{ route('trainingplan.store') }}" method="post" autocomplete="off">
                    @csrf
                    @method('POST')
                    <div class="card-body">
                        <h2 class="card-title text-center mb-4">Create new Playlist</h2>
                        <div class="row">
                            <div class="col-xl-6">
                                <div class="mb-3">
                                    <label class="form-label">Title</label>
                                    <input type="text" class="form-control" placeholder="Enter title" name="title" id="title" required>
                                </div>
                            </div>
                            <div class="col-xl-6">
                                <div class="mb-3">
                                    <label class="form-label">Content</label>
                                    {{-- <input type="text" class="form-control" placeholder="Enter Type" name="type"
                                        id="type" required> --}}
                                    <select type="text" class="form-select" id="select-content" name="content[]" value="" multiple>
                                        @unless (count($content) == 0)
                                            @foreach ($content as $list)
                                                <option value="{{ $list->id }}">{{ $list->title }}</option>
                                            @endforeach
                                        @else
                                            <option value="">No content available</option>
                                        @endunless
                                    </select>

                                    {{-- <input type="text" id="input-tags" value="awesome,neat" autocomplete="off"> --}}

                                </div>
                            </div>
                        </div>
                        {{-- <div class="row" style="display: none;" id="linkrow">

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
                                    <label class="form-label">Duration</label>
                                    <input type="text" class="form-control" placeholder="Enter duration" name="duration" id="duration">
                                </div>
                            </div>
                        </div> --}}



                        <div class="form-footer" style="float: right">
                            {{-- <input type="hidden" name="filetype" id="filetype" value=".jpg, .png"> --}}
                            <button type="submit" class="btn btn-success" name="action" value="save" formnovalidate>Save</button>
                            <button type="submit" class="btn btn-primary" name="action" value="publish">Save & Publish</button>
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
    <link href="{{ $url_vendor }}" rel="stylesheet" />
@endsection

@section('script')
    {{-- <script src={{ $url_dropzone_js }}></script> --}}
    {{-- <script src={{ $url_litepicker }}></script> --}}
    <script src={{ $url_select }}></script>
    <script>
        document.addEventListener("DOMContentLoaded", function() {
            var el;
            window.TomSelect && (new TomSelect(el = document.getElementById('select-content'), {
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
        // new TomSelect('#input-tags', {
        //     plugins: {
        //         remove_button: {
        //             title: 'Remove this item',
        //         }
        //     },
        //     persist: false,
        //     create: true,
        //     onDelete: function(values) {
        //         return confirm(values.length > 1 ? 'Are you sure you want to remove these ' + values.length + ' items?' : 'Are you sure you want to remove "' + values[0] + '"?');
        //     }
        // });


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
    </script>
@endsection
