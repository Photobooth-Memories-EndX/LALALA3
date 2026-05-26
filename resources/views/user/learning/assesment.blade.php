@extends('layouts.sidebar-master')

@section('content')

<x-learning.dashboard-shell 
    active="assessment"
    title="Learning Dashboard"
    subtitle="Daftar assessment dan hasil evaluasi pembelajaran Anda."
>
    @php
        $assessmentItems = collect($assessments ?? []);
        $resultItems = collect($result ?? []);

        $totalAssessment = $assessmentItems->count();
        $completedAssessment = $resultItems->where('final', 1)->count();
        $passedAssessment = $resultItems->where('pass', 1)->count();
        $notStartedAssessment = max($totalAssessment - $completedAssessment, 0);
    @endphp

    <div class="row g-3 mb-3">
        <div class="col-sm-6 col-lg-3">
            <div class="card border-0 shadow-sm rounded-4">
                <div class="card-body">
                    <div class="text-muted">Total Assessment</div>
                    <div class="h2 mb-0">{{ $totalAssessment }}</div>
                </div>
            </div>
        </div>

        <div class="col-sm-6 col-lg-3">
            <div class="card border-0 shadow-sm rounded-4">
                <div class="card-body">
                    <div class="text-muted">Completed</div>
                    <div class="h2 mb-0 text-success">{{ $completedAssessment }}</div>
                </div>
            </div>
        </div>

        <div class="col-sm-6 col-lg-3">
            <div class="card border-0 shadow-sm rounded-4">
                <div class="card-body">
                    <div class="text-muted">Passed</div>
                    <div class="h2 mb-0 text-primary">{{ $passedAssessment }}</div>
                </div>
            </div>
        </div>

        <div class="col-sm-6 col-lg-3">
            <div class="card border-0 shadow-sm rounded-4">
                <div class="card-body">
                    <div class="text-muted">Not Started</div>
                    <div class="h2 mb-0 text-warning">{{ $notStartedAssessment }}</div>
                </div>
            </div>
        </div>
    </div>

    <div class="card border-0 shadow-sm rounded-4 mb-3">
        <div class="card-body">
            <div class="row g-2 align-items-center">
                <div class="col">
                    <h3 class="card-title mb-1">Assessment List</h3>
                    <div class="text-muted small">
                        Pilih assessment yang tersedia dan lihat status pengerjaan Anda.
                    </div>
                </div>

                <div class="col-md-4">
                    <input type="text" id="assessmentSearch" class="form-control" placeholder="Cari assessment">
                </div>
            </div>
        </div>

        <div class="table-responsive">
            @if ($totalAssessment > 0)
                <table class="table table-vcenter card-table" id="table-list-assessment">
                    <thead>
                        <tr>
                            <th>No</th>
                            <th>Assessment</th>
                            <th>Datetime</th>
                            <th>Type</th>
                            <th>Batch</th>
                            <th>Passing Grade</th>
                            <th>Status</th>
                            <th class="text-end">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($assessments as $assessment)
                            @php
                                $bestScore = null;
                                $finalResult = null;

                                if (($assessment->scores ?? null) != null) {
                                    $finalResult = $assessment->scores
                                        ->where('user_id', Auth::user()->id)
                                        ->where('final', 1)
                                        ->sortByDesc('score')
                                        ->first();

                                    $bestScore = $finalResult->score ?? null;
                                }

                                $isCompleted = $finalResult != null;
                                $isPassed = $finalResult && $finalResult->pass;

                                $typeLabel = $assessment->type == 'trainingplan'
                                    ? 'Playlist'
                                    : ucwords(str_replace('_', ' ', $assessment->type ?? '-'));
                            @endphp

                            <tr>
                                <td>{{ $loop->iteration }}</td>

                                <td>
                                    <div class="d-flex align-items-center gap-2">
                                        <span class="avatar avatar-sm bg-blue-lt">
                                            {{ strtoupper(substr($assessment->title ?? 'A', 0, 1)) }}
                                        </span>

                                        <div>
                                            <div class="fw-semibold">
                                                {{ $assessment->title ?? '-' }}
                                            </div>

                                            @if (($assessment->type ?? '') == 'trainingplan')
                                                <div class="small text-muted">
                                                    {{ $assessment->trainingplan->title ?? 'Playlist Assessment' }}
                                                </div>
                                            @elseif (($assessment->type ?? '') == 'content')
                                                <div class="small text-muted">
                                                    Content Assessment
                                                </div>
                                            @else
                                                <div class="small text-muted">
                                                    Global Assessment
                                                </div>
                                            @endif
                                        </div>
                                    </div>
                                </td>

                                <td>
                                    @if (($assessment->timer ?? 'n') == 'y')
                                        <div class="small">
                                            <span class="text-muted">From:</span>
                                            {{ $assessment->fromdate ? date('d/m/y H:i', strtotime($assessment->fromdate)) : '-' }}
                                        </div>
                                        <div class="small">
                                            <span class="text-muted">To:</span>
                                            {{ $assessment->todate ? date('d/m/y H:i', strtotime($assessment->todate)) : '-' }}
                                        </div>
                                    @else
                                        <span class="text-muted">-</span>
                                    @endif
                                </td>

                                <td>
                                    <span class="badge bg-blue-lt text-blue">
                                        {{ $typeLabel }}
                                    </span>
                                </td>

                                <td>
                                    {{ $assessment->batch ?? '-' }}
                                </td>

                                <td>
                                    {{ $assessment->passing_grade ?? '-' }}
                                </td>

                                <td>
                                    @if ($isCompleted)
                                        @if ($isPassed)
                                            <span class="badge bg-green-lt text-green">Completed</span>
                                        @else
                                            <span class="badge bg-red-lt text-red">Failed</span>
                                        @endif

                                        <div class="small text-muted mt-1">
                                            Score: {{ $bestScore ?? '-' }}
                                        </div>
                                    @else
                                        <span class="badge bg-orange-lt text-orange">Not Started</span>
                                    @endif
                                </td>

                                <td class="text-end">
                                    @if ($isCompleted && $isPassed)
                                        <button type="button" class="btn btn-sm btn-white" disabled>
                                            Completed
                                        </button>
                                    @else
                                        @if (!empty($assessment->slug))
                                            <a href="{{ route('assesment.user.view', $assessment->slug) }}" class="btn btn-sm btn-primary">
                                                {{ $isCompleted ? 'Remedial' : 'Kerjakan' }}
                                            </a>
                                        @else
                                            <button type="button" class="btn btn-sm btn-secondary" disabled>
                                                Unavailable
                                            </button>
                                        @endif
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            @else
                <div class="empty py-5">
                    <div class="empty-title">Belum ada assessment</div>
                    <div class="empty-subtitle text-muted">
                        Assessment yang tersedia akan muncul di halaman ini.
                    </div>
                </div>
            @endif
        </div>
    </div>

    <div class="card border-0 shadow-sm rounded-4">
        <div class="card-body">
            <div class="row g-2 align-items-center">
                <div class="col">
                    <h3 class="card-title mb-1">Assessment Result</h3>
                    <div class="text-muted small">
                        Riwayat hasil assessment yang sudah Anda kerjakan.
                    </div>
                </div>

                <div class="col-md-4">
                    <input type="text" id="resultSearch" class="form-control" placeholder="Cari hasil assessment">
                </div>
            </div>
        </div>

        <div class="table-responsive">
            @if ($resultItems->count() > 0)
                <table class="table table-vcenter card-table" id="MyTable">
                    <thead>
                        <tr>
                            <th>No</th>
                            <th>Datetime</th>
                            <th>Assessment</th>
                            <th>Type</th>
                            <th>Score</th>
                            <th>Batch</th>
                            <th>Result</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($result as $list)
                            @php
                                $assessment = $list->assesment ?? null;
                                $typeLabel = ($assessment->type ?? '') == 'trainingplan'
                                    ? 'Playlist'
                                    : ucwords(str_replace('_', ' ', $assessment->type ?? '-'));
                            @endphp

                            <tr>
                                <td>{{ $loop->iteration }}</td>

                                <td>
                                    {{ $list->created_at ? date("d/m/y H:i:s", strtotime($list->created_at)) : '-' }}
                                </td>

                                <td>
                                    <div class="fw-semibold">
                                        {{ $assessment->title ?? '-' }}
                                    </div>

                                    @if (($assessment->type ?? '') == 'trainingplan')
                                        <div class="small text-muted">
                                            {{ $assessment->trainingplan->title ?? 'Playlist Assessment' }}
                                        </div>
                                    @endif
                                </td>

                                <td>
                                    <span class="badge bg-blue-lt text-blue">
                                        {{ $typeLabel }}
                                    </span>
                                </td>

                                <td>
                                    <span class="fw-bold">
                                        {{ $list->score ?? '-' }}
                                    </span>
                                </td>

                                <td>
                                    {{ $list->batch ?? '-' }}
                                </td>

                                <td>
                                    @if ($list->pass)
                                        <span class="badge bg-green-lt text-green">Pass</span>
                                    @else
                                        <span class="badge bg-red-lt text-red">Failed</span>
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            @else
                <div class="empty py-5">
                    <div class="empty-title">Belum ada hasil assessment</div>
                    <div class="empty-subtitle text-muted">
                        Hasil assessment Anda akan tampil setelah submit jawaban.
                    </div>
                </div>
            @endif
        </div>
    </div>

