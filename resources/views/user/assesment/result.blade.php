@extends('layouts.sidebar-master')

@section('content')

<div class="page-body">
    <div class="container-xl">

        @php
            $score = (int) ($assesment_result->score ?? 0);
            $passingGrade = (int) ($assesment->passing_grade ?? 0);
            $isPassed = $score >= $passingGrade;

            $typeDisplay = 'Assessment';
            if (($assesment->type ?? '') === 'global') {
                $typeDisplay = 'Global Assessment';
            } elseif (($assesment->type ?? '') === 'content') {
                $contentFor = $assesment->assesment_content_for ?? ($assesment->assessment_content_for ?? 'content');
                if ($contentFor === 'playlist') {
                    $typeDisplay = 'Content Assessment (Playlist)';
                } else {
                    $typeDisplay = 'Content Assessment (All Content)';
                }
            } elseif (!empty($assesment->type)) {
                $typeDisplay = ucwords(str_replace('_', ' ', $assesment->type)) . ' Assessment';
            }
        @endphp

        <div class="card border-0 shadow-sm rounded-4 mb-3">
            <div class="card-body">
                <div class="row align-items-center g-3">
                    <div class="col">
                        <div class="text-muted small mb-1">Assessment Result</div>
                        <h2 class="mb-1">{{ $assesment->title ?? '-' }}</h2>
                        <div class="text-muted">
                        The following are the assessment results you have submitted.
                        </div>
                    </div>

                    <div class="col-md-auto">
                        @if ($isPassed)
                            <span class="badge bg-green-lt text-green fs-4 px-3 py-2">Pass</span>
                        @else
                            <span class="badge bg-red-lt text-red fs-4 px-3 py-2">Failed</span>
                        @endif
                    </div>
                </div>
            </div>
        </div>

        <div class="row g-3">
            <div class="col-lg-5">
                <div class="card border-0 shadow-sm rounded-4 h-100">
                    <div class="card-body text-center d-flex flex-column justify-content-center">
                        <div class="text-muted mb-2">Your Score</div>

                        <div class="{{ $isPassed ? 'text-success' : 'text-danger' }}" style="font-size: 4rem; font-weight: 800; line-height: 1;">
                            {{ $score }}
                        </div>

                        <div class="mt-3">
                            <div id="chart"></div>
                        </div>

                        <div class="mt-3 text-muted">
                            Passing Score: <strong>{{ $passingGrade }}</strong>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-lg-7">
                <div class="card border-0 shadow-sm rounded-4 mb-3">
                    <div class="card-body">
                        <h3 class="mb-3">Detail Assessment</h3>

                        <div class="row g-3">
                            <div class="col-sm-6">
                                <div class="p-3 rounded-4 bg-blue-lt">
                                    <div class="text-muted small">Assessment</div>
                                    <div class="fw-bold">{{ $assesment->title ?? '-' }}</div>
                                </div>
                            </div>

                            <div class="col-sm-6">
                                <div class="p-3 rounded-4 bg-cyan-lt">
                                    <div class="text-muted small">Type</div>
                                    <div class="fw-bold">{{ $typeDisplay }}</div>
                                </div>
                            </div>

                            @if (($assesment->type ?? '') === 'global')
                                <div class="col-sm-6">
                                    <div class="p-3 rounded-4 bg-purple-lt">
                                        <div class="text-muted small">Batch</div>
                                        <div class="fw-bold">{{ $assesment_result->batch ?? $assesment->batch ?? '-' }}</div>
                                    </div>
                                </div>
                            @elseif (!empty($currentAttempt) && !empty($maxAttempts))
                                <div class="col-sm-6">
                                    <div class="p-3 rounded-4 bg-indigo-lt">
                                        <div class="text-muted small">Attempt</div>
                                        <div class="fw-bold">{{ $currentAttempt }} / {{ $maxAttempts }}</div>
                                    </div>
                                </div>
                            @endif

                            <div class="col-sm-6">
                                <div class="p-3 rounded-4 bg-orange-lt">
                                    <div class="text-muted small">Passing Grade</div>
                                    <div class="fw-bold">{{ $passingGrade }}</div>
                                </div>
                            </div>

                            <div class="col-sm-6">
                                <div class="p-3 rounded-4 bg-yellow-lt">
                                    <div class="text-muted small">AFK Duration</div>
                                    <div class="fw-bold">
                                        @php
                                            $afkSec = (int) ($assesment_result->afk_duration_seconds ?? 0);
                                            $afkFormatted = $afkSec >= 60 ? sprintf('%02dm %02ds', floor($afkSec / 60), $afkSec % 60) : ($afkSec . 's');
                                        @endphp
                                        {{ $afkFormatted }}
                                    </div>
                                </div>
                            </div>

                            <div class="col-12">
                                <div class="p-3 rounded-4 {{ $isPassed ? 'bg-green-lt' : 'bg-red-lt' }}">
                                    <div class="text-muted small">Status</div>
                                    <div class="fw-bold">
                                        {{ $isPassed ? 'Congratulations, you passed!' : 'Sorry, you have not passed this test.' }}
                                    </div>
                                </div>
                            </div>
                        </div>

                            @if (!$isPassed)
                                <div class="alert alert-warning mt-3 mb-0">
                                    If you did not pass and wish to retake the assessment, please wait for the next batch or contact the LMS Administrator.
                                </div>
                            @else
                                <div class="alert alert-success mt-3 mb-0">
                                    Congratulations! You have successfully passed this assessment.
                                </div>
                            @endif
                    </div>
                </div>

                <div class="card border-0 shadow-sm rounded-4">
                    <div class="card-body">
                        <h3 class="mb-3">Next Action</h3>

                        <div class="d-flex flex-wrap gap-2">
                            <a href="{{ url('/my-learning?learning_tab=assessment') }}" class="btn btn-primary rounded-3 px-3">
                                Back to Learning Assessment
                            </a>

                            <a href="{{ url('/') }}" class="btn btn-outline-secondary rounded-3 px-3">
                                Home
                            </a>

                            <a href="{{ url('/my-learning?learning_tab=record') }}" class="btn btn-outline-secondary rounded-3 px-3">
                                Learning Record
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>

    </div>
