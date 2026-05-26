@extends('layouts.sidebar-master')

@section('content')

<x-learning.dashboard-shell 
    active="ongoing"
    title="Learning Dashboard"
    subtitle="Pantau kelas yang sedang Anda ikuti."
>
    @php
        $totalLearning = count($content_user ?? []);
        $completed = collect($content_user ?? [])->filter(fn($item) => ($item->progress ?? 0) >= 100)->count();
        $ongoing = $totalLearning - $completed;
        $averageProgress = $totalLearning > 0 ? round(collect($content_user)->avg('progress')) : 0;
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
            <div class="row g-2 align-items-center">
                <div class="col">
                    <h3 class="card-title mb-1">Learning On Going</h3>
                    <div class="text-muted small">
                        Menampilkan daftar kelas atau kursus yang sedang berjalan.
                    </div>
                </div>
                <div class="col-md-4">
                    <input type="text" id="learningSearch" class="form-control" placeholder="Cari nama kelas atau kursus">
                </div>
            </div>
        </div>
    </div>

    @if ($totalLearning > 0)
        <div class="row g-3" id="learningGrid">
            @foreach ($content_user as $list)
                @if ($list->content != null)
                    @php
                        $progress = (int) ($list->progress ?? 0);
                        $isCompleted = $progress >= 100;
                        $type = $list->content->type ?? 'Content';
                        $title = $list->content->title ?? '-';
                        $classification = $list->content->classification->classification_name ?? '-';
                        $duration = $list->content->duration ?? '-';
                        $slug = $list->content->slug ?? null;
                    @endphp

                    <div class="col-md-6 col-xl-4 learning-item"
                         data-title="{{ strtolower($title) }}"
                         data-classification="{{ strtolower($classification) }}">
                        <div class="card border-0 shadow-sm rounded-4 h-100 overflow-hidden">
                            <div class="card-status-top {{ $isCompleted ? 'bg-success' : 'bg-primary' }}"></div>

                            <div class="card-body">
                                <div class="d-flex align-items-start gap-3 mb-3">
                                    <span class="avatar rounded bg-blue-lt">
                                        @if (in_array($type, ['Video', 'YouTube']))
                                            <svg xmlns="http://www.w3.org/2000/svg" class="icon text-danger" width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none">
                                                <path stroke="none" d="M0 0h24v24H0z" fill="none"/>
                                                <path d="M15 10l4.553 -2.276a1 1 0 0 1 1.447 .894v6.764a1 1 0 0 1 -1.447 .894l-4.553 -2.276v-4z"/>
                                                <path d="M3 6m0 2a2 2 0 0 1 2 -2h8a2 2 0 0 1 2 2v8a2 2 0 0 1 -2 2h-8a2 2 0 0 1 -2 -2z"/>
                                            </svg>
                                        @else
                                            <svg xmlns="http://www.w3.org/2000/svg" class="icon text-warning" width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none">
                                                <path stroke="none" d="M0 0h24v24H0z" fill="none"/>
                                                <path d="M14 3v4a1 1 0 0 0 1 1h4"/>
                                                <path d="M5 12v-7a2 2 0 0 1 2 -2h7l5 5v4"/>
                                                <path d="M5 18h1.5a1.5 1.5 0 0 0 0 -3h-1.5v6"/>
                                                <path d="M17 18h2"/>
                                                <path d="M20 15h-3v6"/>
                                                <path d="M11 15v6h1a2 2 0 0 0 2 -2v-2a2 2 0 0 0 -2 -2h-1z"/>
                                            </svg>
                                        @endif
                                    </span>

                                    <div class="flex-fill">
                                        <span class="badge {{ $isCompleted ? 'bg-green-lt text-green' : 'bg-blue-lt text-blue' }}">
                                            {{ $isCompleted ? 'Completed' : 'Sedang Berlangsung' }}
                                        </span>

                                        <h3 class="mt-2 mb-1 fs-4">
                                            {{ $title }}
                                        </h3>

                                        <div class="text-muted small">
                                            {{ $classification }}
                                        </div>
                                    </div>
                                </div>

                                <div class="mb-3">
                                    <div class="d-flex justify-content-between small mb-1">
                                        <span class="text-muted">Progress</span>
                                        <span class="fw-semibold">{{ $progress }}%</span>
                                    </div>
                                    <div class="progress progress-sm">
                                        <div class="progress-bar {{ $isCompleted ? 'bg-success' : 'bg-primary' }}"
                                             style="width: {{ $progress }}%"
                                             role="progressbar"
                                             aria-valuenow="{{ $progress }}"
                                             aria-valuemin="0"
                                             aria-valuemax="100">
                                        </div>
                                    </div>
                                </div>

                                <div class="d-flex justify-content-between align-items-center">
                                    <div class="small text-muted">
                                        Duration: {{ $duration }}
                                    </div>

                                    @if ($slug)
                                        <a href="{{ route('content.user.view', $slug) }}" class="btn btn-sm btn-primary">
                                            {{ $isCompleted ? 'Detail' : 'Lanjutkan' }}
                                        </a>
                                    @else
                                        <button class="btn btn-sm btn-secondary" disabled>Unavailable</button>
                                    @endif
                                </div>
                            </div>
                        </div>
                    </div>
                @endif
            @endforeach
        </div>

        <div class="d-none">
            <table id="MyTable">
                <tbody>
                    @foreach ($content_user as $list)
                        <tr>
                            <td>{{ $list->content->title ?? '-' }}</td>
                            <td>{{ $list->content->classification->classification_name ?? '-' }}</td>
                            <td>{{ $list->progress ?? 0 }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @else
        <div class="card border-0 shadow-sm rounded-4">
            <div class="card-body text-center py-5">
                <div class="empty">
                    <div class="empty-img">
                        <img src="{{ asset('Tabler/static/illustrations/undraw_empty.svg') }}" height="120" alt="">
                    </div>
                    <p class="empty-title">Belum ada learning berjalan</p>
                    <p class="empty-subtitle text-muted">
                        Data kelas atau kursus Anda akan muncul di halaman ini.
                    </p>
                </div>
            </div>
        </div>
    @endif

</x-learning.dashboard-shell>

@endsection

@section('style')
    <link rel="stylesheet" href="{{ asset('css/dataTables.bootstrap5.min.css') }}">
    <style>
        .rounded-4 {
            border-radius: 1rem !important;
        }

        .learning-item .card {
            transition: .2s ease;
        }

        .learning-item .card:hover {
            transform: translateY(-3px);
        }
    </style>
@endsection

@section('script')
    <script src="{{ asset('js/jquery.dataTables.min.js') }}"></script>
    <script src="{{ asset('js/dataTables.bootstrap5.min.js') }}"></script>

    <script>
        $(function() {
            if ($('#MyTable').length) {
                $("#MyTable").DataTable({
                    ordering: false,
                    pageLength: 25,
                    autoWidth: false,
                });
            }

            $('#learningSearch').on('keyup', function () {
                let keyword = $(this).val().toLowerCase();

                $('.learning-item').each(function () {
                    let title = $(this).data('title') || '';
                    let classification = $(this).data('classification') || '';

                    if (title.includes(keyword) || classification.includes(keyword)) {
                        $(this).removeClass('d-none');
                    } else {
                        $(this).addClass('d-none');
                    }
                });
            });
        });
    </script>
@endsection