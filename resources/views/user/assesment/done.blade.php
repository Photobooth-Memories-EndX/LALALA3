@extends('layouts.sidebar-master')

@section('content')

<div class="page-body">
    <div class="container-xl">

        @php
            $score = (int) ($assesment_result->score ?? 0);
            $passingGrade = (int) ($assesment->passing_grade ?? 0);
            $isPassed = $score >= $passingGrade;
        @endphp

        <div class="card border-0 shadow-sm rounded-4 mb-3 overflow-hidden">
            <div class="card-status-top {{ $isPassed ? 'bg-success' : 'bg-danger' }}"></div>

            <div class="card-body">
                <div class="row align-items-center g-3">
                    <div class="col-auto">
                        <span class="avatar avatar-xl rounded-circle {{ $isPassed ? 'bg-green-lt text-green' : 'bg-red-lt text-red' }}">
                            @if ($isPassed)
                                ✓
                            @else
                                !
                            @endif
                        </span>
                    </div>

                    <div class="col">
                        <div class="text-muted small mb-1">Assessment Status</div>
                        <h2 class="mb-1">
                            You have already taken this assessment.
                        </h2>
                        <div class="text-muted">
                            {{ $assesment->title ?? '-' }}
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
                        <div class="text-muted mb-2">Your Best Score</div>

                        <div class="{{ $isPassed ? 'text-success' : 'text-danger' }}" style="font-size: 4rem; font-weight: 800; line-height: 1;">
                            {{ $score }}
                        </div>

                        <div class="mt-3">
                            <div id="chart"></div>
                        </div>

                        <div class="mt-3">
                            <span class="badge bg-blue-lt text-blue">
                                Passing Score: {{ $passingGrade }}
                            </span>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-lg-7">
                <div class="card border-0 shadow-sm rounded-4 mb-3">
                    <div class="card-body">
                        <h3 class="mb-3">Result Summary</h3>

                        <div class="row g-3">
                            <div class="col-sm-6">
                                <div class="p-3 rounded-4 bg-blue-lt">
                                    <div class="text-muted small">Assessment</div>
                                    <div class="fw-bold">{{ $assesment->title ?? '-' }}</div>
                                </div>
                            </div>

                            <div class="col-sm-6">
                                <div class="p-3 rounded-4 bg-purple-lt">
                                    <div class="text-muted small">Batch</div>
                                    <div class="fw-bold">{{ $assesment_result->batch ?? $assesment->batch ?? '-' }}</div>
                                </div>
                            </div>

                            <div class="col-sm-6">
                                <div class="p-3 rounded-4 bg-orange-lt">
                                    <div class="text-muted small">Score</div>
                                    <div class="fw-bold">{{ $score }}</div>
                                </div>
                            </div>

                            <div class="col-sm-6">
                                <div class="p-3 rounded-4 {{ $isPassed ? 'bg-green-lt' : 'bg-red-lt' }}">
                                    <div class="text-muted small">Result</div>
                                    <div class="fw-bold">
                                        {{ $isPassed ? 'Completed / Pass' : 'Completed / Failed' }}
                                    </div>
                                </div>
                            </div>
                        </div>

                        @if ($isPassed)
                            <div class="alert alert-success mt-3 mb-0">
                                🎉 Congratulations. You passed this assessment.
                            </div>
                        @else
                            <div class="alert alert-warning mt-3 mb-0">
                                Sorry, you have not passed this test. If you want to take remedial, please wait for the next batch.
                            </div>
                        @endif
                    </div>
                </div>

                <div class="card border-0 shadow-sm rounded-4">
                    <div class="card-body">
                        <h3 class="mb-3">Next Action</h3>

                        <div class="d-flex flex-wrap gap-2">
                            <a href="{{ route('learning.assesment') }}" class="btn btn-primary">
                                Back to Assessment
                            </a>

                            <a href="{{ route('learning.ongoing') }}" class="btn btn-white">
                                Learning Dashboard
                            </a>

                            @if (Route::has('learning.record'))
                                <a href="{{ route('learning.record') }}" class="btn btn-white">
                                    Learning Record
                                </a>
                            @endif
                        </div>
                    </div>
                </div>
            </div>
        </div>

    </div>
</div>

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
@endsection