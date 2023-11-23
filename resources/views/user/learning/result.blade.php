@extends('layouts.master')
@section('content')

    <!-- Page body -->
    <div class="page-body">
        <!-- Container Page -->
        <div class="container">

            <!-- Card -->
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">Assesment Result</h3>
                </div>
                <div class="card-body">
                    <div id="table-default" class="table-responsive">
                        @unless (count($result) == 0)
                            <table class="table table-bordered table-striped" id="MyTable">
                                <thead>
                                    <tr>
                                        <th>No</th>
                                        <th>Date</th>
                                        <th>Assesment</th>
                                        <th>Score</th>
                                        <th>Batch</th>
                                        <th>Result</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach ($result as $list)
                                        <tr>
                                            <td>{{ $loop->index + 1 }}</td>
                                            <td>{{ date("d/m/y", strtotime($list->created_at)) }}</td>
                                            <td>{{ $list->assesment->title }}</td>
                                            <td class="text-center">{{ $list->score }}</td>
                                            <td class="text-center">{{ $list->batch }}</td>
                                            <td class="text-center">
                                                @if ($list->pass)
                                                    <span class="badge bg-green">Pass</span>
                                                @else
                                                    <span class="badge bg-red">Failed</span>
                                                @endif
                                            </td>

                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        @else
                            <p>No assesment score data found</p>
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
