@extends('layouts.sidebar-master')

@section('title', 'Import History — Offline Training — OWL LMS')

@section('style')
    @include('main.content.components.content-styles')
@endsection

@push('styles')
    <link rel="stylesheet" href="{{ asset('css/dataTables.bootstrap5.min.css') }}">
    <link rel="stylesheet" href="{{ asset('css/manage/import-history.css') }}">
@endpush

@section('content')
    @include('main.monitoring.components.style')

    {{-- Page Header --}}
    <div class="page-header d-print-none mb-0 pb-0">
        <div class="container-xl">
            <!-- Back Navigation Link -->
            <div class="mb-3">
                <a href="{{ route('manage.record.offline') }}" class="text-decoration-none text-muted small d-inline-flex align-items-center gap-1">
                    <i class="ti ti-arrow-left fs-3"></i><span>Back to Offline Records</span>
                </a>
            </div>

            <!-- Import History Hero Header Box -->
            <div class="card border-0 shadow-sm p-4 mb-3 rounded-3 content-hero-card">
                <div class="d-flex flex-column flex-md-row align-items-md-center justify-content-between gap-3">
                    <div class="d-flex align-items-center gap-3">
                        <div class="content-hero-icon rounded-3 d-flex align-items-center justify-content-center flex-shrink-0">
                            <i class="ti ti-history" style="font-size: 2rem !important;"></i>
                        </div>
                        <div>
                            <h2 class="h3 fw-bold mb-1 text-dark">Import History</h2>
                            <p class="text-muted small mb-0">Centralized audit trail of PTKP and Non-PTKP spreadsheet imports, including validation logs and original file downloads.</p>
                        </div>
                    </div>
                    <div class="flex-shrink-0 d-flex flex-wrap align-items-center gap-2">
                        <a href="{{ route('manage.record.offline.import-ptkp') }}" class="btn btn-outline-primary px-3 py-2 fw-semibold d-inline-flex align-items-center gap-2" id="btn-goto-ptkp">
                            <i class="ti ti-file-upload fs-2"></i><span>Import PTKP</span>
                        </a>
                        <a href="{{ route('manage.record.offline.import-non-ptkp') }}" class="btn btn-primary px-3 py-2 fw-semibold d-inline-flex align-items-center gap-2" style="background-color: #f76707; border-color: #f76707;" id="btn-goto-non-ptkp">
                            <i class="ti ti-file-spreadsheet fs-2"></i><span>Import Non-PTKP</span>
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- Page Body --}}
    <div class="page-body mt-0 pt-0">
        <div class="container-xl">
            {{-- Statistics Cards --}}
            @include('manage.record.offline.partials.history-summary-cards')

            {{-- Quick Filter Card --}}
            <div class="card border-0 shadow-sm rounded-4 mb-4 import-filter-card">
                <div class="card-body p-3 p-md-4">
                    <div class="text-secondary small fw-semibold mb-2" style="color: #6c757d !important; font-size: 0.85rem;">
                        Quick Filter
                    </div>

                    <form id="import-history-filter-form" onsubmit="return false;">
                        <div class="row g-2 g-md-3 align-items-end">
                            <div class="col-12 col-sm-6 col-md-4 col-lg-2">
                                <label class="form-label small text-muted mb-1" for="filter-source-type">Source Type</label>
                                <select class="form-select rounded-3" id="filter-source-type">
                                    <option value="">All Sources</option>
                                    <option value="PTKP">PTKP</option>
                                    <option value="NON_PTKP">Non-PTKP</option>
                                </select>
                            </div>
                            <div class="col-12 col-sm-6 col-md-4 col-lg-2">
                                <label class="form-label small text-muted mb-1" for="filter-status">Status</label>
                                <select class="form-select rounded-3" id="filter-status">
                                    <option value="">All Statuses</option>
                                    <option value="COMPLETED">Success</option>
                                    <option value="COMPLETED_WITH_WARNINGS">Warning</option>
                                    <option value="FAILED">Failed</option>
                                    <option value="IMPORTING">Processing</option>
                                </select>
                            </div>
                            <div class="col-12 col-sm-6 col-md-4 col-lg-2">
                                <label class="form-label small text-muted mb-1" for="filter-user">Imported By</label>
                                <select class="form-select rounded-3" id="filter-user">
                                    <option value="">All Users</option>
                                    @foreach($users as $user)
                                        <option value="{{ $user->id }}">{{ $user->name ?? $user->email }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-12 col-sm-6 col-md-4 col-lg-2">
                                <label class="form-label small text-muted mb-1" for="filter-date-start">Date From</label>
                                <input type="date" class="form-control rounded-3" id="filter-date-start">
                            </div>
                            <div class="col-12 col-sm-6 col-md-4 col-lg-2">
                                <label class="form-label small text-muted mb-1" for="filter-date-end">Date To</label>
                                <input type="date" class="form-control rounded-3" id="filter-date-end">
                            </div>
                            <div class="col-12 col-sm-6 col-md-4 col-lg-2">
                                <label class="form-label small text-muted mb-1" for="filter-search">Search</label>
                                <div class="input-icon">
                                    <span class="input-icon-addon"><i class="ti ti-search"></i></span>
                                    <input type="text" class="form-control rounded-3" id="filter-search" placeholder="Search filename / UUID..." autocomplete="off">
                                </div>
                            </div>
                            <div class="col-12 d-flex justify-content-end gap-2 mt-3 pt-1">
                                <button type="button" class="btn btn-outline-secondary px-3 rounded-3 d-inline-flex align-items-center gap-1" id="btn-reset-filters" title="Reset Filters">
                                    <i class="ti ti-refresh fs-3"></i><span>Reset</span>
                                </button>
                                <button type="button" class="btn btn-primary px-3 rounded-3 d-inline-flex align-items-center gap-1" id="btn-apply-filters" title="Apply Filters">
                                    <i class="ti ti-filter fs-3"></i><span>Apply Filters</span>
                                </button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>

            {{-- Import Batches Table Card --}}
            <div class="card owl-main-card mb-4 import-table-card">
                <div class="card-header d-flex flex-column flex-md-row align-items-md-center justify-content-between gap-2 py-3 px-4">
                    <div>
                        <h3 class="card-title fw-bold text-dark mb-0">Import Batches</h3>
                        <div class="card-subtitle text-muted mt-1 small" id="import-batches-counter">
                            Showing import batches and audit details
                        </div>
                    </div>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover owl-table mb-0" id="import-batches-table" style="width: 100%;">
                            <thead>
                                <tr>
                                    <th class="text-center" style="width: 40px;">No</th>
                                    <th>Batch UUID</th>
                                    <th class="text-center">Type</th>
                                    <th>Original Filename</th>
                                    <th>Imported By</th>
                                    <th class="text-center">Started At</th>
                                    <th class="text-center">Finished At</th>
                                    <th class="text-center">Total</th>
                                    <th class="text-center">Imported</th>
                                    <th class="text-center">Skipped</th>
                                    <th class="text-center">Failed</th>
                                    <th class="text-center">Status</th>
                                    <th class="text-center" style="width: 90px;">Actions</th>
                                </tr>
                            </thead>
                            <tbody></tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- INCLUDE BATCH DETAIL MODAL -->
    @include('manage.record.offline.partials.history-detail-modal')
@endsection

@push('scripts')
<script>
    window.importHistoryConfig = {
        routes: {
            datatable: "{{ route('manage.record.offline.import-history.datatable') }}",
            summary: "{{ route('manage.record.offline.import-history.summary') }}",
            detail: "{{ route('manage.record.offline.import-history.detail', '__UUID__') }}",
            rows: "{{ route('manage.record.offline.import-history.rows', '__UUID__') }}",
            download: "{{ route('manage.record.offline.import-history.download', '__UUID__') }}"
        }
    };
</script>
<script src="{{ asset('js/jquery.dataTables.min.js') }}"></script>
<script src="{{ asset('js/dataTables.bootstrap5.min.js') }}"></script>
<script src="{{ asset('js/manage/import-history.js') }}"></script>
@endpush
