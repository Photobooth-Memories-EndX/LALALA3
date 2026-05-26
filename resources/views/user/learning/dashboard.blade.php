@extends('layouts.sidebar-master')

@section('content')

<div class="page-body">
    <div class="container-xl">

        @php
            $user = Auth::user();
            $name = $user->name ?? 'User';
            $initial = strtoupper(substr($name, 0, 1));

            $contentItems = collect($content_user ?? []);
            $assessmentItems = collect($assessments ?? []);
            $resultItems = collect($result ?? []);

            $totalLearning = $contentItems->count();
            $completedLearning = $contentItems->filter(fn($item) => ($item->progress ?? 0) >= 100 || ($item->finish ?? '0') == '1')->count();
            $ongoingLearning = $contentItems->filter(fn($item) => ($item->finish ?? '0') == '0')->count();
            $averageProgress = $totalLearning > 0 ? round($contentItems->avg('progress')) : 0;

            $totalAssessment = $assessmentItems->count();
            $completedAssessment = $resultItems->where('final', 1)->count();
            $passedAssessment = $resultItems->where('pass', 1)->count();
        @endphp

        <div class="page-header d-print-none mb-3">
            <div class="row align-items-center">
                <div class="col">
                    <h2 class="page-title mb-1">Learning Dashboard</h2>
                    <div class="text-muted">Kelola learning, record, assessment, dan sertifikat Anda.</div>
                </div>
            </div>
        </div>

        <div class="card border-0 shadow-sm rounded-4 mb-3">
            <div class="card-body">
                <div class="row align-items-center g-3">
                    <div class="col-auto">
                        <span class="avatar avatar-lg rounded-circle bg-primary text-white">
                            {{ $initial }}
                        </span>
                    </div>

                    <div class="col">
                        <div class="fw-bold fs-3">{{ $name }}</div>
                        <div class="text-muted small">
                            {{ $user->id_emp ?? '-' }}
                            @if (!empty($user->team))
                                · {{ $user->team }}
                            @endif
                        </div>
                        <div class="text-muted small">
                            {{ $user->department ?? '-' }}
                        </div>
                    </div>

                    <div class="col-md-auto text-md-end">
                        <div class="fw-semibold">{{ $user->position ?? $user->job_title ?? '-' }}</div>
                        <a href="mailto:{{ $user->email ?? '' }}" class="small text-primary">
                            {{ $user->email ?? '-' }}
                        </a>
                    </div>
                </div>
            </div>
        </div>

        <div class="card border-0 shadow-sm rounded-4 mb-3">
            <div class="card-body py-2">
                <div class="d-flex flex-wrap gap-2" role="tablist">
                    <button class="btn btn-primary learning-tab-btn" data-target="#tab-ongoing" type="button">
                        Learning On Going
                    </button>
                    <button class="btn btn-white learning-tab-btn" data-target="#tab-record" type="button">
                        Learning Record
                    </button>
                    <button class="btn btn-white learning-tab-btn" data-target="#tab-assessment" type="button">
                        Assessment
                    </button>
                    <button class="btn btn-white learning-tab-btn" data-target="#tab-claim" type="button">
                        Klaim Learning Hour
                    </button>
                    <button class="btn btn-white learning-tab-btn" data-target="#tab-certificate" type="button">
                        Sertifikat
                    </button>
                    <button class="btn btn-white learning-tab-btn" data-target="#tab-monitoring" type="button">
                        Monitoring
                    </button>
                </div>
            </div>
        </div>

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
                        <div class="h2 mb-0 text-success">{{ $completedLearning }}</div>
                    </div>
                </div>
            </div>

            <div class="col-sm-6 col-lg-3">
                <div class="card border-0 shadow-sm rounded-4">
                    <div class="card-body">
                        <div class="text-muted">On Going</div>
                        <div class="h2 mb-0 text-primary">{{ $ongoingLearning }}</div>
                    </div>
                </div>
            </div>

            <div class="col-sm-6 col-lg-3">
                <div class="card border-0 shadow-sm rounded-4">
                    <div class="card-body">
                        <div class="text-muted">Assessment Pass</div>
                        <div class="h2 mb-0 text-info">{{ $passedAssessment }}</div>
                    </div>
                </div>
            </div>
        </div>

        <div id="tab-ongoing" class="learning-tab-content">
            <section class="learning-section-wrapper">
                <div class="learning-section-padding py-4 px-2 px-md-3 learning-dashboard-container">
                    <div class="card border-0 shadow-sm rounded-4 learning-hero learning-section-hero mb-4">
                    <div class="card-body p-4 p-lg-5">
                        <div class="row align-items-center g-4">
                            <div class="col-lg-7">
                                <div class="d-inline-flex align-items-center gap-2 badge bg-white text-primary shadow-sm mb-3 px-3 py-2 rounded-pill">
                                    <i class="ti ti-flame"></i>
                                    <span><span id="learningVisibleCount">{{ isset($ongoing_content_user) ? $ongoing_content_user->count() : 0 }}</span> kelas aktif</span>
                                </div>
                                <h3 class="display-6 mb-2 text-white">Lanjutkan Pembelajaran Anda</h3>
                                <p class="mb-0 text-white-75">
                                    Selesaikan materi yang sedang berjalan dan tingkatkan progress learning Anda.
                                </p>
                            </div>
                            <div class="col-lg-5">
                                <div class="row g-3">
                                    <div class="col-6">
                                        <div class="learning-mini-stat">
                                            <div class="learning-mini-stat-label">Total Ongoing</div>
                                            <div class="learning-mini-stat-value">{{ $ongoingLearning }}</div>
                                        </div>
                                    </div>
                                    <div class="col-6">
                                        <div class="learning-mini-stat">
                                            <div class="learning-mini-stat-label">Average Progress</div>
                                            <div class="learning-mini-stat-value">{{ $averageProgress }}%</div>
                                        </div>
                                    </div>
                                    <div class="col-12">
                                        <a href="#learningGrid" class="btn btn-light w-100 btn-lg text-primary fw-semibold">
                                            Mulai Belajar
                                        </a>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="card border-0 shadow-sm rounded-4 mb-4 learning-filter-card">
                    <div class="card-body p-4">
                        <div class="row g-3 align-items-end">
                            <div class="col-lg-4">
                                <h3 class="card-title mb-1">Learning On Going</h3>
                                <div class="text-muted small">Daftar kelas yang sedang Anda ikuti.</div>
                            </div>
                            <div class="col-md-4 col-lg-3">
                                <label class="form-label small text-muted mb-1" for="learningSearch">Search</label>
                                <div class="input-icon">
                                    <span class="input-icon-addon">
                                        <i class="ti ti-search"></i>
                                    </span>
                                    <input type="text"
                                           id="learningSearch"
                                           class="form-control rounded-3"
                                           placeholder="Cari kelas atau klasifikasi">
                                </div>
                            </div>
                            <div class="col-md-4 col-lg-3">
                                <label class="form-label small text-muted mb-1" for="learningProgramFilter">Learning Program</label>
                                <select id="learningProgramFilter" class="form-select rounded-3">
                                    <option value="all" selected>All Learning</option>
                                    <option value="mandatory">Mandatory Learning</option>
                                    <option value="optional">Optional Learning</option>
                                </select>
                            </div>
                            <div class="col-md-4 col-lg-2">
                                <label class="form-label small text-muted mb-1" for="learningYearFilter">Tahun</label>
                                <select id="learningYearFilter" class="form-select rounded-3">
                                    <option value="all" selected>All Years</option>
                                    <option value="2020">2020</option>
                                    <option value="2021">2021</option>
                                    <option value="2022">2022</option>
                                    <option value="2023">2023</option>
                                    <option value="2024">2024</option>
                                    <option value="2025">2025</option>
                                    <option value="2026">2026</option>
                                </select>
                            </div>
                        </div>
                    </div>
                </div>

                    @if ($ongoing_content_user->count() > 0)
                    <div class="row g-4 mb-4" id="learningGrid">
                        @foreach ($ongoing_content_user as $list)
                            @php
                                $content = $list->content;
                                $classification = $content->classification->classification_name ?? '-';
                                $title = $content->title ?? '-';
                                $slug = $content->slug ?? null;
                                $progress = (int) ($list->progress ?? 0);
                                $duration = $content->duration ?? '-';
                                $type = strtolower($content->type ?? 'content');

                                $program = strtolower($list->status ?? 'optional');

                                $year = $list->created_at
                                    ? \Carbon\Carbon::parse($list->created_at)->format('Y')
                                    : (
                                        $list->updated_at
                                            ? \Carbon\Carbon::parse($list->updated_at)->format('Y')
                                            : 'unknown'
                                    );

                                $thumbnail = null;

                                if (!empty($content->thumbnail)) {
                                    $thumbnail = asset('storage/content/thumbnail/' . $content->thumbnail);
                                } elseif (!empty($content->image)) {
                                    $thumbnail = asset('storage/content/' . $content->image);
                                } elseif (!empty($content->cover)) {
                                    $thumbnail = asset('storage/content/' . $content->cover);
                                }

                                $statusLabel = $list->status ?? 'optional';
                            @endphp

                               <div class="col-md-6 col-xl-4 learning-item"
                                 data-title="{{ strtolower($title) }}"
                                 data-classification="{{ strtolower($classification) }}"
                                 data-program="{{ $program }}"
                                 data-year="{{ $year }}">
                                <x-learning.ongoing-card
                                    :title="$title"
                                    :slug="$slug"
                                    :classification="$classification"
                                    :duration="$duration"
                                    :type="$type"
                                    :progress="$progress"
                                    :program="$program"
                                    :year="$year"
                                    :active="$list->active ?? '-'"
                                    :status-label="$statusLabel"
                                    :thumbnail="$thumbnail"
                                />
                            </div>
                        @endforeach
                    </div>

                    @if ($ongoing_content_user->hasPages())
                        <div class="mt-4 d-flex justify-content-center">
                            {{ $ongoing_content_user->links('pagination::bootstrap-5') }}
                        </div>
                    @endif

                    <div id="learningEmptyFilter" class="card border-0 shadow-sm rounded-4 d-none mt-3">
                        <div class="card-body text-center py-5">
                            <div class="empty-title">Tidak ada learning yang sesuai filter</div>
                            <div class="empty-subtitle text-muted">
                                Coba ubah kata kunci, program, atau tahun yang dipilih.
                            </div>
                        </div>
                    </div>
                @else
                    <div class="card border-0 shadow-sm rounded-4">
                        <div class="card-body text-center py-5">
                            <div class="empty-title">Belum ada learning berjalan</div>
                            <div class="empty-subtitle text-muted">Data kelas berjalan akan muncul di sini.</div>
                        </div>
                    </div>
                @endif
                </div>
            </section>
        </div>

        <div id="tab-record" class="learning-tab-content d-none">
            <div class="card border-0 shadow-sm rounded-4 mb-3">
                <div class="card-body">
                    <h3 class="mb-1">Learning Record</h3>
                    <div class="text-muted">Riwayat pembelajaran yang sudah selesai.</div>

                    <div class="mt-4">
                        <div class="fw-bold mb-1">Average Progress</div>
                        <div class="display-6 text-primary mb-2">{{ $averageProgress }}%</div>
                        <div class="progress progress-sm">
                            <div class="progress-bar bg-primary" style="width: {{ $averageProgress }}%"></div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="card border-0 shadow-sm rounded-4">
                <div class="card-body">
                    <h3 class="card-title mb-1">Daftar Riwayat Training</h3>
                    <div class="text-muted small">Training yang sudah Anda selesaikan.</div>
                </div>

                <div class="table-responsive">
                    <table class="table table-vcenter card-table" id="recordTable">
                        <thead>
                            <tr>
                                <th>No</th>
                                <th>Nama Kelas</th>
                                <th>Classification</th>
                                <th>Duration</th>
                                <th>Progress</th>
                                <th>Finish Date</th>
                                <th>Score Test</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($content_user as $list)
                                @if (($list->finish ?? '0') == '1')
                                    @php
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
                                        <td>{{ $list->content->title ?? '-' }}</td>
                                        <td>{{ $list->content->classification->classification_name ?? '-' }}</td>
                                        <td>{{ $list->content->duration ?? '-' }}</td>
                                        <td>{{ $list->progress ?? 0 }}%</td>
                                        <td>{{ $list->updated_at ?? $list->created_at ?? '-' }}</td>
                                        <td>{{ $score ?? '-' }}</td>
                                    </tr>
                                @endif
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <div id="tab-assessment" class="learning-tab-content d-none">
            <div class="card border-0 shadow-sm rounded-4 mb-3">
                <div class="card-body">
                    <h3 class="card-title mb-1">Assessment List</h3>
                    <div class="text-muted small">Daftar assessment yang tersedia untuk Anda.</div>
                </div>

                <div class="table-responsive">
                    <table class="table table-vcenter card-table" id="assessmentTable">
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
                                    $finalResult = null;

                                    if (($assessment->scores ?? null) != null) {
                                        $finalResult = $assessment->scores
                                            ->where('user_id', Auth::user()->id)
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
                                        <div class="fw-semibold">{{ $assessment->title ?? '-' }}</div>
                                        <div class="text-muted small">
                                            {{ $assessment->trainingplan->title ?? 'Assessment' }}
                                        </div>
                                    </td>

                                    <td>
                                        @if (($assessment->timer ?? 'n') == 'y')
                                            <div class="small">From: {{ $assessment->fromdate ? date('d/m/y H:i', strtotime($assessment->fromdate)) : '-' }}</div>
                                            <div class="small">To: {{ $assessment->todate ? date('d/m/y H:i', strtotime($assessment->todate)) : '-' }}</div>
                                        @else
                                            -
                                        @endif
                                    </td>

                                    <td>
                                        <span class="badge bg-blue-lt text-blue">{{ $typeLabel }}</span>
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
                                            <div class="small text-muted mt-1">Score: {{ $finalResult->score ?? '-' }}</div>
                                        @else
                                            <span class="badge bg-orange-lt text-orange">Not Started</span>
                                        @endif
                                    </td>

                                    <td class="text-end">
                                        @if ($isCompleted && $isPassed)
                                            <button class="btn btn-sm btn-white" disabled>Completed</button>
                                        @else
                                            @if (!empty($assessment->slug))
                                                <a href="{{ route('assesment.user.view', $assessment->slug) }}" class="btn btn-sm btn-primary">
                                                    {{ $isCompleted ? 'Remedial' : 'Kerjakan' }}
                                                </a>
                                            @else
                                                <button class="btn btn-sm btn-secondary" disabled>Unavailable</button>
                                            @endif
                                        @endif
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>

            <div class="card border-0 shadow-sm rounded-4">
                <div class="card-body">
                    <h3 class="card-title mb-1">Assessment Result</h3>
                    <div class="text-muted small">Riwayat hasil assessment Anda.</div>
                </div>

                <div class="table-responsive">
                    <table class="table table-vcenter card-table" id="assessmentResultTable">
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
                            @foreach ($result as $list)
                                <tr>
                                    <td>{{ $loop->iteration }}</td>
                                    <td>{{ $list->created_at ? date("d/m/y H:i:s", strtotime($list->created_at)) : '-' }}</td>
                                    <td>{{ $list->assesment->title ?? '-' }}</td>
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

        <div id="tab-claim" class="learning-tab-content d-none">
            <div class="card border-0 shadow-sm rounded-4">
                <div class="card-body text-center py-5">
                    <h3>Klaim Learning Hour</h3>
                    <p class="text-muted mb-0">Halaman ini masih kosong dan bisa dikembangkan nanti.</p>
                </div>
            </div>
        </div>

        <div id="tab-certificate" class="learning-tab-content d-none">
            <div class="card border-0 shadow-sm rounded-4">
                <div class="card-body text-center py-5">
                    <h3>Sertifikat</h3>
                    <p class="text-muted mb-0">Halaman ini masih kosong dan bisa dikembangkan nanti.</p>
                </div>
            </div>
        </div>

        <div id="tab-monitoring" class="learning-tab-content d-none">
            <div class="card border-0 shadow-sm rounded-4">
                <div class="card-body text-center py-5">
                    <h3>Monitoring</h3>
                    <p class="text-muted mb-0">Halaman monitoring masih kosong dan bisa dikembangkan nanti.</p>
                </div>
            </div>
        </div>

    </div>
</div>

@endsection

@section('style')
<link rel="stylesheet" href="{{ asset('css/dataTables.bootstrap5.min.css') }}">
<style>
    .learning-section-padding {
        padding: 1.25rem;
    }

    .learning-dashboard-container {
        max-width: 1500px;
        margin: auto;
    }

    .learning-hero {
        background: linear-gradient(135deg, #0d6efd 0%, #1e40af 100%);
        border-radius: 1.25rem;
        color: #fff;
    }

    .learning-mini-stat {
        background: rgba(255, 255, 255, 0.12);
        border: 1px solid rgba(255, 255, 255, 0.18);
        border-radius: 1rem;
        padding: 1rem 1.1rem;
        color: #fff;
        backdrop-filter: blur(10px);
    }

    .learning-mini-stat-label {
        font-size: .75rem;
        letter-spacing: .04em;
        text-transform: uppercase;
        opacity: .8;
        margin-bottom: .35rem;
    }

    .learning-mini-stat-value {
        font-size: 1.5rem;
        font-weight: 700;
        line-height: 1;
    }

    .learning-filter-card .form-control,
    .learning-filter-card .form-select {
        min-height: 42px;
    }

    .learning-filter-card .form-label {
        font-weight: 600;
    }

    .learning-filter-card .form-control,
    .learning-filter-card .form-select {
        border-radius: .9rem;
    }

    .learning-modern-card {
        transition: transform .2s ease, box-shadow .2s ease;
    }

    .learning-modern-card:hover {
        transform: translateY(-3px);
        box-shadow: 0 .5rem 1rem rgba(0,0,0,.08) !important;
    }

    .learning-thumb {
        height: 180px;
        object-fit: cover;
        background: linear-gradient(135deg, #dbeafe, #eff6ff);
    }

    .learning-placeholder {
        height: 180px;
        background: linear-gradient(135deg, #dbeafe 0%, #bfdbfe 100%);
        display: flex;
        align-items: center;
        justify-content: center;
    }

    .learning-progress-bar {
        height: .7rem;
        border-radius: 999px;
        background: #e2e8f0;
    }

    .learning-progress-bar .progress-bar {
        border-radius: 999px;
    }

    .line-clamp-2 {
        display: -webkit-box;
        -webkit-line-clamp: 2;
        -webkit-box-orient: vertical;
        overflow: hidden;
    }

    .learning-card-title {
        min-height: calc(1.4em * 2);
    }

    .rounded-4 {
        border-radius: 1rem !important;
    }

    .learning-tab-btn {
        white-space: nowrap;
    }

    .text-white-75 {
        color: rgba(255, 255, 255, .75) !important;
    }

    .pagination {
        gap: .4rem;
    }

    .page-item .page-link {
        border-radius: 12px !important;
        border: 1px solid #dbe4f0;
        color: #1e40af;
        min-width: 40px;
        height: 40px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-weight: 600;
    }

    .page-item.active .page-link {
        background: #2563eb;
        border-color: #2563eb;
        color: #fff;
        box-shadow: 0 8px 18px rgba(37, 99, 235, .25);
    }

    .page-item .page-link:hover {
        background: #eff6ff;
    }
</style>
@endsection

@section('script')
<script src="{{ asset('js/jquery.dataTables.min.js') }}"></script>
<script src="{{ asset('js/dataTables.bootstrap5.min.js') }}"></script>

<script>
    $(function () {
        const $learningSearch = $('#learningSearch');
        const $programFilter = $('#learningProgramFilter');
        const $yearFilter = $('#learningYearFilter');
        const $emptyFilter = $('#learningEmptyFilter');
        const $visibleCount = $('#learningVisibleCount');
        const $learningItems = $('.learning-item');

        const currentUrl = new URL(window.location.href);
        if (currentUrl.searchParams.has('ongoing_page')) {
            $('.learning-tab-btn').removeClass('btn-primary').addClass('btn-white');
            $('.learning-tab-btn[data-target="#tab-ongoing"]').removeClass('btn-white').addClass('btn-primary');
            $('.learning-tab-content').addClass('d-none');
            $('#tab-ongoing').removeClass('d-none');
        }

        $('.learning-tab-btn').on('click', function () {
            let target = $(this).data('target');

            $('.learning-tab-btn').removeClass('btn-primary').addClass('btn-white');
            $(this).removeClass('btn-white').addClass('btn-primary');

            $('.learning-tab-content').addClass('d-none');
            $(target).removeClass('d-none');
        });

        function applyLearningFilters() {
            const keyword = String($learningSearch.val() || '').toLowerCase().trim();
            const selectedProgram = String($programFilter.val() || 'all').toLowerCase();
            const selectedYear = String($yearFilter.val() || 'all');

            let visibleCount = 0;

            $learningItems.each(function () {
                const $item = $(this);

                const title = String($item.attr('data-title') || '').toLowerCase();
                const classification = String($item.attr('data-classification') || '').toLowerCase();
                const program = String($item.attr('data-program') || '').toLowerCase();
                const year = String($item.attr('data-year') || '');

                const matchKeyword =
                    keyword === '' ||
                    title.includes(keyword) ||
                    classification.includes(keyword) ||
                    program.includes(keyword) ||
                    year.includes(keyword);

                const matchProgram =
                    selectedProgram === 'all' ||
                    program === selectedProgram;

                const matchYear =
                    selectedYear === 'all' ||
                    year === selectedYear;

                if (matchKeyword && matchProgram && matchYear) {
                    $item.removeClass('d-none');
                    visibleCount++;
                } else {
                    $item.addClass('d-none');
                }
            });

            if ($visibleCount.length) {
                $visibleCount.text(visibleCount);
            }

            if ($emptyFilter.length) {
                if (visibleCount === 0) {
                    $emptyFilter.removeClass('d-none');
                } else {
                    $emptyFilter.addClass('d-none');
                }
            }
        }

        let searchTimer = null;

        $learningSearch.on('keyup input', function () {
            clearTimeout(searchTimer);
            searchTimer = setTimeout(applyLearningFilters, 200);
        });

        $programFilter.on('change', applyLearningFilters);
        $yearFilter.on('change', applyLearningFilters);

        applyLearningFilters();

        if ($('#recordTable').length) {
            $('#recordTable').DataTable({
                ordering: false,
                pageLength: 10,
                autoWidth: false,
            });
        }

        if ($('#assessmentTable').length) {
            $('#assessmentTable').DataTable({
                ordering: false,
                pageLength: 10,
                autoWidth: false,
            });
        }

        if ($('#assessmentResultTable').length) {
            $('#assessmentResultTable').DataTable({
                ordering: false,
                pageLength: 10,
                autoWidth: false,
            });
        }
    });
</script>
@endsection