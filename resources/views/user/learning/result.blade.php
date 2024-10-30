@extends('layouts.master')
@section('content')
    <div class="page-body">
        <div class="container">
            <div class="card">
                <div class="card-header py-2">
                    <h3 class="card-title">List Assesment</h3>
                </div>
                <div class="card-body">
                    <div id="table-default" class="table-responsive">
                        @unless (count($assessments) == 0)
                            <table class="table table-bordered table-striped table-vcenter" id="table-list-assessment">
                                <thead>
                                    <tr>
                                        <th class="text-center">No</th>
                                        <th class="text-center">Datetime</th>
                                        <th class="text-center">Title</th>
                                        <th class="text-center">Type</th>
                                        <th class="text-center">Status</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach ($assessments as $assessment)
                                        @if ($assessment->content_id != null)
                                            @php
                                                $newCollectionScore = new Illuminate\Database\Eloquent\Collection();
                                            @endphp
                                            @foreach ($assessments->where('content_id', $assessment->content_id) as $newAssessment)
                                                @php
                                                    $assessmentScore = $newAssessment->scores->where('assesment_id', $newAssessment->id)->where('user_id', Auth::user()->id)->sortByDesc('score');

                                                    $newCollectionScore = $newCollectionScore->merge($assessmentScore);
                                                @endphp
                                            @endforeach
                                        @endif

                                        <tr class="fs-5">
                                            <td class="text-center p-2">{{ $loop->iteration }}</td>
                                            @if ($assessment->timer == 'y')
                                                <td class="text-end p-2">From : {{ date('d/m/y H:i', strtotime($assessment->fromdate)) }} <br>To : {{ date('d/m/y H:i', strtotime($assessment->todate)) }}</td>
                                            @else
                                                <td class="text-center p-2">-</td>
                                            @endif
                                            <td class="p-2">
                                                @if ($assessment->type == 'content' && $assessment->content_id == $assessment->contents->first()->id)
                                                    @if ($newCollectionScore->sortByDesc('score')->first() != null && $newCollectionScore->sortByDesc('score')->first()->final == 1)
                                                        {{ $assessment->title }}
                                                    @else
                                                        <a href="{{ route('assesment.user.view', $assessment->slug) }}" class="text-reset link">{{ $assessment->title }}</a>
                                                    @endif
                                                @elseif ($assessment->type != 'content')
                                                    @if ($assessment->scores->where('assesment_id', $assessment->id)->where('user_id', Auth::user()->id)->first() != null && $assessment->scores->where('assesment_id', $assessment->id)->where('user_id', Auth::user()->id)->first()->final == 1)
                                                        {{ $assessment->title }}
                                                    @else
                                                        <a href="{{ route('assesment.user.view', $assessment->slug) }}" class="text-reset link">{{ $assessment->title }}</a>
                                                    @endif
                                                @endif
                                            </td>
                                            <td class="p-2">{{ $assessment->type }}</td>
                                            <td class="p-2">
                                                @if ($assessment->type == 'content' && $assessment->content_id == $assessment->contents->first()->id)
                                                    @if ($newCollectionScore->sortByDesc('score')->first() != null && $newCollectionScore->sortByDesc('score')->first()->final == 1)
                                                        Finished
                                                    @else
                                                        Haven't done it yet
                                                    @endif
                                                @elseif ($assessment->type != 'content')
                                                    @if ($assessment->scores->where('assesment_id', $assessment->id)->where('user_id', Auth::user()->id)->first() != null && $assessment->scores->where('assesment_id', $assessment->id)->where('user_id', Auth::user()->id)->first()->final == 1)
                                                        Finished
                                                    @else
                                                        Haven't done it yet
                                                    @endif
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

            <hr class="my-3">

            <div class="card">
                <div class="card-header py-2">
                    <h3 class="card-title">Assesment Result</h3>
                </div>
                <div class="card-body">
                    <div id="table-default" class="table-responsive">
                        @unless (count($result) == 0)
                            <table class="table table-bordered table-striped" id="MyTable">
                                <thead>
                                    <tr>
                                        <th class="text-center">No</th>
                                        <th class="text-center">Date</th>
                                        <th class="text-center">Assesment</th>
                                        <th class="text-center">Score</th>
                                        <th class="text-center">Batch</th>
                                        <th class="text-center">Result</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach ($result as $list)
                                        <tr>
                                            <td class=" p-2 text-center">{{ $loop->index + 1 }}</td>
                                            <td class=" p-2 ">{{ date("d/m/y", strtotime($list->created_at)) }}</td>
                                            <td class=" p-2 ">{{ $list->assesment->title }}</td>
                                            <td class=" p-2 text-center">{{ $list->score }}</td>
                                            <td class=" p-2 text-center">{{ $list->batch }}</td>
                                            <td class=" p-2 text-center">
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
        $("#table-list-assessment").DataTable({
            "lengthMenu": [
                [10, 25, 50, -1],
                [10, 25, 50, 'All'],
            ],
            "pageLength": 10,
            "autoWidth": false,
        });

        $(function() {
            $("#MyTable").DataTable({
                "lengthMenu": [
                    [10, 25, 50, -1],
                    [10, 25, 50, 'All'],
                ],
                "pageLength": 10,
                "autoWidth": false,
            });
        });
    </script>
@endsection
