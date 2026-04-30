@extends('layouts.master')

@section('content')
    <div class="page-body">
        <div class="container">
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title mb-0">{{ $title }}</h3>
                </div>
                <div class="card-body">
                    <form action="{{ url()->current() }}" method="GET" class="ms-auto" id="form1">
                        <div class="row justify-content-end">
                            <div class="col-7">
                                <div class="d-flex">
                                    @php
                                        $arraystatus = ['' => 'All', 'mandatory' => 'Mandatory Learning', 'optional' => 'Optional Learning'];
                                    @endphp
                                    
                                    <x-label class="col-form-label me-1">Learning Program:</x-label>
                                    <x-select name="status" class="col me-1" id="learn-status">
                                        @foreach ($arraystatus as $key => $value)
                                            <option value="{{ $key }}" @if (request('status') == $key) selected @endif>{{ $value }}</option>
                                        @endforeach
                                    </x-select>
                                    <x-select name="year" class="col me-1" id="learn-year">
                                        <option value="">All</option>
                                        @foreach ($years as $year)
                                            <option value="{{ $year }}" @if (request('year') == $year) selected @endif>{{ $year }}</option>
                                        @endforeach
                                    </x-select>
                                </div>
                            </div>
                        </div>

                        <div class="row justify-content-between mt-3">
                            <div class="col-md-3">
                                <div class="d-flex align-items-center">
                                    <label class="me-2">Show</label>
                                    <select name="show_entries" id="show-entries" class="form-select" style="width: 75px">
                                        <option value="10" {{ $entries == 10 ? 'selected' : '' }}>10</option>
                                        <option value="25" {{ $entries == 25 ? 'selected' : '' }}>25</option>
                                        <option value="50" {{ $entries == 50 ? 'selected' : '' }}>50</option>
                                        <option value="100" {{ $entries == 100 ? 'selected' : '' }}>100</option>
                                    </select>
                                    <label class="ms-2">Entries</label>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="d-flex">
                                    <input type="hidden" name="type" value="{{ request()->segment(3) }}">
                                    <input type="hidden" name="slug" value="{{ $downloadslug }}">
                                    <input type="text" name="search" class="form-control me-1" placeholder="Search..." aria-label="Search" aria-describedby="button-addon2" value="{{ $search }}">
                                    <x-button class="btn-icon me-1" name="action" value="search" type="submit">
                                        <svg xmlns="http://www.w3.org/2000/svg" class="icon" width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round">
                                            <path stroke="none" d="M0 0h24v24H0z" fill="none"></path>
                                            <path d="M10 10m-7 0a7 7 0 1 0 14 0a7 7 0 1 0 -14 0"></path>
                                            <path d="M21 21l-6 -6"></path>
                                        </svg>
                                    </x-button>
                                    <x-button class="btn-icon btn-green" name="action" value="download" type="submit" formTarget="_blank">
                                        <svg xmlns="http://www.w3.org/2000/svg" class="icon icon-tabler icon-tabler-download" width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round">
                                            <path stroke="none" d="M0 0h24v24H0z" fill="none"></path>
                                            <path d="M4 17v2a2 2 0 0 0 2 2h12a2 2 0 0 0 2 -2v-2"></path>
                                            <path d="M7 11l5 5l5 -5"></path>
                                            <path d="M12 4l0 12"></path>
                                        </svg>
                                    </x-button>
                                </div>
                            </div>
                        </div>
                    </form>
                    <div class="table-responsive mt-3">
                        <table class="table table-bordered table-striped table-vcenter mb-3">
                            <thead>
                                <tr>
                                    <th>#</th>
                                    <th>Status</th>
                                    <th>Name</th>
                                    <th>Title</th>
                                    <th>Classification</th>
                                    <th>Duration</th>
                                    <th>Date</th>
                                    <th width="10%">Progress</th>
                                    <th>Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse ($content as $list)

                                    @php
                                        $activecolor = $list->active == 'Active' ? 'green' : 'orange';
                                    @endphp

                                    <tr>
                                        <td>{{ ++$no }}</td>
                                        <td>{{ $list->status }}</td>
                                        <td>
                                            <a href="{{ route('user.manage.profile', $list->user->ssohash) }}">{{ $list->user->name }}</a>
                                        </td>
                                        <td>
                                            <div class="row align-items-center">
                                                <div class="col-auto">
                                                    @if (in_array($list->content->type, ['Video', 'YouTube']))
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
                                                    <a href="{{ route('learning.manage.content', $list->content->slug) }}" class="text-reset">{{ $list->content->title }}</a>
                                                </div>
                                            </div>
                                        </td>
                                        <td>
                                            <a href="{{ route('learning.manage.category', $list->content->classification->slug) }}" class="text-reset">{{ $list->content->classification->classification_name ?? '' }}</a>
                                        </td>
                                        <td>{{ $list->content->duration }}</td>
                                        <td>{{ $list->created_at }}</td>
                                        <td>
                                            <div class="progress progress-xs" data-bs-toggle="tooltip" title="{{ $list->progress }}%" data-bs-placement="top">
                                                <div class="progress-bar bg-primary" style="width: {{ $list->progress }}%"></div>
                                            </div>
                                        </td>
                                        <td>
                                            <div class="d-inline">
                                                <a href="" class="btn btn-info btn-icon">
                                                    <svg xmlns="http://www.w3.org/2000/svg" class="icon" width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round">
                                                        <path stroke="none" d="M0 0h24v24H0z" fill="none"></path>
                                                        <path d="M14 3v4a1 1 0 0 0 1 1h4"></path>
                                                        <path d="M17 21h-10a2 2 0 0 1 -2 -2v-14a2 2 0 0 1 2 -2h7l5 5v11a2 2 0 0 1 -2 2z"></path>
                                                        <path d="M9 9l1 0"></path>
                                                        <path d="M9 13l6 0"></path>
                                                        <path d="M9 17l6 0"></path>
                                                    </svg>
                                                </a>
                                            </div>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="9" class="text-center">No data available</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>

                        @if ($content->total() <= $entries)
                            <x-page.pagination-first count='{{ $content->count() }}' />
                        @else
                            {{ $content->links('pagination::bootstrap-5') }}
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection

@section('style')
    <link rel="stylesheet" href="{{ asset('css/dataTables.bootstrap5.min.css') }}">
@endsection

@section('script')
    <script src="{{ asset('js/jquery.dataTables.min.js') }}"></script>
    <script src="{{ asset('js/dataTables.bootstrap5.min.js') }}"></script>
    <script>
        $('#learn-status').on('change', function() {
            $('#form1').submit();
        });

        $('#learn-year').on('change', function() {
            $('#form1').submit();
        });

        $('#show-entries').on('change', function() {
            $('#form1').submit();
        });
    </script>
@endsection
