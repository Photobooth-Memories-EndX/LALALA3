@extends('layouts.sidebar-master')

@section('title', 'Import History — Offline Training — OWL LMS')

@push('styles')
<link rel="stylesheet" href="{{ asset('css/dataTables.bootstrap5.min.css') }}">
<link rel="stylesheet" href="{{ asset('css/manage/import-history.css') }}">
@endpush

@section('content')
<div class="page-body import-history-page py-4">
    <div class="container-xl">
        <!-- BREADCRUMB / BACK LINK -->
        <div class="mb-3">
            <a href="{{ route('manage.record.offline') }}" class="btn-back d-inline-flex align-items-center gap-1.5 text-decoration-none fw-medium text-secondary">
                <i class="ti ti-arrow-left"></i>
                <span>Back to Offline Records</span>
            </a>
        </div>

        <!-- PAGE HERO TITLE CARD -->
        <div class="card border-0 shadow-sm p-3 p-md-4 mb-4 rounded-3 bg-white">
            <div class="d-flex align-items-center justify-content-between flex-wrap gap-3">
                <div class="d-flex align-items-center gap-3">
                    <div class="import-history-hero-icon">
                        <i class="ti ti-history fs-1"></i>
                    </div>
                    <div>
                        <h2 class="h3 fw-bold mb-1 text-dark">Import History</h2>
                        <p class="text-muted small mb-0">Centralized audit trail of PTKP and Non PTKP spreadsheet imports, validation logs, and original file downloads.</p>
                    </div>
                </div>
                <div class="d-flex align-items-center gap-2 flex-wrap">
                    <a href="{{ route('manage.record.offline.import-ptkp') }}" class="btn btn-outline-primary rounded-3 px-3 py-2 d-inline-flex align-items-center gap-1.5" id="btn-goto-ptkp">
                        <i class="ti ti-file-upload"></i>
                        <span>Import PTKP</span>
                    </a>
                    <a href="{{ route('manage.record.offline.import-non-ptkp') }}" class="btn btn-primary rounded-3 px-3 py-2 d-inline-flex align-items-center gap-1.5" style="background-color: #d97706; border-color: #d97706;" id="btn-goto-non-ptkp">
                        <i class="ti ti-file-spreadsheet"></i>
                        <span>Import Non PTKP</span>
                    </a>
                </div>
            </div>
        </div>

        <!-- SUMMARY CARDS -->
        @include('manage.record.offline.partials.history-summary-cards')

        <!-- FILTER CARD -->
        <div class="card import-filter-card shadow-sm mb-4">
            <div class="card-body p-3 p-md-4">
                <form id="import-history-filter-form" onsubmit="return false;">
                    <div class="row g-3">
                        <div class="col-12 col-sm-6 col-lg-2">
                            <label class="form-label" for="filter-source-type">Source Type</label>
                            <select class="form-select" id="filter-source-type">
                                <option value="">All Sources</option>
                                <option value="PTKP">PTKP</option>
                                <option value="NON_PTKP">Non PTKP</option>
                            </select>
                        </div>
                        <div class="col-12 col-sm-6 col-lg-2">
                            <label class="form-label" for="filter-status">Status</label>
                            <select class="form-select" id="filter-status">
                                <option value="">All Statuses</option>
                                <option value="COMPLETED">Completed</option>
                                <option value="COMPLETED_WITH_WARNINGS">With Warnings</option>
                                <option value="FAILED">Failed</option>
                                <option value="IMPORTING">In Progress</option>
                            </select>
                        </div>
                        <div class="col-12 col-sm-6 col-lg-2">
                            <label class="form-label" for="filter-user">Imported By</label>
                            <select class="form-select" id="filter-user">
                                <option value="">All Users</option>
                                @foreach($users as $user)
                                <option value="{{ $user->id }}">{{ $user->name ?? $user->email }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-12 col-sm-6 col-lg-2">
                            <label class="form-label" for="filter-date-start">Date From</label>
                            <input type="date" class="form-control" id="filter-date-start">
                        </div>
                        <div class="col-12 col-sm-6 col-lg-2">
                            <label class="form-label" for="filter-date-end">Date To</label>
                            <input type="date" class="form-control" id="filter-date-end">
                        </div>
                        <div class="col-12 col-sm-6 col-lg-2">
                            <label class="form-label" for="filter-search">Search</label>
                            <input type="text" class="form-control" id="filter-search" placeholder="Filename / UUID...">
                        </div>
                        <div class="col-12 d-flex justify-content-end gap-2 mt-2">
                            <button type="button" class="btn btn-outline-secondary px-3" id="btn-reset-filters">
                                <i class="ti ti-refresh me-1"></i>Reset
                            </button>
                            <button type="button" class="btn btn-primary px-4" id="btn-apply-filters">
                                <i class="ti ti-filter me-1"></i>Apply Filters
                            </button>
                        </div>
                    </div>
                </form>
            </div>
        </div>

        <!-- BATCHES TABLE CARD -->
        <div class="card import-table-card shadow-sm mb-4">
            <div class="card-header border-bottom py-3 px-4 d-flex align-items-center justify-content-between">
                <h4 class="card-title fw-bold text-dark mb-0">
                    <i class="ti ti-list text-primary me-2"></i>Import Batches
                </h4>
            </div>
            <div class="table-responsive p-3">
                <table class="table table-vcenter table-hover card-table fs-5" id="import-batches-table" style="width: 100%;">
                    <thead>
                        <tr>
                            <th class="w-1 text-center">No</th>
                            <th>Batch UUID</th>
                            <th class="text-center">Type</th>
                            <th>Original Filename</th>
                            <th>Imported By</th>
                            <th>Started At</th>
                            <th>Finished At</th>
                            <th class="text-center">Total</th>
                            <th class="text-center">Imported</th>
                            <th class="text-center">Skipped</th>
                            <th class="text-center">Failed</th>
                            <th class="text-center">Status</th>
                            <th class="text-center">Actions</th>
                        </tr>
                    </thead>
                    <tbody></tbody>
                </table>
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
