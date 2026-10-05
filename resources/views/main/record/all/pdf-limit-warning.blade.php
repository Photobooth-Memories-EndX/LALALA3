@extends('layouts.sidebar-master')

@section('content')
<div class="page-body manage-record-page">
    <div class="container-xl">
        <!-- Back Navigation -->
        <div class="mb-3">
            <a href="{{ route('manage.record.all.classification', array_merge(['classification' => $classification], $requestParams ?? [])) }}"
               class="btn btn-outline-secondary d-inline-flex align-items-center gap-1 rounded-3 text-decoration-none">
                <i class="ti ti-arrow-left"></i>
                <span>Back to Classification Records</span>
            </a>
        </div>

        <div class="row justify-content-center">
            <div class="col-12 col-lg-8">
                <!-- Limit Exceeded Alert Card -->
                <div class="card border-0 shadow-sm rounded-4 overflow-hidden mb-4">
                    <div class="card-status-top bg-warning" style="height: 4px;"></div>
                    <div class="card-body p-4 p-md-5 text-center">
                        <div class="rounded-circle d-inline-flex align-items-center justify-content-center mb-3"
                             style="width: 72px; height: 72px; background-color: #fef3c7; color: #d97706;">
                            <i class="ti ti-file-alert fs-1" style="font-size: 2.2rem;"></i>
                        </div>

                        <h2 class="h2 fw-bold text-dark mb-2">PDF Export Limit Exceeded</h2>
                        <p class="text-muted mb-4" style="max-width: 580px; margin: 0 auto; font-size: 0.95rem;">
                            PDF Landscape export is designed for printable documents (A2 Landscape, 46 columns) and is restricted to a maximum of
                            <strong>{{ number_format($limit) }} records</strong> to maintain document readability and avoid browser memory exhaustion.
                        </p>

                        <!-- Counter Stat Box -->
                        <div class="p-3 rounded-3 mb-4 d-inline-block text-start"
                             style="background-color: #f8fafc; border: 1px solid #e2e8f0; min-width: 320px;">
                            <div class="d-flex align-items-center justify-content-between gap-3 mb-1">
                                <span class="text-muted small">Classification:</span>
                                <span class="fw-bold text-dark">{{ $classificationName }}</span>
                            </div>
                            <div class="d-flex align-items-center justify-content-between gap-3 mb-1">
                                <span class="text-muted small">Current Filtered Records:</span>
                                <span class="badge bg-danger fs-6">{{ number_format($totalRecords) }} records</span>
                            </div>
                            <div class="d-flex align-items-center justify-content-between gap-3">
                                <span class="text-muted small">Maximum PDF Limit:</span>
                                <span class="badge bg-secondary fs-6">{{ number_format($limit) }} records</span>
                            </div>
                        </div>

                        <div class="text-muted small mb-4">
                            Please choose an alternative format below for the complete dataset, or return to the table and refine your filters to under {{ $limit }} records.
                        </div>

                        <!-- Action Buttons -->
                        <div class="d-flex align-items-center justify-content-center flex-wrap gap-2">
                            <a href="{{ route('manage.record.all.classification.download', array_merge(['classification' => $classification, 'format' => 'xlsx'], $requestParams ?? [])) }}"
                               class="btn btn-success rounded-3 px-4 py-2 d-inline-flex align-items-center gap-2">
                                <i class="ti ti-file-spreadsheet fs-2"></i>
                                <span>Download as Excel (.xlsx)</span>
                            </a>

                            <a href="{{ route('manage.record.all.classification.download', array_merge(['classification' => $classification, 'format' => 'csv'], $requestParams ?? [])) }}"
                               class="btn btn-outline-primary rounded-3 px-4 py-2 d-inline-flex align-items-center gap-2">
                                <i class="ti ti-file-text fs-2"></i>
                                <span>Download as CSV (.csv)</span>
                            </a>

                            <a href="{{ route('manage.record.all.classification', array_merge(['classification' => $classification], $requestParams ?? [])) }}"
                               class="btn btn-outline-secondary rounded-3 px-4 py-2 d-inline-flex align-items-center gap-2">
                                <i class="ti ti-adjustments-horizontal fs-2"></i>
                                <span>Refine Filters</span>
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
