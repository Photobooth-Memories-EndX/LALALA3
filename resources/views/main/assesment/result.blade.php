@extends('layouts.sidebar-master')

@section('content')
    <div class="page-header d-print-none mb-4">
        <div class="container-xl">
            <div class="row align-items-center">
                <div class="col">
                    <div class="page-pretitle text-muted">
                        Learning Management System
                    </div>

                    <h2 class="page-title fw-bold">
                        <i class="fas fa-chart-bar me-2 text-primary"></i>
                        Manage Assessment
                    </h2>
                </div>

                <div class="col-auto">
                    <ol class="breadcrumb breadcrumb-arrows mb-0" aria-label="breadcrumbs">
                        <li class="breadcrumb-item">
                            <a href="#">Manage</a>
                        </li>
                        <li class="breadcrumb-item">
                            <a href="{{ route('assesment.index') }}">Assessment</a>
                        </li>
                        <li class="breadcrumb-item active" aria-current="page">
                            Result
                        </li>
                    </ol>
                </div>
            </div>
        </div>
    </div>

    <div class="page-body">
        <div class="container-xl">

            <x-alert type='success' />
            <x-alert type='warning' />

            <div class="card border-0 shadow-sm">
                <div class="card-header bg-primary-lt">
                    <div class="row align-items-center w-100">
                        <div class="col">
                            <h3 class="card-title fw-bold mb-1">
                                <i class="fas fa-clipboard-check me-2 text-primary"></i>
                                Assessment Result
                            </h3>

                            <div class="text-muted small">
                                {{ $assesment->title }}
                            </div>
                        </div>

                        <div class="col-auto">
                            <form name="form1" id="form1" method="POST" action="{{ route('assesment.result.download') }}">
                                @csrf

                                <input type="hidden" name="assessment_id" id="assessment_id" value="{{ $assesment->id }}">
                                <input type="hidden" name="slug" id="slug" value="{{ $assesment->slug }}">

                                <x-button class="btn btn-success">
                                    <i class="fas fa-download me-2"></i>
                                    Download
                                </x-button>
                            </form>
                        </div>
                    </div>
                </div>

                <div class="card-body">
                    <div id="table-default" class="table-responsive">

                        @unless (count($assesment_result) == 0)

                            <form action="{{ route('assesment.result', $assesment) }}" method="get" class="mb-4" id="form-filter-data-assessment-result">
                                <div class="card bg-dark-lt border-0">
                                    <div class="card-body">
                                        <div class="row align-items-end">

                                            <div class="col-md-3">
                                                <label class="form-label">
                                                    Show Entries
                                                </label>

                                                <select name="show_entries" id="show-entries" class="form-select form-select-sm">
                                                    <option value="10" {{ $entries == 10 ? 'selected' : '' }}>10 Entries</option>
                                                    <option value="25" {{ $entries == 25 ? 'selected' : '' }}>25 Entries</option>
                                                    <option value="50" {{ $entries == 50 ? 'selected' : '' }}>50 Entries</option>
                                                    <option value="100" {{ $entries == 100 ? 'selected' : '' }}>100 Entries</option>
                                                </select>
                                            </div>

                                            <div class="col-md-6 ms-auto">
                                                <label class="form-label">
                                                    Search User
                                                </label>

                                                <div class="input-group">
                                                    <input
                                                        type="text"
                                                        name="search_user"
                                                        class="form-control form-control-sm"
                                                        placeholder="Search user by name, ID, team, or department"
                                                        aria-label="Search User"
                                                        aria-describedby="button-addon2"
                                                        value="{{ $search }}">

                                                    <button class="btn btn-primary btn-sm" type="submit" id="button-addon2">
                                                        <i class="fas fa-search me-1"></i>
                                                        Search
                                                    </button>
                                                </div>
                                            </div>

                                        </div>
                                    </div>
                                </div>
                            </form>

                            <div class="table-responsive">
                                <table class="table table-vcenter card-table table-hover">
                                    <thead>
                                        <tr class="text-center">
                                            <th>No</th>
                                            <th>ID</th>
                                            <th class="text-start">Name</th>
                                            <th class="text-start">Team</th>
                                            <th class="text-start">Department</th>
                                            <th>Score</th>
                                            <th>Batch</th>
                                            <th>Result</th>
                                            <th style="width: 150px;">Start Date Time</th>
                                            <th style="width: 150px;">Finish Date Time</th>
                                        </tr>
                                    </thead>

                                    <tbody>
                                        @foreach ($assesment_result as $list)
                                            <tr>
                                                <td class="text-center text-muted">
                                                    {{ ++$no }}
                                                </td>

                                                <td class="text-center">
                                                    <span class="badge bg-secondary-lt text-secondary">
                                                        {{ $list->user->id_emp }}
                                                    </span>
                                                </td>

                                                <td>
                                                    <div class="fw-semibold">
                                                        {{ $list->user->name }}
                                                    </div>
                                                </td>

                                                <td>
                                                    {{ $list->user->team }}
                                                </td>

                                                <td>
                                                    {{ $list->user->department }}
                                                </td>

                                                <td class="text-center">
                                                    <span class="badge bg-blue-lt text-blue">
                                                        {{ $list->score }}
                                                    </span>
                                                </td>

                                                <td class="text-center">
                                                    {{ $list->batch }}
                                                </td>

                                                <td class="text-center">
                                                    <a
                                                        href="#"
                                                        class="wrongcheck"
                                                        data-bs-toggle="tooltip"
                                                        data-bs-placement="top"
                                                        title="click to check wrong answers"
                                                        data-url="{{ route('assesment.wrong', ['slug' => $assesment->slug, 'id' => $list->id]) }}">

                                                        @if ($list->pass)
                                                            <span class="badge bg-green-lt text-green">
                                                                <i class="fas fa-check-circle me-1"></i>
                                                                Pass
                                                            </span>
                                                        @else
                                                            <span class="badge bg-red-lt text-red">
                                                                <i class="fas fa-times-circle me-1"></i>
                                                                Failed
                                                            </span>
                                                        @endif
                                                    </a>
                                                </td>

                                                <td class="text-end text-muted">
                                                    {{ date("d/m/y H:i:s", strtotime($list->created_at)) }}
                                                </td>

                                                <td class="text-end text-muted">
                                                    {{ date("d/m/y H:i:s", strtotime($list->updated_at)) }}
                                                </td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>

                            @if ($assesment_result->total() <= $entries)
                                <x-page.pagination-first count='{{ $assesment_result->count() }}' />
                            @else
                                <div class="mt-3">
                                    {{ $assesment_result->links('pagination::bootstrap-5') }}
                                </div>
                            @endif

                        @else
                            <div class="empty py-5">
                                <div class="empty-icon">
                                    <i class="fas fa-chart-bar fa-3x text-muted"></i>
                                </div>

                                <p class="empty-title">
                                    No assessment score data found
                                </p>

                                <p class="empty-subtitle text-muted">
                                    There is no user result data available for this assessment yet.
                                </p>
                            </div>
                        @endunless

                    </div>
                </div>
            </div>

        </div>
    </div>

    {{-- Modal Check Wrong Answers --}}
    <div class="modal modal-blur fade" id="modal-answer" role="dialog" aria-hidden="true">
        <div class="modal-dialog modal-xl modal-dialog-centered modal-dialog-scrollable" role="document">
            <div class="modal-content border-0 shadow-lg">

                <div class="modal-header bg-primary-lt">
                    <div>
                        <h5 class="modal-title fw-bold mb-1">
                            <i class="fas fa-list-check me-2 text-primary"></i>
                            Answer List <span id="amount-of-answer"></span>
                        </h5>

                        <div class="text-muted small">
                            Review correct answers and user answers.
                        </div>
                    </div>

                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>

                <div class="modal-body">

                    <div class="d-none modal-blur" id="overlay">
                        <div class="text-center mb-3">
                            <div class="text-muted mb-3">
                                Getting data
                            </div>

                            <div class="progress">
                                <div class="progress-bar progress-bar-indeterminate"></div>
                            </div>
                        </div>
                    </div>

                    <div class="table-responsive">
                        <table class="table table-vcenter card-table table-hover" style="width: 100%">
                            <thead>
                                <tr class="text-center">
                                    <th class="text-start">Question</th>
                                    <th>Correct Answer</th>
                                    <th>User Answer</th>
                                    <th>Status</th>
                                </tr>
                            </thead>

                            <tbody id="result">
                            </tbody>
                        </table>
                    </div>

                </div>

                <div class="modal-footer border-0">
                    <button type="button" class="btn" data-bs-dismiss="modal">
                        <i class="fas fa-times me-2"></i>
                        Close
                    </button>
                </div>

            </div>
        </div>
    </div>
@endsection

@section('style')
    {{-- DataTables --}}
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
        // modal wrong answers
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
                        $('#amount-of-answer').html(response.amountOfAnswer);

                        $.each(response.wrong_answer, function(i, item) {
                            let badge = '';

                            if (item.answer == item.user_answer) {
                                badge = '<span class="badge bg-success">Correct</span>';
                            } else {
                                badge = '<span class="badge bg-danger">Wrong</span>';
                            }

                            col = col + '<tr><td class="p-2">' + item.question + '</td><td class="p-2">' + item.answer + '</td><td class="p-2">' + item.user_answer + '</td><td class="text-center p-2">' + badge + '</td></tr>';
                        });

                        $('#result').html(col);

                        $("#overlay").addClass('d-none');
                    }
                }
            });
        });
    </script>
@endsection