</div>

@include('components.shared.assessment-result-modal')

@endsection

@php
    $color = $isPassed
        ? 'tabler.getColor("success"), tabler.getColor("success", 0.8), tabler.getColor("success", 0.6), tabler.getColor("gray-300")'
        : 'tabler.getColor("danger"), tabler.getColor("danger", 0.8), tabler.getColor("danger", 0.6), tabler.getColor("gray-300")';
@endphp

@section('style')
<style>
    .rounded-4 {
        border-radius: 1rem !important;
    }

    [data-bs-theme=dark] .apexcharts-text {
        fill: #f6f8fb !important;
    }

    /* AFK Duration Card & Warning Badges Dark Mode */
    :is([data-bs-theme="dark"], [data-theme="dark"], body.theme-dark, body.dark-mode, body.dark) .bg-yellow-lt {
        background-color: rgba(245, 159, 0, 0.12) !important;
        border: 1px solid rgba(245, 159, 0, 0.28) !important;
        color: #fbbf24 !important;
    }

    :is([data-bs-theme="dark"], [data-theme="dark"], body.theme-dark, body.dark-mode, body.dark) .bg-yellow-lt .fw-bold {
        color: #fef08a !important;
    }
</style>
@endsection

@section('script')
<script src="{{ asset('Tabler/dist/libs/apexcharts/dist/apexcharts.min.js?1684106062') }}" defer></script>

<script>
document.addEventListener("DOMContentLoaded", function() {
    var options = {
        series: [{{ $score }}],
        chart: {
            height: 220,
            type: 'radialBar',
        },
        plotOptions: {
            radialBar: {
                hollow: {
                    size: '68%',
                },
                dataLabels: {
                    name: {
                        show: false
                    },
                    value: {
                        fontSize: '26px',
                        fontFamily: void 0,
                        fontWeight: 700,
                        color: void 0,
                        offsetY: 8
                    }
                }
            },
        },
        colors: [{!! $color !!}],
        labels: ['Score'],
    };

    var chart = new ApexCharts(document.querySelector("#chart"), options);
    chart.render();
});
</script>

@if (session('show_result_modal'))
<script>
document.addEventListener("DOMContentLoaded", function() {
    var modalEl = document.getElementById('assessmentResultModal');
    if (modalEl) {
        if (typeof bootstrap !== 'undefined' && bootstrap.Modal) {
            var bsModal = bootstrap.Modal.getOrCreateInstance(modalEl);
            bsModal.show();
        } else if (typeof jQuery !== 'undefined' && jQuery(modalEl).modal) {
            jQuery(modalEl).modal('show');
        }
    }
});
</script>
@endif
@endsection
