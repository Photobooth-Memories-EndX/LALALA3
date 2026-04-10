@extends('layouts.master')

@push('styles')
    <link rel="stylesheet" href="{{ asset('Tabler/dist/css/tabler-vendors.min.css?1684106062') }}">
@endpush

@section('content')
    <div class="page-header d-print-none">
        <div class="container-xl">
            <div class="row g-2 align-items-center">
                <div class="col">
                    <h2 class="page-title">{{ $title }}</h2>
                </div>
                <div class="col-auto ms-auto d-print-none">
                    <div class="d-flex">
                        <ol class="breadcrumb breadcrumb-arrows" aria-label="breadcrumbs">
                            <li class="breadcrumb-item"><a href="javascript:;">Manage</a></li>
                            <li class="breadcrumb-item"><a href="{{ route('trainingplan.index') }}">Playlist</a></li>
                            <li class="breadcrumb-item active" aria-current="page"><a href="#">{{ $title }}</a></li>
                        </ol>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="page-body">
        <div class="page page-center">
            <div class="container container-sm">
                <form class="card card-md" action="{{ route('trainingplan.store') }}" method="post" autocomplete="off">
                    @csrf
                    @method('POST')
                    <div class="card-body">

                        <x-alert type='warning'/>

                        <h2 class="card-title text-center mb-4">Create new Playlist</h2>
                        <div class="row g-3">
                            <div class="col-xl-6">
                                <label for="title" class="form-label required">Title</label>
                                <input type="text" class="form-control" placeholder="Enter title" name="title" id="title" required>
                            </div>
                            <div class="col-xl-6">
                                <label for="select-content" class="form-label">Content</label>
                                <select type="text" class="form-select" id="select-content" name="content[]" value="" multiple>
                                    @unless (count($content) == 0)
                                        @foreach ($content as $list)
                                            <option value="{{ $list->id }}">{{ $list->title }}</option>
                                        @endforeach
                                    @else
                                        <option value="">No content available</option>
                                    @endunless
                                </select>
                            </div>
                            <div class="col-12">
                                <label for="url-link" class="form-label">URL Link</label>
                                <input type="text" name="url_link" id="url-link" class="form-control">
                            </div>
                        </div>
                        <div class="d-flex justify-content-end mt-3 gap-2">
                            <button type="submit" class="btn btn-success" name="action" value="save" formnovalidate>Save</button>
                            <button type="submit" class="btn btn-primary" name="action" value="publish">Save & Publish</button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
    <script src="{{ asset('Tabler/dist/libs/tom-select/dist/js/tom-select.complete.min.js?1684106062') }}"></script>
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
@endpush