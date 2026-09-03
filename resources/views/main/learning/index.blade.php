@extends('layouts.sidebar-master')

@section('content')
<div class="page-body manage-learning-page">
    <div class="container-xl">
        <!-- TOMBOL BACK RATA KIRI ATAS -->
        <div class="mb-3">
            <a href="{{ route('manage.index') }}" class="btn-back mb-3 d-inline-flex align-items-center gap-1 text-decoration-none">
                <i class="ti ti-arrow-left"></i>
                <span>Back</span>
            </a>
        </div>

        @include('main.learning.components.learning-header', ['variant' => 'dashboard'])

        <div class="row g-3 mb-4">
            <!-- Card 1: Total Learning -->
            <div class="col-md-3">
                <div class="card border-0 shadow-sm p-3 rounded-3 bg-white">
                    <div class="d-flex align-items-center">
                        <div class="rounded-3 d-flex align-items-center justify-content-center flex-shrink-0 me-3" 
                             style="width: 44px; height: 44px; background-color: #0f4c81; color: #ffffff;">
                            <i class="ti ti-stack-2 fs-2"></i>
                        </div>
                        <div>
                            <div class="text-muted fw-semibold" style="font-size: 0.8rem; line-height: 1.2;">Total Learning</div>
                            <div class="fw-bold fs-3 text-dark mt-1" style="line-height: 1.1;">{{ number_format($totalLearning ?? 0) }}</div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Card 2: Ongoing -->
            <div class="col-md-3">
                <div class="card border-0 shadow-sm p-3 rounded-3 bg-white">
                    <div class="d-flex align-items-center">
                        <div class="rounded-3 d-flex align-items-center justify-content-center flex-shrink-0 me-3" 
                             style="width: 44px; height: 44px; background-color: #f59e0b; color: #ffffff;">
                            <i class="ti ti-progress fs-2"></i>
                        </div>
                        <div>
                            <div class="text-muted fw-semibold" style="font-size: 0.8rem; line-height: 1.2;">Ongoing</div>
                            <div class="fw-bold fs-3 text-dark mt-1" style="line-height: 1.1;">{{ number_format($ongoing ?? 0) }}</div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Card 3: Finished -->
            <div class="col-md-3">
                <div class="card border-0 shadow-sm p-3 rounded-3 bg-white">
                    <div class="d-flex align-items-center">
                        <div class="rounded-3 d-flex align-items-center justify-content-center flex-shrink-0 me-3" 
                             style="width: 44px; height: 44px; background-color: #38b000; color: #ffffff;">
                            <i class="ti ti-circle-check fs-2"></i>
                        </div>
                        <div>
                            <div class="text-muted fw-semibold" style="font-size: 0.8rem; line-height: 1.2;">Finished</div>
                            <div class="fw-bold fs-3 text-dark mt-1" style="line-height: 1.1;">{{ number_format($finish ?? 0) }}</div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Card 4: Completion Rate -->
            <div class="col-md-3">
                <div class="card border-0 shadow-sm p-3 rounded-3 bg-white">
                    <div class="d-flex align-items-center">
                        <div class="rounded-3 d-flex align-items-center justify-content-center flex-shrink-0 me-3" 
                             style="width: 44px; height: 44px; background-color: #0284c7; color: #ffffff;">
                            <i class="ti ti-chart-pie-2 fs-2"></i>
                        </div>
                        <div>
                            <div class="text-muted fw-semibold" style="font-size: 0.8rem; line-height: 1.2;">Completion Rate</div>
                            <div class="fw-bold fs-3 text-dark mt-1" style="line-height: 1.1;">{{ number_format($completionRate ?? 0, 1) }}%</div>
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

    .btn-back {
        color: #0c5b9e !important;
        font-weight: 500;
        transition: transform 0.2s ease, opacity 0.2s ease;
    }
    .btn-back:hover {
        opacity: 0.85;
        transform: translateX(-3px);
    }
</style>
@endsection
