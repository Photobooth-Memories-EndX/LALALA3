@extends('layouts.sidebar-master')

@section('content')

<div class="page-body">
    <div class="container-xl">

        {{-- Mandatory Preview Result Banner --}}
        <div class="alert alert-warning border-0 rounded-4 shadow-sm mb-3 d-flex flex-wrap align-items-center justify-content-between p-3 gap-3">
            <div class="d-flex align-items-center gap-3">
                <div class="p-2 rounded-3 bg-warning-subtle text-warning d-flex align-items-center justify-content-center" style="width: 44px; height: 44px; min-width: 44px;">
                    <i class="ti ti-eye fs-1"></i>
                </div>
                <div>
                    <strong class="d-block text-dark fw-bold fs-3">THIS IS A PREVIEW RESULT</strong>
                    <span class="small text-muted">This result is temporary and has not been saved to the database.</span>
                </div>
            </div>
            <div class="d-flex flex-wrap gap-2">
                <a href="{{ route('assesment.preview', $assesment->slug) }}" class="btn btn-outline-primary rounded-3 px-3 py-2 fw-semibold">
                    <i class="ti ti-rotate-clockwise me-1"></i> Back to Preview
                </a>
                <a href="{{ route('assesment.index') }}" class="btn btn-primary rounded-3 px-3 py-2 fw-semibold">
                    <i class="ti ti-arrow-left me-1"></i> Back to Assessment Management
                </a>
            </div>
        </div>

        @php
            $score = $resultData['score'] ?? 0;
            $passingGrade = $resultData['passing_grade'] ?? 0;
            $isPassed = $resultData['is_passed'] ?? false;

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

        {{-- Assessment Title & Status Header --}}
        <div class="card border-0 shadow-sm rounded-4 mb-3">
            <div class="card-body">
                <div class="row align-items-center g-3">
                    <div class="col">
                        <div class="text-muted small mb-1">Assessment Preview Result</div>
                        <h2 class="mb-1">{{ $assesment->title ?? '-' }}</h2>
                        <div class="text-muted">
                            Simulation of assessment score based on answers selected during preview mode.
                        </div>
                    </div>

                    <div class="col-md-auto">
                        @if ($isPassed)
                            <span class="badge bg-green-lt text-green fs-4 px-3 py-2">
                                <i class="ti ti-circle-check me-1"></i> PASSED
                            </span>
                        @else
                            <span class="badge bg-red-lt text-red fs-4 px-3 py-2">
                                <i class="ti ti-circle-x me-1"></i> FAILED
                            </span>
                        @endif
                    </div>
                </div>
            </div>
        </div>

        {{-- Score & Metrics Summary --}}
        <div class="row g-3 mb-3">
            <div class="col-lg-4">
                <div class="card border-0 shadow-sm rounded-4 h-100">
                    <div class="card-body text-center d-flex flex-column justify-content-center p-4">
                        <div class="text-muted small fw-semibold text-uppercase mb-2">Simulated Score</div>

                        <div class="{{ $isPassed ? 'text-success' : 'text-danger' }}" style="font-size: 3.8rem; font-weight: 800; line-height: 1;">
                            {{ $score }}
                        </div>

                        <div class="mt-2">
                            <div id="chartPreviewScore"></div>
                        </div>

                        <div class="mt-3 text-muted">
                            Passing Grade: <strong>{{ $passingGrade }}</strong>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-lg-8">
                <div class="card border-0 shadow-sm rounded-4 h-100">
                    <div class="card-body">
                        <h3 class="mb-3">Preview Performance Summary</h3>

                        <div class="row g-3">
                            <div class="col-sm-6 col-md-3">
                                <div class="p-3 rounded-4 bg-blue-lt h-100">
                                    <div class="text-muted small">Total Questions</div>
                                    <div class="h2 mb-0 fw-bold">{{ $resultData['total_questions'] ?? 0 }}</div>
                                </div>
                            </div>

                            <div class="col-sm-6 col-md-3">
                                <div class="p-3 rounded-4 bg-green-lt h-100">
                                    <div class="text-muted small">Correct Answers</div>
                                    <div class="h2 mb-0 fw-bold text-success">{{ $resultData['correct_count'] ?? 0 }}</div>
                                </div>
                            </div>

                            <div class="col-sm-6 col-md-3">
                                <div class="p-3 rounded-4 bg-red-lt h-100">
                                    <div class="text-muted small">Incorrect Answers</div>
                                    <div class="h2 mb-0 fw-bold text-danger">{{ $resultData['incorrect_count'] ?? 0 }}</div>
                                </div>
                            </div>

                            <div class="col-sm-6 col-md-3">
                                <div class="p-3 rounded-4 bg-yellow-lt h-100">
                                    <div class="text-muted small">Unanswered</div>
                                    <div class="h2 mb-0 fw-bold text-warning">{{ $resultData['unanswered_count'] ?? 0 }}</div>
                                </div>
                            </div>

                            <div class="col-sm-6">
                                <div class="p-3 rounded-4 bg-cyan-lt">
                                    <div class="text-muted small">Assessment Type</div>
                                    <div class="fw-bold">{{ $typeDisplay }}</div>
                                </div>
                            </div>

                            <div class="col-sm-6">
                                <div class="p-3 rounded-4 bg-orange-lt">
                                    <div class="text-muted small">Passing Grade Requirement</div>
                                    <div class="fw-bold">{{ $passingGrade }}</div>
                                </div>
                            </div>

                            <div class="col-12">
                                <div class="p-3 rounded-4 {{ $isPassed ? 'bg-green-lt' : 'bg-red-lt' }}">
                                    <div class="text-muted small">Simulation Outcome</div>
                                    <div class="fw-bold">
                                        {{ $isPassed ? 'The preview score meets or exceeds the passing grade (Passed).' : 'The preview score is below the required passing grade (Failed).' }}
                                    </div>
                                </div>
                            </div>
                        </div>

                    </div>
                </div>
            </div>
        </div>

        {{-- Answer Review Table --}}
        <div class="card border-0 shadow-sm rounded-4 mb-3">
            <div class="card-header bg-transparent border-bottom pt-3 pb-3">
                <div class="d-flex align-items-center justify-content-between">
                    <div>
                        <h3 class="card-title mb-0 fw-bold">
                            <i class="ti ti-list-check me-2 text-primary"></i> Preview Answer Review
                        </h3>
                        <div class="text-muted small">Detailed comparison of preview answers versus official answer keys.</div>
                    </div>
                    <span class="badge bg-info-lt">
                        {{ count($resultData['reviews'] ?? []) }} Questions Evaluated
                    </span>
                </div>
            </div>

            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-vcenter card-table table-hover">
                        <thead class="bg-light">
                            <tr>
                                <th style="width: 60px;" class="text-center">No</th>
                                <th>Question</th>
                                <th style="min-width: 180px;">Preview Answer</th>
                                <th style="min-width: 180px;">Official Answer Key</th>
                                <th style="width: 130px;" class="text-center">Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($resultData['reviews'] ?? [] as $review)
                                <tr>
                                    <td class="text-center fw-bold text-muted">{{ $review['number'] }}</td>
                                    <td>
                                        <div class="fw-medium text-dark mb-1">
                                            {!! $review['question'] !!}
                                        </div>
                                        @if (!empty($review['image']))
                                            @php
                                                $imgSrc = asset('storage/assesment/' . $review['image']);
                                                if (!file_exists(public_path('storage/assesment/' . $review['image']))) {
                                                    $imgSrc = asset('storage/question_bank/image/' . $review['image']);
                                                }
                                            @endphp
                                            <div class="mt-2">
                                                <img src="{{ $imgSrc }}" alt="Question Image" class="rounded border" style="max-height: 120px;">
                                            </div>
                                        @endif
                                    </td>
                                    <td>
                                        @if ($review['status'] === 'unanswered')
                                            <span class="badge bg-secondary-lt text-secondary">
                                                <i class="ti ti-help me-1"></i> Not Answered
                                            </span>
                                        @elseif ($review['is_correct'])
                                            <span class="text-success fw-semibold">
                                                <i class="ti ti-check me-1"></i> {{ $review['user_answer'] }}
                                            </span>
                                        @else
                                            <span class="text-danger fw-semibold">
                                                <i class="ti ti-x me-1"></i> {{ $review['user_answer'] }}
                                            </span>
                                        @endif
                                    </td>
                                    <td>
                                        <span class="text-dark fw-bold">
                                            <i class="ti ti-key me-1 text-primary"></i> {{ $review['correct_answer'] }}
                                        </span>
                                    </td>
                                    <td class="text-center">
                                        @if ($review['status'] === 'correct')
                                            <span class="badge bg-green-lt text-green fs-5 px-2 py-1">
                                                <i class="ti ti-circle-check me-1"></i> Correct
                                            </span>
                                        @elseif ($review['status'] === 'incorrect')
                                            <span class="badge bg-red-lt text-red fs-5 px-2 py-1">
                                                <i class="ti ti-circle-x me-1"></i> Incorrect
                                            </span>
                                        @else
                                            <span class="badge bg-yellow-lt text-yellow fs-5 px-2 py-1">
                                                <i class="ti ti-alert-circle me-1"></i> Unanswered
                                            </span>
                                        @endif
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="5" class="text-center text-muted py-4">
                                        No questions were found in this assessment.
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>

            <div class="card-footer bg-light d-flex flex-wrap justify-content-between align-items-center gap-2">
                <div class="text-muted small">
                    <i class="ti ti-info-circle me-1"></i> This is a preview simulation. No assessment attempt was recorded in the database.
                </div>
                <div class="d-flex gap-2">
                    <a href="{{ route('assesment.preview', $assesment->slug) }}" class="btn btn-outline-primary rounded-3 px-3">
                        <i class="ti ti-rotate-clockwise me-1"></i> Try Preview Again
                    </a>
                    <a href="{{ route('assesment.index') }}" class="btn btn-primary rounded-3 px-3">
                        <i class="ti ti-arrow-left me-1"></i> Assessment Management
                    </a>
                </div>
            </div>
        </div>

    </div>
</div>

@endsection

@php
    $radialColor = $isPassed
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
    if (typeof ApexCharts === 'undefined') return;

    var chartEl = document.querySelector("#chartPreviewScore");
    if (!chartEl) return;

    var options = {
        series: [{{ $score }}],
        chart: {
            height: 200,
            type: 'radialBar',
        },
        plotOptions: {
            radialBar: {
                hollow: {
                    size: '65%',
                },
                dataLabels: {
                    name: {
                        show: false
                    },
                    value: {
                        fontSize: '24px',
                        fontFamily: void 0,
                        fontWeight: 700,
                        color: void 0,
                        offsetY: 8
                    }
                }
            },
        },
        colors: [{!! $radialColor !!}],
        labels: ['Preview Score'],
    };

    var chart = new ApexCharts(chartEl, options);
    chart.render();
});
</script>
@endsection
