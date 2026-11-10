@extends('layouts.sidebar-master')

@section('title', 'Import Batch Detail — Offline Training — OWL LMS')

@section('style')
    @include('main.content.components.content-styles')
@endsection

@push('styles')
    <link rel="stylesheet" href="{{ asset('css/dataTables.bootstrap5.min.css') }}">
    <link rel="stylesheet" href="{{ asset('css/manage/record/offline/import-history-detail.css') }}">
@endpush

@section('content')
    @include('main.monitoring.components.style')

    {{-- Page Header --}}
    <div class="page-header d-print-none mb-0 pb-0">
        <div class="container-xl">
            <!-- Breadcrumb Navigation -->
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb breadcrumb-arrows mb-2">
                    <li class="breadcrumb-item"><a href="{{ route('manage.record.offline') }}">Manage Record</a></li>
                    <li class="breadcrumb-item"><a href="{{ route('manage.record.offline') }}">Offline</a></li>
                    <li class="breadcrumb-item"><a href="{{ route('manage.record.offline.import-history.index') }}" class="btn-smart-back">Import History</a></li>
                    <li class="breadcrumb-item active" aria-current="page">Batch Detail</li>
                </ol>
            </nav>

            <!-- Back Navigation Link -->
            <div class="mb-3">
                <a href="{{ route('manage.record.offline.import-history.index') }}" class="btn-smart-back text-decoration-none text-muted small d-inline-flex align-items-center gap-1">
                    <i class="ti ti-arrow-left fs-3"></i><span>Back to Import History</span>
                </a>
            </div>

            <!-- Import History Detail Hero Header Box -->
            <div class="card border-0 shadow-sm p-4 mb-3 rounded-3 detail-hero-card">
                <div class="d-flex flex-column flex-md-row align-items-md-center justify-content-between gap-3">
                    <div class="d-flex align-items-center gap-3">
                        <div class="detail-hero-icon rounded-3 d-flex align-items-center justify-content-center flex-shrink-0">
                            <i class="ti ti-file-analytics" style="font-size: 2rem !important;"></i>
                        </div>
                        <div>
                            <div class="d-flex align-items-center gap-2 flex-wrap">
                                <h2 class="h3 fw-bold mb-0 text-dark">Import Batch Detail</h2>
                                <span class="badge bg-secondary-lt text-dark font-monospace fs-5 px-2.5 py-1" id="detail-batch-uuid">{{ $batch->batch_uuid }}</span>
                                <button type="button" class="btn btn-sm btn-ghost-secondary px-1.5 py-0.5" id="btn-copy-uuid" title="Copy Batch UUID" aria-label="Copy Batch UUID">
                                    <i class="ti ti-copy fs-3"></i>
                                </button>
                            </div>
                            <p class="text-muted small mb-0 mt-1">Audit log records, validation evaluation statistics, and export for import batch <span class="fw-bold font-monospace text-dark">{{ substr($batch->batch_uuid, 0, 8) }}...</span></p>
                        </div>
                    </div>
                    <div class="flex-shrink-0 d-flex flex-wrap align-items-center gap-2">
                        <a href="{{ route('manage.record.offline.import-history.index') }}" class="btn btn-outline-secondary px-3 py-2 fw-semibold d-inline-flex align-items-center gap-2 btn-smart-back">
                            <i class="ti ti-arrow-left fs-2"></i><span>Back to History</span>
                        </a>
                        @if($batch->hasStoredFile())
                            <a href="{{ route('manage.record.offline.import-history.download', $batch->batch_uuid) }}" class="btn btn-outline-primary px-3 py-2 fw-semibold d-inline-flex align-items-center gap-2" title="Download Original File from Storage">
                                <i class="ti ti-download fs-2"></i><span>Original File</span>
                            </a>
                        @endif
                        <button type="button" class="btn btn-success px-3 py-2 fw-semibold d-inline-flex align-items-center gap-2 btn-trigger-export" title="Download Excel with active filters">
                            <i class="ti ti-file-spreadsheet fs-2"></i><span>Download Excel</span>
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- Page Body --}}
    <div class="page-body mt-0 pt-0">
        <div class="container-xl">
            {{-- Batch Information Card --}}
            @include('manage.record.offline.import-history.partials.batch-info-card')

            {{-- Evaluation & Persistence Metrics Cards (Exactly 6 Cards) --}}
            @include('manage.record.offline.import-history.partials.metrics-cards')

            {{-- Spreadsheet Rows Audit Log Card --}}
            @include('manage.record.offline.import-history.partials.audit-log-card')
        </div>
    </div>
@endsection

@push('scripts')
<script>
    window.detailConfig = {
        batchUuid: "{{ $batch->batch_uuid }}",
        routes: {
            rows: "{{ route('manage.record.offline.import-history.rows', $batch->batch_uuid) }}",
            export: "{{ route('manage.record.offline.import-history.export', $batch->batch_uuid) }}",
            download: "{{ route('manage.record.offline.import-history.download', $batch->batch_uuid) }}"
        }
    };
</script>
<script src="{{ asset('js/jquery.dataTables.min.js') }}"></script>
<script src="{{ asset('js/dataTables.bootstrap5.min.js') }}"></script>
<script src="{{ asset('js/manage/record/offline/import-history-detail.js') }}"></script>
@endpush
