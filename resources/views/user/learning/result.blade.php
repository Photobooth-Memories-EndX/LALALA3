@extends('layouts.sidebar-master')

@section('content')

<x-learning.dashboard-shell
    active="record"
    title="Learning Dashboard"
    subtitle="Laporan dan riwayat pembelajaran Anda."

>
    @php
        $items = collect($content_user ?? []);
        $totalLearning = $items->count();
        $completed = $items->filter(fn($item) => ($item->progress ?? 0) >= 100)->count();
        $ongoing = $totalLearning - $completed;
        $averageProgress = $totalLearning > 0 ? round($items->avg('progress')) : 0;

        $totalDuration = $items->sum(function ($item) {
            return (int) ($item->content->duration ?? 0);
        });
    @endphp

    <div class="row g-3 mb-3">
        <div class="col-sm-6 col-lg-3">
            <div class="card border-0 shadow-sm rounded-4">
                <div class="card-body">
                    <div class="text-muted">Total Learning</div>
                    <div class="h2 mb-0">{{ $totalLearning }}</div>
                </div>
            </div>
        </div>

        <div class="col-sm-6 col-lg-3">
            <div class="card border-0 shadow-sm rounded-4">
                <div class="card-body">
                    <div class="text-muted">Completed</div>
                    <div class="h2 mb-0 text-success">{{ $completed }}</div>
                </div>
            </div>
        </div>

        <div class="col-sm-6 col-lg-3">
            <div class="card border-0 shadow-sm rounded-4">
                <div class="card-body">
                    <div class="text-muted">On Going</div>
                    <div class="h2 mb-0 text-primary">{{ $ongoing }}</div>
                </div>
            </div>
        </div>

        <div class="col-sm-6 col-lg-3">
            <div class="card border-0 shadow-sm rounded-4">
                <div class="card-body">
                    <div class="text-muted">Average Progress</div>
                    <div class="h2 mb-0">{{ $averageProgress }}%</div>
                </div>
            </div>
        </div>
    </div>

    <div class="card border-0 shadow-sm rounded-4 mb-3">
        <div class="card-body">
            <div class="row align-items-center g-3">
                <div class="col">
                    <h3 class="mb-1">Learning Record</h3>
                    <div class="text-muted">
                        Riwayat pembelajaran berdasarkan aktivitas training Anda.
                    </div>
                </div>

                <div class="col-md-auto">
                    <button type="button" class="btn btn-primary">
                        Unduh PDF
                    </button>
                </div>
            </div>

            <div class="mt-4">
                <div class="fw-bold mb-1">Learning Hour</div>
                <div class="display-6 text-primary mb-2">
                    {{ $totalDuration > 0 ? $totalDuration : '11 Jam 00 Menit' }}
                </div>

                <div class="progress progress-sm mb-2">
                    <div class="progress-bar bg-primary" style="width: {{ $averageProgress }}%"></div>
                </div>

                <div class="text-muted small">
                    Average Learning Progress {{ $averageProgress }}%.
                </div>
            </div>
        </div>
    </div>

    <div class="card border-0 shadow-sm rounded-4 mb-3">
        <div class="card-body">
            <div class="row g-2 align-items-center">
                <div class="col">
                    <h3 class="card-title mb-1">Training History</h3>
                    <div class="text-muted small">
                        View your past and current training history.
                    </div>
                </div>

                <div class="col-md-4">
                    <input type="text" id="recordSearch" class="form-control" placeholder="Cari training">
                </div>
            </div>
        </div>

        <div class="table-responsive">
            @if ($totalLearning > 0)
                <table class="table table-vcenter card-table" id="MyTable">
                    <thead>
                        <tr>
                            <th>No</th>
                            <th>Certificate</th>
                            <th>Class Name</th>
                            <th>Jenis</th>
                            <th>Classification</th>
                            <th>Duration</th>
                            <th>Progress</th>
                            <th>Finish Date</th>
                            <th>Score Test</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        @php
                            // Records with progress >= 100 (eligible for certificate) appear first.
                            // Within each group, newest updated_at first.
                            $sortedForTable = collect($content_user)
                                ->sortByDesc(function ($item) {
                                    $completed = ((int)($item->progress ?? 0) >= 100) ? 1 : 0;
                                    $ts        = optional($item->updated_at)->timestamp ?? 0;
                                    return [$completed, $ts];
                                })
                                ->values();
                        @endphp
                        @foreach ($sortedForTable as $list)
                            @php
                                $progress = (int) ($list->progress ?? 0);
                                $isCompleted = $progress >= 100;
                                $content = $list->content ?? null;
                                $score = null;

                                if (!blank($list->assesment ?? null)) {
                                    $datas = $list->assesment->scores ?? collect();
                                    $filter = $datas->filter(function ($assesment_result) use($list) {
                                        return $assesment_result->user_id == $list->user_id;
                                    })->sortByDesc('score')->first();

                                    if (!blank($filter)) {
                                        $score = $filter->score;
                                    }
                                }
                            @endphp

                            <tr>
                                <td>{{ $loop->iteration }}</td>

                                <td class="text-nowrap">
                                    @if($isCompleted)
                                        <a href="{{ route('certificate.generate', $list->id) }}"
                                           class="btn btn-sm btn-primary">
                                            <i class="ti ti-certificate me-1"></i>
                                            Certificate
                                        </a>
                                    @else
                                        <span class="badge bg-secondary-lt text-secondary">
                                            Not Available
                                        </span>
                                    @endif
                                </td>

                                <td>
                                    <div class="d-flex align-items-center gap-2">
                                        <span class="avatar avatar-sm bg-blue-lt">
                                            {{ strtoupper(substr($content->title ?? 'L', 0, 1)) }}
                                        </span>

                                        <div>
                                            @if ($content && !empty($content->slug))
                                                <a href="{{ route('content.user.view', $content->slug) }}" class="fw-semibold text-reset">
                                                    {{ $content->title ?? '-' }}
                                                </a>
                                            @else
                                                <div class="fw-semibold">{{ $content->title ?? '-' }}</div>
                                            @endif

                                            <div class="text-muted small">
                                                {{ $content->type ?? 'Content' }}
                                            </div>
                                        </div>
                                    </div>
                                </td>

                                <td>
                                    @if (($list->assesment ?? null) != null)
                                        <span class="badge bg-blue-lt text-blue">Content</span>
                                    @elseif (($list->assesmentPlaylist ?? null) != null)
                                        <span class="badge bg-green-lt text-green">Playlist</span>
                                    @else
                                        <span class="badge bg-secondary-lt">N/A</span>
                                    @endif
                                </td>

                                <td>{{ $content->classification->classification_name ?? '-' }}</td>
                                <td>{{ $content->duration ?? '-' }}</td>

                                <td style="min-width: 140px;">
                                    <div class="d-flex justify-content-between small mb-1">
                                        <span>{{ $progress }}%</span>
                                    </div>
                                    <div class="progress progress-sm">
                                        <div class="progress-bar {{ $isCompleted ? 'bg-success' : 'bg-primary' }}"
                                             style="width: {{ $progress }}%">
                                        </div>
                                    </div>
                                </td>

                                <td>
                                    @if ($isCompleted)
                                        {{ $list->updated_at ?? $list->created_at ?? '-' }}
                                    @else
                                        -
                                    @endif
                                </td>

                                <td>
                                    {{ $score ?? '-' }}
                                </td>

                                <td>
                                    @if ($isCompleted)
                                        <span class="badge bg-green-lt text-green">Completed</span>
                                    @else
                                        <span class="badge bg-orange-lt text-orange">On Going</span>
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            @else
            <div class="empty py-5">
                <div class="empty-title">No Learning Records Yet</div>
                <div class="empty-subtitle text-muted">
                    Your training history will appear here.
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
            if ($('#MyTable').length) {
                const table = $("#MyTable").DataTable({
                    ordering: false,
                    pageLength: 10,
                    autoWidth: false,
                    dom: 'rt<"d-flex justify-content-between align-items-center mt-3"ip>',
                });

                $('#recordSearch').on('keyup', function () {
                    table.search(this.value).draw();
                });
            }
        });
    </script>
@endsection
