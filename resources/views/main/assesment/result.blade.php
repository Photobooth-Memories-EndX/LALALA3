@extends('layouts.master')
@section('content')

    <div class="page-header d-print-none">
        <div class="container-xl">
            <div class="row g-2 align-items-center">
                <div class="col">
                    <h2 class="page-title">
                        Manage Assesment
                    </h2>
                </div>
                <!-- Page title actions -->
                <div class="col-auto ms-auto d-print-none">
                    <div class="d-flex">
                        <ol class="breadcrumb breadcrumb-arrows" aria-label="breadcrumbs">
                            <li class="breadcrumb-item"><a href="#">Manage</a></li>
                            <li class="breadcrumb-item"><a href="{{ route('assesment.index') }}">Assesment</a></li>
                            <li class="breadcrumb-item active" aria-current="page"><a href="#">Result</a></li>
                        </ol>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Page body -->
    <div class="page-body">
        <!-- Container Page -->
        <div class="container">

            {{-- alert component   --}}
            <x-alert type='success' />
            <x-alert type='warning' />

            <!-- Card -->
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">Assesment Result: {{ $assesment->title }}</h3>
                    <div class="card-actions row">
                        <!-- Page title actions -->
                        <div class="col-auto ms-auto d-print-none">
                            <div class="d-flex">
                                <form name="form1" id="form1" method="POST" action="{{ route('assesment.result.download') }}">
                                    @csrf
                                    <input type="hidden" name="assessment_id" id="assessment_id" value="{{ $assesment->id }}">
                                    <input type="hidden" name="slug" id="slug" value="{{ $assesment->slug }}">
                                    <x-button class="btn-icon btn-green">
                                        <svg xmlns="http://www.w3.org/2000/svg" class="icon icon-tabler icon-tabler-download" width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round">
                                            <path stroke="none" d="M0 0h24v24H0z" fill="none"></path>
                                            <path d="M4 17v2a2 2 0 0 0 2 2h12a2 2 0 0 0 2 -2v-2"></path>
                                            <path d="M7 11l5 5l5 -5"></path>
                                            <path d="M12 4l0 12"></path>
                                        </svg>
                                    </x-button>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="card-body">
                    <div id="table-default" class="table-responsive">
                        @unless (count($assesment_result) == 0)
                            <table class="table table-bordered table-striped" id="MyTable">
                                <thead>
                                    <tr>
                                        <th>No</th>
                                        <th>ID</th>
                                        <th>Name</th>
                                        <th>Team</th>
                                        <th>Department</th>
                                        <th>Score</th>
                                        <th>Batch</th>
                                        <th>Result</th>
                                        <th>Date</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach ($assesment_result as $list)
                                        <tr>
                                            <td>{{ $loop->index + 1 }}</td>
                                            <td>{{ $list->user->id_emp }}</td>
                                            <td>{{ $list->user->name }}</td>
                                            <td>{{ $list->user->team }}</td>
                                            <td>{{ $list->user->department }}</td>
                                            <td class="text-center">{{ $list->score }}</td>
                                            <td class="text-center">{{ $list->batch }}</td>
                                            <td class="text-center">
                                                <a href="#" class="wrongcheck" title="click to check wrong answers" data-url="{{ route('assesment.wrong', ['slug' => $assesment->slug, 'id' => $list->id]) }}">
                                                @if ($list->pass)
                                                    <span class="badge bg-green">Pass</span>
                                                @else
                                                    <span class="badge bg-red">Failed</span>
                                                @endif
                                                </a>
                                            </td>
                                            <td>{{ date("d/m/y", strtotime($list->created_at)) }}</td>
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

    <!-- Modal Check Wrong Answers -->
    <div class="modal modal-blur fade" id="modal-answer" role="dialog" aria-hidden="true">
        <div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable" role="document">
            <div class="modal-content">

                <div class="modal-header">
                    <h5 class="modal-title">Wrong Answer List</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="d-none modal-blur" id="overlay">
                        <div class="text-center mb-3">
                            <div class="text-muted mb-3">Getting data</div>
                            <div class="progress">
                                <div class="progress-bar progress-bar-indeterminate"></div>
                            </div>
                        </div>
                    </div>
                    <div id="result"></div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn" data-bs-dismiss="modal">Close</button>
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

    <script>
        //modal wrong answers
        $(document).on('click', '.wrongcheck', function(e) {
            e.preventDefault();
            $("#overlay").removeClass('d-none');
            $('#modal-answer').modal('show');
            var col = '';
            $.ajax({
                type: "GET",
                url: $(this).data('url'),
                dataType: "json",
                success: function(response) {
                    if (response.status == 404) {
                        alert(response.message);
                        $('#modal-questions').modal('hide');
                    } else {
                        //$('#title').text(response.assesment.title);
                        $.each(response.wrong_answer, function(i, item) {

                            col = col + '<div class="row border-bottom mb-2"><div class="col-md-8">'+item.question+'</div>'+'<div class="col-md-2 text-primary">'+item.answer+'</div>'+'<div class="col-md-2 text-danger">'+item.user_answer+'</div></div>';

                        });
                        $('#result').html(col);

                        $("#overlay").addClass('d-none');
                    }
                }
            });
            //$("#overlay").addClass('d-none');
        });
    </script>
@endsection
