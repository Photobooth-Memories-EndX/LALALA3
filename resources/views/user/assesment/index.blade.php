@extends('layouts.sidebar-master')

@section('content')
<div class="page-body">
    <div class="container-xl py-4">

        {{-- ASSESSMENT LIST --}}
        <div class="card border-0 shadow-sm rounded-4 mb-3 assessment-card">
            <div class="card-body">
                <h3 class="card-title mb-1">Assessment List</h3>
                <div class="text-muted small">Your Available Assessments.</div>
            </div>

            <div class="table-responsive px-3 pb-3">
                <table class="table table-vcenter card-table table-hover w-100" id="assessmentTable">
                    <thead>
                        <tr>
                            <th>No</th>
                            <th>Assessment</th>
                            <th>Datetime</th>
                            <th>Type</th>
                            <th>Batch</th>
                            <th>Passing Grade</th>
                            <th>Status</th>
                            <th class="text-end">Action</th>
                        </tr>
                    </thead>

                    <tbody>
                        @foreach (($assesment ?? collect()) as $assessment)
                            @php
                                $finalResult = null;

                                if (($assessment->scores ?? null) != null) {
                                    $finalResult = $assessment->scores
                                        ->where('user_id', Auth::id())
                                        ->where('final', 1)
                                        ->sortByDesc('score')
                                        ->first();
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
                                    <div class="fw-semibold assessment-title">
                                        {{ $assessment->title ?? '-' }}
                                    </div>
                                    <div class="text-muted small">
                                        {{ $assessment->trainingplan->title ?? 'Assessment' }}
                                    </div>
                                </td>

                                <td>
                                    @if (($assessment->timer ?? 'n') == 'y')
                                        <div class="small">
                                            From: {{ $assessment->fromdate ? date('d/m/y H:i', strtotime($assessment->fromdate)) : '-' }}
                                        </div>
                                        <div class="small">
                                            To: {{ $assessment->todate ? date('d/m/y H:i', strtotime($assessment->todate)) : '-' }}
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

                                <td>{{ $assessment->batch ?? '-' }}</td>
                                <td>{{ $assessment->passing_grade ?? '-' }}</td>

                                <td>
                                    @if ($isCompleted)
                                        @if ($isPassed)
                                            <span class="badge bg-green-lt text-green">Completed</span>
                                        @else
                                            <span class="badge bg-red-lt text-red">Failed</span>
                                        @endif

                                        <div class="small text-muted mt-1">
                                            Score: {{ $finalResult->score ?? '-' }}
                                        </div>
                                    @else
                                        <span class="badge bg-orange-lt text-orange">Not Started</span>
                                    @endif
                                </td>

                                <td class="text-end">
                                    @if ($isCompleted && $isPassed)
                                        <button class="btn btn-sm btn-outline-secondary" disabled>
                                            Completed
                                        </button>
                                    @else
                                        @if (!empty($assessment->slug))
                                            <a href="{{ route('assesment.user.view', $assessment->slug) }}"
                                               class="btn btn-sm btn-primary">
                                                {{ $isCompleted ? 'Remedial' : 'Kerjakan' }}
                                            </a>
                                        @else
                                            <button class="btn btn-sm btn-secondary" disabled>
                                                Unavailable
                                            </button>
                                        @endif
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>

        {{-- ASSESSMENT RESULT --}}
        <div class="card border-0 shadow-sm rounded-4 assessment-card">
            <div class="card-body">
                <h3 class="card-title mb-1">Assessment Result</h3>
                <div class="text-muted small">Your assessment history.</div>
            </div>

            <div class="table-responsive px-3 pb-3">
                <table class="table table-vcenter card-table table-hover w-100" id="assessmentResultTable">
                    <thead>
                        <tr>
                            <th>No</th>
                            <th>Datetime</th>
                            <th>Assessment</th>
                            <th>Score</th>
                            <th>Batch</th>
                            <th>Result</th>
                        </tr>
                    </thead>

                    <tbody>
                        @foreach (($result ?? collect()) as $list)
                            <tr>
                                <td>{{ $loop->iteration }}</td>

                                <td>
                                    {{ $list->created_at ? date("d/m/y H:i:s", strtotime($list->created_at)) : '-' }}
                                </td>

                                <td>
                                    <div class="fw-semibold assessment-title">
                                        {{ $list->assesment->title ?? '-' }}
                                    </div>
                                </td>

                                <td>{{ $list->score ?? '-' }}</td>
                                <td>{{ $list->batch ?? '-' }}</td>

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
            </div>
        </div>

    </div>
</div>
@endsection

@section('style')
<link rel="stylesheet" href="{{ asset('css/dataTables.bootstrap5.min.css') }}">
<style>
    /* ── Card shell ── */
    .assessment-card {
        overflow: hidden;
        border-radius: 16px !important;
    }

    .assessment-card .table {
        margin-bottom: 0;
        font-size: 13px;
    }

    .assessment-card .table thead th {
        font-size: 11px;
        text-transform: uppercase;
        letter-spacing: .03em;
        color: #64748b;
        background: #f8fafc;
        white-space: nowrap;
        border-bottom: 1px solid #e5e7eb;
        padding: 10px 12px;
    }

    .assessment-card .table tbody td {
        vertical-align: middle;
        padding: 9px 12px;
    }

    .assessment-title {
        max-width: 380px;
        line-height: 1.35;
    }

    /* ── DataTables wrapper layout ── */
    .dataTables_wrapper {
        padding: .75rem 1rem 1rem;
    }

    .dataTables_wrapper .dataTables_length,
    .dataTables_wrapper .dataTables_filter {
        margin-bottom: .6rem;
    }

    .dataTables_wrapper .dataTables_length select,
    .dataTables_wrapper .dataTables_filter input {
        border: 1px solid #dbe3ef;
        border-radius: 8px;
        padding: .3rem .55rem;
        font-size: 13px;
        outline: none;
    }

    .dataTables_wrapper .dataTables_filter input {
        margin-left: .4rem;
    }

    .dataTables_wrapper .dataTables_info {
        font-size: 12px;
        color: #64748b;
        padding-top: .6rem;
    }

    .dataTables_wrapper .dataTables_paginate {
        padding-top: .6rem;
    }

    /* ── Pagination buttons ── */
    .dataTables_wrapper .dataTables_paginate .page-link {
        border-radius: 8px !important;
        font-size: 13px;
        color: #2563eb;
    }

    .dataTables_wrapper .dataTables_paginate .page-item.active .page-link {
        background-color: #2563eb;
        border-color: #2563eb;
        color: #fff;
    }

    /* ── Dark mode ── */
    [data-bs-theme="dark"] .assessment-card,
    body.theme-dark .assessment-card {
        background: #162033;
        border: 1px solid rgba(148,163,184,.16) !important;
    }

    [data-bs-theme="dark"] .assessment-card .table thead th,
    body.theme-dark .assessment-card .table thead th {
        background: #111827;
        color: #94a3b8;
        border-color: rgba(148,163,184,.16);
    }

    [data-bs-theme="dark"] .assessment-card .table tbody td,
    body.theme-dark .assessment-card .table tbody td {
        color: #e5e7eb;
        border-color: rgba(148,163,184,.12);
    }

    [data-bs-theme="dark"] .dataTables_wrapper .dataTables_length select,
    [data-bs-theme="dark"] .dataTables_wrapper .dataTables_filter input,
    body.theme-dark .dataTables_wrapper .dataTables_length select,
    body.theme-dark .dataTables_wrapper .dataTables_filter input {
        background: #0f172a;
        color: #e5e7eb;
        border-color: rgba(148,163,184,.25);
    }

    [data-bs-theme="dark"] .dataTables_wrapper .dataTables_info,
    body.theme-dark .dataTables_wrapper .dataTables_info {
        color: #94a3b8;
    }

    [data-bs-theme="dark"] .dataTables_wrapper .dataTables_paginate .page-link,
    body.theme-dark .dataTables_wrapper .dataTables_paginate .page-link {
        background: #162033;
        border-color: rgba(148,163,184,.2);
        color: #93c5fd;
    }

    [data-bs-theme="dark"] .dataTables_wrapper .dataTables_paginate .page-item.active .page-link,
    body.theme-dark .dataTables_wrapper .dataTables_paginate .page-item.active .page-link {
        background: #2563eb;
        border-color: #2563eb;
        color: #fff;
    }

    [data-bs-theme="dark"] .dataTables_wrapper .dataTables_paginate .page-item.disabled .page-link,
    body.theme-dark .dataTables_wrapper .dataTables_paginate .page-item.disabled .page-link {
        background: #0f172a;
        color: #475569;
    }
</style>
@endsection

@section('script')
<script src="{{ asset('js/jquery.dataTables.min.js') }}"></script>
<script src="{{ asset('js/dataTables.bootstrap5.min.js') }}"></script>
<script>
    $(document).ready(function () {

        if (!$.fn.DataTable.isDataTable('#assessmentTable')) {
            $('#assessmentTable').DataTable({
                pageLength: 5,
                lengthMenu: [[5, 10, 25, 50], [5, 10, 25, 50]],
                ordering: true,
                searching: true,
                paging: true,
                info: true,
                autoWidth: false,
                order: [[0, 'asc']],
                language: {
                    search: 'Search:',
                    lengthMenu: 'Show _MENU_ entries',
                    info: 'Showing _START_ to _END_ of _TOTAL_ entries',
                    infoEmpty: 'Showing 0 to 0 of 0 entries',
                    zeroRecords: 'No matching assessment found',
                    emptyTable: 'No assessment available',
                    paginate: { previous: '&laquo;', next: '&raquo;' }
                }
            });
        }

        if (!$.fn.DataTable.isDataTable('#assessmentResultTable')) {
            $('#assessmentResultTable').DataTable({
                pageLength: 5,
                lengthMenu: [[5, 10, 25, 50], [5, 10, 25, 50]],
                ordering: true,
                searching: true,
                paging: true,
                info: true,
                autoWidth: false,
                order: [[0, 'desc']],
                language: {
                    search: 'Search:',
                    lengthMenu: 'Show _MENU_ entries',
                    info: 'Showing _START_ to _END_ of _TOTAL_ entries',
                    infoEmpty: 'Showing 0 to 0 of 0 entries',
                    zeroRecords: 'No matching result found',
                    emptyTable: 'No assessment result available',
                    paginate: { previous: '&laquo;', next: '&raquo;' }
                }
            });
        }

    });
</script>
@endsection
