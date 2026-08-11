@extends('layouts.sidebar-master')

@section('content')
<div class="page-body manage-learning-page">
    <div class="container-xl">
        @include('main.learning.components.learning-header', ['variant' => 'dashboard'])

        <div class="row row-deck g-3 mb-4">
            <div class="col-md-6 col-xl-3">
                <div class="card stat-card stat-card-total border-0 shadow-sm h-100">
                    <div class="card-body">
                        <div class="text-uppercase small text-muted fw-semibold">Total Learning</div>
                        <div class="d-flex align-items-end justify-content-between mt-3">
                            <div class="display-6 mb-0">{{ number_format($totalLearning) }}</div>
                            <i class="ti ti-stack-2 fs-1 text-primary opacity-50"></i>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-md-6 col-xl-3">
                <div class="card stat-card stat-card-ongoing border-0 shadow-sm h-100">
                    <div class="card-body">
                        <div class="text-uppercase small text-muted fw-semibold">Ongoing</div>
                        <div class="d-flex align-items-end justify-content-between mt-3">
                            <div class="display-6 mb-0">{{ number_format($ongoing) }}</div>
                            <i class="ti ti-progress fs-1 text-warning opacity-50"></i>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-md-6 col-xl-3">
                <div class="card stat-card stat-card-finished border-0 shadow-sm h-100">
                    <div class="card-body">
                        <div class="text-uppercase small text-muted fw-semibold">Finished</div>
                        <div class="d-flex align-items-end justify-content-between mt-3">
                            <div class="display-6 mb-0">{{ number_format($finish) }}</div>
                            <i class="ti ti-circle-check fs-1 text-success opacity-50"></i>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-md-6 col-xl-3">
                <div class="card stat-card stat-card-rate border-0 shadow-sm h-100">
                    <div class="card-body">
                        <div class="text-uppercase small text-muted fw-semibold">Completion Rate</div>
                        <div class="d-flex align-items-end justify-content-between mt-3">
                            <div class="display-6 mb-0">{{ number_format($completionRate, 1) }}%</div>
                            <i class="ti ti-chart-pie-2 fs-1 text-danger opacity-50"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="card border-0 shadow-sm mb-4">
            <div class="card-header border-0 pb-0">
                <div>
                    <h3 class="card-title mb-1">Classification Summary</h3>
                    <div class="text-muted">Aggregate overview per classification, without looping through nested content records in Blade.</div>
                </div>
            </div>

            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-vcenter align-middle">
                        <thead>
                            <tr>
                                <th style="width: 60px;">No</th>
                                <th>Classification</th>
                                <th class="text-center">Content</th>
                                <th class="text-center">Total Learning</th>
                                <th class="text-center">Learning Hours</th>
                                <th class="text-center">Ongoing</th>
                                <th class="text-center">Finished</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($classification as $list)
                                <tr>
                                    <td>{{ $loop->iteration }}</td>
                                    <td>
                                        @if($list->slug)
                                            <a href="{{ route('learning.manage.category', $list->slug) }}"  class="fw-bold text-primary text-decoration-underline">
                                                {{ $list->classification_name }}
                                            </a>
                                        @else
                                            <span class="fw-semibold">{{ $list->classification_name }}
                                            </span> {{ number_format($list->learning_hours, 1) }} Hours
                                        @endif
                                    </td>
                                    <td class="text-center">{{ number_format((int) ($list->content_total ?? 0)) }}</td>
                                    <td class="text-center fw-semibold">{{ number_format((int) ($list->total_learning ?? 0)) }}</td>
                                    <td class="text-center fw-semibold">{{ number_format($list->learning_hours, 1) }} Hours</td>
                                    <td class="text-center text-warning fw-semibold">{{ number_format((int) ($list->ongoing_learning ?? 0)) }}</td>
                                    <td class="text-center text-success fw-semibold">{{ number_format((int) ($list->finished_learning ?? 0)) }}</td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="6" class="text-center text-muted py-4">No classification data found.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@section('style')
<style>
    .manage-learning-page {
        background: #fff;
    }

    [data-bs-theme="dark"] .manage-learning-page,
    body.dark-mode .manage-learning-page,
    body.theme-dark .manage-learning-page {
        background: var(--tblr-body-bg, #111827);
    }

    .manage-learning-page .stat-card {
        border-radius: 18px;
    }

    .manage-learning-page .stat-card .display-6 {
        line-height: 1;
    }

    .manage-learning-page .stat-card-total {
        background: linear-gradient(180deg, #eef4ff 0%, #ffffff 100%);
    }

    .manage-learning-page .stat-card-ongoing {
        background: linear-gradient(180deg, #fff7e6 0%, #ffffff 100%);
    }

    .manage-learning-page .stat-card-finished {
        background: linear-gradient(180deg, #edfdf2 0%, #ffffff 100%);
    }

    .manage-learning-page .stat-card-rate {
        background: linear-gradient(180deg, #fff0f0 0%, #ffffff 100%);
    }
</style>
@endsection
