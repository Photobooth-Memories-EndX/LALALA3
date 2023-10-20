@extends('layouts.master')
@section('content')
    <div class="page-header d-print-none">
        <div class="container-xl">
            <div class="row g-2 align-items-center">
                <div class="col">
                    <h2 class="page-title">
                        Manage Learning
                    </h2>
                </div>
                <!-- Page title actions -->
                <div class="col-auto ms-auto d-print-none">
                    <div class="d-flex">
                        <ol class="breadcrumb breadcrumb-arrows" aria-label="breadcrumbs">
                            <li class="breadcrumb-item"><a href="#">Manage</a></li>
                            <li class="breadcrumb-item active" aria-current="page"><a href="#">Learning</a></li>
                        </ol>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Page body -->
    <div class="page-body">
        <!-- Container Page -->
        <div class="container-xl">
            <div class="row row-cards">
                <div class="col-md-6 col-lg-6">
                    <div class="card">
                        <div class="card-header">
                            <h3 class="card-title"><a href="{{ route('learning.manage.all') }}">Total Learning</a></h3>
                        </div>
                        <div class="card-body">
                            {{ $ongoing }} Ongoing<br>
                            {{ $finish }} Finished
                        </div>
                    </div>
                </div>
                {{-- <div class="col-md-6 col-lg-3">
                    <div class="card">
                        <div class="card-header">
                            <h3 class="card-title">Finished</h3>
                        </div>
                        <div class="card-body">{{ $finish }}</div>
                    </div>
                </div> --}}
            </div>


        </div>
        <br>
        <br>

        <div class="container-xl">
            <div class="row g-2 align-items-center">
                <div class="col">
                    <h2 class="page-title">
                        Classification
                    </h2>
                </div>
            </div>
            <br>
            <br>
            <div class="row row-cards">
                @unless (count($classification) == 0)

                    <div class="card">
                        <div class="card-header">
                            <h3 class="card-title">List Classification</h3>
                            <div class="card-actions">

                            </div>
                        </div>
                        <div class="card-body">
                            <div id="table-default" class="table-responsive">
                                @unless (count($classification) == 0)
                                    <table class="table table-bordered table-striped" id="MyTable">
                                        <thead>
                                            <tr>
                                                <th>No</th>
                                                <th>Title</th>
                                                <th style="text-align: center">Total Learning</th>
                                                <th style="text-align: center">Ongoing Learning</th>
                                                <th style="text-align: center">Finished Learning</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @foreach ($classification as $list)
                                                @unless (count($list->content) == 0)
                                                    @php
                                                        $totallearning = 0;
                                                        $totalongoing = 0;
                                                        $totalfinish = 0;
                                                    @endphp
                                                    @foreach ($list->content as $listcontent)
                                                        @php
                                                            $totallearning += count($listcontent->contentuser);

                                                        @endphp
                                                        @unless (count($listcontent->contentuser) == 0)
                                                            @foreach ($listcontent->contentuser as $listcontentuser)
                                                                @php
                                                                    if ($listcontentuser->finish == 0) {
                                                                        $totalongoing = $totalongoing + 1;
                                                                    } else {
                                                                        $totalfinish = $totalfinish + 1;
                                                                    }
                                                                @endphp
                                                            @endforeach
                                                        @else
                                                        @endunless
                                                    @endforeach
                                                @else
                                                    @php
                                                        $totallearning = '0';
                                                        $totalongoing = '0';
                                                        $totalfinish = '0';
                                                    @endphp
                                                @endunless
                                                <tr>
                                                    <td>{{ $loop->index + 1 }}</td>
                                                    <td><a href="{{ route('learning.manage.category', $list->slug) }}">{{ $list->classification_name }}</a></td>
                                                    <td style="text-align: center; font-weight: bold;">{{ $totallearning }}</td>
                                                    <td style="text-align: center; font-weight: bold;">{{ $totalongoing }}</td>
                                                    <td style="text-align: center; font-weight: bold;">{{ $totalfinish }}</td>

                                                </tr>
                                            @endforeach
                                        </tbody>
                                    </table>
                                @else
                                    <p>No news data found</p>
                                @endunless
                            </div>
                        </div>

                    </div>
                @else
                    No Classification
                @endunless
            </div>
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
