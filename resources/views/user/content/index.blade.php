@extends('layouts.master')

@section('content')
    <div class="page-body">
        <div class="container">
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">All Content</h3>
                </div>
                <div class="card-body">

                    <form action="{{ route('content.user.index') }}" method="get">
                        <div class="input-group mb-2">
                            <span class="input-group-text">
                                Filter By
                            </span>
                            <select name="type" id="type" class="form-select">
                                <option value="">- Choose Type -</option>
                                @foreach ($contentType as $type)
                                    <option value="{{ $type->type }}" {{ $type->type == $filterType ? 'selected' : '' }}>{{ $type->type }}</option>
                                @endforeach
                            </select>
                            <select name="classification" id="classification" class="form-select">
                                <option value="">- Choose Type -</option>
                                @foreach ($contentClassification as $contentClass)
                                    <option value="{{ $contentClass->classification_id }}" {{ $contentClass->classification_id == $filterClassification ? 'selected' : '' }}>{{ $contentClass->classification->classification_name }}</option>
                                @endforeach
                            </select>
                            <select name="date_order" id="date-order" class="form-select">
                                <option value="">- Choose Date Order -</option>
                                <option value="newest" {{ $filterDateOrder == 'newest' ? 'selected' : '' }}>Newest</option>
                                <option value="oldest" {{ $filterDateOrder == 'oldest' ? 'selected' : '' }}>Oldest</option>
                            </select>
                            <button type="submit" class="btn btn-primary"><i class="fas fa-filter"></i></button>
                        </div>
                    </form>

                    <hr class="my-2">

                    <div id="table-default" class="table-responsive">
                        @unless (count($content) == 0)
                            <table class="table table-bordered table-striped table-vcenter" id="MyTable">
                                <thead>
                                    <tr>
                                        <th>#</th>
                                        <th>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;</th>
                                        <th>Title</th>
                                        <th>Type</th>
                                        <th>Classification</th>
                                        <th>Duration</th>
                                        <th>Date</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach ($content as $list)
                                        <tr>
                                            <td>{{ $loop->index + 1 }}</td>
                                            <td>
                                                @if ($list->type == 'YouTube')
                                                    {{-- <img src="http://img.youtube.com/vi/{{ $list->link }}/mqdefault.jpg" class="w-7 h-7 object-cover card-img-start" alt="Beautiful blonde woman relaxing with a can of coke on a tree stump by the beach" /> --}}
                                                    <div class="img-responsive img-responsive-4x3 rounded" style="background-image: url(http://img.youtube.com/vi/{{ $list->link }}/mqdefault.jpg); position:relative;">
                                                        {{-- <div class="ribbon bg-red ribbon-bottom">{{ $list->duration }}</div> --}}
                                                    </div>
                                                @else
                                                    @php
                                                        $src = 'storage/files/content/' . $list->id . '/thumb.jpg';
                                                    @endphp
                                                    @if (\File::exists(public_path($src)) && !is_dir($src))
                                                        <div class="img-responsive img-responsive-4x3 rounded" style="background-image: url({{ asset($src) }});position:relative;">
                                                            {{-- <div class="ribbon bg-red ribbon-bottom">{{ $list->duration }}</div> --}}
                                                        </div>
                                                    @else
                                                        <div class="img-responsive img-responsive-4x3 rounded" style="background-image: url({{ asset('default-video.jpg') }});position:relative;">
                                                            {{-- <div class="ribbon bg-red ribbon-bottom">{{ $list->duration }}</div> --}}
                                                        </div>
                                                    @endif
                                                @endif
                                                {{-- <span class="avatar avatar-sm" style="background-image: url({{ asset('storage/profile') }}/{{ auth()->user()->avatar ?? 'default.jpg' }})"></span></td> --}}
                                            <td>
                                                <div class="row align-items-center">
                                                    <div class="col-auto">
                                                        @if (in_array($list->type, ['Video', 'YouTube']))
                                                            <span class="bg-red-lt avatar">
                                                                <svg xmlns="http://www.w3.org/2000/svg" class="icon icon-tabler icon-tabler-video" width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round">
                                                                    <path stroke="none" d="M0 0h24v24H0z" fill="none"></path>
                                                                    <path d="M15 10l4.553 -2.276a1 1 0 0 1 1.447 .894v6.764a1 1 0 0 1 -1.447 .894l-4.553 -2.276v-4z"></path>
                                                                    <path d="M3 6m0 2a2 2 0 0 1 2 -2h8a2 2 0 0 1 2 2v8a2 2 0 0 1 -2 2h-8a2 2 0 0 1 -2 -2z"></path>
                                                                </svg>
                                                            </span>
                                                        @else
                                                            <span class="bg-yellow-lt avatar">
                                                                <svg xmlns="http://www.w3.org/2000/svg" class="icon icon-tabler icon-tabler-file-type-pdf" width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round">
                                                                    <path stroke="none" d="M0 0h24v24H0z" fill="none"></path>
                                                                    <path d="M14 3v4a1 1 0 0 0 1 1h4"></path>
                                                                    <path d="M5 12v-7a2 2 0 0 1 2 -2h7l5 5v4"></path>
                                                                    <path d="M5 18h1.5a1.5 1.5 0 0 0 0 -3h-1.5v6"></path>
                                                                    <path d="M17 18h2"></path>
                                                                    <path d="M20 15h-3v6"></path>
                                                                    <path d="M11 15v6h1a2 2 0 0 0 2 -2v-2a2 2 0 0 0 -2 -2h-1z"></path>
                                                                </svg>
                                                            </span>
                                                        @endif
                                                    </div>
                                                    <div class="col">
                                                        <h4 class="mb-0"><a href="{{ route('content.user.view', $list->slug) }}" class="text-reset">{{ $list->title }}</a></h4>
                                                        <div class="text-muted">
                                                            {!! nl2br($list->description) !!}
                                                        </div>
                                                    </div>
                                                </div>
                                            </td>
                                            <td>{{ $list->type }}</td>
                                            <td><a href="{{ route('content.user.category', $list->classification->slug) }}">{{ $list->classification->classification_name ?? '' }}</a></td>
                                            <td>{{ $list->duration }}</td>
                                            <td>{{ $list->date }}</td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        @else
                            <p>No content data found</p>
                        @endunless
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection

@section('style')
    <!-- DataTables -->
    <link rel="stylesheet" href="{{ asset('css/dataTables.bootstrap5.min.css') }}">
@endsection

@section('script')
    <script src="{{ asset('js/jquery.dataTables.min.js') }}"></script>
    <script src="{{ asset('js/dataTables.bootstrap5.min.js') }}"></script>
    <script>
        // let table = new DataTable('#MyTable');
        $(function() {
            $("#MyTable").DataTable({
                "lengthMenu": [
                    [10, 25, 50, -1],
                    [10, 25, 50, 'All'],
                ],
                "pageLength": 25,
                "autoWidth": false,
            });
        });
    </script>
@endsection
