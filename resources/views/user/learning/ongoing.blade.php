@extends('layouts.master')
@section('content')

    {{-- <div class="page-header d-print-none">
        <div class="container-xl">
            <div class="row g-2 align-items-center">
                <div class="col">
                    <h2 class="page-title">
                        Learning On Going
                    </h2>
                </div>
                <!-- Page title actions -->
                <div class="col-auto ms-auto d-print-none">
                    <div class="d-flex">
                        <ol class="breadcrumb breadcrumb-arrows" aria-label="breadcrumbs">
                            <li class="breadcrumb-item"><a href="#">Learning</a></li>
                            <li class="breadcrumb-item active" aria-current="page"><a href="#">On Going</a>
                            </li>
                        </ol>
                    </div>
                </div>
            </div>
        </div>
    </div> --}}

    <!-- Page body -->
    <div class="page-body">
        <!-- Container Page -->
        <div class="container">


            <!-- Card -->
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">Learning On Going</h3>
                </div>
                <div class="card-body">
                    <div id="table-default" class="table-responsive">
                        @unless (count($content_user) == 0)
                            <table class="table table-bordered table-striped" id="MyTable">
                                <thead>
                                    <tr>
                                        <th>No</th>
                                        <th>Title</th>
                                        <th>Classification</th>
                                        <th>Duration</th>
                                        <th>Date</th>
                                        <th>Progress</th>
                                        <th>Action</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach ($content_user as $list)
                                        @php
                                            if ($list->active == 'Active') {
                                                $activecolor = 'green';
                                            } else {
                                                $activecolor = 'orange';
                                            }
                                        @endphp
                                        <tr>
                                            <td>{{ $loop->index + 1 }}</td>
                                            <td>{{ $list->content->title }}</td>
                                            <td>{{ $list->content->classification->classification_name ?? '' }}</td>
                                            <td>{{ $list->content->duration }}</td>
                                            <td>{{ $list->content->date }}</td>

                                            <td>{{ $list->progress }}</td>
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
                                    @endforeach
                                </tbody>
                            </table>
                        @else
                            <p>No content data found</p>
                        @endunless
                    </div>
                </div>
            </div>
            <!-- Card -->

        </div>
        <!-- Container Page -->
    </div>
    <!-- Page body -->

@endsection

@section('style')
    <!-- DataTables -->
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.6/css/dataTables.bootstrap5.min.css">
@endsection

@section('script')
    <script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.6/js/dataTables.bootstrap5.min.js"></script>
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