</x-learning.dashboard-shell>

@endsection

@section('style')
    <link rel="stylesheet" href="{{ asset('css/dataTables.bootstrap5.min.css') }}">
    <style>
        .rounded-4 {
            border-radius: 1rem !important;
        }
    </style>
@endsection

@section('script')
    <script src="{{ asset('js/jquery.dataTables.min.js') }}"></script>
    <script src="{{ asset('js/dataTables.bootstrap5.min.js') }}"></script>

    <script>
        $(function() {
            if ($('#table-list-assessment').length) {
                const assessmentTable = $("#table-list-assessment").DataTable({
                    ordering: false,
                    pageLength: 10,
                    autoWidth: false,
                    dom: 'rt<"d-flex justify-content-between align-items-center mt-3"ip>',
                });

                $('#assessmentSearch').on('keyup', function () {
                    assessmentTable.search(this.value).draw();
                });
            }

            if ($('#MyTable').length) {
                const resultTable = $("#MyTable").DataTable({
                    ordering: false,
                    pageLength: 10,
                    autoWidth: false,
                    dom: 'rt<"d-flex justify-content-between align-items-center mt-3"ip>',
                });

                $('#resultSearch').on('keyup', function () {
                    resultTable.search(this.value).draw();
                });
            }
        });
    </script>
@endsection