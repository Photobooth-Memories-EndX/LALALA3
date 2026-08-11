@extends('layouts.sidebar-master')

@php
    $roleName = \App\Models\User::roles()[$profile->role] ?? '-';
@endphp

@section('content')

    <div class="page-header d-print-none mb-3">
        <div class="container-xl">
            <div class="row align-items-center">
                <div class="col">
                    <div class="page-pretitle">My Learning &middot; Monitoring</div>
                    <h2 class="page-title">Detail Monitoring User</h2>
                </div>
                <div class="col-auto ms-auto">
                    <a href="{{ route('user.learning.dashboard') }}" class="btn btn-outline-secondary">
                        <i class="ti ti-arrow-left"></i> Back
                    </a>
                </div>
            </div>
        </div>
    </div>

    <div class="page-body">
        <div class="container-xl">

            {{-- Identity --}}
            <div class="card mb-3">
                <div class="card-body">
                    <div class="row align-items-center">
                        <div class="col-auto">
                            <span class="avatar avatar-xl bg-primary-lt">
                                {{ $profile->name ? $profile->initials : 'U' }}
                            </span>
                        </div>
                        <div class="col">
                            <h2 class="mb-1">{{ $profile->name ?? '-' }}</h2>
                            <div class="text-muted mb-2"><i class="ti ti-mail me-1"></i>{{ $profile->email ?? '-' }}</div>
                            <div class="d-flex flex-wrap" style="gap:.5rem;">
                                <span class="badge bg-blue-lt text-blue">NIP: {{ $profile->id_emp ?: '-' }}</span>
                                <span class="badge bg-azure-lt text-azure">Division: {{ $profile->division ?: '-' }}</span>
                                <span class="badge bg-cyan-lt text-cyan">Department: {{ $profile->department ?: '-' }}</span>
                                <span class="badge bg-purple-lt text-purple">Team: {{ $profile->team ?: '-' }}</span>
                                <span class="badge bg-green-lt text-green">Role: {{ $roleName }}</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Summary --}}
            <div class="row row-cards mb-3">
                @php
                    $cards = [
                        ['label' => 'Total Learning',        'value' => number_format($summary['total_learning']),        'icon' => 'ti-book',            'color' => 'primary'],
                        ['label' => 'Finished Learning',     'value' => number_format($summary['finished_learning']),     'icon' => 'ti-circle-check',    'color' => 'green'],
                        ['label' => 'Average Progress',      'value' => $summary['average_progress'] . '%',               'icon' => 'ti-chart-line',      'color' => 'azure'],
                        ['label' => 'Assessment Completed',  'value' => number_format($summary['assessment_completed']),  'icon' => 'ti-clipboard-check', 'color' => 'purple'],
                        ['label' => 'Certificate Generated', 'value' => number_format($summary['certificate_generated']), 'icon' => 'ti-certificate',     'color' => 'yellow'],
                    ];
                @endphp
                @foreach ($cards as $card)
                    <div class="col-6 col-md-4 col-lg mb-3">
                        <div class="card h-100">
                            <div class="card-body">
                                <div class="d-flex align-items-center">
                                    <span class="avatar bg-{{ $card['color'] }}-lt text-{{ $card['color'] }} me-3">
                                        <i class="ti {{ $card['icon'] }}"></i>
                                    </span>
                                    <div class="min-w-0">
                                        <div class="text-muted small text-truncate">{{ $card['label'] }}</div>
                                        <div class="h2 mb-0">{{ $card['value'] }}</div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>

            {{-- Analytics charts (real ApexCharts) --}}
            @include('user.learning.dashboard-partials.monitoring.analytics-personal', ['analytics' => $analytics])

            {{-- Tabs (data-toggle BS4-style, di-handle via jQuery agar lepas dari versi bootstrap) --}}
            <div class="card">
                <div class="card-header">
                    <ul class="nav nav-tabs card-header-tabs" role="tablist">
                        <li class="nav-item" role="presentation">
                            <a href="#tab-learning" class="nav-link active" data-toggle="tab" role="tab" aria-selected="true">
                                <i class="ti ti-book me-1"></i> Learning Progress
                            </a>
                        </li>
                        <li class="nav-item" role="presentation">
                            <a href="#tab-assessment" class="nav-link" data-toggle="tab" role="tab" aria-selected="false" tabindex="-1">
                                <i class="ti ti-clipboard-check me-1"></i> Assessment Progress
                            </a>
                        </li>
                        <li class="nav-item" role="presentation">
                            <a href="#tab-playlist" class="nav-link" data-toggle="tab" role="tab" aria-selected="false" tabindex="-1">
                                <i class="ti ti-playlist me-1"></i> Playlist Progress
                            </a>
                        </li>
                    </ul>
                </div>
                <div class="card-body">
                    <div class="tab-content">

                        <div class="tab-pane active show" id="tab-learning" role="tabpanel">
                            <div class="table-responsive">
                                <table id="learning-table" class="table table-vcenter table-hover" style="width:100%">
                                    <thead><tr>
                                        <th class="w-1">No</th><th>Content</th><th>Category</th>
                                        <th>Progress</th><th class="text-center">Status</th><th>Last Access</th>
                                    </tr></thead>
                                    <tbody></tbody>
                                </table>
                            </div>
                        </div>

                        <div class="tab-pane" id="tab-assessment" role="tabpanel">
                            <div class="table-responsive">
                                <table id="assessment-table" class="table table-vcenter table-hover" style="width:100%">
                                    <thead><tr>
                                        <th class="w-1">No</th><th>Assessment</th><th class="text-center">Score</th>
                                        <th class="text-center">Status</th><th class="text-center">Attempt</th><th>Completed At</th>
                                    </tr></thead>
                                    <tbody></tbody>
                                </table>
                            </div>
                        </div>

                        <div class="tab-pane" id="tab-playlist" role="tabpanel">
                            <div class="table-responsive">
                                <table id="playlist-table" class="table table-vcenter table-hover" style="width:100%">
                                    <thead><tr>
                                        <th class="w-1">No</th><th>Playlist</th><th>Progress</th>
                                        <th class="text-center">Completed Content</th><th class="text-center">Status</th>
                                    </tr></thead>
                                    <tbody></tbody>
                                </table>
                            </div>
                        </div>

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
    <script src="{{ asset('Tabler/dist/libs/apexcharts/dist/apexcharts.min.js') }}"></script>
    <script>
        $(function () {
            const emptyText = 'Tidak ada data.';

            const learningTable = $('#learning-table').DataTable({
                processing: true, serverSide: true, autoWidth: false, pageLength: 10,
                language: { emptyTable: emptyText },
                ajax: { url: @json(route('user.learning.monitoring.profile.learning.datatable', $profile->ssohash)) },
                columns: [
                    {data: 'DT_RowIndex', name: 'DT_RowIndex', orderable: false, searchable: false},
                    {data: 'title', name: 'title', orderable: false},
                    {data: 'classification', name: 'classification', orderable: false},
                    {data: 'progress_badge', name: 'progress', orderable: false, searchable: false},
                    {data: 'finish_badge', name: 'finish', orderable: false, searchable: false, className: 'text-center'},
                    {data: 'updated_at', name: 'updated_at', searchable: false},
                ]
            });

            let assessmentTable = null;
            let playlistTable = null;

            function initAssessment() {
                if (assessmentTable !== null) return;
                assessmentTable = $('#assessment-table').DataTable({
                    processing: true, serverSide: true, autoWidth: false, pageLength: 10,
                    language: { emptyTable: emptyText },
                    ajax: { url: @json(route('user.learning.monitoring.profile.assessment.datatable', $profile->ssohash)) },
                    columns: [
                        {data: 'DT_RowIndex', name: 'DT_RowIndex', orderable: false, searchable: false},
                        {data: 'title', name: 'title', orderable: false},
                        {data: 'score_badge', name: 'score', orderable: false, searchable: false, className: 'text-center'},
                        {data: 'status_badge', name: 'pass', orderable: false, searchable: false, className: 'text-center'},
                        {data: 'attempt', name: 'attempt', orderable: false, searchable: false, className: 'text-center'},
                        {data: 'created_at', name: 'created_at', searchable: false},
                    ]
                });
            }

            function initPlaylist() {
                if (playlistTable !== null) return;
                playlistTable = $('#playlist-table').DataTable({
                    processing: true, serverSide: true, autoWidth: false, pageLength: 10,
                    language: { emptyTable: 'Playlist progress belum tersedia.' },
                    ajax: { url: @json(route('user.learning.monitoring.profile.playlist.datatable', $profile->ssohash)) },
                    columns: [
                        {data: 'DT_RowIndex', name: 'DT_RowIndex', orderable: false, searchable: false},
                        {data: 'title', name: 'title', orderable: false},
                        {data: 'progress_badge', name: 'progress', orderable: false, searchable: false},
                        {data: 'items', name: 'items', orderable: false, searchable: false, className: 'text-center'},
                        {data: 'status_badge', name: 'status', orderable: false, searchable: false, className: 'text-center'},
                    ]
                });
            }

            // Tab switching mandiri (tidak bergantung pada JS bootstrap / data-bs-*).
            $('.nav-tabs a[data-toggle="tab"]').on('click', function (e) {
                e.preventDefault();
                const target = $(this).attr('href');

                $(this).closest('.nav-tabs').find('.nav-link').removeClass('active').attr('aria-selected', 'false');
                $(this).addClass('active').attr('aria-selected', 'true');

                $('.tab-content > .tab-pane').removeClass('active show');
                $(target).addClass('active show');

                if (target === '#tab-assessment') initAssessment();
                if (target === '#tab-playlist') initPlaylist();

                $.fn.dataTable.tables({ visible: true, api: true }).columns.adjust();
            });
        });
    </script>
@endsection
