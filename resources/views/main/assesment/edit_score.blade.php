@extends('layouts.sidebar-master')


@push('styles')
<style>
    [data-bs-theme="dark"] .card,
    [data-theme="dark"] .card,
    body.theme-dark .card {
        background-color: var(--tblr-bg-surface, #182433) !important;
        color: var(--tblr-body-color, #dce6f1) !important;
        border-color: var(--tblr-border-color, #25384d) !important;
    }

    [data-bs-theme="dark"] .card-header,
    [data-theme="dark"] .card-header,
    body.theme-dark .card-header {
        background-color: var(--tblr-bg-surface-secondary, #1f2d3d) !important;
        color: var(--tblr-body-color, #dce6f1) !important;
        border-color: var(--tblr-border-color, #25384d) !important;
    }

    [data-bs-theme="dark"] .card-body,
    [data-theme="dark"] .card-body,
    body.theme-dark .card-body {
        background-color: var(--tblr-bg-surface, #182433) !important;
        color: var(--tblr-body-color, #dce6f1) !important;
    }

    [data-bs-theme="dark"] .table,
    [data-theme="dark"] .table,
    body.theme-dark .table {
        color: var(--tblr-body-color, #dce6f1) !important;
        background-color: transparent !important;
        border-color: var(--tblr-border-color, #25384d) !important;
    }

    [data-bs-theme="dark"] .table thead th,
    [data-theme="dark"] .table thead th,
    body.theme-dark .table thead th {
        background-color: var(--tblr-bg-surface-secondary, #223247) !important;
        color: var(--tblr-muted, #8fa3b8) !important;
        border-color: var(--tblr-border-color, #25384d) !important;
    }

    [data-bs-theme="dark"] .table tbody tr,
    [data-theme="dark"] .table tbody tr,
    body.theme-dark .table tbody tr {
        background-color: var(--tblr-bg-surface, #182433) !important;
        color: var(--tblr-body-color, #dce6f1) !important;
    }

    [data-bs-theme="dark"] .table tbody td,
    [data-theme="dark"] .table tbody td,
    body.theme-dark .table tbody td {
        background-color: transparent !important;
        color: var(--tblr-body-color, #dce6f1) !important;
        border-color: var(--tblr-border-color, #25384d) !important;
    }

    [data-bs-theme="dark"] .table-hover tbody tr:hover,
    [data-bs-theme="dark"] .table-hover tbody tr:hover td,
    [data-theme="dark"] .table-hover tbody tr:hover,
    [data-theme="dark"] .table-hover tbody tr:hover td,
    body.theme-dark .table-hover tbody tr:hover,
    body.theme-dark .table-hover tbody tr:hover td {
        background-color: var(--tblr-bg-surface-secondary, #223247) !important;
        color: #ffffff !important;
    }

    [data-bs-theme="dark"] .bg-white,
    [data-theme="dark"] .bg-white,
    body.theme-dark .bg-white {
        background-color: var(--tblr-bg-surface, #182433) !important;
    }

    [data-bs-theme="dark"] .bg-light,
    [data-theme="dark"] .bg-light,
    body.theme-dark .bg-light {
        background-color: var(--tblr-bg-surface-secondary, #223247) !important;
    }

    [data-bs-theme="dark"] .text-dark,
    [data-theme="dark"] .text-dark,
    body.theme-dark .text-dark {
        color: var(--tblr-body-color, #dce6f1) !important;
    }

    [data-bs-theme="dark"] .text-muted,
    [data-theme="dark"] .text-muted,
    body.theme-dark .text-muted {
        color: var(--tblr-muted, #8fa3b8) !important;
    }

    [data-bs-theme="dark"] .form-control,
    [data-bs-theme="dark"] .form-select,
    [data-theme="dark"] .form-control,
    [data-theme="dark"] .form-select,
    body.theme-dark .form-control,
    body.theme-dark .form-select {
        background-color: var(--tblr-bg-surface, #111827) !important;
        color: var(--tblr-body-color, #ffffff) !important;
        border-color: var(--tblr-border-color, #25384d) !important;
    }

    [data-bs-theme="dark"] .form-control:focus,
    [data-bs-theme="dark"] .form-select:focus,
    [data-theme="dark"] .form-control:focus,
    [data-theme="dark"] .form-select:focus,
    body.theme-dark .form-control:focus,
    body.theme-dark .form-select:focus {
        background-color: var(--tblr-bg-surface, #111827) !important;
        color: #ffffff !important;
        border-color: var(--tblr-primary, #206bc4) !important;
        box-shadow: 0 0 0 .25rem rgba(32, 107, 196, .25) !important;
    }

    [data-bs-theme="dark"] .btn-light,
    [data-theme="dark"] .btn-light,
    body.theme-dark .btn-light {
        background-color: var(--tblr-bg-surface-secondary, #223247) !important;
        border-color: var(--tblr-border-color, #33465f) !important;
        color: #ffffff !important;
    }

    [data-bs-theme="dark"] .page-title,
    [data-theme="dark"] .page-title,
    body.theme-dark .page-title {
        color: var(--tblr-body-color, #ffffff) !important;
    }

    [data-bs-theme="dark"] .breadcrumb a,
    [data-theme="dark"] .breadcrumb a,
    body.theme-dark .breadcrumb a {
        color: var(--tblr-primary, #4299e1) !important;
    }

    [data-bs-theme="dark"] .select2-container--bootstrap-5 .select2-selection,
    [data-theme="dark"] .select2-container--bootstrap-5 .select2-selection,
    body.theme-dark .select2-container--bootstrap-5 .select2-selection {
        background-color: var(--tblr-bg-surface, #111827) !important;
        color: #ffffff !important;
        border-color: var(--tblr-border-color, #25384d) !important;
    }

    [data-bs-theme="dark"] .select2-container--bootstrap-5 .select2-selection__rendered,
    [data-theme="dark"] .select2-container--bootstrap-5 .select2-selection__rendered,
    body.theme-dark .select2-container--bootstrap-5 .select2-selection__rendered {
        color: #ffffff !important;
    }

    [data-bs-theme="dark"] .select2-dropdown,
    [data-theme="dark"] .select2-dropdown,
    body.theme-dark .select2-dropdown {
        background-color: var(--tblr-bg-surface, #182433) !important;
        border-color: var(--tblr-border-color, #25384d) !important;
        color: #ffffff !important;
    }

    [data-bs-theme="dark"] .select2-results__option,
    [data-theme="dark"] .select2-results__option,
    body.theme-dark .select2-results__option {
        color: #ffffff !important;
    }

    [data-bs-theme="dark"] .select2-results__option--highlighted,
    [data-theme="dark"] .select2-results__option--highlighted,
    body.theme-dark .select2-results__option--highlighted {
        background-color: var(--tblr-primary, #206bc4) !important;
        color: #ffffff !important;
    }
</style>
@endpush

@section('content')
    <div class="page-header d-print-none mb-4">
        <div class="container-xl">
            <div class="row align-items-center">
                <div class="col">
                    <div class="page-pretitle text-muted">
                        Learning Management System
                    </div>

                    <h2 class="page-title fw-bold">
                        <i class="fas fa-pen-to-square me-2 text-primary"></i>
                        Assessment Edit Score
                    </h2>
                </div>

                <div class="col-auto">
                    <ol class="breadcrumb breadcrumb-arrows mb-0" aria-label="breadcrumbs">
                        <li class="breadcrumb-item">
                            <a href="#">Manage</a>
                        </li>
                        <li class="breadcrumb-item">
                            <a href="{{ route('assesment.index') }}">Assessment</a>
                        </li>
                        <li class="breadcrumb-item active" aria-current="page">
                            Edit Score
                        </li>
                    </ol>
                </div>
            </div>
        </div>
    </div>

    <div class="page-body">
        <div class="container-xl">

            @if (session()->has('success'))
                <div class="alert alert-success alert-dismissible shadow-sm" role="alert">
                    <div class="d-flex align-items-center">
                        <div class="me-3">
                            <i class="fas fa-check-circle"></i>
                        </div>

                        <div>
                            <h4 class="alert-title mb-0">
                                {{ session('success') }}
                            </h4>
                        </div>
                    </div>

                    <a class="btn-close" data-bs-dismiss="alert" aria-label="close"></a>
                </div>
            @endif

            <div class="card border-0 shadow-sm">

                <div class="card-header bg-primary-lt">
                    <div class="row align-items-center w-100">
                        <div class="col">
                            <h3 class="card-title fw-bold mb-1">
                                <i class="fas fa-file-signature me-2 text-primary"></i>
                                Form Edit Score Assessment
                            </h3>

                            <div class="text-muted small">
                                Update employee assessment score, paper based status, and evidence file.
                            </div>
                        </div>

                        <div class="col-auto">
                            <span class="badge bg-primary-lt text-primary">
                                Edit Score
                            </span>
                        </div>
                    </div>
                </div>

                <div class="card-body">

                    <form action="{{ route('assesment.edit.score.update') }}" method="post" enctype="multipart/form-data">
                        @csrf

                        <div class="card bg-dark-lt border-0 mb-4">
                            <div class="card-header">
                                <h3 class="card-title">
                                    <i class="fas fa-clipboard-list me-2"></i>
                                    Assessment Information
                                </h3>
                            </div>

                            <div class="card-body">

                                <div class="mb-4">
                                    <label for="assessment" class="form-label required">
                                        Assessment
                                    </label>

                                    <select class="form-select select2" id="assessment" name="assessment">
                                        <option value="">-- Choose Assessment --</option>

                                        @foreach ($assessments as $assessment)
                                            <option value="{{ $assessment->id }}" @selected(old('assessment') == $assessment->id)>
                                                {{ $assessment->title }}
                                            </option>
                                        @endforeach
                                    </select>

                                    @error('assessment')
                                        <div class="invalid-feedback d-block">
                                            {{ $message }}
                                        </div>
                                    @enderror
                                </div>

                                <div class="mb-0">
                                    <label for="employee" class="form-label required">
                                        Employee
                                    </label>

                                    <select class="form-select select2" id="employee" name="employee">
                                        <option value="">-- Choose Employee --</option>

                                        @foreach ($employees as $employee)
                                            <option value="{{ $employee->id_emp }}" @selected(old('employee') == $employee->id_emp)>
                                                {{ $employee->id_emp . ' | ' . $employee->name }}
                                            </option>
                                        @endforeach
                                    </select>

                                    @error('employee')
                                        <div class="invalid-feedback d-block">
                                            {{ $message }}
                                        </div>
                                    @enderror
                                </div>

                            </div>
                        </div>

                        <div class="card bg-dark-lt border-0 mb-4">
                            <div class="card-header">
                                <h3 class="card-title">
                                    <i class="fas fa-file-alt me-2"></i>
                                    Score & Evidence
                                </h3>
                            </div>

                            <div class="card-body">

                                <div class="row">

                                    <div class="col-md-3">
                                        <div class="mb-4">
                                            <label for="is-paper-based" class="form-label">
                                                Paper Based
                                            </label>

                                            <select name="is_paper_based" id="is-paper-based" class="form-select">
                                                <option value="0" @selected(old('is_paper_based') == 0)>No</option>
                                                <option value="1" @selected(old('is_paper_based') == 1)>Yes</option>
                                            </select>
                                        </div>
                                    </div>

                                    <div class="col-md-6">
                                        <div class="mb-4">
                                            <label for="evidence-paper-based" class="form-label">
                                                Evidances Paper Based
                                            </label>

                                            <input
                                                type="file"
                                                class="form-control"
                                                id="evidence-paper-based"
                                                name="evidence_paper_based"
                                                accept=".pdf" />

                                            <small class="form-hint">
                                                Upload PDF file only.
                                            </small>

                                            @error('evidence_paper_based')
                                                <div class="invalid-feedback d-block">
                                                    {{ $message }}
                                                </div>
                                            @enderror
                                        </div>
                                    </div>

                                    <div class="col-md-3">
                                        <div class="mb-4">
                                            <label for="score" class="form-label required">
                                                Score
                                            </label>

                                            <input
                                                type="number"
                                                class="form-control"
                                                id="score"
                                                name="score"
                                                placeholder="0"
                                                value="{{ old('score') }}"
                                                autocomplete="off" />

                                            @error('score')
                                                <div class="invalid-feedback d-block">
                                                    {{ $message }}
                                                </div>
                                            @enderror
                                        </div>
                                    </div>

                                </div>

                            </div>
                        </div>

                        <div class="d-flex justify-content-end">
                            <button type="submit" class="btn btn-primary px-5">
                                <i class="fas fa-save me-2"></i>
                                Update
                            </button>
                        </div>

                    </form>

                </div>
            </div>

        </div>
    </div>
@endsection



@push('scripts')
    <script>
        $('.select2').select2({
            theme: 'bootstrap-5',
        });
    </script>
@endpush