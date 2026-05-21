@extends('layouts.sidebar-master')

@section('content')
    <div class="page-body">
        <div class="container">

            @if (session()->has('success'))
                <div class="alert alert-important alert-success alert-dismissible" role="alert">
                    <div class="d-flex">
                        <div>
                            <svg xmlns="http://www.w3.org/2000/svg" class="icon alert-icon" width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round">
                                <path stroke="none" d="M0 0h24v24H0z" fill="none"></path>
                                <path d="M5 12l5 5l10 -10"></path>
                            </svg>
                        </div>
                        <div>
                            {{ session('success') }}
                        </div>
                    </div>
                    <a class="btn-close btn-close-white" data-bs-dismiss="alert" aria-label="close"></a>
                </div>
            @endif

            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">{{ $title }}</h3>
                </div>
                <div class="card-body">
                    {{-- <button type="button" class="btn btn-primary" data-bs-trigger="hover" data-bs-toggle="popover" title="Popover title" data-bs-content="And here's some amazing content. It's very engaging. Right?">
                        Hover to toggle popover
                    </button> --}}
                    <div id="table-default" class="table-responsive">
                        @unless (count($trainingplan) == 0)
                            <table class="table table-bordered table-striped" id="MyTable">
                                <thead>
                                    <tr>
                                        <th>No</th>
                                        <th>Title</th>
                                        <th>Content</th>
                                        <th>Publish Date</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach ($trainingplan as $list)
                                        @php
                                            if ($list->active == 'Active') {
                                                $activecolor = 'green';
                                            } else {
                                                $activecolor = 'orange';
                                            }
                                        @endphp
                                        <tr>
                                            @php
                                                $listcontent = '';

                                                foreach ($list->trainingplandetail as $detail) {
                                                    if ($detail->content != null) {
                                                        $listcontent .= '- ' . $detail->content->title . '<br>';
                                                    }
                                                }
                                            @endphp
                                            <td>{{ $loop->index + 1 }}</td>
                                            <td>
                                                <a href="{{ route('playlist.user.view', [$list->slug, $list->trainingplandetail[0]->content->slug]) }}">{{ $list->title }}</a>
                                            </td>
                                            <td data-bs-trigger="hover" data-bs-toggle="popover" title="Content List" data-bs-html="true" data-bs-content="{{ $listcontent }}">
                                                @foreach ($list->trainingplandetail->take(3) as $detail)
                                                    @if ($detail->content != null)
                                                        - {{ $detail->content->title }}<br>
                                                    @endif
                                                @endforeach
                                                @if (count($list->trainingplandetail) >= 4)
                                                    - ......
                                                @else
                                                @endif
                                            </td>
                                            <td class="text-center">{{ date_format(date_create($list->created_at), 'Y-m-d') }}</td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        @else
                            <p>No Playlist data found</p>
                        @endunless
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
