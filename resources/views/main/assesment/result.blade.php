@extends('layouts.master')

@section('content')
    <div class="page-header d-print-none">
        <div class="container-xl">
            <div class="row g-2 align-items-center">
                <div class="col">
                    <h2 class="page-title">Manage Assesment</h2>
                </div>
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

    <div class="page-body">
        <div class="container">

            <x-alert type='success' />
            <x-alert type='warning' />

            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">Assesment Result: {{ $assesment->title }}</h3>
                    <div class="card-actions row">
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
                            <form action="{{ route('assesment.result', $assesment) }}" method="get" class="mt-1" id="form-filter-data-assessment-result">
                                <div class="row justify-content-between">
                                    <div class="col-md-3">
                                        <div class="d-flex align-items-center">
                                            <label class="me-2">Show</label>
                                            <select name="show_entries" id="show-entries" class="form-select form-select-sm" style="width: 75px">
                                                <option value="10" {{ $entries == 10 ? 'selected' : '' }}>10</option>
                                                <option value="25" {{ $entries == 25 ? 'selected' : '' }}>25</option>
                                                <option value="50" {{ $entries == 50 ? 'selected' : '' }}>50</option>
                                                <option value="100" {{ $entries == 100 ? 'selected' : '' }}>100</option>
                                            </select>
                                            <label class="ms-2">Entries</label>
                                        </div>
                                    </div>
                                    <div class="col-md-4">
                                        <div class="input-group mb-3">
                                            <input type="text" name="search_user" class="form-control form-control-sm" placeholder="Search User" aria-label="Search User" aria-describedby="button-addon2" value="{{ $search }}">
                                            <button class="btn btn-outline-primary" type="submit" id="button-addon2">
                                                <svg  xmlns="http://www.w3.org/2000/svg" class="mx-0"  width="9"  height="9"  viewBox="0 0 24 24"  fill="none"  stroke="currentColor"  stroke-width="2"  stroke-linecap="round"  stroke-linejoin="round"  class="icon icon-tabler icons-tabler-outline icon-tabler-user-search">
                                                    <path stroke="none" d="M0 0h24v24H0z" fill="none"/>
                                                    <path d="M8 7a4 4 0 1 0 8 0a4 4 0 0 0 -8 0" />
                                                    <path d="M6 21v-2a4 4 0 0 1 4 -4h1.5" />
                                                    <path d="M18 18m-3 0a3 3 0 1 0 6 0a3 3 0 1 0 -6 0" />
                                                    <path d="M20.2 20.2l1.8 1.8" />
                                                </svg>
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            </form>

                            <table class="table table-bordered table-striped">
                                <thead>
                                    <tr class="text-center">
                                        <th>No</th>
                                        <th>ID</th>
                                        <th>Name</th>
                                        <th>Team</th>
                                        <th>Department</th>
                                        <th>Score</th>
                                        <th>Batch</th>
                                        <th>Result</th>
                                        <th style="width: 135px;">Start Date Time</th>
                                        <th style="width: 135px;">Finish Date Time</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach ($assesment_result as $list)
                                        <tr class="fs-5">
                                            <td class="p-2 text-center">{{ ++$no }}</td>
                                            <td class="p-2">{{ $list->user->id_emp }}</td>
                                            <td class="p-2">{{ $list->user->name }}</td>
                                            <td class="p-2">{{ $list->user->team }}</td>
                                            <td class="p-2">{{ $list->user->department }}</td>
                                            <td class="text-center p-2">{{ $list->score }}</td>
                                            <td class="text-center p-2">{{ $list->batch }}</td>
                                            <td class="text-center p-2">
                                                <a href="#" class="wrongcheck" data-bs-toggle="tooltip" data-bs-placement="top" title="click to check wrong answers" data-url="{{ route('assesment.wrong', ['slug' => $assesment->slug, 'id' => $list->id]) }}">
                                                    @if ($list->pass)
                                                        <span class="badge bg-green">Pass</span>
                                                    @else
                                                        <span class="badge bg-red">Failed</span>
                                                    @endif
                                                </a>
                                            </td>
                                            <td class="text-end p-2">{{ date("d/m/y H:i:s", strtotime($list->created_at)) }}</td>
                                            <td class="text-end p-2">{{ date("d/m/y H:i:s", strtotime($list->updated_at)) }}</td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>

                            @if ($assesment_result->total() <= $entries)
                                <x-page.pagination-first count='{{ $assesment_result->count() }}' />
                            @else
                                <div class="mt-3">
                                    {{ $assesment_result->links('pagination::bootstrap-5') }}
                                </div>
                            @endif
                        @else
                            <p>No assesment score data found</p>
                        @endunless
                    </div>
                </div>
            </div>
        </div>
    </div>

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
        $('#show-entries').on('change', function() {
            $('#form-filter-data-assessment-result').submit();
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
                        $.each(response.wrong_answer, function(i, item) {
                            col = col + '<div class="row border-bottom mb-2"><div class="col-md-8">'+item.question+'</div>'+'<div class="col-md-2 text-primary">'+item.answer+'</div>'+'<div class="col-md-2 text-danger">'+item.user_answer+'</div></div>';
                        });
                        $('#result').html(col);

                        $("#overlay").addClass('d-none');
                    }
                }
            });
        });
    </script>
@endsection
